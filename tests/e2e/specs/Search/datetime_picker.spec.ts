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
import { SearchEnginePage } from '../../pages/SearchEnginePage';
import { Profiles } from '../../utils/Profiles';

test.describe('Datetime picker in search criteria', () => {
    test('Time toggle defaults to unchecked and toggles flatpickr time mode', async ({ page, profile }) => {
        await profile.set(Profiles.SuperAdmin);

        const search = new SearchEnginePage(page);
        await search.gotoTicketWithDatetimeCriteria();

        // Open the filters panel so the AJAX-rendered criteria are visible
        await search.doOpenSearchFilters();

        // Wait for the AJAX-rendered date picker
        await expect(search.datetime_time_toggle).toBeVisible();

        // Default: time toggle unchecked (date-only, no forced H:i:S)
        await expect(search.datetime_time_toggle).not.toBeChecked();

        // Open calendar: no time fields in date-only mode
        await search.doOpenDatetimeCalendar();
        // eslint-disable-next-line playwright/no-raw-locators
        await expect(page.locator('.flatpickr-time')).not.toBeAttached();
        await search.doOpenDatetimeCalendar(); // toggle close

        // Enable time
        await search.datetime_time_toggle.click();
        await expect(search.datetime_time_toggle).toBeChecked();

        // Open calendar: time fields now visible
        await search.doOpenDatetimeCalendar();
        // eslint-disable-next-line playwright/no-raw-locators
        await expect(page.locator('.flatpickr-time')).toBeVisible();
        await search.doOpenDatetimeCalendar(); // toggle close

        // Disable time
        await search.datetime_time_toggle.click();
        await expect(search.datetime_time_toggle).not.toBeChecked();
    });
});
