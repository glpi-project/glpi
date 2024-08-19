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

describe('Error controller', () => {
    const front_path = '../front';
    const fixtures_path = 'fixtures/example_error_files';

    before(() => {
        cy.exec(`${console_command} plugin:install --env=testing tester --force -u glpi`, {failOnNonZeroExit: false});
        cy.exec(`${console_command} plugin:activate --env=testing tester`, {failOnNonZeroExit: false});

        cy.exec(`mkdir -p "${front_path}/testing/"`);
        cy.exec(`cp ${fixtures_path}/* "${front_path}/testing/"`);
    });

    after(() => {
        cy.exec(`${console_command} plugin:deactivate --env=testing tester`, {failOnNonZeroExit: false});

        cy.exec(`rm -rf "${front_path}/testing"`);
    });

    beforeEach(() => {
        cy.login();
    });

    it('displays warning message', () => {
        cy.visit(`/front/testing/error_warning.php`, {failOnStatusCode: false}).then(res => {
            expect(res.performance.getEntriesByType('navigation')[0]?.responseStatus).to.eq(200);
        });

        cy.findByRole('main').findByText('Example page to trigger a PHP Warning error.').should('exist');
    });

    it('displays triggered error message', () => {
        cy.visit(`/front/testing/error_trigger.php`, {failOnStatusCode: false}).then(res => {
            expect(res.performance.getEntriesByType('navigation')[0]?.responseStatus).to.eq(500);
        });

        cy.findByRole('alert').should('contain.text', 'User Error: Error triggered');
    });

    it('displays thrown exception message', () => {
        cy.visit(`/front/testing/error_exception.php`, {failOnStatusCode: false}).then(res => {
            expect(res.performance.getEntriesByType('navigation')[0]?.responseStatus).to.eq(500);
        });

        cy.findByRole('alert').should('contain.text', 'Exception triggered');
    });

    it('displays parse error message', () => {
        cy.visit(`/front/testing/error_parse.php`, {failOnStatusCode: false}).then(res => {
            expect(res.performance.getEntriesByType('navigation')[0]?.responseStatus).to.eq(500);
        });

        cy.findByRole('alert').should('contain.text', 'syntax error, unexpected end of file');
    });
});
