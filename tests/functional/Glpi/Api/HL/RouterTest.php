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

namespace tests\units\Glpi\Api\HL;

use Glpi\Api\HL\Controller\AbstractController;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\Router;
use Glpi\Api\HL\RouteVersion;
use Glpi\Http\Request;
use Glpi\Http\Response;
use GLPITestCase;
use Psr\Http\Message\RequestInterface;

class RouterTest extends GLPITestCase
{
    public function testMatch()
    {
        $router = TestRouter::getInstance();
        $this->assertNotNull($router->match(new Request('GET', '/test')));
    }

    public function testAllRoutesHaveVersioningInfo()
    {
        $router = Router::getInstance();
        $all_routes = $router->getAllRoutes();

        $routes_missing_versions = [];
        foreach ($all_routes as $route) {
            $version_attrs = $route->getMethod()->getAttributes(RouteVersion::class);
            if (empty($version_attrs)) {
                $routes_missing_versions[] = $route->getRoutePath();
            }
        }
        $this->assertEmpty($routes_missing_versions, 'Routes missing versioning info: ' . implode(', ', $routes_missing_versions));
    }

    public function testAllSchemasHaveVersioningInfo()
    {
        $router = Router::getInstance();
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

        $this->assertEmpty($schemas_missing_versions, 'Schemas missing versioning info: ' . implode(', ', $schemas_missing_versions));
    }

    public function testNormalizeAPIVersion()
    {
        $this->assertEquals('50.2.0', TestRouter::normalizeAPIVersion('50'));
        $this->assertEquals('50.1.1', TestRouter::normalizeAPIVersion('50.1.1'));
        $this->assertEquals('50.1.2', TestRouter::normalizeAPIVersion('50.1'));
        $this->assertEquals('50.2.0', TestRouter::normalizeAPIVersion('50.2'));
    }

    public function testHLAPIDisabled()
    {
        global $CFG_GLPI;

        $CFG_GLPI['enable_hlapi'] = 0;
        $router = TestRouter::getInstance();
        $response = $router->handleRequest(new Request('GET', '/Computer'));
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('The High-Level API is disabled', (string) $response->getBody());

        // Requesting non-existing endpoints should have the same behavior
        $response = $router->handleRequest(new Request('GET', '/nonexistingendpoint'));
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('The High-Level API is disabled', (string) $response->getBody());
    }
}

// @codingStandardsIgnoreStart
class TestRouter extends Router
{
    // @codingStandardsIgnoreEnd
    public static function getInstance(): Router
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
class TestController extends AbstractController
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
