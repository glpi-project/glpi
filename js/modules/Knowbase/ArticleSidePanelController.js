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
     * @type {HTMLElement}
     */
    #container;

    /**
     * @type {HTMLElement}
     */
    #article;

    constructor(container, article)
    {
        this.#container = container;
        this.#article = article;
        this.#initEventListeners();

        new GlpiKnowbaseCommentsPanelController(this.#container);
        new GlpiKnowbaseServiceCatalogPanelController(this.#container);
        new GlpiKnowbaseRevisionsPanelController(this.#container);
    }

    #initEventListeners()
    {
        this.#container.addEventListener('click', (e) => {
            if (e.target.closest('[data-glpi-knowbase-side-panel-close]')) {
                this.#close();
            }
        });
    }

    #open()
    {
        this.#container.classList.remove('closed');
        this.#article.classList.remove('col-12');
        this.#article.classList.add('col-9');
    }

    #close()
    {
        this.#container.classList.add('closed');
        this.#article.classList.remove('col-9');
        this.#article.classList.add('col-12');
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

        // jQuery's .html() trigger scripts execution, which is needed for select2 and tinymce
        $(this.#container).html(await response.text());
        this.#open();

        // Trigger bootstrap tooltips
        new bootstrap.Tooltip(this.#container, {
            selector: "[data-bs-toggle='tooltip']"
        });
    }
}
