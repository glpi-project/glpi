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

use function Safe\preg_match;

/**
 *
 * @internal Not for use outside {@link Search} class and the "Glpi\Search" namespace.
 */
final class NamesListSearchOutput extends ExportSearchOutput
{
    public function displayData(array $data, array $params = [])
    {
        global $CFG_GLPI;

        if (
            !isset($data['data'])
            || !isset($data['data']['totalcount'])
            || $data['data']['count'] <= 0
            || $data['search']['as_map'] != 0
        ) {
            return false;
        }

        // Define begin and end var for loop
        // Search case
        $begin_display = $data['data']['begin'];
        $end_display   = $data['data']['end'];

        // Compute number of columns to display
        // Add toview elements
        $nbcols          = count($data['data']['cols']);

        // Display List Header
        echo static::showHeader($end_display - $begin_display + 1, $nbcols);

        // Num of the row (1=header_line)
        $row_num = 1;

        $typenames = [];
        // Display Loop
        foreach ($data['data']['rows'] as $row) {
            // Column num
            $item_num = 1;
            $row_num++;
            // New line

            // Print other toview items
            foreach ($data['data']['cols'] as $col) {
                $colkey = "{$col['itemtype']}_{$col['id']}";
                if (!$col['meta']) {
                    echo static::showItem(
                        $row[$colkey]['displayname'], // `displayname` is provided by `giveItem()` and expected to be a safe HTML string
                        $item_num,
                        $row_num,
                        static::displayConfigItem(
                            $data['itemtype'],
                            $col['id'],
                            $row
                        )
                    );
                } else { // META case
                    echo static::showItem(
                        $row[$colkey]['displayname'], // `displayname` is provided by `giveItem()` and expected to be a safe HTML string
                        $item_num,
                        $row_num
                    );
                }
            }

            if (isset($CFG_GLPI["union_search_type"][$data['itemtype']])) {
                if (!isset($typenames[$row["TYPE"]])) {
                    if ($itemtmp = getItemForItemtype($row["TYPE"])) {
                        $typenames[$row["TYPE"]] = $itemtmp->getTypeName();
                    }
                }
                echo static::showItem(
                    htmlescape($typenames[$row["TYPE"]]),
                    $item_num,
                    $row_num
                );
            }
            // End Line
            echo static::showEndLine();
        }
    }

    public static function showEndLine(): string
    {
        return "\n";
    }

    public static function showHeader($rows, $cols, $fixed = 0): string
    {
        if (!headers_sent()) {
            header("Content-disposition: filename=glpi.txt");
            header('Content-type: file/txt');
        }
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
}
