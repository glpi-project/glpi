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

describe('Default forms', () => {
    it('can fill and submit the incident form', () => {
        // Go to form
        cy.login();
        cy.visit("/Form/Render/1");

        // Fill form
        cy.getDropdownByLabelText('Urgency').selectDropdownValue('High');
        cy.findByRole('textbox', {'name': "Title"}).type("My title");
        cy.findByLabelText("Description").awaitTinyMCE().type("My description");

        // Submit form
        cy.findByRole('button', {'name': "Send form"}).click();
        cy.findByRole('alert')
            .should('contain.text', 'Item successfully created')
        ;

        // Validate ticket values using API
        cy.findByRole('alert')
            .findByRole('link')
            .invoke("attr", "href")
            .then((href) => {
                const id = /\?id=(.*)/.exec(href)[1];
                cy.getWithAPI('Ticket', id).then((fields) => {
                    expect(fields.urgency).to.equal(4);
                    expect(fields.name).to.equal('My title');
                    expect(fields.content).to.equal('<p>My description</p>');
                });
            })
        ;
    });

    it('can fill and submit the service form', () => {
        // Go to form
        cy.login();
        cy.visit("/Form/Render/2");

        // Fill form
        cy.getDropdownByLabelText('Urgency').selectDropdownValue('High');
        cy.findByRole('textbox', {'name': "Title"}).type("My title");
        cy.findByLabelText("Description").awaitTinyMCE().type("My description");

        // Submit form
        cy.findByRole('button', {'name': "Send form"}).click();
        cy.findByRole('alert')
            .should('contain.text', 'Item successfully created')
        ;

        // Validate ticket values using API
        cy.findByRole('alert')
            .findByRole('link')
            .invoke("attr", "href")
            .then((href) => {
                const id = /\?id=(.*)/.exec(href)[1];
                cy.getWithAPI('Ticket', id).then((fields) => {
                    expect(fields.urgency).to.equal(4);
                    expect(fields.name).to.equal('My title');
                    expect(fields.content).to.equal('<p>My description</p>');
                });
            })
        ;
    });
});
