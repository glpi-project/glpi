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

function createForm() {
    cy.login();
    cy.createFormWithAPI().as('form_id').visitFormTab('Form');
    cy.then(() => {
        questions = [];
    });
}

function addQuestion(name, is_mandatory = false) {
    cy.findByRole('button', {'name': "Add a question"}).click();
    cy.focused().type(name);

    if (is_mandatory) {
        cy.findByRole('checkbox', {'name': 'Mandatory'}).check();
    }

    cy.then(() => {
        questions.push(name);
    });
}

function getAndFocusQuestion(name) {
    return cy.then(() => {
        const index = questions.indexOf(name);
        cy.findAllByRole('region', {'name': 'Question details', 'timeout': 10000}).eq(index).click();
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

function preview() {
    cy.findByRole('link', {'name': "Preview"})
        .invoke('attr', 'target', '_self')
        .click()
    ;
    cy.url().should('include', '/Form/Render');
}

function submit() {
    cy.findByRole('button', {'name': "Submit"}).click();
}

function checkThatValidationOptionsAreHidden() {
    cy.findByRole('label', {'name': "No validation"}).should('not.exist');
    cy.findByRole('label', {'name': "Valid if..."}).should('not.exist');
    cy.findByRole('label', {'name': "Invalid if..."}).should('not.exist');
}

function initValidationConfiguration() {
    cy.findByRole('button', {'name': 'More actions'}).click();
    cy.findByRole('button', {'name': 'Configure validation'}).click();
}

function openValidationConditionEditor() {
    cy.findByTitle(/Configure validation/).click();
    cy.waitForNetworkIdle(150);
}

function closeValidationConditionEditor() {
    cy.findByTitle(/Configure validation/).click();
}

function checkThatSelectedValidationOptionIs(option) {
    cy.findByRole('radio', {'name': option}).should('be.checked');
    cy.findByRole('button', {'name': option}).should('exist');
}

function setConditionStrategy(option) {
    // Label is the next node
    cy.findByRole('radio', {'name': option}).next().click();
}

function checkThatValidationOptionsAreVisible() {
    cy.findByRole('radio', {'name': "No validation"}).should('be.visible');
    cy.findByRole('radio', {'name': "Valid if..."}).should('be.visible');
    cy.findByRole('radio', {'name': "Invalid if..."}).should('be.visible');
}

function checkThatConditionEditorIsDisplayed() {
    cy.getDropdownByLabelText('Value operator').should('exist');
}

function checkThatConditionEditorIsNotDisplayed() {
    cy.getDropdownByLabelText('Value operator').should('not.exist');
}

function addNewEmptyCondition() {
    cy.findByRole('button', {'name': 'Add another criteria'}).click();
}

function deleteConditon(index) {
    cy.get("[data-glpi-conditions-editor-condition]").eq(index).as('condition');
    cy.get('@condition').findByRole('button', {'name': 'Delete criteria'}).click();
}

function fillCondition(index, logic_operator, value_operator_name, value, value_type = "string") {
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

function checkThatConditionExist(index, logic_operator, value_operator_name, value, value_type = "string") {
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

function checkQuestionValidationIsInvalid(name) {
    cy.findByRole('textbox', { name })
        .should('have.attr', 'aria-invalid', 'true')
        .should('have.attr', 'aria-errormessage')
    ;
}

function checkQuestionValidationIsValid(name) {
    cy.findByRole('textbox', { name }).should('not.have.attr', 'aria-invalid');
    cy.findByRole('textbox', { name }).should('not.have.attr', 'aria-errormessage');
}

function getAriaErrorMessageElement(element) {
    return element.invoke('attr', 'aria-errormessage').then((id) => cy.get(`#${id}`));
}

describe ('Validations', () => {
    beforeEach(() => {
        cy.login();
    });

    it('can set the conditional validation of a question', () => {
        createForm();
        addQuestion('My first question');
        saveAndReload();

        // Select 'Valid if...' (editor should be displayed)
        getAndFocusQuestion('My first question').within(() => {
            checkThatValidationOptionsAreHidden();
            initValidationConfiguration();
            checkThatValidationOptionsAreVisible();
            checkThatSelectedValidationOptionIs('No validation');
            checkThatConditionEditorIsNotDisplayed();
            setConditionStrategy('Valid if...');
            checkThatSelectedValidationOptionIs('Valid if...');
            checkThatConditionEditorIsDisplayed();
        });
        saveAndReload();
        getAndFocusQuestion('My first question').within(() => {
            openValidationConditionEditor();
            checkThatSelectedValidationOptionIs('Valid if...');
            checkThatConditionEditorIsDisplayed();
            closeValidationConditionEditor();
        });

        // Select 'Invalid if...' (editor should be displayed)
        getAndFocusQuestion('My first question').within(() => {
            checkThatValidationOptionsAreHidden();
            openValidationConditionEditor();
            checkThatValidationOptionsAreVisible();
            checkThatSelectedValidationOptionIs('Valid if...');
            checkThatConditionEditorIsDisplayed();
            setConditionStrategy('Invalid if...');
            checkThatSelectedValidationOptionIs('Invalid if...');
            checkThatConditionEditorIsDisplayed();
        });
        saveAndReload();
        getAndFocusQuestion('My first question').within(() => {
            openValidationConditionEditor();
            checkThatSelectedValidationOptionIs('Invalid if...');
            checkThatConditionEditorIsDisplayed();
            closeValidationConditionEditor();
        });

        // Select 'No validation' (editor should be hidden)
        getAndFocusQuestion('My first question').within(() => {
            checkThatValidationOptionsAreHidden();
            openValidationConditionEditor();
            checkThatValidationOptionsAreVisible();
            checkThatSelectedValidationOptionIs('Invalid if...');
            checkThatConditionEditorIsDisplayed();
            setConditionStrategy('No validation');
            checkThatSelectedValidationOptionIs('No validation');
            checkThatConditionEditorIsNotDisplayed();
        });
        saveAndReload();
        getAndFocusQuestion('My first question').within(() => {
            initValidationConfiguration();
            checkThatSelectedValidationOptionIs('No validation');
            checkThatConditionEditorIsNotDisplayed();
            closeValidationConditionEditor();
        });
    });

    it('can use the editor to add or delete conditions on a question', () => {
        createForm();
        addQuestion('My first question');
        addQuestion('My second question');
        addQuestion('My third question');
        saveAndReload();

        getAndFocusQuestion('My third question').within(() => {
            initValidationConfiguration();
            setConditionStrategy('Valid if...');
            fillCondition(0, null, 'Match regular expression', '/^I love GLPI$/');
            addNewEmptyCondition();
            fillCondition(1, 'Or', 'Match regular expression', '/^GLPI is great$/');
        });
        saveAndReload();
        getAndFocusQuestion('My third question').within(() => {
            openValidationConditionEditor();
            checkThatConditionExist(
                0,
                null,
                'Match regular expression',
                '/^I love GLPI$/',
            );
            checkThatConditionExist(
                1,
                'Or',
                'Match regular expression',
                '/^GLPI is great$/',
            );
            deleteConditon(0);
            checkThatConditionExist(
                0,
                null,
                'Match regular expression',
                '/^GLPI is great$/',
            );
            checkThatConditionDoNotExist(1);
        });
        saveAndReload();
        getAndFocusQuestion('My third question').within(() => {
            openValidationConditionEditor();
            checkThatConditionExist(
                0,
                null,
                'Match regular expression',
                '/^GLPI is great$/',
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
            initValidationConfiguration();
            setConditionStrategy('Valid if...');
            fillCondition(0, null, 'Match regular expression', '/^I love GLPI$/');
            addNewEmptyCondition();
            fillCondition(1, 'Or', 'Match regular expression', '/^GLPI is great$/');
        });
        saveAndReload();
        getAndFocusQuestion('My third question').within(() => {
            openValidationConditionEditor();
            checkThatConditionExist(
                0,
                null,
                'Match regular expression',
                '/^I love GLPI$/',
            );
            checkThatConditionExist(
                1,
                'Or',
                'Match regular expression',
                '/^GLPI is great$/',
            );
            deleteConditon(0);
            checkThatConditionExist(
                0,
                null,
                'Match regular expression',
                '/^GLPI is great$/',
            );
            checkThatConditionDoNotExist(1);
        });
        saveAndReload();
        getAndFocusQuestion('My third question').within(() => {
            openValidationConditionEditor();
            checkThatConditionExist(
                0,
                null,
                'Match regular expression',
                '/^GLPI is great$/',
            );
            checkThatConditionDoNotExist(1);
        });
    });

    it('conditions are applied on questions', () => {
        createForm();
        addQuestion('My question that has no validation', true);
        addQuestion('My question that is valid if some criteria are met', true);
        addQuestion('My question that is invalid if some criteria are met', true);

        getAndFocusQuestion('My question that has no validation').within(() => {
            initValidationConfiguration();
            setConditionStrategy('No validation');
            closeValidationConditionEditor();
        });
        getAndFocusQuestion('My question that is valid if some criteria are met').within(() => {
            initValidationConfiguration();
            setConditionStrategy('Valid if...');
            fillCondition(
                0,
                null,
                'Match regular expression',
                '/^I love GLPI$/'
            );
            closeValidationConditionEditor();
        });
        getAndFocusQuestion('My question that is invalid if some criteria are met').within(() => {
            initValidationConfiguration();
            setConditionStrategy('Invalid if...');
            fillCondition(
                0,
                null,
                'Match regular expression',
                '/^I love GLPI$/'
            );
            closeValidationConditionEditor();
        });
        save();
        preview();

        // Submit the form without filling any answer
        submit();

        // Check question validation
        checkQuestionValidationIsInvalid('My question that has no validation');
        checkQuestionValidationIsInvalid('My question that is valid if some criteria are met');
        checkQuestionValidationIsInvalid('My question that is invalid if some criteria are met');

        // Check that the validation message is displayed
        getAriaErrorMessageElement(cy.findByRole('textbox', { name: 'My question that has no validation' }))
            .should('contain.text', 'This field is mandatory');
        getAriaErrorMessageElement(cy.findByRole('textbox', { name: 'My question that is valid if some criteria are met' }))
            .should('contain.text', 'This field is mandatory');
        getAriaErrorMessageElement(cy.findByRole('textbox', { name: 'My question that is invalid if some criteria are met' }))
            .should('contain.text', 'This field is mandatory');

        // Fill all questions
        setTextAnswer('My question that has no validation', 'GLPI is great');
        setTextAnswer('My question that is valid if some criteria are met', 'GLPI is great');
        setTextAnswer('My question that is invalid if some criteria are met', 'GLPI is great');

        // Submit the form again
        submit();

        // Check question validation
        checkQuestionValidationIsValid('My question that has no validation');
        checkQuestionValidationIsInvalid('My question that is valid if some criteria are met');
        checkQuestionValidationIsValid('My question that is invalid if some criteria are met');

        // Check that the validation message is displayed
        getAriaErrorMessageElement(cy.findByRole('textbox', { name: 'My question that is valid if some criteria are met' }))
            .should('contain.text', 'The value must match the requested format');

        // Fill questions
        setTextAnswer('My question that is valid if some criteria are met', 'I love GLPI');
        setTextAnswer('My question that is invalid if some criteria are met', 'I love GLPI');

        // Submit the form again
        submit();

        // Check question validation
        checkQuestionValidationIsValid('My question that has no validation');
        checkQuestionValidationIsValid('My question that is valid if some criteria are met');
        checkQuestionValidationIsInvalid('My question that is invalid if some criteria are met');

        // Check that the validation message is displayed
        getAriaErrorMessageElement(cy.findByRole('textbox', { name: 'My question that is invalid if some criteria are met' }))
            .should('contain.text', 'The value must not match the requested format');
    });

    it('conditions count badge is updated when conditions are added or removed', () => {
        createForm();
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
            // Initialize validation with "Valid if..." strategy
            initValidationConfiguration();
            setConditionStrategy('Valid if...');
            closeValidationConditionEditor();

            // Verify initial count is 0
            checkConditionsCount('0');

            // Add first condition
            openValidationConditionEditor();
            fillCondition(0, null, 'Do not match regular expression', '/^Expected answer 1$/');
            closeValidationConditionEditor();
            checkConditionsCount('1');

            // Add second condition
            openValidationConditionEditor();
            addNewEmptyCondition();
            fillCondition(1, null, 'Match regular expression', '/^Expected answer 2$/');
            closeValidationConditionEditor();
            checkConditionsCount('2');

            // Delete first condition
            openValidationConditionEditor();
            deleteConditon(0);
            closeValidationConditionEditor();
            checkConditionsCount('1');
        });

        // Test persistence after reload
        saveAndReload();

        // Verify that condition count persists and can be reset to 0
        getAndFocusQuestion('My second question').within(() => {
            checkConditionsCount('1');

            openValidationConditionEditor();
            deleteConditon(0);
            closeValidationConditionEditor();
            checkConditionsCount('0');
        });
    });
});
