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

/* global glpi_toast_error, glpi_confirm_danger, glpi_ajax_dialog */

import { post } from "/js/modules/Ajax.js";
import { GlpiKnowbaseArticleSidePanelController } from "/js/modules/Knowbase/ArticleSidePanelController.js";

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

    /**
     * @param {HTMLElement} container
     * @param {HTMLElement} side_panel_container
     */
    constructor(container, side_panel_container)
    {
        this.#container = container;
        this.#side_panel = new GlpiKnowbaseArticleSidePanelController(
            side_panel_container,
        );
        this.#initEventListeners();

        // Enable dots menu once listeners are ready
        const dots = this.#container.querySelector('[data-glpi-kb-dots]');
        if (dots) {
            dots.classList.remove('pointer-events-none');
        }
    }

    #initEventListeners()
    {
        const actions = this.#container.querySelectorAll("[data-glpi-kb-action]");
        for (const action of actions) {
            action.addEventListener("click", (e) => {
                try {
                    this.#executeAction(e);
                } catch (e) {
                    console.error(e);
                    glpi_toast_error(__("An unexpected error occurred."));
                }
            });
        }
    }

    /**
     * @param {Event} event
     */
    #executeAction(event)
    {
        const element = event.currentTarget;
        const target = event.target;

        const type = element.dataset.glpiKbAction;
        const params = this.#extractParamsFromDataset(element.dataset);

        switch (type) {
            case 'LOAD_SIDE_PANEL':
                this.#side_panel.load(params.id, params.key);
                break;
            case 'TOGGLE_VALUE': {
                event.stopPropagation();
                const toggle = element.querySelector('input[type="checkbox"]');
                if (toggle) {
                    const clicked_on_toggle = target === toggle;
                    if (!clicked_on_toggle) {
                        toggle.checked = !toggle.checked;
                    }
                    this.#toggleValue(params.id, params.field, toggle);
                }
                break;
            }
            case 'DELETE_ARTICLE':
                this.#deleteItem(params.id);
                break;
            case 'OPEN_MODAL':
                this.#openModal(params.id, params.key, params.title);
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

    /**
     * @param {number} id
     * @param {string} field
     * @param {HTMLInputElement} toggle
     */
    async #toggleValue(id, field, toggle)
    {
        const value = toggle.checked;
        try {
            await post(`Knowbase/${id}/ToggleField`, {
                field: field,
                value: value,
            });
        } catch (e) {
            toggle.checked = !value;
            throw e;
        }
    }

    /**
     * @param {number} id
     */
    async #deleteItem(id)
    {
        const confirmed = await glpi_confirm_danger({
            title: __('Delete article'),
            message: __('Are you sure you want to delete this article?'),
            confirm_label: __('Delete'),
        });
        if (!confirmed) {
            return;
        }

        const response = await post(`Knowbase/KnowbaseItem/${id}/Delete`, {});
        const body = await response.json();
        window.location.href = body.redirect;
    }

    /**
     * @param {number} id
     * @param {string} key
     * @param {string} title
     */
    #openModal(id, key, title)
    {
        const base_url = CFG_GLPI.root_doc;
        const url = `${base_url}/Knowbase/${id}/${key}`;

        glpi_ajax_dialog({
            url: url,
            title: title || '',
            dialogclass: 'modal-lg',
        });
    }
}
