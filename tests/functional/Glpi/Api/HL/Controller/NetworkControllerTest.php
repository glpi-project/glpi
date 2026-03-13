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

use Glpi\Api\HL\Controller\NetworkController;
use Glpi\Tests\HLAPITestCase;
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
}
