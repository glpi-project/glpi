/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

describe(`Helpdesk home page configuration - entities specific`, () => {
    beforeEach(() => {
        cy.login();
        cy.createWithAPI("Entity", {
            'name': `Entity for e2e tests ${(new Date()).getTime()}`,
            'entities_id': 1, // E2ETestEntity
        }).as('entityId').then((id) => {
            cy.visit(`/front/entity.form.php?id=${id}&forcetab=Entity$9`);
        });
    });

    it('tiles with deleted form are not displayed', () => {
        const uuid = Cypress._.random(0, 1e6);

        // Create a form
        cy.get('@entityId').then((entityId) => {
            cy.createFormWithAPI({
                'name': `Test form - ${uuid}`,
                'is_active': true,
                'entities_id': entityId,
            }).as('formId');
        });

        // Create a form tile
        cy.findByRole('button', {
            name: "Define tiles for this entity from scratch"
        }).click();
        cy.findByRole('button', {name: "Add tile"}).click();
        cy.getDropdownByLabelText('Type').selectDropdownValue('Form');
        cy.getDropdownByLabelText('Target form').selectDropdownValue(`Test form - ${uuid}`);
        cy.findByRole('dialog').findByRole('button', {name: 'Add tile'}).click();
        cy.findAllByRole('alert')
            .contains("Configuration updated successfully.")
            .should('be.visible')
        ;

        // Save the configuration
        cy.findByRole('button', {name: "Save tiles order"}).click();
        cy.findAllByRole('alert')
            .contains("Configuration updated successfully.")
            .should('be.visible')
        ;

        // Validate the tile is displayed
        cy.changeProfile('Self-Service');
        cy.get('@entityId').then((entityId) => cy.changeEntity(entityId));
        cy.visit('/Helpdesk');
        cy.findByRole('heading', {name: `Test form - ${uuid}`}).should('be.visible');

        // Delete the form
        cy.changeProfile('Super-Admin');
        cy.get('@formId').then(formId => {
            // Visit the form to ensure it exists
            cy.visit(`front/form/form.form.php?id=${formId}`);
        });
        cy.findByRole('button', {'name': 'Put in trashbin'}).click();
        cy.findByRole('alert').should('contain.text', 'Item successfully deleted');

        // Go back to the self-service page
        cy.changeProfile('Self-Service');
        cy.get('@entityId').then((entityId) => cy.changeEntity(entityId));
        cy.visit('/Helpdesk');

        // Validate the tile is not displayed anymore
        cy.findByRole('heading', {name: `Test form - ${uuid}`}).should('not.exist');

        // Purge the form
        cy.changeProfile('Super-Admin');
        cy.get('@formId').then(formId => {
            // Visit the form to ensure it exists
            cy.visit(`front/form/form.form.php?id=${formId}`);
        });
        cy.findByRole('button', {'name': 'Delete permanently'}).click();
        cy.findByRole('alert').should('contain.text', 'Item successfully purged');

        // Go back to the self-service page
        cy.changeProfile('Self-Service');
        cy.get('@entityId').then((entityId) => cy.changeEntity(entityId));
        cy.visit('/Helpdesk');

        // Validate the tile is not displayed anymore
        cy.findByRole('heading', {name: `Test form - ${uuid}`}).should('not.exist');
    });
});
