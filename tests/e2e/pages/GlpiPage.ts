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
 */
export class GlpiPage
{
    public readonly page: Page;
    public readonly user_menu: Locator;
    public readonly logout_link: Locator;
    public readonly change_profile_button: Locator;
    public readonly history_rows: Locator;
    public readonly dashboards_widgets: Locator;
    public readonly active_entity: Locator;

    public constructor(page: Page)
    {
        this.page = page;

        // Define locators
        this.user_menu             = this.getLink('User menu');
        this.logout_link           = this.getLink('Logout');
        this.change_profile_button = this.getButton('Change profile');
        this.history_rows          = page.getByRole('row');
        this.dashboards_widgets    = page.getByTestId("dashboard-widget");

        // .first() because we always display this information twice, with only
        // one being shown depending on the screen size.
        this.active_entity         = page.getByTestId("current-entity").first();
    }

    public async doSetDropdownValue(
        dropdown: Locator,
        value: string
    ):  Promise<void> {
        await dropdown.click();
        await this.page
            .getByRole('listbox')
            .getByRole('option', {'name': value})
            .click()
        ;
        await expect(dropdown).toContainText(value);
    }

    public async doLogout(): Promise<void>
    {
        await this.user_menu.click();
        await this.logout_link.click();
    }

    public async doChangeProfile(profile: string): Promise<void>
    {
        await this.user_menu.click();
        await this.change_profile_button.click();
        await this.page.getByRole('button', {name: profile}).click();
    }

    public async doGoToTab(name: string): Promise<void>
    {
        const tab = this.getTab(name);
        await tab.click();
    }

    public async doOpenEntitySelector(): Promise<void>
    {
        await this.page.keyboard.press('Control+Alt+KeyE');
    }

    public async doSwitchToAllEntities(): Promise<void>
    {
        await this.getButton("Select all").click();
    }

    public async doSearchForEntity(entity_name: string): Promise<void>
    {
        await this.getTextbox("Search entity").fill(entity_name);
        await this.getButton("Search").click();
    }

    public async doSwitchToEntityWithRecursion(
        entity_name: string
    ): Promise<void> {
        await this.getButton(
            `Select ${entity_name} entity with all its sub entities`
        ).click();
    }

    public async doSwitchToEntityWithoutRecursion(
        entity_name: string
    ): Promise<void> {
        await this.getButton(entity_name).click();
    }

    /**
     * Locate the orignal <select> item using its label.
     * Select2's container is the span right after the select.
     * The interactive element is the combobox inside the container.
     */
    public getDropdownByLabel(label: string): Locator
    {
        // eslint-disable-next-line playwright/no-raw-locators
        return this.page
            .getByLabel(label)
            .locator('+ span')
            .getByRole('combobox')
        ;
    }

    /**
     * Locate the original <textarea> item using its label.
     * TinyMCE's container is the div right after the textarea.
     * The interactive element is the body of the iframe.
     */
    public getRichTextByLabel(label: string): Locator
    {
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
     * Helper method to make common operation less verbose
     */
    public getLink(name: string): Locator
    {
        return this.page.getByRole('link', {
            name: name,
            exact: true,
        });
    }

    /**
     * Helper method to make common operation less verbose
     */
    public getButton(name: string): Locator
    {
        return this.page.getByRole('button', {
            name: name,
            exact: true,
        });
    }

    /**
     * Helper method to make common operation less verbose
     */
    public getCheckbox(name: string): Locator
    {
        return this.page.getByRole('checkbox', {
            name: name,
            exact: true,
        });
    }

    /**
     * Helper method to make common operation less verbose
     */
    public getTextbox(name: string): Locator
    {
        return this.page.getByRole('textbox', {
            name: name,
            exact: true,
        });
    }

    /**
     * Helper method to make common operation less verbose
     */
    public getTab(name: string): Locator
    {
        return this.page.getByRole('tab', {
            name: name,
            exact: true,
        });
    }

    public getEntityFromTree(name: string): Locator
    {
        return this.page.getByRole('gridcell', {
            name: name,
        });
    }
}
