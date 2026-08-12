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

test('Can fold and unfold a parent article in the KB aside', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);
    const parent_name = `E2E Parent ${unique}`;
    const child_name = `E2E Child ${unique}`;

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

    await kb.goto(child_id);

    // Parent and child are visible by default
    const parent_toggle = kb.getAsideCategoryToggle(parent_name);
    const child_link = kb.getAsideCategoryArticle(parent_name, child_name);

    await expect(parent_toggle).toHaveAttribute('aria-expanded', 'true');
    await expect(child_link).toBeVisible();

    // Fold the parent — child should be hidden
    await kb.doToggleAsideCategory(parent_name);
    await expect(parent_toggle).toHaveAttribute('aria-expanded', 'false');
    await expect(child_link).toBeHidden();

    // Unfold the parent — child should be visible again
    await kb.doToggleAsideCategory(parent_name);
    await expect(parent_toggle).toHaveAttribute('aria-expanded', 'true');
    await expect(child_link).toBeVisible();
});

test('Article fold state is remembered across reloads', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    // Arrange: a parent article with a child so we have something to fold.
    const unique = randomUUID().slice(0, 8);
    const parent_name = `E2E Parent ${unique}`;
    const child_name = `E2E Child ${unique}`;

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

    await kb.goto(child_id);
    await kb.waitForAsideReady();

    const parent_toggle = kb.getAsideCategoryToggle(parent_name);
    const child_link = kb.getAsideCategoryArticle(parent_name, child_name);

    // Fold the parent, then reload: the folded state must be restored.
    await kb.doToggleAsideCategoryAndWaitForPersist(parent_name);
    await expect(child_link).toBeHidden();

    await page.reload();
    await expect(parent_toggle).toHaveAttribute('aria-expanded', 'false');
    await expect(child_link).toBeHidden();

    // Unfold and reload again: the expanded state must likewise be restored.
    await kb.waitForAsideReady();
    await kb.doToggleAsideCategoryAndWaitForPersist(parent_name);
    await expect(child_link).toBeVisible();

    await page.reload();
    await expect(parent_toggle).toHaveAttribute('aria-expanded', 'true');
    await expect(child_link).toBeVisible();
});
