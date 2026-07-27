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

import { test, expect } from '../../fixtures/glpi_fixture';
import { Profiles } from '../../utils/Profiles';
import { PlanningPage } from '../../pages/PlanningPage';

test.describe('Planning View', () => {
    test.describe.configure({ mode: 'serial' });
    let shared_planning_page: PlanningPage;

    test.beforeAll(async ({ browser, profile }) => {
        const page = await browser.newPage();
        await profile.set(Profiles.SuperAdmin);
        shared_planning_page = new PlanningPage(page);
        await shared_planning_page.goto();
    });

    test('Planning view fullcalendar loads', async () => {
        // eslint-disable-next-line playwright/no-raw-locators
        await expect(shared_planning_page.page.locator('div.fc')).toBeVisible();
    });

    /* eslint-disable-next-line playwright/expect-expect -- expects are done in fillNewEventForm */
    test('Create event', async () => {
        await shared_planning_page.gotoView('month');
        await shared_planning_page.page.getByRole('gridcell', { name: /1/ }).first().click();
        await shared_planning_page.fillNewEventForm({
            name: 'Test event from month view',
            description: 'Test description',
            start_time: '10:00',
            period: '1h30',
        });
    });
});
