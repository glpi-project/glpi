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

/* global glpi_confirm_danger, bootstrap */

import { get, post } from "/js/modules/Ajax.js";

export class GlpiKnowbaseSharingPanelController
{
    /**
     * @type {HTMLElement}
     */
    #tabPane;

    /**
     * @param {HTMLElement} panel
     */
    constructor(panel)
    {
        this.#tabPane = panel.closest('.tab-pane');
        this.#initEventListeners();
    }

    #disposeTooltips()
    {
        this.#tabPane.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
            bootstrap.Tooltip.getInstance(el)?.dispose();
        });
    }

    async #reloadSharing()
    {
        const currentPanel = this.#tabPane.querySelector('[data-glpi-sharing-panel]');
        const items_id = currentPanel.dataset.glpiItemsId;
        const response = await get(`Knowbase/${items_id}/SidePanel/sharing`);
        this.#disposeTooltips();
        this.#tabPane.innerHTML = await response.text();
        window.initTooltips(this.#tabPane);
    }

    #initEventListeners()
    {
        // Show/hide create form
        this.#tabPane.addEventListener('click', (e) => {
            if (e.target.closest('[data-glpi-share-create-btn]')) {
                const section = this.#tabPane.querySelector('[data-glpi-share-create-section]');
                section.querySelector('[data-glpi-share-create-btn]').classList.add('d-none');
                section.querySelector('[data-glpi-share-create-form]').classList.remove('d-none');
                section.querySelector('[data-glpi-share-create-name]').focus();
            }
            if (e.target.closest('[data-glpi-share-create-cancel]')) {
                const section = this.#tabPane.querySelector('[data-glpi-share-create-section]');
                section.querySelector('[data-glpi-share-create-btn]').classList.remove('d-none');
                section.querySelector('[data-glpi-share-create-form]').classList.add('d-none');
                section.querySelector('[data-glpi-share-create-name]').value = '';
            }
        });

        // Create token
        this.#tabPane.addEventListener('click', async (e) => {
            if (!e.target.closest('[data-glpi-share-create-submit]')) {
                return;
            }

            const currentPanel = this.#tabPane.querySelector('[data-glpi-sharing-panel]');
            const itemtype = currentPanel.dataset.glpiItemtype;
            const items_id = currentPanel.dataset.glpiItemsId;
            const nameInput = this.#tabPane.querySelector('[data-glpi-share-create-name]');
            const name = nameInput.value.trim() || null;

            await post(
                `Share/Token/${itemtype}/${items_id}`,
                {
                    name: name,
                }
            );
            await this.#reloadSharing();
        });

        // Allow Enter key in the name input
        this.#tabPane.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && e.target.matches('[data-glpi-share-create-name]')) {
                e.preventDefault();
                this.#tabPane.querySelector('[data-glpi-share-create-submit]').click();
            }
        });

        // Toggle token
        this.#tabPane.addEventListener('change', async (e) => {
            if (!e.target.matches('[data-glpi-share-toggle]')) {
                return;
            }

            const row = e.target.closest('[data-glpi-token-id]');
            const tokenId = row.dataset.glpiTokenId;

            try {
                await post(`Share/Token/${tokenId}/Toggle`);
                await this.#reloadSharing();
            } catch (err) {
                e.target.checked = !e.target.checked;
                throw err;
            }
        });

        // Delete token
        this.#tabPane.addEventListener('click', async (e) => {
            const btn = e.target.closest('[data-glpi-share-delete]');
            if (!btn) {
                return;
            }

            const row = btn.closest('[data-glpi-token-id]');
            const tokenId = row.dataset.glpiTokenId;

            const confirmed = await glpi_confirm_danger({
                title: __('Delete sharing link'),
                message: __('Are you sure you want to delete this sharing link? Anyone using it will lose access.'),
                confirm_label: __('Delete'),
            });
            if (!confirmed) {
                return;
            }

            await post(`Share/Token/${tokenId}/Delete`);
            await this.#reloadSharing();
        });
    }
}
