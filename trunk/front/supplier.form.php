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
   $_GET["id"] = -1;
}
if (!isset($_GET["start"])) {
   $_GET["start"] = 0;
}
if (!isset($_GET["sort"])) {
   $_GET["sort"] = "";
}
if (!isset($_GET["order"])) {
   $_GET["order"] = "";
}

$ent = new Supplier();

if (isset($_POST["add"])) {
   $ent->check(-1,'w',$_POST);

   if ($newID = $ent->add($_POST)) {
      Event::log($newID, "suppliers", 4, "financial",
                 $_SESSION["glpiname"]." ".$LANG['log'][20]." ".$_POST["name"].".");
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["delete"])) {
   $ent->check($_POST["id"],'w');
   $ent->delete($_POST);
   Event::log($_POST["id"], "suppliers", 4, "financial", $_SESSION["glpiname"]." ".$LANG['log'][22]);
   $ent->redirectToList();

} else if (isset($_POST["restore"])) {
   $ent->check($_POST["id"],'w');
   $ent->restore($_POST);
   Event::log($_POST["id"], "suppliers", 4, "financial", $_SESSION["glpiname"]." ".$LANG['log'][23]);
   $ent->redirectToList();

} else if (isset($_POST["purge"])) {
   $ent->check($_POST["id"],'w');
   $ent->delete($_POST,1);
   Event::log($_POST["id"], "suppliers", 4, "financial", $_SESSION["glpiname"]." ".$LANG['log'][24]);
   $ent->redirectToList();

} else if (isset($_POST["update"])) {
   $ent->check($_POST["id"],'w');
   $ent->update($_POST);
   Event::log($_POST["id"], "suppliers", 4, "financial", $_SESSION["glpiname"]." ".$LANG['log'][21]);
   glpi_header($_SERVER['HTTP_REFERER']);

} else {
   commonHeader($LANG['Menu'][23],'',"financial","supplier");

   $ent->showForm($_GET["id"]);

   commonFooter();
}

?>
