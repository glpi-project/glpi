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

use Glpi\Event\ItemEvent;
use DbTestCase;

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
      $res = $DB->updateOrInsert(
         \Computer::getTable(), [
            'name'   => 'serial-to-change',
            'serial' => 'serial-one'
         ], [
            'name'   => 'serial-to-change'
         ]
      );
      $this->object($res)->isInstanceOf('PDOStatement');

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
      $this->object($res)->isInstanceOf('PDOStatement');

      $check = $DB->request([
         'FROM'   => \Computer::getTable(),
         'WHERE'  => ['name' => 'serial-to-change']
      ])->next();
      $this->array($check)
         ->string['serial']->isIdenticalTo('serial-changed');

      $this->object(
         $DB->insert(
            \Computer::getTable(),
            ['name' => 'serial-to-change']
         )
      )->isInstanceOf('PDOStatement');

      //multiple update case
      $this->exception(
         function () use ($DB) {
            $res = $DB->updateOrInsert(
               \Computer::getTable(), [
                  'name'   => 'serial-to-change',
                  'serial' => 'serial-changed'
               ], [
                  'name'   => 'serial-to-change'
               ]
            );
         }
      )->message->contains('Update would change too many rows!');

      //allow multiples
      $res = $DB->updateOrInsert(
         \Computer::getTable(), [
            'name'   => 'serial-to-change',
            'serial' => 'serial-changed'
         ], [
            'name'   => 'serial-to-change'
         ],
         false
      );
      $this->object($res)->isInstanceOf('PDOStatement');
   }

   public function testupdateOrInsertMerged() {
      global $DB;

      //insert case
      $res = $DB->updateOrInsert(
         \Computer::getTable(), [
            'serial' => 'serial-one'
         ], [
            'name'   => 'serial-to-change'
         ]
      );
      $this->object($res)->isInstanceOf('PDOStatement');

      $check = $DB->request([
         'FROM'   => \Computer::getTable(),
         'WHERE'  => ['name' => 'serial-to-change']
      ])->next();
      $this->array($check)
         ->string['serial']->isIdenticalTo('serial-one');

      //update case
      $res = $DB->updateOrInsert(
         \Computer::getTable(), [
            'serial' => 'serial-changed'
         ], [
            'name'   => 'serial-to-change'
         ]
      );
      $this->object($res)->isInstanceOf('PDOStatement');

      $check = $DB->request([
         'FROM'   => \Computer::getTable(),
         'WHERE'  => ['name' => 'serial-to-change']
      ])->next();
      $this->array($check)
         ->string['serial']->isIdenticalTo('serial-changed');

      $this->object(
         $DB->insert(
            \Computer::getTable(),
            ['name' => 'serial-to-change']
         )
      )->isInstanceOf('PDOStatement');

      //multiple update case
      $this->exception(
         function () use ($DB) {
            $res = $DB->updateOrInsert(
               \Computer::getTable(), [
                  'serial' => 'serial-changed'
               ], [
                  'name'   => 'serial-to-change'
               ]
            );
         }
      )->message->contains('Update would change too many rows!');

      //allow multiples
      $res = $DB->updateOrInsert(
         \Computer::getTable(), [
            'serial' => 'serial-changed'
         ], [
            'name'   => 'serial-to-change'
         ],
         false
      );
      $this->object($res)->isInstanceOf('PDOStatement');
   }
   /**
    * Check right on Recursive object
    *
    * @return void
    */
   public function testRecursiveObjectChecks() {
      $this->login();

      $ent0 = getItemByTypeName('Entity', '_test_root_entity', true);
      $ent1 = getItemByTypeName('Entity', '_test_child_1', true);
      $ent2 = getItemByTypeName('Entity', '_test_child_2', true);

      $printer = new \Printer();

      $id[0] = (int)$printer->add([
         'name'         => "Printer 1",
         'entities_id'  => $ent0,
         'is_recursive' => 0
      ]);
      $this->integer($id[0])->isGreaterThan(0);

      $id[1] = (int)$printer->add([
         'name'         => "Printer 2",
         'entities_id'  => $ent0,
         'is_recursive' => 1
      ]);
      $this->integer($id[1])->isGreaterThan(0);

      $id[2] = (int)$printer->add([
         'name'         => "Printer 3",
         'entities_id'  => $ent1,
         'is_recursive' => 1
      ]);
      $this->integer($id[2])->isGreaterThan(0);

      $id[3] = (int)$printer->add([
         'name'         => "Printer 4",
         'entities_id'  => $ent2
      ]);
      $this->integer($id[3])->isGreaterThan(0);

      // Super admin
      $this->login('glpi', 'glpi');
      $this->variable($_SESSION['glpiactiveprofile']['id'])->isEqualTo(4);
      $this->variable($_SESSION['glpiactiveprofile']['printer'])->isEqualTo(255);

      // See all
      $this->boolean(\Session::changeActiveEntities('all'))->isTrue();

      $this->boolean($printer->can($id[0], READ))->isTrue("Fail can read Printer 1");
      $this->boolean($printer->can($id[1], READ))->isTrue("Fail can read Printer 2");
      $this->boolean($printer->can($id[2], READ))->isTrue("Fail can read Printer 3");
      $this->boolean($printer->can($id[3], READ))->isTrue("Fail can read Printer 4");

      $this->boolean($printer->canEdit($id[0]))->isTrue("Fail can write Printer 1");
      $this->boolean($printer->canEdit($id[1]))->isTrue("Fail can write Printer 2");
      $this->boolean($printer->canEdit($id[2]))->isTrue("Fail can write Printer 3");
      $this->boolean($printer->canEdit($id[3]))->isTrue("Fail can write Printer 4");

      // See only in main entity
      $this->boolean(\Session::changeActiveEntities($ent0))->isTrue();

      $this->boolean($printer->can($id[0], READ))->isTrue("Fail can read Printer 1");
      $this->boolean($printer->can($id[1], READ))->isTrue("Fail can read Printer 2");
      $this->boolean($printer->can($id[2], READ))->isFalse("Fail can't read Printer 3");
      $this->boolean($printer->can($id[3], READ))->isFalse("Fail can't read Printer 1");

      $this->boolean($printer->canEdit($id[0]))->isTrue("Fail can write Printer 1");
      $this->boolean($printer->canEdit($id[1]))->isTrue("Fail can write Printer 2");
      $this->boolean($printer->canEdit($id[2]))->isFalse("Fail can't write Printer 1");
      $this->boolean($printer->canEdit($id[3]))->isFalse("Fail can't write Printer 1");

      // See only in child entity 1 + parent if recursive
      $this->boolean(\Session::changeActiveEntities($ent1))->isTrue();

      $this->boolean($printer->can($id[0], READ))->isFalse("Fail can't read Printer 1");
      $this->boolean($printer->can($id[1], READ))->isTrue("Fail can read Printer 2");
      $this->boolean($printer->can($id[2], READ))->isTrue("Fail can read Printer 3");
      $this->boolean($printer->can($id[3], READ))->isFalse("Fail can't read Printer 4");

      $this->boolean($printer->canEdit($id[0]))->isFalse("Fail can't write Printer 1");
      $this->boolean($printer->canEdit($id[1]))->isFalse("Fail can't write Printer 2");
      $this->boolean($printer->canEdit($id[2]))->isTrue("Fail can write Printer 2");
      $this->boolean($printer->canEdit($id[3]))->isFalse("Fail can't write Printer 2");

      // See only in child entity 2 + parent if recursive
      $this->boolean(\Session::changeActiveEntities($ent2))->isTrue();

      $this->boolean($printer->can($id[0], READ))->isFalse("Fail can't read Printer 1");
      $this->boolean($printer->can($id[1], READ))->isTrue("Fail can read Printer 2");
      $this->boolean($printer->can($id[2], READ))->isFalse("Fail can't read Printer 3");
      $this->boolean($printer->can($id[3], READ))->isTrue("Fail can read Printer 4");

      $this->boolean($printer->canEdit($id[0]))->isFalse("Fail can't write Printer 1");
      $this->boolean($printer->canEdit($id[1]))->isFalse("Fail can't write Printer 2");
      $this->boolean($printer->canEdit($id[2]))->isFalse("Fail can't write Printer 3");
      $this->boolean($printer->canEdit($id[3]))->isTrue("Fail can write Printer 4");
   }

   /**
    * Check right on CommonDBRelation object
    */
   public function testContact_Supplier() {

      $ent0 = getItemByTypeName('Entity', '_test_root_entity', true);
      $ent1 = getItemByTypeName('Entity', '_test_child_1', true);
      $ent2 = getItemByTypeName('Entity', '_test_child_2', true);

      // Super admin
      $this->login('glpi', 'glpi');
      $this->variable($_SESSION['glpiactiveprofile']['id'])->isEqualTo(4);
      $this->variable($_SESSION['glpiactiveprofile']['contact_enterprise'])->isEqualTo(255);

      // See all
      $this->boolean(\Session::changeActiveEntities('all'))->isTrue();

      // Create some contacts
      $contact = new \Contact();

      $idc[0] = (int)$contact->add([
         'name'         => "Contact 1",
         'entities_id'  => $ent0,
         'is_recursive' => 0
      ]);
      $this->integer($idc[0])->isGreaterThan(0);

      $idc[1] = (int)$contact->add([
         'name'         => "Contact 2",
         'entities_id'  => $ent0,
         'is_recursive' => 1
      ]);
      $this->integer($idc[1])->isGreaterThan(0);

      $idc[2] = (int)$contact->add([
         'name'         => "Contact 3",
         'entities_id'  => $ent1,
         'is_recursive' => 1
      ]);
      $this->integer($idc[2])->isGreaterThan(0);

      $idc[3] = (int)$contact->add([
         'name'         => "Contact 4",
         'entities_id'  => $ent2]);
      $this->integer($idc[3])->isGreaterThan(0);;

      // Create some suppliers
      $supplier = new \Supplier();

      $ids[0] = (int)$supplier->add([
         'name'         => "Supplier 1",
         'entities_id'  => $ent0,
         'is_recursive' => 0
      ]);
      $this->integer($ids[0])->isGreaterThan(0);

      $ids[1] = (int)$supplier->add([
         'name'         => "Supplier 2",
         'entities_id'  => $ent0,
         'is_recursive' => 1
      ]);
      $this->integer($ids[1])->isGreaterThan(0);

      $ids[2] = (int)$supplier->add([
         'name'         => "Supplier 3",
         'entities_id'  => $ent1
      ]);
      $this->integer($ids[2])->isGreaterThan(0);

      $ids[3] = (int)$supplier->add([
         'name'         => "Supplier 4",
         'entities_id'  => $ent2
      ]);
      $this->integer($ids[3])->isGreaterThan(0);

      // Relation
      $rel = new \Contact_Supplier();
      $input = [
         'contacts_id' =>  $idc[0], // root
         'suppliers_id' => $ids[0]  //root
      ];
      $this->boolean($rel->can(-1, CREATE, $input))->isTrue();

      $idr[0] = (int)$rel->add($input);
      $this->integer($idr[0])->isGreaterThan(0);
      $this->boolean($rel->can($idr[0], READ))->isTrue();
      $this->boolean($rel->canEdit($idr[0]))->isTrue();

      $input = [
         'contacts_id' =>  $idc[0], // root
         'suppliers_id' => $ids[1]  // root + rec
      ];
      $this->boolean($rel->can(-1, CREATE, $input))->isTrue();
      $idr[1] = (int)$rel->add($input);
      $this->integer($idr[1])->isGreaterThan(0);
      $this->boolean($rel->can($idr[1], READ))->isTrue();
      $this->boolean($rel->canEdit($idr[1]))->isTrue();

      $input = [
         'contacts_id' =>  $idc[0], // root
         'suppliers_id' => $ids[2]  // child 1
      ];
      $this->boolean($rel->can(-1, CREATE, $input))->isFalse();

      $input = [
         'contacts_id' =>  $idc[0], // root
         'suppliers_id' => $ids[3]  // child 2
      ];
      $this->boolean($rel->can(-1, CREATE, $input))->isFalse();

      $input = [
         'contacts_id' =>  $idc[1], // root + rec
         'suppliers_id' => $ids[0]  // root
      ];
      $this->boolean($rel->can(-1, CREATE, $input))->isTrue();
      $idr[2] = (int)$rel->add($input);
      $this->integer($idr[2])->isGreaterThan(0);
      $this->boolean($rel->can($idr[2], READ))->isTrue();
      $this->boolean($rel->canEdit($idr[2]))->isTrue();

      $input = [
         'contacts_id' =>  $idc[1], // root + rec
         'suppliers_id' => $ids[1]  // root + rec
      ];
      $this->boolean($rel->can(-1, CREATE, $input))->isTrue();
      $idr[3] = (int)$rel->add($input);
      $this->integer($idr[3])->isGreaterThan(0);
      $this->boolean($rel->can($idr[3], READ))->isTrue();
      $this->boolean($rel->canEdit($idr[3]))->isTrue();

      $input = [
         'contacts_id' =>  $idc[1], // root + rec
         'suppliers_id' => $ids[2]  // child 1
      ];
      $this->boolean($rel->can(-1, CREATE, $input))->isTrue();
      $idr[4] = (int)$rel->add($input);
      $this->integer($idr[4])->isGreaterThan(0);
      $this->boolean($rel->can($idr[4], READ))->isTrue();
      $this->boolean($rel->canEdit($idr[4]))->isTrue();

      $input = [
         'contacts_id' =>  $idc[1], // root + rec
         'suppliers_id' => $ids[3]  // child 2
      ];
      $this->boolean($rel->can(-1, CREATE, $input))->isTrue();
      $idr[5] = (int)$rel->add($input);
      $this->integer($idr[5])->isGreaterThan(0);
      $this->boolean($rel->can($idr[5], READ))->isTrue();
      $this->boolean($rel->canEdit($idr[5]))->isTrue();

      $input = [
         'contacts_id' =>  $idc[2], // Child 1
         'suppliers_id' => $ids[0]  // root
      ];
      $this->boolean($rel->can(-1, CREATE, $input))->isFalse();

      $input = [
         'contacts_id' =>  $idc[2], // Child 1
         'suppliers_id' => $ids[1]  // root + rec
      ];
      $this->boolean($rel->can(-1, CREATE, $input))->isTrue();
      $idr[6] = (int)$rel->add($input);
      $this->integer($idr[6])->isGreaterThan(0);
      $this->boolean($rel->can($idr[6], READ))->isTrue();
      $this->boolean($rel->canEdit($idr[6]))->isTrue();

      $input = [
         'contacts_id' =>  $idc[2], // Child 1
         'suppliers_id' => $ids[2]  // Child 1
      ];
      $this->boolean($rel->can(-1, CREATE, $input))->isTrue();
      $idr[7] = (int)$rel->add($input);
      $this->integer($idr[7])->isGreaterThan(0);
      $this->boolean($rel->can($idr[7], READ))->isTrue();
      $this->boolean($rel->canEdit($idr[7]))->isTrue();

      $input = [
         'contacts_id' =>  $idc[2], // Child 1
         'suppliers_id' => $ids[3]  // Child 2
      ];
      $this->boolean($rel->can(-1, CREATE, $input))->isFalse();

      // See only in child entity 2 + parent if recursive
      $this->boolean(\Session::changeActiveEntities($ent2))->isTrue();

      $this->boolean($rel->can($idr[0], READ))->isFalse();  // root / root
      //$this->boolean($rel->canEdit($idr[0]))->isFalse();
      $this->boolean($rel->can($idr[1], READ))->isFalse();  // root / root rec
      //$this->boolean($rel->canEdit($idr[1]))->isFalse();
      $this->boolean($rel->can($idr[2], READ))->isFalse();  // root rec / root
      //$this->boolean($rel->canEdit($idr[2]))->isFalse();
      $this->boolean($rel->can($idr[3], READ))->isTrue();   // root rec / root rec
      //$this->boolean($rel->canEdit($idr[3]))->isFalse();
      $this->boolean($rel->can($idr[4], READ))->isFalse();  // root rec / child 1
      //$this->boolean($rel->canEdit($idr[4]))->isFalse();
      $this->boolean($rel->can($idr[5], READ))->isTrue();   // root rec / child 2
      $this->boolean($rel->canEdit($idr[5]))->isTrue();
      $this->boolean($rel->can($idr[6], READ))->isFalse();  // child 1 / root rec
      //$this->boolean($rel->canEdit($idr[6]))->isFalse();
      $this->boolean($rel->can($idr[7], READ))->isFalse();  // child 1 / child 1
      //$this->boolean($rel->canEdit($idr[7]))->isFalse();

      $input = [
         'contacts_id' =>  $idc[0], // root
         'suppliers_id' => $ids[0]  // root
      ];
      $this->boolean($rel->can(-1, CREATE, $input))->isFalse();

      $input = [
         'contacts_id'  =>  $idc[0],// root
         'suppliers_id' => $ids[1]  // root + rec
      ];
      $this->boolean($rel->can(-1, CREATE, $input))->isFalse();

      $input = [
         'contacts_id'  =>  $idc[1],// root + rec
         'suppliers_id' => $ids[0]  // root
      ];
      $this->boolean($rel->can(-1, CREATE, $input))->isFalse();

      $input = [
         'contacts_id' =>  $idc[3], // Child 2
         'suppliers_id' => $ids[0]  // root
      ];
      $this->boolean($rel->can(-1, CREATE, $input))->isFalse();

      $input = [
         'contacts_id'  =>  $idc[3],// Child 2
         'suppliers_id' => $ids[1]  // root + rec
      ];
      $this->boolean($rel->can(-1, CREATE, $input))->isTrue();
      $idr[7] = (int)$rel->add($input);
      $this->integer($idr[7])->isGreaterThan(0);
      $this->boolean($rel->can($idr[7], READ))->isTrue();
      $this->boolean($rel->canEdit($idr[7]))->isTrue();

      $input = [
         'contacts_id' =>  $idc[3], // Child 2
         'suppliers_id' => $ids[2]  // Child 1
      ];
      $this->boolean($rel->can(-1, CREATE, $input))->isFalse();

      $input = [
         'contacts_id' =>  $idc[3], // Child 2
         'suppliers_id' => $ids[3]  // Child 2
      ];
      $this->boolean($rel->can(-1, CREATE, $input))->isTrue();
      $idr[8] = (int)$rel->add($input);
      $this->integer($idr[8])->isGreaterThan(0);
      $this->boolean($rel->can($idr[8], READ))->isTrue();
      $this->boolean($rel->canEdit($idr[8]))->isTrue();
   }

   /**
    * Entity right check
    */
   public function testEntity() {
      $this->login();

      $ent0 = getItemByTypeName('Entity', '_test_root_entity', true);
      $ent1 = getItemByTypeName('Entity', '_test_child_1', true);
      $ent2 = getItemByTypeName('Entity', '_test_child_2', true);

      $entity = new \Entity();
      $ent3 = (int)$entity->add([
         'name'         => '_test_child_2_subchild_1',
         'entities_id'  => $ent2
      ]);
      $this->integer($ent3)->isGreaterThan(0);

      $ent4 = (int)$entity->add([
         'name'         => '_test_child_2_subchild_2',
         'entities_id'  => $ent2
      ]);
      $this->integer($ent4)->isGreaterThan(0);

      $this->boolean(\Session::changeActiveEntities('all'))->isTrue();

      $this->boolean($entity->can(0, READ))->isTrue("Fail: can't read root entity");
      $this->boolean($entity->can($ent0, READ))->isTrue("Fail: can't read entity 0");
      $this->boolean($entity->can($ent1, READ))->isTrue("Fail: can't read entity 1");
      $this->boolean($entity->can($ent2, READ))->isTrue("Fail: can't read entity 2");
      $this->boolean($entity->can($ent3, READ))->isTrue("Fail: can't read entity 2.1");
      $this->boolean($entity->can($ent4, READ))->isTrue("Fail: can't read entity 2.2");
      $this->boolean($entity->can(99999, READ))->isFalse("Fail: can read not existing entity");

      $this->boolean($entity->canEdit(0))->isTrue("Fail: can't write root entity");
      $this->boolean($entity->canEdit($ent0))->isTrue("Fail: can't write entity 0");
      $this->boolean($entity->canEdit($ent1))->isTrue("Fail: can't write entity 1");
      $this->boolean($entity->canEdit($ent2))->isTrue("Fail: can't write entity 2");
      $this->boolean($entity->canEdit($ent3))->isTrue("Fail: can't write entity 2.1");
      $this->boolean($entity->canEdit($ent4))->isTrue("Fail: can't write entity 2.2");
      $this->boolean($entity->canEdit(99999))->isFalse("Fail: can write not existing entity");

      $input=['entities_id' => $ent1];
      $this->boolean($entity->can(-1, CREATE, $input))->isTrue("Fail: can create entity in root");
      $input=['entities_id' => $ent2];
      $this->boolean($entity->can(-1, CREATE, $input))->isTrue("Fail: can't create entity in 2");
      $input=['entities_id' => $ent3];
      $this->boolean($entity->can(-1, CREATE, $input))->isTrue("Fail: can't create entity in 2.1");
      $input=['entities_id' => 99999];
      $this->boolean($entity->can(-1, CREATE, $input))->isFalse("Fail: can create entity in not existing entity");
      $input=['entities_id' => -1];
      $this->boolean($entity->can(-1, CREATE, $input))->isFalse("Fail: can create entity in not existing entity");

      $this->boolean(\Session::changeActiveEntities($ent2, false))->isTrue();
      $input=['entities_id' => $ent1];
      $this->boolean($entity->can(-1, CREATE, $input))->isFalse("Fail: can create entity in root");
      $input=['entities_id' => $ent2];
      // next should be false (or not).... but check is done on glpiactiveprofile
      // will require to save current state in session - this is probably acceptable
      // this allow creation when no child defined yet (no way to select tree in this case)
      $this->boolean($entity->can(-1, CREATE, $input))->isTrue("Fail: can't create entity in 2");
      $input=['entities_id' => $ent3];
      $this->boolean($entity->can(-1, CREATE, $input))->isFalse("Fail: can create entity in 2.1");
   }

   public function testAdd() {
      $computer = new \Computer();
      $ent0 = getItemByTypeName('Entity', '_test_root_entity', true);
      $bkp_current = $_SESSION['glpi_currenttime'];
      $_SESSION['glpi_currenttime'] = '2000-01-01 00:00:00';

      //test with date set
      $computerID = $computer->add([
         'name'            => 'Computer01',
         'date_creation'   => '2018-01-01 11:22:33',
         'date_mod'        => '2018-01-01 22:33:44',
         'entities_id'     => $ent0
      ]);

      $this->integer($computerID)->isGreaterThan(0);
      $this->boolean(
         $computer->getFromDB($computerID)
      )->isTrue();
      // Verify you can override creation and modifcation dates from add
      $this->string($computer->fields['date_creation'])->isEqualTo('2018-01-01 11:22:33');
      $this->string($computer->fields['date_mod'])->isEqualTo('2018-01-01 22:33:44');

      //test with default date
      $computerID = $computer->add([
         'name'            => 'Computer01',
         'entities_id'     => $ent0
      ]);

      $this->integer($computerID)->isGreaterThan(0);
      $this->boolean(
         $computer->getFromDB($computerID)
      )->isTrue();
      // Verify default date has been used
      $this->string($computer->fields['date_creation'])->isEqualTo('2000-01-01 00:00:00');
      $this->string($computer->fields['date_mod'])->isEqualTo('2000-01-01 00:00:00');

      $_SESSION['glpi_currenttime'] = $bkp_current;
   }

   /**
    * Test that 'item.get_empty' event is dispatched when getting an empty item.
    */
   public function testGetEmptyEvent() {

      global $CONTAINER;

      /** @var \Glpi\EventDispatcher\EventDispatcher $dispatcher */
      $dispatcher = $CONTAINER->get(\Glpi\EventDispatcher\EventDispatcher::class);

      $item  = new \Computer();

      $getEmptyEventItem = null; // Will be set if listened event is dispatched
      $getEmptyListener = function(ItemEvent $event) use (&$getEmptyEventItem) {
         $getEmptyEventItem = $event->getItem();
         $getEmptyEventItem->fields['comment'] = 'Comment added by listener';
      };
      $dispatcher->addListener(ItemEvent::ITEM_GET_EMPTY, $getEmptyListener);

      $item->getEmpty();

      $this->object($getEmptyEventItem)->isEqualTo($item);
      $this->array($getEmptyEventItem->fields)->hasKey('comment');
      $this->string($getEmptyEventItem->fields['comment'])->isEqualTo('Comment added by listener');

      $dispatcher->removeListener(ItemEvent::ITEM_GET_EMPTY, $getEmptyListener);
   }

   /**
    * Test that 'item.pre_add', 'item.post_prepare_add' and 'item.post_add' events
    * are dispatched in expected order when creating a new item.
    */
   public function testAddProcessEventsOrder() {

      global $CONTAINER;

      /** @var \Glpi\EventDispatcher\EventDispatcher $dispatcher */
      $dispatcher = $CONTAINER->get(\Glpi\EventDispatcher\EventDispatcher::class);

      $item  = new \Computer();

      $self = $this;

      $preAddEventItem = null; // Will be set if listened event is dispatched
      $preAddListener = function(ItemEvent $event) use (&$preAddEventItem, $self) {
         $item = $event->getItem();
         $self->object($item)->isInstanceOf(\CommonDBTM::class);
         $self->array($item->input)->hasKey('comment');
         $self->string($item->input['comment'])->isEqualTo('');
         $item->input['comment'] = ItemEvent::ITEM_PRE_ADD;

         $preAddEventItem = $item;

         // Assert that item has not been created in DB when event is dispatched
         $self->array($item->fields)->isEmpty();
      };
      $dispatcher->addListener(ItemEvent::ITEM_PRE_ADD, $preAddListener);

      $postPrepareAddEventItem = null; // Will be set if listened event is dispatched
      $postPrepareAddListener = function(ItemEvent $event) use (&$postPrepareAddEventItem, $self) {
         $item = $event->getItem();
         $self->object($item)->isInstanceOf(\CommonDBTM::class);
         $self->array($item->input)->hasKey('comment');
         $self->string($item->input['comment'])->isEqualTo(ItemEvent::ITEM_PRE_ADD);
         $item->input['comment'] = ItemEvent::ITEM_POST_PREPARE_ADD;

         $postPrepareAddEventItem = $item;

         // Assert that item has not been created in DB when event is dispatched
         $self->array($item->fields)->isEmpty();
      };
      $dispatcher->addListener(ItemEvent::ITEM_POST_PREPARE_ADD, $postPrepareAddListener);

      $postAddEventItem = null; // Will be set if listened event is dispatched
      $postAddListener = function(ItemEvent $event) use (&$postAddEventItem, $self) {
         $item = $event->getItem();
         $self->object($item)->isInstanceOf(\CommonDBTM::class);
         $self->array($item->input)->hasKey('comment');
         $self->string($item->input['comment'])->isEqualTo(ItemEvent::ITEM_POST_PREPARE_ADD);
         $item->input['comment'] = ItemEvent::ITEM_POST_ADD;

         $postAddEventItem = $item;

         // Assert that item has been created in DB when event is dispatched
         $self->array($item->find(['id' => $item->fields['id']]))->isNotEmpty();
      };
      $dispatcher->addListener(ItemEvent::ITEM_POST_ADD, $postAddListener);

      $itemId = $item->add([
         'name'        => 'Computer test',
         'comment'     => '',
         'entities_id' => 0
      ]);
      $this->integer($itemId)->isGreaterThan(0);

      $self->array($item->input)->hasKey('comment');
      $self->string($item->input['comment'])->isEqualTo(ItemEvent::ITEM_POST_ADD);

      $this->object($preAddEventItem)->isEqualTo($item);
      $this->object($postPrepareAddEventItem)->isEqualTo($item);
      $this->object($postAddEventItem)->isEqualTo($item);

      $dispatcher->removeListener(ItemEvent::ITEM_PRE_ADD, $preAddListener);
      $dispatcher->removeListener(ItemEvent::ITEM_POST_PREPARE_ADD, $postPrepareAddListener);
      $dispatcher->removeListener(ItemEvent::ITEM_POST_ADD, $postAddListener);
   }

   /**
    * Test that events can be used to alter/prevent creation of items.
    */
   public function testAddEventsBehaviors() {

      global $CONTAINER;

      /** @var \Glpi\EventDispatcher\EventDispatcher $dispatcher */
      $dispatcher = $CONTAINER->get(\Glpi\EventDispatcher\EventDispatcher::class);

      $eventsNames = [
         ItemEvent::ITEM_PRE_ADD,
         ItemEvent::ITEM_POST_PREPARE_ADD,
      ];
      foreach ($eventsNames as $eventName) {
         // Test that listener can prevent creation of item
         $listener = function(ItemEvent $event) {
            $item = $event->getItem();
            $item->input = false;
         };
         $dispatcher->addListener($eventName, $listener);

         $item  = new \Computer();
         $result = $item->add([
            'name'        => 'Computer01',
            'entities_id' => 0
         ]);
         $this->boolean($result)->isFalse();

         $dispatcher->removeListener($eventName, $listener);

         // Test that listener can modify created data
         $listener = function(ItemEvent $event) {
            $item = $event->getItem();
            $item->input['comment'] = 'Comment added by listener.';
         };
         $dispatcher->addListener($eventName, $listener);

         $item   = $this->getNewItem();
         $itemId = $item->fields['id'];

         $item  = new \Computer();
         $result = $item->getFromDB($itemId);
         $this->boolean($result)->isTrue();
         $this->string($item->fields['comment'])->isEqualTo('Comment added by listener.');

         $dispatcher->removeListener($eventName, $listener);
      }
   }

   /**
    * Test that 'item.pre_update', 'item.post_update' events are dispatched
    * in expected order when updating an item.
    */
   public function testUpdateProcessEventsOrder() {

      global $CONTAINER;

      /** @var \Glpi\EventDispatcher\EventDispatcher $dispatcher */
      $dispatcher = $CONTAINER->get(\Glpi\EventDispatcher\EventDispatcher::class);

      $item  = new \Computer();
      $itemId = $item->add([
         'name'        => 'New computer',
         'comment'     => 'Initial state',
         'entities_id' => 0
      ]);
      $this->integer($itemId)->isGreaterThan(0);
      $itemId = $item->fields['id'];

      $self = $this;

      $preUpdateEventItem = null; // Will be set if listened event is dispatched
      $preUpdateListener = function(ItemEvent $event) use (&$preUpdateEventItem, $self) {
         $item = $event->getItem();
         $self->object($item)->isInstanceOf(\CommonDBTM::class);
         $self->array($item->input)->hasKey('comment');
         $self->string($item->input['comment'])->isEqualTo('');
         $item->input['comment'] = ItemEvent::ITEM_PRE_UPDATE;

         $preUpdateEventItem = $item;

         // Assert that item has not been updated in DB when event is dispatched
         $self->array($item->find(['id' => $item->fields['id'], 'comment' => 'Initial state']))->isNotEmpty();
      };
      $dispatcher->addListener(ItemEvent::ITEM_PRE_UPDATE, $preUpdateListener);

      $postUpdateEventItem = null; // Will be set if listened event is dispatched
      $postUpdateListener = function(ItemEvent $event) use (&$postUpdateEventItem, $self) {
         $item = $event->getItem();
         $self->object($item)->isInstanceOf(\CommonDBTM::class);
         $self->array($item->input)->hasKey('comment');
         $self->string($item->input['comment'])->isEqualTo(ItemEvent::ITEM_PRE_UPDATE);
         $item->input['comment'] = ItemEvent::ITEM_POST_UPDATE;

         $postUpdateEventItem = $item;

         // Assert that item has been updated in DB when event is dispatched
         $self->array($item->find(['id' => $item->fields['id'], 'comment' => ItemEvent::ITEM_PRE_UPDATE]))->isNotEmpty();
      };
      $dispatcher->addListener(ItemEvent::ITEM_POST_UPDATE, $postUpdateListener);

      $result = $item->update([
         'id'      => $itemId,
         'comment' => '',
      ]);
      $this->boolean($result)->isTrue();

      $self->array($item->input)->hasKey('comment');
      $self->string($item->input['comment'])->isEqualTo(ItemEvent::ITEM_POST_UPDATE);

      $this->object($preUpdateEventItem)->isEqualTo($item);
      $this->object($postUpdateEventItem)->isEqualTo($item);

      $dispatcher->removeListener(ItemEvent::ITEM_PRE_UPDATE, $preUpdateListener);
      $dispatcher->removeListener(ItemEvent::ITEM_POST_UPDATE, $postUpdateListener);
   }

   /**
    * Test that events can be used to alter/prevent update of items.
    */
   public function testUpdateEventsBehaviors() {

      global $CONTAINER;

      /** @var \Glpi\EventDispatcher\EventDispatcher $dispatcher */
      $dispatcher = $CONTAINER->get(\Glpi\EventDispatcher\EventDispatcher::class);

      $item   = $this->getNewItem();
      $itemId = $item->fields['id'];

      $eventName = ItemEvent::ITEM_PRE_UPDATE;

      // Test that listener can prevent update of item
      $listener = function(ItemEvent $event) {
         $item = $event->getItem();
         $item->input = false;
      };
      $dispatcher->addListener($eventName, $listener);

      $result = $item->update([
         'id'      => $itemId,
         'comment' => '',
      ]);
      $this->boolean($result)->isFalse();

      $dispatcher->removeListener($eventName, $listener);

      // Test that listener can modify updated data
      $listener = function(ItemEvent $event) {
         $item = $event->getItem();
         $item->input['comment'] = 'Comment added by listener.';
      };
      $dispatcher->addListener($eventName, $listener);

      $result = $item->update([
         'id'      => $itemId,
         'comment' => '',
      ]);
      $this->boolean($result)->isTrue();

      $item  = new \Computer();
      $result = $item->getFromDB($itemId);
      $this->boolean($result)->isTrue();
      $this->string($item->fields['comment'])->isEqualTo('Comment added by listener.');

      $dispatcher->removeListener($eventName, $listener);
   }

   /**
    * Test that 'item.pre_delete', 'item.post_delete' events are dispatched
    * in expected order when deleting an item.
    */
   public function testDeleteProcessEventsOrder() {

      global $CONTAINER;

      /** @var \Glpi\EventDispatcher\EventDispatcher $dispatcher */
      $dispatcher = $CONTAINER->get(\Glpi\EventDispatcher\EventDispatcher::class);

      $item   = $this->getNewItem();
      $itemId = $item->fields['id'];

      $self = $this;

      $preDeleteEventItem = null; // Will be set if listened event is dispatched
      $preDeleteListener = function(ItemEvent $event) use (&$preDeleteEventItem, $self) {
         $item = $event->getItem();
         $self->object($item)->isInstanceOf(\CommonDBTM::class);
         $self->array($item->input)->hasKey('comment');
         $self->string($item->input['comment'])->isEqualTo('');
         $item->input['comment'] = ItemEvent::ITEM_PRE_DELETE;

         $preDeleteEventItem = $item;

         // Assert that item has not been marked as deleted in DB when event is dispatched
         $self->array($item->find(['id' => $item->input['id'], 'is_deleted' => 0]))->isNotEmpty();
      };
      $dispatcher->addListener(ItemEvent::ITEM_PRE_DELETE, $preDeleteListener);

      $postDeleteEventItem = null; // Will be set if listened event is dispatched
      $postDeleteListener = function(ItemEvent $event) use (&$postDeleteEventItem, $self) {
         $item = $event->getItem();
         $self->object($item)->isInstanceOf(\CommonDBTM::class);
         $self->array($item->input)->hasKey('comment');
         $self->string($item->input['comment'])->isEqualTo(ItemEvent::ITEM_PRE_DELETE);
         $item->input['comment'] = ItemEvent::ITEM_POST_DELETE;

         $postDeleteEventItem = $item;

         // Assert that item has been marked as deleted in DB when event is dispatched
         $self->array($item->find(['id' => $item->input['id'], 'is_deleted' => 1]))->isNotEmpty();
      };
      $dispatcher->addListener(ItemEvent::ITEM_POST_DELETE, $postDeleteListener);

      $result = $item->delete([
         'id'      => $itemId,
         'comment' => '',
      ]);
      $this->boolean($result)->isTrue();

      $self->array($item->input)->hasKey('comment');
      $self->string($item->input['comment'])->isEqualTo(ItemEvent::ITEM_POST_DELETE);

      $this->object($preDeleteEventItem)->isEqualTo($item);
      $this->object($postDeleteEventItem)->isEqualTo($item);

      $dispatcher->removeListener(ItemEvent::ITEM_PRE_DELETE, $preDeleteListener);
      $dispatcher->removeListener(ItemEvent::ITEM_POST_DELETE, $postDeleteListener);
   }

   /**
    * Test that events can be used to prevent deletion of items.
    */
   public function testDeleteEventsBehaviors() {

      global $CONTAINER;

      /** @var \Glpi\EventDispatcher\EventDispatcher $dispatcher */
      $dispatcher = $CONTAINER->get(\Glpi\EventDispatcher\EventDispatcher::class);

      $item   = $this->getNewItem();
      $itemId = $item->fields['id'];

      $eventName = ItemEvent::ITEM_PRE_DELETE;

      // Test that listener can prevent deletion of item
      $listener = function(ItemEvent $event) {
         $item = $event->getItem();
         $item->input = false;
      };
      $dispatcher->addListener($eventName, $listener);

      $result = $item->delete([
         'id' => $itemId,
      ]);
      $this->boolean($result)->isFalse();

      $dispatcher->removeListener($eventName, $listener);
   }

   /**
    * Test that 'item.pre_purge', 'item.post_purge' events are dispatched
    * in expected order when purging an item.
    */
   public function testPurgeProcessEventsOrder() {

      global $CONTAINER;

      /** @var \Glpi\EventDispatcher\EventDispatcher $dispatcher */
      $dispatcher = $CONTAINER->get(\Glpi\EventDispatcher\EventDispatcher::class);

      $item   = $this->getNewItem();
      $itemId = $item->fields['id'];

      $self = $this;

      $prePurgeEventItem = null; // Will be set if listened event is dispatched
      $prePurgeListener = function(ItemEvent $event) use (&$prePurgeEventItem, $self) {
         $item = $event->getItem();
         $self->object($item)->isInstanceOf(\CommonDBTM::class);
         $self->array($item->input)->hasKey('comment');
         $self->string($item->input['comment'])->isEqualTo('');
         $item->input['comment'] = ItemEvent::ITEM_PRE_PURGE;

         $prePurgeEventItem = $item;

         // Assert that item has not been deleted from DB when event is dispatched
         $self->array($item->find(['id' => $item->fields['id']]))->isNotEmpty();
      };
      $dispatcher->addListener(ItemEvent::ITEM_PRE_PURGE, $prePurgeListener);

      $postPurgeEventItem = null; // Will be set if listened event is dispatched
      $postPurgeListener = function(ItemEvent $event) use (&$postPurgeEventItem, $self) {
         $item = $event->getItem();
         $self->object($item)->isInstanceOf(\CommonDBTM::class);
         $self->array($item->input)->hasKey('comment');
         $self->string($item->input['comment'])->isEqualTo(ItemEvent::ITEM_PRE_PURGE);
         $item->input['comment'] = ItemEvent::ITEM_POST_PURGE;

         $postPurgeEventItem = $item;

         // Assert that item has been deleted from DB when event is dispatched
         $self->array($item->find(['id' => $item->fields['id']]))->isEmpty();
      };
      $dispatcher->addListener(ItemEvent::ITEM_POST_PURGE, $postPurgeListener);

      $result = $item->delete(
         [
            'id'      => $itemId,
            'comment' => '',
         ],
         true
      );
      $this->boolean($result)->isTrue();

      $self->array($item->input)->hasKey('comment');
      $self->string($item->input['comment'])->isEqualTo(ItemEvent::ITEM_POST_PURGE);

      $this->object($prePurgeEventItem)->isEqualTo($item);
      $this->object($postPurgeEventItem)->isEqualTo($item);

      $dispatcher->removeListener(ItemEvent::ITEM_PRE_PURGE, $prePurgeListener);
      $dispatcher->removeListener(ItemEvent::ITEM_POST_PURGE, $postPurgeListener);
   }

   /**
    * Test that events can be used to prevent purge of items.
    */
   public function testPurgeEventsBehaviors() {

      global $CONTAINER;

      /** @var \Glpi\EventDispatcher\EventDispatcher $dispatcher */
      $dispatcher = $CONTAINER->get(\Glpi\EventDispatcher\EventDispatcher::class);

      $item   = $this->getNewItem();
      $itemId = $item->fields['id'];

      $eventName = ItemEvent::ITEM_PRE_PURGE;

      // Test that listener can prevent deletion of item
      $listener = function(ItemEvent $event) {
         $item = $event->getItem();
         $item->input = false;
      };
      $dispatcher->addListener($eventName, $listener);

      $result = $item->delete(
         [
            'id' => $itemId,
         ],
         true
      );
      $this->boolean($result)->isFalse();

      $dispatcher->removeListener($eventName, $listener);
   }

   /**
    * Test that 'item.pre_restore', 'item.post_restore' events are dispatched
    * in expected order when restoring an item.
    */
   public function testRestoreProcessEventsOrder() {

      global $CONTAINER;

      /** @var \Glpi\EventDispatcher\EventDispatcher $dispatcher */
      $dispatcher = $CONTAINER->get(\Glpi\EventDispatcher\EventDispatcher::class);

      $item   = $this->getNewItem();
      $itemId = $item->fields['id'];

      $result = $item->delete([
         'id' => $itemId,
      ]);
      $this->boolean($result)->isTrue();

      $self = $this;

      $preRestoreEventItem = null; // Will be set if listened event is dispatched
      $preRestoreListener = function(ItemEvent $event) use (&$preRestoreEventItem, $self) {
         $item = $event->getItem();
         $self->object($item)->isInstanceOf(\CommonDBTM::class);
         $self->array($item->input)->hasKey('comment');
         $self->string($item->input['comment'])->isEqualTo('');
         $item->input['comment'] = ItemEvent::ITEM_PRE_RESTORE;

         $preRestoreEventItem = $item;

         // Assert that item has not been unmarked as deleted in DB when event is dispatched
         $self->array($item->find(['id' => $item->input['id'], 'is_deleted' => 1]))->isNotEmpty();
      };
      $dispatcher->addListener(ItemEvent::ITEM_PRE_RESTORE, $preRestoreListener);

      $postRestoreEventItem = null; // Will be set if listened event is dispatched
      $postRestoreListener = function(ItemEvent $event) use (&$postRestoreEventItem, $self) {
         $item = $event->getItem();
         $self->object($item)->isInstanceOf(\CommonDBTM::class);
         $self->array($item->input)->hasKey('comment');
         $self->string($item->input['comment'])->isEqualTo(ItemEvent::ITEM_PRE_RESTORE);
         $item->input['comment'] = ItemEvent::ITEM_POST_RESTORE;

         $postRestoreEventItem = $item;

         // Assert that item has been marked as deleted in DB when event is dispatched
         $self->array($item->find(['id' => $item->input['id'], 'is_deleted' => 0]))->isNotEmpty();
      };
      $dispatcher->addListener(ItemEvent::ITEM_POST_RESTORE, $postRestoreListener);

      $result = $item->restore([
         'id'      => $itemId,
         'comment' => '',
      ]);
      $this->boolean($result)->isTrue();

      $self->array($item->input)->hasKey('comment');
      $self->string($item->input['comment'])->isEqualTo(ItemEvent::ITEM_POST_RESTORE);

      $this->object($preRestoreEventItem)->isEqualTo($item);
      $this->object($postRestoreEventItem)->isEqualTo($item);

      $dispatcher->removeListener(ItemEvent::ITEM_PRE_RESTORE, $preRestoreListener);
      $dispatcher->removeListener(ItemEvent::ITEM_POST_RESTORE, $postRestoreListener);
   }

   /**
    * Test that events can be used to prevent deletion of items.
    */
   public function testRestoreEventsBehaviors() {

      global $CONTAINER;

      /** @var \Glpi\EventDispatcher\EventDispatcher $dispatcher */
      $dispatcher = $CONTAINER->get(\Glpi\EventDispatcher\EventDispatcher::class);

      $item   = $this->getNewItem();
      $itemId = $item->fields['id'];

      $result = $item->delete([
         'id' => $itemId,
      ]);
      $this->boolean($result)->isTrue();

      $eventName = ItemEvent::ITEM_PRE_RESTORE;

      // Test that listener can prevent restore of item
      $listener = function(ItemEvent $event) {
         $item = $event->getItem();
         $item->input = false;
      };
      $dispatcher->addListener($eventName, $listener);

      $result = $item->restore([
         'id' => $itemId,
      ]);
      $this->boolean($result)->isFalse();

      $dispatcher->removeListener($eventName, $listener);
   }

   private function getNewItem(): \CommonDBTM {

      $item  = new \Computer();
      $itemId = $item->add([
         'name'        => 'New computer',
         'entities_id' => 0
      ]);
      $this->integer($itemId)->isGreaterThan(0);

      return $item;
   }

   public function testTimezones() {
      global $DB;

      //check if timezones are available
      $this->boolean($DB->areTimezonesAvailable())->isTrue();
      $this->array($DB->getTimezones())->size->isGreaterThan(0);

      //login with default TZ
      $this->login();
      //add a Compuer with creation and update dates
      $comp = new \Computer();
      $cid = $comp->add([
         'name'            => 'Computer with timezone',
         'date_creation'   => '2019-03-04 10:00:00',
         'date_mod'        => '2019-03-04 10:00:00',
         'entities_id'     => 0
      ]);
      $this->integer($cid)->isGreaterThan(0);

      $this->boolean($comp->getFromDB($cid));
      $this->string($comp->fields['date_creation'])->isIdenticalTo('2019-03-04 10:00:00');

      $user = getItemByTypeName('User', 'glpi');
      $this->boolean($user->update(['id' => $user->fields['id'], 'timezone' => 'Europe/Paris']))->isTrue();

      //check tz is set
      $this->boolean($user->getFromDB($user->fields['id']))->isTrue();
      $this->string($user->fields['timezone'])->isIdenticalTo('Europe/Paris');

      $this->login('glpi', 'glpi');
      $this->boolean($comp->getFromDB($cid));
      $this->string($comp->fields['date_creation'])->matches('/2019-03-04 1[12]:00:00/');
   }
}
