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

describe('Form access policy', () => {
    beforeEach(() => {
        cy.createWithAPI('Glpi\\Form\\Form', {
            'name': 'Test form for the access policy form suite',
            'is_active': true,
            '_init_access_policies': false,
        }).as('form_id');

        cy.login();
        cy.changeProfile('Super-Admin');

        cy.get('@form_id').then((form_id) => {
            const tab = 'Glpi\\Form\\AccessControl\\FormAccessControl$1';
            cy.visit(`/front/form/form.form.php?id=${form_id}&forcetab=${tab}`);
        });
    });

    it('check if form direct access policy can be set', () => {
        cy.changeProfile('Super-Admin');

        // Enable direct access policy
        cy.findByRole('region', {
            name: 'Allow direct access'
        }).within(() => {
            cy.findByRole('checkbox', {name: 'Active'})
                .should('not.be.checked')
                .click()
            ;
        });

        // Check if "allow unauthenticated users" checkbox isn't checked
        cy.findByRole('checkbox', { 'name': 'Allow unauthenticated users ?' }).should('not.be.checked');
        cy.findByRole('textbox', { 'name': 'Direct access URL' }).should('exist').as('direct_access_url_before_save');

        // Save changes
        cy.findByRole('button', { 'name': 'Save changes' }).click();

        // Retrieve the direct access URL
        cy.findByRole('textbox', { 'name': 'Direct access URL' }).should('exist').as('direct_access_url');

        // Make sure the url wasn't regenerated
        cy.get('@direct_access_url_before_save').invoke('val').then((direct_access_url_before_save) => {
            cy.get('@direct_access_url').invoke('val').then((direct_access_url) => {
                expect(direct_access_url_before_save).to.equal(direct_access_url);
            });
        });

        // Visit the direct access URL as non admin (to make sure the token is taken into account)
        cy.get('@direct_access_url').invoke('val').then((direct_access_url) => {
            cy.changeProfile('Self-Service');
            cy.visit(direct_access_url);

            // Check if the form title is displayed
            cy.findByRole('heading', { 'name': 'Form title' }).should('exist').contains('Test form for the access policy form suite');
        });
    });

    it('check if form direct access policy can be set and direct access works with autenticated user', () => {
        cy.changeProfile('Super-Admin');

        // Enable direct access policy
        cy.findByRole('region', {
            name: 'Allow direct access'
        }).within(() => {
            cy.findByRole('checkbox', {name: 'Active'})
                .should('not.be.checked')
                .click()
            ;
        });

        // Check if "allow unauthenticated users" checkbox isn't checked
        cy.findByRole('checkbox', { 'name': 'Allow unauthenticated users ?' }).should('not.be.checked');

        // Save changes
        cy.findByRole('button', { 'name': 'Save changes' }).click();

        // Retrieve the direct access URL
        cy.findByRole('textbox', { 'name': 'Direct access URL' }).should('exist').as('direct_access_url');

        // Logout
        cy.logout();

        // Visit the direct access URL
        cy.get('@direct_access_url').invoke('val').then((direct_access_url) => {
            // Check if we can't access the form
            cy.request({
                url: direct_access_url,
                failOnStatusCode: false,
            }).then((response) => {
                expect(response.status).to.eq(403);
            });
        });
    });

    it('check if form direct access policy can be set and direct access works with unauthenticated user', () => {
        cy.changeProfile('Super-Admin');

        // Enable direct access policy
        cy.findByRole('region', {
            name: 'Allow direct access'
        }).within(() => {
            cy.findByRole('checkbox', {name: 'Active'})
                .should('not.be.checked')
                .click()
            ;
        });

        // Enable "allow unauthenticated users"
        cy.findByRole('checkbox', { 'name': 'Allow unauthenticated users ?' }).check();

        // Save changes
        cy.findByRole('button', { 'name': 'Save changes' }).click();

        // Retrieve the direct access URL
        cy.findByRole('textbox', { 'name': 'Direct access URL' }).should('exist').as('direct_access_url');

        // Logout
        cy.logout();

        // Visit the direct access URL
        cy.get('@direct_access_url').invoke('val').then((direct_access_url) => {
            cy.visit(direct_access_url);

            // Check if the form title is displayed
            cy.findByRole('heading', { 'name': 'Form title' }).should('exist').contains('Test form for the access policy form suite');
        });
    });

    it('check if form direct access policy can be set and direct access works with unauthenticated user and hide blacklisted questions', () => {
        cy.changeProfile('Super-Admin');

        // Enable direct access policy
        cy.findByRole('region', {
            name: 'Allow direct access'
        }).within(() => {
            cy.findByRole('checkbox', {name: 'Active'})
                .should('not.be.checked')
                .click()
            ;
        });

        // Enable "allow unauthenticated users"
        cy.findByRole('checkbox', { 'name': 'Allow unauthenticated users ?' }).check();

        // Save changes
        cy.findByRole('button', { 'name': 'Save changes' }).click();

        // Retrieve the direct access URL
        cy.findByRole('textbox', { 'name': 'Direct access URL' }).should('exist').as('direct_access_url');

        // Add a question
        cy.get('@form_id').then((form_id) => {
            const tab = 'Glpi\\Form\\Form$main';
            cy.visit(`/front/form/form.form.php?id=${form_id}&forcetab=${tab}`);

            // Add a question
            cy.findByRole('button', { 'name': 'Add a question' }).click();

            // Set the question title
            cy.focused().type('Actor question title');

            // Select the actor question type
            cy.findByRole('combobox', { 'name': 'Short answer' }).select('Actors');

            // Add a question
            cy.findByRole('button', { 'name': 'Add a question' }).click();

            // Set the question title
            cy.focused().type('Short answer question title');

            // Save form
            cy.findByRole('button', { 'name': 'Save' }).click();

            // Wait and close the save alert
            cy.findByRole('alert', { timeout: 10000 }).should('exist').within(() => {
                cy.findByRole('button', { 'name': 'Close' }).click();
            });
        });

        // Logout
        cy.logout();

        // Visit the direct access URL
        cy.get('@direct_access_url').invoke('val').then((direct_access_url) => {
            cy.visit(direct_access_url);

            // Check if the form title is displayed
            cy.findByRole('heading', { 'name': 'Form title' }).should('exist').contains('Test form for the access policy form suite');

            // Check if the actor question is hidden
            cy.findByRole('heading', { 'name': 'Actor question title' }).should('not.exist');

            // Check if the short answer question is displayed
            cy.findByRole('heading', { 'name': 'Short answer question title' }).should('exist');
        });
    });

    it('check that form can be submitted with direct access', () => {
        cy.changeProfile('Super-Admin');

        // Enable direct access policy
        cy.findByRole('region', {
            name: 'Allow direct access'
        }).within(() => {
            cy.findByRole('checkbox', {name: 'Active'})
                .should('not.be.checked')
                .click()
            ;
        });
        cy.findByRole('textbox', { 'name': 'Direct access URL' }).should('exist').as('direct_access_url');
        cy.findByRole('button', { 'name': 'Save changes' }).click();

        // Add a simple question to the form
        cy.get('@form_id').then((form_id) => {
            const tab = 'Glpi\\Form\\Form$main';
            cy.visit(`/front/form/form.form.php?id=${form_id}&forcetab=${tab}`);
        });
        cy.findByRole('button', { 'name': 'Add a question' }).click();
        cy.focused().type('Question 1');
        cy.findByRole('button', { 'name': 'Save' }).click();
        cy.findByRole('alert').should('contain.text', 'Item successfully updated');

        // Change profile and go to the form
        cy.changeProfile('Self-Service');
        cy.get('@direct_access_url')
            .invoke('val')
            .then((direct_access_url) => {
                cy.visit(direct_access_url);
                cy.findByRole('heading', { 'name': 'Form title' }).should('exist');
                cy.findByRole('textbox', { 'name': 'Question 1' }).type('My answer');
                cy.findByRole('button', { 'name': 'Submit' }).click();
                cy.findByRole('alert').should('contain.text', 'Item successfully created');
            })
        ;
    });
});
