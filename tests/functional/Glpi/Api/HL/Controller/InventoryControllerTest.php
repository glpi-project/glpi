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

use Agent;
use AgentType;
use Computer;
use Glpi\Http\Request;
use Glpi\Tests\HLAPICallAsserter;
use Glpi\Tests\HLAPITestCase;
use Lockedfield;
use SNMPCredential;

class InventoryControllerTest extends HLAPITestCase
{
    public function testAutoTestCRUD()
    {
        $this->api->autoTestCRUD('/Inventory/SNMPCredential', [
            'snmp_version' => '2c',
        ]);
    }

    public function testCRUDAgent()
    {
        $this->loginWeb();

        // Manually create an agent and type as it isn't supported in the API.
        // Normally these are automatically created during the automatic inventory process
        $agenttypes_id = $this->createItem(AgentType::class, [
            'name' => 'Test Agent Type',
        ])->getID();
        $agents_id = $this->createItem(Agent::class, [
            'name' => 'Test Agent',
            'entities_id' => $this->getTestRootEntity(true),
            'itemtype' => Computer::class,
            'items_id' => getItemByTypeName(Computer::class, '_test_pc01', true),
            'deviceid' => '_test_deviceid01',
            'agenttypes_id' => $agenttypes_id,
        ])->getID();

        // test update, get, search and delete in REST

        $this->login();

        $this->api->call(new Request('GET', '/Inventory/Agent'), function ($call) {
            /** @var HLAPICallAsserter $call */
            $call->response->isOK();
            $call->response->jsonContent(function ($content) {
                $found_agent = false;
                foreach ($content as $agent) {
                    if ($agent['name'] === 'Test Agent') {
                        $found_agent = true;
                        break;
                    }
                }
                $this->assertTrue($found_agent);
            });
        });

        $update_request = new Request('PATCH', "/Inventory/Agent/{$agents_id}");
        $update_request->setParameter('threads_network_discovery', 6);
        $this->api->call($update_request, function ($call) {
            /** @var HLAPICallAsserter $call */
            $call->response->isOK();
            $call->response->jsonContent(function ($content) {
                $this->assertEquals(6, $content['threads_network_discovery']);
            });
        });

        $this->api->call(new Request('GET', "/Inventory/Agent/{$agents_id}"), function ($call) {
            /** @var HLAPICallAsserter $call */
            $call->response->isOK();
            $call->response->jsonContent(function ($content) {
                $this->assertEquals(6, $content['threads_network_discovery']);
            });
        });

        $this->api->call(new Request('DELETE', "/Inventory/Agent/{$agents_id}"), function ($call) {
            /** @var HLAPICallAsserter $call */
            $call->response->isOK();
        });

        $this->api->call(new Request('GET', "/Inventory/Agent/{$agents_id}"), function ($call) {
            /** @var HLAPICallAsserter $call */
            $call->response->isNotFoundError();
        });
    }

    public function testAutoTestCRUDNoRights()
    {
        $this->login();

        $sc = $this->createItem(SNMPCredential::class, [
            'name' => 'Test SNMP Credential',
            'snmpversion' => '2c',
        ]);
        $this->api->autoTestCRUDNoRights(
            endpoint: '/Inventory/SNMPCredential',
            itemtype: SNMPCredential::class,
            items_id: $sc->getID(),
            create_params: [
                'snmp_version' => '2c',
            ]
        );
    }

    public function testAutoTestCRUDAgentNoRights()
    {
        $agenttypes_id = $this->createItem(AgentType::class, [
            'name' => 'Test Agent Type',
        ])->getID();
        $agents_id = $this->createItem(Agent::class, [
            'name' => 'Test Agent',
            'entities_id' => $this->getTestRootEntity(true),
            'itemtype' => Computer::class,
            'items_id' => getItemByTypeName(Computer::class, '_test_pc01', true),
            'deviceid' => '_test_deviceid01',
            'agenttypes_id' => $agenttypes_id,
        ])->getID();
        $this->api->autoTestCRUDNoRights(
            endpoint: '/Inventory/Agent',
            itemtype: Agent::class,
            items_id: $agents_id,
            extra_options: ['skip_create_test' => true]
        );
    }

    public function testCRUDLockedField()
    {
        $this->api->autoTestCRUD('/Inventory/LockedField', [
            'itemtype' => Computer::class,
            'items_id' => getItemByTypeName(Computer::class, '_test_pc01', true),
            'field' => 'comment',
        ]);
    }

    public function testCRUDLockedFieldNoRights()
    {
        $locked_field = $this->createItem(Lockedfield::class, [
            'itemtype' => Computer::class,
            'items_id' => getItemByTypeName(Computer::class, '_test_pc01', true),
            'field' => 'comment',
        ]);
        $this->api->autoTestCRUDNoRights(
            endpoint: '/Inventory/LockedField',
            itemtype: Lockedfield::class,
            items_id: $locked_field->getID(),
            deny_read: static function () {
                $_SESSION['glpiactiveprofile'][Lockedfield::$rightname] = ALLSTANDARDRIGHT & ~UPDATE;
            },
            deny_create: static function () {
                $_SESSION['glpiactiveprofile'][Lockedfield::$rightname] = ALLSTANDARDRIGHT & ~UPDATE;
            },
            deny_delete: static function () {
                $_SESSION['glpiactiveprofile'][Lockedfield::$rightname] = ALLSTANDARDRIGHT & ~UPDATE;
            },
            deny_purge: static function () {
                $_SESSION['glpiactiveprofile'][Lockedfield::$rightname] = ALLSTANDARDRIGHT & ~UPDATE;
            },
            deny_restore: static function () {
                $_SESSION['glpiactiveprofile'][Lockedfield::$rightname] = ALLSTANDARDRIGHT & ~UPDATE;
            },
            create_params: [
                'itemtype' => Computer::class,
                'items_id' => getItemByTypeName(Computer::class, '_test_pc02', true),
                'field' => 'comment',
            ]
        );
    }
}
