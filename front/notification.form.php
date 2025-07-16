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

Session::checkRight("notification", READ);

if (!isset($_GET["id"])) {
    $_GET["id"] = "";
}

$notification = new Notification();
if (isset($_POST["add"])) {
    $notification->check(-1, CREATE, $_POST);

    if ($newID = $notification->add($_POST)) {
        Event::log(
            $newID,
            "notifications",
            4,
            "notification",
            sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"])
        );
        if ($_SESSION['glpibackcreated']) {
            Html::redirect($notification->getLinkURL());
        }
    }
    Html::back();
} elseif (isset($_POST["purge"])) {
    $notification->check($_POST["id"], PURGE);
    $notification->delete($_POST, true);

    Event::log(
        $_POST["id"],
        "notifications",
        4,
        "notification",
        //TRANS: %s is the user login
        sprintf(__('%s purges an item'), $_SESSION["glpiname"])
    );
    $notification->redirectToList();
} elseif (isset($_POST["update"])) {
    $notification->check($_POST["id"], UPDATE);

    $notification->update($_POST);
    Event::log(
        $_POST["id"],
        "notifications",
        4,
        "notification",
        //TRANS: %s is the user login
        sprintf(__('%s updates an item'), $_SESSION["glpiname"])
    );
    Html::back();
} else {
    $menus = ["config", "notification", "Notification"];
    Notification::displayFullPageForItem($_GET["id"], $menus);
}
