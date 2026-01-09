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
import { test, expect } from '../../fixtures/glpi_fixture';
import { Profiles } from '../../utils/Profiles';
import { getWorkerEntityId } from '../../utils/WorkerEntities';

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

test(`Helpdesk home page configuration for entities`, async ({
    page,
    profile,
    api,
}) => {
    // Create an entity
    await profile.set(Profiles.SuperAdmin);
    const entity_id = await api.createItem('Entity', {
        'name': `Entity ${randomUUID()}`,
        'entities_id': getWorkerEntityId(),
    });
    api.refreshSession(); // New entity won't be visible otherwise

    // Create a few tiles
    const created_tiles = [];
    for (const tile of test_tiles) {
        created_tiles.push(
            api.createItem('Glpi\\Helpdesk\\Tile\\GlpiPageTile', tile)
        );
    }
    const tile_ids = await Promise.all(created_tiles);

    const linked_tiles = [];
    let i = 0;
    for (const tile_id of tile_ids) {
        linked_tiles.push(api.createItem('Glpi\\Helpdesk\\Tile\\Item_Tile', {
            'itemtype_item': 'Entity',
            'items_id_item': entity_id,
            'itemtype_tile': 'Glpi\\Helpdesk\\Tile\\GlpiPageTile',
            'items_id_tile': tile_id,
            'rank': i++,
        }));
    }
    await Promise.all(linked_tiles);
});
