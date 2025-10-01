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

describe('Convert default value form', () => {
    beforeEach(() => {
        cy.createWithAPI('Glpi\\Form\\Form', {
            'name': 'Tests convert default values between different question types for the form suite',
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

    it('test convert default value between short text and email types', () => {
        // Check if current sub type is "Text"
        cy.findByRole('combobox', { name: 'Text' }).should('exist');

        // Set defaut value
        cy.findByRole('textbox', { name: 'Default value' }).type('Default value for short text');

        // Change sub type to "Emails"
        cy.findByRole('combobox', { name: 'Text' }).select('Emails');

        // Check if default value has been converted
        cy.findByRole('textbox', { name: 'Default value' }).should('have.value', 'Default value for short text');
    });

    it('test convert default value between email and short text types', () => {
        // Change sub type to "Emails"
        cy.findByRole('combobox', { name: 'Text' }).select('Emails');

        // Set defaut value
        cy.findByRole('textbox', { name: 'Default value' }).type('Default value for short text');

        // Change sub type to "Text"
        cy.findByRole('combobox', { name: 'Emails' }).select('Text');

        // Check if default value has been converted
        cy.findByRole('textbox', { name: 'Default value' }).should('have.value', 'Defaultvalueforshorttext');
    });

    it('test convert default value between short text and long text types', () => {
        // Check if current sub type is "Text"
        cy.findByRole('combobox', { name: 'Text' }).should('exist');

        // Set defaut value
        cy.findByRole('textbox', { name: 'Default value' }).type('Default value for short text');

        // Change type to "Long answer"
        cy.findByRole('option', {'name': 'New question'}).changeQuestionType('Long answer');

        // Check if default value has been converted
        cy.findByRole('region', {'name': 'Question details'}).within(() => {
            cy.findByLabelText("Default value")
                .awaitTinyMCE()
                .should('have.text', 'Default value for short text');
        });
    });

    it('test convert default value between long text and short text types', () => {
        const default_value = 'This is a much longer default value for short text. It contains multiple lines and line breaks.\nLine 1\nLine 2\nLine 3';

        // Change type to "Long answer"
        cy.findByRole('option', {'name': 'New question'}).changeQuestionType('Long answer');

        // Set defaut value
        cy.findByRole('region', {'name': 'Question details'}).within(() => {
            cy.findByLabelText("Default value")
                .awaitTinyMCE()
                .type(default_value);
        });

        // Change type to "Short answer"
        cy.findByRole('option', {'name': 'New question'}).changeQuestionType('Short answer');

        // Check if the current sub type is "Text"
        cy.findByRole('combobox', { name: 'Text' }).should('exist');

        // Check if default value has been converted
        cy.findByRole('textbox', { name: 'Default value' }).should('have.value', default_value.replace(/\n/g, ''));
    });

    it('test convert default value between radio and checkbox types', () => {
        // Start with a "Radio" question type
        cy.getDropdownByLabelText('Question type').selectDropdownValue('Radio');

        // Add some options and check the second one as default
        cy.findByRole('textbox', { name: 'Selectable option' }).type('Option 1');
        cy.findAllByRole('textbox', { name: 'Selectable option' }).eq(1).type('Option 2');
        cy.findAllByRole('textbox', { name: 'Selectable option' }).eq(2).type('Option 3');

        // Check the second option
        cy.findAllByRole('radio', { name: 'Default option' }).eq(1).check();

        // Switch to Checkbox type
        cy.getDropdownByLabelText('Question type').selectDropdownValue('Checkbox');

        // The second option should remain checked and inputs should now be checkboxes
        cy.findAllByRole('checkbox', { name: 'Default option' }).eq(1).should('be.checked');
        cy.findAllByRole('checkbox', { name: 'Default option' }).should('have.length', 4);
        cy.findAllByRole('checkbox', { name: 'Default option' }).eq(3).should('be.disabled');

        // Switch back to radio type
        cy.getDropdownByLabelText('Question type').selectDropdownValue('Radio');

        // Verify the second option is still checked and inputs are back to radio
        cy.findAllByRole('radio', { name: 'Default option' }).eq(1).should('be.checked');
        cy.findAllByRole('radio', { name: 'Default option' }).should('have.length', 4);
        cy.findAllByRole('radio', { name: 'Default option' }).eq(3).should('be.disabled');

        // Save the form
        cy.findByRole('button', { name: 'Save' }).click();

        // Reload the page
        cy.reload();

        // Check if the default value is still the same
        cy.findAllByRole('radio', { name: 'Default option' }).eq(1).should('be.checked');
        cy.findAllByRole('radio', { name: 'Default option' }).should('have.length', 3);
        cy.findAllByRole('radio', { name: 'Default option' }).should('not.be.disabled');
        cy.findAllByRole('textbox', { name: 'Selectable option' }).should('not.have.value', '');
    });

    it('test convert default value between checkbox and dropdown type', () => {
        // Start with a "Checkbox" question type
        cy.getDropdownByLabelText('Question type').selectDropdownValue('Checkbox');

        // Add some options and check the second one as default
        cy.findByRole('textbox', { name: 'Selectable option' }).type('Option 1');
        cy.findAllByRole('textbox', { name: 'Selectable option' }).eq(1).type('Option 2');
        cy.findAllByRole('textbox', { name: 'Selectable option' }).eq(2).type('Option 3');

        // Check the second option
        cy.findAllByRole('checkbox', { name: 'Default option' }).eq(1).check();

        // Switch to Dropdown type
        cy.getDropdownByLabelText('Question type').selectDropdownValue('Dropdown');

        // The second option should remain checked and inputs should now be checkboxes
        cy.getDropdownByLabelText('Default option').should('have.text', 'Option 2');

        // Switch back to checkbox type
        cy.getDropdownByLabelText('Question type').selectDropdownValue('Checkbox');

        // Verify the second option is still checked and inputs are back to radio
        cy.findAllByRole('checkbox', { name: 'Default option' }).eq(1).should('be.checked');
        cy.findAllByRole('checkbox', { name: 'Default option' }).should('have.length', 4);
        cy.findAllByRole('checkbox', { name: 'Default option' }).eq(3).should('be.disabled');

        // Save the form
        cy.findByRole('button', { name: 'Save' }).click();

        // Reload the page
        cy.reload();

        // Check if the default value is still the same
        cy.findAllByRole('checkbox', { name: 'Default option' }).eq(1).should('be.checked');
        cy.findAllByRole('checkbox', { name: 'Default option' }).should('have.length', 3);
        cy.findAllByRole('checkbox', { name: 'Default option' }).should('not.be.disabled');
        cy.findAllByRole('textbox', { name: 'Selectable option' }).should('not.have.value', '');
    });

    it('test convert default value between dropdown and radio type', () => {
        // Start with a "Dropdown" question type
        cy.getDropdownByLabelText('Question type').selectDropdownValue('Dropdown');

        // Add some options and check the second one as default
        cy.findByRole('textbox', { name: 'Selectable option' }).type('Option 1');
        cy.findAllByRole('textbox', { name: 'Selectable option' }).eq(1).type('Option 2');
        cy.findAllByRole('textbox', { name: 'Selectable option' }).eq(2).type('Option 3');

        // Check the second option
        cy.getDropdownByLabelText('Default option').selectDropdownValue('Option 2');

        // Switch to Radio type
        cy.getDropdownByLabelText('Question type').selectDropdownValue('Radio');

        // The second option should remain checked and inputs should now be checkboxes
        cy.findAllByRole('radio', { name: 'Default option' }).eq(1).should('be.checked');
        cy.findAllByRole('radio', { name: 'Default option' }).should('have.length', 4);
        cy.findAllByRole('radio', { name: 'Default option' }).eq(3).should('be.disabled');

        // Switch back to dropdown type
        cy.getDropdownByLabelText('Question type').selectDropdownValue('Dropdown');

        // Verify the second option is still checked and inputs are back to radio
        cy.getDropdownByLabelText('Default option').should('have.text', 'Option 2');

        // Save the form
        cy.findByRole('button', { name: 'Save' }).click();

        // Reload the page
        cy.reload();

        // Check if the default value is still the same
        cy.getDropdownByLabelText('Default option').should('have.text', 'Option 2');
    });
});
