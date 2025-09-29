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

use AutoUpdateSystem;
use Cable;
use Cartridge;
use CartridgeItem;
use CartridgeItem_PrinterModel;
use CommonDBTM;
use Computer;
use Consumable;
use ConsumableItem;
use Datacenter;
use DCRoom;
use Enclosure;
use EnclosureModel;
use Entity;
use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\Middleware\ResultFormatterMiddleware;
use Glpi\Api\HL\ResourceAccessor;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\RouteVersion;
use Glpi\Http\JSONResponse;
use Glpi\Http\Request;
use Glpi\Http\Response;
use Glpi\Socket;
use Glpi\SocketModel;
use Group_Item;
use GuzzleHttp\Psr7\Utils;
use Infocom;
use Item_Rack;
use Location;
use Manufacturer;
use Monitor;
use Network;
use NetworkEquipment;
use NetworkPort;
use OperatingSystem;
use PassiveDCEquipment;
use PassiveDCEquipmentModel;
use PassiveDCEquipmentType;
use PDU;
use PDUModel;
use PDUType;
use Peripheral;
use PrinterModel;
use Rack;
use RackModel;
use RackType;
use RuntimeException;
use Software;
use SoftwareCategory;
use SoftwareVersion;
use State;
use User;

use function Safe\json_decode;
use function Safe\json_encode;

#[Route(path: '/Assets', priority: 1, tags: ['Assets'])]
#[Doc\Route(
    parameters: [
        new Doc\Parameter(
            name: 'itemtype',
            schema: new Doc\Schema(Doc\Schema::TYPE_STRING),
            description: 'Asset type',
            location: Doc\Parameter::LOCATION_PATH,
        ),
        new Doc\Parameter(
            name: 'id',
            schema: new Doc\Schema(Doc\Schema::TYPE_INTEGER),
            description: 'The ID of the Asset',
            location: Doc\Parameter::LOCATION_PATH,
        ),
    ]
)]
final class AssetController extends AbstractController
{
    use CRUDControllerTrait;

    public static function getRawKnownSchemas(): array
    {
        global $CFG_GLPI;
        $schemas = [];

        $fn_get_assignable_restriction = static function (string $itemtype) {
            if (method_exists($itemtype, 'getAssignableVisiblityCriteria')) {
                $criteria = $itemtype::getAssignableVisiblityCriteria('_');
                if (count($criteria) === 1 && isset($criteria[0]) && is_numeric((string) $criteria[0])) {
                    // Return true for QueryExpression('1') and false for QueryExpression('0') to support fast pass/fail
                    return (bool) $criteria[0];
                }
                return ['WHERE' => $criteria];
            }
            throw new RuntimeException("Itemtype $itemtype is not an AssignableItem");
        };

        $schemas['_BaseAsset'] = [
            'type' => Doc\Schema::TYPE_OBJECT,
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

        $schemas['PrinterModel'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => PrinterModel::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'product_number' => ['type' => Doc\Schema::TYPE_STRING],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['SoftwareCategory'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => SoftwareCategory::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'completename' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'parent' => self::getDropdownTypeSchema(class: SoftwareCategory::class, full_schema: 'SoftwareCategory'),
                'level' => ['type' => Doc\Schema::TYPE_INTEGER],
            ],
        ];

        $schemas['OperatingSystem'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => OperatingSystem::class,
            'type' => Doc\Schema::TYPE_OBJECT,
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

        $schemas['RackModel'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => RackModel::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'product_number' => ['type' => Doc\Schema::TYPE_STRING],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['RackType'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => RackType::class,
            'type' => Doc\Schema::TYPE_OBJECT,
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

        $schemas['PDUModel'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => PDUModel::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'product_number' => ['type' => Doc\Schema::TYPE_STRING],
                'weight' => ['type' => Doc\Schema::TYPE_INTEGER],
                'rack_units' => ['x-field' => 'required_units', 'type' => Doc\Schema::TYPE_INTEGER],
                'depth' => ['type' => Doc\Schema::TYPE_NUMBER, 'format' => Doc\Schema::FORMAT_NUMBER_FLOAT],
                'power_connections' => ['type' => Doc\Schema::TYPE_INTEGER],
                'max_power' => ['type' => Doc\Schema::TYPE_INTEGER],
                'is_half_rack' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'is_rackable' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['PDUType'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => PDUType::class,
            'type' => Doc\Schema::TYPE_OBJECT,
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

        $schemas['PassiveDCEquipmentModel'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => PassiveDCEquipmentModel::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'product_number' => ['type' => Doc\Schema::TYPE_STRING],
                'weight' => ['type' => Doc\Schema::TYPE_INTEGER],
                'rack_units' => ['x-field' => 'required_units', 'type' => Doc\Schema::TYPE_INTEGER],
                'depth' => ['type' => Doc\Schema::TYPE_NUMBER, 'format' => Doc\Schema::FORMAT_NUMBER_FLOAT],
                'power_connections' => ['type' => Doc\Schema::TYPE_INTEGER],
                'power_consumption' => ['type' => Doc\Schema::TYPE_INTEGER],
                'max_power' => ['type' => Doc\Schema::TYPE_INTEGER],
                'is_half_rack' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['PassiveDCEquipmentType'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => PassiveDCEquipmentType::class,
            'type' => Doc\Schema::TYPE_OBJECT,
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

        $schemas['SocketModel'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => SocketModel::class,
            'type' => Doc\Schema::TYPE_OBJECT,
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

        $schemas['NetworkPort'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => NetworkPort::class,
            'type' => Doc\Schema::TYPE_OBJECT,
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
                'logical_number' => ['type' => Doc\Schema::TYPE_INTEGER],
                'mac' => ['type' => Doc\Schema::TYPE_STRING],
                'is_deleted' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'is_dynamic' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'if_mtu' => ['x-field' => 'ifmtu', 'type' => Doc\Schema::TYPE_INTEGER],
                'if_speed' => ['x-field' => 'ifspeed', 'type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64],
                'if_internal_status' => ['x-field' => 'ifinternalstatus', 'type' => Doc\Schema::TYPE_STRING],
                'if_connection_status' => ['x-field' => 'ifconnectionstatus', 'type' => Doc\Schema::TYPE_INTEGER],
                'if_last_change' => ['x-field' => 'iflastchange', 'type' => Doc\Schema::TYPE_STRING],
                'if_in_bytes' => ['x-field' => 'ifinbytes', 'type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64],
                'if_out_bytes' => ['x-field' => 'ifoutbytes', 'type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64],
                'if_in_errors' => ['x-field' => 'ifinerrors', 'type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64],
                'if_out_errors' => ['x-field' => 'ifouterrors', 'type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64],
                'if_status' => ['x-field' => 'ifstatus', 'type' => Doc\Schema::TYPE_STRING],
                'if_description' => ['x-field' => 'ifdescr', 'type' => Doc\Schema::TYPE_STRING],
                'if_alias' => ['x-field' => 'ifalias', 'type' => Doc\Schema::TYPE_STRING],
                'port_duplex' => ['x-field' => 'portduplex', 'type' => Doc\Schema::TYPE_STRING],
                'trunk' => ['type' => Doc\Schema::TYPE_INTEGER],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['DCRoom'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => DCRoom::class,
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
                'is_deleted' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'location' => self::getDropdownTypeSchema(class: Location::class, full_schema: 'Location'),
                'datacenter' => self::getDropdownTypeSchema(class: Datacenter::class, full_schema: 'DataCenter'),
                'rows' => ['x-field' => 'vis_rows', 'type' => Doc\Schema::TYPE_INTEGER],
                'cols' => ['x-field' => 'vis_cols', 'type' => Doc\Schema::TYPE_INTEGER],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $asset_types = self::getAssetTypes();

        foreach ($asset_types as $asset_type) {
            if (!is_subclass_of($asset_type, CommonDBTM::class)) {
                continue;
            }
            // Replace namespace separator with underscore
            $schema_name = str_replace('\\', '_', $asset_type);
            $schemas[$schema_name] = $schemas['_BaseAsset'];
            $schemas[$schema_name]['x-version-introduced'] = '2.0';
            $schemas[$schema_name]['x-itemtype'] = $asset_type;

            // Need instance since some fields are not static even if they aren't related to instances
            $asset = new $asset_type();

            if (in_array($asset_type, $CFG_GLPI['state_types'], true)) {
                $schemas[$schema_name]['properties']['status'] = self::getDropdownTypeSchema(class: State::class, full_schema: 'State');
            }

            if (in_array($asset_type, $CFG_GLPI['location_types'], true)) {
                $schemas[$schema_name]['properties']['location'] = self::getDropdownTypeSchema(class: Location::class, full_schema: 'Location');
            }

            if ($asset->isEntityAssign()) {
                $schemas[$schema_name]['properties']['entity'] = self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity');
                // Add completename field
                $schemas[$schema_name]['properties']['entity']['properties']['completename'] = ['type' => Doc\Schema::TYPE_STRING];
                $schemas[$schema_name]['properties']['is_recursive'] = ['type' => Doc\Schema::TYPE_BOOLEAN];
            }

            $type_class = $asset->getTypeClass();
            if ($type_class !== null) {
                $schemas[$schema_name]['properties']['type'] = self::getDropdownTypeSchema(class: $type_class);
            }
            if ($asset->isField('manufacturers_id')) {
                $schemas[$schema_name]['properties']['manufacturer'] = self::getDropdownTypeSchema(class: Manufacturer::class, full_schema: 'Manufacturer');
            }
            $model_class = $asset->getModelClass();
            if ($model_class !== null) {
                $schemas[$schema_name]['properties']['model'] = self::getDropdownTypeSchema(class: $model_class);
            }

            if (in_array($asset_type, $CFG_GLPI['assignable_types'], true)) {
                $schemas[$schema_name]['properties']['user'] = self::getDropdownTypeSchema(
                    class: User::class,
                    field: 'users_id',
                    full_schema: 'User'
                );
                $schemas[$schema_name]['properties']['user_tech'] = self::getDropdownTypeSchema(
                    class: User::class,
                    field: 'users_id_tech',
                    full_schema: 'User'
                );
                $schemas[$schema_name]['properties']['group'] = [
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
                                    'itemtype' => $asset_type,
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
                $schemas[$schema_name]['properties']['group_tech'] = [
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
                                    'itemtype' => $asset_type,
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
                $schemas[$schema_name]['x-rights-conditions'] = [
                    'read' => static fn() => $fn_get_assignable_restriction($asset_type),
                ];
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

        $schemas['Cartridge'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => Cartridge::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'entities_id' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                'cartridgeitems_id' => self::getDropdownTypeSchema(class: CartridgeItem::class, full_schema: 'CartridgeItem'),
                'pages' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                'date_in' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_DATE_TIME,
                    'readOnly' => true,
                ],
                'date_use' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_DATE_TIME,
                    'readOnly' => true,
                ],
                'date_out' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_DATE_TIME,
                    'readOnly' => true,
                ],
                'date_creation' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_DATE_TIME,
                    'readOnly' => true,
                ],
                'date_mod' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_DATE_TIME,
                    'readOnly' => true,
                ],
            ],
        ];

        $schemas['CartridgeItem'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => CartridgeItem::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-rights-conditions' => ['read' => static fn() => $fn_get_assignable_restriction(CartridgeItem::class)],
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
                'user' => self::getDropdownTypeSchema(class: User::class, full_schema: 'User'),
                'user_tech' => self::getDropdownTypeSchema(class: User::class, field: 'users_id_tech', full_schema: 'User'),
                'printer_models' => [
                    'type' => Doc\Schema::TYPE_ARRAY,
                    'description' => 'List of printer models that can use this cartridge',
                    'items' => [
                        'type' => Doc\Schema::TYPE_OBJECT,
                        'x-full-schema' => 'PrinterModel',
                        'x-join' => [
                            'table' => PrinterModel::getTable(),
                            'fkey' => 'printermodels_id',
                            'field' => 'id',
                            'ref-join' => [
                                'table' => CartridgeItem_PrinterModel::getTable(),
                                'fkey' => 'id', // The ID field of the main table used to refer to the cartridgeitems_id of the joined table
                                'field' => CartridgeItem::getForeignKeyField(),
                            ],
                        ],
                        'properties' => [
                            'id' => [
                                'type' => Doc\Schema::TYPE_INTEGER,
                                'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                                'readOnly' => true,
                            ],
                            'name' => ['type' => Doc\Schema::TYPE_STRING],
                            'comment' => ['type' => Doc\Schema::TYPE_STRING],
                        ],
                    ],
                ],
                'cartridges' => [
                    'type' => Doc\Schema::TYPE_ARRAY,
                    'description' => 'List of cartridges',
                    'items' => [
                        'type' => Doc\Schema::TYPE_OBJECT,
                        'x-full-schema' => 'Cartridge',
                        'x-join' => [
                            'table' => Cartridge::getTable(),
                            'fkey' => 'id',
                            'field' => CartridgeItem::getForeignKeyField(),
                        ],
                        'properties' => [
                            'id' => [
                                'type' => Doc\Schema::TYPE_INTEGER,
                                'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                                'readOnly' => true,
                            ],
                            'pages' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                            'date_in' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                            'date_use' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                            'date_out' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                            'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                            'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                        ],
                    ],
                ],
            ],
        ];

        $schemas['Consumable'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => Consumable::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'entities_id' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                'consumableitems_id' => self::getDropdownTypeSchema(class: ConsumableItem::class, full_schema: 'ConsumableItem'),
                'date_in' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_DATE_TIME,
                    'readOnly' => true,
                ],
                'date_out' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_DATE_TIME,
                    'readOnly' => true,
                ],
                'date_creation' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_DATE_TIME,
                    'readOnly' => true,
                ],
                'date_mod' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_DATE_TIME,
                    'readOnly' => true,
                ],
                'itemtype' => ['type' => Doc\Schema::TYPE_STRING],
                'items_id' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64],
            ],
        ];

        $schemas['ConsumableItem'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => ConsumableItem::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-rights-conditions' => ['read' => static fn() => $fn_get_assignable_restriction(ConsumableItem::class)],
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
                'user' => self::getDropdownTypeSchema(class: User::class, full_schema: 'User'),
                'user_tech' => self::getDropdownTypeSchema(class: User::class, field: 'users_id_tech', full_schema: 'User'),
                'consumables' => [
                    'type' => Doc\Schema::TYPE_ARRAY,
                    'description' => 'List of consumables',
                    'items' => [
                        'type' => Doc\Schema::TYPE_OBJECT,
                        'x-full-schema' => 'Consumable',
                        'x-join' => [
                            'table' => Consumable::getTable(),
                            'fkey' => 'id',
                            'field' => ConsumableItem::getForeignKeyField(),
                        ],
                        'properties' => [
                            'id' => [
                                'type' => Doc\Schema::TYPE_INTEGER,
                                'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                                'readOnly' => true,
                            ],
                            'date_in' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                            'date_out' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                            'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                            'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                            'itemtype' => ['type' => Doc\Schema::TYPE_STRING],
                            'items_id' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64],
                        ],
                    ],
                ],
            ],
        ];

        $schemas['Software'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => Software::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-rights-conditions' => ['read' => static fn() => $fn_get_assignable_restriction(Software::class)],
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
                'location' => self::getDropdownTypeSchema(class: Location::class, full_schema: 'Location'),
                'category' => self::getDropdownTypeSchema(class: SoftwareCategory::class, full_schema: 'SoftwareCategory'),
                'manufacturer' => self::getDropdownTypeSchema(class: Manufacturer::class, full_schema: 'Manufacturer'),
                'parent' => self::getDropdownTypeSchema(class: Software::class, full_schema: 'Software'),
                'is_helpdesk_visible' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'user' => self::getDropdownTypeSchema(class: User::class, full_schema: 'User'),
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
                                    'itemtype' => Software::class,
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
                                    'itemtype' => Software::class,
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
                'is_deleted' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'is_update' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'is_valid' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['SoftwareVersion'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => SoftwareVersion::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'arch' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'software' => self::getDropdownTypeSchema(class: Software::class, full_schema: 'Software'),
                'state' => self::getDropdownTypeSchema(class: State::class, full_schema: 'State'),
                'operating_system' => self::getDropdownTypeSchema(class: OperatingSystem::class, full_schema: 'OperatingSystem'),
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['Rack'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => Rack::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-rights-conditions' => ['read' => static fn() => $fn_get_assignable_restriction(Rack::class)],
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
                'location' => self::getDropdownTypeSchema(class: Location::class, full_schema: 'Location'),
                'serial' => ['type' => Doc\Schema::TYPE_STRING],
                'otherserial' => ['type' => Doc\Schema::TYPE_STRING],
                'model' => self::getDropdownTypeSchema(class: RackModel::class, full_schema: 'RackModel'),
                'manufacturer' => self::getDropdownTypeSchema(class: Manufacturer::class, full_schema: 'Manufacturer'),
                'type' => self::getDropdownTypeSchema(class: RackType::class, full_schema: 'RackType'),
                'state' => self::getDropdownTypeSchema(class: State::class, full_schema: 'State'),
                'user' => self::getDropdownTypeSchema(class: User::class, full_schema: 'User'),
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
                                    'itemtype' => Rack::class,
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
                                    'itemtype' => Rack::class,
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
                'width' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                'height' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                'depth' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                'number_units' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                'is_deleted' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'room' => self::getDropdownTypeSchema(class: DCRoom::class, full_schema: 'DCRoom'),
                'room_orientation' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                'position' => ['type' => Doc\Schema::TYPE_STRING],
                'bgcolor' => ['type' => Doc\Schema::TYPE_STRING],
                'max_power' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                'measured_power' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT32,
                    'x-field' => 'mesured_power', // Took liberty to fix typo in DB without having to mess with the DB itself or other code
                ],
                'max_weight' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                'items' => [
                    'type' => Doc\Schema::TYPE_ARRAY,
                    'description' => 'List of items in the rack',
                    'items' => [
                        'type' => Doc\Schema::TYPE_OBJECT,
                        'x-full-schema' => 'RackItem',
                        'x-join' => [
                            'table' => Item_Rack::getTable(),
                            'fkey' => 'id',
                            'field' => Rack::getForeignKeyField(),
                            'primary-property' => 'id',
                        ],
                        'properties' => [
                            'id' => [
                                'type' => Doc\Schema::TYPE_INTEGER,
                                'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                                'readOnly' => true,
                            ],
                            'itemtype' => ['type' => Doc\Schema::TYPE_STRING],
                            'items_id' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64],
                        ],
                    ],
                ],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['RackItem'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => Item_Rack::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'rack' => self::getDropdownTypeSchema(class: Rack::class, full_schema: 'Rack'),
                'itemtype' => ['type' => Doc\Schema::TYPE_STRING],
                'items_id' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64],
                'position' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                'orientation' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT32,
                    'enum' => [Rack::FRONT, Rack::REAR],
                    'description' => <<<EOT
                        Orientation of the item in the rack.
                        - 0: Front
                        - 1: Rear
                        EOT,
                ],
                'bgcolor' => ['type' => Doc\Schema::TYPE_STRING],
                'position_horizontal' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT32,
                    'x-field' => 'hpos',
                ],
                'is_reserved' => ['type' => Doc\Schema::TYPE_BOOLEAN],
            ],
        ];

        $schemas['Enclosure'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => Enclosure::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-rights-conditions' => ['read' => static fn() => $fn_get_assignable_restriction(Enclosure::class)],
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
                'location' => self::getDropdownTypeSchema(class: Location::class, full_schema: 'Location'),
                'serial' => ['type' => Doc\Schema::TYPE_STRING],
                'otherserial' => ['type' => Doc\Schema::TYPE_STRING],
                'model' => self::getDropdownTypeSchema(EnclosureModel::class),
                'manufacturer' => self::getDropdownTypeSchema(class: Manufacturer::class, full_schema: 'Manufacturer'),
                'state' => self::getDropdownTypeSchema(class: State::class, full_schema: 'State'),
                'user' => self::getDropdownTypeSchema(class: User::class, full_schema: 'User'),
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
                                    'itemtype' => Enclosure::class,
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
                                    'itemtype' => Enclosure::class,
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
                'is_deleted' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'orientation' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                'power_supplies' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['PDU'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => PDU::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-rights-conditions' => ['read' => static fn() => $fn_get_assignable_restriction(PDU::class)],
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
                'location' => self::getDropdownTypeSchema(class: Location::class, full_schema: 'Location'),
                'serial' => ['type' => Doc\Schema::TYPE_STRING],
                'otherserial' => ['type' => Doc\Schema::TYPE_STRING],
                'model' => self::getDropdownTypeSchema(class: PDUModel::class, full_schema: 'PDUModel'),
                'manufacturer' => self::getDropdownTypeSchema(class: Manufacturer::class, full_schema: 'Manufacturer'),
                'type' => self::getDropdownTypeSchema(class: PDUType::class, full_schema: 'PDUType'),
                'state' => self::getDropdownTypeSchema(class: State::class, full_schema: 'State'),
                'user' => self::getDropdownTypeSchema(class: User::class, full_schema: 'User'),
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
                                    'itemtype' => PDU::class,
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
                                    'itemtype' => PDU::class,
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
                'is_deleted' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['PassiveDCEquipment'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => PassiveDCEquipment::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-rights-conditions' => ['read' => static fn() => $fn_get_assignable_restriction(PassiveDCEquipment::class)],
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
                'location' => self::getDropdownTypeSchema(class: Location::class, full_schema: 'Location'),
                'serial' => ['type' => Doc\Schema::TYPE_STRING],
                'otherserial' => ['type' => Doc\Schema::TYPE_STRING],
                'model' => self::getDropdownTypeSchema(class: PassiveDCEquipmentModel::class, full_schema: 'PassiveDCEquipmentModel'),
                'manufacturer' => self::getDropdownTypeSchema(class: Manufacturer::class, full_schema: 'Manufacturer'),
                'type' => self::getDropdownTypeSchema(class: PassiveDCEquipmentType::class, full_schema: 'PassiveDCEquipmentType'),
                'state' => self::getDropdownTypeSchema(class: State::class, full_schema: 'State'),
                'user' => self::getDropdownTypeSchema(class: User::class, full_schema: 'User'),
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
                                    'itemtype' => PassiveDCEquipment::class,
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
                                    'itemtype' => PassiveDCEquipment::class,
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
                'is_deleted' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['Cable'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => Cable::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-rights-conditions' => ['read' => static fn() => $fn_get_assignable_restriction(Cable::class)],
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
                'otherserial' => ['type' => Doc\Schema::TYPE_STRING],
                'state' => self::getDropdownTypeSchema(class: State::class, full_schema: 'State'),
                'user' => self::getDropdownTypeSchema(class: User::class, full_schema: 'User'),
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
                                    'itemtype' => Cable::class,
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
                                    'itemtype' => Cable::class,
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
                'is_deleted' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'itemtype_endpoint_a' => ['type' => Doc\Schema::TYPE_STRING],
                'items_id_endpoint_a' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64],
                'socketmodel_endpoint_a' => self::getDropdownTypeSchema(
                    class: SocketModel::class,
                    field: 'socketmodels_id_endpoint_a',
                    full_schema: 'SocketModel'
                ),
                'sockets_id_endpoint_a' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64],
                'itemtype_endpoint_b' => ['type' => Doc\Schema::TYPE_STRING],
                'items_id_endpoint_b' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64],
                'socketmodel_endpoint_b' => self::getDropdownTypeSchema(
                    class: SocketModel::class,
                    field: 'socketmodels_id_endpoint_b',
                    full_schema: 'SocketModel'
                ),
                'sockets_id_endpoint_b' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['Socket'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => Socket::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'location' => self::getDropdownTypeSchema(class: Location::class, full_schema: 'Location'),
                'model' => self::getDropdownTypeSchema(class: SocketModel::class, full_schema: 'SocketModel'),
                'wiring_side' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                'itemtype' => ['type' => Doc\Schema::TYPE_STRING],
                'items_id' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64],
                'network_port' => self::getDropdownTypeSchema(class: NetworkPort::class, full_schema: 'NetworkPort'),
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['CommonAsset'] = self::getGlobalAssetSchema($schemas);

        return $schemas;
    }

    /**
     * @param bool $types_only If true, only the type names are returned. If false, the type name => localized name pairs are returned.
     * @return array<class-string<CommonDBTM>, string>
     */
    public static function getAssetTypes(bool $types_only = true): array
    {
        static $assets = null;

        if ($assets === null) {
            $assets = [];
            $types = ['Computer', 'Monitor', 'NetworkEquipment',
                'Peripheral', 'Phone', 'Printer', 'SoftwareLicense',
                'Certificate', 'Unmanaged', 'Appliance',
            ];
            /**
             * @var class-string<CommonDBTM> $type
             */
            foreach ($types as $type) {
                $assets[$type] = $type::getTypeName(1);
            }
        }
        return $types_only ? array_keys($assets) : $assets;
    }

    public static function getRackTypes(bool $schema_names_only = true): array
    {
        static $rack_types = null;

        if ($rack_types === null) {
            $rack_types = [
                Computer::class => [
                    'schema_name' => 'Computer',
                    'label' => Computer::getTypeName(1),
                ],
                Monitor::class => [
                    'schema_name' => 'Monitor',
                    'label' => Monitor::getTypeName(1),
                ],
                NetworkEquipment::class => [
                    'schema_name' => 'NetworkEquipment',
                    'label' => NetworkEquipment::getTypeName(1),
                ],
                Peripheral::class => [
                    'schema_name' => 'Peripheral',
                    'label' => Peripheral::getTypeName(1),
                ],
                Enclosure::class => [
                    'schema_name' => 'Enclosure',
                    'label' => Enclosure::getTypeName(1),
                ],
                PDU::class => [
                    'schema_name' => 'PDU',
                    'label' => PDU::getTypeName(1),
                ],
                PassiveDCEquipment::class => [
                    'schema_name' => 'PassiveDCEquipment',
                    'label' => PassiveDCEquipment::getTypeName(1),
                ],
            ];
        }
        return $schema_names_only ? array_column($rack_types, 'schema_name') : $rack_types;
    }

    #[Route(path: '/', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Get all available asset types',
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
        $asset_types = self::getAssetTypes(false);
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

    private static function getGlobalAssetSchema($asset_schemas)
    {
        $asset_types = self::getAssetTypes();
        $asset_schemas = array_filter($asset_schemas, static fn($key) => !str_starts_with($key, '_') && in_array($key, $asset_types, true), ARRAY_FILTER_USE_KEY);
        $union_schema = Doc\Schema::getUnionSchema($asset_schemas);
        $union_schema['x-version-introduced'] = '2.0';
        return $union_schema;
    }

    #[Route(path: '/Global', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(
        schema_name: 'CommonAsset',
        description: 'List or search assets of all types'
    )]
    public function searchAll(Request $request): Response
    {
        return ResourceAccessor::searchBySchema($this->getKnownSchema('CommonAsset', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/{itemtype}', methods: ['GET'], requirements: [
        'itemtype' => [self::class, 'getAssetTypes'],
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(
        schema_name: '{itemtype}',
        description: 'List or search assets of a specific type'
    )]
    public function search(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::searchBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['GET'], requirements: [
        'itemtype' => [self::class, 'getAssetTypes'],
        'id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(
        schema_name: '{itemtype}',
        description: 'Get an existing asset of a specific type'
    )]
    public function getItem(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::getOneBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{id}/Infocom', methods: ['GET'], requirements: [
        'itemtype' => [self::class, 'getAssetTypes'],
        'id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(
        schema_name: 'Infocom',
        description: 'Get the financial and administration information for a specific asset'
    )]
    public function getItemInfocom(Request $request): Response
    {
        if (!Infocom::canView()) {
            return self::getAccessDeniedErrorResponse();
        }
        $params = $request->getParameters();
        $itemtype = $request->getAttribute('itemtype');
        $items_id = $request->getAttribute('id');
        $filter = 'itemtype==' . $itemtype . ';items_id==' . $items_id;
        $params['filter'] = $filter;
        $management_controller = new ManagementController();
        $result = ResourceAccessor::searchBySchema($management_controller->getKnownSchema('Infocom', $this->getAPIVersion($request)), $params);
        if ($result->getStatusCode() !== 200) {
            return $result;
        }
        $results = json_decode((string) $result->getBody(), true);
        if (empty($results)) {
            return self::getNotFoundErrorResponse();
        }
        $results = reset($results);
        return $result->withBody(Utils::streamFor(json_encode($results)));
    }

    #[Route(path: '/{itemtype}', methods: ['POST'], requirements: [
        'itemtype' => [self::class, 'getAssetTypes'],
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\CreateRoute(
        schema_name: '{itemtype}',
        description: 'Create an asset of a specific type'
    )]
    public function createItem(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::createBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getParameters() + ['itemtype' => $itemtype], [self::class, 'getItem']);
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['PATCH'], requirements: [
        'itemtype' => [self::class, 'getAssetTypes'],
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\UpdateRoute(
        schema_name: '{itemtype}',
        description: 'Update an existing asset of a specific type'
    )]
    public function updateItem(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::updateBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['DELETE'], requirements: [
        'itemtype' => [self::class, 'getAssetTypes'],
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\DeleteRoute(
        schema_name: '{itemtype}',
        description: 'Delete an asset of a specific type',
    )]
    public function deleteItem(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::deleteBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Cartridge', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(
        schema_name: 'CartridgeItem',
        description: 'List or search cartridge models'
    )]
    public function searchCartridgeItems(Request $request): Response
    {
        return ResourceAccessor::searchBySchema($this->getKnownSchema('CartridgeItem', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/Cartridge/{id}', methods: ['GET'], requirements: [
        'id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(
        schema_name: 'CartridgeItem',
        description: 'Get an existing cartridge model'
    )]
    public function getCartridgeItem(Request $request): Response
    {
        return ResourceAccessor::getOneBySchema($this->getKnownSchema('CartridgeItem', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Cartridge', methods: ['POST'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\CreateRoute(
        schema_name: 'CartridgeItem',
        description: 'Create a new cartridge model'
    )]
    public function createCartridgeItems(Request $request): Response
    {
        return ResourceAccessor::createBySchema($this->getKnownSchema('CartridgeItem', $this->getAPIVersion($request)), $request->getParameters(), [self::class, 'getCartridgeItem']);
    }

    #[Route(path: '/Cartridge/{id}', methods: ['PATCH'], requirements: [
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\UpdateRoute(
        schema_name: 'CartridgeItem',
        description: 'Update an existing cartridge model'
    )]
    public function updateCartridgeItems(Request $request): Response
    {
        return ResourceAccessor::updateBySchema($this->getKnownSchema('CartridgeItem', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Cartridge/{id}', methods: ['DELETE'], requirements: [
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\DeleteRoute(
        schema_name: 'CartridgeItem',
        description: 'Delete a cartridge model',
    )]
    public function deleteCartridgeItems(Request $request): Response
    {
        return ResourceAccessor::deleteBySchema($this->getKnownSchema('CartridgeItem', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Cartridge/{cartridgeitems_id}/{id}', methods: ['GET'], requirements: [
        'cartridgeitems_id' => '\d+',
        'id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(schema_name: 'Cartridge')]
    public function getCartridge(Request $request): Response
    {
        return ResourceAccessor::getOneBySchema($this->getKnownSchema('Cartridge', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Cartridge/{cartridgeitems_id}', methods: ['POST'], requirements: [
        'cartridgeitems_id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\CreateRoute(schema_name: 'Cartridge')]
    public function createCartridges(Request $request): Response
    {
        return ResourceAccessor::createBySchema($this->getKnownSchema('Cartridge', $this->getAPIVersion($request)), $request->getParameters(), [self::class, 'getCartridge']);
    }

    #[Route(path: '/Cartridge/{cartridgeitems_id}/{id}', methods: ['PATCH'], requirements: [
        'cartridgeitems_id' => '\d+',
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\UpdateRoute(schema_name: 'Cartridge')]
    public function updateCartridges(Request $request): Response
    {
        return ResourceAccessor::updateBySchema($this->getKnownSchema('Cartridge', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Cartridge/{cartridgeitems_id}/{id}', methods: ['DELETE'], requirements: [
        'cartridgeitems_id' => '\d+',
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\DeleteRoute(schema_name: 'Cartridge')]
    public function deleteCartridges(Request $request): Response
    {
        return ResourceAccessor::deleteBySchema($this->getKnownSchema('Cartridge', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Consumable', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(
        schema_name: 'ConsumableItem',
        description: 'List or search consumables models'
    )]
    public function searchConsumableItems(Request $request): Response
    {
        return ResourceAccessor::searchBySchema($this->getKnownSchema('ConsumableItem', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/Consumable/{id}', methods: ['GET'], requirements: ['id' => '\d+'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(
        schema_name: 'ConsumableItem',
        description: 'Get an existing consumable model'
    )]
    public function getConsumableItem(Request $request): Response
    {
        return ResourceAccessor::getOneBySchema($this->getKnownSchema('ConsumableItem', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Consumable', methods: ['POST'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\CreateRoute(
        schema_name: 'ConsumableItem',
        description: 'Create a new consumable model'
    )]
    public function createConsumableItems(Request $request): Response
    {
        return ResourceAccessor::createBySchema($this->getKnownSchema('ConsumableItem', $this->getAPIVersion($request)), $request->getParameters(), [self::class, 'getConsumableItem']);
    }

    #[Route(path: '/Consumable/{id}', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\UpdateRoute(
        schema_name: 'ConsumableItem',
        description: 'Update an existing consumable model'
    )]
    public function updateConsumableItems(Request $request): Response
    {
        return ResourceAccessor::updateBySchema($this->getKnownSchema('ConsumableItem', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Consumable/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\DeleteRoute(
        schema_name: 'ConsumableItem',
        description: 'Delete a consumable model',
    )]
    public function deleteConsumableItems(Request $request): Response
    {
        return ResourceAccessor::deleteBySchema($this->getKnownSchema('ConsumableItem', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Consumable/{consumableitems_id}/{id}', methods: ['GET'], requirements: [
        'consumableitems_id' => '\d+',
        'id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(schema_name: 'Consumable')]
    public function getConsumable(Request $request): Response
    {
        return ResourceAccessor::getOneBySchema($this->getKnownSchema('Consumable', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Consumable/{consumableitems_id}', methods: ['POST'], requirements: ['consumableitems_id' => '\d+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\CreateRoute(schema_name: 'Consumable')]
    public function createConsumables(Request $request): Response
    {
        return ResourceAccessor::createBySchema($this->getKnownSchema('Consumable', $this->getAPIVersion($request)), $request->getParameters(), [self::class, 'getConsumable']);
    }

    #[Route(path: '/Consumable/{consumableitems_id}/{id}', methods: ['PATCH'], requirements: [
        'consumableitems_id' => '\d+',
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\UpdateRoute(schema_name: 'Consumable')]
    public function updateConsumable(Request $request): Response
    {
        return ResourceAccessor::updateBySchema($this->getKnownSchema('Consumable', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Consumable/{consumableitems_id}/{id}', methods: ['DELETE'], requirements: [
        'consumableitems_id' => '\d+',
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\DeleteRoute(schema_name: 'Consumable')]
    public function deleteConsumable(Request $request): Response
    {
        return ResourceAccessor::deleteBySchema($this->getKnownSchema('Consumable', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Software', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(schema_name: 'Software')]
    public function searchSoftware(Request $request): Response
    {
        return ResourceAccessor::searchBySchema($this->getKnownSchema('Software', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/Software/{id}', methods: ['GET'], requirements: ['id' => '\d+'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(schema_name: 'Software')]
    public function getSoftware(Request $request): Response
    {
        return ResourceAccessor::getOneBySchema($this->getKnownSchema('Software', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Software', methods: ['POST'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\CreateRoute(schema_name: 'Software')]
    public function createSoftware(Request $request): Response
    {
        return ResourceAccessor::createBySchema($this->getKnownSchema('Software', $this->getAPIVersion($request)), $request->getParameters(), [self::class, 'getSoftware']);
    }

    #[Route(path: '/Software/{id}', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\UpdateRoute(schema_name: 'Software')]
    public function updateSoftware(Request $request): Response
    {
        return ResourceAccessor::updateBySchema($this->getKnownSchema('Software', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Software/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\DeleteRoute(schema_name: 'Software')]
    public function deleteSoftware(Request $request): Response
    {
        return ResourceAccessor::deleteBySchema($this->getKnownSchema('Software', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Rack', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(schema_name: 'Rack')]
    public function searchRack(Request $request): Response
    {
        return ResourceAccessor::searchBySchema($this->getKnownSchema('Rack', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/Rack/{id}', methods: ['GET'], requirements: ['id' => '\d+'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(schema_name: 'Rack')]
    public function getRack(Request $request): Response
    {
        return ResourceAccessor::getOneBySchema($this->getKnownSchema('Rack', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Rack', methods: ['POST'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\CreateRoute(schema_name: 'Rack')]
    public function createRack(Request $request): Response
    {
        return ResourceAccessor::createBySchema($this->getKnownSchema('Rack', $this->getAPIVersion($request)), $request->getParameters(), [self::class, 'getRack']);
    }

    #[Route(path: '/Rack/{id}', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\UpdateRoute(schema_name: 'Rack')]
    public function updateRack(Request $request): Response
    {
        return ResourceAccessor::updateBySchema($this->getKnownSchema('Rack', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Rack/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\DeleteRoute(schema_name: 'Rack')]
    public function deleteRack(Request $request): Response
    {
        return ResourceAccessor::deleteBySchema($this->getKnownSchema('Rack', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Rack/{rack_id}/Item', methods: ['GET'], requirements: ['rack_id' => '\d+'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(
        schema_name: 'RackItem',
        description: 'List or search for items in a rack'
    )]
    public function getRackItems(Request $request): Response
    {
        $filters = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filters .= ';rack.id==' . $request->getAttribute('rack_id');
        $request->setParameter('filter', $filters);
        return ResourceAccessor::searchBySchema($this->getKnownSchema('RackItem', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/Rack/{rack_id}/Item/{id}', methods: ['GET'], requirements: [
        'rack_id' => '\d+',
        'id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(
        schema_name: 'RackItem',
        description: 'Get an existing rack item'
    )]
    public function getRackItem(Request $request): Response
    {
        $filters = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filters .= ';rack.id==' . $request->getAttribute('rack_id');
        $request->setParameter('filter', $filters);
        return ResourceAccessor::getOneBySchema($this->getKnownSchema('RackItem', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Rack/{rack_id}/Item/{id}', methods: ['PATCH'], requirements: [
        'rack_id' => '\d+',
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\UpdateRoute(
        schema_name: 'RackItem',
        description: 'Update an existing rack item'
    )]
    public function updateRackItem(Request $request): Response
    {
        return ResourceAccessor::updateBySchema($this->getKnownSchema('RackItem', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Rack/{rack_id}/Item', methods: ['POST'], requirements: ['rack_id' => '\d+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\CreateRoute(
        schema_name: 'RackItem',
        description: 'Add an item to a rack'
    )]
    public function createRackItem(Request $request): Response
    {
        $rack_types = self::getRackTypes(false);
        $rack_type = $request->getParameters()['itemtype'];

        if (!array_key_exists($rack_type, $rack_types)) {
            return new JSONResponse([
                'error' => "Invalid itemtype '$rack_type'. Allowed values are: " . implode(', ', array_keys($rack_types)),
            ], 400);
        }

        $request->setParameter('rack', $request->getAttribute('rack_id'));
        return ResourceAccessor::createBySchema($this->getKnownSchema('RackItem', $this->getAPIVersion($request)), $request->getParameters(), [
            self::class, 'getRackItem',
        ], [
            'mapped' => [
                'rack_id' => $request->getAttribute('rack_id'),
            ],
        ]);
    }

    #[Route(path: '/Rack/{rack_id}/Item/{id}', methods: ['DELETE'], requirements: [
        'rack_id' => '\d+',
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\DeleteRoute(
        schema_name: 'RackItem',
        description: 'Remove an item from a rack'
    )]
    public function deleteRackItem(Request $request): Response
    {
        return ResourceAccessor::deleteBySchema($this->getKnownSchema('RackItem', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Enclosure', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(schema_name: 'Enclosure')]
    public function searchEnclosure(Request $request): Response
    {
        return ResourceAccessor::searchBySchema($this->getKnownSchema('Enclosure', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/Enclosure/{id}', methods: ['GET'], requirements: ['id' => '\d+'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(schema_name: 'Enclosure')]
    public function getEnclosure(Request $request): Response
    {
        return ResourceAccessor::getOneBySchema($this->getKnownSchema('Enclosure', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Enclosure', methods: ['POST'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\CreateRoute(schema_name: 'Enclosure')]
    public function createEnclosure(Request $request): Response
    {
        return ResourceAccessor::createBySchema($this->getKnownSchema('Enclosure', $this->getAPIVersion($request)), $request->getParameters(), [self::class, 'getEnclosure']);
    }

    #[Route(path: '/Enclosure/{id}', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\UpdateRoute(schema_name: 'Enclosure')]
    public function updateEnclosure(Request $request): Response
    {
        return ResourceAccessor::updateBySchema($this->getKnownSchema('Enclosure', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Enclosure/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\DeleteRoute(schema_name: 'Enclosure')]
    public function deleteEnclosure(Request $request): Response
    {
        return ResourceAccessor::deleteBySchema($this->getKnownSchema('Enclosure', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/PDU', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(schema_name: 'PDU')]
    public function searchPDU(Request $request): Response
    {
        return ResourceAccessor::searchBySchema($this->getKnownSchema('PDU', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/PDU/{id}', methods: ['GET'], requirements: ['id' => '\d+'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(schema_name: 'PDU')]
    public function getPDU(Request $request): Response
    {
        return ResourceAccessor::getOneBySchema($this->getKnownSchema('PDU', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/PDU', methods: ['POST'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\CreateRoute(schema_name: 'PDU')]
    public function createPDU(Request $request): Response
    {
        return ResourceAccessor::createBySchema($this->getKnownSchema('PDU', $this->getAPIVersion($request)), $request->getParameters(), [self::class, 'getPDU']);
    }

    #[Route(path: '/PDU/{id}', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\UpdateRoute(schema_name: 'PDU')]
    public function updatePDU(Request $request): Response
    {
        return ResourceAccessor::updateBySchema($this->getKnownSchema('PDU', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/PDU/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\DeleteRoute(schema_name: 'PDU')]
    public function deletePDU(Request $request): Response
    {
        return ResourceAccessor::deleteBySchema($this->getKnownSchema('PDU', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/PassiveDCEquipment', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(
        schema_name: 'PassiveDCEquipment',
        description: 'List or search passive DC equipment'
    )]
    public function searchPassiveDCEquipment(Request $request): Response
    {
        return ResourceAccessor::searchBySchema($this->getKnownSchema('PassiveDCEquipment', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/PassiveDCEquipment/{id}', methods: ['GET'], requirements: ['id' => '\d+'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(
        schema_name: 'PassiveDCEquipment',
        description: 'Get an existing passive DC equipment'
    )]
    public function getPassiveDCEquipment(Request $request): Response
    {
        return ResourceAccessor::getOneBySchema($this->getKnownSchema('PassiveDCEquipment', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/PassiveDCEquipment', methods: ['POST'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\CreateRoute(
        schema_name: 'PassiveDCEquipment',
        description: 'Create a new passive DC equipment'
    )]
    public function createPassiveDCEquipment(Request $request): Response
    {
        return ResourceAccessor::createBySchema($this->getKnownSchema('PassiveDCEquipment', $this->getAPIVersion($request)), $request->getParameters(), [self::class, 'getPassiveDCEquipment']);
    }

    #[Route(path: '/PassiveDCEquipment/{id}', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\UpdateRoute(
        schema_name: 'PassiveDCEquipment',
        description: 'Update an existing passive DC equipment'
    )]
    public function updatePassiveDCEquipment(Request $request): Response
    {
        return ResourceAccessor::updateBySchema($this->getKnownSchema('PassiveDCEquipment', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/PassiveDCEquipment/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\DeleteRoute(
        schema_name: 'PassiveDCEquipment',
        description: 'Delete a passive DC equipment',
    )]
    public function deletePassiveDCEquipment(Request $request): Response
    {
        return ResourceAccessor::deleteBySchema($this->getKnownSchema('PassiveDCEquipment', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Cable', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(schema_name: 'Cable')]
    public function searchCables(Request $request): Response
    {
        return ResourceAccessor::searchBySchema($this->getKnownSchema('Cable', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/Cable/{id}', methods: ['GET'], requirements: ['id' => '\d+'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(schema_name: 'Cable')]
    public function getCable(Request $request): Response
    {
        return ResourceAccessor::getOneBySchema($this->getKnownSchema('Cable', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Cable', methods: ['POST'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\CreateRoute(schema_name: 'Cable')]
    public function createCable(Request $request): Response
    {
        return ResourceAccessor::createBySchema($this->getKnownSchema('Cable', $this->getAPIVersion($request)), $request->getParameters(), [self::class, 'getCable']);
    }

    #[Route(path: '/Cable/{id}', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\UpdateRoute('Cable')]
    public function updateCable(Request $request): Response
    {
        return ResourceAccessor::updateBySchema($this->getKnownSchema('Cable', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Cable/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\DeleteRoute('Cable')]
    public function deleteCable(Request $request): Response
    {
        return ResourceAccessor::deleteBySchema($this->getKnownSchema('Cable', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Socket', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(schema_name: 'Socket')]
    public function searchSockets(Request $request): Response
    {
        return ResourceAccessor::searchBySchema($this->getKnownSchema('Socket', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/Socket/{id}', methods: ['GET'], requirements: ['id' => '\d+'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(schema_name: 'Socket')]
    public function getSocket(Request $request): Response
    {
        return ResourceAccessor::getOneBySchema($this->getKnownSchema('Socket', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Socket', methods: ['POST'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\CreateRoute(schema_name: 'Socket')]
    public function createSocket(Request $request): Response
    {
        return ResourceAccessor::createBySchema($this->getKnownSchema('Socket', $this->getAPIVersion($request)), $request->getParameters(), [self::class, 'getSocket']);
    }

    #[Route(path: '/Socket/{id}', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\UpdateRoute(schema_name: 'Socket')]
    public function updateSocket(Request $request): Response
    {
        return ResourceAccessor::updateBySchema($this->getKnownSchema('Socket', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Socket/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\DeleteRoute(schema_name: 'Socket')]
    public function deleteSocket(Request $request): Response
    {
        return ResourceAccessor::deleteBySchema($this->getKnownSchema('Socket', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Software/{software_id}/Version', methods: ['GET'], requirements: ['software_id' => '\d+'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(
        schema_name: 'SoftwareVersion',
        description: 'List or search software versions'
    )]
    public function searchSoftwareVersions(Request $request): Response
    {
        $filters = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filters .= ';software.id==' . $request->getAttribute('software_id');
        $request->setParameter('filter', $filters);
        return ResourceAccessor::searchBySchema($this->getKnownSchema('SoftwareVersion', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/Software/{software_id}/Version/{id}', methods: ['GET'], requirements: [
        'software_id' => '\d+',
        'id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(
        schema_name: 'SoftwareVersion',
        description: 'Get an existing software version'
    )]
    public function getSoftwareVersion(Request $request): Response
    {
        $filters = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filters .= ';software.id==' . $request->getAttribute('software_id');
        $request->setParameter('filter', $filters);
        return ResourceAccessor::getOneBySchema($this->getKnownSchema('SoftwareVersion', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Software/{software_id}/Version', methods: ['POST'], requirements: ['software_id' => '\d+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\CreateRoute(
        schema_name: 'SoftwareVersion',
        description: 'Create a new software version'
    )]
    public function createSoftwareVersion(Request $request): Response
    {
        $request->setParameter('software', $request->getAttribute('software_id'));
        return ResourceAccessor::createBySchema($this->getKnownSchema('SoftwareVersion', $this->getAPIVersion($request)), $request->getParameters(), [
            self::class, 'getSoftwareVersion',
        ], [
            'mapped' => [
                'software_id' => $request->getAttribute('software_id'),
            ],
        ]);
    }

    #[Route(path: '/Software/{software_id}/Version/{id}', methods: ['PATCH'], requirements: [
        'software_id' => '\d+',
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\UpdateRoute(
        schema_name: 'SoftwareVersion',
        description: 'Update an existing software version'
    )]
    public function updateSoftwareVersion(Request $request): Response
    {
        return ResourceAccessor::updateBySchema($this->getKnownSchema('SoftwareVersion', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Software/{software_id}/Version/{id}', methods: ['DELETE'], requirements: [
        'software_id' => '\d+',
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\DeleteRoute(
        schema_name: 'SoftwareVersion',
        description: 'Delete a software version',
    )]
    public function deleteSoftwareVersion(Request $request): Response
    {
        return ResourceAccessor::deleteBySchema($this->getKnownSchema('SoftwareVersion', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }
}
