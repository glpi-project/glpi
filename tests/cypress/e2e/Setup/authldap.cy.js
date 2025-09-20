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

describe('LDAP Integration', () => {
    before(() => {
        cy.glpiAPIRequest({
            method: 'DELETE',
            endpoint: 'Administration/User/username/walid?force=1',
            allow_failure: true,
            username: 'glpi', // Need access to root entity
        });
        cy.initApi().updateWithAPI('AuthLDAP', 1,{
            'is_default': 1,
            'is_active': 1,
        });
    });
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin');
    });
    after(() => {
        cy.initApi().updateWithAPI('AuthLDAP', 1,{
            'is_active': 0,
        });
    });

    ['Import', 'Sync'].forEach((action) => {
        const easy_mode_fields = ['Login', 'Email', 'Surname', 'First name', 'Phone', 'Title', 'Category'];
        const expert_mode_fields = ['BaseDN', 'Search filter for users'];

        it(`${action} Users`, () => {
            cy.visit('/front/user.php');
            cy.findByRole('link', { name: 'LDAP directory link' }).click();
            cy.findByRole('link', { name: action === 'Import' ? 'Import new users' : 'Synchronizing already imported users'}).click();
            cy.findByLabelText('Simple mode').should('exist');
            easy_mode_fields.forEach((field) => {
                cy.findByLabelText(field).should('be.visible');
            });
            expert_mode_fields.forEach((field) => {
                cy.findByLabelText(field).should('not.be.visible');
            });

            if (action === 'Import') {
                cy.findByRole('button', {name: 'Search'}).click();
                cy.findAllByRole('row').filter('[data-itemtype="AuthLDAP"]').should('have.length.at.least', 5);
            }
            cy.findByLabelText('Login').invoke('val', 'walid');
            cy.findByRole('button', { name: 'Search' }).click();
            cy.findAllByRole('row').filter('[data-itemtype="AuthLDAP"]').contains('walid').should('have.length', 1);
            if (action === 'Import') {
                cy.findAllByRole('row').filter('[data-itemtype="AuthLDAP"]').then((r) => {
                    cy.wrap(r[0]).findByRole('checkbox').click();
                    cy.findByRole('button', {name: 'Actions'}).click();
                    cy.getDropdownByLabelText('Action').selectDropdownValue('Import');
                    cy.findByRole('button', {name: 'Post'}).click();
                    cy.findByRole('alert').should('contain.text', 'Item successfully added');
                });
            } else {
                cy.findAllByRole('row').filter('[data-itemtype="AuthLDAP"]').then((r) => {
                    cy.wrap(r[0]).findByRole('checkbox').click();
                    cy.findByRole('button', {name: 'Actions'}).click();
                    cy.getDropdownByLabelText('Action').selectDropdownValue('Synchronize');
                    cy.findByRole('button', {name: 'Post'}).click();
                    cy.findByRole('alert').should('contain.text', 'Item successfully updated');
                });
            }

            cy.findByLabelText('Simple mode').click();
            cy.findByLabelText('Simple mode').should('not.exist');
            cy.findByLabelText('Expert mode').should('exist');
            easy_mode_fields.forEach((field) => {
                cy.findByLabelText(field).should('not.be.visible');
            });
            expert_mode_fields.forEach((field) => {
                cy.findByLabelText(field).should('be.visible');
            });

            cy.findByLabelText('Search filter for users').invoke('val', '(& (uid=*xavier*) (objectclass=inetOrgPerson))');
            cy.findByRole('button', { name: 'Search' }).click();
            if (action === 'Import') {
                cy.findAllByRole('row').filter('[data-itemtype="AuthLDAP"]').contains('xavier').should('have.length', 1);
            } else {
                cy.findAllByRole('row').filter('[data-itemtype="AuthLDAP"]').should('not.exist');
            }
        });

        it (`${action} UI with no default server`, () => {
            cy.initApi().updateWithAPI('AuthLDAP', 1, {
                'is_default': 0,
            }).then(() => {
                cy.visit(`/front/ldap.import.php?mode=${action === 'Import' ? 0 : 1}&action=show`);
                cy.findByRole('button', {name: 'Search'}).click();
                if (action === 'Import') {
                    cy.findAllByRole('row').filter('[data-itemtype="AuthLDAP"]').should('have.length.at.least', 5);
                } else {
                    cy.findAllByRole('row').filter('[data-itemtype="AuthLDAP"]').contains('walid').should('have.length', 1);
                }
            });
        });
    });

    it('Test', () => {
        cy.visit('/front/authldap.form.php?id=1');
        cy.findByRole('tab', { name: 'Test' }).click();
        cy.findByRole('tabpanel').within(() => {
            cy.findAllByRole('listitem').should('have.length', 5);
            cy.findAllByRole('listitem').each((item) => {
                cy.wrap(item).find('.h4').should('be.visible');
                cy.wrap(item).find(':not(.h4)').should('have.class', 'text-success');
            });
        });
    });
});
