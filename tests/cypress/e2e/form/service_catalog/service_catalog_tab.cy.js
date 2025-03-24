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

describe('Service catalog tab', () => {

    const uid = new Date().getTime();
    const category_name = `Category ${uid}`;
    const category_dropdown_value = `»${category_name}`; // GLPI add "»" prefix to common tree dropdown values

    before(() => {
        cy.createWithAPI('Glpi\\Form\\Category', {
            'name': category_name,
            'description': "my description",
        });
    });

    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin');
    });

    it('can configure service catalog for form', () => {
        cy.createFormWithAPI({
            'name': "Test form for service_catalog_tab.cy.js"
        }).visitFormTab('ServiceCatalog');

        // Make sure the values we are about to apply are are not already set to
        // prevent false negative.
        cy.findByLabelText("Description").awaitTinyMCE().should('not.contain.text', 'My description');
        cy.getDropdownByLabelText("Category").should('not.have.text', category_name);

        // Set values
        cy.findByLabelText("Description").awaitTinyMCE().type('My description');
        cy.getDropdownByLabelText('Category').selectDropdownValue(category_dropdown_value);
        cy.findByRole('checkbox', {'name': 'Pin to top of the service catalog'}).check();

        // Save changes
        cy.findByRole('button', {'name': "Save changes"}).click();
        cy.findByRole('alert').should('contain.text', 'Item successfully updated');

        // Validate values
        cy.findByLabelText("Description").awaitTinyMCE().should('contain.text', 'My description');
        cy.getDropdownByLabelText("Category").should('have.text', category_name);
        cy.findByRole('checkbox', {'name': 'Pin to top of the service catalog'}).should('be.checked');

        // Note: picking an illustration is not validated here as it is already
        // done in the illustration_picker.cy.js test.
    });

    it('can configure service catalog for KnowbaseItem', () => {
        cy.createWithAPI('KnowbaseItem', {
            'name': "Test knowbase item for service_catalog_tab.cy.js",
            'content': "My content",
        }).then((knowbaseItem_id) => cy.visit(`/front/knowbaseitem.form.php?id=${knowbaseItem_id}&forcetab=Glpi\\Form\\ServiceCatalog\\ServiceCatalog$1`));

        // Check that the service catalog configuration isn't active by default
        cy.findByRole('checkbox', {'name': 'Active'}).should('not.be.checked');

        // Set values
        cy.findByRole('checkbox', {'name': 'Active'}).check();
        cy.findByLabelText("Description").awaitTinyMCE().type('My description');
        cy.getDropdownByLabelText('Category').selectDropdownValue(category_dropdown_value);
        cy.findByRole('checkbox', {'name': 'Pin to top of the service catalog'}).check();

        // Save changes
        cy.findByRole('button', {'name': "Save changes"}).click();
        cy.findByRole('alert').should('contain.text', 'Item successfully updated');

        // Validate values
        cy.findByRole('checkbox', {'name': 'Active'}).should('be.checked');
        cy.findByLabelText("Description").awaitTinyMCE().should('contain.text', 'My description');
        cy.getDropdownByLabelText("Category").should('have.text', category_name);
        cy.findByRole('checkbox', {'name': 'Pin to top of the service catalog'}).should('be.checked');
    });
});
