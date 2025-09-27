<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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
use ChangeTask;
use ChangeTemplate;
use ChangeValidation;
use CommonDBTM;
use CommonITILActor;
use CommonITILObject;
use CommonITILTask;
use CommonITILValidation;
use Document_Item;
use Entity;
use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\Middleware\ResultFormatterMiddleware;
use Glpi\Api\HL\ResourceAccessor;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\RouteVersion;
use Glpi\Http\JSONResponse;
use Glpi\Http\Request;
use Glpi\Http\Response;
use Glpi\Team\Team;
use Group;
use InvalidArgumentException;
use ITILCategory;
use ITILFollowup;
use ITILSolution;
use Location;
use Planning;
use PlanningEventCategory;
use PlanningExternalEvent;
use PlanningExternalEventTemplate;
use Problem;
use ProblemTask;
use RecurrentChange;
use RequestType;
use Session;
use TaskCategory;
use Ticket;
use TicketRecurrent;
use TicketTask;
use TicketTemplate;
use TicketValidation;
use User;

use function Safe\json_decode;

#[Route(path: '/Assistance', requirements: [
    'itemtype' => 'Ticket|Change|Problem',
    'id' => '\d+',
], tags: ['Assistance'])]
#[Doc\Route(
    parameters: [
        new Doc\Parameter(
            name: 'itemtype',
            schema: new Doc\Schema(type: Doc\Schema::TYPE_STRING, enum: ['Ticket', 'Change', 'Problem']),
            location: Doc\Parameter::LOCATION_PATH,
        ),
        new Doc\Parameter(
            name: 'id',
            schema: new Doc\Schema(type: Doc\Schema::TYPE_INTEGER),
            location: Doc\Parameter::LOCATION_PATH,
        ),
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
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'completename' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'parent' => self::getDropdownTypeSchema(class: ITILCategory::class, full_schema: 'ITILCategory'),
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $common_itiltemplate_properties = [
            'id' => [
                'type' => Doc\Schema::TYPE_INTEGER,
                'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                'readOnly' => true,
            ],
            'name' => ['type' => Doc\Schema::TYPE_STRING],
            'completename' => ['type' => Doc\Schema::TYPE_STRING],
            'comment' => ['type' => Doc\Schema::TYPE_STRING],
            'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
            'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN],
        ];
        $schemas['TicketTemplate'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => 'TicketTemplate',
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => $common_itiltemplate_properties,
        ];
        $schemas['ChangeTemplate'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => 'ChangeTemplate',
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => $common_itiltemplate_properties,
        ];
        $schemas['ProblemTemplate'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => 'ProblemTemplate',
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => $common_itiltemplate_properties,
        ];

        // U/I/P Values Description
        $uip_description = <<<EOT
            - 1: Very Low
            - 2: Low
            - 3: Medium
            - 4: High
            - 5: Very High
            EOT;

        $base_schema = [
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'content' => ['type' => Doc\Schema::TYPE_STRING],
                'is_deleted' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'category' => self::getDropdownTypeSchema(class: ITILCategory::class, full_schema: 'ITILCategory'),
                'location' => self::getDropdownTypeSchema(class: Location::class, full_schema: 'Location'),
                'urgency' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'enum' => [1, 2, 3, 4, 5],
                    'description' => $uip_description,
                ],
                'impact' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'enum' => [1, 2, 3, 4, 5],
                    'description' => $uip_description,
                ],
                'priority' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'enum' => [1, 2, 3, 4, 5],
                    'description' => $uip_description,
                ],
                'actiontime' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'readOnly' => true,
                ],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['TeamMember'] = [
            'x-version-introduced' => '2.0',
            'type' => Doc\Schema::TYPE_OBJECT,
            'description' => 'The valid types and roles depend on the type of the item they are being added to',
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'type' => ['type' => Doc\Schema::TYPE_STRING],
                'role' => ['type' => Doc\Schema::TYPE_STRING],
            ],
        ];

        $itil_types = [Ticket::class, Change::class, Problem::class];

        foreach ($itil_types as $itil_type) {
            $schemas[$itil_type] = $base_schema;
            $schemas[$itil_type]['x-version-introduced'] = '2.0';

            $schemas[$itil_type]['x-rights-conditions'] = [
                'read' => static function () use ($itil_type) {
                    if (Session::haveRight($itil_type::$rightname, CommonITILObject::READALL)) {
                        return true; // Can see all. No extra SQL conditions needed.
                    }

                    if ($itil_type !== Ticket::class) {
                        if (Session::haveRight($itil_type::$rightname, CommonITILObject::READMY)) {
                            $item = new $itil_type();
                            $group_table = $item->grouplinkclass::getTable();
                            $user_table = $item->userlinkclass::getTable();
                            $criteria = [
                                'LEFT JOIN' => [
                                    $user_table => [
                                        'ON' => [
                                            $user_table => $itil_type::getForeignKeyField(),
                                            '_' => 'id',
                                        ],
                                    ],
                                ],
                                'WHERE' => [
                                    'OR' => [
                                        '_.users_id_recipient' => Session::getLoginUserID(),
                                        $user_table . '.users_id' => Session::getLoginUserID(),
                                    ],
                                ],
                            ];

                            if (!empty($_SESSION['glpigroups'])) {
                                $criteria['LEFT JOIN'][$group_table] = [
                                    'ON' => [
                                        $group_table => $itil_type::getForeignKeyField(),
                                        '_' => 'id',
                                    ],
                                ];
                                $criteria['WHERE']['OR'][$group_table . '.groups_id'] = $_SESSION['glpigroups'];
                            }
                            return $criteria;
                        }
                    } else {
                        // Tickets have expanded permissions
                        $criteria = [
                            'LEFT JOIN' => [
                                'glpi_tickets_users' => [
                                    'ON' => [
                                        'glpi_tickets_users' => Ticket::getForeignKeyField(),
                                        '_' => 'id',
                                    ],
                                ],
                                'glpi_groups_tickets' => [
                                    'ON' => [
                                        'glpi_groups_tickets' => Ticket::getForeignKeyField(),
                                        '_' => 'id',
                                    ],
                                ],
                            ],
                            'WHERE' => ['OR' => []],
                        ];
                        if (Session::haveRight(Ticket::$rightname, CommonITILObject::READMY)) {
                            // Permission to see tickets as direct requester, observer or writer
                            $criteria['WHERE']['OR'][] = [
                                '_.users_id_recipient' => Session::getLoginUserID(),
                            ];
                            $criteria['WHERE']['OR'][] = [
                                'AND' => [
                                    'glpi_tickets_users' . '.users_id' => Session::getLoginUserID(),
                                    'glpi_tickets_users' . '.type' => [CommonITILActor::REQUESTER, CommonITILActor::OBSERVER],
                                ],
                            ];
                        }
                        if (!empty($_SESSION['glpigroups']) && Session::haveRight(Ticket::$rightname, Ticket::READGROUP)) {
                            // Permission to see tickets as requester or observer group member
                            $criteria['WHERE']['OR'][] = [
                                'AND' => [
                                    'glpi_groups_tickets.groups_id' => $_SESSION['glpigroups'],
                                    'glpi_groups_tickets.type' => [CommonITILActor::REQUESTER, CommonITILActor::OBSERVER],
                                ],
                            ];
                        }

                        if (Session::haveRight(Ticket::$rightname, Ticket::OWN) || Session::haveRight(Ticket::$rightname, Ticket::READASSIGN)) {
                            $criteria['WHERE']['OR'][] = [
                                'AND' => [
                                    'glpi_tickets_users' . '.users_id' => Session::getLoginUserID(),
                                    'glpi_tickets_users' . '.type' => CommonITILActor::ASSIGN,
                                ],
                            ];
                        }
                        if (Session::haveRight(Ticket::$rightname, Ticket::READASSIGN)) {
                            $criteria['WHERE']['OR'][] = [
                                'AND' => [
                                    'glpi_groups_tickets.groups_id' => $_SESSION['glpigroups'],
                                    'glpi_groups_tickets.type' => CommonITILActor::ASSIGN,
                                ],
                            ];
                        }
                        if (Session::haveRight(Ticket::$rightname, Ticket::READNEWTICKET)) {
                            $criteria['WHERE']['OR'][] = [
                                '_.status' => CommonITILObject::INCOMING,
                            ];
                        }

                        if (
                            Session::haveRightsOr(
                                'ticketvalidation',
                                [TicketValidation::VALIDATEINCIDENT,
                                    TicketValidation::VALIDATEREQUEST,
                                ]
                            )
                        ) {
                            $criteria['OR'][] = [
                                'AND' => [
                                    "glpi_ticketvalidations.itemtype_target" => User::class,
                                    "glpi_ticketvalidations.items_id_target" => Session::getLoginUserID(),
                                ],
                            ];
                            if (count($_SESSION['glpigroups'])) {
                                $criteria['OR'][] = [
                                    'AND' => [
                                        "glpi_ticketvalidations.itemtype_target" => Group::class,
                                        "glpi_ticketvalidations.items_id_target" => $_SESSION['glpigroups'],
                                    ],
                                ];
                            }
                        }
                        return empty($criteria['WHERE']['OR']) ? false : $criteria;
                    }
                    return false; // Cannot see anything.
                },
            ];

            if ($itil_type === Ticket::class) {
                $schemas[$itil_type]['properties']['type'] = [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'enum' => [Ticket::INCIDENT_TYPE, Ticket::DEMAND_TYPE],
                    'description' => <<<EOT
                        The type of the ticket.
                        - 1: Incident
                        - 2: Request
                        EOT,
                ];
                $schemas[$itil_type]['properties']['external_id'] = [
                    'x-field' => 'externalid',
                    'type' => Doc\Schema::TYPE_STRING,
                ];
                $schemas[$itil_type]['properties']['request_type'] = self::getDropdownTypeSchema(class: RequestType::class, full_schema: 'RequestType');
            }
            $schemas[$itil_type]['x-itemtype'] = $itil_type;
            $status_description = '';
            foreach ($itil_type::getAllStatusArray() as $status => $status_name) {
                $status_description .= "- $status: $status_name\n";
            }
            $schemas[$itil_type]['properties']['status'] = [
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'x-field' => 'status',
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'enum' => array_keys($itil_type::getAllStatusArray()),
                        'description' => $status_description,
                    ],
                    'name' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'x-mapped-from' => 'status.id',
                        // The x-mapper property indicates this property is calculated.
                        // The mapper callable gets the value of the x-mapped-from field (id in this case) and returns the name.
                        'x-mapper' => static fn($v) => $itil_type::getStatus($v),
                    ],
                ],
            ];
            $schemas[$itil_type]['properties']['entity'] = self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity');
            // Add completename field
            $schemas[$itil_type]['properties']['entity']['properties']['completename'] = ['type' => Doc\Schema::TYPE_STRING];

            $schemas[$itil_type]['properties']['team'] = [
                'type' => Doc\Schema::TYPE_ARRAY,
                'items' => [
                    'x-mapped-from' => 'id',
                    'x-mapper' => function ($v) use ($itil_type) {
                        $item = $itil_type::getById($v);
                        if ($item) {
                            return self::getCleanTeam($item);
                        }
                        return [];
                    },
                    'type' => Doc\Schema::TYPE_OBJECT,
                    'properties' => $schemas['TeamMember']['properties'],
                    'x-full-schema' => 'TeamMember',
                ],
            ];
        }

        $base_task_schema = [
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-rights-conditions' => [ // Object-level extra permissions
                'read' => static function () {
                    if (!Session::haveRight(CommonITILTask::$rightname, CommonITILTask::SEEPRIVATE)) {
                        return [
                            'WHERE' => [
                                'OR' => [
                                    'is_private' => 0,
                                    'users_id' => Session::getLoginUserID(),
                                ],
                            ],
                        ];
                    }
                    return true; // Allow reading by default. No extra SQL conditions needed.
                },
            ],
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'content' => ['type' => Doc\Schema::TYPE_STRING],
                'is_private' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'user' => self::getDropdownTypeSchema(class: User::class, full_schema: 'User'),
                'user_editor' => self::getDropdownTypeSchema(class: User::class, field: 'users_id_editor', full_schema: 'User'),
                'duration' => ['type' => Doc\Schema::TYPE_INTEGER, 'x-field' => 'actiontime'],
                'state' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'enum' => [
                        Planning::INFO,
                        Planning::TODO,
                        Planning::DONE,
                    ],
                    'description' => <<<EOT
                        The state of the task.
                        - 1: Information
                        - 2: To do
                        - 3: Done
                        EOT,
                ],
                'category' => self::getDropdownTypeSchema(class: TaskCategory::class, full_schema: 'TaskCategory'),
            ],
        ];

        $schemas['TicketTask'] = $base_task_schema;
        $schemas['TicketTask']['x-version-introduced'] = '2.0';
        $schemas['TicketTask']['x-itemtype'] = TicketTask::class;
        $schemas['TicketTask']['properties'][Ticket::getForeignKeyField()] = ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64];

        $schemas['ChangeTask'] = $base_task_schema;
        $schemas['ChangeTask']['x-version-introduced'] = '2.0';
        $schemas['ChangeTask']['x-itemtype'] = ChangeTask::class;
        $schemas['ChangeTask']['properties'][Change::getForeignKeyField()] = ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64];

        $schemas['ProblemTask'] = $base_task_schema;
        $schemas['ProblemTask']['x-version-introduced'] = '2.0';
        $schemas['ProblemTask']['x-itemtype'] = ProblemTask::class;
        $schemas['ProblemTask']['properties'][Problem::getForeignKeyField()] = ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64];

        $schemas['TaskCategory'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => TaskCategory::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'is_active' => ['type' => Doc\Schema::TYPE_BOOLEAN],
            ],
        ];

        $schemas['RequestType'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => RequestType::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
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
                    'x-field' => 'is_itilfollowup',
                ],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['Followup'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => ITILFollowup::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-rights-conditions' => [ // Object-level extra permissions
                'read' => static function () {
                    if (!Session::haveRight(ITILFollowup::$rightname, ITILFollowup::SEEPRIVATE)) {
                        return [
                            'WHERE' => [
                                'OR' => [
                                    'is_private' => 0,
                                    'users_id' => Session::getLoginUserID(),
                                ],
                            ],
                        ];
                    }
                    return true; // Allow reading by default. No extra SQL conditions needed.
                },
            ],
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'itemtype' => ['type' => Doc\Schema::TYPE_STRING,],
                'items_id' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64],
                'content' => ['type' => Doc\Schema::TYPE_STRING],
                'is_private' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'user' => self::getDropdownTypeSchema(class: User::class, full_schema: 'User'),
                'user_editor' => self::getDropdownTypeSchema(class: User::class, field: 'users_id_editor', full_schema: 'User'),
                'request_type' => self::getDropdownTypeSchema(RequestType::class, full_schema: 'RequestType'),
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['Solution'] = [
            'x-version-introduced' => '2.0',
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => ITILSolution::class,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'itemtype' => ['type' => Doc\Schema::TYPE_STRING],
                'items_id' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64],
                'content' => ['type' => Doc\Schema::TYPE_STRING],
                'user' => self::getDropdownTypeSchema(class: User::class, full_schema: 'User'),
                'user_editor' => self::getDropdownTypeSchema(class: User::class, field: 'users_id_editor', full_schema: 'User'),
            ],
        ];

        $base_validation_schema = [
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'requester' => self::getDropdownTypeSchema(class: User::class, full_schema: 'User'),
                'approver' => self::getDropdownTypeSchema(class: User::class, field: 'users_id_validate', full_schema: 'User'),
                'requested_approver_type' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'x-field' => 'itemtype_target',
                    'enum' => [User::getType(), Group::getType()],
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
                        CommonITILValidation::NONE,
                        CommonITILValidation::WAITING,
                        CommonITILValidation::ACCEPTED,
                        CommonITILValidation::REFUSED,
                    ],
                    'description' => <<<EOT
                        The status of the validation.
                        - 0: None
                        - 1: Waiting
                        - 2: Accepted
                        - 3: Refused
                        EOT,
                ],
                'submission_date' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'approval_date' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME, 'x-field' => 'validation_date'],
            ],
        ];

        $schemas['TicketValidation'] = $base_validation_schema;
        $schemas['TicketValidation']['x-version-introduced'] = '2.0';
        $schemas['TicketValidation']['x-itemtype'] = TicketValidation::class;
        $schemas['TicketValidation']['x-rights-conditions'] = [
            'read' => static fn() => Session::haveRightsOr(
                TicketValidation::$rightname,
                array_merge(
                    TicketValidation::getCreateRights(),
                    TicketValidation::getValidateRights(),
                    TicketValidation::getPurgeRights()
                )
            ),
        ];
        $schemas['TicketValidation']['properties'][Ticket::getForeignKeyField()] = ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64];

        $schemas['ChangeValidation'] = $base_validation_schema;
        $schemas['ChangeValidation']['x-version-introduced'] = '2.0';
        $schemas['ChangeValidation']['x-itemtype'] = ChangeValidation::class;
        $schemas['ChangeValidation']['x-rights-conditions'] = [
            'read' => static fn() => Session::haveRightsOr(
                ChangeValidation::$rightname,
                array_merge(
                    ChangeValidation::getCreateRights(),
                    ChangeValidation::getValidateRights(),
                    ChangeValidation::getPurgeRights()
                )
            ),
        ];
        $schemas['ChangeValidation']['properties'][Change::getForeignKeyField()] = ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64];

        $schemas['RecurringTicket'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => TicketRecurrent::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'description' => 'Recurring ticket',
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
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
            ],
        ];

        $schemas['RecurringChange'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => RecurrentChange::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'description' => 'Recurring change',
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
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
            ],
        ];

        $schemas['ExternalEventTemplate'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => PlanningExternalEventTemplate::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'description' => PlanningExternalEventTemplate::getTypeName(1),
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
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
                    'enum' => [Planning::INFO, Planning::TODO, Planning::DONE],
                    'description' => <<<EOT
                        The state of the event.
                        - 1: Information
                        - 2: To do
                        - 3: Done
                        EOT,
                ],
                'is_background' => ['x-field' => 'background', 'type' => Doc\Schema::TYPE_BOOLEAN],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['EventCategory'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => PlanningEventCategory::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'description' => PlanningEventCategory::getTypeName(1),
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'color' => ['type' => Doc\Schema::TYPE_STRING],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['ExternalEvent'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => PlanningExternalEvent::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'description' => PlanningExternalEvent::getTypeName(1),
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
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
            ],
        ];

        return $schemas;
    }

    /**
     * @param class-string<CommonDBTM> $subtype
     * @return string
     */
    public static function getFriendlyNameForSubtype(string $subtype): string
    {
        return match (true) {
            is_subclass_of($subtype, CommonITILTask::class) => 'Task',
            $subtype === ITILFollowup::class => 'Followup',
            $subtype === Document_Item::class => 'Document',
            $subtype === ITILSolution::class => 'Solution',
            is_subclass_of($subtype, CommonITILValidation::class) => 'Validation',
            default => $subtype,
        };
    }

    #[Route(path: '/{itemtype}', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(
        schema_name: '{itemtype}',
        description: 'List or search Tickets, Changes or Problems'
    )]
    public function search(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::searchBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(
        schema_name: '{itemtype}',
        description: 'Get an existing Ticket, Change or Problem'
    )]
    public function getItem(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::getOneBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}', methods: ['POST'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\CreateRoute(
        schema_name: '{itemtype}',
        description: 'Create a new new Ticket, Change or Problem'
    )]
    public function createItem(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::createBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getParameters() + ['itemtype' => $itemtype], [self::class, 'getItem']);
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['PATCH'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\UpdateRoute(
        schema_name: '{itemtype}',
        description: 'Update an existing Ticket, Change or Problem'
    )]
    public function updateItem(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::updateBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['DELETE'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\DeleteRoute(
        schema_name: '{itemtype}',
        description: 'Delete a Ticket, Change or Problem'
    )]
    public function deleteItem(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::deleteBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
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
        } elseif ($subitem_type === 'Task') {
            $schema = $this->getKnownSchema($item::getTaskClass(), $api_version);
        } elseif ($subitem_type === 'Validation' && class_exists($item::getType() . 'Validation')) {
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
        $subitem_types = $subitem_types === [] ? ['Followup', 'Task', 'Document', 'Solution', 'Validation'] : $subitem_types;
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
            if (array_key_exists('is_private', $schema['properties']) && !Session::haveRight($schema_itemtype::$rightname, $schema_itemtype::SEEPRIVATE)) {
                $filters .= ';is_private==0';
            }

            $subitem_results = ResourceAccessor::searchBySchema($schema, [
                'filter' => $filters,
                'limit' => 1000,
            ]);
            $decoded_results = json_decode($subitem_results->getBody(), true);
            foreach ($decoded_results as $decoded_result) {
                $results[] = [
                    'type' => $subitem_type,
                    'item' => $decoded_result,
                ];
            }
        }
        $single_result = $request->hasParameter('filter') && str_contains($request->getParameter('filter'), 'id==');
        if ($single_result && count($results) > 0) {
            $results = $results[0]['item'];
        } elseif ($single_result && count($results) === 0) {
            $results = null;
        }
        return $results;
    }

    #[Route(path: '/{itemtype}/{id}/Timeline', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Get all timeline items for a Ticket, Change or Problem'
    )]
    public function getTimeline(Request $request): Response
    {
        //TODO Route documentation needs a response schema
        /** @var CommonITILObject $item */
        $item = $request->getParameter('_item');
        $timeline = $this->getITILTimelineItems($item, $request);
        return new JSONResponse($timeline);
    }

    #[Route(path: '/{itemtype}/{id}/Timeline/{subitem_type}', methods: ['GET'], requirements: [
        'subitem_type' => 'Followup|Document|Solution',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Get all timeline items of a specific type for a Ticket, Change or Problem',
        responses: [
            new Doc\Response(schema: new Doc\SchemaReference('{subitem_type}[]')),
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
        description: 'Get all tasks for a Ticket, Change or Problem',
        responses: [
            new Doc\Response(schema: new Doc\SchemaReference('{itemtype}Task[]')),
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

    #[Route(path: '/{itemtype}/{id}/Timeline/Validation', methods: ['GET'], requirements: [
        'itemtype' => 'Ticket|Change',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Get all validations for a Ticket or Change',
        responses: [
            new Doc\Response(schema: new Doc\SchemaReference('{itemtype}Validation[]')),
        ]
    )]
    public function getTimelineValidations(Request $request): Response
    {
        /** @var CommonITILObject $item */
        $item = $request->getParameter('_item');

        $timeline = $this->getITILTimelineItems($item, $request, ['Validation']);
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

    #[Route(path: '/{itemtype}/{id}/Timeline/{subitem_type}/{subitem_id}', methods: ['GET'], requirements: [
        'subitem_type' => 'Followup|Document|Solution',
        'subitem_id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(
        schema_name: '{subitem_type}',
        description: 'Get an existing specific timeline item by type and ID for a Ticket, Change or Problem'
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
        'subitem_id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(
        schema_name: '{itemtype}Task',
        description: 'Get an existing task for a Ticket, Change or Problem'
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

    #[Route(path: '/{itemtype}/{id}/Timeline/Validation/{subitem_id}', methods: ['GET'], requirements: [
        'itemtype' => 'Ticket|Change',
        'subitem_id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(
        schema_name: '{itemtype}Validation',
        description: 'Get an existing validation for a Ticket or Change'
    )]
    public function getTimelineValidation(Request $request): Response
    {
        $filters = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filters .= ';id==' . $request->getAttribute('subitem_id');
        // Reuse existing logic from the getTimelineItems route
        $request->setParameter('filter', $filters);
        $request->setAttribute('subitem_type', 'Validation');
        return $this->getTimelineItems($request);
    }

    #[Route(path: '/{itemtype}/{id}/Timeline/{subitem_type}', methods: ['POST'], requirements: [
        'subitem_type' => 'Followup|Document|Solution',
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\CreateRoute(
        schema_name: '{subitem_type}',
        description: 'Create a new timeline item for a Ticket, Change or Problem'
    )]
    public function createTimelineItem(Request $request): Response
    {
        /** @var CommonITILObject $item */
        $item = $request->getParameter('_item');
        $subitem_type = $request->getAttribute('subitem_type');

        $parameters = $request->getParameters();
        $parameters = array_merge($parameters, $this->getRequiredTimelineItemFields($item, $request, $subitem_type));
        $schema = $this->getKnownSubitemSchema($item, $subitem_type, $this->getAPIVersion($request));
        return ResourceAccessor::createBySchema($schema, $parameters, [self::class, 'getTimelineItem'], [
            'mapped' => [
                'itemtype' => $item::getType(),
                'subitem_type' => $subitem_type,
                'id' => $item->getID(),
            ],
            'id' => 'subitem_id',
        ]);
    }

    #[Route(path: '/{itemtype}/{id}/Timeline/Task', methods: ['POST'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\CreateRoute(
        schema_name: '{itemtype}Task',
        description: 'Create a new task for a Ticket, Change or Problem'
    )]
    public function createTimelineTask(Request $request): Response
    {
        /** @var CommonITILObject $item */
        $item = $request->getParameter('_item');

        $parameters = $request->getParameters();
        $parameters = array_merge($parameters, $this->getRequiredTimelineItemFields($item, $request, 'Task'));
        $schema = $this->getKnownSubitemSchema($item, 'Task', $this->getAPIVersion($request));
        return ResourceAccessor::createBySchema($schema, $parameters, [self::class, 'getTimelineTask'], [
            'mapped' => [
                'itemtype' => $item::getType(),
                'subitem_type' => 'Task',
                'id' => $item->getID(),
            ],
            'id' => 'subitem_id',
        ]);
    }

    #[Route(path: '/{itemtype}/{id}/Timeline/Validation', methods: ['POST'], requirements: [
        'itemtype' => 'Ticket|Change',
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\CreateRoute(
        schema_name: '{itemtype}Validation',
        description: 'Create a new validation for a Ticket or Change',
    )]
    public function createTimelineValidation(Request $request): Response
    {
        /** @var CommonITILObject $item */
        $item = $request->getParameter('_item');

        $parameters = $request->getParameters();
        $parameters = array_merge($parameters, $this->getRequiredTimelineItemFields($item, $request, 'Validation'));
        $schema = $this->getKnownSubitemSchema($item, 'Validation', $this->getAPIVersion($request));
        return ResourceAccessor::createBySchema($schema, $parameters, [self::class, 'getTimelineValidation'], [
            'mapped' => [
                'itemtype' => $item::getType(),
                'subitem_type' => 'Validation',
                'id' => $item->getID(),
            ],
            'id' => 'subitem_id',
        ]);
    }

    #[Route(path: '/{itemtype}/{id}/Timeline/{subitem_type}/{subitem_id}', methods: ['PATCH'], requirements: [
        'subitem_type' => 'Followup|Document|Solution',
        'subitem_id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\UpdateRoute(
        schema_name: '{subitem_type}',
        description: 'Update an existing timeline item by ID for a Ticket, Change or Problem'
    )]
    public function updateTimelineItem(Request $request): Response
    {
        /** @var CommonITILObject $item */
        $item = $request->getParameter('_item');
        $subitem_type = $request->getAttribute('subitem_type');

        $parameters = $request->getParameters();
        $required_fields = $this->getRequiredTimelineItemFields($item, $request, $subitem_type);
        // Required fields are used to link to the parent item. We cannot let them be changed
        foreach (array_keys($required_fields) as $field) {
            unset($parameters[$field]);
        }
        $attributes = $request->getAttributes();
        $attributes['id'] = $request->getAttribute('subitem_id');
        return ResourceAccessor::updateBySchema($this->getKnownSubitemSchema($item, $subitem_type, $this->getAPIVersion($request)), $attributes, $parameters);
    }

    #[Route(path: '/{itemtype}/{id}/Timeline/Task/{subitem_id}', methods: ['PATCH'], requirements: [
        'subitem_id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\UpdateRoute(
        schema_name: '{itemtype}Task',
        description: 'Update an existing task for a Ticket, Change or Problem'
    )]
    public function updateTimelineTask(Request $request): Response
    {
        /** @var CommonITILObject $item */
        $item = $request->getParameter('_item');

        $parameters = $request->getParameters();
        $required_fields = $this->getRequiredTimelineItemFields($item, $request, 'Task');
        // Required fields are used to link to the parent item. We cannot let them be changed
        foreach (array_keys($required_fields) as $field) {
            unset($parameters[$field]);
        }
        $attributes = $request->getAttributes();
        $attributes['id'] = $request->getAttribute('subitem_id');
        return ResourceAccessor::updateBySchema($this->getKnownSubitemSchema($item, 'Task', $this->getAPIVersion($request)), $attributes, $parameters);
    }

    #[Route(path: '/{itemtype}/{id}/Timeline/Validation/{subitem_id}', methods: ['PATCH'], requirements: [
        'itemtype' => 'Ticket|Change',
        'subitem_id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\UpdateRoute(
        schema_name: '{itemtype}Validation',
        description: 'Update an existing validation for a Ticket or Change'
    )]
    public function updateTimelineValidation(Request $request): Response
    {
        /** @var CommonITILObject $item */
        $item = $request->getParameter('_item');

        $parameters = $request->getParameters();
        $required_fields = $this->getRequiredTimelineItemFields($item, $request, 'Validation');
        // Required fields are used to link to the parent item. We cannot let them be changed
        foreach (array_keys($required_fields) as $field) {
            unset($parameters[$field]);
        }
        $attributes = $request->getAttributes();
        $attributes['id'] = $request->getAttribute('subitem_id');
        return ResourceAccessor::updateBySchema($this->getKnownSubitemSchema($item, 'Validation', $this->getAPIVersion($request)), $attributes, $parameters);
    }

    #[Route(path: '/{itemtype}/{id}/Timeline/{subitem_type}/{subitem_id}', methods: ['DELETE'], requirements: [
        'subitem_type' => 'Followup|Document|Solution',
        'subitem_id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Delete a timeline item by ID for a Ticket, Change or Problem'
    )]
    public function deleteTimelineItem(Request $request): Response
    {
        /** @var CommonITILObject $item */
        $item = $request->getParameter('_item');
        $subitem_type = $request->getAttribute('subitem_type');
        $attributes = $request->getAttributes();
        $attributes['id'] = $request->getAttribute('subitem_id');
        return ResourceAccessor::deleteBySchema($this->getKnownSubitemSchema($item, $subitem_type, $this->getAPIVersion($request)), $attributes, $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{id}/Timeline/Task/{subitem_id}', methods: ['DELETE'], requirements: [
        'subitem_id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Delete a task for a Ticket, Change or Problem'
    )]
    public function deleteTimelineTask(Request $request): Response
    {
        /** @var CommonITILObject $item */
        $item = $request->getParameter('_item');
        $attributes = $request->getAttributes();
        $attributes['id'] = $request->getAttribute('subitem_id');
        return ResourceAccessor::deleteBySchema($this->getKnownSubitemSchema($item, 'Task', $this->getAPIVersion($request)), $attributes, $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{id}/Timeline/Validation/{subitem_id}', methods: ['DELETE'], requirements: [
        'itemtype' => 'Ticket|Change',
        'subitem_id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Delete a validation for a Ticket or Change',
    )]
    public function deleteTimelineValidation(Request $request): Response
    {
        /** @var CommonITILObject $item */
        $item = $request->getParameter('_item');
        $attributes = $request->getAttributes();
        $attributes['id'] = $request->getAttribute('subitem_id');
        return ResourceAccessor::deleteBySchema($this->getKnownSubitemSchema($item, 'Validation', $this->getAPIVersion($request)), $attributes, $request->getParameters());
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
            } catch (InvalidArgumentException) {
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
     * @throws InvalidArgumentException If the textual or numeric role is not valid
     */
    private static function getRoleID(string|int $role): int
    {
        return match ($role) {
            Team::ROLE_REQUESTER, "requester" => Team::ROLE_REQUESTER,
            Team::ROLE_ASSIGNED, "assigned" => Team::ROLE_ASSIGNED,
            Team::ROLE_OBSERVER, "observer", "watcher" => Team::ROLE_OBSERVER,
            default => throw new InvalidArgumentException("Invalid role: $role"),
        };
    }

    /**
     * Get the textual role type given a numeric representation or a textual representation.
     *
     * Only valid roles are returned. If a role is given that is not valid, an exception is thrown instead of returning the
     * invalid role.
     * @param string|int $role The numeric or textual representation of the role
     * @return string The valid role in textual form
     * @throws InvalidArgumentException If the textual or numeric role is not valid
     */
    private static function getRoleName(string|int $role): string
    {
        return match ($role) {
            Team::ROLE_REQUESTER, "requester" => "requester",
            Team::ROLE_ASSIGNED, "assigned" => "assigned",
            Team::ROLE_OBSERVER, "observer", "watcher" => "observer",
            default => throw new InvalidArgumentException("Invalid role: $role"),
        };
    }

    #[Route(path: '/{itemtype}/{id}/TeamMember', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Get the team members for a specific Ticket, Change or Problem',
        responses: [
            new Doc\Response(schema: new Doc\SchemaReference('TeamMember[]')),
        ]
    )]
    public function getTeamMembers(Request $request): Response
    {
        /** @var CommonITILObject $item */
        $item = $request->getParameter('_item');

        $team = self::getCleanTeam($item);
        return new JSONResponse($team);
    }

    #[Route(path: '/{itemtype}/{id}/TeamMember/{role}', methods: ['GET'], requirements: [
        'role' => '\w+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Get the team members by role for specific Ticket, Change, Problem',
        parameters: [
            new Doc\Parameter(
                name: 'role',
                schema: new Doc\Schema(
                    type: Doc\Schema::TYPE_STRING,
                    enum: ['requester', 'assigned', 'observer']
                ),
                description: 'The role of the team member',
                location: Doc\Parameter::LOCATION_BODY
            ),
        ],
        responses: [
            new Doc\Response(schema: new Doc\SchemaReference('TeamMember[]')),
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
        $team = array_filter($team, static fn($v) => $v['role'] === $role_id, ARRAY_FILTER_USE_BOTH);
        return new JSONResponse($team);
    }

    #[Route(path: '/{itemtype}/{id}/TeamMember', methods: ['POST'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Add a team member to a specific Ticket, Change or Problem',
        parameters: [
            new Doc\Parameter(
                name: 'type',
                schema: new Doc\Schema(
                    type: Doc\Schema::TYPE_STRING,
                    enum: ['User', 'Group', 'Supplier']
                ),
                description: 'The type of team member. The applicable types of members will depend on the role.',
                location: Doc\Parameter::LOCATION_BODY
            ),
            new Doc\Parameter(
                name: 'id',
                schema: new Doc\Schema(type: Doc\Schema::TYPE_INTEGER),
                description: 'The ID of the team member',
                location: Doc\Parameter::LOCATION_BODY
            ),
            new Doc\Parameter(
                name: 'role',
                schema: new Doc\Schema(
                    type: Doc\Schema::TYPE_STRING,
                    enum: ['requester', 'assigned', 'observer']
                ),
                description: 'The role of the team member',
                location: Doc\Parameter::LOCATION_BODY
            ),
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
            'role'  => $role_id,
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
            new Doc\Parameter(
                name: 'type',
                schema: new Doc\Schema(
                    type: Doc\Schema::TYPE_STRING,
                    enum: ['User', 'Group', 'Supplier']
                ),
                description: 'The type of team member. The applicable types of members will depend on the role.',
                location: Doc\Parameter::LOCATION_BODY
            ),
            new Doc\Parameter(
                name: 'id',
                schema: new Doc\Schema(type: Doc\Schema::TYPE_INTEGER),
                description: 'The ID of the team member',
                location: Doc\Parameter::LOCATION_BODY
            ),
            new Doc\Parameter(
                name: 'role',
                schema: new Doc\Schema(
                    type: Doc\Schema::TYPE_STRING,
                    enum: ['requester', 'assigned', 'observer']
                ),
                description: 'The role of the team member',
                location: Doc\Parameter::LOCATION_BODY
            ),
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
            'role'  => $role_id,
        ]);
        if ($result) {
            return new JSONResponse(null, 200);
        }
        return self::getInvalidParametersErrorResponse();
    }

    #[Route(path: '/RecurringTicket', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(schema_name: 'RecurringTicket')]
    public function searchRecurringTickets(Request $request): Response
    {
        return ResourceAccessor::searchBySchema($this->getKnownSchema('RecurringTicket', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/RecurringTicket/{id}', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(schema_name: 'RecurringTicket')]
    public function getRecurringTicket(Request $request): Response
    {
        return ResourceAccessor::getOneBySchema($this->getKnownSchema('RecurringTicket', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/RecurringTicket', methods: ['POST'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\CreateRoute(schema_name: 'RecurringTicket')]
    public function createRecurringTicket(Request $request): Response
    {
        return ResourceAccessor::createBySchema($this->getKnownSchema('RecurringTicket', $this->getAPIVersion($request)), $request->getParameters(), [self::class, 'getRecurringTicket']);
    }

    #[Route(path: '/RecurringTicket/{id}', methods: ['PATCH'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\UpdateRoute(schema_name: 'RecurringTicket')]
    public function updateRecurringTicket(Request $request): Response
    {
        return ResourceAccessor::updateBySchema($this->getKnownSchema('RecurringTicket', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/RecurringTicket/{id}', methods: ['DELETE'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\DeleteRoute(schema_name: 'RecurringTicket')]
    public function deleteRecurringTicket(Request $request): Response
    {
        return ResourceAccessor::deleteBySchema($this->getKnownSchema('RecurringTicket', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/RecurringChange', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(schema_name: 'RecurringChange')]
    public function searchRecurringChanges(Request $request): Response
    {
        return ResourceAccessor::searchBySchema($this->getKnownSchema('RecurringChange', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/RecurringChange/{id}', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(schema_name: 'RecurringChange')]
    public function getRecurringChange(Request $request): Response
    {
        return ResourceAccessor::getOneBySchema($this->getKnownSchema('RecurringChange', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/RecurringChange', methods: ['POST'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\CreateRoute(schema_name: 'RecurringChange')]
    public function createRecurringChange(Request $request): Response
    {
        return ResourceAccessor::createBySchema($this->getKnownSchema('RecurringChange', $this->getAPIVersion($request)), $request->getParameters(), [self::class, 'getRecurringChange']);
    }

    #[Route(path: '/RecurringChange/{id}', methods: ['PATCH'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\UpdateRoute(schema_name: 'RecurringChange')]
    public function updateRecurringChange(Request $request): Response
    {
        return ResourceAccessor::updateBySchema($this->getKnownSchema('RecurringChange', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/RecurringChange/{id}', methods: ['DELETE'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\DeleteRoute(schema_name: 'RecurringChange')]
    public function deleteRecurringChange(Request $request): Response
    {
        return ResourceAccessor::deleteBySchema($this->getKnownSchema('RecurringChange', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/ExternalEvent', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(schema_name: 'ExternalEvent')]
    public function searchExternalEvent(Request $request): Response
    {
        return ResourceAccessor::searchBySchema($this->getKnownSchema('ExternalEvent', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/ExternalEvent/{id}', methods: ['GET'], requirements: [
        'id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(schema_name: 'ExternalEvent')]
    public function getExternalEvent(Request $request): Response
    {
        return ResourceAccessor::getOneBySchema($this->getKnownSchema('ExternalEvent', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/ExternalEvent', methods: ['POST'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\CreateRoute(schema_name: 'ExternalEvent')]
    public function createExternalEvent(Request $request): Response
    {
        return ResourceAccessor::createBySchema($this->getKnownSchema('ExternalEvent', $this->getAPIVersion($request)), $request->getParameters(), [self::class, 'getExternalEvent']);
    }

    #[Route(path: '/ExternalEvent/{id}', methods: ['PATCH'], requirements: [
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\UpdateRoute(schema_name: 'ExternalEvent')]
    public function updateExternalEvent(Request $request): Response
    {
        return ResourceAccessor::updateBySchema($this->getKnownSchema('ExternalEvent', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/ExternalEvent/{id}', methods: ['DELETE'], requirements: [
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\DeleteRoute(schema_name: 'ExternalEvent')]
    public function deleteExternalEvent(Request $request): Response
    {
        return ResourceAccessor::deleteBySchema($this->getKnownSchema('ExternalEvent', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }
}
