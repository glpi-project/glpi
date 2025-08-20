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

const actorTypes = [
    {
        name: 'Requester',
        type: 'requester',
        dataAttr: 'requester',
        defaultValue: 'User who filled the form'
    },
    {
        name: 'Assignee',
        type: 'assignee',
        dataAttr: 'assign',
        defaultValue: 'From template'
    },
    {
        name: 'Observer',
        type: 'observer',
        dataAttr: 'observer',
        defaultValue: 'From template'
    }
];

describe('Actors configuration', () => {
    actorTypes.forEach((actorConfig) => {
        describe(`${actorConfig.name} configuration`, () => {
            beforeEach(() => {
                cy.login();
                cy.changeProfile('Super-Admin', true);

                cy.createFormWithAPI().as('form_id').visitFormTab('Form');

                // Create actor, group and computer test data
                cy.get('@form_id').then((form_id) => {
                    const actor_name = `Test ${actorConfig.name} - ${form_id}`;
                    const userParams = {
                        name: actor_name,
                    };

                    // Add technician profile for assignees
                    if (actorConfig.type === 'assignee') {
                        userParams._profiles_id = 6;
                    }

                    cy.createWithAPI('User', userParams).as('actor_id');

                    // Create a Group
                    cy.createWithAPI('Group', {
                        name: `Test Group - ${form_id}`,
                    }).as('group_id');

                    // Create a Computer with users and groups
                    cy.get('@group_id').then((group_id) => {
                        cy.createWithAPI('Computer', {
                            name: `Test Computer - ${form_id}`,
                            users_id: 7,
                            users_id_tech: 7,
                            groups_id: group_id,
                            groups_id_tech: group_id,
                        }).as('computer_id');
                    });

                    // Create actor question
                    cy.findByRole('button', {'name': "Add a question"}).click();
                    cy.focused().type(`My ${actorConfig.name} question`);
                    cy.getDropdownByLabelText('Question type').selectDropdownValue('Actors');
                    cy.getDropdownByLabelText('Question sub type').selectDropdownValue(`${actorConfig.name}s`);
                    cy.getDropdownByLabelText("Select an actor...").selectDropdownValue(actor_name);
                    cy.findByRole('button', {'name': 'Save'}).click();
                    cy.checkAndCloseAlert('Item successfully updated');

                    // Create computer question
                    cy.findByRole('button', {'name': "Add a question"}).click();
                    cy.focused().type("My Computer question");
                    cy.getDropdownByLabelText('Question type').selectDropdownValue('Item');
                    cy.getDropdownByLabelText('Question sub type').selectDropdownValue('GLPI Objects');
                    cy.getDropdownByLabelText("Select an itemtype").selectDropdownValue('Computers');
                    cy.findByRole('button', {'name': 'Save'}).click();

                    // Check alert
                    cy.checkAndCloseAlert('Item successfully updated');

                    // Go to destination tab
                    cy.findByRole('tab', { 'name': "Destinations 1" }).click();
                });
            });

            it('can use all possibles configuration options', () => {
                const regionName = `${actorConfig.name}s configuration`;
                const dropdownLabel = `${actorConfig.name}s`;

                cy.openAccordionItem('Destination fields accordion', 'Actors');
                cy.findByRole('region', { 'name': regionName }).as("config");
                cy.get('@config').getDropdownByLabelText(dropdownLabel).as("dropdown");

                // Test default value
                cy.get('@dropdown').should('have.text', actorConfig.defaultValue);

                // Test hidden dropdowns
                cy.get('@config').getDropdownByLabelText('Select actors...').should('not.exist');
                cy.get('@config').getDropdownByLabelText('Select questions...').should('not.exist');

                // Test common options
                const commonOptions = [
                    'From template',
                    'User who filled the form',
                    'Specific actors',
                    'Answer from specific questions',
                    `Answer to last "${actorConfig.name}s" or "Email" question`,
                    'User from GLPI object answer',
                    'Tech user from GLPI object answer',
                    'Group from GLPI object answer',
                    'Tech group from GLPI object answer',
                    'Supervisor of the user who filled the form'
                ];

                // Test each option
                commonOptions.forEach((option) => {
                    cy.get('@dropdown').selectDropdownValue(option);

                    // Handle specific cases that need additional input
                    if (option === 'Specific actors') {
                        cy.get('@config').getDropdownByLabelText('Select actors...').as('specific_dropdown');
                        cy.get('@form_id').then((form_id) => {
                            const actor_name = `Test ${actorConfig.name} - ${form_id}`;
                            cy.get('@specific_dropdown').selectDropdownValue(actor_name);
                        });
                    } else if (option === 'Answer from specific questions') {
                        cy.get('@config').getDropdownByLabelText('Select questions...').as('question_dropdown');
                        cy.get('@question_dropdown').selectDropdownValue(`My ${actorConfig.name} question`);
                    } else if (option.includes('from GLPI object answer')) {
                        cy.get('@config').getDropdownByLabelText('Select questions...').as('object_dropdown');
                        cy.get('@object_dropdown').selectDropdownValue('My Computer question');
                    }

                    cy.findByRole('button', { 'name': 'Update item' }).click();
                    cy.checkAndCloseAlert('Item successfully updated');
                    cy.openAccordionItem('Destination fields accordion', 'Actors');
                    cy.get('@dropdown').should('have.text', option);
                });
            });

            it('can create ticket using default configuration', () => {
                const regionName = `${actorConfig.name}s configuration`;
                const dropdownLabel = `${actorConfig.name}s`;

                cy.openAccordionItem('Destination fields accordion', 'Actors');
                cy.findByRole('region', { 'name': regionName }).as("config");
                cy.get('@config').getDropdownByLabelText(dropdownLabel).as("dropdown");

                // Set to "User who filled the form"
                cy.get('@dropdown').selectDropdownValue('User who filled the form');
                cy.findByRole('button', { 'name': 'Update item' }).click();
                cy.checkAndCloseAlert('Item successfully updated');

                // Preview and submit form
                cy.findByRole('tab', { 'name': "Form" }).click();
                cy.findByRole('link', { 'name': "Preview" })
                    .invoke('removeAttr', 'target')
                    .click();
                cy.findByRole('button', { 'name': 'Submit' }).click();
                cy.findByRole('link', { 'name': 'My test form' }).click();

                // Verify actor in ticket
                cy.findByRole('region', { 'name': "Actors" }).within(() => {
                    cy.get(`select[data-actor-type="${actorConfig.dataAttr}"]`).contains('E2E Tests');
                });
            });
        });
    });
});
