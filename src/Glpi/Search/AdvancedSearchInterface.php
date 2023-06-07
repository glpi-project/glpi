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

namespace Glpi\Search;

use Glpi\DBAL\QueryFunction;

interface AdvancedSearchInterface
{
    /**
     * @param class-string<\CommonDBTM> $itemtype
     * @return array|null
     */
    public static function getSQLDefaultSelectCriteria(string $itemtype): ?array;

    /**
     * @param class-string<\CommonDBTM> $itemtype
     * @param SearchOption $opt
     * @param bool $meta
     * @param string $meta_type
     * @return array|null
     */
    public static function getSQLSelectCriteria(string $itemtype, SearchOption $opt, bool $meta = false, string $meta_type = ''): ?array;

    /**
     * This method is called on the class that the search option belongs to (based on the table).
     * It can return an array of criteria to handle the search option in a non-standard way, or return null to indicate it wasn't handled at all.
     * @param class-string<\CommonDBTM> $itemtype The main itemtype being searched on
     * @param SearchOption $opt The search option being handled
     * @param bool $nott Whether the search option is negated
     * @param string $searchtype The search type (e.g. 'contains')
     * @param mixed $val The value to search for
     * @param bool $meta Whether the search option is for a meta field
     * @param callable $fn_append_with_search A helper function to append a criterion to a criteria array in a standardized way
     * @phpstan-param callable(array &$criteria, string|QueryFunction $value): void $fn_append_with_search
     * @return array|null
     */
    public static function getSQLWhereCriteria(string $itemtype, SearchOption $opt, bool $nott, string $searchtype, mixed $val, bool $meta, callable $fn_append_with_search): ?array;
}
