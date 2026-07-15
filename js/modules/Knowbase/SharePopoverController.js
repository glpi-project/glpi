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

/* global glpi_confirm_danger, bootstrap, glpi_toast_info */

import { get, post } from "/js/modules/Ajax.js";

export class GlpiKnowbaseSharePopoverController
{
    /** @type {HTMLElement} */
    #root;
    /** @type {HTMLElement} */
    #menu;
    /** @type {HTMLElement} */
    #button;
    /** @type {number} */
    #itemsId;
    /** @type {Promise<void>|null} */
    #initialLoad = null;
    /** @type {string} */
    #slugBeforeEdit = '';

    /**
     * @param {HTMLElement} root - the `.dropdown` wrapping the Share button + menu
     */
    constructor(root)
    {
        this.#root    = root;
        this.#menu    = root.querySelector('[data-glpi-share-menu]');
        this.#button  = root.querySelector('[data-glpi-share-button]');
        this.#itemsId = Number(this.#button.dataset.glpiItemsId);
        this.#initEventListeners();

        // Bootstrap toggles the dropdown via data-bs-toggle independently of
        // this controller, which is instantiated after async module imports.
        // On a fast open the show.bs.dropdown event can therefore fire before
        // the listener above is attached; if the dropdown is already open,
        // load the content now since we missed that event.
        if (this.#button.getAttribute('aria-expanded') === 'true') {
            this.#ensureContentLoaded();
        }
    }

    /**
     * Fetch the popover content once. De-duplicates concurrent/repeat calls and
     * allows a retry if the initial load failed.
     *
     * @returns {Promise<void>}
     */
    #ensureContentLoaded()
    {
        if (this.#initialLoad === null) {
            this.#initialLoad = this.#reload().catch((error) => {
                this.#initialLoad = null;
                throw error;
            });
        }

        return this.#initialLoad;
    }

    #initEventListeners()
    {
        // Load the popover content the first time it opens (no-op afterwards).
        this.#root.addEventListener('show.bs.dropdown', () => {
            this.#ensureContentLoaded();
        });

        // Toggle publish on/off.
        this.#menu.addEventListener('change', async (e) => {
            if (!e.target.matches('[data-glpi-share-toggle]')) {
                return;
            }
            const wants_published = e.target.checked;
            try {
                if (wants_published) {
                    await this.#publish();
                } else {
                    await this.#unpublish();
                }
                await this.#reload();
                this.#reflectState(wants_published);
            } catch (err) {
                e.target.checked = !wants_published;
                throw err;
            }

            // Publishing immediately copies the full link so it's ready to share.
            if (wants_published) {
                await this.#copyShareLink();
            }
        });

        // Regenerate link.
        this.#menu.addEventListener('click', async (e) => {
            if (!e.target.closest('[data-glpi-share-regenerate]')) {
                return;
            }
            const confirmed = await glpi_confirm_danger({
                title: __('Regenerate link'),
                message: __('The current link will stop working. Continue?'),
                confirm_label: __('Regenerate'),
            });
            if (!confirmed) {
                return;
            }
            const token_id = this.#currentTokenId();
            if (token_id === null) {
                return;
            }
            await post(`Share/Token/${token_id}/Regenerate`);
            await this.#reload();
            // Confirming the danger dialog is a click outside the dropdown,
            // which auto-closes it (data-bs-auto-close="outside"); reopen so the
            // freshly generated link stays visible for the user to copy.
            bootstrap.Dropdown.getOrCreateInstance(this.#button).show();
        });

        // Capture the slug value when editing begins (for Escape revert / no-op skip).
        this.#menu.addEventListener('focusin', (e) => {
            if (e.target.matches('[data-glpi-share-slug]')) {
                this.#slugBeforeEdit = e.target.value;
            }
        });

        // Live-validate the slug charset as the user types.
        this.#menu.addEventListener('input', (e) => {
            if (!e.target.matches('[data-glpi-share-slug]')) {
                return;
            }
            this.#validateSlug(e.target);
        });

        // Enter saves (via blur); Escape reverts.
        this.#menu.addEventListener('keydown', (e) => {
            if (!e.target.matches('[data-glpi-share-slug]')) {
                return;
            }
            if (e.key === 'Enter') {
                e.preventDefault();
                e.target.blur();
            } else if (e.key === 'Escape') {
                e.preventDefault();
                e.target.value = this.#slugBeforeEdit;
                this.#validateSlug(e.target);
                e.target.blur();
            }
        });

        // Persist on blur when the slug changed and is valid.
        this.#menu.addEventListener('focusout', async (e) => {
            if (e.target.matches('[data-glpi-share-slug]')) {
                await this.#saveSlug(e.target);
            }
        });
    }

    #currentTokenId()
    {
        const el = this.#menu.querySelector('[data-glpi-token-id]');
        return el ? Number(el.dataset.glpiTokenId) : null;
    }

    #disposeTooltips()
    {
        this.#menu.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
            bootstrap.Tooltip.getInstance(el)?.dispose();
        });
    }

    async #publish()
    {
        const token_id = this.#currentTokenId();
        if (token_id !== null) {
            // A token exists but is inactive → reactivate (reuse the same link).
            await post(`Share/Token/${token_id}/Toggle`);
            return;
        }
        const itemtype = this.#button.dataset.glpiItemtype;
        await post(`Share/Token/${itemtype}/${this.#itemsId}`, { name: null });
    }

    async #unpublish()
    {
        const token_id = this.#currentTokenId();
        if (token_id !== null) {
            await post(`Share/Token/${token_id}/Toggle`);
        }
    }

    async #reload()
    {
        const response = await get(`Knowbase/${this.#itemsId}/SidePanel/sharing`);
        this.#disposeTooltips();
        this.#menu.innerHTML = await response.text();
        window.initTooltips(this.#menu);
    }

    /** @param {boolean} is_published */
    #reflectState(is_published)
    {
        const icon = this.#button.querySelector('i');
        if (is_published) {
            this.#button.classList.add('active');
            icon?.classList.remove('ti-share');
            icon?.classList.add('ti-world');
        } else {
            this.#button.classList.remove('active');
            icon?.classList.remove('ti-world');
            icon?.classList.add('ti-share');
        }
    }

    /**
     * @param {HTMLInputElement} input
     * @returns {boolean} whether the current value is a valid slug
     */
    #validateSlug(input)
    {
        const valid = /^[a-z0-9-]*$/.test(input.value);
        const error = this.#menu.querySelector('[data-glpi-share-slug-error]');
        input.classList.toggle('is-invalid', !valid);
        error?.classList.toggle('d-none', valid);
        return valid;
    }

    /**
     * Persist the slug when it changed and is valid, then reload the popover so
     * the displayed prefix and the full copy URL reflect the new slug.
     *
     * @param {HTMLInputElement} input
     */
    async #saveSlug(input)
    {
        const slug = input.value.trim();
        // Canonicalize the field so display, validation and the payload agree.
        input.value = slug;
        if (slug === this.#slugBeforeEdit) {
            return;
        }
        if (!this.#validateSlug(input)) {
            return;
        }
        const token_id = this.#currentTokenId();
        if (token_id === null) {
            return;
        }
        await post(`Share/Token/${token_id}/Rename`, { slug });
        await this.#reload();
    }

    /**
     * Copy the full share URL (with secret) and toast. Silent no-op if the
     * clipboard API is unavailable (insecure context) or denied (lost
     * transient activation) — the link stays visible for manual copy.
     */
    async #copyShareLink()
    {
        const row = this.#menu.querySelector('[data-glpi-share-url]');
        const url = row?.dataset.glpiShareUrl;
        if (!url || !navigator.clipboard) {
            return;
        }
        try {
            await navigator.clipboard.writeText(url);
            glpi_toast_info(__('Public link copied to clipboard'));
        } catch {
            // Denied → do not claim success.
        }
    }
}
