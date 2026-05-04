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

test('Can pick a native illustration', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const id = await api.createItem('KnowbaseItem', {
        name: 'KB icon picker native test',
        entities_id: getWorkerEntityId(),
        answer: "My answer",
    });

    await kb.goto(id);

    // Open the illustration picker modal
    const picker_button = page.getByTestId('illustration-picker');
    await picker_button.click();

    const modal = page.getByTestId('illustration-picker-modal');
    await expect(modal).toBeVisible();

    // Pick a native icon from the search results
    const save_promise = page.waitForResponse(
        response => response.url().includes('/UpdateIllustration')
            && response.status() === 200
    );
    const icon = modal.getByRole('img', { name: "Antivirus", exact: true });
    await icon.click();
    await save_promise;

    // Modal should close after selecting a custom file
    await expect(modal).toBeHidden();

    // Verify the icon was saved by reloading the page
    await page.reload();
    await expect(page.getByTestId('illustration-input')).toHaveValue('antivirus');
});

test('Can pick a custom illustration', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const id = await api.createItem('KnowbaseItem', {
        name: 'KB icon picker custom test',
        entities_id: getWorkerEntityId(),
        answer: "My answer",
    });

    await kb.goto(id);

    // Open the illustration picker modal
    const picker_button = page.getByTestId('illustration-picker');
    await picker_button.click();

    const modal = page.getByTestId('illustration-picker-modal');
    await expect(modal).toBeVisible();

    // Switch to the upload tab
    await modal.getByText('Upload your own illustration').click();

    // Upload a custom icon
    await kb.doAddFileToUploadArea("test_icon.png", page.getByRole('dialog'));

    // Click "Use selected file" and wait for the save request
    const save_promise = page.waitForResponse(
        response => response.url().includes('/UpdateIllustration')
            && response.status() === 200
    );
    await modal.getByText('Use selected file').click();
    await save_promise;

    // Modal should close after selecting a custom file
    await expect(modal).toBeHidden();

    // Verify a custom illustration preview is shown
    await page.reload();
    const custom_preview = page.getByTestId('illustration-custom-preview');
    await expect(custom_preview).toBeVisible();
});

test('Can pick an illustration when creating an article', async ({ page, profile, entity }) => {
    await profile.set(Profiles.SuperAdmin);
    await entity.resetToDefaultWorkerEntity();

    await page.goto('/front/knowbaseitem.form.php', { waitUntil: 'domcontentloaded' });

    // Pick a native icon before saving
    await page.getByTestId('illustration-picker').click();
    const modal = page.getByTestId('illustration-picker-modal');
    await expect(modal).toBeVisible();
    await modal.getByRole('img', { name: 'Antivirus', exact: true }).click();
    await expect(modal).toBeHidden();

    // Fill the title
    const subject = page.getByTestId('subject');
    await subject.click();
    await subject.pressSequentially('KB created with custom icon');

    // Submit the new article
    await page.getByRole('button', { name: 'Add article' }).click();
    await page.waitForURL(/knowbaseitem\.form\.php\?id=\d+/);

    // Verify the chosen icon was persisted
    await expect(page.getByTestId('illustration-input')).toHaveValue('antivirus');
});

test('Picking an illustration during creation does not trigger an UpdateIllustration AJAX call', async ({ page, profile, entity }) => {
    await profile.set(Profiles.SuperAdmin);
    await entity.resetToDefaultWorkerEntity();

    await page.goto('/front/knowbaseitem.form.php', { waitUntil: 'domcontentloaded' });

    const ajax_calls: string[] = [];
    page.on('request', (request) => {
        if (request.url().includes('/UpdateIllustration')) {
            ajax_calls.push(request.url());
        }
    });

    await page.getByTestId('illustration-picker').click();
    const modal = page.getByTestId('illustration-picker-modal');
    await expect(modal).toBeVisible();
    await modal.getByRole('img', { name: 'Antivirus', exact: true }).click();
    await expect(modal).toBeHidden();

    expect(ajax_calls).toHaveLength(0);

    const subject = page.getByTestId('subject');
    await subject.click();
    await subject.pressSequentially('KB regression no AJAX on pick');

    await page.getByRole('button', { name: 'Add article' }).click();
    await page.waitForURL(/knowbaseitem\.form\.php\?id=\d+/);

    await expect(page.getByTestId('illustration-input')).toHaveValue('antivirus');
});
