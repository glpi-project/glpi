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

use AuthLDAP;
use Computer;
use Config;
use Glpi\Api\HL\Middleware\InternalAuthMiddleware;
use Glpi\Http\Request;
use Glpi\Tests\HLAPITestCase;
use SLM;

class SetupControllerTest extends HLAPITestCase
{
    public function testIndex()
    {
        $this->login();
        $this->api->call(new Request('GET', '/Setup'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertGreaterThanOrEqual(1, count($content));
                    foreach ($content as $asset) {
                        $this->assertNotEmpty($asset['itemtype']);
                        $this->assertNotEmpty($asset['name']);
                        $this->assertStringStartsWith('/Setup/', $asset['href']);
                    }
                });
        });
    }

    public function testAutoSearch()
    {
        $this->login();
        $entity = $this->getTestRootEntity(true);
        $slm = $this->createItem(SLM::class, ['name' => 'Test SLM for AutoSearch', 'entities_id' => $entity]);

        $dataset = [
            [
                'name' => 'testAutoSearch_1',
                'entity' => $entity,
                'slm' => $slm->getID(),
                'time' => 30,
                'time_unit' => 'hour',
                'execution_time' => 45,
                'url' => 'https://example.com',
                'link' => 'https://example.com',
            ],
            [
                'name' => 'testAutoSearch_2',
                'entity' => $entity,
                'slm' => $slm->getID(),
                'time' => 30,
                'time_unit' => 'hour',
                'execution_time' => 45,
                'url' => 'https://example.com',
                'link' => 'https://example.com',
            ],
            [
                'name' => 'testAutoSearch_3',
                'entity' => $entity,
                'slm' => $slm->getID(),
                'time' => 30,
                'time_unit' => 'hour',
                'execution_time' => 45,
                'url' => 'https://example.com',
                'link' => 'https://example.com',
            ],
        ];
        $this->api->call(new Request('GET', '/Setup'), function ($call) use ($dataset) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($dataset) {
                    $this->assertGreaterThanOrEqual(1, count($content));
                    foreach ($content as $type) {
                        if ($type['itemtype'] === 'ManualLink') {
                            $dataset[0]['itemtype'] = Computer::class;
                            $dataset[0]['items_id'] = getItemByTypeName(Computer::class, '_test_pc01', true);
                            $dataset[1]['itemtype'] = Computer::class;
                            $dataset[1]['items_id'] = getItemByTypeName(Computer::class, '_test_pc01', true);
                            $dataset[2]['itemtype'] = Computer::class;
                            $dataset[2]['items_id'] = getItemByTypeName(Computer::class, '_test_pc01', true);
                        }
                        $this->api->autoTestSearch($type['href'], $dataset);
                    }
                });
        });
    }

    public function testAutoCRUD()
    {
        $this->login();


        $this->api->call(new Request('GET', '/Setup'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertGreaterThanOrEqual(1, count($content));
                    $entity = $this->getTestRootEntity(true);
                    $slm = $this->createItem(SLM::class, ['name' => 'Test SLM for AutoSearch', 'entities_id' => $entity]);
                    $sla = $this->createItem('SLA', [
                        'name' => 'Test SLA for AutoCRUD',
                        'entities_id' => $entity,
                        'slms_id' => $slm->getID(),
                        'number_time' => 30,
                        'definition_time' => 'hour',
                    ]);
                    $ola = $this->createItem('OLA', [
                        'name' => 'Test OLA for AutoCRUD',
                        'entities_id' => $entity,
                        'slms_id' => $slm->getID(),
                        'number_time' => 30,
                        'definition_time' => 'hour',
                    ]);
                    foreach ($content as $type) {
                        $create_params = [];
                        if ($type['itemtype'] === 'SLA' || $type['itemtype'] === 'OLA') {
                            $create_params['slm'] = $slm->getID();
                            $create_params['entity'] = $entity;
                            $create_params['time'] = 30;
                            $create_params['time_unit'] = 'hour';
                        } elseif ($type['itemtype'] === 'ManualLink') {
                            $create_params['itemtype'] = Computer::class;
                            $create_params['url'] = 'https://example.com';
                            $create_params['items_id'] = getItemByTypeName(Computer::class, '_test_pc01', true);
                        } elseif ($type['itemtype'] === 'SlaLevel') {
                            $create_params['execution_time'] = 45;
                            $create_params['sla'] = $sla->getID();
                            $create_params['entity'] = $entity;
                        } elseif ($type['itemtype'] === 'OlaLevel') {
                            $create_params['execution_time'] = 45;
                            $create_params['ola'] = $ola->getID();
                            $create_params['entity'] = $entity;
                        }
                        $this->api->autoTestCRUD($type['href'], $create_params);
                    }
                });
        });
    }

    public function testCRUDNoRights()
    {
        $this->loginWeb();
        $this->api->getRouter()->registerAuthMiddleware(new InternalAuthMiddleware());

        $this->api->call(new Request('GET', '/Setup'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertGreaterThanOrEqual(1, count($content));

                    $entity = $this->getTestRootEntity(true);
                    $slm = $this->createItem(SLM::class, ['name' => 'Test SLM for AutoSearch', 'entities_id' => $entity]);
                    $sla = $this->createItem('SLA', [
                        'name' => 'Test SLA for AutoCRUD',
                        'entities_id' => $entity,
                        'slms_id' => $slm->getID(),
                        'number_time' => 30,
                        'definition_time' => 'hour',
                    ]);
                    $ola = $this->createItem('OLA', [
                        'name' => 'Test OLA for AutoCRUD',
                        'entities_id' => $entity,
                        'slms_id' => $slm->getID(),
                        'number_time' => 30,
                        'definition_time' => 'hour',
                    ]);

                    foreach ($content as $type) {
                        $this->login();
                        $create_request = new Request('POST', $type['href']);
                        $create_request->setParameter('name', 'testCRUDNoRights' . random_int(0, 10000));
                        if ($type['itemtype'] === 'SLA' || $type['itemtype'] === 'OLA') {
                            $create_request->setParameter('slm', $slm->getID());
                            $create_request->setParameter('entity', $entity);
                            $create_request->setParameter('time', 30);
                            $create_request->setParameter('time_unit', 'hour');
                        } elseif ($type['itemtype'] === 'ManualLink') {
                            $create_request->setParameter('itemtype', Computer::class);
                            $create_request->setParameter('url', 'https://example.com');
                            $create_request->setParameter('items_id', getItemByTypeName(Computer::class, '_test_pc01', true));
                        } elseif ($type['itemtype'] === 'SlaLevel') {
                            $create_request->setParameter('execution_time', 45);
                            $create_request->setParameter('sla', $sla->getID());
                            $create_request->setParameter('entity', $entity);
                        } elseif ($type['itemtype'] === 'OlaLevel') {
                            $create_request->setParameter('execution_time', 45);
                            $create_request->setParameter('ola', $ola->getID());
                            $create_request->setParameter('entity', $entity);
                        }
                        $new_location = null;
                        $new_items_id = null;
                        $this->api->call($create_request, function ($call) use (&$new_location, &$new_items_id) {
                            /** @var \HLAPICallAsserter $call */
                            $call->response
                                ->isOK()
                                ->headers(function ($headers) use (&$new_location) {
                                    $new_location = $headers['Location'];
                                })
                                ->jsonContent(function ($content) use (&$new_items_id) {
                                    $new_items_id = $content['id'];
                                });
                        });
                        if ($type['itemtype'] === AuthLDAP::class) {
                            $this->api->autoTestCRUDNoRights(
                                endpoint: $type['href'],
                                itemtype: AuthLDAP::class,
                                items_id: (int) $new_items_id,
                                deny_create: static function () {
                                    $_SESSION['glpiactiveprofile'][AuthLDAP::$rightname] = ALLSTANDARDRIGHT & ~UPDATE;
                                },
                                deny_purge: static function () {
                                    $_SESSION['glpiactiveprofile'][AuthLDAP::$rightname] = ALLSTANDARDRIGHT & ~UPDATE;
                                },
                            );
                        } elseif ($type['itemtype'] === 'SlaLevel' || $type['itemtype'] === 'OlaLevel') {
                            $this->api->autoTestCRUDNoRights(
                                endpoint: $type['href'],
                                itemtype: $type['itemtype'],
                                items_id: (int) $new_items_id,
                                deny_create: static function () {
                                    $_SESSION['glpiactiveprofile']['slm'] = ALLSTANDARDRIGHT & ~UPDATE;
                                },
                                deny_purge: static function () {
                                    $_SESSION['glpiactiveprofile']['slm'] = ALLSTANDARDRIGHT & ~UPDATE;
                                },
                            );
                        } elseif ($type['itemtype'] === 'ManualLink') {
                            continue;
                        } else {
                            $this->api->autoTestCRUDNoRights(
                                endpoint: $type['href'],
                                itemtype: $type['itemtype'],
                                items_id: (int) $new_items_id,
                            );
                        }
                    }
                });
        });
    }

    public function testCRUDConfigValues()
    {
        $this->loginWeb();

        $this->api->getRouter()->registerAuthMiddleware(new InternalAuthMiddleware());
        // Can get a config value
        $this->api->call(new Request('GET', '/Setup/Config/core/priority_1'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertEquals('priority_1', $content['name']);
                    $this->assertEquals('core', $content['context']);
                    $this->assertEquals('#fff2f2', $content['value']);
                });
        });

        // Get an undisclosable config value
        Config::setConfigurationValues('core', ['smtp_passwd' => 'test']);
        $this->api->call(new Request('GET', '/Setup/Config/core/smtp_passwd'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isAccessDenied();
        });

        // Not existing config value
        $this->api->call(new Request('GET', '/Setup/Config/core/notrealconfig'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isNotFoundError();
        });

        // Can update a config value
        $request = new Request('PATCH', '/Setup/Config/core/priority_1');
        $request->setParameter('value', '#ffffff');
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertEquals('priority_1', $content['name']);
                    $this->assertEquals('core', $content['context']);
                    $this->assertEquals('#ffffff', $content['value']);
                });
        });
        $this->api->call(new Request('GET', '/Setup/Config/core/priority_1'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertEquals('priority_1', $content['name']);
                    $this->assertEquals('core', $content['context']);
                    $this->assertEquals('#ffffff', $content['value']);
                });
        });

        // Can update an undisclosable config value
        $request = new Request('PATCH', '/Setup/Config/core/smtp_passwd');
        $request->setParameter('value', 'newtest');
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->status(static fn($status) => $status === 204);
        });

        // Can delete a config value
        $this->api->call(new Request('DELETE', '/Setup/Config/core/priority_1'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->status(static fn($status) => $status === 204);
        });
        $this->api->call(new Request('GET', '/Setup/Config/core/priority_1'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isNotFoundError();
        });

        // Can delete an undisclosable config value
        $this->api->call(new Request('DELETE', '/Setup/Config/core/smtp_passwd'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->status(static fn($status) => $status === 204);
        });

        // Can get a config value using GraphQL
        $request = new Request('POST', '/GraphQL', [], 'query { Config(filter: "context==core;name==priority_2") { context, name, value } }');
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertArrayHasKey('data', $content);
                    $this->assertArrayHasKey('Config', $content['data']);
                    $this->assertCount(1, $content['data']['Config']);
                    $config = $content['data']['Config'][0];
                    $this->assertEquals('core', $config['context']);
                    $this->assertEquals('priority_2', $config['name']);
                    $this->assertEquals('#ffe0e0', $config['value']);
                });
        });

        // Cannot get an undisclosable config value using GraphQL
        $request = new Request('POST', '/GraphQL', [], 'query { Config(filter: "context==core;name==smtp_passwd") { context, name, value } }');
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertArrayHasKey('data', $content);
                    $this->assertArrayHasKey('Config', $content['data']);
                    $this->assertEmpty($content['data']['Config']);
                });
        });

        // Can search config values
        $request = new Request('GET', '/Setup/Config');
        $request->setParameter('filter', 'name==priority_2');
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertCount(1, $content);
                    $config = $content[0];
                    $this->assertEquals('core', $config['context']);
                    $this->assertEquals('priority_2', $config['name']);
                    $this->assertEquals('#ffe0e0', $config['value']);
                });
        });

        // Cannot search undisclosable config values
        $request = new Request('GET', '/Setup/Config');
        $request->setParameter('filter', 'name==smtp_passwd');
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertEmpty($content);
                });
        });
    }

    public function testConfigNotIn2_0()
    {
        $this->login();

        $v2_api = $this->api->withVersion('2.0.0');
        $v2_api->call(new Request('GET', '/Setup/Config/core/test'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isNotFoundError();
        });
        $v2_api->call(new Request('PATCH', '/Setup/Config/core/test'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isNotFoundError();
        });
        $v2_api->call(new Request('DELETE', '/Setup/Config/core/test'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isNotFoundError();
        });

        $request = new Request('POST', '/GraphQL', [], 'query { Config(filter: "context==core;name==test") { context, name, value } }');
        $v2_api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertArrayHasKey('errors', $content);
                });
        });
    }
}
