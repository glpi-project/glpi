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

import { randomUUID } from 'crypto';
import { expect, test } from '../../fixtures/glpi_fixture';
import { Profiles } from '../../utils/Profiles';
import { getWorkerEntityId } from '../../utils/WorkerEntities';
import { HelpdeskHomeConfigTag } from '../../pages/HelpdeskConfigTab';

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

const itemtypes_with_helpdesk_tab = [
    {
        label: "profile",
        itemtype: "Profile",
        fields: () => ({
            'name': `Helpdesk profile ${randomUUID()}`,
            'interface': 'helpdesk',
        }),
        url: "/front/profile.form.php?id=${id}&forcetab=Profile$4",
        refresh_session: false,
    },
    {
        label: "entity",
        itemtype: "Entity",
        fields: () => ({
            'name': `Entity ${randomUUID()}`,
            'entities_id': getWorkerEntityId(),
        }),
        url: "/front/entity.form.php?id=${id}&forcetab=Entity$9",
        refresh_session: true, // New entity won't be visible otherwise
    }
];

for (const context of itemtypes_with_helpdesk_tab) {
    test.describe(`Home config tests for ${context.label}`, () => {
        let id: number;

        test.beforeEach(async ({ api, profile }) => {
            // Setup test profile/entity
            id = await api.createItem(context.itemtype, context.fields());
            if (context.refresh_session) {
                api.refreshSession(); // New entity won't be visible otherwise
            }
            await api.asyncCreateTilesForItem(context.itemtype, id, test_tiles);
            await profile.set(Profiles.SuperAdmin);
        });

        test('Can reorder tiles', async ({ page }) => {
            const tab = new HelpdeskHomeConfigTag(page);
            await tab.goto(context.itemtype, id);

            // Validate default order
            await expect(tab.getTileTitles()).toHaveText([
                "Browse help articles",
                "Request a service",
                "Make a reservation",
                "View approval requests",
            ]);
            await tab.doDragAndDropTileAfterTile(
                "Browse help articles",
                "Make a reservation"
            );
            await expect(tab.getTileTitles()).toHaveText([
                "Request a service",
                "Make a reservation",
                "Browse help articles",
                "View approval requests",
            ]);

            // Save new tab order and validate again
            await tab.doSaveTilesOrder();
            await expect(tab.getTileTitles()).toHaveText([
                "Request a service",
                "Make a reservation",
                "Browse help articles",
                "View approval requests",
            ]);
            await expect(tab.getNewTileButton()).toBeVisible();
        });

        test('Can remove tiles', async ({ page }) => {
            const tab = new HelpdeskHomeConfigTag(page);
            await tab.goto(context.itemtype, id);

            // Delete a tile
            await tab.getTile("Request a service").click();
            await tab.doDeleteActiveTile();

            // Make sure it no longer exist
            await expect(tab.getTile("Request a service")).not.toBeAttached();
            await expect(tab.getTileTitles()).toHaveText([
                "Browse help articles",
                "Make a reservation",
                "View approval requests",
            ]);
            await expect(tab.getNewTileButton()).toBeVisible();

            // Refresh page to confirm deletion
            await page.reload();
            await expect(tab.getTileTitles()).toHaveText([
                "Browse help articles",
                "Make a reservation",
                "View approval requests",
            ]);
        });

        test('Can edit tiles', async ({ page }) => {
            const tab = new HelpdeskHomeConfigTag(page);
            await tab.goto(context.itemtype, id);

            // Edit a tile
            await tab.getTile("Request a service").click();
            await tab.doEditActiveTileTitle("My new tile name");
            await tab.doSaveActiveTile();

            // Validate the update
            await expect(tab.getTileTitles()).toHaveText([
                "Browse help articles",
                "My new tile name",
                "Make a reservation",
                "View approval requests",
            ]);
            await expect(tab.getNewTileButton()).toBeVisible();
        });

        test('Can add a "Glpi page" tile', async ({ page }) => {
            const tab = new HelpdeskHomeConfigTag(page);
            await tab.goto(context.itemtype, id);
            await tab.getNewTileButton().click();

            // Set fields
            await tab.doSetActiveTileType("GLPI page");
            await tab.doEditActiveTileTitle("My title");
            await tab.doEditActiveTileDescription("My description");
            await tab.doSetActiveGlpiPageTileTarget("Service catalog");
            await tab.doAddTile();

            // Validate that the new tile is visible
            await expect(tab.getTileTitles()).toHaveText([
                "Browse help articles",
                "Request a service",
                "Make a reservation",
                "View approval requests",
                "My title",
            ]);
            await expect(page.getByText("My description")).toBeVisible();
            await expect(tab.getNewTileButton()).toBeVisible();
        });

        test('Can add an "External page" tile', async ({ page }) => {
            const tab = new HelpdeskHomeConfigTag(page);
            await tab.goto(context.itemtype, id);
            await tab.getNewTileButton().click();

            // Set fields
            await tab.doSetActiveTileType("External page");
            await tab.doEditActiveTileTitle("My external tile title");
            await tab.doEditActiveTileDescription("My description");
            await tab.doSetActiveExternalPageTileUrl("support.teclib.com");
            await tab.doAddTile();

            // Validate that the new tile is visible
            await expect(tab.getTileTitles()).toHaveText([
                "Browse help articles",
                "Request a service",
                "Make a reservation",
                "View approval requests",
                "My external tile title",
            ]);
            await expect(page.getByText("My description")).toBeVisible();
        });

        test('Can add a "Form" tile', async ({ page }) => {
            const tab = new HelpdeskHomeConfigTag(page);
            await tab.goto(context.itemtype, id);
            await tab.getNewTileButton().click();

            // Set fields
            await tab.doSetActiveTileType("Form");
            await tab.doSetActiveFormTileForm("Report an issue");
            await tab.doAddTile();

            // Validate that the new tile is visible
            await expect(tab.getTileTitles()).toHaveText([
                "Browse help articles",
                "Request a service",
                "Make a reservation",
                "View approval requests",
                "Report an issue",
            ]);
            await expect(
                page.getByText("Ask for support from our helpdesk team.")
            ).toBeVisible();
        });
    });
}

test.describe(`Home config specific tests for entities`, () => {
    let id: number;

    test.beforeEach(async ({ api, profile }) => {
        // Setup test entity
        id = await api.createItem('Entity', {
            'name': `Entity ${randomUUID()}`,
            'entities_id': getWorkerEntityId(),
        });
        api.refreshSession(); // New entity won't be visible otherwise
        await profile.set(Profiles.SuperAdmin);
    });

    test('Can configure entity tiles from scratch', async ({ page }) => {
        const tab = new HelpdeskHomeConfigTag(page);
        await tab.gotoForEntity(id);

        // Default state
        await expect(tab.getEmptyEntityTilesMessage()).toBeVisible();
        await expect(tab.getHeading('Browse help articles')).toBeVisible();
        await expect(tab.getNewTileButton()).not.toBeAttached();

        // Replace by our own tiles
        await tab.getDefineEntityTilesButton().click();
        await expect(tab.getNewTileButton()).toBeVisible();
        await expect(tab.getEmptyEntityTilesMessage()).toBeHidden();
        await expect(tab.getHeading('Browse help articles')).toBeHidden();
    });

    test('Can copy tiles from parent entity', async ({ page }) => {
        const tab = new HelpdeskHomeConfigTag(page);
        await tab.gotoForEntity(id);

        // Default state
        await expect(tab.getEmptyEntityTilesMessage()).toBeVisible();
        await expect(tab.getHeading('Browse help articles')).toBeVisible();
        await expect(tab.getNewTileButton()).not.toBeAttached();

        // Copy parent tiles
        await tab.getCopyEntityTilesButton().click();
        await expect(tab.getNewTileButton()).toBeVisible();
        await expect(tab.getEmptyEntityTilesMessage()).toBeHidden();
        await expect(tab.getHeading('Browse help articles')).toBeVisible();
    });

    test('Can configure custom illustrations', async ({ page }) => {
        const tab = new HelpdeskHomeConfigTag(page);
        await tab.gotoForEntity(id);

        // Some headings should be displayed
        await expect(tab.getHeading('Custom illustrations')).toBeVisible();
        await expect(tab.getHeading('Left side')).toBeVisible();
        await expect(tab.getHeading('Right side')).toBeVisible();

        // There should be two dropdowns - one per side
        await expect(
            tab.getDropdownByLabel("Left side configuration")
        ).toBeVisible();
        await expect(
            tab.getDropdownByLabel("Right side configuration")
        ).toBeVisible();

        const left = tab.getRegion("Left side");
        const default_preview = left.getByRole('region', {
            name: "Default illustration preview"
        });
        const custom_preview = left.getByRole('region', {
            name: "Custom illustration preview and selection"
        });
        const inherited_preview = left.getByRole('region', {
            name: "Inherited illustration preview"
        });

        // eslint-disable-next-line playwright/no-raw-locators
        const svg = left.locator('svg').filter({ visible: true });
        // eslint-disable-next-line playwright/no-raw-locators
        const img = left.locator('img').filter({ visible: true });

        // Default state, inheritance should be selected
        await expect(
            tab.getDropdownByLabel("Left side configuration")
        ).toHaveText("Inherited from parent entity");
        await expect(default_preview).toBeHidden();
        await expect(custom_preview).toBeHidden();
        await expect(inherited_preview).toBeVisible();
        await expect(svg).toBeVisible();

        // Switch to "Custom illustration"
        const left_side_dropdown = tab.getDropdownByLabel("Left side configuration");
        await tab.doSetDropdownValue(left_side_dropdown, "Custom illustration");
        await expect(default_preview).toBeHidden();
        await expect(custom_preview).toBeVisible();
        await expect(inherited_preview).toBeHidden();
        await tab.doAddFileToUploadArea("uploads/bar.png", left);
        await tab.doSaveIllustrationSettings();
        await expect(left_side_dropdown).toHaveText("Custom illustration");
        await expect(default_preview).toBeHidden();
        await expect(custom_preview).toBeVisible();
        await expect(inherited_preview).toBeHidden();
        await expect(img).toBeVisible();

        // Use the default illustration
        await tab.doSetDropdownValue(left_side_dropdown, "Default illustration");
        await expect(default_preview).toBeVisible();
        await expect(custom_preview).toBeHidden();
        await expect(inherited_preview).toBeHidden();
        await expect(svg).toBeVisible();
        await tab.doSaveIllustrationSettings();
        await expect(left_side_dropdown).toHaveText("Default illustration");
        await expect(default_preview).toBeVisible();
        await expect(custom_preview).toBeHidden();
        await expect(inherited_preview).toBeHidden();
        await expect(svg).toBeVisible();

        // Go back to inherited value
        await tab.doSetDropdownValue(left_side_dropdown, "Inherited from parent entity");
        await expect(default_preview).toBeHidden();
        await expect(custom_preview).toBeHidden();
        await expect(inherited_preview).toBeVisible();
        await expect(svg).toBeVisible();
        await tab.doSaveIllustrationSettings();
        await expect(left_side_dropdown).toHaveText("Inherited from parent entity");
        await expect(default_preview).toBeHidden();
        await expect(custom_preview).toBeHidden();
        await expect(inherited_preview).toBeVisible();
        await expect(svg).toBeVisible();
    });

    test('Can configure custom titles', async ({ page }) => {
        const tab = new HelpdeskHomeConfigTag(page);
        await tab.gotoForEntity(id);

        // Default state, inheritance should be selected
        const main_title_dropdown = tab.getDropdownByLabel("Main title");
        await expect(main_title_dropdown).toHaveText("Inherited from parent entity");

        const default_preview = tab.getRegion("Default title preview");
        const custom_preview = tab.getRegion("Custom title value");
        const inherited_preview = tab.getRegion("Inherited title preview");
        await expect(default_preview).toBeHidden();
        await expect(custom_preview).toBeHidden();
        await expect(inherited_preview).toBeVisible();
        await expect(inherited_preview.getByRole('textbox')).toHaveValue("How can we help you?");

        // Switch to custom title
        await tab.doSetDropdownValue(main_title_dropdown, "Custom value");
        await expect(default_preview).toBeHidden();
        await expect(custom_preview).toBeVisible();
        await expect(inherited_preview).toBeHidden();
        await custom_preview.getByRole('textbox').fill("My custom title value");
        await tab.doSaveGeneralSettings();
        await expect(main_title_dropdown).toHaveText("Custom value");
        await expect(default_preview).toBeHidden();
        await expect(custom_preview).toBeVisible();
        await expect(inherited_preview).toBeHidden();
        await expect(custom_preview.getByRole('textbox')).toHaveValue("My custom title value");

        // Use the default title
        await tab.doSetDropdownValue(main_title_dropdown, "Default value");
        await expect(default_preview).toBeVisible();
        await expect(custom_preview).toBeHidden();
        await expect(inherited_preview).toBeHidden();
        await expect(default_preview.getByRole('textbox')).toHaveValue("How can we help you?");
        await tab.doSaveGeneralSettings();
        await expect(main_title_dropdown).toHaveText("Default value");
        await expect(default_preview).toBeVisible();
        await expect(custom_preview).toBeHidden();
        await expect(inherited_preview).toBeHidden();
        await expect(default_preview.getByRole('textbox')).toHaveValue("How can we help you?");

        // Go back to inherited value
        await tab.doSetDropdownValue(main_title_dropdown, "Inherited from parent entity");
        await expect(default_preview).toBeHidden();
        await expect(custom_preview).toBeHidden();
        await expect(inherited_preview).toBeVisible();
        await expect(inherited_preview.getByRole('textbox')).toHaveValue("How can we help you?");
        await tab.doSaveGeneralSettings();
        await expect(main_title_dropdown).toHaveText("Inherited from parent entity");
        await expect(default_preview).toBeHidden();
        await expect(custom_preview).toBeHidden();
        await expect(inherited_preview).toBeVisible();
        await expect(inherited_preview.getByRole('textbox')).toHaveValue("How can we help you?");
    });
});
