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

import { Locator, type Page } from '@playwright/test';
import { GlpiPage } from './GlpiPage';

/**
 * Store common actions that can be executed on any CommonDBTM page.
 */
export class CommonDBTMPage extends GlpiPage
{
    public readonly history_rows: Locator;

    public constructor(page: Page)
    {
        super(page);

        this.history_rows = page.getByRole('row');
    }

    public async goToTab(tab: string|RegExp): Promise<void>
    {
        await this.page.getByRole('tab', { name: tab, exact: true }).click();
    }

    public getTab(tab: string): Locator
    {
        return this.page.getByRole('tab', { name: tab, exact: true});
    }
}
