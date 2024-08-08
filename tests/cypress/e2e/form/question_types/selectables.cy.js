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

describe('Selectable form question types', () => {
    beforeEach(() => {
        cy.createWithAPI('Glpi\\Form\\Form', {
            'name': 'Tests form for the selectable form question types suite',
        }).as('form_id');

        cy.login();
        cy.changeProfile('Super-Admin', true);

        cy.get('@form_id').then((form_id) => {
            const tab = 'Glpi\\Form\\Form$main';
            cy.visit(`/front/form/form.form.php?id=${form_id}&forcetab=${tab}`);

            // Add a new question
            cy.findByRole("button", { name: "Add a new question" }).should('exist').click();

            // Set the question name
            cy.findByRole("textbox", { name: "Question name" }).should('exist').type("Test selectable question");
        });
    });

    it('should configure a radio question type', () => {
        describe('Configure question', () => {
            // Change the question type
            cy.getDropdownByLabelText('Question type').select('Radio');

            // Add options
            for (let index = 0; index < 3; index++) {
                cy.findAllByRole('radio', { name: 'Default option' }).eq(index).should('exist').should('be.disabled');
                cy.findAllByRole('textbox', { name: 'Selectable option' }).eq(index).type('Option ' + index);
            }

            // Save the form
            cy.findByRole("button", { name: "Save" }).click();

            // Reload the page
            cy.reload();

            // Check if options are still there
            for (let index = 0; index < 3; index++) {
                cy.findAllByRole('radio', { name: 'Default option' }).eq(index).should('exist').should('not.be.checked');
                cy.findAllByRole('textbox', { name: 'Selectable option' }).eq(index).should('have.value', 'Option ' + index);
            }

            // Check the second option
            cy.findAllByRole('radio', { name: 'Default option' }).eq(1).check();

            // Save the form
            cy.findByRole("button", { name: "Save" }).click();

            // Reload the page
            cy.reload();

            // Check if the second option is still checked
            cy.findAllByRole('radio', { name: 'Default option' }).eq(1).should('be.checked');
        });


        describe('Fill form', () => {
            // Go to preview page (remove the target="_blank" attribute to stay in the same window)
            cy.findByRole("link", { name: "Preview" })
                .invoke('attr', 'target', '_self')
                .click();

            // Check if the question is displayed
            cy.findByText('Test selectable question').should('exist');

            // Check if the options are displayed
            for (let index = 0; index < 3; index++) {
                cy.findByRole('radio', { name: 'Option ' + index }).should('exist');
            }

            // Check if the second option is checked
            cy.findByRole('radio', { name: 'Option 1' }).should('be.checked');

            // Check the first option
            cy.findByRole('radio', { name: 'Option 0' }).check();

            // Check if the second option is not checked anymore
            cy.findByRole('radio', { name: 'Option 1' }).should('not.be.checked');

            // Submit the form
            cy.findByRole("button", { name: "Send form" }).click();

            // Check if the success message is displayed
            cy.findByRole('alert').should('exist').should('contain.text', 'Item successfully created')
                .find('a').should('exist').click();

            // Check if the option is saved
            cy.findByText('Option 0').should('exist');
            cy.findByText('Option 1').should('not.exist');
            cy.findByText('Option 2').should('not.exist');
        });
    });

    it('should configure a checkbox question type', () => {
        describe('Configure question', () => {
            // Change the question type
            cy.getDropdownByLabelText('Question type').select('Checkbox');

            // Add options
            for (let index = 0; index < 3; index++) {
                cy.findAllByRole('checkbox', { name: 'Default option' }).eq(index).should('exist').should('be.disabled');
                cy.findAllByRole('textbox', { name: 'Selectable option' }).eq(index).type('Option ' + index);
            }

            // Save the form
            cy.findByRole("button", { name: "Save" }).click();

            // Reload the page
            cy.reload();

            // Check if options are still there
            for (let index = 0; index < 3; index++) {
                cy.findAllByRole('checkbox', { name: 'Default option' }).eq(index).should('exist').should('not.be.checked');
                cy.findAllByRole('textbox', { name: 'Selectable option' }).eq(index).should('have.value', 'Option ' + index);
            }

            // Check the second and third options
            cy.findAllByRole('checkbox', { name: 'Default option' }).eq(1).check();
            cy.findAllByRole('checkbox', { name: 'Default option' }).eq(2).check();

            // Save the form
            cy.findByRole("button", { name: "Save" }).click();

            // Reload the page
            cy.reload();

            // Check if the second option is still checked
            cy.findAllByRole('checkbox', { name: 'Default option' }).eq(1).should('be.checked');
        });

        describe('Fill form', () => {
            // Go to preview page (remove the target="_blank" attribute to stay in the same window)
            cy.findByRole("link", { name: "Preview" })
                .invoke('attr', 'target', '_self')
                .click();

            // Check if the question is displayed
            cy.findByText('Test selectable question').should('exist');

            // Check if the options are displayed
            for (let index = 0; index < 3; index++) {
                cy.findByRole('checkbox', { name: 'Option ' + index }).should('exist');
            }

            // Check if the second and third options are checked
            cy.findByRole('checkbox', { name: 'Option 1' }).should('be.checked');
            cy.findByRole('checkbox', { name: 'Option 2' }).should('be.checked');

            // Check the first option
            cy.findByRole('checkbox', { name: 'Option 0' }).check();

            // Uncheck the third option
            cy.findByRole('checkbox', { name: 'Option 2' }).uncheck();

            // Check if the second option is still checked
            cy.findByRole('checkbox', { name: 'Option 1' }).should('be.checked');

            // Submit the form
            cy.findByRole("button", { name: "Send form" }).click();

            // Check if the success message is displayed
            cy.findByRole('alert').should('exist').should('contain.text', 'Item successfully created')
                .find('a').should('exist').click();

            // Check if the option is saved
            cy.findByText('Option 0').should('exist');
            cy.findByText('Option 1').should('exist');
            cy.findByText('Option 2').should('not.exist');
        });
    });
});
