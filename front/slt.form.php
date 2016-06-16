<?php
/*
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2016 Teclib'.

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
* @since version 9.1
*/

include ('../inc/includes.php');

Session::checkRight("sla", READ);

if (empty($_GET["id"])) {
   $_GET["id"] = "";
}

$slt = new SLT();

if (isset($_POST["add"])) {
   $slt->check(-1, CREATE, $_POST);

   if ($newID = $slt->add($_POST)) {
      Event::log($newID, "slts", 4, "setup",
                 sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"]));
      if ($_SESSION['glpibackcreated']) {
         Html::redirect($slt->getFormURL()."?id=".$newID);
      }
   }
   Html::back();

} else if (isset($_POST["purge"])) {
   $slt->check($_POST["id"], PURGE);
   $slt->delete($_POST, 1);

   Event::log($_POST["id"], "slts", 4, "setup",
              //TRANS: %s is the user login
              sprintf(__('%s purges an item'), $_SESSION["glpiname"]));
   $slt->redirectToList();

} else if (isset($_POST["update"])) {
   $slt->check($_POST["id"], UPDATE);
   $slt->update($_POST);

   Event::log($_POST["id"], "slts", 4, "setup",
              //TRANS: %s is the user login
              sprintf(__('%s updates an item'), $_SESSION["glpiname"]));
   Html::back();

} else {
   Html::header(SLT::getTypeName(1), $_SERVER['PHP_SELF'], "config", "sla", "slt");

   $slt->display(array('id' => $_GET["id"]));
   Html::footer();
}
