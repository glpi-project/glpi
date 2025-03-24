/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

import 'cypress-axe';
import {terminalLog} from "./cypress-axe-spec-utility.js";

/**
 * @memberof Cypress.Chainable.prototype
 * @method injectAndCheckA11y
 * @description Inject, configure and run axe accessibility tests
 * @returns Chainable
 */
Cypress.Commands.add('injectAndCheckA11y', {prevSubject: 'optional'}, (subject) => {
    cy.injectAxe();
    cy.configureAxe({
        branding: 'GLPI',
    });
    const context = {
        exclude: [
            //FIXME These exclusions are known issues. They may not be easily fixable right now, but are intended to be addressed at some point.
            ['.tox-tinymce'], // TinyMCE library is not under our control and has some issues. Specifically, an issue with the resize handle was noticed.
            ['.select2-container'], // Select2 library is not under our control and has a lot of known issues
            ['div.fileupload'], // JQuery File upload library is not under our control and has some known issues
            ['.alert'], // Default Bootstrap/Tabler alert colors don't meet contrast requirements at any level of WCAG.
            ['.nav-pills .nav-link.active'],
            ['span[aria-label]'], // aria-label attribute cannot be used on a span with no valid role attribute. Done by bootstrap.
            // Below are items that do not need to be validated
            ['.sf-toolbar'], // Symfony profiler
            ['.flatpickr input[type="text"]'], // Input labels broken when using altInput option
            ['*[data-hasqtip]'], // Popper tooltips aren't created as labels for the elements they are attached to, but we use them instead of title attributes.
            ['aside.navbar.sidebar a.dropdown-item'], // Duplicate accesskeys
            ['.fancytree-container .fancytree-expander'], // Expand/collapse 'buttons' have no label
            ['.fancytree-container thead > tr > th'], // Fancytree adds an empty header row to the table
            ['.search-header .search-controls .show_export_menu'], // Wrapper element that triggers the tooltip is not valid
        ]
    };
    if (subject) {
        cy.log(subject);
        context.include = subject;
    }
    cy.checkA11y(context, null, terminalLog);
});

/**
 * @memberof Cypress.Chainable.prototype
 * @method disableAnimations
 * @description Disable animations on the page
 * @returns Chainable
 */
Cypress.Commands.add('disableAnimations', () => {
    cy.get('body').invoke('append', Cypress.$(`
        <style id="__cypress-animation-disabler">
            *, *:before, *:after {
              transition: none !important;
              animation: none !important;
            }
        </style>
    `));
});

/**
 * @memberof Cypress.Chainable.prototype
 * @method enableAnimations
 * @description Enable animations on the page
 * @returns Chainable
 */
Cypress.Commands.add('enableAnimations', () => {
    Cypress.$('#__cypress-animation-disabler').remove();
});
