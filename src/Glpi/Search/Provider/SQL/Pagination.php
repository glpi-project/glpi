<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace Glpi\Search\Provider\SQL;

use CommonDBTM;
use Glpi\DBAL\Parts\Select;
use Glpi\DBAL\QueryExpression;
use Glpi\Search\Provider\SQLProvider;
use Search;

use function Safe\preg_match;
use function Safe\preg_match_all;

/**
 * Pagination of SQL searches: page and count queries, and the deferred join that resolves the
 * page of ids before the display joins are applied.
 *
 * @internal
 */
final class Pagination
{
    /**
     * IDs of forcegroupby (1:N) fields used as a WHERE filter: their join must stay in the
     * minimal FROM (the WHERE references it), unlike display-only 1:N joins which are deferred.
     *
     * @param array<int|string, mixed> $criteria
     * @param array<int|string, mixed> $searchopt
     * @return list<int>
     */
    public static function collectFilterForcegroupbyIds(array $criteria, array $searchopt): array
    {
        $ids = [];
        foreach ($criteria as $criterion) {
            if (isset($criterion['criteria']) && is_array($criterion['criteria'])) {
                $ids = array_merge($ids, self::collectFilterForcegroupbyIds($criterion['criteria'], $searchopt));
                continue;
            }
            if (
                isset($criterion['field'])
                && !in_array($criterion['field'], ['all', 'view'], true)
                && !($criterion['meta'] ?? false)
                && isset($criterion['value'])
                && (string) $criterion['value'] !== ''
                && !empty($searchopt[$criterion['field']]['forcegroupby'])
            ) {
                $ids[] = (int) $criterion['field'];
            }
        }
        return array_values(array_unique($ids));
    }

    /**
     * Minimal FROM for the inner/COUNT queries: base + default join + 1:1 joins, deferring
     * display-only 1:N (forcegroupby) joins except those in $keep_forcegroupby_ids (WHERE-filtered).
     * Uses its own $already_link_tables so a kept filter join re-emits any `beforejoin` parent a
     * deferred join would otherwise have supplied. Not valid with meta criterion / all_search.
     *
     * @param array<int|string, mixed> $data
     * @param array<int|string, mixed> $searchopt
     * @param array<int, string>       $blacklist_tables
     * @param list<int>                $keep_forcegroupby_ids Forcegroupby field IDs filtered in WHERE.
     * @return array{query: string, params: array<int, mixed>}
     */
    public static function buildMinimalFrom(
        array $data,
        string $itemtable,
        array $searchopt,
        array $blacklist_tables,
        array $keep_forcegroupby_ids
    ): array {
        $already_link_tables = [$itemtable];

        $query  = SQLProvider::buildFrom($itemtable) . ' ';
        $default_ljoin = Search::addDefaultJoin($data['itemtype'], $itemtable, $already_link_tables);
        $query .= $default_ljoin->getQuery() . ' ';
        $params = $default_ljoin->getParams();

        foreach ($data['tocompute'] as $val) {
            if (
                !isset($searchopt[$val]['table'])
                || in_array($searchopt[$val]['table'], $blacklist_tables, true)
            ) {
                continue;
            }
            // Defer display-only 1:N joins; keep WHERE-filtered ones ((int) $val: mixed id types).
            if (!empty($searchopt[$val]['forcegroupby']) && !in_array((int) $val, $keep_forcegroupby_ids, true)) {
                continue;
            }
            $ljoin = Search::addLeftJoin(
                $data['itemtype'],
                $itemtable,
                $already_link_tables,
                $searchopt[$val]['table'],
                $searchopt[$val]['linkfield'],
                false,
                '',
                $searchopt[$val]['joinparams'] ?? [],
                $searchopt[$val]['field'] ?? '',
                ($searchopt[$val]['use_join_subquery'] ?? false)
            );
            $query  .= $ljoin->getQuery() . ' ';
            $params  = array_merge($params, $ljoin->getParams());
        }

        return ['query' => $query, 'params' => $params];
    }

    /**
     * Inner-subquery ORDER BY: bare main-table column per sort field + trailing `id` for stable
     * paging. The outer ORDER BY can't be reused (it uses the `ITEM_x` SELECT aliases, absent
     * here); canUseDeferredJoin() guarantees each sort field is a plain column, so this is valid.
     * The `id` follows the direction of the last sort field: a mixed `col DESC, id ASC` prevents
     * the engine from reading the page through the `col` index and makes it sort every row.
     *
     * @param array<int|string, mixed> $sort_fields
     * @param array<int|string, mixed> $searchopt
     */
    public static function buildInnerOrderBy(string $itemtable, array $sort_fields, array $searchopt): string
    {
        global $DB;

        $criteria = [];
        foreach ($sort_fields as $sort_field) {
            $ID = $sort_field['searchopt_id'];
            if (!isset($searchopt[$ID]['field'])) {
                continue;
            }
            $order      = (($sort_field['order'] ?? 'ASC') === 'ASC') ? 'ASC' : 'DESC';
            $criteria[] = $DB::quoteName("{$itemtable}.{$searchopt[$ID]['field']}") . ' ' . $order;
        }
        $criteria[] = $DB::quoteName("{$itemtable}.id") . ' ' . self::getTieBreakerOrder($sort_fields);

        return ' ORDER BY ' . implode(', ', $criteria);
    }

    /**
     * Direction of the `id` tie-breaker: the direction of the last sort field.
     *
     * @param array<int|string, mixed> $sort_fields
     */
    public static function getTieBreakerOrder(array $sort_fields): string
    {
        $last = end($sort_fields);
        return (is_array($last) && ($last['order'] ?? 'ASC') !== 'ASC') ? 'DESC' : 'ASC';
    }

    /**
     * Whether every join of the given FROM clause yields at most one row per item, i.e. is a
     * plain `LEFT JOIN` from a main-table column to the `id` of the joined table. Anything else
     * (default join, 1:N join, extra join condition) is considered as possibly multiplying rows.
     */
    private static function hasOnlyOneToOneJoins(string $itemtable, string $from): bool
    {
        $from_main = trim(SQLProvider::buildFrom($itemtable));
        $joins     = trim($from);
        if (!str_starts_with($joins, $from_main)) {
            return false;
        }
        $joins = ltrim(substr($joins, strlen($from_main)));

        while ($joins !== '') {
            if (
                !preg_match('/^LEFT JOIN `(\w+)`(?: AS `(\w+)`)? ON \(`(\w+)`\.`\w+` = `(\w+)`\.`id`\)\s*/', $joins, $matches)
                || $matches[3] !== $itemtable
                || $matches[4] !== ($matches[2] !== '' ? $matches[2] : $matches[1])
            ) {
                return false;
            }
            $joins = substr($joins, strlen($matches[0]));
        }
        return true;
    }

    /**
     * Inner subquery resolving the page of main-table IDs alone (no display 1:N joins), with
     * ORDER BY + LIMIT. It is grouped by id only when a join may multiply rows: MySQL does not
     * optimise the GROUP BY away and then sorts every matching row instead of reading the sort
     * index. getParams() order: minimal-FROM, then WHERE.
     *
     * @param array{query: string, params: array<int, mixed>} $minimal_from
     * @param array<int, mixed>                                $WHERE_VALUES
     */
    public static function buildDeferredInnerQuery(
        string $itemtable,
        array $minimal_from,
        string $WHERE,
        array $WHERE_VALUES,
        string $inner_order,
        string $LIMIT
    ): Select {
        $query = 'SELECT `' . $itemtable . '`.`id`'
            . $minimal_from['query']
            . $WHERE
            . (self::hasOnlyOneToOneJoins($itemtable, $minimal_from['query']) ? '' : ' GROUP BY `' . $itemtable . '`.`id`')
            . $inner_order
            . $LIMIT;

        return (new Select())
            ->setQuery($query)
            ->setParams(array_merge($minimal_from['params'], $WHERE_VALUES));
    }

    /**
     * COUNT(DISTINCT id) query giving the total row count for an active (SQL-limited) search.
     *
     * @param array{query: string, params: array<int, mixed>} $minimal_from
     * @param array<int, mixed>                                $WHERE_VALUES
     */
    public static function buildActiveCountQuery(
        string $itemtable,
        array $minimal_from,
        string $WHERE,
        array $WHERE_VALUES
    ): Select {
        $query = 'SELECT COUNT(DISTINCT `' . $itemtable . '`.`id`)'
            . $minimal_from['query']
            . $WHERE;

        return (new Select())
            ->setQuery($query)
            ->setParams(array_merge($minimal_from['params'], $WHERE_VALUES));
    }

    /**
     * Whether the search option is a column of a table linked by a foreign key of the main table
     * (standard join on the linked table id): such a join yields at most one row per item.
     *
     * @param class-string<CommonDBTM>  $itemtype
     * @param array<string, mixed>      $opt
     */
    public static function isLinkedTableSortOption(string $itemtype, array $opt, string $itemtable): bool
    {
        return isset($opt['table'], $opt['field'])
            && $opt['table'] !== $itemtable
            && !empty($opt['linkfield'])
            && !Search::isVirtualField($opt['linkfield'])
            && empty($opt['forcegroupby'])
            && empty($opt['usehaving'])
            && empty($opt['nosort'])
            && !isset($opt['computation'])
            && empty($opt['nosql'])
            && !isset($opt['joinparams']['beforejoin'])
            && ($opt['joinparams']['jointype'] ?? 'standard') === 'standard'
            // Plugins may define their own joins and order expressions
            && isPluginItemType($itemtype) === false
            && !str_starts_with($opt['table'], 'glpi_plugin_');
    }

    /**
     * Inner subquery of the deferred join when sorting on columns of linked tables: the sort
     * columns are selected (the outer ORDER BY relies on their `ITEM_x` aliases), then only the
     * ids are kept. Null when it cannot be built safely.
     *
     * @param class-string<CommonDBTM>                         $itemtype
     * @param array{query: string, params: array<int, mixed>} $minimal_from
     * @param array<int, mixed>                                $where_values
     * @param array<int|string, mixed>                         $sort_fields
     */
    public static function buildDeferredInnerQueryWithSortColumns(
        string $itemtype,
        string $itemtable,
        array $minimal_from,
        string $where,
        array $where_values,
        array $sort_fields,
        string $order,
        string $limit
    ): ?Select {
        // A derived table cannot contain duplicated column names, whereas some search options
        // select the same aliased column twice.
        $columns = ['id' => '`' . $itemtable . '`.`id` AS `id`'];
        foreach ($sort_fields as $sort_field) {
            $criteria = SQLProvider::getSelectCriteria($itemtype, (int) $sort_field['searchopt_id']);
            if (!is_array($criteria)) {
                return null;
            }
            foreach ($criteria as $criterion) {
                if ($criterion instanceof QueryExpression) {
                    if ($criterion->getParams() !== []) {
                        return null;
                    }
                    $criterion = $criterion->getValue();
                }
                $expression = (string) $criterion;
                if (!preg_match('/\sAS\s+`?([^`\s]+)`?$/i', $expression, $matches)) {
                    return null;
                }
                $columns[$matches[1]] ??= $expression;
            }
        }
        // Every table referenced by the sort must be joined in the minimal FROM.
        if (!self::minimalFromCoversWhere($minimal_from['query'], implode(' ', $columns) . $order)) {
            return null;
        }

        $query = 'SELECT `id` FROM ('
            . 'SELECT ' . implode(', ', $columns)
            . $minimal_from['query']
            . $where
            . (self::hasOnlyOneToOneJoins($itemtable, $minimal_from['query']) ? '' : ' GROUP BY `' . $itemtable . '`.`id`')
            . $order . ', `' . $itemtable . '`.`id` ' . self::getTieBreakerOrder($sort_fields)
            . $limit
            . ') AS `deferred_sorted`';

        return (new Select())
            ->setQuery($query)
            ->setParams(array_merge($minimal_from['params'], $where_values));
    }

    /**
     * Whether deferred-join pagination is safe: grouped, limited, ordered, non-union search with
     * no meta/`view`/`all` criterion, no HAVING, and every sort field a plain main-table column
     * or a column of a table linked by a foreign key of the main table (so the page can be
     * resolved and ordered from the minimal FROM alone). HAVING / 1:N-aggregate
     * sorts are excluded on purpose — the aggregate must be computed over the whole relation
     * anyway, so deferral buys little. Otherwise the normal query is emitted.
     *
     * @param array<int|string, mixed> $data
     * @param array<int|string, mixed> $sort_fields
     * @param array<int|string, mixed> $searchopt
     */
    public static function canUseDeferredJoin(
        array $data,
        string $itemtable,
        string $GROUPBY,
        string $LIMIT,
        string $ORDER,
        string $HAVING,
        array $sort_fields,
        array $searchopt
    ): bool {
        global $CFG_GLPI;

        if (isset($CFG_GLPI['union_search_type'][$data['itemtype']])) {
            return false;
        }
        if ($GROUPBY === '' || $LIMIT === '' || $ORDER === '' || $HAVING !== '') {
            return false;
        }
        if (!empty($data['search']['all_search'])) {
            return false;
        }
        if (count($data['search']['metacriteria'] ?? []) > 0) {
            return false;
        }
        if (self::hasMetaCriteria($data['search']['criteria'] ?? [])) {
            return false;
        }
        // `view`/`all` filters over every displayed column (incl. deferred 1:N joins).
        if (self::criteriaReferenceAllColumns($data['search']['criteria'] ?? [])) {
            return false;
        }

        foreach ($sort_fields as $sort_field) {
            $ID = $sort_field['searchopt_id'];
            if (!isset($searchopt[$ID]['table']) || !isset($searchopt[$ID]['field'])) {
                return false;
            }
            $opt = $searchopt[$ID];
            // Columns of a table linked by a foreign key of the main table are joined in the
            // minimal FROM (1:1 join), so the inner subquery can sort on them too.
            if (self::isLinkedTableSortOption($data['itemtype'], $opt, $itemtable)) {
                continue;
            }
            // Only plain main-table columns are orderable in the inner subquery.
            if (
                $opt['table'] !== $itemtable
                || !empty($opt['joinparams'])
                || !empty($opt['forcegroupby'])
                || !empty($opt['nosort'])
                || isset($opt['computation'])
            ) {
                return false;
            }
            // Skip if the sort has a computed ORDER expression (date_delay, IP, plugin, …): the
            // inner subquery can only order by the bare column. getOrderByCriteria() returns the
            // `ITEM_x` alias only for plain columns, so anything else disqualifies deferral.
            $order_exprs = SQLProvider::getOrderByCriteria($data['itemtype'], [$sort_field]);
            if (
                count($order_exprs) !== 1
                || !str_starts_with($order_exprs[0]->getValue(), '`ITEM_' . $data['itemtype'] . '_' . $ID . '`')
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether the criteria tree contains any meta criterion (recursively).
     *
     * @param array<int|string, mixed> $criteria
     */
    public static function hasMetaCriteria(array $criteria): bool
    {
        foreach ($criteria as $criterion) {
            if (isset($criterion['criteria']) && is_array($criterion['criteria'])) {
                if (self::hasMetaCriteria($criterion['criteria'])) {
                    return true;
                }
            } elseif ($criterion['meta'] ?? false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Whether the criteria tree has a `view`/`all` criterion with a non-empty value: it expands
     * the WHERE over every displayed column (incl. deferred 1:N joins), so we cannot optimise.
     *
     * @param array<int|string, mixed> $criteria
     */
    public static function criteriaReferenceAllColumns(array $criteria): bool
    {
        foreach ($criteria as $criterion) {
            if (isset($criterion['criteria']) && is_array($criterion['criteria'])) {
                if (self::criteriaReferenceAllColumns($criterion['criteria'])) {
                    return true;
                }
            } elseif (
                isset($criterion['field'])
                && in_array($criterion['field'], ['all', 'view'], true)
                && isset($criterion['value'])
                && (string) $criterion['value'] !== ''
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Whether every `alias`.`column` table reference in the WHERE clause has its alias present in
     * the given (minimal) FROM clause. Guards against a WHERE that pulls in a deferred 1:N join
     * via the itemtype visibility/default restriction. Errs on the safe side: a subquery-local
     * alias (rare in WHERE) yields false → the optimisation is skipped, never a broken query.
     */
    public static function minimalFromCoversWhere(string $minimal_from, string $where): bool
    {
        if (!preg_match_all('/`([a-z0-9_]+)`\s*\.\s*`/i', $where, $matches)) {
            return true;
        }
        foreach (array_unique($matches[1]) as $alias) {
            if (!str_contains($minimal_from, '`' . $alias . '`')) {
                return false;
            }
        }
        return true;
    }
}
