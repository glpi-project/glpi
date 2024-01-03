<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

include('../inc/includes.php');

Session::checkCentralAccess();

//Html::back();
//
if (!isset($_GET["id"])) {
    $_GET["id"] = "";
}

$notiftpl = new Notification_NotificationTemplate();
if (isset($_POST["add"])) {
    $notiftpl->check(-1, CREATE, $_POST);

    if ($notiftpl->add($_POST)) {
        if ($_SESSION['glpibackcreated']) {
            Html::redirect($notiftpl->getLinkURL());
        }
    }
    Html::back();
} else if (isset($_POST["purge"])) {
    $notiftpl->check($_POST["id"], PURGE);
    $notiftpl->delete($_POST, 1);
    Html::redirect(Notification::getFormURLWithID($notiftpl->fields['notifications_id']));
} else if (isset($_POST["update"])) {
    $notiftpl->check($_POST["id"], UPDATE);

    $notiftpl->update($_POST);
    Html::back();
} else {
    $params = [];
    if (isset($_GET['notifications_id'])) {
        $params['notifications_id'] = $_GET['notifications_id'];
    }

    $menus = ["config", "notification", "notifications_notificationtemplates"];
    Notification_NotificationTemplate::displayFullPageForItem(
        $_GET['id'],
        $menus,
        $params
    );
}
