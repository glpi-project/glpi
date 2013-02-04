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

Session::checkRight("consumable", "r");

if (!isset($_GET["tID"])) {
   $_GET["tID"] = "";
}
if (!isset($_GET["cID"])) {
   $_GET["cID"] = "";
}

$con = new Consumable();
$constype = new ConsumableItem();

if (isset($_POST["add_several"])) {
   $constype->check($_POST["tID"],'w');

   for ($i=0 ; $i<$_POST["to_add"] ; $i++) {
      unset($con->fields["id"]);
      $con->add($_POST);
   }
   Event::log($_POST["tID"], "consumables", 4, "inventory",
            $_SESSION["glpiname"]." ".$LANG['log'][89].": ".$_POST["to_add"]);

   Html::back();

} else if (isset($_GET["delete"])) {
   $constype->check($_GET["tID"],'w');

   if ($con->delete($_GET)) {
      Event::log($_GET["tID"], "consumables", 4, "inventory",
               $_SESSION["glpiname"]." ".$LANG['log'][91]);
   }
   Html::back();

} else if (isset($_POST["give"])) {
   $constype->check($_POST["tID"],'w');

   if ($_POST["items_id"] > 0 && !empty($_POST['itemtype'])) {
      if (isset($_POST["out"])) {
         foreach ($_POST["out"] as $key => $val) {
            $con->out($key,$_POST['itemtype'],$_POST["items_id"]);
         }
      }
      $item = new $_POST['itemtype']();
      $item->getFromDB($_POST["items_id"]);
      Event::log($_POST["tID"], "consumables", 5, "inventory",
               $_SESSION["glpiname"]." ".$LANG['log'][97]." ".$item->getNameID());
   }
   Html::back();

} else if (isset($_GET["restore"])) {
   $constype->check($_GET["tID"],'w');

   if ($con->restore($_GET)) {
      Event::log($_GET["tID"], "consumables", 5, "inventory",
               $_SESSION["glpiname"]." ".$LANG['log'][93]);
   }
   Html::back();

} else {
   Html::back();
}

?>
