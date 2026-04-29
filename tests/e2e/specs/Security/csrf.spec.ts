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

import { expect, test } from '../../fixtures/glpi_fixture';
import { Profiles } from '../../utils/Profiles';
import { getWorkerEntityId } from '../../utils/WorkerEntities';

const external_origin = "https://not-my-origin.com";
const same_origin = "https://my-origin.com";

const post_cases = [
    // Test all possible "Sec-Fetch-Site" values
    { sec_fetch_site: "not-a-real-value", origin: null, expected: 403 },
    { sec_fetch_site: "cross-site", origin: null, expected: 403 },
    { sec_fetch_site: "same-site", origin: null, expected: 403 },
    { sec_fetch_site: "same-origin", origin: null, expected: 200 },
    { sec_fetch_site: "none", origin: null, expected: 200 },

    // Demonstrate "Origin" fallback
    { sec_fetch_site: null, origin: external_origin, expected: 403 },
    { sec_fetch_site: null, origin: same_origin, expected: 200 },

    // Demonstrate that "Sec-Fetch-Site" take precedence over "Origin"
    { sec_fetch_site: "same-origin", origin: external_origin, expected: 200 },
    { sec_fetch_site: "cross-site", origin: same_origin, expected: 403 },

    // Both headers missing - not from a browser.
    { sec_fetch_site: null, origin: null, expected: 200 },
];
for (const test_case of post_cases) {
    test(`CSRF scenario: POST with "Sec-Fetch-Site: ${test_case.sec_fetch_site} and "Origin: ${test_case.origin}"`, async ({
        request,
        profile,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const response = await request.post('/front/computer.php', {
            headers: configHeaders(test_case.sec_fetch_site, test_case.origin),
            form: {
                name: "My computer",
                entities_id: getWorkerEntityId(),
                add: "1",
            },
        });
        const status = await response.status();
        expect(status).toBe(test_case.expected);
    });
}

// GET request should never be blocked
const get_cases = [
    // Test all possible "Sec-Fetch-Site" values
    { sec_fetch_site: "cross-site", origin: null, expected: 200 },
    { sec_fetch_site: "same-site", origin: null, expected: 200 },
    { sec_fetch_site: "same-origin", origin: null, expected: 200 },
    { sec_fetch_site: "none", origin: null, expected: 200 },

    // Demonstrate "Origin" fallback
    { sec_fetch_site: "same-origin", origin: external_origin, expected: 200 },
    { sec_fetch_site: "cross-site", origin: same_origin, expected: 200 },

    // Both headers missing - not from a browser.
    { sec_fetch_site: null, origin: null, expected: 200 },
];
for (const test_case of get_cases) {
    test(`CSRF scenario: GET with "Sec-Fetch-Site: ${test_case.sec_fetch_site} and "Origin: ${test_case.origin}"`, async ({
        request,
        profile,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const response = await request.get('/front/computer.php', {
            headers: configHeaders(test_case.sec_fetch_site, test_case.origin),
        });
        const status = await response.status();
        expect(status).toBe(test_case.expected);
    });
}

function configHeaders(
    sec_fetch_site: string | null = null,
    origin: string | null
) {
    const headers: {
        'Sec-Fetch-Site'?: string,
        'Origin'?: string,
        'Host': string,
    } = {
        // Host is always set by the browser.
        Host: "my-origin.com",
    };

    if (sec_fetch_site !== null) {
        headers['Sec-Fetch-Site'] = sec_fetch_site;
    }

    if (origin !== null) {
        headers['Origin'] = origin;
    }

    return headers;
}
