<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

/** @var array $CFG_GLPI */
global $CFG_GLPI;

include('../inc/includes.php');

Session::checkRight("reports", READ);

if (isset($_POST["prise"]) && $_POST["prise"]) {
    Html::header(Report::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "tools", "report");

    Report::title();

    $name = Dropdown::getDropdownName("glpi_sockets", $_POST["prise"]);

   // Titre
    echo "<div class='center spaced'><h2>" . sprintf(__('Network report by outlet: %s'), $name) .
        "</h2></div>";

    Report::reportForNetworkInformations(
        'glpi_sockets', //from
        ['PORT_1' => 'id', 'glpi_networkportethernets' => 'networkports_id'], //joincrit
        ['glpi_sockets.id' => (int) $_POST["prise"]], //where
        ['glpi_locations.completename AS extra'], //select
        [
            'glpi_locations'  => [
                'ON'  => [
                    'glpi_locations'  => 'id',
                    'glpi_sockets'  => 'locations_id'
                ]
            ]
        ], //left join
        [
            'glpi_networkportethernets'   => [
                'ON'  => [
                    'glpi_networkportethernets' => 'networkports_id',
                    'glpi_sockets'              => 'networkports_id'
                ]
            ]
        ], //inner join
        [], //order
        Location::getTypeName()
    );

    Html::footer();
} else {
    Html::redirect($CFG_GLPI['root_doc'] . "/front/report.networking.php");
}
