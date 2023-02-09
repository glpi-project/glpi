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

use Glpi\Api\HL\Controller\CoreController;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\RoutePath;
use Glpi\Api\HL\Router;
use Glpi\Http\Request;
use Glpi\Http\Response;

/**
 * @property HLAPIHelper $api
 */
class HLAPITestCase extends \DbTestCase
{
    public function afterTestMethod($method)
    {
        // kill session
        Session::destroy();
        parent::afterTestMethod($method);
    }

    protected function checkSimpleContentExpect(array $content, array $expected): void
    {
        if (isset($expected['count'])) {
            $operator = $expected['count'][0];
            switch ($operator) {
                case '>':
                    $this->integer(count($content))->isGreaterThan($expected['count'][1]);
                    break;
                case '>=':
                    $this->integer(count($content))->isGreaterThanOrEqualTo($expected['count'][1]);
                    break;
                case '<':
                    $this->integer(count($content))->isLessThan($expected['count'][1]);
                    break;
                case '<=':
                    $this->integer(count($content))->isLessThanOrEqualTo($expected['count'][1]);
                    break;
                case '=':
                    $this->integer(count($content))->isEqualTo($expected['count'][1]);
                    break;
            }
        }

        if (isset($expected['fields'])) {
            foreach ($expected['fields'] as $field => $value) {
                // $field could be an array path. We need to check each part of the path
                $parts = explode('.', $field);
                $current = $content;
                foreach ($parts as $part) {
                    $this->array($current)->hasKey($part);
                    $current = $current[$part];
                }
                $this->variable($current)->isEqualTo($value);
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
    // @codingStandardsIgnoreEnd
    public function __construct(
        private Router $router,
        private HLAPITestCase $test
    ) {
    }

    public function hasMatch(Request $request)
    {
        $request = $request->withHeader('Glpi-Session-Token', session_id());
        $match = $this->router->match($request);
        $is_default_route = false;
        if (
            $match !== null &&
            ($match->getController() === CoreController::class && $match->getMethod()->getShortName() === 'defaultRoute')
        ) {
            $is_default_route = true;
        }
        return $this->test->boolean($match !== null && !$is_default_route);
    }

    /**
     * @param Request $request
     * @param callable(HLAPICallAsserter): void $fn
     * @return self
     */
    public function call(Request $request, callable $fn, bool $auto_session_header = true): self
    {
        if ($auto_session_header) {
            $request = $request->withHeader('Glpi-Session-Token', session_id());
        }
        $response = $this->router->handleRequest($request);
        $fn(new HLAPICallAsserter($this->test, $this->router, $response));
        return $this;
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
    ) {
    }

    public function __get(string $name)
    {
        return match ($name) {
            'originalRequest' => new HLAPIRequestAsserter($this, $this->router->getOriginalRequest()),
            'finalRequest' => new HLAPIRequestAsserter($this, $this->router->getFinalRequest()),
            'response' => new HLAPIResponseAsserter($this, $this->response),
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
    ) {
    }

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
        private Response $response
    ) {
    }

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
        // Status is 200 - 299
        $this->call_asserter->test
            ->integer($this->response->getStatusCode())->isGreaterThanOrEqualTo(200);
        $this->call_asserter->test
            ->integer($this->response->getStatusCode())->isLessThan(300);
        return $this;
    }

    public function isUnauthorizedError(): HLAPIResponseAsserter
    {
        // Status is 401
        $this->call_asserter->test
            ->integer($this->response->getStatusCode())->isEqualTo(401);
        $decoded_content = json_decode((string) $this->response->getBody(), true);
        $this->call_asserter->test
            ->array($decoded_content)->hasKeys(['title', 'detail', 'status']);
        $this->call_asserter->test
            ->string($decoded_content['status'])->isEqualTo('ERROR_UNAUTHENTICATED');
        return $this;
    }

    public function isNotFoundError(): HLAPIResponseAsserter
    {
        // Status is 404
        $this->call_asserter->test
            ->integer($this->response->getStatusCode())->isEqualTo(404);
        $decoded_content = json_decode((string) $this->response->getBody(), true);
        $this->call_asserter->test
            ->array($decoded_content)->hasKeys(['title', 'detail', 'status']);
        $this->call_asserter->test
            ->string($decoded_content['status'])->isEqualTo('ERROR_ITEM_NOT_FOUND');
        return $this;
    }

    public function matchesSchema(string $schema_name): HLAPIResponseAsserter
    {
        $matched_route = $this->call_asserter->route->get();
        /** @var class-string<\Glpi\Api\HL\Controller\AbstractController> $controller */
        $controller = $matched_route->getController();
        $schema = $controller::getKnownSchemas()[$schema_name];
        $content = json_decode((string) $this->response->getBody(), true);

        // Verify the JSON content matches the OpenAPI schema
        $this->call_asserter->test
            ->boolean(\Glpi\Api\HL\Doc\Schema::fromArray($schema)->isValid($content))->isTrue();
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
    ) {
    }

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
            ->boolean($this->routePath->getRouteSecurityLevel() !== Route::SECURITY_NONE)->isTrue();
        return $this;
    }

    public function isAnonymousAllowed(): self
    {
        $this->call_asserter->test
            ->boolean($this->routePath->getRouteSecurityLevel() === Route::SECURITY_NONE)->isTrue();
        return $this;
    }

    public function get(): RoutePath
    {
        return $this->routePath;
    }
}
