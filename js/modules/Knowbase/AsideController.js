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

/* global _ */

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
        this.#initMultiCategoryHover();
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

    #initMultiCategoryHover()
    {
        const tree = this.#aside.querySelector('[data-glpi-kb-aside-tree]');
        if (!tree) {
            return;
        }

        const label = tree.getAttribute('data-glpi-kb-also-in-label') ?? 'Also in';
        let active_li = null;
        let tooltip_el = null;
        let tooltip_seq = 0;

        const activate = (article_li) => {
            const article_id = article_li.getAttribute('data-glpi-kb-article-id');
            const other_cats = article_li.getAttribute('data-glpi-kb-article-other-categories');

            tree.querySelectorAll(`[data-glpi-kb-article-id="${CSS.escape(article_id)}"]`)
                .forEach(el => {
                    if (el !== article_li) {
                        el.classList.add('kb-article--sibling');
                    }
                });

            if (!other_cats) {
                return;
            }

            tooltip_el = document.createElement('div');
            tooltip_el.id = `kb-aside-multi-tooltip-${++tooltip_seq}`;
            tooltip_el.setAttribute('role', 'tooltip');
            tooltip_el.className = 'kb-aside-multi-tooltip';
            tooltip_el.textContent = `${label}: ${other_cats}`;
            document.body.appendChild(tooltip_el);

            // Link the tooltip to the article's anchor for screen reader announcement.
            article_li.querySelector('a')?.setAttribute('aria-describedby', tooltip_el.id);

            const rect     = article_li.getBoundingClientRect();
            const tip_rect = tooltip_el.getBoundingClientRect();
            tooltip_el.style.left = `${rect.left + rect.width / 2 - tip_rect.width / 2}px`;
            tooltip_el.style.top  = `${rect.top - tip_rect.height - 8}px`;
        };

        const deactivate = (article_li) => {
            const article_id = article_li.getAttribute('data-glpi-kb-article-id');
            tree.querySelectorAll(`[data-glpi-kb-article-id="${CSS.escape(article_id)}"]`)
                .forEach(el => el.classList.remove('kb-article--sibling'));
            article_li.querySelector('a')?.removeAttribute('aria-describedby');
            tooltip_el?.remove();
            tooltip_el = null;
        };

        const enter = (article_li) => {
            if (article_li === active_li) {
                return;
            }
            if (active_li) {
                deactivate(active_li);
            }
            active_li = article_li;
            activate(article_li);
        };

        const leave = (related_target) => {
            if (!active_li) {
                return;
            }
            // Pointer / focus moved to a descendant of the active <li> — stay active.
            if (related_target && active_li.contains(related_target)) {
                return;
            }
            deactivate(active_li);
            active_li = null;
        };

        // Delegation via bubbling events. Resilient to DOM mutations (drag-and-drop
        // reparenting, search filtering) — data attributes are read fresh on every event.
        tree.addEventListener('mouseover', (e) => {
            const article_li = e.target.closest('[data-glpi-kb-article-multi]');
            if (article_li) {
                enter(article_li);
            }
        });
        tree.addEventListener('mouseout', (e) => leave(e.relatedTarget));

        // Keyboard parity: tabbing to a multi-category article surfaces the same tooltip.
        tree.addEventListener('focusin', (e) => {
            const article_li = e.target.closest('[data-glpi-kb-article-multi]');
            if (article_li) {
                enter(article_li);
            }
        });
        tree.addEventListener('focusout', (e) => leave(e.relatedTarget));

        // ARIA Tooltip pattern: Escape dismisses without moving focus.
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && active_li) {
                deactivate(active_li);
                active_li = null;
            }
        });
    }
}
