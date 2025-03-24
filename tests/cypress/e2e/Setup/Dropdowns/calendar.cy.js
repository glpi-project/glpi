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

describe('Calendar', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin');
    });
    it('Time range form loads', () => {
        // Go to the default calendar
        cy.visit('/front/calendar.form.php?id=1&forcetab=CalendarSegment$1');

        cy.get('form[name=asset_form]').within(() => {
            cy.findByLabelText('Day').should('be.visible');
            cy.findByLabelText('Start').should('be.visible');
            cy.findByLabelText('End').should('be.visible');
            cy.findByRole('button', { name: 'Add' }).should('be.visible');
        });
    });
});
