<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

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
* @since version 0.85
*/

include ('../inc/includes.php');

if (empty($_GET["id"])) {
   $_GET["id"] = '';
}

Session::checkLoginUser();

$project = new Project();
if (isset($_POST["add"])) {
   $project->check(-1, CREATE, $_POST);

   $newID = $project->add($_POST);
   Event::log($newID, "project", 4, "maintain",
              //TRANS: %1$s is the user login, %2$s is the name of the item
              sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"]));
   if ($_SESSION['glpibackcreated']) {
      Html::redirect($project->getFormURL()."?id=".$newID);
   } else {
      Html::back();
   }

} else if (isset($_POST["delete"])) {
   $project->check($_POST["id"], DELETE);

   $project->delete($_POST);
   Event::log($_POST["id"], "project", 4, "maintain",
              //TRANS: %s is the user login
              sprintf(__('%s deletes an item'), $_SESSION["glpiname"]));
   $project->redirectToList();

} else if (isset($_POST["restore"])) {
   $project->check($_POST["id"], DELETE);

   $project->restore($_POST);
   Event::log($_POST["id"], "project", 4, "maintain",
              //TRANS: %s is the user login
              sprintf(__('%s restores an item'), $_SESSION["glpiname"]));
   $project->redirectToList();

} else if (isset($_POST["purge"])) {
   $project->check($_POST["id"], PURGE);
   $project->delete($_POST,1);

   Event::log($_POST["id"], "project", 4, "maintain",
              //TRANS: %s is the user login
              sprintf(__('%s purges an item'), $_SESSION["glpiname"]));
   $project->redirectToList();

} else if (isset($_POST["update"])) {
   $project->check($_POST["id"], UPDATE);

   $project->update($_POST);
   Event::log($_POST["id"], "project", 4, "maintain",
              //TRANS: %s is the user login
              sprintf(__('%s updates an item'), $_SESSION["glpiname"]));

   Html::back();

} else {
   Html::header(Project::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "tools", "project");

   if (isset($_GET['showglobalgantt']) && $_GET['showglobalgantt']) {
      $project->showGantt(-1);
   } else {
      $project->display($_GET);
   }
   Html::footer();
}
?>
