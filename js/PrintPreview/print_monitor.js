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
    var preview = document.querySelector('.preview');
    if (preview) {
        var mainform = preview.querySelector('#main-form');
        var formFields = mainform.querySelectorAll('.form-field');
        if (formFields.length > 0) {
            var lastFormField = formFields[formFields.length - 1];
            var formLabels = lastFormField.querySelectorAll('.form-check');
            var ports = [];
            formLabels.forEach(function(formLabel) {
                var formInput = formLabel.querySelector('.form-check-input');
                if (formInput.type === 'checkbox' && formInput.checked) {
                    var spanElement = formLabel.querySelector('.form-check-label');
                    if (spanElement) {
                        var spanText = spanElement.textContent.trim();
                        ports.push(spanText);
                    }
                }
                formLabel.remove();
            });

            var colFormLabel = lastFormField.querySelector('.col-form-label');
            if (colFormLabel) {
                var nextDiv = colFormLabel.nextElementSibling;
                if (nextDiv && nextDiv.tagName.toLowerCase() === 'div') {
                    nextDiv.innerHTML = '';
                    var spanElement = document.createElement('span');
                    spanElement.textContent = ports.join(', ');
                    nextDiv.appendChild(spanElement);
                }
            }
        }
    }
});
