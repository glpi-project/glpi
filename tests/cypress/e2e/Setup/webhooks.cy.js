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

describe('Webhooks', () => {
    let webhook_id;

    before(() => {
        cy.initApi().createWithAPI('Webhook', {
            name: 'New computer',
            entities_id: 1,
            itemtype: 'Computer',
            event: 'new',
        }).then(id => webhook_id = id);
    });
    beforeEach(() => {
        cy.login();
    });

    it('Payload editor switches', {retries: 0}, () => {
        // Test with no retries as this test was added in response to a race condition. It should always pass.
        cy.visit(`/front/webhook.form.php?id=${webhook_id}`);
        cy.findByRole('tab', { name: 'Payload editor' }).click();
        cy.findByRole('tabpanel').within(() => {
            cy.findByRole('button', { name: 'Save' }).should('be.visible');
            cy.findByRole('button', { name: 'Search' }).should('not.exist');
            cy.get('textarea[name="default_payload"]').should('be.visible').and('have.attr', 'readonly');
            cy.get('#payload').should('not.be.visible');

            cy.findByLabelText('Use default payload').click();
            cy.findByRole('button', { name: 'Save' }).should('be.visible');
            cy.findByRole('button', { name: 'Search' }).should('be.visible');
            cy.get('textarea[name="default_payload"]').should('not.be.visible');
            cy.get('#payload').should('be.visible');
            // Ensure the monaco editor has a usable size. If initialized with a hidden parent, it can have a size less then 10px by 10px.
            cy.get('#payload .monaco-editor').invoke('outerWidth').should('be.gte', 100);
            cy.get('#payload .monaco-editor').invoke('outerHeight').should('be.gte', 100);
        });
    });
});
