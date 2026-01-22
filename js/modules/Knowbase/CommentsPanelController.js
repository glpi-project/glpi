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

/* global glpi_toast_error, getAjaxCsrfToken */

const content_selector  = "[data-glpi-add-comments-content]";
const submit_selector   = "[data-glpi-add-comments-submit]";
const kb_id_selector    = "[data-glpi-kb-id]";
const comments_selector = "[data-glpi-comments]";

export class GlpiKnowbaseCommentsPanelController
{
    /**
     * @type {HTMLElement}
     */
    #container;

    constructor(container)
    {
        this.#container = container;
        this.#initEventListeners();
    }

    #initEventListeners()
    {
        this.#container.addEventListener('input', (e) => {
            // Show submit button when typing a new comment
            const textarea = e.target.closest(content_selector);
            if (textarea) {
                this.#toggleSubmitButtonVisibility(textarea);
            }
        });

        this.#container.addEventListener('click', (e) => {
            // Submit new comment
            const submit = e.target.closest(submit_selector);
            if (submit) {
                this.#submitComment();
            }
        });
    }

    #toggleSubmitButtonVisibility(textarea)
    {
        if (textarea.value !== '') {
            this.#getSubmitButton().classList.remove('d-none');
        } else {
            this.#getSubmitButton().classList.add('d-none');
        }
    }

    async #submitComment()
    {
        // Show loading state
        this.#getSubmitButton().classList.add('pointer-events-none');
        this.#getSubmitButton()
            .querySelector('[data-glpi-loading]')
            .classList
            .remove('d-none')
        ;
        this.#getSubmitButton()
            .querySelector('[data-glpi-icon]')
            .classList
            .add('d-none')
        ;

        const data = new FormData();
        data.append('content', this.#getContentTextarea().value);

        const base_url = CFG_GLPI.root_doc;
        const url = `${base_url}/Knowbase/${this.#getKbId()}/AddComment`;
        const response = await fetch(url, {
            method: "POST",
            body: data,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-Glpi-Csrf-Token': getAjaxCsrfToken(),
            }
        });

        if (!response.ok) {
            glpi_toast_error(__("An unexpected error occurred."));
            return;
        }

        // Insert new comment
        const html = await response.text();
        this.#getCommentsDiv().insertAdjacentHTML('beforeend', html);

        // Clear input/UI
        this.#getContentTextarea().value = "";
        this.#getSubmitButton().classList.remove('pointer-events-none');
        this.#getSubmitButton().classList.add('d-none');
        this.#getSubmitButton()
            .querySelector('[data-glpi-icon]')
            .classList
            .remove('d-none')
        ;
        this.#getSubmitButton()
            .querySelector('[data-glpi-loading]')
            .classList
            .add('d-none')
        ;
        this.#getCommentsDiv().lastElementChild.scrollIntoView();
    }

    #getContentTextarea()
    {
        return this.#container.querySelector(content_selector);
    }

    #getSubmitButton()
    {
        return this.#container.querySelector(submit_selector);
    }

    #getKbId()
    {
        return this.#container.querySelector(kb_id_selector).value;
    }

    #getCommentsDiv()
    {
        return this.#container.querySelector(comments_selector);
    }
}
