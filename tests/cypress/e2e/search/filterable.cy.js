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

describe('Filterable', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin');
    });

    it('preview results are only loaded when explicitly requester', () => {
        // We will be looking for the computer name directly so it must be unique.
        const computer_name = `Computer for Filterable tests [${Cypress._.uniqueId()}]`;

        describe("Set up data and go to webhook page", () => {
            cy.createWithAPI("Computer", {"name": computer_name})
                .as("test_computer_id")
            ;
            cy.createWithAPI("Webhook", {
                "name": "Test webhook",
                "itemtype": "Computer",
            }).as("webhook_id");

            cy.get('@webhook_id').then((webhook_id) => {
                const url = `/front/webhook.form.php`;
                const tab = "Glpi\\Search\\CriteriaFilter$1";
                cy.visit(`${url}?id=${webhook_id}&forcetab=${tab}`);
            });
        });

        describe("Create filter", () => {
            cy.findByRole("button", {"name": "Create a filter"}).click();

            // TODO: bad selector here, we must add accessiblity labels to
            // the search engine in order to be able to use findByRole instead.
            // -> cy.findByRole("textbox", {"name": "Items seen"})
            cy.get('input[name="criteria[0][value]"').type(computer_name);
            cy.findByRole("button", {"name": "Save"}).click();
            cy.findByRole("alert").should("exist")
                .and("contains.text", "Filter saved")
            ;
        });

        describe("Check that the preview results are not loaded", () => {
            cy.findByRole("link", {'name' : computer_name}).should('not.exist');
        });

        describe("Load preview results", () => {
            // Protecting against false positive by demonstrating that if the
            // preview was shown our computer link would be displayed.
            cy.findByRole("button", {"name": "Preview results"}).click();
            cy.findByRole("link", {'name' : computer_name}).should('exist');
        });

        describe("Reload page and make sure preview was not executed", () => {
            cy.reload();

            // We will force the hidden preview content to be displayed as it
            // make it easier to query it with accessiblity selectors.
            cy.get("#criteria_filter_preview")
                .should('exist')
                .invoke("removeClass", "d-none")
            ;

            cy.get("#criteria_filter_preview").within(() => {
                cy.findByRole("heading", {
                    'name' : "Preview",
                }).should('exist');

                // Computer link should not be present
                cy.findByRole("link", {
                    'name' : computer_name,
                }).should('not.exist');
            });
        });
    });
});
