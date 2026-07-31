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

    /** Distance to the scroll container edge that triggers autoscroll. */
    static #AUTOSCROLL_EDGE_PX = 40;

    /** Pixels scrolled per frame while autoscrolling. */
    static #AUTOSCROLL_STEP_PX = 8;

    /** @type {HTMLElement|null} */
    #tree = null;

    /** @type {HTMLElement|null} */
    #root_list = null;

    /** @type {HTMLElement|null} The `<li>` being dragged. */
    #dragged = null;

    /** @type {HTMLElement|null} Floating label following the pointer. */
    #ghost = null;

    /**
     * Current drop target: an article `<li>`, or the root `<ul>` for a drop at
     * the root level. Null while the pointer is over an invalid target.
     * @type {HTMLElement|null}
     */
    #target = null;

    /**
     * Article ids of the dragged subtree. Dropping onto any of them creates a
     * cycle, including onto another occurrence of the same article elsewhere.
     * @type {Set<string>}
     */
    #forbidden_ids = new Set();

    /** @type {{ x: number, y: number }|null} */
    #origin = null;

    /** @type {boolean} */
    #armed = false;

    /** @type {boolean} */
    #dragging = false;

    /** @type {boolean} */
    #suppress_next_click = false;

    /** @type {number|null} */
    #autoscroll_frame = null;

    /** @type {number} -1 up, 1 down, 0 idle. */
    #autoscroll_direction = 0;

    /** @type {Element|null} Element actually scrolled while autoscrolling. */
    #scroll_container = null;

    /** @type {boolean} Whether that element has anything to scroll at all. */
    #autoscroll_enabled = false;

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
        this.#tree.addEventListener('pointermove', (e) => this.#onPointerMove(e));
        this.#tree.addEventListener('pointerup', (e) => this.#onPointerUp(e));
        this.#tree.addEventListener('pointercancel', () => this.#cancel());

        // Rows are grabbed by their link, and anchors are natively draggable:
        // letting the browser start its own drag would cancel our pointer flow.
        this.#tree.addEventListener('dragstart', (e) => e.preventDefault());

        // Swallow the click that ends a real drag, or the article link would
        // be followed on drop.
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
        // Touch and pen are out of scope: arming them needs `touch-action:
        // none`, which would kill scrolling of the aside.
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

        // Suppress native drag/text-selection here, before the browser's own
        // drag-threshold can beat our pointermove-based one to it.
        e.preventDefault();

        this.#dragged = row;
        this.#origin = { x: e.clientX, y: e.clientY };
        this.#armed = true;
        row.setPointerCapture(e.pointerId);
    }

    /**
     * @param {PointerEvent} e
     */
    #onPointerMove(e)
    {
        if (!this.#armed) {
            return;
        }

        if (!this.#dragging) {
            const distance = Math.hypot(
                e.clientX - this.#origin.x,
                e.clientY - this.#origin.y,
            );
            if (distance < GlpiKnowbaseAsideDragController.#THRESHOLD_PX) {
                return;
            }
            this.#startDrag();
        }

        this.#ghost.style.transform = `translate(${e.clientX + 8}px, ${e.clientY + 8}px)`;
        this.#updateTarget(e.clientX, e.clientY);
        this.#updateAutoscroll(e.clientY);
    }

    #startDrag()
    {
        this.#dragging = true;
        this.#forbidden_ids = this.#collectSubtreeIds(this.#dragged);
        this.#scroll_container = this.#resolveScrollContainer();
        // Nothing to scroll: keep autoscroll idle instead of scrolling nowhere.
        this.#autoscroll_enabled =
            this.#scroll_container.scrollHeight > this.#scroll_container.clientHeight;
        this.#dragged.setAttribute('data-glpi-kb-drag-source', '');
        document.body.classList.add('kb-drag-active');

        this.#ghost = document.createElement('div');
        this.#ghost.className = 'kb-drag-ghost';
        this.#ghost.textContent = this.#titleOf(this.#dragged);
        document.body.append(this.#ghost);
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
     * @param {HTMLElement} row
     * @returns {Set<string>}
     */
    #collectSubtreeIds(row)
    {
        const ids = new Set([row.dataset.glpiKbArticleId]);
        for (const descendant of row.querySelectorAll('li[data-glpi-kb-article-id]')) {
            ids.add(descendant.dataset.glpiKbArticleId);
        }
        return ids;
    }

    /**
     * @param {number} x
     * @param {number} y
     */
    #updateTarget(x, y)
    {
        this.#clearTargetMark();
        this.#target = this.#resolveTarget(x, y);
        this.#target?.setAttribute('data-glpi-kb-drop-target', '');
    }

    /**
     * @param {number} x
     * @param {number} y
     * @returns {HTMLElement|null}
     */
    #resolveTarget(x, y)
    {
        const element = document.elementFromPoint(x, y);
        if (!element || !this.#tree.contains(element)) {
            return null;
        }

        const row = element.closest('li[data-glpi-kb-article-id]');

        // Inside the tree but on no row: the root drop zone.
        if (!row) {
            return this.#parentRowOf(this.#dragged) === null ? null : this.#root_list;
        }

        // Self or any article of the dragged subtree would create a cycle.
        if (this.#forbidden_ids.has(row.dataset.glpiKbArticleId)) {
            return null;
        }

        // Dropping back onto the parent we were grabbed from changes nothing.
        // Compared by id, since that parent may be rendered several times.
        const parent_id = this.#parentRowOf(this.#dragged)?.dataset.glpiKbArticleId ?? null;
        return row.dataset.glpiKbArticleId === parent_id ? null : row;
    }

    /**
     * The `<li>` of the parent of an occurrence, null when it sits at the root.
     *
     * @param {HTMLElement} row
     * @returns {HTMLElement|null}
     */
    #parentRowOf(row)
    {
        return row.parentElement?.closest('li[data-glpi-kb-article-id]') ?? null;
    }

    #clearTargetMark()
    {
        for (const marked of this.#tree.querySelectorAll('[data-glpi-kb-drop-target]')) {
            marked.removeAttribute('data-glpi-kb-drop-target');
        }
    }

    /**
     * @param {number} y
     */
    #updateAutoscroll(y)
    {
        if (!this.#autoscroll_enabled) {
            return;
        }

        // Measured on the scrolled element, not on the tree: "near the edge"
        // has to mean the edge of whatever the next step will actually scroll.
        const bounds = this.#scrollBounds();
        const edge = GlpiKnowbaseAsideDragController.#AUTOSCROLL_EDGE_PX;

        if (y < bounds.top + edge) {
            this.#autoscroll_direction = -1;
        } else if (y > bounds.bottom - edge) {
            this.#autoscroll_direction = 1;
        } else {
            this.#autoscroll_direction = 0;
        }

        if (this.#autoscroll_direction !== 0 && this.#autoscroll_frame === null) {
            this.#autoscroll_frame = window.requestAnimationFrame(() => this.#autoscrollStep());
        }
    }

    #autoscrollStep()
    {
        this.#autoscroll_frame = null;
        if (!this.#dragging || this.#autoscroll_direction === 0) {
            return;
        }
        this.#scroll_container.scrollTop += this.#autoscroll_direction
            * GlpiKnowbaseAsideDragController.#AUTOSCROLL_STEP_PX;
        this.#autoscroll_frame = window.requestAnimationFrame(() => this.#autoscrollStep());
    }

    /**
     * Nearest scrollable ancestor of the tree: depending on the layout, the
     * tree container itself is not always the element that scrolls.
     *
     * @returns {Element}
     */
    #resolveScrollContainer()
    {
        for (let node = this.#tree; node instanceof HTMLElement; node = node.parentElement) {
            const overflow = window.getComputedStyle(node).overflowY;
            if (
                node.scrollHeight > node.clientHeight
                && (overflow === 'auto' || overflow === 'scroll')
            ) {
                return node;
            }
        }
        return document.scrollingElement ?? document.documentElement;
    }

    /**
     * Viewport-space top and bottom edges of the scrolled element. The page
     * scroller has no meaningful rect of its own: its edges are the viewport's.
     *
     * @returns {{ top: number, bottom: number }}
     */
    #scrollBounds()
    {
        const page_scroller = document.scrollingElement ?? document.documentElement;
        if (this.#scroll_container === page_scroller || this.#scroll_container === document.body) {
            return { top: 0, bottom: window.innerHeight };
        }

        const rect = this.#scroll_container.getBoundingClientRect();
        return { top: rect.top, bottom: rect.bottom };
    }

    /**
     * @param {PointerEvent} e
     */
    #onPointerUp(e)
    {
        if (!this.#armed) {
            return;
        }

        const was_dragging = this.#dragging;
        const dragged = this.#dragged;
        const target = this.#target;

        this.#suppress_next_click = was_dragging;
        this.#teardown(e.pointerId);

        if (was_dragging && target) {
            this.#commit(dragged, target);
        }
    }

    #cancel()
    {
        this.#suppress_next_click = false;
        this.#teardown(null);
    }

    /**
     * @param {number|null} pointer_id
     */
    #teardown(pointer_id)
    {
        if (pointer_id !== null && this.#dragged?.hasPointerCapture(pointer_id)) {
            this.#dragged.releasePointerCapture(pointer_id);
        }

        this.#clearTargetMark();
        this.#dragged?.removeAttribute('data-glpi-kb-drag-source');
        this.#ghost?.remove();
        document.body.classList.remove('kb-drag-active');

        this.#ghost = null;
        this.#dragged = null;
        this.#target = null;
        this.#origin = null;
        this.#armed = false;
        this.#dragging = false;
        this.#autoscroll_direction = 0;
        this.#scroll_container = null;
        this.#autoscroll_enabled = false;
        this.#forbidden_ids = new Set();
    }

    /**
     * @param {HTMLElement} dragged
     * @param {HTMLElement} target an article `<li>`, or the root `<ul>`
     */
    async #commit(dragged, target)
    {
        const id = dragged.dataset.glpiKbArticleId;
        const previous_parent_row = this.#parentRowOf(dragged);
        const from_parent_id = Number(previous_parent_row?.dataset.glpiKbArticleId ?? 0);
        const to_parent_id = target === this.#root_list
            ? 0
            : Number(target.dataset.glpiKbArticleId);

        // Exact position to restore should the server refuse the move.
        const previous_list = dragged.parentElement;
        const previous_sibling = dragged.nextElementSibling;

        // The destination row, which is also the new parent unless we dropped
        // at the root. Read from the target rather than from the moved row,
        // which may have been removed rather than moved.
        const new_parent_row = target === this.#root_list ? null : target;

        // Fold state to restore should the server refuse the move.
        const was_collapsed = new_parent_row
            ?.hasAttribute('data-glpi-kb-aside-category-collapsed') ?? false;

        this.#moveInDom(dragged, target);
        this.#refreshNodeState(previous_parent_row);
        this.#refreshNodeState(new_parent_row);
        this.#setNodeCollapsed(new_parent_row, false);

        try {
            await post(
                `Knowbase/Aside/Article/${encodeURIComponent(id)}/Move`,
                { from_parent_id, to_parent_id },
            );
        } catch {
            // `post()` already raised an error toast. Undo the optimistic move
            // so the tree never shows a parent the server rejected. This also
            // puts back a row that was removed as a duplicate occurrence.
            previous_list.insertBefore(dragged, previous_sibling);
            this.#refreshNodeState(new_parent_row);
            this.#refreshNodeState(previous_parent_row);
            this.#setNodeCollapsed(new_parent_row, was_collapsed);
        }
    }

    /**
     * @param {HTMLElement} dragged
     * @param {HTMLElement} target
     */
    #moveInDom(dragged, target)
    {
        // Duplicate occurrence rule: an article is never rendered twice at the
        // same place, so a drop that lands where it already shows only drops
        // the grabbed occurrence. This covers the backend merge (the target
        // edge already exists) and the root drop of a multi-parent article,
        // which stays visible under its other parents instead of moving up.
        if (this.#hasOtherOccurrence(dragged, target)) {
            dragged.remove();
            return;
        }

        const list = target === this.#root_list ? this.#root_list : this.#childListOf(target);
        list.append(dragged);
    }

    /**
     * Whether the dragged article is already rendered at the destination: as a
     * child of the target row, or anywhere else in the tree for a root drop.
     *
     * @param {HTMLElement} dragged
     * @param {HTMLElement} target
     * @returns {boolean}
     */
    #hasOtherOccurrence(dragged, target)
    {
        const id = dragged.dataset.glpiKbArticleId;

        if (target === this.#root_list) {
            const rows = this.#tree.querySelectorAll('li[data-glpi-kb-article-id]');
            return [...rows].some(
                (row) => row.dataset.glpiKbArticleId === id && !dragged.contains(row),
            );
        }

        const list = target.querySelector(':scope > ul');
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

        // Fire and forget, like AsideController `#persistArticleFold`: a lost
        // fold state only means the node renders collapsed again on reload.
        post(`Knowbase/Aside/Article/${encodeURIComponent(id)}/Fold`, { collapsed })
            .catch(() => {});
    }

    /**
     * The `<ul>` holding a row's children, created when the row was a leaf.
     *
     * @param {HTMLElement} row
     * @returns {HTMLElement}
     */
    #childListOf(row)
    {
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
