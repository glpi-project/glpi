<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

Session::checkRight("reports", READ);

Html::header(Report::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "tools", "report");

if (!isset($_GET["id"])) {
    $_GET["id"] = 0;
}

Report::title();

echo "<form method='get' name='form' action='report.reservation.php'>";
echo "<table class='tab_cadre' width='500'><tr class='tab_bg_2'>";
echo "<td class='center' width='300'>";
User::dropdown(['name'   => 'id',
    'value'  => $_GET["id"],
    'right'  => 'reservation'
]);

echo "</td>";
echo "<td class='center'><input type='submit' class='btn btn-primary' name='submit' value='" .
      __s('Display report') . "'></td></tr>";
echo "</table>";
Html::closeForm();

if ($_GET["id"] > 0) {
    Reservation::showForUser($_GET["id"]);
}
Html::footer();
