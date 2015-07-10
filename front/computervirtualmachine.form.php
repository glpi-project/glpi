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

Session::checkCentralAccess();

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}

if (!isset($_GET["computers_id"])) {
   $_GET["computers_id"] = "";
}

$disk = new ComputerVirtualMachine();
if (isset($_POST["add"])) {
   $disk->check(-1, CREATE, $_POST);

   if ($newID = $disk->add($_POST)) {
      Event::log($_POST['computers_id'], "computers", 4, "inventory",
                 //TRANS: %s is the user login
                 sprintf(__('%s adds a virtual machine'), $_SESSION["glpiname"]));
      if ($_SESSION['glpibackcreated']) {
         Html::redirect(Toolbox::getItemTypeFormURL('ComputerVirtualMachine')."?id=".$newID);
      }
   }
   Html::back();

} else if (isset($_POST["purge"])) {
   $disk->check($_POST["id"], PURGE);

   if ($disk->delete($_POST, 1)) {
      Event::log($disk->fields['computers_id'], "computers", 4, "inventory",
                 //TRANS: %s is the user login
                 sprintf(__('%s purges a virtual machine'), $_SESSION["glpiname"]));
   }
   $computer = new Computer();
   $computer->getFromDB($disk->fields['computers_id']);
   Html::redirect(Toolbox::getItemTypeFormURL('Computer').'?id='.$disk->fields['computers_id'].
                  ($computer->fields['is_template']?"&withtemplate=1":""));

} else if (isset($_POST["update"])) {
   $disk->check($_POST["id"], UPDATE);

   if ($disk->update($_POST)) {
      Event::log($disk->fields['computers_id'], "computers", 4, "inventory",
                 //TRANS: %s is the user login
                 sprintf(__('%s updates a virtual machine'), $_SESSION["glpiname"]));
   }
   Html::back();

} else {
   Html::header(Computer::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "assets", "computer");
   $disk->display(array('id'           => $_GET["id"],
                        'computers_id' => $_GET["computers_id"]));
   Html::footer();
}
?>