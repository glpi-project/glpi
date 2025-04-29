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

describe('Form delegation', () => {
    let uuid = new Date().getTime();

    beforeEach(() => {
        uuid = new Date().getTime();
    });

    it("can't view delegation section when no delegation rights", () => {
        createFormAndRenderIt();
        cy.getDropdownByLabelText('Select the user to delegate').should('not.exist');
        cy.getDropdownByLabelText('Do you want to be notified of future events of this ticket').should('not.exist');
    });

    it('can delegate', () => {
        initDelegationWithAPI();
        createFormAndRenderIt();

        // Check values
        cy.getDropdownByLabelText('Select the user to delegate').should('have.text', 'Myself');
        cy.getDropdownByLabelText('Select the user to delegate').click();
        cy.get('.select2-results__options').contains(`Test user - ${uuid}`);
        cy.getDropdownByLabelText('Select the user to delegate').click();

        cy.getDropdownByLabelText('Do you want to be notified of future events of this ticket').should('not.exist');

        // Select user to delegate
        cy.getDropdownByLabelText('Select the user to delegate').selectDropdownValue(`Test user - ${uuid}`);

        // Check values
        cy.getDropdownByLabelText('Do you want to be notified of future events of this ticket').should('have.text', 'He wants');
        cy.getDropdownByLabelText('Do you want to be notified of future events of this ticket').click();
        cy.get('.select2-results__options').contains('He doesn\'t want');

        // Define email address
        cy.findByRole('button', { name: 'Address to send the notification' }).click();
        cy.findByRole('textbox', { name: 'Address to send the notification' }).type('test@test.fr');

        // Fill form
        cy.findByRole('textbox', { name: 'Name' }).type('Test');

        // Submit form
        cy.findByRole('button', { name: 'Submit' }).click();

        // Go to the created ticket
        cy.findByRole('link', { name: `My test form - ${uuid}` }).click();

        cy.findByRole('region', { name: 'Actors' }).within(() => {
            cy.findByRole('listitem', { name: `Test user - ${uuid}` }).should('exist');
            cy.findByRole('listitem', { name: 'E2E Tests' }).should('not.exist');
            cy.findByRole('button', { name: 'Email followup' }).click();
            cy.findByRole('checkbox', { name: 'Email followup' }).should('be.checked');
            cy.findByRole('textbox', { name: 'Email address' }).should('have.value', 'test@test.fr');
        });
    });

    it('can delegate in self-service', () => {
        initDelegationWithAPI();
        createFormAndRenderIt();
        cy.changeProfile('Self-Service');
        cy.reload();

        // Check values
        cy.getDropdownByLabelText('Select the user to delegate').should('have.text', 'Myself');
        cy.getDropdownByLabelText('Select the user to delegate').click();
        cy.get('.select2-results__options').contains(`Test user - ${uuid}`);
        cy.getDropdownByLabelText('Select the user to delegate').click();

        cy.getDropdownByLabelText('Do you want to be notified of future events of this ticket').should('not.exist');

        // Select user to delegate
        cy.getDropdownByLabelText('Select the user to delegate').selectDropdownValue(`Test user - ${uuid}`);

        // Check values
        cy.getDropdownByLabelText('Do you want to be notified of future events of this ticket').should('have.text', 'He wants');
        cy.getDropdownByLabelText('Do you want to be notified of future events of this ticket').click();
        cy.get('.select2-results__options').contains('He doesn\'t want');

        // Define email address
        cy.findByRole('button', { name: 'Address to send the notification' }).click();
        cy.findByRole('textbox', { name: 'Address to send the notification' }).type('test@test.fr');

        // Fill form
        cy.findByRole('textbox', { name: 'Name' }).type('Test');

        // Submit form
        cy.findByRole('button', { name: 'Submit' }).click();

        // Change profile to view ticket properties
        cy.changeProfile('Super-Admin');

        // Go to the created ticket
        cy.findByRole('link', { name: `My test form - ${uuid}` }).click();

        cy.findByRole('region', { name: 'Actors' }).within(() => {
            cy.findByRole('listitem', { name: `Test user - ${uuid}` }).should('exist');
            cy.findByRole('listitem', { name: 'E2E Tests' }).should('not.exist');
            cy.findByRole('button', { name: 'Email followup' }).click();
            cy.findByRole('checkbox', { name: 'Email followup' }).should('be.checked');
            cy.findByRole('textbox', { name: 'Email address' }).should('have.value', 'test@test.fr');
        });
    });

    function createFormAndRenderIt() {
        cy.login();
        cy.createFormWithAPI({
            name: `My test form - ${uuid}`,
            is_active: true
        }).as('form_id').then((form_id) => {
            cy.addQuestionToDefaultSectionWithAPI(
                form_id,
                'Name',
                'Glpi\\Form\\QuestionType\\QuestionTypeShortText',
                0,
                null
            );

            // Go to preview page
            cy.visit(`/Form/Render/${form_id}`);
        });
    }

    function initDelegationWithAPI() {
        // Create a group
        cy.createWithAPI('Group', {
            name: `Test group - ${uuid}`,
        }).as('group_id');

        // Create a user
        cy.createWithAPI('User', {
            name: `Test user - ${uuid}`,
        }).as('user_id');

        // Add E2E user to the group
        cy.get('@group_id').then((group_id) => {
            cy.createWithAPI('Group_User', {
                groups_id      : group_id,
                users_id       : 7,
                is_userdelegate: 1
            });
        });

        // Add the user to the group
        cy.get('@group_id').then((group_id) => {
            cy.get('@user_id').then((user_id) => {
                cy.createWithAPI('Group_User', {
                    groups_id: group_id,
                    users_id : user_id,
                });
            });
        });
    }
});
