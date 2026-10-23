<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

use Glpi\CustomAsset\Test01Asset;
use Glpi\CustomAsset\Test01AssetType;
use Glpi\Dashboard\FakeProvider;
use Glpi\Dashboard\Filters\DatesFilter;
use Glpi\Dashboard\Filters\GroupRequesterFilter;
use Glpi\Dashboard\Filters\GroupTechFilter;
use Glpi\Dashboard\Filters\ItilCategoryFilter;
use Glpi\Dashboard\Filters\SlaFilter;
use Glpi\Dashboard\Filters\StateFilter;
use Glpi\Dashboard\Filters\TicketStatusFilter;
use Glpi\Dashboard\Filters\UserTechFilter;
use Glpi\Dashboard\Provider;
use Glpi\Tests\DbTestCase;
use Item_DeviceSimcard;
use PHPUnit\Framework\Attributes\DataProvider;
use Reminder;
use Reminder_User;
use TicketValidation;
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

    public function testTicketsBySla(): void
    {
        global $DB;

        $this->login();
        $entity_id = $this->getTestRootEntity(true);
        $slm       = $this->createItem(\SLM::class, ['name' => 'test dashboard tickets by sla']);
        $slas      = [];
        foreach (['A' => \SLM::TTR, 'B' => \SLM::TTR, 'C' => \SLM::TTO] as $letter => $type) {
            $slas[$letter] = $this->createItem(\SLA::class, [
                'name'            => "test dashboard sla $letter",
                'type'            => $type,
                'number_time'     => 4,
                'definition_time' => 'hour',
                'slms_id'         => $slm->getID(),
            ]);
        }

        $tickets_ids = [];
        // Dates far in the past so that no other ticket of the test database is counted
        foreach (
            [
                'A on time'      => ['A', \Ticket::SOLVED, '2010-01-06 10:00:00', '2010-01-05 12:00:00'],
                'A on time at midnight' => ['A', \Ticket::SOLVED, '2010-01-06 10:00:00', '2010-01-05 12:00:00', '2010-01-01 00:00:00'],
                'A late solved'  => ['A', \Ticket::SOLVED, '2010-01-05 11:00:00', '2010-01-05 12:00:00'],
                'A late closed'  => ['A', \Ticket::CLOSED, '2010-01-05 11:00:00', '2010-01-05 12:00:00'],
                'B on time'      => ['B', \Ticket::CLOSED, '2010-01-06 10:00:00', '2010-01-05 12:00:00', '2010-01-05 10:00:00', 'C'],
                'B not solved'   => ['B', \Ticket::ASSIGNED, '2010-01-05 11:00:00', null],
                'without SLA'    => [null, \Ticket::SOLVED, null, '2010-01-05 12:00:00'],
                // Only its time to own SLA matches C, it is not solved
                'A with C to own' => ['A', \Ticket::ASSIGNED, '2010-01-05 11:00:00', null, '2010-01-05 10:00:00', 'C'],
            ] as $name => $values
        ) {
            [$letter, $status, $time_to_resolve, $solvedate] = $values;
            $date        = $values[4] ?? '2010-01-05 10:00:00';
            $tto_letter  = $values[5] ?? null;
            $ticket = $this->createItem(\Ticket::class, [
                'name'        => "test dashboard tickets by sla $name",
                'content'     => 'blablabla',
                'entities_id' => $entity_id,
            ]);
            $DB->update(\Ticket::getTable(), [
                'date'            => $date,
                'slas_id_tto'     => $tto_letter !== null ? $slas[$tto_letter]->getID() : 0,
                'status'          => $status,
                'slas_id_ttr'     => $letter !== null ? $slas[$letter]->getID() : 0,
                'time_to_resolve' => $time_to_resolve,
                'solvedate'       => $solvedate,
            ], ['id' => $ticket->getID()]);
            $tickets_ids[$name] = $ticket->getID();
        }

        $dates_filter = [DatesFilter::getId() => ['2010-01-01', '2010-01-31']];
        $get_values   = static function (array $result): array {
            $values = [];
            foreach ($result['data']['series'] as $serie) {
                $values[$serie['name']] = array_column($serie['data'], 'value');
            }
            return $values;
        };

        // Only solved and closed tickets with a SLA are counted, not solved tickets are ignored
        $result = Provider::ticketsBySla(['apply_filters' => $dates_filter]);
        $this->assertSame(['test dashboard sla A', 'test dashboard sla B'], $result['data']['labels']);
        // Both series are always returned, in a fixed order, so colors stay consistent
        $this->assertSame(['On time' => [2, 1], 'Late' => [2, 0]], $get_values($result));

        // The search of a bar finds the tickets counted
        foreach (
            [
                [0, 0, 2], // SLA A on time, including the ticket created at the first second of the dates filter
                [1, 0, 2], // SLA A late
                [1, 1, 0], // SLA B late
            ] as [$serie_index, $label_index, $expected]
        ) {
            $url = $result['data']['series'][$serie_index]['data'][$label_index]['url'];
            parse_str((string) parse_url($url, PHP_URL_QUERY), $search_params);
            $search_data = \Search::getDatas(\Ticket::class, \Search::manageParams(\Ticket::class, $search_params));
            $this->assertSame($expected, $search_data['data']['totalcount'], $url);
        }

        // The status filter is intersected with the solved statuses
        $closed = Provider::ticketsBySla(['apply_filters' => $dates_filter + [TicketStatusFilter::getId() => \Ticket::CLOSED]]);
        $this->assertSame(['On time' => [0, 1], 'Late' => [1, 0]], $get_values($closed));
        $this->assertTrue(Provider::ticketsBySla([
            'apply_filters' => $dates_filter + [TicketStatusFilter::getId() => \Ticket::ASSIGNED],
        ])['data']['nodata'] ?? false);

        // The SLA filter restricts the bars to the selected SLA
        $filtered = Provider::ticketsBySla(['apply_filters' => $dates_filter + [SlaFilter::getId() => $slas['B']->getID()]]);
        $this->assertSame(['test dashboard sla B'], $filtered['data']['labels']);
        $this->assertSame(['On time' => [1], 'Late' => [0]], $get_values($filtered));

        // The SLA filter matches the time to own SLA too
        $filtered = Provider::ticketsBySla(['apply_filters' => $dates_filter + [SlaFilter::getId() => $slas['C']->getID()]]);
        $this->assertSame(['test dashboard sla B'], $filtered['data']['labels']);
        $this->assertSame(['On time' => [1], 'Late' => [0]], $get_values($filtered));
        // It is searched as "time to own OR time to resolve", which must not bypass the other criteria of the bar
        parse_str((string) parse_url($filtered['data']['series'][0]['data'][0]['url'], PHP_URL_QUERY), $search_params);
        $search_data = \Search::getDatas(\Ticket::class, \Search::manageParams(\Ticket::class, $search_params));
        $this->assertSame(1, $search_data['data']['totalcount'], 'the not solved ticket with C to own must not be found');

        // Without any ticket right, nothing is exposed
        $_SESSION['glpiactiveprofile'][\Ticket::$rightname]           = 0;
        $_SESSION['glpiactiveprofile'][TicketValidation::$rightname] = 0;
        $this->assertTrue(Provider::ticketsBySla(['apply_filters' => $dates_filter])['data']['nodata'] ?? false);

        // The update right alone doesn't allow to see tickets
        $_SESSION['glpiactiveprofile'][\Ticket::$rightname] = UPDATE;
        $this->assertTrue(Provider::ticketsBySla(['apply_filters' => $dates_filter])['data']['nodata'] ?? false);

        // The new tickets right alone only allows to see new tickets, which are not solved
        $_SESSION['glpiactiveprofile'][\Ticket::$rightname] = \Ticket::READNEWTICKET;
        $this->assertTrue(Provider::ticketsBySla(['apply_filters' => $dates_filter])['data']['nodata'] ?? false);

        // The "my tickets" right only allows to see the tickets the user is actor of
        $hidden_id = $tickets_ids['A late closed'];
        $DB->update(\Ticket::getTable(), ['users_id_recipient' => 0], ['id' => $hidden_id]);
        $DB->delete(\Ticket_User::getTable(), ['tickets_id' => $hidden_id]);
        $_SESSION['glpiactiveprofile'][\Ticket::$rightname] = \Ticket::READMY;
        $mine = Provider::ticketsBySla(['apply_filters' => $dates_filter]);
        $this->assertSame(['On time' => [2, 1], 'Late' => [1, 0]], $get_values($mine), 'ticket of another user must not be counted');
    }

    public function testFakeTicketsBySla(): void
    {
        // Demo mode must not run the real query
        $method = new \ReflectionMethod(FakeProvider::class, 'ticketsBySla');
        $this->assertSame(FakeProvider::class, $method->getDeclaringClass()->getName());

        $result = FakeProvider::ticketsBySla(['label' => 'test']);
        $this->assertSame('test', $result['label']);
        $this->assertCount(2, $result['data']['series']);
        foreach ($result['data']['series'] as $serie) {
            $this->assertCount(count($result['data']['labels']), $serie['data']);
        }
    }

    public static function itemProvider()
    {
        return [
            ['item' => new \Computer()],
            ['item' => new \Ticket()],
            ['item' => new Item_DeviceSimcard()],
        ];
    }

    #[DataProvider('itemProvider')]
    public function testBigNumber(\CommonDBTM $item)
    {
        $this->login();

        $itemtype = $item::class;
        $data = [
            Provider::bigNumberItem($item),
            call_user_func([Provider::class, "bigNumber$itemtype"]),
        ];

        foreach ($data as $result) {
            $this->assertArrayHasKey('number', $result);
            $this->assertArrayHasKey('url', $result);
            $this->assertArrayHasKey('label', $result);
            $this->assertArrayHasKey('icon', $result);
            if ($item::class !== Item_DeviceSimcard::class) {
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


    public function testTicketsByStatus()
    {
        $this->login();

        // Testing with incoming : to make sure there is at least one ticket
        $this->createItem(\Ticket::class, [
            'name'    => "test dashboard card tickets by status",
            'content' => 'foo',
            'status'  => \Ticket::INCOMING,
        ]);

        // verify existing key data/label/icon
        $result = Provider::ticketsByStatus();
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertArrayHasKey('icon', $result);

        $nb_items = 0;
        foreach ($result['data'] as $key => $data) {
            if ($key === 'nodata') {
                continue;
            }
            // checks that each item has a number, a label, and a URL containing Ticket::getSearchURL
            $this->assertArrayHasKey('number', $data);
            $this->assertArrayHasKey('label', $data);
            $this->assertArrayHasKey('url', $data);

            $this->assertGreaterThan(0, $data['number']);
            $this->assertIsString($data['label']);
            $this->assertStringContainsString(\Ticket::getSearchURL(), $data['url']);

            $nb_items++;
        }

        $this->assertGreaterThan(0, $nb_items);
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

    public function testGetTicketsEvolutionWithInvalidDatesFilter()
    {
        // Non-array "dates" filter value must not crash (regression test)
        $result = Provider::getTicketsEvolution(['apply_filters' => ['dates' => '']]);
        $this->assertArrayHasKey('data', $result);
        $this->assertNotEmpty($result['data']['labels']);
    }


    public function testGetTicketsEvolutionWithUninitializedDatesFilter()
    {
        // The dates filter value can be a string (instead of an array) when not initialized yet,
        // this must not raise a TypeError on count().
        $result = Provider::getTicketsEvolution([
            'apply_filters' => [
                DatesFilter::getId() => '',
            ],
        ]);
        $this->assertArrayHasKey('data', $result);
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

    public function testTicketsLifetime(): void
    {
        global $DB;

        $this->login();
        $entity_id   = $this->getTestRootEntity(true);
        $tickets_ids = [];

        // Dates far in the past so that no other ticket of the test database is counted
        foreach (
            [
                ['new at the start of week 1', '2010-01-04 00:00:00', \Ticket::INCOMING],
                ['solved on sunday of week 1', '2010-01-10 23:00:00', \Ticket::SOLVED],
                ['new in week 2', '2010-01-12 10:00:00', \Ticket::INCOMING],
                ['closed in week 2', '2010-01-13 10:00:00', \Ticket::CLOSED],
                ['closed in february', '2010-02-15 10:00:00', \Ticket::CLOSED],
                ['new in 2009', '2009-12-31 10:00:00', \Ticket::INCOMING],
            ] as [$name, $date, $status]
        ) {
            $ticket = $this->createItem(\Ticket::class, [
                'name'        => "test dashboard lifetime $name",
                'content'     => 'blablabla',
                'entities_id' => $entity_id,
            ]);
            $DB->update(\Ticket::getTable(), [
                'date'   => $date,
                'status' => $status,
            ], ['id' => $ticket->getID()]);
            $tickets_ids[$name] = $ticket->getID();
        }

        $statuses = \Ticket::getAllStatusArray();
        $get_values = static function (array $result) use ($statuses): array {
            $values = [];
            foreach ($result['data']['series'] as $serie) {
                $values[array_search($serie['name'], $statuses, true)] = array_column($serie['data'], 'value');
            }
            return $values;
        };

        // Periods are aligned on their start: ISO weeks start on monday
        $weeks = Provider::ticketsLifetime('week', [
            'apply_filters' => [DatesFilter::getId() => ['2010-01-06', '2010-01-20']],
        ]);
        $this->assertSame(['2010-W01', '2010-W02', '2010-W03'], $weeks['data']['labels']);
        // One serie per status, in a fixed order, so colors stay consistent
        $this->assertSame(array_values($statuses), array_column($weeks['data']['series'], 'name'));
        $values = $get_values($weeks);
        $this->assertSame([1, 1, 0], $values[\Ticket::INCOMING]);
        // 2010-01-10 is a sunday, the last day of week 1
        $this->assertSame([1, 0, 0], $values[\Ticket::SOLVED]);
        $this->assertSame([0, 1, 0], $values[\Ticket::CLOSED]);
        $this->assertSame([0, 0, 0], $values[\Ticket::ASSIGNED]);

        $days = Provider::ticketsLifetime('day', [
            'apply_filters' => [DatesFilter::getId() => ['2010-01-12', '2010-01-13']],
        ]);
        $this->assertSame(['2010-01-12', '2010-01-13'], $days['data']['labels']);
        $values = $get_values($days);
        $this->assertSame([1, 0], $values[\Ticket::INCOMING]);
        $this->assertSame([0, 1], $values[\Ticket::CLOSED]);

        $years = Provider::ticketsLifetime('year', [
            'apply_filters' => [DatesFilter::getId() => ['2009-06-01', '2010-02-01']],
        ]);
        $this->assertSame(['2009', '2010'], $years['data']['labels']);
        $values = $get_values($years);
        $this->assertSame([1, 2], $values[\Ticket::INCOMING]);
        $this->assertSame([0, 2], $values[\Ticket::CLOSED]);

        // The search of a period finds the tickets counted, the strict lower bound starts 1 second before the period
        $closed_url = $weeks['data']['series'][array_search(\Ticket::CLOSED, array_keys($statuses), true)]['data'][1]['url'];
        $this->assertStringContainsString('2010-01-10 23:59:59', urldecode($closed_url));
        $this->assertStringContainsString('2010-01-18 00:00:00', urldecode($closed_url));
        parse_str((string) parse_url($closed_url, PHP_URL_QUERY), $search_params);
        $search_data = \Search::getDatas(\Ticket::class, \Search::manageParams(\Ticket::class, $search_params));
        $this->assertSame(1, $search_data['data']['totalcount']);

        // A ticket created exactly at the period start is counted and found by the period search
        $new_url = $weeks['data']['series'][array_search(\Ticket::INCOMING, array_keys($statuses), true)]['data'][0]['url'];
        parse_str((string) parse_url($new_url, PHP_URL_QUERY), $search_params);
        $search_data = \Search::getDatas(\Ticket::class, \Search::manageParams(\Ticket::class, $search_params));
        $this->assertSame(1, $search_data['data']['totalcount'], 'the midnight ticket must be found by the period search');

        // A long range with a short period is limited to the last periods
        $long_range = Provider::ticketsLifetime('day', [
            'apply_filters' => [DatesFilter::getId() => ['2008-01-01', '2010-12-31']],
        ]);
        $this->assertCount(366, $long_range['data']['labels']);
        $this->assertSame('2010-12-31', end($long_range['data']['labels']));

        // A range of several centuries only builds the last periods
        foreach (
            [
                'day'   => ['2009-12-31', '2010-12-31'],
                'week'  => ['2004-W01', '2010-W52'],
                'year'  => ['1645', '2010'],
            ] as $period => [$first, $last]
        ) {
            $centuries = Provider::ticketsLifetime($period, [
                'apply_filters' => [DatesFilter::getId() => ['0001-01-01', '2010-12-31']],
            ]);
            $this->assertCount(366, $centuries['data']['labels'], $period);
            $this->assertSame($first, reset($centuries['data']['labels']), $period);
            $this->assertSame($last, end($centuries['data']['labels']), $period);
        }

        $week_filters = [
            'apply_filters' => [DatesFilter::getId() => ['2010-01-06', '2010-01-20']],
        ];

        // Without any ticket right, nothing is exposed
        $_SESSION['glpiactiveprofile'][\Ticket::$rightname]           = 0;
        $_SESSION['glpiactiveprofile'][TicketValidation::$rightname] = 0;
        $this->assertTrue(Provider::ticketsLifetime('day')['data']['nodata'] ?? false);

        // The update right alone doesn't allow to see tickets
        $_SESSION['glpiactiveprofile'][\Ticket::$rightname] = UPDATE;
        $this->assertTrue(Provider::ticketsLifetime('week', $week_filters)['data']['nodata'] ?? false);

        // The new tickets right alone only allows to see new tickets
        $_SESSION['glpiactiveprofile'][\Ticket::$rightname] = \Ticket::READNEWTICKET;
        $values = $get_values(Provider::ticketsLifetime('week', $week_filters));
        $this->assertSame([1, 1, 0], $values[\Ticket::INCOMING]);
        $this->assertSame([0, 0, 0], $values[\Ticket::SOLVED], 'solved ticket must not be visible');
        $this->assertSame([0, 0, 0], $values[\Ticket::CLOSED], 'closed ticket must not be visible');

        // The "my tickets" right only allows to see the tickets the user is actor of
        $closed_id = $tickets_ids['closed in week 2'];
        $DB->update(\Ticket::getTable(), ['users_id_recipient' => 0], ['id' => $closed_id]);
        $DB->delete(\Ticket_User::getTable(), ['tickets_id' => $closed_id]);
        $_SESSION['glpiactiveprofile'][\Ticket::$rightname] = \Ticket::READMY;
        $values = $get_values(Provider::ticketsLifetime('week', $week_filters));
        $this->assertSame([1, 1, 0], $values[\Ticket::INCOMING]);
        $this->assertSame([1, 0, 0], $values[\Ticket::SOLVED]);
        $this->assertSame([0, 0, 0], $values[\Ticket::CLOSED], 'ticket of another user must not be visible');
    }

    public function testFakeTicketsLifetime(): void
    {
        // Demo mode must not run the real query
        $method = new \ReflectionMethod(FakeProvider::class, 'ticketsLifetime');
        $this->assertSame(FakeProvider::class, $method->getDeclaringClass()->getName());

        foreach (['day', 'week', 'year'] as $period) {
            $result = FakeProvider::ticketsLifetime($period, ['label' => 'test']);
            $this->assertSame('test', $result['label']);
            $this->assertCount(count(\Ticket::getAllStatusArray()), $result['data']['series']);
            foreach ($result['data']['series'] as $serie) {
                $this->assertCount(count($result['data']['labels']), $serie['data']);
            }
        }
    }

    public function testTicketsActionTimeVsSolveDelay(): void
    {
        global $DB;

        $this->login();
        $entity_id   = $this->getTestRootEntity(true);
        $tickets_ids = [];
        $group_a     = $this->createItem(\Group::class, ['name' => 'test actiontime group A', 'entities_id' => $entity_id]);
        $group_b     = $this->createItem(\Group::class, ['name' => 'test actiontime group B', 'entities_id' => $entity_id]);

        // Dates far in the past so that no other ticket of the test database is counted
        foreach (
            [
                ['75% in week 1', '2010-01-05 10:00:00', 3600, [1800, 900], [$group_a, $group_b]],
                ['50% in week 1', '2010-01-06 10:00:00', 7200, [3600], [$group_a]],
                ['no task in week 2', '2010-01-12 10:00:00', 3600, [], [$group_a]],
                ['not solved in week 2', '2010-01-13 10:00:00', 0, [1800], [$group_a]],
                ['25% in week 3', '2010-01-19 10:00:00', 1000, [250, 0], [$group_b]],
            ] as [$name, $date, $solve_delay, $actiontimes, $groups]
        ) {
            $ticket = $this->createItem(\Ticket::class, [
                'name'        => "test dashboard actiontime $name",
                'content'     => 'blablabla',
                'entities_id' => $entity_id,
            ]);
            foreach ($actiontimes as $actiontime) {
                $this->createItem(\TicketTask::class, [
                    'tickets_id' => $ticket->getID(),
                    'content'    => 'task',
                    'actiontime' => $actiontime,
                ]);
            }
            foreach ($groups as $group) {
                $this->createItem(\Group_Ticket::class, [
                    'tickets_id' => $ticket->getID(),
                    'groups_id'  => $group->getID(),
                    'type'       => \CommonITILActor::REQUESTER,
                ]);
            }
            $DB->update(\Ticket::getTable(), [
                'date'             => $date,
                'status'           => $solve_delay > 0 ? \Ticket::SOLVED : \Ticket::INCOMING,
                'solve_delay_stat' => $solve_delay,
            ], ['id' => $ticket->getID()]);
            $tickets_ids[$name] = $ticket->getID();
        }

        $week_filters = [DatesFilter::getId() => ['2010-01-06', '2010-01-20']];

        $weeks = Provider::ticketsActionTimeVsSolveDelay('week', ['apply_filters' => $week_filters]);
        $this->assertSame(['2010-W01', '2010-W02', '2010-W03'], $weeks['data']['labels']);
        $this->assertCount(1, $weeks['data']['series']);
        // Tickets without any task or not solved are ignored
        $this->assertSame([62.5, 0.0, 25.0], $weeks['data']['series'][0]['data']);

        $months = Provider::ticketsActionTimeVsSolveDelay('month', [
            'apply_filters' => [DatesFilter::getId() => ['2010-01-01', '2010-01-31']],
        ]);
        $this->assertSame(['2010-01'], $months['data']['labels']);
        $this->assertSame([50.0], $months['data']['series'][0]['data']);

        // A ticket matching several filter values is counted once, and its tasks are not counted several times
        $filtered = Provider::ticketsActionTimeVsSolveDelay('week', [
            'apply_filters' => $week_filters + [GroupRequesterFilter::getId() => [$group_a->getID(), $group_b->getID()]],
        ]);
        $this->assertSame([62.5, 0.0, 25.0], $filtered['data']['series'][0]['data']);

        $filtered = Provider::ticketsActionTimeVsSolveDelay('week', [
            'apply_filters' => $week_filters + [GroupRequesterFilter::getId() => [$group_b->getID()]],
        ]);
        $this->assertSame([75.0, 0.0, 25.0], $filtered['data']['series'][0]['data']);

        // A long range with a short period is limited to the last periods
        $long_range = Provider::ticketsActionTimeVsSolveDelay('day', [
            'apply_filters' => [DatesFilter::getId() => ['2008-01-01', '2010-12-31']],
        ]);
        $this->assertCount(366, $long_range['data']['labels']);
        $this->assertSame('2010-12-31', end($long_range['data']['labels']));

        // Only the tasks the user can see are summed: 4 tasks of 15 minutes on a ticket solved in 1 hour
        $self_id     = \Session::getLoginUserID();
        $other_id    = getItemByTypeName(User::class, 'tech', true);
        $tech_group  = $this->createItem(\Group::class, ['name' => 'test actiontime tech group', 'entities_id' => $entity_id]);
        $task_ticket = $this->createItem(\Ticket::class, [
            'name'        => 'test dashboard actiontime private tasks',
            'content'     => 'blablabla',
            'entities_id' => $entity_id,
        ]);
        foreach (
            [
                'public task of another user'      => [0, $other_id, 0],
                'private task of another user'     => [1, $other_id, 0],
                'private task assigned to me'      => [1, $self_id, 0],
                'private task assigned to my group' => [1, $other_id, $tech_group->getID()],
            ] as $content => [$is_private, $users_id_tech, $groups_id_tech]
        ) {
            $task = $this->createItem(\TicketTask::class, [
                'tickets_id' => $task_ticket->getID(),
                'content'    => $content,
                'actiontime' => 900,
                'is_private' => $is_private,
            ]);
            $DB->update(\TicketTask::getTable(), [
                'users_id'       => $other_id,
                'users_id_tech'  => $users_id_tech,
                'groups_id_tech' => $groups_id_tech,
            ], ['id' => $task->getID()]);
        }
        $DB->update(\Ticket::getTable(), [
            'date'             => '2011-03-01 10:00:00',
            'status'           => \Ticket::SOLVED,
            'solve_delay_stat' => 3600,
        ], ['id' => $task_ticket->getID()]);
        $month_filters = ['apply_filters' => [DatesFilter::getId() => ['2011-03-01', '2011-03-31']]];
        $get_percent   = static fn(): array => Provider::ticketsActionTimeVsSolveDelay('month', $month_filters)['data']['series'][0]['data'];

        $this->assertSame([100.0], $get_percent(), 'all tasks are visible with the right to see private tasks');
        $_SESSION['glpiactiveprofile'][\TicketTask::$rightname] = \TicketTask::SEEPUBLIC;
        $_SESSION['glpigroups']                                 = [];
        $this->assertSame([50.0], $get_percent(), 'private task of another user must not be summed');
        $_SESSION['glpigroups'] = [$tech_group->getID()];
        $this->assertSame([75.0], $get_percent(), 'private task assigned to a group of the user is visible');
        $_SESSION['glpiactiveprofile'][\TicketTask::$rightname] = 0;
        $_SESSION['glpigroups']                                 = [];
        $this->assertSame([25.0], $get_percent(), 'without task right, only the tasks assigned to the user are visible');

        // Without any ticket right, nothing is exposed
        $_SESSION['glpiactiveprofile'][\Ticket::$rightname]           = 0;
        $_SESSION['glpiactiveprofile'][TicketValidation::$rightname] = 0;
        $this->assertTrue(Provider::ticketsActionTimeVsSolveDelay('week', ['apply_filters' => $week_filters])['data']['nodata'] ?? false);

        // The update right alone doesn't allow to see tickets
        $_SESSION['glpiactiveprofile'][\Ticket::$rightname] = UPDATE;
        $this->assertTrue(Provider::ticketsActionTimeVsSolveDelay('week', ['apply_filters' => $week_filters])['data']['nodata'] ?? false);

        // The new tickets right alone only allows to see new tickets, which are not solved
        $_SESSION['glpiactiveprofile'][\Ticket::$rightname] = \Ticket::READNEWTICKET;
        $new_only = Provider::ticketsActionTimeVsSolveDelay('week', ['apply_filters' => $week_filters]);
        $this->assertSame([0.0, 0.0, 0.0], $new_only['data']['series'][0]['data'], 'solved tickets must not be visible');

        // The "my tickets" right only allows to see the tickets the user is actor of
        $hidden_id = $tickets_ids['75% in week 1'];
        $DB->update(\Ticket::getTable(), ['users_id_recipient' => 0], ['id' => $hidden_id]);
        $DB->delete(\Ticket_User::getTable(), ['tickets_id' => $hidden_id]);
        $_SESSION['glpiactiveprofile'][\Ticket::$rightname] = \Ticket::READMY;
        $mine = Provider::ticketsActionTimeVsSolveDelay('week', ['apply_filters' => $week_filters]);
        $this->assertSame([50.0, 0.0, 25.0], $mine['data']['series'][0]['data'], 'ticket of another user must not be counted');
    }

    public function testFakeTicketsActionTimeVsSolveDelay(): void
    {
        // Demo mode must not run the real query
        $method = new \ReflectionMethod(FakeProvider::class, 'ticketsActionTimeVsSolveDelay');
        $this->assertSame(FakeProvider::class, $method->getDeclaringClass()->getName());

        foreach (['day', 'week', 'month', 'year'] as $period) {
            $result = FakeProvider::ticketsActionTimeVsSolveDelay($period, ['label' => 'test']);
            $this->assertSame('test', $result['label']);
            $this->assertCount(1, $result['data']['series']);
            $this->assertCount(count($result['data']['labels']), $result['data']['series'][0]['data']);
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
        $this->updateItem($reminder::class, $reminder->getID(), [
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

    /**
     * Regression test to make sure this widget does not crash when loaded as
     * a self service user with validation rights enabled.
     */
    public function testTicketsWaitingForApprovalSelfService(): void
    {
        // Arrange: allow self service users to validate tickets
        $this->addRightToProfile(
            "Self-service",
            TicketValidation::$rightname,
            TicketValidation::VALIDATEREQUEST | TicketValidation::VALIDATEINCIDENT
        );

        // Act: get waiting_validation widget content
        $this->login('post-only');
        $params = Provider::nbTicketsGeneric('waiting_validation');

        // Assert: just make sure this was executed without errors
        $this->assertNotEmpty($params);
    }

    public function testComputersByOperatingSystem()
    {
        $this->login();

        $os = $this->createItem(\OperatingSystem::class, [
            'name' => 'test dashboard OS',
        ]);

        $computer = $this->createItem(\Computer::class, [
            'name'        => 'test dashboard computer by os',
            'entities_id' => 0,
        ]);

        $this->createItem(\Item_OperatingSystem::class, [
            'itemtype'            => \Computer::class,
            'items_id'            => $computer->getID(),
            'operatingsystems_id' => $os->getID(),
        ]);

        $result = Provider::computersByOperatingSystem();
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertArrayHasKey('icon', $result);

        $nb_items = 0;
        foreach ($result['data'] as $key => $data) {
            if ($key === 'nodata') {
                continue;
            }

            $this->assertArrayHasKey('number', $data);
            $this->assertArrayHasKey('label', $data);
            $this->assertArrayHasKey('url', $data);

            $this->assertGreaterThan(0, $data['number']);
            $this->assertIsString($data['label']);
            $this->assertStringContainsString(\Computer::getSearchURL(), $data['url']);

            $nb_items++;
        }

        $this->assertGreaterThan(0, $nb_items);
    }

    public function testComputersByOperatingSystemVersion(): void
    {
        $this->login();
        $entity_id = $this->getTestRootEntity(true);
        $state     = $this->createItem(\State::class, ['name' => 'test dashboard os version state']);

        $oses     = [];
        $versions = [];
        foreach (
            [
                // os => [version => [number of computers, deleted computers, template computers, computers with the state]]
                'test dashboard Microsoft Windows 11' => ['23H2' => [2, 1, 1, 1], '24H2' => [1, 0, 0, 0], '' => [1, 0, 0, 0]],
                'test dashboard Ubuntu'               => ['24.04' => [2, 0, 0, 0]],
                'test dashboard Debian GNU/Linux'     => ['12' => [1, 0, 0, 1]],
                'test dashboard macOS'                => ['14.5' => [1, 0, 0, 0], '14.6' => [2, 0, 0, 0], '15.1' => [1, 0, 0, 0]],
                'test dashboard Mac OS X'             => ['10.15.7' => [1, 0, 0, 0]],
            ] as $os_name => $os_versions
        ) {
            $oses[$os_name] = $this->createItem(\OperatingSystem::class, ['name' => $os_name])->getID();
            foreach ($os_versions as $version_name => [$nb, $nb_deleted, $nb_templates, $nb_with_state]) {
                $version_id = 0;
                if ($version_name !== '') {
                    $versions[$version_name] ??= $this->createItem(\OperatingSystemVersion::class, ['name' => $version_name])->getID();
                    $version_id = $versions[$version_name];
                }
                for ($i = 0; $i < $nb + $nb_deleted + $nb_templates; $i++) {
                    $computer = $this->createItem(\Computer::class, [
                        'name'        => "test dashboard computer $os_name $version_name $i",
                        'entities_id' => $entity_id,
                        'is_deleted'  => (int) ($i >= $nb && $i < $nb + $nb_deleted),
                        'is_template' => (int) ($i >= $nb + $nb_deleted),
                        'states_id'   => $i < $nb_with_state ? $state->getID() : 0,
                    ]);
                    $this->createItem(\Item_OperatingSystem::class, [
                        'itemtype'                   => \Computer::class,
                        'items_id'                   => $computer->getID(),
                        'operatingsystems_id'        => $oses[$os_name],
                        'operatingsystemversions_id' => $version_id,
                    ]);
                }
            }
        }

        // Only the operating systems of this test are checked, other computers may exist in the test database
        $get_values = static function (array $result): array {
            $values = [];
            foreach ($result['data'] as $entry) {
                if (str_contains($entry['label'], 'test dashboard')) {
                    $values[$entry['label']] = $entry['number'];
                }
            }
            return $values;
        };
        $search_count = static function (string $url): int {
            parse_str((string) parse_url($url, PHP_URL_QUERY), $search_params);
            $search_data = \Search::getDatas(\Computer::class, \Search::manageParams(\Computer::class, $search_params));
            return $search_data['data']['totalcount'];
        };

        // Deleted and template computers are not counted, the biggest counts first
        $windows = Provider::computersByOperatingSystemVersion('windows', 'version');
        $this->assertSame([
            'test dashboard Microsoft Windows 11 23H2'   => 2,
            'test dashboard Microsoft Windows 11 (None)' => 1,
            'test dashboard Microsoft Windows 11 24H2'   => 1,
        ], $get_values($windows));
        foreach ($windows['data'] as $entry) {
            if (str_contains($entry['label'], 'test dashboard')) {
                $this->assertSame($entry['number'], $search_count($entry['url']), $entry['label']);
            }
        }

        $this->assertSame([
            'test dashboard Ubuntu 24.04'        => 2,
            'test dashboard Debian GNU/Linux 12' => 1,
        ], $get_values(Provider::computersByOperatingSystemVersion('linux', 'version')));

        $distributions = Provider::computersByOperatingSystemVersion('linux', 'name');
        $this->assertSame([
            'test dashboard Ubuntu'           => 2,
            'test dashboard Debian GNU/Linux' => 1,
        ], $get_values($distributions));

        // macOS 10.x major versions are "10.x", the next ones are "11", "12"...
        $majors = Provider::computersByOperatingSystemVersion('macos', 'major');
        $this->assertSame([
            'test dashboard macOS 14'          => 3,
            'test dashboard Mac OS X 10.15'    => 1,
            'test dashboard macOS 15'          => 1,
        ], $get_values($majors));
        foreach (array_merge($distributions['data'], $majors['data']) as $entry) {
            if (str_contains($entry['label'], 'test dashboard')) {
                $this->assertSame($entry['number'], $search_count($entry['url']), $entry['label']);
            }
        }

        // The dashboard filters are applied
        $filtered = Provider::computersByOperatingSystemVersion('windows', 'version', [
            'apply_filters' => [StateFilter::getId() => [$state->getID()]],
        ]);
        $this->assertSame(['test dashboard Microsoft Windows 11 23H2' => 1], $get_values($filtered));
        $this->assertSame(1, $search_count($filtered['data'][0]['url']));

        // Unknown family or grouping
        $this->assertTrue(Provider::computersByOperatingSystemVersion('beos', 'version')['data']['nodata'] ?? false);
        $this->assertTrue(Provider::computersByOperatingSystemVersion('windows', 'kernel')['data']['nodata'] ?? false);

        // Without the computer right, nothing is exposed
        $_SESSION['glpiactiveprofile'][\Computer::$rightname] = 0;
        $this->assertTrue(Provider::computersByOperatingSystemVersion('windows', 'version')['data']['nodata'] ?? false);
    }

    public function testFakeComputersByOperatingSystemVersion(): void
    {
        // Demo mode must not run the real query
        $method = new \ReflectionMethod(FakeProvider::class, 'computersByOperatingSystemVersion');
        $this->assertSame(FakeProvider::class, $method->getDeclaringClass()->getName());

        foreach (['windows' => 'version', 'linux' => 'name', 'macos' => 'major'] as $family => $group_by) {
            $result = FakeProvider::computersByOperatingSystemVersion($family, $group_by, ['label' => 'test']);
            $this->assertSame('test', $result['label']);
            $this->assertNotEmpty($result['data']);
            foreach ($result['data'] as $entry) {
                $this->assertArrayHasKey('url', $entry);
            }
        }
    }

    public function testComputersByAge()
    {
        $this->login();

        // ensure at least one computer with a warranty date exists so the data loop is exercised
        $computer = $this->createItem(\Computer::class, [
            'name'        => 'test dashboard computer by age',
            'entities_id' => 0,
        ]);

        $this->createItem(\Infocom::class, [
            'itemtype'      => \Computer::class,
            'items_id'      => $computer->getID(),
            'warranty_date' => date('Y-m-d', strtotime('-2 years')),
        ]);

        $result = Provider::computersByAge();
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertArrayHasKey('icon', $result);

        $nb_items = 0;
        foreach ($result['data'] as $key => $data) {
            if ($key === 'nodata') {
                continue;
            }

            $this->assertArrayHasKey('number', $data);
            $this->assertArrayHasKey('label', $data);
            $this->assertArrayHasKey('url', $data);

            $this->assertGreaterThan(0, $data['number']);
            $this->assertIsString($data['label']);
            $this->assertStringContainsString(\Computer::getSearchURL(), $data['url']);

            $nb_items++;
        }

        $this->assertGreaterThan(0, $nb_items);
    }

    public function testTicketsByCategoryAndEntity()
    {
        $this->login();
        $entity_id = $this->getTestRootEntity(true);

        $category1 = $this->createItem(\ITILCategory::class, [
            'name' => 'test dashboard category 1 for tickets by category and entity',
        ]);
        $category2 = $this->createItem(\ITILCategory::class, [
            'name' => 'test dashboard category 2 for tickets by category and entity',
        ]);

        $this->createItem(\Ticket::class, [
            'name'             => 'test dashboard ticket cat1 a',
            'content'          => 'blablabla',
            'entities_id'      => $entity_id,
            'itilcategories_id' => $category1->getID(),
        ]);
        $this->createItem(\Ticket::class, [
            'name'             => 'test dashboard ticket cat1 b',
            'content'          => 'blablabla',
            'entities_id'      => $entity_id,
            'itilcategories_id' => $category1->getID(),
        ]);
        $this->createItem(\Ticket::class, [
            'name'             => 'test dashboard ticket cat2',
            'content'          => 'blablabla',
            'entities_id'      => $entity_id,
            'itilcategories_id' => $category2->getID(),
        ]);
        $this->createItem(\Ticket::class, [
            'name'              => 'test dashboard ticket no category',
            'content'           => 'blablabla',
            'entities_id'       => $entity_id,
            'itilcategories_id' => 0,
        ]);

        $result = Provider::ticketsByCategoryAndEntity();
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertArrayHasKey('icon', $result);

        $this->assertArrayHasKey('labels', $result['data']);
        $this->assertArrayHasKey('series', $result['data']);

        $entity = new \Entity();
        $this->assertTrue($entity->getFromDB($entity_id));
        $entity_index = array_search($entity->fields['completename'], $result['data']['labels'], true);
        $this->assertNotFalse($entity_index, 'entity must appear in the report labels');

        $series_by_name = [];
        foreach ($result['data']['series'] as $serie) {
            $series_by_name[$serie['name']] = $serie['data'];
        }

        $this->assertArrayHasKey($category1->fields['completename'], $series_by_name);
        $this->assertArrayHasKey($category2->fields['completename'], $series_by_name);
        $this->assertArrayHasKey(__('None'), $series_by_name, 'a "None" series is expected for the uncategorized ticket');
        $this->assertSame(2, $series_by_name[$category1->fields['completename']][$entity_index], '2 tickets expected for category 1');
        $this->assertSame(1, $series_by_name[$category2->fields['completename']][$entity_index], '1 ticket expected for category 2');
        $this->assertGreaterThanOrEqual(1, $series_by_name[__('None')][$entity_index], 'at least 1 uncategorized ticket expected');
    }

    public function testTicketsByAssignedGroupAndStatuses(): void
    {
        global $DB;

        $this->login();
        $entity_id = $this->getTestRootEntity(true);

        // A dedicated category isolates the tickets of this test, including the ones without group
        $category = $this->createItem(\ITILCategory::class, [
            'name' => 'test dashboard category for tickets by assigned group and statuses',
        ]);
        $group_b = $this->createItem(\Group::class, [
            'name' => 'test dashboard group B for tickets by assigned group and statuses',
        ]);
        $group_a = $this->createItem(\Group::class, [
            'name' => 'test dashboard group A for tickets by assigned group and statuses',
        ]);

        $tickets_ids = [];
        foreach (
            [
                ['group B new', \Ticket::INCOMING, [$group_b]],
                ['group B pending', \Ticket::WAITING, [$group_b]],
                ['groups A and B closed', \Ticket::CLOSED, [$group_a, $group_b]],
                ['group A solved', \Ticket::SOLVED, [$group_a]],
                ['no group new', \Ticket::INCOMING, []],
                ['no group closed', \Ticket::CLOSED, []],
            ] as [$name, $status, $groups]
        ) {
            $ticket = $this->createItem(\Ticket::class, [
                'name'              => "test dashboard $name",
                'content'           => 'blablabla',
                'entities_id'       => $entity_id,
                'itilcategories_id' => $category->getID(),
            ]);
            foreach ($groups as $group) {
                $this->createItem(\Group_Ticket::class, [
                    'tickets_id' => $ticket->getID(),
                    'groups_id'  => $group->getID(),
                    'type'       => \Group_Ticket::ASSIGN,
                ]);
            }
            // Assigning a group switches the ticket status, force the expected one
            $DB->update(\Ticket::getTable(), ['status' => $status], ['id' => $ticket->getID()]);
            $tickets_ids[$name] = $ticket->getID();
        }

        $category_filter = [ItilCategoryFilter::getId() => $category->getID()];
        $statuses        = \Ticket::getAllStatusArray();
        $get_values      = static function (array $result) use ($statuses): array {
            $values = [];
            foreach ($result['data']['series'] as $serie) {
                $values[array_search($serie['name'], $statuses, true)] = array_column($serie['data'], 'value');
            }
            return $values;
        };

        $result = Provider::ticketsByAssignedGroupAndStatuses(['apply_filters' => $category_filter]);
        // Groups sorted by name, then the tickets without any assigned group
        $this->assertSame(
            [$group_a->fields['completename'], $group_b->fields['completename'], __('None')],
            $result['data']['labels']
        );
        // One serie per status, in a fixed order, so colors stay consistent
        $this->assertSame(array_values($statuses), array_column($result['data']['series'], 'name'));
        $values = $get_values($result);
        $this->assertSame([0, 1, 1], $values[\Ticket::INCOMING]);
        $this->assertSame([0, 1, 0], $values[\Ticket::WAITING]);
        $this->assertSame([1, 0, 0], $values[\Ticket::SOLVED]);
        // A ticket assigned to two groups is counted in each of them
        $this->assertSame([1, 1, 1], $values[\Ticket::CLOSED]);
        $this->assertSame([0, 0, 0], $values[\Ticket::ASSIGNED]);

        // The search of a bar finds the tickets counted
        $status_index = array_flip(array_keys($statuses));
        foreach (
            [
                'group B pending' => $result['data']['series'][$status_index[\Ticket::WAITING]]['data'][1]['url'],
                'no group new'    => $result['data']['series'][$status_index[\Ticket::INCOMING]]['data'][2]['url'],
            ] as $name => $url
        ) {
            parse_str((string) parse_url($url, PHP_URL_QUERY), $search_params);
            $search_data = \Search::getDatas(\Ticket::class, \Search::manageParams(\Ticket::class, $search_params));
            $this->assertSame(1, $search_data['data']['totalcount'], "the search must find the ticket \"$name\"");
            $this->assertSame($tickets_ids[$name], $search_data['data']['rows'][0]['id']);
        }

        // The technician group filter restricts the rows to the selected groups
        $filtered = Provider::ticketsByAssignedGroupAndStatuses([
            'apply_filters' => $category_filter + [GroupTechFilter::getId() => [$group_a->getID()]],
        ]);
        $this->assertSame([$group_a->fields['completename']], $filtered['data']['labels']);
        $values = $get_values($filtered);
        $this->assertSame([1], $values[\Ticket::SOLVED]);
        $this->assertSame([1], $values[\Ticket::CLOSED]);

        // Without any ticket right, nothing is exposed
        $_SESSION['glpiactiveprofile'][\Ticket::$rightname]           = 0;
        $_SESSION['glpiactiveprofile'][TicketValidation::$rightname] = 0;
        $this->assertTrue(Provider::ticketsByAssignedGroupAndStatuses()['data']['nodata'] ?? false);

        // The update right alone doesn't allow to see tickets
        $_SESSION['glpiactiveprofile'][\Ticket::$rightname] = UPDATE;
        $this->assertTrue(Provider::ticketsByAssignedGroupAndStatuses()['data']['nodata'] ?? false);

        // The new tickets right alone only allows to see new tickets
        $_SESSION['glpiactiveprofile'][\Ticket::$rightname] = \Ticket::READNEWTICKET;
        $new_only = Provider::ticketsByAssignedGroupAndStatuses(['apply_filters' => $category_filter]);
        $this->assertSame([$group_b->fields['completename'], __('None')], $new_only['data']['labels']);
        $values = $get_values($new_only);
        $this->assertSame([1, 1], $values[\Ticket::INCOMING]);
        $this->assertSame([0, 0], $values[\Ticket::WAITING], 'pending ticket must not be visible');
        $this->assertSame([0, 0], $values[\Ticket::CLOSED], 'closed tickets must not be visible');

        // The "my tickets" right only allows to see the tickets the user is actor of
        $closed_id = $tickets_ids['groups A and B closed'];
        $DB->update(\Ticket::getTable(), ['users_id_recipient' => 0], ['id' => $closed_id]);
        $DB->delete(\Ticket_User::getTable(), ['tickets_id' => $closed_id]);
        $_SESSION['glpiactiveprofile'][\Ticket::$rightname] = \Ticket::READMY;
        $my_tickets = Provider::ticketsByAssignedGroupAndStatuses(['apply_filters' => $category_filter]);
        $values     = $get_values($my_tickets);
        $this->assertSame([0, 0, 1], $values[\Ticket::CLOSED], 'ticket of another user must not be visible');
        $this->assertSame([1, 0, 0], $values[\Ticket::SOLVED]);
    }

    public function testTicketsByCategoryReportsVisibility(): void
    {
        global $DB;

        $this->login();
        $entity_id = $this->getTestRootEntity(true);

        // A dedicated category isolates the tickets of this test
        $category = $this->createItem(\ITILCategory::class, [
            'name' => 'test dashboard category for category reports visibility',
        ]);
        foreach (['new' => \Ticket::INCOMING, 'pending' => \Ticket::WAITING, 'closed' => \Ticket::CLOSED] as $name => $status) {
            $ticket = $this->createItem(\Ticket::class, [
                'name'              => "test dashboard category reports visibility $name",
                'content'           => 'blablabla',
                'entities_id'       => $entity_id,
                'itilcategories_id' => $category->getID(),
                'type'              => \Ticket::INCIDENT_TYPE,
            ]);
            $DB->update(\Ticket::getTable(), ['status' => $status], ['id' => $ticket->getID()]);
        }

        $params = ['apply_filters' => [ItilCategoryFilter::getId() => $category->getID()]];
        $count  = static function (array $result): int {
            $total = 0;
            foreach ($result['data']['series'] as $serie) {
                $total += array_sum($serie['data']);
            }
            return $total;
        };

        $this->assertSame(3, $count(Provider::ticketsByCategoryAndEntity($params)));
        $this->assertSame(2, $count(Provider::ticketsByCategoryAndType('open', $params)));
        $this->assertSame(1, $count(Provider::ticketsByCategoryAndType('close', $params)));

        // Without any ticket right, nothing is exposed
        $_SESSION['glpiactiveprofile'][\Ticket::$rightname]           = 0;
        $_SESSION['glpiactiveprofile'][TicketValidation::$rightname] = 0;
        $this->assertTrue(Provider::ticketsByCategoryAndEntity($params)['data']['nodata'] ?? false);
        $this->assertTrue(Provider::ticketsByCategoryAndType('open', $params)['data']['nodata'] ?? false);
        $this->assertTrue(Provider::ticketsByCategoryAndType('close', $params)['data']['nodata'] ?? false);

        // The update right alone doesn't allow to see tickets
        $_SESSION['glpiactiveprofile'][\Ticket::$rightname] = UPDATE;
        $this->assertTrue(Provider::ticketsByCategoryAndEntity($params)['data']['nodata'] ?? false);
        $this->assertTrue(Provider::ticketsByCategoryAndType('open', $params)['data']['nodata'] ?? false);

        // The new tickets right alone only allows to see new tickets
        $_SESSION['glpiactiveprofile'][\Ticket::$rightname] = \Ticket::READNEWTICKET;
        $this->assertSame(1, $count(Provider::ticketsByCategoryAndEntity($params)), 'only the new ticket must be visible');
        $this->assertSame(1, $count(Provider::ticketsByCategoryAndType('open', $params)), 'pending ticket must not be visible');
        $this->assertTrue(Provider::ticketsByCategoryAndType('close', $params)['data']['nodata'] ?? false, 'closed ticket must not be visible');
    }

    public function testFakeTicketsByCategoryReports(): void
    {
        // Demo mode must not run the real queries
        foreach (['ticketsByCategoryAndEntity', 'ticketsByCategoryAndType'] as $method_name) {
            $method = new \ReflectionMethod(FakeProvider::class, $method_name);
            $this->assertSame(FakeProvider::class, $method->getDeclaringClass()->getName(), $method_name);
        }

        foreach (
            [
                FakeProvider::ticketsByCategoryAndEntity(['label' => 'test']),
                FakeProvider::ticketsByCategoryAndType('open', ['label' => 'test']),
                FakeProvider::ticketsByCategoryAndType('close', ['label' => 'test']),
            ] as $result
        ) {
            $this->assertSame('test', $result['label']);
            $this->assertNotEmpty($result['data']['series']);
            foreach ($result['data']['series'] as $serie) {
                $this->assertCount(count($result['data']['labels']), $serie['data']);
            }
        }
    }

    public function testFakeTicketsByAssignedGroupAndStatuses(): void
    {
        // Demo mode must not run the real query
        $method = new \ReflectionMethod(FakeProvider::class, 'ticketsByAssignedGroupAndStatuses');
        $this->assertSame(FakeProvider::class, $method->getDeclaringClass()->getName());

        $result = FakeProvider::ticketsByAssignedGroupAndStatuses(['label' => 'test']);
        $this->assertSame('test', $result['label']);
        $this->assertCount(count(\Ticket::getAllStatusArray()), $result['data']['series']);
        foreach ($result['data']['series'] as $serie) {
            $this->assertCount(count($result['data']['labels']), $serie['data']);
        }
    }

    public function testTicketsByAssignedGroupChanges(): void
    {
        global $DB;

        $this->login();
        $entity_id = $this->getTestRootEntity(true);
        $groups    = [];
        foreach (['A', 'B', 'C'] as $letter) {
            $groups[$letter] = $this->createItem(\Group::class, [
                'name'        => "test dashboard group changes $letter",
                'entities_id' => $entity_id,
            ]);
        }

        $tickets_ids = [];
        // Each step adds (+) or removes (-) a group of the given actor type
        foreach (
            [
                'never changed' => [['+', 'A', \CommonITILActor::ASSIGN]],
                'changed once'  => [['+', 'A', \CommonITILActor::ASSIGN], ['-', 'A', \CommonITILActor::ASSIGN], ['+', 'B', \CommonITILActor::ASSIGN]],
                'changed twice' => [
                    ['+', 'A', \CommonITILActor::ASSIGN],
                    ['+', 'B', \CommonITILActor::ASSIGN],
                    ['+', 'C', \CommonITILActor::ASSIGN],
                    ['+', 'A', \CommonITILActor::REQUESTER],
                    ['+', 'B', \CommonITILActor::OBSERVER],
                ],
                'no assigned group' => [['+', 'A', \CommonITILActor::REQUESTER], ['+', 'B', \CommonITILActor::REQUESTER]],
            ] as $name => $steps
        ) {
            $ticket = $this->createItem(\Ticket::class, [
                'name'        => "test dashboard group changes $name",
                'content'     => 'blablabla',
                'entities_id' => $entity_id,
            ]);
            foreach ($steps as [$action, $letter, $type]) {
                $link = [
                    'tickets_id' => $ticket->getID(),
                    'groups_id'  => $groups[$letter]->getID(),
                    'type'       => $type,
                ];
                if ($action === '+') {
                    $this->createItem(\Group_Ticket::class, $link);
                } else {
                    $group_ticket = new \Group_Ticket();
                    $this->assertTrue($group_ticket->getFromDBByCrit($link));
                    $this->deleteItem(\Group_Ticket::class, $group_ticket->getID());
                }
            }
            // A date far in the past so that no other ticket of the test database is counted
            $DB->update(\Ticket::getTable(), [
                'date'   => '2010-01-05 10:00:00',
                'status' => $name === 'changed twice' ? \Ticket::SOLVED : \Ticket::INCOMING,
            ], ['id' => $ticket->getID()]);
            $tickets_ids[$name] = $ticket->getID();
        }

        $dates_filter = [DatesFilter::getId() => ['2010-01-01', '2010-01-31']];
        $get_counts   = static fn(array $result): array => array_combine(
            array_column($result['data'], 'label'),
            array_column($result['data'], 'number')
        );

        // Requester and observer groups are not counted, tickets without assigned group neither
        $result = Provider::ticketsByAssignedGroupChanges(['apply_filters' => $dates_filter]);
        $this->assertSame(['0 changes' => 1, '1 change' => 1, '2 changes' => 1], $get_counts($result));
        foreach ($result['data'] as $data) {
            $this->assertSame('', $data['url']);
        }

        // A ticket matching several filter values is counted once, and its history is not counted several times
        $filtered = Provider::ticketsByAssignedGroupChanges([
            'apply_filters' => $dates_filter + [GroupTechFilter::getId() => array_map(static fn($group) => $group->getID(), array_values($groups))],
        ]);
        $this->assertSame(['0 changes' => 1, '1 change' => 1, '2 changes' => 1], $get_counts($filtered));

        // Missing numbers of changes are filled with zero
        $filtered = Provider::ticketsByAssignedGroupChanges([
            'apply_filters' => $dates_filter + [GroupTechFilter::getId() => [$groups['C']->getID()]],
        ]);
        $this->assertSame(['0 changes' => 0, '1 change' => 0, '2 changes' => 1], $get_counts($filtered));

        // The history is not exposed without the right to read it
        $logs_right = $_SESSION['glpiactiveprofile'][\Log::$rightname];
        $_SESSION['glpiactiveprofile'][\Log::$rightname] = 0;
        $this->assertTrue(Provider::ticketsByAssignedGroupChanges(['apply_filters' => $dates_filter])['data']['nodata'] ?? false);
        $_SESSION['glpiactiveprofile'][\Log::$rightname] = $logs_right;

        // Without any ticket right, nothing is exposed
        $_SESSION['glpiactiveprofile'][\Ticket::$rightname]           = 0;
        $_SESSION['glpiactiveprofile'][TicketValidation::$rightname] = 0;
        $this->assertTrue(Provider::ticketsByAssignedGroupChanges(['apply_filters' => $dates_filter])['data']['nodata'] ?? false);

        // The update right alone doesn't allow to see tickets
        $_SESSION['glpiactiveprofile'][\Ticket::$rightname] = UPDATE;
        $this->assertTrue(Provider::ticketsByAssignedGroupChanges(['apply_filters' => $dates_filter])['data']['nodata'] ?? false);

        // The new tickets right alone only allows to see new tickets
        $_SESSION['glpiactiveprofile'][\Ticket::$rightname] = \Ticket::READNEWTICKET;
        $new_only = Provider::ticketsByAssignedGroupChanges(['apply_filters' => $dates_filter]);
        $this->assertSame(['0 changes' => 1, '1 change' => 1], $get_counts($new_only), 'solved ticket must not be visible');

        // The "my tickets" right only allows to see the tickets the user is actor of
        $hidden_id = $tickets_ids['changed once'];
        $DB->update(\Ticket::getTable(), ['users_id_recipient' => 0], ['id' => $hidden_id]);
        $DB->delete(\Ticket_User::getTable(), ['tickets_id' => $hidden_id]);
        $_SESSION['glpiactiveprofile'][\Ticket::$rightname] = \Ticket::READMY;
        $mine = Provider::ticketsByAssignedGroupChanges(['apply_filters' => $dates_filter]);
        $this->assertSame(['0 changes' => 1, '1 change' => 0, '2 changes' => 1], $get_counts($mine), 'ticket of another user must not be counted');
    }

    public function testFakeTicketsByAssignedGroupChanges(): void
    {
        // Demo mode must not run the real query
        $method = new \ReflectionMethod(FakeProvider::class, 'ticketsByAssignedGroupChanges');
        $this->assertSame(FakeProvider::class, $method->getDeclaringClass()->getName());

        $result = FakeProvider::ticketsByAssignedGroupChanges(['label' => 'test']);
        $this->assertSame('test', $result['label']);
        $this->assertSame('0 changes', $result['data'][0]['label']);
        foreach ($result['data'] as $data) {
            $this->assertIsInt($data['number']);
            $this->assertSame('', $data['url']);
        }
    }

    public function testTicketsByGroupAndStatus()
    {
        $this->login();
        $self = getItemByTypeName(User::class, TU_USER);

        $group1 = $this->createItem(\Group::class, [
            'name' => 'test dashboard group 1 for tickets by status',
        ]);
        $group2 = $this->createItem(\Group::class, [
            'name' => 'test dashboard group 2 for tickets by status',
        ]);

        $this->createItem(\Group_User::class, [
            'groups_id' => $group1->getID(),
            'users_id'  => $self->getID(),
        ]);
        $this->createItem(\Group_User::class, [
            'groups_id' => $group2->getID(),
            'users_id'  => $self->getID(),
        ]);

        \Session::loadGroups();

        $this->attachTicketToGroup('group1 opened 1', \Ticket::ASSIGNED, $group1);
        $this->attachTicketToGroup('group2 opened', \Ticket::ASSIGNED, $group2);
        $this->attachTicketToGroup('group1 opened 2', \Ticket::INCOMING, $group1);
        $this->attachTicketToGroup('group1 closed', \Ticket::CLOSED, $group1);
        $this->attachTicketToGroup('group2 closed/solved 1', \Ticket::SOLVED, $group2);
        $this->attachTicketToGroup('group2 closed 2', \Ticket::CLOSED, $group2);

        $result = Provider::ticketsByGroupAndStatus();
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertArrayHasKey('icon', $result);

        $this->assertArrayHasKey('labels', $result['data']);
        $this->assertArrayHasKey('series', $result['data']);
        $this->assertCount(2, $result['data']['series']);

        $group1_index = array_search($group1->fields['name'], $result['data']['labels'], true);
        $group2_index = array_search($group2->fields['name'], $result['data']['labels'], true);
        $this->assertNotFalse($group1_index, 'group1 must be included in report labels');
        $this->assertNotFalse($group2_index, 'group2 must be included in report labels');
        $this->assertNotSame($group1_index, $group2_index, 'each group must have its own row');

        $this->assertSame(2, $result['data']['series'][0]['data'][$group1_index], 'group1: 2 opened tickets expected');
        $this->assertSame(1, $result['data']['series'][1]['data'][$group1_index], 'group1: 1 closed ticket expected');
        $this->assertSame(1, $result['data']['series'][0]['data'][$group2_index], 'group2: 1 opened ticket expected');
        $this->assertSame(2, $result['data']['series'][1]['data'][$group2_index], 'group2: 2 closed tickets expected');
    }

    public function testTicketsByCategoryTree(): void
    {
        $this->login();
        $entity_id = $this->getTestRootEntity(true);

        $root_a = $this->createItem(\ITILCategory::class, [
            'name' => 'test dashboard category tree A',
        ]);
        $child_a1 = $this->createItem(\ITILCategory::class, [
            'name'              => 'A1',
            'itilcategories_id' => $root_a->getID(),
        ]);
        $child_a2 = $this->createItem(\ITILCategory::class, [
            'name'              => 'A2',
            'itilcategories_id' => $root_a->getID(),
        ]);
        $grandchild_a2a = $this->createItem(\ITILCategory::class, [
            'name'              => 'A2a',
            'itilcategories_id' => $child_a2->getID(),
        ]);
        $root_b = $this->createItem(\ITILCategory::class, [
            'name' => 'test dashboard category tree B',
        ]);

        foreach (
            [
                [$root_a, 1],
                [$child_a1, 2],
                [$grandchild_a2a, 3],
                [$root_b, 1],
            ] as [$category, $count]
        ) {
            for ($i = 0; $i < $count; $i++) {
                $this->createItem(\Ticket::class, [
                    'name'              => 'test dashboard category tree ticket ' . $category->fields['name'] . " $i",
                    'content'           => 'blablabla',
                    'entities_id'       => $entity_id,
                    'itilcategories_id' => $category->getID(),
                ]);
            }
        }
        $this->createItem(\Ticket::class, [
            'name'              => 'test dashboard category tree ticket without category',
            'content'           => 'blablabla',
            'entities_id'       => $entity_id,
            'itilcategories_id' => 0,
        ]);

        $result = Provider::ticketsByCategoryTree();
        $this->assertArrayHasKey('data', $result);
        $roots = array_column($result['data'], null, 'label');

        // Parents count their own tickets plus the ones of their sub-categories
        $node_a = $roots[$root_a->fields['name']] ?? null;
        $this->assertNotNull($node_a, 'root category A must be a root node');
        $this->assertSame(6, $node_a['number']);
        $this->assertSame(['A1', 'A2'], array_column($node_a['children'], 'label'));
        $node_a2 = $node_a['children'][1];
        $this->assertSame(3, $node_a2['number'], 'A2 has no own ticket but its sub-category has 3');
        $this->assertSame(['A2a'], array_column($node_a2['children'], 'label'));
        $this->assertSame(3, $node_a2['children'][0]['number']);
        $this->assertArrayNotHasKey('children', $node_a['children'][0]);

        $this->assertSame(1, $roots[$root_b->fields['name']]['number'] ?? null);
        $this->assertGreaterThanOrEqual(1, $roots[__('None')]['number'] ?? 0);
        $this->assertSame(__('None'), array_key_last($roots), '"None" must be the last root node');

        // Each node links to its tickets, sub-categories included
        $this->assertStringContainsString('under', urldecode($node_a['url']));
        $this->assertStringContainsString('value]=' . $root_a->getID(), urldecode($node_a['url']));

        // The category filter keeps the ancestors of the selected category
        $filtered = Provider::ticketsByCategoryTree([
            'apply_filters' => [ItilCategoryFilter::getId() => $child_a2->getID()],
        ]);
        $this->assertSame([$root_a->fields['name']], array_column($filtered['data'], 'label'));
        $this->assertSame(3, $filtered['data'][0]['number']);
        $this->assertSame(['A2'], array_column($filtered['data'][0]['children'], 'label'));

        // Without any ticket right, nothing is exposed
        $_SESSION['glpiactiveprofile'][\Ticket::$rightname]           = 0;
        $_SESSION['glpiactiveprofile'][TicketValidation::$rightname] = 0;
        $this->assertSame(['nodata' => true], Provider::ticketsByCategoryTree()['data']);
    }

    public function testFakeTicketsByCategoryTree(): void
    {
        // Demo mode must not run the real query
        $method = new \ReflectionMethod(FakeProvider::class, 'ticketsByCategoryTree');
        $this->assertSame(FakeProvider::class, $method->getDeclaringClass()->getName());

        $result = FakeProvider::ticketsByCategoryTree(['label' => 'test']);
        $this->assertSame('test', $result['label']);
        $this->assertNotEmpty($result['data']);
        foreach ($result['data'] as $node) {
            $this->assertArrayHasKey('url', $node);
            if (isset($node['children'])) {
                $this->assertSame($node['number'], array_sum(array_column($node['children'], 'number')));
            }
        }
    }

    public function testTicketsByRequesterGroupAndStatus(): void
    {
        global $DB;

        $this->login();

        $requester_group = $this->createItem(\Group::class, [
            'name' => 'test dashboard requester group for tickets by status',
        ]);
        $other_requester_group = $this->createItem(\Group::class, [
            'name' => 'test dashboard other requester group for tickets by status',
        ]);
        $assigned_group = $this->createItem(\Group::class, [
            'name' => 'test dashboard assigned group for tickets by requester group',
        ]);

        $this->attachTicketToGroup('requester group opened 1', \Ticket::INCOMING, $requester_group, \Group_Ticket::REQUESTER);
        $this->attachTicketToGroup('requester group opened 2', \Ticket::WAITING, $requester_group, \Group_Ticket::REQUESTER);
        $closed_ticket = $this->attachTicketToGroup('requester group closed', \Ticket::CLOSED, $requester_group, \Group_Ticket::REQUESTER);
        $this->attachTicketToGroup('other requester group solved', \Ticket::SOLVED, $other_requester_group, \Group_Ticket::REQUESTER);
        $this->attachTicketToGroup('assigned group opened', \Ticket::INCOMING, $assigned_group);

        $result = Provider::ticketsByRequesterGroupAndStatus();
        $this->assertArrayHasKey('data', $result);
        $this->assertCount(2, $result['data']['series']);

        $requester_index = array_search($requester_group->fields['name'], $result['data']['labels'], true);
        $other_index     = array_search($other_requester_group->fields['name'], $result['data']['labels'], true);
        $this->assertNotFalse($requester_index, 'requester group must be included in report labels');
        $this->assertNotFalse($other_index, 'other requester group must be included in report labels');
        $this->assertNotContains($assigned_group->fields['name'], $result['data']['labels'], 'assigned groups must not be included');

        $this->assertSame(2, $result['data']['series'][0]['data'][$requester_index], 'requester group: 2 opened tickets expected');
        $this->assertSame(1, $result['data']['series'][1]['data'][$requester_index], 'requester group: 1 closed ticket expected');
        $this->assertSame(0, $result['data']['series'][0]['data'][$other_index], 'other requester group: no opened ticket expected');
        $this->assertSame(1, $result['data']['series'][1]['data'][$other_index], 'other requester group: 1 solved ticket expected');

        // The assigned groups card must not include requester groups
        $assigned = Provider::ticketsByGroupAndStatus();
        $this->assertContains($assigned_group->fields['name'], $assigned['data']['labels']);
        $this->assertNotContains($requester_group->fields['name'], $assigned['data']['labels'], 'requester groups must not be included');

        // The requester group filter restricts the rows to the selected groups
        $filtered = Provider::ticketsByRequesterGroupAndStatus([
            'apply_filters' => [GroupRequesterFilter::getId() => [$requester_group->getID()]],
        ]);
        $this->assertSame([$requester_group->fields['name']], $filtered['data']['labels']);
        $this->assertSame(2, $filtered['data']['series'][0]['data'][0]);
        $this->assertSame(1, $filtered['data']['series'][1]['data'][0]);

        // Without any ticket right, nothing is exposed
        $_SESSION['glpiactiveprofile'][\Ticket::$rightname]           = 0;
        $_SESSION['glpiactiveprofile'][TicketValidation::$rightname] = 0;
        $this->assertTrue(Provider::ticketsByRequesterGroupAndStatus()['data']['nodata'] ?? false);
        $this->assertTrue(Provider::ticketsByGroupAndStatus()['data']['nodata'] ?? false);

        // The update right alone doesn't allow to see tickets
        $_SESSION['glpiactiveprofile'][\Ticket::$rightname] = UPDATE;
        $this->assertTrue(Provider::ticketsByRequesterGroupAndStatus()['data']['nodata'] ?? false);
        $this->assertTrue(Provider::ticketsByGroupAndStatus()['data']['nodata'] ?? false);

        // The new tickets right alone only allows to see new tickets
        $_SESSION['glpiactiveprofile'][\Ticket::$rightname] = \Ticket::READNEWTICKET;
        $new_only       = Provider::ticketsByRequesterGroupAndStatus();
        $new_only_index = array_search($requester_group->fields['name'], $new_only['data']['labels'], true);
        $this->assertNotFalse($new_only_index, 'requester group has a new ticket');
        $this->assertSame(1, $new_only['data']['series'][0]['data'][$new_only_index], 'only the new ticket must be visible');
        $this->assertSame(0, $new_only['data']['series'][1]['data'][$new_only_index], 'closed ticket must not be visible');
        $this->assertNotContains($other_requester_group->fields['name'], $new_only['data']['labels'], 'other requester group has no new ticket');

        // The "my tickets" right only allows to see the tickets the user is actor of
        $DB->update(\Ticket::getTable(), ['users_id_recipient' => 0], ['id' => $closed_ticket->getID()]);
        $DB->delete(\Ticket_User::getTable(), ['tickets_id' => $closed_ticket->getID()]);
        $_SESSION['glpiactiveprofile'][\Ticket::$rightname] = \Ticket::READMY;
        $my_tickets       = Provider::ticketsByRequesterGroupAndStatus();
        $my_tickets_index = array_search($requester_group->fields['name'], $my_tickets['data']['labels'], true);
        $this->assertNotFalse($my_tickets_index);
        $this->assertSame(2, $my_tickets['data']['series'][0]['data'][$my_tickets_index]);
        $this->assertSame(0, $my_tickets['data']['series'][1]['data'][$my_tickets_index], 'ticket of another user must not be visible');
    }

    public function testFakeTicketsByRequesterGroupAndStatus(): void
    {
        // Demo mode must not run the real query
        $method = new \ReflectionMethod(FakeProvider::class, 'ticketsByRequesterGroupAndStatus');
        $this->assertSame(FakeProvider::class, $method->getDeclaringClass()->getName());

        $result = FakeProvider::ticketsByRequesterGroupAndStatus(['label' => 'test']);
        $this->assertSame('test', $result['label']);
        $this->assertCount(2, $result['data']['series']);
        foreach ($result['data']['series'] as $serie) {
            $this->assertCount(count($result['data']['labels']), $serie['data']);
        }
    }

    public function testTicketsByRequesterLocation(): void
    {
        $this->login();
        $entity_id = $this->getTestRootEntity(true);

        $location1 = $this->createItem(\Location::class, [
            'name' => 'test dashboard requester location 1',
        ]);
        $location2 = $this->createItem(\Location::class, [
            'name' => 'test dashboard requester location 2',
        ]);
        $ticket_location = $this->createItem(\Location::class, [
            'name' => 'test dashboard ticket location',
        ]);

        $user1 = $this->createItem(User::class, [
            'name'         => 'test dashboard requester location user 1',
            'locations_id' => $location1->getID(),
        ]);
        $user2 = $this->createItem(User::class, [
            'name'         => 'test dashboard requester location user 2',
            'locations_id' => $location2->getID(),
        ]);
        $user_without_location = $this->createItem(User::class, [
            'name' => 'test dashboard requester without location',
        ]);

        foreach (
            [
                'user1 ticket 1'          => [$user1],
                'user1 ticket 2'          => [$user1],
                'user2 ticket'            => [$user2],
                'user1 and user2 ticket'  => [$user1, $user2],
                'without location ticket' => [$user_without_location],
            ] as $name => $requesters
        ) {
            $this->createItem(\Ticket::class, [
                'name'         => "test dashboard requester location $name",
                'content'      => 'blablabla',
                'entities_id'  => $entity_id,
                // The ticket location must not be used by this report
                'locations_id' => $ticket_location->getID(),
                '_actors'      => [
                    'requester' => array_map(
                        static fn(User $user) => ['itemtype' => User::class, 'items_id' => $user->getID()],
                        $requesters
                    ),
                ],
            ], ['_actors']);
        }

        $result = Provider::ticketsByRequesterLocation();
        $this->assertArrayHasKey('data', $result);
        $numbers = array_column($result['data'], 'number', 'label');

        $this->assertSame(3, $numbers[$location1->fields['completename']] ?? null, 'location 1: 3 tickets expected');
        $this->assertSame(2, $numbers[$location2->fields['completename']] ?? null, 'location 2: 2 tickets expected');
        $this->assertGreaterThanOrEqual(1, $numbers[__('None')] ?? 0, 'at least 1 ticket expected for requesters without location');
        $this->assertArrayNotHasKey($ticket_location->fields['completename'], $numbers, 'ticket location must not be used');

        // Rows are sorted by number of tickets
        $sorted = array_column($result['data'], 'number');
        rsort($sorted);
        $this->assertSame($sorted, array_column($result['data'], 'number'));

        // Without any ticket right, nothing is exposed
        $_SESSION['glpiactiveprofile'][\Ticket::$rightname]           = 0;
        $_SESSION['glpiactiveprofile'][TicketValidation::$rightname] = 0;
        $this->assertSame(['nodata' => true], Provider::ticketsByRequesterLocation()['data']);
    }

    public function testFakeTicketsByRequesterLocation(): void
    {
        // Demo mode must not run the real query
        $method = new \ReflectionMethod(FakeProvider::class, 'ticketsByRequesterLocation');
        $this->assertSame(FakeProvider::class, $method->getDeclaringClass()->getName());

        $result = FakeProvider::ticketsByRequesterLocation(['label' => 'test']);
        $this->assertSame('test', $result['label']);
        $this->assertNotEmpty($result['data']);
        foreach ($result['data'] as $entry) {
            $this->assertArrayHasKey('number', $entry);
            $this->assertArrayHasKey('label', $entry);
            $this->assertArrayHasKey('url', $entry);
        }
    }

    public function testTicketsByStatusAndTechnician(): void
    {
        $this->login();
        $entity_id = $this->getTestRootEntity(true);

        $tech1 = $this->createItem(User::class, [
            'name' => 'test dashboard tickets by status technician 1',
        ]);
        $tech2 = $this->createItem(User::class, [
            'name' => 'test dashboard tickets by status technician 2',
        ]);

        foreach (
            [
                ['tech1 assigned 1', $tech1, \Ticket::ASSIGNED],
                ['tech1 assigned 2', $tech1, \Ticket::ASSIGNED],
                ['tech1 pending', $tech1, \Ticket::WAITING],
                ['tech1 closed', $tech1, \Ticket::CLOSED],
                ['tech2 solved', $tech2, \Ticket::SOLVED],
            ] as [$name, $tech, $status]
        ) {
            $this->createItem(\Ticket::class, [
                'name'        => "test dashboard tickets by status and technician $name",
                'content'     => 'blablabla',
                'entities_id' => $entity_id,
                'status'      => $status,
                '_actors'     => [
                    'assign' => [['itemtype' => User::class, 'items_id' => $tech->getID()]],
                ],
            ], ['_actors']);
        }

        $result = Provider::ticketsByStatusAndTechnician();
        $this->assertArrayHasKey('data', $result);

        // One series per status, in status order, so colors stay consistent
        $expected_order = array_map(
            static fn(int $status) => \Ticket::getStatus($status),
            array_keys(\Ticket::getAllStatusArray())
        );
        $this->assertSame($expected_order, array_column($result['data']['series'], 'name'));

        $tech1_index = array_search($tech1->fields['name'], $result['data']['labels'], true);
        $tech2_index = array_search($tech2->fields['name'], $result['data']['labels'], true);
        $this->assertNotFalse($tech1_index, 'technician 1 must be included in report labels');
        $this->assertNotFalse($tech2_index, 'technician 2 must be included in report labels');

        $series_by_name = array_column($result['data']['series'], 'data', 'name');
        $this->assertSame(2, $series_by_name[\Ticket::getStatus(\Ticket::ASSIGNED)][$tech1_index]);
        $this->assertSame(1, $series_by_name[\Ticket::getStatus(\Ticket::WAITING)][$tech1_index]);
        $this->assertSame(1, $series_by_name[\Ticket::getStatus(\Ticket::CLOSED)][$tech1_index]);
        $this->assertSame(0, $series_by_name[\Ticket::getStatus(\Ticket::SOLVED)][$tech1_index]);
        $this->assertSame(1, $series_by_name[\Ticket::getStatus(\Ticket::SOLVED)][$tech2_index]);

        // A status filter keeps every series, so colors don't change, but only counts this status
        $filtered = Provider::ticketsByStatusAndTechnician([
            'apply_filters' => [TicketStatusFilter::getId() => \Ticket::WAITING],
        ]);
        $this->assertSame($expected_order, array_column($filtered['data']['series'], 'name'));
        $this->assertSame([$tech1->fields['name']], array_values(array_intersect(
            $filtered['data']['labels'],
            [$tech1->fields['name'], $tech2->fields['name']]
        )), 'only technician 1 has a pending ticket');
        $filtered_series_by_name = array_column($filtered['data']['series'], 'data', 'name');
        $filtered_index          = array_search($tech1->fields['name'], $filtered['data']['labels'], true);
        $this->assertSame(1, $filtered_series_by_name[\Ticket::getStatus(\Ticket::WAITING)][$filtered_index]);
        $this->assertSame(0, $filtered_series_by_name[\Ticket::getStatus(\Ticket::ASSIGNED)][$filtered_index]);

        // The technician filter restricts the rows to the selected technician
        $filtered_tech = Provider::ticketsByStatusAndTechnician([
            'apply_filters' => [UserTechFilter::getId() => $tech2->getID()],
        ]);
        $this->assertSame([$tech2->fields['name']], $filtered_tech['data']['labels']);

        // A new ticket, only visible with the new tickets right among the limited rights below
        global $DB;
        $new_ticket = $this->createItem(\Ticket::class, [
            'name'        => 'test dashboard tickets by status and technician new',
            'content'     => 'blablabla',
            'entities_id' => $entity_id,
            '_actors'     => [
                'assign' => [['itemtype' => User::class, 'items_id' => $tech2->getID()]],
            ],
        ], ['_actors', 'status']);
        // Assigning a technician switches the ticket status, force it back to new
        $DB->update(\Ticket::getTable(), ['status' => \Ticket::INCOMING], ['id' => $new_ticket->getID()]);

        // Without any ticket right, nothing is exposed
        $_SESSION['glpiactiveprofile'][\Ticket::$rightname]           = 0;
        $_SESSION['glpiactiveprofile'][TicketValidation::$rightname] = 0;
        $this->assertTrue(Provider::ticketsByStatusAndTechnician()['data']['nodata'] ?? false);

        // The update right alone doesn't allow to see tickets
        $_SESSION['glpiactiveprofile'][\Ticket::$rightname] = UPDATE;
        $this->assertTrue(Provider::ticketsByStatusAndTechnician()['data']['nodata'] ?? false);

        // The new tickets right alone only allows to see new tickets
        $_SESSION['glpiactiveprofile'][\Ticket::$rightname] = \Ticket::READNEWTICKET;
        $new_only = Provider::ticketsByStatusAndTechnician();
        $new_only_series = array_column($new_only['data']['series'], 'data', 'name');
        $new_only_index  = array_search($tech2->fields['name'], $new_only['data']['labels'], true);
        $this->assertNotFalse($new_only_index, 'technician 2 has a new ticket');
        $this->assertSame(1, $new_only_series[\Ticket::getStatus(\Ticket::INCOMING)][$new_only_index]);
        $this->assertSame(0, $new_only_series[\Ticket::getStatus(\Ticket::SOLVED)][$new_only_index], 'solved ticket must not be visible');
        $this->assertNotContains($tech1->fields['name'], $new_only['data']['labels'], 'technician 1 has no new ticket');
    }

    public function testFakeTicketsByStatusAndTechnician(): void
    {
        // Demo mode must not run the real query
        $method = new \ReflectionMethod(FakeProvider::class, 'ticketsByStatusAndTechnician');
        $this->assertSame(FakeProvider::class, $method->getDeclaringClass()->getName());

        $result = FakeProvider::ticketsByStatusAndTechnician(['label' => 'test']);
        $this->assertSame('test', $result['label']);
        $this->assertCount(count(\Ticket::getAllStatusArray()), $result['data']['series']);
        foreach ($result['data']['series'] as $serie) {
            $this->assertCount(count($result['data']['labels']), $serie['data']);
        }
    }

    public function testTicketsBacklog(): void
    {
        global $DB;

        $this->login();
        $entity_id = $this->getTestRootEntity(true);

        // Dates far in the past so that no other ticket of the test database is counted
        foreach (
            [
                ['created before, not solved', '2009-12-15 10:00:00', null, null],
                ['created before, solved in january', '2009-12-20 10:00:00', '2010-01-10 10:00:00', null],
                ['created in january, solved and closed in february', '2010-01-05 10:00:00', '2010-02-03 10:00:00', '2010-02-10 10:00:00'],
                ['created in february, not solved', '2010-02-15 10:00:00', null, null],
                ['created and solved in march', '2010-03-01 10:00:00', '2010-03-02 10:00:00', null],
                ['created in march, not solved', '2010-03-10 10:00:00', null, null],
            ] as [$name, $date, $solvedate, $closedate]
        ) {
            $ticket = $this->createItem(\Ticket::class, [
                'name'        => "test dashboard backlog $name",
                'content'     => 'blablabla',
                'entities_id' => $entity_id,
            ]);
            $DB->update(\Ticket::getTable(), [
                'date'      => $date,
                'solvedate' => $solvedate,
                'closedate' => $closedate,
            ], ['id' => $ticket->getID()]);
        }

        $result = Provider::ticketsBacklog('month', [
            'apply_filters' => [DatesFilter::getId() => ['2010-01-01', '2010-03-31']],
        ]);
        $this->assertSame(['2010-01', '2010-02', '2010-03'], $result['data']['labels']);

        // 4 series in a fixed order, so colors stay consistent
        $this->assertSame(
            [
                _nx('ticket', 'Opened', 'Opened', \Session::getPluralNumber()),
                _nx('ticket', 'Solved', 'Solved', \Session::getPluralNumber()),
                __('Closed'),
                __('Backlog'),
            ],
            array_column($result['data']['series'], 'name')
        );
        $values = array_map(
            static fn(array $serie) => array_column($serie['data'], 'value'),
            $result['data']['series']
        );
        $this->assertSame([1, 1, 2], $values[0], 'opened tickets per month');
        $this->assertSame([1, 1, 1], $values[1], 'solved tickets per month');
        $this->assertSame([0, 1, 0], $values[2], 'closed tickets per month');
        // 2 tickets not solved before the range, then opened minus solved each month
        $this->assertSame([2, 2, 3], $values[3], 'backlog at the end of each month');

        // The search lower bound is strict, it starts 1 second before the period
        $this->assertStringContainsString('2010-01-31 23:59:59', urldecode($result['data']['series'][0]['data'][1]['url']));
        $this->assertStringContainsString('2010-03-01 00:00:00', urldecode($result['data']['series'][0]['data'][1]['url']));
        $this->assertSame('', $result['data']['series'][3]['data'][0]['url']);

        // Periods are aligned on their start: ISO weeks start on monday
        $weeks = Provider::ticketsBacklog('week', [
            'apply_filters' => [DatesFilter::getId() => ['2010-01-06', '2010-01-20']],
        ]);
        $this->assertSame(['2010-W01', '2010-W02', '2010-W03'], $weeks['data']['labels']);
        // 2010-01-10 is a sunday, the last day of week 1
        $this->assertSame([1, 0, 0], array_column($weeks['data']['series'][1]['data'], 'value'), 'ticket solved on sunday belongs to week 1');

        $days = Provider::ticketsBacklog('day', [
            'apply_filters' => [DatesFilter::getId() => ['2010-03-01', '2010-03-02']],
        ]);
        $this->assertSame(['2010-03-01', '2010-03-02'], $days['data']['labels']);
        $this->assertSame([1, 0], array_column($days['data']['series'][0]['data'], 'value'));

        $years = Provider::ticketsBacklog('year', [
            'apply_filters' => [DatesFilter::getId() => ['2009-06-01', '2010-02-01']],
        ]);
        $this->assertSame(['2009', '2010'], $years['data']['labels']);

        // A ticket created exactly at the period start is counted and found by the period search
        $midnight_ticket = $this->createItem(\Ticket::class, [
            'name'        => 'test dashboard backlog created at midnight',
            'content'     => 'blablabla',
            'entities_id' => $entity_id,
        ]);
        $DB->update(\Ticket::getTable(), ['date' => '2010-04-01 00:00:00'], ['id' => $midnight_ticket->getID()]);
        $april = Provider::ticketsBacklog('month', [
            'apply_filters' => [DatesFilter::getId() => ['2010-04-01', '2010-04-30']],
        ]);
        $this->assertSame(1, $april['data']['series'][0]['data'][0]['value']);
        parse_str((string) parse_url($april['data']['series'][0]['data'][0]['url'], PHP_URL_QUERY), $search_params);
        $search_data = \Search::getDatas(\Ticket::class, \Search::manageParams(\Ticket::class, $search_params));
        $this->assertSame(1, $search_data['data']['totalcount'], 'the midnight ticket must be found by the period search');

        // A long range with a short period is limited to the last periods
        $long_range = Provider::ticketsBacklog('day', [
            'apply_filters' => [DatesFilter::getId() => ['2008-01-01', '2010-12-31']],
        ]);
        $this->assertCount(366, $long_range['data']['labels']);
        $this->assertSame('2010-12-31', end($long_range['data']['labels']));

        // Without any ticket right, nothing is exposed
        $_SESSION['glpiactiveprofile'][\Ticket::$rightname]           = 0;
        $_SESSION['glpiactiveprofile'][TicketValidation::$rightname] = 0;
        $this->assertTrue(Provider::ticketsBacklog()['data']['nodata'] ?? false);
    }

    public function testFakeTicketsBacklog(): void
    {
        // Demo mode must not run the real query
        $method = new \ReflectionMethod(FakeProvider::class, 'ticketsBacklog');
        $this->assertSame(FakeProvider::class, $method->getDeclaringClass()->getName());

        foreach (['day', 'week', 'month', 'year'] as $period) {
            $result = FakeProvider::ticketsBacklog($period, ['label' => 'test']);
            $this->assertSame('test', $result['label']);
            $this->assertCount(4, $result['data']['series']);
            foreach ($result['data']['series'] as $serie) {
                $this->assertCount(count($result['data']['labels']), $serie['data']);
            }
        }
    }

    public function testTicketsBySlaEvolution(): void
    {
        global $DB;

        $this->login();
        $entity_id = $this->getTestRootEntity(true);
        $slm       = $this->createItem(\SLM::class, ['name' => 'test dashboard sla evolution']);
        $sla       = $this->createItem(\SLA::class, [
            'name'            => 'test dashboard sla evolution',
            'type'            => \SLM::TTR,
            'number_time'     => 4,
            'definition_time' => 'hour',
            'slms_id'         => $slm->getID(),
        ]);
        $sla_to_own = $this->createItem(\SLA::class, [
            'name'            => 'test dashboard sla evolution to own',
            'type'            => \SLM::TTO,
            'number_time'     => 1,
            'definition_time' => 'hour',
            'slms_id'         => $slm->getID(),
        ]);
        // Tickets having the time to own SLA
        $with_sla_to_own = ['week 1 on time', 'week 2 not solved'];

        $tickets_ids = [];
        // Dates far in the past so that no other ticket of the test database is counted
        foreach (
            [
                'week 1 on time'          => ['2010-01-05 10:00:00', true, \Ticket::SOLVED, false],
                'week 1 late'             => ['2010-01-05 10:00:00', true, \Ticket::CLOSED, true],
                'week 2 on time midnight' => ['2010-01-11 00:00:00', true, \Ticket::CLOSED, false],
                'week 2 late'             => ['2010-01-12 10:00:00', true, \Ticket::SOLVED, true],
                'week 2 late again'       => ['2010-01-13 10:00:00', true, \Ticket::SOLVED, true],
                'week 2 without SLA'      => ['2010-01-13 10:00:00', false, \Ticket::SOLVED, true],
                'week 2 not solved'       => ['2010-01-13 10:00:00', true, \Ticket::ASSIGNED, true],
            ] as $name => [$date, $with_sla, $status, $is_late]
        ) {
            $ticket = $this->createItem(\Ticket::class, [
                'name'        => "test dashboard sla evolution $name",
                'content'     => 'blablabla',
                'entities_id' => $entity_id,
            ]);
            $open_ts = strtotime($date);
            $DB->update(\Ticket::getTable(), [
                'date'            => $date,
                'status'          => $status,
                'slas_id_ttr'     => $with_sla ? $sla->getID() : 0,
                'slas_id_tto'     => in_array($name, $with_sla_to_own, true) ? $sla_to_own->getID() : 0,
                'time_to_resolve' => date('Y-m-d H:i:s', $open_ts + 4 * HOUR_TIMESTAMP),
                'solvedate'       => $status === \Ticket::ASSIGNED ? null : date('Y-m-d H:i:s', $open_ts + ($is_late ? 5 : 3) * HOUR_TIMESTAMP),
            ], ['id' => $ticket->getID()]);
            $tickets_ids[$name] = $ticket->getID();
        }

        $week_filters = [DatesFilter::getId() => ['2010-01-06', '2010-01-20']];
        $get_values   = static function (array $result): array {
            $values = [];
            foreach ($result['data']['series'] as $serie) {
                $values[$serie['name']] = array_column($serie['data'], 'value');
            }
            return $values;
        };

        // Only solved and closed tickets with a SLA are counted, by their opening period
        $weeks = Provider::ticketsBySlaEvolution('week', ['apply_filters' => $week_filters]);
        $this->assertSame(['2010-W01', '2010-W02', '2010-W03'], $weeks['data']['labels']);
        // Both series are always returned, in a fixed order, so colors stay consistent
        $this->assertSame(['On time' => [1, 1, 0], 'Late' => [1, 2, 0]], $get_values($weeks));

        $months = Provider::ticketsBySlaEvolution('month', [
            'apply_filters' => [DatesFilter::getId() => ['2010-01-01', '2010-01-31']],
        ]);
        $this->assertSame(['2010-01'], $months['data']['labels']);
        $this->assertSame(['On time' => [2], 'Late' => [3]], $get_values($months));

        // The search of a period finds the tickets counted, including the one created exactly at the period start
        foreach (
            [
                [0, 0, 1], // week 1 on time
                [0, 1, 1], // week 2 on time, at midnight
                [1, 1, 2], // week 2 late
            ] as [$serie_index, $period_index, $expected]
        ) {
            $url = $weeks['data']['series'][$serie_index]['data'][$period_index]['url'];
            parse_str((string) parse_url($url, PHP_URL_QUERY), $search_params);
            $search_data = \Search::getDatas(\Ticket::class, \Search::manageParams(\Ticket::class, $search_params));
            $this->assertSame($expected, $search_data['data']['totalcount'], $url);
        }

        // The status filter is intersected with the solved statuses
        $closed = Provider::ticketsBySlaEvolution('week', [
            'apply_filters' => $week_filters + [TicketStatusFilter::getId() => \Ticket::CLOSED],
        ]);
        $this->assertSame(['On time' => [0, 1, 0], 'Late' => [1, 0, 0]], $get_values($closed));
        $this->assertTrue(Provider::ticketsBySlaEvolution('week', [
            'apply_filters' => $week_filters + [TicketStatusFilter::getId() => \Ticket::ASSIGNED],
        ])['data']['nodata'] ?? false);

        // The SLA filter is searched as "time to own OR time to resolve", which must not bypass the other criteria of the point
        $filtered = Provider::ticketsBySlaEvolution('week', [
            'apply_filters' => $week_filters + [SlaFilter::getId() => $sla_to_own->getID()],
        ]);
        $this->assertSame(['On time' => [1, 0, 0], 'Late' => [0, 0, 0]], $get_values($filtered));
        parse_str((string) parse_url($filtered['data']['series'][0]['data'][0]['url'], PHP_URL_QUERY), $search_params);
        $search_data = \Search::getDatas(\Ticket::class, \Search::manageParams(\Ticket::class, $search_params));
        $this->assertSame(1, $search_data['data']['totalcount'], 'the not solved ticket with the SLA to own must not be found');

        // A long range with a short period is limited to the last periods
        $long_range = Provider::ticketsBySlaEvolution('day', [
            'apply_filters' => [DatesFilter::getId() => ['2008-01-01', '2010-12-31']],
        ]);
        $this->assertCount(366, $long_range['data']['labels']);
        $this->assertSame('2010-12-31', end($long_range['data']['labels']));

        // Without any ticket right, nothing is exposed
        $_SESSION['glpiactiveprofile'][\Ticket::$rightname]           = 0;
        $_SESSION['glpiactiveprofile'][TicketValidation::$rightname] = 0;
        $this->assertTrue(Provider::ticketsBySlaEvolution('week', ['apply_filters' => $week_filters])['data']['nodata'] ?? false);

        // The update right alone doesn't allow to see tickets
        $_SESSION['glpiactiveprofile'][\Ticket::$rightname] = UPDATE;
        $this->assertTrue(Provider::ticketsBySlaEvolution('week', ['apply_filters' => $week_filters])['data']['nodata'] ?? false);

        // The new tickets right alone only allows to see new tickets, which are not solved
        $_SESSION['glpiactiveprofile'][\Ticket::$rightname] = \Ticket::READNEWTICKET;
        $new_only = Provider::ticketsBySlaEvolution('week', ['apply_filters' => $week_filters]);
        $this->assertSame(['On time' => [0, 0, 0], 'Late' => [0, 0, 0]], $get_values($new_only), 'solved tickets must not be visible');

        // The "my tickets" right only allows to see the tickets the user is actor of
        $hidden_id = $tickets_ids['week 2 late again'];
        $DB->update(\Ticket::getTable(), ['users_id_recipient' => 0], ['id' => $hidden_id]);
        $DB->delete(\Ticket_User::getTable(), ['tickets_id' => $hidden_id]);
        $_SESSION['glpiactiveprofile'][\Ticket::$rightname] = \Ticket::READMY;
        $mine = Provider::ticketsBySlaEvolution('week', ['apply_filters' => $week_filters]);
        $this->assertSame(['On time' => [1, 1, 0], 'Late' => [1, 1, 0]], $get_values($mine), 'ticket of another user must not be counted');
    }

    public function testFakeTicketsBySlaEvolution(): void
    {
        // Demo mode must not run the real query
        $method = new \ReflectionMethod(FakeProvider::class, 'ticketsBySlaEvolution');
        $this->assertSame(FakeProvider::class, $method->getDeclaringClass()->getName());

        foreach (['day', 'week', 'month', 'year'] as $period) {
            $result = FakeProvider::ticketsBySlaEvolution($period, ['label' => 'test']);
            $this->assertSame('test', $result['label']);
            $this->assertCount(2, $result['data']['series']);
            foreach ($result['data']['series'] as $serie) {
                $this->assertCount(count($result['data']['labels']), $serie['data']);
            }
        }
    }

    public function testTicketsByCategoryAndType(): void
    {
        $this->login();
        $entity_id = $this->getTestRootEntity(true);

        $category = $this->createItem(\ITILCategory::class, [
            'name' => 'test dashboard category for tickets by category and type',
        ]);

        $this->createItem(\Ticket::class, [
            'name'              => 'test dashboard opened incident',
            'content'           => 'blablabla',
            'entities_id'       => $entity_id,
            'itilcategories_id' => $category->getID(),
            'type'              => \Ticket::INCIDENT_TYPE,
            'status'            => \Ticket::ASSIGNED,
        ]);
        $this->createItem(\Ticket::class, [
            'name'              => 'test dashboard opened request',
            'content'           => 'blablabla',
            'entities_id'       => $entity_id,
            'itilcategories_id' => $category->getID(),
            'type'              => \Ticket::DEMAND_TYPE,
            'status'            => \Ticket::INCOMING,
        ]);
        $this->createItem(\Ticket::class, [
            'name'              => 'test dashboard closed incident',
            'content'           => 'blablabla',
            'entities_id'       => $entity_id,
            'itilcategories_id' => $category->getID(),
            'type'              => \Ticket::INCIDENT_TYPE,
            'status'            => \Ticket::CLOSED,
        ]);
        $this->createItem(\Ticket::class, [
            'name'              => 'test dashboard solved request',
            'content'           => 'blablabla',
            'entities_id'       => $entity_id,
            'itilcategories_id' => $category->getID(),
            'type'              => \Ticket::DEMAND_TYPE,
            'status'            => \Ticket::SOLVED,
        ]);
        $this->createItem(\Ticket::class, [
            'name'              => 'test dashboard opened incident without category',
            'content'           => 'blablabla',
            'entities_id'       => $entity_id,
            'itilcategories_id' => 0,
            'type'              => \Ticket::INCIDENT_TYPE,
            'status'            => \Ticket::ASSIGNED,
        ]);

        $opened = Provider::ticketsByCategoryAndType('open');
        $this->assertArrayHasKey('data', $opened);
        $category_index = array_search($category->fields['completename'], $opened['data']['labels'], true);
        $this->assertNotFalse($category_index, 'category must appear in the opened report labels');

        $opened_series_by_name = [];
        foreach ($opened['data']['series'] as $serie) {
            $opened_series_by_name[$serie['name']] = $serie['data'];
        }
        $this->assertSame(1, $opened_series_by_name[\Ticket::getTicketTypeName(\Ticket::INCIDENT_TYPE)][$category_index], '1 opened incident expected');
        $this->assertSame(1, $opened_series_by_name[\Ticket::getTicketTypeName(\Ticket::DEMAND_TYPE)][$category_index], '1 opened request expected');

        $none_index = array_search(__('None'), $opened['data']['labels'], true);
        $this->assertNotFalse($none_index, 'a "None" label is expected for the uncategorized ticket');
        $this->assertGreaterThanOrEqual(1, $opened_series_by_name[\Ticket::getTicketTypeName(\Ticket::INCIDENT_TYPE)][$none_index], 'at least 1 opened uncategorized incident expected');

        $closed = Provider::ticketsByCategoryAndType('close');
        $this->assertArrayHasKey('data', $closed);
        $category_index_closed = array_search($category->fields['completename'], $closed['data']['labels'], true);
        $this->assertNotFalse($category_index_closed, 'category must appear in the closed report labels');

        $closed_series_by_name = [];
        foreach ($closed['data']['series'] as $serie) {
            $closed_series_by_name[$serie['name']] = $serie['data'];
        }
        $this->assertSame(1, $closed_series_by_name[\Ticket::getTicketTypeName(\Ticket::INCIDENT_TYPE)][$category_index_closed], '1 closed incident expected');
        $this->assertSame(1, $closed_series_by_name[\Ticket::getTicketTypeName(\Ticket::DEMAND_TYPE)][$category_index_closed], '1 solved request expected');

        // Series are ordered by type id so colors stay consistent between cards
        $expected_order = [
            \Ticket::getTicketTypeName(\Ticket::INCIDENT_TYPE),
            \Ticket::getTicketTypeName(\Ticket::DEMAND_TYPE),
        ];
        $this->assertSame($expected_order, array_column($opened['data']['series'], 'name'));
        $this->assertSame($expected_order, array_column($closed['data']['series'], 'name'));

        // A dashboard status filter must not leak tickets outside the requested case
        $opened_filtered = Provider::ticketsByCategoryAndType('open', [
            'apply_filters' => [TicketStatusFilter::getId() => \Ticket::CLOSED],
        ]);
        $filtered_index = array_search($category->fields['completename'], $opened_filtered['data']['labels'], true);
        $this->assertNotFalse($filtered_index);
        $filtered_series_by_name = array_column($opened_filtered['data']['series'], 'data', 'name');
        $this->assertSame(1, $filtered_series_by_name[\Ticket::getTicketTypeName(\Ticket::INCIDENT_TYPE)][$filtered_index], 'closed incident must not leak into the opened report');
        $this->assertSame(1, $filtered_series_by_name[\Ticket::getTicketTypeName(\Ticket::DEMAND_TYPE)][$filtered_index], 'solved request must not leak into the opened report');
    }

    public function testOpenedTicketsByCategoryAndStatus(): void
    {
        $this->login();
        $entity_id = $this->getTestRootEntity(true);

        $category = $this->createItem(\ITILCategory::class, [
            'name' => 'test dashboard category for opened tickets by category and status',
        ]);

        foreach (
            [
                ['new 1', $category->getID(), \Ticket::INCOMING],
                ['new 2', $category->getID(), \Ticket::INCOMING],
                ['pending', $category->getID(), \Ticket::WAITING],
                ['closed', $category->getID(), \Ticket::CLOSED],
                ['solved', $category->getID(), \Ticket::SOLVED],
                ['new without category', 0, \Ticket::INCOMING],
            ] as [$name, $category_id, $status]
        ) {
            $this->createItem(\Ticket::class, [
                'name'              => "test dashboard opened tickets by category and status $name",
                'content'           => 'blablabla',
                'entities_id'       => $entity_id,
                'itilcategories_id' => $category_id,
                'status'            => $status,
            ]);
        }

        $result = Provider::openedTicketsByCategoryAndStatus();
        $this->assertArrayHasKey('data', $result);
        $category_index = array_search($category->fields['completename'], $result['data']['labels'], true);
        $this->assertNotFalse($category_index, 'category must appear in the report labels');

        // One series per opened status, in status order, so colors stay consistent
        $expected_order = array_map(
            static fn(int $status) => \Ticket::getStatus($status),
            \Ticket::getNotSolvedStatusArray()
        );
        $this->assertSame($expected_order, array_column($result['data']['series'], 'name'));

        $series_by_name = array_column($result['data']['series'], 'data', 'name');
        $this->assertSame(2, $series_by_name[\Ticket::getStatus(\Ticket::INCOMING)][$category_index], '2 new tickets expected');
        $this->assertSame(1, $series_by_name[\Ticket::getStatus(\Ticket::WAITING)][$category_index], '1 pending ticket expected');
        $this->assertSame(0, $series_by_name[\Ticket::getStatus(\Ticket::ASSIGNED)][$category_index], 'no assigned ticket expected');

        $none_index = array_search(__('None'), $result['data']['labels'], true);
        $this->assertNotFalse($none_index, 'a "None" label is expected for the uncategorized ticket');
        $this->assertGreaterThanOrEqual(1, $series_by_name[\Ticket::getStatus(\Ticket::INCOMING)][$none_index], 'at least 1 new uncategorized ticket expected');

        // A status filter on an opened status keeps every series, so colors don't change, but only counts this status
        $filtered = Provider::openedTicketsByCategoryAndStatus([
            'apply_filters' => [TicketStatusFilter::getId() => \Ticket::WAITING],
        ]);
        $this->assertSame($expected_order, array_column($filtered['data']['series'], 'name'));
        $filtered_index = array_search($category->fields['completename'], $filtered['data']['labels'], true);
        $this->assertNotFalse($filtered_index);
        $filtered_series_by_name = array_column($filtered['data']['series'], 'data', 'name');
        $this->assertSame(1, $filtered_series_by_name[\Ticket::getStatus(\Ticket::WAITING)][$filtered_index], '1 pending ticket expected');
        $this->assertSame(0, $filtered_series_by_name[\Ticket::getStatus(\Ticket::INCOMING)][$filtered_index], 'new tickets must be filtered out');

        // A status filter on a closed status must not leak closed tickets into the report
        $filtered_closed = Provider::openedTicketsByCategoryAndStatus([
            'apply_filters' => [TicketStatusFilter::getId() => \Ticket::CLOSED],
        ]);
        $this->assertTrue($filtered_closed['data']['nodata'] ?? false, 'no data expected when filtering on a closed status');

        // Without any ticket right, nothing is exposed
        $_SESSION['glpiactiveprofile'][\Ticket::$rightname]           = 0;
        $_SESSION['glpiactiveprofile'][TicketValidation::$rightname] = 0;
        $this->assertTrue(Provider::openedTicketsByCategoryAndStatus()['data']['nodata'] ?? false);
    }

    public function testFakeOpenedTicketsByCategoryAndStatus(): void
    {
        // Demo mode must not run the real query
        $method = new \ReflectionMethod(FakeProvider::class, 'openedTicketsByCategoryAndStatus');
        $this->assertSame(FakeProvider::class, $method->getDeclaringClass()->getName());

        $result = FakeProvider::openedTicketsByCategoryAndStatus(['label' => 'test']);
        $this->assertSame('test', $result['label']);
        $this->assertCount(count(\Ticket::getNotSolvedStatusArray()), $result['data']['series']);
        foreach ($result['data']['series'] as $serie) {
            $this->assertCount(count($result['data']['labels']), $serie['data']);
        }
    }

    private function attachTicketToGroup(string $name, int $status, \Group $group, int $type = \Group_Ticket::ASSIGN): \Ticket
    {
        $ticket = $this->createItem(\Ticket::class, [
            'name'        => "test dashboard $name",
            'content'     => 'blablabla',
            'status'      => $status,
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $this->createItem(\Group_Ticket::class, [
            'tickets_id' => $ticket->getID(),
            'groups_id'  => $group->getID(),
            'type'       => $type,
        ]);

        return $ticket;
    }
}
