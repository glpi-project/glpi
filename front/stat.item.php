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

use function Safe\mktime;

Html::header(__('Statistics'), '', "helpdesk", "stat");

Session::checkRight("statistic", READ);

if (isset($_GET["date1"])) {
    $_POST["date1"] = $_GET["date1"];
}
if (isset($_GET["date2"])) {
    $_POST["date2"] = $_GET["date2"];
}

if (empty($_POST["date1"]) && empty($_POST["date2"])) {
    $year           = ((int) date("Y")) - 1;
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

if (!isset($_GET["start"])) {
    $_GET["start"] = 0;
}

Stat::title();

TemplateRenderer::getInstance()->display('pages/assistance/stats/form.html.twig', [
    'target'    => 'stat.item.php',
    'itemtype'  => $_GET['itemtype'],
    'date1'     => $_POST["date1"],
    'date2'     => $_POST["date2"],
]);

Stat::showItems('stat.item.php', $_POST["date1"], $_POST["date2"], $_GET['start'], $_GET["itemtype"] ?? 'Ticket');

Html::footer();
