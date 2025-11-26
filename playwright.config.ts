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

import { defineConfig, devices } from '@playwright/test';

/**
 * Playwright configuration file
 *
 * See:
 * - https://playwright.dev/docs/test-configuration.
 * - https://playwright.dev/docs/api/class-testconfig
 */
export default defineConfig({
    // Directory that will be recursively scanned for test files.
    // See: https://playwright.dev/docs/api/class-testconfig#test-config-test-dir
    testDir: './tests/e2e/specs',

    // Run tests in files in parallel
    // See: https://playwright.dev/docs/api/class-testconfig#test-config-fully-parallel
    fullyParallel: true,

    // Fail the build on CI if you accidentally left test.only in the source code.
    // See: https://playwright.dev/docs/api/class-testconfig#test-config-forbid-only
    forbidOnly: !!process.env.CI,

    // Retry on CI only
    // See: https://playwright.dev/docs/api/class-testconfig#test-config-retries
    retries: process.env.CI ? 2 : 0,

    // Reporter to use.
    // See:
    // - https://playwright.dev/docs/test-reporters
    // - https://playwright.dev/docs/api/class-testconfig#test-config-reporter
    reporter: process.env.CI ? [
        // Will generate a HTML report that can be downloaded and viewed locally
        ['html'],
        // Easier to read in the terminal output of the CI itself
        ['dot'],
        // Special reporter that can be viewed from github action results summary
        // See:
        // - https://ctrf.io
        // - https://github.com/ctrf-io/github-test-reporter
        // - https://github.com/ctrf-io/playwright-ctrf-json-reporter
        ['playwright-ctrf-json-reporter'],
    ] : [
        // Generate html report in given folder
        ['html', {
            // Disable the report being opened automatically on failure.
            // You can enable it with the PLAYWRIGHT_HTML_OPEN=on-failure env
            // variable if needed.
            open: 'never',
            outputFolder: 'tests/e2e/results',
        }],
    ],

    // Shared settings for all the projects below.
    // See:  https://playwright.dev/docs/api/class-testoptions.
    use: {
        // Collect trace when retrying the failed test.
        // See: https://playwright.dev/docs/trace-viewer */
        trace: 'on-first-retry',
    },

    // Folder for test artifacts such as screenshots, videos, traces, etc.
    // See: https://playwright.dev/docs/api/class-testconfig#test-config-output-dir
    outputDir: 'tests/e2e/results',

    // Test projets, for now we run all our tests in a single projet that uses
    // chromium.
    // See: https://playwright.dev/docs/test-projects
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
});
