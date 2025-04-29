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

/** @var array $_UPOST */
global $_UPOST;

include('../inc/includes.php');

Session::checkRight("config", READ);

if (!isset($_GET["id"])) {
    $_GET["id"] = "";
}

$mailgate = new MailCollector();

if (isset($_POST["add"])) {
    $mailgate->check(-1, CREATE, $_POST);

    if (array_key_exists('passwd', $_POST)) {
        // Password must not be altered, it will be encrypted and never displayed, so sanitize is not necessary.
        $_POST['passwd'] = $_UPOST['passwd'];
    }

    if ($newID = $mailgate->add($_POST)) {
        Event::log(
            $newID,
            "mailcollector",
            4,
            "setup",
            sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"])
        );
        if ($_SESSION['glpibackcreated']) {
            Html::redirect($mailgate->getLinkURL());
        }
    }
    Html::back();
} elseif (isset($_POST["purge"])) {
    $mailgate->check($_POST['id'], PURGE);
    $mailgate->delete($_POST, 1);

    Event::log(
        $_POST["id"],
        "mailcollector",
        4,
        "setup",
        //TRANS: %s is the user login
        sprintf(__('%s purges an item'), $_SESSION["glpiname"])
    );
    $mailgate->redirectToList();
} elseif (isset($_POST["update"])) {
    $mailgate->check($_POST['id'], UPDATE);

    if (array_key_exists('passwd', $_POST)) {
        // Password must not be altered, it will be encrypted and never displayed, so sanitize is not necessary.
        $_POST['passwd'] = $_UPOST['passwd'];
    }

    $mailgate->update($_POST);

    Event::log(
        $_POST["id"],
        "mailcollector",
        4,
        "setup",
        //TRANS: %s is the user login
        sprintf(__('%s updates an item'), $_SESSION["glpiname"])
    );
    Html::back();
} elseif (isset($_POST["get_mails"])) {
    $mailgate->check($_POST['id'], UPDATE);
    $mailgate->collect($_POST["id"], 1);

    Html::back();
} else {
    $menus = ["config", "mailcollector"];
    MailCollector::displayFullPageForItem($_GET["id"], $menus);
}
