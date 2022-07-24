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

Session::checkRight("config", UPDATE);

if (!isset($_GET["id"])) {
    $_GET["id"] = "";
}

$config_mail = new AuthMail();

//IMAP/POP Server add/update/delete
if (isset($_POST["update"])) {
    $config_mail->update($_POST);
    Html::back();
} else if (isset($_POST["add"])) {
   //If no name has been given to this configuration, then go back to the page without adding
    if ($_POST["name"] != "") {
        if (
            ($config_mail->add($_POST))
            && $_SESSION['glpibackcreated']
        ) {
            Html::redirect($config_mail->getLinkURL());
        }
    }
    Html::back();
} else if (isset($_POST["purge"])) {
    $config_mail->delete($_POST, 1);
    $_SESSION['glpi_authconfig'] = 2;
    $config_mail->redirectToList();
} else if (isset($_POST["test"])) {
    if (AuthMail::testAuth($_POST["imap_string"], $_POST["imap_login"], $_POST["imap_password"])) {
        Session::addMessageAfterRedirect(__('Test successful'));
    } else {
        Session::addMessageAfterRedirect(__('Test failed'), false, ERROR);
    }
    Html::back();
}

$menus = ["config", "auth", "imap"];
AuthMail::displayFullPageForItem($_GET['id'], $menus);
