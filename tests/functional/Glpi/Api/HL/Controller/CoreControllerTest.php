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
use Glpi\Security\SessionTracker;
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

    public function testAuthorizePreservesPkceParamsOnLoginRedirect(): void
    {
        // Create an OAuth client using the authorization code grant
        $client = $this->createItem(\OAuthClient::class, [
            'name' => __FUNCTION__,
            'is_active' => 1,
            'is_confidential' => 1,
            'grants' => ['authorization_code'],
        ]);

        // No session is authenticated
        $request = new Request('GET', '/authorize');
        $request = $request->withQueryParams([
            'response_type'         => 'code',
            'client_id'             => $client->fields['identifier'],
            'redirect_uri'          => '/api.php/oauth2/redirection',
            'scope'                 => 'user',
            'state'                 => 'xyzABC123',
            'code_challenge'        => 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cU',
            'code_challenge_method' => 'S256',
        ]);

        $this->api->call($request, function ($call) {
            $call->response
                ->status(fn($status) => $this->assertEquals(302, $status))
                ->headers(function ($headers) {
                    $location = $headers['Location'];
                    $this->assertStringContainsString('redirect=', $location);
                    $redirect_target = urldecode(explode('redirect=', $location, 2)[1]);
                    $this->assertStringContainsString('code_challenge=', $redirect_target);
                    $this->assertStringContainsString('code_challenge_method=', $redirect_target);
                    $this->assertStringContainsString('state=', $redirect_target);
                });
        }, false);
    }

    public function testAuthorizationCodeGrantLinksLoginSession(): void
    {
        global $DB;

        $client = $this->createItem(\OAuthClient::class, [
            'name' => __FUNCTION__,
            'is_active' => 1,
            'is_confidential' => 1,
            'grants' => ['authorization_code', 'refresh_token'],
            'scopes' => ['api'],
        ]);
        $client_secret = (new \GLPIKey())->decrypt($DB->request([
            'SELECT' => ['secret'],
            'FROM' => \OAuthClient::getTable(),
            'WHERE' => ['id' => $client->getID()],
        ])->current()['secret']);

        $browser_user_agent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36';
        $previous_user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $_SERVER['HTTP_USER_AGENT'] = $browser_user_agent;

        try {
            // The user is not logged in yet, so the client authorization starts with the login
            $this->logOut();
            $this->api->call($this->getAuthorizeRequest($client), function ($call) {
                $call->response->status(fn($status) => $this->assertEquals(302, $status));
            }, false);

            // Logging in keeps track of the client the user logged in for
            $auth = new \Auth();
            $auth->user = getItemByTypeName(\User::class, TU_USER);
            $auth->auth_succeded = true;
            $auth->setAuthType(\Auth::DB_GLPI);
            \Session::init($auth);
            $login_session_uid = \Session::getLoginSessionUID();
            $this->assertNotNull($login_session_uid);

            $code = $this->acceptAuthorization($client);

            // The client exchanges the code for tokens from its own backend
            $_SERVER['HTTP_USER_AGENT'] = 'GuzzleHttp/7';
            $refresh_token = null;
            $request = new Request('POST', '/token', ['Content-Type' => 'application/json'], json_encode([
                'grant_type'    => 'authorization_code',
                'client_id'     => $client->fields['identifier'],
                'client_secret' => $client_secret,
                'redirect_uri'  => '/api.php/oauth2/redirection',
                'code'          => $code,
            ]));
            $this->api->call($request, function ($call) use (&$refresh_token) {
                $call->response
                    ->isOK()
                    ->jsonContent(function ($content) use (&$refresh_token) {
                        $refresh_token = $content['refresh_token'];
                    });
            }, false);

            $access_tokens = iterator_to_array($DB->request([
                'SELECT' => ['login_session_uid'],
                'FROM' => 'glpi_oauth_access_tokens',
                'WHERE' => ['client' => $client->fields['identifier']],
            ]));
            $this->assertCount(1, $access_tokens);
            $this->assertEquals($login_session_uid, $access_tokens[0]['login_session_uid']);

            // Refreshed tokens stay linked to the same login session
            $request = new Request('POST', '/token', ['Content-Type' => 'application/json'], json_encode([
                'grant_type'    => 'refresh_token',
                'client_id'     => $client->fields['identifier'],
                'client_secret' => $client_secret,
                'refresh_token' => $refresh_token,
            ]));
            $this->api->call($request, function ($call) {
                $call->response->isOK();
            }, false);

            $access_tokens = iterator_to_array($DB->request([
                'SELECT' => ['login_session_uid'],
                'FROM' => 'glpi_oauth_access_tokens',
                'WHERE' => ['client' => $client->fields['identifier']],
            ]));
            $this->assertCount(1, $access_tokens);
            $this->assertEquals($login_session_uid, $access_tokens[0]['login_session_uid']);

            // A single session is listed for the login and the client authorization
            $sessions = (new SessionTracker())->getSessions(\Session::getLoginUserID());
            $this->assertCount(1, $sessions);
            $this->assertEquals('api', $sessions[0]['type_raw']);
            $this->assertTrue($sessions[0]['current_session']);
            $this->assertStringContainsString(__FUNCTION__, $sessions[0]['details']);
            $this->assertStringContainsString('api', $sessions[0]['details']);
            // The user agent is the one of the browser the client was authorized from, not the one of the client backend
            $this->assertStringContainsString('Chrome 140.0', $sessions[0]['details']);
        } finally {
            if ($previous_user_agent === null) {
                unset($_SERVER['HTTP_USER_AGENT']);
            } else {
                $_SERVER['HTTP_USER_AGENT'] = $previous_user_agent;
            }
        }
    }

    public function testAuthorizationCodeGrantKeepsExistingLoginSessionSeparate(): void
    {
        global $DB;

        $client = $this->createItem(\OAuthClient::class, [
            'name' => __FUNCTION__,
            'is_active' => 1,
            'is_confidential' => 1,
            'grants' => ['authorization_code'],
            'scopes' => ['api'],
        ]);
        $client_secret = (new \GLPIKey())->decrypt($DB->request([
            'SELECT' => ['secret'],
            'FROM' => \OAuthClient::getTable(),
            'WHERE' => ['id' => $client->getID()],
        ])->current()['secret']);

        $previous_user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        try {
            // The user was already logged in from their browser before authorizing the client
            $this->loginWeb();
            $login_session_uid = \Session::getLoginSessionUID();
            $this->assertNotNull($login_session_uid);

            $code = $this->acceptAuthorization($client);

            // The client requests the token from the browser
            $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Linux x86_64; rv:140.0) Gecko/20100101 Firefox/140.0';
            $request = new Request('POST', '/token', ['Content-Type' => 'application/json'], json_encode([
                'grant_type'    => 'authorization_code',
                'client_id'     => $client->fields['identifier'],
                'client_secret' => $client_secret,
                'redirect_uri'  => '/api.php/oauth2/redirection',
                'code'          => $code,
            ]));
            $this->api->call($request, function ($call) {
                $call->response->isOK();
            }, false);

            $access_tokens = iterator_to_array($DB->request([
                'SELECT' => ['login_session_uid'],
                'FROM' => 'glpi_oauth_access_tokens',
                'WHERE' => ['client' => $client->fields['identifier']],
            ]));
            $this->assertCount(1, $access_tokens);
            $this->assertNull($access_tokens[0]['login_session_uid']);

            // The browser session and the client session are listed separately
            $sessions = (new SessionTracker())->getSessions(\Session::getLoginUserID());
            $this->assertCount(2, $sessions);
            $sessions_by_type = array_column($sessions, null, 'type_raw');
            $this->assertTrue($sessions_by_type['web']['current_session']);
            $this->assertEquals($login_session_uid, $sessions_by_type['web']['internal_identifier']);
            $this->assertFalse($sessions_by_type['api']['current_session']);
            $this->assertStringContainsString(__FUNCTION__, $sessions_by_type['api']['details']);
            // Without a linked login session, the user agent of the client that requested the token is used
            $this->assertStringContainsString('Firefox 140.0', $sessions_by_type['api']['details']);
        } finally {
            if ($previous_user_agent === null) {
                unset($_SERVER['HTTP_USER_AGENT']);
            } else {
                $_SERVER['HTTP_USER_AGENT'] = $previous_user_agent;
            }
        }
    }

    private function getAuthorizeRequest(\OAuthClient $client): Request
    {
        $request = new Request('GET', '/authorize');
        return $request->withQueryParams([
            'response_type' => 'code',
            'client_id'     => $client->fields['identifier'],
            'redirect_uri'  => '/api.php/oauth2/redirection',
            'scope'         => 'api',
        ]);
    }

    /**
     * Accept the authorization of the client by the logged in user.
     * @return string The authorization code
     */
    private function acceptAuthorization(\OAuthClient $client): string
    {
        $request = $this->getAuthorizeRequest($client);
        // The router only fills the request parameters checked for the user's choice from $_REQUEST and the body, not from the query params
        $request->setParameter('accept', 1);
        $code = null;
        $this->api->call($request, function ($call) use (&$code) {
            $call->response
                ->status(fn($status) => $this->assertEquals(302, $status))
                ->headers(function ($headers) use (&$code) {
                    parse_str(parse_url($headers['Location'], PHP_URL_QUERY), $query);
                    $code = $query['code'] ?? null;
                });
        }, false);
        $this->assertNotNull($code);
        return $code;
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
