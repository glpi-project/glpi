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

require_once(__DIR__ . '/_check_webserver_config.php');

use Glpi\Application\View\TemplateRenderer;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Stat\Data\Location\StatDataClosed;
use Glpi\Stat\Data\Location\StatDataLate;
use Glpi\Stat\Data\Location\StatDataOpened;
use Glpi\Stat\Data\Location\StatDataOpenSatisfaction;
use Glpi\Stat\Data\Location\StatDataSolved;

use function Safe\mktime;
use function Safe\preg_match;

global $CFG_GLPI;

Html::header(__('Statistics'), '', "helpdesk", "stat");

Session::checkRight("statistic", READ);

if (!$item = getItemForItemtype($_GET['itemtype'])) {
    throw new BadRequestHttpException();
}

//sanitize dates
foreach (['date1', 'date2'] as $key) {
    if (array_key_exists($key, $_GET) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $_GET[$key]) !== 1) {
        unset($_GET[$key]);
    }
}
if (empty($_GET["date1"]) && empty($_GET["date2"])) {
    $_GET["date1"] = date("Y-m-d", mktime(1, 0, 0, (int) date("m"), (int) date("d"), ((int) date("Y")) - 1));
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
    'type'      => (string) ($_GET["type"] ?? "user"),
    'date1'     => $_GET["date1"],
    'date2'     => $_GET["date2"],
    'value2'    => $_GET["value2"] ?? 0,
    'start'     => (int) ($_GET["start"] ?? 0),
    'showgraph' => (int) ($_GET["showgraph"] ?? 0),
];

$values = Stat::getITILStatFields($params['itemtype']);

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
    http_build_query($params, '', '&'),
    'Stat',
    $params
);

TemplateRenderer::getInstance()->display('pages/assistance/stats/form.html.twig', [
    'target'    => 'stat.tracking.php',
    'itemtype'  => $params["itemtype"],
    'type_params' => [
        'field' => 'type',
        'value' => $params["type"],
        'elements' => $values,
    ],
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
