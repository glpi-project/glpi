<?php
/*
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2005 by the INDEPNET Development Team.
 
 http://indepnet.net/   http://glpi.indepnet.org
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
 ------------------------------------------------------------------------
*/

// Based on:
// IRMA, Information Resource-Management and Administration
// Christian Bauer 
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_setup.php");

//if(isset($_SERVER['HTTP_REFERER']))
//$httpreferer=preg_replace("/\?which=\w*/","",$_SERVER['HTTP_REFERER']);
if (isset($_POST["which"]))$which=$_POST["which"];
elseif (isset($_GET["which"]))$which=$_GET["which"];
else $which="";

if (isset($_GET["where"]))$where=$_GET["where"];
else if (isset($_POST["value_where"]))$where=$_POST["value_where"];
else $where="";
if (isset($_GET["tomove"])) $tomove=$_GET["tomove"];
else if (isset($_POST["value_to_move"])) $tomove=$_POST["value_to_move"];
else $tomove="";
if (isset($_GET["value2"]))$value2=$_GET["value2"];
else if (isset($_POST["value2"]))$value2=$_POST["value2"];
else $value2="";
if (isset($_GET["type"]))$type=$_GET["type"];
else if (isset($_POST["type"]))$type=$_POST["type"];
else $type="";
// Selected Item
if (isset($_POST["ID"])) $ID=$_POST["ID"];
elseif (isset($_GET["ID"])) $ID=$_GET["ID"];
else $ID="";

if (isset($_POST["several_add"])) {
	checkAuthentication("admin");
	for ($i=$_POST["from"];$i<=$_POST["to"];$i++){
		$_POST["value"]=$_POST["before"].$i.$_POST["after"];
		addDropdown($_POST);
	}


	logEvent(0, "dropdowns", 5, "setup", $_SESSION["glpiname"]." added several values to a dropdown.");
	glpi_header($_SERVER['PHP_SELF']."?which=$which&value2=$value2&tomove=$tomove&where=$where&type=$type");
}else if (isset($_POST["move"])) {
	checkAuthentication("admin");
	moveTreeUnder($_POST["tablename"],$_POST["value_to_move"],$_POST["value_where"]);
	logEvent(0, "dropdowns", 5, "setup", $_SESSION["glpiname"]." moved a location.");
	glpi_header($_SERVER['PHP_SELF']."?which=$which&value2=$value2&tomove=$tomove&where=$where&type=$type");
}else if (isset($_POST["add"])) {
	checkAuthentication("admin");
	addDropdown($_POST);
	logEvent(0, "dropdowns", 5, "setup", $_SESSION["glpiname"]." added a value to a dropdown.");
	glpi_header($_SERVER['PHP_SELF']."?which=$which&value2=$value2&tomove=$tomove&where=$where&type=$type");
} else if (isset($_POST["delete"])) {
	checkAuthentication("admin");
	if(!dropdownUsed($_POST["tablename"], $_POST["ID"]) && empty($_POST["forcedelete"])) {
		commonHeader($lang["title"][2],$_SERVER["PHP_SELF"]);
		showDeleteConfirmForm($_SERVER["PHP_SELF"],$_POST["tablename"], $_POST["ID"]);
		commonFooter();
	} else {
		deleteDropdown($_POST);
		logEvent(0, "templates", 4, "inventory", $_SESSION["glpiname"]." deleted a dropdown value.");
		glpi_header($_SERVER['PHP_SELF']."?which=$which");
	}

} else if (isset($_POST["update"])) {
	checkAuthentication("admin");
	updateDropdown($_POST);
	logEvent(0, "templates", 4, "inventory", $_SESSION["glpiname"]." updated a dropdown value.");
	glpi_header($_SERVER['PHP_SELF']."?which=$which&ID=$ID");
} else if (isset($_POST["replace"])) {
	checkAuthentication("admin");
	replaceDropDropDown($_POST);
	logEvent(0, "templates", 4, "inventory", $_SESSION["glpiname"]." replaced a dropdown value in each items.");
	glpi_header($_SERVER['PHP_SELF']."?which=$which");
}
 else {
	checkAuthentication("normal");
	commonHeader($lang["title"][2],$_SERVER["PHP_SELF"]);

	$dp=array();
	$dp["locations"]=$lang["setup"][3];	
	$dp["kbcategories"]=$lang["setup"][78];	
	$dp["tracking_category"]=$lang["setup"][79];	
	$dp["computers"]=$lang["setup"][4];	
	$dp["networking"]=$lang["setup"][42];		
	$dp["printers"]=$lang["setup"][43];		
	$dp["monitors"]=$lang["setup"][44];		
	$dp["peripherals"]=$lang["setup"][69];		
	$dp["os"]=$lang["setup"][5];		
	$dp["iface"]=$lang["setup"][9];		
	$dp["firmware"]=$lang["setup"][71];
	$dp["netpoint"]=$lang["setup"][73];		
	$dp["enttype"]=$lang["setup"][80];
	$dp["rubdocs"]=$lang["setup"][81];
	$dp["contact_type"]=$lang["setup"][82];
	$dp["state"]=$lang["setup"][83];
	$dp["cartridge_type"]=$lang["setup"][84];
	$dp["contract_type"]=$lang["setup"][85];
	$dp["ram_type"]=$lang["setup"][86];
	
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
	echo "<td><input type='submit' value=\"".$lang["buttons"][2]."\" class='submit' ></td></tr>";
	echo "</table></form></div>";

	switch ($which){
		case "tracking_category" :
		showFormDropDown($_SERVER["PHP_SELF"],"tracking_category",$lang["setup"][79],$ID);
		break;
				
		case "kbcategories" :
		showFormTreeDown($_SERVER["PHP_SELF"],"kbcategories",$lang["setup"][78],$ID,$value2,$where,$tomove,$type);
		break;
		case "locations" :
		showFormTreeDown($_SERVER["PHP_SELF"],"locations",$lang["setup"][3],$ID,$value2,$where,$tomove,$type);
		break;
		case "computers" :
		showFormTypeDown($_SERVER["PHP_SELF"],"computers",$lang["setup"][4],$ID);
		break;
		case "networking" :
		showFormTypeDown($_SERVER["PHP_SELF"],"networking",$lang["setup"][42],$ID);
		break;
		case "printers" :
		showFormTypeDown($_SERVER["PHP_SELF"],"printers",$lang["setup"][43],$ID);
		break;
		case "monitors" :
		showFormTypeDown($_SERVER["PHP_SELF"],"monitors",$lang["setup"][44],$ID);
		break;
		case "peripherals" :
		showFormTypeDown($_SERVER["PHP_SELF"],"peripherals",$lang["setup"][69],$ID);
		break;
		case "os" :
		showFormDropDown($_SERVER["PHP_SELF"],"os",$lang["setup"][5],$ID);
		break;
		case "enttype" :
		showFormDropDown($_SERVER["PHP_SELF"],"enttype",$lang["setup"][80],$ID);
		break;

		case "iface" :
		showFormDropDown($_SERVER["PHP_SELF"],"iface",$lang["setup"][9],$ID);
		break;
		case "firmware" :
		showFormDropDown($_SERVER["PHP_SELF"],"firmware",$lang["setup"][71],$ID);
		break;
		case "netpoint" : 
		showFormDropDown($_SERVER["PHP_SELF"],"netpoint",$lang["setup"][73],$ID,$value2);
		break;
		case "rubdocs" : 
		showFormDropDown($_SERVER["PHP_SELF"],"rubdocs",$lang["setup"][81],$ID,$value2);
		break;
		case "contact_type" : 
		showFormDropDown($_SERVER["PHP_SELF"],"contact_type",$lang["setup"][82],$ID,$value2);
		break;
		case "state" : 
		showFormDropDown($_SERVER["PHP_SELF"],"state",$lang["setup"][83],$ID,$value2);
		break;
		case "cartridge_type" : 
		showFormDropDown($_SERVER["PHP_SELF"],"cartridge_type",$lang["setup"][84],$ID,$value2);
		break;
		case "contract_type" : 
		showFormDropDown($_SERVER["PHP_SELF"],"contract_type",$lang["setup"][85],$ID,$value2);
		break;
		case "ram_type" : 
		showFormDropDown($_SERVER["PHP_SELF"],"ram_type",$lang["setup"][86],$ID,$value2);
		break;
	default : break;
	}
	commonFooter();
}


?>
