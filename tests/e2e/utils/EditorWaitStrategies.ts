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

import { Page, Locator, expect } from '@playwright/test';

export const EDITOR_TIMEOUTS = {
    BUNDLE_LOAD: 15000,
    EDITOR_INIT: 15000,
    MENU_APPEAR: 5000,
    SAVE_COMPLETE: 10000,
} as const;

export async function waitForTipTapBundle(page: Page): Promise<void> {
    await page.waitForFunction(
        () => typeof (window as unknown as { TiptapCore: unknown }).TiptapCore !== 'undefined',
        { timeout: EDITOR_TIMEOUTS.BUNDLE_LOAD }
    );
}

export async function waitForEditorReady(
    contentContainer: Locator
): Promise<Locator> {
    // eslint-disable-next-line playwright/no-raw-locators
    const editor = contentContainer.locator('.ProseMirror');
    await expect(editor).toBeVisible({ timeout: EDITOR_TIMEOUTS.EDITOR_INIT });
    await expect(editor).toHaveAttribute('contenteditable', 'true', {
        timeout: EDITOR_TIMEOUTS.EDITOR_INIT
    });
    return editor;
}

export async function waitForSlashMenuVisible(page: Page): Promise<Locator> {
    // eslint-disable-next-line playwright/no-raw-locators
    const slashMenu = page.locator('.slash-menu');
    await expect(slashMenu).toBeVisible({ timeout: EDITOR_TIMEOUTS.MENU_APPEAR });
    // eslint-disable-next-line playwright/no-raw-locators
    const hasItems = slashMenu.locator('.slash-menu-item').first();
    // eslint-disable-next-line playwright/no-raw-locators
    const hasNoResults = slashMenu.locator('.slash-menu-empty');
    await expect(hasItems.or(hasNoResults)).toBeVisible();
    return slashMenu;
}

export async function waitForSlashMenuHidden(page: Page): Promise<void> {
    // eslint-disable-next-line playwright/no-raw-locators
    const slashMenu = page.locator('.slash-menu');
    await expect(slashMenu).toBeHidden({ timeout: EDITOR_TIMEOUTS.MENU_APPEAR });
}

export async function waitForFullEditorInit(
    page: Page,
    contentContainer: Locator,
    editButton: Locator
): Promise<Locator> {
    await expect(contentContainer).toBeVisible({ timeout: EDITOR_TIMEOUTS.EDITOR_INIT });
    // eslint-disable-next-line playwright/no-networkidle
    await page.waitForLoadState('networkidle');
    await waitForTipTapBundle(page);
    await editButton.click();
    const editor = await waitForEditorReady(contentContainer);
    await editor.click();
    await expect(page.getByTestId('save-button')).toBeVisible();
    return editor;
}
