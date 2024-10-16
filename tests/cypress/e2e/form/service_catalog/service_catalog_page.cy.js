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

const form_name = `Test form for service_catalog_page.cy.js ${(new Date()).getTime()}`;

describe('Service catalog page', () => {
    before(() => {
        // Create at least one form to make sure the list is not empty.
        cy.createFormWithAPI({
            'name': form_name,
            'description': "Lorem ipsum dolor sit amet, consectetur adipisicing elit.",
            'is_active': true,
        }).as('form_id');

        // Allow form to be displayed in the service catalog.
        cy.login();
        cy.changeProfile('Super-Admin');
        cy.get('@form_id').visitFormTab('Policies');
        cy.getDropdownByLabelText('Allow specifics users, groups or profiles').selectDropdownValue('All users');
        cy.findByRole('link', {'name': /There are \d+ user\(s\) matching these criteria\./}).should('exist');
        cy.findByRole('button', {name: 'Save changes'}).click();
    });

    beforeEach(() => {
        cy.login();
        cy.changeProfile('Self-Service', true);
        cy.visit('/ServiceCatalog');

        // TODO: General A11y issues that are not related to the service catalog must be fixed before this can be enabled.
        // cy.injectAndCheckA11y();
    });

    it('can pick a form in the service catalog', () => {
        cy.findByRole('region', {'name': form_name}).as('forms');
        // Validate that the form is displayed correctly.

        cy.get('@forms').within(() => {
            cy.findByText(form_name).should('exist');
            cy.findByText("Lorem ipsum dolor sit amet, consectetur adipisicing elit.").should('exist');
        });

        // Go to form
        cy.get('@forms').click();
        cy.url().should('include', '/Form/Render');
    });

    it('can filter forms in the service catalog', () => {
        cy.findByRole('region', {'name': form_name}).as('forms');
        cy.findByPlaceholderText('Search for forms...').as('filter_input');

        // Form should be visible as we have no filters yet
        cy.get('@forms').findByText(form_name).should('exist');

        // Filter out the form
        cy.get('@filter_input').type('nonexistent');
        cy.get('@forms').findByText(form_name).should('not.exist');

        // Filter in the form
        cy.get('@filter_input').clear();
        cy.get('@filter_input').type(form_name);
        cy.get('@forms').findByText(form_name).should('exist');
    });
});
