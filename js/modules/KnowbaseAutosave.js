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

/**
 * KnowbaseAutosave
 *
 * Automatically saves KB article drafts to sessionStorage every 30 seconds
 * when changes are detected. Displays a warning banner and a last-saved
 * timestamp when a draft is available.
 *
 * Initialized via a hidden `#kb-autosave-config` element injected by the Twig template.
 *
 * @since 11.0.0
 */
class KnowbaseAutosave {

    /** @type {{ itemId: number, usersId: number, isNew: boolean }} */
    #config;

    /** @type {string} sessionStorage key for this article */
    #storageKey;

    /** @type {string} last saved Subject value (to detect changes) */
    #lastSavedName;

    /** @type {string} last saved Content value (to detect changes) */
    #lastSavedAnswer;

    /** @type {number} setInterval handle */
    #intervalId;

    /**
     * @param {{ itemId: number, usersId: number, isNew: boolean }} config
     */
    constructor(config) {
        this.#config = config;
        this.#storageKey = this.#buildStorageKey();
        this.#init();
    }

    // ─── sessionStorage Key ───────────────────────────────────────────────────

    #buildStorageKey() {
        if (this.#config.isNew) {
            return `glpi_kb_autosave_new_${this.#config.usersId}`;
        }
        return `glpi_kb_autosave_${this.#config.itemId}_${this.#config.usersId}`;
    }

    // ─── Initialization ─────────────────────────────────────────────────────

    #init() {
        // GLPI generates random TinyMCE editor IDs (e.g. 'answer_885423187').
        // activeEditor exists as an object before its iframe/body is ready,
        // so we must check editor.initialized and wait for the 'init' event
        // before calling getContent().
        if (window.tinyMCE) {
            if (tinyMCE.activeEditor?.initialized) {
                // Editor is fully ready — set up right away
                this.#setup();
            } else if (tinyMCE.activeEditor) {
                // Editor object exists but not fully initialized yet
                tinyMCE.activeEditor.on('init', () => this.#setup());
            } else {
                // Editor not created yet — wait for it
                tinyMCE.on('AddEditor', (e) => {
                    if (e.editor.id.startsWith('answer')) {
                        e.editor.on('init', () => this.#setup());
                    }
                });
            }
        } else {
            // TinyMCE not loaded at all — fall back to plain textarea
            document.addEventListener('DOMContentLoaded', () => this.#setup());
        }
    }

    #setup() {
        this.#checkExistingDraft();
        this.#lastSavedName   = this.#getNameValue();
        this.#lastSavedAnswer = this.#getAnswerValue();
        this.#intervalId = setInterval(() => this.#autoSave(), 30_000);
        this.#registerFormSubmitHandler();
    }

    // ─── Reading Form Fields ────────────────────────────────────────────────

    #getNameValue() {
        return document.querySelector('input[name="name"]')?.value ?? '';
    }

    #getAnswerValue() {
        if (window.tinyMCE && tinyMCE.activeEditor) {
            return tinyMCE.activeEditor.getContent();
        }
        return document.querySelector('textarea[name="answer"]')?.value ?? '';
    }

    #setAnswerValue(html) {
        if (window.tinyMCE && tinyMCE.activeEditor) {
            tinyMCE.activeEditor.setContent(html);
            return;
        }
        const textarea = document.querySelector('textarea[name="answer"]');
        if (textarea) {
            textarea.value = html;
        }
    }

    // ─── Change Detection ───────────────────────────────────────────────────

    #hasChanges() {
        return (
            this.#getNameValue()   !== this.#lastSavedName ||
            this.#getAnswerValue() !== this.#lastSavedAnswer
        );
    }

    // ─── Auto-save ──────────────────────────────────────────────────────────

    #autoSave() {
        if (!this.#hasChanges()) {
            return;
        }

        const draft = {
            name:    this.#getNameValue(),
            answer:  this.#getAnswerValue(),
            savedAt: new Date().toISOString(),
        };

        try {
            sessionStorage.setItem(this.#storageKey, JSON.stringify(draft));
        } catch (e) {
            // sessionStorage full or unavailable - fail silently (e.g., large base64 inline images before upload)
            console.warn('[KnowbaseAutosave] Could not save draft:', e);
            // If the draft has been saved before throwing this, the old version shouldn't be
            // kept in sessionStorage to avoid showing a stale draft banner on page reload.
            sessionStorage.removeItem(this.#storageKey);
            return;
        }

        this.#lastSavedName   = draft.name;
        this.#lastSavedAnswer = draft.answer;
    }

    // ─── Check for Existing Draft on Load ───────────────────────────────────

    #checkExistingDraft() {
        const raw = sessionStorage.getItem(this.#storageKey);
        if (!raw) {
            return;
        }

        let draft;
        try {
            draft = JSON.parse(raw);
        } catch {
            sessionStorage.removeItem(this.#storageKey);
            return;
        }

        if (!draft?.savedAt) {
            return;
        }

        this.#showBanner(draft);
    }

    // ─── Banner ─────────────────────────────────────────────────────────────

    /**
     * Reveals the autosave banner and wires up the Restore button.
     *
     * @param {{ name: string, answer: string, savedAt: string }} draft
     */
    #showBanner(draft) {
        const banner = document.getElementById('kb-autosave-banner');
        if (!banner) {
            return;
        }

        banner.classList.remove('d-none');

        // Dismiss button — removes draft from sessionStorage and hides the banner
        document.getElementById('kb-autosave-dismiss')
            ?.addEventListener('click', () => {
                sessionStorage.removeItem(this.#storageKey);
                banner.classList.add('d-none');
            });

        // Restore button — fills the form fields with the draft content
        document.getElementById('kb-autosave-restore')
            ?.addEventListener('click', () => {
                if (!window.confirm(__('Do you want to restore the auto-saved draft?'))) {
                    return;
                }

                const nameInput = document.querySelector('input[name="name"]');
                if (nameInput) {
                    nameInput.value = draft.name;
                }
                this.#setAnswerValue(draft.answer);

                // Update baseline so the timer doesn't immediately re-save
                this.#lastSavedName   = draft.name;
                this.#lastSavedAnswer = draft.answer;

                sessionStorage.removeItem(this.#storageKey);
                banner.classList.add('d-none');
            });
    }

    // ─── Clean Up on Form Submit ────────────────────────────────────────────

    #registerFormSubmitHandler() {
        const form = document.querySelector('form');
        if (!form) {
            return;
        }

        form.addEventListener('submit', () => {
            sessionStorage.removeItem(this.#storageKey);
            clearInterval(this.#intervalId);
        });
    }
}

// Bootstrap — initialize as soon as the config injected by Twig is available
const configEl = document.getElementById('kb-autosave-config');
if (configEl) {
    const config = {
        itemId: parseInt(configEl.dataset.glpiKbItemId, 10),
        usersId: parseInt(configEl.dataset.glpiKbUsersId, 10),
        isNew: configEl.dataset.glpiKbIsNew === 'true',
    };
    new KnowbaseAutosave(config);
}

export default KnowbaseAutosave;
