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

describe('DC Room', () => {
    beforeEach(() => {
        cy.createWithAPI('Datacenter', { name: 'DC for E2E', entities_id: 1 }).then((datacenters_id) => {
            cy.createWithAPI('DCRoom', {
                name: 'DC Room for E2E',
                datacenters_id: datacenters_id,
                entities_id: 1,
                vis_cols: 5,
                vis_rows: 5,
            }).as('dcrooms_id').then((dcrooms_id) => {
                cy.createWithAPI('Rack', {
                    name: 'Rack for E2E 1',
                    dcrooms_id: dcrooms_id,
                    entities_id: 1,
                    position: '1,1'
                }).as('racks_id_1');
                cy.createWithAPI('Rack', {
                    name: 'Rack for E2E 2',
                    dcrooms_id: dcrooms_id,
                    entities_id: 1,
                    position: '1,2'
                }).as('racks_id_2');
                cy.createWithAPI('Rack', {
                    name: 'Rack for E2E 3',
                    dcrooms_id: dcrooms_id,
                    entities_id: 1,
                    position: '10,10' //Out of bounds
                }).as('racks_id_3');
            });
        });

        cy.login();
        cy.changeProfile('Super-Admin');
    });
    it('Graphical view', () => {
        // Single test for the view to reduce API calls
        cy.get('@dcrooms_id').then((dcrooms_id) => {
            cy.visit(`/front/dcroom.form.php?id=${dcrooms_id}`);
            cy.findByRole('tab', {name: /^Racks/}).click();
            cy.findByRole('button', {name: 'View graphical representation'}).click();
            cy.get('table.outbound').should('contain.text', 'Rack for E2E 3');

            // Check loading of new rack modal
            cy.get('div.cell_add[data-x="2"][data-y="1"]').click();
            cy.findByRole('dialog')
                .should('have.attr', 'data-cy-shown', 'true')
                .within(() => {
                    //TODO the heading here should not be level 3
                    cy.findByRole('heading', {level: 3}).should('contain.text', 'New item - Rack');
                    cy.findByRole('button', {name: 'Close'}).click();
                    cy.should('not.exist');
                });


            // Check loading of existing rack
            cy.get('div.grid-stack-item').contains('Rack for E2E 1').click();
            cy.get('@racks_id_1').then((racks_id_1) => {
                cy.url().should('include', `/front/rack.form.php?id=${racks_id_1}`);
            });
        });
    });
});
