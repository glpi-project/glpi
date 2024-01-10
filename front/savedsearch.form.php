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

if (!isset($_GET["id"])) {
    $_GET["id"] = "";
}

if (!isset($_GET["withtemplate"])) {
    $_GET["withtemplate"] = "";
}

$savedsearch = new SavedSearch();
if (isset($_POST["add"])) {
   //Add a new saved search
    $savedsearch->check(-1, CREATE, $_POST);
    if ($savedsearch->add($_POST)) {
        if ($_SESSION['glpibackcreated']) {
            Html::redirect($savedsearch->getLinkURL());
        }
    }
    Html::back();
} else if (isset($_POST["purge"])) {
   // delete a saved search
    $savedsearch->check($_POST['id'], PURGE);
    $savedsearch->delete($_POST, 1);
    $savedsearch->redirectToList();
} else if (isset($_POST["update"])) {
   //update a saved search
    $savedsearch->check($_POST['id'], UPDATE);
    $savedsearch->update($_POST);
    Html::back();
} else if (isset($_GET['create_notif'])) {
    $savedsearch->check($_GET['id'], UPDATE);
    $savedsearch->createNotif();
    Html::back();
} else {
    $menus = [
        'central'  => ['tools', 'savedsearch'],
        'helpdesk' => [],
    ];
    SavedSearch::displayFullPageForItem($_GET["id"], $menus);
}
