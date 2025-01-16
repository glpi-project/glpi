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

const xss_payload = '<script>throw new Error("XSS");</script>';

describe('XSS tests for CRUD and search operations', () => {
    beforeEach(() => {
        cy.login();
    });

    it("Can't inject XSS into an item name", () => {
        // Go to entity page
        cy.visit('/front/entity.form.php');
        const unique_id = (new Date()).getTime();
        const name = unique_id + xss_payload;

        // Create an entity with a XSS payload
        cy.findByRole('textbox', {'name': "Name"}).type(name);
        cy.findByRole('button', {'name': "Add"}).click();

        // Go to created entity
        cy.findByRole('alert')
            .should('contain.text', 'Item successfully added:')
            .and('contain.text', name)
        ;
        cy.findByRole('link', {'name': name}).click();

        // Check name
        cy.findByRole('textbox', {'name': "Name"}).should(
            'have.value',
            name
        );

        // Search for the entity
        cy.visit(`/front/entity.php?criteria[0][link]=AND&criteria[0][field]=14&criteria[0][searchtype]=contains&criteria[0][value]=${name}`);
        cy.findAllByText(name).should('exist').and('have.length', 2);
    });
});
