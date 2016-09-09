<?php
/*
 * @version $Id$
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

Session::checkRight("cartridge", READ);

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}

$cartype = new CartridgeItem();

if (isset($_POST["add"])) {
   $cartype->check(-1, CREATE, $_POST);

   if ($newID = $cartype->add($_POST)) {
      Event::log($newID, "cartridgeitems", 4, "inventory",
                 sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"]));
      if ($_SESSION['glpibackcreated']) {
         Html::redirect($cartype->getFormURL()."?id=".$newID);
      }
   }
   Html::back();

} else if (isset($_POST["delete"])) {
   $cartype->check($_POST["id"], DELETE);

   if ($cartype->delete($_POST)) {
      Event::log($_POST["id"], "cartridgeitems", 4, "inventory",
                 //TRANS: %s is the user login
                 sprintf(__('%s deletes an item'), $_SESSION["glpiname"]));
   }
   $cartype->redirectToList();

} else if (isset($_POST["restore"])) {
   $cartype->check($_POST["id"], DELETE);

   if ($cartype->restore($_POST)) {
      Event::log($_POST["id"], "cartridgeitems", 4, "inventory",
                 //TRANS: %s is the user login
                 sprintf(__('%s restores an item'), $_SESSION["glpiname"]));
   }
   $cartype->redirectToList();

} else if (isset($_POST["purge"])) {
   $cartype->check($_POST["id"], PURGE);

   if ($cartype->delete($_POST,1)) {
      Event::log($_POST["id"], "cartridgeitems", 4, "inventory",
                 //TRANS: %s is the user login
                 sprintf(__('%s purges an item'), $_SESSION["glpiname"]));
   }
   $cartype->redirectToList();

} else if (isset($_POST["update"])) {
   $cartype->check($_POST["id"], UPDATE);

   if ($cartype->update($_POST)) {
      Event::log($_POST["id"], "cartridgeitems", 4, "inventory",
                 //TRANS: %s is the user login
                 sprintf(__('%s updates an item'), $_SESSION["glpiname"]));
   }
   Html::back();

} else {
   Html::header(Cartridge::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "assets", "cartridgeitem");
   $cartype->display(array('id' => $_GET["id"]));
   Html::footer();
}
?>
