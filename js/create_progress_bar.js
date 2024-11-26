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

/**
 * @param     parameters
 * @param     {HTMLElement} parameters.container Mandatory. The progress bar's unique key.
 * @param     {string} parameters.key Mandatory. The progress bar's unique key.
 * @param     {null|function} parameters.progress_callback The function that will be called for each progress response. If the return value is "false", this stops the progress checks.
 * @param     {null|function} parameters.error_callback The function that will be called for each error, either exceptions or non-200 HTTP responses. Stops the progress checks by default, unless you return a true-ish value from the callback, or unless the error is non-recoverable and implies stopping
 * @return    {{start: function, error: function}}
 */
function create_progress_bar(parameters)
{
    if (!parameters.key) {
        throw new Error('Progress key is mandatory.');
    }
    if (!parameters.container) {
        throw new Error('Progress container is mandatory.');
    }
    if (!(parameters.container instanceof HTMLElement)) {
        throw new Error(`Progress key must be an HTML element, "${parameters.container?.constructor?.name || typeof parameters.container}" found.`);
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
    const progress_bar = main_container.querySelector('.progress-bar');
    const messages_container = main_container.querySelector('.messages_container');

    parameters.container.appendChild(main_container);

    const start_timeout = 250;

    let is_running = true;
    let abort_controller = new AbortController();

    /**
     * @param {null|number} percentage
     */
    function set_bar_percentage(percentage) {
        progress_bar.style.width = `${typeof percentage === 'number' ? percentage : 0}%`;
        progress_bar.innerHTML = typeof percentage === 'number' ? `${Math.floor(percentage)} %` : '-';
        progress_bar.setAttribute('aria-valuenow', percentage || '0');

    }

    /**
     * @param {number} value
     * @param {number} max
     * @param {null|string} text
     */
    function update_progress(value, max, text) {
        value = value || 0;
        max = max || 1;
        const percentage = (value / max * 100);

        set_bar_percentage(percentage);

        if (text && text.length) {
            messages_container.innerHTML = _.escape(text.trim()).replace(/\n/gi, '<br>');
        }
    }

    function stop_progress_with_warning_state() {
        is_running = false;

        set_bar_percentage(null);
        progress_bar.classList.remove('bg-info');
        progress_bar.classList.add('bg-warning');
    }

    async function check_progress() {
        is_running = true;
        setTimeout(async () => {
            try {
                const res = await fetch(`/progress/check/${parameters.key}`, {
                    method: 'POST',
                    signal: abort_controller.signal,
                });

                if (res.status === 404) {
                    const cb_err_result = parameters?.error_callback(__('Not found'));
                    if (!cb_err_result) {
                        stop_progress_with_warning_state();
                        return;
                    }
                }

                if (res.status >= 300) {
                    parameters?.error_callback(__('Invalid response from server, expected 200 or 404, found "%s".').replace('%s', res.status.toString()));
                    stop_progress_with_warning_state();
                    return;
                }

                const json =  await res.json();

                if (json['key'] && json['started_at'] && json['updated_at']) {
                    update_progress(json.current, json.max, json.data);

                    const now = new Date().getTime();
                    const updated_at = new Date(json['updated_at']).getTime();
                    const diff = now - updated_at;
                    const max_diff = 1000 * 45;// 45 seconds
                    if (diff > max_diff) {
                        parameters?.error_callback(__('Main process timed out'));
                        stop_progress_with_warning_state();
                        return;
                    }

                    if (
                        (
                            !parameters.progress_callback
                            || (parameters.progress_callback && parameters.progress_callback(json) !== false)
                        )
                        && !json['finished_at']
                    ) {
                        // Recursive call, including the timeout
                        await check_progress();
                    }

                    return;
                }

                parameters?.error_callback(__('JSON returned by progress check endpoint is invalid.'));
                stop_progress_with_warning_state();
            } catch (err) {
                parameters?.error_callback(__(`Request error when checking progress:\n%s`).replace('%s', err.message || err.toString()));
                stop_progress_with_warning_state();
            }
        }, start_timeout);
    }

    function error() {
        if (!is_running) {
            return;
        }

        try {
            abort_controller.abort();
        } finally {
            abort_controller = new AbortController();
        }
        stop_progress_with_warning_state();
    }

    function start() {
        progress_bar.classList.add('bg-info');
        progress_bar.classList.remove('bg-warning');
        set_bar_percentage(0);

        check_progress().then(() => {});
    }

    return {
        start,
        error,
    };
}
