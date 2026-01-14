/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

/* global glpi_toast_error */

import { GlpiKnowbaseArticleSidePanelController } from "./ArticleSidePanelController.js";

export class GlpiKnowbaseArticleController
{
    /**
     * @type {HTMLElement}
     */
    #container;

    /**
     * @type {GlpiKnowbaseArticleSidePanelController}
     */
    #side_panel;

    constructor(container, side_panel_container)
    {
        this.#container = container;
        this.#side_panel = new GlpiKnowbaseArticleSidePanelController(
            side_panel_container,
        );
        this.#initEventListeners();
    }

    #initEventListeners()
    {
        const actions = this.#container.querySelectorAll("[data-glpi-kb-action]");
        for (const action of actions) {
            action.addEventListener("click", (e) => {
                try {
                    this.#executeAction(e.currentTarget);
                } catch (e) {
                    console.error(e);
                    glpi_toast_error(__("An unexpected error occurred."));
                }
            });
        }
    }

    /** @param {HTMLElement} element */
    #executeAction(element)
    {
        const type = element.dataset.glpiKbAction;
        const params = this.#extractParamsFromDataset(element.dataset);

        switch (type) {
            case 'LOAD_SIDE_PANEL':
                this.#side_panel.load(params.id, params.key);
                break;
        }
    }

    /** @param {DOMStringMap} dataset */
    #extractParamsFromDataset(dataset)
    {
        const params = {};
        const prefix = 'glpiKbActionParam';

        for (const [key, value] of Object.entries(dataset)) {
            if (key.startsWith(prefix)) {
                const paramName = key.slice(prefix.length).toLowerCase();
                params[paramName] = value;
            }
        }

        return params;
    }
}
