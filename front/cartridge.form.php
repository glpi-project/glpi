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

Session::checkRight("cartridge", READ);

$cart    = new Cartridge();
$cartype = new CartridgeItem();

if (isset($_POST["add"])) {
   $cartype->check($_POST["cartridgeitems_id"], CREATE);

   for ($i=0 ; $i<$_POST["to_add"] ; $i++) {
      unset($cart->fields["id"]);
      $cart->add($_POST);
   }
   Event::log($_POST["cartridgeitems_id"], "cartridgeitems", 4, "inventory",
              //TRANS: %s is the user login
              sprintf(__('%s adds cartridges'), $_SESSION["glpiname"]));
   Html::back();

} else if (isset($_POST["purge"])) {
   $cartype->check($_POST["cartridgeitems_id"], PURGE);

   if ($cart->delete($_POST, 1)) {
      Event::log($_POST["cartridgeitems_id"], "cartridgeitems", 4, "inventory",
                 //TRANS: %s is the user login
                 sprintf(__('%s purges a cartridge'), $_SESSION["glpiname"]));
   }
   Html::back();

} else if (isset($_POST["install"])) {
   if ($_POST["cartridgeitems_id"]) {
      $cartype->check($_POST["cartridgeitems_id"], UPDATE);
      for ($i=0 ; $i<$_POST["nbcart"] ; $i++) {
         if ($cart->install($_POST["printers_id"],$_POST["cartridgeitems_id"])) {
            Event::log($_POST["printers_id"], "printers", 5, "inventory",
                       //TRANS: %s is the user login
                       sprintf(__('%s installs a cartridge'), $_SESSION["glpiname"]));
         }
      }
   }
   Html::redirect($CFG_GLPI["root_doc"]."/front/printer.form.php?id=".$_POST["printers_id"]);

} else if (isset($_POST["update"])) {
   $cart->check($_POST["id"], UPDATE);

   if ($cart->update($_POST)) {
      Event::log($_POST["printers_id"], "printers", 4, "inventory",
                 //TRANS: %s is the user login
                 sprintf(__('%s updates a cartridge'), $_SESSION["glpiname"]));
   }
   Html::back();

} else {
   Html::back();
}
?>
