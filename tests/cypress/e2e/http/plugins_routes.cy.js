/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

import {console_command} from '../../console_command';

describe('Plugin routes', () => {
    beforeEach(() => {
        cy.login();
    });

    it('returns 404 when plugin is not active', () => {
        cy.exec(`${console_command} plugin:deactivate --env=testing tester`, {failOnNonZeroExit: false});

        cy.visit(`/plugins/tester/plugin-test`, {failOnStatusCode: false}).then(res => {
            expect(res.performance.getEntriesByType('navigation')[0]?.responseStatus).to.eq(404);
        });
    });

    it('returns 200 when plugin is active', () => {
        cy.exec(`${console_command} plugin:install --env=testing tester --force -u glpi`, {failOnNonZeroExit: false})
            .then(res => {
                cy.log(`Install response status: ${res.code}\n\nSTDERR: ${res.stderr}\n\nSTDOUT: ${res.stdout}`);
            });

        cy.exec(`${console_command} plugin:activate --env=testing tester`, {failOnNonZeroExit: false})
            .then(res => {
                cy.log(`Activate response status: ${res.code}\n\nSTDERR: ${res.stderr}\n\nSTDOUT: ${res.stdout}`);
            });

        cy.visit(`/plugins/tester/plugin-test`, {failOnStatusCode: false}).then(res => {
            expect(res.performance.getEntriesByType('navigation')[0]?.responseStatus).to.eq(200);
        });

        cy.findByText('It works!').should('exist');
    });
});
