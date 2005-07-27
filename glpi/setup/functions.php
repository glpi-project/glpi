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
// FUNCTIONS Setup

function showFormTreeDown ($target,$name,$human,$ID,$value2='',$where='',$tomove='',$type='') {

	GLOBAL $cfg_layout, $lang, $HTMLRel;

	echo "<div align='center'>&nbsp;<table class='tab_cadre' width='70%'>";
	echo "<a name=\"$name\"></a>";
	echo "<tr><th colspan='3'>$human:</th></tr>";
	if (countElementsInTable("glpi_dropdown_".$name)>0){
	echo "<form method='post' action=\"$target\">";
	echo "<input type='hidden' name='which' value='$name'>";
	echo "<tr><td  align='center'  class='tab_bg_1'>";


	$value=getTreeLeafValueName("glpi_dropdown_".$name,$ID);
//	getDropdownName("glpi_dropdown_".$name,$ID);

	dropdownValue("glpi_dropdown_".$name, "ID",$ID);
        // on ajoute un input text pour entrer la valeur modifier
		echo "<input type='image' src=\"".$HTMLRel."pics/puce.gif\" alt='' title='' name='fillright' value='fillright'>";

//        echo "<img src=\"".$HTMLRel."pics/puce.gif\" alt='' title=''>";

 	echo "<input type='text' maxlength='100' size='20' name='value' value='$value'>";
	//
	echo "</td><td align='center' class='tab_bg_2'>";
	echo "<input type='hidden' name='tablename' value='glpi_dropdown_".$name."'>";
	//  on ajoute un bouton modifier
        echo "<input type='submit' name='update' value='".$lang["buttons"][14]."' class='submit'>";
        echo "</td><td align='center' class='tab_bg_2'>";
        //
        echo "<input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'>";
	echo "</td></form></tr>";
	
	echo "<form method='post' action=\"$target\">";
	echo "<input type='hidden' name='which' value='$name'>";
	echo "<tr><td align='center' class='tab_bg_1'>";

	dropdownValue("glpi_dropdown_".$name, "value_to_move",$tomove);
//		echo "<select name='type'>";
//		echo "<option value='under'>".$lang["setup"][75]."</option>";
		echo "&nbsp;&nbsp;&nbsp;".$lang["setup"][75]." :&nbsp;&nbsp;&nbsp;";
//		echo "<option value='over'>".$lang["setup"][77]."</option>";
//		echo "<option value='same'>".$lang["setup"][76]."</option>";
//		echo "</select>";

	dropdownValue("glpi_dropdown_".$name, "value_where",$where);
	echo "</td><td align='center' colspan='2' class='tab_bg_2'>";
	echo "<input type='hidden' name='tablename' value='glpi_dropdown_".$name."' >";
	echo "<input type='submit' name='move' value=\"".$lang["buttons"][20]."\" class='submit' class='submit'>";
	
	echo "</td></form></tr>";	
	
	}
	echo "<form action=\"$target\" method='post'>";
	echo "<input type='hidden' name='which' value='$name'>";
	echo "<tr><td  align='center'  class='tab_bg_1'>";
		echo "<input type='text' maxlength='100' size='15' name='value'>&nbsp;&nbsp;&nbsp;";

	if (countElementsInTable("glpi_dropdown_".$name)>0){
		echo "<select name='type'>";
		echo "<option value='under' ".($type=='under'?" selected ":"").">".$lang["setup"][75]."</option>";
		echo "<option value='same' ".($type=='same'?" selected ":"").">".$lang["setup"][76]."</option>";
		echo "</select>&nbsp;&nbsp;&nbsp;";
;
		dropdownValue("glpi_dropdown_".$name, "value2",$value2);
		}		
	else echo "<input type='hidden' name='type' value='first'>";
	 		
	echo "</td><td align='center' colspan='2' class='tab_bg_2'>";
	echo "<input type='hidden' name='tablename' value='glpi_dropdown_".$name."' >";
	echo "<input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit' class='submit'>";
	echo "</td></tr>";
	
	
	
	echo "</table></div>";
	echo "</form>";
}


function showFormDropDown ($target,$name,$human,$ID,$value2='') {

	GLOBAL $cfg_layout, $lang, $HTMLRel;

	echo "<div align='center'>&nbsp;<table class='tab_cadre' width='70%'>";
	echo "<a name=\"$name\"></a>";
	echo "<tr><th colspan='3'>$human:</th></tr>";
	if (countElementsInTable("glpi_dropdown_".$name)>0){
	echo "<form method='post' action=\"$target\">";
	echo "<input type='hidden' name='which' value='$name'>";
	echo "<tr><td align='center' class='tab_bg_1'>";

	dropdownValue("glpi_dropdown_".$name, "ID",$ID);
        // on ajoute un input text pour entrer la valeur modifier
		echo "<input type='image' src=\"".$HTMLRel."pics/puce.gif\" alt='' title='' name='fillright' value='fillright'>";

//        echo "<img src=\"".$HTMLRel."pics/puce.gif\" alt='' title=''>";
	if ($name != "netpoint"){
		if (!empty($ID))
			$value=getDropdownName("glpi_dropdown_".$name,$ID);
		else $value="";
	} else {$value="";$loc="";}

	if($name == "netpoint") {
		$db=new DB;
		$query = "select * from glpi_dropdown_netpoint where ID = '". $ID ."'";
		$result = $db->query($query);
		
		if($db->numrows($result) == 1) {
		$value = $db->result($result,0,"name");
		$loc = $db->result($result,0,"location");
		}
		echo "<br>";
		echo $lang["networking"][1].": ";		
		dropdownValue("glpi_dropdown_locations", "value2",$loc);
		echo $lang["networking"][52].": ";
		echo "<input type='text' maxlength='100' size='10' name='value' value='$value'>";
	} 
	else {
        	echo "<input type='text' maxlength='100' size='20' name='value' value='$value'>";
        }
	//
	echo "</td><td align='center' class='tab_bg_2'>";
	echo "<input type='hidden' name='tablename' value='glpi_dropdown_".$name."'>";
	//  on ajoute un bouton modifier
        echo "<input type='submit' name='update' value='".$lang["buttons"][14]."' class='submit'>";
        echo "</td><td align='center' class='tab_bg_2'>";
        //
        echo "<input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'>";
	echo "</td></form></tr>";
	
	}
	echo "<form action=\"$target\" method='post'>";
	echo "<input type='hidden' name='which' value='$name'>";
	echo "<tr><td align='center'  class='tab_bg_1'>";
	if($name == "netpoint") {
		echo $lang["networking"][1].": ";		
		dropdownValue("glpi_dropdown_locations", "value2",$value2);
		echo $lang["networking"][52].": ";
		echo "<input type='text' maxlength='100' size='10' name='value'>";
	}
	else {
		echo "<input type='text' maxlength='100' size='20' name='value'>";
	}
	echo "</td><td align='center' colspan='2' class='tab_bg_2'>";
	echo "<input type='hidden' name='tablename' value='glpi_dropdown_".$name."' >";
	echo "<input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit' class='submit'>";
	echo "</td></tr>";
	
	// Multiple Add for Netpoint
	if($name == "netpoint") {
		echo "<form action=\"$target\" method='post'>";
		echo "<input type='hidden' name='which' value='$name'>";
		echo "<tr><td align='center'  class='tab_bg_1'>";

		echo $lang["networking"][1].": ";		
		dropdownValue("glpi_dropdown_locations", "value2",$value2);
		echo $lang["networking"][52].": ";
		echo "<input type='text' maxlength='100' size='5' name='before'>";
		echo "<select name='from'>";
		for ($i=0;$i<200;$i++) echo "<option value='$i'>$i</option>";
		echo "</select>";
		echo "-->";
		echo "<select name='to'>";
		for ($i=0;$i<200;$i++) echo "<option value='$i'>$i</option>";
		echo "</select>";

		echo "<input type='text' maxlength='100' size='5' name='after'>";	

		echo "</td><td align='center' colspan='2' class='tab_bg_2'>";
		echo "<input type='hidden' name='tablename' value='glpi_dropdown_".$name."' >";
		echo "<input type='submit' name='several_add' value=\"".$lang["buttons"][8]."\" class='submit' class='submit'>";
		echo "</td></tr>";
	}
	
	
	echo "</form>";
	echo "</table></div>";
}

function showFormTypeDown ($target,$name,$human,$ID) {

	GLOBAL $cfg_layout, $lang, $HTMLRel;
	
	echo "<div align='center'>&nbsp;<table class='tab_cadre' width=70%>";
	echo "<a name=\"$name\"></a>";
	echo "<tr><th colspan='3'>$human:</th></tr>";
	if (countElementsInTable("glpi_type_".$name)>0){
	echo "<form method='post' action=\"$target\">";
	echo "<input type='hidden' name='which' value='$name'>";
	echo "<tr><td align='center' class='tab_bg_1'>";

	dropdownValue("glpi_type_".$name, "ID",$ID);
	// on ajoute un input text pour entrer la valeur modifier
		echo "<input type='image' src=\"".$HTMLRel."pics/puce.gif\" alt='' title='' name='fillright' value='fillright'>";

	if (!empty($ID))
		$value=getDropdownName("glpi_type_".$name,$ID);
	else $value="";

    echo "<input type='text' maxlength='100' size='20' name='value'  value='$value'>";


	echo "</td><td align='center' class='tab_bg_2'>";
	echo "<input type='hidden' name='tablename' value=\"glpi_type_".$name."\"/>";
	//  on ajoute un bouton modifier
        echo "<input type='submit' name='update' value='".$lang["buttons"][14]."' class='submit'>";
	echo "</td><td align='center' class='tab_bg_2'>";
        echo "<input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'>";
	echo "</td></form></tr>";
	}
	echo "<form action=\"$target\" method='post'>";
	echo "<input type='hidden' name='which' value='$name'>";
	echo "<tr><td align='center' class='tab_bg_1'>";
	echo "<input type='text' maxlength='100' size='20' name='value'>";
	echo "</td><td align='center' colspan='2' class='tab_bg_2'>";
	echo "<input type='hidden' name='tablename' value='glpi_type_".$name."'>";
	echo "<input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'>";
	echo "</td></form></tr>";
	echo "</table></div>";
}
function moveTreeUnder($table,$to_move,$where){
	$db=new DB();
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
	global $dropdowntree_tables;
	
	$db = new DB;
	
	if($input["tablename"] == "glpi_dropdown_netpoint") {
		$query = "update ".$input["tablename"]." SET name = '".$input["value"]."', location = '".$input["value2"]."' where ID = '".$input["ID"]."'";
		
	}
	else {
		$query = "update ".$input["tablename"]." SET name = '".$input["value"]."' where ID = '".$input["ID"]."'";
	}
	
	if ($result=$db->query($query)) {
		if (in_array($input["tablename"],$dropdowntree_tables))
			regenerateTreeCompleteNameUnderID($input["tablename"],$input["ID"]);
		return true;
	} else {
		return false;
	}
}


function addDropdown($input) {
	global $dropdowntree_tables;
	
	if (!empty($input["value"])){
	$db = new DB;

	if($input["tablename"] == "glpi_dropdown_netpoint") {
		$query = "INSERT INTO ".$input["tablename"]." (name,location) VALUES ('".$input["value"]."', '".$input["value2"]."')";
	}
	else if (in_array($input["tablename"],$dropdowntree_tables)){
		if ($input['type']=="first"){
		    $query = "INSERT INTO ".$input["tablename"]." (name,parentID) VALUES ('".$input["value"]."', '0')";		
		} else {
			$query="SELECT * from ".$input["tablename"]." where ID='".$input["value2"]."'";
			$result=$db->query($query);
			if ($db->numrows($result)>0){
				$data=$db->fetch_array($result);
				$level_up=$data["parentID"];
				if ($input["type"]=="under") {
					$level_up=$data["ID"];
				} 
				$query = "INSERT INTO ".$input["tablename"]." (name,parentID) VALUES ('".$input["value"]."', '$level_up')";		
			} else $query = "INSERT INTO ".$input["tablename"]." (name,parentID) VALUES ('".$input["value"]."', '0')";				
		}
	}
	else {
		$query = "INSERT INTO ".$input["tablename"]." (name) VALUES ('".$input["value"]."')";
	}

	if ($result=$db->query($query)) {

		if (in_array($input["tablename"],$dropdowntree_tables))
			regenerateTreeCompleteNameUnderID($input["tablename"],$db->insert_id());		
		return true;
	} else {
		return false;
	}
}
}

function deleteDropdown($input) {

	$db = new DB;
	$send = array();
	$send["tablename"] = $input["tablename"];
	$send["oldID"] = $input["ID"];
	$send["newID"] = "NULL";
	replaceDropDropDown($send);
}

//replace all entries for a dropdown in each items
function replaceDropDropDown($input) {
	$db = new DB;
	$name = getDropdownNameFromTable($input["tablename"]);
	switch($name) {
	case "cartridge_type":
		$query = "update glpi_cartridges_type set type = '". $input["newID"] ."'  where type = '".$input["oldID"]."'";
		$db->query($query);
		break;
	case "contact_type":
		$query = "update glpi_contacts set type = '". $input["newID"] ."'  where type = '".$input["oldID"]."'";
		$db->query($query);
		break;
	case "contract_type":
		$query = "update glpi_contracts set contract_type = '". $input["newID"] ."'  where contract_type = '".$input["oldID"]."'";
		$db->query($query);
		break;
	case "enttype":
		$query = "update glpi_enterprises set type = '". $input["newID"] ."'  where type = '".$input["oldID"]."'";
		$db->query($query);
		break;
	case "firmware":
		$query = "update glpi_networking set firmware = '". $input["newID"] ."'  where firmware = '".$input["oldID"]."'";
		$db->query($query);
		break;
	case "os" :
		$query = "update glpi_computers set ". $name ." = '". $input["newID"] ."'  where ". $name ." = '".$input["oldID"]."'";
		$db->query($query);
		$query = "update glpi_software set ". $name ." = '". $input["newID"] ."'  where ". $name ." = '".$input["oldID"]."'";
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
		$query = "update glpi_networking_ports set netpoint = '". $input["newID"] ."'  where netpoint = '".$input["oldID"]."'";
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
	case "state" :
		$query = "update glpi_state_item set state = '". $input["newID"] ."'  where state = '".$input["oldID"]."'";
		$result = $db->query($query);
		break;
	}

	$query = "delete from ". $input["tablename"] ." where ID = '". $input["oldID"] ."'";
	$db->query($query);
}

function showDeleteConfirmForm($target,$table, $ID) {
	global $lang;
	
	if ($table=="glpi_dropdown_locations"){
		$db=new DB();
		$query = "Select count(*) as cpt FROM $table where parentID = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  {
		echo "<center><p style='color:red'>".$lang["setup"][74]."</p></center>";
		return;
		}
	}	

	if ($table=="glpi_dropdown_kbcategories"){
	echo "<center><p style='color:red'>".$lang["setup"][74]."</p></center>";
	return;
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
	echo "<input class='button' type=\"submit\" name=\"delete\" value=\"Confirmer\" /></td>";
	
	echo "<form action=\" ". $target ."\" method=\"post\">";
	echo "<td><input class='button' type=\"submit\" name=\"annuler\" value=\"Annuler\" /></td></tr></table>";
	echo "</form>";
	echo "<p>". $lang["setup"][65]."</p>";
	echo "<form action=\" ". $target ."\" method=\"post\">";
	echo "<input type=\"hidden\" name=\"which\" value=\"". str_replace("glpi_type_","",str_replace("glpi_dropdown_","",$table)) ."\"  />";
	echo "<table class='tab_cadre'><tr><td>";
	dropdownNoValue($table,"newID",$ID);
	echo "<input type=\"hidden\" name=\"tablename\" value=\"". $table ."\"  />";
	echo "<input type=\"hidden\" name=\"oldID\" value=\"". $ID ."\"  />";
	echo "</td><td><input class='button' type=\"submit\" name=\"replace\" value=\"Remplacer\" /></td></tr></table>";
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

	$db = new DB;
	$name = getDropdownNameFromTable($table);

	$var1 = true;
	switch($name) {
	case "cartridge_type":
		$query = "Select count(*) as cpt FROM glpi_cartridges_type where type = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
	case "contact_type":
		$query = "Select count(*) as cpt FROM glpi_contacts where type = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
	case "contract_type":
		$query = "Select count(*) as cpt FROM glpi_contracts where contract_type = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
	case "enttype":
		$query = "Select count(*) as cpt FROM glpi_enterprises where type = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
	case "firmware":
		$query = "Select count(*) as cpt FROM glpi_networking where firmware = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
	case "iface" : 
		$query = "Select count(*) as cpt FROM glpi_networking_ports where ". $name ." = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
	case "kbcategories" :
		$query = "Select count(*) as cpt FROM glpi_dropdown_kbcategories where parentID = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		$query = "Select count(*) as cpt FROM glpi_kbitems where categoryID = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;

		break;
	case "location" :
		$query = "Select count(*) as cpt FROM glpi_dropdown_locations where parentID = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
	
		$query = "Select count(*) as cpt FROM glpi_computers where ". $name ." = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		$query = "Select count(*) as cpt FROM glpi_monitors where ". $name ." = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		$query = "Select count(*) as cpt FROM glpi_printers where ". $name ." = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		$query = "Select count(*) as cpt FROM glpi_software where ". $name ." = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		$query = "Select count(*) as cpt FROM glpi_networking where ". $name ." = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		$query = "Select count(*) as cpt FROM glpi_peripherals where ". $name ." = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		$query = "Select count(*) as cpt FROM glpi_dropdown_netpoint where ". $name ." = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		$query = "Select count(*) as cpt FROM glpi_cartridges_type where ". $name ." = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		$query = "Select count(*) as cpt FROM glpi_users where ". $name ." = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;

		break;
	case "netpoint" : 
		$query = "Select count(*) as cpt FROM glpi_networking_ports where netpoint = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
	case "os" :
		$query = "Select count(*) as cpt FROM glpi_computers where ". $name ." = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		$query = "Select count(*) as cpt FROM glpi_software where platform = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;	
		break;
	case "rubdocs":
		$query = "Select count(*) as cpt FROM glpi_docs where rubrique = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
	case "state":
		$query = "Select count(*) as cpt FROM glpi_state_item where state = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;


	case "tracking_category":
		$query = "Select count(*) as cpt FROM glpi_tracking where category = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
	case "computers" :
		$query = "Select count(*) as cpt FROM glpi_computers where type = '".$ID."'";
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
	case "printers" :
		$query = "Select count(*) as cpt FROM glpi_printers where type = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		$query = "Select count(*) as cpt FROM glpi_cartridges_assoc where FK_glpi_type_printer = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;

		break;

	}
	return $var1;

}












function listTemplates($type,$target) {

	GLOBAL $cfg_layout, $lang;

	$db = new DB;
	
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
	
	}
	if ($result = $db->query($query)) {
		
		echo "<div align='center'><table class='tab_cadre' width='50%'>";
		echo "<tr><th colspan='2'>".$lang["setup"][1]." - $title:</th></tr>";
		$i=0;
		while ($i < $db->numrows($result)) {
			$ID = $db->result($result,$i,"ID");
			$templname = $db->result($result,$i,"tplname");
			
			echo "<tr>";
			echo "<td align='center' class='tab_bg_1'>";
			echo "<a href=\"$target?ID=$ID&withtemplate=1\">&nbsp;&nbsp;&nbsp;$templname&nbsp;&nbsp;&nbsp;</a></td>";
			echo "<td align='center' class='tab_bg_2'>";
			if ($templname!="Blank Template")
			echo "<b><a href=\"$target?ID=$ID&purge=purge&withtemplate=1\">".$lang["buttons"][6]."</a></b>";
			else echo "&nbsp;";
			
			echo "</td>";
			echo "</tr>";		

			$i++;
		}

		echo "<tr>";
		echo "<td colspan='2' align='center' class='tab_bg_2'>";
		echo "<b><a href=\"$target?withtemplate=1\">".$lang["setup"][22]."</a></b>";
		echo "</td>";
		echo "</tr>";
		echo "</form>";
				
		echo "</table></div>";
	}
	

}

function showSortForm($target) {

	GLOBAL $cfg_layout, $lang;
	
	$order = $_SESSION["tracking_order"];
	
	echo "<div align='center'>&nbsp;<table class='tab_cadre' cellpadding='5' width='30%'>";
	echo "<form method='post' action=\"$target\">";
	echo "<tr><th colspan='2'>".$lang["setup"][40]."</th></tr>";
	echo "<tr><td width='100%' align='center' class='tab_bg_1'>";
	echo "<select name='tracking_order'>";
	echo "<option value=\"yes\"";
	if ($order=="yes") { echo " selected"; }	
	echo ">".$lang["choice"][1];
	echo "<option value=\"no\"";
	if ($order=="no") { echo " selected"; }
	echo ">".$lang["choice"][0];
	echo "</select>";
	echo "</td>";
	echo "<td align='center' class='tab_bg_2'>";
	echo "<input type='submit' name='updatesort' value=\"".$lang["buttons"][14]."\" class='submit'>";
	echo "</td></tr>";
	echo "</form>";
	echo "</table></div>";
}



function titleConfigGen(){

GLOBAL  $lang,$HTMLRel;

                echo "<div align='center'><table border='0'><tr><td>";
                echo "<img src=\"".$HTMLRel."pics/configuration.png\" alt='' title=''></td><td><b><span class='icon_nav'>".$lang["setup"][100]."</span>";
		 echo "</b></td></tr></table>&nbsp;</div>";


}


function showFormConfigGen($target){
	
	GLOBAL  $lang,$HTMLRel,$cfg_install;
	
	$db = new DB;
	$query = "select * from glpi_config where ID = 1";
	$result = $db->query($query);
	
	echo "<form name='form' action=\"$target\" method=\"post\">";
	echo "<div align='center'><table class='tab_cadre'>";
	echo "<tr><th colspan='2'>".$lang["setup"][100]."</th></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][101]." </td><td> <input type=\"text\" name=\"root_doc\" value=\"". $db->result($result,0,"root_doc") ."\"></td></tr>";
	$default_language=$db->result($result,0,"default_language");
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][113]." </td><td><select name=\"default_language\">";
	//foreach ($cfg_install["languages"] as $key => $val){
	//echo "<option value=\"$val\"";  if($default_language==$val){ echo " selected";} echo ">".$val." </option>";
	//}
		while (list($val)=each($cfg_install["languages"])){
		echo "<option value=\"".$val."\"";
			if($default_language==$val){ echo " selected";}
		echo ">".$cfg_install["languages"][$val][0];
		}
	
	echo "</select></td></tr>";
	
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][102]." </td><td><select name=\"event_loglevel\">";
	$level=$db->result($result,0,"event_loglevel");
	echo "<option value=\"1\"";  if($level==1){ echo " selected";} echo ">".$lang["setup"][103]." </option>";
	echo "<option value=\"2\"";  if($level==2){ echo " selected";} echo ">".$lang["setup"][104]."</option>";
	echo "<option value=\"3\"";  if($level==3){ echo " selected";} echo ">".$lang["setup"][105]."</option>";
	echo "<option value=\"4\"";  if($level==4){ echo " selected";} echo ">".$lang["setup"][106]." </option>";
	echo "<option value=\"5\"";  if($level==5){ echo " selected";} echo ">".$lang["setup"][107]."</option>";
	echo "</select></td></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][108]."</td><td> <input type=\"text\" name=\"num_of_events\" value=\"". $db->result($result,0,"num_of_events") ."\"></td></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][109]." </td><td><input type=\"text\" name=\"expire_events\" value=\"". $db->result($result,0,"expire_events") ."\"></td></tr>";
	echo "<tr class='tab_bg_2'><td align='center'> ".$lang["setup"][110]." </td><td>   &nbsp;".$lang["choice"][0]."  &nbsp;<input type=\"radio\" name=\"jobs_at_login\" value=\"1\" "; if($db->result($result,0,"jobs_at_login") == 1) echo "checked=\"checked\""; echo " /> &nbsp;".$lang["choice"][1]."  &nbsp;<input type=\"radio\" name=\"jobs_at_login\" value=\"0\" "; if($db->result($result,0,"jobs_at_login") == 0) echo "checked"; 
	echo " ></td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][111]."</td><td> <input type=\"text\" name=\"list_limit\" value=\"". $db->result($result,0,"list_limit") ."\"></td></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][112]."</td><td><input type=\"text\" name=\"cut\" value=\"". $db->result($result,0,"cut") ."\"></td></tr>";

	
	$plan_begin=split(":",$db->result($result,0,"planning_begin"));
	$plan_end=split(":",$db->result($result,0,"planning_end"));
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][223]."</td><td>";
	echo "<select name='planning_begin'>";
	for ($i=0;$i<=24;$i++) echo "<option value='$i'".($plan_begin[0]==$i?" selected ":"").">$i</option>";
	echo "</select>-->";
	
	echo "<select name='planning_end'>";
	for ($i=0;$i<=24;$i++) echo "<option value='$i' ".($plan_end[0]==$i?" selected ":"").">$i</option>";
	echo "</select>";
	
	echo "</td></tr>";
	
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][221]."</td><td>";
	showCalendarForm("form","date_fiscale",$db->result($result,0,"date_fiscale"),0);	
	echo "</td></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][219]."</td><td>&nbsp;".$lang["choice"][0]."<input type=\"radio\" name=\"permit_helpdesk\" value=\"1\""; if($db->result($result,0,"permit_helpdesk") == 1) echo "checked=\"checked\""; echo " />&nbsp;".$lang["choice"][1]."<input type=\"radio\" name=\"permit_helpdesk\" value=\"0\""; if($db->result($result,0,"permit_helpdesk") == 0) echo "checked=\"checked\""; echo" /></td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][114]."</td><td>";
	echo "<table><tr>";
	echo "<td bgcolor='".$db->result($result,0,"priority_1")."'>1:<input type=\"text\" name=\"priority[1]\" size='7' value=\"".$db->result($result,0,"priority_1")."\"></td>";
	echo "<td bgcolor='".$db->result($result,0,"priority_2")."'>2:<input type=\"text\" name=\"priority[2]\" size='7' value=\"".$db->result($result,0,"priority_2")."\"></td>";
	echo "<td bgcolor='".$db->result($result,0,"priority_3")."'>3:<input type=\"text\" name=\"priority[3]\" size='7' value=\"".$db->result($result,0,"priority_3")."\"></td>";
	echo "<td bgcolor='".$db->result($result,0,"priority_4")."'>4:<input type=\"text\" name=\"priority[4]\" size='7' value=\"".$db->result($result,0,"priority_4")."\"></td>";
	echo "<td bgcolor='".$db->result($result,0,"priority_5")."'>5:<input type=\"text\" name=\"priority[5]\" size='7' value=\"".$db->result($result,0,"priority_5")."\"></td>";
	echo "</tr></table>";
	echo "</td></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][115]."</td><td><select name='cartridges_alarm'>";
	for ($i=1;$i<=100;$i++)
		echo "<option value='$i' ".($i==$db->result($result,0,"cartridges_alarm")?" selected ":"").">$i</option>";
	echo "</select></td></tr>";
	
		echo "</table>&nbsp;</div>";	
	echo "<p class=\"submit\"><input type=\"submit\" name=\"update_confgen\" class=\"submit\" value=\"".$lang["buttons"][7]."\" ></p>";

	
	echo "</form>";
}





function titleExtSources(){
// Un titre pour la gestion des sources externes
		
		GLOBAL  $lang,$HTMLRel;
                echo "<div align='center'><table border='0'><tr><td>";
                echo "<img src=\"".$HTMLRel."pics/authentification.png\" alt='' title=''></td><td><b><span class='icon_nav'>".$lang["setup"][150]."</span>";
		 echo "</b></td></tr></table>&nbsp;</div>";

}





function showMailServerConfig($value){
global $lang;
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

function showFormExtSources($target) {

	GLOBAL  $lang,$HTMLRel;
	
	$db = new DB;
	$query = "select * from glpi_config where ID = 1";
	$result = $db->query($query);
	
	echo "<form action=\"$target\" method=\"post\">";
	
	if(function_exists('imap_open')) {

		echo "<div align='center'>";
		echo "<p >".$lang["setup"][160]."</p>";
//		echo "<p>".$lang["setup"][161]."</p>";
		echo "<table class='tab_cadre'>";
		echo "<tr><th colspan='2'>".$lang["setup"][162]."</th></tr>";
		echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][164]."</td><td><input size='30' type=\"text\" name=\"imap_host\" value=\"". $db->result($result,0,"imap_host") ."\" ></td></tr>";

		showMailServerConfig($db->result($result,0,"imap_auth_server"));
//		echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][163]."</td><td><input type=\"text\" name=\"imap_auth_server\" value=\"". $db->result($result,0,"imap_auth_server") ."\" ></td></tr>";
		echo "</table>&nbsp;</div>";
	}
	else {
		echo "<input type=\"hidden\" name=\"IMAP_Test\" value=\"1\" >";
		
		echo "<div align='center'>&nbsp;<table class='tab_cadre' width='400'>";
		echo "<tr><th colspan='2'>".$lang["setup"][162]."</th></tr>";
		echo "<tr class='tab_bg_2'><td align='center'><p class='red'>".$lang["setup"][165]."</p><p>".$lang["setup"][166]."</p></td></tr></table></div>";
	}
	if(extension_loaded('ldap'))
	{
		echo "<div align='center'><p > ".$lang["setup"][151]."</p>";

		echo "<table class='tab_cadre'>";
		echo "<tr><th colspan='2'>".$lang["setup"][152]."</th></tr>";
		echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][153]."</td><td><input type=\"text\" name=\"ldap_host\" value=\"". $db->result($result,0,"ldap_host") ."\"></td></tr>";
		echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][172]."</td><td><input type=\"text\" name=\"ldap_port\" value=\"". $db->result($result,0,"ldap_port") ."\"></td></tr>";
		echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][154]."</td><td><input type=\"text\" name=\"ldap_basedn\" value=\"". $db->result($result,0,"ldap_basedn") ."\" ></td></tr>";
		echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][155]."</td><td><input type=\"text\" name=\"ldap_rootdn\" value=\"". $db->result($result,0,"ldap_rootdn") ."\" ></td></tr>";
		echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][156]."</td><td><input type=\"password\" name=\"ldap_pass\" value=\"". $db->result($result,0,"ldap_pass") ."\" ></td></tr>";
		echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][159]."</td><td><input type=\"text\" name=\"ldap_condition\" value=\"". $db->result($result,0,"ldap_condition") ."\" ></td></tr>";
		echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][167]."</td><td>&nbsp;</td></tr>";
		echo "<tr class='tab_bg_2'><td align='center'>name</td><td><input type=\"text\" name=\"ldap_field_name\" value=\"". $db->result($result,0,"ldap_field_name") ."\" ></td></tr>";
		echo "<tr class='tab_bg_2'><td align='center'>email</td><td><input type=\"text\" name=\"ldap_field_email\" value=\"". $db->result($result,0,"ldap_field_email") ."\" ></td></tr>";
		echo "<tr class='tab_bg_2'><td align='center'>location</td><td><input type=\"text\" name=\"ldap_field_location\" value=\"". $db->result($result,0,"ldap_field_location") ."\" ></td></tr>";
		echo "<tr class='tab_bg_2'><td align='center'>phone</td><td><input type=\"text\" name=\"ldap_field_phone\" value=\"". $db->result($result,0,"ldap_field_phone") ."\" ></td></tr>";
		echo "<tr class='tab_bg_2'><td align='center'>realname</td><td><input type=\"text\" name=\"ldap_field_realname\" value=\"". $db->result($result,0,"ldap_field_realname") ."\" ></td></tr>";
		
		echo "</table>&nbsp;</div>";
	}
	else {
		echo "<input type=\"hidden\" name=\"LDAP_Test\" value=\"1\" >";
		echo "<div align='center'><table class='tab_cadre' width='400'>";
		echo "<tr><th colspan='2'>".$lang["setup"][152]."</th></tr>";
		echo "<tr class='tab_bg_2'><td align='center'><p class='red'>".$lang["setup"][157]."</p><p>".$lang["setup"][158]."</p></td></th></table></div>";
	}

	if(extension_loaded('curl')&&extension_loaded('domxml'))
	{
		echo "<div align='center'><p > ".$lang["setup"][173]."</p>";

		echo "<table class='tab_cadre'>";
		echo "<tr><th colspan='2'>".$lang["setup"][177]."</th></tr>";
		echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][174]."</td><td><input type=\"text\" name=\"cas_host\" value=\"". $db->result($result,0,"cas_host") ."\"></td></tr>";
		echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][175]."</td><td><input type=\"text\" name=\"cas_port\" value=\"". $db->result($result,0,"cas_port") ."\"></td></tr>";
		echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][176]."</td><td><input type=\"text\" name=\"cas_uri\" value=\"". $db->result($result,0,"cas_uri") ."\" ></td></tr>";
		
		echo "</table>&nbsp;</div>";
	}
	else {
		echo "<input type=\"hidden\" name=\"CAS_Test\" value=\"1\" >";
		echo "<div align='center'><table class='tab_cadre' width='400'>";
		echo "<tr><th colspan='2'>".$lang["setup"][177]."</th></tr>";
		echo "<tr class='tab_bg_2'><td align='center'><p class='red'>".$lang["setup"][178]."</p><p>".$lang["setup"][179]."</p></td></th></table></div>";
	}
	
	echo "<p class=\"submit\"><input type=\"submit\" name=\"update_ext\" class=\"submit\" value=\"".$lang["buttons"][2]."\" ></p>";
	echo "</form>";
}


function titleMailing(){
// Un titre pour la gestion du suivi par mail
		
		GLOBAL  $lang,$HTMLRel;
                echo "<div align='center'><table border='0'><tr><td>";
                echo "<img src=\"".$HTMLRel."pics/mail.png\" alt='' title=''></td><td><span class='icon_nav'>".$lang["setup"][200]."</span>";
		 echo "</b></td></tr></table></div>";
}


function showFormMailing($target) {
	
	global $lang;
		$db = new DB;
		$query = "select * from glpi_config where ID = 1";
		$result = $db->query($query);
		echo "<form action=\"$target\" method=\"post\">";
		
		
		echo "<div align='center'><table class='tab_cadre' width='600''><tr><th colspan='3'>".$lang["setup"][201]."</th></tr>";
		
			if (function_exists('mail')) {
		echo "<tr class='tab_bg_2'><td >".$lang["setup"][202]."</td><td align='center'>&nbsp; ".$lang["choice"][0]."  &nbsp;<input type=\"radio\" name=\"mailing\" value=\"1\" "; if($db->result($result,0,"mailing") == 1) echo "checked"; echo " > &nbsp;".$lang["choice"][1]."  &nbsp;<input type=\"radio\" name=\"mailing\" value=\"0\" "; if($db->result($result,0,"mailing") == 0) echo "checked"; echo " ></td></tr>";
		echo "<tr class='tab_bg_2'><td >".$lang["setup"][203]."</td><td> <input type=\"text\" name=\"admin_email\" size='40' value=\"".$db->result($result,0,"admin_email")."\"> </td></tr>";
		echo "<tr class='tab_bg_2'><td >".$lang["setup"][204]."</td><td><input type=\"text\" name=\"mailing_signature\" size='40' value=\"".$db->result($result,0,"mailing_signature")."\" ></td></tr></table>";
		
		echo "<p><b>".$lang["setup"][205]."</b></p>";
		
		echo "<table class='tab_cadre' width='600''><tr><th colspan='3'>".$lang["setup"][206]."<th></tr>";
		echo "<tr class='tab_bg_2'><td>".$lang["setup"][211]."</td><td> ".$lang["choice"][0]." <input type=\"radio\" name=\"mailing_new_admin\" value=\"1\" "; if($db->result($result,0,"mailing_new_admin") == 1) echo "checked"; echo " ></td><td> ".$lang["choice"][1]." <input type=\"radio\" name=\"mailing_new_admin\" value=\"0\" "; if($db->result($result,0,"mailing_new_admin") == 0) echo "checked"; echo " ></td></tr>";
		
		//echo "<tr class='tab_bg_2'><td >A chaque changement de responsable</td><td> ".$lang["choice"][0]." <input type=\"radio\" name=\"mailing_attrib_admin\" value=\"1\" "; if($db->result($result,0,"mailing_attrib_admin") == 1) echo "checked"; echo " ></td><td> ".$lang["choice"][1]." <input type=\"radio\" name=\"mailing_attrib_admin\" value=\"0\" "; if($db->result($result,0,"mailing_attrib_admin") == 0) echo "checked"; echo " ></td></tr>";
		
		echo "<tr class='tab_bg_2'><td >".$lang["setup"][212]."</td><td> ".$lang["choice"][0]." <input type=\"radio\" name=\"mailing_followup_admin\" value=\"1\" "; if($db->result($result,0,"mailing_followup_admin") == 1) echo "checked"; echo "></td><td> ".$lang["choice"][1]." <input type=\"radio\" name=\"mailing_followup_admin\" value=\"0\" "; if($db->result($result,0,"mailing_followup_admin") == 0) echo "checked"; echo " ></td></tr>";
		
		echo "<tr class='tab_bg_2'><td>".$lang["setup"][213]."</td><td> ".$lang["choice"][0]." <input type=\"radio\" name=\"mailing_finish_admin\" value=\"1\" "; if($db->result($result,0,"mailing_finish_admin") == 1) echo "checked"; echo " ></td><td> ".$lang["choice"][1]." <input type=\"radio\" name=\"mailing_finish_admin\" value=\"0\" "; if($db->result($result,0,"mailing_finish_admin") == 0) echo "checked"; echo " ></td></tr>";
		
		echo "<tr class='tab_bg_2'><th colspan='3'>".$lang["setup"][207]."</th></tr>";
		
		echo "<tr class='tab_bg_2'><td>".$lang["setup"][211]."</td><td> ".$lang["choice"][0]." <input type=\"radio\" name=\"mailing_new_all_admin\" value=\"1\" "; if($db->result($result,0,"mailing_new_all_admin") == 1) echo "checked"; echo  " ></td><td> ".$lang["choice"][1]." <input type=\"radio\" name=\"mailing_new_all_admin\" value=\"0\" "; if($db->result($result,0,"mailing_new_all_admin") == 0) echo "checked"; echo  " ></td></tr>";
		
		//echo "<tr class='tab_bg_2'><td>A chaque changement de responsable</td><td> ".$lang["choice"][0]." <input type=\"radio\" name=\"mailing_attrib_all_admin\" value=\"1\"  "; if($db->result($result,0,"mailing_attrib_all_admin") == 1) echo "checked"; echo  " ></td><td> ".$lang["choice"][1]." <input type=\"radio\" name=\"mailing_attrib_all_admin\" value=\"0\"  "; if($db->result($result,0,"mailing_attrib_all_admin") == 0) echo "checked"; echo  " ></td></tr>";
		
		echo "<tr class='tab_bg_2'><td>".$lang["setup"][212]."</td><td> ".$lang["choice"][0]." <input type=\"radio\" name=\"mailing_followup_all_admin\" value=\"1\"  "; if($db->result($result,0,"mailing_followup_all_admin") == 1) echo "checked"; echo  " ></td><td> ".$lang["choice"][1]." <input type=\"radio\" name=\"mailing_followup_all_admin\" value=\"0\"  "; if($db->result($result,0,"mailing_followup_all_admin") == 0) echo "checked"; echo  " ></td></tr>";
		
		echo "<tr class='tab_bg_2'><td>".$lang["setup"][213]."</td><td> ".$lang["choice"][0]." <input type=\"radio\" name=\"mailing_finish_all_admin\" value=\"1\"  "; if($db->result($result,0,"mailing_finish_all_admin") == 1) echo "checked"; echo  " ></td><td> ".$lang["choice"][1]." <input type=\"radio\" name=\"mailing_finish_all_admin\" value=\"0\"  "; if($db->result($result,0,"mailing_finish_all_admin") == 0) echo "checked"; echo  " ></td></tr>";
		
	
		echo "<tr'><th colspan='3'>".$lang["setup"][208]."</th></tr>";
		echo "<tr class='tab_bg_2'><td>".$lang["setup"][211]."</td><td> ".$lang["choice"][0]." <input type=\"radio\" name=\"mailing_new_all_normal\" value=\"1\"  "; if($db->result($result,0,"mailing_new_all_normal") == 1) echo "checked"; echo  " ></td><td> ".$lang["choice"][1]." <input type=\"radio\" name=\"mailing_new_all_normal\" value=\"0\"  "; if($db->result($result,0,"mailing_new_all_normal") == 0) echo "checked"; echo  " ></td></tr>";
		//echo "<tr class='tab_bg_2'><td>A chaque changement de responsable</td><td> ".$lang["choice"][0]." <input type=\"radio\" name=\"mailing_attrib_all_normal\" value=\"1\" "; if($db->result($result,0,"mailing_attrib_all_normal") == 1) echo "checked"; echo  "  ></td><td> ".$lang["choice"][1]." <input type=\"radio\" name=\"mailing_attrib_all_normal\" value=\"0\"  "; if($db->result($result,0,"mailing_attrib_all_normal") == 0) echo "checked"; echo  " ></td></tr>";
		
		echo "<tr class='tab_bg_2'><td>".$lang["setup"][212]."</td><td> ".$lang["choice"][0]." <input type=\"radio\" name=\"mailing_followup_all_normal\" value=\"1\" "; if($db->result($result,0,"mailing_followup_all_normal") == 1) echo "checked"; echo  " ></td><td> ".$lang["choice"][1]." <input type=\"radio\" name=\"mailing_followup_all_normal\" value=\"0\" "; if($db->result($result,0,"mailing_followup_all_normal") == 0) echo "checked"; echo  " ></td></tr>";
		
		echo "<tr class='tab_bg_2'><td>".$lang["setup"][213]."</td><td> ".$lang["choice"][0]." <input type=\"radio\" name=\"mailing_finish_all_normal\" value=\"1\" "; if($db->result($result,0,"mailing_finish_all_normal") == 1) echo "checked"; echo  " ></td><td> ".$lang["choice"][1]." <input type=\"radio\" name=\"mailing_finish_all_normal\" value=\"0\" "; if($db->result($result,0,"mailing_finish_all_normal") == 0) echo "checked"; echo  " ></td></tr>";
		
		
		echo "<tr><th colspan='3'>".$lang["setup"][209]."</th></tr>";
		echo "<tr class='tab_bg_2'><td>".$lang["setup"][211]."</td><td> ".$lang["choice"][0]." <input type=\"radio\" name=\"mailing_new_attrib\" value=\"1\" "; if($db->result($result,0,"mailing_new_attrib") == 1) echo "checked"; echo  " ></td><td> ".$lang["choice"][1]." <input type=\"radio\" name=\"mailing_new_attrib\" value=\"0\" "; if($db->result($result,0,"mailing_new_attrib") == 0) echo "checked"; echo  " ></td></tr>";
		
		//echo "<tr class='tab_bg_2'><td>A chaque changement de responsable</td><td> ".$lang["choice"][0]." <input type=\"radio\" name=\"mailing_attrib_attrib\" value=\"1\" "; if($db->result($result,0,"mailing_attrib_attrib") == 1) echo "checked"; echo  " ></td><td> ".$lang["choice"][1]." <input type=\"radio\" name=\"mailing_attrib_attrib\" value=\"0\"  "; if($db->result($result,0,"mailing_attrib_attrib") == 0) echo "checked"; echo  " ></td></tr>";
		
		echo "<tr class='tab_bg_2'><td>".$lang["setup"][212]."</td><td> ".$lang["choice"][0]." <input type=\"radio\" name=\"mailing_followup_attrib\" value=\"1\" "; if($db->result($result,0,"mailing_followup_attrib") == 1) echo "checked"; echo  " ></td><td> ".$lang["choice"][1]." <input type=\"radio\" name=\"mailing_followup_attrib\" value=\"0\" "; if($db->result($result,0,"mailing_followup_attrib") == 0) echo "checked"; echo  " ></td></tr>";
		echo "<tr class='tab_bg_2'><td>".$lang["setup"][213]."</td><td> ".$lang["choice"][0]." <input type=\"radio\" name=\"mailing_finish_attrib\" value=\"1\" "; if($db->result($result,0,"mailing_finish_attrib") == 1) echo "checked"; echo  " ></td><td> ".$lang["choice"][1]." <input type=\"radio\" name=\"mailing_finish_attrib\" value=\"0\" "; if($db->result($result,0,"mailing_finish_attrib") == 0) echo "checked"; echo  " ><td></td></tr>";
		
		echo "<tr><th colspan='3'>".$lang["setup"][210]."</th></tr>";
		
		echo "<tr class='tab_bg_2'><td>".$lang["setup"][214]."</td><td> ".$lang["choice"][0]." <input type=\"radio\" name=\"mailing_new_user\" value=\"1\" "; if($db->result($result,0,"mailing_new_user") == 1) echo "checked"; echo  " ></td><td> ".$lang["choice"][1]." <input type=\"radio\" name=\"mailing_new_user\" value=\"0\" "; if($db->result($result,0,"mailing_new_user") == 0) echo "checked"; echo  " ></td></tr>";
		
		//echo "<tr class='tab_bg_2'><td>A chaque changement de responsable d'une intervention le concernant</td><td> ".$lang["choice"][0]." <input type=\"radio\" name=\"mailing_attrib_user\" value=\"1\" "; if($db->result($result,0,"mailing_attrib_user") == 1) echo "checked"; echo  " ></td><td> ".$lang["choice"][1]." <input type=\"radio\" name=\"mailing_attrib_user\" value=\"0\" "; if($db->result($result,0,"mailing_attrib_user") == 0) echo "checked"; echo  " ></td></tr>";
		
		echo "<tr class='tab_bg_2'><td>".$lang["setup"][215]."</td><td> ".$lang["choice"][0]." <input type=\"radio\" name=\"mailing_followup_user\" value=\"1\" "; if($db->result($result,0,"mailing_followup_user") == 1) echo "checked"; echo  " ></td><td> ".$lang["choice"][1]." <input type=\"radio\" name=\"mailing_followup_user\" value=\"0\" "; if($db->result($result,0,"mailing_followup_user") == 0) echo "checked"; echo  " ></td></tr>";
		
		echo "<tr class='tab_bg_2'><td>".$lang["setup"][216]."</td><td> ".$lang["choice"][0]." <input type=\"radio\" name=\"mailing_finish_user\" value=\"1\"  "; if($db->result($result,0,"mailing_finish_user") == 1) echo "checked"; echo  " ></td><td> ".$lang["choice"][1]." <input type=\"radio\" name=\"mailing_finish_user\" value=\"0\" "; if($db->result($result,0,"mailing_finish_user") == 0) echo "checked"; echo  " ></td></tr>";
		echo "</table></div>";
		echo "<p class=\"submit\"><input type=\"submit\" name=\"update_mailing\" class=\"submit\" value=\"".$lang["buttons"][2]."\" ></p>";
		}
		else {
		
			
		echo "<tr class='tab_bg_2'><td align='center'><p class='red'>".$lang["setup"][217]."</p><p>".$lang["setup"][218]."</p></td></tr></table></div>";
		
		}
		
		echo "</form>";

}

function updateConfigGen($root_doc,$event_loglevel,$num_of_events,$expire_events,$jobs_at_login,$list_limit,$cut, $permit_helpdesk,$default_language,$priority,$date_fiscale,$cartridges_alarm,$planning_begin,$planning_end) {
	
	$db = new DB;
	
		$query = "update glpi_config set root_doc = '". $root_doc ."', ";
		$query.= "event_loglevel = '". $event_loglevel ."', num_of_events = '". $num_of_events ."', default_language = '". $default_language ."',";
		$query.= "expire_events = '". $expire_events ."', jobs_at_login = '". $jobs_at_login ."' , list_limit = '". $list_limit ."' , cut = '". $cut ."', permit_helpdesk='". $permit_helpdesk ."',";
		$query.= "priority_1 = '". $priority[1] ."', priority_2 = '". $priority[2] ."', priority_3 = '". $priority[3] ."', priority_4 = '". $priority[4] ."', priority_5 = '". $priority[5] ."', ";
		$query.= " date_fiscale = '". $date_fiscale ."', cartridges_alarm='".$cartridges_alarm."', ";
		$query.= " planning_begin = '". $planning_begin .":00:00', planning_end='".$planning_end.":00:00' where ID = '1' ";
		$db->query($query);
	
}


function updateLDAP($ldap_host,$ldap_basedn,$ldap_rootdn,$ldap_pass,$ldap_condition,$field_name,$field_email,$field_location,$field_phone,$field_realname,$ldap_port) {
	
	$db = new DB;
	//TODO : test the remote LDAP connection
	if(!empty($ldap_host)) {
		$query = "update glpi_config set ldap_host = '". $ldap_host ."', ";
		$query.= "ldap_basedn = '". $ldap_basedn ."', ldap_rootdn = '". $ldap_rootdn ."', ";
		$query .= "ldap_pass = '". $ldap_pass ."', ldap_condition = '". $ldap_condition ."', ";
		$query .= "ldap_field_name = '". $field_name ."', ldap_field_email = '". $field_email ."', ";
		$query .= "ldap_field_location = '". $field_location ."', ldap_field_phone = '". $field_phone ."', ";
		$query .= "ldap_field_realname = '". $field_realname ."', ldap_port = '". $ldap_port ."' ";
		$query.= " where ID = '1' ";
		$db->query($query);
	}
}
function updateIMAP($imap_auth_server,$imap_host) {
	$db = new DB;
	//TODO : test the remote IMAP connection
		$query = "update glpi_config set imap_auth_server = '". $imap_auth_server ."', ";
		$query.= "imap_host = '". $imap_host ."' where ID = '1'";
		$db->query($query);
}

function updateCAS($cas_host,$cas_port,$cas_uri) {
	$db = new DB;
	//TODO : test the remote IMAP connection
		$query = "update glpi_config set cas_host = '". $cas_host ."', ";
		$query.= "cas_uri = '". $cas_uri."',";
		$query.= "cas_port = '". $cas_port ."' where ID = '1'";
		$db->query($query);
}

function updateMailing($mailing,$admin_email, $mailing_signature,$mailing_new_admin,$mailing_followup_admin,$mailing_finish_admin,$mailing_new_all_admin,$mailing_followup_all_admin,$mailing_finish_all_admin,$mailing_new_all_normal,$mailing_followup_all_normal,$mailing_finish_all_normal,$mailing_followup_attrib,$mailing_finish_attrib,$mailing_new_user,$mailing_followup_user,$mailing_finish_user,$mailing_new_attrib) {

	$db = new DB;
	$query = "update glpi_config set mailing = '". $mailing ."', ";
	$query .= "admin_email = '". $admin_email ."', ";
	$query .= "mailing_signature = '". $mailing_signature ."', ";
	$query .= "mailing_new_admin = '". $mailing_new_admin ."', ";
	$query .= "mailing_followup_admin = '". $mailing_followup_admin ."', ";
	$query .= "mailing_finish_admin = '". $mailing_finish_admin ."', ";
	$query .= "mailing_new_all_admin = '". $mailing_new_all_admin ."', ";
	$query .= "mailing_followup_all_admin = '". $mailing_followup_all_admin ."', ";
	$query .= "mailing_finish_all_admin = '". $mailing_finish_all_admin ."', ";
	$query .= "mailing_new_all_normal = '". $mailing_new_all_normal ."', ";
	$query .= "mailing_followup_all_normal = '". $mailing_followup_all_normal ."', ";
	$query .= "mailing_finish_all_normal = '". $mailing_finish_all_normal ."', ";
	$query .= "mailing_followup_attrib = '". $mailing_followup_attrib ."', ";
	$query .= "mailing_finish_attrib = '". $mailing_finish_attrib ."', ";
	$query .= "mailing_new_user = '". $mailing_new_user ."', ";
	$query .= "mailing_followup_user = '". $mailing_followup_user ."', ";
	$query .= "mailing_finish_user = '". $mailing_finish_user ."', ";
	$query .= "mailing_new_attrib = '". $mailing_new_attrib ."' ";
	$query .= "where ID = 1";
	
	if($db->query($query)) return true;
	else return false;
}

?>