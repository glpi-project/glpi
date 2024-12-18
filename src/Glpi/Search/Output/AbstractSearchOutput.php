<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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
     * Display some content before the search input form and results
     * @param class-string<CommonGLPI> $itemtype
     * @return void
     */
    public static function showPreSearchDisplay(string $itemtype): void
    {
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

    /**
     * Print generic normal Item Cell
     *
     * @param string|null   $value       Value to display
     * @param integer       &$num        Column number
     * @param integer       $row         Row number
     * @param string        $extraparam  Extra parameters for display (default '')
     *
     * @return string HTML to display
     **/
    abstract public static function showItem($value, &$num, $row, $extraparam = ''): string;


    /**
     * Print generic error
     *
     * @param string  $message  Message to display, if empty "no item found" will be displayed
     *
     * @return string HTML to display
     **/
    abstract public static function showError($message = ''): string;


    /**
     * Print generic footer
     *
     * @param string  $title title of file : used for PDF (default '')
     * @param integer $count Total number of results
     *
     * @return string HTML to display
     **/
    abstract public static function showFooter($title = "", $count = null): string;


    /**
     * Print generic footer
     *
     * @param integer         $rows   Number of rows
     * @param integer         $cols   Number of columns
     * @param boolean|integer $fixed  Used tab_cadre_fixe table for HTML export ? (default 0)
     *
     * @return string HTML to display
     **/
    abstract public static function showHeader($rows, $cols, $fixed = 0): string;

    /**
     * Print generic Header Column
     *
     * @param string           $value    Value to display. This value may contain HTML data. Non-HTML content should be escaped before calling this function.
     * @param integer          &$num     Column number
     * @param string           $linkto   Link display element (HTML specific) (default '')
     * @param boolean|integer  $issort   Is the sort column ? (default 0)
     * @param string           $order    Order type ASC or DESC (defaut '')
     * @param string           $options  Options to add (default '')
     *
     * @return string HTML to display
     **/
    abstract public static function showHeaderItem($value, &$num, $linkto = "", $issort = 0, $order = "", $options = ""): string;

    /**
     * Print begin of header part
     *
     * @return string HTML to display
     **/
    abstract public static function showBeginHeader(): string;


    /**
     * Print end of header part
     *
     * @return string to display
     **/
    abstract public static function showEndHeader(): string;

    /**
     * Print generic new line
     *
     * @param boolean $odd         Is it a new odd line ? (false by default)
     * @param boolean $is_deleted  Is it a deleted search ? (false by default)
     *
     * @return string HTML to display
     **/
    abstract public static function showNewLine($odd = false, $is_deleted = false): string;

    /**
     * Print generic end line
     * @param bool $is_header_line
     * @return string HTML to display
     */
    abstract public static function showEndLine(bool $is_header_line): string;

    public function canDisplayResultsContainerWithoutExecutingSearch(): bool
    {
        return false;
    }
}
