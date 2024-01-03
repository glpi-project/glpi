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

/* Test for inc/computer.class.php */

class Computer extends DbTestCase
{
    protected function getUniqueString()
    {
        $string = parent::getUniqueString();
        $string .= "with a ' inside!";
        return $string;
    }

    private function getNewComputer(): \Computer
    {
        $computer = getItemByTypeName('Computer', '_test_pc01');
        $fields   = $computer->fields;
        unset($fields['id']);
        unset($fields['date_creation']);
        unset($fields['date_mod']);
        $fields['name'] = $this->getUniqueString();
        $this->integer((int)$computer->add(\Toolbox::addslashes_deep($fields)))->isGreaterThan(0);
        return $computer;
    }

    private function getNewPrinter()
    {
        $printer  = getItemByTypeName('Printer', '_test_printer_all');
        $pfields  = $printer->fields;
        unset($pfields['id']);
        unset($pfields['date_creation']);
        unset($pfields['date_mod']);
        $pfields['name'] = $this->getUniqueString();
        $this->integer((int)$printer->add(\Toolbox::addslashes_deep($pfields)))->isGreaterThan(0);
        return $printer;
    }

    public function testUpdate()
    {
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
        $this->boolean($computer->update(\Toolbox::addslashes_deep($in)))->isTrue();
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
        $this->boolean($computer->update(\Toolbox::addslashes_deep($in2)))->isTrue();
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
                'states_id'             => $computer->getField('states_id'),
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
    public function testCreateLinks()
    {
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
        $this->boolean($computer->update(\Toolbox::addslashes_deep($in)))->isTrue();
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

    public function testGetFromIter()
    {
        global $DB;

        $iter = $DB->request(['SELECT' => 'id',
            'FROM'   => 'glpi_computers'
        ]);
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

    public function testGetFromDbByCrit()
    {
        $comp = new \Computer();
        $this->boolean($comp->getFromDBByCrit(['name' => '_test_pc01']))->isTrue();
        $this->string($comp->getField('name'))->isIdenticalTo('_test_pc01');

        $this->when(
            function () use ($comp) {
                $this->boolean($comp->getFromDBByCrit(['name' => ['LIKE', '_test%']]))->isFalse();
            }
        )->error()
         ->withType(E_USER_WARNING)
         ->withMessage('getFromDBByCrit expects to get one result, 9 found in query "SELECT `id` FROM `glpi_computers` WHERE `name` LIKE \'_test%\'".')
         ->exists();
    }

    public function testClone()
    {
        $this->login();
        $this->setEntity('_test_root_entity', true);

        $date = date('Y-m-d H:i:s');
        $_SESSION['glpi_currenttime'] = $date;

       // Test item cloning
        $computer = $this->getNewComputer();
        $id = $computer->fields['id'];

       //add note
        $note = new \Notepad();
        $this->integer(
            $note->add([
                'itemtype'  => 'Computer',
                'items_id'  => $id
            ])
        )->isGreaterThan(0);

       //add os
        $os = new \OperatingSystem();
        $osid = $os->add([
            'name'   => 'My own OS'
        ]);
        $this->integer($osid)->isGreaterThan(0);

        $ios = new \Item_OperatingSystem();
        $this->integer(
            $ios->add([
                'operatingsystems_id' => $osid,
                'itemtype'            => 'Computer',
                'items_id'            => $id,
            ])
        )->isGreaterThan(0);

       //add infocom
        $infocom = new \Infocom();
        $this->integer(
            $infocom->add([
                'itemtype'  => 'Computer',
                'items_id'  => $id
            ])
        )->isGreaterThan(0);

       //add device
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
                'items_id'              => $id,
                'itemtype'              => 'Computer',
                'deviceprocessors_id'   => $cpuid
            ]
        );
        $this->integer((int)$linkid)->isGreaterThan(0);

       //add document
        $document = new \Document();
        $docid = (int)$document->add(['name' => 'Test link document']);
        $this->integer($docid)->isGreaterThan(0);

        $docitem = new \Document_Item();
        $this->integer(
            $docitem->add([
                'documents_id' => $docid,
                'itemtype'     => 'Computer',
                'items_id'     => $id
            ])
        )->isGreaterThan(0);

        //add antivirus
        $antivirus = new \ComputerAntivirus();
        $antivirus_id = (int)$antivirus->add(['name' => 'Test link antivirus', 'computers_id' => $id]);
        $this->integer($antivirus_id)->isGreaterThan(0);

       //clone!
        $computer = new \Computer(); //$computer->fields contents is already escaped!
        $this->boolean($computer->getFromDB($id))->isTrue();
        $added = $computer->clone();
        $this->integer((int)$added)->isGreaterThan(0);
        $this->integer($added)->isNotEqualTo($computer->fields['id']);

        $clonedComputer = new \Computer();
        $this->boolean($clonedComputer->getFromDB($added))->isTrue();

        $fields = $computer->fields;

       // Check the computers values. Id and dates must be different, everything else must be equal
        foreach ($fields as $k => $v) {
            switch ($k) {
                case 'id':
                    $this->variable($clonedComputer->getField($k))->isNotEqualTo($computer->getField($k));
                    break;
                case 'date_mod':
                case 'date_creation':
                    $dateClone = new \DateTime($clonedComputer->getField($k));
                    $expectedDate = new \DateTime($date);
                    $this->dateTime($dateClone)->isEqualTo($expectedDate);
                    break;
                case 'name':
                    $this->variable($clonedComputer->getField($k))->isEqualTo("{$computer->getField($k)} (copy)");
                    break;
                default:
                    $this->variable($clonedComputer->getField($k))->isEqualTo($computer->getField($k));
            }
        }

       //TODO: would be better to check each Computer::getCloneRelations() ones.
        $relations = [
            \Infocom::class => 1,
            \Notepad::class  => 1,
            \Item_OperatingSystem::class => 1,
        ];

        foreach ($relations as $relation => $expected) {
            $this->integer(
                countElementsInTable(
                    $relation::getTable(),
                    [
                        'itemtype'  => 'Computer',
                        'items_id'  => $clonedComputer->fields['id'],
                    ]
                )
            )->isIdenticalTo($expected);
        }

        //check antivirus
        $this->boolean($antivirus->getFromDBByCrit(['computers_id' => $clonedComputer->fields['id']]))->isTrue();

       //check processor has been cloned
        $this->boolean($link->getFromDBByCrit(['itemtype' => 'Computer', 'items_id' => $added]))->isTrue();
        $this->boolean($docitem->getFromDBByCrit(['itemtype' => 'Computer', 'items_id' => $added]))->isTrue();
    }

    public function testCloneWithAutoCreateInfocom()
    {
        global $CFG_GLPI, $DB;

        $this->login();
        $this->setEntity('_test_root_entity', true);

        $date = date('Y-m-d H:i:s');
        $_SESSION['glpi_currenttime'] = $date;

        // Test item cloning
        $computer = $this->getNewComputer();
        $id = $computer->fields['id'];

        // add infocom
        $infocom = new \Infocom();
        $this->integer(
            $infocom->add([
                'itemtype'  => 'Computer',
                'items_id'  => $id,
                'buy_date'  => '2021-01-01',
                'use_date'  => '2021-01-02',
                'value'     => '800.00'
            ])
        )->isGreaterThan(0);

        // clone!
        $computer = new \Computer(); //$computer->fields contents is already escaped!
        $this->boolean($computer->getFromDB($id))->isTrue();
        $infocom_auto_create_original = $CFG_GLPI["infocom_auto_create"] ?? 0;
        $CFG_GLPI["infocom_auto_create"] = 1;
        $added = $computer->clone();
        $CFG_GLPI["infocom_auto_create"] = $infocom_auto_create_original;
        $this->integer((int)$added)->isGreaterThan(0);
        $this->integer($added)->isNotEqualTo($computer->fields['id']);

        $clonedComputer = new \Computer();
        $this->boolean($clonedComputer->getFromDB($added))->isTrue();

        $iterator = $DB->request([
            'SELECT' => ['buy_date', 'use_date', 'value'],
            'FROM'   => \Infocom::getTable(),
            'WHERE'  => [
                'itemtype'  => 'Computer',
                'items_id'  => $clonedComputer->fields['id']
            ]
        ]);
        $this->integer($iterator->count())->isEqualTo(1);
        $this->array($iterator->current())->isIdenticalTo([
            'buy_date'  => '2021-01-01',
            'use_date'  => '2021-01-02',
            'value'     => '800.0000' //DB stores 4 decimal places
        ]);
    }

    public function testCloneWithSpecificName()
    {
        /** @var \Computer $computer */
        $computer = $this->getNewComputer();
        $clone_id = $computer->clone([
            'name' => 'testCloneWithSpecificName'
        ]);
        $this->integer($clone_id)->isGreaterThan(0);
        $result = $computer->getFromDB($clone_id);
        $this->boolean($result)->isTrue();
        $this->string($computer->fields['name'])->isEqualTo('testCloneWithSpecificName');
    }

    public function testClonedRelationNamesFromTemplate()
    {
        $this->login();
        $this->setEntity('_test_root_entity', true);

        /** @var \Computer $computer */
        $computer_template = new \Computer();
        $templates_id = $computer_template->add([
            'template_name' => __FUNCTION__ . '_template',
            'is_template' => 1
        ]);
        $this->integer($templates_id)->isGreaterThan(0);

        // Add a network port to the template
        $networkPort = new \NetworkPort();
        $networkports_id = $networkPort->add([
            'name' => __FUNCTION__,
            'itemtype' => 'Computer',
            'items_id' => $templates_id,
            'instantiation_type' => 'NetworkPortEthernet',
            'logical_number' => 0,
            'items_devicenetworkcards_id' => 0,
            '_create_children' => true
        ]);
        $this->integer($networkports_id)->isGreaterThan(0);

        // Create computer from template
        $computer = new \Computer();
        $computers_id = $computer->add([
            'name' => __FUNCTION__,
            'id' => $templates_id
        ]);
        $this->integer($computers_id)->isGreaterThan(0);

        // Get network port from computer
        $this->boolean($networkPort->getFromDBByCrit([
            'itemtype' => 'Computer',
            'items_id' => $computers_id,
        ]))->isTrue();
        // Network port name should not have a "copy" suffix
        $this->string($networkPort->fields['name'])->isEqualTo(__FUNCTION__);
    }

    public function testCloneWithAutoName()
    {
        /** @var \Computer $computer */
        $computer = $this->getNewComputer();
        $computer->update([
            'id' => $computer->fields['id'],
            'name' => 'testCloneWithAutoName'
        ]);
        $clone_id = $computer->clone();
        $this->integer($clone_id)->isGreaterThan(0);
        $result = $computer->getFromDB($clone_id);
        $this->boolean($result)->isTrue();
        $this->string($computer->fields['name'])->isEqualTo('testCloneWithAutoName (copy)');
    }

    public function testTransfer()
    {
        $this->login();
        $computer = $this->getNewComputer();
        $cid = $computer->fields['id'];

        $soft = new \Software();
        $softwares_id = $soft->add([
            'name'         => 'GLPI',
            'entities_id'  => $computer->fields['entities_id']
        ]);
        $this->integer($softwares_id)->isGreaterThan(0);

        $version = new \SoftwareVersion();
        $versions_id = $version->add([
            'softwares_id' => $softwares_id,
            'name'         => '9.5'
        ]);
        $this->integer($versions_id)->isGreaterThan(0);

        $link = new \Item_SoftwareVersion();
        $link_id  = $link->add([
            'itemtype'              => 'Computer',
            'items_id'              => $cid,
            'softwareversions_id'   => $versions_id
        ]);
        $this->integer($link_id)->isGreaterThan(0);

        $entities_id = getItemByTypeName('Entity', '_test_child_2', true);
        $oentities_id = (int)$computer->fields['entities_id'];
        $this->integer($entities_id)->isNotEqualTo($oentities_id);

       //transer to another entity
        $transfer = new \Transfer();

        $this->mockGenerator->orphanize('__construct');
        $ma = new \mock\MassiveAction([], [], 'process');

        \MassiveAction::processMassiveActionsForOneItemtype(
            $ma,
            $computer,
            [$cid]
        );
        $transfer->moveItems(['Computer' => [$cid]], $entities_id, [$cid, 'keep_software' => 1]);
        unset($_SESSION['glpitransfer_list']);

        $this->boolean($computer->getFromDB($cid))->isTrue();
        $this->integer((int)$computer->fields['entities_id'])->isidenticalTo($entities_id);

        $this->boolean($soft->getFromDB($softwares_id))->isTrue();
        $this->integer($soft->fields['entities_id'])->isidenticalTo($oentities_id);

        global $DB;
        $softwares = $DB->request([
            'FROM'   => \Item_SoftwareVersion::getTable(),
            'WHERE'  => [
                'itemtype'  => 'Computer',
                'items_id'  => $cid
            ]
        ]);
        $this->integer(count($softwares))->isidenticalTo(1);
    }

    public function testClearSavedInputAfterUpdate()
    {
        $this->login();

        // Check that there is no saveInput already
        if (isset($_SESSION['saveInput']) && is_array($_SESSION['saveInput'])) {
            $this->array($_SESSION['saveInput'])->notHasKey('Computer');
        }
        $computer = $this->getNewComputer();
        $cid = $computer->fields['id'];

        $result = $computer->update([
            'id'    => $cid,
            'comment'  => 'test'
        ]);
        $this->boolean($result)->isTrue();

        // Check that there is no savedInput after update
        if (isset($_SESSION['saveInput']) && is_array($_SESSION['saveInput'])) {
            $this->array($_SESSION['saveInput'])->notHasKey('Computer');
        }
    }

    public function testGetInventoryAgent(): void
    {
        $computer = $this->getNewComputer();
        $printer1 = $this->getNewPrinter();
        $this->createItem(
            \Computer_Item::class,
            [
                'computers_id' => $computer->fields['id'],
                'itemtype'     => \Printer::class,
                'items_id'     => $printer1->fields['id'],
            ]
        );
        $printer2 = $this->getNewPrinter();
        $this->createItem(
            \Computer_Item::class,
            [
                'computers_id' => $computer->fields['id'],
                'itemtype'     => \Printer::class,
                'items_id'     => $printer2->fields['id'],
            ]
        );

        $computer_agent = $computer->getInventoryAgent();
        $this->variable($computer_agent)->isNull();

        $agenttype_id = getItemByTypeName(\AgentType::class, 'Core', true);

        $agent1 = $this->createItem(
            \Agent::class,
            [
                'deviceid'     => sprintf('device_%08x', rand()),
                'agenttypes_id' => $agenttype_id,
                'itemtype'     => \Computer::class,
                'items_id'     => $computer->fields['id'],
                'last_contact' => date('Y-m-d H:i:s', strtotime('yesterday')),
            ]
        );

        $agent2 = $this->createItem(
            \Agent::class,
            [
                'deviceid'     => sprintf('device_%08x', rand()),
                'agenttypes_id' => $agenttype_id,
                'itemtype'     => \Computer::class,
                'items_id'     => $computer->fields['id'],
                'last_contact' => date('Y-m-d H:i:s', strtotime('last week')),
            ]
        );

        $agent3 = $this->createItem(
            \Agent::class,
            [
                'deviceid'     => sprintf('device_%08x', rand()),
                'agenttypes_id' => $agenttype_id,
                'itemtype'     => \Printer::class,
                'items_id'     => $printer1->fields['id'],
                'last_contact' => date('Y-m-d H:i:s', strtotime('last hour')),
            ]
        );

        $this->createItem(
            \Agent::class,
            [
                'deviceid'     => sprintf('device_%08x', rand()),
                'agenttypes_id' => $agenttype_id,
                'itemtype'     => \Printer::class,
                'items_id'     => $printer2->fields['id'],
                'last_contact' => date('Y-m-d H:i:s', strtotime('yesterday')),
            ]
        );

        // most recent agent directly linked
        $computer_agent = $computer->getInventoryAgent();
        $this->object($computer_agent)->isInstanceOf(\Agent::class);
        $this->array($computer_agent->fields)->isEqualTo($agent1->fields);

        $this->boolean($agent1->delete(['id' => $agent1->fields['id']]))->isTrue();

        // most recent agent directly linked
        $computer_agent = $computer->getInventoryAgent();
        $this->object($computer_agent)->isInstanceOf(\Agent::class);
        $this->array($computer_agent->fields)->isEqualTo($agent2->fields);

        $this->boolean($agent2->delete(['id' => $agent2->fields['id']]))->isTrue();

        // most recent agent found from linked items, as there is no more agent linked directly
        $computer_agent = $computer->getInventoryAgent();
        $this->object($computer_agent)->isInstanceOf(\Agent::class);
        $printer1_agent = $printer1->getInventoryAgent();
        $this->object($printer1_agent)->isInstanceOf(\Agent::class);
        $this->array($computer_agent->fields)->isEqualTo($printer1_agent->fields);
    }
}
