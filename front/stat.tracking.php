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

use Glpi\Application\View\TemplateRenderer;
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

if (empty($_GET["date1"]) && empty($_GET["date2"])) {
    $_GET["date1"] = date("Y-m-d", mktime(1, 0, 0, date("m"), date("d"), date("Y") - 1));
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

$params = [
    'itemtype'  => $_GET["itemtype"] ?? "",
    'type'      => $_GET["type"] ?? "user",
    'date1'     => $_GET["date1"],
    'date2'     => $_GET["date2"],
    'value2'    => $_GET["value2"] ?? 0,
    'start'     => $_GET["start"] ?? 0,
    'showgraph' => $_GET["showgraph"] ?? 0,
];

$caract = [
    'itilcategories_id'   => ['title' => _n('Category', 'Categories', 1)],
    'itilcategories_tree' => ['title' => __('Category tree')],
    'urgency'             => ['title' => __('Urgency')],
    'impact'              => ['title' => __('Impact')],
    'priority'            => ['title' => __('Priority')],
    'solutiontypes_id'    => ['title' => SolutionType::getTypeName(1)]
];

if ($params['itemtype'] == 'Ticket') {
    $caract['type']            = ['title' => _n('Type', 'Types', 1)];
    $caract['requesttypes_id'] = ['title' => RequestType::getTypeName(1)];
    $caract['locations_id']    = ['title' => Location::getTypeName(1)];
    $caract['locations_tree']  = ['title' => __('Location tree')];
}

$items = [
    _n('Requester', 'Requesters', 1) => [
        'user'               => ['title' => _n('Requester', 'Requesters', 1)],
        'users_id_recipient' => ['title' => __('Writer')],
        'group'              => ['title' => Group::getTypeName(1)],
        'group_tree'         => ['title' => __('Group tree')],
        'usertitles_id'      => ['title' => _x('person', 'Title')],
        'usercategories_id'  => ['title' => _n('Category', 'Categories', 1)]
    ],
    __('Characteristics') => $caract,
    __('Assigned to') => [
        'technicien'          => ['title' => __('Technician as assigned')],
        'technicien_followup' => ['title' => __('Technician in tasks')],
        'groups_id_assign'    => ['title' => Group::getTypeName(1)],
        'groups_tree_assign'  => ['title' => __('Group tree')],
        'suppliers_id_assign' => ['title' => Supplier::getTypeName(1)]
    ]
];

$values = [];
foreach ($items as $label => $tab) {
    foreach ($tab as $key => $val) {
        $values[$label][$key] = $val['title'];
    }
}

$val = Stat::getItems(
    $params["itemtype"],
    $params["date1"],
    $params["date2"],
    $params["type"],
    $params["value2"]
);

Stat::title();
Html::printPager(
    $params['start'],
    count($val),
    $CFG_GLPI['root_doc'] . '/front/stat.tracking.php',
    http_build_query($params, '', '&amp;'),
    'Stat',
    $params
);

TemplateRenderer::getInstance()->display('pages/assistance/stats/tracking_form.html.twig', [
    'itemtype'  => $params["itemtype"],
    'types'     => $values,
    'type'      => $params['type'],
    'date1'     => $params["date1"],
    'date2'     => $params["date2"],
    'value2'    => $params["value2"],
    'showgraph' => $params["showgraph"],
]);

if (!$params['showgraph']) {
    Stat::showTable(
        $params["itemtype"],
        $params["type"],
        $params["date1"],
        $params["date2"],
        $params['start'],
        $val,
        $params['value2']
    );
} else {
    $data_params = [
        'itemtype' => $params['itemtype'],
        'type'     => $params["type"],
        'date1'    => $params['date1'],
        'date2'    => $params['date2'],
        'start'    => $params['start'],
        'val'      => $val,
        'value2'   => $params['value2'],
    ];

    $stat = new Stat();

    $stat->displayPieGraphFromData(new StatDataOpened($data_params));
    $stat->displayPieGraphFromData(new StatDataSolved($data_params));
    $stat->displayPieGraphFromData(new StatDataLate($data_params));
    $stat->displayPieGraphFromData(new StatDataClosed($data_params));

    if ($_GET['itemtype'] == 'Ticket') {
        $stat->displayPieGraphFromData(new StatDataOpenSatisfaction($data_params));
    }
}

Html::footer();
