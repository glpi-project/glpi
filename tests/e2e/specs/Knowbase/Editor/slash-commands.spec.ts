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

import { test } from "../../../fixtures/glpi_fixture";
import { KnowbaseItemPage } from "../../../pages/KnowbaseItemPage";
import { Profiles } from "../../../utils/Profiles";
import { getWorkerEntityId } from "../../../utils/WorkerEntities";

test.describe('Knowledge Base Editor - Slash Commands', () => {
    test.describe('Insert via click', () => {

        test('Can insert Heading 1', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Test heading insertion',
                entities_id: getWorkerEntityId(),
                answer: '<p>Content</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();
            await kb.editor.clearContent();

            await kb.slashMenu.insert('Heading 1', 'click');
            await kb.editor.typeText('My Heading');
            await kb.editor.save();

            await kb.editor.assertHasHeading(1, 'My Heading');
        });

        test('Can insert Bullet List', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Test bullet list',
                entities_id: getWorkerEntityId(),
                answer: '<p>Content</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();
            await kb.editor.clearContent();

            await kb.slashMenu.insert('Bullet List', 'click');
            await kb.editor.typeText('List item');
            await kb.editor.save();

            await kb.editor.assertHasListItem('List item');
        });

        test('Can insert Table', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Test table insertion',
                entities_id: getWorkerEntityId(),
                answer: '<p>Content</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();
            await kb.editor.clearContent();

            await kb.slashMenu.insert('Table', 'click');
            await kb.editor.save();

            await kb.editor.assertHasTable();
        });
    });

    test.describe('Keyboard navigation', () => {

        test('Can navigate menu with arrow keys', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Test keyboard nav',
                entities_id: getWorkerEntityId(),
                answer: '<p>Content</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();
            await kb.editor.clearContent();

            await kb.slashMenu.open();

            await kb.slashMenu.assertSelectedItem('Heading 1');

            await page.keyboard.press('ArrowDown');
            await kb.slashMenu.assertSelectedItem('Heading 2');

            await page.keyboard.press('ArrowUp');
            await kb.slashMenu.assertSelectedItem('Heading 1');

            await kb.slashMenu.close();
        });

        test('Can select with Enter key', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Test enter selection',
                entities_id: getWorkerEntityId(),
                answer: '<p>Content</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();
            await kb.editor.clearContent();

            await kb.slashMenu.insert('Heading 2', 'keyboard');
            await kb.editor.typeText('Heading via keyboard');
            await kb.editor.save();

            await kb.editor.assertHasHeading(2, 'Heading via keyboard');
        });
    });

    test.describe('Filtering', () => {

        test('Shows filtered results when typing', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Test filtering',
                entities_id: getWorkerEntityId(),
                answer: '<p>Content</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();
            await kb.editor.clearContent();

            await kb.slashMenu.openWithFilter('head');

            await kb.slashMenu.assertCommandVisible('Heading 1');
            await kb.slashMenu.assertCommandVisible('Heading 2');
            await kb.slashMenu.assertCommandVisible('Heading 3');

            await kb.slashMenu.assertCommandHidden('Bullet List');
            await kb.slashMenu.assertCommandHidden('Table');

            await kb.slashMenu.close();
        });

        test('Shows no results for invalid filter', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Test no results',
                entities_id: getWorkerEntityId(),
                answer: '<p>Content</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();
            await kb.editor.clearContent();

            await kb.slashMenu.openWithFilter('xyz');
            await kb.slashMenu.assertNoResults();

            await kb.slashMenu.close();
        });
    });
});
