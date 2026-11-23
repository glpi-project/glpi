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
import { DisplayPreferencePage } from '../../pages/DisplayPreferencePage';
import { Profiles } from '../../utils/Profiles';

// The Global View and Personal View forms are rendered as bootstrap tabs of
// the same page: once loaded, both stay in the DOM at the same time, only
// one being visible. The "add option" dropdown of each form must only look
// at its own container to hide already added options, not at the whole page.
test('Global and personal display preference forms have independent "add option" dropdowns', async ({ page, profile }) => {
    await profile.set(Profiles.SuperAdmin);
    await page.goto('/front/computer.php');

    const preferences = new DisplayPreferencePage(page);
    const frame = await preferences.open();

    const created_personal_view = await preferences.ensurePersonalViewExists(frame);
    const personal_choices = await preferences.getAddOptionChoices(frame);

    await preferences.goToTab(frame, 'Global View');
    const global_choices = await preferences.getAddOptionChoices(frame);

    // Only rely on options available on both forms, so the test is not
    // affected by whatever is already configured on either of them.
    const common_choices = personal_choices.filter((choice) => global_choices.includes(choice));
    expect(common_choices.length).toBeGreaterThanOrEqual(2);
    const [personal_only_option, global_only_option] = common_choices;

    try {
        // Add an option on the personal view only.
        await preferences.goToTab(frame, 'Personal View');
        await preferences.addOption(frame, personal_only_option);

        // It must still be selectable on the global view: it must not be
        // hidden there just because it was added on the personal view.
        await preferences.goToTab(frame, 'Global View');
        expect(await preferences.getAddOptionChoices(frame)).toContain(personal_only_option);

        // Add a different option on the global view only.
        await preferences.addOption(frame, global_only_option);

        // It must still be selectable on the personal view: it must not be
        // hidden there just because it was added on the global view.
        await preferences.goToTab(frame, 'Personal View');
        expect(await preferences.getAddOptionChoices(frame)).toContain(global_only_option);
    } finally {
        // Best-effort cleanup so the suite stays idempotent for other runs.
        await preferences.removeOptionIfPresent(frame, 'Global View', global_only_option);
        await preferences.removeOptionIfPresent(frame, 'Personal View', personal_only_option);
        await preferences.deletePersonalViewIfCreated(frame, created_personal_view);
    }
});
