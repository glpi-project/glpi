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
*/

include ('../inc/includes.php');


if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}

$computerconfiguration = new ComputerConfiguration();
if (isset($_POST["add"])) {
   if ($newID = $computerconfiguration->add($_POST)) {
      Event::log($newID, "computerconfigurations", 4, "setup",
              sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"]));
      if ($_SESSION['glpibackcreated']) {
         Html::redirect($computerconfiguration->getFormURL()."?id=".$newID);
      }
   }
   Html::back();

} else if (isset($_POST["purge"])) {
   $computerconfiguration->check($_POST['id'], PURGE);
   $computerconfiguration->delete($_POST,1);
   Event::log($_POST["id"], "computerconfigurations", 4, "setup",
                 sprintf(__('%s purges an item'), $_SESSION["glpiname"]));
   $computerconfiguration->redirectToList();

} else if (isset($_POST["update"])) {
   $computerconfiguration->check($_POST['id'], UPDATE);
   $computerconfiguration->update($_POST);
   Event::log($_POST["id"], "computerconfigurations", 4, "setup",
              sprintf(__('%s updates an item'), $_SESSION["glpiname"]));
   Html::back();
   
} else if (isset($_POST["preview"])) {
   $computerconfiguration->getFromDB($_GET["id"]);
   $computerconfiguration->preview();

} else {
   Html::header(ComputerConfiguration::getTypeName(2), $_SERVER['PHP_SELF'], "config", "control",
             "ComputerConfiguration");
   $computerconfiguration->display(array('id' => $_GET["id"]));
   Html::footer();
}
