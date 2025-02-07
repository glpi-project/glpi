/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

/* global _ */

export class ProgressBar
{
    #progress_bar;
    #messages_container;
    #main_container;
    #parameters;
    #initialized = false;

    /**
     * @param parameters
     * @param {HTMLElement} parameters.container Mandatory. The progress bar's unique key.
     * @param {string} parameters.key Mandatory. The progress bar's unique key.
     * @param {null|function} parameters.progress_callback The function that will be called for each progress response. If the return value is "false", this stops the progress checks.
     * @param {function} parameters.error_callback The function that will be called for each error, either exceptions or non-200 HTTP responses. Stops the progress.
     * @param {function} parameters.success_callback The function that will be called for when the progress has ended and is at 100%.
     */
    constructor({
        container,
        key,
        progress_callback = () => {},
        error_callback = () => {},
        success_callback = () => {},
    }) {
        if (!key) {
            throw new Error('Progress key is mandatory.');
        }
        if (!container) {
            throw new Error('Progress container is mandatory.');
        }
        if (!(container instanceof HTMLElement)) {
            throw new Error(`Progress key must be an HTML element, "${container?.constructor?.name || typeof container}" found.`);
        }

        progress_callback ??= () => {};
        error_callback ??= () => {};
        success_callback ??= () => {};

        const main_container = document.createElement('div');
        main_container.innerHTML = `
        <div style="padding-left: 20px; padding-right: 20px;">
            <div class="progress" style="height: 15px;">
                <div class="progress-bar bg-info" role="progressbar" style="width:0;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
            </div>
            <div class="messages_container"></div>
        </div>
        `;

        this.#main_container = main_container;
        this.#progress_bar = main_container.querySelector('.progress-bar');
        this.#messages_container = main_container.querySelector('.messages_container');
        this.#parameters = { container, key, progress_callback, error_callback, success_callback };
    }

    init() {
        if (this.#initialized) {
            throw new Error('Progress bar must not be initialized more than once.');
        }

        this.#initialized = true;
        this.#parameters.container.appendChild(this.#main_container);
    }

    start() {
        this.#progress_bar.classList.add('bg-info');
        this.#progress_bar.classList.remove('bg-warning');
        this.#set_bar_percentage(0);
        this.#check_progress();
    }

    /**
     * @param {number} value
     * @param {number} max
     * @param {null|string} text
     */
    #update_progress(value, max, text) {
        value = value || 0;
        max = max || 1;
        const percentage = (value / max * 100);

        this.#set_bar_percentage(percentage);

        if (text?.length) {
            this.#messages_container.innerHTML = _.escape(text.trim()).replace(/\n/gi, '<br>');
        }
    }
    /**
     * @param {null|number} percentage
     */
    #set_bar_percentage(percentage) {
        this.#progress_bar.style.width = `${typeof percentage === 'number' ? percentage : 0}%`;
        this.#progress_bar.innerHTML = typeof percentage === 'number' ? `${Math.floor(percentage)} %` : '-';
        this.#progress_bar.setAttribute('aria-valuenow', percentage || '0');
    }

    #stop_progress_with_warning_state() {
        this.#progress_bar.innerHTML = __('failed');
        this.#progress_bar.classList.remove('bg-info');
        this.#progress_bar.classList.add('bg-danger');
    }

    #check_progress() {
        const _this = this;

        const start_timeout = 250;

        setTimeout(async () => {
            try {
                const res = await fetch(`${CFG_GLPI.root_doc}/progress/check/${_this.#parameters.key}`);

                if (res.status === 404) {
                    throw new Error('Not found');
                }

                if (res.status >= 400) {
                    throw new Error(`Error response from server with code "${res.status.toString()}".`);
                }

                const json =  await res.json();

                if (!json['key'] || !json['started_at'] || !json['updated_at']) {
                    throw new Error('JSON returned by progress check endpoint is invalid.');
                }

                this.#update_progress(json.current, json.max, json.data);

                if (json['failed']) {
                    throw new Error('Progress has failed for an unknown reason.');
                }

                const now = new Date().getTime();
                const updated_at = new Date(json['updated_at']).getTime();
                const diff = now - updated_at;
                const max_diff = 1000 * 20; // 20 seconds timeout
                if (diff > max_diff) {
                    _this.#parameters?.error_callback(__('Main process seems to have timed out. It may be still running in the background though.'));
                    _this.#stop_progress_with_warning_state();
                    return;
                }

                if (json['finished_at']) {
                    this.#parameters.success_callback();
                    return;
                }

                // Recursive call, including the timeout
                _this.#check_progress();
            } catch (err) {
                _this.#parameters?.error_callback(__('An unexpected error has occurred.'));
                _this.#stop_progress_with_warning_state();
                throw err;
            }
        }, start_timeout);
    }
}
