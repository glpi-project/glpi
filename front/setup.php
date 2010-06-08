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

checkCentralAccess();

commonHeader($LANG['common'][12],$_SERVER['PHP_SELF'],"config");

echo "<table class='tab_cadre'>";
echo "<tr class='tab_bg_1'><th colspan='2'>".$LANG['setup'][62]."</th></tr>";

$config = array();

if (haveRight("config","w")) {
   $config["config.form.php"]  = $LANG['setup'][70];
   $config["setup.notification.php"] = $LANG['setup'][704];
   $config["setup.auth.php"]   = $LANG['setup'][67];
   $config["mailcollector.php"]     = $LANG['Menu'][39];
   if ($CFG_GLPI["use_ocs_mode"] && haveRight("ocsng","w")) {
      $config["ocsserver.php"] = $LANG['setup'][134];
   }
}

$data = array();
if (haveRight("dropdown","r") || haveRight("entity_dropdown","r")) {
   $data["dropdown.php"] = $LANG['setup'][0];
}
if (haveRight("device","w")) {
   $data[$CFG_GLPI["root_doc"]."/front/device.php"] = $LANG['title'][30];
}
if (haveRight("link","r")) {
   $data[$CFG_GLPI["root_doc"]."/front/link.php"] = $LANG['title'][33];
}

echo "<tr class='tab_bg_1'>";
if (count($data) > 0) {
   echo "<td><table>";
   foreach ($data as $page => $title) {
      echo "<tr><td class='b'><a href='$page'>$title</a>&nbsp;&nbsp;</td></tr>\n";
   }
   echo "</table></td>";
}

if (count($config) > 0) {
   echo "<td><table>";
   foreach ($config as $page => $title) {
      echo "<tr><td class='b'><a href='$page'>$title</a>&nbsp;&nbsp;</td></tr>\n";
   }
   echo "</table></td>";
}

echo "</tr>";

if (isset($PLUGIN_HOOKS['config_page'])
    && is_array($PLUGIN_HOOKS['config_page'])
    && count($PLUGIN_HOOKS['config_page'])) {

   echo "<tr class='tab_bg_1'><td colspan='2' class='center b'>";
   echo "<a href='plugin.php'>".$LANG['common'][29]."</a></td></tr>";
}
if (haveRight("check_update","r")) {
   echo "<tr class='tab_bg_1'><td colspan='2' class='center b'>";
   echo "<a href='setup.version.php'>".$LANG['setup'][300]."</a></td></tr>";
}

echo "</table>";

commonFooter();

?>
