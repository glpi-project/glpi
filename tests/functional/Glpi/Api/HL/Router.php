<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace tests\units\Glpi\Api\HL;

use Glpi\Api\HL\Route;
use Glpi\Api\HL\RouteVersion;
use Glpi\Http\JSONResponse;
use Glpi\Http\Request;
use Glpi\Http\Response;
use GLPITestCase;
use Psr\Http\Message\RequestInterface;

class Router extends GLPITestCase
{
    public function testMatch()
    {
        $router = TestRouter::getInstance();
        $this->variable($router->match(new Request('GET', '/test')))->isNotNull();
    }

    public function testAllRoutesHaveVersioningInfo()
    {
        $router = \Glpi\Api\HL\Router::getInstance();
        $all_routes = $router->getAllRoutes();

        $routes_missing_versions = [];
        foreach ($all_routes as $route) {
            $version_attrs = $route->getMethod()->getAttributes(RouteVersion::class);
            if (empty($version_attrs)) {
                $routes_missing_versions[] = $route->getRoutePath();
            }
        }
        $this->array($routes_missing_versions)->isEmpty('Routes missing versioning info: ' . implode(', ', $routes_missing_versions));
    }

    public function testAllSchemasHaveVersioningInfo()
    {
        $router = \Glpi\Api\HL\Router::getInstance();
        $controllers = $router->getControllers();

        $schemas_missing_versions = [];
        foreach ($controllers as $controller) {
            $schemas = $controller::getKnownSchemas(null);
            foreach ($schemas as $schema_name => $schema) {
                if (str_starts_with($schema_name, '_')) {
                    continue;
                }
                if (!isset($schema['x-version-introduced'])) {
                    $schemas_missing_versions[] = $schema_name . ' in ' . $controller::class;
                }
            }
        }

        $this->array($schemas_missing_versions)->isEmpty('Schemas missing versioning info: ' . implode(', ', $schemas_missing_versions));
    }

    public function testNormalizeAPIVersion()
    {
        $this->string(TestRouter::normalizeAPIVersion('50'))->isEqualTo('50.2.0');
        $this->string(TestRouter::normalizeAPIVersion('50.1.1'))->isEqualTo('50.1.1');
        $this->string(TestRouter::normalizeAPIVersion('50.1'))->isEqualTo('50.1.2');
        $this->string(TestRouter::normalizeAPIVersion('50.2'))->isEqualTo('50.2.0');
    }
}

// @codingStandardsIgnoreStart
class TestRouter extends \Glpi\Api\HL\Router
{
    // @codingStandardsIgnoreEnd
    public static function getInstance(): \Glpi\Api\HL\Router
    {
        static $router = null;
        if ($router === null) {
            $router = new static();
            $router->registerController(new TestController());
        }
        return $router;
    }

    public static function getAPIVersions(): array
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $versions = parent::getAPIVersions();

        // Add fake versions we will probably never use
        $versions[] = [
            'api_version' => '50',
            'version' => '50.0.0',
            'endpoint' => $CFG_GLPI['url_base'] . '/api.php/v50',
        ];
        $versions[] = [
            'api_version' => '50',
            'version' => '50.1.0',
            'endpoint' => $CFG_GLPI['url_base'] . '/api.php/v50.1',
        ];
        $versions[] = [
            'api_version' => '50',
            'version' => '50.1.1',
            'endpoint' => $CFG_GLPI['url_base'] . '/api.php/v50.1.1',
        ];
        $versions[] = [
            'api_version' => '50',
            'version' => '50.1.2',
            'endpoint' => $CFG_GLPI['url_base'] . '/api.php/v50.1.2',
        ];
        $versions[] = [
            'api_version' => '50',
            'version' => '50.2.0',
            'endpoint' => $CFG_GLPI['url_base'] . '/api.php/v50.2',
        ];
        $versions[] = [
            'api_version' => '51',
            'version' => '51.0.0',
            'endpoint' => $CFG_GLPI['url_base'] . '/api.php/v51',
        ];

        return $versions;
    }
}

// @codingStandardsIgnoreStart
class TestController extends \Glpi\Api\HL\Controller\AbstractController
{
    // @codingStandardsIgnoreEnd
    /**
     * @param RequestInterface $request
     * @return Response
     */
    #[Route('/{req}', ['GET', 'POST', 'PATCH', 'PUT', 'DELETE', 'OPTIONS'], ['req' => '.*'], -1)]
    #[RouteVersion(introduced: TestRouter::API_VERSION)]
    public function defaultRoute(RequestInterface $request): Response
    {
        return new Response(200, [], __FUNCTION__);
    }
}
