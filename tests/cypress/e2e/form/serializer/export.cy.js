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

describe ('Export forms', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin');
    });

    it('Export single form', () => {
        cy.intercept('/Form/Export?*').as('download_request');

        cy.createFormWithAPI({
            'name': "My form",
        }).visitFormTab('Form');
        cy.findByRole('button', { 'name': "Actions" }).click();
        cy.findByRole('button', { 'name': "Export form" }).click();

        cy.wait('@download_request').then((res) => {
            // The filename is dynamic, we must read it from the reponse's headers
            const filename = res.response.headers['content-disposition'].split('filename=')[1];
            cy.readFile(`cypress/downloads/${filename}`).then((json) => {
                cy.wrap(json.forms).should('have.length', 1);
            });
        });
    });

    it('Export multiple form', () => {
        cy.intercept('/Form/Export?*').as('download_request');

        cy.createFormWithAPI();
        cy.createFormWithAPI();
        cy.createFormWithAPI();

        cy.visit('/front/form/form.php');
        cy.findAllByRole('checkbox', { 'name': "Select item" }).as('checkboxes');
        cy.get('@checkboxes').eq(0).check();
        cy.get('@checkboxes').eq(1).check();
        cy.get('@checkboxes').eq(2).check();
        cy.findByRole('button', { 'name': "Actions" }).click();
        cy.getDropdownByLabelText('Action').selectDropdownValue('Export form');

        cy.wait('@download_request').then((res) => {
            // The filename is dynamic, we must read it from the reponse's headers
            const filename = res.response.headers['content-disposition'].split('filename=')[1];
            cy.readFile(`cypress/downloads/${filename}`).then((json) => {
                cy.wrap(json.forms).should('have.length', 3);
            });
        });
    });
});
