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

Session::checkCentralAccess();

if (empty($_GET["id"])) {
    $_GET["id"] = '';
}
if (!isset($_GET["withtemplate"])) {
    $_GET["withtemplate"] = '';
}

$record = new DomainRecord();

if (isset($_POST["add"])) {
    $record->check(-1, CREATE, $_POST);
    $newID = $record->add($_POST);
    if ($_SESSION['glpibackcreated'] && !isset($_POST['_in_modal'])) {
        Html::redirect($record->getFormURLWithID($newID));
    }
    Html::back();
} elseif (isset($_POST["delete"])) {
    $record->check($_POST['id'], DELETE);
    $record->delete($_POST);
    $record->redirectToList();
} elseif (isset($_POST["restore"])) {
    $record->check($_POST['id'], PURGE);
    $record->restore($_POST);
    $record->redirectToList();
} elseif (isset($_POST["purge"])) {
    $record->check($_POST['id'], PURGE);
    $record->delete($_POST, true);
    $record->redirectToList();
} elseif (isset($_POST["update"])) {
    $record->check($_POST['id'], UPDATE);
    $record->update($_POST);
    Html::back();
} elseif (isset($_GET['_in_modal'])) {
    Html::popHeader(DomainRecord::getTypeName(Session::getPluralNumber()), in_modal: true);
    $record->showForm($_GET["id"], ['domains_id' => $_GET['domains_id'] ?? null]);
    Html::popFooter();
} else {
    $menus = ["management", "domain", "DomainRecord"];
    $options = [
        'withtemplate' => $_GET["withtemplate"],
    ];
    if (isset($_GET['domains_id'])) {
        $options['domains_id'] = $_GET['domains_id'];
    }
    DomainRecord::displayFullPageForItem($_GET["id"], $menus, $options);
}
