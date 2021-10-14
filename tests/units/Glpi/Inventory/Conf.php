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

namespace tests\units\Glpi\Inventory;

/**
 * Test class for src/Glpi/Inventory/conf.class.php
 */
class Conf extends \GLPITestCase {

   public function testKnownInventoryExtensions() {
      $expected = [
         'json',
         'xml',
         'ocs'
      ];

      $this
         ->if($this->newTestedInstance)
         ->then
            ->array($this->testedInstance->knownInventoryExtensions())
            ->isIdenticalTo($expected);
   }

   protected function inventoryfilesProvider(): array {
      return [
         [
            'file'      => 'computer.json',
            'expected'  => true
         ], [
            'file'      => 'anything.xml',
            'expected'  => true
         ], [
            'file'      => 'another.ocs',
            'expected'  => true
         ], [
            'file'      => 'computer.xls',
            'expected'  => false
         ]
      ];
   }

   /**
    * @dataProvider inventoryfilesProvider
    */
   public function testIsInventoryFile(string $file, bool $expected) {
      $this
         ->if($this->newTestedInstance)
         ->then
            ->boolean($this->testedInstance->isInventoryFile($file))
            ->isIdenticalTo($expected);
   }


   protected function confProvider() :array {
      $provider = [];
      $defaults = \Glpi\Inventory\Conf::$defaults;
      foreach ($defaults as $key => $value) {
         $provider[] = [
            'key'    => $key,
            'value'  => $value
         ];
      }
      return $provider;
   }

   /**
    * @dataProvider confProvider
    */
   public function testGetter($key, $value) {
      $this
         ->if($this->newTestedInstance)
         ->then
            ->variable($this->testedInstance->$key)
            ->isEqualTo($value);
   }

   public function testErrorGetter() {
      $this
         ->if($this->newTestedInstance)
         ->then
            ->exception(
               function () {
                  $this->variable($this->testedInstance->doesNotExists)->isEqualTo(null);
               }
            )->message->contains('Property doesNotExists does not exists!');
   }
}
