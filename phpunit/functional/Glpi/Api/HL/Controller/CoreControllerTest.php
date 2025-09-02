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

use Glpi\Asset\Asset_PeripheralAsset;
use Glpi\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;

class CoreControllerTest extends \HLAPITestCase
{
    public static function routeMatchProvider()
    {
        return [
            [new Request('GET', '/Session'), true],
            [new Request('POST', '/token'), true],
            [new Request('GET', '/doc'), true],
            [new Request('GET', '/Administration/User'), true],
            [new Request('GET', '/A/B/C'), false],
        ];
    }

    #[DataProvider('routeMatchProvider')]
    public function testRouteMatches(Request $request, bool $expected)
    {
        $this->assertEquals($expected, $this->api->hasMatch($request));
    }

    public function testOptionsRoute()
    {
        $this->login();
        $this->api->call(new Request('OPTIONS', '/Session'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->headers(function ($headers) {
                    $this->assertEquals('GET', $headers['Allow']);
                })
                ->status(fn($status) => $this->assertEquals(204, $status));
        });

        $this->api->call(new Request('OPTIONS', '/Administration/User'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->headers(function ($headers) {
                    $this->assertCount(2, array_intersect($headers['Allow'], ['GET', 'POST']));
                })
                ->status(fn($status) => $this->assertEquals(204, $status));
        });
    }

    public function testHeadMethod()
    {
        $this->login();
        $this->api->call(new Request('HEAD', '/Session'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->headers(function ($headers) {
                    $this->assertEquals('application/json', $headers['Content-Type']);
                })
                ->content(fn($content) => $this->assertEmpty($content));
        });
    }

    public static function responseContentSchemaProvider()
    {
        return [
            [new Request('GET', '/Session'), 'Session'],
        ];
    }

    #[DataProvider('responseContentSchemaProvider')]
    public function testResponseContentSchema(Request $request, string $schema_name)
    {
        $this->login();
        $this->api->call($request, function ($call) use ($schema_name) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->matchesSchema($schema_name);
        });
    }

    public function testTransferEntity()
    {
        $this->loginWeb('glpi', 'glpi');
        $root_entity = getItemByTypeName('Entity', '_test_root_entity', true);

        // Create 2 computers (not using API)
        $computer = new \Computer();
        $computers_id_1 = $computer->add([
            'name' => 'Computer 1',
            'entities_id' => $root_entity,
        ]);
        $this->assertGreaterThan(0, $computers_id_1);

        $computers_id_2 = $computer->add([
            'name' => 'Computer 2',
            'entities_id' => $root_entity,
        ]);
        $this->assertGreaterThan(0, $computers_id_2);

        // Create a monitor to test transfer options are passed correctly (keep_dc_monitor)
        $monitor = new \Monitor();
        $monitors_id = $monitor->add([
            'name' => 'Monitor 1',
            'entities_id' => $root_entity,
        ]);
        $this->assertGreaterThan(0, $monitors_id);

        // Connect the monitor to the computer
        $connection_item = new Asset_PeripheralAsset();
        $connection_item_id = $connection_item->add([
            'itemtype_asset' => \Computer::class,
            'items_id_asset' => $computers_id_1,
            'itemtype_peripheral' => \Monitor::class,
            'items_id_peripheral' => $monitors_id,
        ]);
        $this->assertGreaterThan(0, $connection_item_id);

        // Create 2 new entities (not using API)
        $entity = new \Entity();
        $entities_id_1 = $entity->add([
            'name' => 'Entity 1',
            'entities_id' => $root_entity,
        ]);
        $this->assertGreaterThan(0, $entities_id_1);

        $entities_id_2 = $entity->add([
            'name' => 'Entity 2',
            'entities_id' => $root_entity,
        ]);
        $this->assertGreaterThan(0, $entities_id_2);

        $transfer_records = [
            [
                'itemtype' => 'Computer',
                'items_id' => $computers_id_1,
                'entity' => $entities_id_1,
                'options' => [
                    'keep_dc_monitor' => 1,
                ],
            ],
            [
                'itemtype' => 'Computer',
                'items_id' => $computers_id_2,
                'entity' => $entities_id_2,
                'options' => [
                    'keep_dc_monitor' => 0,
                ],
            ],
        ];

        $this->login();

        $request = new Request('POST', '/Transfer', [
            'Content-Type' => 'application/json',
            'GLPI-Entity' => $root_entity,
            'GLPI-Entity-Recursive' => 'true',
        ], json_encode($transfer_records));
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->status(fn($status) => $this->assertEquals(200, $status))
                ->content(fn($content) => $this->assertEmpty($content));
        });

        // Check the computers have been transferred
        $this->assertTrue($computer->getFromDB($computers_id_1));
        $this->assertEquals($entities_id_1, $computer->fields['entities_id']);

        $this->assertTrue($computer->getFromDB($computers_id_2));
        $this->assertEquals($entities_id_2, $computer->fields['entities_id']);

        // Verify computer 1 has a monitor connection, and computer 2 doesn't
        $this->assertTrue($connection_item->getFromDBByCrit([
            'itemtype_asset' => \Computer::class,
            'items_id_asset' => $computers_id_1,
            'itemtype_peripheral' => \Monitor::class,
            'items_id_peripheral' => $monitors_id,
        ]) === true);

        $this->assertFalse($connection_item->getFromDBByCrit([
            'itemtype_asset' => \Computer::class,
            'items_id_asset' => $computers_id_2,
            'itemtype_peripheral' => \Monitor::class,
            'items_id_peripheral' => $monitors_id,
        ]) === true);
    }

    public function testOAuthPasswordGrant()
    {
        global $DB;

        // Create an OAuth client
        $client = new \OAuthClient();
        $client_id = $client->add([
            'name' => __FUNCTION__,
            'is_active' => 1,
            'is_confidential' => 1,
        ]);
        $this->assertGreaterThan(0, $client_id);

        // get client ID and secret
        $it = $DB->request([
            'SELECT' => ['identifier', 'secret'],
            'FROM' => \OAuthClient::getTable(),
            'WHERE' => ['id' => $client_id],
        ]);
        $this->assertCount(1, $it);
        $client_data = $it->current();
        $auth_data = [
            'grant_type' => 'password',
            'client_id' => $client_data['identifier'],
            'client_secret' => (new \GLPIKey())->decrypt($client_data['secret']),
            'username' => TU_USER,
            'password' => TU_PASS,
            'scope' => '',
        ];

        $request = new Request('POST', '/Token', ['Content-Type' => 'application/json'], json_encode($auth_data));
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->status(fn($status) => $this->assertEquals(400, $status))
                ->jsonContent(function ($content) {
                    $this->assertEquals('unauthorized_client', $content['error']);
                    $this->assertEquals('The authenticated client is not authorized to use this authorization grant type.', $content['error_description']);
                });
        });

        $client->update([
            'id' => $client_id,
            'grants' => ['password'],
        ]);

        $request = new Request('POST', '/Token', ['Content-Type' => 'application/json'], json_encode($auth_data));
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->status(fn($status) => $this->assertEquals(200, $status))
                ->jsonContent(function ($content) {
                    $this->assertEquals('Bearer', $content['token_type']);
                    $this->assertNotEmpty($content['access_token']);
                    $this->assertGreaterThan(0, $content['expires_in']);
                });
        });
    }

    public function testOAuthPasswordGrantHeader()
    {
        global $DB;

        // Create an OAuth client
        $client = new \OAuthClient();
        $client_id = $client->add([
            'name' => __FUNCTION__,
            'is_active' => 1,
            'is_confidential' => 1,
            'grants' => ['password'],
        ]);
        $this->assertGreaterThan(0, $client_id);

        // get client ID and secret
        $it = $DB->request([
            'SELECT' => ['identifier', 'secret'],
            'FROM' => \OAuthClient::getTable(),
            'WHERE' => ['id' => $client_id],
        ]);
        $this->assertCount(1, $it);
        $client_data = $it->current();
        $auth_data = [
            'grant_type' => 'password',
            'username' => TU_USER,
            'password' => TU_PASS,
            'scope' => '',
        ];
        $request = new Request('POST', '/Token', [
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode($client_data['identifier'] . ':' . (new \GLPIKey())->decrypt($client_data['secret'])),
        ], json_encode($auth_data));
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->status(fn($status) => $this->assertEquals(200, $status))
                ->jsonContent(function ($content) {
                    $this->assertEquals('Bearer', $content['token_type']);
                    $this->assertNotEmpty($content['access_token']);
                    $this->assertGreaterThan(0, $content['expires_in']);
                });
        });
    }

    public function testOAuthClientCredentialsGrant(): void
    {
        global $DB;

        // Create an OAuth client
        $client = new \OAuthClient();
        $client_id = $client->add([
            'name' => __FUNCTION__,
            'is_active' => 1,
            'is_confidential' => 1,
        ]);
        $this->assertGreaterThan(0, $client_id);

        // get client ID and secret
        $it = $DB->request([
            'SELECT' => ['identifier', 'secret'],
            'FROM' => \OAuthClient::getTable(),
            'WHERE' => ['id' => $client_id],
        ]);
        $this->assertCount(1, $it);
        $client_data = $it->current();
        $auth_data = [
            'grant_type' => 'client_credentials',
            'client_id' => $client_data['identifier'],
            'client_secret' => (new \GLPIKey())->decrypt($client_data['secret']),
            'scope' => 'inventory',
        ];

        $request = new Request('POST', '/Token', ['Content-Type' => 'application/json'], json_encode($auth_data));
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->status(fn($status) => $this->assertEquals(400, $status))
                ->jsonContent(function ($content) {
                    $this->assertEquals('unauthorized_client', $content['error']);
                    $this->assertEquals('The authenticated client is not authorized to use this authorization grant type.', $content['error_description']);
                });
        });

        $client->update([
            'id' => $client_id,
            'grants' => ['client_credentials'],
            'scopes' => ['inventory'],
        ]);

        $request = new Request('POST', '/Token', ['Content-Type' => 'application/json'], json_encode($auth_data));
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->status(fn($status) => $this->assertEquals(200, $status))
                ->jsonContent(function ($content) {
                    $this->assertEquals('Bearer', $content['token_type']);
                    $this->assertNotEmpty($content['access_token']);
                    $this->assertGreaterThan(0, $content['expires_in']);
                });
        });
    }

    public function testStatusScope()
    {
        $this->login(api_options: ['scope' => 'api']);
        $this->api->call(new Request('GET', '/Status'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isAccessDenied()
                ->jsonContent(function ($content) {
                    $this->assertEquals('You do not have the required scope(s) to access this endpoint.', $content['detail']);
                });
        });
        $this->login(api_options: ['scope' => 'status']);
        $this->api->call(new Request('GET', '/Status'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK();
        });
    }
}
