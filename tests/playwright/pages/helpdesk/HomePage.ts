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

import { type Locator, type Page } from '@playwright/test';
import { GlpiPage } from './../GlpiPage';

/**
 * POM for the helpdesk home page.
 * See https://playwright.dev/docs/pom.
 */
export class HomePage extends GlpiPage
{
    private readonly searchBar: Locator;
    private readonly searchResults: Locator;
    private readonly tilesArea: Locator;
    private readonly tiles: Locator;
    private readonly ticketListColumnHeaders: Locator;

    public constructor(page: Page) {
        super(page);

        // Define the fixed locators.
        this.searchBar = page.getByPlaceholder(
            "Search for knowledge base entries or forms"
        );
        this.searchResults = page.getByRole('region', {'name': "Search results"});
        this.tilesArea = page.getByRole('region', {'name': "Quick Access"});
        this.tiles = this.tilesArea.getByRole('link');
        this.ticketListColumnHeaders = this.page.getByRole('columnheader');
    }

    public async goto() {
        await this.page.goto('/Helpdesk');
    }

    public async search(query: string) {
        await this.searchBar.fill(query);
    }

    public async goToTab(tab_name: string) {
        await this.page.getByRole('tab', {'name': tab_name}).click();
    }

    public getTiles() {
        return this.tiles;
    }

    public getTicketListColumnHeaders() {
        return this.ticketListColumnHeaders;
    }

    public getSearchResult(result: string) {
        return this.searchResults.getByRole('link', {'name': result});
    }

    public getLinkToTicket(title: string) {
        return this.page.getByRole('link', {'name': title});
    }

    public getLinkToReminder(title: string) {
        return this.page.getByRole('link', {'name': title});
    }

    public getTicketListColumnHeader(title: string) {
        return this.page.getByRole('columnheader', {'name': title});
    }
}
