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

/* global bootstrap */

import { GlpiKnowbaseCommentsPanelController } from "/js/modules/Knowbase/CommentsPanelController.js";
import { GlpiKnowbaseServiceCatalogPanelController } from "/js/modules/Knowbase/ServiceCatalogPanelController.js";
import { GlpiKnowbaseRevisionsPanelController } from "/js/modules/Knowbase/RevisionsPanelController.js";

export class GlpiKnowbaseArticleSidePanelController
{
    /**
     * @type {number}
     */
    static #OFFCANVAS_BREAKPOINT = 1500;

    /**
     * @type {number} Fallback delay, above the 0.35s CSS transition.
     */
    static #OPEN_TIMEOUT = 500;

    /**
     * @type {HTMLElement}
     */
    #sidepanel_container;

    /**
     * @type {HTMLElement}
     */
    #offcanvas_container;

    /**
     * @type {HTMLElement}
     */
    #article;

    /**
     * @param {HTMLElement} container
     * @param {HTMLElement} offcanvas_container
     * @param {HTMLElement} article
     */
    constructor(sidepanel_container, offcanvas_container, article)
    {
        this.#sidepanel_container = sidepanel_container;
        this.#offcanvas_container = offcanvas_container;
        this.#article = article;
        this.#initEventListeners();

        // For each handlers, we define two instances that will each handle
        // a possible container as the content can be displayed in two possible
        // area depending on the screen size.
        for (const container of [this.#sidepanel_container, this.#offcanvas_container]) {
            new GlpiKnowbaseCommentsPanelController(container);
            new GlpiKnowbaseServiceCatalogPanelController(container);
            new GlpiKnowbaseRevisionsPanelController(container);
        }
    }

    /**
     * @returns {boolean}
     */
    #isSmallScreen()
    {
        return window.innerWidth < GlpiKnowbaseArticleSidePanelController.#OFFCANVAS_BREAKPOINT;
    }

    #initEventListeners()
    {
        this.#sidepanel_container.addEventListener('click', (e) => {
            if (e.target.closest('[data-glpi-knowbase-side-panel-close]')) {
                this.#close();
            }
        });

        this.#offcanvas_container.addEventListener('click', (e) => {
            if (e.target.closest('[data-glpi-knowbase-side-panel-close]')) {
                this.#close();
            }
        });
    }

    /**
     * @returns {Promise<void>} Resolves once the panel finished opening, so
     * callers measure a settled layout instead of an animating one.
     */
    #open()
    {
        if (this.#isSmallScreen()) {
            if (this.#offcanvas_container.classList.contains('show')) {
                return Promise.resolve();
            }
            const shown = new Promise((resolve) => {
                this.#offcanvas_container.addEventListener(
                    'shown.bs.offcanvas',
                    () => resolve(),
                    { once: true }
                );
            });
            bootstrap.Offcanvas.getOrCreateInstance(this.#offcanvas_container).show();
            return shown;
        }

        if (!this.#sidepanel_container.classList.contains('closed')) {
            return Promise.resolve();
        }

        const opened = GlpiKnowbaseArticleSidePanelController.#whenWidthSettles(
            this.#sidepanel_container
        );
        this.#sidepanel_container.classList.remove('closed');
        this.#article.classList.remove('col-12');
        this.#article.classList.add('col-9');

        return opened;
    }

    /**
     * Resolve when `element`'s width transition ends, or on timeout when none
     * runs (reduced motion, no transition...).
     * @param {HTMLElement} element
     * @returns {Promise<void>}
     */
    static #whenWidthSettles(element)
    {
        return new Promise((resolve) => {
            let timer = null;
            const done = () => {
                clearTimeout(timer);
                element.removeEventListener('transitionend', onEnd);
                resolve();
            };
            const onEnd = (e) => {
                if (e.target === element && e.propertyName === 'width') {
                    done();
                }
            };
            timer = setTimeout(done, GlpiKnowbaseArticleSidePanelController.#OPEN_TIMEOUT);
            element.addEventListener('transitionend', onEnd);
        });
    }

    #close()
    {
        if (this.#isSmallScreen()) {
            const offcanvas = bootstrap.Offcanvas.getInstance(this.#offcanvas_container);
            if (offcanvas) {
                offcanvas.hide();
            }
        } else {
            this.#sidepanel_container.classList.add('closed');
            this.#article.classList.remove('col-9');
            this.#article.classList.add('col-12');
        }
    }

    /**
     * @param {number} id
     * @param {string} key
     */
    async load(id, key)
    {
        const base_url = CFG_GLPI.root_doc;
        const url = `${base_url}/Knowbase/${id}/SidePanel/${key}`;
        const response = await fetch(url);

        if (!response.ok) {
            throw new Error("Failed to load side panel content.");
        }

        const html = await response.text();
        const target = this.#isSmallScreen()
            ? this.#offcanvas_container.querySelector('.offcanvas-body')
            : this.#sidepanel_container;

        // jQuery's .html() trigger scripts execution, which is needed for select2 and tinymce
        $(target).html(html);
        const opened = this.#open();

        // Notify controllers that new content was loaded
        target.dispatchEvent(new CustomEvent('glpi:kb:panel-loaded', {
            bubbles: true,
        }));

        // Trigger bootstrap tooltips
        new bootstrap.Tooltip(target, {
            selector: "[data-bs-toggle='tooltip']"
        });

        // Callers measuring the panel (scrollToComment...) need the open
        // animation done, so only resolve once the layout settled.
        await opened;
    }

    /**
     * Forward a pending anchor to the active Comments panel only — the
     * inactive container has no textarea, so dispatching to it would throw.
     * @param {{prefix: string, exact: string, suffix: string, occurrence: number}} anchor
     */
    setPendingCommentAnchor(anchor)
    {
        const target = this.#isSmallScreen() ? this.#offcanvas_container : this.#sidepanel_container;
        target.dispatchEvent(new CustomEvent('glpi:kb:set-pending-comment-anchor', {
            detail: { anchor },
        }));
    }

    /**
     * Hide the quoted passage of every thread whose anchor no longer resolves in
     * the article, so a deleted passage stops being cited.
     * @param {string[]} resolved_ids - Root comment ids whose anchor still resolves.
     */
    setResolvedCommentAnchors(resolved_ids)
    {
        const target = this.#isSmallScreen()
            ? this.#offcanvas_container.querySelector('.offcanvas-body')
            : this.#sidepanel_container;

        target?.querySelectorAll('[data-glpi-comment-thread]').forEach((thread) => {
            const quote = thread.querySelector('[data-glpi-comment-anchor-quote]');
            quote?.classList.toggle(
                'd-none',
                !resolved_ids.includes(thread.dataset.glpiCommentThread)
            );
        });
    }

    /**
     * Scroll the currently visible panel to the thread matching `comment_id`
     * (its root comment's id) and briefly highlight it.
     * @param {number|string} comment_id
     */
    scrollToComment(comment_id)
    {
        const target = this.#isSmallScreen()
            ? this.#offcanvas_container.querySelector('.offcanvas-body')
            : this.#sidepanel_container;
        const thread = target?.querySelector(`[data-glpi-comment-thread="${CSS.escape(String(comment_id))}"]`);
        if (!thread) {
            return;
        }

        thread.scrollIntoView({ behavior: 'smooth', block: 'center' });
        thread.classList.add('kb-comment-thread--focused');
        setTimeout(() => thread.classList.remove('kb-comment-thread--focused'), 2000);
    }
}
