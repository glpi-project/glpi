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

describe('Error page', () => {
    beforeEach(() => {
        cy.login();

        // prevent race conditions with ajax callbacks and speed up execution
        cy.intercept({path: '/ajax/debug.php**'}, { statusCode: 404, body: '' });
    });

    it('Displays a bad request error', () => {
        cy.changeProfile('Super-Admin');

        const urls = [
            '/front/impactcsv.php', // streamed response
            '/Form/Render/0',       // modern controller
        ];

        const expected_code    = 400;
        const expected_message = 'Invalid request parameters.';

        // Check with debug mode (stack trace should be displayed)
        cy.enableDebugMode();
        for (const url of urls) {
            cy.visit({
                url: url,
                failOnStatusCode: false,
            }).then((response) => {
                expect(response.performance.getEntriesByType('navigation')[0].responseStatus).to.eq(expected_code);
                cy.findByRole('alert').should('contain.text', expected_message);
                cy.findByTestId('stack-trace').should('exist');
            });
        }

        // Check without debug mode (stack trace should NOT be displayed)
        cy.disableDebugMode();
        for (const url of urls) {
            cy.visit({
                url: url,
                failOnStatusCode: false,
            }).then((response) => {
                expect(response.performance.getEntriesByType('navigation')[0].responseStatus).to.eq(expected_code);
                cy.findByRole('alert').should('contain.text', expected_message);
                cy.findByTestId('stack-trace').should('not.exist');
            });
        }
    });

    it('Displays an access denied error', () => {
        cy.changeProfile('Self-Service');

        const urls = [
            '/front/central.php',  // streamed response
            '/front/computer.php', // generic controller
            '/Form/Import',        // modern controller
        ];

        const expected_code    = 403;
        const expected_message = 'You don\'t have permission to perform this action.';

        // Cannot test the debug mode with Self-Service profile

        // Check without debug mode (stack trace should NOT be displayed)
        for (const url of urls) {
            cy.visit({
                url: url,
                failOnStatusCode: false,
            }).then((response) => {
                expect(response.performance.getEntriesByType('navigation')[0].responseStatus).to.eq(expected_code);
                cy.findByRole('alert').should('contain.text', expected_message);
                cy.findByTestId('stack-trace').should('not.exist');
            });
        }
    });

    it('Displays a not found error', () => {
        cy.changeProfile('Super-Admin');

        const urls = [
            '/front/logviewer.php?filepath=invalid', // streamed response
            '/front/computer.form.php?id=999999',    // generic response
            '/Form/Render/999999',                   // modern controller
        ];

        const expected_code    = 404;
        const expected_message = 'The requested item has not been found.';

        // Check with debug mode (stack trace should be displayed)
        cy.enableDebugMode();
        for (const url of urls) {
            cy.visit({
                url: url,
                failOnStatusCode: false,
            }).then((response) => {
                expect(response.performance.getEntriesByType('navigation')[0].responseStatus).to.eq(expected_code);
                cy.findByRole('alert').should('contain.text', expected_message);
                cy.findByTestId('stack-trace').should('exist');
            });
        }
        cy.disableDebugMode();

        // Check without debug mode (stack trace should NOT be displayed)
        for (const url of urls) {
            cy.visit({
                url: url,
                failOnStatusCode: false,
            }).then((response) => {
                expect(response.performance.getEntriesByType('navigation')[0].responseStatus).to.eq(expected_code);
                cy.findByRole('alert').should('contain.text', expected_message);
                cy.findByTestId('stack-trace').should('not.exist');
            });
        }
    });
});
