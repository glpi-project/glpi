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

const settings_presets = [
    {'show_search_form': 1},
    // Default setting, keep at the end so the default state is applied when
    // the tests are finished.
    {'show_search_form': 0},
];

for (const [i, settings] of settings_presets.entries()) {
    describe(`Search toolbar with settings preset #${i}`, () => {
        before(() => {
            // Set settings before loggin in to make sure they are taken into account.
            cy.updateTestUserSettings(settings);
        });

        beforeEach(() => {
            cy.login();
            cy.changeProfile('Super-Admin');
        });

        it(`can toggle the trashbin`, () => {
            // Go to the a search page that support the "trashbin" feature,
            // should be toggled off by default.
            cy.visit('/front/computer.php');
            cy.findByTestId('search-results').should('be.visible');
            cy.findByTestId('search-results-trashbin').should('not.exist');

            // Go to trashbin
            cy.findByRole('button', {name: "Show the trashbin"}).click();
            cy.findByTestId('search-results').should('not.exist');
            cy.findByTestId('search-results-trashbin').should('be.visible');
        });

        it(`can toggle the categories tree`, () => {
            // Go to the a search page that support the "browse mode" feature,
            // should be toggled off by default.
            cy.visit('/front/user.php');
            cy.findByTestId('tree-browse').should('not.exist');

            // Toggle "browse mode"
            cy.findByRole('button', {name: "Toggle browse"}).click();
            cy.findByTestId('tree-browse').should('be.visible');
        });

        it(`can toggle unpublished items`, () => {
            // Go to the a search page that support the "unpublished" feature,
            // should be toggled off by default.
            cy.visit('/front/knowbaseitem.php?forcetab=Knowbase$2');
            cy.findByTestId('unpublished-on').should('be.visible');
            cy.findByTestId('unpublished-off').should('not.exist');

            // Show unpublished items
            cy.findByRole('button', {name: "Show unpublished"}).click();
            cy.findByTestId('unpublished-on').should('not.exist');
            cy.findByTestId('unpublished-off').should('be.visible');
        });

        it(`can toggle between map and table views`, () => {
            // Go to the a search page that support the "map" feature,
            // should be toggled off by default.
            cy.visit('/front/monitor.php');
            cy.findByTestId('search-format-table').should('be.visible');
            cy.findByTestId('search-format-map').should('not.exist');

            // Toggle map view
            cy.findByRole('radio', {name: "Show as map"}).next().click();
            cy.findByTestId('search-format-table').should('not.exist');
            cy.findByTestId('search-format-map').should('be.visible');

            // Toggle back to table view
            cy.findByRole('radio', {name: "Show as table"}).next().click();
            cy.findByTestId('search-format-table').should('be.visible');
            cy.findByTestId('search-format-map').should('not.exist');
        });
    });
}
