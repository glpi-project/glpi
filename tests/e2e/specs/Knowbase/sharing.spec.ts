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

test('Can create a sharing link with and without name', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const id = await api.createItem('KnowbaseItem', {
        name: `KB sharing create - ${crypto.randomUUID()}`,
        entities_id: getWorkerEntityId(),
        answer: "Test content for create",
    });

    await kb.goto(id);
    await expect(page.getByText('Test content for create')).toBeVisible();
    const modal = await kb.doOpenSharingTab();

    await expect(modal.getByRole('button', { name: 'Create a sharing link' })).toBeVisible();
    await expect(modal.getByText('No sharing links yet')).toBeVisible();

    await kb.doCreateSharingLink(modal, 'My named link');

    await expect(modal.getByRole('checkbox', { name: 'My named link' })).toBeVisible();
    await expect(modal.getByRole('checkbox', { name: 'My named link' })).toBeChecked();
    await expect(modal.getByText('No sharing links yet')).not.toBeAttached();

    await kb.doCreateSharingLink(modal);

    await expect(modal.getByRole('checkbox')).toHaveCount(2);
    await expect(modal.getByRole('checkbox', { name: 'My named link' })).toBeVisible();
});

test('Can toggle a sharing link off and on', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const id = await api.createItem('KnowbaseItem', {
        name: `KB sharing toggle - ${crypto.randomUUID()}`,
        entities_id: getWorkerEntityId(),
        answer: "Test content for toggle",
    });

    await kb.goto(id);
    await expect(page.getByText('Test content for toggle')).toBeVisible();
    const modal = await kb.doOpenSharingTab();

    await kb.doCreateSharingLink(modal);

    const toggle = modal.getByRole('checkbox', { name: 'Link 1' });
    await expect(toggle).toBeChecked();

    const url_input = modal.getByRole('textbox', { name: '' }).last();
    await expect(url_input).toBeVisible();

    await toggle.click();
    await expect(modal.getByRole('checkbox', { name: 'Link 1' })).not.toBeChecked();
    await expect(url_input).not.toBeAttached();

    await modal.getByRole('checkbox', { name: 'Link 1' }).click();
    await expect(modal.getByRole('checkbox', { name: 'Link 1' })).toBeChecked();
});

test('Can delete a sharing link', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const id = await api.createItem('KnowbaseItem', {
        name: `KB sharing delete - ${crypto.randomUUID()}`,
        entities_id: getWorkerEntityId(),
        answer: "Test content for delete",
    });

    await kb.goto(id);
    await expect(page.getByText('Test content for delete')).toBeVisible();
    const modal = await kb.doOpenSharingTab();

    await kb.doCreateSharingLink(modal);
    await expect(modal.getByRole('checkbox', { name: 'Link 1' })).toBeVisible();

    await modal.getByRole('button', { name: 'Delete' }).click();

    const confirm_dialog = page.getByRole('dialog').filter({ hasText: 'Delete sharing link' });
    await expect(confirm_dialog).toBeVisible();
    await confirm_dialog.getByRole('button', { name: 'Delete' }).click();

    await expect(page.getByRole('dialog')).not.toBeAttached();

    await kb.goto(id);
    await expect(page.getByText('Test content for delete')).toBeVisible();
    const reopened_modal = await kb.doOpenSharingTab();
    await expect(reopened_modal.getByText('No sharing links yet')).toBeVisible();
});

test('Shared link is accessible by anonymous user', async ({ page, anonymousPage, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const article_name = `KB shared article - ${crypto.randomUUID()}`;
    const article_content = `Shared content - ${crypto.randomUUID()}`;
    const id = await api.createItem('KnowbaseItem', {
        name: article_name,
        entities_id: getWorkerEntityId(),
        answer: `<p>${article_content}</p>`,
    });

    await kb.goto(id);
    await expect(page.getByText(article_content)).toBeVisible();
    const modal = await kb.doOpenSharingTab();

    await kb.doCreateSharingLink(modal);
    await expect(modal.getByRole('checkbox', { name: 'Link 1' })).toBeVisible();

    const share_url = await modal.getByRole('textbox').last().inputValue();
    expect(share_url).toContain('/Share/');

    // Use path only so the test is agnostic of the server base URL (host/port vary between local and CI)
    const share_path = new URL(share_url, 'http://placeholder').pathname;
    await anonymousPage.goto(share_path);

    await expect(anonymousPage.getByText(article_name)).toBeVisible();
    await expect(anonymousPage.getByText(article_content)).toBeVisible();
});

test('Invalid token formats are rejected before reaching the controller', async ({ anonymousPage }) => {
    const invalid_paths = [
        '/Share/abc',
        '/Share/GGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGG',
    ];

    for (const path of invalid_paths) {
        const response = await anonymousPage.goto(path);
        expect(response?.status(), `Expected 404 for ${path}`).toBe(404);
    }
});
