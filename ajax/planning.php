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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

$AJAX_INCLUDE=1;
$NEEDED_ITEMS=array('user');
define('GLPI_ROOT','..');
include (GLPI_ROOT."/inc/includes.php");
header("Content-Type: text/html; charset=UTF-8");
header_nocache();

checkCentralAccess();

$split=explode(":",$CFG_GLPI["planning_begin"]);
$global_begin=$split[0].":".$split[1];
$split=explode(":",$CFG_GLPI["planning_end"]);
$global_end=$split[0].":".$split[1];

if (isset($_POST["id"]) && $_POST["id"]>0) {
   echo "<input type='hidden' name='plan[id]' value='".$_POST["id"]."'>";
}

if (isset($_POST["begin"]) && !empty($_POST["begin"])) {
   $begin=$_POST["begin"];
} else {
   $begin=date("Y-m-d")." 12:00:00";
}

if (isset($_POST["end"]) && !empty($_POST["end"])) {
   $end=$_POST["end"];
} else {
   $end=date("Y-m-d")." 13:00:00";
}

$state=0;
if (isset($_POST["state"])) {
   $state=$_POST["state"];
}

echo "<table class='tab_cadre'>";
if (isset($_POST["users_id"]) && isset($_POST["entity"])) {
   echo "<tr class='tab_bg_2'><td>".$LANG['planning'][9]."&nbsp;:</td>";
   echo "<td class='center'>";
   User::dropdown("plan[users_id]",$_POST["users_id"],"own_ticket",-1,1,$_POST["entity"]);
   echo "</td></tr>\n";
}

echo "<tr class='tab_bg_2'><td>".$LANG['search'][8]."&nbsp;:&nbsp;</td><td>";
showDateTimeFormItem("plan[begin]",$begin,-1,false,true,'','',$global_begin,$global_end);
echo "</td></tr>\n";

echo "<tr class='tab_bg_2'><td>".$LANG['search'][9]."&nbsp;:</td><td>";
showDateTimeFormItem("plan[end]",$end,-1,false,true,'','',$global_begin,$global_end);
echo "</td></tr>\n";

echo "<tr class='tab_bg_2'><td>".$LANG['state'][0]."&nbsp;:</td>";
echo "<td class='center'>";
dropdownPlanningState("plan[state]",$state);
echo "</td></tr>";

echo "</table>\n";

ajaxFooter();

?>
