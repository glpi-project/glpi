<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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

Session::checkRight("datacenter", READ);

if (empty($_GET["id"])) {
   $_GET["id"] = "";
}
if (!isset($_GET["withtemplate"])) {
   $_GET["withtemplate"] = "";
}

$rack = new Rack();

if (isset($_POST["add"])) {
   $rack->check(-1, CREATE, $_POST);

   if ($newID = $rack->add($_POST)) {
      Event::log($newID, "racks", 4, "inventory",
                 sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"]));
      if ($_SESSION['glpibackcreated']) {
         Html::redirect($rack->getLinkURL());
      }
   }
   Html::back();

} else if (isset($_POST["delete"])) {
   $rack->check($_POST["id"], DELETE);
   $rack->delete($_POST);

   Event::log($_POST["id"], "racks", 4, "inventory",
              //TRANS: %s is the user login
              sprintf(__('%s deletes an item'), $_SESSION["glpiname"]));
   $rack->redirectToList();

} else if (isset($_POST["restore"])) {
   $rack->check($_POST["id"], DELETE);

   $rack->restore($_POST);
   Event::log($_POST["id"], "racks", 4, "inventory",
              //TRANS: %s is the user login
              sprintf(__('%s restores an item'), $_SESSION["glpiname"]));
   $rack->redirectToList();

} else if (isset($_POST["purge"])) {
   $rack->check($_POST["id"], PURGE);

   $rack->delete($_POST, 1);
   Event::log($_POST["id"], "racks", 4, "inventory",
              //TRANS: %s is the user login
              sprintf(__('%s purges an item'), $_SESSION["glpiname"]));
   $rack->redirectToList();

} else if (isset($_POST["update"])) {
   $rack->check($_POST["id"], UPDATE);

   $rack->update($_POST);
   Event::log($_POST["id"], "racks", 4, "inventory",
              //TRANS: %s is the user login
              sprintf(__('%s updates an item'), $_SESSION["glpiname"]));
   Html::back();

} else {
   $ajax = isset($_REQUEST['ajax']) ? true : false;
   if (!$ajax) {
      Html::header(Rack::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "assets", "rack");
   }
   $options = [
      'id'           => $_GET['id'],
      'withtemplate' => $_GET['withtemplate']
   ];
   if (isset($_GET['position'])) {
      $options['position'] = $_GET['position'];
   }
   if (isset($_GET['room'])) {
      $room = new DCRoom;
      if ($room->getFromDB((int) $_GET['room'])) {
         $options['dcrooms_id']   = $room->getID();
         $options['locations_id'] = $room->fields['locations_id'];
      }
   }
   $rack->display($options);
   if (!$ajax) {
      Html::footer();
   }
}
