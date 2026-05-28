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

/* global _, glpi_ajax_dialog, glpi_toast_info, bootstrap */

import { get } from "/js/modules/Ajax.js";

export class GlpiKnowbaseAsideController
{
    /**
     * @type {HTMLElement}
     */
    #aside;

    /**
     * Used to discard stale responses when multiple searches are in-flight.
     * @type {number}
     */
    #search_request_id = 0;

    /**
     * Whether the favorites section was hidden on initial server render.
     * Used to restore the correct state after clearing the search.
     * @type {boolean}
     */
    #favorites_originally_hidden = false;

    /**
     * @param {HTMLElement} aside
     */
    constructor(aside)
    {
        this.#aside = aside;
        this.#initCategoryToggle();
        this.#initSearch();
        this.#initCreateCategory();
    }

    #initCreateCategory()
    {
        this.#aside.addEventListener('click', (e) => {
            const trigger = e.target.closest('[data-glpi-kb-aside-category-create]');
            if (!trigger) {
                return;
            }
            e.preventDefault();
            this.#openCreateCategoryModal(trigger);
        });
    }

    /**
     * @param {HTMLElement} trigger
     */
    #openCreateCategoryModal(trigger)
    {
        const parent_id = trigger.dataset.glpiKbParentId ?? '0';
        glpi_ajax_dialog({
            url: `${CFG_GLPI.root_doc}/Knowbase/Aside/Category/CreateForm?parent=${encodeURIComponent(parent_id)}`,
            method: 'get',
            title: __('Create a category'),
            dialogclass: 'modal-md',
            show: (e) => {
                const modal = e.target.closest('.modal');
                if (modal) {
                    this.#bindCreateCategoryForm(modal);
                }
            },
        });
    }

    /**
     * @param {HTMLElement} modal
     */
    #bindCreateCategoryForm(modal)
    {
        const form = modal.querySelector('[data-glpi-kb-aside-category-form]');
        if (!form) {
            return;
        }

        const name_input    = form.querySelector('input[name="name"]');
        const name_error_el = form.querySelector('[data-glpi-kb-modal-name-error]');
        const global_error  = form.querySelector('[data-glpi-kb-modal-global-error]');
        const submit_btn    = form.querySelector('[data-glpi-kb-modal-submit]');

        modal.addEventListener('shown.bs.modal', () => name_input?.focus(), { once: true });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            this.#clearFormErrors(name_input, name_error_el, global_error);

            if (name_input.value.trim() === '') {
                this.#showFieldError(name_input, name_error_el, __('Title is mandatory'));
                return;
            }

            const original_label = submit_btn.innerHTML;
            submit_btn.disabled = true;
            submit_btn.innerHTML = `<i class="ti ti-loader me-1"></i>${__('Saving...')}`;

            try {
                const response = await fetch(`${CFG_GLPI.root_doc}/Knowbase/Aside/Category`, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: new FormData(form),
                });

                if (response.ok) {
                    bootstrap.Modal.getOrCreateInstance(modal).hide();
                    glpi_toast_info(__('Category created'));
                    return;
                }

                if (response.status === 422) {
                    const body = await response.json();
                    this.#applyServerErrors(body.errors ?? {}, name_input, name_error_el, global_error);
                    return;
                }

                this.#showGlobalError(global_error, __('An unexpected error occurred.'));
            } catch {
                this.#showGlobalError(global_error, __('An unexpected error occurred.'));
            } finally {
                submit_btn.disabled = false;
                submit_btn.innerHTML = original_label;
            }
        });
    }

    #clearFormErrors(name_input, name_error_el, global_error)
    {
        if (name_input) {
            name_input.classList.remove('is-invalid');
            name_input.removeAttribute('aria-invalid');
        }
        if (name_error_el) {
            name_error_el.textContent = '';
        }
        if (global_error) {
            global_error.classList.add('d-none');
            global_error.textContent = '';
        }
    }

    #showFieldError(input, error_el, message)
    {
        if (input) {
            input.classList.add('is-invalid');
            input.setAttribute('aria-invalid', 'true');
            input.focus();
        }
        if (error_el) {
            error_el.textContent = message;
        }
    }

    #showGlobalError(global_error, message)
    {
        if (!global_error) {
            return;
        }
        global_error.textContent = message;
        global_error.classList.remove('d-none');
    }

    #applyServerErrors(errors, name_input, name_error_el, global_error)
    {
        if (errors.name) {
            this.#showFieldError(name_input, name_error_el, errors.name);
        }
        if (errors._global) {
            this.#showGlobalError(global_error, errors._global);
        }
    }

    #initCategoryToggle()
    {
        this.#aside.addEventListener('click', (e) => {
            // Is the click on a toggle?
            const toggle = e.target.closest('[data-glpi-kb-aside-category-toggle]');
            if (!toggle) {
                return;
            }

            // Get closest tree node
            const node = toggle.closest('[data-glpi-kb-aside-category]');
            if (!node) {
                return;
            }

            // Toggle collasped state
            const is_collapsed = node.hasAttribute('data-glpi-kb-aside-category-collapsed');
            if (is_collapsed) {
                node.removeAttribute('data-glpi-kb-aside-category-collapsed');
                toggle.setAttribute('aria-expanded', 'true');
            } else {
                node.setAttribute('data-glpi-kb-aside-category-collapsed', '');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    #initSearch()
    {
        // Get target nodes from the DOM
        const search_input  = this.#aside.querySelector('[data-glpi-kb-aside-search-input]');
        const search_icon   = this.#aside.querySelector('[data-glpi-kb-aside-search-icon]');
        const clear_button  = this.#aside.querySelector('[data-glpi-kb-aside-search-clear]');
        const favorites     = this.#aside.querySelector('[data-glpi-kb-aside-favorites]');

        // Record the initial server-rendered state so we can restore it on clear.
        this.#favorites_originally_hidden = favorites.hasAttribute('data-glpi-kb-aside-favorites-hidden');

        // Debounce the search method to avoid hitting the server with too many
        // requests.
        const debouncedSearch = _.debounce(
            (value) => this.#performSearch(value),
            300,
        );

        // Signal that the controller is ready (used by e2e tests to wait before interacting)
        search_input.classList.remove('pe-none');

        // Run search on input
        search_input.addEventListener('input', () => {
            const value    = search_input.value;
            const has_text = value.trim() !== '';

            search_icon.classList.toggle('ti-search', !has_text);
            search_icon.classList.toggle('ti-x', has_text);

            if (has_text) {
                clear_button.removeAttribute('disabled');
            } else {
                clear_button.setAttribute('disabled', '');
            }

            debouncedSearch(value);
        });

        // Clear the search when clicking the X icon
        clear_button.addEventListener('click', () => {
            if (search_input.value.trim() === '') {
                return;
            }
            search_input.value = '';
            search_input.dispatchEvent(new Event('input'));
        });
    }

    async #performSearch(value)
    {
        const tree      = this.#aside.querySelector('[data-glpi-kb-aside-tree]');
        const favorites = this.#aside.querySelector('[data-glpi-kb-aside-favorites]');

        // Search criteria was removed, show all items again
        if (value.trim() === '') {
            this.#showAllTreeItems(tree);
            this.#restoreFavorites(favorites);
            return;
        }

        // Send request to backend
        const request_id = ++this.#search_request_id;
        const response = await get(
            `Knowbase/Aside/Search?contains=${encodeURIComponent(value)}`,
        );
        const matching_ids = new Set(await response.json());
        if (request_id !== this.#search_request_id) {
            return;
        }

        // Apply results
        this.#filterTree(tree, matching_ids);
        this.#filterFavorites(favorites, matching_ids);
    }

    /**
     * Show all articles and categories in the tree (restores state after filtering).
     *
     * @param {HTMLElement} tree
     */
    #showAllTreeItems(tree)
    {
        for (const el of tree.querySelectorAll('[data-glpi-kb-search-hidden]')) {
            el.removeAttribute('data-glpi-kb-search-hidden');
        }

        const no_results = tree.querySelector('[data-glpi-kb-aside-no-results]');
        no_results.hidden = true;
    }

    /**
     * Restore the favorites section to its original server-rendered state.
     *
     * @param {HTMLElement} favorites_el
     */
    #restoreFavorites(favorites_el)
    {
        for (const el of favorites_el.querySelectorAll('[data-glpi-kb-search-hidden]')) {
            el.removeAttribute('data-glpi-kb-search-hidden');
        }

        this.#setFavoritesVisible(favorites_el, !this.#favorites_originally_hidden);
    }

    /**
     * Filter the favorites section to only show articles whose IDs are in matching_ids.
     * Hides the entire section (and the header border) when nothing matches.
     *
     * @param {HTMLElement} favorites_el
     * @param {Set<number>} matching_ids
     */
    #filterFavorites(favorites_el, matching_ids)
    {
        if (this.#favorites_originally_hidden) {
            return;
        }

        let any_visible = false;

        for (const article of favorites_el.querySelectorAll('[data-glpi-kb-article-id]')) {
            // Skip pending entries — they are already hidden by CSS and should not
            // count as visible regardless of whether they match the search.
            if (article.dataset.glpiKbFavoriteCurrent === 'pending') {
                continue;
            }

            const id = parseInt(article.dataset.glpiKbArticleId);
            if (matching_ids.has(id)) {
                article.removeAttribute('data-glpi-kb-search-hidden');
                any_visible = true;
            } else {
                article.setAttribute('data-glpi-kb-search-hidden', '');
            }
        }

        this.#setFavoritesVisible(favorites_el, any_visible);
    }

    /**
     * Toggle the favorites section visibility and the matching header border.
     *
     * @param {HTMLElement} favorites_el
     * @param {boolean}     visible
     */
    #setFavoritesVisible(favorites_el, visible)
    {
        const header = this.#aside.querySelector('[data-glpi-kb-aside-header]');

        if (visible) {
            favorites_el.removeAttribute('data-glpi-kb-aside-favorites-hidden');
            header.removeAttribute('data-glpi-kb-aside-header-no-border');
        } else {
            favorites_el.setAttribute('data-glpi-kb-aside-favorites-hidden', '');
            header.setAttribute('data-glpi-kb-aside-header-no-border', '');
        }
    }

    /**
     * Filter the tree to only show articles whose IDs are in matching_ids.
     * Categories with no visible children are hidden recursively.
     *
     * @param {HTMLElement} tree
     * @param {Set<number>} matching_ids
     */
    #filterTree(tree, matching_ids)
    {
        let any_visible = false;

        for (const category of tree.querySelectorAll(':scope > ul > [data-glpi-kb-aside-category]')) {
            const visible = this.#filterCategory(category, matching_ids);
            if (visible) {
                category.removeAttribute('data-glpi-kb-search-hidden');
                any_visible = true;
            } else {
                category.setAttribute('data-glpi-kb-search-hidden', '');
            }
        }

        // Show information message if no results are found
        const no_results = tree.querySelector('[data-glpi-kb-aside-no-results]');
        no_results.hidden = any_visible;
    }

    /**
     * @param {HTMLElement} category_el
     * @param {Set<number>} matching_ids
     * @returns {boolean} Whether the category has any visible children.
     */
    #filterCategory(category_el, matching_ids)
    {
        const ul = category_el.querySelector(':scope > ul');
        if (!ul) {
            return false;
        }

        let has_visible = false;

        for (const article of ul.querySelectorAll(':scope > [data-glpi-kb-article-id]')) {
            const id = parseInt(article.dataset.glpiKbArticleId);
            if (matching_ids.has(id)) {
                article.removeAttribute('data-glpi-kb-search-hidden');
                has_visible = true;
            } else {
                article.setAttribute('data-glpi-kb-search-hidden', '');
            }
        }

        for (const subcategory of ul.querySelectorAll(':scope > [data-glpi-kb-aside-category]')) {
            const visible = this.#filterCategory(subcategory, matching_ids);
            if (visible) {
                subcategory.removeAttribute('data-glpi-kb-search-hidden');
                has_visible = true;
            } else {
                subcategory.setAttribute('data-glpi-kb-search-hidden', '');
            }
        }

        return has_visible;
    }
}
