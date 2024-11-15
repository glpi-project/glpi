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

let request_running = false;

function startDatabaseInstall(message_element_id, success_html)
{
    const message_element = document.getElementById(message_element_id);
    if (!message_element) {
        const alert = document.createElement('p');
        alert.innerText = 'Could not load HtmlElement to append messages to.';
        document.body.appendChild(alert);
        throw new Error(alert.innerText);
    }

    const single_message_element = document.createElement('p');
    const msg_list_element = document.createElement('div');
    message_element.appendChild(msg_list_element);
    msg_list_element.appendChild(single_message_element);

    function message(text) {
        const alert = document.createElement('p');
        alert.innerHTML = text;
        msg_list_element.appendChild(alert);
    }
    function queries_message(amount) {
        single_message_element.innerHTML = `Process: ${amount}`;
    }

    request_running = true;

    setTimeout(() => {
        checkProgress(queries_message);
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
                message(`Error:\n${text}`);
            } else {
                message(success_html);
                queries_message('✅');
            }
        })
        .finally(() => request_running = false);
}

let previous_count = 0;
let previous_state_commutator = 1;

function checkProgress(message_fn)
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
                    return request_running ? checkProgress(message_fn) : null;
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
                    message_fn("Data received:");
                    let msg = json.current.toString();

                    msg += (new Array(previous_state_commutator)).fill('.').join('');
                    previous_state_commutator ++;
                    if (previous_state_commutator >= 4) {
                        previous_state_commutator = 1;
                    }

                    previous_count = json.current;

                    message_fn(msg);

                    return checkProgress(message_fn);
                } else if (json) {
                    message_fn(`Error:\n${json}`);
                }
            })
            .catch((err) => {
                return message_fn(err.message || err.toString());
            });

    }, 500);
}
