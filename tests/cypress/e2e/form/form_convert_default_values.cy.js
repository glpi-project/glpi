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

describe('Convert default value form', () => {
    beforeEach(() => {
        cy.createWithAPI('Glpi\\Form\\Form', {
            'name': 'Tests convert default values between different question types for the form suite',
        }).as('form_id');

        cy.login();
        cy.changeProfile('Super-Admin', true);

        cy.get('@form_id').then((form_id) => {
            const tab = 'Glpi\\Form\\Form$main';
            cy.visit(`/front/form/form.form.php?id=${form_id}&forcetab=${tab}`);

            // Add a new question
            cy.findByRole("button", { name: "Add a new question" }).should('exist').click();
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

        // Change sub type to "Emails"
        cy.findByRole('combobox', { name: 'Short answer' }).select('Long answer');

        // Check if default value has been converted
        cy.findByRole('region', {'name': 'Question details'}).within(() => {
            cy.findByLabelText("Default value")
                .awaitTinyMCE()
                .should('have.text', 'Default value for short text');
        });
    });

    it('test convert default value between long text and short text types', () => {
        const default_value = 'This is a much longer default value for short text. It contains multiple lines and line breaks.\nLine 1\nLine 2\nLine 3';

        // Change sub type to "Emails"
        cy.findByRole('combobox', { name: 'Short answer' }).select('Long answer');

        // Set defaut value
        cy.findByRole('region', {'name': 'Question details'}).within(() => {
            cy.findByLabelText("Default value")
                .awaitTinyMCE()
                .type(default_value);
        });

        // Change type to "Text"
        cy.findByRole('combobox', { name: 'Long answer' }).select('Short answer');

        // Check if the current sub type is "Text"
        cy.findByRole('combobox', { name: 'Text' }).should('exist');

        // Check if default value has been converted
        cy.findByRole('textbox', { name: 'Default value' }).should('have.value', default_value.replace(/\n/g, ''));
    });
});
