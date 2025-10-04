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

let questions = null;
let comments = null;
let sections = null;

function createForm() {
    cy.login();
    cy.createFormWithAPI().as('form_id').visitFormTab('Form');
    cy.then(() => {
        questions = [];
        comments = [];
        sections = ["First section"];
    });
}

function goToDestinationTab()
{
    cy.get('@form_id').visitFormTab('Destinations');
}

function addQuestion(name) {
    cy.findByRole('button', {'name': "Add a question"}).click();
    cy.focused().type(name);
    cy.then(() => {
        questions.push(name);
    });
}

function setQuestionTypeCategory(category) {
    cy.getDropdownByLabelText('Question type').selectDropdownValue(category);
}

function addComment(name) {
    cy.findByRole('button', {'name': "Add a comment"}).click();
    cy.focused().type(name);
    cy.then(() => {
        comments.push(name);
    });
}

function addSection(name) {
    cy.findByRole('button', {'name': "Add a section"}).click();
    cy.focused().type(name);
    cy.then(() => {
        sections.push(name);
    });
}

function getSubmitButtonContainer() {
    return cy.get('#glpi-form-properties-accordion [data-glpi-form-editor-visibility-dropdown-container]');
}

function getAndFocusQuestion(name) {
    return cy.then(() => {
        const index = questions.indexOf(name);
        cy.findAllByRole('region', {'name': 'Question details', 'timeout': 10000}).eq(index).as('question_to_focus');
        cy.get('@question_to_focus').click('top');
        return cy.get('@question_to_focus');
    });
}

function getAndFocusComment(name) {
    return cy.then(() => {
        const index = comments.indexOf(name);
        cy.findAllByRole('region', {'name': 'Comment details'}).eq(index).click();
    });
}

function getAndFocusSection(name) {
    return cy.then(() => {
        const index = sections.indexOf(name);
        cy.findAllByRole('region', {'name': 'Section details'}).eq(index).click();
    });
}

function save() {
    cy.findByRole('button', {'name': "Save"}).click();
    cy.findByRole('alert')
        .should('contain.text', 'Item successfully updated')
    ;
}

function saveAndReload() {
    save();
    cy.reload();
}

function validateThatFormSubmitButtonIsVisible() {
    cy.findByRole('button', {'name': "Submit"}).should('be.visible');
}

function validateThatFormSubmitButtonIsNotVisible() {
    cy.findByRole('button', {'name': "Submit"}).should('not.exist');
}

function validateThatQuestionIsVisible(name) {
    cy.findByRole('heading', {'name': name}).should('be.visible');
}

function validateThatQuestionIsNotVisible(name) {
    cy.findByRole('heading', {'name': name}).should('not.exist');
}

function validateThatCommentIsVisible(name) {
    cy.findByRole('heading', {'name': name}).should('be.visible');
}

function validateThatCommentIsNotVisible(name) {
    cy.findByRole('heading', {'name': name}).should('not.exist');
}

function preview() {
    cy.findByRole('link', {'name': "Preview"})
        .invoke('attr', 'target', '_self')
        .click()
    ;
    cy.url().should('include', '/Form/Render');
}

function checkThatVisibilityOptionsAreHidden() {
    cy.findByRole('label', {'name': "Always visible"}).should('not.exist');
    cy.findByRole('label', {'name': "Visible if..."}).should('not.exist');
    cy.findByRole('label', {'name': "Hidden if..."}).should('not.exist');
}

function initVisibilityConfiguration() {
    cy.findByRole('button', {'name': 'More actions'}).click();
    cy.findByRole('button', {'name': 'Configure visibility'}).click();
}

function closeVisibilityConfiguration() {
    cy.get('[data-glpi-form-editor-visibility-dropdown]:visible').click();
}

function openConditionEditor() {
    cy.findByTitle(/Configure (visibility|creation conditions)/).click();
    cy.waitForNetworkIdle(150);
}

function closeConditionEditor() {
    cy.findByTitle(/Configure (visibility|creation conditions)/).click();
}

function checkThatSelectedVisibilityOptionIs(option) {
    cy.findByRole('radio', {'name': option}).should('be.checked');
    cy.findByRole('button', {'name': option}).should('exist');
}

function setConditionStrategy(option) {
    // Label is the next node
    cy.findByRole('radio', {'name': option}).next().click();
}

function checkThatVisibilityOptionsAreVisible() {
    cy.findByRole('radio', {'name': "Always visible"}).should('be.visible');
    cy.findByRole('radio', {'name': "Visible if..."}).should('be.visible');
    cy.findByRole('radio', {'name': "Hidden if..."}).should('be.visible');
}

function checkThatConditionEditorIsDisplayed() {
    cy.getDropdownByLabelText('Item').should('exist');
}

function checkThatConditionEditorIsNotDisplayed() {
    cy.getDropdownByLabelText('Item').should('not.exist');
}

function addNewEmptyCondition() {
    cy.findByRole('button', {'name': 'Add another criteria'}).click();
}

function deleteCondition(index) {
    cy.get("[data-glpi-conditions-editor-condition]").eq(index).as('condition');
    cy.get('@condition').findByRole('button', {'name': 'Delete criteria'}).click();
}

function fillCondition(index, logic_operator, question_name, value_operator_name, value, value_type = "string") {
    cy.get("[data-glpi-conditions-editor-condition]").eq(index).as('condition');

    // Scroll the condition into view before interacting with it
    cy.get('@condition').scrollIntoView();
    cy.get('@condition').should('be.visible');

    if (logic_operator !== null && index > 0) {
        cy.get('@condition')
            .getDropdownByLabelText('Logic operator')
            .selectDropdownValue(logic_operator)
        ;
    }
    cy.get('@condition').getDropdownByLabelText('Item').selectDropdownValue(question_name);
    cy.get('@condition').getDropdownByLabelText('Value operator')
        .selectDropdownValue(value_operator_name)
    ;

    cy.waitForNetworkIdle(150);

    if (value_type === "string"){
        cy.get('@condition').findByRole('textbox', {'name': 'Value'}).type(value);
    } else if (value_type === "number") {
        cy.get('@condition').findByRole('spinbutton', {'name': 'Value'}).type(value);
    } else if (value_type === "date") {
        cy.get('@condition').findByLabelText('Value').type(value);
    } else if (value_type === "dropdown") {
        cy.get('@condition').getDropdownByLabelText('Value').selectDropdownValue(value);
    } else if (value_type === "dropdown_multiple") {
        for (const option of value) {
            cy.get('@condition').getDropdownByLabelText('Value').selectDropdownValue(option);
        }
    }
}

function checkThatConditionExist(index, logic_operator, question_name, value_operator_name, value, value_type = "string") {
    cy.get("[data-glpi-conditions-editor-condition]").eq(index).as('condition');

    // Scroll the condition into view before interacting with it
    cy.get('@condition').scrollIntoView();
    cy.get('@condition').should('be.visible');

    if (logic_operator !== null && index > 0) {
        cy.get('@condition')
            .getDropdownByLabelText('Logic operator')
            .should('have.text', logic_operator)
        ;
    }
    cy.get('@condition').getDropdownByLabelText('Item').should('have.text', question_name);
    cy.get('@condition').getDropdownByLabelText('Value operator').should(
        'have.text',
        value_operator_name
    );

    if (value_type === "string"){
        cy.get('@condition').findByRole('textbox', {'name': 'Value'}).should('have.value', value);
    } else if (value_type === "number") {
        cy.get('@condition').findByRole('spinbutton', {'name': 'Value'}).should('have.value', value);
    } else if (value_type === "date") {
        cy.get('@condition').findByLabelText('Value').should('have.value', value);
    } else if (value_type === "dropdown") {
        cy.get('@condition').getDropdownByLabelText('Value').should('have.text', value);
    } else if (value_type === "dropdown_multiple") {
        cy.get('@condition').getDropdownByLabelText('Value').should(
            'have.text',
            `×${value.join('×')}`
        );
    }
}

function checkThatConditionDoNotExist(index) {
    cy.get("[data-glpi-conditions-editor-condition]").eq(index).should('not.exist');
}

function setTextAnswer(question, value) {
    cy.findByRole('textbox', {'name': question}).clear();
    cy.findByRole('textbox', {'name': question}).type(value);
}

/**
 * Must be called only when positioned at the start of a form.
 */
function validateSectionOrder(sections) {
    let back = 0;

    // Validate each sections one by one
    sections.forEach((section, i) => {
        cy.findByRole('heading', {'name': section}).should('be.visible');

        if (i + 1 === sections.length) {
            // Last section, do not submit form
            cy.findByRole('button', {'name': "Submit"}).should('be.visible');
        } else {
            // Any other section, go to next.
            cy.findByRole('button', {'name': "Continue"}).click();
            back++;
        }
    });

    // Go back to first section
    for (let i=0; i<back; i++) {
        cy.findByRole('button', {'name': "Back"}).click();
    }
}

function saveDestination() {
    cy.findByRole('button', {name: "Update item"}).click();
    cy.findByRole('alert').should('contains.text', 'Item successfully updated');
    cy.findByRole('button', {'name': 'Close'}).click();
}

describe ('Conditions', () => {
    beforeEach(() => {
        cy.login();
    });

    it('can set the conditional visibility of a form submit button', () => {
        createForm();
        addQuestion('My first question');
        saveAndReload();

        getSubmitButtonContainer().within(() => {
            openConditionEditor();
            checkThatConditionEditorIsNotDisplayed();
            setConditionStrategy('Visible if...');
            checkThatConditionEditorIsDisplayed();
            fillCondition(0, null, 'My first question', 'Is equal to', 'I love GLPI');
        });

        saveAndReload();

        getSubmitButtonContainer().within(() => {
            openConditionEditor();
            checkThatConditionEditorIsDisplayed();
            checkThatConditionExist(
                0,
                null,
                'Questions - My first question',
                'Is equal to',
                'I love GLPI'
            );
            closeConditionEditor();
        });
    });

    it('displays the correct dynamic label for submit button visibility strategy', () => {
        createForm();
        addQuestion('My first question');
        saveAndReload();

        // Test that the initial label is "Always visible"
        getSubmitButtonContainer().within(() => {
            openConditionEditor();
            checkThatSelectedVisibilityOptionIs('Always visible');

            // Change to "Visible if..." and verify the label updates
            setConditionStrategy('Visible if...');
            checkThatSelectedVisibilityOptionIs('Visible if...');
            fillCondition(0, null, 'My first question', 'Is equal to', 'I love GLPI');

            // Change to "Hidden if..." and verify the label updates
            setConditionStrategy('Hidden if...');
            checkThatSelectedVisibilityOptionIs('Hidden if...');

            // Change back to "Always visible" and verify the label updates
            setConditionStrategy('Always visible');
            checkThatSelectedVisibilityOptionIs('Always visible');

            closeConditionEditor();
        });

        // Save and reload to test persistence
        saveAndReload();

        getSubmitButtonContainer().within(() => {
            openConditionEditor();
            checkThatSelectedVisibilityOptionIs('Always visible');
            closeConditionEditor();
        });
    });

    it('can set the conditional visibility of a question', () => {
        createForm();
        addQuestion('My first question');
        saveAndReload();

        // Select 'Visible if...' (editor should be displayed)
        getAndFocusQuestion('My first question').within(() => {
            checkThatVisibilityOptionsAreHidden();
            initVisibilityConfiguration();
            checkThatVisibilityOptionsAreVisible();
            checkThatSelectedVisibilityOptionIs('Always visible');
            checkThatConditionEditorIsNotDisplayed();
            setConditionStrategy('Visible if...');
            checkThatSelectedVisibilityOptionIs('Visible if...');
            checkThatConditionEditorIsDisplayed();
        });
        saveAndReload();
        getAndFocusQuestion('My first question').within(() => {
            openConditionEditor();
            checkThatSelectedVisibilityOptionIs('Visible if...');
            checkThatConditionEditorIsDisplayed();
            closeConditionEditor();
        });

        // Select 'Hidden if...' (editor should be displayed)
        getAndFocusQuestion('My first question').within(() => {
            checkThatVisibilityOptionsAreHidden();
            openConditionEditor();
            checkThatVisibilityOptionsAreVisible();
            checkThatSelectedVisibilityOptionIs('Visible if...');
            checkThatConditionEditorIsDisplayed();
            setConditionStrategy('Hidden if...');
            checkThatSelectedVisibilityOptionIs('Hidden if...');
            checkThatConditionEditorIsDisplayed();
        });
        saveAndReload();
        getAndFocusQuestion('My first question').within(() => {
            openConditionEditor();
            checkThatSelectedVisibilityOptionIs('Hidden if...');
            checkThatConditionEditorIsDisplayed();
            closeConditionEditor();
        });

        // Select 'Always visible' (editor should be hidden)
        getAndFocusQuestion('My first question').within(() => {
            checkThatVisibilityOptionsAreHidden();
            openConditionEditor();
            checkThatVisibilityOptionsAreVisible();
            checkThatSelectedVisibilityOptionIs('Hidden if...');
            checkThatConditionEditorIsDisplayed();
            setConditionStrategy('Always visible');
            checkThatSelectedVisibilityOptionIs('Always visible');
            checkThatConditionEditorIsNotDisplayed();
        });
        saveAndReload();
        getAndFocusQuestion('My first question').within(() => {
            initVisibilityConfiguration();
            checkThatSelectedVisibilityOptionIs('Always visible');
            checkThatConditionEditorIsNotDisplayed();
            closeConditionEditor();
        });
    });

    it('can use the editor to add or delete conditions on a question', () => {
        createForm();
        addQuestion('My first question');
        addQuestion('My second question');
        addQuestion('My third question');
        saveAndReload();

        getAndFocusQuestion('My third question').within(() => {
            initVisibilityConfiguration();
            setConditionStrategy('Visible if...');
            fillCondition(0, null, 'My second question', 'Is not equal to', 'I love GLPI');
            addNewEmptyCondition();
            fillCondition(1, 'Or', 'My first question', 'Contains', 'GLPI is great');
        });
        saveAndReload();
        getAndFocusQuestion('My third question').within(() => {
            openConditionEditor();
            checkThatConditionExist(
                0,
                null,
                'Questions - My second question',
                'Is not equal to',
                'I love GLPI',
            );
            checkThatConditionExist(
                1,
                'Or',
                'Questions - My first question',
                'Contains',
                'GLPI is great',
            );
            deleteCondition(0);
            checkThatConditionExist(
                0,
                null,
                'Questions - My first question',
                'Contains',
                'GLPI is great',
            );
            checkThatConditionDoNotExist(1);
        });
        saveAndReload();
        getAndFocusQuestion('My third question').within(() => {
            openConditionEditor();
            checkThatConditionExist(
                0,
                null,
                'Questions - My first question',
                'Contains',
                'GLPI is great',
            );
            checkThatConditionDoNotExist(1);
        });
    });

    it('can use the editor to add or delete conditions (unsaved form)', () => {
        // Repeat the same process as the previous test but skip the saveAndReload
        // step to see how GLPI's handle conditions on unsaved questions.
        createForm();
        addQuestion('My first question');
        addQuestion('My second question');
        addQuestion('My third question');

        getAndFocusQuestion('My third question').within(() => {
            initVisibilityConfiguration();
            setConditionStrategy('Visible if...');
            fillCondition(0, null, 'My second question', 'Is not equal to', 'I love GLPI');
            addNewEmptyCondition();
            fillCondition(1, 'Or', 'My first question', 'Contains', 'GLPI is great');
        });
        saveAndReload();
        getAndFocusQuestion('My third question').within(() => {
            openConditionEditor();
            checkThatConditionExist(
                0,
                null,
                'Questions - My second question',
                'Is not equal to',
                'I love GLPI'
            );
            checkThatConditionExist(
                1,
                'Or',
                'Questions - My first question',
                'Contains',
                'GLPI is great',
            );
            deleteCondition(0);
            checkThatConditionExist(
                0,
                null,
                'Questions - My first question',
                'Contains',
                'GLPI is great',
            );
            checkThatConditionDoNotExist(1);
        });
        saveAndReload();
        getAndFocusQuestion('My third question').within(() => {
            openConditionEditor();
            checkThatConditionExist(
                0,
                null,
                'Questions - My first question',
                'Contains',
                'GLPI is great',
            );
            checkThatConditionDoNotExist(1);
        });
    });

    it('can use the editor to add or delete conditions on a comment', () => {
        createForm();
        addQuestion('My first question');
        addQuestion('My second question');
        addComment('My first comment');
        saveAndReload();

        getAndFocusComment('My first comment').within(() => {
            initVisibilityConfiguration();
            setConditionStrategy('Visible if...');
            fillCondition(0, null, 'My second question', 'Contains', 'I love GLPI');
            addNewEmptyCondition();
            fillCondition(1, 'Or', 'My first question', 'Contains', 'GLPI is great');
        });
        saveAndReload();
        getAndFocusComment('My first comment').within(() => {
            openConditionEditor();
            checkThatConditionExist(
                0,
                null,
                'Questions - My second question',
                'Contains',
                'I love GLPI'
            );
            checkThatConditionExist(
                1,
                'Or',
                'Questions - My first question',
                'Contains',
                'GLPI is great',
            );
            deleteCondition(0);
            checkThatConditionExist(
                0,
                null,
                'Questions - My first question',
                'Contains',
                'GLPI is great',
            );
            checkThatConditionDoNotExist(1);
        });
        saveAndReload();
        getAndFocusComment('My first comment').within(() => {
            openConditionEditor();
            checkThatConditionExist(
                0,
                null,
                'Questions - My first question',
                'Contains',
                'GLPI is great',
            );
            checkThatConditionDoNotExist(1);
        });
    });

    it('can use the editor to add or delete conditions on a section', () => {
        createForm();
        addQuestion('My first question');
        addQuestion('My second question');
        addSection('My second section');
        addQuestion('Just a question to avoid empty sections');
        saveAndReload();

        getAndFocusSection('My second section').within(() => {
            initVisibilityConfiguration();
            setConditionStrategy('Visible if...');
            fillCondition(0, null, 'My second question', 'Do not contains', 'I love GLPI');
            addNewEmptyCondition();
            fillCondition(1, 'Or', 'My first question', 'Contains', 'GLPI is great');
        });
        saveAndReload();
        getAndFocusSection('My second section').within(() => {
            openConditionEditor();
            checkThatConditionExist(
                0,
                null,
                'Questions - My second question',
                'Do not contains',
                'I love GLPI'
            );
            checkThatConditionExist(
                1,
                'Or',
                'Questions - My first question',
                'Contains',
                'GLPI is great',
            );
            deleteCondition(0);
            checkThatConditionExist(
                0,
                null,
                'Questions - My first question',
                'Contains',
                'GLPI is great',
            );
            checkThatConditionDoNotExist(1);
        });
        saveAndReload();
        getAndFocusSection('My second section').within(() => {
            openConditionEditor();
            checkThatConditionExist(
                0,
                null,
                'Questions - My first question',
                'Contains',
                'GLPI is great',
            );
            checkThatConditionDoNotExist(1);
        });
    });

    it('can use the editor to add or delete conditions on a destination', () => {
        // Create the test form
        createForm();
        addQuestion('My first question');
        addQuestion('My second question');
        saveAndReload();

        // Add a few conditions to the default destination
        goToDestinationTab();
        openConditionEditor();
        setConditionStrategy('Created if...');
        fillCondition(0, null, 'My second question', 'Is not equal to', 'I love GLPI');
        addNewEmptyCondition();
        fillCondition(1, 'Or', 'My first question', 'Contains', 'GLPI is great');
        saveDestination();
        openConditionEditor();

        // Check that the conditions are correctly displayed
        checkThatConditionExist(
            0,
            null,
            'Questions - My second question',
            'Is not equal to',
            'I love GLPI',
        );
        checkThatConditionExist(
            1,
            'Or',
            'Questions - My first question',
            'Contains',
            'GLPI is great',
        );

        // Delete the first condition and check that the second one is still there
        deleteCondition(0);
        checkThatConditionExist(
            0,
            null,
            'Questions - My first question',
            'Contains',
            'GLPI is great',
        );
        checkThatConditionDoNotExist(1);

        // Reload and make sure only one condition remains
        saveDestination();
        openConditionEditor();
        checkThatConditionExist(
            0,
            null,
            'Questions - My first question',
            'Contains',
            'GLPI is great',
        );
        checkThatConditionDoNotExist(1);
    });

    it('conditions are applied on the submit button', () => {
        createForm();
        addQuestion('My question used as a criteria');

        getSubmitButtonContainer().within(() => {
            openConditionEditor();
            setConditionStrategy('Visible if...');
            fillCondition(
                0,
                null,
                'My question used as a criteria',
                'Is equal to',
                'I love GLPI'
            );
        });
        save();
        preview();

        // The form questions are all empty, we expect the following default state
        validateThatFormSubmitButtonIsNotVisible();

        // Set first answer to "I love GLPI" and check the displayed content again.
        setTextAnswer("My question used as a criteria", "I love GLPI");
        validateThatFormSubmitButtonIsVisible();

        // Set first answer to "I love GLPI 2" and check the displayed content again.
        setTextAnswer("My question used as a criteria", "I love GLPI 2");
        validateThatFormSubmitButtonIsNotVisible();
    });

    it('conditions are applied on questions', () => {
        createForm();
        addQuestion('My question used as a criteria');
        addQuestion('My question that is always visible');
        addQuestion('My question that is visible if some criteria are met');
        addQuestion('My question that is hidden if some criteria are met');

        getAndFocusQuestion('My question that is always visible').within(() => {
            initVisibilityConfiguration();
            setConditionStrategy('Always visible');
            closeConditionEditor();
        });
        getAndFocusQuestion('My question that is visible if some criteria are met').within(() => {
            initVisibilityConfiguration();
            setConditionStrategy('Visible if...');
            fillCondition(
                0,
                null,
                'My question used as a criteria',
                'Is equal to',
                'Expected answer 1'
            );
            closeConditionEditor();
        });
        getAndFocusQuestion('My question that is hidden if some criteria are met').within(() => {
            initVisibilityConfiguration();
            setConditionStrategy('Hidden if...');
            fillCondition(
                0,
                null,
                'My question used as a criteria',
                'Is equal to',
                'Expected answer 2'
            );
            closeConditionEditor();
        });
        save();
        preview();

        // The form questions are all empty, we expect the following default state
        validateThatQuestionIsVisible("My question that is always visible");
        validateThatQuestionIsNotVisible("My question that is visible if some criteria are met");
        validateThatQuestionIsVisible("My question that is hidden if some criteria are met");

        // Note: after changing the answer, make sure that the first value that is being
        // checked has a different visibility that in the previous assertions.
        // Indeed, if we don't do that the assertion might be validated instantly
        // before the UI is updated with the new visibilities.
        // By checking for a different value, we make sure the first assertion can't
        // run until the UI is updated - thus making the other assertions safe.

        // Set first answer to "Expected answer 1" and check the displayed content again.
        setTextAnswer("My question used as a criteria", "Expected answer 1");
        validateThatQuestionIsVisible("My question that is visible if some criteria are met");
        validateThatQuestionIsVisible("My question that is hidden if some criteria are met");
        validateThatQuestionIsVisible("My question that is always visible");

        // Set first answer to "Expected answer 2" and check the displayed content again.
        setTextAnswer("My question used as a criteria", "Expected answer 2");
        validateThatQuestionIsNotVisible("My question that is hidden if some criteria are met");
        validateThatQuestionIsNotVisible("My question that is visible if some criteria are met");
        validateThatQuestionIsVisible("My question that is always visible");
    });

    it('conditions are applied on questions that uses array values', () => {
        // Some kind of conditions use an array for their values (e.g. checkboxes, dropdowns).
        // We need a dedicated test for them to be sure that the code that deal
        // with the value can handle arrays correctly.
        createForm();

        // Add the "array" question
        addQuestion('My array question used as a criteria');
        setQuestionTypeCategory('Checkbox');
        getAndFocusQuestion('My array question used as a criteria').within(() => {
            cy.findByPlaceholderText('Enter an option').type('Option 1{enter}');
        });
        cy.focused().type('Option 2{enter}');
        cy.focused().type('Option 3{enter}');
        cy.focused().type('Option 4');

        // Add a question that will be visible depending on the array question value
        addQuestion('My question that is visible if some criteria are met');
        getAndFocusQuestion('My question that is visible if some criteria are met').within(() => {
            initVisibilityConfiguration();
            setConditionStrategy('Visible if...');
            fillCondition(
                0,
                null,
                'My array question used as a criteria',
                'Is equal to',
                ['Option 1', 'Option 4'],
                'dropdown_multiple',
            );
        });
        save();
        preview();

        // The form questions are all empty, we expect the following default state
        validateThatQuestionIsNotVisible("My question that is visible if some criteria are met");

        // Check the correct values
        cy.findByRole('checkbox', {'name': 'Option 1'}).check();
        cy.findByRole('checkbox', {'name': 'Option 4'}).check();
        validateThatQuestionIsVisible("My question that is visible if some criteria are met");

        // Uncheck one value
        cy.findByRole('checkbox', {'name': 'Option 1'}).uncheck();
        validateThatQuestionIsNotVisible("My question that is visible if some criteria are met");
    });

    // Radio, checkboxes and dropdown questions need extensive testing because
    // they rely on a specific data format being send from the client to the
    // backend when the form is submitted.
    // It it thus needed to have a dedicated e2e tests for them as the backend
    // tests can't know if the client code is wrong.
    // It cost us a bit of extra execution time but it is worth it because these
    // types of questions will be the one most likely to be used as conditions.
    const cases = [
        {
            question_type: "Checkbox",
            is_array: true,
            dom_role: 'checkbox',
        },
        {
            question_type: "Radio",
            is_array: false,
            dom_role: 'radio',
        },
        {
            question_type: "Dropdown",
            is_array: false,
            dom_role: 'select2',
        },
    ];
    for (const test_case of cases) {
        it(`conditions using "${test_case.question_type}" question as subject`, () => {
            createForm();

            // Add the question that will be used as a condition criteria
            addQuestion('My question used as a criteria');
            setQuestionTypeCategory(test_case.question_type);
            getAndFocusQuestion('My question used as a criteria').within(() => {
                cy.findByPlaceholderText('Enter an option').type('Option 1{enter}', {force: true}); // Force because getAndFocusQuestion will click in the middle of the question, thus trigerring the default value dropdown that will be displayed over this field. There are no good solutions here.
            });
            cy.focused().type('Option 2{enter}');
            cy.focused().type('Option 3{enter}');
            cy.focused().type('Option 4');

            // Add a question that will be visible depending on our subject value
            addQuestion('My question that is visible if some criteria are met');
            getAndFocusQuestion('My question that is visible if some criteria are met').within(() => {
                initVisibilityConfiguration();
                setConditionStrategy('Visible if...');
                fillCondition(
                    0,
                    null,
                    'My question used as a criteria',
                    'Is equal to',
                    test_case.is_array ? ['Option 3'] : 'Option 3',
                    test_case.is_array ? 'dropdown_multiple' : 'dropdown',
                );
                closeConditionEditor();
            });
            save();
            preview();

            // The form questions are all empty, the test question should be hidden.
            validateThatQuestionIsNotVisible("My question that is visible if some criteria are met");

            // Check the correct values
            if (test_case.dom_role === 'select2') {
                cy.getDropdownByLabelText('My question used as a criteria')
                    .selectDropdownValue('Option 3')
                ;
            } else {
                cy.findByRole(test_case.dom_role, {'name': 'Option 3'}).check();
            }
            validateThatQuestionIsVisible("My question that is visible if some criteria are met");

            // Change to an incorrect value
            if (test_case.dom_role === 'checkbox') {
                cy.findByRole(test_case.dom_role, {'name': 'Option 3'}).uncheck();
            } else if (test_case.dom_role === 'radio') {
                cy.findByRole(test_case.dom_role, {'name': 'Option 1'}).check();
            } else if (test_case.dom_role === 'select2') {
                cy.getDropdownByLabelText('My question used as a criteria')
                    .selectDropdownValue('Option 1')
                ;
            }
            validateThatQuestionIsNotVisible("My question that is visible if some criteria are met");
        });
    }

    it('conditions are applied on comments', () => {
        createForm();
        addQuestion('My question used as a criteria');
        addComment('My comment that is always visible');
        addComment('My comment that is visible if some criteria are met');
        addComment('My comment that is hidden if some criteria are met');

        getAndFocusComment('My comment that is always visible').within(() => {
            initVisibilityConfiguration();
            setConditionStrategy('Always visible');
            closeVisibilityConfiguration();
        });
        getAndFocusComment('My comment that is visible if some criteria are met').within(() => {
            initVisibilityConfiguration();
            setConditionStrategy('Visible if...');
            fillCondition(
                0,
                null,
                'My question used as a criteria',
                'Is equal to',
                'Expected answer 1'
            );
            closeVisibilityConfiguration();
        });
        getAndFocusComment('My comment that is hidden if some criteria are met').within(() => {
            initVisibilityConfiguration();
            setConditionStrategy('Hidden if...');
            fillCondition(
                0,
                null,
                'My question used as a criteria',
                'Is equal to',
                'Expected answer 2'
            );
            closeVisibilityConfiguration();
        });
        save();
        preview();

        // The form questions are all empty, we expect the following default state
        validateThatCommentIsVisible("My comment that is always visible");
        validateThatCommentIsVisible("My comment that is hidden if some criteria are met");
        validateThatCommentIsNotVisible("My comment that is visible if some criteria are met");

        // Note: after changing the answer, make sure that the first value that is being
        // checked has a different visibility that in the previous assertions.
        // Indeed, if we don't do that the assertion might be validated instantly
        // before the UI is updated with the new visibilities.
        // By checking for a different value, we make sure the first assertion can't
        // run until the UI is updated - thus making the other assertions safe.

        // Set first answer to "Expected answer 1" and check the displayed content again.
        setTextAnswer("My question used as a criteria", "Expected answer 1");
        validateThatCommentIsVisible("My comment that is visible if some criteria are met");
        validateThatCommentIsVisible("My comment that is hidden if some criteria are met");
        validateThatCommentIsVisible("My comment that is always visible");

        // Set first answer to "Expected answer 2" and check the displayed content again.
        setTextAnswer("My question used as a criteria", "Expected answer 2");
        validateThatCommentIsNotVisible("My comment that is hidden if some criteria are met");
        validateThatCommentIsNotVisible("My comment that is visible if some criteria are met");
        validateThatCommentIsVisible("My comment that is always visible");
    });

    it('conditions are applied on sections', () => {
        createForm();
        addQuestion('My question used as a criteria');
        addSection('My section that is always visible');
        addQuestion('Just a question to avoid empty sections');
        addSection('My section that is visible if some criteria are met');
        addQuestion('Just a question to avoid empty sections');
        addSection('My section that is hidden if some criteria are met');
        addQuestion('Just a question to avoid empty sections');

        getAndFocusSection('My section that is always visible').within(() => {
            initVisibilityConfiguration();
            setConditionStrategy('Always visible');
            closeVisibilityConfiguration();
        });
        getAndFocusSection('My section that is visible if some criteria are met').within(() => {
            initVisibilityConfiguration();
            setConditionStrategy('Visible if...');
            fillCondition(
                0,
                null,
                'My question used as a criteria',
                'Is equal to',
                'Expected answer 1'
            );
            closeVisibilityConfiguration();
        });
        getAndFocusSection('My section that is hidden if some criteria are met').within(() => {
            initVisibilityConfiguration();
            setConditionStrategy('Hidden if...');
            fillCondition(
                0,
                null,
                'My question used as a criteria',
                'Is equal to',
                'Expected answer 2'
            );
            closeVisibilityConfiguration();
        });
        save();
        preview();

        // The form questions are all empty, we expect the following default state
        validateSectionOrder([
            'First section',
            'My section that is always visible',
            'My section that is hidden if some criteria are met',
        ]);

        // Set first answer to "Expected answer 1" and check the displayed content again.
        setTextAnswer("My question used as a criteria", "Expected answer 1");
        validateSectionOrder([
            'First section',
            'My section that is always visible',
            'My section that is visible if some criteria are met',
            'My section that is hidden if some criteria are met',
        ]);

        // Set first answer to "Expected answer 2" and check the displayed content again.
        setTextAnswer("My question used as a criteria", "Expected answer 2");
        validateSectionOrder([
            'First section',
            'My section that is always visible',
        ]);
    });

    const questionsToAdd = {
        'QuestionTypeShortText': [
            {
                name: 'My text question',
                type: 'Glpi\\Form\\QuestionType\\QuestionTypeShortText',
                conditions: [
                    {
                        logic: 'Or',
                        operator: 'Is equal to',
                        value: 'Exact match',
                        valueType: 'string'
                    },
                    {
                        logic: 'Or',
                        operator: 'Is not equal to',
                        value: 'Exact match',
                        valueType: 'string'
                    },
                    {
                        logic: 'Or',
                        operator: 'Contains',
                        value: 'Expected answer',
                        valueType: 'string'
                    },
                    {
                        logic: 'Or',
                        operator: 'Do not contains',
                        value: 'Expected answer',
                        valueType: 'string'
                    },
                    {
                        logic: 'Or',
                        operator: 'Match regular expression',
                        value: '/Expected answer/',
                        valueType: 'string'
                    },
                    {
                        logic: 'Or',
                        operator: 'Do not match regular expression',
                        value: '/Expected answer/',
                        valueType: 'string'
                    },
                    {
                        logic: 'Or',
                        operator: 'Length is greater than',
                        value: 5,
                        valueType: 'number'
                    },
                    {
                        logic: 'Or',
                        operator: 'Length is less than',
                        value: 10,
                        valueType: 'number'
                    },
                    {
                        logic: 'Or',
                        operator: 'Length is greater than or equals to',
                        value: 5,
                        valueType: 'number'
                    },
                    {
                        logic: 'Or',
                        operator: 'Length is less than or equals to',
                        value: 10,
                        valueType: 'number'
                    },
                ]
            },
        ],
        'QuestionTypeNumber': [
            {
                name: 'My number question',
                type: 'Glpi\\Form\\QuestionType\\QuestionTypeNumber',
                subType: 'Number',
                conditions: [
                    {
                        logic: 'Or',
                        operator: 'Is equal to',
                        value: 10,
                        valueType: 'number'
                    },
                    {
                        logic: 'Or',
                        operator: 'Is not equal to',
                        value: 10,
                        valueType: 'number'
                    },
                    {
                        logic: 'Or',
                        operator: 'Is greater than',
                        value: 5,
                        valueType: 'number'
                    },
                    {
                        logic: 'Or',
                        operator: 'Is less than',
                        value: 10,
                        valueType: 'number'
                    },
                    {
                        logic: 'Or',
                        operator: 'Is greater than or equals to',
                        value: 5,
                        valueType: 'number'
                    },
                    {
                        logic: 'Or',
                        operator: 'Is less than or equals to',
                        value: 10,
                        valueType: 'number'
                    },
                    {
                        logic: 'Or',
                        operator: 'Match regular expression',
                        value: '/^[0-9]$/',
                        valueType: 'string'
                    },
                    {
                        logic: 'Or',
                        operator: 'Do not match regular expression',
                        value: '/^[0-9]$/',
                        valueType: 'string'
                    },
                ]
            },
        ],
        'QuestionTypeEmail': [
            {
                name: 'My email question',
                type: 'Glpi\\Form\\QuestionType\\QuestionTypeEmail',
                conditions: [
                    {
                        logic: null,
                        operator: 'Is visible',
                        value: null,
                        valueType: null
                    },
                    {
                        logic: 'Or',
                        operator: 'Is not visible',
                        value: null,
                        valueType: null
                    },
                    {
                        logic: 'Or',
                        operator: 'Is equal to',
                        value: 'Exact match',
                        valueType: 'string'
                    },
                    {
                        logic: 'Or',
                        operator: 'Is not equal to',
                        value: 'Exact match',
                        valueType: 'string'
                    },
                    {
                        logic: 'Or',
                        operator: 'Contains',
                        value: 'Expected answer',
                        valueType: 'string'
                    },
                    {
                        logic: 'Or',
                        operator: 'Do not contains',
                        value: 'Expected answer',
                        valueType: 'string'
                    },
                    {
                        logic: 'Or',
                        operator: 'Match regular expression',
                        value: '/Expected answer/',
                        valueType: 'string'
                    },
                    {
                        logic: 'Or',
                        operator: 'Do not match regular expression',
                        value: '/Expected answer/',
                        valueType: 'string'
                    },
                    {
                        logic: 'Or',
                        operator: 'Length is greater than',
                        value: 5,
                        valueType: 'number'
                    },
                    {
                        logic: 'Or',
                        operator: 'Length is less than',
                        value: 10,
                        valueType: 'number'
                    },
                    {
                        logic: 'Or',
                        operator: 'Length is greater than or equals to',
                        value: 5,
                        valueType: 'number'
                    },
                    {
                        logic: 'Or',
                        operator: 'Length is less than or equals to',
                        value: 10,
                        valueType: 'number'
                    },
                ]
            },
        ],
        'QuestionTypeLongText': [
            {
                name: 'My long text question',
                type: 'Glpi\\Form\\QuestionType\\QuestionTypeLongText',
                conditions: [
                    {
                        logic: 'Or',
                        operator: 'Is equal to',
                        value: 'Exact match',
                        valueType: 'string'
                    },
                    {
                        logic: 'Or',
                        operator: 'Is not equal to',
                        value: 'Exact match',
                        valueType: 'string'
                    },
                    {
                        logic: 'Or',
                        operator: 'Contains',
                        value: 'Expected answer',
                        valueType: 'string'
                    },
                    {
                        logic: 'Or',
                        operator: 'Do not contains',
                        value: 'Expected answer',
                        valueType: 'string'
                    },
                    {
                        logic: 'Or',
                        operator: 'Match regular expression',
                        value: '/Expected answer/',
                        valueType: 'string'
                    },
                    {
                        logic: 'Or',
                        operator: 'Do not match regular expression',
                        value: '/Expected answer/',
                        valueType: 'string'
                    },
                    {
                        logic: 'Or',
                        operator: 'Length is greater than',
                        value: 5,
                        valueType: 'number'
                    },
                    {
                        logic: 'Or',
                        operator: 'Length is less than',
                        value: 10,
                        valueType: 'number'
                    },
                    {
                        logic: 'Or',
                        operator: 'Length is greater than or equals to',
                        value: 5,
                        valueType: 'number'
                    },
                    {
                        logic: 'Or',
                        operator: 'Length is less than or equals to',
                        value: 10,
                        valueType: 'number'
                    },
                ],
            },
        ],
        'QuestionTypeDate': [
            {
                name: 'My date question',
                type: 'Glpi\\Form\\QuestionType\\QuestionTypeDateTime',
                extra_data: '{"is_default_value_current_time":"0","is_date_enabled":"1","is_time_enabled":"0"}',
                conditions: [
                    {
                        logic: 'Or',
                        operator: 'Is equal to',
                        value: '2021-01-01',
                        valueType: 'date'
                    },
                    {
                        logic: 'Or',
                        operator: 'Is not equal to',
                        value: '2021-01-01',
                        valueType: 'date'
                    },
                    {
                        logic: 'Or',
                        operator: 'Is greater than',
                        value: '2021-01-01',
                        valueType: 'date'
                    },
                    {
                        logic: 'Or',
                        operator: 'Is less than',
                        value: '2021-01-01',
                        valueType: 'date'
                    },
                    {
                        logic: 'Or',
                        operator: 'Is greater than or equals to',
                        value: '2021-01-01',
                        valueType: 'date'
                    },
                    {
                        logic: 'Or',
                        operator: 'Is less than or equals to',
                        value: '2021-01-01',
                        valueType: 'date'
                    },
                    {
                        logic: 'Or',
                        operator: 'Match regular expression',
                        value: '/^2021-01-01$/',
                        valueType: 'date'
                    },
                    {
                        logic: 'Or',
                        operator: 'Do not match regular expression',
                        value: '/^2021-01-01$/',
                        valueType: 'date'
                    },
                ]
            },
        ],
        'QuestionTypeTime': [
            {
                name: 'My time question',
                type: 'Glpi\\Form\\QuestionType\\QuestionTypeDateTime',
                extra_data: '{"is_default_value_current_time":"0","is_date_enabled":"0","is_time_enabled":"1"}',
                conditions: [
                    {
                        logic: 'Or',
                        operator: 'Is equal to',
                        value: '12:00',
                        valueType: 'date'
                    },
                    {
                        logic: 'Or',
                        operator: 'Is not equal to',
                        value: '12:00',
                        valueType: 'date'
                    },
                    {
                        logic: 'Or',
                        operator: 'Is greater than',
                        value: '12:00',
                        valueType: 'date'
                    },
                    {
                        logic: 'Or',
                        operator: 'Is less than',
                        value: '12:00',
                        valueType: 'date'
                    },
                    {
                        logic: 'Or',
                        operator: 'Is greater than or equals to',
                        value: '12:00',
                        valueType: 'date'
                    },
                    {
                        logic: 'Or',
                        operator: 'Is less than or equals to',
                        value: '12:00',
                        valueType: 'date'
                    },
                    {
                        logic: 'Or',
                        operator: 'Match regular expression',
                        value: '/^12:00$/',
                        valueType: 'date'
                    },
                    {
                        logic: 'Or',
                        operator: 'Do not match regular expression',
                        value: '/^12:00$/',
                        valueType: 'date'
                    },
                ]
            },
        ],
        'QuestionTypeDateTime': [
            {
                name: 'My datetime question',
                type: 'Glpi\\Form\\QuestionType\\QuestionTypeDateTime',
                extra_data: '{"is_default_value_current_time":"0","is_date_enabled":"1","is_time_enabled":"1"}',
                conditions: [
                    {
                        logic: 'Or',
                        operator: 'Is equal to',
                        value: '2021-01-01T12:00',
                        valueType: 'date'
                    },
                    {
                        logic: 'Or',
                        operator: 'Is not equal to',
                        value: '2021-01-01T12:00',
                        valueType: 'date'
                    },
                    {
                        logic: 'Or',
                        operator: 'Is greater than',
                        value: '2021-01-01T12:00',
                        valueType: 'date'
                    },
                    {
                        logic: 'Or',
                        operator: 'Is less than',
                        value: '2021-01-01T12:00',
                        valueType: 'date'
                    },
                    {
                        logic: 'Or',
                        operator: 'Is greater than or equals to',
                        value: '2021-01-01T12:00',
                        valueType: 'date'
                    },
                    {
                        logic: 'Or',
                        operator: 'Is less than or equals to',
                        value: '2021-01-01T12:00',
                        valueType: 'date'
                    },
                    {
                        logic: 'Or',
                        operator: 'Match regular expression',
                        value: '/^2021-01-01T12:00$/',
                        valueType: 'date'
                    },
                    {
                        logic: 'Or',
                        operator: 'Do not match regular expression',
                        value: '/^2021-01-01T12:00$/',
                        valueType: 'date'
                    },
                ]
            },
        ],
        'QuestionTypeRequester': [
            {
                name: 'My requester question',
                type: 'Glpi\\Form\\QuestionType\\QuestionTypeRequester',
                conditions: [
                    {
                        logic: 'Or',
                        operator: 'Is equal to',
                        value: 'glpi',
                        valueType: 'dropdown'
                    },
                    {
                        logic: 'Or',
                        operator: 'Is not equal to',
                        value: 'glpi',
                        valueType: 'dropdown'
                    },
                    {
                        logic: 'Or',
                        operator: 'Contains',
                        value: 'glpi',
                        valueType: 'dropdown'
                    },
                    {
                        logic: 'Or',
                        operator: 'Do not contains',
                        value: 'glpi',
                        valueType: 'dropdown'
                    },
                    {
                        logic: 'Or',
                        operator: 'Match regular expression',
                        value: '/glpi/',
                        valueType: 'string'
                    },
                    {
                        logic: 'Or',
                        operator: 'Do not match regular expression',
                        value: '/glpi/',
                        valueType: 'string'
                    },
                ],
            }
        ],
        'QuestionTypeObserver': [
            {
                name: 'My observer question',
                type: 'Glpi\\Form\\QuestionType\\QuestionTypeObserver',
                conditions: [
                    {
                        logic: 'Or',
                        operator: 'Is equal to',
                        value: 'glpi',
                        valueType: 'dropdown'
                    },
                    {
                        logic: 'Or',
                        operator: 'Is not equal to',
                        value: 'glpi',
                        valueType: 'dropdown'
                    },
                    {
                        logic: 'Or',
                        operator: 'Contains',
                        value: 'glpi',
                        valueType: 'dropdown'
                    },
                    {
                        logic: 'Or',
                        operator: 'Do not contains',
                        value: 'glpi',
                        valueType: 'dropdown'
                    },
                    {
                        logic: 'Or',
                        operator: 'Match regular expression',
                        value: '/glpi/',
                        valueType: 'string'
                    },
                    {
                        logic: 'Or',
                        operator: 'Do not match regular expression',
                        value: '/glpi/',
                        valueType: 'string'
                    },
                ],
            }
        ],
        'QuestionTypeAssignee': [
            {
                name: 'My assignee question',
                type: 'Glpi\\Form\\QuestionType\\QuestionTypeAssignee',
                conditions: [
                    {
                        logic: 'Or',
                        operator: 'Is equal to',
                        value: 'glpi',
                        valueType: 'dropdown'
                    },
                    {
                        logic: 'Or',
                        operator: 'Is not equal to',
                        value: 'glpi',
                        valueType: 'dropdown'
                    },
                    {
                        logic: 'Or',
                        operator: 'Contains',
                        value: 'glpi',
                        valueType: 'dropdown'
                    },
                    {
                        logic: 'Or',
                        operator: 'Do not contains',
                        value: 'glpi',
                        valueType: 'dropdown'
                    },
                    {
                        logic: 'Or',
                        operator: 'Match regular expression',
                        value: '/glpi/',
                        valueType: 'string'
                    },
                    {
                        logic: 'Or',
                        operator: 'Do not match regular expression',
                        value: '/glpi/',
                        valueType: 'string'
                    },
                ],
            }
        ],
        'QuestionTypeUrgency': [
            {
                name: 'My urgency question',
                type: 'Glpi\\Form\\QuestionType\\QuestionTypeUrgency',
                conditions: [
                    {
                        logic: 'Or',
                        operator: 'Is equal to',
                        value: 'High',
                        valueType: 'dropdown'
                    },
                    {
                        logic: 'Or',
                        operator: 'Is not equal to',
                        value: 'High',
                        valueType: 'dropdown'
                    },
                    {
                        logic: 'Or',
                        operator: 'Is greater than',
                        value: 'High',
                        valueType: 'dropdown'
                    },
                    {
                        logic: 'Or',
                        operator: 'Is less than',
                        value: 'High',
                        valueType: 'dropdown'
                    },
                    {
                        logic: 'Or',
                        operator: 'Is greater than or equals to',
                        value: 'High',
                        valueType: 'dropdown'
                    },
                    {
                        logic: 'Or',
                        operator: 'Is less than or equals to',
                        value: 'High',
                        valueType: 'dropdown'
                    },
                    {
                        logic: 'Or',
                        operator: 'Match regular expression',
                        value: '/^1$/',
                        valueType: 'string'
                    },
                    {
                        logic: 'Or',
                        operator: 'Do not match regular expression',
                        value: '/^1$/',
                        valueType: 'string'
                    }
                ],
            },
        ],
        'QuestionTypeRequestType': [
            {
                name: 'My request type question',
                type: 'Glpi\\Form\\QuestionType\\QuestionTypeRequestType',
                conditions: [
                    {
                        logic: 'Or',
                        operator: 'Is equal to',
                        value: 'Request',
                        valueType: 'dropdown'
                    },
                    {
                        logic: 'Or',
                        operator: 'Is not equal to',
                        value: 'Request',
                        valueType: 'dropdown'
                    },
                    {
                        logic: 'Or',
                        operator: 'Match regular expression',
                        value: '/^1$/',
                        valueType: 'string'
                    },
                    {
                        logic: 'Or',
                        operator: 'Do not match regular expression',
                        value: '/^1$/',
                        valueType: 'string'
                    }
                ],
            },
        ],
        'QuestionTypeFile': [
            {
                name: 'My file question',
                type: 'Glpi\\Form\\QuestionType\\QuestionTypeFile',
                conditions: [
                    {
                        logic: 'Or',
                        operator: 'Match regular expression',
                        value: '/^file_[0-9]+\\.txt$/',
                        valueType: 'string'
                    },
                    {
                        logic: 'Or',
                        operator: 'Do not match regular expression',
                        value: '/^file_[0-9]+\\.txt$/',
                        valueType: 'string'
                    },
                ],
            },
        ],
        'QuestionTypeRadio': [
            {
                name: 'My radio question',
                type: 'Glpi\\Form\\QuestionType\\QuestionTypeRadio',
                extra_data: '{"options":{"1":"Option 1","2":"Option 2","3":"Option 3","4":"Option 4"}}',
                conditions: [
                    {
                        logic: 'Or',
                        operator: 'Is equal to',
                        value: 'Option 3',
                        valueType: 'dropdown'
                    },
                    {
                        logic: 'Or',
                        operator: 'Is not equal to',
                        value: 'Option 2',
                        valueType: 'dropdown'
                    },
                    {
                        logic: 'Or',
                        operator: 'Match regular expression',
                        value: '^Option [1-4]$',
                        valueType: 'string'
                    },
                    {
                        logic: 'Or',
                        operator: 'Do not match regular expression',
                        value: '^Option [1-4]$',
                        valueType: 'string'
                    }
                ]
            }
        ],
        'QuestionTypeCheckbox': [
            {
                name: 'My checkbox question',
                type: 'Glpi\\Form\\QuestionType\\QuestionTypeCheckbox',
                extra_data: '{"options":{"1":"Option 1","2":"Option 2","3":"Option 3","4":"Option 4"}}',
                conditions: [
                    {
                        logic: 'Or',
                        operator: 'Is equal to',
                        value: ['Option 2', 'Option 4'],
                        valueType: 'dropdown_multiple'
                    },
                    {
                        logic: 'Or',
                        operator: 'Is not equal to',
                        value: ['Option 2', 'Option 4'],
                        valueType: 'dropdown_multiple'
                    },
                    {
                        logic: 'Or',
                        operator: 'Contains',
                        value: ['Option 2', 'Option 4'],
                        valueType: 'dropdown_multiple'
                    },
                    {
                        logic: 'Or',
                        operator: 'Do not contains',
                        value: ['Option 1', 'Option 3'],
                        valueType: 'dropdown_multiple'
                    },
                    {
                        logic: 'Or',
                        operator: 'Match regular expression',
                        value: '^Option [1-4]$',
                        valueType: 'string'
                    },
                    {
                        logic: 'Or',
                        operator: 'Do not match regular expression',
                        value: '^Option [1-4]$',
                        valueType: 'string'
                    }
                ]
            },
        ],
        'QuestionTypeDropdownSingle': [
            {
                name: 'My single value dropdown question',
                type: 'Glpi\\Form\\QuestionType\\QuestionTypeDropdown',
                extra_data: '{"is_multiple_dropdown":false,"options":{"1":"Option 1","2":"Option 2","3":"Option 3","4":"Option 4"}}',
                conditions: [
                    {
                        logic: 'Or',
                        operator: 'Is equal to',
                        value: 'Option 3',
                        valueType: 'dropdown'
                    },
                    {
                        logic: 'Or',
                        operator: 'Is not equal to',
                        value: 'Option 2',
                        valueType: 'dropdown'
                    },
                    {
                        logic: 'Or',
                        operator: 'Match regular expression',
                        value: '^Option [1-4]$',
                        valueType: 'string'
                    },
                    {
                        logic: 'Or',
                        operator: 'Do not match regular expression',
                        value: '^Option [1-4]$',
                        valueType: 'string'
                    }
                ]
            },
        ],
        'QuestionTypeDropdownMultiple': [
            {
                name: 'My multiple value dropdown question',
                type: 'Glpi\\Form\\QuestionType\\QuestionTypeDropdown',
                extra_data: '{"is_multiple_dropdown":true,"options":{"1":"Option 1","2":"Option 2","3":"Option 3","4":"Option 4"}}',
                conditions: [
                    {
                        logic: 'Or',
                        operator: 'Is equal to',
                        value: ['Option 2', 'Option 4'],
                        valueType: 'dropdown_multiple'
                    },
                    {
                        logic: 'Or',
                        operator: 'Is not equal to',
                        value: ['Option 2', 'Option 4'],
                        valueType: 'dropdown_multiple'
                    },
                    {
                        logic: 'Or',
                        operator: 'Contains',
                        value: ['Option 2', 'Option 4'],
                        valueType: 'dropdown_multiple'
                    },
                    {
                        logic: 'Or',
                        operator: 'Do not contains',
                        value: ['Option 2', 'Option 4'],
                        valueType: 'dropdown_multiple'
                    },
                    {
                        logic: 'Or',
                        operator: 'Match regular expression',
                        value: '^Option [1-4]$',
                        valueType: 'string'
                    },
                    {
                        logic: 'Or',
                        operator: 'Do not match regular expression',
                        value: '^Option [1-4]$',
                        valueType: 'string'
                    }
                ]
            },
        ],
        'QuestionTypeItem': [
            {
                name: 'My item question',
                type: 'Glpi\\Form\\QuestionType\\QuestionTypeItem',
                extra_data: '{"itemtype":"Computer","root_items_id":0,"subtree_depth":0,"selectable_tree_root":false}',
                conditions: [
                    {
                        logic: 'Or',
                        operator: 'Is equal to',
                        value: 'Computer - {uuid}',
                        valueType: 'dropdown'
                    },
                    {
                        logic: 'Or',
                        operator: 'Is not equal to',
                        value: 'Computer - {uuid}',
                        valueType: 'dropdown'
                    },
                    {
                        logic: 'Or',
                        operator: 'Contains',
                        value: 'Computer - {uuid}',
                        valueType: 'string'
                    },
                    {
                        logic: 'Or',
                        operator: 'Do not contains',
                        value: 'Computer - {uuid}',
                        valueType: 'string'
                    },
                    {
                        logic: 'Or',
                        operator: 'Match regular expression',
                        value: '/Computer/',
                        valueType: 'string'
                    },
                    {
                        logic: 'Or',
                        operator: 'Do not match regular expression',
                        value: '/Computer/',
                        valueType: 'string'
                    }
                ]
            },
        ],
        'QuestionTypeItemDropdown': [
            {
                name: 'My dropdown item question',
                type: 'Glpi\\Form\\QuestionType\\QuestionTypeItemDropdown',
                extra_data: '{"itemtype":"Location","categories_filter":[],"root_items_id":0,"subtree_depth":0,"selectable_tree_root":false}',
                conditions: [
                    {
                        logic: 'Or',
                        operator: 'Is equal to',
                        value: '»Location - {uuid}',
                        valueType: 'dropdown'
                    },
                    {
                        logic: 'Or',
                        operator: 'Is not equal to',
                        value: '»Location - {uuid}',
                        valueType: 'dropdown'
                    },
                    {
                        logic: 'Or',
                        operator: 'Contains',
                        value: 'Location - {uuid}',
                        valueType: 'string'
                    },
                    {
                        logic: 'Or',
                        operator: 'Do not contains',
                        value: 'Location - {uuid}',
                        valueType: 'string'
                    },
                    {
                        logic: 'Or',
                        operator: 'Match regular expression',
                        value: '/Location/',
                        valueType: 'string'
                    },
                    {
                        logic: 'Or',
                        operator: 'Do not match regular expression',
                        value: '/Location/',
                        valueType: 'string'
                    },
                ]
            },
        ],
        'QuestionTypeUserDeviceSingle': [
            {
                name: 'My single user devices question',
                type: 'Glpi\\Form\\QuestionType\\QuestionTypeUserDevice',
                extra_data: '{"is_multiple_devices":false}',
                conditions: [
                    {
                        logic: 'Or',
                        operator: 'Is of itemtype',
                        value: 'Computer',
                        valueType: 'dropdown'
                    },
                    {
                        logic: 'Or',
                        operator: 'Is not of itemtype',
                        value: 'Computer',
                        valueType: 'dropdown'
                    },
                    {
                        logic: 'Or',
                        operator: 'Contains',
                        value: 'Computer',
                        valueType: 'string'
                    },
                    {
                        logic: 'Or',
                        operator: 'Do not contains',
                        value: 'Computer',
                        valueType: 'string'
                    },
                    {
                        logic: 'Or',
                        operator: 'Match regular expression',
                        value: '/Computer/',
                        valueType: 'string'
                    },
                    {
                        logic: 'Or',
                        operator: 'Do not match regular expression',
                        value: '/Computer/',
                        valueType: 'string'
                    },
                ]
            },
        ],
        'QuestionTypeUserDeviceMultiple': [
            {
                name: 'My multiple user devices question',
                type: 'Glpi\\Form\\QuestionType\\QuestionTypeUserDevice',
                extra_data: '{"is_multiple_devices":true}',
                conditions: [
                    {
                        logic: 'Or',
                        operator: 'At least one item of itemtype',
                        value: ['Computer'],
                        valueType: 'dropdown_multiple'
                    },
                    {
                        logic: 'Or',
                        operator: 'All items of itemtype',
                        value: ['Computer'],
                        valueType: 'dropdown_multiple'
                    },
                    {
                        logic: 'Or',
                        operator: 'Contains',
                        value: 'Computer',
                        valueType: 'string'
                    },
                    {
                        logic: 'Or',
                        operator: 'Do not contains',
                        value: 'Computer',
                        valueType: 'string'
                    },
                    {
                        logic: 'Or',
                        operator: 'Match regular expression',
                        value: '/Computer/',
                        valueType: 'string'
                    },
                    {
                        logic: 'Or',
                        operator: 'Do not match regular expression',
                        value: '/Computer/',
                        valueType: 'string'
                    },
                ]
            }
        ],
    };

    // All questions implement "Is visible", "Is not visible", "Is empty" and "Is not empty" conditions
    Object.values(questionsToAdd).forEach((questions) => {
        questions.forEach((question) => {
            question.conditions.push(
                {
                    logic: null,
                    operator: 'Is visible',
                    value: null,
                    valueType: null
                },
                {
                    logic: 'Or',
                    operator: 'Is not visible',
                    value: null,
                    valueType: null
                },
                {
                    logic: 'Or',
                    operator: 'Is empty',
                    value: null,
                    valueType: null
                },
                {
                    logic: 'Or',
                    operator: 'Is not empty',
                    value: null,
                    valueType: null
                }
            );
        });
    });

    it('can all questions type are tested for conditions', () => {
        const expected_types = Object.values(questionsToAdd).reduce((acc, questions) => {
            return acc.concat(questions.map(q => q.type));
        }, []);

        // Create the test form
        createForm();

        // Add a question
        addQuestion('Test question');
        getAndFocusQuestion('Test question').within(() => {
            cy.get('select[aria-label="Question type"]').then(($dropdown) => {
                // Check that all question types are available in the dropdown
                const categories = $dropdown.find('option').toArray().map(option => option.textContent.trim());
                for (const category of categories) {
                    // Exclude tester plugin category
                    if (category === 'Tester plugin') {
                        continue;
                    }

                    cy.getDropdownByLabelText('Question type').selectDropdownValue(category);
                    cy.get('select[aria-label="Question sub type"]').then(($subtypeDropdown) => {
                        $subtypeDropdown.find('option').toArray()
                            .forEach((subtype) => {
                                // Check that the subtype is contained in the expected types
                                expect(expected_types).to.include(subtype.value);
                            });
                    });
                }
            });
        });
    });

    Object.entries(questionsToAdd).forEach(([type, questionsList]) => {
        it(`can apply all available conditions on ${type}`, () => {
            const uuid = new Date().getTime();

            createForm();

            // Create test items in GLPI that we'll use in conditions
            cy.createWithAPI('Computer', {
                'name': `Computer - ${uuid}`,
            });
            cy.createWithAPI('Location', {
                'name': `Location - ${uuid}`,
            });
            cy.createWithAPI('Computer', {
                name    : `Assigned Computer - ${uuid}`,
                users_id: 7, // E2E Tests user id
            });

            // Add test subject question where conditions will be applied
            cy.get('@form_id').then((formId) => {
                cy.addQuestionToDefaultSectionWithAPI(
                    formId,
                    'Test subject',
                    'Glpi\\Form\\QuestionType\\QuestionTypeShortText',
                    0,
                    null
                );
                questions.push('Test subject');
            });

            // Create all questions of this type through API
            questionsList.forEach((question, index) => {
                cy.get('@form_id').then((formId) => {
                    cy.addQuestionToDefaultSectionWithAPI(
                        formId,
                        question.name,
                        question.type,
                        index + 1,
                        null,
                        null,
                        question.extra_data,
                    );
                    questions.push(question.name);
                });
            });

            cy.reload();

            // Configure visibility conditions on the test subject question
            getAndFocusQuestion('Test subject').within(() => {
                // Initialize the visibility configuration UI
                initVisibilityConfiguration();
                setConditionStrategy('Visible if...');

                let cIndex = 0;

                // Add conditions for each question in this type
                questionsList.forEach((question, qIndex) => {
                    if (qIndex > 0) {
                        // Add a new empty condition if not the first one
                        addNewEmptyCondition();
                        cy.findByRole('button', {'name': 'Add another criteria'}).should('not.exist');
                    }

                    // Check if all operators are tested
                    const expected_operators = question.conditions.map(c => c.operator);
                    cy.get('[data-glpi-conditions-editor-condition-index]').eq(-1).as('condition');
                    cy.get('@condition').getDropdownByLabelText('Item').selectDropdownValue(question.name);
                    cy.get('@condition').within(() => {
                        cy.get('select[aria-label="Value operator"]').last().then(($dropdown) => {
                            const operators = $dropdown.find('option').toArray().map(option => option.textContent.trim());
                            for (const operator of operators) {
                                expect(expected_operators).to.include(operator, `Operator "${operator}" is not tested for question "${question.name}"`);
                            }
                        });

                        // Delete the condition
                        // eslint-disable-next-line
                        cy.findByRole('button', {'name': 'Delete criteria'}).as('deleteButton').click().should('not.exist');
                    });

                    question.conditions.forEach((condition) => {
                        // Calculate overall condition index
                        const conditionIndex = cIndex;

                        // Add new empty condition if not the first one
                        if (conditionIndex > 0) {
                            addNewEmptyCondition();
                        }

                        // Replace {uuid} placeholder in condition value if it exists
                        let value = condition.value;
                        if (typeof value === 'string' && value.includes('{uuid}')) {
                            value = value.replace('{uuid}', uuid);
                        } else if (Array.isArray(value)) {
                            value = value.map(v => typeof v === 'string' && v.includes('{uuid}')
                                ? v.replace('{uuid}', uuid)
                                : v);
                        }

                        // Fill the condition
                        fillCondition(
                            conditionIndex,
                            conditionIndex === 0 ? null : condition.logic || 'And',
                            question.name,
                            condition.operator,
                            value,
                            condition.valueType
                        );

                        // Increment condition index
                        cIndex++;
                    });
                });
            });

            // Save and reload to ensure all conditions are properly stored
            saveAndReload();

            // Verify all conditions are correctly saved and displayed
            getAndFocusQuestion('Test subject').within(() => {
                openConditionEditor();

                // Verify each condition
                let conditionIndex = 0;
                questionsList.forEach((question) => {
                    question.conditions.forEach((condition) => {
                        // Replace {uuid} placeholder in expected value if it exists
                        let expectedValue = condition.value;
                        if (typeof expectedValue === 'string' && expectedValue.includes('{uuid}')) {
                            expectedValue = expectedValue.replace('{uuid}', uuid);
                        } else if (Array.isArray(expectedValue)) {
                            expectedValue = expectedValue.map(v =>
                                typeof v === 'string' && v.includes('{uuid}') ? v.replace('{uuid}', uuid) : v);
                        }

                        // Handle special value transformations after save
                        if (type === 'QuestionTypeActor' && expectedValue === 'E2E Tests') {
                            expectedValue = 'e2e_tests';
                        }

                        if (typeof expectedValue === 'string' && expectedValue.startsWith('»')) {
                            expectedValue = expectedValue.substring(1);
                        }

                        // Check that the condition exists with correct values
                        checkThatConditionExist(
                            conditionIndex,
                            conditionIndex === 0 ? null : condition.logic || 'And',
                            `Questions - ${question.name}`,
                            condition.operator,
                            expectedValue,
                            condition.valueType
                        );

                        conditionIndex++;
                    });
                });
            });
        });
    });

    it('can apply visibility conditions to questions', () => {
        createForm();
        addQuestion('My question used as a criteria');
        addQuestion('My question that is visible if some criteria are met');
        addQuestion('My question that is visible if previous question is visible');

        getAndFocusQuestion('My question that is visible if some criteria are met').within(() => {
            initVisibilityConfiguration();
            setConditionStrategy('Visible if...');
            fillCondition(
                0,
                null,
                'My question used as a criteria',
                'Is equal to',
                'Expected answer 1'
            );
            closeVisibilityConfiguration();
        });

        getAndFocusQuestion('My question that is visible if previous question is visible').within(() => {
            initVisibilityConfiguration();
            setConditionStrategy('Visible if...');
            fillCondition(
                0,
                null,
                'My question that is visible if some criteria are met',
                'Is visible',
                null,
                null,
            );
        });

        save();
        preview();

        // The form questions are all empty, we expect the following default state
        validateThatQuestionIsVisible("My question used as a criteria");
        validateThatQuestionIsNotVisible("My question that is visible if some criteria are met");
        validateThatQuestionIsNotVisible("My question that is visible if previous question is visible");

        // Note: after changing the answer, make sure that the first value that is being
        // checked has a different visibility that in the previous assertions.
        // Indeed, if we don't do that the assertion might be validated instantly
        // before the UI is updated with the new visibilities.
        // By checking for a different value, we make sure the first assertion can't
        // run until the UI is updated - thus making the other assertions safe.

        // Set first answer to "Expected answer 1" and check the displayed content again.
        setTextAnswer("My question used as a criteria", "Expected answer 1");
        validateThatQuestionIsVisible("My question that is visible if some criteria are met");
        validateThatQuestionIsVisible("My question that is visible if previous question is visible");
        validateThatQuestionIsVisible("My question used as a criteria");

        // Set first answer to "Expected answer 2" and check the displayed content again.
        setTextAnswer("My question used as a criteria", "Expected answer 2");
        validateThatQuestionIsNotVisible("My question that is visible if some criteria are met");
        validateThatQuestionIsNotVisible("My question that is visible if previous question is visible");
        validateThatQuestionIsVisible("My question used as a criteria");
    });

    it('can apply visibility conditions to comments', () => {
        createForm();
        addQuestion('My question used as a criteria');
        addComment('My comment that is visible if some criteria are met');
        addComment('My comment that is visible if previous comment is visible');

        getAndFocusComment('My comment that is visible if some criteria are met').within(() => {
            initVisibilityConfiguration();
            setConditionStrategy('Visible if...');
            fillCondition(
                0,
                null,
                'My question used as a criteria',
                'Is equal to',
                'Expected answer 1'
            );
            closeVisibilityConfiguration();
        });

        getAndFocusComment('My comment that is visible if previous comment is visible').within(() => {
            initVisibilityConfiguration();
            setConditionStrategy('Visible if...');
            fillCondition(
                0,
                null,
                'My comment that is visible if some criteria are met',
                'Is visible',
                null,
                null,
            );
        });

        save();
        preview();

        // The form questions are all empty, we expect the following default state
        validateThatQuestionIsVisible("My question used as a criteria");
        validateThatCommentIsNotVisible("My comment that is visible if some criteria are met");
        validateThatCommentIsNotVisible("My comment that is visible if previous comment is visible");

        // Note: after changing the answer, make sure that the first value that is being
        // checked has a different visibility that in the previous assertions.
        // Indeed, if we don't do that the assertion might be validated instantly
        // before the UI is updated with the new visibilities.
        // By checking for a different value, we make sure the first assertion can't
        // run until the UI is updated - thus making the other assertions safe.

        // Set first answer to "Expected answer 1" and check the displayed content again.
        setTextAnswer("My question used as a criteria", "Expected answer 1");
        validateThatQuestionIsVisible("My question used as a criteria");
        validateThatCommentIsVisible("My comment that is visible if some criteria are met");
        validateThatCommentIsVisible("My comment that is visible if previous comment is visible");

        // Set first answer to "Expected answer 2" and check the displayed content again.
        setTextAnswer("My question used as a criteria", "Expected answer 2");
        validateThatQuestionIsVisible("My question used as a criteria");
        validateThatCommentIsNotVisible("My comment that is visible if some criteria are met");
        validateThatCommentIsNotVisible("My comment that is visible if previous comment is visible");
    });

    it('can apply visibility conditions to sections', () => {
        createForm();
        addQuestion('My question used as a criteria');
        addSection('My section that is visible if some criteria are met');
        addQuestion('Just a question to avoid empty sections');
        addSection('My section that is visible if previous section is visible');
        addQuestion('Just a question to avoid empty sections');

        getAndFocusSection('My section that is visible if some criteria are met').within(() => {
            initVisibilityConfiguration();
            setConditionStrategy('Visible if...');
            fillCondition(
                0,
                null,
                'My question used as a criteria',
                'Is equal to',
                'Expected answer 1'
            );
            closeVisibilityConfiguration();
        });

        getAndFocusSection('My section that is visible if previous section is visible').within(() => {
            initVisibilityConfiguration();
            setConditionStrategy('Visible if...');
            fillCondition(
                0,
                null,
                'My section that is visible if some criteria are met',
                'Is visible',
                null,
                null,
            );
        });

        save();
        preview();

        // The form questions are all empty, we expect the following default state
        validateSectionOrder([
            'First section',
        ]);

        // Note: after changing the answer, make sure that the first value that is being
        // checked has a different visibility that in the previous assertions.
        // Indeed, if we don't do that the assertion might be validated instantly
        // before the UI is updated with the new visibilities.
        // By checking for a different value, we make sure the first assertion can't
        // run until the UI is updated - thus making the other assertions safe.

        // Set first answer to "Expected answer 1" and check the displayed content again.
        setTextAnswer("My question used as a criteria", "Expected answer 1");
        validateSectionOrder([
            'First section',
            'My section that is visible if some criteria are met',
            'My section that is visible if previous section is visible',
        ]);

        // Set first answer to "Expected answer 2" and check the displayed content again.
        setTextAnswer("My question used as a criteria", "Expected answer 2");
        validateSectionOrder([
            'First section',
        ]);
    });

    it("can't delete a question used in conditions", () => {
        createForm();
        addQuestion('My first question');
        addQuestion('My second question');

        getAndFocusQuestion('My second question').within(() => {
            initVisibilityConfiguration();
            setConditionStrategy('Visible if...');
            fillCondition(0, null, 'My first question', 'Contains', 'GLPI is great');
        });
        saveAndReload();

        // Delete the first question and check that the conditions are still there
        getAndFocusQuestion('My first question').within(() => {
            cy.findByRole('button', {'name': 'Delete'}).click();
        });
        cy.findByRole('dialog', {'name': 'Item has conditions and cannot be deleted'})
            .should('have.attr', 'data-cy-shown', 'true')
            .within(() => {
                cy.findByRole('link', {'name': 'My second question'}).should('be.visible');
                cy.findByRole('link', {'name': 'First section'}).should('not.exist');
                cy.findByRole('button', {'name': 'Close'}).click();
            });
        saveAndReload();

        getAndFocusQuestion('My second question').within(() => {
            openConditionEditor();
            checkThatConditionExist(
                0,
                null,
                'Questions - My first question',
                'Contains',
                'GLPI is great',
            );
        });

        // Delete the first question and check that the conditions are still there
        getAndFocusQuestion('My first question').within(() => {
            cy.findByRole('button', {'name': 'Delete'}).click();
        });
        cy.findByRole('dialog', {'name': 'Item has conditions and cannot be deleted'})
            .should('have.attr', 'data-cy-shown', 'true')
            .within(() => {
                cy.findByRole('link', {'name': 'My second question'}).should('be.visible');
                cy.findByRole('link', {'name': 'First section'}).should('not.exist');
                cy.findByRole('button', {'name': 'Close'}).click();
            });

        // Delete conditions
        getAndFocusQuestion('My second question').within(() => {
            openConditionEditor();
            deleteCondition(0);
        });

        // Delete the first question
        getAndFocusQuestion('My first question').within(() => {
            cy.findByRole('button', {'name': 'Delete'}).click();
        });
        cy.findByRole('dialog', {'name': 'Item has conditions and cannot be deleted'}).should('not.exist');
    });

    it("can't change the type of a question used in conditions with unsupported value operators", () => {
        createForm();
        addQuestion('My first question');
        addQuestion('My second question');

        getAndFocusQuestion('My first question').changeQuestionType('Urgency');

        getAndFocusQuestion('My second question').within(() => {
            initVisibilityConfiguration();
            setConditionStrategy('Visible if...');
            fillCondition(0, null, 'My first question', 'Is greater than', 'High', 'dropdown');
        });
        saveAndReload();

        // Change the type of the first question to a type that doesn't support "Is greater than" operator
        getAndFocusQuestion('My first question').changeQuestionType('Short answer');
        cy.findByRole('dialog', {'name': 'Question has conditions and its type cannot be changed'})
            .should('have.attr', 'data-cy-shown', 'true')
            .within(() => {
                cy.findByRole('link', {'name': 'My second question'}).should('be.visible');
                cy.findByRole('link', {'name': 'First section'}).should('not.exist');
                cy.findByRole('button', {'name': 'Close'}).click();
            });
        saveAndReload();

        getAndFocusQuestion('My second question').within(() => {
            openConditionEditor();
            checkThatConditionExist(
                0,
                null,
                'Questions - My first question',
                'Is greater than',
                'High',
                'dropdown'
            );
        });

        // Change the type of the first question to a type that doesn't support "Is greater than" operator
        getAndFocusQuestion('My first question').changeQuestionType('Short answer');
        cy.findByRole('dialog', {'name': 'Question has conditions and its type cannot be changed'})
            .should('have.attr', 'data-cy-shown', 'true')
            .within(() => {
                cy.findByRole('link', {'name': 'My second question'}).should('be.visible');
                cy.findByRole('link', {'name': 'First section'}).should('not.exist');
                cy.findByRole('button', {'name': 'Close'}).click();
            });

        // Delete conditions
        getAndFocusQuestion('My second question').within(() => {
            openConditionEditor();
            deleteCondition(0);
        });

        // Change the type of the first question to a type that doesn't support "Is greater than" operator
        getAndFocusQuestion('My first question').changeQuestionType('Short answer');
        cy.findByRole('dialog', {'name': 'Question has conditions and its type cannot be changed'}).should('not.exist');
    });

    it("can't delete a comment used in conditions", () => {
        createForm();
        addComment('My comment');
        addQuestion('My question');

        getAndFocusQuestion('My question').within(() => {
            initVisibilityConfiguration();
            setConditionStrategy('Visible if...');
            fillCondition(0, null, 'My comment', 'Is visible', null, null);
        });
        saveAndReload();

        // Delete the comment and check that the conditions are still there
        getAndFocusComment('My comment').within(() => {
            cy.findByRole('button', {'name': 'Delete'}).click();
        });
        cy.findByRole('dialog', {'name': 'Item has conditions and cannot be deleted'})
            .should('have.attr', 'data-cy-shown', 'true')
            .within(() => {
                cy.findByRole('link', {'name': 'My question'}).should('be.visible');
                cy.findByRole('link', {'name': 'First section'}).should('not.exist');
                cy.findByRole('button', {'name': 'Close'}).click();
            });
        saveAndReload();

        getAndFocusQuestion('My question').within(() => {
            openConditionEditor();
            checkThatConditionExist(
                0,
                null,
                'Comments - My comment',
                'Is visible',
                null,
                null,
            );
        });

        // Delete the comment and check that the conditions are still there
        getAndFocusComment('My comment').within(() => {
            cy.findByRole('button', {'name': 'Delete'}).click();
        });
        cy.findByRole('dialog', {'name': 'Item has conditions and cannot be deleted'})
            .should('have.attr', 'data-cy-shown', 'true')
            .within(() => {
                cy.findByRole('link', {'name': 'My question'}).should('be.visible');
                cy.findByRole('link', {'name': 'First section'}).should('not.exist');
                cy.findByRole('button', {'name': 'Close'}).click();
            });

        // Delete conditions
        getAndFocusQuestion('My question').within(() => {
            openConditionEditor();
            deleteCondition(0);
        });

        // Delete the commeny
        getAndFocusComment('My comment').within(() => {
            cy.findByRole('button', {'name': 'Delete'}).click();
        });
        cy.findByRole('dialog', {'name': 'Item has conditions and cannot be deleted'}).should('not.exist');
    });

    it("can't delete a section used in conditions", () => {
        createForm();
        addQuestion('My first question');
        addSection('Second section');
        addQuestion('My second question');

        getAndFocusSection('Second section').within(() => {
            initVisibilityConfiguration();
            setConditionStrategy('Visible if...');
            fillCondition(0, null, 'First section', 'Is visible', null, null);
        });
        saveAndReload();

        // Delete the first section and check that the conditions are still there
        getAndFocusSection('First section').within(() => {
            cy.findByRole('button', {'name': 'More actions'}).click();
            cy.findByRole('button', {'name': 'Delete section'}).click();
        });
        cy.findByRole('dialog', {'name': 'Item has conditions and cannot be deleted'})
            .should('have.attr', 'data-cy-shown', 'true')
            .within(() => {
                cy.findByRole('link', {'name': 'Second section'}).should('be.visible');
                cy.findByRole('button', {'name': 'Close'}).click();
            });
        saveAndReload();

        getAndFocusSection('Second section').within(() => {
            openConditionEditor();
            checkThatConditionExist(
                0,
                null,
                'Sections - First section',
                'Is visible',
                null,
                null,
            );
        });

        // Delete the first section and check that the conditions are still there
        getAndFocusSection('First section').within(() => {
            cy.findByRole('button', {'name': 'More actions'}).click();
            cy.findByRole('button', {'name': 'Delete section'}).click();
        });
        cy.findByRole('dialog', {'name': 'Item has conditions and cannot be deleted'})
            .should('have.attr', 'data-cy-shown', 'true')
            .within(() => {
                cy.findByRole('link', {'name': 'Second section'}).should('be.visible');
                cy.findByRole('button', {'name': 'Close'}).click();
            });

        // Delete conditions
        getAndFocusSection('Second section').within(() => {
            openConditionEditor();
            deleteCondition(0);
        });

        // Delete the section
        getAndFocusSection('Second section').within(() => {
            cy.findByRole('button', {'name': 'More actions'}).click();
            cy.findByRole('button', {'name': 'Delete section'}).click();
        });
        cy.findByRole('dialog', {'name': 'Item has conditions and cannot be deleted'})
            .should('not.exist');
    });

    it("can't delete a section, question or comment used in destination conditions", () => {
        createForm();
        addQuestion('My first question');
        addSection('My section');
        addComment('My first comment');

        saveAndReload();
        goToDestinationTab();

        // Define destination conditions
        checkThatConditionEditorIsNotDisplayed();
        openConditionEditor();
        setConditionStrategy('Created if...');
        checkThatConditionEditorIsDisplayed();
        fillCondition(0, null, 'My first question', 'Is equal to', 'Expected answer 1');
        addNewEmptyCondition();
        fillCondition(1, null, 'My section', 'Is visible', null, null);
        addNewEmptyCondition();
        fillCondition(2, null, 'My first comment', 'Is visible', null, null);

        saveDestination();

        // Check that the conditions are still there
        openConditionEditor();
        checkThatConditionExist(
            0,
            null,
            'Questions - My first question',
            'Is equal to',
            'Expected answer 1'
        );
        checkThatConditionExist(
            1,
            null,
            'Sections - My section',
            'Is visible',
            null,
            null
        );
        checkThatConditionExist(
            2,
            null,
            'Comments - My first comment',
            'Is visible',
            null,
            null
        );

        // Go to the form tab
        cy.findByRole('tab', {'name': 'Form'}).click();

        // Delete the first question
        getAndFocusQuestion('My first question').within(() => {
            cy.findByRole('button', {'name': 'Delete'}).click();
        });
        cy.findByRole('dialog', {'name': 'Item has conditions and cannot be deleted'})
            .should('have.attr', 'data-cy-shown', 'true')
            .within(() => {
                cy.findByRole('link', {'name': 'Ticket'}).should('be.visible');
                cy.findByRole('button', {'name': 'Close'}).click();
            });

        // Delete the section
        getAndFocusSection('My section').within(() => {
            cy.findByRole('button', {'name': 'More actions'}).click();
            cy.findByRole('button', {'name': 'Delete section'}).click();
        });
        cy.findByRole('dialog', {'name': 'Item has conditions and cannot be deleted'})
            .should('have.attr', 'data-cy-shown', 'true')
            .within(() => {
                cy.findByRole('link', {'name': 'Ticket'}).should('be.visible');
                cy.findByRole('button', {'name': 'Close'}).click();
            });

        // Delete the comment
        getAndFocusComment('My first comment').within(() => {
            cy.findByRole('button', {'name': 'Delete'}).click();
        });
        cy.findByRole('dialog', {'name': 'Item has conditions and cannot be deleted'})
            .should('have.attr', 'data-cy-shown', 'true')
            .within(() => {
                cy.findByRole('link', {'name': 'Ticket'}).should('be.visible');
                cy.findByRole('button', {'name': 'Close'}).click();
            });
    });

    it("can't change the type of a question used in destination conditions with unsupported value operators", () => {
        createForm();
        addQuestion('My first question');
        addQuestion('My second question');

        getAndFocusQuestion('My first question').changeQuestionType('Urgency');

        saveAndReload();
        goToDestinationTab();

        // Define destination conditions
        checkThatConditionEditorIsNotDisplayed();
        openConditionEditor();
        setConditionStrategy('Created if...');
        checkThatConditionEditorIsDisplayed();
        fillCondition(0, null, 'My first question', 'Is greater than', 'High', 'dropdown');

        saveDestination();

        // Change the type of the first question to a type that doesn't support "Is greater than" operator
        cy.findByRole('tab', {'name': 'Form'}).click();
        getAndFocusQuestion('My first question').changeQuestionType('Short answer');
        cy.findByRole('dialog', {'name': 'Question has conditions and its type cannot be changed'})
            .should('have.attr', 'data-cy-shown', 'true')
            .within(() => {
                cy.findByRole('link', {'name': 'Ticket'}).should('be.visible');
                cy.findByRole('button', {'name': 'Close'}).click();
            });
        cy.findByRole('dialog', {'name': 'Question has conditions and its type cannot be changed'}).should('not.exist');

        // Go to the destination tab and check that the conditions are still there
        goToDestinationTab();
        openConditionEditor();
        checkThatConditionExist(
            0,
            null,
            'Questions - My first question',
            'Is greater than',
            'High',
            'dropdown'
        );

        // Delete conditions
        goToDestinationTab();
        openConditionEditor();
        deleteCondition(0);
        saveDestination();

        // Change the type of the first question to a type that doesn't support "Is greater than" operator
        cy.findByRole('tab', {'name': 'Form'}).click();
        getAndFocusQuestion('My first question').changeQuestionType('Short answer');
        cy.findByRole('dialog', {'name': 'Question has conditions and its type cannot be changed'}).should('not.exist');
    });

    it('conditions count badge is updated when conditions are added or removed', () => {
        createForm();
        // Add two questions to the form
        addQuestion('My first question');
        addQuestion('My second question');

        // Helper function to check conditions count badge
        const checkConditionsCount = (count) => {
            cy.findByRole('status', {'name': 'Conditions count'})
                .invoke('text').invoke('trim')
                .should('eq', String(count));
        };

        // Focus on the second question
        getAndFocusQuestion('My second question').within(() => {
            // Initialize validation with "Visible if..." strategy
            initVisibilityConfiguration();
            setConditionStrategy('Visible if...');
            closeVisibilityConfiguration();

            // Verify initial count is 0
            checkConditionsCount('0');

            // Add first condition
            openConditionEditor();
            fillCondition(0, null, 'My first question', 'Is equal to', 'Expected answer 1');
            closeVisibilityConfiguration();
            checkConditionsCount('1');

            // Add second condition
            openConditionEditor();
            addNewEmptyCondition();
            fillCondition(1, null, 'My first question', 'Is equal to', 'Expected answer 2');
            closeVisibilityConfiguration();
            checkConditionsCount('2');

            // Delete first condition
            openConditionEditor();
            deleteCondition(0);
            closeVisibilityConfiguration();
            checkConditionsCount('1');
        });

        // Test persistence after reload
        saveAndReload();

        // Verify that condition count persists and can be reset to 0
        getAndFocusQuestion('My second question').within(() => {
            checkConditionsCount('1');

            openConditionEditor();
            deleteCondition(0);
            closeVisibilityConfiguration();
            checkConditionsCount('0');
        });
    });

    it('conditions count badge is updated when conditions are added or removed in form destination', () => {
        createForm();
        // Add a question to the form
        addQuestion('My first question');

        // Helper function to check conditions count badge
        const checkConditionsCount = (count) => {
            cy.findByRole('status', {'name': 'Conditions count'})
                .invoke('text').invoke('trim')
                .should('eq', String(count));
        };

        // Save the form to ensure we can access the destination tab
        saveAndReload();
        goToDestinationTab();

        // Initialize validation with "Created if..." strategy
        openConditionEditor();
        setConditionStrategy('Created if...');
        closeConditionEditor();

        // Verify initial count is 0
        checkConditionsCount('0');

        // Add first condition
        openConditionEditor();
        fillCondition(0, null, 'My first question', 'Is equal to', 'Expected answer 1');
        closeConditionEditor();
        checkConditionsCount('1');

        // Add second condition
        openConditionEditor();
        addNewEmptyCondition();
        fillCondition(1, null, 'My first question', 'Is equal to', 'Expected answer 2');
        closeConditionEditor();
        checkConditionsCount('2');

        // Delete first condition
        openConditionEditor();
        deleteCondition(0);
        closeConditionEditor();
        checkConditionsCount('1');

        // Save the destination conditions and reload the page to ensure persistence
        saveDestination();
        cy.reload();

        // Verify that condition count persists and can be reset to 0
        openConditionEditor();
        closeConditionEditor();
        checkConditionsCount('1');

        // Delete the remaining condition
        openConditionEditor();
        deleteCondition(0);
        closeConditionEditor();
        checkConditionsCount('0');
    });

    it('check whether uploading a file in a “File” question updates the visibility of a target question', () => {
        createForm();

        // Add a target question
        addQuestion('My target question');

        // Add a file upload question
        addQuestion('My file question');
        setQuestionTypeCategory('File');

        // Add a condition to show the target question if the file question is not empty
        getAndFocusQuestion('My target question').within(() => {
            initVisibilityConfiguration();
            setConditionStrategy('Visible if...');
            fillCondition(0, null, 'My file question', 'Is not empty', null, null);
            closeVisibilityConfiguration();
        });

        // Save and reload to ensure conditions are applied
        saveAndReload();

        // Preview the form
        preview();

        // Check that the target question is not visible initially
        validateThatQuestionIsNotVisible('My target question');

        // Upload a file to the file question
        cy.findByRole('region', {'name': 'My file question'}).within(() => {
            cy.get('input[type="file"]').selectFile('fixtures/uploads/bar.png');
        });

        // Check that the target question is now visible
        validateThatQuestionIsVisible('My target question');

        // Remove the uploaded file
        cy.findByRole('region', {'name': 'My file question'}).within(() => {
            cy.get('.fileupload .remove_file_upload').click();
        });

        // Check that the target question is not visible anymore
        validateThatQuestionIsNotVisible('My target question');
    });
});
