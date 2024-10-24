/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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
    const preview = document.querySelector('.preview');
    if (preview) {
        const mainform = preview.querySelector('#main-form');
        if (mainform) {
            const helpdeskCheckbox = mainform.querySelector('input[name="is_helpdesk_visible"]');
            if (helpdeskCheckbox) {
                const spanElement = document.createElement('span');
                spanElement.textContent = helpdeskCheckbox.checked ? __('Yes') : __('No');
                helpdeskCheckbox.parentNode.replaceChild(spanElement, helpdeskCheckbox);
            }

            const updateCheckbox = mainform.querySelector('input[name="is_update"][type="checkbox"]');
            if (updateCheckbox) {
                if (updateCheckbox.checked) {
                    const spanElement2 = document.createElement('span');
                    spanElement2.classList.add('me-1');
                    spanElement2.textContent = __('Yes from');
                    updateCheckbox.parentNode.replaceChild(spanElement2, updateCheckbox);
                    const nextSibling = spanElement2.nextSibling;
                    if (nextSibling && nextSibling.nodeType === Node.TEXT_NODE) {
                        nextSibling.remove();
                    }
                } else {
                    const parentDiv = updateCheckbox.closest('div');
                    if (parentDiv) {
                        parentDiv.remove();
                    }
                }
            }
        }
    }
});
