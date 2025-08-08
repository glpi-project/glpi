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

describe('Reminders', () => {
    beforeEach(() => {
        cy.login();
    });

    it('Form loads correctly', () => {
        cy.visit('/front/reminder.form.php');
        cy.findByRole('tabpanel').within(() => {
            cy.findByLabelText('Title').should('have.value', 'New note');
            cy.get('input[name="begin_view_date"]').should('exist');
            cy.get('input[name="end_view_date"]').should('exist');
            cy.get('input[name="plan[begin]"]').should('not.exist');
            cy.get('select[name="plan[_duration]"]').should('not.exist');
            cy.findByLabelText('Description').awaitTinyMCE().should('be.visible');
            cy.findByRole('button', { name: 'Add to schedule'}).click();
            cy.get('input[name="plan[begin]"]').should('exist');
            cy.get('select[name="plan[_duration]"]').should('be.visible');
        });
    });
});
