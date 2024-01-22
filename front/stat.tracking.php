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

use Glpi\Stat\Data\Location\StatDataClosed;
use Glpi\Stat\Data\Location\StatDataLate;
use Glpi\Stat\Data\Location\StatDataOpened;
use Glpi\Stat\Data\Location\StatDataOpenSatisfaction;
use Glpi\Stat\Data\Location\StatDataSolved;

/** @var array $CFG_GLPI */
global $CFG_GLPI;

include('../inc/includes.php');

Html::header(__('Statistics'), '', "helpdesk", "stat");

Session::checkRight("statistic", READ);

if (!$item = getItemForItemtype($_GET['itemtype'])) {
    exit;
}

if (empty($_GET["type"])) {
    $_GET["type"] = "user";
} else {
    $_GET["type"] = (string)$_GET["type"];
}

if (empty($_GET["showgraph"])) {
    $_GET["showgraph"] = 0;
} else {
    $_GET["showgraph"] = (int)$_GET["showgraph"];
}

if (empty($_GET["value2"])) {
    $_GET["value2"] = 0;
}

//sanitize dates
foreach (['date1', 'date2'] as $key) {
    if (array_key_exists($key, $_GET) && preg_match('/\d{4}-\d{2}-\d{2}/', (string)$_GET[$key]) !== 1) {
        unset($_GET[$key]);
    }
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
} else {
    $_GET["start"] = (int)$_GET["start"];
}

$stat = new Stat();
Stat::title();

$requester = ['user'               => ['title' => _n('Requester', 'Requesters', 1)],
    'users_id_recipient' => ['title' => __('Writer')],
    'group'              => ['title' => Group::getTypeName(1)],
    'group_tree'         => ['title' => __('Group tree')],
    'usertitles_id'      => ['title' => _x('person', 'Title')],
    'usercategories_id'  => ['title' => _n('Category', 'Categories', 1)]
];

$caract    = ['itilcategories_id'   => ['title' => _n('Category', 'Categories', 1)],
    'itilcategories_tree' => ['title' => __('Category tree')],
    'urgency'             => ['title' => __('Urgency')],
    'impact'              => ['title' => __('Impact')],
    'priority'            => ['title' => __('Priority')],
    'solutiontypes_id'    => ['title' => SolutionType::getTypeName(1)]
];

if ($_GET['itemtype'] == 'Ticket') {
    $caract['type']            = ['title' => _n('Type', 'Types', 1)];
    $caract['requesttypes_id'] = ['title' => RequestType::getTypeName(1)];
    $caract['locations_id']    = ['title' => Location::getTypeName(1)];
    $caract['locations_tree']  = ['title' => __('Location tree')];
}


$items = [_n('Requester', 'Requesters', 1)       => $requester,
    __('Characteristics') => $caract,
    __('Assigned to')     => ['technicien'
                                                   => ['title' => __('Technician as assigned')],
        'technicien_followup'
                                                   => ['title' => __('Technician in tasks')],
        'groups_id_assign'
                                                   => ['title' => Group::getTypeName(1)],
        'groups_tree_assign'
                                                   => ['title' => __('Group tree')],
        'suppliers_id_assign'
                                                   => ['title' => Supplier::getTypeName(1)]
    ]
];

$values = [];
foreach ($items as $label => $tab) {
    foreach ($tab as $key => $val) {
        $values[$label][$key] = $val['title'];
    }
}

echo "<div class='center'><form method='get' name='form' action='stat.tracking.php'>";
// Keep it first param
echo "<input type='hidden' name='itemtype' value=\"" . htmlspecialchars($_GET["itemtype"]) . "\">";

echo "<table class='tab_cadre_fixe'>";
echo "<tr class='tab_bg_2'><td rowspan='2' class='center' width='30%'>";
Dropdown::showFromArray('type', $values, ['value' => $_GET['type']]);
echo "</td>";
echo "<td class='right'>" . __('Start date') . "</td><td>";
Html::showDateField("date1", ['value' => $_GET["date1"]]);
echo "</td>";
echo "<td class='right'>" . __('Show graphics') . "</td>";
echo "<td rowspan='2' class='center'>";
echo "<input type='submit' class='btn btn-primary' name='submit' value=\"" . __s('Display report') . "\"></td>" .
     "</tr>";

echo "<tr class='tab_bg_2'><td class='right'>" . __('End date') . "</td><td>";
Html::showDateField("date2", ['value' => $_GET["date2"]]);
echo "</td><td class='center'>";
echo "<input type='hidden' name='value2' value='" . $_GET["value2"] . "'>";
Dropdown::showYesNo('showgraph', $_GET['showgraph']);
echo "</td></tr>";
echo "</table>";
// form using GET method : CRSF not needed
echo "</form>";
echo "</div>";

$val    = Stat::getItems(
    $_GET["itemtype"],
    $_GET["date1"],
    $_GET["date2"],
    $_GET["type"],
    $_GET["value2"]
);
$params = ['type'   => $_GET["type"],
    'date1'  => $_GET["date1"],
    'date2'  => $_GET["date2"],
    'value2' => $_GET["value2"],
    'start'  => $_GET["start"]
];

Html::printPager(
    $_GET['start'],
    count($val),
    $CFG_GLPI['root_doc'] . '/front/stat.tracking.php',
    Toolbox::append_params(
        [
            'date1'     => $_GET['date1'],
            'date2'     => $_GET['date2'],
            'type'      => $_GET['type'],
            'showgraph' => $_GET['showgraph'],
            'itemtype'  => $_GET['itemtype'],
            'value2'    => $_GET['value2'],
        ],
        '&amp;'
    ),
    'Stat',
    $params
);

if (!$_GET['showgraph']) {
    Stat::showTable(
        $_GET["itemtype"],
        $_GET["type"],
        $_GET["date1"],
        $_GET["date2"],
        $_GET['start'],
        $val,
        $_GET['value2']
    );
} else {
    $data = Stat::getData(
        $_GET["itemtype"],
        $_GET["type"],
        $_GET["date1"],
        $_GET["date2"],
        $_GET['start'],
        $val,
        $_GET['value2']
    );

    $data_params = [
        'itemtype' => $_GET['itemtype'],
        'type'     => $_GET["type"],
        'date1'    => $_GET['date1'],
        'date2'    => $_GET['date2'],
        'start'    => $_GET['start'],
        'val'      => $val,
        'value2'   => $_GET['value2'],
    ];

    $stat->displayPieGraphFromData(new StatDataOpened($data_params));
    $stat->displayPieGraphFromData(new StatDataSolved($data_params));
    $stat->displayPieGraphFromData(new StatDataLate($data_params));
    $stat->displayPieGraphFromData(new StatDataClosed($data_params));

    if ($_GET['itemtype'] == 'Ticket') {
        $stat->displayPieGraphFromData(new StatDataOpenSatisfaction($data_params));
    }
}

Html::footer();
