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

import { Page } from '@playwright/test';
import { test, expect } from '../../fixtures/glpi_fixture';
import { GlpiPage } from '../../pages/GlpiPage';
import { Profiles } from '../../utils/Profiles';

/**
 * Screen readers announce the option referenced by the `aria-activedescendant`
 * attribute of the focused element.
 */
async function expectHighlightedOptionToBeAnnounced(page: Page, previous_id: string | null = null): Promise<string>
{
    // eslint-disable-next-line playwright/no-raw-locators
    const focused = page.locator(':focus');
    await expect(focused).toHaveAttribute('aria-activedescendant', /.+/);
    const active_id = await focused.getAttribute('aria-activedescendant') ?? '';
    expect(active_id).not.toBe(previous_id);

    // eslint-disable-next-line playwright/no-raw-locators
    const active_option = page.locator(`[id="${active_id}"]`);
    await expect(active_option).toHaveRole('option');
    await expect(active_option).toHaveClass(/select2-results__option--highlighted/);

    return active_id;
}

test('options of an AJAX dropdown are announced while navigating with the keyboard', async ({ page, profile }) => {
    await profile.set(Profiles.SuperAdmin);
    const glpi_page = new GlpiPage(page);
    await page.goto('/front/ticket.form.php');

    const dropdown = glpi_page.getDropdownByLabel('Category');
    await dropdown.focus();
    await page.keyboard.press('Enter');
    await expect(page.getByRole('listbox').getByRole('option').first()).toBeVisible();
    await expect(page.getByRole('option', { name: 'Searching…' })).toBeHidden();

    // The focus must not be lost when the search field is hidden because there are few results
    const first_id = await expectHighlightedOptionToBeAnnounced(page);
    await page.keyboard.press('ArrowDown');
    await expectHighlightedOptionToBeAnnounced(page, first_id);
});

test('options of an AJAX dropdown are announced while searching with the keyboard', async ({ page, profile }) => {
    await profile.set(Profiles.SuperAdmin);
    const glpi_page = new GlpiPage(page);
    await page.goto('/front/computer.form.php');

    const dropdown = glpi_page.getDropdownByLabel('Technician in charge');
    await dropdown.focus();
    await page.keyboard.press('Enter');
    await page.keyboard.type('E2E worker');
    await expect(page.getByRole('option', { name: 'E2E worker account 02' })).toBeVisible();
    // eslint-disable-next-line playwright/no-raw-locators
    await expect(page.locator(':focus')).toHaveRole('searchbox');

    const first_id = await expectHighlightedOptionToBeAnnounced(page);
    await page.keyboard.press('ArrowDown');
    await expectHighlightedOptionToBeAnnounced(page, first_id);
});
