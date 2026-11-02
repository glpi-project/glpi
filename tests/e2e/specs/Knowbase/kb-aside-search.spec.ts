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
import { Api } from '../../utils/Api';
import { Profiles } from '../../utils/Profiles';
import { getWorkerEntityId } from '../../utils/WorkerEntities';

/**
 * A token no other article can hold, so a search for it returns exactly what
 * the test created.
 */
function uniqueToken(): string
{
    return randomUUID().slice(0, 8);
}

/**
 * Articles matching `token`, created in parallel: a page of results is 50, so
 * the pagination test needs more articles than the rest of this file put
 * together.
 */
async function createMatchingArticles(api: Api, token: string, count: number): Promise<number[]>
{
    return await Promise.all(
        Array.from({ length: count }, (_, i) => api.createItem('KnowbaseItem', {
            name: `E2E Result ${i} ${token}`,
            answer: '<p>Filler content</p>',
            entities_id: getWorkerEntityId(),
        })),
    );
}

test('The results replace the tree, listing every match on its own row', async ({
    page,
    profile,
    api,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    // Distinct tokens, so searching for one cannot match the other.
    const token_a = uniqueToken();
    const token_b = uniqueToken();

    const parent_a_id = await api.createItem('KnowbaseItem', {
        name: `E2E Parent A ${token_a}`,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
    });
    const parent_b_id = await api.createItem('KnowbaseItem', {
        name: `E2E Parent B ${token_b}`,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
    });
    const article_a_id = await api.createItem('KnowbaseItem', {
        name: `E2E Article A ${token_a}`,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
        _parents: [parent_a_id],
    });
    const article_b_id = await api.createItem('KnowbaseItem', {
        name: `E2E Article B ${token_b}`,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
        _parents: [parent_b_id],
    });

    await kb.goto(article_a_id);
    await kb.doSearchAside(token_a);

    // The tree gives way to the results.
    await expect(kb.asideSearchResults).toBeVisible();
    await expect(kb.asideRenderedTree).toBeHidden();

    // Both matches are listed, and a match is a row of its own: the nesting of
    // the tree is gone, so the child does not hang under its parent.
    await expect(kb.asideSearchResultRows).toHaveCount(2);
    await expect(kb.getAsideSearchResultRow(parent_a_id)).toBeVisible();
    await expect(kb.getAsideSearchResultRow(article_a_id)).toBeVisible();

    // Nothing that does not match is listed.
    await expect(kb.getAsideSearchResultRow(parent_b_id)).toHaveCount(0);
    await expect(kb.getAsideSearchResultRow(article_b_id)).toHaveCount(0);
});

test('A result shows the title of the article and the beginning of its content', async ({
    page,
    profile,
    api,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const token = uniqueToken();
    const title = `E2E Reset a password ${token}`;

    const article_id = await api.createItem('KnowbaseItem', {
        name: title,
        answer: '<p>Open the user form.</p><p>Then use the reset action.</p>',
        entities_id: getWorkerEntityId(),
    });

    await kb.goto(article_id);
    await kb.doSearchAside(token);

    const row = kb.getAsideSearchResultRow(article_id);
    await expect(row).toContainText(title);

    // The content of the article, as flowing text: the paragraphs of the
    // article are not run together, and its markup is nowhere to be seen.
    await expect(kb.getAsideSearchResultExcerpt(article_id))
        .toHaveText('Open the user form. Then use the reset action.');
});

test('The article being read is marked in the results', async ({
    page,
    profile,
    api,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const token = uniqueToken();
    const [other_id, current_id] = await createMatchingArticles(api, token, 2);

    await kb.goto(current_id);
    await kb.doSearchAside(token);

    await expect(kb.getAsideSearchResultRow(current_id)).toHaveAttribute('aria-current', 'page');
    await expect(kb.getAsideSearchResultRow(other_id)).not.toHaveAttribute('aria-current', 'page');
});

test('Results are cut into pages, the next one loading as the reader scrolls', async ({
    page,
    profile,
    api,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    // One more than a page, so there is a second page holding a single result.
    const page_size = 50;
    const token = uniqueToken();
    const ids = await createMatchingArticles(api, token, page_size + 1);

    await kb.goto(ids[0]);
    await kb.doSearchAside(token);

    // A page of results, closed by the marker asking for the next one.
    await expect(kb.asideSearchResultRows).toHaveCount(page_size);
    await expect(kb.asideSearchLoadMore).toHaveCount(1);

    // The marker is a live region, and nothing is loading until it is reached.
    await expect(kb.asideSearchLoadMoreLoading).toBeHidden();

    await kb.doScrollAsideSearchResultsToEnd();

    // The last page arrives, and nothing is left to ask for.
    await expect(kb.asideSearchResultRows).toHaveCount(page_size + 1);
    await expect(kb.asideSearchLoadMore).toHaveCount(0);

    // Every article is listed exactly once: no page overlaps or skips another.
    const listed = await kb.asideSearchResultRows.evaluateAll(
        (rows) => rows.map((row) => Number((row as HTMLElement).dataset.glpiKbArticleId)),
    );
    expect([...listed].sort()).toEqual([...ids].sort());
});

test('A failed page request does not put an end to the results', async ({
    page,
    profile,
    api,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const page_size = 50;
    const token = uniqueToken();
    const ids = await createMatchingArticles(api, token, page_size + 1);

    // Fail the second page once, the way a dropped connection would.
    let fail_next_page = true;
    await page.route(
        (url) => url.pathname.endsWith('/Knowbase/Aside/Search')
            && url.searchParams.get('offset') === String(page_size),
        async (route) => {
            if (!fail_next_page) {
                await route.continue();
                return;
            }
            fail_next_page = false;
            await route.fulfill({ status: 500, body: '' });
        },
    );

    await kb.goto(ids[0]);
    await kb.doSearchAside(token);
    await expect(kb.asideSearchResultRows).toHaveCount(page_size);

    await kb.doScrollAsideSearchResultsToEnd();
    await expect(kb.asideSearchLoadMoreError).toBeVisible();

    // Taking the marker out of view and back in asks for the page again.
    await kb.doScrollAsideSearchResultsToStart();
    await expect(kb.asideSearchLoadMore).not.toBeInViewport();
    await kb.doScrollAsideSearchResultsToEnd();

    await expect(kb.asideSearchResultRows).toHaveCount(page_size + 1);
    await expect(kb.asideSearchLoadMore).toHaveCount(0);
});

test('A new search starts back at the first page', async ({
    page,
    profile,
    api,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const page_size = 50;
    const token = uniqueToken();
    const ids = await createMatchingArticles(api, token, page_size + 1);

    // An article of its own, to search for once scrolled to the end.
    const lone_token = uniqueToken();
    const lone_id = await api.createItem('KnowbaseItem', {
        name: `E2E Lone article ${lone_token}`,
        answer: '<p>Lone content</p>',
        entities_id: getWorkerEntityId(),
    });

    await kb.goto(ids[0]);
    await kb.doSearchAside(token);
    await expect(kb.asideSearchResultRows).toHaveCount(page_size);
    await kb.doScrollAsideSearchResultsToEnd();
    await expect(kb.asideSearchResultRows).toHaveCount(page_size + 1);

    // Searching again from the end of the previous results must not carry the
    // scroll over, which would ask for every page of the new search at once.
    await kb.doSearchAside(lone_token);
    await expect(kb.asideSearchResultRows).toHaveCount(1);
    await expect(kb.getAsideSearchResultRow(lone_id)).toBeVisible();
});

test('The aside works on an article that has not been saved yet', async ({
    page,
    profile,
    api,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const token = uniqueToken();
    const parent_id = await api.createItem('KnowbaseItem', {
        name: `E2E Unsaved parent ${token}`,
        answer: '<p>Parent content</p>',
        entities_id: getWorkerEntityId(),
    });
    const child_id = await api.createItem('KnowbaseItem', {
        name: `E2E Unsaved child ${token}`,
        answer: '<p>Child content</p>',
        entities_id: getWorkerEntityId(),
        _parents: [parent_id],
    });

    // The creation form: no article is being read, so the aside has no current
    // article to name in the requests it sends.
    await page.goto('/front/knowbaseitem.form.php', { waitUntil: 'domcontentloaded' });
    await kb.waitForAsideReady();

    // Unfolding a category still loads its children.
    await kb.doExpandAsideCategory(`E2E Unsaved parent ${token}`);
    await expect(kb.getAsideTreeArticleRow(child_id)).toBeVisible();

    await kb.doSearchAside(token);
    await expect(kb.getAsideSearchResultRow(parent_id)).toBeVisible();
    await expect(kb.getAsideSearchResultRow(child_id)).toBeVisible();
});

test('Favorites are hidden while a search is active, and restored when it is cleared', async ({
    page,
    profile,
    api,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const token = uniqueToken();
    const favorite_id = await api.createItem('KnowbaseItem', {
        name: `E2E Favorite ${token}`,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
    });

    await api.knowbase.addFavorite(favorite_id);
    await kb.goto(favorite_id);
    await expect(kb.favoritesSection).toBeVisible();

    // The results stand in for the whole tree view, favorites included, even
    // when a favorite is itself a match.
    await kb.doSearchAside(token);
    await expect(kb.getAsideSearchResultRow(favorite_id)).toBeVisible();
    await expect(kb.favoritesSection).toBeHidden();

    await kb.doClearAsideSearch();
    await expect(kb.favoritesSection).toBeVisible();
    await expect(kb.getFavoriteArticleRow(favorite_id)).toBeVisible();
});

test('Clearing the search restores the tree', async ({
    page,
    profile,
    api,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const token = uniqueToken();
    const parent_id = await api.createItem('KnowbaseItem', {
        name: `E2E Parent ${token}`,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
    });
    const article_id = await api.createItem('KnowbaseItem', {
        name: `E2E Article ${token}`,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
        _parents: [parent_id],
    });

    await kb.goto(article_id);
    await kb.doSearchAside(uniqueToken());

    // The row count alone is met before the search is even sent, and clearing
    // would then just restart the debounce with an empty value.
    await expect(kb.asideRenderedTree).toBeHidden();
    await expect(kb.asideSearchResultRows).toHaveCount(0);

    await kb.doClearAsideSearch();

    // The results are gone and the tree is back, without a round trip.
    await expect(kb.asideSearchResults).toHaveCount(0);
    await expect(kb.asideRenderedTree).toBeVisible();
    await expect(kb.getAsideTreeArticleRow(article_id)).toBeVisible();
});

test('Clear button appears when typing and clicking it restores the tree', async ({
    page,
    profile,
    api,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const token = uniqueToken();
    const article_id = await api.createItem('KnowbaseItem', {
        name: `E2E Article ${token}`,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
    });

    await kb.goto(article_id);
    await kb.waitForAsideReady();

    // Not interactive until there is something to clear.
    await expect(kb.asideSearchClearButton).toBeDisabled();

    await kb.doSearchAside(uniqueToken());
    await expect(kb.asideSearchClearButton).toBeEnabled();
    await expect(kb.asideRenderedTree).toBeHidden();

    await kb.doClickAsideSearchClear();
    await expect(kb.asideSearchClearButton).toBeDisabled();
    await expect(kb.asideRenderedTree).toBeVisible();
    await expect(kb.getAsideTreeArticleRow(article_id)).toBeVisible();
});

test('"No articles found" is shown when nothing matches and hidden when results exist', async ({
    page,
    profile,
    api,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const token = uniqueToken();
    const article_id = await api.createItem('KnowbaseItem', {
        name: `E2E Article ${token}`,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
    });

    await kb.goto(article_id);

    await kb.doSearchAside(uniqueToken());
    await expect(kb.asideNoResultsMessage).toBeVisible();

    await kb.doSearchAside(token);
    await expect(kb.asideNoResultsMessage).toBeHidden();
    await expect(kb.getAsideSearchResultRow(article_id)).toBeVisible();
});
