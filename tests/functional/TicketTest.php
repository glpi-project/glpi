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

use Calendar;
use CalendarSegment;
use CommonITILActor;
use CommonITILObject;
use CommonITILSatisfaction;
use CommonITILValidation;
use Computer;
use Contract;
use CronTask;
use DbTestCase;
use Entity;
use Glpi\Search\SearchOption;
use Glpi\Team\Team;
use Glpi\Tests\Glpi\ITILTrait;
use Glpi\Tests\Glpi\ValidationStepTrait;
use Group;
use Group_Ticket;
use Group_User;
use ITILCategory;
use ITILFollowup;
use ITILSolution;
use PHPUnit\Framework\Attributes\DataProvider;
use Profile;
use Profile_User;
use ProfileRight;
use Psr\Log\LogLevel;
use Rule;
use Search;
use Session;
use SLA;
use SLM;
use Supplier;
use Supplier_Ticket;
use Symfony\Component\DomCrawler\Crawler;
use Ticket;
use Ticket_Contract;
use Ticket_User;
use TicketSatisfaction;
use TicketValidation;
use User;

/* Test for inc/ticket.class.php */

class TicketTest extends DbTestCase
{
    use ValidationStepTrait;
    use ITILTrait;

    public static function addActorsProvider(): iterable
    {
        $default_use_notifications = 1;

        $admin_user_id    = getItemByTypeName(User::class, 'glpi', true);
        $tech_user_id     = getItemByTypeName(User::class, 'tech', true);
        $normal_user_id   = getItemByTypeName(User::class, 'normal', true);
        $postonly_user_id = getItemByTypeName(User::class, 'post-only', true);

        $group_1_id = getItemByTypeName(Group::class, '_test_group_1', true);
        $group_2_id = getItemByTypeName(Group::class, '_test_group_2', true);

        $supplier_id = getItemByTypeName(Supplier::class, '_suplier01_name', true);

        $actor_types = ['requester', 'assign', 'observer'];

        foreach ($actor_types as $actor_type) {
            $actor_type_value = constant(CommonITILActor::class . '::' . strtoupper($actor_type));

            // single user
            $expected_actors = [
                [
                    'type'              => $actor_type_value,
                    'itemtype'          => User::class,
                    'items_id'          => $tech_user_id,
                    'use_notification'  => $default_use_notifications,
                    'alternative_email' => '',
                ],
            ];
            // using historical keys
            yield [
                'actors_input'   => [
                    "_users_id_{$actor_type}" => "$tech_user_id",
                ],
                'expected_actors' => $expected_actors,
            ];
            // using _actors key
            yield [
                'actors_input'   => [
                    '_actors' => [
                        $actor_type => [
                            [
                                'itemtype'          => User::class,
                                'items_id'          => $tech_user_id,
                                'use_notification'  => $default_use_notifications,
                                'alternative_email' => '',
                            ],
                        ],
                    ],
                ],
                'expected_actors' => $expected_actors,
            ];

            // single email actor
            $expected_actors = [
                [
                    'type'              => $actor_type_value,
                    'itemtype'          => User::class,
                    'items_id'          => 0,
                    'use_notification'  => 1,
                    'alternative_email' => 'unknownuser@localhost.local',
                ],
            ];
            // using historical keys
            yield [
                'actors_input'   => [
                    "_users_id_{$actor_type}"       => '0',
                    "_users_id_{$actor_type}_notif" => [
                        'use_notification'   => '1',
                        'alternative_email'  => 'unknownuser@localhost.local',
                    ],
                ],
                'expected_actors' => $expected_actors,
            ];
            // using _actors key
            yield [
                'actors_input'   => [
                    '_actors' => [
                        $actor_type => [
                            [
                                'itemtype'          => User::class,
                                'items_id'          => 0,
                                'use_notification'  => 1,
                                'alternative_email' => 'unknownuser@localhost.local',
                            ],
                        ],
                    ],
                ],
                'expected_actors' => $expected_actors,
            ];

            // single group
            $expected_actors = [
                [
                    'type'     => $actor_type_value,
                    'itemtype' => Group::class,
                    'items_id' => $group_1_id,
                ],
            ];
            // using historical keys
            yield [
                'actors_input'   => [
                    "_groups_id_{$actor_type}" => "$group_1_id",
                ],
                'expected_actors' => $expected_actors,
            ];
            // using _actors key
            yield [
                'actors_input'   => [
                    '_actors' => [
                        $actor_type => [
                            [
                                'itemtype' => Group::class,
                                'items_id' => $group_1_id,
                            ],
                        ],
                    ],
                ],
                'expected_actors' => $expected_actors,
            ];

            // multiple actors
            $expected_actors = [
                [
                    'type'              => $actor_type_value,
                    'itemtype'          => User::class,
                    'items_id'          => $tech_user_id,
                    'use_notification'  => 1,
                    'alternative_email' => 'alt-email@localhost.local',
                ],
                [
                    'type'              => $actor_type_value,
                    'itemtype'          => User::class,
                    'items_id'          => $admin_user_id,
                    'use_notification'  => 0,
                    'alternative_email' => '',
                ],
                [
                    'type'              => $actor_type_value,
                    'itemtype'          => User::class,
                    'items_id'          => 0,
                    'use_notification'  => 1,
                    'alternative_email' => 'unknownuser1@localhost.local',
                ],
                [
                    'type'              => $actor_type_value,
                    'itemtype'          => User::class,
                    'items_id'          => 0,
                    'use_notification'  => 1,
                    'alternative_email' => 'unknownuser2@localhost.local',
                ],
                [
                    'type'              => $actor_type_value,
                    'itemtype'          => Group::class,
                    'items_id'          => $group_1_id,
                ],
                [
                    'type'              => $actor_type_value,
                    'itemtype'          => Group::class,
                    'items_id'          => $group_2_id,
                ],
            ];
            // using historical keys
            yield [
                'actors_input'   => [
                    "_users_id_{$actor_type}"       => ["$tech_user_id", "$admin_user_id", '0', '0'],
                    "_users_id_{$actor_type}_notif" => [
                        'use_notification'   => ['1', '0', '1', '1'],
                        'alternative_email'  => ['alt-email@localhost.local', '', 'unknownuser1@localhost.local', 'unknownuser2@localhost.local'],
                    ],
                    "_groups_id_{$actor_type}"      => ["$group_1_id", "$group_2_id"],
                ],
                'expected_actors' => $expected_actors,
            ];
            // using _actors key
            yield [
                'actors_input'   => [
                    '_actors' => [
                        $actor_type => [
                            [
                                'itemtype'          => User::class,
                                'items_id'          => $tech_user_id,
                                'use_notification'  => 1,
                                'alternative_email' => 'alt-email@localhost.local',
                            ],
                            [
                                'itemtype'          => User::class,
                                'items_id'          => $admin_user_id,
                                'use_notification'  => 0,
                                'alternative_email' => '',
                            ],
                            [
                                'itemtype'          => User::class,
                                'items_id'          => 0,
                                'use_notification'  => 1,
                                'alternative_email' => 'unknownuser1@localhost.local',
                            ],
                            [
                                'itemtype'          => User::class,
                                'items_id'          => 0,
                                'use_notification'  => 1,
                                'alternative_email' => 'unknownuser2@localhost.local',
                            ],
                            [
                                'itemtype'          => Group::class,
                                'items_id'          => $group_1_id,
                            ],
                            [
                                'itemtype'          => Group::class,
                                'items_id'          => $group_2_id,
                            ],
                        ],
                    ],
                ],
                'expected_actors' => $expected_actors,
            ];
            // using mix between historical keys and _actors key
            yield [
                'actors_input'   => [
                    "_users_id_{$actor_type}"       => ["$tech_user_id"],
                    "_users_id_{$actor_type}_notif" => [
                        'use_notification'   => ['1'],
                        'alternative_email'  => ['alt-email@localhost.local'],
                    ],
                    '_actors' => [
                        $actor_type => [
                            [
                                'itemtype'          => User::class,
                                'items_id'          => $admin_user_id,
                                'use_notification'  => 0,
                                'alternative_email' => '',
                            ],
                            [
                                'itemtype'          => User::class,
                                'items_id'          => 0,
                                'use_notification'  => 1,
                                'alternative_email' => 'unknownuser1@localhost.local',
                            ],
                            [
                                'itemtype'          => User::class,
                                'items_id'          => 0,
                                'use_notification'  => 1,
                                'alternative_email' => 'unknownuser2@localhost.local',
                            ],
                            [
                                'itemtype'          => Group::class,
                                'items_id'          => $group_1_id,
                            ],
                            [
                                'itemtype'          => Group::class,
                                'items_id'          => $group_2_id,
                            ],
                        ],
                    ],
                ],
                'expected_actors' => $expected_actors,
            ];
        }

        // complete mix
        $expected_actors = [
            [
                'type'              => CommonITILActor::REQUESTER,
                'itemtype'          => User::class,
                'items_id'          => $postonly_user_id,
                'use_notification'  => $default_use_notifications,
                'alternative_email' => '',
            ],
            [
                'type'              => CommonITILActor::REQUESTER,
                'itemtype'          => User::class,
                'items_id'          => $normal_user_id,
                'use_notification'  => $default_use_notifications,
                'alternative_email' => '',
            ],
            [
                'type'              => CommonITILActor::OBSERVER,
                'itemtype'          => User::class,
                'items_id'          => $normal_user_id,
                'use_notification'  => 0,
                'alternative_email' => '',
            ],
            [
                'type'              => CommonITILActor::OBSERVER,
                'itemtype'          => User::class,
                'items_id'          => 0,
                'use_notification'  => 1,
                'alternative_email' => 'obs1@localhost.local',
            ],
            [
                'type'              => CommonITILActor::OBSERVER,
                'itemtype'          => User::class,
                'items_id'          => 0,
                'use_notification'  => 1,
                'alternative_email' => 'obs1@localhost.local',
            ],
            [
                'type'              => CommonITILActor::OBSERVER,
                'itemtype'          => Group::class,
                'items_id'          => $group_1_id,
            ],
            [
                'type'              => CommonITILActor::ASSIGN,
                'itemtype'          => User::class,
                'items_id'          => $tech_user_id,
                'use_notification'  => 1,
                'alternative_email' => 'alternativeemail@localhost.local',
            ],
            [
                'type'              => CommonITILActor::ASSIGN,
                'itemtype'          => Group::class,
                'items_id'          => $group_2_id,
            ],
            [
                'type'              => CommonITILActor::ASSIGN,
                'itemtype'          => Supplier::class,
                'items_id'          => $supplier_id,
            ],
        ];
        // using historical keys
        yield [
            'actors_input'   => [
                '_users_id_requester'       => ["$postonly_user_id", "$normal_user_id"],
                '_users_id_observer'        => ["$normal_user_id", '0', '0'],
                '_users_id_observer_notif'  => [
                    'use_notification'   => ['0', '1', '1'],
                    'alternative_email'  => ['', 'obs1@localhost.local', 'obs2@localhost.local'],
                ],
                '_groups_id_observer'       => ["$group_1_id"],
                '_users_id_assign'          => ["$tech_user_id"],
                '_users_id_assign_notif'    => [
                    'use_notification'   => ['1'],
                    'alternative_email'  => ['alternativeemail@localhost.local'],
                ],
                '_groups_id_assign'         => ["$group_2_id"],
                '_suppliers_id_assign'      => ["$supplier_id"],
            ],
            'expected_actors' => $expected_actors,
        ];
        // using _actors key
        yield [
            'actors_input'   => [
                '_actors' => [
                    'requester' => [
                        [
                            'itemtype'          => User::class,
                            'items_id'          => $postonly_user_id,
                            'use_notification'  => $default_use_notifications,
                            'alternative_email' => '',
                        ],
                        [
                            'itemtype'          => User::class,
                            'items_id'          => $normal_user_id,
                            'use_notification'  => $default_use_notifications,
                            'alternative_email' => '',
                        ],
                    ],
                    'observer' => [
                        [
                            'itemtype'          => User::class,
                            'items_id'          => $normal_user_id,
                            'use_notification'  => 0,
                            'alternative_email' => '',
                        ],
                        [
                            'itemtype'          => User::class,
                            'items_id'          => 0,
                            'use_notification'  => 1,
                            'alternative_email' => 'obs1@localhost.local',
                        ],
                        [
                            'itemtype'          => User::class,
                            'items_id'          => 0,
                            'use_notification'  => 1,
                            'alternative_email' => 'obs2@localhost.local',
                        ],
                        [
                            'itemtype'          => Group::class,
                            'items_id'          => $group_1_id,
                        ],
                    ],
                    'assign' => [
                        [
                            'itemtype'          => User::class,
                            'items_id'          => $tech_user_id,
                            'use_notification'  => 1,
                            'alternative_email' => 'alternativeemail@localhost.local',
                        ],
                        [
                            'itemtype'          => Group::class,
                            'items_id'          => $group_2_id,
                        ],
                        [
                            'itemtype'          => Supplier::class,
                            'items_id'          => $supplier_id,
                        ],
                    ],
                ],
            ],
            'expected_actors' => $expected_actors,
        ];
        // using mix between historical keys and _actors key
        yield [
            'actors_input'   => [
                '_users_id_requester'        => ["$postonly_user_id", "$normal_user_id"],
                '_users_id_observer'        => ['0'],
                '_users_id_observer_notif'  => [
                    'use_notification'   => ['1'],
                    'alternative_email'  => ['obs2@localhost.local'],
                ],
                '_actors' => [
                    'requester' => [
                        // Duplicates actor defined in "_users_id_requester", should not be a problem
                        [
                            'itemtype'          => User::class,
                            'items_id'          => $postonly_user_id,
                            'use_notification'  => $default_use_notifications,
                            'alternative_email' => '',
                        ],
                    ],
                    'observer' => [
                        [
                            'itemtype'          => User::class,
                            'items_id'          => $normal_user_id,
                            'use_notification'  => 0,
                            'alternative_email' => '',
                        ],
                        [
                            'itemtype'          => User::class,
                            'items_id'          => 0,
                            'use_notification'  => 1,
                            'alternative_email' => 'obs1@localhost.local',
                        ],
                        [
                            'itemtype'          => Group::class,
                            'items_id'          => $group_1_id,
                        ],
                    ],
                    'assign' => [
                        [
                            'itemtype'          => User::class,
                            'items_id'          => $tech_user_id,
                            'use_notification'  => 1,
                            'alternative_email' => 'alternativeemail@localhost.local',
                        ],
                        [
                            'itemtype'          => Group::class,
                            'items_id'          => $group_2_id,
                        ],
                        [
                            'itemtype'          => Supplier::class,
                            'items_id'          => $supplier_id,
                        ],
                    ],
                ],
            ],
            'expected_actors' => $expected_actors,
        ];
    }

    #[DataProvider('addActorsProvider')]
    public function testCreateTicketWithActors(array $actors_input, array $expected_actors): void
    {
        $this->login();

        $ticket = new Ticket();
        $ticket_id = $ticket->add(
            [
                'name'        => 'ticket title',
                'content'     => 'a description',
                'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            ] + $actors_input
        );
        $this->assertGreaterThan(0, $ticket_id);

        $this->checkActors($ticket, $expected_actors);
    }

    public function testSearchOptions()
    {
        $this->login();

        $last_followup_date = '2016-01-01 00:00:00';
        $last_task_date = '2017-01-01 00:00:00';
        $last_solution_date = '2018-01-01 00:00:00';

        $ticket = new Ticket();
        $ticket_id = $ticket->add(
            [
                'name'        => 'ticket title',
                'content'     => 'a description',
                'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            ]
        );

        $followup = new ITILFollowup();
        $followup->add([
            'itemtype'  => $ticket::getType(),
            'items_id' => $ticket_id,
            'content'    => 'followup content',
            'date'       => '2015-01-01 00:00:00',
        ]);

        $followup->add([
            'itemtype'  => $ticket::getType(),
            'items_id' => $ticket_id,
            'content'    => 'followup content',
            'date'       => '2015-02-01 00:00:00',
        ]);

        $task = new \TicketTask();
        $this->assertGreaterThan(
            0,
            (int) $task->add([
                'tickets_id'   => $ticket_id,
                'content'      => 'A simple Task',
                'date'         => '2015-01-01 00:00:00',
            ])
        );

        $this->assertGreaterThan(
            0,
            (int) $task->add([
                'tickets_id'   => $ticket_id,
                'content'      => 'A simple Task',
                'date'         => $last_task_date,
            ])
        );

        $this->assertGreaterThan(
            0,
            (int) $task->add([
                'tickets_id'   => $ticket_id,
                'content'      => 'A simple Task',
                'date'         => '2016-01-01 00:00:00',
            ])
        );

        $solution = new ITILSolution();
        $this->assertGreaterThan(
            0,
            (int) $solution->add([
                'itemtype'  => $ticket::getType(),
                'items_id' => $ticket_id,
                'content'    => 'solution content',
                'date_creation' => '2017-01-01 00:00:00',
                'status' => 2,
            ])
        );

        $this->assertGreaterThan(
            0,
            (int) $followup->add([
                'itemtype'  => $ticket::getType(),
                'items_id'  => $ticket_id,
                'add_reopen'   => '1',
                'content'      => 'This is required',
                'date'         => $last_followup_date,
            ])
        );

        $this->assertGreaterThan(
            0,
            (int) $solution->add([
                'itemtype'  => $ticket::getType(),
                'items_id' => $ticket_id,
                'content'    => 'solution content',
                'date_creation' => $last_solution_date,
            ])
        );

        $criteria = [
            [
                'link' => 'AND',
                'field' => 2,
                'searchtype' => 'contains',
                'value' => $ticket_id,
            ],
        ];
        $data   = Search::getDatas($ticket->getType(), ["criteria" => $criteria], [72,73,74]);
        $this->assertSame(1, $data['data']['totalcount']);
        $ticket_with_so = $data['data']['rows'][0]['raw'];
        $this->assertEquals($ticket_id, $ticket_with_so['id']);
        $this->assertTrue(array_key_exists('ITEM_Ticket_72', $ticket_with_so));
        $this->assertEquals($last_followup_date, $ticket_with_so['ITEM_Ticket_72']);
        $this->assertTrue(array_key_exists('ITEM_Ticket_73', $ticket_with_so));
        $this->assertEquals($last_task_date, $ticket_with_so['ITEM_Ticket_73']);
        $this->assertTrue(array_key_exists('ITEM_Ticket_74', $ticket_with_so));
        $this->assertEquals($last_solution_date, $ticket_with_so['ITEM_Ticket_74']);
    }


    public static function updateActorsProvider(): iterable
    {
        foreach (self::addActorsProvider() as $params) {
            yield [
                'add_actors_input'       => [],
                'add_expected_actors'    => [],
                'update_actors_input'    => $params['actors_input'],
                'update_expected_actors' => $params['expected_actors'],
            ];

            // Update without an actor input should not change actors
            yield [
                'add_actors_input'       => $params['actors_input'],
                'add_expected_actors'    => $params['expected_actors'],
                'update_actors_input'    => [],
                'update_expected_actors' => $params['expected_actors'],
            ];
        }

        $postonly_user_id = getItemByTypeName(User::class, 'post-only', true);

        $actor_types = ['requester', 'assign', 'observer'];
        foreach ($actor_types as $actor_type) {
            $actor_type_value = constant(CommonITILActor::class . '::' . strtoupper($actor_type));

            // single email actor updated
            yield [
                'add_actors_input'       => [
                    '_actors' => [
                        $actor_type => [
                            [
                                'itemtype'          => User::class,
                                'items_id'          => 0,
                                'use_notification'  => 1,
                                'alternative_email' => 'extern1@localhost.local',
                            ],
                        ],
                    ],
                ],
                'add_expected_actors'    => [
                    [
                        'type'              => $actor_type_value,
                        'itemtype'          => User::class,
                        'items_id'          => 0,
                        'use_notification'  => 1,
                        'alternative_email' => 'extern1@localhost.local',
                    ],
                ],
                'update_actors_input'    => [
                    '_actors' => [
                        $actor_type => [
                            [
                                'itemtype'          => User::class,
                                'items_id'          => 0,
                                'use_notification'  => 0,
                                'alternative_email' => 'extern1@localhost.local',
                            ],
                        ],
                    ],
                ],
                'update_expected_actors' => [
                    [
                        'type'              => $actor_type_value,
                        'itemtype'          => User::class,
                        'items_id'          => 0,
                        'use_notification'  => 0,
                        'alternative_email' => 'extern1@localhost.local',
                    ],
                ],
            ];

            // single email actor replaced
            yield [
                'add_actors_input'       => [
                    '_actors' => [
                        $actor_type => [
                            [
                                'itemtype'          => User::class,
                                'items_id'          => 0,
                                'use_notification'  => 1,
                                'alternative_email' => 'extern1@localhost.local',
                            ],
                        ],
                    ],
                ],
                'add_expected_actors'    => [
                    [
                        'type'              => $actor_type_value,
                        'itemtype'          => User::class,
                        'items_id'          => 0,
                        'use_notification'  => 1,
                        'alternative_email' => 'extern1@localhost.local',
                    ],
                ],
                'update_actors_input'    => [
                    '_actors' => [
                        $actor_type => [
                            [
                                'itemtype'          => User::class,
                                'items_id'          => 0,
                                'use_notification'  => 0,
                                'alternative_email' => 'extern2@localhost.local',
                            ],
                        ],
                    ],
                ],
                'update_expected_actors' => [
                    [
                        'type'              => $actor_type_value,
                        'itemtype'          => User::class,
                        'items_id'          => 0,
                        'use_notification'  => 0,
                        'alternative_email' => 'extern2@localhost.local',
                    ],
                ],
            ];

            // single email actor removed
            yield [
                'add_actors_input'       => [
                    '_actors' => [
                        $actor_type => [
                            [
                                'itemtype'          => User::class,
                                'items_id'          => 0,
                                'use_notification'  => 1,
                                'alternative_email' => 'extern1@localhost.local',
                            ],
                        ],
                    ],
                ],
                'add_expected_actors'    => [
                    [
                        'type'              => $actor_type_value,
                        'itemtype'          => User::class,
                        'items_id'          => 0,
                        'use_notification'  => 1,
                        'alternative_email' => 'extern1@localhost.local',
                    ],
                ],
                'update_actors_input'    => [
                    '_actors' => [
                        $actor_type => [
                        ],
                    ],
                ],
                'update_expected_actors' => [],
            ];

            // add multiple actors, including multiple email actors, add an update for one of them (in mixed order)
            // to validate that the expected email actor is updated
            // also remove an email actor
            yield [
                'add_actors_input'       => [
                    '_actors' => [
                        $actor_type => [
                            [
                                'itemtype'          => User::class,
                                'items_id'          => 0,
                                'use_notification'  => 1,
                                'alternative_email' => 'extern1@localhost.local',
                            ],
                            [
                                'itemtype'          => User::class,
                                'items_id'          => $postonly_user_id,
                                'use_notification'  => 1,
                                'alternative_email' => '',
                            ],
                            [
                                'itemtype'          => User::class,
                                'items_id'          => 0,
                                'use_notification'  => 1,
                                'alternative_email' => 'extern2@localhost.local',
                            ],
                            [
                                'itemtype'          => User::class,
                                'items_id'          => 0,
                                'use_notification'  => 1,
                                'alternative_email' => 'extern3@localhost.local',
                            ],
                        ],
                    ],
                ],
                'add_expected_actors'    => [
                    [
                        'type'              => $actor_type_value,
                        'itemtype'          => User::class,
                        'items_id'          => 0,
                        'use_notification'  => 1,
                        'alternative_email' => 'extern1@localhost.local',
                    ],
                    [
                        'type'              => $actor_type_value,
                        'itemtype'          => User::class,
                        'items_id'          => $postonly_user_id,
                        'use_notification'  => 1,
                        'alternative_email' => '',
                    ],
                    [
                        'type'              => $actor_type_value,
                        'itemtype'          => User::class,
                        'items_id'          => 0,
                        'use_notification'  => 1,
                        'alternative_email' => 'extern2@localhost.local',
                    ],
                    [
                        'type'              => $actor_type_value,
                        'itemtype'          => User::class,
                        'items_id'          => 0,
                        'use_notification'  => 1,
                        'alternative_email' => 'extern3@localhost.local',
                    ],
                ],
                'update_actors_input'    => [
                    '_actors' => [
                        $actor_type => [
                            [
                                'itemtype'          => User::class,
                                'items_id'          => $postonly_user_id,
                                'use_notification'  => 1,
                                'alternative_email' => '',
                            ],
                            [
                                'itemtype'          => User::class,
                                'items_id'          => 0,
                                'use_notification'  => 0,
                                'alternative_email' => 'extern2@localhost.local',
                            ],
                            [
                                'itemtype'          => User::class,
                                'items_id'          => 0,
                                'use_notification'  => 1,
                                'alternative_email' => 'extern1@localhost.local',
                            ],
                        ],
                    ],
                ],
                'update_expected_actors' => [
                    [
                        'type'              => $actor_type_value,
                        'itemtype'          => User::class,
                        'items_id'          => 0,
                        'use_notification'  => 1,
                        'alternative_email' => 'extern1@localhost.local',
                    ],
                    [
                        'type'              => $actor_type_value,
                        'itemtype'          => User::class,
                        'items_id'          => $postonly_user_id,
                        'use_notification'  => 1,
                        'alternative_email' => '',
                    ],
                    [
                        'type'              => $actor_type_value,
                        'itemtype'          => User::class,
                        'items_id'          => 0,
                        'use_notification'  => 0,
                        'alternative_email' => 'extern2@localhost.local',
                    ],
                ],
            ];
        }
    }

    #[DataProvider('updateActorsProvider')]
    public function testUpdateTicketWithActors(
        array $add_actors_input,
        array $add_expected_actors,
        array $update_actors_input,
        array $update_expected_actors
    ): void {
        $this->login();

        $ticket = new Ticket();
        $ticket_id = $ticket->add(
            [
                'name'        => 'ticket title',
                'content'     => 'a description',
                'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            ] + $add_actors_input
        );
        $this->assertGreaterThan(0, $ticket_id);

        $this->checkActors($ticket, $add_expected_actors);

        $this->assertTrue($ticket->update(['id' => $ticket_id] + $update_actors_input));

        $this->checkActors($ticket, $update_expected_actors);
    }

    /**
     * Check that ticket actors are matching expected actors.
     *
     * @param Ticket $ticket
     * @param array $expected_actors
     *
     * @return void
     */
    private function checkActors(Ticket $ticket, array $expected_actors): void
    {
        foreach ([Ticket_User::class, Group_Ticket::class, Supplier_Ticket::class] as $link_class) {
            $link_obj = new $link_class();

            $expected_actors_for_itemtype = array_filter(
                $expected_actors,
                function (array $actor) use ($link_obj) {
                    return $actor['itemtype'] === getItemtypeForForeignKeyField($link_obj->getActorForeignKey());
                }
            );

            foreach ($expected_actors_for_itemtype as $actor) {
                $actor[$link_obj->getActorForeignKey()] = $actor['items_id'];
                unset($actor['itemtype'], $actor['items_id']);
                $this->assertTrue(
                    $link_obj->getFromDBByCrit(['tickets_id' => $ticket->getID()] + $actor),
                    sprintf('Actor not found: %s', json_encode($actor))
                );
            }
            $this->assertEquals(
                count($expected_actors_for_itemtype),
                $link_obj->countForItem($ticket)
            );
        }
    }

    public function testTasksFromTemplate()
    {
        // 1- create a task category
        $taskcat    = new \TaskCategory();
        $taskcat_id = $taskcat->add([
            'name' => 'my task cat',
        ]);
        $this->assertFalse($taskcat->isNewItem());

        // 2- create some task templates
        $tasktemplate = new \TaskTemplate();
        $ttA_id          = $tasktemplate->add([
            'name'              => 'my task template A',
            'content'           => '<p>my task template A</p>',
            'taskcategories_id' => $taskcat_id,
            'actiontime'        => 60,
            'is_private'        => true,
            'users_id_tech'     => 2,
            'groups_id_tech'    => 0,
            'state'             => \Planning::INFO,
        ]);
        $this->assertFalse($tasktemplate->isNewItem());
        $ttB_id          = $tasktemplate->add([
            'name'              => 'my task template B',
            'content'           => '<p>my task template B</p>',
            'taskcategories_id' => $taskcat_id,
            'actiontime'        => 120,
            'is_private'        => false,
            'users_id_tech'     => 2,
            'groups_id_tech'    => 0,
            'state'             => \Planning::TODO,
        ]);
        $this->assertFalse($tasktemplate->isNewItem());

        // 3 - create a ticket template with the task templates in predefined fields
        $itiltemplate    = new \TicketTemplate();
        $itiltemplate_id = $itiltemplate->add([
            'name' => 'my ticket template',
        ]);
        $this->assertFalse($itiltemplate->isNewItem());
        $ttp = new \TicketTemplatePredefinedField();
        $ttp->add([
            'tickettemplates_id' => $itiltemplate_id,
            'num'                => '175',
            'value'              => $ttA_id,
        ]);
        $this->assertFalse($ttp->isNewItem());
        $ttp->add([
            'tickettemplates_id' => $itiltemplate_id,
            'num'                => '176',
            'value'              => $ttB_id,
        ]);
        $this->assertFalse($ttp->isNewItem());

        // 4 - create a ticket category using the ticket template
        $itilcat    = new ITILCategory();
        $itilcat_id = $itilcat->add([
            'name'                        => 'my itil category',
            'ticketltemplates_id_incident' => $itiltemplate_id,
            'tickettemplates_id_demand'   => $itiltemplate_id,
            'is_incident'                 => true,
            'is_request'                  => true,
        ]);
        $this->assertFalse($itilcat->isNewItem());

        // 5 - create a ticket using the ticket category
        $ticket     = new Ticket();
        $tickets_id = $ticket->add([
            'name'                => 'test task template',
            'content'             => 'test task template',
            'itilcategories_id'   => $itilcat_id,
            '_tickettemplates_id' => $itiltemplate_id,
            '_tasktemplates_id'   => [$ttA_id, $ttB_id],
        ]);
        $this->assertFalse($ticket->isNewItem());

        // 6 - check creation of the tasks
        $tickettask = new \TicketTask();
        $found_tasks = $tickettask->find(['tickets_id' => $tickets_id], "id ASC");

        // 6.1 -> check first task
        $taskA = array_shift($found_tasks);
        $this->assertEquals('<p>my task template A</p>', $taskA['content']);
        $this->assertEquals($taskcat_id, $taskA['taskcategories_id']);
        $this->assertEquals(60, $taskA['actiontime']);
        $this->assertEquals(1, $taskA['is_private']);
        $this->assertEquals(2, $taskA['users_id_tech']);
        $this->assertEquals(0, $taskA['groups_id_tech']);
        $this->assertEquals(\Planning::INFO, $taskA['state']);

        // 6.2 -> check second task
        $taskB = array_shift($found_tasks);
        $this->assertSame('<p>my task template B</p>', $taskB['content']);
        $this->assertEquals($taskcat_id, $taskB['taskcategories_id']);
        $this->assertEquals(120, $taskB['actiontime']);
        $this->assertEquals(0, $taskB['is_private']);
        $this->assertEquals(2, $taskB['users_id_tech']);
        $this->assertEquals(0, $taskB['groups_id_tech']);
        $this->assertEquals(\Planning::TODO, $taskB['state']);
    }

    public function testAcls()
    {
        $ticket = new Ticket();
        //to fix an undefined index
        $_SESSION["glpiactiveprofile"]["interface"] = '';
        $this->assertFalse((bool) $ticket->canAdminActors());
        $this->assertFalse((bool) $ticket->canAssign());
        $this->assertFalse((bool) $ticket->canAssignToMe());
        $this->assertFalse((bool) $ticket->canUpdate());
        $this->assertFalse((bool) $ticket->canView());
        $this->assertFalse((bool) $ticket->canViewItem());
        $this->assertFalse((bool) $ticket->canSolve());
        $this->assertFalse((bool) $ticket->canApprove());
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'content', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'name', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'priority', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'type', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'location', 'qwerty'));
        $this->assertFalse((bool) $ticket->canCreateItem());
        $this->assertFalse((bool) $ticket->canUpdateItem());
        $this->assertFalse((bool) $ticket->canRequesterUpdateItem());
        $this->assertFalse((bool) $ticket->canDelete());
        $this->assertFalse((bool) $ticket->canDeleteItem());
        $this->assertFalse((bool) $ticket->canAddItem('Document'));
        $this->assertFalse((bool) $ticket->canAddItem('Ticket_Cost'));
        $this->assertFalse((bool) $ticket->canAddFollowups());
        $this->assertFalse((bool) $ticket->canUserAddFollowups(Session::getLoginUserID()));

        $this->login();
        $this->setEntity('Root entity', true);
        $ticket = new Ticket();
        $this->assertTrue((bool) $ticket->canAdminActors()); //=> get 2
        $this->assertTrue((bool) $ticket->canAssign()); //=> get 8192
        $this->assertTrue((bool) $ticket->canAssignToMe());
        $this->assertTrue((bool) $ticket->canUpdate());
        $this->assertTrue((bool) $ticket->canView());
        $this->assertTrue((bool) $ticket->canViewItem());
        $this->assertTrue((bool) $ticket->canSolve());
        $this->assertFalse((bool) $ticket->canApprove());
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'content', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'name', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'priority', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'type', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'location', 'qwerty'));
        $this->assertTrue((bool) $ticket->canCreateItem());
        $this->assertTrue((bool) $ticket->canUpdateItem());
        $this->assertFalse((bool) $ticket->canRequesterUpdateItem());
        $this->assertTrue((bool) $ticket->canDelete());
        $this->assertTrue((bool) $ticket->canDeleteItem());
        $this->assertTrue((bool) $ticket->canAddItem('Document'));
        $this->assertTrue((bool) $ticket->canAddItem('Ticket_Cost'));
        $this->assertTrue((bool) $ticket->canAddFollowups());
        $this->assertTrue((bool) $ticket->canUserAddFollowups(Session::getLoginUserID()));

        $ticket = getItemByTypeName('Ticket', '_ticket01');
        $this->assertTrue((bool) $ticket->canAdminActors()); //=> get 2
        $this->assertTrue((bool) $ticket->canAssign()); //=> get 8192
        $this->assertTrue((bool) $ticket->canAssignToMe());
        $this->assertTrue((bool) $ticket->canUpdate());
        $this->assertTrue((bool) $ticket->canView());
        $this->assertTrue((bool) $ticket->canViewItem());
        $this->assertTrue((bool) $ticket->canSolve());
        $this->assertTrue((bool) $ticket->canApprove());
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'content', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'name', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'priority', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'type', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'location', 'qwerty'));
        $this->assertTrue((bool) $ticket->canCreateItem());
        $this->assertTrue((bool) $ticket->canUpdateItem());
        $this->assertFalse((bool) $ticket->canRequesterUpdateItem());
        $this->assertTrue((bool) $ticket->canDelete());
        $this->assertTrue((bool) $ticket->canDeleteItem());
        $this->assertTrue((bool) $ticket->canAddItem('Document'));
        $this->assertTrue((bool) $ticket->canAddItem('Ticket_Cost'));
        $this->assertTrue((bool) $ticket->canAddFollowups());
        $this->assertTrue((bool) $ticket->canUserAddFollowups(Session::getLoginUserID()));
    }

    public function testPostOnlyAcls()
    {
        $auth = new \Auth();
        $this->assertTrue((bool) $auth->login('post-only', 'postonly', true));

        $ticket = new Ticket();
        $this->assertFalse((bool) $ticket->canAdminActors());
        $this->assertFalse((bool) $ticket->canAssign());
        $this->assertFalse((bool) $ticket->canAssignToMe());
        $this->assertTrue((bool) $ticket->canUpdate());
        $this->assertTrue((bool) $ticket->canView());
        $this->assertFalse((bool) $ticket->canViewItem());
        $this->assertFalse((bool) $ticket->canSolve());
        $this->assertFalse((bool) $ticket->canApprove());
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'content', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'name', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'priority', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'type', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'location', 'qwerty'));
        $this->assertTrue((bool) $ticket->canCreateItem());
        $this->assertFalse((bool) $ticket->canUpdateItem());
        $this->assertFalse((bool) $ticket->canRequesterUpdateItem());
        $this->assertTrue((bool) $ticket->canDelete());
        $this->assertFalse((bool) $ticket->canDeleteItem());
        $this->assertFalse((bool) $ticket->canAddItem('Document'));
        $this->assertFalse((bool) $ticket->canAddItem('Ticket_Cost'));
        $this->assertFalse((bool) $ticket->canAddFollowups());
        $this->assertFalse((bool) $ticket->canUserAddFollowups(Session::getLoginUserID()));

        $this->assertGreaterThan(
            0,
            (int) $ticket->add([
                'name'    => '',
                'content' => 'A ticket to check ACLS',
                '_users_id_requester' => getItemByTypeName('User', 'post-only', true),
            ])
        );

        //reload ticket from DB
        $this->assertTrue((bool) $ticket->getFromDB($ticket->getID()));
        $this->assertFalse((bool) $ticket->canAdminActors());
        $this->assertFalse((bool) $ticket->canAssign());
        $this->assertFalse((bool) $ticket->canAssignToMe());
        $this->assertTrue((bool) $ticket->canUpdate());
        $this->assertTrue((bool) $ticket->canView());
        $this->assertTrue((bool) $ticket->canViewItem());
        $this->assertFalse((bool) $ticket->canSolve());
        $this->assertTrue((bool) $ticket->canApprove());
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'content', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'name', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'priority', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'type', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'location', 'qwerty'));
        $this->assertTrue((bool) $ticket->canCreateItem());
        $this->assertTrue((bool) $ticket->canUpdateItem());
        $this->assertTrue((bool) $ticket->canRequesterUpdateItem());
        $this->assertTrue((bool) $ticket->canDelete());
        $this->assertTrue((bool) $ticket->canDeleteItem());
        $this->assertTrue((bool) $ticket->canAddItem('Document'));
        $this->assertTrue((bool) $ticket->canAddItem('Ticket_Cost'));
        $this->assertTrue((bool) $ticket->canAddFollowups());
        $this->assertTrue((bool) $ticket->canUserAddFollowups(Session::getLoginUserID()));

        $uid = getItemByTypeName('User', TU_USER, true);
        //add a followup to the ticket
        $fup = new ITILFollowup();
        $this->assertGreaterThan(
            0,
            (int) $fup->add([
                'itemtype'  => 'Ticket',
                'items_id'   => $ticket->getID(),
                'users_id'     => $uid,
                'content'      => 'A simple followup',
            ])
        );

        $this->assertTrue((bool) $ticket->getFromDB($ticket->getID()));
        $this->assertFalse((bool) $ticket->canAdminActors());
        $this->assertFalse((bool) $ticket->canAssign());
        $this->assertFalse((bool) $ticket->canAssignToMe());
        $this->assertTrue((bool) $ticket->canUpdate());
        $this->assertTrue((bool) $ticket->canView());
        $this->assertTrue((bool) $ticket->canViewItem());
        $this->assertFalse((bool) $ticket->canSolve());
        $this->assertTrue((bool) $ticket->canApprove());
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'content', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'name', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'priority', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'type', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'location', 'qwerty'));
        $this->assertTrue((bool) $ticket->canCreateItem());
        $this->assertFalse((bool) $ticket->canUpdateItem());
        $this->assertFalse((bool) $ticket->canRequesterUpdateItem());
        $this->assertTrue((bool) $ticket->canDelete());
        $this->assertFalse((bool) $ticket->canDeleteItem());
        $this->assertTrue((bool) $ticket->canAddItem('Document'));
        $this->assertFalse((bool) $ticket->canAddItem('Ticket_Cost'));
        $this->assertTrue((bool) $ticket->canAddFollowups());
        $this->assertTrue((bool) $ticket->canUserAddFollowups(Session::getLoginUserID()));
    }

    public function testTechAcls()
    {
        $auth = new \Auth();
        $this->assertTrue((bool) $auth->login('tech', 'tech', true));

        $ticket = new Ticket();
        $this->assertTrue((bool) $ticket->canAdminActors());
        $this->assertFalse((bool) $ticket->canAssign());
        $this->assertTrue((bool) $ticket->canAssignToMe());
        $this->assertTrue((bool) $ticket->canUpdate());
        $this->assertTrue((bool) $ticket->canView());
        $this->assertTrue((bool) $ticket->canViewItem());
        $this->assertTrue((bool) $ticket->canSolve());
        $this->assertFalse((bool) $ticket->canApprove());
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'content', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'name', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'priority', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'type', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'location', 'qwerty'));
        $this->assertTrue((bool) $ticket->canCreateItem());
        $this->assertTrue((bool) $ticket->canUpdateItem());
        $this->assertFalse((bool) $ticket->canRequesterUpdateItem());
        $this->assertFalse((bool) $ticket->canDelete());
        $this->assertFalse((bool) $ticket->canDeleteItem());
        $this->assertTrue((bool) $ticket->canAddItem('Document'));
        $this->assertTrue((bool) $ticket->canAddItem('Ticket_Cost'));
        $this->assertTrue((bool) $ticket->canAddFollowups());
        $this->assertTrue((bool) $ticket->canUserAddFollowups(Session::getLoginUserID()));

        $this->assertGreaterThan(
            0,
            (int) $ticket->add([
                'name'    => '',
                'content' => 'A ticket to check ACLS',
                '_users_id_assign' => getItemByTypeName("User", 'tech', true),
            ])
        );

        //reload ticket from DB
        $this->assertTrue((bool) $ticket->getFromDB($ticket->getID()));
        $this->assertTrue((bool) $ticket->canAdminActors());
        $this->assertFalse((bool) $ticket->canAssign());
        $this->assertFalse((bool) $ticket->canAssignToMe());
        $this->assertTrue((bool) $ticket->canUpdate());
        $this->assertTrue((bool) $ticket->canView());
        $this->assertTrue((bool) $ticket->canViewItem());
        $this->assertTrue((bool) $ticket->canSolve());
        $this->assertTrue((bool) $ticket->canApprove());
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'content', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'name', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'priority', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'type', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'location', 'qwerty'));
        $this->assertTrue((bool) $ticket->canCreateItem());
        $this->assertTrue((bool) $ticket->canUpdateItem());
        $this->assertTrue((bool) $ticket->canRequesterUpdateItem());
        $this->assertFalse((bool) $ticket->canDelete());
        $this->assertFalse((bool) $ticket->canDeleteItem());
        $this->assertTrue((bool) $ticket->canAddItem('Document'));
        $this->assertTrue((bool) $ticket->canAddItem('Ticket_Cost'));
        $this->assertTrue((bool) $ticket->canAddFollowups());
        $this->assertTrue((bool) $ticket->canUserAddFollowups(Session::getLoginUserID()));

        $uid = getItemByTypeName('User', TU_USER, true);
        //add a followup to the ticket
        $fup = new ITILFollowup();
        $this->assertGreaterThan(
            0,
            (int) $fup->add([
                'itemtype'  => 'Ticket',
                'items_id'   => $ticket->getID(),
                'users_id'     => $uid,
                'content'      => 'A simple followup',
            ])
        );

        $this->assertTrue((bool) $ticket->getFromDB($ticket->getID()));
        $this->assertTrue((bool) $ticket->canAdminActors());
        $this->assertFalse((bool) $ticket->canAssign());
        $this->assertFalse((bool) $ticket->canAssignToMe());
        $this->assertTrue((bool) $ticket->canUpdate());
        $this->assertTrue((bool) $ticket->canView());
        $this->assertTrue((bool) $ticket->canViewItem());
        $this->assertTrue((bool) $ticket->canSolve());
        $this->assertTrue((bool) $ticket->canApprove());
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'content', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'name', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'priority', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'type', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'location', 'qwerty'));
        $this->assertTrue((bool) $ticket->canCreateItem());
        $this->assertTrue((bool) $ticket->canUpdateItem());
        $this->assertFalse((bool) $ticket->canRequesterUpdateItem());
        $this->assertFalse((bool) $ticket->canDelete());
        $this->assertFalse((bool) $ticket->canDeleteItem());
        $this->assertTrue((bool) $ticket->canAddItem('Document'));
        $this->assertTrue((bool) $ticket->canAddItem('Ticket_Cost'));
        $this->assertTrue((bool) $ticket->canAddFollowups());
        $this->assertTrue((bool) $ticket->canUserAddFollowups(Session::getLoginUserID()));

        //drop update ticket right from tech profile
        global $DB;
        $DB->update(
            'glpi_profilerights',
            ['rights' => 168965],
            [
                'profiles_id'  => 6,
                'name'         => 'ticket',
            ]
        );
        //ACLs have changed: login again.
        $this->assertTrue((bool) $auth->login('tech', 'tech', true));

        //reset rights. Done here so ACLs are reset even if tests fails.
        $DB->update(
            'glpi_profilerights',
            ['rights' => 168967],
            [
                'profiles_id'  => 6,
                'name'         => 'ticket',
            ]
        );

        $this->assertTrue((bool) $ticket->getFromDB($ticket->getID()));
        $this->assertFalse((bool) $ticket->canAdminActors());
        $this->assertFalse((bool) $ticket->canAssign());
        $this->assertFalse((bool) $ticket->canAssignToMe());
        $this->assertTrue((bool) $ticket->canUpdate());
        $this->assertTrue((bool) $ticket->canView());
        $this->assertTrue((bool) $ticket->canViewItem());
        $this->assertTrue((bool) $ticket->canSolve());
        $this->assertTrue((bool) $ticket->canApprove());
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'content', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'name', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'priority', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'type', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'location', 'qwerty'));
        $this->assertTrue((bool) $ticket->canCreateItem());
        $this->assertTrue((bool) $ticket->canUpdateItem());
        $this->assertFalse((bool) $ticket->canRequesterUpdateItem());
        $this->assertFalse((bool) $ticket->canDelete());
        $this->assertFalse((bool) $ticket->canDeleteItem());
        $this->assertTrue((bool) $ticket->canAddItem('Document'));
        $this->assertFalse((bool) $ticket->canAddItem('Ticket_Cost'));
        $this->assertTrue((bool) $ticket->canAddFollowups());
        $this->assertTrue((bool) $ticket->canUserAddFollowups(Session::getLoginUserID()));

        $this->assertGreaterThan(
            0,
            (int) $ticket->add([
                'name'    => '',
                'content' => 'Another ticket to check ACLS',
                '_users_id_assign' => getItemByTypeName("User", 'tech', true),
            ])
        );
        $this->assertTrue((bool) $ticket->getFromDB($ticket->getID()));
        $this->assertFalse((bool) $ticket->canAdminActors());
        $this->assertFalse((bool) $ticket->canAssign());
        $this->assertFalse((bool) $ticket->canAssignToMe());
        $this->assertTrue((bool) $ticket->canUpdate());
        $this->assertTrue((bool) $ticket->canView());
        $this->assertTrue((bool) $ticket->canViewItem());
        $this->assertTrue((bool) $ticket->canSolve());
        $this->assertTrue((bool) $ticket->canApprove());
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'content', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'name', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'priority', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'type', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'location', 'qwerty'));
        $this->assertTrue((bool) $ticket->canCreateItem());
        $this->assertTrue((bool) $ticket->canUpdateItem());
        $this->assertTrue((bool) $ticket->canRequesterUpdateItem());
        $this->assertFalse((bool) $ticket->canDelete());
        $this->assertFalse((bool) $ticket->canDeleteItem());
        $this->assertTrue((bool) $ticket->canAddItem('Document'));
        $this->assertTrue((bool) $ticket->canAddItem('Ticket_Cost'));
        $this->assertTrue((bool) $ticket->canAddFollowups());
        $this->assertTrue((bool) $ticket->canUserAddFollowups(Session::getLoginUserID()));
    }

    public function testNotOwnerAcls()
    {
        $this->login();

        $ticket = new Ticket();
        $this->assertGreaterThan(
            0,
            (int) $ticket->add([
                'name'    => '',
                'content' => 'A ticket to check ACLS',
                '_users_id_assign' => getItemByTypeName("User", TU_USER, true),
            ])
        );

        $auth = new \Auth();
        $this->assertTrue((bool) $auth->login('tech', 'tech', true));

        //reload ticket from DB
        $this->assertTrue((bool) $ticket->getFromDB($ticket->getID()));
        $this->assertTrue((bool) $ticket->canAdminActors());
        $this->assertFalse((bool) $ticket->canAssign());
        $this->assertFalse((bool) $ticket->canAssignToMe());
        $this->assertTrue((bool) $ticket->canUpdate());
        $this->assertTrue((bool) $ticket->canView());
        $this->assertTrue((bool) $ticket->canViewItem());
        $this->assertTrue((bool) $ticket->canSolve());
        $this->assertFalse((bool) $ticket->canApprove());
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'content', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'name', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'priority', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'type', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'location', 'qwerty'));
        $this->assertTrue((bool) $ticket->canCreateItem());
        $this->assertTrue((bool) $ticket->canUpdateItem());
        $this->assertFalse((bool) $ticket->canRequesterUpdateItem());
        $this->assertFalse((bool) $ticket->canDelete());
        $this->assertFalse((bool) $ticket->canDeleteItem());
        $this->assertTrue((bool) $ticket->canAddItem('Document'));
        $this->assertTrue((bool) $ticket->canAddItem('Ticket_Cost'));
        $this->assertTrue((bool) $ticket->canAddFollowups());
        $this->assertTrue((bool) $ticket->canUserAddFollowups(Session::getLoginUserID()));

        //drop update ticket right from tech profile
        global $DB;
        $this->assertTrue(
            $DB->update(
                'glpi_profilerights',
                ['rights' => 168965],
                [
                    'profiles_id'  => 6,
                    'name'         => 'ticket',
                ]
            )
        );
        //ACLs have changed: login again.
        $this->assertTrue((bool) $auth->login('tech', 'tech', true));

        //reset rights. Done here so ACLs are reset even if tests fails.
        $this->assertTrue(
            $DB->update(
                'glpi_profilerights',
                ['rights' => 168967],
                [
                    'profiles_id'  => 6,
                    'name'         => 'ticket',
                ]
            )
        );

        $this->assertTrue((bool) $ticket->getFromDB($ticket->getID()));
        $this->assertFalse((bool) $ticket->canAdminActors());
        $this->assertFalse((bool) $ticket->canAssign());
        $this->assertFalse((bool) $ticket->canAssignToMe());
        $this->assertTrue((bool) $ticket->canUpdate());
        $this->assertTrue((bool) $ticket->canView());
        $this->assertTrue((bool) $ticket->canViewItem());
        $this->assertFalse((bool) $ticket->canSolve());
        $this->assertFalse((bool) $ticket->canApprove());
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'content', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'name', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'priority', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'type', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'location', 'qwerty'));
        $this->assertTrue((bool) $ticket->canCreateItem());
        $this->assertFalse((bool) $ticket->canUpdateItem());
        $this->assertFalse((bool) $ticket->canRequesterUpdateItem());
        $this->assertFalse((bool) $ticket->canDelete());
        $this->assertFalse((bool) $ticket->canDeleteItem());
        $this->assertTrue((bool) $ticket->canAddItem('Document'));
        $this->assertFalse((bool) $ticket->canAddItem('Ticket_Cost'));
        $this->assertTrue((bool) $ticket->canAddFollowups());
        $this->assertTrue((bool) $ticket->canUserAddFollowups(Session::getLoginUserID()));

        // post only tests
        $this->assertTrue((bool) $auth->login('post-only', 'postonly', true));
        $this->assertTrue((bool) $ticket->getFromDB($ticket->getID()));
        $this->assertFalse((bool) $ticket->canAdminActors());
        $this->assertFalse((bool) $ticket->canAssign());
        $this->assertFalse((bool) $ticket->canAssignToMe());
        $this->assertTrue((bool) $ticket->canUpdate());
        $this->assertTrue((bool) $ticket->canView());
        $this->assertFalse((bool) $ticket->canViewItem());
        $this->assertFalse((bool) $ticket->canSolve());
        $this->assertFalse((bool) $ticket->canApprove());
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'content', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'name', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'priority', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'type', 'qwerty'));
        $this->assertTrue((bool) $ticket->canMassiveAction('update', 'location', 'qwerty'));
        $this->assertTrue((bool) $ticket->canCreateItem());
        $this->assertFalse((bool) $ticket->canUpdateItem());
        $this->assertFalse((bool) $ticket->canRequesterUpdateItem());
        $this->assertTrue((bool) $ticket->canDelete());
        $this->assertFalse((bool) $ticket->canDeleteItem());
        $this->assertFalse((bool) $ticket->canAddItem('Document'));
        $this->assertFalse((bool) $ticket->canAddItem('Ticket_Cost'));
        $this->assertFalse((bool) $ticket->canAddFollowups());
        $this->assertFalse((bool) $ticket->canUserAddFollowups(Session::getLoginUserID()));
    }

    /**
     * Checks showForm() output
     *
     * @param Ticket $ticket   Ticket instance
     * @param boolean $name     Name is editable
     * @param boolean $textarea Content is editable
     * @param boolean $priority Priority can be changed
     * @param boolean $save     Save button is present
     * @param boolean $assign   Can assign
     *
     * @return void
     */
    private function checkFormOutput(
        Ticket $ticket,
        $name = true,
        $textarea = true,
        $priority = true,
        $save = true,
        $assign = true,
        $openDate = true,
        $timeOwnResolve = true,
        $type = true,
        $status = true,
        $urgency = true,
        $impact = true,
        $category = true,
        $requestSource = true,
        $location = true,
        $itil_form = true,
        $cancel_ticket = false,
    ) {
        ob_start();
        $ticket->showForm($ticket->getID());
        $output = ob_get_contents();
        ob_end_clean();
        $crawler = new Crawler($output);

        $backtrace = debug_backtrace(0, 1);
        $caller = "File: {$backtrace[0]['file']} Function: {$backtrace[0]['function']}:{$backtrace[0]['line']}";
        // Opening date, editable
        $matches = iterator_to_array($crawler->filter("#itil-data input[name=date]:not([disabled])"));
        $this->assertCount(($openDate === true ? 1 : 0), $matches, "RW Opening date $caller");

        // Time to own, editable
        $matches = iterator_to_array($crawler->filter("#itil-data input[name=time_to_own]:not([disabled])"));
        $this->assertCount(($timeOwnResolve === true ? 1 : 0), $matches, "Time to own editable $caller");

        // Internal time to own, editable
        $matches = iterator_to_array($crawler->filter("#itil-data input[name=internal_time_to_own]:not([disabled])"));
        $this->assertCount(($timeOwnResolve === true ? 1 : 0), $matches, "Internal time to own editable $caller");

        // Time to resolve, editable
        $matches = iterator_to_array($crawler->filter("#itil-data input[name=time_to_resolve]:not([disabled])"));
        $this->assertCount(($timeOwnResolve === true ? 1 : 0), $matches, "Time to resolve $caller");

        // Internal time to resolve, editable
        $matches = iterator_to_array($crawler->filter("#itil-data input[name=internal_time_to_resolve]:not([disabled])"));
        $this->assertCount(($timeOwnResolve === true ? 1 : 0), $matches, "Internal time to resolve $caller");

        //Type
        $matches = iterator_to_array($crawler->filter("#itil-data select[name=type]:not([disabled])"));
        $this->assertCount(($type === true ? 1 : 0), $matches, "Type $caller");

        //Status
        $matches = iterator_to_array($crawler->filter("#itil-data select[name=status]:not([disabled])"));
        $this->assertCount(($status === true ? 1 : 0), $matches, "Status $caller");

        //Urgency
        $matches = iterator_to_array($crawler->filter("#itil-data select[name=urgency]:not([disabled])"));
        $this->assertCount(($urgency === true ? 1 : 0), $matches, "Urgency $caller");

        //Impact
        $matches = iterator_to_array($crawler->filter("#itil-data select[name=impact]:not([disabled])"));
        $this->assertCount(($impact === true ? 1 : 0), $matches, "Impact $caller");

        //Category
        $matches = iterator_to_array($crawler->filter("#itil-data select[name=itilcategories_id]:not([disabled])"));
        $this->assertCount(($category === true ? 1 : 0), $matches, "Category $caller");

        //Request source file_put_contents('/tmp/out.html', $output)
        $matches = iterator_to_array($crawler->filter("#itil-data select[name=requesttypes_id]:not([disabled])"));
        $this->assertCount(($requestSource === true ? 1 : 0), $matches, "Request source $caller");

        //Location
        $matches = iterator_to_array($crawler->filter("#itil-data select[name=locations_id]:not([disabled])"));
        $this->assertCount(($location === true ? 1 : 0), $matches, "Location $caller");

        //Priority, editable
        $matches = iterator_to_array($crawler->filter("#itil-data select[name=priority]:not([disabled])"));
        $this->assertCount(($priority === true ? 1 : 0), $matches, "RW priority $caller");

        //Save button
        $matches = iterator_to_array($crawler->filter("#itil-footer button[type=submit][name=update]:not([disabled])"));
        $this->assertCount(($save === true ? 1 : 0), $matches, ($save === true ? 'Save button missing' : 'Save button present') . ' ' . $caller);

        // Check that the itil form exist
        $matches = iterator_to_array($crawler->filter("#itil-form"));
        $this->assertCount(
            ($itil_form === true ? 1 : 0),
            $matches,
            ($itil_form === true ? 'ITIL form' : 'ITIL form present') . ' ' . $caller
        );

        // Cancel ticket button
        $matches = iterator_to_array($crawler->filter("button:contains('Cancel ticket')"));
        $this->assertCount(
            ($cancel_ticket === true ? 1 : 0),
            $matches,
            'Cancel ticket ' . ($cancel_ticket === true ? 'missing' : 'present')
        );


        //Assign to
        /*preg_match(
          '|.*<select name=\'_itil_assign\[_type\]\'[^>]*>.*|',
          $output,
          $matches
        );
        $this->assertCount(($assign === true ? 1 : 0), $matches);*/
    }

    public function testForm()
    {
        $this->login();
        $this->setEntity('Root entity', true);
        $ticket = getItemByTypeName('Ticket', '_ticket01');

        $this->checkFormOutput($ticket);
    }

    public function testFormPostOnly()
    {
        $auth = new \Auth();
        $this->assertTrue((bool) $auth->login('post-only', 'postonly', true));

        //create a new ticket
        $ticket = new Ticket();
        $this->assertGreaterThan(
            0,
            (int) $ticket->add([
                'name'    => '',
                'content' => 'A ticket to check displayed postonly form',
            ])
        );
        $this->assertTrue($ticket->getFromDB($ticket->getId()));

        $this->checkFormOutput(
            $ticket,
            name: false,
            textarea: true,
            priority: false,
            save: false,
            assign: false,
            openDate: false,
            timeOwnResolve: false,
            type: false,
            status: false,
            urgency: false,
            impact: false,
            category: false,
            requestSource: false,
            location: false,
            itil_form: false,
            cancel_ticket: true,
        );

        $uid = getItemByTypeName('User', TU_USER, true);
        //add a followup to the ticket
        $fup = new ITILFollowup();
        $this->assertGreaterThan(
            0,
            (int) $fup->add([
                'itemtype'  => 'Ticket',
                'items_id'   => $ticket->getID(),
                'users_id'     => $uid,
                'content'      => 'A simple followup',
            ])
        );

        $this->checkFormOutput(
            $ticket,
            $name = false,
            $textarea = false,
            $priority = false,
            $save = false,
            $assign = false,
            $openDate = false,
            $timeOwnResolve = false,
            $type = false,
            $status = false,
            $urgency = false,
            $impact = false,
            $category = false,
            $requestSource = false,
            $location = false,
            itil_form: false,
            cancel_ticket: false, // Can no longer cancel once a followup is added
        );

        // Display extra fields
        $this->login('glpi', 'glpi'); // Need to be admin to update entities
        $this->updateItem(Entity::class, 0, [
            'show_tickets_properties_on_helpdesk' => 1,
        ]);
        $this->login('post-only', 'postonly');
        $this->checkFormOutput(
            $ticket,
            name: false,
            textarea: false,
            priority: false,
            save: false,
            assign: false,
            openDate: false,
            timeOwnResolve: false,
            type: false,
            status: false,
            urgency: false,
            impact: false,
            category: false,
            requestSource: false,
            location: false,
            itil_form: true,
            cancel_ticket: false, // Can no longer cancel once a followup is added
        );
    }

    public function testFormTech()
    {
        //create a new ticket with tu user
        $auth = new \Auth();
        $this->login();
        $ticket = new Ticket();
        $this->assertGreaterThan(
            0,
            (int) $ticket->add([
                'name'                => '',
                'content'             => 'A ticket to check displayed tech form',
                '_users_id_requester' => '3', // post-only
                '_users_id_assign'    => '4', // tech
            ])
        );
        $this->assertTrue($ticket->getFromDB($ticket->getId()));

        //check output with default ACLs
        $this->changeTechRights(['ticket' => null]);
        $this->checkFormOutput(
            $ticket,
            $name = false,
            $textarea = true,
            $priority = false,
            $save = true,
            $assign = false,
            $openDate = true,
            $timeOwnResolve = true,
            $type = true,
            $status = true,
            $urgency = true,
            $impact = true,
            $category = true,
            $requestSource = true,
            $location = true
        );

        //drop UPDATE ticket right from tech profile (still with OWN)
        $this->changeTechRights(['ticket' => 168965]);
        $this->checkFormOutput(
            $ticket,
            $name = false,
            $textarea = true,
            $priority = false,
            $save = true,
            $assign = false,
            $openDate = true,
            $timeOwnResolve = true,
            $type = true,
            $status = true,
            $urgency = true,
            $impact = true,
            $category = true,
            $requestSource = true,
            $location = true
        );

        //drop UPDATE ticket right from tech profile (without OWN)
        $this->changeTechRights(['ticket' => 136197]);
        $this->checkFormOutput(
            $ticket,
            $name = false,
            $textarea = false,
            $priority = false,
            $save = false,
            $assign = false,
            $openDate = false,
            $timeOwnResolve = false,
            $type = false,
            $status = false,
            $urgency = false,
            $impact = false,
            $category = false,
            $requestSource = false,
            $location = false
        );

        // only assign and priority right for tech (without UPDATE and OWN rights)
        $this->changeTechRights(['ticket' => 94209]);
        $this->checkFormOutput(
            $ticket,
            $name = false,
            $textarea = false,
            $priority = true,
            $save = true,
            $assign = true,
            $openDate = false,
            $timeOwnResolve = false,
            $type = false,
            $status = false,
            $urgency = false,
            $impact = false,
            $category = false,
            $requestSource = false,
            $location = false
        );

        $this->changeTechRights([
            'ticket'    => 168967,
            'slm'       => 256,
        ]);
        $this->checkFormOutput(
            $ticket,
            $name = true,
            $textarea = true,
            $priority = false,
            $save = true,
            $assign = true,
            $openDate = true,
            $timeOwnResolve = true,
            $type = true,
            $status = true,
            $urgency = true,
            $impact = true,
            $category = true,
            $requestSource = true,
            $location = true
        );

        $this->changeTechRights([
            'ticket'    => 168967,
            'slm'       => 255,
        ]);
        $this->checkFormOutput(
            $ticket,
            $name = true,
            $textarea = true,
            $priority = false,
            $save = true,
            $assign = true,
            $openDate = true,
            $timeOwnResolve = false,
            $type = true,
            $status = true,
            $urgency = true,
            $impact = true,
            $category = true,
            $requestSource = true,
            $location = true
        );

        // no update rights, only display for tech
        $this->changeTechRights(['ticket' => 3077]);
        $this->checkFormOutput(
            $ticket,
            $name = false,
            $textarea = false,
            $priority = false,
            $save = false,
            $assign = false,
            $openDate = false,
            $timeOwnResolve = false,
            $type = false,
            $status = false,
            $urgency = false,
            $impact = false,
            $category = false,
            $requestSource = false,
            $location = false
        );

        $uid = getItemByTypeName('User', TU_USER, true);
        //add a followup to the ticket
        $fup = new ITILFollowup();
        $this->assertGreaterThan(
            0,
            (int) $fup->add([
                'itemtype'  => 'Ticket',
                'items_id'   => $ticket->getID(),
                'users_id'     => $uid,
                'content'      => 'A simple followup',
            ])
        );

        //check output with changed ACLs when a followup has been added
        $this->checkFormOutput(
            $ticket,
            $name = false,
            $textarea = false,
            $priority = false,
            $save = false,
            $assign = false,
            $openDate = false,
            $timeOwnResolve = false,
            $type = false,
            $status = false,
            $urgency = false,
            $impact = false,
            $category = false,
            $requestSource = false,
            $location = false
        );
    }

    /**
     * Update tech user rights (and relogin to apply these rights)
     *
     * $rights parameter is an array with the following format:
     * key : object type (e.g. ticket)
     * value : right (e.g. \Ticket::READNEWTICKET)
     * @param array<string, int> $rights
     * @throws \Exception
     */
    public function changeTechRights(array $rights): void
    {
        global $DB;

        $default_rights = [
            'ticket'    => 168967,
            'slm'       => 255,
        ];

        foreach ($rights as $name => $value) {
            if (is_array($value) && isset($value['default'])) {
                $default_rights[$name] = $value;
            }
            $default_value = $default_rights[$name] ?? null;
            if ($default_value === null) {
                throw new \Exception("Unknown right $name with no default value specified");
            }
            if ($value === null) {
                $value = $default_value;
            }

            // set new rights
            $this->assertTrue(
                $DB->update(
                    'glpi_profilerights',
                    ['rights' => $value],
                    [
                        'profiles_id'  => 6,
                        'name'         => $name,
                    ]
                )
            );

            //ACLs have changed: login again.
            $auth = new \Auth();
            $this->assertTrue((bool) $auth->Login('tech', 'tech', true));

            if ($rights != $default_value) {
                //reset rights. Done here so ACLs are reset even if tests fails.
                $this->assertTrue(
                    $DB->update(
                        'glpi_profilerights',
                        ['rights' => $default_value],
                        [
                            'profiles_id'  => 6,
                            'name'         => $name,
                        ]
                    )
                );
            }
        }
    }

    /**
     * @param $rights
     * @return void
     * @deprecated 11.0.0 - Use changeTechRights() instead
     */
    public function changeTechRight($rights = 168967)
    {
        $this->changeTechRights(['ticket' => $rights]);
    }

    public function testPriorityAcl()
    {
        $this->login();

        $ticket = new Ticket();
        $this->assertGreaterThan(
            0,
            (int) $ticket->add([
                'name'    => '',
                'content' => 'A ticket to check priority ACLS',
            ])
        );

        $auth = new \Auth();
        $this->assertTrue((bool) $auth->login('tech', 'tech', true));
        $this->assertTrue((bool) $ticket->getFromDB($ticket->getID()));

        $this->assertFalse((bool) Session::haveRight(Ticket::$rightname, Ticket::CHANGEPRIORITY));
        //check output with default ACLs
        $this->checkFormOutput(
            $ticket,
            $name = false,
            $textarea = true,
            $priority = false,
            $save = true,
            $assign = false,
            $openDate = true,
            $timeOwnResolve = true,
            $type = true,
            $status = true,
            $urgency = true,
            $impact = true,
            $category = true,
            $requestSource = true,
            $location = true
        );

        //Add priority right from tech profile
        global $DB;
        $this->assertTrue(
            $DB->update(
                'glpi_profilerights',
                ['rights' => 234503],
                [
                    'profiles_id'  => 6,
                    'name'         => 'ticket',
                ]
            )
        );

        //ACLs have changed: login again.
        $this->assertTrue((bool) $auth->login('tech', 'tech', true));

        //reset rights. Done here so ACLs are reset even if tests fails.
        $this->assertTrue(
            $DB->update(
                'glpi_profilerights',
                ['rights' => 168967],
                [
                    'profiles_id'  => 6,
                    'name'         => 'ticket',
                ]
            )
        );

        $this->assertTrue((bool) Session::haveRight(Ticket::$rightname, Ticket::CHANGEPRIORITY));
        //check output with changed ACLs
        $this->checkFormOutput(
            $ticket,
            $name = false,
            $textarea = true,
            $priority = true,
            $save = true,
            $assign = false,
            $openDate = true,
            $timeOwnResolve = true,
            $type = true,
            $status = true,
            $urgency = true,
            $impact = true,
            $category = true,
            $requestSource = true,
            $location = true
        );
    }

    public function testAssignAcl()
    {
        $this->login();

        $ticket = new Ticket();
        $this->assertGreaterThan(
            0,
            (int) $ticket->add([
                'name'    => '',
                'content' => 'A ticket to check assign ACLS',
                '_users_id_assign' => getItemByTypeName("User", TU_USER, true),
            ])
        );

        $auth = new \Auth();
        $this->assertTrue((bool) $auth->login('tech', 'tech', true));
        $this->assertTrue((bool) $ticket->getFromDB($ticket->getID()));

        $this->assertFalse((bool) $ticket->canAssign());
        $this->assertFalse((bool) $ticket->canAssignToMe());
        $this->changeTechRights(['ticket' => 168967]);
        //check output with default ACLs
        $this->checkFormOutput(
            $ticket,
            $name = false,
            $textarea = true,
            $priority = false,
            $save = true,
            $assign = false,
            $openDate = true,
            $timeOwnResolve = true,
            $type = true,
            $status = true,
            $urgency = true,
            $impact = true,
            $category = true,
            $requestSource = true,
            $location = true
        );

        //Drop being in charge from tech profile
        global $DB;
        $this->assertTrue(
            $DB->update(
                'glpi_profilerights',
                ['rights' => 136199],
                [
                    'profiles_id'  => 6,
                    'name'         => 'ticket',
                ]
            )
        );

        //ACLs have changed: login again.
        $this->assertTrue((bool) $auth->login('tech', 'tech', true));

        //reset rights. Done here so ACLs are reset even if tests fails.
        $this->assertTrue(
            $DB->update(
                'glpi_profilerights',
                ['rights' => 168967],
                [
                    'profiles_id'  => 6,
                    'name'         => 'ticket',
                ]
            )
        );

        $this->assertFalse((bool) $ticket->canAssign());
        $this->assertFalse((bool) $ticket->canAssignToMe());
        //check output with changed ACLs
        $this->checkFormOutput(
            $ticket,
            $name = false,
            $textarea = true,
            $priority = false,
            $save = true,
            $assign = false,
            $openDate = true,
            $timeOwnResolve = true,
            $type = true,
            $status = true,
            $urgency = true,
            $impact = true,
            $category = true,
            $requestSource = true,
            $location = true
        );

        //Add assign in charge from tech profile
        $this->assertTrue(
            $DB->update(
                'glpi_profilerights',
                ['rights' => 144391],
                [
                    'profiles_id'  => 6,
                    'name'         => 'ticket',
                ]
            )
        );

        //ACLs have changed: login again.
        $this->assertTrue((bool) $auth->login('tech', 'tech', true));

        //reset rights. Done here so ACLs are reset even if tests fails.
        $DB->update(
            'glpi_profilerights',
            ['rights' => 168967],
            [
                'profiles_id'  => 6,
                'name'         => 'ticket',
            ]
        );

        $this->assertTrue((bool) $ticket->canAssign());
        $this->assertFalse((bool) $ticket->canAssignToMe());
        //check output with changed ACLs
        $this->checkFormOutput(
            $ticket,
            $name = false,
            $textarea = true,
            $priority = false,
            $save = true,
            $assign = true,
            $openDate = true,
            $timeOwnResolve = true,
            $type = true,
            $status = true,
            $urgency = true,
            $impact = true,
            $category = true,
            $requestSource = true,
            $location = true
        );

        // Assign right without UPDATE
        $this->changeTechRight(Ticket::ASSIGN | Ticket::READALL);
        $this->checkFormOutput(
            $ticket,
            $name = false,
            $textarea = false,
            $priority = false,
            $save = true,
            $assign = true,
            $openDate = false,
            $timeOwnResolve = false,
            $type = false,
            $status = false,
            $urgency = false,
            $impact = false,
            $category = false,
            $requestSource = false,
            $location = false
        );
    }

    public function testUpdateFollowup()
    {
        $uid = getItemByTypeName('User', 'tech', true);
        $auth = new \Auth();
        $this->assertTrue((bool) $auth->login('tech', 'tech', true));

        $ticket = new Ticket();
        $this->assertGreaterThan(
            0,
            (int) $ticket->add([
                'name'    => '',
                'content' => 'A ticket to check followup updates',
            ])
        );

        //add a followup to the ticket
        $fup = new ITILFollowup();
        $this->assertGreaterThan(
            0,
            (int) $fup->add([
                'itemtype'  => $ticket::getType(),
                'items_id'   => $ticket->getID(),
                'users_id'     => $uid,
                'content'      => 'A simple followup',
            ])
        );

        $this->login();
        $uid2 = getItemByTypeName('User', TU_USER, true);
        $this->assertTrue($fup->getFromDB($fup->getID()));
        $this->assertTrue($fup->update([
            'id'        => $fup->getID(),
            'content'   => 'A simple edited followup',
        ]));

        $this->assertTrue($fup->getFromDB($fup->getID()));
        $this->assertIsArray($fup->fields);
        $this->assertEquals($uid, $fup->fields['users_id']);
        $this->assertEquals($uid2, $fup->fields['users_id_editor']);
    }

    public function testClone()
    {
        $this->login();
        $this->setEntity('Root entity', true);
        $ticket = new Ticket();
        $ticket_id = $ticket->add([
            'name'    => 'Ticket to check cloning',
            'content' => 'Ticket to check cloning',
        ]);
        $this->assertGreaterThan(0, $ticket_id);
        $task = new \TicketTask();
        $this->assertGreaterThan(
            0,
            (int) $task->add([
                'tickets_id' => $ticket_id,
                'content'    => 'A task to check cloning',
                'actiontime' => 3600,
            ])
        );

        $date = date('Y-m-d H:i:s');
        $_SESSION['glpi_currenttime'] = $date;

        // Test item cloning
        $added = $ticket->clone();
        $this->assertGreaterThan(0, (int) $added);

        $clonedTicket = new Ticket();
        $this->assertTrue($clonedTicket->getFromDB($added));

        // Check timeline items are not cloned
        $this->assertEquals(0, (int) $clonedTicket->getTimelineItems());

        $fields = $ticket->fields;

        // Check the ticket values. Id and dates must be different, everything else must be equal
        foreach ($fields as $k => $v) {
            switch ($k) {
                case 'id':
                    $this->assertNotEquals($ticket->getField($k), $clonedTicket->getField($k));
                    break;
                case 'date_mod':
                case 'date_creation':
                    $dateClone = new \DateTime($clonedTicket->getField($k));
                    $expectedDate = new \DateTime($date);
                    $this->assertEquals($expectedDate, $dateClone);
                    break;
                case 'name':
                    $this->assertEquals("{$ticket->getField($k)} (copy)", $clonedTicket->getField($k));
                    break;
                default:
                    $this->assertEquals($ticket->getField($k), $clonedTicket->getField($k), "$k");
            }
        }
    }

    public function testCloneActor()
    {
        $this->login();
        $this->setEntity('Root entity', true);
        $ticket = getItemByTypeName('Ticket', '_ticket01');

        $ticket_user = new Ticket_User();
        $this->assertGreaterThan(
            0,
            (int) $ticket_user->add([
                'tickets_id' => $ticket->getID(),
                'users_id' => (int) getItemByTypeName('User', 'post-only', true), //requester
                'type' => 1,
            ])
        );

        $this->assertGreaterThan(
            0,
            $ticket_user->add([
                'tickets_id' => $ticket->getID(),
                'users_id' => (int) getItemByTypeName('User', 'tech', true), //assign
                'type' => 2,
            ])
        );

        $ticket_Supplier = new Supplier_Ticket();
        $this->assertGreaterThan(
            0,
            (int) $ticket_Supplier->add([
                'tickets_id' => $ticket->getID(),
                'suppliers_id' => (int) getItemByTypeName('Supplier', '_suplier01_name', true), //observer
                'type' => 3,
            ])
        );

        $this->assertGreaterThan(
            0,
            (int) $ticket_Supplier->add([
                'tickets_id' => $ticket->getID(),
                'suppliers_id' => (int) getItemByTypeName('Supplier', '_suplier02_name', true), //requester
                'type' => 1,
            ])
        );

        $this->assertGreaterThan(
            0,
            $ticket_user->add([
                'tickets_id' => $ticket->getID(),
                'users_id' => (int) getItemByTypeName('User', 'normal', true), //observer
                'type' => 3,
            ])
        );

        $group_ticket = new Group_Ticket();
        $this->assertGreaterThan(
            0,
            $group_ticket->add([
                'tickets_id' => $ticket->getID(),
                'groups_id' => (int) getItemByTypeName('Group', '_test_group_1', true), //observer
                'type' => 3,
            ])
        );

        $this->assertGreaterThan(
            0,
            $group_ticket->add([
                'tickets_id' => $ticket->getID(),
                'groups_id' => (int) getItemByTypeName('Group', '_test_group_2', true), //assign
                'type' => 3,
            ])
        );

        $date = date('Y-m-d H:i:s');
        $_SESSION['glpi_currenttime'] = $date;

        // Test item cloning
        $added = $ticket->clone();
        $this->assertGreaterThan(0, (int) $added);

        $clonedTicket = new Ticket();
        $this->assertTrue($clonedTicket->getFromDB($added));

        // Check timeline items are not cloned except log
        $this->assertCount(
            0,
            $clonedTicket->getTimelineItems(['with_logs' => false])
        );

        $this->assertCount(
            8,
            $clonedTicket->getTimelineItems(['with_logs' => true])
        );
        //User: Add a link with an item: 5 times
        //Group: Add a link with an item: 2 times
        //Status: Change New to Processing (assigned): once

        //check actors
        $this->assertTrue(
            $ticket_user->getFromDBByCrit([
                'tickets_id' => $clonedTicket->getID(),
                'users_id' => (int) getItemByTypeName('User', 'post-only', true), //requester
                'type' => 1,
            ])
        );

        $this->assertTrue(
            $ticket_user->getFromDBByCrit([
                'tickets_id' => $clonedTicket->getID(),
                'users_id' => (int) getItemByTypeName('User', 'tech', true), //assign
                'type' => 2,
            ])
        );

        $this->assertTrue(
            $ticket_user->getFromDBByCrit([
                'tickets_id' => $clonedTicket->getID(),
                'users_id' => (int) getItemByTypeName('User', 'normal', true), //observer
                'type' => 3,
            ])
        );

        $this->assertTrue(
            $ticket_Supplier->getFromDBByCrit([
                'tickets_id' => $ticket->getID(),
                'suppliers_id' => (int) getItemByTypeName('Supplier', '_suplier01_name', true), //observer
                'type' => 3,
            ])
        );

        $this->assertTrue(
            $ticket_Supplier->getFromDBByCrit([
                'tickets_id' => $ticket->getID(),
                'suppliers_id' => (int) getItemByTypeName('Supplier', '_suplier02_name', true), //requester
                'type' => 1,
            ])
        );

        $this->assertTrue(
            $group_ticket->getFromDBByCrit([
                'tickets_id' => $clonedTicket->getID(),
                'groups_id' => (int) getItemByTypeName('Group', '_test_group_1', true), //observer
                'type' => 3,
            ])
        );

        $this->assertTrue(
            $group_ticket->getFromDBByCrit([
                'tickets_id' => $clonedTicket->getID(),
                'groups_id' => (int) getItemByTypeName('Group', '_test_group_2', true), //assign
                'type' => 3,
            ])
        );

        $fields = $ticket->fields;
        // Check the ticket values. Id and dates must be different, everything else must be equal
        foreach ($fields as $k => $v) {
            switch ($k) {
                case 'id':
                    $this->assertNotEquals($ticket->getField($k), $clonedTicket->getField($k));
                    break;
                case 'date_mod':
                case 'date_creation':
                    $dateClone = new \DateTime($clonedTicket->getField($k));
                    $expectedDate = new \DateTime($date);
                    $this->assertEquals($expectedDate, $dateClone);
                    break;
                case 'name':
                    $this->assertEquals("{$ticket->getField($k)} (copy)", $clonedTicket->getField($k));
                    break;
                default:
                    $this->assertEquals($ticket->getField($k), $clonedTicket->getField($k), "$k");
            }
        }
    }

    protected function testGetTimelinePosition2($tlp, $tickets_id)
    {
        foreach ($tlp as $users_name => $user) {
            $this->login($users_name, $user['pass']);
            $uid = getItemByTypeName('User', $users_name, true);

            // ITILFollowup
            $fup = new ITILFollowup();
            $this->assertGreaterThan(
                0,
                (int) $fup->add([
                    'itemtype'  => 'Ticket',
                    'items_id'   => $tickets_id,
                    'users_id'     => $uid,
                    'content'      => 'A simple followup',
                ])
            );

            $this->assertEquals(
                $user['pos'],
                (int) $fup->fields['timeline_position']
            );

            // TicketTask
            $task = new \TicketTask();
            $this->assertGreaterThan(
                0,
                (int) $task->add([
                    'tickets_id'   => $tickets_id,
                    'users_id'     => $uid,
                    'content'      => 'A simple Task',
                ])
            );

            $this->assertEquals(
                $user['pos'],
                (int) $task->fields['timeline_position']
            );

            // Document and Document_Item
            $doc = new \Document();
            $this->assertGreaterThan(
                0,
                (int) $doc->add([
                    'users_id'     => $uid,
                    'tickets_id'   => $tickets_id,
                    'name'         => 'A simple document object',
                ])
            );

            $doc_item = new \Document_Item();
            $this->assertGreaterThan(
                0,
                (int) $doc_item->add([
                    'users_id'      => $uid,
                    'items_id'      => $tickets_id,
                    'itemtype'      => 'Ticket',
                    'documents_id'  => $doc->getID(),
                ])
            );

            $this->assertEquals(
                $user['pos'],
                (int) $doc_item->fields['timeline_position']
            );

            // TicketValidation
            $val = new TicketValidation();
            $this->assertGreaterThan(
                0,
                (int) $val->add([
                    'tickets_id'   => $tickets_id,
                    'comment_submission'      => 'A simple validation',
                    'itemtype_target' => 'User',
                    'items_id_target' => 5, // normal
                    'status' => 2,
                ])
            );

            $this->assertEquals(
                $user['pos'],
                (int) $val->fields['timeline_position']
            );
        }
    }

    protected function testGetTimelinePositionSolution($tlp, $tickets_id)
    {
        foreach ($tlp as $users_name => $user) {
            $this->login($users_name, $user['pass']);
            $uid = getItemByTypeName('User', $users_name, true);

            // Ticket Solution
            $tkt = new Ticket();
            $this->assertTrue(
                (bool) $tkt->update([
                    'id'   => $tickets_id,
                    'solution'      => 'A simple solution from ' . $users_name,
                ])
            );

            $this->assertEquals(
                $user['pos'],
                (int) $tkt->getTimelinePosition($tickets_id, 'ITILSolution', $uid)
            );
        }
    }

    public function testGetTimelinePosition()
    {

        // login TU_USER
        $this->login();

        // create ticket
        // with post-only as requester
        // tech as assigned to
        // normal as observer
        $ticket = new Ticket();
        $this->assertGreaterThan(
            0,
            (int) $ticket->add([
                'name'                => 'ticket title',
                'content'             => 'a description',
                '_users_id_requester' => '3', // post-only
                '_users_id_observer'  => '5', // normal
                '_users_id_assign'    => ['4', '5'], // tech and normal
            ])
        );

        $tlp = [
            'glpi'      => ['pass' => 'glpi',     'pos' => CommonITILObject::TIMELINE_LEFT],
            'post-only' => ['pass' => 'postonly', 'pos' => CommonITILObject::TIMELINE_LEFT],
            'tech'      => ['pass' => 'tech',     'pos' => CommonITILObject::TIMELINE_RIGHT],
            'normal'    => ['pass' => 'normal',   'pos' => CommonITILObject::TIMELINE_RIGHT],
        ];

        $this->testGetTimelinePosition2($tlp, $ticket->getID());

        // Solution timeline tests
        $tlp = [
            'tech' => [
                'pass' => 'tech',
                'pos' => CommonITILObject::TIMELINE_RIGHT,
            ],
        ];

        $this->testGetTimelinePositionSolution($tlp, $ticket->getID());

        return $ticket->getID();
    }

    public function testGetTimelineItemsPosition()
    {

        $tkt_id = $this->testGetTimelinePosition();

        // login TU_USER
        $this->login();

        $ticket = new Ticket();
        $this->assertTrue(
            (bool) $ticket->getFromDB($tkt_id)
        );

        // test timeline_position from getTimelineItems()
        $timeline_items = $ticket->getTimelineItems();

        foreach ($timeline_items as $item) {
            switch ($item['type']) {
                case 'ITILFollowup':
                case 'TicketTask':
                case 'TicketValidation':
                case 'Document_Item':
                    if (in_array($item['item']['users_id'], [2, 3])) {
                        $this->assertEquals(CommonITILObject::TIMELINE_LEFT, (int) $item['item']['timeline_position']);
                    } else {
                        $this->assertEquals(CommonITILObject::TIMELINE_RIGHT, (int) $item['item']['timeline_position']);
                    }
                    break;
                case 'ITILSolution':
                    $this->assertEquals(CommonITILObject::TIMELINE_RIGHT, (int) $item['item']['timeline_position']);
                    break;
            }
        }
    }

    public static function inputProvider()
    {
        return [
            [
                'input'     => [
                    'name'     => 'This is a title',
                    'content'   => 'This is a content',
                ],
                'expected'  => [
                    'name' => 'This is a title',
                    'content' => 'This is a content',
                ],
            ], [
                'input'     => [
                    'name'      => '',
                    'content'   => 'This is a content',
                ],
                'expected'  => [
                    'name' => 'This is a content',
                    'content' => 'This is a content',
                ],
            ], [
                'input'     => [
                    'name'      => '',
                    'content'   => "This is a content\nwith a carriage return",
                ],
                'expected'  => [
                    'name' => 'This is a content with a carriage return',
                    'content' => "This is a content\nwith a carriage return",
                ],
            ], [
                'input'     => [
                    'name'      => '',
                    'content'   => "This is a content\r\nwith a carriage return",
                ],
                'expected'  => [
                    'name' => 'This is a content with a carriage return',
                    'content' => "This is a content\nwith a carriage return",
                ],
            ], [
                'input'     => [
                    'name'      => '',
                    'content'   => "<p>This is a content\r\nwith a carriage return</p>",
                ],
                'expected'  => [
                    'name' => 'This is a content with a carriage return',
                    'content' => "<p>This is a content\nwith a carriage return</p>",
                ],
            ], [
                'input'     => [
                    'name'      => '',
                    'content'   => 'Test for buggy &#039; character',
                ],
                'expected'  => [
                    'name'      => "Test for buggy ' character",
                    'content'   => "Test for buggy &#039; character",
                ],
            ],
        ];
    }

    #[DataProvider('inputProvider')]
    public function testPrepareInputForAdd($input, $expected)
    {
        $instance = new Ticket();
        $prepared = $instance->prepareInputForAdd($input);
        $this->assertSame($expected['name'], $prepared['name']);
        $this->assertSame($expected['content'], $prepared['content']);
    }

    public function testAssignChangeStatus()
    {
        // login postonly
        $this->login('post-only', 'postonly');

        //create a new ticket
        $ticket = new Ticket();
        $this->assertGreaterThan(
            0,
            (int) $ticket->add([
                'name'    => '',
                'content' => 'A ticket to check change of status when using "associate myself" feature',
            ])
        );
        $tickets_id = $ticket->getID();
        $this->assertTrue($ticket->getFromDB($tickets_id));

        // login TU_USER
        $this->login();

        // simulate "associate myself" feature
        $ticket_user = new Ticket_User();
        $input_ticket_user = [
            'tickets_id'       => $tickets_id,
            'users_id'         => Session::getLoginUserID(),
            'use_notification' => 1,
            'type'             => CommonITILActor::ASSIGN,
        ];
        $this->assertGreaterThan(0, (int) $ticket_user->add($input_ticket_user));
        $this->assertTrue($ticket_user->getFromDB($ticket_user->getId()));

        // check status (should be ASSIGNED)
        $this->assertTrue($ticket->getFromDB($tickets_id));
        $this->assertEquals(CommonITILObject::ASSIGNED, (int) $ticket->fields['status']);

        // remove associated user
        $ticket_user->delete([
            'id' => $ticket_user->getId(),
        ]);

        // check status (should be INCOMING)
        $this->assertTrue($ticket->getFromDB($tickets_id));
        $this->assertEquals(CommonITILObject::INCOMING, (int) $ticket->fields['status']);

        // drop UPDATE right to TU_USER and redo "associate myself"
        $saverights = $_SESSION['glpiactiveprofile'];
        $_SESSION['glpiactiveprofile']['ticket'] -= \UPDATE;
        $this->assertGreaterThan(0, (int) $ticket_user->add($input_ticket_user));
        // restore rights
        $_SESSION['glpiactiveprofile'] = $saverights;
        //check ticket creation
        $this->assertTrue($ticket_user->getFromDB($ticket_user->getId()));

        // check status (should be ASSIGNED)
        $this->assertTrue($ticket->getFromDB($tickets_id));
        $this->assertEquals(CommonITILObject::ASSIGNED, (int) $ticket->fields['status']);

        // remove associated user
        $ticket_user->delete([
            'id' => $ticket_user->getId(),
        ]);

        // check status (should be INCOMING)
        $this->assertTrue($ticket->getFromDB($tickets_id));
        $this->assertEquals(CommonITILObject::INCOMING, (int) $ticket->fields['status']);

        // remove associated user
        $ticket_user->delete([
            'id' => $ticket_user->getId(),
        ]);

        // check with very limited rights and redo "associate myself"
        $_SESSION['glpiactiveprofile']['ticket'] = \CREATE
                                               + Ticket::READMY
                                               + Ticket::READALL
                                               + Ticket::READGROUP
                                               + Ticket::OWN; // OWN right must allow self-assign
        $this->assertGreaterThan(0, (int) $ticket_user->add($input_ticket_user));
        // restore rights
        $_SESSION['glpiactiveprofile'] = $saverights;
        //check ticket creation
        $this->assertTrue($ticket_user->getFromDB($ticket_user->getId()));

        // check status (should still be ASSIGNED)
        $this->assertTrue($ticket->getFromDB($tickets_id));
        $this->assertEquals(CommonITILObject::ASSIGNED, (int) $ticket->fields['status']);
    }

    public function testClosedTicketTransfer()
    {

        // 1- create a category and location
        $itilcat      = new ITILCategory();
        $first_cat_id = $itilcat->add([
            'name' => 'my first cat',
        ]);
        $this->assertFalse($itilcat->isNewItem());

        $itilloc      = new \Location();
        $first_loc_id = $itilloc->add([
            'name' => 'my first loc',
        ]);
        $this->assertFalse($itilloc->isNewItem());

        // 2- create a category and location
        $second_cat    = new ITILCategory();
        $second_cat_id = $second_cat->add([
            'name' => 'my second cat',
        ]);
        $this->assertFalse($second_cat->isNewItem());

        $second_loc    = new \Location();
        $second_loc_id = $second_loc->add([
            'name' => 'my second loc',
        ]);
        $this->assertFalse($second_loc->isNewItem());

        // 3- create ticket
        $ticket    = new Ticket();
        $ticket_id = $ticket->add([
            'name'              => 'A ticket to check the category change when using the "transfer" function.',
            'content'           => 'A ticket to check the category change when using the "transfer" function.',
            'itilcategories_id' => $first_cat_id,
            'status'            => CommonITILObject::CLOSED,
            'locations_id'       => $first_loc_id,
        ]);

        $this->assertFalse($ticket->isNewItem());

        // 4 - delete category and location with replacement
        $itilcat->delete(['id'          => $first_cat_id,
            '_replace_by' => $second_cat_id,
        ], 1);

        $itilloc->delete(['id'          => $first_loc_id,
            '_replace_by' => $second_loc_id,
        ], 1);

        // 5 - check that the category and the location has been replaced in the ticket
        $ticket->getFromDB($ticket_id);
        $this->assertEquals($second_cat_id, (int) $ticket->fields['itilcategories_id']);
        $this->assertEquals($second_loc_id, (int) $ticket->fields['locations_id']);
    }

    public static function computePriorityProvider()
    {
        return [
            [
                'input'    => [
                    'urgency'   => 2,
                    'impact'    => 2,
                ],
                'urgency'  => 2,
                'impact'   => 2,
                'priority' => 2,
            ], [
                'input'    => [
                    'urgency'   => 5,
                ],
                'urgency'  => 5,
                'impact'   => 3,
                'priority' => 4,
            ], [
                'input'    => [
                    'impact'   => 5,
                ],
                'urgency'  => 3,
                'impact'   => 5,
                'priority' => 4,
            ], [
                'input'    => [
                    'urgency'   => 5,
                    'impact'    => 5,
                ],
                'urgency'  => 5,
                'impact'   => 5,
                'priority' => 5,
            ], [
                'input'    => [
                    'urgency'   => 5,
                    'impact'    => 1,
                ],
                'urgency'  => 5,
                'impact'   => 1,
                'priority' => 2,
            ],
        ];
    }

    #[DataProvider('computePriorityProvider')]
    public function testComputePriority($input, $urgency, $impact, $priority)
    {
        $this->login();
        $ticket = getItemByTypeName('Ticket', '_ticket01');
        $input['id'] = $ticket->fields['id'];
        $result = $ticket->prepareInputForUpdate($input);
        $this->assertSame($urgency, $result['urgency']);
        $this->assertSame($impact, $result['impact']);
        $this->assertSame($priority, $result['priority']);
    }

    public function testGetDefaultValues()
    {
        $input = Ticket::getDefaultValues();

        $this->assertEquals(0, $input['_users_id_requester']);
        $this->assertContains('1', $input['_users_id_requester_notif']['use_notification']);
        $this->assertContains('', $input['_users_id_requester_notif']['alternative_email']);

        $this->assertEquals(0, $input['_groups_id_requester']);

        $this->assertEquals(0, $input['_users_id_assign']);
        $this->assertContains('1', $input['_users_id_assign_notif']['use_notification']);
        $this->assertContains('', $input['_users_id_assign_notif']['alternative_email']);

        $this->assertEquals(0, $input['_groups_id_assign']);

        $this->assertEquals(0, $input['_users_id_observer']);
        $this->assertContains(1, $input['_users_id_observer_notif']['use_notification']);
        $this->assertContains('', $input['_users_id_observer_notif']['alternative_email']);

        $this->assertEquals(0, $input['_suppliers_id_assign']);
        $this->assertContains(1, $input['_suppliers_id_assign_notif']['use_notification']);
        $this->assertContains('', $input['_suppliers_id_assign_notif']['alternative_email']);

        $this->assertEquals('', $input['name']);
        $this->assertEquals('', $input['content']);
        $this->assertEquals(0, (int) $input['itilcategories_id']);
        $this->assertEquals(3, (int) $input['urgency']);
        $this->assertEquals(3, (int) $input['impact']);
        $this->assertEquals(3, (int) $input['priority']);
        $this->assertEquals(1, (int) $input['requesttypes_id']);
        $this->assertEquals(0, (int) $input['actiontime']);
        $this->assertEquals(0, (int) $input['entities_id']);
        $this->assertEquals(Ticket::INCOMING, (int) $input['status']);
        $this->assertCount(0, $input['followup']);
        $this->assertEquals('', $input['itemtype']);
        $this->assertEquals(0, (int) $input['items_id']);
        $this->assertCount(0, $input['plan']);

        $this->assertEquals('NULL', $input['time_to_resolve']);
        $this->assertEquals('NULL', $input['time_to_own']);
        $this->assertEquals(0, (int) $input['slas_id_tto']);
        $this->assertEquals(0, (int) $input['slas_id_ttr']);

        $this->assertEquals('NULL', $input['internal_time_to_resolve']);
        $this->assertEquals('NULL', $input['internal_time_to_own']);
        $this->assertEquals(0, (int) $input['olas_id_tto']);
        $this->assertEquals(0, (int) $input['olas_id_ttr']);

        $this->assertEquals(0, (int) $input['_add_validation']);

        $this->assertCount(0, $input['_validation_targets']);
        $this->assertEquals(Ticket::INCIDENT_TYPE, (int) $input['type']);
        $this->assertCount(0, $input['_documents_id']);
        $this->assertCount(0, $input['_tasktemplates_id']);
        $this->assertCount(0, $input['_filename']);
        $this->assertCount(0, $input['_tag_filename']);
    }

    /**
     * @see self::testCanTakeIntoAccount()
     */
    public static function canTakeIntoAccountProvider()
    {
        return [
            [
                'input'    => [
                    '_users_id_requester' => ['3'], // "post-only"
                ],
                'user'     => [
                    'login'    => 'post-only',
                    'password' => 'postonly',
                ],
                'expected' => false, // is requester, so cannot take into account
            ],
            [
                'input'    => [
                    '_users_id_requester' => ['3', '4'], // "post-only" and "tech"
                ],
                'user'     => [
                    'login'    => 'tech',
                    'password' => 'tech',
                ],
                'expected' => false, // is requester, so cannot take into account
            ],
            [
                'input'    => [
                    '_users_id_requester' => ['3'], // "post-only"
                ],
                'user'     => [
                    'login'    => 'tech',
                    'password' => 'tech',
                ],
                'expected' => true, // has enough rights so can take into account
            ],
            [
                'input'    => [
                    '_users_id_requester' => ['3'], // "post-only"
                ],
                'user'     => [
                    'login'    => 'tech',
                    'password' => 'tech',
                    'rights'   => [
                        'task' => \READ,
                        'followup' => \READ,
                    ],
                ],
                'expected' => false, // has not enough rights so cannot take into account
            ],
            [
                'input'    => [
                    '_users_id_requester' => ['3'], // "post-only"
                ],
                'user'     => [
                    'login'    => 'tech',
                    'password' => 'tech',
                    'rights'   => [
                        'task' => \READ + \CommonITILTask::ADDALLITEM,
                        'followup' => \READ,
                    ],
                ],
                'expected' => true, // has enough rights so can take into account
            ],
            [
                'input'    => [
                    '_users_id_requester' => ['3'], // "post-only"
                ],
                'user'     => [
                    'login'    => 'tech',
                    'password' => 'tech',
                    'rights'   => [
                        'task' => \READ,
                        'followup' => \READ + ITILFollowup::ADDALLITEM,
                    ],
                ],
                'expected' => true, // has enough rights so can take into account
            ],
            [
                'input'    => [
                    '_users_id_requester' => ['3'], // "post-only"
                ],
                'user'     => [
                    'login'    => 'tech',
                    'password' => 'tech',
                    'rights'   => [
                        'task' => \READ,
                        'followup' => \READ + ITILFollowup::ADDMY,
                    ],
                ],
                'expected' => true, // has enough rights so can take into account
            ],
            [
                'input'    => [
                    '_users_id_requester' => ['3'], // "post-only"
                ],
                'user'     => [
                    'login'    => 'tech',
                    'password' => 'tech',
                    'rights'   => [
                        'task' => \READ,
                        'followup' => \READ + ITILFollowup::ADD_AS_GROUP,
                    ],
                ],
                'expected' => true, // has enough rights so can take into account
            ],
            [
                'input'    => [
                    '_do_not_compute_takeintoaccount' => 1,
                    '_users_id_requester'             => ['4'], // "tech"
                    '_users_id_assign'                => ['4'], // "tech"
                ],
                'user'     => [
                    'login'    => 'tech',
                    'password' => 'tech',
                ],
                // is requester but also assigned, so can take into account
                // this is only possible if "_do_not_compute_takeintoaccount" flag is set by business rules
                'expected' => true,
            ],
        ];
    }

    /**
     * Tests ability to take a ticket into account.
     *
     * @param array   $input    Input used to create the ticket
     * @param array   $user     Array containing 'login' and 'password' fields of tested user,
     *                          and a 'rights' array if rights have to be forced
     * @param boolean $expected Expected result of "Ticket::canTakeIntoAccount()" method
     */
    #[DataProvider('canTakeIntoAccountProvider')]
    public function testCanTakeIntoAccount(array $input, array $user, bool $expected)
    {
        // Create a ticket
        $this->login();
        $_SESSION['glpiset_default_tech'] = false;
        $ticket = new Ticket();
        $ticketId = $ticket->add(
            $input + [
                'name'    => '',
                'content' => 'A ticket to check canTakeIntoAccount() results',
                'status'  => CommonITILObject::ASSIGNED,
            ]
        );
        $this->assertGreaterThan(0, (int) $ticketId);
        // Reload ticket to get all default fields values
        $this->assertTrue($ticket->getFromDB($ticketId));
        // Validate that "takeintoaccount_delay_stat" is not automatically defined
        $this->assertEquals(0, (int) $ticket->fields['takeintoaccount_delay_stat']);
        $this->assertEquals(null, $ticket->fields['takeintoaccountdate']);
        // Login with tested user
        $this->login($user['login'], $user['password']);
        // Apply specific rights if defined
        if (array_key_exists('rights', $user)) {
            foreach ($user['rights'] as $rightname => $rightvalue) {
                $_SESSION['glpiactiveprofile'][$rightname] = $rightvalue;
            }
        }
        // Verify result
        $this->assertEquals($expected, $ticket->canTakeIntoAccount());

        // Check that computation of "takeintoaccount_delay_stat" can be prevented
        $this->modifyCurrentTime('+1 second'); // be sure to wait at least one second before updating
        $this->assertTrue(
            $ticket->update(
                [
                    'id'                              => $ticketId,
                    'content'                         => 'Updated ticket 1',
                    '_do_not_compute_takeintoaccount' => 1,
                ]
            )
        );
        $this->assertEquals(0, (int) $ticket->fields['takeintoaccount_delay_stat']);
        $this->assertEquals(null, $ticket->fields['takeintoaccountdate']);

        // Check that computation of "takeintoaccount_delay_stat" is done if user can take into account
        $this->assertTrue(
            $ticket->update(
                [
                    'id'      => $ticketId,
                    'content' => 'Updated ticket 2',
                ]
            )
        );
        if (!$expected) {
            $this->assertEquals(0, (int) $ticket->fields['takeintoaccount_delay_stat']);
            $this->assertEquals(null, $ticket->fields['takeintoaccountdate']);
        } else {
            $this->assertGreaterThan(0, (int) $ticket->fields['takeintoaccount_delay_stat']);
            $this->assertEquals($_SESSION['glpi_currenttime'], $ticket->fields['takeintoaccountdate']);
        }
    }

    /**
     * Tests taken into account state.
     */
    public function testIsAlreadyTakenIntoAccount()
    {
        // Create a ticket
        $this->login();
        $_SESSION['glpiset_default_tech'] = false;
        $ticket = new Ticket();
        $ticket_id = $ticket->add(
            [
                'name'    => '',
                'content' => 'A ticket to check isAlreadyTakenIntoAccount() results',
            ]
        );
        $this->assertGreaterThan(0, (int) $ticket_id);

        // Reload ticket to get all default fields values
        $this->assertTrue($ticket->getFromDB($ticket_id));

        // Empty ticket is not taken into account
        $this->assertFalse($ticket->isAlreadyTakenIntoAccount());

        // Take into account
        $this->login('tech', 'tech');
        $ticket_user = new Ticket_User();
        $ticket_user_id = $ticket_user->add(
            [
                'tickets_id'       => $ticket_id,
                'users_id'         => Session::getLoginUserID(),
                'use_notification' => 1,
                'type'             => CommonITILActor::ASSIGN,
            ]
        );
        $this->assertGreaterThan(0, (int) $ticket_user_id);

        // Assign to tech made ticket taken into account
        $this->assertTrue($ticket->getFromDB($ticket_id));
        $this->assertTrue($ticket->isAlreadyTakenIntoAccount());
    }

    public function testCronCloseTicket()
    {
        global $DB;
        $this->login();
        // set default calendar and autoclose delay in root entity
        $entity = new Entity();
        $this->assertTrue($entity->update([
            'id'              => 0,
            'calendars_id'    => 1,
            'autoclose_delay' => 5,
        ]));

        // create some solved tickets at various solvedate
        $ticket = new Ticket();
        $tickets_id_1 = $ticket->add([
            'name'        => "test autoclose 1",
            'content'     => "test autoclose 1",
            'entities_id' => 0,
            'status'      => CommonITILObject::SOLVED,
        ]);
        $this->assertGreaterThan(0, (int) $tickets_id_1);
        $DB->update('glpi_tickets', [
            'solvedate' => date('Y-m-d 10:00:00', time() - 10 * DAY_TIMESTAMP),
        ], [
            'id' => $tickets_id_1,
        ]);
        $tickets_id_2 = $ticket->add([
            'name'        => "test autoclose 1",
            'content'     => "test autoclose 1",
            'entities_id' => 0,
            'status'      => CommonITILObject::SOLVED,
        ]);
        $DB->update('glpi_tickets', [
            'solvedate' => date('Y-m-d 10:00:00', time()),
        ], [
            'id' => $tickets_id_2,
        ]);
        $this->assertGreaterThan(0, (int) $tickets_id_2);

        // launch Cron for closing tickets
        $mode = - CronTask::MODE_EXTERNAL; // force
        CronTask::launch($mode, 5, 'closeticket');

        // check ticket status
        $this->assertTrue($ticket->getFromDB($tickets_id_1));
        $this->assertEquals(CommonITILObject::CLOSED, (int) $ticket->fields['status']);
        $this->assertTrue($ticket->getFromDB($tickets_id_2));
        $this->assertEquals(CommonITILObject::SOLVED, (int) $ticket->fields['status']);
    }

    /**
     * @see self::testTakeIntoAccountDelayComputationOnCreate()
     * @see self::testTakeIntoAccountDelayComputationOnUpdate()
     */
    protected function takeIntoAccountDelayComputationProvider()
    {
        $this->login();
        $group = new Group();
        $group_id = $group->add(['name' => 'Test group']);
        $this->assertGreaterThan(0, (int) $group_id);

        $group_user = new Group_User();
        $this->assertGreaterThan(
            0,
            (int) $group_user->add([
                'groups_id' => $group_id,
                'users_id'  => '4', // "tech"
            ])
        );

        $test_cases = [
            [
                'input'    => [
                    'content' => 'test',
                ],
                'computed' => false, // not computed as tech is requester
            ],
            [
                'input'    => [
                    '_users_id_assign' => '4', // "tech"
                ],
                'computed' => true, // computed on asignment
            ],
            [
                'input'    => [
                    '_users_id_observer' => '4', // "tech"
                ],
                'computed' => false, // not computed as new actor is not assigned
            ],
            /* Triggers PHP error "Uncaught Error: [] operator not supported for strings in /var/www/glpi/inc/ticket.class.php:1162"
         [
            'input'    => [
               '_users_id_requester' => '3', // "post-only"
            ],
            'computed' => false, // not computed as new actor is not assigned
         ],
         */
            [
                'input'    => [
                    '_additional_assigns' => [
                        ['users_id' => '4'], // "tech"
                    ],
                ],
                'computed' => true, // computed on asignment
            ],
            [
                'input'    => [
                    '_additional_observers' => [
                        ['users_id' => '4'], // "tech"
                    ],
                ],
                'computed' => false, // not computed as new actor is not assigned
            ],
            [
                'input'    => [
                    '_additional_requesters' => [
                        ['users_id' => '2'], // "post-only"
                    ],
                ],
                'computed' => false, // not computed as new actor is not assigned
            ],
            [
                'input'    => [
                    '_groups_id_assign' => $group_id,
                ],
                'computed' => true, // computed on asignment
            ],
            [
                'input'    => [
                    '_groups_id_observer' => $group_id,
                ],
                'computed' => false, // not computed as new actor is not assigned
            ],
            [
                'input'    => [
                    '_groups_id_requester' => $group_id,
                ],
                'computed' => false, // not computed as new actor is not assigned
            ],
            [
                'input'    => [
                    '_additional_groups_assigns' => [$group_id],
                ],
                'computed' => true, // computed on asignment
            ],
            [
                'input'    => [
                    '_additional_groups_observers' => [$group_id],
                ],
                'computed' => false, // not computed as new actor is not assigned
            ],
            [
                'input'    => [
                    '_additional_groups_requesters' => [$group_id],
                ],
                'computed' => false, // not computed as new actor is not assigned
            ],
            /* Not computing delay, do not know why
         [
            'input'    => [
               '_suppliers_id_assign' => '1', // "_suplier01_name"
            ],
            'computed' => true, // computed on asignment
         ],
         */
            [
                'input'    => [
                    '_additional_suppliers_assigns' => [
                        ['suppliers_id' => '1'], // "_suplier01_name"
                    ],
                ],
                'computed' => true, // computed on asignment
            ],
        ];

        // for all test cases that expect a computation
        // add a test case with '_do_not_compute_takeintoaccount' flag to check that computation is prevented
        foreach ($test_cases as $test_case) {
            $test_case['input']['_do_not_compute_takeintoaccount'] = 1;
            $test_case['computed'] = false;
            $test_cases[] = $test_case;
        }

        return $test_cases;
    }

    /**
     * Tests that "takeintoaccount_delay_stat" is computed (or not) as expected on ticket creation.
     */
    public function testTakeIntoAccountDelayComputationOnCreate()
    {
        $provider = $this->takeIntoAccountDelayComputationProvider();
        $this->login('tech', 'tech'); // Login with tech to be sure to be the requester

        foreach ($provider as $row) {
            $input = $row['input'];
            $computed = $row['computed'];

            // Create a ticket
            $_SESSION['glpiset_default_tech'] = false;
            $ticket = new Ticket();
            $ticketId = $ticket->add(
                $input + [
                    'name' => '',
                    'content' => 'A ticket to check takeintoaccount_delay_stat computation state',
                ]
            );
            $this->assertGreaterThan(0, (int) $ticketId);

            // Reload ticket to get all default fields values
            $this->assertTrue($ticket->getFromDB($ticketId));

            if (!$computed) {
                $this->assertEquals(0, (int) $ticket->fields['takeintoaccount_delay_stat']);
                $this->assertEquals(null, $ticket->fields['takeintoaccountdate']);
            } else {
                $this->assertGreaterThan(0, (int) $ticket->fields['takeintoaccount_delay_stat']);
                $this->assertEquals($_SESSION['glpi_currenttime'], $ticket->fields['takeintoaccountdate']);
            }
        }
    }

    /**
     * Tests that "takeintoaccount_delay_stat" is computed (or not) as expected on ticket update.
     */
    public function testTakeIntoAccountDelayComputationOnUpdate()
    {
        $provider = $this->takeIntoAccountDelayComputationProvider();
        $this->login('tech', 'tech'); // Login with tech to be sure to be the requester

        foreach ($provider as $row) {
            $input = $row['input'];
            $computed = $row['computed'];

            // Create a ticket
            $_SESSION['glpiset_default_tech'] = false;
            $ticket = new Ticket();
            $ticketId = $ticket->add(
                [
                    'name' => '',
                    'content' => 'A ticket to check takeintoaccount_delay_stat computation state',
                ]
            );
            $this->assertGreaterThan(0, (int) $ticketId);

            // Reload ticket to get all default fields values
            $this->assertTrue($ticket->getFromDB($ticketId));

            // Validate that "takeintoaccount_delay_stat" is not automatically defined
            $this->assertEquals(0, (int) $ticket->fields['takeintoaccount_delay_stat']);
            $this->assertEquals(null, $ticket->fields['takeintoaccountdate']);

            // Login with tech to be sure to be have rights to take into account
            $this->login('tech', 'tech');

            $this->modifyCurrentTime('+1 second'); // be sure to wait at least one second before updating
            $this->assertTrue(
                $ticket->update(
                    $input + [
                        'id' => $ticketId,
                    ]
                )
            );

            // Reload ticket to get fresh values that can be defined by a tier object
            $this->assertTrue($ticket->getFromDB($ticketId));

            if (!$computed) {
                $this->assertEquals(0, (int) $ticket->fields['takeintoaccount_delay_stat']);
                $this->assertEquals(null, $ticket->fields['takeintoaccountdate']);
            } else {
                $this->assertGreaterThan(0, (int) $ticket->fields['takeintoaccount_delay_stat']);
                $this->assertEquals($_SESSION['glpi_currenttime'], $ticket->fields['takeintoaccountdate']);
            }
        }
    }

    /**
     * @see self::testStatusComputationOnCreate()
     */
    protected function statusComputationOnCreateProvider()
    {

        $group = new Group();
        $group_id = $group->add(['name' => 'Test group']);
        $this->assertGreaterThan(0, (int) $group_id);

        return [
            [
                'input'    => [
                    '_users_id_assign' => ['4'], // "tech"
                    'status' => CommonITILObject::INCOMING,
                ],
                'expected' => CommonITILObject::ASSIGNED, // incoming changed to assign as actors are set
            ],
            [
                'input'    => [
                    '_groups_id_assign' => $group_id,
                    'status' => CommonITILObject::INCOMING,
                ],
                'expected' => CommonITILObject::ASSIGNED, // incoming changed to assign as actors are set
            ],
            [
                'input'    => [
                    '_suppliers_id_assign' => '1', // "_suplier01_name"
                    'status' => CommonITILObject::INCOMING,
                ],
                'expected' => CommonITILObject::ASSIGNED, // incoming changed to assign as actors are set
            ],
            [
                'input'    => [
                    '_users_id_assign' => ['4'], // "tech"
                    'status' => CommonITILObject::INCOMING,
                    '_do_not_compute_status' => '1',
                ],
                'expected' => CommonITILObject::INCOMING, // flag prevent status change
            ],
            [
                'input'    => [
                    '_groups_id_assign' => $group_id,
                    'status' => CommonITILObject::INCOMING,
                    '_do_not_compute_status' => '1',
                ],
                'expected' => CommonITILObject::INCOMING, // flag prevent status change
            ],
            [
                'input'    => [
                    '_suppliers_id_assign' => '1', // "_suplier01_name"
                    'status' => CommonITILObject::INCOMING,
                    '_do_not_compute_status' => '1',
                ],
                'expected' => CommonITILObject::INCOMING, // flag prevent status change
            ],
            [
                'input'    => [
                    '_users_id_assign' => ['4'], // "tech"
                    'status' => CommonITILObject::WAITING,
                ],
                'expected' => CommonITILObject::WAITING, // status not changed as not "new"
            ],
            [
                'input'    => [
                    '_groups_id_assign' => $group_id,
                    'status' => CommonITILObject::WAITING,
                ],
                'expected' => CommonITILObject::WAITING, // status not changed as not "new"
            ],
            [
                'input'    => [
                    '_suppliers_id_assign' => '1', // "_suplier01_name"
                    'status' => CommonITILObject::WAITING,
                ],
                'expected' => CommonITILObject::WAITING, // status not changed as not "new"
            ],
        ];
    }

    /**
     * Check computed status on ticket creation
     */
    public function testStatusComputationOnCreate()
    {
        $provider = $this->statusComputationOnCreateProvider();
        foreach ($provider as $row) {
            $input = $row['input'];
            $expected = $row['expected'];

            // Create a ticket
            $this->login();
            $_SESSION['glpiset_default_tech'] = false;
            $ticket = new Ticket();
            $ticketId = (int) $ticket->add([
                'name' => '',
                'content' => 'A ticket to check status computation',
            ] + $input);
            $this->assertGreaterThan(0, $ticketId);

            // Reload ticket to get computed fields values
            $this->assertTrue($ticket->getFromDB($ticketId));

            // Check status
            $this->assertEquals($expected, (int) $ticket->fields['status']);
        }
    }

    public function testLocationAssignment()
    {
        $rule = new Rule();
        $rule->getFromDBByCrit([
            'sub_type' => 'RuleTicket',
            'name' => 'Ticket location from user',
        ]);
        $location = new \Location();
        $location->getFromDBByCrit([
            'name' => '_location01',
        ]);
        $user = new User();
        $user->add([
            'name' => $this->getUniqueString(),
            'locations_id' => $location->getID(),
        ]);

        // test ad ticket with single requester
        $ticket = new Ticket();
        $rule->update([
            'id' => $rule->getID(),
            'is_active' => '1',
        ]);
        $ticket->add([
            '_users_id_requester' => $user->getID(),
            'name' => 'test location assignment',
            'content' => 'test location assignment',
        ]);
        $rule->update([
            'id' => $rule->getID(),
            'is_active' => '0',
        ]);
        $ticket->getFromDB($ticket->getID());
        $this->assertEquals($location->getID(), (int) $ticket->fields['locations_id']);

        // test add ticket with multiple requesters
        $ticket = new Ticket();
        $rule->update([
            'id' => $rule->getID(),
            'is_active' => '1',
        ]);
        $ticket->add([
            '_users_id_requester' => [$user->getID(), 2],
            'name' => 'test location assignment',
            'content' => 'test location assignment',
        ]);
        $rule->update([
            'id' => $rule->getID(),
            'is_active' => '0',
        ]);
        $ticket->getFromDB($ticket->getID());
        $this->assertEquals($location->getID(), (int) $ticket->fields['locations_id']);

        // test add ticket with multiple requesters
        $ticket = new Ticket();
        $rule->update([
            'id' => $rule->getID(),
            'is_active' => '1',
        ]);
        $ticket->add([
            '_users_id_requester' => [2, $user->getID()],
            'name' => 'test location assignment',
            'content' => 'test location assignment',
        ]);
        $rule->update([
            'id' => $rule->getID(),
            'is_active' => '0',
        ]);
        $ticket->getFromDB($ticket->getID());
        $this->assertEquals(0, (int) $ticket->fields['locations_id']);
    }

    public function testCronPurgeTicket()
    {

        $this->login(); // must be logged as Document_Item uses Session::getLoginUserID()

        global $DB;
        // set default calendar and autoclose delay in root entity
        $entity = new Entity();
        $this->assertTrue($entity->update([
            'id'              => 0,
            'calendars_id'    => 1,
            'autopurge_delay' => 5,
        ]));

        $doc = new \Document();
        $did = (int) $doc->add([
            'name'   => 'test doc',
        ]);
        $this->assertGreaterThan(0, $did);

        // create some closed tickets at various solvedate
        $ticket = new Ticket();
        $tickets_id_1 = $ticket->add([
            'name'            => "test autopurge 1",
            'content'         => "test autopurge 1",
            'entities_id'     => 0,
            'status'          => CommonITILObject::CLOSED,
            '_documents_id'   => [$did],
        ]);
        $this->assertGreaterThan(0, (int) $tickets_id_1);
        $this->assertTrue(
            $DB->update('glpi_tickets', [
                'closedate' => date('Y-m-d 10:00:00', time() - 10 * DAY_TIMESTAMP),
            ], [
                'id' => $tickets_id_1,
            ])
        );
        $this->assertTrue($ticket->getFromDB($tickets_id_1));

        $docitem = new \Document_Item();
        $this->assertTrue($docitem->getFromDBByCrit(['itemtype' => 'Ticket', 'items_id' => $tickets_id_1]));

        $tickets_id_2 = $ticket->add([
            'name'        => "test autopurge 2",
            'content'     => "test autopurge 2",
            'entities_id' => 0,
            'status'      => CommonITILObject::CLOSED,
        ]);
        $this->assertGreaterThan(0, (int) $tickets_id_2);
        $this->assertTrue(
            $DB->update('glpi_tickets', [
                'closedate' => date('Y-m-d 10:00:00', time()),
            ], [
                'id' => $tickets_id_2,
            ])
        );

        /**
         * Ticket with satisfaction
         */
        // Update Entity to enable survey
        $entity = new Entity();
        $result = $entity->update([
            'id'                => 0,
            'inquest_config'    => 1,
            'inquest_rate'      => 100,
            'inquest_delay'     => 0,
        ]);
        $this->assertTrue($result);
        // Create a ticket
        $ticket = new Ticket();
        $tickets_id_3 = $ticket->add([
            'name'        => "test autopurge 3",
            'content'     => "test autopurge 3",
            'entities_id' => 0,
        ]);
        $this->assertGreaterThan(0, (int) $tickets_id_3);
        // Close ticket
        $this->assertTrue($ticket->update([
            'id' => $tickets_id_3,
            'status' => CommonITILObject::CLOSED,
        ]));
        // Set closedate to 15 days ago
        $this->assertTrue(
            $DB->update('glpi_tickets', [
                'closedate' => date('Y-m-d 10:00:00', time() - 15 * DAY_TIMESTAMP),
            ], [
                'id' => $tickets_id_3,
            ])
        );
        // Verify survey created
        $satisfaction = new TicketSatisfaction();
        $this->assertTrue($satisfaction->getFromDBByCrit(['tickets_id' => $tickets_id_3]));


        // launch Cron for closing tickets
        $mode = - CronTask::MODE_EXTERNAL; // force
        CronTask::launch($mode, 5, 'purgeticket');

        // check ticket presence
        // first ticket should have been removed
        $this->assertFalse($ticket->getFromDB($tickets_id_1));
        //also ensure linked document has been dropped
        $this->assertFalse($docitem->getFromDBByCrit(['itemtype' => 'Ticket', 'items_id' => $tickets_id_1]));
        $this->assertTrue($doc->getFromDB($did)); //document itself remains
        //second ticket is still present
        $this->assertTrue($ticket->getFromDB($tickets_id_2));
        $this->assertEquals(CommonITILObject::CLOSED, (int) $ticket->fields['status']);

        // third ticket should have been removed with its satisfaction
        $this->assertFalse($ticket->getFromDB($tickets_id_3));
        $this->assertFalse($satisfaction->getFromDBByCrit(['tickets_id' => $tickets_id_3]));
    }

    public function testMerge()
    {
        $this->login();
        $_SESSION['glpiactiveprofile']['interface'] = '';
        $this->setEntity('Root entity', true);

        $ticket = new Ticket();
        $ticket1 = $ticket->add([
            'name'        => "test merge 1",
            'content'     => "test merge 1",
            'entities_id' => 0,
            'status'      => CommonITILObject::INCOMING,
        ]);
        $ticket2 = $ticket->add([
            'name'        => "test merge 2",
            'content'     => "test merge 2",
            'entities_id' => 0,
            'status'      => CommonITILObject::INCOMING,
        ]);
        $ticket3 = $ticket->add([
            'name'        => "test merge 3",
            'content'     => "test merge 3",
            'entities_id' => 0,
            'status'      => CommonITILObject::INCOMING,
        ]);

        $task = new \TicketTask();
        $fup = new ITILFollowup();
        $task->add([
            'tickets_id'   => $ticket2,
            'content'      => 'ticket 2 task 1',
        ]);
        $task->add([
            'tickets_id'   => $ticket3,
            'content'      => 'ticket 3 task 1',
        ]);
        $fup->add([
            'itemtype'  => 'Ticket',
            'items_id'  => $ticket2,
            'content'   => 'ticket 2 fup 1',
        ]);
        $fup->add([
            'itemtype'  => 'Ticket',
            'items_id'  => $ticket3,
            'content'   => 'ticket 3 fup 1',
        ]);

        $document = new \Document();
        $documents_id = $document->add([
            'name'     => 'basic document in both',
            'filename' => 'doc.xls',
            'users_id' => '2', // user "glpi"
        ]);
        $documents_id2 = $document->add([
            'name'     => 'basic document in target',
            'filename' => 'doc.xls',
            'users_id' => '2', // user "glpi"
        ]);
        $documents_id3 = $document->add([
            'name'     => 'basic document in sources',
            'filename' => 'doc.xls',
            'users_id' => '2', // user "glpi"
        ]);

        $document_item = new \Document_Item();
        // Add document to two tickets to test merging duplicates
        $document_item->add([
            'itemtype'     => 'Ticket',
            'items_id'     => $ticket2,
            'documents_id' => $documents_id,
            'entities_id'  => '0',
            'is_recursive' => 0,
        ]);
        $document_item->add([
            'itemtype'     => 'Ticket',
            'items_id'     => $ticket1,
            'documents_id' => $documents_id,
            'entities_id'  => '0',
            'is_recursive' => 0,
        ]);
        $document_item->add([
            'itemtype'     => 'Ticket',
            'items_id'     => $ticket1,
            'documents_id' => $documents_id2,
            'entities_id'  => '0',
            'is_recursive' => 0,
        ]);
        $document_item->add([
            'itemtype'     => 'Ticket',
            'items_id'     => $ticket2,
            'documents_id' => $documents_id3,
            'entities_id'  => '0',
            'is_recursive' => 0,
        ]);
        $document_item->add([
            'itemtype'     => 'Ticket',
            'items_id'     => $ticket3,
            'documents_id' => $documents_id3,
            'entities_id'  => '0',
            'is_recursive' => 0,
        ]);

        $ticket_user = new Ticket_User();
        $ticket_user->add([
            'tickets_id'         => $ticket1,
            'type'               => Ticket_User::REQUESTER,
            'users_id'           => 2,
        ]);
        $ticket_user->add([ // Duplicate with #1
            'tickets_id'         => $ticket3,
            'type'               => Ticket_User::REQUESTER,
            'users_id'           => 2,
        ]);
        $ticket_user->add([
            'tickets_id'         => $ticket1,
            'users_id'           => 0,
            'type'               => Ticket_User::REQUESTER,
            'alternative_email'  => 'test@glpi.com',
        ]);
        $ticket_user->add([ // Duplicate with #3
            'tickets_id'         => $ticket2,
            'users_id'           => 0,
            'type'               => Ticket_User::REQUESTER,
            'alternative_email'  => 'test@glpi.com',
        ]);
        $ticket_user->add([ // Duplicate with #1
            'tickets_id'         => $ticket2,
            'users_id'           => 2,
            'type'               => Ticket_User::REQUESTER,
            'alternative_email'  => 'test@glpi.com',
        ]);
        $ticket_user->add([
            'tickets_id'         => $ticket3,
            'users_id'           => 2,
            'type'               => Ticket_User::ASSIGN,
            'alternative_email'  => 'test@glpi.com',
        ]);

        $ticket_group = new Group_Ticket();
        $ticket_group->add([
            'tickets_id'         => $ticket1,
            'groups_id'          => 1,
            'type'               => Group_Ticket::REQUESTER,
        ]);
        $ticket_group->add([ // Duplicate with #1
            'tickets_id'         => $ticket3,
            'groups_id'          => 1,
            'type'               => Group_Ticket::REQUESTER,
        ]);
        $ticket_group->add([
            'tickets_id'         => $ticket3,
            'groups_id'          => 1,
            'type'               => Group_Ticket::ASSIGN,
        ]);

        $ticket_supplier = new Supplier_Ticket();
        $ticket_supplier->add([
            'tickets_id'         => $ticket1,
            'type'               => Supplier_Ticket::REQUESTER,
            'suppliers_id'       => 2,
        ]);
        $ticket_supplier->add([ // Duplicate with #1
            'tickets_id'         => $ticket3,
            'type'               => Supplier_Ticket::REQUESTER,
            'suppliers_id'       => 2,
        ]);
        $ticket_supplier->add([
            'tickets_id'         => $ticket1,
            'suppliers_id'       => 0,
            'type'               => Supplier_Ticket::REQUESTER,
            'alternative_email'  => 'test@glpi.com',
        ]);
        $ticket_supplier->add([ // Duplicate with #3
            'tickets_id'         => $ticket2,
            'suppliers_id'       => 0,
            'type'               => Supplier_Ticket::REQUESTER,
            'alternative_email'  => 'test@glpi.com',
        ]);
        $ticket_supplier->add([ // Duplicate with #1
            'tickets_id'         => $ticket2,
            'suppliers_id'       => 2,
            'type'               => Supplier_Ticket::REQUESTER,
            'alternative_email'  => 'test@glpi.com',
        ]);
        $ticket_supplier->add([
            'tickets_id'         => $ticket3,
            'suppliers_id'       => 2,
            'type'               => Supplier_Ticket::ASSIGN,
            'alternative_email'  => 'test@glpi.com',
        ]);

        $status = [];
        $mergeparams = [
            'linktypes' => [
                'ITILFollowup',
                'TicketTask',
                'Document',
            ],
            'link_type'  => \CommonITILObject_CommonITILObject::SON_OF,
        ];

        Ticket::merge($ticket1, [$ticket2, $ticket3], $status, $mergeparams);

        $status_counts = array_count_values($status);
        $failure_count = 0;
        if (array_key_exists(1, $status_counts)) {
            $failure_count += $status_counts[1];
        }
        if (array_key_exists(2, $status_counts)) {
            $failure_count += $status_counts[2];
        }

        $this->assertEquals(0, (int) $failure_count);

        $task_count = count($task->find(['tickets_id' => $ticket1]));
        $fup_count = count($fup->find([
            'itemtype' => 'Ticket',
            'items_id' => $ticket1,
        ]));
        $doc_count = count($document_item->find([
            'itemtype' => 'Ticket',
            'items_id' => $ticket1,
        ]));
        $user_count = count($ticket_user->find([
            'tickets_id' => $ticket1,
        ]));
        $group_count = count($ticket_group->find([
            'tickets_id' => $ticket1,
        ]));
        $supplier_count = count($ticket_supplier->find([
            'tickets_id' => $ticket1,
        ]));

        // Target ticket should have all tasks
        $this->assertEquals(2, (int) $task_count);
        // Target ticket should have all followups + 1 for each source ticket description
        $this->assertEquals(4, (int) $fup_count);
        // Target ticket should have the original document, one instance of the duplicate, and the new document from one of the source tickets
        $this->assertEquals(3, (int) $doc_count);
        // Target ticket should have all users not marked as duplicates above
        $this->assertEquals(3, (int) $user_count);
        // Target ticket should have all groups not marked as duplicates above
        $this->assertEquals(2, (int) $group_count);
        // Target ticket should have all suppliers not marked as duplicates above
        $this->assertEquals(3, (int) $supplier_count);
    }

    /**
     * After a ticket has been merged (set as deleted), the responses from the child tickets should be copied to the parent ticket.
     */
    public function testResponsesAfterMerge(): void
    {
        $this->login();
        $_SESSION['glpiactiveprofile']['interface'] = '';
        $this->setEntity('Root entity', true);

        $ticket = new Ticket();
        $ticket1 = $ticket->add([
            'name'        => "Parent ticket",
            'content'     => "Parent ticket",
            'entities_id' => 0,
            'status'      => CommonITILObject::INCOMING,
        ]);
        $ticket2 = $ticket->add([
            'name'        => "Child ticket",
            'content'     => "Child ticket",
            'entities_id' => 0,
            'status'      => CommonITILObject::INCOMING,
        ]);

        $status = [];
        $mergeparams = [
            'linktypes' => [
                'ITILFollowup',
                'TicketTask',
                'Document',
            ],
            'link_type'  => \Ticket_Ticket::SON_OF,
        ];

        Ticket::merge($ticket1, [$ticket2], $status, $mergeparams);

        $status_counts = array_count_values($status);
        $failure_count = 0;
        if (array_key_exists(1, $status_counts)) {
            $failure_count += $status_counts[1];
        }
        if (array_key_exists(2, $status_counts)) {
            $failure_count += $status_counts[2];
        }

        $this->assertEquals(0, (int) $failure_count);

        // Add a followup to the child ticket
        $followup = new ITILFollowup();
        $this->assertGreaterThan(
            0,
            $followup->add([
                'itemtype'  => 'Ticket',
                'items_id'  => $ticket2,
                'content'   => 'Child ticket followup',
            ])
        );

        // Check that the followup was copied to the parent ticket
        $this->assertNotEmpty($followup->find([
            'itemtype' => 'Ticket',
            'items_id' => $ticket1,
            'sourceitems_id' => $ticket2,
            'content' => 'Child ticket followup',
        ]));

        // Add a task to the child ticket
        $task = new \TicketTask();
        $this->assertGreaterThan(
            0,
            $task->add([
                'tickets_id'   => $ticket2,
                'content'      => 'Child ticket task',
            ])
        );

        // Check that the task was copied to the parent ticket
        $this->assertNotEmpty($task->find([
            'tickets_id' => $ticket1,
            'sourceitems_id' => $ticket2,
            'content' => 'Child ticket task',
        ]));

        // Add a document to the child ticket
        $document = new \Document();
        $documents_id = $document->add([
            'name'     => 'Child ticket document',
            'filename' => 'doc.xls',
            'users_id' => '2', // user "glpi"
        ]);
        $this->assertGreaterThan(0, $documents_id);

        $document_item = new \Document_Item();
        $this->assertGreaterThan(
            0,
            $document_item->add([
                'itemtype'     => 'Ticket',
                'items_id'     => $ticket2,
                'documents_id' => $documents_id,
                'entities_id'  => '0',
                'is_recursive' => 0,
            ])
        );

        // Check that the document was copied to the parent ticket
        $this->assertNotEmpty($document_item->find([
            'itemtype' => 'Ticket',
            'items_id' => $ticket1,
            'documents_id' => $documents_id,
        ]));
    }

    public function testKeepScreenshotsOnFormReload()
    {
        //login to get session
        $auth = new \Auth();
        $this->assertTrue($auth->login(TU_USER, TU_PASS, true));

        $base64Image = base64_encode(file_get_contents(FIXTURE_DIR . '/uploads/foo.png'));

        // Test display of saved inputs from a previous submit
        $_SESSION['saveInput'][Ticket::class] = [
            'content' => '&lt;p&gt; &lt;/p&gt;&lt;p&gt;&lt;img id="3e29dffe-0237ea21-5e5e7034b1d1a1.77230247"'
         . ' src="data:image/png;base64,' . $base64Image . '" width="12" height="12" /&gt;&lt;/p&gt;',
        ];

        ob_start();
        $instance = new Ticket();
        $instance->showForm('-1');
        $output = ob_get_clean();
        $this->assertStringContainsString('src&amp;#61;&amp;#34;data:image/png;base64,' . str_replace(['+', '='], ['&amp;#43;', '&amp;#61;'], $base64Image) . '&amp;#34;', $output);
    }

    public function testScreenshotConvertedIntoDocument()
    {

        $this->login(); // must be logged as Document_Item uses Session::getLoginUserID()

        // Test uploads for item creation
        $base64Image = base64_encode(file_get_contents(FIXTURE_DIR . '/uploads/foo.png'));
        $filename = '5e5e92ffd9bd91.11111111image_paste22222222.png';
        $instance = new Ticket();
        $input = [
            'name'    => 'a ticket',
            'content' => <<<HTML
<p>Test with a ' (add)</p>
<p><img id="3e29dffe-0237ea21-5e5e7034b1d1a1.00000000" src="data:image/png;base64,{$base64Image}" width="12" height="12"></p>
HTML,
            '_filename' => [
                $filename,
            ],
            '_tag_filename' => [
                '3e29dffe-0237ea21-5e5e7034b1d1a1.00000000',
            ],
            '_prefix_filename' => [
                '5e5e92ffd9bd91.11111111',
            ],
        ];
        copy(FIXTURE_DIR . '/uploads/foo.png', GLPI_TMP_DIR . '/' . $filename);
        $instance->add($input);
        $this->assertTrue($instance->getFromDB($instance->getId()));
        $expected = 'a href="/front/document.send.php?docid=';
        $this->assertStringContainsString($expected, $instance->fields['content']);

        // Test uploads for item update
        $base64Image = base64_encode(file_get_contents(FIXTURE_DIR . '/uploads/bar.png'));
        $filename = '5e5e92ffd9bd91.44444444image_paste55555555.png';
        copy(FIXTURE_DIR . '/uploads/bar.png', GLPI_TMP_DIR . '/' . $filename);
        $instance->update([
            'id' => $instance->getID(),
            'content' => <<<HTML
<p>Test with a ' (update)</p>
<p><img id="3e29dffe-0237ea21-5e5e7034b1d1a1.33333333" src="data:image/png;base64,{$base64Image}" width="12" height="12"></p>
HTML,
            '_filename' => [
                $filename,
            ],
            '_tag_filename' => [
                '3e29dffe-0237ea21-5e5e7034b1d1a1.33333333',
            ],
            '_prefix_filename' => [
                '5e5e92ffd9bd91.44444444',
            ],
        ]);
        $this->assertTrue($instance->getFromDB($instance->getId()));
        $expected = 'a href="/front/document.send.php?docid=';
        $this->assertStringContainsString($expected, $instance->fields['content']);
    }

    public function testUploadDocuments()
    {

        $this->login(); // must be logged as Document_Item uses Session::getLoginUserID()

        // Test uploads for item creation
        $filename = '5e5e92ffd9bd91.11111111' . 'foo.txt';
        $instance = new Ticket();
        $input = [
            'name'    => 'a ticket',
            'content' => 'testUploadDocuments',
            '_filename' => [
                $filename,
            ],
            '_tag_filename' => [
                '3e29dffe-0237ea21-5e5e7034b1ffff.00000000',
            ],
            '_prefix_filename' => [
                '5e5e92ffd9bd91.11111111',
            ],
        ];
        copy(FIXTURE_DIR . '/uploads/foo.txt', GLPI_TMP_DIR . '/' . $filename);
        $instance->add($input);
        $this->assertStringContainsString('testUploadDocuments', $instance->fields['content']);
        $count = (new \DBUtils())->countElementsInTable(\Document_Item::getTable(), [
            'itemtype' => 'Ticket',
            'items_id' => $instance->getID(),
        ]);
        $this->assertEquals(1, $count);

        // Test uploads for item update (adds a 2nd document)
        $filename = '5e5e92ffd9bd91.44444444bar.txt';
        copy(FIXTURE_DIR . '/uploads/bar.txt', GLPI_TMP_DIR . '/' . $filename);
        $instance->update([
            'id' => $instance->getID(),
            'content' => 'update testUploadDocuments',
            '_filename' => [
                $filename,
            ],
            '_tag_filename' => [
                '3e29dffe-0237ea21-5e5e7034b1d1a1.33333333',
            ],
            '_prefix_filename' => [
                '5e5e92ffd9bd91.44444444',
            ],
        ]);
        $this->assertStringContainsString('update testUploadDocuments', $instance->fields['content']);
        $count = (new \DBUtils())->countElementsInTable(\Document_Item::getTable(), [
            'itemtype' => 'Ticket',
            'items_id' => $instance->getID(),
        ]);
        $this->assertEquals(2, $count);
    }

    public function testCanAddFollowupsDefaults()
    {
        $tech_id = getItemByTypeName('User', 'tech', true);
        $normal_id = getItemByTypeName('User', 'normal', true);
        $post_only_id = getItemByTypeName('User', 'post-only', true);

        $this->login();

        $ticket = new Ticket();
        $this->assertGreaterThan(
            0,
            (int) $ticket->add([
                'name'    => '',
                'content' => 'A ticket to check ACLS',
            ])
        );

        $this->assertTrue((bool) $ticket->canUserAddFollowups($tech_id));
        $this->assertFalse((bool) $ticket->canUserAddFollowups($normal_id));
        $this->assertFalse((bool) $ticket->canUserAddFollowups($post_only_id));

        $this->login('tech', 'tech');
        $this->assertTrue((bool) $ticket->canAddFollowups());
        $this->login('normal', 'normal');
        $this->assertFalse((bool) $ticket->canAddFollowups());
        $this->login('post-only', 'postonly');
        $this->assertFalse((bool) $ticket->canAddFollowups());
    }

    public function testCanAddFollowupsAsRecipient()
    {
        global $DB;

        $post_only_id = getItemByTypeName('User', 'post-only', true);

        $this->login();

        $ticket = new Ticket();
        $this->assertGreaterThan(
            0,
            (int) $ticket->add([
                'name'               => '',
                'content'            => 'A ticket to check ACLS',
                'users_id_recipient' => $post_only_id,
                '_auto_import'       => false,
            ])
        );

        // Drop all followup rights
        $DB->update(
            'glpi_profilerights',
            [
                'rights' => 0,
            ],
            [
                'profiles_id' => getItemByTypeName('Profile', 'Self-Service', true),
                'name'        => ITILFollowup::$rightname,
            ]
        );

        // Cannot add followup as user do not have ADDMY right
        $this->login();
        $this->assertFalse((bool) $ticket->canUserAddFollowups($post_only_id));
        $this->login('post-only', 'postonly');
        $this->assertFalse((bool) $ticket->canAddFollowups());

        // Add user right
        $DB->update(
            'glpi_profilerights',
            [
                'rights' => ITILFollowup::ADDMY,
            ],
            [
                'profiles_id' => getItemByTypeName('Profile', 'Self-Service', true),
                'name'        => ITILFollowup::$rightname,
            ]
        );

        // User is recipient and have ADDMY, he should be able to add followup
        $this->login();
        $this->assertTrue((bool) $ticket->canUserAddFollowups($post_only_id));
        $this->login('post-only', 'postonly');
        $this->assertTrue((bool) $ticket->canAddFollowups());
    }

    public function testCanAddFollowupsAsRequester()
    {
        global $DB;

        $post_only_id = getItemByTypeName('User', 'post-only', true);

        $this->login();

        $ticket = new Ticket();
        $this->assertGreaterThan(
            0,
            (int) $ticket->add([
                'name'    => '',
                'content' => 'A ticket to check ACLS',
            ])
        );

        // Drop all followup rights
        $DB->update(
            'glpi_profilerights',
            [
                'rights' => 0,
            ],
            [
                'profiles_id' => getItemByTypeName('Profile', 'Self-Service', true),
                'name'        => ITILFollowup::$rightname,
            ]
        );

        // Cannot add followups by default
        $this->login();
        $this->assertFalse((bool) $ticket->canUserAddFollowups($post_only_id));
        $this->login('post-only', 'postonly');
        $this->assertFalse((bool) $ticket->canAddFollowups());

        // Add user as requester
        $this->login();
        $ticket_user = new Ticket_User();
        $input_ticket_user = [
            'tickets_id' => $ticket->getID(),
            'users_id'   => $post_only_id,
            'type'       => CommonITILActor::REQUESTER,
        ];
        $this->assertGreaterThan(0, (int) $ticket_user->add($input_ticket_user));
        $this->assertTrue($ticket->getFromDB($ticket->getID())); // Reload ticket actors

        // Cannot add followup as user do not have ADDMY right
        $this->login();
        $this->assertFalse((bool) $ticket->canUserAddFollowups($post_only_id));
        $this->login('post-only', 'postonly');
        $this->assertFalse((bool) $ticket->canAddFollowups());

        // Add user right
        $DB->update(
            'glpi_profilerights',
            [
                'rights' => ITILFollowup::ADDMY,
            ],
            [
                'profiles_id' => getItemByTypeName('Profile', 'Self-Service', true),
                'name'        => ITILFollowup::$rightname,
            ]
        );

        // User is requester and have ADDMY, he should be able to add followup
        $this->login();
        $this->assertTrue((bool) $ticket->canUserAddFollowups($post_only_id));
        $this->login('post-only', 'postonly');
        $this->assertTrue((bool) $ticket->canAddFollowups());
    }

    public function testCanAddFollowupsAsRequesterGroup()
    {
        global $DB;

        $post_only_id = getItemByTypeName('User', 'post-only', true);

        $this->login();

        $ticket = new Ticket();
        $this->assertGreaterThan(
            0,
            (int) $ticket->add([
                'name'    => '',
                'content' => 'A ticket to check ACLS',
            ])
        );

        // Drop all followup rights
        $DB->update(
            'glpi_profilerights',
            [
                'rights' => 0,
            ],
            [
                'profiles_id' => getItemByTypeName('Profile', 'Self-Service', true),
                'name'        => ITILFollowup::$rightname,
            ]
        );

        // Cannot add followups by default
        $this->login();
        $this->assertFalse((bool) $ticket->canUserAddFollowups($post_only_id));
        $this->login('post-only', 'postonly');
        $this->assertFalse((bool) $ticket->canAddFollowups());

        // Add user's group as requester
        $this->login();
        $group = new Group();
        $group_id = $group->add(['name' => 'Test group']);
        $this->assertGreaterThan(0, (int) $group_id);
        $group_user = new Group_User();
        $this->assertGreaterThan(
            0,
            (int) $group_user->add([
                'groups_id' => $group_id,
                'users_id'  => $post_only_id,
            ])
        );

        $group_ticket = new Group_Ticket();
        $input_group_ticket = [
            'tickets_id' => $ticket->getID(),
            'groups_id'  => $group_id,
            'type'       => CommonITILActor::REQUESTER,
        ];
        $this->assertGreaterThan(0, (int) $group_ticket->add($input_group_ticket));
        $this->assertTrue($ticket->getFromDB($ticket->getID())); // Reload ticket actors

        // Cannot add followup as user do not have ADD_AS_GROUP right
        $this->login();
        $this->assertFalse((bool) $ticket->canUserAddFollowups($post_only_id));
        $this->login('post-only', 'postonly');
        $this->assertFalse((bool) $ticket->canAddFollowups());

        // Add user rights
        $DB->update(
            'glpi_profilerights',
            [
                'rights' => ITILFollowup::ADD_AS_GROUP,
            ],
            [
                'profiles_id' => getItemByTypeName('Profile', 'Self-Service', true),
                'name'        => ITILFollowup::$rightname,
            ]
        );

        // User is requester and have ADD_AS_GROUP bot not UPDATEMY, he shouldn't be able to add followup
        $this->login();
        $this->assertfalse((bool) $ticket->canUserAddFollowups($post_only_id));
        $this->login('post-only', 'postonly');
        $this->assertFalse((bool) $ticket->canAddFollowups());

        // Add user rights
        $DB->update(
            'glpi_profilerights',
            [
                'rights' => ITILFollowup::ADD_AS_GROUP | ITILFollowup::ADDMY,
            ],
            [
                'profiles_id' => getItemByTypeName('Profile', 'Self-Service', true),
                'name'        => ITILFollowup::$rightname,
            ]
        );

        // User is requester and have ADD_AS_GROUP & UPDATEMY, he should be able to add followup
        $this->login();
        $this->assertTrue((bool) $ticket->canUserAddFollowups($post_only_id));
        $this->login('post-only', 'postonly');
        $this->assertTrue((bool) $ticket->canAddFollowups());
    }

    public function testCanAddFollowupsAsAssigned()
    {
        global $DB;

        $post_only_id = getItemByTypeName('User', 'post-only', true);

        $this->login();

        $ticket = new Ticket();
        $this->assertGreaterThan(
            0,
            (int) $ticket->add([
                'name'    => '',
                'content' => 'A ticket to check ACLS',
            ])
        );

        // Drop all followup rights
        $DB->update(
            'glpi_profilerights',
            [
                'rights' => 0,
            ],
            [
                'profiles_id' => getItemByTypeName('Profile', 'Self-Service', true),
                'name'        => ITILFollowup::$rightname,
            ]
        );

        // Cannot add followups by default
        $this->login();
        $this->assertFalse((bool) $ticket->canUserAddFollowups($post_only_id));
        $this->login('post-only', 'postonly');
        $this->assertFalse((bool) $ticket->canAddFollowups());

        // Add user as requester
        $this->login();
        $ticket_user = new Ticket_User();
        $input_ticket_user = [
            'tickets_id' => $ticket->getID(),
            'users_id'   => $post_only_id,
            'type'       => CommonITILActor::ASSIGN,
        ];
        $this->assertGreaterThan(0, (int) $ticket_user->add($input_ticket_user));
        $this->assertTrue($ticket->getFromDB($ticket->getID())); // Reload ticket actors

        // Cant add followup as user is assigned but do not have ADD_AS_TECHNICIAN right
        $this->login();
        $this->assertFalse((bool) $ticket->canUserAddFollowups($post_only_id));
        $this->login('post-only', 'postonly');
        $this->assertFalse((bool) $ticket->canAddFollowups());

        // Add user right
        $DB->update(
            'glpi_profilerights',
            [
                'rights' => ITILFollowup::ADD_AS_TECHNICIAN,
            ],
            [
                'profiles_id' => getItemByTypeName('Profile', 'Self-Service', true),
                'name'        => ITILFollowup::$rightname,
            ]
        );

        // User is assigned and have ADD_AS_TECHNICIAN, he should be able to add followup
        $this->login();
        $this->assertTrue((bool) $ticket->canUserAddFollowups($post_only_id));
        $this->login('post-only', 'postonly');
        $this->assertTrue((bool) $ticket->canAddFollowups());
    }

    public function testCanAddFollowupsAsAssignedGroup()
    {
        global $DB;

        $post_only_id = getItemByTypeName('User', 'post-only', true);

        $this->login();

        $ticket = new Ticket();
        $this->assertGreaterThan(
            0,
            (int) $ticket->add([
                'name'    => '',
                'content' => 'A ticket to check ACLS',
            ])
        );

        // Drop all followup rights
        $DB->update(
            'glpi_profilerights',
            [
                'rights' => 0,
            ],
            [
                'profiles_id' => getItemByTypeName('Profile', 'Self-Service', true),
                'name'        => ITILFollowup::$rightname,
            ]
        );

        // Cannot add followups by default
        $this->login();
        $this->assertFalse((bool) $ticket->canUserAddFollowups($post_only_id));
        $this->login('post-only', 'postonly');
        $this->assertFalse((bool) $ticket->canAddFollowups());

        // Add user's group as requester
        $this->login();
        $group = new Group();
        $group_id = $group->add(['name' => 'Test group']);
        $this->assertGreaterThan(0, (int) $group_id);
        $group_user = new Group_User();
        $this->assertGreaterThan(
            0,
            (int) $group_user->add([
                'groups_id' => $group_id,
                'users_id'  => $post_only_id,
            ])
        );

        $group_ticket = new Group_Ticket();
        $input_group_ticket = [
            'tickets_id' => $ticket->getID(),
            'groups_id'  => $group_id,
            'type'       => CommonITILActor::ASSIGN,
        ];
        $this->assertGreaterThan(0, (int) $group_ticket->add($input_group_ticket));
        $this->assertTrue($ticket->getFromDB($ticket->getID())); // Reload ticket actors

        // Cant add followup as user is assigned but do not have ADD_AS_TECHNICIAN right
        $this->login();
        $this->assertFalse((bool) $ticket->canUserAddFollowups($post_only_id));
        $this->login('post-only', 'postonly');
        $this->assertFalse((bool) $ticket->canAddFollowups());

        // Add user right
        $DB->update(
            'glpi_profilerights',
            [
                'rights' => ITILFollowup::ADD_AS_TECHNICIAN,
            ],
            [
                'profiles_id' => getItemByTypeName('Profile', 'Self-Service', true),
                'name'        => ITILFollowup::$rightname,
            ]
        );

        // User is assigned and have ADD_AS_TECHNICIAN, he should be able to add followup
        $this->login();
        $this->assertTrue((bool) $ticket->canUserAddFollowups($post_only_id));
        $this->login('post-only', 'postonly');
        $this->assertTrue((bool) $ticket->canAddFollowups());
    }

    public function testCanAddFollowupsAsObserver()
    {
        global $DB;

        $post_only_id = getItemByTypeName('User', 'post-only', true);

        $this->login();

        $ticket = new Ticket();
        $this->assertGreaterThan(
            0,
            (int) $ticket->add([
                'name'    => '',
                'content' => 'A ticket to check ACLS',
            ])
        );

        // Cannot add followups by default
        $this->login();
        $this->assertFalse((bool) $ticket->canUserAddFollowups($post_only_id));
        $this->login('post-only', 'postonly');
        $this->assertFalse((bool) $ticket->canAddFollowups());

        // Add user as observer
        $this->login();
        $ticket_user = new Ticket_User();
        $input_ticket_user = [
            'tickets_id' => $ticket->getID(),
            'users_id'   => $post_only_id,
            'type'       => CommonITILActor::OBSERVER,
        ];
        $this->assertGreaterThan(0, (int) $ticket_user->add($input_ticket_user));
        $this->assertTrue($ticket->getFromDB($ticket->getID())); // Reload ticket actors

        // Cannot add followup as user do not have ADD_AS_OBSERVER right
        $this->login();
        $this->assertFalse((bool) $ticket->canUserAddFollowups($post_only_id));
        $this->login('post-only', 'postonly');
        $this->assertFalse((bool) $ticket->canAddFollowups());

        // Add user right
        $DB->update(
            'glpi_profilerights',
            [
                'rights' => ITILFollowup::ADD_AS_OBSERVER,
            ],
            [
                'profiles_id' => getItemByTypeName('Profile', 'Self-Service', true),
                'name'        => ITILFollowup::$rightname,
            ]
        );

        // User is observer and have ADD_AS_OBSERVER, he should be able to add followup
        $this->login();
        $this->assertTrue((bool) $ticket->canUserAddFollowups($post_only_id));
        $this->login('post-only', 'postonly');
        $this->assertTrue((bool) $ticket->canAddFollowups());

        // Remove user as observer
        $this->assertGreaterThan(0, (int) $ticket_user->deleteByCriteria([
            'tickets_id' => $ticket->getID(),
            'users_id'   => $post_only_id,
            'type'       => CommonITILActor::OBSERVER,
        ]));
        $this->assertTrue($ticket->getFromDB($ticket->getID())); // Reload ticket actors

        // Add user to a group and assign the group as observer
        $group = new Group();
        $group_id = $group->add(['name' => 'Test group']);
        $this->assertGreaterThan(0, (int) $group_id);

        $group_user = new Group_User();
        $this->assertGreaterThan(
            0,
            (int) $group_user->add([
                'groups_id' => $group_id,
                'users_id'  => $post_only_id,
            ])
        );

        $group_ticket = new Group_Ticket();
        $input_group_ticket = [
            'tickets_id' => $ticket->getID(),
            'groups_id'  => $group_id,
            'type'       => CommonITILActor::OBSERVER,
        ];
        $this->assertGreaterThan(0, (int) $group_ticket->add($input_group_ticket));
        $this->assertTrue($ticket->getFromDB($ticket->getID())); // Reload ticket actors

        // User is in a group that is observer and has ADD_AS_OBSERVER rights but not ADD_AS_GROUP
        $this->login();
        $this->assertFalse((bool) $ticket->canUserAddFollowups($post_only_id));
        $this->login('post-only', 'postonly');
        $this->assertFalse((bool) $ticket->canAddFollowups());

        // Add user right
        $DB->update(
            'glpi_profilerights',
            [
                'rights' => ITILFollowup::ADD_AS_OBSERVER | ITILFollowup::ADD_AS_GROUP,
            ],
            [
                'profiles_id' => getItemByTypeName('Profile', 'Self-Service', true),
                'name'        => ITILFollowup::$rightname,
            ]
        );

        // User is observer and have ADD_AS_OBSERVER & ADD_AS_GROUP, he should be able to add followup
        $this->login();
        $this->assertTrue((bool) $ticket->canUserAddFollowups($post_only_id));
        $this->login('post-only', 'postonly');
        $this->assertTrue((bool) $ticket->canAddFollowups());
    }

    public static function convertContentForTicketProvider(): iterable
    {
        yield [
            'content'  => '',
            'files'    => [],
            'tags'     => [],
            'expected' => '',
        ];

        foreach (['"', "'", ''] as $quote_style) {
            // `img` of embedded image that has only a `src` attribute.
            yield [
                'content'  => <<<HTML
Here is the screenshot:
<img src={$quote_style}screenshot.png{$quote_style}>
blabla
HTML,
                'files'    => [
                    'screenshot.png' => 'screenshot.png',
                ],
                'tags'     => [
                    'screenshot.png' => '9faff0a6-f37490bd-60e2af9721f420.96500246',
                ],
                'expected' => <<<HTML
Here is the screenshot:
<p>#9faff0a6-f37490bd-60e2af9721f420.96500246#</p>
blabla
HTML,
            ];
            // `img` of embedded image that has multiple attributes.
            yield [
                'content'  => <<<HTML
Here is the screenshot:
<img id="img-id" src={$quote_style}screenshot.png{$quote_style} height="150" width="100" />
blabla
HTML,
                'files'    => [
                    'screenshot.png' => 'screenshot.png',
                ],
                'tags'     => [
                    'screenshot.png' => '9faff0a6-f37490bd-60e2af9721f420.96500246',
                ],
                'expected' => <<<HTML
Here is the screenshot:
<p>#9faff0a6-f37490bd-60e2af9721f420.96500246#</p>
blabla
HTML,
            ];

            // Content with leading external image that will not be replaced by a tag.
            yield [
                'content'  => <<<HTML
<img src={$quote_style}http://test.glpi-project.org/logo.png{$quote_style} />
Here is the screenshot:
<img src={$quote_style}img.jpg{$quote_style} />
blabla
HTML,
                'files'    => [
                    'img.jpg' => 'img.jpg',
                ],
                'tags'     => [
                    'img.jpg' => '3eaff0a6-f37490bd-60e2a59721f420.96500246',
                ],
                'expected' => <<<HTML
<img src={$quote_style}http://test.glpi-project.org/logo.png{$quote_style} />
Here is the screenshot:
<p>#3eaff0a6-f37490bd-60e2a59721f420.96500246#</p>
blabla
HTML,
            ];
        }
    }

    #[DataProvider('convertContentForTicketProvider')]
    public function testConvertContentForTicket(string $content, array $files, array $tags, string $expected)
    {
        $instance = new Ticket();
        $this->assertEquals($expected, $instance->convertContentForTicket($content, $files, $tags));
    }

    public function testGetTeamRoles(): void
    {
        $roles = Ticket::getTeamRoles();
        $this->assertContains(CommonITILActor::ASSIGN, $roles);
        $this->assertContains(CommonITILActor::OBSERVER, $roles);
        $this->assertContains(CommonITILActor::REQUESTER, $roles);
    }

    public function testGetTeamRoleName(): void
    {
        $roles = Ticket::getTeamRoles();
        foreach ($roles as $role) {
            $this->assertNotEmpty(Ticket::getTeamRoleName($role));
        }
    }

    /**
     * Tests addTeamMember, deleteTeamMember, and getTeamMembers methods
     */
    public function testTeamManagement(): void
    {

        $ticket = new Ticket();

        $tickets_id = $ticket->add([
            'name'      => 'Team test',
            'content'   => 'Team test',
        ]);
        $this->assertGreaterThan(0, $tickets_id);

        $this->assertEmpty($ticket->getTeam());

        // Add team members
        $this->assertTrue($ticket->addTeamMember(User::class, 4, ['role' => Team::ROLE_ASSIGNED])); // using constant value
        $this->assertTrue($ticket->addTeamMember(User::class, 2, ['role' => 'observer'])); // using CommonITILActor contant name

        // Reload ticket from DB
        $this->assertTrue($ticket->getFromDB($tickets_id));

        // Check team members
        $team = $ticket->getTeam();
        $this->assertCount(2, $team);
        $member = array_shift($team);
        $this->assertArrayHasKey('itemtype', $member);
        $this->assertArrayHasKey('items_id', $member);
        $this->assertArrayHasKey('role', $member);
        $this->assertEquals(User::class, $member['itemtype']);
        $this->assertEquals(2, $member['items_id']);
        $this->assertEquals(Team::ROLE_OBSERVER, $member['role']);
        $member = array_shift($team);
        $this->assertArrayHasKey('itemtype', $member);
        $this->assertArrayHasKey('items_id', $member);
        $this->assertArrayHasKey('role', $member);
        $this->assertEquals(User::class, $member['itemtype']);
        $this->assertEquals(4, $member['items_id']);
        $this->assertEquals(Team::ROLE_ASSIGNED, $member['role']);

        // Delete team member
        $this->assertTrue($ticket->deleteTeamMember(User::class, 4, ['role' => Team::ROLE_ASSIGNED]));

        //Reload ticket from DB
        $this->assertTrue($ticket->getFromDB($tickets_id));

        // Check team members
        $team = $ticket->getTeam();
        $this->assertCount(1, $team);
        $member = array_shift($team);
        $this->assertArrayHasKey('itemtype', $member);
        $this->assertArrayHasKey('items_id', $member);
        $this->assertArrayHasKey('role', $member);
        $this->assertEquals(User::class, $member['itemtype']);
        $this->assertEquals(2, $member['items_id']);
        $this->assertEquals(Team::ROLE_OBSERVER, $member['role']);

        // Delete team member
        $this->assertTrue($ticket->deleteTeamMember(User::class, 2, ['role' => Team::ROLE_OBSERVER]));

        //Reload ticket from DB
        $this->assertTrue($ticket->getFromDB($tickets_id));

        // Check team members
        $this->assertEmpty($team);

        // Add team members
        $this->assertTrue($ticket->addTeamMember(Group::class, 2, ['role' => Team::ROLE_ASSIGNED]));

        // Reload ticket from DB
        $this->assertTrue($ticket->getFromDB($tickets_id));

        // Check team members
        $team = $ticket->getTeam();
        $this->assertCount(1, $team);
        $this->assertArrayHasKey('itemtype', $team[0]);
        $this->assertArrayHasKey('items_id', $team[0]);
        $this->assertArrayHasKey('role', $team[0]);
        $this->assertEquals(Group::class, $team[0]['itemtype']);
        $this->assertEquals(2, $team[0]['items_id']);
        $this->assertEquals(Team::ROLE_ASSIGNED, $team[0]['role']);
    }

    public function testGetTeamWithInvalidData(): void
    {
        global $DB;

        $this->login();

        $user_id = getItemByTypeName(User::class, TU_USER, true);

        $ticket = $this->createItem(
            Ticket::class,
            [
                'name'             => __FUNCTION__,
                'content'          => __FUNCTION__,
                'entities_id'      => $this->getTestRootEntity(true),
                '_users_id_assign' => $user_id,
            ]
        );

        $this->assertCount(1, $ticket->getTeam()); // TU_USER as assignee

        // Create invalid entries
        foreach ([CommonITILActor::REQUESTER, CommonITILActor::OBSERVER, CommonITILActor::ASSIGN] as $role) {
            $this->assertTrue(
                $DB->insert(
                    Ticket_User::getTable(),
                    [
                        'tickets_id' => $ticket->getID(),
                        'users_id'   => 978897, // not a valid id
                        'type'       => $role,
                    ]
                )
            );
            $this->assertTrue(
                $DB->insert(
                    Group_Ticket::getTable(),
                    [
                        'tickets_id' => $ticket->getID(),
                        'groups_id'  => 46543, // not a valid id
                        'type'       => $role,
                    ]
                )
            );
            $this->assertTrue(
                $DB->insert(
                    Supplier_Ticket::getTable(),
                    [
                        'tickets_id'   => $ticket->getID(),
                        'suppliers_id' => 99999, // not a valid id
                        'type'         => $role,
                    ]
                )
            );
        }

        $this->assertTrue($ticket->getFromDB($ticket->getID()));

        // Does not contains invalid entries
        $this->assertCount(1, $ticket->getTeam()); // TU_USER as assignee

        // Check team in global Kanban
        $kanban = Ticket::getDataToDisplayOnKanban(-1);
        $kanban_ticket = array_pop($kanban); // checked ticket is the last created
        $this->assertArrayHasKey('id', $kanban_ticket);
        $this->assertArrayHasKey('_itemtype', $kanban_ticket);
        $this->assertArrayHasKey('_team', $kanban_ticket);
        $this->assertEquals($ticket->getID(), $kanban_ticket['id']);
        $this->assertEquals(Ticket::class, $kanban_ticket['_itemtype']);
        $this->assertEquals(
            [
                [
                    'itemtype'  => User::class,
                    'id'        => $user_id,
                    'firstname' => null,
                    'realname'  => null,
                    'name'      => '_test_user',
                ],
            ],
            $kanban_ticket['_team']
        );
    }

    protected function testUpdateLoad1NTableDataProvider(): \Generator
    {
        // Build test data
        $ticket = $this->createItem('Ticket', [
            'name'    => 'testUpdate1NTableData ticket',
            'content' => 'testUpdate1NTableData ticket',
        ]);

        // Build test params
        $user1 = getItemByTypeName('User', 'glpi', true);
        $user2 = getItemByTypeName('User', 'tech', true);
        $user3 = getItemByTypeName('User', 'post-only', true);
        $user4 = getItemByTypeName('User', 'normal', true);

        $tickets_base_params = [
            'item'              => $ticket,
            'commondb_relation' => Ticket_User::class,
            'field'             => 'assigned_users',
            'extra_input'       => ['type' => CommonITILActor::ASSIGN],
        ];

        // Add two users
        $ticket->input = [
            'id' => $ticket->getID(),
            'assigned_users' => [$user1, $user2],
        ];
        yield $tickets_base_params;

        // Remove one user
        $ticket->input = [
            'id' => $ticket->getID(),
            'assigned_users' => [$user1],
        ];
        yield $tickets_base_params;

        // Add one user
        $ticket->input = [
            'id' => $ticket->getID(),
            'assigned_users' => [$user1, $user3],
        ];
        yield $tickets_base_params;

        // Change both users
        $ticket->input = [
            'id' => $ticket->getID(),
            'assigned_users' => [$user2, $user4],
        ];
        yield $tickets_base_params;

        // Remove all users
        $ticket->input = [
            'id' => $ticket->getID(),
            'assigned_users' => [],
        ];
        yield $tickets_base_params;

        // Try from the opposite side of the relation
        $user = getItemByTypeName('User', 'glpi');

        // Build test data
        $this->createItems('Ticket', [
            [
                'name'    => 'testUpdate1NTableData1',
                'content' => 'testUpdate1NTableData1',
            ],
            [
                'name'    => 'testUpdate1NTableData2',
                'content' => 'testUpdate1NTableData2',
            ],
            [
                'name'    => 'testUpdate1NTableData3',
                'content' => 'testUpdate1NTableData3',
            ],
            [
                'name'    => 'testUpdate1NTableData4',
                'content' => 'testUpdate1NTableData4',
            ],
        ]);
        $ticket1 = getItemByTypeName('Ticket', 'testUpdate1NTableData1', true);
        $ticket2 = getItemByTypeName('Ticket', 'testUpdate1NTableData2', true);
        $ticket3 = getItemByTypeName('Ticket', 'testUpdate1NTableData3', true);
        $ticket4 = getItemByTypeName('Ticket', 'testUpdate1NTableData4', true);

        $user_base_params = [
            'item'              => $user,
            'commondb_relation' => Ticket_User::class,
            'field'             => 'linked_tickets',
            'extra_input'       => ['type' => CommonITILActor::ASSIGN],
        ];

        // Add two tickets
        $user->input = [
            'id' => $user->getID(),
            'linked_tickets' => [$ticket1, $ticket2],
        ];
        yield $user_base_params;

        // Remove one ticket
        $user->input = [
            'id' => $user->getID(),
            'linked_tickets' => [$ticket1],
        ];
        yield $user_base_params;

        // Add one tickett
        $user->input = [
            'id' => $user->getID(),
            'linked_tickets' => [$ticket1, $ticket3],
        ];
        yield $user_base_params;

        // Change both tickets
        $user->input = [
            'id' => $user->getID(),
            'linked_tickets' => [$ticket2, $ticket4],
        ];
        yield $user_base_params;

        // Remove all tickets
        $user->input = [
            'id' => $user->getID(),
            'linked_tickets' => [],
        ];
        yield $user_base_params;
    }

    /**
     * Functional tests for update1NTableData and load1NTableData
     */
    public function testUpdateLoad1NTableData(): void
    {
        $provider = $this->testUpdateLoad1NTableDataProvider();
        foreach ($provider as $row) {
            $item = $row['item'];
            $commondb_relation = $row['commondb_relation'];
            $field = $row['field'];
            $extra_input = $row['extra_input'];

            // Keep track of the linked items
            $linked = $item->input[$field];
            $this->assertIsArray($linked);

            // Update DB
            $this->callPrivateMethod($item, 'update1NTableData', $commondb_relation, $field, $extra_input);

            // Load values
            $this->callPrivateMethod($item, 'load1NTableData', $commondb_relation, $field, $extra_input);

            // Compare values
            $this->assertEquals($linked, $item->fields[$field]);
        }
    }

    public function testNewToSolvedUnassigned()
    {
        $this->login();
        // Create ticket without automatic assignment
        $ticket = new Ticket();
        $tickets_id = $ticket->add([
            'name' => 'testNewToSolvedUnassigned',
            'content' => 'testNewToSolvedUnassigned',
            '_skip_auto_assign' => true,
        ]);
        $this->assertGreaterThan(0, $tickets_id);

        // Check ticket status is new
        $this->assertTrue($ticket->getFromDB($tickets_id));
        $this->assertEquals(0, $ticket->countUsers(CommonITILActor::ASSIGN));
        $this->assertEquals(CommonITILObject::INCOMING, $ticket->fields['status']);

        // Set status to solved
        $this->assertTrue($ticket->update([
            'id' => $tickets_id,
            'status' => CommonITILObject::SOLVED,
            '_skip_auto_assign' => true,
        ]));

        // Check ticket status is solved
        $this->assertTrue($ticket->getFromDB($tickets_id));
        $this->assertEquals(CommonITILObject::SOLVED, $ticket->fields['status']);

        // Set status to new
        $this->assertTrue($ticket->update([
            'id' => $tickets_id,
            'status' => CommonITILObject::INCOMING,
            '_skip_auto_assign' => true,
        ]));

        // Check ticket status is new
        $this->assertTrue($ticket->getFromDB($tickets_id));
        $this->assertEquals(CommonITILObject::INCOMING, $ticket->fields['status']);

        // Set status to closed
        $this->assertTrue($ticket->update([
            'id' => $tickets_id,
            'status' => CommonITILObject::CLOSED,
            '_skip_auto_assign' => true,
        ]));

        // Check ticket status is closed
        $this->assertTrue($ticket->getFromDB($tickets_id));
        $this->assertEquals(CommonITILObject::CLOSED, $ticket->fields['status']);
    }

    public function testSurveyCreation()
    {
        global $DB;

        $this->login();
        // Create ticket
        $ticket = new Ticket();
        $tickets_id = $ticket->add([
            'name' => 'testSurveyCreation',
            'content' => 'testSurveyCreation',
        ]);
        $this->assertGreaterThan(0, $tickets_id);

        $entities_id = $ticket->fields['entities_id'];
        // Update Entity to enable survey
        $entity = new Entity();
        $result = $entity->update([
            'id'                => $entities_id,
            'inquest_config'    => 1,
            'inquest_rate'      => 100,
            'inquest_delay'     => 0,
        ]);
        $this->assertTrue($result);

        $inquest = new TicketSatisfaction();

        // Verify no existing survey for ticket
        $it = $DB->request([
            'SELECT' => ['id'],
            'FROM' => TicketSatisfaction::getTable(),
            'WHERE' => [
                'tickets_id' => $tickets_id,
            ],
        ]);
        $this->assertEquals(0, $it->count());

        // Close ticket
        $this->assertTrue($ticket->update([
            'id' => $tickets_id,
            'status' => CommonITILObject::CLOSED,
        ]));

        // Verify survey created
        $it = $DB->request([
            'SELECT' => ['id'],
            'FROM' => TicketSatisfaction::getTable(),
            'WHERE' => [
                'tickets_id' => $tickets_id,
            ],
        ]);
        $this->assertEquals(1, $it->count());
    }

    public function testSurveyCreationOnReopened()
    {
        global $DB;

        $this->login();
        // Create ticket
        $ticket = new Ticket();
        $tickets_id = $ticket->add([
            'name' => 'testSurveyCreation',
            'content' => 'testSurveyCreation',
        ]);
        $this->assertGreaterThan(0, $tickets_id);

        $entities_id = $ticket->fields['entities_id'];
        // Update Entity to enable survey
        $entity = new Entity();
        $result = $entity->update([
            'id' => $entities_id,
            'inquest_config' => 1,
            'inquest_rate' => 100,
            'inquest_delay' => 0,
        ]);
        $this->assertTrue($result);

        $inquest = new TicketSatisfaction();

        // Verify no existing survey for ticket
        $it = $DB->request([
            'SELECT' => ['id'],
            'FROM' => TicketSatisfaction::getTable(),
            'WHERE' => [
                'tickets_id' => $tickets_id,
            ],
        ]);
        $this->assertEquals(0, $it->count());

        // Close ticket
        $this->assertTrue($ticket->update([
            'id' => $tickets_id,
            'status' => CommonITILObject::CLOSED,
        ]));

        // Reopen ticket
        $this->assertTrue($ticket->update([
            'id' => $tickets_id,
            'status' => CommonITILObject::INCOMING,
        ]));

        $result = $entity->update([
            'id' => $entities_id,
            'inquest_config' => 1,
            'inquest_rate' => 100,
            'inquest_delay' => 0,
        ]);
        $this->assertTrue($result);

        // Re-close ticket
        $this->assertTrue($ticket->update([
            'id' => $tickets_id,
            'status' => CommonITILObject::CLOSED,
        ]));

        // Verify survey created and only one exists
        $it = $DB->request([
            'SELECT' => ['id'],
            'FROM' => TicketSatisfaction::getTable(),
            'WHERE' => [
                'tickets_id' => $tickets_id,
            ],
        ]);
        $this->assertEquals(1, $it->count());
    }

    public function testCronSurveyCreation(): void
    {
        $this->login();

        $root_entity_id    = $this->getTestRootEntity(true);
        $child_1_entity_id = getItemByTypeName('Entity', '_test_child_1', true);
        $child_2_entity_id = getItemByTypeName('Entity', '_test_child_2', true);

        $now              = Session::getCurrentTime();
        $twelve_hours_ago = date("Y-m-d H:i:s", strtotime('-12 hours'));
        $six_hours_ago    = date("Y-m-d H:i:s", strtotime('-4 hours'));
        $four_hours_ago   = date("Y-m-d H:i:s", strtotime('-4 hours'));
        $two_hours_ago    = date("Y-m-d H:i:s", strtotime('-2 hours'));

        $this->updateItem(
            Entity::class,
            0,
            [
                'inquest_config' => 1, // GLPI native survey
                'inquest_rate'   => 100, // always generate a survey for closed tickets
                'inquest_delay'  => 0, // instant survey generation
            ]
        );
        $this->updateItem(
            Entity::class,
            $root_entity_id,
            [
                'inquest_config' => Entity::CONFIG_PARENT, // inherits
            ]
        );
        $this->updateItem(
            Entity::class,
            $child_1_entity_id,
            [
                'inquest_config' => Entity::CONFIG_PARENT, // inherits
            ]
        );
        $this->updateItem(
            Entity::class,
            $child_2_entity_id,
            [
                'inquest_config' => 1, // GLPI native survey
                'inquest_rate'   => 100, // always generate a survey for closed tickets
                'inquest_delay'  => 0, // instant survey generation
            ]
        );

        foreach ([0, $root_entity_id, $child_1_entity_id, $child_2_entity_id] as $entity_id) {
            // Ensure `max_closedate` is in the past
            $this->updateItem(
                Entity::class,
                $entity_id,
                [
                    'max_closedate'  => $twelve_hours_ago,
                ]
            );
        }

        // Create a closed ticket on test root entity
        $_SESSION['glpi_currenttime'] = $six_hours_ago;
        $root_ticket = $this->createItem(
            Ticket::class,
            [
                'name'        => "test root entity survey",
                'content'     => "test root entity survey",
                'entities_id' => $root_entity_id,
                'status'      => CommonITILObject::CLOSED,
            ]
        );

        // Create a closed ticket on test child entity 1
        $_SESSION['glpi_currenttime'] = $four_hours_ago;
        $child_1_ticket = $this->createItem(
            Ticket::class,
            [
                'name'        => "test child entity 1 survey",
                'content'     => "test child entity 1 survey",
                'entities_id' => $child_1_entity_id,
                'status'      => CommonITILObject::CLOSED,
            ]
        );

        // Create a closed ticket on test child entity 2
        $_SESSION['glpi_currenttime'] = $two_hours_ago;
        $child_1_ticket = $this->createItem(
            Ticket::class,
            [
                'name'        => "test child entity 2 survey",
                'content'     => "test child entity 2 survey",
                'entities_id' => $child_2_entity_id,
                'status'      => CommonITILObject::CLOSED,
            ]
        );

        // Ensure no survey has been created yet
        $ticket_satisfaction = new TicketSatisfaction();
        $this->assertEquals(0, count($ticket_satisfaction->find(['tickets_id' => $root_ticket->getID()])));
        $this->assertEquals(0, count($ticket_satisfaction->find(['tickets_id' => $child_1_ticket->getID()])));

        // Launch cron to create surveys
        CronTask::launch(
            - CronTask::MODE_INTERNAL, // force
            1,
            'createinquest'
        );

        // Ensure survey has been created
        $ticket_satisfaction = new TicketSatisfaction();
        $this->assertEquals(1, count($ticket_satisfaction->find(['tickets_id' => $root_ticket->getID()])));
        $this->assertEquals(1, count($ticket_satisfaction->find(['tickets_id' => $child_1_ticket->getID()])));

        // Check `max_closedate` values in DB
        $expected_db_values = [
            0                  => $four_hours_ago,   // last ticket closedate from entities that inherits the config
            $root_entity_id    => $twelve_hours_ago, // not updated as it inherits the config
            $child_1_entity_id => $twelve_hours_ago, // not updated as it inherits the config
            $child_2_entity_id => $two_hours_ago,    // last ticket closedate from self as it has its own config
        ];
        foreach ($expected_db_values as $entity_id => $date) {
            $entity = new Entity();
            $this->assertTrue($entity->getFromDB($entity_id));
            $this->assertEquals($date, $entity->fields['max_closedate']);
        }

        // Check `max_closedate` returned by `Entity::getUsedConfig()`
        $expected_config_values = [
            0                  => $four_hours_ago, // last ticket closedate from entities that inherits the config
            $root_entity_id    => $four_hours_ago, // inherited value
            $child_1_entity_id => $four_hours_ago, // inherited value
            $child_2_entity_id => $two_hours_ago,  // last ticket closedate from self as it has its own config
        ];
        foreach ($expected_config_values as $entity_id => $date) {
            $this->assertEquals($date, Entity::getUsedConfig('inquest_config', $entity_id, 'max_closedate'));
        }
    }

    public function testAddAssignWithoutUpdateRight()
    {
        $this->login();

        $ticket = new Ticket();
        $tickets_id = $ticket->add([
            'name' => 'testAddAssignWithoutUpdateRight',
            'content' => 'testAddAssignWithoutUpdateRight',
            '_skip_auto_assign' => true,
        ]);
        $this->assertGreaterThan(0, $tickets_id);

        $ticket->loadActors();
        $this->assertEquals(0, $ticket->countUsers(CommonITILActor::ASSIGN));
        $this->assertEquals(0, $ticket->countUsers(CommonITILActor::REQUESTER));

        $this->changeTechRight(Ticket::ASSIGN | Ticket::READALL);
        $this->assertFalse($ticket->canUpdateItem());
        $this->assertTrue((bool) $ticket->canAssign());
        $this->assertTrue($ticket->update([
            'id' => $tickets_id,
            '_actors' => [
                'requester' => [
                    [
                        'itemtype'  => 'User',
                        'items_id'  => getItemByTypeName('User', 'post-only', true),
                        'use_notification' => 0,
                        'alternative_email' => '',
                    ],
                    [
                        'itemtype'  => 'User',
                        'items_id'  => getItemByTypeName('User', 'tech', true),
                        'use_notification' => 0,
                        'alternative_email' => '',
                    ],
                ],
                'assign' => [
                    [
                        'itemtype'  => 'User',
                        'items_id'  => getItemByTypeName('User', 'tech', true),
                        'use_notification' => 0,
                        'alternative_email' => '',
                    ],
                ],
            ],
        ]));
        $ticket->loadActors();
        // Verify new assignee was added
        $this->assertEquals(1, $ticket->countUsers(CommonITILActor::ASSIGN));
        // Verify new requester wasn't added
        $this->assertEquals(0, $ticket->countUsers(CommonITILActor::REQUESTER));
    }

    public function testAddAssignWithoutAssignRight()
    {
        $this->login();

        $ticket = new Ticket();
        $tickets_id = $ticket->add([
            'name' => 'testAddAssignWithoutAssignRight',
            'content' => 'testAddAssignWithoutAssignRight',
            '_skip_auto_assign' => true,
        ]);
        $this->assertGreaterThan(0, $tickets_id);

        $ticket->loadActors();
        $this->assertEquals(0, $ticket->countUsers(CommonITILActor::ASSIGN));
        $this->assertEquals(0, $ticket->countUsers(CommonITILActor::REQUESTER));

        $this->changeTechRight(Ticket::READALL | UPDATE);
        $this->assertTrue($ticket->canUpdateItem());
        $this->assertFalse((bool) $ticket->canAssign());
        $this->assertTrue($ticket->update([
            'id' => $tickets_id,
            '_actors' => [
                'requester' => [
                    [
                        'itemtype'  => 'User',
                        'items_id'  => getItemByTypeName('User', 'post-only', true),
                        'use_notification' => 0,
                        'alternative_email' => '',
                    ],
                    [
                        'itemtype'  => 'User',
                        'items_id'  => getItemByTypeName('User', 'tech', true),
                        'use_notification' => 0,
                        'alternative_email' => '',
                    ],
                ],
                'assign' => [
                    [
                        'itemtype'  => 'User',
                        'items_id'  => getItemByTypeName('User', 'tech', true),
                        'use_notification' => 0,
                        'alternative_email' => '',
                    ],
                ],
            ],
        ]));
        $ticket->loadActors();
        // Verify new assignee wasn't added
        $this->assertEquals(0, $ticket->countUsers(CommonITILActor::ASSIGN));
        // Verify new requester was added
        $this->assertEquals(2, $ticket->countUsers(CommonITILActor::REQUESTER));
    }

    public function testAddActorsWithAssignAndUpdateRight()
    {
        $this->login();

        $ticket = new Ticket();
        $tickets_id = $ticket->add([
            'name' => 'testAddActorsWithAssignAndUpdateRight',
            'content' => 'testAddActorsWithAssignAndUpdateRight',
            '_skip_auto_assign' => true,
        ]);
        $this->assertGreaterThan(0, $tickets_id);

        $ticket->loadActors();
        $this->assertEquals(0, $ticket->countUsers(CommonITILActor::ASSIGN));
        $this->assertEquals(0, $ticket->countUsers(CommonITILActor::REQUESTER));

        $this->changeTechRight(Ticket::ASSIGN | UPDATE | Ticket::READALL);
        $this->assertTrue($ticket->canUpdateItem());
        $this->assertTrue((bool) $ticket->canAssign());
        $this->assertTrue($ticket->update([
            'id' => $tickets_id,
            '_actors' => [
                'requester' => [
                    [
                        'itemtype'  => 'User',
                        'items_id'  => getItemByTypeName('User', 'post-only', true),
                        'use_notification' => 0,
                        'alternative_email' => '',
                    ],
                    [
                        'itemtype'  => 'User',
                        'items_id'  => getItemByTypeName('User', 'tech', true),
                        'use_notification' => 0,
                        'alternative_email' => '',
                    ],
                ],
                'assign' => [
                    [
                        'itemtype'  => 'User',
                        'items_id'  => getItemByTypeName('User', 'tech', true),
                        'use_notification' => 0,
                        'alternative_email' => '',
                    ],
                ],
            ],
        ]));
        $ticket->loadActors();
        // Verify new assignee was added
        $this->assertEquals(1, $ticket->countUsers(CommonITILActor::ASSIGN));
        // Verify new requester was added
        $this->assertEquals(2, $ticket->countUsers(CommonITILActor::REQUESTER));
    }


    public function testGetActorsForType()
    {
        $this->login();

        $ticket = new Ticket();
        $ticket->getEmpty();

        $tech_id = getItemByTypeName('User', 'tech', true);
        $postonly_id = getItemByTypeName('User', 'post-only', true);

        // ## 1st - test auto requester and assign feature
        // ###############################################

        $this->assertCount(1, $ticket->getActorsForType(CommonITILActor::REQUESTER));
        $this->assertCount(0, $ticket->getActorsForType(CommonITILActor::OBSERVER));
        $this->assertCount(1, $ticket->getActorsForType(CommonITILActor::ASSIGN));

        // disable autoactor by parameter
        $params = ['_skip_default_actor' => true];
        $this->assertCount(0, $ticket->getActorsForType(CommonITILActor::REQUESTER, $params));
        $this->assertCount(0, $ticket->getActorsForType(CommonITILActor::OBSERVER, $params));
        $this->assertCount(0, $ticket->getActorsForType(CommonITILActor::ASSIGN, $params));

        // disable autoactor in session
        $_SESSION['glpiset_default_requester'] = false;
        $_SESSION['glpiset_default_tech']      = false;
        $this->assertCount(0, $ticket->getActorsForType(CommonITILActor::REQUESTER));
        $this->assertCount(0, $ticket->getActorsForType(CommonITILActor::OBSERVER));
        $this->assertCount(0, $ticket->getActorsForType(CommonITILActor::ASSIGN));

        // ## 2nd - test load actors from templates (simulated)
        // ####################################################
        //reset session
        $_SESSION['glpiset_default_requester'] = true;
        $_SESSION['glpiset_default_tech']      = true;
        //prepare params
        $params = [
            '_template_changed'  => true,
            '_predefined_fields' => [
                '_users_id_requester' => $postonly_id,
                '_users_id_observer'  => $postonly_id,
                '_users_id_assign'    => $tech_id,
            ],
        ];
        $this->assertCount(2, $ticket->getActorsForType(CommonITILActor::REQUESTER, $params));
        $this->assertCount(1, $ticket->getActorsForType(CommonITILActor::OBSERVER, $params));
        $this->assertCount(2, $ticket->getActorsForType(CommonITILActor::ASSIGN, $params));

        $_SESSION['glpiset_default_requester'] = false;
        $_SESSION['glpiset_default_tech']      = false;

        $actors = $ticket->getActorsForType(CommonITILActor::REQUESTER, $params);
        $this->assertCount(1, $actors);
        $this->assertEquals($postonly_id, $actors[0]['items_id']);

        $actors = $ticket->getActorsForType(CommonITILActor::OBSERVER, $params);
        $this->assertCount(1, $actors);
        $this->assertEquals($postonly_id, $actors[0]['items_id']);

        $actors = $ticket->getActorsForType(CommonITILActor::ASSIGN, $params);
        $this->assertCount(1, $actors);
        $this->assertEquals($tech_id, $actors[0]['items_id']);

        // apend groups
        $params['_predefined_fields']['_groups_id_requester'] = [1];
        $params['_predefined_fields']['_groups_id_observer'] = [1];
        $params['_predefined_fields']['_groups_id_assign'] = [1];

        $actors = $ticket->getActorsForType(CommonITILActor::REQUESTER, $params);
        $this->assertCount(2, $actors);
        $this->assertEquals('_test_group_1', $actors[1]['text']);

        $actors = $ticket->getActorsForType(CommonITILActor::OBSERVER, $params);
        $this->assertCount(2, $actors);
        $this->assertEquals('_test_group_1', $actors[1]['text']);

        $actors = $ticket->getActorsForType(CommonITILActor::ASSIGN, $params);
        $this->assertCount(2, $actors);
        $this->assertEquals('_test_group_1', $actors[1]['text']);

        // ## 2nd - test load actors from _actors key (reload simulated)
        // #############################################################
        //reset session
        $_SESSION['glpiset_default_requester'] = true;
        $_SESSION['glpiset_default_tech']      = true;
        //prepare params
        $params = [
            '_skip_default_actor' => true,
            '_actors'             => [
                'requester' => [
                    ['itemtype' => 'User',  'items_id' => $postonly_id],
                    ['itemtype' => 'Group', 'items_id' => 1],
                ],
                'observer'  => [
                    ['itemtype' => 'User',  'items_id' => $postonly_id],
                    ['itemtype' => 'Group', 'items_id' => 1],
                ],
                'assign'    => [
                    ['itemtype' => 'User',  'items_id' => $tech_id],
                    ['itemtype' => 'Group', 'items_id' => 1],
                ],
            ],
        ];
        $requesters = $ticket->getActorsForType(CommonITILActor::REQUESTER, $params);
        $this->assertCount(2, $requesters);
        $this->assertEquals('post-only', $requesters[0]['text']);
        $this->assertEquals('_test_group_1', $requesters[1]['text']);

        $observers = $ticket->getActorsForType(CommonITILActor::OBSERVER, $params);
        $this->assertCount(2, $observers);
        $this->assertEquals('post-only', $observers[0]['text']);
        $this->assertEquals('_test_group_1', $observers[1]['text']);

        $assignees = $ticket->getActorsForType(CommonITILActor::ASSIGN, $params);
        $this->assertCount(2, $assignees);
        $this->assertEquals('tech', $assignees[0]['text']);
        $this->assertEquals('_test_group_1', $assignees[1]['text']);
    }

    public function testGetActorsForTypeNoDuplicates()
    {
        $this->login();

        $ticket = new Ticket();
        $ticket->getEmpty();
        $tech_id = getItemByTypeName('User', 'tech', true);
        $postonly_id = getItemByTypeName('User', 'post-only', true);

        $params = [
            '_template_changed'  => true,
            '_users_id_requester' => $postonly_id,
            '_users_id_observer'  => $postonly_id,
            '_users_id_assign'    => $tech_id,
            '_predefined_fields' => [
                '_users_id_requester' => $postonly_id,
                '_users_id_observer'  => $postonly_id,
                '_users_id_assign'    => $tech_id,
                '_groups_id_requester' => 1,
                '_groups_id_observer'  => 1,
                '_groups_id_assign'    => 1,
            ],
        ];

        $this->assertCount(2, $ticket->getActorsForType(CommonITILActor::REQUESTER, $params));
        $this->assertCount(2, $ticket->getActorsForType(CommonITILActor::OBSERVER, $params));
        $this->assertCount(2, $ticket->getActorsForType(CommonITILActor::ASSIGN, $params));

        $ticket->getEmpty();
        $params = [
            '_skip_default_actor' => true,
            '_actors'             => [
                'requester' => [
                    ['itemtype' => 'User',  'items_id' => $postonly_id],
                    ['itemtype' => 'User',  'items_id' => $postonly_id],
                    ['itemtype' => 'User',  'items_id' => $tech_id],
                    ['itemtype' => 'Group', 'items_id' => 1],
                    ['itemtype' => 'Group', 'items_id' => 1],
                ],
                'observer'  => [
                    ['itemtype' => 'User',  'items_id' => $tech_id],
                    ['itemtype' => 'Group', 'items_id' => 1],
                    ['itemtype' => 'User',  'items_id' => $tech_id],
                    ['itemtype' => 'Group', 'items_id' => 1],
                ],
                'assign'    => [
                    ['itemtype' => 'User',  'items_id' => $tech_id],
                    ['itemtype' => 'Group', 'items_id' => 1],
                    ['itemtype' => 'User',  'items_id' => $tech_id],
                    ['itemtype' => 'Group', 'items_id' => 1],
                ],
            ],
        ];

        $this->assertCount(3, $ticket->getActorsForType(CommonITILActor::REQUESTER, $params));
        $this->assertCount(2, $ticket->getActorsForType(CommonITILActor::OBSERVER, $params));
        $this->assertCount(2, $ticket->getActorsForType(CommonITILActor::ASSIGN, $params));
    }


    public function testNeedReopen()
    {
        $this->login();

        $tech_id     = getItemByTypeName('User', 'tech', true);
        $postonly_id = getItemByTypeName('User', 'post-only', true);
        $normal_id   = getItemByTypeName('User', 'normal', true);

        $requester_group = $this->createItem("Group", [
            'name' => "testNeedReopen",
        ]);
        $this->createItem("Group_User", [
            'users_id' => $normal_id,
            'groups_id' => $requester_group->getID(),
        ]);

        $ticket = new Ticket();
        $tickets_id = $ticket->add([
            'name'                => 'testNeedReopen',
            'content'             => 'testNeedReopen',
            '_users_id_requester' => $postonly_id,
            '_users_id_assign'    => $tech_id,
        ]);
        $this->assertGreaterThan(0, $tickets_id);
        $this->assertTrue((bool) $ticket->getFromDB($ticket->getID()));
        $this->assertEquals(Ticket::ASSIGNED, $ticket->fields['status']);
        $this->assertFalse((bool) $ticket->needReopen());

        $ticket->update([
            'id' => $tickets_id,
            'status' => Ticket::WAITING,
        ]);

        // tech user cant reopen
        $this->assertTrue((bool) $ticket->getFromDB($ticket->getID()));
        $this->assertEquals(Ticket::WAITING, $ticket->fields['status']);
        $this->assertFalse((bool) $ticket->needReopen());

        // requester can reopen
        $this->login('post-only', 'postonly');
        $this->assertTrue((bool) $ticket->getFromDB($ticket->getID()));
        $this->assertEquals(Ticket::WAITING, $ticket->fields['status']);
        $this->assertTrue((bool) $ticket->needReopen());

        // force a reopen
        $followup = new ITILFollowup();
        $followup->add([
            'itemtype'   => 'Ticket',
            'items_id'   => $tickets_id,
            'content'    => 'testNeedReopen',
            'add_reopen' => 1,
        ]);

        // requester cant reopen anymore (ticket is already in an open state)
        $this->assertTrue((bool) $ticket->getFromDB($ticket->getID()));
        $this->assertEquals(Ticket::ASSIGNED, $ticket->fields['status']);
        $this->assertFalse((bool) $ticket->needReopen());

        // Test reopen as a member of a requester group
        $ticket = $this->createItem('Ticket', [
            'name'                 => 'testNeedReopen requester group',
            'content'              => 'testNeedReopen requester group',
            '_users_id_requester'  => $postonly_id,
            '_groups_id_requester' => $requester_group->getID(),
            '_users_id_assign'     => $tech_id,
        ]);

        $this->updateItem('Ticket', $ticket->getID(), [
            'status' => Ticket::WAITING,
        ]);
        $ticket->getFromDB($ticket->getID());

        $this->login('normal', 'normal');
        $this->assertTrue((bool) $ticket->needReopen());
    }

    protected function assignFromCategoryOrItemProvider(): iterable
    {
        $tech_id    = getItemByTypeName('User', 'tech', true);
        $glpi_id    = getItemByTypeName('User', 'glpi', true);
        $normal_id  = getItemByTypeName('User', 'normal', true);

        $group_1_id = getItemByTypeName('Group', '_test_group_1', true);
        $group_2_id = getItemByTypeName('Group', '_test_group_2', true);

        $group = new Group();
        $group_3_id = $group->add(
            [
                'name'        => 'Group 3',
                'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
                'is_assign'   => 1,
            ]
        );
        $this->assertGreaterThan(0, $group_3_id);

        // _skip_auto_assign in input should prevent auto assign
        yield [
            'auto_assign_mode' => Entity::AUTO_ASSIGN_HARDWARE_CATEGORY,
            'category_input'   => [
                'users_id'  => $tech_id,
                'groups_id' => $group_1_id,
            ],
            'computer_input'   => [
                'users_id_tech'  => $glpi_id,
                'groups_id_tech' => $group_2_id,
            ],
            'ticket_input'     => [
                '_skip_auto_assign' => 1,
            ],
            'expected_actors'  => [
            ],
        ];

        // Entity::CONFIG_NEVER case
        yield [
            'auto_assign_mode' => Entity::CONFIG_NEVER,
            'category_input'   => [
                'users_id'  => $tech_id,
                'groups_id' => $group_1_id,
            ],
            'computer_input'   => [
                'users_id_tech'  => $glpi_id,
                'groups_id_tech' => $group_2_id,
            ],
            'ticket_input'     => [
            ],
            'expected_actors'  => [
            ],
        ];

        // Entity::AUTO_ASSIGN_HARDWARE_CATEGORY case
        // - with no assignee from input
        // - with hardware having both user and group defined
        yield [
            'auto_assign_mode' => Entity::AUTO_ASSIGN_HARDWARE_CATEGORY,
            'category_input'   => [
                'users_id'  => $tech_id,
                'groups_id' => $group_1_id,
            ],
            'computer_input'   => [
                'users_id_tech'  => $glpi_id,
                'groups_id_tech' => $group_2_id,
            ],
            'ticket_input'     => [
            ],
            'expected_actors'  => [
                [
                    'itemtype' => User::class,
                    'items_id' => $glpi_id,
                ],
                [
                    'itemtype' => Group::class,
                    'items_id' => $group_2_id,
                ],
            ],
        ];

        // Entity::AUTO_ASSIGN_HARDWARE_CATEGORY case
        // - with no assignee from input
        // - with hardware having only user defined
        // - with category having both user and group defined
        yield [
            'auto_assign_mode' => Entity::AUTO_ASSIGN_HARDWARE_CATEGORY,
            'category_input'   => [
                'users_id'  => $tech_id,
                'groups_id' => $group_1_id,
            ],
            'computer_input'   => [
                'users_id_tech'  => 0,
                'groups_id_tech' => $group_2_id,
            ],
            'ticket_input'     => [
            ],
            'expected_actors'  => [
                [
                    'itemtype' => User::class,
                    'items_id' => $tech_id,
                ],
                [
                    'itemtype' => Group::class,
                    'items_id' => $group_2_id,
                ],
            ],
        ];

        // Entity::AUTO_ASSIGN_HARDWARE_CATEGORY case
        // - with no assignee from input
        // - with hardware having only group defined
        // - with category having both user and group defined
        yield [
            'auto_assign_mode' => Entity::AUTO_ASSIGN_HARDWARE_CATEGORY,
            'category_input'   => [
                'users_id'  => $tech_id,
                'groups_id' => $group_1_id,
            ],
            'computer_input'   => [
                'users_id_tech'  => $glpi_id,
                'groups_id_tech' => 0,
            ],
            'ticket_input'     => [
            ],
            'expected_actors'  => [
                [
                    'itemtype' => User::class,
                    'items_id' => $glpi_id,
                ],
                [
                    'itemtype' => Group::class,
                    'items_id' => $group_1_id,
                ],
            ],
        ];

        // Entity::AUTO_ASSIGN_HARDWARE_CATEGORY case
        // - with no assignee from input
        // - with hardware having neither user or group defined
        // - with category having both user and group defined
        yield [
            'auto_assign_mode' => Entity::AUTO_ASSIGN_HARDWARE_CATEGORY,
            'category_input'   => [
                'users_id'  => $tech_id,
                'groups_id' => $group_1_id,
            ],
            'computer_input'   => [
                'users_id_tech'  => 0,
                'groups_id_tech' => 0,
            ],
            'ticket_input'     => [
            ],
            'expected_actors'  => [
                [
                    'itemtype' => User::class,
                    'items_id' => $tech_id,
                ],
                [
                    'itemtype' => Group::class,
                    'items_id' => $group_1_id,
                ],
            ],
        ];

        // Entity::AUTO_ASSIGN_HARDWARE_CATEGORY case
        // - with assignee from input (user)
        // - with hardware having both user and group defined
        yield [
            'auto_assign_mode' => Entity::AUTO_ASSIGN_HARDWARE_CATEGORY,
            'category_input'   => [
                'users_id'  => $tech_id,
                'groups_id' => $group_1_id,
            ],
            'computer_input'   => [
                'users_id_tech'  => $glpi_id,
                'groups_id_tech' => $group_2_id,
            ],
            'ticket_input'     => [
                '_users_id_assign' => [$normal_id],
            ],
            'expected_actors'  => [
                [
                    'itemtype' => User::class,
                    'items_id' => $normal_id,
                ],
                [
                    'itemtype' => Group::class,
                    'items_id' => $group_2_id,
                ],
            ],
        ];

        // Entity::AUTO_ASSIGN_HARDWARE_CATEGORY case
        // - with assignee from input ("email" actor)
        // - with hardware having both user and group defined
        yield [
            'auto_assign_mode' => Entity::AUTO_ASSIGN_HARDWARE_CATEGORY,
            'category_input'   => [
                'users_id'  => $tech_id,
                'groups_id' => $group_1_id,
            ],
            'computer_input'   => [
                'users_id_tech'  => $glpi_id,
                'groups_id_tech' => $group_2_id,
            ],
            'ticket_input'     => [
                '_users_id_assign' => [0],
                '_users_id_assign_notif' => [
                    'use_notification'  => [1],
                    'alternative_email' => ['test@glpi-project.org'],
                ],
            ],
            'expected_actors'  => [
                [
                    'itemtype' => User::class,
                    'items_id' => 0,
                    'use_notification'  => 1,
                    'alternative_email' => 'test@glpi-project.org',
                ],
                [
                    'itemtype' => Group::class,
                    'items_id' => $group_2_id,
                ],
            ],
        ];

        // Entity::AUTO_ASSIGN_HARDWARE_CATEGORY case
        // - with assignee from input (group)
        // - with hardware having both user and group defined
        yield [
            'auto_assign_mode' => Entity::AUTO_ASSIGN_HARDWARE_CATEGORY,
            'category_input'   => [
                'users_id'  => $tech_id,
                'groups_id' => $group_1_id,
            ],
            'computer_input'   => [
                'users_id_tech'  => $glpi_id,
                'groups_id_tech' => $group_2_id,
            ],
            'ticket_input'     => [
                '_groups_id_assign' => [$group_3_id],
            ],
            'expected_actors'  => [
                [
                    'itemtype' => User::class,
                    'items_id' => $glpi_id,
                ],
                [
                    'itemtype' => Group::class,
                    'items_id' => $group_3_id,
                ],
            ],
        ];

        // Entity::AUTO_ASSIGN_CATEGORY_HARDWARE case
        // - with no assignee from input
        // - with category having both user and group defined
        yield [
            'auto_assign_mode' => Entity::AUTO_ASSIGN_CATEGORY_HARDWARE,
            'category_input'   => [
                'users_id'  => $tech_id,
                'groups_id' => $group_1_id,
            ],
            'computer_input'   => [
                'users_id_tech'  => $glpi_id,
                'groups_id_tech' => $group_2_id,
            ],
            'ticket_input'     => [
            ],
            'expected_actors'  => [
                [
                    'itemtype' => User::class,
                    'items_id' => $tech_id,
                ],
                [
                    'itemtype' => Group::class,
                    'items_id' => $group_1_id,
                ],
            ],
        ];

        // Entity::AUTO_ASSIGN_CATEGORY_HARDWARE case
        // - with no assignee from input
        // - with category having only user defined
        // - with hardware having both user and group defined
        yield [
            'auto_assign_mode' => Entity::AUTO_ASSIGN_CATEGORY_HARDWARE,
            'category_input'   => [
                'users_id'  => $tech_id,
                'groups_id' => 0,
            ],
            'computer_input'   => [
                'users_id_tech'  => $glpi_id,
                'groups_id_tech' => $group_2_id,
            ],
            'ticket_input'     => [
            ],
            'expected_actors'  => [
                [
                    'itemtype' => User::class,
                    'items_id' => $tech_id,
                ],
                [
                    'itemtype' => Group::class,
                    'items_id' => $group_2_id,
                ],
            ],
        ];

        // Entity::AUTO_ASSIGN_CATEGORY_HARDWARE case
        // - with no assignee from input
        // - with category having only group defined
        // - with hardware having both user and group defined
        yield [
            'auto_assign_mode' => Entity::AUTO_ASSIGN_CATEGORY_HARDWARE,
            'category_input'   => [
                'users_id'  => 0,
                'groups_id' => $group_1_id,
            ],
            'computer_input'   => [
                'users_id_tech'  => $glpi_id,
                'groups_id_tech' => $group_2_id,
            ],
            'ticket_input'     => [
            ],
            'expected_actors'  => [
                [
                    'itemtype' => User::class,
                    'items_id' => $glpi_id,
                ],
                [
                    'itemtype' => Group::class,
                    'items_id' => $group_1_id,
                ],
            ],
        ];

        // Entity::AUTO_ASSIGN_CATEGORY_HARDWARE case
        // - with no assignee from input
        // - with category having neither user or group defined
        // - with hardware having both user and group defined
        yield [
            'auto_assign_mode' => Entity::AUTO_ASSIGN_CATEGORY_HARDWARE,
            'category_input'   => [
                'users_id'  => 0,
                'groups_id' => 0,
            ],
            'computer_input'   => [
                'users_id_tech'  => $glpi_id,
                'groups_id_tech' => $group_2_id,
            ],
            'ticket_input'     => [
            ],
            'expected_actors'  => [
                [
                    'itemtype' => User::class,
                    'items_id' => $glpi_id,
                ],
                [
                    'itemtype' => Group::class,
                    'items_id' => $group_2_id,
                ],
            ],
        ];

        // Entity::AUTO_ASSIGN_CATEGORY_HARDWARE case
        // - with assignee from input (user)
        // - with hardware having both user and group defined
        yield [
            'auto_assign_mode' => Entity::AUTO_ASSIGN_CATEGORY_HARDWARE,
            'category_input'   => [
                'users_id'  => $tech_id,
                'groups_id' => $group_1_id,
            ],
            'computer_input'   => [
                'users_id_tech'  => $glpi_id,
                'groups_id_tech' => $group_2_id,
            ],
            'ticket_input'     => [
                '_users_id_assign' => [$normal_id],
            ],
            'expected_actors'  => [
                [
                    'itemtype' => User::class,
                    'items_id' => $normal_id,
                ],
                [
                    'itemtype' => Group::class,
                    'items_id' => $group_1_id,
                ],
            ],
        ];

        // Entity::AUTO_ASSIGN_CATEGORY_HARDWARE case
        // - with assignee from input ("email" actor)
        // - with category having both user and group defined
        yield [
            'auto_assign_mode' => Entity::AUTO_ASSIGN_CATEGORY_HARDWARE,
            'category_input'   => [
                'users_id'  => $tech_id,
                'groups_id' => $group_1_id,
            ],
            'computer_input'   => [
                'users_id_tech'  => $glpi_id,
                'groups_id_tech' => $group_2_id,
            ],
            'ticket_input'     => [
                '_users_id_assign' => [0],
                '_users_id_assign_notif' => [
                    'use_notification'  => [1],
                    'alternative_email' => ['test@glpi-project.org'],
                ],
            ],
            'expected_actors'  => [
                [
                    'itemtype' => User::class,
                    'items_id' => 0,
                    'use_notification'  => 1,
                    'alternative_email' => 'test@glpi-project.org',
                ],
                [
                    'itemtype' => Group::class,
                    'items_id' => $group_1_id,
                ],
            ],
        ];

        // Entity::AUTO_ASSIGN_CATEGORY_HARDWARE case
        // - with assignee from input (group)
        // - with category having both user and group defined
        yield [
            'auto_assign_mode' => Entity::AUTO_ASSIGN_CATEGORY_HARDWARE,
            'category_input'   => [
                'users_id'  => $tech_id,
                'groups_id' => $group_1_id,
            ],
            'computer_input'   => [
                'users_id_tech'  => $glpi_id,
                'groups_id_tech' => $group_2_id,
            ],
            'ticket_input'     => [
                '_groups_id_assign' => [$group_3_id],
            ],
            'expected_actors'  => [
                [
                    'itemtype' => User::class,
                    'items_id' => $tech_id,
                ],
                [
                    'itemtype' => Group::class,
                    'items_id' => $group_3_id,
                ],
            ],
        ];
    }

    public function testAssignFromCategoryOrItem(): void
    {
        $provider = $this->assignFromCategoryOrItemProvider();
        $entity_id = getItemByTypeName('Entity', '_test_root_entity', true);
        $this->login();

        foreach ($provider as $row) {
            $auto_assign_mode = $row['auto_assign_mode'];
            $category_input = $row['category_input'] ?? null;
            $computer_input = $row['computer_input'] ?? null;
            $ticket_input = $row['ticket_input'];
            $expected_actors = $row['expected_actors'];

            $entity = new Entity();
            $this->assertTrue($entity->update(['id' => $entity_id, 'auto_assign_mode' => $auto_assign_mode]));

            $itilcategory_id = 0;
            if ($category_input !== null) {
                $itilcategory = new ITILCategory();
                $itilcategory_id = $itilcategory->add(
                    $category_input + [
                        'name' => __METHOD__,
                        'entities_id' => $entity_id,
                    ]
                );
                $this->assertGreaterThan(0, $itilcategory_id);
            }

            $items_id = [];
            if ($computer_input !== null) {
                $computer = new Computer();
                $computer_id = $computer->add(
                    $computer_input + [
                        'name' => __METHOD__,
                        'entities_id' => $entity_id,
                    ]
                );
                $this->assertGreaterThan(0, $computer_id);
                $items_id[Computer::class] = [$computer_id];
            }

            $ticket = new Ticket();
            $ticket_id = $ticket->add(
                $ticket_input + [
                    'name' => __METHOD__,
                    'content' => __METHOD__,
                    'entities_id' => $entity_id,
                    'itilcategories_id' => $itilcategory_id,
                    'items_id' => $items_id,
                ]
            );
            $this->assertGreaterThan(0, $ticket_id);

            $ticket->getFromDB($ticket->getID());
            $actors = $ticket->getActorsForType(CommonITILActor::ASSIGN);
            $this->assertCount(count($expected_actors), $actors);

            foreach ($expected_actors as $expected_actor) {
                $found = false;
                foreach ($actors as $actor) {
                    if (array_intersect_assoc($expected_actor, $actor) === $expected_actor) {
                        // Found an actor that has same properties as those defined in expected actor
                        $found = true;
                        break;
                    }
                }
                $this->assertTrue(
                    $found,
                    sprintf(
                        "Expected:\n%s\nFound:\n%s",
                        json_encode($expected_actor),
                        json_encode($actors)
                    )
                );
            }
        }
    }

    protected function providerGetPrimaryRequesterUser()
    {
        $this->login();
        $entity_id = 0;

        $ticket = new Ticket();
        yield [
            'ticket' => $ticket,
            'expected' => null,
        ];

        $ticket = new Ticket();
        $ticket->add([
            'name'              => __METHOD__,
            'content'           => __METHOD__,
            'entities_id'       => $entity_id,
            '_skip_auto_assign' => true,
        ]);
        yield [
            'ticket' => $ticket,
            'expected' => null,
        ];

        $ticket = new Ticket();
        $ticket->add([
            'name'              => __METHOD__,
            'content'           => __METHOD__,
            'entities_id'       => $entity_id,
            '_actors'           => [
                'requester'       => [
                    [
                        'itemtype'          => User::class,
                        'items_id'          => $_SESSION['glpiID'],
                        'use_notification'  => 0,
                        'alternative_email' => '',
                    ],
                ],
            ],
        ]);
        yield [
            'ticket' => $ticket,
            'expected' => $_SESSION['glpiID'],
        ];

        $glpi_user = new User();
        $glpi_user->getFromDBbyName('glpi');
        $normal_user = new User();
        $normal_user->getFromDBbyName('normal');
        $ticket = new Ticket();
        $ticket->add([
            'name'              => __METHOD__,
            'content'           => __METHOD__,
            'entities_id'       => $entity_id,
            '_actors'           => [
                'requester'       => [
                    [
                        'itemtype'          => User::class,
                        'items_id'          => $normal_user->getID(),
                        'use_notification'  => 0,
                        'alternative_email' => '',
                    ], [
                        'itemtype'          => User::class,
                        'items_id'          => $glpi_user->getID(),
                        'use_notification'  => 0,
                        'alternative_email' => '',
                    ],
                ],
            ],
        ]);
        yield [
            'ticket' => $ticket,
            'expected' => $normal_user->getID(),
        ];
    }

    public function testGetPrimaryRequesterUser()
    {
        $provider = $this->providerGetPrimaryRequesterUser();
        foreach ($provider as $row) {
            /** @var Ticket $ticket */
            $ticket = $row['ticket'];
            $expected = $row['expected'];
            $output = $ticket->getPrimaryRequesterUser();
            if ($expected === null) {
                $this->assertNull($output);
            } else {
                $this->assertSame($expected, $output->getID());
            }
        }
    }

    protected function requestersEntitiesProvider(): iterable
    {
        $this->login();

        $entity_1 = $this->createItem(
            Entity::class,
            [
                'name'        => __FUNCTION__ . '1',
                'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            ]
        );

        $entity_2 = $this->createItem(
            Entity::class,
            [
                'name'        => __FUNCTION__ . '2',
                'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            ]
        );

        $profile_id = getItemByTypeName('Profile', 'Self-Service', true);

        // User 1 is attached only to Entity 1
        $user_1 = $this->createItem(
            User::class,
            [
                'name'         => __FUNCTION__ . '1',
                '_profiles_id' => $profile_id,
                '_entities_id' => $entity_1->getID(),
                'entities_id'  => $entity_1->getID(),
            ]
        );

        // User 2 is attached to Entity 1 and Entity 2
        $user_2 = $this->createItem(
            User::class,
            [
                'name'         => __FUNCTION__ . '2',
                '_profiles_id' => $profile_id,
                '_entities_id' => $entity_1->getID(),
                'entities_id'  => $entity_1->getID(),
            ]
        );
        $this->createItem(
            Profile_User::class,
            [
                'entities_id' => $entity_2->getID(),
                'profiles_id' => $profile_id,
                'users_id'    => $user_2->getID(),
            ]
        );

        // Check for User 1
        yield [
            'params'   => [
                '_users_id_requester' => $user_1->getID(),
            ],
            'expected' => [
                $entity_1->getID(),
            ],
        ];
        yield [
            'params'   => [
                '_actors' => [
                    'requester' => [
                        [
                            'itemtype'          => User::class,
                            'items_id'          => $user_1->getID(),
                            'use_notification'  => 1,
                            'alternative_email' => '',
                        ],
                    ],
                ],
            ],
            'expected' => [
                $entity_1->getID(),
            ],
        ];

        // Check for User 2
        yield [
            'params'   => [
                '_users_id_requester' => $user_2->getID(),
            ],
            'expected' => [
                $entity_1->getID(),
                $entity_2->getID(),
            ],
        ];
        yield [
            'params'   => [
                '_actors' => [
                    'requester' => [
                        [
                            'itemtype'          => User::class,
                            'items_id'          => $user_2->getID(),
                            'use_notification'  => 1,
                            'alternative_email' => '',
                        ],
                    ],
                ],
            ],
            'expected' => [
                $entity_1->getID(),
                $entity_2->getID(),
            ],
        ];

        // Check for User 1 + User 2
        yield [
            'params'   => [
                '_users_id_requester' => [$user_1->getID(), $user_2->getID()],
            ],
            'expected' => [
                $entity_1->getID(),
            ],
        ];
        yield [
            'params'   => [
                '_actors' => [
                    'requester' => [
                        [
                            'itemtype'          => User::class,
                            'items_id'          => $user_1->getID(),
                            'use_notification'  => 1,
                            'alternative_email' => '',
                        ],
                        [
                            'itemtype'          => User::class,
                            'items_id'          => $user_2->getID(),
                            'use_notification'  => 1,
                            'alternative_email' => '',
                        ],
                    ],
                ],
            ],
            'expected' => [
                $entity_1->getID(),
            ],
        ];

        // Check for "email" actor
        yield [
            'params'   => [
                '_actors' => [
                    'requester' => [
                        [
                            'itemtype'          => User::class,
                            'items_id'          => 0,
                            'use_notification'  => 1,
                            'alternative_email' => 'notaglpiuser@domain.tld',
                        ],
                    ],
                ],
            ],
            'expected' => array_values($_SESSION['glpiactiveentities']),
        ];
    }

    public function testGetEntitiesForRequesters()
    {
        $this->login();
        $provider = $this->requestersEntitiesProvider();
        foreach ($provider as $row) {
            $params = $row['params'];
            $expected = $row['expected'];

            $instance = new Ticket();
            $this->assertSame($expected, $instance->getEntitiesForRequesters($params));
        }
    }

    public function testViewIncomingTicketWithoutNewTicketRight()
    {
        $this->login();

        $ticket = new Ticket();
        $tickets_id = $ticket->add([
            'name' => 'testViewIncomingTicketWithoutNewTicketRight',
            'content' => 'testViewIncomingTicketWithoutNewTicketRight',
        ]);
        $this->assertGreaterThan(0, $tickets_id);

        $this->changeTechRight(Ticket::READMY);
        $this->assertFalse($ticket->canViewItem());
    }

    public function testViewIncomingTicketWithNewTicketRight()
    {
        $this->login();

        $ticket = new Ticket();
        $tickets_id = $ticket->add([
            'name' => 'testViewIncomingTicketWithoutNewTicketRight',
            'content' => 'testViewIncomingTicketWithoutNewTicketRight',
        ]);
        $this->assertGreaterThan(0, $tickets_id);

        $this->changeTechRight(Ticket::READNEWTICKET);
        $this->assertTrue($ticket->canViewItem());
    }

    /**
     * The right "View new tickets" should not include those with the "approval" status.
     */
    public function testUserCannotViewApprovalTicketsWithReadNewTicketRight()
    {
        $this->login();

        $ticket = $this->createItem('Ticket', [
            'name' => __FUNCTION__,
            'content' => __FUNCTION__,
            'status' => CommonITILObject::APPROVAL,
        ]);

        $this->changeTechRights(['ticket' => Ticket::READNEWTICKET]);
        $this->assertFalse($ticket->canViewItem());
    }

    public function testAssignToMe()
    {
        $this->login();
        $entity_id = 0;

        $ticket = new Ticket();
        $fup = new ITILFollowup();
        $sol = new ITILSolution();

        //create a ticket
        $ticket_id = $ticket->add([
            'name'                  => __METHOD__,
            'content'               => __METHOD__,
            'entities_id'           => $entity_id,
            '_skip_auto_assign'     => true,
            '_users_id_requester'   => getItemByTypeName('User', 'normal', true),
        ]);
        $this->assertGreaterThan(0, $ticket_id);

        //add a followup to the ticket without assigning to me (tech)
        $this->login('tech', 'tech');
        $_SESSION['glpiset_followup_tech'] = 0;
        $this->assertGreaterThan(
            0,
            (int) $fup->add([
                'itemtype'  => 'Ticket',
                'items_id'  => $ticket_id,
                'content'   => 'A simple followup',
            ])
        );

        $ticket->getFromDB($ticket_id);
        $actors = $ticket->getActorsForType(CommonITILActor::ASSIGN);
        $this->assertCount(0, $actors);

        //add a private followup to the ticket and NOT assign to me (tech)
        $_SESSION['glpiset_followup_tech'] = 1;
        $this->assertGreaterThan(
            0,
            (int) $fup->add([
                'itemtype'      => 'Ticket',
                'items_id'      => $ticket_id,
                'content'       => 'A simple followup',
                'is_private'    => 1,
            ])
        );

        $ticket->getFromDB($ticket_id);
        $actors = $ticket->getActorsForType(CommonITILActor::ASSIGN);
        $this->assertCount(0, $actors);

        //add a followup to the ticket and assign to me (tech)
        $this->assertGreaterThan(
            0,
            (int) $fup->add([
                'itemtype'  => 'Ticket',
                'items_id'  => $ticket_id,
                'content'   => 'A simple followup',
            ])
        );

        $ticket->getFromDB($ticket_id);
        $actors = $ticket->getActorsForType(CommonITILActor::ASSIGN);
        $this->assertCount(1, $actors);

        //add a solution to the ticket and assign to me
        $this->login('glpi', 'glpi');
        $_SESSION['glpiset_solution_tech'] = 1;
        $this->assertGreaterThan(
            0,
            (int) $sol->add([
                'itemtype'  => 'Ticket',
                'items_id'  => $ticket_id,
                'content'   => 'A simple solution',
            ])
        );

        $ticket->getFromDB($ticket_id);
        $actors = $ticket->getActorsForType(CommonITILActor::ASSIGN);
        $this->assertCount(2, $actors);

        //create a new ticket
        $ticket_id = $ticket->add([
            'name'                  => __METHOD__,
            'content'               => __METHOD__,
            'entities_id'           => $entity_id,
            '_skip_auto_assign'     => true,
            '_users_id_requester'   => getItemByTypeName('User', 'normal', true),
        ]);
        $this->assertGreaterThan(0, $ticket_id);

        //add a solution to the ticket without assigning to me
        $this->login('tech', 'tech');
        $_SESSION['glpiset_solution_tech'] = 0;
        $this->assertGreaterThan(
            0,
            (int) $sol->add([
                'itemtype'  => 'Ticket',
                'items_id'  => $ticket_id,
                'content'   => 'A simple solution',
            ])
        );

        $ticket->getFromDB($ticket_id);
        $actors = $ticket->getActorsForType(CommonITILActor::ASSIGN);
        $this->assertCount(0, $actors);

        //create a new ticket
        $ticket_id = $ticket->add([
            'name'                  => __METHOD__,
            'content'               => __METHOD__,
            'entities_id'           => $entity_id,
            '_skip_auto_assign'     => true,
            '_users_id_requester'   => getItemByTypeName('User', 'glpi', true),
            '_users_id_observer'    => getItemByTypeName('User', 'tech', true),
        ]);
        $this->assertGreaterThan(0, $ticket_id);
        $ticket->getFromDB($ticket_id);
        $actors = $ticket->getActorsForType(CommonITILActor::REQUESTER);
        $this->assertCount(1, $actors);

        //add a followup to the ticket without assigning to me
        $this->login('glpi', 'glpi');
        $_SESSION['glpiset_followup_tech'] = 1;
        $this->assertGreaterThan(
            0,
            (int) $fup->add([
                'itemtype'  => 'Ticket',
                'items_id'  => $ticket_id,
                'content'   => 'A simple followup',
            ])
        );

        //add a followup to the ticket without assigning to me
        $this->login('tech', 'tech');
        $_SESSION['glpiset_followup_tech'] = 1;
        $this->assertGreaterThan(
            0,
            (int) $fup->add([
                'itemtype'  => 'Ticket',
                'items_id'  => $ticket_id,
                'content'   => 'A simple followup',
            ])
        );

        $ticket->getFromDB($ticket_id);
        $actors = $ticket->getActorsForType(CommonITILActor::ASSIGN);
        $this->assertCount(0, $actors);

        //add a solution to the ticket without assigning to me
        $this->login('glpi', 'glpi');
        $_SESSION['glpiset_solution_tech'] = 1;
        $this->assertGreaterThan(
            0,
            (int) $sol->add([
                'itemtype'  => 'Ticket',
                'items_id'  => $ticket_id,
                'content'   => 'A simple solution',
            ])
        );

        $ticket->getFromDB($ticket_id);
        $actors = $ticket->getActorsForType(CommonITILActor::ASSIGN);
        $this->assertCount(0, $actors);
    }

    public function testNotificationDisabled()
    {
        //setup
        $this->login();

        $user = new User();

        //check default computed value
        $this->assertTrue((bool) $user->getFromDB(Session::getLoginUserID()));
        $this->assertNull($user->fields['is_notif_enable_default']); //default value from user table
        $this->assertTrue((bool) $user->isUserNotificationEnable()); //like default configuration

        $ticket = new Ticket();
        $ticket_id = $ticket->add(
            [
                'name'        => 'ticket title',
                'content'     => 'a description',
                'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
                "_users_id_requester" => Session::getLoginUserID(),
            ] + $ticket->getDefaultValues(getItemByTypeName('Entity', '_test_root_entity', true))
        );
        $this->assertGreaterThan(0, $ticket_id);

        //load ticket actor
        $ticket_user = new Ticket_User();
        $actors = $ticket_user->find([
            "tickets_id" => $ticket_id,
            "type" => CommonITILActor::REQUESTER,
        ]);
        $this->assertCount(1, $actors);
        $this->assertEquals(1, reset($actors)['use_notification']);

        //update user to explicitly refuse notification
        $this->assertTrue($user->update([
            'id' => Session::getLoginUserID(),
            'is_notif_enable_default' => '0',
        ]));
        //check computed value
        $this->assertTrue($user->getFromDB(Session::getLoginUserID()));
        $this->assertFalse((bool) $user->fields['is_notif_enable_default']);
        $this->assertFalse($user->isUserNotificationEnable());

        $ticket = new Ticket();
        $ticket_id = $ticket->add(
            [
                'name'        => 'other ticket title',
                'content'     => 'other description',
                'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            ] + $ticket->getDefaultValues(getItemByTypeName('Entity', '_test_root_entity', true))
        );
        $this->assertGreaterThan(0, $ticket_id);

        //load ticket actor
        $ticket_user = new Ticket_User();
        $actors = $ticket_user->find([
            "tickets_id" => $ticket_id,
            "type" => CommonITILActor::REQUESTER,
        ]);
        $this->assertCount(1, $actors);
        $this->assertEquals(0, reset($actors)['use_notification']);
    }

    public function testShowCentralCountCriteria()
    {
        global $DB;

        $this->login();

        // Create entities
        $entity = new Entity();
        $entity_id = $entity->add([
            'name' => 'testShowCentralCountCriteria',
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
        ]);
        $this->assertGreaterThan(0, $entity_id);

        // Create a new user
        $user = new User();
        $user_id = $user->add([
            'name' => 'testShowCentralCountCriteria',
            'password' => 'testShowCentralCountCriteria',
            'password2' => 'testShowCentralCountCriteria',
            '_profiles_id' => getItemByTypeName('Profile', 'Self-Service', true),
            '_entities_id' => $entity_id,
            'entities_id' => $entity_id,
        ]);
        $this->assertGreaterThan(0, $user_id);

        // Create a new ticket with the user as requester
        $ticket_requester = new Ticket();
        $ticket_id_requester = $ticket_requester->add([
            'name' => 'testShowCentralCountCriteria',
            'content' => 'testShowCentralCountCriteria',
            'entities_id' => $entity_id,
        ]);
        $this->assertGreaterThan(0, $ticket_id_requester);

        // Add the user as requester for the ticket
        $ticket_user_requester = new Ticket_User();
        $ticket_user_id_requester = $ticket_user_requester->add([
            'tickets_id' => $ticket_id_requester,
            'users_id' => $user_id,
            'type' => CommonITILActor::REQUESTER,
        ]);
        $this->assertGreaterThan(0, $ticket_user_id_requester);

        // Create a new ticket with the user as observer
        $ticket_observer = new Ticket();
        $ticket_id_observer = $ticket_observer->add([
            'name' => 'testShowCentralCountCriteria',
            'content' => 'testShowCentralCountCriteria',
            'entities_id' => $entity_id,
        ]);
        $this->assertGreaterThan(0, $ticket_id_observer);

        // Add the user as observer for the ticket
        $ticket_user_observer = new Ticket_User();
        $ticket_user_observer_id = $ticket_user_observer->add([
            'tickets_id' => $ticket_id_observer,
            'users_id' => $user_id,
            'type' => CommonITILActor::OBSERVER,
        ]);
        $this->assertGreaterThan(0, $ticket_user_observer_id);

        // Create a new ticket
        $ticket = new Ticket();
        $ticket_id = $ticket->add([
            'name' => 'testShowCentralCountCriteria',
            'content' => 'testShowCentralCountCriteria',
            'entities_id' => $entity_id,
        ]);
        $this->assertGreaterThan(0, $ticket_id);

        // Login with the new user
        $this->login('testShowCentralCountCriteria', 'testShowCentralCountCriteria');

        // Check if the user can see 2 tickets
        $criteria = $this->callPrivateMethod(new Ticket(), 'showCentralCountCriteria', true);
        $iterator = $DB->request($criteria);
        foreach ($iterator as $data) {
            $this->assertArrayHasKey('status', $data);
            $this->assertArrayHasKey('COUNT', $data);
            $this->assertEquals(1, $data['status']);
            $this->assertEquals(2, $data['COUNT']);
        }

        // Check if the global view return 3 tickets
        $criteria = $this->callPrivateMethod(new Ticket(), 'showCentralCountCriteria', false);
        $iterator = $DB->request($criteria);
        foreach ($iterator as $data) {
            $this->assertArrayHasKey('status', $data);
            $this->assertArrayHasKey('COUNT', $data);
            $this->assertEquals(1, $data['status']);
            $this->assertEquals(3, $data['COUNT']);
        }
    }

    protected function timelineItemsProvider(): iterable
    {
        $now = time();

        $postonly_user_id = getItemByTypeName(User::class, 'post-only', true);
        $normal_user_id   = getItemByTypeName(User::class, 'normal', true);
        $tech_user_id     = getItemByTypeName(User::class, 'tech', true);
        $glpi_user_id     = getItemByTypeName(User::class, 'glpi', true);

        $profile_id = getItemByTypeName(Profile::class, 'Observer', true);

        $profile_right = new ProfileRight();
        $profile_right->getFromDBByCrit([
            'profiles_id' => $profile_id,
            'name'        => 'task',
        ]);

        $this->updateItem(
            ProfileRight::class,
            $profile_right->getID(),
            [
                'rights' => \CommonITILTask::SEEPRIVATEGROUPS + \CommonITILTask::SEEPUBLIC,
            ]
        );

        $tprofile_id = getItemByTypeName(Profile::class, 'Technician', true);

        $profile_right = new ProfileRight();
        $profile_right->getFromDBByCrit([
            'profiles_id' => $tprofile_id,
            'name'        => 'task',
        ]);

        $this->updateItem(
            ProfileRight::class,
            $profile_right->getID(),
            [
                'rights' => \CommonITILTask::SEEPRIVATEGROUPS + \CommonITILTask::SEEPUBLIC,
            ]
        );

        $group = $this->createItem(
            Group::class,
            [
                'name' => 'Test_Group_Task',
            ]
        );

        $this->createItem(
            Group_User::class,
            [
                'groups_id' => $group->getID(),
                'users_id'  => $normal_user_id,
            ]
        );

        $seegroup_id = $group->getID();

        $this->login();

        $ticket = $this->createItem(
            Ticket::class,
            [
                'name'                => __FUNCTION__,
                'content'             => __FUNCTION__,
                '_users_id_requester' => $postonly_user_id,
                '_users_id_observer'  => $normal_user_id,
                '_users_id_assign'    => [$tech_user_id, $glpi_user_id],
                '_groups_id_assign'   => $seegroup_id,
            ]
        );

        $this->createItem(
            ITILFollowup::class,
            [
                'itemtype'      => Ticket::class,
                'items_id'      => $ticket->getID(),
                'content'       => 'public followup',
                'date_creation' => date('Y-m-d H:i:s', strtotime('+10s', $now)), // to ensure result order is correct
            ]
        );

        $this->createItem(
            ITILFollowup::class,
            [
                'itemtype'      => Ticket::class,
                'items_id'      => $ticket->getID(),
                'content'       => 'private followup of tech user',
                'is_private'    => 1,
                'users_id'      => $tech_user_id,
                'date_creation' => date('Y-m-d H:i:s', strtotime('+20s', $now)), // to ensure result order is correct
            ]
        );

        $this->createItem(
            ITILFollowup::class,
            [
                'itemtype'   => Ticket::class,
                'items_id'   => $ticket->getID(),
                'content'    => 'private followup of normal user',
                'is_private' => 1,
                'users_id'   => $normal_user_id,
                'date_creation' => date('Y-m-d H:i:s', strtotime('+30s', $now)), // to ensure result order is correct
            ]
        );

        $task1 = $this->createItem(
            \TicketTask::class,
            [
                'tickets_id'    => $ticket->getID(),
                'content'       => 'public task',
                'date_creation' => date('Y-m-d H:i:s', strtotime('+10s', $now)), // to ensure result order is correct
            ]
        );

        $task2 = $this->createItem(
            \TicketTask::class,
            [
                'tickets_id'    => $ticket->getID(),
                'content'       => 'private task of tech user',
                'is_private'    => 1,
                'users_id'      => $tech_user_id,
                'date_creation' => date('Y-m-d H:i:s', strtotime('+20s', $now)), // to ensure result order is correct
            ]
        );

        $task3 = $this->createItem(
            \TicketTask::class,
            [
                'tickets_id'    => $ticket->getID(),
                'content'       => 'private task of normal user',
                'is_private'    => 1,
                'users_id'      => $normal_user_id,
                'date_creation' => date('Y-m-d H:i:s', strtotime('+30s', $now)), // to ensure result order is correct
            ]
        );

        $task4 = $this->createItem(
            \TicketTask::class,
            [
                'tickets_id'    => $ticket->getID(),
                'content'       => 'private task assigned to normal user',
                'is_private'    => 1,
                'users_id_tech' => $normal_user_id,
                'date_creation' => date('Y-m-d H:i:s', strtotime('+40s', $now)), // to ensure result order is correct
            ]
        );

        $task5 = $this->createItem(
            \TicketTask::class,
            [
                'tickets_id'        => $ticket->getID(),
                'content'           => 'private task assigned to see group',
                'is_private'        => 1,
                'groups_id_tech'    => $seegroup_id,
                'date_creation'     => date('Y-m-d H:i:s', strtotime('+50s', $now)), // to ensure result order is correct
            ]
        );

        $task6 = $this->createItem(
            \TicketTask::class,
            [
                'tickets_id'    => $ticket->getID(),
                'content'       => 'private task assign to tech user',
                'is_private'    => 1,
                'users_id_tech' => $tech_user_id,
                'date_creation' => date('Y-m-d H:i:s', strtotime('+20s', $now)), // to ensure result order is correct
            ]
        );

        $document = $this->createTxtDocument();
        $weblink_document = $this->createItem(
            \Document::class,
            [
                'name' => 'weblink document',
                'link' => 'https://example.com/document.txt',
                'entities_id' => $this->getTestRootEntity(true),
            ],
        );

        $this->createItems(
            \Document_Item::class,
            [
                [
                    'documents_id'   => $document->getID(),
                    'items_id'       => $task1->getID(),
                    'itemtype'       => \TicketTask::class,
                ],
                [
                    'documents_id'   => $document->getID(),
                    'items_id'       => $task2->getID(),
                    'itemtype'       => \TicketTask::class,
                ],
                [
                    'documents_id'   => $document->getID(),
                    'items_id'       => $task3->getID(),
                    'itemtype'       => \TicketTask::class,
                ],
                [
                    'documents_id'   => $document->getID(),
                    'items_id'       => $task4->getID(),
                    'itemtype'       => \TicketTask::class,
                ],
                [
                    'documents_id'   => $document->getID(),
                    'items_id'       => $task5->getID(),
                    'itemtype'       => \TicketTask::class,
                ],
                [
                    'documents_id'   => $document->getID(),
                    'items_id'       => $task6->getID(),
                    'itemtype'       => \TicketTask::class,
                ],
                [
                    'documents_id'   => $weblink_document->getID(),
                    'items_id'       => $ticket->getID(),
                    'itemtype'       => Ticket::class,
                ],
            ]
        );

        // glpi has rights to see all private followups/tasks
        yield [
            'login'              => 'glpi',
            'pass'               => 'glpi',
            'ticket_id'          => $ticket->getID(),
            'options'            => [],
            'expected_followups' => [
                'private followup of normal user',
                'private followup of tech user',
                'public followup',
            ],
            'expected_tasks'     => [
                'private task assigned to see group',
                'private task assigned to normal user',
                'private task of normal user',
                'private task of tech user',
                'private task assign to tech user',
                'public task',
            ],
        ];

        // tech will only see own private tasks and all private followups
        yield [
            'login'              => 'tech',
            'pass'               => 'tech',
            'ticket_id'          => $ticket->getID(),
            'options'            => [],
            'expected_followups' => [
                'private followup of normal user',
                'private followup of tech user',
                'public followup',
            ],
            'expected_tasks'     => [
                'private task of tech user',
                'private task assign to tech user',
                'public task',
            ],
        ];

        // normal will only see own private followups/tasks + private followups/tasks from its group
        yield [
            'login'              => 'normal',
            'pass'               => 'normal',
            'ticket_id'          => $ticket->getID(),
            'options'            => [],
            'expected_followups' => [
                'private followup of normal user',
                'public followup',
            ],
            'expected_tasks'     => [
                'private task assigned to see group',
                'private task assigned to normal user',
                'private task of normal user',
                'public task',
            ],
        ];

        // post-only will only see public followup/tasks
        yield [
            'login'              => 'post-only',
            'pass'               => 'postonly',
            'ticket_id'          => $ticket->getID(),
            'options'            => [],
            'expected_followups' => [
                'public followup',
            ],
            'expected_tasks'     => [
                'public task',
            ],
        ];

        foreach ([null => null, 'post-only' => 'postonly', 'tech' => 'tech'] as $login => $pass) {
            // usage of `check_view_rights` should produce the same result whoever is logged-in (used for notifications)
            yield [
                'login'              => $login,
                'pass'               => $pass,
                'ticket_id'          => $ticket->getID(),
                'options'            => [
                    'check_view_rights'  => false,
                    'hide_private_items' => false,
                ],
                'expected_followups' => [
                    'private followup of normal user',
                    'private followup of tech user',
                    'public followup',
                ],
                'expected_tasks'     => [
                    'private task assigned to see group',
                    'private task assigned to normal user',
                    'private task of normal user',
                    'private task of tech user',
                    'private task assign to tech user',
                    'public task',
                ],
            ];

            // usage of `check_view_rights` should produce the same result whoever is logged-in (used for notifications)
            yield [
                'login'              => $login,
                'pass'               => $pass,
                'ticket_id'          => $ticket->getID(),
                'options'            => [
                    'check_view_rights'  => false,
                    'hide_private_items' => true,
                ],
                'expected_followups' => [
                    'public followup',
                ],
                'expected_tasks'     => [
                    'public task',
                ],
            ];
        }
    }

    public function testGetTimelineItems(): void
    {
        $provider = $this->timelineItemsProvider();
        foreach ($provider as $row) {
            $login = $row['login'] ?? null;
            $pass = $row['pass'] ?? null;
            $ticket_id = $row['ticket_id'];
            $options = $row['options'];
            $expected_followups = $row['expected_followups'];
            $expected_tasks = $row['expected_tasks'];

            if ($pass !== null) {
                $this->login($login, $pass);
            } else {
                $this->resetSession();
            }

            $ticket = new Ticket();
            $this->assertTrue($ticket->getFromDB($ticket_id));

            $this->assertIsArray($timeline = $ticket->getTimelineItems($options));

            $followups_content = array_map(
                fn($entry) => $entry['item']['content'],
                array_values(
                    array_filter(
                        $timeline,
                        fn($entry) => $entry['type'] === ITILFollowup::class
                    )
                ),
            );
            $this->assertEquals($expected_followups, $followups_content);

            $tasks_content = array_map(
                fn($entry) => $entry['item']['content'],
                array_values(
                    array_filter(
                        $timeline,
                        fn($entry) => $entry['type'] === \TicketTask::class
                    )
                ),
            );
            $this->assertEquals($expected_tasks, $tasks_content);

            $has_weblink = false;
            foreach ($timeline as $entry) {
                if (
                    $entry['type'] === \TicketTask::class
                    && isset($entry['item']['content'])
                    && $entry['item']['content'] !== 'private task assigned to normal user'
                ) {
                    $this->assertArrayHasKey('documents', $entry);
                }
                if ($entry['type'] === \Document_Item::class && !empty($entry['item']['link'])) {
                    $this->assertFalse($entry['_is_image']);
                    $has_weblink = true;
                }
            }
            $this->assertTrue($has_weblink);
        }
    }

    /**
     * Check that when a ticket has multiple timeline items with the same creation date, they are ordered by ID
     * @return void
     * @see https://github.com/glpi-project/glpi/issues/15761
     */
    public function testGetTimelineItemsSameDate()
    {
        $this->login();

        $ticket = new Ticket();
        $this->assertGreaterThan(
            0,
            $tickets_id = $ticket->add([
                'name' => __FUNCTION__,
                'content' => __FUNCTION__,
                'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            ])
        );

        $task = new \TicketTask();
        $date = date('Y-m-d H:i:s');
        // Create one task with a different creation date after the others
        $this->assertGreaterThan(
            0,
            $task->add([
                'tickets_id' => $tickets_id,
                'content' => __FUNCTION__ . 'after',
                'date_creation' => date('Y-m-d H:i:s', strtotime('+1 second', strtotime($date))),
            ])
        );
        // Create one task with a different creation date before the others
        $this->assertGreaterThan(
            0,
            $task->add([
                'tickets_id' => $tickets_id,
                'content' => __FUNCTION__ . 'before',
                'date_creation' => date('Y-m-d H:i:s', strtotime('-1 second', strtotime($date))),
            ])
        );
        for ($i = 0; $i < 20; $i++) {
            $this->assertGreaterThan(
                0,
                $task->add([
                    'tickets_id' => $tickets_id,
                    'content' => __FUNCTION__,
                    'date_creation' => $date,
                ])
            );
        }

        $this->assertTrue($ticket->getFromDB($tickets_id));
        $timeline_items = $ticket->getTimelineItems();

        // Ensure that the tasks are ordered by creation date. And, if they have the same creation date, by ID
        $tasks = array_values(array_filter($timeline_items, static fn($entry) => $entry['type'] === \TicketTask::class));
        // Check tasks are in order of creation date
        $creation_dates = array_map(static fn($entry) => $entry['item']['date_creation'], $tasks);
        $sorted_dates = $creation_dates;
        sort($sorted_dates);
        $this->assertEquals($sorted_dates, $creation_dates);
        // Check tasks with same creation date are ordered by ID
        $same_date_tasks = array_filter($tasks, static fn($entry) => $entry['item']['date_creation'] === $date);
        $ids = array_map(static fn($entry) => $entry['item']['id'], $same_date_tasks);
        $sorted_ids = $ids;
        sort($sorted_ids, SORT_NUMERIC);
        $this->assertEquals(array_values($sorted_ids), array_values($ids));

        // Check reverse timeline order
        $timeline_items = $ticket->getTimelineItems(['sort_by_date_desc' => true]);
        $tasks = array_values(array_filter($timeline_items, static fn($entry) => $entry['type'] === \TicketTask::class));
        $creation_dates = array_map(static fn($entry) => $entry['item']['date_creation'], $tasks);
        $sorted_dates = $creation_dates;
        sort($sorted_dates);
        $sorted_dates = array_reverse($sorted_dates);
        $this->assertEquals($sorted_dates, $creation_dates);
        $same_date_tasks = array_filter($tasks, static fn($entry) => $entry['item']['date_creation'] === $date);
        $ids = array_map(static fn($entry) => $entry['item']['id'], $same_date_tasks);
        $sorted_ids = $ids;
        sort($sorted_ids, SORT_NUMERIC);
        $sorted_ids = array_reverse($sorted_ids);
        $this->assertEquals(array_values($sorted_ids), array_values($ids));
    }

    /**
     * Data provider for the testCountActors function
     *
     * @return iterable
     */
    protected function testCountActorsProvider(): iterable
    {
        $root = getItemByTypeName('Entity', '_test_root_entity', true);

        // Get tests users
        $user_1 = getItemByTypeName('User', 'glpi', true);
        $user_2 = getItemByTypeName('User', 'tech', true);

        // Create groups
        $this->createItems('Group', [
            [
                'name' => 'Group 1',
                'entities_id' => $root,
            ],
            [
                'name' => 'Group 2',
                'entities_id' => $root,
            ],
        ]);
        $group_1 = getItemByTypeName('Group', 'Group 1', true);
        $group_2 = getItemByTypeName('Group', 'Group 2', true);

        // Create suppliers
        $this->createItems('Supplier', [
            [
                'name' => 'Supplier 1',
                'entities_id' => $root,
            ],
            [
                'name' => 'Supplier 2',
                'entities_id' => $root,
            ],
        ]);
        $supplier_1 = getItemByTypeName('Supplier', 'Supplier 1', true);
        $supplier_2 = getItemByTypeName('Supplier', 'Supplier 2', true);

        // Run tests cases
        $ticket = $this->createItem('Ticket', [
            'name'        => 'Ticket supplier 1 + supplier 2',
            'content'     => '',
            'entities_id' => $root,
            '_actors'     => [],
        ]);
        yield [$ticket, 0];

        $ticket = $this->createItem('Ticket', [
            'name'        => 'Ticket supplier 1 + supplier 2',
            'content'     => '',
            'entities_id' => $root,
            '_actors'     => [
                'assign' => [
                    ['itemtype' => 'Group', 'items_id' => $group_1],
                ],
            ],
        ]);
        yield [$ticket, 1];

        $ticket = $this->createItem('Ticket', [
            'name'        => 'Ticket supplier 1 + supplier 2',
            'content'     => '',
            'entities_id' => $root,
            '_actors'     => [
                'assign' => [
                    ['itemtype' => 'Group', 'items_id' => $group_1],
                    ['itemtype' => 'Supplier', 'items_id' => $supplier_1],
                    ['itemtype' => 'Supplier', 'items_id' => $supplier_2],
                ],
            ],
        ]);
        yield [$ticket, 3];

        $ticket = $this->createItem('Ticket', [
            'name'        => 'Ticket supplier 1 + supplier 2',
            'content'     => '',
            'entities_id' => $root,
            '_actors'     => [
                'assign' => [
                    ['itemtype' => 'Group', 'items_id' => $group_1],
                    ['itemtype' => 'Supplier', 'items_id' => $supplier_1],
                    ['itemtype' => 'Supplier', 'items_id' => $supplier_2],
                ],
                'observer' => [
                    ['itemtype' => 'Group', 'items_id' => $group_2],
                ],
            ],
        ]);
        yield [$ticket, 4];

        $ticket = $this->createItem('Ticket', [
            'name'        => 'Ticket supplier 1 + supplier 2',
            'content'     => '',
            'entities_id' => $root,
            '_actors'     => [
                'assign' => [
                    ['itemtype' => 'Group', 'items_id' => $group_1],
                    ['itemtype' => 'Supplier', 'items_id' => $supplier_1],
                    ['itemtype' => 'Supplier', 'items_id' => $supplier_2],
                ],
                'observer' => [
                    ['itemtype' => 'Group', 'items_id' => $group_2],
                ],
                'requester' => [
                    ['itemtype' => 'User', 'items_id' => $user_1],
                ],
            ],
        ]);
        yield [$ticket, 5];

        $ticket = $this->createItem('Ticket', [
            'name'        => 'Ticket supplier 1 + supplier 2',
            'content'     => '',
            'entities_id' => $root,
            '_actors'     => [
                'assign' => [
                    ['itemtype' => 'Group', 'items_id' => $group_1],
                    ['itemtype' => 'Supplier', 'items_id' => $supplier_1],
                    ['itemtype' => 'Supplier', 'items_id' => $supplier_2],
                ],
                'observer' => [
                    ['itemtype' => 'Group', 'items_id' => $group_2],
                ],
                'requester' => [
                    ['itemtype' => 'User', 'items_id' => $user_1],
                    ['itemtype' => 'User', 'items_id' => $user_2],
                ],
            ],
        ]);
        yield [$ticket, 6];
    }

    /**
     * Test the testCountActors method
     *
     * @return void
     */
    public function testCountActors(): void
    {
        $this->login();
        $provider = $this->testCountActorsProvider();
        foreach ($provider as $row) {
            /** @var Ticket $ticket */
            $ticket = $row[0];
            /** @var int $expected */
            $expected = $row[1];
            $this->assertEquals($expected, $ticket->countActors());
        }
    }

    /**
     * Data provider for the testActorsMagicProperties function
     *
     * @return iterable
     */
    protected function testActorsMagicPropertiesProvider(): iterable
    {
        $this->login();

        $root = getItemByTypeName('Entity', '_test_root_entity', true);

        // Get tests users
        $user_1 = getItemByTypeName('User', 'glpi', true);
        $user_2 = getItemByTypeName('User', 'tech', true);

        // Create tests groups
        $this->createItems('Group', [
            [
                'name' => 'Group 1',
                'entities_id' => $root,
            ],
            [
                'name' => 'Group 2',
                'entities_id' => $root,
            ],
        ]);
        $group_1 = getItemByTypeName('Group', 'Group 1', true);
        $group_2 = getItemByTypeName('Group', 'Group 2', true);

        // Create tests suppliers
        $this->createItems('Supplier', [
            [
                'name' => 'Supplier 1',
                'entities_id' => $root,
            ],
            [
                'name' => 'Supplier 2',
                'entities_id' => $root,
            ],
        ]);
        $supplier_1 = getItemByTypeName('Supplier', 'Supplier 1', true);
        $supplier_2 = getItemByTypeName('Supplier', 'Supplier 2', true);

        // Case 1: ticket without actors
        $ticket_1 = $this->createItem('Ticket', [
            'name'        => 'Ticket 1',
            'content'     => '',
            'entities_id' => $root,
            '_actors'     => [],
        ]);
        yield [$ticket_1, [], [], []];

        // Case 2: add actors to our ticket
        $this->updateItem('Ticket', $ticket_1->getID(), [
            '_actors'     => [
                'assign' => [
                    ['itemtype' => 'Group', 'items_id' => $group_1],
                    ['itemtype' => 'Supplier', 'items_id' => $supplier_1],
                    ['itemtype' => 'Supplier', 'items_id' => $supplier_2],
                ],
                'observer' => [
                    ['itemtype' => 'Group', 'items_id' => $group_2],
                ],
                'requester' => [
                    ['itemtype' => 'User', 'items_id' => $user_1],
                    ['itemtype' => 'User', 'items_id' => $user_2],
                ],
            ],
        ]);
        $ticket_1->getFromDB($ticket_1->getID());
        yield [
            $ticket_1,
            [CommonITILActor::REQUESTER => [$user_1, $user_2]],
            [
                CommonITILActor::ASSIGN => [$group_1,],
                CommonITILActor::OBSERVER => [$group_2],
            ],
            [CommonITILActor::ASSIGN => [$supplier_1, $supplier_2]],
        ];

        // Case 3: create another ticket directly with actors
        $ticket_2 = $this->createItem('Ticket', [
            'name'        => 'Ticket 2',
            'content'     => '',
            'entities_id' => $root,
            '_actors'     => [
                'assign' => [
                    ['itemtype' => 'User', 'items_id' => $user_1],
                    ['itemtype' => 'User', 'items_id' => $user_2],
                    ['itemtype' => 'Group', 'items_id' => $group_1],
                    ['itemtype' => 'Group', 'items_id' => $group_2],
                    ['itemtype' => 'Supplier', 'items_id' => $supplier_1],
                    ['itemtype' => 'Supplier', 'items_id' => $supplier_2],
                ],
            ],
        ]);
        yield [
            $ticket_2,
            [CommonITILActor::ASSIGN => [$user_1, $user_2]],
            [CommonITILActor::ASSIGN => [$group_1, $group_2]],
            [CommonITILActor::ASSIGN => [$supplier_1, $supplier_2]],
        ];

        // Case 4: load ticket 2 into ticket 1 variable (simulate reusing an object for multiple rows)
        $ticket_1->getFromDb($ticket_2->getID());
        yield [
            $ticket_1,
            [CommonITILActor::ASSIGN => [$user_1, $user_2]],
            [CommonITILActor::ASSIGN => [$group_1, $group_2]],
            [CommonITILActor::ASSIGN => [$supplier_1, $supplier_2]],
        ];
    }

    /**
     * Test the magic properties used to lazy load actors
     *
     * @return void
     */
    public function testActorsMagicProperties()
    {
        $provider = $this->testActorsMagicPropertiesProvider();
        foreach ($provider as $row) {
            $ticket = $row[0];
            $expected_users = $row[1];
            $expected_groups = $row[2];
            $expected_suppliers = $row[3];

            $actors = [
                User::class => $ticket->users,
                Group::class => $ticket->groups,
                Supplier::class => $ticket->suppliers,
            ];

            // Simplify data to be able to compare it easily to our expected values
            $simplied_actors = [
                User::class => [],
                Group::class => [],
                Supplier::class => [],
            ];
            foreach ($actors as $itemtype => $actor_types) {
                foreach ($actor_types as $actor_type => $values) {
                    // Extract users_id / groups_id / suppliers_id
                    $simplied_actors[$itemtype][$actor_type] = array_column(
                        $values,
                        $itemtype::getForeignKeyField()
                    );
                }
            }

            $this->assertEquals($expected_users, $simplied_actors[User::class]);
            $this->assertEquals($expected_groups, $simplied_actors[Group::class]);
            $this->assertEquals($expected_suppliers, $simplied_actors[Supplier::class]);
        }
    }

    public function testDynamicProperties(): void
    {
        $ticket = new Ticket();

        $reporting_level = \error_reporting(E_ALL); // be sure to report deprecations
        $ticket->plugin_xxx_data = 'test';
        \error_reporting($reporting_level); // restore previous level

        $this->hasPhpLogRecordThatContains(
            'Creation of dynamic property Ticket::$plugin_xxx_data is deprecated',
            LogLevel::INFO
        );

        $this->assertTrue(property_exists($ticket, 'plugin_xxx_data'));
        $this->assertEquals('test', $ticket->plugin_xxx_data);
    }

    protected function ageSearchOptionDataProvider()
    {
        $this->login();
        $_SESSION['glpi_currenttime'] = '2023-11-27 10:00:00';

        $entity = $this->createItem(
            Entity::class,
            [
                'name' => __FUNCTION__,
            ]
        );

        $calendar = $this->createItem(
            Calendar::class,
            [
                'name' => __FUNCTION__,
            ]
        );

        $segments = $this->createItems(CalendarSegment::class, [
            ['calendars_id' => $calendar->getID(), 'day' => 0, 'begin' => '00:00:00', 'end' => '24:00:00'],
            ['calendars_id' => $calendar->getID(), 'day' => 1, 'begin' => '00:00:00', 'end' => '24:00:00'],
            ['calendars_id' => $calendar->getID(), 'day' => 2, 'begin' => '00:00:00', 'end' => '24:00:00'],
            ['calendars_id' => $calendar->getID(), 'day' => 3, 'begin' => '00:00:00', 'end' => '24:00:00'],
            ['calendars_id' => $calendar->getID(), 'day' => 4, 'begin' => '00:00:00', 'end' => '24:00:00'],
            ['calendars_id' => $calendar->getID(), 'day' => 5, 'begin' => '00:00:00', 'end' => '24:00:00'],
            ['calendars_id' => $calendar->getID(), 'day' => 6, 'begin' => '00:00:00', 'end' => '24:00:00'],
        ]);

        $calendar2 = $this->createItem(
            Calendar::class,
            [
                'name' => __FUNCTION__,
            ]
        );

        $segments2 = $this->createItems(CalendarSegment::class, [
            ['calendars_id' => $calendar2->getID(), 'day' => 0, 'begin' => '08:00:00', 'end' => '17:00:00'],
            ['calendars_id' => $calendar2->getID(), 'day' => 1, 'begin' => '08:00:00', 'end' => '17:00:00'],
            ['calendars_id' => $calendar2->getID(), 'day' => 2, 'begin' => '08:00:00', 'end' => '17:00:00'],
            ['calendars_id' => $calendar2->getID(), 'day' => 3, 'begin' => '08:00:00', 'end' => '17:00:00'],
            ['calendars_id' => $calendar2->getID(), 'day' => 4, 'begin' => '08:00:00', 'end' => '17:00:00'],
            ['calendars_id' => $calendar2->getID(), 'day' => 5, 'begin' => '08:00:00', 'end' => '17:00:00'],
            ['calendars_id' => $calendar2->getID(), 'day' => 6, 'begin' => '08:00:00', 'end' => '17:00:00'],
        ]);

        $calendar3 = $this->createItem(
            Calendar::class,
            [
                'name' => __FUNCTION__,
            ]
        );

        $calendar4 = $this->createItem(
            Calendar::class,
            [
                'name' => __FUNCTION__,
            ]
        );

        $segmetns4 = $this->createItems(CalendarSegment::class, [
            ['calendars_id' => $calendar4->getID(), 'day' => 1, 'begin' => '08:00:00', 'end' => '17:00:00'],
            ['calendars_id' => $calendar4->getID(), 'day' => 2, 'begin' => '08:00:00', 'end' => '17:00:00'],
            ['calendars_id' => $calendar4->getID(), 'day' => 4, 'begin' => '08:00:00', 'end' => '17:00:00'],
            ['calendars_id' => $calendar4->getID(), 'day' => 5, 'begin' => '08:00:00', 'end' => '17:00:00'],
        ]);

        $data = [];

        // No calendar defined, 24/24
        $data[] = [
            $entity->getID(),
            0,
            '2023-11-26 10:00:00',
            '24 hours 0 minutes',
        ];

        // Calendar with 24/24 working hours
        $data[] = [
            $entity->getID(),
            $calendar2->getID(),
            '2023-11-11 10:00:00',
            '144 hours 0 minutes',
        ];

        // Calendar with 0 working hours
        $data[] = [
            $entity->getID(),
            $calendar3->getID(),
            '2023-11-11 10:00:00',
            '0 hours 0 minutes',
        ];

        // Calendar with working hours
        $data[] = [
            $entity->getID(),
            $calendar4->getID(),
            '2023-11-10 10:47:21',
            '80 hours 12 minutes',
        ];

        // Calendar with working hours with ticket creation date outside working hours
        $data[] = [
            $entity->getID(),
            $calendar4->getID(),
            '2023-11-11 10:00:00',
            '74 hours 0 minutes',
        ];

        return $data;
    }

    public function testAgeSearchOption()
    {
        $this->login();
        $_SESSION['glpi_currenttime'] = '2023-11-27 10:00:00';

        $provider = $this->ageSearchOptionDataProvider();
        foreach ($provider as $row) {
            $entity_id = $row[0];
            $calendar_id = $row[1];
            $date = $row[2];
            $expected = $row[3];

            if ($calendar_id) {
                $this->updateItem(
                    Entity::class,
                    $entity_id,
                    [
                        'calendars_id' => $calendar_id,
                    ]
                );
            }

            $ticket = $this->createItem(
                Ticket::class,
                [
                    'name' => __FUNCTION__,
                    'content' => __FUNCTION__,
                    'entities_id' => $entity_id,
                    'date' => $date,
                ]
            );

            $this->assertEquals(
                $expected,
                $ticket->getSpecificValueToDisplay(
                    '_virtual_age',
                    [
                        'entities_id' => $entity_id,
                        'date' => $date,
                    ]
                )
            );
        }
    }

    public function testRestrictedDropdownValues()
    {
        $this->login();

        $fn_dropdown_has_id = static function ($dropdown_values, $id) use (&$fn_dropdown_has_id) {
            foreach ($dropdown_values as $dropdown_value) {
                if (isset($dropdown_value['children'])) {
                    if ($fn_dropdown_has_id($dropdown_value['children'], $id)) {
                        return true;
                    }
                } elseif ((int) $dropdown_value['id'] === (int) $id) {
                    return true;
                }
            }
            return false;
        };

        $ticket = new Ticket();
        $this->assertGreaterThan(
            0,
            $not_my_tickets_id = $ticket->add([
                'name'      => __FUNCTION__,
                'content'   => __FUNCTION__,
                'users_id'  => $_SESSION['glpiID'] + 1, // Not current user
                '_skip_auto_assign' => true,
                'entities_id' => $this->getTestRootEntity(true),
            ])
        );

        $dropdown_params = [
            'itemtype' => Ticket::class,
            'entity_restrict' => -1,
            'page_limit' => 1000,
        ];
        $idor = Session::getNewIDORToken(Ticket::class, $dropdown_params);
        $values = \Dropdown::getDropdownValue($dropdown_params + ['_idor_token' => $idor], false);
        $this->assertGreaterThan(1, count($values['results']));
        $this->assertTrue($fn_dropdown_has_id($values['results'], $not_my_tickets_id));

        // Remove permission to see all tickets
        $_SESSION['glpiactiveprofile']['ticket'] = READ;
        $idor = Session::getNewIDORToken(Ticket::class, $dropdown_params);
        $values = \Dropdown::getDropdownValue($dropdown_params + ['_idor_token' => $idor], false);
        $this->assertGreaterThan(1, count($values['results']));
        $this->assertFalse($fn_dropdown_has_id($values['results'], $not_my_tickets_id));

        // Add user as requester
        $ticket_user = new Ticket_User();
        $ticket_user->add([
            'tickets_id' => $not_my_tickets_id,
            'users_id' => $_SESSION['glpiID'],
            'type' => CommonITILActor::REQUESTER,
        ]);
        $idor = Session::getNewIDORToken(Ticket::class, $dropdown_params);
        $values = \Dropdown::getDropdownValue($dropdown_params + ['_idor_token' => $idor], false);
        $this->assertGreaterThan(1, count($values['results']));
        $this->assertTrue($fn_dropdown_has_id($values['results'], $not_my_tickets_id));
    }

    public function testGetCommonCriteria()
    {
        global $DB;

        $this->login('tech', 'tech');

        $item = new \Project();
        $item->add([
            'name' => $this->getUniqueString(),
        ]);
        $this->assertFalse($item->isNewItem());

        // Find tickets already in the entity
        $request = Ticket::getCommonCriteria();
        $request['WHERE'] = $this->callPrivateMethod(new Ticket(), 'getListForItemRestrict', $item);
        $request['WHERE'] = $request['WHERE'] + getEntitiesRestrictCriteria(Ticket::getTable());
        $result = $DB->request($request);
        $existing_tickets = $result->count();

        // Create a ticket with no actor and a valdiator
        $ticket = new Ticket();
        $ticket->add([
            'name'      => __FUNCTION__,
            'content'   => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
            'users_id_recipient' => getItemByTypeName(User::class, 'tech', true),
        ]);
        $this->assertFalse($ticket->isNewItem());

        $item_ticket = new \Item_Ticket();
        $item_ticket->add([
            'tickets_id' => $ticket->getID(),
            'itemtype' => $item->getType(),
            'items_id' => $item->getID(),
        ]);
        $this->assertFalse($item_ticket->isNewItem());

        $user = new Ticket_User();
        $users = $user->find([
            'tickets_id' => $ticket->getID(),
        ]);
        $this->assertCount(0, $users);

        $this->login('post-only', 'postonly');
        $_SESSION['glpiactiveprofile'][TicketValidation::$rightname] = TicketValidation::VALIDATEINCIDENT + TicketValidation::VALIDATEREQUEST;

        // Check the ticket is not found
        $request['WHERE'] = $this->callPrivateMethod(new Ticket(), 'getListForItemRestrict', $item);
        $request['WHERE'] = $request['WHERE'] + getEntitiesRestrictCriteria(Ticket::getTable());
        $result = $DB->request($request);
        $this->assertEquals($existing_tickets, $result->count());

        $ticket_validation = new TicketValidation();
        $ticket_validation->add([
            'tickets_id'        => $ticket->getID(),
            'entities_id'       => $ticket->fields['entities_id'],
            'itemtype_target'   => User::class,
            'items_id_target'   => Session::getLoginUserID(),
            'timeline_position' => 1,
        ]);
        $this->assertFalse($ticket_validation->isNewItem());

        // Check the ticket under valdiation is found
        $result = $DB->request($request);
        $this->assertEquals($existing_tickets + 1, $result->count());
    }

    public function testComputeDefaultValuesForAdd(): void
    {
        // OK : Inputs dates are good (no modification expected)
        $this->createItem('Ticket', [
            'name'          => __FUNCTION__,
            'content'       => __FUNCTION__,
            'status'        => CommonITILObject::CLOSED,
            'date_creation' => '2024-01-11 09:00:00',
            'date'          => '2024-01-12 09:00:00',
            'solvedate'     => '2024-01-13 09:00:00',
            'date_mod'      => '2024-01-14 09:00:00',
            'closedate'     => '2024-01-15 09:00:00',
        ]);

        // OK : All inputs dates are equal (no modification expected)
        $this->createItem('Ticket', [
            'name'          => __FUNCTION__,
            'content'       => __FUNCTION__,
            'status'        => CommonITILObject::CLOSED,
            'date_creation' => '2024-01-11 09:00:00',
            'date'          => '2024-01-11 09:00:00',
            'solvedate'     => '2024-01-11 09:00:00',
            'date_mod'      => '2024-01-11 09:00:00',
            'closedate'     => '2024-01-11 09:00:00',
        ]);

        // NOK : Bad inputs dates -> fixed durring add
        $input = [
            'name'          => __FUNCTION__,
            'content'       => __FUNCTION__,
            'status'        => CommonITILObject::CLOSED,
            'date_creation' => '2024-01-11 09:00:00',
            'date'          => '2024-01-12 09:00:00',
            'solvedate'     => '2024-01-11 09:00:00',
            'date_mod'      => '2024-01-11 09:00:00',
            'closedate'     => '2024-01-11 09:00:00',
        ];
        $expected = $input;
        $expected['solvedate'] = $input['date'];
        $expected['closedate'] = $input['date'];

        $ticket = new Ticket();
        $id = $ticket->add($input);
        $this->assertGreaterThan(0, $id);
        $this->checkInput($ticket, $id, $expected);
    }

    public static function isCategoryValidProvider(): array
    {
        $ent0 = getItemByTypeName('Entity', '_test_root_entity', true);
        $ent1 = getItemByTypeName('Entity', '_test_child_1', true);

        return [
            [
                'category_fields' => [
                    'name' => 'category_root_entity_recursive',
                    'entities_id' => $ent0,
                    'is_recursive' => 1,
                ],
                'input' => [
                    'entities_id'       => $ent0,
                    'type'              => Ticket::INCIDENT_TYPE,
                ],
                'expected' => true,
            ],
            [
                'category_fields' => [
                    'name' => 'category_root_entity_recursive',
                    'entities_id' => $ent0,
                    'is_recursive' => 1,
                ],
                'input' => [
                    'entities_id'       => $ent1,
                    'type'              => Ticket::INCIDENT_TYPE,
                ],
                'expected' => true,
            ],
            [
                'category_fields' => [
                    'name' => 'category_root_entity_no_recursive',
                    'entities_id' => $ent0,
                    'is_recursive' => 0,
                ],
                'input' => [
                    'entities_id'       => $ent0,
                    'type'              => Ticket::INCIDENT_TYPE,
                ],
                'expected' => true,
            ],
            [
                'category_fields' => [
                    'name' => 'category_root_entity_no_recursive',
                    'entities_id' => $ent0,
                    'is_recursive' => 0,
                ],
                'input' => [
                    'entities_id'       => $ent1,
                    'type'              => Ticket::INCIDENT_TYPE,
                ],
                'expected' => false,
            ],
            [
                'category_fields' => [
                    'name' => 'category_child_entity',
                    'entities_id' => $ent1,
                ],
                'input' => [
                    'entities_id'       => $ent0,
                    'type'              => Ticket::INCIDENT_TYPE,
                ],
                'expected' => false,
            ],
            [
                'category_fields' => [
                    'name' => 'category_child_entity',
                    'entities_id' => $ent1,
                ],
                'input' => [
                    'entities_id'       => $ent1,
                    'type'              => Ticket::INCIDENT_TYPE,
                ],
                'expected' => true,
            ],
            [
                'category_fields' => [
                    'name' => 'category_no_request',
                    'entities_id' => $ent0,
                    'is_recursive' => 1,
                    'is_request' => 0,
                ],
                'input' => [
                    'entities_id'       => $ent0,
                    'type'              => Ticket::INCIDENT_TYPE,
                ],
                'expected' => true,
            ],
            [
                'category_fields' => [
                    'name' => 'category_no_request',
                    'entities_id' => $ent0,
                    'is_recursive' => 1,
                    'is_request' => 0,
                ],
                'input' => [
                    'entities_id'       => $ent0,
                    'type'              => Ticket::DEMAND_TYPE,
                ],
                'expected' => false,
            ],
            [
                'category_fields' => [
                    'name' => 'category_no_incident',
                    'entities_id' => $ent0,
                    'is_recursive' => 1,
                    'is_incident' => 0,
                ],
                'input' => [
                    'entities_id'       => $ent0,
                    'type'              => Ticket::INCIDENT_TYPE,
                ],
                'expected' => false,
            ],
            [
                'category_fields' => [
                    'name' => 'category_no_incident',
                    'entities_id' => $ent0,
                    'is_recursive' => 1,
                    'is_incident' => 0,
                ],
                'input' => [
                    'entities_id'       => $ent0,
                    'type'              => Ticket::DEMAND_TYPE,
                ],
                'expected' => true,
            ],
        ];
    }

    #[DataProvider('isCategoryValidProvider')]
    public function testIsCategoryValid(array $category_fields, array $input, bool $expected): void
    {
        $category = $this->createItem('ITILCategory', $category_fields);
        $input['itilcategories_id'] = $category->getID();
        $this->assertSame($expected, Ticket::isCategoryValid($input));
    }

    public function testCanAssign()
    {
        $this->login();

        $ticket = new Ticket();
        $tickets_id = $ticket->add([
            'name' => __FUNCTION__,
            'content' => __FUNCTION__,
            '_skip_auto_assign' => true,
            '_actors' => [
                'assign' => [
                    [
                        'itemtype'  => 'User',
                        'items_id'  => getItemByTypeName('User', 'tech', true),
                        'use_notification' => 0,
                        'alternative_email' => '',
                    ],
                ],
            ],
        ]);
        $this->assertGreaterThan(0, $tickets_id);
        $ticket->loadActors();
        $this->assertEquals(1, $ticket->countUsers(CommonITILActor::ASSIGN));

        // Assigning technician during creation of closed ticket should work
        $tickets_id = $ticket->add([
            'name' => __FUNCTION__,
            'content' => __FUNCTION__,
            'status' => CommonITILObject::CLOSED,
            '_actors' => [
                'assign' => [
                    [
                        'itemtype'  => 'User',
                        'items_id'  => getItemByTypeName('User', 'tech', true),
                        'use_notification' => 0,
                        'alternative_email' => '',
                    ],
                ],
            ],
            '_skip_auto_assign' => true,
        ]);
        $this->assertGreaterThan(0, $tickets_id);
        $ticket->loadActors();
        $this->assertEquals(1, $ticket->countUsers(CommonITILActor::ASSIGN));
        $this->assertEquals(CommonITILObject::CLOSED, $ticket->fields['status']);

        // Assigning technician in same update as closing should work
        $tickets_id = $ticket->add([
            'name' => __FUNCTION__,
            'content' => __FUNCTION__,
            '_skip_auto_assign' => true,
        ]);
        $this->assertGreaterThan(0, $tickets_id);
        $ticket->loadActors();
        $this->assertEquals(0, $ticket->countUsers(CommonITILActor::ASSIGN));
        $ticket->update([
            'id' => $tickets_id,
            'status' => CommonITILObject::CLOSED,
            '_actors' => [
                'assign' => [
                    [
                        'itemtype'  => 'User',
                        'items_id'  => getItemByTypeName('User', 'tech', true),
                        'use_notification' => 0,
                        'alternative_email' => '',
                    ],
                ],
            ],
        ]);
        $ticket->loadActors();
        $this->assertEquals(1, $ticket->countUsers(CommonITILActor::ASSIGN));
        $this->assertEquals(CommonITILObject::CLOSED, $ticket->fields['status']);

        // Assigning technician after ticket is already closed should be blocked
        $tickets_id = $ticket->add([
            'name' => __FUNCTION__,
            'content' => __FUNCTION__,
            'status' => CommonITILObject::CLOSED,
            '_skip_auto_assign' => true,
        ]);
        $this->assertGreaterThan(0, $tickets_id);
        $ticket->loadActors();
        $this->assertEquals(0, $ticket->countUsers(CommonITILActor::ASSIGN));
        $this->assertFalse($ticket->update([
            'id' => $tickets_id,
            '_actors' => [
                'assign' => [
                    [
                        'itemtype'  => 'User',
                        'items_id'  => getItemByTypeName('User', 'tech', true),
                        'use_notification' => 0,
                        'alternative_email' => '',
                    ],
                ],
            ],
        ]));
        $ticket->loadActors();
        $this->assertEquals(0, $ticket->countUsers(CommonITILActor::ASSIGN));
    }

    public function testDoNotComputeStatusFollowup()
    {
        $this->login('glpi', 'glpi');

        $user1 = new User();
        $user1->getFromDBbyName('glpi');
        $this->assertGreaterThan(0, $user1->getID());

        $user2 = new User();
        $user2->getFromDBbyName('tech');
        $this->assertGreaterThan(0, $user2->getID());

        $ticket = new Ticket();
        // Create ticket with two actors (requester and technician)
        $tickets_id = $ticket->add([
            'name' => __FUNCTION__,
            'content' => __FUNCTION__,
            'status' => CommonITILObject::WAITING,
            '_actors' => [
                'requester' => [
                    [
                        'items_id' => $user1->getID(),
                        'itemtype' => 'User',
                    ],
                ],
                'assign' => [
                    [
                        'items_id' => $user2->getID(),
                        'itemtype' => 'User',
                    ],
                ],
            ],
        ]);
        $this->assertGreaterThan(0, $tickets_id);

        $this->createItem('ITILFollowup', [
            'itemtype'               => $ticket::getType(),
            'items_id'               => $tickets_id,
            'content'                => 'do not compute status followup content',
            'date'                   => '2015-01-01 00:00:00',
            '_do_not_compute_status' => 1,
        ]);

        $ticket = new Ticket();
        $ticket->getFromDB($tickets_id);

        $this->assertEquals(CommonITILObject::WAITING, $ticket->fields['status']);

        $this->createItem('ITILFollowup', [
            'itemtype'               => $ticket::getType(),
            'items_id'               => $tickets_id,
            'content'                => 'simple followup content',
            'date'                   => '2015-01-01 00:00:00',
        ]);

        $ticket = new Ticket();
        $ticket->getFromDB($tickets_id);

        $this->assertEquals(CommonITILObject::ASSIGNED, $ticket->fields['status']);
    }

    public function testTechniciansDontSeeSolvedTicketsByDefault(): void
    {
        // Make sure the tested profile does not have the right to see all the
        // tickets to increase the test fidelity.
        $technician_profile = getItemByTypeName(Profile::class, 'Technician', true);
        $right = new ProfileRight();
        $right->getFromDBByCrit([
            'profiles_id' => $technician_profile,
            'name'        => Ticket::$rightname,
        ]);
        $this->updateItem(ProfileRight::class, $right->getID(), [
            'rights' => $right->fields['rights'] & ~Ticket::READALL,
        ]);

        // Need to login before creating the tickets to make sure they will be visible for our user.
        $this->login('tech', 'tech');

        // Arrange: create 3 open tickets and 2 solved.
        $entity_id = $this->getTestRootEntity(true);
        $to_create = [
            __FUNCTION__ . ' 1' => CommonITILObject::INCOMING,
            __FUNCTION__ . ' 2' => CommonITILObject::INCOMING,
            __FUNCTION__ . ' 3' => CommonITILObject::INCOMING,
            __FUNCTION__ . ' 4' => CommonITILObject::SOLVED,
            __FUNCTION__ . ' 5' => CommonITILObject::SOLVED,
        ];
        foreach ($to_create as $name => $status) {
            $this->createItem(Ticket::class, [
                'name'        => $name,
                'content'     => '...',
                'status'      => $status,
                'entities_id' => $entity_id,
            ]);
        }

        // Act: login as "tech" and get tickets using the default search request
        $criteria = Ticket::getDefaultSearchRequest();
        $results = Search::getDatas(Ticket::class, $criteria);
        $count = 0;
        foreach ($results['data']['rows'] as $item) {
            if (str_starts_with($item['raw']['ITEM_Ticket_1'], __FUNCTION__)) {
                $count++;
            }
        }

        // Assert: only the non solved tickets should be found.
        $this->assertEquals(3, $count);
    }

    public function testHelpdeskUsersCanSeeSolvedTicketsByDefault(): void
    {
        // Need to login before creating the tickets to make sure they will be visible for our user.
        $this->login('post-only', 'postonly');

        // Arrange: create 3 open tickets, 2 solved and 1 closed.
        $entity_id = $this->getTestRootEntity(true);
        $to_create = [
            'Ticket 1' => CommonITILObject::INCOMING,
            'Ticket 2' => CommonITILObject::INCOMING,
            'Ticket 3' => CommonITILObject::INCOMING,
            'Ticket 4' => CommonITILObject::SOLVED,
            'Ticket 5' => CommonITILObject::SOLVED,
            'Ticket 6' => CommonITILObject::CLOSED,
        ];
        foreach ($to_create as $name => $status) {
            $this->createItem(Ticket::class, [
                'name'        => $name,
                'content'     => '...',
                'status'      => $status,
                'entities_id' => $entity_id,
            ]);
        }

        // Act: Get tickets using the default search request
        $criteria = Ticket::getDefaultSearchRequest();
        $results = Search::getDatas(Ticket::class, $criteria);

        // Assert: only the non closed tickets should be found.
        $this->assertEquals(5, $results['data']['totalcount']);
    }

    public function testConditionalSearchOptions()
    {
        $this->login();
        $this->assertArrayHasKey('111', SearchOption::getCleanedOptions(Ticket::class));

        $this->login('post-only', 'postonly');
        SearchOption::clearSearchOptionCache(Ticket::class);
        $this->assertArrayNotHasKey('111', SearchOption::getCleanedOptions(Ticket::class));
    }

    public function testShowSubForm(): void
    {
        // Arrange: create a ticket
        $ticket = $this->createItem(Ticket::class, [
            'name' => 'My ticket',
            'content' => 'My content',
            'entities_id' => $this->getTestRootEntity(only_id: true),
        ]);

        // Act: render sub form for this ticket
        $this->login();
        ob_start();
        Ticket::showSubForm($ticket, $ticket->getId(), [
            // Note: these parameters are ugly (ticket is both the target and
            // the parent somehow) but this is how its called from the actual
            // front files so we need to replicate it.
            'parent' => $ticket,
            'tickets_id' => $ticket->getID(),
        ]);
        $html = ob_get_clean();

        // Assert: make sure some html was generated
        $crawler = new Crawler($html);
        $this->assertCount(1, $crawler->filter('input[name="name"]'));
        $this->assertCount(1, $crawler->filter('textarea[name="content"]'));
    }

    public static function canAddDocumentProvider(): iterable
    {
        yield [
            'profilerights' => [
                'followup' => 0,
                'ticket'   => 0,
                'document' => 0,
            ],
            'expected' => false,
        ];

        yield [
            'profilerights' => [
                'followup' => ITILFollowup::ADDMYTICKET,
                'ticket'   => 0,
                'document' => 0,
            ],
            'expected' => true,
        ];

        yield [
            'profilerights' => [
                'followup' => 0,
                'ticket'   => UPDATE,
                'document' => 0,
            ],
            'expected' => false,
        ];

        yield [
            'profilerights' => [
                'followup' => 0,
                'ticket'   => 0,
                'document' => CREATE,
            ],
            'expected' => true, // requester can always add docs if the ticket is not modified
        ];

        yield [
            'profilerights' => [
                'followup' => ITILFollowup::ADDMYTICKET,
                'ticket'   => UPDATE,
                'document' => 0,
            ],
            'expected' => true,
        ];

        yield [
            'profilerights' => [
                'followup' => ITILFollowup::ADDMYTICKET,
                'ticket'   => 0,
                'document' => CREATE,
            ],
            'expected' => true,
        ];

        yield [
            'profilerights' => [
                'followup' => 0,
                'ticket'   => CREATE,
                'document' => CREATE,
            ],
            'expected' => true, // requester can always add docs if the ticket is not modified
        ];
    }

    #[DataProvider('canAddDocumentProvider')]
    public function testCanAddDocument(array $profilerights, bool $expected): void
    {
        global $DB;

        foreach ($profilerights as $right => $value) {
            $this->assertTrue($DB->update(
                'glpi_profilerights',
                ['rights' => $value],
                [
                    'profiles_id'  => 4,
                    'name'         => $right,
                ]
            ));
        }

        $this->login();

        $ticket = $this->createItem(\Change::class, [
            'name' => 'Ticket Test',
            'content' => 'Ticket content',
            '_actors' => [
                'requester' => [
                    [
                        'itemtype'  => 'User',
                        'items_id'  => Session::getLoginUserID(),
                    ],
                ],
            ],
        ]);

        $input = ['itemtype' => Ticket::class, 'items_id' => $ticket->getID()];
        $doc = new \Document();
        $this->assertEquals($expected, $doc->can(-1, CREATE, $input));
    }

    /**
     * date & date_creation field initial value test
     *
     * - use the current time is not set
     * - use the value provided otherwise
     *
     * date field can be changed in front office
     * date_creation cannot be changed in front office, it is not supposed to be changed.
     */
    public function testDateFieldsInitialValues(): void
    {
        // test 1 : date & date_creation field is not set : current time is used
        $now = $this->setCurrentTime('2023-11-27 02:11:44');
        $ticket = $this->createTicket();

        $this->assertEquals($now->format('Y-m-d H:i:s'), $ticket->fields['date']);
        $this->assertEquals($now->format('Y-m-d H:i:s'), $ticket->fields['date_creation']);

        // test 2 : date & date_creation set : provided values used
        $provided_date = '2023-11-27 10:00:00';
        $ticket = $this->createTicket(['date' => $provided_date, 'date_creation' => $provided_date]);
        $this->assertEquals($provided_date, $ticket->fields['date']);
        $this->assertEquals($provided_date, $ticket->fields['date_creation']);
    }

    public function testRequesterHaveDoubleSolvedTicketNotification()
    {
        global $CFG_GLPI;
        $CFG_GLPI['use_notifications'] = 1;
        $CFG_GLPI['notifications_mailing'] = 1;

        $this->login('glpi', 'glpi');

        $user = getItemByTypeName(User::class, 'tech');

        $itilsolution_template = $this->createItem(
            \SolutionTemplate::class,
            [
                'entities_id' => 0,
                'name' => 'ITILsolution Template',
                'content' => 'ITILsolution Content',
            ]
        );

        $rule = $this->createItem(
            Rule::class,
            [
                'entities_id' => 0,
                'name' => 'Rule name',
                'sub_type' => 'RuleTicket',
                'match' => 'AND',
                'is_active' => 1,
                'condition' => 3,
            ]
        );

        $this->createItem(\RuleAction::class, [
            'rules_id' => $rule->getID(),
            'action_type' => 'assign',
            'field' => 'solution_template',
            'value' => $itilsolution_template->getID(),
        ]);

        $this->createItem(\RuleCriteria::class, [
            'rules_id' => $rule->getID(),
            'criteria' => 'name',
            'condition' => Rule::PATTERN_CONTAIN,
            'pattern' => 'ITILsolution',
        ]);

        $this->createItem(\UserEmail::class, [
            'users_id' => $user->getID(),
            'is_default' => 1,
            'email' => 'tech@tech.tech',
        ]);

        //Test Notification for solved ticket with solution template rule at creation
        $ticket = $this->createItem(
            Ticket::class,
            [
                'name'        => 'ITILsolution Title',
                'content'     => '',
                'entities_id' => 0,
                '_actors'     => [
                    'requester' => [
                        ['itemtype' => 'User', 'items_id' => $user->getID(), 'use_notification' => 1],
                    ],
                ],
            ]
        );

        $queue = new \QueuedNotification();
        $this->assertTrue($queue->getFromDBByCrit([
            'itemtype' => Ticket::class,
            'items_id' => $ticket->getID(),
            'event' => 'solved',
            'mode' => 'mailing',
            'recipientname' => 'tech',
        ]));

        $this->assertTrue($queue->delete(['id' => $queue->getID()], true));

        $solution = new ITILSolution();
        $this->assertTrue($solution->getFromDBByCrit([
            'items_id' => $ticket->getID(),
            'itemtype' => Ticket::class,
            'status'   => 2,
        ]));

        // Test Notification for solved ticket with solution template rule at update
        $this->updateItem(
            ITILSolution::class,
            $solution->getID(),
            [
                'status' => 3,
            ],
        );

        $this->updateItem(
            Ticket::class,
            $ticket->getID(),
            [
                'name'        => 'ITILsolution',
                'status'      => CommonITILObject::ASSIGNED,
            ],
            ['status']
        );

        $this->assertTrue($queue->getFromDBByCrit([
            'itemtype' => Ticket::class,
            'items_id' => $ticket->getID(),
            'event' => 'solved',
            'mode' => 'mailing',
            'recipientname' => 'tech',
        ]));

        $this->assertTrue($queue->delete(['id' => $queue->getID()], true));

        $this->updateItem(
            Ticket::class,
            $ticket->getID(),
            [
                'status'      => CommonITILObject::ASSIGNED,
            ],
            ['status']
        );
        $solution = new ITILSolution();
        $this->createItem(
            ITILSolution::class,
            [
                'items_id' => $ticket->getID(),
                'itemtype' => Ticket::class,
                'content' => 'ITILsolution Content',
                'status' => 2,
            ]
        );

        $this->assertTrue($queue->getFromDBByCrit([
            'itemtype' => Ticket::class,
            'items_id' => $ticket->getID(),
            'event' => 'solved',
            'mode' => 'mailing',
            'recipientname' => 'tech',
        ]));
    }

    public function testShowCentralListSurvey()
    {
        global $DB, $GLPI_CACHE;

        $this->login();

        $DB->insert('glpi_tickets', [
            'name' => __FUNCTION__ . ' old',
            'content' => '',
            'users_id_recipient' => Session::getLoginUserID(),
            'entities_id' => getItemByTypeName('Entity', '_test_child_1', true),
            'status' => CommonITILObject::CLOSED,
            'closedate' => date('Y-m-d', \Safe\strtotime(Session::getCurrentTime() . ' - 181 days')),
        ]);
        $this->assertNotFalse($DB->insert('glpi_ticketsatisfactions', [
            'tickets_id' => $DB->insertId(),
            'date_begin' => date('Y-m-d', \Safe\strtotime(Session::getCurrentTime() . ' - 181 days')),
        ]));
        $DB->insert('glpi_tickets', [
            'name' => __FUNCTION__ . ' 1',
            'content' => '',
            'users_id_recipient' => Session::getLoginUserID(),
            'entities_id' => getItemByTypeName('Entity', '_test_child_1', true),
            'status' => CommonITILObject::CLOSED,
            'closedate' => date('Y-m-d', \Safe\strtotime(Session::getCurrentTime() . ' - 10 days')),
        ]);
        $this->assertNotFalse($DB->insert('glpi_ticketsatisfactions', [
            'tickets_id' => $DB->insertId(),
            'date_begin' => date('Y-m-d', \Safe\strtotime(Session::getCurrentTime() . ' - 10 days')),
        ]));
        $DB->insert('glpi_tickets', [
            'name' => __FUNCTION__ . ' 2',
            'content' => '',
            'users_id_recipient' => Session::getLoginUserID(),
            'entities_id' => getItemByTypeName('Entity', '_test_child_1', true),
            'status' => CommonITILObject::CLOSED,
            'closedate' => date('Y-m-d', \Safe\strtotime(Session::getCurrentTime() . ' - 5 days')),
        ]);
        $this->assertNotFalse($DB->insert('glpi_ticketsatisfactions', [
            'tickets_id' => $DB->insertId(),
            'date_begin' => date('Y-m-d', \Safe\strtotime(Session::getCurrentTime() . ' - 5 days')),
        ]));

        $this->assertNotFalse($DB->update('glpi_entities', [
            'inquest_config' => Entity::CONFIG_PARENT,
        ], [
            'id' => getItemByTypeName('Entity', '_test_child_1', true),
        ]));
        $this->assertNotFalse($DB->update('glpi_entities', [
            'inquest_config' => 1,
            'inquest_duration' => 7,
        ], [
            'id' => getItemByTypeName('Entity', '_test_root_entity', true),
        ]));
        $GLPI_CACHE->clear();

        $result = Ticket::showCentralList(0, 'survey', false, false);
        $this->assertStringContainsString(__FUNCTION__ . ' 2', $result);
        $this->assertStringNotContainsString(__FUNCTION__ . ' 1', $result);
        $this->assertStringNotContainsString(__FUNCTION__ . ' old', $result);

        $DB->update('glpi_entities', [
            'inquest_duration' => 11,
        ], [
            'id' => getItemByTypeName('Entity', '_test_root_entity', true),
        ]);
        $GLPI_CACHE->clear();
        $result = Ticket::showCentralList(0, 'survey', false, false);

        $this->assertStringContainsString(__FUNCTION__ . ' 2', $result);
        $this->assertStringContainsString(__FUNCTION__ . ' 1', $result);
        $this->assertStringNotContainsString(__FUNCTION__ . ' old', $result);

        $DB->update('glpi_entities', [
            'inquest_duration' => 200,
        ], [
            'id' => getItemByTypeName('Entity', '_test_root_entity', true),
        ]);
        $GLPI_CACHE->clear();
        $result = Ticket::showCentralList(0, 'survey', false, false);

        $this->assertStringContainsString(__FUNCTION__ . ' 2', $result);
        $this->assertStringContainsString(__FUNCTION__ . ' 1', $result);
        $this->assertStringNotContainsString(__FUNCTION__ . ' old', $result);
    }

    public function testShowListForItem(): void
    {
        $this->login('glpi', 'glpi');

        $user = getItemByTypeName(User::class, 'glpi');
        $normal = getItemByTypeName(User::class, 'normal');

        // Ticket not visible for normal user
        $ticket1_id = $this->createItem(
            Ticket::class,
            [
                'name'        => 'Test Ticket 1',
                'content'     => 'Test Ticket 1 Content',
                'entities_id' => 0,
                '_actors'     => [
                    'requester' => [
                        ['itemtype' => 'User', 'items_id' => $user->getID()],
                    ],
                ],
            ],
        )->getID();

        // Ticket visible for normal user
        $ticket2_id = $this->createItem(
            Ticket::class,
            [
                'name'        => 'Test Ticket 2',
                'content'     => 'Test Ticket 2 Content',
                'entities_id' => 0,
                '_actors'     => [
                    'requester' => [
                        ['itemtype' => 'User', 'items_id' => $user->getID()],
                    ],
                    'observer' => [
                        ['itemtype' => 'User', 'items_id' => $normal->getID()],
                    ],
                ],
            ],
        )->getID();

        $this->login('normal', 'normal');

        // Remove permission to see all tickets
        $_SESSION['glpiactiveprofile']['ticket'] = Ticket::READMY;

        ob_start();
        Ticket::showListForItem($user);
        $out = ob_get_clean();

        $crawler = new Crawler($out);
        $rows = $crawler->filter('table.table.table-hover > tbody > tr');

        $found = [];
        foreach ($rows as $row) {
            $cells = (new Crawler($row))->filter('td');
            if (!empty($cells->getNode(0))) {
                $ticket_id = trim($cells->getNode(0)->textContent);
                if (!empty($ticket_id)) {
                    $found[$ticket_id] = true;
                }
            }
        }
        $this->assertArrayNotHasKey($ticket1_id, $found);
        $this->assertArrayHasKey($ticket2_id, $found);
    }

    public function testHandleAddContracts()
    {
        $this->login();
        $contract_1 = $this->createItem(Contract::class, [
            'name' => 'Contract 1',
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $contract_2 = $this->createItem(Contract::class, [
            'name' => 'Contract 2',
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        $ticket = $this->createItem(Ticket::class, [
            'name' => 'Ticket with contracts',
            'content' => 'test',
            'entities_id' => $this->getTestRootEntity(true),
            '_contracts_id' => $contract_1->getID(),
        ]);
        $tc = new Ticket_Contract();
        $linked_contracts = array_values($tc->find(['tickets_id' => $ticket->getID()]));
        $this->assertCount(1, $linked_contracts);
        $this->assertEquals($contract_1->getID(), $linked_contracts[0]['contracts_id']);

        $ticket->update([
            'id' => $ticket->getID(),
            '_contracts_id' => $contract_2->getID(),
        ]);
        $linked_contracts = array_values($tc->find(['tickets_id' => $ticket->getID()]));
        $this->assertCount(2, $linked_contracts);
        $this->assertContains($contract_1->getID(), array_column($linked_contracts, 'contracts_id'));
        $this->assertContains($contract_2->getID(), array_column($linked_contracts, 'contracts_id'));
    }

    /**
     * Ensure that there is no error triggered when refusing solutions or reopening tickets
     * with a SLA assigned on a specific calendar.
     * @see https://github.com/glpi-project/glpi/pull/21337
     */
    public function testTicketUnsolveWithSLA()
    {
        $this->login();

        $entity_id = getItemByTypeName('Entity', '_test_root_entity', true);

        // Create a calendar with working hours from 8 a.m. to 7 p.m. Monday to Friday
        $calendar = $this->createItem(Calendar::class, ['name' => 'Calendar']);
        $this->createItems(CalendarSegment::class, [
            ['calendars_id' => $calendar->getID(), 'day' => 1, 'begin' => '08:00:00', 'end' => '18:00:00'],
            ['calendars_id' => $calendar->getID(), 'day' => 2, 'begin' => '08:00:00', 'end' => '18:00:00'],
            ['calendars_id' => $calendar->getID(), 'day' => 3, 'begin' => '08:00:00', 'end' => '18:00:00'],
            ['calendars_id' => $calendar->getID(), 'day' => 4, 'begin' => '08:00:00', 'end' => '18:00:00'],
            ['calendars_id' => $calendar->getID(), 'day' => 5, 'begin' => '08:00:00', 'end' => '18:00:00'],
        ]);

        // Create SLM/SLA
        $slm = $this->createItem(SLM::class, [
            'name'                => 'SLM',
            'entities_id'         => $entity_id,
            'is_recursive'        => true,
            'use_ticket_calendar' => false,
            'calendars_id'        => $calendar->getID(),
        ]);
        $sla = $this->createItem(SLA::class, [
            'name'                => 'SLA TTR',
            'entities_id'         => $entity_id,
            'is_recursive'        => true,
            'type'                => SLM::TTR,
            'number_time'         => 4,
            'calendars_id'        => $calendar->getID(),
            'definition_time'     => 'hour',
            'end_of_working_day'  => false,
            'slms_id'             => $slm->getID(),
            'use_ticket_calendar' => false,
        ]);

        // Create a ticket
        $this->setCurrentTime('2025-10-06 11:26:34'); // be sure to be on monday
        $ticket = $this->createItem(
            Ticket::class,
            [
                'name'          => __FUNCTION__,
                'content'       => __FUNCTION__,
                'slas_id_ttr'   => $sla->getID(),
            ]
        );

        // Add a solution
        $this->setCurrentTime('2025-10-07 09:12:48'); // add some time to consistent stats
        $solution = $this->createItem(
            ITILSolution::class,
            [
                'itemtype'  => Ticket::class,
                'items_id'  => $ticket->getID(),
                'content'   => __FUNCTION__,
            ]
        );

        $this->assertTrue($ticket->getFromDB($ticket->getID()));
        $this->assertEquals(Ticket::SOLVED, $ticket->fields['status']);
        $this->assertEquals(27974, $ticket->fields['solve_delay_stat']);
        $this->assertEquals(0, $ticket->fields['close_delay_stat']);
        $this->assertTrue($solution->getFromDB($solution->getID()));
        $this->assertSame(CommonITILValidation::WAITING, $solution->fields['status']);

        // Refuse the solution
        $this->setCurrentTime('2025-10-07 10:47:10'); // add some time to consistent stats
        $this->createItem(
            ITILFollowup::class,
            [
                'itemtype'      => Ticket::class,
                'items_id'      => $ticket->getID(),
                'add_reopen'    => '1',
                'content'       => __FUNCTION__,
            ],
            ['add_reopen']
        );

        $this->assertTrue($ticket->getFromDB($ticket->getID()));
        $this->assertEquals(Ticket::INCOMING, $ticket->fields['status']);
        $this->assertEquals(0, $ticket->fields['solve_delay_stat']);
        $this->assertEquals(0, $ticket->fields['close_delay_stat']);
        $this->assertTrue($solution->getFromDB($solution->getID()));
        $this->assertSame(CommonITILValidation::REFUSED, $solution->fields['status']);

        // Close the ticket
        $this->setCurrentTime('2025-10-08 14:17:31'); // add some time to consistent stats
        $this->updateItem(Ticket::class, $ticket->getID(), ['status' => Ticket::CLOSED]);

        $this->assertTrue($ticket->getFromDB($ticket->getID()));
        $this->assertEquals(Ticket::CLOSED, $ticket->fields['status']);
        $this->assertEquals(76595, $ticket->fields['solve_delay_stat']);
        $this->assertEquals(76595, $ticket->fields['close_delay_stat']);

        // Reopen the ticket
        $this->setCurrentTime('2025-10-08 14:24:05'); // add some time to consistent stats
        $this->createItem(
            ITILFollowup::class,
            [
                'itemtype'      => Ticket::class,
                'items_id'      => $ticket->getID(),
                'add_reopen'    => '1',
                'content'       => __FUNCTION__,
            ],
            ['add_reopen']
        );

        $this->assertTrue($ticket->getFromDB($ticket->getID()));
        $this->assertEquals(Ticket::INCOMING, $ticket->fields['status']);
        $this->assertEquals(0, $ticket->fields['solve_delay_stat']);
        $this->assertEquals(0, $ticket->fields['close_delay_stat']);
    }

    public function testSatisfactionSurveyIsDisplayedOnHelpdesk(): void
    {
        // Arrange: create a ticket with a satisfaction survey
        $this->login('post-only');
        $ticket = $this->createItem(Ticket::class, [
            'name'    => "My ticket",
            'content' => "My ticket content",
            'status'  => 6,
        ]);
        $this->createItem(TicketSatisfaction::class, [
            'tickets_id' => $ticket->getID(),
            'type' => CommonITILSatisfaction::TYPE_INTERNAL,
        ]);

        // Act: render ticket form
        ob_start();
        $ticket->showForm($ticket->getID());
        $html = ob_get_clean();

        // Assert: make sure the satisfaction form was rendered
        $crawler = new Crawler($html);
        $survey = $crawler->filter('[data-testid="survey"]');
        $this->assertNotEmpty($survey);
    }
}
