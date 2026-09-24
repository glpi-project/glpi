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
import { expect, test } from '../../fixtures/glpi_fixture';
import { ReminderPage } from '../../pages/ReminderPage';
import { Profiles } from '../../utils/Profiles';

test.describe('Reminders', () => {
    test('Form loads correctly', async ({ page, profile }) => {
        await profile.set(Profiles.SuperAdmin);
        const reminder_page = new ReminderPage(page);

        await reminder_page.goto();
        const tabpanel = reminder_page.form;

        await expect(reminder_page.title_input).toHaveValue('New note');

        // eslint-disable-next-line playwright/no-raw-locators
        await expect(tabpanel.locator('input[name="begin_view_date"]')).toBeAttached();
        // eslint-disable-next-line playwright/no-raw-locators
        await expect(tabpanel.locator('input[name="end_view_date"]')).toBeAttached();
        await expect(reminder_page.plan_begin_input).not.toBeAttached();
        await expect(reminder_page.time_slot_radio).not.toBeAttached();

        const description = await reminder_page.getRichTextByLabel(
            'Description',
            tabpanel
        );
        await expect(description).toBeVisible();

        await reminder_page.doAddToSchedule();
        await expect(reminder_page.plan_begin_input).toBeAttached();
        await expect(reminder_page.plan_end_input).toBeAttached();

        // Default planning is a time slot, hours must be displayed
        await expect(reminder_page.time_slot_radio).toBeChecked();
        await expect(reminder_page.start_time_input).toBeVisible();
        await expect(reminder_page.end_time_input).toBeVisible();

        // Hours are hidden for an "All day" event, which starts and ends at midnight
        await reminder_page.doSwitchToAllDay();
        await expect(reminder_page.all_day_radio).toBeChecked();
        await expect(reminder_page.start_time_input).toBeHidden();
        await expect(reminder_page.end_time_input).toBeHidden();
        await expect(reminder_page.plan_begin_input).toHaveValue(/ 00:00:00$/);
        await expect(reminder_page.plan_end_input).toHaveValue(/ 00:00:00$/);

        // Planning is saved with the note
        await reminder_page.doSave();
        await expect(reminder_page.getPlanningSummary()).toContainText(/00:00 to .* 00:00/);
    });
});
