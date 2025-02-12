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

import { test, expect } from '../../../fixtures/authenticated';
import { HomeConfigPage } from '../../../pages/helpdesk/HomeConfigPage';
import { GlpiApi } from '../../../utils/GlpiApi';
import { SessionManager } from '../../../utils/SessionManager';

let config_page: HomeConfigPage;
let profile_id: number;

test.beforeEach(async ({ page, request }) => {
    // Load super admin profile
    const session = new SessionManager(request);
    await session.changeProfile("Super-Admin");

    // Create a test profile with a few tiles
    const glpi_api = new GlpiApi();
    profile_id = await glpi_api.createItem('Profile', {
        name: 'Helpdesk profile for e2e tests',
        interface: 'helpdesk',
    });
    [
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
        }
    ].forEach(async (tile, i) => {
        const tile_id = await glpi_api.createItem('Glpi\\Helpdesk\\Tile\\GlpiPageTile', tile);
        await glpi_api.createItem('Glpi\\Helpdesk\\Tile\\Profile_Tile', {
            profiles_id: profile_id,
            itemtype: 'Glpi\\Helpdesk\\Tile\\GlpiPageTile',
            items_id: tile_id,
            rank: i,
        });
    });

    // Load POM object and go to page
    config_page = new HomeConfigPage(page);
    await config_page.goto(profile_id);
    await config_page.fixHtmlSortableRoles();
});

test('can remove tiles', async ({ page }) => {
    // Make sure the tile exist before trying to delete it
    await expect(config_page.getTile("Request a service")).toBeVisible();
    await config_page.deleteTile("Request a service");
    await expect(config_page.getTile("Request a service")).not.toBeAttached();

    // Refresh page and confirm deletion
    await page.reload();
    await expect(config_page.getTile("Request a service")).not.toBeAttached();
});

test('can edit a tile', async () => {
    // UI should not be in edition mode by default
    await expect(config_page.getTilesArea()).toBeVisible();
    await expect(config_page.getEditionArea()).toBeHidden();

    // Enter edition mode
    await config_page.goToEditTile("Request a service");
    await expect(config_page.getTilesArea()).toBeHidden();
    await expect(config_page.getEditionArea()).toBeVisible();

    // Change the title
    await config_page.setTileTitle("My new tile name");
    await config_page.saveTileChanges();

    // Tile name should be unchanged
    await expect(config_page.getTilesArea()).toBeVisible();
    await expect(config_page.getEditionArea()).toBeHidden();
    await expect(config_page.getTile("My new tile name")).toBeVisible();
    await expect(config_page.getTile("Request a service")).not.toBeAttached();
});

test('can cancel editing a tile', async () => {
    // UI should not be in edition mode by default
    await expect(config_page.getTilesArea()).toBeVisible();
    await expect(config_page.getEditionArea()).toBeHidden();

    // Enter edition mode
    await config_page.goToEditTile("Request a service");
    await expect(config_page.getTilesArea()).toBeHidden();
    await expect(config_page.getEditionArea()).toBeVisible();

    // Change the title
    await config_page.setTileTitle("My new tile name");
    await config_page.cancelEdition();

    // Original tile should be unchanged
    await expect(config_page.getTilesArea()).toBeVisible();
    await expect(config_page.getEditionArea()).toBeHidden();
    await expect(config_page.getTile("Request a service")).toBeVisible();
});

test('can add a "Glpi page" tile', async ({ page }) => {
    // UI should not be in insertion mode by default
    await expect(config_page.getTilesArea()).toBeVisible();
    await expect(config_page.getInsertionArea()).toBeHidden();

    // Enter insertion mode
    await config_page.addNewTile();
    await expect(config_page.getTilesArea()).toBeHidden();
    await expect(config_page.getInsertionArea()).toBeVisible();

    // Set fields
    await config_page.setTileType("GLPI page");
    await config_page.setTileTitle("My new tile");
    await config_page.setTileDescription("My new tile description");
    await config_page.setGlpiTileTargetPage("Service catalog");

    // Submit and confirm new tile was added
    await config_page.submitNewTile();
    await expect(config_page.getTilesArea()).toBeVisible();
    await expect(config_page.getInsertionArea()).toBeHidden();
    await expect(config_page.getTile("My new tile")).toBeVisible();
    await expect(page.getByText("My new tile description")).toBeVisible();
});

test('can add an "External page" tile', async ({ page }) => {
    // UI should not be in insertion mode by default
    await expect(config_page.getTilesArea()).toBeVisible();
    await expect(config_page.getInsertionArea()).toBeHidden();

    // Enter insertion mode
    await config_page.addNewTile();
    await expect(config_page.getTilesArea()).toBeHidden();
    await expect(config_page.getInsertionArea()).toBeVisible();

    // Set fields
    await config_page.setTileType("External page");
    await config_page.setTileTitle("My external page tile");
    await config_page.setTileDescription("My external page tile description");
    await config_page.setExternalTileTargetUrl("support.teclib.com");

    // Submit and confirm new tile was added
    await config_page.submitNewTile();
    await expect(config_page.getTilesArea()).toBeVisible();
    await expect(config_page.getInsertionArea()).toBeHidden();
    await expect(config_page.getTile("My external page tile")).toBeVisible();
    await expect(page.getByText("My external page tile description")).toBeVisible();
});

test('can add a "Form" tile', async ({ page }) => {
    // UI should not be in insertion mode by default
    await expect(config_page.getTilesArea()).toBeVisible();
    await expect(config_page.getInsertionArea()).toBeHidden();

    // Enter insertion mode
    await config_page.addNewTile();
    await expect(config_page.getTilesArea()).toBeHidden();
    await expect(config_page.getInsertionArea()).toBeVisible();

    // Set fields
    await config_page.setTileType("Form");
    await config_page.setFormTileTargetForm("Report an issue");

    // Submit and confirm new tile was added
    await config_page.submitNewTile();
    await expect(config_page.getTilesArea()).toBeVisible();
    await expect(config_page.getInsertionArea()).toBeHidden();
    await expect(config_page.getTile("Report an issue")).toBeVisible();
    await expect(page.getByText("Ask for support from our helpdesk team")).toBeVisible();
});

test('can cancel adding a tile', async ({ page }) => {
    // UI should not be in insertion mode by default
    await expect(config_page.getTilesArea()).toBeVisible();
    await expect(config_page.getInsertionArea()).toBeHidden();

    // Enter insertion mode
    await config_page.addNewTile();
    await expect(config_page.getTilesArea()).toBeHidden();
    await expect(config_page.getInsertionArea()).toBeVisible();

    // Set fields
    await config_page.setTileType("Form");
    await config_page.setFormTileTargetForm("Report an issue");

    // Cancel and confirm new tile was not added
    await config_page.cancelNewTile();
    await expect(config_page.getTilesArea()).toBeVisible();
    await expect(config_page.getInsertionArea()).toBeHidden();
    await expect(config_page.getTile("Report an issue")).not.toBeAttached();
    await expect(page.getByText("Ask for support from our helpdesk team")).not.toBeAttached();
});
