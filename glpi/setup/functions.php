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

		}	
	
	}	
}

function updateDropdown($input) {
	$db = new DB;
	
	if($input["tablename"] == "glpi_dropdown_netpoint") {
		$query = "update ".$input["tablename"]." SET name = '".$input["value"]."', location = '".$input["value2"]."' where ID = '".$input["ID"]."'";
		
	}
	else {
		$query = "update ".$input["tablename"]." SET name = '".$input["value"]."' where ID = '".$input["ID"]."'";
	}
	if ($result=$db->query($query)) {
		return true;
	} else {
		return false;
	}
}


function addDropdown($input) {
	if (!empty($input["value"])){
	$db = new DB;

	if($input["tablename"] == "glpi_dropdown_netpoint") {
		$query = "INSERT INTO ".$input["tablename"]." (name,location) VALUES ('".$input["value"]."', '".$input["value2"]."')";
	}
	else if ($input["tablename"] == "glpi_dropdown_locations" || $input["tablename"] == "glpi_dropdown_kbcategories"){
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
	case  "hdtype" : case "sndcard" : case "moboard" : case "gfxcard" : case "network" : case "ramtype" : case "processor" :
		$query = "update glpi_computers set ". $name ." = '". $input["newID"] ."'  where ". $name ." = '".$input["oldID"]."'";
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
		break;
	case "networking" :
		$query = "update glpi_networking set type = '". $input["newID"] ."'  where type = '".$input["oldID"]."'";
		$result = $db->query($query);
		break;
	case "netpoint" : 
		$query = "update glpi_networking_ports set netpoint = '". $input["newID"] ."'  where type = '".$input["oldID"]."'";
		$result = $db->query($query);
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
	case  "hdtype" : case "sndcard" : case "moboard" : case "gfxcard" : case "network" : case "ramtype" : case "processor" :
		$query = "Select count(*) as cpt FROM glpi_computers where ". $name ." = ".$ID."";
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

		break;
	case "monitors" :
		$query = "Select count(*) as cpt FROM glpi_monitors where type = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
	case "computers" :
		$query = "Select count(*) as cpt FROM glpi_computers where type = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
	case "printers" :
		$query = "Select count(*) as cpt FROM glpi_printers where type = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
	case "networking" :
		$query = "Select count(*) as cpt FROM glpi_networking where type = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;

	case "netpoint" : 
		$query = "Select count(*) as cpt FROM glpi_networking_ports where netpoint = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
	}
	return $var1;

}



function titleUsers(){
                
		// Un titre pour la gestion des users
		
		GLOBAL  $lang,$HTMLRel;
                echo "<div align='center'><table border='0'><tr><td>";
                echo "<img src=\"".$HTMLRel."pics/users.png\" alt='".$lang["setup"][2]."' title='".$lang["setup"][2]."'></td><td><a  class='icon_consol' href=\"users-info-form.php?new=1\"><b>".$lang["setup"][2]."</b></a>";
                echo "</td></tr></table></div>";
}


function showPasswordForm($target,$ID) {

	GLOBAL $cfg_layout, $lang;
	
	$user = new User($ID);
	$user->getFromDB($ID);
		
	echo "<form method='post' action=\"$target\">";
	echo "<div align='center'>&nbsp;<table class='tab_cadre' cellpadding='5' width='30%'>";
	echo "<tr><th colspan='2'>".$lang["setup"][11]." '".$user->fields["name"]."':</th></tr>";
	echo "<tr><td width='100%' align='center' class='tab_bg_1'>";
	echo "<input type='password' name='password' size='10'>";
	echo "</td><td align='center' class='tab_bg_2'>";
	echo "<input type='hidden' name='name' value=\"".$user->fields["name"]."\">";
	echo "<input type='submit' name='changepw' value=\"".$lang["buttons"][14]."\" class='submit'>";
	echo "</td></tr>";
	echo "</table></div>";
	echo "</form>";

}


function showUserinfo($target,$name) {
	
	// Affiche les infos User
	
	GLOBAL $cfg_layout, $lang;
	
	$user = new User();
	
	
	$user->getfromDB($name);
		
	
	
	echo "<div align='center'>";
		echo "<table class='tab_cadre'>";
		echo   "<tr><th colspan='2'>".$lang["setup"][57]." : " .$user->fields["name"]."</th></tr>";
		echo "<tr class='tab_bg_1'>";	
		
			echo "<td align='center'>".$lang["setup"][18]."</td>";
			
			echo "<td align='center'><b>".$user->fields["name"]."</b></td></tr>";
									
			echo "<tr class='tab_bg_1'><td align='center'>".$lang["setup"][13]."</td><td>".$user->fields["realname"]."</td></tr>";

			echo "<tr class='tab_bg_1'><td align='center'>".$lang["setup"][20]."</td><td>".$user->fields["type"]."</td></tr>";	
			echo "<tr class='tab_bg_1'><td align='center'>".$lang["setup"][14]."</td><td>".$user->fields["email"]."</td></tr>";
			echo "<tr class='tab_bg_1'><td align='center'>".$lang["setup"][15]."</td><td>".$user->fields["phone"]."</td></tr>";
			echo "<tr class='tab_bg_1'><td align='center'>".$lang["setup"][16]."</td><td>";
				echo getDropdownName("glpi_dropdown_locations",$user->fields["location"]);
			echo "</td></tr>";
	echo "</table></div>";

	echo "<div align='center' ><p><b>".$lang["tracking"][11]."</p></div>";
	
}




function showUserform($target,$name) {
	
	// Affiche un formulaire User
	GLOBAL $cfg_layout, $lang;
	
	$user = new User();
	if($name == 'Helpdesk') {
		echo "<div align='center'>";
		echo $lang["setup"][220];
		echo "</div>";
		return 0;
	}
	if(empty($name)) {
	// Partie ajout d'un user
	// il manque un getEmpty pour les users	
	$user->getEmpty();
	
	} else {
		$user->getfromDB($name);
		
	}
	echo "<div align='center'>";
	echo "<form method='post' name=\"user_manager\" action=\"$target\"><table class='tab_cadre'>";
	echo "<tr><th colspan='2'>".$lang["setup"][57]." : " .$user->fields["name"]."</th></tr>";
	echo "<tr class='tab_bg_1'>";	
	echo "<td align='center'>".$lang["setup"][18]."</td>";
	// si on est dans le cas d'un ajout , cet input ne doit plus être hiden
	if ($name=="") {
		echo "<td><input  name='name' value=\"".$user->fields["name"]."\">";
		echo "</td></tr>";
	} else {
		echo "<td align='center'><b>".$user->fields["name"]."</b>";
		echo "<input type='hidden' name='name' value=\"".$user->fields["name"]."\">";
		echo "</td></tr>";
	}
	//do some rights verification
	if(isSuperAdmin($_SESSION["glpitype"])) {
		if (!empty($user->fields["password"])||$name=="")
		echo "<tr class='tab_bg_1'><td align='center'>".$lang["setup"][19]."</td><td><input type='password' name='password' value=\"".$user->fields["password"]."\" size='20' /></td></tr>";
		echo "<tr class='tab_bg_1'><td align='center'>".$lang["setup"][13]."</td><td><input name='realname' size='20' value=\"".$user->fields["realname"]."\"></td></tr>";
		echo "<tr class='tab_bg_1'><td align='center'>".$lang["setup"][20]."</td><td>";
		echo "<select name='type' >";
		echo "<option value='super-admin'";
		if ($user->fields["type"]=="super-admin") { echo " selected"; }
		echo ">Super-Admin";
		echo "<option value='admin'";
		if ($user->fields["type"]=="admin") { echo " selected"; }
		echo ">Admin";
		echo "<option value=normal";
		if (empty($name)||$user->fields["type"]=="normal") { echo " selected"; }
		echo ">Normal";
		echo "<option value=\"post-only\"";
		if ($user->fields["type"]=="post-only") { echo " selected"; }
		echo ">Post Only";
		echo "</select>";
	} else {
		if (($user->fields["type"]!="super-admin"&&!empty($user->fields["password"]))||$name=="")
			echo "<tr class='tab_bg_1'><td align='center'>".$lang["setup"][19]."</td><td><input type='password' name='password' value=\"".$user->fields["password"]."\" size='20' /></td></tr>";
		echo "<tr class='tab_bg_1'><td align='center'>".$lang["setup"][13]."</td><td><input name='realname' size='20' value=\"".$user->fields["realname"]."\"></td></tr>";
		echo "<tr class='tab_bg_1'><td align='center'>".$lang["setup"][20]."</td>";
		if($user->fields["type"] != "super-admin" && $user->fields["type"] != "admin") {
			echo "<td><select name='type' >";
			echo "<option value='normal'";
			if (empty($name)||$user->fields["type"]=="normal") { echo " selected"; }
			echo ">Normal";
			echo "<option value=\"post-only\"";
			if ($user->fields["type"]=="post-only") { echo " selected"; }
			echo ">Post Only";
			echo "</select>";	
		} else {
			echo "<td align='center'>".$user->fields["type"]."</td>";
		}
	}
	echo "</td></tr>";	
	echo "<tr class='tab_bg_1'><td align='center'>".$lang["setup"][14]."</td><td><input name='email_form' size='20' value=\"".$user->fields["email"]."\"></td></tr>";
	echo "<tr class='tab_bg_1'><td align='center'>".$lang["setup"][15]."</td><td><input name='phone' size='20' value=\"".$user->fields["phone"]."\"></td></tr>";
	echo "<tr class='tab_bg_1'><td align='center'>".$lang["setup"][16]."</td><td>";
	dropdownValue("glpi_dropdown_locations", "location", $user->fields["location"]);
	echo "</td></tr>";
	if (isSuperAdmin($_SESSION["glpitype"])) {
		echo "<tr class='tab_bg_1'>";
		echo "<td align='center' >".$lang["setup"][58]."</td>
		<td align='center' ><p><strong>".$lang["setup"][60]."</strong><input type='radio' value='no' name='can_assign_job' ";
		if (empty($name)||$user->fields["can_assign_job"] == 'no') echo "checked ";
		echo "></p>";
		echo "<p><strong>".$lang["setup"][61]."</strong><input type='radio' value='yes' name='can_assign_job' ";
		if ($user->fields["can_assign_job"] == 'yes') echo "checked";
		echo "></p>";
		echo "</td></tr>";
	}
	if ($name=="") {
		echo "<tr >";
		echo "<td class='tab_bg_2' valign='top' colspan='2'>";
		echo "<center><input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'></center>";
		echo "</td>";
		echo "</tr>";	
	} else {
		if(isSuperadmin($_SESSION["glpitype"])) {
			echo "<tr>";
			echo "<td class='tab_bg_2' valign='top' >";	
			echo "<center><input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit' ></center>";
			echo "</td>";
			echo "<td class='tab_bg_2' valign='top' >\n";
			echo "<center><input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit' ></center>";
			echo "</td>";
			echo "</tr>";
		}
		else {
			echo "<tr>";
			echo "<td class='tab_bg_2' valign='top' colspan='2'>";	
			echo "<center><input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit' ></center>";
			echo "</td>";
			echo "</tr>";
		}
	}

	echo "</table></form></div>";
}



function searchFormUsers() {
	// Users Search Form
	
	GLOBAL $cfg_install, $cfg_layout, $layout, $lang;

	$option["glpi_users.name"]			= $lang["setup"][18];
	$option["glpi_users.realname"]			= $lang["setup"][13];
	$option["glpi_users.type"]			= $lang["setup"][20];
	$option["glpi_users.email"]			= $lang["setup"][14];
	$option["glpi_users.phone"]			= $lang["setup"][15];
	$option["glpi_dropdown_locations.name"]		= $lang["setup"][3];
	

	echo "<form method='get' action=\"".$cfg_install["root"]."/setup/users-search.php\">";
	echo "<div align='center'><table  width='750' class='tab_cadre'>";
	echo "<tr><th colspan='2'><b>".$lang["search"][0].":</b></th></tr>";
	echo "<tr class='tab_bg_1'>";
	echo "<td align='center'>";
	echo "<select name=\"field\" size='1'>";
	echo "<option value='all' ";
	if($_GET["field"] == "all") echo "selected";
	echo ">".$lang["search"][7]."</option>";

	reset($option);
	foreach ($option as $key => $val) {
		$selected="";
		if ($_GET["field"]==$key) $selected="selected";
		echo "<option value='$key' $selected>$val\n";
	}
	echo "</select>&nbsp;";
	echo $lang["search"][1];
	echo "&nbsp;<select name='phrasetype' size='1'>";
	echo "<option value='contains' ";
	if($_GET["phrasetype"] == "contains") echo "selected";
	echo ">".$lang["search"][2]."</option>";
	echo "<option value='exact' ";
	if($_GET["phrasetype"] == "exact") echo "selected";
	echo ">".$lang["search"][3]."</option>";
	echo "</select>";
	echo "<input type='text' size='12' name=\"contains\" value=\"".$_GET["contains"]."\">";
	echo "&nbsp;";
	echo $lang["search"][4];
	echo "&nbsp;<select name='sort' size='1'>";
	reset($option);
	foreach ($option as $key => $val) {
		$selected="";
		if ($_GET["sort"]==$key) $selected="selected";

		echo "<option value=$key $selected>$val\n";
	}
	echo "</select> ";
	echo "</td><td width='80' align='center' class='tab_bg_2'>";
	echo "<input type='submit' value=\"".$lang["buttons"][0]."\" class='submit'>";
	echo "</td></tr></table></div></form>";
}

function showUsersList($target,$username,$field,$phrasetype,$contains,$sort,$order,$start) {

	// Lists Users

	GLOBAL $cfg_install, $cfg_layout, $cfg_features, $lang, $HTMLRel;

	$db = new DB;

	// Build query
	if($field=="all") {
		$where = " (";
		$fields = $db->list_fields("glpi_users");
		$columns = $db->num_fields($fields);
		
		for ($i = 0; $i < $columns; $i++) {
			if($i != 0) {
				$where .= " OR ";
			}
			$coco = $db->field_name($fields, $i);
			if($coco == "location") {
				$where .= " glpi_dropdown_locations.name LIKE '%".$contains."%' and glpi_users.name != 'Helpdesk'";
			}
			else {
   				$where .= "glpi_users.".$coco . " LIKE '%".$contains."%' and glpi_users.name != 'Helpdesk'";
			}
		}
		$where .= ")";
	}
	else {
		if ($phrasetype == "contains") {
			$where = "($field LIKE '%".$contains."%' and glpi_users.name != 'Helpdesk')";
		}
		else {
			$where = "($field LIKE '".$contains."' and glpi_users.name != 'Helpdesk')";
		}
	}
	
	if (!$start) {
		$start = 0;
	}
	if (!$order) {
		$order = "ASC";
	}
	$query = "SELECT * FROM glpi_users  LEFT JOIN glpi_dropdown_locations on glpi_users.location=glpi_dropdown_locations.ID ";
	$query.=" WHERE $where ORDER BY $sort $order";

		// Get it from database	
	if ($result = $db->query($query)) {
		$numrows= $db->numrows($result);

		// Limit the result, if no limit applies, use prior result
		if ($numrows>$cfg_features["list_limit"]) {
			$query_limit = $query." LIMIT $start,".$cfg_features["list_limit"]." ";
			$result_limit = $db->query($query_limit);
			$numrows_limit = $db->numrows($result_limit);
		} else {
			$numrows_limit = $numrows;
			$result_limit = $result;
		}
		

		if ($numrows_limit>0) {
			// Produce headline
			echo "<center><table  class='tab_cadre'><tr>";

			// Name
			echo "<th>";
			if ($sort=="glpi_users.name") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_users.name&order=ASC&start=$start\">";
			echo $lang["setup"][12]."</a></th>";
			
			// realname		
			echo "<th>";
			if ($sort=="glpi_users.realname") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_users.realname&order=ASC&start=$start\">";
			echo $lang["setup"][13]."</a></th>";

			// type
			echo "<th>";
			if ($sort=="glpi_users.type") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_users.type&order=ASC&start=$start\">";
			echo $lang["setup"][17]."</a></th>";			
			
			// email
			echo "<th>";
			if ($sort=="glpi_users.email") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_users.email&order=ASC&start=$start\">";
			echo $lang["setup"][14]."</a></th>";

						
			// Phone
			echo "<th>";
			if ($sort=="glpi_users.phone") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_users.phone&order=ASC&start=$start\">";
			echo $lang["setup"][15]."</a></th>";

			
						
			// Location			
			echo "<th>";
			if ($sort=="glpi_dropdown_locations.name") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_dropdown_locations.name&order=ASC&start=$start\">";
			echo $lang["setup"][16]."</a></th></tr>";



			

			for ($i=0; $i < $numrows_limit; $i++) {
			$name= $db->result($result_limit, $i, "name");
				
				$user = new User($name);
				$user->getFromDB($name);
							
				echo "<tr class='tab_bg_2'>";
				echo "<td><b>";
				echo "<a href=\"".$cfg_install["root"]."/setup/users-info-form.php?name=".$user->fields["name"] ."\">";
				echo $user->fields["name"]." (".$user->fields["ID"].")";
				echo "</a></b></td>";
				echo "<td>".$user->fields["realname"] ."</td>";
				echo "<td>". $user->fields["type"] ."</td>";
				echo "<td>".$user->fields["email"]."</td>";
				echo "<td>".$user->fields["phone"]."</td>";
				echo "<td>". getDropdownName("glpi_dropdown_locations",$user->fields["location"]) ."</td>";
				echo "</tr>";
			}

			// Close Table
			echo "</table></center>";

			// Pager
			$parameters="field=$field&phrasetype=$phrasetype&contains=$contains&sort=$sort&order=$order";
			printPager($start,$numrows,$target,$parameters);

		} else {
			echo "<div align='center'><b>".$lang["setup"][66]."</b></div>";
			echo "<hr noshade>";
			
		}
	}
}







function addUser($input) {
global $cfg_install;
	
	//only admin and superadmin can add some user
	if(isAdmin($_SESSION["glpitype"])) {
		//Only super-admin's can add users with admin or super-admin access.
		//set to "normal" by default
		if(!isSuperAdmin($_SESSION["glpitype"])) {
			if($input["type"] != "normal" && $input["type"] != "post-only") {
				$input["type"] = "normal";
			}
		}
			// Add User, nasty hack until we get PHP4-array-functions
			$user = new User($input["name"]);
			if(empty($input["password"]))  $input["password"] = "";
			// dump status
			$null = array_pop($input);
			// change email_form to email (not to have a problem with preselected email)
			if (isset($input["email_form"])){
				$input["email"]=$input["email_form"];
				unset($input["email_form"]);
			}
	
			// fill array for update
			foreach ($input as $key => $val) {
				if (!isset($user->fields[$key]) || $user->fields[$key] != $input[$key]) {
					$user->fields[$key] = $input[$key];
				}
			}

			if ($user->addToDB()) {
				// Give him some default prefs...
				$query = "INSERT INTO glpi_prefs (username,tracking_order,language) VALUES ('".$input["name"]."','no','".$cfg_install["default_language"]."')";

				$db = new DB;
				$result=$db->query($query);
				return true;
			} else {
				return false;
			}
	} else {
		return false;
	}
}


function updateUser($input) {

	//only admin and superadmin can update some user
/*	if(!isAdmin($_SESSION["glpitype"])) {
		return false;
	}
*/
	// Update User in the database
	$user = new User($input["name"]);
	$user->getFromDB($input["name"]); 

	// dump status
	$null = array_pop($input);
	// password updated by admin user or own password for user
	if(empty($input["password"]) || (!isAdmin($_SESSION["glpitype"])&&$_SESSION["glpiname"]!=$input['name'])) {
		unset($user->fields["password"]);
		unset($user->fields["password_md5"]);
		unset($input["password"]);
	} 
	
	// change email_form to email (not to have a problem with preselected email)
	if (isset($input["email_form"])){
	$input["email"]=$input["email_form"];
	unset($input["email_form"]);
	}
	//Only super-admin's can set admin or super-admin access.
	//set to "normal" by default
	//if user type is allready admin or super-admin do not touch it
	if(!isSuperAdmin($_SESSION["glpitype"])) {
		if(!empty($input["type"]) && $input["type"] != "normal" && $input["type"] != "post-only") {
			$input["type"] = "normal";
		}
		if($user->fields["type"] == "") {
			$input["type"] = "super-admin";
		}
		if($user->fields["type"] == "admin") {
			$input["type"] = "";
		}
		
	}
	// fill array for update
	$x=0;
	foreach ($input as $key => $val) {
		if (isset($input[$key]) &&  $input[$key] != $user->fields[$key]) {
			$user->fields[$key] = $input[$key];
			$updates[$x] = $key;
			$x++;
		}
	}
	
	
	if(!empty($updates)) {
		$user->updateInDB($updates);
	}
}

function deleteUser($input) {
	// Delete User (only superadmin can delete an user)
	if(isSuperAdmin($_SESSION["glpitype"])) {
		$user = new User($input["name"]);
		$user->deleteFromDB($input["name"]);
	}
} 


function showFormAssign($target)
{

	GLOBAL $cfg_layout,$cfg_install, $lang, $IRMName;
	
	$db = new DB;

	$query = "SELECT name FROM glpi_users where name <> 'Helpdesk' and name <> '".$_SESSION["glpiname"]."' ORDER BY type DESC";
	
	if ($result = $db->query($query)) {

		echo "<div align='center'><table class='tab_cadre'>";
		echo "<tr><th>".$lang["setup"][57]."</th><th colspan='2'>".$lang["setup"][58]."</th>";
		echo "</tr>";
		
		  $i = 0;
		  while ($i < $db->numrows($result)) {
			$name = $db->result($result,$i,"name");
			$user = new User($name);
			$user->getFromDB($name);
			
			echo "<tr class='tab_bg_1'>";	
			echo "<form method='post' action=\"$target\">";
			echo "<td align='center'><b>".$user->fields["name"]."</b>";
			echo "<input type='hidden' name='name' value=\"".$user->fields["name"]."\">";
			echo "</td>";
			echo "<td align='center'><strong>".$lang["setup"][60]."</strong><input type='radio' value='no' name='can_assign_job' ";
			if ($user->fields["can_assign_job"] == 'no') echo "checked ";
      echo ">";
      echo "<td align='center'><strong>".$lang["setup"][61]."</strong><input type='radio' value='yes' name='can_assign_job' ";
			if ($user->fields["can_assign_job"] == 'yes') echo "checked";
      echo ">";
			echo "</td>";
			echo "<td class='tab_bg_2'><input type='submit' name='update' value=\"".$lang["buttons"][7]."\"></td>";
						
                        echo "</form>";
	
      $i++;
			}
echo "</table></div>";}
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

function updateSort($input) {

	$db = new DB;
	//print_r($input);
	$query = "UPDATE glpi_prefs SET tracking_order = '".$input["tracking_order"]."' WHERE (username = '".$_SESSION["glpiname"]."')";
	if ($result=$db->query($query)) {
		$_SESSION["tracking_order"] = $input["tracking_order"];
		return true;
	} else {
		return false;
	}
}

function showLangSelect($target) {

	GLOBAL $cfg_layout, $cfg_install, $lang;
	
	$l = $_SESSION["glpilanguage"]; 
	
	echo "<form method='post' action=\"$target\">";
	echo "<div align='center'>&nbsp;<table class='tab_cadre' cellpadding='5' width='30%'>";
	echo "<tr><th colspan='2'>".$lang["setup"][41].":</th></tr>";
	echo "<tr><td width='100%' align='center' class='tab_bg_1'>";
	echo "<select name='language'>";
	/*
	$i=0;
	while ($i < count($cfg_install["languages"])) {
		echo "<option value=\"".$cfg_install["languages"][$i]."\"";
		if ($l==$cfg_install["languages"][$i]) { 
			echo " selected"; 
		}
		echo ">".$cfg_install["languages"][$i];
		$i++;
	}
	*/

	while (list($cle)=each($cfg_install["languages"])){
		echo "<option value=\"".$cle."\"";
			if ($l==$cle) { echo " selected"; }
		echo ">".$cfg_install["languages"][$cle][0];
	}
	echo "</select>";
	echo "</td>";
	echo "<td align='center' class='tab_bg_2'>";
	echo "<input type='submit' name='changelang' value=\"".$lang["buttons"][14]."\" class='submit'>";
	echo "</td></tr>";
	echo "</table></div>";
	echo "</form>";
}

function updateLanguage($input) {

	$db = new DB;
	$query = "UPDATE glpi_prefs SET language = '".$input["language"]."' WHERE (username = '".$_SESSION["glpiname"]."')";
	if ($result=$db->query($query)) {
		$_SESSION["glpilanguage"] = $input["language"];
		return true;
	} else {
		return false;
	}
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

function updateConfigGen($root_doc,$event_loglevel,$num_of_events,$expire_events,$jobs_at_login,$list_limit,$cut, $permit_helpdesk,$default_language,$priority,$date_fiscale) {
	
	$db = new DB;
	
		$query = "update glpi_config set root_doc = '". $root_doc ."', ";
		$query.= "event_loglevel = '". $event_loglevel ."', num_of_events = '". $num_of_events ."', default_language = '". $default_language ."',";
		$query.= "expire_events = '". $expire_events ."', jobs_at_login = '". $jobs_at_login ."' , list_limit = '". $list_limit ."' , cut = '". $cut ."', permit_helpdesk='". $permit_helpdesk ."',";
		$query.= "priority_1 = '". $priority[1] ."', priority_2 = '". $priority[2] ."', priority_3 = '". $priority[3] ."', priority_4 = '". $priority[4] ."', priority_5 = '". $priority[5] ."', ";
		$query.= " date_fiscale = '". $date_fiscale ."' where ID = '1' ";
		$db->query($query);
	
}


function updateLDAP($ldap_host,$ldap_basedn,$ldap_rootdn,$ldap_pass,$ldap_condition,$field_name,$field_email,$field_location,$field_phone,$field_realname) {
	
	$db = new DB;
	//TODO : test the remote LDAP connection
	if(!empty($ldap_host)) {
		$query = "update glpi_config set ldap_host = '". $ldap_host ."', ";
		$query.= "ldap_basedn = '". $ldap_basedn ."', ldap_rootdn = '". $ldap_rootdn ."', ";
		$query .= "ldap_pass = '". $ldap_pass ."', ldap_condition = '". $ldap_condition ."', ";
		$query .= "ldap_field_name = '". $field_name ."', ldap_field_email = '". $field_email ."', ";
		$query .= "ldap_field_location = '". $field_location ."', ldap_field_phone = '". $field_phone ."', ";
		$query .= "ldap_field_realname = '". $field_realname ."' ";
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
