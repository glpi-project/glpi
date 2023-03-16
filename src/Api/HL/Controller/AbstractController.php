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

namespace Glpi\Api\HL\Controller;

use CommonDBTM;
use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\RoutePath;
use Glpi\Api\HL\Router;
use Glpi\Api\HL\RSQLInput;
use Glpi\Http\JSONResponse;
use Glpi\Http\Response;
use QueryExpression;
use QueryUnion;
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

    /**
     * @return array<string, Doc\Schema>
     */
    protected static function getRawKnownSchemas(): array
    {
        return [];
    }

    /**
     * @return array
     * @phpstan-return array<string, Doc\Schema>
     */
    final public static function getKnownSchemas(): array
    {
        $schemas = static::getRawKnownSchemas();
        // Allow plugins to inject or modify schemas
        $schemas = \Plugin::doHookFunction('redefine_api_schemas', [
            'controller' => static::class,
            'schemas' => $schemas,
        ])['schemas'];
        return $schemas;
    }

    protected function getKnownSchema(string $name): ?array
    {
        $schemas = static::getKnownSchemas();
        return array_change_key_case($schemas)[strtolower($name)] ?? null;
    }

    /**
     * @param class-string<CommonDBTM> $class
     * @param string|null $field
     * @return array
     */
    protected static function getDropdownTypeSchema(string $class, ?string $field = null): array
    {
        if ($field === null) {
            $field = $class::getForeignKeyField();
        }
        return [
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-field' => $field,
            'x-join' => [
                'table' => $class::getTable(),
                'fkey' => $field,
            ],
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'x-readonly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
            ]
        ];
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

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>[]
     */
    private function formatSearchResults(array $data): array
    {
        $results = [];
        foreach ($data['data']['rows'] as $row) {
            $result = [];
            // Print other toview items
            foreach ($data['data']['cols'] as $col) {
                $colkey = "{$col['itemtype']}_{$col['id']}";
                if (!isset($col['meta']) || !$col['meta']) {
                    $result[(string) $col['searchopt']['field']] = $row[$colkey][0]['name'];
                }
            }
            $results[] = $result;
        }
        return $results;
    }

    private function getSearchCriteria(array $schema, array $request_params): array
    {
        global $DB;

        $flattened_properties = Doc\Schema::flattenProperties($schema['properties']);
        $joins = Doc\Schema::getJoins($schema['properties']);

        $criteria = [
            'SELECT' => []
        ];

        foreach ($flattened_properties as $prop_name => $prop) {
            if ($prop['x-writeonly'] ?? false) {
                // Do not expose write-only fields
                continue;
            }
            // Handle selects
            $prop_params = $prop;
            // if prop is an array, set the params to the items
            if (array_key_exists('type', $prop_params) && $prop['type'] === Doc\Schema::TYPE_ARRAY) {
                $prop_params = $prop['items'];
            }
            if (array_key_exists('type', $prop_params) && $prop_params['type'] !== Doc\Schema::TYPE_OBJECT) {
                if (!isset($prop['x-mapper'])) {
                    // Do not select fields mapped after the results are retrieved
                    $sql_field = $prop_params['x-field'] ?? $prop_name;
                    if (!str_contains($sql_field, '.')) {
                        $sql_field = "_.$sql_field";
                    }
                    $criteria['SELECT'][] = new QueryExpression($DB::quoteName($sql_field) . ' AS ' . $DB::quoteValue($prop_name));
                }
            }
        }

        $fn_append_join = static function ($join_alias, $join, $parent_type = null) use (&$criteria, &$fn_append_join) {
            $join_type = ($join['type'] ?? 'LEFT') . ' JOIN';
            if (!isset($criteria[$join_type])) {
                $criteria[$join_type] = [];
            }
            $join_table = $join['table'] . ' AS ' . $join_alias;
            $join_parent = (isset($join['ref_join']) && $join['ref_join']) ? "{$join_alias}_ref" : '_';
            if (isset($join['ref_join'])) {
                $fn_append_join("{$join_alias}_ref", $join['ref_join'], $join['parent_type'] ?? $parent_type);
            }
            $criteria[$join_type][$join_table] = [
                'ON' => [
                    $join_alias => $join['field'] ?? 'id',
                    $join_parent => $join['fkey'],
                ],
            ];
            if (isset($join['condition'])) {
                $criteria[$join_type][$join_table]['ON'][] = ['AND' => $join['condition']];
            }
        };
        //TODO Need to handle translatable dropdowns
        foreach ($joins as $join_alias => $join) {
            $fn_append_join($join_alias, $join);
        }

        if (isset($request_params['filter']) && !empty($request_params['filter'])) {
            $rsql = new RSQLInput($request_params['filter']);
            $criteria['WHERE'] = $rsql->getSQLCriteria($schema);
        }

        return $criteria;
    }

    private function getSearchResultsBySchema(array $schema, array $request_params): array
    {
        global $DB;

        // Schema must be an object type
        if ($schema['type'] !== Doc\Schema::TYPE_OBJECT) {
            throw new \RuntimeException('Schema must be an object type');
        }
        // Schema must have a table "x-table" or an itemtype "x-itemtype"
        $tables = [];
        $tables_schemas = [];
        if (isset($schema['x-table'])) {
            $tables = [$schema['x-table']];
            $tables_schemas = [$schema['x-table'] => $schema];
        } else if (isset($schema['x-itemtype'])) {
            if (is_subclass_of($schema['x-itemtype'], CommonDBTM::class)) {
                $t = $schema['x-itemtype']::getTable();
                $tables[] = $t;
                $tables_schemas[$t] = $schema;
            } else {
                throw new \RuntimeException('Invalid itemtype');
            }
        } else if (isset($schema['x-subtypes'])) {
            foreach ($schema['x-subtypes'] as $subtype_info) {
                if (is_subclass_of($subtype_info['itemtype'], CommonDBTM::class)) {
                    $t = $subtype_info['itemtype']::getTable();
                    $tables[] = $t;
                    $tables_schemas[$t] = $subtype_info['schema_name'];
                } else {
                    throw new \RuntimeException('Invalid itemtype');
                }
            }
        } else {
            throw new \RuntimeException('Cannot search using a schema without an x-table or an x-itemtype');
        }

        $union_search_mode = count($tables) > 1;
        $criteria = [
            'SELECT' => [],
            'FROM' => $tables[0] . ' AS _',
        ];

        $criteria = array_merge_recursive($criteria, $this->getSearchCriteria($schema, $request_params));

        if ($union_search_mode) {
            $queries = [];
            foreach ($tables as $table) {
                $query = $criteria;
                // Remove join props from the select for now (complex to handle)
                $query['SELECT'] = array_filter($query['SELECT'], static function ($select) use ($DB) {
                    return str_contains($select, $DB::quoteName('_') . '.');
                });
                //Inject a field for the schema name as the first select
                $schema_name = $tables_schemas[$table];
                $itemtype_field = new QueryExpression($DB::quoteValue($schema_name) . ' AS ' . $DB::quoteValue('_itemtype'));
                array_unshift($query['SELECT'], $itemtype_field);

                $query['FROM'] = $table . ' AS _';
                $queries[] = $query;
            }
            $union = new QueryUnion($queries, false, '_');
            $criteria['SELECT'] = $queries[0]['SELECT'];
            // Remove the _itemtype alias select
            $criteria['SELECT'] = array_filter($criteria['SELECT'], static function ($select) use ($DB) {
                return !str_contains($select, ' AS ' . $DB::quoteValue('_itemtype'));
            });
            // Insert generic _itemtype select as first element
            array_unshift($criteria['SELECT'], '_itemtype');
            $criteria['FROM'] = $union;
            unset($criteria['LEFT JOIN'], $criteria['INNER JOIN'], $criteria['RIGHT JOIN'], $criteria['WHERE']);
        }

        if (isset($request_params['start']) && !empty($request_params['start'])) {
            $criteria['START'] = (int) $request_params['start'];
        }
        if (isset($request_params['limit']) && !empty($request_params['limit'])) {
            $criteria['LIMIT'] = (int) $request_params['limit'];
        } else {
            $criteria['LIMIT'] = $_SESSION['glpilist_limit'];
        }
        $iterator = $DB->request($criteria);

        // There may be multiple rows for the same id, if there are joins
        // All fields returned with a name containing a dot are considered to be from a join
        // Those joined fields will need expanded. If the join parent_type is an array ($joins[$parent_key]['parent_type, the joined field will be added to the array
        // FOr example, if two rows from the iterator with id=4 have "emails.id" and "emails.email", the $result[$id]['emails'] will be an array with two items with an id and email field

        $results = [];
        $record_ids = [];
        $joins = Doc\Schema::getJoins($schema['properties']);
        foreach ($iterator as $row) {
            if ($union_search_mode) {
                $record_id = $row['_itemtype'] . $row['id'];
            } else {
                $record_id = $row['id'];
            }

            if (!isset($record_ids[$record_id])) {
                $record_ids[$record_id] = 0;
            } else {
                $record_ids[$record_id]++;
            }
            if (!isset($results[$record_id])) {
                $results[$record_id] = [];
            }
            foreach ($row as $k => $v) {
                if (str_contains($k, '.')) {
                    $path = explode('.', $k);
                    $parent_key = $path[count($path) - 2];
                    $leaf_key = $path[count($path) - 1];
                    $parent_type = $joins[$parent_key]['parent_type'] ?? null;
                    if ($parent_type === Doc\Schema::TYPE_ARRAY) {
                        $results[$record_id][$parent_key][$record_ids[$record_id]][$leaf_key] = $v;
                    } else {
                        $results[$record_id][$parent_key][$leaf_key] = $v;
                    }
                } else {
                    $results[$record_id][$k] = $v;
                }
            }
        }

        $flattened_properties = Doc\Schema::flattenProperties($schema['properties']);
        $mapped_props = array_filter($flattened_properties, static function ($prop) {
            return isset($prop['x-mapper']);
        });
        foreach ($results as &$result) {
            // Handle mapped fields
            foreach ($mapped_props as $mapped_prop_name => $mapped_prop) {
                $mapped_from_path = explode('.', $mapped_prop['x-mapped-from']);
                $mapped_from = $result;
                foreach ($mapped_from_path as $path_part) {
                    if (isset($mapped_from[$path_part])) {
                        $mapped_from = $mapped_from[$path_part];
                    } else {
                        $mapped_from = null;
                        break;
                    }
                }
                $mapped_to_path = explode('.', $mapped_prop_name);
                // set the mapped value to the result of the x-mapper callable
                $current = &$result;
                foreach ($mapped_to_path as $path_part) {
                    if (!isset($current[$path_part])) {
                        $current[$path_part] = [];
                    }
                    $current = &$current[$path_part];
                }
                $current = $mapped_prop['x-mapper']($mapped_from);
            }
            $result = Doc\Schema::fromArray($schema)->castProperties($result);
        }
        unset($result);

        // Count the total number of results with the same criteria, but without the offset and limit
        $count_criteria = $criteria;
        unset($count_criteria['SELECT'], $count_criteria['START'], $count_criteria['LIMIT']);
        $count_criteria['COUNT'] = 'cpt';
        $count_iterator = $DB->request($count_criteria);
        $total_count = $count_iterator->current()['cpt'];

        return [
            'results' => array_values($results),
            'start' => $criteria['START'] ?? 0,
            'limit' => $criteria['LIMIT'] ?? $total_count,
            'total' => $total_count ?? 0,
        ];
    }

    protected function searchBySchema(array $schema, array $request_params): Response
    {
        $itemtype = $schema['x-itemtype'] ?? null;
        if (($itemtype !== null) && !$itemtype::canView()) {
            return self::getCRUDErrorResponse(self::CRUD_ACTION_LIST);
        }
        $results = $this->getSearchResultsBySchema($schema, $request_params);
        $has_more = $results['start'] + $results['limit'] < $results['total'];
        $end = max(0, ($results['start'] + $results['limit'] - 1));
        return new JSONResponse($results['results'], $has_more ? 206 : 200, [
            'Content-Range' => $results['start'] . '-' . $end . '/' . $results['total'],
        ]);
    }

    /**
     * Map the request parameters to the format required for the GLPI add/update methods.
     * Only top-level properties are mapped.
     * Nested properties which would represent relations are not supported.
     * Creating/updating relations should be done using the appropriate endpoints.
     * @param array $schema
     * @param array $request_params
     * @return array
     */
    protected function getInputParamsBySchema(array $schema, array $request_params): array
    {
        $params = [];
        $flattened_properties = Doc\Schema::flattenProperties($schema['properties']);
        //Get top level properties (do not contain "." in the key)
        $top_level_properties = array_filter($flattened_properties, static function ($k) {
            $is_dropdown_identifier = preg_match('/^(\w+)\.id$/', $k);
            return $is_dropdown_identifier || !str_contains($k, '.');
        }, ARRAY_FILTER_USE_KEY);
        foreach ($top_level_properties as $prop_name => $prop) {
            if (str_contains($prop_name, '.')) {
                // This is a dropdown identifier, we need to get the id from the request
                $prop_name = explode('.', $prop_name)[0];
                $prop = $schema['properties'][$prop_name];
            } else {
                if ($prop['x-readonly'] ?? false) {
                    // Ignore properties marked as read-only
                    continue;
                }
            }

            $internal_name = $prop['x-field'] ?? $prop_name;
            if (isset($request_params[$prop_name])) {
                $params[$internal_name] = $request_params[$prop_name];
            }
        }
        return $params;
    }

    /**
     * @param array $schema
     * @return class-string<CommonDBTM>
     */
    private function getItemtypeFromSchema(array $schema): string
    {
        $itemtype = $schema['x-itemtype'] ?? ($schema['x-table'] ? getItemTypeForTable($schema['x-table']) : null);
        if ($itemtype === null) {
            throw new \RuntimeException('Schema has no x-table or x-itemtype');
        }
        if (!is_subclass_of($itemtype, CommonDBTM::class)) {
            throw new \RuntimeException('Invalid itemtype');
        }
        return $itemtype;
    }

    private function getTableFromSchema(array $schema): string
    {
        $table = $schema['x-table'] ?? ($schema['x-itemtype'] ? getTableForItemType($schema['x-itemtype']) : null);
        if ($table === null) {
            throw new \RuntimeException('Schema has no x-table or x-itemtype');
        }
        return $table;
    }

    protected function createBySchema(array $schema, array $request_params, array|string $get_route): Response
    {
        $itemtype = $this->getItemtypeFromSchema($schema);
        $input = $this->getInputParamsBySchema($schema, $request_params);

        /** @var CommonDBTM $item */
        $item = new $itemtype();
        $items_id = $item->add($input);

        $controller = static::class;
        if (is_array($get_route)) {
            [$controller, $method] = $get_route;
        } else {
            $method = $get_route;
        }

        if ($items_id !== false) {
            // Ensure the ID parameter is set to get the correct GET route
            $request_params['id'] = $items_id;
        }

        //TODO Return a 202 response if one or more fields are not valid (don't exist in the schema at least or are read-only)
        return static::getCRUDCreateResponse($items_id, $this->getAPIPathForRouteFunction($controller, $method, $request_params));
    }

    private function getIDForOtherUniqueFieldBySchema(array $schema, string $field, mixed $value): ?int
    {
        global $DB;

        if (!isset($schema['properties'][$field])) {
            throw new \RuntimeException('Invalid primary key');
        }
        $prop = $schema['properties'][$field];
        $pk_sql_name = $prop['x-field'] ?? $field;
        $iterator = $DB->request([
            'SELECT' => ['id'],
            'FROM' => $this->getTableFromSchema($schema),
            'WHERE' => [
                $pk_sql_name => $value,
            ],
        ]);
        if (count($iterator) === 0) {
            return null;
        }
        return $iterator->current()['id'];
    }

    protected function updateBySchema(array $schema, array $request_attrs, array $request_params, string $field = 'id'): Response
    {
        $items_id = $field === 'id' ? $request_attrs['id'] : $this->getIDForOtherUniqueFieldBySchema($schema, $field, $request_attrs[$field]);
        $itemtype = $this->getItemtypeFromSchema($schema);
        // Ignore entity updates. This needs to be done through the Transfer process
        // TODO This should probably be handled in a more generic way (support other fields that can be used during creation but not updates)
        if (array_key_exists('entity', $request_attrs)) {
            unset($request_attrs['entity']);
        }
        $input = $this->getInputParamsBySchema($schema, $request_params);
        $input['id'] = $items_id;
        /** @var CommonDBTM $item */
        $item = new $itemtype();
        $result = $item->update($input);

        if ($result === false) {
            return static::getCRUDErrorResponse(self::CRUD_ACTION_UPDATE);
        }
        // We should return the updated item but we NEVER return the GLPI item fields directly. Need to use special API methods.
        //TODO Return a 202 response if one or more fields are not valid (don't exist in the schema at least or are read-only)
        return $this->getOneBySchema($schema, $request_attrs + ['id' => $items_id], $request_params);
    }

    protected function getOneBySchema(array $schema, array $request_attrs, array $request_params, string $field = 'id'): Response
    {
        // Shortcut implementation using the search functionality with an injected RSQL filter and returning the first result.
        // This shouldn't have much if any unneeded overhead as the filter would be mapped to a SQL condition.
        $request_params['filter'] = $field . '==' . $request_attrs[$field];
        $request_params['limit'] = 1;
        unset($request_params['start']);
        $results = $this->getSearchResultsBySchema($schema, $request_params);
        if (count($results['results']) === 0) {
            return self::getNotFoundErrorResponse();
        }
        return new JSONResponse($results['results'][0]);
    }

    protected function deleteBySchema(array $schema, array $request_attrs, array $request_params, string $field = 'id'): Response
    {
        $items_id = $field === 'id' ? $request_attrs['id'] : $this->getIDForOtherUniqueFieldBySchema($schema, $field, $request_attrs[$field]);
        $itemtype = $this->getItemtypeFromSchema($schema);
        /** @var CommonDBTM $item */
        $item = new $itemtype();
        $force = $request_params['force'] ?? false;
        $result = $item->delete(['id' => (int) $items_id], $force ? 1 : 0);

        if ($result === false) {
            return static::getCRUDErrorResponse(self::CRUD_ACTION_DELETE);
        }
        return new JSONResponse(null, 204);
    }

    protected function getAPIPathForRouteFunction(string $controller, string $function, array $params = [], bool $allow_invalid = false): string
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
