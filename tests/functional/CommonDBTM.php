<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

namespace tests\units;

use DbTestCase;
use SoftwareVersion;

/* Test for inc/commondbtm.class.php */

class CommonDBTM extends DbTestCase
{
    public function testgetIndexNameOtherThanID()
    {

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

    public function testGetFromDBByRequest()
    {
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
                'contact' => 'johndoe'
            ],
                \Entity::getTable() . '.name' => '_test_root_entity',
            ]
        ]);
       // the instance must be populated
        $this->boolean($instance->isNewItem())->isFalse();

        $instance = new \Computer();
        $result = null;
        $this->when(
            function () use ($instance, &$result) {
                $result = $instance->getFromDbByRequest([
                    'WHERE' => ['contact' => 'johndoe'],
                ]);
            }
        )->error
         ->withType(E_USER_WARNING)
         ->withMessage('getFromDBByRequest expects to get one result, 2 found in query "SELECT `glpi_computers`.* FROM `glpi_computers` WHERE `contact` = \'johndoe\'".')
         ->exists();
        $this->boolean($result)->isFalse();

       // the instance must not be populated
        $this->boolean($instance->isNewItem())->isTrue();
    }

    public function testGetFromResultSet()
    {
        global $DB;
        $result = $DB->request([
            'FROM'   => \Computer::getTable(),
            'LIMIT'  => 1
        ])->current();

        $this->array($result)->hasKeys(['name', 'uuid']);

        $computer = new \Computer();
        $computer->getFromResultSet($result);
        $this->array($computer->fields)->isIdenticalTo($result);
    }

    public function testGetId()
    {
        $comp = new \Computer();

        $this->integer($comp->getID())->isIdenticalTo(-1);

        $this->boolean($comp->getFromDBByCrit(['name' => '_test_pc01']))->isTrue();
        $this->integer((int)$comp->getID())->isGreaterThan(0);
    }

    public function testGetEmpty()
    {
        $comp = new \Computer();

        $this->array($comp->fields)->isEmpty();

        $this->boolean($comp->getEmpty())->isTrue();
        $this->array($comp->fields)->integer['entities_id']->isEqualTo(0);

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
    protected function getTableProvider()
    {

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
    public function testGetTable($classname, $tablename)
    {

        $this->string($classname::getTable())
         ->isEqualTo(\CommonDBTM::getTable($classname))
         ->isEqualTo($tablename);
    }

    /**
     * Test CommonDBTM::getTableField() method.
     *
     * @return void
     */
    public function testGetTableField()
    {

       // Exception if field argument is empty
        $this->exception(
            function () {
                \Computer::getTableField('');
            }
        )->isInstanceOf(\InvalidArgumentException::class)
         ->hasMessage('Argument $field cannot be empty.');

       // Exception if class has no table
        $this->exception(
            function () {
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

    public function testupdateOrInsert()
    {
        global $DB;

       //insert case
        $res = (int)$DB->updateOrInsert(
            \Computer::getTable(),
            [
                'name'   => 'serial-to-change',
                'serial' => 'serial-one'
            ],
            [
                'name'   => 'serial-to-change'
            ]
        );
        $this->integer($res)->isGreaterThan(0);

        $check = $DB->request([
            'FROM'   => \Computer::getTable(),
            'WHERE'  => ['name' => 'serial-to-change']
        ])->current();
        $this->array($check)
         ->string['serial']->isIdenticalTo('serial-one');

       //update case
        $res = $DB->updateOrInsert(
            \Computer::getTable(),
            [
                'name'   => 'serial-to-change',
                'serial' => 'serial-changed'
            ],
            [
                'name'   => 'serial-to-change'
            ]
        );
        $this->boolean($res)->isTrue();

        $check = $DB->request([
            'FROM'   => \Computer::getTable(),
            'WHERE'  => ['name' => 'serial-to-change']
        ])->current();
        $this->array($check)
         ->string['serial']->isIdenticalTo('serial-changed');

        $this->integer(
            (int)$DB->insert(
                \Computer::getTable(),
                ['name' => 'serial-to-change']
            )
        )->isGreaterThan(0);

       //multiple update case
        $this->when(
            function () use ($DB) {
                $res = $DB->updateOrInsert(
                    \Computer::getTable(),
                    [
                        'name'   => 'serial-to-change',
                        'serial' => 'serial-changed'
                    ],
                    [
                        'name'   => 'serial-to-change'
                    ]
                );
                $this->boolean($res)->isFalse();
            }
        )->error
         ->withType(E_USER_WARNING)
         ->withMessage('Update would change too many rows!')
         ->exists();

       //allow multiples
        $res = $DB->updateOrInsert(
            \Computer::getTable(),
            [
                'name'   => 'serial-to-change',
                'serial' => 'serial-changed'
            ],
            [
                'name'   => 'serial-to-change'
            ],
            false
        );
        $this->boolean($res)->isTrue();
    }

    public function testupdateOrInsertMerged()
    {
        global $DB;

       //insert case
        $res = (int)$DB->updateOrInsert(
            \Computer::getTable(),
            [
                'serial' => 'serial-one'
            ],
            [
                'name'   => 'serial-to-change'
            ]
        );
        $this->integer($res)->isGreaterThan(0);

        $check = $DB->request([
            'FROM'   => \Computer::getTable(),
            'WHERE'  => ['name' => 'serial-to-change']
        ])->current();
        $this->array($check)
         ->string['serial']->isIdenticalTo('serial-one');

       //update case
        $res = $DB->updateOrInsert(
            \Computer::getTable(),
            [
                'serial' => 'serial-changed'
            ],
            [
                'name'   => 'serial-to-change'
            ]
        );
        $this->boolean($res)->isTrue();

        $check = $DB->request([
            'FROM'   => \Computer::getTable(),
            'WHERE'  => ['name' => 'serial-to-change']
        ])->current();
        $this->array($check)
         ->string['serial']->isIdenticalTo('serial-changed');

        $this->integer(
            (int)$DB->insert(
                \Computer::getTable(),
                ['name' => 'serial-to-change']
            )
        )->isGreaterThan(0);

       //multiple update case
        $this->when(
            function () use ($DB) {
                $res = $DB->updateOrInsert(
                    \Computer::getTable(),
                    [
                        'serial' => 'serial-changed'
                    ],
                    [
                        'name'   => 'serial-to-change'
                    ]
                );
                $this->boolean($res)->isFalse();
            }
        )->error
         ->withType(E_USER_WARNING)
         ->withMessage('Update would change too many rows!')
         ->exists();

       //allow multiples
        $res = $DB->updateOrInsert(
            \Computer::getTable(),
            [
                'serial' => 'serial-changed'
            ],
            [
                'name'   => 'serial-to-change'
            ],
            false
        );
        $this->boolean($res)->isTrue();
    }
    /**
     * Check right on Recursive object
     *
     * @return void
     */
    public function testRecursiveObjectChecks()
    {
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
    public function testContact_Supplier()
    {

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
            'entities_id'  => $ent2
        ]);
        $this->integer($idc[3])->isGreaterThan(0);
        ;

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
    public function testEntity()
    {
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

        $input = ['entities_id' => $ent1];
        $this->boolean($entity->can(-1, CREATE, $input))->isTrue("Fail: can create entity in root");
        $input = ['entities_id' => $ent2];
        $this->boolean($entity->can(-1, CREATE, $input))->isTrue("Fail: can't create entity in 2");
        $input = ['entities_id' => $ent3];
        $this->boolean($entity->can(-1, CREATE, $input))->isTrue("Fail: can't create entity in 2.1");
        $input = ['entities_id' => 99999];
        $this->boolean($entity->can(-1, CREATE, $input))->isFalse("Fail: can create entity in not existing entity");
        $input = ['entities_id' => -1];
        $this->boolean($entity->can(-1, CREATE, $input))->isFalse("Fail: can create entity in not existing entity");

        $this->boolean(\Session::changeActiveEntities($ent2, false))->isTrue();
        $input = ['entities_id' => $ent1];
        $this->boolean($entity->can(-1, CREATE, $input))->isFalse("Fail: can create entity in root");
        $input = ['entities_id' => $ent2];
       // next should be false (or not).... but check is done on glpiactiveprofile
       // will require to save current state in session - this is probably acceptable
       // this allow creation when no child defined yet (no way to select tree in this case)
        $this->boolean($entity->can(-1, CREATE, $input))->isTrue("Fail: can't create entity in 2");
        $input = ['entities_id' => $ent3];
        $this->boolean($entity->can(-1, CREATE, $input))->isFalse("Fail: can create entity in 2.1");
    }

    public function testAdd()
    {
        $computer = new \Computer();
        $ent0 = getItemByTypeName('Entity', '_test_root_entity', true);
        $bkp_current = $_SESSION['glpi_currenttime'];
        $_SESSION['glpi_currenttime'] = '2000-01-01 00:00:00';

       //test with date set
        $computerID = $computer->add(\Toolbox::addslashes_deep([
            'name'            => 'Computer01 \'',
            'date_creation'   => '2018-01-01 11:22:33',
            'date_mod'        => '2018-01-01 22:33:44',
            'entities_id'     => $ent0
        ]));
        $this->string($computer->fields['name'])->isIdenticalTo("Computer01 '");

        $this->integer($computerID)->isGreaterThan(0);
        $this->boolean(
            $computer->getFromDB($computerID)
        )->isTrue();
       // Verify you can override creation and modifcation dates from add
        $this->string($computer->fields['date_creation'])->isEqualTo('2018-01-01 11:22:33');
        $this->string($computer->fields['date_mod'])->isEqualTo('2018-01-01 22:33:44');
        $this->string($computer->fields['name'])->isIdenticalTo("Computer01 '");

       //test with default date
        $computerID = $computer->add(\Toolbox::addslashes_deep([
            'name'            => 'Computer01 \'',
            'entities_id'     => $ent0
        ]));
        $this->string($computer->fields['name'])->isIdenticalTo("Computer01 '");

        $this->integer($computerID)->isGreaterThan(0);
        $this->boolean(
            $computer->getFromDB($computerID)
        )->isTrue();
       // Verify default date has been used
        $this->string($computer->fields['date_creation'])->isEqualTo('2000-01-01 00:00:00');
        $this->string($computer->fields['date_mod'])->isEqualTo('2000-01-01 00:00:00');
        $this->string($computer->fields['name'])->isIdenticalTo("Computer01 '");

        $_SESSION['glpi_currenttime'] = $bkp_current;
    }

    public function testUpdate()
    {
        $computer = new \Computer();
        $ent0 = getItemByTypeName('Entity', '_test_root_entity', true);
        $bkp_current = $_SESSION['glpi_currenttime'];
        $_SESSION['glpi_currenttime'] = '2000-01-01 00:00:00';

       //test with date set
        $computerID = $computer->add(\Toolbox::addslashes_deep([
            'name'            => 'Computer01',
            'date_creation'   => '2018-01-01 11:22:33',
            'date_mod'        => '2018-01-01 22:33:44',
            'entities_id'     => $ent0
        ]));
        $this->string($computer->fields['name'])->isIdenticalTo("Computer01");

        $this->integer($computerID)->isGreaterThan(0);
        $this->boolean(
            $computer->getFromDB($computerID)
        )->isTrue();
        $this->string($computer->fields['name'])->isIdenticalTo("Computer01");

        $this->boolean(
            $computer->update(['id' => $computerID, 'name' => \Toolbox::addslashes_deep('Computer01 \'')])
        )->isTrue();
        $this->string($computer->fields['name'])->isIdenticalTo('Computer01 \'');
        $this->boolean($computer->getFromDB($computerID))->isTrue();
        $this->string($computer->fields['name'])->isIdenticalTo('Computer01 \'');
    }


    public function testTimezones()
    {
        global $DB;

       //check if timezones are available
        $this->boolean($DB->use_timezones)->isTrue();
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

    public function testCircularRelation()
    {
        $project = new \Project();
        $project_id_1 = $project->add([
            'name' => 'Project 1',
            'auto_percent_done' => 1
        ]);
        $this->integer((int) $project_id_1)->isGreaterThan(0);
        $project_id_2 = $project->add([
            'name' => 'Project 2',
            'auto_percent_done' => 1,
            'projects_id' => $project_id_1
        ]);
        $this->integer((int) $project_id_2)->isGreaterThan(0);
        $project_id_3 = $project->add([
            'name' => 'Project 3',
            'projects_id' => $project_id_2
        ]);
        $this->integer((int) $project_id_3)->isGreaterThan(0);
        $project_id_4 = $project->add([
            'name' => 'Project 4',
        ]);
        $this->integer((int) $project_id_4)->isGreaterThan(0);

       // This should evaluate as a circular relation
        $this->boolean(\Project::checkCircularRelation($project_id_1, $project_id_3))->isTrue();
       // This should not evaluate as a circular relation
        $this->boolean(\Project::checkCircularRelation($project_id_4, $project_id_3))->isFalse();
    }

    protected function relationConfigProvider()
    {

        return [
            [
                'relation_itemtype' => \Infocom::getType(),
                'config_name'       => 'infocom_types',
            ],
            [
                'relation_itemtype' => \ReservationItem::getType(),
                'config_name'       => 'reservation_types',
            ],
            [
                'relation_itemtype' => \Contract_Item::getType(),
                'config_name'       => 'contract_types',
                'linked_itemtype'   => \Contract::class,
            ],
            [
                'relation_itemtype' => \Document_Item::getType(),
                'config_name'       => 'document_types',
                'linked_itemtype'   => \Document::class,
            ],
            [
                'relation_itemtype' => \KnowbaseItem_Item::getType(),
                'config_name'       => 'kb_types',
                'linked_itemtype'   => \KnowbaseItem::class,
            ],
        ];
    }

    /**
     * @dataProvider relationConfigProvider
     */
    public function testCleanRelationTableBasedOnConfiguredTypes(
        $relation_itemtype,
        $config_name,
        $linked_itemtype = null
    ) {
        global $CFG_GLPI;

        $entity_id = getItemByTypeName('Entity', '_test_root_entity', true);

        $this->login(); // must be logged as Document_Item uses Session::getLoginUserID()

        $computer = new \Computer();
        $relation_item = new $relation_itemtype();

        $linked_item_input = [];
        if ($linked_itemtype !== null) {
            $linked_item = new $linked_itemtype();
            $linked_item_id = $linked_item->add(
                [
                    'name'        => 'Linked item',
                    'entities_id' => $entity_id,
                ]
            );
            $this->integer($linked_item_id)->isGreaterThan(0);
            $linked_item_input = [$linked_item->getForeignKeyField() => $linked_item_id];
        }

       // Create computer for which cleaning will be done.
        $computer_1_id = $computer->add(
            [
                'name'        => 'Computer 1',
                'entities_id' => $entity_id,
            ]
        );
        $this->integer($computer_1_id)->isGreaterThan(0);
        $relation_item_1_id = $relation_item->add(
            [
                'itemtype' => $computer->getType(),
                'items_id' => $computer_1_id,
            ] + $linked_item_input
        );
        $this->integer($relation_item_1_id)->isGreaterThan(0);
        $this->boolean($relation_item->getFromDB($relation_item_1_id))->isTrue();

       // Create witness computer.
        $computer_2_id = $computer->add(
            [
                'name'        => 'Computer 2',
                'entities_id' => $entity_id,
            ]
        );
        $this->integer($computer_2_id)->isGreaterThan(0);
        $relation_item_2_id = $relation_item->add(
            [
                'itemtype' => $computer->getType(),
                'items_id' => $computer_2_id,
            ] + $linked_item_input
        );
        $this->integer($relation_item_2_id)->isGreaterThan(0);
        $this->boolean($relation_item->getFromDB($relation_item_2_id))->isTrue();

        $cfg_backup = $CFG_GLPI;
        $CFG_GLPI[$config_name] = [$computer->getType()];
        $computer->delete(['id' => $computer_1_id], true);
        $CFG_GLPI = $cfg_backup;

       // Relation with deleted item has been cleaned
        $this->boolean($relation_item->getFromDB($relation_item_1_id))->isFalse();
       // Relation with witness object is still present
        $this->boolean($relation_item->getFromDB($relation_item_2_id))->isTrue();
    }

    public function testCleanItemDeviceDBOnItemDelete()
    {
        $this->login();

        $entity_id     = getItemByTypeName(\Entity::class, '_test_root_entity', true);
        $computer_1    = getItemByTypeName(\Computer::class, '_test_pc01');
        $computer_id_1 = $computer_1->getID();
        $computer_2    = getItemByTypeName(\Computer::class, '_test_pc02');
        $computer_id_2 = $computer_2->getID();
        $phone         = getItemByTypeName(\Phone::class, '_test_phone_1');
        $phone_id      = $phone->getID();

        $device_battery_1 = $this->createItem(
            \DeviceBattery::class,
            [
                'designation'         => 'Battery 1',
                'entities_id'         => $entity_id,
            ]
        );
        $device_battery_1_id = $device_battery_1->getID();
        $device_battery_2 = $this->createItem(
            \DeviceBattery::class,
            [
                'designation'         => 'Battery 2',
                'entities_id'         => $entity_id,
            ]
        );
        $device_battery_2_id = $device_battery_2->getID();

        $items = [
            [
                'itemtype' => \Computer::class,
                'items_id' => $computer_id_1,
            ],
            [
                'itemtype' => \Computer::class,
                'items_id' => $computer_id_2,
            ],
            [
                'itemtype' => \Phone::class,
                'items_id' => $phone_id,
            ],
        ];
        foreach ($items as $item) {
            foreach ([$device_battery_1_id, $device_battery_2_id] as $device_battery_id) {
                $this->createItem(
                    \Item_DeviceBattery::class,
                    $item + [
                        'devicebatteries_id' => $device_battery_id,
                        'entities_id'        => $entity_id,
                    ]
                );
            }
        }

        // Check that only created relations exists
        $this->integer(
            countElementsInTable(\Item_DeviceBattery::getTable())
        )->isEqualTo(6);
        $this->integer(
            countElementsInTable(\Item_DeviceBattery::getTable(), ['itemtype' => \Computer::class, 'items_id' => $computer_id_1])
        )->isEqualTo(2);
        $this->integer(
            countElementsInTable(\Item_DeviceBattery::getTable(), ['itemtype' => \Computer::class, 'items_id' => $computer_id_2])
        )->isEqualTo(2);
        $this->integer(
            countElementsInTable(\Item_DeviceBattery::getTable(), ['itemtype' => \Phone::class, 'items_id' => $phone_id])
        )->isEqualTo(2);

        $computer_1->delete(['id' => $computer_id_1, 'keep_devices' => 1], true);

        // Check that only relations to computer were cleaned
        $this->integer(
            countElementsInTable(\Item_DeviceBattery::getTable())
        )->isEqualTo(6); // item devices were preserved but detached
        $this->integer(
            countElementsInTable(\Item_DeviceBattery::getTable(), ['itemtype' => \Computer::class, 'items_id' => $computer_id_1])
        )->isEqualTo(0);
        $this->integer(
            countElementsInTable(\Item_DeviceBattery::getTable(), ['itemtype' => '', 'items_id' => 0])
        )->isEqualTo(2);
        $this->integer(
            countElementsInTable(\Item_DeviceBattery::getTable(), ['itemtype' => \Computer::class, 'items_id' => $computer_id_2])
        )->isEqualTo(2);
        $this->integer(
            countElementsInTable(\Item_DeviceBattery::getTable(), ['itemtype' => \Phone::class, 'items_id' => $phone_id])
        )->isEqualTo(2);

        $computer_2->delete(['id' => $computer_id_2], true);
        // Check that only relations to computer were cleaned
        $this->integer(
            countElementsInTable(\Item_DeviceBattery::getTable())
        )->isEqualTo(4); // item devices were deleted
        $this->integer(
            countElementsInTable(\Item_DeviceBattery::getTable(), ['itemtype' => \Computer::class, 'items_id' => $computer_id_1])
        )->isEqualTo(0);
        $this->integer(
            countElementsInTable(\Item_DeviceBattery::getTable(), ['itemtype' => '', 'items_id' => 0])
        )->isEqualTo(2);
        $this->integer(
            countElementsInTable(\Item_DeviceBattery::getTable(), ['itemtype' => \Computer::class, 'items_id' => $computer_id_2])
        )->isEqualTo(0);
        $this->integer(
            countElementsInTable(\Item_DeviceBattery::getTable(), ['itemtype' => \Phone::class, 'items_id' => $phone_id])
        )->isEqualTo(2);
    }


    protected function testCheckTemplateEntityProvider()
    {
        $sv1 = getItemByTypeName('SoftwareVersion', '_test_softver_1');

        $sv2 = getItemByTypeName('SoftwareVersion', '_test_softver_1');
        $sv2->fields['entities_id'] = 99999;

        $sv3 = getItemByTypeName('SoftwareVersion', '_test_softver_1');
        $sv3->fields['entities_id'] = 99999;

        return [
            [
            // Case 1: no entites field -> no change
                'data'            => ['test' => "test"],
                'parent_id'       => 999,
                'parent_itemtype' => SoftwareVersion::class,
                'active_entities' => [],
                'expected'        => ['test' => "test"],
            ],
            [
            // Case 2: entity is allowed -> no change
                'data'            => $sv1->fields,
                'parent_id'       => $sv1->fields['softwares_id'],
                'parent_itemtype' => SoftwareVersion::class,
                'active_entities' => [$sv1->fields['entities_id']],
                'expected'        => $sv1->fields,
            ],
            [
            // Case 3: entity is not allowed -> change to parent entity
                'data'            => $sv2->fields, // SV with modified entity
                'parent_id'       => $sv2->fields['softwares_id'],
                'parent_itemtype' => SoftwareVersion::class,
                'active_entities' => [],
                'expected'        => $sv1->fields, // SV with correct entity
            ],
            [
            // Case 4: can't load parent -> no change
                'data'            => $sv3->fields,
                'parent_id'       => 99999,
                'parent_itemtype' => SoftwareVersion::class,
                'active_entities' => [],
                'expected'        => $sv3->fields,
            ],
        ];
    }

    /**
     * @dataProvider testCheckTemplateEntityProvider
     */
    public function testCheckTemplateEntity(
        array $data,
        $parent_id,
        $parent_itemtype,
        array $active_entities,
        array $expected
    ) {
        $_SESSION['glpiactiveentities'] = $active_entities;

        $res = \CommonDBTM::checkTemplateEntity($data, $parent_id, $parent_itemtype);
        $this->array($res)->isEqualTo($expected);

       // Reset session
        unset($_SESSION['glpiactiveentities']);
    }

    public function testGetById()
    {
        $itemtype = \Computer::class;

       // test null ID
        $output = $itemtype::getById(null);
        $this->boolean($output)->isFalse();

       // test existing item
        $instance = new $itemtype();
        $instance->getFromDBByRequest([
            'WHERE' => ['name' => '_test_pc01'],
        ]);
        $this->boolean($instance->isNewItem())->isFalse();
        $output = $itemtype::getById($instance->getID());
        $this->object($output)->isInstanceOf($itemtype);

       // test non-existing item
        $instance = new $itemtype();
        $instance->add([
            'name' => 'to be deleted',
            'entities_id' => 0,
        ]);
        $this->boolean($instance->isNewItem())->isFalse();
        $nonExistingId = $instance->getID();
        $instance->delete([
            'id' => $nonExistingId,
        ], 1);
        $this->boolean($instance->getFromDB($nonExistingId))->isFalse();

        $output = $itemtype::getById($nonExistingId);
        $this->boolean($output)->isFalse();
    }


    protected function textValueProvider(): iterable
    {
        $value = 'This is not a long value';
        yield [
            'value'     => $value,
            'truncated' => $value,
            'length'    => 24,
        ];

        // 500 1-byte chars
        // truncated string should contains 255 1-byte chars
        yield [
            'value'     => str_repeat('12345', 100),
            'truncated' => str_repeat('12345', 51), // 5 * 51 = 255
            'length'    => 500,
        ];

        // value that have a `\` as 255th char
        yield [
            'value'     => str_repeat('a', 254) . '\\abcdefg',
            'truncated' => str_repeat('a', 254),
            'length'    => 262,
        ];

        // 253 1-byte chars followed by a 4-bytes char
        // string should not be truncated because the size in the database is expressed in number of characters and not in bytes
        $value = str_repeat('x', 253) . '𝄠';
        yield [
            'value'     => $value,
            'truncated' => $value,
            'length'    => 254,
        ];

        // 224 (7 * 32) 4-bytes chars
        // string should not be truncated because the size in the database is expressed in number of characters and not in bytes
        $value = str_repeat('🂧🂨🂩🂪🂫🂭🂮🂡🂷🂸🂹🂺🂻🂽🂾🂱🃇🃈🃉🃊🃋🃍🃎🃁🃗🃘🃙🃚🃛🃝🃞🃑', 7);
        yield [
            'value'     => $value,
            'truncated' => $value,
            'length'    => 224,
        ];

        // 500 4-bytes chars
        // truncated string should contains 255 4-bytes chars
        yield [
            'value'     => str_repeat('🂡🂢🂣🂤🂥', 100),
            'truncated' => str_repeat('🂡🂢🂣🂤🂥', 51), // 5 * 51 = 255
            'length'    => 500,
        ];
    }

    /**
     * @dataProvider textValueProvider
     */
    public function testTextValueTuncation(string $value, string $truncated, int $length)
    {
        $computer = new \Computer();

        $this->when(
            function () use ($computer, $value) {
                $this->integer($computer->add(['name' => $value, 'entities_id' => 0]))->isGreaterThan(0);
            }
        )->error()
            ->withType(E_USER_WARNING)
            ->withMessage(sprintf('%s exceed 255 characters long (%s), it will be truncated.', $value, $length))
            ->{($value !== $truncated ? 'exists' : 'notExists')};

        $this->string($computer->fields['name'])->isEqualTo($truncated);
    }

    public function testCheckUnicity()
    {
        $this->login();

        $field_unicity = new \FieldUnicity();
        $this->integer($field_unicity->add([
            'name' => 'uuid uniqueness',
            'itemtype' => 'Computer',
            '_fields' => ['uuid'],
            'is_active' => 1,
            'action_refuse' => 1,
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
        ]))->isGreaterThan(0);

        $computer = new \Computer();
        $this->integer($computers_id1 = $computer->add([
            'name' => __FUNCTION__ . '01',
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            'uuid' => '76873749-0813-482f-ac20-eb7102ed3367'
        ]))->isGreaterThan(0);

        $this->integer($computers_id2 = $computer->add([
            'name' => __FUNCTION__ . '02',
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            'uuid' => '81fb7b20-a404-4d1e-aafa-4255b7614eae'
        ]))->isGreaterThan(0);

        $this->variable($computer->update([
            'id' => $computers_id2,
            'uuid' => '76873749-0813-482f-ac20-eb7102ed3367'
        ]))->isNotTrue();

        $err_msg = "Impossible record for UUID = 76873749-0813-482f-ac20-eb7102ed3367<br>Other item exist<br>[<a  href='/glpi/front/computer.form.php?id=" . $computers_id1 . "'  title=\"testCheckUnicity01\">testCheckUnicity01</a> - ID: {$computers_id1} - Serial number:  - Entity: Root entity &#62; _test_root_entity]";
        $this->hasSessionMessages(1, [$err_msg]);

        $this->variable($computer->add([
            'name' => __FUNCTION__ . '03',
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            'uuid' => '76873749-0813-482f-ac20-eb7102ed3367'
        ]))->isNotTrue();

        $this->hasSessionMessages(1, [$err_msg]);
    }
}
