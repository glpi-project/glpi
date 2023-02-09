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

namespace Glpi\Api\HL;

use Glpi\Api\HL\Controller\AbstractController;
use Glpi\Api\HL\Controller\AdministrationController;
use Glpi\Api\HL\Controller\AssetController;
use Glpi\Api\HL\Controller\CoreController;
use Glpi\Api\HL\Controller\CRUDControllerTrait;
use Glpi\Api\HL\Controller\ITILController;
use Glpi\Api\HL\Controller\ManagementController;
use Glpi\Api\HL\Middleware\AbstractMiddleware;
use Glpi\Api\HL\Middleware\CRUDRequestMiddleware;
use Glpi\Api\HL\Middleware\DebugRequestMiddleware;
use Glpi\Api\HL\Middleware\DebugResponseMiddleware;
use Glpi\Api\HL\Middleware\MiddlewareInput;
use Glpi\Api\HL\Middleware\RequestMiddlewareInterface;
use Glpi\Api\HL\Middleware\ResponseMiddlewareInterface;
use Glpi\Api\HL\Middleware\RSQLRequestMiddleware;
use Glpi\Api\HL\Middleware\SecurityResponseMiddleware;
use Glpi\Http\JSONResponse;
use Glpi\Http\Request;
use Glpi\Http\Response;
use Glpi\Plugin\Hooks;
use GuzzleHttp\Psr7\Utils;
use Session;

class Router
{
    /** @var string */
    public const API_VERSION = '2.0.0';

    /**
     * @var AbstractController[]
     */
    protected array $controllers = [];

    /**
     * @var array{middleware: RequestMiddlewareInterface, priority: integer, condition: callable}[]
     */
    protected array $request_middlewares = [];

    /**
     * @var array{middleware: ResponseMiddlewareInterface, priority: integer, condition: callable}[]
     */
    protected array $response_middlewares = [];

    /**
     * The request as it was received by the router (and after some very basic processing).
     * @var ?Request
     * @interal Only intended to be used by tests
     */
    private ?Request $original_request;

    /**
     * The final state of the request after it was modified by the request middlewares.
     * @var ?Request
     * @interal Only intended to be used by tests
     */
    private ?Request $final_request;

    /**
     * The last route that was matched and invoked.
     * @var ?RoutePath
     * @interal Only intended to be used by tests
     */
    private ?RoutePath $last_invoked_route = null;

    /**
     * Get information about all API versions available.
     * @return array
     */
    public static function getAPIVersions(): array
    {
        global $CFG_GLPI;

        $low_level_api_description = <<<EOT
The low-level API which is closely tied to the GLPI source code.
While not as user friendly as the high-level API, it is more powerful and allows to do some things that are not possible with the high-level API.
It has no promise of stability between versions so it may change without warning.
EOT;
        $current_version = self::API_VERSION;
        // Get short version which is the major part of the semver string
        $current_version_major = explode('.', $current_version)[0];

        return [
            [
                'api_version' => '1',
                'version'    => '1.0.0',
                'description' => str_replace(PHP_EOL, ' ', $low_level_api_description),
                'endpoint'   => $CFG_GLPI['url_base_api'],
            ],
            [
                'api_version' => $current_version_major,
                'version' => self::API_VERSION,
                'endpoint' => $CFG_GLPI['url_base'] . '/api.php/v2',
            ],
        ];
    }

    /**
     * Get the singleton instance of the router
     *
     * @return Router
     */
    public static function getInstance(): Router
    {
        global $PLUGIN_HOOKS;

        static $instance;
        if (!$instance) {
            $instance = new self();
            $instance->registerController(new CoreController());
            $instance->registerController(new AssetController());
            $instance->registerController(new ITILController());
            $instance->registerController(new AdministrationController());
            $instance->registerController(new ManagementController());

            // Register controllers from plugins
            if (isset($PLUGIN_HOOKS[Hooks::API_CONTROLLERS])) {
                foreach ($PLUGIN_HOOKS[Hooks::API_CONTROLLERS] as $controllers) {
                    if (!is_array($controllers)) {
                        continue;
                    }
                    foreach ($controllers as $controller) {
                        if (is_subclass_of($controller, AbstractController::class, true)) {
                            $instance->registerController(new $controller());
                        }
                    }
                }
            }

            $instance->registerRequestMiddleware(new CRUDRequestMiddleware(), 0, static function (AbstractController $controller) {
                return \Toolbox::hasTrait($controller, CRUDControllerTrait::class);
            });
            $instance->registerRequestMiddleware(new DebugRequestMiddleware());
            $instance->registerRequestMiddleware(new RSQLRequestMiddleware());

            // Always run the security middleware (no condition set)
            $instance->registerResponseMiddleware(new SecurityResponseMiddleware());
            $instance->registerResponseMiddleware(new DebugResponseMiddleware(), PHP_INT_MAX);

            // Register middleware from plugins
            if (isset($PLUGIN_HOOKS[Hooks::API_MIDDLEWARE])) {
                foreach ($PLUGIN_HOOKS[Hooks::API_MIDDLEWARE] as $middlewares) {
                    if (!is_array($middlewares)) {
                        continue;
                    }
                    foreach ($middlewares as $middleware_info) {
                        if (
                            !isset($middleware_info['middleware']) ||
                            !is_subclass_of($middleware_info['middleware'], AbstractMiddleware::class, true)
                        ) {
                            continue;
                        }
                        $middleware = new $middleware_info['middleware']();
                        if (class_implements($middleware, RequestMiddlewareInterface::class)) {
                            $instance->registerRequestMiddleware(new $middleware(), $middleware_info['priority'] ?? 0, $middleware_info['condition'] ?? null);
                        }
                        if (class_implements($middleware, ResponseMiddlewareInterface::class)) {
                            $instance->registerResponseMiddleware(new $middleware(), $middleware_info['priority'] ?? 0, $middleware_info['condition'] ?? null);
                        }
                    }
                }
            }
        }
        return $instance;
    }

    /**
     * @return Request|null
     * @internal Only intended to be used by tests
     */
    public function getOriginalRequest(): ?Request
    {
        return $this->original_request;
    }

    /**
     * @return Request|null
     * @internal Only intended to be used by tests
     */
    public function getFinalRequest(): ?Request
    {
        return $this->final_request;
    }

    /**
     * @return RoutePath|null
     * @internal Only intended to be used by tests
     */
    public function getLastInvokedRoute(): ?RoutePath
    {
        return $this->last_invoked_route;
    }

    /**
     * Register a route controller
     * @param AbstractController $controller
     * @return void
     */
    public function registerController(AbstractController $controller): void
    {
        $this->controllers[] = $controller;
    }

    /**
     * Register a request middleware
     *
     * @param RequestMiddlewareInterface $middleware
     * @param int $priority
     * @param callable|null $condition
     * @phpstan-param null|callable(AbstractController): bool $condition
     * @return void
     */
    public function registerRequestMiddleware(RequestMiddlewareInterface $middleware, int $priority = 0, ?callable $condition = null): void
    {
        $this->request_middlewares[] = [
            'priority' => $priority,
            'middleware' => $middleware,
            'condition' => $condition ?? static fn(AbstractController $controller) => true,
        ];
        // Sort by priority (Higher priority last due to how the processing is done)
        usort($this->request_middlewares, static function ($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });
    }

    /**
     * Register a response middleware
     *
     * @param ResponseMiddlewareInterface $middleware
     * @param int $priority
     * @param callable|null $condition
     * @phpstan-param null|callable(AbstractController): bool $condition
     * @return void
     */
    public function registerResponseMiddleware(ResponseMiddlewareInterface $middleware, int $priority = 0, ?callable $condition = null): void
    {
        $this->response_middlewares[] = [
            'priority' => $priority,
            'middleware' => $middleware,
            'condition' => $condition ?? static fn(AbstractController $controller) => true,
        ];
        // Sort by priority (Higher priority last due to how the processing is done)
        usort($this->response_middlewares, static function ($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });
    }

    /**
     * @param RoutePath[] $routes
     * @return void
     */
    private function cacheRoutes(array $routes): void
    {
        global $GLPI_CACHE;

        $hints = [];
        foreach ($routes as $route) {
            $hints[] = $route->getCachedRouteHint();
        }
        $GLPI_CACHE->set('hlapi_routes', $hints, 5 * MINUTE_TIMESTAMP);
    }

    /**
     * @return RoutePath[]
     */
    private function getRoutesFromCache(): array
    {
        global $GLPI_CACHE;

        $routes = [];
        $hints = $GLPI_CACHE->get('hlapi_routes') ?? [];
        if (empty($hints)) {
            foreach ($this->controllers as $controller) {
                $rc = new \ReflectionClass($controller);
                $methods = $rc->getMethods();

                foreach ($methods as $method) {
                    $attributes = $method->getAttributes(Route::class);
                    if (count($attributes) && $method->isPublic()) {
                        /** @var Route $route_attr */
                        $route_attr = $attributes[0]->newInstance();

                        $routes[] = RoutePath::fromRouteAttribute($route_attr, get_class($controller), $method->getName());
                    }
                }
            }
            $this->cacheRoutes($routes);
            $hints = $GLPI_CACHE->get('hlapi_routes') ?? [];
        }

        foreach ($hints as $hint) {
            [$controller, $method] = explode('::', $hint['key']);
            $routes[] = new RoutePath(
                class: $controller,
                method: $method,
                path: $hint['path'],
                methods: $hint['methods'],
                priority: $hint['priority'],
                security: $hint['security'],
                compiled_path: $hint['compiled_path'],
            );
        }

        return $routes;
    }

    /**
     * @return RoutePath[]
     */
    public function getAllRoutes(): array
    {
        static $routes = null;

        if ($routes === null) {
            $routes = $this->getRoutesFromCache();
        }
        return $routes ?? [];
    }

    /**
     * @param bool $force_all If true, all paths will be returned even if they require authentication and the user is not logged in.
     * @return array{href: string, methods: array<string>, requirements: array<string, string>}[]
     */
    public function getAllRoutePaths(bool $force_all = false): array
    {
        $routes = $this->getAllRoutes();
        $paths = [];
        $is_user_authenticated = isset($_SESSION['glpiID']);
        foreach ($routes as $route) {
            if (!$force_all && !$is_user_authenticated && $route->getRouteSecurityLevel() !== Route::SECURITY_NONE) {
                continue;
            }
            $paths[] = [
                'href' => $route->getRoutePath(),
                'methods' => $route->getRouteMethods(),
                'requirements' => $route->getRouteRequirements(),
            ];
        }
        // Sort by href
        usort($paths, static function ($a, $b) {
            return strcmp($a['href'], $b['href']);
        });
        return $paths;
    }

    /**
     * @param Request $request
     * @return ?RoutePath
     */
    public function match(Request $request): ?RoutePath
    {
        /** @var RoutePath[] $routes */
        $routes = $this->getRoutesFromCache();
        $routes = array_filter($routes, static function ($route) use ($request) {
            if (in_array($request->getMethod(), $route->getRouteMethods(), true)) {
                // Verify the request uri path matches the compiled path
                return (bool) preg_match('#^' . $route->getCompiledPath() . '$#i', $request->getUri()->getPath());
            }
            return false;
        });

        foreach ($routes as $route) {
            $path = $route->getRoutePath();
            $request_path = $request->getUri()->getPath();

            // Extract the path parameter names from the route path and match them with the values from the request path
            $path_params = [];
            $path_fragments = explode('/', $path);
            foreach ($path_fragments as $i => $fragment) {
                $matches = [];
                if (preg_match('/\{([^}]+)\}/', $fragment, $matches)) {
                    $path_params[$i] = $matches[1];
                }
            }
            $request_fragments = explode('/', $request_path);
            foreach ($path_params as $i => $param) {
                if (isset($request_fragments[$i]) && !$request->hasAttribute($param)) {
                    $request->setAttribute($param, $request_fragments[$i]);
                }
            }
        }

        // Sort routes by priority (descending)
        usort($routes, static function (RoutePath $a, RoutePath $b) {
            return ($a->getRoutePriority() < $b->getRoutePriority()) ? -1 : 1;
        });

        $routes = array_reverse($routes);
        if (count($routes)) {
            return reset($routes);
        }
        return null;
    }

    private function doRequestMiddleware(MiddlewareInput $input): ?Response
    {
        $action = static function (MiddlewareInput $input, ?callable $next = null) {
            return null;
        };
        foreach ($this->request_middlewares as $middleware) {
            $conditions_met = $middleware['condition']($input->route_path->getControllerInstance());
            if (!$conditions_met) {
                continue;
            }
            $action = static fn ($input) => $middleware['middleware']($input, $action);
        }
        return $action($input);
    }

    private function doResponseMiddleware(MiddlewareInput $input): void
    {
        $action = static function (MiddlewareInput $input, ?callable $next = null) {
        };
        foreach ($this->response_middlewares as $middleware) {
            $conditions_met = $middleware['condition']($input->route_path->getControllerInstance());
            if (!$conditions_met) {
                continue;
            }
            $action = static fn ($input) => $middleware['middleware']($input, $action);
        }
        $action($input);
    }

    public function handleRequest(Request $request): Response
    {
        // Start an output buffer to capture any potential debug errors
        ob_start();
        $response = null;
        $original_method = $request->getMethod();
        if ($original_method === 'HEAD') {
            $request = $request->withMethod('GET');
        }

        // Fill parameters from $_REQUEST
        foreach ($_REQUEST as $key => $value) {
            $request->setParameter($key, $value);
        }

        // Handle potential JSON request body
        $content_types = $request->getHeader('Content-Type');
        if (in_array('application/json', $content_types, true)) {
            $body = $request->getBody()->getContents();
            try {
                $body = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($body)) {
                    foreach ($body as $key => $value) {
                        $request->setParameter((string) $key, $value);
                    }
                } else {
                    throw new \RuntimeException();
                }
            } catch (\Exception) {
                $response = new JSONResponse(
                    AbstractController::getErrorResponseBody(AbstractController::ERROR_GENERIC, _x('api', 'Invalid JSON body')),
                    400
                );
            }
        }

        $this->resumeSession($request);

        $this->original_request = clone $request;
        $matched_route = $this->match($request);

        if ($matched_route === null) {
            $response = new Response(404);
        } else {
            $requires_auth = $matched_route->getRouteSecurityLevel() === Route::SECURITY_AUTHENTICATED;
            if ($requires_auth) {
                $unauthenticated_response = new JSONResponse([
                    'title' => _x('api', 'You are not authenticated'),
                    'detail' => _x('api', 'The Glpi-Session-Token header is missing or invalid'),
                    'status' => 'ERROR_UNAUTHENTICATED',
                ], 401);
                if (!$request->hasHeader('Glpi-Session-Token')) {
                    $response =  $unauthenticated_response;
                } else {
                    $current_session_id = session_id();
                    $session_token = $request->getHeaderLine('Glpi-Session-Token');
                    if (($current_session_id !== $session_token && !empty($current_session_id)) || !isset($_SESSION['glpiID'])) {
                        $response = $unauthenticated_response;
                    }
                }
            }

            if ($response === null) {
                $middleware_input = new MiddlewareInput($request, $matched_route, null);
                $this->doRequestMiddleware($middleware_input);
                $response = $middleware_input->response;
                $request = $middleware_input->request;
                $this->final_request = clone $request;
                if ($response === null) {
                    $this->last_invoked_route = $matched_route;
                    $response = $matched_route->invoke($request);
                    $middleware_input = new MiddlewareInput($request, $matched_route, $response);
                    $this->doResponseMiddleware($middleware_input);
                    $response = $middleware_input->response;
                }
            }
        }

        if ($original_method === 'HEAD') {
            $response = $response->withBody(Utils::streamFor(''));
        }
        // Clear output buffers
        $ob_config = ini_get('output_buffering');
        $max_level = filter_var($ob_config, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        while (ob_get_level() > $max_level) {
            ob_end_clean();
        }
        if (ob_get_level() > 0) {
            ob_clean();
        }
        return $response;
    }

    /**
     * Try resuming the session from the Glpi-Session-Token header
     * @param Request $request
     * @return void
     */
    private function resumeSession(Request $request): void
    {
        if (
            $request->hasHeader('Glpi-Session-Token')
            && !empty($request->getHeaderLine('Glpi-Session-Token'))
        ) {
            $current = session_id();
            $session = trim($request->getHeaderLine('Glpi-Session-Token'));

            if ($session != $current && !empty($current)) {
                session_destroy();
            }
            if ($session != $current && !empty($session)) {
                session_id($session);
            }
        }
        Session::setPath();
        Session::start();

        // Clear all messages in the session to avoid unhandled messages being displayed in the errors of unrelated API requests
        $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];
    }

    /**
     * Get all registered controllers
     * @return AbstractController[]
     */
    public function getControllers(): array
    {
        return $this->controllers;
    }
}
