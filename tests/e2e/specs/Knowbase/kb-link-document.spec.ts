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

test('Can link an existing document to a KB article', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const kb_id = await api.createItem('KnowbaseItem', {
        name: 'KB article for link document test',
        entities_id: getWorkerEntityId(),
        answer: 'Test content',
    });
    const doc_name = `Link test doc - ${crypto.randomUUID()}`;
    await api.createItem('Document', {
        name: doc_name,
        entities_id: getWorkerEntityId(),
    });

    await kb.goto(kb_id);

    await page.getByRole('button', { name: 'Add Document' }).click();
    const modal = page.getByRole('dialog');
    await expect(modal).toBeVisible();

    await modal.getByRole('tab', { name: 'Link a document' }).click();

    const dropdown = modal.getByRole('combobox');
    await dropdown.click();
    await page.getByRole('listbox').getByRole('option', { name: doc_name, exact: false }).click();

    await expect(modal.getByRole('listitem')).toHaveCount(1);
    await expect(modal.getByRole('listitem').first()).toContainText(doc_name);

    await modal.getByRole('button', { name: 'Link Documents' }).click();
    await expect(modal).toBeHidden();
    await page.waitForLoadState('load');

    await expect(page.getByTestId('document-chip')).toHaveCount(1);
});

test('Can link multiple documents at once', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const kb_id = await api.createItem('KnowbaseItem', {
        name: 'KB article for multi-link test',
        entities_id: getWorkerEntityId(),
        answer: 'Test content',
    });
    const doc1_name = `Multi link doc 1 - ${crypto.randomUUID()}`;
    const doc2_name = `Multi link doc 2 - ${crypto.randomUUID()}`;
    await api.createItem('Document', {
        name: doc1_name,
        entities_id: getWorkerEntityId(),
    });
    await api.createItem('Document', {
        name: doc2_name,
        entities_id: getWorkerEntityId(),
    });

    await kb.goto(kb_id);

    await page.getByRole('button', { name: 'Add Document' }).click();
    const modal = page.getByRole('dialog');
    await expect(modal).toBeVisible();

    await modal.getByRole('tab', { name: 'Link a document' }).click();

    const dropdown = modal.getByRole('combobox');

    await dropdown.click();
    await page.getByRole('listbox').getByRole('option', { name: doc1_name, exact: false }).click();
    await expect(modal.getByRole('listitem')).toHaveCount(1);

    await dropdown.click();
    await page.getByRole('listbox').getByRole('option', { name: doc2_name, exact: false }).click();
    await expect(modal.getByRole('listitem')).toHaveCount(2);

    await modal.getByRole('button', { name: 'Link Documents' }).click();
    await expect(modal).toBeHidden();
    await page.waitForLoadState('load');

    await expect(page.getByTestId('document-chip')).toHaveCount(2);
});

test('Can remove a document from selection before linking', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const kb_id = await api.createItem('KnowbaseItem', {
        name: 'KB article for remove selection test',
        entities_id: getWorkerEntityId(),
        answer: 'Test content',
    });
    const doc1_name = `Remove test doc 1 - ${crypto.randomUUID()}`;
    const doc2_name = `Remove test doc 2 - ${crypto.randomUUID()}`;
    await api.createItem('Document', {
        name: doc1_name,
        entities_id: getWorkerEntityId(),
    });
    await api.createItem('Document', {
        name: doc2_name,
        entities_id: getWorkerEntityId(),
    });

    await kb.goto(kb_id);

    await page.getByRole('button', { name: 'Add Document' }).click();
    const modal = page.getByRole('dialog');
    await expect(modal).toBeVisible();

    await modal.getByRole('tab', { name: 'Link a document' }).click();

    const dropdown = modal.getByRole('combobox');

    await dropdown.click();
    await page.getByRole('listbox').getByRole('option', { name: doc1_name, exact: false }).click();

    await dropdown.click();
    await page.getByRole('listbox').getByRole('option', { name: doc2_name, exact: false }).click();

    await expect(modal.getByRole('listitem')).toHaveCount(2);

    await modal.getByRole('listitem').first().getByTitle('Remove').click();
    await expect(modal.getByRole('listitem')).toHaveCount(1);
});

test('Link button is disabled without selection', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const kb_id = await api.createItem('KnowbaseItem', {
        name: 'KB article for button state test',
        entities_id: getWorkerEntityId(),
        answer: 'Test content',
    });

    await kb.goto(kb_id);

    await page.getByRole('button', { name: 'Add Document' }).click();
    const modal = page.getByRole('dialog');
    await expect(modal).toBeVisible();

    await modal.getByRole('tab', { name: 'Link a document' }).click();

    await expect(modal.getByRole('button', { name: 'Link Documents' })).toBeDisabled();
});

test('Already linked documents are excluded from dropdown', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const kb_id = await api.createItem('KnowbaseItem', {
        name: 'KB article for exclusion test',
        entities_id: getWorkerEntityId(),
        answer: 'Test content',
    });
    const linked_doc_name = `Already linked - ${crypto.randomUUID()}`;
    const doc_id = await api.createItem('Document', {
        name: linked_doc_name,
        entities_id: getWorkerEntityId(),
    });
    await api.createItem('Document_Item', {
        documents_id: doc_id,
        itemtype: 'KnowbaseItem',
        items_id: kb_id,
    });

    await kb.goto(kb_id);

    await page.getByRole('button', { name: 'Add Document' }).click();
    const modal = page.getByRole('dialog');
    await expect(modal).toBeVisible();

    await modal.getByRole('tab', { name: 'Link a document' }).click();

    const dropdown = modal.getByRole('combobox');
    await dropdown.click();

    await expect(
        page.getByRole('listbox').getByRole('option', { name: linked_doc_name, exact: false })
    ).toHaveCount(0);
});
