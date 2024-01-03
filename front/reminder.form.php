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

use Glpi\Event;

include('../inc/includes.php');

if (!isset($_GET["id"])) {
    $_GET["id"] = "";
}
$remind = new Reminder();
Session::checkLoginUser();

if (isset($_POST["add"])) {
    $remind->check(-1, CREATE, $_POST);

    if ($newID = $remind->add($_POST)) {
        Event::log(
            $newID,
            "reminder",
            4,
            "tools",
            sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"])
        );
        if ($_SESSION['glpibackcreated']) {
            Html::redirect($remind->getLinkURL());
        }
    }
    Html::back();
} else if (isset($_POST["purge"])) {
    $remind->check($_POST["id"], PURGE);
    $remind->delete($_POST, 1);
    Event::log(
        $_POST["id"],
        "reminder",
        4,
        "tools",
        //TRANS: %s is the user login
        sprintf(__('%s purges an item'), $_SESSION["glpiname"])
    );
    if (!isset($_POST["from_planning_edit_ajax"])) {
        $remind->redirectToList();
    } else {
        Html::back();
    }
} else if (isset($_POST["update"])) {
    $remind->check($_POST["id"], UPDATE);   // Right to update the reminder

    $remind->update($_POST);
    Event::log(
        $_POST["id"],
        "reminder",
        4,
        "tools",
        //TRANS: %s is the user login
        sprintf(__('%s updates an item'), $_SESSION["glpiname"])
    );
    Html::back();
} else if (isset($_POST["addvisibility"])) {
    if (
        isset($_POST["_type"]) && !empty($_POST["_type"])
        && isset($_POST["reminders_id"]) && $_POST["reminders_id"]
    ) {
        if (array_key_exists('entities_id', $_POST) && $_POST['entities_id'] == -1) {
            // "No restriction" value selected
            $_POST['entities_id'] = 'NULL';
            $_POST['no_entity_restriction'] = 1;
        }
        $item = null;
        switch ($_POST["_type"]) {
            case 'User':
                if (isset($_POST['users_id']) && $_POST['users_id']) {
                    $item = new Reminder_User();
                }
                break;

            case 'Group':
                if (isset($_POST['groups_id']) && $_POST['groups_id']) {
                    $item = new Group_Reminder();
                }
                break;

            case 'Profile':
                if (isset($_POST['profiles_id']) && $_POST['profiles_id']) {
                    $item = new Profile_Reminder();
                }
                break;

            case 'Entity':
                $item = new Entity_Reminder();
                break;
        }
        if (!is_null($item)) {
            $item->add($_POST);
            Event::log(
                $_POST["reminders_id"],
                "reminder",
                4,
                "tools",
                //TRANS: %s is the user login
                sprintf(__('%s adds a target'), $_SESSION["glpiname"])
            );
        }
    }
    Html::back();
} else {
    $menus = [
        'central'  => ["tools", "reminder"],
        'helpdesk' => [],
    ];
    Reminder::displayFullPageForItem($_GET["id"], $menus);
}
