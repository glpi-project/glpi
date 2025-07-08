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

use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Stat\Data\Sglobal\StatDataAverageSatisfaction;
use Glpi\Stat\Data\Sglobal\StatDataSatisfaction;
use Glpi\Stat\Data\Sglobal\StatDataTicketAverageTime;
use Glpi\Stat\Data\Sglobal\StatDataTicketNumber;

use function Safe\mktime;
use function Safe\preg_match;

Html::header(__('Statistics'), '', "helpdesk", "stat");

Session::checkRight("statistic", READ);

//sanitize dates
foreach (['date1', 'date2'] as $key) {
    if (array_key_exists($key, $_GET) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $_GET[$key]) !== 1) {
        unset($_GET[$key]);
    }
}
if (empty($_GET["date1"]) && empty($_GET["date2"])) {
    $year          = date("Y") - 1;
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

Stat::title();

if (!$item = getItemForItemtype($_GET['itemtype'])) {
    throw new BadRequestHttpException();
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

echo "<div class='text-center mt-3'>";
$stat->displayLineGraphFromData(new StatDataTicketNumber($stat_params));
$stat->displayLineGraphFromData(new StatDataTicketAverageTime($stat_params));

if ($_GET['itemtype'] == 'Ticket') {
    $stat->displayLineGraphFromData(new StatDataSatisfaction($stat_params));
    $stat->displayLineGraphFromData(new StatDataAverageSatisfaction($stat_params));
}
echo "</div>";

Html::footer();
