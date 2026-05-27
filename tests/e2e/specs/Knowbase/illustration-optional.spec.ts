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

test('Selecting "No illustration" clears the value and shows the placeholder', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const id = await api.createItem('KnowbaseItem', {
        name: 'KB optional icon clear test',
        entities_id: getWorkerEntityId(),
        answer: 'My answer',
        illustration: 'antivirus',
    });

    await kb.goto(id);
    await kb.editor.enterEditMode();

    await page.getByRole('button', { name: 'Select an illustration' }).click();
    const modal = page.getByTestId('illustration-picker-modal');
    await expect(modal).toBeVisible();

    await modal.getByRole('button', { name: 'No illustration' }).click();
    await expect(modal).toBeHidden();

    const picker = page.getByTestId('illustration-picker');
    await expect(page.getByTestId('illustration-input')).toHaveValue('');
    await expect(picker.getByRole('img', { name: 'Antivirus', exact: true })).toBeHidden();

    await kb.editor.save();
    await page.reload();

    await expect(page.getByTestId('illustration-input')).toHaveValue('');
});

test('Article without illustration hides the container in view mode and reveals it in edit mode', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const id = await api.createItem('KnowbaseItem', {
        name: 'KB optional icon empty view test',
        entities_id: getWorkerEntityId(),
        answer: 'My answer',
    });

    await kb.goto(id);

    const picker = page.getByTestId('illustration-picker');
    await expect(picker).toBeHidden();

    await kb.editor.enterEditMode();

    await expect(picker).toBeVisible();
    await expect(page.getByTestId('illustration-input')).toHaveValue('');

    await kb.editor.cancel();
    await expect(picker).toBeHidden();
});

test('Cancelling edit after clearing illustration restores the original value', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const id = await api.createItem('KnowbaseItem', {
        name: 'KB optional icon cancel revert test',
        entities_id: getWorkerEntityId(),
        answer: 'My answer',
        illustration: 'antivirus',
    });

    await kb.goto(id);
    await kb.editor.enterEditMode();

    await page.getByRole('button', { name: 'Select an illustration' }).click();
    const modal = page.getByTestId('illustration-picker-modal');
    await expect(modal).toBeVisible();
    await modal.getByRole('button', { name: 'No illustration' }).click();
    await expect(modal).toBeHidden();
    await expect(page.getByTestId('illustration-input')).toHaveValue('');

    await kb.editor.cancel();

    const picker = page.getByTestId('illustration-picker');
    await expect(page.getByTestId('illustration-input')).toHaveValue('antivirus');
    await expect(picker.getByRole('img', { name: 'Antivirus', exact: true })).toBeVisible();
});

test('History panel shows "Illustration removed by" when illustration is cleared', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const id = await api.createItem('KnowbaseItem', {
        name: 'KB optional icon history test',
        entities_id: getWorkerEntityId(),
        answer: 'My answer',
        illustration: 'antivirus',
    });

    await kb.goto(id);
    await kb.editor.enterEditMode();

    await page.getByRole('button', { name: 'Select an illustration' }).click();
    const modal = page.getByTestId('illustration-picker-modal');
    await expect(modal).toBeVisible();
    await modal.getByRole('button', { name: 'No illustration' }).click();
    await expect(modal).toBeHidden();

    await kb.editor.save();

    await kb.doOpenHistoryPanel();
    await expect(kb.getHistoryEventByText('Illustration removed by')).toBeVisible();
});

test('Aside renders no svg for an article without illustration', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const article_name = `KB optional icon aside ${randomUUID().slice(0, 8)}`;
    const id = await api.createItem('KnowbaseItem', {
        name: article_name,
        entities_id: getWorkerEntityId(),
        answer: 'My answer',
    });

    await kb.goto(id);

    const aside_link = page.getByRole('main')
        .getByRole('complementary')
        .getByRole('link', { name: article_name })
    ;
    await expect(aside_link).toBeVisible();
    await expect(aside_link.getByRole('img')).toHaveCount(0);
});
