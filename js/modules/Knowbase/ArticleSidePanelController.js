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

    #open()
    {
        if (this.#isSmallScreen()) {
            const offcanvas = bootstrap.Offcanvas.getOrCreateInstance(this.#offcanvas_container);
            offcanvas.show();
        } else {
            this.#sidepanel_container.classList.remove('closed');
            this.#article.classList.remove('col-12');
            this.#article.classList.add('col-9');
        }
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
        this.#open();

        // Trigger bootstrap tooltips
        new bootstrap.Tooltip(target, {
            selector: "[data-bs-toggle='tooltip']"
        });
    }
}
