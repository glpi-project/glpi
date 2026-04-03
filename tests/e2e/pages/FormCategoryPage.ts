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

export class FormCategoryPage extends GlpiPage
{
    public readonly name_input: Locator;
    public readonly description_input: Locator;

    public constructor(page: Page)
    {
        super(page);

        this.name_input        = page.getByLabel('Name', { exact: true });
        this.description_input = this.getRichTextByLabel('Description');
    }

    public async gotoList(): Promise<void>
    {
        await this.page.goto('/front/form/category.php');
    }

    public async goto(id: number): Promise<void>
    {
        // Force main tab.
        await this.gotoWithTab(id, 'Glpi\\Form\\Category$main');
    }

    public async gotoWithTab(id: number, tab: string): Promise<void>
    {
        await this.page.goto(`/front/form/category.form.php?id=${id}&forcetab=${tab}`);
    }
}
