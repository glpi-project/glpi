<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
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

use \DbTestCase;

/* Test for inc/commondropdown.class.php */

abstract class CommonDropdown extends DbTestCase {

   /**
    * Get object class name
    *
    */
   abstract protected function getObjectClass();

   abstract protected function typenameProvider();

   /**
    * @dataprovider typenameProvider
    */
   public function testGetTypeName($string, $expected) {
      $this->string($string)->isIdenticalTo($expected);
   }

   public function testMaybeTranslated() {
      $this
         ->given($this->newTestedInstance)
            ->then
               ->boolean($this->testedInstance->maybeTranslated())->isFalse();
   }

   public function testGetMenuContent() {
      $class = $this->getObjectClass();
      $this->boolean($class::getMenuContent())->isFalse();
   }

   public function testGetAdditionalFields() {
      $this
         ->given($this->newTestedInstance)
            ->then
               ->array($this->testedInstance->getAdditionalFields())->isIdenticalTo([]);
   }

   abstract protected function getTabs();

   public function testDefineTabs() {
      $this
         ->given($this->newTestedInstance)
            ->then
               ->array($this->testedInstance->defineTabs())
                  ->isIdenticalTo($this->getTabs());
   }

   public function testPre_deleteItem() {
      $this
         ->given($this->newTestedInstance)
            ->then
               ->boolean($this->testedInstance->pre_deleteItem())->isTrue();
   }

   public function testPrepareInputForAdd() {
      $this->newTestedInstance;

      $input = [];
      $this->array($this->testedInstance->prepareInputForAdd($input))->isIdenticalTo($input);

      $input = ['name' => 'Any name', 'comment' => 'Any comment'];
      $this->array($this->testedInstance->prepareInputForAdd($input))->isIdenticalTo($input);

      $loc = getItemByTypeName('Location', '_location01');
      $input['locations_id'] = $loc->getID();
      $this->array($this->testedInstance->prepareInputForAdd($input))
         ->isIdenticalTo($input + ['entities_id' => $loc->fields['entities_id']]);
   }

   /**
    * Create new object in database
    *
    * @return void
    */
   abstract protected function newInstance();

   public function testGetDropdownName() {
      $this->newInstance();
      $instance = $this->testedInstance;
      $ret = \Dropdown::getDropdownName($instance::getTable(), $this->testedInstance->getID());
      $this->string($ret)->isIdenticalTo($this->testedInstance->getName());
   }

   public function testAddUpdate() {
      $this->newTestedInstance();

      $this->integer(
         (int)$this->testedInstance->add([])
      )->isGreaterThan(0);
      $this->boolean(
         $this->testedInstance->getFromDB($this->testedInstance->getID())
      )->isTrue();

      $keys = ['name', 'comment', 'date_mod', 'date_creation'];
      $this->array($this->testedInstance->fields)
         ->hasKeys($keys)
         ->string['date_mod']->isNotEqualTo('')
         ->string['date_creation']->isNotEqualTo('');

      $this->integer(
         (int)$this->testedInstance->add(['name' => 'Tested name'])
      )->isGreaterThan(0);
      $this->boolean(
         $this->testedInstance->getFromDB($this->testedInstance->getID())
      )->isTrue();

      $this->array($this->testedInstance->fields)
         ->hasKeys($keys)
         ->string['name']->isIdenticalTo('Tested name')
         ->string['date_mod']->isNotEqualTo('')
         ->string['date_creation']->isNotEqualTo('');

      $this->integer(
         (int)$this->testedInstance->add([
            'name'      => 'Another name',
            'comment'   => 'A comment on an object'
         ])
      )->isGreaterThan(0);
      $this->boolean(
         $this->testedInstance->getFromDB($this->testedInstance->getID())
      )->isTrue();

      $this->array($this->testedInstance->fields)
         ->hasKeys($keys)
         ->string['name']->isIdenticalTo('Another name')
         ->string['comment']->isIdenticalTo('A comment on an object')
         ->string['date_mod']->isNotEqualTo('')
         ->string['date_creation']->isNotEqualTo('');

      $this->boolean(
         $this->testedInstance->update([
            'id'     => $this->testedInstance->getID(),
            'name'   => 'Changed name'
         ])
      )->isTrue();
      $this->boolean(
         $this->testedInstance->getFromDB($this->testedInstance->getID())
      )->isTrue();
      $this->string($this->testedInstance->fields['name'])->isIdenticalTo('Changed name');

      //cannot update if id is missing
      $this->when(
         function () {
            $this->testedInstance->update(['name' => 'Will not change']);
         }
      )->error()
         ->withType(E_NOTICE)
         ->withMessage('Undefined index: id')
         ->exists();
   }
}
