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

namespace tests\units\Glpi\Dashboard;

use DbTestCase;
use Reminder;
use Reminder_User;
use User;

/* Test for inc/dashboard/provider.class.php */

class Provider extends DbTestCase
{
    public function testNbTicketsByAgreementStatusAndTechnician()
    {
        global $DB;

       // Prepare context
        $slm = new \Slm();
        $slm->add([
            'name' => 'SLM',
        ]);

        $slaTto = new \SLA();
        $slaTto->add([
            'name' => 'sla tto',
            'type' => '1', // TTO
            'number_time' => 4,
            'definition_time' => 'hour',
            'slms_id' => $slm->getID(),
        ]);

        $slaTtr = new \SLA();
        $slaTtr->add([
            'name' => 'sla ttr',
            'type' => '0', // TTR
            'number_time' => 4,
            'definition_time' => 'hour',
            'slms_id' => $slm->getID(),
        ]);

        $ticket = new \Ticket();
        $ticket->add([
            'name' => "test dashboard card SLA / tech",
            'content' => 'foo',
            '_users_id_assign' => 2, // glpi
            'sla_id_tto'       => $slaTto->getID(),
            'sla_id_ttr'       => $slaTtr->getID(),
            'status'           => \Ticket::ASSIGNED,
        ]);
        $this->boolean($ticket->isNewItem())->isFalse();

        $ticket2 = new \Ticket();
        $ticket2->add([
            'name' => "test dashboard card SLA / tech",
            'content' => 'foo',
            '_users_id_assign' => 4, // tech
            'sla_id_tto'       => $slaTto->getID(),
            'sla_id_ttr'       => $slaTtr->getID(),
            'status'           => \Ticket::ASSIGNED,
        ]);
        $this->boolean($ticket2->isNewItem())->isFalse();

        $ticket3 = new \Ticket();
        $ticket3->add([
            'name' => "test dashboard card SLA / tech",
            'content' => 'foo',
            '_users_id_assign' => 4, // tech
            'sla_id_tto'       => $slaTto->getID(),
            'sla_id_ttr'       => $slaTtr->getID(),
            'status'           => \Ticket::ASSIGNED,
        ]);
        $this->boolean($ticket3->isNewItem())->isFalse();

        $output = \Glpi\Dashboard\Provider::nbTicketsByAgreementStatusAndTechnician();
        $expected = [
            'label' => "Tickets by SLA status and by technician",
            'data' => [
                'labels' => ['tech', 'glpi'],
                'series' => [
                    [
                        'name' => 'Late own and resolve',
                        'data' => [0, 0]
                    ],
                    [
                        'name' => 'Late resolve',
                        'data' => [0, 0]
                    ],
                    [
                        'name' => 'Late own',
                        'data' => [0, 0]
                    ],
                    [
                        'name' => 'On time',
                        'data' => [2, 1]
                    ]
                ]
            ],
            'icon' => 'fas fa-stopwatch'
        ];
        $this->array($output)->isEqualTo($expected);

        $DB->update(
            $ticket::getTable(),
            [
                'date'                       => '2021-01-01 00:00',
                'time_to_own'                => '2021-01-01 01:00',
                'takeintoaccount_delay_stat' => 50000,
            ],
            [
                'id' => $ticket->getID()
            ]
        );
        $DB->update(
            $ticket2::getTable(),
            [
                'date'                       => '2021-01-01 00:00',
                'time_to_own'                => '2021-01-01 01:00',
                'takeintoaccount_delay_stat' => 50000,
            ],
            [
                'id' => $ticket2->getID()
            ]
        );
        $ticket->getFromDB($ticket->getID());
        $ticket2->getFromDB($ticket2->getID());

        $output = \Glpi\Dashboard\Provider::nbTicketsByAgreementStatusAndTechnician();
        $expected = [
            'label' => "Tickets by SLA status and by technician",
            'data' => [
                'labels' => ['glpi', 'tech'],
                'series' => [
                    [
                        'name' => 'Late own and resolve',
                        'data' => [0, 0]
                    ],
                    [
                        'name' => 'Late resolve',
                        'data' => [0, 0]
                    ],
                    [
                        'name' => 'Late own',
                        'data' => [1, 1]
                    ],
                    [
                        'name' => 'On time',
                        'data' => [0, 1]
                    ]
                ]
            ],
            'icon' => 'fas fa-stopwatch'
        ];
        $this->array($output)->isEqualTo($expected);

        $DB->update(
            $ticket::getTable(),
            [
                'date'                       => '2021-01-01 00:00',
                'time_to_own'                => '2021-01-01 01:00',
                'takeintoaccount_delay_stat' => 60,
                'solvedate'                  => '2021-02-01 00:00',
                'time_to_resolve'            => '2021-01-02 00:00'
            ],
            [
                'id' => $ticket->getID()
            ]
        );
        $DB->update(
            $ticket2::getTable(),
            [
                'date'                       => '2021-01-01 00:00',
                'time_to_own'                => '2021-01-01 01:00',
                'takeintoaccount_delay_stat' => 60,
                'solvedate'                  => '2021-02-01 00:00',
                'time_to_resolve'            => '2021-01-02 00:00'
            ],
            [
                'id' => $ticket2->getID()
            ]
        );
        $ticket->getFromDB($ticket->getID());
        $ticket2->getFromDB($ticket2->getID());
        $output = \Glpi\Dashboard\Provider::nbTicketsByAgreementStatusAndTechnician();
        $expected = [
            'label' => "Tickets by SLA status and by technician",
            'data' => [
                'labels' => ['glpi', 'tech'],
                'series' => [
                    [
                        'name' => 'Late own and resolve',
                        'data' => [0, 0]
                    ],
                    [
                        'name' => 'Late resolve',
                        'data' => [1, 1]
                    ],
                    [
                        'name' => 'Late own',
                        'data' => [0, 0]
                    ],
                    [
                        'name' => 'On time',
                        'data' => [0, 1]
                    ]
                ]
            ],
            'icon' => 'fas fa-stopwatch'
        ];
        $this->array($output)->isEqualTo($expected);

        $DB->update(
            $ticket::getTable(),
            [
                'date'                       => '2021-01-01 00:00',
                'time_to_own'                => '2021-01-01 01:00',
                'takeintoaccount_delay_stat' => 50000,
                'solvedate'                  => '2021-02-01 00:00',
                'time_to_resolve'            => '2021-01-02 00:00'
            ],
            [
                'id' => $ticket->getID()
            ]
        );
        $DB->update(
            $ticket2::getTable(),
            [
                'date'                       => '2021-01-01 00:00',
                'time_to_own'                => '2021-01-01 01:00',
                'takeintoaccount_delay_stat' => 50000,
                'solvedate'                  => '2021-02-01 00:00',
                'time_to_resolve'            => '2021-01-02 00:00'
            ],
            [
                'id' => $ticket2->getID()
            ]
        );
        $DB->update(
            $ticket3::getTable(),
            [
                'date'                       => '2021-01-01 00:00',
                'time_to_own'                => '2021-01-01 01:00',
                'takeintoaccount_delay_stat' => 50000,
                'solvedate'                  => '2021-02-01 00:00',
                'time_to_resolve'            => '2021-01-02 00:00'
            ],
            [
                'id' => $ticket3->getID()
            ]
        );
        $ticket->getFromDB($ticket->getID());
        $ticket2->getFromDB($ticket2->getID());
        $ticket3->getFromDB($ticket3->getID());
        $output = \Glpi\Dashboard\Provider::nbTicketsByAgreementStatusAndTechnician();
        $expected = [
            'label' => "Tickets by SLA status and by technician",
            'data' => [
                'labels' => ['tech', 'glpi'],
                'series' => [
                    [
                        'name' => 'Late own and resolve',
                        'data' => [2, 1]
                    ],
                    [
                        'name' => 'Late resolve',
                        'data' => [0, 0]
                    ],
                    [
                        'name' => 'Late own',
                        'data' => [0, 0]
                    ],
                    [
                        'name' => 'On time',
                        'data' => [0, 0]
                    ]
                ]
            ],
            'icon' => 'fas fa-stopwatch'
        ];
        $this->array($output)->isEqualTo($expected);
    }


    public function testNbTicketsByAgreementStatusAndTechnicianGroup()
    {
        global $DB;

       // Prepare context
        $slm = new \Slm();
        $slm->add([
            'name' => 'SLM',
        ]);

        $slaTto = new \SLA();
        $slaTto->add([
            'name' => 'sla tto',
            'type' => '1', // TTO
            'number_time' => 4,
            'definition_time' => 'hour',
            'slms_id' => $slm->getID(),
        ]);

        $slaTtr = new \SLA();
        $slaTtr->add([
            'name' => 'sla ttr',
            'type' => '0', // TTR
            'number_time' => 4,
            'definition_time' => 'hour',
            'slms_id' => $slm->getID(),
        ]);

        $group = new \Group();
        $group->add([
            'entities_id' => 0,
            'name'        => 'group sla test',
            'level'       => 1,
            'groups_id'   => 0,
        ]);
        $this->boolean($group->isNewItem())->isFalse();

        $group2 = new \Group();
        $group2->add([
            'entities_id' => 0,
            'name'        => 'second group sla test',
            'level'       => 1,
            'groups_id'   => 0,
        ]);
        $this->boolean($group2->isNewItem())->isFalse();

        $ticket = new \Ticket();
        $ticket->add([
            'name' => "test dashboard card SLA / tech",
            'content' => 'foo',
            '_groups_id_assign' => $group->getID(),
            'sla_id_tto'       => $slaTto->getID(),
            'sla_id_ttr'       => $slaTtr->getID(),
            'status'           => \Ticket::ASSIGNED,
        ]);
        $this->boolean($ticket->isNewItem())->isFalse();

        $ticket2 = new \Ticket();
        $ticket2->add([
            'name' => "test dashboard card SLA / tech for second group",
            'content' => 'foo',
            '_groups_id_assign' => $group2->getID(),
            'sla_id_tto'       => $slaTto->getID(),
            'sla_id_ttr'       => $slaTtr->getID(),
            'status'           => \Ticket::ASSIGNED,
        ]);
        $this->boolean($ticket2->isNewItem())->isFalse();

        $ticket3 = new \Ticket();
        $ticket3->add([
            'name' => "test dashboard card SLA / tech for second group",
            'content' => 'foo',
            '_groups_id_assign' => $group2->getID(),
            'sla_id_tto'       => $slaTto->getID(),
            'sla_id_ttr'       => $slaTtr->getID(),
            'status'           => \Ticket::ASSIGNED,
        ]);

        $output = \Glpi\Dashboard\Provider::nbTicketsByAgreementStatusAndTechnicianGroup();
        $expected = [
            'label' => "Tickets by SLA status and by technician group",
            'data' => [
                'labels' => ['second group sla test', 'group sla test'],
                'series' => [
                    [
                        'name' => 'Late own and resolve',
                        'data' => [0, 0]
                    ],
                    [
                        'name' => 'Late resolve',
                        'data' => [0, 0]
                    ],
                    [
                        'name' => 'Late own',
                        'data' => [0, 0]
                    ],
                    [
                        'name' => 'On time',
                        'data' => [2, 1]
                    ]
                ]
            ],
            'icon' => 'fas fa-stopwatch'
        ];
        $this->array($output)->isEqualTo($expected);

        $DB->update(
            $ticket::getTable(),
            [
                'date'                       => '2021-01-01 00:00',
                'time_to_own'                => '2021-01-01 01:00',
                'takeintoaccount_delay_stat' => 50000,
            ],
            [
                'id' => $ticket->getID()
            ]
        );
        $DB->update(
            $ticket2::getTable(),
            [
                'date'                       => '2021-01-01 00:00',
                'time_to_own'                => '2021-01-01 01:00',
                'takeintoaccount_delay_stat' => 50000,
            ],
            [
                'id' => $ticket2->getID()
            ]
        );
        $ticket->getFromDB($ticket->getID());
        $ticket2->getFromDB($ticket2->getID());

        $output = \Glpi\Dashboard\Provider::nbTicketsByAgreementStatusAndTechnicianGroup();
        $expected = [
            'label' => "Tickets by SLA status and by technician group",
            'data' => [
                'labels' => ['group sla test', 'second group sla test'],
                'series' => [
                    [
                        'name' => 'Late own and resolve',
                        'data' => [0, 0]
                    ],
                    [
                        'name' => 'Late resolve',
                        'data' => [0, 0]
                    ],
                    [
                        'name' => 'Late own',
                        'data' => [1, 1]
                    ],
                    [
                        'name' => 'On time',
                        'data' => [0, 1]
                    ]
                ]
            ],
            'icon' => 'fas fa-stopwatch'
        ];
        $this->array($output)->isEqualTo($expected);

        $DB->update(
            $ticket::getTable(),
            [
                'date'                       => '2021-01-01 00:00',
                'time_to_own'                => '2021-01-01 01:00',
                'takeintoaccount_delay_stat' => 60,
                'solvedate'                  => '2021-02-01 00:00',
                'time_to_resolve'            => '2021-01-02 00:00'
            ],
            [
                'id' => $ticket->getID()
            ]
        );
        $DB->update(
            $ticket2::getTable(),
            [
                'date'                       => '2021-01-01 00:00',
                'time_to_own'                => '2021-01-01 01:00',
                'takeintoaccount_delay_stat' => 60,
                'solvedate'                  => '2021-02-01 00:00',
                'time_to_resolve'            => '2021-01-02 00:00'
            ],
            [
                'id' => $ticket2->getID()
            ]
        );
        $ticket->getFromDB($ticket->getID());
        $ticket2->getFromDB($ticket2->getID());
        $output = \Glpi\Dashboard\Provider::nbTicketsByAgreementStatusAndTechnicianGroup();
        $expected = [
            'label' => "Tickets by SLA status and by technician group",
            'data' => [
                'labels' => ['group sla test', 'second group sla test'],
                'series' => [
                    [
                        'name' => 'Late own and resolve',
                        'data' => [0, 0]
                    ],
                    [
                        'name' => 'Late resolve',
                        'data' => [1, 1]
                    ],
                    [
                        'name' => 'Late own',
                        'data' => [0, 0]
                    ],
                    [
                        'name' => 'On time',
                        'data' => [0, 1]
                    ]
                ]
            ],
            'icon' => 'fas fa-stopwatch'
        ];
        $this->array($output)->isEqualTo($expected);

        $DB->update(
            $ticket::getTable(),
            [
                'date'                       => '2021-01-01 00:00',
                'time_to_own'                => '2021-01-01 01:00',
                'takeintoaccount_delay_stat' => 50000,
                'solvedate'                  => '2021-02-01 00:00',
                'time_to_resolve'            => '2021-01-02 00:00'
            ],
            [
                'id' => $ticket->getID()
            ]
        );
        $DB->update(
            $ticket2::getTable(),
            [
                'date'                       => '2021-01-01 00:00',
                'time_to_own'                => '2021-01-01 01:00',
                'takeintoaccount_delay_stat' => 50000,
                'solvedate'                  => '2021-02-01 00:00',
                'time_to_resolve'            => '2021-01-02 00:00'
            ],
            [
                'id' => $ticket2->getID()
            ]
        );
        $DB->update(
            $ticket3::getTable(),
            [
                'date'                       => '2021-01-01 00:00',
                'time_to_own'                => '2021-01-01 01:00',
                'takeintoaccount_delay_stat' => 50000,
                'solvedate'                  => '2021-02-01 00:00',
                'time_to_resolve'            => '2021-01-02 00:00'
            ],
            [
                'id' => $ticket3->getID()
            ]
        );
        $ticket->getFromDB($ticket->getID());
        $ticket2->getFromDB($ticket2->getID());
        $ticket3->getFromDB($ticket3->getID());
        $output = \Glpi\Dashboard\Provider::nbTicketsByAgreementStatusAndTechnicianGroup();
        $expected = [
            'label' => "Tickets by SLA status and by technician group",
            'data' => [
                'labels' => ['second group sla test', 'group sla test'],
                'series' => [
                    [
                        'name' => 'Late own and resolve',
                        'data' => [2, 1]
                    ],
                    [
                        'name' => 'Late resolve',
                        'data' => [0, 0]
                    ],
                    [
                        'name' => 'Late own',
                        'data' => [0, 0]
                    ],
                    [
                        'name' => 'On time',
                        'data' => [0, 0]
                    ]
                ]
            ],
            'icon' => 'fas fa-stopwatch'
        ];
        $this->array($output)->isEqualTo($expected);
    }
    public function itemProvider()
    {
        return [
            ['item' => new \Computer()],
            ['item' => new \Ticket()],
            ['item' => new \Item_DeviceSimcard()],
        ];
    }

    /**
     * @dataProvider itemProvider
     */
    public function testBigNumber(\CommonDBTM $item)
    {
        $this->login();

        $itemtype = $item->getType();
        $data = [
            \Glpi\Dashboard\Provider::bigNumberItem($item),
            call_user_func(['\\Glpi\\Dashboard\\Provider', "bigNumber$itemtype"])
        ];

        foreach ($data as $result) {
            $this->array($result)
            ->hasKeys([
                'number',
                'url',
                'label',
                'icon',
            ]);
            if ($item::getType() !== 'Item_DeviceSimcard') {
                // Ignore count for simcards. None are added in Bootstrap process and is here for regression testing only.
                $this->integer($result['number'])->isGreaterThan(0);
            }
            $this->string($result['url'])->contains($item::getSearchURL());
            //Verify URL doesn't have two query param joiners next to each other
            $this->string($result['url'])->notContains('&&');
            $this->string($result['url'])->notContains('?&');
            //Verify URL only has one ? joiner
            $this->integer(substr_count($result['url'], '?'))->isLessThanOrEqualTo(1);
            $this->string($result['label'])->isNotEmpty();
            $this->string($result['icon'])->isEqualTo($item::getIcon());
        }
    }


    public function ticketsCaseProvider()
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


    /**
     * @dataProvider ticketsCaseProvider
     */
    public function testNbTicketsGeneric(string $case)
    {
        $result = \Glpi\Dashboard\Provider::nbTicketsGeneric($case);

        $this->array($result)
         ->hasKeys([
             'number',
             'url',
             'label',
             'icon',
             's_criteria',
             'itemtype',
         ]);
        $this->integer($result['number']);
        $this->string($result['url'])->contains(\Ticket::getSearchURL());
        $this->string($result['icon']);
        $this->string($result['label']);
        $this->array($result['s_criteria'])->size->isGreaterThan(0);
        $this->string($result['itemtype'])->isEqualTo('Ticket');
    }


    public function itemFKProvider()
    {
        return [
            ['item' => new \Computer(), 'fk_item' => new \Entity()],
            ['item' => new \Software(), 'fk_item' => new \Entity()],
        ];
    }


    /**
     * @dataProvider itemFKProvider
     */
    public function testNbItemByFk(\CommonDBTM $item, \CommonDBTM $fk_item)
    {
        $this->login();

        $result = \Glpi\Dashboard\Provider::nbItemByFk($item, $fk_item);
        $this->array($result)
         ->hasKeys([
             'data',
             'label',
             'icon',
         ]);

        foreach ($result['data'] as $data) {
            $this->array($data)
            ->hasKeys([
                'number',
                'label',
                'url',
            ]);

            $this->integer($data['number'])->isGreaterThan(0);
            $this->string($data['label']);
            $this->string($data['url'])->contains($item::getSearchURL());
        }
    }


    public function testTicketsOpened()
    {
        $result = \Glpi\Dashboard\Provider::ticketsOpened();
        $this->array($result)
         ->hasKeys([
             'data',
             'distributed',
             'label',
             'icon',
         ]);

        $this->boolean($result['distributed'])->isFalse();
        $this->string($result['icon']);
        $this->string($result['label']);

        foreach ($result['data'] as $data) {
            $this->array($data)
            ->hasKeys([
                'number',
                'label',
                'url',
            ]);

            $this->integer($data['number'])->isGreaterThan(0);
            $this->string($data['label']);
            $this->string($data['url'])->contains(\Ticket::getSearchURL());
        }
    }


    public function testGetTicketsEvolution()
    {
        $result = \Glpi\Dashboard\Provider::getTicketsEvolution();
        $this->array($result)
         ->hasKeys([
             'data',
             'label',
             'icon',
         ]);

        $this->string($result['icon']);
        $this->string($result['label']);
        $this->array($result['data'])->hasKeys(['labels', 'series']);
        $this->array($result['data']['labels'])->isNotEmpty();
        $this->array($result['data']['series'])->isNotEmpty();

        $nb_labels = count($result['data']['labels']);
        foreach ($result['data']['series'] as $serie) {
            $this->array($serie)->hasKey('data');
            $this->integer(count($serie['data']))->isEqualTo($nb_labels);

            foreach ($serie['data'] as $serie_data) {
                $this->integer($serie_data['value']);
                $this->string($serie_data['url'])->contains(\Ticket::getSearchURL());
            }
        }
    }


    public function testGetTicketsStatus()
    {
        $this->login();

        $result = \Glpi\Dashboard\Provider::getTicketsStatus();
        $this->array($result)
         ->hasKeys([
             'data',
             'label',
             'icon',
         ]);

        $this->string($result['icon']);
        $this->string($result['label']);
        $this->array($result['data'])->hasKeys(['labels', 'series']);
        $this->array($result['data']['labels'])->isNotEmpty();
        $this->array($result['data']['series'])->isNotEmpty();

        $nb_labels = count($result['data']['labels']);
        foreach ($result['data']['series'] as $serie) {
            $this->array($serie)->hasKey('data');
            $this->integer(count($serie['data']))->isEqualTo($nb_labels);

            foreach ($serie['data'] as $serie_data) {
                $this->integer($serie_data['value']);
                $this->string($serie_data['url'])->contains(\Ticket::getSearchURL());
            }
        }
    }


    public function testTopTicketsCategories()
    {
        $this->login();

        $result = \Glpi\Dashboard\Provider::multipleNumberTicketByITILCategory();
        $this->array($result)
         ->hasKeys([
             'data',
             'label',
             'icon',
         ]);

        $this->string($result['icon']);
        $this->string($result['label']);

        foreach ($result['data'] as $data) {
            $this->array($data)
            ->hasKeys([
                'number',
                'label',
                'url',
            ]);

            $this->integer($data['number'])->isGreaterThan(0);
            $this->string($data['label']);
            $this->string($data['url'])->contains(\Ticket::getSearchURL());
        }
    }

    public function monthYearProvider()
    {
        return [
            [
                'monthyear' => '2019-01',
                'expected'  => [
                    '2019-01-01 00:00:00',
                    '2019-02-01 00:00:00'
                ]
            ], [
                'monthyear' => '2019-12',
                'expected'  => [
                    '2019-12-01 00:00:00',
                    '2020-01-01 00:00:00'
                ]
            ]
        ];
    }


    /**
     * @dataProvider monthYearProvider
     */
    public function testFormatMonthyearDates(string $monthyear, array $expected)
    {
        $this->array(\Glpi\Dashboard\Provider::formatMonthyearDates($monthyear))
         ->isEqualTo($expected);
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
            'users_id'  => $tech->getID()
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

    /**
     * @dataProvider testGetArticleListReminderProvider
     */
    public function testGetArticleListReminder(int $expected): void
    {
        $results = \Glpi\Dashboard\Provider::getArticleListReminder();
        $this->integer($results['number'])->isEqualTo($expected);
    }
}
