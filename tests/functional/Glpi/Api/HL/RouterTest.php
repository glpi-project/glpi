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

namespace tests\units\Glpi\Api\HL;

use Glpi\Api\HL\Controller\AbstractController;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\Router;
use Glpi\Api\HL\RouteVersion;
use Glpi\Http\Request;
use Glpi\Http\Response;
use Glpi\Tests\GLPITestCase;
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

        $api_versions = array_column($router::getAPIVersions(), 'version');
        foreach ($api_versions as $version) {
            foreach ($controllers as $controller) {
                $schemas = $controller::getKnownSchemas($version);
                foreach ($schemas as $schema_name => $schema) {
                    if (str_starts_with($schema_name, '_')) {
                        continue;
                    }
                    if (!isset($schema['x-version-introduced'])) {
                        $schemas_missing_versions[] = $schema_name . ' in ' . $controller::class;
                    }
                }
            }
        }

        $this->assertEmpty($schemas_missing_versions, 'Schemas missing versioning info: ' . implode(', ', $schemas_missing_versions));
    }

    /**
     * Ensure all schemas for CommonTreeDropdown itemtypes have the correct readonly properties such as completename and level
     * @return void
     */
    public function testAllTreeSchemasHaveReadonlyProps()
    {
        $router = Router::getInstance();
        $controllers = $router->getControllers();

        $schemas_errors = [];
        $required_readonly_props = ['completename', 'level'];

        $api_versions = array_column($router::getAPIVersions(), 'version');
        foreach ($api_versions as $version) {
            foreach ($controllers as $controller) {
                $schemas = $controller::getKnownSchemas($version);
                foreach ($schemas as $schema_name => $schema) {
                    if (!isset($schema['x-itemtype']) || !is_subclass_of($schema['x-itemtype'], \CommonTreeDropdown::class)) {
                        continue;
                    }
                    foreach ($required_readonly_props as $prop) {
                        if (!isset($schema['properties'][$prop])) {
                            $schemas_errors[] = "Schema $schema_name in " . $controller::class . " is missing property '$prop'";
                        } else {
                            if (!isset($schema['properties'][$prop]['readOnly']) || $schema['properties'][$prop]['readOnly'] !== true) {
                                $schemas_errors[] = "Property '$prop' in schema $schema_name in " . $controller::class . " is not marked as readOnly";
                            }
                        }
                    }
                }
            }
        }
        $this->assertEmpty($schemas_errors, "Tree schemas with errors: \n" . implode("\n", $schemas_errors));
    }

    /**
     * Ensure all schemas have the expected readonly properties where applicable.
     * - Mapped properties can only be read currently.
     * @return void
     */
    public function testAllSchemasHaveExpectedReadonlyProps(): void
    {
        $router = Router::getInstance();
        $controllers = $router->getControllers();

        $schemas_errors = [];

        $fn_check_properties = static function ($parent_path, $properties, $schema_name, $controller) use (&$schemas_errors, &$fn_check_properties) {
            foreach ($properties as $prop_name => $prop) {
                $full_prop_name = $parent_path !== '' ? ($parent_path . '.' . $prop_name) : $prop_name;
                if (isset($prop['x-mapper'])) {
                    // A mapped property must be read-only. It either declares `readOnly` outright,
                    // or defers it to a later API version via `x-version-readonly` (legacy properties
                    // that used to be writable columns) — in which case that version must already be
                    // reached by the current API version.
                    $is_readonly = ($prop['readOnly'] ?? false) === true
                        || (
                            isset($prop['x-version-readonly'])
                            && version_compare(Router::API_VERSION, $prop['x-version-readonly']) >= 0
                        );
                    if (!$is_readonly) {
                        $schemas_errors[] = "Property '$full_prop_name' in schema $schema_name in " . $controller::class . " is mapped but is not marked as readOnly";
                    }
                }
                if (isset($prop['type'], $prop['properties']) && $prop['type'] === 'object' && is_array($prop['properties'])) {
                    $fn_check_properties($full_prop_name, $prop['properties'], $schema_name, $controller);
                }
                if (isset($prop['items']['properties'], $prop['type']) && $prop['type'] === 'array' && is_array($prop['items']['properties'])) {
                    $fn_check_properties($full_prop_name, $prop['items']['properties'], $schema_name, $controller);
                }
            }
        };

        $api_versions = array_column($router::getAPIVersions(), 'version');
        foreach ($api_versions as $version) {
            foreach ($controllers as $controller) {
                $schemas = $controller::getKnownSchemas($version);
                foreach ($schemas as $schema_name => $schema) {
                    if (!isset($schema['properties']) || !is_array($schema['properties'])) {
                        continue;
                    }
                    $fn_check_properties('', $schema['properties'], $schema_name, $controller);
                }
            }
        }

        $this->assertEmpty($schemas_errors, "Schemas with readonly property errors: \n" . implode("\n", $schemas_errors));
    }

    /**
     * Ensure there are not multiple schemas for the same itemtype (identified by x-itemtype).
     * In some cases, like user preferences, we may hav e multiple schemas for the same itemtype, but those extra schemas
     * should use x-table instead to point to the table directly.
     * @return void
     */
    public function testNoDuplicateItemtypeSchemas()
    {
        $router = Router::getInstance();
        $controllers = $router->getControllers();

        $api_versions = array_column($router::getAPIVersions(), 'version');
        foreach ($api_versions as $version) {
            $seen_itemtypes = [];
            $duplicate_schemas = [];
            $all_schemas = [];
            foreach ($controllers as $controller) {
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $all_schemas = array_merge($all_schemas, $controller::getKnownSchemas($version));
            }
            foreach ($all_schemas as $schema_name => $schema) {
                //TODO remove SoftwareLicense check after HLAPI v2 removed
                if ($schema_name === 'SoftwareLicense') {
                    continue;
                }
                if (isset($schema['x-itemtype'])) {
                    $itemtype = $schema['x-itemtype'];
                    if (isset($seen_itemtypes[$itemtype])) {
                        $duplicate_schemas[] = "Itemtype $itemtype has multiple schemas: " . $seen_itemtypes[$itemtype] . " and $schema_name";
                    } else {
                        $seen_itemtypes[$itemtype] = $schema_name;
                    }
                }
            }

            $this->assertEmpty($duplicate_schemas, "Duplicate itemtype schemas found: \n" . implode("\n", $duplicate_schemas));
        }
    }

    public function testHLAPIDisabled()
    {
        global $CFG_GLPI;

        $CFG_GLPI['enable_hlapi'] = 0;
        $router = Router::getInstance();
        $response = $router->handleRequest(new Request('GET', '/Computer'));
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('The High-Level API is disabled', (string) $response->getBody());

        // Requesting non-existing endpoints should have the same behavior
        $response = $router->handleRequest(new Request('GET', '/nonexistingendpoint'));
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('The High-Level API is disabled', (string) $response->getBody());
    }

    public function testNormalizeVersion()
    {
        // invalid version = router default
        $this->assertEquals('51.0.0', TestRouter::normalizeAPIVersion('99'));
        // only major version = latest API version for this major
        $this->assertEquals('50.2.0', TestRouter::normalizeAPIVersion('50'));
        // major.minor version = latest API version for this major.minor
        $this->assertEquals('50.1.2', TestRouter::normalizeAPIVersion('50.1'));
        // major.minor.patch version = same version
        $this->assertEquals('50.1.1', TestRouter::normalizeAPIVersion('50.1.1'));

        $this->assertEquals('50.2.0', TestRouter::normalizeAPIVersion('50.2'));
    }

    public function testGetAPIMajorVersions()
    {
        $majors = Router::getAPIMajorVersions();
        // Numeric string keys are normalized to int by PHP, hence the array_map().
        $declared = array_map('strval', array_keys($majors));

        // Asserted as invariants rather than as an exact list: shipping a new major is precisely
        // what this whole feature prepares for, and must not turn this test red.

        // Deduplicated: the real router declares several 2.x minor versions.
        $this->assertCount(count(array_unique($declared)), $declared);

        // Sorted ascending, which is what makes "oldest" and "latest" meaningful to the callers.
        $sorted = $declared;
        usort($sorted, static fn(string $a, string $b): int => version_compare($a, $b));
        $this->assertSame($sorted, $declared);

        // Major 2 still has non deprecated minor versions (2.3, 2.4).
        $this->assertArrayHasKey('2', $majors);
        $this->assertFalse($majors['2']['deprecated']);
        $this->assertArrayHasKey('1', $majors);
        $this->assertFalse($majors['1']['deprecated']);
    }

    public function testGetAPIMajorVersionsDeprecatedMajor()
    {
        $majors = FullyDeprecatedMajorRouter::getAPIMajorVersions();

        // Major 60 only has deprecated minor versions, so the major itself is deprecated.
        $this->assertTrue($majors['60']['deprecated']);
        // Major 61 has one non deprecated minor version.
        $this->assertFalse($majors['61']['deprecated']);
        // Sorted by ascending version, not by declaration order.
        // Numeric string keys are normalized to int by PHP, hence the array_map().
        $this->assertSame(['1', '2', '60', '61'], array_map('strval', array_keys($majors)));
    }

    public function testRoutingByVersion()
    {
        $router = TestRouter::getInstance();
        // 50.0 is requesting 50.0.X or earlier
        $this->assertNotEquals('/{req}', $router->match(new Request('GET', '/version500', ['GLPI-API-Version' => '50.0']))->getRoutePath());
        // 50 is requesting 50.X.X or earlier
        $this->assertNotEquals('/{req}', $router->match(new Request('GET', '/version500', ['GLPI-API-Version' => '50']))->getRoutePath());
        // 50.1 is requesting 50.1.X or earlier
        $this->assertNotEquals('/{req}', $router->match(new Request('GET', '/version500', ['GLPI-API-Version' => '50.1']))->getRoutePath());
        $this->assertNotEquals('/{req}', $router->match(new Request('GET', '/version500', ['GLPI-API-Version' => '51']))->getRoutePath());

        $this->assertEquals('/{req}', $router->match(new Request('GET', '/version501', ['GLPI-API-Version' => '50.0']))->getRoutePath());
        $this->assertNotEquals('/{req}', $router->match(new Request('GET', '/version501', ['GLPI-API-Version' => '50.1']))->getRoutePath());
        $this->assertNotEquals('/{req}', $router->match(new Request('GET', '/version501', ['GLPI-API-Version' => '50']))->getRoutePath());

        $this->assertEquals('/{req}', $router->match(new Request('GET', '/version510', ['GLPI-API-Version' => '50.0']))->getRoutePath());
        $this->assertEquals('/{req}', $router->match(new Request('GET', '/version510', ['GLPI-API-Version' => '50.1']))->getRoutePath());
        $this->assertNotEquals('/{req}', $router->match(new Request('GET', '/version510', ['GLPI-API-Version' => '51']))->getRoutePath());
        $this->assertEquals('/{req}', $router->match(new Request('GET', '/version510', ['GLPI-API-Version' => '50']))->getRoutePath());
    }

    public function testContentTypeWithCharset()
    {
        $router = TestRouter::getInstance();
        $router->handleRequest(new Request('POST', '/test', ['Content-Type' => 'application/json; charset=utf-8'], json_encode(['test' => 'value'])));
        $this->assertEquals('value', $router->getOriginalRequest()->getParameter('test'));
    }
}

// @codingStandardsIgnoreStart
class TestRouter extends Router
{
    public const API_VERSION = '51.0.0';

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

    protected static function getRawKnownSchemas(?string $api_version = null): array
    {
        return [
            'Schema200' => [
                'type' => 'object',
                'x-version-introduced' => '2.0',
                'properties' => [
                    'field1' => [
                        'type' => 'string',
                    ],
                    'field2' => [
                        'type' => 'string',
                        'x-version-introduced' => '2.1.0',
                    ],
                ],
            ],
            'Schema200_2' => [
                'type' => 'object',
                'x-version-introduced' => '2.0.0',
                'properties' => [
                    'field1' => [
                        'type' => 'string',
                    ],

                    'field2' => [
                        'type' => 'string',
                        'x-version-introduced' => '2.1.0',
                    ],
                ],
            ],
            'Schema210' => [
                'type' => 'object',
                'x-version-introduced' => '2.1.0',
                'properties' => [
                    'field1' => [
                        'type' => 'string',
                    ],
                ],
            ],
        ];
    }

    #[Route('/{req}', ['GET', 'POST', 'PATCH', 'PUT', 'DELETE', 'OPTIONS'], ['req' => '.*'], -1)]
    #[RouteVersion(introduced: '50.0.0')]
    public function defaultRoute(RequestInterface $request): Response
    {
        return new Response(200, [], __FUNCTION__);
    }

    #[Route('/version500', ['GET'])]
    #[RouteVersion(introduced: '50.0.0')]
    public function testVersion500(RequestInterface $request): Response
    {
        return new Response(200, [], __FUNCTION__);
    }

    #[Route('/version501', ['GET'])]
    #[RouteVersion(introduced: '50.1.0')]
    public function testVersion501(RequestInterface $request): Response
    {
        return new Response(200, [], __FUNCTION__);
    }

    #[Route('/version510', ['GET'])]
    #[RouteVersion(introduced: '51.0.0')]
    public function testVersion510(RequestInterface $request): Response
    {
        return new Response(200, [], __FUNCTION__);
    }
}

// @codingStandardsIgnoreStart
class FullyDeprecatedMajorRouter extends Router
{
    // @codingStandardsIgnoreEnd
    public static function getAPIVersions(): array
    {
        global $CFG_GLPI;

        $versions = parent::getAPIVersions();

        // Declared out of order on purpose to check the sorting.
        $versions[] = [
            'api_version' => '61',
            'version' => '61.0.0',
            'endpoint' => $CFG_GLPI['url_base'] . '/api.php/v61',
        ];
        $versions[] = [
            'api_version' => '60',
            'version' => '60.0.0',
            'endpoint' => $CFG_GLPI['url_base'] . '/api.php/v60',
            'deprecated' => true,
        ];
        $versions[] = [
            'api_version' => '60',
            'version' => '60.1.0',
            'endpoint' => $CFG_GLPI['url_base'] . '/api.php/v60.1',
            'deprecated' => true,
        ];

        return $versions;
    }
}
