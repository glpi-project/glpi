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

use DBmysql;
use Glpi\DBAL\QueryElementInterface;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryUnion;
use RuntimeException;

/**
 * Helper for polymorphic joins.
 *
 * A polymorphic join is an array join (x-join on the items of an array property) whose data comes from multiple tables.
 * Each source table is defined as a branch, keyed by the value of the discriminator property for the items coming from it:
 * <code>
 * 'x-join' => [
 *     'fkey' => 'id', // The field in the parent the branches reference
 *     'branches' => [
 *         'UserTeamMember' => [
 *             'table' => 'glpi_tickets_users',
 *             'field' => 'tickets_id', // The field in the branch table that references the parent
 *             'joins' => ['INNER JOIN' => ['glpi_users AS member' => ['ON' => ['member' => 'id', 'branch' => 'users_id']]]],
 *             'columns' => ['id' => 'branch.id', 'users_id' => 'branch.users_id', 'user_name' => 'member.name'],
 *             'properties' => ['_type', 'id', 'user'], // The item properties populated by this branch
 *         ],
 *     ],
 * ],
 * </code>
 * The branch table is always aliased as {@link self::BRANCH_TABLE_ALIAS}.
 * Each branch provides the value of the derived columns. Columns not provided by a branch are NULL.
 * Properties of the items are read from the derived column matching their x-field (or their name if there is no x-field), even for properties of nested objects.
 * A special {@link self::ORDER_COLUMN} column may be provided to sort the items of each parent.
 *
 * The data for these joins is never fetched as part of the main search query.
 * Instead, it is fetched for all parents at once when hydrating the results.
 * The union of the branches is only joined in the main search query if it is needed to filter or sort the results.
 */
final class PolymorphicJoin
{
    /** Alias of the branch table in each branch query */
    public const BRANCH_TABLE_ALIAS = 'branch';

    /** Derived column containing the ID of the parent */
    public const PARENT_ID_COLUMN = '_parent_id';

    /** Optional derived column used to sort the items of each parent */
    public const ORDER_COLUMN = '_order';

    /**
     * @param array<string, mixed> $join The join definition
     * @return bool True if the join definition is a polymorphic join
     */
    public static function isPolymorphicJoin(array $join): bool
    {
        return isset($join['branches']);
    }

    /**
     * @param array<string, mixed> $join The join definition
     * @return string The name of the discriminator property/column
     */
    public static function getDiscriminatorColumn(array $join): string
    {
        if (empty($join['discriminator'])) {
            throw new RuntimeException('Polymorphic joins require a discriminator property');
        }
        return $join['discriminator'];
    }

    /**
     * Get the name of all derived columns provided by at least one branch, in the order they are first defined.
     * @param array<string, mixed> $join The join definition
     * @return string[]
     */
    public static function getColumns(array $join): array
    {
        $columns = [];
        foreach ($join['branches'] as $branch) {
            foreach (array_keys($branch['columns']) as $column) {
                $column = (string) $column;
                if (!in_array($column, $columns, true)) {
                    $columns[] = $column;
                }
            }
        }
        return $columns;
    }

    /**
     * Build the union of all branches.
     * @param array<string, mixed> $join The join definition
     * @param array<int>|null $parent_ids If specified, only the data for these parents is included.
     * @param string|null $alias The alias of the union
     * @return QueryUnion
     */
    public static function build(array $join, ?array $parent_ids = null, ?string $alias = null): QueryUnion
    {
        $discriminator = self::getDiscriminatorColumn($join);
        $columns = self::getColumns($join);
        $queries = [];
        foreach ($join['branches'] as $discriminator_value => $branch) {
            $select = [
                new QueryExpression(DBmysql::quoteValue($discriminator_value), $discriminator),
                new QueryExpression(DBmysql::quoteName(self::BRANCH_TABLE_ALIAS . '.' . $branch['field']), self::PARENT_ID_COLUMN),
            ];
            foreach ($columns as $column) {
                $expression = $branch['columns'][$column] ?? null;
                if ($expression === null) {
                    $expression = 'NULL';
                } elseif (!$expression instanceof QueryElementInterface) {
                    $expression = DBmysql::quoteName($expression);
                }
                $select[] = new QueryExpression($expression, $column);
            }

            $criteria = [
                'SELECT' => $select,
                'FROM' => $branch['table'] . ' AS ' . self::BRANCH_TABLE_ALIAS,
            ];
            foreach (['LEFT JOIN', 'INNER JOIN', 'RIGHT JOIN'] as $join_type) {
                if (!empty($branch['joins'][$join_type])) {
                    $criteria[$join_type] = $branch['joins'][$join_type];
                }
            }
            if ($parent_ids !== null) {
                $criteria['WHERE'] = [
                    self::BRANCH_TABLE_ALIAS . '.' . $branch['field'] => $parent_ids === [] ? [0] : array_values($parent_ids),
                ];
            }
            $queries[] = $criteria;
        }

        return new QueryUnion($queries, false, $alias);
    }

    /**
     * Get the item property path for each derived column.
     * @param string $join_name The name of the join (path of the array property)
     * @param array<string, array<string, mixed>> $flattened_properties The flattened properties of the schema
     * @return array<string, string> Array where the keys are the derived column names and the values are the property paths relative to an item
     */
    public static function getPropertyPathsByColumn(string $join_name, array $flattened_properties): array
    {
        $paths = [];
        $prefix = $join_name . '.';
        foreach ($flattened_properties as $prop_name => $prop) {
            if (!str_starts_with($prop_name, $prefix)) {
                continue;
            }
            $relative_path = substr($prop_name, strlen($prefix));
            $leaf_name = str_contains($relative_path, '.') ? substr($relative_path, strrpos($relative_path, '.') + 1) : $relative_path;
            $paths[$prop['x-field'] ?? $leaf_name] = $relative_path;
        }
        return $paths;
    }
}
