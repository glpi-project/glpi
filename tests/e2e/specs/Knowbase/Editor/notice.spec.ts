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

import { expect, test } from "../../../fixtures/glpi_fixture";
import { KnowbaseItemPage } from "../../../pages/KnowbaseItemPage";
import { Profiles } from "../../../utils/Profiles";
import { getWorkerEntityId } from "../../../utils/WorkerEntities";

const NOTICES = ['Info notice', 'Success notice', 'Warning notice', 'Tip notice'] as const;

test.describe('Knowledge Base Editor - Notices', () => {
    for (const notice of NOTICES) {
        test(`Can insert a ${notice} and keep it after reload`, async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: `Test ${notice}`,
                entities_id: getWorkerEntityId(),
                answer: '<p>Content</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();
            await kb.editor.clearContent();

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
            answer: '<div class="kb-notice kb-notice-warning"><p>Saved text</p></div>',
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
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Test notice keyboard',
            entities_id: getWorkerEntityId(),
            answer: '<p>Content</p>',
        });

        await kb.goto(id);
        await kb.editor.enterEditMode();
        await kb.editor.clearContent();

        await kb.slashMenu.insert('Warning notice', 'keyboard');
        await kb.editor.typeText('Keyboard notice');

        await expect(page.getByRole('note', { name: 'Warning notice' })).toHaveText('Keyboard notice');
    });

    test('Enter on an empty last paragraph leaves the notice', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Test notice exit',
            entities_id: getWorkerEntityId(),
            answer: '<p>Content</p>',
        });

        await kb.goto(id);
        await kb.editor.enterEditMode();
        await kb.editor.clearContent();

        await kb.slashMenu.insert('Info notice', 'click');
        await kb.editor.typeText('Inside');
        await kb.editor.pressKey('Enter');
        await kb.editor.pressKey('Enter');
        await kb.editor.typeText('Outside');

        await expect(page.getByRole('note', { name: 'Info notice' })).toHaveText('Inside');
        await kb.editor.assertContainsText('Outside');
    });

    test('Backspace in an empty notice removes it', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Test notice removal',
            entities_id: getWorkerEntityId(),
            answer: '<p>Content</p>',
        });

        await kb.goto(id);
        await kb.editor.enterEditMode();
        await kb.editor.clearContent();

        await kb.slashMenu.insert('Tip notice', 'click');
        await expect(page.getByRole('note', { name: 'Tip notice' })).toBeVisible();

        await kb.editor.pressKey('Backspace');
        await expect(page.getByRole('note', { name: 'Tip notice' })).toHaveCount(0);
    });

    test('Slash menu in a notice shows only the allowed blocks', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Test slash menu in notice',
            entities_id: getWorkerEntityId(),
            answer: '<p>Content</p>',
        });

        await kb.goto(id);
        await kb.editor.enterEditMode();
        await kb.editor.clearContent();

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

    test('Slash menu shows separators between groups', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Test slash menu separators',
            entities_id: getWorkerEntityId(),
            answer: '<p>Content</p>',
        });

        await kb.goto(id);
        await kb.editor.enterEditMode();
        await kb.editor.clearContent();

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
