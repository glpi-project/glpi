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
    let request_running = false;

    function updateProgress(progress_element, value, max) {
        // Trick for visual aid to understand it's not yet finished.
        // Values "100" are used when it's *really* finished
        if (max !== 100 && value !== 100 && value && max === value) {
            value = (max * 0.98).toFixed(0);
        }

        if (value) {
            progress_element.value = value;
        } else {
            progress_element.removeAttribute('value');
        }

        if (max) {
            progress_element.max = max;
        } else {
            progress_element.removeAttribute('max');
        }
    }

    function message(msg_list_element, text) {
        const alert = document.createElement('p');
        alert.innerHTML = text;
        msg_list_element.appendChild(alert);
    }

    function startDatabaseInstall()
    {
        const message_element_id = 'glpi_install_messages_container';
        const success_element_id = 'glpi_install_success';

        const messages_container = document.getElementById(message_element_id);
        const success_element = document.getElementById(success_element_id);

        const progress_container_element = document.createElement('p');
        const progress_element = document.createElement('progress');
        progress_container_element.appendChild(progress_element);
        const message_element = document.createElement('div');

        success_element.querySelector('button').setAttribute('disabled', true);
        
        messages_container.appendChild(message_element);
        messages_container.appendChild(progress_container_element);

        request_running = true;

        setTimeout(() => {
            checkProgress(message_element, progress_element);
        }, 1500);

        fetch("/install/database_setup/start_db_inserts", {
            method: 'POST',
            headers: {
                'Content-Type': 'text/plain'
            },
        })
            .then((res) => res.text())
            .then((text) => {
                if (text && text.trim().length) {
                    message(message_element, `Error:\n${text}`);
                } else {
                    updateProgress(progress_element, 100, 100);
                    success_element.querySelector('button').removeAttribute('disabled');
                }
            })
            .catch(err => message(message_element, `Request error:\n${err.message||err.toString()}`))
            .finally(() => {
                request_running = false;
            });
    }

    function checkProgress(message_element, progress_element)
    {
        if (!request_running) {
            return;
        }

        setTimeout(() => {

            fetch("/install/database_setup/check_progress", {
                method: 'POST',
            })
                .then((res) => {
                    if (res.status === 404) {
                        // Progress not found, let's continue when necessary.
                        return request_running ? checkProgress(message_element, progress_element) : null;
                    }

                    if (res.status >= 300) {
                        throw new Error('Invalid response from progress check.');
                    }

                    return res.json();
                })
                .then((json) => {
                    if (!request_running) {
                        return;
                    }

                    if (json && json.current) {
                        updateProgress(progress_element, json.current, json.max);

                        return checkProgress(message_element, progress_element);
                    } else if (json) {
                        message(message_element, `Error:\n${json}`);
                    }
                })
                .catch((err) => message(message_element, err.message || err.toString()));

        }, 500);
    }

    window.startDatabaseInstall = startDatabaseInstall;
})();
