/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

import _ from 'lodash';

export class ProgressBar
{
    #progress_bar;
    #messages_container;
    #main_container;
    #parameters;
    #abort_controller;
    #request_running = false;
    #initialized = false;

    /**
     * @param parameters
     * @param {HTMLElement} parameters.container Mandatory. The progress bar's unique key.
     * @param {string} parameters.key Mandatory. The progress bar's unique key.
     * @param {null|function} parameters.progress_callback The function that will be called for each progress response. If the return value is "false", this stops the progress checks.
     * @param {null|function} parameters.error_callback The function that will be called for each error, either exceptions or non-200 HTTP responses. Stops the progress checks by default, unless you return a true-ish value from the callback, or unless the error is non-recoverable and implies stopping
     */
    constructor({
        container,
        key,
        progress_callback = () => {},
        error_callback = () => {},
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
        this.#parameters = { container, key, progress_callback, error_callback };
    }

    init() {
        if (this.#initialized) {
            throw new Error('Progress bar must not be initialized more than once.');
        }

        this.#initialized = true;
        this.#parameters.container.appendChild(this.#main_container);

        this.#abort_controller = new AbortController();
    }

    start() {
        this.#progress_bar.classList.add('bg-info');
        this.#progress_bar.classList.remove('bg-warning');
        this.#set_bar_percentage(0);
        this.#check_progress();
    }

    error() {
        if (!this.#request_running) {
            return;
        }

        try {
            this.#abort_controller.abort();
        } finally {
            this.#abort_controller = new AbortController();
        }
        this.#stop_progress_with_warning_state();
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
        this.#request_running = false;

        this.#set_bar_percentage(null);
        this.#progress_bar.classList.remove('bg-info');
        this.#progress_bar.classList.add('bg-warning');
    }

    #check_progress() {
        this.#request_running = true;

        const _this = this;

        const start_timeout = 250;

        setTimeout(async () => {
            try {
                const res = await fetch(`/progress/check/${_this.#parameters.key}`, {
                    method: 'POST',
                    signal: _this.#abort_controller.signal,
                });

                if (res.status === 404) {
                    const cb_err_result = _this.#parameters?.error_callback(__('Not found'));
                    if (!cb_err_result) {
                        _this.#stop_progress_with_warning_state();
                        return;
                    }
                }

                if (res.status >= 400) {
                    _this.#parameters?.error_callback(__('Error response from server with code "%s".').replace('%s', res.status.toString()));
                    _this.#stop_progress_with_warning_state();
                    return;
                }

                const json =  await res.json();

                if (json['key'] && json['started_at'] && json['updated_at']) {
                    this.#update_progress(json.current, json.max, json.data);

                    const now = new Date().getTime();
                    const updated_at = new Date(json['updated_at']).getTime();
                    const diff = now - updated_at;
                    const max_diff = 1000 * 45;// 45 seconds
                    if (diff > max_diff) {
                        _this.#parameters?.error_callback(__('Main process seems to have timed out. It may be still running in the background though.'));
                        _this.#stop_progress_with_warning_state();
                        return;
                    }

                    if (
                        (
                            !_this.#parameters.progress_callback
                            || (_this.#parameters.progress_callback && _this.#parameters.progress_callback(json) !== false)
                        )
                        && !json['finished_at']
                    ) {
                        // Recursive call, including the timeout
                        _this.#check_progress();
                    }

                    return;
                }

                _this.#parameters?.error_callback(__('JSON returned by progress check endpoint is invalid.'));
                _this.#stop_progress_with_warning_state();
            } catch (err) {
                _this.#parameters?.error_callback(__(`Request error when checking progress:\n%s`).replace('%s', err.message || err.toString()));
                _this.#stop_progress_with_warning_state();
            }
        }, start_timeout);
    }
}
