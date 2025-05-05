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

describe('Form rendering', () => {
    it('Can preset form fields using GET parameters', () => {
        // Set up a form with all questions types that support url initialization
        cy.createWithAPI('Glpi\\Form\\Form', {
            'name': 'Test form preview',
        }).as('form_id');
        cy.login();

        cy.get('@form_id').then((form_id) => {
            const tab = 'Glpi\\Form\\Form$1';
            cy.visit(`/front/form/form.form.php?id=${form_id}&forcetab=${tab}`);
        });
        addQuestionAndGetUuuid('Name');
        addQuestionAndGetUuuid('Email', 'Short answer', 'Emails');
        addQuestionAndGetUuuid('Age', 'Short answer', 'Number');
        addQuestionAndGetUuuid('Prefered software', 'Long answer');
        addQuestionAndGetUuuid('Urgency', 'Urgency');
        addQuestionAndGetUuuid('Request type', 'Request type');
        cy.findByRole('button', { 'name': 'Save' }).click();
        cy.checkAndCloseAlert('Item successfully updated');

        // Go to the form and send preset values.
        cy.getMany([
            "@form_id",
            "@Name UUID",
            "@Email UUID",
            "@Age UUID",
            "@Prefered software UUID",
            "@Urgency UUID",
            "@Request type UUID",
        ]).then(([
            form_id,
            name_uuid,
            email_uuid,
            age_uuid,
            prefered_software_uuid,
            urgency_uuid,
            request_type_uuid,
        ]) => {
            const params = new URLSearchParams({
                [name_uuid] : 'My name',
                [email_uuid]: 'myemail@teclib.com',
                [age_uuid]: 29,
                [urgency_uuid] : 'very loW', // case insentive value
                [request_type_uuid]: 'reQuest', // // case insentive value
                [prefered_software_uuid]: 'I really like GLPI',
            });
            cy.visit(`/Form/Render/${form_id}?${params}`);
        });

        // Validate each values
        cy.findByRole('textbox', { 'name': 'Name' }).should('have.value', 'My name');
        cy.findByRole('textbox', { 'name': 'Email' }).should('have.value', 'myemail@teclib.com');
        cy.findByRole('spinbutton', { 'name': 'Age' }).should('have.value', '29');
        cy.findByLabelText("Prefered software").awaitTinyMCE().should('have.text', 'I really like GLPI');
        cy.getDropdownByLabelText('Urgency').should('have.text', "Very low");
        cy.getDropdownByLabelText('Request type').should('have.text', "Request");
    });

    it('Mandatory questions must be filled', () => {
        // Set up a form with two sections, each with a mandatory question
        cy.createWithAPI('Glpi\\Form\\Form', {
            'name': 'Test mandatory questions',
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

            // Add second section
            cy.createWithAPI('Glpi\\Form\\Section', {
                'name': 'Second section',
                'rank': 1,
                'forms_forms_id': form_id,
            }).as('second_section_id');

            cy.get('@second_section_id').then((second_section_id) => {
                // Add mandatory question to second section
                cy.createWithAPI('Glpi\\Form\\Question', {
                    'name': 'Second question',
                    'type': 'Glpi\\Form\\QuestionType\\QuestionTypeShortText',
                    'vertical_rank': 1,
                    'forms_sections_id': second_section_id,
                    'is_mandatory' : true,
                }).as('second_section_id');
            });

            // Preview form
            cy.login();
            cy.visit(`/Form/Render/${form_id}`);

            // Try to submit first section, should fail since we didn't answer
            // the mandatory question
            cy.findByRole('button', {name: 'Continue'}).click();
            checkMandatoryQuestion('First question');
            cy.findByRole('heading', {name: 'First section'}).should('be.visible');
            cy.findByRole('heading', {name: 'Second section'}).should('not.exist');

            // Submit again with a value
            cy.findByRole('textbox', {name: 'First question'}).type("test");
            cy.findByRole('button', {name: 'Continue'}).click();
            cy.findByRole('heading', {name: 'Second section'}).should('be.visible');
            cy.findByRole('heading', {name: 'First section'}).should('not.exist');

            // Try to submit the final section, should fail since we didn't answer
            // the mandatory question
            cy.findByRole('button', {name: 'Submit'}).click();
            checkMandatoryQuestion('Second question');
            cy.findByRole('heading', {name: 'Second section'}).should('be.visible');
            cy.findByText('Form submitted').should('not.be.visible');

            // Submit again with a value
            cy.findByRole('textbox', {name: 'Second question'}).type("test");
            cy.findByRole('button', {name: 'Submit'}).click();
            cy.findByRole('heading', {name: 'Second section'}).should('not.exist');
            cy.findByText('Form submitted').should('be.visible');
        });
    });
});

function addQuestionAndGetUuuid(name, type = null, subtype = null) {
    cy.findByRole('button', { 'name': 'Add a question' }).click();
    cy.focused().type(name);
    if (type !== null) {
        cy.getDropdownByLabelText('Question type').selectDropdownValue(type);
    }
    if (subtype !== null) {
        cy.getDropdownByLabelText('Question sub type').selectDropdownValue(subtype);
    }

    cy.findAllByRole('button', {'name': "More actions"}).last().click();
    cy.findByRole('button', {'name': "Copy uuid"}).click();

    cy.window()
        .its('navigator.clipboard')
        .invoke('readText')
        .then((text) => {
            cy.wrap(text).as(`${name} UUID`);
        })
    ;

    cy.findByRole('alert').should('contain.text', "UUID copied successfully to clipboard.");
    cy.findByRole('button', {'name': "Close"}).click();
    cy.findByRole('alert').should('not.exist');
}

function checkMandatoryQuestion(name) {
    cy.findByRole('textbox', { name })
        .should('have.attr', 'aria-invalid', 'true')
        .should('have.attr', 'aria-errormessage')
    ;
    getAriaErrorMessageElement(cy.findByRole('textbox', { name }))
        .should('contain.text', 'This field is mandatory')
    ;
}

function getAriaErrorMessageElement(element) {
    return element.invoke('attr', 'aria-errormessage').then((id) => cy.get(`#${id}`));
}
