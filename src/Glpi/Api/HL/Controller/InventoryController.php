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

namespace Glpi\Api\HL\Controller;

use Agent;
use AgentType;
use Entity;
use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\Middleware\ResultFormatterMiddleware;
use Glpi\Api\HL\ResourceAccessor;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\RouteVersion;
use Glpi\Http\JSONResponse;
use Glpi\Http\Request;
use Glpi\Http\Response;
use SNMPCredential;

use function Safe\file_get_contents;

#[Route(path: '/Inventory', priority: 1, tags: ['Inventory'])]
final class InventoryController extends AbstractController
{
    protected static function getRawKnownSchemas(): array
    {
        return [
            'Agent' => [
                'x-version-introduced' => '2.3.0',
                'x-itemtype' => Agent::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'type' => self::getDropdownTypeSchema(class: AgentType::class, full_schema: 'AgentType') + ['readOnly' => true],
                    'deviceid' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255, 'readOnly' => true],
                    'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255, 'readOnly' => true],
                    'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity') + ['readOnly' => true],
                    'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'readOnly' => true],
                    'last_contact' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'format' => Doc\Schema::FORMAT_STRING_DATE_TIME,
                        'readOnly' => true,
                    ],
                    'version' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255, 'readOnly' => true],
                    'locked' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                    'itemtype' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255, 'readOnly' => true],
                    'items_id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'useragent' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255, 'readOnly' => true],
                    'tag' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255, 'readOnly' => true],
                    'port' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                    'remote_address' => ['type' => Doc\Schema::TYPE_STRING, 'x-field' => 'remote_addr', 'maxLength' => 255, 'readOnly' => true],
                    'threads_network_discovery' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT32,
                        'x-field' => 'threads_networkdiscovery',
                        'minimum' => 1,
                        'maximum' => 128,
                        'default' => 1,
                    ],
                    'threads_network_inventory' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT32,
                        'x-field' => 'threads_networkinventory',
                        'minimum' => 1,
                        'maximum' => 128,
                        'default' => 1,
                    ],
                    'timeout_network_discovery' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT32,
                        'x-field' => 'timeout_networkdiscovery',
                        'description' => 'Timeout in seconds for network discovery operations. If 0, the value in the inventory configuration is used.',
                        'minimum' => 0,
                        'maximum' => 100,
                        'default' => 0,
                    ],
                    'timeout_network_inventory' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT32,
                        'x-field' => 'timeout_networkinventory',
                        'description' => 'Timeout in seconds for network inventory operations. If 0, the value in the inventory configuration is used.',
                        'minimum' => 0,
                        'maximum' => 100,
                        'default' => 0,
                    ],
                    'use_module_wake_on_lan' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'readOnly' => true],
                    'use_module_computer_inventory' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'readOnly' => true],
                    'use_module_esx_remote_inventory' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'readOnly' => true],
                    'use_module_remote_inventory' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'readOnly' => true],
                    'use_module_network_inventory' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'readOnly' => true],
                    'use_module_network_discovery' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'readOnly' => true],
                    'use_module_package_deployment' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'readOnly' => true],
                    'use_module_collect_data' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'readOnly' => true],
                ],
            ],
            'AgentType' => [
                'x-version-introduced' => '2.3.0',
                'x-itemtype' => AgentType::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                ],
            ],
            'SNMPCredential' => [
                'x-version-introduced' => '2.3.0',
                'x-itemtype' => SNMPCredential::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 64],
                    'snmp_version' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'x-field' => 'snmpversion',
                        'enum' => ['1', '2c', '3'],
                        'required' => true,
                    ],
                    'community' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                    'username' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                    'authentication' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'enum' => [1, 2, 3, 4, 5, 6],
                        'description' => <<<EOT
                        - 1: MD5
                        - 2: SHA
                        - 3: SHA224
                        - 4: SHA256
                        - 5: SHA384
                        - 6: SHA512
EOT,
                    ],
                    'auth_passphrase' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255, 'writeOnly' => true],
                    'encryption' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'enum' => [1, 2, 3, 4, 5, 6, 7],
                        'description' => <<<EOT
                        - 1: DES
                        - 2: AES
                        - 3: 3DES
                        - 4: AES192C
                        - 5: AES256C
                        - 6: CFB192-AES
                        - 7: CFB256-AES
EOT,
                    ],
                    'priv_passphrase' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255, 'writeOnly' => true],
                    'is_deleted' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                ],
            ],
        ];
    }

    #[Route(path: '/{itemtype}', methods: ['GET'], requirements: [
        'itemtype' => 'Agent|SNMPCredential',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\SearchRoute(schema_name: '{itemtype}')]
    public function search(Request $request): Response
    {
        return ResourceAccessor::searchBySchema(
            $this->getKnownSchema($request->getAttribute('itemtype'), $this->getAPIVersion($request)),
            $request->getParameters(),
        );
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['GET'], requirements: [
        'itemtype' => 'Agent|SNMPCredential',
        'id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\GetRoute(schema_name: '{itemtype}')]
    public function getItem(Request $request): Response
    {
        return ResourceAccessor::getOneBySchema(
            schema: $this->getKnownSchema($request->getAttribute('itemtype'), $this->getAPIVersion($request)),
            request_attrs: $request->getAttributes(),
            request_params: $request->getParameters(),
        );
    }

    #[Route(path: '/{itemtype}', methods: ['POST'], requirements: [
        'itemtype' => 'SNMPCredential',
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\CreateRoute(schema_name: '{itemtype}')]
    public function createItem(Request $request): Response
    {
        return ResourceAccessor::createBySchema(
            schema: $this->getKnownSchema($request->getAttribute('itemtype'), $this->getAPIVersion($request)),
            request_params: $request->getParameters(),
            get_route: [self::class, 'getItem'],
            extra_get_route_params: ['mapped' => ['itemtype' => $request->getAttribute('itemtype')]],
        );
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['PATCH'], requirements: [
        'itemtype' => 'Agent|SNMPCredential',
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\UpdateRoute(schema_name: '{itemtype}')]
    public function updateItem(Request $request): Response
    {
        return ResourceAccessor::updateBySchema(
            schema: $this->getKnownSchema($request->getAttribute('itemtype'), $this->getAPIVersion($request)),
            request_attrs: $request->getAttributes(),
            request_params: $request->getParameters(),
        );
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['DELETE'], requirements: [
        'itemtype' => 'Agent|SNMPCredential',
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\DeleteRoute(schema_name: '{itemtype}')]
    public function deleteItem(Request $request): Response
    {
        return ResourceAccessor::deleteBySchema(
            schema: $this->getKnownSchema($request->getAttribute('itemtype'), $this->getAPIVersion($request)),
            request_attrs: $request->getAttributes(),
            request_params: $request->getParameters(),
        );
    }

    #[Route(path: '/Agent/{id}/InventoryRequest', methods: ['POST'], requirements: [
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\Route(
        description: 'Request inventory from an agent',
        responses: [
            new Doc\Response(
                schema: new Doc\Schema(
                    type: Doc\Schema::TYPE_OBJECT,
                    properties: [
                        'answer' => new Doc\Schema(type: Doc\Schema::TYPE_STRING),
                    ],
                ),
                status_code: 200,
            ),
        ]
    )]
    public function requestInventory(Request $request): Response
    {
        $agent = new Agent();
        if (!$agent->getFromDB((int) $request->getAttributes()['id'])) {
            return self::getNotFoundErrorResponse();
        }
        if (!$agent->can($agent->getID(), READ)) {
            return self::getAccessDeniedErrorResponse();
        }
        $result = $agent->requestInventory();

        return new JSONResponse($result);
    }

    #[Route(path: '/Agent/{id}/StatusRequest', methods: ['POST'], requirements: [
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\Route(
        description: 'Request status from an agent',
        responses: [
            new Doc\Response(
                schema: new Doc\Schema(
                    type: Doc\Schema::TYPE_OBJECT,
                    properties: [
                        'answer' => new Doc\Schema(type: Doc\Schema::TYPE_STRING),
                    ],
                ),
                status_code: 200,
            ),
        ]
    )]
    public function requestStatus(Request $request): Response
    {
        $agent = new Agent();
        if (!$agent->getFromDB((int) $request->getAttributes()['id'])) {
            return self::getNotFoundErrorResponse();
        }
        if (!$agent->can($agent->getID(), READ)) {
            return self::getAccessDeniedErrorResponse();
        }
        $result = $agent->requestStatus();

        return new JSONResponse($result);
    }

    #[Route(path: '/Agent/{id}/InventoryFile', methods: ['GET'], requirements: [
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\Route(
        description: 'Get the last inventory file sent by the agent',
        responses: [
            new Doc\Response(
                schema: new Doc\Schema(type: Doc\Schema::TYPE_STRING, format: Doc\Schema::FORMAT_STRING_BINARY),
                media_type: 'application/octet-stream',
                status_code: 200,
            ),
        ]
    )]
    public function getLastInventoryFile(Request $request): Response
    {
        $agent = new Agent();
        if (!$agent->getFromDB((int) $request->getAttributes()['id'])) {
            return self::getNotFoundErrorResponse();
        }
        if (!$agent->can($agent->getID(), READ)) {
            return self::getAccessDeniedErrorResponse();
        }
        $item = $agent->getLinkedItem();
        if (!method_exists($item, 'getInventoryFileName')) {
            return self::getNotFoundErrorResponse();
        }
        $file_name = $item->getInventoryFileName();
        if (!$file_name || !file_exists($file_name)) {
            return self::getNotFoundErrorResponse();
        }
        $contents = file_get_contents($file_name);
        // get mime from extension
        $mime = match (pathinfo($file_name, PATHINFO_EXTENSION)) {
            'json' => 'application/json',
            'xml' => 'application/xml',
            default => 'application/octet-stream',
        };
        return new Response(200, [
            'Content-Type' => $mime,
        ], $contents);
    }
}
