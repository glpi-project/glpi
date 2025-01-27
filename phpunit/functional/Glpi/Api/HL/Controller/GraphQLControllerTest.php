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

namespace tests\units\Glpi\Api\HL\Controller;

use Glpi\Http\Request;
use Glpi\OAuth\Server;

class GraphQLControllerTest extends \HLAPITestCase
{
    public function testGraphQLListSchemas()
    {
        $this->login();

        $request = new Request('POST', '/GraphQL', [], 'query { __schema { types { name } } }');
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->status(fn ($status) => $this->assertEquals(200, $status))
                ->jsonContent(function ($content) {
                    $this->assertGreaterThan(150, $content['data']['__schema']['types']);
                    $types = $content['data']['__schema']['types'];
                    $some_expected = ['Computer', 'Ticket', 'User', 'PrinterModel', 'FirmwareType'];
                    $found = [];
                    foreach ($types as $type) {
                        $this->assertArrayNotHasKey('description', $type);
                        $this->assertArrayNotHasKey('fields', $type);
                        $this->assertNotEmpty($type['name']);
                        if (in_array($type['name'], $some_expected, true)) {
                            $found[] = $type['name'];
                        }
                    }
                    $this->assertCount(count($some_expected), $found);
                });
        });

        $request = new Request('POST', '/GraphQL', [], 'query { __schema { types { name description } } }');
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->status(fn ($status) => $this->assertEquals(200, $status))
                ->jsonContent(function ($content) {
                    $types = $content['data']['__schema']['types'];
                    foreach ($types as $type) {
                        $this->assertArrayHasKey('name', $type);
                        $this->assertArrayHasKey('description', $type);
                        $this->assertArrayNotHasKey('fields', $type);
                    }
                });
        });

        $request = new Request('POST', '/GraphQL', [], 'query { __schema { types { name fields { type { name } } } } }');
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->status(fn ($status) => $this->assertEquals(200, $status))
                ->jsonContent(function ($content) {
                    $types = $content['data']['__schema']['types'];
                    foreach ($types as $type) {
                        $this->assertArrayHasKey('name', $type);
                        $this->assertArrayNotHasKey('description', $type);
                        $this->assertArrayHasKey('fields', $type);
                    }
                });
        });
    }

    public function testGetComputer()
    {
        $this->login();

        $request = new Request('POST', '/GraphQL', [], 'query { Computer(id: 1) { id name } }');
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->status(fn ($status) => $this->assertEquals(200, $status))
                ->jsonContent(function ($content) {
                    $this->assertCount(1, $content['data']);
                    $this->assertCount(1, $content['data']['Computer']);
                    $this->assertEquals(1, $content['data']['Computer'][0]['id']);
                    $this->assertEquals('_test_pc01', $content['data']['Computer'][0]['name']);
                });
        });
    }

    public function testGetComputers()
    {
        $this->login();

        $request = new Request('POST', '/GraphQL', [], 'query { Computer { id name } }');
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->status(fn ($status) => $this->assertEquals(200, $status))
                ->jsonContent(function ($content) {
                    $this->assertCount(1, $content['data']);
                    $this->assertGreaterThan(2, count($content['data']['Computer']));
                });
        });
    }

    public function testGetComputersWithFilter()
    {
        $this->login();

        $request = new Request('POST', '/GraphQL', [], 'query { Computer(filter: "name=like=\'*_test_pc*\'") { id name } }');
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->status(fn ($status) => $this->assertEquals(200, $status))
                ->jsonContent(function ($content) {
                    $this->assertCount(1, $content['data']);
                    $this->assertCount(9, $content['data']['Computer']);
                });
        });
    }

    public function testFullSchemaReplacement()
    {
        $this->login();
        $this->loginWeb();

        // Create some data just to ensure there is something to return
        $printer_model = new \PrinterModel();
        $this->assertGreaterThan(0, $printer_model->add([
            'name' => '_test_printer_model',
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true)
        ]));
        $this->assertGreaterThan(0, (new \CartridgeItem_PrinterModel())->add([
            'cartridgeitems_id' => \getItemByTypeName(\CartridgeItem::class, '_test_cartridgeitem01', true),
            'printermodels_id'  => $printer_model->getID()
        ]));

        // product_number is not available this way via the REST API, but should be available here as the partial schema gets replaced by the full schema
        $request = new Request('POST', '/GraphQL', [], 'query { CartridgeItem { id name printer_models { name product_number } } }');
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->status(fn ($status) => $this->assertEquals(200, $status))
                ->jsonContent(function ($content) {
                    $this->assertCount(1, $content['data']);
                    $this->assertArrayHasKey('id', $content['data']['CartridgeItem'][0]);
                    $this->assertArrayHasKey('name', $content['data']['CartridgeItem'][0]);
                    $this->assertGreaterThan(0, count($content['data']['CartridgeItem'][0]['printer_models']));
                    $this->assertArrayHasKey('name', $content['data']['CartridgeItem'][0]['printer_models'][0]);
                    $this->assertArrayHasKey('product_number', $content['data']['CartridgeItem'][0]['printer_models'][0]);
                });
        });
    }

    /**
     * Tests a case where there are scalar joins inside an already-joined field (status in this case).
     * @return void
     */
    public function testGetStateVisibilities()
    {
        $state = new \State();
        $this->assertGreaterThan(0, $states_id = $state->add([
            'name' => __FUNCTION__,
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            'is_visible_computer' => 1,
            'is_visible_monitor' => 0
        ]));
        $computer = new \Computer();
        $this->assertGreaterThan(0, $computers_id = $computer->add([
            'name' => __FUNCTION__,
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            'states_id' => $states_id
        ]));

        $this->login();
        $request = new Request('POST', '/GraphQL', [], <<<GRAPHQL
            query {
                Computer(id: $computers_id) {
                    id
                    name
                    status {
                        name
                        visibilities {
                            computer monitor
                        }
                    }
                }
            }
GRAPHQL);

        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->status(fn ($status) => $this->assertEquals(200, $status))
                ->jsonContent(function ($content) {
                    $this->assertCount(1, $content['data']);
                    $this->assertCount(1, $content['data']['Computer']);
                    $this->assertArrayHasKey('id', $content['data']['Computer'][0]);
                    $this->assertArrayHasKey('name', $content['data']['Computer'][0]);
                    $this->assertArrayHasKey('status', $content['data']['Computer'][0]);
                    $this->assertArrayHasKey('name', $content['data']['Computer'][0]['status']);
                    $this->assertTrue($content['data']['Computer'][0]['status']['visibilities']['computer']);
                    $this->assertFalse($content['data']['Computer'][0]['status']['visibilities']['monitor']);
                });
        });
    }
}
