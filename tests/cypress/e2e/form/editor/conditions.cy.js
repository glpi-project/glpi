/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

let questions = null;
let comments = null;
let sections = null;

function createForm() {
    cy.login();
    cy.createFormWithAPI().visitFormTab('Form');
    cy.then(() => {
        questions = [];
        comments = [];
        sections = ["First section"];
    });
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

function setQuestionType(type) {
    cy.getDropdownByLabelText('Question sub type').selectDropdownValue(type);
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
    cy.reload();
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
    cy.findByRole('button', {'name': 'Configure visiblity'}).click();
}

function closeVisibilityConfiguration() {
    cy.get('body').type('{esc}');
}

function openVisibilityOptions() {
    cy.findByTitle('Configure visibility').click();
}

function closeVisibilityOptions() {
    cy.findByTitle('Configure visibility').click();
}

function checkThatSelectedVisibilityOptionIs(option) {
    cy.findByRole('radio', {'name': option}).should('be.checked');
    cy.findByRole('button', {'name': option}).should('exist');
}

function setVisibilityOption(option) {
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
    }
}

function checkThatConditionExist(index, logic_operator, question_name, value_operator_name, value, value_type = "string") {
    cy.get("[data-glpi-conditions-editor-condition]").eq(index).as('condition');
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
    } else {
        cy.get('@condition').findByRole('spinbutton', {'name': 'Value'}).should('have.value', value);
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
            setVisibilityOption('Visible if...');
            checkThatSelectedVisibilityOptionIs('Visible if...');
            checkThatConditionEditorIsDisplayed();
        });
        saveAndReload();
        getAndFocusQuestion('My first question').within(() => {
            openVisibilityOptions();
            checkThatSelectedVisibilityOptionIs('Visible if...');
            checkThatConditionEditorIsDisplayed();
            closeVisibilityOptions();
        });

        // Select 'Hidden if...' (editor should be displayed)
        getAndFocusQuestion('My first question').within(() => {
            checkThatVisibilityOptionsAreHidden();
            openVisibilityOptions();
            checkThatVisibilityOptionsAreVisible();
            checkThatSelectedVisibilityOptionIs('Visible if...');
            checkThatConditionEditorIsDisplayed();
            setVisibilityOption('Hidden if...');
            checkThatSelectedVisibilityOptionIs('Hidden if...');
            checkThatConditionEditorIsDisplayed();
        });
        saveAndReload();
        getAndFocusQuestion('My first question').within(() => {
            openVisibilityOptions();
            checkThatSelectedVisibilityOptionIs('Hidden if...');
            checkThatConditionEditorIsDisplayed();
            closeVisibilityOptions();
        });

        // Select 'Always visible' (editor should be hidden)
        getAndFocusQuestion('My first question').within(() => {
            checkThatVisibilityOptionsAreHidden();
            openVisibilityOptions();
            checkThatVisibilityOptionsAreVisible();
            checkThatSelectedVisibilityOptionIs('Hidden if...');
            checkThatConditionEditorIsDisplayed();
            setVisibilityOption('Always visible');
            checkThatSelectedVisibilityOptionIs('Always visible');
            checkThatConditionEditorIsNotDisplayed();
        });
        saveAndReload();
        getAndFocusQuestion('My first question').within(() => {
            initVisibilityConfiguration();
            checkThatSelectedVisibilityOptionIs('Always visible');
            checkThatConditionEditorIsNotDisplayed();
            closeVisibilityOptions();
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
            setVisibilityOption('Visible if...');
            fillCondition(0, null, 'My second question', 'Is not equal to', 'I love GLPI');
            addNewEmptyCondition();
            fillCondition(1, 'Or', 'My first question', 'Contains', 'GLPI is great');
        });
        saveAndReload();
        getAndFocusQuestion('My third question').within(() => {
            openVisibilityOptions();
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
            openVisibilityOptions();
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
            setVisibilityOption('Visible if...');
            fillCondition(0, null, 'My second question', 'Is not equal to', 'I love GLPI');
            addNewEmptyCondition();
            fillCondition(1, 'Or', 'My first question', 'Contains', 'GLPI is great');
        });
        saveAndReload();
        getAndFocusQuestion('My third question').within(() => {
            openVisibilityOptions();
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
            openVisibilityOptions();
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
            setVisibilityOption('Visible if...');
            fillCondition(0, null, 'My second question', 'Contains', 'I love GLPI');
            addNewEmptyCondition();
            fillCondition(1, 'Or', 'My first question', 'Contains', 'GLPI is great');
        });
        saveAndReload();
        getAndFocusComment('My first comment').within(() => {
            openVisibilityOptions();
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
            openVisibilityOptions();
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
            setVisibilityOption('Visible if...');
            fillCondition(0, null, 'My second question', 'Do not contains', 'I love GLPI');
            addNewEmptyCondition();
            fillCondition(1, 'Or', 'My first question', 'Contains', 'GLPI is great');
        });
        saveAndReload();
        getAndFocusSection('My second section').within(() => {
            openVisibilityOptions();
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
            openVisibilityOptions();
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

    it('conditions are applied on questions', () => {
        createForm();
        addQuestion('My question used as a criteria');
        addQuestion('My question that is always visible');
        addQuestion('My question that is visible if some criteria are met');
        addQuestion('My question that is hidden if some criteria are met');

        getAndFocusQuestion('My question that is always visible').within(() => {
            initVisibilityConfiguration();
            setVisibilityOption('Always visible');
        });
        getAndFocusQuestion('My question that is visible if some criteria are met').within(() => {
            initVisibilityConfiguration();
            setVisibilityOption('Visible if...');
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
            setVisibilityOption('Hidden if...');
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

    it('conditions are applied on comments', () => {
        createForm();
        addQuestion('My question used as a criteria');
        addComment('My comment that is always visible');
        addComment('My comment that is visible if some criteria are met');
        addComment('My comment that is hidden if some criteria are met');

        getAndFocusComment('My comment that is always visible').within(() => {
            initVisibilityConfiguration();
            setVisibilityOption('Always visible');
        });
        closeVisibilityConfiguration();
        getAndFocusComment('My comment that is visible if some criteria are met').within(() => {
            initVisibilityConfiguration();
            setVisibilityOption('Visible if...');
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
            setVisibilityOption('Hidden if...');
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
            setVisibilityOption('Always visible');
        });
        closeVisibilityConfiguration();
        getAndFocusSection('My section that is visible if some criteria are met').within(() => {
            initVisibilityConfiguration();
            setVisibilityOption('Visible if...');
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
            setVisibilityOption('Hidden if...');
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
        createForm();

        // Init test question on which we will add our conditions.
        addQuestion('Test subject');
        getAndFocusQuestion('Test subject').within(() => {
            initVisibilityConfiguration();
            setVisibilityOption('Visible if...');
        });
        closeVisibilityConfiguration();

        // Text condition
        addQuestion('My text question');
        getAndFocusQuestion('Test subject').within(() => {
            openVisibilityOptions();
            cy.waitForNetworkIdle(150);
            fillCondition(
                0,
                null,
                'My text question',
                'Contains',
                'Expected answer'
            );
        });
        closeVisibilityConfiguration();

        // Number condition
        addQuestion('My number question');
        setQuestionTypeCategory('Short answer');
        setQuestionType('Number');
        getAndFocusQuestion('Test subject').within(() => {
            openVisibilityOptions();
            cy.waitForNetworkIdle(150);
            addNewEmptyCondition();
            fillCondition(
                1,
                'And',
                'My number question',
                'Is greater than',
                10,
                "number",
            );
        });
        closeVisibilityConfiguration();

        // Reload and check values
        saveAndReload();

        getAndFocusQuestion('Test subject').within(() => {
            openVisibilityOptions();

            // Validate text condition
            checkThatConditionExist(
                0,
                null,
                'My text question',
                'Contains',
                'Expected answer',
            );

            // Validate number condition
            checkThatConditionExist(
                1,
                'And',
                'My number question',
                'Is greater than',
                10,
                'number',
            );
        });
    });
});
