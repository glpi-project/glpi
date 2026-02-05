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

import { post } from "/js/modules/Ajax.js";

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
const comments_empty_selector = "[data-glpi-comments-empty]";
const reply_trigger_selector = "[data-glpi-reply-trigger]";
const reply_btn_selector = "[data-glpi-reply-btn]";
const reply_form_selector = "[data-glpi-reply-form]";
const reply_textarea_selector = "[data-glpi-reply-textarea]";
const reply_cancel_selector = "[data-glpi-reply-cancel]";
const reply_submit_selector = "[data-glpi-reply-submit]";
const comment_thread_selector = "[data-glpi-comment-thread]";

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

            // Reply button
            const reply_btn = e.target.closest(reply_btn_selector);
            if (reply_btn) {
                this.#showReplyForm(reply_btn);
            }

            // Cancel reply button
            const reply_cancel_btn = e.target.closest(reply_cancel_selector);
            if (reply_cancel_btn) {
                this.#hideReplyForm(reply_cancel_btn);
            }

            // Submit reply button
            const reply_submit_btn = e.target.closest(reply_submit_selector);
            if (reply_submit_btn) {
                this.#submitReply(reply_submit_btn);
            }
        });

        // Show/hide reply trigger on thread hover
        this.#container.addEventListener('mouseenter', (e) => {
            const thread = e.target.closest(comment_thread_selector);
            if (thread) {
                const trigger = thread.querySelector(reply_trigger_selector);
                if (trigger) {
                    trigger.classList.remove('opacity-0');
                    trigger.classList.add('opacity-100');
                }
            }
        }, true);

        this.#container.addEventListener('mouseleave', (e) => {
            const thread = e.target.closest(comment_thread_selector);
            if (thread) {
                const trigger = thread.querySelector(reply_trigger_selector);
                if (trigger) {
                    trigger.classList.add('opacity-0');
                    trigger.classList.remove('opacity-100');
                }
            }
        }, true);
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

        const response = await post(`Knowbase/UpdateComment/${comment_id}`, {
            'content': textarea.value,
        });
        const result = await response.json();

        // Reset loading state
        submit_btn.classList.remove('pointer-events-none');
        submit_btn.querySelector('[data-glpi-icon]').classList.remove('d-none');
        submit_btn.querySelector('[data-glpi-loading]').classList.add('d-none');

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
        await post(`Knowbase/PurgeComment/${comment_id}`);

        // Get the others comments from this thread
        const parent_thread = comment_card.closest('[data-glpi-comment-thread]');
        const comments = parent_thread.querySelectorAll('[data-glpi-comment-id]');

        if (comments.length === 1) {
            // This is the only comment in this thread, delete the whole thread
            parent_thread.remove();
        } else {
            // Delete the comment
            comment_card.remove();
        }

        this.#updateCounter(-1);
        this.#showEmptyStateIfNoComments();
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

        const response = await post(`Knowbase/${this.#getKbId()}/AddComment`, {
            'content': this.#getContentTextarea().value,
        });

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

        // Insert new comment
        const html = await response.text();
        this.#getCommentsDiv().insertAdjacentHTML('beforeend', html);
        this.#updateCounter(1);
        this.#hideEmptyState();
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

    #updateCounter(delta)
    {
        const counter = document.querySelector('[data-glpi-kb-action-counter="comments"]');
        if (counter) {
            const current = parseInt(counter.textContent, 10) || 0;
            counter.textContent = Math.max(0, current + delta);
        }
    }

    #getEmptyState()
    {
        return this.#container.querySelector(comments_empty_selector);
    }

    #hideEmptyState()
    {
        const empty = this.#getEmptyState();
        empty.classList.add('d-none');
    }

    #showEmptyStateIfNoComments()
    {
        const empty = this.#getEmptyState();
        const comments = this.#getCommentsDiv();
        const has_comments = comments.querySelectorAll('[data-testid="comment"]').length > 0;
        empty.classList.toggle('d-none', has_comments);
    }

    #showReplyForm(reply_btn)
    {
        const thread = reply_btn.closest(comment_thread_selector);
        const form = thread.querySelector(reply_form_selector);
        const trigger = thread.querySelector(reply_trigger_selector);

        trigger.classList.add('d-none');
        form.classList.remove('d-none');
        form.querySelector(reply_textarea_selector).focus();

        // Add a special class so we can merge the borders with the reply form.
        thread.classList.add('reply-shown');
    }

    #hideReplyForm(cancel_btn)
    {
        const thread = cancel_btn.closest(comment_thread_selector);
        const form = thread.querySelector(reply_form_selector);
        const trigger = thread.querySelector(reply_trigger_selector);

        form.classList.add('d-none');
        form.querySelector(reply_textarea_selector).value = '';
        trigger.classList.remove('d-none');

        // Remove the special class.
        thread.classList.remove('reply-shown');
    }

    async #submitReply(submit_btn)
    {
        const thread            = submit_btn.closest(comment_thread_selector);
        const form              = thread.querySelector(reply_form_selector);
        const textarea          = form.querySelector(reply_textarea_selector);
        const parent_comment_id = form.dataset.parentCommentId;

        const content = textarea.value.trim();
        if (!content) {
            return;
        }

        // Show loading state
        submit_btn.classList.add('pointer-events-none');
        submit_btn.querySelector('[data-glpi-loading]').classList.remove('d-none');
        submit_btn.querySelector('[data-glpi-icon]').classList.add('d-none');

        const response = await post(`Knowbase/${this.#getKbId()}/AddComment`, {
            content : content,
            parent_comment_id : parent_comment_id,
        });

        // Reset loading state
        submit_btn.classList.remove('pointer-events-none');
        submit_btn.querySelector('[data-glpi-icon]').classList.remove('d-none');
        submit_btn.querySelector('[data-glpi-loading]').classList.add('d-none');

        // Insert new comment before the reply button
        const trigger = thread.querySelector(reply_trigger_selector);
        const html = await response.text();
        trigger.insertAdjacentHTML('beforebegin', html);
        this.#updateCounter(1);

        // Hide reply form and clear textarea
        textarea.value = '';
        form.classList.add('d-none');
        thread.querySelector(reply_trigger_selector).classList.remove('d-none');
    }
}
