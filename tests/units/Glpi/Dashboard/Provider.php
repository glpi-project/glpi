<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
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

namespace tests\units\Glpi\Dashboard;

use DbTestCase;

/* Test for inc/dashboard/provider.class.php */

class Provider extends DbTestCase {

   public function monthYearProvider() {
      return [
         [
            'monthyear' => '2019-01',
            'expected'  => [
               '2019-01-01 00:00:00',
               '2019-02-01 00:00:00'
            ]
         ], [
            'monthyear' => '2019-12',
            'expected'  => [
               '2019-12-01 00:00:00',
               '2020-01-01 00:00:00'
            ]
         ]
      ];
   }


   /**
    * @dataProvider monthYearProvider
    */
   public function testFormatMonthyearDates(string $monthyear, array $expected) {
      $this->array(\Glpi\Dashboard\Provider::formatMonthyearDates($monthyear))
         ->isEqualTo($expected);
   }
}