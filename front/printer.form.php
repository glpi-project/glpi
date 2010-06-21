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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

if (!isset($_GET["id"])) {
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

$print = new Printer();
if (isset($_POST["add"])) {
   $print->check(-1,'w',$_POST);

   if ($newID=$print->add($_POST)) {
      Event::log($newID, "printers", 4, "inventory",
                 $_SESSION["glpiname"]."  ".$LANG['log'][20]."  ".$_POST["name"].".");
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["delete"])) {
   $print->check($_POST["id"],'d');
   $print->delete($_POST);

   Event::log($_POST["id"], "printers", 4, "inventory", $_SESSION["glpiname"]." ".$LANG['log'][22]);
   if (!empty($_POST["withtemplate"])) {
      glpi_header($CFG_GLPI["root_doc"]."/front/setup.templates.php");
   } else {
      glpi_header($CFG_GLPI["root_doc"]."/front/printer.php");
   }

} else if (isset($_POST["restore"])) {
   $print->check($_POST["id"],'d');

   $print->restore($_POST);
   Event::log($_POST["id"], "printers", 4, "inventory", $_SESSION["glpiname"]." ".$LANG['log'][23]);
   glpi_header($CFG_GLPI["root_doc"]."/front/printer.php");

} else if (isset($_REQUEST["purge"])) {

   $print->check($_REQUEST["id"],'d');

   $print->delete($_REQUEST,1);
   Event::log($_REQUEST["id"], "printers", 4, "inventory", $_SESSION["glpiname"]." ".$LANG['log'][24]);
   glpi_header($CFG_GLPI["root_doc"]."/front/printer.php");

} else if (isset($_POST["update"])) {
   $print->check($_POST["id"],'w');

   $print->update($_POST);
   Event::log($_POST["id"], "printers", 4, "inventory", $_SESSION["glpiname"]." ".$LANG['log'][21]);
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_GET["unglobalize"])) {
   $print->check($_GET["id"],'w');

   Computer_Item::unglobalizeItem($print);
   Event::log($_GET["id"], "printers", 4, "inventory", $_SESSION["glpiname"]." ".$LANG['log'][60]);
   glpi_header($CFG_GLPI["root_doc"]."/front/printer.form.php?id=".$_GET["id"]);

} else {
   commonHeader($LANG['Menu'][2],$_SERVER['PHP_SELF'],"inventory","printer");
   $print->showForm($_GET["id"], array('withtemplate' => $_GET["withtemplate"]));
   commonFooter();
}

?>
