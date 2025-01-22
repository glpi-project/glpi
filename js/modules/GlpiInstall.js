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

/* global getAjaxCsrfToken */

import { ProgressBar } from './ProgressBar.js';

export async function init_database(progress_key)
{
    function message(message_list_element, text) {
        const alert = document.createElement('div');
        alert.setAttribute('class', 'alert alert-important alert-danger my-2 mx-4');
        alert.setAttribute('role', 'alert');
        alert.innerHTML = `
            <i class="fas fa-2x fa-exclamation-triangle align-middle"></i>
            ${text}
        `;
        message_list_element.appendChild(alert);
    }

    const messages_container = document.getElementById('glpi_install_messages_container');
    const success_container = document.getElementById('glpi_install_success');
    const back_button_container = document.getElementById('glpi_install_back');

    const message_list_element = document.createElement('div');

    const progress = new ProgressBar({
        key: progress_key,
        container: messages_container,
        success_callback: () => {
            success_container.querySelector('button[type="submit"]').removeAttribute('disabled');
            success_container.setAttribute('class', 'd-inline');
        },
        error_callback: (msg) => {
            back_button_container.querySelector('button[type="submit"]').removeAttribute('disabled');
            back_button_container.setAttribute('class', 'd-inline');
            message(message_list_element, msg);
        },
    });

    progress.init();

    messages_container.appendChild(message_list_element);

    setTimeout(() => {
        progress.start();
    }, 1500);

    try {
        await fetch(`${CFG_GLPI.root_doc}/install/init_database`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded;',
                'X-Requested-With': 'XMLHttpRequest',
                'X-Glpi-Csrf-Token': getAjaxCsrfToken(),
            },
        });
    } catch {
        // DB installation is really long and can result in a `Proxy timeout` error.
        // It does not mean that the process is killed, it just mean that the proxy did not wait for the response
        // and send an error to the client.
        // Here we catch any error to make it silent, but we will handle it with the ProgressBar error_callback.
    }
}
