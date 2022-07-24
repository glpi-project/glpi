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

/**
 * Show choices for network reports
 */

use Glpi\Socket;

include('../inc/includes.php');

Session::checkRight("reports", READ);

Html::header(Report::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "tools", "report");

Report::title();

// Titre

echo "<table class='tab_cadre' >";
echo "<tr><th colspan='3'>&nbsp;" . __('Network report') . "</th></tr>";
echo "</table><br>";

// 3. Selection d'affichage pour generer la liste

echo "<form name='form' method='post' action='report.location.list.php'>";
echo "<table class='tab_cadre' width='500'>";
echo "<tr class='tab_bg_1'><td width='120'>" . __('By location') . "</td>";
echo "<td>";
Location::dropdown(['entity' => $_SESSION["glpiactive_entity"]]);
echo "</td><td class='center' width='120'>";
echo "<input type='submit' value=\"" . __s('Display report') . "\" class='btn btn-primary'>";
echo "</td></tr>";
echo "</table>";
Html::closeForm();

echo "<form name='form2' method='post' action='report.switch.list.php'>";
echo "<table class='tab_cadre' width='500'>";
echo "<tr class='tab_bg_1'><td width='120'>" . __('By hardware') . "</td>";
echo "<td>";
NetworkEquipment::dropdown(['name' => 'switch']);
echo "</td><td class='center' width='120'>";
echo "<input type='submit' value=\"" . __s('Display report') . "\" class='btn btn-primary'>";
echo "</td></tr>";
echo "</table>";
Html::closeForm();

if (countElementsInTableForMyEntities("glpi_sockets") > 0) {
    echo "<form name='form3' method='post' action='report.socket.list.php'>";
    echo "<table class='tab_cadre' width='500'>";
    echo "<tr class='tab_bg_1'><td width='120'>" . __('By network socket') . "</td>";
    echo "<td>";
    Socket::dropdown(['name'   => 'prise']);
    echo "</td><td class='center' width='120'>";
    echo "<input type='submit' value=\"" . __s('Display report') . "\" class='btn btn-primary'>";
    echo "</td></tr>";
    echo "</table>";
    Html::closeForm();
}

Html::footer();
