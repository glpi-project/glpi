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

test('Articles created from the form are saved as drafts and surface a toast', async ({ page, profile }) => {
    await profile.set(Profiles.SuperAdmin);

    await page.goto('/front/knowbaseitem.form.php');

    // eslint-disable-next-line playwright/no-raw-locators -- TipTap editor has no semantic role
    const editor = page.locator('#kb-tiptap-editor .ProseMirror');
    await expect(editor).toBeVisible();

    const title = page.getByTestId('subject');
    const unique_title = getUniqueName('auto-draft');
    await title.click();
    await title.fill('');
    await page.keyboard.type(unique_title);

    await editor.click();
    await page.keyboard.type('Work in progress.');

    await page.getByRole('button', { name: 'Add article' }).click();

    // Toast confirming draft state.
    await expect(
        page.getByText('Article saved as draft', { exact: false })
    ).toBeVisible();

    // The article header shows the Draft chip. Match by title attribute so
    // we hit the unique badge (not the literal word "Draft" that may appear
    // elsewhere, e.g. in the aside).
    await expect(
        page.getByRole('article').getByTitle(/Draft — only visible/)
    ).toBeVisible();
});

test('Mark as draft / publish toggle in the action menu flips the status', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const name = getUniqueName('toggle-draft');
    const id = await api.knowbase.createArticle({ name, is_draft: false });

    await kb.goto(id);

    // Open "More actions" and toggle draft on.
    await page.getByTitle('More actions').click();
    const responsePromise = page.waitForResponse(r => r.url().includes('/ToggleField'));
    await page.getByRole('button', { name: 'Mark as draft', exact: false }).click();
    await responsePromise;

    // The article's aside entry now carries the screen-reader-only Draft label.
    const tree = page.getByRole('navigation', { name: 'Knowledge base articles tree' });
    await expect(
        tree.getByRole('link', { name }).getByText('Draft', { exact: true })
    ).toBeAttached();
});

test('Draft articles carry a screen-reader Draft label in the left aside', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const name = getUniqueName('aside-draft');
    const id = await api.knowbase.createArticle({ name, is_draft: true });

    // Goto any KB page so the aside renders.
    await kb.goto(id);

    // The current article is rendered both in favorites (as a "pending" slot)
    // and in the tree — scope to the tree navigation landmark so the matcher
    // is unambiguous.
    const tree = page.getByRole('navigation', { name: 'Knowledge base articles tree' });
    const link = tree.getByRole('link', { name });
    await expect(link).toBeVisible();
    await expect(link.getByText('Draft', { exact: true })).toBeAttached();
});

test('Share link cannot be created on a draft article', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const id = await api.knowbase.createArticle({
        name: getUniqueName('draft-share-block'),
        is_draft: true,
    });

    await kb.goto(id);
    const modal = await kb.doOpenSharingTab();

    // SuperAdmin always sees the create button; the draft block is enforced
    // by the API (HTTP 409 from ShareTokenController::canBeShared()).
    await modal.getByRole('button', { name: 'Create a sharing link' }).click();

    const responsePromise = page.waitForResponse(r => r.url().includes('/Share/Token/'));
    await modal.getByPlaceholder('Link name (optional)').press('Enter');
    const response = await responsePromise;
    expect(response.status()).toBe(409);
});
