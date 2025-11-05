<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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
use Config;
use Glpi\Api\HL\Middleware\InternalAuthMiddleware;
use Glpi\Http\Request;

class SetupControllerTest extends \HLAPITestCase
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
                        $this->assertEquals('/Setup/' . $asset['itemtype'], $asset['href']);
                    }
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
                'entity' => $entity,
            ],
            [
                'name' => 'testAutoSearch_2',
                'entity' => $entity,
            ],
            [
                'name' => 'testAutoSearch_3',
                'entity' => $entity,
            ],
        ];
        $this->api->call(new Request('GET', '/Setup'), function ($call) use ($dataset) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($dataset) {
                    $this->assertGreaterThanOrEqual(1, count($content));
                    foreach ($content as $type) {
                        $this->api->autoTestSearch('/Setup/' . $type['itemtype'], $dataset);
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
                    foreach ($content as $type) {
                        $this->api->autoTestCRUD('/Setup/' . $type['itemtype']);
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
                    foreach ($content as $type) {
                        $create_request = new Request('POST', $type['href']);
                        $create_request->setParameter('name', 'testCRUDNoRights' . random_int(0, 10000));
                        $create_request->setParameter('entity', getItemByTypeName('Entity', '_test_root_entity', true));
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
                        if ($type['itemtype'] === 'LDAPDirectory') {
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
