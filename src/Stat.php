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

use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;
use Glpi\Plugin\Hooks;
use Glpi\Search\Output\Csv;
use Glpi\Search\Output\HTMLSearchOutput;
use Glpi\Search\Output\Pdf;
use Glpi\Search\SearchEngine;
use Glpi\Stat\StatData;
use Symfony\Component\HttpFoundation\Request;

use function Safe\mktime;
use function Safe\strtotime;

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

    public static function getMenuShorcut()
    {
        return 'a';
    }

    /**
     * @param class-string<CommonGLPI> $itemtype
     * @param string $date1
     * @param string $date2
     * @param string $type
     * @param int $parent (default 0)
     * @return array
     */
    public static function getItems($itemtype, $date1, $date2, $type, $parent = 0)
    {
        global $DB;

        if (!$item = getItemForItemtype($itemtype)) {
            return [];
        }
        $val  = [];

        switch ($type) {
            case 'technician':
                /** @var CommonITILObject $item */
                $val = $item->getUsedTechBetween($date1, $date2);
                $val = array_map(static function ($item) {
                    $item['itemtype'] = User::class;
                    return $item;
                }, $val);
                break;

            case "technician_followup":
                /** @var CommonITILObject $item */
                $val = $item->getUsedTechTaskBetween($date1, $date2);
                $val = array_map(static function ($item) {
                    $item['itemtype'] = User::class;
                    return $item;
                }, $val);
                break;

            case "suppliers_id_assign":
                /** @var CommonITILObject $item */
                $val = $item->getUsedSupplierBetween($date1, $date2);
                $val = array_map(static function ($item) {
                    $item['itemtype'] = Supplier::class;
                    return $item;
                }, $val);
                break;

            case "user":
                /** @var CommonITILObject $item */
                $val = $item->getUsedAuthorBetween($date1, $date2);
                $val = array_map(static function ($item) {
                    $item['itemtype'] = User::class;
                    return $item;
                }, $val);
                break;

            case "users_id_recipient":
                /** @var CommonITILObject $item */
                $val = $item->getUsedRecipientBetween($date1, $date2);
                $val = array_map(static function ($item) {
                    $item['itemtype'] = User::class;
                    return $item;
                }, $val);
                break;

            case 'group_tree':
            case 'groups_tree_assign':
                // Get all groups
                $is_field = ($type === 'group_tree') ? 'is_requester' : 'is_assign';
                $iterator = $DB->request([
                    'SELECT' => ['id', 'name'],
                    'FROM'   => 'glpi_groups',
                    'WHERE'  => [
                        'OR'  => [
                            'id'        => $parent,
                            'groups_id' => $parent,
                        ],
                        $is_field   => 1,
                    ] + getEntitiesRestrictCriteria("glpi_groups", '', '', true),
                    'ORDER'  => 'completename',
                ]);

                foreach ($iterator as $line) {
                    $val[] = [
                        'itemtype' => Group::class,
                        'id'     => $line['id'],
                        'link'   => htmlescape($line['name']),
                    ];
                }
                break;

            case "itilcategories_tree":
            case "itilcategories_id":
                $is_tree = $type === 'itilcategories_tree';
                // Get all ticket categories for tree merge management
                $criteria = [
                    'SELECT'    => [
                        'glpi_itilcategories.id',
                        'glpi_itilcategories.' . ($is_tree ? 'name' : 'completename') . ' AS category',
                    ],
                    'DISTINCT'  => true,
                    'FROM'      => 'glpi_itilcategories',
                    'WHERE'     => getEntitiesRestrictCriteria('glpi_itilcategories', '', '', true),
                    'ORDERBY'   => 'completename',
                ];

                if ($is_tree) {
                    $criteria['WHERE']['OR'] = [
                        'id'                 => $parent,
                        'itilcategories_id'  => $parent,
                    ];
                }

                $iterator = $DB->request($criteria);

                foreach ($iterator as $line) {
                    $val[] = [
                        'itemtype' => ITILCategory::class,
                        'id'     => $line['id'],
                        'link'   => htmlescape($line['category']),
                    ];
                }
                break;

            case 'locations_tree':
            case 'locations_id':
                $is_tree = $type === 'locations_tree';
                // Get all locations for tree merge management
                $criteria = [
                    'SELECT'    => [
                        'glpi_locations.id',
                        'glpi_locations.' . ($is_tree ? 'name' : 'completename') . ' AS location',
                    ],
                    'DISTINCT'  => true,
                    'FROM'      => 'glpi_locations',
                    'WHERE'     => getEntitiesRestrictCriteria('glpi_locations', '', '', true),
                    'ORDERBY'   => 'completename',
                ];

                if ($is_tree) {
                    $criteria['WHERE']['OR'] = [
                        'id'           => $parent,
                        'locations_id' => $parent,
                    ];
                }

                $iterator = $DB->request($criteria);

                foreach ($iterator as $line) {
                    $val[] = [
                        'itemtype' => Location::class,
                        'id'     => $line['id'],
                        'link'   => htmlescape($line['location']),
                    ];
                }
                break;

            case "type":
                // TODO: would be better to use an interface + instanceof here.
                if (!method_exists($item, "getTypes")) {
                    throw new RuntimeException("Given item doesn't support getTypes() operation");
                }

                $types = $item::getTypes(); // @phpstan-ignore method.staticCall (phpstan seems to think that method_exist = non static method, which is not true)
                foreach ($types as $id => $v) {
                    $tmp['id']   = $id;
                    $tmp['link'] = htmlescape($v);
                    $val[]       = $tmp;
                }
                break;

            case "group":
                /** @var CommonITILObject $item */
                $val = $item->getUsedGroupBetween($date1, $date2);
                $val = array_map(static function ($item) {
                    $item['itemtype'] = Group::class;
                    return $item;
                }, $val);
                break;

            case "groups_id_assign":
                /** @var CommonITILObject $item */
                $val = $item->getUsedAssignGroupBetween($date1, $date2);
                $val = array_map(static function ($item) {
                    $item['itemtype'] = Group::class;
                    return $item;
                }, $val);
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
                $val = array_map(static function ($item) {
                    $item['itemtype'] = RequestType::class;
                    return $item;
                }, $val);
                break;

            case "solutiontypes_id":
                /** @var CommonITILObject $item */
                $val = $item->getUsedSolutionTypeBetween($date1, $date2);
                $val = array_map(static function ($item) {
                    $item['itemtype'] = SolutionType::class;
                    return $item;
                }, $val);
                break;

            case "usertitles_id":
                /** @var CommonITILObject $item */
                $val = $item->getUsedUserTitleOrTypeBetween($date1, $date2, true);
                $val = array_map(static function ($item) {
                    $item['itemtype'] = UserTitle::class;
                    return $item;
                }, $val);
                break;

            case "usercategories_id":
                /** @var CommonITILObject $item */
                $val = $item->getUsedUserTitleOrTypeBetween($date1, $date2, false);
                $val = array_map(static function ($item) {
                    $item['itemtype'] = UserCategory::class;
                    return $item;
                }, $val);
                break;

                // DEVICE CASE
            default:
                if (
                    ($item = getItemForItemtype($type))
                    && ($item instanceof CommonDevice)
                ) {
                    $device_table = $item::getTable();

                    //select devices IDs (table row)
                    $iterator = $DB->request([
                        'SELECT' => [
                            'id',
                            'designation',
                        ],
                        'FROM'   => $device_table,
                        'ORDER'  => 'designation',
                    ]);

                    foreach ($iterator as $line) {
                        $val[] = [
                            'itemtype' => $item::class,
                            'id'     => $line['id'],
                            'link'   => htmlescape($line['designation']),
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
                        'ORDER'  => $field,
                    ];

                    if ($item->isEntityAssign()) {
                        $criteria['ORDER'] = ['entities_id', $field];
                        $criteria['WHERE'] = getEntitiesRestrictCriteria($table);
                    }

                    $iterator = $DB->request($criteria);

                    foreach ($iterator as $line) {
                        $val[] = [
                            'itemtype' => $type,
                            'id'     => $line['id'],
                            'link'   => htmlescape($line[$field]),
                        ];
                    }
                }
        }
        return $val;
    }

    /**
     * @param class-string<CommonITILObject> $itemtype
     * @param string $type
     * @param string $date1
     * @param string $date2
     * @param integer $start
     * @param array $value
     * @param string $value2 (default '')
     * @return array|mixed
     */
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

        $end_display = $start + $_SESSION['glpilist_limit'];
        $numrows     = count($value);

        $fn_append_entry_values = static function (int $i, string $data_type, string $data_key) use ($itemtype, $date1, $date2, $type, $value, $value2, &$export_data) {
            $values = self::constructEntryValues(
                $itemtype,
                $data_type,
                $date1,
                $date2,
                $type,
                $value[$i]["id"],
                $value2
            );
            $export_data[$data_key][$value[$i]['link']] = array_sum($values);
        };

        for ($i = $start; $i < $numrows && $i < ($end_display); $i++) {
            // the number of intervention
            $fn_append_entry_values($i, "inter_total", "opened");
            // the number of solved intervention
            $fn_append_entry_values($i, "inter_solved", "solved");
            // the number of solved late intervention
            $fn_append_entry_values($i, "inter_solved_late", "late");
            // the number of closed intervention
            $fn_append_entry_values($i, "inter_closed", "closed");

            if ($itemtype === Ticket::class) {
                // open satisfaction
                $fn_append_entry_values($i, "inter_opensatisfaction", "opensatisfaction");
            }
        }

        self::$cache[$hash] = $export_data;
        return $export_data;
    }

    /**
     * @param class-string<CommonITILObject> $itemtype
     * @param string $type
     * @param string $date1
     * @param string $date2
     * @param integer $start
     * @param array $value
     * @param int|string $value2
     * @return void
     *
     * @since 0.85 (before show with same parameters)
     **/
    public static function showTable($itemtype, $type, $date1, $date2, $start, array $value, $value2 = '')
    {
        $numrows = count($value);
        // Set display type for export if define
        $output_type = $_GET["display_type"] ?? Search::HTML_OUTPUT;
        $output = SearchEngine::getOutputForLegacyKey($output_type);
        $is_html_output = $output instanceof HTMLSearchOutput;
        $html_output = '';

        if ($numrows === 0 && $is_html_output) {
            echo $output::showHeader(0, 0);
            echo '<div class="alert alert-info">' . __s('No statistics are available') . '</div>';
            echo $output::showFooter('', 0);
            return;
        }

        $headers = [];
        $rows = [];

        $end_display = $start + $_SESSION['glpilist_limit'];
        if (isset($_GET['export_all'])) {
            $start       = 0;
            $end_display = $numrows;
        }

        $nbcols = 8;
        if (!$is_html_output) {
            $nbcols--;
        }

        $request = Request::createFromGlobals();

        if ($is_html_output) {
            $html_output .= $output::showHeader($end_display - $start + 1, $nbcols);
        }
        $subname = match ($type) {
            'group_tree', 'groups_tree_assign' => Dropdown::getDropdownName('glpi_groups', $value2),
            'itilcategories_tree' => Dropdown::getDropdownName('glpi_itilcategories', $value2),
            'locations_tree' => Dropdown::getDropdownName('glpi_locations', $value2),
            default => '',
        };

        $header_num = 1;
        if ($is_html_output) {
            $html_output .= $output::showNewLine();

            if (str_contains($type, '_tree') && $value2) {
                $url = $request->getBasePath() . $request->getPathInfo() . '?' . Toolbox::append_params(
                    [
                        'date1'    => $date1,
                        'date2'    => $date2,
                        'itemtype' => $itemtype,
                        'type'     => $type,
                        'value2'   => 0,
                    ]
                );
                $html_output .= $output::showHeaderItem("<a href='" . htmlescape($url) . "'>" . __s('Back') . "</a>", $header_num);
            } else {
                $html_output .= $output::showHeaderItem("&nbsp;", $header_num);
            }
            $html_output .= $output::showHeaderItem('', $header_num);

            $html_output .= $output::showHeaderItem(
                value: _sx('quantity', 'Number'),
                num: $header_num,
                options: "colspan='4'"
            );
            if ($itemtype === Ticket::class) {
                $html_output .= $output::showHeaderItem(
                    value: __s('Satisfaction'),
                    num: $header_num,
                    options: "colspan='3'"
                );
            }
            $html_output .= $output::showHeaderItem(
                value: __s('Average time'),
                num: $header_num,
                options: $itemtype === Ticket::class ? "colspan='3'" : "colspan='2'"
            );
            $html_output .= $output::showHeaderItem(
                value: __s('Real duration of treatment of the ticket'),
                num: $header_num,
                options: "colspan='2'"
            );
            $html_output .= $output::showNewLine();

            $html_output .= $output::showHeaderItem(htmlescape($subname), $header_num);
            $html_output .= $output::showHeaderItem('', $header_num);
        }

        if (!$is_html_output) {
            $headers[] = $subname;
            $headers[] = __('Number of opened tickets');
            $headers[] = __('Number of solved tickets');
            $headers[] = __('Number of late tickets');
            $headers[] = __('Number of closed tickets');
        } else {
            $html_output .= $output::showHeaderItem(htmlescape(_nx('ticket', 'Opened', 'Opened', Session::getPluralNumber())), $header_num);
            $html_output .= $output::showHeaderItem(
                htmlescape(_nx('ticket', 'Solved', 'Solved', Session::getPluralNumber())),
                $header_num
            );
            $html_output .= $output::showHeaderItem(__s('Late'), $header_num);
            $html_output .= $output::showHeaderItem(__s('Closed'), $header_num);
        }

        if ($itemtype === Ticket::class) {
            if (!$is_html_output) {
                $headers[] = __('Number of opened satisfaction survey');
                $headers[] = __('Number of answered satisfaction survey');
                $headers[] = __('Average satisfaction');
            } else {
                $html_output .= $output::showHeaderItem(
                    htmlescape(_nx('survey', 'Opened', 'Opened', Session::getPluralNumber())),
                    $header_num
                );
                $html_output .= $output::showHeaderItem(
                    htmlescape(_nx('survey', 'Answered', 'Answered', Session::getPluralNumber())),
                    $header_num
                );
                $html_output .= $output::showHeaderItem(__s('Average'), $header_num);
            }
        }

        if (!$is_html_output) {
            if ($itemtype === Ticket::class) {
                $headers[] = __('Average time to take into account');
            }
            $headers[] = __('Average time to resolution');
            $headers[] = __('Average time to closure');
        } else {
            if ($itemtype === Ticket::class) {
                $html_output .= $output::showHeaderItem(__s('Take into account'), $header_num); //@phpstan-ignore staticMethod.notFound (see $is_html_output condition)
            }
            $html_output .= $output::showHeaderItem(__s('Resolution'), $header_num); //@phpstan-ignore staticMethod.notFound (see $is_html_output condition)
            $html_output .= $output::showHeaderItem(__s('Closure'), $header_num); //@phpstan-ignore staticMethod.notFound (see $is_html_output condition)
        }

        if (!$is_html_output) {
            $headers[] = __('Average real duration of treatment of the ticket');
            $headers[] = __('Total real duration of treatment of the ticket');
        } else {
            $html_output .= $output::showHeaderItem(__s('Average'), $header_num); //@phpstan-ignore staticMethod.notFound (see $is_html_output condition)
            $html_output .= $output::showHeaderItem(__s('Total duration'), $header_num); //@phpstan-ignore staticMethod.notFound (see $is_html_output condition)
        }
        // End Line for column headers
        if ($is_html_output) {
            $html_output .= $output::showEndLine(); //@phpstan-ignore staticMethod.notFound (see $is_html_output condition)
        }
        $row_num = 1;

        for ($i = $start; ($i < $numrows) && ($i < $end_display); $i++) {
            $row_num++;
            $current_row = [];
            $item_num = 1;
            $colnum = 0;
            if ($is_html_output) {
                $html_output .= $output::showNewLine($i % 2 === 1); //@phpstan-ignore staticMethod.notFound (see $is_html_output condition)
            }
            $value_link = $value[$i]['link']; // `link` contains a safe HTML string (built in `Stat::getItems()`)
            if (
                $is_html_output
                && str_contains($type, '_tree')
                && ((int) $value[$i]['id'] !== (int) $value2)
            ) {
                // HTML display
                $url = $request->getBasePath() . $request->getPathInfo() . '?' . Toolbox::append_params(
                    [
                        'date1'    => $date1,
                        'date2'    => $date2,
                        'itemtype' => $itemtype,
                        'type'     => $type,
                        'value2'  => $value[$i]['id'],
                    ]
                );
                $html_output .= $output::showItem("<a href='" . htmlescape($url) . "'>" . $value_link . "</a>", $item_num, $row_num); //@phpstan-ignore staticMethod.notFound (see $is_html_output condition)
            } else {
                if ($is_html_output) {
                    $html_output .= $output::showItem($value_link, $item_num, $row_num); //@phpstan-ignore staticMethod.notFound (see $is_html_output condition)
                } else {
                    $current_row[$itemtype . '_' . (++$colnum)] = ['displayname' => $value[$i]['link']];
                }
            }

            if ($is_html_output) {
                $link = "";
                if ($value[$i]['id'] > 0) {
                    $url = 'stat.graph.php?' . Toolbox::append_params(
                        [
                            'id' => $value[$i]['id'],
                            'date1'    => $date1,
                            'date2'    => $date2,
                            'itemtype' => $itemtype,
                            'type'     => $type,
                            'champ'    => $value2,
                        ]
                    );
                    $link = "<a href='" . htmlescape($url) . "' title='" . __s('View graph') . "'>"
                      . "<i class='ti ti-graph fs-1'></i>"
                      . "</a>";
                }
                $html_output .= $output::showItem($link, $item_num, $row_num); //@phpstan-ignore staticMethod.notFound (see $is_html_output condition)
            }

            $fn_show_entry_values = static function (int $i, string $data_type) use ($itemtype, $date1, $date2, $type, $value, $value2, &$item_num, $row_num, $output, &$html_output, $is_html_output, &$current_row, &$colnum) {
                $values = self::constructEntryValues(
                    $itemtype,
                    $data_type,
                    $date1,
                    $date2,
                    $type,
                    $value[$i]["id"],
                    $value2
                );
                $sum = array_sum($values);
                if ($is_html_output) {
                    $html_output .= $output::showItem((string) $sum, $item_num, $row_num); //@phpstan-ignore staticMethod.notFound (see $is_html_output condition)
                } else {
                    $current_row[$itemtype . '_' . (++$colnum)] = ['displayname' => $sum];
                }
                return [$values, $sum];
            };

            // the number of intervention
            $fn_show_entry_values($i, 'inter_total');

            // the number of solved intervention
            [$solved, $nb_solved] = $fn_show_entry_values($i, 'inter_solved');

            // the number of late solved intervention
            $fn_show_entry_values($i, 'inter_solved_late');

            // the number of closed intervention
            [, $nb_closed] = $fn_show_entry_values($i, 'inter_closed');

            if ($itemtype === Ticket::class) {
                // Satisfaction open
                $fn_show_entry_values($i, 'inter_opensatisfaction');
                // Satisfaction answer
                [$answersatisfaction, $nb_answersatisfaction] = $fn_show_entry_values($i, 'inter_answersatisfaction');

                // Satisfaction rate
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
                    if ($is_html_output) {
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
                        $avgsatisfaction *= $max_rate / 5;
                        $avgsatisfaction = TicketSatisfaction::displaySatisfaction($avgsatisfaction, 0);
                    }
                } else {
                    $avgsatisfaction = '&nbsp;';
                }
                if ($is_html_output) {
                    $html_output .= $output::showItem($avgsatisfaction, $item_num, $row_num); //@phpstan-ignore staticMethod.notFound (see $is_html_output condition)
                } else {
                    $current_row[$itemtype . '_' . (++$colnum)] = ['displayname' => $avgsatisfaction];
                }

                // The average time to take a ticket into account
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

                $timedisplay = $nb_solved > 0 ? array_sum($data) / $nb_solved : 0;

                if ($is_html_output || $output instanceof Pdf) {
                    $timedisplay = Html::timestampToString($timedisplay, false, false);
                } elseif ($output instanceof Csv) {
                    $timedisplay = Html::timestampToCsvString($timedisplay);
                }
                $timedisplay = htmlescape($timedisplay);

                if ($is_html_output) {
                    $html_output .= $output::showItem($timedisplay, $item_num, $row_num); //@phpstan-ignore staticMethod.notFound (see $is_html_output condition)
                } else {
                    $current_row[$itemtype . '_' . (++$colnum)] = ['displayname' => $timedisplay];
                }
            }

            // The average time to resolve
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
            if ($is_html_output || $output instanceof Pdf) {
                $timedisplay = Html::timestampToString($timedisplay, false, false);
            } elseif ($output instanceof Csv) {
                $timedisplay = Html::timestampToCsvString($timedisplay);
            }
            $timedisplay = htmlescape($timedisplay);

            if ($is_html_output) {
                $html_output .= $output::showItem($timedisplay, $item_num, $row_num); //@phpstan-ignore staticMethod.notFound (see $is_html_output condition)
            } else {
                $current_row[$itemtype . '_' . (++$colnum)] = ['displayname' => $timedisplay];
            }

            // The average time to close
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
            if ($is_html_output || $output instanceof Pdf) {
                $timedisplay = Html::timestampToString($timedisplay, false, false);
            } elseif ($output instanceof Csv) {
                $timedisplay = Html::timestampToCsvString($timedisplay);
            }
            $timedisplay = htmlescape($timedisplay);

            if ($is_html_output) {
                $html_output .= $output::showItem($timedisplay, $item_num, $row_num); //@phpstan-ignore staticMethod.notFound (see $is_html_output condition)
            } else {
                $current_row[$itemtype . '_' . (++$colnum)] = ['displayname' => $timedisplay];
            }

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

            // The average actiontime to resolve
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

            if ($is_html_output || $output instanceof Pdf) {
                $timedisplay = Html::timestampToString($timedisplay, false, false);
            } elseif ($output instanceof Csv) {
                $timedisplay = Html::timestampToCsvString($timedisplay);
            }
            $timedisplay = htmlescape($timedisplay);

            if ($is_html_output) {
                $html_output .= $output::showItem($timedisplay, $item_num, $row_num); //@phpstan-ignore staticMethod.notFound (see $is_html_output condition)
            } else {
                $current_row[$itemtype . '_' . (++$colnum)] = ['displayname' => $timedisplay];
            }
            // The total actiontime to resolve
            $timedisplay = $total_actiontime;

            if ($is_html_output || $output instanceof Pdf) {
                $timedisplay = Html::timestampToString($timedisplay, false, false);
            } elseif ($output instanceof Csv) {
                $timedisplay = Html::timestampToCsvString($timedisplay);
            }
            $timedisplay = htmlescape($timedisplay);

            if ($is_html_output) {
                $html_output .= $output::showItem($timedisplay, $item_num, $row_num); //@phpstan-ignore staticMethod.notFound (see $is_html_output condition)
            } else {
                $current_row[$itemtype . '_' . (++$colnum)] = ['displayname' => $timedisplay];
            }

            $rows[$row_num] = $current_row;
            if ($is_html_output) {
                $html_output .= $output::showEndLine(); //@phpstan-ignore staticMethod.notFound (see $is_html_output condition)
            }
        }
        if ($is_html_output) {
            $html_output .= $output::showFooter('', $numrows); //@phpstan-ignore staticMethod.notFound (see $is_html_output condition)
        }

        if ($is_html_output) {
            echo $html_output;
        } else {
            $params = [
                'start' => 0,
                'is_deleted' => 0,
                'as_map' => 0,
                'browse' => 0,
                'unpublished' => 1,
                'criteria' => [],
                'metacriteria' => [],
                'display_type' => 0,
                'hide_controls' => true,
            ];
            $stats_data = SearchEngine::prepareDataForSearch($itemtype, $params);
            $stats_data = array_merge($stats_data, [
                'itemtype' => $itemtype,
                'data' => [
                    'totalcount' => $numrows,
                    'count' => $numrows,
                    'search' => '',
                    'cols' => [],
                    'rows' => $rows,
                ],
            ]);

            $colid = 0;
            foreach ($headers as $header) {
                $stats_data['data']['cols'][] = [
                    'name' => $header,
                    'itemtype' => $itemtype,
                    'id' => ++$colid,
                ];
            }

            $output->displayData($stats_data, []);
        }
    }

    /**
     * @param class-string<CommonITILObject> $itemtype
     * @param string $type
     * @param string $begin
     * @param string $end
     * @param string $param
     * @param string|array $value
     * @param string $value2 (default '')
     * @param $add_criteria          (default [''])
     *
     * @return array|void
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
        global $CFG_GLPI;
        $DB = DBConnection::getReadConnection();

        if (!$item = getItemForItemtype($itemtype)) {
            return;
        }
        /** @var CommonITILObject $item */
        $table          = $item::getTable();
        $fkfield        = $item::getForeignKeyField();

        if (!($userlinkclass = getItemForItemtype($item->userlinkclass))) {
            return;
        }
        $userlinktable  = $userlinkclass::getTable();
        if (!$grouplinkclass = getItemForItemtype($item->grouplinkclass)) {
            return;
        }
        $grouplinktable = $grouplinkclass::getTable();

        if (!($supplierlinkclass = getItemForItemtype($item->supplierlinkclass))) {
            return;
        }
        $supplierlinktable = $supplierlinkclass::getTable();

        $tasktable      = getTableForItemType($item::getTaskClass());

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
                    $table         => 'id',
                ],
            ],
        ];
        $LEFTJOINGROUP    = [
            $grouplinktable => [
                'ON' => [
                    $grouplinktable   => $fkfield,
                    $table            => 'id',
                ],
            ],
        ];
        $LEFTJOINSUPPLIER = [
            $supplierlinktable => [
                'ON' => [
                    $supplierlinktable   => $fkfield,
                    $table               => 'id',
                ],
            ],
        ];

        switch ($param) {
            case "technician":
                $LEFTJOIN = $LEFTJOINUSER;
                $WHERE["$userlinktable.users_id"] = $value;
                $WHERE["$userlinktable.type"] = CommonITILActor::ASSIGN;
                break;

            case "technician_followup":
                $WHERE["$tasktable.users_id_tech"] = $value;
                $LEFTJOIN = [
                    $tasktable => [
                        'ON' => [
                            $tasktable  => $fkfield,
                            $table      => 'id',
                        ],
                    ],
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
                        'glpi_users'   => 'id',
                    ],
                ];
                $WHERE["glpi_users.usertitles_id"] = $value;
                $WHERE["$userlinktable.type"] = CommonITILActor::REQUESTER;
                break;

            case "usercategories_id":
                $LEFTJOIN  = $LEFTJOINUSER;
                $LEFTJOIN['glpi_users'] = [
                    'ON' => [
                        $userlinktable => 'users_id',
                        'glpi_users'   => 'id',
                    ],
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
                                    'glpi_itilsolutions.itemtype' => 'Ticket',
                                ],
                            ],
                        ],
                    ],
                ];
                $WHERE["glpi_itilsolutions.$param"] = $value;
                break;

            case "device":
                $devtable = getTableForItemType('Item_' . $value2);
                $fkname   = getForeignKeyFieldForTable(getTableForItemType($value2));
                //select computers IDs that are using this device;
                $linkedtable = $table;
                if (in_array($itemtype, $CFG_GLPI['itil_types'], true)) {
                    $linkedtable = $itemtype::getItemsTable();
                    $LEFTJOIN = [
                        $linkedtable => [
                            'ON' => [
                                $linkedtable => $itemtype::getForeignKeyField(),
                                $table => 'id', [
                                    'AND' => [
                                        "$linkedtable.itemtype" => 'Computer',
                                    ],
                                ],
                            ],
                        ],
                    ];
                }
                $INNERJOIN = [
                    'glpi_computers'  => [
                        'ON' => [
                            'glpi_computers'  => 'id',
                            $linkedtable      => 'items_id',
                        ],
                    ],
                    $devtable         => [
                        'ON' => [
                            'glpi_computers'  => 'id',
                            $devtable         => 'items_id', [
                                'AND' => [
                                    "$devtable.itemtype" => Computer::class,
                                    "$devtable.$fkname" => $value,
                                ],
                            ],
                        ],
                    ],
                ];

                $WHERE["glpi_computers.is_template"] = 0;
                break;

            case "comp_champ":
                $ftable   = getTableForItemType($value2);
                $champ    = getForeignKeyFieldForTable($ftable);
                $linkedtable = $table;
                if (in_array($itemtype, $CFG_GLPI['itil_types'], true)) {
                    $linkedtable = $itemtype::getItemsTable();
                    $LEFTJOIN = [
                        $linkedtable => [
                            'ON' => [
                                $linkedtable => $itemtype::getForeignKeyField(),
                                $table => 'id', [
                                    'AND' => [
                                        "$linkedtable.itemtype" => 'Computer',
                                    ],
                                ],
                            ],
                        ],
                    ];
                }
                $INNERJOIN = [
                    'glpi_computers' => [
                        'ON' => [
                            'glpi_computers'  => 'id',
                            $linkedtable      => 'items_id',
                        ],
                    ],
                ];

                $WHERE["glpi_computers.is_template"] = 0;
                if (str_starts_with($champ, 'operatingsystem')) {
                    $INNERJOIN['glpi_items_operatingsystems'] = [
                        'ON' => [
                            'glpi_computers'              => 'id',
                            'glpi_items_operatingsystems' => 'items_id', [
                                'AND' => [
                                    "glpi_items_operatingsystems.itemtype" => 'Computer',
                                ],
                            ],
                        ],
                    ];
                    $WHERE["glpi_items_operatingsystems.$champ"] = $value;
                } else {
                    $WHERE["glpi_computers.$champ"] = $value;
                }
                break;
        }

        $date_unix = QueryFunction::fromUnixtime(
            expression: QueryFunction::unixTimestamp("$table.date"),
            format: new QueryExpression($DB::quoteValue('%Y-%m')),
            alias: 'date_unix'
        );
        $solvedate_unix = QueryFunction::fromUnixtime(
            expression: QueryFunction::unixTimestamp("$table.solvedate"),
            format: new QueryExpression($DB::quoteValue('%Y-%m')),
            alias: 'date_unix'
        );
        $closedate_unix = QueryFunction::fromUnixtime(
            expression: QueryFunction::unixTimestamp("$table.closedate"),
            format: new QueryExpression($DB::quoteValue('%Y-%m')),
            alias: 'date_unix'
        );

        switch ($type) {
            case "inter_total":
                $WHERE[] = getDateCriteria("$table.date", $begin, $end);

                $criteria = [
                    'SELECT'    => [
                        $date_unix,
                        'COUNT DISTINCT' => "$table.id AS total_visites",
                    ],
                    'FROM'      => $table,
                    'WHERE'     => $WHERE,
                    'GROUPBY'   => 'date_unix',
                    'ORDERBY'   => "$table.date",
                ];
                break;

            case "inter_solved":
                $WHERE["$table.status"] = $solved_status;
                $WHERE[] = ['NOT' => ["$table.solvedate" => null]];
                $WHERE[] = getDateCriteria("$table.solvedate", $begin, $end);

                $criteria = [
                    'SELECT'    => [
                        $solvedate_unix,
                        'COUNT DISTINCT'  => "$table.id AS total_visites",
                    ],
                    'FROM'      => $table,
                    'WHERE'     => $WHERE,
                    'GROUPBY'   => 'date_unix',
                    'ORDERBY'   => "$table.solvedate",
                ];
                break;

            case "inter_solved_late":
                $WHERE["$table.status"] = $solved_status;
                $WHERE[] = [
                    'NOT' => [
                        "$table.solvedate"         => null,
                        "$table.time_to_resolve"   => null,
                    ],
                ];
                $WHERE[] = getDateCriteria("$table.solvedate", $begin, $end);
                $WHERE[] = new QueryExpression("$table.solvedate > $table.time_to_resolve");

                $criteria = [
                    'SELECT'    => [
                        $solvedate_unix,
                        'COUNT DISTINCT'  => "$table.id AS total_visites",
                    ],
                    'FROM'      => $table,
                    'WHERE'     => $WHERE,
                    'GROUPBY'   => 'date_unix',
                    'ORDERBY'   => "$table.solvedate",
                ];
                break;

            case "inter_closed":
                $WHERE["$table.status"] = $closed_status;
                $WHERE[] = ['NOT' => ["$table.closedate" => null]];
                $WHERE[] = getDateCriteria("$table.closedate", $begin, $end);

                $criteria = [
                    'SELECT'    => [
                        $closedate_unix,
                        'COUNT DISTINCT'  => "$table.id AS total_visites",
                    ],
                    'FROM'      => $table,
                    'WHERE'     => $WHERE,
                    'GROUPBY'   => 'date_unix',
                    'ORDERBY'   => "$table.closedate",
                ];
                break;

            case "inter_solved_with_actiontime":
                $WHERE["$table.status"] = $solved_status;
                $WHERE["$table.actiontime"] = ['>', 0];
                $WHERE[] = ['NOT' => ["$table.solvedate" => null]];
                $WHERE[] = getDateCriteria("$table.solvedate", $begin, $end);

                $criteria = [
                    'SELECT'    => [
                        $solvedate_unix,
                        'COUNT DISTINCT'  => "$table.id AS total_visites",
                    ],
                    'FROM'      => $table,
                    'WHERE'     => $WHERE,
                    'GROUPBY'   => 'date_unix',
                    'ORDERBY'   => "$table.solvedate",
                ];
                break;

            case "inter_avgsolvedtime":
                $WHERE["$table.status"] = $solved_status;
                $WHERE[] = ['NOT' => ["$table.solvedate" => null]];
                $WHERE[] = getDateCriteria("$table.solvedate", $begin, $end);

                $criteria = [
                    'SELECT'    => [
                        $solvedate_unix,
                        'AVG' => "solve_delay_stat AS total_visites",
                    ],
                    'FROM'      => $table,
                    'WHERE'     => $WHERE,
                    'GROUPBY'   => 'date_unix',
                    'ORDERBY'   => "$table.solvedate",
                ];
                break;

            case "inter_avgclosedtime":
                $WHERE["$table.status"] = $closed_status;
                $WHERE[] = ['NOT' => ["$table.closedate" => null]];
                $WHERE[] = getDateCriteria("$table.closedate", $begin, $end);

                $criteria = [
                    'SELECT'    => [
                        $closedate_unix,
                        'AVG'  => "close_delay_stat AS total_visites",
                    ],
                    'FROM'      => $table,
                    'WHERE'     => $WHERE,
                    'GROUPBY'   => 'date_unix',
                    'ORDERBY'   => "$table.closedate",
                ];
                break;

            case "inter_avgactiontime":
                if ($param === "technician_followup") {
                    $actiontime_table = $tasktable;
                } else {
                    $actiontime_table = $table;
                }
                $WHERE["$actiontime_table.actiontime"] = ['>', 0];
                $WHERE[] = getDateCriteria("$table.solvedate", $begin, $end);

                $criteria = [
                    'SELECT'    => [
                        $solvedate_unix,
                        'AVG'  => "$actiontime_table.actiontime AS total_visites",
                    ],
                    'FROM'      => $table,
                    'WHERE'     => $WHERE,
                    'GROUPBY'   => 'date_unix',
                    'ORDERBY'   => "$table.solvedate",
                ];
                break;

            case "inter_avgtakeaccount":
                $WHERE["$table.status"] = $solved_status;
                $WHERE[] = ['NOT' => ["$table.solvedate" => null]];
                $WHERE[] = getDateCriteria("$table.solvedate", $begin, $end);

                $criteria = [
                    'SELECT'    => [
                        $solvedate_unix,
                        'AVG'  => "$table.takeintoaccount_delay_stat AS total_visites",
                    ],
                    'FROM'      => $table,
                    'WHERE'     => $WHERE,
                    'GROUPBY'   => 'date_unix',
                    'ORDERBY'   => "$table.solvedate",
                ];
                break;

            case "inter_opensatisfaction":
                $WHERE["$table.status"] = $closed_status;
                $WHERE[] = ['NOT' => ["$table.closedate" => null]];
                $WHERE[] = getDateCriteria("$table.closedate", $begin, $end);

                $INNERJOIN['glpi_ticketsatisfactions'] = [
                    'ON' => [
                        'glpi_ticketsatisfactions' => 'tickets_id',
                        $table                     => 'id',
                    ],
                ];

                $criteria = [
                    'SELECT'    => [
                        $closedate_unix,
                        'COUNT DISTINCT'  => "$table.id AS total_visites",
                    ],
                    'FROM'      => $table,
                    'WHERE'     => $WHERE,
                    'GROUPBY'   => 'date_unix',
                    'ORDERBY'   => "$table.closedate",
                ];
                break;

            case "inter_answersatisfaction":
                $WHERE["$table.status"] = $closed_status;
                $WHERE[] = [
                    ['NOT' => ["$table.closedate" => null]],
                    ['NOT' => ["glpi_ticketsatisfactions.date_answered"  => null]],
                ];

                $WHERE[] = getDateCriteria("$table.closedate", $begin, $end);

                $INNERJOIN['glpi_ticketsatisfactions'] = [
                    'ON' => [
                        'glpi_ticketsatisfactions' => 'tickets_id',
                        $table                     => 'id',
                    ],
                ];

                $criteria = [
                    'SELECT'    => [
                        $closedate_unix,
                        'COUNT DISTINCT'  => "$table.id AS total_visites",
                    ],
                    'FROM'      => $table,
                    'WHERE'     => $WHERE,
                    'GROUPBY'   => 'date_unix',
                    'ORDERBY'   => "$table.closedate",
                ];
                break;

            case "inter_avgsatisfaction":
                $WHERE["$table.status"] = $closed_status;
                $WHERE[] = [
                    'NOT' => [
                        "$table.closedate" => null,
                        "glpi_ticketsatisfactions.date_answered" => null,
                    ],
                ];
                $WHERE[] = getDateCriteria("$table.closedate", $begin, $end);

                $INNERJOIN['glpi_ticketsatisfactions'] = [
                    'ON' => [
                        'glpi_ticketsatisfactions' => 'tickets_id',
                        $table                     => 'id',
                    ],
                ];

                $criteria = [
                    'SELECT'    => [
                        $closedate_unix,
                        'AVG'  => "glpi_ticketsatisfactions.satisfaction_scaled_to_5 AS total_visites",
                    ],
                    'FROM'      => $table,
                    'WHERE'     => $WHERE,
                    'GROUPBY'   => 'date_unix',
                    'ORDERBY'   => "$table.closedate",
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
            $entrees[$date] = $row['total_visites'];
        }

        $end_time   = strtotime(date("Y-m", strtotime($end)) . "-01");
        $begin_time = strtotime(date("Y-m", strtotime($begin)) . "-01");

        $current = $begin_time;

        while ($current <= $end_time) {
            $curentry = date("Y-m", $current);
            if (!isset($entrees[$curentry])) {
                $entrees[$curentry] = 0;
            }
            $month   = date("m", $current);
            $year    = date("Y", $current);
            $current = mktime(0, 0, 0, (int) $month + 1, 1, (int) $year);
        }
        ksort($entrees);

        return $entrees;
    }

    /**
     * @param DateTime|string $start_date
     * @param DateTime|string $end_date
     * @param class-string<CommonITILObject> $itil_type
     * @return array
     */
    public static function getAssetsWithITIL($start_date, $end_date, $itil_type = 'Ticket'): array
    {
        global $DB;

        $itil_table = $itil_type::getTable();
        $itil_fkfield = $itil_type::getForeignKeyField();
        $item_link_table = $itil_type::getItemsTable();

        $iterator = $DB->request([
            'SELECT' => [
                "$item_link_table.itemtype",
                "$item_link_table.items_id",
                'COUNT'  => '* AS NB',
            ],
            'FROM'   => $itil_table,
            'LEFT JOIN' => [
                $item_link_table => [
                    'ON' => [
                        $item_link_table => $itil_fkfield,
                        $itil_table => 'id',
                    ],
                ],
            ],
            'WHERE'  => [
                'date' => ['<=', $end_date],
                "$itil_table.date" => ['>=', $start_date],
                "$item_link_table.itemtype" => ['<>', ''],
                "$item_link_table.items_id" => ['>', 0],
            ] + getEntitiesRestrictCriteria($itil_table),
            'GROUP'  => [
                "$item_link_table.itemtype",
                "$item_link_table.items_id",
            ],
            'ORDER'  => 'NB DESC',
        ]);

        $data = [];
        $view_entities = Session::isMultiEntitiesMode();
        if ($view_entities) {
            $entities = getAllDataFromTable('glpi_entities');
        }

        foreach ($iterator as $row) {
            $itemtype = $row['itemtype'];
            $items_id = $row['items_id'];
            $item     = getItemForItemtype($itemtype);
            $data_row = $row;
            if ($item && $item->getFromDB($items_id)) {
                if ($view_entities) {
                    $ent = $item->getEntityID();
                    $data_row['entities_id'] = $ent;
                    $data_row['entity_name'] = $entities[$ent]['completename'];
                }
                $data_row['name'] = $item->getName();
                $data_row['link'] = $item->getLink();
                $data_row['is_deleted'] = $item->isDeleted();
                $data[] = $data_row;
            }
        }

        return $data;
    }

    /**
     * @param string $target
     * @param string $date1
     * @param string $date2
     * @param integer $start
     * @param class-string<CommonITILObject>|null $itemtype
     **/
    public static function showItems($target, $date1, $date2, $start, $itemtype = null)
    {
        $view_entities = Session::isMultiEntitiesMode();

        $output_type = $_GET["display_type"] ?? Search::HTML_OUTPUT;
        $output = SearchEngine::getOutputForLegacyKey($output_type);
        $is_html_output = $output instanceof HTMLSearchOutput;

        if (empty($date2)) {
            $date2 = date("Y-m-d");
        }
        $date2 .= " 23:59:59";

        // 1 an par defaut
        if (empty($date1)) {
            $date1 = date("Y-m-d", mktime(0, 0, 0, (int) date("m"), (int) date("d"), ((int) date("Y")) - 1));
        }
        $date1 .= " 00:00:00";

        $assets = self::getAssetsWithITIL($date1, $date2, $itemtype ?? 'Ticket');
        $numrows = count($assets);

        if ($numrows > 0) {
            if ($is_html_output) {
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
                        ]
                    ),
                    'Stat'
                );
                echo "<div class='text-center'>";
            }

            $end_display = $start + $_SESSION['glpilist_limit'];
            if (isset($_GET['export_all'])) {
                $end_display = $numrows;
            }

            $header_num = 1;
            if ($is_html_output) {
                echo $output::showHeader($end_display - $start + 1, 2, 1);
                echo $output::showNewLine();
                echo $output::showHeaderItem(_sn('Associated element', 'Associated elements', $numrows), $header_num);
                if ($view_entities) {
                    echo $output::showHeaderItem(htmlescape(Entity::getTypeName(1)), $header_num);
                }
                echo $output::showHeaderItem(__s('Number of tickets'), $header_num);
                echo $output::showEndLine();
            }

            $i = $start;
            if (isset($_GET['export_all'])) {
                $start = 0;
            }

            $i = $start;
            foreach ($assets as $data) {
                $item_num = 1;
                // Get data and increment loop variables
                if ($is_html_output) {
                    echo $output::showNewLine($i % 2 === 1);
                    $link = sprintf(__s('%1$s - %2$s'), htmlescape($data['itemtype']::getTypeName()), $data['link']);
                    echo $output::showItem(
                        $link,
                        $item_num,
                        $i - $start + 1,
                        "class='text-center'" . " " . ($data['is_deleted'] ? " class='deleted' "
                            : "")
                    );
                    if ($view_entities) {
                        echo $output::showItem(
                            htmlescape($data['entity_name']),
                            $item_num,
                            $i - $start + 1,
                            "class='text-center'" . " " . ($data['is_deleted'] ? " class='deleted' "
                                : "")
                        );
                    }
                    echo $output::showItem(
                        htmlescape($data["NB"]),
                        $item_num,
                        $i - $start + 1,
                        "class='center'" . " " . ($data['is_deleted'] ? " class='deleted' "
                            : "")
                    );
                }

                $i++;
                if ($i == $end_display) {
                    break;
                }
            }

            if ($is_html_output) {
                echo $output::showFooter();
            }
        }
    }

    public static function getAvailableStatistics()
    {
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
        $stat_list["Ticket"]["Ticket_Item"]["file"]     = "stat.item.php?itemtype=Ticket";

        if (Problem::canView()) {
            $opt_list["Problem"]                               = Problem::getTypeName(Session::getPluralNumber());

            $stat_list["Problem"]["Problem_Global"]["name"]    = __('Global');
            $stat_list["Problem"]["Problem_Global"]["file"]    = "stat.global.php?itemtype=Problem";
            $stat_list["Problem"]["Problem_Problem"]["name"]   = __('By problem');
            $stat_list["Problem"]["Problem_Problem"]["file"]   = "stat.tracking.php?itemtype=Problem";
            $stat_list["Problem"]["Problem_Location"]["name"] = __('By hardware characteristics');
            $stat_list["Problem"]["Problem_Location"]["file"] = "stat.location.php?itemtype=Problem";
            $stat_list["Problem"]["Problem_Item"]["name"]     = __('By hardware');
            $stat_list["Problem"]["Problem_Item"]["file"]     = "stat.item.php?itemtype=Problem";
        }

        if (Change::canView()) {
            $opt_list["Change"]                             = _n('Change', 'Changes', Session::getPluralNumber());

            $stat_list["Change"]["Change_Global"]["name"]   = __('Global');
            $stat_list["Change"]["Change_Global"]["file"]   = "stat.global.php?itemtype=Change";
            $stat_list["Change"]["Change_Change"]["name"]   = __('By change');
            $stat_list["Change"]["Change_Change"]["file"]   = "stat.tracking.php?itemtype=Change";
            $stat_list["Change"]["Change_Location"]["name"] = __('By hardware characteristics');
            $stat_list["Change"]["Change_Location"]["file"] = "stat.location.php?itemtype=Change";
            $stat_list["Change"]["Change_Item"]["name"]     = __('By hardware');
            $stat_list["Change"]["Change_Item"]["file"]     = "stat.item.php?itemtype=Change";
        }

        $values   = [$CFG_GLPI["root_doc"] . '/front/stat.php' => Dropdown::EMPTY_VALUE];

        foreach ($opt_list as $opt => $group) {
            foreach ($stat_list[$opt] as $data) {
                $name    = $data['name'];
                $file    = $data['file'];
                $key                  = $CFG_GLPI["root_doc"] . "/front/" . $file;
                $values[$group][$key] = $name;
            }
        }

        // Manage plugins
        $names    = [];
        $optgroup = [];
        if (isset($PLUGIN_HOOKS[Hooks::STATS]) && is_array($PLUGIN_HOOKS[Hooks::STATS])) {
            foreach ($PLUGIN_HOOKS[Hooks::STATS] as $plug => $pages) {
                if (!Plugin::isPluginActive($plug)) {
                    continue;
                }
                if (is_array($pages) && count($pages)) {
                    foreach ($pages as $page => $name) {
                        $names["/plugins/{$plug}/{$page}"] = [
                            "name" => $name,
                            "plug" => $plug,
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
                }
            }
        }

        return $values;
    }

    /**
     * @param class-string<CommonITILObject> $itemtype
     * @return array
     */
    public static function getITILStatFields(string $itemtype): array
    {
        $caract = [
            'itilcategories_id'   => _n('Category', 'Categories', 1),
            'itilcategories_tree' => __('Category tree'),
            'urgency'             => __('Urgency'),
            'impact'              =>  __('Impact'),
            'priority'            => __('Priority'),
            'solutiontypes_id'    => SolutionType::getTypeName(1),
        ];

        if ($itemtype === Ticket::class) {
            $caract['type']            = _n('Type', 'Types', 1);
            $caract['requesttypes_id'] = RequestType::getTypeName(1);
            $caract['locations_id']    = Location::getTypeName(1);
            $caract['locations_tree']  = __('Location tree');
        }

        return [
            _n('Requester', 'Requesters', 1) => [
                'user'               => _n('Requester', 'Requesters', 1),
                'users_id_recipient' => __('Writer'),
                'group'              => Group::getTypeName(1),
                'group_tree'         => __('Group tree'),
                'usertitles_id'      => _x('person', 'Title'),
                'usercategories_id'  => _n('Category', 'Categories', 1),
            ],
            __('Characteristics') => $caract,
            __('Assigned to') => [
                'technician'          => __('Technician as assigned'),
                'technician_followup' => __('Technician in tasks'),
                'groups_id_assign'    => Group::getTypeName(1),
                'groups_tree_assign'  => __('Group tree'),
                'suppliers_id_assign' => Supplier::getTypeName(1),
            ],
        ];
    }

    public static function getItemCharacteristicStatFields(): array
    {
        $values = [
            _n('Dropdown', 'Dropdowns', Session::getPluralNumber()) => [
                'ComputerType'    => _n('Type', 'Types', 1),
                'ComputerModel'   => _n('Model', 'Models', 1),
                'OperatingSystem' => OperatingSystem::getTypeName(1),
                'Location'        => Location::getTypeName(1),
            ],
        ];
        $devices = Dropdown::getDeviceItemTypes();
        foreach ($devices as $label => $dp) {
            foreach ($dp as $i => $name) {
                $values[$label][$i] = $name;
            }
        }
        return $values;
    }

    public static function title()
    {
        $values = self::getAvailableStatistics();
        $selected = -1;

        foreach ($values as $reports) {
            if (is_array($reports)) {
                foreach (array_keys($reports) as $key) {
                    if (stripos($_SERVER['REQUEST_URI'], (string) $key) !== false) {
                        $selected = $key;
                    }
                }
            }
        }

        TemplateRenderer::getInstance()->display('pages/assistance/stats/title.html.twig', [
            'values'   => $values,
            'selected' => $selected,
        ]);
    }

    public function getRights($interface = 'central')
    {
        $values[READ] = __('Read');
        return $values;
    }

    /**
     * Call displayLineGraph with arguments from a StatData object
     * @param StatData $stat_data
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
     * @param string|null $csv_link Link to download the dataset as csv
     *
     * @return string|void
     * @phpstan-return ($display is true ? void : string)
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
            'img'     => true,
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
                'feature' => [],
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
                    'width'  => 4,
                ],
                'symbolSize'      => 8,
                'legendHoverLink' => true,
            ];
        }

        if ($param['csv'] && $csv_link) {
            $chart_options['toolbox']['feature']['myCsvExport'] = [
                'icon'    => 'path://M14,3v4a1,1,0,0,0,1,1h4 M17,21h-10a2,2,0,0,1,-2,-2v-14a2,2,0,0,1,2,-2h7l5,5v11a2,2,0,0,1,-2,2z M12,17v-6 M9.5,14.5l2.5,2.5l2.5,-2.5',
                'title'   => __('Export to CSV'),
            ];
        }

        if ($param['img']) {
            $chart_options['toolbox']['feature']['saveAsImage'] = [
                'icon'  => 'path://M15,8L15.01,8 M7,4h10s3,0,3,3v10s0,3,-3,3h-10s-3,0,-3,-3v-10s0,-3,3,-3 M4,15l4,-4a3,5,0,0,1,3,0l5,5 M14,14l1,-1a3,5,0,0,1,3,0l2,2',
                'title' => __('Save as image'),
            ];
        }

        $height = ((int) $param['height']) . "px";
        $width  = ((int) $param['width']) . "px";
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

        $twig_params = [
            'slug' => $slug,
            'chart_options' => $chart_options,
            'csv_link' => $csv_link,
        ];
        // language=Twig
        $js = TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            <script type="module">
                function exportToCSV() {
                    location.href = '{{ csv_link|e('js') }}';
                }
                const chart_options = {{ chart_options|json_encode|raw }};
                const myChart = echarts.init(document.getElementById('{{ slug }}'));

                $.each(chart_options.series, function (index, serie) {
                    serie.symbol = (value) => value > 0 ? 'circle': 'none';
                });
                if (chart_options['toolbox']['feature']['myCsvExport'] !== undefined) {
                    chart_options['toolbox']['feature']['myCsvExport']['onclick'] = exportToCSV;
                }
                myChart.setOption(chart_options);
            </script>
TWIG, $twig_params);

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
     * @param string|null $csv_link Link to download the dataset as csv
     *
     * @return string|void
     * @phpstan-return ($display is true ? void : string)
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
            'csv'     => true,
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
                'left' => 'center',
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
                    ],
                    'saveAsImage' => [
                        'icon'  => 'path://M15,8L15.01,8 M7,4h10s3,0,3,3v10s0,3,-3,3h-10s-3,0,-3,-3v-10s0,-3,3,-3 M4,15l4,-4a3,5,0,0,1,3,0l5,5 M14,14l1,-1a3,5,0,0,1,3,0l2,2',
                        'title' => __('Save as image'),
                    ],
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
                ],
            ],
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

        $twig_params = [
            'slug' => $slug,
            'chart_options' => $chart_options,
            'csv_link' => $csv_link,
        ];
        // language=Twig
        $js = TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            <script type="module">
                function exportToCSV() {
                    location.href = '{{ csv_link|e('js') }}';
                }
                const chart_options = {{ chart_options|json_encode|raw }};
                const myChart = echarts.init(document.getElementById('{{ slug }}'));

                if (chart_options['toolbox']['feature']['myCsvExport'] !== undefined) {
                    chart_options['toolbox']['feature']['myCsvExport']['onclick'] = exportToCSV;
                }
                myChart.setOption(chart_options);
            </script>
TWIG, $twig_params);

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
     * @phpstan-return ($display is true ? void : string)
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
