<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

if (!isset($_GET["tID"])) {
   $_GET["tID"] = "";
}
if (!isset($_GET["cID"])) {
   $_GET["cID"] = "";
}

$cart = new Cartridge();
$cartype = new CartridgeItem();

if (isset($_POST["update_pages"]) || isset($_POST["update_pages_x"])) {
   $cart->check($_POST["cID"],'w');

   if ($cart->updatePages($_POST['pages'])) {
      Event::log(0, "cartridges", 4, "inventory", $_SESSION["glpiname"]." ".$LANG['log'][94]);
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["add_several"])) {
   $cartype->check($_POST["tID"],'w');

   for ($i=0 ; $i<$_POST["to_add"] ; $i++) {
      unset($cart->fields["id"]);
      $cart->add($_POST);
   }
   Event::log($_POST["tID"], "cartridges", 4, "inventory",
            $_SESSION["glpiname"]." ".$LANG['log'][88].": ".$_POST["to_add"]);
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_GET["delete"])) {
   $cartype->check($_GET["tID"],'w');

   checkRight("cartridge","w");
   if ($cart->delete($_GET)) {
      Event::log($_GET["tID"], "cartridges", 4, "inventory",
               $_SESSION["glpiname"]." ".$LANG['log'][90]);
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_GET["restore"])) {
   $cartype->check($_GET["tID"],'w');

   if ($cart->restore($_GET)) {
      Event::log($_GET["tID"], "cartridges", 5, "inventory",
               $_SESSION["glpiname"]." ".$LANG['log'][92]);
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["install"])) {
   $cartype->check($_POST["tID"],'w');

   if ($cart->install($_POST["pID"],$_POST["tID"])) {
      Event::log($_POST["tID"], "cartridges", 5, "inventory",
               $_SESSION["glpiname"]." ".$LANG['log'][95]);
   }
   glpi_header($CFG_GLPI["root_doc"]."/front/printer.form.php?id=".$_POST["pID"]);

} else if (isset($_GET["uninstall"])) {
   $cartype->check($_GET["tID"],'w');

   if ($cart->uninstall($_GET["id"])) {
      Event::log($_GET["tID"], "cartridges", 5, "inventory",
               $_SESSION["glpiname"]." ".$LANG['log'][96]);
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else {
   glpi_header($_SERVER['HTTP_REFERER']);
}
?>
