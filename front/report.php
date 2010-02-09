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

// Based on:
// IRMA, Information Resource-Management and Administration
// Christian Bauer
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

checkRight("reports","r");

commonHeader($LANG['Menu'][6],$_SERVER['PHP_SELF'],"utils","report");

echo "<table class='tab_cadre'>";
echo "<tr><th>".$LANG['reports'][0]."&nbsp;:</th></tr>";

// Report generation
// Default Report included
$report_list["default"]["name"] = $LANG['reports'][26];
$report_list["default"]["file"] = "report.default.php";

if (haveRight("contract","r")) {
   // Rapport ajoute par GLPI V0.2
   $report_list["Contrats"]["name"] = $LANG['reports'][27];
   $report_list["Contrats"]["file"] = "report.contract.php";
}
if (haveRight("infocom","r")) {
   $report_list["Par_annee"]["name"] = $LANG['reports'][28];
   $report_list["Par_annee"]["file"] = "report.year.php";
   $report_list["Infocoms"]["name"]  = $LANG['reports'][62];
   $report_list["Infocoms"]["file"]  = "report.infocom.php";
   $report_list["Infocoms2"]["name"] = $LANG['reports'][63];
   $report_list["Infocoms2"]["file"] = "report.infocom.conso.php";
}
if (haveRight("networking","r")) {
   $report_list["Rapport prises reseau"]["name"] = $LANG['reports'][33];
   $report_list["Rapport prises reseau"]["file"] = "report.networking.php";
}
if (haveRight("reservation_central","r")) {
   $report_list["reservation"]["name"] = $LANG['financial'][50];
   $report_list["reservation"]["file"] = "report.reservation.php";
}

$i = 0;
$count = count($report_list);
while ($data = each($report_list)) {
   $val = $data[0];
   $name = $report_list["$val"]["name"];
   $file = $report_list["$val"]["file"];
   echo  "<tr class='tab_bg_1'><td class='center b'><a href='$file'>$name</a></td></tr>";
   $i++;
}

$names = array();
if (isset($PLUGIN_HOOKS["reports"]) && is_array($PLUGIN_HOOKS["reports"])) {
   foreach ($PLUGIN_HOOKS["reports"] as $plug => $pages) {
      $function = "plugin_version_$plug";
      $plugname = $function();
      if (is_array($pages) && count($pages)) {
         foreach ($pages as $page => $name) {
            $names[$plug.'/'.$page]=$plugname['name'].' - '.$name;
         }
      }
   }
   asort($names);
}

foreach ($names as $key => $val) {
   echo "<tr class='tab_bg_1'><td class='center'>";
   echo "<a href='".$CFG_GLPI["root_doc"]."/plugins/$key'><strong>".$val."</strong></a></td></tr>";
}

echo "</table></div>";

commonFooter();

?>
