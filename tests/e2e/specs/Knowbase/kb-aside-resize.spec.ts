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

test('Resizes the KB aside from its edge handle, width persists', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    await page.setViewportSize({ width: 1280, height: 900 }); // above the 992px breakpoint

    const kb = new KnowbaseItemPage(page);
    const article_id = await api.knowbase.createArticle({
        name: getUniqueName('E2E Resize Article'),
        answer: 'Test content',
    });
    await kb.goto(article_id);

    const handle = kb.getAsideResizer();
    const asideWidth = async () => Math.round((await kb.aside.boundingBox())!.width);
    // The aside may take up to half of its row
    const max = String(Math.round(await kb.aside.evaluate((el) => el.parentElement!.clientWidth / 2)));
    await expect(handle).toHaveAttribute('aria-valuenow', '300');
    await expect(handle).toHaveAttribute('aria-valuemax', max);

    // Keyboard: two steps of 16px
    await handle.focus();
    await page.keyboard.press('ArrowRight');
    await page.keyboard.press('ArrowRight');
    await expect(handle).toHaveAttribute('aria-valuenow', '332');
    await expect.poll(asideWidth).toBe(332);

    // End clamps to the maximum
    await page.keyboard.press('End');
    await expect(handle).toHaveAttribute('aria-valuenow', max);
    await expect.poll(asideWidth).toBe(Number(max)); // wait for the width transition before measuring the handle

    // Pointer drag to 500px from the aside's left edge
    const aside_box = (await kb.aside.boundingBox())!;
    const handle_box = (await handle.boundingBox())!;
    const y = handle_box.y + 100;
    await page.mouse.move(handle_box.x + handle_box.width / 2, y);
    await page.mouse.down();
    await page.mouse.move(aside_box.x + 500, y, { steps: 5 });
    await page.mouse.up();
    await expect(handle).toHaveAttribute('aria-valuenow', '500');
    await expect.poll(asideWidth).toBe(500);

    // Persists across reload
    await page.reload({ waitUntil: 'domcontentloaded' });
    await expect(handle).toHaveAttribute('aria-valuenow', '500');
    await expect.poll(asideWidth).toBe(500);

    // Double-click resets to the default width, also after reload
    await handle.dblclick();
    await expect(handle).toHaveAttribute('aria-valuenow', '300');
    await expect.poll(asideWidth).toBe(300);
    await page.reload({ waitUntil: 'domcontentloaded' });
    await expect.poll(asideWidth).toBe(300);
});

test('Resize handle is hidden when the aside is collapsed', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    await page.setViewportSize({ width: 1280, height: 900 });

    const kb = new KnowbaseItemPage(page);
    const article_id = await api.knowbase.createArticle({
        name: getUniqueName('E2E Resize Article'),
        answer: 'Test content',
    });
    await kb.goto(article_id);

    await expect(kb.getAsideResizer()).toBeVisible();
    await kb.doCollapseAside();
    await expect(kb.getAsideResizer()).toBeHidden();
});
