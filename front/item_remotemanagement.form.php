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

use Glpi\Event;

include ('../inc/includes.php');

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

$mgmt = new Item_RemoteManagement();
if (isset($_POST["add"])) {
   $mgmt->check(-1, CREATE, $_POST);

   if ($mgmt->add($_POST)) {
      Event::log($_POST['items_id'], $_POST['itemtype'], 4, "inventory",
                 //TRANS: %s is the user login
                 sprintf(__('%s adds a remote management'), $_SESSION["glpiname"]));
      if ($_SESSION['glpibackcreated']) {
         Html::redirect($mgmt->getLinkURL());
      }
   }
   Html::back();

} else if (isset($_POST["purge"])) {
   $mgmt->check($_POST["id"], PURGE);

   if ($mgmt->delete($_POST, 1)) {
      Event::log($mgmt->fields['items_id'], $mgmt->fields['itemtype'], 4, "inventory",
                 //TRANS: %s is the user login
                 sprintf(__('%s purges a remote management'), $_SESSION["glpiname"]));
   }
   $itemtype = $mgmt->fields['itemtype'];
   $item = new $itemtype();
   $item->getFromDB($mgmt->fields['items_id']);
   Html::redirect($itemtype::getFormURLWithID($mgmt->fields['items_id']).
                  ($item->fields['is_template']?"&withtemplate=1":""));

} else if (isset($_POST["update"])) {
   $mgmt->check($_POST["id"], UPDATE);

   if ($mgmt->update($_POST)) {
      Event::log($mgmt->fields['items_id'], $mgmt->fields['itemtype'], 4, "inventory",
                 //TRANS: %s is the user login
         sprintf(__('%s updates a remote management'), $_SESSION["glpiname"]));
   }
   Html::back();

} else {
   $itemtype = "computer";
   if ($_GET['id'] != '') {
      $mgmt->getFromDB($_GET['id']);
   }
   if (!$mgmt->isNewItem()) {
      $itemtype = $mgmt->fields['itemtype'];
   } else if ($_GET['itemtype'] != '') {
      $itemtype = $_GET['itemtype'];
   }
   Html::header(Item_RemoteManagement::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "assets", $itemtype);
   $mgmt->display([
      'id'        => $_GET["id"],
      'items_id'  => $_GET["items_id"],
      'itemtype'  => $_GET['itemtype']
   ]);
   Html::footer();
}

