/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

// Isolate functions and run when document is ready
$(() => {
    /**
     * Build form data from the submitted form data + some extra params like
     * the expected action and the target itemtype.
     *
     * @param {Object} form
     * @returns {string}
     */
    const buildFormData = function (form) {
        // Parse raw form data
        const data = form.serializeArray();

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

        // Add submit button info to the form data
        data.push({'name': '_action', 'value': action});

        // Also keep action as a "direct" parameter as some internal code will
        // look for it that way
        data.push({'name': action, 'value': true});

        // Add target itemtype
        data.push({'name': 'itemtype', 'value': form.data('ajaxSubmitItemtype')});

        return $.param(data);
    };

    /**
     * Handle feedback message found in the response
     *
     * @param {Object} response
     * @returns {void}
     */
    const handleFeedbackMessages = function (response) {
        if (!response.messages) {
            return;
        }

        // Display feedback messages as a toast
        response.messages.info.forEach(message => glpi_toast_info(message));
        response.messages.warning.forEach(message => glpi_toast_warning(message));
        response.messages.error.forEach(message => glpi_toast_error(message));
    };

    /**
     * Handle friendly name updates found in the response
     *
     * @param {Object} response
     * @returns {void}
     */
    const handleFriendlyNameUpdate = function (response) {
        if (!response.friendlyname || !$('#header-friendlyname').length) {
            return;
        }

        // Update friendlyname
        $('#header-friendlyname').text(response.friendlyname);
    };

    /**
     * Handle thrashin status updates found in the response
     *
     * @param {Object} response
     * @param {Object} form
     * @returns {void}
     */
    const handleTrashbinStatus = function (response, form) {
        if (response.is_deleted === undefined) {
            return;
        }

        // Update UX to show deleted state and relevant actions
        $('#navigationheader').toggleClass("asset-deleted", response.is_deleted);
        form.find('button[name=delete]').toggleClass("d-none", response.is_deleted);
        form.find('button[name=purge]').toggleClass("d-none", !response.is_deleted);
        form.find('button[name=restore]').toggleClass("d-none", !response.is_deleted);
    };

    /**
     * Handle redirect instructions found in the response
     *
     * @param {Object} response
     * @returns {void}
     */
    const handleRedirect = function (response) {
        if (response.redirect === undefined) {
            return;
        }

        // Redirect to specified page
        window.location = response.redirect;
    };

    // Event handler on ajax form submit
    $(document).on('submit', 'form[data-ajax-submit]', async function(e) {
        e.preventDefault();

        // Build form data
        const form = $(e.target).closest('form');
        const data = buildFormData(form);

        // Send AJAX request
        try {
            const response = await $.post({
                url: CFG_GLPI.root_doc + '/ajax/common_ajax_controller.php',
                data: data,
            });

            // Response might contain specific content depending on the action type
            // This extra content will be handled by the functions below
            handleFeedbackMessages(response);
            handleFriendlyNameUpdate(response);
            handleTrashbinStatus(response, form);
            handleRedirect(response);
        } catch (error) {
            // Handle backend errors
            const response = error.responseJSON;

            // Add minimal error message if server provided no feedback
            if (response.messages === undefined) {
                response.messages = {error: __("Unexpected error")};
            }

            handleFeedbackMessages(response);
        }
    });
});
