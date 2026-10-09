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

use Glpi\Api\HL\Middleware\InternalAuthMiddleware;
use Glpi\Asset\Asset_PeripheralAsset;
use Glpi\Http\Request;
use Glpi\Tests\HLAPITestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Transfer;

class CoreControllerTest extends HLAPITestCase
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
            $call->response
                ->isOK()
                ->headers(function ($headers) {
                    $this->assertEquals('GET', $headers['Allow']);
                })
                ->status(fn($status) => $this->assertEquals(204, $status));
        });

        $this->api->call(new Request('OPTIONS', '/Administration/User'), function ($call) {
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
            $call->response->matchesSchema($schema_name);
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

        $request = new Request('POST', '/Transfer', [
            'Content-Type' => 'application/json',
            'GLPI-Entity' => $root_entity,
            'GLPI-Entity-Recursive' => 'true',
        ], json_encode($transfer_records));

        $_SESSION['glpiactiveprofile'][Transfer::$rightname] = 0;
        $this->api->getRouter()->registerAuthMiddleware(new InternalAuthMiddleware());
        $this->api->call($request, function ($call) {
            $call->response->isAccessDenied();
        });

        $_SESSION['glpiactiveprofile'][Transfer::$rightname] = READ;

        $request = new Request('POST', '/Transfer', [
            'Content-Type' => 'application/json',
            'GLPI-Entity' => $root_entity,
            'GLPI-Entity-Recursive' => 'true',
        ], json_encode($transfer_records));
        $this->api->call($request, function ($call) {
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
            $call->response
                ->status(fn($status) => $this->assertEquals(200, $status))
                ->jsonContent(function ($content) {
                    $this->assertEquals('Bearer', $content['token_type']);
                    $this->assertNotEmpty($content['access_token']);
                    $this->assertGreaterThan(0, $content['expires_in']);
                });
        });
    }

    private const PKCE_REDIRECT_URI = 'https://spa.example.com/callback';

    /**
     * Create an OAuth client and return its identifier.
     * @param string[] $grants
     */
    private function createOAuthClient(string $name, bool $is_confidential, array $grants): string
    {
        $client = $this->createItem(\OAuthClient::class, [
            'name' => $name,
            'is_active' => 1,
            'is_confidential' => (int) $is_confidential,
            'grants' => $grants,
            'scopes' => ['api'],
            'redirect_uri' => [self::PKCE_REDIRECT_URI],
        ], ['grants', 'scopes', 'redirect_uri']);
        return $client->fields['identifier'];
    }

    private static function getPKCEChallenge(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }

    /**
     * Call the authorize endpoint with the given query parameters, accepting the authorization request.
     * @param array<string, string> $query
     */
    private function callAuthorize(array $query, callable $fn): void
    {
        $request = (new Request('GET', '/Authorize?' . http_build_query($query)))->withQueryParams($query);
        $request->setParameter('accept', '');
        $this->api->call($request, $fn, false);
    }

    /**
     * Accept an authorization request and return the query parameters of the redirection to the client.
     * @param array<string, string> $query
     * @return array<string, string>
     */
    private function getAuthorizationRedirectParams(array $query): array
    {
        $location = null;
        $this->callAuthorize($query, function ($call) use (&$location) {
            $call->response
                ->status(fn($status) => $this->assertEquals(302, $status))
                ->headers(function ($headers) use (&$location) {
                    $location = $headers['Location'];
                });
        });
        $this->assertStringStartsWith(self::PKCE_REDIRECT_URI . '?', $location);
        parse_str(parse_url($location, PHP_URL_QUERY), $params);
        return $params;
    }

    public function testOAuthAuthCodePKCEPublicClient(): void
    {
        $client_id = $this->createOAuthClient(__FUNCTION__, false, ['authorization_code']);
        $this->loginWeb();

        $verifier = bin2hex(random_bytes(32));
        $query = [
            'response_type' => 'code',
            'client_id' => $client_id,
            'redirect_uri' => self::PKCE_REDIRECT_URI,
            'scope' => 'api',
            'state' => 'test_state',
            'code_challenge' => self::getPKCEChallenge($verifier),
            'code_challenge_method' => 'S256',
        ];
        $params = $this->getAuthorizationRedirectParams($query);
        $this->assertEquals('test_state', $params['state']);
        $this->assertNotEmpty($params['code']);

        $token_request_data = [
            'grant_type' => 'authorization_code',
            'client_id' => $client_id,
            'redirect_uri' => self::PKCE_REDIRECT_URI,
            'code' => $params['code'],
        ];

        // Invalid code verifier
        $request = new Request('POST', '/Token', ['Content-Type' => 'application/json'], json_encode($token_request_data + [
            'code_verifier' => bin2hex(random_bytes(32)),
        ]));
        $this->api->call($request, function ($call) {
            $call->response
                ->status(fn($status) => $this->assertEquals(400, $status))
                ->jsonContent(fn($content) => $this->assertEquals('invalid_grant', $content['error']));
        }, false);

        // Request a new authorization code
        $params = $this->getAuthorizationRedirectParams($query);
        $token_request_data['code'] = $params['code'];

        // Valid code verifier without any client secret
        $request = new Request('POST', '/Token', ['Content-Type' => 'application/json'], json_encode($token_request_data + [
            'code_verifier' => $verifier,
        ]));
        $this->api->call($request, function ($call) {
            $call->response
                ->status(fn($status) => $this->assertEquals(200, $status))
                ->jsonContent(function ($content) {
                    $this->assertEquals('Bearer', $content['token_type']);
                    $this->assertNotEmpty($content['access_token']);
                    $this->assertNotEmpty($content['refresh_token']);
                });
        }, false);
    }

    public function testOAuthAuthCodePublicClientRequiresS256PKCE(): void
    {
        $client_id = $this->createOAuthClient(__FUNCTION__, false, ['authorization_code']);
        $this->loginWeb();

        $query = [
            'response_type' => 'code',
            'client_id' => $client_id,
            'redirect_uri' => self::PKCE_REDIRECT_URI,
            'scope' => 'api',
        ];

        // No code challenge
        $this->callAuthorize($query, function ($call) {
            $call->response
                ->status(fn($status) => $this->assertEquals(400, $status))
                ->jsonContent(function ($content) {
                    $this->assertEquals('invalid_request', $content['error']);
                    $this->assertEquals('Code challenge must be provided for public clients', $content['hint']);
                });
        });

        // Plain code challenge method
        $this->callAuthorize($query + [
            'code_challenge' => bin2hex(random_bytes(32)),
            'code_challenge_method' => 'plain',
        ], function ($call) {
            $call->response
                ->status(fn($status) => $this->assertEquals(400, $status))
                ->jsonContent(function ($content) {
                    $this->assertEquals('invalid_request', $content['error']);
                    $this->assertEquals('Public clients must use the S256 code challenge method', $content['hint']);
                });
        });
    }

    public function testOAuthPublicClientGrantRestrictions(): void
    {
        $client_id = $this->createOAuthClient(__FUNCTION__, false, ['authorization_code']);

        // Grants not allowed for the client are rejected even without a secret to validate
        $request = new Request('POST', '/Token', ['Content-Type' => 'application/json'], json_encode([
            'grant_type' => 'password',
            'client_id' => $client_id,
            'username' => TU_USER,
            'password' => TU_PASS,
            'scope' => 'api',
        ]));
        $this->api->call($request, function ($call) {
            $call->response
                ->status(fn($status) => $this->assertEquals(400, $status))
                ->jsonContent(fn($content) => $this->assertEquals('unauthorized_client', $content['error']));
        }, false);
    }

    public function testOAuthAuthCodeConfidentialClientWithoutPKCE(): void
    {
        global $DB;

        $client_id = $this->createOAuthClient(__FUNCTION__, true, ['authorization_code']);
        $secret = (new \GLPIKey())->decrypt($DB->request([
            'SELECT' => ['secret'],
            'FROM' => \OAuthClient::getTable(),
            'WHERE' => ['identifier' => $client_id],
        ])->current()['secret']);
        $this->loginWeb();

        $params = $this->getAuthorizationRedirectParams([
            'response_type' => 'code',
            'client_id' => $client_id,
            'redirect_uri' => self::PKCE_REDIRECT_URI,
            'scope' => 'api',
        ]);

        $token_request_data = [
            'grant_type' => 'authorization_code',
            'client_id' => $client_id,
            'redirect_uri' => self::PKCE_REDIRECT_URI,
            'code' => $params['code'],
        ];

        // Confidential clients still need their secret
        $request = new Request('POST', '/Token', ['Content-Type' => 'application/json'], json_encode($token_request_data));
        $this->api->call($request, function ($call) {
            $call->response
                ->status(fn($status) => $this->assertEquals(400, $status))
                ->jsonContent(fn($content) => $this->assertEquals('invalid_request', $content['error']));
        }, false);

        $request = new Request('POST', '/Token', ['Content-Type' => 'application/json'], json_encode($token_request_data + [
            'client_secret' => $secret,
        ]));
        $this->api->call($request, function ($call) {
            $call->response
                ->status(fn($status) => $this->assertEquals(200, $status))
                ->jsonContent(fn($content) => $this->assertNotEmpty($content['access_token']));
        }, false);
    }

    public function testOAuthAuthorizeLoginRedirectKeepsPKCEParams(): void
    {
        $client_id = $this->createOAuthClient(__FUNCTION__, false, ['authorization_code']);
        $challenge = self::getPKCEChallenge(bin2hex(random_bytes(32)));

        $location = null;
        $this->callAuthorize([
            'response_type' => 'code',
            'client_id' => $client_id,
            'redirect_uri' => self::PKCE_REDIRECT_URI,
            'scope' => 'api',
            'state' => 'test_state',
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
        ], function ($call) use (&$location) {
            $call->response
                ->status(fn($status) => $this->assertEquals(302, $status))
                ->headers(function ($headers) use (&$location) {
                    $location = $headers['Location'];
                });
        });

        parse_str(parse_url($location, PHP_URL_QUERY), $login_params);
        parse_str(parse_url($login_params['redirect'], PHP_URL_QUERY), $authorize_params);
        $this->assertEquals('test_state', $authorize_params['state']);
        $this->assertEquals($challenge, $authorize_params['code_challenge']);
        $this->assertEquals('S256', $authorize_params['code_challenge_method']);
    }

    public function testStatusScope()
    {
        $this->login(api_options: ['scope' => 'api']);
        $this->api->call(new Request('GET', '/Status'), function ($call) {
            $call->response
                ->isAccessDenied()
                ->jsonContent(function ($content) {
                    $this->assertEquals('You do not have the required scope(s) to access this endpoint.', $content['detail']);
                });
        });
        $this->login(api_options: ['scope' => 'status']);
        $this->api->call(new Request('GET', '/Status'), function ($call) {
            $call->response
                ->isOK();
        });
    }

    public function testSessionEntityTree()
    {
        $this->login();
        $this->api->call(new Request('GET', '/Session/EntityTree'), function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertIsArray($content);
                    $this->assertNotEmpty($content);
                    $fn_check_node = function ($node) use (&$fn_check_node) {
                        $this->assertArrayHasKey('key', $node);
                        $this->assertArrayHasKey('label', $node);
                        $this->assertArrayHasKey('children', $node);
                        $this->assertIsArray($node['children']);
                        foreach ($node['children'] as $child) {
                            $fn_check_node($child);
                        }
                    };
                    foreach ($content as $root_node) {
                        $fn_check_node($root_node);
                    }
                });
        });
    }
}
