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

describe("Impact graph", () => {
    let computer_id;
    before(() => {
        cy.createWithAPI('Computer', { name: 'Impact computer', entities_id: 1 }).then(id => computer_id = id);
    });

    beforeEach(() => {
        cy.login();
    });

    it('Loads', () => {
        cy.visit(`/front/computer.form.php?id=${computer_id}`);
        cy.findByRole('tab', { name: 'Impact analysis' }).click();
        cy.findByRole('tabpanel').within(() => {
            cy.get('#impact_graph_view').should('be.visible');
            cy.get('#impact_list_view').should('not.be.visible');
            cy.get('.__________cytoscape_container > canvas').should('have.length.at.least', 1);
            cy.findByTitle('View as list').click();
            cy.get('#impact_graph_view').should('not.be.visible');
            cy.get('#impact_list_view').should('be.visible');
            cy.findByTitle('View graphical representation').click();
            cy.get('#impact_graph_view').should('be.visible');
            cy.get('#impact_list_view').should('not.be.visible');
        });
    });
});
