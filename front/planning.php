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



   if (!isset($_GET["uID"]) || !haveRight("show_all_planning","1")) {
      $_GET["uID"] = getLoginUserID();
   }
   if (!isset($_GET["gID"])) {
      $_GET["gID"] = 0;
   }
   if (!isset($_GET["usertype"])) {
      $_GET["usertype"] = "user";
   }

   switch ($_GET["usertype"]) {
      case "user" :
         $_GET['gID'] = 0;
         break;

      case "group" :
         $_GET['uID'] = 0;
         break;

      case "user_group" :
         $_GET['gID'] = "mine";
         break;
   }

if (isset($_REQUEST['genical'])) {
   // Send UTF8 Headers
   @header ("content-type:text/calendar; charset=UTF-8");
   @header("Content-disposition: filename=\"glpi.ics\"");

   echo Planning::generateIcal($_GET["uID"],$_GET["gID"],$_GET["usertype"]);

} else {
   commonHeader($LANG['Menu'][29],$_SERVER['PHP_SELF'],"maintain","planning");

   checkSeveralRightsOr(array('show_all_planning' => '1',
                              'show_planning'     => '1'));

   if (!isset($_GET["date"]) || empty($_GET["date"])) {
      $_GET["date"] = strftime("%Y-%m-%d");
   }
   if (!isset($_GET["type"])) {
      $_GET["type"] = "week";
   }

   Planning::showSelectionForm($_GET['type'],$_GET['date'],$_GET["usertype"],$_GET["uID"],
                               $_GET["gID"]);

   Planning::show($_GET['uID'],$_GET['gID'],$_GET["date"],$_GET["type"]);

   commonFooter();
}

?>