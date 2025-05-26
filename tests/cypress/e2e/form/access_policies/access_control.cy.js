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

describe('Access Control', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin');

        cy.createWithAPI('Glpi\\Form\\Form', {
            'name': '[Tests] Access Control',
            '_init_access_policies': false,
        }).then((form_id) => {
            const tab = 'Glpi\\Form\\AccessControl\\FormAccessControl$1';
            cy.visit(`/front/form/form.form.php?id=${form_id}&forcetab=${tab}`);
        });
    });
    it('warnings are displayed', () => {
        // Quick tests to ensure that warnings are rendered correcly by twig.
        // We don't check their exact content as it is already validated by unit tests.
        cy.findAllByRole('alert').eq(0).should('contain.text', "This form is not visible to anyone because it is not active.");
        cy.findAllByRole('alert').eq(1).should('contain.text', "This form will not be visible to any users as there are currently no active access policies.");
    });
    it('can configure the allow list policy', () => {
        // Change values
        cy.findByRole('region', {
            name: 'Allow specifics users, groups or profiles'
        }).within(() => {
            cy.findByRole('checkbox', {name: 'Active'})
                .should('not.be.checked')
                .click()
            ;
        });
        cy.getDropdownByLabelText('Allow specifics users, groups or profiles').selectDropdownValue('All users');
        cy.findByRole('link', {'name': /There are \d+ user\(s\) matching these criteria\./}).should('exist');

        // Save changes
        cy.findByRole('button', {name: 'Save changes'}).click();

        // Check values are kept after update
        cy.findByRole('region', {
            name: 'Allow specifics users, groups or profiles'
        }).within(() => {
            cy.findByRole('checkbox', {name: 'Active'}).should('be.checked');
            cy.getDropdownByLabelText('Allow specifics users, groups or profiles').should(
                'contain.text',
                'All users'
            );
        });
    });
    it('can configure the direct access policy', () => {
        cy.findByRole('region', {
            name: 'Allow direct access'
        }).within(() => {
            cy.findByRole('checkbox', {name: 'Active'})
                .should('not.be.checked')
                .click()
            ;
            cy.findByRole('checkbox', {name: 'Allow unauthenticated users ?'})
                .should('not.be.checked')
                .click()
            ;
        });

        // Save changes
        cy.findByRole('button', {name: 'Save changes'}).click();

        cy.findByRole('region', {
            name: 'Allow direct access'
        }).within(() => {
            // Check values are kept after update
            cy.findByRole('checkbox', {name: 'Active'}).should('be.checked');
            cy.findByRole('checkbox', {
                name: 'Allow unauthenticated users ?'
            }).should('be.checked');

            // Make sure link can be copied to clipboard
            cy.findByLabelText("Click to copy to clipboard").click();
            cy.window().then((win) => {
                win.navigator.clipboard.readText().then((text) => {
                    expect(text).to.contains('token=');
                });
            });
        });
    });
    it('activate policy when any input is modified', () => {
        cy.findByRole('region', {
            name: 'Allow direct access'
        }).within(() => {
            cy.findByRole('checkbox', {name: 'Active'})
                .should('not.be.checked')
            ;
            cy.findByRole('checkbox', {name: 'Allow unauthenticated users ?'})
                .should('not.be.checked')
                .click()
            ;
            cy.findByRole('checkbox', {name: 'Active'})
                .should('be.checked')
            ;
        });
    });
});
