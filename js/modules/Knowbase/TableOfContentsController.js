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

/* global bootstrap, _ */

import { EditorActionType } from "/js/modules/Knowbase/EditorActions.js";

/**
 * The article "Contents" column, its toggle button in the meta row, its
 * kebab switch and its keyboard shortcut. Without space for the column, the
 * button opens the list in a dropdown instead.
 */
export class GlpiKnowbaseTableOfContentsController
{
    static #STORAGE_KEY = 'glpi_kb_toc_visible';

    /**
     * @type {number} Below this body width, in rem, the column does not show:
     * 37.5rem of text plus the 18rem of `.kb-toc-column` and its gap. Low
     * enough for the fixed-width helpdesk and public FAQ layouts.
     */
    static #MIN_BODY_WIDTH = 55.5;

    /** @type {HTMLElement} */
    #container;

    /** @type {import('/js/modules/Knowbase/ArticleOutline.js').GlpiKnowbaseArticleOutline} */
    #outline;

    /** @type {HTMLElement} */
    #column;

    /** @type {HTMLElement} */
    #toggle_container;

    /** @type {HTMLButtonElement} */
    #button;

    /** @type {boolean} */
    #is_visible;

    /** @type {boolean} */
    #is_narrow = false;

    /** @type {AbortController} Removes the page-level listener once the article leaves the page. */
    #listeners = new AbortController();

    /**
     * @param {HTMLElement} container The article container.
     * @param {import('/js/modules/Knowbase/ArticleOutline.js').GlpiKnowbaseArticleOutline} outline
     */
    constructor(container, outline)
    {
        this.#container = container;
        this.#outline = outline;
        this.#column = container.querySelector('[data-glpi-kb-toc-column]');
        this.#toggle_container = container.querySelector('[data-glpi-kb-toc-toggle-container]');
        this.#button = container.querySelector('[data-glpi-kb-toc-toggle]');
        this.#is_visible = GlpiKnowbaseTableOfContentsController.#readPreference();

        this.#outline.addEventListener('change', () => {
            this.#render();
            this.#update();
        });
        this.#outline.addEventListener('active-change', () => this.#syncActive());

        this.#button.addEventListener('click', () => {
            bootstrap.Tooltip.getInstance(this.#toggle_container)?.hide();
            // When narrow, Bootstrap opens the dropdown from `data-bs-toggle`.
            if (!this.#is_narrow) {
                this.toggle();
            }
        });
        // The column replaces the dropdown when there is space for it.
        this.#button.addEventListener('show.bs.dropdown', (e) => {
            if (!this.#is_narrow) {
                e.preventDefault();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (!container.isConnected) {
                this.#listeners.abort();
                return;
            }
            // `code`, not `key`: with Alt, the produced character depends on the layout.
            if (e.ctrlKey && e.altKey && !e.shiftKey && !e.metaKey && e.code === 'KeyH') {
                e.preventDefault();
                this.#onShortcut();
            }
        }, { signal: this.#listeners.signal });

        this.#container.addEventListener('click', (e) => {
            const link = e.target.closest('[data-glpi-kb-toc-link]');
            if (link === null) {
                return;
            }
            e.preventDefault();
            this.#outline.scrollTo(link.dataset.glpiKbTocLink);
            if (link.closest('.dropdown-menu') !== null) {
                bootstrap.Dropdown.getInstance(this.#button)?.hide();
            }
        });

        const body = container.querySelector('[data-glpi-kb-article-body]');
        const resize_observer = new ResizeObserver(() => {
            const rem = parseFloat(getComputedStyle(document.documentElement).fontSize);
            const is_narrow = body.clientWidth < GlpiKnowbaseTableOfContentsController.#MIN_BODY_WIDTH * rem;
            if (is_narrow !== this.#is_narrow) {
                this.#is_narrow = is_narrow;
                this.#update();
            }
        });
        resize_observer.observe(body);
        this.#listeners.signal.addEventListener('abort', () => resize_observer.disconnect());

        // Bootstrap allows one component per element and the button holds the
        // dropdown: the tooltip goes on the wrapper, driven by the button only.
        const tooltip = new bootstrap.Tooltip(this.#toggle_container, {
            html: true,
            allowList: { ...bootstrap.Tooltip.Default.allowList, kbd: [] },
            customClass: 'kb-toc-tooltip',
            placement: 'bottom',
            trigger: 'manual',
            title: () => this.#getTooltip(),
        });
        for (const type of ['mouseenter', 'focus']) {
            this.#button.addEventListener(type, () => {
                if (this.#button.getAttribute('aria-expanded') !== 'true') {
                    tooltip.show();
                }
            });
        }
        for (const type of ['mouseleave', 'blur']) {
            this.#button.addEventListener(type, () => tooltip.hide());
        }

        this.#render();
        this.#update();
    }

    toggle()
    {
        this.#setVisible(!this.#is_visible);
    }

    /**
     * @param {boolean} is_visible
     */
    #setVisible(is_visible)
    {
        this.#is_visible = is_visible;
        try {
            localStorage.setItem(GlpiKnowbaseTableOfContentsController.#STORAGE_KEY, is_visible ? '1' : '0');
        } catch {
            // Storage unavailable: the choice lasts until the page reloads.
        }
        this.#update();
    }

    /** @returns {boolean} */
    static #readPreference()
    {
        try {
            return localStorage.getItem(GlpiKnowbaseTableOfContentsController.#STORAGE_KEY) === '1';
        } catch {
            return false;
        }
    }

    #onShortcut()
    {
        if (!this.#is_narrow) {
            this.toggle();
        } else if (this.#outline.getHeadings().length > 0) {
            bootstrap.Dropdown.getOrCreateInstance(this.#button).toggle();
        }
    }

    /** @returns {string} */
    #getLabel()
    {
        return this.#is_visible && !this.#is_narrow ? __('Hide contents') : __('Show contents');
    }

    /** @returns {string} */
    #getTooltip()
    {
        const is_mac = /Mac|iPhone|iPad/.test(navigator.platform);
        const keys = is_mac ? ['⌃', '⌥', 'H'] : ['Ctrl', 'Alt', 'H'];
        return `${_.escape(this.#getLabel())} ${keys.map((key) => `<kbd>${key}</kbd>`).join('+')}`;
    }

    #update()
    {
        const has_headings = this.#outline.getHeadings().length > 0;

        this.#toggle_container.classList.toggle('d-none', !has_headings);

        // The kebab switch can show the empty column, so headings added in edit mode appear in it.
        this.#column.classList.toggle('d-none', !this.#is_visible || this.#is_narrow);
        this.#column.querySelector('[data-glpi-kb-toc-empty]')
            .classList.toggle('d-none', has_headings);

        if (this.#is_narrow) {
            this.#button.removeAttribute('aria-pressed');
        } else {
            bootstrap.Dropdown.getInstance(this.#button)?.hide();
            this.#button.setAttribute('aria-pressed', this.#is_visible ? 'true' : 'false');
        }
        this.#button.setAttribute('aria-label', this.#getLabel());
        bootstrap.Tooltip.getInstance(this.#toggle_container)?.setContent({ '.tooltip-inner': this.#getTooltip() });

        const selector = `[data-glpi-kb-action="${EditorActionType.TOGGLE_TABLE_OF_CONTENTS}"] input[type="checkbox"]`;
        for (const checkbox of this.#container.querySelectorAll(selector)) {
            checkbox.checked = this.#is_visible;
        }
    }

    #render()
    {
        const headings = this.#outline.getHeadings();
        for (const list of this.#container.querySelectorAll('[data-glpi-kb-toc-list]')) {
            list.replaceChildren(...headings.map((heading) => {
                const link = document.createElement('a');
                link.href = `#${heading.id}`;
                link.className = 'kb-toc-link';
                link.style.setProperty('--kb-toc-depth', String(heading.depth));
                link.dataset.glpiKbTocLink = heading.id;
                link.textContent = heading.text;

                const item = document.createElement('li');
                item.append(link);
                return item;
            }));
        }
        this.#syncActive();
    }

    #syncActive()
    {
        const active_id = this.#outline.getActiveId();
        for (const link of this.#container.querySelectorAll('[data-glpi-kb-toc-link]')) {
            if (link.dataset.glpiKbTocLink === active_id) {
                link.setAttribute('aria-current', 'location');
            } else {
                link.removeAttribute('aria-current');
            }
        }
    }
}
