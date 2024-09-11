/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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
        cy.changeProfile('Super-Admin', true);

        cy.createFormWithAPI({
            'name': "Test form for service_catalog_tab.cy.js"
        }).visitFormTab('ServiceCatalog');
    });

    it('can configure service catalog', () => {
        // Make sure the values we are about to apply are are not already set to
        // prevent false negative.
        cy.getDropdownByLabelText("Icon").should('not.contain.text', 'ti-dog');
        cy.findByLabelText("Description").awaitTinyMCE().should('not.contain.text', 'My description');

        // Set values
        cy.getDropdownByLabelText("Icon").selectDropdownValue('ti-dog');
        cy.findByLabelText("Description").awaitTinyMCE().type('My description');

        // Save changes
        cy.findByRole('button', {'name': "Save changes"}).click();
        cy.findByRole('alert').should('contain.text', 'Item successfully updated');

        // Validate values
        cy.getDropdownByLabelText("Icon").should('contain.text', 'ti-dog');
        cy.findByLabelText("Description").awaitTinyMCE().should('contain.text', 'My description');
    });
});
