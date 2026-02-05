<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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
use Config;
use Entity;
use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\Middleware\ResultFormatterMiddleware;
use Glpi\Api\HL\ResourceAccessor;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\RouteVersion;
use Glpi\Http\JSONResponse;
use Glpi\Http\Request;
use Glpi\Http\Response;
use Link;
use Link_Itemtype;
use ManualLink;
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
        global $DB;

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
                'x-field' => 'definition_time',
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
            ],
            'operator' => [
                'type' => Doc\Schema::TYPE_STRING,
                'enum' => ['AND', 'OR'],
                'x-field' => 'match',
            ],
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
            'Config' => [
                'x-version-introduced' => '2.1',
                'x-itemtype' => Config::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'context' => ['type' => Doc\Schema::TYPE_STRING],
                    'name' => ['type' => Doc\Schema::TYPE_STRING],
                    'value' => ['type' => Doc\Schema::TYPE_STRING],
                ],
                'x-rights-conditions' => [
                    'read' => static function () use ($DB) {
                        // Make a SQL request to get all config items so we can check which are undisclosed
                        // We are using safe IDs rather than undisclosed IDs to avoid issues with concurrent modifications
                        // We cannot reliably lock the table due to the fact that the DB connection here may differ from the one used to perform the actual read in the Search code
                        $disclosed_ids = [];

                        $it = $DB->request([
                            'SELECT' => ['id', 'context', 'name'],
                            'FROM'   => 'glpi_configs',
                        ]);
                        $test_configs = [];
                        foreach ($it as $row) {
                            $test_configs[] = $row + ['value' => 'dummy'];
                        }
                        foreach ($test_configs as $f) {
                            if (!self::isUndisclosedConfig($f['context'], $f['name'])) {
                                $disclosed_ids[] = $f['id'];
                            }
                        }
                        return ['WHERE' => ['_.id' => $disclosed_ids]];
                    },
                ],
            ],
            'SLM' => [
                'x-version-introduced' => '2.1.0',
                'x-itemtype' => SLM::class,
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
            'ExternalLink' => [
                'x-version-introduced' => '2.3',
                'x-itemtype' => Link::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'itemtype' => [
                        'type' => Doc\Schema::TYPE_ARRAY,
                        'items' => [
                            'type' => Doc\Schema::TYPE_STRING,
                            'x-field' => 'itemtype',
                            'x-join' => [
                                'table' => Link_Itemtype::getTable(),
                                'fkey' => 'id',
                                'field' => Link::getForeignKeyField(),
                                'primary-property' => 'id',
                            ],
                        ],
                    ],
                    'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                    'link' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                    'data' => ['type' => Doc\Schema::TYPE_STRING],
                    'open_window' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => true],
                    'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                    'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                    'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                ],
            ],
            'ManualLink' => [
                'x-version-introduced' => '2.3',
                'x-itemtype' => ManualLink::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'itemtype' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                    'items_id' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64],
                    'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                    'url' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 8096],
                    'open_window' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => true],
                    'icon' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                    'comment' => ['type' => Doc\Schema::TYPE_STRING],
                    'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                ],
            ],
        ];
    }

    /**
     * @param bool $types_only If true, only the type names are returned. If false, the type name => localized name pairs are returned.
     * @return array<string, array{itemtype: class-string<CommonDBTM>, label: string}>
     */
    public static function getSetupTypes(bool $types_only = true): array
    {
        static $types = null;

        if ($types === null) {
            $types = [
                'LDAPDirectory' => [
                    'itemtype' => AuthLDAP::class,
                    'label' => AuthLDAP::getTypeName(1),
                ],
                // Do not add Config here as it is handled specially
                'SLM' => [
                    'itemtype' => SLM::class,
                    'label' => SLM::getTypeName(1),
                ],
                'SLA' => [
                    'itemtype' => SLA::class,
                    'label' => SLA::getTypeName(1),
                ],
                'OLA' => [
                    'itemtype' => OLA::class,
                    'label' => OLA::getTypeName(1),
                ],
                'SLALevel' => [
                    'itemtype' => SlaLevel::class,
                    'label' => SlaLevel::getTypeName(1),
                ],
                'OLALevel' => [
                    'itemtype' => OlaLevel::class,
                    'label' => OlaLevel::getTypeName(1),
                ],
                'ExternalLink' => [
                    'itemtype' => Link::class,
                    'label' => Link::getTypeName(1),
                ],
                'ManualLink' => [
                    'itemtype' => ManualLink::class,
                    'label' => ManualLink::getTypeName(1),
                ],
            ];
        }
        return $types_only ? array_keys($types) : $types;
    }

    /**
     * @return string[]
     */
    public static function getSetupEndpointTypes20()
    {
        return ['LDAPDirectory'];
    }

    /**
     * @return string[]
     */
    public static function getSetupEndpointTypes23()
    {
        return ['SLM', 'SLA', 'OLA', 'SLALevel', 'OLALevel', 'ExternalLink', 'ManualLink'];
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
        $v20_types = self::getSetupEndpointTypes20();
        $setup_paths = [];
        foreach ($setup_types as $setup_type => $setup_data) {
            $setup_paths[] = [
                'itemtype'  => $setup_data['itemtype'],
                'name'      => $setup_data['label'],
                'href'      => self::getAPIPathForRouteFunction(self::class, in_array($setup_type, $v20_types, true) ? 'search20' : 'search23', ['itemtype' => $setup_type]),
            ];
        }
        return new JSONResponse($setup_paths);
    }

    #[Route(path: '/{itemtype}', methods: ['GET'], requirements: [
        'itemtype' => [self::class, 'getSetupEndpointTypes20'],
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(schema_name: '{itemtype}')]
    public function search20(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::searchBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['GET'], requirements: [
        'itemtype' => [self::class, 'getSetupEndpointTypes20'],
        'id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(schema_name: '{itemtype}')]
    public function getItem20(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::getOneBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}', methods: ['POST'], requirements: [
        'itemtype' => [self::class, 'getSetupEndpointTypes20'],
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\CreateRoute(schema_name: '{itemtype}')]
    public function createItem20(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::createBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getParameters() + ['itemtype' => $itemtype], [self::class, 'getItem20']);
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['PATCH'], requirements: [
        'itemtype' => [self::class, 'getSetupEndpointTypes20'],
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\UpdateRoute(schema_name: '{itemtype}')]
    public function updateItem20(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::updateBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['DELETE'], requirements: [
        'itemtype' => [self::class, 'getSetupEndpointTypes20'],
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\DeleteRoute(schema_name: '{itemtype}')]
    public function deleteItem20(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::deleteBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    private static function isUndisclosedConfig(string $context, string $name): bool
    {
        $f = ['context' => $context, 'name' => $name, 'value' => 'dummy'];
        Config::unsetUndisclosedFields($f);
        return !array_key_exists('value', $f);
    }

    #[Route(path: '/Config/{context}/{name}', methods: ['PATCH'], requirements: [
        'context' => '\w+',
        'name' => '\w+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.1')]
    #[Doc\UpdateRoute(schema_name: 'Config')]
    public function setConfigValue(Request $request): Response
    {
        // Skip using ResourceAccessor given the particularities of Config
        if (!Config::canUpdate()) {
            return AbstractController::getAccessDeniedErrorResponse();
        }
        $context = $request->getAttribute('context');
        $name = $request->getAttribute('name');
        $value = $request->getParameter('value');
        Config::setConfigurationValues($context, [$name => $value]);
        // Return the updated config
        if (self::isUndisclosedConfig($context, $name)) {
            // If the field is undisclosed, only return a 204 to indicate success without revealing the value
            return new JSONResponse(null, 204);
        }
        return new JSONResponse([
            'context' => $context,
            'name'    => $name,
            'value'   => Config::getConfigurationValue($context, $name),
        ]);
    }

    #[Route(path: '/Config', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.1')]
    #[Doc\SearchRoute(schema_name: 'Config')]
    public function searchConfigValues(Request $request): Response
    {
        return ResourceAccessor::searchBySchema($this->getKnownSchema('Config', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/Config/{context}', methods: ['GET'], requirements: [
        'context' => '\w+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.1')]
    #[Doc\SearchRoute(schema_name: 'Config')]
    public function searchConfigValuesByContext(Request $request): Response
    {
        $filters = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filters .= ';context==' . $request->getAttribute('context');
        $request->setParameter('filter', $filters);
        return ResourceAccessor::searchBySchema($this->getKnownSchema('Config', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/Config/{context}/{name}', methods: ['GET'], requirements: [
        'context' => '\w+',
        'name' => '\w+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.1')]
    #[Doc\GetRoute(schema_name: 'Config')]
    public function getConfigValue(Request $request): Response
    {
        // Skip using ResourceAccessor given the particularities of Config
        $context = $request->getAttribute('context');
        $name = $request->getAttribute('name');
        $config = new Config();
        if (!$config->getFromDBByCrit(['context' => $context, 'name'    => $name,])) {
            return AbstractController::getNotFoundErrorResponse();
        }
        if (self::isUndisclosedConfig($context, $name) || !$config->can($config->getID(), READ)) {
            return AbstractController::getAccessDeniedErrorResponse();
        }
        return new JSONResponse([
            'context' => $context,
            'name'    => $name,
            'value'   => Config::getConfigurationValue($context, $name),
        ]);
    }

    #[Route(path: '/Config/{context}/{name}', methods: ['DELETE'], requirements: [
        'context' => '\w+',
        'name' => '\w+',
    ])]
    #[RouteVersion(introduced: '2.1')]
    #[Doc\DeleteRoute(schema_name: 'Config')]
    public function deleteConfigValue(Request $request): Response
    {
        // Skip using ResourceAccessor given the particularities of Config
        if (!Config::canUpdate()) {
            return AbstractController::getAccessDeniedErrorResponse();
        }
        $context = $request->getAttribute('context');
        $name = $request->getAttribute('name');
        $config = new Config();
        if (!$config->getFromDBByCrit(['context' => $context, 'name' => $name])) {
            return AbstractController::getNotFoundErrorResponse();
        }
        Config::deleteConfigurationValues($context, [$name]);
        return new JSONResponse(null, 204);
    }

    #[Route(path: '/{itemtype}', methods: ['GET'], requirements: [
        'itemtype' => [self::class, 'getSetupEndpointTypes23'],
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\SearchRoute(schema_name: '{itemtype}')]
    public function search23(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::searchBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['GET'], requirements: [
        'itemtype' => [self::class, 'getSetupEndpointTypes23'],
        'id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\GetRoute(schema_name: '{itemtype}')]
    public function getItem23(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::getOneBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}', methods: ['POST'], requirements: [
        'itemtype' => [self::class, 'getSetupEndpointTypes23'],
    ])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\CreateRoute(schema_name: '{itemtype}')]
    public function createItem23(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::createBySchema(
            $this->getKnownSchema($itemtype, $this->getAPIVersion($request)),
            $request->getParameters() + ['itemtype' => $itemtype],
            [self::class, 'getItem23'],
            [
                'mapped' => [
                    'itemtype' => $request->getAttribute('itemtype'),
                ],
            ],
        );
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['PATCH'], requirements: [
        'itemtype' => [self::class, 'getSetupEndpointTypes23'],
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\UpdateRoute(schema_name: '{itemtype}')]
    public function updateItem23(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::updateBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['DELETE'], requirements: [
        'itemtype' => [self::class, 'getSetupEndpointTypes23'],
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\DeleteRoute(schema_name: '{itemtype}')]
    public function deleteItem23(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::deleteBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }
}
