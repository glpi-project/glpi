/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

/**
 * Client code to handle AJAX updates orchestrated by the CommonAjaxController class
 */

/* global glpi_toast_info */
/* global glpi_toast_warning */
/* global glpi_toast_error */
/* global _ */

// Isolate functions and run when document is ready
class GlpiCommonAjaxController
{
    constructor() {
        // Init events handler
        $(document).on(
            'submit',
            'form[data-ajax-submit]',
            (e) => this.#handleFormSubmit(e)
        );
    }

    /**
     * Handle ajax form submission event
     *
     * @param {Event} e
     */
    async #handleFormSubmit(e) {
        e.preventDefault();

        // Build form data
        const form = $(e.target).closest('form');
        const data = this.#buildFormData(form);

        // Send AJAX request
        try {
            const response = await $.ajax({
                url: `${CFG_GLPI.root_doc}/GenericAjaxCrud`,
                method: 'POST',
                contentType: 'application/json',
                data: data,
            });

            // Response might contain specific content depending on the action type
            // This extra content will be handled by the functions below
            this.#handleFeedbackMessages(response);
            this.#handleFriendlyNameUpdate(response);
            this.#handleTrashbinStatus(response, form);
            this.#handleRedirect(response);
            this.#removeCachedTabsContent();

            // Trigger a custom event to allow the client to execute an handler
            // after the form has been successfully submitted
            form.trigger("glpi-ajax-controller-submit-success", response);
        } catch (error) {
            // Handle known backend errors
            if (
                error.responseJSON !== undefined
                && error.responseJSON.messages !== undefined
            ) {
                this.#handleFeedbackMessages(error.responseJSON);
            } else {
                // We don't know how to handle this error
                console.error(error);
                this.#handleFeedbackMessages({
                    messages: {
                        error: [__("Unexpected error.")],
                    },
                });
            }
        }
    }

    /**
     * Build form data from the submitted form data + some extra params like
     * the expected action and the target itemtype.
     *
     * @param {jQuery} form
     * @returns {string}
     */
    #buildFormData(form) {
        // Try to get submit button info
        const active_element = document.activeElement;
        let action = null;

        if (
            // Submitted using the submit button
            active_element.tagName.toLowerCase() == "button"
            || (
                // Submitted using a "submit" or "button" input
                active_element.tagName.toLowerCase() == "input"
                && (
                    $(active_element).attr("type") == "submit"
                    || $(active_element).attr("type") == "button"
                )
            )
        ) {
            // Get value from active element name
            action = $(active_element).prop('name');
        } else {
            // Form submitted by shift + enter, default to "update"
            action = "update";
        }

        const form_data = new FormData(form.get(0));
        const form_object = {};

        let key;
        let value;
        for ([key, value] of form_data.entries()) {
            if (value === '' && form.get(0).querySelector(`[name="${CSS.escape(key)}[]"]:not([disabled])`)) {
                // Empty hidden field placed before each multiple select dropdown
                // to be sure to send an empty value if no option is selected.
                if (_.get(form_object, key) === undefined) {
                    _.setWith(form_object, key, [], Object);
                }
                continue;
            }

            if (key.endsWith('[]')) {
                const baseKey = key.slice(0, -2);
                // Initialize as array if not an array
                if (_.get(form_object, baseKey) === undefined || !Array.isArray(_.get(form_object, baseKey))) {
                    _.set(form_object, baseKey, []);
                }
                // Push value to array directly instead of using indexed key
                _.get(form_object, baseKey).push(value);
            } else {
                _.setWith(form_object, key, value, Object);
            }
        }

        // Add submit button info to the form data
        form_object._action = action;

        // Also keep action as a "direct" parameter as some internal code will
        // look for it that way
        form_object[action] = true;

        // Add target itemtype
        form_object.itemtype = form.data('ajaxSubmitItemtype');

        // Use JSON format for large forms to avoid max_input_vars limitation
        return JSON.stringify(form_object);
    }

    /**
     * Handle feedback message found in the response
     *
     * @param {Object} response
     * @returns {void}
     */
    #handleFeedbackMessages(response) {
        if (!response.messages) {
            return;
        }

        // Display feedback messages as a toast
        if (response.messages.info !== undefined) {
            response.messages.info.forEach(message => glpi_toast_info(message));
        }
        if (response.messages.warning !== undefined) {
            response.messages.warning.forEach(message => glpi_toast_warning(message));
        }
        if (response.messages.error !== undefined) {
            response.messages.error.forEach(message => glpi_toast_error(message));
        }
    }

    /**
     * Handle friendly name updates found in the response
     *
     * @param {Object} response
     * @returns {void}
     */
    #handleFriendlyNameUpdate(response) {
        if (!response.friendlyname || !$('#header-friendlyname').length) {
            return;
        }

        // Update friendlyname
        $('#header-friendlyname').text(response.friendlyname);
    }

    /**
     * Handle thrashin status updates found in the response
     *
     * @param {Object} response
     * @param {jQuery} form
     * @returns {void}
     */
    #handleTrashbinStatus(response, form) {
        if (response.is_deleted === undefined) {
            return;
        }

        // Update UX to show deleted state and relevant actions
        $('#navigationheader').toggleClass("asset-deleted", response.is_deleted);
        form.find('button[name=delete]').toggleClass("d-none", response.is_deleted);
        form.find('button[name=purge]').toggleClass("d-none", !response.is_deleted);
        form.find('button[name=restore]').toggleClass("d-none", !response.is_deleted);
    }

    /**
     * Handle redirect instructions found in the response
     *
     * @param {Object} response
     * @returns {void}
     */
    #handleRedirect(response) {
        if (response.redirect === undefined) {
            return;
        }

        // Redirect to specified page
        window.location = response.redirect;
    }

    #removeCachedTabsContent() {
        $('[data-glpi-tab-content]').each((index, element) => {
            const is_current_tab = $(element).hasClass('active');
            if (is_current_tab) {
                return;
            }

            $(element).html("");
        });

    }
}
