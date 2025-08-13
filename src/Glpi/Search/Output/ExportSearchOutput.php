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

use Glpi\Plugin\Hooks;
use Glpi\Search\SearchOption;
use Plugin;

/**
 *
 * @internal Not for use outside {@link Search} class and the "Glpi\Search" namespace.
 */
abstract class ExportSearchOutput extends AbstractSearchOutput
{
    /**
     * Generic Function to display Items
     *
     * @since 9.4: $num param has been dropped
     *
     * @param string  $itemtype item type
     * @param integer $ID       ID of the SEARCH_OPTION item
     * @param array   $data     array retrieved data array
     *
     * @return string String to print
     **/
    public static function displayConfigItem($itemtype, $ID, $data = [])
    {

        SearchOption::getOptionsForItemtype($itemtype);

        // Plugin can override core definition for its type
        if ($plug = isPluginItemType($itemtype)) {
            $out = Plugin::doOneHook(
                $plug['plugin'],
                Hooks::AUTO_DISPLAY_CONFIG_ITEM,
                $itemtype,
                $ID,
                $data,
                "{$itemtype}_{$ID}"
            );
            if (!empty($out)) {
                return $out;
            }
        }

        return '';
    }

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

        // New Line for Header Items Line
        $headers_line        = '';
        $headers_line_top    = '';

        $headers_line_top .= static::showBeginHeader();
        $headers_line_top .= static::showNewLine();

        $header_num = 1;

        // Display column Headers for toview items
        $metanames = [];
        foreach ($data['data']['cols'] as $val) {
            $name = $val["name"];

            // prefix by group name (corresponding to optgroup in dropdown) if exists
            if (isset($val['groupname'])) {
                $groupname = $val['groupname'];
                if (is_array($groupname)) {
                    //since 9.2, getSearchOptions has been changed
                    $groupname = $groupname['name'];
                }
                $name  = "$groupname - $name";
            }

            // Not main itemtype add itemtype to display
            if ($data['itemtype'] != $val['itemtype']) {
                if (!isset($metanames[$val['itemtype']])) {
                    if ($metaitem = getItemForItemtype($val['itemtype'])) {
                        $metanames[$val['itemtype']] = $metaitem->getTypeName();
                    }
                }
                $name = sprintf(
                    __('%1$s - %2$s'),
                    $metanames[$val['itemtype']],
                    $val["name"]
                );
            }

            $headers_line .= static::showHeaderItem(
                $name,
                $header_num,
                '',
                (!$val['meta']
                    && ($data['search']['sort'] == $val['id'])),
                $data['search']['order']
            );
        }

        // Add specific column Header
        if (isset($CFG_GLPI["union_search_type"][$data['itemtype']])) {
            $headers_line .= static::showHeaderItem(
                __('Item type'),
                $header_num
            );
        }
        // End Line for column headers
        $headers_line .= static::showEndLine(true);

        $headers_line_top    .= $headers_line;
        $headers_line_top    .= static::showEndHeader();

        echo $headers_line_top;

        // Num of the row (1=header_line)
        $row_num = 1;

        $typenames = [];
        // Display Loop
        foreach ($data['data']['rows'] as $row) {
            // Column num
            $item_num = 1;
            $row_num++;
            // New line
            echo static::showNewLine(
                $row_num % 2 === 1,
                $data['search']['is_deleted']
            );

            // Print other toview items
            foreach ($data['data']['cols'] as $col) {
                $colkey = "{$col['itemtype']}_{$col['id']}";
                if (!$col['meta']) {
                    echo static::showItem(
                        $row[$colkey]['displayname'],
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
                        $row[$colkey]['displayname'],
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
                    $typenames[$row["TYPE"]],
                    $item_num,
                    $row_num
                );
            }
            // End Line
            echo static::showEndLine(false);
        }

        // Create title
        $title = static::computeTitle($data);

        // Display footer (close table)
        echo static::showFooter($title, $data['data']['count']);
    }

    public static function showNewLine($odd = false, $is_deleted = false): string
    {
        return '';
    }

    public static function showEndHeader(): string
    {
        return '';
    }

    public static function showError($message = ''): string
    {
        return '';
    }

    protected static function computeTitle(array $data): string
    {
        return '';
    }
}
