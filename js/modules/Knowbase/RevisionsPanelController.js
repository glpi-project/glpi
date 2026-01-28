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

/* global glpi_toast_error, glpi_toast_info, glpi_html_dialog, getAjaxCsrfToken */

const revert_selector = "[data-glpi-revert-revision]";

export class GlpiKnowbaseRevisionsPanelController
{
    /**
     * @type {HTMLElement}
     */
    #container;

    constructor(container)
    {
        this.#container = container;
        this.#initEventListeners();
    }

    #initEventListeners()
    {
        this.#container.addEventListener('click', (e) => {
            const revertButton = e.target.closest(revert_selector);
            if (revertButton) {
                e.preventDefault();
                this.#handleRevert(revertButton);
            }
        });
    }

    #handleRevert(button)
    {
        const revisionId = button.dataset.glpiRevertRevision;
        const kbId = button.dataset.glpiKbId;

        // Confirmation dialog
        glpi_html_dialog({
            title: __("Restore revision"),
            body: __("Are you sure you want to restore this version? The current content will be saved as a new revision."),
            buttons: [{
                label: __("Cancel"),
                class: 'btn-outline-secondary',
            }, {
                label: __("Confirm"),
                class: 'btn-primary',
                click: () => {
                    this.#performRevert(button, kbId, revisionId);
                },
            }],
        });
    }

    async #performRevert(button, kbId, revisionId)
    {
        // Show loading state
        button.classList.add('pointer-events-none');
        const icon = button.querySelector('i');
        const originalClass = icon.className;
        icon.className = 'spinner-border spinner-border-sm';

        const base_url = CFG_GLPI.root_doc;
        const url = `${base_url}/Knowbase/${kbId}/RevertTo/${revisionId}`;

        try {
            const response = await fetch(url, {
                method: "POST",
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Glpi-Csrf-Token': getAjaxCsrfToken(),
                }
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                glpi_toast_error(data.message || __("An unexpected error occurred."));
                // Restore button state
                button.classList.remove('pointer-events-none');
                icon.className = originalClass;
                return;
            }

            glpi_toast_info(data.message);
            window.location.reload();
        } catch {
            glpi_toast_error(__("An unexpected error occurred."));
            // Restore button state
            button.classList.remove('pointer-events-none');
            icon.className = originalClass;
        }
    }
}
