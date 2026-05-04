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
    await kb.editor.enterEditMode();

    const picker_button = page.getByRole('button', { name: 'Select an illustration' });
    await picker_button.click();

    const modal = page.getByTestId('illustration-picker-modal');
    await expect(modal).toBeVisible();

    const icon = modal.getByRole('img', { name: "Antivirus", exact: true });
    await icon.click();

    await expect(modal).toBeHidden();

    const save_request = page.waitForRequest(
        req => req.url().includes(`/Knowbase/KnowbaseItem/${id}/Answer`) && req.method() === 'POST'
    );
    await kb.editor.save();
    const req = await save_request;
    expect(JSON.parse(req.postData() ?? '{}')).toMatchObject({ illustration: 'antivirus' });

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
    await kb.editor.enterEditMode();

    const picker_button = page.getByRole('button', { name: 'Select an illustration' });
    await picker_button.click();

    const modal = page.getByTestId('illustration-picker-modal');
    await expect(modal).toBeVisible();

    await modal.getByText('Upload your own illustration').click();

    await kb.doAddFileToUploadArea("test_icon.png", page.getByRole('dialog'));

    const save_request = page.waitForRequest(
        req => req.url().includes(`/Knowbase/KnowbaseItem/${id}/Answer`) && req.method() === 'POST'
    );
    await modal.getByText('Use selected file').click();

    await expect(modal).toBeHidden();

    await kb.editor.save();
    const req = await save_request;
    expect(JSON.parse(req.postData() ?? '{}').illustration).toMatch(/^custom:/);

    await page.reload();
    const custom_preview = page.getByTestId('illustration-custom-preview');
    await expect(custom_preview).toBeVisible();
});

test('Illustration is not editable until edit mode is entered (regression #248)', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const id = await api.createItem('KnowbaseItem', {
        name: 'KB illustration read-only test',
        entities_id: getWorkerEntityId(),
        answer: "My answer",
    });

    await kb.goto(id);

    const picker = page.getByTestId('illustration-picker');
    const modal = page.getByTestId('illustration-picker-modal');

    await expect(page.getByRole('button', { name: 'Select an illustration' })).toHaveCount(0);
    await expect(picker).toHaveAttribute('aria-disabled', 'true');

    await picker.click();
    await expect(modal).toBeHidden();

    await kb.editor.enterEditMode();

    await expect(page.getByRole('button', { name: 'Select an illustration' })).toBeVisible();
    await expect(picker).not.toHaveAttribute('aria-disabled', 'true');

    await kb.editor.cancel();

    await expect(page.getByRole('button', { name: 'Select an illustration' })).toHaveCount(0);
    await expect(picker).toHaveAttribute('aria-disabled', 'true');
});

test('Cancelling edit reverts illustration to original', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const id = await api.createItem('KnowbaseItem', {
        name: 'KB illustration cancel revert test',
        entities_id: getWorkerEntityId(),
        answer: 'My answer',
    });

    await kb.goto(id);

    const illustration_input = page.getByTestId('illustration-input');
    const original_value = await illustration_input.inputValue();
                                                                                                  
    // eslint-disable-next-line playwright/prefer-web-first-assertions
    expect(original_value).not.toBe('antivirus');

    await kb.editor.enterEditMode();

    await page.getByRole('button', { name: 'Select an illustration' }).click();
    const modal = page.getByTestId('illustration-picker-modal');
    await expect(modal).toBeVisible();
    await modal.getByRole('img', { name: 'Antivirus', exact: true }).click();
    await expect(modal).toBeHidden();

    await expect(illustration_input).toHaveValue('antivirus');

    await kb.editor.cancel();
    await expect(illustration_input).toHaveValue(original_value);

    await page.reload();
    await expect(page.getByTestId('illustration-input')).toHaveValue(original_value);
});
