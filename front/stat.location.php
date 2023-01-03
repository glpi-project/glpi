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

use Glpi\Stat\Data\Location\StatDataClosed;
use Glpi\Stat\Data\Location\StatDataLate;
use Glpi\Stat\Data\Location\StatDataOpened;
use Glpi\Stat\Data\Location\StatDataOpenSatisfaction;
use Glpi\Stat\Data\Location\StatDataSolved;

include('../inc/includes.php');

Html::header(__('Statistics'), '', "helpdesk", "stat");

Session::checkRight("statistic", READ);


if (empty($_GET["showgraph"])) {
    $_GET["showgraph"] = 0;
}

if (empty($_GET["date1"]) && empty($_GET["date2"])) {
    $year          = date("Y") - 1;
    $_GET["date1"] = date("Y-m-d", mktime(1, 0, 0, date("m"), date("d"), $year));
    $_GET["date2"] = date("Y-m-d");
}

if (
    !empty($_GET["date1"])
    && !empty($_GET["date2"])
    && (strcmp($_GET["date2"], $_GET["date1"]) < 0)
) {
    $tmp           = $_GET["date1"];
    $_GET["date1"] = $_GET["date2"];
    $_GET["date2"] = $tmp;
}

if (!isset($_GET["start"])) {
    $_GET["start"] = 0;
}
// Why this test ?? For me it's doing nothing
if (isset($_GET["dropdown"])) {
    $_GET["dropdown"] = $_GET["dropdown"];
}

if (empty($_GET["dropdown"])) {
    $_GET["dropdown"] = "ComputerType";
}

if (!isset($_GET['itemtype'])) {
    $_GET['itemtype'] = 'Ticket';
}

$stat = new Stat();
Stat::title();

echo "<form method='get' name='form' action='stat.location.php'>";
// keep it first param
echo "<input type='hidden' name='itemtype' value='" . $_GET['itemtype'] . "'>";

echo "<table class='tab_cadre_fixe' ><tr class='tab_bg_2'><td rowspan='2' width='30%'>";
$values = [_n('Dropdown', 'Dropdowns', Session::getPluralNumber()) => ['ComputerType'    => _n('Type', 'Types', 1),
    'ComputerModel'   => _n('Model', 'Models', 1),
    'OperatingSystem' => OperatingSystem::getTypeName(1),
    'Location'        => Location::getTypeName(1)
],
];
$devices = Dropdown::getDeviceItemTypes();
foreach ($devices as $label => $dp) {
    foreach ($dp as $i => $name) {
        $values[$label][$i] = $name;
    }
}

Dropdown::showFromArray('dropdown', $values, ['value' => $_GET["dropdown"]]);

echo "</td>";

echo "<td class='right'>" . __('Start date') . "</td><td>";
Html::showDateField("date1", ['value' => $_GET["date1"]]);
echo "</td>";
echo "<td class='right'>" . __('Show graphics') . "</td>";
echo "<td rowspan='2' class='center'>";
echo "<input type='submit' class='btn btn-primary' name='submit' value='" . __s('Display report') . "'></td></tr>";

echo "<tr class='tab_bg_2'><td class='right'>" . __('End date') . "</td><td>";
Html::showDateField("date2", ['value' => $_GET["date2"]]);
echo "</td><td class='center'>";
Dropdown::showYesNo('showgraph', $_GET['showgraph']);
echo "</td>";
echo "</tr>";
echo "</table>";
// form using GET method : CRSF not needed
echo "</form>";

if (
    empty($_GET["dropdown"])
    || !($item = getItemForItemtype($_GET["dropdown"]))
) {
   // Do nothing
    Html::footer();
    exit();
}


if (!($item instanceof CommonDevice)) {
   // echo "Dropdown";
    $type = "comp_champ";

    $val = Stat::getItems($_GET['itemtype'], $_GET["date1"], $_GET["date2"], $_GET["dropdown"]);
    $params = ['type'     => $type,
        'dropdown' => $_GET["dropdown"],
        'date1'    => $_GET["date1"],
        'date2'    => $_GET["date2"],
        'start'    => $_GET["start"]
    ];
} else {
   //   echo "Device";
    $type  = "device";

    $val = Stat::getItems($_GET['itemtype'], $_GET["date1"], $_GET["date2"], $_GET["dropdown"]);
    $params = ['type'     => $type,
        'dropdown' => $_GET["dropdown"],
        'date1'    => $_GET["date1"],
        'date2'    => $_GET["date2"],
        'start'    => $_GET["start"]
    ];
}

Html::printPager(
    $_GET['start'],
    count($val),
    $CFG_GLPI['root_doc'] . '/front/stat.location.php',
    "date1=" . $_GET["date1"] . "&amp;date2=" . $_GET["date2"] .
                     "&amp;itemtype=" . $_GET['itemtype'] . "&amp;dropdown=" . $_GET["dropdown"],
    'Stat',
    $params
);

if (!$_GET['showgraph']) {
    Stat::showTable(
        $_GET['itemtype'],
        $type,
        $_GET["date1"],
        $_GET["date2"],
        $_GET['start'],
        $val,
        $_GET["dropdown"]
    );
} else {
    $data_params = [
        'itemtype' => $_GET['itemtype'],
        'type'     => $type,
        'date1'    => $_GET['date1'],
        'date2'    => $_GET['date2'],
        'start'    => $_GET['start'],
        'val'      => $val,
        'value2' => $_GET['dropdown'],
    ];

    $stat->displayPieGraphFromData(new StatDataOpened($data_params));
    $stat->displayPieGraphFromData(new StatDataSolved($data_params));
    $stat->displayPieGraphFromData(new StatDataLate($data_params));
    $stat->displayPieGraphFromData(new StatDataClosed($data_params));
    $stat->displayPieGraphFromData(new StatDataOpenSatisfaction($data_params));
}

Html::footer();
