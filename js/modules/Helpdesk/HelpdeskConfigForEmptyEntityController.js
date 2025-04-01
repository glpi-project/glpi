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

export class GlpiHelpdeskConfigForEmptyEntityController
{
    /** @type {HTMLElement} */
    #container;

    constructor(container)
    {
        this.#container = container;
        this.#initEventsHandlers();
        this.#enableActions();
    }

    #initEventsHandlers()
    {
        // Watch for click on the "define tiles" button.
        this.#getDefineTilesButton().addEventListener('click', () => {
            this.#getSpecificConfigDiv().classList.add('d-none');
            this.#getOriginalHelpdeskConfigDiv()
                .classList
                .remove('helpdesk-home-config-for-empty-entity-wrapper')
            ;
        });
    }

    #enableActions()
    {
        this.#getDefineTilesButton().classList.remove('pointer-events-none');
        this.#getCopyTilesButton().classList.remove('pointer-events-none');
    }

    /** @return {HTMLElement} */
    #getDefineTilesButton()
    {
        return this.#container.querySelector(
            '[data-glpi-helpdesk-config-tiles-empty-entity-define-tiles]'
        );
    }

    /** @return {HTMLElement} */
    #getCopyTilesButton()
    {
        return this.#container.querySelector(
            '[data-glpi-helpdesk-config-tiles-empty-entity-copy-tiles]'
        );
    }

    /** @return {HTMLElement} */
    #getOriginalHelpdeskConfigDiv()
    {
        return this.#container.querySelector(
            '[data-glpi-helpdesk-config-tiles-empty-entity-original-content]'
        );
    }

    /** @return {HTMLElement} */
    #getSpecificConfigDiv()
    {
        return this.#container.querySelector(
            '[data-glpi-helpdesk-config-tiles-empty-entity-specific]'
        );
    }
}
