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

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}

$constype = new ConsumableItem();

if (isset($_POST["add"])) {
   $constype->check(-1,'w',$_POST);

   if ($newID = $constype->add($_POST)) {
      Event::log($newID, "consumables", 4, "inventory",
               $_SESSION["glpiname"]." ".$LANG['log'][20]." ".$_POST["name"].".");
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["delete"])) {
   $constype->check($_POST["id"],'w');

   if ($constype->delete($_POST)) {
      Event::log($_POST["id"], "consumables", 4, "inventory",
               $_SESSION["glpiname"]." ".$LANG['log'][22]);
   }
   $constype->redirectToList();

} else if (isset($_POST["restore"])) {
   $constype->check($_POST["id"],'w');

   if ($constype->restore($_POST)) {
      Event::log($_POST["id"], "consumables", 4, "inventory",
               $_SESSION["glpiname"]." ".$LANG['log'][23]);
   }
   $constype->redirectToList();

} else if (isset($_POST["purge"])) {
   $constype->check($_POST["id"],'w');

   if ($constype->delete($_POST,1)) {
      Event::log($_POST["id"], "consumables", 4, "inventory",
               $_SESSION["glpiname"]." ".$LANG['log'][24]);
   }
   $constype->redirectToList();

} else if (isset($_POST["update"])) {
   $constype->check($_POST["id"],'w');

   if ($constype->update($_POST)) {
      Event::log($_POST["id"], "consumables", 4, "inventory",
               $_SESSION["glpiname"]." ".$LANG['log'][21]);
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else {
   commonHeader($LANG['Menu'][32],$_SERVER['PHP_SELF'],"inventory","consumable");
   $constype->showForm($_GET["id"]);
   commonFooter();
}

?>
