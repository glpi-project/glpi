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

namespace Glpi\Api\HL;

use CommonDBTM;
use Glpi\Api\HL\Controller\AbstractController;
use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\RSQL\RSQLException;
use Glpi\Api\HL\Search\SearchContext;
use Glpi\Http\JSONResponse;
use Glpi\Http\Response;
use Glpi\Toolbox\ArrayPathAccessor;
use RuntimeException;
use Session;
use Throwable;

use function Safe\preg_match;

/**
 * Class contaning methods for accessing GLPI resources (items) from the HL API via schemas.
 */
final class ResourceAccessor
{
    /**
     * Get the related itemtype for the given schema.
     * @param array $schema
     */
    private static function getItemFromSchema(array $schema): CommonDBTM
    {
        $itemtype = $schema['x-itemtype'] ?? ($schema['x-table'] ? getItemTypeForTable($schema['x-table']) : null);
        if ($itemtype === null) {
            throw new RuntimeException('Schema has no x-table or x-itemtype');
        }
        if (!is_subclass_of($itemtype, CommonDBTM::class)) {
            throw new RuntimeException('Invalid itemtype');
        }
        return new $itemtype();
    }

    /**
     * Get the primary ID field given some other unique field.
     * @param array $schema The schema
     * @param string $field The unique field name
     * @param mixed $value The unique field value
     * @return int|null The ID or null if not found
     */
    public static function getIDForOtherUniqueFieldBySchema(array $schema, string $field, mixed $value): ?int
    {
        global $DB;

        if (!isset($schema['properties'][$field])) {
            throw new RuntimeException('Invalid primary key');
        }
        $prop = $schema['properties'][$field];
        $pk_sql_name = $prop['x-field'] ?? $field;
        $context = new SearchContext($schema, []);
        $iterator = $DB->request([
            'SELECT' => ['id'],
            'FROM' => $context->getSchemaTable(),
            'WHERE' => [
                $pk_sql_name => $value,
            ],
        ]);
        if (count($iterator) === 0) {
            return null;
        }
        return $iterator->current()['id'];
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
    public static function getInputParamsBySchema(array $schema, array $request_params): array
    {
        $params = [];
        $flattened_properties = Doc\Schema::flattenProperties($schema['properties']);
        $joins = Doc\Schema::getJoins($schema['properties']);
        $writable_props = array_filter($flattened_properties, static function ($v, $k) use ($joins) {
            $base_k = strstr($k, '.', true) ?: $k;
            $is_join = isset($joins[$base_k]);
            $is_dropdown_identifier = preg_match('/^(\w+)\.id$/', $k);
            return $is_dropdown_identifier || !$is_join;
        }, ARRAY_FILTER_USE_BOTH);
        foreach ($writable_props as $prop_name => $prop) {
            $base_prop_name = strstr($prop_name, '.', true) ?: $prop_name;
            $is_join = isset($joins[$base_prop_name]);
            $is_dropdown_identifier = $is_join && preg_match('/^(\w+)\.id$/', $prop_name);
            if ($is_dropdown_identifier) {
                // This is a dropdown identifier, we need to get the id from the request
                $prop_name = strstr($prop_name, '.', true);
                $prop = $schema['properties'][$prop_name];
            } else {
                if ($prop['readOnly'] ?? false) {
                    // Ignore properties marked as read-only
                    continue;
                }
            }

            // Field resolution priority: x-field -> x-join.fkey -> property name
            if (isset($prop['x-field'])) {
                $internal_name = $prop['x-field'];
            } elseif (isset($prop['x-join']['fkey'])) {
                $internal_name = $prop['x-join']['fkey'] ?? $prop_name;
            } else {
                $internal_name = $prop_name;
            }
            if (ArrayPathAccessor::hasElementByArrayPath($request_params, $prop_name)) {
                $params[$internal_name] = ArrayPathAccessor::getElementByArrayPath($request_params, $prop_name);
            }
        }
        return $params;
    }

    /**
     * Update an item of the given schema using the given request parameters.
     * @param array $schema The schema
     * @param array $request_attrs The request attributes
     * @param array $request_params The request parameters
     * @param string $field The unique field to match on. Defaults to ID. If different, the ID is resolved from the given other unique field.
     * The field must be present in the route path (request attributes).
     * @return Response
     * @see self::getIDForOtherUniqueFieldBySchema()
     */
    public static function updateBySchema(array $schema, array $request_attrs, array $request_params, string $field = 'id'): Response
    {
        $items_id = $field === 'id' ? $request_attrs['id'] : self::getIDForOtherUniqueFieldBySchema($schema, $field, $request_attrs[$field]);
        // Ignore entity updates. This needs to be done through the Transfer process
        // TODO This should probably be handled in a more generic way (support other fields that can be used during creation but not updates)
        if (array_key_exists('entity', $request_attrs)) {
            unset($request_attrs['entity']);
        }
        $input = self::getInputParamsBySchema($schema, $request_params);
        $input['id'] = $items_id;

        $item = self::getItemFromSchema($schema);
        if (!$item->can($items_id, UPDATE, $input)) {
            return AbstractController::getAccessDeniedErrorResponse();
        }
        $result = $item->update($input);

        if ($result === false) {
            return AbstractController::getCRUDErrorResponse(AbstractController::CRUD_ACTION_UPDATE);
        }
        // We should return the updated item but we NEVER return the GLPI item fields directly. Need to use special API methods.
        return self::getOneBySchema($schema, $request_attrs + ['id' => $items_id], $request_params);
    }

    /**
     * Create an item of the given schema using the given request parameters.
     * @param array $schema The schema
     * @param array $request_params The request parameters
     * @param array $get_route The GET route to use to get the created item. This should be an array containing the controller class and method.
     * @phpstan-param array{0: class-string<AbstractController>, 1: string} $get_route
     * @param array $extra_get_route_params Additional parameters needed to generate the GET route. This should only be needed for complex routes.
     *      This is used to re-map the parameters to the GET route.
     *      The array can contain an 'id' property which is the name of the parameter that the resulting ID is set to ('id' by default).
     *      The array may also contain a 'mapped' property which is an array of parameter names and static values.
     *      For example ['mapped' => ['subitem_type' => 'Followup']] would set the 'subitem_type' parameter to 'Followup'.
     * @return Response
     */
    public static function createBySchema(array $schema, array $request_params, array $get_route, array $extra_get_route_params = []): Response
    {
        if (!isset($request_params['entity']) && isset($_SESSION['glpiactive_entity'])) {
            $request_params['entity'] = $_SESSION['glpiactive_entity'];
        }
        $input = self::getInputParamsBySchema($schema, $request_params);

        $item = self::getItemFromSchema($schema);
        if (!$item->can($item->getID(), CREATE, $input)) {
            return AbstractController::getAccessDeniedErrorResponse();
        }
        $items_id = $item->add($input);
        [$controller, $method] = $get_route;

        $id_field = $extra_get_route_params['id'] ?? 'id';
        if ($items_id !== false) {
            $request_params[$id_field] = $items_id;
        }
        if (array_key_exists('mapped', $extra_get_route_params)) {
            foreach ($extra_get_route_params['mapped'] as $key => $value) {
                $request_params[$key] = $value;
            }
        }

        return AbstractController::getCRUDCreateResponse($items_id, $controller::getAPIPathForRouteFunction($controller, $method, $request_params));
    }

    /**
     * Search items using the given schema and request parameters.
     * Public entry point for {@link Search::getSearchResultsBySchema()} method.
     * @param array $schema
     * @param array $request_params
     * @return Response
     */
    public static function searchBySchema(array $schema, array $request_params): Response
    {
        $itemtype = $schema['x-itemtype'] ?? null;
        // No item-level checks done here. They are handled when generating the SQL using the x-rights-condtions schema property
        if (($itemtype !== null) && !$itemtype::canView()) {
            return AbstractController::getAccessDeniedErrorResponse();
        }
        if (isset($schema['x-subtypes'])) {
            // For this case, we need to filter out the schemas that the user doesn't have read rights on
            $schemas = $schema['x-subtypes'];
            $schemas = array_filter($schemas, static function ($v) {
                $itemtype = $v['itemtype'];
                if (class_exists($itemtype) && is_subclass_of($itemtype, CommonDBTM::class)) {
                    return $itemtype::canView();
                }
                return false;
            });
            $schema['x-subtypes'] = $schemas;
            if (empty($schema['x-subtypes'])) {
                // No right on any subtypes. Could be useful to return an access denied error here instead of an empty list
                return AbstractController::getAccessDeniedErrorResponse();
            }
        }
        try {
            $results = Search::getSearchResultsBySchema($schema, $request_params);
        } catch (RSQLException $e) {
            return new JSONResponse(AbstractController::getErrorResponseBody(AbstractController::ERROR_INVALID_PARAMETER, $e->getMessage(), $e->getDetails()), 400);
        } catch (APIException $e) {
            return new JSONResponse(AbstractController::getErrorResponseBody(AbstractController::ERROR_GENERIC, $e->getUserMessage(), $e->getDetails()), $e->getCode() ?: 400);
        } catch (Throwable $e) {
            $message = (new APIException())->getUserMessage();
            $detail = null;
            if ($_SESSION['glpi_use_mode'] === Session::DEBUG_MODE) {
                $detail = $e->getMessage();
            }
            return new JSONResponse(AbstractController::getErrorResponseBody(AbstractController::ERROR_GENERIC, $message, $detail), 500);
        }
        $has_more = $results['start'] + $results['limit'] < $results['total'];
        $end = max(0, ($results['start'] + $results['limit'] - 1));
        if ($end > $results['total']) {
            $end = $results['total'] - 1;
        }
        return new JSONResponse($results['results'], $has_more ? 206 : 200, [
            'Content-Range' => $results['start'] . '-' . $end . '/' . $results['total'],
        ]);
    }

    /**
     * Get a single item of the given schema, request data and unique field.
     * @param array $schema The schema
     * @param array $request_attrs The request attributes
     * @param array $request_params The request parameters
     * @param string $field The unique field to match on. Defaults to ID. If different, the ID is resolved from the given other unique field.
     * The field must be present in the route path (request attributes).
     * @return Response
     * @see self::getIDForOtherUniqueFieldBySchema()
     * @see ResourceAccessor::searchBySchema()
     */
    public static function getOneBySchema(array $schema, array $request_attrs, array $request_params, string $field = 'id'): Response
    {
        $itemtype = $schema['x-itemtype'] ?? null;
        // No item-level checks done here. They are handled when generating the SQL using the x-rights-condtions schema property
        if (($itemtype !== null) && !$itemtype::canView()) {
            return AbstractController::getAccessDeniedErrorResponse();
        }
        // Shortcut implementation using the search functionality with an injected RSQL filter and returning the first result.
        // This shouldn't have much if any unneeded overhead as the filter would be mapped to a SQL condition.
        $request_params['filter'] = $field . '==' . $request_attrs[$field];
        $request_params['limit'] = 1;
        unset($request_params['start']);
        try {
            $results = Search::getSearchResultsBySchema($schema, $request_params);
        } catch (RSQLException $e) {
            return new JSONResponse(AbstractController::getErrorResponseBody(AbstractController::ERROR_INVALID_PARAMETER, $e->getUserMessage()), 400);
        } catch (APIException $e) {
            return new JSONResponse(AbstractController::getErrorResponseBody(AbstractController::ERROR_GENERIC, $e->getUserMessage()), $e->getCode() ?: 400);
        } catch (Throwable $e) {
            $message = (new APIException())->getUserMessage();
            $detail = null;
            if ($_SESSION['glpi_use_mode'] === Session::DEBUG_MODE) {
                $detail = $e->getMessage();
            }
            return new JSONResponse(AbstractController::getErrorResponseBody(AbstractController::ERROR_GENERIC, $message, $detail), 500);
        }
        if (count($results['results']) === 0) {
            return AbstractController::getNotFoundErrorResponse();
        }
        return new JSONResponse($results['results'][0]);
    }

    /**
     * Delete an item of the given schema using the given request parameters.
     * @param array $schema The schema
     * @param array $request_attrs The request attributes
     * @param array $request_params The request parameters
     * @param string $field The unique field to match on. Defaults to ID. If different, the ID is resolved from the given other unique field.
     * The field must be present in the route path (request attributes).
     * @return Response
     * @see self::getIDForOtherUniqueFieldBySchema()
     */
    public static function deleteBySchema(array $schema, array $request_attrs, array $request_params, string $field = 'id'): Response
    {
        $items_id = $field === 'id' ? $request_attrs['id'] : self::getIDForOtherUniqueFieldBySchema($schema, $field, $request_attrs[$field]);
        $item = self::getItemFromSchema($schema);
        $force = $request_params['force'] ?? false;
        $input = ['id' => (int) $items_id];
        $purge = !$item->maybeDeleted() || $force;
        if (!$item->can($items_id, $purge ? PURGE : DELETE, $input)) {
            return AbstractController::getAccessDeniedErrorResponse();
        }
        $result = $item->delete($input, $purge);

        if ($result === false) {
            return AbstractController::getCRUDErrorResponse(AbstractController::CRUD_ACTION_DELETE);
        }
        return new JSONResponse(null, 204);
    }
}
