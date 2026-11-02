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

import { HEADING_SELECTOR, OUTLINE_SELECTOR, annotateHeadings } from '/js/modules/Knowbase/HeadingAnchors.js';

/**
 * @param {string} text
 * @returns {string}
 */
function normalizeText(text)
{
    return text.replace(/\s+/g, ' ').trim();
}

/**
 * @typedef {Object} OutlineHeading
 * @property {string} id
 * @property {string} text
 * @property {number} depth 0 for the highest heading level used in the content.
 * @property {HTMLElement} element
 */

/**
 * Tracks the headings of the displayed article content, in view and edit
 * mode, and the one the reader is at.
 *
 * Events: `change` when the headings list changes, `active-change` when the
 * heading the reader is at changes.
 */
export class GlpiKnowbaseArticleOutline extends EventTarget
{
    /** @type {number} Distance below the top of the scroller where a heading becomes active. */
    static #ACTIVE_OFFSET = 80;

    /** @type {HTMLElement} */
    #content;

    /** @type {HTMLElement} */
    #article;

    /** @type {string|null} Set when the article is embedded in another page. */
    #article_url;

    /** @type {Object<string, string>} Heading text, by the id a link shared before may use. */
    #legacy_ids;

    /** @type {OutlineHeading[]} */
    #headings = [];

    /** @type {string|null} */
    #active_id = null;

    /** @type {MutationObserver|null} */
    #observer = null;

    /** @type {AbortController} Removes the page-level listeners once the article leaves the page. */
    #listeners = new AbortController();

    /** @type {number|null} */
    #scroll_frame = null;

    /**
     * Stays active until the scroller leaves its end: a short page cannot scroll it to the top.
     * @type {{id: string, reached_end: boolean}|null}
     */
    #picked = null;

    // The editor changes the DOM on each key press.
    #scheduleRefresh = _.debounce(() => this.#refresh(), 100);

    /**
     * @param {HTMLElement} content The element that holds the article HTML or the editor.
     * @param {HTMLElement} article The article element, which scrolls the content.
     * @param {{article_url?: string|null, legacy_ids?: Object<string, string>}} options
     */
    constructor(content, article, { article_url = null, legacy_ids = {} } = {})
    {
        super();
        this.#content = content;
        this.#article = article;
        this.#article_url = article_url;
        this.#legacy_ids = legacy_ids;
    }

    /**
     * Start to track the content. Call it only once the article HTML was read
     * for editing, as it adds ids and icons to the headings.
     */
    start()
    {
        this.#refresh();

        this.#observer = new MutationObserver(() => this.#scheduleRefresh());
        this.#observer.observe(this.#content, {
            childList: true,
            subtree: true,
            characterData: true,
        });

        // The scroller is `.kb-article`, or the window in a modal.
        const { signal } = this.#listeners;
        const on_scroll = () => this.#scheduleActiveUpdate();
        this.#article.addEventListener('scroll', on_scroll, { passive: true });
        window.addEventListener('scroll', on_scroll, { passive: true, signal });
        window.addEventListener('resize', on_scroll, { passive: true, signal });
        window.addEventListener('hashchange', () => this.#scrollToHash(true), { signal });

        this.#content.addEventListener('click', (e) => {
            const anchor = e.target.closest('[data-glpi-kb-heading-anchor]');
            if (anchor !== null) {
                e.preventDefault();
                const url = new URL(this.#article_url ?? window.location.href, window.location.href);
                url.hash = anchor.getAttribute('href');
                // Copied next, by the `data-glpi-clipboard-text` handler of common.js.
                anchor.dataset.glpiClipboardText = url.toString();
                this.#setHash(anchor.getAttribute('href'));
            }
        });

        if (this.#scrollToHash(false)) {
            // Else, on reload, the browser restores the previous position after this scroll.
            history.scrollRestoration = 'manual';
        }
    }

    /**
     * @returns {boolean} False once the article left the page, after the listeners were removed.
     */
    #isConnected()
    {
        if (this.#content.isConnected) {
            return true;
        }
        this.#listeners.abort();
        this.#observer?.disconnect();
        return false;
    }

    /** @returns {OutlineHeading[]} */
    getHeadings()
    {
        return this.#headings;
    }

    /** @returns {string|null} */
    getActiveId()
    {
        return this.#active_id;
    }

    /**
     * @param {string} id
     * @param {boolean} smooth
     */
    scrollTo(id, smooth = true)
    {
        // Not only the outline: a link can name a deeper heading.
        const element = [...this.#content.querySelectorAll(HEADING_SELECTOR)]
            .find((candidate) => candidate.id === id);
        if (element === undefined) {
            return;
        }
        const reduce_motion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const behavior = smooth && !reduce_motion ? 'smooth' : 'auto';

        // scrollIntoView() would also scroll the page around the article.
        if (this.#article.scrollHeight > this.#article.clientHeight) {
            const margin = parseFloat(getComputedStyle(element).scrollMarginTop) || 0;
            this.#article.scrollTo({
                top: this.#article.scrollTop
                    + element.getBoundingClientRect().top
                    - this.#article.getBoundingClientRect().top
                    - margin,
                behavior,
            });
        } else {
            element.scrollIntoView({ behavior, block: 'start' });
        }
        this.#setHash(`#${id}`);

        this.#picked = { id, reached_end: false };
        this.#updateActive();
    }

    /**
     * @param {string} hash
     */
    #setHash(hash)
    {
        // The URL of the page that embeds the article is not the article URL.
        if (this.#article_url === null) {
            history.replaceState(null, '', hash);
        }
    }

    /**
     * @param {boolean} smooth
     * @returns {boolean} Whether the hash named a heading.
     */
    #scrollToHash(smooth)
    {
        let id;
        try {
            id = decodeURIComponent(window.location.hash.slice(1));
        } catch {
            return false;
        }
        if (id === '' || !this.#isConnected()) {
            return false;
        }
        const elements = [...this.#content.querySelectorAll(HEADING_SELECTOR)]
            .filter((element) => element.id !== '');
        const legacy_text = Object.hasOwn(this.#legacy_ids, id) ? normalizeText(this.#legacy_ids[id]) : null;
        const element = elements.find((candidate) => candidate.id === id)
            ?? elements.find((candidate) => normalizeText(candidate.textContent) === legacy_text);
        if (element === undefined) {
            return false;
        }
        this.scrollTo(element.id, smooth);
        return true;
    }

    #refresh()
    {
        if (!this.#isConnected()) {
            return;
        }
        // The editor gives the ids itself, with decorations. A diff is not the article.
        const is_diff = this.#article.classList.contains('kb-article--diff-mode');
        if (this.#content.querySelector('.ProseMirror') === null && !is_diff) {
            annotateHeadings(this.#content);
            // Our own changes must not schedule a new refresh.
            this.#observer?.takeRecords();
        }

        const elements = [...this.#content.querySelectorAll(OUTLINE_SELECTOR)]
            .filter((element) => element.id !== '');
        const min_level = Math.min(...elements.map((element) => Number(element.tagName[1])));
        const headings = elements.map((element) => ({
            id: element.id,
            text: element.textContent.trim(),
            depth: Number(element.tagName[1]) - min_level,
            element: element,
        }));

        const changed = headings.length !== this.#headings.length
            || headings.some((heading, i) => {
                const previous = this.#headings[i];
                return heading.id !== previous.id
                    || heading.text !== previous.text
                    || heading.depth !== previous.depth;
            });

        // Elements can be replaced while the list stays the same.
        this.#headings = headings;
        if (changed) {
            this.dispatchEvent(new Event('change'));
        }
        this.#updateActive();
    }

    #scheduleActiveUpdate()
    {
        if (this.#scroll_frame !== null || this.#headings.length === 0 || !this.#isConnected()) {
            return;
        }
        this.#scroll_frame = requestAnimationFrame(() => {
            this.#scroll_frame = null;
            this.#updateActive();
        });
    }

    #updateActive()
    {
        // `.kb-article` does not scroll in a modal: the viewport top applies then.
        const top = Math.max(0, this.#article.getBoundingClientRect().top)
            + GlpiKnowbaseArticleOutline.#ACTIVE_OFFSET;

        let active_id = null;
        for (const heading of this.#headings) {
            if (heading.element.getBoundingClientRect().top > top) {
                break;
            }
            active_id = heading.id;
        }
        if (this.#picked !== null) {
            const scroller = this.#article.scrollHeight > this.#article.clientHeight
                ? this.#article
                : document.scrollingElement;
            const at_end = scroller.scrollTop + scroller.clientHeight >= scroller.scrollHeight - 1;
            const left_end = this.#picked.reached_end && !at_end;
            const exists = this.#headings.some((heading) => heading.id === this.#picked.id);
            if (active_id === this.#picked.id || left_end || !exists) {
                this.#picked = null;
            } else {
                this.#picked.reached_end ||= at_end;
                active_id = this.#picked.id;
            }
        }

        if (active_id !== this.#active_id) {
            this.#active_id = active_id;
            this.dispatchEvent(new Event('active-change'));
        }
    }
}
