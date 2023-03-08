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

namespace Glpi\Api\HL\Controller;

use Calendar;
use Change;
use ChangeTemplate;
use CommonDBTM;
use CommonITILObject;
use Entity;
use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\Route;
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
            ]
        ];

        $itil_types = [Ticket::class, Change::class, Problem::class];

        /** @var class-string<CommonITILObject> $itil_type */
        foreach ($itil_types as $itil_type) {
            $schemas[$itil_type] = $base_schema;
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
            $schemas[$itil_type]['properties']['entity'] = self::getDropdownTypeSchema(Entity::class);
            // Add completename field
            $schemas[$itil_type]['properties']['entity']['properties']['completename'] = ['type' => Doc\Schema::TYPE_STRING];

            $schemas[$itil_type]['properties']['team'] = [
                'type' => Doc\Schema::TYPE_ARRAY,
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

        $schemas['Task'] = [
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'x-readonly' => true,
                ],
                'content' => ['type' => Doc\Schema::TYPE_STRING],
            ]
        ];

        $schemas['Followup'] = [
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'x-readonly' => true,
                ],
                'content' => ['type' => Doc\Schema::TYPE_STRING],
            ]
        ];

        $schemas['TeamMember'] = [
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
                'entity' => self::getDropdownTypeSchema(Entity::class),
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'is_active' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'template' => self::getDropdownTypeSchema(TicketTemplate::class),
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
                'calendar' => self::getDropdownTypeSchema(Calendar::class),
                'ticket_per_item' => ['type' => Doc\Schema::TYPE_BOOLEAN],
            ]
        ];

        $schemas['RecurringChange'] = [
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
                'entity' => self::getDropdownTypeSchema(Entity::class),
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'is_active' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'template' => self::getDropdownTypeSchema(ChangeTemplate::class),
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
                'calendar' => self::getDropdownTypeSchema(Calendar::class),
            ]
        ];

        $schemas['ExternalEvent'] = [
            'x-itemtype' => \PlanningExternalEvent::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'description' => 'External event',
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'x-readonly' => true,
                ],
                'uuid' => ['type' => Doc\Schema::TYPE_STRING, 'pattern' => Doc\Schema::PATTERN_UUIDV4],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'text' => ['type' => Doc\Schema::TYPE_STRING],
                'template' => self::getDropdownTypeSchema(PlanningExternalEventTemplate::class),
                'category' => self::getDropdownTypeSchema(PlanningEventCategory::class),
                'entity' => self::getDropdownTypeSchema(Entity::class),
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'user' => self::getDropdownTypeSchema(User::class),
                'group' => self::getDropdownTypeSchema(Group::class),
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
     * @param string $subitem_schema_name
     * @param class-string<CommonITILObject> $itemtype
     * @return array
     */
    private function getSubitemSchemaFor(string $subitem_schema_name, string $itemtype): array
    {
        $subitem_schema = $this->getKnownSchema($subitem_schema_name);
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

    #[Route(path: '/{itemtype}/', methods: ['GET'])]
    #[Doc\Route(
        description: 'List or search Tickets, Changes or Problems'
    )]
    public function search(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return Search::searchBySchema($this->getKnownSchema($itemtype), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['GET'])]
    #[Doc\Route(
        description: 'Get a Ticket, Change or Problem by ID',
        parameters: [
            [
                'name' => 'id',
                'description' => 'The ID of the Ticket, Change, or Problem',
                'location' => Doc\Parameter::LOCATION_PATH,
                'schema' => ['type' => Doc\Schema::TYPE_INTEGER]
            ]
        ]
    )]
    public function getItem(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return Search::getOneBySchema($this->getKnownSchema($itemtype), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/', methods: ['POST'])]
    #[Doc\Route(
        description: 'Create a new Ticket, Change or Problem'
    )]
    public function createItem(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return Search::createBySchema($this->getKnownSchema($itemtype), $request->getParameters() + ['itemtype' => $itemtype], [self::class, 'getItem']);
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['PATCH'])]
    #[Doc\Route(
        description: 'Update a Ticket, Change or Problem by ID',
        parameters: [
            [
                'name' => 'id',
                'description' => 'The ID of the Ticket, Change, or Problem',
                'location' => Doc\Parameter::LOCATION_PATH,
                'schema' => ['type' => Doc\Schema::TYPE_INTEGER]
            ]
        ]
    )]
    public function updateItem(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return Search::updateBySchema($this->getKnownSchema($itemtype), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['DELETE'])]
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
        return Search::deleteBySchema($this->getKnownSchema($itemtype), $request->getAttributes(), $request->getParameters());
    }

    /**
     * @param CommonITILObject $item
     * @return array{type: string, item: array<string, mixed>}[]
     */
    private function getCleanTimelineItems(CommonITILObject $item): array
    {
        $timeline = $item->getTimelineItems();
        $items = [];
        // Only keep certain properties
        foreach ($timeline as &$timeline_item) {
            $t_item = [
                '_itemtype' => $this->getSubitemFriendlyType($item, $timeline_item['type']),
            ];
            $allowed_props = [
                'id', 'content', 'uuid', 'date', 'users_id', 'users_id_editor', 'is_private',
                'actiontime', 'begin', 'end', 'state', 'users_id_tech', 'groups_id_tech',
                'date_mod', 'date_creation', 'filepath'
            ];
            foreach ($allowed_props as $prop) {
                if (array_key_exists($prop, $timeline_item['item'])) {
                    $t_item[$prop] = $timeline_item['item'][$prop];
                }
            }
            if (isset($t_item['filepath'])) {
                // Replace internal path with external path
                $front_path = 'front/document.send.php?docid=' . $timeline_item['item']['id'] . '&' . $item::getForeignKeyField() . '=' . $item->getID();
                $t_item['filepath'] = Html::getPrefixedUrl($front_path);
            }
            $items[] = $t_item;
        }
        return $items;
    }

    #[Route(path: '/{itemtype}/{id}/Timeline', methods: ['GET'])]
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
        $timeline = $this->getCleanTimelineItems($item);
        return new JSONResponse($timeline);
    }

    #[Route(path: '/{itemtype}/{id}/Timeline/{subitem_type}', methods: ['GET'], requirements: [
        'subitem_type' => 'Followup|Task|Document|Solution|Validation|Log'
    ])]
    #[Doc\Route(
        description: 'Get all timeline items of a specific type for a Ticket, Change or Problem by ID',
        parameters: [
            [
                'name' => 'id',
                'description' => 'The ID of the Ticket, Change, or Problem',
                'location' => Doc\Parameter::LOCATION_PATH,
                'schema' => ['type' => Doc\Schema::TYPE_INTEGER]
            ]
        ]
    )]
    public function getTimelineItems(Request $request): Response
    {
        /** @var CommonITILObject $item */
        $item = $request->getParameter('_item');
        $friendly_subitem_type = $request->getAttribute('subitem_type');

        $timeline = $this->getCleanTimelineItems($item);
        $timeline = array_filter($timeline, static function ($timeline_item) use ($friendly_subitem_type) {
            return $timeline_item['_itemtype'] === $friendly_subitem_type;
        });
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
        'subitem_type' => 'Followup|Task|Document|Solution|Validation|Log',
        'subitem_id' => '\d+'
    ])]
    #[Doc\Route(
        description: 'Get a specific timeline item by type and ID for a Ticket, Change or Problem by ID',
        parameters: [
            [
                'name' => 'id',
                'description' => 'The ID of the Ticket, Change, or Problem',
                'location' => Doc\Parameter::LOCATION_PATH,
                'schema' => ['type' => Doc\Schema::TYPE_INTEGER]
            ]
        ]
    )]
    public function getTimelineItem(Request $request): Response
    {
        /** @var CommonITILObject $item */
        $item = $request->getParameter('_item');
        $subitem_type = $this->getSubitemType($item, $request->getAttribute('subitem_type'));
        /** @var CommonDBTM $subitem */
        $subitem = new $subitem_type();

        if (!$subitem::canView()) {
            return self::getAccessDeniedErrorResponse();
        }

        $subitem_id = $request->getAttribute('subitem_id');
        if (!$subitem->getFromDB($subitem_id)) {
            return self::getNotFoundErrorResponse();
        }
        if (!$subitem->canViewItem()) {
            return self::getAccessDeniedErrorResponse();
        }
        $subitem::unsetUndisclosedFields($subitem->fields);
        return new JSONResponse($subitem->fields);
    }

    #[Route(path: '/{itemtype}/{id}/Timeline/{subitem_type}', methods: ['POST'], requirements: [
        'subitem_type' => 'Followup|Task|Document|Solution|Validation|Log'
    ])]
    #[Doc\Route(
        description: 'Create a timeline item for a Ticket, Change or Problem by ID',
        parameters: [
            [
                'name' => 'id',
                'description' => 'The ID of the Ticket, Change, or Problem',
                'location' => Doc\Parameter::LOCATION_PATH,
                'schema' => ['type' => Doc\Schema::TYPE_INTEGER]
            ]
        ]
    )]
    public function createTimelineItem(Request $request): Response
    {
        /** @var CommonITILObject $item */
        $item = $request->getParameter('_item');
        $subitem_type = $this->getSubitemType($item, $request->getAttribute('subitem_type'));
        /** @var CommonDBTM $subitem */
        $subitem = new $subitem_type();

        if (!$subitem::canCreate()) {
            return self::getAccessDeniedErrorResponse();
        }

        $input = $request->getParameters() + $this->getSubitemLinkFields($item, $subitem_type);
        unset($input['_item']);
        $subitem->add($input);
        $api_path = "/Assistance/{$item::getType()}/{$item->fields['id']}/Timeline/{$request->getAttribute('subitem_type')}/{$subitem->getID()}";
        return self::getItemLinkResponse($subitem->fields['id'], $api_path, 201);
    }

    #[Route(path: '/{itemtype}/{id}/Timeline/{subitem_type}/{subitem_id}', methods: ['PATCH'], requirements: [
        'subitem_type' => 'Followup|Task|Document|Solution|Validation|Log',
        'subitem_id' => '\d+'
    ])]
    #[Doc\Route(
        description: 'Update a timeline item by ID for a Ticket, Change or Problem by ID',
        parameters: [
            [
                'name' => 'id',
                'description' => 'The ID of the Ticket, Change, or Problem',
                'location' => Doc\Parameter::LOCATION_PATH,
                'schema' => ['type' => Doc\Schema::TYPE_INTEGER]
            ]
        ]
    )]
    public function updateTimelineItem(Request $request): Response
    {
        /** @var CommonITILObject $item */
        $item = $request->getParameter('_item');
        $subitem_type = $this->getSubitemType($item, $request->getAttribute('subitem_type'));
        /** @var CommonDBTM $subitem */
        $subitem = new $subitem_type();

        if (!$subitem::canUpdate()) {
            return self::getAccessDeniedErrorResponse();
        }
        $subitem->update($request->getParameters() + [
            'id' => $request->getAttribute('subitem_id')
        ]);
        $api_path = "/{$item::getType()}/{$item->fields['id']}/Timeline/{$request->getAttribute('subitem_type')}/{$subitem->getID()}";
        return self::getItemLinkResponse($subitem->fields['id'], $api_path);
    }

    #[Route(path: '/{itemtype}/{id}/Timeline/{subitem_type}/{subitem_id}', methods: ['DELETE'], requirements: [
        'subitem_type' => 'Followup|Task|Document|Solution|Validation|Log',
        'subitem_id' => '\d+'
    ])]
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
        $subitem_type = $this->getSubitemType($item, $request->getAttribute('subitem_type'));
        /** @var CommonDBTM $subitem */
        $subitem = new $subitem_type();
        $force = $request->hasParameter('force') ? $request->getParameter('force') : false;

        if (!$subitem::canUpdate()) {
            return self::getAccessDeniedErrorResponse();
        }
        $subitem->delete([
            'id' => $request->getAttribute('subitem_id'),
        ], $force);
        return new JSONResponse(null, 204);
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

    #[Route(path: '/{itemtype}/{id}/TeamMember', methods: ['GET'])]
    #[Doc\Route(
        description: 'Get the team members for a specific Ticket, Change or Problem by ID'
    )]
    public function getTeamMembers(Request $request): Response
    {
        /** @var CommonITILObject $item */
        $item = $request->getParameter('_item');

        $team = self::getCleanTeam($item);
        return new JSONResponse($team);
    }

    #[Route(path: '/{itemtype}/{id}/TeamMember/{role}', methods: ['GET'])]
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

    #[Route(path: '/RecurringTicket', methods: ['GET'])]
    #[Doc\Route(
        description: 'List or search recurring tickets'
    )]
    public function searchRecurringTickets(Request $request): Response
    {
        return Search::searchBySchema($this->getKnownSchema('RecurringTicket'), $request->getParameters());
    }

    #[Route(path: '/RecurringTicket/{id}', methods: ['GET'], requirements: [
        'id' => '\d+'
    ])]
    #[Doc\Route(
        description: 'Get a recurring ticket by ID',
    )]
    public function getRecurringTicket(Request $request): Response
    {
        return Search::getOneBySchema($this->getKnownSchema('RecurringTicket'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/RecurringTicket', methods: ['POST'])]
    #[Doc\Route(
        description: 'Create a recurring ticket',
    )]
    public function createRecurringTicket(Request $request): Response
    {
        return Search::createBySchema($this->getKnownSchema('RecurringTicket'), $request->getParameters(), [self::class, 'getRecurringTicket']);
    }

    #[Route(path: '/RecurringTicket/{id}', methods: ['PATCH'], requirements: [
        'id' => '\d+'
    ])]
    #[Doc\Route(
        description: 'Update a recurring ticket by ID',
    )]
    public function updateRecurringTicket(Request $request): Response
    {
        return Search::updateBySchema($this->getKnownSchema('RecurringTicket'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/RecurringTicket/{id}', methods: ['DELETE'], requirements: [
        'id' => '\d+'
    ])]
    #[Doc\Route(
        description: 'Delete a recurring ticket by ID',
    )]
    public function deleteRecurringTicket(Request $request): Response
    {
        return Search::deleteBySchema($this->getKnownSchema('RecurringTicket'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/RecurringChange', methods: ['GET'])]
    #[Doc\Route(
        description: 'List or search recurring changes'
    )]
    public function searchRecurringChanges(Request $request): Response
    {
        return Search::searchBySchema($this->getKnownSchema('RecurringChange'), $request->getParameters());
    }

    #[Route(path: '/RecurringChange/{id}', methods: ['GET'], requirements: [
        'id' => '\d+'
    ])]
    #[Doc\Route(
        description: 'Get a recurring change by ID',
    )]
    public function getRecurringChange(Request $request): Response
    {
        return Search::getOneBySchema($this->getKnownSchema('RecurringChange'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/RecurringChange', methods: ['POST'])]
    #[Doc\Route(
        description: 'Create a recurring change',
    )]
    public function createRecurringChange(Request $request): Response
    {
        return Search::createBySchema($this->getKnownSchema('RecurringChange'), $request->getParameters(), [self::class, 'getRecurringChange']);
    }

    #[Route(path: '/RecurringChange/{id}', methods: ['PATCH'], requirements: [
        'id' => '\d+'
    ])]
    #[Doc\Route(
        description: 'Update a recurring change by ID',
    )]
    public function updateRecurringChange(Request $request): Response
    {
        return Search::updateBySchema($this->getKnownSchema('RecurringChange'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/RecurringChange/{id}', methods: ['DELETE'], requirements: [
        'id' => '\d+'
    ])]
    #[Doc\Route(
        description: 'Delete a recurring change by ID',
    )]
    public function deleteRecurringChange(Request $request): Response
    {
        return Search::deleteBySchema($this->getKnownSchema('RecurringChange'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/ExternalEvent', methods: ['GET'])]
    #[Doc\Route(
        description: 'List or search external evenst'
    )]
    public function searchExternalEvent(Request $request): Response
    {
        return Search::searchBySchema($this->getKnownSchema('ExternalEvent'), $request->getParameters());
    }

    #[Route(path: '/ExternalEvent/{id}', methods: ['GET'], requirements: [
        'id' => '\d+'
    ])]
    #[Doc\Route(
        description: 'Get a recurring change by ID',
    )]
    public function getExternalEvent(Request $request): Response
    {
        return Search::getOneBySchema($this->getKnownSchema('ExternalEvent'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/ExternalEvent', methods: ['POST'])]
    #[Doc\Route(
        description: 'Create an external event',
    )]
    public function createExternalEvent(Request $request): Response
    {
        return Search::createBySchema($this->getKnownSchema('ExternalEvent'), $request->getParameters(), [self::class, 'getExternalEvent']);
    }

    #[Route(path: '/ExternalEvent/{id}', methods: ['PATCH'], requirements: [
        'id' => '\d+'
    ])]
    #[Doc\Route(
        description: 'Update an external event by ID',
    )]
    public function updateExternalEvent(Request $request): Response
    {
        return Search::updateBySchema($this->getKnownSchema('ExternalEvent'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/ExternalEvent/{id}', methods: ['DELETE'], requirements: [
        'id' => '\d+'
    ])]
    #[Doc\Route(
        description: 'Delete an external event by ID',
    )]
    public function deleteExternalEvent(Request $request): Response
    {
        return Search::deleteBySchema($this->getKnownSchema('ExternalEvent'), $request->getAttributes(), $request->getParameters());
    }
}
