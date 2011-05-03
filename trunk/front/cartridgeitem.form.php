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

$cartype = new CartridgeItem();

if (isset($_POST["add"])) {
   $cartype->check(-1,'w',$_POST);

   if ($newID = $cartype->add($_POST)) {
      Event::log($newID, "cartridges", 4, "inventory",
               $_SESSION["glpiname"]." ".$LANG['log'][20]." ".$_POST["name"].".");
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["delete"])) {
   $cartype->check($_POST["id"],'w');

   if ($cartype->delete($_POST)) {
      Event::log($_POST["id"], "cartridges", 4, "inventory", $_SESSION["glpiname"]." ".$LANG['log'][22]);
   }
   $cartype->redirectToList();

} else if (isset($_POST["restore"])) {
   $cartype->check($_POST["id"],'w');

   if ($cartype->restore($_POST)) {
      Event::log($_POST["id"], "cartridges", 4, "inventory", $_SESSION["glpiname"]." ".$LANG['log'][23]);
   }
   $cartype->redirectToList();

} else if (isset($_POST["purge"])) {
   $cartype->check($_POST["id"],'w');

   if ($cartype->delete($_POST,1)) {
      Event::log($_POST["id"], "cartridges", 4, "inventory", $_SESSION["glpiname"]." ".$LANG['log'][24]);
   }
   $cartype->redirectToList();

} else if (isset($_POST["update"])) {
   $cartype->check($_POST["id"],'w');

   if ($cartype->update($_POST)) {
      Event::log($_POST["id"], "cartridges", 4, "inventory", $_SESSION["glpiname"]." ".$LANG['log'][21]);
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["addtype"])) {
   $cartype->check($_POST["tID"],'w');

   if ($cartype->addCompatibleType($_POST["tID"],$_POST["printermodels_id"])) {
      Event::log($_POST["tID"], "cartridges", 4, "inventory",
               $_SESSION["glpiname"]." ".$LANG['log'][30]);
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_GET["deletetype"])) {
   $cartype->check($_GET["tID"],'w');

   if ($cartype->deleteCompatibleType($_GET["id"])) {
      Event::log($_GET["tID"], "cartridges", 4, "inventory",
               $_SESSION["glpiname"]." ".$LANG['log'][31]);
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else {
   commonHeader($LANG['Menu'][21],$_SERVER['PHP_SELF'],"inventory","cartridge");
   $cartype->showForm($_GET["id"]);
   commonFooter();
}

?>
