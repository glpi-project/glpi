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

/** @var array $CFG_GLPI */
global $CFG_GLPI;

include('../inc/includes.php');

Session::checkRight("contact_enterprise", READ);

if (empty($_GET["id"])) {
    $_GET["id"] = -1;
}

$contact = new Contact();

if (isset($_GET['getvcard'])) {
    if ($_GET["id"] < 0) {
        Html::redirect($CFG_GLPI["root_doc"] . "/front/contact.php");
    }
    $contact->check($_GET["id"], READ);
    $contact->generateVcard();
} elseif (isset($_POST["add"])) {
    $contact->check(-1, CREATE, $_POST);

    if ($newID = $contact->add($_POST)) {
        Event::log(
            $newID,
            "contacts",
            4,
            "financial",
            sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"])
        );
        if ($_SESSION['glpibackcreated']) {
            Html::redirect($contact->getLinkURL());
        }
    }
    Html::back();
} elseif (isset($_POST["delete"])) {
    $contact->check($_POST["id"], DELETE);

    if ($contact->delete($_POST)) {
        Event::log(
            $_POST["id"],
            "contacts",
            4,
            "financial",
            //TRANS: %s is the user login
            sprintf(__('%s deletes an item'), $_SESSION["glpiname"])
        );
    }
    $contact->redirectToList();
} elseif (isset($_POST["restore"])) {
    $contact->check($_POST["id"], DELETE);

    if ($contact->restore($_POST)) {
        Event::log(
            $_POST["id"],
            "contacts",
            4,
            "financial",
            //TRANS: %s is the user login
            sprintf(__('%s restores an item'), $_SESSION["glpiname"])
        );
    }
    $contact->redirectToList();
} elseif (isset($_POST["purge"])) {
    $contact->check($_POST["id"], PURGE);

    if ($contact->delete($_POST, 1)) {
        Event::log(
            $_POST["id"],
            "contacts",
            4,
            "financial",
            //TRANS: %s is the user login
            sprintf(__('%s purges an item'), $_SESSION["glpiname"])
        );
    }
    $contact->redirectToList();
} elseif (isset($_POST["update"])) {
    $contact->check($_POST["id"], UPDATE);

    if ($contact->update($_POST)) {
        Event::log(
            $_POST["id"],
            "contacts",
            4,
            "financial",
            //TRANS: %s is the user login
            sprintf(__('%s updates an item'), $_SESSION["glpiname"])
        );
    }
    Html::back();
} else {
    $menus = ["management", "contact"];
    Contact::displayFullPageForItem($_GET["id"], $menus, [
        'formoptions'  => "data-track-changes=true",
    ]);
}
