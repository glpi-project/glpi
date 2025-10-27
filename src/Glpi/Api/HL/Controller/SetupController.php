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

namespace Glpi\Api\HL\Controller;

use AuthLDAP;
use CommonDBTM;
use Config;
use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\Middleware\ResultFormatterMiddleware;
use Glpi\Api\HL\ResourceAccessor;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\RouteVersion;
use Glpi\Http\JSONResponse;
use Glpi\Http\Request;
use Glpi\Http\Response;

#[Route(path: '/Setup', tags: ['Setup'])]
final class SetupController extends AbstractController
{
    public static function getRawKnownSchemas(): array
    {
        global $DB;

        return [
            'LDAPDirectory' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => AuthLDAP::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'name' => ['type' => Doc\Schema::TYPE_STRING],
                    'host' => ['type' => Doc\Schema::TYPE_STRING],
                    'port' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'min' => 1,
                        'max' => 65535,
                    ],
                    'is_default' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                    'is_active' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                    'comment' => ['type' => Doc\Schema::TYPE_STRING],
                    'connection_filter' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'x-field' => 'condition',
                        'description' => 'LDAP filter to restrict the search for users',
                    ],
                    'basedn' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'The distinguished name in the directory from which the searches will be made',
                    ],
                    'use_bind' => [
                        'type' => Doc\Schema::TYPE_BOOLEAN,
                        'description' => 'Whether to bind to the directory with a specific user or anonymously',
                    ],
                    'rootdn' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'The distinguished name of the user to bind to the directory',
                    ],
                    'rootdn_password' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'writeOnly' => true,
                        'description' => 'The password of the user to bind to the directory',
                    ],
                    'login_field' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'The attribute corresponding to the login',
                    ],
                    'sync_field' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'The attribute to use to uniquely identify a user (usually objectguid or employeeuid)',
                    ],
                ],
            ],
            'Config' => [
                'x-version-introduced' => '2.1',
                'x-itemtype' => Config::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'context' => ['type' => Doc\Schema::TYPE_STRING],
                    'name' => ['type' => Doc\Schema::TYPE_STRING],
                    'value' => ['type' => Doc\Schema::TYPE_STRING],
                ],
                'x-rights-conditions' => [
                    'read' => static function () use ($DB) {
                        // Make a SQL request to get all config items so we can check which are undisclosed
                        // We are using safe IDs rather than undisclosed IDs to avoid issues with concurrent modifications
                        // We cannot reliably lock the table due to the fact that the DB connection here may differ from the one used to perform the actual read in the Search code
                        $disclosed_ids = [];

                        $it = $DB->request([
                            'SELECT' => ['id', 'context', 'name'],
                            'FROM'   => 'glpi_configs',
                        ]);
                        $test_configs = [];
                        foreach ($it as $row) {
                            $test_configs[] = $row + ['value' => 'dummy'];
                        }
                        foreach ($test_configs as $f) {
                            if (!self::isUndisclosedConfig($f['context'], $f['name'])) {
                                $disclosed_ids[] = $f['id'];
                            }
                        }
                        return ['WHERE' => ['_.id' => $disclosed_ids]];
                    },
                ],
            ],
        ];
    }

    /**
     * @param bool $types_only If true, only the type names are returned. If false, the type name => localized name pairs are returned.
     * @return array<class-string<CommonDBTM>, string>
     */
    public static function getSetupTypes(bool $types_only = true): array
    {
        static $types = null;

        if ($types === null) {
            $types = [
                'LDAPDirectory' => AuthLDAP::getTypeName(1),
                // Do not add Config here as it is handled specially
            ];
        }
        return $types_only ? array_keys($types) : $types;
    }

    #[Route(path: '/', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Get all available setup types',
        responses: [
            new Doc\Response(new Doc\Schema(
                type: Doc\Schema::TYPE_ARRAY,
                items: new Doc\Schema(
                    type: Doc\Schema::TYPE_OBJECT,
                    properties: [
                        'itemtype' => new Doc\Schema(Doc\Schema::TYPE_STRING),
                        'name' => new Doc\Schema(Doc\Schema::TYPE_STRING),
                        'href' => new Doc\Schema(Doc\Schema::TYPE_STRING),
                    ]
                )
            )),
        ]
    )]
    public function index(Request $request): Response
    {
        $setup_types = self::getSetupTypes(false);
        $setup_paths = [];
        foreach ($setup_types as $setup_type => $setup_name) {
            $setup_paths[] = [
                'itemtype'  => $setup_type,
                'name'      => $setup_name,
                'href'      => self::getAPIPathForRouteFunction(self::class, 'search', ['itemtype' => $setup_type]),
            ];
        }
        return new JSONResponse($setup_paths);
    }

    #[Route(path: '/{itemtype}', methods: ['GET'], requirements: [
        'itemtype' => [self::class, 'getSetupTypes'],
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(schema_name: '{itemtype}')]
    public function search(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::searchBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['GET'], requirements: [
        'itemtype' => [self::class, 'getSetupTypes'],
        'id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(schema_name: '{itemtype}')]
    public function getItem(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::getOneBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}', methods: ['POST'], requirements: [
        'itemtype' => [self::class, 'getSetupTypes'],
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\CreateRoute(schema_name: '{itemtype}')]
    public function createItem(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::createBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getParameters() + ['itemtype' => $itemtype], [self::class, 'getItem']);
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['PATCH'], requirements: [
        'itemtype' => [self::class, 'getSetupTypes'],
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\UpdateRoute(schema_name: '{itemtype}')]
    public function updateItem(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::updateBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['DELETE'], requirements: [
        'itemtype' => [self::class, 'getSetupTypes'],
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\DeleteRoute(schema_name: '{itemtype}')]
    public function deleteItem(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::deleteBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    private static function isUndisclosedConfig(string $context, string $name): bool
    {
        $f = ['context' => $context, 'name' => $name, 'value' => 'dummy'];
        Config::unsetUndisclosedFields($f);
        return !array_key_exists('value', $f);
    }

    #[Route(path: '/Config/{context}/{name}', methods: ['PATCH'], requirements: [
        'context' => '\w+',
        'name' => '\w+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.1')]
    #[Doc\UpdateRoute(schema_name: 'Config')]
    public function setConfigValue(Request $request): Response
    {
        // Skip using ResourceAccessor given the particularities of Config
        if (!Config::canUpdate()) {
            return AbstractController::getAccessDeniedErrorResponse();
        }
        $context = $request->getAttribute('context');
        $name = $request->getAttribute('name');
        $value = $request->getParameter('value');
        Config::setConfigurationValues($context, [$name => $value]);
        // Return the updated config
        if (self::isUndisclosedConfig($context, $name)) {
            // If the field is undisclosed, only return a 204 to indicate success without revealing the value
            return new JSONResponse(null, 204);
        }
        return new JSONResponse([
            'context' => $context,
            'name'    => $name,
            'value'   => Config::getConfigurationValue($context, $name),
        ]);
    }

    #[Route(path: '/Config', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.1')]
    #[Doc\SearchRoute(schema_name: 'Config')]
    public function searchConfigValues(Request $request): Response
    {
        return ResourceAccessor::searchBySchema($this->getKnownSchema('Config', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/Config/{context}', methods: ['GET'], requirements: [
        'context' => '\w+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.1')]
    #[Doc\SearchRoute(schema_name: 'Config')]
    public function searchConfigValuesByContext(Request $request): Response
    {
        $filters = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filters .= ';context==' . $request->getAttribute('context');
        $request->setParameter('filter', $filters);
        return ResourceAccessor::searchBySchema($this->getKnownSchema('Config', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/Config/{context}/{name}', methods: ['GET'], requirements: [
        'context' => '\w+',
        'name' => '\w+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.1')]
    #[Doc\GetRoute(schema_name: 'Config')]
    public function getConfigValue(Request $request): Response
    {
        // Skip using ResourceAccessor given the particularities of Config
        $context = $request->getAttribute('context');
        $name = $request->getAttribute('name');
        $config = new Config();
        if (!$config->getFromDBByCrit(['context' => $context, 'name'    => $name,])) {
            return AbstractController::getNotFoundErrorResponse();
        }
        if (self::isUndisclosedConfig($context, $name) || !$config->can($config->getID(), READ)) {
            return AbstractController::getAccessDeniedErrorResponse();
        }
        return new JSONResponse([
            'context' => $context,
            'name'    => $name,
            'value'   => Config::getConfigurationValue($context, $name),
        ]);
    }

    #[Route(path: '/Config/{context}/{name}', methods: ['DELETE'], requirements: [
        'context' => '\w+',
        'name' => '\w+',
    ])]
    #[RouteVersion(introduced: '2.1')]
    #[Doc\DeleteRoute(schema_name: 'Config')]
    public function deleteConfigValue(Request $request): Response
    {
        // Skip using ResourceAccessor given the particularities of Config
        if (!Config::canUpdate()) {
            return AbstractController::getAccessDeniedErrorResponse();
        }
        $context = $request->getAttribute('context');
        $name = $request->getAttribute('name');
        $config = new Config();
        if (!$config->getFromDBByCrit(['context' => $context, 'name' => $name])) {
            return AbstractController::getNotFoundErrorResponse();
        }
        Config::deleteConfigurationValues($context, [$name]);
        return new JSONResponse(null, 204);
    }
}
