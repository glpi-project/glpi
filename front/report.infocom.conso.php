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

/** @var array $CFG_GLPI */
global $CFG_GLPI;

include('../inc/includes.php');

Session::checkRight("reports", READ);

Html::header(Report::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "tools", "report");

if (empty($_POST["date1"]) && empty($_POST["date2"])) {
    $year           = date("Y") - 1;
    $_POST["date1"] = date("Y-m-d", mktime(1, 0, 0, (int) date("m"), (int) date("d"), $year));
    $_POST["date2"] = date("Y-m-d");
}

if (
    !empty($_POST["date1"])
    && !empty($_POST["date2"])
    && (strcmp($_POST["date2"], $_POST["date1"]) < 0)
) {
    $tmp            = $_POST["date1"];
    $_POST["date1"] = $_POST["date2"];
    $_POST["date2"] = $tmp;
}

$stat = new Stat();
$chart_opts =  [
    'width'  => '90%',
    'legend' => false,
    'title'  => __('Value'),
];

Report::title();

echo "\n<form method='post' name='form' action='" . $_SERVER['PHP_SELF'] . "'>";
echo "<table class='tab_cadre'><tr class='tab_bg_2'>";
echo "<td class='right'>" . __('Start date') . "</td><td>";
Html::showDateField("date1", ['value' => $_POST["date1"]]);
echo "</td><td rowspan='2' class='center'>";
echo "<input type='submit' class='btn btn-primary' name='submit' value=\"" . __s('Display report') . "\"></td>" .
     "</tr>\n";
echo "<tr class='tab_bg_2'><td class='right'>" . __('End date') . "</td><td>";
Html::showDateField("date2", ['value' => $_POST["date2"]]);
echo "</td></tr>";
echo "</table>\n";
Html::closeForm();

$valeurtot           = 0;
$valeurnettetot      = 0;
$valeurnettegraphtot = [];
$valeurgraphtot      = [];


/** Display an infocom report for items like consumables
 *
 * @param string $itemtype  item type
 * @param string $begin     begin date
 * @param string $end       end date
 **/
function display_infocoms_report($itemtype, $begin, $end)
{
    /**
     * @var array $CFG_GLPI
     * @var \DBmysql $DB
     * @var int $valeurtot
     * @var int $valeurnettetot
     * @var array $valeurnettegraphtot
     * @var array $valeurgraphtot
     * @var \Stat $stat
     * @var array $chart_opts
     */
    global $CFG_GLPI, $DB, $valeurtot, $valeurnettetot, $valeurnettegraphtot, $valeurgraphtot, $stat, $chart_opts;

    $itemtable = getTableForItemType($itemtype);
    if ($DB->fieldExists($itemtable, "ticket_tco", false)) { // those are in the std infocom report
        return false;
    }

    $criteria = [
        'SELECT'       => 'glpi_infocoms.*',
        'FROM'         => 'glpi_infocoms',
        'INNER JOIN'   => [
            $itemtable  => [
                'ON'  => [
                    $itemtable        => 'id',
                    'glpi_infocoms'   => 'items_id', [
                        'AND' => [
                            'glpi_infocoms.itemtype' => $itemtype,
                        ],
                    ],
                ],
            ],
        ],
        'WHERE'        => [],
    ];

    switch ($itemtype) {
        case 'SoftwareLicense':
            $criteria['INNER JOIN']['glpi_softwares'] = [
                'ON'  => [
                    'glpi_softwarelicenses' => 'softwares_id',
                    'glpi_softwares'        => 'id',
                ],
            ];
            $criteria['WHERE'] =  getEntitiesRestrictCriteria("glpi_softwarelicenses");
            break;
        default:
            if (is_a($itemtype, CommonDBChild::class, true)) {
                $childitemtype = $itemtype::$itemtype; // acces to child via $itemtype static
                $criteria['INNER JOIN'][$childitemtype::getTable()] = [
                    'ON'  => [
                        $itemtype::getTable() => $itemtype::$items_id,
                        $childitemtype::getTable() => 'id',
                    ],
                ];
                $criteria['WHERE'] =  getEntitiesRestrictCriteria($itemtable);
            }
            break;
    }

    if (!empty($begin)) {
        $criteria['WHERE'][] = [
            'OR'  => [
                'glpi_infocoms.buy_date'   => ['>=', $begin],
                'glpi_infocoms.use_date'   => ['>=', $begin],
            ],
        ];
    }
    if (!empty($end)) {
        $criteria['WHERE'][] = [
            'OR'  => [
                'glpi_infocoms.buy_date'   => ['<=', $end],
                'glpi_infocoms.use_date'   => ['<=', $end],
            ],
        ];
    }
    $iterator = $DB->request($criteria);

    if (
        count($iterator)
         && ($item = getItemForItemtype($itemtype))
    ) {
        echo "<h2>" . $item->getTypeName(1) . "</h2>";
        echo "<table class='tab_cadre'>";

        $valeursoustot      = 0;
        $valeurnettesoustot = 0;
        $valeurnettegraph   = [];
        $valeurgraph        = [];

        foreach ($iterator as $line) {
            if ($itemtype == 'SoftwareLicense') {
                $item->getFromDB($line["items_id"]);

                if ($item->fields["serial"] == "global") {
                    if ($item->fields["number"] > 0) {
                        $line["value"] *= $item->fields["number"];
                    }
                }
            }
            if ($line["value"] > 0) {
                $valeursoustot += $line["value"];
            }

            $valeurnette = Infocom::Amort(
                $line["sink_type"],
                $line["value"],
                $line["sink_time"],
                $line["sink_coeff"],
                $line["buy_date"],
                $line["use_date"],
                $CFG_GLPI["date_tax"],
                "n"
            );

            $tmp         = Infocom::Amort(
                $line["sink_type"],
                $line["value"],
                $line["sink_time"],
                $line["sink_coeff"],
                $line["buy_date"],
                $line["use_date"],
                $CFG_GLPI["date_tax"],
                "all"
            );

            if (is_array($tmp) && (count($tmp) > 0)) {
                foreach ($tmp["annee"] as $key => $val) {
                    if ($tmp["vcnetfin"][$key] > 0) {
                        if (!isset($valeurnettegraph[$val])) {
                            $valeurnettegraph[$val] = 0;
                        }
                        $valeurnettegraph[$val] += $tmp["vcnetdeb"][$key];
                    }
                }
            }

            if (!empty($line["buy_date"])) {
                $year = substr($line["buy_date"], 0, 4);

                if ($line["value"] > 0) {
                    if (!isset($valeurgraph[$year])) {
                        $valeurgraph[$year] = 0;
                    }
                    $valeurgraph[$year] += $line["value"];
                }
            }

            $valeurnette = str_replace([" ", "-"], ["", ""], $valeurnette);
            if (!empty($valeurnette)) {
                $valeurnettesoustot += $valeurnette;
            }
        }

        $valeurtot      += $valeursoustot;
        $valeurnettetot += $valeurnettesoustot;

        if (count($valeurnettegraph) > 0) {
            echo "<tr><td colspan='5' class='center'>";
            ksort($valeurnettegraph);
            $valeurnettegraphdisplay = array_map('round', $valeurnettegraph);

            foreach ($valeurnettegraph as $key => $val) {
                if (!isset($valeurnettegraphtot[$key])) {
                    $valeurnettegraphtot[$key] = 0;
                }
                $valeurnettegraphtot[$key] += $valeurnettegraph[$key];
            }

            $stat->displayLineGraph(
                sprintf(
                    __('%1$s account net value'),
                    $item->getTypeName(1)
                ),
                array_keys($valeurnettegraphdisplay),
                [
                    [
                        'data' => $valeurnettegraphdisplay,
                    ],
                ],
                $chart_opts
            );

            echo "</td></tr>\n";
        }

        if (count($valeurgraph) > 0) {
            echo "<tr><td colspan='5' class='center'>";
            ksort($valeurgraph);
            $valeurgraphdisplay = array_map('round', $valeurgraph);

            foreach ($valeurgraph as $key => $val) {
                if (!isset($valeurgraphtot[$key])) {
                    $valeurgraphtot[$key] = 0;
                }
                $valeurgraphtot[$key] += $valeurgraph[$key];
            }
            $stat->displayLineGraph(
                sprintf(
                    __('%1$s value'),
                    $item->getTypeName(1)
                ),
                array_keys($valeurgraphdisplay),
                [
                    [
                        'data' => $valeurgraphdisplay,
                    ],
                ],
                $chart_opts
            );
            echo "</td></tr>";
        }
        echo "</table>\n";
        return true;
    }
    return false;
}


$types = $CFG_GLPI["infocom_types"];

$i = 0;
echo "<table width='90%'><tr><td class='center top'>";
while (count($types) > 0) {
    $type = array_shift($types);

    if (display_infocoms_report($type, $_POST["date1"], $_POST["date2"])) {
        echo "</td>";
        $i++;

        if (($i % 2) == 0) {
            echo "</tr><tr>";
        }

        echo "<td class='center top'>";
    }
}

if (($i % 2) == 0) {
    echo "&nbsp;</td><td>&nbsp;";
}

echo "&nbsp;</td></tr></table>";

//TRANS: %1$s and %2$s are values
$tmpmsg = sprintf(
    __('Total: Value=%1$s - Account net value=%2$s'),
    Html::formatNumber($valeurtot),
    Html::formatNumber($valeurnettetot)
);
echo "<div class='center'><h3>$tmpmsg</h3></div>\n";

if (count($valeurnettegraphtot) > 0) {
    $valeurnettegraphtotdisplay = array_map('round', $valeurnettegraphtot);

    $stat->displayLineGraph(
        __('Total account net value'),
        array_keys($valeurnettegraphtotdisplay),
        [
            [
                'data' => $valeurnettegraphtotdisplay,
            ],
        ],
        $chart_opts
    );
}
if (count($valeurgraphtot) > 0) {
    $valeurgraphtotdisplay = array_map('round', $valeurgraphtot);

    $stat->displayLineGraph(
        __('Total value'),
        array_keys($valeurgraphtotdisplay),
        [
            [
                'data' => $valeurgraphtotdisplay,
            ],
        ],
        $chart_opts
    );
}

Html::footer();
