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

/* global glpi_toast_error, glpi_confirm_danger, getAjaxCsrfToken */

const content_selector  = "[data-glpi-add-comments-content]";
const submit_selector   = "[data-glpi-add-comments-submit]";
const kb_id_selector    = "[data-glpi-kb-id]";
const comments_selector = "[data-glpi-comments]";
const comment_edit_selector = "[data-glpi-comment-edit]";
const comment_delete_selector = "[data-glpi-comment-delete]";
const comment_content_selector = "[data-glpi-comment-content]";
const comment_edit_form_selector = "[data-glpi-comment-edit-form]";
const comment_edit_textarea_selector = "[data-glpi-comment-edit-textarea]";
const comment_edit_cancel_selector = "[data-glpi-comment-edit-cancel]";
const comment_edit_submit_selector = "[data-glpi-comment-edit-submit]";

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

            // Edit comment button
            const edit_btn = e.target.closest(comment_edit_selector);
            if (edit_btn) {
                this.#showEditForm(edit_btn);
            }

            // Cancel edit button
            const cancel_btn = e.target.closest(comment_edit_cancel_selector);
            if (cancel_btn) {
                this.#hideEditForm(cancel_btn);
            }

            // Submit edit button
            const submit_edit_btn = e.target.closest(comment_edit_submit_selector);
            if (submit_edit_btn) {
                this.#submitEditComment(submit_edit_btn);
            }

            // Delete comment button
            const delete_btn = e.target.closest(comment_delete_selector);
            if (delete_btn) {
                this.#deleteComment(delete_btn);
            }
        });
    }

    #showEditForm(edit_btn)
    {
        const comment_card = edit_btn.closest('[data-testid="comment"]');
        const content = comment_card.querySelector(comment_content_selector);
        const form = comment_card.querySelector(comment_edit_form_selector);

        content.classList.add('d-none');
        form.classList.remove('d-none');

        const textarea = form.querySelector(comment_edit_textarea_selector);
        textarea.focus();
    }

    #hideEditForm(cancel_btn)
    {
        const comment_card = cancel_btn.closest('[data-testid="comment"]');
        const content = comment_card.querySelector(comment_content_selector);
        const form = comment_card.querySelector(comment_edit_form_selector);

        form.classList.add('d-none');
        content.classList.remove('d-none');
    }

    async #submitEditComment(submit_btn)
    {
        const comment_card = submit_btn.closest('[data-testid="comment"]');
        const comment_id   = comment_card.dataset.glpiCommentId;
        const form         = comment_card.querySelector(comment_edit_form_selector);
        const textarea     = form.querySelector(comment_edit_textarea_selector);
        const content      = comment_card.querySelector(comment_content_selector);

        // Show loading state
        submit_btn.classList.add('pointer-events-none');
        submit_btn.querySelector('[data-glpi-loading]').classList.remove('d-none');
        submit_btn.querySelector('[data-glpi-icon]').classList.add('d-none');

        const data = new FormData();
        data.append('content', textarea.value);

        const base_url = CFG_GLPI.root_doc;
        const url = `${base_url}/Knowbase/Comment/${comment_id}/Update`;
        const response = await fetch(url, {
            method: "POST",
            body: data,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-Glpi-Csrf-Token': getAjaxCsrfToken(),
            }
        });

        // Reset loading state
        submit_btn.classList.remove('pointer-events-none');
        submit_btn.querySelector('[data-glpi-icon]').classList.remove('d-none');
        submit_btn.querySelector('[data-glpi-loading]').classList.add('d-none');

        if (!response.ok) {
            glpi_toast_error(__("An unexpected error occurred."));
            return;
        }

        const result = await response.json();

        // Update content and hide form
        content.innerHTML = result.comment.replace(/\n/g, '<br>');
        form.classList.add('d-none');
        content.classList.remove('d-none');
    }

    async #deleteComment(delete_btn)
    {
        const comment_card = delete_btn.closest('[data-testid="comment"]');
        const comment_id   = comment_card.dataset.glpiCommentId;

        // Ask for confirmation
        const confirmed = await glpi_confirm_danger({
            title: __('Delete comment'),
            message: __('Are you sure you want to delete this comment?'),
            confirm_label: __('Delete'),
        });
        if (!confirmed) {
            return;
        }

        // Delete comment on the backend
        const base_url = CFG_GLPI.root_doc;
        const url = `${base_url}/Knowbase/Comment/${comment_id}/Delete`;
        const response = await fetch(url, {
            method: "POST",
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-Glpi-Csrf-Token': getAjaxCsrfToken(),
            }
        });
        if (!response.ok) {
            glpi_toast_error(__("An unexpected error occurred."));
            return;
        }

        // Delete comment on the client
        comment_card.remove();
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
