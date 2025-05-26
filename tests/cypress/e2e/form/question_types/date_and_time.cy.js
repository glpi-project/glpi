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

describe('Date and Time form question type', () => {
    beforeEach(() => {
        cy.createWithAPI('Glpi\\Form\\Form', {
            'name': 'Tests form for the date and time form question type suite',
        }).as('form_id');

        cy.login();
        cy.changeProfile('Super-Admin', true);

        cy.get('@form_id').then((form_id) => {
            const tab = 'Glpi\\Form\\Form$main';
            cy.visit(`/front/form/form.form.php?id=${form_id}&forcetab=${tab}`);

            // Add a question
            cy.findByRole("button", { name: "Add a question" }).should('exist').click();

            // Set the question name
            cy.findByRole("textbox", { name: "Question name" }).should('exist').type("Test Date and Time question");

            // Change question type
            cy.getDropdownByLabelText('Question type').selectDropdownValue('Date and time');
        });
    });


    it('check behavior of the date and time question type', () => {
        cy.findByRole('region', { name: 'Question details' }).within(() => {
            // Check the checkboxes
            cy.findByRole('checkbox', { name: 'Date' }).should('be.checked');
            cy.findByRole('checkbox', { name: 'Time' }).should('not.be.checked');
            cy.findByRole('checkbox', { name: 'Current date' }).should('not.be.checked');

            // Check the input type
            cy.findByLabelText('Default value').should('have.attr', 'type', 'date');

            // Switch to time
            cy.findByRole('checkbox', { name: 'Time' }).check();

            // Check the checkboxes
            cy.findByRole('checkbox', { name: 'Date' }).should('be.checked');
            cy.findByRole('checkbox', { name: 'Time' }).should('be.checked');
            cy.findByRole('checkbox', { name: 'Current date and time' }).should('not.be.checked');

            // Check the input type
            cy.findByLabelText('Default value').should('have.attr', 'type', 'datetime-local');

            // Uncheck the date
            cy.findByRole('checkbox', { name: 'Date' }).uncheck();

            // Check the checkboxes
            cy.findByRole('checkbox', { name: 'Date' }).should('not.be.checked');
            cy.findByRole('checkbox', { name: 'Time' }).should('be.checked');
            cy.findByRole('checkbox', { name: 'Current time' }).should('not.be.checked');

            // Check the input type
            cy.findByLabelText('Default value').should('have.attr', 'type', 'time');

            // Switch to current date
            cy.findByRole('checkbox', { name: 'Current time' }).check();

            // Check the input type
            cy.findByLabelText('Default value').should('have.attr', 'type', 'text').should('have.be.disabled');

            // Uncheck the time
            cy.findByRole('checkbox', { name: 'Time' }).uncheck();

            // Check the checkboxes
            cy.findByRole('checkbox', { name: 'Date' }).should('be.checked');
            cy.findByRole('checkbox', { name: 'Time' }).should('not.be.checked');
            cy.findByRole('checkbox', { name: 'Current date' }).should('be.checked');

            // Check the input type
            cy.findByLabelText('Default value').should('have.attr', 'type', 'text').should('have.be.disabled');
        });
    });

    it('check date and time value conversions', () => {
        cy.findByRole('region', { name: 'Question details' }).within(() => {
            // Start with date input (default)
            cy.findByRole('checkbox', { name: 'Date' }).should('be.checked');
            cy.findByRole('checkbox', { name: 'Time' }).should('not.be.checked');

            // Set a date value
            cy.findByLabelText('Default value').clear();
            cy.findByLabelText('Default value').type('2023-12-15');

            // Add time - should convert to datetime-local
            cy.findByRole('checkbox', { name: 'Time' }).check();
            cy.findByLabelText('Default value').should('have.attr', 'type', 'datetime-local')
                .invoke('val').should('match', /^2023-12-15T\d{2}:\d{2}$/);

            // Set a specific time with the date
            cy.findByLabelText('Default value').clear();
            cy.findByLabelText('Default value').type('2023-12-15T14:30');

            // Remove date - should convert to time only
            cy.findByRole('checkbox', { name: 'Date' }).uncheck();
            cy.findByLabelText('Default value').should('have.attr', 'type', 'time')
                .should('have.value', '14:30');

            // Add date back - should convert back to datetime-local
            cy.findByRole('checkbox', { name: 'Date' }).check();
            cy.findByLabelText('Default value').should('have.attr', 'type', 'datetime-local')
                .invoke('val').should('match', /^\d{4}-\d{2}-\d{2}T14:30$/);

            // Remove time - should convert to date only
            cy.findByRole('checkbox', { name: 'Time' }).uncheck();
            cy.findByLabelText('Default value').should('have.attr', 'type', 'date')
                .invoke('val').should('match', /^\d{4}-\d{2}-\d{2}$/);

            // Check "Current date" - should disable the input
            cy.findByRole('checkbox', { name: 'Current date' }).check();
            cy.findByLabelText('Default value').should('be.disabled');

            // Uncheck "Current date" and enable Time
            cy.findByRole('checkbox', { name: 'Current date' }).uncheck();
            cy.findByRole('checkbox', { name: 'Time' }).check();

            // Set datetime value then check "Current date and time" - should disable the input
            cy.findByLabelText('Default value').clear();
            cy.findByLabelText('Default value').type('2023-12-15T16:45');
            cy.findByRole('checkbox', { name: 'Current date and time' }).check();
            cy.findByLabelText('Default value').should('be.disabled');
        });
    });
});
