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
(() => {
    function update_progress(single_message_element, progress_element, value, max, text) {
        value = value || 0;
        max = max || 1;
        const percentage = (value / max * 100);

        const bar = progress_element.querySelector('.progress-bar');
        bar.style.width = `${percentage}%`;
        bar.innerHTML = `${Math.floor(percentage)}%`;
        bar.setAttribute('aria-valuenow', percentage);

        if (text && text.length) {
            console.info('Text', {text});
            single_message_element.innerHTML = text.trim().replace(/\n/gi, '<br>');
        }
    }

    function message(message_list_element, text) {
        const alert = document.createElement('p');
        alert.innerHTML = text;
        message_list_element.appendChild(alert);
    }

    async function start_database_install(progress_key)
    {
        const message_element_id = 'glpi_install_messages_container';
        const success_element_id = 'glpi_install_success';

        const messages_container = document.getElementById(message_element_id);
        const success_element = document.getElementById(success_element_id);

        const progress_container_element = document.createElement('p');
        const progress_element = document.createElement('div');
        progress_element.className = "progress";
        progress_element.style.height = '15px';
        progress_element.innerHTML = '<div class="progress-bar bg-info" role="progressbar" style="width:0;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>';
        progress_container_element.appendChild(progress_element);
        const message_list_element = document.createElement('div');
        const single_message_element = document.createElement('p');

        success_element.querySelector('button').setAttribute('disabled', 'disabled');

        messages_container.appendChild(message_list_element);
        messages_container.appendChild(single_message_element);
        messages_container.appendChild(progress_container_element);

        setTimeout(() => {
            check_progress({
                key: progress_key,
                error_callback: (error) => {
                    message(message_list_element, `Progress error:\n${error}`);
                    update_progress(single_message_element, progress_element, 0, 1);
                },
                progress_callback: (json) => {
                    if (!json || Object.keys(json).length === 0 || json['finished_at']) {
                        update_progress(single_message_element, progress_element, 100, 100, json['finished_at'] ? json.data : null);
                        return false;
                    }
                    update_progress(single_message_element, progress_element, json.current, json.max, json.data);
                },
            });
        }, 1500);

        try {
            const res = await fetch("/install/database_setup/start_db_inserts", {method: 'POST'});
            const text = await res.text();
            if (text && text.trim().length) {
                message(message_list_element, `Error:\n${text}`);
            } else {
                update_progress(single_message_element, progress_element, 1, 1);
                success_element.querySelector('button').removeAttribute('disabled');
            }
        } catch (err) {
            message(message_list_element, `Database install error:\n${err.message||err.toString()}`);
            update_progress(single_message_element, progress_element, 0, 1);
        }
    }

    /**
     * @param parameters
     * @param {string} parameters.key Mandatory. The progress bar's unique key.
     * @param {function} parameters.progress_callback The function that will be called for each progress response. If the return value is "false", this stops the progress checks.
     * @param {function} parameters.error_callback The function that will be called for each error, either exceptions or non-200 HTTP responses. Stops the progress checks by default, unless you return a true-ish value from the callback.
     */
    function check_progress(parameters)
    {
        if (!parameters.key) {
            throw new Error('Progress key is mandatory.');
        }

        const start_timeout = 250;

        setTimeout(async () => {
            try {
                const res = await fetch('/progress/check/' + parameters.key, {
                    method: 'POST',
                });

                if (res.status === 404) {
                    const cb_err_result = parameters.error_callback('Not found');
                    if (!cb_err_result) {
                        return;
                    }
                }

                if (res.status >= 300) {
                    parameters.error_callback(`Invalid response from server, expected 200 or 404, found "${res.status}".`);
                    return;
                }

                const json =  await res.json();

                debugger;
                if (json['key'] && json['started_at']) {
                    if (parameters.progress_callback(json) !== false) {
                        // Recursive call, including the timeout
                        check_progress(parameters);
                    }

                    return;
                }

                parameters.error_callback(`Result error when checking progress:\n${err.message || err.toString()}`);
            } catch (err) {
                parameters.error_callback(`Request error when checking progress:\n${err.message || err.toString()}`);
            }
        }, start_timeout);
    }

    window.start_database_install = start_database_install;
})();
