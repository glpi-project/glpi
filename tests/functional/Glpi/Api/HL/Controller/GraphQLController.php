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

namespace tests\units\Glpi\Api\HL\Controller;

use Glpi\Http\Request;
use Glpi\OAuth\Server;

class GraphQLController extends \HLAPITestCase
{
    public function testGraphQLListSchemas()
    {
        $this->login();

        $request = new Request('POST', '/GraphQL', [], 'query { __schema { types { name } } }');
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->status(fn ($status) => $this->integer($status)->isEqualTo(200))
                ->jsonContent(function ($content) {
                    $this->array($content)->hasKey('data');
                    $this->array($content['data'])->hasKey('__schema');
                    $this->array($content['data']['__schema'])->hasKey('types');
                    $this->array($content['data']['__schema']['types'])->size->isGreaterThan(150);
                    $types = $content['data']['__schema']['types'];
                    $some_expected = ['Computer', 'Ticket', 'User', 'PrinterModel', 'FirmwareType'];
                    $found = [];
                    foreach ($types as $type) {
                        $this->array($type)->hasKey('name');
                        $this->array($type)->notHasKey('description');
                        $this->array($type)->notHasKey('fields');
                        $this->string($type['name'])->isNotEmpty();
                        if (in_array($type['name'], $some_expected, true)) {
                            $found[] = $type['name'];
                        }
                    }
                    $this->array($found)->size->isEqualTo(count($some_expected));
                });
        });

        $request = new Request('POST', '/GraphQL', [], 'query { __schema { types { name description } } }');
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->status(fn ($status) => $this->integer($status)->isEqualTo(200))
                ->jsonContent(function ($content) {
                    $types = $content['data']['__schema']['types'];
                    foreach ($types as $type) {
                        $this->array($type)->hasKey('name');
                        $this->array($type)->hasKey('description');
                        $this->array($type)->notHasKey('fields');
                    }
                });
        });

        $request = new Request('POST', '/GraphQL', [], 'query { __schema { types { name fields { type { name } } } } }');
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->status(fn ($status) => $this->integer($status)->isEqualTo(200))
                ->jsonContent(function ($content) {
                    $types = $content['data']['__schema']['types'];
                    foreach ($types as $type) {
                        $this->array($type)->hasKey('name');
                        $this->array($type)->notHasKey('description');
                        $this->array($type)->hasKey('fields');
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
                ->status(fn ($status) => $this->integer($status)->isEqualTo(200))
                ->jsonContent(function ($content) {
                    $this->array($content)->hasKey('data');
                    $this->array($content['data'])->hasSize(1);
                    $this->array($content['data']['Computer'])->hasSize(1);
                    $this->array($content['data']['Computer'][0])->hasKey('id');
                    $this->array($content['data']['Computer'][0])->hasKey('name');
                    $this->integer($content['data']['Computer'][0]['id'])->isEqualTo(1);
                    $this->string($content['data']['Computer'][0]['name'])->isEqualTo('_test_pc01');
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
                ->status(fn ($status) => $this->integer($status)->isEqualTo(200))
                ->jsonContent(function ($content) {
                    $this->array($content)->hasKey('data');
                    $this->array($content['data'])->hasSize(1);
                    $this->array($content['data']['Computer'])->size->isGreaterThan(2);
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
                ->status(fn ($status) => $this->integer($status)->isEqualTo(200))
                ->jsonContent(function ($content) {
                    $this->array($content)->hasKey('data');
                    $this->array($content['data'])->hasSize(1);
                    $this->array($content['data']['Computer'])->size->isEqualTo(9);
                });
        });
    }

    public function testFullSchemaReplacement()
    {
        $this->login();
        $this->loginWeb();

        // Create some data just to ensure there is something to return
        $printer_model = new \PrinterModel();
        $this->integer($printer_model->add([
            'name' => '_test_printer_model',
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true)
        ]))->isGreaterThan(0);
        $cartridge_item = new \CartridgeItem();
        $this->integer($cartridge_item->add([
            'name' => '_test_cartridge_item',
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true)
        ]))->isGreaterThan(0);
        $this->integer((new \CartridgeItem_PrinterModel())->add([
            'cartridgeitems_id' => $cartridge_item->getID(),
            'printermodels_id'  => $printer_model->getID()
        ]))->isGreaterThan(0);

        // product_number is not available this way via the REST API, but should be available here as the partial schema gets replaced by the full schema
        $request = new Request('POST', '/GraphQL', [], 'query { CartridgeItem { id name printer_models { name product_number } } }');
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->status(fn ($status) => $this->integer($status)->isEqualTo(200))
                ->jsonContent(function ($content) {
                    $this->array($content)->hasKey('data');
                    $this->array($content['data'])->hasSize(1);
                    $this->array($content['data']['CartridgeItem'][0])->hasKey('id');
                    $this->array($content['data']['CartridgeItem'][0])->hasKey('name');
                    $this->array($content['data']['CartridgeItem'][0])->hasKey('printer_models');
                    $this->array($content['data']['CartridgeItem'][0]['printer_models'])->size->isGreaterThan(0);
                    $this->array($content['data']['CartridgeItem'][0]['printer_models'][0])->hasKey('name');
                    $this->array($content['data']['CartridgeItem'][0]['printer_models'][0])->hasKey('product_number');
                });
        });
    }
}
