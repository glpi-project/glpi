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
import { ServiceCatalogPage } from '../../pages/ServiceCatalogPage';

test(`Can filter and go to a form using the service catalog`, async ({page, profile}) => {
    // Go to the service catalog
    await profile.set(Profiles.SelfService);
    const service_catalog = new ServiceCatalogPage(page);
    await service_catalog.goto();

    // Search and go to a specific form
    await service_catalog.doSearchItem('Request a service');
    await service_catalog.doGoToItem('Request a service');
    await expect(page).toHaveURL('/Form/Render/2');
});

test(`Can filter and go to a KB item using the service catalog`, async ({page, profile, api}) => {
    // Create a KB entry
    const kb = `My KB entry ${randomUUID()}`;
    await api.createItem('KnowbaseItem', {
        'name': kb,
        'answer': `Content for ${kb}`,
        'description': `Description for ${kb}`,
        'is_faq': 1,
        'show_in_service_catalog': 1,
        '_visibility': {
            '_type': 'Entity',
            'entities_id': getWorkerEntityId(),
            'is_recursive': 1,
        }
    });

    // Go to the service catalog
    await profile.set(Profiles.SelfService);
    const service_catalog = new ServiceCatalogPage(page);
    await service_catalog.goto();

    // Search and go to a specific KB
    await service_catalog.doSearchItem(kb);
    await service_catalog.doGoToItem(kb);
    await expect(page).toHaveURL(/\/front\/helpdesk.faq.php/);
});

test(`Search with no results`, async ({page, profile}) => {
    // Go to the service catalog
    await profile.set(Profiles.SelfService);
    const service_catalog = new ServiceCatalogPage(page);
    await service_catalog.goto();

    // Make an impossible search
    await service_catalog.doSearchItem("AAAAAAAAAAAAAAAAAA");
    await expect(page.getByText('No forms found')).toBeVisible();
});

