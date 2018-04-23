<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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

namespace tests\units;

/* Test for inc/search.class.php */

class Search extends \GLPITestCase {

   public function dataInfocomOptions() {
      return [
         [1, false],
         [2, false],
         [4, false],
         [40, false],
         [31, false],
         [80, false],
         [25, true],
         [26, true],
         [27, true],
         [28, true],
         [37, true],
         [38, true],
         [50, true],
         [51, true],
         [52, true],
         [53, true],
         [54, true],
         [55, true],
         [56, true],
         [57, true],
         [58, true],
         [59, true],
         [120, true],
         [122, true],
         [123, true],
         [124, true],
         [125, true],
         [142, true],
         [159, true],
         [173, true],
      ];
   }

   /**
    * @dataProvider dataInfocomOptions
    */
   public function testIsInfocomOption($index, $expected) {
      $this->boolean(\Search::isInfocomOption('Computer', $index))->isIdenticalTo($expected);
   }
}
