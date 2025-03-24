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

describe('Entities selector', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin');

        // Go to any page; force the entity to be E2ETestEntity
        cy.blockGLPIDashboards();
        cy.changeEntity(1);
        cy.visit('/front/preference.php');
        cy.get('header').findByTitle('Root entity > E2ETestEntity').should('exist');
    });

    after(() => {
        cy.blockGLPIDashboards();
        cy.changeEntity(1, true);
    });

    it('Can switch to full structure', () => {
        cy.openEntitySelector();

        // Enable full structure
        cy.get('header').findByTitle('Root entity > E2ETestEntity (full structure)').should('not.exist');
        cy.findByRole("button", {'name': "Select all"}).click();
        cy.get('header').findByTitle('Root entity > E2ETestEntity (full structure)').should('exist');
    });

    it('Can search for entities', () => {
        cy.openEntitySelector();

        // Search for a specific entity
        cy.findByRole('gridcell', {'name': "E2ETestSubEntity1"}).should('not.exist');
        cy.findByRole('gridcell', {'name': "E2ETestSubEntity2"}).should('not.exist');
        cy.focused().type("Entity2");
        cy.findByRole("button", {'name': "Search"}).click();
        cy.findByRole('gridcell', {'name': "E2ETestSubEntity1"}).should('not.exist');
        cy.findByRole('gridcell', {'name': "E2ETestSubEntity2"}).should('exist');

        // Go to entity 2
        cy.get('header').findByTitle('Root entity > E2ETestEntity > E2ETestSubEntity2').should('not.exist');
        cy.findByRole('button',  {'name': "E2ETestSubEntity2"})
            .as('entity_button')
            .click()
        ;

        // Not sure why but this button only work in cypress if you click it 3 times...
        // This is not a timing issue, waiting before a click doesn't change anything.
        cy.get('@entity_button').click();
        cy.get('@entity_button').click();

        cy.get('header').findByTitle('Root entity > E2ETestEntity > E2ETestSubEntity2').should('exist');
    });

    it('Can fold/unfold tree', () => {
        cy.openEntitySelector();

        // Unfold the tree
        cy.findByRole('gridcell', {'name': "E2ETestSubEntity1"}).should('not.exist');
        cy.findByRole('gridcell', {'name': "E2ETestSubEntity2"}).should('not.exist');
        cy.get('.fancytree-expander[role=button]:visible').as('toggle_tree').click(); // We don't have access to a more precise selector here, so we have to rely on css
        cy.findByRole('gridcell', {'name': "E2ETestSubEntity1"}).should('exist');
        cy.findByRole('gridcell', {'name': "E2ETestSubEntity2"}).should('exist');

        // Fold tree again
        cy.get('@toggle_tree').click();
        cy.findByRole('gridcell', {'name': "E2ETestSubEntity1"}).should('not.exist');
        cy.findByRole('gridcell', {'name': "E2ETestSubEntity2"}).should('not.exist');
    });

    it('Can enable sub entities', { retries: {runMode: 2, openMode: 0} }, () => {
        cy.openEntitySelector();

        // Enable sub entities
        cy.get('header').findByTitle('Root entity > E2ETestEntity (tree structure)').should('not.exist');
        cy.findAllByRole('gridcell').findByTitle('+ sub-entities').click();
        cy.get('header').findByTitle('Root entity > E2ETestEntity (tree structure)').should('exist');
    });
});
