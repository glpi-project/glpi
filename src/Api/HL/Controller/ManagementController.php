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

use Appliance;
use AutoUpdateSystem;
use Budget;
use Certificate;
use Cluster;
use Contact;
use Contract;
use Database;
use Datacenter;
use Document;
use Document_Item;
use Domain;
use Entity;
use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\Middleware\ResultFormatterMiddleware;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\Search;
use Glpi\Http\JSONResponse;
use Glpi\Http\Request;
use Glpi\Http\Response;
use Group;
use Line;
use Location;
use Manufacturer;
use Network;
use SoftwareLicense;
use State;
use Supplier;
use User;

#[Route(path: '/Management', tags: ['Management'])]
final class ManagementController extends AbstractController
{
    use CRUDControllerTrait;

    /**
     * @param bool $schema_names_only If true, only the schema names are returned.
     * @return array<class-string<CommonDBTM>, string>
     */
    public static function getManagementTypes(bool $schema_names_only = true): array
    {
        static $management_types = null;

        if ($management_types === null) {
            $management_types = [
                Appliance::class => [
                    'schema_name' => 'Appliance',
                    'label' => Appliance::getTypeName(1)
                ],
                Budget::class => [
                    'schema_name' => 'Budget',
                    'label' => Budget::getTypeName(1)
                ],
                Certificate::class => [
                    'schema_name' => 'Certificate',
                    'label' => Certificate::getTypeName(1)
                ],
                Cluster::class => [
                    'schema_name' => 'Cluster',
                    'label' => Cluster::getTypeName(1)
                ],
                Contact::class => [
                    'schema_name' => 'Contact',
                    'label' => Contact::getTypeName(1)
                ],
                Contract::class => [
                    'schema_name' => 'Contract',
                    'label' => Contract::getTypeName(1)
                ],
                Database::class => [
                    'schema_name' => 'Database',
                    'label' => Database::getTypeName(1)
                ],
                Datacenter::class => [
                    'schema_name' => 'DataCenter',
                    'label' => Datacenter::getTypeName(1)
                ],
                Document::class => [
                    'schema_name' => 'Document',
                    'label' => Document::getTypeName(1)
                ],
                Domain::class => [
                    'schema_name' => 'Domain',
                    'label' => Domain::getTypeName(1)
                ],
                SoftwareLicense::class => [
                    'schema_name' => 'License',
                    'label' => SoftwareLicense::getTypeName(1)
                ],
                Line::class => [
                    'schema_name' => 'Line',
                    'label' => Line::getTypeName(1)
                ],
                Supplier::class => [
                    'schema_name' => 'Supplier',
                    'label' => Supplier::getTypeName(1)
                ],
            ];
        }
        return $schema_names_only ? array_column($management_types, 'schema_name') : $management_types;
    }

    protected static function getRawKnownSchemas(): array
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;
        $schemas = [];

        $management_types = self::getManagementTypes(false);

        foreach ($management_types as $m_class => $m_data) {
            $m_name = $m_data['schema_name'];
            $schemas[$m_name] = [
                'x-itemtype' => $m_class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'x-readonly' => true,
                    ],
                    'name' => ['type' => Doc\Schema::TYPE_STRING],
                ]
            ];

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

            if (in_array($m_class, $CFG_GLPI['linkuser_tech_types'], true)) {
                $schemas[$m_name]['properties']['user_tech'] = self::getDropdownTypeSchema(class: User::class, field: 'users_id_tech', full_schema: 'User');
            }
            if (in_array($m_class, $CFG_GLPI['linkgroup_tech_types'], true)) {
                $schemas[$m_name]['properties']['group_tech'] = self::getDropdownTypeSchema(class: Group::class, field: 'groups_id_tech', full_schema: 'Group');
            }
            if (in_array($m_class, $CFG_GLPI['linkuser_types'], true)) {
                $schemas[$m_name]['properties']['user'] = self::getDropdownTypeSchema(class: User::class, full_schema: 'User');
            }
            if (in_array($m_class, $CFG_GLPI['linkgroup_types'], true)) {
                $schemas[$m_name]['properties']['group'] = self::getDropdownTypeSchema(class: Group::class, full_schema: 'Group');
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
                $schemas[$m_name]['properties']['uuid'] = ['type' => Doc\Schema::TYPE_STRING];
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
            'x-mapper' => static function ($v) use ($CFG_GLPI) {
                return $CFG_GLPI["root_doc"] . "/front/document.send.php?docid=" . $v;
            }
        ];
        $schemas['Document']['properties']['mime'] = ['type' => Doc\Schema::TYPE_STRING];
        $schemas['Document']['properties']['sha1sum'] = ['type' => Doc\Schema::TYPE_STRING];
        $schemas['Document_Item'] = [
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => Document_Item::class,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'x-readonly' => true,
                ],
                'itemtype' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'x-readonly' => true,
                ],
                'items_id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'x-readonly' => true,
                ],
                'documents_id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'x-readonly' => true,
                ],
                'filepath' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'x-mapped-from' => 'documents_id',
                    'x-mapper' => static function ($v) use ($CFG_GLPI) {
                        return $CFG_GLPI["root_doc"] . "/front/document.send.php?docid=" . $v;
                    }
                ]
            ]
        ];

        return $schemas;
    }

    #[Route(path: '/', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'Get all available management types',
        methods: ['GET'],
        responses: [
            '200' => [
                'description' => 'List of management types',
                'schema' => [
                    'type' => Doc\Schema::TYPE_ARRAY,
                    'items' => [
                        'type' => Doc\Schema::TYPE_OBJECT,
                        'properties' => [
                            'itemtype' => ['type' => Doc\Schema::TYPE_STRING],
                            'name' => ['type' => Doc\Schema::TYPE_STRING],
                            'href' => ['type' => Doc\Schema::TYPE_STRING],
                        ],
                    ],
                ]
            ]
        ]
    )]
    public function index(Request $request): Response
    {
        $management_types = self::getManagementTypes(false);
        $asset_paths = [];
        foreach ($management_types as $m_class => $m_data) {
            $asset_paths[] = [
                'itemtype'  => $m_class,
                'name'      => $m_data['label'],
                'href'      => self::getAPIPathForRouteFunction(self::class, 'search', ['itemtype' => $m_data['schema_name']]),
            ];
        }
        return new JSONResponse($asset_paths);
    }

    #[Route(path: '/{itemtype}', methods: ['GET'], requirements: [
        'itemtype' => [self::class, 'getManagementTypes']
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'List or search management items',
        parameters: [self::PARAMETER_RSQL_FILTER, self::PARAMETER_START, self::PARAMETER_LIMIT],
        responses: [
            ['schema' => '{itemtype}[]']
        ]
    )]
    public function searchItems(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return Search::searchBySchema($this->getKnownSchema($itemtype), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['GET'], requirements: [
        'itemtype' => [self::class, 'getManagementTypes'],
        'id' => '\d+'
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'Get a management item by ID',
        responses: [
            ['schema' => '{itemtype}']
        ]
    )]
    public function getItem(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return Search::getOneBySchema($this->getKnownSchema($itemtype), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}', methods: ['POST'], requirements: [
        'itemtype' => [self::class, 'getManagementTypes']
    ])]
    #[Doc\Route(description: 'Create a new management item', parameters: [
        [
            'name' => '_',
            'location' => Doc\Parameter::LOCATION_BODY,
            'schema' => '{itemtype}',
        ]
    ])]
    public function createItem(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return Search::createBySchema($this->getKnownSchema($itemtype), $request->getParameters() + ['itemtype' => $itemtype], [self::class, 'getItem']);
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['PATCH'], requirements: [
        'itemtype' => [self::class, 'getManagementTypes'],
        'id' => '\d+'
    ])]
    #[Doc\Route(
        description: 'Update a management item by ID',
        parameters: [
            [
                'name' => '_',
                'location' => Doc\Parameter::LOCATION_BODY,
                'schema' => '{itemtype}',
            ]
        ],
        responses: [
            ['schema' => '{itemtype}']
        ]
    )]
    public function updateItem(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return Search::updateBySchema($this->getKnownSchema($itemtype), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['DELETE'], requirements: [
        'itemtype' => [self::class, 'getManagementTypes'],
        'id' => '\d+'
    ])]
    #[Doc\Route(description: 'Delete a management item by ID')]
    public function deleteItem(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return Search::deleteBySchema($this->getKnownSchema($itemtype), $request->getAttributes(), $request->getParameters());
    }
}
