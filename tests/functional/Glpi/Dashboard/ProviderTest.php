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

namespace tests\units\Glpi\Dashboard;

use DbTestCase;
use Glpi\CustomAsset\Test01Asset;
use Glpi\CustomAsset\Test01AssetType;
use Glpi\Dashboard\Provider;
use PHPUnit\Framework\Attributes\DataProvider;
use Reminder;
use Reminder_User;
use User;

/* Test for inc/dashboard/provider.class.php */

class ProviderTest extends DbTestCase
{
    public function testNbTicketsByAgreementStatusAndTechnician()
    {
        global $DB;

        // Prepare context
        $slm = new \SLM();
        $this->assertGreaterThan(
            0,
            $slm->add([
                'name' => 'SLM',
            ])
        );

        $slaTto = new \SLA();
        $this->assertGreaterThan(
            0,
            $slaTto->add([
                'name' => 'sla tto',
                'type' => '1', // TTO
                'number_time' => 4,
                'definition_time' => 'hour',
                'slms_id' => $slm->getID(),
            ])
        );

        $slaTtr = new \SLA();
        $this->assertGreaterThan(
            0,
            $slaTtr->add([
                'name' => 'sla ttr',
                'type' => '0', // TTR
                'number_time' => 4,
                'definition_time' => 'hour',
                'slms_id' => $slm->getID(),
            ])
        );

        $ticket = new \Ticket();
        $ticket->add([
            'name' => "test dashboard card SLA / tech",
            'content' => 'foo',
            '_users_id_assign' => 2, // glpi
            'sla_id_tto'       => $slaTto->getID(),
            'sla_id_ttr'       => $slaTtr->getID(),
            'status'           => \Ticket::ASSIGNED,
        ]);
        $this->assertFalse($ticket->isNewItem());

        $ticket2 = new \Ticket();
        $ticket2->add([
            'name' => "test dashboard card SLA / tech",
            'content' => 'foo',
            '_users_id_assign' => 4, // tech
            'sla_id_tto'       => $slaTto->getID(),
            'sla_id_ttr'       => $slaTtr->getID(),
            'status'           => \Ticket::ASSIGNED,
        ]);
        $this->assertFalse($ticket2->isNewItem());

        $ticket3 = new \Ticket();
        $ticket3->add([
            'name' => "test dashboard card SLA / tech",
            'content' => 'foo',
            '_users_id_assign' => 4, // tech
            'sla_id_tto'       => $slaTto->getID(),
            'sla_id_ttr'       => $slaTtr->getID(),
            'status'           => \Ticket::ASSIGNED,
        ]);
        $this->assertFalse($ticket3->isNewItem());

        $output = Provider::nbTicketsByAgreementStatusAndTechnician();
        $expected = [
            'label' => "Tickets by SLA status and by technician",
            'data' => [
                'labels' => ['tech', 'glpi'],
                'series' => [
                    [
                        'name' => 'Late own and resolve',
                        'data' => [0, 0],
                    ],
                    [
                        'name' => 'Late resolve',
                        'data' => [0, 0],
                    ],
                    [
                        'name' => 'Late own',
                        'data' => [0, 0],
                    ],
                    [
                        'name' => 'On time',
                        'data' => [2, 1],
                    ],
                ],
            ],
            'icon' => 'ti ti-stopwatch',
        ];
        $this->assertEquals($expected, $output);

        $this->assertTrue(
            $DB->update(
                $ticket::getTable(),
                [
                    'date'                       => '2021-01-01 00:00',
                    'time_to_own'                => '2021-01-01 01:00',
                    'takeintoaccount_delay_stat' => 50000,
                ],
                [
                    'id' => $ticket->getID(),
                ]
            )
        );
        $this->assertTrue(
            $DB->update(
                $ticket2::getTable(),
                [
                    'date'                       => '2021-01-01 00:00',
                    'time_to_own'                => '2021-01-01 01:00',
                    'takeintoaccount_delay_stat' => 50000,
                ],
                [
                    'id' => $ticket2->getID(),
                ]
            )
        );
        $this->assertTrue($ticket->getFromDB($ticket->getID()));
        $this->assertTrue($ticket2->getFromDB($ticket2->getID()));

        $output = Provider::nbTicketsByAgreementStatusAndTechnician();
        $expected = [
            'label' => "Tickets by SLA status and by technician",
            'data' => [
                'labels' => ['glpi', 'tech'],
                'series' => [
                    [
                        'name' => 'Late own and resolve',
                        'data' => [0, 0],
                    ],
                    [
                        'name' => 'Late resolve',
                        'data' => [0, 0],
                    ],
                    [
                        'name' => 'Late own',
                        'data' => [1, 1],
                    ],
                    [
                        'name' => 'On time',
                        'data' => [0, 1],
                    ],
                ],
            ],
            'icon' => 'ti ti-stopwatch',
        ];
        $this->assertEquals($expected, $output);

        $this->assertTrue(
            $DB->update(
                $ticket::getTable(),
                [
                    'date'                       => '2021-01-01 00:00',
                    'time_to_own'                => '2021-01-01 01:00',
                    'takeintoaccount_delay_stat' => 60,
                    'solvedate'                  => '2021-02-01 00:00',
                    'time_to_resolve'            => '2021-01-02 00:00',
                ],
                [
                    'id' => $ticket->getID(),
                ]
            )
        );
        $this->assertTrue(
            $DB->update(
                $ticket2::getTable(),
                [
                    'date'                       => '2021-01-01 00:00',
                    'time_to_own'                => '2021-01-01 01:00',
                    'takeintoaccount_delay_stat' => 60,
                    'solvedate'                  => '2021-02-01 00:00',
                    'time_to_resolve'            => '2021-01-02 00:00',
                ],
                [
                    'id' => $ticket2->getID(),
                ]
            )
        );
        $this->assertTrue($ticket->getFromDB($ticket->getID()));
        $this->assertTrue($ticket2->getFromDB($ticket2->getID()));
        $output = Provider::nbTicketsByAgreementStatusAndTechnician();
        $expected = [
            'label' => "Tickets by SLA status and by technician",
            'data' => [
                'labels' => ['glpi', 'tech'],
                'series' => [
                    [
                        'name' => 'Late own and resolve',
                        'data' => [0, 0],
                    ],
                    [
                        'name' => 'Late resolve',
                        'data' => [1, 1],
                    ],
                    [
                        'name' => 'Late own',
                        'data' => [0, 0],
                    ],
                    [
                        'name' => 'On time',
                        'data' => [0, 1],
                    ],
                ],
            ],
            'icon' => 'ti ti-stopwatch',
        ];
        $this->assertEquals($expected, $output);

        $this->assertTrue(
            $DB->update(
                $ticket::getTable(),
                [
                    'date'                       => '2021-01-01 00:00',
                    'time_to_own'                => '2021-01-01 01:00',
                    'takeintoaccount_delay_stat' => 50000,
                    'solvedate'                  => '2021-02-01 00:00',
                    'time_to_resolve'            => '2021-01-02 00:00',
                ],
                [
                    'id' => $ticket->getID(),
                ]
            )
        );
        $this->assertTrue(
            $DB->update(
                $ticket2::getTable(),
                [
                    'date'                       => '2021-01-01 00:00',
                    'time_to_own'                => '2021-01-01 01:00',
                    'takeintoaccount_delay_stat' => 50000,
                    'solvedate'                  => '2021-02-01 00:00',
                    'time_to_resolve'            => '2021-01-02 00:00',
                ],
                [
                    'id' => $ticket2->getID(),
                ]
            )
        );
        $this->assertTrue(
            $DB->update(
                $ticket3::getTable(),
                [
                    'date'                       => '2021-01-01 00:00',
                    'time_to_own'                => '2021-01-01 01:00',
                    'takeintoaccount_delay_stat' => 50000,
                    'solvedate'                  => '2021-02-01 00:00',
                    'time_to_resolve'            => '2021-01-02 00:00',
                ],
                [
                    'id' => $ticket3->getID(),
                ]
            )
        );
        $this->assertTrue($ticket->getFromDB($ticket->getID()));
        $this->assertTrue($ticket2->getFromDB($ticket2->getID()));
        $this->assertTrue($ticket3->getFromDB($ticket3->getID()));
        $output = Provider::nbTicketsByAgreementStatusAndTechnician();
        $expected = [
            'label' => "Tickets by SLA status and by technician",
            'data' => [
                'labels' => ['tech', 'glpi'],
                'series' => [
                    [
                        'name' => 'Late own and resolve',
                        'data' => [2, 1],
                    ],
                    [
                        'name' => 'Late resolve',
                        'data' => [0, 0],
                    ],
                    [
                        'name' => 'Late own',
                        'data' => [0, 0],
                    ],
                    [
                        'name' => 'On time',
                        'data' => [0, 0],
                    ],
                ],
            ],
            'icon' => 'ti ti-stopwatch',
        ];
        $this->assertEquals($expected, $output);
    }


    public function testNbTicketsByAgreementStatusAndTechnicianGroup()
    {
        global $DB;

        // Prepare context
        $slm = new \SLM();
        $this->assertGreaterThan(
            0,
            $slm->add([
                'name' => 'SLM',
            ])
        );

        $slaTto = new \SLA();
        $this->assertGreaterThan(
            0,
            $slaTto->add([
                'name' => 'sla tto',
                'type' => '1', // TTO
                'number_time' => 4,
                'definition_time' => 'hour',
                'slms_id' => $slm->getID(),
            ])
        );

        $slaTtr = new \SLA();
        $this->assertGreaterThan(
            0,
            $slaTtr->add([
                'name' => 'sla ttr',
                'type' => '0', // TTR
                'number_time' => 4,
                'definition_time' => 'hour',
                'slms_id' => $slm->getID(),
            ])
        );

        $group = new \Group();
        $group->add([
            'entities_id' => 0,
            'name'        => 'group sla test',
            'level'       => 1,
            'groups_id'   => 0,
        ]);
        $this->assertFalse($group->isNewItem());

        $group2 = new \Group();
        $group2->add([
            'entities_id' => 0,
            'name'        => 'second group sla test',
            'level'       => 1,
            'groups_id'   => 0,
        ]);
        $this->assertFalse($group2->isNewItem());

        $ticket = new \Ticket();
        $ticket->add([
            'name' => "test dashboard card SLA / tech",
            'content' => 'foo',
            '_groups_id_assign' => $group->getID(),
            'sla_id_tto'       => $slaTto->getID(),
            'sla_id_ttr'       => $slaTtr->getID(),
            'status'           => \Ticket::ASSIGNED,
        ]);
        $this->assertFalse($ticket->isNewItem());

        $ticket2 = new \Ticket();
        $ticket2->add([
            'name' => "test dashboard card SLA / tech for second group",
            'content' => 'foo',
            '_groups_id_assign' => $group2->getID(),
            'sla_id_tto'       => $slaTto->getID(),
            'sla_id_ttr'       => $slaTtr->getID(),
            'status'           => \Ticket::ASSIGNED,
        ]);
        $this->assertFalse($ticket2->isNewItem());

        $ticket3 = new \Ticket();
        $ticket3->add([
            'name' => "test dashboard card SLA / tech for second group",
            'content' => 'foo',
            '_groups_id_assign' => $group2->getID(),
            'sla_id_tto'       => $slaTto->getID(),
            'sla_id_ttr'       => $slaTtr->getID(),
            'status'           => \Ticket::ASSIGNED,
        ]);
        $this->assertFalse($ticket3->isNewItem());

        $output = Provider::nbTicketsByAgreementStatusAndTechnicianGroup();
        $expected = [
            'label' => "Tickets by SLA status and by technician group",
            'data' => [
                'labels' => ['second group sla test', 'group sla test'],
                'series' => [
                    [
                        'name' => 'Late own and resolve',
                        'data' => [0, 0],
                    ],
                    [
                        'name' => 'Late resolve',
                        'data' => [0, 0],
                    ],
                    [
                        'name' => 'Late own',
                        'data' => [0, 0],
                    ],
                    [
                        'name' => 'On time',
                        'data' => [2, 1],
                    ],
                ],
            ],
            'icon' => 'ti ti-stopwatch',
        ];
        $this->assertEquals($expected, $output);

        $this->assertTrue(
            $DB->update(
                $ticket::getTable(),
                [
                    'date'                       => '2021-01-01 00:00',
                    'time_to_own'                => '2021-01-01 01:00',
                    'takeintoaccount_delay_stat' => 50000,
                ],
                [
                    'id' => $ticket->getID(),
                ]
            )
        );
        $this->assertTrue(
            $DB->update(
                $ticket2::getTable(),
                [
                    'date'                       => '2021-01-01 00:00',
                    'time_to_own'                => '2021-01-01 01:00',
                    'takeintoaccount_delay_stat' => 50000,
                ],
                [
                    'id' => $ticket2->getID(),
                ]
            )
        );
        $this->assertTrue($ticket->getFromDB($ticket->getID()));
        $this->assertTrue($ticket2->getFromDB($ticket2->getID()));

        $output = Provider::nbTicketsByAgreementStatusAndTechnicianGroup();
        $expected = [
            'label' => "Tickets by SLA status and by technician group",
            'data' => [
                'labels' => ['group sla test', 'second group sla test'],
                'series' => [
                    [
                        'name' => 'Late own and resolve',
                        'data' => [0, 0],
                    ],
                    [
                        'name' => 'Late resolve',
                        'data' => [0, 0],
                    ],
                    [
                        'name' => 'Late own',
                        'data' => [1, 1],
                    ],
                    [
                        'name' => 'On time',
                        'data' => [0, 1],
                    ],
                ],
            ],
            'icon' => 'ti ti-stopwatch',
        ];
        $this->assertEquals($expected, $output);

        $this->assertTrue(
            $DB->update(
                $ticket::getTable(),
                [
                    'date'                       => '2021-01-01 00:00',
                    'time_to_own'                => '2021-01-01 01:00',
                    'takeintoaccount_delay_stat' => 60,
                    'solvedate'                  => '2021-02-01 00:00',
                    'time_to_resolve'            => '2021-01-02 00:00',
                ],
                [
                    'id' => $ticket->getID(),
                ]
            )
        );
        $this->assertTrue(
            $DB->update(
                $ticket2::getTable(),
                [
                    'date'                       => '2021-01-01 00:00',
                    'time_to_own'                => '2021-01-01 01:00',
                    'takeintoaccount_delay_stat' => 60,
                    'solvedate'                  => '2021-02-01 00:00',
                    'time_to_resolve'            => '2021-01-02 00:00',
                ],
                [
                    'id' => $ticket2->getID(),
                ]
            )
        );
        $this->assertTrue($ticket->getFromDB($ticket->getID()));
        $this->assertTrue($ticket2->getFromDB($ticket2->getID()));
        $output = Provider::nbTicketsByAgreementStatusAndTechnicianGroup();
        $expected = [
            'label' => "Tickets by SLA status and by technician group",
            'data' => [
                'labels' => ['group sla test', 'second group sla test'],
                'series' => [
                    [
                        'name' => 'Late own and resolve',
                        'data' => [0, 0],
                    ],
                    [
                        'name' => 'Late resolve',
                        'data' => [1, 1],
                    ],
                    [
                        'name' => 'Late own',
                        'data' => [0, 0],
                    ],
                    [
                        'name' => 'On time',
                        'data' => [0, 1],
                    ],
                ],
            ],
            'icon' => 'ti ti-stopwatch',
        ];
        $this->assertEquals($expected, $output);

        $this->assertTrue(
            $DB->update(
                $ticket::getTable(),
                [
                    'date'                       => '2021-01-01 00:00',
                    'time_to_own'                => '2021-01-01 01:00',
                    'takeintoaccount_delay_stat' => 50000,
                    'solvedate'                  => '2021-02-01 00:00',
                    'time_to_resolve'            => '2021-01-02 00:00',
                ],
                [
                    'id' => $ticket->getID(),
                ]
            )
        );
        $this->assertTrue(
            $DB->update(
                $ticket2::getTable(),
                [
                    'date'                       => '2021-01-01 00:00',
                    'time_to_own'                => '2021-01-01 01:00',
                    'takeintoaccount_delay_stat' => 50000,
                    'solvedate'                  => '2021-02-01 00:00',
                    'time_to_resolve'            => '2021-01-02 00:00',
                ],
                [
                    'id' => $ticket2->getID(),
                ]
            )
        );
        $this->assertTrue(
            $DB->update(
                $ticket3::getTable(),
                [
                    'date'                       => '2021-01-01 00:00',
                    'time_to_own'                => '2021-01-01 01:00',
                    'takeintoaccount_delay_stat' => 50000,
                    'solvedate'                  => '2021-02-01 00:00',
                    'time_to_resolve'            => '2021-01-02 00:00',
                ],
                [
                    'id' => $ticket3->getID(),
                ]
            )
        );
        $this->assertTrue($ticket->getFromDB($ticket->getID()));
        $this->assertTrue($ticket2->getFromDB($ticket2->getID()));
        $this->assertTrue($ticket3->getFromDB($ticket3->getID()));
        $output = Provider::nbTicketsByAgreementStatusAndTechnicianGroup();
        $expected = [
            'label' => "Tickets by SLA status and by technician group",
            'data' => [
                'labels' => ['second group sla test', 'group sla test'],
                'series' => [
                    [
                        'name' => 'Late own and resolve',
                        'data' => [2, 1],
                    ],
                    [
                        'name' => 'Late resolve',
                        'data' => [0, 0],
                    ],
                    [
                        'name' => 'Late own',
                        'data' => [0, 0],
                    ],
                    [
                        'name' => 'On time',
                        'data' => [0, 0],
                    ],
                ],
            ],
            'icon' => 'ti ti-stopwatch',
        ];
        $this->assertEquals($expected, $output);
    }
    public static function itemProvider()
    {
        return [
            ['item' => new \Computer()],
            ['item' => new \Ticket()],
            ['item' => new \Item_DeviceSimcard()],
        ];
    }

    #[DataProvider('itemProvider')]
    public function testBigNumber(\CommonDBTM $item)
    {
        $this->login();

        $itemtype = $item->getType();
        $data = [
            Provider::bigNumberItem($item),
            call_user_func(['\\Glpi\\Dashboard\\Provider', "bigNumber$itemtype"]),
        ];

        foreach ($data as $result) {
            $this->assertArrayHasKey('number', $result);
            $this->assertArrayHasKey('url', $result);
            $this->assertArrayHasKey('label', $result);
            $this->assertArrayHasKey('icon', $result);
            if ($item::getType() !== 'Item_DeviceSimcard') {
                // Ignore count for simcards. None are added in Bootstrap process and is here for regression testing only.
                $this->assertGreaterThan(0, $result['number']);
            }
            $this->assertStringContainsString($item::getSearchURL(), $result['url']);
            //Verify URL doesn't have two query param joiners next to each other
            $this->assertStringNotContainsString('&&', $result['url']);
            $this->assertStringNotContainsString('?&', $result['url']);
            //Verify URL only has one ? joiner
            $this->assertLessThanOrEqual(1, substr_count($result['url'], '?'));
            $this->assertNotEmpty($result['label']);
            $this->assertEquals($item::getIcon(), $result['icon']);
        }
    }


    public static function ticketsCaseProvider()
    {
        return [
            ['case' => 'notold'],
            ['case' => 'late'],
            ['case' => 'waiting_validation'],
            ['case' => 'incoming'],
            ['case' => 'waiting'],
            ['case' => 'assigned'],
            ['case' => 'planned'],
            ['case' => 'solved'],
            ['case' => 'closed'],
            ['case' => 'status'],
        ];
    }


    #[DataProvider('ticketsCaseProvider')]
    public function testNbTicketsGeneric(string $case)
    {
        $result = Provider::nbTicketsGeneric($case);

        $this->assertArrayHasKey('number', $result);
        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertArrayHasKey('icon', $result);
        $this->assertArrayHasKey('s_criteria', $result);
        $this->assertArrayHasKey('itemtype', $result);
        $this->assertIsInt($result['number']);
        $this->assertStringContainsString(\Ticket::getSearchURL(), $result['url']);
        $this->assertIsString($result['icon']);
        $this->assertIsString($result['label']);
        $this->assertGreaterThan(0, count($result['s_criteria']));
        $this->assertEquals(\Ticket::class, $result['itemtype']);
    }


    public static function itemFKProvider()
    {
        return [
            ['item' => new \Computer(), 'fk_item' => new \Entity()],
            ['item' => new \Software(), 'fk_item' => new \Entity()],
            ['item' => new Test01Asset(), 'fk_item' => new Test01AssetType()],
        ];
    }


    #[DataProvider('itemFKProvider')]
    public function testNbItemByFk(\CommonDBTM $item, \CommonDBTM $fk_item)
    {
        $this->login();

        $result = Provider::nbItemByFk($item, $fk_item);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertArrayHasKey('icon', $result);

        foreach ($result['data'] as $data) {
            $this->assertArrayHasKey('number', $data);
            $this->assertArrayHasKey('label', $data);
            $this->assertArrayHasKey('url', $data);

            $this->assertGreaterThan(0, $data['number']);
            $this->assertIsString($data['label']);
            $this->assertStringContainsString($item::getSearchURL(), $data['url']);
        }
    }


    public function testTicketsOpened()
    {
        $result = Provider::ticketsOpened();
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('distributed', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertArrayHasKey('icon', $result);

        $this->assertFalse($result['distributed']);
        $this->assertIsString($result['icon']);
        $this->assertIsString($result['label']);

        foreach ($result['data'] as $data) {
            $this->assertArrayHasKey('number', $data);
            $this->assertArrayHasKey('label', $data);
            $this->assertArrayHasKey('url', $data);

            $this->assertGreaterThan(0, $data['number']);
            $this->assertIsString($data['label']);
            $this->assertStringContainsString(\Ticket::getSearchURL(), $data['url']);
        }
    }


    public function testGetTicketsEvolution()
    {
        $result = Provider::getTicketsEvolution();
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertArrayHasKey('icon', $result);

        $this->assertIsString($result['icon']);
        $this->assertIsString($result['label']);
        $this->assertArrayHasKey('labels', $result['data']);
        $this->assertArrayHasKey('series', $result['data']);
        $this->assertNotEmpty($result['data']['labels']);
        $this->assertNotEmpty($result['data']['series']);

        $nb_labels = count($result['data']['labels']);
        foreach ($result['data']['series'] as $serie) {
            $this->assertArrayHasKey('data', $serie);
            $this->assertCount($nb_labels, $serie['data']);

            foreach ($serie['data'] as $serie_data) {
                $this->assertIsInt($serie_data['value']);
                $this->assertStringContainsString(\Ticket::getSearchURL(), $serie_data['url']);
            }
        }
    }


    public function testGetTicketsStatus()
    {
        $this->login();

        $result = Provider::getTicketsStatus();
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertArrayHasKey('icon', $result);

        $this->assertIsString($result['icon']);
        $this->assertIsString($result['label']);
        $this->assertArrayHasKey('labels', $result['data']);
        $this->assertArrayHasKey('series', $result['data']);
        $this->assertNotEmpty($result['data']['labels']);
        $this->assertNotEmpty($result['data']['series']);

        $nb_labels = count($result['data']['labels']);
        foreach ($result['data']['series'] as $serie) {
            $this->assertArrayHasKey('data', $serie);
            $this->assertCount($nb_labels, $serie['data']);

            foreach ($serie['data'] as $serie_data) {
                $this->assertIsInt($serie_data['value']);
                $this->assertStringContainsString(\Ticket::getSearchURL(), $serie_data['url']);
            }
        }
    }


    public function testTopTicketsCategories()
    {
        $this->login();

        $result = Provider::multipleNumberTicketByITILCategory();
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertArrayHasKey('icon', $result);

        $this->assertIsString($result['icon']);
        $this->assertIsString($result['label']);

        foreach ($result['data'] as $data) {
            $this->assertArrayHasKey('number', $data);
            $this->assertArrayHasKey('label', $data);
            $this->assertArrayHasKey('url', $data);

            $this->assertGreaterThan(0, $data['number']);
            $this->assertIsString($data['label']);
            $this->assertStringContainsString(\Ticket::getSearchURL(), $data['url']);
        }
    }

    public static function monthYearProvider()
    {
        return [
            [
                'monthyear' => '2019-01',
                'expected'  => [
                    '2019-01-01 00:00:00',
                    '2019-02-01 00:00:00',
                ],
            ], [
                'monthyear' => '2019-12',
                'expected'  => [
                    '2019-12-01 00:00:00',
                    '2020-01-01 00:00:00',
                ],
            ],
        ];
    }


    #[DataProvider('monthYearProvider')]
    public function testFormatMonthyearDates(string $monthyear, array $expected)
    {
        $this->assertEquals(
            $expected,
            Provider::formatMonthyearDates($monthyear)
        );
    }

    protected function testGetArticleListReminderProvider(): iterable
    {
        $this->login();

        // Create one reminder that will be visible because we are its author
        $reminder = $this->createItem(Reminder::class, ['name' => 'test']);
        yield ['expected' => 1];

        // Change author to someone else
        $tech = getItemByTypeName(User::class, 'tech');
        $this->updateItem($reminder::getType(), $reminder->getID(), [
            'users_id'  => $tech->getID(),
        ]);
        yield ['expected' => 0];

        // Allow our user through the visiblity criteria system
        $self = getItemByTypeName(User::class, TU_USER);
        $this->createItem(Reminder_User::class, [
            'reminders_id' => $reminder->getID(),
            'users_id' => $self->getID(),
        ]);
        yield ['expected' => 1];
    }

    public function testGetArticleListReminder(): void
    {
        foreach ($this->testGetArticleListReminderProvider() as $row) {
            $expected = $row['expected'];

            $results = Provider::getArticleListReminder();
            $this->assertEquals($expected, $results['number']);
        }
    }
}
