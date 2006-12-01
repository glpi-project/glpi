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

include ("_relpos.php");

$NEEDED_ITEMS=array("setup");
include ($phproot . "/inc/includes.php");

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

	logEvent(0, "dropdown", 5, "setup", $_SESSION["glpiname"]." ".$lang["log"][20]);
	glpi_header($_SERVER['PHP_SELF']."?which=$which&value2=$value2&tomove=$tomove&where=$where&type=$type");
}else if (isset($_POST["move"])) {
	moveTreeUnder($_POST["tablename"],$_POST["value_to_move"],$_POST["value_where"]);
	logEvent(0, "dropdown", 5, "setup", $_SESSION["glpiname"]."".$lang["log"][21]);
	glpi_header($_SERVER['PHP_SELF']."?which=$which&value2=$value2&tomove=$tomove&where=$where&type=$type");
}else if (isset($_POST["add"])) {
	addDropdown($_POST);
	logEvent(0, "dropdown", 5, "setup", $_SESSION["glpiname"]." ".$lang["log"][20]);
	glpi_header($_SERVER['PHP_SELF']."?which=$which&value2=$value2&tomove=$tomove&where=$where&type=$type");
} else if (isset($_POST["delete"])) {
	if(!dropdownUsed($_POST["tablename"], $_POST["ID"]) && empty($_POST["forcedelete"])) {
		if (ereg("popup",$_SERVER['PHP_SELF']))
			popHeader($lang["title"][2],$_SERVER['PHP_SELF']);
		else 	
			commonHeader($lang["title"][2],$_SERVER['PHP_SELF']);
		showDeleteConfirmForm($_SERVER['PHP_SELF'],$_POST["tablename"], $_POST["ID"]);
		if (ereg("popup",$_SERVER['PHP_SELF']))
			popFooter();
		else 
			commonFooter();
	} else {
		deleteDropdown($_POST);
		logEvent(0, "dropdown", 4, "setup", $_SESSION["glpiname"]." ".$lang["log"][22]);
		glpi_header($_SERVER['PHP_SELF']."?which=$which");
	}

} else if (isset($_POST["update"])) {
	updateDropdown($_POST);
	logEvent(0, "dropdown", 4, "setup", $_SESSION["glpiname"]." ".$lang["log"][21]);
	glpi_header($_SERVER['PHP_SELF']."?which=$which&ID=$ID");
} else if (isset($_POST["replace"])) {
	replaceDropDropDown($_POST);
	logEvent(0, "dropdown", 4, "setup", $_SESSION["glpiname"]." ".$lang["log"][21]);
	glpi_header($_SERVER['PHP_SELF']."?which=$which");
}
else {
	if (ereg("popup",$_SERVER['PHP_SELF']))
		popHeader($lang["title"][2],$_SERVER['PHP_SELF']);
	else 
		commonHeader($lang["title"][2],$_SERVER['PHP_SELF']);

	$optgroup=array(
			$lang["setup"][139]=>array(
				"locations"=>$lang["common"][15],		
				"state"=>$lang["setup"][83],
				),

			$lang["setup"][140]=>array(
				"computers"=>$lang["setup"][4],
				"networking"=>$lang["setup"][42],		
				"printers"=>$lang["setup"][43],		
				"monitors"=>$lang["setup"][44],		
				"peripherals"=>$lang["setup"][69],
				"phones"=>$lang["setup"][504],
				"cartridge_type"=>$lang["setup"][84],
				"consumable_type"=>$lang["setup"][92],
				"contract_type"=>$lang["setup"][85],
				"contact_type"=>$lang["setup"][82],	
				"ram_type"=>$lang["setup"][86],	
				"enttype"=>$lang["setup"][80],
				"interface"=>$lang["setup"][93],
				"case_type"=>$lang["setup"][45],
				"phone_power"=>$lang["setup"][505],
				),

			$lang["common"][22]=>array(
					"model"=>$lang["setup"][91],
					"model_networking"=>$lang["setup"][95],
					"model_printers"=>$lang["setup"][96],	
					"model_monitors"=>$lang["setup"][94],
					"model_peripherals"=>$lang["setup"][97],			
					"model_phones"=>$lang["setup"][503],

					),

			$lang["setup"][142]=>array(
					"budget"=>$lang["setup"][99],
					"rubdocs"=>$lang["setup"][81],	
					),

			$lang["setup"][143]=>array(
					"tracking_category"=>$lang["setup"][79],		
					),

			$lang["setup"][144]=>array(
					"kbcategories"=>$lang["setup"][78],	
					),

			$lang["setup"][145]=>array(
					"os"=>$lang["setup"][5],	
					"os_version"=>$lang["setup"][500],
					"os_sp"=>$lang["setup"][501],			
					"auto_update"=>$lang["setup"][98],			
					),

			$lang["setup"][146]=>array(
					"iface"=>$lang["setup"][9],		
					"firmware"=>$lang["setup"][71],
					"netpoint"=>$lang["setup"][73],
					"domain"=>$lang["setup"][89],
					"network"=>$lang["setup"][88],
					"vlan"=>$lang["setup"][90],	
					),

			); //end $opt













	//	asort($dp);

	echo "<div align='center'><form method='get' action=\"".$_SERVER['PHP_SELF']."\">";
	echo "<table class='tab_cadre' cellpadding='5'><tr><th colspan='2'>";
	echo $lang["setup"][72].": </th></tr><tr class='tab_bg_1'><td><select name='which'>";

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
	echo "<td><input type='submit' value=\"".$lang["buttons"][2]."\" class='submit' ></td></tr>";
	echo "</table></form></div>";

	switch ($which){
		case "tracking_category" :
			showFormTreeDown($_SERVER['PHP_SELF'],"tracking_category",$lang["setup"][79],$ID);
		break;

		case "kbcategories" :
			showFormTreeDown($_SERVER['PHP_SELF'],"kbcategories",$lang["setup"][78],$ID,$value2,$where,$tomove,$type);
		break;
		case "locations" :
			showFormTreeDown($_SERVER['PHP_SELF'],"locations",$lang["common"][15],$ID,$value2,$where,$tomove,$type);
		break;
		case "computers" :
			showFormTypeDown($_SERVER['PHP_SELF'],"computers",$lang["setup"][4],$ID);
		break;
		case "networking" :
			showFormTypeDown($_SERVER['PHP_SELF'],"networking",$lang["setup"][42],$ID);
		break;
		case "printers" :
			showFormTypeDown($_SERVER['PHP_SELF'],"printers",$lang["setup"][43],$ID);
		break;
		case "monitors" :
			showFormTypeDown($_SERVER['PHP_SELF'],"monitors",$lang["setup"][44],$ID);
		break;
		case "peripherals" :
			showFormTypeDown($_SERVER['PHP_SELF'],"peripherals",$lang["setup"][69],$ID);
		break;
		case "phones" :
			showFormTypeDown($_SERVER['PHP_SELF'],"phones",$lang["setup"][504],$ID);
		break;
		case "os" :
			showFormDropDown($_SERVER['PHP_SELF'],"os",$lang["setup"][5],$ID);
		break;
		case "os_version" :
			showFormDropDown($_SERVER['PHP_SELF'],"os_version",$lang["setup"][500],$ID);
		break;
		case "os_sp" :
			showFormDropDown($_SERVER['PHP_SELF'],"os_sp",$lang["setup"][501],$ID);
		break;
		case "enttype" :
			showFormDropDown($_SERVER['PHP_SELF'],"enttype",$lang["setup"][80],$ID);
		break;

		case "iface" :
			showFormDropDown($_SERVER['PHP_SELF'],"iface",$lang["setup"][9],$ID);
		break;
		case "firmware" :
			showFormDropDown($_SERVER['PHP_SELF'],"firmware",$lang["setup"][71],$ID);
		break;
		case "netpoint" : 
			showFormDropDown($_SERVER['PHP_SELF'],"netpoint",$lang["setup"][73],$ID,$value2);
		break;
		case "model" : 
			showFormDropDown($_SERVER['PHP_SELF'],"model",$lang["setup"][91],$ID,$value2);
		break;
		case "model_printers" : 
			showFormDropDown($_SERVER['PHP_SELF'],"model_printers",$lang["setup"][96],$ID,$value2);
		break;
		case "model_monitors" : 
			showFormDropDown($_SERVER['PHP_SELF'],"model_monitors",$lang["setup"][94],$ID,$value2);
		break;
		case "model_networking" : 
			showFormDropDown($_SERVER['PHP_SELF'],"model_networking",$lang["setup"][95],$ID,$value2);
		break;
		case "model_peripherals" : 
			showFormDropDown($_SERVER['PHP_SELF'],"model_peripherals",$lang["setup"][97],$ID,$value2);
		break;
		case "model_phones" : 
			showFormDropDown($_SERVER['PHP_SELF'],"model_phones",$lang["setup"][503],$ID,$value2);
		break;
		case "rubdocs" : 
			showFormDropDown($_SERVER['PHP_SELF'],"rubdocs",$lang["setup"][81],$ID,$value2);
		break;
		case "contact_type" : 
			showFormDropDown($_SERVER['PHP_SELF'],"contact_type",$lang["setup"][82],$ID,$value2);
		break;
		case "state" : 
			showFormDropDown($_SERVER['PHP_SELF'],"state",$lang["setup"][83],$ID,$value2);
		break;
		case "cartridge_type" : 
			showFormDropDown($_SERVER['PHP_SELF'],"cartridge_type",$lang["setup"][84],$ID,$value2);
		break;
		case "consumable_type" : 
			showFormDropDown($_SERVER['PHP_SELF'],"consumable_type",$lang["setup"][92],$ID,$value2);
		break;
		case "contract_type" : 
			showFormDropDown($_SERVER['PHP_SELF'],"contract_type",$lang["setup"][85],$ID,$value2);
		break;
		case "ram_type" : 
			showFormDropDown($_SERVER['PHP_SELF'],"ram_type",$lang["setup"][86],$ID,$value2);
		break;
		case "case_type" : 
			showFormDropDown($_SERVER['PHP_SELF'],"case_type",$lang["setup"][45],$ID,$value2);
		break;
		case "interface" : 
			showFormDropDown($_SERVER['PHP_SELF'],"interface",$lang["setup"][93],$ID,$value2);
		break;
		case "domain" : 
			showFormDropDown($_SERVER['PHP_SELF'],"domain",$lang["setup"][89],$ID,$value2);
		break;
		case "network" : 
			showFormDropDown($_SERVER['PHP_SELF'],"network",$lang["setup"][88],$ID,$value2);
		break;
		case "vlan" : 
			showFormDropDown($_SERVER['PHP_SELF'],"vlan",$lang["setup"][90],$ID,$value2);
		break;
		case "auto_update" : 
			showFormDropDown($_SERVER['PHP_SELF'],"auto_update",$lang["setup"][98],$ID,$value2);
		break;
		case "budget" : 
			showFormDropDown($_SERVER['PHP_SELF'],"budget",$lang["setup"][99],$ID,$value2);
		break;
		case "phone_power" : 
			showFormDropDown($_SERVER['PHP_SELF'],"phone_power",$lang["setup"][505],$ID,$value2);
		break;

		default : break;
	}

	if (ereg("popup",$_SERVER['PHP_SELF']))
		popFooter();
	else 
		commonFooter();
}


?>
