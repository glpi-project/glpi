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

export class PreferencePage extends GlpiPage
{
    public backcreated_select: Locator;

    public constructor(page: Page)
    {
        super(page);

        this.backcreated_select = page.getByLabel('Go to created item after creation', { exact: true });
    }

    public async gotoPersonalizationTab(): Promise<void>
    {
        await this.page.goto('/front/preference.php?forcetab=Config$1');
    }

    /**
     * Get the "Go to created item after creation" preference, as currently
     * applied to the user (user value or global default).
     */
    public async getBackcreatedValue(): Promise<boolean>
    {
        return await this.backcreated_select.inputValue() === '1';
    }
}
