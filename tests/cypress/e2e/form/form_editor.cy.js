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

describe ('Form editor', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin', true);
    });

    it('can create a form and fill its main details', () => {
        // Go to form creation page
        cy.visit('/front/form/form.php');
        cy.findByRole('link', {'name': 'Add'}).click();
        cy.findByRole('tab', {'name': 'Form'}).click();

        // Edit form details
        cy.findByRole('region', {'name': 'Form details'}).within(() => {
            cy.findByRole('textbox', {'name': 'Form name'})
                .type("My form name")
            ;

            cy.findByRole('checkbox', {'name': 'Active'})
                .should('not.to.be.checked')
                .check()
            ;

            cy.findByLabelText("Form description")
                .awaitTinyMCE()
                .type("My form description")
            ;
        });

        // Save form and reload page to force new data to be displayed.
        cy.findByRole('button', {'name': 'Add'}).click();
        cy.findByRole('alert').should('contain.text', 'Item successfully updated');
        cy.reload();

        // Validate that the new values are displayed
        cy.findByRole('region', {'name': 'Form details'}).within(() => {
            cy.findByRole('textbox', {'name': 'Form name'})
                .should('have.value', 'My form name')
            ;
            cy.findByRole('checkbox', {'name': 'Active'})
                .should('be.checked')
                .check()
            ;
            cy.findByLabelText("Form description")
                .awaitTinyMCE()
                .should('have.text', 'My form description')
            ;
        });
    });

    it('can create and delete a question', () => {
        cy.createFormWithAPI().visitFormTab('Form');

        describe('create question', () => {
            cy.findByRole('button', {'name': 'Add a new question'}).click();

            // Edit form details
            cy.focused().type("My question"); // Question name is focused by default
            cy.findByRole('region', {'name': 'Question details'}).within(() => {
                cy.findByRole('checkbox', {'name': 'Mandatory'})
                    .should('not.be.checked')
                    .check()
                ;
                cy.findByLabelText("Question description")
                    .awaitTinyMCE()
                    .type("My question description")
                ;
            });

            // Save form and reload page to force new data to be displayed.
            cy.findByRole('button', {'name': 'Save'}).click();
            cy.findByRole('alert')
                .should('contain.text', 'Item successfully updated')
            ;
            cy.reload();
        });

        describe('validate question content', () => {
            // Validate that the new values are displayed
            cy.findByRole('region', {'name': 'Question details'}).within(() => {
                cy.findByRole('textbox', {'name': 'Question name'})
                    .should('have.value', 'My question')
                    .click() // Click to make sure form focus is on the question
                ;
                cy.findByRole('checkbox', {'name': 'Mandatory'})
                    .should('be.checked')
                ;
                cy.findByLabelText("Question description")
                    .awaitTinyMCE()
                    .should('have.text', 'My question description')
                ;
            });
        });

        describe('delete question', () => {
            cy.findByRole('region', {'name': 'Question details'})
                .as("question_details")
            ;

            // Focus question to display hiden actions
            cy.get("@question_details").click();
            cy.get("@question_details").within(() => {
                cy.findByRole('button', {'name': 'Delete'}).click();
            });
            cy.get("@question_details").should('not.exist');

            // Save form and reload page to force latest state to be displayed.
            cy.findByRole('button', {'name': 'Save'}).click();
            cy.findByRole('alert')
                .should('contain.text', 'Item successfully updated')
            ;
            cy.reload();
            cy.get("@question_details").should('not.exist');
        });
    });
});
