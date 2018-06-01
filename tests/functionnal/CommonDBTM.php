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

use \DbTestCase;

/* Test for inc/commondbtm.class.php */

class CommonDBTM extends DbTestCase {


   public function testgetIndexNameOtherThanID() {

      $networkport = new \NetworkPort();
      $networkequipment = new \NetworkEquipment();
      $networkportaggregate = new \NetworkPortAggregate();

      $ne_id = $networkequipment->add([
          'entities_id' => 0,
          'name'        => 'switch'
      ]);
      $this->integer($ne_id)->isGreaterThan(0);

      // Add 5 ports
      $port1 = (int)$networkport->add([
            'name'         => 'if0/1',
            'logicial_number' => 1,
            'items_id' => $ne_id,
            'itemtype' => 'NetworkEquipment',
            'entities_id'  => 0,
         ]);
      $port2 = (int)$networkport->add([
            'name'         => 'if0/2',
            'logicial_number' => 2,
            'items_id' => $ne_id,
            'itemtype' => 'NetworkEquipment',
            'entities_id'  => 0,
         ]);
      $port3 = (int)$networkport->add([
            'name'         => 'if0/3',
            'logicial_number' => 3,
            'items_id' => $ne_id,
            'itemtype' => 'NetworkEquipment',
            'entities_id'  => 0,
         ]);
      $port4 = (int)$networkport->add([
            'name'         => 'if0/4',
            'logicial_number' => 4,
            'items_id' => $ne_id,
            'itemtype' => 'NetworkEquipment',
            'entities_id'  => 0,
         ]);
      $port5 = (int)$networkport->add([
            'name'         => 'if0/5',
            'logicial_number' => 5,
            'items_id' => $ne_id,
            'itemtype' => 'NetworkEquipment',
            'entities_id'  => 0,
         ]);

      $this->integer($port1)->isGreaterThan(0);
      $this->integer($port2)->isGreaterThan(0);
      $this->integer($port3)->isGreaterThan(0);
      $this->integer($port4)->isGreaterThan(0);
      $this->integer($port5)->isGreaterThan(0);

      // add an aggregate port use port 3 and 4
      $aggport = (int)$networkportaggregate->add([
            'networkports_id' => $port5,
            'networkports_id_list' => [$port3, $port4],
         ]);

      $this->integer($aggport)->isGreaterThan(0);
      // Try update to use 2 and 4
      $this->boolean($networkportaggregate->update([
            'networkports_id' => $port5,
            'networkports_id_list' => [$port2, $port4],
      ]))->isTrue();

      // Try update with id not exist, it will return false
      $this->boolean($networkportaggregate->update([
            'networkports_id' => $port3,
            'networkports_id_list' => [$port2, $port4],
      ]))->isFalse();

   }

   public function testGetFromDBByRequest() {
      $instance = new \Computer();
      $instance->getFromDbByRequest([
         'LEFT JOIN' => [
            \Entity::getTable() => [
               'FKEY' => [
                  \Entity::getTable() => 'id',
                  \Computer::getTable() => \Entity::getForeignKeyField()
               ]
            ]
         ],
         'WHERE' => ['AND' => [
            'contact' => 'johndoe'],
            \Entity::getTable() . '.name' => '_test_root_entity',
         ]
      ]);
      // the instance must be populated
      $this->boolean($instance->isNewItem())->isFalse();

      $instance = new \Computer();
      $this->exception(
         function() use ($instance) {
            $instance->getFromDbByRequest([
               'WHERE' => ['contact' => 'johndoe'],
            ]);
         }
      )->isInstanceOf(\RuntimeException::class)
      ->message
      ->contains('getFromDBByRequest expects to get one result, 2 found!');

      // the instance must not be populated
      $this->boolean($instance->isNewItem())->isTrue();
   }

   public function testGetFromResultSet() {
      global $DB;
      $result = $DB->request([
         'FROM'   => \Computer::getTable(),
         'LIMIT'  =>1
      ])->next();

      $this->array($result)->hasKeys(['name', 'uuid']);

      $computer = new \Computer();
      $computer->getFromResultSet($result);
      $this->array($computer->fields)->isIdenticalTo($result);
   }

   public function testGetId() {
      $comp = new \Computer();

      $this->integer($comp->getID())->isIdenticalTo(-1);

      $this->boolean($comp->getFromDBByCrit(['name' => '_test_pc01']))->isTrue();
      $this->integer((int)$comp->getID())->isGreaterThan(0);
   }

   public function testGetEmpty() {
      $comp = new \Computer();

      $this->array($comp->fields)->isEmpty();

      $this->boolean($comp->getEmpty())->isTrue();
      $this->array($comp->fields)
         ->string['entities_id']->isIdenticalTo('');

      $_SESSION["glpiactive_entity"] = 12;
      $this->boolean($comp->getEmpty())->isTrue();
      unset($_SESSION['glpiactive_entity']);
      $this->array($comp->fields)
         ->integer['entities_id']->isIdenticalTo(12);

      /* do not work
      $_SESSION['glpi_table_of']['Computer'] = '';
      $this->boolean($comp->getEmpty())->isFalse();
      unset($_SESSION['glpi_table_of']);*/
   }

   /**
    * Provider for self::testGetTable().
    *
    * @return array
    */
   protected function getTableProvider() {

      return [
         [\DBConnection::class, ''], // "static protected $notable = true;" case
         [\Item_Devices::class, ''], // "static protected $notable = true;" case
         [\Config::class, 'glpi_configs'],
         [\Computer::class, 'glpi_computers'],
         [\User::class, 'glpi_users'],
      ];
   }

   /**
    * Test CommonDBTM::getTable() method.
    *
    * @dataProvider getTableProvider
    * @return void
    */
   public function testGetTable($classname, $tablename) {

      $this->string($classname::getTable())
         ->isEqualTo(\CommonDBTM::getTable($classname))
         ->isEqualTo($tablename);
   }

   /**
    * Test CommonDBTM::getTableField() method.
    *
    * @return void
    */
   public function testGetTableField() {

      // Exception if field argument is empty
      $this->exception(
         function() {
            \Computer::getTableField('');
         }
      )->isInstanceOf(\InvalidArgumentException::class)
         ->hasMessage('Argument $field cannot be empty.');

      // Exception if class has no table
      $this->exception(
         function() {
            \Item_Devices::getTableField('id');
         }
      )->isInstanceOf(\LogicException::class)
         ->hasMessage('Invalid table name.');

      // Base case
      $this->string(\Computer::getTableField('serial'))
         ->isEqualTo(\CommonDBTM::getTableField('serial', \Computer::class))
         ->isEqualTo('glpi_computers.serial');

      // Wildcard case
      $this->string(\Config::getTableField('*'))
         ->isEqualTo(\CommonDBTM::getTableField('*', \Config::class))
         ->isEqualTo('glpi_configs.*');
   }

   public function testupdateOrInsert() {
      global $DB;

      //insert case
      $res = (int)$DB->updateOrInsert(
         \Computer::getTable(), [
            'name'   => 'serial-to-change',
            'serial' => 'serial-one'
         ], [
            'name'   => 'serial-to-change'
         ]
      );
      $this->integer($res)->isGreaterThan(0);

      $check = $DB->request([
         'FROM'   => \Computer::getTable(),
         'WHERE'  => ['name' => 'serial-to-change']
      ])->next();
      $this->array($check)
         ->string['serial']->isIdenticalTo('serial-one');

      //update case
      $res = $DB->updateOrInsert(
         \Computer::getTable(), [
            'name'   => 'serial-to-change',
            'serial' => 'serial-changed'
         ], [
            'name'   => 'serial-to-change'
         ]
      );
      $this->boolean($res)->isTrue();

      $check = $DB->request([
         'FROM'   => \Computer::getTable(),
         'WHERE'  => ['name' => 'serial-to-change']
      ])->next();
      $this->array($check)
         ->string['serial']->isIdenticalTo('serial-changed');
   }

}
