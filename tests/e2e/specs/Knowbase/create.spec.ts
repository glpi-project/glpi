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
import { Profiles } from "../../utils/Profiles";

test('Can create an article with title and content', async ({ page, profile }) => {
    await profile.set(Profiles.SuperAdmin);

    // Navigate to the add article form
    await page.goto('/front/knowbaseitem.form.php');

    // The editor should be in edit mode automatically for new articles
    // eslint-disable-next-line playwright/no-raw-locators
    const editor = page.locator('#kb-tiptap-editor .ProseMirror');
    await expect(editor).toBeVisible();

    // Fill in the title
    const title = page.getByTestId('subject');
    await title.click();
    await title.fill('');
    await page.keyboard.type('My new KB article');

    // Fill in the content
    await editor.click();
    await page.keyboard.type('This is the content of my new article.');

    // Click the Add article button
    const add_button = page.getByRole('button', { name: 'Add article' });
    await expect(add_button).toBeVisible();
    await add_button.click();

    // After the page is reloaded, we expect the following data to be set
    await expect(page.getByTestId('subject')).toHaveText('My new KB article');
    await expect(page.getByTestId('content')).toContainText('This is the content of my new article.');
});
