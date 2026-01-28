/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

describe('Entity', () => {
    beforeEach(() => {
        cy.login();
    });

    it('Should be able to create a sub-subentity in a sub-entity context', () => {
        const rand = Math.floor(Math.random() * 1000);
        const subentity_name = `Subentity ${rand}`;

        cy.visit(`/front/entity.form.php`);
        cy.findByLabelText('Name').type(subentity_name);
        cy.findByRole('button', {'name': "Add"}).click();

        // We switch context to the newly created subentity
        cy.openEntitySelector();
        cy.get('.fancytree-expander[role=button]:visible').as('toggle_tree').click(); // From entities_selector tests.
        cy.findByRole('gridcell', {'name': subentity_name}).findByRole('button').click();

        // We can create the sub-subentity (first child so recursive will be automatically set)
        cy.visit(`/front/entity.form.php`);
        cy.intercept(`/front/entity.form.php`).as('formSent');
        cy.findByLabelText('Name').type(`First-sub-${subentity_name}`);
        cy.findByRole('button', {'name': "Add"}).click();
        cy.wait('@formSent').then((interception) => {
            expect(interception.response.statusCode).to.eq(302);
        });

        // We can't create the sub-subentity, form is inaccessible as we already have a sub-subentity
        cy.visit('/front/entity.php');
        cy.openEntitySelector();
        cy.findByRole('button',  {'name': `${subentity_name}`}).click();

        cy.intercept('GET', '/front/entity.form.php').as('formRequest');
        cy.visit('/front/entity.form.php', {failOnStatusCode: false} );
        cy.wait('@formRequest').then((interception) => {
            expect(interception.response.statusCode).to.eq(403);
        });

        // The listing page should display an error message
        cy.visit('/front/entity.php');
        cy.get('div.toast-container .toast-body').should('exist');

        // We switch context to be in ALL recursive mode
        cy.openEntitySelector();
        cy.findByRole("button", {'name': "Select all"}).click();

        cy.visit(`/front/entity.form.php`);
        cy.intercept(`/front/entity.form.php`).as('formRecursiveSent');
        cy.findByLabelText('Name').type(`Sub-${subentity_name}`);
        cy.findByRole('button', {'name': "Add"}).click();
        cy.wait('@formRecursiveSent').then((interception) => {
            expect(interception.response.statusCode).to.eq(302);
        });
    });
});
