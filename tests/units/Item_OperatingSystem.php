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

/* Test for inc/item_operatingsystem.class.php */

class Item_OperatingSystem extends DbTestCase {

   public function testGetTypeName() {
      $this->string(\Item_OperatingSystem::getTypeName())->isIdenticalTo('Item operating systems');
      $this->string(\Item_OperatingSystem::getTypeName(0))->isIdenticalTo('Item operating systems');
      $this->string(\Item_OperatingSystem::getTypeName(10))->isIdenticalTo('Item operating systems');
      $this->string(\Item_OperatingSystem::getTypeName(1))->isIdenticalTo('Item operating system');
   }

   /**
    * Create dropdown objects to be used
    *
    * @return array
    */
   private function createDdObjects() {
      $objects = [];
      foreach (['', 'Architecture', 'Version', 'Edition', 'KernelVersion'] as $object) {
         $classname = 'OperatingSystem' . $object;
         $instance = new $classname;
         $this->integer(
            (int)$instance->add([
               'name' => $classname . ' ' . $this->getUniqueInteger()
            ])
         )->isGreaterThan(0);
         $this->boolean($instance->getFromDB($instance->getID()))->isTrue();
         $objects[$object] = $instance;
      }
      return $objects;
   }

   public function testAttachComputer() {
      $computer = getItemByTypeName('Computer', '_test_pc01');

      $objects = $this->createDdObjects();;
      $ios = new \Item_OperatingSystem();
      $input = [
         'itemtype'                          => $computer->getType(),
         'items_id'                          => $computer->getID(),
         'operatingsystems_id'               => $objects['']->getID(),
         'operatingsystemarchitectures_id'   => $objects['Architecture']->getID(),
         'operatingsystemversions_id'        => $objects['Version']->getID(),
         'operatingsystemkernelversions_id'  => $objects['KernelVersion']->getID(),
         'license_id'                        => $this->getUniqueString(),
         'license_number'                    => $this->getUniqueString()
      ];
      $this->integer(
         (int)$ios->add($input)
      )->isGreaterThan(0);
      $this->boolean($ios->getFromDB($ios->getID()))->isTrue();

      $this->string($ios->getTabNameForItem($computer))
         ->isIdenticalTo("Operating systems <sup class='tab_nb'>1</sup>");
      $this->integer(
         (int)\Item_OperatingSystem::countForItem($computer)
      )->isIdenticalTo(1);

      $this->exception(
         function () use ($ios, $input) {
            $ios->add($input);
         }
      )
         ->isInstanceOf('GlpitestSQLError')
         ->message
            ->matches("#Duplicate entry '.+' for key 'unicity'#");

      $this->integer(
         (int)\Item_OperatingSystem::countForItem($computer)
      )->isIdenticalTo(1);

      $objects = $this->createDdObjects();;
      $ios = new \Item_OperatingSystem();
      $input = [
         'itemtype'                          => $computer->getType(),
         'items_id'                          => $computer->getID(),
         'operatingsystems_id'               => $objects['']->getID(),
         'operatingsystemarchitectures_id'   => $objects['Architecture']->getID(),
         'operatingsystemversions_id'        => $objects['Version']->getID(),
         'operatingsystemkernelversions_id'  => $objects['KernelVersion']->getID(),
         'license_id'                        => $this->getUniqueString(),
         'license_number'                    => $this->getUniqueString()
      ];
      $this->integer(
         (int)$ios->add($input)
      )->isGreaterThan(0);
      $this->boolean($ios->getFromDB($ios->getID()))->isTrue();

      $this->string($ios->getTabNameForItem($computer))
         ->isIdenticalTo("Operating systems <sup class='tab_nb'>2</sup>");
      $this->integer(
         (int)\Item_OperatingSystem::countForItem($computer)
      )->isIdenticalTo(2);
   }

   public function testShowForItem() {
      $this->login();
      $computer = getItemByTypeName('Computer', '_test_pc01');

      foreach (['showForItem', 'displayTabContentForItem'] as $method) {
         $this->output(
            function () use ($method, $computer) {
               \Item_OperatingSystem::$method($computer);
            }
         )->contains('operatingsystems_id');
      }

      $objects = $this->createDdObjects();;
      $ios = new \Item_OperatingSystem();
      $input = [
         'itemtype'                          => $computer->getType(),
         'items_id'                          => $computer->getID(),
         'operatingsystems_id'               => $objects['']->getID(),
         'operatingsystemarchitectures_id'   => $objects['Architecture']->getID(),
         'operatingsystemversions_id'        => $objects['Version']->getID(),
         'operatingsystemkernelversions_id'  => $objects['KernelVersion']->getID(),
         'license_id'                        => $this->getUniqueString(),
         'license_number'                    => $this->getUniqueString()
      ];
      $this->integer(
         (int)$ios->add($input)
      )->isGreaterThan(0);
      $this->boolean($ios->getFromDB($ios->getID()))->isTrue();

      foreach (['showForItem', 'displayTabContentForItem'] as $method) {
         $this->output(
            function () use ($method, $computer) {
               \Item_OperatingSystem::$method($computer);
            }
         )->contains('operatingsystems_id');
      }

      $objects = $this->createDdObjects();;
      $ios = new \Item_OperatingSystem();
      $input = [
         'itemtype'                          => $computer->getType(),
         'items_id'                          => $computer->getID(),
         'operatingsystems_id'               => $objects['']->getID(),
         'operatingsystemarchitectures_id'   => $objects['Architecture']->getID(),
         'operatingsystemversions_id'        => $objects['Version']->getID(),
         'operatingsystemkernelversions_id'  => $objects['KernelVersion']->getID(),
         'license_id'                        => $this->getUniqueString(),
         'license_number'                    => $this->getUniqueString()
      ];
      $this->integer(
         (int)$ios->add($input)
      )->isGreaterThan(0);
      $this->boolean($ios->getFromDB($ios->getID()))->isTrue();

      //thera are now 2 OS linked, we will no longer show a form, but a list.
      foreach (['showForItem', 'displayTabContentForItem'] as $method) {
         $this->output(
            function () use ($method, $computer) {
               \Item_OperatingSystem::$method($computer);
            }
         )->notContains('operatingsystems_id');
      }
   }
}
