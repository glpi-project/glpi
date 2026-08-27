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
use AuthLdapReplicate;
use AuthMail;
use Computer;
use Config;
use Glpi\Api\HL\Controller\SetupController;
use Glpi\Api\HL\Middleware\InternalAuthMiddleware;
use Glpi\Http\Request;
use Glpi\Tests\HLAPITestCase;
use Link;
use MailCollector;
use NotImportedEmail;
use QueuedWebhook;
use SLM;

use function Safe\json_encode;

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
                        } elseif ($type['itemtype'] === 'FieldUnicity') {
                            $dataset[0]['itemtype'] = Computer::class;
                            $dataset[0]['fields'] = 'serial';
                            $dataset[1]['itemtype'] = Computer::class;
                            $dataset[1]['fields'] = 'serial';
                            $dataset[2]['itemtype'] = Computer::class;
                            $dataset[2]['fields'] = 'serial';
                        } else {
                            continue;
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
                        if ($type['itemtype'] === 'CronTask' || $type['itemtype'] === 'QueuedWebhook') {
                            continue;
                        }
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
                        } elseif ($type['itemtype'] === 'FieldUnicity') {
                            $create_params['itemtype'] = Computer::class;
                            $create_params['fields'] = 'serial';
                        } elseif ($type['itemtype'] === 'MailCollector') {
                            $create_params['host'] = '{imap.example.com:993/imap/ssl/novalidate-cert}';
                        } elseif ($type['itemtype'] === 'AuthLdapReplicate') {
                            $create_params['host'] = 'ldap.example.com';
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

        $types_20 = SetupController::getSetupEndpointTypes20();
        $types_23 = SetupController::getSetupEndpointTypes23();

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

        foreach ([...$types_20, ...$types_23] as $type) {
            if ($type === 'QueuedWebhook') {
                continue;
            }
            $itemtype = $type;
            $create_request = new Request('POST', '/Setup/' . $type);
            $create_request->setParameter('name', 'testCRUDNoRights' . random_int(0, 10000));
            $create_request->setParameter('entity', getItemByTypeName('Entity', '_test_root_entity', true));
            if ($type === 'SLA' || $type === 'OLA') {
                $create_request->setParameter('slm', $slm->getID());
                $create_request->setParameter('time', 2);
                $create_request->setParameter('time_unit', 'hour');
            } elseif ($type === 'FieldUnicity') {
                $create_request->setParameter('itemtype', 'Computer');
                $create_request->setParameter('fields', 'serial');
            } elseif ($type === 'EmailCollector') {
                $itemtype = MailCollector::class;
                $create_request->setParameter('host', '{imap.example.com:993/imap/ssl/novalidate-cert}');
            } elseif ($type === 'EmailAuthServer') {
                $itemtype = AuthMail::class;
            } elseif ($type === 'LDAPDirectory') {
                $itemtype = AuthLDAP::class;
            } elseif ($type === 'LDAPDirectoryReplicate') {
                $itemtype = AuthLdapReplicate::class;
                $create_request->setParameter('ldap_directory', getItemByTypeName('AuthLDAP', '_local_ldap', true));
                $create_request->setParameter('host', 'ldap.example.com');
            } elseif ($type === 'ManualLink') {
                $create_request->setParameter('itemtype', Computer::class);
                $create_request->setParameter('url', 'https://example.com');
                $create_request->setParameter('items_id', getItemByTypeName(Computer::class, '_test_pc01', true));
            } elseif ($type === 'ExternalLink') {
                $itemtype = Link::class;
            } elseif ($type === 'SLALevel') {
                $create_request->setParameter('execution_time', 45);
                $create_request->setParameter('sla', $sla->getID());
                $create_request->setParameter('entity', $entity);
            } elseif ($type === 'OLALevel') {
                $create_request->setParameter('execution_time', 45);
                $create_request->setParameter('ola', $ola->getID());
                $create_request->setParameter('entity', $entity);
            }
            $new_location = null;
            $new_items_id = null;
            $this->login();
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
            if (
                $type === 'LDAPDirectory'
                || $type === 'LDAPDirectoryReplicate'
                || $type === 'EmailAuthServer'
                || $type === 'FieldUnicity'
                || $type === 'EmailCollector'
                || $type === 'Webhook'
            ) {
                $this->api->autoTestCRUDNoRights(
                    endpoint: '/Setup/' . $type,
                    itemtype: $itemtype,
                    items_id: (int) $new_items_id,
                    deny_create: static function () use ($itemtype) {
                        $_SESSION['glpiactiveprofile'][$itemtype::$rightname] = ALLSTANDARDRIGHT & ~UPDATE;
                    },
                    deny_purge: static function () use ($itemtype) {
                        $_SESSION['glpiactiveprofile'][$itemtype::$rightname] = ALLSTANDARDRIGHT & ~UPDATE;
                    },
                );
            } elseif ($type === 'SLALevel' || $type === 'OLALevel') {
                $this->api->autoTestCRUDNoRights(
                    endpoint: '/Setup/' . $type,
                    itemtype: $itemtype,
                    items_id: (int) $new_items_id,
                    deny_create: static function () {
                        $_SESSION['glpiactiveprofile']['slm'] = ALLSTANDARDRIGHT & ~UPDATE;
                    },
                    deny_purge: static function () {
                        $_SESSION['glpiactiveprofile']['slm'] = ALLSTANDARDRIGHT & ~UPDATE;
                    },
                );
            } elseif ($type === 'ManualLink') {
                continue;
            } else {
                $this->api->autoTestCRUDNoRights(
                    endpoint: '/Setup/' . $type,
                    itemtype: $itemtype,
                    items_id: (int) $new_items_id,
                );
            }
        }
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

    private function createQueuedWebhook()
    {
        $webhook = $this->createItem('Webhook', [
            'name' => 'Test Queued Webhook',
            'url' => 'https://example.com',
            'http_method' => 'POST',
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $queued_webhook = $this->createItem('QueuedWebhook', [
            'webhooks_id' => $webhook->getID(),
            'url' => 'https://example.com',
            'http_method' => 'POST',
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $this->assertEquals($webhook->getID(), $queued_webhook->fields['webhooks_id']);
        $this->assertEquals('https://example.com', $queued_webhook->fields['url']);
        return $queued_webhook;
    }

    public function testCRUDQueuedWebhook()
    {
        $this->login();
        $queued_webhook = $this->createQueuedWebhook();

        $this->api->call(new Request('GET', '/Setup/QueuedWebhook/' . $queued_webhook->getID()), function ($call) use ($queued_webhook) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($queued_webhook) {
                    $this->assertEquals($queued_webhook->getID(), $content['id']);
                    $this->assertEquals('https://example.com', $content['url']);
                    $this->assertEquals('POST', $content['http_method']);
                });
        });

        $this->api->call(new Request('DELETE', '/Setup/QueuedWebhook/' . $queued_webhook->getID()), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isOK();
        });

        $this->api->call(new Request('GET', '/Setup/QueuedWebhook/' . $queued_webhook->getID()), function ($call) use ($queued_webhook) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($queued_webhook) {
                    $this->assertTrue($content['is_deleted']);
                });
        });

        $force_delete_request = new Request('DELETE', '/Setup/QueuedWebhook/' . $queued_webhook->getID());
        $force_delete_request->setParameter('force', true);
        $this->api->call($force_delete_request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isOK();
        });

        $this->api->call(new Request('GET', '/Setup/QueuedWebhook/' . $queued_webhook->getID()), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isNotFoundError();
        });
    }

    public function testCRUDNotImportedEmails()
    {
        $not_imported_id = $this->createItem(NotImportedEmail::class, [
            'from' => 'sender@example.com',
            'to' => 'helpdesk@example.com',
            'subject' => 'Test email',
            'messageid' => '8f9510c1-0f31-450d-8082-71f22e2ac58f@exmaple.com',
            'reason' => NotImportedEmail::USER_UNKNOWN,
        ])->getID();

        // search, get, delete only

        $this->login();
        $this->api->call(new Request('GET', '/Setup/NotImportedEmail'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $found = false;
                    foreach ($content as $email) {
                        if ($email['messageid'] === '8f9510c1-0f31-450d-8082-71f22e2ac58f@exmaple.com') {
                            $found = true;
                            $this->assertEquals('sender@example.com', $email['from']);
                            $this->assertEquals('helpdesk@example.com', $email['to']);
                            $this->assertEquals('Test email', $email['subject']);
                            $this->assertEquals(NotImportedEmail::USER_UNKNOWN, $email['reason']);
                            break;
                        }
                    }
                    $this->assertTrue($found);
                });
        });

        $this->api->call(new Request('GET', "/Setup/NotImportedEmail/{$not_imported_id}"), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($email) {
                    $this->assertEquals('8f9510c1-0f31-450d-8082-71f22e2ac58f@exmaple.com', $email['messageid']);
                    $this->assertEquals('helpdesk@example.com', $email['to']);
                    $this->assertEquals('Test email', $email['subject']);
                    $this->assertEquals(NotImportedEmail::USER_UNKNOWN, $email['reason']);
                });
        });

        $this->api->call(new Request('DELETE', "/Setup/NotImportedEmail/{$not_imported_id}"), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK();
        });
        $this->api->call(new Request('GET', "/Setup/NotImportedEmail/{$not_imported_id}"), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isNotFoundError();
        });
    }

    public function testCRUDNoRightsQueuedWebhook()
    {
        $this->loginWeb();
        $this->api->getRouter()->registerAuthMiddleware(new InternalAuthMiddleware());
        $queued_webhook = $this->createQueuedWebhook();

        $_SESSION['glpiactiveprofile'][QueuedWebhook::$rightname] = ALLSTANDARDRIGHT & ~READ;

        $this->api->call(new Request('GET', '/Setup/QueuedWebhook/' . $queued_webhook->getID()), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isAccessDenied();
        });

        $_SESSION['glpiactiveprofile'][QueuedWebhook::$rightname] = ALLSTANDARDRIGHT & ~UPDATE;

        $this->api->call(new Request('DELETE', '/Setup/QueuedWebhook/' . $queued_webhook->getID()), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isAccessDenied();
        });
    }

    public function testCRUDNotImportedEmailsNoRights()
    {
        $not_imported_id = $this->createItem(NotImportedEmail::class, [
            'from' => 'sender@example.com',
            'to' => 'helpdesk@example.com',
            'subject' => 'Test email',
            'messageid' => '8f9510c1-0f31-450d-8082-71f22e2ac58f@exmaple.com',
            'reason' => NotImportedEmail::USER_UNKNOWN,
        ])->getID();

        $this->api->autoTestCRUDNoRights(
            endpoint: '/Setup/NotImportedEmail',
            itemtype: NotImportedEmail::class,
            items_id: $not_imported_id,
            deny_purge: static function () {
                $_SESSION['glpiactiveprofile'][NotImportedEmail::$rightname] = ALLSTANDARDRIGHT & ~UPDATE;
            },
            create_params: [
                'from' => 'sender2@example.com',
                'to' => 'helpdesk@example.com',
                'subject' => 'Test email',
                'messageid' => 'c9b53a8a-a460-4b4a-98ad-02fd548e3c25@exmaple.com',
                'reason' => NotImportedEmail::USER_UNKNOWN,
            ],
            extra_options: ['skip_create_test' => true, 'skip_update_test' => true],
        );
    }

    public function testCRUDAssetDefinition()
    {
        $create_input = [
            "system_name" => "Car",
            "label" => "Car",
            "icon" => "ti-car",
            "comment" => "",
            "is_active" => true,
            "capacities" => json_encode([
                ['name' => 'Glpi\\Asset\\Capacity\\HasCertificatesCapacity', 'config' => []],
                ['name' => 'Glpi\\Asset\\Capacity\\HasContractsCapacity', 'config' => []],
                ['name' => 'Glpi\\Asset\\Capacity\\HasDocumentsCapacity', 'config' => []],
                ['name' => 'Glpi\\Asset\\Capacity\\HasInfocomCapacity', 'config' => []],
                ['name' => 'Glpi\\Asset\\Capacity\\HasHistoryCapacity', 'config' => []],
                ['name' => 'Glpi\\Asset\\Capacity\\HasKnowbaseCapacity', 'config' => []],
                ['name' => 'Glpi\\Asset\\Capacity\\HasLinksCapacity', 'config' => []],
                ['name' => 'Glpi\\Asset\\Capacity\\HasNotepadCapacity', 'config' => []],
                ['name' => 'Glpi\\Asset\\Capacity\\IsProjectAssetCapacity', 'config' => []],
                ['name' => 'Glpi\\Asset\\Capacity\\IsReservableCapacity', 'config' => []],
            ]),
            "translations" => "{\"fr_FR\":{\"one\":\"Voiture\",\"many\":\"Voitures\",\"other\":\"Voitures\"}}",
            "date_creation" => "2025-10-16T09:51:25+00:00",
            "date_mod" => "2026-06-29T23:09:20+00:00",
        ];

        $this->login();

        // Create
        $request = new Request('POST', '/Setup/AssetDefinition');
        foreach ($create_input as $key => $value) {
            $request->setParameter($key, $value);
        }

        $asset_definition_location = null;
        $this->api->call($request, function ($call) use (&$asset_definition_location) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->headers(function ($headers) use (&$asset_definition_location) {
                    $this->assertNotEmpty($headers['Location']);
                    $asset_definition_location = $headers['Location'];
                });
        });

        // Get
        $this->api->call(new Request('GET', $asset_definition_location), function ($call) use ($create_input) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($create_input) {
                    foreach ($create_input as $key => $value) {
                        if ($key === 'capacities' || $key === 'translations') {
                            $actual_value = json_decode($content[$key], true);
                            $expected_value = json_decode($value, true);
                            foreach ($expected_value as $index => $expected_item) {
                                $this->assertEquals($expected_item, $actual_value[$index]);
                            }
                        } else {
                            $this->assertEquals($value, $content[$key]);
                        }
                    }
                });
        });

        // Search
        $request = new Request('GET', '/Setup/AssetDefinition');
        $request->setParameter('filter', 'system_name==Car');
        $this->api->call($request, function ($call) use ($create_input) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($create_input) {
                    $this->assertCount(1, $content);
                    foreach ($create_input as $key => $value) {
                        if ($key === 'capacities' || $key === 'translations') {
                            $actual_value = json_decode($content[0][$key], true);
                            $expected_value = json_decode($value, true);
                            foreach ($expected_value as $index => $expected_item) {
                                $this->assertEquals($expected_item, $actual_value[$index]);
                            }
                        } else {
                            $this->assertEquals($value, $content[0][$key]);
                        }
                    }
                });
        });

        // Update
        $update_input = [
            "label" => "Updated Car",
            "is_active" => false,
        ];
        $request = new Request('PATCH', $asset_definition_location);
        foreach ($update_input as $key => $value) {
            $request->setParameter($key, $value);
        }
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isOK();
        });

        // Get after update
        $this->api->call(new Request('GET', $asset_definition_location), function ($call) use ($update_input) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($update_input) {
                    $this->assertEquals($update_input['label'], $content['label']);
                    $this->assertEquals($update_input['is_active'], $content['is_active']);
                });
        });

        // Cannot change system name
        $request = new Request('PATCH', $asset_definition_location);
        $request->setParameter('system_name', 'NewCar');
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isNotOK()
                ->jsonContent(function ($content) {
                    $this->assertEquals('Failed to update item(s)', $content['title']);
                    $this->assertStringContainsString('The system name cannot be changed', $content['additional_messages'][0]['message']);
                });
        });

        // Delete
        $this->api->call(new Request('DELETE', $asset_definition_location), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isOK();
        });

        // Ensure deleted
        $this->api->call(new Request('GET', $asset_definition_location), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isNotFoundError();
        });
    }

    public function testCRUDAssetDefinitionCustomField()
    {
        $fields = [
            [
                "system_name" => "horsepower",
                "label" => "Horsepower",
                "type" => "Glpi\\Asset\\CustomFieldType\\NumberType",
                "field_options" => "{\"full_width\":\"0\",\"required\":\"0\",\"readonly\":\"\",\"hidden\":\"\",\"min\":\"0\",\"max\":\"300\",\"step\":\"1.00\"}",
                "itemtype" => null,
                "default_value" => "\"180\"",
                "translations" => "[]",
                "date_creation" => "2025-11-27T18:26:29+00:00",
                "date_mod" => "2025-11-27T18:26:29+00:00",
            ],
            [
                "system_name" => "had_accident",
                "label" => "Had Accident",
                "type" => "Glpi\\Asset\\CustomFieldType\\BooleanType",
                "field_options" => "[]",
                "itemtype" => null,
                "default_value" => "false",
                "translations" => "{\"fr_FR\":\"a eu un accident\"}",
                "date_creation" => "2026-03-15T22:59:46+00:00",
                "date_mod" => "2026-06-29T23:09:36+00:00",
            ],
            [
                "system_name" => "last_service_date",
                "label" => "Last Service Date",
                "type" => "Glpi\\Asset\\CustomFieldType\\DateType",
                "field_options" => "{\"full_width\":\"0\",\"required\":\"0\",\"readonly\":\"\",\"hidden\":\"\"}",
                "itemtype" => null,
                "default_value" => null,
                "translations" => "[]",
                "date_creation" => "2026-04-21T18:13:24+00:00",
                "date_mod" => "2026-04-21T18:13:24+00:00",
            ],
        ];

        $car_definition = $this->initAssetDefinition('Car');

        $this->login();

        $custom_field_locations = [];

        foreach ($fields as $field) {
            // Create
            $request = new Request('POST', '/Setup/AssetDefinition/' . $car_definition->getID() . '/CustomField');
            foreach ($field as $key => $value) {
                $request->setParameter($key, $value);
            }

            $this->api->call($request, function ($call) use ($field, &$custom_field_locations) {
                /** @var \HLAPICallAsserter $call */
                $call->response
                    ->isOK()
                    ->headers(function ($headers) use ($field, &$custom_field_locations) {
                        $this->assertNotEmpty($headers['Location']);
                        $custom_field_locations[$field['system_name']] = $headers['Location'];
                    });
            });

            // Get
            $this->api->call(new Request('GET', $custom_field_locations[$field['system_name']]), function ($call) use ($field) {
                /** @var \HLAPICallAsserter $call */
                $call->response
                    ->isOK()
                    ->jsonContent(function ($content) use ($field) {
                        foreach ($field as $key => $value) {
                            if ($key === 'field_options' || $key === 'translations') {
                                $actual_value = json_decode($content[$key], true);
                                $expected_value = json_decode($value, true);
                                $this->assertEquals($expected_value, $actual_value);
                            } else {
                                $this->assertEquals($value, $content[$key]);
                            }
                        }
                    });
            });
        }

        // Search
        $request = new Request('GET', '/Setup/AssetDefinition/' . $car_definition->getID() . '/CustomField');
        $request->setParameter('filter', 'system_name==horsepower');
        $this->api->call($request, function ($call) use ($fields) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($fields) {
                    $this->assertCount(1, $content);
                    foreach ($fields[0] as $key => $value) {
                        if ($key === 'field_options' || $key === 'translations') {
                            $actual_value = json_decode($content[0][$key], true);
                            $expected_value = json_decode($value, true);
                            $this->assertEquals($expected_value, $actual_value);
                        } else {
                            $this->assertEquals($value, $content[0][$key]);
                        }
                    }
                });
        });

        // Update one of the fields
        $update_input = [
            "label" => "Updated Horsepower",
            "field_options" => "{\"full_width\":\"0\",\"required\":\"1\",\"readonly\":\"\",\"hidden\":\"\",\"min\":\"0\",\"max\":\"400\",\"step\":\"1.00\"}",
        ];
        $request = new Request('PATCH', $custom_field_locations['horsepower']);
        foreach ($update_input as $key => $value) {
            $request->setParameter($key, $value);
        }
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isOK();
        });

        // Get after update
        $this->api->call(new Request('GET', $custom_field_locations['horsepower']), function ($call) use ($update_input) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($update_input) {
                    $this->assertEquals($update_input['label'], $content['label']);
                    $expected_field_options = json_decode($update_input['field_options'], true);
                    $actual_field_options = json_decode($content['field_options'], true);
                    foreach ($expected_field_options as $key => $value) {
                        $this->assertEquals($value, $actual_field_options[$key]);
                    }
                });
        });

        // Delete all custom fields
        foreach ($custom_field_locations as $system_name => $location) {
            $this->api->call(new Request('DELETE', $location), function ($call) {
                /** @var \HLAPICallAsserter $call */
                $call->response->isOK();
            });
        }

        // Search after deletion to ensure they are gone
        $request = new Request('GET', '/Setup/AssetDefinition/' . $car_definition->getID() . '/CustomField');
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertEmpty($content);
                });
        });
    }

    public function testCreateAssetDefinitionWithPicture(): void
    {
        $bar_file_path = GLPI_ROOT . '/tests/fixtures/uploads/bar.png';
        $bar_file_content = file_get_contents($bar_file_path);

        $this->login();

        // multipart form data request with the document item's name, the file, and the entity ID
        $multipart_body = <<<EOT
-----boundary
Content-Disposition: form-data; name="system_name"

Car
-----boundary
Content-Disposition: form-data; name="label"

Car
-----boundary
Content-Disposition: form-data; name="picture_upload"; filename="bar.png"

$bar_file_content
-----boundary--
EOT;

        $request = new Request('POST', '/Setup/AssetDefinition', [
            'Content-Type' => 'multipart/form-data; boundary=---boundary',
        ], $multipart_body);

        $new_location = null;
        $this->api->call($request, function ($call) use (&$new_location) {
            $call->response
                ->isOK()
                ->headers(function ($headers) use (&$new_location) {
                    $new_location = $headers['Location'];
                });
        });

        $this->api->call(new Request('GET', $new_location), function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertEquals('Car', $content['system_name']);
                    $this->assertEquals('Car', $content['label']);
                    $this->assertNotEmpty($content['picture']);
                });
        });
    }
}
