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

use Glpi\Search\SearchOption;
use Glpi\Toolbox\DataExport;

/**
 *
 * @internal Not for use outside {@link Search} class and the "Glpi\Search" namespace.
 */
abstract class PDFSearchOutput extends ExportSearchOutput
{
    /**
     * Compute title (use case of PDF OUTPUT)
     *
     * @param array $data Array data of search
     *
     * @return string Title
     **/
    protected static function computeTitle(array $data): string
    {
        $title = "";

        if (count($data['search']['criteria'])) {
            //Drop the first link as it is not needed, or convert to clean link (AND NOT -> NOT)
            if (isset($data['search']['criteria']['0']['link'])) {
                $notpos = strpos($data['search']['criteria']['0']['link'], 'NOT');
                //If link was like '%NOT%' just use NOT. Otherwise, remove the link
                if ($notpos > 0) {
                    $data['search']['criteria']['0']['link'] = 'NOT';
                } else if (!$notpos) {
                    unset($data['search']['criteria']['0']['link']);
                }
            }

            foreach ($data['search']['criteria'] as $criteria) {
                if (isset($criteria['itemtype'])) {
                    $searchopt = SearchOption::getOptionsForItemtype($criteria['itemtype']);
                } else {
                    $searchopt = SearchOption::getOptionsForItemtype($data['itemtype']);
                }
                $titlecontain = '';

                if (isset($criteria['criteria'])) {
                    //This is a group criteria, call computeTitle again and concat
                    $newdata = $data;
                    $oldlink = $criteria['link'];
                    $newdata['search'] = $criteria;
                    $titlecontain = sprintf(
                        __('%1$s %2$s (%3$s)'),
                        $titlecontain,
                        $oldlink,
                        self::computeTitle($newdata)
                    );
                } else {
                    if (strlen($criteria['value']) > 0) {
                        if (isset($criteria['link'])) {
                            $titlecontain = " " . $criteria['link'] . " ";
                        }
                        $gdname    = '';
                        $valuename = '';

                        switch ($criteria['field']) {
                            case "all":
                                $titlecontain = sprintf(__('%1$s %2$s'), $titlecontain, __('All'));
                                break;

                            case "view":
                                $titlecontain = sprintf(__('%1$s %2$s'), $titlecontain, __('Items seen'));
                                break;

                            default:
                                if (isset($criteria['meta']) && $criteria['meta']) {
                                    $searchoptname = sprintf(
                                        __('%1$s / %2$s'),
                                        $criteria['itemtype'],
                                        $searchopt[$criteria['field']]["name"]
                                    );
                                } else {
                                    $searchoptname = $searchopt[$criteria['field']]["name"];
                                }

                                $titlecontain = sprintf(__('%1$s %2$s'), $titlecontain, $searchoptname);
                                $itemtype     = getItemTypeForTable($searchopt[$criteria['field']]["table"]);
                                $valuename    = '';
                                if ($item = getItemForItemtype($itemtype)) {
                                    $valuename = $item->getValueToDisplay(
                                        $searchopt[$criteria['field']],
                                        $criteria['value']
                                    );
                                }

                                $gdname = \Dropdown::getDropdownName(
                                    $searchopt[$criteria['field']]["table"],
                                    $criteria['value']
                                );
                        }

                        if (empty($valuename)) {
                            $valuename = $criteria['value'];
                        }
                        switch ($criteria['searchtype']) {
                            case "equals":
                                if (
                                    in_array(
                                        $searchopt[$criteria['field']]["field"],
                                        ['name', 'completename']
                                    )
                                ) {
                                    $titlecontain = sprintf(__('%1$s = %2$s'), $titlecontain, $gdname);
                                } else {
                                    $titlecontain = sprintf(__('%1$s = %2$s'), $titlecontain, $valuename);
                                }
                                break;

                            case "notequals":
                                if (
                                    in_array(
                                        $searchopt[$criteria['field']]["field"],
                                        ['name', 'completename']
                                    )
                                ) {
                                    $titlecontain = sprintf(__('%1$s <> %2$s'), $titlecontain, $gdname);
                                } else {
                                    $titlecontain = sprintf(__('%1$s <> %2$s'), $titlecontain, $valuename);
                                }
                                break;

                            case "lessthan":
                                $titlecontain = sprintf(__('%1$s < %2$s'), $titlecontain, $valuename);
                                break;

                            case "morethan":
                                $titlecontain = sprintf(__('%1$s > %2$s'), $titlecontain, $valuename);
                                break;

                            case "contains":
                                $titlecontain = sprintf(
                                    __('%1$s = %2$s'),
                                    $titlecontain,
                                    '%' . $valuename . '%'
                                );
                                break;

                            case "notcontains":
                                $titlecontain = sprintf(
                                    __('%1$s <> %2$s'),
                                    $titlecontain,
                                    '%' . $valuename . '%'
                                );
                                break;

                            case "under":
                                $titlecontain = sprintf(
                                    __('%1$s %2$s'),
                                    $titlecontain,
                                    sprintf(__('%1$s %2$s'), __('under'), $gdname)
                                );
                                break;

                            case "notunder":
                                $titlecontain = sprintf(
                                    __('%1$s %2$s'),
                                    $titlecontain,
                                    sprintf(__('%1$s %2$s'), __('not under'), $gdname)
                                );
                                break;

                            case "empty":
                                $titlecontain = sprintf(__('%1$s is empty'), $titlecontain);
                                break;

                            default:
                                $titlecontain = sprintf(__('%1$s = %2$s'), $titlecontain, $valuename);
                                break;
                        }
                    }
                }
                $title .= $titlecontain;
            }
        }
        if (
            isset($data['search']['metacriteria']) &&
            count($data['search']['metacriteria'])
        ) {
            $metanames = [];
            foreach ($data['search']['metacriteria'] as $metacriteria) {
                $searchopt = SearchOption::getOptionsForItemtype($metacriteria['itemtype']);
                if (!isset($metanames[$metacriteria['itemtype']])) {
                    if ($metaitem = getItemForItemtype($metacriteria['itemtype'])) {
                        $metanames[$metacriteria['itemtype']] = $metaitem->getTypeName();
                    }
                }

                $titlecontain2 = '';
                if (strlen($metacriteria['value']) > 0) {
                    if (isset($metacriteria['link'])) {
                        $titlecontain2 = sprintf(
                            __('%1$s %2$s'),
                            $titlecontain2,
                            $metacriteria['link']
                        );
                    }
                    $titlecontain2 = sprintf(
                        __('%1$s %2$s'),
                        $titlecontain2,
                        sprintf(
                            __('%1$s / %2$s'),
                            $metanames[$metacriteria['itemtype']],
                            $searchopt[$metacriteria['field']]["name"]
                        )
                    );

                    $gdname2 = \Dropdown::getDropdownName(
                        $searchopt[$metacriteria['field']]["table"],
                        $metacriteria['value']
                    );
                    switch ($metacriteria['searchtype']) {
                        case "equals":
                            if (
                                in_array(
                                    $searchopt[$metacriteria['link']]
                                    ["field"],
                                    ['name', 'completename']
                                )
                            ) {
                                $titlecontain2 = sprintf(
                                    __('%1$s = %2$s'),
                                    $titlecontain2,
                                    $gdname2
                                );
                            } else {
                                $titlecontain2 = sprintf(
                                    __('%1$s = %2$s'),
                                    $titlecontain2,
                                    $metacriteria['value']
                                );
                            }
                            break;

                        case "notequals":
                            if (
                                in_array(
                                    $searchopt[$metacriteria['link']]["field"],
                                    ['name', 'completename']
                                )
                            ) {
                                $titlecontain2 = sprintf(
                                    __('%1$s <> %2$s'),
                                    $titlecontain2,
                                    $gdname2
                                );
                            } else {
                                $titlecontain2 = sprintf(
                                    __('%1$s <> %2$s'),
                                    $titlecontain2,
                                    $metacriteria['value']
                                );
                            }
                            break;

                        case "lessthan":
                            $titlecontain2 = sprintf(
                                __('%1$s < %2$s'),
                                $titlecontain2,
                                $metacriteria['value']
                            );
                            break;

                        case "morethan":
                            $titlecontain2 = sprintf(
                                __('%1$s > %2$s'),
                                $titlecontain2,
                                $metacriteria['value']
                            );
                            break;

                        case "contains":
                            $titlecontain2 = sprintf(
                                __('%1$s = %2$s'),
                                $titlecontain2,
                                '%' . $metacriteria['value'] . '%'
                            );
                            break;

                        case "notcontains":
                            $titlecontain2 = sprintf(
                                __('%1$s <> %2$s'),
                                $titlecontain2,
                                '%' . $metacriteria['value'] . '%'
                            );
                            break;

                        case "under":
                            $titlecontain2 = sprintf(
                                __('%1$s %2$s'),
                                $titlecontain2,
                                sprintf(
                                    __('%1$s %2$s'),
                                    __('under'),
                                    $gdname2
                                )
                            );
                            break;

                        case "notunder":
                            $titlecontain2 = sprintf(
                                __('%1$s %2$s'),
                                $titlecontain2,
                                sprintf(
                                    __('%1$s %2$s'),
                                    __('not under'),
                                    $gdname2
                                )
                            );
                            break;

                        case "empty":
                            $titlecontain2 = sprintf(__('%1$s is empty'), $titlecontain2);
                            break;

                        default:
                            $titlecontain2 = sprintf(
                                __('%1$s = %2$s'),
                                $titlecontain2,
                                $metacriteria['value']
                            );
                            break;
                    }
                }
                $title .= $titlecontain2;
            }
        }
        return $title;
    }

    public static function showNewLine($odd = false, $is_deleted = false): string
    {
        /** @var string $PDF_TABLE */
        global $PDF_TABLE;
        $style = "";
        if ($odd) {
            $style = " style=\"background-color:#DDDDDD;\" ";
        }
        $PDF_TABLE .= "<tr $style nobr=\"true\">";
        return '';
    }

    public static function showEndLine(bool $is_header_line): string
    {
        /** @var string $PDF_TABLE */
        global $PDF_TABLE;
        $PDF_TABLE .= '</tr>';
        return '';
    }

    public static function showBeginHeader(): string
    {
        /** @var string $PDF_TABLE */
        global $PDF_TABLE;
        $PDF_TABLE .= "<thead>";
        return '';
    }

    public static function showHeader($rows, $cols, $fixed = 0): string
    {
        /** @var string $PDF_TABLE */
        global $PDF_TABLE;
        $PDF_TABLE = "<table cellspacing=\"0\" cellpadding=\"1\" border=\"1\" >";
        return '';
    }

    public static function showHeaderItem($value, &$num, $linkto = "", $issort = 0, $order = "", $options = ""): string
    {
        /** @var string $PDF_TABLE */
        global $PDF_TABLE;
        $PDF_TABLE .= "<th $options>";
        $PDF_TABLE .= htmlspecialchars($value);
        $PDF_TABLE .= "</th>";
        $num++;
        return '';
    }

    public static function showEndHeader(): string
    {
        /** @var string $PDF_TABLE */
        global $PDF_TABLE;
        $PDF_TABLE .= "</thead>";
        return '';
    }

    public static function showItem($value, &$num, $row, $extraparam = ''): string
    {
        /** @var string $PDF_TABLE */
        global $PDF_TABLE;
        $value = DataExport::normalizeValueForTextExport($value ?? '');
        $value = htmlspecialchars($value);
        $value = preg_replace('/' . \Search::LBBR . '/', '<br>', $value);
        $value = preg_replace('/' . \Search::LBHR . '/', '<hr>', $value);
        $PDF_TABLE .= "<td $extraparam valign='top'>";
        $PDF_TABLE .= $value;
        $PDF_TABLE .= "</td>";
        $num++;
        return '';
    }
}
