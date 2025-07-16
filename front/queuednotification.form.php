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

/**
 * @since 0.85
 */

use Glpi\Event;

Session::checkRight('queuednotification', READ);

if (!isset($_GET["id"])) {
    $_GET["id"] = "";
}

$queuednotification = new QueuedNotification();

if (isset($_POST["delete"])) {
    $queuednotification->check($_POST["id"], DELETE);
    $queuednotification->delete($_POST);

    Event::log(
        $_POST["id"],
        QueuedNotification::class,
        4,
        "notification",
        //TRANS: %s is the user login
        sprintf(__('%s deletes an item'), $_SESSION["glpiname"])
    );
    $queuednotification->redirectToList();
} elseif (isset($_POST["restore"])) {
    $queuednotification->check($_POST["id"], DELETE);
    $queuednotification->restore($_POST);

    Event::log(
        $_POST["id"],
        QueuedNotification::class,
        4,
        "notification",
        //TRANS: %s is the user login
        sprintf(__('%s restores an item'), $_SESSION["glpiname"])
    );
    $queuednotification->redirectToList();
} elseif (isset($_POST["purge"])) {
    $queuednotification->check($_POST["id"], PURGE);
    $queuednotification->delete($_POST, true);

    Event::log(
        $_POST["id"],
        QueuedNotification::class,
        4,
        "notification",
        //TRANS: %s is the user login
        sprintf(__('%s purges an item'), $_SESSION["glpiname"])
    );
    $queuednotification->redirectToList();
} else {
    $menus = ["admin", "queuednotification"];
    QueuedNotification::displayFullPageForItem($_GET["id"], $menus, $_GET);
}
