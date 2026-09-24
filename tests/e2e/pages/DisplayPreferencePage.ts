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

import { expect, type FrameLocator, type Locator, type Page } from '@playwright/test';
import { GlpiPage } from './GlpiPage';

const SAVE_ENDPOINT = '/ajax/displaypreference.php';

/**
 * The "Select default items to show" modal of a search page.
 *
 * The forms of the modal are rendered inside an iframe, and each view (global,
 * personal, helpdesk) is a bootstrap tab of that same page. Once a tab has been
 * opened, its form stays in the DOM even when another tab is shown, thus every
 * locator must be limited to the form of the active tab.
 */
export class DisplayPreferencePage extends GlpiPage
{
    public readonly open_button: Locator;

    public constructor(page: Page)
    {
        super(page);
        this.open_button = this.getButton('Select default items to show');
    }

    /**
     * Open the modal and return the frame that holds its forms.
     */
    public async open(): Promise<FrameLocator>
    {
        await this.open_button.click();
        await expect(this.page.getByRole('dialog')).toBeVisible();

        return this.page.frameLocator('[data-testid="display-preference-iframe"]');
    }

    public async goToTab(frame: FrameLocator, name: string): Promise<void>
    {
        // The modal iframe is narrower than the "md" breakpoint, so GLPI
        // renders its tabs as a <select> (mobile layout) instead of the usual
        // nav-tabs.
        // eslint-disable-next-line playwright/no-raw-locators
        await frame.locator('#tabspanel-select').selectOption({ label: name });
        await expect(this.getActiveForm(frame)).toBeVisible();
    }

    public getAddOptionDropdown(frame: FrameLocator): Locator
    {
        // Select2 hides the original, labelled <select> and renders the visible
        // combobox into a sibling <span>.
        // eslint-disable-next-line playwright/no-raw-locators
        return this.getActiveForm(frame)
            .getByLabel('Select an option to add', { exact: true })
            .locator('+ span')
            .getByRole('combobox')
        ;
    }

    public async getAddOptionChoices(frame: FrameLocator): Promise<string[]>
    {
        const dropdown = this.getAddOptionDropdown(frame);
        await dropdown.click();
        const options = await frame.getByRole('listbox').getByRole('option').all();
        const texts = await Promise.all(options.map((option) => option.textContent()));
        await dropdown.click(); // Close the dropdown without selecting anything

        return texts
            .map((text) => (text ?? '').trim())
            .filter((text) => text.length > 0)
        ;
    }

    /**
     * The list rows are draggable <li> elements whose ARIA role is toggled
     * between "listitem" and "option" by the sortable library depending on
     * whether they went through its (re)initialization, so it cannot be relied
     * on to find a specific row: match on the "data-opt-id" attribute instead.
     */
    public getOptionRow(frame: FrameLocator, name: string): Locator
    {
        // eslint-disable-next-line playwright/no-raw-locators
        return this.getActiveForm(frame)
            .locator('li[data-opt-id]')
            .filter({ hasText: name })
        ;
    }

    public async addOption(frame: FrameLocator, name: string): Promise<void>
    {
        const dropdown = this.getAddOptionDropdown(frame);
        await dropdown.click();
        await frame
            .getByRole('listbox')
            .getByRole('option', { name: name, exact: true })
            .click()
        ;

        await this.saveAndWait(
            frame.getByRole('button', { name: 'Add' }).click()
        );
        await expect(this.getOptionRow(frame, name)).toBeVisible();
    }

    public async removeOption(frame: FrameLocator, name: string): Promise<void>
    {
        const row = this.getOptionRow(frame, name);

        // The remove button is icon-only and, unlike other icon buttons in this
        // form, its accessible name does not fall back to its "title"
        // attribute, so it cannot be matched by name; each row only has one
        // button though.
        await this.saveAndWait(row.getByRole('button').click());
        await expect(row).toBeHidden();
    }

    public async removeOptionIfPresent(
        frame: FrameLocator,
        tab: string,
        name: string,
    ): Promise<void> {
        await this.goToTab(frame, tab);
        const row = this.getOptionRow(frame, name);
        if (await row.count() === 0) {
            return;
        }

        await this.removeOption(frame, name);
    }

    /**
     * Personal preferences do not exist until explicitly activated. Make sure
     * they are, so that the personal form is rendered instead of the
     * "Create personal parameters?" prompt.
     *
     * @returns true if the personal view was created by this call.
     */
    public async ensurePersonalViewExists(frame: FrameLocator): Promise<boolean>
    {
        await this.goToTab(frame, 'Personal View');
        const create_button = this.getActiveForm(frame)
            .getByRole('button', { name: 'Create' })
        ;
        const add_dropdown = this.getAddOptionDropdown(frame);
        await expect(create_button.or(add_dropdown)).toBeVisible();

        const has_create_button = await create_button.isVisible();
        if (!has_create_button) {
            return false;
        }

        await create_button.click();
        await expect(add_dropdown).toBeVisible();

        return true;
    }

    public async deletePersonalView(frame: FrameLocator): Promise<void>
    {
        await this.goToTab(frame, 'Personal View');
        await this.getActiveForm(frame)
            .getByRole('button', { name: 'Delete personal view', exact: true })
            .click()
        ;
    }

    public async deletePersonalViewIfCreated(
        frame: FrameLocator,
        created: boolean,
    ): Promise<void> {
        if (!created) {
            return;
        }

        await this.deletePersonalView(frame);
    }

    /**
     * The form of the tab that is currently shown.
     *
     * The modal also holds tabs that are not display preference forms, thus
     * the "display_preference_config" class is needed to identify the right
     * one.
     */
    private getActiveForm(frame: FrameLocator): Locator
    {
        // eslint-disable-next-line playwright/no-raw-locators
        return frame
            .locator('.tab-pane.active .display_preference_config')
            .filter({ visible: true })
        ;
    }

    /**
     * Adding and removing an option only changes the DOM: the new list is sent
     * to the server by an asynchronous request. Without waiting for it, the
     * test could leave the page before the list is saved, and the change would
     * be lost.
     */
    private async saveAndWait(action: Promise<void>): Promise<void>
    {
        const response = this.page.waitForResponse(
            (response) => response.url().includes(SAVE_ENDPOINT)
                && response.request().method() === 'POST'
        );
        await action;
        await response;
    }
}
