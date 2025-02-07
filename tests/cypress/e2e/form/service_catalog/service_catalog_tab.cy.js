/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

describe('Service catalog tab', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin');

        cy.createFormWithAPI({
            'name': "Test form for service_catalog_tab.cy.js"
        }).visitFormTab('ServiceCatalog');
    });

    it('can configure service catalog', () => {
        const uid = new Date().getTime();
        const category_name = `Category ${uid}`;
        const category_dropdown_value = `»${category_name}`; // GLPI add "»" prefix to common tree dropdown values

        cy.createWithAPI('Glpi\\Form\\Category', {
            'name': category_name,
            'description': "my description",
        });

        // Make sure the values we are about to apply are are not already set to
        // prevent false negative.
        cy.findByLabelText("Description").awaitTinyMCE().should('not.contain.text', 'My description');
        cy.getDropdownByLabelText("Category").should('not.have.text', category_name);

        // Set values
        cy.findByLabelText("Description").awaitTinyMCE().type('My description');
        cy.getDropdownByLabelText('Category').selectDropdownValue(category_dropdown_value);

        // Save changes
        cy.findByRole('button', {'name': "Save changes"}).click();
        cy.findByRole('alert').should('contain.text', 'Item successfully updated');

        // Validate values
        cy.findByLabelText("Description").awaitTinyMCE().should('contain.text', 'My description');
        cy.getDropdownByLabelText("Category").should('have.text', category_name);

        // Note: picking an illustration is not validated here as it is already
        // done in the illustration_picker.cy.js test.
    });
});
