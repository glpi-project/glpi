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
import { Api } from '../../utils/Api';
import { Profiles } from '../../utils/Profiles';
import { getWorkerEntityId } from '../../utils/WorkerEntities';

test.describe('DC Room', () => {
    const setupDcRoom = async (api: Api): Promise<{
        dcrooms_id: number,
        racks_id_1: number,
        rack_name_1: string,
        rack_name_3: string,
    }> => {
        const uuid = randomUUID();

        const datacenters_id = await api.createItem('Datacenter', {
            name: `DC for E2E ${uuid}`,
            entities_id: getWorkerEntityId(),
        });
        const dcrooms_id = await api.createItem('DCRoom', {
            name: `DC Room for E2E ${uuid}`,
            datacenters_id: datacenters_id,
            entities_id: getWorkerEntityId(),
            vis_cols: 5,
            vis_rows: 5,
        });

        const rack_name_1 = `Rack for E2E 1 ${uuid}`;
        const racks_id_1 = await api.createItem('Rack', {
            name: rack_name_1,
            dcrooms_id: dcrooms_id,
            entities_id: getWorkerEntityId(),
            position: '1,1',
        });
        await api.createItem('Rack', {
            name: `Rack for E2E 2 ${uuid}`,
            dcrooms_id: dcrooms_id,
            entities_id: getWorkerEntityId(),
            position: '1,2',
        });
        const rack_name_3 = `Rack for E2E 3 ${uuid}`;
        await api.createItem('Rack', {
            name: rack_name_3,
            dcrooms_id: dcrooms_id,
            entities_id: getWorkerEntityId(),
            position: '10,10', //Out of bounds
        });

        return { dcrooms_id, racks_id_1, rack_name_1, rack_name_3 };
    };

    test('Graphical view', async ({ page, profile, api }) => {
        // Single test for the view to reduce API calls
        await profile.set(Profiles.SuperAdmin);

        const fixtures = await setupDcRoom(api);

        await page.goto(`/front/dcroom.form.php?id=${fixtures.dcrooms_id}`);
        await page.getByRole('tab', { name: /^Racks/ }).click();
        // This button only holds an icon, its accessible name is empty: it can
        // only be reached through its title.
        await page.getByTitle('View graphical representation').click();

        // We have no control on these selectors, the graphical view has no
        // accessible names.
        // eslint-disable-next-line playwright/no-raw-locators
        await expect(page.locator('table.outbound'))
            .toContainText(fixtures.rack_name_3)
        ;

        // Check loading of new rack modal
        // eslint-disable-next-line playwright/no-raw-locators
        await page.locator('div.cell_add[data-x="2"][data-y="1"]').click();
        const dialog = page.getByRole('dialog');
        await expect(dialog).toHaveAttribute('data-cy-shown', 'true');

        //TODO the heading here should not be level 3
        await expect(dialog.getByRole('heading', { level: 3 }))
            .toContainText('New item - Rack')
        ;
        await dialog.getByRole('button', { name: 'Close' }).click();
        await expect(dialog).not.toBeAttached();

        // Check loading of existing rack
        // eslint-disable-next-line playwright/no-raw-locators
        await page.locator('div.grid-stack-item')
            .filter({ hasText: fixtures.rack_name_1 })
            .click()
        ;
        await expect(page).toHaveURL(
            new RegExp(`/front/rack\\.form\\.php\\?id=${fixtures.racks_id_1}`)
        );
    });
});
