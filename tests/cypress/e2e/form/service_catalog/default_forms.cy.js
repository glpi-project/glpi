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

const uuid = Date.now();

before(() => {
    cy.createWithAPI('ITILCategory', {
        'name': `Test ITILCategory - ${uuid}`,
    });
    cy.createWithAPI('Computer', {
        'name': `Test Computer - ${uuid}`,
        'users_id': 7,
    });
    cy.createWithAPI('Location', {
        'name': `Test Location - ${uuid}`,
    });
});

function testDefaultForm({ profile, formId }) {
    cy.login();
    cy.changeProfile(profile);

    cy.visit(`/Form/Render/${formId}`);
    cy.getDropdownByLabelText('Urgency').selectDropdownValue('High');
    cy.getDropdownByLabelText('Category').selectDropdownValue(`»Test ITILCategory - ${uuid}`);
    cy.getDropdownByLabelText('User devices').selectDropdownValue(`Computers - Test Computer - ${uuid}`);
    cy.getDropdownByLabelText('Observers').selectDropdownValue('glpi');
    cy.getDropdownByLabelText('Location').selectDropdownValue(`»Test Location - ${uuid}`);
    cy.findByRole('textbox', { name: "Title" }).type("My title");
    cy.findByLabelText("Description").awaitTinyMCE().type("My description");

    cy.findByRole('button', { name: "Submit" }).click();
    cy.findByRole('alert').should('contain.text', 'Item successfully created');

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
        });
}

describe('Default forms', () => {
    [
        { profile: 'Super-Admin', formId: 1 },
        { profile: 'Self-Service', formId: 1 },
        { profile: 'Super-Admin', formId: 2 },
        { profile: 'Self-Service', formId: 2 }
    ].forEach((scenario) => {
        it(`can fill and submit form ${scenario.formId} as ${scenario.profile}`, () => {
            testDefaultForm(scenario);
        });
    });
});
