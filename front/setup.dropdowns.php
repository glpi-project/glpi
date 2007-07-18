<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2007 by the INDEPNET Development Team.

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



$NEEDED_ITEMS=array("setup","ocsng");

if(!defined('GLPI_ROOT')){
	define('GLPI_ROOT', '..');
}

include (GLPI_ROOT . "/inc/includes.php");

checkSeveralRightsOr(array("dropdown"=>"w","entity_dropdown"=>"w"));

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

if (isset($_POST["FK_entities"])) $FK_entities=$_POST["FK_entities"];
elseif (isset($_GET["FK_entities"])) $FK_entities=$_GET["FK_entities"];
else $FK_entities="";

if (isset($_POST["several_add"])) {

	for ($i=$_POST["from"];$i<=$_POST["to"];$i++){
		$_POST["value"]=$_POST["before"].$i.$_POST["after"];
		addDropdown($_POST);
	}

	logEvent(0, "dropdown", 5, "setup", $_SESSION["glpiname"]." ".$LANG["log"][20]);
	glpi_header($_SERVER['PHP_SELF']."?which=$which&value2=$value2&tomove=$tomove&where=$where&type=$type&FK_entities=$FK_entities");
}else if (isset($_POST["move"])) {
	logEvent(0, "dropdown", 5, "setup", $_SESSION["glpiname"]." ".$LANG["log"][21]." ".getDropdownName($_POST['tablename'],$_POST['value_to_move']));
	moveTreeUnder($_POST["tablename"],$_POST["value_to_move"],$_POST["value_where"]);
	glpi_header($_SERVER['PHP_SELF']."?which=$which&value2=$value2&tomove=$tomove&where=$where&type=$type&FK_entities=$FK_entities");
}else if (isset($_POST["add"])) {
	addDropdown($_POST);
	logEvent(0, "dropdown", 5, "setup", $_SESSION["glpiname"]." ".$LANG["log"][20]." ".$_POST["value"]);
	glpi_header($_SERVER['PHP_SELF']."?which=$which&value2=$value2&tomove=$tomove&where=$where&type=$type&FK_entities=$FK_entities");
} else if (isset($_POST["delete"])) {
	if(!dropdownUsed($_POST["tablename"], $_POST["ID"]) && empty($_POST["forcedelete"])) {
		if (!ereg("popup",$_SERVER['PHP_SELF'])){
			commonHeader($LANG["title"][2],$_SERVER['PHP_SELF'],"config","dropdowns");
		}
		showDeleteConfirmForm($_SERVER['PHP_SELF'],$_POST["tablename"], $_POST["ID"],$_POST["FK_entities"]);
		if (!ereg("popup",$_SERVER['PHP_SELF'])){
			commonFooter();
		}
	} else {
		logEvent(0, "dropdown", 4, "setup", $_SESSION["glpiname"]." ".$LANG["log"][22]." ".getDropdownName($_POST['which'],$_POST['ID']));
		deleteDropdown($_POST);
		glpi_header($_SERVER['PHP_SELF']."?which=$which&FK_entities=$FK_entities");
	}

} else if (isset($_POST["update"])) {
	updateDropdown($_POST);
	logEvent(0, "dropdown", 4, "setup", $_SESSION["glpiname"]." ".$LANG["log"][21]);
	glpi_header($_SERVER['PHP_SELF']."?which=$which&ID=$ID&FK_entities=$FK_entities");
} else if (isset($_POST["replace"])) {
	replaceDropDropDown($_POST);
	logEvent(0, "dropdown", 4, "setup", $_SESSION["glpiname"]." ".$LANG["log"][21]);
	glpi_header($_SERVER['PHP_SELF']."?which=$which&FK_entities=$FK_entities");
}
else {
	if (!ereg("popup",$_SERVER['PHP_SELF'])){
		commonHeader($LANG["title"][2],$_SERVER['PHP_SELF'],"config","dropdowns");
	}

	$optgroup=array(
			$LANG["setup"][139]=>array(
				"glpi_dropdown_locations"=>$LANG["common"][15],
				"glpi_dropdown_state"=>$LANG["setup"][83],
				"glpi_dropdown_manufacturer"=>$LANG["common"][5],
				),

			$LANG["setup"][140]=>array(
				"glpi_type_computers"=>$LANG["setup"][4],
				"glpi_type_networking"=>$LANG["setup"][42],
				"glpi_type_printers"=>$LANG["setup"][43],
				"glpi_type_monitors"=>$LANG["setup"][44],
				"glpi_type_peripherals"=>$LANG["setup"][69],
				"glpi_type_phones"=>$LANG["setup"][504],
				"glpi_dropdown_cartridge_type"=>$LANG["setup"][84],
				"glpi_dropdown_consumable_type"=>$LANG["setup"][92],
				"glpi_dropdown_contract_type"=>$LANG["setup"][85],
				"glpi_dropdown_contact_type"=>$LANG["setup"][82],	
				"glpi_dropdown_ram_type"=>$LANG["setup"][86],
				"glpi_dropdown_enttype"=>$LANG["setup"][80],
				"glpi_dropdown_interface"=>$LANG["setup"][93],
				"glpi_dropdown_case_type"=>$LANG["setup"][45],
				"glpi_dropdown_phone_power"=>$LANG["setup"][505],
				),

			$LANG["common"][22]=>array(
					"glpi_dropdown_model"=>$LANG["setup"][91],
					"glpi_dropdown_model_networking"=>$LANG["setup"][95],
					"glpi_dropdown_model_printers"=>$LANG["setup"][96],
					"glpi_dropdown_model_monitors"=>$LANG["setup"][94],
					"glpi_dropdown_model_peripherals"=>$LANG["setup"][97],
					"glpi_dropdown_model_phones"=>$LANG["setup"][503],

					),

			$LANG["setup"][142]=>array(
					"glpi_dropdown_budget"=>$LANG["setup"][99],
					"glpi_dropdown_rubdocs"=>$LANG["setup"][81],
					),

			$LANG["setup"][143]=>array(
					"glpi_dropdown_tracking_category"=>$LANG["setup"][79],
					),

			$LANG["setup"][144]=>array(
					"glpi_dropdown_kbcategories"=>$LANG["setup"][78],	
					),

			$LANG["setup"][145]=>array(
					"glpi_dropdown_os"=>$LANG["setup"][5],	
					"glpi_dropdown_os_version"=>$LANG["setup"][500],
					"glpi_dropdown_os_sp"=>$LANG["setup"][501],
					"glpi_dropdown_auto_update"=>$LANG["setup"][98],
					),

			$LANG["setup"][146]=>array(
					"glpi_dropdown_iface"=>$LANG["setup"][9],
					"glpi_dropdown_firmware"=>$LANG["setup"][71],
					"glpi_dropdown_netpoint"=>$LANG["setup"][73],
					"glpi_dropdown_domain"=>$LANG["setup"][89],
					"glpi_dropdown_network"=>$LANG["setup"][88],
					"glpi_dropdown_vlan"=>$LANG["setup"][90],	
					),
			
			$LANG["reports"][55]=>array(
			"glpi_dropdown_software_category"=>$LANG["rulesoftwarecategories"][5],
			)
			
			); //end $opt

	$plugdrop=getPluginsDropdowns();
	if (count($plugdrop)){
		$optgroup=array_merge($optgroup,$plugdrop);
	}
	if (!haveRight("dropdown","w")){
		foreach($optgroup as $label=>$dp){
			foreach ($dp as $key => $val){
				if (!in_array($key,$CFG_GLPI["specif_entities_tables"])){
					unset($optgroup[$label][$key]);
				}
				
			}
			if (count($optgroup[$label])==0){
				unset($optgroup[$label]);
			}
		}
	}

	if (!haveRight("entity_dropdown","w")){
		foreach($optgroup as $label=>$dp){
			foreach ($dp as $key => $val){
				if (in_array($key,$CFG_GLPI["specif_entities_tables"])){
					unset($optgroup[$label][$key]);
				}
				
			}
			if (count($optgroup[$label])==0){
				unset($optgroup[$label]);
			}
		}
	}

	if (!ereg("popup",$_SERVER['PHP_SELF'])){
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
	}

	if ($which){
		// Search title
		$title="";
		foreach ($optgroup as $key => $val){
			if (isset($val[$which])){
				$title=$val[$which];
			}
		}
		if (!empty($title))
		if (in_array($which,$CFG_GLPI["dropdowntree_tables"])){
			showFormTreeDown($_SERVER['PHP_SELF'],$which,$title,$ID,$value2,$where,$tomove,$type,$FK_entities);
		} else {
			showFormDropDown($_SERVER['PHP_SELF'],$which,$title,$ID,$value2,$FK_entities);
		}
	}

	if (!ereg("popup",$_SERVER['PHP_SELF'])){
		commonFooter();
	}
}


?>
