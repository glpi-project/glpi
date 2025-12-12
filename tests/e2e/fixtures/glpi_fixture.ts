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

import { test as baseTest, Page, request } from '@playwright/test';
import { existsSync } from 'fs';
import { resolve } from 'path';
import { Config } from '../utils/Config';
import { JSDOM } from 'jsdom';
import { CsrfExtractor } from '../utils/CsrfExtractor';
import { Constants } from '../utils/Constants';
import { ProfileSwitcher } from '../utils/ProfileSwitcher';
import { CsrfFetcher } from '../utils/CsrfFetcher';
import { WorkerSessionCache } from '../utils/WorkerSessionCache';

export * from '@playwright/test';
export const test = baseTest.extend<{
    // Test scoped fixtures, these object will be created for each tests.
    anonymousPage: Page,
    profile: ProfileSwitcher,
    csrf: CsrfFetcher,
}, {
    // Worker scoped fixtures, these objects will be created once per thread.
    workerSessionCache: WorkerSessionCache,
    workerStorageState: string,
}>({
    // Use the same storage state for all tests in this worker.
    storageState: ({ workerStorageState }, use) => use(workerStorageState),

    // Authenticate once per worker with a worker-scoped fixture.
    // See: https://playwright.dev/docs/auth#moderate-one-account-per-parallel-worker
    workerStorageState: [async ({}, use) => {
        // Use parallelIndex as a unique identifier for each worker.
        const id = test.info().parallelIndex;
        const file_name = resolve(
            test.info().project.outputDir,
            `.auth/${id}.json`
        );

        if (existsSync(file_name)) {
            // Reuse existing authentication state if any.
            await use(file_name);
            return;
        }

        // Important: make sure we authenticate in a clean environment by
        // unsetting storage state.
        const context = await request.newContext({ storageState: undefined });

        // Render the login page in order to extract the CSRF token and find the
        // login and password fields names
        const response = await context.get(Config.getBaseUrl());
        const body = await response.text();
        const document = new JSDOM(body).window.document;

        // Extract CSRF token
        const csrf_extractor = new CsrfExtractor();
        const token = csrf_extractor.extractToken(body);

        // Extract login field name
        const login_field = document.getElementById('login_name');
        if (login_field === null) {
            throw new Error("Can't find login field");
        }
        const login_field_name = login_field.getAttribute('name');
        if (login_field_name === null) {
            throw new Error("Can't find login field name");
        }

        // Extract password field name
        const password_field = document.getElementById('login_password');
        if (password_field === null) {
            throw new Error("Can't find password field");
        }
        const password_field_name = password_field.getAttribute('name');
        if (password_field_name === null) {
            throw new Error("Can't find password field name");
        }

        // Map id to expected playwright worker login
        const worker_prefix = Constants.E2E_WORKER_PREFIX;
        const worker_index = id + 1;
        const worker_index_string = worker_index > 10
            ? worker_index.toString()
            : `0${worker_index.toString()}` // Pad numbers < 10
        ;
        const worker_login = worker_prefix + worker_index_string;

        // Send login request
        await context.post(`${Config.getBaseUrl()}/front/login.php`, {
            form: {
                [login_field_name]     : `${worker_login}`,
                [password_field_name]  : `${worker_login}`,
                '_glpi_csrf_token': token,
            }
        });

        // Store session
        await context.storageState({ path: file_name });
        await context.dispose();
        await use(file_name);
    }, { scope: 'worker' }],

    // Service used to fetch a CSRF token.
    csrf: [async ({ request, workerSessionCache }, use) => {
        await use(new CsrfFetcher(request, workerSessionCache));
    }, { scope: 'test' }],

    // Service used to switch profiles as needed.
    profile: [async ({ request, csrf, workerSessionCache }, use) => {
        await use(new ProfileSwitcher(request, csrf, workerSessionCache));
    }, { scope: 'test' }],

    // Store the state of the current session.
    // This avoid fetching CSRF token multiple times or trying to set a profile
    // that is already the one being used.
    // Worker scoped so we will get one object per thread (= per session since
    // each of our thread has a unique account).
    workerSessionCache: [async ({}, use) => {
        await use(new WorkerSessionCache());
    }, { scope: 'worker' }],

    // Since the default page will be authenticated, expose another fixture that
    // return a page without an active GLPI session.
    anonymousPage: async ({ browser }, use) => {
        const context = await browser.newContext({
            storageState: {
                cookies: [],
                origins: []
            }
        });
        await use(await context.newPage());
        await context.close();
    },
});
