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
import { Locator, Page } from '@playwright/test';
import { expect, test } from '../../fixtures/glpi_fixture';
import { DebugModeSwitcher } from '../../utils/DebugModeSwitcher';
import { ProfileSwitcher } from '../../utils/ProfileSwitcher';
import { Profiles } from '../../utils/Profiles';

/* eslint-disable playwright/no-raw-locators */
// The debug toolbar has almost no accessible name, all its selectors are kept
// as they were in the cypress version.

test.describe("Debug Bar", () => {
    const setupDebugBar = async (
        page: Page,
        profile: ProfileSwitcher,
        debug: DebugModeSwitcher,
    ): Promise<Locator> => {
        await profile.set(Profiles.SuperAdmin);
        await debug.enable();
        await page.goto('/front/computer.php');

        const applet = page.locator('#debug-toolbar-applet');
        await expect(applet).toBeAttached();

        return applet;
    };

    test.afterEach(async ({ debug }) => {
        await debug.disable();
    });

    const getExpandedContent = (applet: Locator): Locator => {
        return applet.locator('#debug-toolbar-expanded-content');
    };

    const getWidget = (applet: Locator, id: string): Locator => {
        return applet.locator(
            `.debug-toolbar-widget[data-glpi-debug-widget-id="${id}"]`
        );
    };

    const getDatagridValue = (parent: Locator, title: string): Locator => {
        return parent.locator('.datagrid-title')
            .filter({ hasText: title })
            .first()
            .locator('+ *')
        ;
    };

    const assertEachCellMatches = async (
        cells: Locator,
        pattern: RegExp,
    ): Promise<void> => {
        await expect(cells).not.toHaveCount(0);

        for (const cell of await cells.all()) {
            expect(await cell.textContent()).toMatch(pattern);
        }
    };

    test('Debug bar controls', async ({ page, profile, debug }) => {
        const applet = await setupDebugBar(page, profile, debug);

        const toggle_bar = applet.getByTitle('Toggle debug bar');
        const toggle_content_area = applet.getByTitle(
            'Toggle debug content area'
        );
        const expanded_content = getExpandedContent(applet);
        const content = applet.locator('.debug-toolbar-content').first();

        await expect(toggle_bar).toBeDisabled();
        await expect(expanded_content).toBeHidden();

        await toggle_content_area.click();
        await expect(expanded_content).toBeVisible();

        await applet.getByTitle('Close').click();
        await expect(content).toBeHidden();

        await toggle_bar.click();
        await expect(content).toBeVisible();
        await expect(expanded_content).toBeVisible();

        await toggle_content_area.click();
        await expect(expanded_content).toBeHidden();
    });

    test('Server performance widget', async ({ page, profile, debug }) => {
        const applet = await setupDebugBar(page, profile, debug);

        const widget = getWidget(applet, 'server_performance');
        await expect(widget).toContainText(
            /\d+\s+ms\s+initial,\s+\d+\s+ms\s+total\s+using\s+[\d.]+\s+MiB/
        );
        await widget.click();

        const expanded_content = getExpandedContent(applet);
        await expect(expanded_content).toBeVisible();

        await expect(
            getDatagridValue(expanded_content, 'Initial Execution Time')
        ).toContainText(/\d+\s+ms/);
        await expect(
            getDatagridValue(expanded_content, 'Total Execution Time')
        ).toContainText(/\d+\s+ms/);
        await expect(
            getDatagridValue(expanded_content, 'Memory Usage')
        ).toContainText(/\d+.+\s+MiB\s+\/\s+[\d.]+\s+MiB/);
        await expect(
            getDatagridValue(expanded_content, 'Memory Peak')
        ).toContainText(/\d+.+\s+MiB\s+\/\s+[\d.]+\s+MiB/);
    });

    test('SQL requests', async ({ page, profile, debug }) => {
        const applet = await setupDebugBar(page, profile, debug);

        const widget = getWidget(applet, 'sql');
        await expect(widget).toContainText(/\d+\s+requests/);
        await widget.click();

        const expanded_content = getExpandedContent(applet);
        await expect(expanded_content).toBeVisible();

        const table = expanded_content.locator('#debug-sql-request-table');

        // 1st column should be alphanumeric
        await assertEachCellMatches(
            table.locator('tr td:nth-child(1)'),
            /^[a-z0-9]+$/
        );
        // 2nd column should be numeric
        await assertEachCellMatches(
            table.locator('tr td:nth-child(2)'),
            /^\d+$/
        );
        // 4th column should be a float ms value
        await assertEachCellMatches(
            table.locator('tr td:nth-child(4)'),
            /^\d+\.\d+\sms$/
        );
        // 5th column should be a number
        await assertEachCellMatches(
            table.locator('tr td:nth-child(5)'),
            /^\d+$/
        );
    });

    test('HTTP requests', async ({ page, profile, debug }) => {
        const applet = await setupDebugBar(page, profile, debug);

        const widget = getWidget(applet, 'requests');
        await expect(widget).toContainText(/\d+\s+requests/);
        await widget.click();

        const expanded_content = getExpandedContent(applet);
        const requests_table = expanded_content.locator('#debug-requests-table');
        await expect
            .poll(async () => await requests_table.locator('tr').count())
            .toBeGreaterThanOrEqual(2)
        ;
        await expect(
            requests_table.locator('tbody tr:first-child')
        ).toHaveClass(/table-active/);

        const tabs = expanded_content.locator('.right-panel .nav .nav-link');
        const content_area = expanded_content
            .locator('.request-details-content-area')
        ;

        // Summary Tab
        await expect(tabs.filter({ hasText: 'Summary' }))
            .toHaveClass(/active/)
        ;
        await expect(
            content_area.locator('h1').filter({ hasText: /^Request Summary/ })
        ).toBeVisible();
        for (const pattern of [
            /Initial Execution Time:\s+\d+ ms/,
            /Memory Usage:\s+[\d.]+\s+MiB\s+\/\s+[\d.]+\s+MiB/,
            /Memory Peak:\s+[\d.]+\s+MiB\s+\/\s+[\d.]+\s+MiB/,
            /SQL Requests:\s+\d+/,
            /SQL Duration:\s+[\d.]+ ms/,
        ]) {
            await expect(
                content_area.locator('td').filter({ hasText: pattern }).first()
            ).toBeVisible();
        }

        // Globals Tab
        await tabs.filter({ hasText: 'Globals' }).click();
        for (const global of ['POST', 'GET', 'SESSION', 'SERVER']) {
            await content_area.locator('.nav-item')
                .filter({ hasText: global })
                .click()
            ;
            await expect(
                content_area.locator(
                    `.tab-pane[id^="debug${global.toLowerCase()}"] .monaco-editor-container`
                )
            ).toBeAttached();
        }

        // Profiler
        await tabs.filter({ hasText: 'Profiler' }).click();
        const profiler_rows = content_area
            .locator('tr[data-profiler-section-id]')
        ;
        const profiler_categories = profiler_rows
            .locator('> td[data-prop="category"]')
        ;
        await expect(profiler_categories).not.toHaveCount(0);

        for (const category of await profiler_categories.all()) {
            await expect(category.locator('.category-badge')).toBeAttached();
        }
        await assertEachCellMatches(
            profiler_rows.locator('> td[data-prop="duration"]'),
            /\d+\sms/
        );
        await assertEachCellMatches(
            profiler_rows.locator('> td[data-prop="percent_of_parent"]'),
            /[\d.]%/
        );
        await assertEachCellMatches(
            profiler_rows.locator('> td[data-prop="auto_ended"]'),
            /(Yes|No)/
        );

        // SQL Tab
        await tabs.filter({ hasText: 'SQL' }).click();
        const sql_table = content_area.locator('#debug-sql-request-table');
        await assertEachCellMatches(
            sql_table.locator('tr td:nth-of-type(1)'),
            /^\d+$/
        );
        await assertEachCellMatches(
            sql_table.locator('tr td:nth-of-type(3)'),
            /^\d+\.\d+\sms$/
        );
    });

    test('Client performance', async ({ page, profile, debug }) => {
        const applet = await setupDebugBar(page, profile, debug);

        const widget = getWidget(applet, 'client_performance');
        await widget.click();

        const expanded_content = getExpandedContent(applet);
        await expect(expanded_content).toBeVisible();

        const expectations: [string, RegExp][] = [
            ['Time to first paint', /\d+\s+ms/],
            ['Time to DOM interactive', /\d+\s+ms/],
            ['Time to DOM complete', /\d+\s+ms/],
            ['Total resources', /^\d+$/],
            ['Total resources size', /[\d.]+\s+MiB/],
            ['Used JS Heap', /[\d.]+\s+MiB/],
            ['Total JS Heap', /[\d.]+\s+MiB/],
            ['JS Heap Limit', /[\d.]+\s+MiB/],
        ];
        for (const [title, pattern] of expectations) {
            await expect(getDatagridValue(expanded_content, title))
                .toContainText(pattern)
            ;
        }

        await expect(widget).toContainText(/[\d.]\s+ms/);
    });

    test('Search options', async ({ page, profile, debug }) => {
        const applet = await setupDebugBar(page, profile, debug);

        const widget = getWidget(applet, 'search_options');
        await expect(widget).toBeAttached();
        await widget.click();

        const expanded_content = getExpandedContent(applet);
        await expect(expanded_content).toBeVisible();
        await expect(expanded_content.locator('.search-opts-table'))
            .not.toBeAttached()
        ;

        const search_options_response = () => page.waitForResponse((response) => {
            const url = new URL(response.url());
            return url.pathname === '/ajax/debug.php'
                && url.searchParams.get('action') === 'get_search_options'
            ;
        });

        const itemtype = expanded_content.getByLabel('Itemtype')
            .filter({ visible: true })
        ;
        await expect(itemtype).toBeVisible();

        // Should always be available since it is required for the session, so
        // already autoloaded.
        let response = search_options_response();
        await itemtype.selectOption('Profile');
        expect((await response).status()).toEqual(200);
        await expect(expanded_content.locator('.search-opts-table'))
            .toBeAttached()
        ;

        await expanded_content.getByTitle('Toggle manual input').click();

        const manual_itemtype = expanded_content.getByLabel('Itemtype')
            .filter({ visible: true })
        ;
        response = search_options_response();
        await manual_itemtype.fill('User');
        await manual_itemtype.press('Enter');
        expect((await response).status()).toEqual(200);
    });

    test('Theme switcher', async ({ page, profile, debug }) => {
        const applet = await setupDebugBar(page, profile, debug);

        const widget = getWidget(applet, 'theme_switcher');
        await expect(widget).toBeAttached();
        await widget.click();

        const expanded_content = getExpandedContent(applet);
        await expect(expanded_content).toBeVisible();

        const palette = expanded_content.getByRole('combobox', {
            name: 'Palette',
        });
        const html = page.locator('html');

        try {
            await expect(palette).toBeVisible();
            await palette.selectOption('midnight');
            await expect(html).toHaveAttribute('data-glpi-theme', 'midnight');
            await expect(html).toHaveAttribute('data-glpi-theme-dark', '1');
        } finally {
            // The palette is stored on the user, restore the default one even
            // if the test failed: the session is shared between the tests of a
            // worker.
            await palette.selectOption('auror');
        }
        await expect(html).toHaveAttribute('data-glpi-theme', 'auror');
        await expect(html).toHaveAttribute('data-glpi-theme-dark', '0');
    });
});
