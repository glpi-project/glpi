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

import { Locator, Page, Response, expect } from "@playwright/test";
import { GlpiPage } from "./GlpiPage";

export class ServiceCatalogPage extends GlpiPage
{
    public constructor(page: Page)
    {
        super(page);
    }

    public async goto(): Promise<void>
    {
        await this.page.goto(`/ServiceCatalog`);
    }

    public async doSearchItem(filter: string): Promise<void>
    {
        const searchResponsePromise = this.page.waitForResponse(
            response => response.url().includes('/ServiceCatalog/Items') && response.status() === 200
        );
        await this.getTextbox('Search for forms...').fill(filter);
        await searchResponsePromise;
    }

    public getCategoryRegion(name: string)
    {
        return this.page.getByRole('region', { name: name, exact: true });
    }

    public getItemRegion(name: string)
    {
        return this.page.getByRole('region', { name: name, exact: true });
    }

    public async doGoToItem(name: string): Promise<void>
    {
        await this.getLink(name).click();
    }

    public waitForItemsResponse(): Promise<Response>
    {
        return this.page.waitForResponse(
            response => response.url().includes('/ServiceCatalog/Items') && response.status() === 200
        );
    }

    public async doChangeSortOrder(value: string): Promise<void>
    {
        const sort_dropdown = this.getDropdownByLabel('Sort by');
        const response_promise = this.waitForItemsResponse();
        await this.doSetDropdownValue(sort_dropdown, value, true, false);
        await response_promise;
    }

    public async doGoToPaginationPage(label: string): Promise<void>
    {
        const pagination = this.getPagination();
        const response_promise = this.waitForItemsResponse();
        await pagination.getByRole('link', { name: label }).click();
        await response_promise;
    }

    public getBannerBreadcrumbs(): Locator
    {
        return this.page.getByRole('banner').getByRole('list').first().getByRole('link');
    }

    public getCategoryBreadcrumbNav(): Locator
    {
        return this.page.getByRole('main')
            .getByRole('navigation', { name: 'Service catalog categories' });
    }

    public getPagination(): Locator
    {
        return this.page.getByRole('navigation', { name: 'Service catalog pages' });
    }

    public getFormsRegion(): Locator
    {
        return this.page.getByRole('region', { name: 'Forms', exact: true });
    }

    public async assertBannerBreadcrumbs(): Promise<void>
    {
        const links = this.getBannerBreadcrumbs();
        await expect(links.nth(0)).toContainText('Home');
        await expect(links.nth(1)).toContainText('Assistance');
        await expect(links.nth(2)).toContainText('Service catalog');
    }

    public async assertServiceCatalogMenuActive(): Promise<void>
    {
        await expect(
            this.page.getByRole('complementary')
                .getByRole('link', { name: 'Service catalog', exact: true })
        ).toHaveClass(/active/);
    }

    public async assertPaginationState(options: {
        activePage: string,
        visiblePages: string[],
        hiddenPages: string[],
        disabledButtons: string[],
        enabledButtons: string[],
    }): Promise<void>
    {
        const pagination = this.getPagination();
        await expect(pagination.getByRole('link', { name: options.activePage })).toHaveAttribute('aria-current', 'page');
        for (const p of options.visiblePages) {
            await expect(pagination.getByRole('link', { name: p })).toBeVisible();
        }
        for (const p of options.hiddenPages) {
            await expect(pagination.getByRole('link', { name: p })).toBeHidden();
        }
        for (const btn of options.disabledButtons) {
            await expect(pagination.getByRole('link', { name: btn })).toHaveAttribute('aria-disabled', 'true');
        }
        for (const btn of options.enabledButtons) {
            await expect(pagination.getByRole('link', { name: btn })).not.toHaveAttribute('aria-disabled', 'true');
        }
    }
}
