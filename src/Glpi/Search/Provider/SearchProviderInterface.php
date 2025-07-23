<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace Glpi\Search\Provider;

/**
 * Interface for all search providers.
 *
 * Search providers query one or more types of data sources to get matching data.
 *
 * @internal Not for use outside {@link Search} class and the "Glpi\Search" namespace.
 */
interface SearchProviderInterface
{
    public static function prepareData(array &$data, array $options): array;

    /**
     * Construct SQL request depending on search parameters
     *
     * Add to data array a field sql containing an array of requests :
     *      search : request to get items limited to wanted ones
     *      count : to count all items based on search criterias
     *                    may be an array a request : need to add counts
     *                    maybe empty : use search one to count
     *
     * @param array $data Array of search data prepared to generate SQL
     * @return false|void
     */
    public static function constructSQL(array &$data);

    /**
     * Retrieve data from DB : construct data array containing columns definitions and rows data
     *
     * add to data array a field data containing :
     *      cols : columns definition
     *      rows : rows data
     *
     * @param array   $data      array of search data prepared to get data
     * @param boolean $onlycount If we just want to count results
     *
     * @return void|false
     **/
    public static function constructData(array &$data, $onlycount = false);
}
