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

include('../inc/includes.php');

Html::header(__('Statistics'), '', "helpdesk", "stat");

Session::checkRight("statistic", READ);

if (isset($_GET["date1"])) {
    $_POST["date1"] = $_GET["date1"];
}
if (isset($_GET["date2"])) {
    $_POST["date2"] = $_GET["date2"];
}

if (empty($_POST["date1"]) && empty($_POST["date2"])) {
    $year           = date("Y") - 1;
    $_POST["date1"] = date("Y-m-d", mktime(1, 0, 0, date("m"), date("d"), $year));
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

echo "<div class='center'><form method='post' name='form' action='stat.item.php'>";
echo "<table class='tab_cadre'><tr class='tab_bg_2'>";
echo "<td class='right'>" . __('Start date') . "</td><td>";
Html::showDateField("date1", ['value' => $_POST["date1"]]);
echo "</td><td rowspan='2' class='center'>";
echo "<input type='submit' class='btn btn-primary' name='submit' value='" . __s('Display report') . "'></td></tr>";
echo "<tr class='tab_bg_2'><td class='right'>" . __('End date') . "</td><td>";
Html::showDateField("date2", ['value' => $_POST["date2"]]);
echo "</td></tr>";
echo "</table>";
Html::closeForm();
echo "</div>";

Stat::showItems($_SERVER['PHP_SELF'], $_POST["date1"], $_POST["date2"], $_GET['start']);

Html::footer();
