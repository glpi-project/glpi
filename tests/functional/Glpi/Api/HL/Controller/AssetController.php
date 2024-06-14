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

namespace tests\units\Glpi\Api\HL\Controller;

use Glpi\Http\Request;

class AssetController extends \HLAPITestCase
{
    public function testIndex()
    {
        global $CFG_GLPI;
        $types = $CFG_GLPI['asset_types'];

        $this->login();
        $this->api->call(new Request('GET', '/Assets'), function ($call) use ($types) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($types) {
                    $this->array($content)->size->isGreaterThanOrEqualTo(count($types));
                    foreach ($content as $asset) {
                        $this->array($asset)->hasKeys(['itemtype', 'name', 'href']);
                        $this->string($asset['name'])->isNotEmpty();
                        $this->string($asset['href'])->isEqualTo('/Assets/' . $asset['itemtype']);
                    }
                });
        });
    }

    public function searchProvider()
    {
        return [
            ['schema' => 'Computer', 'filters' => [], 'expected' => ['count' => ['>=', 9]]],
            ['schema' => 'Computer', 'filters' => ['name==_test_pc1'], 'expected' => ['count' => ['=', 0]]],
            ['schema' => 'Computer', 'filters' => ['name=like=_test_pc1*'], 'expected' => ['count' => ['>=', 3]]],
            [
                'schema' => 'Computer', 'filters' => [
                    'name=like=_test_pc1*;name=like=*3*'
                ], 'expected' => ['count' => ['>=', 1]]
            ],
            [
                'schema' => 'Computer', 'filters' => [
                    '(name=like=_test_pc1*;name=like=*3*)'
                ], 'expected' => ['count' => ['>=', 1]]
            ],
            [
                'schema' => 'Computer', 'filters' => [
                    '(name=like=_test_pc1*;name=like=*3*),name==_test_pc_with_encoded_comment'
                ], 'expected' => ['count' => ['>=', 2]]
            ],
            ['schema' => 'Monitor', 'filters' => [], 'expected' => ['count' => ['>', 0]]],
            ['schema' => 'Monitor', 'filters' => ['name=="_test_monitor_1"'], 'expected' => ['count' => ['>=', 1]]],
            ['schema' => 'Monitor', 'filters' => ['name=like="_test_monitor*"'], 'expected' => ['count' => ['>=', 2]]],
            ['schema' => 'NetworkEquipment', 'filters' => [], 'expected' => ['count' => ['>', 0]]],
            ['schema' => 'Peripheral', 'filters' => [], 'expected' => ['count' => ['>', 0]]],
            ['schema' => 'Phone', 'filters' => [], 'expected' => ['count' => ['>', 0]]],
            ['schema' => 'Printer', 'filters' => [], 'expected' => ['count' => ['>', 0]]],
            ['schema' => 'SoftwareLicense', 'filters' => [], 'expected' => ['count' => ['>', 0]]],
        ];
    }

    /**
     * @dataProvider searchProvider
     */
    public function testSearch(string $schema, array $filters, array $expected)
    {
        $this->login();
        $request = new Request('GET', '/Assets/' . $schema);
        $request->setParameter('filter', $filters);
        $this->api->call($request, function ($call) use ($expected) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($expected) {
                    $this->checkSimpleContentExpect($content, $expected);
                });
        });
    }

    public function testAutoSearch()
    {
        $this->login();
        $entity = $this->getTestRootEntity(true);
        $dataset = [
            [
                'name' => 'testAutoSearch_1',
                'entity' => $entity
            ],
            [
                'name' => 'testAutoSearch_2',
                'entity' => $entity
            ],
            [
                'name' => 'testAutoSearch_3',
                'entity' => $entity
            ]
        ];
        $this->api->call(new Request('GET', '/Assets'), function ($call) use ($dataset) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($dataset) {
                    $this->array($content)->size->isGreaterThanOrEqualTo(1);
                    foreach ($content as $asset) {
                        $to_skip = ['SoftwareLicense', 'Unmanaged'];
                        if (in_array($asset['itemtype'], $to_skip, true)) {
                            continue;
                        }
                        $this->api->autoTestSearch('/Assets/' . $asset['itemtype'], $dataset);
                    }
                });
        });
    }

    protected function getItemProvider()
    {
        return [
            ['schema' => 'Computer', 'id' => getItemByTypeName('Computer', '_test_pc01', true), 'expected' => ['fields' => ['name' => '_test_pc01']]],
            ['schema' => 'Monitor', 'id' => getItemByTypeName('Monitor', '_test_monitor_1', true), 'expected' => ['fields' => ['name' => '_test_monitor_1']]],
            ['schema' => 'NetworkEquipment', 'id' => getItemByTypeName('NetworkEquipment', '_test_networkequipment_1', true), 'expected' => ['fields' => ['name' => '_test_networkequipment_1']]],
            ['schema' => 'Peripheral', 'id' => getItemByTypeName('Peripheral', '_test_peripheral_1', true), 'expected' => ['fields' => ['name' => '_test_peripheral_1']]],
            ['schema' => 'Phone', 'id' => getItemByTypeName('Phone', '_test_phone_1', true), 'expected' => ['fields' => ['name' => '_test_phone_1']]],
            ['schema' => 'Printer', 'id' => getItemByTypeName('Printer', '_test_printer_all', true), 'expected' => ['fields' => ['name' => '_test_printer_all']]],
            ['schema' => 'SoftwareLicense', 'id' => getItemByTypeName('SoftwareLicense', '_test_softlic_1', true), 'expected' => ['fields' => ['name' => '_test_softlic_1']]],
        ];
    }

    /**
     * @dataProvider getItemProvider
     */
    public function testGetItem(string $schema, int $id, array $expected)
    {
        $this->login();
        $request = new Request('GET', '/Assets/' . $schema . '/' . $id);
        $this->api->call($request, function ($call) use ($schema, $expected) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($expected) {
                    $this->checkSimpleContentExpect($content, $expected);
                })
                ->matchesSchema($schema);
        });
    }

    protected function createUpdateDeleteItemProvider()
    {
        $types = [
            'Computer', 'Monitor', 'NetworkEquipment', 'Peripheral', 'Phone', 'Printer',
            'Software', 'Rack', 'Enclosure', 'PDU', 'PassiveDCEquipment', 'Cable', 'Socket'
        ];
        foreach ($types as $type) {
            $unique_id = __FUNCTION__ . '_' . random_int(0, 10000);
            $fields = [
                'name' => $unique_id,
            ];
            if ($type !== 'Socket') {
                $fields['entity'] = getItemByTypeName('Entity', '_test_root_entity', true);
            }
            yield [
                $type, $fields
            ];
        }
    }

    /**
     * @dataProvider createUpdateDeleteItemProvider
     */
    public function testCreateUpdateDeleteItem(string $schema, array $fields)
    {
        $this->api->autoTestCRUD('/Assets/' . $schema, $fields);
    }

    public function testCRUDRackItem()
    {
        $this->loginWeb();
        // Create rack
        $rack = new \Rack();
        $rack_id = $rack->add([
            'name' => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
            'number_units' => 20
        ]);
        // Create computer
        $computer = new \Computer();
        $computer_id = $computer->add([
            'name' => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        $this->login();

        // get rack items (should be empty)
        $this->api->call(new Request('GET', '/Assets/Rack/' . $rack_id . '/Item'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->array($content)->isEmpty();
                });
        });

        // Add computer to rack
        $request = new Request('POST', '/Assets/Rack/' . $rack_id . '/Item');
        $request->setParameter('itemtype', 'Computer');
        $request->setParameter('items_id', $computer_id);
        $request->setParameter('position', 1);
        $rackitem_location = null;
        $this->api->call($request, function ($call) use (&$rackitem_location){
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->headers(function ($headers) use (&$rackitem_location) {
                    $this->array($headers)->hasKey('Location');
                    $rackitem_location = $headers['Location'];
                });
        });

        // get rack items (should contain the computer)
        $this->api->call(new Request('GET', '/Assets/Rack/' . $rack_id . '/Item'), function ($call) use ($computer_id) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($computer_id) {
                    $this->array($content)->size->isEqualTo(1);
                    $this->string($content[0]['itemtype'])->isEqualTo('Computer');
                    $this->integer($content[0]['items_id'])->isEqualTo($computer_id);
                });
        });

        //Update position
        $request = new Request('PATCH', $rackitem_location);
        $request->setParameter('position', 2);
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK();
        });

        // get specific rack item and validate the update
        $this->api->call(new Request('GET', $rackitem_location), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->checkSimpleContentExpect($content, ['fields' => ['position' => 2]]);
                });
        });

        // Delete computer from rack
        $this->api->call(new Request('DELETE', $rackitem_location), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK();
        });

        // get rack items (should be empty)
        $this->api->call(new Request('GET', '/Assets/Rack/' . $rack_id . '/Item'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->array($content)->isEmpty();
                });
        });
    }

    public function testSearchAllAssets()
    {
        $this->login();

        $request = new Request('GET', '/Assets/Global');
        $request->setParameter('filter', ['name=ilike=*_test*']);
        $request->setParameter('limit', 10000);
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->array($content)->size->isGreaterThanOrEqualTo(3);
                    $count_by_type = [];
                    // Count by the _itemtype field in each element
                    foreach ($content as $item) {
                        $count_by_type[$item['_itemtype']] = ($count_by_type[$item['_itemtype']] ?? 0) + 1;
                    }
                    $this->integer($count_by_type['Computer'])->isGreaterThanOrEqualTo(1);
                    $this->integer($count_by_type['Monitor'])->isGreaterThanOrEqualTo(1);
                    $this->integer($count_by_type['Printer'])->isGreaterThanOrEqualTo(1);
                });
        });
    }

    public function testCRUDSoftwareVersion()
    {
        $this->login();

        // Create a software
        $software = new \Software();
        $this->integer($software_id = $software->add([
            'name' => __FUNCTION__,
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
        ]));

        // Create
        $request = new Request('POST', '/Assets/Software/' . $software_id . '/Version');
        $request->setParameter('name', '1.0');
        $new_item_location = null;
        $this->api->call($request, function ($call) use ($software_id, &$new_item_location) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->headers(function ($headers) use ($software_id, &$new_item_location) {
                    $this->array($headers)->hasKey('Location');
                    $this->string($headers['Location'])->startWith("/Assets/Software/$software_id/Version/");
                    $new_item_location = $headers['Location'];
                });
        });

        // Get and verify
        $this->api->call(new Request('GET', $new_item_location), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->array($content)->hasKey('name');
                    $this->string($content['name'])->isEqualTo('1.0');
                });
        });

        // Update
        $request = new Request('PATCH', $new_item_location);
        $request->setParameter('name', '1.1');
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->checkSimpleContentExpect($content, ['fields' => ['name' => '1.1']]);
                });
        });

        // Delete
        $this->api->call(new Request('DELETE', $new_item_location), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK();
        });

        // Verify item does not exist anymore
        $this->api->call(new Request('GET', $new_item_location), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isNotFoundError();
        });
    }

    public function testDropdownTranslations()
    {
        global $CFG_GLPI;

        $this->login();
        $state = new \State();
        $this->integer($state_id = $state->add([
            'name' => 'Test',
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
        ]))->isGreaterThan(0);
        $computer = new \Computer();
        $this->integer($computer_id = $computer->add([
            'name' => 'Test',
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            'states_id' => $state_id,
        ]))->isGreaterThan(0);
        $dropdown_translation = new \DropdownTranslation();
        $this->integer($dropdown_translation->add([
            'items_id'  => $state_id,
            'itemtype'  => 'State',
            'language'  => 'fr_FR',
            'field'     => 'name',
            'value'     => 'Essai',
        ]))->isGreaterThan(0);

        $CFG_GLPI['translate_dropdowns'] = true;
        // Get and verify
        $this->api->call(new Request('GET', '/Assets/Computer/' . $computer_id), function ($call) use ($state_id) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($state_id) {
                    $this->array($content)->hasKey('status');
                    $this->array($content['status'])->hasKey('name');
                    $this->string($content['status']['name'])->isEqualTo('Test');
                });
        });
        // Change language and verify
        $request = new Request('GET', '/Assets/Computer/' . $computer_id, [
            'Accept-Language' => 'fr_FR',
        ]);
        $this->api->call($request, function ($call) use ($state_id) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($state_id) {
                    $this->array($content)->hasKey('status');
                    $this->array($content['status'])->hasKey('name');
                    $this->string($content['status']['name'])->isEqualTo('Essai');
                });
        });
    }

    public function testMissingDropdownTranslation()
    {
        global $CFG_GLPI;

        $this->login();
        $state = new \State();
        $this->integer($state_id = $state->add([
            'name' => 'Test',
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
        ]))->isGreaterThan(0);
        $computer = new \Computer();
        $this->integer($computer_id = $computer->add([
            'name' => 'Test',
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            'states_id' => $state_id,
        ]))->isGreaterThan(0);

        $CFG_GLPI['translate_dropdowns'] = true;
        // Get and verify
        $this->api->call(new Request('GET', '/Assets/Computer/' . $computer_id), function ($call) use ($state_id) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($state_id) {
                    $this->array($content)->hasKey('status');
                    $this->array($content['status'])->hasKey('name');
                    $this->string($content['status']['name'])->isEqualTo('Test');
                });
        });
        // Change language and verify the default name is returned instead of null
        $_SESSION['glpilanguage'] = 'fr_FR';
        $this->api->call(new Request('GET', '/Assets/Computer/' . $computer_id), function ($call) use ($state_id) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($state_id) {
                    $this->array($content)->hasKey('status');
                    $this->array($content['status'])->hasKey('name');
                    $this->string($content['status']['name'])->isEqualTo('Test');
                });
        });
    }

    public function testGetItemInfocom()
    {
        $this->loginWeb();
        $computers_id = $this->createItem('Computer', [
            'name' => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
        ])->getID();

        $this->login();
        $this->api->call(new Request('GET', '/Assets/Computer/' . $computers_id . '/Infocom'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isNotFoundError();
        });

        $this->createItem('Infocom', [
            'itemtype' => 'Computer',
            'items_id' => $computers_id,
            'buy_date' => '2020-01-01',
        ]);

        $this->api->call(new Request('GET', '/Assets/Computer/' . $computers_id . '/Infocom'), function ($call) use ($computers_id) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($computers_id) {
                    $this->string($content['itemtype'])->isEqualTo('Computer');
                    $this->integer($content['items_id'])->isEqualTo($computers_id);
                    $this->string($content['date_buy'])->isEqualTo('2020-01-01');
                });
        });
    }
}
