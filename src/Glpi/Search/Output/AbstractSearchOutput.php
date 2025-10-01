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

namespace Glpi\Search\Output;

use CommonGLPI;

/**
 *
 * @internal Not for use outside {@link Search} class and the "Glpi\Search" namespace.
 */
abstract class AbstractSearchOutput
{
    /**
     * Modify the search parameters before the search is executed.
     *
     * This is useful if some criteria need injected such as the Location in the case of the Map output.
     * This is called after the search input form is shown, so any new criteria will be hidden.
     * @param class-string<CommonGLPI> $itemtype
     * @param array $params
     * @return array
     */
    public static function prepareInputParams(string $itemtype, array $params): array
    {
        return $params;
    }

    /**
     * Display the search results
     *
     * @param array $data Array of search data prepared to get data
     * @param array $params The original search parameters
     *
     * @return void|false
     **/
    abstract public function displayData(array $data, array $params = []);

    public function canDisplayResultsContainerWithoutExecutingSearch(): bool
    {
        return false;
    }
}
