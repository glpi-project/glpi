<?php
/*
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
*/

include ('../inc/includes.php');

Session::checkRight("budget", READ);

if (empty($_GET["id"])) {
   $_GET["id"] = '';
}
if (!isset($_GET["withtemplate"])) {
   $_GET["withtemplate"] = '';
}

$budget = new Budget();
if (isset($_POST["add"])) {
   $budget->check(-1, CREATE, $_POST);

   if ($newID = $budget->add($_POST)) {
      Event::log($newID, "budget", 4, "financial",
                  //TRANS: %1$s is the user login, %2$s is the name of the item to add
                  sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"]));
      if ($_SESSION['glpibackcreated']) {
         Html::redirect($budget->getFormURL()."?id=".$newID);
      }
   }
   Html::back();

} else if (isset($_POST["delete"])) {
   $budget->check($_POST["id"], DELETE);

   if ($budget->delete($_POST)) {
      Event::log($_POST["id"], "budget", 4, "financial",
                  //TRANS: %s is the user login
                  sprintf(__('%s deletes an item'), $_SESSION["glpiname"]));
   }
   $budget->redirectToList();

} else if (isset($_POST["restore"])) {
   $budget->check($_POST["id"], DELETE);

   if ($budget->restore($_POST)) {
      Event::log($_POST["id"], "budget", 4, "financial",
                  //TRANS: %s is the user login
                  sprintf(__('%s restores an item'), $_SESSION["glpiname"]));
   }
   $budget->redirectToList();

} else if (isset($_POST["purge"])) {
   $budget->check($_POST["id"], PURGE);

   if ($budget->delete($_POST,1)) {
      Event::log($_POST["id"], "budget", 4, "financial",
                  //TRANS: %s is the user login
                  sprintf(__('%s purges an item'), $_SESSION["glpiname"]));
   }
   $budget->redirectToList();

} else if (isset($_POST["update"])) {
   $budget->check($_POST["id"], UPDATE);

   if ($budget->update($_POST)) {
      Event::log($_POST["id"], "budget", 4, "financial",
                  //TRANS: %s is the user login
                  sprintf(__('%s updates an item'), $_SESSION["glpiname"]));
   }
   Html::back();

} else if (isset($_GET['_in_modal'])) {
      Html::popHeader(Budget::getTypeName(1),$_SERVER['PHP_SELF']);
      $budget->showForm($_GET["id"], array('withtemplate' => $_GET["withtemplate"]));
      Html::popFooter();

} else {
   Html::header(Budget::getTypeName(1), $_SERVER['PHP_SELF'], "management", "budget");
   $budget->display(array('id'           => $_GET["id"],
                          'withtemplate' => $_GET["withtemplate"]));

   Html::footer();
}
