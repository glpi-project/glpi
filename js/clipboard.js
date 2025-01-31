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

$(function() {
    // set a function to track drag hover event
    $(document).on("click", ".copy_to_clipboard_wrapper", function(event) {

        var succeed;
        // find the good element
        var target = $(event.target);

        // click on other button
        if (target.hasClass('input-group-text') && !target.hasClass('copy_to_clipboard_wrapper')) {
            return false;
        }

        // click on 'copy button'
        if (target.hasClass('input-group-text') || target.is('input')) {
            target = target.parent('.copy_to_clipboard_wrapper').find('input');

            // copy text
            succeed = copyTextToClipboard(target.val());
        } else {
            if (target.attr('class') == 'copy_to_clipboard_wrapper') {
                target = target.find('*');
            }

            // copy text
            target.select();
            try {
                succeed = document.execCommand("copy");
            } catch (e) {
                succeed = false;
            }
            target.blur();
        }

        // get copy icon
        var icon;
        if (target.attr('class') == 'copy_to_clipboard_wrapper') {
            icon = target;
        } else {
            icon = target.parent('.copy_to_clipboard_wrapper').find('i.copy_to_clipboard_wrapper');
            if (!icon.length) {
                icon = target.parent('.copy_to_clipboard_wrapper');
            }
        }

        // indicate success
        if (succeed) {
            $('.copy_to_clipboard_wrapper.copied').removeClass('copied');
            icon.addClass('copied');
            setTimeout(function(){
                icon.removeClass('copied');
            }, 1000);
        } else {
            icon.addClass('copyfail');
        }
    });
});

/**
 * Copy a text to the clipboard
 *
 * @param {string} text
 *
 * @return {void}
 */
function copyTextToClipboard (text) {
    // Create a textarea to be able to select its content
    var textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.setAttribute('readonly', ''); // readonly to prevent focus
    textarea.style = {position: 'absolute', visibility: 'hidden'};
    document.body.appendChild(textarea);

    // Select and copy text to clipboard
    textarea.select();
    var succeed;
    try {
        succeed = document.execCommand('copy');
    } catch (e) {
        succeed = false;
    }

    // Remove textarea
    document.body.removeChild(textarea);

    return succeed;
}
