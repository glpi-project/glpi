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

use AuthLDAP;
use Calendar;
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
use OLA;
use OlaLevel;
use SLA;
use SlaLevel;
use SLM;

#[Route(path: '/Setup', tags: ['Setup'])]
final class SetupController extends AbstractController
{
    public static function getRawKnownSchemas(): array
    {
        $base_la_properties = [
            'id' => [
                'type' => Doc\Schema::TYPE_INTEGER,
                'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                'readOnly' => true,
            ],
            'name' => ['type' => Doc\Schema::TYPE_STRING],
            'slm' => self::getDropdownTypeSchema(class: SLM::class, full_schema: 'SLM'),
            'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
            'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN],
            'type' => [
                'type' => Doc\Schema::TYPE_INTEGER,
                'enum' => [SLM::TTR, SLM::TTO],
                'description' => <<<EOT
                - 0: Time to resolve (TTR)
                - 1: Time to own (TTO)
EOT,
            ],
            'comment' => ['type' => Doc\Schema::TYPE_STRING],
            'time' => [
                'type' => Doc\Schema::TYPE_INTEGER,
                'x-field' => 'number_time',
                'description' => 'Time in the unit defined by the time_unit property',
            ],
            'time_unit' => [
                'type' => Doc\Schema::TYPE_STRING,
                'enum' => ['minute', 'hour', 'day', 'month'],
                'description' => 'Unit of time for the time property',
            ],
            'use_ticket_calendar' => ['type' => Doc\Schema::TYPE_BOOLEAN],
            'calendar' => self::getDropdownTypeSchema(class: Calendar::class, full_schema: 'Calendar'),
            'end_of_working_day' => [
                'type' => Doc\Schema::TYPE_BOOLEAN,
                'description' => 'Whether the time computation will target the end of the working day',
            ],
            'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
        ];

        $base_la_level_properties = [
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
            'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
            'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN],
            'execution_time' => [
                'type' => Doc\Schema::TYPE_INTEGER,
                'readOnly' => true,
            ],
            'operator' => [
                'type' => Doc\Schema::TYPE_STRING,
                'enum' => ['AND', 'OR'],
                'x-field' => 'match',
            ]
        ];

        return [
            'LDAPDirectory' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => AuthLDAP::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'name' => ['type' => Doc\Schema::TYPE_STRING],
                    'host' => ['type' => Doc\Schema::TYPE_STRING],
                    'port' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'min' => 1,
                        'max' => 65535,
                    ],
                    'is_default' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                    'is_active' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                    'comment' => ['type' => Doc\Schema::TYPE_STRING],
                    'connection_filter' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'x-field' => 'condition',
                        'description' => 'LDAP filter to restrict the search for users',
                    ],
                    'basedn' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'The distinguished name in the directory from which the searches will be made',
                    ],
                    'use_bind' => [
                        'type' => Doc\Schema::TYPE_BOOLEAN,
                        'description' => 'Whether to bind to the directory with a specific user or anonymously',
                    ],
                    'rootdn' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'The distinguished name of the user to bind to the directory',
                    ],
                    'rootdn_password' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'writeOnly' => true,
                        'description' => 'The password of the user to bind to the directory',
                    ],
                    'login_field' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'The attribute corresponding to the login',
                    ],
                    'sync_field' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'The attribute to use to uniquely identify a user (usually objectguid or employeeuid)',
                    ],
                ],
            ],
            'SLM' => [
                'x-version-introduced' => '2.1.0',
                'x-itemtype' => AuthLDAP::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'name' => ['type' => Doc\Schema::TYPE_STRING],
                    'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                    'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                    'comment' => ['type' => Doc\Schema::TYPE_STRING],
                    'use_ticket_calendar' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                    'calendar' => self::getDropdownTypeSchema(class: Calendar::class, full_schema: 'Calendar'),
                    'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                ],
            ],
            'SLA' => [
                'x-version-introduced' => '2.1.0',
                'x-itemtype' => SLA::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $base_la_properties,
            ],
            'OLA' => [
                'x-version-introduced' => '2.1.0',
                'x-itemtype' => OLA::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $base_la_properties,
            ],
            'SLALevel' => [
                'x-version-introduced' => '2.1.0',
                'x-itemtype' => SlaLevel::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $base_la_level_properties + [
                    'sla' => self::getDropdownTypeSchema(class: SLA::class, full_schema: 'SLA'),
                ],
            ],
            'OLALevel' => [
                'x-version-introduced' => '2.1.0',
                'x-itemtype' => OlaLevel::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $base_la_level_properties + [
                    'ola' => self::getDropdownTypeSchema(class: OLA::class, full_schema: 'OLA'),
                ],
            ],
        ];
    }

    /**
     * @param bool $types_only If true, only the type names are returned. If false, the type name => localized name pairs are returned.
     * @return array<class-string<CommonDBTM>, string>
     */
    public static function getSetupTypes(bool $types_only = true): array
    {
        static $types = null;

        if ($types === null) {
            $types = [
                'LDAPDirectory' => AuthLDAP::getTypeName(1),
            ];
        }
        return $types_only ? array_keys($types) : $types;
    }

    #[Route(path: '/', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Get all available setup types',
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
        $setup_types = self::getSetupTypes(false);
        $setup_paths = [];
        foreach ($setup_types as $setup_type => $setup_name) {
            $setup_paths[] = [
                'itemtype'  => $setup_type,
                'name'      => $setup_name,
                'href'      => self::getAPIPathForRouteFunction(self::class, 'search', ['itemtype' => $setup_type]),
            ];
        }
        return new JSONResponse($setup_paths);
    }

    #[Route(path: '/{itemtype}', methods: ['GET'], requirements: [
        'itemtype' => [self::class, 'getSetupTypes'],
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(schema_name: '{itemtype}')]
    public function search(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::searchBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['GET'], requirements: [
        'itemtype' => [self::class, 'getSetupTypes'],
        'id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(schema_name: '{itemtype}')]
    public function getItem(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::getOneBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}', methods: ['POST'], requirements: [
        'itemtype' => [self::class, 'getSetupTypes'],
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\CreateRoute(schema_name: '{itemtype}')]
    public function createItem(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::createBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getParameters() + ['itemtype' => $itemtype], [self::class, 'getItem']);
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['PATCH'], requirements: [
        'itemtype' => [self::class, 'getSetupTypes'],
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\UpdateRoute(schema_name: '{itemtype}')]
    public function updateItem(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::updateBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['DELETE'], requirements: [
        'itemtype' => [self::class, 'getSetupTypes'],
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\DeleteRoute(schema_name: '{itemtype}')]
    public function deleteItem(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::deleteBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }
}
