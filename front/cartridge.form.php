<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

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

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

Session::checkRight("cartridge", "r");

if (!isset($_GET["cartridgeitems_id"])) {
   $_GET["cartridgeitems_id"] = "";
}
if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}

$cart    = new Cartridge();
$cartype = new CartridgeItem();

if (isset($_POST["update_pages"]) || isset($_POST["update_pages_x"])) {
   $cart->check($_POST["id"],'w');

   if ($cart->updatePages($_POST['pages'])) {
      Event::log(0, "cartridges", 4, "inventory",
               //TRANS: %s is the user login
               sprintf(__('%s updates cartridges'), $_SESSION["glpiname"]));
   }
   Html::back();

} else if (isset($_POST["add_several"])) {
   $cartype->check($_POST["cartridgeitems_id"],'w');

   for ($i=0 ; $i<$_POST["to_add"] ; $i++) {
      unset($cart->fields["id"]);
      $cart->add($_POST);
   }
   Event::log($_POST["cartridgeitems_id"], "cartridges", 4, "inventory",
               //TRANS: %s is the user login
               sprintf(__('%s adds cartridges'), $_SESSION["glpiname"]));
   Html::back();

} else if (isset($_GET["delete"])) {
   $cartype->check($_GET["cartridgeitems_id"],'w');

   Session::checkRight("cartridge", "w");
   if ($cart->delete($_GET)) {
      Event::log($_GET["cartridgeitems_id"], "cartridges", 4, "inventory",
               //TRANS: %s is the user login
               sprintf(__('%s deletes a cartridge'),$_SESSION["glpiname"]));
   }
   Html::back();

} else if (isset($_GET["restore"])) {
   $cartype->check($_GET["cartridgeitems_id"],'w');

   if ($cart->restore($_GET)) {
      Event::log($_GET["cartridgeitems_id"], "cartridges", 5, "inventory",
               //TRANS: %s is the user login
               sprintf(__('%s restores a cartridge'), $_SESSION["glpiname"]));
   }
   Html::back();

} else if (isset($_POST["install"])) {
   $cartype->check($_POST["cartridgeitems_id"],'w');

   if ($cart->install($_POST["printers_id"],$_POST["cartridgeitems_id"])) {
      Event::log($_POST["cartridgeitems_id"], "cartridges", 5, "inventory",
               //TRANS: %s is the user login
               sprintf(__('%s installs a cartridge'), $_SESSION["glpiname"]));
   }
   Html::redirect($CFG_GLPI["root_doc"]."/front/printer.form.php?id=".$_POST["printers_id"]);

} else if (isset($_GET["uninstall"])) {
   $cartype->check($_GET["cartridgeitems_id"],'w');

   if ($cart->uninstall($_GET["id"])) {
      Event::log($_GET["cartridgeitems_id"], "cartridges", 5, "inventory",
               //TRANS: %s is the user login
               sprintf(__('%s uninstalls a cartridge'), $_SESSION["glpiname"]));
   }
   Html::back();

} else {
   Html::back();
}
?>
