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


$_GET["showgraph"] = (int) ($_GET["showgraph"] ?? 0);

//sanitize dates
foreach (['date1', 'date2'] as $key) {
    if (array_key_exists($key, $_GET) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $_GET[$key]) !== 1) {
        unset($_GET[$key]);
    }
}
if (empty($_GET["date1"]) && empty($_GET["date2"])) {
    $year          = ((int) date("Y")) - 1;
    $_GET["date1"] = date("Y-m-d", mktime(1, 0, 0, (int) date("m"), (int) date("d"), $year));
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
    $_GET["start"] = (int) $_GET["start"];
}

if (empty($_GET["dropdown"])) {
    $_GET["dropdown"] = "ComputerType";
} else {
    $_GET["dropdown"] = (string) $_GET["dropdown"];
}

if (!isset($_GET['itemtype'])) {
    $_GET['itemtype'] = 'Ticket';
} else {
    $_GET['itemtype'] = (string) $_GET["itemtype"];
}

$stat = new Stat();
Stat::title();

TemplateRenderer::getInstance()->display('pages/assistance/stats/form.html.twig', [
    'target'    => 'stat.location.php',
    'itemtype'  => $_GET['itemtype'],
    'type_params' => [
        'field' => 'dropdown',
        'value' => $_GET["dropdown"],
        'elements' => Stat::getItemCharacteristicStatFields(),
    ],
    'date1'     => $_GET["date1"],
    'date2'     => $_GET["date2"],
    'showgraph' => $_GET['showgraph'],
]);

if (
    !($item = getItemForItemtype($_GET["dropdown"]))
) {
    // Do nothing
    Html::footer();
    return;
}


if (!($item instanceof CommonDevice)) {
    $type = "comp_champ";

    $val = Stat::getItems($_GET['itemtype'], $_GET["date1"], $_GET["date2"], $_GET["dropdown"]);
    $params = ['type'     => $type,
        'dropdown' => $_GET["dropdown"],
        'date1'    => $_GET["date1"],
        'date2'    => $_GET["date2"],
        'start'    => $_GET["start"],
    ];
} else {
    $type  = "device";

    $val = Stat::getItems($_GET['itemtype'], $_GET["date1"], $_GET["date2"], $_GET["dropdown"]);
    $params = ['type'     => $type,
        'dropdown' => $_GET["dropdown"],
        'date1'    => $_GET["date1"],
        'date2'    => $_GET["date2"],
        'start'    => $_GET["start"],
    ];
}

Html::printPager(
    $_GET['start'],
    count($val),
    $CFG_GLPI['root_doc'] . '/front/stat.location.php',
    Toolbox::append_params(
        [
            'date1'    => $_GET['date1'],
            'date2'    => $_GET['date2'],
            'itemtype' => $_GET['itemtype'],
            'dropdown' => $_GET['dropdown'],
        ]
    ),
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
