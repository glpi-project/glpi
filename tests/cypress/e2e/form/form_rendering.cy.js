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
