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

test.describe('User menu trigger', () => {
    test.beforeEach(async ({ page, profile }) => {
        await profile.set(Profiles.SuperAdmin);
        await page.goto('/front/central.php');
    });

    test('exposes button role and disclosure states', async ({ page }) => {
        const trigger = page.getByRole('button', { name: 'User menu' });

        await expect(trigger).toBeVisible();
        await expect(trigger).toHaveAttribute('aria-haspopup', 'true');
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');

        await trigger.click();
        await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        await expect(page.getByTestId('user-menu-dropdown').filter({ visible: true })).toBeVisible();
    });

    test('opens with the keyboard', async ({ page }) => {
        const trigger = page.getByRole('button', { name: 'User menu' });
        await trigger.focus();
        await trigger.press('Space');

        await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        await expect(page.getByTestId('user-menu-dropdown').filter({ visible: true })).toBeVisible();
    });
});
