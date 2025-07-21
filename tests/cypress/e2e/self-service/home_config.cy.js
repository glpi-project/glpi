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

const test_tiles = [
    {
        title: "Browse help articles",
        description: "See all available help articles and our FAQ.",
        illustration: "browse-kb",
        page: "faq",
    },
    {
        title: "Request a service",
        description: "Ask for a service to be provided by our team.",
        illustration: "request-service",
        page: "service_catalog ",
    },
    {
        title: "Make a reservation",
        description: "Pick an available asset and reserve it for a given date.",
        illustration: "reservation",
        page: "reservation",
    },
    {
        title: "View approval requests",
        description: "View all tickets waiting for your validation.",
        illustration: "approve-requests",
        page: "approval",
    },
];

const tests = [
    {
        label: "profile",
        itemtype: "Profile",
        subject: {
            'name': 'Helpdesk profile for e2e tests',
            'interface': 'helpdesk',
        },
        url: "/front/profile.form.php?id=${id}&forcetab=Profile$4",
    },
    {
        label: "entity",
        itemtype: "Entity",
        subject: {
            'name': 'Entity for e2e tests',
            'entities_id': 1, // E2ETestEntity
        },
        url: "/front/entity.form.php?id=${id}&forcetab=Entity$9",
    }
];

for (const test of tests) {
    const original_name = test.subject.name;

    describe(`Helpdesk home page configuration (${test.label})`, () => {
        beforeEach(() => {
            cy.login();

            // Add random part to subject name to avoid unicity issues
            test.subject.name = original_name + (new Date()).getTime();

            // Set up a new profile with 4 tiles
            cy.createWithAPI(test.itemtype, test.subject).then((id) => {
                test_tiles.forEach((tile, i) => {
                    cy.createWithAPI(
                        'Glpi\\Helpdesk\\Tile\\GlpiPageTile',
                        tile
                    ).then((tile_id) => {
                        cy.createWithAPI('Glpi\\Helpdesk\\Tile\\Item_Tile', {
                            'itemtype_item': test.itemtype,
                            'items_id_item': id,
                            'itemtype_tile': 'Glpi\\Helpdesk\\Tile\\GlpiPageTile',
                            'items_id_tile': tile_id,
                            'rank': i,
                        });
                    });
                });

                cy.visit(test.url.replace('${id}', id));
            });

            // Need the JS controller to be ready
            // eslint-disable-next-line cypress/no-unnecessary-waiting
            cy.wait(1200);

            // html5sortable add the wrong "option" role, we must remove it
            cy.window().then((win) => {
                win.document
                    .querySelector('[data-glpi-helpdesk-config-tiles]')
                    .querySelectorAll('section')
                    .forEach((node) => node.removeAttribute('role'))
                ;
            });
        });

        it(`can reorder tiles (${test.label})`, () => {
            // Valide default order
            validateTilesOrder([
                "Browse help articles",
                "Request a service",
                "Make a reservation",
                "View approval requests",
            ]);

            // Change order
            moveTileAfterTile("Browse help articles", "Make a reservation");
            validateTilesOrder([
                "Request a service",
                "Make a reservation",
                "Browse help articles",
                "View approval requests",
            ]);

            // Save new order
            cy.findByRole('button', {'name': "Save tiles order"}).click();
            cy.findAllByRole('alert')
                .contains("Configuration updated successfully.")
                .should('be.visible')
            ;
            validateTilesOrder([
                "Request a service",
                "Make a reservation",
                "Browse help articles",
                "View approval requests",
            ]);

            // Make sure the state is still editable after the action
            checkThatTilesAreEditable();
        });

        it(`can remove tiles (${test.label})`, () => {
            // Delete tile
            cy.findByRole("region", {'name': "Request a service"}).click();
            cy.findByRole('button', {'name': 'Delete tile'}).click();

            // Validate deletion
            cy.findByRole("region", {'name': "Request a service"}).should('not.exist');
            cy.findAllByRole('alert')
                .contains("Configuration updated successfully.")
                .should('be.visible')
            ;
            validateTilesOrder([
                "Browse help articles",
                "Make a reservation",
                "View approval requests",
            ]);

            // Make sure the state is still editable after the action
            checkThatTilesAreEditable();

            // Refresh page to confirm deletion
            cy.reload();
            validateTilesOrder([
                "Browse help articles",
                "Make a reservation",
                "View approval requests",
            ]);
        });

        it(`can edit a tile (${test.label})`, () => {
            // Go to tile edition form
            cy.findByRole("region", {'name': "Request a service"}).click();

            // Change a field
            cy.findByRole("textbox", {'name': 'Title'}).clear();
            cy.findByRole("textbox", {'name': 'Title'}).type("My new tile name");

            // Submit
            cy.findByRole('dialog').findByRole('button', {'name': 'Save changes'}).click();
            cy.findAllByRole('alert')
                .contains("Configuration updated successfully.")
                .should('be.visible')
            ;
            validateTilesOrder([
                "Browse help articles",
                "My new tile name",
                "Make a reservation",
                "View approval requests",
            ]);

            // Make sure the state is still editable after the action
            checkThatTilesAreEditable();
        });

        it(`can add a "Glpi page" tile (${test.label})`, () => {
            // Go to tile creation form
            cy.findByRole('button', {'name': "Add tile"}).click();

            // Set fields
            cy.getDropdownByLabelText('Type').selectDropdownValue('GLPI page');
            cy.findByRole("textbox", {'name': 'Title'}).type("My title");
            cy.findByRole("dialog")
                .find('div[data-glpi-helpdesk-config-add-tile-form-for]:visible') // impossible to target without this due to some limitations
                .findByLabelText('Description')
                .awaitTinyMCE()
                .type("My description")
            ;
            cy.getDropdownByLabelText('Target page').selectDropdownValue('Service catalog');

            // Submit
            cy.findByRole('dialog').findByRole('button', {'name': 'Add tile'}).click();
            cy.findAllByRole('alert')
                .contains("Configuration updated successfully.")
                .should('be.visible')
            ;
            validateTilesOrder([
                "Browse help articles",
                "Request a service",
                "Make a reservation",
                "View approval requests",
                "My title",
            ]);
            cy.findByText("My description").should('be.visible');

            // Make sure the state is still editable after the action
            checkThatTilesAreEditable();
        });

        it(`can add a "External page" tile (${test.label})`, () => {
            // Go to tile creation form
            cy.findByRole('button', {'name': "Add tile"}).click();

            // Set fields
            cy.getDropdownByLabelText('Type').selectDropdownValue('External page');
            cy.findByRole("textbox", {'name': 'Title'}).type("My external tile title");
            cy.findByRole("dialog")
                .find('div[data-glpi-helpdesk-config-add-tile-form-for]:visible') // impossible to target without this due to some limitations
                .findByLabelText('Description')
                .awaitTinyMCE()
                .type("My description")
            ;
            cy.findByRole("textbox", {'name': 'Target url'}).type("support.teclib.com");

            // Submit
            cy.findByRole('dialog').findByRole('button', {'name': 'Add tile'}).click();
            cy.findAllByRole('alert')
                .contains("Configuration updated successfully.")
                .should('be.visible')
            ;
            validateTilesOrder([
                "Browse help articles",
                "Request a service",
                "Make a reservation",
                "View approval requests",
                "My external tile title",
            ]);
            cy.findByText("My description").should('be.visible');
            validateTileFields(
                "My external tile title",
                "My description",
                "support.teclib.com"
            );
        });

        it(`can add a "Form" tile (${test.label})`, () => {
            // Go to tile creation form
            cy.findByRole('button', {'name': "Add tile"}).click();

            // Set fields
            cy.getDropdownByLabelText('Type').selectDropdownValue('Form');
            cy.getDropdownByLabelText('Target form').selectDropdownValue('Report an issue');

            // Submit
            cy.findByRole('dialog').findByRole('button', {'name': 'Add tile'}).click();
            cy.findAllByRole('alert')
                .contains("Configuration updated successfully.")
                .should('be.visible')
            ;
            validateTilesOrder([
                "Browse help articles",
                "Request a service",
                "Make a reservation",
                "View approval requests",
                "Report an issue",
            ]);
            cy.findByText("Ask for support from our helpdesk team.").should('be.visible');
        });
    });
}

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

function validateTilesOrder(tiles) {
    cy.findByRole("region", {'name': "Home tiles configuration"}).within(() => {
        tiles.forEach((title, i) => {
            cy.findAllByRole("region").eq(i).should('have.attr', 'aria-label', title);
        });
    });
}

function validateTileFields(title, description, target) {
    cy.findByRole("region", {'name': title}).click();
    cy.findByRole("heading", {'name': title}).should('be.visible');
    cy.findByLabelText('Description').awaitTinyMCE().should('contain', description);
    cy.findByLabelText('Target url').should('have.value', target);
}

function moveTileAfterTile(subject, destination) {
    cy.findByRole("region", {'name': subject}).startToDrag();
    cy.findByRole("region", {'name': destination}).dropDraggedItemAfter();
}

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

function checkThatTilesAreEditable() {
    // Using "should be.visible" instead of click would be more precise but
    // it seems to lead to many false negative results.
    cy.findByRole('button', {'name': "Add tile"}).click();
}
