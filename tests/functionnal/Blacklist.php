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

namespace tests\units;

use DbTestCase;

/* Test for inc/blacklist.class.php */

class Blacklist extends DbTestCase {

   public function testGetDefaults() {
      $defaults = \Blacklist::getDefaults();

      $expecteds = [
         \Blacklist::SERIAL => 40,
         \Blacklist::UUID => 5,
         \Blacklist::MAC => 19,
         \Blacklist::MODEL => 7,
         \Blacklist::MANUFACTURER => 1,
         \Blacklist::IP => 3
      ];
      $this->array(array_keys($defaults))->isIdenticalTo(array_keys($expecteds));

      foreach ($expecteds as $type => $expected) {
         $this->array($defaults[$type])->hasSize($expected);
      }
   }

   protected function processProvider(): array {
      return [
         [
            'input'    => ['name' => 'My name', 'serial' => 'AGH577C'],
            'expected' => null
         ], [
            'input'    => ['name' => 'My name', 'serial' => '123456'],
            'expected' => ['name' => 'My name']
         ], [
            'input'    => ['name' => 'My name', 'mac' => '00:50:56:C0:00:03'],
            'expected' => ['name' => 'My name']
         ]
      ];
   }

   /** @dataProvider processProvider */
   public function testProcess($input, $expected) {
      $blacklist = new \Blacklist();

      if ($expected == null) {
         $expected = (object)$input;
      } else {
         $expected = (object)$expected;
      }
      $input = (object)$input;
      $blacklist->processBlackList($input);
      $this->object($input)->isEqualTo($expected);
   }
}
