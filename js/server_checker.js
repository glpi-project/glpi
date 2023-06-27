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

/* global glpi_toast_info */

var ajax_url;

$(document).ready(function() {
    ajax_url = CFG_GLPI.root_doc + '/ajax/usessionactivity.php?input=user-activity';
});

/**
 * Object that sends ajax request for server status and show/hide warning messages.
 *
 * @type {Object}
 */
var ServerChecker = {
    $elem: null,
    isMessageDelivered: false,
    delay: 5000, // 5000 milliseconds

    start: function ($elem, timeout) {
        if (!$elem.length) {
            return false;
        }

        this.prepareNext(timeout);
        this.$elem = $elem;
    },

    prepareNext: function (delay) {
        setTimeout(this.check.bind(this), delay || this.delay);
    },

    check: function () {
        $.ajax({
            url: ajax_url,
            type: 'POST',
            data: { method: "user.status" },
            dataType: 'JSON',
            success: this.onSuccess.bind(this),
            error: this.onError.bind(this)
        });
    },

    onSuccess: function () {
        this.prepareNext();
    },

    onError: function (error) {
        console.warn(error.responseText);
        this.prepareNext();
    }
};

$(function () {
    ServerChecker.start($('#page'), 5000);
});
