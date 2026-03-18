<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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
use FQDN;
use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\Middleware\ResultFormatterMiddleware;
use Glpi\Api\HL\ResourceAccessor;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\RouteVersion;
use Glpi\Http\Request;
use Glpi\Http\Response;
use IPAddress;
use IPNetwork;
use Item_DeviceNetworkCard;
use NetworkAlias;
use NetworkInterface;
use NetworkName;
use NetworkPort;
use NetworkPort_NetworkPort;
use NetworkPort_Vlan;
use NetworkPortAggregate;
use NetworkPortAlias;
use NetworkPortConnectionLog;
use NetworkPortDialup;
use NetworkPortEthernet;
use NetworkPortFiberchannel;
use NetworkPortFiberchannelType;
use NetworkPortInstantiation;
use NetworkPortLocal;
use NetworkPortMetrics;
use NetworkPortWifi;
use Vlan;
use WifiNetwork;

#[Route(path: '/Network', priority: 1, tags: ['Network'])]
final class NetworkController extends AbstractController
{
    protected static function getRawKnownSchemas(): array
    {
        $schemas = [];

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
                'itemtype' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'x-version-introduced' => '2.2.0',
                ],
                'items_id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'x-version-introduced' => '2.2.0',
                ],
                'instantiation_type' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'x-version-introduced' => '2.2.0',
                    'enum' => [
                        'NetworkPortEthernet', 'NetworkPortWifi', 'NetworkPortAggregate', 'NetworkPortAlias',
                        'NetworkPortDialup', 'NetworkPortLocal', 'NetworkPortFiberchannel',
                    ],
                ],
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
                'vlans' => [
                    'type' => Doc\Schema::TYPE_ARRAY,
                    'x-version-introduced' => '2.3.0',
                    'items' => [
                        'type' => Doc\Schema::TYPE_OBJECT,
                        'x-full-schema' => 'VLAN',
                        'x-join' => [
                            'table' => Vlan::getTable(),
                            'fkey' => Vlan::getForeignKeyField(),
                            'field' => 'id',
                            'primary-property' => 'id',
                            'ref-join' => [
                                'table' => NetworkPort_Vlan::getTable(),
                                'fkey' => 'id',
                                'field' => NetworkPort::getForeignKeyField(),
                            ],
                        ],
                        'properties' => [
                            'id' => [
                                'type' => Doc\Schema::TYPE_INTEGER,
                                'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                                'readOnly' => true,
                            ],
                        ]
                    ]
                ],
            ],
        ];

        $schemas['NetworkPortEthernet'] = [
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => NetworkPortEthernet::class,
            'x-version-introduced' => '2.2',
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'network_port' => self::getDropdownTypeSchema(class: NetworkPort::class, full_schema: 'NetworkPort'),
                'network_card' => self::getDropdownTypeSchema(class: Item_DeviceNetworkCard::class, name_field: 'serial', full_schema: 'NetworkCardItem'),
                'type' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'enum' => ['', 'T', 'SX', 'LX'],
                    'description' => <<<EOT
                        Type of Ethernet port.
                        - '': Not specified
                        - 'T': Twisted Pair (RJ-45)
                        - 'SX': Multimode fiber
                        - 'LX': Single mode fiber
EOT,
                ],
                'speed' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT32,
                    'description' => 'Speed of the Ethernet port in Mbps',
                    'default' => 10,
                ],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['NetworkPortWifi'] = [
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => NetworkPortWifi::class,
            'x-version-introduced' => '2.2',
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'network_port' => self::getDropdownTypeSchema(class: NetworkPort::class, full_schema: 'NetworkPort'),
                'network_card' => self::getDropdownTypeSchema(class: Item_DeviceNetworkCard::class, name_field: 'serial', full_schema: 'NetworkCardItem'),
                'wifinetwork' => self::getDropdownTypeSchema(class: WifiNetwork::class, full_schema: 'WifiNetwork'),
                'version' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'enum' => ['', 'a', 'b', 'a/b', 'a/b/g', 'a/b/g/n', 'a/b/g/n/y', 'ac', 'ax', 'be', 'bn'],
                    'description' => <<<EOT
                        Wi-Fi version.
                        - '': Not specified
                        - 'a': 802.11a
                        - 'b': 802.11b
                        - 'a/b': 802.11a/b
                        - 'a/b/g': 802.11a/b/g
                        - 'a/b/g/n': 802.11a/b/g/n
                        - 'a/b/g/n/y': 802.11a/b/g/n/y
                        - 'ac': 802.11ac (Wi-Fi 5)
                        - 'ax': 802.11ax (Wi-Fi 6)
                        - 'be': 802.11be (Wi-Fi 7)
                        - 'bn': 802.11bn (Wi-Fi 8)
EOT,
                ],
                'mode' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'enum' => ['', 'ad-hoc', 'managed', 'master', 'repeater', 'secondary', 'monitor', 'auto'],
                    'description' => <<<EOT
                        Wi-Fi mode.
                        - '': Not specified
                        - 'ad-hoc': Ad-Hoc mode
                        - 'managed': Managed mode
                        - 'master': Master mode
                        - 'repeater': Repeater mode
                        - 'secondary': Secondary mode
                        - 'monitor': Monitor mode
                        - 'auto': Automatic mode
EOT,
                ],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['NetworkPortAggregate'] = [
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => NetworkPortAggregate::class,
            'x-version-introduced' => '2.2',
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'network_port' => self::getDropdownTypeSchema(class: NetworkPort::class, full_schema: 'NetworkPort'),
                'network_port_list' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'x-field' => 'networkports_id_list',
                    'description' => 'JSON-encoded array of Network Port IDs that are part of this aggregate port',
                ],
                //TODO add network_ports property that uses something like JSON_TABLE to properly join the related ports. May need changes to the search code to support it.
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['NetworkPortAlias'] = [
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => NetworkPortAlias::class,
            'x-version-introduced' => '2.2',
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'network_port' => self::getDropdownTypeSchema(class: NetworkPort::class, full_schema: 'NetworkPort'),
                'aliased_network_port' => self::getDropdownTypeSchema(class: NetworkPort::class, field: 'networkports_id_alias', full_schema: 'NetworkPort'),
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['NetworkPortDialup'] = [
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => NetworkPortDialup::class,
            'x-version-introduced' => '2.2',
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'network_port' => self::getDropdownTypeSchema(class: NetworkPort::class, full_schema: 'NetworkPort'),
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['NetworkPortLocal'] = [
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => NetworkPortLocal::class,
            'x-version-introduced' => '2.2',
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'network_port' => self::getDropdownTypeSchema(class: NetworkPort::class, full_schema: 'NetworkPort'),
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['NetworkPortFiberchannel'] = [
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => NetworkPortFiberchannel::class,
            'x-version-introduced' => '2.2',
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'network_port' => self::getDropdownTypeSchema(class: NetworkPort::class, full_schema: 'NetworkPort'),
                'network_card' => self::getDropdownTypeSchema(class: Item_DeviceNetworkCard::class, name_field: 'serial', full_schema: 'NetworkCardItem'),
                'type' => self::getDropdownTypeSchema(class: NetworkPortFiberchannelType::class, full_schema: 'NetworkPortFiberchannelType'),
                'wwn' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 50],
                'speed' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT32,
                    'description' => 'Speed of the Fiber Channel port in Mbps',
                    'default' => 10,
                ],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['IPAddress'] = [
            'x-version-introduced' => '2.3',
            'x-itemtype' => IPAddress::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                'itemtype' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 100, 'required' => true],
                'items_id' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64, 'required' => true],
                'version' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT32,
                    'enum' => [0, 4, 6]
                ],
                'name' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'description' => 'The IP address in textual format.',
                    'maxLength' => 255
                ],
                'binary_0' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'description' => 'The first part of the IP address in binary format. This can be useful for sorting.',
                    'readOnly' => true,
                ],
                'binary_1' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'description' => 'The second part of the IP address in binary format. This can be useful for sorting.',
                    'readOnly' => true,
                ],
                'binary_2' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'description' => 'The third part of the IP address in binary format. This can be useful for sorting.',
                    'readOnly' => true,
                ],
                'binary_3' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'description' => 'The fourth part of the IP address in binary format. This can be useful for sorting.',
                    'readOnly' => true,
                ],
                'is_deleted' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                'is_dynamic' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                'mainitemtype' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'mainitems_id' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64],
            ],
        ];

        $schemas['IPNetwork'] = [
            'x-version-introduced' => '2.3',
            'x-itemtype' => IPNetwork::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                'ip_network' => self::getDropdownTypeSchema(class: IPNetwork::class, full_schema: 'IPNetwork'),
                'completename' => ['type' => Doc\Schema::TYPE_STRING, 'readOnly' => true],
                'level' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32, 'readOnly' => true],
                'addressable' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'version' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT32,
                    'enum' => [0, 4, 6]
                ],
                'name' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'maxLength' => 255,
                ],
                'address' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'description' => 'The network address in textual format.',
                    'maxLength' => 40,
                ],
                'address_0' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'description' => 'The first part of the network address in binary format. This can be useful for sorting.',
                    'readOnly' => true,
                ],
                'address_1' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'description' => 'The second part of the network address in binary format. This can be useful for sorting.',
                    'readOnly' => true,
                ],
                'address_2' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'description' => 'The third part of the network address in binary format. This can be useful for sorting.',
                    'readOnly' => true,
                ],
                'address_3' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'description' => 'The fourth part of the network address in binary format. This can be useful for sorting.',
                    'readOnly' => true,
                ],
                'netmask' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'description' => 'The netmask in textual format.',
                    'maxLength' => 40,
                ],
                'netmask_0' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'description' => 'The first part of the netmask in binary format. This can be useful for sorting.',
                    'readOnly' => true,
                ],
                'netmask_1' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'description' => 'The second part of the netmask in binary format. This can be useful for sorting.',
                    'readOnly' => true,
                ],
                'netmask_2' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'description' => 'The third part of the netmask in binary format. This can be useful for sorting.',
                    'readOnly' => true,
                ],
                'netmask_3' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'description' => 'The fourth part of the netmask in binary format. This can be useful for sorting.',
                    'readOnly' => true,
                ],
                'gateway' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'description' => 'The gateway address in textual format.',
                    'maxLength' => 40,
                ],
                'gateway_0' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'description' => 'The first part of the gateway address in binary format. This can be useful for sorting.',
                    'readOnly' => true,
                ],
                'gateway_1' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'description' => 'The second part of the gateway address in binary format. This can be useful for sorting.',
                    'readOnly' => true,
                ],
                'gateway_2' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'description' => 'The third part of the gateway address in binary format. This can be useful for sorting.',
                    'readOnly' => true,
                ],
                'gateway_3' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'description' => 'The fourth part of the gateway address in binary format. This can be useful for sorting.',
                    'readOnly' => true,
                ],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['NetworkAlias'] = [
            'x-version-introduced' => '2.3',
            'x-itemtype' => NetworkAlias::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                'network_name' => self::getDropdownTypeSchema(class: NetworkName::class, full_schema: 'NetworkName'),
                'name' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'maxLength' => 63,
                    'pattern' => NetworkAlias::FQDN_LABEL_PATTERN,
                ],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'fqdn' => self::getDropdownTypeSchema(class: FQDN::class, full_schema: 'FQDN'),
            ],
        ];

        $schemas['FQDN'] = [
            'x-version-introduced' => '2.3',
            'x-itemtype' => FQDN::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'fqdn' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255, 'required' => true],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['NetworkInterface'] = [
            'x-version-introduced' => '2.3',
            'x-itemtype' => NetworkInterface::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
            ],
        ];

        $schemas['NetworkName'] = [
            'x-version-introduced' => '2.3',
            'x-itemtype' => NetworkName::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                'itemtype' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 100],
                'items_id' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64],
                'name' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'maxLength' => 63,
                    'pattern' => NetworkName::FQDN_LABEL_PATTERN,
                ],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'fqdn' => self::getDropdownTypeSchema(class: FQDN::class, full_schema: 'FQDN'),
                'ip_network' => self::getDropdownTypeSchema(class: IPNetwork::class, full_schema: 'IPNetwork'),
                'is_deleted' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                'is_dynamic' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['NetworkPortConnectionLog'] = [
            'x-version-introduced' => '2.3',
            'x-itemtype' => NetworkPortConnectionLog::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'date' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'connected' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'source' => self::getDropdownTypeSchema(class: NetworkPort::class, field: 'networkports_id_source', full_schema: 'NetworkPort'),
                'destination' => self::getDropdownTypeSchema(class: NetworkPort::class, field: 'networkports_id_destination', full_schema: 'NetworkPort'),
            ],
        ];

        $schemas['NetworkPortMetrics'] = [
            'x-version-introduced' => '2.3',
            'x-itemtype' => NetworkPortMetrics::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'date' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'if_in_bytes' => ['x-field' => 'ifinbytes', 'type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64],
                'if_out_bytes' => ['x-field' => 'ifoutbytes', 'type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64],
                'if_in_errors' => ['x-field' => 'ifinerrors', 'type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64],
                'if_out_errors' => ['x-field' => 'ifouterrors', 'type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64],
                'network_port' => self::getDropdownTypeSchema(class: NetworkPort::class, field: 'networkports_id', full_schema: 'NetworkPort'),
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['VLAN'] = [
            'x-version-introduced' => '2.3',
            'x-itemtype' => Vlan::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'tag' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT32,
                    'description' => 'The VLAN tag (ID)',
                    'minimum' => 0,
                    'maximum' => 4095,
                ],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['NetworkPort_NetworkPort'] = [
            'x-version-introduced' => '2.3',
            'x-itemtype' => NetworkPort_NetworkPort::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'network_port_1' => self::getDropdownTypeSchema(class: NetworkPort::class, field: 'networkports_id_1', full_schema: 'NetworkPort'),
                'network_port_2' => self::getDropdownTypeSchema(class: NetworkPort::class, field: 'networkports_id_2', full_schema: 'NetworkPort'),
            ],
        ];
        return $schemas;
    }

    /**
     * @return string[]
     */
    public static function  getNetworkEndpointTypes23(): array
    {
        return [
            'NetworkPort', 'NetworkPortEthernet', 'NetworkPortWifi', 'NetworkPortAggregate', 'NetworkPortAlias',
            'NetworkPortDialup', 'NetworkPortLocal', 'NetworkPortFiberchannel', 'IPAddress', 'IPNetwork',
            'NetworkAlias', 'FQDN', 'NetworkInterface', 'NetworkName', 'VLAN',
        ];
    }

    #[Route(path: '/{itemtype}', methods: ['GET'], requirements: [
        'itemtype' => [self::class, 'getNetworkEndpointTypes23'],
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\SearchRoute(schema_name: '{itemtype}')]
    public function searchItem23(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::searchBySchema(
            schema: $this->getKnownSchema($itemtype, $this->getAPIVersion($request)),
            request_params: $request->getParameters()
        );
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['GET'], requirements: [
        'itemtype' => [self::class, 'getNetworkEndpointTypes23'],
        'id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\GetRoute(schema_name: '{itemtype}')]
    public function getItem23(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        $schema = $this->getKnownSchema($itemtype, $this->getAPIVersion($request));
        $schema_itemtype = $schema['x-itemtype'];
        $id_field = is_subclass_of($schema_itemtype, NetworkPortInstantiation::class) ? 'network_port' : 'id';
        $request->setAttribute($id_field, $request->getAttribute('id'));

        return ResourceAccessor::getOneBySchema(
            schema: $schema,
            request_attrs: $request->getAttributes(),
            request_params: $request->getParameters(),
            field: $id_field,
        );
    }

    #[Route(path: '/{itemtype}', methods: ['POST'], requirements: [
        'itemtype' => [self::class, 'getNetworkEndpointTypes23'],
    ])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\CreateRoute(schema_name: '{itemtype}')]
    public function createItem23(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        $schema = $this->getKnownSchema($itemtype, $this->getAPIVersion($request));

        $extra_get_route_params = [
            'mapped' => [
                'itemtype' => $itemtype,
            ],
        ];

        return ResourceAccessor::createBySchema(
            schema: $schema,
            request_params: $request->getParameters() + ['itemtype' => $itemtype],
            get_route: [self::class, 'getItem23'],
            extra_get_route_params: $extra_get_route_params,
        );
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['PATCH'], requirements: [
        'itemtype' => [self::class, 'getNetworkEndpointTypes23'],
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\UpdateRoute(schema_name: '{itemtype}')]
    public function updateItem23(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        $schema = $this->getKnownSchema($itemtype, $this->getAPIVersion($request));
        $schema_itemtype = $schema['x-itemtype'];
        $id_field = is_subclass_of($schema_itemtype, NetworkPortInstantiation::class) ? 'network_port' : 'id';
        $request->setAttribute($id_field, $request->getAttribute('id'));

        return ResourceAccessor::updateBySchema(
            schema: $schema,
            request_attrs: $request->getAttributes(),
            request_params: $request->getParameters(),
            field: $id_field,
        );
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['DELETE'], requirements: [
        'itemtype' => [self::class, 'getNetworkEndpointTypes23'],
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\DeleteRoute(schema_name: '{itemtype}')]
    public function deleteItem23(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        $schema = $this->getKnownSchema($itemtype, $this->getAPIVersion($request));
        $schema_itemtype = $schema['x-itemtype'];
        $id_field = is_subclass_of($schema_itemtype, NetworkPortInstantiation::class) ? 'network_port' : 'id';
        $request->setAttribute($id_field, $request->getAttribute('id'));

        return ResourceAccessor::deleteBySchema(
            schema: $schema,
            request_attrs: $request->getAttributes(),
            request_params: $request->getParameters(),
            field: $id_field,
        );
    }
}
