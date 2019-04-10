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

use DbTestCase;

/* Test for inc/computer_softwareversion.class.php */

class Computer extends DbTestCase {

   private function getNewComputer() {
      $computer = getItemByTypeName('Computer', '_test_pc01');
      $fields   = $computer->fields;
      unset($fields['id']);
      unset($fields['date_creation']);
      unset($fields['date_mod']);
      $fields['name'] = $this->getUniqueString();
      $this->integer((int)$computer->add($fields))->isGreaterThan(0);
      return $computer;
   }

   private function getNewPrinter() {
      $printer  = getItemByTypeName('Printer', '_test_printer_all');
      $pfields  = $printer->fields;
      unset($pfields['id']);
      unset($pfields['date_creation']);
      unset($pfields['date_mod']);
      $pfields['name'] = $this->getUniqueString();
      $this->integer((int)$printer->add($pfields))->isGreaterThan(0);
      return $printer;
   }

   public function testUpdate() {
      global $CFG_GLPI;
      $saveconf = $CFG_GLPI;

      $computer = $this->getNewComputer();
      $printer  = $this->getNewPrinter();

      // Create the link
      $link = new \Computer_Item();
      $in = ['computers_id' => $computer->getField('id'),
             'itemtype'     => $printer->getType(),
             'items_id'     => $printer->getID(),
      ];
      $this->integer((int)$link->add($in))->isGreaterThan(0);

      // Change the computer
      $CFG_GLPI['is_contact_autoupdate']  = 1;
      $CFG_GLPI['is_user_autoupdate']     = 1;
      $CFG_GLPI['is_group_autoupdate']    = 1;
      $CFG_GLPI['state_autoupdate_mode']  = -1;
      $CFG_GLPI['is_location_autoupdate'] = 1;
      $in = ['id'           => $computer->getField('id'),
             'contact'      => $this->getUniqueString(),
             'contact_num'  => $this->getUniqueString(),
             'users_id'     => $this->getUniqueInteger(),
             'groups_id'    => $this->getUniqueInteger(),
             'states_id'    => $this->getUniqueInteger(),
             'locations_id' => $this->getUniqueInteger(),
      ];
      $this->boolean($computer->update($in))->isTrue();
      $this->boolean($computer->getFromDB($computer->getID()))->isTrue();
      $this->boolean($printer->getFromDB($printer->getID()))->isTrue();
      unset($in['id']);
      foreach ($in as $k => $v) {
         // Check the computer new values
         $this->variable($computer->getField($k))->isEqualTo($v);
         // Check the printer and test propagation occurs
         $this->variable($printer->getField($k))->isEqualTo($v);
      }

      //reset values
      $in = ['id'           => $computer->getField('id'),
             'contact'      => '',
             'contact_num'  => '',
             'users_id'     => 0,
             'groups_id'    => 0,
             'states_id'    => 0,
             'locations_id' => 0,
      ];
      $this->boolean($computer->update($in))->isTrue();
      $this->boolean($computer->getFromDB($computer->getID()))->isTrue();
      $this->boolean($printer->getFromDB($printer->getID()))->isTrue();
      unset($in['id']);
      foreach ($in as $k => $v) {
         // Check the computer new values
         $this->variable($computer->getField($k))->isEqualTo($v);
         // Check the printer and test propagation occurs
         $this->variable($printer->getField($k))->isEqualTo($v);
      }

      // Change the computer again
      $CFG_GLPI['is_contact_autoupdate']  = 0;
      $CFG_GLPI['is_user_autoupdate']     = 0;
      $CFG_GLPI['is_group_autoupdate']    = 0;
      $CFG_GLPI['state_autoupdate_mode']  = 0;
      $CFG_GLPI['is_location_autoupdate'] = 0;
      $in2 = ['id'          => $computer->getField('id'),
             'contact'      => $this->getUniqueString(),
             'contact_num'  => $this->getUniqueString(),
             'users_id'     => $this->getUniqueInteger(),
             'groups_id'    => $this->getUniqueInteger(),
             'states_id'    => $this->getUniqueInteger(),
             'locations_id' => $this->getUniqueInteger(),
      ];
      $this->boolean($computer->update($in2))->isTrue();
      $this->boolean($computer->getFromDB($computer->getID()))->isTrue();
      $this->boolean($printer->getFromDB($printer->getID()))->isTrue();
      unset($in2['id']);
      foreach ($in2 as $k => $v) {
         // Check the computer new values
         $this->variable($computer->getField($k))->isEqualTo($v);
         // Check the printer and test propagation DOES NOT occurs
         $this->variable($printer->getField($k))->isEqualTo($in[$k]);
      }

      // Restore configuration
      $computer = $this->getNewComputer();
      $CFG_GLPI = $saveconf;

      //update devices
      $cpu = new \DeviceProcessor();
      $cpuid = $cpu->add(
         [
            'designation'  => 'Intel(R) Core(TM) i5-4210U CPU @ 1.70GHz',
            'frequence'    => '1700'
         ]
      );

      $this->integer((int)$cpuid)->isGreaterThan(0);

      $link = new \Item_DeviceProcessor();
      $linkid = $link->add(
         [
            'items_id'              => $computer->getID(),
            'itemtype'              => \Computer::getType(),
            'deviceprocessors_id'   => $cpuid,
            'locations_id'          => $computer->getField('locations_id'),
            'states_id'             => $computer->getField('status_id'),
         ]
      );

      $this->integer((int)$linkid)->isGreaterThan(0);

      // Change the computer
      $CFG_GLPI['state_autoupdate_mode']  = -1;
      $CFG_GLPI['is_location_autoupdate'] = 1;
      $in = ['id'           => $computer->getField('id'),
             'states_id'    => $this->getUniqueInteger(),
             'locations_id' => $this->getUniqueInteger(),
      ];
      $this->boolean($computer->update($in))->isTrue();
      $this->boolean($computer->getFromDB($computer->getID()))->isTrue();
      $this->boolean($link->getFromDB($link->getID()))->isTrue();
      unset($in['id']);
      foreach ($in as $k => $v) {
         // Check the computer new values
         $this->variable($computer->getField($k))->isEqualTo($v);
         // Check the printer and test propagation occurs
         $this->variable($link->getField($k))->isEqualTo($v);
      }

      //reset
      $in = ['id'           => $computer->getField('id'),
             'states_id'    => 0,
             'locations_id' => 0,
      ];
      $this->boolean($computer->update($in))->isTrue();
      $this->boolean($computer->getFromDB($computer->getID()))->isTrue();
      $this->boolean($link->getFromDB($link->getID()))->isTrue();
      unset($in['id']);
      foreach ($in as $k => $v) {
         // Check the computer new values
         $this->variable($computer->getField($k))->isEqualTo($v);
         // Check the printer and test propagation occurs
         $this->variable($link->getField($k))->isEqualTo($v);
      }

      // Change the computer again
      $CFG_GLPI['state_autoupdate_mode']  = 0;
      $CFG_GLPI['is_location_autoupdate'] = 0;
      $in2 = ['id'          => $computer->getField('id'),
             'states_id'    => $this->getUniqueInteger(),
             'locations_id' => $this->getUniqueInteger(),
      ];
      $this->boolean($computer->update($in2))->isTrue();
      $this->boolean($computer->getFromDB($computer->getID()))->isTrue();
      $this->boolean($link->getFromDB($link->getID()))->isTrue();
      unset($in2['id']);
      foreach ($in2 as $k => $v) {
         // Check the computer new values
         $this->variable($computer->getField($k))->isEqualTo($v);
         // Check the printer and test propagation DOES NOT occurs
         $this->variable($link->getField($k))->isEqualTo($in[$k]);
      }

      // Restore configuration
      $CFG_GLPI = $saveconf;
   }

   /**
    * Checks that newly created links inherits locations, status, and so on
    *
    * @return void
    */
   public function testCreateLinks() {
      global $CFG_GLPI;

      $computer = $this->getNewComputer();
      $saveconf = $CFG_GLPI;

      $CFG_GLPI['is_contact_autoupdate']  = 1;
      $CFG_GLPI['is_user_autoupdate']     = 1;
      $CFG_GLPI['is_group_autoupdate']    = 1;
      $CFG_GLPI['state_autoupdate_mode']  = -1;
      $CFG_GLPI['is_location_autoupdate'] = 1;

      // Change the computer
      $in = ['id'           => $computer->getField('id'),
             'contact'      => $this->getUniqueString(),
             'contact_num'  => $this->getUniqueString(),
             'users_id'     => $this->getUniqueInteger(),
             'groups_id'    => $this->getUniqueInteger(),
             'states_id'    => $this->getUniqueInteger(),
             'locations_id' => $this->getUniqueInteger(),
      ];
      $this->boolean($computer->update($in))->isTrue();
      $this->boolean($computer->getFromDB($computer->getID()))->isTrue();

      $printer = new \Printer();
      $pid = $printer->add(
         [
            'name'         => 'A test printer',
            'entities_id'  => $computer->getField('entities_id')
         ]
      );

      $this->integer((int)$pid)->isGreaterThan(0);

      // Create the link
      $link = new \Computer_Item();
      $in2 = ['computers_id' => $computer->getField('id'),
             'itemtype'     => $printer->getType(),
             'items_id'     => $printer->getID(),
      ];
      $this->integer((int)$link->add($in2))->isGreaterThan(0);

      $this->boolean($printer->getFromDB($printer->getID()))->isTrue();
      unset($in['id']);
      foreach ($in as $k => $v) {
         // Check the computer new values
         $this->variable($computer->getField($k))->isEqualTo($v);
         // Check the printer and test propagation occurs
         $this->variable($printer->getField($k))->isEqualTo($v);
      }

      //create devices
      $cpu = new \DeviceProcessor();
      $cpuid = $cpu->add(
         [
            'designation'  => 'Intel(R) Core(TM) i5-4210U CPU @ 1.70GHz',
            'frequence'    => '1700'
         ]
      );

      $this->integer((int)$cpuid)->isGreaterThan(0);

      $link = new \Item_DeviceProcessor();
      $linkid = $link->add(
         [
            'items_id'              => $computer->getID(),
            'itemtype'              => \Computer::getType(),
            'deviceprocessors_id'   => $cpuid
         ]
      );

      $this->integer((int)$linkid)->isGreaterThan(0);

      $in3 = ['states_id'    => $in['states_id'],
              'locations_id' => $in['locations_id'],
      ];

      $this->boolean($link->getFromDB($link->getID()))->isTrue();
      foreach ($in3 as $k => $v) {
         // Check the computer new values
         $this->variable($computer->getField($k))->isEqualTo($v);
         // Check the printer and test propagation occurs
         $this->variable($link->getField($k))->isEqualTo($v);
      }

      // Restore configuration
      $CFG_GLPI = $saveconf;
   }

   public function testGetFromIter() {
      global $DB;

      $iter = $DB->request(['SELECT' => 'id',
                            'FROM'   => 'glpi_computers']);
      $prev = false;
      foreach (\Computer::getFromIter($iter) as $comp) {
         $this->object($comp)->isInstanceOf('Computer');
         $this->array($comp->fields)
            ->hasKey('name')
            ->string['name']->isNotEqualTo($prev);
         $prev = $comp->fields['name'];
      }
      $this->boolean((bool)$prev)->isTrue(); // we are retrieve something
   }

   public function testGetFromDbByCrit() {
      global $DB;

      $comp = new \Computer();
      $this->boolean($comp->getFromDBByCrit(['name' => '_test_pc01']))->isTrue();
      $this->string($comp->getField('name'))->isIdenticalTo('_test_pc01');

      $this->exception(
         function () use ($comp) {
            $this->boolean($comp->getFromDBByCrit(['name' => ['LIKE', '_test%']]))->isFalse();
         }
      )->message->contains('getFromDBByCrit expects to get one result, 8 found!');
   }
}
