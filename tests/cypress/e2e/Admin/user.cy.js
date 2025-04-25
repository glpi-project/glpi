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
});
