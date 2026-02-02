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

test.describe('Knowledge Base Editor', () => {
    // Run tests serially to avoid race conditions with lazy-loaded editor bundle
    test.describe.configure({ mode: 'serial' });
    test('Can edit article using slash commands, save, and persist after reload', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        // Create a KB item
        const id = await api.createItem('KnowbaseItem', {
            name: 'Test article for editor',
            entities_id: getWorkerEntityId(),
            answer: '<p>Initial content</p>',
        });

        // Go to article
        await kb.goto(id);
        await expect(page.getByTestId('content')).toContainText('Initial content');

        // Wait for TipTap bundle to be fully loaded (sets window.TiptapCore)
        await page.waitForFunction(() => typeof (window as unknown as { TiptapCore: unknown }).TiptapCore !== 'undefined', {
            timeout: 10000
        });

        // Click Edit button to enter edit mode (editor is lazy loaded on first click)
        await page.getByTestId('edit-button').click();

        // Wait for TipTap to create the ProseMirror editor element
        // eslint-disable-next-line playwright/no-raw-locators
        const editor = page.getByTestId('content').locator('.ProseMirror');
        await expect(editor).toBeVisible({ timeout: 15000 });

        // Click to focus
        await editor.click();
        await expect(page.getByTestId('save-button')).toBeVisible();

        // Clear existing content and use slash command to insert a heading
        await page.keyboard.press('Control+a');
        await page.keyboard.press('Backspace');

        // Type "/" to trigger slash commands menu
        await editor.pressSequentially('/');

        // Wait for slash menu to appear and select "Heading 1"
        const heading1Button = page.getByRole('button', { name: 'Heading 1' });
        await expect(heading1Button).toBeVisible();
        await heading1Button.click();

        // Type heading content
        await editor.pressSequentially('My Test Heading');

        // Press Enter to create new paragraph
        await page.keyboard.press('Enter');

        // Use slash command to insert a bullet list
        await editor.pressSequentially('/');
        const bulletListButton = page.getByRole('button', { name: 'Bullet List' });
        await expect(bulletListButton).toBeVisible();
        await bulletListButton.click();

        // Type list item content
        await editor.pressSequentially('First list item');

        // Save the article
        await page.getByTestId('save-button').click();

        // Wait for save to complete (button should be hidden, edit button visible)
        await expect(page.getByTestId('save-button')).toBeHidden();
        await expect(page.getByTestId('edit-button')).toBeVisible();

        // Verify content is displayed correctly
        const content = page.getByTestId('content');
        await expect(content.getByRole('heading', { level: 1 })).toContainText('My Test Heading');
        await expect(content.getByRole('listitem')).toContainText('First list item');

        // Reload the page to verify persistence
        await page.reload();

        // Wait for page to load
        await expect(page.getByTestId('content')).toBeVisible();

        // Verify content persisted after reload
        const reloadedContent = page.getByTestId('content');
        await expect(reloadedContent.getByRole('heading', { level: 1 })).toContainText('My Test Heading');
        await expect(reloadedContent.getByRole('listitem')).toContainText('First list item');
    });

    test('Slash commands menu shows filtered results when typing', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        // Create a KB item
        const id = await api.createItem('KnowbaseItem', {
            name: 'Test slash commands filtering',
            entities_id: getWorkerEntityId(),
            answer: '<p>Content</p>',
        });

        // Go to article
        await kb.goto(id);
        await expect(page.getByTestId('content')).toBeVisible();

        // Wait for TipTap bundle to be fully loaded (sets window.TiptapCore)
        await page.waitForFunction(() => typeof (window as unknown as { TiptapCore: unknown }).TiptapCore !== 'undefined', {
            timeout: 10000
        });

        // Enter edit mode (editor is lazy loaded on first click)
        await page.getByTestId('edit-button').click();

        // Wait for TipTap to create the ProseMirror editor element
        // eslint-disable-next-line playwright/no-raw-locators
        const editor = page.getByTestId('content').locator('.ProseMirror');
        await expect(editor).toBeVisible({ timeout: 15000 });

        // Click to focus
        await editor.click();
        await expect(page.getByTestId('save-button')).toBeVisible();

        // Clear and type slash command with filter
        await page.keyboard.press('Control+a');
        await page.keyboard.press('Backspace');

        // Type "/head" to filter for headings
        await editor.pressSequentially('/head');

        // Should show heading options
        await expect(page.getByRole('button', { name: 'Heading 1' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Heading 2' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Heading 3' })).toBeVisible();

        // Should NOT show non-matching options
        await expect(page.getByRole('button', { name: 'Bullet List' })).toBeHidden();
    });

    test('Can navigate slash menu with keyboard', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        // Create a KB item
        const id = await api.createItem('KnowbaseItem', {
            name: 'Test keyboard navigation',
            entities_id: getWorkerEntityId(),
            answer: '<p>Content</p>',
        });

        // Go to article
        await kb.goto(id);
        await expect(page.getByTestId('content')).toBeVisible();

        // Wait for TipTap bundle to be fully loaded (sets window.TiptapCore)
        await page.waitForFunction(() => typeof (window as unknown as { TiptapCore: unknown }).TiptapCore !== 'undefined', {
            timeout: 10000
        });

        // Enter edit mode (editor is lazy loaded on first click)
        await page.getByTestId('edit-button').click();

        // Wait for TipTap to create the ProseMirror editor element
        // eslint-disable-next-line playwright/no-raw-locators
        const editor = page.getByTestId('content').locator('.ProseMirror');
        await expect(editor).toBeVisible({ timeout: 15000 });

        // Click to focus
        await editor.click();
        await expect(page.getByTestId('save-button')).toBeVisible();

        // Clear and open slash menu
        await page.keyboard.press('Control+a');
        await page.keyboard.press('Backspace');
        await editor.pressSequentially('/');

        // Wait for menu to appear
        const heading1Button = page.getByRole('button', { name: 'Heading 1' });
        await expect(heading1Button).toBeVisible();

        // First item should be selected by default
        // eslint-disable-next-line playwright/no-raw-locators
        await expect(page.locator('.slash-menu-item.is-selected')).toContainText('Heading 1');

        // Navigate down with arrow key
        await page.keyboard.press('ArrowDown');
        // eslint-disable-next-line playwright/no-raw-locators
        await expect(page.locator('.slash-menu-item.is-selected')).toContainText('Heading 2');

        // Navigate up with arrow key
        await page.keyboard.press('ArrowUp');
        // eslint-disable-next-line playwright/no-raw-locators
        await expect(page.locator('.slash-menu-item.is-selected')).toContainText('Heading 1');

        // Select with Enter key (Heading 1)
        await page.keyboard.press('Enter');

        // Menu should close
        await expect(heading1Button).toBeHidden();

        // Should have inserted a heading
        await expect(editor.getByRole('heading', { level: 1 })).toBeVisible();
    });

    test('Cancel button discards changes', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        // Create a KB item with specific content
        const id = await api.createItem('KnowbaseItem', {
            name: 'Test cancel functionality',
            entities_id: getWorkerEntityId(),
            answer: '<p>Original content that should persist</p>',
        });

        // Go to article
        await kb.goto(id);
        await expect(page.getByTestId('content')).toContainText('Original content that should persist');

        // Wait for TipTap bundle to be fully loaded (sets window.TiptapCore)
        await page.waitForFunction(() => typeof (window as unknown as { TiptapCore: unknown }).TiptapCore !== 'undefined', {
            timeout: 10000
        });

        // Enter edit mode (editor is lazy loaded on first click)
        await page.getByTestId('edit-button').click();

        // Wait for TipTap to create the ProseMirror editor element
        // eslint-disable-next-line playwright/no-raw-locators
        const editor = page.getByTestId('content').locator('.ProseMirror');
        await expect(editor).toBeVisible({ timeout: 15000 });

        // Click to focus
        await editor.click();
        await expect(page.getByTestId('save-button')).toBeVisible();

        // Modify content
        await page.keyboard.press('Control+a');
        await page.keyboard.press('Backspace');
        await editor.pressSequentially('Modified content that should be discarded');

        // Click cancel
        await page.getByTestId('cancel-button').click();

        // Should exit edit mode
        await expect(page.getByTestId('edit-button')).toBeVisible();
        await expect(page.getByTestId('save-button')).toBeHidden();
        await expect(page.getByTestId('cancel-button')).toBeHidden();

        // Original content should be restored
        await expect(page.getByTestId('content')).toContainText('Original content that should persist');
    });
});
