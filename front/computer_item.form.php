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

/**
 * @since 0.84
 */

use Glpi\Event;

include('../inc/includes.php');

Session::checkCentralAccess();

$conn = new Computer_Item();

if (isset($_POST["disconnect"])) {
    $conn->check($_POST["id"], PURGE);
    $conn->delete($_POST, 1);
    Event::log(
        $_POST["computers_id"],
        "computers",
        5,
        "inventory",
        //TRANS: %s is the user login
        sprintf(__('%s disconnects an item'), $_SESSION["glpiname"])
    );
    Html::back();

    // Connect a computer to a printer/monitor/phone/peripheral
} elseif (isset($_POST["add"])) {
    if (isset($_POST["items_id"]) && ($_POST["items_id"] > 0)) {
        $conn->check(-1, CREATE, $_POST);
        if ($conn->add($_POST)) {
            Event::log(
                $_POST["computers_id"],
                "computers",
                5,
                "inventory",
                //TRANS: %s is the user login
                sprintf(__('%s connects an item'), $_SESSION["glpiname"])
            );
        }
    }
    Html::back();
}

Html::displayErrorAndDie('Lost');
