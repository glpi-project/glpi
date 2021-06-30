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

if (!isset($_GET["databaseinstances_id"])) {
   $_GET["databaseinstances_id"] = "";
}

$database = new Database();
if (isset($_POST["add"])) {
   $database->check(-1, CREATE, $_POST);

   if ($database->add($_POST)) {
      Event::log($_POST['databaseinstances_id'], "databases", 4, "management",
                 //TRANS: %s is the user login
                 sprintf(__('%s adds a database instance'), $_SESSION["glpiname"]));
      if ($_SESSION['glpibackcreated']) {
         Html::redirect($database->getLinkURL());
      }
   }
   Html::back();
} else if (isset($_POST["delete"])) {
   $database->check($_POST['id'], DELETE);
   $ok = $database->delete($_POST);
   if ($ok) {
      Event::log($_POST["id"], "instances", 4, "management",
         //TRANS: %s is the user login
         sprintf(__('%s deletes an item'), $_SESSION["glpiname"]));
   }
   $database->redirectToList();

} else if (isset($_POST["restore"])) {
   $database->check($_POST['id'], DELETE);
   if ($database->restore($_POST)) {
      Event::log($_POST["id"], "instances", 4, "management",
         //TRANS: %s is the user login
         sprintf(__('%s restores an item'), $_SESSION["glpiname"]));
   }
   $database->redirectToList();

} else if (isset($_POST["purge"])) {
   $database->check($_POST["id"], PURGE);

   if ($database->delete($_POST, 1)) {
      Event::log($database->fields['databaseinstances_id'], "databases", 4, "management",
                 //TRANS: %s is the user login
                 sprintf(__('%s purges a database instance'), $_SESSION["glpiname"]));
   }
   $database->redirectToList();
} else if (isset($_POST["update"])) {
   $database->check($_POST["id"], UPDATE);

   if ($database->update($_POST)) {
      Event::log($database->fields['databaseinstances_id'], "databases", 4, "management",
                 //TRANS: %s is the user login
                 sprintf(__('%s updates a database instance'), $_SESSION["glpiname"]));
   }
   Html::back();

} else {
   Html::header(Database::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "management", "database");
   $database->display(['id'           => $_GET["id"],
                        'databaseinstances_id' => $_GET["databaseinstances_id"]]);
   Html::footer();
}

