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

describe ('Form editor', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin');
        cy.createFormWithAPI().as('form_id').visitFormTab('Form');
    });

    it('can add a new question and verify it is not within a horizontal block', () => {
        // Add a question
        cy.findByRole('button', {'name': 'Add a question'}).click();

        // Check if the question isn't in a horizontal block
        cy.findByRole('region', {'name': 'Horizontal blocks layout'}).should('not.exist');
    });

    it('can create and verify a horizontal block with questions and a comment', () => {
        cy.findByRole('button', {'name': 'Add a horizontal layout'}).click();
        cy.findByRole('region', {'name': 'Horizontal blocks layout'}).within(() => {
            // Placeholder toolbars must be hidden when the placeholder is not active
            cy.findByRole('button', {'name': 'Add a question'}).should('not.exist');

            cy.findAllByRole('option', {'name': 'Form horizontal block placeholder'}).eq(0).click();
            cy.findAllByRole('option', {'name': 'Form horizontal block placeholder'}).eq(0)
                .findByRole('button', {'name': 'Add a question'}).click();
            cy.findByRole('region', {'name': 'Question details'}).within(() => {
                cy.findByRole('textbox', {'name': 'Question name'}).type('First question');
                cy.getDropdownByLabelText("Question type").selectDropdownValue('Short answer');
                cy.getDropdownByLabelText("Question sub type").selectDropdownValue('Text');
            });

            // Add a comment from the other placeholder
            cy.findByRole('option', {'name': 'Form horizontal block placeholder'}).click();
            cy.findByRole('option', {'name': 'Form horizontal block placeholder'})
                .findByRole('button', {'name': 'Add a comment'}).click();
            cy.findByRole('region', {'name': 'Comment details'}).within(() => {
                cy.findByRole('textbox', {'name': 'Comment title'}).type('Comment title');
                cy.findByLabelText("Comment description")
                    .awaitTinyMCE()
                    .type("Comment description");
            });
        });

        // Save and reload
        cy.saveFormEditorAndReload();

        // Check that the horizontal block is displayed
        cy.findByRole('region', {'name': 'Horizontal blocks layout'}).within(() => {
            cy.findByRole('region', {'name': 'Question details'}).within(() => {
                cy.findByRole('textbox', {'name': 'Question name'}).should('have.value', 'First question');
            });
            cy.findByRole('region', {'name': 'Comment details'}).within(() => {
                cy.findByRole('textbox', {'name': 'Comment title'}).should('have.value', 'Comment title');
                cy.findByLabelText("Comment description").should('have.text', '<p>Comment description</p>');
            });
        });
    });

    it('should not allow adding more than 4 placeholders in a horizontal block', () => {
        cy.findByRole('button', {'name': 'Add a horizontal layout'}).click();
        cy.findByRole('region', {'name': 'Horizontal blocks layout'}).within(() => {
            cy.findAllByRole('option', {'name': 'Form horizontal block placeholder'}).should('have.length', 2);
            for (let index = 3; index < 5; index++) {
                cy.findByRole('button', {'name': 'Add a slot'}).click();
                cy.findAllByRole('option', {'name': 'Form horizontal block placeholder'}).should('have.length', index);
            }
            cy.findByRole('button', {'name': 'Add a slot'}).should('not.exist');
        });
    });


    it('should not allow adding more than 4 questions in a horizontal block using placeholder toolbar', () => {
        cy.findByRole('button', {'name': 'Add a horizontal layout'}).click();
        cy.findByRole('region', {'name': 'Horizontal blocks layout'}).within(() => {
            cy.findByRole('button', {'name': 'Add a slot'}).click();
            cy.findByRole('button', {'name': 'Add a slot'}).click();

            for (let index = 0; index < 4; index++) {
                // Focus the first placeholder
                cy.findAllByRole('option', {'name': 'Form horizontal block placeholder'}).eq(0).click();

                // Add a question
                cy.findAllByRole('option', {'name': 'Form horizontal block placeholder'}).eq(0)
                    .findByRole('button', {'name': 'Add a question'}).click();
            }
        });
    });

    it('should not allow adding more than 4 questions in a horizontal block using question duplicate', () => {
        cy.findByRole('button', {'name': 'Add a horizontal layout'}).click();
        cy.findByRole('region', {'name': 'Horizontal blocks layout'}).within(() => {
            // Focus the first placeholder
            cy.findAllByRole('option', {'name': 'Form horizontal block placeholder'}).eq(0).click();

            // Add a question
            cy.findAllByRole('option', {'name': 'Form horizontal block placeholder'}).eq(0)
                .findByRole('button', {'name': 'Add a question'}).click();

            for (let index = 0; index < 3; index++) {
                // Duplicate the question
                cy.findAllByRole('button', {'name': 'Duplicate question'}).eq(-1).click();
            }
        });

        // Check that the duplicate button disappears
        cy.findAllByRole('button', {'name': 'Duplicate question'}).should('not.exist');

        // Check that the new slot button disappears
        cy.findByRole('button', {'name': 'Add a slot'}).should('not.exist');
    });

    it('can save a form with a horizontal block with only one question', () => {
        cy.findByRole('button', {'name': 'Add a horizontal layout'}).click();
        cy.findByRole('region', {'name': 'Horizontal blocks layout'}).within(() => {
            // Focus the first placeholder
            cy.findAllByRole('option', {'name': 'Form horizontal block placeholder'}).eq(0).click();

            cy.findAllByRole('option', {'name': 'Form horizontal block placeholder'}).eq(0)
                .findByRole('button', {'name': 'Add a question'}).click();
            cy.findByRole('region', {'name': 'Question details'}).within(() => {
                cy.findByRole('textbox', {'name': 'Question name'}).type('First question');
                cy.getDropdownByLabelText("Question type").selectDropdownValue('Short answer');
                cy.getDropdownByLabelText("Question sub type").selectDropdownValue('Text');
            });
        });

        // Save and reload
        cy.saveFormEditorAndReload();

        // Check that the horizontal block is displayed
        cy.findByRole('region', {'name': 'Horizontal blocks layout'}).within(() => {
            cy.findByRole('region', {'name': 'Question details'}).within(() => {
                cy.findByRole('textbox', {'name': 'Question name'}).should('have.value', 'First question');
            });
        });
    });

    it('can remove a horizontal block and verify the questions are still displayed', () => {
        cy.findByRole('button', {'name': 'Add a horizontal layout'}).click();
        cy.findByRole('region', {'name': 'Horizontal blocks layout'}).within(() => {
            // Focus the first placeholder
            cy.findAllByRole('option', {'name': 'Form horizontal block placeholder'}).eq(0).click();

            // Add a question
            cy.findAllByRole('option', {'name': 'Form horizontal block placeholder'}).eq(0)
                .findByRole('button', {'name': 'Add a question'}).click();
            cy.findByRole('region', {'name': 'Question details'}).within(() => {
                cy.findByRole('textbox', {'name': 'Question name'}).type('First question');
            });

            // Focus the second placeholder
            cy.findAllByRole('option', {'name': 'Form horizontal block placeholder'}).eq(0).click();

            // Add a question
            cy.findAllByRole('option', {'name': 'Form horizontal block placeholder'}).eq(0)
                .findByRole('button', {'name': 'Add a question'}).click();
            cy.findAllByRole('region', {'name': 'Question details'}).eq(1).within(() => {
                cy.findByRole('textbox', {'name': 'Question name'}).type('Second question');
            });
        });

        // Save and reload
        cy.saveFormEditorAndReload();

        // Remove the horizontal block
        cy.findByRole('button', {'name': 'Remove horizontal layout'}).click();

        // Save and reload
        cy.saveFormEditorAndReload();

        // Check that the questions are displayed and not in a horizontal block
        cy.findAllByRole('region', {'name': 'Horizontal blocks layout'}).should('not.exist');
        cy.findAllByRole('region', {'name': 'Question details'}).eq(0).within(() => {
            cy.findByRole('textbox', {'name': 'Question name'}).should('have.value', 'First question');
        });
        cy.findAllByRole('region', {'name': 'Question details'}).eq(1).within(() => {
            cy.findByRole('textbox', {'name': 'Question name'}).should('have.value', 'Second question');
        });
    });
});
