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

/**
 * @since 9.2
 */

use Glpi\Event;

/** @var array $CFG_GLPI */
global $CFG_GLPI;

include('../inc/includes.php');

Session::checkRight("slm", READ);

if (empty($_GET["id"])) {
    $_GET["id"] = "";
}

$slm = new SLM();

if (isset($_POST["add"])) {
    $slm->check(-1, CREATE);

    if ($newID = $slm->add($_POST)) {
        Event::log(
            $newID,
            "slms",
            4,
            "setup",
            sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"])
        );
        if ($_SESSION['glpibackcreated']) {
            Html::redirect($slm->getLinkURL());
        }
    }
    Html::redirect($CFG_GLPI["root_doc"] . "/front/slm.php");
} else if (isset($_POST["purge"])) {
    $slm->check($_POST["id"], PURGE);
    $slm->delete($_POST, 1);

    Event::log(
        $_POST["id"],
        "slms",
        4,
        "setup",
        //TRANS: %s is the user login
        sprintf(__('%s purges an item'), $_SESSION["glpiname"])
    );
    $slm->redirectToList();
} else if (isset($_POST["update"])) {
    $slm->check($_POST["id"], UPDATE);
    $slm->update($_POST);

    Event::log(
        $_POST["id"],
        "slms",
        4,
        "setup",
        //TRANS: %s is the user login
        sprintf(__('%s updates an item'), $_SESSION["glpiname"])
    );
    Html::back();
} else {
    $menus = ["config", "slm"];
    SLM::displayFullPageForItem($_GET["id"], $menus);
}
