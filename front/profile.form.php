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

Session::checkRight("profile", READ);

if (!isset($_GET['id'])) {
    $_GET['id'] = "";
}

$prof = new Profile();

if (isset($_POST["add"])) {
    $prof->check(-1, CREATE, $_POST);
    if ($newID = $prof->add($_POST)) {
        Event::log(
            $newID,
            "profiles",
            4,
            "setup",
            //TRANS: %1$s is the user login, %2$s is the name of the item
            sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"])
        );
        if ($_SESSION['glpibackcreated']) {
            Html::redirect($prof->getLinkURL());
        }
    }
    Html::back();
} elseif (isset($_POST["purge"])) {
    $prof->check($_POST['id'], PURGE);
    if ($prof->delete($_POST, true)) {
        Event::log(
            $_POST['id'],
            "profiles",
            4,
            "setup",
            //TRANS: %s is the user login
            sprintf(__('%s purges an item'), $_SESSION["glpiname"])
        );
        $prof->redirectToList();
    } else {
        Html::back();
    }
} elseif (
    isset($_POST["update"])
           || isset($_POST["interface"])
) {
    $prof->check($_POST['id'], UPDATE);

    $prof->update($_POST);
    Event::log(
        $_POST['id'],
        "profiles",
        4,
        "setup",
        //TRANS: %s is the user login
        sprintf(__('%s updates an item'), $_SESSION["glpiname"])
    );
    Html::back();
}

$menus = ["admin", "profile"];
Profile::displayFullPageForItem($_GET["id"], $menus, [
    'formoptions'  => " data-track-changes='true'",
]);
