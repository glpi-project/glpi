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

namespace Glpi\Api\HL\Controller;

use Calendar;
use Change;
use ChangeTemplate;
use CommonDBTM;
use CommonITILObject;
use Entity;
use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\Middleware\ResultFormatterMiddleware;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\RouteVersion;
use Glpi\Api\HL\Search;
use Glpi\Http\JSONResponse;
use Glpi\Http\Request;
use Glpi\Http\Response;
use Glpi\Team\Team;
use Group;
use Html;
use PlanningEventCategory;
use PlanningExternalEventTemplate;
use Problem;
use Ticket;
use TicketTemplate;
use User;

#[Route(path: '/Assistance', requirements: [
    'itemtype' => 'Ticket|Change|Problem',
    'id' => '\d+'
], tags: ['Assistance'])]
#[Doc\Route(
    parameters: [
        [
            'name' => 'itemtype',
            'description' => 'Ticket, Change or Problem',
            'location' => Doc\Parameter::LOCATION_PATH,
            'schema' => ['type' => Doc\Schema::TYPE_STRING]
        ]
    ]
)]
final class ITILController extends AbstractController
{
    use CRUDControllerTrait;

    public static function getRawKnownSchemas(): array
    {
        $schemas = [];

        $schemas['ITILCategory'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => 'ITILCategory',
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'x-readonly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'completename' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'entity' => self::getDropdownTypeSchema(class: \Entity::class, full_schema: 'Entity'),
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'parent' => self::getDropdownTypeSchema(class: \ITILCategory::class, full_schema: 'ITILCategory'),
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ]
        ];

        $common_itiltemplate_properties = [
            'id' => [
                'type' => Doc\Schema::TYPE_INTEGER,
                'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                'x-readonly' => true,
            ],
            'name' => ['type' => Doc\Schema::TYPE_STRING],
            'completename' => ['type' => Doc\Schema::TYPE_STRING],
            'comment' => ['type' => Doc\Schema::TYPE_STRING],
            'entity' => self::getDropdownTypeSchema(class: \Entity::class, full_schema: 'Entity'),
            'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN],
        ];
        $schemas['TicketTemplate'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => 'TicketTemplate',
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => $common_itiltemplate_properties
        ];
        $schemas['ChangeTemplate'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => 'ChangeTemplate',
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => $common_itiltemplate_properties
        ];
        $schemas['ProblemTemplate'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => 'ProblemTemplate',
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => $common_itiltemplate_properties
        ];

        $base_schema = [
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'x-readonly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'content' => ['type' => Doc\Schema::TYPE_STRING],
                'is_deleted' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'category' => self::getDropdownTypeSchema(class: \ITILCategory::class, full_schema: 'ITILCategory'),
                'location' => self::getDropdownTypeSchema(class: \Location::class, full_schema: 'Location'),
                'urgency' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'enum' => [1, 2, 3, 4, 5]
                ],
                'impact' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'enum' => [1, 2, 3, 4, 5]
                ],
                'priority' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'enum' => [1, 2, 3, 4, 5]
                ],
                'actiontime' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'x-readonly' => true,
                ],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ]
        ];

        $itil_types = [Ticket::class, Change::class, Problem::class];

        /** @var class-string<CommonITILObject> $itil_type */
        foreach ($itil_types as $itil_type) {
            $schemas[$itil_type] = $base_schema;
            $schemas[$itil_type]['x-version-introduced'] = '2.0';
            if ($itil_type === Ticket::class) {
                $schemas[$itil_type]['properties']['type'] = [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'enum' => [Ticket::INCIDENT_TYPE, Ticket::DEMAND_TYPE]
                ];
                $schemas[$itil_type]['properties']['external_id'] = [
                    'x-field' => 'externalid',
                    'type' => Doc\Schema::TYPE_STRING,
                ];
                $schemas[$itil_type]['properties']['request_type'] = self::getDropdownTypeSchema(class: \RequestType::class, full_schema: 'RequestType');
            }
            $schemas[$itil_type]['x-itemtype'] = $itil_type;
            $schemas[$itil_type]['properties']['status'] = [
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'x-field' => 'status',
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64
                    ],
                    'name' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'x-mapped-from' => 'status.id',
                        // The x-mapper property indicates this property is calculated.
                        // The mapper callable gets the value of the x-mapped-from field (id in this case) and returns the name.
                        'x-mapper' => static function ($v) use ($itil_type) {
                            return $itil_type::getStatus($v);
                        }
                    ],
                ]
            ];
            $schemas[$itil_type]['properties']['entity'] = self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity');
            // Add completename field
            $schemas[$itil_type]['properties']['entity']['properties']['completename'] = ['type' => Doc\Schema::TYPE_STRING];

            $schemas[$itil_type]['properties']['team'] = [
                'type' => Doc\Schema::TYPE_ARRAY,
                'x-full-schema' => 'TeamMember',
                'items' => [
                    'x-mapped-from' => 'id',
                    'x-mapper' => function ($v) use ($itil_type) {
                        $item = new $itil_type();
                        if ($item->getFromDB($v)) {
                            return self::getCleanTeam($item);
                        }
                        return [];
                    },
                    'ref' => 'TeamMember'
                ]
            ];
        }

        $base_task_schema = [
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-rights-conditions' => [ // Object-level extra permissions
                'read' => static function () {
                    if (!\Session::haveRight(\CommonITILTask::$rightname, \CommonITILTask::SEEPRIVATE)) {
                        return [
                            'WHERE' => [
                                'OR' => [
                                    'is_private' => 0,
                                    'users_id' => \Session::getLoginUserID()
                                ]
                            ]
                        ];
                    }
                    return true; // Allow reading by default. No extra SQL conditions needed.
                }
            ],
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'x-readonly' => true,
                ],
                'content' => ['type' => Doc\Schema::TYPE_STRING],
                'is_private' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'user' => self::getDropdownTypeSchema(class: User::class, full_schema: 'User'),
                'duration' => ['type' => Doc\Schema::TYPE_INTEGER, 'x-field' => 'actiontime'],
                'state' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'enum' => [
                        \Planning::INFO,
                        \Planning::TODO,
                        \Planning::DONE,
                    ]
                ],
                'category' => self::getDropdownTypeSchema(class: \TaskCategory::class, full_schema: 'TaskCategory'),
            ]
        ];

        $schemas['TicketTask'] = $base_task_schema;
        $schemas['TicketTask']['x-version-introduced'] = '2.0';
        $schemas['TicketTask']['x-itemtype'] = \TicketTask::class;
        $schemas['TicketTask']['properties'][Ticket::getForeignKeyField()] = ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64];

        $schemas['ChangeTask'] = $base_task_schema;
        $schemas['ChangeTask']['x-version-introduced'] = '2.0';
        $schemas['ChangeTask']['x-itemtype'] = \ChangeTask::class;
        $schemas['ChangeTask']['properties'][Change::getForeignKeyField()] = ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64];

        $schemas['ProblemTask'] = $base_task_schema;
        $schemas['ProblemTask']['x-version-introduced'] = '2.0';
        $schemas['ProblemTask']['x-itemtype'] = \ProblemTask::class;
        $schemas['ProblemTask']['properties'][Problem::getForeignKeyField()] = ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64];

        $schemas['TaskCategory'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => \TaskCategory::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'x-readonly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'is_active' => ['type' => Doc\Schema::TYPE_BOOLEAN],
            ]
        ];

        $schemas['RequestType'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => \RequestType::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'x-readonly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'is_helpdesk_default' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'is_followup_default' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'is_mail_default' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'is_mailfollowup_default' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'is_active' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'is_visible_ticket' => [
                    'type' => Doc\Schema::TYPE_BOOLEAN,
                    'x-field' => 'is_ticketheader',
                ],
                'is_visible_followup' => [
                    'type' => Doc\Schema::TYPE_BOOLEAN,
                    'x-field' => 'is_itilfollowup'
                ],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ]
        ];

        $schemas['Followup'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => \ITILFollowup::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-rights-conditions' => [ // Object-level extra permissions
                'read' => static function () {
                    if (!\Session::haveRight(\ITILFollowup::$rightname, \ITILFollowup::SEEPRIVATE)) {
                        return [
                            'WHERE' => [
                                'OR' => [
                                    'is_private' => 0,
                                    'users_id' => \Session::getLoginUserID()
                                ]
                            ]
                        ];
                    }
                    return true; // Allow reading by default. No extra SQL conditions needed.
                }
            ],
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'x-readonly' => true,
                ],
                'itemtype' => ['type' => Doc\Schema::TYPE_STRING,],
                'items_id' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64],
                'content' => ['type' => Doc\Schema::TYPE_STRING],
                'is_private' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'request_type' => self::getDropdownTypeSchema(\RequestType::class, full_schema: 'RequestType'),
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ]
        ];

        $schemas['Solution'] = [
            'x-version-introduced' => '2.0',
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => \ITILSolution::class,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'x-readonly' => true,
                ],
                'itemtype' => ['type' => Doc\Schema::TYPE_STRING],
                'items_id' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64],
                'content' => ['type' => Doc\Schema::TYPE_STRING],
            ]
        ];

        $base_validation_schema = [
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'x-readonly' => true,
                ],
                'requester' => self::getDropdownTypeSchema(class: User::class, full_schema: 'User'),
                'approver' => self::getDropdownTypeSchema(class: User::class, field: 'users_id_validate', full_schema: 'User'),
                'requested_approver_type' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'x-field' => 'itemtype_target',
                    'enum' => [User::getType(), Group::getType()]
                ],
                'requested_approver_id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'x-field' => 'items_id_target',
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                ],
                'submission_comment' => ['type' => Doc\Schema::TYPE_STRING, 'x-field' => 'comment_submission'],
                'approval_comment' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'x-field' => 'comment_validation',
                ],
                'status' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'enum' => [
                        \CommonITILValidation::NONE,
                        \CommonITILValidation::WAITING,
                        \CommonITILValidation::ACCEPTED,
                        \CommonITILValidation::REFUSED,
                    ]
                ],
                'submission_date' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'approval_date' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME, 'x-field' => 'validation_date'],
            ]
        ];

        $schemas['TicketValidation'] = $base_validation_schema;
        $schemas['TicketValidation']['x-version-introduced'] = '2.0';
        $schemas['TicketValidation']['x-itemtype'] = \TicketValidation::class;
        $schemas['TicketValidation']['properties'][Ticket::getForeignKeyField()] = ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64];

        $schemas['ChangeValidation'] = $base_validation_schema;
        $schemas['ChangeValidation']['x-version-introduced'] = '2.0';
        $schemas['ChangeValidation']['x-itemtype'] = \ChangeValidation::class;
        $schemas['ChangeValidation']['properties'][Change::getForeignKeyField()] = ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64];

        $schemas['TeamMember'] = [
            'x-version-introduced' => '2.0',
            'type' => Doc\Schema::TYPE_OBJECT,
            'description' => 'The valid types and roles depend on the type of the item they are being added to',
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'x-readonly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'type' => ['type' => Doc\Schema::TYPE_STRING],
                'role' => ['type' => Doc\Schema::TYPE_STRING],
            ]
        ];

        $schemas['RecurringTicket'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => \TicketRecurrent::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'description' => 'Recurring ticket',
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'x-readonly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'is_active' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'template' => self::getDropdownTypeSchema(class: TicketTemplate::class, full_schema: 'TicketTemplate'),
                'date_begin' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_DATE_TIME,
                    'x-field' => 'begin_date',
                ],
                'date_end' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_DATE_TIME,
                    'x-field' => 'end_date',
                ],
                'periodicity' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                'create_before' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                'date_next_creation' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_DATE_TIME,
                    'x-field' => 'next_creation_date',
                ],
                'calendar' => self::getDropdownTypeSchema(class: Calendar::class, full_schema: 'Calendar'),
                'ticket_per_item' => ['type' => Doc\Schema::TYPE_BOOLEAN],
            ]
        ];

        $schemas['RecurringChange'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => \RecurrentChange::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'description' => 'Recurring change',
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'x-readonly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'is_active' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'template' => self::getDropdownTypeSchema(class: ChangeTemplate::class, full_schema: 'ChangeTemplate'),
                'date_begin' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_DATE_TIME,
                    'x-field' => 'begin_date',
                ],
                'date_end' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_DATE_TIME,
                    'x-field' => 'end_date',
                ],
                'periodicity' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                'create_before' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                'date_next_creation' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_DATE_TIME,
                    'x-field' => 'next_creation_date',
                ],
                'calendar' => self::getDropdownTypeSchema(class: Calendar::class, full_schema: 'Calendar'),
            ]
        ];

        $schemas['ExternalEventTemplate'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => \PlanningExternalEventTemplate::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'description' => \PlanningExternalEventTemplate::getTypeName(1),
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'x-readonly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'text' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'duration' => ['type' => Doc\Schema::TYPE_INTEGER],
                'before_time' => ['type' => Doc\Schema::TYPE_INTEGER],
                'rrule' => ['type' => Doc\Schema::TYPE_STRING],
                'category' => self::getDropdownTypeSchema(class: PlanningEventCategory::class, full_schema: 'EventCategory'),
                'state' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'enum' => [\Planning::INFO, \Planning::TODO, \Planning::DONE]
                ],
                'is_background' => ['x-field' => 'background', 'type' => Doc\Schema::TYPE_BOOLEAN],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ]
        ];

        $schemas['EventCategory'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => \PlanningEventCategory::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'description' => \PlanningEventCategory::getTypeName(1),
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'x-readonly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'color' => ['type' => Doc\Schema::TYPE_STRING],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ]
        ];

        $schemas['ExternalEvent'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => \PlanningExternalEvent::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'description' => \PlanningExternalEvent::getTypeName(1),
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'x-readonly' => true,
                ],
                'uuid' => ['type' => Doc\Schema::TYPE_STRING, 'pattern' => Doc\Schema::PATTERN_UUIDV4],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'text' => ['type' => Doc\Schema::TYPE_STRING],
                'template' => self::getDropdownTypeSchema(class: PlanningExternalEventTemplate::class, full_schema: 'ExternalEventTemplate'),
                'category' => self::getDropdownTypeSchema(class: PlanningEventCategory::class, full_schema: 'EventCategory'),
                'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'user' => self::getDropdownTypeSchema(class: User::class, full_schema: 'User'),
                'group' => self::getDropdownTypeSchema(class: Group::class, full_schema: 'Group'),
                'date' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_begin' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_DATE_TIME,
                    'x-field' => 'begin',
                ],
                'date_end' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_DATE_TIME,
                    'x-field' => 'end',
                ],
                'rrule' => ['type' => Doc\Schema::TYPE_STRING],
                'state' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64],
                'is_background' => [
                    'type' => Doc\Schema::TYPE_BOOLEAN,
                    'x-field' => 'background',
                ],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ]
        ];

        return $schemas;
    }

    /**
     * @param class-string<\CommonDBTM> $subtype
     * @return string
     */
    public static function getFriendlyNameForSubtype(string $subtype): string
    {
        return match (true) {
            is_subclass_of($subtype, \CommonITILTask::class) => 'Task',
            $subtype === \ITILFollowup::class => 'Followup',
            $subtype === \Document_Item::class => 'Document',
            $subtype === \ITILSolution::class => 'Solution',
            is_subclass_of($subtype, \CommonITILValidation::class) => 'Validation',
            default => $subtype,
        };
    }

    /**
     * @param string $subitem_schema_name
     * @param class-string<CommonITILObject> $itemtype
     * @return array
     */
    private function getSubitemSchemaFor(string $subitem_schema_name, string $itemtype, string $api_version): array
    {
        $subitem_schema = $this->getKnownSchema($subitem_schema_name, $api_version);
        $subitem_schema['x-itemtype'] = match ($subitem_schema_name) {
            'Task' => $itemtype::getTaskClass(),
            'Followup' => 'ITILFollowup',
            'Document' => 'Document_Item',
            'Solution' => 'ITILSolution',
            'Validation' => $itemtype::getValidationClassInstance()::class,
            'Log' => 'Log',
            default => $subitem_schema_name,
        };
        return $subitem_schema;
    }

    #[Route(path: '/{itemtype}', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'List or search Tickets, Changes or Problems',
        parameters: [self::PARAMETER_RSQL_FILTER, self::PARAMETER_START, self::PARAMETER_LIMIT, self::PARAMETER_SORT],
        responses: [
            ['schema' => '{itemtype}[]']
        ]
    )]
    public function search(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return Search::searchBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Get a Ticket, Change or Problem by ID',
        parameters: [
            [
                'name' => 'id',
                'description' => 'The ID of the Ticket, Change, or Problem',
                'location' => Doc\Parameter::LOCATION_PATH,
                'schema' => ['type' => Doc\Schema::TYPE_INTEGER]
            ]
        ],
        responses: [
            ['schema' => '{itemtype}']
        ]
    )]
    public function getItem(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return Search::getOneBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}', methods: ['POST'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Create a new Ticket, Change or Problem',
        parameters: [
            [
                'name' => '_',
                'location' => Doc\Parameter::LOCATION_BODY,
                'schema' => '{itemtype}',
            ]
        ]
    )]
    public function createItem(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return Search::createBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getParameters() + ['itemtype' => $itemtype], [self::class, 'getItem']);
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['PATCH'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Update a Ticket, Change or Problem by ID',
        parameters: [
            [
                'name' => 'id',
                'description' => 'The ID of the Ticket, Change, or Problem',
                'location' => Doc\Parameter::LOCATION_PATH,
                'schema' => ['type' => Doc\Schema::TYPE_INTEGER]
            ],
            [
                'name' => '_',
                'location' => Doc\Parameter::LOCATION_BODY,
                'schema' => '{itemtype}',
            ]
        ]
    )]
    public function updateItem(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return Search::updateBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['DELETE'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Delete a Ticket, Change or Problem by ID',
        parameters: [
            [
                'name' => 'id',
                'description' => 'The ID of the Ticket, Change, or Problem',
                'location' => Doc\Parameter::LOCATION_PATH,
                'schema' => ['type' => Doc\Schema::TYPE_INTEGER]
            ]
        ]
    )]
    public function deleteItem(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return Search::deleteBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    private function getRequiredTimelineItemFields(CommonITILObject $item, Request $request, string $subitem_type): array
    {
        $fields = [
            'itemtype' => $item::getType(),
            'items_id' => $item->getID(),
        ];
        if ($subitem_type === 'Task' || $subitem_type === 'Validation') {
            $fields = [
                $item::getForeignKeyField() => $item->getID(),
            ];
        }
        return $fields;
    }

    private function getTimelineItemFilters(CommonITILObject $item, Request $request, string $subitem_type): string
    {
        $request_filters = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filters = $request_filters;
        $required_fields = $this->getRequiredTimelineItemFields($item, $request, $subitem_type);
        foreach ($required_fields as $name => $value) {
            $filters .= ";{$name}=={$value}";
        }
        return $filters;
    }

    private function getKnownSubitemSchema(CommonITILObject $item, string $subitem_type, string $api_version): ?array
    {
        if ($subitem_type === 'Document') {
            $schema = (new ManagementController())->getKnownSchema('Document_Item', $api_version);
        } else if ($subitem_type === 'Task') {
            $schema = $this->getKnownSchema($item::getTaskClass(), $api_version);
        } else if ($subitem_type === 'Validation' && class_exists($item::getType() . 'Validation')) {
            $schema = $this->getKnownSchema($item::getType() . 'Validation', $api_version);
        } else {
            $schema = $this->getKnownSchema($subitem_type, $api_version);
        }
        return $schema;
    }

    /**
     * Get the timeline items for a given item
     * @param CommonITILObject $item The item to get the timeline items for
     * @param Request $request The original request
     * @param array $subitem_types The subitem types to include or all if empty
     * @return array|null Array of results. Null may be returned if a specific subitem was requested but not found.
     */
    private function getITILTimelineItems(CommonITILObject $item, Request $request, array $subitem_types = []): ?array
    {
        $subitem_types = empty($subitem_types) ? ['Followup', 'Task', 'Document', 'Solution', 'Validation'] : $subitem_types;
        $results = [];
        foreach ($subitem_types as $subitem_type) {
            $filters = $this->getTimelineItemFilters($item, $request, $subitem_type);
            $schema = $this->getKnownSubitemSchema($item, $subitem_type, $this->getAPIVersion($request));
            if ($schema === null) {
                continue;
            }

            /** @var class-string<CommonDBTM> $schema_itemtype */
            $schema_itemtype = $schema['x-itemtype'];
            if (!$schema_itemtype::canView()) {
                continue;
            }
            if (array_key_exists('is_private', $schema['properties']) && !\Session::haveRight($schema_itemtype::$rightname, $schema_itemtype::SEEPRIVATE)) {
                $filters .= ';is_private==0';
            }

            $subitem_results = Search::searchBySchema($schema, [
                'filter' => $filters,
                'limit' => 1000
            ]);
            $decoded_results = json_decode($subitem_results->getBody(), true);
            foreach ($decoded_results as $decoded_result) {
                $results[] = [
                    'type' => $subitem_type,
                    'item' => $decoded_result
                ];
            }
        }
        $single_result = $request->hasParameter('filter') && str_contains($request->getParameter('filter'), 'id==');
        if ($single_result && count($results) > 0) {
            $results = $results[0]['item'];
        } else if ($single_result && count($results) === 0) {
            $results = null;
        }
        return $results;
    }

    #[Route(path: '/{itemtype}/{id}/Timeline', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Get all timeline items for a Ticket, Change or Problem by ID',
        parameters: [
            [
                'name' => 'id',
                'description' => 'The ID of the Ticket, Change, or Problem',
                'location' => Doc\Parameter::LOCATION_PATH,
                'schema' => ['type' => Doc\Schema::TYPE_INTEGER]
            ]
        ]
    )]
    public function getTimeline(Request $request): Response
    {
        /** @var CommonITILObject $item */
        $item = $request->getParameter('_item');
        $timeline = $this->getITILTimelineItems($item, $request);
        return new JSONResponse($timeline);
    }

    #[Route(path: '/{itemtype}/{id}/Timeline/{subitem_type}', methods: ['GET'], requirements: [
        'subitem_type' => 'Followup|Document|Solution|Validation'
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Get all timeline items of a specific type for a Ticket, Change or Problem by ID',
        parameters: [
            [
                'name' => 'id',
                'description' => 'The ID of the Ticket, Change, or Problem',
                'location' => Doc\Parameter::LOCATION_PATH,
                'schema' => ['type' => Doc\Schema::TYPE_INTEGER]
            ]
        ],
        responses: [
            ['schema' => '{subitem_type}[]']
        ]
    )]
    public function getTimelineItems(Request $request): Response
    {
        /** @var CommonITILObject $item */
        $item = $request->getParameter('_item');
        $friendly_subitem_type = $request->getAttribute('subitem_type');

        $timeline = $this->getITILTimelineItems($item, $request, [$friendly_subitem_type]);
        $single_result = $request->hasParameter('filter') && str_contains($request->getParameter('filter'), 'id==');
        if ($single_result && $timeline === null) {
            return self::getNotFoundErrorResponse();
        }
        return new JSONResponse($timeline);
    }

    #[Route(path: '/{itemtype}/{id}/Timeline/Task', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Get all tasks for a Ticket, Change or Problem by ID',
        parameters: [
            [
                'name' => 'id',
                'description' => 'The ID of the Ticket, Change, or Problem',
                'location' => Doc\Parameter::LOCATION_PATH,
                'schema' => ['type' => Doc\Schema::TYPE_INTEGER]
            ]
        ],
        responses: [
            ['schema' => '{itemtype}Task[]']
        ]
    )]
    public function getTimelineTasks(Request $request): Response
    {
        /** @var CommonITILObject $item */
        $item = $request->getParameter('_item');

        $timeline = $this->getITILTimelineItems($item, $request, ['Task']);
        $single_result = $request->hasParameter('filter') && str_contains($request->getParameter('filter'), 'id==');
        if ($single_result && $timeline === null) {
            return self::getNotFoundErrorResponse();
        }
        return new JSONResponse($timeline);
    }

    public function getSubitemType(CommonITILObject $parent_item, string $friendly_name): string
    {
        return match ($friendly_name) {
            'Followup' => 'ITILFollowup',
            'Task' => $parent_item::getTaskClass(),
            'Document' => 'Document_Item',
            'Solution' => 'ITILSolution',
            'Validation' => 'ITILValidation',
            'Log'    => 'Log',
            default => $friendly_name,
        };
    }

    public function getSubitemFriendlyType(CommonITILObject $parent_item, string $itemtype): string
    {
        return match ($itemtype) {
            'ITILFollowup' => 'Followup',
            $parent_item::getTaskClass() => 'Task',
            'Document_Item' => 'Document',
            'ITILSolution' => 'Solution',
            'ITILValidation' => 'Validation',
            'Log'    => 'Log',
            default => $itemtype,
        };
    }

    /**
     * @param CommonITILObject $parent_item
     * @param string $subitem_type
     * @return array<string, int>|array{itemtype: string, items_id: int}
     */
    private function getSubitemLinkFields(CommonITILObject $parent_item, string $subitem_type): array
    {
        if (is_subclass_of($subitem_type, \CommonDBChild::class)) {
            return [
                $subitem_type::$itemtype => $parent_item::getType(),
                $subitem_type::$items_id => (int)$parent_item->fields['id']
            ];
        }

        return [
            $parent_item::getForeignKeyField() => (int)$parent_item->fields['id']
        ];
    }

    #[Route(path: '/{itemtype}/{id}/Timeline/{subitem_type}/{subitem_id}', methods: ['GET'], requirements: [
        'subitem_type' => 'Followup|Document|Solution|Validation',
        'subitem_id' => '\d+'
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Get a specific timeline item by type and ID for a Ticket, Change or Problem by ID',
        parameters: [
            [
                'name' => 'id',
                'description' => 'The ID of the Ticket, Change, or Problem',
                'location' => Doc\Parameter::LOCATION_PATH,
                'schema' => ['type' => Doc\Schema::TYPE_INTEGER]
            ]
        ],
        responses: [
            ['schema' => '{subitem_type}']
        ]
    )]
    public function getTimelineItem(Request $request): Response
    {
        $filters = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filters .= ';id==' . $request->getAttribute('subitem_id');
        // Reuse existing logic from the getTimelineItems route
        $request->setParameter('filter', $filters);
        return $this->getTimelineItems($request);
    }

    #[Route(path: '/{itemtype}/{id}/Timeline/Task/{subitem_id}', methods: ['GET'], requirements: [
        'subitem_id' => '\d+'
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Get a task for a Ticket, Change or Problem by ID',
        parameters: [
            [
                'name' => 'id',
                'description' => 'The ID of the Ticket, Change, or Problem',
                'location' => Doc\Parameter::LOCATION_PATH,
                'schema' => ['type' => Doc\Schema::TYPE_INTEGER]
            ]
        ],
        responses: [
            ['schema' => '{itemtype}Task']
        ]
    )]
    public function getTimelineTask(Request $request): Response
    {
        $filters = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filters .= ';id==' . $request->getAttribute('subitem_id');
        // Reuse existing logic from the getTimelineItems route
        $request->setParameter('filter', $filters);
        $request->setAttribute('subitem_type', 'Task');
        return $this->getTimelineItems($request);
    }

    #[Route(path: '/{itemtype}/{id}/Timeline/{subitem_type}', methods: ['POST'], requirements: [
        'subitem_type' => 'Followup|Document|Solution|Validation'
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Create a timeline item for a Ticket, Change or Problem by ID',
        parameters: [
            [
                'name' => 'id',
                'description' => 'The ID of the Ticket, Change, or Problem',
                'location' => Doc\Parameter::LOCATION_PATH,
                'schema' => ['type' => Doc\Schema::TYPE_INTEGER]
            ],
            [
                'name' => '_',
                'location' => Doc\Parameter::LOCATION_BODY,
                'schema' => '{subitem_type}',
            ]
        ],
    )]
    public function createTimelineItem(Request $request): Response
    {
        /** @var CommonITILObject $item */
        $item = $request->getParameter('_item');
        $subitem_type = $request->getAttribute('subitem_type');

        $parameters = $request->getParameters();
        $parameters = array_merge($parameters, $this->getRequiredTimelineItemFields($item, $request, $subitem_type));
        $schema = $this->getKnownSubitemSchema($item, $subitem_type, $this->getAPIVersion($request));
        return Search::createBySchema($schema, $parameters, [self::class, 'getTimelineItem'], [
            'mapped' => [
                'itemtype' => $item::getType(),
                'subitem_type' => $subitem_type,
                'id' => $item->getID()
            ],
            'id' => 'subitem_id'
        ]);
    }

    #[Route(path: '/{itemtype}/{id}/Timeline/Task', methods: ['POST'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Create a task for a Ticket, Change or Problem by ID',
        parameters: [
            [
                'name' => 'id',
                'description' => 'The ID of the Ticket, Change, or Problem',
                'location' => Doc\Parameter::LOCATION_PATH,
                'schema' => ['type' => Doc\Schema::TYPE_INTEGER]
            ],
            [
                'name' => '_',
                'location' => Doc\Parameter::LOCATION_BODY,
                'schema' => '{itemtype}Task',
            ]
        ],
    )]
    public function createTimelineTask(Request $request): Response
    {
        /** @var CommonITILObject $item */
        $item = $request->getParameter('_item');

        $parameters = $request->getParameters();
        $parameters = array_merge($parameters, $this->getRequiredTimelineItemFields($item, $request, 'Task'));
        $schema = $this->getKnownSubitemSchema($item, 'Task', $this->getAPIVersion($request));
        return Search::createBySchema($schema, $parameters, [self::class, 'getTimelineTask'], [
            'mapped' => [
                'itemtype' => $item::getType(),
                'subitem_type' => 'Task',
                'id' => $item->getID()
            ],
            'id' => 'subitem_id'
        ]);
    }

    #[Route(path: '/{itemtype}/{id}/Timeline/{subitem_type}/{subitem_id}', methods: ['PATCH'], requirements: [
        'subitem_type' => 'Followup|Document|Solution|Validation',
        'subitem_id' => '\d+'
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Update a timeline item by ID for a Ticket, Change or Problem by ID',
        parameters: [
            [
                'name' => 'id',
                'description' => 'The ID of the Ticket, Change, or Problem',
                'location' => Doc\Parameter::LOCATION_PATH,
                'schema' => ['type' => Doc\Schema::TYPE_INTEGER]
            ],
            [
                'name' => '_',
                'location' => Doc\Parameter::LOCATION_BODY,
                'schema' => '{subitem_type}',
            ]
        ]
    )]
    public function updateTimelineItem(Request $request): Response
    {
        /** @var CommonITILObject $item */
        $item = $request->getParameter('_item');
        $subitem_type = $request->getAttribute('subitem_type');

        $parameters = $request->getParameters();
        $required_fields = $this->getRequiredTimelineItemFields($item, $request, $subitem_type);
        // Required fields are used to link to the parent item. We cannot let them be changed
        foreach ($required_fields as $field => $value) {
            unset($parameters[$field]);
        }
        $attributes = $request->getAttributes();
        $attributes['id'] = $request->getAttribute('subitem_id');
        return Search::updateBySchema($this->getKnownSubitemSchema($item, $subitem_type, $this->getAPIVersion($request)), $attributes, $parameters);
    }

    #[Route(path: '/{itemtype}/{id}/Timeline/Task/{subitem_id}', methods: ['PATCH'], requirements: [
        'subitem_id' => '\d+'
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Update a task for a Ticket, Change or Problem by ID',
        parameters: [
            [
                'name' => 'id',
                'description' => 'The ID of the Ticket, Change, or Problem',
                'location' => Doc\Parameter::LOCATION_PATH,
                'schema' => ['type' => Doc\Schema::TYPE_INTEGER]
            ],
            [
                'name' => '_',
                'location' => Doc\Parameter::LOCATION_BODY,
                'schema' => '{itemtype}Task',
            ]
        ]
    )]
    public function updateTimelineTask(Request $request): Response
    {
        /** @var CommonITILObject $item */
        $item = $request->getParameter('_item');

        $parameters = $request->getParameters();
        $required_fields = $this->getRequiredTimelineItemFields($item, $request, 'Task');
        // Required fields are used to link to the parent item. We cannot let them be changed
        foreach ($required_fields as $field => $value) {
            unset($parameters[$field]);
        }
        $attributes = $request->getAttributes();
        $attributes['id'] = $request->getAttribute('subitem_id');
        return Search::updateBySchema($this->getKnownSubitemSchema($item, 'Task', $this->getAPIVersion($request)), $attributes, $parameters);
    }

    #[Route(path: '/{itemtype}/{id}/Timeline/{subitem_type}/{subitem_id}', methods: ['DELETE'], requirements: [
        'subitem_type' => 'Followup|Document|Solution|Validation',
        'subitem_id' => '\d+'
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Delete a timeline item by ID for a Ticket, Change or Problem by ID',
        parameters: [
            [
                'name' => 'id',
                'description' => 'The ID of the Ticket, Change, or Problem',
                'location' => Doc\Parameter::LOCATION_PATH,
                'schema' => ['type' => Doc\Schema::TYPE_INTEGER]
            ]
        ]
    )]
    public function deleteTimelineItem(Request $request): Response
    {
        /** @var CommonITILObject $item */
        $item = $request->getParameter('_item');
        $subitem_type = $request->getAttribute('subitem_type');
        $attributes = $request->getAttributes();
        $attributes['id'] = $request->getAttribute('subitem_id');
        return Search::deleteBySchema($this->getKnownSubitemSchema($item, $subitem_type, $this->getAPIVersion($request)), $attributes, $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{id}/Timeline/Task/{subitem_id}', methods: ['DELETE'], requirements: [
        'subitem_id' => '\d+'
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Delete a task for a Ticket, Change or Problem by ID',
        parameters: [
            [
                'name' => 'id',
                'description' => 'The ID of the Ticket, Change, or Problem',
                'location' => Doc\Parameter::LOCATION_PATH,
                'schema' => ['type' => Doc\Schema::TYPE_INTEGER]
            ]
        ]
    )]
    public function deleteTimelineTask(Request $request): Response
    {
        /** @var CommonITILObject $item */
        $item = $request->getParameter('_item');
        $attributes = $request->getAttributes();
        $attributes['id'] = $request->getAttribute('subitem_id');
        return Search::deleteBySchema($this->getKnownSubitemSchema($item, 'Task', $this->getAPIVersion($request)), $attributes, $request->getParameters());
    }

    /**
     * @param CommonITILObject $item
     * @return array{role: string|int, name?: string, realname?: string, firstname?: string, display_name?: string, href: string}[]
     */
    private static function getCleanTeam(CommonITILObject $item): array
    {
        $team = $item->getTeam();
        $preserved_element_keys = ['role', 'name', 'realname', 'firstname', 'display_name'];
        foreach ($team as &$member) {
            /** @var class-string<CommonDBTM> $member_itemtype */
            $member_itemtype = $member['itemtype'];
            $member_items_id = $member['items_id'];
            // Only keep the allowed properties
            foreach ($member as $k => $v) {
                if (!in_array($k, $preserved_element_keys, true)) {
                    unset($member[$k]);
                }
            }
            // Add a link to the full resource represented by the team member (User, Group, etc)
            $member['href'] = $member_itemtype::getFormURLWithID($member_items_id);
            // Replace role with non-localized textual representation
            try {
                $member['role'] = self::getRoleName($member['role']);
            } catch (\InvalidArgumentException) {
                // Leave invalid role as-is
            }
        }
        return $team;
    }

    /**
     * Get the numeric role type given a textual representation or a numeric representation.
     *
     * Only valid roles are returned. If a role is given that is not valid, an exception is thrown instead of returning the
     * invalid role.
     * @param string|int $role The numeric or textual representation of the role
     * @return int The valid numeric role
     * @throws \InvalidArgumentException If the textual or numeric role is not valid
     */
    private static function getRoleID(string|int $role): int
    {
        return match ($role) {
            Team::ROLE_REQUESTER, "requester" => Team::ROLE_REQUESTER,
            Team::ROLE_ASSIGNED, "assigned" => Team::ROLE_ASSIGNED,
            Team::ROLE_OBSERVER, "observer", "watcher" => Team::ROLE_OBSERVER,
            default => throw new \InvalidArgumentException("Invalid role: $role"),
        };
    }

    /**
     * Get the textual role type given a numeric representation or a textual representation.
     *
     * Only valid roles are returned. If a role is given that is not valid, an exception is thrown instead of returning the
     * invalid role.
     * @param string|int $role The numeric or textual representation of the role
     * @return string The valid role in textual form
     * @throws \InvalidArgumentException If the textual or numeric role is not valid
     */
    private static function getRoleName(string|int $role): string
    {
        return match ($role) {
            Team::ROLE_REQUESTER, "requester" => "requester",
            Team::ROLE_ASSIGNED, "assigned" => "assigned",
            Team::ROLE_OBSERVER, "observer", "watcher" => "observer",
            default => throw new \InvalidArgumentException("Invalid role: $role"),
        };
    }

    #[Route(path: '/{itemtype}/{id}/TeamMember', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Get the team members for a specific Ticket, Change or Problem by ID',
        responses: [
            [
                'description' => 'The team members',
                'schema' => 'TeamMember[]'
            ]
        ]
    )]
    public function getTeamMembers(Request $request): Response
    {
        /** @var CommonITILObject $item */
        $item = $request->getParameter('_item');

        $team = self::getCleanTeam($item);
        return new JSONResponse($team);
    }

    #[Route(path: '/{itemtype}/{id}/TeamMember/{role}', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Get the team members by role for specific Ticket, Change, Problem by ID',
        parameters: [
            [
                'name' => 'role',
                'description' => 'The role',
                'location' => Doc\Parameter::LOCATION_PATH,
                'schema' => ['type' => Doc\Schema::TYPE_STRING]
            ]
        ],
        responses: [
            [
                'description' => 'The team member',
                'schema' => 'TeamMember[]'
            ]
        ]
    )]
    public function getTeamMembersByRole(Request $request): Response
    {
        /** @var CommonITILObject $item */
        $item = $request->getParameter('_item');

        // TODO Handle textual representations of roles
        $role_id = $request->getAttribute('role');
        if ($role_id === null) {
            self::getInvalidParametersErrorResponse();
        }

        $team = self::getCleanTeam($item);
        $team = array_filter($team, static function ($v) use ($role_id) {
            return $v['role'] === $role_id;
        }, ARRAY_FILTER_USE_BOTH);
        return new JSONResponse($team);
    }

    #[Route(path: '/{itemtype}/{id}/TeamMember', methods: ['POST'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Add a team member to a specific Ticket, Change or Problem',
        parameters: [
            [
                'name' => 'type',
                'description' => 'The type of team member. The applicable types of members will depend on the role.',
                'location' => Doc\Parameter::LOCATION_BODY
            ],
            [
                'name' => 'id',
                'description' => 'The ID of the team member',
                'location' => Doc\Parameter::LOCATION_BODY
            ],
            [
                'name' => 'role',
                'description' => 'The role of the team member',
                'location' => Doc\Parameter::LOCATION_BODY
            ]
        ]
    )]
    public function addTeamMember(Request $request): Response
    {
        /** @var CommonITILObject $item */
        $item = $request->getParameter('_item');

        $member_itemtype = $request->getParameter('type');
        $member_items_id = $request->getParameter('id');
        // TODO Handle textual representations of roles
        $role_id = $request->getParameter('role');

        $result = $item->addTeamMember($member_itemtype, $member_items_id, [
            'role'  => $role_id
        ]);
        if ($result) {
            return new JSONResponse(null, 201);
        }
        return self::getInvalidParametersErrorResponse();
    }

    #[Route(path: '/{itemtype}/{id}/TeamMember', methods: ['DELETE'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Remove a team member from a specific Ticket, Change or Problem',
        parameters: [
            [
                'name' => 'type',
                'description' => 'The type of team member. The applicable types of members will depend on the role.',
                'location' => Doc\Parameter::LOCATION_BODY
            ],
            [
                'name' => 'id',
                'description' => 'The ID of the team member',
                'location' => Doc\Parameter::LOCATION_BODY
            ],
            [
                'name' => 'role',
                'description' => 'The role of the team member',
                'location' => Doc\Parameter::LOCATION_BODY
            ]
        ]
    )]
    public function removeTeamMember(Request $request): Response
    {
        /** @var CommonITILObject $item */
        $item = $request->getParameter('_item');

        $member_itemtype = $request->getParameter('type');
        $member_items_id = $request->getParameter('id');
        // TODO Handle textual representations of roles
        $role_id = self::getRoleID($request->getParameter('role'));

        $result = $item->deleteTeamMember($member_itemtype, $member_items_id, [
            'role'  => $role_id
        ]);
        if ($result) {
            return new JSONResponse(null, 200);
        }
        return self::getInvalidParametersErrorResponse();
    }

    #[Route(path: '/RecurringTicket', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'List or search recurring tickets',
        parameters: [self::PARAMETER_RSQL_FILTER, self::PARAMETER_START, self::PARAMETER_LIMIT, self::PARAMETER_SORT],
        responses: [
            ['schema' => 'RecurringTicket[]']
        ]
    )]
    public function searchRecurringTickets(Request $request): Response
    {
        return Search::searchBySchema($this->getKnownSchema('RecurringTicket', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/RecurringTicket/{id}', methods: ['GET'], requirements: [
        'id' => '\d+'
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Get a recurring ticket by ID',
        responses: [
            ['schema' => 'RecurringTicket']
        ]
    )]
    public function getRecurringTicket(Request $request): Response
    {
        return Search::getOneBySchema($this->getKnownSchema('RecurringTicket', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/RecurringTicket', methods: ['POST'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Create a recurring ticket',
        parameters: [
            [
                'name' => '_',
                'location' => Doc\Parameter::LOCATION_BODY,
                'schema' => 'RecurringTicket',
            ]
        ]
    )]
    public function createRecurringTicket(Request $request): Response
    {
        return Search::createBySchema($this->getKnownSchema('RecurringTicket', $this->getAPIVersion($request)), $request->getParameters(), [self::class, 'getRecurringTicket']);
    }

    #[Route(path: '/RecurringTicket/{id}', methods: ['PATCH'], requirements: [
        'id' => '\d+'
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Update a recurring ticket by ID',
        parameters: [
            [
                'name' => '_',
                'location' => Doc\Parameter::LOCATION_BODY,
                'schema' => 'RecurringTicket',
            ]
        ]
    )]
    public function updateRecurringTicket(Request $request): Response
    {
        return Search::updateBySchema($this->getKnownSchema('RecurringTicket', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/RecurringTicket/{id}', methods: ['DELETE'], requirements: [
        'id' => '\d+'
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Delete a recurring ticket by ID',
    )]
    public function deleteRecurringTicket(Request $request): Response
    {
        return Search::deleteBySchema($this->getKnownSchema('RecurringTicket', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/RecurringChange', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'List or search recurring changes',
        parameters: [self::PARAMETER_RSQL_FILTER, self::PARAMETER_START, self::PARAMETER_LIMIT, self::PARAMETER_SORT],
        responses: [
            ['schema' => 'RecurringChange[]']
        ]
    )]
    public function searchRecurringChanges(Request $request): Response
    {
        return Search::searchBySchema($this->getKnownSchema('RecurringChange', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/RecurringChange/{id}', methods: ['GET'], requirements: [
        'id' => '\d+'
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Get a recurring change by ID',
        responses: [
            ['schema' => 'RecurringChange']
        ]
    )]
    public function getRecurringChange(Request $request): Response
    {
        return Search::getOneBySchema($this->getKnownSchema('RecurringChange', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/RecurringChange', methods: ['POST'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Create a recurring change',
        parameters: [
            [
                'name' => '_',
                'location' => Doc\Parameter::LOCATION_BODY,
                'schema' => 'RecurringChange',
            ]
        ]
    )]
    public function createRecurringChange(Request $request): Response
    {
        return Search::createBySchema($this->getKnownSchema('RecurringChange', $this->getAPIVersion($request)), $request->getParameters(), [self::class, 'getRecurringChange']);
    }

    #[Route(path: '/RecurringChange/{id}', methods: ['PATCH'], requirements: [
        'id' => '\d+'
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Update a recurring change by ID',
        parameters: [
            [
                'name' => '_',
                'location' => Doc\Parameter::LOCATION_BODY,
                'schema' => 'RecurringChange',
            ]
        ]
    )]
    public function updateRecurringChange(Request $request): Response
    {
        return Search::updateBySchema($this->getKnownSchema('RecurringChange', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/RecurringChange/{id}', methods: ['DELETE'], requirements: [
        'id' => '\d+'
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Delete a recurring change by ID',
    )]
    public function deleteRecurringChange(Request $request): Response
    {
        return Search::deleteBySchema($this->getKnownSchema('RecurringChange', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/ExternalEvent', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'List or search external events',
        parameters: [self::PARAMETER_RSQL_FILTER, self::PARAMETER_START, self::PARAMETER_LIMIT, self::PARAMETER_SORT],
        responses: [
            ['schema' => 'ExternalEvent[]']
        ]
    )]
    public function searchExternalEvent(Request $request): Response
    {
        return Search::searchBySchema($this->getKnownSchema('ExternalEvent', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/ExternalEvent/{id}', methods: ['GET'], requirements: [
        'id' => '\d+'
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Get an external event by ID',
        responses: [
            ['schema' => 'ExternalEvent']
        ]
    )]
    public function getExternalEvent(Request $request): Response
    {
        return Search::getOneBySchema($this->getKnownSchema('ExternalEvent', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/ExternalEvent', methods: ['POST'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Create an external event',
        parameters: [
            [
                'name' => '_',
                'location' => Doc\Parameter::LOCATION_BODY,
                'schema' => 'ExternalEvent',
            ]
        ]
    )]
    public function createExternalEvent(Request $request): Response
    {
        return Search::createBySchema($this->getKnownSchema('ExternalEvent', $this->getAPIVersion($request)), $request->getParameters(), [self::class, 'getExternalEvent']);
    }

    #[Route(path: '/ExternalEvent/{id}', methods: ['PATCH'], requirements: [
        'id' => '\d+'
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Update an external event by ID',
        parameters: [
            [
                'name' => '_',
                'location' => Doc\Parameter::LOCATION_BODY,
                'schema' => 'ExternalEvent',
            ]
        ]
    )]
    public function updateExternalEvent(Request $request): Response
    {
        return Search::updateBySchema($this->getKnownSchema('ExternalEvent', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/ExternalEvent/{id}', methods: ['DELETE'], requirements: [
        'id' => '\d+'
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Delete an external event by ID',
    )]
    public function deleteExternalEvent(Request $request): Response
    {
        return Search::deleteBySchema($this->getKnownSchema('ExternalEvent', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }
}
