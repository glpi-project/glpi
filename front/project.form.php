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

/**
 * @since 0.85
 */

use Glpi\Event;

include('../inc/includes.php');

if (empty($_GET["id"])) {
    $_GET["id"] = '';
}
if (!isset($_GET["withtemplate"])) {
    $_GET["withtemplate"] = '';
}

Session::checkLoginUser();

$project = new Project();
if (isset($_POST["add"])) {
    $project->check(-1, CREATE, $_POST);

    $newID = $project->add($_POST);
    Event::log(
        $newID,
        "project",
        4,
        "maintain",
        //TRANS: %1$s is the user login, %2$s is the name of the item
        sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"])
    );
    if ($_SESSION['glpibackcreated']) {
        Html::redirect($project->getLinkURL());
    } else {
        Html::back();
    }
} else if (isset($_POST["delete"])) {
    $project->check($_POST["id"], DELETE);

    $project->delete($_POST);
    Event::log(
        $_POST["id"],
        "project",
        4,
        "maintain",
        //TRANS: %s is the user login
        sprintf(__('%s deletes an item'), $_SESSION["glpiname"])
    );
    $project->redirectToList();
} else if (isset($_POST["restore"])) {
    $project->check($_POST["id"], DELETE);

    $project->restore($_POST);
    Event::log(
        $_POST["id"],
        "project",
        4,
        "maintain",
        //TRANS: %s is the user login
        sprintf(__('%s restores an item'), $_SESSION["glpiname"])
    );
    $project->redirectToList();
} else if (isset($_POST["purge"])) {
    $project->check($_POST["id"], PURGE);
    $project->delete($_POST, 1);

    Event::log(
        $_POST["id"],
        "project",
        4,
        "maintain",
        //TRANS: %s is the user login
        sprintf(__('%s purges an item'), $_SESSION["glpiname"])
    );
    $project->redirectToList();
} else if (isset($_POST["update"])) {
    $project->check($_POST["id"], UPDATE);

    $project->update($_POST);
    Event::log(
        $_POST["id"],
        "project",
        4,
        "maintain",
        //TRANS: %s is the user login
        sprintf(__('%s updates an item'), $_SESSION["glpiname"])
    );

    Html::back();
} else if (isset($_GET['_in_modal'])) {
    Html::popHeader(Budget::getTypeName(1), $_SERVER['PHP_SELF'], true);
    $project->showForm($_GET["id"], ['withtemplate' => $_GET["withtemplate"]]);
    Html::popFooter();
} else {
    if (isset($_GET['showglobalkanban']) && $_GET['showglobalkanban']) {
        Html::header(Project::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "tools", "project");
        $project->showKanban(0);
        Html::footer();
    } else {
        $menus = ["tools", "project"];
        Project::displayFullPageForItem($_GET["id"], $menus, [
            'withtemplate' => $_GET["withtemplate"],
            'formoptions'  => "data-track-changes=true"
        ]);
    }
}
