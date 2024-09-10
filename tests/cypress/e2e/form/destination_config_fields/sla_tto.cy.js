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

describe('SLA TTO configuration', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin', true);

        // Create form
        cy.createFormWithAPI().visitFormTab('Form');
        cy.findByRole('button', {'name': "Add a new question"}).click();
        cy.focused().type("My test question");
        cy.findByRole('button', {'name': 'Save'}).click();
        cy.checkAndCloseAlert('Item successfully updated');

        cy.createWithAPI('SLM', {}).as('slm_id');
        cy.get('@slm_id').then((slm_id) => {
            const sla_name = 'SLA TTO - ' + slm_id;
            cy.createWithAPI('SLA', {
                'name': sla_name,
                'type': 1,
                'number_time': 1,
                'definition_time': 'hour',
                'slms_id': slm_id,
            });
        });

        // Go to destination tab
        cy.findByRole('tab', { 'name': "Items to create" }).click();
        cy.findByRole('button', { 'name': "Add ticket" }).click();
        cy.checkAndCloseAlert('Item successfully added');
    });

    it('can use all possibles configuration options', () => {
        cy.findByRole('region', { 'name': "SLA TTO configuration" }).as("config");
        cy.get('@config').getDropdownByLabelText('SLA TTO').as("sla_tto_dropdown");

        // Default value
        cy.get('@sla_tto_dropdown').should(
            'have.text',
            'From template'
        );

        // Switch to "From template"
        cy.get('@sla_tto_dropdown').selectDropdownValue('From template');
        cy.findByRole('button', { 'name': 'Update item' }).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.get('@sla_tto_dropdown').should('have.text', 'From template');

        // Switch to "Specific SLA"
        cy.get('@sla_tto_dropdown').selectDropdownValue('Specific SLA');
        cy.get('@config').getDropdownByLabelText('Select an SLA...').as('specific_sla_tto_dropdown');
        cy.get('@slm_id').then((slm_id) => {
            const sla_name = 'SLA TTO - ' + slm_id;
            cy.get('@specific_sla_tto_dropdown').selectDropdownValue(sla_name);
        });

        cy.findByRole('button', { 'name': 'Update item' }).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.get('@sla_tto_dropdown').should('have.text', 'Specific SLA');
        cy.get('@slm_id').then((slm_id) => {
            const sla_name = 'SLA TTO - ' + slm_id;
            cy.get('@specific_sla_tto_dropdown').should('have.text', sla_name);
        });
    });

    it('can create ticket using default configuration', () => {
        // Switch to "Specific SLA"
        cy.findByRole('region', { 'name': "SLA TTO configuration" }).as("config");
        cy.get('@config').getDropdownByLabelText('SLA TTO').selectDropdownValue('Specific SLA');
        cy.get('@slm_id').then((slm_id) => {
            const sla_name = 'SLA TTO - ' + slm_id;
            cy.get('@config').getDropdownByLabelText('Select an SLA...').selectDropdownValue(sla_name);
        });
        cy.findByRole('button', { 'name': 'Update item' }).click();
        cy.checkAndCloseAlert('Item successfully updated');

        // Go to preview
        cy.findByRole('tab', { 'name': "Form" }).click();
        cy.findByRole('link', { 'name': "Preview" })
            .invoke('removeAttr', 'target') // Cypress can't handle tab changes
            .click()
        ;

        // Fill form
        cy.findByRole('button', { 'name': 'Send form' }).click();
        cy.findByRole('link', { 'name': 'My test form' }).click();

        // Check ticket values
        cy.findByRole('region', { 'name': "Service levels" }).as('service_levels');
        cy.get('@slm_id').then((slm_id) => {
            cy.get('@service_levels').should('contain.text', 'SLA TTO - ' + slm_id);
        });

        // Others possibles configurations are tested directly by the backend.
    });
});
