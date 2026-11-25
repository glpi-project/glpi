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

/**
 * SQL clauses of a non-union search query, as built by SQLProvider::constructSQL(), with the
 * parameters bound by each of them.
 *
 * @internal
 */
final readonly class SearchQueryParts
{
    /**
     * @param string            $from          FROM clause, main table and every join
     * @param string            $joins         Joins of the FROM clause alone, re-applied by the
     *                                         deferred join to the page of ids
     * @param array<int, mixed> $select_values
     * @param array<int, mixed> $from_values
     * @param array<int, mixed> $joins_values
     * @param array<int, mixed> $where_values
     * @param array<int, mixed> $having_values
     */
    public function __construct(
        public string $itemtable,
        public string $select,
        public array $select_values,
        public string $from,
        public array $from_values,
        public string $joins,
        public array $joins_values,
        public string $where,
        public array $where_values,
        public string $groupby,
        public string $having,
        public array $having_values,
        public string $order,
        public string $limit,
    ) {}
}
