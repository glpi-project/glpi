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

Session::checkCentralAccess();

if (!isset($_GET["id"])) {
    $_GET["id"] = "";
}

$notificationtemplate = new NotificationTemplate();
if (isset($_POST["add"])) {
    $notificationtemplate->check(-1, CREATE, $_POST);

    $newID = $notificationtemplate->add($_POST);
    Event::log(
        $newID,
        "notificationtemplates",
        4,
        "notification",
        sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"])
    );

    $url      = Toolbox::getItemTypeFormURL('NotificationTemplateTranslation', true);
    $url     .= "?notificationtemplates_id=$newID";
    Html::redirect($url);
} elseif (isset($_POST["purge"])) {
    $notificationtemplate->check($_POST["id"], PURGE);
    $notificationtemplate->delete($_POST, true);

    Event::log(
        $_POST["id"],
        "notificationtemplates",
        4,
        "notification",
        //TRANS: %s is the user login
        sprintf(__('%s purges an item'), $_SESSION["glpiname"])
    );
    $notificationtemplate->redirectToList();
} elseif (isset($_POST["update"])) {
    $notificationtemplate->check($_POST["id"], UPDATE);

    $notificationtemplate->update($_POST);
    Event::log(
        $_POST["id"],
        "notificationtemplates",
        4,
        "notification",
        //TRANS: %s is the user login
        sprintf(__('%s updates an item'), $_SESSION["glpiname"])
    );
    Html::back();
} else {
    $menus = ["config", "notification", "NotificationTemplate"];
    NotificationTemplate::displayFullPageForItem($_GET["id"], $menus);
}
