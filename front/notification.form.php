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
 along with GLPI; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

checkRight("notification", "r");

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}

$notification = new Notification();
if (isset($_POST["add"])) {
   $notification->check(-1,'w',$_POST);

   $newID = $notification->add($_POST);
   Event::log($newID, "notifications", 4, "notification",
              $_SESSION["glpiname"]." ".$LANG['log'][20]." :  ".$_POST["name"].".");
   glpi_header($_SERVER['PHP_SELF']."?id=$newID");

} else if (isset($_POST["delete"])) {
   $notification->check($_POST["id"],'d');
   $notification->delete($_POST);

   Event::log($_POST["id"], "notifications", 4, "notification",
              $_SESSION["glpiname"] ." ".$LANG['log'][22]);
   $notification->redirectToList();

} else if (isset($_POST["update"])) {
   $notification->check($_POST["id"],'w');

   $notification->update($_POST);
   Event::log($_POST["id"], "notifications", 4, "notification",
              $_SESSION["glpiname"]." ".$LANG['log'][21]);
   glpi_header($_SERVER['HTTP_REFERER']);

} else {
   commonHeader($LANG['setup'][704],$_SERVER['PHP_SELF'],"config","mailing","notification");
   $notification->showForm($_GET["id"]);
   commonFooter();
}

?>