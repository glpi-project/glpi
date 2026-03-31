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

export class LogViewerPage extends GlpiPage
{
    public readonly log_list_items: Locator;
    public readonly log_entries: Locator;
    public readonly log_entries_container: Locator;

    public constructor(page: Page)
    {
        super(page);

        this.log_list_items = page.getByTestId('log-list-item');
        this.log_entries = page.getByTestId('log-entry');
        this.log_entries_container = page.getByTestId('log-entries');
    }

    public async gotoLogList(): Promise<void>
    {
        await this.page.goto('/front/logs.php');
    }

    public async gotoLogViewer(filepath: string): Promise<void>
    {
        await this.page.goto(`/front/logviewer.php?filepath=${filepath}`);
    }
}
