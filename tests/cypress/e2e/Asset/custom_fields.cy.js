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

describe("Custom Assets - Custom Fields", () => {
    beforeEach(() => {
        cy.login();
    });

    function parseRequest(interception) {
        const formDataObject = {};

        if (interception.request.headers['content-type'].startsWith('multipart/form-data')) {
            // Parse the multipart form data to an object
            const boundary = interception.request.headers['content-type'].split('boundary=')[1];
            const parts = interception.request.body.split(`--${boundary}`).map((part) => part.trim()).filter((part) => part !== '');
            for (const part of parts) {
                if (part.trim() === '--') {
                    continue;
                }
                const [, name, value] = part.match(/name="([^"]*)"\s*([\s\S]*)\s*/);
                if (name.endsWith('[]')) {
                    const arrayName = name.slice(0, -2);
                    if (!formDataObject[arrayName]) {
                        formDataObject[arrayName] = [];
                    }
                    formDataObject[arrayName].push(value.trim());
                } else {
                    formDataObject[name] = value.trim();
                }
            }
        } else if (interception.request.headers['content-type'].startsWith('application/x-www-form-urlencoded')) {
            // Parse the urlencoded form data to an object
            const urlSearchParams = new URLSearchParams(interception.request.body);
            for (const [name, value] of urlSearchParams) {
                if (name.endsWith('[]')) {
                    const arrayName = name.slice(0, -2);
                    if (!formDataObject[arrayName]) {
                        formDataObject[arrayName] = [];
                    }
                    formDataObject[arrayName].push(value);
                } else {
                    formDataObject[name] = value;
                }
            }
        }
        return formDataObject;
    }

    const getAssetName = () => {
        const asset_name_chars = 'abcdefghijklmnopqrstuvwxyz';
        return `customasset${Array.from({ length: 10 }, () => asset_name_chars.charAt(Math.floor(Math.random() * asset_name_chars.length))).join('')}`;
    };

    it('Reordering fields', () => {
        cy.visit('/front/asset/assetdefinition.form.php');
        const asset_name = getAssetName();
        cy.findByLabelText(/System name/i).type(asset_name);
        cy.findByLabelText("Active").select('1', { force: true });
        cy.findByRole('button', {name: "Add"}).click();

        cy.get('div.toast-container .toast-body a').click();
        cy.url().should('include', '/front/asset/assetdefinition.form.php?id=');

        cy.findByRole('tab', {name: 'Profiles'}).click();
        cy.get('input[type="checkbox"][id^="cb_checkall_table"]').check({ force: true });
        cy.findByRole('button', {name: 'Save'}).click();

        cy.findByRole('tab', {name: /^Fields/}).click();
        cy.get('#sortable-fields[aria-dropeffect]').within(() => {
            cy.get('.sortable-field[data-key="name"][draggable="true"]')
                .as('name_field')
                .should('be.visible')
                .invoke('index').should('eq', 0);
            cy.get('.sortable-field[data-key="states_id"][draggable="true"]')
                .as('states_id_field')
                .should('be.visible')
                .invoke('index').should('eq', 1);
            cy.get("@name_field").then(($name_field) => {
                cy.get("@states_id_field").then(($states_id_field) => {
                    $name_field.insertAfter($states_id_field);
                });
            });
        });
        cy.findByRole('button', {name: 'Save'}).click();

        cy.get('.sortable-field[data-key="name"][draggable="true"]').invoke('index').should('eq', 1);
        cy.get('.sortable-field[data-key="states_id"][draggable="true"]').invoke('index').should('eq', 0);

        cy.visit(`/front/asset/asset.form.php?class=${asset_name}&id=-1&withtemplate=2`);
        cy.findByLabelText('Name').closest('.form-field').should('be.visible').invoke('index').should('eq', 1);
        cy.findByLabelText('Status').closest('.form-field').should('be.visible').invoke('index').should('eq', 0);
    });

    // eslint-disable-next-line cypress/no-async-tests
    it('Create custom fields', async () => {
        async function createField(label, type, options = new Map()) {
            cy.findByRole('button', {name: 'New field'}).click();
            cy.findByRole('dialog').should('be.visible').within(() => {
                cy.findByLabelText('Label').type(label);
                cy.findByLabelText('Type').select(type, {force: true});
                cy.findByLabelText('System name').should('have.attr', 'readonly');

                if (options.has('item_type')) {
                    cy.findByLabelText('Item type').select(options.get('item_type'), {force: true});
                }
                if (options.has('min')) {
                    cy.findByLabelText('Minimum').type(`{selectall}{del}${options.get('min')}`);
                }
                if (options.has('max')) {
                    cy.findByLabelText('Maximum').type(`{selectall}{del}${options.get('max')}`);
                }
                if (options.has('step')) {
                    cy.findByLabelText('Step').type(`{selectall}{del}${options.get('step')}`);
                }
                if (options.has('multiple_values')) {
                    cy.findByLabelText('Multiple values').check();
                }
                if (options.has('readonly')) {
                    cy.findByLabelText('Readonly').check();
                }
                if (options.has('mandatory')) {
                    cy.findByLabelText('Mandatory').check();
                }

                cy.findByRole('button', {name: 'Add'}).click();
                cy.waitForNetworkIdle('/front/asset/customfielddefinition.form.php', 100);
            });
            cy.findByRole('dialog').should('not.exist');
            cy.get(`.sortable-field[data-key="custom_${label.toLowerCase().replace(' ', '_')}"]`).should('be.visible');
        }

        cy.visit('/front/asset/assetdefinition.form.php');
        const asset_name = getAssetName();
        cy.findByLabelText(/System name/i).type(asset_name);
        cy.findByLabelText("Active").select('1', { force: true });
        cy.findByRole('button', {name: "Add"}).click();
        cy.get('div.toast-container .toast-body a').click();
        cy.url().should('include', '/front/asset/assetdefinition.form.php?id=');

        cy.findByRole('tab', {name:  /^Fields/}).click();

        cy.findByRole('button', {name: 'New field'}).parents('.tab-pane').should('have.class', 'active').first().within(async () => {
            await createField('Test String', 'String');
            await createField('Test Text', 'Text');
            await createField('Test Number', 'Number', new Map([['min', '10'], ['max', '20'], ['step', '2']]));
            await createField('Test Date', 'Date');
            await createField('Test Datetime', 'Date and time');
            await createField('Test Dropdown', 'Dropdown', new Map([['item_type', 'Monitor']]));
            await createField('Test MultiDropdown', 'Dropdown', new Map([['item_type', 'Monitor'], ['multiple_values', true]]));
            await createField('Test URL', 'URL');
            await createField('Test YesNo', 'Yes/No');

            // Intercept form submission to check the form display values sent
            cy.intercept('POST', '/front/asset/assetdefinition.form.php').as('saveFieldsDisplay');
            cy.findByRole('button', {name: 'Save'}).click({force: true});
            cy.wait('@saveFieldsDisplay').then((interception) => {
                const formDataObject = parseRequest(interception);
                expect(formDataObject['_update_fields_display']).to.be.equal('1');
                expect(formDataObject['fields_display']).to.include.members([
                    'custom_test_string', 'custom_test_text', 'custom_test_number', 'custom_test_date', 'custom_test_datetime',
                    'custom_test_dropdown', 'custom_test_multidropdown', 'custom_test_url', 'custom_test_yesno'
                ]);
            });
        });
    });

    it('Edit core fields', () => {
        cy.visit('/front/asset/assetdefinition.form.php');
        const asset_name = getAssetName();
        cy.findByLabelText(/System name/i).type(asset_name);
        cy.findByLabelText("Active").select('1', { force: true });
        cy.findByRole('button', {name: "Add"}).click();
        cy.get('div.toast-container .toast-body a').click();
        cy.url().should('include', '/front/asset/assetdefinition.form.php?id=');

        cy.findByRole('tab', {name:  /^Fields/}).click();

        cy.intercept({
            pathname: '/ajax/asset/assetdefinition.php',
            query: { action: 'get_all_fields' },
            times: 1
        }, (req) => {
            req.reply({ results: [] });
        });

        cy.get('.sortable-field[data-key="name"] .edit-field').click({force: true});
        cy.get('#core_field_options_editor').within(() => {
            cy.findByLabelText('Full width').should('be.visible').check();
            cy.findByLabelText('Readonly').should('be.visible').check();
            cy.findByLabelText('Mandatory').should('be.visible').check();
            cy.intercept('POST', '/ajax/asset/assetdefinition.php').as('saveFieldOptions');
            cy.findByRole('button', {name: 'Save'}).click();
        });
        cy.get('input[name="field_options[name][full_width]"]').should('have.value', '1');
        cy.get('input[name="field_options[name][readonly]"]').should('have.value', '1');
        cy.get('input[name="field_options[name][required]"]').should('have.value', '1');
    });
});
