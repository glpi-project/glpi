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

Session::checkRight("printer", READ);

if (!isset($_GET["id"])) {
    $_GET["id"] = "";
}
if (!isset($_GET["withtemplate"])) {
    $_GET["withtemplate"] = "";
}

$print = new Printer();
if (isset($_POST["add"])) {
    $print->check(-1, CREATE, $_POST);

    if ($newID = $print->add($_POST)) {
        Event::log(
            $newID,
            "printers",
            4,
            "inventory",
            sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"])
        );
        if ($_SESSION['glpibackcreated']) {
            Html::redirect($print->getLinkURL());
        }
    }
    Html::back();
} else if (isset($_POST["delete"])) {
    $print->check($_POST["id"], DELETE);
    $print->delete($_POST);

    Event::log(
        $_POST["id"],
        "printers",
        4,
        "inventory",
        //TRANS: %s is the user login
        sprintf(__('%s deletes an item'), $_SESSION["glpiname"])
    );
    $print->redirectToList();
} else if (isset($_POST["restore"])) {
    $print->check($_POST["id"], DELETE);

    $print->restore($_POST);
    Event::log(
        $_POST["id"],
        "printers",
        4,
        "inventory",
        //TRANS: %s is the user login
        sprintf(__('%s restores an item'), $_SESSION["glpiname"])
    );
    $print->redirectToList();
} else if (isset($_POST["purge"])) {
    $print->check($_POST["id"], PURGE);

    $print->delete($_POST, 1);
    Event::log(
        $_POST["id"],
        "printers",
        4,
        "inventory",
        //TRANS: %s is the user login
        sprintf(__('%s purges an item'), $_SESSION["glpiname"])
    );
    $print->redirectToList();
} else if (isset($_POST["update"])) {
    $print->check($_POST["id"], UPDATE);

    $print->update($_POST);
    Event::log(
        $_POST["id"],
        "printers",
        4,
        "inventory",
        //TRANS: %s is the user login
        sprintf(__('%s updates an item'), $_SESSION["glpiname"])
    );
    Html::back();
} else if (isset($_POST["unglobalize"])) {
    $print->check($_POST["id"], UPDATE);

    Computer_Item::unglobalizeItem($print);
    Event::log(
        $_POST["id"],
        "printers",
        4,
        "inventory",
        //TRANS: %s is the user login
        sprintf(__('%s sets unitary management'), $_SESSION["glpiname"])
    );
    Html::redirect($print->getFormURLWithID($_POST["id"]));
} else {
    $menus = ["assets", "printer"];
    Printer::displayFullPageForItem($_GET["id"], $menus, [
        'withtemplate' => $_GET["withtemplate"],
        'formoptions'  => "data-track-changes=true"
    ]);
}
