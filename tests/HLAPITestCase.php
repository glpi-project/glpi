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

use Glpi\Api\HL\Controller\AbstractController;
use Glpi\Api\HL\Controller\CoreController;
use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\Middleware\InternalAuthMiddleware;
use Glpi\Api\HL\Middleware\ResultFormatterMiddleware;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\RoutePath;
use Glpi\Api\HL\Router;
use Glpi\Features\AssignableItemInterface;
use Glpi\Http\Request;
use Glpi\Http\Response;

/**
 * @property HLAPIHelper $api
 */
class HLAPITestCase extends DbTestCase
{
    private $bearer_token = null;

    public function setUp(): void
    {
        global $CFG_GLPI;
        parent::setUp();
        $CFG_GLPI['enable_hlapi'] = 1;
    }
    public function tearDown(): void
    {
        // kill session
        Session::destroy();
        // Reset the router
        Router::resetInstance();
        parent::tearDown();
    }

    public function resetSession()
    {
        parent::resetSession();
    }

    public function loginWeb(string $user_name = TU_USER, string $user_pass = TU_PASS, bool $noauto = true, bool $expected = true): Auth
    {
        return parent::login($user_name, $user_pass, $noauto, $expected);
    }

    public function login(string $user_name = TU_USER, string $user_pass = TU_PASS, bool $noauto = true, bool $expected = true, array $api_options = []): Auth
    {
        $request = new Request('POST', '/token', [
            'Content-Type' => 'application/json',
        ], json_encode([
            'grant_type' => 'password',
            'client_id' => TU_OAUTH_CLIENT_ID,
            'client_secret' => TU_OAUTH_CLIENT_SECRET,
            'username' => $user_name,
            'password' => $user_pass,
            'scope' => $api_options['scope'] ?? 'email user api inventory status graphql',
        ]));
        $this->api->call($request, function ($call) {
            /** @var HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertEquals('Bearer', $content['token_type']);
                    $this->assertNotEmpty($content['expires_in']);
                    $this->assertNotEmpty($content['access_token']);
                    $this->assertNotEmpty($content['refresh_token']);
                    $this->bearer_token = $content['access_token'];
                });
        });
        return new Auth();
    }

    public function getCurrentBearerToken(): ?string
    {
        return $this->bearer_token;
    }

    protected function checkSimpleContentExpect(array $content, array $expected): void
    {
        if (isset($expected['count'])) {
            $operator = $expected['count'][0];
            switch ($operator) {
                case '>':
                    $this->assertGreaterThan($expected['count'][1], count($content));
                    break;
                case '>=':
                    $this->assertGreaterThanOrEqual($expected['count'][1], count($content));
                    break;
                case '<':
                    $this->assertLessThan($expected['count'][1], count($content));
                    break;
                case '<=':
                    $this->assertLessThanOrEqual($expected['count'][1], count($content));
                    break;
                case '=':
                    $this->assertEquals($expected['count'][1], count($content));
                    break;
            }
        }

        if (isset($expected['fields'])) {
            foreach ($expected['fields'] as $field => $value) {
                // $field could be an array path. We need to check each part of the path
                $parts = explode('.', $field);
                $current = $content;
                foreach ($parts as $part) {
                    $current = $current[$part];
                }
                $this->assertEquals($value, $current);
            }
        }
    }

    public function __get($name)
    {
        if ($name === 'api') {
            return new HLAPIHelper(Router::getInstance(), $this);
        }
    }
}

// @codingStandardsIgnoreStart
final class HLAPIHelper
{
    private Router $router;
    private HLAPITestCase $test;
    private string $api_version;

    // @codingStandardsIgnoreEnd

    /**
     * @param Router $router
     * @param HLAPITestCase $test
     * @param string|null $api_version The API version to use. Cannot be specified or overriden by the request URL. Defaults to the current API version.
     */
    public function __construct(Router $router, HLAPITestCase $test, ?string $api_version = null)
    {
        $this->router = $router;
        $this->test = $test;
        $this->api_version = $api_version ?? $router::API_VERSION;
    }

    /**
     * Get a new API helper with a specific API version
     * @param string $api_version
     * @return HLAPIHelper
     */
    public function withVersion(string $api_version)
    {
        return new HLAPIHelper($this->router, $this->test, $api_version);
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * @param string $endpoint
     * @return RoutePath[]
     */
    private function getRoutesForEndpoint(string $endpoint): array
    {
        $methods = ['GET', 'POST', 'PATCH', 'DELETE'];
        $all_routes = [];
        foreach ($methods as $method) {
            $all_routes = [...$all_routes, ...$this->router->matchAll(new Request($method, $endpoint))];
            $all_routes = [...$all_routes, ...$this->router->matchAll(new Request($method, $endpoint . '/1'))];
        }
        // Remove default routes
        $all_routes = array_filter($all_routes, static fn($route) => !($route->getController() === CoreController::class && $route->getMethod()->getShortName() === 'defaultRoute'));
        return $all_routes;
    }

    private function getRoutePathBodySchema(RoutePath $route_path, string $method, array $attributes = []): ?array
    {
        $doc = $route_path->getRouteDoc($method);
        $this->test->assertNotNull($doc, 'No documentation found for route ' . $route_path->getRoutePath() . ' with method ' . $method);

        $params = $doc->getParameters();
        $body_params = array_filter($params, static fn($param) => $param->getName() === '_' && $param->getLocation() === 'body');
        if (empty($body_params)) {
            return null;
        }
        $this->test->assertCount(1, $body_params, 'Multiple body parameters found for route ' . $route_path->getRoutePath() . ' with method ' . $method);
        $body_param = array_values($body_params)[0];
        $schema = $body_param->getSchema();
        if ($schema instanceof Doc\SchemaReference) {
            $is_array = str_ends_with($schema->getRef(), '[]');
            $schema = Doc\SchemaReference::resolveRef($schema->getRef(), $route_path->getController(), $attributes, $this->api_version);
            if ($is_array) {
                $schema = [
                    'type' => Doc\Schema::TYPE_ARRAY,
                    'items' => $schema,
                ];
            }
        }
        return $schema;
    }

    private function getRoutePathResponseSchema(RoutePath $route_path, string $method, $status = 200, array $attributes = []): ?array
    {
        $doc = $route_path->getRouteDoc($method);
        $this->test->assertNotNull($doc, 'No documentation found for route ' . $route_path->getRoutePath() . ' with method ' . $method);

        $responses = $doc->getResponses();
        $response = array_filter($responses, static fn($response) => $response->getStatusCode() === $status)[0] ?? null;
        if ($response === null) {
            return null;
        }
        $schema = $response->getSchema();
        if ($schema instanceof Doc\SchemaReference) {
            $schema = Doc\SchemaReference::resolveRef($schema->getRef(), $route_path->getController(), $attributes, $this->api_version);
        }
        return $schema;
    }

    private function routePathHasParameter(RoutePath $route_path, string $parameter, string $location): bool
    {
        $doc = $route_path->getRouteDoc('GET');
        $this->test->assertNotNull($doc, 'No documentation found for route ' . $route_path->getRoutePath() . ' with method GET');

        $params = $doc->getParameters();
        $matches = array_filter($params, static fn($param) => $param->getName() === $parameter && $param->getLocation() === $location);
        return !empty($matches);
    }

    private function routePathHasMiddleware(RoutePath $route_path, string $middleware_class): bool
    {
        $middlewares = $route_path->getMiddlewares();
        $matches = array_filter($middlewares, static fn($middleware) => $middleware === $middleware_class);
        return !empty($matches);
    }

    public function hasMatch(Request $request)
    {
        if ($this->test->getCurrentBearerToken() !== null) {
            $request = $request->withHeader('Authorization', 'Bearer ' . $this->test->getCurrentBearerToken());
        }
        $match = $this->router->match($request);
        $is_default_route = false;
        if (
            $match !== null
            && ($match->getController() === CoreController::class && $match->getMethod()->getShortName() === 'defaultRoute')
        ) {
            $is_default_route = true;
        }
        return $match !== null && !$is_default_route;
    }

    /**
     * @param Request $request
     * @param callable(HLAPICallAsserter): void $fn
     * @return self
     */
    public function call(Request $request, callable $fn, bool $auto_auth_header = true): self
    {
        if ($auto_auth_header && $this->test->getCurrentBearerToken() !== null) {
            $request = $request->withHeader('Authorization', 'Bearer ' . $this->test->getCurrentBearerToken());
        }
        $request = $request->withHeader('GLPI-API-Version', $this->api_version);
        $response = $this->router->handleRequest($request);
        $fn(new HLAPICallAsserter($this->test, $this->router, $response));
        return $this;
    }

    public function autoTestCRUD(string $endpoint, array $create_params = [], array $update_params = []): self
    {
        $this->test->resetSession();
        $this->test->login();
        $unique_id = __FUNCTION__;

        /** @var RoutePath[] $routes */
        $routes = [...$this->getRoutesForEndpoint($endpoint), ...$this->getRoutesForEndpoint($endpoint . '/{id}')];
        $all_methods = [];
        foreach ($routes as $route) {
            $all_methods = [...$all_methods, ...$route->getRouteMethods()];
        }
        $required_methods = ['POST', 'GET', 'PATCH', 'DELETE'];
        $missing_methods = array_diff($required_methods, $all_methods);
        $this->test->assertEmpty($missing_methods, 'The endpoint "' . $endpoint . '" does not support the following CRUD methods: ' . implode(', ', $missing_methods));

        $schema = null;
        foreach ($routes as $route) {
            if (in_array('POST', $route->getRouteMethods(), true)) {
                $attributes = $route->getAttributesFromPath($endpoint);
                $schema = $this->getRoutePathBodySchema($route, 'POST', $attributes);
                break;
            }
        }
        $this->test->assertNotNull($schema, 'The POST route for endpoint "' . $endpoint . '" does not have a body schema');
        $this->test->assertEquals('object', $schema['type'], 'The POST route for endpoint "' . $endpoint . '" body schema is not for an object');
        $schema_json = json_encode($schema);
        $flattened_props = Doc\Schema::flattenProperties($schema['properties'] ?? []);

        foreach ($routes as $route) {
            $attributes = $route->getAttributesFromPath($endpoint);
            if (in_array('GET', $route->getRouteMethods(), true) && str_ends_with($route->getRoutePath(), '/{id}')) {
                $get_schema = $this->getRoutePathResponseSchema(route_path: $route, method: 'GET', attributes: $attributes);
                $this->test->assertNotNull($get_schema, 'The GET route for endpoint "' . $endpoint . '" does not have a response schema');
                $this->test->assertEquals($schema_json, json_encode($get_schema), 'The POST route for endpoint "' . $endpoint . '" body schema does not match the GET route response schema');
            } elseif (in_array('PATCH', $route->getRouteMethods(), true)) {
                $patch_schema = $this->getRoutePathBodySchema(route_path: $route, method: 'PATCH', attributes: $attributes);
                $this->test->assertNotNull($patch_schema, 'The PATCH route for endpoint "' . $endpoint . '" does not have a body schema');
                $this->test->assertEquals($schema_json, json_encode($patch_schema), 'The input body for the POST route path and the body for the PATCH route path of endpoint "' . $endpoint . '" do not match');
            } elseif (in_array('DELETE', $route->getRouteMethods(), true)) {
                $delete_schema = $this->getRoutePathBodySchema(route_path: $route, method: 'DELETE', attributes: $attributes);
                $this->test->assertNull($delete_schema, 'The DELETE route for endpoint "' . $endpoint . '" has a body schema');
            }
        }

        // CREATE
        $request = new Request('POST', $endpoint);
        if (isset($schema['properties']['name']) && !isset($create_params['name'])) {
            $create_params['name'] = $unique_id;
            if (empty($update_params)) {
                $update_params['name'] = $unique_id . '2';
            }
        }
        if (isset($schema['properties']['entity']) && !isset($create_params['entity'])) {
            $create_params['entity'] = getItemByTypeName('Entity', '_test_root_entity', true);
        }
        foreach ($create_params as $key => $value) {
            $request->setParameter($key, $value);
        }
        // remove writeonly properties from $create_params so checks below don't fail
        foreach ($flattened_props as $key => $value) {
            if (isset($value['writeOnly']) && $value['writeOnly'] === true) {
                unset($create_params[$key]);
            }
        }

        $this->call(new Request('POST', $endpoint), function ($call) {
            /** @var HLAPICallAsserter $call */
            $call->response->isUnauthorizedError();
        }, false);

        $new_item_location = null;
        $this->call($request, function ($call) use (&$new_item_location, $endpoint) {
            /** @var HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($endpoint) {
                    $this->test->assertArrayHasKey('id', $content, 'The response for the POST route path of endpoint "' . $endpoint . '" does not have an "id" field');
                    $this->test->assertGreaterThan(0, $content['id'], 'The response for the POST route path of endpoint "' . $endpoint . '" has an "id" field that is not valid');
                    $this->test->assertArrayHasKey('href', $content, 'The response for the POST route path of endpoint "' . $endpoint . '" does not have an "href" field');
                    $this->test->assertEqualsIgnoringCase($content['href'], $endpoint . '/' . $content['id'], 'The response for the POST route path of endpoint "' . $endpoint . '" has an "href" field that is not valid');
                })
                ->headers(function ($headers) use (&$new_item_location) {
                    $this->test->assertNotEmpty($headers['Location']);
                    $new_item_location = $headers['Location'];
                });
        });

        $this->call(new Request('PATCH', $new_item_location), function ($call) {
            /** @var HLAPICallAsserter $call */
            $call->response->isUnauthorizedError();
        }, false);
        $this->call(new Request('GET', $new_item_location), function ($call) {
            /** @var HLAPICallAsserter $call */
            $call->response->isUnauthorizedError();
        }, false);
        $this->call(new Request('DELETE', $new_item_location), function ($call) {
            /** @var HLAPICallAsserter $call */
            $call->response->isUnauthorizedError();
        }, false);

        // Get the new item
        $this->call(new Request('GET', $new_item_location), function ($call) use ($schema, $endpoint, $create_params) {
            /** @var HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->matchesSchema($schema, 'The response for the GET route path of endpoint "' . $endpoint . '" does not match the schema', 'read')
                ->jsonContent(function ($content) use ($create_params) {
                    foreach ($create_params as $key => $value) {
                        if (is_array($content[$key]) && isset($content[$key]['id'])) {
                            $this->test->assertEquals($value, $content[$key]['id']);
                        } else {
                            $this->test->assertEquals($value, $content[$key]);
                        }
                    }
                });
        });

        // Update the new item
        $request = new Request('PATCH', $new_item_location);
        foreach ($update_params as $key => $value) {
            $request->setParameter($key, $value);
        }
        $this->call($request, function ($call) use ($schema, $endpoint) {
            /** @var HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->matchesSchema($schema, 'The response for the PATCH route path of endpoint "' . $endpoint . '" does not match the schema', 'read');
        });

        // Get the new item again and verify that the name has been updated
        $this->call(new Request('GET', $new_item_location), function ($call) use ($update_params) {
            /** @var HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($update_params) {
                    foreach ($update_params as $key => $value) {
                        if (is_array($content[$key]) && isset($content[$key]['id'])) {
                            $this->test->assertEquals($value, $content[$key]['id']);
                        } else {
                            $this->test->assertEquals($value, $content[$key]);
                        }
                    }
                });
        });

        // Delete the new item
        $this->call(new Request('DELETE', $new_item_location), function ($call) {
            /** @var HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(fn($content) => $this->test->assertNull($content));
        });

        $itemtype = $schema['x-itemtype'];
        if (is_subclass_of($itemtype, CommonDBTM::class)) {
            $item = new $itemtype();
            if ($item->maybeDeleted()) {
                // Try getting the new item again. It should still exist.
                $this->call(new Request('GET', $new_item_location), function ($call) use ($update_params) {
                    /** @var HLAPICallAsserter $call */
                    $call->response
                        ->isOK()
                        ->jsonContent(function ($content) use ($update_params) {
                            foreach ($update_params as $key => $value) {
                                if (is_array($content[$key]) && isset($content[$key]['id'])) {
                                    $this->test->assertEquals($value, $content[$key]['id']);
                                } else {
                                    $this->test->assertEquals($value, $content[$key]);
                                }
                            }
                        });
                });

                // Force delete the new item
                $request = new Request('DELETE', $new_item_location);
                $request->setParameter('force', 1);
                $this->call($request, function ($call) {
                    /** @var HLAPICallAsserter $call */
                    $call->response
                        ->isOK()
                        ->jsonContent(fn($content) => $this->test->assertNull($content));
                });
            }
        }

        // Try getting the new item again (should be a 404)
        $this->call(new Request('GET', $new_item_location), function ($call) {
            /** @var HLAPICallAsserter $call */
            $call->response
                ->isNotFoundError();
        });

        return $this;
    }

    /**
     * Automatically tests CRUD endpoints for a specific itemtype to check that responses are correct when the user lacks permissions.
     * @param string $endpoint The base endpoint to test
     * @param class-string<CommonDBTM> $itemtype The itemtype related to this endpoint
     * @param int $items_id A specific, existing itm ID to test with
     * @param callable|null $deny_read A callable to customize how READ access is denied.
     *     The callable should "reset" the permissions in a way so that only READ is denied.
     *     If not specified, the permissions for the specified itemtype are set to (ALLSTANDARDRIGHT & ~READ).
     * @param callable|null $deny_create A callable to customize how CREATE access is denied.
     *     The callable should "reset" the permissions in a way so that only CREATE is denied.
     *     If not specified, the permissions for the specified itemtype are set to (ALLSTANDARDRIGHT & ~CREATE).
     * @param callable|null $deny_update A callable to customize how UPDATE access is denied.
     *     The callable should "reset" the permissions in a way so that only UPDATE is denied.
     *     If not specified, the permissions for the specified itemtype are set to (ALLSTANDARDRIGHT & ~UPDATE).
     * @param callable|null $deny_delete A callable to customize how DELETE access is denied.
     *     The callable should "reset" the permissions in a way so that only DELETE is denied.
     *     If not specified, the permissions for the specified itemtype are set to (ALLSTANDARDRIGHT & ~DELETE).
     * @param callable|null $deny_purge A callable to customize how PURGE access is denied.
     *     The callable should "reset" the permissions in a way so that only PURGE is denied.
     *     If not specified, the permissions for the specified itemtype are set to (ALLSTANDARDRIGHT & ~PURGE).
     * @param callable|null $deny_restore A callable to customize how RESTORE access is denied.
     *     The callable should "reset" the permissions in a way so that only RESTORE is denied.
     *     If not specified, the permissions for the specified itemtype are set to (ALLSTANDARDRIGHT & ~DELETE & ~UPDATE).
     * @return $this
     */
    public function autoTestCRUDNoRights(
        string $endpoint,
        string $itemtype,
        int $items_id,
        ?callable $deny_read = null,
        ?callable $deny_create = null,
        ?callable $deny_update = null,
        ?callable $deny_delete = null,
        ?callable $deny_purge = null,
        ?callable $deny_restore = null
    ): self {
        $this->test->loginWeb();
        $this->router->registerAuthMiddleware(new InternalAuthMiddleware());
        $item = getItemForItemtype($itemtype);

        $deny_read ??= static function () use ($itemtype) {
            $_SESSION['glpiactiveprofile'][$itemtype::$rightname] = ALLSTANDARDRIGHT & ~READ;
        };
        $deny_create ??= static function () use ($itemtype) {
            $_SESSION['glpiactiveprofile'][$itemtype::$rightname] = ALLSTANDARDRIGHT & ~CREATE;
        };
        $deny_update ??= static function () use ($itemtype) {
            $_SESSION['glpiactiveprofile'][$itemtype::$rightname] = ALLSTANDARDRIGHT & ~UPDATE;
        };
        $deny_delete ??= static function () use ($itemtype) {
            $_SESSION['glpiactiveprofile'][$itemtype::$rightname] = ALLSTANDARDRIGHT & ~DELETE;
        };
        $deny_purge ??= static function () use ($itemtype) {
            $_SESSION['glpiactiveprofile'][$itemtype::$rightname] = ALLSTANDARDRIGHT & ~PURGE;
        };
        $deny_restore ??= static function () use ($itemtype) {
            $_SESSION['glpiactiveprofile'][$itemtype::$rightname] = ALLSTANDARDRIGHT & ~DELETE & ~UPDATE;
        };

        $deny_read();
        // No READ = No List
        $this->call(new Request('GET', $endpoint), function ($call) {
            /** @var HLAPICallAsserter $call */
            $call->response->isAccessDenied();
        }, false);
        // No READ = No GET
        $this->call(new Request('GET', $endpoint . '/' . $items_id), function ($call) {
            /** @var HLAPICallAsserter $call */
            $call->response->isNotOK();
        }, false);

        $deny_create();
        // No CREATE = Access denied
        $this->call(new Request('POST', $endpoint), function ($call) {
            /** @var HLAPICallAsserter $call */
            $call->response->isAccessDenied();
        }, false);

        $deny_update();
        $update_request = new Request('PATCH', $endpoint . '/' . $items_id);
        // Property does not need to exist, but we try to act like a real update
        $update_request->setParameter('name', 'updated');
        // No UPDATE = Access denied
        $this->call($update_request, function ($call) {
            /** @var HLAPICallAsserter $call */
            $call->response->isAccessDenied();
        }, false);

        if ($item->maybeDeleted()) {
            $deny_delete();
            // No DELETE = Access denied
            $this->call(new Request('DELETE', $endpoint . '/' . $items_id), function ($call) {
                /** @var HLAPICallAsserter $call */
                $call->response->isAccessDenied();
            }, false);

            $deny_restore();
            // No RESTORE = Access denied
            $restore_request = new Request('PATCH', $endpoint . '/' . $items_id);
            $restore_request->setParameter('is_deleted', 0);
            $this->call($restore_request, function ($call) {
                /** @var HLAPICallAsserter $call */
                $call->response
                    ->isAccessDenied();
            }, false);
        }

        $deny_purge();
        // No PURGE = Access denied
        $purge_request = new Request('DELETE', $endpoint . '/' . $items_id);
        $purge_request->setParameter('force', 1);
        $this->call($purge_request, function ($call) {
            /** @var HLAPICallAsserter $call */
            $call->response->isAccessDenied();
        }, false);

        return $this;
    }

    public function autoTestSearch(string $endpoint, array $dataset, string $unique_field = 'name'): self
    {
        $this->test->resetSession();
        $this->test->assertCount(3, $dataset, 'Dataset for endpoint "' . $endpoint . '" must have at least 3 entries');

        // Search without authorization should return an error
        $this->call(new Request('GET', $endpoint), function ($call) {
            /** @var HLAPICallAsserter $call */
            $call->response
                ->isUnauthorizedError();
        }, false);

        $this->test->login();

        /** @var RoutePath[] $routes */
        $routes = [...$this->getRoutesForEndpoint($endpoint)];
        $search_route = array_filter($routes, static fn($rp) => in_array('GET', $rp->getRouteMethods()))[0] ?? null;

        $this->test->assertNotNull($search_route, 'No GET route found for endpoint "' . $endpoint . '"');
        $response_schema = $this->getRoutePathResponseSchema(route_path: $search_route, method: 'GET', attributes: $search_route->getAttributesFromPath($endpoint));
        $this->test->assertNotNull($response_schema, 'No response schema found for GET route for endpoint "' . $endpoint . '"');
        $this->test->assertEquals('array', $response_schema['type'], 'Response schema for GET route for endpoint "' . $endpoint . '" is not for an array');

        // Search routes should allow filtering, pagination, and sorting
        $this->test->assertTrue($this->routePathHasParameter($search_route, 'filter', 'query'), 'No "filter" query parameter found for GET route for endpoint "' . $endpoint . '"');
        $this->test->assertTrue($this->routePathHasParameter($search_route, 'start', 'query'), 'No "start" query parameter found for GET route for endpoint "' . $endpoint . '"');
        $this->test->assertTrue($this->routePathHasParameter($search_route, 'limit', 'query'), 'No "limit" query parameter found for GET route for endpoint "' . $endpoint . '"');
        $this->test->assertTrue($this->routePathHasParameter($search_route, 'sort', 'query'), 'No "sort" query parameter found for GET route for endpoint "' . $endpoint . '"');

        // Search routes should specify the ResultFormatterMiddleware to allow optionally returning results as CSV or XML
        $this->test->assertTrue($this->routePathHasMiddleware($search_route, ResultFormatterMiddleware::class), 'ResultFormatterMiddleware not found on GET route for endpoint "' . $endpoint . '"');

        foreach ($dataset as $i => &$entry) {
            if (!isset($entry[$unique_field])) {
                $entry[$unique_field] = __FUNCTION__ . '_' . $i;
            }
            if (isset($response_schema['items']['properties']['entity']) && !isset($entry['entity'])) {
                $entry['entity'] = getItemByTypeName('Entity', '_test_root_entity', true);
            }
        }
        unset($entry);

        foreach ($dataset as $entry) {
            $request = new Request('POST', $endpoint);
            foreach ($entry as $key => $value) {
                $request->setParameter($key, $value);
            }
            $this->call($request, function ($call) {
                /** @var HLAPICallAsserter $call */
                $call->response
                    ->isOK();
            });
        }

        // Test default pagination
        $this->call(new Request('GET', $endpoint), function ($call) use ($endpoint, $dataset) {
            /** @var HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->headers(function ($headers) use ($endpoint, $dataset) {
                    $this->test->assertArrayHasKey('Content-Range', $headers);
                    $content_range = $headers['Content-Range'];
                    [$result_range, $total_count] = explode('/', $content_range);
                    $result_range = explode('-', $result_range);
                    $this->test->assertEquals(0, (int) $result_range[0], 'The Content-Range header for endpoint "' . $endpoint . '" does not start at 0 when no pagination parameters are specified (' . $content_range . ')');
                    $this->test->assertLessThanOrEqual($total_count, (int) $result_range[1], 'The Content-Range header for endpoint "' . $endpoint . '" does not have a valid range when no pagination parameters are specified (' . $content_range . ')');
                    $this->test->assertGreaterThanOrEqual(count($dataset), (int) $total_count, 'The Content-Range header for endpoint "' . $endpoint . '" does not have a valid total count when no pagination parameters are specified (' . $content_range . ')');
                });
        });

        // Test pagination
        $request = new Request('GET', $endpoint);
        $request->setParameter('start', 1);
        $request->setParameter('limit', 2);
        $this->call($request, function ($call) use ($endpoint, $dataset) {
            /** @var HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->headers(function ($headers) use ($endpoint, $dataset) {
                    $this->test->assertArrayHasKey('Content-Range', $headers);
                    $content_range = $headers['Content-Range'];
                    [$result_range, $total_count] = explode('/', $content_range);
                    $result_range = explode('-', $result_range);
                    $this->test->assertEquals(1, (int) $result_range[0], 'The Content-Range header for endpoint "' . $endpoint . '" does not start at the correct position (' . $content_range . ')');
                    $this->test->assertEquals(2, (int) $result_range[1], 'The Content-Range header for endpoint "' . $endpoint . '" does not have a valid range (' . $content_range . ')');
                    $this->test->assertGreaterThanOrEqual(count($dataset), (int) $total_count, 'The Content-Range header for endpoint "' . $endpoint . '" does not have a valid total count (' . $content_range . ')');
                });
        });

        // Test filtering
        $request = new Request('GET', $endpoint);
        $unique_prefix = explode('_', $dataset[0][$unique_field])[0];
        $request->setParameter('filter', $unique_field . '=like=' . $unique_prefix . '*');

        $this->call($request, function ($call) use ($unique_prefix, $unique_field, $endpoint, $dataset) {
            /** @var HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($unique_prefix, $unique_field, $endpoint, $dataset) {
                    $fail_msg = 'The response for the GET route path of endpoint "' . $endpoint . '" does not have the correct number of results when filtering by ' . $unique_field;
                    $fail_msg .= ' (filter: ' . $unique_field . '=like=' . $unique_prefix . '*)';
                    $fail_msg .= "\n" . var_export($content, true);
                    $this->test->assertCount(count($dataset), $content, $fail_msg);
                });
        });

        $request = new Request('GET', $endpoint);
        $request->setParameter('filter', $unique_field . '==' . $dataset[0][$unique_field]);
        $this->call($request, function ($call) use ($unique_field, $endpoint) {
            /** @var HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($unique_field, $endpoint) {
                    $fail_msg = 'The response for the GET route path of endpoint "' . $endpoint . '" does not have the correct number of results when filtering by ' . $unique_field;
                    $fail_msg .= ' (filter: ' . $unique_field . '==' . $content[0][$unique_field] . ')';
                    $fail_msg .= "\n" . var_export($content, true);
                    $this->test->assertCount(1, $content, $fail_msg);
                });
        });

        // Test sorting
        $sorted_dataset = $dataset;
        // Sort by the $unique_field DESC
        usort($sorted_dataset, static function ($a, $b) use ($unique_field) {
            return $b[$unique_field] <=> $a[$unique_field];
        });

        $request = new Request('GET', $endpoint);
        $request->setParameter('filter', $unique_field . '=in=(' . implode(',', array_column($dataset, $unique_field)) . ')');
        $sort = $unique_field . ':desc';
        $request->setParameter('sort', $sort);
        $this->call($request, function ($call) use ($sorted_dataset, $endpoint, $sort, $unique_field) {
            /** @var HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($sorted_dataset, $endpoint, $sort, $unique_field) {
                    $fail_msg = 'The response for the GET route path of endpoint "' . $endpoint . '" does not have the correct results when sorting with ' . $sort;
                    $fail_msg .= "\n" . var_export($content, true);
                    $this->test->assertCount(count($sorted_dataset), $content, $fail_msg);
                    // Compare the results with the sorted dataset
                    foreach ($sorted_dataset as $i => $entry) {
                        $this->test->assertEquals($entry[$unique_field], $content[$i][$unique_field], $fail_msg);
                    }
                });
        });

        $sort = $unique_field . ':asc';
        $request->setParameter('sort', $sort);
        // Re-sort the dataset to match the new sort order
        usort($sorted_dataset, static function ($a, $b) use ($unique_field) {
            return $a[$unique_field] <=> $b[$unique_field];
        });
        $this->call($request, function ($call) use ($sorted_dataset, $endpoint, $sort, $unique_field) {
            /** @var HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($sorted_dataset, $endpoint, $sort, $unique_field) {
                    $fail_msg = 'The response for the GET route path of endpoint "' . $endpoint . '" does not have the correct results when sorting with ' . $sort;
                    $fail_msg .= "\n" . var_export($content, true);
                    $this->test->assertCount(count($sorted_dataset), $content, $fail_msg);
                    // Compare the results with the sorted dataset
                    foreach ($sorted_dataset as $i => $entry) {
                        $this->test->assertEquals($entry[$unique_field], $content[$i][$unique_field], $fail_msg);
                    }
                });
        });

        return $this;
    }

    /**
     * @param string $endpoint
     * @param class-string<AssignableItemInterface> $itemtype
     * @return void
     */
    public function autoTestAssignableItemRights(string $endpoint, string $itemtype)
    {
        $this->test->loginWeb();
        $this->getRouter()->registerAuthMiddleware(new InternalAuthMiddleware());
        $_SESSION['glpigroups'] = [99];

        $create_request = new Request('POST', $endpoint);
        $create_request->setParameter('name', 'autoTestAssignableItem' . random_int(0, 10000));
        $create_request->setParameter('entity', getItemByTypeName('Entity', '_test_root_entity', true));
        $new_location = null;
        $new_items_id = null;
        $this->call($create_request, function ($call) use (&$new_location, &$new_items_id) {
            $call->response
                ->isOK()
                ->headers(function ($headers) use (&$new_location) {
                    $new_location = $headers['Location'];
                })
                ->jsonContent(function ($content) use (&$new_items_id) {
                    $new_items_id = $content['id'];
                });
        }, false);

        global $DB;

        $_SESSION['glpiactiveprofile'][$itemtype::$rightname] = READ_OWNED;
        $this->call(new Request('GET', $new_location), function ($call) {
            $call->response->isNotFoundError();
        }, false);

        // Test READ_OWNED when set as the User
        $DB->update(
            $itemtype::getTable(),
            ['users_id' => $_SESSION['glpiID']],
            ['id' => $new_items_id]
        );
        $this->call(new Request('GET', $new_location), function ($call) {
            $call->response->isOK();
        }, false);
        // Test READ_OWNED when User is set, but not to the current user
        $DB->update(
            $itemtype::getTable(),
            ['users_id' => $_SESSION['glpiID'] + 1],
            ['id' => $new_items_id]
        );
        $this->call(new Request('GET', $new_location), function ($call) {
            $call->response->isNotFoundError();
        }, false);
        // Test READ_OWNED when the Group is set to one of the current user's groups
        $DB->insert('glpi_groups_items', [
            'itemtype' => $itemtype,
            'items_id' => $new_items_id,
            'groups_id' => 99,
            'type' => Group_Item::GROUP_TYPE_NORMAL,
        ]);
        $this->call(new Request('GET', $new_location), function ($call) {
            $call->response->isOK();
        }, false);

        $DB->delete(
            'glpi_groups_items',
            [
                'itemtype' => $itemtype,
                'items_id' => $new_items_id,
                'groups_id' => 99,
            ]
        );
        $DB->update(
            $itemtype::getTable(),
            ['users_id_tech' => $_SESSION['glpiID']],
            ['id' => $new_items_id]
        );
        // Test READ_OWNED when the User is set as a technician instead of regular user
        $this->call(new Request('GET', $new_location), function ($call) {
            $call->response->isNotFoundError();
        }, false);
        // Test READ_ASSIGNED when the User is set as a technician
        $_SESSION['glpiactiveprofile'][$itemtype::$rightname] = READ_ASSIGNED;
        $this->call(new Request('GET', $new_location), function ($call) {
            $call->response->isOK();
        }, false);
        // Test READ_ASSIGNED when a Technician user is set, but not to the current user
        $DB->update(
            $itemtype::getTable(),
            ['users_id_tech' => $_SESSION['glpiID'] + 1],
            ['id' => $new_items_id]
        );
        $this->call(new Request('GET', $new_location), function ($call) {
            $call->response->isNotFoundError();
        }, false);
        // Test READ_ASSIGNED when the technician Group is set to one of the current user's groups
        $DB->insert('glpi_groups_items', [
            'itemtype' => $itemtype,
            'items_id' => $new_items_id,
            'groups_id' => 99,
            'type' => Group_Item::GROUP_TYPE_TECH,
        ]);
        $this->call(new Request('GET', $new_location), function ($call) {
            $call->response->isOK();
        }, false);
    }
}

// @codingStandardsIgnoreStart
/**
 * @property HLAPIRequestAsserter $originalRequest
 * @property HLAPIRequestAsserter $finalRequest
 * @property HLAPIResponseAsserter $response
 * @property HLAPIRouteAsserter $route
 */
final class HLAPICallAsserter
{
    // @codingStandardsIgnoreEnd
    public function __construct(
        public HLAPITestCase $test,
        private Router $router,
        private Response $response
    ) {}

    public function __get(string $name)
    {
        return match ($name) {
            'originalRequest' => new HLAPIRequestAsserter($this, $this->router->getOriginalRequest()),
            'finalRequest' => new HLAPIRequestAsserter($this, $this->router->getFinalRequest()),
            'response' => new HLAPIResponseAsserter($this, $this->response, $this->router->getOriginalRequest()->getHeaderLine('GLPI-API-Version') ?: $this->router::API_VERSION),
            'route' => new HLAPIRouteAsserter($this, $this->router->getLastInvokedRoute()),
            default => null,
        };
    }
}

// @codingStandardsIgnoreStart
/**
 * @property $method
 * @property $uri
 * @property $headers
 * @property $body
 */
final class HLAPIRequestAsserter
{
    // @codingStandardsIgnoreEnd
    public function __construct(
        private HLAPICallAsserter $call_asserter,
        private Request $request
    ) {}

    public function method(callable $fn): self
    {
        $fn($this->request->getMethod());
        return $this;
    }

    public function uri(callable $fn): self
    {
        $fn($this->request->getUri());
        return $this;
    }

    public function getUri(): string
    {
        return $this->request->getUri();
    }

    public function getMethod(): string
    {
        return $this->request->getMethod();
    }

    public function headers(callable $fn): self
    {
        $fn($this->request->getHeaders());
        return $this;
    }

    public function body(callable $fn): self
    {
        $fn($this->request->getBody());
        return $this;
    }
}

// @codingStandardsIgnoreStart
final class HLAPIResponseAsserter
{
    // @codingStandardsIgnoreEnd
    public function __construct(
        private HLAPICallAsserter $call_asserter,
        private Response $response,
        private string $api_version,
    ) {}

    public function status(callable $fn): self
    {
        $fn($this->response->getStatusCode());
        return $this;
    }

    public function headers(callable $fn): self
    {
        $headers = $this->response->getHeaders();
        $headers = array_map(static function ($header) {
            if (is_array($header) && count($header) === 1) {
                return $header[0];
            }
            return $header;
        }, $headers);
        $fn($headers);
        return $this;
    }

    /**
     * @param callable $fn
     * @phpstan-param callable(string): void $fn
     * @return $this
     */
    public function content(callable $fn): self
    {
        $fn((string) $this->response->getBody());
        return $this;
    }

    /**
     * @param callable $fn
     * @phpstan-param callable(array): void $fn
     * @return $this
     */
    public function jsonContent(callable $fn): self
    {
        $fn(json_decode((string) $this->response->getBody(), true));
        return $this;
    }

    public function isOK(): HLAPIResponseAsserter
    {
        $original_request = $this->call_asserter->originalRequest;
        $fail_msg = 'Status code for call to ' . $original_request->getMethod() . ' ' . $original_request->getUri() . ' is not 2xx';
        $status_code = $this->response->getStatusCode();
        if ($status_code < 200 || $status_code >= 300) {
            $response_content = json_decode((string) $this->response->getBody(), true);
            $fail_msg .= " ($status_code):\n" . var_export($response_content, true);
            // Status is 200 - 299
            $this->call_asserter->test->assertGreaterThanOrEqual(200, $status_code, $fail_msg);
            $this->call_asserter->test->assertLessThan(300, $status_code, $fail_msg);
        }
        return $this;
    }

    public function isUnauthorizedError(): HLAPIResponseAsserter
    {
        $original_request = $this->call_asserter->originalRequest;
        $method = $original_request->getMethod();
        $uri = $this->call_asserter->originalRequest->getUri();
        // Status is 401
        $this->call_asserter->test
            ->assertEquals(401, $this->response->getStatusCode(), 'Status code for call to ' . $method . ' ' . $uri . ' is not 401');
        $decoded_content = json_decode((string) $this->response->getBody(), true);
        $this->call_asserter->test->assertCount(3, array_intersect(['title', 'detail', 'status'], array_keys($decoded_content)), 'Response from ' . $uri . ' is not a valid error response');
        $this->call_asserter->test->assertEquals('ERROR_UNAUTHENTICATED', $decoded_content['status'], 'Status property in response from ' . $uri . ' is not ERROR_UNAUTHENTICATED');
        return $this;
    }

    public function isAccessDenied(): HLAPIResponseAsserter
    {
        $uri = $this->call_asserter->originalRequest->getUri();
        $method = $this->call_asserter->originalRequest->getMethod();
        // Status is 403
        $this->call_asserter->test
            ->assertEquals(403, $this->response->getStatusCode(), 'Status code for call to ' . $method . ' ' . $uri . ' is not 403');
        $decoded_content = json_decode((string) $this->response->getBody(), true);
        $this->call_asserter->test->assertCount(3, array_intersect(['title', 'detail', 'status'], array_keys($decoded_content)), 'Response from ' . $uri . ' is not a valid error response');
        $this->call_asserter->test->assertEquals('ERROR_RIGHT_MISSING', $decoded_content['status'], 'Status property in response from ' . $uri . ' is not ERROR_RIGHT_MISSING');
        return $this;
    }

    public function isNotFoundError(): HLAPIResponseAsserter
    {
        $uri = $this->call_asserter->originalRequest->getUri();
        $method = $this->call_asserter->originalRequest->getMethod();
        // Status is 404
        $this->call_asserter->test
            ->assertEquals(404, $this->response->getStatusCode(), 'Status code for call to ' . $method . ' ' . $uri . ' is not 404');
        $decoded_content = json_decode((string) $this->response->getBody(), true);
        $this->call_asserter->test->assertCount(3, array_intersect(['title', 'detail', 'status'], array_keys($decoded_content)), 'Response from ' . $uri . ' is not a valid error response');
        $this->call_asserter->test->assertEquals('ERROR_ITEM_NOT_FOUND', $decoded_content['status'], 'Status property in response from ' . $uri . ' is not ERROR_ITEM_NOT_FOUND');
        return $this;
    }

    public function isNotOK(): HLAPIResponseAsserter
    {
        $uri = $this->call_asserter->originalRequest->getUri();
        $method = $this->call_asserter->originalRequest->getMethod();
        $fail_msg = 'Status code for call to ' . $method . ' ' . $uri . ' is not a non-OK status';
        $status_code = $this->response->getStatusCode();
        if ($status_code < 300) {
            $response_content = json_decode((string) $this->response->getBody(), true);
            $fail_msg .= " ($status_code):\n" . var_export($response_content, true);
            $this->call_asserter->test->assertGreaterThanOrEqual(300, $status_code, $fail_msg);
        }
        return $this;
    }

    public function matchesSchema(string|array $schema, ?string $fail_msg = null, ?string $operation = null): HLAPIResponseAsserter
    {
        if (is_string($schema)) {
            $is_schema_array = str_ends_with($schema, '[]');
            if ($is_schema_array) {
                $schema = substr($schema, 0, -2);
            }
            $matched_route = $this->call_asserter->route->get();
            /** @var class-string<AbstractController> $controller */
            $controller = $matched_route->getController();
            $schema = $controller::getKnownSchemas($this->api_version)[$schema];
        } else {
            $is_schema_array = $schema['type'] === Doc\Schema::TYPE_ARRAY;
        }
        $content = json_decode((string) $this->response->getBody(), true);
        $items = $is_schema_array ? $content : [$content];

        foreach ($items as $item) {
            // Verify the JSON content matches the OpenAPI schema
            $matches = Doc\Schema::fromArray($schema)->isValid($item, $operation);
            if (!$matches) {
                $fail_msg ??= 'Response content does not match the schema';
                $fail_msg .= ":\n" . var_export($item, true);
                $fail_msg .= "\n\nSchema:\n" . var_export($schema, true);
                $this->call_asserter->test->assertTrue($matches, $fail_msg);
            }
        }
        return $this;
    }
}

// @codingStandardsIgnoreStart
final class HLAPIRouteAsserter
{
    // @codingStandardsIgnoreEnd
    public function __construct(
        private HLAPICallAsserter $call_asserter,
        private RoutePath $routePath
    ) {}

    public function path(callable $fn): self
    {
        $fn($this->routePath->getRoutePath());
        return $this;
    }

    public function compiledPath(callable $fn): self
    {
        $fn($this->routePath->getCompiledPath());
        return $this;
    }

    public function isAuthRequired(): self
    {
        $this->call_asserter->test
            ->assertTrue($this->routePath->getRouteSecurityLevel() !== Route::SECURITY_NONE, 'Route does not require authentication');
        return $this;
    }

    public function isAnonymousAllowed(): self
    {
        $this->call_asserter->test
            ->assertTrue($this->routePath->getRouteSecurityLevel() === Route::SECURITY_NONE, 'Route does not allow anonymous access');
        return $this;
    }

    public function get(): RoutePath
    {
        return $this->routePath;
    }
}
