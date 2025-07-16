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

use Glpi\Event;

Session::checkRight("datacenter", READ);

if (empty($_GET["id"])) {
    $_GET["id"] = "";
}
if (!isset($_GET["withtemplate"])) {
    $_GET["withtemplate"] = "";
}

$room = new DCRoom();

if (isset($_POST["add"])) {
    $room->check(-1, CREATE, $_POST);

    if ($newID = $room->add($_POST)) {
        Event::log(
            $newID,
            "dcrooms",
            4,
            "inventory",
            sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"])
        );
        if ($_SESSION['glpibackcreated']) {
            Html::redirect($room->getLinkURL());
        }
    }
    Html::back();
} elseif (isset($_POST["delete"])) {
    $room->check($_POST["id"], DELETE);
    $room->delete($_POST);

    Event::log(
        $_POST["id"],
        "dcrooms",
        4,
        "inventory",
        //TRANS: %s is the user login
        sprintf(__('%s deletes an item'), $_SESSION["glpiname"])
    );
    $room->redirectToList();
} elseif (isset($_POST["restore"])) {
    $room->check($_POST["id"], DELETE);

    $room->restore($_POST);
    Event::log(
        $_POST["id"],
        "dcrooms",
        4,
        "inventory",
        //TRANS: %s is the user login
        sprintf(__('%s restores an item'), $_SESSION["glpiname"])
    );
    $room->redirectToList();
} elseif (isset($_POST["purge"])) {
    $room->check($_POST["id"], PURGE);

    $room->delete($_POST, true);
    Event::log(
        $_POST["id"],
        "dcrooms",
        4,
        "inventory",
        //TRANS: %s is the user login
        sprintf(__('%s purges an item'), $_SESSION["glpiname"])
    );
    $room->redirectToList();
} elseif (isset($_POST["update"])) {
    $room->check($_POST["id"], UPDATE);

    $room->update($_POST);
    Event::log(
        $_POST["id"],
        "dcrooms",
        4,
        "inventory",
        //TRANS: %s is the user login
        sprintf(__('%s updates an item'), $_SESSION["glpiname"])
    );
    Html::back();
} else {
    $options = [
        'id' => $_GET["id"],
    ];
    if (
        isset($_REQUEST['_add_fromitem'])
        && isset($_REQUEST['datacenters_id'])
    ) {
        $options['datacenters_id'] = $_REQUEST['datacenters_id'];
        $datacenter = new Datacenter();
        $datacenter->getFromDB($options['datacenters_id']);
        $options['locations_id'] = $datacenter->fields['locations_id'];
    }
    $menus = ["management", "datacenter", "DCRoom"];
    DCRoom::displayFullPageForItem($_GET["id"], $menus, $options);
}
