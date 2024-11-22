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
    function message(message_list_element, text) {
        const alert = document.createElement('p');
        alert.innerHTML = text;
        message_list_element.appendChild(alert);
    }

    async function start_database_install(dom_element, progress_key)
    {
        if (!dom_element) {
            throw new Error('No DOM element provided to start database install.');
        }
        const messages_container = document.getElementById('glpi_install_messages_container');
        const success_element = document.getElementById('glpi_install_success');

        const message_list_element = document.createElement('div');

        success_element.querySelector('button').setAttribute('disabled', 'disabled');

        const progress = create_progress_bar({
            key: progress_key,
            container: messages_container,
            error_callback: (error) => message(message_list_element, `Progress error:\n${error}`),
        });

        messages_container.appendChild(message_list_element);

        setTimeout(() => {
            progress.start();
        }, 1500);

        try {
            const res = await fetch("/install/database_setup/start_db_inserts", {method: 'POST'});
            const text = await res.text();
            if (text && text.trim().length) {
                message(message_list_element, `Error:\n${text}`);
                progress.stop(false);
            } else {
                success_element.querySelector('button').removeAttribute('disabled');
            }
        } catch (err) {
            message(message_list_element, `Database install error:\n${err.message||err.toString()}`);
            progress.stop(false);
        }
    }

    window.start_database_install = start_database_install;
})();
