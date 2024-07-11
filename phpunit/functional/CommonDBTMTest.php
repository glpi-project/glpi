<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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
use Computer;
use Document;
use Document_Item;
use Entity;
use Glpi\Toolbox\Sanitizer;
use Monolog\Logger;
use SoftwareVersion;

/* Test for inc/commondbtm.class.php */

class CommonDBTMTest extends DbTestCase
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
        $this->assertGreaterThan(0, $ne_id);

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

        $this->assertGreaterThan(0, $port1);
        $this->assertGreaterThan(0, $port2);
        $this->assertGreaterThan(0, $port3);
        $this->assertGreaterThan(0, $port4);
        $this->assertGreaterThan(0, $port5);

       // add an aggregate port use port 3 and 4
        $aggport = (int)$networkportaggregate->add([
            'networkports_id' => $port5,
            'networkports_id_list' => [$port3, $port4],
        ]);

        $this->assertGreaterThan(0, $aggport);
       // Try update to use 2 and 4
        $this->assertTrue($networkportaggregate->update([
            'networkports_id' => $port5,
            'networkports_id_list' => [$port2, $port4],
        ]));

       // Try update with id not exist, it will return false
        $this->assertFalse($networkportaggregate->update([
            'networkports_id' => $port3,
            'networkports_id_list' => [$port2, $port4],
        ]));
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
        $this->assertFalse($instance->isNewItem());

        $instance = new \Computer();
        $result = $instance->getFromDbByRequest([
            'WHERE' => ['contact' => 'johndoe'],
        ]);
        $this->hasPhpLogRecordThatContains(
            'getFromDBByRequest expects to get one result, 2 found in query "SELECT `glpi_computers`.* FROM `glpi_computers` WHERE `contact` = \'johndoe\'".',
            Logger::WARNING
        );
        $this->assertFalse($result);
        // the instance must not be populated
        $this->assertTrue($instance->isNewItem());
    }

    public function testGetFromResultSet()
    {
        global $DB;
        $result = $DB->request([
            'FROM'   => \Computer::getTable(),
            'LIMIT'  => 1
        ])->current();

        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('uuid', $result);

        $computer = new \Computer();
        $computer->getFromResultSet($result);
        $this->assertSame($result, $computer->fields);
    }

    public function testGetId()
    {
        $comp = new \Computer();

        $this->assertSame(-1, $comp->getID());

        $this->assertTrue($comp->getFromDBByCrit(['name' => '_test_pc01']));
        $this->assertGreaterThan(0, $comp->getID());
    }

    public function testGetEmpty()
    {
        $comp = new \Computer();

        $this->assertEmpty($comp->fields);

        $this->assertTrue($comp->getEmpty());
        $this->assertSame(0, $comp->fields['entities_id']);

        $_SESSION["glpiactive_entity"] = 12;
        $this->assertTrue($comp->getEmpty());
        unset($_SESSION['glpiactive_entity']);
        $this->assertSame(12, $comp->fields['entities_id']);
    }

    /**
     * Provider for self::testGetTable().
     *
     * @return array
     */
    public static function getTableProvider()
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
        $this->assertSame($tablename, $classname::getTable());
        $this->assertSame($tablename, \CommonDBTM::getTable($classname));
    }

    /**
     * Test CommonDBTM::getTableField() method.
     *
     * @return void
     */
    public function testGetTableField()
    {
        // Base case
        $this->assertSame('glpi_computers.serial', \Computer::getTableField('serial'));
        $this->assertSame('glpi_computers.serial', \CommonDBTM::getTableField('serial', \Computer::class));

        // Wildcard case
        $this->assertSame('glpi_configs.*', \Config::getTableField('*'));
        $this->assertSame('glpi_configs.*', \CommonDBTM::getTableField('*', \Config::class));
    }

    /**
     * Test CommonDBTM::getTableField() method.
     *
     * @return void
     */
    public function testGetTableFieldEmpty()
    {
        // Exception if field argument is empty
        $this->expectExceptionMessage('Argument $field cannot be empty.');
        \Computer::getTableField('');
    }

    /**
     * Test CommonDBTM::getTableField() method.
     *
     * @return void
     */
    public function testGetTableFieldNoTable()
    {
        // Exception if class has no table
        $this->expectExceptionMessage('Invalid table name.');
        \Item_Devices::getTableField('id');
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
        $this->assertGreaterThan(0, $res);

        $check = $DB->request([
            'FROM'   => \Computer::getTable(),
            'WHERE'  => ['name' => 'serial-to-change']
        ])->current();
        $this->assertSame('serial-one', $check['serial']);

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
        $this->assertTrue($res);

        $check = $DB->request([
            'FROM'   => \Computer::getTable(),
            'WHERE'  => ['name' => 'serial-to-change']
        ])->current();
        $this->assertSame('serial-changed', $check['serial']);

        $this->assertGreaterThan(
            0,
            (int)$DB->insert(
                \Computer::getTable(),
                ['name' => 'serial-to-change']
            )
        );

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
        $this->assertTrue($res);

        //multiple update case
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
        $this->assertFalse($res);
        $this->hasPhpLogRecordThatContains(
            'Update would change too many rows!',
            Logger::WARNING
        );
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
        $this->assertGreaterThan(0, $res);

        $check = $DB->request([
            'FROM'   => \Computer::getTable(),
            'WHERE'  => ['name' => 'serial-to-change']
        ])->current();
        $this->assertSame('serial-one', $check['serial']);

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
        $this->assertTrue($res);

        $check = $DB->request([
            'FROM'   => \Computer::getTable(),
            'WHERE'  => ['name' => 'serial-to-change']
        ])->current();
        $this->assertSame('serial-changed', $check['serial']);

        $this->assertGreaterThan(
            0,
            (int)$DB->insert(
                \Computer::getTable(),
                ['name' => 'serial-to-change']
            )
        );

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
        $this->assertTrue($res);

        //multiple update case
        $res = $DB->updateOrInsert(
            \Computer::getTable(),
            [
                'serial' => 'serial-changed'
            ],
            [
                'name'   => 'serial-to-change'
            ]
        );
        $this->assertFalse($res);
        $this->hasPhpLogRecordThatContains(
            'Update would change too many rows!',
            Logger::WARNING
        );
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
        $this->assertGreaterThan(0, $id[0]);

        $id[1] = (int)$printer->add([
            'name'         => "Printer 2",
            'entities_id'  => $ent0,
            'is_recursive' => 1
        ]);
        $this->assertGreaterThan(0, $id[1]);

        $id[2] = (int)$printer->add([
            'name'         => "Printer 3",
            'entities_id'  => $ent1,
            'is_recursive' => 1
        ]);
        $this->assertGreaterThan(0, $id[2]);

        $id[3] = (int)$printer->add([
            'name'         => "Printer 4",
            'entities_id'  => $ent2
        ]);
        $this->assertGreaterThan(0, $id[3]);

        // Super admin
        $this->login('glpi', 'glpi');
        $this->assertEquals(4, $_SESSION['glpiactiveprofile']['id']);
        $this->assertEquals(255, $_SESSION['glpiactiveprofile']['printer']);

        // See all
        $this->assertTrue(\Session::changeActiveEntities('all'));

        $this->assertTrue($printer->can($id[0], READ), "Fail can read Printer 1");
        $this->assertTrue($printer->can($id[1], READ), "Fail can read Printer 2");
        $this->assertTrue($printer->can($id[2], READ), "Fail can read Printer 3");
        $this->assertTrue($printer->can($id[3], READ), "Fail can read Printer 4");

        $this->assertTrue($printer->canEdit($id[0]), "Fail can write Printer 1");
        $this->assertTrue($printer->canEdit($id[1]), "Fail can write Printer 2");
        $this->assertTrue($printer->canEdit($id[2]), "Fail can write Printer 3");
        $this->assertTrue($printer->canEdit($id[3]), "Fail can write Printer 4");

        // See only in main entity
        $this->assertTrue(\Session::changeActiveEntities($ent0));

        $this->assertTrue($printer->can($id[0], READ), "Fail can read Printer 1");
        $this->assertTrue($printer->can($id[1], READ), "Fail can read Printer 2");
        $this->assertFalse($printer->can($id[2], READ), "Fail can't read Printer 3");
        $this->assertFalse($printer->can($id[3], READ), "Fail can't read Printer 1");

        $this->assertTrue($printer->canEdit($id[0]), "Fail can write Printer 1");
        $this->assertTrue($printer->canEdit($id[1]), "Fail can write Printer 2");
        $this->assertFalse($printer->canEdit($id[2]), "Fail can't write Printer 1");
        $this->assertFalse($printer->canEdit($id[3]), "Fail can't write Printer 1");

        // See only in child entity 1 + parent if recursive
        $this->assertTrue(\Session::changeActiveEntities($ent1));

        $this->assertFalse($printer->can($id[0], READ), "Fail can't read Printer 1");
        $this->assertTrue($printer->can($id[1], READ), "Fail can read Printer 2");
        $this->assertTrue($printer->can($id[2], READ), "Fail can read Printer 3");
        $this->assertFalse($printer->can($id[3], READ), "Fail can't read Printer 4");

        $this->assertFalse($printer->canEdit($id[0]), "Fail can't write Printer 1");
        $this->assertFalse($printer->canEdit($id[1]), "Fail can't write Printer 2");
        $this->assertTrue($printer->canEdit($id[2]), "Fail can write Printer 2");
        $this->assertFalse($printer->canEdit($id[3]), "Fail can't write Printer 2");

        // See only in child entity 2 + parent if recursive
        $this->assertTrue(\Session::changeActiveEntities($ent2));

        $this->assertFalse($printer->can($id[0], READ), "Fail can't read Printer 1");
        $this->assertTrue($printer->can($id[1], READ), "Fail can read Printer 2");
        $this->assertFalse($printer->can($id[2], READ), "Fail can't read Printer 3");
        $this->assertTrue($printer->can($id[3], READ), "Fail can read Printer 4");

        $this->assertFalse($printer->canEdit($id[0]), "Fail can't write Printer 1");
        $this->assertFalse($printer->canEdit($id[1]), "Fail can't write Printer 2");
        $this->assertFalse($printer->canEdit($id[2]), "Fail can't write Printer 3");
        $this->assertTrue($printer->canEdit($id[3]), "Fail can write Printer 4");
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
        $this->assertEquals(4, $_SESSION['glpiactiveprofile']['id']);
        $this->assertEquals(255, $_SESSION['glpiactiveprofile']['contact_enterprise']);

       // See all
        $this->assertTrue(\Session::changeActiveEntities('all'));

       // Create some contacts
        $contact = new \Contact();

        $idc[0] = (int)$contact->add([
            'name'         => "Contact 1",
            'entities_id'  => $ent0,
            'is_recursive' => 0
        ]);
        $this->assertGreaterThan(0, $idc[0]);

        $idc[1] = (int)$contact->add([
            'name'         => "Contact 2",
            'entities_id'  => $ent0,
            'is_recursive' => 1
        ]);
        $this->assertGreaterThan(0, $idc[1]);

        $idc[2] = (int)$contact->add([
            'name'         => "Contact 3",
            'entities_id'  => $ent1,
            'is_recursive' => 1
        ]);
        $this->assertGreaterThan(0, $idc[2]);

        $idc[3] = (int)$contact->add([
            'name'         => "Contact 4",
            'entities_id'  => $ent2
        ]);
        $this->assertGreaterThan(0, $idc[3]);
        ;

       // Create some suppliers
        $supplier = new \Supplier();

        $ids[0] = (int)$supplier->add([
            'name'         => "Supplier 1",
            'entities_id'  => $ent0,
            'is_recursive' => 0
        ]);
        $this->assertGreaterThan(0, $ids[0]);

        $ids[1] = (int)$supplier->add([
            'name'         => "Supplier 2",
            'entities_id'  => $ent0,
            'is_recursive' => 1
        ]);
        $this->assertGreaterThan(0, $ids[1]);

        $ids[2] = (int)$supplier->add([
            'name'         => "Supplier 3",
            'entities_id'  => $ent1
        ]);
        $this->assertGreaterThan(0, $ids[2]);

        $ids[3] = (int)$supplier->add([
            'name'         => "Supplier 4",
            'entities_id'  => $ent2
        ]);
        $this->assertGreaterThan(0, $ids[3]);

       // Relation
        $rel = new \Contact_Supplier();
        $input = [
            'contacts_id' =>  $idc[0], // root
            'suppliers_id' => $ids[0]  //root
        ];
        $this->assertTrue($rel->can(-1, CREATE, $input));

        $idr[0] = (int)$rel->add($input);
        $this->assertGreaterThan(0, $idr[0]);
        $this->assertTrue($rel->can($idr[0], READ));
        $this->assertTrue($rel->canEdit($idr[0]));

        $input = [
            'contacts_id' =>  $idc[0], // root
            'suppliers_id' => $ids[1]  // root + rec
        ];
        $this->assertTrue($rel->can(-1, CREATE, $input));
        $idr[1] = (int)$rel->add($input);
        $this->assertGreaterThan(0, $idr[1]);
        $this->assertTrue($rel->can($idr[1], READ));
        $this->assertTrue($rel->canEdit($idr[1]));

        $input = [
            'contacts_id' =>  $idc[0], // root
            'suppliers_id' => $ids[2]  // child 1
        ];
        $this->assertFalse($rel->can(-1, CREATE, $input));

        $input = [
            'contacts_id' =>  $idc[0], // root
            'suppliers_id' => $ids[3]  // child 2
        ];
        $this->assertFalse($rel->can(-1, CREATE, $input));

        $input = [
            'contacts_id' =>  $idc[1], // root + rec
            'suppliers_id' => $ids[0]  // root
        ];
        $this->assertTrue($rel->can(-1, CREATE, $input));
        $idr[2] = (int)$rel->add($input);
        $this->assertGreaterThan(0, $idr[2]);
        $this->assertTrue($rel->can($idr[2], READ));
        $this->assertTrue($rel->canEdit($idr[2]));

        $input = [
            'contacts_id' =>  $idc[1], // root + rec
            'suppliers_id' => $ids[1]  // root + rec
        ];
        $this->assertTrue($rel->can(-1, CREATE, $input));
        $idr[3] = (int)$rel->add($input);
        $this->assertGreaterThan(0, $idr[3]);
        $this->assertTrue($rel->can($idr[3], READ));
        $this->assertTrue($rel->canEdit($idr[3]));

        $input = [
            'contacts_id' =>  $idc[1], // root + rec
            'suppliers_id' => $ids[2]  // child 1
        ];
        $this->assertTrue($rel->can(-1, CREATE, $input));
        $idr[4] = (int)$rel->add($input);
        $this->assertGreaterThan(0, $idr[4]);
        $this->assertTrue($rel->can($idr[4], READ));
        $this->assertTrue($rel->canEdit($idr[4]));

        $input = [
            'contacts_id' =>  $idc[1], // root + rec
            'suppliers_id' => $ids[3]  // child 2
        ];
        $this->assertTrue($rel->can(-1, CREATE, $input));
        $idr[5] = (int)$rel->add($input);
        $this->assertGreaterThan(0, $idr[5]);
        $this->assertTrue($rel->can($idr[5], READ));
        $this->assertTrue($rel->canEdit($idr[5]));

        $input = [
            'contacts_id' =>  $idc[2], // Child 1
            'suppliers_id' => $ids[0]  // root
        ];
        $this->assertFalse($rel->can(-1, CREATE, $input));

        $input = [
            'contacts_id' =>  $idc[2], // Child 1
            'suppliers_id' => $ids[1]  // root + rec
        ];
        $this->assertTrue($rel->can(-1, CREATE, $input));
        $idr[6] = (int)$rel->add($input);
        $this->assertGreaterThan(0, $idr[6]);
        $this->assertTrue($rel->can($idr[6], READ));
        $this->assertTrue($rel->canEdit($idr[6]));

        $input = [
            'contacts_id' =>  $idc[2], // Child 1
            'suppliers_id' => $ids[2]  // Child 1
        ];
        $this->assertTrue($rel->can(-1, CREATE, $input));
        $idr[7] = (int)$rel->add($input);
        $this->assertGreaterThan(0, $idr[7]);
        $this->assertTrue($rel->can($idr[7], READ));
        $this->assertTrue($rel->canEdit($idr[7]));

        $input = [
            'contacts_id' =>  $idc[2], // Child 1
            'suppliers_id' => $ids[3]  // Child 2
        ];
        $this->assertFalse($rel->can(-1, CREATE, $input));

        // See only in child entity 2 + parent if recursive
        $this->assertTrue(\Session::changeActiveEntities($ent2));

        $this->assertFalse($rel->can($idr[0], READ));  // root / root
        //$this->assertFalse($rel->canEdit($idr[0]));
        $this->assertFalse($rel->can($idr[1], READ));  // root / root rec
        //$this->assertFalse($rel->canEdit($idr[1]));
        $this->assertFalse($rel->can($idr[2], READ));  // root rec / root
        //$this->assertFalse($rel->canEdit($idr[2]));
        $this->assertTrue($rel->can($idr[3], READ));   // root rec / root rec
        //$this->assertFalse($rel->canEdit($idr[3]));
        $this->assertFalse($rel->can($idr[4], READ));  // root rec / child 1
        //$this->assertFalse($rel->canEdit($idr[4]));
        $this->assertTrue($rel->can($idr[5], READ));   // root rec / child 2
        $this->assertTrue($rel->canEdit($idr[5]));
        $this->assertFalse($rel->can($idr[6], READ));  // child 1 / root rec
        //$this->assertFalse($rel->canEdit($idr[6]));
        $this->assertFalse($rel->can($idr[7], READ));  // child 1 / child 1
        //$this->assertFalse($rel->canEdit($idr[7]));

        $input = [
            'contacts_id' =>  $idc[0], // root
            'suppliers_id' => $ids[0]  // root
        ];
        $this->assertFalse($rel->can(-1, CREATE, $input));

        $input = [
            'contacts_id'  =>  $idc[0],// root
            'suppliers_id' => $ids[1]  // root + rec
        ];
        $this->assertFalse($rel->can(-1, CREATE, $input));

        $input = [
            'contacts_id'  =>  $idc[1],// root + rec
            'suppliers_id' => $ids[0]  // root
        ];
        $this->assertFalse($rel->can(-1, CREATE, $input));

        $input = [
            'contacts_id' =>  $idc[3], // Child 2
            'suppliers_id' => $ids[0]  // root
        ];
        $this->assertFalse($rel->can(-1, CREATE, $input));

        $input = [
            'contacts_id'  =>  $idc[3],// Child 2
            'suppliers_id' => $ids[1]  // root + rec
        ];
        $this->assertTrue($rel->can(-1, CREATE, $input));
        $idr[7] = (int)$rel->add($input);
        $this->assertGreaterThan(0, $idr[7]);
        $this->assertTrue($rel->can($idr[7], READ));
        $this->assertTrue($rel->canEdit($idr[7]));

        $input = [
            'contacts_id' =>  $idc[3], // Child 2
            'suppliers_id' => $ids[2]  // Child 1
        ];
        $this->assertFalse($rel->can(-1, CREATE, $input));

        $input = [
            'contacts_id' =>  $idc[3], // Child 2
            'suppliers_id' => $ids[3]  // Child 2
        ];
        $this->assertTrue($rel->can(-1, CREATE, $input));
        $idr[8] = (int)$rel->add($input);
        $this->assertGreaterThan(0, $idr[8]);
        $this->assertTrue($rel->can($idr[8], READ));
        $this->assertTrue($rel->canEdit($idr[8]));
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
        $this->assertGreaterThan(0, $ent3);

        $ent4 = (int)$entity->add([
            'name'         => '_test_child_2_subchild_2',
            'entities_id'  => $ent2
        ]);
        $this->assertGreaterThan(0, $ent4);

        $this->assertTrue(\Session::changeActiveEntities('all'));

        $this->assertTrue($entity->can(0, READ), "Fail: can't read root entity");
        $this->assertTrue($entity->can($ent0, READ), "Fail: can't read entity 0");
        $this->assertTrue($entity->can($ent1, READ), "Fail: can't read entity 1");
        $this->assertTrue($entity->can($ent2, READ), "Fail: can't read entity 2");
        $this->assertTrue($entity->can($ent3, READ), "Fail: can't read entity 2.1");
        $this->assertTrue($entity->can($ent4, READ), "Fail: can't read entity 2.2");
        $this->assertFalse($entity->can(99999, READ), "Fail: can read not existing entity");

        $this->assertTrue($entity->canEdit(0), "Fail: can't write root entity");
        $this->assertTrue($entity->canEdit($ent0), "Fail: can't write entity 0");
        $this->assertTrue($entity->canEdit($ent1), "Fail: can't write entity 1");
        $this->assertTrue($entity->canEdit($ent2), "Fail: can't write entity 2");
        $this->assertTrue($entity->canEdit($ent3), "Fail: can't write entity 2.1");
        $this->assertTrue($entity->canEdit($ent4), "Fail: can't write entity 2.2");
        $this->assertFalse($entity->canEdit(99999), "Fail: can write not existing entity");

        $input = ['entities_id' => $ent1];
        $this->assertTrue($entity->can(-1, CREATE, $input), "Fail: can create entity in root");
        $input = ['entities_id' => $ent2];
        $this->assertTrue($entity->can(-1, CREATE, $input), "Fail: can't create entity in 2");
        $input = ['entities_id' => $ent3];
        $this->assertTrue($entity->can(-1, CREATE, $input), "Fail: can't create entity in 2.1");
        $input = ['entities_id' => 99999];
        $this->assertFalse($entity->can(-1, CREATE, $input), "Fail: can create entity in not existing entity");
        $input = ['entities_id' => -1];
        $this->assertFalse($entity->can(-1, CREATE, $input), "Fail: can create entity in not existing entity");

        $this->assertTrue(\Session::changeActiveEntities($ent2, false));
        $input = ['entities_id' => $ent1];
        $this->assertFalse($entity->can(-1, CREATE, $input), "Fail: can create entity in root");
        $input = ['entities_id' => $ent2];
        // next should be false (or not).... but check is done on glpiactiveprofile
        // will require to save current state in session - this is probably acceptable
        // this allow creation when no child defined yet (no way to select tree in this case)
        $this->assertTrue($entity->can(-1, CREATE, $input), "Fail: can't create entity in 2");
        $input = ['entities_id' => $ent3];
        $this->assertFalse($entity->can(-1, CREATE, $input), "Fail: can create entity in 2.1");
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
        $this->assertSame("Computer01 '", $computer->fields['name']);

        $this->assertGreaterThan(0, $computerID);
        $this->assertTrue(
            $computer->getFromDB($computerID)
        );
        // Verify you can override creation and modifcation dates from add
        $this->assertEquals('2018-01-01 11:22:33', $computer->fields['date_creation']);
        $this->assertEquals('2018-01-01 22:33:44', $computer->fields['date_mod']);
        $this->assertSame("Computer01 '", $computer->fields['name']);

        //test with default date
        $computerID = $computer->add(\Toolbox::addslashes_deep([
            'name'            => 'Computer01 \'',
            'entities_id'     => $ent0
        ]));
        $this->assertSame("Computer01 '", $computer->fields['name']);

        $this->assertGreaterThan(0, $computerID);
        $this->assertTrue(
            $computer->getFromDB($computerID)
        );
        // Verify default date has been used
        $this->assertEquals('2000-01-01 00:00:00', $computer->fields['date_creation']);
        $this->assertEquals('2000-01-01 00:00:00', $computer->fields['date_mod']);
        $this->assertSame("Computer01 '", $computer->fields['name']);

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
        $this->assertSame("Computer01", $computer->fields['name']);

        $this->assertGreaterThan(0, $computerID);
        $this->assertTrue(
            $computer->getFromDB($computerID)
        );
        $this->assertSame("Computer01", $computer->fields['name']);

        $this->assertTrue(
            $computer->update(['id' => $computerID, 'name' => \Toolbox::addslashes_deep('Computer01 \'')])
        );
        $this->assertSame('Computer01 \'', $computer->fields['name']);
        $this->assertTrue($computer->getFromDB($computerID));
        $this->assertSame('Computer01 \'', $computer->fields['name']);

        $this->assertTrue(
            $computer->update(['id' => $computerID, 'name' => null])
        );
        $this->assertNull($computer->fields['name']);
        $this->assertTrue($computer->getFromDB($computerID));
        $this->assertNull($computer->fields['name']);

        $this->assertTrue(
            $computer->update(['id' => $computerID, 'name' => 'renamed'])
        );
        $this->assertSame('renamed', $computer->fields['name']);
        $this->assertTrue($computer->getFromDB($computerID));
        $this->assertSame('renamed', $computer->fields['name']);
    }

    public function testTimezones()
    {
        global $DB;

        //check if timezones are available
        $this->assertTrue($DB->use_timezones);
        $this->assertGreaterThan(0, count($DB->getTimezones()));

        //login with default TZ
        $this->login();
        //add a Computer with creation and update dates
        $comp = new \Computer();
        $cid = $comp->add([
            'name'            => 'Computer with timezone',
            'date_creation'   => '2019-03-04 10:00:00',
            'date_mod'        => '2019-03-04 10:00:00',
            'entities_id'     => 0
        ]);
        $this->assertGreaterThan(0, $cid);

        $this->assertTrue($comp->getFromDB($cid));
        $this->assertSame('2019-03-04 10:00:00', $comp->fields['date_creation']);

        $user = getItemByTypeName('User', 'glpi');
        $this->assertTrue($user->update(['id' => $user->fields['id'], 'timezone' => 'Europe/Paris']));

       //check tz is set
        $this->assertTrue($user->getFromDB($user->fields['id']));
        $this->assertSame('Europe/Paris', $user->fields['timezone']);

        $this->login('glpi', 'glpi');
        $this->assertTrue($comp->getFromDB($cid));
        $this->assertMatchesRegularExpression('/2019-03-04 1[12]:00:00/', $comp->fields['date_creation']);
    }

    public function testCircularRelation()
    {
        $project = new \Project();
        $project_id_1 = $project->add([
            'name' => 'Project 1',
            'auto_percent_done' => 1
        ]);
        $this->assertGreaterThan(0, (int) $project_id_1);
        $project_id_2 = $project->add([
            'name' => 'Project 2',
            'auto_percent_done' => 1,
            'projects_id' => $project_id_1
        ]);
        $this->assertGreaterThan(0, (int) $project_id_2);
        $project_id_3 = $project->add([
            'name' => 'Project 3',
            'projects_id' => $project_id_2
        ]);
        $this->assertGreaterThan(0, (int) $project_id_3);
        $project_id_4 = $project->add([
            'name' => 'Project 4',
        ]);
        $this->assertGreaterThan(0, (int) $project_id_4);

        // This should evaluate as a circular relation
        $this->assertTrue(\Project::checkCircularRelation($project_id_1, $project_id_3));
        // This should not evaluate as a circular relation
        $this->assertFalse(\Project::checkCircularRelation($project_id_4, $project_id_3));
    }

    public static function relationConfigProvider()
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
        /** @var array $CFG_GLPI */
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
            $this->assertGreaterThan(0, $linked_item_id);
            $linked_item_input = [$linked_item->getForeignKeyField() => $linked_item_id];
        }

        // Create computer for which cleaning will be done.
        $computer_1_id = $computer->add(
            [
                'name'        => 'Computer 1',
                'entities_id' => $entity_id,
            ]
        );
        $this->assertGreaterThan(0, $computer_1_id);
        $relation_item_1_id = $relation_item->add(
            [
                'itemtype' => $computer->getType(),
                'items_id' => $computer_1_id,
            ] + $linked_item_input
        );
        $this->assertGreaterThan(0, $relation_item_1_id);
        $this->assertTrue($relation_item->getFromDB($relation_item_1_id));

        // Create witness computer.
        $computer_2_id = $computer->add(
            [
                'name'        => 'Computer 2',
                'entities_id' => $entity_id,
            ]
        );
        $this->assertGreaterThan(0, $computer_2_id);
        $relation_item_2_id = $relation_item->add(
            [
                'itemtype' => $computer->getType(),
                'items_id' => $computer_2_id,
            ] + $linked_item_input
        );
        $this->assertGreaterThan(0, $relation_item_2_id);
        $this->assertTrue($relation_item->getFromDB($relation_item_2_id));

        $cfg_backup = $CFG_GLPI;
        $CFG_GLPI[$config_name] = [$computer->getType()];
        $computer->delete(['id' => $computer_1_id], true);
        $CFG_GLPI = $cfg_backup;

        // Relation with deleted item has been cleaned
        $this->assertFalse($relation_item->getFromDB($relation_item_1_id));
        // Relation with witness object is still present
        $this->assertTrue($relation_item->getFromDB($relation_item_2_id));
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
        $this->assertSame(
            6,
            countElementsInTable(\Item_DeviceBattery::getTable())
        );
        $this->assertSame(
            2,
            countElementsInTable(\Item_DeviceBattery::getTable(), ['itemtype' => \Computer::class, 'items_id' => $computer_id_1])
        );
        $this->assertEquals(
            2,
            countElementsInTable(\Item_DeviceBattery::getTable(), ['itemtype' => \Computer::class, 'items_id' => $computer_id_2])
        );
        $this->assertEquals(
            2,
            countElementsInTable(\Item_DeviceBattery::getTable(), ['itemtype' => \Phone::class, 'items_id' => $phone_id])
        );

        $computer_1->delete(['id' => $computer_id_1, 'keep_devices' => 1], true);

        // Check that only relations to computer were cleaned
        $this->assertEquals(
            6,
            countElementsInTable(\Item_DeviceBattery::getTable())
        ); // item devices were preserved but detached
        $this->assertEquals(
            0,
            countElementsInTable(\Item_DeviceBattery::getTable(), ['itemtype' => \Computer::class, 'items_id' => $computer_id_1])
        );
        $this->assertEquals(
            2,
            countElementsInTable(\Item_DeviceBattery::getTable(), ['itemtype' => '', 'items_id' => 0])
        );
        $this->assertEquals(
            2,
            countElementsInTable(\Item_DeviceBattery::getTable(), ['itemtype' => \Computer::class, 'items_id' => $computer_id_2])
        );
        $this->assertEquals(
            2,
            countElementsInTable(\Item_DeviceBattery::getTable(), ['itemtype' => \Phone::class, 'items_id' => $phone_id])
        );

        $computer_2->delete(['id' => $computer_id_2], true);
        // Check that only relations to computer were cleaned
        $this->assertEquals(
            4,
            countElementsInTable(\Item_DeviceBattery::getTable())
        ); // item devices were deleted
        $this->assertEquals(
            0,
            countElementsInTable(\Item_DeviceBattery::getTable(), ['itemtype' => \Computer::class, 'items_id' => $computer_id_1])
        );
        $this->assertEquals(
            2,
            countElementsInTable(\Item_DeviceBattery::getTable(), ['itemtype' => '', 'items_id' => 0])
        );
        $this->assertEquals(
            0,
            countElementsInTable(\Item_DeviceBattery::getTable(), ['itemtype' => \Computer::class, 'items_id' => $computer_id_2])
        );
        $this->assertEquals(
            2,
            countElementsInTable(\Item_DeviceBattery::getTable(), ['itemtype' => \Phone::class, 'items_id' => $phone_id])
        );
    }


    public static function testCheckTemplateEntityProvider()
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
        $this->assertEquals($expected, $res);

        // Reset session
        unset($_SESSION['glpiactiveentities']);
    }

    public function testGetById()
    {
        $itemtype = \Computer::class;

        // test null ID
        $output = $itemtype::getById(null);
        $this->assertFalse($output);

        // test existing item
        $instance = new $itemtype();
        $instance->getFromDBByRequest([
            'WHERE' => ['name' => '_test_pc01'],
        ]);
        $this->assertFalse($instance->isNewItem());
        $output = $itemtype::getById($instance->getID());
        $this->assertInstanceOf($itemtype, $output);

        // test non-existing item
        $instance = new $itemtype();
        $instance->add([
            'name' => 'to be deleted',
            'entities_id' => 0,
        ]);
        $this->assertFalse($instance->isNewItem());
        $nonExistingId = $instance->getID();
        $instance->delete([
            'id' => $nonExistingId,
        ], 1);
        $this->assertFalse($instance->getFromDB($nonExistingId));

        $output = $itemtype::getById($nonExistingId);
        $this->assertFalse($output);
    }


    public static function textValueProvider(): iterable
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

        $this->assertGreaterThan(0, $computer->add(['name' => $value, 'entities_id' => 0]));
        $this->assertEquals($truncated, $computer->fields['name']);
        if ($value !== $truncated) {
            $this->hasPhpLogRecordThatContains(
                sprintf(
                    '%s exceed 255 characters long (%s), it will be truncated.',
                    $value,
                    $length
                ),
                Logger::WARNING
            );
        }
    }

    public function testCheckUnicity()
    {
        $this->login();

        $field_unicity = new \FieldUnicity();
        $this->assertGreaterThan(
            0,
            $field_unicity->add([
                'name' => 'uuid uniqueness',
                'itemtype' => 'Computer',
                '_fields' => ['uuid'],
                'is_active' => 1,
                'action_refuse' => 1,
                'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            ])
        );

        $computer = new \Computer();
        $this->assertGreaterThan(
            0,
            $computers_id1 = $computer->add([
                'name' => __FUNCTION__ . '01',
                'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
                'uuid' => '76873749-0813-482f-ac20-eb7102ed3367'
            ])
        );

        $this->assertGreaterThan(
            0,
            $computers_id2 = $computer->add([
                'name' => __FUNCTION__ . '02',
                'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
                'uuid' => '81fb7b20-a404-4d1e-aafa-4255b7614eae'
            ])
        );

        $this->assertFalse($computer->update([
            'id' => $computers_id2,
            'uuid' => '76873749-0813-482f-ac20-eb7102ed3367'
        ]));

        $err_msg = "Impossible record for UUID = 76873749-0813-482f-ac20-eb7102ed3367<br>Other item exist<br>[<a  href='/glpi/front/computer.form.php?id=" . $computers_id1 . "'  title=\"testCheckUnicity01\">testCheckUnicity01</a> - ID: {$computers_id1} - Serial number:  - Entity: Root entity &#62; _test_root_entity]";
        $this->hasSessionMessages(1, [$err_msg]);

        $this->assertFalse($computer->add([
            'name' => __FUNCTION__ . '03',
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            'uuid' => '76873749-0813-482f-ac20-eb7102ed3367'
        ]));

        $this->hasSessionMessages(1, [$err_msg]);
    }

    public function testAddFilesWithNewFile()
    {
        // Simulate legit call to `addFiles()` post_addItem / post_updateItem
        $item = getItemByTypeName(Computer::class, '_test_pc01');

        $filename_txt = '65292dc32d6a87.46654965' . 'foo.txt';
        $content = $this->getUniqueString();
        file_put_contents(GLPI_TMP_DIR . '/' . $filename_txt, $content);

        $input = [
            'name' => 'Upload new file',
            '_filename' => [
                0 => $filename_txt,
            ],
            '_tag_filename' => [
                0 => '0bf32119-761764d0-65292dc0770083.87619309',
            ],
            '_prefix_filename' => [
                0 => '65292dc32d6a87.46654965',
            ]
        ];
        $item->input = $input;
        $item->addFiles($input);

        unlink(GLPI_TMP_DIR . '/' . $filename_txt);

        // Check the document exists and is linked to the computer
        $document_item = new Document_Item();
        $this->assertTrue(
            $document_item->getFromDbByCrit(['itemtype' => $item->getType(), 'items_id' => $item->getID()])
        );
        $document = new Document();
        $this->assertTrue(
            $document->getFromDB($document_item->fields['documents_id'])
        );
        $this->assertEquals('foo.txt', $document->fields['filename']);
    }

    public function testAddFilesSimilarToExistingDocument()
    {
        $root_entity_id = getItemByTypeName(Entity::class, '_test_root_entity', true);

        $content = $this->getUniqueString();

        // Create the document
        $filename1_txt = '6079908c4be820.58460925' . 'foo.txt';
        file_put_contents(GLPI_TMP_DIR . '/' . $filename1_txt, $content);

        $document = new Document();
        $init_document_id = $document->add([
            'entities_id' => $root_entity_id,
            'is_recursive' => 0,
            '_only_if_upload_succeed' => 1,
            '_filename' => [
                0 => $filename1_txt,
            ],
            '_prefix_filename' => [
                0 => '6079908c4be820.58460925',
            ]
        ]);
        $this->assertGreaterThan(0, $init_document_id);

        unlink(GLPI_TMP_DIR . '/' . $filename1_txt);

        $this->assertTrue($document->getFromDB($init_document_id));

        // Simulate legit call to `addFiles()` post_addItem / post_updateItem
        $item = getItemByTypeName(Computer::class, '_test_pc01');

        $filename2_txt = '65292dc32d6a87.22222222' . 'bar.txt';
        file_put_contents(GLPI_TMP_DIR . '/' . $filename2_txt, $content);

        $input = [
            'name' => 'Upload new file',
            '_filename' => [
                0 => $filename2_txt,
            ],
            '_tag_filename' => [
                0 => '0bf32119-761764d0-65292dc0770083.87619309',
            ],
            '_prefix_filename' => [
                0 => '65292dc32d6a87.22222222',
            ]
        ];
        $item->input = $input;
        $item->addFiles($input);

        unlink(GLPI_TMP_DIR . '/' . $filename2_txt);

        // Check the document is linked to the computer
        $document_item = new Document_Item();
        $this->assertTrue(
            $document_item->getFromDbByCrit(['itemtype' => $item->getType(), 'items_id' => $item->getID()])
        );

        // Check that first document has been updated
        $document = new Document();
        $this->assertTrue(
            $document->getFromDB($document_item->fields['documents_id'])
        );
        $this->assertEquals($init_document_id, $document->getID());
        $this->assertEquals('bar.txt', $document->fields['filename']);
    }

    public static function updatedInputProvider(): iterable
    {
        $root_entity_id = getItemByTypeName(\Entity::class, '_test_root_entity', true);

        // make sure itemtype change is detected
        yield [
            'itemtype' => \Alert::class,
            'add_input' => [
                'itemtype' => \Glpi\Event::class,
                'items_id' => 1,
            ],
            'update_input' => [
                'itemtype' => \Contract::class,
                'items_id' => 1,
            ],
            'expected_updates' => [
                'itemtype',
            ],
        ];

        // make sure namespaced itemtype is not prone to false positives
        yield [
            'itemtype' => \Alert::class,
            'add_input' => [
                'itemtype' => \Glpi\Event::class,
                'items_id' => 1,
            ],
            'update_input' => [
                'itemtype' => \Glpi\Event::class,
                'items_id' => 1,
            ],
            'expected_updates' => [
            ],
        ];

        // `text` or `string` datatype
        // or `itemlink` datatype on a `name` field
        foreach (['name', 'comment', 'num'] as $fieldname) {
            // null is not considered different from an empty string
            yield [
                'itemtype' => \Contract::class,
                'add_input' => [
                    'entities_id' => $root_entity_id,
                    $fieldname    => null,
                ],
                'update_input' => [
                    $fieldname    => '',
                ],
                'expected_updates' => [
                ],
            ];
            yield [
                'itemtype' => \Contract::class,
                'add_input' => [
                    'entities_id' => $root_entity_id,
                    $fieldname    => '',
                ],
                'update_input' => [
                    $fieldname    => null,
                ],
                'expected_updates' => [
                ],
            ];
            // numeric value is not considered different when its string reprosentation does not differ
            yield [
                'itemtype' => \Contract::class,
                'add_input' => [
                    'entities_id' => $root_entity_id,
                    $fieldname    => 0,
                ],
                'update_input' => [
                    $fieldname    => '0',
                ],
                'expected_updates' => [
                ],
            ];
            // make sure HTML text value change is not prone to false positives
            yield [
                'itemtype' => \Contract::class,
                'add_input' => [
                    'entities_id' => $root_entity_id,
                    $fieldname    => '<p>test \' with quote</p>',
                ],
                'update_input' => [
                    $fieldname    => '<p>test \' with quote</p>',
                ],
                'expected_updates' => [
                ],
            ];
            // make sure text value change is detected
            yield [
                'itemtype' => \Contract::class,
                'add_input' => [
                    'entities_id' => $root_entity_id,
                    $fieldname    => 'init value',
                ],
                'update_input' => [
                    $fieldname    => 'updated value',
                ],
                'expected_updates' => [
                    $fieldname,
                    'date_mod', // date_mod is automatically added
                ],
            ];
            // make sure HTML text value change is detected
            yield [
                'itemtype' => \Contract::class,
                'add_input' => [
                    'entities_id' => $root_entity_id,
                    $fieldname    => '<p>test \' with quote</p>',
                ],
                'update_input' => [
                    $fieldname    => '<p>updated text</p>',
                ],
                'expected_updates' => [
                    $fieldname,
                    'date_mod', // date_mod is automatically added
                ],
            ];
            // make sure numeric value change is detected
            yield [
                'itemtype' => \Contract::class,
                'add_input' => [
                    'entities_id' => $root_entity_id,
                    $fieldname    => 152,
                ],
                'update_input' => [
                    $fieldname    => 459,
                ],
                'expected_updates' => [
                    $fieldname,
                    'date_mod', // date_mod is automatically added
                ],
            ];
        }

        // `number` datatype
        yield [
            'itemtype' => \Contract::class,
            'add_input' => [
                'entities_id' => $root_entity_id,
                'duration'    => 24,
            ],
            'update_input' => [
                'duration'    => 24,
            ],
            'expected_updates' => [
            ],
        ];
        yield [
            'itemtype' => \Contract::class,
            'add_input' => [
                'entities_id' => $root_entity_id,
                'duration'    => 12,
            ],
            'update_input' => [
                'duration'    => 24,
            ],
            'expected_updates' => [
                'duration',
                'date_mod', // date_mod is automatically added
            ],
        ];

        // `email` datatype
        yield [
            'itemtype' => \Contact::class,
            'add_input' => [
                'entities_id' => $root_entity_id,
                'email'    => 'test@domain.tld',
            ],
            'update_input' => [
                'email'    => 'test@domain.tld',
            ],
            'expected_updates' => [
            ],
        ];
        yield [
            'itemtype' => \Contact::class,
            'add_input' => [
                'entities_id' => $root_entity_id,
                'email'    => 'test@domain.tld',
            ],
            'update_input' => [
                'email'    => 'no-reply@domain.tld',
            ],
            'expected_updates' => [
                'email',
                'date_mod', // date_mod is automatically added
            ],
        ];
    }

    /**
     * @dataProvider updatedInputProvider
     */
    public function testUpdatedFields(string $itemtype, array $add_input, array $update_input, array $expected_updates): void
    {
        $item = new $itemtype();

        $item_id = $item->add(Sanitizer::sanitize($add_input));
        $this->assertGreaterThan(0, $item_id);

        $updated = $item->update(['id' => $item_id] + Sanitizer::sanitize($update_input));
        $this->assertTrue($updated, 0);

        sort($item->updates);
        sort($expected_updates);
        $this->assertEquals($expected_updates, $item->updates);
    }
}
