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

use Glpi\Event;
use Glpi\Socket;

include('../inc/includes.php');

Session::checkCentralAccess();

if (!isset($_GET["id"])) {
    $_GET["id"] = "";
}
if (!isset($_GET["items_id"])) {
    $_GET["items_id"] = "";
}
if (!isset($_GET["itemtype"])) {
    $_GET['itemtype'] = '';
}

$socket = new Socket();
if (isset($_POST["add"])) {
    $socket->check(-1, CREATE, $_POST);

    if ($socket->add($_POST)) {
        Event::log(
            $_POST['items_id'],
            $_POST['itemtype'],
            4,
            "socket",
            //TRANS: %s is the user login
            sprintf(__('%s adds a socket'), $_SESSION["glpiname"])
        );
        if ($_SESSION['glpibackcreated']) {
            Html::redirect($socket->getLinkURL());
        }
    }
    Html::back();
} else if (isset($_POST["purge"])) {
    $socket->check($_POST["id"], PURGE);

    if ($socket->delete($_POST, 1)) {
        Event::log(
            $socket->fields['items_id'],
            $socket->fields['itemtype'],
            4,
            "socket",
            //TRANS: %s is the user login
            sprintf(__('%s purges a socket'), $_SESSION["glpiname"])
        );
    }
    $socket->redirectToList();
} else if (isset($_POST["update"])) {
    $socket->check($_POST["id"], UPDATE);

    if ($socket->update($_POST)) {
        Event::log(
            $socket->fields['items_id'],
            $socket->fields['itemtype'],
            4,
            "socket",
            //TRANS: %s is the user login
            sprintf(__('%s updates a socket'), $_SESSION["glpiname"])
        );
    }
    Html::back();
} else if (isset($_POST["execute_multi"])) {
    $socket->check(-1, CREATE, $_POST);

    for ($i = $_POST["_from"]; $i <= $_POST["_to"]; $i++) {
        $_POST["name"] = $_POST["_before"] . $i . $_POST["_after"];
        $socket->add($_POST);
    }
    Event::log(
        0,
        "socket",
        5,
        "setup",
        sprintf(__('%1$s adds several sockets'), $_SESSION["glpiname"])
    );
    Html::back();
} else if (isset($_POST["execute_single"])) {
    $socket->check(-1, CREATE, $_POST);

    if ($socket->add($_POST)) {
        Event::log(
            $_POST['items_id'],
            $_POST['itemtype'],
            4,
            "socket",
            //TRANS: %s is the user login
            sprintf(__('%s adds a socket'), $_SESSION["glpiname"])
        );
        if ($_SESSION['glpibackcreated']) {
            Html::redirect($socket->getLinkURL());
        }
    }
    Html::back();
} else {
    $itemtype = "Computer";
    if ($_GET['id'] != '') {
        $socket->getFromDB($_GET['id']);
    }
    if (!$socket->isNewItem()) {
        $itemtype = $socket->fields['itemtype'];
    } else if ($_GET['itemtype'] != '') {
        $itemtype = $_GET['itemtype'];
    }

    $options = [];
    if (isset($_GET["id"])) {
        $options['id'] = $_GET["id"];
    }

    if (isset($_GET["items_id"])) {
        $options['items_id'] = $_GET["items_id"];
    }

    if (isset($itemtype)) {
        $options['itemtype'] = $itemtype;
    }

   // Add a socket from item : format data
   // see Socket::showNetworkPortForm()
    if (
        isset($_REQUEST['_add_fromitem'])
        && isset($_REQUEST['_from_itemtype'])
        && isset($_REQUEST['_from_items_id'])
    ) {
        $options['_add_fromitem'] = [
            '_from_itemtype' => $_REQUEST['_from_itemtype'],
            '_from_items_id' => $_REQUEST['_from_items_id'],
        ];
    }

    $menus = ["assets", "cable", "socket"];
    Socket::displayFullPageForItem($_GET["id"], $menus, $options);
}
