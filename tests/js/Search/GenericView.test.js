/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

require('../../../js/modules/Search/GenericView.js');

describe('Search GenericView', () => {
   beforeEach(() => {
      jest.clearAllMocks();
   });
   $(document.body).append(`
    <div class="ajax-container search-display-data">
        <div id="generic-search-view" class="search-container"></div>
    </div>
`);

   const generic_view = new GLPI.Search.GenericView('generic-search-view');
   test('Class exists', () => {
      expect(GLPI).toBeDefined();
      expect(GLPI.Search).toBeDefined();
      expect(GLPI.Search.GenericView).toBeDefined();
   });
   test('getElement', () => {
      expect(generic_view.getElement().length).toBe(1);
   });
   test('getResultsView', () => {
      generic_view.getElement().closest('.ajax-container').data('js_class', {test: 'Test'});
      expect(generic_view.getResultsView()).toBeObject();
      expect(generic_view.getResultsView()).toHaveProperty('test');
   });
});
