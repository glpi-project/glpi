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

describe("Asset Definition", () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin');
    });

    it('Profiles tab', () => {
        cy.createWithAPI('Glpi\\Asset\\AssetDefinition', {
            system_name: 'test' + Math.random().toString(36).replace(/[^a-z]+/g, ''),
            is_active: true,
        }).then((definition_id) => {
            cy.visit('front/asset/assetdefinition.form.php?id=' + definition_id);
            cy.findByRole('tab', { name: 'Profiles' }).click();
            cy.findByLabelText('Show extra fields').should('be.checked');
            cy.findAllByLabelText('Associable items to tickets, changes and problems').should('be.visible');

            cy.findByLabelText('Show extra fields').click();
            cy.findByLabelText('Show extra fields').should('not.be.checked');

            cy.findAllByLabelText('Associable items to tickets, changes and problems').should('not.be.visible');
        });
    });
});
