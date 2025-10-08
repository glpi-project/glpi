<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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
use Entity;
use Glpi\Asset\Asset_PeripheralAsset;
use PHPUnit\Framework\Attributes\DataProvider;

/* Test for inc/computer.class.php */

class ComputerTest extends DbTestCase
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
        $this->assertGreaterThan(0, (int) $computer->add($fields));
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
        $this->assertGreaterThan(0, (int) $printer->add($pfields));
        return $printer;
    }

    public function testUpdate()
    {
        $this->login();

        $computer = $this->getNewComputer();
        $printer  = $this->getNewPrinter();

        // Create the link
        $link = new Asset_PeripheralAsset();
        $in = [
            'itemtype_asset' => $computer->getType(),
            'items_id_asset' => $computer->getField('id'),
            'itemtype_peripheral' => $printer->getType(),
            'items_id_peripheral' => $printer->getID(),
        ];
        $this->assertGreaterThan(0, (int) $link->add($in));

        // Change the computer
        $entity = new Entity();
        $entity->getFromDB(0);
        $this->assertTrue($entity->update([
            'id' => $entity->fields['id'],
            'is_contact_autoupdate'  => 1,
            'is_user_autoupdate'     => 1,
            'is_group_autoupdate'    => 1,
            'state_autoupdate_mode'  => -1,
            'is_location_autoupdate' => 1,
        ]));
        $in = ['id'           => $computer->getField('id'),
            'contact'      => $this->getUniqueString(),
            'contact_num'  => $this->getUniqueString(),
            'users_id'     => $this->getUniqueInteger(),
            'groups_id'    => $this->getUniqueInteger(),
            'states_id'    => $this->getUniqueInteger(),
            'locations_id' => $this->getUniqueInteger(),
        ];
        $this->assertTrue($computer->update($in));
        $this->assertTrue($computer->getFromDB($computer->getID()));
        $this->assertTrue($printer->getFromDB($printer->getID()));
        unset($in['id']);
        foreach ($in as $k => $v) {
            $expected = $v;
            if (in_array($k, ['groups_id', 'groups_id_tech'], true)) {
                // These fields are transformed into arrays
                $expected = [$v];
            }
            // Check the computer new values
            $this->assertEquals($expected, $computer->getField($k));
            // Check the printer and test propagation occurs
            $this->assertEquals($expected, $printer->getField($k), $k);
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
        $this->assertTrue($computer->update($in));
        $this->assertTrue($computer->getFromDB($computer->getID()));
        $this->assertTrue($printer->getFromDB($printer->getID()));
        unset($in['id']);
        foreach ($in as $k => $v) {
            $expected = $v;
            if (in_array($k, ['groups_id', 'groups_id_tech'], true)) {
                // These fields are transformed into arrays
                $expected = $v === 0 ? [] : [$v];
            }
            // Check the computer new values
            $this->assertEquals($expected, $computer->getField($k));
            // Check the printer and test propagation occurs
            $this->assertEquals($expected, $printer->getField($k));
        }

        // Change the computer again
        $this->assertTrue($entity->update([
            'id' => $entity->fields['id'],
            'is_contact_autoupdate'  => 0,
            'is_user_autoupdate'     => 0,
            'is_group_autoupdate'    => 0,
            'state_autoupdate_mode'  => 0,
            'is_location_autoupdate' => 0,
        ]));
        $this->login();
        $in2 = ['id'          => $computer->getField('id'),
            'contact'      => $this->getUniqueString(),
            'contact_num'  => $this->getUniqueString(),
            'users_id'     => $this->getUniqueInteger(),
            'groups_id'    => $this->getUniqueInteger(),
            'states_id'    => $this->getUniqueInteger(),
            'locations_id' => $this->getUniqueInteger(),
        ];
        $this->assertTrue($computer->update($in2));
        $this->assertTrue($computer->getFromDB($computer->getID()));
        $this->assertTrue($printer->getFromDB($printer->getID()));
        unset($in2['id']);
        foreach ($in2 as $k => $v) {
            $expected = $v;
            $old_value = $in[$k];
            if (in_array($k, ['groups_id', 'groups_id_tech'], true)) {
                // These fields are transformed into arrays
                $expected = $v === 0 ? [] : [$v];
                $old_value = $old_value === 0 ? [] : [$old_value];
            }
            // Check the computer new values
            $this->assertEquals($expected, $computer->getField($k));
            // Check the printer and test propagation DOES NOT occurs
            $this->assertEquals($old_value, $printer->getField($k));
        }

        // Restore configuration
        $computer = $this->getNewComputer();

        //update devices
        $cpu = new \DeviceProcessor();
        $cpuid = $cpu->add(
            [
                'designation'  => 'Intel(R) Core(TM) i5-4210U CPU @ 1.70GHz',
                'frequence'    => '1700',
            ]
        );

        $this->assertGreaterThan(0, (int) $cpuid);

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

        $this->assertGreaterThan(0, (int) $linkid);

        // Change the computer
        $this->assertTrue($entity->update([
            'id' => $entity->fields['id'],
            'state_autoupdate_mode'  => -1,
            'is_location_autoupdate' => 1,
        ]));
        $in = ['id'           => $computer->getField('id'),
            'states_id'    => $this->getUniqueInteger(),
            'locations_id' => $this->getUniqueInteger(),
        ];
        $this->assertTrue($computer->update($in));
        $this->assertTrue($computer->getFromDB($computer->getID()));
        $this->assertTrue($link->getFromDB($link->getID()));
        unset($in['id']);
        foreach ($in as $k => $v) {
            $expected = $v;
            if (in_array($k, ['groups_id', 'groups_id_tech'], true)) {
                // These fields are transformed into arrays
                $expected = [$v];
            }
            // Check the computer new values
            $this->assertEquals($expected, $computer->getField($k));
            // Check the printer and test propagation occurs
            $this->assertEquals($expected, $link->getField($k));
        }

        //reset
        $in = ['id'           => $computer->getField('id'),
            'states_id'    => 0,
            'locations_id' => 0,
        ];
        $this->assertTrue($computer->update($in));
        $this->assertTrue($computer->getFromDB($computer->getID()));
        $this->assertTrue($link->getFromDB($link->getID()));
        unset($in['id']);
        foreach ($in as $k => $v) {
            // Check the computer new values
            $this->assertEquals($v, $computer->getField($k));
            // Check the printer and test propagation occurs
            $this->assertEquals($v, $link->getField($k));
        }

        // Change the computer again
        $this->assertTrue($entity->update([
            'id' => $entity->fields['id'],
            'state_autoupdate_mode'  => 0,
            'is_location_autoupdate' => 0,
        ]));
        $in2 = ['id'          => $computer->getField('id'),
            'states_id'    => $this->getUniqueInteger(),
            'locations_id' => $this->getUniqueInteger(),
        ];
        $this->assertTrue($computer->update($in2));
        $this->assertTrue($computer->getFromDB($computer->getID()));
        $this->assertTrue($link->getFromDB($link->getID()));
        unset($in2['id']);
        foreach ($in2 as $k => $v) {
            $old_value = $in[$k];
            $expected = $v;
            if (in_array($k, ['groups_id', 'groups_id_tech'], true)) {
                // These fields are transformed into arrays
                $expected = [$v];
                $old_value = [$old_value];
            }
            // Check the computer new values
            $this->assertEquals($expected, $computer->getField($k));
            // Check the printer and test propagation DOES NOT occurs
            $this->assertEquals($old_value, $link->getField($k));
        }
    }

    /**
     * Checks that newly created links inherits locations, status, and so on
     *
     * @return void
     */
    public function testCreateLinks()
    {
        $this->login();

        $computer = $this->getNewComputer();

        $entity = new Entity();
        $entity->getFromDB(0);
        $this->assertTrue($entity->update([
            'id' => $entity->fields['id'],
            'is_contact_autoupdate'  => 1,
            'is_user_autoupdate'     => 1,
            'is_group_autoupdate'    => 1,
            'state_autoupdate_mode'  => -1,
            'is_location_autoupdate' => 1,
        ]));

        // Change the computer
        $in = ['id'           => $computer->getField('id'),
            'contact'      => $this->getUniqueString(),
            'contact_num'  => $this->getUniqueString(),
            'users_id'     => $this->getUniqueInteger(),
            'groups_id'    => $this->getUniqueInteger(),
            'states_id'    => $this->getUniqueInteger(),
            'locations_id' => $this->getUniqueInteger(),
        ];
        $this->assertTrue($computer->update($in));
        $this->assertTrue($computer->getFromDB($computer->getID()));

        $printer = new \Printer();
        $pid = $printer->add(
            [
                'name'         => 'A test printer',
                'entities_id'  => $computer->getField('entities_id'),
            ]
        );

        $this->assertGreaterThan(0, (int) $pid);

        // Create the link
        $link = new Asset_PeripheralAsset();
        $in2 = [
            'itemtype_asset' => $computer->getType(),
            'items_id_asset' => $computer->getField('id'),
            'itemtype_peripheral' => $printer->getType(),
            'items_id_peripheral' => $printer->getID(),
        ];
        $this->assertGreaterThan(0, (int) $link->add($in2));

        $this->assertTrue($printer->getFromDB($printer->getID()));
        unset($in['id']);
        foreach ($in as $k => $v) {
            $expected = $v;
            if (in_array($k, ['groups_id', 'groups_id_tech'], true)) {
                // These fields are transformed into arrays
                $expected = [$v];
            }
            // Check the computer new values
            $this->assertEquals($expected, $computer->getField($k));
            // Check the printer and test propagation occurs
            $this->assertEquals($expected, $printer->getField($k));
        }

        //create devices
        $cpu = new \DeviceProcessor();
        $cpuid = $cpu->add(
            [
                'designation'  => 'Intel(R) Core(TM) i5-4210U CPU @ 1.70GHz',
                'frequence'    => '1700',
            ]
        );

        $this->assertGreaterThan(0, (int) $cpuid);

        $link = new \Item_DeviceProcessor();
        $linkid = $link->add(
            [
                'items_id'              => $computer->getID(),
                'itemtype'              => \Computer::getType(),
                'deviceprocessors_id'   => $cpuid,
            ]
        );

        $this->assertGreaterThan(0, (int) $linkid);

        $in3 = ['states_id'    => $in['states_id'],
            'locations_id' => $in['locations_id'],
        ];

        $this->assertTrue($link->getFromDB($link->getID()));
        foreach ($in3 as $k => $v) {
            // Check the computer new values
            $this->assertEquals($v, $computer->getField($k));
            // Check the printer and test propagation occurs
            $this->assertEquals($v, $link->getField($k));
        }
    }

    public function testGetFromIter()
    {
        global $DB;

        $iter = $DB->request(['SELECT' => 'id',
            'FROM'   => 'glpi_computers',
        ]);
        foreach (\Computer::getFromIter($iter) as $comp) {
            $this->assertInstanceOf(\Computer::class, $comp);
            $this->assertArrayHasKey('name', $comp->fields);
        }
    }

    public function testGetFromDbByCrit()
    {
        $comp = new \Computer();
        $this->assertTrue($comp->getFromDBByCrit(['name' => '_test_pc01']));
        $this->assertSame('_test_pc01', $comp->getField('name'));


        $this->expectExceptionMessage(
            '`Computer::getFromDBByCrit()` expects to get one result, 9 found in query "SELECT `id` FROM `glpi_computers` WHERE `name` LIKE \'_test%\'".'
        );
        $comp->getFromDBByCrit(['name' => ['LIKE', '_test%']]);
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
        $this->assertGreaterThan(
            0,
            $note->add([
                'itemtype'  => 'Computer',
                'items_id'  => $id,
            ])
        );

        //add os
        $os = new \OperatingSystem();
        $osid = $os->add([
            'name'   => 'My own OS',
        ]);
        $this->assertGreaterThan(0, $osid);

        $ios = new \Item_OperatingSystem();
        $this->assertGreaterThan(
            0,
            $ios->add([
                'operatingsystems_id' => $osid,
                'itemtype'            => 'Computer',
                'items_id'            => $id,
            ])
        );

        //add infocom
        $infocom = new \Infocom();
        $this->assertGreaterThan(
            0,
            $infocom->add([
                'itemtype'  => 'Computer',
                'items_id'  => $id,
            ])
        );

        //add device
        $cpu = new \DeviceProcessor();
        $cpuid = $cpu->add(
            [
                'designation'  => 'Intel(R) Core(TM) i5-4210U CPU @ 1.70GHz',
                'frequence'    => '1700',
            ]
        );

        $this->assertGreaterThan(0, (int) $cpuid);

        $link = new \Item_DeviceProcessor();
        $linkid = $link->add(
            [
                'items_id'              => $id,
                'itemtype'              => 'Computer',
                'deviceprocessors_id'   => $cpuid,
            ]
        );
        $this->assertGreaterThan(0, (int) $linkid);

        //add document
        $document = new \Document();
        $docid = (int) $document->add(['name' => 'Test link document']);
        $this->assertGreaterThan(0, $docid);

        $docitem = new \Document_Item();
        $this->assertGreaterThan(
            0,
            $docitem->add([
                'documents_id' => $docid,
                'itemtype'     => 'Computer',
                'items_id'     => $id,
            ])
        );

        //add antivirus
        $antivirus = new \ItemAntivirus();
        $antivirus_id = (int) $antivirus->add(['name' => 'Test link antivirus', 'itemtype' => 'Computer', 'items_id' => $id]);
        $this->assertGreaterThan(0, $antivirus_id);

        //clone!
        $computer = new \Computer(); //$computer->fields contents is already escaped!
        $this->assertTrue($computer->getFromDB($id));
        $added = $computer->clone();
        $this->assertGreaterThan(0, (int) $added);
        $this->assertNotEquals($computer->fields['id'], $added);

        $clonedComputer = new \Computer();
        $this->assertTrue($clonedComputer->getFromDB($added));

        $fields = $computer->fields;

        // Check the computers values. Id and dates must be different, everything else must be equal
        foreach ($fields as $k => $v) {
            switch ($k) {
                case 'id':
                    $this->assertNotEquals($computer->getField($k), $clonedComputer->getField($k));
                    break;
                case 'date_mod':
                case 'date_creation':
                    $dateClone = new \DateTime($clonedComputer->getField($k));
                    $expectedDate = new \DateTime($date);
                    $this->assertEquals($expectedDate, $dateClone);
                    break;
                case 'name':
                    $this->assertEquals("{$computer->getField($k)} (copy)", $clonedComputer->getField($k));
                    break;
                default:
                    $this->assertEquals($computer->getField($k), $clonedComputer->getField($k));
            }
        }

        //TODO: would be better to check each Computer::getCloneRelations() ones.
        $relations = [
            \Infocom::class => 1,
            \Notepad::class  => 1,
            \Item_OperatingSystem::class => 1,
        ];

        foreach ($relations as $relation => $expected) {
            $this->assertSame(
                $expected,
                countElementsInTable(
                    $relation::getTable(),
                    [
                        'itemtype'  => 'Computer',
                        'items_id'  => $clonedComputer->fields['id'],
                    ]
                )
            );
        }

        //check antivirus
        $this->assertTrue($antivirus->getFromDBByCrit(['itemtype' => 'Computer', 'items_id' => $clonedComputer->fields['id']]));

        //check processor has been cloned
        $this->assertTrue($link->getFromDBByCrit(['itemtype' => 'Computer', 'items_id' => $added]));
        $this->assertTrue($docitem->getFromDBByCrit(['itemtype' => 'Computer', 'items_id' => $added]));
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
        $this->assertGreaterThan(
            0,
            $infocom->add([
                'itemtype'  => 'Computer',
                'items_id'  => $id,
                'buy_date'  => '2021-01-01',
                'use_date'  => '2021-01-02',
                'value'     => '800.00',
            ])
        );

        // clone!
        $computer = new \Computer(); //$computer->fields contents is already escaped!
        $this->assertTrue($computer->getFromDB($id));
        $auto_create_infocoms_original = $CFG_GLPI["auto_create_infocoms"] ?? 0;
        $CFG_GLPI["auto_create_infocoms"] = 1;
        $added = $computer->clone();
        $CFG_GLPI["auto_create_infocoms"] = $auto_create_infocoms_original;
        $this->assertGreaterThan(0, (int) $added);
        $this->assertNotEquals($computer->fields['id'], $added);

        $clonedComputer = new \Computer();
        $this->assertTrue($clonedComputer->getFromDB($added));

        $iterator = $DB->request([
            'SELECT' => ['buy_date', 'use_date', 'value'],
            'FROM'   => \Infocom::getTable(),
            'WHERE'  => [
                'itemtype'  => 'Computer',
                'items_id'  => $clonedComputer->fields['id'],
            ],
        ]);
        $this->assertEquals(1, $iterator->count());
        $this->assertSame(
            [
                'buy_date'  => '2021-01-01',
                'use_date'  => '2021-01-02',
                'value'     => '800.0000', //DB stores 4 decimal places
            ],
            $iterator->current()
        );
    }

    public function testCloneWithSpecificName()
    {
        /** @var \Computer $computer */
        $computer = $this->getNewComputer();
        $clone_id = $computer->clone([
            'name' => 'testCloneWithSpecificName',
        ]);
        $this->assertGreaterThan(0, $clone_id);
        $result = $computer->getFromDB($clone_id);
        $this->assertTrue($result);
        $this->assertEquals('testCloneWithSpecificName', $computer->fields['name']);
    }

    public function testClonedRelationNamesFromTemplate()
    {
        $entity_id = \getItemByTypeName(Entity::class, '_test_root_entity', true);

        $this->login();
        $this->setEntity('_test_root_entity', true);

        /** @var \Computer $computer */
        $computer_template = new \Computer();
        $templates_id = $computer_template->add([
            'template_name' => __FUNCTION__ . '_template',
            'is_template'   => 1,
            'entities_id'   => $entity_id,
        ]);
        $this->assertGreaterThan(0, $templates_id);

        // Add a network port to the template
        $networkPort = new \NetworkPort();
        $networkports_id = $networkPort->add([
            'name' => __FUNCTION__,
            'itemtype' => 'Computer',
            'items_id' => $templates_id,
            'instantiation_type' => 'NetworkPortEthernet',
            'logical_number' => 0,
            'items_devicenetworkcards_id' => 0,
            '_create_children' => true,
        ]);
        $this->assertGreaterThan(0, $networkports_id);

        // Create computer from template
        $computer = new \Computer();
        $computers_id = $computer->add([
            'name' => __FUNCTION__,
            'id' => $templates_id,
        ]);
        $this->assertGreaterThan(0, $computers_id);

        // Get network port from computer
        $this->assertTrue(
            $networkPort->getFromDBByCrit([
                'itemtype' => 'Computer',
                'items_id' => $computers_id,
            ])
        );
        // Network port name should not have a "copy" suffix
        $this->assertEquals(__FUNCTION__, $networkPort->fields['name']);
    }

    public function testCloneWithAutoName()
    {
        /** @var \Computer $computer */
        $computer = $this->getNewComputer();
        $computer->update([
            'id' => $computer->fields['id'],
            'name' => 'testCloneWithAutoName',
        ]);
        $clone_id = $computer->clone();
        $this->assertGreaterThan(0, $clone_id);
        $result = $computer->getFromDB($clone_id);
        $this->assertTrue($result);
        $this->assertEquals('testCloneWithAutoName (copy)', $computer->fields['name']);
    }

    /**
     * Test cloning a computer template to a new template
     * @return void
     */
    public function testCloneTemplate()
    {
        $this->login();
        $this->setEntity('_test_root_entity', true);

        $computer = $this->getNewComputer();
        $computer->update([
            'id' => $computer->fields['id'],
            'is_template' => 1,
            'template_name' => 'testCloneTemplate',
        ]);

        $clone_id = $computer->clone(clone_as_template: true);
        $this->assertGreaterThan(0, $clone_id);

        $clonedComputer = new \Computer();
        $this->assertTrue($clonedComputer->getFromDB($clone_id));
        $this->assertEquals(1, $clonedComputer->fields['is_template']);
        $this->assertEquals('testCloneTemplate (copy)', $clonedComputer->fields['template_name']);
    }

    /**
     * Test creating a template from an item using the clone feature
     * @return void
     */
    public function testCreateTemplateFromItem()
    {
        $this->login();
        $this->setEntity('_test_root_entity', true);

        $computer = $this->getNewComputer();
        $computer->update([
            'id' => $computer->fields['id'],
            'name' => 'testCreateTemplateFromItem',
        ]);

        $template_id = $computer->clone([
            'template_name' => 'my_template',
        ], true, true);
        $this->assertGreaterThan(0, $template_id);

        $template = new \Computer();
        $this->assertTrue($template->getFromDB($template_id));
        $this->assertEquals(1, $template->fields['is_template']);
        $this->assertEquals('my_template', $template->fields['template_name']);
    }

    public function testTransfer()
    {
        $this->login();
        $computer = $this->getNewComputer();
        $cid = $computer->fields['id'];

        $soft = new \Software();
        $softwares_id = $soft->add([
            'name'         => 'GLPI',
            'entities_id'  => $computer->fields['entities_id'],
        ]);
        $this->assertGreaterThan(0, $softwares_id);

        $version = new \SoftwareVersion();
        $versions_id = $version->add([
            'softwares_id' => $softwares_id,
            'name'         => '9.5',
        ]);
        $this->assertGreaterThan(0, $versions_id);

        $link = new \Item_SoftwareVersion();
        $link_id  = $link->add([
            'itemtype'              => 'Computer',
            'items_id'              => $cid,
            'softwareversions_id'   => $versions_id,
        ]);
        $this->assertGreaterThan(0, $link_id);

        $entities_id = getItemByTypeName('Entity', '_test_child_2', true);
        $oentities_id = (int) $computer->fields['entities_id'];
        $this->assertNotEquals($oentities_id, $entities_id);

        //transfer to another entity
        $transfer = new \Transfer();

        $ma = $this->getMockBuilder(\MassiveAction::class)
            ->disableOriginalConstructor()
            ->getMock();

        \MassiveAction::processMassiveActionsForOneItemtype(
            $ma,
            $computer,
            [$cid]
        );
        $transfer->moveItems(['Computer' => [$cid]], $entities_id, [$cid, 'keep_software' => 1]);
        unset($_SESSION['glpitransfer_list']);

        $this->assertTrue($computer->getFromDB($cid));
        $this->assertSame($entities_id, (int) $computer->fields['entities_id']);

        $this->assertTrue($soft->getFromDB($softwares_id));
        $this->assertSame($oentities_id, $soft->fields['entities_id']);

        global $DB;
        $softwares = $DB->request([
            'FROM'   => \Item_SoftwareVersion::getTable(),
            'WHERE'  => [
                'itemtype'  => 'Computer',
                'items_id'  => $cid,
            ],
        ]);
        $this->assertSame(1, count($softwares));
    }

    public function testClearSavedInputAfterUpdate()
    {
        $this->login();

        // Check that there is no saveInput already
        if (isset($_SESSION['saveInput']) && is_array($_SESSION['saveInput'])) {
            $this->assertArrayNotHasKey(\Computer::class, $_SESSION['saveInput']);
        }
        $computer = $this->getNewComputer();
        $cid = $computer->fields['id'];

        $result = $computer->update([
            'id'    => $cid,
            'comment'  => 'test',
        ]);
        $this->assertTrue($result);

        // Check that there is no savedInput after update
        if (isset($_SESSION['saveInput']) && is_array($_SESSION['saveInput'])) {
            $this->assertArrayNotHasKey(\Computer::class, $_SESSION['saveInput']);
        }
    }

    public function testGetInventoryAgent(): void
    {
        $computer = $this->getNewComputer();
        $printer1 = $this->getNewPrinter();
        $this->createItem(
            Asset_PeripheralAsset::class,
            [
                'itemtype_asset' => $computer->getType(),
                'items_id_asset' => $computer->getID(),
                'itemtype_peripheral' => \Printer::class,
                'items_id_peripheral' => $printer1->fields['id'],
            ]
        );
        $printer2 = $this->getNewPrinter();
        $this->createItem(
            Asset_PeripheralAsset::class,
            [
                'itemtype_asset' => $computer->getType(),
                'items_id_asset' => $computer->getID(),
                'itemtype_peripheral' => \Printer::class,
                'items_id_peripheral' => $printer2->fields['id'],
            ]
        );

        $computer_agent = $computer->getInventoryAgent();
        $this->assertNull($computer_agent);

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
        $this->assertInstanceOf(\Agent::class, $computer_agent);
        $this->assertEquals($agent1->fields, $computer_agent->fields);

        $this->assertTrue($agent1->delete(['id' => $agent1->fields['id']]));

        // most recent agent directly linked
        $computer_agent = $computer->getInventoryAgent();
        $this->assertInstanceOf(\Agent::class, $computer_agent);
        $this->assertEquals($agent2->fields, $computer_agent->fields);

        $this->assertTrue($agent2->delete(['id' => $agent2->fields['id']]));

        // most recent agent found from linked items, as there is no more agent linked directly
        $computer_agent = $computer->getInventoryAgent();
        $this->assertInstanceOf(\Agent::class, $computer_agent);
        $printer1_agent = $printer1->getInventoryAgent();
        $this->assertInstanceOf(\Agent::class, $printer1_agent);
        $this->assertEquals($printer1_agent->fields, $computer_agent->fields);
    }

    /**
     * Data provider for the testFormatSessionMessageAfterAction method
     *
     * @return iterable
     */
    public static function formatSessionMessageAfterActionProvider(): iterable
    {
        // Test message with link to item
        yield [
            "C1",
            "Test",
            'Test: <a href="/front/computer.form.php?id=CID" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Computer 1">Computer 1</a>',
        ];
        yield [
            "C2",
            "Test",
            'Test: <a href="/front/computer.form.php?id=CID" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Computer 2">Computer 2</a>',
        ];

        // Test message without link
        yield [
            "C3",
            "Test",
            "Test: Computer 3",
        ];
    }

    /**
     * Test the formatSessionMessageAfterAction method
     *
     * @param string $item                       Item string to create
     * @param string $raw_message                Raw message to format
     * @param string $expected_formatted_message Expected formatted message
     *
     * @return void
     */
    #[DataProvider('formatSessionMessageAfterActionProvider')]
    public function testFormatSessionMessageAfterAction(
        string $item_str,
        string $raw_message,
        string $expected_formatted_message
    ): void {
        $this->login();
        $entity = $this->getTestRootEntity();

        switch ($item_str) {
            case 'C1':
                $item = $this->createItem(\Computer::class, ['name' => 'Computer 1', 'entities_id' => $entity->fields['id']]);
                break;
            case 'C2':
                $item = $this->createItem(\Computer::class, ['name' => 'Computer 2', 'entities_id' => $entity->fields['id']]);
                break;
            case 'C3':
                $item = $this->createItem(\Computer::class, ['name' => 'Computer 3', 'entities_id' => $entity->fields['id']]);
                $item->input["_no_message_link"] = true;
                break;
            default:
                throw new \InvalidArgumentException("Unknown item string: $item_str");
        }
        $expected_formatted_message = str_replace('CID', $item->getID(), $expected_formatted_message);

        $message = $item->formatSessionMessageAfterAction($raw_message);
        $this->assertSame($expected_formatted_message, $message);
    }
}
