<?php
/*
 
  ----------------------------------------------------------------------
GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2004 by the INDEPNET Development Team.
 
 http://indepnet.net/   http://glpi.indepnet.org
 ----------------------------------------------------------------------
 Based on:
IRMA, Information Resource-Management and Administration
Christian Bauer 

 ----------------------------------------------------------------------
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
 ----------------------------------------------------------------------
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------


*/


include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_setup.php");

if(isset($_SERVER['HTTP_REFERER']))
$httpreferer=preg_replace("/which=\w*/","",$_SERVER['HTTP_REFERER']);

if (isset($_POST["which"]))$which=$_POST["which"];
elseif (isset($_GET["which"]))$which=$_GET["which"];
else $which="";

//echo $which."---";

if (isset($_POST["add"])) {
	checkAuthentication("admin");
	addDropdown($_POST);
	logEvent(0, "dropdowns", 5, "setup", $_SESSION["glpiname"]." added a value to a dropdown.");
	header("Location: $httpreferer?which=$which");
} else if (isset($_POST["delete"])) {
	checkAuthentication("admin");
	if(!dropdownUsed($_POST["tablename"], $_POST["ID"]) && empty($_POST["forcedelete"])) {
		commonHeader("Setup",$_SERVER["PHP_SELF"]);
		showDeleteConfirmForm($_SERVER["PHP_SELF"],$_POST["tablename"], $_POST["ID"]);
		commonFooter();
	} else {
		deleteDropdown($_POST);
		logEvent(0, "templates", 4, "inventory", $_SESSION["glpiname"]." deleted a dropdown value.");
		header("Location: $httpreferer?which=$which");
	}

} else if (isset($_POST["update"])) {
	checkAuthentication("admin");
	updateDropdown($_POST);
	logEvent(0, "templates", 4, "inventory", $_SESSION["glpiname"]." updated a dropdown value.");
	header("Location: $httpreferer?which=$which");
} else if (isset($_POST["replace"])) {
	checkAuthentication("admin");
	replaceDropDropDown($_POST);
	logEvent(0, "templates", 4, "inventory", $_SESSION["glpiname"]." replaced a dropdown value in each items.");
	header("Location: $httpreferer?which=$which");
}
 if (!isset($_POST["delete"])){
	checkAuthentication("normal");
	commonHeader("Setup",$_SERVER["PHP_SELF"]);
	$dp=array();
	$dp["locations"]=$lang["setup"][3];	
	$dp["computers"]=$lang["setup"][4];	
	$dp["networking"]=$lang["setup"][42];		
	$dp["printers"]=$lang["setup"][43];		
	$dp["monitors"]=$lang["setup"][44];		
	$dp["peripherals"]=$lang["setup"][69];		
	$dp["os"]=$lang["setup"][5];		
	$dp["ram"]=$lang["setup"][6];	
	$dp["processor"]=$lang["setup"][7];			
	$dp["moboard"]=$lang["setup"][45];		
	$dp["gfxcard"]=$lang["setup"][46];		
	$dp["sndcard"]=$lang["setup"][47];		
	$dp["hdtype"]=$lang["setup"][48];		
	$dp["network"]=$lang["setup"][8];		
	$dp["iface"]=$lang["setup"][9];		
	$dp["firmware"]=$lang["setup"][71];
	$dp["netpoint"]=$lang["setup"][73];		
	
//	asort($dp);
	
	echo "<div align='center'><form method='post' action=\"".$cfg_install["root"]."/setup/setup-dropdowns.php\">";
	echo "<table class='tab_cadre' cellpadding='5'><tr><th colspan='2'>";
	echo $lang["setup"][72].": </th></tr><tr class='tab_bg_1'><td><select name='which'>";

foreach ($dp as $key => $val){
$sel="";
if ($which==$key) $sel="selected";
echo "<option value='$key' $sel>".$val."</option>";
}
	echo "</select></td>";
	echo "<td><input type='submit' value=\"".$lang["buttons"][2]."\" class='submit' /></td></tr>";
	echo "</table></form></div>";
	switch ($which){
		case "locations" :
		showFormDropDown($_SERVER["PHP_SELF"],"locations",$lang["setup"][3]);
		break;
		case "computers" :
		showFormTypeDown($_SERVER["PHP_SELF"],"computers",$lang["setup"][4]);
		break;
		case "networking" :
		showFormTypeDown($_SERVER["PHP_SELF"],"networking",$lang["setup"][42]);
		break;
		case "printers" :
		showFormTypeDown($_SERVER["PHP_SELF"],"printers",$lang["setup"][43]);
		break;
		case "monitors" :
		showFormTypeDown($_SERVER["PHP_SELF"],"monitors",$lang["setup"][44]);
		break;
		case "peripherals" :
		showFormTypeDown($_SERVER["PHP_SELF"],"peripherals",$lang["setup"][69]);
		break;
		case "os" :
		showFormDropDown($_SERVER["PHP_SELF"],"os",$lang["setup"][5]);
		break;
		case "ram" :
		showFormDropDown($_SERVER["PHP_SELF"],"ram",$lang["setup"][6]);
		break;
		case "processor" :
		showFormDropDown($_SERVER["PHP_SELF"],"processor",$lang["setup"][7]);
		break;
		case "moboard" :
		showFormDropDown($_SERVER["PHP_SELF"],"moboard",$lang["setup"][45]);
		break;
		case "gfxcard" :
		showFormDropDown($_SERVER["PHP_SELF"],"gfxcard",$lang["setup"][46]);
		break;
		case "sndcard" :
		showFormDropDown($_SERVER["PHP_SELF"],"sndcard",$lang["setup"][47]);
		break;
		case "hdtype" :
		showFormDropDown($_SERVER["PHP_SELF"],"hdtype",$lang["setup"][48]);
		break;
		case "network" :
		showFormDropDown($_SERVER["PHP_SELF"],"network",$lang["setup"][8]);
		break;
		case "iface" :
		showFormDropDown($_SERVER["PHP_SELF"],"iface",$lang["setup"][9]);
		break;
		case "firmware" :
		showFormDropDown($_SERVER["PHP_SELF"],"firmware",$lang["setup"][71]);
		break;
		case "netpoint" : 
		showFormDropDown($_SERVER["PHP_SELF"],"netpoint",$lang["setup"][73]);
		break;
	default : break;
	}
	commonFooter();
}


?>
