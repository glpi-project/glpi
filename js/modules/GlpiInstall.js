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
import { ProgressBar } from './modules/ProgressBar.js';

export async function start_database_install(dom_element, progress_key)
{
    function message(message_list_element, text) {
        const alert = document.createElement('p');
        alert.innerHTML = text;
        message_list_element.appendChild(alert);
    }

    if (!dom_element) {
        throw new Error('No DOM element provided to start database install.');
    }
    const messages_container = document.getElementById('glpi_install_messages_container');
    const success_container = document.getElementById('glpi_install_success');
    const back_button_container = document.getElementById('glpi_install_back');

    const message_list_element = document.createElement('div');

    success_container.querySelector('button').setAttribute('disabled', 'disabled');
    back_button_container.querySelector('input').setAttribute('disabled', 'disabled');

    const progress = new ProgressBar({
        key: progress_key,
        container: messages_container,
        error_callback: (msg) => {
            if (msg.match('timed out')) {
                message(message_list_element, msg);
            } else {
                message(message_list_element, __('An unexpected error has occurred.'));
            }
        },
    });

    progress.init();

    messages_container.appendChild(message_list_element);

    setTimeout(() => {
        progress.start();
    }, 1500);

    try {
        await fetch("/install/database_setup/start_db_inserts", {method: 'POST'});
    } catch (err) {
    } finally {
        back_button_container.querySelector('input').removeAttribute('disabled');
    }
}
