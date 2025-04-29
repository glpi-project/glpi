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

use Glpi\Event;

include('../inc/includes.php');

$ic = new Infocom();

if (isset($_POST['add'])) {
    $ic->check(-1, CREATE, $_POST);

    $newID = $ic->add($_POST, false);
    Event::log(
        $newID,
        "infocom",
        4,
        "financial",
        sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $newID)
    );
    Html::back();
} elseif (isset($_POST["purge"])) {
    $ic->check($_POST["id"], PURGE);
    $ic->delete($_POST, 1);
    Event::log(
        $_POST["id"],
        "infocom",
        4,
        "financial",
        //TRANS: %s is the user login
        sprintf(__('%s purges an item'), $_SESSION["glpiname"])
    );
    Html::back();
} elseif (isset($_POST["update"])) {
    $ic->check($_POST["id"], UPDATE);

    $ic->update($_POST);
    Event::log(
        $_POST["id"],
        "infocom",
        4,
        "financial",
        //TRANS: %s is the user login
        sprintf(__('%s updates an item'), $_SESSION["glpiname"])
    );
    Html::back();
} else {
    Session::checkRight("infocom", READ);

    Html::popHeader(Infocom::getTypeName(), $_SERVER['PHP_SELF']);

    if (isset($_GET["id"])) {
        $ic->getFromDB($_GET["id"]);
        $_GET["itemtype"] = $ic->fields["itemtype"];
        $_GET["items_id"] = $ic->fields["items_id"];
    }
    $item = false;
    if (isset($_GET["itemtype"]) && ($item = getItemForItemtype($_GET["itemtype"]))) {
        if (!isset($_GET["items_id"]) || !$item->getFromDB($_GET["items_id"])) {
            $item = false;
        }
    }

    Infocom::showForItem($item, 0);

    Html::popFooter();
}
