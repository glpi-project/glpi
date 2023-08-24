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

use Certificate;
use Certificate_Item;
use Computer;
use DbTestCase;
use Item_SoftwareVersion;
use Software;
use SoftwareVersion;

/* Test for inc/transfer.class.php */

class Transfer extends DbTestCase
{
    public function testTransfer()
    {
        $this->login();

       //Original entity
        $fentity = (int)getItemByTypeName('Entity', '_test_root_entity', true);
       //Destination entity
        $dentity = (int)getItemByTypeName('Entity', '_test_child_2', true);

        $location_id = getItemByTypeName('Location', '_location01', true);

        $itemtypeslist = $this->getClasses(
            'searchOptions',
            [
                '/^Rule.*/',
                '/^Common.*/',
                '/^DB.*/',
                '/^SlaLevel.*/',
                '/^OlaLevel.*/',
                'Event',
                'Glpi\\Event',
                'KnowbaseItem',
                '/SavedSearch.*/',
                '/.*Notification.*/',
                '/^Device.*/',
                '/^Network.*/',
                'IPNetwork',
                'FQDN',
                '/^SoftwareVersion.*/',
                '/^SoftwareLicense.*/',
                'FieldUnicity',
                'PurgeLogs',
                'TicketRecurrent',
                'Agent',
                'USBVendor',
                'PCIVendor',
                'PendingReasonCron',
                'Netpoint',
            ]
        );

        $fields_values = [
            'name'            => 'Object to transfer',
            'entities_id'     => $fentity,
            'content'         => 'A content',
            'definition_time' => 'hour',
            'number_time'     => 4,
            'begin_date'      => '2020-01-01',
        ];

        $count = 0;
        foreach ($itemtypeslist as $itemtype) {
            if (is_a($itemtype, \CommonDBConnexity::class, true)) {
                // Do not check transfer of child items, they are not supposed to be transfered directly.
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
            $this->integer((int)$id)->isGreaterThan(0, "Cannot add $itemtype");
            $this->boolean($obj->getFromDB($id))->isTrue();

           //transer to another entity
            $transfer = new \Transfer();

            $this->mockGenerator->orphanize('__construct');
            $ma = new \mock\MassiveAction([], [], 'process');

            \MassiveAction::processMassiveActionsForOneItemtype(
                $ma,
                $obj,
                [$id]
            );
            $transfer->moveItems([$itemtype => [$id]], $dentity, [$id]);
            unset($_SESSION['glpitransfer_list']);

            $this->boolean($obj->getFromDB($id))->isTrue();
            $this->integer((int)$obj->fields['entities_id'])->isidenticalTo($dentity, "Transfer has failed on $itemtype");

            ++$count;
        }
    }

    public function testDomainTransfer()
    {
        $this->login();

       //Original entity
        $fentity = (int)getItemByTypeName('Entity', '_test_root_entity', true);
       //Destination entity
        $dentity = (int)getItemByTypeName('Entity', '_test_child_2', true);

       //records types
        $type_a = (int)getItemByTypeName('DomainRecordType', 'A', true);
        $type_cname = (int)getItemByTypeName('DomainRecordType', 'CNAME', true);

        $domain = new \Domain();
        $record = new \DomainRecord();

        $did = (int)$domain->add([
            'name'         => 'glpi-project.org',
            'entities_id'  => $fentity
        ]);
        $this->integer($did)->isGreaterThan(0);
        $this->boolean($domain->getFromDB($did))->isTrue();

        $this->integer(
            (int)$record->add([
                'name'         => 'glpi-project.org.',
                'type'         => $type_a,
                'data'         => '127.0.1.1',
                'entities_id'  => $fentity,
                'domains_id'   => $did
            ])
        )->isGreaterThan(0);

        $this->integer(
            (int)$record->add([
                'name'         => 'www.glpi-project.org.',
                'type'         => $type_cname,
                'data'         => 'glpi-project.org.',
                'entities_id'  => $fentity,
                'domains_id'   => $did
            ])
        )->isGreaterThan(0);

        $this->integer(
            (int)$record->add([
                'name'         => 'doc.glpi-project.org.',
                'type'         => $type_cname,
                'data'         => 'glpi-doc.rtfd.io',
                'entities_id'  => $fentity,
                'domains_id'   => $did
            ])
        )->isGreaterThan(0);

       //transer to another entity
        $transfer = new \Transfer();

        $this->mockGenerator->orphanize('__construct');
        $ma = new \mock\MassiveAction([], [], 'process');

        \MassiveAction::processMassiveActionsForOneItemtype(
            $ma,
            $domain,
            [$did]
        );
        $transfer->moveItems(['Domain' => [$did]], $dentity, [$did]);
        unset($_SESSION['glpitransfer_list']);

        $this->boolean($domain->getFromDB($did))->isTrue();
        $this->integer((int)$domain->fields['entities_id'])->isidenticalTo($dentity);

        global $DB;
        $records = $DB->request([
            'FROM'   => $record->getTable(),
            'WHERE'  => [
                'domains_id' => $did
            ]
        ]);

        $this->integer(count($records))->isidenticalTo(3);
        foreach ($records as $rec) {
            $this->integer((int)$rec['entities_id'])->isidenticalTo($dentity);
        }
    }

    protected function testKeepSoftwareOptionProvider(): array
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
            $this->integer($computers_id)->isGreaterThan(0);
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
            $this->integer($softwares_id)->isGreaterThan(0);
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
                $this->integer($softwareversions_id)->isGreaterThan(0);
                $softwareversion_ids[] = $softwareversions_id;
            }
        }

       // Link softwares and computers
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
                $this->integer($item_softwareversions_id)->isGreaterThan(0);
                $item_softwareversion_ids[] = $item_softwareversions_id;
            }
        }

        return [
            [
                'items' => [
                    'Computer' => [
                        getItemByTypeName('Computer', 'test_transfer_pc_1', true),
                        getItemByTypeName('Computer', 'test_transfer_pc_2', true),
                    ]
                ],
                'entities_id_destination' => $test_entity,
                'transfer_options'        => ['keep_software' => 1],
                'expected_softwares_after_transfer' => [
                    'Computer' => [
                        getItemByTypeName('Computer', 'test_transfer_pc_1', true) => [
                            $item_softwareversion_ids[0],
                            $item_softwareversion_ids[1]
                        ],
                        getItemByTypeName('Computer', 'test_transfer_pc_2', true) => [
                            $item_softwareversion_ids[2],
                            $item_softwareversion_ids[3]
                        ],
                    ]
                ],
                'expected_softwares_version_after_transfer' => [
                    'Software' => [
                        getItemByTypeName('Software', 'test_transfer_software_1', true) => [
                            $softwareversion_ids[0],
                            $softwareversion_ids[1],
                        ],
                        getItemByTypeName('Software', 'test_transfer_software_2', true) => [
                            $softwareversion_ids[2],
                            $softwareversion_ids[3]
                        ],
                    ]
                ]
            ],
            [
                'items' => [
                    'Computer' => [
                        getItemByTypeName('Computer', 'test_transfer_pc_3', true),
                        getItemByTypeName('Computer', 'test_transfer_pc_4', true),
                    ]
                ],
                'entities_id_destination' => $test_entity,
                'transfer_options'        => ['keep_software' => 0],
                'expected_softwares_after_transfer' => [
                    'Computer' => [
                        getItemByTypeName('Computer', 'test_transfer_pc_3', true) => [],
                        getItemByTypeName('Computer', 'test_transfer_pc_4', true) => [],
                    ]
                ],
                'expected_softwares_version_after_transfer' => [
                    'Software' => [
                        getItemByTypeName('Software', 'test_transfer_software_1', true) => [],
                        getItemByTypeName('Software', 'test_transfer_software_2', true) => [],
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider testKeepSoftwareOptionProvider
     */
    public function testKeepSoftwareOption(
        array $items,
        int $entities_id_destination,
        array $transfer_options,
        array $expected_softwares_after_transfer,
        array $expected_softwares_version_after_transfer
    ): void {
        $tranfer = new \Transfer();
        $tranfer->moveItems($items, $entities_id_destination, $transfer_options);

        foreach ($items as $itemtype => $ids) {
            foreach ($ids as $id) {
                //check item_software
                $item_softwareversion = new Item_SoftwareVersion();
                $data = $item_softwareversion->find([
                    'items_id' => $id,
                    'itemtype' => $itemtype
                ]);

                $found_ids = array_column($data, 'id');
                $this->array($found_ids)->isEqualTo($expected_softwares_after_transfer[$itemtype][$id]);

                if (!empty($data)) {
                    foreach ($data as $db_field) {
                        //check entity foreach Item_SoftwareVersion
                        $this->integer($db_field['entities_id'])->isEqualTo($entities_id_destination);

                        //check SoftwareVersion attached to Item_SoftwareVersion
                        $softwareversion = new SoftwareVersion();
                        $softwareversion->getFromDB($db_field['softwareversions_id']);

                        $softversion_id = $softwareversion->fields['id'];
                        $soft_id = $softwareversion->fields['softwares_id'];

                        //check SoftwareVersion exist from expected
                        $this->array($expected_softwares_version_after_transfer['Software'][$soft_id])->contains($softversion_id);
                        //check entity for SoftwareVersion
                        $this->integer($softwareversion->fields['entities_id'])->isEqualTo($entities_id_destination);
                    }
                }
            }
        }
    }


    protected function testKeepCertificateOptionProvider(): array
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
            $this->integer($computers_id)->isGreaterThan(0);
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
            $this->integer($certificates_id)->isGreaterThan(0);
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
                'certificates_id' => getItemByTypeName('Certificate', $certificate, true)
            ]);
            $this->integer($certificate_items_id)->isGreaterThan(0);
            $certificate_item_ids[] = $certificate_items_id;
        }

        return [
            [
                'items' => [
                    'Computer' => [
                        getItemByTypeName('Computer', 'test_transfer_pc_1', true),
                        getItemByTypeName('Computer', 'test_transfer_pc_2', true),
                    ]
                ],
                'entities_id_destination' => $test_entity,
                'transfer_options'        => ['keep_certificate' => 1],
                'expected_certificates_after_transfer' => [
                    'Computer' => [
                        getItemByTypeName('Computer', 'test_transfer_pc_1', true) => [
                            $certificate_item_ids[0]
                        ],
                        getItemByTypeName('Computer', 'test_transfer_pc_2', true) => [
                            $certificate_item_ids[1]
                        ],
                    ]
                ]
            ],
            [
                'items' => [
                    'Computer' => [
                        getItemByTypeName('Computer', 'test_transfer_pc_3', true),
                        getItemByTypeName('Computer', 'test_transfer_pc_4', true),
                    ]
                ],
                'entities_id_destination' => $test_entity,
                'transfer_options'        => ['keep_certificate' => 0],
                'expected_certificates_after_transfer' => [
                    'Computer' => [
                        getItemByTypeName('Computer', 'test_transfer_pc_3', true) => [],
                        getItemByTypeName('Computer', 'test_transfer_pc_4', true) => [],
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider testKeepCertificateOptionProvider
     */
    public function testKeepCertificateOption(
        array $items,
        int $entities_id_destination,
        array $transfer_options,
        array $expected_certificates_after_transfer
    ): void {
        $tranfer = new \Transfer();
        $tranfer->moveItems($items, $entities_id_destination, $transfer_options);

        foreach ($items as $itemtype => $ids) {
            foreach ($ids as $id) {
                $certificate_item = new Certificate_Item();
                $data = $certificate_item->find([
                    'items_id' => $id,
                    'itemtype' => $itemtype
                ]);
                $found_ids = array_column($data, 'id');
                $this->array($found_ids)->isEqualTo($expected_certificates_after_transfer[$itemtype][$id]);
            }
        }
    }
}
