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

test('Can unlink a document from a KB article', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const kb_id = await api.createItem('KnowbaseItem', {
        name: 'KB article for unlink document test',
        entities_id: getWorkerEntityId(),
        answer: 'Test content',
    });
    const doc_id = await api.createItem('Document', {
        name: 'Test document',
        entities_id: getWorkerEntityId(),
    });
    await api.createItem('Document_Item', {
        documents_id: doc_id,
        itemtype: 'KnowbaseItem',
        items_id: kb_id,
    });

    await kb.goto(kb_id);

    const chip = page.getByTestId('document-chip');
    await expect(chip).toBeVisible();

    await chip.hover();
    const unlink_button = chip.getByLabel('Unlink document');
    await unlink_button.click();

    const modal = kb.getDialog('Unlink document');
    await expect(modal).toBeVisible();
    await modal.getByRole('button', { name: 'Unlink' }).click();

    await expect(chip).not.toBeAttached();
});

test('Document count updates after unlinking', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const kb_id = await api.createItem('KnowbaseItem', {
        name: 'KB article for document count test',
        entities_id: getWorkerEntityId(),
        answer: 'Test content',
    });
    const doc_id = await api.createItem('Document', {
        name: 'Test document for count',
        entities_id: getWorkerEntityId(),
    });
    await api.createItem('Document_Item', {
        documents_id: doc_id,
        itemtype: 'KnowbaseItem',
        items_id: kb_id,
    });

    await kb.goto(kb_id);

    await expect(page.getByTestId('documents-count')).toHaveText('1 document');

    const chip = page.getByTestId('document-chip');
    await chip.hover();
    await chip.getByLabel('Unlink document').click();

    const modal = kb.getDialog('Unlink document');
    await modal.getByRole('button', { name: 'Unlink' }).click();

    await expect(page.getByTestId('documents-count')).not.toBeAttached();
});
