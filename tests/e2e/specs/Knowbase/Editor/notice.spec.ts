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

import { expect, Page, test } from "../../../fixtures/glpi_fixture";
import { KnowbaseItemPage } from "../../../pages/KnowbaseItemPage";
import { Api } from "../../../utils/Api";
import { ProfileSwitcher } from "../../../utils/ProfileSwitcher";
import { Profiles } from "../../../utils/Profiles";
import { getWorkerEntityId } from "../../../utils/WorkerEntities";

const NOTICES = ['Info notice', 'Success notice', 'Warning notice', 'Danger notice'] as const;

async function openEmptyEditor(
    page: Page,
    profile: ProfileSwitcher,
    api: Api,
    name: string,
): Promise<KnowbaseItemPage> {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);
    await kb.goto(await api.knowbase.createArticle({ name }));
    await kb.editor.enterEditMode();
    await kb.editor.clearContent();
    return kb;
}

test.describe('Knowledge Base Editor - Notices', () => {
    for (const notice of NOTICES) {
        test(`Can insert a ${notice} and keep it after reload`, async ({ page, profile, api }) => {
            const kb = await openEmptyEditor(page, profile, api, `Test ${notice}`);

            await kb.slashMenu.insert(notice, 'click');
            await kb.editor.typeText('Notice text');
            await kb.editor.save();

            await page.reload();
            await expect(page.getByRole('note', { name: notice })).toHaveText('Notice text');
        });
    }

    test('A saved notice can be edited again', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Test notice edit again',
            entities_id: getWorkerEntityId(),
            answer: '<div class="kb-notice kb-notice-warning"><div class="kb-notice-content"><p>Saved text</p></div></div>',
        });

        await kb.goto(id);
        const notice = page.getByRole('note', { name: 'Warning notice' });
        await expect(notice).toHaveText('Saved text');

        // The editor parses the rendered HTML: the icon must not become content.
        await kb.editor.enterEditMode();
        await notice.getByText('Saved text').click();
        await kb.editor.pressKey('End');
        await kb.editor.typeText(' edited');
        await kb.editor.save();

        await page.reload();
        await expect(notice).toHaveText('Saved text edited');
        await expect(notice.getByRole('paragraph')).toHaveCount(1);
    });

    test('Can insert a notice with the keyboard', async ({ page, profile, api }) => {
        const kb = await openEmptyEditor(page, profile, api, 'Test notice keyboard');

        await kb.slashMenu.insert('Warning notice', 'keyboard');
        await kb.editor.typeText('Keyboard notice');

        await expect(page.getByRole('note', { name: 'Warning notice' })).toHaveText('Keyboard notice');
    });

    test('Enter on an empty last paragraph leaves the notice', async ({ page, profile, api }) => {
        const kb = await openEmptyEditor(page, profile, api, 'Test notice exit');

        await kb.slashMenu.insert('Info notice', 'click');
        await kb.editor.typeText('Inside');
        await kb.editor.pressKey('Enter');
        await kb.editor.pressKey('Enter');
        await kb.editor.typeText('Outside');

        await expect(page.getByRole('note', { name: 'Info notice' })).toHaveText('Inside');
        await kb.editor.assertContainsText('Outside');
    });

    test('Enter on an empty middle line adds a line in the notice', async ({ page, profile, api }) => {
        const kb = await openEmptyEditor(page, profile, api, 'Test notice enter middle');

        await kb.slashMenu.insert('Info notice', 'click');
        await kb.editor.typeText('First');
        await kb.editor.pressKey('Enter');
        await kb.editor.typeText('Last');

        const notice = page.getByRole('note', { name: 'Info notice' });
        await notice.getByText('First').click();
        await kb.editor.pressKey('End');
        await kb.editor.pressKey('Enter');
        await kb.editor.pressKey('Enter');
        await kb.editor.typeText('Middle');

        await expect(page.getByRole('note')).toHaveCount(1);
        await expect(notice).toHaveText('FirstMiddleLast');
    });

    test('Backspace in an empty notice removes it', async ({ page, profile, api }) => {
        const kb = await openEmptyEditor(page, profile, api, 'Test notice removal');

        await kb.slashMenu.insert('Danger notice', 'click');
        await expect(page.getByRole('note', { name: 'Danger notice' })).toBeVisible();

        await kb.editor.pressKey('Backspace');
        await expect(page.getByRole('note', { name: 'Danger notice' })).toHaveCount(0);
    });

    test('Slash menu in a notice shows only the allowed blocks', async ({ page, profile, api }) => {
        const kb = await openEmptyEditor(page, profile, api, 'Test slash menu in notice');

        await kb.slashMenu.insert('Info notice', 'click');
        await kb.slashMenu.open();

        await kb.slashMenu.assertCommandVisible('Heading 1');
        await kb.slashMenu.assertCommandVisible('Bullet List');
        await kb.slashMenu.assertCommandVisible('Code Block');
        await kb.slashMenu.assertCommandVisible('Image');

        await kb.slashMenu.assertCommandHidden('Info notice');
        await kb.slashMenu.assertCommandHidden('Quote');
        await kb.slashMenu.assertCommandHidden('Table');
        await kb.slashMenu.assertCommandHidden('Divider');
        await kb.slashMenu.assertCommandHidden('Video');
    });

    test('Divider shortcut in a notice keeps the text and the notice', async ({ page, profile, api }) => {
        const kb = await openEmptyEditor(page, profile, api, 'Test divider shortcut in notice');

        await kb.slashMenu.insert('Info notice', 'click');
        await kb.editor.typeText('---');

        await expect(page.getByRole('note', { name: 'Info notice' })).toHaveText('---');
        await expect(page.getByRole('note')).toHaveCount(1);
        await expect(kb.editor.contentContainer.getByRole('separator')).toHaveCount(0);
    });

    test('Divider shortcut in a list item of a notice keeps the text', async ({ page, profile, api }) => {
        const kb = await openEmptyEditor(page, profile, api, 'Test divider shortcut in notice list');

        await kb.slashMenu.insert('Info notice', 'click');
        await kb.editor.typeText('- Item');
        await kb.editor.pressKey('Enter');
        await kb.editor.typeText('---');

        const notice = page.getByRole('note', { name: 'Info notice' });
        await expect(notice.getByRole('listitem')).toHaveText(['Item', '---']);
        await expect(kb.editor.contentContainer.getByRole('separator')).toHaveCount(0);
    });

    test('Enter after a divider shortcut in a notice adds a line', async ({ page, profile, api }) => {
        const kb = await openEmptyEditor(page, profile, api, 'Test divider shortcut and Enter in notice');

        await kb.slashMenu.insert('Info notice', 'click');
        await kb.editor.typeText('___');
        await kb.editor.pressKey('Enter');
        await kb.editor.typeText('Next');

        const notice = page.getByRole('note', { name: 'Info notice' });
        await expect(notice.getByRole('paragraph')).toHaveText(['___', 'Next']);
        await expect(kb.editor.contentContainer.getByRole('separator')).toHaveCount(0);
    });

    test('Enter in an empty code block in a notice adds a line in the code', async ({ page, profile, api }) => {
        const kb = await openEmptyEditor(page, profile, api, 'Test code block enter in notice');

        await kb.slashMenu.insert('Info notice', 'click');
        await kb.editor.typeText('First');
        await kb.editor.pressKey('Enter');
        await kb.editor.typeText('Last');

        const notice = page.getByRole('note', { name: 'Info notice' });
        await notice.getByText('First').click();
        await kb.editor.pressKey('End');
        await kb.editor.pressKey('Enter');
        await kb.editor.typeText('``` ');
        await kb.editor.pressKey('Enter');
        await kb.editor.typeText('code');

        await expect(notice.getByRole('code')).toHaveText('code');
        await expect(notice.getByRole('paragraph')).toHaveText(['First', 'Last']);
    });

    test('Pasting a table in a notice does not split it', async ({ page, profile, api }) => {
        const kb = await openEmptyEditor(page, profile, api, 'Test table paste in notice');

        await kb.slashMenu.insert('Info notice', 'click');
        await kb.editor.typeText('InOut');
        // In the middle of the text: at the end, the table goes after the notice.
        await kb.editor.pressKey('ArrowLeft');
        await kb.editor.pressKey('ArrowLeft');
        await kb.editor.pressKey('ArrowLeft');
        await kb.editor.getEditor().evaluate((element) => {
            const data = new DataTransfer();
            data.setData('text/html', '<table><tbody><tr><td>Cell</td></tr></tbody></table>');
            element.dispatchEvent(new ClipboardEvent('paste', { bubbles: true, cancelable: true, clipboardData: data }));
        });

        await expect(page.getByRole('note', { name: 'Info notice' })).toHaveText('InOut');
        await expect(page.getByRole('note')).toHaveCount(1);
        await expect(kb.editor.contentContainer.getByRole('table')).toHaveCount(0);
    });

    test('Slash menu shows separators between groups', async ({ page, profile, api }) => {
        const kb = await openEmptyEditor(page, profile, api, 'Test slash menu separators');

        // Text, Lists, Blocks, Notices, Media
        await kb.slashMenu.open();
        await kb.slashMenu.assertSeparatorCount(4);
        await kb.slashMenu.close();

        await kb.slashMenu.openWithFilter('notice');
        for (const notice of NOTICES) {
            await kb.slashMenu.assertCommandVisible(notice);
        }
        await kb.slashMenu.assertCommandHidden('Heading 1');
        await kb.slashMenu.assertSeparatorCount(0);
        await kb.slashMenu.close();
    });
});
