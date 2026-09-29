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

import { defineConfig } from '@playwright/test';
import base_config, { DESKTOP_CHROME_USE, ISOLATED_GLOB } from './playwright.config';

/**
 * Playwright configuration file dedicated to the "isolated" tests.
 *
 * Most of our tests run in parallel and share a single database: each worker
 * has its own account and its own entity, which is enough to keep them
 * independent from each other.
 *
 * Some features can't be tested this way because they are global to the whole
 * application (maintenance mode, general configuration, display preferences of
 * the global view, LDAP servers, ...). A test that changes such data would
 * break every other test running at the same time.
 *
 * These tests are written in `*.spec.isolated.ts` files, are ignored by the
 * projects of `playwright.config.ts` and are executed by this configuration
 * file, in a single worker and in a dedicated run.
 *
 * Note: since these tests change global data, they MUST restore the initial
 * values in an `afterEach` hook. A test that fails in the middle would
 * otherwise leave the application in a dirty state for its own retries.
 */
export default defineConfig({
    // Inherit the common options (testDir, baseURL, trace, retries, ...).
    ...base_config,

    // These tests change global data: only one of them may run at a time.
    // See: https://playwright.dev/docs/api/class-testconfig#test-config-workers
    workers: 1,
    fullyParallel: false,

    // Folder for test artifacts.
    // It must be different from the folder used by the main configuration file,
    // as Playwright empties it when a run starts.
    outputDir: 'tests/e2e/output-isolated',

    // Same reporters as the main configuration file, but writing into dedicated
    // folders: the blob and html reporters both empty their output folder when
    // a run starts, which would destroy the report of the main run.
    reporter: process.env.CI ? [
        ['blob', { outputDir: 'blob-report-isolated' }],
        ['dot'],
    ] : [
        ['html', {
            open: 'never',
            outputFolder: 'tests/e2e/results-isolated',
        }],
    ],

    // A single project: the plugins do not have isolated tests for now.
    projects: [
        {
            name: 'isolated',
            testMatch: ISOLATED_GLOB,
            use: DESKTOP_CHROME_USE,
        },
    ],
});
