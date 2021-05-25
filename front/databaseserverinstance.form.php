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

if (!isset($_GET["databaseservers_id"])) {
   $_GET["databaseservers_id"] = "";
}

$instance = new DatabaseServerInstance();
if (isset($_POST["add"])) {
   $instance->check(-1, CREATE, $_POST);

   if ($instance->add($_POST)) {
      Event::log($_POST['databaseservers_id'], "databases", 4, "management",
                 //TRANS: %s is the user login
                 sprintf(__('%s adds a database instance'), $_SESSION["glpiname"]));
      if ($_SESSION['glpibackcreated']) {
         Html::redirect($instance->getLinkURL());
      }
   }
   Html::back();
} else if (isset($_POST["delete"])) {
   $instance->check($_POST['id'], DELETE);
   $ok = $instance->delete($_POST);
   if ($ok) {
      Event::log($_POST["id"], "instances", 4, "management",
         //TRANS: %s is the user login
         sprintf(__('%s deletes an item'), $_SESSION["glpiname"]));
   }
   $instance->redirectToList();

} else if (isset($_POST["restore"])) {
   $instance->check($_POST['id'], DELETE);
   if ($instance->restore($_POST)) {
      Event::log($_POST["id"], "instances", 4, "management",
         //TRANS: %s is the user login
         sprintf(__('%s restores an item'), $_SESSION["glpiname"]));
   }
   $instance->redirectToList();

} else if (isset($_POST["purge"])) {
   $instance->check($_POST["id"], PURGE);

   if ($instance->delete($_POST, 1)) {
      Event::log($instance->fields['databaseservers_id'], "databases", 4, "management",
                 //TRANS: %s is the user login
                 sprintf(__('%s purges a database instance'), $_SESSION["glpiname"]));
   }
   $database = new DatabaseServer();
   $database->getFromDB($instance->fields['databaseservers_id']);
   Html::redirect(Toolbox::getItemTypeFormURL('DatabaseServer').'?id='.$instance->fields['databaseservers_id'].
                  ($database->fields['is_template']?"&withtemplate=1":""));

} else if (isset($_POST["update"])) {
   $instance->check($_POST["id"], UPDATE);

   if ($instance->update($_POST)) {
      Event::log($instance->fields['databaseservers_id'], "databases", 4, "management",
                 //TRANS: %s is the user login
                 sprintf(__('%s updates a database instance'), $_SESSION["glpiname"]));
   }
   Html::back();

} else {
   Html::header(DatabaseServer::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "management", "database");
   $instance->display(['id'           => $_GET["id"],
                        'databaseservers_id' => $_GET["databaseservers_id"]]);
   Html::footer();
}

