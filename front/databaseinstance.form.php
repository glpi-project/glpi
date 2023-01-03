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

Session::checkRight('database', READ);

if (!isset($_GET["id"])) {
    $_GET["id"] = "";
}
if (!isset($_GET["withtemplate"])) {
    $_GET["withtemplate"] = "";
}

$instance = new DatabaseInstance();
if (isset($_POST["add"])) {
    $instance->check(-1, CREATE, $_POST);

    if ($newID = $instance->add($_POST)) {
        Event::log(
            $newID,
            "databaseinstance",
            4,
            "management",
            //TRANS: %s is the user login
            sprintf(__('%s adds a database instance'), $_SESSION["glpiname"])
        );
        if ($_SESSION['glpibackcreated']) {
            Html::redirect($instance->getLinkURL());
        }
    }
    Html::back();
} else if (isset($_POST["delete"])) {
    $instance->check($_POST['id'], DELETE);
    $ok = $instance->delete($_POST);
    if ($ok) {
        Event::log(
            $_POST["id"],
            "databaseinstance",
            4,
            "management",
            //TRANS: %s is the user login
            sprintf(__('%s deletes a database instance'), $_SESSION["glpiname"])
        );
    }
    $instance->redirectToList();
} else if (isset($_POST["restore"])) {
    $instance->check($_POST['id'], DELETE);
    if ($instance->restore($_POST)) {
        Event::log(
            $_POST["id"],
            "databaseinstance",
            4,
            "management",
            //TRANS: %s is the user login
            sprintf(__('%s restores a database instance'), $_SESSION["glpiname"])
        );
    }
    $instance->redirectToList();
} else if (isset($_POST["purge"])) {
    $instance->check($_POST["id"], PURGE);

    if ($instance->delete($_POST, 1)) {
        Event::log(
            $_POST['id'],
            "databaseinstance",
            4,
            "management",
            //TRANS: %s is the user login
            sprintf(__('%s purges a database instance'), $_SESSION["glpiname"])
        );
    }
    $instance->redirectToList();
} else if (isset($_POST["update"])) {
    $instance->check($_POST["id"], UPDATE);

    if ($instance->update($_POST)) {
        Event::log(
            $_POST['id'],
            "databaseinstance",
            4,
            "management",
            //TRANS: %s is the user login
            sprintf(__('%s updates a database instance'), $_SESSION["glpiname"])
        );
    }
    Html::back();
} else {
    $menus = ["database", "databaseinstance"];
    DatabaseInstance::displayFullPageForItem($_GET['id'], $menus, [
        'withtemplate' => $_GET['withtemplate']
    ]);
}
