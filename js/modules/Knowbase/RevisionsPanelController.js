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
const revision_selector = "[data-glpi-revision-id]";
const current_version_selector = "[data-glpi-current-version]";
const translation_revision_selector = "[data-glpi-translation-revision-id]";
const revert_translation_selector = "[data-glpi-revert-translation-revision]";
const current_translation_selector = "[data-glpi-current-translation-language]";

export class GlpiKnowbaseRevisionsPanelController
{
    /**
     * @type {HTMLElement}
     */
    #container;

    /**
     * The language currently being viewed (null = main article).
     * @type {string|null}
     */
    #viewedLanguage = null;

    /**
     * The revision ID being compared, if any.
     * @type {string|null}
     */
    #activeRevisionId = null;

    constructor(container)
    {
        this.#container = container;
        this.#initClickListeners();
        this.#initStateSync();
    }

    #initStateSync()
    {
        document.addEventListener('glpi:kb:exit-diff', () => {
            this.#activeRevisionId = null;
            this.#viewedLanguage = null;
            this.#clearHighlighting();
        });

        // Reset internal values on reload.
        this.#container.addEventListener('glpi:kb:panel-loaded', () => {
            this.#activeRevisionId = null;
            this.#viewedLanguage = null;
        });
    }

    #initClickListeners()
    {
        this.#container.addEventListener('click', (e) => {
            const revertButton = e.target.closest(revert_selector);
            if (revertButton) {
                e.preventDefault();
                e.stopPropagation();
                this.#handleRevert(revertButton);
                return;
            }

            const translationRevertButton = e.target.closest(revert_translation_selector);
            if (translationRevertButton) {
                e.preventDefault();
                e.stopPropagation();
                this.#handleTranslationRevert(translationRevertButton);
                return;
            }

            const revisionItem = e.target.closest(revision_selector);
            if (revisionItem) {
                e.preventDefault();
                this.#handleRevisionClick(revisionItem);
                return;
            }

            const translationRevisionItem = e.target.closest(translation_revision_selector);
            if (translationRevisionItem) {
                e.preventDefault();
                this.#handleTranslationRevisionClick(translationRevisionItem);
                return;
            }

            const currentTranslationItem = e.target.closest(current_translation_selector);
            if (currentTranslationItem) {
                e.preventDefault();
                this.#handleCurrentTranslationClick(currentTranslationItem);
                return;
            }

            const currentVersionItem = e.target.closest(current_version_selector);
            if (currentVersionItem) {
                e.preventDefault();
                this.#handleCurrentVersionClick();
            }
        });
    }

    #handleCurrentVersionClick()
    {
        if (this.#activeRevisionId !== null) {
            this.#stopComparison();
        }

        if (this.#viewedLanguage !== null) {
            const event = this.#dispatchBubbling('glpi:kb:exit-translation');
            if (event.defaultPrevented) {
                return;
            }
            this.#viewedLanguage = null;
        }

        this.#updateHighlighting();
    }

    /**
     * @param {HTMLElement} item
     */
    #handleCurrentTranslationClick(item)
    {
        const language = item.dataset.glpiCurrentTranslationLanguage;
        const was_comparing = this.#activeRevisionId !== null;

        if (!was_comparing && this.#viewedLanguage === language) {
            return;
        }

        if (was_comparing) {
            this.#stopComparison();
        }

        if (this.#viewedLanguage !== language) {
            const event = this.#dispatchBubbling('glpi:kb:show-translation', {language});
            if (event.defaultPrevented) {
                return;
            }
            this.#viewedLanguage = language;
        }

        this.#updateHighlighting();
    }

    /**
     * @param {HTMLElement} revisionItem
     */
    async #handleRevisionClick(revisionItem)
    {
        const revisionId = revisionItem.dataset.glpiRevisionId;
        const kbId = revisionItem.dataset.glpiKbId;

        if (this.#activeRevisionId === revisionId) {
            return;
        }

        await this.#startComparison(kbId, revisionId);
    }

    /**
     * @param {HTMLElement} item
     */
    async #handleTranslationRevisionClick(item)
    {
        const revisionId = item.dataset.glpiTranslationRevisionId;
        const kbId = item.dataset.glpiKbId;
        const language = item.dataset.glpiTranslationRevisionLanguage;

        if (this.#activeRevisionId === revisionId) {
            return;
        }

        await this.#startTranslationComparison(kbId, revisionId, language);
    }

    /**
     * @param {string} kbId
     * @param {string} revisionId
     */
    async #startComparison(kbId, revisionId)
    {
        if (this.#activeRevisionId !== null) {
            this.#stopComparison();
        }

        try {
            const base_url = CFG_GLPI.root_doc;
            const response = await fetch(
                `${base_url}/Knowbase/${kbId}/CompareRevision/${revisionId}`,
                {headers: {'X-Requested-With': 'XMLHttpRequest'}}
            );

            if (!response.ok) {
                throw new Error('Failed to load revision diff');
            }

            const {content_diff} = await response.json();

            const event = this.#dispatchBubbling('glpi:kb:compare', {revisionId, content_diff});
            if (event.defaultPrevented) {
                return;
            }

            this.#viewedLanguage = null;
            this.#activeRevisionId = revisionId;
            this.#updateHighlighting();
        } catch {
            glpi_toast_error(__("An unexpected error occurred."));
        }
    }

    /**
     * @param {string} kbId
     * @param {string} revisionId
     * @param {string} language
     */
    async #startTranslationComparison(kbId, revisionId, language)
    {
        if (this.#activeRevisionId !== null) {
            this.#stopComparison();
        }

        try {
            const base_url = CFG_GLPI.root_doc;
            const response = await fetch(
                `${base_url}/Knowbase/${kbId}/CompareTranslationRevision/${revisionId}`,
                {headers: {'X-Requested-With': 'XMLHttpRequest'}}
            );

            if (!response.ok) {
                throw new Error('Failed to load translation revision diff');
            }

            const {content_diff} = await response.json();

            const event = this.#dispatchBubbling('glpi:kb:compare-translation', {
                revisionId, content_diff, language,
            });
            if (event.defaultPrevented) {
                return;
            }

            this.#viewedLanguage = language;
            this.#activeRevisionId = revisionId;
            this.#updateHighlighting();
        } catch {
            glpi_toast_error(__("An unexpected error occurred."));
        }
    }

    #stopComparison()
    {
        this.#activeRevisionId = null;
        this.#dispatchBubbling('glpi:kb:compare-off');
    }

    #updateHighlighting()
    {
        const is_comparing = this.#activeRevisionId !== null;

        const current_version = this.#container.querySelector(current_version_selector);
        if (current_version) {
            current_version.classList.toggle(
                'kb-revision--selected',
                !is_comparing && this.#viewedLanguage === null
            );
        }

        const current_translations = this.#container.querySelectorAll(current_translation_selector);
        for (const item of current_translations) {
            item.classList.toggle(
                'kb-revision--selected',
                !is_comparing && item.dataset.glpiCurrentTranslationLanguage === this.#viewedLanguage
            );
        }

        const revisions = this.#container.querySelectorAll(revision_selector);
        for (const item of revisions) {
            item.classList.toggle(
                'kb-revision--selected',
                item.dataset.glpiRevisionId === this.#activeRevisionId
            );
        }

        const translation_revisions = this.#container.querySelectorAll(translation_revision_selector);
        for (const item of translation_revisions) {
            item.classList.toggle(
                'kb-revision--selected',
                item.dataset.glpiTranslationRevisionId === this.#activeRevisionId
            );
        }
    }

    #clearHighlighting()
    {
        const selected = document.querySelector('.kb-revision--selected');
        if (selected) {
            selected.classList.remove('kb-revision--selected');
        }
    }

    /**
     * @param {string} name
     * @param {object} detail
     * @returns {CustomEvent}
     */
    #dispatchBubbling(name, detail = {})
    {
        const event = new CustomEvent(name, {
            bubbles: true,
            cancelable: true,
            detail,
        });
        this.#container.dispatchEvent(event);
        return event;
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

    #handleTranslationRevert(button)
    {
        const revisionId = button.dataset.glpiRevertTranslationRevision;
        const kbId = button.dataset.glpiKbId;
        const language = button.dataset.glpiTranslationRevisionLanguage;

        glpi_html_dialog({
            title: __("Restore translation revision"),
            body: __("Are you sure you want to restore this translation version? The current translation content will be saved as a new revision."),
            buttons: [{
                label: __("Cancel"),
                class: 'btn-outline-secondary',
            }, {
                label: __("Confirm"),
                class: 'btn-primary',
                click: () => {
                    this.#performTranslationRevert(button, kbId, revisionId, language);
                },
            }],
        });
    }

    async #performTranslationRevert(button, kbId, revisionId, language)
    {
        button.classList.add('pointer-events-none');
        const icon = button.querySelector('i');
        const originalClass = icon.className;
        icon.className = 'spinner-border spinner-border-sm';

        const base_url = CFG_GLPI.root_doc;
        const url = `${base_url}/Knowbase/${kbId}/RevertTranslationRevision/${revisionId}`;

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
                button.classList.remove('pointer-events-none');
                icon.className = originalClass;
                return;
            }

            this.#container.dispatchEvent(new CustomEvent('glpi:kb:translation-reverted', {
                bubbles: true,
                detail: {language},
            }));

            glpi_toast_info(__("Translation revision restored successfully."));

            window.location.reload();

        } catch {
            glpi_toast_error(__("An unexpected error occurred."));
            button.classList.remove('pointer-events-none');
            icon.className = originalClass;
        }
    }
}
