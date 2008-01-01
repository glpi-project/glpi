<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2008 by the INDEPNET Development Team.

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



$NEEDED_ITEMS=array("stat","tracking","user","setup","device");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

commonHeader($LANG["title"][11],$_SERVER['PHP_SELF'],"maintain","stat");

checkRight("statistic","1");

echo "<div align='center'><p><b><span class='icon_consol'>".$LANG["stats"][19]."</span></b></p>";

if (isset($_GET["date1"])) $_POST["date1"] = $_GET["date1"];
if (isset($_GET["date2"])) $_POST["date2"] = $_GET["date2"];

if(empty($_POST["date1"])&&empty($_POST["date2"])) {
	$year=date("Y")-1;
	$_POST["date1"]=date("Y-m-d",mktime(1,0,0,date("m"),date("d"),$year));

	$_POST["date2"]=date("Y-m-d");
}

if ($_POST["date1"]!=""&&$_POST["date2"]!=""&&strcmp($_POST["date2"],$_POST["date1"])<0){
	$tmp=$_POST["date1"];
	$_POST["date1"]=$_POST["date2"];
	$_POST["date2"]=$tmp;
}

if(!isset($_GET["start"])) $_GET["start"] = 0;

if (isset($_GET["dropdown"])) $_POST["dropdown"] = $_GET["dropdown"];
if(empty($_POST["dropdown"])) $_POST["dropdown"] = "glpi_type_computers";

echo "<form method=\"post\" name=\"form\" action=\"stat.location.php\">";

echo "<table class='tab_cadre'><tr class='tab_bg_2'><td rowspan='2'>";
echo "<select name=\"dropdown\">";

echo "<option value=\"glpi_type_computers\" ".($_POST["dropdown"]=="glpi_type_computers"?"selected":"").">".$LANG["common"][17]."</option>";
echo "<option value=\"glpi_dropdown_model\" ".($_POST["dropdown"]=="glpi_dropdown_model"?"selected":"").">".$LANG["common"][22]."</option>";

echo "<option value=\"glpi_dropdown_os\" ".($_POST["dropdown"]=="glpi_dropdown_os"?"selected":"").">".$LANG["computers"][9]."</option>";
echo "<option value=\"glpi_dropdown_locations\" ".($_POST["dropdown"]=="glpi_dropdown_locations"?"selected":"").">".$LANG["common"][15]."</option>";


for ($i=MOBOARD_DEVICE;$i<=POWER_DEVICE;$i++)
echo "<option value=\"$i\" ".($_POST["dropdown"]==$i?"selected":"").">".getDeviceTypeLabel($i)."</option>";
echo "</select></td>";


echo "<td align='right'>";
echo $LANG["search"][8]." :</td><td>";
showCalendarForm("form","date1",$_POST["date1"]);
echo "</td><td rowspan='2' align='center'><input type=\"submit\" class='button' name=\"submit\" Value=\"". $LANG["buttons"][7] ."\" /></td></tr>";
echo "<tr class='tab_bg_2'><td align='right'>".$LANG["search"][9]." :</td><td>";
showCalendarForm("form","date2",$_POST["date2"]);
echo "</td></tr>";
echo "</table></form></div>";

echo "<div align ='center'>";

if(is_dropdown_stat($_POST["dropdown"])) {

	$type="comp_champ";
	$field=getDropdownNameFromTableForStats($_POST["dropdown"]);

	$val=getStatsItems($_POST["date1"],$_POST["date2"],$_POST["dropdown"]);
	$params=array("type"=>$type,"field"=>$field,"table"=>$_POST["dropdown"],"date1"=>$_POST["date1"],"date2"=>$_POST["date2"],"start"=>$_GET["start"]);
	printPager($_GET['start'],count($val),$_SERVER['PHP_SELF'],"date1=".$_POST["date1"]."&amp;date2=".$_POST["date2"]."&amp;dropdown=".$_POST["dropdown"],STAT_TYPE,$params);

	displayStats($type,$field,$_POST["date1"],$_POST["date2"],$_GET['start'],$val,$field);


} else {

	$type="device";
	$field=$_POST["dropdown"];

	$val=getStatsItems($_POST["date1"],$_POST["date2"],$_POST["dropdown"]);
	$params=array("type"=>$type,"field"=>$field,"date1"=>$_POST["date1"],"date2"=>$_POST["date2"],"start"=>$_GET["start"]);
	printPager($_GET['start'],count($val),$_SERVER['PHP_SELF'],"date1=".$_POST["date1"]."&amp;date2=".$_POST["date2"]."&amp;dropdown=".$_POST["dropdown"],STAT_TYPE,$params);

	displayStats($type,$field,$_POST["date1"],$_POST["date2"],$_GET['start'],$val,$field);
}


echo "</div>"; 


commonFooter();
?>
