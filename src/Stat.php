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

use Glpi\DBAL\QueryExpression;
use Glpi\Stat\StatData;
use Glpi\Application\View\TemplateRenderer;
use Laminas\Json\Expr as Json_Expr;
use Laminas\Json\Json;

/**
 *  Stat class
 **/
class Stat extends CommonGLPI
{
    public static $rightname = 'statistic';

    public static $cache = [];

    public static function getTypeName($nb = 0)
    {
        return __('Statistics');
    }


    /**
     * @see CommonGLPI::getMenuShorcut()
     *
     * @since 0.85
     **/
    public static function getMenuShorcut()
    {
        return 'a';
    }


    /**
     * @param $itemtype
     * @param $date1
     * @param $date2
     * @param $type
     * @param $parent    (default 0)
     **/
    public static function getItems($itemtype, $date1, $date2, $type, $parent = 0)
    {
        /** @var \DBmysql $DB */
        global $DB;

        if (!$item = getItemForItemtype($itemtype)) {
            return;
        }
        $val  = [];

        switch ($type) {
            case "technicien":
                /** @var CommonITILObject $item */
                $val = $item->getUsedTechBetween($date1, $date2);
                break;

            case "technicien_followup":
                /** @var CommonITILObject $item */
                $val = $item->getUsedTechTaskBetween($date1, $date2);
                break;

            case "suppliers_id_assign":
                /** @var CommonITILObject $item */
                $val = $item->getUsedSupplierBetween($date1, $date2);
                break;

            case "user":
                /** @var CommonITILObject $item */
                $val = $item->getUsedAuthorBetween($date1, $date2);
                break;

            case "users_id_recipient":
                /** @var CommonITILObject $item */
                $val = $item->getUsedRecipientBetween($date1, $date2);
                break;

            case 'group_tree':
            case 'groups_tree_assign':
               // Get all groups
                $is_field = ($type == 'group_tree') ? 'is_requester' : 'is_assign';
                $iterator = $DB->request([
                    'SELECT' => ['id', 'name'],
                    'FROM'   => 'glpi_groups',
                    'WHERE'  => [
                        'OR'  => [
                            'id'        => $parent,
                            'groups_id' => $parent
                        ],
                        $is_field   => 1
                    ] + getEntitiesRestrictCriteria("glpi_groups", '', '', true),
                    'ORDER'  => 'completename'
                ]);

                $val    = [];
                foreach ($iterator as $line) {
                     $val[] = [
                         'id'     => $line['id'],
                         'link'   => $line['name']
                     ];
                }
                break;

            case "itilcategories_tree":
            case "itilcategories_id":
                $is_tree = $type == 'itilcategories_tree';
               // Get all ticket categories for tree merge management
                $criteria = [
                    'SELECT'    => [
                        'glpi_itilcategories.id',
                        'glpi_itilcategories.' . ($is_tree ? 'name' : 'completename') . ' AS category'
                    ],
                    'DISTINCT'  => true,
                    'FROM'      => 'glpi_itilcategories',
                    'WHERE'     => getEntitiesRestrictCriteria('glpi_itilcategories', '', '', true),
                    'ORDERBY'   => 'completename'
                ];

                if ($is_tree) {
                    $criteria['WHERE']['OR'] = [
                        'id'                 => $parent,
                        'itilcategories_id'  => $parent
                    ];
                }

                $iterator = $DB->request($criteria);

                $val    = [];
                foreach ($iterator as $line) {
                    $val[] = [
                        'id'     => $line['id'],
                        'link'   => $line['category']
                    ];
                }
                break;

            case 'locations_tree':
            case 'locations_id':
                $is_tree = $type == 'locations_tree';
               // Get all locations for tree merge management
                $criteria = [
                    'SELECT'    => [
                        'glpi_locations.id',
                        'glpi_locations.' . ($is_tree ? 'name' : 'completename') . ' AS location'
                    ],
                    'DISTINCT'  => true,
                    'FROM'      => 'glpi_locations',
                    'WHERE'     => getEntitiesRestrictCriteria('glpi_locations', '', '', true),
                    'ORDERBY'   => 'completename'
                ];

                if ($is_tree) {
                    $criteria['WHERE']['OR'] = [
                        'id'           => $parent,
                        'locations_id' => $parent
                    ];
                }

                $iterator = $DB->request($criteria);

                $val    = [];
                foreach ($iterator as $line) {
                    $val[] = [
                        'id'     => $line['id'],
                        'link'   => $line['location']
                    ];
                }
                break;

            case "type":
                $types = $item->getTypes();
                $val   = [];
                foreach ($types as $id => $v) {
                    $tmp['id']   = $id;
                    $tmp['link'] = $v;
                    $val[]       = $tmp;
                }
                break;

            case "group":
                /** @var CommonITILObject $item */
                $val = $item->getUsedGroupBetween($date1, $date2);
                break;

            case "groups_id_assign":
                /** @var CommonITILObject $item */
                $val = $item->getUsedAssignGroupBetween($date1, $date2);
                break;

            case "priority":
                /** @var CommonITILObject $item */
                $val = $item->getUsedPriorityBetween($date1, $date2);
                break;

            case "urgency":
                /** @var CommonITILObject $item */
                $val = $item->getUsedUrgencyBetween($date1, $date2);
                break;

            case "impact":
                /** @var CommonITILObject $item */
                $val = $item->getUsedImpactBetween($date1, $date2);
                break;

            case "requesttypes_id":
                /** @var CommonITILObject $item */
                $val = $item->getUsedRequestTypeBetween($date1, $date2);
                break;

            case "solutiontypes_id":
                /** @var CommonITILObject $item */
                $val = $item->getUsedSolutionTypeBetween($date1, $date2);
                break;

            case "usertitles_id":
                /** @var CommonITILObject $item */
                $val = $item->getUsedUserTitleOrTypeBetween($date1, $date2, true);
                break;

            case "usercategories_id":
                /** @var CommonITILObject $item */
                $val = $item->getUsedUserTitleOrTypeBetween($date1, $date2, false);
                break;

           // DEVICE CASE
            default:
                if (
                    ($item = getItemForItemtype($type))
                    && ($item instanceof CommonDevice)
                ) {
                    $device_table = $item->getTable();

                   //select devices IDs (table row)
                    $iterator = $DB->request([
                        'SELECT' => [
                            'id',
                            'designation'
                        ],
                        'FROM'   => $device_table,
                        'ORDER'  => 'designation'
                    ]);

                    foreach ($iterator as $line) {
                          $val[] = [
                              'id'     => $line['id'],
                              'link'   => $line['designation']
                          ];
                    }
                } else {
                   // Dropdown case for computers
                    $field = "name";
                    $table = getTableForItemType($type);
                    if (
                        ($item = getItemForItemtype($type))
                        && ($item instanceof CommonTreeDropdown)
                    ) {
                        $field = "completename";
                    }

                    $criteria = [
                        'FROM'   => $table,
                        'ORDER'  => $field
                    ];

                    if ($item->isEntityAssign()) {
                        $criteria['ORDER'] = ['entities_id', $field];
                        $criteria['WHERE'] = getEntitiesRestrictCriteria($table);
                    }

                    $iterator = $DB->request($criteria);

                    $val    = [];
                    foreach ($iterator as $line) {
                        $val[] = [
                            'id'     => $line['id'],
                            'link'   => $line[$field]
                        ];
                    }
                }
        }
        return $val;
    }


    /**
     * @param $itemtype
     * @param $type
     * @param $date1
     * @param $date2
     * @param $start
     * @param $value     array
     * @param $value2             (default '')
     **/
    public static function getData($itemtype, $type, $date1, $date2, $start, array $value, $value2 = "")
    {
        $hash = md5(serialize(func_get_args()));

       // Single query cache to avoid recalculating data multiple times
       // Needed as multiple stats rely on partial data returneds by this function
       // Can be removed once we improve this code by spliting each data calculations
       // into separate functions that can be called independently
        if (isset(self::$cache[$hash])) {
            return self::$cache[$hash];
        }

        $export_data = [];

        if (is_array($value)) {
            $end_display = $start + $_SESSION['glpilist_limit'];
            $numrows     = count($value);

            for ($i = $start; $i < $numrows && $i < ($end_display); $i++) {
               //le nombre d'intervention - the number of intervention
                $opened    = self::constructEntryValues(
                    $itemtype,
                    "inter_total",
                    $date1,
                    $date2,
                    $type,
                    $value[$i]["id"],
                    $value2
                );
                $nb_opened = array_sum($opened);
                $export_data['opened'][$value[$i]['link']] = $nb_opened;

               //le nombre d'intervention resolues - the number of solved intervention
                $solved    = self::constructEntryValues(
                    $itemtype,
                    "inter_solved",
                    $date1,
                    $date2,
                    $type,
                    $value[$i]["id"],
                    $value2
                );
                $nb_solved = array_sum($solved);
                $export_data['solved'][$value[$i]['link']] = $nb_solved;

               //le nombre d'intervention resolues - the number of solved late intervention
                $late      = self::constructEntryValues(
                    $itemtype,
                    "inter_solved_late",
                    $date1,
                    $date2,
                    $type,
                    $value[$i]["id"],
                    $value2
                );
                $nb_late   = array_sum($late);
                $export_data['late'][$value[$i]['link']] = $nb_late;

               //le nombre d'intervention closes - the number of closed intervention
                $closed    = self::constructEntryValues(
                    $itemtype,
                    "inter_closed",
                    $date1,
                    $date2,
                    $type,
                    $value[$i]["id"],
                    $value2
                );
                $nb_closed = array_sum($closed);
                $export_data['closed'][$value[$i]['link']] = $nb_closed;

                if ($itemtype == 'Ticket') {
                     //open satisfaction
                     $opensatisfaction    = self::constructEntryValues(
                         $itemtype,
                         "inter_opensatisfaction",
                         $date1,
                         $date2,
                         $type,
                         $value[$i]["id"],
                         $value2
                     );
                       $nb_opensatisfaction = array_sum($opensatisfaction);
                       $export_data['opensatisfaction'][$value[$i]['link']] = $nb_opensatisfaction;
                }
            }
        }

        self::$cache[$hash] = $export_data;
        return $export_data;
    }


    /**
     * @param $itemtype
     * @param $type
     * @param $date1
     * @param $date2
     * @param $start
     * @param $value     array
     * @param $value2          (default '')
     *
     * @since 0.85 (before show with same parameters)
     **/
    public static function showTable($itemtype, $type, $date1, $date2, $start, array $value, $value2 = "")
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

       // Set display type for export if define
        $output_type = Search::HTML_OUTPUT;
        if (isset($_GET["display_type"])) {
            $output_type = $_GET["display_type"];
        }

        if ($output_type == Search::HTML_OUTPUT) { // HTML display
            echo "<div class ='card table-card center'>";
        }

        if (is_array($value)) {
            $end_display = $start + $_SESSION['glpilist_limit'];
            $numrows     = count($value);

            if (isset($_GET['export_all'])) {
                $start       = 0;
                $end_display = $numrows;
            }

            $nbcols = 8;
            if ($output_type != Search::HTML_OUTPUT) { // not HTML display
                $nbcols--;
            }

            echo Search::showHeader($output_type, $end_display - $start + 1, $nbcols);
            $subname = '';
            switch ($type) {
                case 'group_tree':
                case 'groups_tree_assign':
                    $subname = Dropdown::getDropdownName('glpi_groups', $value2);
                    break;

                case 'itilcategories_tree':
                    $subname = Dropdown::getDropdownName('glpi_itilcategories', $value2);
                    break;

                case 'locations_tree':
                    $subname = Dropdown::getDropdownName('glpi_locations', $value2);
                    break;
            }

            if ($output_type == Search::HTML_OUTPUT) { // HTML display
                echo Search::showNewLine($output_type);
                $header_num = 1;

                if (
                    ($output_type == Search::HTML_OUTPUT)
                    && strstr($type, '_tree')
                    && $value2
                ) {
                    // HTML display
                    $url = $_SERVER['PHP_SELF'] . '?' . Toolbox::append_params(
                        [
                            'date1'    => $date1,
                            'date2'    => $date2,
                            'itemtype' => $itemtype,
                            'type'     => $type,
                            'value2'   => 0,
                        ],
                        '&amp;'
                    );
                    $link = "<a href='$url'>" . __('Back') . "</a>";
                    echo Search::showHeaderItem($output_type, $link, $header_num);
                } else {
                    echo Search::showHeaderItem($output_type, "&nbsp;", $header_num);
                }
                echo Search::showHeaderItem($output_type, '', $header_num);

                echo Search::showHeaderItem(
                    $output_type,
                    _x('quantity', 'Number'),
                    $header_num,
                    '',
                    0,
                    '',
                    "colspan='4'"
                );
                if ($itemtype == 'Ticket') {
                     echo Search::showHeaderItem(
                         $output_type,
                         __('Satisfaction'),
                         $header_num,
                         '',
                         0,
                         '',
                         "colspan='3'"
                     );
                }
                echo Search::showHeaderItem(
                    $output_type,
                    __('Average time'),
                    $header_num,
                    '',
                    0,
                    '',
                    $itemtype == 'Ticket' ? "colspan='3'" : "colspan='2'"
                );
                echo Search::showHeaderItem(
                    $output_type,
                    __('Real duration of treatment of the ticket'),
                    $header_num,
                    '',
                    0,
                    '',
                    "colspan='2'"
                );
            }

            echo Search::showNewLine($output_type);
            $header_num    = 1;
            echo Search::showHeaderItem($output_type, $subname, $header_num);

            if ($output_type == Search::HTML_OUTPUT) { // HTML display
                echo Search::showHeaderItem($output_type, "", $header_num);
            }
            if ($output_type != Search::HTML_OUTPUT) {
                echo Search::showHeaderItem($output_type, __('Number of opened tickets'), $header_num);
                echo Search::showHeaderItem($output_type, __('Number of solved tickets'), $header_num);
                echo Search::showHeaderItem($output_type, __('Number of late tickets'), $header_num);
                echo Search::showHeaderItem($output_type, __('Number of closed tickets'), $header_num);
            } else {
                echo Search::showHeaderItem($output_type, _nx('ticket', 'Opened', 'Opened', Session::getPluralNumber()), $header_num);
                echo Search::showHeaderItem(
                    $output_type,
                    _nx('ticket', 'Solved', 'Solved', Session::getPluralNumber()),
                    $header_num
                );
                echo Search::showHeaderItem($output_type, __('Late'), $header_num);
                echo Search::showHeaderItem($output_type, __('Closed'), $header_num);
            }

            if ($itemtype == 'Ticket') {
                if ($output_type != Search::HTML_OUTPUT) {
                    echo Search::showHeaderItem(
                        $output_type,
                        __('Number of opened satisfaction survey'),
                        $header_num
                    );
                    echo Search::showHeaderItem(
                        $output_type,
                        __('Number of answered satisfaction survey'),
                        $header_num
                    );
                    echo Search::showHeaderItem(
                        $output_type,
                        __('Average satisfaction'),
                        $header_num
                    );
                } else {
                    echo Search::showHeaderItem(
                        $output_type,
                        _nx('survey', 'Opened', 'Opened', Session::getPluralNumber()),
                        $header_num
                    );
                    echo Search::showHeaderItem(
                        $output_type,
                        _nx('survey', 'Answered', 'Answered', Session::getPluralNumber()),
                        $header_num
                    );
                    echo Search::showHeaderItem($output_type, __('Average'), $header_num);
                }
            }

            if ($output_type != Search::HTML_OUTPUT) {
                if ($itemtype == 'Ticket') {
                    echo Search::showHeaderItem(
                        $output_type,
                        __('Average time to take into account'),
                        $header_num
                    );
                }
                echo Search::showHeaderItem($output_type, __('Average time to resolution'), $header_num);
                echo Search::showHeaderItem($output_type, __('Average time to closure'), $header_num);
            } else {
                if ($itemtype == 'Ticket') {
                    echo Search::showHeaderItem($output_type, __('Take into account'), $header_num);
                }
                echo Search::showHeaderItem($output_type, __('Resolution'), $header_num);
                echo Search::showHeaderItem($output_type, __('Closure'), $header_num);
            }

            if ($output_type != Search::HTML_OUTPUT) {
                echo Search::showHeaderItem(
                    $output_type,
                    __('Average real duration of treatment of the ticket'),
                    $header_num
                );
                echo Search::showHeaderItem(
                    $output_type,
                    __('Total real duration of treatment of the ticket'),
                    $header_num
                );
            } else {
                echo Search::showHeaderItem($output_type, __('Average'), $header_num);
                echo Search::showHeaderItem($output_type, __('Total duration'), $header_num);
            }
           // End Line for column headers
            echo Search::showEndLine($output_type);
            $row_num = 1;

            for ($i = $start; ($i < $numrows) && ($i < $end_display); $i++) {
                $row_num++;
                $item_num = 1;
                echo Search::showNewLine($output_type, $i % 2);
                if (
                    ($output_type == Search::HTML_OUTPUT)
                    && strstr($type, '_tree')
                    && ($value[$i]['id'] != $value2)
                ) {
                    // HTML display
                    $url = $_SERVER['PHP_SELF'] . '?' . Toolbox::append_params(
                        [
                            'date1'    => $date1,
                            'date2'    => $date2,
                            'itemtype' => $itemtype,
                            'type'     => $type,
                            'value2'  => $value[$i]['id'],
                        ],
                        '&amp;'
                    );
                    $link = "<a href='$url'>" . $value[$i]['link'] . "</a>";
                    echo Search::showItem($output_type, $link, $item_num, $row_num);
                } else {
                    echo Search::showItem($output_type, $value[$i]['link'], $item_num, $row_num);
                }

                if ($output_type == Search::HTML_OUTPUT) { // HTML display
                    $link = "";
                    if ($value[$i]['id'] > 0) {
                        $url = 'stat.graph.php?' . Toolbox::append_params(
                            [
                                'date1'    => $date1,
                                'date2'    => $date2,
                                'itemtype' => $itemtype,
                                'type'     => $type,
                                'champ'    => $value2,
                            ],
                            '&amp;'
                        );
                        $link = "<a href='$url'>" .
                          "<img src='" . $CFG_GLPI["root_doc"] . "/pics/stats_item.png' alt=''>" .
                          "</a>";
                    }
                    echo Search::showItem($output_type, $link, $item_num, $row_num);
                }

               //le nombre d'intervention - the number of intervention
                $opened    = self::constructEntryValues(
                    $itemtype,
                    "inter_total",
                    $date1,
                    $date2,
                    $type,
                    $value[$i]["id"],
                    $value2
                );
                $nb_opened = array_sum($opened);
                echo Search::showItem($output_type, $nb_opened, $item_num, $row_num);

               //le nombre d'intervention resolues - the number of solved intervention
                $solved    = self::constructEntryValues(
                    $itemtype,
                    "inter_solved",
                    $date1,
                    $date2,
                    $type,
                    $value[$i]["id"],
                    $value2
                );
                $nb_solved = array_sum($solved);
                echo Search::showItem($output_type, $nb_solved, $item_num, $row_num);

               //le nombre d'intervention resolues - the number of solved intervention
                $solved_late    = self::constructEntryValues(
                    $itemtype,
                    "inter_solved_late",
                    $date1,
                    $date2,
                    $type,
                    $value[$i]["id"],
                    $value2
                );
                $nb_solved_late = array_sum($solved_late);
                echo Search::showItem($output_type, $nb_solved_late, $item_num, $row_num);

               //le nombre d'intervention closes - the number of closed intervention
                $closed    = self::constructEntryValues(
                    $itemtype,
                    "inter_closed",
                    $date1,
                    $date2,
                    $type,
                    $value[$i]["id"],
                    $value2
                );
                $nb_closed = array_sum($closed);

                echo Search::showItem($output_type, $nb_closed, $item_num, $row_num);

                if ($itemtype == 'Ticket') {
                     //Satisfaction open
                     $opensatisfaction    = self::constructEntryValues(
                         $itemtype,
                         "inter_opensatisfaction",
                         $date1,
                         $date2,
                         $type,
                         $value[$i]["id"],
                         $value2
                     );
                     $nb_opensatisfaction = array_sum($opensatisfaction);
                     echo Search::showItem($output_type, $nb_opensatisfaction, $item_num, $row_num);

                     //Satisfaction answer
                     $answersatisfaction    = self::constructEntryValues(
                         $itemtype,
                         "inter_answersatisfaction",
                         $date1,
                         $date2,
                         $type,
                         $value[$i]["id"],
                         $value2
                     );
                    $nb_answersatisfaction = array_sum($answersatisfaction);
                    echo Search::showItem($output_type, $nb_answersatisfaction, $item_num, $row_num);

                    //Satisfaction rate
                    $satisfaction = self::constructEntryValues(
                        $itemtype,
                        "inter_avgsatisfaction",
                        $date1,
                        $date2,
                        $type,
                        $value[$i]["id"],
                        $value2
                    );
                    foreach (array_keys($satisfaction) as $key2) {
                          $satisfaction[$key2] *= $answersatisfaction[$key2];
                    }
                    if ($nb_answersatisfaction > 0) {
                        $avgsatisfaction = round(array_sum($satisfaction) / $nb_answersatisfaction, 1);
                        if ($output_type == Search::HTML_OUTPUT) {
                            // Display using the max number of stars defined in the root entity
                            $max_rate = Entity::getUsedConfig(
                                'inquest_config',
                                0,
                                'inquest_max_rate' . TicketSatisfaction::getConfigSufix()
                            );
                            if (!$max_rate) {
                                $max_rate = 5;
                            }
                            // Scale satisfaction accordingly
                            $avgsatisfaction = $avgsatisfaction * ($max_rate / 5);
                            $avgsatisfaction = TicketSatisfaction::displaySatisfaction($avgsatisfaction, 0);
                        }
                    } else {
                        $avgsatisfaction = '&nbsp;';
                    }
                    echo Search::showItem($output_type, $avgsatisfaction, $item_num, $row_num);

                    //Le temps moyen de prise en compte du ticket - The average time to take a ticket into account
                    $data = self::constructEntryValues(
                        $itemtype,
                        "inter_avgtakeaccount",
                        $date1,
                        $date2,
                        $type,
                        $value[$i]["id"],
                        $value2
                    );
                    foreach (array_keys($data) as $key2) {
                          $data[$key2] *= $solved[$key2];
                    }

                    if ($nb_solved > 0) {
                        $timedisplay = array_sum($data) / $nb_solved;
                    } else {
                        $timedisplay = 0;
                    }

                    if (
                        ($output_type == Search::HTML_OUTPUT)
                        || ($output_type == Search::PDF_OUTPUT_LANDSCAPE)
                        || ($output_type == Search::PDF_OUTPUT_PORTRAIT)
                    ) {
                        $timedisplay = Html::timestampToString($timedisplay, 0, false);
                    } else if ($output_type == Search::CSV_OUTPUT) {
                        $timedisplay = Html::timestampToCsvString($timedisplay);
                    }
                    echo Search::showItem($output_type, $timedisplay, $item_num, $row_num);
                }

              //Le temps moyen de resolution - The average time to resolv
                $data = self::constructEntryValues(
                    $itemtype,
                    "inter_avgsolvedtime",
                    $date1,
                    $date2,
                    $type,
                    $value[$i]["id"],
                    $value2
                );
                foreach (array_keys($data) as $key2) {
                    $data[$key2] = round($data[$key2] * $solved[$key2]);
                }

                if ($nb_solved > 0) {
                    $timedisplay = array_sum($data) / $nb_solved;
                } else {
                    $timedisplay = 0;
                }
                if (
                    ($output_type == Search::HTML_OUTPUT)
                    || ($output_type == Search::PDF_OUTPUT_LANDSCAPE)
                    || ($output_type == Search::PDF_OUTPUT_PORTRAIT)
                ) {
                    $timedisplay = Html::timestampToString($timedisplay, 0, false);
                } else if ($output_type == Search::CSV_OUTPUT) {
                    $timedisplay = Html::timestampToCsvString($timedisplay);
                }
                echo Search::showItem($output_type, $timedisplay, $item_num, $row_num);

              //Le temps moyen de cloture - The average time to close
                $data = self::constructEntryValues(
                    $itemtype,
                    "inter_avgclosedtime",
                    $date1,
                    $date2,
                    $type,
                    $value[$i]["id"],
                    $value2
                );
                foreach (array_keys($data) as $key2) {
                    $data[$key2] = round($data[$key2] * $solved[$key2]);
                }

                if ($nb_closed > 0) {
                    $timedisplay = array_sum($data) / $nb_closed;
                } else {
                    $timedisplay = 0;
                }
                if (
                    ($output_type == Search::HTML_OUTPUT)
                    || ($output_type == Search::PDF_OUTPUT_LANDSCAPE)
                    || ($output_type == Search::PDF_OUTPUT_PORTRAIT)
                ) {
                    $timedisplay = Html::timestampToString($timedisplay, 0, false);
                } else if ($output_type == Search::CSV_OUTPUT) {
                    $timedisplay = Html::timestampToCsvString($timedisplay);
                }
                echo Search::showItem($output_type, $timedisplay, $item_num, $row_num);

              //the number of solved interventions with a duration time
                $solved_with_actiontime = self::constructEntryValues(
                    $itemtype,
                    "inter_solved_with_actiontime",
                    $date1,
                    $date2,
                    $type,
                    $value[$i]["id"],
                    $value2
                );
                $nb_solved_with_actiontime = array_sum($solved_with_actiontime);

              //Le temps moyen de l'intervention reelle - The average actiontime to resolv
                $data = self::constructEntryValues(
                    $itemtype,
                    "inter_avgactiontime",
                    $date1,
                    $date2,
                    $type,
                    $value[$i]["id"],
                    $value2
                );
                foreach (array_keys($data) as $key2) {
                    if (isset($solved_with_actiontime[$key2])) {
                        $data[$key2] *= $solved_with_actiontime[$key2];
                    } else {
                        $data[$key2] *= 0;
                    }
                }
                $total_actiontime = array_sum($data);

                if ($nb_solved_with_actiontime > 0) {
                    $timedisplay = $total_actiontime / $nb_solved_with_actiontime;
                } else {
                    $timedisplay = 0;
                }

                if (
                    ($output_type == Search::HTML_OUTPUT)
                    || ($output_type == Search::PDF_OUTPUT_LANDSCAPE)
                    || ($output_type == Search::PDF_OUTPUT_PORTRAIT)
                ) {
                    $timedisplay = Html::timestampToString($timedisplay, 0, false);
                } else if ($output_type == Search::CSV_OUTPUT) {
                    $timedisplay = Html::timestampToCsvString($timedisplay);
                }
                echo Search::showItem($output_type, $timedisplay, $item_num, $row_num);
              //Le temps total de l'intervention reelle - The total actiontime to resolv
                $timedisplay = $total_actiontime;

                if (
                    ($output_type == Search::HTML_OUTPUT)
                    || ($output_type == Search::PDF_OUTPUT_LANDSCAPE)
                    || ($output_type == Search::PDF_OUTPUT_PORTRAIT)
                ) {
                    $timedisplay = Html::timestampToString($timedisplay, 0, false);
                } else if ($output_type == Search::CSV_OUTPUT) {
                    $timedisplay = Html::timestampToCsvString($timedisplay);
                }
                echo Search::showItem($output_type, $timedisplay, $item_num, $row_num);

                echo Search::showEndLine($output_type);
            }
          // Display footer
            echo Search::showFooter($output_type, '', $numrows);
        } else {
            echo __('No statistics are available');
        }

        if ($output_type == Search::HTML_OUTPUT) { // HTML display
            echo "</div>";
        }
    }


    /**
     * @param $itemtype
     * @param $type
     * @param $begin              (default '')
     * @param $end                (default '')
     * @param $param              (default '')
     * @param $value              (default '')
     * @param $value2             (default '')
     * @param $add_criteria          (default [''])
     */
    public static function constructEntryValues(
        $itemtype,
        $type,
        $begin = "",
        $end = "",
        $param = "",
        $value = "",
        $value2 = "",
        array $add_criteria = []
    ) {
        $DB = \DBConnection::getReadConnection();

        if (!$item = getItemForItemtype($itemtype)) {
            return;
        }
        /** @var CommonITILObject $item */
        $table          = $item->getTable();
        $fkfield        = $item->getForeignKeyField();

        if (!($userlinkclass = getItemForItemtype($item->userlinkclass))) {
            return;
        }
        $userlinktable  = $userlinkclass->getTable();
        if (!$grouplinkclass = getItemForItemtype($item->grouplinkclass)) {
            return;
        }
        $grouplinktable = $grouplinkclass->getTable();

        if (!($supplierlinkclass = getItemForItemtype($item->supplierlinkclass))) {
            return;
        }
        $supplierlinktable = $supplierlinkclass->getTable();

        $tasktable      = getTableForItemType($item->getType() . 'Task');

        $closed_status  = $item->getClosedStatusArray();
        $solved_status  = array_merge($closed_status, $item->getSolvedStatusArray());

        $criteria = [];
        $WHERE = [];
        if ($item->maybeDeleted()) {
            $WHERE["$table.is_deleted"] = 0;
        }
        $WHERE += getEntitiesRestrictCriteria($table);
        $LEFTJOIN          = [];
        $INNERJOIN         = [];
        $LEFTJOINUSER      = [
            $userlinktable => [
                'ON' => [
                    $userlinktable => $fkfield,
                    $table         => 'id'
                ]
            ]
        ];
        $LEFTJOINGROUP    = [
            $grouplinktable => [
                'ON' => [
                    $grouplinktable   => $fkfield,
                    $table            => 'id'
                ]
            ]
        ];
        $LEFTJOINSUPPLIER = [
            $supplierlinktable => [
                'ON' => [
                    $supplierlinktable   => $fkfield,
                    $table               => 'id'
                ]
            ]
        ];

        switch ($param) {
            case "technicien":
                $LEFTJOIN = $LEFTJOINUSER;
                $WHERE["$userlinktable.users_id"] = $value;
                $WHERE["$userlinktable.type"] = CommonITILActor::ASSIGN;
                break;

            case "technicien_followup":
                $WHERE["$tasktable.users_id"] = $value;
                $LEFTJOIN = [
                    $tasktable => [
                        'ON' => [
                            $tasktable  => $fkfield,
                            $table      => 'id'
                        ]
                    ]
                ];
                break;

            case "user":
                $LEFTJOIN = $LEFTJOINUSER;
                $WHERE["$userlinktable.users_id"] = $value;
                $WHERE["$userlinktable.type"] = CommonITILActor::REQUESTER;
                break;

            case "usertitles_id":
                $LEFTJOIN  = $LEFTJOINUSER;
                $LEFTJOIN['glpi_users'] = [
                    'ON' => [
                        $userlinktable => 'users_id',
                        'glpi_users'   => 'id'
                    ]
                ];
                $WHERE["glpi_users.usertitles_id"] = $value;
                $WHERE["$userlinktable.type"] = CommonITILActor::REQUESTER;
                break;

            case "usercategories_id":
                $LEFTJOIN  = $LEFTJOINUSER;
                $LEFTJOIN['glpi_users'] = [
                    'ON' => [
                        $userlinktable => 'users_id',
                        'glpi_users'   => 'id'
                    ]
                ];
                $WHERE["glpi_users.usercategories_id"] = $value;
                $WHERE["$userlinktable.type"] = CommonITILActor::REQUESTER;
                break;

            case "itilcategories_tree":
                if ($value == $value2) {
                    $categories = [$value];
                } else {
                    $categories = getSonsOf("glpi_itilcategories", $value);
                }
                $WHERE["$table.itilcategories_id"] = $categories;
                break;

            case 'locations_tree':
                if ($value == $value2) {
                    $locations = [$value];
                } else {
                    $locations = getSonsOf('glpi_locations', $value);
                }
                $WHERE["$table.locations_id"] = $locations;
                break;

            case 'group_tree':
            case 'groups_tree_assign':
                $grptype = (($param == 'group_tree') ? CommonITILActor::REQUESTER
                                                 : CommonITILActor::ASSIGN);
                if ($value == $value2) {
                     $groups = [$value];
                } else {
                    $groups = getSonsOf("glpi_groups", $value);
                }

                $LEFTJOIN  = $LEFTJOINGROUP;
                $WHERE["$grouplinktable.groups_id"] = $groups;
                $WHERE["$grouplinktable.type"] = $grptype;
                break;

            case "group":
                $LEFTJOIN = $LEFTJOINGROUP;
                $WHERE["$grouplinktable.groups_id"] = $value;
                $WHERE["$grouplinktable.type"] = CommonITILActor::REQUESTER;
                break;

            case "groups_id_assign":
                $LEFTJOIN = $LEFTJOINGROUP;
                $WHERE["$grouplinktable.groups_id"] = $value;
                $WHERE["$grouplinktable.type"] = CommonITILActor::ASSIGN;
                break;

            case "suppliers_id_assign":
                $LEFTJOIN = $LEFTJOINSUPPLIER;
                $WHERE["$supplierlinktable.suppliers_id"] = $value;
                $WHERE["$supplierlinktable.type"] = CommonITILActor::ASSIGN;
                break;

            case "requesttypes_id":
            case "urgency":
            case "impact":
            case "priority":
            case "users_id_recipient":
            case "type":
            case "itilcategories_id":
            case 'locations_id':
                $WHERE["$table.$param"] = $value;
                break;

            case "solutiontypes_id":
                $LEFTJOIN = [
                    'glpi_itilsolutions' => [
                        'ON' => [
                            'glpi_itilsolutions'   => 'items_id',
                            'glpi_tickets'               => 'id', [
                                'AND' => [
                                    'glpi_itilsolutions.itemtype' => 'Ticket'
                                ]
                            ]
                        ]
                    ]
                ];
                $WHERE["glpi_itilsolutions.$param"] = $value;
                break;

            case "device":
                $devtable = getTableForItemType('Item_' . $value2);
                $fkname   = getForeignKeyFieldForTable(getTableForItemType($value2));
               //select computers IDs that are using this device;
                $linkedtable = $table;
                if ($itemtype == 'Ticket') {
                    $linkedtable = 'glpi_items_tickets';
                    $LEFTJOIN = [
                        'glpi_items_tickets' => [
                            'ON' => [
                                'glpi_items_tickets' => 'tickets_id',
                                'glpi_tickets'       => 'id', [
                                    'AND' => [
                                        "$linkedtable.itemtype" => 'Computer'
                                    ]
                                ]
                            ]
                        ]
                    ];
                }
                $INNERJOIN = [
                    'glpi_computers'  => [
                        'ON' => [
                            'glpi_computers'  => 'id',
                            $linkedtable      => 'items_id'
                        ]
                    ],
                    $devtable         => [
                        'ON' => [
                            'glpi_computers'  => 'id',
                            $devtable         => 'items_id', [
                                'AND' => [
                                    "$devtable.itemtype" => Computer::class,
                                    "$devtable.$fkname" => $value
                                ]
                            ]
                        ]
                    ]
                ];

                $WHERE["glpi_computers.is_template"] = 0;
                break;

            case "comp_champ":
                $ftable   = getTableForItemType($value2);
                $champ    = getForeignKeyFieldForTable($ftable);
                $linkedtable = $table;
                if ($itemtype == 'Ticket') {
                    $linkedtable = 'glpi_items_tickets';
                    $LEFTJOIN = [
                        'glpi_items_tickets' => [
                            'ON' => [
                                'glpi_items_tickets' => 'tickets_id',
                                'glpi_tickets'       => 'id', [
                                    'AND' => [
                                        "$linkedtable.itemtype" => 'Computer'
                                    ]
                                ]
                            ]
                        ]
                    ];
                }
                $INNERJOIN = [
                    'glpi_computers' => [
                        'ON' => [
                            'glpi_computers'  => 'id',
                            $linkedtable      => 'items_id'
                        ]
                    ]
                ];

                $WHERE["glpi_computers.is_template"] = 0;
                if (substr($champ, 0, strlen('operatingsystem')) === 'operatingsystem') {
                    $INNERJOIN['glpi_items_operatingsystems'] = [
                        'ON' => [
                            'glpi_computers'              => 'id',
                            'glpi_items_operatingsystems' => 'items_id', [
                                'AND' => [
                                    "glpi_items_operatingsystems.itemtype" => 'Computer'
                                ]
                            ]
                        ]
                    ];
                    $WHERE["glpi_items_operatingsystems.$champ"] = $value;
                } else {
                    $WHERE["glpi_computers.$champ"] = $value;
                }
                break;
        }

        switch ($type) {
            case "inter_total":
                $WHERE[] = getDateCriteria("$table.date", $begin, $end);

                $date_unix = new QueryExpression(
                    "FROM_UNIXTIME(UNIX_TIMESTAMP(" . $DB->quoteName("$table.date") . "),'%Y-%m') AS " . $DB->quoteName('date_unix')
                );

                $criteria = [
                    'SELECT'    => [
                        $date_unix,
                        'COUNT DISTINCT' => "$table.id AS total_visites"
                    ],
                    'FROM'      => $table,
                    'WHERE'     => $WHERE,
                    'GROUPBY'   => 'date_unix',
                    'ORDERBY'   => "$table.date"
                ];
                break;

            case "inter_solved":
                $WHERE["$table.status"] = $solved_status;
                $WHERE[] = ['NOT' => ["$table.solvedate" => null]];
                $WHERE[] = getDateCriteria("$table.solvedate", $begin, $end);

                $date_unix = new QueryExpression(
                    "FROM_UNIXTIME(UNIX_TIMESTAMP(" . $DB->quoteName("$table.solvedate") . "),'%Y-%m') AS " . $DB->quoteName('date_unix')
                );

                $criteria = [
                    'SELECT'    => [
                        $date_unix,
                        'COUNT DISTINCT'  => "$table.id AS total_visites"
                    ],
                    'FROM'      => $table,
                    'WHERE'     => $WHERE,
                    'GROUPBY'   => 'date_unix',
                    'ORDERBY'   => "$table.solvedate"
                ];
                break;

            case "inter_solved_late":
                $WHERE["$table.status"] = $solved_status;
                $WHERE[] = [
                    'NOT' => [
                        "$table.solvedate"         => null,
                        "$table.time_to_resolve"   => null
                    ]
                ];
                $WHERE[] = getDateCriteria("$table.solvedate", $begin, $end);
                $WHERE[] = new QueryExpression("$table.solvedate > $table.time_to_resolve");

                $date_unix = new QueryExpression(
                    "FROM_UNIXTIME(UNIX_TIMESTAMP(" . $DB->quoteName("$table.solvedate") . "),'%Y-%m') AS " . $DB->quoteName('date_unix')
                );

                $criteria = [
                    'SELECT'    => [
                        $date_unix,
                        'COUNT DISTINCT'  => "$table.id AS total_visites"
                    ],
                    'FROM'      => $table,
                    'WHERE'     => $WHERE,
                    'GROUPBY'   => 'date_unix',
                    'ORDERBY'   => "$table.solvedate"
                ];
                break;

            case "inter_closed":
                $WHERE["$table.status"] = $closed_status;
                $WHERE[] = ['NOT' => ["$table.closedate" => null]];
                $WHERE[] = getDateCriteria("$table.closedate", $begin, $end);

                $date_unix = new QueryExpression(
                    "FROM_UNIXTIME(UNIX_TIMESTAMP(" . $DB->quoteName("$table.closedate") . "),'%Y-%m') AS " . $DB->quoteName('date_unix')
                );

                $criteria = [
                    'SELECT'    => [
                        $date_unix,
                        'COUNT DISTINCT'  => "$table.id AS total_visites"
                    ],
                    'FROM'      => $table,
                    'WHERE'     => $WHERE,
                    'GROUPBY'   => 'date_unix',
                    'ORDERBY'   => "$table.closedate"
                ];
                break;

            case "inter_solved_with_actiontime":
                $WHERE["$table.status"] = $solved_status;
                $WHERE["$table.actiontime"] = ['>', 0];
                $WHERE[] = ['NOT' => ["$table.solvedate" => null]];
                $WHERE[] = getDateCriteria("$table.solvedate", $begin, $end);

                $date_unix = new QueryExpression(
                    "FROM_UNIXTIME(UNIX_TIMESTAMP(" . $DB->quoteName("$table.solvedate") . "),'%Y-%m') AS " . $DB->quoteName('date_unix')
                );

                $criteria = [
                    'SELECT'    => [
                        $date_unix,
                        'COUNT DISTINCT'  => "$table.id AS total_visites"
                    ],
                    'FROM'      => $table,
                    'WHERE'     => $WHERE,
                    'GROUPBY'   => 'date_unix',
                    'ORDERBY'   => "$table.solvedate"
                ];
                break;

            case "inter_avgsolvedtime":
                $WHERE["$table.status"] = $solved_status;
                $WHERE[] = ['NOT' => ["$table.solvedate" => null]];
                $WHERE[] = getDateCriteria("$table.solvedate", $begin, $end);

                $date_unix = new QueryExpression(
                    "FROM_UNIXTIME(UNIX_TIMESTAMP(" . $DB->quoteName("$table.solvedate") . "),'%Y-%m') AS " . $DB->quoteName('date_unix')
                );

                $criteria = [
                    'SELECT'    => [
                        $date_unix,
                        'AVG' => "solve_delay_stat AS total_visites"
                    ],
                    'FROM'      => $table,
                    'WHERE'     => $WHERE,
                    'GROUPBY'   => 'date_unix',
                    'ORDERBY'   => "$table.solvedate"
                ];
                break;

            case "inter_avgclosedtime":
                $WHERE["$table.status"] = $closed_status;
                $WHERE[] = ['NOT' => ["$table.closedate" => null]];
                $WHERE[] = getDateCriteria("$table.closedate", $begin, $end);

                $date_unix = new QueryExpression(
                    "FROM_UNIXTIME(UNIX_TIMESTAMP(" . $DB->quoteName("$table.closedate") . "),'%Y-%m') AS " . $DB->quoteName('date_unix')
                );

                $criteria = [
                    'SELECT'    => [
                        $date_unix,
                        'AVG'  => "close_delay_stat AS total_visites"
                    ],
                    'FROM'      => $table,
                    'WHERE'     => $WHERE,
                    'GROUPBY'   => 'date_unix',
                    'ORDERBY'   => "$table.closedate"
                ];
                break;

            case "inter_avgactiontime":
                if ($param == "technicien_followup") {
                    $actiontime_table = $tasktable;
                } else {
                    $actiontime_table = $table;
                }
                $WHERE["$actiontime_table.actiontime"] = ['>', 0];
                $WHERE[] = getDateCriteria("$table.solvedate", $begin, $end);

                $date_unix = new QueryExpression(
                    "FROM_UNIXTIME(UNIX_TIMESTAMP(" . $DB->quoteName("$table.solvedate") . "),'%Y-%m') AS " . $DB->quoteName('date_unix')
                );

                $criteria = [
                    'SELECT'    => [
                        $date_unix,
                        'AVG'  => "$actiontime_table.actiontime AS total_visites"
                    ],
                    'FROM'      => $table,
                    'WHERE'     => $WHERE,
                    'GROUPBY'   => 'date_unix',
                    'ORDERBY'   => "$table.solvedate"
                ];
                break;

            case "inter_avgtakeaccount":
                $WHERE["$table.status"] = $solved_status;
                $WHERE[] = ['NOT' => ["$table.solvedate" => null]];
                $WHERE[] = getDateCriteria("$table.solvedate", $begin, $end);

                $date_unix = new QueryExpression(
                    "FROM_UNIXTIME(UNIX_TIMESTAMP(" . $DB->quoteName("$table.solvedate") . "),'%Y-%m') AS " . $DB->quoteName('date_unix')
                );

                $criteria = [
                    'SELECT'    => [
                        $date_unix,
                        'AVG'  => "$table.takeintoaccount_delay_stat AS total_visites"
                    ],
                    'FROM'      => $table,
                    'WHERE'     => $WHERE,
                    'GROUPBY'   => 'date_unix',
                    'ORDERBY'   => "$table.solvedate"
                ];
                break;

            case "inter_opensatisfaction":
                $WHERE["$table.status"] = $closed_status;
                $WHERE[] = ['NOT' => ["$table.closedate" => null]];
                $WHERE[] = getDateCriteria("$table.closedate", $begin, $end);

                $date_unix = new QueryExpression(
                    "FROM_UNIXTIME(UNIX_TIMESTAMP(" . $DB->quoteName("$table.closedate") . "),'%Y-%m') AS " . $DB->quoteName('date_unix')
                );

                $INNERJOIN['glpi_ticketsatisfactions'] = [
                    'ON' => [
                        'glpi_ticketsatisfactions' => 'tickets_id',
                        $table                     => 'id'
                    ]
                ];

                $criteria = [
                    'SELECT'    => [
                        $date_unix,
                        'COUNT DISTINCT'  => "$table.id AS total_visites"
                    ],
                    'FROM'      => $table,
                    'WHERE'     => $WHERE,
                    'GROUPBY'   => 'date_unix',
                    'ORDERBY'   => "$table.closedate"
                ];
                break;

            case "inter_answersatisfaction":
                $WHERE["$table.status"] = $closed_status;
                $WHERE[] = [
                    ['NOT' => ["$table.closedate" => null]],
                    ['NOT' => ["glpi_ticketsatisfactions.date_answered"  => null]],
                ];

                $WHERE[] = getDateCriteria("$table.closedate", $begin, $end);

                $date_unix = new QueryExpression(
                    "FROM_UNIXTIME(UNIX_TIMESTAMP(" . $DB->quoteName("$table.closedate") . "),'%Y-%m') AS " . $DB->quoteName('date_unix')
                );

                $INNERJOIN['glpi_ticketsatisfactions'] = [
                    'ON' => [
                        'glpi_ticketsatisfactions' => 'tickets_id',
                        $table                     => 'id'
                    ]
                ];

                $criteria = [
                    'SELECT'    => [
                        $date_unix,
                        'COUNT DISTINCT'  => "$table.id AS total_visites"
                    ],
                    'FROM'      => $table,
                    'WHERE'     => $WHERE,
                    'GROUPBY'   => 'date_unix',
                    'ORDERBY'   => "$table.closedate"
                ];
                break;

            case "inter_avgsatisfaction":
                $WHERE["$table.status"] = $closed_status;
                $WHERE[] = [
                    'NOT' => [
                        "$table.closedate" => null,
                        "glpi_ticketsatisfactions.date_answered" => null
                    ]
                ];
                $WHERE[] = getDateCriteria("$table.closedate", $begin, $end);

                $date_unix = new QueryExpression(
                    "FROM_UNIXTIME(UNIX_TIMESTAMP(" . $DB->quoteName("$table.closedate") . "),'%Y-%m') AS " . $DB->quoteName('date_unix')
                );

                $INNERJOIN['glpi_ticketsatisfactions'] = [
                    'ON' => [
                        'glpi_ticketsatisfactions' => 'tickets_id',
                        $table                     => 'id'
                    ]
                ];

                $criteria = [
                    'SELECT'    => [
                        $date_unix,
                        'AVG'  => "glpi_ticketsatisfactions.satisfaction_scaled_to_5 AS total_visites"
                    ],
                    'FROM'      => $table,
                    'WHERE'     => $WHERE,
                    'GROUPBY'   => 'date_unix',
                    'ORDERBY'   => "$table.closedate"
                ];
                break;
        }

        if (count($LEFTJOIN)) {
            $criteria['LEFT JOIN'] = $LEFTJOIN;
        }

        if (count($INNERJOIN)) {
            $criteria['INNER JOIN'] = $INNERJOIN;
        }

        $entrees = [];
        if (!count($criteria)) {
            return [];
        }

        if (count($add_criteria)) {
            $criteria = array_merge_recursive($criteria, $add_criteria);
        }

        $iterator = $DB->request($criteria);
        foreach ($iterator as $row) {
            $date             = $row['date_unix'];
           //$visites = round($row['total_visites']);
            $entrees["$date"] = $row['total_visites'];
        }

        $end_time   = strtotime(date("Y-m", strtotime($end)) . "-01");
        $begin_time = strtotime(date("Y-m", strtotime($begin)) . "-01");

        $current = $begin_time;

        while ($current <= $end_time) {
            $curentry = date("Y-m", $current);
            if (!isset($entrees["$curentry"])) {
                $entrees["$curentry"] = 0;
            }
            $month   = date("m", $current);
            $year    = date("Y", $current);
            $current = mktime(0, 0, 0, intval($month) + 1, 1, intval($year));
        }
        ksort($entrees);

        return $entrees;
    }

    /**
     * @param string $target
     * @param string $date1
     * @param string $date2
     * @param int $start
     **/
    public static function showItems($target, $date1, $date2, $start)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $view_entities = Session::isMultiEntitiesMode();

        if ($view_entities) {
            $entities = getAllDataFromTable('glpi_entities');
        }

        $output_type = Search::HTML_OUTPUT;
        if (isset($_GET["display_type"])) {
            $output_type = $_GET["display_type"];
        }
        if (empty($date2)) {
            $date2 = date("Y-m-d");
        }
        $date2 .= " 23:59:59";

       // 1 an par defaut
        if (empty($date1)) {
            $date1 = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d"), date("Y") - 1));
        }
        $date1 .= " 00:00:00";

        $iterator = $DB->request([
            'SELECT' => [
                'glpi_items_tickets.itemtype',
                'glpi_items_tickets.items_id',
                'COUNT'  => '* AS NB'
            ],
            'FROM'   => 'glpi_tickets',
            'LEFT JOIN' => [
                'glpi_items_tickets' => [
                    'ON' => [
                        'glpi_items_tickets' => 'tickets_id',
                        'glpi_tickets'       => 'id'
                    ]
                ]
            ],
            'WHERE'  => [
                'date'                        => ['<=', $date2],
                'glpi_tickets.date'           => ['>=', $date1],
                'glpi_items_tickets.itemtype' => ['<>', ''],
                'glpi_items_tickets.items_id' => ['>', 0]
            ] + getEntitiesRestrictCriteria('glpi_tickets'),
            'GROUP'  => [
                'glpi_items_tickets.itemtype',
                'glpi_items_tickets.items_id'
            ],
            'ORDER'  => 'NB DESC'
        ]);
        $numrows = count($iterator);

        if ($numrows > 0) {
            if ($output_type == Search::HTML_OUTPUT) {
                Html::printPager(
                    $start,
                    $numrows,
                    $target,
                    Toolbox::append_params(
                        [
                            'date1'     => $date1,
                            'date2'     => $date2,
                            'type'      => 'hardwares',
                            'start'     => $start,
                        ],
                        '&amp;'
                    ),
                    'Stat'
                );
                echo "<div class='center'>";
            }

            $end_display = $start + $_SESSION['glpilist_limit'];
            if (isset($_GET['export_all'])) {
                $end_display = $numrows;
            }
            echo Search::showHeader($output_type, $end_display - $start + 1, 2, 1);
            $header_num = 1;
            echo Search::showNewLine($output_type);
            echo Search::showHeaderItem($output_type, _n('Associated element', 'Associated elements', Session::getPluralNumber()), $header_num);
            if ($view_entities) {
                echo Search::showHeaderItem($output_type, Entity::getTypeName(1), $header_num);
            }
            echo Search::showHeaderItem($output_type, __('Number of tickets'), $header_num);
            echo Search::showEndLine($output_type);

            $i = $start;
            if (isset($_GET['export_all'])) {
                $start = 0;
            }

            $i = $start;
            foreach ($iterator as $data) {
                $item_num = 1;
               // Get data and increment loop variables
                if (!($item = getItemForItemtype($data["itemtype"]))) {
                    continue;
                }
                if ($item->getFromDB($data["items_id"])) {
                    echo Search::showNewLine($output_type, $i % 2);
                    echo Search::showItem(
                        $output_type,
                        sprintf(
                            __('%1$s - %2$s'),
                            $item->getTypeName(),
                            $item->getLink()
                        ),
                        $item_num,
                        $i - $start + 1,
                        "class='center'" . " " . ($item->isDeleted() ? " class='deleted' "
                        : "")
                    );
                    if ($view_entities) {
                          $ent = $item->getEntityID();
                          $ent = $entities[$ent]['completename'];
                          echo Search::showItem(
                              $output_type,
                              $ent,
                              $item_num,
                              $i - $start + 1,
                              "class='center'" . " " . ($item->isDeleted() ? " class='deleted' "
                              : "")
                          );
                    }
                    echo Search::showItem(
                        $output_type,
                        $data["NB"],
                        $item_num,
                        $i - $start + 1,
                        "class='center'" . " " . ($item->isDeleted() ? " class='deleted' "
                        : "")
                    );
                }

                $i++;
                if ($i == $end_display) {
                    break;
                }
            }

            echo Search::showFooter($output_type);
            if ($output_type == Search::HTML_OUTPUT) {
                echo "</div>";
            }
        }
    }


    /**
     * @since 0.84
     **/
    public static function title()
    {
        /**
         * @var array $CFG_GLPI
         * @var array $PLUGIN_HOOKS
         */
        global $CFG_GLPI, $PLUGIN_HOOKS;

        $opt_list["Ticket"]                             = __('Tickets');

        $stat_list = [];

        $stat_list["Ticket"]["Ticket_Global"]["name"]   = __('Global');
        $stat_list["Ticket"]["Ticket_Global"]["file"]   = "stat.global.php?itemtype=Ticket";
        $stat_list["Ticket"]["Ticket_Ticket"]["name"]   = __('By ticket');
        $stat_list["Ticket"]["Ticket_Ticket"]["file"]   = "stat.tracking.php?itemtype=Ticket";
        $stat_list["Ticket"]["Ticket_Location"]["name"] = __('By hardware characteristics');
        $stat_list["Ticket"]["Ticket_Location"]["file"] = "stat.location.php?itemtype=Ticket";
        $stat_list["Ticket"]["Ticket_Item"]["name"]     = __('By hardware');
        $stat_list["Ticket"]["Ticket_Item"]["file"]     = "stat.item.php";

        if (Problem::canView()) {
            $opt_list["Problem"]                               = Problem::getTypeName(Session::getPluralNumber());

            $stat_list["Problem"]["Problem_Global"]["name"]    = __('Global');
            $stat_list["Problem"]["Problem_Global"]["file"]    = "stat.global.php?itemtype=Problem";
            $stat_list["Problem"]["Problem_Problem"]["name"]   = __('By problem');
            $stat_list["Problem"]["Problem_Problem"]["file"]   = "stat.tracking.php?itemtype=Problem";
        }

        if (Change::canView()) {
            $opt_list["Change"]                             = _n('Change', 'Changes', Session::getPluralNumber());

            $stat_list["Change"]["Change_Global"]["name"]   = __('Global');
            $stat_list["Change"]["Change_Global"]["file"]   = "stat.global.php?itemtype=Change";
            $stat_list["Change"]["Change_Change"]["name"]   = __('By change');
            $stat_list["Change"]["Change_Change"]["file"]   = "stat.tracking.php?itemtype=Change";
        }

        $values   = [$CFG_GLPI["root_doc"] . '/front/stat.php' => Dropdown::EMPTY_VALUE];

        $selected = -1;
        foreach ($opt_list as $opt => $group) {
            foreach ($stat_list[$opt] as $data) {
                $name    = $data['name'];
                $file    = $data['file'];
                $key                  = $CFG_GLPI["root_doc"] . "/front/" . $file;
                $values[$group][$key] = $name;
                if (stripos($_SERVER['REQUEST_URI'], $key) !== false) {
                    $selected = $key;
                }
            }
        }

       // Manage plugins
        $names    = [];
        $optgroup = [];
        if (isset($PLUGIN_HOOKS["stats"]) && is_array($PLUGIN_HOOKS["stats"])) {
            foreach ($PLUGIN_HOOKS["stats"] as $plug => $pages) {
                if (!Plugin::isPluginActive($plug)) {
                    continue;
                }
                if (is_array($pages) && count($pages)) {
                    foreach ($pages as $page => $name) {
                        $names[Plugin::getWebDir($plug, false) . '/' . $page] = ["name" => $name,
                            "plug" => $plug
                        ];
                        $optgroup[$plug] = Plugin::getInfo($plug, 'name');
                    }
                }
            }
            asort($names);
        }

        foreach ($optgroup as $opt => $title) {
            $group = $title;
            foreach ($names as $key => $val) {
                if ($opt == $val["plug"]) {
                    $file                  = $CFG_GLPI["root_doc"] . "/" . $key;
                    $values[$group][$file] = $val["name"];
                    if (stripos($_SERVER['REQUEST_URI'], $file) !== false) {
                        $selected = $file;
                    }
                }
            }
        }

        TemplateRenderer::getInstance()->display('pages/assistance/stats/title.html.twig', [
            'values'   => $values,
            'selected' => $selected
        ]);
    }


    /**
     * @since 0.85
     **/
    public function getRights($interface = 'central')
    {

        $values[READ] = __('Read');
        return $values;
    }

    /**
     * Call displayLineGraph with arguments from a StatData object
     */
    public function displayLineGraphFromData(StatData $stat_data)
    {
        if ($stat_data->isEmpty()) {
            return;
        }

        $this->displayLineGraph(
            $stat_data->getTitle(),
            $stat_data->getLabels(),
            $stat_data->getSeries(),
            $stat_data->getOptions(),
            true,
            $stat_data->getCsvLink()
        );
    }

    /**
     * Display line graph
     *
     * @param string   $title  Graph title
     * @param string[] $labels Labels to display
     * @param array    $series Series data. An array of the form:
     *                 [
     *                    ['name' => 'a name', 'data' => []],
     *                    ['name' => 'another name', 'data' => []]
     *                 ]
     * @param array    $options  Options
     * @param boolean  $display  Whether to display directly; defauts to true
     * @param string   $csv_link Link to download the dataset as csv
     *
     * @return void
     */
    public function displayLineGraph(
        $title,
        $labels,
        $series,
        $options = null,
        $display = true,
        ?string $csv_link = null
    ) {
        $param = [
            'width'   => 800,
            'height'  => 300,
            'tooltip' => true,
            'legend'  => true,
            'animate' => true,
            'csv'     => true,
            'img'     => true
        ];

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $param[$key] = $val;
            }
        }

        $slug = str_replace('-', '_', Toolbox::slugify($title));
        $this->checkEmptyLabels($labels);

        $chart_options = [
            'title' => ['text' => $title],
            'tooltip' => [
                'trigger'      => 'axis',
                'appendToBody' => true,
            ],
            'grid' => [
                'left'         => '3%',
                'right'        => '4%',
                'bottom'       => '3%',
                'containLabel' => true,
            ],
            'toolbox' => [
                'show'    => true,
                'feature' => []
            ],
            'legend' => [
                'show' => true,
            ],
            'xAxis' => [
                'type'        => 'category',
                'data'        => $labels,
                'boundaryGap' => false,
            ],
            'yAxis' => [
                'type' => 'value',
            ],
            'series' => [],
        ];

        foreach ($series as $serie) {
            $chart_options['series'][] = [
                'type' => 'line',
                'areaStyle' => [
                    'opacity' => 0.3,
                ],
                'name' => $serie['name'],
                'data' => array_values($serie['data']),
                'smooth'          => 0.4,
                'lineStyle'       => [
                    'width'  => 4
                ],
                'symbol'          => new Json_Expr(<<<JAVASCRIPT
                    function(value) {
                        return value > 0 ? 'circle': 'none';
                    }
JAVASCRIPT
                ),
                'symbolSize'      => 8,
                'legendHoverLink' => true,
            ];
        }

        if ($param['csv'] && $csv_link) {
            $chart_options['toolbox']['feature']['myCsvExport'] = [
                'icon'    => 'path://M14,3v4a1,1,0,0,0,1,1h4 M17,21h-10a2,2,0,0,1,-2,-2v-14a2,2,0,0,1,2,-2h7l5,5v11a2,2,0,0,1,-2,2z M12,17v-6 M9.5,14.5l2.5,2.5l2.5,-2.5',
                'title'   => __('Export to CSV'),
                'onclick' => new Json_Expr(<<<JAVASCRIPT
                    function () {
                        location.href = '$csv_link';
                    }
JAVASCRIPT
                ),
            ];
        }

        if ($param['img']) {
            $chart_options['toolbox']['feature']['saveAsImage'] = [
                'icon'  => 'path://M15,8L15.01,8 M7,4h10s3,0,3,3v10s0,3,-3,3h-10s-3,0,-3,-3v-10s0,-3,3,-3 M4,15l4,-4a3,5,0,0,1,3,0l5,5 M14,14l1,-1a3,5,0,0,1,3,0l2,2',
                'title' => __('Save as image'),
            ];
        }

        $height = $param['height'] . "px";
        $width  = $param['width'] . "px";
        $html = <<<HTML
        <div class="card mb-3 d-inline-flex">
            <div class="card-body">
                <div class="chart" id='$slug' ></div>
            </div>
        </div>

        <style>
        #$slug {
            width: $width;
            height: $height;
        }
        </style>
HTML;

        $chart_options_json = Json::encode($chart_options, false, ['enableJsonExprFinder' => true]);
        $js = <<<JAVASCRIPT
        $(function () {
            var myChart = echarts.init($('#{$slug}')[0]);
            myChart.setOption($chart_options_json);
        });
JAVASCRIPT;
        $js = Html::scriptBlock($js);

        $out = $html . $js;

        if ($display) {
            echo $out;
            return;
        }
        return $out;
    }

    /**
     * Call displayPieGraph with arguments from a StatData object
     */
    public function displayPieGraphFromData(StatData $stat_data)
    {
        if ($stat_data->isEmpty()) {
            return;
        }

        $this->displayPieGraph(
            $stat_data->getTitle(),
            $stat_data->getLabels(),
            $stat_data->getSeries(),
            $stat_data->getOptions(),
            true,
            $stat_data->getCsvLink()
        );
    }

    /**
     * Display pie graph
     *
     * @param string   $title  Graph title
     * @param string[] $labels Labels to display
     * @param array    $series Series data. An array of the form:
     *                 [
     *                    ['name' => 'a name', 'data' => []],
     *                    ['name' => 'another name', 'data' => []]
     *                 ]
     * @param array    $options  Options
     * @param boolean  $display  Whether to display directly; defauts to true
     * @param string   $csv_link Link to download the dataset as csv
     *
     * @return void
     */
    public function displayPieGraph(
        $title,
        $labels,
        $series,
        $options = [],
        $display = true,
        ?string $csv_link = null
    ) {
        $param = [
            'csv'     => true
        ];

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $param[$key] = $val;
            }
        }

        $slug = str_replace('-', '_', Toolbox::slugify($title));
        $this->checkEmptyLabels($labels);

        $chart_options = [
            'title' => [
                'text' => $title,
                'left' => 'center'
            ],
            'tooltip' => [
                'trigger'      => 'item',
                'appendToBody' => true,
            ],
            'toolbox' => [
                'show'    => true,
                'feature' => [
                    'myCsvExport'    => [
                        'icon'    => 'path://M14,3v4a1,1,0,0,0,1,1h4 M17,21h-10a2,2,0,0,1,-2,-2v-14a2,2,0,0,1,2,-2h7l5,5v11a2,2,0,0,1,-2,2z M12,17v-6 M9.5,14.5l2.5,2.5l2.5,-2.5',
                        'title'   => __('Export to CSV'),
                        'onclick' => new Json_Expr(<<<JAVASCRIPT
                            function () {
                                location.href = '$csv_link';
                            }
JAVASCRIPT
                        ),
                    ],
                    'saveAsImage' => [
                        'icon'  => 'path://M15,8L15.01,8 M7,4h10s3,0,3,3v10s0,3,-3,3h-10s-3,0,-3,-3v-10s0,-3,3,-3 M4,15l4,-4a3,5,0,0,1,3,0l5,5 M14,14l1,-1a3,5,0,0,1,3,0l2,2',
                        'title' => __('Save as image'),
                    ]
                ],
            ],
            'series' => [
                [
                    'type'              => 'pie',
                    'avoidLabelOverlap' => true,
                    'data'              => [],
                    'radius'            => ['35%', '60%'],
                    'itemStyle'         => [
                        'borderRadius' => 2,
                        'borderColor'  => 'rgba(255, 255, 255, 0.5)',
                        'borderWidth'  => 2,
                    ],
                    'selectedMode'      => 'single',
                    'selectedOffset'    => 10,
                    'startAngle'        => 180,
                    'label'             => [
                        'show' => count($labels) < 10,
                    ],
                    'labelLine'         => [
                        'showAbove' => true,
                    ],
                ]
            ]
        ];

        foreach ($series as $serie) {
            $chart_options['series'][0]['data'][] = [
                'name' => $serie['name'],
                'value' => $serie['data'],
            ];
        }

        $html = <<<HTML
        <div class="card d-inline-flex mx-auto mb-1">
            <div class="card-body">
                <div id='$slug' class='chart'></div>
            </div>
        </div>

        <style>
        #$slug {
            width: 475px;
            height: 300px;
        }
        </style>
HTML;

        $chart_options_json = Json::encode($chart_options, false, ['enableJsonExprFinder' => true]);
        $js = <<<JAVASCRIPT
        $(function () {
            var myChart = echarts.init($('#{$slug}')[0]);
            myChart.setOption($chart_options_json);
        });
JAVASCRIPT;
        $js = Html::scriptBlock($js);

        $out = $html . $js;

        if ($display) {
            echo $out;
            return;
        }
        return $out;
    }

    /**
     * Display search form
     *
     * @param string  $itemtype Item type
     * @param string  $date1    First date
     * @param string  $date2    Second date
     * @param boolean $display  Whether to display directly; defauts to true
     *
     * @return void|string
     */
    public function displaySearchForm($itemtype, $date1, $date2, $display = true)
    {
        $out = TemplateRenderer::getInstance()->render('pages/assistance/stats/global_form.html.twig', [
            'itemtype' => $itemtype,
            'date1'    => $date1,
            'date2'    => $date2,
        ]);

        if ($display) {
            echo $out;
            return;
        }
        return $out;
    }

    /**
     * Check and replace empty labels
     *
     * @param array $labels Labels
     *
     * @return void
     */
    private function checkEmptyLabels(&$labels)
    {
        foreach ($labels as &$label) {
            if (empty($label)) {
                $label = '-';
            }
        }
    }

    public static function getIcon()
    {
        return "ti ti-chart-pie";
    }
}
