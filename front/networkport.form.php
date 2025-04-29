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

/**
 * @since 0.84
 */

use Glpi\Event;

/** @var array $CFG_GLPI */
global $CFG_GLPI;

include('../inc/includes.php');

Session::checkRight("networking", READ);

$np  = new NetworkPort();
$nn  = new NetworkPort_NetworkPort();

if (!isset($_GET["id"])) {
    $_GET["id"] = "";
}

if (isset($_POST["add"])) {
    // Is a preselected mac adress selected ?
    if (isset($_POST['pre_mac'])) {
        if (!empty($_POST['pre_mac'])) {
            $_POST['mac'] = $_POST['pre_mac'];
        }
        unset($_POST['pre_mac']);
    }

    if (!isset($_POST["several"])) {
        $np->check(-1, UPDATE, $_POST);
        $newID = $np->add($_POST);
        Event::log(
            $newID,
            "networkport",
            5,
            "inventory",
            //TRANS: %s is the user login
            sprintf(__('%s adds an item'), $_SESSION["glpiname"])
        );
        Html::back();
    } else {
        Session::checkRight("networking", UPDATE);

        $input = $_POST;
        unset($input['several']);
        unset($input['from_logical_number']);
        unset($input['to_logical_number']);

        for ($i = $_POST["from_logical_number"]; $i <= $_POST["to_logical_number"]; $i++) {
            $add = "";
            if ($i < 10) {
                $add = "0";
            }
            $input["logical_number"] = $i;
            $input["name"]           = $_POST["name"] . $add . $i;
            unset($np->fields["id"]);

            if ($np->can(-1, CREATE, $input)) {
                $np->add($input);
            }
        }
        Event::log(
            0,
            "networkport",
            5,
            "inventory",
            //TRANS: %s is the user login
            sprintf(__('%s adds several network ports'), $_SESSION["glpiname"])
        );
        Html::back();
    }
} elseif (isset($_POST["purge"])) {
    $np->check($_POST['id'], PURGE);
    $np->delete($_POST, 1);
    Event::log(
        $_POST['id'],
        "networkport",
        5,
        "inventory",
        //TRANS: %s is the user login
        sprintf(__('%s purges an item'), $_SESSION["glpiname"])
    );

    if ($item = getItemForItemtype($np->fields['itemtype'])) {
        Html::redirect($item->getFormURLWithID($np->fields['items_id']));
    }
    Html::redirect($CFG_GLPI["root_doc"] . "/front/central.php");
} elseif (isset($_POST["delete"])) {
    $np->check($_POST['id'], DELETE);
    $np->delete($_POST, 0);
    Event::log(
        $_POST['id'],
        "networkport",
        5,
        "inventory",
        //TRANS: %s is the user login
        sprintf(__('%s deletes an item'), $_SESSION["glpiname"])
    );

    if ($item = getItemForItemtype($np->fields['itemtype'])) {
        Html::redirect($item->getFormURLWithID($np->fields['items_id']));
    }
    Html::redirect($CFG_GLPI["root_doc"] . "/front/central.php");
} elseif (isset($_POST["update"])) {
    $np->check($_POST['id'], UPDATE);

    $np->update($_POST);
    Event::log(
        $_POST["id"],
        "networkport",
        4,
        "inventory",
        //TRANS: %s is the user login
        sprintf(__('%s updates an item'), $_SESSION["glpiname"])
    );
    Html::back();
} elseif (isset($_POST["disconnect"])) {
    $nn->check($_POST['id'], DELETE);

    if (isset($_POST["id"])) {
        $nn->delete($_POST);
    }
    Html::back();
} elseif (isset($_POST["restore"])) {
    $np->check($_POST["id"], DELETE);

    if ($np->restore($_POST)) {
        Event::log(
            $_POST["id"],
            "networkport",
            4,
            "inventory",
            //TRANS: %s is the user login
            sprintf(__('%s restores an item'), $_SESSION["glpiname"])
        );
    }
    Html::back();
} else {
    if (empty($_GET["items_id"])) {
        $_GET["items_id"] = "";
    }
    if (empty($_GET["itemtype"])) {
        $_GET["itemtype"] = "";
    }
    if (empty($_GET["several"])) {
        $_GET["several"] = "";
    }
    if (empty($_GET["instantiation_type"])) {
        $_GET["instantiation_type"] = "";
    }

    $menus[0] = 'assets';
    $menus[1] = 'networkport';
    NetworkPort::displayFullPageForItem($_GET["id"], $menus, $_GET);
}
