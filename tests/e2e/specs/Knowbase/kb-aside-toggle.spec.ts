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

async function createArticle(api: any): Promise<number> {
    return api.knowbase.createArticle({
        name: getUniqueName('E2E Toggle Article'),
        answer: 'Test content',
    });
}

test('Collapses and expands the KB aside on desktop, state persists', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    await page.setViewportSize({ width: 1280, height: 900 }); // above the 992px breakpoint

    const kb = new KnowbaseItemPage(page);
    const article_id = await createArticle(api);
    await kb.goto(article_id);

    // Expanded by default
    await expect(kb.getAsideTitle()).toBeVisible();
    await expect(kb.getAsideCollapseButton()).toBeVisible();
    await expect(kb.getAsideCollapseButton()).toHaveAttribute('aria-expanded', 'true');

    // Collapse — body hidden, rail button shown
    await kb.doCollapseAside();
    await expect(kb.getAsideTitle()).toBeHidden();
    await expect(kb.getAsideExpandButton()).toBeVisible();

    // Persists across reload
    await page.reload({ waitUntil: 'domcontentloaded' });
    await expect(kb.getAsideTitle()).toBeHidden();
    await expect(kb.getAsideExpandButton()).toBeVisible();

    // Expand again from the rail
    await kb.doExpandAside();
    await expect(kb.getAsideTitle()).toBeVisible();
    await expect(kb.getAsideCollapseButton()).toHaveAttribute('aria-expanded', 'true');
});

test('KB aside opens as a sliding overlay on narrow screens', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    await page.setViewportSize({ width: 800, height: 900 }); // below the 992px breakpoint

    const kb = new KnowbaseItemPage(page);
    const article_id = await createArticle(api);
    await kb.goto(article_id);

    // Overlay closed by default: rail visible, body hidden
    await expect(kb.getAsideExpandButton()).toBeVisible();
    await expect(kb.getAsideTitle()).toBeHidden();

    // Open the overlay from the rail
    await kb.doExpandAside();
    await expect(kb.getAsideTitle()).toBeVisible();

    // Escape closes the overlay
    await page.keyboard.press('Escape');
    await expect(kb.getAsideTitle()).toBeHidden();
    await expect(kb.getAsideExpandButton()).toBeVisible();
});

test('A desktop-collapsed aside still opens as an overlay after shrinking to a narrow screen', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    await page.setViewportSize({ width: 1280, height: 900 }); // desktop, above the breakpoint

    const kb = new KnowbaseItemPage(page);
    const article_id = await createArticle(api);
    await kb.goto(article_id);

    // Collapse on desktop (persists data-glpi-kb-aside-collapsed + localStorage)
    await kb.doCollapseAside();
    await expect(kb.getAsideTitle()).toBeHidden();
    await expect(kb.getAsideExpandButton()).toBeVisible();

    // Below the breakpoint the persisted collapsed state must not block the overlay.
    await page.setViewportSize({ width: 800, height: 900 });
    await kb.doExpandAside();
    await expect(kb.getAsideTitle()).toBeVisible();
});
