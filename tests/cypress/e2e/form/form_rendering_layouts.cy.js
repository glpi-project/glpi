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

describe('Form rendering layouts', () => {
    beforeEach(() => {
        cy.login();
    });

    describe('Step-by-step layout (default)', () => {
        it('Shows navigation buttons and one section at a time with validation', () => {
            // Create a form with step-by-step layout (default)
            cy.createWithAPI('Glpi\\Form\\Form', {
                'name': 'Step-by-step form',
                'render_layout': 'step_by_step'
            }).as('form_id');

            cy.get('@form_id').then((form_id) => {
                // Add mandatory question to default section
                cy.addQuestionToDefaultSectionWithAPI(
                    form_id,
                    'First question',
                    'Glpi\\Form\\QuestionType\\QuestionTypeShortText',
                    0,
                    null,
                    null,
                    null,
                    true // Mandatory
                );

                // Add second section with mandatory question
                cy.createWithAPI('Glpi\\Form\\Section', {
                    'name': 'Second section',
                    'rank': 1,
                    'forms_forms_id': form_id,
                }).then((second_section_id) => {
                    cy.createWithAPI('Glpi\\Form\\Question', {
                        'name': 'Second question',
                        'type': 'Glpi\\Form\\QuestionType\\QuestionTypeShortText',
                        'vertical_rank': 1,
                        'forms_sections_id': second_section_id,
                        'is_mandatory': true,
                    });
                });

                // Add third section with optional question
                cy.createWithAPI('Glpi\\Form\\Section', {
                    'name': 'Third section',
                    'rank': 2,
                    'forms_forms_id': form_id,
                }).then((third_section_id) => {
                    cy.createWithAPI('Glpi\\Form\\Question', {
                        'name': 'Third question',
                        'type': 'Glpi\\Form\\QuestionType\\QuestionTypeShortText',
                        'vertical_rank': 1,
                        'forms_sections_id': third_section_id,
                        'is_mandatory': false,
                    });
                });

                // Visit the form
                cy.visit(`/Form/Render/${form_id}`);

                // Should show only first section
                cy.findByRole('heading', { name: 'First section' }).should('be.visible');
                cy.findByRole('heading', { name: 'Second section' }).should('not.exist');
                cy.findByRole('heading', { name: 'Third section' }).should('not.exist');

                // Should show Continue button but no Back button on first section
                cy.findByRole('button', { name: 'Continue' }).should('be.visible');
                cy.findByRole('button', { name: 'Back' }).should('not.exist');
                cy.findByRole('button', { name: 'Submit' }).should('not.exist');

                // Try to continue without filling mandatory field
                cy.findByRole('button', { name: 'Continue' }).click();
                checkMandatoryQuestion('First question');
                cy.findByRole('heading', { name: 'First section' }).should('be.visible');

                // Fill first question and continue
                cy.findByRole('textbox', { name: 'First question' }).type('Answer 1');
                cy.findByRole('button', { name: 'Continue' }).click();

                // Should now show second section only
                cy.findByRole('heading', { name: 'Second section' }).should('be.visible');
                cy.findByRole('heading', { name: 'First section' }).should('not.exist');
                cy.findByRole('heading', { name: 'Third section' }).should('not.exist');

                // Should show both Back and Continue buttons
                cy.findByRole('button', { name: 'Back' }).should('be.visible');
                cy.findByRole('button', { name: 'Continue' }).should('be.visible');
                cy.findByRole('button', { name: 'Submit' }).should('not.exist');

                // Test back navigation
                cy.findByRole('button', { name: 'Back' }).click();
                cy.findByRole('heading', { name: 'First section' }).should('be.visible');
                cy.findByRole('textbox', { name: 'First question' }).should('have.value', 'Answer 1');

                // Navigate forward again
                cy.findByRole('button', { name: 'Continue' }).click();
                cy.findByRole('heading', { name: 'Second section' }).should('be.visible');

                // Try to continue without filling second mandatory field
                cy.findByRole('button', { name: 'Continue' }).click();
                checkMandatoryQuestion('Second question');

                // Fill second question and continue
                cy.findByRole('textbox', { name: 'Second question' }).type('Answer 2');
                cy.findByRole('button', { name: 'Continue' }).click();

                // Should now show third section (last one)
                cy.findByRole('heading', { name: 'Third section' }).should('be.visible');
                cy.findByRole('heading', { name: 'Second section' }).should('not.exist');

                // Should show Back and Submit buttons (no Continue on last section)
                cy.findByRole('button', { name: 'Back' }).should('be.visible');
                cy.findByRole('button', { name: 'Submit' }).should('be.visible');
                cy.findByRole('button', { name: 'Continue' }).should('not.exist');

                // Can submit without filling optional field
                cy.findByRole('button', { name: 'Submit' }).click();
                cy.findByText('Form submitted').should('be.visible');
            });
        });

        it('Works correctly with single section form', () => {
            // Create form with only one section
            cy.createWithAPI('Glpi\\Form\\Form', {
                'name': 'Single section step-by-step form',
                'render_layout': 'step_by_step'
            }).as('form_id');

            cy.get('@form_id').then((form_id) => {
                // Add question to default section
                cy.addQuestionToDefaultSectionWithAPI(
                    form_id,
                    'Only question',
                    'Glpi\\Form\\QuestionType\\QuestionTypeShortText',
                    0
                );

                cy.visit(`/Form/Render/${form_id}`);

                // If the form has only one section, it should not show the section title
                cy.findByRole('heading', { name: 'First section' }).should('not.exist');

                // Should show only Submit button (no navigation needed)
                cy.findByRole('button', { name: 'Submit' }).should('be.visible');
                cy.findByRole('button', { name: 'Continue' }).should('not.exist');
                cy.findByRole('button', { name: 'Back' }).should('not.exist');

                // Should be able to submit
                cy.findByRole('textbox', { name: 'Only question' }).type('Answer');
                cy.findByRole('button', { name: 'Submit' }).click();
                cy.findByText('Form submitted').should('be.visible');
            });
        });
    });

    describe('Single page layout', () => {
        it('Shows all sections at once with only submit button', () => {
            // Create a form with single page layout
            cy.createWithAPI('Glpi\\Form\\Form', {
                'name': 'Single page form',
                'render_layout': 'single_page'
            }).as('form_id');

            cy.get('@form_id').then((form_id) => {
                // Add question to default section
                cy.addQuestionToDefaultSectionWithAPI(
                    form_id,
                    'First question',
                    'Glpi\\Form\\QuestionType\\QuestionTypeShortText',
                    0,
                    null,
                    null,
                    null,
                    true // Mandatory
                );

                // Add second section
                cy.createWithAPI('Glpi\\Form\\Section', {
                    'name': 'Second section',
                    'rank': 1,
                    'forms_forms_id': form_id,
                }).then((second_section_id) => {
                    cy.createWithAPI('Glpi\\Form\\Question', {
                        'name': 'Second question',
                        'type': 'Glpi\\Form\\QuestionType\\QuestionTypeShortText',
                        'vertical_rank': 1,
                        'forms_sections_id': second_section_id,
                        'is_mandatory': true,
                    });
                });

                // Add third section
                cy.createWithAPI('Glpi\\Form\\Section', {
                    'name': 'Third section',
                    'rank': 2,
                    'forms_forms_id': form_id,
                }).then((third_section_id) => {
                    cy.createWithAPI('Glpi\\Form\\Question', {
                        'name': 'Third question',
                        'type': 'Glpi\\Form\\QuestionType\\QuestionTypeShortText',
                        'vertical_rank': 1,
                        'forms_sections_id': third_section_id,
                        'is_mandatory': false,
                    });
                });

                cy.visit(`/Form/Render/${form_id}`);

                // Should show all sections at once
                cy.findByRole('heading', { name: 'First section' }).should('be.visible');
                cy.findByRole('heading', { name: 'Second section' }).should('be.visible');
                cy.findByRole('heading', { name: 'Third section' }).should('be.visible');

                // All questions should be visible
                cy.findByRole('textbox', { name: 'First question' }).should('be.visible');
                cy.findByRole('textbox', { name: 'Second question' }).should('be.visible');
                cy.findByRole('textbox', { name: 'Third question' }).should('be.visible');

                // Should show only Submit button (no navigation buttons)
                cy.findByRole('button', { name: 'Submit' }).should('be.visible');
                cy.findByRole('button', { name: 'Continue' }).should('not.exist');
                cy.findByRole('button', { name: 'Back' }).should('not.exist');

                // Try to submit without filling mandatory fields
                cy.findByRole('button', { name: 'Submit' }).click();

                // Should show validation errors for both mandatory fields
                checkMandatoryQuestion('First question');
                checkMandatoryQuestion('Second question');

                // Form should not be submitted
                cy.findByText('Form submitted').should('not.be.visible');

                // Fill only first mandatory field
                cy.findByRole('textbox', { name: 'First question' }).type('Answer 1');
                cy.findByRole('button', { name: 'Submit' }).click();

                // Should still show error for second mandatory field
                checkMandatoryQuestion('Second question');
                cy.findByText('Form submitted').should('not.be.visible');

                // Fill second mandatory field
                cy.findByRole('textbox', { name: 'Second question' }).type('Answer 2');

                // Can submit without filling optional field
                cy.findByRole('button', { name: 'Submit' }).click();
                cy.findByText('Form submitted').should('be.visible');
            });
        });

        it('Works correctly with single section form', () => {
            // Create single page form with only one section
            cy.createWithAPI('Glpi\\Form\\Form', {
                'name': 'Single section single page form',
                'render_layout': 'single_page'
            }).as('form_id');

            cy.get('@form_id').then((form_id) => {
                // Add question to default section
                cy.addQuestionToDefaultSectionWithAPI(
                    form_id,
                    'Only question',
                    'Glpi\\Form\\QuestionType\\QuestionTypeShortText',
                    0
                );

                cy.visit(`/Form/Render/${form_id}`);

                // Should show the section but no section title (as it's the only one)
                cy.findByRole('heading', { name: 'First section' }).should('not.exist');
                cy.findByRole('textbox', { name: 'Only question' }).should('be.visible');

                // Should show only Submit button
                cy.findByRole('button', { name: 'Submit' }).should('be.visible');
                cy.findByRole('button', { name: 'Continue' }).should('not.exist');
                cy.findByRole('button', { name: 'Back' }).should('not.exist');

                // Should be able to submit
                cy.findByRole('textbox', { name: 'Only question' }).type('Answer');
                cy.findByRole('button', { name: 'Submit' }).click();
                cy.findByText('Form submitted').should('be.visible');
            });
        });

        it('Validates all sections at once with mixed field types', () => {
            // Create single page form with different question types
            cy.createWithAPI('Glpi\\Form\\Form', {
                'name': 'Mixed fields single page form',
                'render_layout': 'single_page'
            }).as('form_id');

            cy.get('@form_id').then((form_id) => {
                // Add text question
                cy.addQuestionToDefaultSectionWithAPI(
                    form_id,
                    'Text field',
                    'Glpi\\Form\\QuestionType\\QuestionTypeShortText',
                    0,
                    null,
                    null,
                    null,
                    true
                );

                // Add long text question
                cy.addQuestionToDefaultSectionWithAPI(
                    form_id,
                    'Long text field',
                    'Glpi\\Form\\QuestionType\\QuestionTypeLongText',
                    1,
                    null,
                    null,
                    null,
                    true
                );

                // Add number question
                cy.addQuestionToDefaultSectionWithAPI(
                    form_id,
                    'Number field',
                    'Glpi\\Form\\QuestionType\\QuestionTypeShortText',
                    2,
                    null,
                    null,
                    null,
                    true
                );

                // Add checkbox question
                cy.addQuestionToDefaultSectionWithAPI(
                    form_id,
                    'Checkbox field',
                    'Glpi\\Form\\QuestionType\\QuestionTypeCheckbox',
                    3,
                    null,
                    null,
                    {'options': {
                        1: 'Option 1',
                        2: 'Option 2',
                        3: 'Option 3'
                    }},
                    true
                );

                cy.visit(`/Form/Render/${form_id}`);

                // All questions should be visible
                cy.findByRole('textbox', { name: 'Text field' }).should('be.visible');
                cy.findByLabelText('Long text field').should('be.visible');
                cy.findByRole('textbox', { name: 'Number field' }).should('be.visible');
                cy.findByRole('checkbox', { name: 'Option 1' }).should('be.visible');
                cy.findByRole('checkbox', { name: 'Option 2' }).should('be.visible');
                cy.findByRole('checkbox', { name: 'Option 3' }).should('be.visible');

                // Try to submit without filling any fields
                cy.findByRole('button', { name: 'Submit' }).click();

                // All should show validation errors
                checkMandatoryQuestion('Text field');
                checkMandatoryLongTextQuestion('Long text field');
                checkMandatoryQuestion('Number field');
                checkMandatoryCheckboxQuestion('Checkbox field');

                // Fill all fields
                cy.findByRole('textbox', { name: 'Text field' }).type('Text answer');
                cy.findByLabelText('Long text field').awaitTinyMCE().type('Long text answer');
                cy.findByRole('textbox', { name: 'Number field' }).type('123');
                cy.findByRole('checkbox', { name: 'Option 1' }).check();

                // Should be able to submit
                cy.findByRole('button', { name: 'Submit' }).click();
                cy.findByText('Form submitted').should('be.visible');
            });
        });
    });

    describe('Layout consistency and edge cases', () => {
        it('Prevents multiple form submissions in both layouts', () => {
            // Test step-by-step layout
            cy.createWithAPI('Glpi\\Form\\Form', {
                'name': 'Step-by-step submission test',
                'render_layout': 'step_by_step'
            }).as('step_form_id');

            cy.get('@step_form_id').then((form_id) => {
                cy.addQuestionToDefaultSectionWithAPI(
                    form_id,
                    'Question',
                    'Glpi\\Form\\QuestionType\\QuestionTypeShortText',
                    0
                );

                cy.visit(`/Form/Render/${form_id}`);
                cy.findByRole('textbox', { name: 'Question' }).type('Answer');
                cy.findByRole('button', { name: 'Submit' }).click();

                // Submit button should be disabled after first click
                cy.findByRole('button', { name: 'Submit' }).should('have.class', 'pointer-events-none');
            });

            // Test single page layout
            cy.createWithAPI('Glpi\\Form\\Form', {
                'name': 'Single page submission test',
                'render_layout': 'single_page'
            }).as('single_form_id');

            cy.get('@single_form_id').then((form_id) => {
                cy.addQuestionToDefaultSectionWithAPI(
                    form_id,
                    'Question',
                    'Glpi\\Form\\QuestionType\\QuestionTypeShortText',
                    0
                );

                cy.visit(`/Form/Render/${form_id}`);
                cy.findByRole('textbox', { name: 'Question' }).type('Answer');
                cy.findByRole('button', { name: 'Submit' }).click();

                // Submit button should be disabled after first click
                cy.findByRole('button', { name: 'Submit' }).should('have.class', 'pointer-events-none');
            });
        });

        it('Preserves form data when navigating in step-by-step mode', () => {
            cy.createWithAPI('Glpi\\Form\\Form', {
                'name': 'Data preservation test',
                'render_layout': 'step_by_step'
            }).as('form_id');

            cy.get('@form_id').then((form_id) => {
                // Add questions to multiple sections
                cy.addQuestionToDefaultSectionWithAPI(
                    form_id,
                    'First question',
                    'Glpi\\Form\\QuestionType\\QuestionTypeShortText',
                    0
                );

                cy.createWithAPI('Glpi\\Form\\Section', {
                    'name': 'Second section',
                    'rank': 1,
                    'forms_forms_id': form_id,
                }).then((second_section_id) => {
                    cy.createWithAPI('Glpi\\Form\\Question', {
                        'name': 'Second question',
                        'type': 'Glpi\\Form\\QuestionType\\QuestionTypeShortText',
                        'vertical_rank': 1,
                        'forms_sections_id': second_section_id,
                        'is_mandatory': false,
                    });
                });

                cy.visit(`/Form/Render/${form_id}`);

                // Fill first question
                cy.findByRole('textbox', { name: 'First question' }).type('First answer');
                cy.findByRole('button', { name: 'Continue' }).click();

                // Fill second question
                cy.findByRole('textbox', { name: 'Second question' }).type('Second answer');

                // Navigate back
                cy.findByRole('button', { name: 'Back' }).click();

                // First question should still have its value
                cy.findByRole('textbox', { name: 'First question' }).should('have.value', 'First answer');

                // Navigate forward again
                cy.findByRole('button', { name: 'Continue' }).click();

                // Second question should still have its value
                cy.findByRole('textbox', { name: 'Second question' }).should('have.value', 'Second answer');

                // Submit form
                cy.findByRole('button', { name: 'Submit' }).click();
                cy.findByText('Form submitted').should('be.visible');
            });
        });
    });
});

// Helper function to check mandatory question validation
function checkMandatoryQuestion(name) {
    cy.findByRole('textbox', { name })
        .should('have.attr', 'aria-invalid', 'true')
        .should('have.attr', 'aria-errormessage')
    ;
    getAriaErrorMessageElement(cy.findByRole('textbox', { name }))
        .should('contain.text', 'This field is mandatory')
    ;
}

function checkMandatoryLongTextQuestion(question_name) {
    cy.findByRole('region', { name: question_name })
        .find('textarea')
        .should('have.attr', 'aria-invalid', 'true')
        .should('have.attr', 'aria-errormessage')
    ;
    getAriaErrorMessageElement(cy.findByRole('region', { name: question_name }).find('textarea'))
        .should('contain.text', 'This field is mandatory')
    ;
}

function checkMandatoryCheckboxQuestion(question_name) {
    cy.findByRole('region', { name: question_name })
        .findAllByRole('checkbox')
        .should('have.attr', 'aria-invalid', 'true')
        .should('have.attr', 'aria-errormessage')
    ;

    cy.findByRole('region', { name: question_name })
        .findAllByRole('checkbox')
        .each(($checkbox) => {
            getAriaErrorMessageElement(cy.wrap($checkbox))
                .should('contain.text', 'This field is mandatory');
        })
    ;
}

// Helper function to get error message element by aria-errormessage
function getAriaErrorMessageElement(element) {
    return element.invoke('attr', 'aria-errormessage').then((id) => cy.get(`#${id}`));
}
