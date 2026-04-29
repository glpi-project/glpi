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

import { Page, expect, test } from "../../fixtures/glpi_fixture";
import { KnowbaseItemPage } from "../../pages/KnowbaseItemPage";
import { Profiles } from "../../utils/Profiles";
import { getWorkerEntityId } from "../../utils/WorkerEntities";

async function captureBeforeUnload(page: Page): Promise<boolean> {
    const dialogPromise = page
        .waitForEvent('dialog', { timeout: 2000 })
        .then(async (dialog) => {
            const isBeforeUnload = dialog.type() === 'beforeunload';
            await dialog.dismiss();
            return isBeforeUnload;
        })
        .catch(() => false);

    await page.close({ runBeforeUnload: true });
    return dialogPromise;
}

test('Warns before leaving page with unsaved edits', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const id = await api.createItem('KnowbaseItem', {
        name: 'Unsaved edits test',
        entities_id: getWorkerEntityId(),
        answer: '<p>Original</p>',
    });
    await kb.goto(id);

    await kb.editor.enterEditMode();
    await kb.editor.typeText('dirty');

    expect(await captureBeforeUnload(page)).toBe(true);
});

test('Does not warn after saving edits', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const id = await api.createItem('KnowbaseItem', {
        name: 'Saved edits test',
        entities_id: getWorkerEntityId(),
        answer: '<p>Original</p>',
    });
    await kb.goto(id);

    await kb.editor.enterEditMode();
    await kb.editor.typeText('dirty');
    await kb.editor.save();

    expect(await captureBeforeUnload(page)).toBe(false);
});

test('Does not warn when submitting a new article via "Add"', async ({ page, profile }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    await page.goto('/front/knowbaseitem.form.php', { waitUntil: 'domcontentloaded' });
    await expect(kb.editor.getEditor()).toHaveAttribute('contenteditable', 'true');

    await kb.subject.click();
    await page.keyboard.type('New article title');

    let beforeUnloadFired = false;
    page.on('dialog', async (dialog) => {
        if (dialog.type() === 'beforeunload') {
            beforeUnloadFired = true;
        }
        await dialog.accept();
    });

    await page.getByRole('button', { name: 'Add article' }).click();
    await page.waitForURL(/id=\d+/);

    expect(beforeUnloadFired).toBe(false);
});

test('Warns before leaving page with an unsaved new article draft', async ({ page, profile }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    await page.goto('/front/knowbaseitem.form.php', { waitUntil: 'domcontentloaded' });
    await expect(kb.editor.getEditor()).toHaveAttribute('contenteditable', 'true');

    await kb.subject.click();
    await page.keyboard.type('Draft never saved');

    expect(await captureBeforeUnload(page)).toBe(true);
});

test('Regression : does not warn after switching translation language without editing', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const id = await api.createItem('KnowbaseItem', {
        name: 'Switch language test',
        entities_id: getWorkerEntityId(),
        answer: '<p>Base content</p>',
    });
    await api.createItem('KnowbaseItemTranslation', {
        knowbaseitems_id: id,
        language: 'fr_FR',
        name: 'Contenu français',
        answer: '<p>Contenu de base</p>',
    });
    await api.createItem('KnowbaseItemTranslation', {
        knowbaseitems_id: id,
        language: 'es_ES',
        name: 'Contenido español',
        answer: '<p>Contenido base</p>',
    });
    await kb.goto(id);

    await page.getByTestId('translations-count').click();
    await expect(page.getByTestId('translation-mode-alert')).toBeVisible();

    const language_select = page.getByTestId('translation-language-select');
    await language_select.selectOption('es_ES');
    await expect(page.getByTestId('subject')).toHaveText('Contenido español');

    expect(await captureBeforeUnload(page)).toBe(false);
});
