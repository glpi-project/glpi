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

test('visiting a nested article marks it (and only it) as current in the aside tree', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);
    const parent_name = `E2E Browse Parent ${unique}`;
    const child_name = `E2E Browse Child ${unique}`;

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

    // The parent stays expanded by default, so the child is visible without
    // any interaction, and is the one marked current.
    await expect(kb.getAsideCategoryToggle(parent_name)).toHaveAttribute('aria-expanded', 'true');
    await expect(kb.getAsideTreeArticleRow(child_id)).toHaveAttribute('aria-current', 'page');
    await expect(kb.getAsideTreeArticleRow(parent_id)).not.toHaveAttribute('aria-current', 'page');
});

test('the article being read is highlighted in the aside tree', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    // A three-level branch: the highlight must land on the row being read, and
    // on neither its ancestor nor its own children.
    const unique = randomUUID().slice(0, 8);
    const parent_id = await api.createItem('KnowbaseItem', {
        name: `E2E Highlight Parent ${unique}`,
        answer: 'Parent content',
        entities_id: getWorkerEntityId(),
    });
    const read_id = await api.createItem('KnowbaseItem', {
        name: `E2E Highlight Read ${unique}`,
        answer: 'Read content',
        entities_id: getWorkerEntityId(),
        _parents: [parent_id],
    });
    const child_id = await api.createItem('KnowbaseItem', {
        name: `E2E Highlight Child ${unique}`,
        answer: 'Child content',
        entities_id: getWorkerEntityId(),
        _parents: [read_id],
    });

    await kb.goto(read_id);
    await kb.waitForAsideReady();

    const colourOf = (id: number): Promise<string> => kb
        .getAsideTreeArticleTitleLink(id)
        .evaluate((link) => getComputedStyle(link).color);

    const read_colour = await colourOf(read_id);
    const parent_colour = await colourOf(parent_id);
    const child_colour = await colourOf(child_id);

    // Asserted against a row that is not current rather than against a fixed
    // value: the highlight colour comes from the theme.
    expect(read_colour).not.toBe(parent_colour);

    // The rows nested inside the one being read keep the plain colour: the
    // highlight is scoped to the row's own line, not to its whole subtree.
    expect(child_colour).toBe(parent_colour);
});

test('clicking a child article in the aside tree navigates to it', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);
    const parent_name = `E2E Browse Nav Parent ${unique}`;
    const child_name = `E2E Browse Nav Child ${unique}`;

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

    // Start on the parent, then follow the child link from the aside tree.
    await kb.goto(parent_id);
    await kb.getAsideCategoryArticle(parent_name, child_name).click();

    await expect(page).toHaveURL(new RegExp(`id=${child_id}\\b`));
    await expect(page.getByTestId('subject')).toHaveText(child_name);
    await expect(kb.getAsideTreeArticleRow(child_id)).toHaveAttribute('aria-current', 'page');
});
