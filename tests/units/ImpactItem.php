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

namespace tests\units;

use DbTestCase;

class ImpactItem extends DbTestCase {

   public function prepareInputForUpdateProvider() {
      return [
         [
            'input'  => ['max_depth' => "glpi"],
            'result' => \Impact::DEFAULT_DEPTH,
         ],
         [
            'input'  => ['max_depth' => 0],
            'result' => \Impact::DEFAULT_DEPTH,
         ],
         [
            'input'  => ['max_depth' => -58],
            'result' => \Impact::DEFAULT_DEPTH,
         ],
         [
            'input'  => ['max_depth' => 9],
            'result' => 9,
         ],
         [
            'input'  => ['max_depth' => 2],
            'result' => 2,
         ],
         [
            'input'  => ['max_depth' => 40],
            'result' => \Impact::NO_DEPTH_LIMIT,
         ],
         [
            'input'  => ['max_depth' => \Impact::NO_DEPTH_LIMIT],
            'result' => \Impact::NO_DEPTH_LIMIT,
         ],
      ];
   }

   /**
    * @dataProvider prepareInputForUpdateProvider
    */
   public function testPrepareInputForUpdate($input, $result) {
      $impact_item = new \ImpactItem();
      $input = $impact_item->prepareInputForUpdate($input);
      $this->array($input)->hasKey('max_depth');
      $this->integer($input['max_depth'])->isEqualTo($result);
   }
}