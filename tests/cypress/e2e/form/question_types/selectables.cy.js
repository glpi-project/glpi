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

describe('Selectable form question types', () => {
    beforeEach(() => {
        cy.createWithAPI('Glpi\\Form\\Form', {
            'name': 'Tests form for the selectable form question types suite',
        }).as('form_id');

        cy.login();
        cy.changeProfile('Super-Admin');

        cy.get('@form_id').then((form_id) => {
            const tab = 'Glpi\\Form\\Form$main';
            cy.visit(`/front/form/form.form.php?id=${form_id}&forcetab=${tab}`);

            // Add a question
            cy.findByRole("button", { name: "Add a question" }).should('exist').click();
        });
    });

    it('should configure a radio question type', () => {
        describe('Configure question', () => {
            // Change the question type
            cy.getDropdownByLabelText('Question type').select('Radio');

            // Set the question name
            cy.findByRole("textbox", { name: "Question name" }).should('exist').type("Test selectable question");

            // Add options
            for (let index = 0; index < 3; index++) {
                cy.findAllByRole('radio', { name: 'Default option' }).eq(index).should('exist').should('be.disabled');
                cy.findAllByRole('textbox', { name: 'Selectable option' }).eq(index).type(`Option ${index}`);
            }

            // Save the form
            cy.findByRole("button", { name: "Save" }).click();

            // Reload the page
            cy.reload();

            // Check if options are still there
            for (let index = 0; index < 3; index++) {
                cy.findAllByRole('radio', { name: 'Default option' }).eq(index).should('exist').should('not.be.checked');
                cy.findAllByRole('textbox', { name: 'Selectable option' }).eq(index).should('have.value', `Option ${index}`);
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
                cy.findByRole('radio', { name: `Option ${index}` }).should('exist');
            }

            // Check if the second option is checked
            cy.findByRole('radio', { name: 'Option 1' }).should('be.checked');

            // Check the first option
            cy.findByRole('radio', { name: 'Option 0' }).check();

            // Check if the second option is not checked anymore
            cy.findByRole('radio', { name: 'Option 1' }).should('not.be.checked');

            // Submit the form
            cy.findByRole("button", { name: "Submit" }).click();

            // Check if the success message is displayed
            cy.findByRole('alert').should('exist').should('contain.text', 'Item successfully created')
                .find('a').should('exist').click();

            // Check if the option is saved
            cy.findByText(': Option 0').should('exist');
        });
    });

    it('should configure a checkbox question type', () => {
        describe('Configure question', () => {
            // Change the question type
            cy.getDropdownByLabelText('Question type').select('Checkbox');

            // Set the question name
            cy.findByRole("textbox", { name: "Question name" }).should('exist').type("Test selectable question");

            // Add options
            for (let index = 0; index < 3; index++) {
                cy.findAllByRole('checkbox', { name: 'Default option' }).eq(index).should('exist').should('be.disabled');
                cy.findAllByRole('textbox', { name: 'Selectable option' }).eq(index).type(`Option ${index}`);
            }

            // Save the form
            cy.findByRole("button", { name: "Save" }).click();

            // Reload the page
            cy.reload();

            // Check if options are still there
            for (let index = 0; index < 3; index++) {
                cy.findAllByRole('checkbox', { name: 'Default option' }).eq(index).should('exist').should('not.be.checked');
                cy.findAllByRole('textbox', { name: 'Selectable option' }).eq(index).should('have.value', `Option ${index}`);
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
                cy.findByRole('checkbox', { name: `Option ${index}` }).should('exist');
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
            cy.findByRole("button", { name: "Submit" }).click();

            // Check if the success message is displayed
            cy.findByRole('alert').should('exist').should('contain.text', 'Item successfully created')
                .find('a').should('exist').click();

            // Check if the option is saved
            cy.findByText(': Option 0, Option 1').should('exist');
        });
    });

    it('test can duplicate a radio question', () => {
        // Set the question name
        cy.findByRole("textbox", { name: "Question name" }).should('exist').type("Test radio question");

        // Change question type
        cy.findByRole("combobox", { name: "Short answer" }).should('exist').select("Radio");

        // Add a option
        cy.findAllByRole("textbox", { name: "Selectable option"}).eq(0).should('exist').type("Option 1");

        // Add a option
        cy.findAllByRole("textbox", { name: "Selectable option"}).eq(1).should('exist').type("Option 2");

        // Define second option as default
        cy.findAllByRole("radio", { name: "Default option"}).eq(1).should('exist').check();

        // Duplicate the question
        cy.findByRole("button", { name: "Duplicate question" }).should('exist').click();

        // Check the source question
        cy.findAllByRole("region", { name: "Question details" }).eq(0).should('exist').as('source_question');
        cy.get('@source_question').findByRole("textbox", { name: "Question name" }).should('exist').should('have.value', "Test radio question");
        cy.get('@source_question').findAllByRole("textbox", { name: "Selectable option"}).eq(0).should('exist').should('have.value', "Option 1");
        cy.get('@source_question').findAllByRole("textbox", { name: "Selectable option"}).eq(1).should('exist').should('have.value', "Option 2");
        cy.get('@source_question').findAllByRole("radio", { name: "Default option"}).eq(1).should('exist').should('be.checked');

        // Check the duplicated question
        cy.findAllByRole("region", { name: "Question details" }).eq(1).should('exist').as('duplicated_question');
        cy.get('@duplicated_question').findByRole("textbox", { name: "Question name" }).should('exist').should('have.value', "Test radio question");
        cy.get('@duplicated_question').findAllByRole("textbox", { name: "Selectable option"}).eq(0).should('exist').should('have.value', "Option 1");
        cy.get('@duplicated_question').findAllByRole("textbox", { name: "Selectable option"}).eq(1).should('exist').should('have.value', "Option 2");
        cy.get('@duplicated_question').findAllByRole("radio", { name: "Default option"}).eq(1).should('exist').should('be.checked');

        // Define first option as default for the duplicated question
        cy.get('@duplicated_question').findAllByRole("radio", { name: "Default option"}).eq(0).should('exist').check();

        // Check the source question
        cy.get('@source_question').findAllByRole("radio", { name: "Default option"}).eq(0).should('exist').should('not.be.checked');
        cy.get('@source_question').findAllByRole("radio", { name: "Default option"}).eq(1).should('exist').should('be.checked');

        // Save the form
        cy.findByRole("button", { name: "Save" }).should('exist').click();

        // Reload the form
        cy.reload();

        // Check options for the source question
        cy.findAllByRole("option", { name: "Test radio question" }).eq(0).should('exist').as('source_question');
        cy.get('@source_question').findAllByRole("textbox", { name: "Selectable option" }).eq(0).should('exist').should('have.value', "Option 1");
        cy.get('@source_question').findAllByRole("textbox", { name: "Selectable option" }).eq(1).should('exist').should('have.value', "Option 2");
        cy.get('@source_question').findAllByRole("radio", { name: "Default option" }).eq(0).should('not.be.checked');
        cy.get('@source_question').findAllByRole("radio", { name: "Default option" }).eq(1).should('be.checked');

        // Check options for the duplicated question
        cy.findAllByRole("option", { name: "Test radio question" }).eq(1).should('exist').as('duplicated_question');
        cy.get('@duplicated_question').findAllByRole("textbox", { name: "Selectable option" }).eq(0).should('exist').should('have.value', "Option 1");
        cy.get('@duplicated_question').findAllByRole("textbox", { name: "Selectable option" }).eq(1).should('exist').should('have.value', "Option 2");
        cy.get('@duplicated_question').findAllByRole("radio", { name: "Default option" }).eq(0).should('be.checked');
        cy.get('@duplicated_question').findAllByRole("radio", { name: "Default option" }).eq(1).should('not.be.checked');
    });

    it('test can duplicate a checkbox question', () => {
        // Set the question name
        cy.findByRole("textbox", { name: "Question name" }).should('exist').type("Test checkbox question");

        // Change question type
        cy.findByRole("combobox", { name: "Short answer" }).should('exist').select("Checkbox");

        // Add a option
        cy.findAllByRole("textbox", { name: "Selectable option"}).eq(0).should('exist').type("Option 1");

        // Add a option
        cy.findAllByRole("textbox", { name: "Selectable option"}).eq(1).should('exist').type("Option 2");

        // Add a option
        cy.findAllByRole("textbox", { name: "Selectable option"}).eq(2).should('exist').type("Option 3");

        // Define second option as default
        cy.findAllByRole("checkbox", { name: "Default option"}).eq(1).should('exist').check();

        // Define third option as default
        cy.findAllByRole("checkbox", { name: "Default option"}).eq(2).should('exist').check();

        // Duplicate the question
        cy.findByRole("button", { name: "Duplicate question" }).should('exist').click();

        // Check the source question
        cy.findAllByRole("region", { name: "Question details" }).eq(0).should('exist').as('source_question');
        cy.get('@source_question').findByRole("textbox", { name: "Question name" }).should('exist').should('have.value', "Test checkbox question");
        cy.get('@source_question').findAllByRole("textbox", { name: "Selectable option"}).eq(0).should('exist').should('have.value', "Option 1");
        cy.get('@source_question').findAllByRole("textbox", { name: "Selectable option"}).eq(1).should('exist').should('have.value', "Option 2");
        cy.get('@source_question').findAllByRole("textbox", { name: "Selectable option"}).eq(2).should('exist').should('have.value', "Option 3");
        cy.get('@source_question').findAllByRole("checkbox", { name: "Default option"}).eq(1).should('exist').should('be.checked');
        cy.get('@source_question').findAllByRole("checkbox", { name: "Default option"}).eq(2).should('exist').should('be.checked');

        // Check the duplicated question
        cy.findAllByRole("region", { name: "Question details" }).eq(1).should('exist').as('duplicated_question');
        cy.get('@duplicated_question').findByRole("textbox", { name: "Question name" }).should('exist').should('have.value', "Test checkbox question");
        cy.get('@duplicated_question').findAllByRole("textbox", { name: "Selectable option"}).eq(0).should('exist').should('have.value', "Option 1");
        cy.get('@duplicated_question').findAllByRole("textbox", { name: "Selectable option"}).eq(1).should('exist').should('have.value', "Option 2");
        cy.get('@duplicated_question').findAllByRole("textbox", { name: "Selectable option"}).eq(2).should('exist').should('have.value', "Option 3");
        cy.get('@duplicated_question').findAllByRole("checkbox", { name: "Default option"}).eq(1).should('exist').should('be.checked');
        cy.get('@duplicated_question').findAllByRole("checkbox", { name: "Default option"}).eq(2).should('exist').should('be.checked');

        // Define first option as default for the duplicated question
        cy.get('@duplicated_question').findAllByRole("checkbox", { name: "Default option"}).eq(0).should('exist').check();

        // Check the source question
        cy.get('@source_question').findAllByRole("checkbox", { name: "Default option"}).eq(0).should('exist').should('not.be.checked');
        cy.get('@source_question').findAllByRole("checkbox", { name: "Default option"}).eq(1).should('exist').should('be.checked');
        cy.get('@source_question').findAllByRole("checkbox", { name: "Default option"}).eq(2).should('exist').should('be.checked');

        // Save the form
        cy.findByRole("button", { name: "Save" }).should('exist').click();

        // Reload the form
        cy.reload();

        // Check options for the source question
        cy.findAllByRole("option", { name: "Test checkbox question" }).eq(0).should('exist').as('source_question');
        cy.get('@source_question').findAllByRole("textbox", { name: "Selectable option" }).eq(0).should('exist').should('have.value', "Option 1");
        cy.get('@source_question').findAllByRole("textbox", { name: "Selectable option" }).eq(1).should('exist').should('have.value', "Option 2");
        cy.get('@source_question').findAllByRole("textbox", { name: "Selectable option" }).eq(2).should('exist').should('have.value', "Option 3");
        cy.get('@source_question').findAllByRole("checkbox", { name: "Default option" }).eq(0).should('not.be.checked');
        cy.get('@source_question').findAllByRole("checkbox", { name: "Default option" }).eq(1).should('be.checked');
        cy.get('@source_question').findAllByRole("checkbox", { name: "Default option" }).eq(2).should('be.checked');

        // Check options for the duplicated question
        cy.findAllByRole("option", { name: "Test checkbox question" }).eq(1).should('exist').as('duplicated_question');
        cy.get('@duplicated_question').findAllByRole("textbox", { name: "Selectable option" }).eq(0).should('exist').should('have.value', "Option 1");
        cy.get('@duplicated_question').findAllByRole("textbox", { name: "Selectable option" }).eq(1).should('exist').should('have.value', "Option 2");
        cy.get('@duplicated_question').findAllByRole("textbox", { name: "Selectable option" }).eq(2).should('exist').should('have.value', "Option 3");
        cy.get('@duplicated_question').findAllByRole("checkbox", { name: "Default option" }).eq(0).should('be.checked');
        cy.get('@duplicated_question').findAllByRole("checkbox", { name: "Default option" }).eq(1).should('be.checked');
        cy.get('@duplicated_question').findAllByRole("checkbox", { name: "Default option" }).eq(2).should('be.checked');
    });

    it('test can duplicate a dropdown question', () => {
        // Set the question name
        cy.findByRole("textbox", { name: "Question name" }).should('exist').type("Test dropdown question");

        // Change question type
        cy.findByRole("combobox", { name: "Short answer" }).should('exist').select("Dropdown");

        // Add a option
        cy.findAllByRole("textbox", { name: "Selectable option"}).eq(0).should('exist').type("Option 1");

        // Add a option
        cy.findAllByRole("textbox", { name: "Selectable option"}).eq(1).should('exist').type("Option 2");

        // Define second option as default
        cy.getDropdownByLabelText("Default option").selectDropdownValue("Option 2");

        // Duplicate the question
        cy.findByRole("button", { name: "Duplicate question" }).should('exist').click();

        // Check the source question
        cy.findAllByRole("region", { name: "Question details" }).eq(0).should('exist').as('source_question');
        cy.get('@source_question').findByRole("textbox", { name: "Question name" }).should('exist').should('have.value', "Test dropdown question").click();
        cy.get('@source_question').findAllByRole("textbox", { name: "Selectable option"}).eq(0).should('exist').should('have.value', "Option 1");
        cy.get('@source_question').findAllByRole("textbox", { name: "Selectable option"}).eq(1).should('exist').should('have.value', "Option 2");
        cy.get('@source_question').getDropdownByLabelText("Default option").should('exist').findByRole("textbox", { name: "Option 2" }).should('exist');

        // Check the duplicated question
        cy.findAllByRole("region", { name: "Question details" }).eq(1).should('exist').as('duplicated_question');
        cy.get('@duplicated_question').findByRole("textbox", { name: "Question name" }).should('exist').should('have.value', "Test dropdown question").click();
        cy.get('@duplicated_question').findAllByRole("textbox", { name: "Selectable option"}).eq(0).should('exist').should('have.value', "Option 1");
        cy.get('@duplicated_question').findAllByRole("textbox", { name: "Selectable option"}).eq(1).should('exist').should('have.value', "Option 2");
        cy.get('@duplicated_question').getDropdownByLabelText("Default option").should('have.text', "Option 2");

        // Define first option as default for the duplicated question
        cy.get('@duplicated_question').getDropdownByLabelText("Default option").selectDropdownValue("Option 1");

        // Check the source question
        cy.get('@source_question').getDropdownByLabelText("Default option").should('have.text', "Option 2");

        // Save the form
        cy.findByRole("button", { name: "Save" }).should('exist').click();
    });
});
