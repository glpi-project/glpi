/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

/* global GLPI */

require('../../../js/modules/Search/ResultsView.js');
require('../../../js/modules/Search/Table.js');

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
