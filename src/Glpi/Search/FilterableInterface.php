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

namespace Glpi\Search;

use CommonDBTM;

/**
 * Must be implemented by classes that wish to enable search engine based filters
 */
interface FilterableInterface
{
    /**
     * Get itemtype to be used as a filter by the search engine
     *
     * @return string
     */
    public function getItemtypeToFilter(): string;

    /**
     * Must be specified if getItemtypeToFilter() rely on a dynamic database column.
     * This will allow to invalidate filters when the target itemtype change
     *
     * If getItemtypeToFilter() use a fixed value instead, this function must
     * return null
     *
     * @return string|null
     */
    public function getItemtypeField(): ?string;

    /**
     * To help users understand how the filter will be used by GLPI, we will
     * display a small info section at the start of the "Filter" tab
     *
     * The info section will be constructed as a tabler "alert", which need
     * a title explaining the general purpose of the filter.
     *
     * @return string
     */
    public function getInfoTitle(): string;

    /**
     * To help users understand how the filter will be used by GLPI, we will
     * display a small info section at the start of the "Filter" tab
     *
     * The info section will be constructed as a tabler "alert", which need
     * a description to explain in details how the filter will be used.
     *
     * @return string
     */
    public function getInfoDescription(): string;

    /**
     * Check that the given item match the filters defined for the current item
     *
     * @param CommonDBTM $item Given item
     *
     * @return bool
     */
    public function itemMatchFilter(CommonDBTM $item): bool;

    /**
     * Create or update filter for the current item
     *
     * @param array  $search_criteria Search criteria used as filter
     *
     * @return bool
     */
    public function saveFilter(array $search_criteria): bool;

    /**
     * Delete filter for a given item
     *
     * @return bool
     */
    public function deleteFilter(): bool;
}
