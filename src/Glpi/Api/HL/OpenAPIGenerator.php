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

namespace Glpi\Api\HL;

use Glpi\Api\HL\Controller\ProjectController;
use Glpi\Api\HL\Doc\Response;
use Glpi\Api\HL\Doc\Schema;
use Glpi\Api\HL\Doc\SchemaReference;
use Glpi\OAuth\Server;

/**
 * @phpstan-type OpenAPIInfo array{title: string, version: string, license: array{name: string, url: string}}
 * @phpstan-type SecuritySchemaComponent array{type: string, schema?: string, name?: string, in?: string}
 * @phpstan-type ResponseSchema array{description: string}
 * @phpstan-type SchemaArray array{
 *      type: string,
 *      format?: string,
 *      pattern?: string,
 *      properties?: array<string, array{type: string, format?: string}>
 *  }
 * @phpstan-type PathParameterSchema array{
 *      name: string,
 *      in: string,
 *      description: string,
 *      required: true|false,
 *      schema?: mixed
 * }
 * @phpstan-type PathSchema array{
 *      tags: string[],
 *      responses: array<string|int, ResponseSchema>,
 *      description?: string,
 *      parameters: PathParameterSchema[],
 *      requestBody?: RequestBodySchema,
 * }
 * @phpstan-type RequestBodySchema array{content: array{"application/json": array{schema: SchemaArray}}}
 */
final class OpenAPIGenerator
{
    public const OPENAPI_VERSION = '3.0.0';

    private Router $router;

    private string $api_version;

    public function __construct(Router $router, string $api_version)
    {
        $this->router = $router;
        $this->api_version = $api_version;
    }

    private function getPublicVendorExtensions(): array
    {
        return ['x-writeonly', 'x-readonly', 'x-full-schema', 'x-introduced', 'x-deprecated', 'x-removed'];
    }

    private function cleanVendorExtensions(array $schema, ?string $parent_key = null): array
    {
        $to_keep = $this->getPublicVendorExtensions();
        // Recursively walk through every key of the schema
        foreach ($schema as $key => &$value) {
            // If the key is a vendor extension
            // If the key is not a public vendor extension
            if (str_starts_with($key, 'x-') && !in_array($key, $to_keep, true)) {
                // Remove the key from the schema
                unset($schema[$key]);
                continue;
            }
            if ($parent_key === 'properties') {
                if ($key === 'id') {
                    //Implicitly set the id property as read-only
                    $value['x-readonly'] = true;
                }
            }
            // If the value is an array
            if (is_array($value)) {
                // Clean the value
                $schema[$key] = $this->cleanVendorExtensions($value, $key);
            }
        }
        return $schema;
    }

    /**
     * @return array
     * @phpstan-return OpenAPIInfo
     */
    private function getInfo(): array
    {
        $description = <<<EOT
The High-Level REST API documentation shown here is dynamically generated from the core GLPI code and any enabled plugins.
If a plugin is not enabled, its routes will not be shown here.
EOT;

        return [
            'title' => 'GLPI High-Level REST API',
            'description' => $description,
            'version' => Router::API_VERSION,
            'license' => [
                'name' => 'GNU General Public License v3 or later',
                'url' => 'https://www.gnu.org/licenses/gpl-3.0.html',
            ],
        ];
    }

    public static function getComponentSchemas(string $api_version): array
    {
        static $schemas = null;

        if ($schemas === null) {
            $schemas = [];

            $controllers = Router::getInstance()->getControllers();
            foreach ($controllers as $controller) {
                $known_schemas = $controller::getKnownSchemas($api_version);
                $short_name = (new \ReflectionClass($controller))->getShortName();
                $controller_name = str_replace('Controller', '', $short_name);
                foreach ($known_schemas as $schema_name => $known_schema) {
                    // Ignore schemas starting with an underscore. They are only used internally.
                    if (str_starts_with($schema_name, '_')) {
                        continue;
                    }
                    $calculated_name = $schema_name;
                    if (isset($schemas[$schema_name])) {
                        // For now, set the new calculated name to the short name of the controller + the schema name
                        $calculated_name = $controller_name . ' - ' . $schema_name;
                        // Change the existing schema name to its own calculated name
                        $other_short_name = (new \ReflectionClass($schemas[$schema_name]['x-controller']))->getShortName();
                        $other_calculated_name = str_replace('Controller', '', $other_short_name) . ' - ' . $schema_name;
                        $schemas[$other_calculated_name] = $schemas[$schema_name];
                        unset($schemas[$schema_name]);
                    }
                    if (!isset($known_schema['description']) && isset($known_schema['x-itemtype'])) {
                        $itemtype = $known_schema['x-itemtype'];
                        $known_schema['description'] = method_exists($itemtype, 'getTypeName') ? $itemtype::getTypeName(1) : 'No description available';
                    }
                    $schemas[$calculated_name] = $known_schema;
                    $schemas[$calculated_name]['x-controller'] = $controller::class;
                    $schemas[$calculated_name]['x-schemaname'] = $schema_name;
                }
            }
        }

        return $schemas;
    }

    private function getComponentReference(string $name, string $controller): ?array
    {
        $components = self::getComponentSchemas($this->api_version);
        // Try matching by name and controller first
        $match = null;
        $is_ref_array = str_ends_with($name, '[]');
        if ($is_ref_array) {
            $name = substr($name, 0, -2);
        }
        if ($name === '{itemtype}') {
            // Placeholder that will be replaced after route paths are expanded
            $match = $name;
        }
        if ($match === null) {
            foreach ($components as $component_name => $component) {
                if ($component['x-controller'] === $controller && $component['x-schemaname'] === $name) {
                    $match = $component_name;
                    break;
                }
            }
        }
        // If no match was found, try matching by name only
        if ($match === null) {
            foreach ($components as $component_name => $component) {
                if ($component['x-schemaname'] === $name) {
                    $match = $component_name;
                    break;
                }
            }
        }
        if ($match === null) {
            return null;
        }
        if ($is_ref_array) {
            return [
                'type' => 'array',
                'items' => [
                    '$ref' => '#/components/schemas/' . $match,
                ],
            ];
        }
        return [
            '$ref' => '#/components/schemas/' . $match,
        ];
    }

    /**
     * @return array{openapi: string, info: OpenAPIInfo, servers: array<array{url: string, description: string}>, components: array{securitySchemes: array<string, SecuritySchemaComponent>}, paths: array<string, array<string, PathSchema>>}
     */
    public function getSchema(): array
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $component_schemas = self::getComponentSchemas($this->api_version);
        ksort($component_schemas);
        $schema = [
            'openapi' => self::OPENAPI_VERSION,
            'info' => $this->getInfo(),
            'servers' => [
                [
                    'url' => $CFG_GLPI['url_base'] . '/api.php',
                    'description' => 'GLPI High-Level REST API'
                ]
            ],
            'components' => [
                'securitySchemes' => $this->getSecuritySchemeComponents(),
                'schemas' => $component_schemas,
            ]
        ];

        $routes = $this->router->getAllRoutes();
        $paths = [];

        foreach ($routes as $route_path) {
            /** @noinspection SlowArrayOperationsInLoopInspection */
            $paths = array_merge_recursive($paths, $this->getPathSchemas($route_path));
        }

        $schema['paths'] = $this->expandGenericPaths($paths);

        // Clean vendor extensions
        if ($_SESSION['glpi_use_mode'] !== \Session::DEBUG_MODE) {
            $schema = $this->cleanVendorExtensions($schema);
        }

        return $schema;
    }

    private function replaceRefPlaceholdersInResponses(array $responses, array $placeholders, string $controller): array
    {
        $new_responses = $responses;
        foreach ($new_responses as $status => &$response) {
            if (!isset($response['content'])) {
                continue;
            }
            foreach ($response['content'] as $content_type => &$content) {
                foreach ($placeholders as $placeholder_name => $placeholder_value) {
                    if (isset($content['schema']['$ref']) && $content['schema']['$ref'] === '#/components/schemas/{' . $placeholder_name . '}') {
                        $new_schema = $this->getComponentReference($placeholder_value, $controller);
                        if ($new_schema !== null) {
                            $content['schema']['$ref'] = $new_schema['$ref'];
                        }
                    }
                    // Handle array types
                    if (isset($content['schema']['items']['$ref']) && $content['schema']['items']['$ref'] === '#/components/schemas/{' . $placeholder_name . '}') {
                        $new_schema = $this->getComponentReference($placeholder_value, $controller);
                        if ($new_schema !== null) {
                            $content['schema']['items']['$ref'] = $new_schema['$ref'];
                        }
                    }
                }
            }
            unset($content);
        }
        unset($response);
        return $new_responses;
    }

    private function replaceRefPlaceholdersInParameters(array $parameters, array $placeholders, string $controller): array
    {
        $new_parameters = $parameters;
        foreach ($new_parameters as &$parameter) {
            foreach ($placeholders as $placeholder_name => $placeholder_value) {
                if (isset($parameter['schema']['$ref']) && $parameter['schema']['$ref'] === '#/components/schemas/{' . $placeholder_name . '}') {
                    $new_schema = $this->getComponentReference($placeholder_value, $controller);
                    if ($new_schema !== null) {
                        $parameter['schema']['$ref'] = $new_schema['$ref'];
                    }
                }
                // Handle array types
                if (isset($parameter['schema']['items']['$ref']) && $parameter['schema']['items']['$ref'] === '#/components/schemas/{' . $placeholder_name . '}') {
                    $new_schema = $this->getComponentReference($placeholder_value, $controller);
                    if ($new_schema !== null) {
                        $parameter['schema']['items']['$ref'] = $new_schema['$ref'];
                    }
                }
            }
        }
        unset($parameter);
        return $new_parameters;
    }

    private function replaceRefPlaceholdersInRequestBody(array $request_body, array $placeholders, string $controller): array
    {
        $new_request_body = $request_body;
        if (!isset($new_request_body['content'])) {
            return $new_request_body;
        }
        foreach ($new_request_body['content'] as $content_type => &$content) {
            foreach ($placeholders as $placeholder_name => $placeholder_value) {
                if (isset($content['schema']['$ref']) && $content['schema']['$ref'] === '#/components/schemas/{' . $placeholder_name . '}') {
                    $new_schema = $this->getComponentReference($placeholder_value, $controller);
                    if ($new_schema !== null) {
                        $content['schema']['$ref'] = $new_schema['$ref'];
                    }
                }
                // Handle array types
                if (isset($content['schema']['items']['$ref']) && $content['schema']['items']['$ref'] === '#/components/schemas/{' . $placeholder_name . '}') {
                    $new_schema = $this->getComponentReference($placeholder_value, $controller);
                    if ($new_schema !== null) {
                        $content['schema']['items']['$ref'] = $new_schema['$ref'];
                    }
                }
            }
        }
        unset($content);
        return $new_request_body;
    }

    /**
     * Replace any generic paths like `/Assets/{itemtype}` with the actual paths for each itemtype as long as the parameter pattern(s) are explicit lists.
     * Example: "Computer|Monitor|NetworkEquipment".
     * @param array $paths
     * @return array
     */
    private function expandGenericPaths(array $paths): array
    {
        $expanded = [];
        foreach ($paths as $path_url => $path) {
            if (str_contains($path_url, 'Timeline')) {
                $t = '';
            }
            foreach ($path as $method => $route) {
                $new_urls = [];
                /** @var array $all_expansions All path expansions where the keys are the placeholder name and the values are arrays of possible replacements */
                $all_expansions = [];
                foreach ($route['parameters'] as $param_key => $param) {
                    if (isset($param['schema']['pattern']) && preg_match('/^[\w+|]+$/', $param['schema']['pattern'])) {
                        $all_expansions[$param['name']] = explode('|', $param['schema']['pattern']);
                    }
                }
                // enumerate all possible combinations of expansions (where keys are the placeholder name and the value is a single replacement) and generate a new URL and route for each
                $combinations = [];
                foreach ($all_expansions as $placeholder_name => $expansions) {
                    $new_combinations = [];
                    foreach ($expansions as $expansion) {
                        if (count($combinations) === 0) {
                            $new_combinations[] = [$placeholder_name => $expansion];
                        } else {
                            foreach ($combinations as $combination) {
                                $new_combinations[] = array_merge($combination, [$placeholder_name => $expansion]);
                            }
                        }
                    }
                    $combinations = $new_combinations;
                }

                foreach ($combinations as $combination) {
                    $new_url = $path_url;
                    $temp_expanded = $route;
                    foreach ($combination as $placeholder => $value) {
                        $new_url = str_replace("{{$placeholder}}", $value, $new_url);
                        $temp_expanded['responses'] = $this->replaceRefPlaceholdersInResponses(
                            $route['responses'],
                            [$placeholder => $value],
                            $route['x-controller']
                        );
                        $temp_expanded['parameters'] = $this->replaceRefPlaceholdersInParameters(
                            $route['parameters'],
                            [$placeholder => $value],
                            $route['x-controller']
                        );
                        if (isset($route['requestBody'])) {
                            $temp_expanded['requestBody'] = $this->replaceRefPlaceholdersInRequestBody(
                                $route['requestBody'],
                                [$placeholder => $value],
                                $route['x-controller']
                            );
                        }
                        // Remove the itemtype path parameter now that it is a static value
                        foreach ($temp_expanded['parameters'] as $param_key => $param) {
                            if ($param['name'] === $placeholder) {
                                unset($temp_expanded['parameters'][$param_key]);
                                break;
                            }
                        }
                    }
                    if (!isset($paths[$new_url][$method])) {
                        $expanded[$new_url][$method] = $temp_expanded;
                    }
                    $new_urls[] = $new_url;
                }

                if (count($new_urls)) {
                    foreach ($new_urls as $new_url) {
                        // fix parameter array indexing. should not be associative, but unsetting the path parameter causes a gap and breaks openapi.
                        $expanded[$new_url][$method]['parameters'] = array_values($expanded[$new_url][$method]['parameters']);
                    }
                } else {
                    $expanded[$path_url][$method] = $route;
                }
            }
        }
        return $expanded;
    }

    /**
     * @return array<string, SecuritySchemaComponent>
     */
    private function getSecuritySchemeComponents(): array
    {
        $scopes = Server::getAllowedScopes();
        $scope_descriptions = Server::getScopeDescriptions();
        $scopes = array_combine(array_keys($scopes), $scope_descriptions);

        return [
            'oauth' => [
                'type' => 'oauth2',
                'flows' => [
                    'authorizationCode' => [
                        'authorizationUrl' => '/api.php/authorize',
                        'tokenUrl' => '/api.php/token',
                        'refreshUrl' => '/api.php/token',
                        'scopes' => $scopes
                    ],
                    'password' => [
                        'tokenUrl' => '/api.php/token',
                        'scopes' => $scopes
                    ]
                ]
            ],
        ];
    }

    /**
     * @param Doc\Parameter $route_param
     * @return array
     * @phpstan-return SchemaArray
     */
    private function getRouteParamSchema(Doc\Parameter $route_param): array
    {
        return $route_param->getSchema()->toArray();
    }

    /**
     * @param RoutePath $route_path
     * @param string $route_method
     * @return RequestBodySchema|null
     */
    private function getRequestBodySchema(RoutePath $route_path, string $route_method): ?array
    {
        $route_doc = $route_path->getRouteDoc($route_method);
        if ($route_doc === null) {
            return null;
        }
        $request_params = array_filter($route_doc->getParameters(), static function (Doc\Parameter $param) {
            return $param->getLocation() === Doc\Parameter::LOCATION_BODY;
        });
        if (count($request_params) === 0) {
            return null;
        }
        $request_body = [
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [],
                    ]
                ]
            ]
        ];

        // If there is a parameter with the location of body and name of "_", it should be an object that represents the entire request body (or at least the base schema of it)
        $request_body_param = array_filter($request_params, static function (Doc\Parameter $param) {
            return $param->getName() === '_';
        });
        if (count($request_body_param) > 0) {
            $request_body_param = array_values($request_body_param)[0];
            if ($request_body_param->getSchema() instanceof SchemaReference) {
                $body_schema = $this->getComponentReference($request_body_param->getSchema()['ref'], $route_path->getController());
            } else {
                $body_schema = $request_body_param->getSchema()->toArray();
            }
            $request_body['content']['application/json']['schema'] = $body_schema;
        }

        foreach ($request_params as $route_param) {
            if ($route_param->getName() === '_') {
                continue;
            }
            $body_param = [
                'type' => $route_param->getSchema()->getType()
            ];
            if ($route_param->getSchema()->getFormat() !== null) {
                $body_param['format'] = $route_param->getSchema()->getFormat();
            }
            if (count($route_param->getSchema()->getProperties())) {
                $body_param['properties'] = $route_param->getSchema()->getProperties();
            }
            $request_body['content']['application/json']['schema']['properties'][$route_param->getName()] = $body_param;
        }
        return $request_body;
    }

    /**
     * @param Doc\Parameter $route_param
     * @return array
     * @phpstan-return PathParameterSchema
     */
    private function getPathParameterSchema(Doc\Parameter $route_param): array
    {
        $schema = $this->getRouteParamSchema($route_param);
        return [
            'name' => $route_param->getName(),
            'description' => $route_param->getDescription(),
            'in' => $route_param->getLocation(),
            'required' => $route_param->getRequired(),
            'schema' => $schema,
            'example' => $route_param->getExample(),
        ];
    }

    /**
     * @param RoutePath $route_path
     * @param string $route_method
     * @return array<string, array<string, mixed>>[]
     */
    private function getPathSecuritySchema(RoutePath $route_path, string $route_method): array
    {
        $schemas = [
            [
                'oauth' => []
            ]
        ];
        // Handle special Session case
        if ($route_path->getRouteSecurityLevel() !== Route::SECURITY_NONE) {
            $schemas = array_merge($schemas, [
                [
                    'sessionTokenAuth' => []
                ]
            ]);
        }

        return $schemas;
    }

    private function getPathResponseSchemas(RoutePath $route_path, string $method): array
    {
        $route_doc = $route_path->getRouteDoc($method);
        if ($route_doc === null) {
            return [];
        }
        $responses = $route_doc->getResponses();
        $response_schemas = [];
        foreach ($responses as $response) {
            if ($response->isReference()) {
                $resolved_schema = $this->getComponentReference($response->getSchema()['ref'], $route_path->getController());
            } else {
                $resolved_schema = $response->getSchema()->toArray();
            }
            $response_media_type = $response->getMediaType();
            $response_schema = [
                'description' => $response->getDescription(),
                'content' => [
                    $response_media_type => [
                        'schema' => $resolved_schema
                    ]
                ],
            ];
            if ($response_media_type === 'application/json') {
                // add csv and xml
                $response_schema['content']['text/csv'] = [
                    'schema' => $resolved_schema
                ];
                $response_schema['content']['application/xml'] = [
                    'schema' => $resolved_schema
                ];
            }
            $response_schemas[$response->getStatusCode()] = $response_schema;
        }
        return $response_schemas;
    }

    /**
     * @param RoutePath $route_path
     * @return array<string, array<string, PathSchema>>
     */
    private function getPathSchemas(RoutePath $route_path): array
    {
        $path_schemas = [];
        $route_methods = $route_path->getRouteMethods();

        foreach ($route_methods as $route_method) {
            $route_doc = $route_path->getRouteDoc($route_method);
            $method = strtolower($route_method);
            $response_schema = $this->getPathResponseSchemas($route_path, $route_method);
            $path_schema = [
                'tags' => $route_path->getRouteTags(),
                'responses' => $response_schema,
            ];

            $default_responses = [
                '200' => [
                    'description' => 'Success',
                    'methods' => ['GET', 'PATCH'] // Usually only GET and PATCH methods return 200
                ],
                '201' => [
                    'description' => 'Success (created)',
                    'methods' => ['POST'],
                    'headers' => [
                        'Location' => [
                            'description' => 'The URL of the newly created resource',
                            'schema' => [
                                'type' => 'string'
                            ]
                        ]
                    ],
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'id' => [
                                        'type' => 'integer',
                                        'format' => 'int64'
                                    ],
                                    'href' => [
                                        'type' => 'string'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                '204' => [
                    'description' => 'Success (no content)',
                    'methods' => ['DELETE']
                ],
                '400' => [
                    'description' => 'Bad request',
                ],
                '401' => [
                    'description' => 'Unauthorized',
                ],
                '403' => [
                    'description' => 'Forbidden',
                ],
                '404' => [
                    'description' => 'Not found',
                ],
                '500' => [
                    'description' => 'Internal server error',
                ],
            ];

            foreach ($default_responses as $code => $info) {
                if (isset($info['methods']) && !in_array(strtoupper($method), $info['methods'], true)) {
                    continue;
                }
                if (!isset($path_schema['responses'][$code])) {
                    $path_schema['responses'][$code] = [
                        'description' => $info['description'],
                    ];
                    if (isset($info['headers'])) {
                        $path_schema['responses'][$code]['headers'] = $info['headers'];
                    }
                    if (isset($info['content'])) {
                        $path_schema['responses'][$code]['content'] = $info['content'];
                    }
                } else {
                    $path_schema['responses'][$code]['produces'] = array_keys($response_schema[(int) $code]['content']);
                }
            }

            $request_body = $this->getRequestBodySchema($route_path, $route_method);

            if ($route_doc !== null) {
                $path_schema['description'] = $route_doc->getDescription();
            }

            $requirements = $route_path->getRouteRequirements();
            $path_schema['parameters'] = [];
            if ($route_doc !== null) {
                $route_params = $route_doc->getParameters();
                if (count($route_params) > 0) {
                    foreach ($route_params as $route_param) {
                        $location = $route_param->getLocation();
                        if ($location !== Doc\Parameter::LOCATION_BODY && $location !== Doc\Parameter::LOCATION_PATH) {
                            $path_schema['parameters'][$route_param->getName()] = $this->getPathParameterSchema($route_param);
                        }
                    }
                }
            }
            if (count($requirements)) {
                foreach ($requirements as $name => $requirement) {
                    if (!str_contains($route_path->getRoutePath(), '{' . $name . '}')) {
                        continue;
                    }
                    if (is_callable($requirement)) {
                        $values = $requirement();
                        $requirement = implode('|', $values);
                    }
                    if ($requirement === '\d+') {
                        $param = [
                            'name' => $name,
                            'in' => 'path',
                            'required' => 'true',
                            'schema' => [
                                'type' => 'integer',
                                'pattern' => $requirement
                            ]
                        ];
                    } else {
                        $param = [
                            'name' => $name,
                            'in' => 'path',
                            'required' => 'true',
                            'schema' => [
                                'type' => 'string',
                                'pattern' => $requirement
                            ]
                        ];
                    }

                    $existing = $path_schema['parameters'][$param['name']] ?? [];
                    $path_schema['parameters'][$param['name']] = [
                        'name' => $existing['name'] ?? $param['name'],
                        'description' => $existing['description'] ?? '',
                        'in' => $existing['in'] ?? $param['in'],
                        'required' => $existing['required'] ?? $param['required'],
                    ];
                    /** @var SchemaArray $combined_schema */
                    $combined_schema = $param['schema'];
                    if (!empty($existing['schema'])) {
                        $combined_schema = array_replace($existing['schema'], $param['schema']);
                    }
                    $path_schema['parameters'][$param['name']]['schema'] = $combined_schema;
                }
            }
            // Inject global headers
            if ($route_path->getRouteSecurityLevel() !== Route::SECURITY_NONE) {
                $path_schema['parameters']['GLPI-Entity'] = [
                    'name' => 'GLPI-Entity',
                    'in' => 'header',
                    'description' => 'The ID of the entity to use. If not specified, the default entity for the user is used.',
                    'schema' => ['type' => Schema::TYPE_INTEGER]
                ];
                $path_schema['parameters']['GLPI-Profile'] = [
                    'name' => 'GLPI-Profile',
                    'in' => 'header',
                    'description' => 'The ID of the profile to use. If not specified, the default profile for the user is used.',
                    'schema' => ['type' => Schema::TYPE_INTEGER]
                ];
                $path_schema['parameters']['GLPI-Entity-Recursive'] = [
                    'name' => 'GLPI-Entity-Recursive',
                    'in' => 'header',
                    'description' => '"true" if the entity access should include child entities. This is false by default.',
                    'schema' => [
                        'type' => Schema::TYPE_STRING,
                        'enum' => ['true', 'false']
                    ],
                ];
            }
            // Language can always be specified, even if it may not always be respected if no temporary session is started
            $path_schema['parameters']['Accept-Language'] = [
                'name' => 'Accept-Language',
                'in' => 'header',
                'description' => 'The language to use for the response. If not specified, the default language for the user is used.',
                'schema' => ['type' => Schema::TYPE_STRING],
                'examples' => [
                    'English_GB' => [
                        'value' => 'en_GB',
                        'summary' => 'English (United Kingdom)'
                    ],
                    'French_FR' => [
                        'value' => 'fr_FR',
                        'summary' => 'French (France)'
                    ],
                    'Portuguese_BR' => [
                        'value' => 'pt_BR',
                        'summary' => 'Portuguese (Brazil)'
                    ],
                ]
            ];

            if (strcasecmp($method, 'delete') && $request_body !== null) {
                $path_schema['requestBody'] = $request_body;
            }
            $path_schema['security'] = $this->getPathSecuritySchema($route_path, $route_method);
            $path_schema['parameters'] = array_values($path_schema['parameters'] ?? []);
            $path_schema['x-controller'] = $route_path->getController();
            $path_schemas[$method] = $path_schema;
        }
        return [
            $route_path->getRoutePath() => $path_schemas
        ];
    }
}
