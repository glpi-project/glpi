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

namespace Glpi\Api\HL\Controller;

use CommonDBTM;
use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\RoutePath;
use Glpi\Api\HL\Router;
use Glpi\Api\HL\RSQLInput;
use Glpi\Http\JSONResponse;
use Glpi\Http\Request;
use Glpi\Http\Response;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryUnion;
use Search;

/**
 * @phpstan-type AdditionalErrorMessage array{priority: string, message: string}
 * @phpstan-type ErrorResponseBody array{status: string, title: string, detail: string|null, additional_messages?: AdditionalErrorMessage[]}
 * @phpstan-type InvalidParameterInfo array{name: string, reason?: string}
 */
abstract class AbstractController
{
    public const ERROR_GENERIC = 'ERROR';
    public const ERROR_RIGHT_MISSING = 'ERROR_RIGHT_MISSING';
    public const ERROR_SESSION_TOKEN_INVALID = 'ERROR_SESSION_TOKEN_INVALID';
    public const ERROR_SESSION_TOKEN_MISSING = 'ERROR_SESSION_TOKEN_MISSING';
    public const ERROR_ITEM_NOT_FOUND = 'ERROR_ITEM_NOT_FOUND';
    public const ERROR_BAD_ARRAY = 'ERROR_BAD_ARRAY';
    public const ERROR_INVALID_PARAMETER = 'ERROR_INVALID_PARAMETER';
    public const ERROR_METHOD_NOT_ALLOWED = 'ERROR_METHOD_NOT_ALLOWED';
    public const ERROR_ALREADY_EXISTS = 'ERROR_ALREADY_EXISTS';

    public const CRUD_ACTION_CREATE = 'create';
    public const CRUD_ACTION_READ = 'read';
    public const CRUD_ACTION_UPDATE = 'update';
    public const CRUD_ACTION_DELETE = 'delete';
    public const CRUD_ACTION_PURGE = 'purge';
    public const CRUD_ACTION_RESTORE = 'restore';
    public const CRUD_ACTION_LIST = 'list';

    protected const PARAMETER_RSQL_FILTER = [
        'name' => 'filter',
        'description' => 'RSQL query string',
        'location' => 'query',
        'schema' => [
            'type' => Doc\Schema::TYPE_STRING,
        ]
    ];

    protected const PARAMETER_START = [
        'name' => 'start',
        'description' => 'The first item to return',
        'location' => 'query',
        'schema' => [
            'type' => Doc\Schema::TYPE_INTEGER,
            'format' => Doc\Schema::FORMAT_INTEGER_INT64,
            'minimum' => 0,
            'default' => 0,
        ]
    ];

    protected const PARAMETER_LIMIT = [
        'name' => 'limit',
        'description' => 'The maximum number of items to return',
        'location' => 'query',
        'schema' => [
            'type' => Doc\Schema::TYPE_INTEGER,
            'format' => Doc\Schema::FORMAT_INTEGER_INT64,
            'minimum' => 0,
            'default' => 100,
        ]
    ];

    protected const PARAMETER_SORT = [
        'name' => 'sort',
        'description' => 'One or more properties to sort by in the form of property:direction where property is the full property name in dot notation and direction is either asc or desc.
                          If no direction is provided, asc is assumed. Multiple sorts can be provided by separating them with a comma.',
        'location' => 'query',
        'schema' => [
            'type' => Doc\Schema::TYPE_STRING,
        ]
    ];

    /**
     * Get the requested API version from the request
     * @param Request $request
     * @return string
     */
    protected function getAPIVersion(Request $request): string
    {
        return $request->getHeaderLine('GLPI-API-Version') ?: Router::API_VERSION;
    }

    /**
     * @return array<string, Doc\Schema>
     */
    protected static function getRawKnownSchemas(): array
    {
        return [];
    }

    /**
     * Get all known schemas for this controller for the requested API version
     * @param ?string $api_version The API version or null if all versions should be returned
     * @return array
     * @phpstan-return array<string, Doc\Schema>
     */
    final public static function getKnownSchemas(?string $api_version): array
    {
        $schemas = static::getRawKnownSchemas();
        // Allow plugins to inject or modify schemas
        $schemas = \Plugin::doHookFunction('redefine_api_schemas', [
            'controller' => static::class,
            'schemas' => $schemas,
        ])['schemas'];

        if ($api_version !== null) {
            foreach ($schemas as $schema_name => &$schema) {
                if (str_starts_with($schema_name, '_')) {
                    continue;
                }
                $schema = Doc\Schema::filterSchemaByAPIVersion($schema, $api_version);
            }
        }
        // Remove any null schemas
        return array_filter($schemas);
    }

    /**
     * Get a schema by name and API version
     * @param string $name The name of the schema
     * @param string $api_version The API version
     * @return array|null
     */
    protected function getKnownSchema(string $name, string $api_version): ?array
    {
        $schemas = static::getKnownSchemas($api_version);
        return array_change_key_case($schemas)[strtolower($name)] ?? null;
    }

    /**
     * @param class-string<CommonDBTM> $class The class this schema represents. Used in the SQL join.
     * @param string|null $field The SQL field to use as a reference in the SQL join.
     * @param string $name_field The field that contains the name
     * @param string|null $full_schema The name of the schema that represents the full object
     * @return array The schema
     */
    public static function getDropdownTypeSchema(string $class, ?string $field = null, string $name_field = 'name', ?string $full_schema = null): array
    {
        if ($field === null) {
            $field = $class::getForeignKeyField();
        }
        $schema = [
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-field' => $field,
            'x-itemtype' => $class,
            'x-join' => [
                'table' => $class::getTable(), // The table to join
                'fkey' => $field, // The field in the main table to use as a reference
                'field' => 'id', // The field in the joined table the reference points to
            ],
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'x-readonly' => $class !== \Entity::class,
                ],
                $name_field => ['type' => Doc\Schema::TYPE_STRING],
            ]
        ];
        if ($full_schema !== null) {
            $schema['x-full-schema'] = $full_schema;
        }
        return $schema;
    }

    /**
     * @return int
     * @throws \RuntimeException if the user ID is not set in the current session
     */
    protected function getMyUserID(): int
    {
        $user_id = \Session::getLoginUserID();
        if (!is_int($user_id)) {
            throw new \RuntimeException('Invalid session');
        }
        return $user_id;
    }

    /**
     * @param string $status
     * @phpstan-param self::ERROR_* $status
     * @param string $title
     * @param ?string $detail
     * @param AdditionalErrorMessage[] $additionalMessages
     * @return array
     * @phpstan-return ErrorResponseBody
     */
    public static function getErrorResponseBody(string $status, string $title, ?string $detail = null, array $additionalMessages = []): array
    {
        $body = [
            'status' => $status,
            'title' => $title,
            'detail' => $detail,
        ];
        if (count($additionalMessages) > 0) {
            $body['additional_messages'] = $additionalMessages;
        }
        return $body;
    }

    /**
     * @param int|false $new_id
     * @param string $api_path
     * @return Response
     */
    public static function getCRUDCreateResponse(int|false $new_id, string $api_path): Response
    {
        if ($new_id === false) {
            return self::getCRUDErrorResponse(self::CRUD_ACTION_CREATE);
        }

        return self::getItemLinkResponse($new_id, $api_path);
    }

    /**
     * @param string $action
     * @phpstan-param self::CRUD_ACTION_* $action
     * @return Response
     */
    public static function getCRUDErrorResponse(string $action): Response
    {
        // Get any messages from the session that would usually be shown after the redirect
        /** @var array<int, string[]> $messages */
        $messages = $_SESSION['MESSAGE_AFTER_REDIRECT'] ?? [];
        $additional_messages = [];
        if (count($messages) > 0) {
            $get_priority_name = static function ($priority) {
                return match ($priority) {
                    0 => 'info',
                    1 => 'error',
                    2 => 'warning',
                    default => 'unknown',
                };
            };
            foreach ($messages as $priority => $message_texts) {
                foreach ($message_texts as $message) {
                    $additional_messages[] = [
                        'priority' => $get_priority_name($priority),
                        'message' => $message
                    ];
                }
            }
        }

        return new JSONResponse(
            self::getErrorResponseBody(self::ERROR_GENERIC, "Failed to $action item(s)", null, $additional_messages),
            500
        );
    }

    /**
     * @return Response
     */
    public static function getNotFoundErrorResponse(): Response
    {
        return new JSONResponse(
            self::getErrorResponseBody(self::ERROR_ITEM_NOT_FOUND, 'Not found'),
            404
        );
    }

    /**
     * @param array{missing?: array<string>, invalid?: InvalidParameterInfo[]} $errors
     * @param array<string, mixed> $headers
     * @return Response
     */
    public static function getInvalidParametersErrorResponse(array $errors = [], array $headers = []): Response
    {
        $additional_messages = [];
        if (isset($errors['missing'])) {
            foreach ($errors['missing'] as $missing_parameter) {
                $additional_messages[] = [
                    'priority' => 'error',
                    'message' => 'Missing parameter: ' . $missing_parameter,
                ];
            }
        }
        if (isset($errors['invalid'])) {
            foreach ($errors['invalid'] as $invalid_info) {
                $msg = [
                    'priority' => 'error',
                    'message' => 'Invalid parameter: ' . $invalid_info['name']
                ];
                if (isset($invalid_info['reason'])) {
                    $msg['message'] .= '. ' . $invalid_info['reason'];
                }
                $additional_messages[] = $msg;
            }
        }
        return new JSONResponse(
            self::getErrorResponseBody(self::ERROR_INVALID_PARAMETER, 'One or more parameters are invalid', null, $additional_messages),
            400,
            $headers
        );
    }

    /**
     * @return Response
     */
    public static function getAccessDeniedErrorResponse(): Response
    {
        return new JSONResponse(
            self::getErrorResponseBody(self::ERROR_RIGHT_MISSING, "You don't have permission to perform this action."),
            403
        );
    }

    /**
     * @param int $id
     * @param string $api_path
     * @param int $status
     * @return Response
     */
    public static function getItemLinkResponse(int $id, string $api_path, int $status = 200): Response
    {
        return new JSONResponse([
            'id' => $id,
            'href' => $api_path
        ], $status, ['Location' => $api_path]);
    }

    public static function getAPIPathForRouteFunction(string $controller, string $function, array $params = [], bool $allow_invalid = false): string
    {
        $route_paths = Router::getInstance()->getAllRoutes();
        $matches = array_filter($route_paths, static function (/** @var RoutePath $route_path */$route_path) use ($controller, $function) {
            return $route_path->getController() === $controller && $route_path->getMethod()->getName() === $function;
        });
        if (count($matches) === 0) {
            return '/';
        }
        $match = reset($matches);
        $path = $match->getRoutePathWithParameters($params);
        if ($allow_invalid || $match->isValidPath($path)) {
            return $path;
        }
        return '/';
    }
}
