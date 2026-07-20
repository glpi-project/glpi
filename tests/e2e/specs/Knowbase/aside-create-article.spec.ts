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

test('the aside "+" opens an inline input under a parent article; an empty submit creates nothing', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);
    const parent_name = `E2E Aside Parent ${unique}`;

    const parent_id = await api.createItem('KnowbaseItem', {
        name: parent_name,
        answer: 'Parent content',
        entities_id: getWorkerEntityId(),
    });
    await api.createItem('KnowbaseItem', {
        name: `Seed ${unique}`,
        answer: 'Seed content',
        entities_id: getWorkerEntityId(),
        _parents: [parent_id],
    });

    await kb.goto(parent_id);

    // The "+" click is intercepted by AsideController (a dynamically
    // imported module), instead of being a plain <a href> navigation. Wait
    // for the controller to finish initializing before clicking it, using
    // the same readiness signal doSearchAside() relies on, otherwise the
    // click can race the module load and fall through to the browser's
    // default navigation.
    await expect(kb.asideSearchInput).not.toHaveClass(/pe-none/);

    const add_link = kb.getAsideCategoryAddLink(parent_name);
    await kb.getAsideArticleTitleLink(parent_name).hover();
    await expect(add_link).toBeVisible();
    await add_link.click();

    // No navigation: the "+" now opens an inline input instead of a full page.
    await expect(page).not.toHaveURL(/knowbaseitems_id_parent=/);
    const inline_input = kb.getAsideCategoryCreateInput(parent_name);
    await expect(inline_input).toBeFocused();

    // Escape cancels: the input disappears, nothing is created.
    await inline_input.press('Escape');
    await expect(inline_input).toBeHidden();

    // Re-open and blur while empty: same result.
    await add_link.click();
    await expect(kb.getAsideCategoryCreateInput(parent_name)).toBeFocused();
    await page.keyboard.press('Tab');
    await expect(kb.getAsideCategoryCreateInput(parent_name)).toBeHidden();
});

test('using the root-level "+" creates an article without a parent', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);
    const article_title = `E2E Root Article ${unique}`;

    const seed_id = await api.createItem('KnowbaseItem', {
        name: `Seed ${unique}`,
        answer: 'Seed content',
        entities_id: getWorkerEntityId(),
    });

    await kb.goto(seed_id);

    // The "+" click is intercepted by AsideController (a dynamically
    // imported module), instead of being a plain <a href> navigation. Wait
    // for the controller to finish initializing before clicking it, using
    // the same readiness signal doSearchAside() relies on, otherwise the
    // click can race the module load and fall through to the browser's
    // default navigation.
    await expect(kb.asideSearchInput).not.toHaveClass(/pe-none/);

    await kb.asideRootHeader.hover();
    await expect(kb.asideRootCreateLink).toBeVisible();
    await kb.asideRootCreateLink.click();

    // No navigation: the "+" now opens an inline input instead of a full page,
    // so we are still on the seed article's page.
    await expect(page).toHaveURL(new RegExp(`id=${seed_id}\\b`));
    const inline_input = kb.asideRootCreateInput;
    await expect(inline_input).toBeFocused();
    await inline_input.fill(article_title);
    await inline_input.press('Enter');

    await expect(page).toHaveURL(/knowbaseitem\.form\.php\?id=\d+/);
    await expect(page.getByTestId('subject')).toHaveText(article_title);

    // The new article is visible at the root of the tree, and marked current.
    // (Its <li> has `role="group"` since article creation is allowed here,
    // overriding the implicit "listitem" role — locate it by id instead.)
    const new_id = Number(new URL(page.url()).searchParams.get('id'));
    const article_row = kb.getAsideTreeArticleRow(new_id);
    await expect(article_row).toBeVisible();
    await expect(article_row).toHaveAttribute('aria-current', 'page');
});

test('hovering a child article does not reveal its parent\'s add-article link', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);
    const parent_name = `E2E Hover Parent ${unique}`;
    const child_name = `E2E Hover Child ${unique}`;

    const parent_id = await api.createItem('KnowbaseItem', {
        name: parent_name,
        answer: 'Parent content',
        entities_id: getWorkerEntityId(),
    });
    const child_id = await api.createItem('KnowbaseItem', {
        name: child_name,
        answer: 'Child content',
        entities_id: getWorkerEntityId(),
        _parents: [parent_id],
    });

    await kb.goto(child_id);

    const parent_add = kb.getAsideCategoryAddLink(parent_name);
    const child_add = kb.getAsideCategoryAddLink(child_name);

    await kb.getAsideArticleTitleLink(child_name).hover();

    // visibility:hidden removes the parent link from the a11y tree, so the
    // role-based locator resolves to nothing — assert visibility, not CSS.
    await expect(child_add).toBeVisible();
    await expect(parent_add).toBeHidden();
});

test('typing a title and pressing Enter creates the child article and navigates to it', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);
    const parent_name = `E2E Inline Create Parent ${unique}`;
    const article_title = `E2E Inline Create Article ${unique}`;

    const parent_id = await api.createItem('KnowbaseItem', {
        name: parent_name,
        answer: 'Parent content',
        entities_id: getWorkerEntityId(),
    });

    await kb.goto(parent_id);

    // The "+" click is intercepted by AsideController (a dynamically imported
    // module) instead of being a plain <a href> navigation. Wait for the
    // controller to finish initializing before clicking it, using the same
    // readiness signal doSearchAside() relies on, otherwise the click can
    // race the module load and fall through to the browser's default
    // navigation.
    await expect(kb.asideSearchInput).not.toHaveClass(/pe-none/);

    const add_link = kb.getAsideCategoryAddLink(parent_name);
    await kb.getAsideArticleTitleLink(parent_name).hover();
    await add_link.click();

    const inline_input = kb.getAsideCategoryCreateInput(parent_name);
    await expect(inline_input).toBeFocused();

    await inline_input.fill(article_title);
    await inline_input.press('Enter');

    // Lands on the new article after a full page load.
    await expect(page).toHaveURL(/knowbaseitem\.form\.php\?id=\d+/);
    await expect(page.getByTestId('subject')).toHaveText(article_title);

    // The new article is visible under the parent in the aside, and marked
    // current. (Its <li> has `role="group"` since article creation is
    // allowed here, overriding the implicit "listitem" role — locate it by
    // id instead.)
    const new_id = Number(new URL(page.url()).searchParams.get('id'));
    const article_row = kb.getAsideTreeArticleRow(new_id);
    await expect(article_row).toBeVisible();
    await expect(article_row).toHaveAttribute('aria-current', 'page');
});
