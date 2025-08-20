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

describe('SLA TTR configuration', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin');

        // Create form
        cy.createFormWithAPI().visitFormTab('Form');
        cy.findByRole('button', {'name': "Add a question"}).click();
        cy.focused().type("My test question");
        cy.findByRole('button', {'name': 'Save'}).click();
        cy.checkAndCloseAlert('Item successfully updated');

        cy.createWithAPI('SLM', {}).as('slm_id');
        cy.get('@slm_id').then((slm_id) => {
            const sla_name = `SLA TTR - ${slm_id}`;
            cy.createWithAPI('SLA', {
                'name': sla_name,
                'type': 0,
                'number_time': 1,
                'definition_time': 'hour',
                'slms_id': slm_id,
            });
        });

        // Go to destination tab
        cy.findByRole('tab', { 'name': "Destinations 1" }).click();
    });

    it('can use all possibles configuration options', () => {
        cy.openAccordionItem('Destination fields accordion', 'Service levels');
        cy.findByRole('region', { 'name': "TTR configuration" }).as("config");
        cy.get('@config').getDropdownByLabelText('TTR').as("sla_ttr_dropdown");

        // Default value
        cy.get('@sla_ttr_dropdown').should(
            'have.text',
            'From template'
        );

        // Switch to "From template"
        cy.get('@sla_ttr_dropdown').selectDropdownValue('From template');
        cy.findByRole('button', { 'name': 'Update item' }).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.openAccordionItem('Destination fields accordion', 'Service levels');
        cy.get('@sla_ttr_dropdown').should('have.text', 'From template');

        // Switch to "Specific SLA"
        cy.get('@sla_ttr_dropdown').selectDropdownValue('Specific SLA');
        cy.get('@config').getDropdownByLabelText('Select a SLA...').as('specific_sla_ttr_dropdown');
        cy.get('@slm_id').then((slm_id) => {
            const sla_name = `SLA TTR - ${slm_id}`;
            cy.get('@specific_sla_ttr_dropdown').selectDropdownValue(sla_name);
        });

        cy.findByRole('button', { 'name': 'Update item' }).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.openAccordionItem('Destination fields accordion', 'Service levels');
        cy.get('@sla_ttr_dropdown').should('have.text', 'Specific SLA');
        cy.get('@slm_id').then((slm_id) => {
            const sla_name = `SLA TTR - ${slm_id}`;
            cy.get('@specific_sla_ttr_dropdown').should('have.text', sla_name);
        });
    });

    it('can create ticket using default configuration', () => {
        // Switch to "Specific SLA"
        cy.openAccordionItem('Destination fields accordion', 'Service levels');
        cy.findByRole('region', { 'name': "TTR configuration" }).as("config");
        cy.get('@config').getDropdownByLabelText('TTR').selectDropdownValue('Specific SLA');
        cy.get('@slm_id').then((slm_id) => {
            const sla_name = `SLA TTR - ${slm_id}`;
            cy.get('@config').getDropdownByLabelText('Select a SLA...').selectDropdownValue(sla_name);
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
        cy.findByRole('textbox', { 'name': 'My test question' }).type('My test answer');

        // Submit form
        cy.findByRole('button', { 'name': 'Submit' }).click();
        cy.findByRole('link', { 'name': 'My test form' }).click();

        // Check ticket values
        cy.findByRole('region', { 'name': "Service levels" }).as('service_levels');
        cy.get('@slm_id').then((slm_id) => {
            cy.get('@service_levels').should('contain.text', `SLA TTR - ${slm_id}`);
        });

        // Others possibles configurations are tested directly by the backend.
    });
});
