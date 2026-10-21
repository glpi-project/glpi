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

import { GlpiFormServiceCatalogController } from '/js/modules/Forms/ServiceCatalogController.js';
import { jest } from '@jest/globals';

// Title as rendered by the server, using the session language.
const root_title = 'Catálogo de servicios';

const appendFixture = () => {
    $('body').append(`
        <ol data-breadcrumbs-container data-root-title="${root_title}">
            <li class="breadcrumb-item text-truncate active">
                <a
                    href="?category=0"
                    data-children-url-parameters="category=0"
                    data-breadcrumb-item
                >${root_title}</a>
            </li>
        </ol>
        <input type="text" data-glpi-service-catalog-filter-items />
        <select id="sort_strategy" data-glpi-service-catalog-sort-strategy></select>
        <section data-glpi-service-catalog-items></section>
    `);
};

// Items markup as returned by the `/ServiceCatalog/Items` endpoint.
const itemsResponse = (ancestors) => `
    <span
        id="category-ancestors"
        data-ancestors='${JSON.stringify(ancestors)}'
        style="display: none;"
    ></span>
`;

const getBreadcrumbTitles = () => {
    return Array.from(
        document.querySelectorAll('[data-breadcrumbs-container] li a')
    ).map((a) => a.textContent);
};

describe('GlpiFormServiceCatalogController', () => {
    beforeEach(() => {
        $('body').empty();
        window.history.replaceState({}, '', '/ServiceCatalog');

        // Dependencies pulled from `common.js`, not available here.
        window.select2_configs = { sort_strategy: {} };
        window.setupAdaptDropdown = () => $('<select></select>');
    });

    afterEach(() => {
        jest.clearAllMocks();
        delete window.fetch;
    });

    test('Breadcrumb root uses the server side title instead of the JS translation', async () => {
        // JS translations are fetched asynchronously; until they are loaded,
        // `__()` returns the untranslated message id.
        expect(__('Service catalog')).toBe('Service catalog');

        window.fetch = jest.fn().mockResolvedValue({
            ok: true,
            text: async () => itemsResponse([
                { id: 1, name: 'Categoría 1 de Catálogo de Servicios' },
            ]),
        });

        appendFixture();
        // Opening a category directly (new browser tab) makes the controller
        // load the items, and thus rebuild the breadcrumb, on page load.
        window.history.replaceState({}, '', '/ServiceCatalog?category=1');
        new GlpiFormServiceCatalogController({}, 'popularity');

        await new Promise(process.nextTick);

        expect(window.fetch).toHaveBeenCalled();
        expect(getBreadcrumbTitles()).toEqual([
            root_title,
            'Categoría 1 de Catálogo de Servicios',
        ]);
    });
});
