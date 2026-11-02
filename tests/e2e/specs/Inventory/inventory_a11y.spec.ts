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
import { GlpiPage } from '../../pages/GlpiPage';
import { Profiles } from '../../utils/Profiles';
import { a11yScan } from '../../utils/Accessibility';

/**
 * Accessibility of the native inventory pages, which had no coverage at all.
 *
 * These pages were recently moved to Twig and are in reasonable shape, so the value here is
 * mostly non-regression: the assertions pin down names and states that have no visible effect
 * and would otherwise be easy to drop again.
 *
 * Two neighbouring pages are deliberately not covered, because reaching them is not something
 * a test can do without side effects:
 *
 *  - the "Import from file" tab only exists once `enabled_inventory` is on
 *    (`Glpi\Inventory\Conf::getTabNameForItem()`), and that setting is global, so switching it
 *    on would leak into every test running in parallel;
 *  - the agent form needs an agent, and agents cannot be created through the API: `deviceid`
 *    and `name` are `readOnly` in the `Agent` schema
 *    (`Glpi\Api\HL\Controller\InventoryController`), since real ones are created by the
 *    agent protocol itself.
 */

test('Inventory configuration accessibility', async ({ page, profile }) => {
    await profile.set(Profiles.SuperAdmin);
    await page.goto('/Inventory/Configuration');

    const config_a11y = await a11yScan(page).include('main').analyze();
    expect(config_a11y.violations).toEqual([]);

    // Turning this switch off hides the whole rest of the form, so it has to report that it
    // controls a region and whether that region is currently shown.
    const enable_switch = new GlpiPage(page).getCheckbox('Inventory enabled');
    await expect(enable_switch).toHaveAttribute('aria-controls', 'bloc_enabled');

    const was_enabled = await enable_switch.isChecked();
    await expect(enable_switch).toHaveAttribute(
        'aria-expanded',
        was_enabled ? 'true' : 'false'
    );

    // Toggled in the DOM only: the form is never submitted, as this setting is global and
    // shared with every other test running in parallel.
    await enable_switch.click();
    await expect(enable_switch).toHaveAttribute(
        'aria-expanded',
        was_enabled ? 'false' : 'true'
    );
    await enable_switch.click();
    await expect(enable_switch).toHaveAttribute(
        'aria-expanded',
        was_enabled ? 'true' : 'false'
    );
});
