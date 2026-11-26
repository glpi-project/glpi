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
    public readonly search_criteria_fields: Locator;
    public readonly datetime_time_toggle: Locator;
    public readonly datetime_calendar_button: Locator;
    public readonly first_criteria_value_dropdown: Locator;
    public readonly upward_select2_results: Locator;

    public constructor(page: Page)
    {
        super(page);
        this.search_page              = page.getByTestId('search-page');
        this.search_filters_button    = page.getByTestId('search-filters-button');
        this.search_sorts_button      = page.getByTestId('search-sorts-button');
        this.search_filters_panel     = page.getByTestId('search-filters-panel');
        this.search_sorts_panel       = page.getByTestId('search-sorts-panel');
        // Select2-hidden selects have no accessible name, match on submitted `name`
        // eslint-disable-next-line playwright/no-raw-locators
        this.search_criteria_fields   = this.search_filters_panel.locator('select[name$="[field]"]');
        this.datetime_time_toggle     = page.getByRole('checkbox', { name: 'Specify a time' });
        this.datetime_calendar_button = page.getByRole('button', { name: 'Enter or select a date' });
        // Select2 widgets have no accessible name either, match on their markup
        // eslint-disable-next-line playwright/no-raw-locators
        this.first_criteria_value_dropdown = this.search_filters_panel.locator('select[name="criteria[0][value]"] + .select2');
        // eslint-disable-next-line playwright/no-raw-locators
        this.upward_select2_results   = page.locator('.select2-dropdown--above');
    }

    public async goto(): Promise<void>
    {
        await this.page.goto('/front/computer.php');
    }

    /**
     * Navigate to ticket search with a datetime field criterion preset to "Specify a date".
     * Field 15 = Opening date (datetime). Using searchtype=lessthan to trigger relative_dates rendering.
     * Passing an ISO date as value causes the inner dropdown to pre-select "Specify a date".
     */
    public async gotoTicketWithDatetimeCriteria(date: string = '2023-06-15'): Promise<void>
    {
        await this.page.goto(
            `/front/ticket.php?criteria[0][link]=AND&criteria[0][field]=15`
            + `&criteria[0][searchtype]=lessthan&criteria[0][value]=${date}`
        );
    }

    public async gotoTicketWithNotClosedStatusCriteria(): Promise<void>
    {
        await this.page.goto(
            `/front/ticket.php?criteria[0][link]=AND&criteria[0][field]=12`
            + `&criteria[0][searchtype]=equals&criteria[0][value]=notold`
        );
    }

    /**
     * Open the search filters with content inserted above the search page (like plugins using
     * the "pre_item_list" hook), so that the bottom of the filters panel reaches the bottom of the screen.
     */
    public async doOpenSearchFiltersAtViewportBottom(): Promise<void>
    {
        await this.doOpenSearchFilters();
        const panel_bottom = await this.search_filters_panel.evaluate(async (node) => {
            await Promise.all(node.getAnimations().map((animation) => animation.finished));
            return node.getBoundingClientRect().bottom;
        });
        await this.doOpenSearchFilters(); // close
        await this.search_filters_panel.waitFor({ state: 'hidden' });

        const block_height = (this.page.viewportSize()?.height ?? 0) - 10 - panel_bottom;
        await this.search_page.evaluate((search_page, height) => {
            const block = document.createElement('div');
            block.style.height = `${height}px`;
            search_page.before(block);
        }, block_height);
        await this.doOpenSearchFilters();
    }

    public async doOpenFirstCriteriaValueDropdown(): Promise<void>
    {
        await this.first_criteria_value_dropdown.click();
    }

    /**
     * Whether the top edge of the upward Select2 results is really displayed (not hidden by another element).
     */
    public async isUpwardSelect2ResultsTopVisible(): Promise<boolean>
    {
        return this.upward_select2_results.evaluate((node) => {
            const rect = node.getBoundingClientRect();
            const hit = document.elementFromPoint(rect.left + rect.width / 2, rect.top + 5);
            return hit !== null && node.contains(hit);
        });
    }

    public async doOpenSearchFilters(): Promise<void>
    {
        await this.search_filters_button.click();
    }

    public async doSubmitSearchFilters(): Promise<void>
    {
        await this.doOpenSearchFilters();
        await this.search_filters_panel.getByRole('button', { name: /Search$/ }).click();
        await this.page.waitForURL((url) => url.searchParams.has('criteria[0][field]'));
    }

    public async doOpenSearchSorts(): Promise<void>
    {
        await this.search_sorts_button.click();
    }

    public async doOpenDatetimeCalendar(): Promise<void>
    {
        await this.datetime_calendar_button.click();
    }
}
