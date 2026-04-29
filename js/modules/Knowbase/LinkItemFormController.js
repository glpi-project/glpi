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

/* global bootstrap, glpi_toast_info */

import { post } from '/js/modules/Ajax.js';

/**
 * Controller for the "Link to another item" form inside a glpi_ajax_dialog.
 *
 * Submits the form via JS to the LinkItem endpoint without a page reload,
 * dispatches an `item:linked` custom event so the article controller can
 * insert the new badge, closes the modal, and shows a success toast.
 */
export class LinkItemFormController
{
    /** @type {HTMLFormElement} */
    #form;

    /** @type {HTMLElement} */
    #modal;

    /** @type {number} */
    #kb_id;

    /**
     * @param {HTMLElement} modal - The Bootstrap modal element
     */
    constructor(modal)
    {
        this.#modal = modal;
        this.#form  = modal.querySelector('[data-glpi-kb-link-item-form]');

        if (!this.#form) {
            return;
        }

        this.#kb_id = parseInt(this.#form.dataset.glpiKbId, 10);
        this.#bindEvents();
    }

    #bindEvents()
    {
        this.#form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.#submit();
        });
    }

    async #submit()
    {
        const itemtype = this.#form.querySelector('[name="itemtype"]').value;
        const items_id = parseInt(
            this.#form.querySelector('[name="items_id"]').value,
        );

        if (!itemtype || !items_id) {
            return;
        }

        const response = await post(`Knowbase/${this.#kb_id}/LinkItem`, { itemtype, items_id });
        const body     = await response.json();

        // Notify the article controller
        const article = document.querySelector('[data-glpi-knowbase-article]');
        if (article) {
            article.dispatchEvent(new CustomEvent('item:linked', {
                bubbles: true,
                detail:  { item: body.item ?? null },
            }));
        }

        // Close the dialog
        bootstrap.Modal.getInstance(this.#modal)?.hide();

        glpi_toast_info(__('Item linked successfully'));
    }
}
