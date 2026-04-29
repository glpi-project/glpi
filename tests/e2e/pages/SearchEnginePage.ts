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

export class SearchEnginePage extends GlpiPage
{
    public readonly search_page: Locator;
    public readonly search_filters_button: Locator;
    public readonly search_sorts_button: Locator;
    public readonly search_filters_panel: Locator;
    public readonly search_sorts_panel: Locator;

    public constructor(page: Page)
    {
        super(page);
        this.search_page           = page.getByTestId('search-page');
        this.search_filters_button = page.getByTestId('search-filters-button');
        this.search_sorts_button   = page.getByTestId('search-sorts-button');
        this.search_filters_panel  = page.getByTestId('search-filters-panel');
        this.search_sorts_panel    = page.getByTestId('search-sorts-panel');
    }

    public async goto(): Promise<void>
    {
        await this.page.goto('/front/computer.php');
    }

    public async doOpenSearchFilters(): Promise<void>
    {
        await this.search_filters_button.click();
    }

    public async doOpenSearchSorts(): Promise<void>
    {
        await this.search_sorts_button.click();
    }
}
