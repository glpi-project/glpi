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
    function updateProgress(single_message_element, progress_element, value, max, text) {
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

    function startDatabaseInstall()
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
            checkProgress(message_list_element, single_message_element, progress_element);
        }, 1500);

        fetch("/install/database_setup/start_db_inserts", {
            method: 'POST',
        })
            .then((res) => res.text())
            .then((text) => {
                if (text && text.trim().length) {
                    message(message_list_element, `Error:\n${text}`);
                } else {
                    updateProgress(single_message_element, progress_element, 1, 1);
                    success_element.querySelector('button').removeAttribute('disabled');
                }
            })
            .catch(function (err) {
                message(message_list_element, `Database install error:\n${err.message||err.toString()}`);
                updateProgress(single_message_element, progress_element, 0, 10);
            });
    }

    function checkProgress(message_list_element, single_message_element, progress_element)
    {
        setTimeout(() => {

            fetch("/install/database_setup/check_progress", {
                method: 'POST',
            })
                .then((res) => {
                    if (res.status === 404) {
                        // Progress not found, let's stop here.
                        updateProgress(single_message_element, progress_element, 1, 1);
                        return null;
                    }

                    if (res.status >= 300) {
                        throw new Error(`Invalid response from server, expected 200 or 404, found "${res.status}".`);
                    }

                    return res.json();
                })
                .then((json) => {
                    if (json.started_at) {
                        if (json.finished_at) {
                            // Finished, nothing else to do!
                            updateProgress(single_message_element, progress_element, 1, 1, json.data);
                            return;
                        }

                        updateProgress(single_message_element, progress_element, json.current, json.max, json.data);

                        checkProgress(message_list_element, single_message_element, progress_element);
                        return;
                    }

                    console.info(json);
                    message(message_list_element, `Result Error when checking progress:\n${JSON.stringify(json)}`);
                })
                .catch((err) => {
                    message(message_list_element, `Request Error when checking progress:\n${err.message || err.toString()}`);
                    updateProgress(single_message_element, progress_element, 0, 1);
                });

        }, 500);
    }

    window.startDatabaseInstall = startDatabaseInstall;
})();
