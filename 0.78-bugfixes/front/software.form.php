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

$soft = new Software();
if (isset($_POST["add"])) {
   $soft->check(-1,'w',$_POST);

   $newID = $soft->add($_POST);
   Event::log($newID, "software", 4, "inventory",
              $_SESSION["glpiname"]." ".$LANG['log'][20]." ".$_POST["name"].".");
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["delete"])) {
   $soft->check($_POST["id"],'d');
   $soft->delete($_POST);

   Event::log($_POST["id"], "software", 4, "inventory",
              $_SESSION["glpiname"]." ".$LANG['log'][22]);

   $soft->redirectToList();

} else if (isset($_POST["restore"])) {
   $soft->check($_POST["id"],'d');

   $soft->restore($_POST);
   Event::log($_POST["id"], "software", 4, "inventory",
              $_SESSION["glpiname"]." ".$LANG['log'][23]);
   $soft->redirectToList();

} else if (isset($_REQUEST["purge"])) {

   $soft->check($_REQUEST["id"],'d');

   $soft->delete($_REQUEST,1);
   Event::log($_REQUEST["id"], "software", 4, "inventory", $_SESSION["glpiname"]." ".$LANG['log'][24]);
   $soft->redirectToList();

} else if (isset($_POST["update"])) {
   $soft->check($_POST["id"],'w');

   $soft->update($_POST);
   Event::log($_POST["id"], "software", 4, "inventory", $_SESSION["glpiname"]." ".$LANG['log'][21]);
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["mergesoftware"])) {
   popHeader($LANG['Menu'][4]);

   if (isset($_POST["id"])
       && isset($_POST["item"])
       && is_array($_POST["item"])
       && count($_POST["item"])) {

      $soft->check($_POST["id"],'w');
      $soft->merge($_POST["item"]);
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else {
   commonHeader($LANG['Menu'][4],$_SERVER['PHP_SELF'],"inventory","software");
   $soft->showForm($_GET["id"], array('withtemplate' => $_GET["withtemplate"]));
   commonFooter();
}

?>
