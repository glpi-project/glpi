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

/* global _, glpi_confirm_danger, glpi_toast_error */

import { get } from "/js/modules/Ajax.js";
import {
    EditorActionType,
    extractParamsFromDataset,
    syncToggleCheckboxes,
    toggleFavorite,
    toggleField,
    deleteArticle,
} from "/js/modules/Knowbase/EditorActions.js";

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
     * In-flight/resolved dots menu content, keyed by article id. The tree
     * renders only the dots trigger; the menu items are fetched on demand
     * (prefetched on hover) so we never build every article's actions up-front.
     * @type {Map<number, Promise<string>>}
     */
    #actions_cache = new Map();

    /**
     * @param {HTMLElement} aside
     */
    constructor(aside)
    {
        this.#aside = aside;
        this.#initCategoryToggle();
        this.#initSearch();
        this.#initActions();
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

    /**
     * Wire up the per-article kebab menu actions (add to favorites, add to FAQ,
     * delete). Clicks are delegated so entries added later (e.g. a cloned
     * favorite) work without re-binding.
     */
    #initActions()
    {
        this.#aside.addEventListener('click', (e) => {
            const button = e.target.closest('[data-glpi-kb-action]');
            if (!button || !this.#aside.contains(button)) {
                return;
            }

            e.preventDefault();
            try {
                this.#executeAction(e, button);
            } catch (error) {
                glpi_toast_error(__("An unexpected error occurred."));
                throw error;
            }
        });

        // Prefetch the menu content as soon as the row is hovered or focused, so
        // it is ready by the time the user opens the kebab (no visible latency).
        const prefetch = (e) => {
            const line = e.target.closest('.article[data-glpi-kb-article-id]');
            if (line && this.#aside.contains(line)) {
                this.#populateMenus(parseInt(line.dataset.glpiKbArticleId));
            }
        };
        this.#aside.addEventListener('mouseover', prefetch);
        this.#aside.addEventListener('focusin', prefetch);

        // Fallback for opens that outran the prefetch (touch, instant clicks,
        // keyboard): make sure the content is loaded when the menu opens.
        this.#aside.addEventListener('show.bs.dropdown', (e) => {
            const line = e.target.closest('.article[data-glpi-kb-article-id]');
            if (line) {
                this.#populateMenus(parseInt(line.dataset.glpiKbArticleId));
            }
        });
    }

    /**
     * Fetch (once) and inject the kebab menu items for an article into every
     * not-yet-populated menu bearing that id (tree + favorites).
     *
     * @param {number} id
     */
    async #populateMenus(id)
    {
        if (!Number.isInteger(id)) {
            return;
        }

        const selector = `[data-glpi-kb-article-id="${CSS.escape(id)}"] `
            + `[data-glpi-kb-actions-menu]:not([data-glpi-kb-actions-loaded])`;
        if (this.#aside.querySelector(selector) === null) {
            return; // Nothing left to populate for this id.
        }

        let html;
        try {
            html = await this.#loadActions(id);
        } catch {
            // Drop the cached rejection so a later hover/open can retry.
            this.#actions_cache.delete(id);
            return;
        }

        for (const menu of this.#aside.querySelectorAll(selector)) {
            menu.innerHTML = html;
            menu.setAttribute('data-glpi-kb-actions-loaded', '');
        }
    }

    /**
     * @param {number} id
     * @returns {Promise<string>} rendered menu items HTML
     */
    #loadActions(id)
    {
        if (!this.#actions_cache.has(id)) {
            this.#actions_cache.set(
                id,
                get(`Knowbase/${id}/AsideActions`).then((response) => response.text()),
            );
        }
        return this.#actions_cache.get(id);
    }

    /**
     * @param {Event} e
     * @param {HTMLElement} button
     */
    #executeAction(e, button)
    {
        const type = button.dataset.glpiKbAction;
        const params = extractParamsFromDataset(button.dataset);
        const id = parseInt(params.id);

        switch (type) {
            case EditorActionType.TOGGLE_FAVORITE: {
                // Keep the dropdown open when toggling.
                e.stopPropagation();
                const toggle = button.querySelector('input[type="checkbox"]');
                if (!toggle) {
                    break;
                }
                if (e.target !== toggle) {
                    toggle.checked = !toggle.checked;
                }
                this.#onToggleFavorite(id, toggle.checked);
                break;
            }
            case EditorActionType.TOGGLE_VALUE: {
                // Keep the dropdown open when toggling.
                e.stopPropagation();
                const toggle = button.querySelector('input[type="checkbox"]');
                if (!toggle) {
                    break;
                }
                if (e.target !== toggle) {
                    toggle.checked = !toggle.checked;
                }
                this.#onToggleField(id, params.field, toggle.checked);
                break;
            }
            case EditorActionType.DELETE_ARTICLE:
                this.#onDelete(id);
                break;
        }
    }

    /**
     * @param {number} id
     * @param {boolean} value
     */
    async #onToggleFavorite(id, value)
    {
        this.#updateFavoritesSection(id, value);
        // Sync every menu for this article, page-wide (aside + article header).
        syncToggleCheckboxes(id, EditorActionType.TOGGLE_FAVORITE, value);
        try {
            await toggleFavorite(id, value);
        } catch (error) {
            // Revert the optimistic UI changes.
            this.#updateFavoritesSection(id, !value);
            syncToggleCheckboxes(id, EditorActionType.TOGGLE_FAVORITE, !value);
            throw error;
        }
    }

    /**
     * @param {number} id
     * @param {string} field
     * @param {boolean} value
     */
    async #onToggleField(id, field, value)
    {
        // Sync every menu for this article, page-wide (aside + article header).
        syncToggleCheckboxes(id, EditorActionType.TOGGLE_VALUE, value, field);
        try {
            await toggleField(id, field, value);
        } catch (error) {
            syncToggleCheckboxes(id, EditorActionType.TOGGLE_VALUE, !value, field);
            throw error;
        }
    }

    /**
     * @param {number} id
     */
    async #onDelete(id)
    {
        const confirmed = await glpi_confirm_danger({
            title: __('Delete article'),
            message: __('Are you sure you want to delete this article?'),
            confirm_label: __('Delete'),
        });
        if (!confirmed) {
            return;
        }

        const response = await deleteArticle(id);
        const body = await response.json();

        // Deleting the article currently being viewed: leave the page.
        const current = this.#aside.querySelector('[data-glpi-kb-article-current]');
        if (current && parseInt(current.dataset.glpiKbArticleId) === id) {
            window.location.href = body.redirect;
            return;
        }

        // Otherwise remove every entry for this article (tree + favorites) in
        // place. Categories are left as-is: the server renders empty categories
        // too, so a now-empty category should stay visible (as it would on reload).
        for (const entry of this.#aside.querySelectorAll(`[data-glpi-kb-article-id="${CSS.escape(id)}"]`)) {
            entry.remove();
        }

        const favorites = this.#aside.querySelector('[data-glpi-kb-aside-favorites]');
        if (favorites) {
            this.#refreshFavoritesVisibility(favorites);
        }
    }

    /**
     * Add or remove the article from the favorites section to mirror its new
     * favorite state, then refresh the section visibility.
     *
     * @param {number} id
     * @param {boolean} is_favorited
     */
    #updateFavoritesSection(id, is_favorited)
    {
        const favorites = this.#aside.querySelector('[data-glpi-kb-aside-favorites]');
        if (!favorites) {
            return;
        }
        const list = favorites.querySelector('ul');
        if (!list) {
            return;
        }

        // The current article has a dedicated entry that is only toggled between
        // "pending" (hidden) and "active" (shown) states, never added/removed.
        const current = favorites.querySelector('[data-glpi-kb-favorite-current]');
        if (current && parseInt(current.dataset.glpiKbArticleId) === id) {
            current.setAttribute('data-glpi-kb-favorite-current', is_favorited ? 'active' : 'pending');
            this.#refreshFavoritesVisibility(favorites);
            return;
        }

        if (is_favorited) {
            const already_listed = list.querySelector(`:scope > [data-glpi-kb-article-id="${CSS.escape(id)}"]`);
            if (!already_listed) {
                const source = this.#aside.querySelector(
                    `[data-glpi-kb-aside-tree] [data-glpi-kb-article-id="${CSS.escape(id)}"]`
                );
                if (source) {
                    const clone = source.cloneNode(true);
                    clone.classList.add('mb-2');
                    clone.removeAttribute('data-glpi-kb-search-hidden');
                    // The source row's dots menu is still open (the user just
                    // clicked a toggle inside it); close it in the clone.
                    this.#resetClonedDropdown(clone);
                    list.appendChild(clone);
                }
            }
        } else {
            for (const entry of list.querySelectorAll(`:scope > [data-glpi-kb-article-id="${CSS.escape(id)}"]`)) {
                if (!entry.hasAttribute('data-glpi-kb-favorite-current')) {
                    entry.remove();
                }
            }
        }

        this.#refreshFavoritesVisibility(favorites);
    }

    /**
     * Reset a cloned article row's dots menu to a closed state. The source row
     * is cloned while its dropdown is still open (the user just clicked a toggle
     * inside it), and the clone is not a Bootstrap-managed instance — so without
     * this it would render a second, orphaned open menu that never closes.
     *
     * @param {HTMLElement} clone
     */
    #resetClonedDropdown(clone)
    {
        for (const shown of clone.querySelectorAll('.show')) {
            shown.classList.remove('show');
        }
        const trigger = clone.querySelector('[data-bs-toggle="dropdown"]');
        if (trigger) {
            trigger.setAttribute('aria-expanded', 'false');
        }
        const menu = clone.querySelector('.dropdown-menu');
        if (menu) {
            // Drop any inline positioning Popper may have applied while open.
            menu.removeAttribute('style');
        }
    }

    /**
     * Show or hide the favorites section (and matching header border) depending
     * on whether it still holds any visible entry.
     *
     * @param {HTMLElement} favorites_el
     */
    #refreshFavoritesVisibility(favorites_el)
    {
        const header = this.#aside.querySelector('[data-glpi-kb-aside-header]');
        const has_visible = favorites_el.querySelector(
            '[data-glpi-kb-article-id]:not([data-glpi-kb-favorite-current="pending"])'
        ) !== null;

        if (has_visible) {
            favorites_el.removeAttribute('data-glpi-kb-aside-favorites-hidden');
            header?.removeAttribute('data-glpi-kb-aside-header-no-border');
        } else {
            favorites_el.setAttribute('data-glpi-kb-aside-favorites-hidden', '');
            header?.setAttribute('data-glpi-kb-aside-header-no-border', '');
        }

        // Keep the "restore after search" baseline in sync with the live state.
        this.#favorites_originally_hidden = !has_visible;
    }
}
