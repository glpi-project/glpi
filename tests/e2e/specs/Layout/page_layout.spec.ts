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
import AxeBuilder from '@axe-core/playwright';

test('Page layout accessibility', async ({ page, profile }) => {
    await profile.set(Profiles.SuperAdmin);
    const layout_page = new GlpiPage(page);
    await page.goto('/front/computer.php');

    const sidebar_a11y = await new AxeBuilder({ page })
        .include('[data-testid="sidebar"]')
        .analyze()
    ;
    expect(sidebar_a11y.violations).toEqual([]);

    const visible_toggles = layout_page.sidebar_menu_toggles.filter({ visible: true });
    const toggles_count = await visible_toggles.count();
    for (let i = 1; i < toggles_count; i++) {
        await visible_toggles.nth(i).click();
        await page.waitForFunction(() =>
            document.getAnimations().filter(a => a.playState === 'running').length === 0
        );
        const sidebar_a11y = await new AxeBuilder({ page })
            .include('[data-testid="sidebar"]')
            .disableRules(['accesskeys']) // known issues in sidebar menus
            .analyze()
        ;
        expect(sidebar_a11y.violations).toEqual([]);
    }

    await layout_page.user_menu.click();
    await page.waitForFunction(() =>
        document.getAnimations().filter(a => a.playState === 'running').length === 0
    );
    await expect(layout_page.user_menu_dropdown).toBeVisible();
    const user_menu_a11y = await new AxeBuilder({ page })
        .include('[data-testid="user-menu-dropdown"]')
        .analyze()
    ;
    expect(user_menu_a11y.violations).toEqual([]);

    await layout_page.entity_menu_toggle.click();
    await expect(layout_page.entity_menu_dropdown).toBeVisible();
    const entity_a11y = await new AxeBuilder({ page })
        .include('[data-testid="entity-menu-dropdown"]')
        .disableRules(['aria-command-name', 'empty-table-header']) // known issues in entity tree
        .analyze()
    ;
    expect(entity_a11y.violations).toEqual([]);

    await page.keyboard.press('Escape');
    await expect(layout_page.entity_menu_dropdown).toBeHidden();
    await page.keyboard.press('Escape');
    await expect(layout_page.user_menu_dropdown).toBeHidden();

    const header_a11y = await new AxeBuilder({ page })
        .include('[data-testid="main-header"]')
        .analyze()
    ;
    expect(header_a11y.violations).toEqual([]);
});

test('About link visibility by profile', async ({ page, profile }) => {
    const layout_page = new GlpiPage(page);

    const cannot_see = [
        Profiles.SelfService,
        Profiles.Observer,
        Profiles.Technician,
        Profiles.Hotliner,
        Profiles.Admin,
    ];
    const can_see = [Profiles.SuperAdmin];

    for (const p of cannot_see) {
        await profile.set(p);
        await page.goto('/');
        await layout_page.user_menu.click();
        await expect(layout_page.about_link).toBeHidden();
    }
    for (const p of can_see) {
        await profile.set(p);
        await page.goto('/');
        await layout_page.user_menu.click();
        await expect(layout_page.about_link).toBeVisible();
    }
});
