/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

describe('Service catalog', () => {
    beforeEach(() => {
        // Create at least one form to make sure the list is not empty.
        cy.createFormWithAPI({
            'name': "Test form for service_catalog.cy.js",
            'header': "Lorem ipsum dolor sit amet, consectetur adipisicing elit. Aperiam deleniti fugit incidunt, iste, itaque minima neque pariatur perferendis sed suscipit velit vitae voluptatem.",
            'is_active': true,
        });

        cy.login();
        cy.changeProfile('Self-Service', true);

    });

    it('can pick a form in the service catalog', () => {
        cy.visit('/ServiceCatalog');
        cy.injectAndCheckA11y();
        cy.findByRole('region', {'name': 'Test form for service_catalog.cy.js'}).as('form');

        // Validate that the form is displayed correctly.
        cy.get('@form').within(() => {
            cy.findByText("Test form for service_catalog.cy.js").should('exist');
            cy.findByText("Lorem ipsum dolor sit amet, consectetur adipisicing elit. Aperiam deleniti fugit incidunt, iste, itaque minima neque pariatur perferendis sed suscipit velit vitae voluptatem.").should('exist');
        });

        // Go to form
        cy.get('@form').click();
        cy.url().should('include', '/Form/Render');

        // TODO: validate the form is actually displayed, can't be done others PR are merged.
    });
});
