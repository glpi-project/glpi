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
 * @since 0.84
 */

use Glpi\Event;

include('../inc/includes.php');

Session::checkCentralAccess();
$npv = new IPNetwork_Vlan();
if (isset($_POST["add"])) {
    $npv->check(-1, CREATE, $_POST);

    if (isset($_POST["vlans_id"]) && ($_POST["vlans_id"] > 0)) {
        $npv->assignVlan($_POST["ipnetworks_id"], $_POST["vlans_id"]);
        Event::log(
            0,
            "ipnetwork",
            5,
            "inventory",
            //TRANS: %s is the user login
            sprintf(__('%s associates a VLAN to a network port'), $_SESSION["glpiname"])
        );
    }
    Html::back();
}

Html::displayErrorAndDie('Lost');
