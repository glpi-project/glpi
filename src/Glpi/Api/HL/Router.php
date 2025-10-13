<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use Auth;
use Config;
use DropdownTranslation;
use Glpi\Api\HL\Controller\AbstractController;
use Glpi\Api\HL\Controller\AdministrationController;
use Glpi\Api\HL\Controller\AssetController;
use Glpi\Api\HL\Controller\ComponentController;
use Glpi\Api\HL\Controller\CoreController;
use Glpi\Api\HL\Controller\CRUDControllerTrait;
use Glpi\Api\HL\Controller\CustomAssetController;
use Glpi\Api\HL\Controller\DropdownController;
use Glpi\Api\HL\Controller\GraphQLController;
use Glpi\Api\HL\Controller\ITILController;
use Glpi\Api\HL\Controller\ManagementController;
use Glpi\Api\HL\Controller\ProjectController;
use Glpi\Api\HL\Controller\ReportController;
use Glpi\Api\HL\Controller\RuleController;
use Glpi\Api\HL\Controller\SetupController;
use Glpi\Api\HL\Middleware\AbstractMiddleware;
use Glpi\Api\HL\Middleware\AuthMiddlewareInterface;
use Glpi\Api\HL\Middleware\CookieAuthMiddleware;
use Glpi\Api\HL\Middleware\CRUDRequestMiddleware;
use Glpi\Api\HL\Middleware\DebugRequestMiddleware;
use Glpi\Api\HL\Middleware\DebugResponseMiddleware;
use Glpi\Api\HL\Middleware\InternalAuthMiddleware;
use Glpi\Api\HL\Middleware\IPRestrictionRequestMiddleware;
use Glpi\Api\HL\Middleware\MiddlewareInput;
use Glpi\Api\HL\Middleware\OAuthRequestMiddleware;
use Glpi\Api\HL\Middleware\RequestMiddlewareInterface;
use Glpi\Api\HL\Middleware\ResponseMiddlewareInterface;
use Glpi\Api\HL\Middleware\ResultFormatterMiddleware;
use Glpi\Api\HL\Middleware\RSQLRequestMiddleware;
use Glpi\Api\HL\Middleware\SecurityResponseMiddleware;
use Glpi\Http\JSONResponse;
use Glpi\Http\Request;
use Glpi\Http\Response;
use Glpi\OAuth\Server;
use Glpi\Plugin\Hooks;
use GuzzleHttp\Psr7\Utils;
use League\OAuth2\Server\Exception\OAuthServerException;
use ReflectionClass;
use RuntimeException;
use Session;
use Throwable;
use Toolbox;
use User;

use function Safe\ob_end_clean;
use function Safe\ob_start;
use function Safe\preg_match;

class Router
{
    /** @var string */
    public const API_VERSION = '2.0.0';

    /**
     * @var AbstractController[]
     */
    protected array $controllers = [];

    /**
     * @var array{middleware: AuthMiddlewareInterface, priority: integer, condition: callable}[]
     */
    protected array $auth_middlewares = [];

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
     * @internal Only intended to be used by tests
     */
    private ?Request $original_request = null;

    /**
     * The final state of the request after it was modified by the request middlewares.
     * @var ?Request
     * @internal Only intended to be used by tests
     */
    private ?Request $final_request = null;

    /**
     * The last route that was matched and invoked.
     * @var ?RoutePath
     * @internal Only intended to be used by tests
     */
    private ?RoutePath $last_invoked_route = null;

    /**
     * @var array{client_id: string, user_id: string, scopes: array}|null The current client information if the user is authenticated.
     */
    private ?array $current_client = null;

    private static ?self $instance = null;

    /**
     * Get information about all API versions available.
     * @return array{api_version: string, version: string, description?: string, endpoint: string}[]
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
                'endpoint'   => $CFG_GLPI['url_base'] . '/api.php/v1',
            ],
            [
                'api_version' => $current_version_major,
                'version' => self::API_VERSION,
                'endpoint' => $CFG_GLPI['url_base'] . '/api.php/v2',
            ],
        ];
    }

    /**
     * Normalize the requested API version based on the available versions.
     *
     * If only a major version is specified, the latest minor version will be used.
     * If a major and minor version is specified, the latest patch version will be used.
     * If a complete version is specified, it will be used as is.
     * If no version is specified, the latest version will be used.
     * @param string $version
     * @return string
     */
    public static function normalizeAPIVersion(string $version): string
    {
        $versions = array_column(static::getAPIVersions(), 'version');
        $best_match = self::API_VERSION;
        if (in_array($version, $versions, true)) {
            // Exact match
            return $version;
        }

        foreach ($versions as $available_version) {
            if (str_starts_with($available_version, $version . '.') && version_compare($available_version, $best_match, '>')) {
                $best_match = $available_version;
            }
        }
        return $best_match;
    }

    /**
     * Unsets the instance so it can be recreated the next time {@link getInstance()} is called.
     * @return void
     */
    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    /**
     * Get the singleton instance of the router
     *
     * @return Router
     */
    public static function getInstance(): Router
    {
        global $PLUGIN_HOOKS;

        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->registerController(new CoreController());
            self::$instance->registerController(new AssetController());
            self::$instance->registerController(new CustomAssetController());
            self::$instance->registerController(new ComponentController());
            self::$instance->registerController(new ITILController());
            self::$instance->registerController(new AdministrationController());
            self::$instance->registerController(new ManagementController());
            self::$instance->registerController(new ProjectController());
            self::$instance->registerController(new DropdownController());
            self::$instance->registerController(new GraphQLController());
            self::$instance->registerController(new ReportController());
            self::$instance->registerController(new RuleController());
            self::$instance->registerController(new SetupController());

            // Register controllers from plugins
            if (isset($PLUGIN_HOOKS[Hooks::API_CONTROLLERS])) {
                foreach ($PLUGIN_HOOKS[Hooks::API_CONTROLLERS] as $controllers) {
                    if (!is_array($controllers)) {
                        continue;
                    }
                    foreach ($controllers as $controller) {
                        if (is_subclass_of($controller, AbstractController::class, true)) {
                            self::$instance->registerController(new $controller());
                        }
                    }
                }
            }

            // Cookie middleware shouldn't run by default. Must be explicitly enabled by adding it in a Route attribute.
            self::$instance->registerAuthMiddleware(new CookieAuthMiddleware(), 0, static fn(RoutePath $route_path) => false);

            self::$instance->registerRequestMiddleware(new IPRestrictionRequestMiddleware());
            self::$instance->registerRequestMiddleware(new OAuthRequestMiddleware());
            self::$instance->registerRequestMiddleware(new CRUDRequestMiddleware(), 0, static fn(RoutePath $route_path) => Toolbox::hasTrait($route_path->getControllerInstance(), CRUDControllerTrait::class));
            self::$instance->registerRequestMiddleware(new DebugRequestMiddleware());
            self::$instance->registerRequestMiddleware(new RSQLRequestMiddleware());

            // Always run the security middleware (no condition set)
            self::$instance->registerResponseMiddleware(new SecurityResponseMiddleware());
            self::$instance->registerResponseMiddleware(new DebugResponseMiddleware(), PHP_INT_MAX);
            self::$instance->registerResponseMiddleware(new ResultFormatterMiddleware(), 0, static fn(RoutePath $route_path) => false);

            // Register middleware from plugins
            if (isset($PLUGIN_HOOKS[Hooks::API_MIDDLEWARE])) {
                foreach ($PLUGIN_HOOKS[Hooks::API_MIDDLEWARE] as $middlewares) {
                    if (!is_array($middlewares)) {
                        continue;
                    }
                    foreach ($middlewares as $middleware_info) {
                        if (
                            !isset($middleware_info['middleware'])
                            || !is_subclass_of($middleware_info['middleware'], AbstractMiddleware::class, true)
                        ) {
                            continue;
                        }
                        $middleware = new $middleware_info['middleware']();
                        if ($middleware instanceof RequestMiddlewareInterface) {
                            self::$instance->registerRequestMiddleware(new $middleware(), $middleware_info['priority'] ?? 0, $middleware_info['condition'] ?? null);
                        }
                        if ($middleware instanceof ResponseMiddlewareInterface) {
                            self::$instance->registerResponseMiddleware(new $middleware(), $middleware_info['priority'] ?? 0, $middleware_info['condition'] ?? null);
                        }
                    }
                }
            }
        }
        return self::$instance;
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
     * Register an auth middleware
     *
     * @param AuthMiddlewareInterface $middleware
     * @param int $priority
     * @param callable|null $condition
     * @phpstan-param null|callable(RoutePath): bool $condition
     * @return void
     */
    public function registerAuthMiddleware(AuthMiddlewareInterface $middleware, int $priority = 0, ?callable $condition = null): void
    {
        $this->auth_middlewares[] = [
            'priority' => $priority,
            'middleware' => $middleware,
            'condition' => $condition ?? static fn(RoutePath $route_path) => true,
        ];
        // Sort by priority (Higher priority last due to how the processing is done)
        usort($this->auth_middlewares, static fn($a, $b) => $a['priority'] <=> $b['priority']);
    }

    /**
     * Register a request middleware
     *
     * @param RequestMiddlewareInterface $middleware
     * @param int $priority
     * @param callable|null $condition
     * @phpstan-param null|callable(RoutePath): bool $condition
     * @return void
     */
    public function registerRequestMiddleware(RequestMiddlewareInterface $middleware, int $priority = 0, ?callable $condition = null): void
    {
        $this->request_middlewares[] = [
            'priority' => $priority,
            'middleware' => $middleware,
            'condition' => $condition ?? static fn(RoutePath $route_path) => true,
        ];
        // Sort by priority (Higher priority last due to how the processing is done)
        usort($this->request_middlewares, static fn($a, $b) => $a['priority'] <=> $b['priority']);
    }

    /**
     * Register a response middleware
     *
     * @param ResponseMiddlewareInterface $middleware
     * @param int $priority
     * @param callable|null $condition
     * @phpstan-param null|callable(RoutePath): bool $condition
     * @return void
     */
    public function registerResponseMiddleware(ResponseMiddlewareInterface $middleware, int $priority = 0, ?callable $condition = null): void
    {
        $this->response_middlewares[] = [
            'priority' => $priority,
            'middleware' => $middleware,
            'condition' => $condition ?? static fn(RoutePath $route_path) => true,
        ];
        // Sort by priority (Higher priority last due to how the processing is done)
        usort($this->response_middlewares, static fn($a, $b) => $a['priority'] <=> $b['priority']);
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
                $rc = new ReflectionClass($controller);
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
        return $routes;
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
        usort($paths, static fn($a, $b) => strcmp($a['href'], $b['href']));
        return $paths;
    }

    /**
     * @param Request $request
     * @return RoutePath[]
     */
    public function matchAll(Request $request): array
    {
        $routes = $this->getRoutesFromCache();

        $api_version = $request->getHeaderLine('GLPI-API-Version') ?: static::API_VERSION;
        // Filter routes by the requested API version and method
        $routes = array_filter($routes, static function ($route) use ($request, $api_version) {
            if ($route->matchesAPIVersion($api_version) && in_array($request->getMethod(), $route->getRouteMethods(), true)) {
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
        usort($routes, static fn(RoutePath $a, RoutePath $b) => ($a->getRoutePriority() < $b->getRoutePriority()) ? -1 : 1);

        return array_reverse($routes);
    }

    /**
     * @param Request $request
     * @return ?RoutePath
     */
    public function match(Request $request): ?RoutePath
    {
        $routes = $this->matchAll($request);
        if (count($routes)) {
            return reset($routes);
        }
        return null;
    }

    private function doAuthMiddleware(MiddlewareInput $input): void
    {
        $action = static function (MiddlewareInput $input, ?callable $next = null) {};
        foreach ($this->auth_middlewares as $middleware) {
            $explicit_include = in_array(get_class($middleware['middleware']), $input->route_path->getMiddlewares());
            $conditions_met = $explicit_include || $middleware['condition']($input->route_path);
            if (!$conditions_met) {
                continue;
            }
            $action = static fn($input) => $middleware['middleware']($input, $action);
        }
        $action($input); // @phpstan-ignore expr.resultUnused (phpstan doens't understand this, TODO rewrite with listeners instead of callbacks)
    }

    private function doRequestMiddleware(MiddlewareInput $input): ?Response
    {
        $action = (static fn(MiddlewareInput $input, ?callable $next = null) => null);
        foreach ($this->request_middlewares as $middleware) {
            $explicit_include = in_array(get_class($middleware['middleware']), $input->route_path->getMiddlewares());
            $conditions_met = $explicit_include || $middleware['condition']($input->route_path);
            if (!$conditions_met) {
                continue;
            }
            $action = static fn($input) => $middleware['middleware']($input, $action);
        }

        /** @var ?Response $result  */
        $result = $action($input);

        return $result;
    }

    private function doResponseMiddleware(MiddlewareInput $input): void
    {
        $action = static function (MiddlewareInput $input, ?callable $next = null) {};
        foreach ($this->response_middlewares as $middleware) {
            $explicit_include = in_array(get_class($middleware['middleware']), $input->route_path->getMiddlewares());
            $conditions_met = $explicit_include || $middleware['condition']($input->route_path);
            if (!$conditions_met) {
                continue;
            }
            $action = static fn($input) => $middleware['middleware']($input, $action);
        }
        $action($input); // @phpstan-ignore expr.resultUnused (phpstan doens't understand this, TODO rewrite with listeners instead of callbacks)
    }

    public function handleRequest(Request $request): Response
    {
        global $CFG_GLPI;

        // Start an output buffer to capture any potential debug errors
        $current_output_buffer_level = ob_get_level();
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

        $request = $request->withQueryParams(array_merge($request->getQueryParams(), $_GET));

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
                    throw new RuntimeException();
                }
            } catch (Throwable) {
                $response = new JSONResponse(
                    AbstractController::getErrorResponseBody(AbstractController::ERROR_GENERIC, _x('api', 'Invalid JSON body')),
                    400
                );
            }
        }

        if (Config::isHlApiEnabled()) {
            // OAuth will only be used if the API is enabled
            try {
                $this->handleAuth($request);
            } catch (OAuthServerException $e) {
                return new JSONResponse(
                    content: AbstractController::getErrorResponseBody(
                        status: AbstractController::ERROR_INVALID_PARAMETER,
                        title: 'Invalid OAuth token',
                        detail: $e->getHint()
                    ),
                    status: 400
                );
            }
        }

        $this->original_request = clone $request;
        $matched_route = $this->match($request);
        $routes_allowed_when_disabled = ['/token'];

        if ($matched_route === null) {
            $response = new Response(404);
        } else {
            $requires_auth = $matched_route->getRouteSecurityLevel() !== Route::SECURITY_NONE;
            if (Config::isHlApiEnabled()) {
                $unauthenticated_response = new JSONResponse([
                    'title' => _x('api', 'You are not authenticated'),
                    'detail' => _x('api', 'The Authorization header is missing or invalid'),
                    'status' => 'ERROR_UNAUTHENTICATED',
                ], 401);
            } else {
                // Remove all authentication middlewares except InternalAuthMiddleware if it is present
                // If HL API is disabled, only internal requests should be allowed as they are used for features like Webhooks rather than user-initiated requests
                $this->auth_middlewares = array_filter($this->auth_middlewares, static fn($middleware) => get_class($middleware['middleware']) === InternalAuthMiddleware::class);
                // The internal auth is required to succeed here even for public endpoints because the HL API is disabled
                $requires_auth = !in_array(strtolower($matched_route->getCompiledPath()), $routes_allowed_when_disabled, true);
                $unauthenticated_response = AbstractController::getAccessDeniedErrorResponse('The High-Level API is disabled');
            }
            $middleware_input = new MiddlewareInput($request, $matched_route, $unauthenticated_response);
            // Do auth middlewares now even if auth isn't required so session data *could* be used like the theme for doc endpoints.
            $this->doAuthMiddleware($middleware_input);
            $auth_from_middleware = $middleware_input->response === null;
            $this->current_client ??= $middleware_input->client;

            if ($requires_auth && !$auth_from_middleware) {
                if (!($request->hasHeader('Authorization') && Session::getLoginUserID() !== false)) {
                    $response = $unauthenticated_response;
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

                    // Make sure all required parameters are present
                    $params = $matched_route->getRouteDoc($request->getMethod())?->getParameters() ?? [];
                    $missing_params = [];
                    foreach ($params as $param) {
                        if ($param->getRequired() && !$request->hasParameter($param->getName())) {
                            $missing_params[] = $param->getName();
                        }
                    }
                    if (count($missing_params)) {
                        $errors = [
                            'missing' => $missing_params,
                        ];
                        $response = AbstractController::getInvalidParametersErrorResponse($errors);
                    }

                    if ($response === null) {
                        $response = $matched_route->invoke($request);
                        $middleware_input = new MiddlewareInput($request, $matched_route, $response);
                        $this->doResponseMiddleware($middleware_input);
                        $response = $middleware_input->response;
                    }
                }
            }
        }

        if ($original_method === 'HEAD') {
            $response = $response->withBody(Utils::streamFor(''));
        }
        // Clear output buffers up to the level when the request was started
        while (ob_get_level() > $current_output_buffer_level) {
            ob_end_clean();
        }

        return $response;
    }

    /**
     * Try to start a temporary session if an OAuth token is provided and handle the profile and entity headers.
     * @param Request $request
     * @return void
     * @throws OAuthServerException
     */
    private function handleAuth(Request $request): void
    {
        if ($request->hasHeader('Authorization')) {
            // Ignore Basic auth because it is only supported when passing data in password flow to /token, not actual auth
            if (str_starts_with($request->getHeaderLine('Authorization'), 'Basic ')) {
                return;
            }
            $this->startTemporarySession($request);
            if ($request->hasHeader('GLPI-Profile')) {
                $requested_profile = $request->getHeaderLine('GLPI-Profile');
                if (is_numeric($requested_profile)) {
                    Session::changeProfile((int) $requested_profile);
                }
            }
            if ($request->hasHeader('GLPI-Entity')) {
                $requested_entity = $request->getHeaderLine('GLPI-Entity');
                if (is_numeric($requested_entity)) {
                    $is_recursive = $request->hasHeader('GLPI-Entity-Recursive') && strtolower($request->getHeaderLine('GLPI-Entity-Recursive')) === 'true';
                    Session::changeActiveEntities((int) $requested_entity, $is_recursive);
                }
            }
        }
    }

    /**
     * @throws OAuthServerException
     */
    public function startTemporarySession(Request $request): void
    {
        $this->current_client = Server::validateAccessToken($request);
        $auth = new Auth();
        $auth->auth_succeded = true;
        $auth->user = new User();
        $auth->user->getFromDB($this->current_client['user_id']);
        Session::init($auth);
        if ($request->getHeaderLine('Accept-Language')) {
            // Make sure language header is set in SERVER superglobal so that Session::getPreferredLanguage() works
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] = $request->getHeaderLine('Accept-Language');
            $_SESSION['glpilanguage'] = Session::getPreferredLanguage();
            $_SESSION['glpi_dropdowntranslations'] = DropdownTranslation::getAvailableTranslations($_SESSION["glpilanguage"]);
        }
    }

    /**
     * Get all registered controllers
     * @return AbstractController[]
     */
    public function getControllers(): array
    {
        return $this->controllers;
    }

    public function getCurrentClient(): ?array
    {
        return $this->current_client;
    }
}
