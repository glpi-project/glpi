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

use Glpi\Api\HL\OpenAPIGenerator;
use Glpi\Api\HL\Router;
use HLAPITestCase;

class OpenAPIGeneratorTest extends HLAPITestCase
{
    public function testExpandedEndpoints()
    {
        $this->login();
        // Some expanded paths to spot-check
        $to_check = [
            '/Assistance/Ticket',
            '/Assistance/Change',
            '/Assistance/Problem',
            '/Assistance/Ticket/{id}',
            '/Assets/Computer',
            '/Assets/Computer/{id}',
            '/Assets/Monitor/{id}',
        ];
        $generator = new OpenAPIGenerator(Router::getInstance(), Router::API_VERSION);
        $openapi = $generator->getSchema();

        foreach ($to_check as $path) {
            $this->assertArrayHasKey($path, $openapi['paths']);
        }

        // Check that the pre-expanded paths are not present
        $to_check = [
            '/Assistance/{itemtype}',
            '/Assistance/{itemtype}/{id}',
            '/Assets/{itemtype}',
        ];
        foreach ($to_check as $path) {
            $this->assertArrayNotHasKey($path, $openapi['paths']);
        }
    }

    /**
     * Endpoints that get expanded (for example /Assistance/{itemtype} where 'itemtype' is known to be Ticket, Change or Problem)
     * should not list the 'itemtype' parameter in the documentation.
     */
    public function testExpandedAttributesNoParameter()
    {
        $this->login();
        // Some expanded paths to spot-check
        $to_check = [
            ['path' => '/Assistance/Ticket', 'placeholder' => 'itemtype'],
            ['path' => '/Assistance/Change', 'placeholder' => 'itemtype'],
            ['path' => '/Assistance/Problem', 'placeholder' => 'itemtype'],
            ['path' => '/Assistance/Ticket/{id}', 'placeholder' => 'itemtype'],
            ['path' => '/Assistance/Change/{id}', 'placeholder' => 'itemtype'],
            ['path' => '/Assistance/Problem/{id}', 'placeholder' => 'itemtype'],
        ];

        $generator = new OpenAPIGenerator(Router::getInstance(), Router::API_VERSION);
        $openapi = $generator->getSchema();

        foreach ($to_check as $endpoint) {
            $this->assertEmpty(array_filter($openapi['paths'][$endpoint['path']]['get']['parameters'], static fn($v) => $v['name'] === $endpoint['placeholder']));
        }
    }

    private function diffSchemaPaths($snapshot, $schema)
    {
        // Compare OpenAPI route paths and return differences
        $differences = [];
        // ignore "/Assets/Custom' paths
        $snapshot_paths = array_filter($snapshot['paths'] ?? [], static fn($p) => !str_starts_with($p, '/Assets/Custom'), ARRAY_FILTER_USE_KEY);
        $schema_paths = array_filter($schema['paths'] ?? [], static fn($p) => !str_starts_with($p, '/Assets/Custom'), ARRAY_FILTER_USE_KEY);
        $common_paths = array_intersect(array_keys($snapshot_paths), array_keys($schema_paths));

        if (count($common_paths) < count($snapshot_paths) || count($common_paths) < count($schema_paths)) {
            $missing_in_schema = array_diff(array_keys($snapshot_paths), array_keys($schema_paths));
            $missing_in_snapshot = array_diff(array_keys($schema_paths), array_keys($snapshot_paths));
            if (!empty($missing_in_schema)) {
                $differences[] = 'Paths missing in schema: ' . implode(', ', $missing_in_schema);
            }
            if (!empty($missing_in_snapshot)) {
                $differences[] = 'Paths missing in snapshot: ' . implode(', ', $missing_in_snapshot);
            }
        }

        foreach ($common_paths as $path) {
            foreach (['get', 'post', 'put', 'delete', 'patch'] as $method) {
                $snapshot_method = $snapshot_paths[$path][$method] ?? null;
                $schema_method = $schema_paths[$path][$method] ?? null;
                if ($snapshot_method === null && $schema_method === null) {
                    continue;
                } elseif ($snapshot_method === null) {
                    $differences[] = "Method '$method' for path '$path' is missing in the snapshot";
                    continue;
                } elseif ($schema_method === null) {
                    $differences[] = "Method '$method' for path '$path' is missing in the schema";
                    continue;
                }
                unset($snapshot_method['description'], $schema_method['description'], $snapshot_method['tags'], $schema_method['tags']);
                if ($snapshot_method !== $schema_method) {
                    $differences[] = "Method '$method' for path '$path' differs between snapshot and schema";
                }
            }
        }
        return $differences;
    }

    private function diffSchemaProperties($snapshot_props, $schema_props, $parent_path = '')
    {
        $differences = [];
        $common_props = array_intersect(array_keys($snapshot_props), array_keys($schema_props));

        if (count($common_props) < count($snapshot_props) || count($common_props) < count($schema_props)) {
            $missing_in_schema = array_diff(array_keys($snapshot_props), array_keys($schema_props));
            $missing_in_snapshot = array_diff(array_keys($schema_props), array_keys($snapshot_props));
            if (!empty($missing_in_schema)) {
                $differences[] = 'Properties missing in schema at ' . $parent_path . ': ' . implode(', ', $missing_in_schema);
            }
            if (!empty($missing_in_snapshot)) {
                $differences[] = 'Properties missing in snapshot at ' . $parent_path . ': ' . implode(', ', $missing_in_snapshot);
            }
        }

        foreach ($common_props as $prop_name) {
            $snapshot_prop = $snapshot_props[$prop_name];
            $schema_prop = $schema_props[$prop_name];
            unset($snapshot_prop['description'], $schema_prop['description']);

            // Recursively compare nested properties
            if (isset($snapshot_prop['properties'], $schema_prop['properties'])) {
                $nested_diffs = $this->diffSchemaProperties(
                    $snapshot_prop['properties'],
                    $schema_prop['properties'],
                    $parent_path . $prop_name . '.'
                );
                $differences = array_merge($differences, $nested_diffs);
            } elseif (isset($snapshot_prop['items']['properties'], $schema_prop['items']['properties'])) {
                $nested_diffs = $this->diffSchemaProperties(
                    $snapshot_prop['items']['properties'],
                    $schema_prop['items']['properties'],
                    $parent_path . $prop_name . '[]' . '.'
                );
                $differences = array_merge($differences, $nested_diffs);
            } elseif ($snapshot_prop != $schema_prop) {
                $differences[] = "Property '$parent_path$prop_name' differs between snapshot and schema";
            }
        }

        return $differences;
    }

    private function diffComponentSchemas($snapshot, $schema)
    {
        // Compare OpenAPI component schemas and return differences
        $differences = [];
        // Ignore custom assets
        $snapshot_schemas = array_filter($snapshot['components']['schemas'] ?? [], static fn($k) => !str_starts_with($k, 'Custom'), ARRAY_FILTER_USE_KEY);
        $schema_schemas = array_filter($schema['components']['schemas'] ?? [], static fn($k) => !str_starts_with($k, 'Custom'), ARRAY_FILTER_USE_KEY);
        $common_schemas = array_intersect(array_keys($snapshot_schemas), array_keys($schema_schemas));

        if (count($common_schemas) < count($snapshot_schemas) || count($common_schemas) < count($schema_schemas)) {
            $missing_in_schema = array_diff(array_keys($snapshot_schemas), array_keys($schema_schemas));
            $missing_in_snapshot = array_diff(array_keys($schema_schemas), array_keys($snapshot_schemas));
            if (!empty($missing_in_schema)) {
                $differences[] = 'Component schemas missing in schema: ' . implode(', ', $missing_in_schema);
            }
            if (!empty($missing_in_snapshot)) {
                $differences[] = 'Component schemas missing in snapshot: ' . implode(', ', $missing_in_snapshot);
            }
        }

        foreach ($common_schemas as $schema_name) {
            $snapshot_schema = $snapshot_schemas[$schema_name];
            $schema_schema = $schema_schemas[$schema_name];
            unset($snapshot_schema['description'], $schema_schema['description']);

            // Compare properties recursively
            if (isset($snapshot_schema['properties'], $schema_schema['properties'])) {
                $prop_diffs = $this->diffSchemaProperties(
                    $snapshot_schema['properties'],
                    $schema_schema['properties'],
                    $schema_name . '.'
                );
                $differences = array_merge($differences, $prop_diffs);
            }
            unset($snapshot_schema['properties'], $schema_schema['properties']);
            if ($snapshot_schema !== $schema_schema) {
                $differences[] = "Component schema '$schema_name' differs between snapshot and schema";
            }
        }
        return $differences;
    }

    private function assertSchemaMatchesSnapshot(array $snapshot, array $schema)
    {
        $path_differences = $this->diffSchemaPaths($snapshot, $schema);
        $component_differences = $this->diffComponentSchemas($snapshot, $schema);

        if (!empty($path_differences) || !empty($component_differences)) {
            $version = $schema['info']['version'];
            $this->fail("Schema for v{$version} does not match snapshot:\n" . implode("\n", $path_differences + $component_differences));
        }
    }

    /**
     * Ensure schemas do not change unexpectedly for API versions
     * @return void
     */
    public function testSchemaSnapshot()
    {
        $this->login();

        $snapshot_dir = __DIR__ . '/../../../../fixtures/hlapi/snapshots';
        $this->assertDirectoryExists($snapshot_dir, "Snapshot directory does not exist: $snapshot_dir");

        $router = Router::getInstance();
        $api_versions = $router::getAPIVersions();
        // Only care about the initial minor versions (2.0.0 and 2.1.0 but not 2.1.1, etc)
        $initial_minor_versions = [];
        foreach ($api_versions as $version_info) {
            if ((int) $version_info['api_version'] === 1) {
                continue;
            }
            $version = $version_info['version'];
            if (preg_match('/\d+\.\d+\.0$/', $version)) {
                $initial_minor_versions[] = $version;
            }
        }

        foreach ($initial_minor_versions as $version) {
            $openapi_generator = new OpenAPIGenerator($router, $version);
            $schema = $openapi_generator->getSchema();
            $snapshot_file = $snapshot_dir . '/v' . str_replace('.', '_', $version) . '.json';
            $this->assertFileExists($snapshot_file, "Snapshot file does not exist for version $version: $snapshot_file");
            $expected_schema = json_decode(file_get_contents($snapshot_file), true);
            $this->assertSchemaMatchesSnapshot($expected_schema, $schema);
        }
    }
}
