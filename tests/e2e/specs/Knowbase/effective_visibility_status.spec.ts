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

import { expect, test } from "../../fixtures/glpi_fixture";
import { KnowbaseItemPage } from "../../pages/KnowbaseItemPage";
import { Profiles } from "../../utils/Profiles";
import { getUniqueName } from "../../utils/Random";
import { getWorkerEntityId } from "../../utils/WorkerEntities";

test('Draft article shows the Draft badge and Publish CTA regardless of visibility targets', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const id = await api.knowbase.createArticle({
        name: getUniqueName('effective-status-draft'),
        is_draft: true,
    });
    await api.knowbase.addEntityVisibility(id, getWorkerEntityId(), false);

    await kb.goto(id);

    await expect(kb.getStatusBadge('draft')).toBeVisible();

    const banner = kb.getVisibilityBanner('draft');
    await expect(banner).toBeVisible();
    await expect(banner).toContainText('Only you and knowledge base admins can see this');
    await expect(kb.publishButton).toBeVisible();
});

test('Publishing a draft with no visibility target lands in No audience without a full reload', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const id = await api.knowbase.createArticle({
        name: getUniqueName('effective-status-publish-no-target'),
        is_draft: true,
    });

    await kb.goto(id);
    await expect(kb.getStatusBadge('draft')).toBeVisible();

    let navigated = false;
    page.on('framenavigated', () => { navigated = true; });

    const response_promise = page.waitForResponse(r => r.url().includes('/ToggleField'));
    await kb.publishButton.click();
    await response_promise;

    await expect(kb.getStatusBadge('no_audience')).toBeVisible();

    const banner = kb.getVisibilityBanner('no_audience');
    await expect(banner).toBeVisible();
    await expect(banner).toContainText('Published, but no one can reach it');
    await expect(kb.defineAudienceButton).toBeVisible();

    await expect(
        page.getByText('Article published, but no one can see it yet', { exact: false })
    ).toBeVisible();

    expect(navigated).toBe(false);
});

test('Published article with a visibility target shows the Published badge and no CTA banner', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const id = await api.knowbase.createArticle({
        name: getUniqueName('effective-status-published'),
        is_draft: false,
    });
    await api.knowbase.addEntityVisibility(id, getWorkerEntityId(), false);

    await kb.goto(id);

    await expect(kb.getStatusBadge('published')).toBeVisible();
    await expect(kb.getVisibilityBanner('draft')).toBeHidden();
    await expect(kb.getVisibilityBanner('no_audience')).toBeHidden();
});
