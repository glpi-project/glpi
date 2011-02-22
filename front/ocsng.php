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

checkSeveralRightsOr(array('ocsng' => 'r',
                           'clean_ocsng'      => 'r'));

commonHeader($LANG['ocsng'][0], $_SERVER['PHP_SELF'], "utils","ocsng");
if (isset ($_SESSION["ocs_import"])) {
   unset ($_SESSION["ocs_import"]);
}
if (isset ($_SESSION["ocs_link"])) {
   unset ($_SESSION["ocs_link"]);
}
if (isset ($_SESSION["ocs_update"])) {
   unset ($_SESSION["ocs_update"]);
}

if (isset($_GET["ocsservers_id"]) && $_GET["ocsservers_id"]) {
   $name = "";
   if (isset($_GET["ocsservers_id"])) {
      $_SESSION["ocsservers_id"] = $_GET["ocsservers_id"];
   }
   $sql = "SELECT `name`
           FROM `glpi_ocsservers`
           WHERE `id` = '".$_SESSION["ocsservers_id"]."'";
   $result = $DB->query($sql);

   if ($DB->numrows($result) > 0) {
      $datas = $DB->fetch_array($result);
      $name = " : " . $datas["name"];
   }
   echo "<div class='center'>";
   echo "<img src='" . $CFG_GLPI["root_doc"] . "/pics/logoOcs.png' alt='" .
         $LANG['ocsng'][0] . "' title=\"" . $LANG['ocsng'][0] . "\" ></td>";
   echo "</div>";

   echo "<div class='center'><table class='tab_cadre'>";
   echo "<tr><th>" . $LANG['ocsng'][0] . " " . $name . "</th></tr>";

   if (haveRight('ocsng','w')) {

      echo "<tr class='tab_bg_1'><td class='center b'><a href='ocsng.import.php'>" . $LANG['ocsng'][2] .
            "</a></td></tr>";

      echo "<tr class='tab_bg_1'><td class='center b'><a href='ocsng.sync.php'>" . $LANG['ocsng'][1] .
            "</a></td></tr>";

      echo "<tr class='tab_bg_1'><td class='center b'><a href='ocsng.link.php'>" . $LANG['ocsng'][4] .
            "</a></td></tr>";
   }

   if (haveRight('clean_ocsng','r')) {
      echo "<tr class='tab_bg_1'><td class='center b'><a href='ocsng.clean.php'>" . $LANG['ocsng'][3] .
            "</a></td> </tr>";
   }

   echo "</table></div>";

   OcsServer::manageDeleted($_SESSION["ocsservers_id"]);

} else {
   OcsServer::showFormServerChoice();
}
commonFooter();

?>
