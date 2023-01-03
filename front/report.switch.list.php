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

/**
 * Show network port by network equipment
 */

include('../inc/includes.php');

Session::checkRight("reports", READ);

// Titre
if (isset($_POST["switch"]) && $_POST["switch"]) {
    Html::header(Report::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "tools", "report");

    Report::title();

    $name = Dropdown::getDropdownName("glpi_networkequipments", $_POST["switch"]);
    echo "<div class='center spaced'><h2>" . sprintf(__('Network report by hardware: %s'), $name) .
        "</h2></div>";
    Report::reportForNetworkInformations(
        'glpi_networkequipments AS ITEM', //from
        ['PORT_1' => 'items_id', 'ITEM' => 'id', ['AND' => ['PORT_1.itemtype' => 'NetworkEquipment']]], //joincrit
        ['ITEM.id' => $_POST['switch']] //where
    );

    Html::footer();
} else {
    Html::redirect($CFG_GLPI['root_doc'] . "/front/report.networking.php");
}
