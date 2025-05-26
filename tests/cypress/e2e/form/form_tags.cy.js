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

let last_name_question_id;

describe('Form tags', () => {
    beforeEach(() => {
        cy.createWithAPI('Glpi\\Form\\Form', {
            'name': 'Test form for the form tags suite',
        }).as('form_id').then((form_id) => {
            cy.createWithAPI('Glpi\\Form\\Section', {
                'name': 'Section 1',
                'forms_forms_id': form_id,
            }).then((section_id) => {
                cy.createWithAPI('Glpi\\Form\\Question', {
                    'name': 'First name',
                    'forms_sections_id': section_id,
                    'type': 'Glpi\\Form\\QuestionType\\QuestionTypeShortText',
                });
                cy.createWithAPI('Glpi\\Form\\Question', {
                    'name': 'Last name',
                    'forms_sections_id': section_id,
                    'type': 'Glpi\\Form\\QuestionType\\QuestionTypeShortText',
                }).then((question_id) => {
                    last_name_question_id = question_id;
                });
                cy.createWithAPI('Glpi\\Form\\Comment', {
                    'name': 'Comment title',
                    'description': 'Comment description',
                    'forms_sections_id': section_id,
                });
            });
        });

        cy.login();
        cy.changeProfile('Super-Admin');

        cy.get('@form_id').then((form_id) => {
            const tab = 'Glpi\\Form\\Destination\\FormDestination$1';
            cy.visit(`/front/form/form.form.php?id=${form_id}&forcetab=${tab}`);
        });
    });

    it('tags autocompletion is loaded and values are preserved on reload', () => {
        // Auto completion is not yet opened
        cy.findByRole("menuitem", {name: "Form name: Test form for the form tags suite"}).should('not.exist');
        cy.findByRole("menuitem", {name: "Section: Section 1"}).should('not.exist');
        cy.findByRole("menuitem", {name: "Question: First name"}).should('not.exist');
        cy.findByRole("menuitem", {name: "Question: Last name"}).should('not.exist');
        cy.findByRole("menuitem", {name: "Answer: First name"}).should('not.exist');
        cy.findByRole("menuitem", {name: "Answer: Last name"}).should('not.exist');
        cy.findByRole("menuitem", {name: "Comment title: Comment title"}).should('not.exist');
        cy.findByRole("menuitem", {name: "Comment description: Comment description"}).should('not.exist');

        // Remove auto configuration to allow us to type into the content field
        cy.findByRole('region', {name: 'Content configuration'}).awaitTinyMCE().as("rich_text_editor");
        cy.findByRole('region', {'name': "Content configuration"})
            .findByRole('checkbox', {'name': "Auto config"})
            .uncheck()
        ;

        // Use autocomplete
        cy.get("@rich_text_editor").clear();
        cy.get("@rich_text_editor").type("#");
        cy.findByRole("menuitem", {name: "Form name: Test form for the form tags suite"}).should('exist');
        cy.findByRole("menuitem", {name: "Section: Section 1"}).should('exist');
        cy.findByRole("menuitem", {name: "Question: First name"}).should('exist');
        cy.findByRole("menuitem", {name: "Question: Last name"}).should('exist');
        cy.findByRole("menuitem", {name: "Answer: First name"}).should('exist');
        cy.findByRole("menuitem", {name: "Answer: Last name"}).should('exist');
        cy.findByRole("menuitem", {name: "Comment title: Comment title"}).should('exist');
        cy.findByRole("menuitem", {name: "Comment description: Comment description"}).should('exist');

        // Filter results
        cy.get("@rich_text_editor").type("Last");
        cy.findByRole("menuitem", {name: "Form name: Test form for the form tags suite"}).should('not.exist');
        cy.findByRole("menuitem", {name: "Question: First name"}).should('not.exist');
        cy.findByRole("menuitem", {name: "Question: First name"}).should('not.exist');
        cy.findByRole("menuitem", {name: "Question: Last name"}).should('exist');
        cy.findByRole("menuitem", {name: "Answer: First name"}).should('not.exist');
        cy.findByRole("menuitem", {name: "Answer: Last name"}).should('exist');
        cy.findByRole("menuitem", {name: "Comment title: Comment title"}).should('not.exist');
        cy.findByRole("menuitem", {name: "Comment description: Comment description"}).should('not.exist');

        // Auto completion UI is terminated after clicking on the item.
        cy.findByRole("menuitem", {name: "Question: Last name"}).click();
        cy.findByRole("menuitem", {name: "Form name: Test form for the form tags suite"}).should('not.exist');
        cy.findByRole("menuitem", {name: "Section: Section 1"}).should('not.exist');
        cy.findByRole("menuitem", {name: "Question: Last name"}).should('not.exist');
        cy.findByRole("menuitem", {name: "Answer: Last name"}).should('not.exist');
        cy.findByRole("menuitem", {name: "Comment title: Comment title"}).should('not.exist');
        cy.findByRole("menuitem", {name: "Comment description: Comment description"}).should('not.exist');

        // Item has been inserted into rich text
        cy.get("@rich_text_editor")
            .findByText("#Question: Last name")
            .should('have.attr', 'contenteditable', 'false')
            .should('have.attr', 'data-form-tag', 'true')
            .should('have.attr', 'data-form-tag-value', last_name_question_id)
        ;

        // Save form
        cy.findByRole("button", {name: "Update item"}).click();

        // Rich text content provided by autocompleted values should be displayed properly
        cy.findByRole('region', {name: 'Content configuration'}).awaitTinyMCE()
            .findByText("#Question: Last name")
            .should('have.attr', 'contenteditable', 'false')
            .should('have.attr', 'data-form-tag', 'true')
            .should('have.attr', 'data-form-tag-value', last_name_question_id)
        ;
    });
});
