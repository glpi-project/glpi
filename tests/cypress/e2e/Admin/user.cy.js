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

describe('User form', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin');
    });
    it('Change my password field', () => {
        cy.visit('/front/preference.php');
        cy.get('.nav-item').contains('Main').click();
        cy.findByRole('button', { name: /Change password/ }).click();
        cy.url().should('include', '/front/updatepassword.php');
    });
    it('Change other password field', () => {
        // E2E user shouldn't have access to change password button since they exist in an entity below the "tech" user
        cy.visit('/front/user.form.php?id=4');
        cy.findByRole('tab', { name: 'User' }).click();
        cy.findByRole('button', { name: /Change password/ }).should('not.exist');
        // Need to log in as the default super-admin to have more permissions over the "tech" user to test they can see the change password button
        cy.login('glpi', 'glpi');
        cy.changeEntity(0, true);
        cy.visit('/front/user.form.php?id=4');
        cy.findByRole('tab', { name: 'User' }).click();
        cy.findByRole('button', { name: /Change password/ }).click();
        cy.findByRole('dialog').should('be.visible').within(() => {
            cy.findByLabelText('Password').should('be.visible').closest('form').should('exist');
            cy.findByLabelText('Confirm password').should('be.visible').closest('form').should('exist');
        });
    });
    it('Change user picture', () => {
        cy.visit('/front/user.form.php?id=7');
        cy.findByRole('tab', { name: 'User' }).click();
        cy.findByTitle('Change picture').click();
        cy.findByRole('dialog').should('be.visible').within(() => {
            cy.get('[data-current-avatar]').should('be.visible');
            cy.get('[data-default-avatar]').should('not.be.visible');
            cy.get('[data-preview-avatar]').should('not.be.visible');
            cy.get('input[type="file"]').selectFile('fixtures/uploads/foo.png');
            cy.get('[data-preview-avatar]').should('be.visible');
            cy.get('[data-default-avatar]').should('not.be.visible');
            cy.get('[data-current-avatar]').should('not.be.visible');
            cy.get('.fileupload .remove_file_upload').click();
            cy.get('[data-current-avatar]').should('be.visible');
            cy.get('[data-default-avatar]').should('not.be.visible');
            cy.get('[data-preview-avatar]').should('not.be.visible');
        });
    });
    it('can add emails and set one as default', () => {
        cy.visit('/front/preference.php');
        cy.get('.nav-item').contains('Main').click();

        // Add a email
        cy.findByRole('textbox', { name: 'Email address' }).should('be.visible').type('test@test.test');
        cy.findByRole('button', { name: /Save/ }).click();

        // Check if the email is added
        cy.findByRole('textbox', { name: 'Email address' }).should('have.value', 'test@test.test');
        cy.findByRole('radio', { name: 'Set as default email' }).should('be.checked');

        // Add another email
        cy.findByRole('generic', { name: 'Add a new Emails' }).click();
        cy.findAllByRole('textbox', { name: 'Email address' }).eq(1).should('be.visible').type('anothertest@test.test');
        cy.findByRole('button', { name: /Save/ }).click();

        // Check emails
        cy.findAllByRole('textbox', { name: 'Email address' }).eq(0).should('have.value', 'anothertest@test.test');
        cy.findAllByRole('textbox', { name: 'Email address' }).eq(1).should('have.value', 'test@test.test');
        cy.findAllByRole('radio', { name: 'Set as default email' }).eq(0).should('not.be.checked');
        cy.findAllByRole('radio', { name: 'Set as default email' }).eq(1).should('be.checked');
    });
    it('can add and remove my substitutes', () => {
        cy.visit('/front/preference.php');
        cy.findByRole('tab', { name: /Authorized substitutes/ }).click();
        cy.getDropdownByLabelText('Approval substitutes').selectDropdownValue('normal');
        cy.findByRole('button', { name: 'Save' }).click();
        cy.findByRole('tab', { name: /Authorized substitutes/ }).click();
        cy.getDropdownByLabelText('Approval substitutes').should('contain.text', 'normal');
        cy.getDropdownByLabelText('Approval substitutes').closest('.select2-container').find('.select2-selection__choice__remove').click();
        cy.findByRole('button', { name: 'Save' }).click();
        cy.findByRole('tab', { name: /Authorized substitutes/ }).click();
        cy.getDropdownByLabelText('Approval substitutes').should('have.text', '');
    });
});
