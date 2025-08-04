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

describe("Computer asset form", () => {
    let computers_id;

    before(() => {
        cy.createWithAPI('Computer', {
            name: 'Test computer',
            entities_id: 1
        }).then(id => computers_id = id);
    });

    beforeEach(() => {
        cy.login();
    });

    it('Main form loads', () => {
        cy.visit(`/front/computer.form.php?id=${computers_id}`);
        cy.findByRole('tabpanel').within(() => {
            cy.findByRole('textbox', { name: 'Name' }).should('have.value', 'Test computer');
            cy.findByRole('button', {name: 'Put in trashbin'}).should('be.visible');
            cy.findByRole('button', {name: 'Save'}).should('be.visible');
        });
    });

    it('Virtual machine tab loads', () => {
        cy.visit(`/front/computer.form.php?id=${computers_id}`);
        cy.findByRole('tab', { name: /Virtualization/ }).click();
        cy.findByRole('tabpanel').within(() => {
            cy.findByRole('cell').should('contain.text', 'No results found');
            cy.findByRole('button', {name: 'Add a virtual machine'}).click();
            cy.findByRole('textbox', { name: 'Name' }).type('Test VM');
            cy.findByRole('button', {name: 'Add'}).click();
            cy.findAllByRole('cell').contains('Test VM').should('be.visible');
        });
    });

    it('Antivirus tab loads', () => {
        cy.visit(`/front/computer.form.php?id=${computers_id}`);
        cy.findByRole('tab', { name: /Antiviruses/ }).click();
        cy.findByRole('tabpanel').within(() => {
            cy.findByRole('cell').should('contain.text', 'No results found');
            cy.findByRole('button', {name: 'Add an antivirus'}).click();
            cy.findByRole('textbox', { name: 'Name' }).type('Test AV');
            cy.findByRole('button', {name: 'Add'}).click();
            cy.findAllByRole('cell').contains('Test AV').should('be.visible');
        });
    });
});
