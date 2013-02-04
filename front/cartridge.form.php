<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2013 by the INDEPNET Development Team.

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

if (!isset($_GET["tID"])) {
   $_GET["tID"] = "";
}
if (!isset($_GET["cID"])) {
   $_GET["cID"] = "";
}

$cart = new Cartridge();
$cartype = new CartridgeItem();

if (isset($_POST["update_cart_use"])) {
   if (isset($_POST['date_use'])) {
      foreach ($_POST["date_use"] as $key => $value) {
         $cart->check($key,'w');

         if ($cart->updateCartUse($value)) {
            Event::log(0, "cartridges", 4, "inventory", $_SESSION["glpiname"]." ".$LANG['log'][94]);
         }
      }
   }
   Html::back();
} else if (isset($_POST["update_cart_out"])) {
   if (isset($_POST['date_out'])) {
      foreach ($_POST["date_out"] as $key => $value) {
         $cart->check($key,'w');
         if (isset($_POST['pages'][$key])) {
            if ($cart->updateCartOut($_POST['pages'][$key], $value)) {
               Event::log(0, "cartridges", 4, "inventory", $_SESSION["glpiname"]." ".$LANG['log'][94]);
            }
         }
      }
   }
   Html::back();
}  else if (isset($_POST["add_several"])) {
   $cartype->check($_POST["tID"],'w');

   for ($i=0 ; $i<$_POST["to_add"] ; $i++) {
      unset($cart->fields["id"]);
      $cart->add($_POST);
   }
   Event::log($_POST["tID"], "cartridges", 4, "inventory",
            $_SESSION["glpiname"]." ".$LANG['log'][88].": ".$_POST["to_add"]);
   Html::back();

} else if (isset($_GET["delete"])) {
   $cartype->check($_GET["tID"],'w');

   Session::checkRight("cartridge", "w");
   if ($cart->delete($_GET)) {
      Event::log($_GET["tID"], "cartridges", 4, "inventory",
               $_SESSION["glpiname"]." ".$LANG['log'][90]);
   }
   Html::back();

} else if (isset($_GET["restore"])) {
   $cartype->check($_GET["tID"],'w');

   if ($cart->restore($_GET)) {
      Event::log($_GET["tID"], "cartridges", 5, "inventory",
               $_SESSION["glpiname"]." ".$LANG['log'][92]);
   }
   Html::back();

} else if (isset($_POST["install"])) {
   $cartype->check($_POST["tID"],'w');

   if ($cart->install($_POST["pID"],$_POST["tID"])) {
      Event::log($_POST["tID"], "cartridges", 5, "inventory",
               $_SESSION["glpiname"]." ".$LANG['log'][95]);
   }
   Html::redirect($CFG_GLPI["root_doc"]."/front/printer.form.php?id=".$_POST["pID"]);

} else if (isset($_GET["uninstall"])) {
   $cartype->check($_GET["tID"],'w');

   if ($cart->uninstall($_GET["id"])) {
      Event::log($_GET["tID"], "cartridges", 5, "inventory",
               $_SESSION["glpiname"]." ".$LANG['log'][96]);
   }
   Html::back();

} else {
   Html::back();
}
?>
