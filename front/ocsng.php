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
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

Session::checkSeveralRightsOr(array('ocsng'        => 'r',
                                    'clean_ocsng'  => 'r'));

Html::header(__('OCS Inventory NG'), $_SERVER['PHP_SELF'], "utils","ocsng");
if (isset($_SESSION["ocs_import"])) {
   unset ($_SESSION["ocs_import"]);
}
if (isset($_SESSION["ocs_link"])) {
   unset ($_SESSION["ocs_link"]);
}
if (isset($_SESSION["ocs_update"])) {
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
      $name = $datas["name"];
   }
   echo "<div class='center'>";
   echo "<img src='" . $CFG_GLPI["root_doc"] . "/pics/logoOcs.png' alt='" .
         __s('OCS Inventory NG') . "' title=\"" . __s('OCS Inventory NG') . "\" ></td>";
   echo "</div>";

   echo "<div class='center'><table class='tab_cadre'>";
   echo "<tr><th>" . __('OCSNG server: ') . " " . $name . "</th></tr>";

   if (Session::haveRight('ocsng','w')) {
      echo "<tr class='tab_bg_1'><td class='center b'><a href='ocsng.import.php'>".
            __('Import new computers')."</a></td></tr>";

      echo "<tr class='tab_bg_1'><td class='center b'><a href='ocsng.sync.php'>".
            __('Synchronize computers already imported')."</a></td></tr>";

      echo "<tr class='tab_bg_1'><td class='center b'><a href='ocsng.link.php'>".
            __('Link new OCS computers to existing GLPI computers')."</a></td></tr>";
   }

   if (Session::haveRight('clean_ocsng','r')) {
      echo "<tr class='tab_bg_1'><td class='center b'><a href='ocsng.clean.php'>".
            __('Clean links between GLPI and OCSNG')."</a></td> </tr>";
   }

   echo "</table></div>";

   OcsServer::manageDeleted($_SESSION["ocsservers_id"]);

} else {
   OcsServer::showFormServerChoice();
}
Html::footer();
?>