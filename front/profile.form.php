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

include('../inc/includes.php');

Session::checkRight("profile", READ);

if (!isset($_GET['id'])) {
    $_GET['id'] = "";
}

$prof = new Profile();

if (isset($_POST["add"])) {
    $prof->check(-1, CREATE, $_POST);
    $ID = $prof->add($_POST);

   // We need to redirect to form to enter rights
    Html::redirect($prof->getFormURLWithID($ID));
} else if (isset($_POST["purge"])) {
    $prof->check($_POST['id'], PURGE);
    if ($prof->delete($_POST, 1)) {
        $prof->redirectToList();
    } else {
        Html::back();
    }
} else if (
    isset($_POST["update"])
           || isset($_POST["interface"])
) {
    $prof->check($_POST['id'], UPDATE);

    $prof->update($_POST);
    Html::back();
}

$menus = ["admin", "profile"];
Profile::displayFullPageForItem($_GET["id"], $menus, [
    'formoptions'  => " data-track-changes='true'"
]);
