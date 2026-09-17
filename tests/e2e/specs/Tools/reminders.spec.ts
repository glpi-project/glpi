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
import { GlpiPage } from '../../pages/GlpiPage';
import { Profiles } from '../../utils/Profiles';

test.describe('Reminders', () => {
    test('Form loads correctly', async ({ page, profile }) => {
        await profile.set(Profiles.SuperAdmin);
        const glpi_page = new GlpiPage(page);

        await page.goto('/front/reminder.form.php');
        const tabpanel = page.getByRole('tabpanel');

        await expect(tabpanel.getByLabel('Title', { exact: true }))
            .toHaveValue('New note')
        ;

        // eslint-disable-next-line playwright/no-raw-locators
        await expect(tabpanel.locator('input[name="begin_view_date"]')).toBeAttached();
        // eslint-disable-next-line playwright/no-raw-locators
        await expect(tabpanel.locator('input[name="end_view_date"]')).toBeAttached();
        // eslint-disable-next-line playwright/no-raw-locators
        await expect(tabpanel.locator('input[name="plan[begin]"]')).not.toBeAttached();
        await expect(tabpanel.getByRole('radio', { name: 'Time slot' })).not.toBeAttached();

        const description = await glpi_page.getRichTextByLabel(
            'Description',
            tabpanel
        );
        await expect(description).toBeVisible();

        await tabpanel.getByRole('button', { name: 'Add to schedule' }).click();
        // eslint-disable-next-line playwright/no-raw-locators
        await expect(tabpanel.locator('input[name="plan[begin]"]')).toBeAttached();
        // eslint-disable-next-line playwright/no-raw-locators
        await expect(tabpanel.locator('input[name="plan[end]"]')).toBeAttached();

        // Default planning is a time slot, hours must be displayed
        await expect(tabpanel.getByRole('radio', { name: 'Time slot' })).toBeChecked();
        await expect(tabpanel.getByRole('textbox', { name: 'Start time' })).toBeVisible();
        await expect(tabpanel.getByRole('textbox', { name: 'End time' })).toBeVisible();

        // Hours are hidden for an "All day" event, which starts and ends at midnight
        await tabpanel.getByText('All day').click();
        await expect(tabpanel.getByRole('textbox', { name: 'Start time' })).toBeHidden();
        await expect(tabpanel.getByRole('textbox', { name: 'End time' })).toBeHidden();
        // eslint-disable-next-line playwright/no-raw-locators
        await expect(tabpanel.locator('input[name="plan[begin]"]')).toHaveValue(/ 00:00:00$/);
        // eslint-disable-next-line playwright/no-raw-locators
        await expect(tabpanel.locator('input[name="plan[end]"]')).toHaveValue(/ 00:00:00$/);

        // Planning is saved with the note
        await tabpanel.getByRole('button', { name: 'Add', exact: true }).click();
        await expect(page.getByRole('tabpanel').getByText(/^\s*From .* 00:00 to .* 00:00/)).toBeVisible();
    });
});
