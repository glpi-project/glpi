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

Session::checkRight("domain", READ);

if (empty($_GET["id"])) {
    $_GET["id"] = '';
}
if (!isset($_GET["withtemplate"])) {
    $_GET["withtemplate"] = '';
}

$domain = new Domain();
$ditem  = new Domain_Item();

if (isset($_POST["add"])) {
    $domain->check(-1, CREATE, $_POST);
    $newID = $domain->add($_POST);
    if ($_SESSION['glpibackcreated']) {
        Html::redirect($domain->getLinkURL());
    }
    Html::back();
} elseif (isset($_POST["delete"])) {
    $domain->check($_POST['id'], DELETE);
    $domain->delete($_POST);
    $domain->redirectToList();
} elseif (isset($_POST["restore"])) {
    $domain->check($_POST['id'], PURGE);
    $domain->restore($_POST);
    $domain->redirectToList();
} elseif (isset($_POST["purge"])) {
    $domain->check($_POST['id'], PURGE);
    $domain->delete($_POST, true);
    $domain->redirectToList();
} elseif (isset($_POST["update"])) {
    $domain->check($_POST['id'], UPDATE);
    $domain->update($_POST);
    Html::back();
} elseif (isset($_POST["additem"])) {
    if (!empty($_POST['itemtype']) && $_POST['items_id'] > 0) {
        $ditem->check(-1, UPDATE, $_POST);
        $ditem->addItem($_POST);
    }
    Html::back();
} elseif (isset($_POST["addrecord"])) {
    $record = new DomainRecord();
    $_POST['id'] = $_POST['domainrecords_id'];
    unset($_POST['domainrecords_id']);
    $record->check(-1, UPDATE, $_POST);
    $record->update($_POST);
    Html::redirect($domain->getFormURLWithID($_POST['domains_id']));
} elseif (isset($_POST["deleteitem"])) {
    foreach ($_POST["item"] as $key => $val) {
        $input = ['id' => $key];
        if ($val == 1) {
            $ditem->check($key, UPDATE);
            $ditem->delete($input);
        }
    }
    Html::back();
} elseif (isset($_POST["deletedomains"])) {
    $input = ['id' => $_POST["id"]];
    $ditem->check($_POST["id"], UPDATE);
    $ditem->delete($input);
    Html::back();
} else {
    $menus = ["management", "domain"];
    Domain::displayFullPageForItem($_GET["id"], $menus, [
        'withtemplate' => $_GET["withtemplate"],
        'formoptions'  => "data-track-changes=true",
    ]);
}
