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

    private readonly editorActiveCheckbox: Locator;
    private readonly editorSaveButton: Locator;
    private readonly editorSaveSuccessAlert: Locator;

    public constructor(page: Page) {
        super(page);

        this.editorActiveCheckbox = page.getByRole('checkbox', {'name': "Active"});
        this.editorSaveButton = page.getByRole('button', {
            name: "Save",
            exact: true,
        });
        this.editorSaveSuccessAlert = page.getByRole('alert');
    }

    public async goto(id: number, tab: string = FormPage.MAIN_TAB) {
        await this.page.goto(`/front/form/form.form.php?id=${id}&forcetab=${tab}`);
    }

    public async setActive() {
        await this.editorActiveCheckbox.check();
    }

    public async saveFormEditor() {
        await this.editorSaveButton.click();
        await expect(this.editorSaveSuccessAlert).toBeVisible();
        await expect(this.editorSaveSuccessAlert).toContainText('Item successfully updated');
    }
}
