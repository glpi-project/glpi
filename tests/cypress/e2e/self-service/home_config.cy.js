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

describe('Helpdesk home page configuration', () => {
    beforeEach(() => {
        cy.login();

        // Set up a new profile with 4 tiles
        cy.createWithAPI('Profile', {
            'name': 'Helpdesk profile for e2e tests',
            'interface': 'helpdesk',
        }).then((profile_id) => {
            const tiles = [
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
            tiles.forEach((tile, i) => {
                cy.createWithAPI(
                    'Glpi\\Helpdesk\\Tile\\GlpiPageTile',
                    tile
                ).then((tile_id) => {
                    cy.createWithAPI('Glpi\\Helpdesk\\Tile\\Profile_Tile', {
                        'profiles_id': profile_id,
                        'itemtype': 'Glpi\\Helpdesk\\Tile\\GlpiPageTile',
                        'items_id': tile_id,
                        'rank': i,
                    });
                });
            });

            cy.visit(`/front/profile.form.php?id=${profile_id}&forcetab=Profile$4`);
        });

        // Need the JS controller to be ready
        // eslint-disable-next-line cypress/no-unnecessary-waiting
        cy.wait(1000);

        // html5sortable add the wrong "option" role, we must remove it
        cy.window().then((win) => {
            win.document
                .querySelector('[data-glpi-helpdesk-config-tiles]')
                .querySelectorAll('section')
                .forEach((node) => node.removeAttribute('role'))
            ;
        });
    });

    function validateTilesOrder(tiles) {
        cy.findByRole("region", {'name': "Home tiles configuration"}).within(() => {
            tiles.forEach((title, i) => {
                cy.findAllByRole("region").eq(i).should('have.attr', 'aria-label', title);
            });
        });
    }

    function validateOrderControlsAreHidden() {
        cy.findByRole('button', {'name': "Cancel"}).should('not.exist');
        cy.findByRole('button', {'name': "Save order"}).should('not.exist');
        cy.findByRole('button', {'name': "Add tile"}).should('be.visible');
    }

    function validateOrderControlsAreShown() {
        cy.findByRole('button', {'name': "Cancel"}).should('be.visible');
        cy.findByRole('button', {'name': "Save order"}).should('be.visible');
        cy.findByRole('button', {'name': "Add tile"}).should('not.exist');
    }

    function moveTileAfterTile(subject, destination) {
        // Because the drag and drop was faked, we need to manually trigger the
        // sortstart event to display the actions
        cy.get("[data-glpi-helpdesk-config-tiles]").trigger('sortstart');

        cy.findByRole("region", {'name': subject}).startToDrag();
        cy.findByRole("region", {'name': destination}).dropDraggedItemAfter();
    }

    // Not yet migrated because I haven't tested drag and drop in playwright yet.
    it('can reorder tiles', () => {
        // Valide default order
        validateTilesOrder([
            "Browse help articles",
            "Request a service",
            "Make a reservation",
            "View approval requests",
        ]);
        validateOrderControlsAreHidden();

        // Change order
        moveTileAfterTile("Browse help articles", "Make a reservation");
        validateTilesOrder([
            "Request a service",
            "Make a reservation",
            "Browse help articles",
            "View approval requests",
        ]);
        validateOrderControlsAreShown();

        // Revert to original order
        cy.findByRole('button', {'name': "Cancel"}).click();
        validateTilesOrder([
            "Browse help articles",
            "Request a service",
            "Make a reservation",
            "View approval requests",
        ]);
        validateOrderControlsAreHidden();

        // Change order again
        moveTileAfterTile("View approval requests", "Request a service");
        validateTilesOrder([
            "Browse help articles",
            "Request a service",
            "View approval requests",
            "Make a reservation",
        ]);
        validateOrderControlsAreShown();

        // Save new order
        cy.findByRole('button', {'name': "Save order"}).click();
        cy.findByRole('alert').should(
            'contain.text',
            "Configuration updated successfully."
        );
        validateTilesOrder([
            "Browse help articles",
            "Request a service",
            "View approval requests",
            "Make a reservation",
        ]);
        validateOrderControlsAreHidden();
    });

    it('[already migrated] can remove tiles', () => {
        // Delete tile
        cy.findByRole("region", {'name': "Request a service"}).within(() => {
            cy.findByRole('button', {'name': 'Show more actions'}).click();
        });
        cy.findByRole('button', {'name': 'Delete tile'}).click();

        // Validate deletion
        cy.findByRole("region", {'name': "Request a service"}).should('not.exist');
        cy.findByRole('alert').should(
            'contain.text',
            "Configuration updated successfully."
        );
        validateTilesOrder([
            "Browse help articles",
            "Make a reservation",
            "View approval requests",
        ]);

        // Refresh page to confirm deletion
        cy.reload();
        validateTilesOrder([
            "Browse help articles",
            "Make a reservation",
            "View approval requests",
        ]);
    });

    it('[already migrated] can cancel editing tile', () => {
        // Default state
        cy.findByRole("region", {'name': "Home tiles configuration"}).should('be.visible');
        cy.findByRole("region", {'name': "Edit tile"}).should('not.exist');

        // Enter edit mode
        cy.findByRole("region", {'name': "Request a service"}).within(() => {
            cy.findByRole('button', {'name': 'Show more actions'}).click();
        });
        cy.findByRole('button', {'name': 'Edit tile'}).click();
        cy.findByRole("region", {'name': "Edit tile"}).should('be.visible');
        cy.findByRole("region", {'name': "Home tiles configuration"}).should('not.exist');

        // Change a field
        cy.findByRole("textbox", {'name': 'Title'}).clear();
        cy.findByRole("textbox", {'name': 'Title'}).type("My new tile name");

        // Cancel
        cy.findByRole('button', {'name': 'Cancel'}).click();
        validateTilesOrder([
            "Browse help articles",
            "Request a service",
            "Make a reservation",
            "View approval requests",
        ]);
    });

    it('[already migrated] can edit a tile', () => {
        // Default state
        cy.findByRole("region", {'name': "Home tiles configuration"}).should('be.visible');
        cy.findByRole("region", {'name': "Edit tile"}).should('not.exist');

        // Enter edit mode
        cy.findByRole("region", {'name': "Request a service"}).within(() => {
            cy.findByRole('button', {'name': 'Show more actions'}).click();
        });
        cy.findByRole('button', {'name': 'Edit tile'}).click();
        cy.findByRole("region", {'name': "Edit tile"}).should('be.visible');
        cy.findByRole("region", {'name': "Home tiles configuration"}).should('not.exist');

        // Change a field
        cy.findByRole("textbox", {'name': 'Title'}).clear();
        cy.findByRole("textbox", {'name': 'Title'}).type("My new tile name");

        // Submit
        cy.findByRole('button', {'name': 'Save changes'}).click();
        validateTilesOrder([
            "Browse help articles",
            "My new tile name",
            "Make a reservation",
            "View approval requests",
        ]);
    });

    it('[already migrated] can add a "Glpi page" tile', () => {
        // Default state
        cy.findByRole("region", {'name': "Home tiles configuration"}).should('be.visible');
        cy.findByRole("region", {'name': "Add a new tile"}).should('not.exist');

        // Enter add mode
        cy.findByRole('button', {'name': "Add tile"}).click();
        cy.findByRole("region", {'name': "Add a new tile"}).should('be.visible');
        cy.findByRole("region", {'name': "Home tiles configuration"}).should('not.exist');

        // Set fields
        cy.getDropdownByLabelText('Type').selectDropdownValue('GLPI page');
        cy.findByRole("textbox", {'name': 'Title'}).type("My title");
        cy.findByRole("region", {'name': "Add a new tile"})
            .find('div[data-glpi-helpdesk-config-add-tile-form-for]:visible') // impossible to target without this due to some limitations
            .findByLabelText('Description')
            .awaitTinyMCE()
            .type("My description")
        ;
        cy.getDropdownByLabelText('Target page').selectDropdownValue('Service catalog');

        // Submit
        cy.findByRole('button', {'name': 'Add tile'}).click();
        validateTilesOrder([
            "Browse help articles",
            "Request a service",
            "Make a reservation",
            "View approval requests",
            "My title",
        ]);
        cy.findByText("My description").should('be.visible');
    });

    it('[already migrated] can add a "External page" tile', () => {
        // Default state
        cy.findByRole("region", {'name': "Home tiles configuration"}).should('be.visible');
        cy.findByRole("region", {'name': "Add a new tile"}).should('not.exist');

        // Enter add mode
        cy.findByRole('button', {'name': "Add tile"}).click();
        cy.findByRole("region", {'name': "Add a new tile"}).should('be.visible');
        cy.findByRole("region", {'name': "Home tiles configuration"}).should('not.exist');

        // Set fields
        cy.getDropdownByLabelText('Type').selectDropdownValue('External page');
        cy.findByRole("textbox", {'name': 'Title'}).type("My external tile title");
        cy.findByRole("region", {'name': "Add a new tile"})
            .find('div[data-glpi-helpdesk-config-add-tile-form-for]:visible') // impossible to target without this due to some limitations
            .findByLabelText('Description')
            .awaitTinyMCE()
            .type("My description")
        ;
        cy.findByRole("textbox", {'name': 'Target url'}).type("support.teclib.com");

        // Submit
        cy.findByRole('button', {'name': 'Add tile'}).click();
        validateTilesOrder([
            "Browse help articles",
            "Request a service",
            "Make a reservation",
            "View approval requests",
            "My external tile title",
        ]);
        cy.findByText("My description").should('be.visible');
    });

    it('[already migrated] can add a "Form" tile', () => {
        // Default state
        cy.findByRole("region", {'name': "Home tiles configuration"}).should('be.visible');
        cy.findByRole("region", {'name': "Add a new tile"}).should('not.exist');

        // Enter add mode
        cy.findByRole('button', {'name': "Add tile"}).click();
        cy.findByRole("region", {'name': "Add a new tile"}).should('be.visible');
        cy.findByRole("region", {'name': "Home tiles configuration"}).should('not.exist');

        // Set fields
        cy.getDropdownByLabelText('Type').selectDropdownValue('Form');
        cy.getDropdownByLabelText('Target form').selectDropdownValue('Report an issue');

        // Submit
        cy.findByRole('button', {'name': 'Add tile'}).click();
        validateTilesOrder([
            "Browse help articles",
            "Request a service",
            "Make a reservation",
            "View approval requests",
            "Report an issue",
        ]);
        cy.findByText("Ask for support from our helpdesk team.").should('be.visible');
    });

    it('[already migrated] can cancel addding a tile', () => {
        // Default state
        cy.findByRole("region", {'name': "Home tiles configuration"}).should('be.visible');
        cy.findByRole("region", {'name': "Add a new tile"}).should('not.exist');

        // Enter add mode
        cy.findByRole('button', {'name': "Add tile"}).click();
        cy.findByRole("region", {'name': "Add a new tile"}).should('be.visible');
        cy.findByRole("region", {'name': "Home tiles configuration"}).should('not.exist');

        // Set fields
        cy.getDropdownByLabelText('Type').selectDropdownValue('Form');
        cy.getDropdownByLabelText('Target form').selectDropdownValue('Report an issue');

        // Cancel
        cy.findByRole('button', {'name': 'Cancel'}).click();
        validateTilesOrder([
            "Browse help articles",
            "Request a service",
            "Make a reservation",
            "View approval requests",
        ]);
    });
});
