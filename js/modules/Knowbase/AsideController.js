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

/* global _, glpi_toast_error */

import { get } from "/js/modules/Ajax.js";

const ITEMTYPE_ARTICLE   = "KnowbaseItem";
const ITEMTYPE_CATEGORY  = "KnowbaseItemCategory";
const AUTO_EXPAND_DELAY  = 800;

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
     * Active drag operation state. Null when no drag is in progress.
     * @type {?{source: HTMLElement, origin_parent: HTMLElement, origin_next: ?HTMLElement, current_target: ?HTMLElement, expand_timer: ?number, expand_target: ?HTMLElement}}
     */
    #drag = null;

    /**
     * @param {HTMLElement} aside
     */
    constructor(aside)
    {
        this.#aside = aside;
        this.#initCategoryToggle();
        this.#initSearch();
        this.#initDragAndDrop();
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
     * Initialize native HTML5 drag-and-drop reparenting on the tree.
     *
     * The whole <li> is draggable (set server-side based on rights via the
     * `draggable="true"` HTML attribute). Drop targets are category title rows
     * ([data-glpi-kb-aside-category-title]). Dropping onto a category's title
     * makes the dragged item a child of that category. Hovering on a collapsed
     * category for AUTO_EXPAND_DELAY ms auto-expands it.
     */
    #initDragAndDrop()
    {
        const tree = this.#aside.querySelector('[data-glpi-kb-aside-tree]');
        if (!tree) {
            return;
        }
        const can_reorder_articles   = tree.hasAttribute('data-glpi-kb-aside-can-reorder-articles');
        const can_reorder_categories = tree.hasAttribute('data-glpi-kb-aside-can-reorder-categories');
        if (!can_reorder_articles && !can_reorder_categories) {
            return;
        }

        tree.addEventListener('dragstart', (e) => this.#onDragStart(e));
        tree.addEventListener('dragover',  (e) => this.#onDragOver(e));
        tree.addEventListener('dragenter', (e) => this.#onDragEnter(e));
        tree.addEventListener('dragleave', (e) => this.#onDragLeave(e));
        tree.addEventListener('drop',      (e) => this.#onDrop(e));
        tree.addEventListener('dragend',   (e) => this.#onDragEnd(e));
    }

    /**
     * @param {DragEvent} event
     */
    #onDragStart(event)
    {
        const li = event.target.closest('li[draggable="true"]');
        if (!li) {
            return;
        }

        this.#drag = {
            source: li,
            origin_parent: li.parentElement,
            origin_next: li.nextElementSibling,
            current_target: null,
            expand_timer: null,
            expand_target: null,
        };

        event.dataTransfer.effectAllowed = 'move';
        // Required by Firefox; payload is unused (we keep state on `this`).
        event.dataTransfer.setData('text/plain', '');

        // Defer the class toggle so the browser captures the un-faded element
        // for the native drag image, then fades the source in place.
        requestAnimationFrame(() => {
            li.classList.add('kb-aside-source-dragging');
            this.#aside.classList.add('kb-aside-dragging');
        });
    }

    /**
     * @param {DragEvent} event
     */
    #onDragOver(event)
    {
        if (!this.#drag?.source) {
            return;
        }
        // Required to allow a drop.
        event.preventDefault();
        event.dataTransfer.dropEffect = 'move';
    }

    /**
     * @param {DragEvent} event
     */
    #onDragEnter(event)
    {
        if (!this.#drag?.source) {
            return;
        }
        const title = event.target.closest?.('[data-glpi-kb-aside-category-title]');
        if (!title) {
            return;
        }

        const target_li = title.closest('[data-glpi-kb-aside-category]');
        const target_id = parseInt(target_li.dataset.glpiKbAsideCategoryId, 10);
        const { itemtype, items_id } = this.#identifySource(this.#drag.source);

        if (!this.#isDropAllowed(itemtype, items_id, target_li, target_id)) {
            return;
        }

        this.#clearTargetHighlight();
        title.classList.add('kb-aside-target');
        this.#drag.current_target = title;

        if (target_li.hasAttribute('data-glpi-kb-aside-category-collapsed')) {
            this.#scheduleAutoExpand(target_li);
        }
    }

    /**
     * @param {DragEvent} event
     */
    #onDragLeave(event)
    {
        if (!this.#drag?.source) {
            return;
        }
        const title = event.target.closest?.('[data-glpi-kb-aside-category-title]');
        if (!title || title !== this.#drag.current_target) {
            return;
        }
        // dragleave fires when entering a child node too — confirm we left the
        // title row by checking relatedTarget.
        const related_title = event.relatedTarget?.closest?.('[data-glpi-kb-aside-category-title]');
        if (related_title === title) {
            return;
        }

        this.#clearTargetHighlight();
        this.#cancelAutoExpand();
    }

    /**
     * @param {DragEvent} event
     */
    async #onDrop(event)
    {
        if (!this.#drag?.source) {
            return;
        }
        event.preventDefault();

        const target_title = this.#drag.current_target;
        if (!target_title) {
            this.#revertDrop();
            return;
        }

        const target_li = target_title.closest('[data-glpi-kb-aside-category]');
        const target_id = parseInt(target_li.dataset.glpiKbAsideCategoryId, 10);
        const { itemtype, items_id } = this.#identifySource(this.#drag.source);
        const from_parent_id = this.#getCurrentParentId(this.#drag.source);

        if (from_parent_id === target_id) {
            this.#clearTargetHighlight();
            return;
        }

        const target_children = target_li.querySelector(':scope > ul');
        if (!target_children) {
            this.#revertDrop();
            return;
        }
        // Optimistic move.
        target_children.appendChild(this.#drag.source);

        let response;
        try {
            response = await fetch(`${CFG_GLPI.root_doc}/Knowbase/Aside/Reparent`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    itemtype,
                    items_id,
                    from_parent_id,
                    to_parent_id: target_id,
                }),
            });
        } catch (e) {
            this.#revertDrop();
            glpi_toast_error(__("An unexpected error occurred."));
            console.error(e);
            return;
        }

        if (response.ok) {
            return;
        }
        this.#revertDrop();
        if (response.status === 409) {
            glpi_toast_error(__("This item can't be moved here."));
        } else {
            glpi_toast_error(__("An unexpected error occurred."));
        }
    }

    #onDragEnd()
    {
        this.#clearTargetHighlight();
        this.#cancelAutoExpand();
        if (this.#drag?.source) {
            this.#drag.source.classList.remove('kb-aside-source-dragging');
        }
        this.#aside.classList.remove('kb-aside-dragging');
        this.#drag = null;
    }

    #clearTargetHighlight()
    {
        if (this.#drag?.current_target) {
            this.#drag.current_target.classList.remove('kb-aside-target');
            this.#drag.current_target = null;
        }
    }

    /**
     * @param {HTMLElement} target_li
     */
    #scheduleAutoExpand(target_li)
    {
        if (this.#drag.expand_target === target_li) {
            return;
        }
        this.#cancelAutoExpand();
        this.#drag.expand_target = target_li;
        this.#drag.expand_timer = window.setTimeout(() => {
            target_li.removeAttribute('data-glpi-kb-aside-category-collapsed');
            const toggle = target_li.querySelector('[data-glpi-kb-aside-category-toggle]');
            toggle?.setAttribute('aria-expanded', 'true');
            this.#drag.expand_timer = null;
            this.#drag.expand_target = null;
        }, AUTO_EXPAND_DELAY);
    }

    #cancelAutoExpand()
    {
        if (this.#drag?.expand_timer) {
            window.clearTimeout(this.#drag.expand_timer);
            this.#drag.expand_timer = null;
            this.#drag.expand_target = null;
        }
    }

    /**
     * @param {HTMLElement} li
     * @returns {{itemtype: ?string, items_id: ?number}}
     */
    #identifySource(li)
    {
        if (li.hasAttribute('data-glpi-kb-article-id')) {
            return {
                itemtype: ITEMTYPE_ARTICLE,
                items_id: parseInt(li.dataset.glpiKbArticleId, 10),
            };
        }
        if (li.hasAttribute('data-glpi-kb-aside-category')) {
            return {
                itemtype: ITEMTYPE_CATEGORY,
                items_id: parseInt(li.dataset.glpiKbAsideCategoryId, 10),
            };
        }
        return { itemtype: null, items_id: null };
    }

    /**
     * Resolve the id of the category currently containing `li`.
     * Returns 0 when the source's parent is the synthetic root.
     *
     * @param {HTMLElement} li
     * @returns {number}
     */
    #getCurrentParentId(li)
    {
        const parent_li = li.parentElement?.closest('[data-glpi-kb-aside-category]');
        if (!parent_li) {
            return 0;
        }
        return parseInt(parent_li.dataset.glpiKbAsideCategoryId, 10);
    }

    /**
     * @param {?string}     itemtype
     * @param {?number}     items_id
     * @param {HTMLElement} target_li
     * @param {number}      target_id
     * @returns {boolean}
     */
    #isDropAllowed(itemtype, items_id, target_li, target_id)
    {
        const is_uncategorized = target_li.hasAttribute('data-glpi-kb-aside-category-uncategorized');

        if (itemtype === ITEMTYPE_ARTICLE) {
            // Articles can go into any category including the uncategorized
            // pseudo-bucket (id=0).
            return true;
        }

        if (itemtype === ITEMTYPE_CATEGORY) {
            if (is_uncategorized) {
                return false;
            }
            if (target_id === items_id) {
                return false;
            }
            // A category cannot be dropped onto one of its own descendants.
            if (this.#drag.source.contains(target_li)) {
                return false;
            }
            return true;
        }

        return false;
    }

    /**
     * Put the source element back at its origin (optimistic UI revert).
     */
    #revertDrop()
    {
        if (!this.#drag?.source || !this.#drag?.origin_parent) {
            return;
        }
        this.#drag.origin_parent.insertBefore(
            this.#drag.source,
            this.#drag.origin_next,
        );
    }
}
