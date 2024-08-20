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

Cypress.Commands.add('createFormWithAPI', (
    fields = {
        name: "My test form",
    }
) => {
    return cy.createWithAPI('Glpi\\Form\\Form', fields).then((form_id) => {
        return form_id;
    });
});

Cypress.Commands.add('visitFormTab', {prevSubject: true}, (
    form_id,
    tab_name
) => {
    const fully_qualified_tabs = new Map([
        ['Form', 'Glpi\\Form\\Form\\Form$main'],
        ['Destinations', 'Glpi\\Form\\Destination\\FormDestination$1'],
        ['ServiceCatalog', 'Glpi\\Form\\ServiceCatalog\\ServiceCatalog$1'],
    ]);
    const tab = fully_qualified_tabs.get(tab_name);

    return cy.visit(`/front/form/form.form.php?id=${form_id}&forcetab=${tab}`);
});

Cypress.Commands.add('saveFormEditorAndReload', () => {
    cy.findByRole('button', {'name': 'Save'}).click();
    cy.findByRole('alert')
        .should('contain.text', 'Item successfully updated')
    ;
    cy.reload();
});

Cypress.Commands.add('addQuestion', (name) => {
    cy.findByRole('button', {'name': 'Add a new question'}).click();
    cy.focused().type(name); // Question name is focused by default
});

Cypress.Commands.add('addSection', (name) => {
    cy.findByRole('button', {'name': 'Add a new section'}).click();
    cy.focused().type(name); // Section name is focused by default
});
