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

use Glpi\Search\SearchOption;

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

        $searchopt  = SearchOption::getOptionsForItemtype($itemtype);

        $table      = $searchopt[$ID]["table"];
        $field      = $searchopt[$ID]["field"];

        // Plugin can override core definition for its type
        if ($plug = isPluginItemType($itemtype)) {
            $out = \Plugin::doOneHook(
                $plug['plugin'],
                'displayConfigItem',
                $itemtype,
                $ID,
                $data,
                "{$itemtype}_{$ID}"
            );
            if (!empty($out)) {
                return $out;
            }
        }

        $out = "";
        $NAME = "{$itemtype}_{$ID}";

        switch ($table . "." . $field) {
            case "glpi_tickets.time_to_resolve":
            case "glpi_tickets.internal_time_to_resolve":
            case "glpi_problems.time_to_resolve":
            case "glpi_changes.time_to_resolve":
                if (in_array($ID, [151, 181])) {
                    break; // Skip "TTR + progress" search options
                }

                $value      = $data[$NAME][0]['name'];
                $status     = $data[$NAME][0]['status'];
                $solve_date = $data[$NAME][0]['solvedate'];

                $is_late = !empty($value)
                    && $status != \CommonITILObject::WAITING
                    && (
                        $solve_date > $value
                        || ($solve_date == null && $value < $_SESSION['glpi_currenttime'])
                    );

                if ($is_late) {
                    $out = " class=\"shadow-none\" style=\"background-color: #cf9b9b\" ";
                }
                break;
            case "glpi_tickets.time_to_own":
            case "glpi_tickets.internal_time_to_own":
                if (in_array($ID, [158, 186])) {
                    break; // Skip "TTO + progress" search options
                }

                $value        = $data[$NAME][0]['name'];
                $status       = $data[$NAME][0]['status'];
                $opening_date = $data[$NAME][0]['date'];
                $tia_delay    = $data[$NAME][0]['takeintoaccount_delay_stat'];
                $tia_date     = $data[$NAME][0]['takeintoaccountdate'];
                // Fallback to old and incorrect computation for tickets saved before introducing takeintoaccountdate field
                if ($tia_delay > 0 && $tia_date == null) {
                    $tia_date = strtotime($opening_date) + $tia_delay;
                }

                $is_late = !empty($value)
                    && $status != \CommonITILObject::WAITING
                    && (
                        $tia_date > $value
                        || ($tia_date == null && $value < $_SESSION['glpi_currenttime'])
                    );

                if ($is_late) {
                    $out = " class=\"shadow-none\" style=\"background-color: #cf9b9b\" ";
                }
                break;
            case "glpi_certificates.date_expiration":
                if (
                    !in_array($ID, [151, 158, 181, 186])
                    && !empty($data[$NAME][0]['name'])
                ) {
                    $out = "";
                    if ($before = \Entity::getUsedConfig('send_certificates_alert_before_delay', $_SESSION['glpiactive_entity'])) {
                        $before = date('Y-m-d', strtotime($_SESSION['glpi_currenttime'] . " + $before days"));
                        if ($data[$NAME][0]['name'] < $_SESSION['glpi_currenttime']) {
                            $out = " class=\"shadow-none\" style=\"color: white; background-color: #d63939\" ";
                        } elseif ($data[$NAME][0]['name'] < $before) {
                            $out = " class=\"shadow-none\"  style=\"background-color: #de5d06\" ";
                        } elseif ($data[$NAME][0]['name'] >= $before) {
                            $out = " class=\"shadow-none\"  style=\"background-color: #a1cf66\" ";
                        }
                    } else {
                        if ($data[$NAME][0]['name'] < $_SESSION['glpi_currenttime']) {
                            $out = " class=\"shadow-none\" style=\"background-color: #cf9b9b\" ";
                        }
                    }
                }
                break;
            case "glpi_projectstates.color":
            case "glpi_cables.color":
                $bg_color = $data[$NAME][0]['name'];
                if (!empty($bg_color)) {
                    $out = " class=\"shadow-none\" style=\"background-color: $bg_color;\" ";
                }
                break;

            case "glpi_projectstates.name":
                if (array_key_exists('color', $data[$NAME][0])) {
                    $bg_color = $data[$NAME][0]['color'];
                    if (!empty($bg_color)) {
                        $out = " class=\"shadow-none\" style=\"background-color: $bg_color;\" ";
                    }
                }
                break;

            case "glpi_domains.date_expiration":
                if (
                    !empty($data[$NAME][0]['name'])
                    && ($data[$NAME][0]['name'] < $_SESSION['glpi_currenttime'])
                ) {
                    $out = " class=\"shadow-none\" style=\"background-color: #cf9b9b\" ";
                }
                break;
        }

        return $out;
    }

    public static function displayData(array $data, array $params = [])
    {
        /** @var array $CFG_GLPI */
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
                ($row_num % 2),
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
