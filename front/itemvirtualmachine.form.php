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

if (!isset($_GET["itemtype"])) {
    $_GET["itemtype"] = "";
}

if (!isset($_GET["items_id"])) {
    $_GET["items_id"] = "";
}

$item_vm = new ItemVirtualMachine();
if (isset($_POST["add"])) {
    $item_vm->check(-1, CREATE, $_POST);

    if ($item_vm->add($_POST)) {
        Event::log(
            $_POST['items_id'],
            $_POST['itemtype'],
            4,
            "inventory",
            //TRANS: %s is the user login
            sprintf(__('%s adds a virtual machine'), $_SESSION["glpiname"])
        );
        if ($_SESSION['glpibackcreated']) {
            Html::redirect($item_vm->getLinkURL());
        }
    }
    Html::back();
} elseif (isset($_POST["delete"])) {
    $item_vm->check($_POST["id"], DELETE);
    $item_vm->delete($_POST);

    Event::log(
        $_POST["id"],
        $_POST['itemtype'],
        4,
        "inventory",
        //TRANS: %s is the user login
        sprintf(__('%s deletes an item'), $_SESSION["glpiname"])
    );
    $asset = getItemForItemtype($_POST['itemtype']);
    $asset->getFromDB($item_vm->fields['items_id']);
    Html::redirect($asset->getFormURLWithID($item_vm->fields['items_id'])
                  . ($asset->fields['is_template'] ? "&withtemplate=1" : ""));
} elseif (isset($_POST["purge"])) {
    $item_vm->check($_POST["id"], PURGE);

    if ($item_vm->delete($_POST, true)) {
        Event::log(
            $item_vm->fields['items_id'],
            $item_vm->fields['itemtype'],
            4,
            "inventory",
            //TRANS: %s is the user login
            sprintf(__('%s purges a virtual machine'), $_SESSION["glpiname"])
        );
    }
    $asset = getItemForItemtype($item_vm->fields['itemtype']);
    $asset->getFromDB($item_vm->fields['items_id']);
    Html::redirect($asset->getFormURLWithID($item_vm->fields['items_id'])
                  . ($asset->fields['is_template'] ? "&withtemplate=1" : ""));
} elseif (isset($_POST["update"])) {
    $item_vm->check($_POST["id"], UPDATE);

    if ($item_vm->update($_POST)) {
        Event::log(
            $item_vm->fields['items_id'],
            $item_vm->fields['itemtype'],
            4,
            "inventory",
            //TRANS: %s is the user login
            sprintf(__('%s updates a virtual machine'), $_SESSION["glpiname"])
        );
    }
    Html::back();
} elseif (isset($_POST["restore"])) {
    $item_vm->check($_POST['id'], DELETE);
    if ($item_vm->restore($_POST)) {
        Event::log(
            $_POST["id"],
            $_POST['itemtype'],
            4,
            "inventory",
            //TRANS: %s is the user login
            sprintf(__('%s restores a virtual machine'), $_SESSION["glpiname"])
        );
    }
    Html::back();
} else {
    if ($item_vm->getFromDB($_GET['id'])) {
        $menus = ['assets', $item_vm->fields['itemtype']];
    } else {
        $menus = ['assets', $_GET['itemtype']];
    }

    ItemVirtualMachine::displayFullPageForItem($_GET["id"], $menus, [
        'itemtype' => $_GET['itemtype'],
        'items_id' => $_GET["items_id"],
    ]);
}
