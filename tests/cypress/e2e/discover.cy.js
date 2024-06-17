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
describe('Discover', () => {
    beforeEach(() => {
        // Reset the discover progression
        cy.resetDiscoverProgression()

        cy.login();

        // Intercept all requests and add the header 'load-discover'
        cy.intercept('*', (req) => {
            req.headers['load-discover'] = 'true';
        });

        cy.changeProfile('Super-Admin', true);
    });
    it('can view the default discover lesson', () => {
        cy.findByRole('dialog').should('exist').within(() => {
            // Check the dialog title
            cy.findByRole('heading', { name: 'Introduction' }).should('exist');

            cy.findByRole('button', { name: 'Next' }).click();
            cy.findByRole('button', { name: 'Next' }).click();
            cy.findByRole('button', { name: 'Next' }).click();
            cy.findByRole('button', { name: 'Done' }).click();
        });

        cy.findByRole('dialog').should('exist').within(() => {
            cy.findByRole('button', { name: 'Done' }).click();
        });
    });
});
