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

    /** @type {HTMLElement|null} */
    #errorContainer;

    /** @type {HTMLButtonElement|null} */
    #submitBtn;

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

        this.#kb_id          = parseInt(this.#form.dataset.glpiKbId, 10);
        this.#errorContainer = this.#form.querySelector('[data-glpi-kb-link-item-error]');
        this.#submitBtn      = this.#form.querySelector('button[type="submit"]');

        // Ensure the error container has an id so aria-describedby can reference it
        if (this.#errorContainer && !this.#errorContainer.id) {
            this.#errorContainer.id = `kb-link-item-error-${this.#kb_id}`;
        }

        this.#bindEvents();
    }

    #bindEvents()
    {
        this.#form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.#submit();
        });

        // Clear the error as soon as the user picks something. Use a jQuery
        // listener because select2 dispatches the change via jQuery, which
        // does not fire native addEventListener handlers.
        $(this.#form).on('change', '[name="itemtype"], [name="items_id"]', () => this.#clearError());
    }

    async #submit()
    {
        const itemtype = this.#form.querySelector('[name="itemtype"]')?.value ?? '';
        const items_id = parseInt(
            this.#form.querySelector('[name="items_id"]')?.value ?? '',
            10,
        );

        if (!itemtype || itemtype === '0' || !items_id) {
            this.#showError(__('Please select an item to link.'));
            return;
        }

        this.#clearError();
        this.#setLoading(true);

        try {
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
        } finally {
            this.#setLoading(false);
        }
    }

    /**
     * Disable the submit button during the AJAX call to prevent
     * double-submission (double-click, repeated Enter).
     *
     * @param {boolean} loading
     */
    #setLoading(loading)
    {
        if (!this.#submitBtn) {
            return;
        }

        if (loading) {
            this.#submitBtn.disabled            = true;
            this.#submitBtn.dataset.originalHtml = this.#submitBtn.innerHTML;
            this.#submitBtn.innerHTML            = `<span class="spinner-border spinner-border-sm me-1"></span>${__('Linking...')}`;
        } else {
            this.#submitBtn.disabled = false;
            if (this.#submitBtn.dataset.originalHtml) {
                this.#submitBtn.innerHTML = this.#submitBtn.dataset.originalHtml;
            }
        }
    }

    /**
     * @param {string} message
     */
    #showError(message)
    {
        if (this.#errorContainer) {
            this.#errorContainer.textContent = message;
        }

        const selects = this.#form.querySelectorAll('[name="itemtype"], [name="items_id"]');
        selects.forEach((select) => {
            select.classList.add('is-invalid');
            select.setAttribute('aria-invalid', 'true');
            if (this.#errorContainer?.id) {
                select.setAttribute('aria-describedby', this.#errorContainer.id);
            }
        });

        // Fallback to keep keyboard focus on the first interactive element
        const select2Selection = this.#form.querySelector('.select2-selection');
        if (select2Selection instanceof HTMLElement) {
            select2Selection.focus();
        } else {
            const itemtypeSelect = this.#form.querySelector('[name="itemtype"]');
            if (itemtypeSelect instanceof HTMLElement) {
                itemtypeSelect.focus();
            }
        }
    }

    #clearError()
    {
        if (this.#errorContainer) {
            this.#errorContainer.textContent = '';
        }

        this.#form.querySelectorAll('[name="itemtype"], [name="items_id"]').forEach((select) => {
            select.classList.remove('is-invalid');
            select.removeAttribute('aria-invalid');
            select.removeAttribute('aria-describedby');
        });
    }
}
