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

Session::checkRight("cluster", READ);

if (empty($_GET["id"])) {
   $_GET["id"] = "";
}
if (!isset($_GET["withtemplate"])) {
   $_GET["withtemplate"] = "";
}

$cluster = new Cluster();

if (isset($_POST["add"])) {
   $cluster->check(-1, CREATE, $_POST);

   if ($newID = $cluster->add($_POST)) {
      Event::log($newID, "cluster", 4, "inventory",
                 sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"]));
      if ($_SESSION['glpibackcreated']) {
         Html::redirect($cluster->getLinkURL());
      }
   }
   Html::back();

} else if (isset($_POST["delete"])) {
   $cluster->check($_POST["id"], DELETE);
   $cluster->delete($_POST);

   Event::log($_POST["id"], "cluster", 4, "inventory",
              //TRANS: %s is the user login
              sprintf(__('%s deletes an item'), $_SESSION["glpiname"]));
   $cluster->redirectToList();

} else if (isset($_POST["restore"])) {
   $cluster->check($_POST["id"], DELETE);

   $cluster->restore($_POST);
   Event::log($_POST["id"], "cluster", 4, "inventory",
              //TRANS: %s is the user login
              sprintf(__('%s restores an item'), $_SESSION["glpiname"]));
   $cluster->redirectToList();

} else if (isset($_POST["purge"])) {
   $cluster->check($_POST["id"], PURGE);

   $cluster->delete($_POST, 1);
   Event::log($_POST["id"], "cluster", 4, "inventory",
              //TRANS: %s is the user login
              sprintf(__('%s purges an item'), $_SESSION["glpiname"]));
   $cluster->redirectToList();

} else if (isset($_POST["update"])) {
   $cluster->check($_POST["id"], UPDATE);

   $cluster->update($_POST);
   Event::log($_POST["id"], "cluster", 4, "inventory",
              //TRANS: %s is the user login
              sprintf(__('%s updates an item'), $_SESSION["glpiname"]));
   Html::back();

} else {
   Html::header(Cluster::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "management", "cluster");
   $options = ['id' => $_GET['id']];
   if (isset($_GET['position'])) {
      $options['position'] = $_GET['position'];
   }
   if (isset($_GET['room'])) {
      $options['room'] = $_GET['room'];
   }
   $options['withtemplate'] = $_GET['withtemplate'];
   $cluster->display($options);
   Html::footer();
}
