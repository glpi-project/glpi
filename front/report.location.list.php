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

use Glpi\Socket;

/** @var array $CFG_GLPI */
global $CFG_GLPI;

include('../inc/includes.php');

Session::checkRight("reports", READ);

if (isset($_POST["locations_id"]) && $_POST["locations_id"]) {
    Html::header(Report::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "tools", "report");

    Report::title();

    // Titre
    $name = Dropdown::getDropdownName("glpi_locations", $_POST["locations_id"]);
    echo "<div class='center spaced'><h2>" . sprintf(__('Network report by location: %s'), $name) .
        "</h2></div>";

    $sons = getSonsOf('glpi_locations', $_POST['locations_id']);
    $where = ['glpi_locations.id' => $sons];

    Report::reportForNetworkInformations(
        'glpi_locations', //from
        ['PORT_1' => 'id', 'glpi_networkportethernets' => 'networkports_id'], //joincrit
        $where, //where
        ['glpi_sockets.name AS extra'], //select
        [], //left join
        [
            'glpi_sockets'  => [
                'ON'  => [
                    'glpi_sockets'  => 'locations_id',
                    'glpi_locations'  => 'id',
                ],
            ],
            'glpi_networkportethernets'   => [
                'ON'  => [
                    'glpi_networkportethernets' => 'networkports_id',
                    'glpi_sockets'              => 'networkports_id',
                ],
            ],
        ], //inner join
        ['glpi_locations.completename', 'PORT_1.name'], //order
        Socket::getTypeName()
    );

    Html::footer();
} else {
    Html::redirect($CFG_GLPI['root_doc'] . "/front/report.networking.php");
}
