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

checkRight("ocsng","w");

commonHeader($LANG['ocsng'][0],$_SERVER['PHP_SELF'],"utils","ocsng");

$display_list = true;

if (isset($_SESSION["ocs_update"]['computers'])) {
   if ($count = count($_SESSION["ocs_update"]['computers'])) {
      $percent = min(100,
                     round(100*($_SESSION["ocs_update_count"]-$count)/$_SESSION["ocs_update_count"],
                           0));


      $key = array_pop($_SESSION["ocs_update"]['computers']);
      $action = OcsServer::updateComputer($key, $_SESSION["ocsservers_id"], 2);
      OcsServer::manageImportStatistics($_SESSION["ocs_update"]['statistics'],
                                        $action['status']);
      OcsServer::showStatistics($_SESSION["ocs_update"]['statistics']);
      displayProgressBar(400, $percent);

      glpi_header($_SERVER['PHP_SELF']);

   } else {
      OcsServer::showStatistics($_SESSION["ocs_update"]['statistics'],true);
      unset($_SESSION["ocs_update"]);
      $display_list = false;
      echo "<div class='center b'><br>";
      echo "<a href='".$_SERVER['PHP_SELF']."'>".$LANG['buttons'][13]."</a></div>";
   }
}

if (!isset($_POST["update_ok"])) {
   if (!isset($_GET['check'])) {
      $_GET['check'] = 'all';
   }
   if (!isset($_GET['start'])) {
      $_GET['start'] = 0;
   }
   OcsServer::manageDeleted($_SESSION["ocsservers_id"]);
   if ($display_list) {
      OcsServer::showComputersToUpdate($_SESSION["ocsservers_id"], $_GET['check'], $_GET['start']);
   }
} else {
   if (count($_POST['toupdate']) >0) {
      $_SESSION["ocs_update_count"] = 0;

      foreach ($_POST['toupdate'] as $key => $val) {
         if ($val == "on") {
            $_SESSION["ocs_update"]['computers'][] = $key;
            $_SESSION["ocs_update_count"]++;
         }
      }
   }
   glpi_header($_SERVER['PHP_SELF']);
}

commonFooter();

?>