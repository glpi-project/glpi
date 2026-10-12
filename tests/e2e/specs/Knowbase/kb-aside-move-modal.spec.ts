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

test('Can move an article to another parent from the aside menu', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);
    const parent_name = `E2E Parent ${unique}`;
    const child_name = `E2E Child ${unique}`;
    const host_name = `E2E Host ${unique}`;

    const parent_id = await api.createItem('KnowbaseItem', {
        name: parent_name,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
    });
    const child_id = await api.createItem('KnowbaseItem', {
        name: child_name,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
        _parents: [parent_id],
    });
    await api.createItem('KnowbaseItem', {
        name: host_name,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
    });

    await kb.goto(child_id);
    await expect(kb.getAsideCategoryArticle(parent_name, child_name)).toBeVisible();

    await kb.doOpenAsideArticleMenu(child_id);
    await kb.getAsideArticleAction(child_id, 'Move').click();

    const dialog = page.getByRole('dialog');
    await expect(dialog).toBeVisible();

    // exact: false accounts for hierarchical labels like "Some parent > E2E Host …"
    await kb.doSetDropdownValue(
        kb.getDropdownByLabel('New parent', dialog),
        host_name,
        false,
    );

    await dialog.getByRole('button', { name: 'Move', exact: true }).click();

    // Modal reloads the page on success, tree comes from server.
    await expect(kb.getAsideCategoryArticle(host_name, child_name)).toBeVisible();
    await expect(kb.getAsideCategoryArticle(parent_name, child_name)).toHaveCount(0);
});
