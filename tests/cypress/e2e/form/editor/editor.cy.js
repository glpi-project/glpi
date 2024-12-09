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
        cy.changeProfile('Super-Admin');
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
        cy.findByRole('alert')
            .should('contain.text', 'Item successfully updated')
        ;
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

    it('can enable child entities', () => {
        cy.createFormWithAPI().visitFormTab('Form');
        cy.findByRole('checkbox', {"name": "Child entities"})
            .should('be.not.checked')
            .check()
        ;
        cy.findByRole('button', {"name": "Save"}).click();
        cy.reload();
        cy.findByRole('checkbox', {"name": "Child entities"}).should('be.checked');
    }),

    it('can create and delete a question', () => {
        cy.createFormWithAPI().visitFormTab('Form');

        describe('create question', () => {
            cy.addQuestion("My question");
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
            cy.saveFormEditorAndReload();
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
            cy.saveFormEditorAndReload();
            cy.get("@question_details").should('not.exist');
        });
    });

    it('can duplicate a question', () => {
        cy.createFormWithAPI().visitFormTab('Form');

        // Create a question
        cy.addQuestion("My question");
        cy.findAllByRole('region', {'name': 'Question details'}).as('questions');
        cy.get('@questions').eq(0).as('question');

        // Set all general questions properties
        // Type specific properties should have their own tests
        cy.get('@question')
            .findByRole('checkbox', {'name': 'Mandatory'})
            .should('not.be.checked')
            .check()
        ;
        cy.get('@question')
            .findByLabelText("Question description")
            .awaitTinyMCE()
            .type("My question description")
        ;

        // Duplicate question
        cy.get('@question')
            .findByRole('button', {'name': "Duplicate question"})
            .click()
        ;
        cy.saveFormEditorAndReload();

        // Question 1 and 2 should be identical
        cy.findAllByRole('region', {'name': 'Question details'}).as('questions');
        [0, 1].forEach((question_index) => {
            cy.get('@questions').eq(question_index).as('question');
            cy.get('@question').click(); // Set as actice to show more data

            // Validate question fieldse
            cy.get('@question')
                .findByRole('textbox', {'name': 'Question name'})
                .should('have.value', "My question")
            ;
            cy.get('@question')
                .findByRole('checkbox', {'name': 'Mandatory'})
                .should('be.checked')
            ;
            cy.get('@question')
                .findByLabelText("Question description")
                .awaitTinyMCE()
                .should('have.text', "My question description")
            ;
        });
    });

    it('can move question', () => {
        cy.createFormWithAPI().visitFormTab('Form');

        // Create a few questions
        cy.addQuestion("First question");
        cy.addQuestion("Second question");
        cy.addQuestion("Third question");

        // Move the second question
        cy.findAllByRole('region', {'name': "Question details"}).eq(0).as("first_question");
        cy.findAllByRole('region', {'name': "Question details"}).eq(1).as("second_question");
        cy.get("@first_question").startToDrag();
        cy.get("@second_question").dropDraggedItemAfter();

        // Relaod page
        cy.saveFormEditorAndReload();
        cy.findAllByRole('region', {'name': "Question details"}).eq(0).as("first_displayed_question");
        cy.findAllByRole('region', {'name': "Question details"}).eq(1).as("second_displayed_question");

        // First displayed question is "Second question"
        cy.get("@first_displayed_question")
            .findByRole('textbox', {'name': "Question name"})
            .should('have.value', "Second question")
        ;

        // Second displayed question is "First question"
        cy.get("@second_displayed_question")
            .findByRole('textbox', {'name': "Question name"})
            .should('have.value', "First question")
        ;
    });

    it('can create and delete a section', () => {
        cy.createFormWithAPI().visitFormTab('Form');

        // We must create at least one question before we can add a section
        cy.addQuestion("First question");

        // There is always one section when a form is create but it is hidden
        cy.findByRole('region', {'name': 'Section details'}).should('not.exist');

        // Create section
        cy.addSection("Second section");
        cy.findAllByRole('region', {'name': 'Section details'}).as('sections');
        cy.get('@sections').should('have.length', 2);

        // Add description to our section
        cy.get('@sections')
            .eq(1)
            .findByLabelText("Section description")
            .awaitTinyMCE()
            .type("Second section description")
        ;

        // Save and reload
        cy.saveFormEditorAndReload();

        // Validate values
        cy.findAllByRole('region', {'name': 'Section details'}).as('sections');
        cy.get('@sections').should('have.length', 2);
        cy.get('@sections').eq(1).as('second_section');
        cy.get('@second_section')
            .findByRole('textbox', {'name': 'Section name'})
            .should('have.value', "Second section")
        ;
        cy.get('@second_section')
            .findByLabelText("Section description")
            .awaitTinyMCE()
            .should('have.text', "Second section description")
        ;

        // Delete question
        cy.get('@second_section')
            .findByRole('button', {'name': "Section actions"})
            .click()
        ;
        cy.findByRole('button', {'name': "Delete section"}).click();

        // Save and reload
        cy.saveFormEditorAndReload();
        cy.findByRole('region', {'name': 'Section details'}).should('not.exist');
    });

    it('can duplicate a section', () => {
        cy.createFormWithAPI().visitFormTab('Form');

        // We must create at least one question before we can add a section
        cy.addQuestion("First question");

        // Create section
        cy.addSection("Second section");
        cy.findAllByRole('region', {'name': 'Section details'}).as('sections');
        cy.get('@sections').should('have.length', 2);

        // Add two questions in the section
        cy.addQuestion("Second question");
        cy.addQuestion("Third question");

        // Duplicate second section
        cy.get('@sections').eq(1).as('second_section');
        cy.get('@second_section')
            .findByRole('button', {'name': "Section actions"})
            .click()
        ;
        cy.findByRole('button', {'name': "Duplicate section"}).click();
        cy.saveFormEditorAndReload();

        // There should now be 3 sections
        cy.findAllByRole('region', {'name': 'Form section'}).as('sections_containers');
        cy.findAllByRole('region', {'name': 'Section details'}).as('sections_details');
        cy.get('@sections_details').should('have.length', 3);

        // Section 2 and 3 should be identical
        [1, 2].forEach((section_index) => {
            cy.get('@sections_containers').eq(section_index).as('section_container');
            cy.get('@sections_details').eq(section_index).as('section_detail');

            // Validate section name
            cy.get('@section_detail')
                .findByRole('textbox', {'name': 'Section name'})
                .should('have.value', "Second section")
            ;

            // Validate questions
            cy.get('@section_container')
                .findAllByRole('region', {'name': 'Question details'})
                .as('questions')
            ;
            cy.get('@questions')
                .eq(0)
                .findByRole('textbox', {'name': 'Question name'})
                .should('have.value', "Second question")
            ;
            cy.get('@questions')
                .eq(1)
                .findByRole('textbox', {'name': 'Question name'})
                .should('have.value', "Third question")
            ;
        });
    });

    it('can merge sections', () => {
        cy.createFormWithAPI().visitFormTab('Form');

        // We must create at least one question before we can add a section
        cy.addQuestion("First question");

        // Create section
        cy.addSection("Second section");
        cy.findAllByRole('region', {'name': 'Section details'}).as('sections');
        cy.get('@sections').should('have.length', 2);

        // Add two questions in the new section
        cy.addQuestion("Second question");
        cy.addQuestion("Third question");

        // Merge the two sections
        cy.get('@sections')
            .eq(1)
            .findByRole('button', {'name': "Section actions"})
            .click()
        ;
        cy.findByRole('button', {'name': "Merge with previous section"}).click();
        cy.saveFormEditorAndReload();

        // There should be only one hidden section
        cy.findByRole('region', {'name': 'Form section'}).as('section');
        cy.findByRole('region', {'name': 'Section details'}).should('not.exist'); // Only one hidden section

        // There should be 3 questions
        cy.get('@section')
            .findAllByRole('region', {'name': 'Question details'})
            .as("questions")
        ;
        cy.get("@questions").should('have.length', 3);
        cy.get("@questions")
            .eq(0)
            .findByRole('textbox', {'name': 'Question name'})
            .should('have.value', "First question")
        ;
        cy.get("@questions")
            .eq(1)
            .findByRole('textbox', {'name': 'Question name'})
            .should('have.value', "Second question")
        ;
        cy.get("@questions")
            .eq(2)
            .findByRole('textbox', {'name': 'Question name'})
            .should('have.value', "Third question")
        ;
    });

    it('can insert a section at the start of another section', () => {
        cy.createFormWithAPI().visitFormTab('Form');

        // We must create at least one question before we can add a section
        cy.addQuestion("First question");

        // Create a second section
        cy.addSection("Second section");
        cy.findAllByRole('region', {'name': 'Form section'}).as('sections');
        cy.get('@sections').should('have.length', 2);

        // Add two questions in the new section
        cy.addQuestion("Second question");
        cy.addQuestion("Third question");

        // Move focus to the second section details
        cy.get('@sections')
            .eq(1)
            .findByRole('region', {'name': 'Section details'})
            .click();

        // Create a third section
        cy.addSection("Third section");
        cy.get('@sections').should('have.length', 3);

        // Save and reload before checking the values
        cy.saveFormEditorAndReload();
        cy.findAllByRole('region', {'name': 'Form section'}).as('sections');

        // The third section should "steal" the questions of the second section,
        // which should now be empty.
        cy.get('@sections')
            .eq(0)
            .findAllByRole('region', {'name': 'Question details'})
            .should('have.length', 1) // First section is unchanged
        ;
        cy.get('@sections')
            .eq(1)
            .findAllByRole('region', {'name': 'Question details'})
            .should('have.length', 0) // Second section is empty
        ;
        cy.get('@sections')
            .eq(2)
            .findAllByRole('region', {'name': 'Question details'})
            .should('have.length', 2)
        ;
    });

    it('can insert a section in the middle of another section', () => {
        cy.createFormWithAPI().visitFormTab('Form');

        // We must create at least one question before we can add a section
        cy.addQuestion("First question");

        // Create a second section
        cy.addSection("Second section");
        cy.findAllByRole('region', {'name': 'Form section'}).as('sections');
        cy.get('@sections').should('have.length', 2);

        // Add two questions in the new section
        cy.addQuestion("Second question");
        cy.addQuestion("Third question");

        // Move focus to the second question
        cy.findAllByRole('region', {'name': 'Question details'})
            .eq(1)
            .click()
        ;

        // Create a third section
        cy.addSection("Third section");
        cy.get('@sections').should('have.length', 3);

        // Save and reload before checking the values
        cy.saveFormEditorAndReload();
        cy.findAllByRole('region', {'name': 'Form section'}).as('sections');

        // The third section should "steal" the third questions of the second section
        cy.get('@sections')
            .eq(0)
            .findAllByRole('region', {'name': 'Question details'})
            .should('have.length', 1) // First section is unchanged
        ;
        cy.get('@sections')
            .eq(1)
            .findAllByRole('region', {'name': 'Question details'})
            .should('have.length', 1) // Contains the second question
        ;
        cy.get('@sections')
            .eq(2)
            .findAllByRole('region', {'name': 'Question details'})
            .should('have.length', 1) // Contains the third question
        ;
    });

    // The "can insert a section in the end of another section" scenario is already
    // covered by the "can create and delete a section" test.

    it('can collapse sections', () => {
        cy.createFormWithAPI().visitFormTab('Form');

        // We must create at least one question before we can add a section
        cy.addQuestion("First question");
        cy.findAllByRole('region', {'name': 'Question details'}).eq(0).as('question');

        // Create a second section
        cy.addSection("Second section");
        cy.findAllByRole('region', {'name': 'Form section'}).as('sections');
        cy.get('@sections').should('have.length', 2);

        // The first question should be visible
        cy.findByRole('region', {'name': 'Question details'}).should('exist');

        // Collaspse the first section
        cy.get('@sections')
            .eq(0)
            .findByRole('button', {'name': "Collapse section"})
            .click()
        ;
        cy.findByRole('region', {'name': 'Question details'}).should('not.exist');

        // Uncollapse
        cy.get('@sections')
            .eq(0)
            .findByRole('button', {'name': "Collapse section"})
            .click()
        ;
        cy.findByRole('region', {'name': 'Question details'}).should('exist');
    });

    it('can reorder sections', () => {
        cy.createFormWithAPI().visitFormTab('Form');

        // We must create at least one question before we can add a section
        cy.addQuestion("First question");

        // Create a second section
        cy.addSection("Second section");
        cy.findAllByRole('region', {'name': 'Form section'}).as('sections');
        cy.get('@sections').should('have.length', 2);

        // Add two questions to our section
        cy.addQuestion("Second question");
        cy.addQuestion("Third question");

        // Open "reorder sections" modal
        cy.get('@sections')
            .eq(0)
            .findByRole('button', {'name': "Section actions"})
            .click()
        ;
        cy.findByRole('button', {'name': "Move section"}).click();
        cy.findByRole('dialog').as("modal");

        // Move the second section at the end
        cy.get("@modal").findByText("First section").closest("section").startToDrag();
        cy.get("@modal").findByText("Second section").closest("section").dropDraggedItemAfter();

        // eslint-disable-next-line
        cy.wait(300); // bootstrap modal events needs to be ready, we have no control on that
        cy.get("@modal").findByRole('button', {'name': "Save"}).click();

        // Save and reload
        cy.saveFormEditorAndReload();
        cy.findAllByRole('region', {'name': 'Form section'}).as('sections');

        // The "Second section" is now displayed first
        cy.get('@sections')
            .eq(0)
            .as("first_displayed_section")
        ;
        cy.get('@first_displayed_section')
            .findByRole('textbox', {'name': 'Section name'})
            .should('have.value', "Second section")
        ;
        cy.get('@first_displayed_section')
            .findAllByRole('region', {'name': 'Question details'})
            .should('have.length', 2) // Second + Third question
        ;

        // The "First section" is now displayed last
        cy.get('@sections')
            .eq(1)
            .as("second_displayed_section")
        ;
        cy.get('@second_displayed_section')
            .findByRole('textbox', {'name': 'Section name'})
            .should('have.value', "First section")
        ;
        cy.get('@second_displayed_section')
            .findAllByRole('region', {'name': 'Question details'})
            .should('have.length', 1) // First question
        ;
    });

    it('can duplicate a question and change its type', () => {
        cy.createFormWithAPI().visitFormTab('Form');

        // Create a question
        cy.addQuestion("My question");

        // Set all general questions properties
        // Type specific properties should have their own tests
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

        // Change the question type to not use the default type
        cy.getDropdownByLabelText("Question type")
            .selectDropdownValue('Long answer')
        ;

        // Duplicate question
        cy.findByRole('button', {'name': "Duplicate question"}).click();

        // Validate values
        cy.findAllByRole('region', {'name': 'Question details'}).each((region) => {
            cy.wrap(region).within(() => {
                // Focus region to display hiden actions
                cy.findByRole('textbox', {'name': 'Question name'}).click();

                cy.findByRole('textbox', {'name': 'Question name'})
                    .should('have.value', "My question")
                ;
                cy.findByRole('checkbox', {'name': 'Mandatory'})
                    .should('be.checked')
                ;
                cy.findByLabelText("Question description")
                    .awaitTinyMCE()
                    .should('have.text', "My question description")
                ;
                cy.getDropdownByLabelText("Question type").should('have.text', 'Long answer');
            });
        });

        // Change question type
        cy.getDropdownByLabelText("Question type").selectDropdownValue('Date and time');

        // Save and reload
        cy.saveFormEditorAndReload();

        // Validate values
        cy.findAllByRole('region', {'name': 'Question details'}).each((region, index) => {
            cy.wrap(region).within(() => {
                // Focus region to display hiden actions
                cy.findByRole('textbox', {'name': 'Question name'}).click();

                cy.findByRole('textbox', {'name': 'Question name'})
                    .should('have.value', "My question")
                ;
                cy.findByRole('checkbox', {'name': 'Mandatory'})
                    .should('be.checked')
                ;
                cy.findByLabelText("Question description")
                    .awaitTinyMCE()
                    .should('have.text', "My question description")
                ;

                if (index === 0) {
                    cy.getDropdownByLabelText("Question type").should('have.text', 'Long answer');
                } else if (index === 1) {
                    cy.getDropdownByLabelText("Question type").should('have.text', 'Date and time');
                }
            });
        });
    });

    function verifySection(sectionIndex, sectionName, questions) {
        cy.findAllByRole('region', {'name': 'Form section'}).eq(sectionIndex).within(() => {
            cy.findByRole('textbox', {'name': 'Section name'}).should('have.value', sectionName);
            questions.forEach((question, questionIndex) => {
                cy.findAllByRole('region', {'name': 'Question details'}).eq(questionIndex).within(() => {
                    // Focus region to display hidden actions
                    cy.findByRole('textbox', {'name': 'Question name'}).click();

                    cy.findByRole('textbox', {'name': 'Question name'}).should('have.value', question.name);
                    cy.findByRole('checkbox', {'name': 'Mandatory'}).should('be.checked');
                    cy.findByLabelText("Question description").awaitTinyMCE().should('have.text', question.description);
                    cy.getDropdownByLabelText("Question type").should('have.text', question.type);
                });
            });
        });
    }

    it('can duplicate a section with questions and change their types', () => {
        cy.createFormWithAPI().visitFormTab('Form');

        // Create questions
        cy.addQuestion("First question");
        cy.findAllByRole('region', {'name': 'Question details'}).eq(0).within(() => {
            cy.findByRole('checkbox', {'name': 'Mandatory'}).check();
            cy.findByLabelText("Question description")
                .awaitTinyMCE()
                .type("First question description");
        });
        cy.getDropdownByLabelText("Question type").selectDropdownValue('Long answer');

        cy.addQuestion("Second question");
        cy.findAllByRole('region', {'name': 'Question details'}).eq(1).within(() => {
            cy.findByRole('checkbox', {'name': 'Mandatory'}).check();
            cy.findByLabelText("Question description")
                .awaitTinyMCE()
                .type("Second question description");
        });
        cy.getDropdownByLabelText("Question type").selectDropdownValue('Date and time');

        // Create sections
        cy.findByRole('button', {'name': 'Add a new section'}).click();

        // Add a question to the new section
        cy.addQuestion("Third question");
        cy.findAllByRole('region', {'name': 'Question details'}).eq(2).within(() => {
            cy.findByRole('checkbox', {'name': 'Mandatory'}).check();
            cy.findByLabelText("Question description")
                .awaitTinyMCE()
                .type("Third question description");
        });
        cy.getDropdownByLabelText("Question type").selectDropdownValue('Actors');

        // Duplicate first section
        cy.findAllByRole('region', {'name': 'Form section'}).eq(0).within(() => {
            cy.findByRole('button', {'name': 'Section actions'}).click();
            cy.findByRole('button', {'name': 'Duplicate section'}).click();
        });

        // Validate values
        verifySection(0, 'First section', [
            { name: 'First question', description: 'First question description', type: 'Long answer' },
            { name: 'Second question', description: 'Second question description', type: 'Date and time' }
        ]);

        verifySection(1, 'First section', [
            { name: 'First question', description: 'First question description', type: 'Long answer' },
            { name: 'Second question', description: 'Second question description', type: 'Date and time' }
        ]);

        verifySection(2, '', [
            { name: 'Third question', description: 'Third question description', type: 'Actors' }
        ]);

        // Change question type of the first question of the first section
        cy.findAllByRole('region', {'name': 'Form section'}).eq(0).within(() => {
            cy.findAllByRole('region', {'name': 'Question details'}).eq(0).within(() => {
                // Focus region to display hidden actions
                cy.findByRole('textbox', {'name': 'Question name'}).click();
            });
        });
        cy.getDropdownByLabelText("Question type").selectDropdownValue('Date and time');

        // Change question type of the first question of the second section
        cy.findAllByRole('region', {'name': 'Form section'}).eq(1).within(() => {
            cy.findAllByRole('region', {'name': 'Question details'}).eq(0).within(() => {
                // Focus region to display hidden actions
                cy.findByRole('textbox', {'name': 'Question name'}).click();
            });
        });
        cy.getDropdownByLabelText("Question type").selectDropdownValue('Actors');

        // Change question type of the first question of the third section
        cy.findAllByRole('region', {'name': 'Form section'}).eq(2).within(() => {
            cy.findAllByRole('region', {'name': 'Question details'}).eq(0).within(() => {
                // Focus region to display hidden actions
                cy.findByRole('textbox', {'name': 'Question name'}).click();
            });
        });
        cy.getDropdownByLabelText("Question type").selectDropdownValue('Long answer');

        // Save and reload
        cy.saveFormEditorAndReload();

        // Validate values
        verifySection(0, 'First section', [
            { name: 'First question', description: 'First question description', type: 'Date and time' },
            { name: 'Second question', description: 'Second question description', type: 'Date and time' }
        ]);

        verifySection(1, 'First section', [
            { name: 'First question', description: 'First question description', type: 'Actors' },
            { name: 'Second question', description: 'Second question description', type: 'Date and time' }
        ]);

        verifySection(2, '', [
            { name: 'Third question', description: 'Third question description', type: 'Long answer' }
        ]);
    });
});
