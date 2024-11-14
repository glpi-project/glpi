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

describe('Display preferences', () => {
    before(() => {
        // Create at least one ticket as we will be displaying the ticket list
        // to validate that the right columns are displayed
        cy.createWithAPI('Ticket', {
            'name': 'Open ticket',
            'content': 'Open ticket',
        });
    });

    it('can add a column to the global view', () => {
        cy.login();
        cy.changeProfile('Super-Admin', true);
        cy.visit('/front/ticket.php');
        openDisplayPreferences();

        // Add a new column to the global view
        goToTab('Global View');
        addDisplayPeference('Pending reason');

        // Refresh page
        cy.reload();

        // Make sure the column was added to the ticket list (still as admin)
        cy.findByRole('columnheader', {'name': 'Pending reason'}).should('be.visible');

        // Switch to helpdesk and make sure the column was not added
        cy.changeProfile('Self-Service', true);
        cy.visit('/front/ticket.php');
        cy.findByRole('columnheader', {'name': 'Pending reason'}).should('not.exist');

        // Go back to super admin
        cy.changeProfile('Super-Admin', true);
        cy.visit('/front/ticket.php');

        // Validate that the column was added to the global view config
        openDisplayPreferences();
        goToTab('Global View');
        validateThatDisplayPreferenceExist('Pending reason');

        // Make sure the column is not in the helpdesk view config
        goToTab('Helpdesk View');
        validateThatDisplayPreferenceDoNotExist('Pending reason');

        // Return to global view and remove the new column to avoid changing the state for the next test
        goToTab('Global View');
        deletePreference('Pending reason');
    });

    it('can add a column to the helpesk view', () => {
        cy.login();
        cy.changeProfile('Super-Admin', true);
        cy.visit('/front/ticket.php');
        openDisplayPreferences();

        // Add a new column to the global view
        goToTab('Helpdesk View');
        addDisplayPeference('Pending reason');

        // Refresh page
        cy.reload();

        // Make sure the column was not added to the central ticket list
        cy.findByRole('columnheader', {'name': 'Pending reason'}).should('not.exist');

        // Switch to helpdesk and make sure the column was added
        cy.changeProfile('Self-Service', true);
        cy.visit('/front/ticket.php');
        cy.findByRole('columnheader', {'name': 'Pending reason'}).should('be.visible');

        // Go back to super admin
        cy.changeProfile('Super-Admin', true);
        cy.visit('/front/ticket.php');

        // Validate that the column was added to the helpdesk view
        openDisplayPreferences();
        goToTab('Helpdesk View');
        validateThatDisplayPreferenceExist('Pending reason');

        // Make sure the column is not in the global view
        goToTab('Global View');
        validateThatDisplayPreferenceDoNotExist('Pending reason');

        // Return to helpdesk view and remove the new column to avoid changing the state for the next test
        goToTab('Helpdesk View');
        deletePreference('Pending reason');
    });

    function openDisplayPreferences() {
        cy.findByRole('button', {'name': 'Select default items to show'}).click();
        cy.findByRole('dialog').should('be.visible');
        createIframeBodyAlias();
    }

    function createIframeBodyAlias() {
        // Special code needed because the display preferences modal use an iframe
        // see: https://www.cypress.io/blog/working-with-iframes-in-cypress
        cy.findByTestId('display-preference-iframe')
            .its('0.contentDocument')
            .its('body')
            .then(cy.wrap)
            .as('iframeBody')
        ;
    }

    function goToTab(name) {
        cy.get('@iframeBody').find('#tabspanel-select').select(name);
    }

    function addDisplayPeference(name) {
        cy.get('@iframeBody')
            .getDropdownByLabelText('Select an option to add')
            .click()
        ;
        cy.get('@iframeBody')
            .findByRole('option', {'name': name})
            .click()
        ;
        cy.get('@iframeBody')
            .findByRole('button', {'name': 'Add'})
            .click()
        ;
    }

    function deletePreference(name) {
        cy.get('@iframeBody')
            .findByRole('list')
            .findByRole('option', {'name': name}) // Should be listitem instead of option, our DOM is wrong
            .findByRole('button', {'name': "Delete permanently"})
            .click()
        ;
    }

    function validateThatDisplayPreferenceExist(name) {
        cy.get('@iframeBody')
            .findByRole('list')
            .findByRole('option', {'name': name}) // Should be listitem instead of option, our DOM is wrong
            .should('be.visible')
        ;
    }

    function validateThatDisplayPreferenceDoNotExist(name) {
        cy.get('@iframeBody')
            .findByRole('list')
            .findByRole('option', {'name': name}) // Should be listitem instead of option, our DOM is wrong
            .should('not.exist')
        ;
    }
});
