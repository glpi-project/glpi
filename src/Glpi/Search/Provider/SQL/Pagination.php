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
use Glpi\DBAL\Parts\LeftJoin;
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
     * LIMIT clause, set here when an active search is paginated in the database.
     */
    private string $limit;

    /**
     * FROM clause of the page and count queries: main table, default join and the joins that
     * yield at most one row per item, see buildMinimalFrom().
     */
    private string $minimal_from = '';

    /**
     * @var array<int, mixed>
     */
    private array $minimal_from_values = [];

    /**
     * Whether every join of the minimal FROM yields at most one row per item.
     */
    private bool $minimal_from_is_one_to_one = true;

    /**
     * @param array<int|string, mixed> $data
     * @param array<int|string, mixed> $sort_fields
     * @param array<int|string, mixed> $searchopt
     * @param array<int, string>       $blacklist_tables
     */
    private function __construct(
        private readonly array $data,
        private readonly SearchQueryParts $parts,
        private readonly array $sort_fields,
        private readonly array $searchopt,
        private readonly array $blacklist_tables,
    ) {
        $this->limit = $parts->limit;
    }

    /**
     * Search query of a non-union search. An active search gets a SQL LIMIT plus a dedicated
     * COUNT query, so paging happens in the database instead of fetching every row; its page
     * of ids is resolved before the display joins when possible (deferred join). Otherwise the
     * full query is returned. Fills `$data['sql']['raw']`, `['count']` and `['has_sql_limit']`.
     *
     * @param array<int|string, mixed> $data
     * @param array<int|string, mixed> $sort_fields
     * @param array<int|string, mixed> $searchopt
     * @param array<int, string>       $blacklist_tables
     */
    public static function buildSearchQuery(
        array &$data,
        SearchQueryParts $parts,
        array $sort_fields,
        array $searchopt,
        array $blacklist_tables
    ): Select {
        return (new self($data, $parts, $sort_fields, $searchopt, $blacklist_tables))->build($data);
    }

    /**
     * @param array<int|string, mixed> $data
     */
    private function build(array &$data): Select
    {
        $parts = $this->parts;

        // The SQL LIMIT relies on the minimal FROM covering the WHERE, which holds only without
        // meta/all_search/`view`/`all`/HAVING; otherwise keep the legacy fetch-all behaviour.
        $supports_sql_limit = empty($this->data['search']['all_search'])
            && $parts->having === ''
            && count($this->data['search']['metacriteria'] ?? []) === 0
            && !self::hasMetaCriteria($this->data['search']['criteria'] ?? [])
            && !self::criteriaReferenceAllColumns($this->data['search']['criteria'] ?? []);

        $need_active_count = false;
        if (
            $supports_sql_limit
            && $this->limit === ''
            && !$this->data['search']['export_all']
            && (($this->data['search']['as_map'] ?? 0) != 1)
        ) {
            $this->limit = " LIMIT " . (int) $this->data['search']['start'] . ", " . (int) $this->data['search']['list_limit'];
            $data['sql']['has_sql_limit'] = true;
            $need_active_count = true;
        }

        $can_defer = $this->canUseDeferredJoin();

        // Minimal FROM shared by the active COUNT query and the deferred inner subquery.
        if ($can_defer || $need_active_count) {
            $this->buildMinimalFrom(self::collectFilterForcegroupbyIds($this->data['search']['criteria'], $this->searchopt));
            // The WHERE may reference a 1:N join via the itemtype visibility/default
            // restriction (not just user criteria, e.g. KnowbaseItem). If the minimal FROM
            // does not cover every alias the WHERE uses, fall back to the full query.
            if (!self::minimalFromCoversWhere($this->minimal_from, $parts->where)) {
                $can_defer         = false;
                $need_active_count = false;
                $this->limit       = '';
                $data['sql']['has_sql_limit'] = false;
            }
        }

        $data['sql']['raw'] = [
            'SELECT' => $parts->select,
            'FROM' => $parts->from,
            'WHERE' => $parts->where,
            'GROUPBY' => $parts->groupby,
            'HAVING' => $parts->having,
            'ORDER' => $parts->order,
            'LIMIT' => $this->limit,
        ];

        if ($need_active_count) {
            $data['sql']['count'][] = $this->buildActiveCountQuery();
        }

        $inner = null;
        if ($can_defer) {
            $sorts_on_linked_table = false;
            foreach ($this->sort_fields as $sort_field) {
                if ($this->isLinkedTableSortOption($this->searchopt[$sort_field['searchopt_id']])) {
                    $sorts_on_linked_table = true;
                    break;
                }
            }
            // Two-phase: inner subquery picks the page of ids (no display 1:N joins), outer
            // query attaches the display joins to just those ids.
            $inner = $sorts_on_linked_table
                ? $this->buildDeferredInnerQueryWithSortColumns()
                : $this->buildDeferredInnerQuery();
        }

        if ($inner !== null) {
            $query = $parts->select
                . " FROM `" . $parts->itemtable . "`"
                . " INNER JOIN (" . $inner->getQuery() . ") AS `deferred_page`"
                . " ON `" . $parts->itemtable . "`.`id` = `deferred_page`.`id` "
                . $parts->joins
                . $parts->groupby
                . $parts->order . ', `' . $parts->itemtable . '`.`id` ' . $this->getTieBreakerOrder();
            // Param order matches the `?` order: outer SELECT, inner subquery (its WHERE
            // included), then outer display joins — inner WHERE BEFORE the joins here.
            $values = array_merge($parts->select_values, $inner->getParams(), $parts->joins_values);
        } else {
            $query = $parts->select
                . $parts->from
                . $parts->where
                . $parts->groupby
                . $parts->having
                . $parts->order
                . $this->limit;
            $values = array_merge(
                $parts->select_values,
                $parts->from_values,
                $parts->where_values,
                $parts->having_values
            );
        }

        return (new Select())
            ->setQuery($query)
            ->setParams($values);
    }

    /**
     * IDs of forcegroupby (1:N) fields used as a WHERE filter: their join must stay in the
     * minimal FROM (the WHERE references it), unlike display-only 1:N joins which are deferred.
     *
     * @param array<int|string, mixed> $criteria
     * @param array<int|string, mixed> $searchopt
     * @return list<int>
     */
    private static function collectFilterForcegroupbyIds(array $criteria, array $searchopt): array
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
     * @param list<int> $keep_forcegroupby_ids Forcegroupby field IDs filtered in WHERE.
     */
    private function buildMinimalFrom(array $keep_forcegroupby_ids): void
    {
        $itemtable           = $this->parts->itemtable;
        $already_link_tables = [$itemtable];

        $joins = [SQLProvider::getDefaultJoinCriteria($this->data['itemtype'], $itemtable, $already_link_tables)];

        foreach ($this->data['tocompute'] as $val) {
            if (
                !isset($this->searchopt[$val]['table'])
                || in_array($this->searchopt[$val]['table'], $this->blacklist_tables, true)
            ) {
                continue;
            }
            // Defer display-only 1:N joins; keep WHERE-filtered ones ((int) $val: mixed id types).
            if (!empty($this->searchopt[$val]['forcegroupby']) && !in_array((int) $val, $keep_forcegroupby_ids, true)) {
                continue;
            }
            $joins[] = SQLProvider::getLeftJoinCriteria(
                $this->data['itemtype'],
                $itemtable,
                $already_link_tables,
                $this->searchopt[$val]['table'],
                $this->searchopt[$val]['linkfield'],
                false,
                '',
                $this->searchopt[$val]['joinparams'] ?? [],
                $this->searchopt[$val]['field'] ?? '',
                ($this->searchopt[$val]['use_join_subquery'] ?? false)
            );
        }

        $query  = SQLProvider::buildFrom($itemtable) . ' ';
        $params = [];
        foreach ($joins as $criteria) {
            $ljoin   = (new LeftJoin())->withCriteria($criteria);
            $query  .= $ljoin->getQuery() . ' ';
            $params  = array_merge($params, $ljoin->getParams());
            $this->minimal_from_is_one_to_one = $this->minimal_from_is_one_to_one
                && self::isOneToOneJoin($criteria, $itemtable);
        }

        $this->minimal_from        = $query;
        $this->minimal_from_values = $params;
    }

    /**
     * Inner-subquery ORDER BY: bare main-table column per sort field + trailing `id` for stable
     * paging. The outer ORDER BY can't be reused (it uses the `ITEM_x` SELECT aliases, absent
     * here); canUseDeferredJoin() guarantees each sort field is a plain column, so this is valid.
     * The `id` follows the direction of the last sort field: a mixed `col DESC, id ASC` prevents
     * the engine from reading the page through the `col` index and makes it sort every row.
     */
    private function buildInnerOrderBy(): string
    {
        global $DB;

        $itemtable = $this->parts->itemtable;
        $criteria  = [];
        foreach ($this->sort_fields as $sort_field) {
            $ID = $sort_field['searchopt_id'];
            if (!isset($this->searchopt[$ID]['field'])) {
                continue;
            }
            $order      = (($sort_field['order'] ?? 'ASC') === 'ASC') ? 'ASC' : 'DESC';
            $criteria[] = $DB::quoteName("{$itemtable}.{$this->searchopt[$ID]['field']}") . ' ' . $order;
        }
        $criteria[] = $DB::quoteName("{$itemtable}.id") . ' ' . $this->getTieBreakerOrder();

        return ' ORDER BY ' . implode(', ', $criteria);
    }

    /**
     * Direction of the `id` tie-breaker: the direction of the last sort field.
     */
    private function getTieBreakerOrder(): string
    {
        $sort_fields = $this->sort_fields;
        $last        = end($sort_fields);
        return (is_array($last) && ($last['order'] ?? 'ASC') !== 'ASC') ? 'DESC' : 'ASC';
    }

    /**
     * Whether the join criteria, as returned by SQLProvider::getLeftJoinCriteria(), yield at most
     * one row per item: only `LEFT JOIN`s whose sole condition is a column of the reference
     * table equal to the `id` of the joined table. Anything else (other join type, sub-query,
     * extra condition) is considered as possibly multiplying rows.
     *
     * @param array<mixed> $criteria
     */
    private static function isOneToOneJoin(array $criteria, string $ref_table): bool
    {
        foreach ($criteria as $type => $tables) {
            if ($type !== 'LEFT JOIN' || !is_array($tables)) {
                return false;
            }
            foreach ($tables as $table => $join) {
                if (!is_string($table) || !is_array($join) || array_keys($join) !== ['ON'] || !is_array($join['ON'])) {
                    return false;
                }
                // Table key: `table` or `table AS `alias``.
                $parts = explode(' AS ', $table);
                $alias = trim(end($parts), '` ');
                if (
                    count($join['ON']) !== 2
                    || !is_string($join['ON'][$ref_table] ?? null)
                    || ($join['ON'][$alias] ?? null) !== 'id'
                ) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * GROUP BY of the inner subquery: only when a join may multiply rows, because MySQL does not
     * optimise the GROUP BY away and then sorts every matching row instead of reading the sort
     * index.
     */
    private function buildInnerGroupBy(): string
    {
        return $this->minimal_from_is_one_to_one ? '' : ' GROUP BY `' . $this->parts->itemtable . '`.`id`';
    }

    /**
     * Inner subquery resolving the page of main-table IDs alone (no display 1:N joins), with
     * ORDER BY + LIMIT. getParams() order: minimal-FROM, then WHERE.
     */
    private function buildDeferredInnerQuery(): Select
    {
        $query = 'SELECT `' . $this->parts->itemtable . '`.`id`'
            . $this->minimal_from
            . $this->parts->where
            . $this->buildInnerGroupBy()
            . $this->buildInnerOrderBy()
            . $this->limit;

        return (new Select())
            ->setQuery($query)
            ->setParams(array_merge($this->minimal_from_values, $this->parts->where_values));
    }

    /**
     * COUNT(DISTINCT id) query giving the total row count for an active (SQL-limited) search.
     */
    private function buildActiveCountQuery(): Select
    {
        $query = 'SELECT COUNT(DISTINCT `' . $this->parts->itemtable . '`.`id`)'
            . $this->minimal_from
            . $this->parts->where;

        return (new Select())
            ->setQuery($query)
            ->setParams(array_merge($this->minimal_from_values, $this->parts->where_values));
    }

    /**
     * Whether the search option is a column of a table linked by a foreign key of the main table
     * (standard join on the linked table id): such a join yields at most one row per item.
     *
     * @param array<string, mixed> $opt
     */
    private function isLinkedTableSortOption(array $opt): bool
    {
        $itemtype = $this->data['itemtype'];

        return isset($opt['table'], $opt['field'])
            && $opt['table'] !== $this->parts->itemtable
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
     */
    private function buildDeferredInnerQueryWithSortColumns(): ?Select
    {
        /** @var class-string<CommonDBTM> $itemtype */
        $itemtype  = $this->data['itemtype'];
        $itemtable = $this->parts->itemtable;

        // A derived table cannot contain duplicated column names, whereas some search options
        // select the same aliased column twice.
        $columns = ['id' => '`' . $itemtable . '`.`id` AS `id`'];
        foreach ($this->sort_fields as $sort_field) {
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
        if (!self::minimalFromCoversWhere($this->minimal_from, implode(' ', $columns) . $this->parts->order)) {
            return null;
        }

        $query = 'SELECT `id` FROM ('
            . 'SELECT ' . implode(', ', $columns)
            . $this->minimal_from
            . $this->parts->where
            . $this->buildInnerGroupBy()
            . $this->parts->order . ', `' . $itemtable . '`.`id` ' . $this->getTieBreakerOrder()
            . $this->limit
            . ') AS `deferred_sorted`';

        return (new Select())
            ->setQuery($query)
            ->setParams(array_merge($this->minimal_from_values, $this->parts->where_values));
    }

    /**
     * Whether deferred-join pagination is safe: grouped, limited, ordered, non-union search with
     * no meta/`view`/`all` criterion, no HAVING, and every sort field a plain main-table column
     * or a column of a table linked by a foreign key of the main table (so the page can be
     * resolved and ordered from the minimal FROM alone). HAVING / 1:N-aggregate
     * sorts are excluded on purpose — the aggregate must be computed over the whole relation
     * anyway, so deferral buys little. Otherwise the normal query is emitted.
     */
    private function canUseDeferredJoin(): bool
    {
        global $CFG_GLPI;

        $data      = $this->data;
        $itemtable = $this->parts->itemtable;

        if (isset($CFG_GLPI['union_search_type'][$data['itemtype']])) {
            return false;
        }
        if ($this->parts->groupby === '' || $this->limit === '' || $this->parts->order === '' || $this->parts->having !== '') {
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

        foreach ($this->sort_fields as $sort_field) {
            $ID = $sort_field['searchopt_id'];
            if (!isset($this->searchopt[$ID]['table']) || !isset($this->searchopt[$ID]['field'])) {
                return false;
            }
            $opt = $this->searchopt[$ID];
            // Columns of a table linked by a foreign key of the main table are joined in the
            // minimal FROM (1:1 join), so the inner subquery can sort on them too.
            if ($this->isLinkedTableSortOption($opt)) {
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
    private static function hasMetaCriteria(array $criteria): bool
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
    private static function criteriaReferenceAllColumns(array $criteria): bool
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
    private static function minimalFromCoversWhere(string $minimal_from, string $where): bool
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
