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

Session::checkRight("group", READ);

if (empty($_GET["id"])) {
   $_GET["id"] = "";
}

$group = new Group();

if (isset($_POST["add"])) {
   $group->check(-1, CREATE, $_POST);
   if ($newID=$group->add($_POST)) {
      Event::log($newID, "groups", 4, "setup",
                 sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"]));
      if ($_SESSION['glpibackcreated']) {
         Html::redirect($group->getLinkURL());
      }
   }
   Html::back();

} else if (isset($_POST["purge"])) {
   $group->check($_POST["id"], PURGE);
   if ($group->isUsed()
         && empty($_POST["forcepurge"])) {
      Html::header($group->getTypeName(1), $_SERVER['PHP_SELF'], "admin", "group",
      str_replace('glpi_', '', $group->getTable()));

      $group->showDeleteConfirmForm($_SERVER['PHP_SELF']);
      Html::footer();
   } else {
      $group->delete($_POST, 1);
      Event::log($_POST["id"], "groups", 4, "setup",
                 //TRANS: %s is the user login
                 sprintf(__('%s purges an item'), $_SESSION["glpiname"]));
      $group->redirectToList();
   }

} else if (isset($_POST["update"])) {
   $group->check($_POST["id"], UPDATE);
   $group->update($_POST);
   Event::log($_POST["id"], "groups", 4, "setup",
              //TRANS: %s is the user login
              sprintf(__('%s updates an item'), $_SESSION["glpiname"]));
   Html::back();

} else if (isset($_GET['_in_modal'])) {
   Html::popHeader(Group::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF']);
   $group->showForm($_GET["id"]);
   Html::popFooter();

} else if (isset($_POST["replace"])) {
   $group->check($_POST["id"], PURGE);
   $group->delete($_POST, 1);

   Event::log($_POST["id"], "groups", 4, "setup",
              //TRANS: %s is the user login
              sprintf(__('%s replaces an item'), $_SESSION["glpiname"]));
   $group->redirectToList();

} else {
   Html::header(Group::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "admin", "group");
   $group->display(['id' =>$_GET["id"]]);
   Html::footer();
}

