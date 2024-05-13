/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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
        cy.changeProfile('Super-Admin', true);

        cy.createWithAPI('Glpi\\Form\\Form', {
            'name': '[Tests] Access Control',
        }).then((form_id) => {
            const tab = 'Glpi\\Form\\AccessControl\\FormAccessControl$1';
            cy.visit(`/front/form/form.form.php?id=${form_id}&forcetab=${tab}`);
        });
    });
    it('warnings are displayed', () => {
        // Quick tests to ensure that warnings are rendered correcly by twig.
        // We don't check their exact content as it is already validated by unit tests.
        cy.findAllByRole('alert').should('have.length', 2);
    });
    it('can configure the global access stategy', () => {
        // Ensure the value we want to test is not already selected as it
        // would make the test pointless
        cy.findByLabelText("Access strategy ?")
            .find('option:selected', {force: true})
            .should('not.have.value', 'affirmative')
        ;

        // Update value and refresh page
        cy.findByLabelText("Access strategy ?")
            .select('affirmative', {force: true})
        ;
        cy.findByRole('button', {name: 'Save changes'}).click();
        cy.findByRole('alert').should('contain.text', 'Item successfully updated');

        // Check value again
        cy.findByLabelText("Access strategy ?")
            .find('option:selected', {force: true})
            .should('have.value', 'affirmative')
        ;
    }),
    it('can configure the allow list policy', () => {
        cy.findByRole('region', {
            name: 'Allow specifics users, groups or profiles'
        }).within(() => {
            cy.findByRole('checkbox', {name: 'Active'})
                .should('not.be.checked')
                .click()
            ;
        });

        // TODO: modify the user/group/profile dropdown (unsure how to tests
        // select2 ajax dropdown for now)

        // Save changes
        cy.findByRole('button', {name: 'Save changes'}).click();
        cy.findByRole('alert').should('contain.text', 'Item successfully updated');

        cy.findByRole('region', {
            name: 'Allow specifics users, groups or profiles'
        }).within(() => {
            // Check values are kept after update
            cy.findByRole('checkbox', {name: 'Active'}).should('be.checked');
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
        cy.findByRole('alert').should('contain.text', 'Item successfully updated');

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
