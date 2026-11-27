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

import { test, expect } from '../../fixtures/glpi_fixture';
import { SearchEnginePage } from '../../pages/SearchEnginePage';
import { Profiles } from '../../utils/Profiles';
import AxeBuilder from '@axe-core/playwright';

test('Search engine accessibility', async ({ page, profile }) => {
    await profile.set(Profiles.SuperAdmin);
    const search_engine_page = new SearchEnginePage(page);
    await search_engine_page.goto();
    await expect(search_engine_page.search_page).toBeVisible();

    const page_a11y = await new AxeBuilder({ page })
        .include('[data-testid="search-page"]')
        .analyze()
    ;
    expect(page_a11y.violations).toEqual([]);

    await search_engine_page.doOpenSearchFilters();
    await expect(search_engine_page.search_filters_panel).toBeVisible();

    const filters_a11y = await new AxeBuilder({ page })
        .include('[data-testid="search-filters-panel"]')
        .disableRules(['color-contrast']) // known issue: action button labels have insufficient contrast
        .analyze()
    ;
    expect(filters_a11y.violations).toEqual([]);

    await search_engine_page.doOpenSearchSorts();
    await expect(search_engine_page.search_sorts_panel).toBeVisible();

    const sorts_a11y = await new AxeBuilder({ page })
        .include('[data-testid="search-sorts-panel"]')
        .disableRules(['color-contrast']) // known issue: action button labels have insufficient contrast
        .analyze()
    ;
    expect(sorts_a11y.violations).toEqual([]);
});

test('Criteria dropdown opening upwards is not clipped by the results container', async ({ page, profile }) => {
    await profile.set(Profiles.SuperAdmin);
    await page.setViewportSize({ width: 1280, height: 750 });
    const search_engine_page = new SearchEnginePage(page);
    await search_engine_page.gotoTicketWithNotClosedStatusCriteria();
    await expect(search_engine_page.search_page).toBeVisible();

    // Content displayed above the list leaves no room below the criteria
    await search_engine_page.doOpenSearchFiltersAtViewportBottom();
    await search_engine_page.doOpenFirstCriteriaValueDropdown();

    // The dropdown opens upwards, outside of the scrollable results container, and must not be hidden
    await expect(search_engine_page.upward_select2_results).toBeVisible();
    await expect.poll(() => search_engine_page.isUpwardSelect2ResultsTopVisible()).toBe(true);
});
