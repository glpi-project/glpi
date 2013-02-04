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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

Session::checkRight("peripheral", "r");

if (empty($_GET["id"])) {
   $_GET["id"] = "";
}
if (!isset($_GET["sort"])) {
   $_GET["sort"] = "";
}
if (!isset($_GET["order"])) {
   $_GET["order"] = "";
}
if (!isset($_GET["withtemplate"])) {
   $_GET["withtemplate"] = "";
}

$peripheral = new Peripheral();

if (isset($_POST["add"])) {
   $peripheral->check(-1,'w',$_POST);

   $newID = $peripheral->add($_POST);
   Event::log($newID, "peripherals", 4, "inventory",
              $_SESSION["glpiname"]." ".$LANG['log'][20]." ".$_POST["name"].".");
   Html::back();

} else if (isset($_POST["delete"])) {
   $peripheral->check($_POST["id"],'d');
   $peripheral->delete($_POST);

   Event::log($_POST["id"], "peripherals", 4, "inventory",
              $_SESSION["glpiname"]." ".$LANG['log'][22]);
   $peripheral->redirectToList();

} else if (isset($_POST["restore"])) {
   $peripheral->check($_POST["id"],'d');

   $peripheral->restore($_POST);
   Event::log($_POST["id"], "peripherals", 4, "inventory",
              $_SESSION["glpiname"]." ".$LANG['log'][23]);
   $peripheral->redirectToList();

} else if (isset($_REQUEST["purge"])) {
   $peripheral->check($_REQUEST["id"],'d');

   $peripheral->delete($_REQUEST,1);
   Event::log($_REQUEST["id"], "peripherals", 4, "inventory",
              $_SESSION["glpiname"]." ".$LANG['log'][24]);
   $peripheral->redirectToList();

} else if (isset($_POST["update"])) {
   $peripheral->check($_POST["id"],'w');

   $peripheral->update($_POST);
   Event::log($_POST["id"], "peripherals", 4, "inventory",
              $_SESSION["glpiname"]." ".$LANG['log'][21]);
   Html::back();

} else if (isset($_GET["unglobalize"])) {
   $peripheral->check($_GET["id"],'w');

   Computer_Item::unglobalizeItem($peripheral);
   Event::log($_GET["id"], "peripherals", 4, "inventory", $_SESSION["glpiname"]." ".$LANG['log'][60]);
   Html::redirect($CFG_GLPI["root_doc"]."/front/peripheral.form.php?id=".$_GET["id"]);

} else {
   Html::header($LANG['Menu'][16],$_SERVER['PHP_SELF'],"inventory","peripheral");
   $peripheral->showForm($_GET["id"], array('withtemplate' => $_GET["withtemplate"]));
   Html::footer();
}
?>