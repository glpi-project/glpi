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

describe('Ajax Controller', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin');
    });
    it('refresh tabs on update', () => {
        cy.createWithAPI('Glpi\\Form\\Form', {
            'name': '[Test] Ajax Controller: refresh tabs on update',
        }).then((form_id) => {
            // Go to form main tab
            const tab = 'Glpi\\Form\\Form$main';
            cy.visit(`/front/form/form.form.php?id=${form_id}&forcetab=${tab}`);

            // Load the history tab
            cy.findByRole('tab', {'name': "Historical 4"}).click();
            cy.findAllByRole('row').should('have.length', 5); // 4 entries + header
            cy.findByRole('tab', {'name': "Form"}).click();

            // Modify and save form
            cy.findByRole('checkbox', {'name': "Active"}).click();
            cy.findByRole('button', {'name': "Save"}).click();
            cy.findByRole('alert').should('exist').and(
                'contains.text',
                'Item successfully updated'
            );

            // Go to history tab, it must be updated with a new entry
            cy.findByRole('tab', {'name': "Historical 4"}).click();
            cy.findAllByRole('row').should('have.length', 6); // 5 entries + header
        });
    });
});
