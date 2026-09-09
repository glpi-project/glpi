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
import { getWorkerEntityId } from '../../utils/WorkerEntities';

interface FaqBranch {
    parent_id: number;
    parent_name: string;
    child_id: number;
    child_name: string;
}

/**
 * A parent article with one child, both published to the FAQ and readable in
 * the worker entity. Both have to be in the FAQ: an ancestor that is not is
 * invisible to a helpdesk reader, and its children are then promoted to the
 * top level instead of nesting.
 */
async function createFaqBranch(api: Api, is_parent_faq: boolean = true): Promise<FaqBranch>
{
    const unique = randomUUID().slice(0, 8);
    const parent_name = `E2E FAQ Parent ${unique}`;
    const child_name = `E2E FAQ Child ${unique}`;
    const entities_id = getWorkerEntityId();

    const parent_id = await api.createItem('KnowbaseItem', {
        name: parent_name,
        answer: 'Parent content',
        entities_id,
        is_faq: is_parent_faq ? 1 : 0,
    });
    const child_id = await api.createItem('KnowbaseItem', {
        name: child_name,
        answer: 'Child content',
        entities_id,
        is_faq: 1,
        _parents: [parent_id],
    });

    for (const knowbaseitems_id of [parent_id, child_id]) {
        await api.createItem('Entity_KnowbaseItem', {
            knowbaseitems_id,
            entities_id,
            is_recursive: 1,
        });
    }

    return { parent_id, parent_name, child_id, child_name };
}

test('The FAQ aside nests FAQ articles under their FAQ parent', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const { parent_id, parent_name, child_name } = await createFaqBranch(api);

    await profile.set(Profiles.SelfService);

    const kb = new KnowbaseItemPage(page);
    await kb.gotoFaq(parent_id);
    await kb.waitForAsideReady();

    const parent_toggle = kb.getAsideCategoryToggle(parent_name);
    const child_link = kb.getAsideCategoryArticle(parent_name, child_name);

    await expect(parent_toggle).toHaveAttribute('aria-expanded', 'true');
    await expect(child_link).toBeVisible();

    await kb.doToggleAsideCategory(parent_name);
    await expect(parent_toggle).toHaveAttribute('aria-expanded', 'false');
    await expect(child_link).toBeHidden();

    await kb.doToggleAsideCategory(parent_name);
    await expect(parent_toggle).toHaveAttribute('aria-expanded', 'true');
    await expect(child_link).toBeVisible();
});

test('A parent left out of the FAQ does not appear in the FAQ aside', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const { parent_name, child_id } = await createFaqBranch(api, false);

    await profile.set(Profiles.SelfService);

    const kb = new KnowbaseItemPage(page);
    await kb.gotoFaq(child_id);
    await kb.waitForAsideReady();

    await expect(kb.getAsideTreeArticleTitleLink(child_id)).toBeVisible();
    await expect(kb.getAsideCategoryToggle(parent_name)).toHaveCount(0);
    await expect(kb.aside.getByText(parent_name)).toHaveCount(0);
});

test('The FAQ aside offers no way to restructure the knowledge base', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const { parent_id, child_id } = await createFaqBranch(api);

    await profile.set(Profiles.SelfService);

    const kb = new KnowbaseItemPage(page);
    await kb.gotoFaq(parent_id);
    await kb.waitForAsideReady();

    await expect(kb.getAsideArticleAddChildTrigger(parent_id)).toHaveCount(0);

    await kb.doOpenAsideArticleMenu(child_id);

    await expect(kb.getAsideArticleAction(child_id, 'Add to favorites')).toBeVisible();
    for (const action of ['Add to FAQ', 'Move', 'Delete article']) {
        await expect(kb.getAsideArticleAction(child_id, action)).toHaveCount(0);
    }
});
