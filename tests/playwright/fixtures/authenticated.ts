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

import { test as baseTest, request } from '@playwright/test';
import { existsSync } from 'fs';
import { resolve } from 'path';
import { Config } from '../utils/Config';
import { JSDOM } from 'jsdom';
import { CsrfManager } from '../utils/CsrfManager';

export * from '@playwright/test';
export const test = baseTest.extend<{}, { workerStorageState: string }>({
    // Use the same storage state for all tests in this worker.
    storageState: ({ workerStorageState }, use) => use(workerStorageState),

    // Authenticate once per worker with a worker-scoped fixture.
    workerStorageState: [async ({}, use) => {
        // Use parallelIndex as a unique identifier for each worker.
        const id = test.info().parallelIndex;
        const file_name = resolve(test.info().project.outputDir, `.auth/${id}.json`);

        if (existsSync(file_name)) {
            // Reuse existing authentication state if any.
            await use(file_name);
            return;
        }

        // Important: make sure we authenticate in a clean environment by unsetting storage state.
        const context = await request.newContext({ storageState: undefined });

        // Render the login page to extract a CSRF token.
        const csrf_manager = new CsrfManager(context);

        // Manually navigate to the login page instead of calling getToken()
        // because we will also need to extract the login and password fields name
        const response = await context.get(Config.getBaseUrl());
        const body = await response.text();
        const token = csrf_manager.extractToken(body);

        // Extract login and password fields name
        const document = new JSDOM(body).window.document;
        const login_field = document
            .getElementById('login_name')
            .getAttribute('name')
        ;
        const password_field = document
            .getElementById('login_password')
            .getAttribute('name')
        ;

        // Send login request
        await context.post(`${Config.getBaseUrl()}/front/login.php`, {
            form: {
                [login_field]     : `playwright_worker_${id}`,
                [password_field]  : `playwright_worker_${id}`,
                '_glpi_csrf_token': token,
            }
        });

        // Store session
        await context.storageState({ path: file_name });
        await context.dispose();
        await use(file_name);
    }, { scope: 'worker' }],
});
