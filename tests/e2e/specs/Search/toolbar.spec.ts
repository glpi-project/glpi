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
import { APIRequestContext } from '@playwright/test';
import { expect, test } from '../../fixtures/glpi_fixture';
import { GlpiPage } from '../../pages/GlpiPage';
import { CsrfFetcher } from '../../utils/CsrfFetcher';
import { Profiles } from '../../utils/Profiles';
import { getWorkerUserId } from '../../utils/WorkerEntities';

// The cypress version relied on the presets running in order so the default
// value was restored by the last one. Run serially and reset explicitly
// instead.
// TODO: improve the tests so this serial constraint can be removed.
test.describe.configure({ mode: 'serial' });

const DEFAULT_SHOW_SEARCH_FORM = 0;

const settings_presets = [
    { 'show_search_form': 1 },
    { 'show_search_form': 0 },
];

/**
 * The search form preference is read from the session
 * (`$_SESSION['glpishow_search_form']`), which is only filled at login time or
 * when the user updates its own preferences. The cypress version could set it
 * through the API because it logged in again before each test; a playwright
 * worker reuses a single session, so the preference page must be used.
 */
const doSetShowSearchForm = async (
    request: APIRequestContext,
    csrf: CsrfFetcher,
    value: number,
): Promise<void> => {
    // Sent as an ajax request so the CSRF token is preserved and can be
    // reused, like the other session switchers of `utils/` do.
    const response = await request.post('/front/preference.php', {
        form: {
            'id': getWorkerUserId(),
            'update': 1,
            'show_search_form': value,
        },
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-Glpi-Csrf-Token': await csrf.get(),
        },
    });
    expect(response.ok()).toBe(true);
};

for (const [i, settings] of settings_presets.entries()) {
    test.describe(`Search toolbar with settings preset #${i}`, () => {
        test.beforeEach(async ({ request, profile, csrf }) => {
            await profile.set(Profiles.SuperAdmin);
            await doSetShowSearchForm(
                request,
                csrf,
                settings['show_search_form']
            );
        });

        test.afterEach(async ({ request, csrf }) => {
            // The preference lives in the session shared by all the tests of
            // this worker.
            await doSetShowSearchForm(request, csrf, DEFAULT_SHOW_SEARCH_FORM);
        });

        test(`can toggle the trashbin`, async ({ page }) => {
            const glpi_page = new GlpiPage(page);

            // Go to the a search page that support the "trashbin" feature,
            // should be toggled off by default.
            await page.goto('/front/computer.php');
            await expect(page.getByTestId('search-results')).toBeVisible();
            await expect(page.getByTestId('search-results-trashbin'))
                .not.toBeAttached()
            ;

            try {
                // Go to trashbin
                await glpi_page.getButton("Show the trashbin").click();
                await expect(page.getByTestId('search-results'))
                    .not.toBeAttached()
                ;
                await expect(page.getByTestId('search-results-trashbin'))
                    .toBeVisible()
                ;
            } finally {
                // The toggle is stored in the session shared by all the tests
                // of this worker, and they all expect it to be off.
                await glpi_page.getButton("Show the trashbin").click();
                await expect(page.getByTestId('search-results')).toBeVisible();
            }
        });

        test(`can toggle the categories tree`, async ({ page }) => {
            const glpi_page = new GlpiPage(page);

            // Go to the a search page that support the "browse mode" feature,
            // should be toggled off by default.
            await page.goto('/front/user.php');
            await expect(page.getByTestId('tree-browse')).not.toBeAttached();

            try {
                // Toggle "browse mode"
                await glpi_page.getButton("Toggle browse").click();
                await expect(page.getByTestId('tree-browse')).toBeVisible();
            } finally {
                // The toggle is stored in the session shared by all the tests
                // of this worker, and they all expect it to be off.
                await glpi_page.getButton("Toggle browse").click();
                await expect(page.getByTestId('tree-browse'))
                    .not.toBeAttached()
                ;
            }
        });

        test(`can toggle unpublished items`, async ({ page }) => {
            const glpi_page = new GlpiPage(page);

            // Go to the a search page that support the "unpublished" feature,
            // should be toggled off by default.
            await page.goto('/front/knowbaseitem.php?forcetab=Knowbase$2');
            await expect(page.getByTestId('unpublished-on')).toBeVisible();
            await expect(page.getByTestId('unpublished-off'))
                .not.toBeAttached()
            ;

            try {
                // Show unpublished items
                await glpi_page.getButton("Show unpublished").click();
                await expect(page.getByTestId('unpublished-on'))
                    .not.toBeAttached()
                ;
                await expect(page.getByTestId('unpublished-off'))
                    .toBeVisible()
                ;
            } finally {
                // The toggle is stored in the session shared by all the tests
                // of this worker, and they all expect it to be off.
                await glpi_page.getButton("Show unpublished").click();
                await expect(page.getByTestId('unpublished-on')).toBeVisible();
            }
        });

        test(`can toggle between map and table views`, async ({ page }) => {
            // Go to the a search page that support the "map" feature,
            // should be toggled off by default.
            await page.goto('/front/monitor.php');
            await expect(page.getByTestId('search-format-table'))
                .toBeVisible()
            ;
            await expect(page.getByTestId('search-format-map'))
                .not.toBeAttached()
            ;

            // The radio inputs are visually hidden, their label (the next
            // sibling) is the clickable element.
            /* eslint-disable playwright/no-raw-locators */
            const map_radio_label = page
                .getByRole('radio', { name: "Show as map" })
                .locator('+ *')
            ;
            const table_radio_label = page
                .getByRole('radio', { name: "Show as table" })
                .locator('+ *')
            ;
            /* eslint-enable playwright/no-raw-locators */

            // Toggle map view
            await map_radio_label.click();
            await expect(page.getByTestId('search-format-table'))
                .not.toBeAttached()
            ;
            await expect(page.getByTestId('search-format-map')).toBeVisible();

            // Toggle back to table view
            await table_radio_label.click();
            await expect(page.getByTestId('search-format-table'))
                .toBeVisible()
            ;
            await expect(page.getByTestId('search-format-map'))
                .not.toBeAttached()
            ;
        });
    });
}
