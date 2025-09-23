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

/* global _, getAjaxCsrfToken */

export class ProgressIndicator
{
    /**
     * The progress indicator unique key.
     * @type {string}
     */
    #key;

    /**
     * The HTML container that will contain the progress indicator.
     * @type {HTMLElement}
     */
    #container;

    /**
     * The request corresponding to the operation to execute.
     * @type {Request}
     */
    #request;

    /**
     * The function that will be called if the operation succeed.
     * @type {Function}
     */
    #success_callback;

    /**
     * The function that will be called if the operation fails.
     * @type {Function}
     */
    #error_callback;

    /**
     * Indicates whether the operation already started.
     * @type {boolean}
     */
    #started = false;

    /**
     * Progress indicator refresh timeout (milliseconds).
     * @type {Number}
     */
    #refresh_timeout = 250;

    /**
     * Last displayed message index.
     * @type {Number}
     */
    #last_message_index = -1;

    /**
     * @param parameters
     * @param {HTMLElement} parameters.container Mandatory. The HTML container that will contain the progress indicator.
     * @param {Request} parameters.request Mandatory. The request corresponding to the operation to execute.
     * @param {Function} parameters.success_callback The function that will be called if the operation succeed.
     * @param {Function} parameters.error_callback The function that will be called if the operation fails.
     */
    constructor({
        container,
        request,
        success_callback = () => {},
        error_callback = () => {},
    }) {
        if (!(container instanceof HTMLElement)) {
            throw new Error(`\`container\` must be an \`HTMLElement\`, "${container?.constructor?.name || typeof container}" found.`);
        }
        if (!(request instanceof Request)) {
            throw new Error(`\`request\` must be a \`Request\`, "${request?.constructor?.name || typeof request}" found.`);
        }

        this.#container = container;
        this.#request = request;
        this.#success_callback = success_callback;
        this.#error_callback = error_callback;
    }

    /**
     * Start the operation.
     */
    async start() {
        if (this.#started) {
            throw new Error('Progress indicator must not be started more than once.');
        }
        this.#started = true;

        const self_dom_el = document.createElement('div');
        self_dom_el.innerHTML = `
        <div class="progress-indicator" style="padding-left: 20px; padding-right: 20px;">
            <div class="progress" style="height: 15px;">
                <div class="progress-bar bg-info" role="progressbar" style="width:0;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
            </div>
            <div class="progress-msg-container"></div>
            <div class="messages-container card text-dark my-3 p-2 d-none"></div>
        </div>
        `;
        this.#container.appendChild(self_dom_el);

        const response = await fetch(this.#request);

        // Read the first chunk only (the one that contains the progress indicator key).
        // Reading the full response will make the fetch API to wait for the initial request to be fully processed,
        // because even if the browser consider the reponse as fully received, the TCP connection is still alive
        // until the end of the operation, and the fetch API waits for the TCP connection to be closed
        // to consider the response as fully received.
        const encoded_key = (await response.body.getReader().read()).value;

        this.#key = (new TextDecoder()).decode(encoded_key);

        setTimeout(
            () => {
                this.#check_progress();
            },
            this.#refresh_timeout
        );
    }

    /**
     * Update the progress bar display.
     *
     * @param {number} current_step
     * @param {number} max_steps
     * @param {string} progress_message
     */
    #update_progress_bar(current_step, max_steps, progress_message) {
        const percentage = current_step / max_steps * 100;

        const progress_bar_dom_el = this.#container.querySelector('.progress-indicator > .progress > .progress-bar');
        progress_bar_dom_el.style.width = `${percentage}%`;
        progress_bar_dom_el.innerHTML = `${Math.floor(percentage)} %`;
        progress_bar_dom_el.setAttribute('aria-valuenow', percentage);

        const progress_message_dom_el = this.#container.querySelector('.progress-indicator > .progress-msg-container');
        progress_message_dom_el.innerHTML = _.escape(progress_message.trim()).replace(/\n/gi, '<br>');
    }

    /**
     * Change the progress bar style to indicates that the operation failed.
     */
    #show_progress_failure() {
        const progress_bar_dom_el = this.#container.querySelector('.progress-indicator > .progress > .progress-bar');
        progress_bar_dom_el.innerHTML = __('failed');
        progress_bar_dom_el.classList.remove('bg-info');
        progress_bar_dom_el.classList.add('bg-danger');
    }

    /**
     * Check the operation progression.
     */
    async #check_progress() {
        try {
            const response = await fetch(`${CFG_GLPI.root_doc}/progress/check/${this.#key}`);

            if (response.status >= 400) {
                throw new Error(`Error response from server with code "${response.status.toString()}".`);
            }

            const json = await response.json();

            this.#update_progress_bar(json['current_step'], json['max_steps'], json['progress_bar_message']);

            for (let i = this.#last_message_index + 1; i < json['messages'].length; i++) {
                const entry = json['messages'][i];
                if (entry.type === 'debug') {
                    continue; // ignore debug messages
                }
                this.#display_message(entry.type, entry.message);
                this.#last_message_index = i;
            }

            if (json['failed'] === true) {
                this.#error_callback();
                this.#show_progress_failure();
                return;
            }

            if (json['ended_at']) {
                this.#success_callback();
                return;
            }

            setTimeout(
                () => {
                    this.#check_progress();
                },
                this.#refresh_timeout
            );
        } catch (err) {
            this.#display_message(
                'error',
                _.unescape(__('An unexpected error occurred'))
            );
            this.#error_callback();
            this.#show_progress_failure();
            throw err;
        }
    }

    /**
     * Display a message in the messages container.
     *
     * @param {string} type
     * @param {string} text
     */
    #display_message(type, text) {
        let icon_class = '';

        switch (type) {
            case 'error':
                icon_class  = 'ti ti-exclamation-circle-filled text-danger';
                break;
            case 'warning':
                icon_class  = 'ti ti-alert-triangle text-warning';
                break;
            case 'notice':
                icon_class  = 'ti ti-info-circle text-info';
                break;
            case 'success':
                icon_class  = 'ti ti-check text-success';
                break;
        }

        const message_element = document.createElement('div');
        message_element.innerHTML = `
            <i class="${icon_class} align-middle"></i>
            ${_.escape(text)}
        `;

        const messages_container = this.#container.querySelector('.progress-indicator > .messages-container');
        messages_container.appendChild(message_element);
        messages_container.classList.remove('d-none');
    }
}
