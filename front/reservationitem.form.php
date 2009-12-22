<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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

checkCentralAccess();

$ri=new ReservationItem();

if (isset($_GET["add"])) {
   checkRight("reservation_central","w");
   if ($newID=$ri->add($_GET)){
      Event::log($newID, "reservation", 4, "inventory", $_SESSION["glpiname"]." ".$LANG['log'][20]." ".$_GET["itemtype"]."-".$_GET["items_id"].".");
   }
   glpi_header($_SERVER['HTTP_REFERER']);
} else if (isset($_GET["delete"])) {
   checkRight("reservation_central","w");
   $ri->delete($_GET);
   Event::log($_GET['id'], "reservation", 4, "inventory", $_SESSION["glpiname"]." ".$LANG['log'][22]);
   glpi_header($_SERVER['HTTP_REFERER']);
} else if (isset($_GET["is_active"])) {
   /// TODO  create activate / unactivate action
   checkRight("reservation_central","w");
   $ri->update($_GET);
   Event::log($_GET['id'], "reservation", 4, "inventory", $_SESSION["glpiname"]." ".$LANG['log'][21]);
   glpi_header($_SERVER['HTTP_REFERER']);
} else if (isset($_POST["updatecomment"])) {
   /// TODO  action = update
   checkRight("reservation_central","w");
   $ri->update($_POST);
   Event::log($_POST['id'], "reservation", 4, "inventory", $_SESSION["glpiname"]." ".$LANG['log'][21]);
} else {
   checkRight("reservation_central","w");
   showReservationCommentForm($_SERVER['PHP_SELF'],$_GET["id"]);
}

?>