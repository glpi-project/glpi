<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.

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



$NEEDED_ITEMS=array("setup");

if(!defined('GLPI_ROOT')){
	define('GLPI_ROOT', '..');
}
include (GLPI_ROOT . "/inc/includes.php");

checkRight("dropdown","w");

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

	for ($i=$_POST["from"];$i<=$_POST["to"];$i++){
		$_POST["value"]=$_POST["before"].$i.$_POST["after"];
		addDropdown($_POST);
	}

	// logEvent ($item, $itemtype, $level, $service, $event)

	logEvent(0, "dropdown", 5, "setup", $_SESSION["glpiname"]." ".$LANG["log"][20]);
	glpi_header($_SERVER['PHP_SELF']."?which=$which&value2=$value2&tomove=$tomove&where=$where&type=$type");
}else if (isset($_POST["move"])) {
	moveTreeUnder($_POST["tablename"],$_POST["value_to_move"],$_POST["value_where"]);
	logEvent(0, "dropdown", 5, "setup", $_SESSION["glpiname"]."".$LANG["log"][21]);
	glpi_header($_SERVER['PHP_SELF']."?which=$which&value2=$value2&tomove=$tomove&where=$where&type=$type");
}else if (isset($_POST["add"])) {
	addDropdown($_POST);
	logEvent(0, "dropdown", 5, "setup", $_SESSION["glpiname"]." ".$LANG["log"][20]);
	glpi_header($_SERVER['PHP_SELF']."?which=$which&value2=$value2&tomove=$tomove&where=$where&type=$type");
} else if (isset($_POST["delete"])) {
	if(!dropdownUsed($_POST["tablename"], $_POST["ID"]) && empty($_POST["forcedelete"])) {
		if (ereg("popup",$_SERVER['PHP_SELF']))
			popHeader($LANG["title"][2],$_SERVER["PHP_SELF"]);
		else 	
			commonHeader($LANG["title"][2],$_SERVER["PHP_SELF"]);
		showDeleteConfirmForm($_SERVER["PHP_SELF"],$_POST["tablename"], $_POST["ID"]);
		if (ereg("popup",$_SERVER['PHP_SELF']))
			popFooter();
		else 
			commonFooter();
	} else {
		deleteDropdown($_POST);
		logEvent(0, "dropdown", 4, "setup", $_SESSION["glpiname"]." ".$LANG["log"][22]);
		glpi_header($_SERVER['PHP_SELF']."?which=$which");
	}

} else if (isset($_POST["update"])) {
	updateDropdown($_POST);
	logEvent(0, "dropdown", 4, "setup", $_SESSION["glpiname"]." ".$LANG["log"][21]);
	glpi_header($_SERVER['PHP_SELF']."?which=$which&ID=$ID");
} else if (isset($_POST["replace"])) {
	replaceDropDropDown($_POST);
	logEvent(0, "dropdown", 4, "setup", $_SESSION["glpiname"]." ".$LANG["log"][21]);
	glpi_header($_SERVER['PHP_SELF']."?which=$which");
}
else {
	if (ereg("popup",$_SERVER['PHP_SELF']))
		popHeader($LANG["title"][2],$_SERVER["PHP_SELF"]);
	else 
		commonHeader($LANG["title"][2],$_SERVER["PHP_SELF"]);

	$optgroup=array(
			$LANG["setup"][139]=>array(
				"locations"=>$LANG["common"][15],		
				"state"=>$LANG["setup"][83],
				),

			$LANG["setup"][140]=>array(
				"computers"=>$LANG["setup"][4],
				"networking"=>$LANG["setup"][42],		
				"printers"=>$LANG["setup"][43],		
				"monitors"=>$LANG["setup"][44],		
				"peripherals"=>$LANG["setup"][69],
				"phones"=>$LANG["setup"][504],
				"cartridge_type"=>$LANG["setup"][84],
				"consumable_type"=>$LANG["setup"][92],
				"contract_type"=>$LANG["setup"][85],
				"contact_type"=>$LANG["setup"][82],	
				"ram_type"=>$LANG["setup"][86],	
				"enttype"=>$LANG["setup"][80],
				"interface"=>$LANG["setup"][93],
				"case_type"=>$LANG["setup"][45],
				"phone_power"=>$LANG["setup"][505],
				),

			$LANG["common"][22]=>array(
					"model"=>$LANG["setup"][91],
					"model_networking"=>$LANG["setup"][95],
					"model_printers"=>$LANG["setup"][96],	
					"model_monitors"=>$LANG["setup"][94],
					"model_peripherals"=>$LANG["setup"][97],			
					"model_phones"=>$LANG["setup"][503],

					),

			$LANG["setup"][142]=>array(
					"budget"=>$LANG["setup"][99],
					"rubdocs"=>$LANG["setup"][81],	
					),

			$LANG["setup"][143]=>array(
					"tracking_category"=>$LANG["setup"][79],		
					),

			$LANG["setup"][144]=>array(
					"kbcategories"=>$LANG["setup"][78],	
					),

			$LANG["setup"][145]=>array(
					"os"=>$LANG["setup"][5],	
					"os_version"=>$LANG["setup"][500],
					"os_sp"=>$LANG["setup"][501],			
					"auto_update"=>$LANG["setup"][98],			
					),

			$LANG["setup"][146]=>array(
					"iface"=>$LANG["setup"][9],		
					"firmware"=>$LANG["setup"][71],
					"netpoint"=>$LANG["setup"][73],
					"domain"=>$LANG["setup"][89],
					"network"=>$LANG["setup"][88],
					"vlan"=>$LANG["setup"][90],	
					),

			); //end $opt













	//	asort($dp);

	echo "<div align='center'><form method='get' action=\"".$_SERVER['PHP_SELF']."\">";
	echo "<table class='tab_cadre' cellpadding='5'><tr><th colspan='2'>";
	echo $LANG["setup"][72].": </th></tr><tr class='tab_bg_1'><td><select name='which'>";

	foreach($optgroup as $label=>$dp){

		echo "<optgroup label=\"$label\">";

		foreach ($dp as $key => $val){
			$sel="";
			if ($which==$key) $sel="selected";
			echo "<option value='$key' $sel>".$val."</option>";	
		}
		echo "</optgroup>";
	}
	echo "</select></td>";
	echo "<td><input type='submit' value=\"".$LANG["buttons"][2]."\" class='submit' ></td></tr>";
	echo "</table></form></div>";

	switch ($which){
		case "tracking_category" :
			showFormTreeDown($_SERVER["PHP_SELF"],"tracking_category",$LANG["setup"][79],$ID);
		break;

		case "kbcategories" :
			showFormTreeDown($_SERVER["PHP_SELF"],"kbcategories",$LANG["setup"][78],$ID,$value2,$where,$tomove,$type);
		break;
		case "locations" :
			showFormTreeDown($_SERVER["PHP_SELF"],"locations",$LANG["common"][15],$ID,$value2,$where,$tomove,$type);
		break;
		case "computers" :
			showFormTypeDown($_SERVER["PHP_SELF"],"computers",$LANG["setup"][4],$ID);
		break;
		case "networking" :
			showFormTypeDown($_SERVER["PHP_SELF"],"networking",$LANG["setup"][42],$ID);
		break;
		case "printers" :
			showFormTypeDown($_SERVER["PHP_SELF"],"printers",$LANG["setup"][43],$ID);
		break;
		case "monitors" :
			showFormTypeDown($_SERVER["PHP_SELF"],"monitors",$LANG["setup"][44],$ID);
		break;
		case "peripherals" :
			showFormTypeDown($_SERVER["PHP_SELF"],"peripherals",$LANG["setup"][69],$ID);
		break;
		case "phones" :
			showFormTypeDown($_SERVER["PHP_SELF"],"phones",$LANG["setup"][504],$ID);
		break;
		case "os" :
			showFormDropDown($_SERVER["PHP_SELF"],"os",$LANG["setup"][5],$ID);
		break;
		case "os_version" :
			showFormDropDown($_SERVER["PHP_SELF"],"os_version",$LANG["setup"][500],$ID);
		break;
		case "os_sp" :
			showFormDropDown($_SERVER["PHP_SELF"],"os_sp",$LANG["setup"][501],$ID);
		break;
		case "enttype" :
			showFormDropDown($_SERVER["PHP_SELF"],"enttype",$LANG["setup"][80],$ID);
		break;

		case "iface" :
			showFormDropDown($_SERVER["PHP_SELF"],"iface",$LANG["setup"][9],$ID);
		break;
		case "firmware" :
			showFormDropDown($_SERVER["PHP_SELF"],"firmware",$LANG["setup"][71],$ID);
		break;
		case "netpoint" : 
			showFormDropDown($_SERVER["PHP_SELF"],"netpoint",$LANG["setup"][73],$ID,$value2);
		break;
		case "model" : 
			showFormDropDown($_SERVER["PHP_SELF"],"model",$LANG["setup"][91],$ID,$value2);
		break;
		case "model_printers" : 
			showFormDropDown($_SERVER["PHP_SELF"],"model_printers",$LANG["setup"][96],$ID,$value2);
		break;
		case "model_monitors" : 
			showFormDropDown($_SERVER["PHP_SELF"],"model_monitors",$LANG["setup"][94],$ID,$value2);
		break;
		case "model_networking" : 
			showFormDropDown($_SERVER["PHP_SELF"],"model_networking",$LANG["setup"][95],$ID,$value2);
		break;
		case "model_peripherals" : 
			showFormDropDown($_SERVER["PHP_SELF"],"model_peripherals",$LANG["setup"][97],$ID,$value2);
		break;
		case "model_phones" : 
			showFormDropDown($_SERVER["PHP_SELF"],"model_phones",$LANG["setup"][503],$ID,$value2);
		break;
		case "rubdocs" : 
			showFormDropDown($_SERVER["PHP_SELF"],"rubdocs",$LANG["setup"][81],$ID,$value2);
		break;
		case "contact_type" : 
			showFormDropDown($_SERVER["PHP_SELF"],"contact_type",$LANG["setup"][82],$ID,$value2);
		break;
		case "state" : 
			showFormDropDown($_SERVER["PHP_SELF"],"state",$LANG["setup"][83],$ID,$value2);
		break;
		case "cartridge_type" : 
			showFormDropDown($_SERVER["PHP_SELF"],"cartridge_type",$LANG["setup"][84],$ID,$value2);
		break;
		case "consumable_type" : 
			showFormDropDown($_SERVER["PHP_SELF"],"consumable_type",$LANG["setup"][92],$ID,$value2);
		break;
		case "contract_type" : 
			showFormDropDown($_SERVER["PHP_SELF"],"contract_type",$LANG["setup"][85],$ID,$value2);
		break;
		case "ram_type" : 
			showFormDropDown($_SERVER["PHP_SELF"],"ram_type",$LANG["setup"][86],$ID,$value2);
		break;
		case "case_type" : 
			showFormDropDown($_SERVER["PHP_SELF"],"case_type",$LANG["setup"][45],$ID,$value2);
		break;
		case "interface" : 
			showFormDropDown($_SERVER["PHP_SELF"],"interface",$LANG["setup"][93],$ID,$value2);
		break;
		case "domain" : 
			showFormDropDown($_SERVER["PHP_SELF"],"domain",$LANG["setup"][89],$ID,$value2);
		break;
		case "network" : 
			showFormDropDown($_SERVER["PHP_SELF"],"network",$LANG["setup"][88],$ID,$value2);
		break;
		case "vlan" : 
			showFormDropDown($_SERVER["PHP_SELF"],"vlan",$LANG["setup"][90],$ID,$value2);
		break;
		case "auto_update" : 
			showFormDropDown($_SERVER["PHP_SELF"],"auto_update",$LANG["setup"][98],$ID,$value2);
		break;
		case "budget" : 
			showFormDropDown($_SERVER["PHP_SELF"],"budget",$LANG["setup"][99],$ID,$value2);
		break;
		case "phone_power" : 
			showFormDropDown($_SERVER["PHP_SELF"],"phone_power",$LANG["setup"][505],$ID,$value2);
		break;

		default : break;
	}

	if (ereg("popup",$_SERVER['PHP_SELF']))
		popFooter();
	else 
		commonFooter();
}


?>
