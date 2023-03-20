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

/**
 * Must be implemented by classes that wish to enable search engine based filters
 */
interface Filterable
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
}
