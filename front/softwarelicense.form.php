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

Session::checkRight("license", READ);
if (!isset($_REQUEST["id"])) {
    $_REQUEST["id"] = "";
}

if (!isset($_REQUEST["softwares_id"])) {
    $_REQUEST["softwares_id"] = "";
}
if (!isset($_REQUEST["withtemplate"])) {
    $_REQUEST["withtemplate"] = "";
}
$license = new SoftwareLicense();

if (isset($_POST["add"])) {
    $license->check(-1, CREATE, $_POST);
    if ($newID = $license->add($_POST)) {
        Event::log(
            $_POST['softwares_id'],
            "software",
            4,
            "inventory",
            //TRANS: %s is the user login, %2$s is the license id
            sprintf(__('%1$s adds the license %2$s'), $_SESSION["glpiname"], $newID)
        );
        if ($_SESSION['glpibackcreated']) {
            Html::redirect($license->getLinkURL());
        }
    }
    Html::back();
} else if (isset($_POST["restore"])) {
    $license->check($_POST['id'], DELETE);
    if ($license->restore($_POST)) {
        Event::log(
            $_POST["id"],
            "software",
            4,
            "inventory",
            //TRANS: %s is the user login
            sprintf(__('%s restores an item'), $_SESSION["glpiname"])
        );
    }
    $license->redirectToList();
} else if (isset($_POST["delete"])) {
    $license->check($_POST['id'], DELETE);
    $license->delete($_POST, 0);
    Event::log(
        $license->fields['softwares_id'],
        "software",
        4,
        "inventory",
        //TRANS: %s is the user login, %2$s is the license id
        sprintf(__('%1$s deletes the license %2$s'), $_SESSION["glpiname"], $_POST["id"])
    );
    $license->redirectToList();
} else if (isset($_POST["purge"])) {
    $license->check($_POST['id'], PURGE);
    $license->delete($_POST, 1);
    Event::log(
        $license->fields['softwares_id'],
        "software",
        4,
        "inventory",
        //TRANS: %s is the user login, %2$s is the license id
        sprintf(__('%1$s purges the license %2$s'), $_SESSION["glpiname"], $_POST["id"])
    );
    $license->redirectToList();
} else if (isset($_POST["update"])) {
    $license->check($_POST['id'], UPDATE);

    $license->update($_POST);
    Event::log(
        $license->fields['softwares_id'],
        "software",
        4,
        "inventory",
        //TRANS: %s is the user login, %2$s is the license id
        sprintf(__('%1$s updates the license %2$s'), $_SESSION["glpiname"], $_POST["id"])
    );
    Html::back();
} else {
    $menus = ["management", "softwarelicense"];
    SoftwareLicense::displayFullPageForItem($_REQUEST['id'], $menus, $_REQUEST);
}
