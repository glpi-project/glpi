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

/**
 * @callback FetchMockCallable
 * @param {Request} req
 */

/**
 * @typedef {Object} FetchMock
 * @property {string|RegExp} url URL to match for mocking
 * @property {FetchMockCallable} callable Function to call when URL matches, should return a Response object
 * @property {number} times Number of times to mock this URL, if not provided will mock indefinitely
 * @property {number} callCount Internal counter of how many times this mock has been called
 */

const originalFetch = globalThis.fetch;

/** @type {FetchMock[]} */
const fetchMocks = [];

/**
 * Conditionally mock fetch calls matching the given url
 * @param {string} url URL to match for mocking
 * @param {FetchMockCallable} callable Function to call when URL matches, should return a Response object
 * @param {number} [times] Number of times to mock this URL, if not provided will mock indefinitely
 */
export function mockFetchIf(url, callable, times = undefined) {
    fetchMocks.push({
        url: url,
        callable: callable,
        callCount: 0,
        times: times
    });
}

/**
 * Replaces "fetch" with a mock version.
 */
export function startFetchMock(blockUnmocked = true) {
    globalThis.fetch = async (input, init) => {
        if (typeof input === 'string' && input.startsWith('/')) {
            input = new URL(input, window.location.origin).href;
        }
        const request = new Request(input, init);
        for (const m of fetchMocks) {
            if (m.times === undefined || m.callCount < m.times) {
                const urlMatch = m.url instanceof RegExp ? m.url.test(request.url) : m.url === request.url;
                if (!urlMatch) {
                    continue;
                }
                const res = Promise.resolve(m.callable(request));
                if (res !== null) {
                    m.callCount++;
                    return res;
                }
            }
        }
        if (blockUnmocked) {
            throw new Error(`Unmocked fetch call to ${request.url} with options ${JSON.stringify(init)}`);
        }
        return originalFetch(request);
    };
}

/**
 * Restores the original "fetch" function and clears mocks if specified.
 * @param {boolean} [clearMocks=true] Whether to clear the defined mocks. If just pausing the mock, you may want to keep the existing mocks.
 */
export function stopFetchMock(clearMocks = true) {
    if (clearMocks) {
        fetchMocks.length = 0;
    }
    globalThis.fetch = originalFetch;
}

