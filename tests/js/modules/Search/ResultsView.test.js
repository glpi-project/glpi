/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

/* global GLPI */

import '../../../../js/modules/Search/ResultsView.js';
import '../../../../js/modules/Search/Table.js';
import {jest} from '@jest/globals';

describe('Search ResultsView', () => {
    beforeEach(() => {
        jest.clearAllMocks();
    });
    $(document.body).append(`
    <div class="ajax-container search-display-data">
        <form id="massformComputer" data-search-itemtype="Computer">
            <div class="table-responsive-md">
                <table id="search_9439839" class="search-results">
                </table>
            </div>
        </form>
    </div>
`);

    const results_view = new GLPI.Search.ResultsView('massformComputer', GLPI.Search.Table);
    test('Class exists', () => {
        expect(GLPI).toBeDefined();
        expect(GLPI.Search).toBeDefined();
        expect(GLPI.Search.ResultsView).toBeDefined();
    });
    test('getView', () => {
        expect(results_view.getView() instanceof GLPI.Search.Table).toBeTrue();
    });
});
