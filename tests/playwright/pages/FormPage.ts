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

import { expect, Locator, Page } from 'playwright/test';
import { CommonDBTMPage } from './CommonDBTMPage';

export class FormPage extends CommonDBTMPage
{
    public static MAIN_TAB = 'Glpi\\Form\\Form$main';

    public readonly editor_active_checkbox: Locator;
    public readonly editor_save_button: Locator;
    public readonly editor_save_success_alert: Locator;

    public constructor(page: Page)
    {
        super(page);

        // Define locators
        this.editor_active_checkbox    = this.getCheckbox("Active");
        this.editor_save_button        = this.getButton("Save");
        this.editor_save_success_alert = page.getByRole('alert');
    }

    public async goto(id: number, tab: string = FormPage.MAIN_TAB): Promise<void>
    {
        await this.page.goto(`/front/form/form.form.php?id=${id}&forcetab=${tab}`);
    }

    public async setActive(): Promise<void>
    {
        await this.editor_active_checkbox.check();
    }

    public async saveFormEditor(): Promise<void>
    {
        await this.editor_save_button.click();
        await expect(this.editor_save_success_alert).toBeVisible();
        await expect(this.editor_save_success_alert).toContainText('Item successfully updated');
    }
}
