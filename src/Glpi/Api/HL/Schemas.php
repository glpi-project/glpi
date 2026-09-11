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

namespace Glpi\Api\HL;

use CommonGLPI;
use Glpi\Api\HL\Controller\AbstractController;
use Glpi\Debug\Profiler;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionClass;

/**
 * Manages access to the pseudo-OpenAPI schemas used for the OpenAPI schemas and GraphQL Types.
 * Allow lazy loading of the schemas and caching them for performance.
 */
final class Schemas
{
    private string $api_version;

    /**
     * Array of hints to locate schemas for controllers. The key is the schema name, the value is the controller name.
     * @var array<string, string>
     */
    private array $controller_schema_hints = [];

    /**
     * @var array<string, array<string, mixed>> Cache of loaded schemas. The key is the schema name, the value is the schema array.
     */
    private array $schemas_cache = [];

    /**
     * @var array<string, self> Cache of Schemas instances by API version.
     */
    private static array $instance_cache = [];

    public function __construct(?string $api_version = null)
    {
        global $GLPI_CACHE;

        $this->api_version = Router::normalizeAPIVersion($api_version ?? Router::API_VERSION);

        try {
            // Load known hints
            $this->controller_schema_hints = $GLPI_CACHE->get('hlapi_controller_schema_hints_' . $this->api_version, []);
        } catch (InvalidArgumentException) {
            // No-op
        }
    }

    public static function getInstance(?string $api_version = null): self
    {
        $api_version = Router::normalizeAPIVersion($api_version ?? Router::API_VERSION);
        if (!isset(self::$instance_cache[$api_version])) {
            self::$instance_cache[$api_version] = new self($api_version);
        }
        return self::$instance_cache[$api_version];
    }

    public function loadAllSchemas(): void
    {
        $this->schemas_cache = [];
        $controllers = Router::getInstance()->getControllers();
        foreach ($controllers as $controller) {
            $this->loadSchemasFromController($controller);
        }
    }

    /**
     * Get all known schemas, loading them if necessary.
     * @param bool $force If true, force reloading of all schemas even if they are already cached.
     * @return array<string, array<string, mixed>> The array of all known schemas, keyed by schema name.
     */
    public function getAllSchemas(bool $force = false): array
    {
        if ($force || $this->schemas_cache === []) {
            $this->loadAllSchemas();
        }
        return $this->schemas_cache;
    }

    private function loadSchemasFromController(AbstractController $controller): void
    {
        Profiler::getInstance()->start('Schemas Retrieval for ' . $controller::class, Profiler::CATEGORY_HLAPI);
        $known_schemas = $controller::getKnownSchemas($this->api_version);
        $controller_rc = new ReflectionClass($controller);
        $controller_name = str_replace('Controller', '', $controller_rc->getShortName());
        foreach ($known_schemas as $schema_name => $known_schema) {
            // Add/update a hint to locate the schema for this controller
            $this->controller_schema_hints[$schema_name] = $controller_rc->getName();
            // Ignore schemas starting with an underscore. They are only used internally.
            if (str_starts_with($schema_name, '_')) {
                continue;
            }
            $calculated_name = $schema_name;
            if (isset($this->schemas_cache[$schema_name])) {
                //TODO throw exception and clean up cases in SchemaReference and OpenAPIGenerator where resolution of schema names is done by controller name + schema name.
                // does not affect any core schemas, but some plugins may have taken advantage of this behavior.
                //throw new LogicException('Duplicate schema name "' . $schema_name . '" found in controller ' . $controller::class . '. Schema names must be unique across all controllers.');
                // For now, set the new calculated name to the short name of the controller + the schema name
                $calculated_name = $controller_name . ' - ' . $schema_name;
                // Change the existing schema name to its own calculated name
                $other_short_name = (new ReflectionClass($this->schemas_cache[$schema_name]['x-controller']))->getShortName();
                $other_calculated_name = str_replace('Controller', '', $other_short_name) . ' - ' . $schema_name;
                $this->schemas_cache[$other_calculated_name] = $this->schemas_cache[$schema_name];
                unset($this->schemas_cache[$schema_name]);
            }
            if (!isset($known_schema['description']) && isset($known_schema['x-itemtype'])) {
                /** @var class-string<CommonGLPI> $itemtype */
                $itemtype = $known_schema['x-itemtype'];
                $known_schema['description'] = $itemtype::getTypeName(1);
            }

            // Add properties that have 'required' flags to a 'required' array on the nearest parent object
            // We add the 'required' on individual properties so that it works well with the API version filtering
            $fn_hoist_required_flags = static function (&$schema_part) use (&$fn_hoist_required_flags) {
                if (is_array($schema_part)) {
                    if (isset($schema_part['properties']) && is_array($schema_part['properties'])) {
                        $required_fields = [];
                        foreach ($schema_part['properties'] as $prop_name => &$prop_value) {
                            if (is_array($prop_value)) {
                                if (isset($prop_value['required']) && $prop_value['required'] === true) {
                                    $required_fields[] = $prop_name;
                                    unset($prop_value['required']);
                                }
                                // Recurse into the property value
                                $fn_hoist_required_flags($prop_value);
                            }
                        }
                        unset($prop_value);
                        if (count($required_fields) > 0) {
                            $schema_part['required'] = $required_fields;
                        }
                    }
                }
            };
            $fn_hoist_required_flags($known_schema);

            self::resolveCompositionAndDiscriminators($known_schema);

            $this->schemas_cache[$calculated_name] = $known_schema;
            $this->schemas_cache[$calculated_name]['x-controller'] = $controller::class;
            $this->schemas_cache[$calculated_name]['x-schemaname'] = $schema_name;
        }
        // Save the updated hints to the cache
        try {
            global $GLPI_CACHE;
            $GLPI_CACHE->set('hlapi_controller_schema_hints_' . $this->api_version, $this->controller_schema_hints);
        } catch (InvalidArgumentException) {
            // No-op
        }
        Profiler::getInstance()->stop('Schemas Retrieval for ' . $controller::class);
    }

    /**
     * Resolve schemas using composition/polymorphism (anyOf, oneOf, allOf).
     * Also resolves discriminator mappings.
     * @param array<string, mixed> $schema The schema (or sub-schema when called recursively) to resolve
     * @return void
     */
    private static function resolveCompositionAndDiscriminators(array &$schema): void
    {
        if (isset($schema['properties']) && is_array($schema['properties'])) {
            foreach ($schema['properties'] as &$property) {
                self::resolveCompositionAndDiscriminators($property);
            }
        } elseif (isset($schema['items']) && is_array($schema['items'])) {
            self::resolveCompositionAndDiscriminators($schema['items']);
        } else {
            if (isset($schema['anyOf']) && is_array($schema['anyOf'])) {
                $resolved_anyof = [];
                foreach ($schema['anyOf'] as $anyof_schema_name) {
                    $component_ref = [
                        '$ref' => '#/components/schemas/' . $anyof_schema_name,
                    ];
                    $resolved_anyof[] = $component_ref;
                }
                $schema['anyOf'] = $resolved_anyof;
            }
            if (isset($schema['discriminator']['mapping']) && is_array($schema['discriminator']['mapping'])) {
                $resolved_mapping = [];
                foreach ($schema['discriminator']['mapping'] as $mapping_key => $mapping_schema_name) {
                    $component_ref = [
                        '$ref' => '#/components/schemas/' . $mapping_schema_name,
                    ];
                    $resolved_mapping[$mapping_key] = $component_ref;
                }
                $schema['discriminator']['mapping'] = $resolved_mapping;
            }
        }
    }

    /**
     * @param string $schema_name
     * @return array<string, mixed>|null The schema array if found, or null if not found
     */
    public function getSchema(string $schema_name): ?array
    {
        if (!array_key_exists($schema_name, $this->schemas_cache)) {
            // Schema not found in the cache, see if we at least know which controller it belongs to
            if (array_key_exists($schema_name, $this->controller_schema_hints)) {
                $controller_name = $this->controller_schema_hints[$schema_name];
                $controller_class = Router::getInstance()->getRegisteredController($controller_name);
                if ($controller_class !== null) {
                    $this->loadSchemasFromController($controller_class);
                } else {
                    // We know the controller name but it is not registered, maybe the hints are outdated
                    $this->loadAllSchemas();
                }
            } else {
                // We don't know which controller it belongs to, load all schemas
                $this->loadAllSchemas();
            }
        }
        if (array_key_exists($schema_name, $this->schemas_cache)) {
            return $this->schemas_cache[$schema_name];
        }
        // Not in cache and was not found
        return null;
    }
}
