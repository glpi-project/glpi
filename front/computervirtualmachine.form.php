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

Session::checkCentralAccess();

if (!isset($_GET["id"])) {
    $_GET["id"] = "";
}

if (!isset($_GET["computers_id"])) {
    $_GET["computers_id"] = "";
}

$computer_vm = new ComputerVirtualMachine();
if (isset($_POST["add"])) {
    $computer_vm->check(-1, CREATE, $_POST);

    if ($computer_vm->add($_POST)) {
        Event::log(
            $_POST['computers_id'],
            "computers",
            4,
            "inventory",
            //TRANS: %s is the user login
            sprintf(__('%s adds a virtual machine'), $_SESSION["glpiname"])
        );
        if ($_SESSION['glpibackcreated']) {
            Html::redirect($computer_vm->getLinkURL());
        }
    }
    Html::back();
} else if (isset($_POST["delete"])) {
    $computer_vm->check($_POST["id"], DELETE);
    $computer_vm->delete($_POST);

    Event::log(
        $_POST["id"],
        "computers",
        4,
        "inventory",
        //TRANS: %s is the user login
        sprintf(__('%s deletes an item'), $_SESSION["glpiname"])
    );
    $computer = new Computer();
    $computer->getFromDB($computer_vm->fields['computers_id']);
    Html::redirect(Toolbox::getItemTypeFormURL('Computer') . '?id=' . $computer_vm->fields['computers_id'] .
                  ($computer->fields['is_template'] ? "&withtemplate=1" : ""));
} else if (isset($_POST["purge"])) {
    $computer_vm->check($_POST["id"], PURGE);

    if ($computer_vm->delete($_POST, 1)) {
        Event::log(
            $computer_vm->fields['computers_id'],
            "computers",
            4,
            "inventory",
            //TRANS: %s is the user login
            sprintf(__('%s purges a virtual machine'), $_SESSION["glpiname"])
        );
    }
    $computer = new Computer();
    $computer->getFromDB($computer_vm->fields['computers_id']);
    Html::redirect(Toolbox::getItemTypeFormURL('Computer') . '?id=' . $computer_vm->fields['computers_id'] .
                  ($computer->fields['is_template'] ? "&withtemplate=1" : ""));
} else if (isset($_POST["update"])) {
    $computer_vm->check($_POST["id"], UPDATE);

    if ($computer_vm->update($_POST)) {
        Event::log(
            $computer_vm->fields['computers_id'],
            "computers",
            4,
            "inventory",
            //TRANS: %s is the user login
            sprintf(__('%s updates a virtual machine'), $_SESSION["glpiname"])
        );
    }
    Html::back();
} else if (isset($_POST["restore"])) {
    $computer_vm->check($_POST['id'], DELETE);
    if ($computer_vm->restore($_POST)) {
        Event::log(
            $_POST["id"],
            "computers",
            4,
            "inventory",
            //TRANS: %s is the user login
            sprintf(__('%s restores a virtual machine'), $_SESSION["glpiname"])
        );
    }
    Html::back();
} else {
    $menus = ["assets", "computer"];
    ComputerVirtualMachine::displayFullPageForItem($_GET["id"], $menus, [
        'computers_id' => $_GET["computers_id"]
    ]);
}
