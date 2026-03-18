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

namespace tests\units\Glpi\Api\HL\Controller;

use Computer;
use Glpi\Api\HL\Controller\NetworkController;
use Glpi\Http\Request;
use Glpi\Tests\HLAPICallAsserter;
use Glpi\Tests\HLAPITestCase;
use IPAddress;
use IPNetwork;
use NetworkName;
use NetworkPort;
use NetworkPortInstantiation;

class NetworkControllerTest extends HLAPITestCase
{
    public function testAutoSearch()
    {
        $types = NetworkController::getNetworkEndpointTypes23();

        $network_name = $this->createItem(NetworkName::class, [
            'name' => 'test-network-name',
            'itemtype' => 'Computer',
            'items_id' => getItemByTypeName('Computer', '_test_pc01', true),
            'entities_id' => $this->getTestRootEntity(true),
        ])->getID();

        foreach ($types as $type) {
            if (is_subclass_of($type, NetworkPortInstantiation::class) || $type === 'IPAddress' || $type === 'IPNetwork') {
                // These types cannot be tested with autoTestSearch
                continue;
            }
            $specific_dataset = [[], [], []];
            if ($type === 'NetworkAlias') {
                $specific_dataset = [
                    [
                        'name' => 'test-network-alias',
                        'network_name' => $network_name
                    ],
                    [
                        'name' => 'test-network-alias2',
                        'network_name' => $network_name
                    ],
                    [
                        'name' => 'test-network-alias3',
                        'network_name' => $network_name
                    ],
                ];
            } elseif ($type === 'FQDN') {
                $specific_dataset = [
                    ['fqdn' => 'test-fqdn'],
                    ['fqdn' => 'test-fqdn2'],
                    ['fqdn' => 'test-fqdn3'],
                ];
            } elseif ($type === 'NetworkName') {
                $specific_dataset = [
                    ['name' => 'network-name'],
                    ['name' => 'network-name2'],
                    ['name' => 'network-name3'],
                ];
            }
            $this->api->autoTestSearch(
                endpoint: "/Network/$type",
                dataset: [
                    [
                        'itemtype' => 'Computer',
                        'items_id' => getItemByTypeName('Computer', '_test_pc01', true),
                    ] + $specific_dataset[0],
                    [
                        'itemtype' => 'Computer',
                        'items_id' => getItemByTypeName('Computer', '_test_pc02', true),
                    ] + $specific_dataset[1],
                    [
                        'itemtype' => 'Computer',
                        'items_id' => getItemByTypeName('Computer', '_test_pc03', true),
                    ] + $specific_dataset[2],
                ],
            );
        }
    }

    public function testAutoSearchIPAddress()
    {
        $network_name = $this->createItem(NetworkName::class, [
            'name' => 'test-network-name',
            'itemtype' => 'Computer',
            'items_id' => getItemByTypeName('Computer', '_test_pc01', true),
            'entities_id' => $this->getTestRootEntity(true),
        ])->getID();
        $this->createItem(IPAddress::class, [
            'entities_id' => $this->getTestRootEntity(true),
            'name' => '10.9.45.32',
            'version' => 4,
            'itemtype' => NetworkName::class,
            'items_id' => $network_name,
        ]);
        $this->createItem(IPAddress::class, [
            'entities_id' => $this->getTestRootEntity(true),
            'name' => '10.9.45.64',
            'version' => 4,
            'itemtype' => NetworkName::class,
            'items_id' => $network_name,
        ]);
        $this->createItem(IPAddress::class, [
            'entities_id' => $this->getTestRootEntity(true),
            'name' => '2001:db8:85a3::8a2e:1370:7334',
            'version' => 6,
            'itemtype' => NetworkName::class,
            'items_id' => $network_name,
        ]);

        $this->api->call(new Request('GET', '/Network/IPAddress'),  function ($call) {
            /** @var HLAPICallAsserter $call */
            $call->response->isUnauthorizedError();
        }, false);

        $this->login();
        $this->api->call(new Request('GET', '/Network/IPAddress'),  function ($call) {
            /** @var HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $ips = array_column($content, 'name');
                    $this->assertContains('10.9.45.32', $ips);
                    $this->assertContains('10.9.45.64', $ips);
                    $this->assertContains('2001:db8:85a3::8a2e:1370:7334', $ips);
                });
        });
    }

    public function testAutoSearchIPNetwork()
    {
        $this->createItem(IPNetwork::class, [
            'entities_id' => $this->getTestRootEntity(true),
            'name' => 'test-network',
            'version' => 4,
            'network' => '10.9.0.0 / 255.255.0.0',
            'gateway' => '10.9.0.1',
        ]);
        $this->createItem(IPNetwork::class, [
            'entities_id' => $this->getTestRootEntity(true),
            'name' => 'test-network2',
            'version' => 4,
            'network' => '10.10.0.0 / 255.255.0.0',
            'gateway' => '10.10.0.1',
        ]);
        $this->createItem(IPNetwork::class, [
            'entities_id' => $this->getTestRootEntity(true),
            'name' => 'test-network3',
            'version' => 4,
            'network' => '10.11.0.0 / 255.255.0.0',
            'gateway' => '10.11.0.1',
        ]);

        $this->api->call(new Request('GET', '/Network/IPNetwork'),  function ($call) {
            /** @var HLAPICallAsserter $call */
            $call->response->isUnauthorizedError();
        }, false);

        $this->login();
        $this->api->call(new Request('GET', '/Network/IPNetwork'),  function ($call) {
            /** @var HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $addresses = array_column($content, 'address');
                    $this->assertContains('10.9.0.0', $addresses);
                    $this->assertContains('10.10.0.0', $addresses);
                    $this->assertContains('10.11.0.0', $addresses);
                });
        });
    }

    private function getCreateParamsForType(string $type, int $network_name, int $network_port): array
    {
        $create_params = [];

        $itemtype = NetworkController::getKnownSchemas('2.3.0')[$type]['x-itemtype'];

        if ($type === 'NetworkPort') {
            $create_params = [
                'itemtype' => 'Computer',
                'items_id' => getItemByTypeName('Computer', '_test_pc01', true),
            ];
        } elseif ($type === 'NetworkAlias') {
            $create_params = [
                'name' => 'test-network-alias',
                'network_name' => $network_name
            ];
        } elseif ($type === 'FQDN') {
            $create_params = ['fqdn' => 'test-fqdn'];
        } elseif ($type === 'NetworkName') {
            $create_params = ['name' => 'network-name'];
        } elseif (is_subclass_of($itemtype, NetworkPortInstantiation::class)) {
            $create_params = ['network_port' => $network_port];
        } elseif ($type === 'IPAddress') {
            $create_params = [
                'name' => '10.9.45.32',
                'version' => 4,
                'itemtype' => NetworkName::class,
                'items_id' => $network_name,
            ];
        } elseif ($type === 'IPNetwork') {
            $create_params = [
                'name' => 'test-network',
                'address' => '10.9.0.0',
                'version' => 4,
                'netmask' => '255.255.0.0',
                'gateway' => '10.9.0.1',
            ];
        }

        return $create_params;
    }

    public function testAutoCRUD()
    {
        $types = NetworkController::getNetworkEndpointTypes23();

        $network_name = $this->createItem(NetworkName::class, [
            'name' => 'test-network-name',
            'itemtype' => 'Computer',
            'items_id' => getItemByTypeName('Computer', '_test_pc01', true),
            'entities_id' => $this->getTestRootEntity(true),
        ])->getID();
        $network_port = $this->createItem(NetworkPort::class, [
            'name' => 'test-network-port',
            'itemtype' => 'Computer',
            'items_id' => getItemByTypeName('Computer', '_test_pc01', true),
            'entities_id' => $this->getTestRootEntity(true),
        ])->getID();

        foreach ($types as $type) {
            $update_params = [];
            // these types have no fields that can be changed
            $skip_update_test = $type === 'NetworkPortDialup' || $type === 'NetworkPortLocal';

            $this->api->autoTestCRUD(
                endpoint: "/Network/$type",
                create_params: $this->getCreateParamsForType($type, $network_name, $network_port),
                update_params: $update_params,
                extra_options: [
                    'skip_update_test' => $skip_update_test,
                ]
            );
        }
    }

//    public function testAutoCRUDNoRights()
//    {
//
//        $network_name = $this->createItem(NetworkName::class, [
//            'name' => 'test-network-name',
//            'itemtype' => 'Computer',
//            'items_id' => getItemByTypeName('Computer', '_test_pc01', true),
//            'entities_id' => $this->getTestRootEntity(true),
//        ])->getID();
//        $network_port = $this->createItem(NetworkPort::class, [
//            'name' => 'test-network-port',
//            'itemtype' => 'Computer',
//            'items_id' => getItemByTypeName('Computer', '_test_pc01', true),
//            'entities_id' => $this->getTestRootEntity(true),
//        ])->getID();
//
//        $types = NetworkController::getNetworkEndpointTypes23();
//        foreach ($types as $type) {
//            $this->login();
//            $itemtype = NetworkController::getKnownSchemas('2.3.0')[$type]['x-itemtype'];
//
//            $create_request = new Request('POST', "/Network/$type");
//            $create_params = $this->getCreateParamsForType($type, $network_name, $network_port);
//            foreach ($create_params as $key => $value) {
//                $create_request->setParameter($key, $value);
//            }
//
//            $items_id = null;
//            $this->api->call($create_request, function ($call) use (&$items_id) {
//                /** @var HLAPICallAsserter $call */
//                $call->response
//                    ->isOK()
//                    ->jsonContent(function ($content) use (&$items_id) {
//                        $this->assertArrayHasKey('id', $content);
//                        $items_id = $content['id'];
//                    });
//            });
//
//            $deny_read = null;
//            if ($type === 'NetworkPort') {
//                $deny_read = static function () {
//                    $_SESSION['glpiactiveprofile'][NetworkPort::$rightname] &= ~READ;
//                    $_SESSION['glpiactiveprofile'][NetworkName::$rightname] &= ~READ;
//                };
//            }
//
//            $this->api->autoTestCRUDNoRights(
//                endpoint: "/Network/$type",
//                itemtype: $itemtype,
//                items_id: (int) $items_id,
//                deny_read: $deny_read,
//                create_params: $create_params,
//            );
//        }
//    }
}
