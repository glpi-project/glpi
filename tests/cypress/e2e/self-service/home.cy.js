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

describe('Helpdesk home page', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Self-Service');
    });
    it('can use tiles', () => {
        cy.visit('/Helpdesk');
        cy.findByRole('region', { name: 'Quick Access' })
            .findAllByRole('link')
            .as('tiles');

        // Each links must lead to a valid page (status code 200)
        cy.get('@tiles').each(($tile) => {
            cy.request({
                url: $tile.attr('href'),
                failOnStatusCode: true,
            });
        });
    });

    it('can use tabs', () => {
        cy.visit('/Home');

        // Default tab should be opened tickets
        cy.findByRole('tabpanel').within(() => {
            cy.get('form[data-search-itemtype="Ticket"]').should('be.visible');
            cy.findByRole('columnheader', {'name': 'Status'}).should('be.visible');
            // None of the status cells should contain 'Solved' or 'Closed'
            cy.get('td[data-searchopt-content-id="12"]').should('be.visible').invoke('text').should('not.match', /Solved|Closed/);
        });

        // Got to closed tickets tab
        cy.findByRole('tab', {'name': 'Solved tickets'}).click();
        cy.findByRole('tabpanel').within(() => {
            cy.get('form[data-search-itemtype="Ticket"]').should('be.visible');
            cy.findByRole('columnheader', {'name': 'Status'}).should('be.visible');
            // The status cells should contain 'Solved' or 'Closed' only
            cy.get('td[data-searchopt-content-id="12"]').should('be.visible').invoke('text').should('match', /Solved|Closed/);
        });

        // Got to Reminder Feed tab
        cy.findByRole('tab', {'name': 'Reminders'}).click();
        cy.findByRole('columnheader', {'name': 'Public reminders'}).should('be.visible');
        cy.findAllByRole('link', {'name': 'Public reminder 1'}).should('be.visible');

        // Return to main tab, make it easier to re-run the test as the last tab
        // is kept in the session
        cy.findByRole('tab', {'name': 'Ongoing tickets'}).click();

        // RSS feeds are not tested as they are only displayed if a real feed
        // is configurated. Since the query to the feed is done on the backend,
        // we can't mock it here.
        // Could be added if we don't mind relying on a real outside feeds for
        // ours tests or if we setup a dedicated container for this.
        cy.findByRole('tab', {'name': 'RSS feeds'}).click();
        cy.findByRole('columnheader', {'name': 'Public RSS feeds'}).should('be.visible');
    });
});
