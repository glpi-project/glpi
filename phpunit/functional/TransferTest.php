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

use Certificate;
use Certificate_Item;
use Computer;
use DbTestCase;
use Item_SoftwareVersion;
use Software;
use SoftwareVersion;

/* Test for inc/transfer.class.php */

class TransferTest extends DbTestCase
{
    public function testTransfer()
    {
        $this->login();

        //Original entity
        $fentity = (int) getItemByTypeName('Entity', '_test_root_entity', true);
        //Destination entity
        $dentity = (int) getItemByTypeName('Entity', '_test_child_2', true);

        $location_id = getItemByTypeName('Location', '_location01', true);

        $itemtypeslist = self::getClasses(
            'searchOptions',
            [
                '/^Rule.*/',
                '/^Common.*/',
                '/^DB.*/',
                '/^SlaLevel.*/',
                '/^OlaLevel.*/',
                '/^Glpi\\Event$/',
                '/^KnowbaseItem$/',
                '/SavedSearch.*/',
                '/.*Notification.*/',
                '/^Device.*/',
                '/^Network.*/',
                '/^IPNetwork$/',
                '/^FQDN$/',
                '/^SoftwareVersion.*/',
                '/^SoftwareLicense.*/',
                '/^FieldUnicity$/',
                '/^PurgeLogs$/',
                '/^TicketRecurrent$/',
                '/^Agent$/',
                '/^USBVendor$/',
                '/^PCIVendor$/',
                '/^PendingReasonCron$/',
            ]
        );

        $fields_values = [
            'name'            => 'Object to transfer',
            'entities_id'     => $fentity,
            'content'         => 'A content',
            'definition_time' => 'hour',
            'number_time'     => 4,
            'begin_date'      => '2020-01-01',
            'vis_cols'        => 1,
            'vis_rows'        => 1,
        ];

        $count = 0;
        foreach ($itemtypeslist as $itemtype) {
            if (is_a($itemtype, \CommonDBConnexity::class, true)) {
                // Do not check transfer of child items, they are not supposed to be transferred directly.
                continue;
            }

            $item_class = new \ReflectionClass($itemtype);
            if ($item_class->isAbstract()) {
                continue;
            }

            $obj = new $itemtype();
            if (!$obj->isEntityAssign()) {
                continue;
            }

            // Add
            $input = [];
            foreach ($fields_values as $field => $value) {
                if ($obj->isField($field)) {
                    $input[$field] = $value;
                }
            }

            if ($obj->maybeLocated()) {
                $input['locations_id'] = $location_id;
            }

            $id = $obj->add($input);
            $this->assertGreaterThan(
                0,
                $id,
                "Cannot add $itemtype"
            );
            $this->assertTrue($obj->getFromDB($id));

            //transfer to another entity
            $transfer = new \Transfer();

            $ma = $this->getMockBuilder(\MassiveAction::class)
                ->disableOriginalConstructor()
                ->getMock();

            \MassiveAction::processMassiveActionsForOneItemtype(
                $ma,
                $obj,
                [$id]
            );
            $transfer->moveItems([$itemtype => [$id]], $dentity, [$id]);
            unset($_SESSION['glpitransfer_list']);

            $this->assertTrue($obj->getFromDB($id));
            $this->assertSame(
                $dentity,
                $obj->fields['entities_id'],
                "Transfer has failed on $itemtype"
            );

            ++$count;
        }
    }

    public function testDomainTransfer()
    {
        $this->login();

        //Original entity
        $fentity = (int) getItemByTypeName('Entity', '_test_root_entity', true);
        //Destination entity
        $dentity = (int) getItemByTypeName('Entity', '_test_child_2', true);

        //records types
        $type_a = (int) getItemByTypeName('DomainRecordType', 'A', true);
        $type_cname = (int) getItemByTypeName('DomainRecordType', 'CNAME', true);

        $domain = new \Domain();
        $record = new \DomainRecord();

        $did = (int) $domain->add([
            'name'         => 'glpi-project.org',
            'entities_id'  => $fentity,
        ]);
        $this->assertGreaterThan(0, $did);
        $this->assertTrue($domain->getFromDB($did));

        $this->assertGreaterThan(
            0,
            $record->add([
                'name'         => 'glpi-project.org.',
                'type'         => $type_a,
                'data'         => '127.0.1.1',
                'entities_id'  => $fentity,
                'domains_id'   => $did,
            ])
        );

        $this->assertGreaterThan(
            0,
            $record->add([
                'name'         => 'www.glpi-project.org.',
                'type'         => $type_cname,
                'data'         => 'glpi-project.org.',
                'entities_id'  => $fentity,
                'domains_id'   => $did,
            ])
        );

        $this->assertGreaterThan(
            0,
            $record->add([
                'name'         => 'doc.glpi-project.org.',
                'type'         => $type_cname,
                'data'         => 'glpi-doc.rtfd.io',
                'entities_id'  => $fentity,
                'domains_id'   => $did,
            ])
        );

        //transfer to another entity
        $transfer = new \Transfer();

        $ma = $this->getMockBuilder(\MassiveAction::class)
            ->disableOriginalConstructor()
            ->getMock();

        \MassiveAction::processMassiveActionsForOneItemtype(
            $ma,
            $domain,
            [$did]
        );
        $transfer->moveItems(['Domain' => [$did]], $dentity, [$did]);
        unset($_SESSION['glpitransfer_list']);

        $this->assertTrue($domain->getFromDB($did));
        $this->assertSame($dentity, (int) $domain->fields['entities_id']);

        global $DB;
        $records = $DB->request([
            'FROM'   => $record->getTable(),
            'WHERE'  => [
                'domains_id' => $did,
            ],
        ]);

        $this->assertSame(3, count($records));
        foreach ($records as $rec) {
            $this->assertSame($dentity, (int) $rec['entities_id']);
        }
    }

    private function testKeepSoftwareOptionData(): array
    {
        $test_entity = getItemByTypeName('Entity', '_test_root_entity', true);

        // Create test computers
        $computers_to_create = [
            'test_transfer_pc_1',
            'test_transfer_pc_2',
            'test_transfer_pc_3',
            'test_transfer_pc_4',
        ];
        foreach ($computers_to_create as $computer_name) {
            $computer = new Computer();
            $computers_id = $computer->add([
                'name'        => $computer_name,
                'entities_id' => $test_entity,
            ]);
            $this->assertGreaterThan(0, $computers_id);
        }

        // Create test software
        $softwares_to_create = [
            'test_transfer_software_1',
            'test_transfer_software_2',
            'test_transfer_software_3',
        ];
        foreach ($softwares_to_create as $software_name) {
            $software = new Software();
            $softwares_id = $software->add([
                'name'        => $software_name,
                'entities_id' => $test_entity,
            ]);
            $this->assertGreaterThan(0, $softwares_id);
        }

        // Create test software versions
        $softwareversion_ids = [];
        $software_versions_to_create = [
            'test_transfer_software_1' => ['V1', 'V2'],
            'test_transfer_software_2' => ['V1', 'V2'],
            'test_transfer_software_3' => ['V1', 'V2'],
        ];
        foreach ($software_versions_to_create as $software_name => $versions) {
            foreach ($versions as $version) {
                $softwareversion = new SoftwareVersion();
                $softwareversions_id = $softwareversion->add([
                    'name'         => $software_name . '::' . $version,
                    'softwares_id' => getItemByTypeName('Software', $software_name, true),
                    'entities_id'  => $test_entity,
                ]);
                $this->assertGreaterThan(0, $softwareversions_id);
                $softwareversion_ids[] = $softwareversions_id;
            }
        }

        // Link software and computers
        $item_softwareversion_ids = [];
        $item_softwareversion_to_create = [
            'test_transfer_pc_1' => ['test_transfer_software_1::V1', 'test_transfer_software_2::V1'],
            'test_transfer_pc_2' => ['test_transfer_software_1::V2', 'test_transfer_software_2::V2'],
            'test_transfer_pc_3' => ['test_transfer_software_2::V1', 'test_transfer_software_3::V2'],
            'test_transfer_pc_4' => ['test_transfer_software_1::V2', 'test_transfer_software_3::V1'],
        ];
        foreach ($item_softwareversion_to_create as $computer_name => $versions) {
            foreach ($versions as $version) {
                $item_softwareversion = new Item_SoftwareVersion();
                $item_softwareversions_id = $item_softwareversion->add([
                    'items_id'     => getItemByTypeName('Computer', $computer_name, true),
                    'itemtype'     => 'Computer',
                    'softwareversions_id' => getItemByTypeName('SoftwareVersion', $version, true),
                    'entities_id'  => $test_entity,
                ]);
                $this->assertGreaterThan(0, $item_softwareversions_id);
                $item_softwareversion_ids[] = $item_softwareversions_id;
            }
        }

        return [
            [
                'items' => [
                    'Computer' => [
                        getItemByTypeName('Computer', 'test_transfer_pc_1', true),
                        getItemByTypeName('Computer', 'test_transfer_pc_2', true),
                    ],
                ],
                'entities_id_destination' => $test_entity,
                'transfer_options'        => ['keep_software' => 1],
                'expected_softwares_after_transfer' => [
                    'Computer' => [
                        getItemByTypeName('Computer', 'test_transfer_pc_1', true) => [
                            $item_softwareversion_ids[0],
                            $item_softwareversion_ids[1],
                        ],
                        getItemByTypeName('Computer', 'test_transfer_pc_2', true) => [
                            $item_softwareversion_ids[2],
                            $item_softwareversion_ids[3],
                        ],
                    ],
                ],
                'expected_softwares_version_after_transfer' => [
                    'Software' => [
                        getItemByTypeName('Software', 'test_transfer_software_1', true) => [
                            $softwareversion_ids[0],
                            $softwareversion_ids[1],
                        ],
                        getItemByTypeName('Software', 'test_transfer_software_2', true) => [
                            $softwareversion_ids[2],
                            $softwareversion_ids[3],
                        ],
                    ],
                ],
            ],
            [
                'items' => [
                    'Computer' => [
                        getItemByTypeName('Computer', 'test_transfer_pc_3', true),
                        getItemByTypeName('Computer', 'test_transfer_pc_4', true),
                    ],
                ],
                'entities_id_destination' => $test_entity,
                'transfer_options'        => ['keep_software' => 0],
                'expected_softwares_after_transfer' => [
                    'Computer' => [
                        getItemByTypeName('Computer', 'test_transfer_pc_3', true) => [],
                        getItemByTypeName('Computer', 'test_transfer_pc_4', true) => [],
                    ],
                ],
                'expected_softwares_version_after_transfer' => [
                    'Software' => [
                        getItemByTypeName('Software', 'test_transfer_software_1', true) => [],
                        getItemByTypeName('Software', 'test_transfer_software_2', true) => [],
                    ],
                ],
            ],
        ];
    }

    public function testKeepSoftwareOption(): void
    {
        $data = $this->testKeepSoftwareOptionData();
        foreach ($data as $test_row) {
            $items = $test_row['items'];
            $entities_id_destination = $test_row['entities_id_destination'];
            $transfer_options = $test_row['transfer_options'];
            $expected_softwares_after_transfer = $test_row['expected_softwares_after_transfer'];
            $expected_softwares_version_after_transfer = $test_row['expected_softwares_version_after_transfer'];

            $tranfer = new \Transfer();
            $tranfer->moveItems($items, $entities_id_destination, $transfer_options);

            foreach ($items as $itemtype => $ids) {
                foreach ($ids as $id) {
                    //check item_software
                    $item_softwareversion = new Item_SoftwareVersion();
                    $data = $item_softwareversion->find([
                        'items_id' => $id,
                        'itemtype' => $itemtype,
                    ]);

                    $found_ids = array_column($data, 'id');
                    $this->assertEquals($expected_softwares_after_transfer[$itemtype][$id], $found_ids);

                    if (!empty($data)) {
                        foreach ($data as $db_field) {
                            //check entity foreach Item_SoftwareVersion
                            $this->assertEquals($entities_id_destination, $db_field['entities_id']);

                            //check SoftwareVersion attached to Item_SoftwareVersion
                            $softwareversion = new SoftwareVersion();
                            $softwareversion->getFromDB($db_field['softwareversions_id']);

                            $softversion_id = $softwareversion->fields['id'];
                            $soft_id = $softwareversion->fields['softwares_id'];

                            //check SoftwareVersion exist from expected
                            $this->assertTrue(in_array($softversion_id, $expected_softwares_version_after_transfer['Software'][$soft_id]));
                            //check entity for SoftwareVersion
                            $this->assertEquals($entities_id_destination, $softwareversion->fields['entities_id']);
                        }
                    }
                }
            }
        }
    }

    public function testCleanSoftware()
    {
        global $DB;
        $test_entity = getItemByTypeName('Entity', '_test_root_entity', true);
        $dest_entity = getItemByTypeName('Entity', '_test_child_1', true);

        // Create test computers

        $computer = new Computer();
        $computers_id_1 = $computer->add([
            'name'        => 'test_transfer_pc_1',
            'entities_id' => $test_entity,
        ]);
        $this->assertGreaterThan(0, $computers_id_1);
        $computers_id_2 = $computer->add([
            'name'        => 'test_transfer_pc_2',
            'entities_id' => $test_entity,
        ]);
        $this->assertGreaterThan(0, $computers_id_2);

        // Create test software and versions. One software is linked to both computers, the other is linked to only one.
        $software = new Software();
        $software_id_1 = $software->add([
            'name'        => 'test_transfer_software_1',
            'entities_id' => $test_entity,
        ]);
        $this->assertGreaterThan(0, $software_id_1);
        $software_id_2 = $software->add([
            'name'        => 'test_transfer_software_2',
            'entities_id' => $test_entity,
        ]);
        $this->assertGreaterThan(0, $software_id_2);
        $softwareversion = new SoftwareVersion();
        $softwareversion_id_1 = $softwareversion->add([
            'name'         => 'test_transfer_software_1::V1',
            'softwares_id' => $software_id_1,
            'entities_id'  => $test_entity,
        ]);
        $this->assertGreaterThan(0, $softwareversion_id_1);
        $softwareversion_id_2 = $softwareversion->add([
            'name'         => 'test_transfer_software_1::V2',
            'softwares_id' => $software_id_2,
            'entities_id'  => $test_entity,
        ]);
        $this->assertGreaterThan(0, $softwareversion_id_2);

        $item_softwareversion = new Item_SoftwareVersion();
        $item_softwareversion_id_1 = $item_softwareversion->add([
            'items_id'     => $computers_id_1,
            'itemtype'     => 'Computer',
            'softwareversions_id' => $softwareversion_id_1,
            'entities_id'  => $test_entity,
        ]);
        $this->assertGreaterThan(0, $item_softwareversion_id_1);
        $item_softwareversion_id_2 = $item_softwareversion->add([
            'items_id'     => $computers_id_1,
            'itemtype'     => 'Computer',
            'softwareversions_id' => $softwareversion_id_2,
            'entities_id'  => $test_entity,
        ]);
        $this->assertGreaterThan(0, $item_softwareversion_id_2);
        $item_softwareversion_id_3 = $item_softwareversion->add([
            'items_id'     => $computers_id_2,
            'itemtype'     => 'Computer',
            'softwareversions_id' => $softwareversion_id_1,
            'entities_id'  => $test_entity,
        ]);
        $this->assertGreaterThan(0, $item_softwareversion_id_3);

        // Transfer the first computer to another entity
        $transfer = new \Transfer();
        $transfer->moveItems(['Computer' => [$computers_id_1]], $dest_entity, ['keep_software' => 1, 'clean_software' => 1]);

        // Software 2 should be deleted since it was only linked to the first computer
        //TODO Why not just move?
        $this->assertTrue($software->getFromDB($software_id_2));
        $this->assertEquals(1, $software->fields['is_deleted']);
        // There should be a non-deleted copy of software 2 in the destination entity
        $it = $DB->request([
            'SELECT' => ['id'],
            'FROM'   => $software::getTable(),
            'WHERE'  => [
                'name' => 'test_transfer_software_2',
                'entities_id' => $dest_entity,
                'is_deleted' => 0,
            ],
        ]);
        $this->assertCount(1, $it);
        $software_id_2_dest = $it->current()['id'];

        // Transfer computer 1 back to the original entity but purge software instead
        $transfer = new \Transfer();
        $transfer->moveItems(['Computer' => [$computers_id_1]], $test_entity, ['keep_software' => 1, 'clean_software' => 2]);
        $this->assertFalse($software->getFromDB($software_id_2_dest));
    }

    protected function testKeepCertificateOptionData(): array
    {
        $test_entity = getItemByTypeName('Entity', '_test_root_entity', true);

        // Create test computers
        $computers_to_create = [
            'test_transfer_pc_1',
            'test_transfer_pc_2',
            'test_transfer_pc_3',
            'test_transfer_pc_4',
        ];
        foreach ($computers_to_create as $computer_name) {
            $computer = new Computer();
            $computers_id = $computer->add([
                'name'        => $computer_name,
                'entities_id' => $test_entity,
            ]);
            $this->assertGreaterThan(0, $computers_id);
        }

        // Create test certificates
        $certificates_to_create = [
            'test_transfer_certificate_1',
            'test_transfer_certificate_2',
            'test_transfer_certificate_3',
            'test_transfer_certificate_4',
        ];
        foreach ($certificates_to_create as $certificate_name) {
            $certificate = new Certificate();
            $certificates_id = $certificate->add([
                'name'        => $certificate_name,
                'entities_id' => $test_entity,
            ]);
            $this->assertGreaterThan(0, $certificates_id);
        }

        // Link certificates and computers
        $certificate_item_ids = [];
        $certificate_item_to_create = [
            'test_transfer_pc_1' => 'test_transfer_certificate_1',
            'test_transfer_pc_2' => 'test_transfer_certificate_2',
            'test_transfer_pc_3' => 'test_transfer_certificate_3',
            'test_transfer_pc_4' => 'test_transfer_certificate_4',
        ];
        foreach ($certificate_item_to_create as $computer_name => $certificate) {
            $certificate_item = new Certificate_Item();
            $certificate_items_id = $certificate_item->add([
                'items_id'     => getItemByTypeName('Computer', $computer_name, true),
                'itemtype'     => 'Computer',
                'certificates_id' => getItemByTypeName('Certificate', $certificate, true),
            ]);
            $this->assertGreaterThan(0, $certificate_items_id);
            $certificate_item_ids[] = $certificate_items_id;
        }

        return [
            [
                'items' => [
                    'Computer' => [
                        getItemByTypeName('Computer', 'test_transfer_pc_1', true),
                        getItemByTypeName('Computer', 'test_transfer_pc_2', true),
                    ],
                ],
                'entities_id_destination' => $test_entity,
                'transfer_options'        => ['keep_certificate' => 1],
                'expected_certificates_after_transfer' => [
                    'Computer' => [
                        getItemByTypeName('Computer', 'test_transfer_pc_1', true) => [
                            $certificate_item_ids[0],
                        ],
                        getItemByTypeName('Computer', 'test_transfer_pc_2', true) => [
                            $certificate_item_ids[1],
                        ],
                    ],
                ],
            ],
            [
                'items' => [
                    'Computer' => [
                        getItemByTypeName('Computer', 'test_transfer_pc_3', true),
                        getItemByTypeName('Computer', 'test_transfer_pc_4', true),
                    ],
                ],
                'entities_id_destination' => $test_entity,
                'transfer_options'        => ['keep_certificate' => 0],
                'expected_certificates_after_transfer' => [
                    'Computer' => [
                        getItemByTypeName('Computer', 'test_transfer_pc_3', true) => [],
                        getItemByTypeName('Computer', 'test_transfer_pc_4', true) => [],
                    ],
                ],
            ],
        ];
    }

    public function testKeepCertificateOption(): void
    {
        $data = $this->testKeepCertificateOptionData();
        foreach ($data as $test_row) {
            $items = $test_row['items'];
            $entities_id_destination = $test_row['entities_id_destination'];
            $transfer_options = $test_row['transfer_options'];
            $expected_certificates_after_transfer = $test_row['expected_certificates_after_transfer'];

            $tranfer = new \Transfer();
            $tranfer->moveItems($items, $entities_id_destination, $transfer_options);

            foreach ($items as $itemtype => $ids) {
                foreach ($ids as $id) {
                    $certificate_item = new Certificate_Item();
                    $data = $certificate_item->find([
                        'items_id' => $id,
                        'itemtype' => $itemtype,
                    ]);
                    $found_ids = array_column($data, 'id');
                    $this->assertEquals(
                        $expected_certificates_after_transfer[$itemtype][$id],
                        $found_ids
                    );
                }
            }
        }
    }


    public function testKeepLocationTransfer()
    {
        $this->login();

        //Original entity
        $fentity = (int) getItemByTypeName('Entity', '_test_root_entity', true);
        //Destination entity
        $dentity = (int) getItemByTypeName('Entity', '_test_child_2', true);

        $location = new \Location();
        $location_id = (int) $location->add([
            'name'          => 'location',
            'entities_id'   => $fentity,
            'is_recursive'  => 1,
        ]);
        $this->assertGreaterThan(0, $location_id);
        $this->assertTrue($location->getFromDB($location_id));

        $ticket = new \Ticket();
        $ticket_id = (int) $ticket->add([
            'name'         => 'ticket',
            'content'         => 'content ticket',
            'locations_id' => $location_id,
            'entities_id'  => $fentity,
        ]);
        $this->assertGreaterThan(0, $ticket_id);
        $this->assertTrue($ticket->getFromDB($ticket_id));

        //transfer to another entity
        $transfer = new \Transfer();
        $this->assertTrue($transfer->getFromDB(1));

        //update transfer model to keep location
        $transfer->fields["keep_location"] = 1;
        $this->assertTrue($transfer->update($transfer->fields));

        $item_to_transfer = [\Ticket::class => [$ticket_id => $ticket_id]];
        $transfer->moveItems($item_to_transfer, $dentity, $transfer->fields);

        //reload ticket
        $this->assertTrue($ticket->getFromDB($ticket_id));
        $this->assertEquals($location_id, $ticket->fields['locations_id']);
    }

    public function testEmptyLocationTransfer()
    {
        $this->login();

        //Original entity
        $fentity = (int) getItemByTypeName('Entity', '_test_root_entity', true);
        //Destination entity
        $dentity = (int) getItemByTypeName('Entity', '_test_child_2', true);

        $location = new \Location();
        $location_id = (int) $location->add([
            'name'          => 'location',
            'entities_id'   => $fentity,
            'is_recursive'  => 1,
        ]);
        $this->assertGreaterThan(0, $location_id);
        $this->assertTrue($location->getFromDB($location_id));


        $ticket = new \Ticket();
        $ticket_id = (int) $ticket->add([
            'name'         => 'ticket',
            'content'         => 'content ticket',
            'locations_id' => $location_id,
            'entities_id'  => $fentity,
        ]);
        $this->assertGreaterThan(0, $ticket_id);
        $this->assertTrue($ticket->getFromDB($ticket_id));


        //transfer to another entity
        $transfer = new \Transfer();
        $this->assertTrue($transfer->getFromDB(1));

        //update transfer model to empty location
        $transfer->fields["keep_location"] = 0;
        $this->assertTrue($transfer->update($transfer->fields));

        $item_to_transfer = [\Ticket::class => [$ticket_id => $ticket_id]];
        $transfer->moveItems($item_to_transfer, $dentity, $transfer->fields);

        //reload ticket
        $this->assertTrue($ticket->getFromDB($ticket_id));
        $this->assertEquals(0, $ticket->fields['locations_id']);
    }

    public function testTransferLinkedSuppliers()
    {
        $this->login();
        $source_entity = getItemByTypeName('Entity', '_test_child_1', true);
        $destination_entity = getItemByTypeName('Entity', '_test_child_2', true);

        $supplier = new \Supplier();
        $supplier_id = $supplier->add([
            'name' => __FUNCTION__,
            'entities_id' => $source_entity,
        ]);
        $this->assertGreaterThan(0, $supplier_id);

        $ticket = new \Ticket();
        $ticket_id = $ticket->add([
            'name' => 'ticket',
            'content' => 'content ticket',
            'entities_id' => $source_entity,
        ]);
        $this->assertGreaterThan(0, $ticket_id);

        $supplier_ticket = new \Supplier_Ticket();
        $this->assertGreaterThan(0, $supplier_ticket->add([
            'tickets_id'   => $ticket_id,
            'suppliers_id' => $supplier_id,
            'type'         => \CommonITILActor::ASSIGN,
        ]));

        $transfer = new \Transfer();
        $transfer->moveItems(['Ticket' => [$ticket_id]], $destination_entity, []);

        $this->assertTrue($ticket->getFromDB($ticket_id));
        $this->assertEquals($destination_entity, $ticket->fields['entities_id']);

        $suppliers = $supplier->find([
            'name' => __FUNCTION__,
            'entities_id' => $destination_entity,
        ]);
        $this->assertCount(1, $suppliers);
        $supplier = reset($suppliers);
        $this->assertCount(1, $supplier_ticket->find([
            'tickets_id' => $ticket_id,
            'suppliers_id' => $supplier['id'],
            'type'         => \CommonITILActor::ASSIGN,
        ]));
    }

    public function testTransferTaskCategory()
    {
        $this->login();
        $source_entity = getItemByTypeName('Entity', '_test_child_1', true);
        $destination_entity = getItemByTypeName('Entity', '_test_child_2', true);

        $ticket = new \Ticket();
        $ticket_id = $ticket->add([
            'name' => 'ticket',
            'content' => 'content ticket',
            'entities_id' => $source_entity,
        ]);
        $this->assertGreaterThan(0, $ticket_id);

        $task_cat = new \TaskCategory();
        $task_cat_id = $task_cat->add([
            'name' => __FUNCTION__,
            'entities_id' => $source_entity,
        ]);
        $this->assertGreaterThan(0, $task_cat_id);

        $ticket_task = new \TicketTask();
        $task_id = $ticket_task->add([
            'name' => 'task',
            'content' => 'content task',
            'taskcategories_id' => $task_cat_id,
            'tickets_id' => $ticket_id,
        ]);
        $this->assertGreaterThan(0, $task_id);

        $transfer = new \Transfer();
        $transfer->moveItems(['Ticket' => [$ticket_id]], $destination_entity, []);
        $this->assertTrue($ticket->getFromDB($ticket_id));
        $this->assertEquals($destination_entity, $ticket->fields['entities_id']);

        $task_cats = $task_cat->find([
            'name' => __FUNCTION__,
            'entities_id' => $destination_entity,
        ]);
        $this->assertCount(1, $task_cats);
        $task_cat = reset($task_cats);
        $this->assertTrue($ticket_task->getFromDB($task_id));
        $this->assertEquals($task_cat['id'], $ticket_task->fields['taskcategories_id']);
    }

    public function testGenericAssetTransfer(): void
    {
        $this->login();
        $source_entity = getItemByTypeName('Entity', '_test_child_1', true);
        $destination_entity = getItemByTypeName('Entity', '_test_child_2', true);

        //create Smartphone generic asset
        $definition = $this->initAssetDefinition(
            system_name: 'Smartphone' . $this->getUniqueString()
        );
        $classname  = $definition->getAssetClassName();

        $item = new $classname();
        $item_id = $item->add([
            'name' => 'To transfer Smartphone',
            'entities_id' => $source_entity,
        ]);
        $this->assertGreaterThan(0, $item_id);

        $transfer = new \Transfer();
        $transfer->moveItems([$classname => [$item_id]], $destination_entity, []);
        $this->assertTrue($item->getFromDB($item_id));
        $this->assertEquals($destination_entity, $item->fields['entities_id']);
    }

    public function testTicketWithDocumentTransfer()
    {
        $this->login();

        //Original entity
        $fentity = (int) getItemByTypeName('Entity', '_test_root_entity', true);
        //Destination entity
        $dentity = (int) getItemByTypeName('Entity', '_test_child_2', true);

        $ticket = new \Ticket();
        $ticket_id = (int) $ticket->add([
            'name' => 'ticket',
            'content' => 'content ticket',
            'entities_id' => $fentity,
        ]);
        $this->assertGreaterThan(0, $ticket_id);
        $this->assertTrue($ticket->getFromDB($ticket_id));

        // Create a document stub.
        $mdoc = $this->getMockBuilder(\Document::class)
            ->onlyMethods(['moveUploadedDocument'])
            ->getMock();
        $mdoc->method('moveUploadedDocument')->willReturn(true);

        $input['upload_file'] = 'filename.ext';
        $input['itemtype'] = \Ticket::class;
        $input['items_id'] = $ticket_id;
        $input['entities_id'] = $fentity;

        $docid = $mdoc->add($input);
        $this->assertGreaterThan(0, $docid);

        $doc_item = new \Document_Item();
        $this->assertTrue($doc_item->getFromDBByCrit(['documents_id' => $docid]));
        $this->assertSame($fentity, $doc_item->fields['entities_id']);

        //transfer to another entity
        $transfer = new \Transfer();
        $this->assertTrue($transfer->getFromDB(1));

        //update transfer model to keep documents
        $transfer->fields["keep_document"] = 1;
        $this->assertTrue($transfer->update($transfer->fields));

        $item_to_transfer = [\Ticket::class => [$ticket_id => $ticket_id]];
        $transfer->moveItems($item_to_transfer, $dentity, $transfer->fields);

        //reload ticket
        $this->assertTrue($ticket->getFromDB($ticket_id));
        $doc_item = new \Document_Item();
        $this->assertTrue($doc_item->getFromDBByCrit(['documents_id' => $docid]));
        $this->assertSame($dentity, $doc_item->fields['entities_id']);
    }
}
