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

use Glpi\Event;

include('../inc/includes.php');

Session::checkRight("phone", READ);

if (empty($_GET["id"])) {
    $_GET["id"] = "";
}
if (!isset($_GET["withtemplate"])) {
    $_GET["withtemplate"] = "";
}

$phone = new Phone();

if (isset($_POST["add"])) {
    $phone->check(-1, CREATE, $_POST);

    if ($newID = $phone->add($_POST)) {
        Event::log(
            $newID,
            "phones",
            4,
            "inventory",
            sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"])
        );
        if ($_SESSION['glpibackcreated']) {
            Html::redirect($phone->getLinkURL());
        }
    }
    Html::back();
} elseif (isset($_POST["delete"])) {
    $phone->check($_POST["id"], DELETE);
    $phone->delete($_POST);

    Event::log(
        $_POST["id"],
        "phones",
        4,
        "inventory",
        //TRANS: %s is the user login
        sprintf(__('%s deletes an item'), $_SESSION["glpiname"])
    );
    $phone->redirectToList();
} elseif (isset($_POST["restore"])) {
    $phone->check($_POST["id"], DELETE);

    $phone->restore($_POST);
    Event::log(
        $_POST["id"],
        "phones",
        4,
        "inventory",
        //TRANS: %s is the user login
        sprintf(__('%s restores an item'), $_SESSION["glpiname"])
    );
    $phone->redirectToList();
} elseif (isset($_POST["purge"])) {
    $phone->check($_POST["id"], PURGE);

    $phone->delete($_POST, 1);
    Event::log(
        $_POST["id"],
        "phones",
        4,
        "inventory",
        //TRANS: %s is the user login
        sprintf(__('%s purges an item'), $_SESSION["glpiname"])
    );
    $phone->redirectToList();
} elseif (isset($_POST["update"])) {
    $phone->check($_POST["id"], UPDATE);

    $phone->update($_POST);
    Event::log(
        $_POST["id"],
        "phones",
        4,
        "inventory",
        //TRANS: %s is the user login
        sprintf(__('%s updates an item'), $_SESSION["glpiname"])
    );
    Html::back();
} elseif (isset($_POST["unglobalize"])) {
    $phone->check($_POST["id"], UPDATE);

    Computer_Item::unglobalizeItem($phone);
    Event::log(
        $_POST["id"],
        "phones",
        4,
        "inventory",
        //TRANS: %s is the user login
        sprintf(__('%s sets unitary management'), $_SESSION["glpiname"])
    );

    Html::redirect($phone->getFormURLWithID($_POST["id"]));
} else {
    $menus = ['assets', 'phone'];
    Phone::displayFullPageForItem($_GET["id"], $menus, [
        'withtemplate' => $_GET["withtemplate"],
        'formoptions'  => "data-track-changes=true",
    ]);
}
