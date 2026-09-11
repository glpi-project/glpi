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

import { randomUUID } from 'crypto';
import { expect, test } from '../../fixtures/glpi_fixture';
import { Api } from '../../utils/Api';
import { KnowbaseItemPage } from '../../pages/KnowbaseItemPage';
import { Profiles } from '../../utils/Profiles';

// Regression coverage for the aside's kebab-menu prefetch (AsideController#loadActions).

async function createArticle(api: Api): Promise<number>
{
    return api.createItem('KnowbaseItem', {
        name: `E2E aside prefetch ${randomUUID().slice(0, 8)}`,
        answer: 'Content',
    });
}

test('A failed actions-menu prefetch does not show an error toast', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const id = await createArticle(api);

    // Route before navigating: registering it after goto() races the page's
    // own early requests to this URL, which then slip through unintercepted.
    let aborted = false;
    await page.route(`**/Knowbase/${id}/AsideActions`, async (route) => {
        aborted = true;
        await route.abort();
    });

    const kb = new KnowbaseItemPage(page);
    await kb.goto(id);
    await kb.waitForAsideReady();

    await kb.getAsideTreeArticleLine(id).hover();
    // Prove the request actually fired and was aborted, not just that no
    // toast happened to appear (which would also be true if it never fired).
    await expect.poll(() => aborted).toBe(true);
    await expect(kb.getAlert('An unexpected error occurred.')).toHaveCount(0);
});

test('A row the pointer rests on gets its actions menu prefetched', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const id = await createArticle(api);

    const kb = new KnowbaseItemPage(page);
    await kb.goto(id);
    await kb.waitForAsideReady();

    const response = page.waitForResponse((r) => r.url().includes(`/Knowbase/${id}/AsideActions`));
    await kb.getAsideTreeArticleLine(id).hover();
    await response;

    // Checked in the DOM, not via role/visibility: the dropdown stays closed (display:none).
    await expect(kb.getAsideArticleActionsMenu(id)).toHaveAttribute('data-glpi-kb-actions-loaded', '');
    await expect(kb.getAsideArticleActionsMenuButton(id, 'TOGGLE_FAVORITE')).toBeAttached();
});

test('Sweeping across many aside rows only prefetches the one the pointer rests on', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const ids = [
        await createArticle(api),
        await createArticle(api),
        await createArticle(api),
        await createArticle(api),
        await createArticle(api),
    ];

    const kb = new KnowbaseItemPage(page);
    await kb.goto(ids[0]);
    await kb.waitForAsideReady();

    const requested_ids: number[] = [];
    page.on('request', (request) => {
        const match = request.url().match(/\/Knowbase\/(\d+)\/AsideActions$/);
        if (match) {
            requested_ids.push(Number(match[1]));
        }
    });

    // Simulate a fast pointer sweep: mouseout the previous row, then mouseover the next.
    await kb.sweepPointerAcrossAsideTreeRows(ids);

    // Only the last row (the one never left) should have triggered a request.
    await expect.poll(() => requested_ids).toEqual([ids[ids.length - 1]]);
});
