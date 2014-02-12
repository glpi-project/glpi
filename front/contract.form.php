<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
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

Session::checkRight("contract", "r");

if (!isset($_GET["id"])) {
   $_GET["id"] = -1;
}

if (!isset($_GET["withtemplate"])) {
   $_GET["withtemplate"] = "";
}

$contract         = new Contract();
$contractitem     = new Contract_Item();
$contractsupplier = new Contract_Supplier();

if (isset($_POST["add"])) {
   $contract->check(-1,'w',$_POST);

   if ($newID = $contract->add($_POST)) {
      Event::log($newID, "contracts", 4, "financial",
                 sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"]));
   }
   Html::back();

} else if (isset($_POST["delete"])) {
   $contract->check($_POST['id'],'d');

   if ($contract->delete($_POST)) {
      Event::log($_POST["id"], "contracts", 4, "financial",
                 //TRANS: %s is the user login
                 sprintf(__('%s deletes an item'), $_SESSION["glpiname"]));
   }
   $contract->redirectToList();

} else if (isset($_POST["restore"])) {
   $contract->check($_POST['id'],'d');

   if ($contract->restore($_POST)) {
      Event::log($_POST["id"], "contracts", 4, "financial",
                 //TRANS: %s is the user login
                 sprintf(__('%s restores an item'), $_SESSION["glpiname"]));
   }
   $contract->redirectToList();

} else if (isset($_POST["purge"])) {
   $contract->check($_POST['id'],'d');

   if ($contract->delete($_POST,1)) {
      Event::log($_POST["id"], "contracts", 4, "financial",
                 //TRANS: %s is the user login
                 sprintf(__('%s purges an item'), $_SESSION["glpiname"]));
   }
   $contract->redirectToList();

} else if (isset($_POST["update"])) {
   $contract->check($_POST['id'],'w');

   if ($contract->update($_POST)) {
      Event::log($_POST["id"], "contracts", 4, "financial",
                 //TRANS: %s is the user login
                 sprintf(__('%s updates an item'), $_SESSION["glpiname"]));
   }
   Html::back();

} else {
   Html::header(Contract::getTypeName(2), $_SERVER['PHP_SELF'], "financial", "contract");
   $contract->showForm($_GET["id"], array('withtemplate' => $_GET["withtemplate"]));
   Html::footer();
}
?>