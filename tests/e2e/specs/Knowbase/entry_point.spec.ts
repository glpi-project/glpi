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
import { KnowbaseItemPage } from '../../pages/KnowbaseItemPage';
import { Profiles } from '../../utils/Profiles';
import { getWorkerEntityId } from '../../utils/WorkerEntities';

test('entering the knowledge base opens the root article', async ({ page, profile }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    await page.goto('/front/knowbaseitem.php');

    // Redirected to an article, and it is the one at the root of the tree: the
    // aside marks it as current and it is not nested under another row.
    await expect(page).toHaveURL(/knowbaseitem\.form\.php\?id=\d+/);
    const root_id = Number(new URL(page.url()).searchParams.get('id'));
    const root_row = kb.getAsideTreeArticleRow(root_id);
    await expect(root_row).toHaveAttribute('aria-current', 'page');
    await expect(kb.asideTree.locator(`:scope > ul > [data-glpi-kb-article-id="${root_id}"]`)).toBeVisible();
});

test('the "All articles" button reaches the article list, not the root article', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);
    const article_id = await api.createItem('KnowbaseItem', {
        name: `E2E Entry Point ${unique}`,
        answer: 'My answer',
        entities_id: getWorkerEntityId(),
    });

    await kb.goto(article_id);
    await page.getByRole('button', { name: 'All articles' }).click();

    // Still on the list: the button carries the parameter that skips the
    // redirect, and the page is a standard search page.
    await expect(page).toHaveURL(/knowbaseitem\.php\?/);
    await expect(page.getByTestId('search-page')).toBeVisible();
    await expect(page.getByTestId('kb-article')).toHaveCount(0);
});

test('a search request is not redirected to the root article', async ({ page, profile }) => {
    await profile.set(Profiles.SuperAdmin);

    // What the search form posts back to this page. It cannot carry the "All
    // articles" parameter: browsers drop the query string of a GET form action.
    await page.goto(
        '/front/knowbaseitem.php?itemtype=KnowbaseItem'
        + '&criteria[0][link]=AND&criteria[0][field]=1'
        + '&criteria[0][searchtype]=contains&criteria[0][value]=',
    );

    await expect(page).toHaveURL(/knowbaseitem\.php\?/);
    await expect(page.getByTestId('search-page')).toBeVisible();
    await expect(page.getByTestId('kb-article')).toHaveCount(0);
});
