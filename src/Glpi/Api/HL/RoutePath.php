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

use Exception;
use Glpi\Api\HL\Controller\AbstractController;
use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\Middleware\AbstractMiddleware;
use Glpi\Http\Request;
use Glpi\Http\Response;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use RuntimeException;
use Throwable;

use function Safe\preg_match;
use function Safe\preg_match_all;
use function Safe\preg_replace_callback;

/**
 * @phpstan-type RoutePathCacheHint array{key: string, path: string, compiled_path: string, methods: string[], priority: int, security: int}
 */
final class RoutePath
{
    /**
     * The Route attribute
     * @var Route|null
     */
    private ?Route $route = null;

    /**
     * @var ReflectionClass<AbstractController>|null
     */
    private ?ReflectionClass $controller = null;

    /**
     * @var ReflectionMethod|null
     */
    private ?ReflectionMethod $method = null;

    /**
     * The relative URI path with placeholder requirements inlined
     * @var string|null
     */
    private ?string $compiled_path;

    /**
     * Key used to identify the controller and method this route is linked to.
     * Used for hydration.
     * @var string
     */
    private string $key;

    /**
     * @var string The non-compiled path
     */
    private string $path;

    /**
     * @var array The list of HTTP methods this route is linked to
     */
    private array $methods;

    /**
     * @var int The priority of this route
     */
    private int $priority;

    /**
     * @var int The security level of this route
     */
    private int $security;

    /**
     * @var AbstractController|null The controller instance
     */
    private $controller_instance;

    /**
     * @param class-string<AbstractController> $class
     * @param string $method
     */
    public function __construct(string $class, string $method, string $path, array $methods, int $priority, int $security, ?string $compiled_path = null)
    {
        $this->key = $class . '::' . $method;
        $this->path = $path;
        $this->methods = $methods;
        $this->priority = $priority;
        $this->security = $security;
        $this->compiled_path = $compiled_path;
    }

    /**
     * @param Route $route Route Attribute instance
     * @param class-string<AbstractController> $class Controller class name
     * @param string $method Controller method name
     */
    public static function fromRouteAttribute(Route $route, string $class, string $method): self
    {
        $path = new self(
            $class,
            $method,
            $route->path,
            $route->methods,
            $route->priority,
            $route->security_level
        );
        $path->controller = new ReflectionClass($class);
        $path->method = $path->controller->getMethod($method);
        $path->route = $route;
        $path->mergeControllerRouteData();
        $path->compilePath();

        return $path;
    }

    private function hydrate(): void
    {
        $is_hydrated = $this->route !== null && $this->controller !== null && $this->method !== null;
        if (!$is_hydrated) {
            [$controller, $method] = explode('::', $this->key);
            try {
                if (!\is_a($controller, AbstractController::class, true)) {
                    throw new Exception('Invalid controller');
                }
                $this->controller = new ReflectionClass($controller);
                $this->method = $this->controller->getMethod($method);
                if (!$this->method->isPublic()) {
                    throw new Exception('Method is not public');
                }
                $route_attributes = $this->method->getAttributes(Route::class);
                if (count($route_attributes) === 0) {
                    throw new Exception("RoutePath has no Route attribute");
                }
                $this->route = $route_attributes[0]->newInstance();
            } catch (Throwable $e) {
                throw new RuntimeException(
                    "Unable to hydrate RoutePath {$this->key}: {$e->getMessage()}",
                    0,
                    $e
                );
            }
            $this->mergeControllerRouteData();
            $this->compilePath();
        }
    }

    private function getRoute(): Route
    {
        $this->hydrate();
        return $this->route;
    }

    public function getRoutePath(): string
    {
        return $this->path;
    }

    public function getRoutePathWithParameters(array $params = []): string
    {
        $path = $this->getRoutePath();
        foreach ($params as $key => $value) {
            // Ignore arrays/objects
            if (!is_array($value) && !is_object($value)) {
                $path = str_replace('{' . $key . '}', $value, $path);
            }
        }
        return $path;
    }

    /**
     * Get the attributes from the provided path when matched against this route
     * @param string $path
     * @return array<string, string>
     */
    public function getAttributesFromPath(string $path): array
    {
        $attributes = [];
        $placeholders = [];
        preg_match_all('/\{([^}]+)\}/', $this->getRoutePath(), $placeholders);
        $path_parts = explode('/', $path);
        $route_parts = explode('/', $this->getRoutePath());
        foreach ($route_parts as $i => $part) {
            if (isset($path_parts[$i])) {
                if (preg_match('/\{([^}]+)\}/', $part)) {
                    $attributes[trim($part, '{}')] = $path_parts[$i];
                }
            }
        }
        return $attributes;
    }

    public function isValidPath($path): bool
    {
        // Ensure no placeholders are left
        $dynamic_expandable_placeholders = array_filter($this->getRouteRequirements(), static fn($v, $k) => is_callable($v), ARRAY_FILTER_USE_BOTH);
        $leftover_placeholders = [];
        preg_match_all('/\{([^}]+)\}/', $path, $leftover_placeholders);
        // Remove dynamic expandable placeholders
        $leftover_placeholders = array_diff($leftover_placeholders[1], array_keys($dynamic_expandable_placeholders));
        return count($leftover_placeholders) === 0;
    }

    public function getCompiledPath(): string
    {
        if ($this->compiled_path === null) {
            $this->hydrate();
        }
        return $this->compiled_path;
    }

    /**
     * @return string[]
     */
    public function getRouteMethods(): array
    {
        return $this->methods;
    }

    /**
     * @return array<string, string>
     */
    public function getRouteRequirements(): array
    {
        return $this->getRoute()->requirements;
    }

    public function getRoutePriority(): int
    {
        return $this->priority;
    }

    public function getRouteSecurityLevel(): int
    {
        return $this->security;
    }

    /**
     * @return string[]
     */
    public function getRouteTags(): array
    {
        return $this->getRoute()->tags;
    }

    /**
     * @return string
     */
    public function getController(): string
    {
        $this->hydrate();
        return $this->controller->getName();
    }

    public function getControllerInstance(): AbstractController
    {
        if ($this->controller_instance === null) {
            $this->hydrate();
            $this->controller_instance = $this->controller->newInstance();
        }
        return $this->controller_instance;
    }

    /**
     * @return ReflectionMethod
     */
    public function getMethod(): ReflectionMethod
    {
        $this->hydrate();
        return $this->method;
    }

    /**
     * @return Doc\Route[]
     */
    public function getRouteDocs(): array
    {
        $this->hydrate();
        $controller_doc_attrs = array_filter(
            $this->controller->getAttributes(),
            static fn($attr) => is_a($attr->getName(), Doc\Route::class, true)
        );
        /** @var Doc\Route $controller_doc_attr */
        $controller_doc_attr = count($controller_doc_attrs) ? reset($controller_doc_attrs)->newInstance() : null;
        $doc_attrs = array_filter(
            $this->getMethod()->getAttributes(),
            static fn($attr) => is_a($attr->getName(), Doc\Route::class, true)
        );
        $docs = [];

        foreach ($doc_attrs as $doc_attr) {
            /** @var Doc\Route $doc */
            $doc = $doc_attr->newInstance();
            if ($controller_doc_attr !== null) {
                $doc = new Doc\Route($doc->getDescription(), $doc->getMethods(), array_merge($controller_doc_attr->getParameters(), $doc->getParameters()), $doc->getResponses());
            }
            $docs[] = $doc;
        }
        return $docs;
    }

    public function getRouteDoc(string $method): ?Doc\Route
    {
        $docs = $this->getRouteDocs();
        $result = null;
        foreach ($docs as $doc) {
            if (empty($doc->getMethods())) {
                // Non-specific. Store in $result in case a specific doc is found later
                $result = $doc;
            } elseif (in_array($method, $doc->getMethods(), true)) {
                // Specific. Return immeditately
                return $doc;
            }
        }
        return $result;
    }

    public function getRouteVersion(): RouteVersion
    {
        return $this->getMethod()->getAttributes(RouteVersion::class)[0]->newInstance();
    }

    /**
     * Returns true if the route is valid for the given API version
     * @param string $api_version
     * @return bool
     */
    public function matchesAPIVersion(string $api_version): bool
    {
        $version = $this->getRouteVersion();
        return (version_compare($api_version, $version->introduced, '>=') && (empty($version->removed) || version_compare($api_version, $version->removed, '<')));
    }

    private function setPath(string $path)
    {
        $this->path = $path;
        $this->route->path = $path;
    }

    private function setPriority(int $priority)
    {
        $this->priority = $priority;
        $this->route->priority = $priority;
    }

    /**
     * @return class-string<AbstractMiddleware>[]
     */
    public function getMiddlewares(): array
    {
        return $this->getRoute()->middlewares;
    }

    /**
     * Check if a middleware is present in the route
     * @param class-string<AbstractMiddleware> $middleware
     */
    public function hasMiddleware(string $middleware): bool
    {
        return in_array($middleware, $this->getMiddlewares(), true);
    }

    /**
     * Combine data from the class Route attribute (if present) with the method's own attribute
     *
     * Must be called during or after hydration only.
     * @return void
     */
    private function mergeControllerRouteData(): void
    {
        $controller_attributes = $this->controller->getAttributes(Route::class);
        if (count($controller_attributes)) {
            $controller_route = $controller_attributes[0]->newInstance();
            // Prefix route path with controller path, making sure the route path already starts with a slash
            $path = '/' . ltrim($this->route->path, '/');
            $this->setPath($controller_route->path . $path);

            // Merge requirements and tags
            $this->route->requirements = array_merge($controller_route->requirements, $this->route->requirements);
            $this->route->tags = array_unique(array_merge($controller_route->tags, $this->route->tags));

            if ($controller_route->priority !== Route::DEFAULT_PRIORITY && $this->route->priority === Route::DEFAULT_PRIORITY) {
                $this->setPriority($controller_route->priority);
            }

            // Merge middlewares
            $this->route->middlewares = array_unique(array_merge($controller_route->middlewares, $this->route->middlewares));

            // None of the other properties have meaning when on a class
        }
    }

    /**
     * "Compile" the path by replacing placeholders with regex patterns from the requirements or default patterns
     *
     * Must be called during or after hydration only.
     * @return void
     */
    private function compilePath(): void
    {
        $compiled_path = $this->getRoutePath();

        // Replace all placeholders with their matching requirement or a default pattern (letters,  numbers, underscore only)
        $compiled_path = preg_replace_callback('/(\{[^}]+\})/', function ($matches) {
            $name = $matches[1];
            $name = substr($name, 1, -1);
            if (isset($this->route->requirements[$name])) {
                if (is_callable($this->route->requirements[$name])) {
                    $reqs = $this->route->requirements[$name]();
                } else {
                    $reqs = is_array($this->route->requirements[$name]) ? $this->route->requirements[$name] : [$this->route->requirements[$name]];
                }
                return '(' . implode('|', $reqs) . ')';
            }
            return '([a-zA-Z0-9_]+)';
        }, $compiled_path);

        if ($compiled_path === null) {
            throw new RuntimeException('Failed to compile path');
        }

        // Ensure the compiled path starts with a slash but does not end with one (unless the path is just '/')
        if ($compiled_path !== '/') {
            if ($compiled_path[0] !== '/') {
                $compiled_path = '/' . $compiled_path;
            }
            $compiled_path = rtrim($compiled_path, '/');
        }
        $this->compiled_path = $compiled_path;
    }

    /**
     * @throws ReflectionException
     */
    public function invoke(Request $request): Response
    {
        // Set parameters to defaults if not provided and a default is available
        $params = $request->getParameters();
        $docs = $this->getRouteDocs();
        $matched_doc = array_filter($docs, static fn(Doc\Route $doc) => !count($doc->getMethods()) || in_array($request->getMethod(), $doc->getMethods(), true));
        if (count($matched_doc)) {
            $route_params = $matched_doc[0]->getParameters();
            /** @var Doc\Parameter $param */
            foreach ($route_params as $param) {
                if (!isset($params[$param->getName()]) && $param->getDefaultValue() !== null) {
                    $request->setParameter($param->getName(), $param->getDefaultValue());
                }
            }
        }
        $response = $this->getMethod()->invoke($this->getControllerInstance(), $request);
        if ($response instanceof Response) {
            return $response;
        }
        throw new RuntimeException('Controller method must return a Response object');
    }

    /**
     * Get a minimal representation of this route path that can be cached, used for basic matching, and for recreating the full object
     * @return array
     * @phpstan-return RoutePathCacheHint
     */
    public function getCachedRouteHint(): array
    {
        $this->hydrate();
        return [
            'key' => $this->key,
            'path' => $this->path,
            'compiled_path' => $this->compiled_path,
            'methods' => $this->methods,
            'priority' => $this->priority,
            'security' => $this->security,
        ];
    }
}
