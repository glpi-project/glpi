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
            await tab.expectTiles([
                "Browse help articles",
                "Request a service",
                "Make a reservation",
                "View approval requests",
            ]);
            await tab.doDragAndDropTileAfterTile(
                "Browse help articles",
                "Make a reservation"
            );
            await tab.expectTiles([
                "Request a service",
                "Make a reservation",
                "Browse help articles",
                "View approval requests",
            ]);

            // Save new tab order and validate again
            await tab.doSaveTilesOrder();
            await tab.expectTiles([
                "Request a service",
                "Make a reservation",
                "Browse help articles",
                "View approval requests",
            ]);

            // Make sure we can still add new tiles (regression prevention)
            await expect(tab.getNewTileButton()).toBeVisible();
        });
    });
}
