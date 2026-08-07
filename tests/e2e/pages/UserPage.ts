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

import { Locator, Page } from "@playwright/test";
import { GlpiPage } from "./GlpiPage";

export class UserPage extends GlpiPage
{
    public readonly save_button: Locator;

    public constructor(page: Page)
    {
        super(page);
        this.save_button = page.getByRole('button', { name: "Save" });
    }

    public async gotoPreferences(tab?: string): Promise<void>
    {
        let url = '/front/preference.php';
        if (tab) {
            url += `?forcetab=${tab}`;
        }
        await this.page.goto(url);
    }

    public async gotoUserForm(user_id: number, tab?: string): Promise<void>
    {
        let url = `/front/user.form.php?id=${user_id}`;
        if (tab) {
            url += `&forcetab=${tab}`;
        }
        await this.page.goto(url);
    }

    public async doOpenChangePictureDialog(): Promise<void>
    {
        await this.page.getByTitle('Change picture').click();
    }

    public async doUploadPicture(file: string): Promise<void>
    {
        const dialog = this.page.getByRole('dialog');
        await this.doAddFileToUploadArea(file, dialog);
    }

    public async doRemoveUploadedFile(): Promise<void>
    {
        const dialog = this.page.getByRole('dialog');
        await dialog.getByTitle('Delete').click();
    }

    public async doAddNewEmailField(): Promise<void>
    {
        await this.page.getByLabel('Add a new Emails').click();
    }
}
