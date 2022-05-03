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

use Glpi\Event;

include('../inc/includes.php');

Session::checkRight("datacenter", READ);

if (empty($_GET["id"])) {
    $_GET["id"] = "";
}
if (!isset($_GET["withtemplate"])) {
    $_GET["withtemplate"] = "";
}

$passive_equip = new PassiveDCEquipment();

if (isset($_POST["add"])) {
    $passive_equip->check(-1, CREATE, $_POST);

    if ($newID = $passive_equip->add($_POST)) {
        Event::log(
            $newID,
            "passivedcequipment",
            4,
            "inventory",
            sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"])
        );
        if ($_SESSION['glpibackcreated']) {
            Html::redirect($passive_equip->getLinkURL());
        }
    }
    Html::back();
} else if (isset($_POST["delete"])) {
    $passive_equip->check($_POST["id"], DELETE);
    $passive_equip->delete($_POST);

    Event::log(
        $_POST["id"],
        "passivedcequipment",
        4,
        "inventory",
        //TRANS: %s is the user login
        sprintf(__('%s deletes an item'), $_SESSION["glpiname"])
    );
    $passive_equip->redirectToList();
} else if (isset($_POST["restore"])) {
    $passive_equip->check($_POST["id"], DELETE);

    $passive_equip->restore($_POST);
    Event::log(
        $_POST["id"],
        "passivedcequipment",
        4,
        "inventory",
        //TRANS: %s is the user login
        sprintf(__('%s restores an item'), $_SESSION["glpiname"])
    );
    $passive_equip->redirectToList();
} else if (isset($_POST["purge"])) {
    $passive_equip->check($_POST["id"], PURGE);

    $passive_equip->delete($_POST, 1);
    Event::log(
        $_POST["id"],
        "passivedcequipment",
        4,
        "inventory",
        //TRANS: %s is the user login
        sprintf(__('%s purges an item'), $_SESSION["glpiname"])
    );
    $passive_equip->redirectToList();
} else if (isset($_POST["update"])) {
    $passive_equip->check($_POST["id"], UPDATE);

    $passive_equip->update($_POST);
    Event::log(
        $_POST["id"],
        "passivedcequipment",
        4,
        "inventory",
        //TRANS: %s is the user login
        sprintf(__('%s updates an item'), $_SESSION["glpiname"])
    );
    Html::back();
} else {
    $options = [
        'withtemplate' => $_GET['withtemplate'],
        'formoptions'  => "data-track-changes=true"
    ];
    if (isset($_GET['position'])) {
        $options['position'] = $_GET['position'];
    }
    if (isset($_GET['room'])) {
        $options['room'] = $_GET['room'];
    }

    $menus = ["assets", "passivedcequipment"];
    PassiveDCEquipment::displayFullPageForItem($_GET['id'], $menus, $options);
}
