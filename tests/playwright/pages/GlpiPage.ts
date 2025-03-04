/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

import { expect, type Locator, type Page } from '@playwright/test';

/**
 * Store common actions that can be executed on any GLPI page.
 * See https://playwright.dev/docs/pom.
 */
export class GlpiPage
{
    protected readonly page: Page;
    protected readonly userMenu: Locator;
    protected readonly logoutLink: Locator;
    protected readonly changeProfileButton: Locator;

    public constructor(page: Page) {
        this.page = page;

        this.userMenu = page.getByRole('link', {name: 'User menu'});
        this.logoutLink = page.getByRole('link', {name: 'Logout'});
        this.changeProfileButton = page.getByRole('button', {name: 'Change profile'});
    }

    /**
     * Locate the orignal <select> item using its label.
     * Select2's container is the span right after the select.
     * The interactive element is the combobox inside the container.
     */
    public getDropdownByLabel(label: string) {
        // eslint-disable-next-line playwright/no-raw-locators
        return this.page
            .getByLabel(label)
            .locator('+ span')
            .getByRole('combobox')
        ;
    }

    public async setDropdownValue(dropdown: Locator, value: string) {
        await dropdown.click();
        await this.page
            .getByRole('listbox')
            .getByRole('option', {'name': value})
            .click()
        ;
        await expect(dropdown).toContainText(value);
    }

    /**
     * Locate the original <textarea> item using its label.
     * TinyMCE's container is the div right after the textarea.
     * The interactive element is the body of the iframe.
     */
    public getRichTextByLabel(label: string) {
        // eslint-disable-next-line playwright/no-raw-locators
        return this.page
            .getByLabel(label)
            .locator('+ div')
            .locator('iframe:visible')
            .contentFrame()
            .locator('body')
        ;
    }

    /**
     * Html sortable add the wrong "option" role to its items.
     * We must remove it to be able to interact with the items the right way.
     */
    public async fixHtmlSortableRoles() {
        const draggables = this.page.getByRole('option')
            // eslint-disable-next-line playwright/no-raw-locators
            .and(this.page.locator('[draggable=true]'))
        ;

        // Wait for the sortable lib to be loaded.
        await expect(draggables).not.toHaveCount(0);
        for (const draggable of await draggables.all()) {
            draggable.evaluate((node) => {
                node.removeAttribute('role');
            });
        }
    }

    public async logout() {
        await this.userMenu.click();
        await this.logoutLink.click();
    }

    public async changeProfile(profile: string) {
        await this.userMenu.click();
        await this.changeProfileButton.click();
        await this.page.getByRole('button', {name: profile}).click();
    }

    public getUserMenu() {
        return this.userMenu;
    }

    public getMenuEntry(entry: string) {
        return this.page.getByRole('listitem', {'name': entry});
    }
}
