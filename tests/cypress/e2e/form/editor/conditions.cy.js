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
    cy.findByRole('button', {'name': "Add a new question"}).click();
    cy.focused().type(name);
    cy.then(() => {
        questions.push(name);
    });
}

function setQuestionTypeCategory(category) {
    cy.getDropdownByLabelText('Question type').selectDropdownValue(category);
}

function addComment(name) {
    cy.findByRole('button', {'name': "Add a new comment"}).click();
    cy.focused().type(name);
    cy.then(() => {
        comments.push(name);
    });
}

function addSection(name) {
    cy.findByRole('button', {'name': "Add a new section"}).click();
    cy.focused().type(name);
    cy.then(() => {
        sections.push(name);
    });
}

function getAndFocusQuestion(name) {
    return cy.then(() => {
        const index = questions.indexOf(name);
        cy.findAllByRole('region', {'name': 'Question details'}).eq(index).click();
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

function deleteConditon(index) {
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

        // Make sure step label is accurate
        cy.findByText(`Step ${i + 1} of ${sections.length}`).should('be.visible');

        if (i + 1 === sections.length) {
            // Last section, do not submit form
            cy.findByRole('button', {'name': "Send form"}).should('be.visible');
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
                'My second question',
                'Is not equal to',
                'I love GLPI',
            );
            checkThatConditionExist(
                1,
                'Or',
                'My first question',
                'Contains',
                'GLPI is great',
            );
            deleteConditon(0);
            checkThatConditionExist(
                0,
                null,
                'My first question',
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
                'My first question',
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
                'My second question',
                'Is not equal to',
                'I love GLPI'
            );
            checkThatConditionExist(
                1,
                'Or',
                'My first question',
                'Contains',
                'GLPI is great',
            );
            deleteConditon(0);
            checkThatConditionExist(
                0,
                null,
                'My first question',
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
                'My first question',
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
                'My second question',
                'Contains',
                'I love GLPI'
            );
            checkThatConditionExist(
                1,
                'Or',
                'My first question',
                'Contains',
                'GLPI is great',
            );
            deleteConditon(0);
            checkThatConditionExist(
                0,
                null,
                'My first question',
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
                'My first question',
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
                'My second question',
                'Do not contains',
                'I love GLPI'
            );
            checkThatConditionExist(
                1,
                'Or',
                'My first question',
                'Contains',
                'GLPI is great',
            );
            deleteConditon(0);
            checkThatConditionExist(
                0,
                null,
                'My first question',
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
                'My first question',
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
        setConditionStrategy('Created if');
        fillCondition(0, null, 'My second question', 'Is not equal to', 'I love GLPI');
        addNewEmptyCondition();
        fillCondition(1, 'Or', 'My first question', 'Contains', 'GLPI is great');
        saveDestination();
        openConditionEditor();

        // Check that the conditions are correctly displayed
        checkThatConditionExist(
            0,
            null,
            'My second question',
            'Is not equal to',
            'I love GLPI',
        );
        checkThatConditionExist(
            1,
            'Or',
            'My first question',
            'Contains',
            'GLPI is great',
        );

        // Delete the first condition and check that the second one is still there
        deleteConditon(0);
        checkThatConditionExist(
            0,
            null,
            'My first question',
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
            'My first question',
            'Contains',
            'GLPI is great',
        );
        checkThatConditionDoNotExist(1);
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


    it('conditions are applied on comments', () => {
        createForm();
        addQuestion('My question used as a criteria');
        addComment('My comment that is always visible');
        addComment('My comment that is visible if some criteria are met');
        addComment('My comment that is hidden if some criteria are met');

        getAndFocusComment('My comment that is always visible').within(() => {
            initVisibilityConfiguration();
            setConditionStrategy('Always visible');
        });
        closeVisibilityConfiguration();
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
        });
        closeVisibilityConfiguration();
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
        });
        closeVisibilityConfiguration();
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
        addSection('My section that is visible if some criteria are met');
        addSection('My section that is hidden if some criteria are met');

        getAndFocusSection('My section that is always visible').within(() => {
            initVisibilityConfiguration();
            setConditionStrategy('Always visible');
        });
        closeVisibilityConfiguration();
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
        });
        closeVisibilityConfiguration();
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
        });
        closeVisibilityConfiguration();
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

    it('can apply all supported conditions types', () => {
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

        // Define all question types we need to test different condition operators
        const testQuestions = [
            // Main question where we'll add all our conditions
            { name: 'Test subject', type: 'Glpi\\Form\\QuestionType\\QuestionTypeShortText' },

            // Text type
            { name: 'My text question', type: 'Glpi\\Form\\QuestionType\\QuestionTypeShortText' },

            // Numeric type
            { name: 'My number question', type: 'Glpi\\Form\\QuestionType\\QuestionTypeNumber', subType: 'Number'},

            // Date/Time types
            {
                name: 'My date question',
                type: 'Glpi\\Form\\QuestionType\\QuestionTypeDateTime',
                extra_data: '{"is_default_value_current_time":"0","is_date_enabled":"1","is_time_enabled":"0"}'
            },
            {
                name: 'My time question',
                type: 'Glpi\\Form\\QuestionType\\QuestionTypeDateTime',
                extra_data: '{"is_default_value_current_time":"0","is_date_enabled":"0","is_time_enabled":"1"}'
            },
            {
                name: 'My datetime question',
                type: 'Glpi\\Form\\QuestionType\\QuestionTypeDateTime',
                extra_data: '{"is_default_value_current_time":"0","is_date_enabled":"1","is_time_enabled":"1"}'
            },

            // ITIL fields
            { name: 'My urgency question', type: 'Glpi\\Form\\QuestionType\\QuestionTypeUrgency'},
            { name: 'My request type question', type: 'Glpi\\Form\\QuestionType\\QuestionTypeRequestType'},

            // Selectable types
            {
                name: 'My radio question',
                type: 'Glpi\\Form\\QuestionType\\QuestionTypeRadio',
                extra_data: '{"options":{"1":"Option 1","2":"Option 2","3":"Option 3","4":"Option 4"}}'
            },
            {
                name: 'My checkbox question',
                type: 'Glpi\\Form\\QuestionType\\QuestionTypeCheckbox',
                extra_data: '{"options":{"1":"Option 1","2":"Option 2","3":"Option 3","4":"Option 4"}}'
            },
            {
                name: 'My single value dropdown question',
                type: 'Glpi\\Form\\QuestionType\\QuestionTypeDropdown',
                extra_data: '{"is_multiple_dropdown":false,"options":{"1":"Option 1","2":"Option 2","3":"Option 3","4":"Option 4"}}'
            },
            {
                name: 'My multiple value dropdown question',
                type: 'Glpi\\Form\\QuestionType\\QuestionTypeDropdown',
                extra_data: '{"is_multiple_dropdown":true,"options":{"1":"Option 1","2":"Option 2","3":"Option 3","4":"Option 4"}}'
            },

            // User types
            { name: 'My requester question', type: 'Glpi\\Form\\QuestionType\\QuestionTypeRequester'},
            { name: 'My observer question', type: 'Glpi\\Form\\QuestionType\\QuestionTypeObserver'},
            { name: 'My assignee question', type: 'Glpi\\Form\\QuestionType\\QuestionTypeAssignee'},

            // Item reference types
            {
                name: 'My item question',
                type: 'Glpi\\Form\\QuestionType\\QuestionTypeItem',
                extra_data: '{"itemtype":"Computer"}',
            },
            {
                name: 'My dropdown item question',
                type: 'Glpi\\Form\\QuestionType\\QuestionTypeItemDropdown',
                extra_data: '{"itemtype":"Location"}',
            },

            // User device reference types
            {
                name: 'My single user devices question',
                type: 'Glpi\\Form\\QuestionType\\QuestionTypeUserDevice',
                extra_data: '{"is_multiple_devices":false}',
            },
            {
                name: 'My multiple user devices question',
                type: 'Glpi\\Form\\QuestionType\\QuestionTypeUserDevice',
                extra_data: '{"is_multiple_devices":true}',
            }
        ];

        // Create all questions through API for better performance
        testQuestions.forEach((question, index) => {
            cy.get('@form_id').then((formId) => {
                cy.addQuestionToDefaultSectionWithAPI(
                    formId,
                    question.name,
                    question.type,
                    index,
                    null,
                    null,
                    question.extra_data,
                );
                questions.push(question.name);
            });
        });

        cy.reload();

        // Define conditions that will test each question type with appropriate operators
        const conditionsToTest = [
            // Text condition
            {
                logic: null,
                question: 'My text question',
                operator: 'Contains',
                value: 'Expected answer',
                valueType: 'string'
            },

            // Numeric condition
            {
                logic: 'And',
                question: 'My number question',
                operator: 'Is greater than',
                value: 10,
                valueType: 'number'
            },

            // Date/time conditions
            {
                logic: 'And',
                question: 'My date question',
                operator: 'Is greater than',
                value: '2021-01-01',
                valueType: 'date'
            },
            {
                logic: 'And',
                question: 'My time question',
                operator: 'Is greater than',
                value: '15:40',
                valueType: 'date'
            },
            {
                logic: 'And',
                question: 'My datetime question',
                operator: 'Is greater than',
                value: '2021-01-01T15:40',
                valueType: 'date'
            },

            // ITIL field conditions
            {
                logic: 'And',
                question: 'My urgency question',
                operator: 'Is greater than',
                value: 'Low',
                valueType: 'dropdown'
            },
            {
                logic: 'And',
                question: 'My request type question',
                operator: 'Is equal to',
                value: 'Request',
                valueType: 'dropdown'
            },

            // Selectable field conditions
            {
                logic: 'And',
                question: 'My radio question',
                operator: 'Is not equal to',
                value: 'Option 3',
                valueType: 'dropdown'
            },
            {
                logic: 'And',
                question: 'My checkbox question',
                operator: 'Contains',
                value: ['Option 2', 'Option 4'],
                valueType: 'dropdown_multiple'
            },
            {
                logic: 'And',
                question: 'My single value dropdown question',
                operator: 'Is not equal to',
                value: 'Option 2',
                valueType: 'dropdown'
            },
            {
                logic: 'And',
                question: 'My multiple value dropdown question',
                operator: 'Is not equal to',
                value: ['Option 1', 'Option 2'],
                valueType: 'dropdown_multiple'
            },

            // User field conditions
            {
                logic: 'And',
                question: 'My requester question',
                operator: 'Is equal to',
                value: 'E2E Tests',
                valueType: 'dropdown'
            },
            {
                logic: 'And',
                question: 'My observer question',
                operator: 'Is equal to',
                value: 'E2E Tests',
                valueType: 'dropdown'
            },
            {
                logic: 'And',
                question: 'My assignee question',
                operator: 'Is equal to',
                value: 'E2E Tests',
                valueType: 'dropdown'
            },

            // Item reference conditions
            {
                logic: 'And',
                question: 'My item question',
                operator: 'Is equal to',
                value: `Computer - ${uuid}`,
                valueType: 'dropdown'
            },
            {
                logic: 'And',
                question: 'My dropdown item question',
                operator: 'Is equal to',
                value: `»Location - ${uuid}`,
                valueType: 'dropdown'
            },

            // User device reference conditions
            {
                logic: 'And',
                question: 'My single user devices question',
                operator: 'Is of itemtype',
                value: 'Computer',
                valueType: 'dropdown'
            },
            {
                logic: 'And',
                question: 'My multiple user devices question',
                operator: 'At least one item of itemtype',
                value: 'Computer',
                valueType: 'dropdown'
            }
        ];

        // Configure visibility conditions on the test subject question
        getAndFocusQuestion('Test subject').within(() => {
            // Initialize the visibility configuration UI
            initVisibilityConfiguration();
            setConditionStrategy('Visible if...');

            // Add the first condition without a logical operator
            fillCondition(
                0,
                conditionsToTest[0].logic,
                conditionsToTest[0].question,
                conditionsToTest[0].operator,
                conditionsToTest[0].value,
                conditionsToTest[0].valueType
            );

            // Add all remaining conditions with their logical operators
            conditionsToTest.slice(1).forEach((condition, index) => {
                addNewEmptyCondition();
                fillCondition(
                    index + 1,
                    condition.logic,
                    condition.question,
                    condition.operator,
                    condition.value,
                    condition.valueType
                );
            });
        });

        // Save and reload to ensure all conditions are properly stored
        saveAndReload();

        // Define expected condition values after saving
        // Note: some values are adjusted to match how they appear in the UI after saving
        const expectedConditions = [
            { logic: null, question: 'My text question', operator: 'Contains', value: 'Expected answer' },
            { logic: 'And', question: 'My number question', operator: 'Is greater than', value: 10, valueType: 'number' },
            { logic: 'And', question: 'My date question', operator: 'Is greater than', value: '2021-01-01', valueType: 'date' },
            { logic: 'And', question: 'My time question', operator: 'Is greater than', value: '15:40', valueType: 'date' },
            { logic: 'And', question: 'My datetime question', operator: 'Is greater than', value: '2021-01-01T15:40', valueType: 'date' },
            { logic: 'And', question: 'My urgency question', operator: 'Is greater than', value: 'Low', valueType: 'dropdown' },
            { logic: 'And', question: 'My request type question', operator: 'Is equal to', value: 'Request', valueType: 'dropdown' },
            { logic: 'And', question: 'My radio question', operator: 'Is not equal to', value: 'Option 3', valueType: 'dropdown' },
            { logic: 'And', question: 'My checkbox question', operator: 'Contains', value: ['Option 2', 'Option 4'], valueType: 'dropdown_multiple' },
            { logic: 'And', question: 'My single value dropdown question', operator: 'Is not equal to', value: 'Option 2', valueType: 'dropdown' },
            { logic: 'And', question: 'My multiple value dropdown question', operator: 'Is not equal to', value: ['Option 1', 'Option 2'], valueType: 'dropdown_multiple' },
            { logic: 'And', question: 'My requester question', operator: 'Is equal to', value: 'e2e_tests', valueType: 'dropdown' },
            { logic: 'And', question: 'My observer question', operator: 'Is equal to', value: 'e2e_tests', valueType: 'dropdown' },
            { logic: 'And', question: 'My assignee question', operator: 'Is equal to', value: 'e2e_tests', valueType: 'dropdown' },
            { logic: 'And', question: 'My item question', operator: 'Is equal to', value: `Computer - ${uuid}`, valueType: 'dropdown' },
            { logic: 'And', question: 'My dropdown item question', operator: 'Is equal to', value: `Location - ${uuid}`, valueType: 'dropdown' },
            { logic: 'And', question: 'My single user devices question', operator: 'Is of itemtype', value: 'Computer', valueType: 'dropdown' },
            { logic: 'And', question: 'My multiple user devices question', operator: 'At least one item of itemtype', value: ['Computer'], valueType: 'dropdown_multiple' }
        ];

        // Verify all conditions are correctly saved and displayed
        getAndFocusQuestion('Test subject').within(() => {
            openConditionEditor();

            // Check each condition exists with the correct values
            // Adding a timeout increase for complex condition checks
            cy.wrap(null).then(() => {
                // Use a loop with cy.then() to ensure each check completes before starting the next
                const checkConditionSequentially = (index) => {
                    if (index >= expectedConditions.length) {
                        return; // Done checking all conditions
                    }

                    const condition = expectedConditions[index];
                    checkThatConditionExist(
                        index,
                        condition.logic,
                        condition.question,
                        condition.operator,
                        condition.value,
                        condition.valueType
                    );

                    // Check the next condition after the current one is verified
                    cy.then(() => checkConditionSequentially(index + 1));
                };

                // Start the sequential checking
                checkConditionSequentially(0);
            });
        });
    });
});
