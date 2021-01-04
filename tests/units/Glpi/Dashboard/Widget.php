<?php
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

namespace tests\units\Glpi\Dashboard;

use DbTestCase;

/* Test for inc/dashboard/widget.class.php */

class Widget extends DbTestCase {

   public function testGetAllTypes() {
      $types = \Glpi\Dashboard\Widget::getAllTypes();

      $this->array($types)->isNotEmpty();
      foreach ($types as $specs) {
         $this->array($specs)
            ->hasKeys(['label', 'function', 'image']);
      }
   }


   protected function palettes() {
      return [
         [
            'bg_color'  => "#FFFFFF",
            'nb_series' => 4,
            'revert'    => true,
            'expected'  => [
               'names'  => ['a', 'b', 'c', 'd'],
               'colors' => [
                  '#a5a5a5',
                  '#7f7f7f',
                  '#595959',
                  '#323232',
               ],
            ]
         ], [
            'bg_color'  => "#FFFFFF",
            'nb_series' => 4,
            'revert'    => false,
            'expected'  => [
               'names'  => ['a', 'b', 'c', 'd'],
               'colors' => [
                  '#595959',
                  '#7f7f7f',
                  '#a5a5a5',
                  '#cccccc',
               ],
            ]
         ], [
            'bg_color'  => "#FFFFFF",
            'nb_series' => 1,
            'revert'    => true,
            'expected'  => [
               'names'  => ['a'],
               'colors' => [
                  '#999999',
               ],
            ]
         ],
      ];
   }

   /**
    * @dataProvider palettes
    */
   public function testGetGradientPalette(
      string $bg_color,
      int $nb_series,
      bool $revert,
      array $expected
   ) {
      $this->array(\Glpi\Dashboard\Widget::getGradientPalette($bg_color, $nb_series, $revert))
           ->isEqualTo($expected);
   }
}