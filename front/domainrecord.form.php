<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

include ('../inc/includes.php');

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
} else if (isset($_POST["delete"])) {
   $record->check($_POST['id'], DELETE);
   $record->delete($_POST);
   $record->redirectToList();

} else if (isset($_POST["restore"])) {
   $record->check($_POST['id'], PURGE);
   $record->restore($_POST);
   $record->redirectToList();

} else if (isset($_POST["purge"])) {
   $record->check($_POST['id'], PURGE);
   $record->delete($_POST, 1);
   $record->redirectToList();

} else if (isset($_POST["update"])) {
   $record->check($_POST['id'], UPDATE);
   $record->update($_POST);
   Html::back();
} else if (isset($_GET['_in_modal'])) {
   Html::popHeader(DomainRecord::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF']);
   $record->showForm($_GET["id"], ['domains_id' => $_GET['domains_id'] ?? null]);
   Html::popFooter();

} else {
   Html::header(DomainRecord::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "management", "domain", "domainrecord");
   $record->display([
      'id'           => $_GET["id"],
      'domains_id'   => $_GET['domains_id'] ?? null,
      'withtemplate' => $_GET["withtemplate"]
   ]);

   Html::footer();
}
