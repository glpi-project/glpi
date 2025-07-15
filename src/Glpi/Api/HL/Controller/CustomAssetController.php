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

namespace Glpi\Api\HL\Controller;

use Entity;
use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\Middleware\ResultFormatterMiddleware;
use Glpi\Api\HL\ResourceAccessor;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\RouteVersion;
use Glpi\Asset\Asset;
use Glpi\Asset\AssetDefinitionManager;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;
use Glpi\Http\JSONResponse;
use Glpi\Http\Request;
use Glpi\Http\Response;
use Group_Item;
use Location;
use Manufacturer;
use State;
use User;

#[Route(path: '/Assets/Custom', priority: 1, tags: ['Custom Assets'])]
#[Doc\Route(
    parameters: [
        [
            'name' => 'itemtype',
            'description' => 'Asset type',
            'location' => Doc\Parameter::LOCATION_PATH,
            'schema' => ['type' => Doc\Schema::TYPE_STRING],
        ],
        [
            'name' => 'id',
            'description' => 'The ID of the Asset',
            'location' => Doc\Parameter::LOCATION_PATH,
            'schema' => ['type' => Doc\Schema::TYPE_INTEGER],
        ],
    ]
)]
final class CustomAssetController extends AbstractController
{
    protected static function getRawKnownSchemas(): array
    {
        /** @var \DBmysql $DB */
        global $DB;

        $custom_assets = [];
        $definitions = AssetDefinitionManager::getInstance()->getDefinitions();

        foreach ($definitions as $definition) {
            $asset_class = $definition->getAssetClassName();
            $asset_system_name = $definition->fields['system_name'];
            $schema_name = 'CustomAsset_' . $asset_system_name;
            $custom_assets[$schema_name] = [
                'x-version-introduced' => '2.0',
                'x-itemtype' => $asset_class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'x-rights-conditions' => [
                    'read' => static fn() => [
                        'WHERE' => [
                            '_.assets_assetdefinitions_id' => $definition->getID(),
                        ],
                    ],
                ],
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'x-readonly' => true,
                    ],
                    'name' => ['type' => Doc\Schema::TYPE_STRING],
                    'comment' => ['type' => Doc\Schema::TYPE_STRING],
                    'serial' => ['type' => Doc\Schema::TYPE_STRING],
                    'otherserial' => ['type' => Doc\Schema::TYPE_STRING],
                    'contact' => ['type' => Doc\Schema::TYPE_STRING],
                    'contact_num' => ['type' => Doc\Schema::TYPE_STRING],
                    'user' => self::getDropdownTypeSchema(class: User::class, field: 'users_id', full_schema: 'User'),
                    'group' => [
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
                                        'itemtype' => $asset_class,
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
                    ],
                    'user_tech' => self::getDropdownTypeSchema(class: User::class, field: 'users_id_tech', full_schema: 'User'),
                    'group_tech' => [
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
                                        'itemtype' => $asset_class,
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
                    ],
                    'location' => self::getDropdownTypeSchema(class: Location::class, full_schema: 'Location'),
                    'manufacturer' => self::getDropdownTypeSchema(class: Manufacturer::class, full_schema: 'Manufacturer'),
                    'state' => self::getDropdownTypeSchema(class: State::class, full_schema: 'State'),
                    'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                    'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                    'is_deleted' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                    'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'custom_fields' => [
                        'type' => Doc\Schema::TYPE_OBJECT,
                        'x-readonly' => true,
                        'properties' => [],
                    ],
                ],
            ];

            $custom_fields = $definition->getCustomFieldDefinitions();
            foreach ($custom_fields as $field) {
                $field_name = $field->fields['system_name'];
                $default_value = is_array($field->fields['default_value']) ? exportArrayToDB($field->fields['default_value']) : $field->fields['default_value'];
                $custom_assets[$schema_name]['properties']['custom_fields']['properties'][$field_name] = [
                    'type' => Doc\Schema::TYPE_STRING,
                    'x-field' => "custom_$field_name",
                    'computation' =>  QueryFunction::coalesce([
                        QueryFunction::jsonUnquote(
                            expression: QueryFunction::jsonExtract([
                                '_.custom_fields',
                                new QueryExpression($DB::quoteValue('$."' . $field->fields['id'] . '"')),
                            ])
                        ),
                        new QueryExpression($DB::quoteValue($default_value)),
                    ]),
                ];
            }

            $asset_type_class = $definition->getAssetTypeClassName();
            $type_schema_name = 'CustomAsset_' . $asset_system_name . 'Type';
            $custom_assets[$type_schema_name] = [
                'x-version-introduced' => '2.0',
                'x-itemtype' => $asset_type_class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'x-readonly' => true,
                    ],
                    'name' => ['type' => Doc\Schema::TYPE_STRING],
                    'comment' => ['type' => Doc\Schema::TYPE_STRING],
                    'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                ],
            ];

            $asset_model_class = $definition->getAssetModelClassName();
            $model_schema_name = 'CustomAsset_' . $asset_system_name . 'Model';
            $custom_assets[$model_schema_name] = [
                'x-version-introduced' => '2.0',
                'x-itemtype' => $asset_model_class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'x-readonly' => true,
                    ],
                    'name' => ['type' => Doc\Schema::TYPE_STRING],
                    'comment' => ['type' => Doc\Schema::TYPE_STRING],
                    'product_number' => ['type' => Doc\Schema::TYPE_STRING],
                    'weight' => ['type' => Doc\Schema::TYPE_INTEGER],
                    'required_units' => ['type' => Doc\Schema::TYPE_INTEGER],
                    'depth' => ['type' => Doc\Schema::TYPE_NUMBER, 'format' => Doc\Schema::FORMAT_NUMBER_FLOAT],
                    'power_connections' => ['type' => Doc\Schema::TYPE_INTEGER],
                    'power_consumption' => ['type' => Doc\Schema::TYPE_INTEGER],
                    'is_half_rack' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                    'picture_front' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'x-mapped-from' => 'picture_front',
                        'x-mapper' => static fn($v) => \Toolbox::getPictureUrl($v, true) ?? '',
                    ],
                    'picture_rear' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'x-mapped-from' => 'picture_back',
                        'x-mapper' => static fn($v) => \Toolbox::getPictureUrl($v, true) ?? '',
                    ],
                    'pictures' => [
                        'type' => Doc\Schema::TYPE_ARRAY,
                        'items' => [
                            'type' => Doc\Schema::TYPE_STRING,
                            'x-mapped-from' => 'pictures',
                            'x-mapper' => static function ($v) {
                                $pictures = importArrayFromDB($v);
                                return array_map(static fn($picture) => \Toolbox::getPictureUrl($picture, true) ?? '', $pictures);
                            },
                        ],
                    ],
                    'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                ],
            ];
        }

        return $custom_assets;
    }

    /**
     * @param bool $types_only If true, only the type names are returned. If false, the type name => localized name pairs are returned.
     * @return array<class-string<Asset>, string>
     */
    public static function getCustomAssetTypes(bool $types_only = true): array
    {
        $assets = [];
        $definitions = AssetDefinitionManager::getInstance()->getDefinitions(true);
        foreach ($definitions as $definition) {
            $assets[$definition->fields['system_name']] = $definition->getAssetClassName()::getTypeName(1);
        }
        return $types_only ? array_keys($assets) : $assets;
    }

    #[Route(path: '/', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Get all available custom asset types',
        methods: ['GET'],
        responses: [
            '200' => [
                'description' => 'List of custom asset types',
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
                ],
            ],
        ]
    )]
    public function index(Request $request): Response
    {
        $asset_types = self::getCustomAssetTypes(false);
        $asset_paths = [];
        foreach ($asset_types as $asset_type => $asset_name) {
            $asset_paths[] = [
                'itemtype'  => $asset_type,
                'name'      => $asset_name,
                'href'      => self::getAPIPathForRouteFunction(self::class, 'search', ['itemtype' => $asset_type]),
            ];
        }
        return new JSONResponse($asset_paths);
    }

    #[RouteVersion(introduced: '2.0')]
    #[Route(path: '/{itemtype}', methods: ['GET'], requirements: [
        'itemtype' => [self::class, 'getCustomAssetTypes'],
    ], tags: ['Assets'], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'List or search custom assets of a specific type',
        parameters: [self::PARAMETER_RSQL_FILTER, self::PARAMETER_START, self::PARAMETER_LIMIT],
        responses: [
            ['schema' => 'CustomAsset_{itemtype}[]'],
        ]
    )]
    public function search(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::searchBySchema($this->getKnownSchema('CustomAsset_' . $itemtype, $this->getAPIVersion($request)), $request->getParameters());
    }

    #[RouteVersion(introduced: '2.0')]
    #[Route(path: '/{itemtype}/{id}', methods: ['GET'], requirements: [
        'itemtype' => [self::class, 'getCustomAssetTypes'],
        'id' => '\d+',
    ], tags: ['Assets'], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'Get a custom asset of a specific type by ID',
        responses: [
            ['schema' => 'CustomAsset_{itemtype}'],
        ]
    )]
    public function getItem(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::getOneBySchema($this->getKnownSchema('CustomAsset_' . $itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[RouteVersion(introduced: '2.0')]
    #[Route(path: '/{itemtype}', methods: ['POST'], requirements: [
        'itemtype' => [self::class, 'getCustomAssetTypes'],
    ], tags: ['Assets'])]
    #[Doc\Route(
        description: 'Create a custom asset of a specific type',
        parameters: [
            [
                'name' => '_',
                'location' => Doc\Parameter::LOCATION_BODY,
                'schema' => 'CustomAsset_{itemtype}',
            ],
        ]
    )]
    public function createItem(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::createBySchema($this->getKnownSchema('CustomAsset_' . $itemtype, $this->getAPIVersion($request)), $request->getParameters() + ['itemtype' => $itemtype], [self::class, 'getItem']);
    }

    #[RouteVersion(introduced: '2.0')]
    #[Route(path: '/{itemtype}/{id}', methods: ['PATCH'], requirements: [
        'itemtype' => [self::class, 'getCustomAssetTypes'],
        'id' => '\d+',
    ], tags: ['Assets'])]
    #[Doc\Route(
        description: 'Update a custom asset of a specific type by ID',
        parameters: [
            [
                'name' => '_',
                'location' => Doc\Parameter::LOCATION_BODY,
                'schema' => 'CustomAsset_{itemtype}',
            ],
        ]
    )]
    public function updateItem(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::updateBySchema($this->getKnownSchema('CustomAsset_' . $itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[RouteVersion(introduced: '2.0')]
    #[Route(path: '/{itemtype}/{id}', methods: ['DELETE'], requirements: [
        'itemtype' => [self::class, 'getCustomAssetTypes'],
        'id' => '\d+',
    ], tags: ['Assets'])]
    #[Doc\Route(
        description: 'Delete a custom asset of a specific type by ID',
    )]
    public function deleteItem(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::deleteBySchema($this->getKnownSchema('CustomAsset_' . $itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[RouteVersion(introduced: '2.0')]
    #[Route(path: '/{itemtype}Model', methods: ['GET'], requirements: [
        'itemtype' => [self::class, 'getCustomAssetTypes'],
    ], tags: ['Assets'], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'List or search custom asset models of a specific type',
        parameters: [self::PARAMETER_RSQL_FILTER, self::PARAMETER_START, self::PARAMETER_LIMIT],
        responses: [
            ['schema' => 'CustomAsset_{itemtype}Model[]'],
        ]
    )]
    public function searchModels(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::searchBySchema($this->getKnownSchema('CustomAsset_' . $itemtype, $this->getAPIVersion($request)), $request->getParameters());
    }

    #[RouteVersion(introduced: '2.0')]
    #[Route(path: '/{itemtype}Model/{id}', methods: ['GET'], requirements: [
        'itemtype' => [self::class, 'getCustomAssetTypes'],
        'id' => '\d+',
    ], tags: ['Assets'], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'Get a custom asset model of a specific type by ID',
        responses: [
            ['schema' => 'CustomAsset_{itemtype}Model'],
        ]
    )]
    public function getItemModel(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::getOneBySchema($this->getKnownSchema('CustomAsset_' . $itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[RouteVersion(introduced: '2.0')]
    #[Route(path: '/{itemtype}Model', methods: ['POST'], requirements: [
        'itemtype' => [self::class, 'getCustomAssetTypes'],
    ], tags: ['Assets'])]
    #[Doc\Route(
        description: 'Create a custom asset model of a specific type',
        parameters: [
            [
                'name' => '_',
                'location' => Doc\Parameter::LOCATION_BODY,
                'schema' => 'CustomAsset_{itemtype}Model',
            ],
        ]
    )]
    public function createItemModel(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::createBySchema($this->getKnownSchema('CustomAsset_' . $itemtype, $this->getAPIVersion($request)), $request->getParameters() + ['itemtype' => $itemtype], [self::class, 'getItem']);
    }

    #[RouteVersion(introduced: '2.0')]
    #[Route(path: '/{itemtype}Model/{id}', methods: ['PATCH'], requirements: [
        'itemtype' => [self::class, 'getCustomAssetTypes'],
        'id' => '\d+',
    ], tags: ['Assets'])]
    #[Doc\Route(
        description: 'Update a custom asset model of a specific type by ID',
        parameters: [
            [
                'name' => '_',
                'location' => Doc\Parameter::LOCATION_BODY,
                'schema' => 'CustomAsset_{itemtype}Model',
            ],
        ]
    )]
    public function updateItemModel(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::updateBySchema($this->getKnownSchema('CustomAsset_' . $itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[RouteVersion(introduced: '2.0')]
    #[Route(path: '/{itemtype}Model/{id}', methods: ['DELETE'], requirements: [
        'itemtype' => [self::class, 'getCustomAssetTypes'],
        'id' => '\d+',
    ], tags: ['Assets'])]
    #[Doc\Route(
        description: 'Delete a custom asset model of a specific type by ID',
    )]
    public function deleteItemModel(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::deleteBySchema($this->getKnownSchema('CustomAsset_' . $itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[RouteVersion(introduced: '2.0')]
    #[Route(path: '/{itemtype}Type', methods: ['GET'], requirements: [
        'itemtype' => [self::class, 'getCustomAssetTypes'],
    ], tags: ['Assets'], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'List or search custom asset models of a specific type',
        parameters: [self::PARAMETER_RSQL_FILTER, self::PARAMETER_START, self::PARAMETER_LIMIT],
        responses: [
            ['schema' => 'CustomAsset_{itemtype}Type[]'],
        ]
    )]
    public function searchTypes(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::searchBySchema($this->getKnownSchema('CustomAsset_' . $itemtype, $this->getAPIVersion($request)), $request->getParameters());
    }

    #[RouteVersion(introduced: '2.0')]
    #[Route(path: '/{itemtype}Type/{id}', methods: ['GET'], requirements: [
        'itemtype' => [self::class, 'getCustomAssetTypes'],
        'id' => '\d+',
    ], tags: ['Assets'], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'Get a custom asset type of a specific type by ID',
        responses: [
            ['schema' => 'CustomAsset_{itemtype}Type'],
        ]
    )]
    public function getItemType(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::getOneBySchema($this->getKnownSchema('CustomAsset_' . $itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[RouteVersion(introduced: '2.0')]
    #[Route(path: '/{itemtype}Type', methods: ['POST'], requirements: [
        'itemtype' => [self::class, 'getCustomAssetTypes'],
    ], tags: ['Assets'])]
    #[Doc\Route(
        description: 'Create a custom asset type of a specific type',
        parameters: [
            [
                'name' => '_',
                'location' => Doc\Parameter::LOCATION_BODY,
                'schema' => 'CustomAsset_{itemtype}Type',
            ],
        ]
    )]
    public function createItemType(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::createBySchema($this->getKnownSchema('CustomAsset_' . $itemtype, $this->getAPIVersion($request)), $request->getParameters() + ['itemtype' => $itemtype], [self::class, 'getItem']);
    }

    #[RouteVersion(introduced: '2.0')]
    #[Route(path: '/{itemtype}Type/{id}', methods: ['PATCH'], requirements: [
        'itemtype' => [self::class, 'getCustomAssetTypes'],
        'id' => '\d+',
    ], tags: ['Assets'])]
    #[Doc\Route(
        description: 'Update a custom asset type of a specific type by ID',
        parameters: [
            [
                'name' => '_',
                'location' => Doc\Parameter::LOCATION_BODY,
                'schema' => 'CustomAsset_{itemtype}Type',
            ],
        ]
    )]
    public function updateItemType(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::updateBySchema($this->getKnownSchema('CustomAsset_' . $itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[RouteVersion(introduced: '2.0')]
    #[Route(path: '/{itemtype}Type/{id}', methods: ['DELETE'], requirements: [
        'itemtype' => [self::class, 'getCustomAssetTypes'],
        'id' => '\d+',
    ], tags: ['Assets'])]
    #[Doc\Route(
        description: 'Delete a custom asset type of a specific type by ID',
    )]
    public function deleteItemType(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::deleteBySchema($this->getKnownSchema('CustomAsset_' . $itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }
}
