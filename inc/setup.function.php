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


// FUNCTIONS Setup

function showFormTreeDown ($target,$name,$human,$ID,$value2='',$where='',$tomove='',$type='') {

	global $cfg_glpi, $lang, $HTMLRel;

	if (!haveRight("dropdown","w")) return false;

	echo "<div align='center'>&nbsp;\n";
	echo "<form method='post' action=\"$target\">";

	echo "<table class='tab_cadre_fixe'  cellpadding='1'>\n";
	echo "<tr><th colspan='3'>$human:</th></tr>";
	if (countElementsInTable("glpi_dropdown_".$name)>0){
		echo "<tr><td  align='center' valign='middle' class='tab_bg_1'>";
		echo "<input type='hidden' name='which' value='$name'>";


		$value=getTreeLeafValueName("glpi_dropdown_".$name,$ID,1);

		dropdownValue("glpi_dropdown_".$name, "ID",$ID,0);
		// on ajoute un input text pour entrer la valeur modifier
		echo "&nbsp;&nbsp<input type='image' class='calendrier' src=\"".$HTMLRel."pics/puce.gif\" alt='' title='' name='fillright' value='fillright'>&nbsp";


		echo "<input type='text' maxlength='100' size='20' name='value' value=\"".$value["name"]."\"><br>";
		echo "<textarea rows='2' cols='50' name='comments' title='".$lang["common"][25]."' >".$value["comments"]."</textarea>";

		echo "</td><td align='center' class='tab_bg_2' width='99'>";
		echo "<input type='hidden' name='tablename' value='glpi_dropdown_".$name."'>";
		//  on ajoute un bouton modifier
		echo "<input type='submit' name='update' value='".$lang["buttons"][14]."' class='submit'>";
		echo "</td><td align='center' class='tab_bg_2' width='99'>";
		//
		echo "<input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'>";
		echo "</td></tr></table></form>";

		echo "<form method='post' action=\"$target\">";

		echo "<input type='hidden' name='which' value='$name'>";
		echo "<table class='tab_cadre_fixe' cellpadding='1'>\n";

		echo "<tr><td align='center' class='tab_bg_1'>";

		dropdownValue("glpi_dropdown_".$name, "value_to_move",$tomove,0);
		echo "&nbsp;&nbsp;&nbsp;".$lang["setup"][75]." :&nbsp;&nbsp;&nbsp;";

		dropdownValue("glpi_dropdown_".$name, "value_where",$where,0);
		echo "</td><td align='center' colspan='2' class='tab_bg_2' width='202'>";
		echo "<input type='hidden' name='tablename' value='glpi_dropdown_".$name."' >";
		echo "<input type='submit' name='move' value=\"".$lang["buttons"][20]."\" class='submit'>";

		echo "</td></tr>";	

	}
		echo "</table></form>";	

	echo "<form action=\"$target\" method='post'>";
	echo "<input type='hidden' name='which' value='$name'>";

	echo "<table class='tab_cadre_fixe' cellpadding='1'>\n";
	echo "<tr><td  align='center'  class='tab_bg_1'>";
	echo "<input type='text' maxlength='100' size='15' name='value'>&nbsp;&nbsp;&nbsp;";


	if (countElementsInTable("glpi_dropdown_".$name)>0){
		echo "<select name='type'>";
		echo "<option value='under' ".($type=='under'?" selected ":"").">".$lang["setup"][75]."</option>";
		echo "<option value='same' ".($type=='same'?" selected ":"").">".$lang["setup"][76]."</option>";
		echo "</select>&nbsp;&nbsp;&nbsp;";
		;
		dropdownValue("glpi_dropdown_".$name, "value2",$value2,0);
	}		
	else echo "<input type='hidden' name='type' value='first'>";

	echo "<br><textarea rows='2' cols='50' name='comments' title='".$lang["common"][25]."' ></textarea>";

	echo "</td><td align='center' colspan='2' class='tab_bg_2'  width='202'>";
	echo "<input type='hidden' name='tablename' value='glpi_dropdown_".$name."' >";
	echo "<input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'>";
	echo "</td></tr>";



	echo "</table></form></div>";
}


function showFormDropDown ($target,$name,$human,$ID,$value2='') {

	global $db,$cfg_glpi, $lang, $HTMLRel;

	if (!haveRight("dropdown","w")) return false;

	echo "<div align='center'>&nbsp;";
	echo "<form method='post' action=\"$target\">";

	echo "<table class='tab_cadre_fixe' cellpadding='1'>";
	echo "<tr><th colspan='3'>$human:</th></tr>";
	if (countElementsInTable("glpi_dropdown_".$name)>0){
		echo "<tr><td class='tab_bg_1' align='center' valign='top'>";
		echo "<input type='hidden' name='which' value='$name'>";

		dropdownValue("glpi_dropdown_".$name, "ID",$ID,0);
		// on ajoute un input text pour entrer la valeur modifier
		echo "&nbsp;&nbsp;<input type='image' class='calendrier'  src=\"".$HTMLRel."pics/puce.gif\" alt='' title='' name='fillright' value='fillright'>&nbsp;";

		//        echo "<img src=\"".$HTMLRel."pics/puce.gif\" alt='' title=''>";
		if ($name != "netpoint"){
			if (!empty($ID)){
				$value=getDropdownName("glpi_dropdown_".$name,$ID,1);
			}
			else $value=array("name"=>"","comments"=>"");
		} else {$value="";$loc="";}

		if($name == "netpoint") {
			$query = "select * from glpi_dropdown_netpoint where ID = '". $ID ."'";
			$result = $db->query($query);
			$value=$loc=$comments="";
			if($db->numrows($result) == 1) {
				$value = $db->result($result,0,"name");
				$loc = $db->result($result,0,"location");
				$comments = $db->result($result,0,"comments");
			}
			echo "<br>";
			echo $lang["common"][15].": ";		

			dropdownValue("glpi_dropdown_locations", "value2",$loc,0);
			echo $lang["networking"][52].": ";
			echo "<input type='text' maxlength='100' size='10' name='value' value=\"".$value."\"><br>";
			echo "<textarea rows='2' cols='50' name='comments' title='".$lang["common"][25]."' >".$comments."</textarea>";

		} 
		else {

			echo "<input type='text' maxlength='100' size='20' name='value' value=\"".$value["name"]."\"><br>";
			echo "<textarea rows='2' cols='50' name='comments' title='".$lang["common"][25]."' >".$value["comments"]."</textarea>";
		}
		//
		echo "</td><td align='center' class='tab_bg_2' width='99'>";
		echo "<input type='hidden' name='tablename' value='glpi_dropdown_".$name."'>";
		//  on ajoute un bouton modifier
		echo "<input type='submit' name='update' value='".$lang["buttons"][14]."' class='submit'>";
		echo "</td><td align='center' class='tab_bg_2' width='99'>";
		//
		echo "<input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'>";
		echo "</td></tr>";

	}
	echo "</table></form>";
	echo "<form action=\"$target\" method='post'>";
	echo "<input type='hidden' name='which' value='$name'>";
	echo "<table class='tab_cadre_fixe' cellpadding='1'>";
	echo "<tr><td align='center'  class='tab_bg_1'>";
	if($name == "netpoint") {
		echo $lang["common"][15].": ";		
		dropdownValue("glpi_dropdown_locations", "value2",$value2,0);
		echo $lang["networking"][52].": ";
		echo "<input type='text' maxlength='100' size='10' name='value'><br>";
		echo "<textarea rows='2' cols='50' name='comments' title='".$lang["common"][25]."'></textarea>";
	}
	else {
		echo "<input type='text' maxlength='100' size='20' name='value'><br>";
		echo "<textarea rows='2' cols='50' name='comments' title='".$lang["common"][25]."'></textarea>";
	}
	echo "</td><td align='center' colspan='2' class='tab_bg_2' width='202'>";
	echo "<input type='hidden' name='tablename' value='glpi_dropdown_".$name."' >";
	echo "<input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'>";
	echo "</td></tr>";

	// Multiple Add for Netpoint
	if($name == "netpoint") {
		echo "</table></form>";

		echo "<form action=\"$target\" method='post'>";
		echo "<input type='hidden' name='which' value='$name'>";
		echo "<table class='tab_cadre_fixe' cellpadding='1'>";
		echo "<tr><td align='center'  class='tab_bg_1'>";

		echo $lang["common"][15].": ";		
		dropdownValue("glpi_dropdown_locations", "value2",$value2,0);
		echo $lang["networking"][52].": ";
		echo "<input type='text' maxlength='100' size='5' name='before'>";
		echo "<select name='from'>";
		for ($i=0;$i<400;$i++) echo "<option value='$i'>$i</option>";
		echo "</select>";
		echo "-->";
		echo "<select name='to'>";
		for ($i=0;$i<400;$i++) echo "<option value='$i'>$i</option>";
		echo "</select>";

		echo "<input type='text' maxlength='100' size='5' name='after'><br>";	
		echo "<textarea rows='2' cols='50' name='comments' title='".$lang["common"][25]."'></textarea>";
		echo "</td><td align='center' colspan='2' class='tab_bg_2' width='202'>";
		echo "<input type='hidden' name='tablename' value='glpi_dropdown_".$name."' >";
		echo "<input type='submit' name='several_add' value=\"".$lang["buttons"][8]."\" class='submit'>";
		echo "</td></tr>";
	}

	echo "</table></form></div>";
}

function showFormTypeDown ($target,$name,$human,$ID) {

	global $cfg_glpi, $lang, $HTMLRel;

	if (!haveRight("dropdown","w")) return false;	

	echo "<div align='center'>&nbsp;";

	echo "<form action=\"$target\" method='post'>";

	echo "<table class='tab_cadre_fixe'>";
	echo "<tr><th colspan='3'>$human:</th></tr>";

	if (countElementsInTable("glpi_type_".$name)>0){
		echo "<tr><td align='center' valign='middle' class='tab_bg_1'>";

		dropdownValue("glpi_type_".$name, "ID",$ID,0);
		// on ajoute un input text pour entrer la valeur modifier
		echo "&nbsp;&nbsp;<input type='image' class='calendrier' src=\"".$HTMLRel."pics/puce.gif\" alt='' title='' name='fillright' value='fillright'>&nbsp;";

		if (!empty($ID))
			$value=getDropdownName("glpi_type_".$name,$ID,1);
		else $value=array("name"=>"","comments"=>"");

		echo "<input type='text' maxlength='100' size='20' name='value'  value=\"".$value["name"]."\"><br>";
		echo "<textarea rows='2' cols='50' name='comments' title='".$lang["common"][25]."'>".$value["comments"]."</textarea>";

		echo "</td><td align='center' class='tab_bg_2'>";
		echo "<input type='hidden' name='tablename' value='glpi_type_".$name."'>";
		echo "<input type='hidden' name='which' value='$name'>";

		//  on ajoute un bouton modifier
		echo "<input type='submit' name='update' value='".$lang["buttons"][14]."' class='submit'>";
		echo "</td><td align='center' class='tab_bg_2'>";
		echo "<input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'>";
		echo "</td></tr>";
	}
	echo "</table></form>";

	echo "<form action=\"$target\" method='post'>";
	echo "<table class='tab_cadre_fixe'>";
	echo "<tr><td align='center' class='tab_bg_1'>";
	echo "<input type='text' maxlength='100' size='20' name='value'><br>";
	echo "<textarea rows='2' cols='50' name='comments' title='".$lang["common"][25]."'></textarea>";

	echo "</td><td align='center' colspan='2' class='tab_bg_2'>";
	echo "<input type='hidden' name='tablename' value='glpi_type_".$name."'>";
	echo "<input type='hidden' name='which' value='$name'>";
	echo "<input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'>";
	echo "</td></tr>";
	echo "</table></form></div>";
}
function moveTreeUnder($table,$to_move,$where){
	global $db;
	if ($where!=$to_move){
		// Is the $where location under the to move ???
		$impossible_move=false;

		$current_ID=$where;
		while ($current_ID!=0&&$impossible_move==false){

			$query="select * from $table WHERE ID='$current_ID'";
			$result = $db->query($query);
			$current_ID=$db->result($result,0,"parentID");
			if ($current_ID==$to_move) $impossible_move=true;

		}
		if (!$impossible_move){

			// Move Location
			$query = "UPDATE $table SET parentID='$where' where ID='$to_move'";
			$result = $db->query($query);
			regenerateTreeCompleteNameUnderID($table,$to_move);
		}	

	}	
}

function updateDropdown($input) {
	global $db,$cfg_glpi;


	if($input["tablename"] == "glpi_dropdown_netpoint") {
		$query = "update ".$input["tablename"]." SET name = '".$input["value"]."', location = '".$input["value2"]."', comments='".$input["comments"]."' where ID = '".$input["ID"]."'";

	}
	else {
		$query = "update ".$input["tablename"]." SET name = '".$input["value"]."', comments='".$input["comments"]."' where ID = '".$input["ID"]."'";
	}

	if ($result=$db->query($query)) {
		if (in_array($input["tablename"],$cfg_glpi["dropdowntree_tables"]))
			regenerateTreeCompleteNameUnderID($input["tablename"],$input["ID"]);
		return true;
	} else {
		return false;
	}
}


function addDropdown($input) {
	global $db,$cfg_glpi;

	if (!empty($input["value"])){

		if($input["tablename"] == "glpi_dropdown_netpoint") {
			$query = "INSERT INTO ".$input["tablename"]." (name,location,comments) VALUES ('".$input["value"]."', '".$input["value2"]."', '".$input["comments"]."')";
		}
		else if (in_array($input["tablename"],$cfg_glpi["dropdowntree_tables"])){
			if ($input['type']=="first"){
				$query = "INSERT INTO ".$input["tablename"]." (name,parentID,completename,comments) VALUES ('".$input["value"]."', '0','','".$input["comments"]."')";		
			} else {
				$query="SELECT * from ".$input["tablename"]." where ID='".$input["value2"]."'";
				$result=$db->query($query);
				if ($db->numrows($result)>0){
					$data=$db->fetch_array($result);
					$level_up=$data["parentID"];
					if ($input["type"]=="under") {
						$level_up=$data["ID"];
					} 
					$query = "INSERT INTO ".$input["tablename"]." (name,parentID,completename,comments) VALUES ('".$input["value"]."', '$level_up','','".$input["comments"]."')";		
				} else $query = "INSERT INTO ".$input["tablename"]." (name,parentID,completename,comments) VALUES ('".$input["value"]."', '0','','".$input["comments"]."')";				
			}
		}
		else {
			$query = "INSERT INTO ".$input["tablename"]." (name,comments) VALUES ('".$input["value"]."','".$input["comments"]."')";
		}

		if ($result=$db->query($query)) {

			if (in_array($input["tablename"],$cfg_glpi["dropdowntree_tables"]))
				regenerateTreeCompleteNameUnderID($input["tablename"],$db->insert_id());		
			return true;
		} else {
			return false;
		}
	}
}

function deleteDropdown($input) {

	global $db;
	$send = array();
	$send["tablename"] = $input["tablename"];
	$send["oldID"] = $input["ID"];
	$send["newID"] = 0;
	replaceDropDropDown($send);
}

//replace all entries for a dropdown in each items
function replaceDropDropDown($input) {
	global $db;
	$name = getDropdownNameFromTable($input["tablename"]);
	switch($name) {
		case "cartridge_type":
			$query = "update glpi_cartridges_type set type = '". $input["newID"] ."'  where type = '".$input["oldID"]."'";
		$db->query($query);
		break;
		case "consumable_type":
			$query = "update glpi_consumables_type set type = '". $input["newID"] ."'  where type = '".$input["oldID"]."'";
		$db->query($query);
		break;
		case "contact_type":
			$query = "update glpi_contacts set type = '". $input["newID"] ."'  where type = '".$input["oldID"]."'";
		$db->query($query);
		break;
		case "contract_type":
			$query = "update glpi_contracts set contract_type = '". $input["newID"] ."'  where ". $name ." = '".$input["oldID"]."'";
		$db->query($query);
		break;
		case "ram_type":
			$query = "update glpi_device_ram set type = '". $input["newID"] ."'  where type = '".$input["oldID"]."'";
		$db->query($query);
		break;
		case "interface":
			$query = "update glpi_device_hdd set interface = '". $input["newID"] ."'  where interface = '".$input["oldID"]."'";
		$db->query($query);
		$query = "update glpi_device_drive set interface = '". $input["newID"] ."'  where interface = '".$input["oldID"]."'";
		$db->query($query);
		$query = "update glpi_device_control set interface = '". $input["newID"] ."'  where interface = '".$input["oldID"]."'";
		$db->query($query);
		break;	
		case "vlan":
			$query = "update glpi_networking_vlan set FK_vlan = '". $input["newID"] ."'  where FK_vlan = '".$input["oldID"]."'";
		$db->query($query);
		break;
		case "domain":
			$query = "update glpi_computers set domain = '". $input["newID"] ."'  where ". $name ." = '".$input["oldID"]."'";
		$db->query($query);
		$query = "update glpi_printers set domain = '". $input["newID"] ."'  where ". $name ." = '".$input["oldID"]."'";
		$db->query($query);
		$query = "update glpi_networking set domain = '". $input["newID"] ."'  where ". $name ." = '".$input["oldID"]."'";
		$db->query($query);
		break;
		case "network":
			$query = "update glpi_computers set network = '". $input["newID"] ."'  where ". $name ." = '".$input["oldID"]."'";
		$db->query($query);
		$query = "update glpi_printers set network = '". $input["newID"] ."'  where network = '".$input["oldID"]."'";
		$db->query($query);
		$query = "update glpi_networking set network = '". $input["newID"] ."'  where network = '".$input["oldID"]."'";
		$db->query($query);
		break;

		case "enttype":
			$query = "update glpi_enterprises set type = '". $input["newID"] ."'  where type = '".$input["oldID"]."'";
		$db->query($query);
		break;
		case "firmware":
			$query = "update glpi_networking set firmware = '". $input["newID"] ."'  where ". $name ." = '".$input["oldID"]."'";
		$db->query($query);
		break;
		case "os" :
			$query = "update glpi_computers set ". $name ." = '". $input["newID"] ."'  where ". $name ." = '".$input["oldID"]."'";
		$db->query($query);
		$query = "update glpi_software set platform = '". $input["newID"] ."'  where platform = '".$input["oldID"]."'";
		$db->query($query);
		break;
		case "os_version" :
			$query = "update glpi_computers set ". $name ." = '". $input["newID"] ."'  where ". $name ." = '".$input["oldID"]."'";
			$db->query($query);
			
		break;
		case "os_sp" :
			$query = "update glpi_computers set ". $name ." = '". $input["newID"] ."'  where ". $name ." = '".$input["oldID"]."'";
		$db->query($query);
		break;
		case "iface" :
			$query = "update glpi_networking_ports set ". $name ." = '". $input["newID"] ."'  where ". $name ." = '".$input["oldID"]."'";
		$db->query($query);
		break;
		case "location" :
			$query = "update glpi_computers set ". $name ." = '". $input["newID"] ."'  where ". $name ." = '".$input["oldID"]."'";
		$result = $db->query($query);
		$query = "update glpi_monitors set ". $name ." = '". $input["newID"] ."'  where ". $name ." = '".$input["oldID"]."'";
		$result = $db->query($query);
		$query = "update glpi_printers set ". $name ." = '". $input["newID"] ."'  where ". $name ." = '".$input["oldID"]."'";
		$result = $db->query($query);
		$query = "update glpi_software set ". $name ." = '". $input["newID"] ."'  where ". $name ." = '".$input["oldID"]."'";
		$result = $db->query($query);
		$query = "update glpi_networking set ". $name ." = '". $input["newID"] ."'  where ". $name ." = '".$input["oldID"]."'";
		$db->query($query);
		$query = "update glpi_peripherals set ". $name ." = '". $input["newID"] ."'  where ". $name ." = '".$input["oldID"]."'";
		$db->query($query);
		$query = "update glpi_dropdown_netpoint set ". $name ." = '". $input["newID"] ."'  where ". $name ." = '".$input["oldID"]."'";
		$db->query($query);
		$query = "update glpi_cartridges_type set ". $name ." = '". $input["newID"] ."'  where ". $name ." = '".$input["oldID"]."'";
		$db->query($query);
		$query = "update glpi_users set ". $name ." = '". $input["newID"] ."'  where ". $name ." = '".$input["oldID"]."'";
		$result = $db->query($query);

		break;
		case "monitors" :
			$query = "update glpi_monitors set type = '". $input["newID"] ."'  where type = '".$input["oldID"]."'";
		$result = $db->query($query);
		break;
		case "computers" :
			$query = "update glpi_computers set type = '". $input["newID"] ."'  where type = '".$input["oldID"]."'";
		$result = $db->query($query);
		break;
		case "model" :
			$query = "update glpi_computers set model = '". $input["newID"] ."'  where model = '".$input["oldID"]."'";
		$result = $db->query($query);
		break;
		case "model_printers" :
			$query = "update glpi_printers set model = '". $input["newID"] ."'  where model = '".$input["oldID"]."'";
		$result = $db->query($query);
		break;
		case "model_monitors" :
			$query = "update glpi_monitors set model = '". $input["newID"] ."'  where model = '".$input["oldID"]."'";
		$result = $db->query($query);
		break;
		case "model_peripherals" :
			$query = "update glpi_peripherals set model = '". $input["newID"] ."'  where model = '".$input["oldID"]."'";
		$result = $db->query($query);
		break;
		case "model_phones" :
			$query = "update glpi_phones set model = '". $input["newID"] ."'  where model = '".$input["oldID"]."'";
		$result = $db->query($query);
		break;
		case "model_networking" :
			$query = "update glpi_networking set model = '". $input["newID"] ."'  where model = '".$input["oldID"]."'";
		$result = $db->query($query);
		break;
		case "printers" :
			$query = "update glpi_printers set type = '". $input["newID"] ."'  where type = '".$input["oldID"]."'";
		$result = $db->query($query);
		$query = "update glpi_cartridges_assoc set FK_glpi_type_printer = '". $input["newID"] ."'  where FK_glpi_type_printer = '".$input["oldID"]."'";
		$result = $db->query($query);
		break;
		case "networking" :
			$query = "update glpi_networking set type = '". $input["newID"] ."'  where type = '".$input["oldID"]."'";
		$result = $db->query($query);
		break;
		case "netpoint" : 
			$query = "update glpi_networking_ports set netpoint = '". $input["newID"] ."'  where ". $name ." = '".$input["oldID"]."'";
		$result = $db->query($query);
		break;
		case "rubdocs" : 
			$query = "update glpi_docs set rubrique = '". $input["newID"] ."'  where rubrique = '".$input["oldID"]."'";
		$result = $db->query($query);
		break;
		case "tracking_category":
			$query = "update glpi_tracking set category = '". $input["newID"] ."'  where category = '".$input["oldID"]."'";
		$result = $db->query($query);
		break;
		case "peripherals" :
			$query = "update glpi_peripherals set type = '". $input["newID"] ."'  where type = '".$input["oldID"]."'";
		$result = $db->query($query);
		break;
		case "phones" :
			$query = "update glpi_phones set type = '". $input["newID"] ."'  where type = '".$input["oldID"]."'";
		$result = $db->query($query);
		break;
		case "state" :
			$query = "update glpi_state_item set state = '". $input["newID"] ."'  where ". $name ." = '".$input["oldID"]."'";
		$result = $db->query($query);
		break;
		case "auto_update" :
			$query = "update glpi_computers set auto_update = '". $input["newID"] ."'  where ". $name ." = '".$input["oldID"]."'";
		$result = $db->query($query);
		break;
		case "budget" :
			$query = "update glpi_infocoms set budget = '". $input["newID"] ."'  where ". $name ." = '".$input["oldID"]."'";
		$db->query($query);
		break;
		case "phone_power" :
			$query = "update glpi_phones set power = '". $input["newID"] ."'  where power = '".$input["oldID"]."'";
		$db->query($query);
		break;
		case "case_type":
			$query = "update glpi_device_case set type = '". $input["newID"] ."'  where type = '".$input["oldID"]."'";
		$db->query($query);
		break;

	}

	$query = "delete from ". $input["tablename"] ." where ID = '". $input["oldID"] ."'";
	$db->query($query);
}

function showDeleteConfirmForm($target,$table, $ID) {
	global $db,$lang;

	if (!haveRight("dropdown","w")) return false;

	if ($table=="glpi_dropdown_locations"){

		$query = "Select count(*) as cpt FROM $table where parentID = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  {
			echo "<div align='center'><p style='color:red'>".$lang["setup"][74]."</p></div>";
			return;
		}
	}	

	if ($table=="glpi_dropdown_kbcategories"){
		$query = "Select count(*) as cpt FROM $table where parentID = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  {	
			echo "<div align='center'><p style='color:red'>".$lang["setup"][74]."</p></div>";
			return;
		} else {
			$query = "Select count(*) as cpt FROM glpi_kbitems where categoryID = '".$ID."'";
			$result = $db->query($query);
			if($db->result($result,0,"cpt") > 0)  {
				echo "<div align='center'><p style='color:red'>".$lang["setup"][74]."</p></div>";
				return;
			}
		}
	}

	echo "<div align='center'>";
	echo "<p style='color:red'>".$lang["setup"][63]."</p>";
	echo "<p>".$lang["setup"][64]."</p>";

	echo "<form action=\"". $target ."\" method=\"post\">";
	echo "<input type=\"hidden\" name=\"tablename\" value=\"". $table ."\"  />";
	echo "<input type=\"hidden\" name=\"ID\" value=\"". $ID ."\"  />";
	echo "<input type=\"hidden\" name=\"which\" value=\"". str_replace("glpi_type_","",str_replace("glpi_dropdown_","",$table)) ."\"  />";
	echo "<input type=\"hidden\" name=\"forcedelete\" value=\"1\" />";

	echo "<table class='tab_cadre'><tr><td>";
	echo "<input class='button' type=\"submit\" name=\"delete\" value=\"".$lang["buttons"][2]."\" /></td>";

	echo "<form action=\" ". $target ."\" method=\"post\">";
	echo "<td><input class='button' type=\"submit\" name=\"annuler\" value=\"".$lang["buttons"][34]."\" /></td></tr></table>";
	echo "</form>";
	echo "<p>". $lang["setup"][65]."</p>";
	echo "<form action=\" ". $target ."\" method=\"post\">";
	echo "<input type=\"hidden\" name=\"which\" value=\"". str_replace("glpi_type_","",str_replace("glpi_dropdown_","",$table)) ."\"  />";
	echo "<table class='tab_cadre'><tr><td>";
	dropdownNoValue($table,"newID",$ID);
	echo "<input type=\"hidden\" name=\"tablename\" value=\"". $table ."\"  />";
	echo "<input type=\"hidden\" name=\"oldID\" value=\"". $ID ."\"  />";
	echo "</td><td><input class='button' type=\"submit\" name=\"replace\" value=\"".$lang["buttons"][39]."\" /></td></tr></table>";
	echo "</form>";

	echo "</div>";
}


function getDropdownNameFromTable($table) {

	if(ereg("glpi_type_",$table)){
		$name = ereg_replace("glpi_type_","",$table);
	}
	else {
		if($table == "glpi_dropdown_locations") $name = "location";
		else {
			$name = ereg_replace("glpi_dropdown_","",$table);
		}
	}
	return $name;
}

function getDropdownNameFromTableForStats($table) {

	if(ereg("glpi_type_",$table)){
		$name = "type";
	}
	else {
		if($table == "glpi_dropdown_locations") $name = "location";
		else {
			$name = ereg_replace("glpi_dropdown_","",$table);
		}
	}
	return $name;
}


//check if the dropdown $ID is used into item tables
function dropdownUsed($table, $ID) {

	global $db;
	$name = getDropdownNameFromTable($table);

	$var1 = true;
	switch($name) {
		case "cartridge_type":
			$query = "Select count(*) as cpt FROM glpi_cartridges_type where type = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
		case "consumable_type":
			$query = "Select count(*) as cpt FROM glpi_consumables_type where type = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
		case "contact_type":
			$query = "Select count(*) as cpt FROM glpi_contacts where type = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
		case "contract_type":
			$query = "Select count(*) as cpt FROM glpi_contracts where ". $name ." = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
		case "ram_type":
			$query = "Select count(*) as cpt FROM glpi_device_ram where type = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
		case "interface":
			$query = "Select count(*) as cpt FROM glpi_device_hdd where interface = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		$query = "Select count(*) as cpt FROM glpi_device_drive where interface = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		$query = "Select count(*) as cpt FROM glpi_device_control where interface = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
		case "vlan":
			$query = "Select count(*) as cpt FROM glpi_networking_vlan where FK_vlan = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
		case "domain":
			$query = "Select count(*) as cpt FROM glpi_computers where ". $name ." = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		$query = "Select count(*) as cpt FROM glpi_printers where ". $name ." = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		$query = "Select count(*) as cpt FROM glpi_networking where ". $name ." = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
		case "network":
			$query = "Select count(*) as cpt FROM glpi_computers where ". $name ." = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		$query = "Select count(*) as cpt FROM glpi_printers where ". $name ." = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		$query = "Select count(*) as cpt FROM glpi_networking where ". $name ." = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
		case "enttype":
			$query = "Select count(*) as cpt FROM glpi_enterprises where type = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
		case "firmware":
			$query = "Select count(*) as cpt FROM glpi_networking where ". $name ." = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
		case "iface" : 
			$query = "Select count(*) as cpt FROM glpi_networking_ports where ". $name ." = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
		case "kbcategories" :
			$query = "Select count(*) as cpt FROM glpi_dropdown_kbcategories where parentID = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		$query = "Select count(*) as cpt FROM glpi_kbitems where categoryID = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
		case "location" :
			$query = "Select count(*) as cpt FROM glpi_dropdown_locations where parentID = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;

		$query = "Select count(*) as cpt FROM glpi_computers where ". $name ." = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		$query = "Select count(*) as cpt FROM glpi_monitors where ". $name ." = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		$query = "Select count(*) as cpt FROM glpi_printers where ". $name ." = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		$query = "Select count(*) as cpt FROM glpi_software where ". $name ." = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		$query = "Select count(*) as cpt FROM glpi_networking where ". $name ." = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		$query = "Select count(*) as cpt FROM glpi_peripherals where ". $name ." = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		$query = "Select count(*) as cpt FROM glpi_dropdown_netpoint where ". $name ." = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		$query = "Select count(*) as cpt FROM glpi_cartridges_type where ". $name ." = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		$query = "Select count(*) as cpt FROM glpi_users where ". $name ." = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;

		break;
		case "netpoint" : 
			$query = "Select count(*) as cpt FROM glpi_networking_ports where ". $name ." = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
		case "os" :
			$query = "Select count(*) as cpt FROM glpi_computers where ". $name ." = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		$query = "Select count(*) as cpt FROM glpi_software where platform = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;	
		break;
		case "os_version" :
			$query = "Select count(*) as cpt FROM glpi_computers where ". $name ." = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
		case "os_sp" :
			$query = "Select count(*) as cpt FROM glpi_computers where ". $name ." = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
		case "rubdocs":
			$query = "Select count(*) as cpt FROM glpi_docs where rubrique = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
		case "state":
			$query = "Select count(*) as cpt FROM glpi_state_item where ". $name ." = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;


		case "tracking_category":
			$query = "Select count(*) as cpt FROM glpi_tracking where category = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
		case "computers" :
			$query = "Select count(*) as cpt FROM glpi_computers where type = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
		case "model" :
			$query = "Select count(*) as cpt FROM glpi_computers where model = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
		case "model_printers" :
			$query = "Select count(*) as cpt FROM glpi_printers where model = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
		case "model_networking" :
			$query = "Select count(*) as cpt FROM glpi_networking where model = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
		case "model_monitors" :
			$query = "Select count(*) as cpt FROM glpi_monitors where model = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
		case "model_peripherals" :
			$query = "Select count(*) as cpt FROM glpi_peripherals where model = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
		case "model_phones" :
			$query = "Select count(*) as cpt FROM glpi_phones where model = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
		case "monitors" :
			$query = "Select count(*) as cpt FROM glpi_monitors where type = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
		case "networking" :
			$query = "Select count(*) as cpt FROM glpi_networking where type = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
		case "peripherals":
			$query = "Select count(*) as cpt FROM glpi_peripherals where type = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
		case "phones":
			$query = "Select count(*) as cpt FROM glpi_phones where type = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
		case "printers" :
			$query = "Select count(*) as cpt FROM glpi_printers where type = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		$query = "Select count(*) as cpt FROM glpi_cartridges_assoc where FK_glpi_type_printer = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;

		break;
		case "auto_update" :
			$query = "Select count(*) as cpt FROM glpi_computers where ". $name ." = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
		case "budget" :
			$query = "Select count(*) as cpt FROM glpi_infocoms where ". $name ." = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
		case "phone_power" :
			$query = "Select count(*) as cpt FROM glpi_phones where power = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
		case "case_type":
			$query = "Select count(*) as cpt FROM glpi_device_case where type = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;

	}
	return $var1;

}



function listTemplates($type,$target,$add=0) {

	global $db,$cfg_glpi, $lang;

	if (!haveTypeRight($type,"w")) return false;

	switch ($type){
		case COMPUTER_TYPE :
			$title=$lang["Menu"][0];
			$query = "SELECT * FROM glpi_computers where is_template = '1' ORDER by tplname";
			break;
		case NETWORKING_TYPE :
			$title=$lang["Menu"][1];
			$query = "SELECT * FROM glpi_networking where is_template = '1' ORDER by tplname";
			break;
		case MONITOR_TYPE :
			$title=$lang["Menu"][3];
			$query = "SELECT * FROM glpi_monitors where is_template = '1' ORDER by tplname";
			break;	
		case PRINTER_TYPE :
			$title=$lang["Menu"][2];
			$query = "SELECT * FROM glpi_printers where is_template = '1' ORDER by tplname";
			break;	
		case PERIPHERAL_TYPE :
			$title=$lang["Menu"][16];
			$query = "SELECT * FROM glpi_peripherals where is_template = '1' ORDER by tplname";
			break;
		case SOFTWARE_TYPE :
			$title=$lang["Menu"][4];
			$query = "SELECT * FROM glpi_software where is_template = '1' ORDER by tplname";
			break;
		case PHONE_TYPE :
			$title=$lang["Menu"][34];
			$query = "SELECT * FROM glpi_phones where is_template = '1' ORDER by tplname";
			break;

	}
	if ($result = $db->query($query)) {

		echo "<div align='center'><table class='tab_cadre' width='50%'>";
		if ($add)
			echo "<tr><th>".$lang["common"][7]." - $title:</th></tr>";
		else 
			echo "<tr><th colspan='2'>".$lang["common"][14]." - $title:</th></tr>";

		while ($data= $db->fetch_array($result)) {

			$templname = $data["tplname"];
			if ($templname=="Blank Template")
				$templname=$lang["common"][31];

			echo "<tr>";
			echo "<td align='center' class='tab_bg_1'>";
			if (!$add){
				echo "<a href=\"$target?ID=".$data["ID"]."&amp;withtemplate=1\">&nbsp;&nbsp;&nbsp;$templname&nbsp;&nbsp;&nbsp;</a></td>";

				echo "<td align='center' class='tab_bg_2'>";
				if ($data["tplname"]!="Blank Template")
					echo "<b><a href=\"$target?ID=".$data["ID"]."&amp;purge=purge&amp;withtemplate=1\">".$lang["buttons"][6]."</a></b>";
				else echo "&nbsp;";
				echo "</td>";
			} else {
				echo "<a href=\"$target?ID=".$data["ID"]."&amp;withtemplate=2\">&nbsp;&nbsp;&nbsp;$templname&nbsp;&nbsp;&nbsp;</a></td>";
			}

			echo "</tr>";		

		}

		if (!$add){
			echo "<tr>";
			echo "<td colspan='2' align='center' class='tab_bg_2'>";
			echo "<b><a href=\"$target?withtemplate=1\">".$lang["common"][9]."</a></b>";
			echo "</td>";
			echo "</tr>";
		}

		echo "</table></div>";
	}


}





function titleConfigGen(){

	global  $lang,$HTMLRel;

	echo "<div align='center'><table border='0'><tr><td>";
	echo "<img src=\"".$HTMLRel."pics/configuration.png\" alt='' title=''></td><td><b><span class='icon_sous_nav'>".$lang["setup"][70]."</span>";
	echo "</b></td></tr></table>&nbsp;</div>";


}

function titleConfigDisplay(){

	global  $lang,$HTMLRel;

	echo "<div align='center'><table border='0'><tr><td>";
	echo "<img src=\"".$HTMLRel."pics/configuration.png\" alt='' title=''></td><td><b><span class='icon_sous_nav'>".$lang["setup"][119]."</span>";
	echo "</b></td></tr></table>&nbsp;</div>";


}

function showFormConfigGen($target){

	global  $db,$lang,$HTMLRel,$cfg_glpi;

	if (!haveRight("config","w")) return false;	

	echo "<form name='form' action=\"$target\" method=\"post\">";
	echo "<input type='hidden' name='ID' value='".$cfg_glpi["ID"]."'>";
	echo "<div align='center'><table class='tab_cadre_fixe'>";
	echo "<tr><th colspan='4'>".$lang["setup"][70]."</th></tr>";

	$default_language=$cfg_glpi["default_language"];
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][113]." </td><td><select name=\"default_language\">";
	foreach ($cfg_glpi["languages"] as $key => $val){
		echo "<option value=\"".$key."\"";
		if($default_language==$key){ echo " selected";}
		echo ">".$val[0]. " (".$key.")";
	}

	echo "</select></td>";

	echo "<td align='center'> ".$lang["setup"][133]." </td><td>";
	dropdownYesNoInt("ocs_mode",$cfg_glpi["ocs_mode"]);
	echo "</td></tr>";


	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][102]." </td><td><select name=\"event_loglevel\">";
	$level=$cfg_glpi["event_loglevel"];
	echo "<option value=\"1\"";  if($level==1){ echo " selected";} echo ">".$lang["setup"][103]." </option>";
	echo "<option value=\"2\"";  if($level==2){ echo " selected";} echo ">".$lang["setup"][104]."</option>";
	echo "<option value=\"3\"";  if($level==3){ echo " selected";} echo ">".$lang["setup"][105]."</option>";
	echo "<option value=\"4\"";  if($level==4){ echo " selected";} echo ">".$lang["setup"][106]." </option>";
	echo "<option value=\"5\"";  if($level==5){ echo " selected";} echo ">".$lang["setup"][107]."</option>";
	echo "</select></td>";

	echo "<td align='center'>".$lang["setup"][109]." </td><td><input type=\"text\" name=\"expire_events\" value=\"". $cfg_glpi["expire_events"] ."\"></td></tr>";


	echo "<tr class='tab_bg_2'><td align='center'> ".$lang["setup"][124]." </td><td>";
	dropdownYesNoInt("auto_add_users",$cfg_glpi["auto_add_users"]);
	echo "</td>";

	echo "<td align='center'>".$lang["setup"][138]." </td><td><select name=\"debug\">";
	$check=$cfg_glpi["debug"];
	echo "<option value=\"".NORMAL_MODE."\" ".($cfg_glpi["debug"]==NORMAL_MODE?" selected ":"")." >".$lang["setup"][135]." </option>";
	echo "<option value=\"".TRANSLATION_MODE."\" ".($cfg_glpi["debug"]==TRANSLATION_MODE?" selected ":"")." >".$lang["setup"][136]." </option>";
	echo "<option value=\"".DEBUG_MODE."\" ".($cfg_glpi["debug"]==DEBUG_MODE?" selected ":"")." >".$lang["setup"][137]." </option>";
	echo "<option value=\"".DEMO_MODE."\" ".($cfg_glpi["debug"]==DEMO_MODE?" selected ":"")." >".$lang["setup"][141]." </option>";
	echo "</select></td></tr>";


	echo "<tr class='tab_bg_1'><td colspan='4' align='center'><strong>".$lang["setup"][10]."</strong></td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][115]."</td><td><select name='cartridges_alarm'>";
	for ($i=-1;$i<=100;$i++)
		echo "<option value='$i' ".($i==$cfg_glpi["cartridges_alarm"]?" selected ":"").">$i</option>";
	echo "</select></td>";

	echo "<td align='center'>".$lang["setup"][221]."</td><td>";
	showCalendarForm("form","date_fiscale",$cfg_glpi["date_fiscale"],0);	
	echo "</td></tr>";


	echo "<tr class='tab_bg_1'><td colspan='4' align='center'><strong>".$lang["title"][24]."</strong></td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][219]."</td><td>";
	dropdownYesNoInt("permit_helpdesk",$cfg_glpi["permit_helpdesk"]);
	echo "</td>";

	echo "<td align='center'> ".$lang["setup"][116]." </td><td>";
	dropdownYesNoInt("auto_assign",$cfg_glpi["auto_assign"]);
	echo "</td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][405]."</td><td>";
	dropdownYesNoInt("followup_on_update_ticket",$cfg_glpi["followup_on_update_ticket"]);
	echo "</td><td align='center'>".$lang["tracking"][37]."</td><td>";
	dropdownYesNoInt("keep_tracking_on_delete",$cfg_glpi["keep_tracking_on_delete"]);
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td colspan='4' align='center'><strong>".$lang["common"][41]."</strong></td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][246]." (".$lang["common"][44].")</td><td>";
	dropdownContractAlerting("contract_alerts",$cfg_glpi["contract_alerts"]);
	echo "</td>";

	echo "<td align='center'>".$lang["setup"][247]." (".$lang["common"][44].")</td><td>";
	echo "<select name=\"infocom_alerts\">";
	echo "<option value=\"0\" ".($cfg_glpi["infocom_alerts"]==0?" selected ":"")." >-----</option>";
	echo "<option value=\"".pow(2,ALERT_END)."\" ".($cfg_glpi["infocom_alerts"]==pow(2,ALERT_END)?" selected ":"")." >".$lang["financial"][80]." </option>";
	echo "</select>";
	echo "</td></tr>";


	echo "<tr class='tab_bg_1'><td colspan='4' align='center'><strong>".$lang["setup"][306]."</strong></td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][306]." </td><td><select name=\"auto_update_check\">";
	$check=$cfg_glpi["auto_update_check"];
	echo "<option value=\"0\" ".($check==0?" selected":"").">".$lang["setup"][307]." </option>";
	echo "<option value=\"7\" ".($check==7?" selected":"").">".$lang["setup"][308]." </option>";
	echo "<option value=\"30\" ".($check==30?" selected":"").">".$lang["setup"][309]." </option>";
	echo "</select></td><td colspan='2'>&nbsp;</td></tr>";



	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][401]." </td><td><input type=\"text\" name=\"proxy_name\" value=\"". $cfg_glpi["proxy_name"] ."\"></td>";
	echo "<td align='center'>".$lang["setup"][402]." </td><td><input type=\"text\" name=\"proxy_port\" value=\"". $cfg_glpi["proxy_port"] ."\"></td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][403]." </td><td><input type=\"text\" name=\"proxy_user\" value=\"". $cfg_glpi["proxy_user"] ."\"></td>";
	echo "<td align='center'>".$lang["setup"][404]." </td><td><input type=\"text\" name=\"proxy_password\" value=\"". $cfg_glpi["proxy_password"] ."\"></td></tr>";

	echo "<tr class='tab_bg_2'><td colspan='4' align='center'><input type=\"submit\" name=\"update_confgen\" class=\"submit\" value=\"".$lang["buttons"][2]."\" ></td></tr>";

	echo "</table></div>";	


	echo "</form>";
}

function showFormConfigDisplay($target){

	global $db, $lang,$HTMLRel,$cfg_glpi;

	if (!haveRight("config","w")) return false;	

	// Needed for list_limit
	$cfg=new Config();
	$cfg->getFromDB(1);
	echo "<form name='form' action=\"$target\" method=\"post\">";
	echo "<input type='hidden' name='ID' value='".$cfg_glpi["ID"]."'>";
	echo "<div align='center'><table class='tab_cadre'>";
	echo "<tr><th colspan='4'>".$lang["setup"][70]."</th></tr>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][108]."</td><td> <input type=\"text\" name=\"num_of_events\" value=\"". $cfg_glpi["num_of_events"] ."\"></td>";
	echo "<td align='center'>".$lang["setup"][111]."</td><td> <input type=\"text\" name=\"list_limit\" value=\"". $cfg->fields["list_limit"] ."\"></td></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][112]."</td><td><input type=\"text\" name=\"cut\" value=\"". $cfg_glpi["cut"] ."\"></td>";

	$dp_limit=$cfg_glpi["dropdown_limit"];
	echo "<td align='center'>".$lang["setup"][131]."</td><td>";
	echo "<select name='dropdown_limit'>";
	for ($i=20;$i<=100;$i++) echo "<option value='$i'".($dp_limit==$i?" selected ":"").">$i</option>";
	echo "</select>";	

	echo "</td></tr>";


	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][128]." </td><td><select name=\"dateformat\">";
	echo "<option value=\"0\"";  if($cfg_glpi["dateformat"]==0){ echo " selected";} echo ">YYYY-MM-DD</option>";
	echo "<option value=\"1\"";  if($cfg_glpi["dateformat"]==1){ echo " selected";} echo ">DD-MM-YYYY</option>";
	echo "</select></td>";

	echo "<td align='center'> ".$lang["setup"][117]." </td><td>";
	dropdownYesNoInt("public_faq",$cfg_glpi["public_faq"]);
	echo " </td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][129]." </td><td>";
	dropdownYesNoInt("view_ID",$cfg_glpi["view_ID"]);
	echo "</td>";

	echo "<td align='center'>".$lang["setup"][130]." </td><td><select name=\"nextprev_item\">";
	$nextprev_item=$cfg_glpi["nextprev_item"];
	echo "<option value=\"ID\"";  if($nextprev_item=="ID"){ echo " selected";} echo ">".$lang["common"][2]." </option>";
	echo "<option value=\"name\"";  if($nextprev_item=="name"){ echo " selected";} echo ">".$lang["common"][16]."</option>";
	echo "</select></td></tr>";

	$plan_begin=split(":",$cfg_glpi["planning_begin"]);
	$plan_end=split(":",$cfg_glpi["planning_end"]);
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][223]."</td><td>";
	echo "<select name='planning_begin'>";
	for ($i=0;$i<=24;$i++) echo "<option value='$i'".($plan_begin[0]==$i?" selected ":"").">$i</option>";
	echo "</select>";
	echo "&nbsp;->&nbsp;";
	echo "<select name='planning_end'>";
	for ($i=0;$i<=24;$i++) echo "<option value='$i' ".($plan_end[0]==$i?" selected ":"").">$i</option>";
	echo "</select>";


	echo "</td><td align='center'>".$lang["setup"][148]."</td><td>";
	echo "<select name='time_step'>";
	$steps=array(5,10,15,20,30,60);
	foreach ($steps as $step){
		echo "<option value='$step'".($cfg_glpi["time_step"]==$step?" selected ":"").">$step</option>";
	}
	echo "</select>&nbsp;".$lang["job"][22];
	echo "</td></tr>";


	echo "<tr class='tab_bg_2'><td align='center'> ".$lang["setup"][118]." </td><td colspan='3' align='center'>";
	echo "<textarea cols='70' rows='4' name='text_login' >";
	echo $cfg_glpi["text_login"];
	echo "</textarea>";
	echo "</td></tr>";



	echo "<tr class='tab_bg_1'><td colspan='4' align='center'><strong>".$lang["title"][24]."</strong></td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'> ".$lang["setup"][110]." </td><td>";
	dropdownYesNoInt("jobs_at_login",$cfg_glpi["jobs_at_login"]);
	echo " </td><td colspan='2'>&nbsp;</td></tr>";


	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][114]."</td><td colspan='3'>";
	echo "<table><tr>";
	echo "<td bgcolor='".$cfg_glpi["priority_1"]."'>1:<input type=\"text\" name=\"priority_1\" size='7' value=\"".$cfg_glpi["priority_1"]."\"></td>";
	echo "<td bgcolor='".$cfg_glpi["priority_2"]."'>2:<input type=\"text\" name=\"priority_2\" size='7' value=\"".$cfg_glpi["priority_2"]."\"></td>";
	echo "<td bgcolor='".$cfg_glpi["priority_3"]."'>3:<input type=\"text\" name=\"priority_3\" size='7' value=\"".$cfg_glpi["priority_3"]."\"></td>";
	echo "<td bgcolor='".$cfg_glpi["priority_4"]."'>4:<input type=\"text\" name=\"priority_4\" size='7' value=\"".$cfg_glpi["priority_4"]."\"></td>";
	echo "<td bgcolor='".$cfg_glpi["priority_5"]."'>5:<input type=\"text\" name=\"priority_5\" size='7' value=\"".$cfg_glpi["priority_5"]."\"></td>";
	echo "</tr></table>";
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td colspan='4' align='center'><strong>".$lang["setup"][147]."</strong></td></tr>";	


	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][120]." </td><td>";
	dropdownYesNoInt("use_ajax",$cfg_glpi["use_ajax"]);
	echo "</td>";

	echo "<td align='center'>".$lang["setup"][127]." </td><td>";
	dropdownYesNoInt("ajax_autocompletion",$cfg_glpi["ajax_autocompletion"]);
	echo "</td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][121]."</td><td><input type=\"text\" size='1' name=\"ajax_wildcard\" value=\"". $cfg_glpi["ajax_wildcard"] ."\"></td>";

	echo "<td align='center'>".$lang["setup"][122]."</td><td>";
	echo "<select name='dropdown_max'>";
	$dropdown_max=$cfg_glpi["dropdown_max"];
	for ($i=0;$i<=200;$i++) echo "<option value='$i'".($dropdown_max==$i?" selected ":"").">$i</option>";
	echo "</select>";
	echo "</td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][123]."</td><td>";
	echo "<select name='ajax_limit_count'>";
	$ajax_limit_count=$cfg_glpi["ajax_limit_count"];
	for ($i=0;$i<=200;$i++) echo "<option value='$i'".($ajax_limit_count==$i?" selected ":"").">$i</option>";
	echo "</select>";
	echo "</td><td colspan='2'>&nbsp;</td></tr>";



	echo "</table>&nbsp;</div>";	
	echo "<p class=\"submit\"><input type=\"submit\" name=\"update_confdisplay\" class=\"submit\" value=\"".$lang["buttons"][2]."\" ></p>";


	echo "</form>";
}




function titleExtAuth(){
	// Un titre pour la gestion des sources externes

	global  $lang,$HTMLRel;
	echo "<div align='center'><table border='0'><tr><td>";
	echo "<img src=\"".$HTMLRel."pics/authentification.png\" alt='' title=''></td><td><span class='icon_sous_nav'>".$lang["setup"][150]."</span>";
	echo "</td></tr></table>&nbsp;</div>";

}





function showMailServerConfig($value){
	global $lang;

	if (!haveRight("config","w")) return false;	

	if (ereg(":",$value)){
		$addr=ereg_replace("{","",preg_replace("/:.*/","",$value));
		$port=preg_replace("/.*:/","",preg_replace("/\/.*/","",$value));
	}
	else {
		if (ereg("/",$value))
			$addr=ereg_replace("{","",preg_replace("/\/.*/","",$value));
		else $addr=ereg_replace("{","",preg_replace("/}.*/","",$value));
		$port="";
	}
	$mailbox=preg_replace("/.*}/","",$value);

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][163]."</td><td><input size='30' type=\"text\" name=\"mail_server\" value=\"". $addr."\" ></td></tr>";	
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][168]."</td><td>";
	echo "<select name='server_type'>";
	echo "<option value=''>&nbsp;</option>";
	echo "<option value='/imap' ".(ereg("/imap",$value)?" selected ":"").">IMAP</option>";
	echo "<option value='/pop' ".(ereg("/pop",$value)?" selected ":"").">POP</option>";
	echo "</select>";
	echo "<select name='server_ssl'>";
	echo "<option value=''>&nbsp;</option>";
	echo "<option value='/ssl' ".(ereg("/ssl",$value)?" selected ":"").">SSL</option>";
	echo "</select>";
	echo "<select name='server_cert'>";
	echo "<option value=''>&nbsp;</option>";
	echo "<option value='/novalidate-cert' ".(ereg("/novalidate-cert",$value)?" selected ":"").">NO-VALIDATE-CERT</option>";
	echo "<option value='/validate-cert' ".(ereg("/validate-cert",$value)?" selected ":"").">VALIDATE-CERT</option>";
	echo "</select>";
	echo "<select name='server_tls'>";
	echo "<option value=''>&nbsp;</option>";
	echo "<option value='/tls' ".(ereg("/tls",$value)?" selected ":"").">TLS</option>";
	echo "<option value='/notls' ".(ereg("/notls",$value)?" selected ":"").">NO-TLS</option>";
	echo "</select>";

	echo "</td></tr>";	

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][169]."</td><td><input size='30' type=\"text\" name=\"server_mailbox\" value=\"". $mailbox."\" ></td></tr>";	
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][171]."</td><td><input size='10' type=\"text\" name=\"server_port\" value=\"". $port."\" ></td></tr>";	
	if (empty($value)) $value="&nbsp;";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][170]."</td><td><b>$value</b></td></tr>";	

}	

function constructIMAPAuthServer($input){

	$out="";
	if (isset($input['mail_server'])&&!empty($input['mail_server'])) $out.="{".$input['mail_server'];
	else return $out;
	if (isset($input['server_port'])&&!empty($input['server_port'])) $out.=":".$input['server_port'];
	if (isset($input['server_type'])) $out.=$input['server_type'];
	if (isset($input['server_ssl'])) $out.=$input['server_ssl'];
	if (isset($input['server_cert'])) $out.=$input['server_cert'];
	if (isset($input['server_tls'])) $out.=$input['server_tls'];

	$out.="}";
	if (isset($input['server_mailbox'])) $out.=$input['server_mailbox'];

	return $out;

}

function showFormExtAuth($target) {

	global  $db,$lang,$HTMLRel,$cfg_glpi;

	if (!haveRight("config","w")) return false;	

	echo "<form action=\"$target\" method=\"post\">";
	echo "<input type='hidden' name='ID' value='".$cfg_glpi["ID"]."'>";
	if(function_exists('imap_open')) {

		echo "<div align='center'>";
		echo "<p >".$lang["setup"][160]."</p>";
		//		echo "<p>".$lang["setup"][161]."</p>";
		echo "<table class='tab_cadre_fixe'>";
		echo "<tr><th colspan='2'>".$lang["setup"][162]."</th></tr>";
		echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][164]."</td><td><input size='30' type=\"text\" name=\"imap_host\" value=\"". $cfg_glpi["imap_host"] ."\" ></td></tr>";

		showMailServerConfig($cfg_glpi["imap_auth_server"]);
		echo "</table>&nbsp;</div>";
	}
	else {
		echo "<input type=\"hidden\" name=\"IMAP_Test\" value=\"1\" >";

		echo "<div align='center'>&nbsp;<table class='tab_cadre_fixe'>";
		echo "<tr><th colspan='2'>".$lang["setup"][162]."</th></tr>";
		echo "<tr class='tab_bg_2'><td align='center'><p class='red'>".$lang["setup"][165]."</p><p>".$lang["setup"][166]."</p></td></tr></table></div>";
	}
	if(extension_loaded('ldap'))
	{
		echo "<div align='center'><p > ".$lang["setup"][151]."</p>";

		echo "<table class='tab_cadre_fixe'>";
		echo "<tr><th colspan='4'>".$lang["setup"][152]."</th></tr>";

		echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][153]."</td><td><input type=\"text\" name=\"ldap_host\" value=\"". $cfg_glpi["ldap_host"] ."\"></td>";
		echo "<td align='center'>".$lang["setup"][172]."</td><td><input type=\"text\" name=\"ldap_port\" value=\"". $cfg_glpi["ldap_port"] ."\"></td></tr>";

		echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][154]."</td><td><input type=\"text\" name=\"ldap_basedn\" value=\"". $cfg_glpi["ldap_basedn"] ."\" ></td>";
		echo "<td align='center'>".$lang["setup"][155]."</td><td><input type=\"text\" name=\"ldap_rootdn\" value=\"". $cfg_glpi["ldap_rootdn"] ."\" ></td></tr>";

		echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][156]."</td><td><input type=\"password\" name=\"ldap_pass\" value=\"". $cfg_glpi["ldap_pass"] ."\" ></td>";
		echo "<td align='center'>".$lang["setup"][159]."</td><td><input type=\"text\" name=\"ldap_condition\" value=\"". $cfg_glpi["ldap_condition"] ."\" ></td></tr>";

		echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][228]."</td><td><input type=\"text\" name=\"ldap_login\" value=\"". $cfg_glpi["ldap_login"] ."\" ></td>";
		echo "<td align='center'>".$lang["setup"][180]."</td><td>";
		if (function_exists("ldap_start_tls")){
			$ldap_use_tls=$cfg_glpi["ldap_use_tls"];
			echo "<select name='ldap_use_tls'>\n";
			echo "<option value='0' ".(!$ldap_use_tls?" selected ":"").">".$lang["choice"][0]."</option>\n";
			echo "<option value='1' ".($ldap_use_tls?" selected ":"").">".$lang["choice"][1]."</option>\n";
			echo "</select>\n";	
		} else {
			echo "<input type='hidden' name='ldap_use_tls' value='0'>";
			echo $lang["setup"][181];

		}
		echo "</td></tr>";

		echo "<tr class='tab_bg_1'><td align='center' colspan='4'>".$lang["setup"][259]."</td></tr>";

		echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][254]."</td><td>";
		$ldap_search_for_groups=$cfg_glpi["ldap_search_for_groups"];

		echo "<select name='ldap_search_for_groups'>\n";
		echo "<option value='0' ".(($ldap_search_for_groups==0)?" selected ":"").">".$lang["setup"][256]."</option>\n";
		echo "<option value='1' ".(($ldap_search_for_groups==1)?" selected ":"").">".$lang["setup"][257]."</option>\n";
		echo "<option value='2' ".(($ldap_search_for_groups==2)?" selected ":"").">".$lang["setup"][258]."</option>\n";
		echo "</select>\n";
		echo "</td>";
		echo "<td align='center'>".$lang["setup"][260]."</td><td><input type=\"text\" name=\"ldap_field_group\" value=\"". $cfg_glpi["ldap_field_group"] ."\" ></td></tr>";

		echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][253]."</td><td>";
		echo "<input type=\"text\" name=\"ldap_group_condition\" value=\"". $cfg_glpi["ldap_group_condition"] ."\" ></td>";
		echo "<td align='center'>".$lang["setup"][255]."</td><td><input type=\"text\" name=\"ldap_field_group_member\" value=\"". $cfg_glpi["ldap_field_group_member"] ."\" ></td></tr>";


		echo "<tr class='tab_bg_1'><td align='center' colspan='4'>".$lang["setup"][167]."</td></tr>";

		echo "<tr class='tab_bg_2'><td align='center'>".$lang["common"][48]."</td><td><input type=\"text\" name=\"ldap_field_realname\" value=\"". $cfg_glpi["ldap_field_realname"] ."\" ></td>";
		echo "<td align='center'>".$lang["common"][43]."</td><td><input type=\"text\" name=\"ldap_field_firstname\" value=\"". $cfg_glpi["ldap_field_firstname"] ."\" ></td></tr>";

		echo "<tr class='tab_bg_2'><td align='center'>".$lang["common"][15]."</td><td><input type=\"text\" name=\"ldap_field_location\" value=\"". $cfg_glpi["ldap_field_location"] ."\" ></td>";
		echo "<td align='center'>".$lang["setup"][14]."</td><td><input type=\"text\" name=\"ldap_field_email\" value=\"". $cfg_glpi["ldap_field_email"] ."\" ></td></tr>";

		echo "<tr class='tab_bg_2'><td align='center'>".$lang["financial"][29]."</td><td><input type=\"text\" name=\"ldap_field_phone\" value=\"". $cfg_glpi["ldap_field_phone"] ."\" ></td>";
		echo "<td align='center'>".$lang["financial"][29]." 2</td><td><input type=\"text\" name=\"ldap_field_phone2\" value=\"". $cfg_glpi["ldap_field_phone2"] ."\" ></td></tr>";

		echo "<tr class='tab_bg_2'><td align='center'>".$lang["common"][42]."</td><td><input type=\"text\" name=\"ldap_field_mobile\" value=\"". $cfg_glpi["ldap_field_mobile"] ."\" ></td>";
		echo "<td align='center' colspan='2'>&nbsp;</td></tr>";

		echo "</table>&nbsp;</div>";
	}
	else {
		echo "<input type=\"hidden\" name=\"LDAP_Test\" value=\"1\" >";
		echo "<div align='center'><table class='tab_cadre_fixe'>";
		echo "<tr><th colspan='2'>".$lang["setup"][152]."</th></tr>";
		echo "<tr class='tab_bg_2'><td align='center'><p class='red'>".$lang["setup"][157]."</p><p>".$lang["setup"][158]."</p></td></th></table></div>";
	}

	if(function_exists('curl_init')&&(version_compare(PHP_VERSION,'5','>=')||(function_exists("domxml_open_mem")&&function_exists("utf8_decode"))))
	{
		echo "<div align='center'><p > ".$lang["setup"][173]."</p>";

		echo "<table class='tab_cadre'>";
		echo "<tr><th colspan='2'>".$lang["setup"][177]."</th></tr>";
		echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][174]."</td><td><input type=\"text\" name=\"cas_host\" value=\"". $cfg_glpi["cas_host"] ."\"></td></tr>";
		echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][175]."</td><td><input type=\"text\" name=\"cas_port\" value=\"". $cfg_glpi["cas_port"] ."\"></td></tr>";
		echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][176]."</td><td><input type=\"text\" name=\"cas_uri\" value=\"". $cfg_glpi["cas_uri"] ."\" ></td></tr>";

		echo "</table>&nbsp;</div>";
	}
	else {
		echo "<input type=\"hidden\" name=\"CAS_Test\" value=\"1\" >";
		echo "<div align='center'><table class='tab_cadre_fixe'>";
		echo "<tr><th colspan='2'>".$lang["setup"][177]."</th></tr>";
		echo "<tr class='tab_bg_2'><td align='center'><p class='red'>".$lang["setup"][178]."</p><p>".$lang["setup"][179]."</p></td></th></table></div>";
	}

	echo "<p class=\"submit\"><input type=\"submit\" name=\"update_ext\" class=\"submit\" value=\"".$lang["buttons"][2]."\" ></p>";
	echo "</form>";
}


function titleMailing(){
	// Un titre pour la gestion du suivi par mail

	global  $lang,$HTMLRel;
	echo "<div align='center'><table border='0'><tr><td>";
	echo "<img src=\"".$HTMLRel."pics/mail.png\" alt='' title=''></td><td><span class='icon_sous_nav'>".$lang["setup"][200]."</span>";
	echo "</td></tr></table></div>";
}


function showFormMailing($target) {

	global $db,$lang,$cfg_glpi;

	if (!haveRight("config","w")) return false;	

	echo "<form action=\"$target\" method=\"post\">";
	echo "<input type='hidden' name='ID' value='".$cfg_glpi["ID"]."'>";

	echo "<div id='barre_onglets'><ul id='onglet'>";
	echo "<li "; if ($_SESSION['glpi_mailconfig']==1){ echo "class='actif'";} echo  "><a href='$target?next=mailing&amp;onglet=1'>".$lang["Menu"][10]."</a></li>";
	echo "<li "; if ($_SESSION['glpi_mailconfig']==2){ echo "class='actif'";} echo  "><a href='$target?next=mailing&amp;onglet=2'>".$lang["setup"][240]."</a></li>";
	echo "<li "; if ($_SESSION['glpi_mailconfig']==3){ echo "class='actif'";} echo  "><a href='$target?next=mailing&amp;onglet=3'>".$lang["setup"][242]."</a></li>";
	echo "</ul></div>";

	if ($_SESSION['glpi_mailconfig']==1){
		echo "<div align='center'><table class='tab_cadre_fixe'><tr><th colspan='2'>".$lang["setup"][201]."</th></tr>";

		echo "<tr class='tab_bg_2'><td >".$lang["setup"][202]."</td><td>";
		dropdownYesNoInt("mailing",$cfg_glpi["mailing"]);
		echo "</td></tr>";

		echo "<tr class='tab_bg_2'><td >".$lang["setup"][203]."</td><td> <input type=\"text\" name=\"admin_email\" size='40' value=\"".$cfg_glpi["admin_email"]."\"> </td></tr>";

		echo "<tr class='tab_bg_2'><td >".$lang["setup"][204]."</td><td><input type=\"text\" name=\"mailing_signature\" size='40' value=\"".$cfg_glpi["mailing_signature"]."\" ></td></tr>";

		echo "<tr class='tab_bg_2'><td >".$lang["setup"][226]."</td><td>";
		dropdownYesNoInt("url_in_mail",$cfg_glpi["url_in_mail"]);
		echo "</td></tr>";

		echo "<tr class='tab_bg_2'><td >".$lang["setup"][227]."</td><td> <input type=\"text\" name=\"url_base\" size='40' value=\"".$cfg_glpi["url_base"]."\"> </td></tr>";

		if (!function_exists('mail')) {
			echo "<tr class='tab_bg_2'><td align='center' colspan='2'><span class='red'>".$lang["setup"][217]." : </span><span>".$lang["setup"][218]."</span></td></tr>";
		}

		echo "<tr class='tab_bg_2'><td >".$lang["setup"][231]."</td><td>&nbsp; ";

		if (!function_exists('mail')) { // if mail php disabled we forced SMTP usage 
			echo $lang["choice"][1]."  &nbsp;<input type=\"radio\" name=\"smtp_mode\" value=\"1\" checked >";
		}else{
			dropdownYesNoInt("smtp_mode",$cfg_glpi["smtp_mode"]);
		}
		echo "</td></tr>";

		echo "<tr class='tab_bg_2'><td >".$lang["setup"][232]."</td><td> <input type=\"text\" name=\"smtp_host\" size='40' value=\"".$cfg_glpi["smtp_host"]."\"> </td></tr>";

		echo "<tr class='tab_bg_2'><td >".$lang["setup"][233]."</td><td> <input type=\"text\" name=\"smtp_port\" size='40' value=\"".$cfg_glpi["smtp_port"]."\"> </td></tr>";

		echo "<tr class='tab_bg_2'><td >".$lang["setup"][234]."</td><td> <input type=\"text\" name=\"smtp_username\" size='40' value=\"".$cfg_glpi["smtp_username"]."\"> </td></tr>";

		echo "<tr class='tab_bg_2'><td >".$lang["setup"][235]."</td><td> <input type=\"password\" name=\"smtp_password\" size='40' value=\"".$cfg_glpi["smtp_password"]."\"> </td></tr>";

		echo "<tr class='tab_bg_2'><td >".$lang["setup"][245]." ".$lang["setup"][244]."</td><td>";
		echo "<select name='cartridges_alert'> ";
		echo "<option value='0' ".($cfg_glpi["cartridges_alert"]==0?"selected":"")." >".$lang["setup"][307]."</option>";
		echo "<option value='".WEEK_TIMESTAMP."' ".($cfg_glpi["cartridges_alert"]==WEEK_TIMESTAMP?"selected":"")." >".$lang["setup"][308]."</option>";
		echo "<option value='".MONTH_TIMESTAMP."' ".($cfg_glpi["cartridges_alert"]==MONTH_TIMESTAMP?"selected":"")." >".$lang["setup"][309]."</option>";
		echo "</select>";
		echo "</td></tr>";

		echo "<tr class='tab_bg_2'><td >".$lang["setup"][245]." ".$lang["setup"][243]."</td><td>";
		echo "<select name='consumables_alert'> ";
		echo "<option value='0' ".($cfg_glpi["consumables_alert"]==0?"selected":"")." >".$lang["setup"][307]."</option>";
		echo "<option value='".WEEK_TIMESTAMP."' ".($cfg_glpi["consumables_alert"]==WEEK_TIMESTAMP?"selected":"")." >".$lang["setup"][308]."</option>";
		echo "<option value='".MONTH_TIMESTAMP."' ".($cfg_glpi["consumables_alert"]==MONTH_TIMESTAMP?"selected":"")." >".$lang["setup"][309]."</option>";
		echo "</select>";
		echo "</td></tr>";

		echo "<tr class='tab_bg_2'><td align='center' colspan='2'>";
		echo "<input type=\"submit\" name=\"update_mailing\" class=\"submit\" value=\"".$lang["buttons"][2]."\" >";
		echo "</td></tr>";

		echo "</table>";
		echo "</div>";
		echo "</form>";
		echo "<form action=\"$target\" method=\"post\">";
		echo "<div align='center'><table class='tab_cadre_fixe'><tr><th colspan='2'>".$lang["setup"][229]."</th></tr>";
		echo "<tr class='tab_bg_2'>";
		echo "<td align='center'>";
		echo "<input class=\"submit\" type=\"submit\" name=\"test_smtp_send\" value=\"".$lang["buttons"][2]."\">";
		echo " </td></tr></table></div>";

	} else if ($_SESSION['glpi_mailconfig']==2)	{

		$profiles[USER_MAILING_TYPE."_".ADMIN_MAILING]=$lang["setup"][237];
		$profiles[USER_MAILING_TYPE."_".TECH_MAILING]=$lang["common"][10];
		$profiles[USER_MAILING_TYPE."_".USER_MAILING]=$lang["common"][34]." ".$lang["common"][1];
		$profiles[USER_MAILING_TYPE."_".AUTHOR_MAILING]=$lang["setup"][238];
		$profiles[USER_MAILING_TYPE."_".ASSIGN_MAILING]=$lang["setup"][239];
		

		$query="SELECT ID, name FROM glpi_profiles order by name";
		$result=$db->query($query);
		while ($data=$db->fetch_assoc($result))
			$profiles[PROFILE_MAILING_TYPE."_".$data["ID"]]=$lang["profiles"][22]." ".$data["name"];

		$query="SELECT ID, name FROM glpi_groups order by name";
		$result=$db->query($query);
		while ($data=$db->fetch_assoc($result))
			$profiles[GROUP_MAILING_TYPE."_".$data["ID"]]=$lang["common"][35]." ".$data["name"];


		ksort($profiles);
		echo "<div align='center'>";
		echo "<input type='hidden' name='update_notifications' value='1'>";
		// ADMIN
		echo "<table class='tab_cadre_fixe'>";
		echo "<tr><th colspan='3'>".$lang["setup"][211]."</th></tr>";
		echo "<tr class='tab_bg_2'>";
		showFormMailingType("new",$profiles);
		echo "</tr>";
		echo "<tr><th colspan='3'>".$lang["setup"][212]."</th></tr>";
		echo "<tr class='tab_bg_1'>";
		showFormMailingType("followup",$profiles);
		echo "</tr>";
		echo "<tr class='tab_bg_2'><th colspan='3'>".$lang["setup"][213]."</th></tr>";
		echo "<tr class='tab_bg_2'>";
		showFormMailingType("finish",$profiles);
		echo "</tr>";
		echo "<tr class='tab_bg_2'><th colspan='3'>".$lang["setup"][230]."</th></tr>";
		echo "<tr class='tab_bg_1'>";
		$profiles[USER_MAILING_TYPE."_".OLD_ASSIGN_MAILING]=$lang["setup"][236];
		ksort($profiles);
		showFormMailingType("update",$profiles);
		unset($profiles[USER_MAILING_TYPE."_".OLD_ASSIGN_MAILING]);
		echo "</tr>";

		echo "<tr class='tab_bg_2'><th colspan='3'>".$lang["setup"][225]."</th></tr>";
		echo "<tr class='tab_bg_2'>";
		unset($profiles[USER_MAILING_TYPE."_".ASSIGN_MAILING]);
		showFormMailingType("resa",$profiles);
		echo "</tr>";

		echo "</table>";
		echo "</div>";
	} else if ($_SESSION['glpi_mailconfig']==3)	{
		$profiles[USER_MAILING_TYPE."_".ADMIN_MAILING]=$lang["setup"][237];
		$query="SELECT ID, name FROM glpi_profiles order by name";
		$result=$db->query($query);
		while ($data=$db->fetch_assoc($result))
			$profiles[PROFILE_MAILING_TYPE."_".$data["ID"]]=$lang["profiles"][22]." ".$data["name"];

		$query="SELECT ID, name FROM glpi_groups order by name";
		$result=$db->query($query);
		while ($data=$db->fetch_assoc($result))
			$profiles[GROUP_MAILING_TYPE."_".$data["ID"]]=$lang["common"][35]." ".$data["name"];


		ksort($profiles);
		echo "<div align='center'>";
		echo "<input type='hidden' name='update_notifications' value='1'>";
		// ADMIN
		echo "<table class='tab_cadre_fixe'>";
		echo "<tr><th colspan='3'>".$lang["setup"][243]."</th></tr>";
		echo "<tr class='tab_bg_2'>";
		showFormMailingType("alertconsumable",$profiles);
		echo "</tr>";
		echo "<tr><th colspan='3'>".$lang["setup"][244]."</th></tr>";
		echo "<tr class='tab_bg_1'>";
		showFormMailingType("alertcartridge",$profiles);
		echo "</tr>";
		echo "<tr><th colspan='3'>".$lang["setup"][246]."</th></tr>";
		echo "<tr class='tab_bg_2'>";
		showFormMailingType("alertcontract",$profiles);
		echo "</tr>";
		echo "<tr><th colspan='3'>".$lang["setup"][247]."</th></tr>";
		echo "<tr class='tab_bg_1'>";
		showFormMailingType("alertinfocom",$profiles);
		echo "</tr>";
		echo "</table>";
		echo "</div>";

	}
	echo "</form>";

}

function showFormMailingType($type,$profiles){
	global $lang,$db;

	echo "<td align='right'>";

	echo "<select name='mailing_to_add_".$type."[]' multiple size='5'>";

	foreach ($profiles as $key => $val){
		list($item_type,$item)=split("_",$key);
		echo "<option value='$key'>".$val."</option>";
	}
	echo "</select>";
	echo "</td>";
	echo "<td align='center'>";
	echo "<input type='submit'  class=\"submit\" name='mailing_add_$type' value='".$lang["buttons"][8]." >>'><br><br>";
	echo "<input type='submit'  class=\"submit\" name='mailing_delete_$type' value='<< ".$lang["buttons"][6]."'>";
	echo "</td>";
	echo "<td>";
	echo "<select name='mailing_to_delete_".$type."[]' multiple size='5'>";
	// Get User mailing
	$query="SELECT glpi_mailing.FK_item as item, glpi_mailing.ID as ID FROM glpi_mailing WHERE glpi_mailing.type='$type' AND glpi_mailing.item_type='".USER_MAILING_TYPE."' ORDER BY glpi_mailing.FK_item;";
	$result=$db->query($query);
	if ($db->numrows($result))
		while ($data=$db->fetch_assoc($result)){
			switch ($data["item"]){
				case ADMIN_MAILING: $name=$lang["setup"][237];break;
				case ASSIGN_MAILING: $name=$lang["setup"][239];break;
				case AUTHOR_MAILING: $name=$lang["setup"][238];break;
				case USER_MAILING: $name=$lang["common"][34]." ".$lang["common"][1];break;
				case OLD_ASSIGN_MAILING: $name=$lang["setup"][236];break;
				case TECH_MAILING: $name=$lang["common"][10];break;
			}
			echo "<option value='".$data["ID"]."'>".$name."</option>";
		}
	// Get Profile mailing
	$query="SELECT glpi_mailing.FK_item as item, glpi_mailing.ID as ID, glpi_profiles.name as prof FROM glpi_mailing LEFT JOIN glpi_profiles ON (glpi_mailing.FK_item = glpi_profiles.ID) WHERE glpi_mailing.type='$type' AND glpi_mailing.item_type='".PROFILE_MAILING_TYPE."' ORDER BY glpi_profiles.name;";
	$result=$db->query($query);
	if ($db->numrows($result))
		while ($data=$db->fetch_assoc($result)){
			echo "<option value='".$data["ID"]."'>".$lang["profiles"][22]." ".$data["prof"]."</option>";
		}

	// Get Group mailing
	$query="SELECT glpi_mailing.FK_item as item, glpi_mailing.ID as ID, glpi_groups.name as name FROM glpi_mailing LEFT JOIN glpi_groups ON (glpi_mailing.FK_item = glpi_groups.ID) WHERE glpi_mailing.type='$type' AND glpi_mailing.item_type='".GROUP_MAILING_TYPE."' ORDER BY glpi_groups.name;";
	$result=$db->query($query);
	if ($db->numrows($result))
		while ($data=$db->fetch_assoc($result)){
			echo "<option value='".$data["ID"]."'>".$lang["common"][35]." ".$data["name"]."</option>";
		}

	echo "</select>";
	echo "</td>";

}

function updateMailNotifications($input){
	global $db;
	$type="";
	$action="";


	foreach ($input as $key => $val){
		if (!ereg("mailing_to_",$key)&&ereg("mailing_",$key)){
			if (preg_match("/mailing_([a-z]+)_([a-z]+)/",$key,$matches)){
				$type=$matches[2];
				$action=$matches[1];
			}
		}
	}

	if (count($input["mailing_to_".$action."_".$type])>0){
		foreach ($input["mailing_to_".$action."_".$type] as $val){
			switch ($action){
				case "add":
					list($item_type,$item)=split("_",$val);
				$query="INSERT INTO glpi_mailing (type,FK_item,item_type) VALUES ('$type','$item','$item_type')";
				$db->query($query);
				break;
				case "delete":
					$query="DELETE FROM glpi_mailing WHERE ID='$val'";
				$db->query($query);
				break;
			} 
		}
	}


}



/**
 * Update the DB configuration of the OCS Mode
 *
 * Update this DB config from the form, do the query and go back to the form.
 *
 *@param $input array : The _POST values from the config form
 *@param $id int : template or basic computers
 *
 *@return nothing (displays or error)
 *
 **/
function ocsUpdateDBConfig($input, $id) {

	global $db,$phproot;
	if(!empty($input["ocs_db_user"]) && !empty($input["ocs_db_host"])) {

		if(empty($input["ocs_db_passwd"])) $input["ocs_db_passwd"] = "";

		$query = "update glpi_ocs_config set ocs_db_user = '".$input["ocs_db_user"]."', ocs_db_host = '".$input["ocs_db_host"]."', ocs_db_passwd = '".$input["ocs_db_passwd"]."', ocs_db_name = '".$input["ocs_db_name"]."' where ID = '".$id."'";

		$db->query($query);
	} else {
		echo $lang["ocsng"][17];
	}

}




function ocsFormDBConfig($target, $id) {


	global  $db,$dbocs,$lang,$cfg_glpi;

	if (!haveRight("ocsng","w")) return false;	

	$data=getOcsConf($id);

	echo "<form name='formdbconfig' action=\"$target\" method=\"post\">";
	echo "<input type='hidden' name='ID' value='".$cfg_glpi["ID"]."'>";
	echo "<input type='hidden' name='update_ocs_dbconfig' value='1'>";
	echo "<div align='center'><table class='tab_cadre'>";
	echo "<tr><th colspan='2'>".$lang["ocsconfig"][0]."</th></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["ocsconfig"][2]." </td><td> <input type=\"text\" name=\"ocs_db_host\" value=\"".$data["ocs_db_host"]."\"></td></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["ocsconfig"][4]." </td><td> <input type=\"text\" name=\"ocs_db_name\" value=\"".$data["ocs_db_name"]."\"></td></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["ocsconfig"][1]." </td><td> <input type=\"text\" name=\"ocs_db_user\" value=\"".$data["ocs_db_user"]."\"></td></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["ocsconfig"][3]." </td><td> <input type=\"password\" name=\"ocs_db_passwd\" value=\"".$data["ocs_db_passwd"]."\"></td></tr>";
	echo "</table></div>";
	echo "<p class=\"submit\"><input type=\"submit\" name=\"update_conf_ocs\" class=\"submit\" value=\"".$lang["buttons"][2]."\" ></p>";
	echo "</form>";


	echo "<div align='center'>";
	if (!$dbocs->error){
		echo $lang["ocsng"][18]."<br>";
		$result=$dbocs->query("SELECT TVALUE FROM config WHERE NAME='GUI_VERSION'");
		if ($dbocs->numrows($result)==1&&$dbocs->result($result,0,0)>=4020) {
			$query = "UPDATE config SET IVALUE='1' WHERE NAME='TRACE_DELETED'";
			$dbocs->query($query);

			echo $lang["ocsng"][19]."</div>";
			ocsFormConfig($target, $id);
		} else echo $lang["ocsng"][20]."</div>";
	} else echo $lang["ocsng"][21]."</div>";

}

function ocsFormConfig($target, $id) {


	global  $db,$lang,$cfg_glpi;

	if (!haveRight("ocsng","w")) return false;	

	$data=getOcsConf($id);

	echo "<form name='formconfig' action=\"$target\" method=\"post\">";
	echo "<input type='hidden' name='ID' value='".$cfg_glpi["ID"]."'>";
	echo "<input type='hidden' name='update_ocs_config' value='1'>";
	echo "<div align='center'><table class='tab_cadre'>";
	echo "<tr><th colspan='2'>".$lang["ocsconfig"][5]."</th></tr>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["ocsconfig"][17]." </td><td> <input type=\"text\" size='30' name=\"tag_limit\" value=\"".$data["tag_limit"]."\"></td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["ocsconfig"][16]." </td><td>";
	dropdownValue("glpi_dropdown_state","default_state",$data["default_state"]);
	echo "</td></tr>";

	$periph=$data["import_periph"];
	$monitor=$data["import_monitor"];
	$printer=$data["import_printer"];
	$software=$data["import_software"];
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["ocsconfig"][8]." </td><td>";
	echo "<select name='import_periph'>";
	echo "<option value='0' ".($periph==0?" selected ":"").">".$lang["ocsconfig"][11]."</option>";
	echo "<option value='1' ".($periph==1?" selected ":"").">".$lang["ocsconfig"][10]."</option>";
	echo "<option value='2' ".($periph==2?" selected ":"").">".$lang["ocsconfig"][12]."</option>";
	echo "</select>";
	echo "</td></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["ocsconfig"][7]." </td><td>";
	echo "<select name='import_monitor'>";
	echo "<option value='0' ".($monitor==0?" selected ":"").">".$lang["ocsconfig"][11]."</option>";
	echo "<option value='1' ".($monitor==1?" selected ":"").">".$lang["ocsconfig"][10]."</option>";
	echo "<option value='2' ".($monitor==2?" selected ":"").">".$lang["ocsconfig"][12]."</option>";
	echo "</select>";
	echo "</td></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["ocsconfig"][9]." </td><td>";

	echo "<select name='import_printer'>";
	echo "<option value='0' ".($printer==0?" selected ":"").">".$lang["ocsconfig"][11]."</option>";
	echo "<option value='1' ".($printer==1?" selected ":"").">".$lang["ocsconfig"][10]."</option>";
	echo "<option value='2' ".($printer==2?" selected ":"").">".$lang["ocsconfig"][12]."</option>";
	echo "</select>";
	echo "</td></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["ocsconfig"][6]." </td><td>";
	echo "<select name='import_software'>";
	echo "<option value='0' ".($software==0?" selected ":"").">".$lang["ocsconfig"][11]."</option>";
	echo "<option value='1' ".($software==1?" selected ":"").">".$lang["ocsconfig"][12]."</option>";
	echo "</select>";

	echo "</td></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["ocsconfig"][38]." </td><td>";
	dropdownYesNoInt("use_soft_dict",$data["use_soft_dict"]);
	echo "</td></tr>";
	echo "</td></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["ocsconfig"][40]." </td><td>";
	echo "<select name='cron_sync_number'>";
	for ($i=0;$i<100;$i++){
		echo "<option value='$i' ".($i==$data["cron_sync_number"]?" selected":"").">$i</option>";
	}
	echo "</select>";

	echo "</td></tr>";

	echo "</table></div>";

	echo "<div align='center'>".$lang["ocsconfig"][15]."</div>";
	echo "<div align='center'>".$lang["ocsconfig"][14]."</div>";
	echo "<div align='center'>".$lang["ocsconfig"][13]."</div>";

	echo "<br />";

	echo "<div align='center'><table class='tab_cadre'>";
	echo "<tr><th>".$lang["ocsconfig"][27]."</th><th>".$lang["ocsconfig"][28]."</th></tr>";
	echo "<tr><td class='tab_bg_2' valign='top'><table width='100%' cellpadding='1' cellspacing='0' border='0'>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["ocsconfig"][39]." </td><td>";
	echo "<select name='import_tag_field'>";
	echo "<option value=''>".$lang["ocsconfig"][11]."</option>";
	echo "<option value='otherserial' ".($data["import_tag_field"]=="otherserial"?"selected":"").">".$lang["common"][20]."</option>";
	echo "<option value='contact_num' ".($data["import_tag_field"]=="contact_num"?"selected":"").">".$lang["common"][21]."</option>";
	echo "<option value='location' ".($data["import_tag_field"]=="location"?"selected":"").">".$lang["common"][15]."</option>";
	echo "<option value='network' ".($data["import_tag_field"]=="network"?"selected":"").">".$lang["setup"][88]."</option>";
	echo "</select>";
	echo "</td></tr>";


	echo "<tr class='tab_bg_2'><td align='center'>".$lang["common"][16]." </td><td>";
	dropdownYesNoInt("import_general_name",$data["import_general_name"]);
	echo "</td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["computers"][9]." </td><td>";
	dropdownYesNoInt("import_general_os",$data["import_general_os"]);
	echo "</td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["common"][19]." </td><td>";
	dropdownYesNoInt("import_general_serial",$data["import_general_serial"]);
	echo "</td></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["common"][22]." </td><td>";
	dropdownYesNoInt("import_general_model",$data["import_general_model"]);
	echo "</td></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["common"][5]." </td><td>";
	dropdownYesNoInt("import_general_enterprise",$data["import_general_enterprise"]);
	echo "</td></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["common"][17]." </td><td>";
	dropdownYesNoInt("import_general_type",$data["import_general_type"]);
	echo "</td></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][89]." </td><td>";
	dropdownYesNoInt("import_general_domain",$data["import_general_domain"]);
	echo "</td></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["common"][18]." </td><td>";
	dropdownYesNoInt("import_general_contact",$data["import_general_contact"]);
	echo "</td></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["common"][25]." </td><td>";
	dropdownYesNoInt("import_general_comments",$data["import_general_comments"]);
	echo "</td></tr>";

	echo "<tr class='tab_bg_2'><td colspan='2'>&nbsp;";
	echo "</td></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["networking"][14]." </td><td>";
	dropdownYesNoInt("import_ip",$data["import_ip"]);
	echo "</td></tr>";

	echo "</table></td>";
	echo "<td class='tab_bg_2' valign='top'><table width='100%' cellpadding='1' cellspacing='0' border='0'>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["devices"][4]." </td><td>";
	dropdownYesNoInt("import_device_processor",$data["import_device_processor"]);
	echo "</td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["devices"][6]." </td><td>";
	dropdownYesNoInt("import_device_memory",$data["import_device_memory"]);
	echo "</td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["devices"][1]." </td><td>";
	dropdownYesNoInt("import_device_hdd",$data["import_device_hdd"]);
	echo "</td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["devices"][3]." </td><td>";
	dropdownYesNoInt("import_device_iface",$data["import_device_iface"]);
	echo "</td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["devices"][2]." </td><td>";
	dropdownYesNoInt("import_device_gfxcard",$data["import_device_gfxcard"]);
	echo "</td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["devices"][7]." </td><td>";
	dropdownYesNoInt("import_device_sound",$data["import_device_sound"]);
	echo "</td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["devices"][19]." </td><td>";
	dropdownYesNoInt("import_device_drives",$data["import_device_drives"]);
	echo "</td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["ocsconfig"][36]." </td><td>";
	dropdownYesNoInt("import_device_modems",$data["import_device_modems"]);
	echo "</td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["ocsconfig"][37]." </td><td>";
	dropdownYesNoInt("import_device_ports",$data["import_device_ports"]);
	echo "</td></tr>";

	echo "</table></td></tr>";
	echo "</table></div>";
	echo "<p class=\"submit\"><input type=\"submit\" name=\"update_conf_ocs\" class=\"submit\" value=\"".$lang["buttons"][2]."\" ></p>";
	echo "</form>";

}

?>
