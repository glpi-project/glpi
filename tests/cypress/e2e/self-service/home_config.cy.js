/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

describe(`Helpdesk home page configuration - entities specific`, () => {
    beforeEach(() => {
        cy.login();
        cy.createWithAPI("Entity", {
            'name': `Entity for e2e tests ${(new Date()).getTime()}`,
            'entities_id': 1, // E2ETestEntity
        }).as('entityId').then((id) => {
            cy.visit(`/front/entity.form.php?id=${id}&forcetab=Entity$9`);
        });
    });

    it(`can configure an entity from scratch`, () => {
        // When the page is loaded, the tiles from the parent entity are shown
        cy.findByText('There are no tiles defined for this entity.')
            .should('be.visible')
        ;
        cy.findByRole('heading', {name: 'Browse help articles'})
            .should('be.visible')
        ;
        cy.findByRole('button', {name: "Add tile"}).should('not.exist');

        // Define our own tabs
        cy.findByRole('button', {
            name: "Define tiles for this entity from scratch"
        }).click();
        cy.findByRole('button', {name: "Add tile"}).should('be.visible');
        cy.findByText('There are no tiles defined for this entity.')
            .should('not.be.visible')
        ;
        cy.findByRole('heading', {name: 'Browse help articles'})
            .should('not.exist')
        ;
    });

    it(`can copy an entity settings`, () => {
        // When the page is loaded, the tiles from the parent entity are shown
        cy.findByText('There are no tiles defined for this entity.')
            .should('be.visible')
        ;
        cy.findByRole('heading', {name: 'Browse help articles'})
            .should('be.visible')
        ;
        cy.findByRole('button', {name: "Add tile"}).should('not.exist');

        // Define our own tabs, with the parent tabs still being here
        cy.findByRole('button', {
            name: "Copy parent entity configuration into this entity"
        }).click();
        cy.findByRole('button', {name: "Add tile"}).should('be.visible');
        cy.findByText('There are no tiles defined for this entity.')
            .should('not.exist')
        ;
        cy.findByRole('heading', {name: 'Browse help articles'})
            .should('be.visible')
        ;
    });

    it('can configure custom illustrations', () => {
        // Some headings should be displayed
        cy.findByRole('heading', {name: "Custom illustrations"}).should('be.visible');
        cy.findByRole('heading', {name: "Left side"}).should('be.visible');
        cy.findByRole('heading', {name: "Right side"}).should('be.visible');

        // There should be two dropdowns - one per side
        cy.getDropdownByLabelText("Left side configuration").should('be.visible');
        cy.getDropdownByLabelText("Right side configuration").should('be.visible');

        // Configure a custom illustration
        cy.findByRole('region', {name: "Left side"}).within(() => {
            // Default state, inheritance should be selected
            cy.getDropdownByLabelText("Left side configuration").should(
                "have.text",
                "Inherited from parent entity"
            );
            validateRegionVisibilities({
                "Default illustration preview"             : 'not.exist',
                "Custom illustration preview and selection": 'not.exist',
                "Inherited illustration preview"           : 'be.visible',
            });
            validateSvgSpriteIsShown();

            // Switch to "Custom illustration"
            cy.getDropdownByLabelText("Left side configuration")
                .selectDropdownValue("Custom illustration")
            ;
            validateRegionVisibilities({
                "Default illustration preview"             : 'not.exist',
                "Custom illustration preview and selection": 'be.visible',
                "Inherited illustration preview"           : 'not.exist',
            });

            // Upload an image
            cy.get('input[type=file]').selectFile("fixtures/uploads/bar.png");
            cy.findByText("Upload successful").should('be.visible');

            // Save and reload, make sure value was saved
            saveIllustrationSettings();
            cy.getDropdownByLabelText("Left side configuration").should(
                "have.text",
                "Custom illustration"
            );
            validateRegionVisibilities({
                "Default illustration preview"             : 'not.exist',
                "Custom illustration preview and selection": 'be.visible',
                "Inherited illustration preview"           : 'not.exist',
            });
            validateImageIsShown();
        });

        // Use the default illustration
        cy.findByRole('region', {name: "Left side"}).within(() => {
            // Switch to "Default illustration"
            cy.getDropdownByLabelText("Left side configuration")
                .selectDropdownValue("Default illustration")
            ;
            validateRegionVisibilities({
                "Default illustration preview"             : 'be.visible',
                "Custom illustration preview and selection": 'not.exist',
                "Inherited illustration preview"           : 'not.exist',
            });
            validateSvgSpriteIsShown();

            // Save and reload, make sure value was saved
            saveIllustrationSettings();
            cy.getDropdownByLabelText("Left side configuration").should(
                "have.text",
                "Default illustration"
            );
            validateRegionVisibilities({
                "Default illustration preview"             : 'be.visible',
                "Custom illustration preview and selection": 'not.exist',
                "Inherited illustration preview"           : 'not.exist',
            });
            validateSvgSpriteIsShown();
        });

        // Go back to inherited value
        cy.findByRole('region', {name: "Left side"}).within(() => {
            // Switch to "Default illustration"
            cy.getDropdownByLabelText("Left side configuration")
                .selectDropdownValue("Inherited from parent entity")
            ;
            validateRegionVisibilities({
                "Default illustration preview"             : 'not.exist',
                "Custom illustration preview and selection": 'not.exist',
                "Inherited illustration preview"           : 'be.visible',
            });
            validateSvgSpriteIsShown();

            // Save and reload, make sure value was saved
            saveIllustrationSettings();
            cy.getDropdownByLabelText("Left side configuration").should(
                "have.text",
                "Inherited from parent entity"
            );
            validateRegionVisibilities({
                "Default illustration preview"             : 'not.exist',
                "Custom illustration preview and selection": 'not.exist',
                "Inherited illustration preview"           : 'be.visible',
            });
            validateSvgSpriteIsShown();
        });
    });

    it('can configure custom titles', () => {
        cy.findByRole('heading', {name: "General"}).should('be.visible');

        // Default state, inheritance should be selected
        cy.getDropdownByLabelText("Main title").should(
            "have.text",
            "Inherited from parent entity"
        );
        validateRegionVisibilities({
            "Default title preview"  : 'not.exist',
            "Custom title value"     : 'not.exist',
            "Inherited title preview": 'be.visible',
        });
        cy.findByRole('region', {name: "Inherited title preview"})
            .find('input')
            .should('have.value', "How can we help you?")
        ;

        // Switch to custom title
        cy.getDropdownByLabelText("Main title")
            .selectDropdownValue("Custom value")
        ;
        validateRegionVisibilities({
            "Default title preview"  : 'not.exist',
            "Custom title value"     : 'be.visible',
            "Inherited title preview": 'not.exist',
        });
        cy.findByRole('region', {name: "Custom title value"})
            .find('input')
            .should('have.value', "How can we help you?")
            .clear()
        ;
        cy.findByRole('region', {name: "Custom title value"})
            .find('input')
            .type("My custom title value")
        ;
        saveTitleSettings();
        cy.getDropdownByLabelText("Main title").should(
            "have.text",
            "Custom value",
        );
        validateRegionVisibilities({
            "Default title preview"  : 'not.exist',
            "Custom title value"     : 'be.visible',
            "Inherited title preview": 'not.exist',
        });
        cy.findByRole('region', {name: "Custom title value"})
            .find('input')
            .should('have.value', "My custom title value")
        ;

        // Use the default title
        cy.getDropdownByLabelText("Main title")
            .selectDropdownValue("Default value")
        ;
        validateRegionVisibilities({
            "Default title preview"  : 'be.visible',
            "Custom title value"     : 'not.exist',
            "Inherited title preview": 'not.exist',
        });
        cy.findByRole('region', {name: "Default title preview"})
            .find('input')
            .should('have.value', "How can we help you?")
        ;
        saveTitleSettings();
        cy.getDropdownByLabelText("Main title").should(
            "have.text",
            "Default value",
        );
        validateRegionVisibilities({
            "Default title preview"  : 'be.visible',
            "Custom title value"     : 'not.exist',
            "Inherited title preview": 'not.exist',
        });
        cy.findByRole('region', {name: "Default title preview"})
            .find('input')
            .should('have.value', "How can we help you?")
        ;

        // Go back to inherited value
        cy.getDropdownByLabelText("Main title")
            .selectDropdownValue("Inherited from parent entity")
        ;
        validateRegionVisibilities({
            "Default title preview"  : 'not.exist',
            "Custom title value"     : 'not.exist',
            "Inherited title preview": 'be.visible',
        });
        cy.findByRole('region', {name: "Inherited title preview"})
            .find('input')
            .should('have.value', "How can we help you?")
        ;
        saveTitleSettings();
        cy.getDropdownByLabelText("Main title").should(
            "have.text",
            "Inherited from parent entity",
        );
        validateRegionVisibilities({
            "Default title preview"  : 'not.exist',
            "Custom title value"     : 'not.exist',
            "Inherited title preview": 'be.visible',
        });
        cy.findByRole('region', {name: "Inherited title preview"})
            .find('input')
            .should('have.value', "How can we help you?")
        ;
    });

    it('tiles with deleted form are not displayed', () => {
        const uuid = Cypress._.random(0, 1e6);

        // Create a form
        cy.get('@entityId').then((entityId) => {
            cy.createFormWithAPI({
                'name': `Test form - ${uuid}`,
                'is_active': true,
                'entities_id': entityId,
            }).as('formId');
        });

        // Create a form tile
        cy.findByRole('button', {
            name: "Define tiles for this entity from scratch"
        }).click();
        cy.findByRole('button', {name: "Add tile"}).click();
        cy.getDropdownByLabelText('Type').selectDropdownValue('Form');
        cy.getDropdownByLabelText('Target form').selectDropdownValue(`Test form - ${uuid}`);
        cy.findByRole('dialog').findByRole('button', {name: 'Add tile'}).click();
        cy.findAllByRole('alert')
            .contains("Configuration updated successfully.")
            .should('be.visible')
        ;

        // Save the configuration
        cy.findByRole('button', {name: "Save tiles order"}).click();
        cy.findAllByRole('alert')
            .contains("Configuration updated successfully.")
            .should('be.visible')
        ;

        // Validate the tile is displayed
        cy.changeProfile('Self-Service');
        cy.get('@entityId').then((entityId) => cy.changeEntity(entityId));
        cy.visit('/Helpdesk');
        cy.findByRole('heading', {name: `Test form - ${uuid}`}).should('be.visible');

        // Delete the form
        cy.changeProfile('Super-Admin');
        cy.get('@formId').then(formId => {
            // Visit the form to ensure it exists
            cy.visit(`front/form/form.form.php?id=${formId}`);
        });
        cy.findByRole('button', {'name': 'Put in trashbin'}).click();
        cy.findByRole('alert').should('contain.text', 'Item successfully deleted');

        // Go back to the self-service page
        cy.changeProfile('Self-Service');
        cy.get('@entityId').then((entityId) => cy.changeEntity(entityId));
        cy.visit('/Helpdesk');

        // Validate the tile is not displayed anymore
        cy.findByRole('heading', {name: `Test form - ${uuid}`}).should('not.exist');

        // Purge the form
        cy.changeProfile('Super-Admin');
        cy.get('@formId').then(formId => {
            // Visit the form to ensure it exists
            cy.visit(`front/form/form.form.php?id=${formId}`);
        });
        cy.findByRole('button', {'name': 'Delete permanently'}).click();
        cy.findByRole('alert').should('contain.text', 'Item successfully purged');

        // Go back to the self-service page
        cy.changeProfile('Self-Service');
        cy.get('@entityId').then((entityId) => cy.changeEntity(entityId));
        cy.visit('/Helpdesk');

        // Validate the tile is not displayed anymore
        cy.findByRole('heading', {name: `Test form - ${uuid}`}).should('not.exist');
    });
});

function saveIllustrationSettings() {
    cy.document().its('body').within(() => {
        cy.findByRole('button', {name: "Save custom illustrations settings"}).click();
    });
}

function saveTitleSettings() {
    cy.findByRole('button', {name: "Save general settings"}).click();
    cy.findByRole('alert').findByRole('button', {name: "Close"}).click();
}

function validateRegionVisibilities(regions) {
    for (const [region, assertion] of Object.entries(regions)) {
        cy.findByRole('region', {name: region})
            .should(assertion)
        ;
    }
}

function validateImageIsShown() {
    cy.get('img:visible')
        .should('have.prop', 'naturalWidth')
        .should('be.greaterThan', 0)
    ;
}

function validateSvgSpriteIsShown() {
    cy.get('svg:visible').should('exist');
    // TODO: something like this would be better but I can't get it to work.
    // cy.get('svg:visible').find('use').shadow().find('symbol').should('exist');
}
