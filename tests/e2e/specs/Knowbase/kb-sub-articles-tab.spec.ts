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
import { getWorkerEntityId } from "../../utils/WorkerEntities";

test('The Sub-articles tab lists child articles and is selected by default', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = crypto.randomUUID();
    const parent_name = `Sub-articles parent ${unique}`;
    const first_child_name = `Sub-articles child A ${unique}`;
    const second_child_name = `Sub-articles child B ${unique}`;

    const parent_id = await api.createItem('KnowbaseItem', {
        name: parent_name,
        entities_id: getWorkerEntityId(),
        answer: 'Parent content',
    });
    await api.createItem('KnowbaseItem', {
        name: first_child_name,
        entities_id: getWorkerEntityId(),
        answer: 'First child content',
        _parents: [parent_id],
    });
    await api.createItem('KnowbaseItem', {
        name: second_child_name,
        entities_id: getWorkerEntityId(),
        answer: 'Second child content',
        _parents: [parent_id],
    });

    await kb.goto(parent_id);

    const tab = page.getByRole('tab', { name: /Sub-articles/ });
    await expect(tab).toBeVisible();
    await expect(tab).toHaveAttribute('aria-selected', 'true');
    // The page's own tab bar is a visible tablist too, so scope to the article footer one.
    const tabs = page
        .getByRole('tablist')
        .filter({ has: page.getByRole('tab', { name: /Sub-articles/ }) })
        .getByRole('tab');
    await expect(tabs).toHaveText([/Sub-articles/, /Documents/, /Related items/]);

    // Only the active pane is in the accessibility tree, so this excludes the aside links.
    const panel = page.getByRole('tabpanel', { name: /Sub-articles/ });
    await expect(panel.getByRole('link', { name: first_child_name })).toBeVisible();
    await expect(panel.getByRole('link', { name: second_child_name })).toBeVisible();

    await panel.getByRole('link', { name: first_child_name }).click();
    await expect(page.getByRole('heading', { name: first_child_name })).toBeVisible();
});

test('The Sub-articles tab is absent when the article has no child', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const article_id = await api.createItem('KnowbaseItem', {
        name: `Sub-articles childless ${crypto.randomUUID()}`,
        entities_id: getWorkerEntityId(),
        answer: 'Childless content',
    });

    await kb.goto(article_id);

    const documents_tab = page.getByRole('tab', { name: /Documents/ });
    await expect(documents_tab).toBeVisible();
    await expect(documents_tab).toHaveAttribute('aria-selected', 'true');
    await expect(page.getByRole('tab', { name: /Related items/ })).toHaveAttribute('aria-selected', 'false');
    await expect(page.getByRole('tab', { name: /Sub-articles/ })).toHaveCount(0);
});

test('The Sub-articles tab wins the default selection over Documents', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = crypto.randomUUID();
    const parent_id = await api.createItem('KnowbaseItem', {
        name: `Sub-articles with document ${unique}`,
        entities_id: getWorkerEntityId(),
        answer: 'Parent content',
    });
    await api.createItem('KnowbaseItem', {
        name: `Sub-articles child C ${unique}`,
        entities_id: getWorkerEntityId(),
        answer: 'Child content',
        _parents: [parent_id],
    });
    const document_id = await api.createItem('Document', {
        name: `Sub-articles document ${unique}`,
        entities_id: getWorkerEntityId(),
    });
    await api.createItem('Document_Item', {
        documents_id: document_id,
        itemtype: 'KnowbaseItem',
        items_id: parent_id,
    });

    await kb.goto(parent_id);

    await expect(page.getByRole('tab', { name: /Sub-articles/ })).toHaveAttribute('aria-selected', 'true');
    await expect(page.getByRole('tab', { name: /Documents/ })).toHaveAttribute('aria-selected', 'false');
});
