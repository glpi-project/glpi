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
import { expect, test } from '../../../fixtures/glpi_fixture';
import { GlpiPage } from '../../../pages/GlpiPage';
import { Api } from '../../../utils/Api';
import { Profiles } from '../../../utils/Profiles';
import { getWorkerEntityId } from '../../../utils/WorkerEntities';

test.describe('Calendar', () => {
    const setupCalendar = async (api: Api): Promise<{
        calendar_id: number,
        holiday_name: string,
    }> => {
        const uuid = randomUUID();

        const calendar_id = await api.createItem('Calendar', {
            name: `Test Calendar ${uuid}`,
            entities_id: getWorkerEntityId(),
        });

        const holiday_name = `Test Holiday ${uuid}`;
        await api.createItem('Holiday', {
            name: holiday_name,
            entities_id: getWorkerEntityId(),
            begin_date: '2025-01-13',
            end_date: '2025-01-14',
            is_perpetual: 1,
        });

        return { calendar_id, holiday_name };
    };

    test('Time range form', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const glpi_page = new GlpiPage(page);

        const { calendar_id } = await setupCalendar(api);
        await page.goto(`/front/calendar.form.php?id=${calendar_id}`);

        await glpi_page.doGoToTab('Time ranges');
        const tabpanel = page.getByRole('tabpanel');

        await glpi_page.doSetDropdownValue(
            glpi_page.getDropdownByLabel('Day', tabpanel),
            'Tuesday'
        );
        await glpi_page.doSetDropdownValue(
            glpi_page.getDropdownByLabel('Start', tabpanel),
            '08:00'
        );
        await glpi_page.doSetDropdownValue(
            glpi_page.getDropdownByLabel('End', tabpanel),
            '18:00'
        );
        await tabpanel.getByRole('button', { name: 'Add' }).click();

        const rows = page.getByRole('tabpanel').getByRole('row');
        await expect(rows).toHaveCount(2);
        await expect(rows.nth(1).getByText('Tuesday')).toBeAttached();
        await expect(rows.nth(1).getByText('08:00:00')).toBeAttached();
        await expect(rows.nth(1).getByText('18:00:00')).toBeAttached();
    });

    test('Close times form', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const glpi_page = new GlpiPage(page);

        const { calendar_id, holiday_name } = await setupCalendar(api);
        await page.goto(`/front/calendar.form.php?id=${calendar_id}`);

        await glpi_page.doGoToTab('Close times');
        const tabpanel = page.getByRole('tabpanel');

        await glpi_page.doSearchAndClickDropdownValue(
            glpi_page.getDropdownByLabel('Add a close time', tabpanel),
            holiday_name
        );
        await tabpanel.getByRole('button', { name: 'Add' }).click();

        const rows = page.getByRole('tabpanel').getByRole('row');
        await expect(rows).toHaveCount(2);
        await expect(rows.nth(1).getByText(holiday_name)).toBeAttached();
        await expect(rows.nth(1).getByText('2025-01-13')).toBeAttached();
        await expect(rows.nth(1).getByText('2025-01-14')).toBeAttached();
        await expect(rows.nth(1).getByText('Yes')).toBeAttached();
    });
});
