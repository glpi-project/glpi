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
if (!isset($_GET["savedsearches_id"])) {
    $_GET["savedsearches_id"] = "";
}

$alert = new SavedSearch_Alert();
if (isset($_POST["add"])) {
    $alert->check(-1, CREATE, $_POST);

    if ($alert->add($_POST)) {
        Event::log(
            $_POST['savedsearches_id'],
            "savedsearches",
            4,
            "inventory",
            //TRANS: %s is the user login
            sprintf(__('%s adds an alert'), $_SESSION["glpiname"])
        );
        if ($_SESSION['glpibackcreated']) {
            Html::redirect($alert->getLinkURL());
        }
    }
    Html::back();
} elseif (isset($_POST["purge"])) {
    $alert->check($_POST["id"], PURGE);

    if ($alert->delete($_POST, true)) {
        Event::log(
            $alert->fields['savedsearches_id'],
            "savedsearches",
            4,
            "inventory",
            //TRANS: %s is the user login
            sprintf(__('%s purges an alert'), $_SESSION["glpiname"])
        );
    }
    $search = new SavedSearch();
    $search->getFromDB($alert->fields['savedsearches_id']);
    Html::redirect(Toolbox::getItemTypeFormURL('SavedSearch') . '?id=' . $alert->fields['savedsearches_id']);
} elseif (isset($_POST["update"])) {
    $alert->check($_POST["id"], UPDATE);

    if ($alert->update($_POST)) {
        Event::log(
            $alert->fields['savedsearches_id'],
            "savedsearches",
            4,
            "inventory",
            //TRANS: %s is the user login
            sprintf(__('%s updates an alert'), $_SESSION["glpiname"])
        );
    }
    Html::back();
} else {
    $menu = ["tools", "savedsearch"];
    SavedSearch_Alert::displayFullPageForItem($_GET["id"], $menu, [
        'savedsearches_id' => $_GET["savedsearches_id"],
    ]);
}
