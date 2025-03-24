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

describe("Custom Assets - Definitions", () => {
    beforeEach(() => {
        cy.login();
    });

    it('Show translations form', () => {
        // This can be split into multiple tests when the new API supports custom assets and custom fields
        cy.visit('/front/asset/assetdefinition.form.php');
        const asset_name_chars = 'abcdefghijklmnopqrstuvwxyz';
        const asset_name = `customasset${Array.from({ length: 10 }, () => asset_name_chars.charAt(Math.floor(Math.random() * asset_name_chars.length))).join('')}`;
        cy.findByLabelText(/System name/i).type(asset_name);
        cy.findByLabelText("Active").select('1', { force: true });
        cy.findByRole('button', {name: "Add"}).click();

        cy.get('div.toast-container .toast-body a').click();
        cy.url().should('include', '/front/asset/assetdefinition.form.php?id=');

        cy.findByRole('tab', {name: /Translations/}).click();

        cy.findByRole('button', {name: /New translation/}).parents('.tab-pane').should('have.class', 'active').first().within(() => {
            cy.root().invoke('text').should('include', 'No translation has been added yet');
            cy.findByRole('button', {name: /New translation/}).click();
            cy.findByRole('dialog').should('be.visible').within(() => {
                cy.get('.modal-header').invoke('text').should('include', 'Add translation');
                cy.findByLabelText('Asset name / Field').siblings('.select2-container').should('be.visible');
                cy.findByLabelText('Language').siblings('.select2-container').should('be.visible');
                cy.get('input[name="plurals[one]"]').should('not.exist');
                cy.findByLabelText('Language').select('fr_FR', { force: true });
                cy.get('input[name="plurals[one]"]').should('be.visible');
            });
        });
    });

    it('Profiles tab', () => {
        const system_name = `test${Math.random().toString(36).replace(/[^a-z0-9_]+/g, '')}`;
        cy.createWithAPI('Glpi\\Asset\\AssetDefinition', {
            system_name: system_name,
            is_active: true,
        }).then((definition_id) => {
            cy.visit(`front/asset/assetdefinition.form.php?id=${definition_id}`);
            cy.findByRole('tab', { name: 'Profiles' }).click();
            cy.findAllByLabelText(`Profiles that can associate ${system_name} with tickets, problems or changes`).should('be.visible');
        });
    });
});
