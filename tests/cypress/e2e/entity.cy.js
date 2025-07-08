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

let entity_id;

describe('Entity', () => {
    it('Can configure assistance properties', () => {
        const unique_id = (new Date()).getTime();
        cy.createWithAPI("Entity", {
            name: `Test entity ${unique_id}`,
        }).then((id) => {
            entity_id = id;
            cy.login();
            cy.visit(`/front/entity.form.php?id=${entity_id}&forcetab=Entity$5`);
            cy.getDropdownByLabelText('Show tickets properties on helpdesk')
                .should('have.text', 'Inheritance of the parent entity')
                .selectDropdownValue('Yes')
            ;
            cy.findByRole('button', {'name': "Save"}).click();
            cy.getDropdownByLabelText('Show tickets properties on helpdesk')
                .should('have.text', 'Yes');
        });
    });

    it('Survey options change by type and rate', () => {
        cy.login();
        cy.visit(`/front/entity.form.php?id=1&forcetab=Entity$5`);
        cy.findByLabelText('Configuring the satisfaction survey: Tickets').within(() => {
            cy.getDropdownByLabelText('Configuring the satisfaction survey').selectDropdownValue('Internal survey');
            cy.getDropdownByLabelText('Create survey after').should('be.visible');
            cy.getDropdownByLabelText('Rate to trigger survey').should('be.visible');
            cy.getDropdownByLabelText('Duration of survey').should('not.exist');
            cy.getDropdownByLabelText('Max rate').should('not.exist');
            cy.findByLabelText('Default rate').should('not.be.visible');
            cy.findByLabelText('Comment required if score is <= to').should('not.be.visible');
            // Flatpickr ruins labels
            cy.findByText('For Tickets closed after').should('not.be.visible');
            // Tags aren't shown in an input and therefore not technically labelable using 'label'
            cy.findByText('Valid tags').should('not.be.visible');
            cy.findByLabelText('URL').should('not.be.visible');

            cy.getDropdownByLabelText('Rate to trigger survey').selectDropdownValue('10%');
            cy.getDropdownByLabelText('Create survey after').should('be.visible');
            cy.getDropdownByLabelText('Rate to trigger survey').should('be.visible');
            cy.getDropdownByLabelText('Duration of survey').should('be.visible');
            cy.getDropdownByLabelText('Max rate').should('be.visible');
            cy.findByLabelText('Default rate').should('be.visible');
            cy.findByLabelText('Comment required if score is <= to').should('be.visible');
            cy.findByText('For Tickets closed after').should('exist');
            cy.findByText('Valid tags').should('not.be.visible');
            cy.findByLabelText('URL').should('not.be.visible');


            cy.getDropdownByLabelText('Configuring the satisfaction survey').selectDropdownValue('External survey');
            cy.getDropdownByLabelText('Create survey after').should('be.visible');
            cy.getDropdownByLabelText('Rate to trigger survey').should('be.visible');
            cy.getDropdownByLabelText('Duration of survey').should('not.exist');
            cy.getDropdownByLabelText('Max rate').should('not.exist');
            cy.findByLabelText('Default rate').should('not.be.visible');
            cy.findByLabelText('Comment required if score is <= to').should('not.be.visible');
            cy.findByText('For Tickets closed after').should('not.be.visible');
            cy.findByText('Valid tags').should('not.be.visible');
            cy.findByLabelText('URL').should('not.be.visible');

            cy.getDropdownByLabelText('Rate to trigger survey').selectDropdownValue('10%');
            cy.getDropdownByLabelText('Create survey after').should('be.visible');
            cy.getDropdownByLabelText('Rate to trigger survey').should('be.visible');
            cy.getDropdownByLabelText('Duration of survey').should('be.visible');
            cy.getDropdownByLabelText('Max rate').should('be.visible');
            cy.findByLabelText('Default rate').should('be.visible');
            cy.findByLabelText('Comment required if score is <= to').should('be.visible');
            cy.findByText('For Tickets closed after').should('exist');
            cy.findByText('Valid tags').should('be.visible');
            cy.findByLabelText('URL').should('be.visible');
        });
    });
});
