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
use CommonDBTM;
use DropdownVisibility;
use Entity;
use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\Middleware\ResultFormatterMiddleware;
use Glpi\Api\HL\ResourceAccessor;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\RouteVersion;
use Glpi\Http\JSONResponse;
use Glpi\Http\Request;
use Glpi\Http\Response;
use Location;
use Manufacturer;
use State;

#[Route(path: '/Dropdowns', priority: 1, tags: ['Dropdowns'])]
#[Doc\Route(
    parameters: [
        new Doc\Parameter(
            name: 'itemtype',
            schema: new Doc\Schema(Doc\Schema::TYPE_STRING),
            description: 'Dropdown type',
            location: Doc\Parameter::LOCATION_PATH,
        ),
        new Doc\Parameter(
            name: 'id',
            schema: new Doc\Schema(Doc\Schema::TYPE_INTEGER),
            description: 'The ID of the dropdown item',
            location: Doc\Parameter::LOCATION_PATH,
        ),
    ]
)]
final class DropdownController extends AbstractController
{
    use CRUDControllerTrait;

    protected static function getRawKnownSchemas(): array
    {
        $schemas = [];

        $schemas['Location'] = [
            'x-version-introduced' => '2.0',
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => Location::class,
            'description' => Location::getTypeName(1),
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'completename' => ['type' => Doc\Schema::TYPE_STRING],
                'code' => ['type' => Doc\Schema::TYPE_STRING],
                'alias' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'parent' => self::getDropdownTypeSchema(class: Location::class, full_schema: 'Location'),
                'level' => ['type' => Doc\Schema::TYPE_INTEGER],
                'room' => ['type' => Doc\Schema::TYPE_STRING],
                'building' => ['type' => Doc\Schema::TYPE_STRING],
                'address' => ['type' => Doc\Schema::TYPE_STRING],
                'town' => ['type' => Doc\Schema::TYPE_STRING],
                'postcode' => ['type' => Doc\Schema::TYPE_STRING],
                'state' => ['type' => Doc\Schema::TYPE_STRING],
                'country' => ['type' => Doc\Schema::TYPE_STRING],
                'latitude' => ['type' => Doc\Schema::TYPE_STRING],
                'longitude' => ['type' => Doc\Schema::TYPE_STRING],
                'altitude' => ['type' => Doc\Schema::TYPE_STRING],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['State'] = [
            'x-version-introduced' => '2.0',
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => State::class,
            'description' => State::getTypeName(1),
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
                'parent' => self::getDropdownTypeSchema(class: State::class, full_schema: 'State'),
                'level' => ['type' => Doc\Schema::TYPE_INTEGER],
                'is_visible_helpdesk' => ['x-field' => 'is_helpdesk_visible', 'type' => Doc\Schema::TYPE_BOOLEAN],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        // Uses static array for BC/stability. Plugins adding new types should use the related hook to modify the API schema
        $state_types = [
            'Computer', 'Monitor', 'NetworkEquipment',
            'Peripheral', 'Phone', 'Printer', 'SoftwareLicense',
            'Certificate', 'Enclosure', 'PDU', 'Line',
            'Rack', 'SoftwareVersion', 'Cluster', 'Contract',
            'Appliance', 'DatabaseInstance', 'Cable', 'Unmanaged', 'PassiveDCEquipment',
        ];
        $visiblities = [];
        foreach ($state_types as $state_type) {
            // Handle any cases where there may be a namespace and also make the property lowercase
            $visiblities[$state_type] = strtolower(str_replace('\\', '_', $state_type));
        }

        $schemas['State_Visibilities'] = [
            'x-version-introduced' => '2.0',
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [],
        ];
        $schemas['State']['properties']['visibilities'] = [
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-full-schema' => 'State_Visibilities',
        ];

        foreach ($visiblities as $state_type => $visiblity) {
            $schemas['State_Visibilities']['properties'][$visiblity] = [
                'type' => Doc\Schema::TYPE_BOOLEAN,
                'x-field' => 'is_visible',
                'readOnly' => true,
                'x-join' => [
                    'table' => DropdownVisibility::getTable(),
                    'fkey' => 'id',
                    'field' => 'items_id',
                    'condition' => [
                        'itemtype' => 'State',
                        'visible_itemtype' => $state_type,
                    ],
                ],
            ];
        }
        $schemas['State']['properties']['visibilities']['properties'] = $schemas['State_Visibilities']['properties'];

        $schemas['Manufacturer'] = [
            'x-version-introduced' => '2.0',
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => Manufacturer::class,
            'description' => Manufacturer::getTypeName(1),
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['Calendar'] = [
            'x-version-introduced' => '2.0',
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => Calendar::class,
            'description' => Calendar::getTypeName(1),
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
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        return $schemas;
    }

    /**
     * @param bool $types_only If true, only the type names are returned. If false, the type name => localized name pairs are returned.
     * @return array<class-string<CommonDBTM>, string>
     */
    public static function getDropdownTypes(bool $types_only = true): array
    {
        static $dropdowns = null;

        if ($dropdowns === null) {
            $dropdowns = [
                'Location' => Location::getTypeName(1),
                'State' => State::getTypeName(1),
                'Manufacturer' => Manufacturer::getTypeName(1),
                'Calendar' => Calendar::getTypeName(1),
            ];
        }
        return $types_only ? array_keys($dropdowns) : $dropdowns;
    }

    #[Route(path: '/', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Get all available dropdown types',
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
        $asset_types = self::getDropdownTypes(false);
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

    #[Route(path: '/{itemtype}', methods: ['GET'], requirements: [
        'itemtype' => [self::class, 'getDropdownTypes'],
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(
        schema_name: '{itemtype}',
        description: 'List or search dropdowns of a specific type'
    )]
    public function search(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::searchBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['GET'], requirements: [
        'itemtype' => [self::class, 'getDropdownTypes'],
        'id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(
        schema_name: '{itemtype}',
        description: 'Get an existing dropdown of a specific type'
    )]
    public function getItem(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::getOneBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}', methods: ['POST'], requirements: [
        'itemtype' => [self::class, 'getDropdownTypes'],
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\CreateRoute(
        schema_name: '{itemtype}',
        description: 'Create a dropdown of a specific type'
    )]
    public function createItem(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::createBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getParameters() + ['itemtype' => $itemtype], [self::class, 'getItem']);
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['PATCH'], requirements: [
        'itemtype' => [self::class, 'getDropdownTypes'],
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\UpdateRoute(
        schema_name: '{itemtype}',
        description: 'Update an existing dropdown of a specific type'
    )]
    public function updateItem(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::updateBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['DELETE'], requirements: [
        'itemtype' => [self::class, 'getDropdownTypes'],
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\DeleteRoute(
        schema_name: '{itemtype}',
        description: 'Delete a dropdown of a specific type',
    )]
    public function deleteItem(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::deleteBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }
}
