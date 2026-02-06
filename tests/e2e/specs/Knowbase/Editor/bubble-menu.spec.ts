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

test.describe('Knowledge Base Editor - Bubble Menu', () => {
    test.describe('Text Formatting', () => {

        test('Can apply bold formatting via bubble menu', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Test bold formatting',
                entities_id: getWorkerEntityId(),
                answer: '<p>Text to format</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();

            await kb.bubbleMenu.selectAllContent();
            await kb.bubbleMenu.clickButton('Bold');
            await kb.editor.save();

            await kb.editor.assertHasBold('Text to format');
        });

        test('Can apply italic formatting via bubble menu', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Test italic formatting',
                entities_id: getWorkerEntityId(),
                answer: '<p>Text to format</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();

            await kb.bubbleMenu.selectAllContent();
            await kb.bubbleMenu.clickButton('Italic');
            await kb.editor.save();

            await kb.editor.assertHasItalic('Text to format');
        });

        test('Can apply strikethrough formatting via bubble menu', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Test strikethrough formatting',
                entities_id: getWorkerEntityId(),
                answer: '<p>Text to format</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();

            await kb.bubbleMenu.selectAllContent();
            await kb.bubbleMenu.clickButton('Strikethrough');
            await kb.editor.save();

            await kb.editor.assertHasStrikethrough('Text to format');
        });

        test('Can apply code formatting via bubble menu', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Test code formatting',
                entities_id: getWorkerEntityId(),
                answer: '<p>Text to format</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();

            await kb.bubbleMenu.selectAllContent();
            await kb.bubbleMenu.clickButton('Code');
            await kb.editor.save();

            await kb.editor.assertHasCode('Text to format');
        });
    });

    test.describe('Headings', () => {

        test('Can convert text to heading via bubble menu', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Test heading conversion',
                entities_id: getWorkerEntityId(),
                answer: '<p>My heading text</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();

            await kb.bubbleMenu.selectAllContent();
            await kb.bubbleMenu.clickButton('Heading 1');
            await kb.editor.save();

            await kb.editor.assertHasHeading(1, 'My heading text');
        });

    });

    test.describe('Lists', () => {

        test('Can convert text to bullet list via bubble menu', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Test bullet list',
                entities_id: getWorkerEntityId(),
                answer: '<p>List item content</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();

            await kb.bubbleMenu.selectAllContent();
            await kb.bubbleMenu.clickButton('Bullet List');
            await kb.editor.save();

            await kb.editor.assertHasListItem('List item content');
        });

        test('Can convert text to numbered list via bubble menu', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Test numbered list',
                entities_id: getWorkerEntityId(),
                answer: '<p>Numbered item</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();

            await kb.bubbleMenu.selectAllContent();
            await kb.bubbleMenu.clickButton('Numbered List');
            await kb.editor.save();

            await kb.editor.assertHasListItem('Numbered item');
        });
    });

    test.describe('Links', () => {

        test('Can add link via bubble menu', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Test add link',
                entities_id: getWorkerEntityId(),
                answer: '<p>Link text</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();

            await kb.bubbleMenu.selectAllContent();
            await kb.bubbleMenu.setLink('https://example.com');
            await kb.editor.save();

            await kb.editor.assertHasLink('Link text', 'https://example.com');
        });

        test('Can remove link via bubble menu', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Test remove link',
                entities_id: getWorkerEntityId(),
                answer: '<p><a href="https://example.com">Linked text</a></p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();

            await kb.bubbleMenu.selectAllContent();
            await kb.bubbleMenu.removeLink();
            await kb.editor.save();

            await kb.editor.assertContainsText('Linked text');
            // eslint-disable-next-line playwright/no-raw-locators
            const link = page.getByTestId('content').locator('a');
            await link.waitFor({ state: 'hidden', timeout: 5000 });
        });

        test('Remove link button only visible when link exists', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Test remove link visibility',
                entities_id: getWorkerEntityId(),
                answer: '<p>Plain text</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();

            await kb.bubbleMenu.selectAllContent();
            await kb.bubbleMenu.assertButtonHidden('Remove link');
            await kb.bubbleMenu.assertButtonVisible('Link');

            await kb.editor.cancel();
        });
    });

    test.describe('Button State Management', () => {

        test('Buttons show active state when formatting applied', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Test button states',
                entities_id: getWorkerEntityId(),
                answer: '<p><strong>Bold text</strong></p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();

            await kb.bubbleMenu.selectAllContent();
            await kb.bubbleMenu.assertButtonActive('Bold');
            await kb.bubbleMenu.assertButtonInactive('Italic');

            await kb.editor.cancel();
        });
    });
});
