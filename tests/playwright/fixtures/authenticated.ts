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

import { test as baseTest } from '@playwright/test';
import { existsSync } from 'fs';
import { resolve } from 'path';
import { Config } from '../utils/Config';
import { LoginPage } from '../pages/LoginPage';

export * from '@playwright/test';
export const test = baseTest.extend<{}, { workerStorageState: string }>({
    // Use the same storage state for all tests in this worker.
    storageState: ({ workerStorageState }, use) => use(workerStorageState),

    // Authenticate once per worker with a worker-scoped fixture.
    workerStorageState: [async ({ browser }, use) => {
        // Use parallelIndex as a unique identifier for each worker.
        const id = test.info().parallelIndex;
        const file_name = resolve(test.info().project.outputDir, `.auth/${id}.json`);

        if (existsSync(file_name)) {
            // Reuse existing authentication state if any.
            await use(file_name);
            return;
        }

        // Important: make sure we authenticate in a clean environment by unsetting storage state.
        const page = await browser.newPage({ storageState: undefined });
        const login_page = new LoginPage(page, Config.getBaseUrl());

        // Acquire a unique account and entity.
        const account = `playwright_worker_${id}`;

        // Perform authentication steps.
        await login_page.goto();
        await login_page.login(account, account);

        // End of authentication steps.
        await page.context().storageState({ path: file_name });
        await page.close();
        await use(file_name);
    }, { scope: 'worker' }],
});
