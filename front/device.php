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


$NEEDED_ITEMS=array("device","enterprise");
define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

checkRight("device","w");

commonHeader($LANG['title'][30],$_SERVER['PHP_SELF'],"config","device");

if (!isset($_GET['devicetype'])) {
   $_GET['devicetype'] = "0";
}
if (!isset($_GET['name'])) {
   $_GET['name'] = '';
}
if (!empty($_GET["devicetype"])) {
   titleDevices($_GET["devicetype"]);
}

echo "<form method='get' action=\"".$CFG_GLPI["root_doc"]."/front/device.php\">";
echo "<table class='tab_cadre_fixe'>";
echo "<tr class='tab_bg_1'><td><strong>".$LANG['devices'][17]."</strong>&nbsp;: <select name='devicetype'>";

$dp=getDictDeviceLabel();

foreach ($dp as $key=>$val) {
   $sel="";
   if ($_GET["devicetype"]==$key) {
      $sel="selected";
   }
   echo "<option value='$key' $sel>".$val."</option>";	
}
echo "</select></td>";
echo "<td>".$LANG['common'][16]."&nbsp;: <input  type='text' size='20' name='name' value='".$_GET['name']."'></td>";
echo "<td class='tab_bg_2'><input type='submit' value=\"".$LANG['buttons'][0]."\" class='submit' ></td></tr>";
echo "</table></form>";

if (!empty($_GET["devicetype"])) {
   showDevicesList($_GET["devicetype"],$_SERVER['PHP_SELF']);
}

commonFooter();
?>
