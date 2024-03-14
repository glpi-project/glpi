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

use CommonDBTM;
use CommonITILActor;
use CommonITILObject;
use Computer;
use CronTask;
use DbTestCase;
use Entity;
use Glpi\Team\Team;
use Glpi\Toolbox\Sanitizer;
use Group;
use Group_Ticket;
use ITILCategory;
use Profile_User;
use Supplier;
use Supplier_Ticket;
use Symfony\Component\DomCrawler\Crawler;
use Ticket_User;
use TicketValidation;
use User;

/* Test for inc/ticket.class.php */

class Ticket extends DbTestCase
{
    protected function addActorsProvider(): iterable
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
                            ]
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
                            ]
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
                            ]
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

    /**
     * @dataProvider addActorsProvider
     */
    public function testCreateTicketWithActors(array $actors_input, array $expected_actors): void
    {
        $this->login();

        $ticket = new \Ticket();
        $ticket_id = $ticket->add(
            [
                'name'        => 'ticket title',
                'content'     => 'a description',
                'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            ] + $actors_input
        );
        $this->integer($ticket_id)->isGreaterThan(0);

        $this->checkActors($ticket, $expected_actors);
    }


    protected function updateActorsProvider(): iterable
    {
        foreach ($this->addActorsProvider() as $params) {
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

    /**
     * @dataProvider updateActorsProvider
     */
    public function testUpdateTicketWithActors(
        array $add_actors_input,
        array $add_expected_actors,
        array $update_actors_input,
        array $update_expected_actors
    ): void {
        $this->login();

        $ticket = new \Ticket();
        $ticket_id = $ticket->add(
            [
                'name'        => 'ticket title',
                'content'     => 'a description',
                'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            ] + $add_actors_input
        );
        $this->integer($ticket_id)->isGreaterThan(0);

        $this->checkActors($ticket, $add_expected_actors);

        $this->boolean($ticket->update(['id' => $ticket_id] + $update_actors_input))->isTrue();

        $this->checkActors($ticket, $update_expected_actors);
    }

    /**
     * Check that ticket actors are matching expected actors.
     *
     * @param \Ticket $ticket
     * @param array $expected_actors
     *
     * @return void
     */
    private function checkActors(\Ticket $ticket, array $expected_actors): void
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
                $this->boolean($link_obj->getFromDBByCrit(['tickets_id' => $ticket->getID()] + $actor))
                    ->isTrue(sprintf('Actor not found: %s', json_encode($actor)));
            }
            $this->integer($link_obj->countForItem($ticket))->isEqualTo(count($expected_actors_for_itemtype));
        }
    }

    public function testTasksFromTemplate()
    {
       // 1- create a task category
        $taskcat    = new \TaskCategory();
        $taskcat_id = $taskcat->add([
            'name' => 'my task cat',
        ]);
        $this->boolean($taskcat->isNewItem())->isFalse();

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
        $this->boolean($tasktemplate->isNewItem())->isFalse();
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
        $this->boolean($tasktemplate->isNewItem())->isFalse();

       // 3 - create a ticket template with the task templates in predefined fields
        $itiltemplate    = new \TicketTemplate();
        $itiltemplate_id = $itiltemplate->add([
            'name' => 'my ticket template',
        ]);
        $this->boolean($itiltemplate->isNewItem())->isFalse();
        $ttp = new \TicketTemplatePredefinedField();
        $ttp->add([
            'tickettemplates_id' => $itiltemplate_id,
            'num'                => '175',
            'value'              => $ttA_id,
        ]);
        $this->boolean($ttp->isNewItem())->isFalse();
        $ttp->add([
            'tickettemplates_id' => $itiltemplate_id,
            'num'                => '176',
            'value'              => $ttB_id,
        ]);
        $this->boolean($ttp->isNewItem())->isFalse();

       // 4 - create a ticket category using the ticket template
        $itilcat    = new \ITILCategory();
        $itilcat_id = $itilcat->add([
            'name'                        => 'my itil category',
            'ticketltemplates_id_incident' => $itiltemplate_id,
            'tickettemplates_id_demand'   => $itiltemplate_id,
            'is_incident'                 => true,
            'is_request'                  => true,
        ]);
        $this->boolean($itilcat->isNewItem())->isFalse();

       // 5 - create a ticket using the ticket category
        $ticket     = new \Ticket();
        $tickets_id = $ticket->add([
            'name'                => 'test task template',
            'content'             => 'test task template',
            'itilcategories_id'   => $itilcat_id,
            '_tickettemplates_id' => $itiltemplate_id,
            '_tasktemplates_id'   => [$ttA_id, $ttB_id],
        ]);
        $this->boolean($ticket->isNewItem())->isFalse();

       // 6 - check creation of the tasks
        $tickettask = new \TicketTask();
        $found_tasks = $tickettask->find(['tickets_id' => $tickets_id], "id ASC");

       // 6.1 -> check first task
        $taskA = array_shift($found_tasks);
        $this->string($taskA['content'])->isIdenticalTo(Sanitizer::encodeHtmlSpecialChars('<p>my task template A</p>'));
        $this->variable($taskA['taskcategories_id'])->isEqualTo($taskcat_id);
        $this->variable($taskA['actiontime'])->isEqualTo(60);
        $this->variable($taskA['is_private'])->isEqualTo(1);
        $this->variable($taskA['users_id_tech'])->isEqualTo(2);
        $this->variable($taskA['groups_id_tech'])->isEqualTo(0);
        $this->variable($taskA['state'])->isEqualTo(\Planning::INFO);

       // 6.2 -> check second task
        $taskB = array_shift($found_tasks);
        $this->string($taskB['content'])->isIdenticalTo(Sanitizer::encodeHtmlSpecialChars('<p>my task template B</p>'));
        $this->variable($taskB['taskcategories_id'])->isEqualTo($taskcat_id);
        $this->variable($taskB['actiontime'])->isEqualTo(120);
        $this->variable($taskB['is_private'])->isEqualTo(0);
        $this->variable($taskB['users_id_tech'])->isEqualTo(2);
        $this->variable($taskB['groups_id_tech'])->isEqualTo(0);
        $this->variable($taskB['state'])->isEqualTo(\Planning::TODO);
    }

    public function testAcls()
    {
        $ticket = new \Ticket();
       //to fix an undefined index
        $_SESSION["glpiactiveprofile"]["interface"] = '';
        $this->boolean((bool)$ticket->canAdminActors())->isFalse();
        $this->boolean((bool)$ticket->canAssign())->isFalse();
        $this->boolean((bool)$ticket->canAssignToMe())->isFalse();
        $this->boolean((bool)$ticket->canUpdate())->isFalse();
        $this->boolean((bool)$ticket->canView())->isFalse();
        $this->boolean((bool)$ticket->canViewItem())->isFalse();
        $this->boolean((bool)$ticket->canSolve())->isFalse();
        $this->boolean((bool)$ticket->canApprove())->isFalse();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'content', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'name', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'priority', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'type', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'location', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canCreateItem())->isFalse();
        $this->boolean((bool)$ticket->canUpdateItem())->isFalse();
        $this->boolean((bool)$ticket->canRequesterUpdateItem())->isFalse();
        $this->boolean((bool)$ticket->canDelete())->isFalse();
        $this->boolean((bool)$ticket->canDeleteItem())->isFalse();
        $this->boolean((bool)$ticket->canAddItem('Document'))->isFalse();
        $this->boolean((bool)$ticket->canAddItem('Ticket_Cost'))->isFalse();
        $this->boolean((bool)$ticket->canAddFollowups())->isFalse();
        $this->boolean((bool)$ticket->canUserAddFollowups(\Session::getLoginUserID()))->isFalse();

        $this->login();
        $this->setEntity('Root entity', true);
        $ticket = new \Ticket();
        $this->boolean((bool)$ticket->canAdminActors())->isTrue(); //=> get 2
        $this->boolean((bool)$ticket->canAssign())->isTrue(); //=> get 8192
        $this->boolean((bool)$ticket->canAssignToMe())->isTrue();
        $this->boolean((bool)$ticket->canUpdate())->isTrue();
        $this->boolean((bool)$ticket->canView())->isTrue();
        $this->boolean((bool)$ticket->canViewItem())->isTrue();
        $this->boolean((bool)$ticket->canSolve())->isTrue();
        $this->boolean((bool)$ticket->canApprove())->isFalse();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'content', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'name', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'priority', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'type', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'location', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canCreateItem())->isTrue();
        $this->boolean((bool)$ticket->canUpdateItem())->isTrue();
        $this->boolean((bool)$ticket->canRequesterUpdateItem())->isFalse();
        $this->boolean((bool)$ticket->canDelete())->isTrue();
        $this->boolean((bool)$ticket->canDeleteItem())->isTrue();
        $this->boolean((bool)$ticket->canAddItem('Document'))->isTrue();
        $this->boolean((bool)$ticket->canAddItem('Ticket_Cost'))->isTrue();
        $this->boolean((bool)$ticket->canAddFollowups())->isTrue();
        $this->boolean((bool)$ticket->canUserAddFollowups(\Session::getLoginUserID()))->isTrue();

        $ticket = getItemByTypeName('Ticket', '_ticket01');
        $this->boolean((bool)$ticket->canAdminActors())->isTrue(); //=> get 2
        $this->boolean((bool)$ticket->canAssign())->isTrue(); //=> get 8192
        $this->boolean((bool)$ticket->canAssignToMe())->isTrue();
        $this->boolean((bool)$ticket->canUpdate())->isTrue();
        $this->boolean((bool)$ticket->canView())->isTrue();
        $this->boolean((bool)$ticket->canViewItem())->isTrue();
        $this->boolean((bool)$ticket->canSolve())->isTrue();
        $this->boolean((bool)$ticket->canApprove())->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'content', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'name', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'priority', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'type', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'location', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canCreateItem())->isTrue();
        $this->boolean((bool)$ticket->canUpdateItem())->isTrue();
        $this->boolean((bool)$ticket->canRequesterUpdateItem())->isFalse();
        $this->boolean((bool)$ticket->canDelete())->isTrue();
        $this->boolean((bool)$ticket->canDeleteItem())->isTrue();
        $this->boolean((bool)$ticket->canAddItem('Document'))->isTrue();
        $this->boolean((bool)$ticket->canAddItem('Ticket_Cost'))->isTrue();
        $this->boolean((bool)$ticket->canAddFollowups())->isTrue();
        $this->boolean((bool)$ticket->canUserAddFollowups(\Session::getLoginUserID()))->isTrue();
    }

    public function testPostOnlyAcls()
    {
        $auth = new \Auth();
        $this->boolean((bool)$auth->login('post-only', 'postonly', true))->isTrue();

        $ticket = new \Ticket();
        $this->boolean((bool)$ticket->canAdminActors())->isFalse();
        $this->boolean((bool)$ticket->canAssign())->isFalse();
        $this->boolean((bool)$ticket->canAssignToMe())->isFalse();
        $this->boolean((bool)$ticket->canUpdate())->isTrue();
        $this->boolean((bool)$ticket->canView())->isTrue();
        $this->boolean((bool)$ticket->canViewItem())->isFalse();
        $this->boolean((bool)$ticket->canSolve())->isFalse();
        $this->boolean((bool)$ticket->canApprove())->isFalse();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'content', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'name', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'priority', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'type', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'location', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canCreateItem())->isTrue();
        $this->boolean((bool)$ticket->canUpdateItem())->isFalse();
        $this->boolean((bool)$ticket->canRequesterUpdateItem())->isFalse();
        $this->boolean((bool)$ticket->canDelete())->isTrue();
        $this->boolean((bool)$ticket->canDeleteItem());
        $this->boolean((bool)$ticket->canAddItem('Document'));
        $this->boolean((bool)$ticket->canAddItem('Ticket_Cost'))->isFalse();
        $this->boolean((bool)$ticket->canAddFollowups())->isFalse();
        $this->boolean((bool)$ticket->canUserAddFollowups(\Session::getLoginUserID()))->isFalse();

        $this->integer(
            (int)$ticket->add([
                'name'    => '',
                'content' => 'A ticket to check ACLS',
                '_users_id_requester' => getItemByTypeName('User', 'post-only', true),
            ])
        )->isGreaterThan(0);

       //reload ticket from DB
        $this->boolean((bool)$ticket->getFromDB($ticket->getID()))->isTrue();
        $this->boolean((bool)$ticket->canAdminActors())->isFalse();
        $this->boolean((bool)$ticket->canAssign())->isFalse();
        $this->boolean((bool)$ticket->canAssignToMe())->isFalse();
        $this->boolean((bool)$ticket->canUpdate())->isTrue();
        $this->boolean((bool)$ticket->canView())->isTrue();
        $this->boolean((bool)$ticket->canViewItem())->isTrue();
        $this->boolean((bool)$ticket->canSolve())->isFalse();
        $this->boolean((bool)$ticket->canApprove())->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'content', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'name', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'priority', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'type', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'location', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canCreateItem())->isTrue();
        $this->boolean((bool)$ticket->canUpdateItem())->isTrue();
        $this->boolean((bool)$ticket->canRequesterUpdateItem())->isTrue();
        $this->boolean((bool)$ticket->canDelete())->isTrue();
        $this->boolean((bool)$ticket->canDeleteItem())->isTrue();
        $this->boolean((bool)$ticket->canAddItem('Document'))->isTrue();
        $this->boolean((bool)$ticket->canAddItem('Ticket_Cost'))->isTrue();
        $this->boolean((bool)$ticket->canAddFollowups())->isTrue();
        $this->boolean((bool)$ticket->canUserAddFollowups(\Session::getLoginUserID()))->isTrue();

        $uid = getItemByTypeName('User', TU_USER, true);
       //add a followup to the ticket
        $fup = new \ITILFollowup();
        $this->integer(
            (int)$fup->add([
                'itemtype'  => 'Ticket',
                'items_id'   => $ticket->getID(),
                'users_id'     => $uid,
                'content'      => 'A simple followup'
            ])
        )->isGreaterThan(0);

        $this->boolean((bool)$ticket->getFromDB($ticket->getID()))->isTrue();
        $this->boolean((bool)$ticket->canAdminActors())->isFalse();
        $this->boolean((bool)$ticket->canAssign())->isFalse();
        $this->boolean((bool)$ticket->canAssignToMe())->isFalse();
        $this->boolean((bool)$ticket->canUpdate())->isTrue();
        $this->boolean((bool)$ticket->canView())->isTrue();
        $this->boolean((bool)$ticket->canViewItem())->isTrue();
        $this->boolean((bool)$ticket->canSolve())->isFalse();
        $this->boolean((bool)$ticket->canApprove())->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'content', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'name', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'priority', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'type', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'location', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canCreateItem())->isTrue();
        $this->boolean((bool)$ticket->canUpdateItem())->isFalse();
        $this->boolean((bool)$ticket->canRequesterUpdateItem())->isFalse();
        $this->boolean((bool)$ticket->canDelete())->isTrue();
        $this->boolean((bool)$ticket->canDeleteItem())->isFalse();
        $this->boolean((bool)$ticket->canAddItem('Document'))->isTrue();
        $this->boolean((bool)$ticket->canAddItem('Ticket_Cost'))->isFalse();
        $this->boolean((bool)$ticket->canAddFollowups())->isTrue();
        $this->boolean((bool)$ticket->canUserAddFollowups(\Session::getLoginUserID()))->isTrue();
    }

    public function testTechAcls()
    {
        $auth = new \Auth();
        $this->boolean((bool)$auth->login('tech', 'tech', true))->isTrue();

        $ticket = new \Ticket();
        $this->boolean((bool)$ticket->canAdminActors())->isTrue();
        $this->boolean((bool)$ticket->canAssign())->isFalse();
        $this->boolean((bool)$ticket->canAssignToMe())->isTrue();
        $this->boolean((bool)$ticket->canUpdate())->isTrue();
        $this->boolean((bool)$ticket->canView())->isTrue();
        $this->boolean((bool)$ticket->canViewItem())->isTrue();
        $this->boolean((bool)$ticket->canSolve())->isTrue();
        $this->boolean((bool)$ticket->canApprove())->isFalse();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'content', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'name', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'priority', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'type', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'location', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canCreateItem())->isTrue();
        $this->boolean((bool)$ticket->canUpdateItem())->isTrue();
        $this->boolean((bool)$ticket->canRequesterUpdateItem())->isFalse();
        $this->boolean((bool)$ticket->canDelete())->isFalse();
        $this->boolean((bool)$ticket->canDeleteItem())->isFalse();
        $this->boolean((bool)$ticket->canAddItem('Document'))->isTrue();
        $this->boolean((bool)$ticket->canAddItem('Ticket_Cost'))->isTrue();
        $this->boolean((bool)$ticket->canAddFollowups())->isTrue();
        $this->boolean((bool)$ticket->canUserAddFollowups(\Session::getLoginUserID()))->isTrue();

        $this->integer(
            (int)$ticket->add([
                'name'    => '',
                'content' => 'A ticket to check ACLS',
                '_users_id_assign' => getItemByTypeName("User", 'tech', true),
            ])
        )->isGreaterThan(0);

       //reload ticket from DB
        $this->boolean((bool)$ticket->getFromDB($ticket->getID()))->isTrue();
        $this->boolean((bool)$ticket->canAdminActors())->isTrue();
        $this->boolean((bool)$ticket->canAssign())->isFalse();
        $this->boolean((bool)$ticket->canAssignToMe())->isFalse();
        $this->boolean((bool)$ticket->canUpdate())->isTrue();
        $this->boolean((bool)$ticket->canView())->isTrue();
        $this->boolean((bool)$ticket->canViewItem())->isTrue();
        $this->boolean((bool)$ticket->canSolve())->isTrue();
        $this->boolean((bool)$ticket->canApprove())->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'content', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'name', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'priority', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'type', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'location', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canCreateItem())->isTrue();
        $this->boolean((bool)$ticket->canUpdateItem())->isTrue();
        $this->boolean((bool)$ticket->canRequesterUpdateItem())->isTrue();
        $this->boolean((bool)$ticket->canDelete())->isFalse();
        $this->boolean((bool)$ticket->canDeleteItem())->isFalse();
        $this->boolean((bool)$ticket->canAddItem('Document'))->isTrue();
        $this->boolean((bool)$ticket->canAddItem('Ticket_Cost'))->isTrue();
        $this->boolean((bool)$ticket->canAddFollowups())->isTrue();
        $this->boolean((bool)$ticket->canUserAddFollowups(\Session::getLoginUserID()))->isTrue();

        $uid = getItemByTypeName('User', TU_USER, true);
       //add a followup to the ticket
        $fup = new \ITILFollowup();
        $this->integer(
            (int)$fup->add([
                'itemtype'  => 'Ticket',
                'items_id'   => $ticket->getID(),
                'users_id'     => $uid,
                'content'      => 'A simple followup'
            ])
        )->isGreaterThan(0);

        $this->boolean((bool)$ticket->getFromDB($ticket->getID()))->isTrue();
        $this->boolean((bool)$ticket->canAdminActors())->isTrue();
        $this->boolean((bool)$ticket->canAssign())->isFalse();
        $this->boolean((bool)$ticket->canAssignToMe())->isFalse();
        $this->boolean((bool)$ticket->canUpdate())->isTrue();
        $this->boolean((bool)$ticket->canView())->isTrue();
        $this->boolean((bool)$ticket->canViewItem())->isTrue();
        $this->boolean((bool)$ticket->canSolve())->isTrue();
        $this->boolean((bool)$ticket->canApprove())->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'content', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'name', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'priority', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'type', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'location', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canCreateItem())->isTrue();
        $this->boolean((bool)$ticket->canUpdateItem())->isTrue();
        $this->boolean((bool)$ticket->canRequesterUpdateItem())->isFalse();
        $this->boolean((bool)$ticket->canDelete())->isFalse();
        $this->boolean((bool)$ticket->canDeleteItem())->isFalse();
        $this->boolean((bool)$ticket->canAddItem('Document'))->isTrue();
        $this->boolean((bool)$ticket->canAddItem('Ticket_Cost'))->isTrue();
        $this->boolean((bool)$ticket->canAddFollowups())->isTrue();
        $this->boolean((bool)$ticket->canUserAddFollowups(\Session::getLoginUserID()))->isTrue();

       //drop update ticket right from tech profile
        global $DB;
        $DB->update(
            'glpi_profilerights',
            ['rights' => 168965],
            [
                'profiles_id'  => 6,
                'name'         => 'ticket'
            ]
        );
       //ACLs have changed: login again.
        $this->boolean((bool)$auth->login('tech', 'tech', true))->isTrue();

       //reset rights. Done here so ACLs are reset even if tests fails.
        $DB->update(
            'glpi_profilerights',
            ['rights' => 168967],
            [
                'profiles_id'  => 6,
                'name'         => 'ticket'
            ]
        );

        $this->boolean((bool)$ticket->getFromDB($ticket->getID()))->isTrue();
        $this->boolean((bool)$ticket->canAdminActors())->isFalse();
        $this->boolean((bool)$ticket->canAssign())->isFalse();
        $this->boolean((bool)$ticket->canAssignToMe())->isFalse();
        $this->boolean((bool)$ticket->canUpdate())->isTrue();
        $this->boolean((bool)$ticket->canView())->isTrue();
        $this->boolean((bool)$ticket->canViewItem())->isTrue();
        $this->boolean((bool)$ticket->canSolve())->isTrue();
        $this->boolean((bool)$ticket->canApprove())->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'content', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'name', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'priority', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'type', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'location', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canCreateItem())->isTrue();
        $this->boolean((bool)$ticket->canUpdateItem())->isTrue();
        $this->boolean((bool)$ticket->canRequesterUpdateItem())->isFalse();
        $this->boolean((bool)$ticket->canDelete())->isFalse();
        $this->boolean((bool)$ticket->canDeleteItem())->isFalse();
        $this->boolean((bool)$ticket->canAddItem('Document'))->isTrue();
        $this->boolean((bool)$ticket->canAddItem('Ticket_Cost'))->isFalse();
        $this->boolean((bool)$ticket->canAddFollowups())->isTrue();
        $this->boolean((bool)$ticket->canUserAddFollowups(\Session::getLoginUserID()))->isTrue();

        $this->integer(
            (int)$ticket->add([
                'name'    => '',
                'content' => 'Another ticket to check ACLS',
                '_users_id_assign' => getItemByTypeName("User", 'tech', true),
            ])
        )->isGreaterThan(0);
        $this->boolean((bool)$ticket->getFromDB($ticket->getID()))->isTrue();
        $this->boolean((bool)$ticket->canAdminActors())->isFalse();
        $this->boolean((bool)$ticket->canAssign())->isFalse();
        $this->boolean((bool)$ticket->canAssignToMe())->isFalse();
        $this->boolean((bool)$ticket->canUpdate())->isTrue();
        $this->boolean((bool)$ticket->canView())->isTrue();
        $this->boolean((bool)$ticket->canViewItem())->isTrue();
        $this->boolean((bool)$ticket->canSolve())->isTrue();
        $this->boolean((bool)$ticket->canApprove())->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'content', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'name', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'priority', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'type', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'location', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canCreateItem())->isTrue();
        $this->boolean((bool)$ticket->canUpdateItem())->isTrue();
        $this->boolean((bool)$ticket->canRequesterUpdateItem())->isTrue();
        $this->boolean((bool)$ticket->canDelete())->isFalse();
        $this->boolean((bool)$ticket->canDeleteItem())->isFalse();
        $this->boolean((bool)$ticket->canAddItem('Document'))->isTrue();
        $this->boolean((bool)$ticket->canAddItem('Ticket_Cost'))->isTrue();
        $this->boolean((bool)$ticket->canAddFollowups())->isTrue();
        $this->boolean((bool)$ticket->canUserAddFollowups(\Session::getLoginUserID()))->isTrue();
    }

    public function testNotOwnerAcls()
    {
        $this->login();

        $ticket = new \Ticket();
        $this->integer(
            (int)$ticket->add([
                'name'    => '',
                'content' => 'A ticket to check ACLS',
                '_users_id_assign' => getItemByTypeName("User", TU_USER, true),
            ])
        )->isGreaterThan(0);

        $auth = new \Auth();
        $this->boolean((bool)$auth->login('tech', 'tech', true))->isTrue();

       //reload ticket from DB
        $this->boolean((bool)$ticket->getFromDB($ticket->getID()))->isTrue();
        $this->boolean((bool)$ticket->canAdminActors())->isTrue();
        $this->boolean((bool)$ticket->canAssign())->isFalse();
        $this->boolean((bool)$ticket->canAssignToMe())->isFalse();
        $this->boolean((bool)$ticket->canUpdate())->isTrue();
        $this->boolean((bool)$ticket->canView())->isTrue();
        $this->boolean((bool)$ticket->canViewItem())->isTrue();
        $this->boolean((bool)$ticket->canSolve())->isTrue();
        $this->boolean((bool)$ticket->canApprove())->isFalse();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'content', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'name', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'priority', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'type', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'location', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canCreateItem())->isTrue();
        $this->boolean((bool)$ticket->canUpdateItem())->isTrue();
        $this->boolean((bool)$ticket->canRequesterUpdateItem())->isFalse();
        $this->boolean((bool)$ticket->canDelete())->isFalse();
        $this->boolean((bool)$ticket->canDeleteItem())->isFalse();
        $this->boolean((bool)$ticket->canAddItem('Document'))->isTrue();
        $this->boolean((bool)$ticket->canAddItem('Ticket_Cost'))->isTrue();
        $this->boolean((bool)$ticket->canAddFollowups())->isTrue();
        $this->boolean((bool)$ticket->canUserAddFollowups(\Session::getLoginUserID()))->isTrue();

       //drop update ticket right from tech profile
        global $DB;
        $DB->update(
            'glpi_profilerights',
            ['rights' => 168965],
            [
                'profiles_id'  => 6,
                'name'         => 'ticket'
            ]
        );
       //ACLs have changed: login again.
        $this->boolean((bool)$auth->login('tech', 'tech', true))->isTrue();

       //reset rights. Done here so ACLs are reset even if tests fails.
        $DB->update(
            'glpi_profilerights',
            ['rights' => 168967],
            [
                'profiles_id'  => 6,
                'name'         => 'ticket'
            ]
        );

        $this->boolean((bool)$ticket->getFromDB($ticket->getID()))->isTrue();
        $this->boolean((bool)$ticket->canAdminActors())->isFalse();
        $this->boolean((bool)$ticket->canAssign())->isFalse();
        $this->boolean((bool)$ticket->canAssignToMe())->isFalse();
        $this->boolean((bool)$ticket->canUpdate())->isTrue();
        $this->boolean((bool)$ticket->canView())->isTrue();
        $this->boolean((bool)$ticket->canViewItem())->isTrue();
        $this->boolean((bool)$ticket->canSolve())->isFalse();
        $this->boolean((bool)$ticket->canApprove())->isFalse();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'content', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'name', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'priority', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'type', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'location', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canCreateItem())->isTrue();
        $this->boolean((bool)$ticket->canUpdateItem())->isFalse();
        $this->boolean((bool)$ticket->canRequesterUpdateItem())->isFalse();
        $this->boolean((bool)$ticket->canDelete())->isFalse();
        $this->boolean((bool)$ticket->canDeleteItem())->isFalse();
        $this->boolean((bool)$ticket->canAddItem('Document'))->isTrue();
        $this->boolean((bool)$ticket->canAddItem('Ticket_Cost'))->isFalse();
        $this->boolean((bool)$ticket->canAddFollowups())->isTrue();
        $this->boolean((bool)$ticket->canUserAddFollowups(\Session::getLoginUserID()))->isTrue();

       // post only tests
        $this->boolean((bool)$auth->login('post-only', 'postonly', true))->isTrue();
        $this->boolean((bool)$ticket->getFromDB($ticket->getID()))->isTrue();
        $this->boolean((bool)$ticket->canAdminActors())->isFalse();
        $this->boolean((bool)$ticket->canAssign())->isFalse();
        $this->boolean((bool)$ticket->canAssignToMe())->isFalse();
        $this->boolean((bool)$ticket->canUpdate())->isTrue();
        $this->boolean((bool)$ticket->canView())->isTrue();
        $this->boolean((bool)$ticket->canViewItem())->isFalse();
        $this->boolean((bool)$ticket->canSolve())->isFalse();
        $this->boolean((bool)$ticket->canApprove())->isFalse();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'content', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'name', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'priority', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'type', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canMassiveAction('update', 'location', 'qwerty'))->isTrue();
        $this->boolean((bool)$ticket->canCreateItem())->isTrue();
        $this->boolean((bool)$ticket->canUpdateItem())->isFalse();
        $this->boolean((bool)$ticket->canRequesterUpdateItem())->isFalse();
        $this->boolean((bool)$ticket->canDelete())->isTrue();
        $this->boolean((bool)$ticket->canDeleteItem())->isFalse();
        $this->boolean((bool)$ticket->canAddItem('Document'))->isFalse();
        $this->boolean((bool)$ticket->canAddItem('Ticket_Cost'))->isFalse();
        $this->boolean((bool)$ticket->canAddFollowups())->isFalse();
        $this->boolean((bool)$ticket->canUserAddFollowups(\Session::getLoginUserID()))->isFalse();
    }

    /**
     * Checks showForm() output
     *
     * @param \Ticket $ticket   Ticket instance
     * @param boolean $name     Name is editable
     * @param boolean $textarea Content is editable
     * @param boolean $priority Priority can be changed
     * @param boolean $save     Save button is present
     * @param boolean $assign   Can assign
     *
     * @return void
     */
    private function checkFormOutput(
        \Ticket $ticket,
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
        $location = true
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
        $this->array($matches)->hasSize(($openDate === true ? 1 : 0), "RW Opening date $caller");

       // Time to own, editable
        $matches = iterator_to_array($crawler->filter("#itil-data input[name=time_to_own]:not([disabled])"));
        $this->array($matches)->hasSize(($timeOwnResolve === true ? 1 : 0), "Time to own editable $caller");

       // Internal time to own, editable
        $matches = iterator_to_array($crawler->filter("#itil-data input[name=internal_time_to_own]:not([disabled])"));
        $this->array($matches)->hasSize(($timeOwnResolve === true ? 1 : 0), "Internal time to own editable $caller");

       // Time to resolve, editable
        $matches = iterator_to_array($crawler->filter("#itil-data input[name=time_to_resolve]:not([disabled])"));
        $this->array($matches)->hasSize(($timeOwnResolve === true ? 1 : 0), "Time to resolve $caller");

       // Internal time to resolve, editable
        $matches = iterator_to_array($crawler->filter("#itil-data input[name=internal_time_to_resolve]:not([disabled])"));
        $this->array($matches)->hasSize(($timeOwnResolve === true ? 1 : 0), "Internal time to resolve $caller");

       //Type
        $matches = iterator_to_array($crawler->filter("#itil-data select[name=type]:not([disabled])"));
        $this->array($matches)->hasSize(($type === true ? 1 : 0), "Type $caller");

       //Status
        $matches = iterator_to_array($crawler->filter("#itil-data select[name=status]:not([disabled])"));
        $this->array($matches)->hasSize(($status === true ? 1 : 0), "Status $caller");

       //Urgency
        $matches = iterator_to_array($crawler->filter("#itil-data select[name=urgency]:not([disabled])"));
        $this->array($matches)->hasSize(($urgency === true ? 1 : 0), "Urgency $caller");

       //Impact
        $matches = iterator_to_array($crawler->filter("#itil-data select[name=impact]:not([disabled])"));
        $this->array($matches)->hasSize(($impact === true ? 1 : 0), "Impact $caller");

       //Category
        $matches = iterator_to_array($crawler->filter("#itil-data select[name=itilcategories_id]:not([disabled])"));
        $this->array($matches)->hasSize(($category === true ? 1 : 0), "Category $caller");

       //Request source file_put_contents('/tmp/out.html', $output)
        $matches = iterator_to_array($crawler->filter("#itil-data select[name=requesttypes_id]:not([disabled])"));
        $this->array($matches)->hasSize($requestSource === true ? 1 : 0, "Request source $caller");

       //Location
        $matches = iterator_to_array($crawler->filter("#itil-data select[name=locations_id]:not([disabled])"));
        $this->array($matches)->hasSize(($location === true ? 1 : 0), "Location $caller");

       //Priority, editable
        $matches = iterator_to_array($crawler->filter("#itil-data select[name=priority]:not([disabled])"));
        $this->array($matches)->hasSize(($priority === true ? 1 : 0), "RW priority $caller");

       //Save button
        $matches = iterator_to_array($crawler->filter("#itil-footer button[type=submit][name=update]:not([disabled])"));
        $this->array($matches)->hasSize(($save === true ? 1 : 0), ($save === true ? 'Save button missing' : 'Save button present') . ' ' . $caller);

       //Assign to
       /*preg_match(
         '|.*<select name=\'_itil_assign\[_type\]\'[^>]*>.*|',
         $output,
         $matches
       );
       $this->array($matches)->hasSize(($assign === true ? 1 : 0));*/
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
        $this->boolean((bool)$auth->login('post-only', 'postonly', true))->isTrue();

       //create a new ticket
        $ticket = new \Ticket();
        $this->integer(
            (int)$ticket->add([
                'name'    => '',
                'content' => 'A ticket to check displayed postonly form',
            ])
        )->isGreaterThan(0);
        $this->boolean($ticket->getFromDB($ticket->getId()))->isTrue();

        $this->checkFormOutput(
            $ticket,
            $name = false,
            $textarea = true,
            $priority = false,
            $save = true,
            $assign = false,
            $openDate = false,
            $timeOwnResolve = false,
            $type = false,
            $status = false,
            $urgency = true,
            $impact = false,
            $category = true,
            $requestSource = false,
            $location = false
        );

        $uid = getItemByTypeName('User', TU_USER, true);
       //add a followup to the ticket
        $fup = new \ITILFollowup();
        $this->integer(
            (int)$fup->add([
                'itemtype'  => 'Ticket',
                'items_id'   => $ticket->getID(),
                'users_id'     => $uid,
                'content'      => 'A simple followup'
            ])
        )->isGreaterThan(0);

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

    public function testFormTech()
    {
       //create a new ticket with tu user
        $auth = new \Auth();
        $this->login();
        $ticket = new \Ticket();
        $this->integer(
            (int)$ticket->add([
                'name'                => '',
                'content'             => 'A ticket to check displayed tech form',
                '_users_id_requester' => '3', // post-only
                '_users_id_assign'    => '4', // tech
            ])
        )->isGreaterThan(0);
        $this->boolean($ticket->getFromDB($ticket->getId()))->isTrue();

       //check output with default ACLs
        $this->changeTechRight();
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
        $this->changeTechRight(168965);
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
        $this->changeTechRight(136197);
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
        $this->changeTechRight(94209);
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

       // no update rights, only display for tech
        $this->changeTechRight(3077);
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
        $fup = new \ITILFollowup();
        $this->integer(
            (int)$fup->add([
                'itemtype'  => 'Ticket',
                'items_id'   => $ticket->getID(),
                'users_id'     => $uid,
                'content'      => 'A simple followup'
            ])
        )->isGreaterThan(0);

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

    public function changeTechRight($rights = 168967)
    {
        global $DB;

       // set new rights
        $DB->update(
            'glpi_profilerights',
            ['rights' => $rights],
            [
                'profiles_id'  => 6,
                'name'         => 'ticket'
            ]
        );

       //ACLs have changed: login again.
        $auth = new \Auth();
        $this->boolean((bool) $auth->Login('tech', 'tech', true))->isTrue();

        if ($rights != 168967) {
           //reset rights. Done here so ACLs are reset even if tests fails.
            $DB->update(
                'glpi_profilerights',
                ['rights' => 168967],
                [
                    'profiles_id'  => 6,
                    'name'         => 'ticket'
                ]
            );
        }
    }

    public function testPriorityAcl()
    {
        $this->login();

        $ticket = new \Ticket();
        $this->integer(
            (int)$ticket->add([
                'name'    => '',
                'content' => 'A ticket to check priority ACLS',
            ])
        )->isGreaterThan(0);

        $auth = new \Auth();
        $this->boolean((bool)$auth->login('tech', 'tech', true))->isTrue();
        $this->boolean((bool)$ticket->getFromDB($ticket->getID()))->isTrue();

        $this->boolean((bool)\Session::haveRight(\Ticket::$rightname, \Ticket::CHANGEPRIORITY))->isFalse();
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
        $DB->update(
            'glpi_profilerights',
            ['rights' => 234503],
            [
                'profiles_id'  => 6,
                'name'         => 'ticket'
            ]
        );

       //ACLs have changed: login again.
        $this->boolean((bool)$auth->login('tech', 'tech', true))->isTrue();

       //reset rights. Done here so ACLs are reset even if tests fails.
        $DB->update(
            'glpi_profilerights',
            ['rights' => 168967],
            [
                'profiles_id'  => 6,
                'name'         => 'ticket'
            ]
        );

        $this->boolean((bool)\Session::haveRight(\Ticket::$rightname, \Ticket::CHANGEPRIORITY))->isTrue();
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

        $ticket = new \Ticket();
        $this->integer(
            (int)$ticket->add([
                'name'    => '',
                'content' => 'A ticket to check assign ACLS',
                '_users_id_assign' => getItemByTypeName("User", TU_USER, true),
            ])
        )->isGreaterThan(0);

        $auth = new \Auth();
        $this->boolean((bool)$auth->login('tech', 'tech', true))->isTrue();
        $this->boolean((bool)$ticket->getFromDB($ticket->getID()))->isTrue();

        $this->boolean((bool)$ticket->canAssign())->isFalse();
        $this->boolean((bool)$ticket->canAssignToMe())->isFalse();
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
        $DB->update(
            'glpi_profilerights',
            ['rights' => 136199],
            [
                'profiles_id'  => 6,
                'name'         => 'ticket'
            ]
        );

       //ACLs have changed: login again.
        $this->boolean((bool)$auth->login('tech', 'tech', true))->isTrue();

       //reset rights. Done here so ACLs are reset even if tests fails.
        $DB->update(
            'glpi_profilerights',
            ['rights' => 168967],
            [
                'profiles_id'  => 6,
                'name'         => 'ticket'
            ]
        );

        $this->boolean((bool)$ticket->canAssign())->isFalse();
        $this->boolean((bool)$ticket->canAssignToMe())->isFalse();
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
        $DB->update(
            'glpi_profilerights',
            ['rights' => 144391],
            [
                'profiles_id'  => 6,
                'name'         => 'ticket'
            ]
        );

       //ACLs have changed: login again.
        $this->boolean((bool)$auth->login('tech', 'tech', true))->isTrue();

       //reset rights. Done here so ACLs are reset even if tests fails.
        $DB->update(
            'glpi_profilerights',
            ['rights' => 168967],
            [
                'profiles_id'  => 6,
                'name'         => 'ticket'
            ]
        );

        $this->boolean((bool)$ticket->canAssign())->isTrue();
        $this->boolean((bool)$ticket->canAssignToMe())->isFalse();
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
        $this->changeTechRight(\Ticket::ASSIGN | \Ticket::READALL);
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
        $this->boolean((bool)$auth->login('tech', 'tech', true))->isTrue();

        $ticket = new \Ticket();
        $this->integer(
            (int)$ticket->add([
                'name'    => '',
                'content' => 'A ticket to check followup updates',
            ])
        )->isGreaterThan(0);

       //add a followup to the ticket
        $fup = new \ITILFollowup();
        $this->integer(
            (int)$fup->add([
                'itemtype'  => $ticket::getType(),
                'items_id'   => $ticket->getID(),
                'users_id'     => $uid,
                'content'      => 'A simple followup'
            ])
        )->isGreaterThan(0);

        $this->login();
        $uid2 = getItemByTypeName('User', TU_USER, true);
        $this->boolean($fup->getFromDB($fup->getID()))->isTrue();
        $this->boolean($fup->update([
            'id'        => $fup->getID(),
            'content'   => 'A simple edited followup'
        ]))->isTrue();

        $this->boolean($fup->getFromDB($fup->getID()))->isTrue();
        $this->array($fup->fields)
         ->variable['users_id']->isEqualTo($uid)
         ->variable['users_id_editor']->isEqualTo($uid2);
    }

    public function testClone()
    {
        $this->login();
        $this->setEntity('Root entity', true);
        $ticket = getItemByTypeName('Ticket', '_ticket01');

        $task = new \TicketTask();
        $this->integer(
            (int)$task->add([
                'tickets_id' => $ticket->getID(),
                'content'    => 'A task to check cloning',
                'actiontime' => 3600,
            ])
        )->isGreaterThan(0);

        $date = date('Y-m-d H:i:s');
        $_SESSION['glpi_currenttime'] = $date;

       // Test item cloning
        $added = $ticket->clone();
        $this->integer((int)$added)->isGreaterThan(0);

        $clonedTicket = new \Ticket();
        $this->boolean($clonedTicket->getFromDB($added))->isTrue();

         // Check timeline items are not cloned
        $this->integer((int)$clonedTicket->getTimelineItems())->isEqualTo(0);

        $fields = $ticket->fields;

       // Check the ticket values. Id and dates must be different, everything else must be equal
        foreach ($fields as $k => $v) {
            switch ($k) {
                case 'id':
                    $this->variable($clonedTicket->getField($k))->isNotEqualTo($ticket->getField($k));
                    break;
                case 'date_mod':
                case 'date_creation':
                    $dateClone = new \DateTime($clonedTicket->getField($k));
                    $expectedDate = new \DateTime($date);
                    $this->dateTime($dateClone)->isEqualTo($expectedDate);
                    break;
                case 'name':
                    $this->variable($clonedTicket->getField($k))->isEqualTo("{$ticket->getField($k)} (copy)");
                    break;
                default:
                    $this->executeOnFailure(
                        function () use ($k) {
                            dump($k);
                        }
                    )->variable($clonedTicket->getField($k))->isEqualTo($ticket->getField($k));
            }
        }
    }

    public function testCloneActor()
    {
        $this->login();
        $this->setEntity('Root entity', true);
        $ticket = getItemByTypeName('Ticket', '_ticket01');

        $ticket_user = new Ticket_User();
        $this->integer((int)$ticket_user->add([
            'tickets_id' => $ticket->getID(),
            'users_id' => (int)getItemByTypeName('User', 'post-only', true), //requester
            'type' => 1
        ]))->isGreaterThan(0);

        $this->integer($ticket_user->add([
            'tickets_id' => $ticket->getID(),
            'users_id' => (int)getItemByTypeName('User', 'tech', true), //assign
            'type' => 2
        ]))->isGreaterThan(0);

        $ticket_Supplier = new Supplier_Ticket();
        $this->integer((int)$ticket_Supplier->add([
            'tickets_id' => $ticket->getID(),
            'suppliers_id' => (int)getItemByTypeName('Supplier', '_suplier01_name', true), //observer
            'type' => 3
        ]))->isGreaterThan(0);

        $this->integer((int)$ticket_Supplier->add([
            'tickets_id' => $ticket->getID(),
            'suppliers_id' => (int)getItemByTypeName('Supplier', '_suplier02_name', true), //requester
            'type' => 1
        ]))->isGreaterThan(0);

        $this->integer($ticket_user->add([
            'tickets_id' => $ticket->getID(),
            'users_id' => (int)getItemByTypeName('User', 'normal', true), //observer
            'type' => 3
        ]))->isGreaterThan(0);

        $group_ticket = new Group_Ticket();
        $this->integer($group_ticket->add([
            'tickets_id' => $ticket->getID(),
            'groups_id' => (int)getItemByTypeName('Group', '_test_group_1', true), //observer
            'type' => 3
        ]))->isGreaterThan(0);

        $this->integer($group_ticket->add([
            'tickets_id' => $ticket->getID(),
            'groups_id' => (int)getItemByTypeName('Group', '_test_group_2', true), //assign
            'type' => 3
        ]))->isGreaterThan(0);



        $date = date('Y-m-d H:i:s');
        $_SESSION['glpi_currenttime'] = $date;

       // Test item cloning
        $added = $ticket->clone();
        $this->integer((int)$added)->isGreaterThan(0);

        $clonedTicket = new \Ticket();
        $this->boolean($clonedTicket->getFromDB($added))->isTrue();

        // Check timeline items are not cloned except log
        $this->integer(count($clonedTicket->getTimelineItems([
            'with_logs'         => false,
        ])))->isEqualTo(0);

        $this->integer(count($clonedTicket->getTimelineItems([
            'with_logs'         => true,
        ])))->isEqualTo(8);
        //User: Add a link with an item: 5 times
        //Group: Add a link with an item: 2 times
        //Status: Change New to Processing (assigned): once

        //check actors
        $this->boolean($ticket_user->getFromDBByCrit([
            'tickets_id' => $clonedTicket->getID(),
            'users_id' => (int)getItemByTypeName('User', 'post-only', true), //requester
            'type' => 1
        ]))->isTrue();

        $this->boolean($ticket_user->getFromDBByCrit([
            'tickets_id' => $clonedTicket->getID(),
            'users_id' => (int)getItemByTypeName('User', 'tech', true), //assign
            'type' => 2
        ]))->isTrue();

        $this->boolean($ticket_user->getFromDBByCrit([
            'tickets_id' => $clonedTicket->getID(),
            'users_id' => (int)getItemByTypeName('User', 'normal', true), //observer
            'type' => 3
        ]))->isTrue();

        $this->boolean($ticket_Supplier->getFromDBByCrit([
            'tickets_id' => $ticket->getID(),
            'suppliers_id' => (int)getItemByTypeName('Supplier', '_suplier01_name', true), //observer
            'type' => 3
        ]))->isTrue();

        $this->boolean($ticket_Supplier->getFromDBByCrit([
            'tickets_id' => $ticket->getID(),
            'suppliers_id' => (int)getItemByTypeName('Supplier', '_suplier02_name', true), //requester
            'type' => 1
        ]))->isTrue();

        $this->boolean($group_ticket->getFromDBByCrit([
            'tickets_id' => $clonedTicket->getID(),
            'groups_id' => (int)getItemByTypeName('Group', '_test_group_1', true), //observer
            'type' => 3
        ]))->isTrue();

        $this->boolean($group_ticket->getFromDBByCrit([
            'tickets_id' => $clonedTicket->getID(),
            'groups_id' => (int)getItemByTypeName('Group', '_test_group_2', true), //assign
            'type' => 3
        ]))->isTrue();



        $fields = $ticket->fields;
       // Check the ticket values. Id and dates must be different, everything else must be equal
        foreach ($fields as $k => $v) {
            switch ($k) {
                case 'id':
                    $this->variable($clonedTicket->getField($k))->isNotEqualTo($ticket->getField($k));
                    break;
                case 'date_mod':
                case 'date_creation':
                    $dateClone = new \DateTime($clonedTicket->getField($k));
                    $expectedDate = new \DateTime($date);
                    $this->dateTime($dateClone)->isEqualTo($expectedDate);
                    break;
                case 'name':
                    $this->variable($clonedTicket->getField($k))->isEqualTo("{$ticket->getField($k)} (copy)");
                    break;
                default:
                    $this->executeOnFailure(
                        function () use ($k) {
                            dump($k);
                        }
                    )->variable($clonedTicket->getField($k))->isEqualTo($ticket->getField($k));
            }
        }
    }

    protected function testGetTimelinePosition2($tlp, $tickets_id)
    {
        foreach ($tlp as $users_name => $user) {
            $this->login($users_name, $user['pass']);
            $uid = getItemByTypeName('User', $users_name, true);

           // ITILFollowup
            $fup = new \ITILFollowup();
            $this->integer(
                (int)$fup->add([
                    'itemtype'  => 'Ticket',
                    'items_id'   => $tickets_id,
                    'users_id'     => $uid,
                    'content'      => 'A simple followup'
                ])
            )->isGreaterThan(0);

            $this->integer(
                (int)$fup->fields['timeline_position']
            )->isEqualTo($user['pos']);

           // TicketTask
            $task = new \TicketTask();
            $this->integer(
                (int)$task->add([
                    'tickets_id'   => $tickets_id,
                    'users_id'     => $uid,
                    'content'      => 'A simple Task'
                ])
            )->isGreaterThan(0);

            $this->integer(
                (int)$task->fields['timeline_position']
            )->isEqualTo($user['pos']);

           // Document and Document_Item
            $doc = new \Document();
            $this->integer(
                (int)$doc->add([
                    'users_id'     => $uid,
                    'tickets_id'   => $tickets_id,
                    'name'         => 'A simple document object'
                ])
            )->isGreaterThan(0);

            $doc_item = new \Document_Item();
            $this->integer(
                (int)$doc_item->add([
                    'users_id'      => $uid,
                    'items_id'      => $tickets_id,
                    'itemtype'      => 'Ticket',
                    'documents_id'  => $doc->getID()
                ])
            )->isGreaterThan(0);

            $this->integer(
                (int)$doc_item->fields['timeline_position']
            )->isEqualTo($user['pos']);

           // TicketValidation
            $val = new \TicketValidation();
            $this->integer(
                (int)$val->add([
                    'tickets_id'   => $tickets_id,
                    'comment_submission'      => 'A simple validation',
                    'users_id_validate' => 5, // normal
                    'status' => 2
                ])
            )->isGreaterThan(0);

            $this->integer(
                (int)$val->fields['timeline_position']
            )->isEqualTo($user['pos']);
        }
    }

    protected function testGetTimelinePositionSolution($tlp, $tickets_id)
    {
        foreach ($tlp as $users_name => $user) {
            $this->login($users_name, $user['pass']);
            $uid = getItemByTypeName('User', $users_name, true);

           // Ticket Solution
            $tkt = new \Ticket();
            $this->boolean(
                (bool)$tkt->update([
                    'id'   => $tickets_id,
                    'solution'      => 'A simple solution from ' . $users_name
                ])
            )->isEqualto(true);

            $this->integer(
                (int)$tkt->getTimelinePosition($tickets_id, 'ITILSolution', $uid)
            )->isEqualTo($user['pos']);
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
        $ticket = new \Ticket();
        $this->integer((int)$ticket->add([
            'name'                => 'ticket title',
            'content'             => 'a description',
            '_users_id_requester' => '3', // post-only
            '_users_id_observer'  => '5', // normal
            '_users_id_assign'    => ['4', '5'] // tech and normal
        ]))->isGreaterThan(0);

        $tlp = [
            'glpi'      => ['pass' => 'glpi',     'pos' => \CommonITILObject::TIMELINE_LEFT],
            'post-only' => ['pass' => 'postonly', 'pos' => \CommonITILObject::TIMELINE_LEFT],
            'tech'      => ['pass' => 'tech',     'pos' => \CommonITILObject::TIMELINE_RIGHT],
            'normal'    => ['pass' => 'normal',   'pos' => \CommonITILObject::TIMELINE_RIGHT]
        ];

        $this->testGetTimelinePosition2($tlp, $ticket->getID());

       // Solution timeline tests
        $tlp = [
            'tech'      => ['pass' => 'tech',     'pos' => \CommonITILObject::TIMELINE_RIGHT]
        ];

        $this->testGetTimelinePositionSolution($tlp, $ticket->getID());

        return $ticket->getID();
    }

    public function testGetTimelineItemsPosition()
    {

        $tkt_id = $this->testGetTimelinePosition();

       // login TU_USER
        $this->login();

        $ticket = new \Ticket();
        $this->boolean(
            (bool)$ticket->getFromDB($tkt_id)
        )->isTrue();

       // test timeline_position from getTimelineItems()
        $timeline_items = $ticket->getTimelineItems();

        foreach ($timeline_items as $item) {
            switch ($item['type']) {
                case 'ITILFollowup':
                case 'TicketTask':
                case 'TicketValidation':
                case 'Document_Item':
                    if (in_array($item['item']['users_id'], [2, 3])) {
                        $this->integer((int)$item['item']['timeline_position'])->isEqualTo(\CommonITILObject::TIMELINE_LEFT);
                    } else {
                        $this->integer((int)$item['item']['timeline_position'])->isEqualTo(\CommonITILObject::TIMELINE_RIGHT);
                    }
                    break;
                case 'ITILSolution':
                    $this->integer((int)$item['item']['timeline_position'])->isEqualTo(\CommonITILObject::TIMELINE_RIGHT);
                    break;
            }
        }
    }

    public function inputProvider()
    {
        return [
            [
                'input'     => [
                    'name'     => 'This is a title',
                    'content'   => 'This is a content'
                ],
                'expected'  => [
                    'name' => 'This is a title',
                    'content' => 'This is a content'
                ]
            ], [
                'input'     => [
                    'name'      => '',
                    'content'   => 'This is a content'
                ],
                'expected'  => [
                    'name' => 'This is a content',
                    'content' => 'This is a content'
                ]
            ], [
                'input'     => [
                    'name'      => '',
                    'content'   => "This is a content\nwith a carriage return"
                ],
                'expected'  => [
                    'name' => 'This is a content with a carriage return',
                    'content' => 'This is a content\nwith a carriage return'
                ]
            ], [
                'input'     => [
                    'name'      => '',
                    'content'   => "This is a content\r\nwith a carriage return"
                ],
                'expected'  => [
                    'name' => 'This is a content with a carriage return',
                    'content' => 'This is a content\nwith a carriage return'
                ]
            ], [
                'input'     => [
                    'name'      => '',
                    'content'   => "<p>This is a content\r\nwith a carriage return</p>"
                ],
                'expected'  => [
                    'name' => 'This is a content with a carriage return',
                    'content' => '<p>This is a content\nwith a carriage return</p>',
                ]
            ], [
                'input'     => [
                    'name'      => '',
                    'content'   => "&lt;p&gt;This is a content\r\nwith a carriage return&lt;/p&gt;"
                ],
                'expected'  => [
                    'name' => 'This is a content with a carriage return',
                    'content' => '&lt;p&gt;This is a content\nwith a carriage return&lt;/p&gt;'
                ]
            ], [
                'input'     => [
                    'name'      => '',
                    'content'   => 'Test for buggy &#039; character'
                ],
                'expected'  => [
                    'name'      => 'Test for buggy \\\' character',
                    'content'   => 'Test for buggy \\\' character',
                ]
            ], [
                'input'     => [
                    'name'      => '',
                    'content'   => 'Test for buggy &#39; character'
                ],
                'expected'  => [
                    'name'      => 'Test for buggy \\\' character',
                    'content'   => 'Test for buggy \\\' character',
                ]
            ]
        ];
    }

    /**
     * @dataProvider inputProvider
     */
    public function testPrepareInputForAdd($input, $expected)
    {
        $this
         ->if($this->newTestedInstance)
         ->then
            ->array($this->testedInstance->prepareInputForAdd(\Toolbox::addslashes_deep($input)))
               ->string['name']->isIdenticalTo($expected['name'])
               ->string['content']->isIdenticalTo($expected['content']);
    }

    public function testAssignChangeStatus()
    {
       // login postonly
        $this->login('post-only', 'postonly');

       //create a new ticket
        $ticket = new \Ticket();
        $this->integer(
            (int)$ticket->add([
                'name'    => '',
                'content' => 'A ticket to check change of status when using "associate myself" feature',
            ])
        )->isGreaterThan(0);
        $tickets_id = $ticket->getID();
        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();

       // login TU_USER
        $this->login();

       // simulate "associate myself" feature
        $ticket_user = new \Ticket_User();
        $input_ticket_user = [
            'tickets_id'       => $tickets_id,
            'users_id'         => \Session::getLoginUserID(),
            'use_notification' => 1,
            'type'             => \CommonITILActor::ASSIGN
        ];
        $this->integer((int) $ticket_user->add($input_ticket_user))->isGreaterThan(0);
        $this->boolean($ticket_user->getFromDB($ticket_user->getId()))->isTrue();

       // check status (should be ASSIGNED)
        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();
        $this->integer((int) $ticket->fields['status'])
           ->isEqualto(\CommonITILObject::ASSIGNED);

       // remove associated user
        $ticket_user->delete([
            'id' => $ticket_user->getId()
        ]);

       // check status (should be INCOMING)
        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();
        $this->integer((int) $ticket->fields['status'])
           ->isEqualto(\CommonITILObject::INCOMING);

       // drop UPDATE right to TU_USER and redo "associate myself"
        $saverights = $_SESSION['glpiactiveprofile'];
        $_SESSION['glpiactiveprofile']['ticket'] -= \UPDATE;
        $this->integer((int) $ticket_user->add($input_ticket_user))->isGreaterThan(0);
       // restore rights
        $_SESSION['glpiactiveprofile'] = $saverights;
       //check ticket creation
        $this->boolean($ticket_user->getFromDB($ticket_user->getId()))->isTrue();

       // check status (should be ASSIGNED)
        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();
        $this->integer((int) $ticket->fields['status'])
           ->isEqualto(\CommonITILObject::ASSIGNED);

       // remove associated user
        $ticket_user->delete([
            'id' => $ticket_user->getId()
        ]);

       // check status (should be INCOMING)
        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();
        $this->integer((int) $ticket->fields['status'])
           ->isEqualto(\CommonITILObject::INCOMING);

       // remove associated user
        $ticket_user->delete([
            'id' => $ticket_user->getId()
        ]);

       // check with very limited rights and redo "associate myself"
        $_SESSION['glpiactiveprofile']['ticket'] = \CREATE
                                               + \Ticket::READMY
                                               + \Ticket::READALL
                                               + \Ticket::READGROUP
                                               + \Ticket::OWN; // OWN right must allow self-assign
        $this->integer((int) $ticket_user->add($input_ticket_user))->isGreaterThan(0);
       // restore rights
        $_SESSION['glpiactiveprofile'] = $saverights;
       //check ticket creation
        $this->boolean($ticket_user->getFromDB($ticket_user->getId()))->isTrue();

       // check status (should still be ASSIGNED)
        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();
        $this->integer((int) $ticket->fields['status'])
           ->isEqualto(\CommonITILObject::ASSIGNED);
    }

    public function testClosedTicketTransfer()
    {

       // 1- create a category
        $itilcat      = new \ITILCategory();
        $first_cat_id = $itilcat->add([
            'name' => 'my first cat',
        ]);
        $this->boolean($itilcat->isNewItem())->isFalse();

       // 2- create a category
        $second_cat    = new \ITILCategory();
        $second_cat_id = $second_cat->add([
            'name' => 'my second cat',
        ]);
        $this->boolean($second_cat->isNewItem())->isFalse();

       // 3- create ticket
        $ticket    = new \Ticket();
        $ticket_id = $ticket->add([
            'name'              => 'A ticket to check the category change when using the "transfer" function.',
            'content'           => 'A ticket to check the category change when using the "transfer" function.',
            'itilcategories_id' => $first_cat_id,
            'status'            => \CommonITILObject::CLOSED
        ]);

        $this->boolean($ticket->isNewItem())->isFalse();

       // 4 - delete category with replacement
        $itilcat->delete(['id'          => $first_cat_id,
            '_replace_by' => $second_cat_id
        ], 1);

       // 5 - check that the category has been replaced in the ticket
        $ticket->getFromDB($ticket_id);
        $this->integer((int)$ticket->fields['itilcategories_id'])
           ->isEqualto($second_cat_id);
    }

    protected function computePriorityProvider()
    {
        return [
            [
                'input'    => [
                    'urgency'   => 2,
                    'impact'    => 2
                ],
                'urgency'  => '2',
                'impact'   => '2',
                'priority' => '2'
            ], [
                'input'    => [
                    'urgency'   => 5
                ],
                'urgency'  => '5',
                'impact'   => '3',
                'priority' => '4'
            ], [
                'input'    => [
                    'impact'   => 5
                ],
                'urgency'  => '3',
                'impact'   => '5',
                'priority' => '4'
            ], [
                'input'    => [
                    'urgency'   => 5,
                    'impact'    => 5
                ],
                'urgency'  => '5',
                'impact'   => '5',
                'priority' => '5'
            ], [
                'input'    => [
                    'urgency'   => 5,
                    'impact'    => 1
                ],
                'urgency'  => '5',
                'impact'   => '1',
                'priority' => '2'
            ]
        ];
    }

    /**
     * @dataProvider computePriorityProvider
     */
    public function testComputePriority($input, $urgency, $impact, $priority)
    {
        $this->login();
        $ticket = getItemByTypeName('Ticket', '_ticket01');
        $input['id'] = $ticket->fields['id'];
        $result = $ticket->prepareInputForUpdate($input);
        $this->array($result)
         ->string['urgency']->isIdenticalTo($urgency)
         ->string['impact']->isIdenticalTo($impact)
         ->string['priority']->isIdenticalTo($priority);
    }

    public function testGetDefaultValues()
    {
        $input = \Ticket::getDefaultValues();

        $this->integer($input['_users_id_requester'])->isEqualTo(0);
        $this->array($input['_users_id_requester_notif']['use_notification'])->contains('1');
        $this->array($input['_users_id_requester_notif']['alternative_email'])->contains('');

        $this->integer($input['_groups_id_requester'])->isEqualTo(0);

        $this->integer($input['_users_id_assign'])->isEqualTo(0);
        $this->array($input['_users_id_assign_notif']['use_notification'])->contains('1');
        $this->array($input['_users_id_assign_notif']['alternative_email'])->contains('');

        $this->integer($input['_groups_id_assign'])->isEqualTo(0);

        $this->integer($input['_users_id_observer'])->isEqualTo(0);
        $this->array($input['_users_id_observer_notif']['use_notification'])->contains('1');
        $this->array($input['_users_id_observer_notif']['alternative_email'])->contains('');

        $this->integer($input['_suppliers_id_assign'])->isEqualTo(0);
        $this->array($input['_suppliers_id_assign_notif']['use_notification'])->contains('1');
        $this->array($input['_suppliers_id_assign_notif']['alternative_email'])->contains('');

        $this->string($input['name'])->isEqualTo('');
        $this->string($input['content'])->isEqualTo('');
        $this->integer((int) $input['itilcategories_id'])->isEqualTo(0);
        $this->integer((int) $input['urgency'])->isEqualTo(3);
        $this->integer((int) $input['impact'])->isEqualTo(3);
        $this->integer((int) $input['priority'])->isEqualTo(3);
        $this->integer((int) $input['requesttypes_id'])->isEqualTo(1);
        $this->integer((int) $input['actiontime'])->isEqualTo(0);
        $this->integer((int) $input['entities_id'])->isEqualTo(0);
        $this->integer((int) $input['status'])->isEqualTo(\Ticket::INCOMING);
        $this->array($input['followup'])->size->isEqualTo(0);
        $this->string($input['itemtype'])->isEqualTo('');
        $this->integer((int) $input['items_id'])->isEqualTo(0);
        $this->array($input['plan'])->size->isEqualTo(0);
        $this->integer((int) $input['global_validation'])->isEqualTo(\CommonITILValidation::NONE);

        $this->string($input['time_to_resolve'])->isEqualTo('NULL');
        $this->string($input['time_to_own'])->isEqualTo('NULL');
        $this->integer((int) $input['slas_id_tto'])->isEqualTo(0);
        $this->integer((int) $input['slas_id_ttr'])->isEqualTo(0);

        $this->string($input['internal_time_to_resolve'])->isEqualTo('NULL');
        $this->string($input['internal_time_to_own'])->isEqualTo('NULL');
        $this->integer((int) $input['olas_id_tto'])->isEqualTo(0);
        $this->integer((int) $input['olas_id_ttr'])->isEqualTo(0);

        $this->integer((int) $input['_add_validation'])->isEqualTo(0);

        $this->array($input['users_id_validate'])->size->isEqualTo(0);
        $this->integer((int) $input['type'])->isEqualTo(\Ticket::INCIDENT_TYPE);
        $this->array($input['_documents_id'])->size->isEqualTo(0);
        $this->array($input['_tasktemplates_id'])->size->isEqualTo(0);
        $this->array($input['_filename'])->size->isEqualTo(0);
        $this->array($input['_tag_filename'])->size->isEqualTo(0);
    }

    /**
     * @see self::testCanTakeIntoAccount()
     */
    protected function canTakeIntoAccountProvider()
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
                        'followup' => \READ + \ITILFollowup::ADDALLTICKET,
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
                        'followup' => \READ + \ITILFollowup::ADDMYTICKET,
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
                        'followup' => \READ + \ITILFollowup::ADDGROUPTICKET,
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
     *
     * @dataProvider canTakeIntoAccountProvider
     */
    public function testCanTakeIntoAccount(array $input, array $user, $expected)
    {
       // Create a ticket
        $this->login();
        $_SESSION['glpiset_default_tech'] = false;
        $ticket = new \Ticket();
        $ticketId = $ticket->add(
            $input + [
                'name'    => '',
                'content' => 'A ticket to check canTakeIntoAccount() results',
                'status'  => CommonITILObject::ASSIGNED
            ]
        );
        $this->integer((int)$ticketId)->isGreaterThan(0);
       // Reload ticket to get all default fields values
        $this->boolean($ticket->getFromDB($ticketId))->isTrue();
       // Validate that "takeintoaccount_delay_stat" is not automatically defined
        $this->integer((int)$ticket->fields['takeintoaccount_delay_stat'])->isEqualTo(0);
        $this->variable($ticket->fields['takeintoaccountdate'])->isEqualTo(null);
       // Login with tested user
        $this->login($user['login'], $user['password']);
       // Apply specific rights if defined
        if (array_key_exists('rights', $user)) {
            foreach ($user['rights'] as $rightname => $rightvalue) {
                $_SESSION['glpiactiveprofile'][$rightname] = $rightvalue;
            }
        }
       // Verify result
        $this->boolean($ticket->canTakeIntoAccount())->isEqualTo($expected);

       // Check that computation of "takeintoaccount_delay_stat" can be prevented
        sleep(1); // be sure to wait at least one second before updating
        $this->boolean(
            $ticket->update(
                [
                    'id'                              => $ticketId,
                    'content'                         => 'Updated ticket 1',
                    '_do_not_compute_takeintoaccount' => 1
                ]
            )
        )->isTrue();
        $this->integer((int)$ticket->fields['takeintoaccount_delay_stat'])->isEqualTo(0);
        $this->variable($ticket->fields['takeintoaccountdate'])->isEqualTo(null);

       // Check that computation of "takeintoaccount_delay_stat" is done if user can take into account
        $this->boolean(
            $ticket->update(
                [
                    'id'      => $ticketId,
                    'content' => 'Updated ticket 2',
                ]
            )
        )->isTrue();
        if (!$expected) {
            $this->integer((int)$ticket->fields['takeintoaccount_delay_stat'])->isEqualTo(0);
            $this->variable($ticket->fields['takeintoaccountdate'])->isEqualTo(null);
        } else {
            $this->integer((int)$ticket->fields['takeintoaccount_delay_stat'])->isGreaterThan(0);
            $this->string($ticket->fields['takeintoaccountdate'])->isEqualTo($_SESSION['glpi_currenttime']);
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
        $ticket = new \Ticket();
        $ticket_id = $ticket->add(
            [
                'name'    => '',
                'content' => 'A ticket to check isAlreadyTakenIntoAccount() results',
            ]
        );
        $this->integer((int)$ticket_id)->isGreaterThan(0);

       // Reload ticket to get all default fields values
        $this->boolean($ticket->getFromDB($ticket_id))->isTrue();

       // Empty ticket is not taken into account
        $this->boolean($ticket->isAlreadyTakenIntoAccount())->isFalse();

       // Take into account
        $this->login('tech', 'tech');
        $ticket_user = new \Ticket_User();
        $ticket_user_id = $ticket_user->add(
            [
                'tickets_id'       => $ticket_id,
                'users_id'         => \Session::getLoginUserID(),
                'use_notification' => 1,
                'type'             => \CommonITILActor::ASSIGN
            ]
        );
        $this->integer((int)$ticket_user_id)->isGreaterThan(0);

       // Assign to tech made ticket taken into account
        $this->boolean($ticket->getFromDB($ticket_id))->isTrue();
        $this->boolean($ticket->isAlreadyTakenIntoAccount())->isTrue();
    }

    public function testCronCloseTicket()
    {
        global $DB;
        $this->login();
       // set default calendar and autoclose delay in root entity
        $entity = new \Entity();
        $this->boolean($entity->update([
            'id'              => 0,
            'calendars_id'    => 1,
            'autoclose_delay' => 5,
        ]))->isTrue();

       // create some solved tickets at various solvedate
        $ticket = new \Ticket();
        $tickets_id_1 = $ticket->add([
            'name'        => "test autoclose 1",
            'content'     => "test autoclose 1",
            'entities_id' => 0,
            'status'      => \CommonITILObject::SOLVED,
        ]);
        $this->integer((int)$tickets_id_1)->isGreaterThan(0);
        $DB->update('glpi_tickets', [
            'solvedate' => date('Y-m-d 10:00:00', time() - 10 * DAY_TIMESTAMP),
        ], [
            'id' => $tickets_id_1,
        ]);
        $tickets_id_2 = $ticket->add([
            'name'        => "test autoclose 1",
            'content'     => "test autoclose 1",
            'entities_id' => 0,
            'status'      => \CommonITILObject::SOLVED,
        ]);
        $DB->update('glpi_tickets', [
            'solvedate' => date('Y-m-d 10:00:00', time()),
        ], [
            'id' => $tickets_id_2,
        ]);
        $this->integer((int)$tickets_id_2)->isGreaterThan(0);

       // launch Cron for closing tickets
        $mode = - \CronTask::MODE_EXTERNAL; // force
        \CronTask::launch($mode, 5, 'closeticket');

       // check ticket status
        $this->boolean($ticket->getFromDB($tickets_id_1))->isTrue();
        $this->integer((int)$ticket->fields['status'])->isEqualTo(\CommonITILObject::CLOSED);
        $this->boolean($ticket->getFromDB($tickets_id_2))->isTrue();
        $this->integer((int)$ticket->fields['status'])->isEqualTo(\CommonITILObject::SOLVED);
    }

    /**
     * @see self::testTakeIntoAccountDelayComputationOnCreate()
     * @see self::testTakeIntoAccountDelayComputationOnUpdate()
     */
    protected function takeIntoAccountDelayComputationProvider()
    {
        $this->login();
        $group = new \Group();
        $group_id = $group->add(['name' => 'Test group']);
        $this->integer((int)$group_id)->isGreaterThan(0);

        $group_user = new \Group_User();
        $this->integer(
            (int)$group_user->add([
                'groups_id' => $group_id,
                'users_id'  => '4', // "tech"
            ])
        )->isGreaterThan(0);

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
     *
     * @param array   $input    Input used to create the ticket
     * @param boolean $computed Expected computation state
     *
     * @dataProvider takeIntoAccountDelayComputationProvider
     */
    public function testTakeIntoAccountDelayComputationOnCreate(array $input, $computed)
    {

       // Create a ticket
        $this->login('tech', 'tech'); // Login with tech to be sure to be the requester
        $_SESSION['glpiset_default_tech'] = false;
        $ticket = new \Ticket();
        $ticketId = $ticket->add(
            $input + [
                'name'    => '',
                'content' => 'A ticket to check takeintoaccount_delay_stat computation state',
            ]
        );
        $this->integer((int)$ticketId)->isGreaterThan(0);

       // Reload ticket to get all default fields values
        $this->boolean($ticket->getFromDB($ticketId))->isTrue();

        if (!$computed) {
            $this->integer((int)$ticket->fields['takeintoaccount_delay_stat'])->isEqualTo(0);
            $this->variable($ticket->fields['takeintoaccountdate'])->isEqualTo(null);
        } else {
            $this->integer((int)$ticket->fields['takeintoaccount_delay_stat'])->isGreaterThan(0);
            $this->string($ticket->fields['takeintoaccountdate'])->isEqualTo($_SESSION['glpi_currenttime']);
        }
    }

    /**
     * Tests that "takeintoaccount_delay_stat" is computed (or not) as expected on ticket update.
     *
     * @param array   $input     Input used to update the ticket
     * @param boolean $computed  Expected computation state
     *
     * @dataProvider takeIntoAccountDelayComputationProvider
     */
    public function testTakeIntoAccountDelayComputationOnUpdate(array $input, $computed)
    {

       // Create a ticket
        $this->login('tech', 'tech'); // Login with tech to be sure to be the requester
        $_SESSION['glpiset_default_tech'] = false;
        $ticket = new \Ticket();
        $ticketId = $ticket->add(
            [
                'name'    => '',
                'content' => 'A ticket to check takeintoaccount_delay_stat computation state',
            ]
        );
        $this->integer((int)$ticketId)->isGreaterThan(0);

       // Reload ticket to get all default fields values
        $this->boolean($ticket->getFromDB($ticketId))->isTrue();

       // Validate that "takeintoaccount_delay_stat" is not automatically defined
        $this->integer((int)$ticket->fields['takeintoaccount_delay_stat'])->isEqualTo(0);
        $this->variable($ticket->fields['takeintoaccountdate'])->isEqualTo(null);

       // Login with tech to be sure to be have rights to take into account
        $this->login('tech', 'tech');

        sleep(1); // be sure to wait at least one second before updating
        $this->boolean(
            $ticket->update(
                $input + [
                    'id' => $ticketId,
                ]
            )
        )->isTrue();

       // Reload ticket to get fresh values that can be defined by a tier object
        $this->boolean($ticket->getFromDB($ticketId))->isTrue();

        if (!$computed) {
            $this->integer((int)$ticket->fields['takeintoaccount_delay_stat'])->isEqualTo(0);
            $this->variable($ticket->fields['takeintoaccountdate'])->isEqualTo(null);
        } else {
            $this->integer((int)$ticket->fields['takeintoaccount_delay_stat'])->isGreaterThan(0);
            $this->string($ticket->fields['takeintoaccountdate'])->isEqualTo($_SESSION['glpi_currenttime']);
        }
    }

    /**
     * @see self::testStatusComputationOnCreate()
     */
    protected function statusComputationOnCreateProvider()
    {

        $group = new \Group();
        $group_id = $group->add(['name' => 'Test group']);
        $this->integer((int)$group_id)->isGreaterThan(0);

        return [
            [
                'input'    => [
                    '_users_id_assign' => ['4'], // "tech"
                    'status' => \CommonITILObject::INCOMING,
                ],
                'expected' => \CommonITILObject::ASSIGNED, // incoming changed to assign as actors are set
            ],
            [
                'input'    => [
                    '_groups_id_assign' => $group_id,
                    'status' => \CommonITILObject::INCOMING,
                ],
                'expected' => \CommonITILObject::ASSIGNED, // incoming changed to assign as actors are set
            ],
            [
                'input'    => [
                    '_suppliers_id_assign' => '1', // "_suplier01_name"
                    'status' => \CommonITILObject::INCOMING,
                ],
                'expected' => \CommonITILObject::ASSIGNED, // incoming changed to assign as actors are set
            ],
            [
                'input'    => [
                    '_users_id_assign' => ['4'], // "tech"
                    'status' => \CommonITILObject::INCOMING,
                    '_do_not_compute_status' => '1',
                ],
                'expected' => \CommonITILObject::INCOMING, // flag prevent status change
            ],
            [
                'input'    => [
                    '_groups_id_assign' => $group_id,
                    'status' => \CommonITILObject::INCOMING,
                    '_do_not_compute_status' => '1',
                ],
                'expected' => \CommonITILObject::INCOMING, // flag prevent status change
            ],
            [
                'input'    => [
                    '_suppliers_id_assign' => '1', // "_suplier01_name"
                    'status' => \CommonITILObject::INCOMING,
                    '_do_not_compute_status' => '1',
                ],
                'expected' => \CommonITILObject::INCOMING, // flag prevent status change
            ],
            [
                'input'    => [
                    '_users_id_assign' => ['4'], // "tech"
                    'status' => \CommonITILObject::WAITING,
                ],
                'expected' => \CommonITILObject::WAITING, // status not changed as not "new"
            ],
            [
                'input'    => [
                    '_groups_id_assign' => $group_id,
                    'status' => \CommonITILObject::WAITING,
                ],
                'expected' => \CommonITILObject::WAITING, // status not changed as not "new"
            ],
            [
                'input'    => [
                    '_suppliers_id_assign' => '1', // "_suplier01_name"
                    'status' => \CommonITILObject::WAITING,
                ],
                'expected' => \CommonITILObject::WAITING, // status not changed as not "new"
            ],
        ];
    }

    /**
     * Check computed status on ticket creation..
     *
     * @param array   $input     Input used to create the ticket
     * @param boolean $expected  Expected status
     *
     * @dataProvider statusComputationOnCreateProvider
     */
    public function testStatusComputationOnCreate(array $input, $expected)
    {

       // Create a ticket
        $this->login();
        $_SESSION['glpiset_default_tech'] = false;
        $ticket = new \Ticket();
        $ticketId = $this->integer(
            (int)$ticket->add([
                'name'    => '',
                'content' => 'A ticket to check status computation',
            ] + $input)
        )->isGreaterThan(0);

       // Reload ticket to get computed fields values
        $this->boolean($ticket->getFromDB($ticketId))->isTrue();

       // Check status
        $this->integer((int)$ticket->fields['status'])->isEqualTo($expected);
    }

    public function testLocationAssignment()
    {
        $rule = new \Rule();
        $rule->getFromDBByCrit([
            'sub_type' => 'RuleTicket',
            'name' => 'Ticket location from user',
        ]);
        $location = new \Location();
        $location->getFromDBByCrit([
            'name' => '_location01'
        ]);
        $user = new \User();
        $user->add([
            'name' => $this->getUniqueString(),
            'locations_id' => $location->getID(),
        ]);

       // test ad ticket with single requester
        $ticket = new \Ticket();
        $rule->update([
            'id' => $rule->getID(),
            'is_active' => '1'
        ]);
        $ticket->add([
            '_users_id_requester' => $user->getID(),
            'name' => 'test location assignment',
            'content' => 'test location assignment',
        ]);
        $rule->update([
            'id' => $rule->getID(),
            'is_active' => '0'
        ]);
        $ticket->getFromDB($ticket->getID());
        $this->integer((int) $ticket->fields['locations_id'])->isEqualTo($location->getID());

       // test add ticket with multiple requesters
        $ticket = new \Ticket();
        $rule->update([
            'id' => $rule->getID(),
            'is_active' => '1'
        ]);
        $ticket->add([
            '_users_id_requester' => [$user->getID(), 2],
            'name' => 'test location assignment',
            'content' => 'test location assignment',
        ]);
        $rule->update([
            'id' => $rule->getID(),
            'is_active' => '0'
        ]);
        $ticket->getFromDB($ticket->getID());
        $this->integer((int) $ticket->fields['locations_id'])->isEqualTo($location->getID());

       // test add ticket with multiple requesters
        $ticket = new \Ticket();
        $rule->update([
            'id' => $rule->getID(),
            'is_active' => '1'
        ]);
        $ticket->add([
            '_users_id_requester' => [2, $user->getID()],
            'name' => 'test location assignment',
            'content' => 'test location assignment',
        ]);
        $rule->update([
            'id' => $rule->getID(),
            'is_active' => '0'
        ]);
        $ticket->getFromDB($ticket->getID());
        $this->integer((int) $ticket->fields['locations_id'])->isEqualTo(0);
    }

    public function testCronPurgeTicket()
    {

        $this->login(); // must be logged as Document_Item uses Session::getLoginUserID()

        global $DB;
       // set default calendar and autoclose delay in root entity
        $entity = new \Entity();
        $this->boolean($entity->update([
            'id'              => 0,
            'calendars_id'    => 1,
            'autopurge_delay' => 5,
        ]))->isTrue();

        $doc = new \Document();
        $did = (int)$doc->add([
            'name'   => 'test doc'
        ]);
        $this->integer($did)->isGreaterThan(0);

       // create some closed tickets at various solvedate
        $ticket = new \Ticket();
        $tickets_id_1 = $ticket->add([
            'name'            => "test autopurge 1",
            'content'         => "test autopurge 1",
            'entities_id'     => 0,
            'status'          => \CommonITILObject::CLOSED,
            '_documents_id'   => [$did]
        ]);
        $this->integer((int)$tickets_id_1)->isGreaterThan(0);
        $this->boolean(
            $DB->update('glpi_tickets', [
                'closedate' => date('Y-m-d 10:00:00', time() - 10 * DAY_TIMESTAMP),
            ], [
                'id' => $tickets_id_1,
            ])
        )->isTrue();
        $this->boolean($ticket->getFromDB($tickets_id_1))->isTrue();

        $docitem = new \Document_Item();
        $this->boolean($docitem->getFromDBByCrit(['itemtype' => 'Ticket', 'items_id' => $tickets_id_1]))->isTrue();

        $tickets_id_2 = $ticket->add([
            'name'        => "test autopurge 2",
            'content'     => "test autopurge 2",
            'entities_id' => 0,
            'status'      => \CommonITILObject::CLOSED,
        ]);
        $this->integer((int)$tickets_id_2)->isGreaterThan(0);
        $this->boolean(
            $DB->update('glpi_tickets', [
                'closedate' => date('Y-m-d 10:00:00', time()),
            ], [
                'id' => $tickets_id_2,
            ])
        );

       // launch Cron for closing tickets
        $mode = - \CronTask::MODE_EXTERNAL; // force
        \CronTask::launch($mode, 5, 'purgeticket');

       // check ticket presence
       // first ticket should have been removed
        $this->boolean($ticket->getFromDB($tickets_id_1))->isFalse();
       //also ensure linked document has been dropped
        $this->boolean($docitem->getFromDBByCrit(['itemtype' => 'Ticket', 'items_id' => $tickets_id_1]))->isFalse();
        $this->boolean($doc->getFromDB($did))->isTrue(); //document itself remains
       //second ticket is still present
        $this->boolean($ticket->getFromDB($tickets_id_2))->isTrue();
        $this->integer((int)$ticket->fields['status'])->isEqualTo(\CommonITILObject::CLOSED);
    }

    public function testMerge()
    {
        $this->login();
        $_SESSION['glpiactiveprofile']['interface'] = '';
        $this->setEntity('Root entity', true);

        $ticket = new \Ticket();
        $ticket1 = $ticket->add([
            'name'        => "test merge 1",
            'content'     => "test merge 1",
            'entities_id' => 0,
            'status'      => \CommonITILObject::INCOMING,
        ]);
        $ticket2 = $ticket->add([
            'name'        => "test merge 2",
            'content'     => "test merge 2",
            'entities_id' => 0,
            'status'      => \CommonITILObject::INCOMING,
        ]);
        $ticket3 = $ticket->add([
            'name'        => "test merge 3",
            'content'     => "test merge 3",
            'entities_id' => 0,
            'status'      => \CommonITILObject::INCOMING,
        ]);

        $task = new \TicketTask();
        $fup = new \ITILFollowup();
        $task->add([
            'tickets_id'   => $ticket2,
            'content'      => 'ticket 2 task 1'
        ]);
        $task->add([
            'tickets_id'   => $ticket3,
            'content'      => 'ticket 3 task 1'
        ]);
        $fup->add([
            'itemtype'  => 'Ticket',
            'items_id'  => $ticket2,
            'content'   => 'ticket 2 fup 1'
        ]);
        $fup->add([
            'itemtype'  => 'Ticket',
            'items_id'  => $ticket3,
            'content'   => 'ticket 3 fup 1'
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
            'is_recursive' => 0
        ]);
        $document_item->add([
            'itemtype'     => 'Ticket',
            'items_id'     => $ticket1,
            'documents_id' => $documents_id,
            'entities_id'  => '0',
            'is_recursive' => 0
        ]);
        $document_item->add([
            'itemtype'     => 'Ticket',
            'items_id'     => $ticket1,
            'documents_id' => $documents_id2,
            'entities_id'  => '0',
            'is_recursive' => 0
        ]);
        $document_item->add([
            'itemtype'     => 'Ticket',
            'items_id'     => $ticket2,
            'documents_id' => $documents_id3,
            'entities_id'  => '0',
            'is_recursive' => 0
        ]);
        $document_item->add([
            'itemtype'     => 'Ticket',
            'items_id'     => $ticket3,
            'documents_id' => $documents_id3,
            'entities_id'  => '0',
            'is_recursive' => 0
        ]);

        $ticket_user = new \Ticket_User();
        $ticket_user->add([
            'tickets_id'         => $ticket1,
            'type'               => \Ticket_User::REQUESTER,
            'users_id'           => 2
        ]);
        $ticket_user->add([ // Duplicate with #1
            'tickets_id'         => $ticket3,
            'type'               => \Ticket_User::REQUESTER,
            'users_id'           => 2
        ]);
        $ticket_user->add([
            'tickets_id'         => $ticket1,
            'users_id'           => 0,
            'type'               => \Ticket_User::REQUESTER,
            'alternative_email'  => 'test@glpi.com'
        ]);
        $ticket_user->add([ // Duplicate with #3
            'tickets_id'         => $ticket2,
            'users_id'           => 0,
            'type'               => \Ticket_User::REQUESTER,
            'alternative_email'  => 'test@glpi.com'
        ]);
        $ticket_user->add([ // Duplicate with #1
            'tickets_id'         => $ticket2,
            'users_id'           => 2,
            'type'               => \Ticket_User::REQUESTER,
            'alternative_email'  => 'test@glpi.com'
        ]);
        $ticket_user->add([
            'tickets_id'         => $ticket3,
            'users_id'           => 2,
            'type'               => \Ticket_User::ASSIGN,
            'alternative_email'  => 'test@glpi.com'
        ]);

        $ticket_group = new \Group_Ticket();
        $ticket_group->add([
            'tickets_id'         => $ticket1,
            'groups_id'          => 1,
            'type'               => \Group_Ticket::REQUESTER
        ]);
        $ticket_group->add([ // Duplicate with #1
            'tickets_id'         => $ticket3,
            'groups_id'          => 1,
            'type'               => \Group_Ticket::REQUESTER
        ]);
        $ticket_group->add([
            'tickets_id'         => $ticket3,
            'groups_id'          => 1,
            'type'               => \Group_Ticket::ASSIGN
        ]);

        $ticket_supplier = new \Supplier_Ticket();
        $ticket_supplier->add([
            'tickets_id'         => $ticket1,
            'type'               => \Supplier_Ticket::REQUESTER,
            'suppliers_id'       => 2
        ]);
        $ticket_supplier->add([ // Duplicate with #1
            'tickets_id'         => $ticket3,
            'type'               => \Supplier_Ticket::REQUESTER,
            'suppliers_id'       => 2
        ]);
        $ticket_supplier->add([
            'tickets_id'         => $ticket1,
            'suppliers_id'       => 0,
            'type'               => \Supplier_Ticket::REQUESTER,
            'alternative_email'  => 'test@glpi.com'
        ]);
        $ticket_supplier->add([ // Duplicate with #3
            'tickets_id'         => $ticket2,
            'suppliers_id'       => 0,
            'type'               => \Supplier_Ticket::REQUESTER,
            'alternative_email'  => 'test@glpi.com'
        ]);
        $ticket_supplier->add([ // Duplicate with #1
            'tickets_id'         => $ticket2,
            'suppliers_id'       => 2,
            'type'               => \Supplier_Ticket::REQUESTER,
            'alternative_email'  => 'test@glpi.com'
        ]);
        $ticket_supplier->add([
            'tickets_id'         => $ticket3,
            'suppliers_id'       => 2,
            'type'               => \Supplier_Ticket::ASSIGN,
            'alternative_email'  => 'test@glpi.com'
        ]);

        $status = [];
        $mergeparams = [
            'linktypes' => [
                'ITILFollowup',
                'TicketTask',
                'Document'
            ],
            'link_type'  => \Ticket_Ticket::SON_OF
        ];

        \Ticket::merge($ticket1, [$ticket2, $ticket3], $status, $mergeparams);

        $status_counts = array_count_values($status);
        $failure_count = 0;
        if (array_key_exists(1, $status_counts)) {
            $failure_count += $status_counts[1];
        }
        if (array_key_exists(2, $status_counts)) {
            $failure_count += $status_counts[2];
        }

        $this->integer((int)$failure_count)->isEqualTo(0);

        $task_count = count($task->find(['tickets_id' => $ticket1]));
        $fup_count = count($fup->find([
            'itemtype' => 'Ticket',
            'items_id' => $ticket1
        ]));
        $doc_count = count($document_item->find([
            'itemtype' => 'Ticket',
            'items_id' => $ticket1
        ]));
        $user_count = count($ticket_user->find([
            'tickets_id' => $ticket1
        ]));
        $group_count = count($ticket_group->find([
            'tickets_id' => $ticket1
        ]));
        $supplier_count = count($ticket_supplier->find([
            'tickets_id' => $ticket1
        ]));

       // Target ticket should have all tasks
        $this->integer((int)$task_count)->isEqualTo(2);
       // Target ticket should have all followups + 1 for each source ticket description
        $this->integer((int)$fup_count)->isEqualTo(4);
       // Target ticket should have the original document, one instance of the duplicate, and the new document from one of the source tickets
        $this->integer((int)$doc_count)->isEqualTo(3);
       // Target ticket should have all users not marked as duplicates above
        $this->integer((int)$user_count)->isEqualTo(3);
       // Target ticket should have all groups not marked as duplicates above
        $this->integer((int)$group_count)->isEqualTo(2);
       // Target ticket should have all suppliers not marked as duplicates above
        $this->integer((int)$supplier_count)->isEqualTo(3);
    }

    public function testKeepScreenshotsOnFormReload()
    {
       //login to get session
        $auth = new \Auth();
        $this->boolean($auth->login(TU_USER, TU_PASS, true))->isTrue();

        $base64Image = base64_encode(file_get_contents(__DIR__ . '/../fixtures/uploads/foo.png'));

       // Test display of saved inputs from a previous submit
        $_SESSION['saveInput'][\Ticket::class] = [
            'content' => '&lt;p&gt; &lt;/p&gt;&lt;p&gt;&lt;img id="3e29dffe-0237ea21-5e5e7034b1d1a1.77230247"'
         . ' src="data:image/png;base64,' . $base64Image . '" width="12" height="12" /&gt;&lt;/p&gt;',
        ];

        $this->output(
            function () {
                $instance = new \Ticket();
                $instance->showForm('-1');
            }
        )->contains('src=&quot;data:image/png;base64,' . $base64Image . '&quot;');
    }

    public function testScreenshotConvertedIntoDocument()
    {

        $this->login(); // must be logged as Document_Item uses Session::getLoginUserID()

       // Test uploads for item creation
        $base64Image = base64_encode(file_get_contents(__DIR__ . '/../fixtures/uploads/foo.png'));
        $filename = '5e5e92ffd9bd91.11111111image_paste22222222.png';
        $instance = new \Ticket();
        $input = [
            'name'    => 'a ticket',
            'content' => Sanitizer::sanitize(<<<HTML
<p>Test with a ' (add)</p>
<p><img id="3e29dffe-0237ea21-5e5e7034b1d1a1.00000000" src="data:image/png;base64,{$base64Image}" width="12" height="12"></p>
HTML
            ),
            '_filename' => [
                $filename,
            ],
            '_tag_filename' => [
                '3e29dffe-0237ea21-5e5e7034b1d1a1.00000000',
            ],
            '_prefix_filename' => [
                '5e5e92ffd9bd91.11111111',
            ]
        ];
        copy(__DIR__ . '/../fixtures/uploads/foo.png', GLPI_TMP_DIR . '/' . $filename);
        $instance->add($input);
        $this->boolean($instance->getFromDB($instance->getId()))->isTrue();
        $expected = 'a href="/front/document.send.php?docid=';
        $this->string($instance->fields['content'])->contains($expected);

       // Test uploads for item update
        $base64Image = base64_encode(file_get_contents(__DIR__ . '/../fixtures/uploads/bar.png'));
        $filename = '5e5e92ffd9bd91.44444444image_paste55555555.png';
        copy(__DIR__ . '/../fixtures/uploads/bar.png', GLPI_TMP_DIR . '/' . $filename);
        $instance->update([
            'id' => $instance->getID(),
            'content' => Sanitizer::sanitize(<<<HTML
<p>Test with a ' (update)</p>
<p><img id="3e29dffe-0237ea21-5e5e7034b1d1a1.33333333" src="data:image/png;base64,{$base64Image}" width="12" height="12"></p>
HTML
            ),
            '_filename' => [
                $filename,
            ],
            '_tag_filename' => [
                '3e29dffe-0237ea21-5e5e7034b1d1a1.33333333',
            ],
            '_prefix_filename' => [
                '5e5e92ffd9bd91.44444444',
            ]
        ]);
        $this->boolean($instance->getFromDB($instance->getId()))->isTrue();
        $expected = 'a href="/front/document.send.php?docid=';
        $this->string($instance->fields['content'])->contains($expected);
    }

    public function testUploadDocuments()
    {

        $this->login(); // must be logged as Document_Item uses Session::getLoginUserID()

       // Test uploads for item creation
        $filename = '5e5e92ffd9bd91.11111111' . 'foo.txt';
        $instance = new \Ticket();
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
            ]
        ];
        copy(__DIR__ . '/../fixtures/uploads/foo.txt', GLPI_TMP_DIR . '/' . $filename);
        $instance->add($input);
        $this->string($instance->fields['content'])->contains('testUploadDocuments');
        $count = (new \DBUtils())->countElementsInTable(\Document_Item::getTable(), [
            'itemtype' => 'Ticket',
            'items_id' => $instance->getID(),
        ]);
        $this->integer($count)->isEqualTo(1);

       // Test uploads for item update (adds a 2nd document)
        $filename = '5e5e92ffd9bd91.44444444bar.txt';
        copy(__DIR__ . '/../fixtures/uploads/bar.txt', GLPI_TMP_DIR . '/' . $filename);
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
            ]
        ]);
        $this->string($instance->fields['content'])->contains('update testUploadDocuments');
        $count = (new \DBUtils())->countElementsInTable(\Document_Item::getTable(), [
            'itemtype' => 'Ticket',
            'items_id' => $instance->getID(),
        ]);
        $this->integer($count)->isEqualTo(2);
    }


    public function testCanDelegateeCreateTicket()
    {
        $normal_id   = getItemByTypeName('User', 'normal', true);
        $tech_id     = getItemByTypeName('User', 'tech', true);
        $postonly_id = getItemByTypeName('User', 'post-only', true);
        $tuser_id    = getItemByTypeName('User', TU_USER, true);

       // check base behavior (only standard interface can create for other users)
        $this->login();
        $this->boolean(\Ticket::canDelegateeCreateTicket($normal_id))->isTrue();
        $this->login('tech', 'tech');
        $this->boolean(\Ticket::canDelegateeCreateTicket($normal_id))->isTrue();
        $this->login('post-only', 'postonly');
        $this->boolean(\Ticket::canDelegateeCreateTicket($normal_id))->isFalse();

       // create a test group
        $group = new \Group();
        $groups_id = $group->add(['name' => 'test delegatee']);
        $this->integer($groups_id)->isGreaterThan(0);

       // make postonly delegate of the group
        $gu = new \Group_User();
        $this->integer($gu->add([
            'users_id'         => $postonly_id,
            'groups_id'        => $groups_id,
            'is_userdelegate' => 1,
        ]))->isGreaterThan(0);
        $this->integer($gu->add([
            'users_id'  => $normal_id,
            'groups_id' => $groups_id,
        ]))->isGreaterThan(0);

       // check postonly can now create (yes for normal and himself) or not (no for others) for other users
        $this->login('post-only', 'postonly');
        $this->boolean(\Ticket::canDelegateeCreateTicket($postonly_id))->isTrue();
        $this->boolean(\Ticket::canDelegateeCreateTicket($normal_id))->isTrue();
        $this->boolean(\Ticket::canDelegateeCreateTicket($tech_id))->isFalse();
        $this->boolean(\Ticket::canDelegateeCreateTicket($tuser_id))->isFalse();
    }

    public function testCanAddFollowupsDefaults()
    {
        $tech_id = getItemByTypeName('User', 'tech', true);
        $normal_id = getItemByTypeName('User', 'normal', true);
        $post_only_id = getItemByTypeName('User', 'post-only', true);

        $this->login();

        $ticket = new \Ticket();
        $this->integer(
            (int)$ticket->add([
                'name'    => '',
                'content' => 'A ticket to check ACLS',
            ])
        )->isGreaterThan(0);

        $this->boolean((bool)$ticket->canUserAddFollowups($tech_id))->isTrue();
        $this->boolean((bool)$ticket->canUserAddFollowups($normal_id))->isFalse();
        $this->boolean((bool)$ticket->canUserAddFollowups($post_only_id))->isFalse();

        $this->login('tech', 'tech');
        $this->boolean((bool)$ticket->canAddFollowups())->isTrue();
        $this->login('normal', 'normal');
        $this->boolean((bool)$ticket->canAddFollowups())->isFalse();
        $this->login('post-only', 'postonly');
        $this->boolean((bool)$ticket->canAddFollowups())->isFalse();
    }

    public function testCanAddFollowupsAsRecipient()
    {
        global $DB;

        $post_only_id = getItemByTypeName('User', 'post-only', true);

        $this->login();

        $ticket = new \Ticket();
        $this->integer(
            (int)$ticket->add([
                'name'               => '',
                'content'            => 'A ticket to check ACLS',
                'users_id_recipient' => $post_only_id,
                '_auto_import'       => false,
            ])
        )->isGreaterThan(0);

       // Drop all followup rights
        $DB->update(
            'glpi_profilerights',
            [
                'rights' => 0
            ],
            [
                'profiles_id' => getItemByTypeName('Profile', 'Self-Service', true),
                'name'        => \ITILFollowup::$rightname,
            ]
        );

       // Cannot add followup as user do not have ADDMYTICKET right
        $this->login();
        $this->boolean((bool)$ticket->canUserAddFollowups($post_only_id))->isFalse();
        $this->login('post-only', 'postonly');
        $this->boolean((bool)$ticket->canAddFollowups())->isFalse();

       // Add user right
        $DB->update(
            'glpi_profilerights',
            [
                'rights' => \ITILFollowup::ADDMYTICKET
            ],
            [
                'profiles_id' => getItemByTypeName('Profile', 'Self-Service', true),
                'name'        => \ITILFollowup::$rightname,
            ]
        );

       // User is recipient and have ADDMYTICKET, he should be able to add followup
        $this->login();
        $this->boolean((bool)$ticket->canUserAddFollowups($post_only_id))->isTrue();
        $this->login('post-only', 'postonly');
        $this->boolean((bool)$ticket->canAddFollowups())->isTrue();
    }

    public function testCanAddFollowupsAsRequester()
    {
        global $DB;

        $post_only_id = getItemByTypeName('User', 'post-only', true);

        $this->login();

        $ticket = new \Ticket();
        $this->integer(
            (int)$ticket->add([
                'name'    => '',
                'content' => 'A ticket to check ACLS',
            ])
        )->isGreaterThan(0);

       // Drop all followup rights
        $DB->update(
            'glpi_profilerights',
            [
                'rights' => 0
            ],
            [
                'profiles_id' => getItemByTypeName('Profile', 'Self-Service', true),
                'name'        => \ITILFollowup::$rightname,
            ]
        );

       // Cannot add followups by default
        $this->login();
        $this->boolean((bool)$ticket->canUserAddFollowups($post_only_id))->isFalse();
        $this->login('post-only', 'postonly');
        $this->boolean((bool)$ticket->canAddFollowups())->isFalse();

       // Add user as requester
        $this->login();
        $ticket_user = new \Ticket_User();
        $input_ticket_user = [
            'tickets_id' => $ticket->getID(),
            'users_id'   => $post_only_id,
            'type'       => \CommonITILActor::REQUESTER
        ];
        $this->integer((int) $ticket_user->add($input_ticket_user))->isGreaterThan(0);
        $this->boolean($ticket->getFromDB($ticket->getID()))->isTrue(); // Reload ticket actors

       // Cannot add followup as user do not have ADDMYTICKET right
        $this->login();
        $this->boolean((bool)$ticket->canUserAddFollowups($post_only_id))->isFalse();
        $this->login('post-only', 'postonly');
        $this->boolean((bool)$ticket->canAddFollowups())->isFalse();

       // Add user right
        $DB->update(
            'glpi_profilerights',
            [
                'rights' => \ITILFollowup::ADDMYTICKET
            ],
            [
                'profiles_id' => getItemByTypeName('Profile', 'Self-Service', true),
                'name'        => \ITILFollowup::$rightname,
            ]
        );

       // User is requester and have ADDMYTICKET, he should be able to add followup
        $this->login();
        $this->boolean((bool)$ticket->canUserAddFollowups($post_only_id))->isTrue();
        $this->login('post-only', 'postonly');
        $this->boolean((bool)$ticket->canAddFollowups())->isTrue();
    }

    public function testCanAddFollowupsAsRequesterGroup()
    {
        global $DB;

        $post_only_id = getItemByTypeName('User', 'post-only', true);

        $this->login();

        $ticket = new \Ticket();
        $this->integer(
            (int)$ticket->add([
                'name'    => '',
                'content' => 'A ticket to check ACLS',
            ])
        )->isGreaterThan(0);

       // Drop all followup rights
        $DB->update(
            'glpi_profilerights',
            [
                'rights' => 0
            ],
            [
                'profiles_id' => getItemByTypeName('Profile', 'Self-Service', true),
                'name'        => \ITILFollowup::$rightname,
            ]
        );

       // Cannot add followups by default
        $this->login();
        $this->boolean((bool)$ticket->canUserAddFollowups($post_only_id))->isFalse();
        $this->login('post-only', 'postonly');
        $this->boolean((bool)$ticket->canAddFollowups())->isFalse();

       // Add user's group as requester
        $this->login();
        $group = new \Group();
        $group_id = $group->add(['name' => 'Test group']);
        $this->integer((int)$group_id)->isGreaterThan(0);
        $group_user = new \Group_User();
        $this->integer(
            (int)$group_user->add([
                'groups_id' => $group_id,
                'users_id'  => $post_only_id,
            ])
        )->isGreaterThan(0);

        $group_ticket = new \Group_Ticket();
        $input_group_ticket = [
            'tickets_id' => $ticket->getID(),
            'groups_id'  => $group_id,
            'type'       => \CommonITILActor::REQUESTER
        ];
        $this->integer((int) $group_ticket->add($input_group_ticket))->isGreaterThan(0);
        $this->boolean($ticket->getFromDB($ticket->getID()))->isTrue(); // Reload ticket actors

       // Cannot add followup as user do not have ADDGROUPTICKET right
        $this->login();
        $this->boolean((bool)$ticket->canUserAddFollowups($post_only_id))->isFalse();
        $this->login('post-only', 'postonly');
        $this->boolean((bool)$ticket->canAddFollowups())->isFalse();

       // Add user right
        $DB->update(
            'glpi_profilerights',
            [
                'rights' => \ITILFollowup::ADDGROUPTICKET
            ],
            [
                'profiles_id' => getItemByTypeName('Profile', 'Self-Service', true),
                'name'        => \ITILFollowup::$rightname,
            ]
        );

       // User is requester and have ADDGROUPTICKET, he should be able to add followup
        $this->login();
        $this->boolean((bool)$ticket->canUserAddFollowups($post_only_id))->isTrue();
        $this->login('post-only', 'postonly');
        $this->boolean((bool)$ticket->canAddFollowups())->isTrue();
    }

    public function testCanAddFollowupsAsAssigned()
    {
        global $DB;

        $post_only_id = getItemByTypeName('User', 'post-only', true);

        $this->login();

        $ticket = new \Ticket();
        $this->integer(
            (int)$ticket->add([
                'name'    => '',
                'content' => 'A ticket to check ACLS',
            ])
        )->isGreaterThan(0);

       // Drop all followup rights
        $DB->update(
            'glpi_profilerights',
            [
                'rights' => 0
            ],
            [
                'profiles_id' => getItemByTypeName('Profile', 'Self-Service', true),
                'name'        => \ITILFollowup::$rightname,
            ]
        );

       // Cannot add followups by default
        $this->login();
        $this->boolean((bool)$ticket->canUserAddFollowups($post_only_id))->isFalse();
        $this->login('post-only', 'postonly');
        $this->boolean((bool)$ticket->canAddFollowups())->isFalse();

       // Add user as requester
        $this->login();
        $ticket_user = new \Ticket_User();
        $input_ticket_user = [
            'tickets_id' => $ticket->getID(),
            'users_id'   => $post_only_id,
            'type'       => \CommonITILActor::ASSIGN
        ];
        $this->integer((int) $ticket_user->add($input_ticket_user))->isGreaterThan(0);
        $this->boolean($ticket->getFromDB($ticket->getID()))->isTrue(); // Reload ticket actors

       // Can add followup as user is assigned
        $this->login();
        $this->boolean((bool)$ticket->canUserAddFollowups($post_only_id))->isTrue();
        $this->login('post-only', 'postonly');
        $this->boolean((bool)$ticket->canAddFollowups())->isTrue();
    }

    public function testCanAddFollowupsAsAssignedGroup()
    {
        global $DB;

        $post_only_id = getItemByTypeName('User', 'post-only', true);

        $this->login();

        $ticket = new \Ticket();
        $this->integer(
            (int)$ticket->add([
                'name'    => '',
                'content' => 'A ticket to check ACLS',
            ])
        )->isGreaterThan(0);

       // Drop all followup rights
        $DB->update(
            'glpi_profilerights',
            [
                'rights' => 0
            ],
            [
                'profiles_id' => getItemByTypeName('Profile', 'Self-Service', true),
                'name'        => \ITILFollowup::$rightname,
            ]
        );

       // Cannot add followups by default
        $this->login();
        $this->boolean((bool)$ticket->canUserAddFollowups($post_only_id))->isFalse();
        $this->login('post-only', 'postonly');
        $this->boolean((bool)$ticket->canAddFollowups())->isFalse();

       // Add user's group as requester
        $this->login();
        $group = new \Group();
        $group_id = $group->add(['name' => 'Test group']);
        $this->integer((int)$group_id)->isGreaterThan(0);
        $group_user = new \Group_User();
        $this->integer(
            (int)$group_user->add([
                'groups_id' => $group_id,
                'users_id'  => $post_only_id,
            ])
        )->isGreaterThan(0);

        $group_ticket = new \Group_Ticket();
        $input_group_ticket = [
            'tickets_id' => $ticket->getID(),
            'groups_id'  => $group_id,
            'type'       => \CommonITILActor::ASSIGN
        ];
        $this->integer((int) $group_ticket->add($input_group_ticket))->isGreaterThan(0);
        $this->boolean($ticket->getFromDB($ticket->getID()))->isTrue(); // Reload ticket actors

       // Can add followup as user is assigned
        $this->login();
        $this->boolean((bool)$ticket->canUserAddFollowups($post_only_id))->isTrue();
        $this->login('post-only', 'postonly');
        $this->boolean((bool)$ticket->canAddFollowups())->isTrue();
    }

    public function testCanAddFollowupsAsObserver()
    {
        global $DB;

        $post_only_id = getItemByTypeName('User', 'post-only', true);

        $this->login();

        $ticket = new \Ticket();
        $this->integer(
            (int)$ticket->add([
                'name'    => '',
                'content' => 'A ticket to check ACLS',
            ])
        )->isGreaterThan(0);

       // Cannot add followups by default
        $this->login();
        $this->boolean((bool)$ticket->canUserAddFollowups($post_only_id))->isFalse();
        $this->login('post-only', 'postonly');
        $this->boolean((bool)$ticket->canAddFollowups())->isFalse();

       // Add user as observer
        $this->login();
        $ticket_user = new \Ticket_User();
        $input_ticket_user = [
            'tickets_id' => $ticket->getID(),
            'users_id'   => $post_only_id,
            'type'       => \CommonITILActor::OBSERVER
        ];
        $this->integer((int) $ticket_user->add($input_ticket_user))->isGreaterThan(0);
        $this->boolean($ticket->getFromDB($ticket->getID()))->isTrue(); // Reload ticket actors

       // Cannot add followup as user do not have ADD_AS_FOLLOWUP right
        $this->login();
        $this->boolean((bool)$ticket->canUserAddFollowups($post_only_id))->isFalse();
        $this->login('post-only', 'postonly');
        $this->boolean((bool)$ticket->canAddFollowups())->isFalse();

       // Add user right
        $DB->update(
            'glpi_profilerights',
            [
                'rights' => \ITILFollowup::ADD_AS_OBSERVER
            ],
            [
                'profiles_id' => getItemByTypeName('Profile', 'Self-Service', true),
                'name'        => \ITILFollowup::$rightname,
            ]
        );

       // User is observer and have ADD_AS_OBSERVER, he should be able to add followup
        $this->login();
        $this->boolean((bool)$ticket->canUserAddFollowups($post_only_id))->isTrue();
        $this->login('post-only', 'postonly');
        $this->boolean((bool)$ticket->canAddFollowups())->isTrue();
    }

    protected function convertContentForTicketProvider(): iterable
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
HTML
                ,
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
HTML
                ,
            ];
            // `img` of embedded image that has multiple attributes.
            yield [
                'content'  => <<<HTML
Here is the screenshot:
<img id="img-id" src={$quote_style}screenshot.png{$quote_style} height="150" width="100" />
blabla
HTML
                ,
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
HTML
                ,
            ];

            // Content with leading external image that will not be replaced by a tag.
            yield [
                'content'  => <<<HTML
<img src={$quote_style}http://test.glpi-project.org/logo.png{$quote_style} />
Here is the screenshot:
<img src={$quote_style}img.jpg{$quote_style} />
blabla
HTML
                ,
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
HTML
                ,
            ];
        }
    }

    /**
     * @dataProvider convertContentForTicketProvider
     */
    public function testConvertContentForTicket(string $content, array $files, array $tags, string $expected)
    {
        $this->newTestedInstance();

        $this->string($this->testedInstance->convertContentForTicket($content, $files, $tags))->isEqualTo($expected);
    }

    protected function testIsValidatorProvider(): array
    {
        $this->login();

       // Existing ursers from databaser
        $users_id_1 = getItemByTypeName(User::class, "glpi", true);
        $users_id_2 = getItemByTypeName(User::class, "tech", true);

       // Tickets to create before tests
        $this->createItems(\Ticket::class, [
            [
                'name'    => 'testIsValidatorProvider 1',
                'content' => 'testIsValidatorProvider 1',
            ],
            [
                'name'    => 'testIsValidatorProvider 2',
                'content' => 'testIsValidatorProvider 2',
            ],
        ]);

       // Get id of created tickets to reuse later
        $tickets_id_1 = getItemByTypeName(\Ticket::class, "testIsValidatorProvider 1", true);
        $tickets_id_2 = getItemByTypeName(\Ticket::class, "testIsValidatorProvider 2", true);

       // TicketValidation items to create before tests
        $this->createItems(TicketValidation::class, [
            [
                'tickets_id'        => $tickets_id_1,
                'users_id_validate' => $users_id_1,
            ],
            [
                'tickets_id'        => $tickets_id_2,
                'users_id_validate' => $users_id_2,
            ],
        ]);

        return [
            [
                'tickets_id' => $tickets_id_1,
                'users_id'   => $users_id_1,
                'expected'   => true,
            ],
            [
                'tickets_id' => $tickets_id_1,
                'users_id'   => $users_id_2,
                'expected'   => false,
            ],
            [
                'tickets_id' => $tickets_id_2,
                'users_id'   => $users_id_1,
                'expected'   => false,
            ],
            [
                'tickets_id' => $tickets_id_2,
                'users_id'   => $users_id_2,
                'expected'   => true,
            ],
        ];
    }

    /**
     * @dataProvider testIsValidatorProvider
     */
    public function testIsValidator(
        int $tickets_id,
        int $users_id,
        bool $expected
    ) {
        $ticket = new \Ticket();
        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();
        $this->boolean($ticket->isValidator($users_id))->isEqualTo($expected);
    }

    public function testGetTeamRoles(): void
    {
        $roles = \Ticket::getTeamRoles();
        $this->array($roles)->containsValues([
            \CommonITILActor::ASSIGN,
            \CommonITILActor::OBSERVER,
            \CommonITILActor::REQUESTER,
        ]);
    }

    public function testGetTeamRoleName(): void
    {
        $roles = \Ticket::getTeamRoles();
        foreach ($roles as $role) {
            $this->string(\Ticket::getTeamRoleName($role))->isNotEmpty();
        }
    }

    /**
     * Tests addTeamMember, deleteTeamMember, and getTeamMembers methods
     */
    public function testTeamManagement(): void
    {

        $ticket = new \Ticket();

        $tickets_id = $ticket->add([
            'name'      => 'Team test',
            'content'   => 'Team test'
        ]);
        $this->integer($tickets_id)->isGreaterThan(0);

        $this->array($ticket->getTeam())->isEmpty();

        // Add team members
        $this->boolean($ticket->addTeamMember(\User::class, 4, ['role' => Team::ROLE_ASSIGNED]))->isTrue(); // using constant value
        $this->boolean($ticket->addTeamMember(\User::class, 2, ['role' => 'observer']))->isTrue(); // using CommonITILActor contant name

        // Reload ticket from DB
        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();

        // Check team members
        $team = $ticket->getTeam();
        $this->array($team)->hasSize(2);
        $member = array_shift($team);
        $this->array($member)->hasKeys(['itemtype', 'items_id', 'role']);
        $this->string($member['itemtype'])->isEqualTo(\User::class);
        $this->integer($member['items_id'])->isEqualTo(2);
        $this->integer($member['role'])->isEqualTo(Team::ROLE_OBSERVER);
        $member = array_shift($team);
        $this->array($member)->hasKeys(['itemtype', 'items_id', 'role']);
        $this->string($member['itemtype'])->isEqualTo(\User::class);
        $this->integer($member['items_id'])->isEqualTo(4);
        $this->integer($member['role'])->isEqualTo(Team::ROLE_ASSIGNED);

        // Delete team member
        $this->boolean($ticket->deleteTeamMember(\User::class, 4, ['role' => Team::ROLE_ASSIGNED]))->isTrue();

        //Reload ticket from DB
        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();

        // Check team members
        $team = $ticket->getTeam();
        $this->array($team)->hasSize(1);
        $member = array_shift($team);
        $this->array($member)->hasKeys(['itemtype', 'items_id', 'role']);
        $this->string($member['itemtype'])->isEqualTo(\User::class);
        $this->integer($member['items_id'])->isEqualTo(2);
        $this->integer($member['role'])->isEqualTo(Team::ROLE_OBSERVER);

        // Delete team member
        $this->boolean($ticket->deleteTeamMember(\User::class, 2, ['role' => Team::ROLE_OBSERVER]))->isTrue();

        //Reload ticket from DB
        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();

        // Check team members
        $this->array($team)->isEmpty();

        // Add team members
        $this->boolean($ticket->addTeamMember(\Group::class, 2, ['role' => Team::ROLE_ASSIGNED]))->isTrue();

        // Reload ticket from DB
        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();

        // Check team members
        $team = $ticket->getTeam();
        $this->array($team)->hasSize(1);
        $this->array($team[0])->hasKeys(['itemtype', 'items_id', 'role']);
        $this->string($team[0]['itemtype'])->isEqualTo(\Group::class);
        $this->integer($team[0]['items_id'])->isEqualTo(2);
        $this->integer($team[0]['role'])->isEqualTo(Team::ROLE_ASSIGNED);
    }

    public function testGetTeamWithInvalidData(): void
    {
        global $DB;

        $this->login();

        $user_id = getItemByTypeName(User::class, TU_USER, true);

        $ticket = $this->createItem(
            \Ticket::class,
            [
                'name'             => __FUNCTION__,
                'content'          => __FUNCTION__,
                'entities_id'      => $this->getTestRootEntity(true),
                '_users_id_assign' => $user_id,
            ]
        );

        $this->array($ticket->getTeam())->hasSize(1); // TU_USER as assignee

        // Create invalid entries
        foreach ([CommonITILActor::REQUESTER, CommonITILActor::OBSERVER, CommonITILActor::ASSIGN] as $role) {
            $this->boolean(
                $DB->insert(
                    Ticket_User::getTable(),
                    [
                        'tickets_id' => $ticket->getID(),
                        'users_id'   => 978897, // not a valid id
                        'type'       => $role,
                    ]
                )
            )->isTrue();
            $this->boolean(
                $DB->insert(
                    Group_Ticket::getTable(),
                    [
                        'tickets_id' => $ticket->getID(),
                        'groups_id'  => 46543, // not a valid id
                        'type'       => $role,
                    ]
                )
            )->isTrue();
            $this->boolean(
                $DB->insert(
                    Supplier_Ticket::getTable(),
                    [
                        'tickets_id'   => $ticket->getID(),
                        'suppliers_id' => 99999, // not a valid id
                        'type'         => $role,
                    ]
                )
            )->isTrue();
        }

        $this->boolean($ticket->getFromDB($ticket->getID()))->isTrue();

        // Does not contains invalid entries
        $this->array($ticket->getTeam())->hasSize(1); // TU_USER as assignee

        // Check team in global Kanban
        $kanban = \Ticket::getDataToDisplayOnKanban(-1);
        $kanban_ticket = array_pop($kanban); // checked ticket is the last created
        $this->array($kanban_ticket)->hasKeys(['id', '_itemtype', '_team']);
        $this->integer($kanban_ticket['id'])->isEqualTo($ticket->getID());
        $this->string($kanban_ticket['_itemtype'])->isEqualTo(\Ticket::class);
        $this->array($kanban_ticket['_team'])->isEqualTo(
            [
                [
                    'itemtype'  => User::class,
                    'id'        => $user_id,
                    'firstname' => null,
                    'realname'  => null,
                    'name'      => '_test_user',
                ]
            ]
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
            'extra_input'       => ['type' => CommonITILActor::ASSIGN]
        ];

        // Add two users
        $ticket->input = [
            'id' => $ticket->getID(),
            'assigned_users' => [$user1, $user2]
        ];
        yield $tickets_base_params;

        // Remove one user
        $ticket->input = [
            'id' => $ticket->getID(),
            'assigned_users' => [$user1]
        ];
        yield $tickets_base_params;

        // Add one user
        $ticket->input = [
            'id' => $ticket->getID(),
            'assigned_users' => [$user1, $user3]
        ];
        yield $tickets_base_params;

        // Change both users
        $ticket->input = [
            'id' => $ticket->getID(),
            'assigned_users' => [$user2, $user4]
        ];
        yield $tickets_base_params;

        // Remove all users
        $ticket->input = [
            'id' => $ticket->getID(),
            'assigned_users' => []
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
            'extra_input'       => ['type' => CommonITILActor::ASSIGN]
        ];

        // Add two tickets
        $user->input = [
            'id' => $user->getID(),
            'linked_tickets' => [$ticket1, $ticket2]
        ];
        yield $user_base_params;

        // Remove one ticket
        $user->input = [
            'id' => $user->getID(),
            'linked_tickets' => [$ticket1]
        ];
        yield $user_base_params;

        // Add one tickett
        $user->input = [
            'id' => $user->getID(),
            'linked_tickets' => [$ticket1, $ticket3]
        ];
        yield $user_base_params;

        // Change both tickets
        $user->input = [
            'id' => $user->getID(),
            'linked_tickets' => [$ticket2, $ticket4]
        ];
        yield $user_base_params;

        // Remove all tickets
        $user->input = [
            'id' => $user->getID(),
            'linked_tickets' => []
        ];
        yield $user_base_params;
    }

    /**
     * Functional tests for update1NTableData and load1NTableData
     *
     * @dataProvider testUpdateLoad1NTableDataProvider
     */
    public function testUpdateLoad1NTableData(
        CommonDBTM $item,
        string $commondb_relation,
        string $field,
        array $extra_input
    ): void {
        // Keep track of the linked items
        $linked = $item->input[$field];
        $this->array($linked);

        // Update DB
        $this->callPrivateMethod($item, 'update1NTableData', $commondb_relation, $field, $extra_input);

        // Load values
        $this->callPrivateMethod($item, 'load1NTableData', $commondb_relation, $field, $extra_input);

        // Compare values
        $this->array($item->fields[$field])->isEqualTo($linked);
    }

    public function testNewToSolvedUnassigned()
    {
        $this->login();
        // Create ticket without automatic assignment
        $ticket = new \Ticket();
        $tickets_id = $ticket->add([
            'name' => 'testNewToSolvedUnassigned',
            'content' => 'testNewToSolvedUnassigned',
            '_skip_auto_assign' => true,
        ]);
        $this->integer($tickets_id)->isGreaterThan(0);

        // Check ticket status is new
        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();
        $this->integer($ticket->countUsers(\CommonITILActor::ASSIGN))->isEqualTo(0);
        $this->integer($ticket->fields['status'])->isEqualTo(\CommonITILObject::INCOMING);

        // Set status to solved
        $this->boolean($ticket->update([
            'id' => $tickets_id,
            'status' => \CommonITILObject::SOLVED,
            '_skip_auto_assign' => true,
        ]))->isTrue();

        // Check ticket status is solved
        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();
        $this->integer($ticket->fields['status'])->isEqualTo(\CommonITILObject::SOLVED);

        // Set status to new
        $this->boolean($ticket->update([
            'id' => $tickets_id,
            'status' => \CommonITILObject::INCOMING,
            '_skip_auto_assign' => true,
        ]))->isTrue();

        // Check ticket status is new
        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();
        $this->integer($ticket->fields['status'])->isEqualTo(\CommonITILObject::INCOMING);

        // Set status to closed
        $this->boolean($ticket->update([
            'id' => $tickets_id,
            'status' => \CommonITILObject::CLOSED,
            '_skip_auto_assign' => true,
        ]))->isTrue();

        // Check ticket status is closed
        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();
        $this->integer($ticket->fields['status'])->isEqualTo(\CommonITILObject::CLOSED);
    }

    public function testSurveyCreation()
    {
        global $DB;

        $this->login();
        // Create ticket
        $ticket = new \Ticket();
        $tickets_id = $ticket->add([
            'name' => 'testSurveyCreation',
            'content' => 'testSurveyCreation',
        ]);
        $this->integer($tickets_id)->isGreaterThan(0);

        $entities_id = $ticket->fields['entities_id'];
        // Update Entity to enable survey
        $entity = new \Entity();
        $result = $entity->update([
            'id'                => $entities_id,
            'inquest_config'    => 1,
            'inquest_rate'      => 100,
            'inquest_delay'     => 0,
        ]);
        $this->boolean($result)->isTrue();

        $inquest = new \TicketSatisfaction();

        // Verify no existing survey for ticket
        $it = $DB->request([
            'SELECT' => ['id'],
            'FROM' => \TicketSatisfaction::getTable(),
            'WHERE' => [
                'tickets_id' => $tickets_id,
            ],
        ]);
        $this->integer($it->count())->isEqualTo(0);

        // Close ticket
        $this->boolean($ticket->update([
            'id' => $tickets_id,
            'status' => \CommonITILObject::CLOSED
        ]))->isTrue();

        // Verify survey created
        $it = $DB->request([
            'SELECT' => ['id'],
            'FROM' => \TicketSatisfaction::getTable(),
            'WHERE' => [
                'tickets_id' => $tickets_id,
            ],
        ]);
        $this->integer($it->count())->isEqualTo(1);
    }

    public function testSurveyCreationOnReopened()
    {
        global $DB;

        $this->login();
        // Create ticket
        $ticket = new \Ticket();
        $tickets_id = $ticket->add([
            'name' => 'testSurveyCreation',
            'content' => 'testSurveyCreation',
        ]);
        $this->integer($tickets_id)->isGreaterThan(0);

        $entities_id = $ticket->fields['entities_id'];
        // Update Entity to enable survey
        $entity = new \Entity();
        $result = $entity->update([
            'id' => $entities_id,
            'inquest_config' => 1,
            'inquest_rate' => 100,
            'inquest_delay' => 0,
        ]);
        $this->boolean($result)->isTrue();

        $inquest = new \TicketSatisfaction();

        // Verify no existing survey for ticket
        $it = $DB->request([
            'SELECT' => ['id'],
            'FROM' => \TicketSatisfaction::getTable(),
            'WHERE' => [
                'tickets_id' => $tickets_id,
            ],
        ]);
        $this->integer($it->count())->isEqualTo(0);

        // Close ticket
        $this->boolean($ticket->update([
            'id' => $tickets_id,
            'status' => \CommonITILObject::CLOSED
        ]))->isTrue();

        // Reopen ticket
        $this->boolean($ticket->update([
            'id' => $tickets_id,
            'status' => \CommonITILObject::INCOMING
        ]))->isTrue();

        $result = $entity->update([
            'id' => $entities_id,
            'inquest_config' => 1,
            'inquest_rate' => 100,
            'inquest_delay' => 0,
        ]);
        $this->boolean($result)->isTrue();

        // Re-close ticket
        $this->boolean($ticket->update([
            'id' => $tickets_id,
            'status' => \CommonITILObject::CLOSED
        ]))->isTrue();

        // Verify survey created and only one exists
        $it = $DB->request([
            'SELECT' => ['id'],
            'FROM' => \TicketSatisfaction::getTable(),
            'WHERE' => [
                'tickets_id' => $tickets_id,
            ],
        ]);
        $this->integer($it->count())->isEqualTo(1);
    }

    public function testCronSurveyCreation(): void
    {
        $this->login();

        $root_entity_id    = $this->getTestRootEntity(true);
        $child_1_entity_id = getItemByTypeName('Entity', '_test_child_1', true);
        $child_2_entity_id = getItemByTypeName('Entity', '_test_child_2', true);

        $now              = \Session::getCurrentTime();
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
            \Ticket::class,
            [
                'name'        => "test root entity survey",
                'content'     => "test root entity survey",
                'entities_id' => $root_entity_id,
                'status'      => CommonITILObject::CLOSED
            ]
        );

        // Create a closed ticket on test child entity 1
        $_SESSION['glpi_currenttime'] = $four_hours_ago;
        $child_1_ticket = $this->createItem(
            \Ticket::class,
            [
                'name'        => "test child entity 1 survey",
                'content'     => "test child entity 1 survey",
                'entities_id' => $child_1_entity_id,
                'status'      => CommonITILObject::CLOSED
            ]
        );

        // Create a closed ticket on test child entity 2
        $_SESSION['glpi_currenttime'] = $two_hours_ago;
        $child_1_ticket = $this->createItem(
            \Ticket::class,
            [
                'name'        => "test child entity 2 survey",
                'content'     => "test child entity 2 survey",
                'entities_id' => $child_2_entity_id,
                'status'      => CommonITILObject::CLOSED
            ]
        );

        // Ensure no survey has been created yet
        $ticket_satisfaction = new \TicketSatisfaction();
        $this->integer(count($ticket_satisfaction->find(['tickets_id' => $root_ticket->getID()])))->isEqualTo(0);
        $this->integer(count($ticket_satisfaction->find(['tickets_id' => $child_1_ticket->getID()])))->isEqualTo(0);

        // Launch cron to create surveys
        CronTask::launch(
            - CronTask::MODE_INTERNAL, // force
            1,
            'createinquest'
        );

        // Ensure survey has been created
        $ticket_satisfaction = new \TicketSatisfaction();
        $this->integer(count($ticket_satisfaction->find(['tickets_id' => $root_ticket->getID()])))->isEqualTo(1);
        $this->integer(count($ticket_satisfaction->find(['tickets_id' => $child_1_ticket->getID()])))->isEqualTo(1);

        // Check `max_closedate` values in DB
        $expected_db_values = [
            0                  => $four_hours_ago,   // last ticket closedate from entities that inherits the config
            $root_entity_id    => $twelve_hours_ago, // not updated as it inherits the config
            $child_1_entity_id => $twelve_hours_ago, // not updated as it inherits the config
            $child_2_entity_id => $two_hours_ago,    // last ticket closedate from self as it has its own config
        ];
        foreach ($expected_db_values as $entity_id => $date) {
            $entity = new Entity();
            $this->boolean($entity->getFromDB($entity_id))->isTrue();
            $this->string($entity->fields['max_closedate'])->isEqualTo($date);
        }

        // Check `max_closedate` returned by `Entity::getUsedConfig()`
        $expected_config_values = [
            0                  => $four_hours_ago, // last ticket closedate from entities that inherits the config
            $root_entity_id    => $four_hours_ago, // inherited value
            $child_1_entity_id => $four_hours_ago, // inherited value
            $child_2_entity_id => $two_hours_ago,  // last ticket closedate from self as it has its own config
        ];
        foreach ($expected_config_values as $entity_id => $date) {
            $this->string(Entity::getUsedConfig('inquest_config', $entity_id, 'max_closedate'))->isEqualTo($date);
        }
    }

    public function testAddAssignWithoutUpdateRight()
    {
        $this->login();

        $ticket = new \Ticket();
        $tickets_id = $ticket->add([
            'name' => 'testAddAssignWithoutUpdateRight',
            'content' => 'testAddAssignWithoutUpdateRight',
            '_skip_auto_assign' => true,
        ]);
        $this->integer($tickets_id)->isGreaterThan(0);

        $ticket->loadActors();
        $this->integer($ticket->countUsers(\CommonITILActor::ASSIGN))->isEqualTo(0);
        $this->integer($ticket->countUsers(\CommonITILActor::REQUESTER))->isEqualTo(0);

        $this->changeTechRight(\Ticket::ASSIGN | \Ticket::READALL);
        $this->boolean($ticket->canUpdateItem())->isFalse();
        $this->boolean((bool) $ticket->canAssign())->isTrue();
        $this->boolean($ticket->update([
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
                    ]
                ],
                'assign' => [
                    [
                        'itemtype'  => 'User',
                        'items_id'  => getItemByTypeName('User', 'tech', true),
                        'use_notification' => 0,
                        'alternative_email' => '',
                    ]
                ],
            ],
        ]))->isTrue();
        $ticket->loadActors();
        // Verify new assignee was added
        $this->integer($ticket->countUsers(\CommonITILActor::ASSIGN))->isEqualTo(1);
        // Verify new requester wasn't added
        $this->integer($ticket->countUsers(\CommonITILActor::REQUESTER))->isEqualTo(0);
    }

    public function testAddAssignWithoutAssignRight()
    {
        $this->login();

        $ticket = new \Ticket();
        $tickets_id = $ticket->add([
            'name' => 'testAddAssignWithoutAssignRight',
            'content' => 'testAddAssignWithoutAssignRight',
            '_skip_auto_assign' => true,
        ]);
        $this->integer($tickets_id)->isGreaterThan(0);

        $ticket->loadActors();
        $this->integer($ticket->countUsers(\CommonITILActor::ASSIGN))->isEqualTo(0);
        $this->integer($ticket->countUsers(\CommonITILActor::REQUESTER))->isEqualTo(0);

        $this->changeTechRight(\Ticket::READALL | UPDATE);
        $this->boolean($ticket->canUpdateItem())->isTrue();
        $this->boolean((bool) $ticket->canAssign())->isFalse();
        $this->boolean($ticket->update([
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
                    ]
                ],
                'assign' => [
                    [
                        'itemtype'  => 'User',
                        'items_id'  => getItemByTypeName('User', 'tech', true),
                        'use_notification' => 0,
                        'alternative_email' => '',
                    ]
                ],
            ],
        ]))->isTrue();
        $ticket->loadActors();
        // Verify new assignee wasn't added
        $this->integer($ticket->countUsers(\CommonITILActor::ASSIGN))->isEqualTo(0);
        // Verify new requester was added
        $this->integer($ticket->countUsers(\CommonITILActor::REQUESTER))->isEqualTo(2);
    }

    public function testAddActorsWithAssignAndUpdateRight()
    {
        $this->login();

        $ticket = new \Ticket();
        $tickets_id = $ticket->add([
            'name' => 'testAddActorsWithAssignAndUpdateRight',
            'content' => 'testAddActorsWithAssignAndUpdateRight',
            '_skip_auto_assign' => true,
        ]);
        $this->integer($tickets_id)->isGreaterThan(0);

        $ticket->loadActors();
        $this->integer($ticket->countUsers(\CommonITILActor::ASSIGN))->isEqualTo(0);
        $this->integer($ticket->countUsers(\CommonITILActor::REQUESTER))->isEqualTo(0);

        $this->changeTechRight(\Ticket::ASSIGN | UPDATE | \Ticket::READALL);
        $this->boolean($ticket->canUpdateItem())->isTrue();
        $this->boolean((bool) $ticket->canAssign())->isTrue();
        $this->boolean($ticket->update([
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
                    ]
                ],
                'assign' => [
                    [
                        'itemtype'  => 'User',
                        'items_id'  => getItemByTypeName('User', 'tech', true),
                        'use_notification' => 0,
                        'alternative_email' => '',
                    ]
                ],
            ],
        ]))->isTrue();
        $ticket->loadActors();
        // Verify new assignee was added
        $this->integer($ticket->countUsers(\CommonITILActor::ASSIGN))->isEqualTo(1);
        // Verify new requester was added
        $this->integer($ticket->countUsers(\CommonITILActor::REQUESTER))->isEqualTo(2);
    }


    public function testGetActorsForType()
    {
        $this->login();

        $ticket = new \Ticket();
        $ticket->getEmpty();

        $tech_id = getItemByTypeName('User', 'tech', true);
        $postonly_id = getItemByTypeName('User', 'post-only', true);

        // ## 1st - test auto requester and assign feature
        // ###############################################

        $this->array($ticket->getActorsForType(\CommonITILActor::REQUESTER))->hasSize(1);
        $this->array($ticket->getActorsForType(\CommonITILActor::OBSERVER))->hasSize(0);
        $this->array($ticket->getActorsForType(\CommonITILActor::ASSIGN))->hasSize(1);

        // disable autoactor by parameter
        $params = ['_skip_default_actor' => true];
        $this->array($ticket->getActorsForType(\CommonITILActor::REQUESTER, $params))->hasSize(0);
        $this->array($ticket->getActorsForType(\CommonITILActor::OBSERVER, $params))->hasSize(0);
        $this->array($ticket->getActorsForType(\CommonITILActor::ASSIGN, $params))->hasSize(0);

        // disable autoactor in session
        $_SESSION['glpiset_default_requester'] = false;
        $_SESSION['glpiset_default_tech']      = false;
        $this->array($ticket->getActorsForType(\CommonITILActor::REQUESTER))->hasSize(0);
        $this->array($ticket->getActorsForType(\CommonITILActor::OBSERVER))->hasSize(0);
        $this->array($ticket->getActorsForType(\CommonITILActor::ASSIGN))->hasSize(0);

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
            ]
        ];
        $this->array($ticket->getActorsForType(\CommonITILActor::REQUESTER, $params))->hasSize(2);
        $this->array($ticket->getActorsForType(\CommonITILActor::OBSERVER, $params))->hasSize(1);
        $this->array($ticket->getActorsForType(\CommonITILActor::ASSIGN, $params))->hasSize(2);

        $_SESSION['glpiset_default_requester'] = false;
        $_SESSION['glpiset_default_tech']      = false;
        $this->array($ticket->getActorsForType(\CommonITILActor::REQUESTER, $params))->hasSize(1)
            ->integer[0]
            ->integer['items_id']->isEqualTo($postonly_id);
        $this->array($ticket->getActorsForType(\CommonITILActor::OBSERVER, $params))->hasSize(1)
            ->integer[0]
            ->integer['items_id']->isEqualTo($postonly_id);
        $this->array($ticket->getActorsForType(\CommonITILActor::ASSIGN, $params))->hasSize(1)
            ->integer[0]
            ->integer['items_id']->isEqualTo($tech_id);

        // apend groups
        $params['_predefined_fields']['_groups_id_requester'] = [1];
        $params['_predefined_fields']['_groups_id_observer'] = [1];
        $params['_predefined_fields']['_groups_id_assign'] = [1];

        $this->array($ticket->getActorsForType(\CommonITILActor::REQUESTER, $params))->hasSize(2)
            ->integer[1]
            ->string['text']->isEqualTo("_test_group_1");
        $this->array($ticket->getActorsForType(\CommonITILActor::OBSERVER, $params))->hasSize(2)
            ->integer[1]
            ->string['text']->isEqualTo("_test_group_1");
        $this->array($ticket->getActorsForType(\CommonITILActor::ASSIGN, $params))->hasSize(2)
            ->integer[1]
            ->string['text']->isEqualTo("_test_group_1");

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
                    ['itemtype' => 'Group', 'items_id' => 1]
                ],
                'observer'  => [
                    ['itemtype' => 'User',  'items_id' => $postonly_id],
                    ['itemtype' => 'Group', 'items_id' => 1]
                ],
                'assign'    => [
                    ['itemtype' => 'User',  'items_id' => $tech_id],
                    ['itemtype' => 'Group', 'items_id' => 1]
                ],
            ]
        ];
        $requesters = $ticket->getActorsForType(\CommonITILActor::REQUESTER, $params);
        $this->array($requesters)->hasSize(2)
            ->integer[0]
            ->string['text']->isEqualTo("post-only");
        $this->array($requesters)
            ->integer[1]
            ->string['text']->isEqualTo("_test_group_1");
        $observers = $ticket->getActorsForType(\CommonITILActor::OBSERVER, $params);
        $this->array($observers)->hasSize(2)
            ->integer[0]
            ->string['text']->isEqualTo("post-only");
        $this->array($observers)
            ->integer[1]
            ->string['text']->isEqualTo("_test_group_1");
        $assignees = $ticket->getActorsForType(\CommonITILActor::ASSIGN, $params);
        $this->array($assignees)->hasSize(2)
            ->integer[0]
            ->string['text']->isEqualTo("tech");
        $this->array($assignees)
            ->integer[1]
            ->string['text']->isEqualTo("_test_group_1");
    }


    public function testNeedReopen()
    {
        $this->login();

        $tech_id     = getItemByTypeName('User', 'tech', true);
        $postonly_id = getItemByTypeName('User', 'post-only', true);
        $normal_id   = getItemByTypeName('User', 'normal', true);

        $requester_group = $this->createItem("Group", [
            'name' => "testNeedReopen"
        ]);
        $this->createItem("Group_User", [
            'users_id' => $normal_id,
            'groups_id' => $requester_group->getID(),
        ]);

        $ticket = new \Ticket();
        $tickets_id = $ticket->add([
            'name'                => 'testNeedReopen',
            'content'             => 'testNeedReopen',
            '_users_id_requester' => $postonly_id,
            '_users_id_assign'    => $tech_id,
        ]);
        $this->integer($tickets_id)->isGreaterThan(0);
        $this->boolean((bool)$ticket->getFromDB($ticket->getID()))->isTrue();
        $this->integer($ticket->fields['status'])->isEqualTo(\Ticket::ASSIGNED);
        $this->boolean((bool)$ticket->needReopen())->isFalse();

        $ticket->update([
            'id' => $tickets_id,
            'status' => \Ticket::WAITING,
        ]);

        // tech user cant reopen
        $this->boolean((bool)$ticket->getFromDB($ticket->getID()))->isTrue();
        $this->integer($ticket->fields['status'])->isEqualTo(\Ticket::WAITING);
        $this->boolean((bool)$ticket->needReopen())->isFalse();

        // requester can reopen
        $this->login('post-only', 'postonly');
        $this->boolean((bool)$ticket->getFromDB($ticket->getID()))->isTrue();
        $this->integer($ticket->fields['status'])->isEqualTo(\Ticket::WAITING);
        $this->boolean((bool)$ticket->needReopen())->isTrue();

        // force a reopen
        $followup = new \ITILFollowup();
        $followup->add([
            'itemtype'   => 'Ticket',
            'items_id'   => $tickets_id,
            'content'    => 'testNeedReopen',
            'add_reopen' => 1,
        ]);

        // requester cant reopen anymore (ticket is already in an open state)
        $this->boolean((bool)$ticket->getFromDB($ticket->getID()))->isTrue();
        $this->integer($ticket->fields['status'])->isEqualTo(\Ticket::ASSIGNED);
        $this->boolean((bool)$ticket->needReopen())->isFalse();

        // Test reopen as a member of a requester group
        $ticket = $this->createItem('Ticket', [
            'name'                 => 'testNeedReopen requester group',
            'content'              => 'testNeedReopen requester group',
            '_users_id_requester'  => $postonly_id,
            '_groups_id_requester' => $requester_group->getID(),
            '_users_id_assign'     => $tech_id,
        ]);

        $this->updateItem('Ticket', $ticket->getID(), [
            'status' => \Ticket::WAITING,
        ]);
        $ticket->getFromDB($ticket->getID());

        $this->login('normal', 'normal');
        $this->boolean((bool)$ticket->needReopen())->isTrue();
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
        $this->integer($group_3_id)->isGreaterThan(0);

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

    /**
     * @dataProvider assignFromCategoryOrItemProvider
     */
    public function testAssignFromCategoryOrItem(
        int $auto_assign_mode,
        ?array $category_input,
        ?array $computer_input,
        array $ticket_input,
        array $expected_actors
    ): void {
        $entity_id = getItemByTypeName('Entity', '_test_root_entity', true);

        $this->login();

        $entity = new Entity();
        $this->boolean($entity->update(['id' => $entity_id, 'auto_assign_mode' => $auto_assign_mode]))->isTrue();

        $itilcategory_id = 0;
        if ($category_input !== null) {
            $itilcategory = new ITILCategory();
            $itilcategory_id = $itilcategory->add(
                $category_input + [
                    'name'        => __METHOD__,
                    'entities_id' => $entity_id,
                ]
            );
            $this->integer($itilcategory_id)->isGreaterThan(0);
        }

        $items_id = [];
        if ($computer_input !== null) {
            $computer = new Computer();
            $computer_id = $computer->add(
                $computer_input + [
                    'name'        => __METHOD__,
                    'entities_id' => $entity_id,
                ]
            );
            $this->integer($computer_id)->isGreaterThan(0);
            $items_id[Computer::class] = [$computer_id];
        }

        $ticket = new \Ticket();
        $ticket_id = $ticket->add(
            $ticket_input + [
                'name'              => __METHOD__,
                'content'           => __METHOD__,
                'entities_id'       => $entity_id,
                'itilcategories_id' => $itilcategory_id,
                'items_id'          => $items_id,
            ]
        );
        $this->integer($ticket_id)->isGreaterThan(0);

        $ticket->getFromDB($ticket->getID());
        $actors = $ticket->getActorsForType(CommonITILActor::ASSIGN);
        $this->array($actors)->hasSize(count($expected_actors));

        foreach ($expected_actors as $expected_actor) {
            $found = false;
            foreach ($actors as $actor) {
                if (array_intersect_assoc($expected_actor, $actor) === $expected_actor) {
                    // Found an actor that has same properties as those defined in expected actor
                    $found = true;
                    break;
                }
            }
            $this->boolean($found)->isTrue(json_encode($expected_actor));
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
                ]
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
                ]
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
                ]
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
                ]
            ],
            'expected' => array_values($_SESSION['glpiactiveentities']),
        ];
    }

    /**
     * @dataProvider requestersEntitiesProvider
     */
    public function testGetEntitiesForRequesters(array $params, array $expected)
    {
        $this->newTestedInstance();
        $this->array($this->testedInstance->getEntitiesForRequesters($params))->isIdenticalTo($expected);
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
        $this->integer($entity_id)->isGreaterThan(0);

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
        $this->integer($user_id)->isGreaterThan(0);

        // Create a new ticket with the user as requester
        $ticket_requester = new \Ticket();
        $ticket_id_requester = $ticket_requester->add([
            'name' => 'testShowCentralCountCriteria',
            'content' => 'testShowCentralCountCriteria',
            'entities_id' => $entity_id,
        ]);
        $this->integer($ticket_id_requester)->isGreaterThan(0);

        // Add the user as requester for the ticket
        $ticket_user_requester = new \Ticket_User();
        $ticket_user_id_requester = $ticket_user_requester->add([
            'tickets_id' => $ticket_id_requester,
            'users_id' => $user_id,
            'type' => CommonITILActor::REQUESTER,
        ]);
        $this->integer($ticket_user_id_requester)->isGreaterThan(0);

        // Create a new ticket with the user as observer
        $ticket_observer = new \Ticket();
        $ticket_id_observer = $ticket_observer->add([
            'name' => 'testShowCentralCountCriteria',
            'content' => 'testShowCentralCountCriteria',
            'entities_id' => $entity_id,
        ]);
        $this->integer($ticket_id_observer)->isGreaterThan(0);

        // Add the user as observer for the ticket
        $ticket_user_observer = new \Ticket_User();
        $ticket_user_observer_id = $ticket_user_observer->add([
            'tickets_id' => $ticket_id_observer,
            'users_id' => $user_id,
            'type' => CommonITILActor::OBSERVER,
        ]);
        $this->integer($ticket_user_observer_id)->isGreaterThan(0);

        // Create a new ticket
        $ticket = new \Ticket();
        $ticket_id = $ticket->add([
            'name' => 'testShowCentralCountCriteria',
            'content' => 'testShowCentralCountCriteria',
            'entities_id' => $entity_id,
        ]);
        $this->integer($ticket_id)->isGreaterThan(0);

        // Login with the new user
        $this->login('testShowCentralCountCriteria', 'testShowCentralCountCriteria');

        // Check if the user can see 2 tickets
        $criteria = $this->callPrivateMethod($this->newTestedInstance(), 'showCentralCountCriteria', true);
        $iterator = $DB->request($criteria);
        foreach ($iterator as $data) {
            $this->array($data)
                ->hasKey('status')
                ->hasKey('COUNT')
                ->integer['status']->isEqualTo(1)
                ->integer['COUNT']->isEqualTo(2);
        }

        // Check if the global view return 3 tickets
        $criteria = $this->callPrivateMethod($this->newTestedInstance(), 'showCentralCountCriteria', false);
        $iterator = $DB->request($criteria);
        foreach ($iterator as $data) {
            $this->array($data)
                ->hasKey('status')
                ->hasKey('COUNT')
                ->integer['status']->isEqualTo(1)
                ->integer['COUNT']->isEqualTo(3);
        }
    }

    protected function timelineItemsProvider(): iterable
    {
        $now = time();

        $postonly_user_id = getItemByTypeName(\User::class, 'post-only', true);
        $normal_user_id   = getItemByTypeName(\User::class, 'normal', true);
        $tech_user_id     = getItemByTypeName(\User::class, 'tech', true);

        $this->login();

        $ticket = $this->createItem(
            \Ticket::class,
            [
                'name'                => __FUNCTION__,
                'content'             => __FUNCTION__,
                '_users_id_requester' => $postonly_user_id,
                '_users_id_observer'  => $normal_user_id,
                '_users_id_assign'    => $tech_user_id,
            ]
        );

        $this->createItem(
            \ITILFollowup::class,
            [
                'itemtype'      => \Ticket::class,
                'items_id'      => $ticket->getID(),
                'content'       => 'public followup',
                'date_creation' => date('Y-m-d H:i:s', strtotime('+10s', $now)), // to ensure result order is correct
            ]
        );

        $this->createItem(
            \ITILFollowup::class,
            [
                'itemtype'      => \Ticket::class,
                'items_id'      => $ticket->getID(),
                'content'       => 'private followup of tech user',
                'is_private'    => 1,
                'users_id'      => $tech_user_id,
                'date_creation' => date('Y-m-d H:i:s', strtotime('+20s', $now)), // to ensure result order is correct
            ]
        );

        $this->createItem(
            \ITILFollowup::class,
            [
                'itemtype'   => \Ticket::class,
                'items_id'   => $ticket->getID(),
                'content'    => 'private followup of normal user',
                'is_private' => 1,
                'users_id'   => $normal_user_id,
                'date_creation' => date('Y-m-d H:i:s', strtotime('+30s', $now)), // to ensure result order is correct
            ]
        );

        $this->createItem(
            \TicketTask::class,
            [
                'tickets_id'    => $ticket->getID(),
                'content'       => 'public task',
                'date_creation' => date('Y-m-d H:i:s', strtotime('+10s', $now)), // to ensure result order is correct
            ]
        );

        $this->createItem(
            \TicketTask::class,
            [
                'tickets_id'    => $ticket->getID(),
                'content'       => 'private task of tech user',
                'is_private'    => 1,
                'users_id'      => $tech_user_id,
                'date_creation' => date('Y-m-d H:i:s', strtotime('+20s', $now)), // to ensure result order is correct
            ]
        );

        $this->createItem(
            \TicketTask::class,
            [
                'tickets_id'    => $ticket->getID(),
                'content'       => 'private task of normal user',
                'is_private'    => 1,
                'users_id'      => $normal_user_id,
                'date_creation' => date('Y-m-d H:i:s', strtotime('+30s', $now)), // to ensure result order is correct
            ]
        );

        // tech has rights to see all private followups/tasks
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
                'private task of normal user',
                'private task of tech user',
                'public task',
            ],
        ];

        // normal will only see own private followups/tasks
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
                    'private task of normal user',
                    'private task of tech user',
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

    /**
     * @dataProvider timelineItemsProvider
     */
    public function testGetTimelineItems(
        ?string $login,
        ?string $pass,
        int $ticket_id,
        array $options,
        array $expected_followups,
        array $expected_tasks
    ): void {
        if ($pass !== null) {
            $this->login($login, $pass);
        } else {
            $this->resetSession();
        }

        $ticket = new \Ticket();
        $this->boolean($ticket->getFromDB($ticket_id))->isTrue();

        $this->array($timeline = $ticket->getTimelineItems($options));

        $followups_content = array_map(
            fn($entry) => $entry['item']['content'],
            array_values(
                array_filter(
                    $timeline,
                    fn($entry) => $entry['type'] === \ITILFollowup::class
                )
            ),
        );
        $this->array($followups_content)->isEqualTo($expected_followups);

        $tasks_content = array_map(
            fn($entry) => $entry['item']['content'],
            array_values(
                array_filter(
                    $timeline,
                    fn($entry) => $entry['type'] === \TicketTask::class
                )
            ),
        );
        $this->array($tasks_content)->isEqualTo($expected_tasks);
    }

    /**
     * Check that when a ticket has multiple timeline items with the same creation date, they are ordered by ID
     * @return void
     * @see https://github.com/glpi-project/glpi/issues/15761
     */
    public function testGetTimelineItemsSameDate()
    {
        $this->login();

        $ticket = new \Ticket();
        $this->integer($tickets_id = $ticket->add([
            'name' => __FUNCTION__,
            'content' => __FUNCTION__,
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
        ]))->isGreaterThan(0);


        $task = new \TicketTask();
        $date = date('Y-m-d H:i:s');
        // Create one task with a different creation date after the others
        $this->integer($task->add([
            'tickets_id' => $tickets_id,
            'content' => __FUNCTION__ . 'after',
            'date_creation' => date('Y-m-d H:i:s', strtotime('+1 second', strtotime($date))),
        ]))->isGreaterThan(0);
        // Create one task with a different creation date before the others
        $this->integer($task->add([
            'tickets_id' => $tickets_id,
            'content' => __FUNCTION__ . 'before',
            'date_creation' => date('Y-m-d H:i:s', strtotime('-1 second', strtotime($date))),
        ]))->isGreaterThan(0);
        for ($i = 0; $i < 20; $i++) {
            $this->integer($task->add([
                'tickets_id' => $tickets_id,
                'content' => __FUNCTION__,
                'date_creation' => $date,
            ]))->isGreaterThan(0);
        }

        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();
        $timeline_items = $ticket->getTimelineItems();

        // Ensure that the tasks are ordered by creation date. And, if they have the same creation date, by ID
        $tasks = array_values(array_filter($timeline_items, static fn($entry) => $entry['type'] === \TicketTask::class));
        // Check tasks are in order of creation date
        $creation_dates = array_map(static fn($entry) => $entry['item']['date_creation'], $tasks);
        $sorted_dates = $creation_dates;
        sort($sorted_dates);
        $this->array($creation_dates)->isEqualTo($sorted_dates);
        // Check tasks with same creation date are ordered by ID
        $same_date_tasks = array_filter($tasks, static fn($entry) => $entry['item']['date_creation'] === $date);
        $ids = array_map(static fn($entry) => $entry['item']['id'], $same_date_tasks);
        $sorted_ids = $ids;
        sort($sorted_ids, SORT_NUMERIC);
        $this->array(array_values($ids))->isEqualTo(array_values($sorted_ids));

        // Check reverse timeline order
        $timeline_items = $ticket->getTimelineItems(['sort_by_date_desc' => true]);
        $tasks = array_values(array_filter($timeline_items, static fn($entry) => $entry['type'] === \TicketTask::class));
        $creation_dates = array_map(static fn($entry) => $entry['item']['date_creation'], $tasks);
        $sorted_dates = $creation_dates;
        sort($sorted_dates);
        $sorted_dates = array_reverse($sorted_dates);
        $this->array($creation_dates)->isEqualTo($sorted_dates);
        $same_date_tasks = array_filter($tasks, static fn($entry) => $entry['item']['date_creation'] === $date);
        $ids = array_map(static fn($entry) => $entry['item']['id'], $same_date_tasks);
        $sorted_ids = $ids;
        sort($sorted_ids, SORT_NUMERIC);
        $sorted_ids = array_reverse($sorted_ids);
        $this->array(array_values($ids))->isEqualTo(array_values($sorted_ids));
    }

    /**
     * Data provider for the testCountActors function
     *
     * @return iterable
     */
    protected function testCountActorsProvider(): iterable
    {
        $this->login();
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
            '_actors'     => []
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
            ]
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
            ]
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
            ]
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
            ]
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
            ]
        ]);
        yield [$ticket, 6];
    }

    /**
     * Test the testCountActors method
     *
     * @dataProvider testCountActorsProvider
     *
     * @param \Ticket $ticket
     * @param int $expected
     *
     * @return void
     */
    public function testCountActors(\Ticket $ticket, int $expected): void
    {
        $this->integer($ticket->countActors())->isEqualTo($expected);
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
            '_actors'     => []
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
            ]
        ]);
        $ticket_1->getFromDB($ticket_1->getID());
        yield [
            $ticket_1,
            [CommonITILActor::REQUESTER => [$user_1, $user_2]],
            [
                CommonITILActor::ASSIGN => [$group_1,],
                CommonITILActor::OBSERVER => [$group_2],
            ],
            [CommonITILActor::ASSIGN => [$supplier_1, $supplier_2]]
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
            ]
        ]);
        yield [
            $ticket_2,
            [CommonITILActor::ASSIGN => [$user_1, $user_2]],
            [CommonITILActor::ASSIGN => [$group_1, $group_2]],
            [CommonITILActor::ASSIGN => [$supplier_1, $supplier_2]]
        ];

        // Case 4: load ticket 2 into ticket 1 variable (simulate reusing an object for multiple rows)
        $ticket_1->getFromDb($ticket_2->getID());
        yield [
            $ticket_1,
            [CommonITILActor::ASSIGN => [$user_1, $user_2]],
            [CommonITILActor::ASSIGN => [$group_1, $group_2]],
            [CommonITILActor::ASSIGN => [$supplier_1, $supplier_2]]
        ];
    }

    /**
     * Test the magic properties used to lazy load actors
     *
     * @dataProvider testActorsMagicPropertiesProvider
     *
     * @param \Ticket $ticket
     * @param array $expected_users
     * @param array $expected_groups
     * @param array $exptected_suppliers
     *
     * @return void
     */
    public function testActorsMagicProperties(
        \Ticket $ticket,
        array $expected_users,
        array $expected_groups,
        array $expected_suppliers
    ) {
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

        $this->array($simplied_actors[User::class])->isEqualTo($expected_users);
        $this->array($simplied_actors[Group::class])->isEqualTo($expected_groups);
        $this->array($simplied_actors[Supplier::class])->isEqualTo($expected_suppliers);
    }

    public function testDynamicProperties(): void
    {
        $ticket = new \Ticket();

        $this->when(
            function () use ($ticket) {
                $ticket->plugin_xxx_data = 'test';
            }
        )
         ->error
         ->withMessage('Creation of dynamic property Ticket::$plugin_xxx_data is deprecated')
         ->exists();

        $this->boolean(property_exists($ticket, 'plugin_xxx_data'))->isTrue();
        $this->string($ticket->plugin_xxx_data)->isEqualTo('test');
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

        $ticket = new \Ticket();
        $this->integer($not_my_tickets_id = $ticket->add([
            'name'      => __FUNCTION__,
            'content'   => __FUNCTION__,
            'users_id'  => $_SESSION['glpiID'] + 1, // Not current user
            '_skip_auto_assign' => true,
            'entities_id' => $this->getTestRootEntity(true),
        ]))->isGreaterThan(0);

        $dropdown_params = [
            'itemtype' => \Ticket::class,
            'entity_restrict' => -1,
            'page_limit' => 1000
        ];
        $idor = \Session::getNewIDORToken(\Ticket::class, $dropdown_params);
        $values = \Dropdown::getDropdownValue($dropdown_params + ['_idor_token' => $idor], false);
        $this->array($values['results'])->size->isGreaterThan(1);
        $this->boolean($fn_dropdown_has_id($values['results'], $not_my_tickets_id))->isTrue();

        // Remove permission to see all tickets
        $_SESSION['glpiactiveprofile']['ticket'] = READ;
        $idor = \Session::getNewIDORToken(\Ticket::class, $dropdown_params);
        $values = \Dropdown::getDropdownValue($dropdown_params + ['_idor_token' => $idor], false);
        $this->array($values['results'])->size->isGreaterThan(1);
        $this->boolean($fn_dropdown_has_id($values['results'], $not_my_tickets_id))->isFalse();

        // Add user as requester
        $ticket_user = new \Ticket_User();
        $ticket_user->add([
            'tickets_id' => $not_my_tickets_id,
            'users_id' => $_SESSION['glpiID'],
            'type' => CommonITILActor::REQUESTER,
        ]);
        $idor = \Session::getNewIDORToken(\Ticket::class, $dropdown_params);
        $values = \Dropdown::getDropdownValue($dropdown_params + ['_idor_token' => $idor], false);
        $this->array($values['results'])->size->isGreaterThan(1);
        $this->boolean($fn_dropdown_has_id($values['results'], $not_my_tickets_id))->isTrue();
    }
}
