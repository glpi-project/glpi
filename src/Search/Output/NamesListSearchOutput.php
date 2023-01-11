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

namespace Glpi\Search\Output;

/**
 *
 * @internal Not for use outside {@link Search} class and the "Glpi\Search" namespace.
 */
final class NamesListSearchOutput extends ExportSearchOutput
{
    public static function showEndLine(bool $is_header_line): string
    {
        return $is_header_line ? '' : "\n";
    }

    public static function showBeginHeader(): string
    {
        return '';
    }

    public static function showHeader($rows, $cols, $fixed = 0): string
    {
        if (!headers_sent()) {
            header("Content-disposition: filename=glpi.txt");
            header('Content-type: file/txt');
        }
        return '';
    }

    public static function showHeaderItem($value, &$num, $linkto = "", $issort = 0, $order = "", $options = ""): string
    {
        return '';
    }

    public static function showItem($value, &$num, $row, $extraparam = ''): string
    {
        // We only want to display one column (the name of the item).
        // The name field is always the first column expect for tickets
        // which have their ids as the first column instead, thus moving the
        // name to the second column.
        // We don't have access to the itemtype so we must rely on data
        // types to figure which column to use :
        //    - Ticket will have a numeric first column (id) and an HTML
        //    link containing the name as the second column.
        //    - Other items will have an HTML link containing the name as
        //    the first column and a simple string containing the entity
        //    name as the second column.
        // -> We can check that the column is the first or second AND is html
        $out = '';
        if (
            strip_tags($value) !== $value
            && ($num == 1 || $num == 2)
        ) {
            // Use a regex to keep only the link, there may be other content
            // after that we don't need (script, tooltips, ...)
            if (preg_match('/<a.*<\/a>/', $value, $matches)) {
                $out = html_entity_decode(strip_tags($matches[0]));
            }
        }
        $num++;
        return $out;
    }

    public static function showFooter($title = "", $count = null): string
    {
        return '';
    }
}
