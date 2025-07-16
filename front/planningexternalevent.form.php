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

use function Safe\strtotime;

Session::checkRight("planning", READ);

if (empty($_GET["id"])) {
    $_GET["id"] = "";
}

$extevent = new PlanningExternalEvent();

if (isset($_POST["add"])) {
    $extevent->check(-1, CREATE, $_POST);

    if ($newID = $extevent->add($_POST)) {
        if ($_SESSION['glpibackcreated']) {
            Html::redirect($extevent->getLinkURL());
        }
    }
    Html::back();
} elseif (isset($_POST["delete"])) {
    $extevent->check($_POST["id"], DELETE);
    $extevent->delete($_POST);
    $extevent->redirectToList();
} elseif (isset($_POST["restore"])) {
    $extevent->check($_POST["id"], DELETE);
    $extevent->restore($_POST);
    $extevent->redirectToList();
} elseif (isset($_POST["purge"])) {
    $extevent->check($_POST["id"], PURGE);
    $extevent->delete($_POST, true);
    $extevent->redirectToList();
} elseif (isset($_POST["purge_instance"])) {
    $extevent->check($_POST["id"], PURGE);
    $extevent->deleteInstance((int) $_POST["id"], $_POST['day']);
    $extevent->redirectToList();
} elseif (isset($_POST["save_instance"])) {
    $input = $_POST;
    unset($input['id']);
    unset($input['rrule']);
    $input['plan']['begin'] = $_POST['day'] . date(" H:i:s", strtotime($_POST['plan']['begin']));
    $extevent->check(-1, CREATE, $input);
    $extevent->add($input);
    $extevent->deleteInstance((int) $_POST["id"], $_POST['day']);
    $extevent->redirectToList();
} elseif (isset($_POST["update"])) {
    $extevent->check($_POST["id"], UPDATE);
    $extevent->update($_POST);
    Html::back();
} else {
    $menus = ["helpdesk", "planning", "PlanningExternalEvent"];
    PlanningExternalEvent::displayFullPageForItem($_GET["id"], $menus);
}
