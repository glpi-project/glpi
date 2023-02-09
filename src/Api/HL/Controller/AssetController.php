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

use AutoUpdateSystem;
use CommonDBTM;
use Computer;
use Entity;
use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\Route;
use Glpi\Http\JSONResponse;
use Glpi\Http\Request;
use Glpi\Http\Response;
use Group;
use Location;
use Manufacturer;
use Network;
use State;
use User;

#[Route(path: '/Assets', priority: 1, tags: ['Assets'])]
#[Doc\Route(
    parameters: [
        [
            'name' => 'itemtype',
            'description' => 'Asset type',
            'location' => Doc\Parameter::LOCATION_PATH,
            'schema' => ['type' => Doc\Schema::TYPE_STRING]
        ],
        [
            'name' => 'id',
            'description' => 'The ID of the Asset',
            'location' => Doc\Parameter::LOCATION_PATH,
            'schema' => ['type' => Doc\Schema::TYPE_INTEGER]
        ]
    ]
)]
final class AssetController extends AbstractController
{
    use CRUDControllerTrait;

    public static function getRawKnownSchemas(): array
    {
        global $CFG_GLPI;
        $schemas = [];

        $schemas['_BaseAsset'] = [
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
            ]
        ];

        $asset_types = self::getAssetTypes();

        foreach ($asset_types as $asset_type) {
            if (!is_subclass_of($asset_type, CommonDBTM::class)) {
                continue;
            }
            // Replace namespace separator with underscore
            $schema_name = str_replace('\\', '_', $asset_type);
            $schemas[$schema_name] = $schemas['_BaseAsset'];
            $schemas[$schema_name]['x-itemtype'] = $asset_type;

            // Need instance since some fields are not static even if they aren't related to instances
            $asset = new $asset_type();

            if (in_array($asset_type, $CFG_GLPI['state_types'], true)) {
                $schemas[$schema_name]['properties']['status'] = self::getDropdownTypeSchema(State::class);
            }

            if (in_array($asset_type, $CFG_GLPI['location_types'], true)) {
                $schemas[$schema_name]['properties']['location'] = self::getDropdownTypeSchema(Location::class);
            }

            if ($asset->isEntityAssign()) {
                $schemas[$schema_name]['properties']['entity'] = self::getDropdownTypeSchema(Entity::class);
                // Add completename field
                $schemas[$schema_name]['properties']['entity']['properties']['completename'] = ['type' => Doc\Schema::TYPE_STRING];
                $schemas[$schema_name]['properties']['is_recursive'] = ['type' => Doc\Schema::TYPE_BOOLEAN];
            }

            $type_class = $asset->getTypeClass();
            if ($type_class !== null) {
                $schemas[$schema_name]['properties']['type'] = self::getDropdownTypeSchema($type_class);
            }
            if ($asset->isField('manufacturers_id')) {
                $schemas[$schema_name]['properties']['manufacturer'] = self::getDropdownTypeSchema(Manufacturer::class);
            }
            $model_class = $asset->getModelClass();
            if ($model_class !== null) {
                $schemas[$schema_name]['properties']['model'] = self::getDropdownTypeSchema($model_class);
            }

            if (in_array($asset_type, $CFG_GLPI['linkuser_tech_types'], true)) {
                $schemas[$schema_name]['properties']['user_tech'] = self::getDropdownTypeSchema(User::class, 'users_id_tech');
            }
            if (in_array($asset_type, $CFG_GLPI['linkgroup_tech_types'], true)) {
                $schemas[$schema_name]['properties']['group_tech'] = self::getDropdownTypeSchema(Group::class, 'groups_id_tech');
            }
            if (in_array($asset_type, $CFG_GLPI['linkuser_types'], true)) {
                $schemas[$schema_name]['properties']['user'] = self::getDropdownTypeSchema(User::class, 'users_id');
            }
            if (in_array($asset_type, $CFG_GLPI['linkgroup_types'], true)) {
                $schemas[$schema_name]['properties']['group'] = self::getDropdownTypeSchema(Group::class, 'groups_id');
            }

            if ($asset->isField('contact')) {
                $schemas[$schema_name]['properties']['contact'] = ['type' => Doc\Schema::TYPE_STRING];
            }
            if ($asset->isField('contact_num')) {
                $schemas[$schema_name]['properties']['contact_num'] = ['type' => Doc\Schema::TYPE_STRING];
            }
            if ($asset->isField('serial')) {
                $schemas[$schema_name]['properties']['serial'] = ['type' => Doc\Schema::TYPE_STRING];
            }
            if ($asset->isField('otherserial')) {
                $schemas[$schema_name]['properties']['otherserial'] = ['type' => Doc\Schema::TYPE_STRING];
            }
            if ($asset->isField('networks_id')) {
                $schemas[$schema_name]['properties']['network'] = self::getDropdownTypeSchema(Network::class);
            }

            if ($asset->isField('uuid')) {
                $schemas[$schema_name]['properties']['uuid'] = ['type' => Doc\Schema::TYPE_STRING];
            }
            if ($asset->isField('autoupdatesystems_id')) {
                $schemas[$schema_name]['properties']['autoupdatesystem'] = self::getDropdownTypeSchema(AutoUpdateSystem::class);
            }

            if ($asset->maybeDeleted()) {
                $schemas[$schema_name]['properties']['is_deleted'] = ['type' => Doc\Schema::TYPE_BOOLEAN];
            }
        }

        return $schemas;
    }

    /**
     * @param bool $classes_only If true, only the class names are returned. If false, the class name => localized name pairs are returned..
     * @return array<class-string<CommonDBTM>, string>
     */
    public static function getAssetTypes(bool $classes_only = true): array
    {
        global $CFG_GLPI;

        static $assets = null;

        if ($assets === null) {
            $assets = [];
            $types = $CFG_GLPI["asset_types"];
            /**
             * @var class-string<CommonDBTM> $type
             */
            foreach ($types as $type) {
                $assets[$type] = $type::getTypeName(1);
            }
        }
        return $classes_only ? array_keys($assets) : $assets;
    }

    #[Route(path: '/', methods: ['GET'], tags: ['Assets'])]
    #[Doc\Route(
        description: 'Get all available asset types',
        methods: ['GET'],
        responses: [
            '200' => [
                'description' => 'List of asset types',
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
        $asset_types = self::getAssetTypes(false);
        $asset_paths = [];
        foreach ($asset_types as $asset_type => $asset_name) {
            $asset_paths[] = [
                'itemtype'  => $asset_type,
                'name'      => $asset_name,
                'href'      => $this->getAPIPathForRouteFunction(self::class, 'search', ['itemtype' => $asset_type]),
            ];
        }
        return new JSONResponse($asset_paths);
    }

    private function getGlobalAssetSchema()
    {
        $asset_schemas = self::getKnownSchemas();
        $asset_schemas = array_filter($asset_schemas, static function ($key) {
            return !str_starts_with($key, '_');
        }, ARRAY_FILTER_USE_KEY);

        $shared_properties = [];
        $subtype_info = [];
        foreach ($asset_schemas as $schema_name => $schema) {
            $itemtype = $schema['x-itemtype'];
            // Need to check rights for each asset type
            if (!$itemtype::canView()) {
                continue;
            }
            $subtype_info[] = [
                'schema_name' => $schema_name,
                'itemtype' => $itemtype,
            ];
            if ($shared_properties === []) {
                $shared_properties = Doc\Schema::flattenProperties($schema['properties']);
                // Remove array properties (complex handling may be required. No support added for now)
                $shared_properties = array_filter($shared_properties, static function ($property) {
                    return !isset($property['type']) || $property['type'] !== Doc\Schema::TYPE_ARRAY;
                });
            } else {
                $props = Doc\Schema::flattenProperties($schema['properties']);
                foreach ($shared_properties as $key => $value) {
                    if (!array_key_exists($key, $props)) {
                        unset($shared_properties[$key]);
                    }
                }
            }
        }

        return [
            'x-subtypes' => $subtype_info,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => $shared_properties,
        ];
    }

    #[Route(path: '/Global', methods: ['GET'], tags: ['Assets'])]
    public function searchAll(Request $request): Response
    {
        return $this->searchBySchema($this->getGlobalAssetSchema(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}', methods: ['GET'], requirements: ['itemtype' => [self::class, 'getAssetTypes']], tags: ['Assets'])]
    #[Doc\Route(
        description: 'List or search assets of a specific type'
    )]
    public function search(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return $this->searchBySchema($this->getKnownSchema($itemtype), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['GET'], requirements: [
        'itemtype' => [self::class, 'getAssetTypes'],
        'id' => '\d+'
    ], tags: ['Assets'])]
    #[Doc\Route(
        description: 'Get an asset of a specific type by ID',
    )]
    public function getItem(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return $this->getOneBySchema($this->getKnownSchema($itemtype), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}', methods: ['POST'], requirements: [
        'itemtype' => [self::class, 'getAssetTypes'],
    ], tags: ['Assets'])]
    #[Doc\Route(
        description: 'Create an asset of a specific type',
    )]
    public function createItem(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return $this->createBySchema($this->getKnownSchema($itemtype), $request->getParameters() + ['itemtype' => $itemtype], 'getItem');
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['PATCH'], requirements: [
        'itemtype' => [self::class, 'getAssetTypes'],
        'id' => '\d+'
    ], tags: ['Assets'])]
    #[Doc\Route(
        description: 'Update an asset of a specific type by ID',
    )]
    public function updateItem(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return $this->updateBySchema($this->getKnownSchema($itemtype), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['DELETE'], requirements: [
        'itemtype' => [self::class, 'getAssetTypes'],
        'id' => '\d+'
    ], tags: ['Assets'])]
    #[Doc\Route(
        description: 'Delete an asset of a specific type by ID',
    )]
    public function deleteItem(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return $this->deleteBySchema($this->getKnownSchema($itemtype), $request->getAttributes(), $request->getParameters());
    }
}
