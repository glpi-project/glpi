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

use Dropdown;
use Glpi\Search\SearchOption;
use Glpi\Toolbox\DataExport;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\BaseWriter;
use PhpOffice\PhpSpreadsheet\Writer\IWriter;
use Session;

/**
 * @internal Not for use outside {@link Search} class and the "Glpi\Search" namespace.
 * @phpstan-consistent-constructor
 */
abstract class Spreadsheet extends ExportSearchOutput
{
    protected \PhpOffice\PhpSpreadsheet\Spreadsheet $spread;
    protected BaseWriter|IWriter $writer;
    protected $count;

    public function __construct()
    {
        $this->spread = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    }

    public function displayData(array $data, array $params = [])
    {
        global $CFG_GLPI;

        if (
            !isset($data['data'])
            || !isset($data['data']['totalcount'])
            || $data['search']['as_map'] != 0
        ) {
            return false;
        }

        $spread = $this->getSpreasheet();
        $writer = $this->getWriter();

        //set styles
        $style = $spread->getDefaultStyle();
        $font = $style->getFont();

        //write metadata
        $spread->getProperties()
            ->setCreator("GLPI " . GLPI_VERSION)
            ->setTitle($this->getTitle($data))
            ->setCustomProperty('items count', $data['data']['totalcount'])
        ;

        $worksheet = $spread->getActiveSheet();

        $line_num = 1;
        $col_num = 0;

        $font->setName($_SESSION['glpipdffont'] ?? 'helvetica');
        $font->setSize(8);

        // Display column Headers for toview items
        $metanames = [];
        foreach ($data['data']['cols'] as $val) {
            ++$col_num;
            $name = $val["name"];

            // prefix by group name (corresponding to optgroup in dropdown) if exists
            if (isset($val['groupname'])) {
                $groupname = $val['groupname'];
                if (is_array($groupname)) {
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

            $worksheet->setCellValue([$col_num, $line_num], $name);
        }

        // Add specific column Header
        if (isset($CFG_GLPI["union_search_type"][$data['itemtype']])) {
            ++$col_num;
            $worksheet->setCellValue([$col_num, $line_num], __('Item type'));
        }

        //column headers in bold
        $worksheet->getStyle('A1:' . $worksheet->getHighestColumn() . '1')
            ->getFont()->setBold(true);

        $typenames = [];
        // Display Loop
        foreach ($data['data']['rows'] as $row) {
            $col_num = 0;
            ++$line_num;

            // Print other toview items
            foreach ($data['data']['cols'] as $col) {
                ++$col_num;
                $colkey = "{$col['itemtype']}_{$col['id']}";

                $value = DataExport::normalizeValueForTextExport($row[$colkey]['displayname'] ?? '');
                $worksheet->setCellValue([$col_num, $line_num], $value);
            }

            if (isset($CFG_GLPI["union_search_type"][$data['itemtype']])) {
                ++$col_num;
                if (!isset($typenames[$row["TYPE"]])) {
                    if ($itemtmp = getItemForItemtype($row["TYPE"])) {
                        $typenames[$row["TYPE"]] = $itemtmp->getTypeName();
                    }
                }
                $value = DataExport::normalizeValueForTextExport($typenames[$row["TYPE"]] ?? '');
                $worksheet->setCellValue([$col_num, $line_num], $value);
            }

            if ($line_num % 2 != 0) {
                $worksheet->getStyle('A' . $line_num . ':' . $worksheet->getHighestColumn() . $line_num)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFDDDDDD');
            }
        }

        header('Content-Type: ' . $this->getMime());
        header('Content-Disposition: attachment; filename="' . urlencode($this->getFileName()) . '"');
        $writer->save('php://output');
    }

    public function getWriter(): BaseWriter|IWriter
    {
        return $this->writer;
    }

    public function getSpreasheet(): \PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        return $this->spread;
    }

    /**
     * Get file title
     *
     * @param array $data Array data of search
     *
     * @return string Title
     **/
    protected function getTitle(array $data): string
    {
        $title = "";

        if (count($data['search']['criteria'])) {
            //Drop the first link as it is not needed, or convert to clean link (AND NOT -> NOT)
            if (isset($data['search']['criteria']['0']['link'])) {
                $notpos = strpos($data['search']['criteria']['0']['link'], 'NOT');
                //If link was like '%NOT%' just use NOT. Otherwise, remove the link
                if ($notpos > 0) {
                    $data['search']['criteria']['0']['link'] = 'NOT';
                } elseif (!$notpos) {
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
                        $this->getTitle($newdata)
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

                                $gdname = Dropdown::getDropdownName(
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
            isset($data['search']['metacriteria'])
            && count($data['search']['metacriteria'])
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

                    $gdname2 = Dropdown::getDropdownName(
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

        if ($title === '') {
            $itemtype = $data['itemtype'];
            $title = sprintf(
                __('All %1$s'),
                $itemtype::getTypeName(Session::getPluralNumber())
            );
        }

        return sprintf(
            __('Search results for %1$s'),
            $title
        );
    }

    abstract public function getMime(): string;
    abstract public function getFileName(): string;
}
