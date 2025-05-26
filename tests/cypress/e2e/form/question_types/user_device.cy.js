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

describe('User device form question type', () => {
    beforeEach(() => {
        cy.createWithAPI('Glpi\\Form\\Form', {
            'name': 'Tests form for the user device form question type suite',
        }).as('form_id');

        cy.login();
        cy.changeProfile('Super-Admin', true);

        cy.get('@form_id').then((form_id) => {
            const tab = 'Glpi\\Form\\Form$main';
            cy.visit(`/front/form/form.form.php?id=${form_id}&forcetab=${tab}`);

            // Add a question
            cy.findByRole("button", { name: "Add a question" }).should('exist').click();

            // Set the question name
            cy.findByRole("textbox", { name: "Question name" }).should('exist').type("Test user device question");

            // Change question type
            cy.getDropdownByLabelText('Question type').selectDropdownValue('Item');

            // Define question sub type
            cy.getDropdownByLabelText('Question sub type').selectDropdownValue('User Devices');
        });
    });

    it('should be able to switch between multiple devices and single device', () => {
        cy.findByRole('region', { name: 'Question details' }).within(() => {
            // Double switch
            cy.findByRole('checkbox', { name: 'Allow multiple devices' }).check();
            cy.findByRole('checkbox', { name: 'Allow multiple devices' }).uncheck();

            cy.getDropdownByLabelText('Select device...').closest('.devices-dropdown').find('select').should('exist').should('be.disabled');
            cy.getDropdownByLabelText('Select devices...').should('not.exist');
        });

        // Save
        cy.findByRole('button', { name: 'Save' }).click();

        // Reload the page
        cy.reload();

        // Focus on the question
        cy.findByRole('option', { name: 'Test user device question' }).click();

        cy.findByRole('region', { name: 'Question details' }).within(() => {
            // Check the switch
            cy.findByRole('checkbox', { name: 'Allow multiple devices' }).should('not.be.checked');

            // Check the dropdowns
            cy.getDropdownByLabelText('Select device...').closest('.devices-dropdown').find('select').should('exist').should('be.disabled');
            cy.getDropdownByLabelText('Select devices...').should('not.exist');

            // Switch to multiple devices
            cy.findByRole('checkbox', { name: 'Allow multiple devices' }).check();

            // Check the dropdowns
            cy.getDropdownByLabelText('Select device...').should('not.exist');
            cy.getDropdownByLabelText('Select devices...').closest('.devices-dropdown').find('select').should('exist').should('be.disabled');

        });

        // Save
        cy.findByRole('button', { name: 'Save' }).click();

        // Reload the page
        cy.reload();

        // Focus on the question
        cy.findByRole('option', { name: 'Test user device question' }).click();

        cy.findByRole('region', { name: 'Question details' }).within(() => {
            // Check the dropdowns
            cy.getDropdownByLabelText('Select device...').should('not.exist');
            cy.getDropdownByLabelText('Select devices...').closest('.devices-dropdown').find('select').should('exist').should('be.disabled');

            // Check the switch
            cy.findByRole('checkbox', { name: 'Allow multiple devices' }).should('be.checked');
        });
    });

    it('can duplicate a single device question', () => {
        // Duplicate the question
        cy.findByRole('button', {'name': "Duplicate question"}).click();

        cy.findAllByRole('option', {'name': 'New question'}).each((region) => {
            cy.wrap(region).within(() => {
                // Focus on the question
                cy.findByRole('textbox', { name: 'Question name' }).click();

                // Check the dropdowns
                cy.getDropdownByLabelText('Select device...').closest('.devices-dropdown').find('select').should('exist').should('be.disabled');
                cy.getDropdownByLabelText('Select devices...').should('not.exist');

                // Check the switch
                cy.findByRole('checkbox', { name: 'Allow multiple devices' }).should('not.be.checked');
            });
        });
    });

    it('can duplicate a multiple devices question', () => {
        // Allow multiple actors
        cy.findByRole('checkbox', { name: 'Allow multiple devices' }).check();

        // Duplicate the question
        cy.findByRole('button', {'name': "Duplicate question"}).click();

        cy.findAllByRole('option', {'name': 'New question'}).each((region) => {
            cy.wrap(region).within(() => {
                // Focus on the question
                cy.findByRole('textbox', { name: 'Question name' }).click();

                // Check the dropdowns
                cy.getDropdownByLabelText('Select device...').should('not.exist');
                cy.getDropdownByLabelText('Select devices...').closest('.devices-dropdown').find('select').should('exist').should('be.disabled');

                // Check the switch
                cy.findByRole('checkbox', { name: 'Allow multiple devices' }).should('be.checked');
            });
        });
    });
});
