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

// Tests from this file seems to randomly fail 1/20 times.
// No one has been able to fix it yet, thus we are adding retries for now...
const config = {
    retries: {
        runMode: 2,
    },
};

describe('Form preview', config, () => {
    beforeEach(() => {
        cy.createWithAPI('Glpi\\Form\\Form', {
            'name': 'Test form preview',
        }).as('form_id');

        cy.login();
        cy.changeProfile('Super-Admin');

        cy.get('@form_id').then((form_id) => {
            const tab = 'Glpi\\Form\\Form$1';
            cy.visit(`/front/form/form.form.php?id=${form_id}&forcetab=${tab}`);
        });
    });

    function checkPreviewButton() {
        // Check the preview button
        cy.findByRole('button', { 'name': 'Preview' }).should('not.exist');
        cy.findByRole('button', { 'name': 'Save and preview' }).should('exist');

        // Save the form and check the preview button
        cy.findByRole('button', { 'name': 'Save' }).click();
        cy.findByRole('alert')
            .should('contain.text', 'Item successfully updated')
            .within(() => {
                cy.findByRole('button', { 'name': 'Close' }).click();
            });
        cy.findByRole('link', { 'name': 'Save and preview' }).should('not.exist');
        cy.findByRole('link', { 'name': 'Preview' }).should('exist');
    }

    /**
     * Test form preview unsaved changes handling in form header
     * This test case checks the behavior of the form preview when there are unsaved changes in the form header.
     */
    it('Test form preview unsaved changes handling in form header', () => {
        cy.findByRole('textbox', { 'name': 'Form name' }).type('Test form');
        cy.findByRole('textbox', { 'name': 'Form name' }).blur();
        checkPreviewButton();

        cy.findByLabelText('Form description')
            .awaitTinyMCE()
            .type('My form description');

        // Click on another field to save the TinyMCE content
        cy.findByRole('textbox', { 'name': 'Form name' }).click();

        checkPreviewButton();
    });

    /**
    * Test form preview unsaved changes handling in sections
    * This test case checks the behavior of the form preview when there are unsaved changes in the sections.
    */
    it('Test form preview unsaved changes handling in sections', () => {
        // Add a question
        cy.findByRole('button', { 'name': 'Add a question' }).click();
        checkPreviewButton();

        // Focus question
        cy.findByRole('textbox', { 'name': 'Question name' }).click();

        // Add a section
        cy.findByRole('button', { 'name': 'Add a section' }).click();
        checkPreviewButton();

        cy.findAllByRole('region', { 'name': 'Form section' }).first().within(() => {
            // Set the section name
            cy.findByRole('textbox', { 'name': 'Section name' }).clear();
            cy.findByRole('textbox', { 'name': 'Section name' }).type('Test section');
            cy.findByRole('textbox', { 'name': 'Section name' }).blur();
        });
        checkPreviewButton();

        cy.findAllByRole('region', { 'name': 'Form section' }).first().within(() => {
            // Set the section description
            cy.findByLabelText('Section description')
                .awaitTinyMCE()
                .type('My section description');

            // Click on another field to save the TinyMCE content
            cy.findByRole('textbox', { 'name': 'Section name' }).click();
        });
        checkPreviewButton();

        cy.findAllByRole('region', { 'name': 'Form section' }).eq(1).within(() => {
            // Set the section description
            cy.findByRole('button', { 'name': 'More actions' }).click();
            cy.findByRole('button', { 'name': 'Merge with previous section' }).click();
        });
        checkPreviewButton();
    });

    /**
     * Test form preview unsaved changes handling in questions
     * This test case checks the behavior of the form preview when there are unsaved changes in the questions.
     */
    it('Test form preview unsaved changes handling in questions', () => {
        const check = () => {
            checkPreviewButton();

            // Focus the question
            cy.findByRole('textbox', { 'name': 'Question name' }).click();
        };

        // Add a question
        cy.findByRole('button', { 'name': 'Add a question' }).click();
        check();

        // Edit the question name
        cy.findByRole('textbox', { 'name': 'Question name' }).type('Test question');
        cy.findByRole('textbox', { 'name': 'Question name' }).blur();
        check();

        cy.findByRole('region', { 'name': 'Question details' }).within(() => {
            // Type the question description
            cy.findByLabelText("Question description")
                .awaitTinyMCE()
                .type("My question description");
        });

        // Click on another field to save the TinyMCE content
        cy.findByRole('textbox', { 'name': 'Question name' }).click();

        check();

        cy.findByRole('region', { 'name': 'Question details' }).within(() => {
            // Check the mandatory checkbox
            cy.findByRole('checkbox', { 'name': 'Mandatory' })
                .should('not.be.checked')
                .check();
        });
        check();

        // Change the question type
        cy.findByRole('option', {'name': 'New question'}).changeQuestionType('Short answer').changeQuestionSubType('Emails');
        check();

        // Change the category question type
        cy.findByRole('option', {'name': 'New question'}).changeQuestionType('Long answer');
        check();
    });

    /**
     * Test form preview unsaved changes handling in comments
     * This test case checks the behavior of the form preview when there are unsaved changes in the comments.
     */
    it('Test form preview unsaved changes handling in comments', () => {
        const check = () => {
            checkPreviewButton();

            // Focus the comment
            cy.findByRole('textbox', { 'name': 'Comment title' }).click();
        };

        // Add a comment
        cy.findByRole('button', { 'name': 'Add a comment' }).click();

        // Edit the comment name
        cy.findByRole('textbox', { 'name': 'Comment title' }).type('Test comment');
        cy.findByRole('textbox', { 'name': 'Comment title' }).blur();
        check();

        cy.findByRole('region', { 'name': 'Comment details' }).within(() => {
            // Type the comment description
            cy.findByLabelText("Comment description")
                .awaitTinyMCE()
                .type("My comment description");
        });

        // Click on another field to save the TinyMCE content
        cy.findByRole('textbox', { 'name': 'Comment title' }).click();
        check();
    });
});
