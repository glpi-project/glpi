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

use CommonDBTM;
use Entity;
use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\Middleware\ResultFormatterMiddleware;
use Glpi\Api\HL\ResourceAccessor;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\RouteVersion;
use Glpi\Http\JSONResponse;
use Glpi\Http\Request;
use Glpi\Http\Response;
use Planning;
use Reminder;
use Reservation;
use ReservationItem;
use RSSFeed;
use User;

#[Route(path: '/Tools', tags: ['Tools'])]
final class ToolController extends AbstractController
{
    public static function getRawKnownSchemas(): array
    {
        return [
            'Reminder' => [
                'x-version-introduced' => '2.1.0',
                'x-itemtype' => Reminder::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'uuid' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'pattern' => Doc\Schema::PATTERN_UUIDV4,
                        'readOnly' => true,
                    ],
                    'name' => ['type' => Doc\Schema::TYPE_STRING],
                    'text' => ['type' => Doc\Schema::TYPE_STRING],
                    'date' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'format' => Doc\Schema::FORMAT_STRING_DATE_TIME,
                    ],
                    'user' => self::getDropdownTypeSchema(class: User::class, full_schema: 'User'),
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
                    'is_planned' => ['type' => Doc\Schema::TYPE_BOOLEAN],
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
                    'date_view_begin' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'format' => Doc\Schema::FORMAT_STRING_DATE_TIME,
                        'x-field' => 'begin_view_date',
                    ],
                    'date_view_end' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'format' => Doc\Schema::FORMAT_STRING_DATE_TIME,
                        'x-field' => 'end_view_date',
                    ],
                    'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                ],
            ],
            'RSSFeed' => [
                'x-version-introduced' => '2.1.0',
                'x-itemtype' => RSSFeed::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'comment' => ['type' => Doc\Schema::TYPE_STRING],
                    'url' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'required' => true,
                    ],
                    'refresh_interval' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'description' => 'Refresh interval in seconds',
                        'x-field' => 'refresh_rate',
                        'min' => HOUR_TIMESTAMP,
                        'max' => DAY_TIMESTAMP,
                        'multipleOf' => HOUR_TIMESTAMP,
                    ],
                    'max_items' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'description' => 'Maximum number of items to fetch',
                    ],
                    'have_error' => [
                        'type' => Doc\Schema::TYPE_BOOLEAN,
                        'readOnly' => true,
                        'description' => 'Whether the last fetch had errors',
                    ],
                    'is_active' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                    'user' => self::getDropdownTypeSchema(class: User::class, full_schema: 'User'),
                    'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                ],
            ],
            'ReservableItem' => [
                'x-version-introduced' => '2.1.0',
                'x-itemtype' => ReservationItem::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'itemtype' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'The itemtype of the reservable item',
                    ],
                    'items_id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'description' => 'The ID of the reservable item',
                    ],
                    'comment' => ['type' => Doc\Schema::TYPE_STRING],
                    'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                    'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                    'is_active' => [
                        'type' => Doc\Schema::TYPE_BOOLEAN,
                        'description' => 'Whether the item is currently active for reservations',
                    ],
                ],
            ],
            'Reservation' => [
                'x-version-introduced' => '2.1.0',
                'x-itemtype' => Reservation::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'reservable_item' => [
                        'type' => Doc\Schema::TYPE_OBJECT,
                        'x-field' => 'reservationitems_id',
                        'x-itemtype' => ReservationItem::class,
                        'x-join' => [
                            'table' => ReservationItem::getTable(),
                            'fkey' => 'reservationitems_id',
                            'field' => 'id',
                        ],
                        'properties' => [
                            'id' => [
                                'type' => Doc\Schema::TYPE_INTEGER,
                                'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                                'readOnly' => true,
                            ],
                            'itemtype' => [
                                'type' => Doc\Schema::TYPE_STRING,
                                'description' => 'The itemtype of the reservable item',
                            ],
                            'items_id' => [
                                'type' => Doc\Schema::TYPE_INTEGER,
                                'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                                'description' => 'The ID of the reservable item',
                            ],
                        ],
                    ],
                    'comment' => ['type' => Doc\Schema::TYPE_STRING],
                    'user' => self::getDropdownTypeSchema(class: User::class, full_schema: 'User'),
                    'group' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'description' => 'A random number used to identify reservations that are part of the same series (recurring reservations)',
                    ],
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
                ],
            ],
        ];
    }

    /**
     * @param bool $types_only If true, only the type names are returned. If false, the type name => localized name pairs are returned.
     * @return array<class-string<CommonDBTM>, string>
     */
    public static function getToolTypes(bool $types_only = true): array
    {
        static $types = null;

        if ($types === null) {
            $types = [
                'Reminder' => Reminder::getTypeName(1),
                'RSSFeed'  => RSSFeed::getTypeName(1),
            ];
        }
        return $types_only ? array_keys($types) : $types;
    }

    #[Route(path: '/', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.1')]
    #[Doc\Route(
        description: 'Get all available tool types',
        responses: [
            new Doc\Response(new Doc\Schema(
                type: Doc\Schema::TYPE_ARRAY,
                items: new Doc\Schema(
                    type: Doc\Schema::TYPE_OBJECT,
                    properties: [
                        'itemtype' => new Doc\Schema(Doc\Schema::TYPE_STRING),
                        'name' => new Doc\Schema(Doc\Schema::TYPE_STRING),
                        'href' => new Doc\Schema(Doc\Schema::TYPE_STRING),
                    ]
                )
            )),
        ]
    )]
    public function index(Request $request): Response
    {
        $tool_types = self::getToolTypes(false);
        $tool_paths = [];
        foreach ($tool_types as $tool_type => $tool_name) {
            $tool_paths[] = [
                'itemtype'  => $tool_type,
                'name'      => $tool_name,
                'href'      => self::getAPIPathForRouteFunction(self::class, 'search', ['itemtype' => $tool_type]),
            ];
        }
        return new JSONResponse($tool_paths);
    }

    #[Route(path: '/{itemtype}', methods: ['GET'], requirements: [
        'itemtype' => [self::class, 'getToolTypes'],
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.1')]
    #[Doc\SearchRoute(schema_name: '{itemtype}')]
    public function search(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::searchBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['GET'], requirements: [
        'itemtype' => [self::class, 'getToolTypes'],
        'id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.1')]
    #[Doc\GetRoute(schema_name: '{itemtype}')]
    public function getItem(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::getOneBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}', methods: ['POST'], requirements: [
        'itemtype' => [self::class, 'getToolTypes'],
    ])]
    #[RouteVersion(introduced: '2.1')]
    #[Doc\CreateRoute(schema_name: '{itemtype}')]
    public function createItem(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::createBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getParameters() + ['itemtype' => $itemtype], [self::class, 'getItem']);
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['PATCH'], requirements: [
        'itemtype' => [self::class, 'getToolTypes'],
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.1')]
    #[Doc\UpdateRoute(schema_name: '{itemtype}')]
    public function updateItem(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::updateBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['DELETE'], requirements: [
        'itemtype' => [self::class, 'getToolTypes'],
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.1')]
    #[Doc\DeleteRoute(schema_name: '{itemtype}')]
    public function deleteItem(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::deleteBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }
}
