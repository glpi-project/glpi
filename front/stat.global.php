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

use Glpi\Stat\Data\Sglobal\StatDataAverageSatisfaction;
use Glpi\Stat\Data\Sglobal\StatDataSatisfaction;
use Glpi\Stat\Data\Sglobal\StatDataTicketAverageTime;
use Glpi\Stat\Data\Sglobal\StatDataTicketNumber;

include('../inc/includes.php');

Html::header(__('Statistics'), $_SERVER['PHP_SELF'], "helpdesk", "stat");

Session::checkRight("statistic", READ);

if (empty($_GET["date1"]) && empty($_GET["date2"])) {
    $year          = date("Y") - 1;
    $_GET["date1"] = date("Y-m-d", mktime(1, 0, 0, (int)date("m"), (int)date("d"), $year));
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

Stat::title();

if (!$item = getItemForItemtype($_GET['itemtype'])) {
    exit;
}

$stat = new Stat();

$stat->displaySearchForm(
    $_GET['itemtype'],
    $_GET['date1'],
    $_GET['date2']
);

$stat_params = [
    'itemtype' => $_GET['itemtype'],
    'date1'    => $_GET['date1'],
    'date2'    => $_GET['date2'],
];

$stat->displayLineGraphFromData(new StatDataTicketNumber($stat_params));
$stat->displayLineGraphFromData(new StatDataTicketAverageTime($stat_params));

if ($_GET['itemtype'] == 'Ticket') {
    $stat->displayLineGraphFromData(new StatDataSatisfaction($stat_params));
    $stat->displayLineGraphFromData(new StatDataAverageSatisfaction($stat_params));
}

Html::footer();
