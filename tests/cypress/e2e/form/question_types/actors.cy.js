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

describe('Actor form question type', () => {
    beforeEach(() => {
        cy.createWithAPI('Glpi\\Form\\Form', {
            'name': 'Tests form for the actor form question type suite',
        }).as('form_id');

        cy.login();
        cy.changeProfile('Super-Admin');

        cy.get('@form_id').then((form_id) => {
            const tab = 'Glpi\\Form\\Form$main';
            cy.visit(`/front/form/form.form.php?id=${form_id}&forcetab=${tab}`);

            // Add a question
            cy.findByRole("button", { name: "Add a question" }).should('exist').click();

            // Set the question name
            cy.findByRole("textbox", { name: "Question name" }).should('exist').type("Test actor question");

            // Change question type
            cy.getDropdownByLabelText('Question type').selectDropdownValue('Actors');

            // Define question sub type
            cy.getDropdownByLabelText('Question sub type').selectDropdownValue('Assignees');
        });
    });

    it('should be able to define an actor as default value', () => {
        // Ensure we don't allow multiple actors
        cy.findByRole('checkbox', { name: 'Allow multiple actors' }).should('not.be.checked');

        // Define default value
        cy.getDropdownByLabelText('Select an actor...').selectDropdownValue('E2E Tests');

        // Save
        cy.findByRole('button', { name: 'Save' }).click();

        // Reload the page
        cy.reload();

        // Check the default value
        cy.getDropdownByLabelText('Select an actor...').should('have.text', 'e2e_tests');
    });

    it('should be able to define multiple actors as default value', () => {
        // Allow multiple actors
        cy.findByRole('checkbox', { name: 'Allow multiple actors' }).check();

        // Define default values
        cy.getDropdownByLabelText('Select an actor...').selectDropdownValue('E2E Tests');
        cy.getDropdownByLabelText('Select an actor...').selectDropdownValue('glpi');

        // Save
        cy.findByRole('button', { name: 'Save' }).click();

        // Reload the page
        cy.reload();

        // Check the default values
        cy.getDropdownByLabelText('Select an actor...').contains('e2e_tests');
        cy.getDropdownByLabelText('Select an actor...').contains('glpi');
    });

    it('should be able to switch between multiple actors and single actor', () => {
        // Double switch
        cy.findByRole('checkbox', { name: 'Allow multiple actors' }).check();
        cy.findByRole('checkbox', { name: 'Allow multiple actors' }).uncheck();

        // Define default value
        cy.getDropdownByLabelText('Select an actor...').selectDropdownValue('E2E Tests');

        // Save
        cy.findByRole('button', { name: 'Save' }).click();

        // Reload the page
        cy.reload();

        // Check the default value
        cy.getDropdownByLabelText('Select an actor...').should('have.text', 'e2e_tests');

        // Focus on the question
        cy.findByRole('option', { name: 'Test actor question' })
            .findByRole('textbox', { name: 'Question name' }).click();

        // Switch to multiple actors
        cy.findByRole('checkbox', { name: 'Allow multiple actors' }).check();

        // Check the default value
        cy.getDropdownByLabelText('Select an actor...').should('have.text', '×e2e_tests');

        // Add another actor
        cy.getDropdownByLabelText('Select an actor...').selectDropdownValue('glpi');

        // Save
        cy.findByRole('button', { name: 'Save' }).click();

        // Reload the page
        cy.reload();

        // Check the default values
        cy.getDropdownByLabelText('Select an actor...').contains('e2e_tests');
        cy.getDropdownByLabelText('Select an actor...').contains('glpi');
    });

    it('can duplicate a single actor question', () => {
        // Define default value
        cy.getDropdownByLabelText('Select an actor...').selectDropdownValue('E2E Tests');

        // Duplicate the question
        cy.findByRole('button', { 'name': "Duplicate question" }).click();

        cy.findAllByRole('region', { 'name': 'Question details' }).each((region) => {
            cy.wrap(region).within(() => {
                cy.getDropdownByLabelText('Select an actor...').contains('E2E Tests');
            });
        });
    });

    it('can duplicate a multiple actors question', () => {
        // Allow multiple actors
        cy.findByRole('checkbox', { name: 'Allow multiple actors' }).check();

        // Define default values
        cy.getDropdownByLabelText('Select an actor...').selectDropdownValue('E2E Tests');
        cy.getDropdownByLabelText('Select an actor...').selectDropdownValue('glpi');

        // Duplicate the question
        cy.findByRole('button', { 'name': "Duplicate question" }).click();

        cy.findAllByRole('region', { 'name': 'Question details' }).each((region) => {
            cy.wrap(region).within(() => {
                cy.getDropdownByLabelText('Select an actor...').contains('E2E Tests');
                cy.getDropdownByLabelText('Select an actor...').contains('glpi');
            });
        });
    });

    it('check available actors according to the question sub type', () => {
        cy.get('@form_id').then((form_id) => {
            function createGroup(name, is_assign, is_watcher, is_requester) {
                cy.createWithAPI('Group', {
                    'name': name,
                    'is_assign': is_assign ? 1 : 0,
                    'is_watcher': is_watcher ? 1 : 0,
                    'is_requester': is_requester ? 1 : 0,
                });
            }

            function checkActorOptions(questionAlias, userOptionsExist, groupOptionsExist) {
                cy.get(questionAlias).getDropdownByLabelText('Select an actor...').click();
                cy.findByRole('group', { name: 'User' }).within(() => {
                    cy.findByRole('option', { name: 'glpi' }).should(userOptionsExist.glpi ? 'exist' : 'not.exist');
                    cy.findByRole('option', { name: 'post-only' }).should(userOptionsExist.postOnly ? 'exist' : 'not.exist');
                });
                cy.findByRole('group', { name: 'Group' }).within(() => {
                    cy.findByRole('option', { name: first_group_name }).should(groupOptionsExist.firstGroup ? 'exist' : 'not.exist');
                    cy.findByRole('option', { name: second_group_name }).should(groupOptionsExist.secondGroup ? 'exist' : 'not.exist');
                });
                cy.get(questionAlias).getDropdownByLabelText('Select an actor...').click(); // Close the dropdown
            }

            function addNewQuestion(questionName, subType, alias) {
                cy.findByRole("button", { name: "Add a question" }).should('exist').click();
                cy.findAllByRole('region', { name: 'Question details' }).last().as(alias);
                cy.get(`@${alias}`).findByRole("textbox", { name: "Question name" }).should('exist').type(questionName);
                cy.get(`@${alias}`).getDropdownByLabelText('Question type').selectDropdownValue('Actors');
                cy.get(`@${alias}`).getDropdownByLabelText('Question sub type').selectDropdownValue(subType);
            }

            const first_group_name = `Test Group that can be assigned, added as observer and requester - ${form_id}`;
            const second_group_name = `Test Group that can't be assigned, added as observer and requester - ${form_id}`;

            createGroup(first_group_name, true, true, true);
            createGroup(second_group_name, false, false, false);

            // Verify the assignee question
            cy.findByRole('region', { name: 'Question details' }).as('assignee_question');
            checkActorOptions('@assignee_question', { glpi: true, postOnly: false }, { firstGroup: true, secondGroup: false });

            // Add and verify the observer question
            addNewQuestion("Test observer question", 'Observers', 'observer_question');
            checkActorOptions('@observer_question', { glpi: true, postOnly: true }, { firstGroup: true, secondGroup: false });

            // Add and verify the requester question
            addNewQuestion("Test requestor question", 'Requesters', 'requestor_question');
            checkActorOptions('@requestor_question', { glpi: true, postOnly: true }, { firstGroup: true, secondGroup: false });
        });
    });

    it('can submit a form with an empty actor question with multiple actors allowed', () => {
        // Allow multiple actors
        cy.findByRole('checkbox', { name: 'Allow multiple actors' }).check();

        // Save
        cy.findByRole('button', { name: 'Save' }).click();

        // Go to preview
        cy.findByRole('link', { 'name': "Preview" })
            .invoke('removeAttr', 'target') // Cypress can't handle tab changes
            .click();

        // Submit the form
        cy.findByRole('button', { 'name': 'Submit' }).click();

        // Check the form was submitted
        cy.checkAndCloseAlert('Item successfully created');
    });

    it('can submit a form with an empty actor question with simple actor', () => {
        // Save
        cy.findByRole('button', { name: 'Save' }).click();

        // Go to preview
        cy.findByRole('link', { 'name': "Preview" })
            .invoke('removeAttr', 'target') // Cypress can't handle tab changes
            .click();

        // Submit the form
        cy.findByRole('button', { 'name': 'Submit' }).click();

        // Check the form was submitted
        cy.checkAndCloseAlert('Item successfully created');
    });
});
