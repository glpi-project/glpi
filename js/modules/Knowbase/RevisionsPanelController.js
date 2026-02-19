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

import { computeHtmlDiff } from "/js/modules/Knowbase/RevisionDiffRenderer.js";

const revert_selector = "[data-glpi-revert-revision]";
const revision_selector = "[data-glpi-revision-id]";
const current_version_selector = "[data-glpi-current-version]";

export class GlpiKnowbaseRevisionsPanelController
{
    /**
     * @type {HTMLElement}
     */
    #container;

    /**
     * @type {string|null}
     */
    #activeRevisionId = null;

    constructor(container)
    {
        this.#container = container;
        this.#initEventListeners();
    }

    #initEventListeners()
    {
        this.#container.addEventListener('click', (e) => {
            // Revert button takes priority — stop propagation to avoid triggering compare
            const revertButton = e.target.closest(revert_selector);
            if (revertButton) {
                e.preventDefault();
                e.stopPropagation();
                this.#handleRevert(revertButton);
                return;
            }

            // Click on a revision item → toggle comparison
            const revisionItem = e.target.closest(revision_selector);
            if (revisionItem) {
                e.preventDefault();
                this.#handleCompareToggle(revisionItem);
                return;
            }

            // Click on current version item → deactivate comparison
            const currentVersionItem = e.target.closest(current_version_selector);
            if (currentVersionItem && this.#activeRevisionId !== null) {
                e.preventDefault();
                this.#deactivateComparison();
            }
        });
    }

    /**
     * @param {HTMLElement} revisionItem
     */
    async #handleCompareToggle(revisionItem)
    {
        const revisionId = revisionItem.dataset.glpiRevisionId;

        // Toggle off if clicking the already-active revision
        if (this.#activeRevisionId === revisionId) {
            this.#deactivateComparison();
            return;
        }

        // Activate comparison
        await this.#activateComparison(revisionId);
    }

    /**
     * @param {string} revisionId
     */
    async #activateComparison(revisionId)
    {
        // Restore original DOM before computing new diff
        if (this.#activeRevisionId !== null) {
            this.#deactivateComparison();
        }

        try {
            // Fetch revision content
            const response = await fetch(
                `${CFG_GLPI.root_doc}/ajax/getKbRevision.php?revid=${revisionId}`,
                {headers: {'X-Requested-With': 'XMLHttpRequest'}}
            );

            if (!response.ok) {
                throw new Error('Failed to load revision');
            }

            const revisionData = await response.json();

            // Get current article content from the DOM
            const subjectEl = document.querySelector('[data-glpi-kb-subject]');
            const contentEl = document.querySelector('[data-glpi-kb-content]');

            if (!subjectEl || !contentEl) {
                return;
            }

            // Compute diffs
            const titleDiff = await computeHtmlDiff(
                revisionData.name,
                subjectEl.textContent
            );
            const contentDiff = await computeHtmlDiff(
                revisionData.answer,
                contentEl.innerHTML
            );

            // Dispatch event to ArticleController
            this.#container.dispatchEvent(new CustomEvent('glpi:kb:compare', {
                bubbles: true,
                detail: {revisionId, titleDiff, contentDiff},
            }));

            // Update state
            this.#activeRevisionId = revisionId;
            this.#updateRevisionItemsState();

        } catch {
            glpi_toast_error(__("An unexpected error occurred."));
        }
    }

    #deactivateComparison()
    {
        this.#container.dispatchEvent(new CustomEvent('glpi:kb:compare-off', {
            bubbles: true,
        }));

        this.#activeRevisionId = null;
        this.#updateRevisionItemsState();
    }

    #updateRevisionItemsState()
    {
        const items = this.#container.querySelectorAll(revision_selector);
        for (const item of items) {
            item.classList.toggle(
                'kb-revision--comparing',
                item.dataset.glpiRevisionId === this.#activeRevisionId
            );
        }
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

            window.location.reload();
        } catch {
            glpi_toast_error(__("An unexpected error occurred."));
            // Restore button state
            button.classList.remove('pointer-events-none');
            icon.className = originalClass;
        }
    }
}
