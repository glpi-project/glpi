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

describe('Form category', () => {
    beforeEach(() => {
        cy.login();
    });

    it('can create a new form category', () => {
        cy.visit('/front/form/category.php');
        cy.findByRole('link', { name: 'Add' }).click();
        cy.findByLabelText('Name').type('Test category');
        cy.findByLabelText('Description').awaitTinyMCE().type('This is a test category');

        // Select an illustration
        cy.findByRole('button', { name: 'Select an illustration' }).click();
        cy.findByRole('dialog').as('modal');
        cy.get('@modal').should('have.attr', 'data-cy-shown', 'true');
        cy.get('@modal').findByRole('img', { name: 'Cartridge' }).click();

        // Create and go to the new category
        cy.findByRole('button', { name: 'Add' }).click();
        cy.findByRole('alert').findByRole('link', { name: 'Test category' }).click();

        // Check category fields
        cy.findByLabelText('Name').should('have.value', 'Test category');
        cy.findByLabelText('Description')
            .should('have.value', '<p>This is a test category</p>')
            .awaitTinyMCE().should('have.text', 'This is a test category');
        cy.findByRole('img', { name: 'Cartridge' }).should('be.visible');
    });

    it('can open illustration picker, show forms attached to the category and go back to illustration picker', () => {
        // Create a form category with a form
        cy.createWithAPI('Glpi\\Form\\Category', {
            name: 'Test category',
        }).then((category_id) => {
            cy.createWithAPI('Glpi\\Form\\Form', {
                name: 'Test form',
                forms_categories_id: category_id,
            });

            cy.visit(`/front/form/category.form.php?id=${category_id}`);
        });

        // Open illustration picker
        cy.findByRole('button', { name: 'Select an illustration' }).click();
        cy.findByRole('dialog').as('modal');
        cy.get('@modal').should('have.attr', 'data-cy-shown', 'true');
        cy.get('@modal').findByRole('img', { name: 'Cartridge' }).click();
        cy.get('@modal').should('not.exist');
        cy.findByRole('img', { name: 'Cartridge' }).should('be.visible');

        // Go to forms tab
        cy.findByRole('tab', { name: 'Forms 1' }).click();
        cy.findByRole('link', { name: 'Test form' }).should('be.visible');

        // Go back to illustration picker
        cy.findByRole('tab', { name: 'Service catalog category' }).click();
        cy.findByRole('button', { name: 'Select an illustration' }).click();
        cy.get('@modal').should('have.attr', 'data-cy-shown', 'true');
        cy.get('@modal').findByRole('img', { name: 'Cartridge' }).click();
        cy.get('@modal').should('not.exist');
        cy.findByRole('img', { name: 'Cartridge' }).should('be.visible');
    });
});
