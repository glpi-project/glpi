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

describe ('Import forms', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin');
    });

    it('can import forms whitout resolve issues', () => {
        // Step 1: file selection
        cy.visit('/front/form/form.php');
        cy.findByRole('button', {'name': "Import forms"}).click();
        cy.findByLabelText("Select your file").selectFile("fixtures/export-of-2-forms.json");

        // Step 2: preview
        cy.findByRole('button', {'name': "Preview import"}).click();
        cy.findAllByRole('row').as('preview');
        cy.get("@preview").eq(1).within(() => {
            cy.findByText("My valid form").should('exist');
            cy.findByText("Ready to be imported").should('exist');
            cy.findByRole("button", {'name': "Resolve issues"}).should('not.exist');
            cy.findByRole("button", {'name': "Remove form"}).should('exist');
        });
        cy.get("@preview").eq(2).within(() => {
            cy.findByText("My invalid form").should('exist');
            cy.findByText("Can't be imported").should('exist');
            cy.findByRole("button", {'name': "Resolve issues"}).should('exist');
            cy.findByRole("button", {'name': "Remove form"}).should('exist');
        });

        // Step 4: import
        cy.findByRole('button', {'name': "Import"}).click();
        cy.findAllByRole('row').as('preview');
        cy.get("@preview").eq(1).within(() => {
            cy.findByRole("link", {'name': "My valid form"}).should('exist');
            cy.findByText("Imported").should('exist');
        });
        cy.get("@preview").eq(2).within(() => {
            cy.findByText("My invalid form").should('exist');
            cy.findByText("Not imported").should('exist');
        });

        // Go back to first step
        cy.findByRole('link', {'name': "Import another file"}).click();
        cy.findByLabelText("Select your file").should('exist');
    });

    it('can import forms with resolve issues', () => {
        // Step 1: file selection
        cy.visit('/front/form/form.php');
        cy.findByRole('button', {'name': "Import forms"}).click();
        cy.findByLabelText("Select your file").selectFile("fixtures/export-of-2-forms.json");

        // Step 2: preview
        cy.findByRole('button', {'name': "Preview import"}).click();
        cy.findAllByRole('row').as('preview');
        cy.get("@preview").eq(1).within(() => {
            cy.findByText("My valid form").should('exist');
            cy.findByText("Ready to be imported").should('exist');
            cy.findByRole("button", {'name': "Resolve issues"}).should('not.exist');
            cy.findByRole("button", {'name': "Remove form"}).should('exist');
        });
        cy.get("@preview").eq(2).within(() => {
            cy.findByText("My invalid form").should('exist');
            cy.findByText("Can't be imported").should('exist');
            cy.findByRole("button", {'name': "Remove form"}).should('exist');
            cy.findByRole("button", {'name': "Resolve issues"}).should('exist').click();
        });

        // Step 3: resolve issues
        cy.findAllByRole('row').as('issues');
        cy.get("@issues").eq(1).within(() => {
            cy.findByText("Missing entity").should('exist');
            cy.document().within(() => {
                cy.getDropdownByLabelText("Replacement value for 'Missing entity'").selectDropdownValue("»E2ETestEntity");
            });
        });
        cy.get("@issues").eq(2).within(() => {
            cy.findByText("Missing user").should('exist');
            cy.document().within(() => {
                cy.getDropdownByLabelText("Replacement value for 'Missing user'").selectDropdownValue("E2E Tests");
            });
        });

        // Step 2: preview
        cy.findByRole('button', {'name': "Preview import"}).click();
        cy.findAllByRole('row').as('preview');
        cy.get("@preview").eq(1).within(() => {
            cy.findByText("My valid form").should('exist');
            cy.findByText("Ready to be imported").should('exist');
            cy.findByRole("button", {'name': "Resolve issues"}).should('not.exist');
            cy.findByRole("button", {'name': "Remove form"}).should('exist');
        });
        cy.get("@preview").eq(2).within(() => {
            cy.findByText("My invalid form").should('exist');
            cy.findByText("Ready to be imported").should('exist');
            cy.findByRole("button", {'name': "Resolve issues"}).should('not.exist');
            cy.findByRole("button", {'name': "Remove form"}).should('exist');
        });

        // Step 4: import
        cy.findByRole('button', {'name': "Import"}).click();
        cy.findAllByRole('row').as('preview');
        cy.get("@preview").eq(1).within(() => {
            cy.findByRole("link", {'name': "My valid form"}).should('exist');
            cy.findByText("Imported").should('exist');
        });
        cy.get("@preview").eq(2).within(() => {
            cy.findByRole("link", {'name': "My invalid form"}).should('exist');
            cy.findByText("Imported").should('exist');
        });

        // Go back to first step
        cy.findByRole('link', {'name': "Import another file"}).click();
        cy.findByLabelText("Select your file").should('exist');
    });

    it('can remove forms from the import list', () => {
        // Step 1: file selection
        cy.visit('/front/form/form.php');
        cy.findByRole('button', {'name': "Import forms"}).click();
        cy.findByLabelText("Select your file").selectFile("fixtures/export-of-2-forms.json");

        // Step 2: preview
        cy.findByRole('button', {'name': "Preview import"}).click();
        cy.findAllByRole('row').as('preview');
        cy.get("@preview").eq(1).within(() => {
            cy.findByText("My valid form").should('exist');
            cy.findByText("Ready to be imported").should('exist');
            cy.findByRole("button", {'name': "Resolve issues"}).should('not.exist');
            cy.findByRole("button", {'name': "Remove form"}).should('exist');
        });
        cy.get("@preview").eq(2).within(() => {
            cy.findByText("My invalid form").should('exist');
            cy.findByText("Can't be imported").should('exist');
            cy.findByRole("button", {'name': "Resolve issues"}).should('exist');
            cy.findByRole("button", {'name': "Remove form"}).should('exist');
        });

        // Remove the second form
        cy.get("@preview").eq(2).findByRole("button", {'name': "Remove form"}).click();
        cy.get("@preview").eq(1).should('exist');
        cy.get("@preview").eq(2).should('not.exist');

        // Remove the first form
        cy.get("@preview").eq(1).findByRole("button", {'name': "Remove form"}).click();
        cy.get("@preview").should('not.exist');

        // Check if we are back to the first step
        cy.findByLabelText("Select your file").should('exist');
    });
});
