<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

use Glpi\Event;

include('../inc/includes.php');

Session::checkRight("config", READ);

if (!isset($_GET["id"])) {
    $_GET["id"] = "";
}

if (!isset($_GET["withtemplate"])) {
    $_GET["withtemplate"] = "";
}

$agent = new Agent();
// delete an agent
if (isset($_POST["delete"])) {
    $agent->check($_POST['id'], DELETE);
    $ok = $agent->delete($_POST);
    if ($ok) {
        Event::log(
            $_POST["id"],
            "agents",
            4,
            "inventory",
            //TRANS: %s is the user login
            sprintf(__('%s deletes an item'), $_SESSION["glpiname"])
        );
    }
    $agent->redirectToList();
} else if (isset($_POST["restore"])) {
    $agent->check($_POST['id'], DELETE);
    if ($agent->restore($_POST)) {
        Event::log(
            $_POST["id"],
            "agents",
            4,
            "inventory",
            //TRANS: %s is the user login
            sprintf(__('%s restores an item'), $_SESSION["glpiname"])
        );
    }
    $agent->redirectToList();
} else if (isset($_POST["purge"])) {
    $agent->check($_POST['id'], PURGE);
    if ($agent->delete($_POST, 1)) {
        Event::log(
            $_POST["id"],
            "agents",
            4,
            "inventory",
            //TRANS: %s is the user login
            sprintf(__('%s purges an item'), $_SESSION["glpiname"])
        );
    }
    $agent->redirectToList();

   //update an agent
} else if (isset($_POST["update"])) {
    $agent->check($_POST['id'], UPDATE);
    $agent->update($_POST);
    Event::log(
        $_POST["id"],
        "agents",
        4,
        "inventory",
        //TRANS: %s is the user login
        sprintf(__('%s updates an item'), $_SESSION["glpiname"])
    );
    Html::back();
} else {//print agent information
    $menus = ["admin", "glpi\inventory\inventory", "agent"];
    Agent::displayFullPageForItem((int) $_GET['id'], $menus, [
        'withtemplate' => $_GET["withtemplate"],
        'formoptions'  => "data-track-changes=true",
    ]);
}
