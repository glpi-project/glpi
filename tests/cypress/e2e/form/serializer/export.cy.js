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

describe ('Export forms', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin', true);
    });

    it('Export single form', () => {
        cy.createFormWithAPI({
            'name': "My form",
        }).visitFormTab('Form');
        cy.findByRole('button', { 'name': "Actions" }).click();
        cy.findByRole('button', { 'name': "Export form" }).click();
        cy.readFile("cypress/downloads/my-form.json").then((json) => {
            cy.wrap(json.forms).should('have.length', 1);
        });
    });

    it('Export multiple form', () => {
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
        cy.readFile("cypress/downloads/export-of-3-forms.json").then((json) => {
            cy.wrap(json.forms).should('have.length', 3);
        });
    });
});
