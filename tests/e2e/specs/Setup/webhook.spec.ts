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
import { WebhookPage } from '../../pages/WebhookPage';

/**
 * A webhook pins the API major version its payload is built against. A new webhook must start on
 * the highest offered major, so that a webhook created through the form and one created
 * programmatically never end up on different versions. The form default and the programmatic
 * default live in two different places (post_getEmpty() and prepareInputForAdd()), are each
 * correct in isolation, and can only disagree once rendered together, which no unit test sees.
 *
 * What this test can and cannot catch today, verified by mutation: with a single pinnable major,
 * the dropdown holds one option, so an empty pre-selection and a correct one render identically.
 * Removing the form default does NOT fail this test yet, and neither would reversing the order the
 * versions are offered in. Both become detectable as soon as a second major ships, which is
 * exactly when they would start to matter.
 *
 * What it does catch now: the field disappearing from the creation form, v1 leaking into the
 * offered versions, and the chosen version not surviving a save.
 */
test('A new webhook is pinned to the highest offered API version', async ({ page, profile }) => {
    await profile.set(Profiles.SuperAdmin);

    const webhook_page = new WebhookPage(page);
    await webhook_page.gotoNew();

    await page.getByRole('textbox', { name: 'Name' }).fill('Pinned version default');
    await webhook_page.doSetDropdownValue(
        webhook_page.getDropdownByLabel('Itemtype'),
        'Ticket'
    );

    // The label carries a help tooltip, so it is matched on its text rather than exactly.
    const version_dropdown = webhook_page.getDropdownByLabel('API version', undefined, false);

    // Read the offered versions from the dropdown itself rather than hardcoding one, so this test
    // keeps its meaning once a new major version ships.
    const offered = await webhook_page.getDropdownOptions(version_dropdown);
    const majors = offered.map((option) => parseInt(option ?? '', 10)).filter(Number.isInteger);
    expect(majors.length).toBeGreaterThan(0);

    // v1 is routed to the legacy API and would resolve no webhook path, so it is never offered.
    expect(majors).not.toContain(1);

    // Deliberately computed, not taken as the last entry: this also catches a reversal of the
    // order the versions are offered in.
    const highest_major = Math.max(...majors).toString();
    await expect(version_dropdown).toContainText(highest_major);

    await page.getByRole('button', { name: 'Add', exact: true }).click();

    // The saved webhook keeps the version the form showed before saving.
    await expect(
        webhook_page.getDropdownByLabel('API version', undefined, false)
    ).toContainText(highest_major);
});
