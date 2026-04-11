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

namespace Glpi\Api\HL\Search;

use Glpi\Api\HL\APIException;
use Glpi\Api\HL\Search;
use Glpi\DBAL\QueryExpression;
use Glpi\Toolbox\ArrayPathAccessor;

/**
 * Utility class to handle the generation of cursor tokens and conversion of tokens to criteria.
 */
final class CursorPagination
{
    /**
     * Generates a token that can be used for previous or next page retrieval based on the edge result (first or last) of the current and sort/order
     * @param 'previous'|'next' $type The type of token to generate, either 'next' or 'previous'.
     * The cursor type is saved in the token so the API knows how to handle results.
     * With a 'next' token the API retreives result normally, but with 'previous' tokens the API needs to reverse the the criteria to read backwards, then reverse the results back to the normal order.
     * @param array $edge_result The result from the edge (first or last) of the current page's results
     * @param array $sort_order The sort order used for the current search where the keys are the field names and the values are the sort direction ASC or DESC
     * @return string A base64 encoded JSON string token that can be used for pagination in subsequent requests
     */
    public static function generateCursorToken(string $type, $edge_result, array $sort_order): string
    {
        $cursor_params = [
            'type' => $type,
            'sort' => [],
        ];
        foreach ($sort_order as $field => $direction) {
            $field_path = str_replace(chr(0x1F), '.', $field);
            $edge_value = ArrayPathAccessor::getElementByArrayPath($edge_result, $field_path);
            $cursor_params['sort'][] = [
                'field' => $field,
                'direction' => $direction === 'DESC' ? 'DESC' : 'ASC',
                'value' => $edge_value,
            ];
        }
        return base64_encode(json_encode($cursor_params));
    }

    /**
     * @throws APIException
     * @throws \JsonException
     */
    public static function getCriteriaFromCursor(array $cursor_params, Search $search): array
    {
        $cursor_type = $cursor_params['type'] ?? 'next';

        $tuple_comparison = [];
        // Build the criteria to start after the specified values
        /** @var array{field: string, direction: string, value: mixed} $field */
        foreach ($cursor_params['sort'] as $field) {
            $field_name = $field['field'];
            $direction = strtoupper($field['direction']) === 'DESC' ? 'DESC' : 'ASC';
            $value = $field['value'];
            // Verify the property is valid
            if (!isset($search->getContext()->getFlattenedProperties()[$field_name])) {
                throw new APIException(
                    message: 'Invalid property for cursor-based pagination: ' . $field_name,
                    user_message: 'Invalid property for cursor-based pagination: ' . $field_name,
                    code: 400,
                );
            }
            $sql_field = $search->getSQLFieldForProperty($field_name);
            // Append the criteria as a tuple comparison (key 0 = left, key 1 = right)
            // Tuple comparison will always use >, so DESC fields should put the value on the left side.
            if ($direction === 'ASC') {
                $tuple_comparison[0][] = $search->getDBRead()::quoteName($sql_field);
                $tuple_comparison[1][] = $search->getDBRead()::quoteValue($value);
            } else {
                $tuple_comparison[0][] = $search->getDBRead()::quoteValue($value);
                $tuple_comparison[1][] = $search->getDBRead()::quoteName($sql_field);
            }
        }
        $operator = $cursor_type === 'previous' ? '<' : '>';
        if (!empty($tuple_comparison)) {
            return [new QueryExpression('(' . implode(',', $tuple_comparison[0]) . ') ' . $operator . ' (' . implode(',', $tuple_comparison[1]) . ')')];
        }
        return [];
    }
}
