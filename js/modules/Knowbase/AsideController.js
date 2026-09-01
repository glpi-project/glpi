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

import { get, post } from "/js/modules/Ajax.js";
import {
    EditorActionType,
    extractParamsFromDataset,
    isTogglePending,
    runToggle,
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
     * Watches the "load more" marker at the end of the search results.
     * @type {IntersectionObserver|null}
     */
    #search_load_more_observer = null;

    /**
     * Children requests by article id, so a branch is only fetched once even if
     * the reader folds and unfolds it repeatedly.
     *
     * @type {Map<number, Promise<string>>}
     */
    #children_cache = new Map();

    /**
     * In-flight/resolved dots menu content, keyed by article id. The tree
     * renders only the dots trigger; the menu items are fetched on demand
     * (prefetched on hover) so we never build every article's actions up-front.
     * @type {Map<number, Promise<string>>}
     */
    #actions_cache = new Map();

    /**
     * Viewport width (px) under which the aside becomes a sliding overlay.
     * @type {number}
     */
    static #COLLAPSE_BREAKPOINT = 992;

    /**
     * localStorage key persisting the desktop collapsed preference.
     * @type {string}
     */
    static #STORAGE_KEY = 'glpi-kb-aside-collapsed';

    /** @type {HTMLElement|null} */
    #collapse_btn = null;

    /** @type {HTMLElement|null} */
    #expand_btn = null;

    /** @type {HTMLElement|null} */
    #body = null;

    /** @type {HTMLElement|null} */
    #backdrop = null;

    /**
     * @param {HTMLElement} aside
     */
    constructor(aside)
    {
        this.#aside = aside;
        this.#initCategoryToggle();
        this.#initSearch();
        this.#initCreateArticle();
        this.#initActions();
        this.#initToggle();
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

            // Toggle collapsed state
            this.#setCollapsed(
                node,
                !node.hasAttribute('data-glpi-kb-aside-category-collapsed')
            );
        });
    }

    /**
     * Collapse or expand a tree node, keeping the DOM, the toggle's ARIA state
     * and the persisted per-user fold state in sync.
     *
     * @param {HTMLElement} node
     * @param {boolean} collapsed
     * @returns {Promise<void>}
     */
    async #setCollapsed(node, collapsed)
    {
        const id = node.dataset.glpiKbArticleId;

        // The same article can be rendered more than once, under each of its
        // parents. They all share one fold state, so every copy gets the very
        // same treatment.
        const nodes = id ? this.#aside.querySelectorAll(
            `[data-glpi-kb-aside-category][data-glpi-kb-article-id="${CSS.escape(id)}"]`,
        ) : [node];

        const loading = [];
        for (const twin of nodes) {
            twin.toggleAttribute('data-glpi-kb-aside-category-collapsed', collapsed);

            // `:scope >` on the header is required: without it we would reach
            // the toggle of a nested article instead of this node's own one.
            const toggle = twin.querySelector(
                ':scope > [data-glpi-kb-aside-category-header] [data-glpi-kb-aside-category-toggle]'
            );
            // A childless node has no toggle to update (it is still collapsible,
            // so that a child created below lands in a visible list).
            toggle?.setAttribute('aria-expanded', collapsed ? 'false' : 'true');

            if (!collapsed) {
                // A single fetch feeds them all: `#loadChildren()` caches the
                // pending request per article id.
                loading.push(this.#loadChildren(twin));
            }
        }

        this.#persistArticleFold(id, collapsed);

        await Promise.all(loading);
    }

    /**
     * Fill in the children of a node the reader just unfolded, if the tree was
     * rendered without them.
     *
     * @param {HTMLElement} node
     */
    async #loadChildren(node)
    {
        // `:scope >` is required: a nested node has a list of its own.
        const list = node.querySelector(':scope > ul[data-glpi-kb-children-unloaded]');
        if (!list) {
            return;
        }
        // Claim it right away, so a second unfold does not fetch it again.
        list.removeAttribute('data-glpi-kb-children-unloaded');

        const id = parseInt(node.dataset.glpiKbArticleId);
        if (!this.#children_cache.has(id)) {
            const current_id = this.#aside
                .querySelector('[data-glpi-kb-aside-tree] [data-glpi-kb-article-current]')
                ?.dataset.glpiKbArticleId ?? '0';
            this.#children_cache.set(
                id,
                get(
                    `Knowbase/Aside/Article/${encodeURIComponent(id)}/Children`
                    + `?current_id=${encodeURIComponent(current_id)}`,
                ).then((response) => response.text()),
            );
        }

        try {
            list.innerHTML = await this.#children_cache.get(id);
        } catch {
            // Drop the cached rejection and let a later unfold retry.
            this.#children_cache.delete(id);
            list.setAttribute('data-glpi-kb-children-unloaded', '');
        }
    }

    #initToggle()
    {
        this.#collapse_btn = this.#aside.querySelector('[data-glpi-kb-aside-collapse]');
        this.#expand_btn   = this.#aside.querySelector('[data-glpi-kb-aside-expand]');
        this.#body         = this.#aside.querySelector('[data-glpi-kb-aside-body]');

        // Defensive: the aside may render without the toggle controls.
        if (!this.#collapse_btn || !this.#expand_btn || !this.#body) {
            return;
        }

        // Restore the persisted desktop collapsed state; the overlay starts closed.
        if (this.#readCollapsed()) {
            this.#aside.setAttribute('data-glpi-kb-aside-collapsed', '');
        }
        this.#syncAria();

        // Commit the initial state (reflow) before enabling transitions, so only user toggles animate.
        void this.#aside.offsetWidth;
        this.#aside.classList.add('kb-aside-animated');

        this.#collapse_btn.addEventListener('click', () => this.#close());
        this.#expand_btn.addEventListener('click', () => this.#open());

        // Close the mobile overlay on Escape.
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.#aside.hasAttribute('data-glpi-kb-aside-open')) {
                this.#close();
            }
        });

        // Reset the transient overlay state when crossing the breakpoint.
        let was_mobile = this.#isMobile();
        window.addEventListener('resize', () => {
            const now_mobile = this.#isMobile();
            if (now_mobile !== was_mobile) {
                was_mobile = now_mobile;
                this.#aside.removeAttribute('data-glpi-kb-aside-open');
                this.#removeBackdrop();
                this.#syncAria();
            }
        });
    }

    #isMobile()
    {
        return window.innerWidth < GlpiKnowbaseAsideController.#COLLAPSE_BREAKPOINT;
    }

    // localStorage can throw when storage is blocked (enterprise policy…); degrade to no persistence.
    #readCollapsed()
    {
        try {
            return window.localStorage.getItem(GlpiKnowbaseAsideController.#STORAGE_KEY) === '1';
        } catch {
            return false;
        }
    }

    #storeCollapsed(collapsed)
    {
        try {
            window.localStorage.setItem(GlpiKnowbaseAsideController.#STORAGE_KEY, collapsed ? '1' : '0');
        } catch { /* storage unavailable */ }
    }

    #open()
    {
        if (this.#isMobile()) {
            this.#aside.setAttribute('data-glpi-kb-aside-open', '');
            this.#addBackdrop();
        } else {
            this.#aside.removeAttribute('data-glpi-kb-aside-collapsed');
            this.#storeCollapsed(false);
        }
        this.#syncAria();
        // Focus the control that stays visible, else focus is stranded on a hidden button.
        this.#collapse_btn.focus();
    }

    #close()
    {
        if (this.#isMobile()) {
            this.#aside.removeAttribute('data-glpi-kb-aside-open');
            this.#removeBackdrop();
        } else {
            this.#aside.setAttribute('data-glpi-kb-aside-collapsed', '');
            this.#storeCollapsed(true);
        }
        this.#syncAria();
        // Collapsed: the chevron is hidden, so focus the rail's expand button.
        this.#expand_btn.focus();
    }

    #syncAria()
    {
        const body_visible = this.#isMobile()
            ? this.#aside.hasAttribute('data-glpi-kb-aside-open')
            : !this.#aside.hasAttribute('data-glpi-kb-aside-collapsed');
        this.#collapse_btn.setAttribute('aria-expanded', String(body_visible));
        this.#expand_btn.setAttribute('aria-expanded', String(body_visible));
    }

    #addBackdrop()
    {
        if (this.#backdrop) {
            return;
        }
        this.#backdrop = document.createElement('div');
        this.#backdrop.className = 'kb-aside-backdrop';
        this.#backdrop.addEventListener('click', () => this.#close());
        document.body.appendChild(this.#backdrop);
    }

    #removeBackdrop()
    {
        if (this.#backdrop) {
            this.#backdrop.remove();
            this.#backdrop = null;
        }
    }

    /**
     * @param {string|undefined} id
     * @param {boolean} collapsed
     */
    #persistArticleFold(id, collapsed)
    {
        if (!id) {
            return;
        }

        // Persist to server. We don't care about the response as the UI was
        // already updated.
        post(`Knowbase/Aside/Article/${encodeURIComponent(id)}/Fold`, { collapsed });
    }

    #initCreateArticle()
    {
        this.#aside.addEventListener('click', (e) => {
            const add_button = e.target.closest('[data-glpi-kb-aside-category-add]');
            if (!add_button) {
                return;
            }
            e.preventDefault();
            this.#openCreateInput(add_button);
        });
    }

    /**
     * @param {HTMLElement} add_button
     */
    async #openCreateInput(add_button)
    {
        const header = add_button.closest('[data-glpi-kb-aside-category-header]');
        const node = header.closest('[data-glpi-kb-aside-category]');
        const parent_id = Number(add_button.dataset.glpiKbAsideCategoryAdd) || 0;

        // The list is hidden while the node is collapsed, so the input below
        // would be inserted into a `display: none` subtree: invisible, and
        // impossible to focus. Expand first. This also persists the new fold
        // state, which matters because a successful create navigates to the new
        // article: were the parent still folded on that reload, the article the
        // user just created would be hidden.
        if (node.hasAttribute('data-glpi-kb-aside-category-collapsed')) {
            await this.#setCollapsed(node, false);
        }

        // Looked up after the expansion above, which may have refilled it.
        const list = node.querySelector(':scope > ul');

        // Only one inline input at a time across the whole tree.
        const existing = this.#aside.querySelector('[data-glpi-kb-aside-create-row]');
        if (existing) {
            existing.remove();
        }

        const li = document.createElement('li');
        li.dataset.glpiKbAsideCreateRow = '';
        li.className = 'article mb-2';

        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'form-control form-control-sm';
        input.placeholder = __('New article...');
        li.appendChild(input);
        list.prepend(li);
        input.focus();

        let settled = false;
        const cleanup = () => {
            settled = true;
            li.remove();
        };
        const unsettle = () => {
            settled = false;
        };

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                e.preventDefault();
                cleanup();
            } else if (e.key === 'Enter') {
                e.preventDefault();
                settled = true;
                this.#commitCreateInput(input, parent_id, cleanup, unsettle);
            }
        });

        input.addEventListener('blur', () => {
            if (settled) {
                return;
            }
            if (input.value.trim() === '') {
                cleanup();
            } else {
                settled = true;
                this.#commitCreateInput(input, parent_id, cleanup, unsettle);
            }
        });
    }

    /**
     * @param {HTMLInputElement} input
     * @param {number} parent_id
     * @param {() => void} cleanup
     * @param {() => void} unsettle
     */
    async #commitCreateInput(input, parent_id, cleanup, unsettle)
    {
        const name = input.value.trim();
        if (name === '') {
            cleanup();
            return;
        }

        input.disabled = true;

        let data;
        try {
            const response = await post('Knowbase/KnowbaseItem/Create', {
                name,
                knowbaseitems_id_parent: parent_id,
            });
            data = await response.json();
        } catch {
            unsettle();
            input.disabled = false;
            input.focus();
            return;
        }

        window.location.href = data.url;
    }

    #initSearch()
    {
        // Get target nodes from the DOM
        const search_input  = this.#aside.querySelector('[data-glpi-kb-aside-search-input]');
        const search_icon   = this.#aside.querySelector('[data-glpi-kb-aside-search-icon]');
        const clear_button  = this.#aside.querySelector('[data-glpi-kb-aside-search-clear]');

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
        const tree = this.#aside.querySelector('[data-glpi-kb-aside-tree]');

        const request_id = ++this.#search_request_id;

        // Search criteria was removed, show all items again
        if (value.trim() === '') {
            this.#showAllTreeItems(tree);
            this.#setFavoritesSearchHidden(false);
            return;
        }

        // Send request to backend
        const response = await get(
            `Knowbase/Aside/Search?contains=${encodeURIComponent(value)}`
            + `&current_id=${encodeURIComponent(this.#currentArticleId())}`,
        );
        const html = await response.text();
        if (request_id !== this.#search_request_id) {
            return;
        }

        // Apply results.
        this.#showTreeResults(tree, html);
        this.#setFavoritesSearchHidden(true);
    }

    /**
     * Replace the tree with the server-rendered search results. The rendered
     * tree is kept in place (hidden) so clearing the search restores it without
     * a round trip.
     *
     * @param {HTMLElement} tree
     * @param {string}      html
     */
    #showTreeResults(tree, html)
    {
        const rendered = tree.querySelector(':scope > ul.kb-tree');
        rendered?.setAttribute('data-glpi-kb-search-hidden', '');

        let results = tree.querySelector(':scope > [data-glpi-kb-aside-tree-results]');
        if (!results) {
            results = document.createElement('ul');
            results.className = 'kb-search-results ps-0';
            results.setAttribute('data-glpi-kb-aside-tree-results', '');
            results.dataset.testid = 'kb-search-results';
            rendered ? rendered.after(results) : tree.prepend(results);
        }
        results.innerHTML = html;

        // Back to the first result
        tree.scrollTop = 0;

        this.#watchSearchLoadMore(results);

        const no_results = tree.querySelector('[data-glpi-kb-aside-no-results]');
        no_results.hidden = results.querySelector('[data-glpi-kb-article-id]') !== null;
    }

    /**
     * The results are loaded 50 at a time. The server puts a marker after the
     * last one, watched here to append the next page as it comes into view.
     *
     * @param {HTMLElement} list
     */
    #watchSearchLoadMore(list)
    {
        // The list is replaced on each search, so the observer is always reset.
        this.#search_load_more_observer?.disconnect();
        this.#search_load_more_observer = null;

        const marker = list.querySelector('[data-glpi-kb-aside-search-load-more]');
        if (!marker) {
            return;
        }

        this.#search_load_more_observer = new IntersectionObserver(
            (entries) => {
                for (const entry of entries) {
                    if (entry.isIntersecting) {
                        this.#loadNextSearchPage(entry.target);
                    }
                }
            },
            // The results scroll with the pane holding them, and the next page
            // is loaded slightly before the marker is actually reached.
            { root: list.closest('[data-glpi-kb-aside-tree]'), rootMargin: '200px' },
        );
        this.#search_load_more_observer.observe(marker);
    }

    /**
     * @param {HTMLElement} marker
     */
    async #loadNextSearchPage(marker)
    {
        // Both the observer and a fast scroll may ask for the same page.
        if (marker.dataset.glpiLoading !== undefined) {
            return;
        }
        marker.dataset.glpiLoading = '';

        // The marker is a live region: it says what is happening now, and the
        // reader may come back to it after a failure.
        const loading = marker.querySelector('[data-glpi-kb-aside-search-load-more-loading]');
        const error = marker.querySelector('[data-glpi-kb-aside-search-load-more-error]');
        loading.hidden = false;
        error.hidden = true;

        // The marker carries the search it belongs to.
        const contains = marker.dataset.glpiKbAsideSearchContains;
        const offset = marker.dataset.glpiKbAsideSearchNextOffset;

        let page;
        try {
            const response = await get(
                `Knowbase/Aside/Search?contains=${encodeURIComponent(contains)}`
                + `&offset=${encodeURIComponent(offset)}`
                + `&current_id=${encodeURIComponent(this.#currentArticleId())}`,
            );
            page = await response.text();
        } catch {
            // The marker stays watched, so scrolling it out of view and back
            // in asks for the page again.
            delete marker.dataset.glpiLoading;
            loading.hidden = true;
            error.hidden = false;
            return;
        }

        // The marker left the document: its search is over.
        if (!marker.isConnected) {
            return;
        }

        // The page ends with the marker of the following one, if any.
        const list = marker.parentElement;
        marker.insertAdjacentHTML('beforebegin', page);
        marker.remove();

        this.#watchSearchLoadMore(list);
    }

    /**
     * @returns {string} `0` when no article is being read
     */
    #currentArticleId()
    {
        return this.#aside.querySelector('[data-glpi-kb-article-current]')
            ?.dataset.glpiKbArticleId ?? '0';
    }

    /**
     * Show all articles and categories in the tree (restores state after filtering).
     *
     * @param {HTMLElement} tree
     */
    #showAllTreeItems(tree)
    {
        this.#search_load_more_observer?.disconnect();
        this.#search_load_more_observer = null;

        tree.querySelector(':scope > [data-glpi-kb-aside-tree-results]')?.remove();

        for (const el of tree.querySelectorAll('[data-glpi-kb-search-hidden]')) {
            el.removeAttribute('data-glpi-kb-search-hidden');
        }

        const no_results = tree.querySelector('[data-glpi-kb-aside-no-results]');
        no_results.hidden = true;
    }

    /**
     * Hide the favorites section (and the header border going with it) for as
     * long as the results stand in for the tree. This is a reason of its own,
     * kept apart from the "no favorites to show" state the section owns.
     *
     * @param {boolean} hidden
     */
    #setFavoritesSearchHidden(hidden)
    {
        const favorites = this.#aside.querySelector('[data-glpi-kb-aside-favorites]');
        const header = this.#aside.querySelector('[data-glpi-kb-aside-header]');

        // The header holds the search input, so it only loses its border.
        if (hidden) {
            favorites.setAttribute('data-glpi-kb-search-hidden', '');
            header.setAttribute('data-glpi-kb-aside-header-search-no-border', '');
        } else {
            favorites.removeAttribute('data-glpi-kb-search-hidden');
            header.removeAttribute('data-glpi-kb-aside-header-search-no-border');
        }
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

            // Keep the checkbox's native toggle on direct clicks; cancelling it desyncs the UI.
            if (!e.target.matches('input[type="checkbox"]')) {
                e.preventDefault();
            }
            try {
                this.#executeAction(e, button);
            } catch (error) {
                glpi_toast_error(__("An unexpected error occurred."));
                throw error;
            }
        });

        // Create the row's menu and prefetch its content as soon as the row is
        // hovered or focused, so both are ready by the time the user opens the
        // kebab (no visible latency).
        const prepare = (e) => {
            const line = e.target.closest('.article[data-glpi-kb-article-id]');
            if (line && this.#aside.contains(line)) {
                this.#ensureActionsMenu(line);
                this.#populateMenus(parseInt(line.dataset.glpiKbArticleId));
            }
        };
        this.#aside.addEventListener('mouseover', prepare);
        this.#aside.addEventListener('focusin', prepare);
        // Safety net for opens that skip hover and focus (touch, synthetic
        // clicks): the menu has to exist before Bootstrap looks it up, and the
        // capture phase runs before its own delegated click handler.
        this.#aside.addEventListener('pointerdown', prepare);
        this.#aside.addEventListener('click', prepare, true);

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
     * Create an article row's kebab menu element, unless it already has one.
     *
     * The tree only renders the menu triggers: a large knowledge base would
     * otherwise carry thousands of identical, never-opened menus. The menu is
     * cloned from the template the aside renders once, see
     * `render_actions_menu_lazy()`.
     *
     * @param {HTMLElement} line
     */
    #ensureActionsMenu(line)
    {
        // Scoped to the row itself: a row nests its child rows, whose own
        // triggers must not be confused with it.
        const dropdown = line.querySelector(':scope > .article-line > .dropdown');
        if (!dropdown || dropdown.querySelector(':scope > [data-glpi-kb-actions-menu]')) {
            return;
        }

        const template = this.#aside.querySelector('[data-glpi-kb-actions-menu-template]');
        if (template) {
            dropdown.append(template.content.cloneNode(true));
        }
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

        if (this.#findEmptyMenus(id).length === 0) {
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

        for (const menu of this.#findEmptyMenus(id)) {
            menu.innerHTML = html;
            menu.setAttribute('data-glpi-kb-actions-loaded', '');
        }
    }

    /**
     * @param {number} id
     * @returns {HTMLElement[]}
     */
    #findEmptyMenus(id)
    {
        const menus = this.#aside.querySelectorAll(
            `[data-glpi-kb-article-id="${CSS.escape(id)}"] `
            + `[data-glpi-kb-actions-menu]:not([data-glpi-kb-actions-loaded])`,
        );

        return Array.from(menus).filter(
            (menu) => menu.closest('[data-glpi-kb-article-id]')?.dataset.glpiKbArticleId === String(id),
        );
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
                const clicked_on_toggle = e.target === toggle;
                if (isTogglePending(EditorActionType.TOGGLE_FAVORITE, id)) {
                    // Ignore clicks while a request is in flight; undo any native flip.
                    if (clicked_on_toggle) {
                        toggle.checked = !toggle.checked;
                    }
                    break;
                }
                if (!clicked_on_toggle) {
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
        syncToggleCheckboxes(id, EditorActionType.TOGGLE_FAVORITE, value);
        try {
            const { favorite } = await runToggle(
                EditorActionType.TOGGLE_FAVORITE,
                id,
                () => toggleFavorite(id, value),
            );
            // Reconcile from the server's authoritative state.
            this.#updateFavoritesSection(id, favorite);
            syncToggleCheckboxes(id, EditorActionType.TOGGLE_FAVORITE, favorite);
        } catch (error) {
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
        // place. A tree entry may nest child articles inside its own <li>
        // (recursive tree), and those children are NOT deleted server-side:
        // on reload the Builder promotes any article left without a visible
        // parent up to the root. Mirror that here so children don't vanish
        // until reload — reparent them to the root <ul> before removing the
        // deleted node, unless they remain reachable under another parent.
        const tree = this.#aside.querySelector('[data-glpi-kb-aside-tree]');
        const root_list = tree ? tree.querySelector(':scope > ul') : null;

        for (const entry of this.#aside.querySelectorAll(`[data-glpi-kb-article-id="${CSS.escape(id)}"]`)) {
            const child_list = entry.querySelector(':scope > ul');
            if (child_list && root_list) {
                for (const child of [...child_list.querySelectorAll(':scope > [data-glpi-kb-article-id]')]) {
                    const child_id = child.dataset.glpiKbArticleId;
                    // Still reachable under another (non-deleted) parent
                    // elsewhere in the tree? Then it stays there; promoting it
                    // would duplicate it, and on reload it would not be a root.
                    const still_reachable = [...this.#aside.querySelectorAll(
                        `[data-glpi-kb-aside-tree] [data-glpi-kb-article-id="${CSS.escape(child_id)}"]`
                    )].some(el => !entry.contains(el));
                    if (!still_reachable) {
                        root_list.appendChild(child);
                    }
                }
            }
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
                // Scoped to the tree list: the search results hold rows of
                // the same articles, in a shape of their own.
                const source = this.#aside.querySelector(
                    `[data-glpi-kb-aside-tree] > ul.kb-tree [data-glpi-kb-article-id="${CSS.escape(id)}"]`
                );
                if (source) {
                    const clone = source.cloneNode(true);
                    clone.classList.add('mb-2');
                    this.#flattenClonedFavorite(clone);
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
     * @param {HTMLElement} clone
     */
    #flattenClonedFavorite(clone)
    {
        for (const children of clone.querySelectorAll(':scope > ul')) {
            children.remove();
        }

        for (const affordance of clone.querySelectorAll(
            '[data-glpi-kb-aside-category-toggle], [data-glpi-kb-aside-category-add]',
        )) {
            affordance.remove();
        }

        clone.classList.remove('node');
        clone.removeAttribute('data-glpi-kb-aside-category');
        clone.removeAttribute('data-glpi-kb-aside-category-collapsed');
        // Node rows are groups labelled by their title; a flat entry is a plain
        // list item again.
        clone.removeAttribute('role');
        clone.removeAttribute('aria-label');

        const line = clone.querySelector(':scope > .article-line');
        if (line) {
            line.removeAttribute('data-glpi-kb-aside-category-header');
            line.classList.remove('mb-2');
        }
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
     * `ArticleController.#updateFavoritesAside()` applies the same rule from the
     * article side.
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
    }
}
