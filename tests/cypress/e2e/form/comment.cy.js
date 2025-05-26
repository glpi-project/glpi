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

describe('Comment form', () => {
    beforeEach(() => {
        cy.createWithAPI('Glpi\\Form\\Form', {
            'name': 'Tests form for the comment form suite',
        }).as('form_id');

        cy.login();
        cy.changeProfile('Super-Admin');

        cy.get('@form_id').then((form_id) => {
            const tab = 'Glpi\\Form\\Form$main';
            cy.visit(`/front/form/form.form.php?id=${form_id}&forcetab=${tab}`);

            // Add a comment
            cy.findByRole("button", { name: "Add a comment" }).should('exist').click();
        });
    });

    // Helper function to set comment title and description
    function setCommentTitleAndDescription(title, description) {
        // Set comment title and description
        cy.findByRole("textbox", { name: "Comment title" }).should('exist').type(title);
        cy.findByRole("textbox", { name: "Comment title" }).closest(".card-body")
            .find("textarea").should('exist').awaitTinyMCE().type(description);
    }

    // Helper function to save the form
    function saveForm() {
        // Save the form (force is required because the button is hidden by a toast message)
        cy.findByRole("button", { name: "Save" }).click({ force: true });
    }

    it('test fill header and description and check persistence', () => {
        setCommentTitleAndDescription("Test comment title", "Test comment description");
        saveForm();

        // Reload the page to check if the options are still selected
        cy.reload();

        // Check if the values are still present
        cy.findByRole("textbox", { name: "Comment title" }).should('exist').and('have.value', "Test comment title");
        cy.findByRole("textbox", { name: "Comment title" }).closest(".card-body")
            .find("textarea").should('exist').and('have.value', "<p>Test comment description</p>");
    });

    it('test fill header and description and check persistence in preview', () => {
        setCommentTitleAndDescription("Test comment title", "Test comment description");
        saveForm();

        // Go to preview page (remove the target="_blank" attribute to stay in the same window)
        cy.findByRole("link", { name: "Preview" })
            .invoke('attr', 'target', '_self')
            .click();

        // Check if the values are still present
        cy.findByRole("heading", { name: "Test comment title" }).should('exist');
        cy.findByRole("heading", { name: "Test comment title" }).closest(".card-body")
            .findByText("Test comment description").should('exist');
    });
});
