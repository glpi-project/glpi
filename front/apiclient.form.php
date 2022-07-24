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

include("../inc/includes.php");

if (!isset($_GET["id"])) {
    $_GET["id"] = "";
}
$client = new APIClient();

if (isset($_POST["add"])) {
    $client->check(-1, CREATE, $_POST);
    $client->add($_POST);
    Html::back();
} else if (isset($_POST["update"])) {
    $client->check($_POST["id"], UPDATE);
    $client->update($_POST);
    Html::back();
} else if (isset($_POST["purge"])) {
    $client->check($_POST["id"], PURGE);
    $client->delete($_POST);
    Html::redirect($CFG_GLPI["root_doc"] . "/front/config.form.php");
} else {
    $menus = ["config", "config", "apiclient"];
    APIClient::displayFullPageForItem($_GET["id"], $menus);
}
