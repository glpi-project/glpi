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

use AutoUpdateSystem;
use Budget;
use BusinessCriticity;
use Cluster;
use CommonDBTM;
use CommonITILObject;
use Contact;
use Contract;
use Database;
use DatabaseInstance;
use DatabaseInstanceCategory;
use DatabaseInstanceType;
use Datacenter;
use Document;
use Document_Item;
use Domain;
use Entity;
use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\Middleware\ResultFormatterMiddleware;
use Glpi\Api\HL\ResourceAccessor;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\RouteVersion;
use Glpi\Http\JSONResponse;
use Glpi\Http\Request;
use Glpi\Http\Response;
use Group_Item;
use Line;
use Location;
use LogicException;
use Manufacturer;
use Network;
use SoftwareLicense;
use State;
use Supplier;
use User;

#[Route(path: '/Management', tags: ['Management'])]
final class ManagementController extends AbstractController
{
    /**
     * @param bool $schema_names_only If true, only the schema names are returned.
     * @return array<class-string<CommonDBTM>, array>
     */
    public static function getManagementTypes(bool $schema_names_only = true): array
    {
        static $management_types = null;

        if ($management_types === null) {
            $management_types = [
                Budget::class => [
                    'schema_name' => 'Budget',
                    'label' => Budget::getTypeName(1),
                    'version_introduced' => '2.0',
                ],
                Cluster::class => [
                    'schema_name' => 'Cluster',
                    'label' => Cluster::getTypeName(1),
                    'version_introduced' => '2.0',
                ],
                Contact::class => [
                    'schema_name' => 'Contact',
                    'label' => Contact::getTypeName(1),
                    'version_introduced' => '2.0',
                ],
                Contract::class => [
                    'schema_name' => 'Contract',
                    'label' => Contract::getTypeName(1),
                    'version_introduced' => '2.0',
                ],
                Database::class => [
                    'schema_name' => 'Database',
                    'label' => Database::getTypeName(1),
                    'version_introduced' => '2.0',
                ],
                DatabaseInstance::class => [
                    'schema_name' => 'DatabaseInstance',
                    'label' => DatabaseInstance::getTypeName(1),
                    'version_introduced' => '2.2',
                ],
                Datacenter::class => [
                    'schema_name' => 'DataCenter',
                    'label' => Datacenter::getTypeName(1),
                    'version_introduced' => '2.0',
                ],
                Document::class => [
                    'schema_name' => 'Document',
                    'label' => Document::getTypeName(1),
                    'version_introduced' => '2.0',
                ],
                Domain::class => [
                    'schema_name' => 'Domain',
                    'label' => Domain::getTypeName(1),
                    'version_introduced' => '2.0',
                ],
                SoftwareLicense::class => [
                    'schema_name' => 'License',
                    'label' => SoftwareLicense::getTypeName(1),
                    'version_introduced' => '2.0',
                ],
                Line::class => [
                    'schema_name' => 'Line',
                    'label' => Line::getTypeName(1),
                    'version_introduced' => '2.0',
                ],
                Supplier::class => [
                    'schema_name' => 'Supplier',
                    'label' => Supplier::getTypeName(1),
                    'version_introduced' => '2.0',
                ],
            ];
        }
        return $schema_names_only ? array_column($management_types, 'schema_name') : $management_types;
    }

    /**
     * @return string[]
     */
    public static function getManagementEndpointTypes20(): array
    {
        return [
            'Budget', 'Cluster', 'Contact', 'Contract', 'Database', 'DataCenter', 'Document',
            'Domain', 'License', 'Line', 'Supplier',
        ];
    }

    /**
     * @return string[]
     */
    public static function getManagementEndpointTypes22(): array
    {
        return [
            'DatabaseInstance',
        ];
    }

    protected static function getRawKnownSchemas(): array
    {
        global $CFG_GLPI;
        $schemas = [];

        $management_types = self::getManagementTypes(false);
        $fn_get_assignable_restriction = static function (string $itemtype) {
            if (method_exists($itemtype, 'getAssignableVisiblityCriteria')) {
                $criteria = $itemtype::getAssignableVisiblityCriteria('_');
                if (count($criteria) === 1 && isset($criteria[0]) && is_numeric((string) $criteria[0])) {
                    // Return true for QueryExpression('1') and false for QueryExpression('0') to support fast pass/fail
                    return (bool) $criteria[0];
                }
                return ['WHERE' => $criteria];
            }
            return true;
        };

        foreach ($management_types as $m_class => $m_data) {
            if (!\is_a($m_class, CommonDBTM::class, true)) {
                throw new LogicException();
            }
            if ($m_class === DatabaseInstanceType::class) {
                continue;
            }

            $m_name = $m_data['schema_name'];
            $schemas[$m_name] = [
                'x-version-introduced' => $m_data['version_introduced'],
                'x-itemtype' => $m_class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'name' => ['type' => Doc\Schema::TYPE_STRING],
                ],
            ];

            if (method_exists($m_class, 'getAssignableVisiblityCriteria')) {
                $schemas[$m_name]['x-rights-conditions'] = [
                    'read' => static fn() => $fn_get_assignable_restriction($m_class),
                ];
            }

            // Need instance since some fields are not static even if they aren't related to instances
            $item = new $m_class();

            if ($item->isField('comment')) {
                $schemas[$m_name]['properties']['comment'] = ['type' => Doc\Schema::TYPE_STRING];
            }

            if (in_array($m_class, $CFG_GLPI['state_types'], true)) {
                $schemas[$m_name]['properties']['status'] = self::getDropdownTypeSchema(class: State::class, full_schema: 'State');
            }

            if (in_array($m_class, $CFG_GLPI['location_types'], true)) {
                $schemas[$m_name]['properties']['location'] = self::getDropdownTypeSchema(class: Location::class, full_schema: 'Location');
            }

            if ($item->isEntityAssign()) {
                $schemas[$m_name]['properties']['entity'] = self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity');
            }
            $schemas[$m_name]['properties']['date_creation'] = ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME];
            $schemas[$m_name]['properties']['date_mod'] = ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME];

            $type_class = $item->getTypeClass();
            if ($type_class !== null) {
                $schemas[$m_name]['properties']['type'] = self::getDropdownTypeSchema($type_class);
            }
            if ($item->isField('manufacturers_id')) {
                $schemas[$m_name]['properties']['manufacturer'] = self::getDropdownTypeSchema(class: Manufacturer::class, full_schema: 'Manufacturer');
            }
            $model_class = $item->getModelClass();
            if ($model_class !== null) {
                $schemas[$m_name]['properties']['model'] = self::getDropdownTypeSchema($model_class);
            }
            $env_class = $m_class . 'Environment';
            if (class_exists($env_class)) {
                $schemas[$m_name]['properties']['environment'] = self::getDropdownTypeSchema($env_class);
            }

            if (in_array($m_class, $CFG_GLPI['assignable_types'], true)) {
                $schemas[$m_name]['properties']['user_tech'] = self::getDropdownTypeSchema(class: User::class, field: 'users_id_tech', full_schema: 'User');

                $schemas[$m_name]['properties']['group_tech'] = [
                    'type' => Doc\Schema::TYPE_ARRAY,
                    'items' => [
                        'type' => Doc\Schema::TYPE_OBJECT,
                        'x-full-schema' => 'Group',
                        'x-join' => [
                            'table' => 'glpi_groups', // The table with the desired data
                            'fkey' => 'groups_id',
                            'field' => 'id',
                            'ref-join' => [
                                'table' => 'glpi_groups_items',
                                'fkey' => 'id',
                                'field' => 'items_id',
                                'condition' => [
                                    'itemtype' => $m_class,
                                    'type' => Group_Item::GROUP_TYPE_TECH,
                                ],
                            ],
                        ],
                        'properties' => [
                            'id' => [
                                'type' => Doc\Schema::TYPE_INTEGER,
                                'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                                'description' => 'ID',
                            ],
                            'name' => ['type' => Doc\Schema::TYPE_STRING],
                        ],
                    ],
                ];

                $schemas[$m_name]['properties']['user'] = self::getDropdownTypeSchema(class: User::class, full_schema: 'User');

                $schemas[$m_name]['properties']['group'] = [
                    'type' => Doc\Schema::TYPE_ARRAY,
                    'items' => [
                        'type' => Doc\Schema::TYPE_OBJECT,
                        'x-full-schema' => 'Group',
                        'x-join' => [
                            'table' => 'glpi_groups', // The table with the desired data
                            'fkey' => 'groups_id',
                            'field' => 'id',
                            'ref-join' => [
                                'table' => 'glpi_groups_items',
                                'fkey' => 'id',
                                'field' => 'items_id',
                                'condition' => [
                                    'itemtype' => $m_class,
                                    'type' => Group_Item::GROUP_TYPE_NORMAL,
                                ],
                            ],
                        ],
                        'properties' => [
                            'id' => [
                                'type' => Doc\Schema::TYPE_INTEGER,
                                'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                                'description' => 'ID',
                            ],
                            'name' => ['type' => Doc\Schema::TYPE_STRING],
                        ],
                    ],
                ];
            }

            if ($item->isField('contact')) {
                $schemas[$m_name]['properties']['contact'] = ['type' => Doc\Schema::TYPE_STRING];
            }
            if ($item->isField('contact_num')) {
                $schemas[$m_name]['properties']['contact_num'] = ['type' => Doc\Schema::TYPE_STRING];
            }
            if ($item->isField('serial')) {
                $schemas[$m_name]['properties']['serial'] = ['type' => Doc\Schema::TYPE_STRING];
            }
            if ($item->isField('otherserial')) {
                $schemas[$m_name]['properties']['otherserial'] = ['type' => Doc\Schema::TYPE_STRING];
            }
            if ($item->isField('networks_id')) {
                $schemas[$m_name]['properties']['network'] = self::getDropdownTypeSchema(Network::class);
            }

            if ($item->isField('uuid')) {
                $schemas[$m_name]['properties']['uuid'] = [
                    'type' => Doc\Schema::TYPE_STRING,
                    'pattern' => Doc\Schema::PATTERN_UUIDV4,
                    'readOnly' => true,
                ];
            }
            if ($item->isField('autoupdatesystems_id')) {
                $schemas[$m_name]['properties']['autoupdatesystem'] = self::getDropdownTypeSchema(AutoUpdateSystem::class);
            }

            if ($item->maybeDeleted()) {
                $schemas[$m_name]['properties']['is_deleted'] = ['type' => Doc\Schema::TYPE_BOOLEAN];
            }

            if ($m_class === Budget::class) {
                $schemas[$m_name]['properties']['value'] = ['type' => Doc\Schema::TYPE_NUMBER];
                $schemas[$m_name]['properties']['date_begin'] = [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_DATE,
                    'x-field' => 'begin_date',
                ];
                $schemas[$m_name]['properties']['date_end'] = [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_DATE,
                    'x-field' => 'end_date',
                ];
            }
        }

        $schemas['Document']['properties']['filename'] = ['type' => Doc\Schema::TYPE_STRING];
        $schemas['Document']['properties']['filepath'] = [
            'type' => Doc\Schema::TYPE_STRING,
            'x-mapped-from' => 'id',
            'x-mapper' => static fn($v) => $CFG_GLPI["root_doc"] . "/front/document.send.php?docid=" . $v,
        ];
        $schemas['Document']['properties']['mime'] = ['type' => Doc\Schema::TYPE_STRING];
        $schemas['Document']['properties']['sha1sum'] = ['type' => Doc\Schema::TYPE_STRING];
        $schemas['Document_Item'] = [
            'x-version-introduced' => '2.0',
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => Document_Item::class,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'itemtype' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'readOnly' => true,
                ],
                'items_id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'documents_id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'filepath' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'x-mapped-from' => 'documents_id',
                    'x-mapper' => static fn($v) => $CFG_GLPI["root_doc"] . "/front/document.send.php?docid=" . $v,
                ],
                'timeline_position' => [
                    'x-version-introduced' => '2.1.0',
                    'type' => Doc\Schema::TYPE_NUMBER,
                    'enum' => [
                        CommonITILObject::NO_TIMELINE,
                        CommonITILObject::TIMELINE_NOTSET,
                        CommonITILObject::TIMELINE_LEFT,
                        CommonITILObject::TIMELINE_MIDLEFT,
                        CommonITILObject::TIMELINE_MIDRIGHT,
                        CommonITILObject::TIMELINE_RIGHT,
                    ],
                    'description' => <<<EOT
                        The position in the timeline.
                        - 0: No timeline
                        - 1: Not set
                        - 2: Left
                        - 3: Mid left
                        - 4: Mid right
                        - 5: Right
                        EOT,
                ],
            ],
        ];

        // Post v2 additions
        $schemas['License']['properties']['is_recursive'] = [
            'x-version-introduced' => '2.1.0',
            'type' => Doc\Schema::TYPE_BOOLEAN,
            'readOnly' => true,
        ];
        $schemas['License']['properties']['completename'] = [
            'x-version-introduced' => '2.1.0',
            'type' => Doc\Schema::TYPE_STRING,
            'readOnly' => true,
        ];
        $schemas['License']['properties']['level'] = [
            'x-version-introduced' => '2.1.0',
            'type' => Doc\Schema::TYPE_INTEGER,
            'readOnly' => true,
        ];

        $schemas['Infocom'] = [
            'x-version-introduced' => '2.0',
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => 'Infocom',
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'itemtype' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'readOnly' => true,
                ],
                'items_id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'entity' => self::getDropdownTypeSchema(Entity::class, full_schema: 'Entity'),
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'date_buy' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_DATE,
                    'x-field' => 'buy_date',
                ],
                'date_use' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_DATE,
                    'x-field' => 'use_date',
                ],
                'date_order' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_DATE,
                    'x-field' => 'order_date',
                ],
                'date_delivery' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_DATE,
                    'x-field' => 'delivery_date',
                ],
                'date_inventory' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_DATE,
                    'x-field' => 'inventory_date',
                ],
                'date_warranty' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_DATE,
                    'x-field' => 'warranty_date',
                ],
                'date_decommission' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_DATE,
                    'x-field' => 'decommission_date',
                ],
                'warranty_info' => ['type' => Doc\Schema::TYPE_STRING],
                'warranty_value' => ['type' => Doc\Schema::TYPE_NUMBER, 'format' => Doc\Schema::FORMAT_NUMBER_FLOAT],
                'warranty_duration' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT32,
                    'description' => 'Warranty duration in months',
                ],
                'budget' => self::getDropdownTypeSchema(Budget::class),
                'supplier' => self::getDropdownTypeSchema(Supplier::class),
                'order_number' => ['type' => Doc\Schema::TYPE_STRING],
                'delivery_number' => ['type' => Doc\Schema::TYPE_STRING],
                'immo_number' => ['type' => Doc\Schema::TYPE_STRING],
                'invoice_number' => ['type' => Doc\Schema::TYPE_STRING, 'x-field' => 'bill'],
                'value' => ['type' => Doc\Schema::TYPE_NUMBER, 'format' => Doc\Schema::FORMAT_NUMBER_FLOAT],
                'amortization_type' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'enum' => [0, 1, 2],
                    'description' => <<<EOT
                        The amortization type:
                        - 0: No amortization
                        - 1: Decreasing
                        - 2: Linear
                        EOT,
                    'x-field' => 'sink_type',
                ],
                'amortization_time' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT32,
                    'description' => 'Amortization duration in years',
                    'x-field' => 'sink_time',
                ],
                'amortization_coeff' => [
                    'type' => Doc\Schema::TYPE_NUMBER,
                    'format' => Doc\Schema::FORMAT_NUMBER_FLOAT,
                    'description' => 'Amortization coefficient',
                    'x-field' => 'sink_coeff',
                ],
                'business_criticity' => self::getDropdownTypeSchema(BusinessCriticity::class),
            ],
        ];

        $schemas['DatabaseInstance'] = [
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => DatabaseInstance::class,
            'x-version-introduced' => '2.2',
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'entity' => self::getDropdownTypeSchema(Entity::class, full_schema: 'Entity'),
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'version' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'port' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 10], // Why is this a string instead of integer?
                'path' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'type' => self::getDropdownTypeSchema(class: DatabaseInstanceType::class, full_schema: 'DatabaseInstanceType'),
                'category' => self::getDropdownTypeSchema(class: DatabaseInstanceCategory::class, full_schema: 'DatabaseInstanceCategory'),
                'location' => self::getDropdownTypeSchema(class: Location::class, full_schema: 'Location'),
                'manufacturer' => self::getDropdownTypeSchema(class: Manufacturer::class, full_schema: 'Manufacturer'),
                'user' => self::getDropdownTypeSchema(class: User::class, full_schema: 'User'),
                'user_tech' => self::getDropdownTypeSchema(class: User::class, field: 'users_id_tech', full_schema: 'User'),
                'state' => self::getDropdownTypeSchema(class: State::class, full_schema: 'State'),
                'itemtype' => ['type' => Doc\Schema::TYPE_STRING],
                'items_id' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64],
                'is_onbackup' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'is_active' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'is_deleted' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'is_dynamic' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_lastboot' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_lastbackup' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'database' => [
                    'type' => Doc\Schema::TYPE_ARRAY,
                    'items' => [
                        'type' => Doc\Schema::TYPE_OBJECT,
                        'x-full-schema' => 'Database',
                        'x-join' => [
                            'table' => Database::getTable(), // The table with the desired data
                            'fkey' => 'id',
                            'field' => DatabaseInstance::getForeignKeyField(),
                            'primary-property' => 'id',
                        ],
                        'properties' => [
                            'id' => [
                                'type' => Doc\Schema::TYPE_INTEGER,
                                'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                                'readOnly' => true,
                            ],
                            'name' => ['type' => Doc\Schema::TYPE_STRING],
                        ],
                    ],
                ],
            ],
        ];
        $schemas['Database']['properties']['instance'] = self::getDropdownTypeSchema(class: DatabaseInstance::class, full_schema: 'DatabaseInstance') + [
            'x-version-introduced' => '2.2',
        ];

        return $schemas;
    }

    #[Route(path: '/', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Get all available management types',
        methods: ['GET'],
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
        $management_types = self::getManagementTypes(false);
        $v20_types = self::getManagementEndpointTypes20();
        $management_paths = [];
        foreach ($management_types as $m_class => $m_data) {
            $management_paths[] = [
                'itemtype'  => $m_class,
                'name'      => $m_data['label'],
                'href'      => self::getAPIPathForRouteFunction(
                    self::class,
                    in_array($m_data['schema_name'], $v20_types, true) ? 'search20' : 'search22',
                    ['itemtype' => $m_data['schema_name']]
                ),
            ];
        }
        return new JSONResponse($management_paths);
    }

    #[Route(path: '/Document/{id}/Download', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Download a document',
        parameters: [
            new Doc\Parameter(name: 'HTTP_IF_NONE_MATCH', schema: new Doc\Schema(type: Doc\Schema::TYPE_STRING), location: Doc\Parameter::LOCATION_HEADER),
            new Doc\Parameter(name: 'HTTP_IF_MODIFIED_SINCE', schema: new Doc\Schema(type: Doc\Schema::TYPE_STRING), location: Doc\Parameter::LOCATION_HEADER),
        ],
        responses: [
            new Doc\Response(schema: new Doc\SchemaReference('Document'), media_type: 'application/octet-stream'),
        ]
    )]
    public function downloadDocument(Request $request): Response
    {
        $document = new Document();
        if ($document->getFromDB($request->getAttribute('id'))) {
            if ($document->canViewFile()) {
                $symfony_response = $document->getAsResponse();
                return new Response($symfony_response->getStatusCode(), $symfony_response->headers->all(), $symfony_response->getContent());
            }
            return self::getAccessDeniedErrorResponse();
        }
        return self::getNotFoundErrorResponse();
    }

    #[Route(path: '/{itemtype}', methods: ['GET'], requirements: [
        'itemtype' => [self::class, 'getManagementEndpointTypes20'],
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(
        schema_name: '{itemtype}',
        description: 'List or search management items'
    )]
    public function searchItems20(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::searchBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['GET'], requirements: [
        'itemtype' => [self::class, 'getManagementEndpointTypes20'],
        'id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(
        schema_name: '{itemtype}',
        description: 'Get an existing management item'
    )]
    public function getItem20(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::getOneBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}', methods: ['POST'], requirements: [
        'itemtype' => [self::class, 'getManagementEndpointTypes20'],
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\CreateRoute(
        schema_name: '{itemtype}',
        description: 'Create a new management item'
    )]
    public function createItem20(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::createBySchema(
            $this->getKnownSchema($itemtype, $this->getAPIVersion($request)),
            $request->getParameters() + ['itemtype' => $itemtype],
            [self::class, 'getItem20']
        );
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['PATCH'], requirements: [
        'itemtype' => [self::class, 'getManagementEndpointTypes20'],
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\UpdateRoute(
        schema_name: '{itemtype}',
        description: 'Update an existing management item'
    )]
    public function updateItem20(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::updateBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['DELETE'], requirements: [
        'itemtype' => [self::class, 'getManagementEndpointTypes20'],
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\DeleteRoute(
        schema_name: '{itemtype}',
        description: 'Delete a management item'
    )]
    public function deleteItem20(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::deleteBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}', methods: ['GET'], requirements: [
        'itemtype' => [self::class, 'getManagementEndpointTypes22'],
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.2')]
    #[Doc\SearchRoute(
        schema_name: '{itemtype}',
        description: 'List or search management items'
    )]
    public function searchItems22(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::searchBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['GET'], requirements: [
        'itemtype' => [self::class, 'getManagementEndpointTypes22'],
        'id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.2')]
    #[Doc\GetRoute(
        schema_name: '{itemtype}',
        description: 'Get an existing management item'
    )]
    public function getItem22(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::getOneBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}', methods: ['POST'], requirements: [
        'itemtype' => [self::class, 'getManagementEndpointTypes22'],
    ])]
    #[RouteVersion(introduced: '2.2')]
    #[Doc\CreateRoute(
        schema_name: '{itemtype}',
        description: 'Create a new management item'
    )]
    public function createItem22(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::createBySchema(
            $this->getKnownSchema($itemtype, $this->getAPIVersion($request)),
            $request->getParameters() + ['itemtype' => $itemtype],
            [self::class, 'getItem22']
        );
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['PATCH'], requirements: [
        'itemtype' => [self::class, 'getManagementEndpointTypes22'],
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.2')]
    #[Doc\UpdateRoute(
        schema_name: '{itemtype}',
        description: 'Update an existing management item'
    )]
    public function updateItem22(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::updateBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['DELETE'], requirements: [
        'itemtype' => [self::class, 'getManagementEndpointTypes22'],
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.2')]
    #[Doc\DeleteRoute(
        schema_name: '{itemtype}',
        description: 'Delete a management item'
    )]
    public function deleteItem22(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::deleteBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }
}
