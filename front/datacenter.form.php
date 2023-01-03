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

use Glpi\Event;

include('../inc/includes.php');

Session::checkRight("datacenter", READ);

if (empty($_GET["id"])) {
    $_GET["id"] = "";
}
if (!isset($_GET["withtemplate"])) {
    $_GET["withtemplate"] = "";
}

$datacenter = new Datacenter();

if (isset($_POST["add"])) {
    $datacenter->check(-1, CREATE, $_POST);

    if ($newID = $datacenter->add($_POST)) {
        Event::log(
            $newID,
            "datacenters",
            4,
            "inventory",
            sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"])
        );
        if ($_SESSION['glpibackcreated']) {
            Html::redirect($datacenter->getLinkURL());
        }
    }
    Html::back();
} else if (isset($_POST["delete"])) {
    $datacenter->check($_POST["id"], DELETE);
    $datacenter->delete($_POST);

    Event::log(
        $_POST["id"],
        "datacenters",
        4,
        "inventory",
        //TRANS: %s is the user login
        sprintf(__('%s deletes an item'), $_SESSION["glpiname"])
    );
    $datacenter->redirectToList();
} else if (isset($_POST["restore"])) {
    $datacenter->check($_POST["id"], DELETE);

    $datacenter->restore($_POST);
    Event::log(
        $_POST["id"],
        "datacenters",
        4,
        "inventory",
        //TRANS: %s is the user login
        sprintf(__('%s restores an item'), $_SESSION["glpiname"])
    );
    $datacenter->redirectToList();
} else if (isset($_POST["purge"])) {
    $datacenter->check($_POST["id"], PURGE);

    $datacenter->delete($_POST, 1);
    Event::log(
        $_POST["id"],
        "datacenters",
        4,
        "inventory",
        //TRANS: %s is the user login
        sprintf(__('%s purges an item'), $_SESSION["glpiname"])
    );
    $datacenter->redirectToList();
} else if (isset($_POST["update"])) {
    $datacenter->check($_POST["id"], UPDATE);

    $datacenter->update($_POST);
    Event::log(
        $_POST["id"],
        "datacenters",
        4,
        "inventory",
        //TRANS: %s is the user login
        sprintf(__('%s updates an item'), $_SESSION["glpiname"])
    );
    Html::back();
} else {
    $menus = ["management", "datacenter"];
    Datacenter::displayFullPageForItem($_GET["id"], $menus, [
        'formoptions'  => "data-track-changes=true"
    ]);
}
