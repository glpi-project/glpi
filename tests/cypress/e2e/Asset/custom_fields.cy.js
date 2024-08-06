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

describe("Custom Assets - Custom Fields", () => {
    beforeEach(() => {
        cy.login();
    });

    it('Custom field creation and display', () => {
        // This can be split into multiple tests when the new API supports custom assets and custom fields
        cy.visit('/front/asset/assetdefinition.form.php');
        const asset_name_chars = 'abcdefghijklmnopqrstuvwxyz';
        const asset_name = 'customasset' + Array.from({ length: 10 }, () => asset_name_chars.charAt(Math.floor(Math.random() * asset_name_chars.length))).join('');
        cy.findByLabelText(/System name/i).type(asset_name);
        cy.findByLabelText("Active").select('1', { force: true });
        cy.findByRole('button', {name: "Add"}).click();

        cy.get('div.toast-container .toast-body a').click();
        cy.url().should('include', '/front/asset/assetdefinition.form.php?id=');

        cy.findByRole('tab', {name: 'Custom fields'}).click();
        cy.findByRole('button', {name: 'Add a new field'}).parents('.tab-pane').should('have.class', 'active').first().within(() => {
            cy.findByRole('button', {name: 'Add a new field'}).click();
            cy.findByLabelText('Label').type('Test String');
            cy.findByLabelText('Type').select('String', { force: true });
            cy.findByRole('button', {name: 'Add'}).click();
        });
        cy.findByRole('tab', {name: 'Custom fields'}).click();
        cy.findByRole('button', {name: 'Add a new field'}).parents('.tab-pane').should('have.class', 'active').first().within(() => {
            cy.findByRole('button', {name: 'Add a new field'}).click();
            cy.findByLabelText('Label').type('Test Text');
            cy.findByLabelText('Type').select('Text', { force: true });
            cy.findByRole('button', {name: 'Add'}).click();
        });
        cy.findByRole('tab', {name: 'Custom fields'}).click();
        cy.findByRole('button', {name: 'Add a new field'}).parents('.tab-pane').should('have.class', 'active').first().within(() => {
            cy.findByRole('button', {name: 'Add a new field'}).click();
            cy.findByLabelText('Label').type('Test Number');
            cy.findByLabelText('Type').select('Number', { force: true });
            cy.findByLabelText('Minimum').type('{selectall}{del}10');
            cy.findByLabelText('Maximum').type('{selectall}{del}20');
            cy.findByLabelText('Step').type('{selectall}{del}2');
            cy.findByRole('button', {name: 'Add'}).click();
        });
        cy.findByRole('tab', {name: 'Custom fields'}).click();
        cy.findByRole('button', {name: 'Add a new field'}).parents('.tab-pane').should('have.class', 'active').first().within(() => {
            cy.findByRole('button', {name: 'Add a new field'}).click();
            cy.findByLabelText('Label').type('Test Date');
            cy.findByLabelText('Type').select('Date', { force: true });
            cy.findByRole('button', {name: 'Add'}).click();
        });
        cy.findByRole('tab', {name: 'Custom fields'}).click();
        cy.findByRole('button', {name: 'Add a new field'}).parents('.tab-pane').should('have.class', 'active').first().within(() => {
            cy.findByRole('button', {name: 'Add a new field'}).click();
            cy.findByLabelText('Label').type('Test Datetime');
            cy.findByLabelText('Type').select('Date and time', { force: true });
            cy.findByRole('button', {name: 'Add'}).click();
        });
        cy.findByRole('tab', {name: 'Custom fields'}).click();
        cy.findByRole('button', {name: 'Add a new field'}).parents('.tab-pane').should('have.class', 'active').first().within(() => {
            cy.findByRole('button', {name: 'Add a new field'}).click();
            cy.findByLabelText('Label').type('Test Dropdown');
            cy.findByLabelText('Type').select('Dropdown', { force: true });
            cy.findByLabelText('Item type').select('Monitor', { force: true });
            cy.findByRole('button', {name: 'Add'}).click();
        });
        cy.findByRole('tab', {name: 'Custom fields'}).click();
        cy.findByRole('button', {name: 'Add a new field'}).parents('.tab-pane').should('have.class', 'active').first().within(() => {
            cy.findByRole('button', {name: 'Add a new field'}).click();
            cy.findByLabelText('Label').type('Test MultiDropdown');
            cy.findByLabelText('Type').select('Dropdown', { force: true });
            cy.findByLabelText('Item type').select('Monitor', { force: true });
            cy.findByLabelText('Multiple values').check();
            cy.findByRole('button', {name: 'Add'}).click();
        });
        cy.findByRole('tab', {name: 'Custom fields'}).click();
        cy.findByRole('button', {name: 'Add a new field'}).parents('.tab-pane').should('have.class', 'active').first().within(() => {
            cy.findByRole('button', {name: 'Add a new field'}).click();
            cy.findByLabelText('Label').type('Test URL');
            cy.findByLabelText('Type').select('URL', { force: true });
            cy.findByRole('button', {name: 'Add'}).click();
        });
        cy.findByRole('tab', {name: 'Custom fields'}).click();
        cy.findByRole('button', {name: 'Add a new field'}).parents('.tab-pane').should('have.class', 'active').first().within(() => {
            cy.findByRole('button', {name: 'Add a new field'}).click();
            cy.findByLabelText('Label').type('Test YesNo');
            cy.findByLabelText('Type').select('Yes/No', { force: true });
            cy.findByRole('button', {name: 'Add'}).click();
        });
        cy.findByRole('tab', {name: 'Custom fields'}).click();
        cy.findByRole('button', {name: 'Add a new field'}).parents('.tab-pane').should('have.class', 'active').first().within(() => {
            cy.findByRole('button', {name: 'Add a new field'}).click();
            cy.findByLabelText('Type').select('Placeholder', { force: true });
            cy.findByRole('button', {name: 'Add'}).click();
        });

        cy.findByRole('tab', {name: 'Profiles'}).click();
        cy.get('input[type="checkbox"][id^="cb_checkall_table"]').check({ force: true });
        cy.findByRole('button', {name: 'Save'}).click();

        cy.visit('/front/asset/asset.form.php?class=' + asset_name + '&id=-1&withtemplate=2');
        // Validate the custom fields look OK
        cy.findByLabelText('Test String')
            .should('have.attr', 'type', 'text')
            .should('have.attr', 'maxlength', '255');
        cy.findByLabelText('Test Text').should('be.visible');
        cy.findByLabelText('Test Number')
            .should('have.attr', 'type', 'number')
            .should('have.attr', 'min', '10')
            .should('have.attr', 'max', '20')
            .should('have.attr', 'step', '2');

        // FlatPickr input is not reachable by its label.
        // cy.findByLabelText('Test Date').click();
        cy.get('label').contains('Test Date').next().within(() => {
            cy.get('.flatpickr-input').should('exist');
        });

        // FlatPickr input is not reachable by its label.
        // cy.findByLabelText('Test Datetime').click();
        cy.get('label').contains('Test Datetime').next().within(() => {
            cy.get('.flatpickr-input').should('exist');
        });

        cy.getDropdownByLabelText('Test Dropdown')
            .should('be.visible')
            .should('have.class', 'select2-selection--single');
        cy.getDropdownByLabelText('Test MultiDropdown')
            .should('be.visible')
            .should('have.class', 'select2-selection--multiple');

        cy.findByLabelText('Test URL').should('have.attr', 'type', 'url');

        cy.getDropdownByLabelText('Test YesNo').selectDropdownValue('No');
        cy.getDropdownByLabelText('Test YesNo').selectDropdownValue('Yes');

        // This is the placeholder field
        cy.get('.form-field .field-container:not(:has(*))').should('have.length', 1);
    });
});
