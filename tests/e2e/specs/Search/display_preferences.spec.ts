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
import { Profiles } from '../../utils/Profiles';
import type { FrameLocator, Locator } from '@playwright/test';

// The Global View and Personal View forms are rendered as bootstrap tabs of
// the same page: once loaded, both stay in the DOM at the same time, only
// one being visible. The "add option" dropdown of each form must only look
// at its own container to hide already added options, not at the whole page.

function getAddOptionDropdown(frame: FrameLocator): Locator
{
    // Select2 hides the original, labelled <select> and renders the visible
    // combobox into a sibling <span>.
    // eslint-disable-next-line playwright/no-raw-locators
    return frame
        .getByLabel('Select an option to add', { exact: true })
        .locator('+ span')
        .getByRole('combobox')
    ;
}

async function goToTab(frame: FrameLocator, name: string): Promise<void>
{
    // The modal iframe is narrower than the "md" breakpoint, so GLPI renders
    // its tabs as a <select> (mobile layout) instead of the usual nav-tabs.
    // eslint-disable-next-line playwright/no-raw-locators
    await frame.locator('#tabspanel-select').selectOption({ label: name });
}

async function getAddOptionChoices(frame: FrameLocator): Promise<string[]>
{
    const dropdown = getAddOptionDropdown(frame);
    await dropdown.click();
    const options = await frame.getByRole('listbox').getByRole('option').all();
    const texts = await Promise.all(options.map((option) => option.textContent()));
    await dropdown.click(); // Close the dropdown without selecting anything
    return texts
        .map((text) => (text ?? '').trim())
        .filter((text) => text.length > 0)
    ;
}

// The list rows are draggable <li> elements whose ARIA role is toggled
// between "listitem" and "option" by the sortable library depending on
// whether they went through its (re)initialization, so it cannot be relied
// on to find a specific row: match on the "data-opt-id" attribute instead.
function getOptionRow(frame: FrameLocator, name: string): Locator
{
    // eslint-disable-next-line playwright/no-raw-locators
    return frame.locator('li[data-opt-id]').filter({ hasText: name });
}

async function addOption(frame: FrameLocator, name: string): Promise<void>
{
    const dropdown = getAddOptionDropdown(frame);
    await dropdown.click();
    await frame.getByRole('listbox').getByRole('option', { name: name, exact: true }).click();
    await frame.getByRole('button', { name: 'Add' }).click();
    await expect(getOptionRow(frame, name)).toBeVisible();
}

async function removeOptionIfPresent(frame: FrameLocator, tab: string, name: string): Promise<void>
{
    await goToTab(frame, tab);
    const row = getOptionRow(frame, name);
    if (await row.count() === 0) {
        return;
    }
    // The remove button is icon-only and, unlike other icon buttons in this
    // form, its accessible name does not fall back to its "title" attribute,
    // so it cannot be matched by name; each row only has one button though.
    await row.getByRole('button').click();
    await expect(row).toBeHidden();
}

/**
 * Personal preferences do not exist until explicitly activated. Make sure
 * they are, so that the personal form is rendered instead of the
 * "Create personal parameters?" prompt.
 *
 * @returns true if the personal view was created by this call.
 */
async function ensurePersonalViewExists(frame: FrameLocator): Promise<boolean>
{
    await goToTab(frame, 'Personal View');
    const create_button = frame.getByRole('button', { name: 'Create' });
    const add_dropdown = getAddOptionDropdown(frame);
    await expect(create_button.or(add_dropdown)).toBeVisible();

    const has_create_button = await create_button.isVisible();
    if (!has_create_button) {
        return false;
    }

    await create_button.click();
    await expect(add_dropdown).toBeVisible();
    return true;
}

async function deletePersonalView(frame: FrameLocator): Promise<void>
{
    await goToTab(frame, 'Personal View');
    await frame.getByRole('button', { name: 'Delete personal view', exact: true }).click();
}

async function deletePersonalViewIfCreated(frame: FrameLocator, created: boolean): Promise<void>
{
    if (!created) {
        return;
    }
    await deletePersonalView(frame);
}

test('Global and personal display preference forms have independent "add option" dropdowns', async ({ page, profile }) => {
    await profile.set(Profiles.SuperAdmin);
    await page.goto('/front/computer.php');

    await page.getByRole('button', { name: 'Select default items to show' }).click();
    await expect(page.getByRole('dialog')).toBeVisible();
    const frame = page.frameLocator('[data-testid="display-preference-iframe"]');

    const created_personal_view = await ensurePersonalViewExists(frame);
    const personal_choices = await getAddOptionChoices(frame);

    await goToTab(frame, 'Global View');
    const global_choices = await getAddOptionChoices(frame);

    // Only rely on options available on both forms, so the test is not
    // affected by whatever is already configured on either of them.
    const common_choices = personal_choices.filter((choice) => global_choices.includes(choice));
    expect(common_choices.length).toBeGreaterThanOrEqual(2);
    const [personal_only_option, global_only_option] = common_choices;

    try {
        // Add an option on the personal view only.
        await goToTab(frame, 'Personal View');
        await addOption(frame, personal_only_option);

        // It must still be selectable on the global view: it must not be
        // hidden there just because it was added on the personal view.
        await goToTab(frame, 'Global View');
        expect(await getAddOptionChoices(frame)).toContain(personal_only_option);

        // Add a different option on the global view only.
        await addOption(frame, global_only_option);

        // It must still be selectable on the personal view: it must not be
        // hidden there just because it was added on the global view.
        await goToTab(frame, 'Personal View');
        expect(await getAddOptionChoices(frame)).toContain(global_only_option);
    } finally {
        // Best-effort cleanup so the suite stays idempotent for other runs.
        await removeOptionIfPresent(frame, 'Global View', global_only_option);
        await removeOptionIfPresent(frame, 'Personal View', personal_only_option);
        await deletePersonalViewIfCreated(frame, created_personal_view);
    }
});
