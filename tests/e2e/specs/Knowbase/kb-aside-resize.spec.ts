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
import { KnowbaseItemPage } from '../../pages/KnowbaseItemPage';
import { Profiles } from '../../utils/Profiles';
import { getUniqueName } from '../../utils/Random';

let kb: KnowbaseItemPage;

test.beforeEach(async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    await page.setViewportSize({ width: 1280, height: 900 }); // above the 992px breakpoint

    kb = new KnowbaseItemPage(page);
    const article_id = await api.knowbase.createArticle({
        name: getUniqueName('E2E Resize Article'),
        answer: 'Test content',
    });
    await kb.goto(article_id);
});

test('Resizes the KB aside from its edge handle, width persists', async ({ page }) => {
    const handle = kb.aside_resizer;
    const aside_width = async () => Math.round((await kb.aside.boundingBox())!.width);
    // The aside may take up to half of its row
    const max = String(Math.round(await kb.aside.evaluate((el) => el.parentElement!.clientWidth / 2)));
    await expect(handle).toHaveAttribute('aria-valuenow', '300');
    await expect(handle).toHaveAttribute('aria-valuemax', max);

    // Keyboard: two steps of 16px
    await kb.doResizeAsideWithKeyboard('ArrowRight', 'ArrowRight');
    await expect(handle).toHaveAttribute('aria-valuenow', '332');
    await expect.poll(aside_width).toBe(332);

    // End clamps to the maximum
    await kb.doResizeAsideWithKeyboard('End');
    await expect(handle).toHaveAttribute('aria-valuenow', max);
    await expect.poll(aside_width).toBe(Number(max)); // wait for the width transition before measuring the handle

    // Pointer drag to 500px from the aside's left edge
    await kb.doDragAsideToWidth(500);
    await expect(handle).toHaveAttribute('aria-valuenow', '500');
    await expect.poll(aside_width).toBe(500);

    // Persists across reload
    await page.reload({ waitUntil: 'domcontentloaded' });
    await expect(handle).toHaveAttribute('aria-valuenow', '500');
    await expect.poll(aside_width).toBe(500);

    // Double-click resets to the default width, also after reload
    await kb.doResetAsideWidth();
    await expect(handle).toHaveAttribute('aria-valuenow', '300');
    await expect.poll(aside_width).toBe(300);
    await page.reload({ waitUntil: 'domcontentloaded' });
    await expect.poll(aside_width).toBe(300);
});

test('Resize handle is hidden when the aside is collapsed', async () => {
    await expect(kb.aside_resizer).toBeVisible();
    await kb.doCollapseAside();
    await expect(kb.aside_resizer).toBeHidden();
});
