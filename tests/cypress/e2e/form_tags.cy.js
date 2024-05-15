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

describe('Form tags', () => {
    beforeEach(() => {
        cy.createWithAPI('Glpi\\Form\\Form', {
            'name': 'Test form for the form tags suite',
        }).as('form_id').then((form_id) => {
            cy.createWithAPI('Glpi\\Form\\Destination\\FormDestination', {
                'forms_forms_id': form_id,
                'itemtype': 'Glpi\\Form\\Destination\\FormDestinationTicket',
                'name': 'Test ticket 1',
            });
        });

        cy.login();
        cy.changeProfile('Super-Admin', true);

        cy.get('@form_id').then((form_id) => {
            const tab = 'Glpi\\Form\\Destination\\FormDestination$1';
            cy.visit(`/front/form/form.form.php?id=${form_id}&forcetab=${tab}`);
            cy.findByRole('button', {name: "Test ticket 1"}).click();
        });
    });

    it('tags autocompletion is loaded and values are preserved on reload', () => {
        // Auto completion is not yet opened
        cy.findByRole("menuitem", {name: "Exemple tag 1"}).should('not.exist');
        cy.findByRole("menuitem", {name: "Exemple tag 2"}).should('not.exist');
        cy.findByRole("menuitem", {name: "Exemple tag 3"}).should('not.exist');

        // Use autocomplete
        cy.findByLabelText("Content").awaitTinyMCE().as("rich_text_editor");
        cy.get("@rich_text_editor").type("#");
        cy.findByRole("menuitem", {name: "Exemple tag 1"}).should('exist');
        cy.findByRole("menuitem", {name: "Exemple tag 2"}).should('exist');
        cy.findByRole("menuitem", {name: "Exemple tag 3"}).should('exist').click();

        // Auto completion UI is terminated after clicking on the item.
        cy.findByRole("menuitem", {name: "Exemple tag 1"}).should('not.exist');
        cy.findByRole("menuitem", {name: "Exemple tag 2"}).should('not.exist');
        cy.findByRole("menuitem", {name: "Exemple tag 3"}).should('not.exist');

        // Item has been inserted into rich text
        cy.get("@rich_text_editor")
            .findByText("Exemple tag 3")
            .should('have.attr', 'contenteditable', 'false')
            .should('have.attr', 'data-form-tag', 'true')
            .should('have.attr', 'data-form-tag-value', 'exemple-tag-3')
        ;

        // Save form
        cy.findByRole("button", {name: "Update item"}).click();

        // Rich text content provided by autocompleted values should be displayed properly
        cy.findByLabelText("Content").awaitTinyMCE()
            .findByText("Exemple tag 3")
            .should('have.attr', 'contenteditable', 'false')
            .should('have.attr', 'data-form-tag', 'true')
            .should('have.attr', 'data-form-tag-value', 'exemple-tag-3')
        ;
    });
});
