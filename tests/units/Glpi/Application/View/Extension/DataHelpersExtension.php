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

namespace tests\units\Glpi\Application\View\Extension;

class DataHelpersExtension extends \GLPITestCase {

   protected function ungroupProvider() {
      return [
         [
            [
               'User'   => [
                  ['id' => 1, 'name' => 'user1'],
                  ['id' => 2, 'name' => 'user2']
               ],
               'Group'  => [
                  ['id' => 1, 'name' => 'group1'],
                  ['id' => 2, 'name' => 'group2']
               ],
            ],
            'itemtype',
            [
               ['id' => 1, 'name' => 'user1', 'itemtype' => 'User'],
               ['id' => 2, 'name' => 'user2', 'itemtype' => 'User'],
               ['id' => 1, 'name' => 'group1', 'itemtype' => 'Group'],
               ['id' => 2, 'name' => 'group2', 'itemtype' => 'Group'],
            ]
         ]
      ];
   }

   /**
    * @dataProvider ungroupProvider
    */
   public function testUngroup(array $array, string $key_property, array $expected): void {
      $result = (new \Glpi\Application\View\Extension\DataHelpersExtension)->ungroup($array, $key_property);
      $this->array($result)->isEqualTo($expected);
   }

   protected function groupByProvider() {
      return [
         [
            [
               ['id' => 1, 'name' => 'user1', 'itemtype' => 'User'],
               ['id' => 2, 'name' => 'user2', 'itemtype' => 'User'],
               ['id' => 1, 'name' => 'group1', 'itemtype' => 'Group'],
               ['id' => 2, 'name' => 'group2', 'itemtype' => 'Group'],
            ],
            'itemtype',
            [
               'User' => [
                  ['id' => 1, 'name' => 'user1', 'itemtype' => 'User'],
                  ['id' => 2, 'name' => 'user2', 'itemtype' => 'User'],
               ],
               'Group' => [
                  ['id' => 1, 'name' => 'group1', 'itemtype' => 'Group'],
                  ['id' => 2, 'name' => 'group2', 'itemtype' => 'Group'],
               ],
            ]
         ]
      ];
   }

   /**
    * @dataProvider groupByProvider
    */
   public function testGroupBy(array $array, string $property, array $expected): void {
      $result = (new \Glpi\Application\View\Extension\DataHelpersExtension)->groupBy($array, $property);
      $this->array($result)->isEqualTo($expected);
   }
}
