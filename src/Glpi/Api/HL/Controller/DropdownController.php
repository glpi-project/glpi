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

use AutoUpdateSystem;
use Calendar;
use CommonDBTM;
use Entity;
use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\Middleware\ResultFormatterMiddleware;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\Search;
use Glpi\Http\JSONResponse;
use Glpi\Http\Request;
use Glpi\Http\Response;
use Group;
use Location;
use Manufacturer;
use Network;
use State;
use User;

#[Route(path: '/Dropdowns', priority: 1, tags: ['Dropdowns'])]
#[Doc\Route(
    parameters: [
        [
            'name' => 'itemtype',
            'description' => 'Dropdown type',
            'location' => Doc\Parameter::LOCATION_PATH,
            'schema' => ['type' => Doc\Schema::TYPE_STRING]
        ],
        [
            'name' => 'id',
            'description' => 'The ID of the dropdown item',
            'location' => Doc\Parameter::LOCATION_PATH,
            'schema' => ['type' => Doc\Schema::TYPE_INTEGER]
        ]
    ]
)]
final class DropdownController extends AbstractController
{
    use CRUDControllerTrait;

    protected static function getRawKnownSchemas(): array
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

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
                    'x-readonly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'completename' => ['type' => Doc\Schema::TYPE_STRING],
                'code' => ['type' => Doc\Schema::TYPE_STRING],
                'aliases' => ['type' => Doc\Schema::TYPE_STRING],
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
            ]
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
                    'x-readonly' => true,
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
            ]
        ];

        // Uses static array for BC/stability. Plugins adding new types should use the related hook to modify the API schema
        $state_types = [
            'Computer', 'Monitor', 'NetworkEquipment',
            'Peripheral', 'Phone', 'Printer', 'SoftwareLicense',
            'Certificate', 'Enclosure', 'PDU', 'Line',
            'Rack', 'SoftwareVersion', 'Cluster', 'Contract',
            'Appliance', 'DatabaseInstance', 'Cable', 'Unmanaged', 'PassiveDCEquipment'
        ];
        $visiblities = [];
        foreach ($state_types as $state_type) {
            // Handle any cases where there may be a namespace and also make the property lowercase
            $visiblities[$state_type] = strtolower(str_replace('\\', '_', $state_type));
        }

        $schemas['State_Visibilities'] = [
            'x-version-introduced' => '2.0',
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => []
        ];
        $schemas['State']['properties']['visibilities'] = [
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-full-schema' => 'State_Visibilities',
        ];

        foreach ($visiblities as $state_type => $visiblity) {
            $schemas['State_Visibilities']['properties'][$visiblity] = [
                'type' => Doc\Schema::TYPE_BOOLEAN,
                'x-field' => 'is_visible',
                'x-readonly' => true,
                'x-join' => [
                    'table' => \DropdownVisibility::getTable(),
                    'fkey' => 'id',
                    'field' => 'items_id',
                    'condition' => [
                        'itemtype' => 'State',
                        'visible_itemtype' => $state_type
                    ]
                ]
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
                    'x-readonly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ]
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
                    'x-readonly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ]
        ];

        return $schemas;
    }
}
