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
            const link = kb.editor.contentContainer.getByRole('link');
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

    test.describe('Visibility', () => {

        test('Visible when selecting text in edit mode', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Test bubble menu visible in edit mode',
                entities_id: getWorkerEntityId(),
                answer: '<p>Selectable content</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();

            await kb.bubbleMenu.selectAllContent();
            await kb.bubbleMenu.assertVisible();

            await kb.editor.cancel();
        });

        test('Hidden when selecting text without entering edit mode', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Test bubble menu hidden in readonly',
                entities_id: getWorkerEntityId(),
                answer: '<p>Selectable content</p>',
            });

            await kb.goto(id);

            await kb.editor.contentContainer
                .getByText('Selectable content')
                .click({ clickCount: 3 });

            await kb.bubbleMenu.assertHidden();
        });

        test('Hidden when selecting text after exiting edit mode', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Test bubble menu hidden after exit',
                entities_id: getWorkerEntityId(),
                answer: '<p>Selectable content</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();
            await kb.editor.cancel();

            await kb.editor.contentContainer
                .getByText('Selectable content')
                .click({ clickCount: 3 });

            await kb.bubbleMenu.assertHidden();
        });
    });

    test.describe('Comments', () => {

        test('Can comment on a selection via the bubble menu', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Test comment via bubble menu',
                entities_id: getWorkerEntityId(),
                answer: '<p>Some text to comment on</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();

            await kb.bubbleMenu.selectAllContent();
            await kb.bubbleMenu.clickButton('Comment');

            await expect(kb.getPendingAnchorQuote()).toBeVisible();
            await kb.getNewCommentTextarea().fill('Commenting on this passage');
            await page.getByRole('button', { name: 'Add comment' }).click();

            await expect(kb.getComment('Commenting on this passage')).toBeVisible();
            await kb.editor.cancel();
        });

        test('Comment button is disabled for a selection longer than the anchor limit', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Test oversized selection in editor',
                // Over KnowbaseItem_Comment::MAX_ANCHOR_LENGTH.
                answer: `<p>${'a'.repeat(1001)}</p>`,
                entities_id: getWorkerEntityId(),
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();

            await kb.bubbleMenu.selectAllContent();
            await kb.bubbleMenu.assertButtonDisabled('Comment');

            await kb.editor.cancel();
        });
    });

    test.describe('Keyboard navigation', () => {

        test('Toolbar has correct ARIA semantics', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Test bubble menu aria semantics',
                entities_id: getWorkerEntityId(),
                answer: '<p>Text to format</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();
            await kb.bubbleMenu.selectAllContent();

            const menu = await kb.bubbleMenu.assertVisible();
            await expect(menu).toHaveAttribute('role', 'toolbar');
            await expect(menu).toHaveAttribute('aria-orientation', 'horizontal');
            await expect(menu).toHaveAttribute('aria-label', 'Text formatting');

            await kb.editor.cancel();
        });

        test('Arrow keys move focus between visible buttons with wraparound', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Test bubble menu arrow navigation',
                entities_id: getWorkerEntityId(),
                answer: '<p>Text to format</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();
            await kb.bubbleMenu.selectAllContent();

            const bold = kb.bubbleMenu.getButton('Bold');
            const italic = kb.bubbleMenu.getButton('Italic');
            const comment = kb.bubbleMenu.getButton('Comment');

            await bold.focus();
            await expect(bold).toHaveAttribute('tabindex', '0');
            await expect(italic).toHaveAttribute('tabindex', '-1');

            await page.keyboard.press('ArrowRight');
            await expect(italic).toBeFocused();
            await expect(italic).toHaveAttribute('tabindex', '0');
            await expect(bold).toHaveAttribute('tabindex', '-1');

            await page.keyboard.press('ArrowLeft');
            await expect(bold).toBeFocused();

            // Wrap backward from the first button to the last visible one.
            await page.keyboard.press('ArrowLeft');
            await expect(comment).toBeFocused();

            // Wrap forward from the last button back to the first.
            await page.keyboard.press('ArrowRight');
            await expect(bold).toBeFocused();

            await kb.editor.cancel();
        });

        test('Home and End jump to the first and last visible buttons', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Test bubble menu home end navigation',
                entities_id: getWorkerEntityId(),
                answer: '<p>Text to format</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();
            await kb.bubbleMenu.selectAllContent();

            const bold = kb.bubbleMenu.getButton('Bold');
            const comment = kb.bubbleMenu.getButton('Comment');
            const italic = kb.bubbleMenu.getButton('Italic');

            await italic.focus();
            await page.keyboard.press('Home');
            await expect(bold).toBeFocused();

            await page.keyboard.press('End');
            await expect(comment).toBeFocused();

            await kb.editor.cancel();
        });

        test('Hidden buttons are skipped during arrow navigation', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Test bubble menu skips hidden buttons',
                entities_id: getWorkerEntityId(),
                answer: '<p>Text to format</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();
            await kb.bubbleMenu.selectAllContent();

            // No link on this selection, so "Remove link" stays hidden between
            // "Link" and "Comment" — arrow navigation must skip over it.
            const link = kb.bubbleMenu.getButton('Link');
            const comment = kb.bubbleMenu.getButton('Comment');
            await kb.bubbleMenu.assertButtonHidden('Remove link');

            await link.focus();
            await page.keyboard.press('ArrowRight');
            await expect(comment).toBeFocused();

            await kb.editor.cancel();
        });

        test('Tab from a text selection focuses the bubble menu directly', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Test bubble menu tab entry',
                entities_id: getWorkerEntityId(),
                answer: '<p>Text to format</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();
            await kb.bubbleMenu.selectAllContent();

            await page.keyboard.press('Tab');
            await expect(kb.bubbleMenu.getButton('Bold')).toBeFocused();

            await kb.editor.cancel();
        });

        test('Shift+Tab from a text selection does not jump into the bubble menu', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Test bubble menu shift tab entry',
                entities_id: getWorkerEntityId(),
                answer: '<p>Text to format</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();
            await kb.bubbleMenu.selectAllContent();

            await page.keyboard.press('Shift+Tab');
            await expect(kb.bubbleMenu.getButton('Bold')).not.toBeFocused();

            await kb.editor.cancel();
        });

        test('Focus stays in the toolbar after activating a command via keyboard, allowing chained actions', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Test bubble menu focus retention',
                entities_id: getWorkerEntityId(),
                answer: '<p>Text to format</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();
            await kb.bubbleMenu.selectAllContent();

            await page.keyboard.press('Tab');
            await expect(kb.bubbleMenu.getButton('Bold')).toBeFocused();

            await page.keyboard.press('Enter');
            await expect(kb.bubbleMenu.getButton('Bold')).toBeFocused();
            await kb.bubbleMenu.assertButtonActive('Bold');

            // Chain a second action without leaving the toolbar.
            await page.keyboard.press('ArrowRight');
            await expect(kb.bubbleMenu.getButton('Italic')).toBeFocused();
            await page.keyboard.press('Enter');
            await kb.bubbleMenu.assertButtonActive('Italic');

            await kb.editor.save();
            await kb.editor.assertHasBold('Text to format');
            await kb.editor.assertHasItalic('Text to format');
        });

        test('Escape returns focus to the editor without clearing the selection', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Test bubble menu escape',
                entities_id: getWorkerEntityId(),
                answer: '<p>Text to format</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();
            await kb.bubbleMenu.selectAllContent();

            await page.keyboard.press('Tab');
            await expect(kb.bubbleMenu.getButton('Bold')).toBeFocused();

            await page.keyboard.press('Escape');
            await expect(kb.editor.getEditor()).toBeFocused();
            await kb.bubbleMenu.assertVisible();

            await kb.editor.cancel();
        });
    });

    test.describe('Shortcut discoverability', () => {

        test('Buttons with a default Tiptap shortcut expose it in their tooltip and aria-keyshortcuts', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Test bubble menu shortcut hints',
                entities_id: getWorkerEntityId(),
                answer: '<p>Text to format</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();
            await kb.bubbleMenu.selectAllContent();

            const bold = kb.bubbleMenu.getButton('Bold');
            await expect(bold).toHaveAttribute('aria-keyshortcuts', 'Control+b');
            await expect(bold).toHaveAttribute('title', /^Bold \((Ctrl\+B|⌘B)\)$/);

            const heading1 = kb.bubbleMenu.getButton('Heading 1');
            await expect(heading1).toHaveAttribute('aria-keyshortcuts', 'Control+Alt+1');
            await expect(heading1).toHaveAttribute('title', /^Heading 1 \((Ctrl\+Alt\+1|⌘⌥1)\)$/);

            await kb.editor.cancel();
        });

        test('Buttons without a default shortcut keep a plain tooltip', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Test bubble menu no shortcut for link',
                entities_id: getWorkerEntityId(),
                answer: '<p>Text to format</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();
            await kb.bubbleMenu.selectAllContent();

            const link = kb.bubbleMenu.getButton('Link');
            await expect(link).toHaveAttribute('title', 'Link');
            await expect(link).not.toHaveAttribute('aria-keyshortcuts');

            await kb.editor.cancel();
        });
    });
});
