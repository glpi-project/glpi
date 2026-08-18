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

import { post } from "/js/modules/Ajax.js";
import { GlpiKnowbaseAsideAutoscroll } from "/js/modules/Knowbase/AsideAutoscroll.js";
import { parentIdOf, parentRowOf } from "/js/modules/Knowbase/AsideTree.js";

/**
 * Pointer-driven reparenting of articles in the knowledge base aside tree.
 *
 * An article may have several parents and is rendered under each of them, so
 * an "occurrence" has no identity beyond its DOM element: the parent of the
 * dragged occurrence is read from its DOM ancestry. Dropping moves that single
 * edge and leaves the article's other parents alone.
 */
export class GlpiKnowbaseAsideDragController
{
    /** Pointer travel before a drag is armed, so plain clicks still work. */
    static #THRESHOLD_PX = 5;

    /** Share of a row's height, at each end, that means "sibling" not "child". */
    static #EDGE_BAND_RATIO = 0.25;

    /** @type {HTMLElement|null} */
    #tree = null;

    /** @type {HTMLElement|null} */
    #root_list = null;

    /** @type {HTMLElement|null} The `<li>` being dragged. */
    #dragged = null;

    /** @type {HTMLElement|null} Floating label following the pointer. */
    #ghost = null;

    /** @type {HTMLElement|null} Rule showing a sibling drop position. */
    #insert_line = null;

    /**
     * What the current drop would do, or null when it would do nothing.
     * @type {{row: HTMLElement, mode: 'into'|'before'|'after', to_parent_id: number}|null}
     */
    #intent = null;

    /**
     * Article ids of the dragged subtree. Dropping onto any of them creates a
     * cycle, including onto another occurrence of the same article elsewhere.
     * @type {Set<number>}
     */
    #forbidden_ids = new Set();

    /**
     * Header lines eligible as drop targets, frozen for the whole gesture.
     * @type {HTMLElement[]}
     */
    #visible_lines = [];

    /** @type {number|null} Pointer that owns the current gesture. */
    #pointer_id = null;

    /** @type {{ x: number, y: number }|null} */
    #origin = null;

    /** @type {boolean} */
    #dragging = false;

    /** @type {boolean} */
    #suppress_next_click = false;

    /** @type {GlpiKnowbaseAsideAutoscroll} */
    #autoscroll = new GlpiKnowbaseAsideAutoscroll();

    /**
     * @param {HTMLElement} aside
     */
    constructor(aside)
    {
        this.#tree = aside.querySelector('[data-glpi-kb-aside-tree]');
        this.#root_list = this.#tree?.querySelector(':scope > ul.kb-tree') ?? null;
        if (!this.#root_list) {
            return;
        }
        this.#initEventHandlers();
    }

    #initEventHandlers()
    {
        this.#tree.addEventListener('pointerdown', (e) => this.#onPointerDown(e));
        // Before a drag arms there is no capture to catch a release outside the tree.
        window.addEventListener('pointermove', (e) => this.#onPointerMove(e));
        window.addEventListener('pointerup', (e) => this.#onPointerUp(e));
        window.addEventListener('pointercancel', (e) => this.#onPointerCancel(e));

        // Anchors are natively draggable; the browser's own drag would cancel ours.
        this.#tree.addEventListener('dragstart', (e) => e.preventDefault());

        // Swallow the click that ends a real drag, or the drop would follow the link.
        this.#tree.addEventListener('click', (e) => {
            if (!this.#suppress_next_click) {
                return;
            }
            this.#suppress_next_click = false;
            e.preventDefault();
            e.stopPropagation();
        }, true);
    }

    /**
     * @param {PointerEvent} e
     */
    #onPointerDown(e)
    {
        // A row removed on drop fires no click: void it before it eats another.
        this.#suppress_next_click = false;

        // Touch and pen would need `touch-action: none`, killing aside scrolling.
        if (e.pointerType !== 'mouse' || e.button !== 0) {
            return;
        }

        // Never hijack the row's own controls (fold toggle, "+", kebab).
        if (e.target.closest('button, [data-glpi-kb-aside-category-add]')) {
            return;
        }

        const row = e.target.closest('li[data-glpi-kb-article-id]');
        if (!row || !this.#tree.contains(row)) {
            return;
        }

        // Before the browser's own drag threshold beats our pointermove-based one.
        e.preventDefault();

        this.#dragged = row;
        this.#pointer_id = e.pointerId;
        this.#origin = { x: e.clientX, y: e.clientY };
    }

    /**
     * @param {PointerEvent} e
     */
    #onPointerMove(e)
    {
        if (this.#dragged === null || e.pointerId !== this.#pointer_id) {
            return;
        }

        if (!this.#dragging) {
            // Released where we could not see it, e.g. outside the window.
            if ((e.buttons & 1) === 0) {
                this.#cancel();
                return;
            }

            const distance = Math.hypot(
                e.clientX - this.#origin.x,
                e.clientY - this.#origin.y,
            );
            if (distance < GlpiKnowbaseAsideDragController.#THRESHOLD_PX) {
                return;
            }
            this.#startDrag(e.pointerId);
        }

        this.#ghost.style.transform = `translate(${e.clientX + 8}px, ${e.clientY + 8}px)`;
        this.#updateIntent(e.clientX, e.clientY);
        this.#autoscroll.update(e.clientY);
    }

    /**
     * @param {number} pointer_id
     */
    #startDrag(pointer_id)
    {
        this.#dragging = true;
        // Only now: a capture held during a plain press retargets its click.
        this.#dragged.setPointerCapture(pointer_id);
        this.#forbidden_ids = this.#collectSubtreeIds(this.#dragged);
        this.#visible_lines = this.#collectVisibleLines();
        this.#autoscroll.start(this.#tree);
        this.#dragged.setAttribute('data-glpi-kb-drag-source', '');
        document.body.classList.add('kb-drag-active');

        this.#ghost = document.createElement('div');
        this.#ghost.className = 'kb-drag-ghost';
        this.#ghost.textContent = this.#titleOf(this.#dragged);
        document.body.append(this.#ghost);

        this.#insert_line = document.createElement('div');
        this.#insert_line.className = 'kb-drag-insert';
        this.#insert_line.hidden = true;
        this.#tree.append(this.#insert_line);
    }

    /**
     * @param {HTMLElement} row
     * @returns {string}
     */
    #titleOf(row)
    {
        return row.querySelector('[data-glpi-kb-article-title]')?.textContent?.trim() ?? '';
    }

    /**
     * Article ids of the dragged subtree, including its own.
     *
     * Cycle detection works on ids, not on DOM containment: a descendant may
     * also be rendered elsewhere under another parent, and dropping onto that
     * occurrence would create a cycle just the same.
     *
     * Only what the tree renders: a descendant reachable solely through an
     * article the user cannot see is missing here, and the endpoint refuses it.
     *
     * @param {HTMLElement} row
     * @returns {Set<number>}
     */
    #collectSubtreeIds(row)
    {
        const ids = new Set([Number(row.dataset.glpiKbArticleId)]);
        for (const descendant of row.querySelectorAll('li[data-glpi-kb-article-id]')) {
            ids.add(Number(descendant.dataset.glpiKbArticleId));
        }
        return ids;
    }

    /**
     * @param {number} x
     * @param {number} y
     */
    #updateIntent(x, y)
    {
        this.#intent = this.#resolveIntent(x, y);
        this.#renderIntent(this.#intent);
    }

    /**
     * Null means "this drop changes nothing", whether the pointer sits on a
     * refused position or outside the tree: both render the refused state.
     *
     * @param {number} x
     * @param {number} y
     * @returns {{row: HTMLElement, mode: string, to_parent_id: number}|null}
     */
    #resolveIntent(x, y)
    {
        const element = document.elementFromPoint(x, y);
        if (!element || !this.#tree.contains(element)) {
            return null;
        }

        // Below the list: the empty area reads as "after the last root article".
        const intent = y > this.#root_list.getBoundingClientRect().bottom
            ? this.#rootIntent()
            : this.#bandIntent(y);

        return this.#isEffective(intent) ? intent : null;
    }

    /**
     * @returns {{row: HTMLElement, mode: string, to_parent_id: number}|null}
     */
    #rootIntent()
    {
        const last = this.#root_list.lastElementChild;
        return last === null ? null : { row: last, mode: 'after', to_parent_id: 0 };
    }

    /**
     * Rows are hit by vertical distance rather than by `closest()`: the 8px
     * gaps between lines and the 24px indent gutter belong to the DOM of the
     * row above, which would otherwise capture drops meant for its neighbour.
     *
     * @param {number} y
     * @returns {{row: HTMLElement, mode: string, to_parent_id: number}|null}
     */
    #bandIntent(y)
    {
        let row = null;
        let rect = null;
        let best = Infinity;

        for (const line of this.#visible_lines) {
            const bounds = line.getBoundingClientRect();
            const distance = Math.max(bounds.top - y, y - bounds.bottom, 0);
            if (distance < best) {
                best = distance;
                row = line.parentElement;
                rect = bounds;
            }
            if (distance === 0) {
                break;
            }
        }

        if (row === null) {
            return null;
        }

        const band = rect.height * GlpiKnowbaseAsideDragController.#EDGE_BAND_RATIO;
        if (y < rect.top + band) {
            return { row, mode: 'before', to_parent_id: parentIdOf(row) };
        }
        if (y > rect.bottom - band) {
            return { row, mode: 'after', to_parent_id: parentIdOf(row) };
        }
        return { row, mode: 'into', to_parent_id: Number(row.dataset.glpiKbArticleId) };
    }

    /**
     * Header lines of rows that are not hidden inside a collapsed ancestor.
     * Collected once per gesture: folding only happens on drop.
     *
     * @returns {HTMLElement[]}
     */
    #collectVisibleLines()
    {
        const lines = this.#tree.querySelectorAll(
            'li[data-glpi-kb-article-id] > .article-line'
        );
        return [...lines].filter((line) => line.offsetParent !== null);
    }

    /**
     * @param {{to_parent_id: number}|null} intent
     * @returns {boolean}
     */
    #isEffective(intent)
    {
        if (intent === null) {
            return false;
        }
        // A parent taken from the dragged subtree would close a cycle.
        if (this.#forbidden_ids.has(intent.to_parent_id)) {
            return false;
        }
        return intent.to_parent_id !== this.#currentParentId();
    }

    /**
     * @returns {number}
     */
    #currentParentId()
    {
        return parentIdOf(this.#dragged);
    }

    /**
     * @param {{row: HTMLElement, mode: string}|null} intent
     */
    #renderIntent(intent)
    {
        this.#clearIntentMarks();
        document.body.classList.toggle('kb-drag-refused', intent === null);

        if (intent === null) {
            return;
        }

        if (intent.mode === 'into') {
            intent.row.setAttribute('data-glpi-kb-drop-target', '');
            return;
        }

        this.#placeInsertLine(intent);
    }

    /**
     * The line is placed on the target's own header line, so its horizontal
     * offset carries the destination level: 24px of indent per depth.
     *
     * @param {{row: HTMLElement, mode: string}} intent
     */
    #placeInsertLine(intent)
    {
        const line = intent.row.querySelector(':scope > .article-line');
        const rect = line.getBoundingClientRect();
        const tree_rect = this.#tree.getBoundingClientRect();
        const edge = intent.mode === 'before' ? rect.top : rect.bottom;

        this.#insert_line.hidden = false;
        this.#insert_line.style.left =
            `${rect.left - tree_rect.left + this.#tree.scrollLeft}px`;
        this.#insert_line.style.width = `${rect.width}px`;
        this.#insert_line.style.top =
            `${edge - tree_rect.top + this.#tree.scrollTop}px`;
    }

    #clearIntentMarks()
    {
        for (const marked of this.#tree.querySelectorAll('[data-glpi-kb-drop-target]')) {
            marked.removeAttribute('data-glpi-kb-drop-target');
        }
        if (this.#insert_line !== null) {
            this.#insert_line.hidden = true;
        }
    }

    /**
     * @param {PointerEvent} e
     */
    #onPointerUp(e)
    {
        if (this.#dragged === null || e.pointerId !== this.#pointer_id) {
            return;
        }

        const was_dragging = this.#dragging;
        const dragged = this.#dragged;
        const intent = this.#intent;

        this.#suppress_next_click = was_dragging;
        this.#teardown();

        if (was_dragging && intent) {
            this.#commit(dragged, intent);
        }
    }

    /**
     * @param {PointerEvent} e
     */
    #onPointerCancel(e)
    {
        if (this.#dragged === null || e.pointerId !== this.#pointer_id) {
            return;
        }
        this.#cancel();
    }

    #cancel()
    {
        this.#suppress_next_click = false;
        this.#teardown();
    }

    #teardown()
    {
        if (this.#dragged?.hasPointerCapture(this.#pointer_id)) {
            this.#dragged.releasePointerCapture(this.#pointer_id);
        }

        this.#clearIntentMarks();
        this.#dragged?.removeAttribute('data-glpi-kb-drag-source');
        this.#ghost?.remove();
        this.#insert_line?.remove();
        document.body.classList.remove('kb-drag-active');
        document.body.classList.remove('kb-drag-refused');

        this.#ghost = null;
        this.#insert_line = null;
        this.#dragged = null;
        this.#pointer_id = null;
        this.#intent = null;
        this.#origin = null;
        this.#dragging = false;
        this.#autoscroll.stop();
        this.#forbidden_ids = new Set();
        this.#visible_lines = [];
    }

    /**
     * @param {HTMLElement} dragged
     * @param {{row: HTMLElement, mode: string, to_parent_id: number}} intent
     */
    async #commit(dragged, intent)
    {
        const id = dragged.dataset.glpiKbArticleId;
        const previous_parent_row = parentRowOf(dragged);
        const from_parent_id = Number(previous_parent_row?.dataset.glpiKbArticleId ?? 0);

        // Exact position to restore should the server refuse the move.
        const previous_list = dragged.parentElement;
        const previous_sibling = dragged.nextElementSibling;

        const new_parent_row = this.#newParentRowOf(intent);

        // Fold state to restore should the server refuse the move.
        const was_collapsed = new_parent_row
            ?.hasAttribute('data-glpi-kb-aside-category-collapsed') ?? false;

        this.#moveInDom(dragged, intent);
        this.#refreshNodeState(previous_parent_row);
        this.#refreshNodeState(new_parent_row);
        // Only `into` can land in a folded subtree; siblings target a visible level.
        if (intent.mode === 'into') {
            this.#setNodeCollapsed(new_parent_row, false);
        }

        try {
            await post(
                `Knowbase/Aside/Article/${encodeURIComponent(id)}/Move`,
                { from_parent_id, to_parent_id: intent.to_parent_id },
            );
        } catch {
            // `post()` already toasted: undo the move, restoring a dropped duplicate too.
            previous_list.insertBefore(dragged, previous_sibling);
            this.#refreshNodeState(new_parent_row);
            this.#refreshNodeState(previous_parent_row);
            if (intent.mode === 'into') {
                this.#setNodeCollapsed(new_parent_row, was_collapsed);
            }
        }
    }

    /**
     * The row that becomes the parent, null at the root level.
     *
     * @param {{row: HTMLElement, mode: string}} intent
     * @returns {HTMLElement|null}
     */
    #newParentRowOf(intent)
    {
        return intent.mode === 'into' ? intent.row : parentRowOf(intent.row);
    }

    /**
     * @param {HTMLElement} dragged
     * @param {{row: HTMLElement, mode: string}} intent
     */
    #moveInDom(dragged, intent)
    {
        const parent_row = this.#newParentRowOf(intent);

        // Never rendered twice at one place: drop the grabbed occurrence instead.
        if (this.#hasOtherOccurrence(dragged, parent_row)) {
            dragged.remove();
            return;
        }

        if (intent.mode === 'into') {
            this.#childListOf(intent.row).append(dragged);
            return;
        }

        intent.row.parentElement.insertBefore(
            dragged,
            intent.mode === 'before' ? intent.row : intent.row.nextElementSibling,
        );
    }

    /**
     * Whether the dragged article is already rendered under the destination
     * parent. At the root the whole tree is scanned: the builder only promotes
     * articles that have no remaining visible parent, so an occurrence left
     * anywhere else means this one simply disappears.
     *
     * @param {HTMLElement} dragged
     * @param {HTMLElement|null} parent_row
     * @returns {boolean}
     */
    #hasOtherOccurrence(dragged, parent_row)
    {
        const id = dragged.dataset.glpiKbArticleId;

        if (parent_row === null) {
            const rows = this.#tree.querySelectorAll('li[data-glpi-kb-article-id]');
            return [...rows].some(
                (row) => row.dataset.glpiKbArticleId === id && !dragged.contains(row),
            );
        }

        const list = parent_row.querySelector(':scope > ul');
        return [...(list?.children ?? [])].some(
            (row) => row.dataset.glpiKbArticleId === id && row !== dragged,
        );
    }

    /**
     * Fold or unfold a node, and persist it as a manual fold would. Dropping
     * onto a collapsed node has to unfold it, since its children are hidden and
     * the article would otherwise silently vanish; a rejected move folds it back.
     *
     * @param {HTMLElement|null} row
     * @param {boolean} collapsed
     */
    #setNodeCollapsed(row, collapsed)
    {
        if (!row || row.hasAttribute('data-glpi-kb-aside-category-collapsed') === collapsed) {
            return;
        }

        row.toggleAttribute('data-glpi-kb-aside-category-collapsed', collapsed);
        row.querySelector(':scope > .article-line [data-glpi-kb-aside-category-toggle]')
            ?.setAttribute('aria-expanded', collapsed ? 'false' : 'true');

        const id = row.dataset.glpiKbArticleId;
        if (!id) {
            return;
        }

        // Fire and forget: a lost fold state only means it renders collapsed on reload.
        post(`Knowbase/Aside/Article/${encodeURIComponent(id)}/Fold`, { collapsed })
            .catch(() => {});
    }

    /**
     * The `<ul>` holding a row's children, created when the row was a leaf.
     * A null row means the root list.
     *
     * @param {HTMLElement|null} row
     * @returns {HTMLElement}
     */
    #childListOf(row)
    {
        if (row === null) {
            return this.#root_list;
        }

        let list = row.querySelector(':scope > ul');
        if (!list) {
            list = document.createElement('ul');
            row.append(list);
        }
        return list;
    }

    /**
     * Re-apply the leaf/node contract after a row's children changed: a row
     * that lost its last child drops its fold toggle, one that gained its
     * first acquires it.
     *
     * The `<ul>` itself is never removed: when article creation is allowed it
     * is also where the "+" affordance writes (AsideController
     * `#openCreateInput`).
     *
     * @param {HTMLElement|null} row
     */
    #refreshNodeState(row)
    {
        if (!row || row === this.#root_list) {
            return;
        }

        const list = row.querySelector(':scope > ul');
        const has_children = list !== null && list.children.length > 0;
        const line = row.querySelector(':scope > .article-line');
        const toggle = line?.querySelector('[data-glpi-kb-aside-category-toggle]') ?? null;

        if (has_children && !toggle && line) {
            // Server-rendered nodes carry these; a leaf becoming one needs them too.
            row.setAttribute('data-glpi-kb-aside-category', '');
            row.setAttribute('role', 'group');
            // Names the group, and `AsideController#setCollapsed` reaches the
            // toggle only through the header attribute.
            row.setAttribute('aria-label', this.#titleOf(row));
            line.setAttribute('data-glpi-kb-aside-category-header', '');
            line.classList.add('mb-2');
            line.prepend(this.#buildFoldToggle(row));
        } else if (!has_children && toggle) {
            toggle.remove();
            row.removeAttribute('data-glpi-kb-aside-category-collapsed');
        }

        row.classList.toggle('node', has_children);
    }

    /**
     * The fold toggle as `aside.html.twig` renders it, so the handler in
     * AsideController keeps working on rows that just became parents.
     *
     * @param {HTMLElement} row
     * @returns {HTMLButtonElement}
     */
    #buildFoldToggle(row)
    {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'd-flex align-items-center p-0 border-0 bg-transparent';
        button.setAttribute('data-glpi-kb-aside-category-toggle', '');
        button.setAttribute('aria-label', this.#titleOf(row));
        button.setAttribute('aria-expanded', 'true');

        const icon = document.createElement('i');
        icon.className = 'ti ti-chevron-down me-1 fs-4';
        icon.setAttribute('aria-hidden', 'true');
        button.append(icon);

        return button;
    }
}
