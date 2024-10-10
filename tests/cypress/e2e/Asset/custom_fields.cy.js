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
        const asset_name = `customasset${Array.from({ length: 10 }, () => asset_name_chars.charAt(Math.floor(Math.random() * asset_name_chars.length))).join('')}`;
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

            // Verify readonly and mandatory options don't affect the default value field
            cy.findByLabelText('Readonly').check();
            cy.findByLabelText('Mandatory').check();
            cy.waitForNetworkIdle('/ajax/asset/customfield.php', 100);
            cy.findByLabelText('Default value', {selector: 'input:not([readonly]):not([disabled]):not([required])'}).should('exist');
            cy.findByLabelText('Readonly').uncheck();
            cy.findByLabelText('Mandatory').uncheck();

            cy.findByRole('button', {name: 'Add'}).click();
        });
        cy.findByRole('button', {name: 'Add a new field'}).parents('.tab-pane').should('have.class', 'active').first().within(() => {
            cy.findByRole('button', {name: 'Add a new field'}).click();
            cy.findByLabelText('Label').type('Test Text');
            cy.findByLabelText('Type').select('Text', { force: true });

            // Verify readonly and mandatory options don't affect the default value field
            cy.findByLabelText('Readonly').check();
            cy.findByLabelText('Mandatory').check();
            cy.waitForNetworkIdle('/ajax/asset/customfield.php', 100);
            cy.findByLabelText('Default value', {selector: 'input:not([readonly]):not([disabled]):not([required])'}).should('exist');
            cy.findByLabelText('Readonly').uncheck();
            cy.findByLabelText('Mandatory').uncheck();

            cy.findByRole('button', {name: 'Add'}).click();
        });
        cy.findByRole('button', {name: 'Add a new field'}).parents('.tab-pane').should('have.class', 'active').first().within(() => {
            cy.findByRole('button', {name: 'Add a new field'}).click();
            cy.findByLabelText('Label').type('Test Number');
            cy.findByLabelText('Type').select('Number', { force: true });

            cy.findByLabelText('Minimum').type('{selectall}{del}10');
            cy.findByLabelText('Maximum').type('{selectall}{del}20');
            cy.findByLabelText('Step').type('{selectall}{del}2');

            // Verify readonly and mandatory options don't affect the default value field
            cy.findByLabelText('Readonly').check();
            cy.findByLabelText('Mandatory').check();
            cy.waitForNetworkIdle('/ajax/asset/customfield.php', 100);
            cy.findByLabelText('Default value', {selector: 'input:not([readonly]):not([disabled]):not([required])'}).should('exist');
            cy.findByLabelText('Readonly').uncheck();
            cy.findByLabelText('Mandatory').uncheck();

            cy.waitForNetworkIdle('/ajax/asset/customfield.php', 100);
            cy.findByLabelText('Default value').type('{selectall}{del}12');

            cy.findByRole('button', {name: 'Add'}).click();
        });
        cy.findByRole('button', {name: 'Add a new field'}).parents('.tab-pane').should('have.class', 'active').first().within(() => {
            cy.findByRole('button', {name: 'Add a new field'}).click();
            cy.findByLabelText('Label').type('Test Date');
            cy.findByLabelText('Type').select('Date', { force: true });

            // Verify readonly and mandatory options don't affect the default value field
            cy.findByLabelText('Readonly').check();
            cy.findByLabelText('Mandatory').check();
            cy.waitForNetworkIdle('/ajax/asset/customfield.php', 100);
            cy.get('input[name="default_value"]:not([readonly]):not([disabled]):not([required])').should('exist');
            cy.findByLabelText('Readonly').uncheck();
            cy.findByLabelText('Mandatory').uncheck();

            cy.findByRole('button', {name: 'Add'}).click();
        });
        cy.findByRole('button', {name: 'Add a new field'}).parents('.tab-pane').should('have.class', 'active').first().within(() => {
            cy.findByRole('button', {name: 'Add a new field'}).click();
            cy.findByLabelText('Label').type('Test Datetime');
            cy.findByLabelText('Type').select('Date and time', { force: true });

            // Verify readonly and mandatory options don't affect the default value field
            cy.findByLabelText('Readonly').check();
            cy.findByLabelText('Mandatory').check();
            cy.waitForNetworkIdle('/ajax/asset/customfield.php', 100);
            cy.get('input[name="default_value"]:not([readonly]):not([disabled]):not([required])').should('exist');
            cy.findByLabelText('Readonly').uncheck();
            cy.findByLabelText('Mandatory').uncheck();

            cy.findByRole('button', {name: 'Add'}).click();
        });
        cy.findByRole('button', {name: 'Add a new field'}).parents('.tab-pane').should('have.class', 'active').first().within(() => {
            cy.findByRole('button', {name: 'Add a new field'}).click();
            cy.findByLabelText('Label').type('Test Dropdown');
            cy.findByLabelText('Type').select('Dropdown', { force: true });
            cy.findByLabelText('Item type').select('Monitor', { force: true });

            // Verify readonly and mandatory options don't affect the default value field
            cy.findByLabelText('Readonly').check();
            cy.findByLabelText('Mandatory').check();
            cy.waitForNetworkIdle('/ajax/asset/customfield.php', 100);
            cy.findByLabelText('Default value', {selector: 'select:not([readonly]):not([disabled]):not([required])'}).should('exist');
            cy.findByLabelText('Readonly').uncheck();
            cy.findByLabelText('Mandatory').uncheck();

            // Test the default value input respects the "Multiple values" option
            cy.findByLabelText('Multiple values').check();
            cy.waitForNetworkIdle('/ajax/asset/customfield.php', 100);
            cy.findByLabelText('Default value').should('have.attr', 'multiple');
            cy.findByLabelText('Multiple values').uncheck();
            cy.waitForNetworkIdle('/ajax/asset/customfield.php', 100);
            cy.findByLabelText('Default value').should('not.have.attr', 'multiple');

            cy.findByRole('button', {name: 'Add'}).click();
        });
        cy.findByRole('button', {name: 'Add a new field'}).parents('.tab-pane').should('have.class', 'active').first().within(() => {
            cy.findByRole('button', {name: 'Add a new field'}).click();
            cy.findByLabelText('Label').type('Test MultiDropdown');
            cy.findByLabelText('Type').select('Dropdown', { force: true });
            cy.findByLabelText('Item type').select('Monitor', { force: true });
            cy.findByLabelText('Multiple values').check();

            // Verify readonly and mandatory options don't affect the default value field
            cy.findByLabelText('Readonly').check();
            cy.findByLabelText('Mandatory').check();
            cy.waitForNetworkIdle('/ajax/asset/customfield.php', 100);
            cy.findByLabelText('Default value', {selector: 'select:not([readonly]):not([disabled]):not([required])'}).should('exist');
            cy.findByLabelText('Readonly').uncheck();
            cy.findByLabelText('Mandatory').uncheck();

            cy.findByRole('button', {name: 'Add'}).click();
        });
        cy.findByRole('button', {name: 'Add a new field'}).parents('.tab-pane').should('have.class', 'active').first().within(() => {
            cy.findByRole('button', {name: 'Add a new field'}).click();
            cy.findByLabelText('Label').type('Test URL');
            cy.findByLabelText('Type').select('URL', { force: true });

            // Verify readonly and mandatory options don't affect the default value field
            cy.findByLabelText('Readonly').check();
            cy.findByLabelText('Mandatory').check();
            cy.waitForNetworkIdle('/ajax/asset/customfield.php', 100);
            cy.findByLabelText('Default value', {selector: 'input:not([readonly]):not([disabled]):not([required])'}).should('exist');
            cy.findByLabelText('Readonly').uncheck();
            cy.findByLabelText('Mandatory').uncheck();

            cy.findByRole('button', {name: 'Add'}).click();
        });
        cy.findByRole('button', {name: 'Add a new field'}).parents('.tab-pane').should('have.class', 'active').first().within(() => {
            cy.findByRole('button', {name: 'Add a new field'}).click();
            cy.findByLabelText('Label').type('Test YesNo');
            cy.findByLabelText('Type').select('Yes/No', { force: true });

            // Verify readonly and mandatory options don't affect the default value field
            cy.findByLabelText('Readonly').check();
            cy.findByLabelText('Mandatory').check();
            cy.waitForNetworkIdle('/ajax/asset/customfield.php', 100);
            cy.findByLabelText('Default value', {selector: 'select:not([readonly]):not([disabled]):not([required])'}).should('exist');
            cy.findByLabelText('Readonly').uncheck();
            cy.findByLabelText('Mandatory').uncheck();

            cy.findByRole('button', {name: 'Add'}).click();
        });

        //Check the Item type column in the custom fields list is correct for dropdowns
        cy.findByRole('cell', {name: 'Test Dropdown'}).should('exist').siblings().contains('Monitor').should('exist');
        cy.findByRole('cell', {name: 'Test MultiDropdown'}).should('exist').siblings().contains('Monitor').should('exist');

        cy.findByRole('tab', {name: 'Profiles'}).click();
        cy.get('input[type="checkbox"][id^="cb_checkall_table"]').check({ force: true });
        cy.findByRole('button', {name: 'Save'}).click();

        cy.visit(`/front/asset/asset.form.php?class=${asset_name}&id=-1&withtemplate=2`);
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
        cy.get('label').contains('Test Date').next().within(() => {
            cy.get('.flatpickr-input').should('exist');
        });

        // FlatPickr input is not reachable by its label.
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
    });

    it('Custom field update', () => {
        cy.visit('/front/asset/assetdefinition.form.php');
        const asset_name_chars = 'abcdefghijklmnopqrstuvwxyz';
        const asset_name = `customasset${Array.from({ length: 10 }, () => asset_name_chars.charAt(Math.floor(Math.random() * asset_name_chars.length))).join('')}`;
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

        cy.findByRole('cell', {name: 'Test String'}).click();
        cy.findByLabelText('Label').should('have.value', 'Test String');
        cy.findByLabelText('Type').should('not.exist');
        cy.get('span.form-control').contains('String').should('exist');
        cy.findByLabelText('System name').should('have.value', 'test_string');
        cy.findByLabelText('Label').type(' Updated');
        // System name preview should not update because the system name cannot/will not actually change
        cy.findByLabelText('System name').should('have.value', 'test_string');
        cy.findByRole('button', {name: 'Save'}).click();

        cy.findByRole('cell', {name: 'Test String Updated'}).click();
        cy.findByLabelText('Label').should('have.value', 'Test String Updated');
        cy.findByLabelText('Type').should('not.exist');
        cy.get('span.form-control').contains('String').should('exist');
        cy.findByLabelText('System name').should('have.value', 'test_string');
    });

    it('Custom field delete', () => {
        cy.visit('/front/asset/assetdefinition.form.php');
        const asset_name_chars = 'abcdefghijklmnopqrstuvwxyz';
        const asset_name = `customasset${Array.from({ length: 10 }, () => asset_name_chars.charAt(Math.floor(Math.random() * asset_name_chars.length))).join('')}`;
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

        cy.findByRole('cell', {name: 'Test String'}).click();
        cy.findByRole('button', {name: 'Delete permanently'}).click();
        cy.findByRole('cell', {name: 'Test String'}).should('not.exist');
    });
});
