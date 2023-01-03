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

Session::checkRight("locked_field", CREATE);

if (!isset($_GET["id"])) {
    $_GET["id"] = "";
}

$lockedfield = new Lockedfield();

//Add a new locked field
if (isset($_POST["add"])) {
    $lockedfield->check(-1, UPDATE, $_POST);
    if ($newID = $lockedfield->add($_POST)) {
        Event::log(
            $newID,
            "lockedfield",
            4,
            "inventory",
            sprintf(__('%1$s adds global lock on %2$s'), $_SESSION["glpiname"], $_POST["item"])
        );

        if ($_SESSION['glpibackcreated']) {
            Html::redirect($lockedfield->getLinkURL());
        }
    }
    Html::back();
} else if (isset($_POST["purge"])) {
    $lockedfield->check($_POST['id'], UPDATE);
    if ($lockedfield->delete($_POST, 1)) {
        Event::log(
            $_POST["id"],
            "lockedfield",
            4,
            "inventory",
            //TRANS: %s is the user login
            sprintf(__('%s purges an item'), $_SESSION["glpiname"])
        );
    }
    $lockedfield->redirectToList();

   //update a locked field
} else if (isset($_POST["update"])) {
    $lockedfield->check($_POST['id'], UPDATE);
    $lockedfield->update($_POST);
    Event::log(
        $_POST["id"],
        "lockedfield",
        4,
        "inventory",
        //TRANS: %s is the user login
        sprintf(__('%s updates an item'), $_SESSION["glpiname"])
    );
    Html::back();
} else {//print locked field information
    $menus = ["admin", "glpi\inventory\inventory", "lockedfield"];
    $lockedfield->displayFullPageForItem($_GET['id'], $menus, [
        'formoptions'  => "data-track-changes=true"
    ]);
}
