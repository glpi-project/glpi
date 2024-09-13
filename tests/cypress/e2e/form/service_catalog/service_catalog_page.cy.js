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

describe('Service catalog page', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Self-Service', true);

    });

    it('can pick a form in the service catalog', () => {
        const form_name = "Test form for service_catalog_page.cy.js " + (new Date()).getTime();

        // Create at least one form to make sure the list is not empty.
        cy.createFormWithAPI({
            'name': form_name,
            'description': "Lorem ipsum dolor sit amet, consectetur adipisicing elit.",
            'is_active': true,
        });

        cy.visit('/ServiceCatalog');
        cy.injectAndCheckA11y();
        cy.findByRole('region', {'name': form_name}).as('form');

        // Validate that the form is displayed correctly.
        cy.get('@form').within(() => {
            cy.findByText(form_name).should('exist');
            cy.findByText("Lorem ipsum dolor sit amet, consectetur adipisicing elit.").should('exist');
        });

        // Go to form
        cy.get('@form').click();
        cy.url().should('include', '/Form/Render');

        // TODO: validate the form is actually displayed, can't be done others PR are merged.
    });
});
