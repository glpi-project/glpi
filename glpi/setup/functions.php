<?php
/*
 
  ----------------------------------------------------------------------
GLPI - Gestionnaire libre de parc informatique
 Copyright (C) 2002 by the INDEPNET Development Team.
 Bazile Lebeau, baaz@indepnet.net - Jean-Mathieu Doléans, jmd@indepnet.net
 http://indepnet.net/   http://glpi.indepnet.org
 ----------------------------------------------------------------------
 Based on:
IRMA, Information Resource-Management and Administration
Christian Bauer, turin@incubus.de 

 ----------------------------------------------------------------------
 LICENSE

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License (GPL)
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 To read the license please visit http://www.gnu.org/copyleft/gpl.html
 ----------------------------------------------------------------------
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------
*/
 

include ("_relpos.php");
// FUNCTIONS Setup

function showFormDropDown ($target,$name,$human) {

	GLOBAL $cfg_layout, $lang, $HTMLRel;

	echo "<div align='center'>&nbsp;<table class='tab_cadre' width='70%'>";
	echo "<a name=\"$name\"></a>";
	echo "<tr><th colspan='3'>$human:</th></tr>";
	echo "<form method='post' action=\"$target\">";
	echo "<input type='hidden' name='which' value='$name'>";
	if (countElementsInTable("glpi_dropdown_".$name)>0){
	echo "<tr><td align='center' class='tab_bg_1'>";

	dropdown("glpi_dropdown_".$name, "ID");
        // on ajoute un input text pour entrer la valeur modifier
        echo "<img src=\"".$HTMLRel."pics/puce.gif\" alt='' title=''>";
	if($name == "netpoint") {
		echo $lang["networking"][1].": ";		
		dropdown("glpi_dropdown_locations", "value2");
		echo $lang["networking"][52].": ";
		echo "<input type='text' maxlength='100' size='10' name='value'>";
	}
	else {
        	echo "<input type='text' maxlength='100' size='20' name='value'>";
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
		dropdown("glpi_dropdown_locations", "value2");
		echo $lang["networking"][52].": ";
		echo "<input type='text' maxlength='100' size='10' name='value'>";
	}
	else {
		echo "<input type='text' maxlength='100' size='20' name='value'>";
	}
	echo "</td><td align='center' colspan='2' class='tab_bg_2'>";
	echo "<input type='hidden' name='tablename' value='glpi_dropdown_".$name."' >";
	echo "<input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit' class='submit'>";
	echo "</td></form></tr>";
	echo "</table></div>";
}

function showFormTypeDown ($target,$name,$human) {

	GLOBAL $cfg_layout, $lang, $HTMLRel;
	
	echo "<div align='center'>&nbsp;<table class='tab_cadre' width=70%>";
	echo "<a name=\"$name\"></a>";
	echo "<tr><th colspan='3'>$human:</th></tr>";
	echo "<form method='post' action=\"$target\">";
	echo "<input type='hidden' name='which' value='$name'>";
	echo "<tr><td align='center' class='tab_bg_1'>";

	dropdown("glpi_type_".$name, "ID");
	// on ajoute un input text pour entrer la valeur modifier
         echo "<img src=\"".$HTMLRel."pics/puce.gif\" alt='' title=''>";
        echo "<input type='text' maxlength='100' size='20' name='value'>";

	echo "</td><td align='center' class='tab_bg_2'>";
	echo "<input type='hidden' name='tablename' value=\"glpi_type_".$name."\" />";
	//  on ajoute un bouton modifier
        echo "<input type='submit' name='update' value='".$lang["buttons"][14]."' class='submit'>";
	echo "</td><td align='center' class='tab_bg_2'>";
        echo "<input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'>";
	echo "</td></form></tr>";
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

	$db = new DB;
	if($input["tablename"] == "glpi_dropdown_netpoint") {
		$query = "INSERT INTO ".$input["tablename"]." (name,location) VALUES ('".$input["value"]."', '".$input["value2"]."')";
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
		$query = "update glpi_templates set ". $name ." = '". $input["newID"] ."'  where ". $name ." = '".$input["oldID"]."'";
		$db->query($query);
		break;
	case "os" :
		$query = "update glpi_computers set ". $name ." = '". $input["newID"] ."'  where ". $name ." = '".$input["oldID"]."'";
		$db->query($query);
		$query = "update glpi_templates set ". $name ." = '". $input["newID"] ."'  where ". $name ." = '".$input["oldID"]."'";
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
		$query = "update glpi_templates set ". $name ." = '". $input["newID"] ."'  where ". $name ." = '".$input["oldID"]."'";
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
	case "templates" :  
		$query = "update glpi_templates set type = '". $input["newID"] ."'  where type = '".$input["oldID"]."'";
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
	
	echo $lang["setup"][63];
	echo $lang["setup"][64];
	echo "<form action=\"". $target ."\" method=\"post\">";
	echo "<input type=\"hidden\" name=\"tablename\" value=\"". $table ."\"  />";
	echo "<input type=\"hidden\" name=\"ID\" value=\"". $ID ."\"  />";
	echo "<input type=\"hidden\" name=\"which\" value=\"". str_replace("glpi_type_","",str_replace("glpi_dropdown_","",$table)) ."\"  />";
	echo "<input type=\"hidden\" name=\"forcedelete\" value=\"1\" />";
	echo "<input type=\"submit\" name=\"delete\" value=\"Confirmer\" />";
	echo "<form action=\" ". $target ."\" method=\"post\">";
	echo "<input type=\"submit\" name=\"annuler\" value=\"Annuler\" />";
	echo "</form>";
	echo "<br />". $lang["setup"][65];
	echo "<form action=\" ". $target ."\" method=\"post\">";
	echo "<input type=\"hidden\" name=\"which\" value=\"". str_replace("glpi_type_","",str_replace("glpi_dropdown_","",$table)) ."\"  />";
	dropdownNoValue($table,"newID",$ID);
	echo "<input type=\"hidden\" name=\"tablename\" value=\"". $table ."\"  />";
	echo "<input type=\"hidden\" name=\"oldID\" value=\"". $ID ."\"  />";
	echo "<input type=\"submit\" name=\"replace\" value=\"Remplacer\" />";
	echo "</form>";
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
		$query = "Select count(*) as cpt FROM glpi_templates where ". $name ." = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
	case "os" :
		$query = "Select count(*) as cpt FROM glpi_computers where ". $name ." = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		$query = "Select count(*) as cpt FROM glpi_templates where ". $name ." = ".$ID."";
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
	case "location" :
		$query = "Select count(*) as cpt FROM glpi_computers where ". $name ." = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		$query = "Select count(*) as cpt FROM glpi_templates where ". $name ." = ".$ID."";
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
	case "templates" :
		$query = "Select count(*) as cpt FROM glpi_templates where type = '".$ID."'";
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
	
	if (empty($name)) {
	// Partie ajout d'un user
	// il manque un getEmpty pour les users	
	$user->getEmpty();
	
	} else {
		$user->getfromDB($name);
		
	}		
	
	echo "<div align='center'>";
		echo "<form method='post' name=\"user_manager\" action=\"$target\"><table class='tab_cadre'>";
		echo   "<tr><th colspan='2'>".$lang["setup"][57]." : " .$user->fields["name"]."</th></tr>";
		echo "<tr class='tab_bg_1'>";	
		
			echo "<td align='center'>".$lang["setup"][18]."</td>";
			// si on est dans le cas d'un ajout , cet input ne doit plus être hiden
			if ($name=="") {
			 echo "<td><input  name='name' value=\"".$user->fields["name"]."\">";
			echo "</td></tr>";
				
			}else{
			echo "<td align='center'><b>".$user->fields["name"]."</b>";
			 echo "<input type='hidden' name='name' value=\"".$user->fields["name"]."\">";
			echo "</td></tr>";
			}
			echo "<tr class='tab_bg_1'><td align='center'>".$lang["setup"][19]."</td><td><input type='password' name='password' value=\"".$user->fields["password"]."\" size='20'></td></tr>";
			
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
			echo "</td></tr>";	
			echo "<tr class='tab_bg_1'><td align='center'>".$lang["setup"][14]."</td><td><input name='email_form' size='20' value=\"".$user->fields["email"]."\"></td></tr>";
			echo "<tr class='tab_bg_1'><td align='center'>".$lang["setup"][15]."</td><td><input name='phone' size='20' value=\"".$user->fields["phone"]."\"></td></tr>";
			echo "<tr class='tab_bg_1'><td align='center'>".$lang["setup"][16]."</td><td>";
				dropdownValue("glpi_dropdown_locations", "location", $user->fields["location"]);
			echo "</td></tr>";
			
		
			
			if (can_assign_job($_SESSION["glpiname"]))
	{
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

		echo "<tr>";
		echo "<td class='tab_bg_2' valign='top' >";
		
		echo "<center><input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit' ></center>";
		echo "</td>";
		echo "<td class='tab_bg_2' valign='top' >\n";
		
		echo "<center><input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit' ></center>";
		echo "</td>";
		echo "</tr>";

		
			
			}

echo "</table></form></div>";

}



function searchFormUsers() {
	// Users Search Form
	
	GLOBAL $cfg_install, $cfg_layout, $layout, $lang;

	$option["glpi_users.name"]				= $lang["setup"][18];
	$option["glpi_users.realname"]			= $lang["setup"][13];
	$option["glpi_users.type"]			= $lang["setup"][20];
	$option["glpi_users.email"]			= $lang["setup"][14];
	$option["glpi_users.phone"]		= $lang["setup"][15];
	$option["glpi_dropdown_locations.name"]			= $lang["setup"][3];
	

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

	GLOBAL $cfg_install, $cfg_layout, $cfg_features, $lang;

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
			$coco = mysql_field_name($fields, $i);
			if($coco == "location") {
				$where .= " glpi_dropdown_locations.name LIKE '%".$contains."%'";
			}
			else {
   				$where .= "glpi_users.".$coco . " LIKE '%".$contains."%'";
			}
		}
		$where .= ")";
	}
	else {
		if ($phrasetype == "contains") {
			$where = "($field LIKE '%".$contains."%')";
		}
		else {
			$where = "($field LIKE '".$contains."')";
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
				echo "&middot;&nbsp;";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_users.name&order=ASC&start=$start\">";
			echo $lang["setup"][12]."</a></th>";
			
			// realname		
			echo "<th>";
			if ($sort=="glpi_users.realname") {
				echo "&middot;&nbsp;";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_users.realname&order=ASC&start=$start\">";
			echo $lang["setup"][13]."</a></th>";

			// type
			echo "<th>";
			if ($sort=="glpi_users.type") {
				echo "&middot;&nbsp;";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_users.type&order=ASC&start=$start\">";
			echo $lang["setup"][17]."</a></th>";			
			
			// email
			echo "<th>";
			if ($sort=="glpi_users.email") {
				echo "&middot;&nbsp;";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_users.email&order=ASC&start=$start\">";
			echo $lang["setup"][14]."</a></th>";

						
			// Phone
			echo "<th>";
			if ($sort=="glpi_users.phone") {
				echo "&middot;&nbsp;";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_users.phone&order=ASC&start=$start\">";
			echo $lang["setup"][15]."</a></th>";

			
						
			// Location			
			echo "<th>";
			if ($sort=="glpi_dropdown_locations.name") {
				echo "&middot;&nbsp;";
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
		$query = "INSERT INTO glpi_prefs VALUES ('".$input["name"]."','','english')";
		$db = new DB;
		$result=$db->query($query);
		return true;
	} else {
		return false;
	}
}


function updateUser($input) {
	// Update User in the database

	$user = new User($input["name"]);
	$user->getFromDB($input["name"]); 

 	// dump status
	$null = array_pop($input);
	// password updated?
	if(empty($input["password"])) {
		$user->fields["password"]="";
	}
	// change email_form to email (not to have a problem with preselected email)
	if (isset($input["email_form"])){
	$input["email"]=$input["email_form"];
	unset($input["email_form"]);
	}
	// fill array for update
	$x=0;
	foreach ($input as $key => $val) {
		if (empty($input[$key]) ||  $input[$key] != $user->fields[$key]) {
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
	// Delete User
	
	$user = new User($input["name"]);
	$user->deleteFromDB($input["name"]);
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

function listTemplates($target) {

	GLOBAL $cfg_layout, $lang;

	$db = new DB;
	$query = "SELECT * FROM glpi_templates ORDER by templname";
	if ($result = $db->query($query)) {
		
		echo "<div align='center'><table class='tab_cadre' width='50%'>";
		echo "<tr><th colspan='2'>".$lang["setup"][1].":</th></tr>";
		$i=0;
		while ($i < $db->numrows($result)) {
			$ID = $db->result($result,$i,"ID");
			$templname = $db->result($result,$i,"templname");
			
			echo "<tr>";
			echo "<td align='center' class='tab_bg_1'>";
			echo "<a href=\"$target?ID=$ID&showform=showform\">&nbsp;&nbsp;&nbsp;$templname&nbsp;&nbsp;&nbsp;</a></td>";
			echo "<td align='center' class='tab_bg_2'>";
			echo "<b><a href=\"$target?ID=$ID&delete=delete\">".$lang["buttons"][6]."</a></b></td>";
			echo "</tr>";		

			$i++;
		}

		echo "<tr>";
		echo "<td colspan='2' align='center' class='tab_bg_2'>";
		echo "<b><a href=\"$target?showform=showform\">".$lang["setup"][22]."</a></b>";
		echo "</td>";
		echo "</tr>";
		echo "</form>";
				
		echo "</table></div>";
	}
	

}

function showTemplateForm($target,$ID) {

	GLOBAL $cfg_install, $cfg_layout, $lang,$HTMLRel;

	$templ = new Template;
	
	if ($ID) {
		$templ->getfromDB($ID);
	}
	else {
		$templ->getEmpty();
	}
	
	echo "<div align='center'><table class='tab_cadre'>";
	echo "<form name='form' method='post' action=$target>";
	echo "<tr><th colspan='2'>";
//	if ($ID) {
//		echo $lang["setup"][23].": '".$templ->fields["templname"]."'";
//	} else {
		echo $lang["setup"][23].": <input type='text' name='templname' value=\"".$templ->fields["templname"]."\" size='10'>";
//	}
	echo "</th></tr>";
	
	echo "<tr><td class='tab_bg_1' valign='top'>";
	echo "<table cellpadding='0' cellspacing='0' border='0'>\n";

	echo "<tr><td>".$lang["setup"][24].":		</td>";
	echo "<td><input type='text' name='name' value=\"".$templ->fields["name"]."\" size='12'></td>";
	echo "</tr>";

	echo "<tr><td>".$lang["setup"][25].": 	</td>";
	echo "<td>";
		dropdownValue("glpi_dropdown_locations", "location", $templ->fields["location"]);
	echo "</td></tr>";

	echo "<tr><td>".$lang["setup"][26].":		</td>";
	echo "<td><input type='text' name='contact_num' value=\"".$templ->fields["contact_num"]."\" size='12'>";
	echo "</td></tr>";
	
	echo "<tr><td>".$lang["setup"][27].":	</td>";
	echo "<td><input type='text' name='contact' size='12' value=\"".$templ->fields["contact"]."\">";
	echo "</td></tr>";

	echo "<tr><td>".$lang["setup"][28].":	</td>";
	echo "<td><input type='text' name='serial' size='12' value=\"".$templ->fields["serial"]."\">";
	echo "</td></tr>";

	echo "<tr><td>".$lang["setup"][29].":	</td>";
	echo "<td><input type='text' size='12' name='otherserial' value=\"".$templ->fields["otherserial"]."\">";
	echo "</td></tr>";

	echo "<tr><td valign='top'>".$lang["setup"][30].":</td>";
	echo "<td><textarea 0 rows='8' name='comments' >".$templ->fields["comments"]."</textarea>";
	echo "</td></tr>";

	echo "</table>";

	echo "</td>\n";	
	echo "<td class='tab_bg_1' valign='top'>\n";
	echo "<table cellpadding='0' cellspacing='0' border='0'";


	echo "<tr><td>".$lang["setup"][31].": 	</td>";
	echo "<td>";
		dropdownValue("glpi_type_computers", "type", $templ->fields["type"]);
	echo "</td></tr>";

	echo "<tr><td>".$lang["setup"][32].": 	</td>";
	echo "<td>";	
		dropdownValue("glpi_dropdown_os", "os", $templ->fields["os"]);
	echo "</td></tr>";
		
	echo "<tr><td>".$lang["setup"][33].":</td>";
	echo "<td><input type='text' size='8' name=osver value=\"".$templ->fields["osver"]."\">";
	echo "</td></tr>";
		
	echo "<tr><td>".$lang["setup"][34].":	</td>";
	echo "<td>";
		dropdownValue("glpi_dropdown_processor", "processor", $templ->fields["processor"]);
	echo "</td></tr>";
	
	echo "<tr><td>".$lang["setup"][35].":	</td>";
	echo "<td><input type='text' name='processor_speed' size='4' value=\"".$templ->fields["processor_speed"]."\">";
	echo "</td></tr>";
	
	echo "<tr><td>".$lang["setup"][49].":	</td>";
	echo "<td>";
		dropdownValue("glpi_dropdown_moboard", "moboard", $templ->fields["moboard"]);
	echo "</td></tr>";

	echo "<tr><td>".$lang["setup"][51].":	</td>";
	echo "<td>";
		dropdownValue("glpi_dropdown_sndcard", "sndcard", $templ->fields["sndcard"]);
	echo "</td></tr>";
		
	echo "<tr><td>".$lang["setup"][50].":	</td>";
	echo "<td>";
		dropdownValue("glpi_dropdown_gfxcard", "gfxcard", $templ->fields["gfxcard"]);
	echo "</td></tr>";
		
	echo "<tr><td>".$lang["setup"][36].":	</td>";
	echo "<td>";
		dropdownValue("glpi_dropdown_ram", "ramtype", $templ->fields["ramtype"]);
	echo "</td></tr>";
	
	echo "<tr><td>".$lang["setup"][37].":	</td>";
	echo "<td><input type='text' name='ram' value=\"".$templ->fields["ram"]."\" size=3>";
	echo "</td></tr>";

	echo "<tr><td>".$lang["setup"][52].":	</td>";
	echo "<td>";
		dropdownValue("glpi_dropdown_hdtype", "hdtype", $templ->fields["hdtype"]);
	echo "</td></tr>";

	echo "<tr><td>".$lang["setup"][38].":	</td>";
	echo "<td><input type='text' name='hdspace' size='3' value=\"".$templ->fields["hdspace"]."\">";
	echo "</td></tr>";

	echo "<tr><td>".$lang["setup"][39].":	</td>";
	echo "<td>";
		dropdownValue("glpi_dropdown_network", "network", $templ->fields["network"]);
	echo "</td></tr>";

//
	
	echo "<tr><td>".$lang["setup"][53].":	</td>";
	echo "<td><input type='text' name='achat_date' readonly size='10' value=\"". $templ->fields["achat_date"] ."\">";
	echo "&nbsp; <input name='button' type='button' class='button' onClick=\"window.open('$HTMLRel/mycalendar.php?form=form&elem=achat_date&value=". $templ->fields["achat_date"] ."','Calendrier','width=200,height=220')\" value='".$lang["buttons"][15]."...'>";
	echo "&nbsp; <input name='button_reset' type='button' class='button' onClick=\"document.forms['form'].achat_date.value='0000-00-00'\" value='reset'>";
  echo "</td></tr>";
	
	echo "<tr><td>".$lang["setup"][54].":	</td>";
	echo "<td><input type='text' name='date_fin_garantie' readonly size='10' value=\"". $templ->fields["date_fin_garantie"] ."\">";
	echo "&nbsp; <input name='button' type='button' class='button' readonly onClick=\"window.open('$HTMLRel/mycalendar.php?form=form&elem=date_fin_garantie&value=". $templ->fields["date_fin_garantie"] ."','Calendrier','width=200,height=220')\" value='".$lang["buttons"][15]."...'>";
	echo "&nbsp; <input name='button_reset' type='button' class='button' onClick=\"document.forms['form'].date_fin_garantie.value='0000-00-00'\" value='reset'>";
  echo "</td></tr>";
	
echo "<tr><td>".$lang["setup"][55].":	</td>";
		echo "<td>";
		if ($templ->fields["maintenance"] == 1) {
			echo " OUI <input type='radio' name='maintenance' value='1' checked>";
			echo "&nbsp; &nbsp; NON <input type='radio' name='maintenance' value='0'>";
		} else {
			echo " OUI <input type='radio' name='maintenance' value='1'>";
			echo "&nbsp; &nbsp; NON <input type='radio' name='maintenance' value='0' checked >";
		}
		echo "</td></tr>";


	echo "</table>";

	echo "</td>\n";	
	echo "</tr><tr>";

	if (!empty($ID)) {
		echo "<td class='tab_bg_2' align='center' valign='top' colspan='2'>\n";
		echo "<input type='hidden' name=\"ID\" value=\"".$ID."\">";
		echo "<input type='submit' name=\"update\" value=\"".$lang["buttons"][7]."\" class='submit'>";
		echo "</td></form>\n";	
	} else {
		echo "<td class='tab_bg_2' align=\"center\" valign=\"top\" colspan=\"2\">\n";
		echo "<input type='submit' name=\"add\" value=\"".$lang["buttons"][8]."\" class='submit'>";
		echo "</td></form>\n";	
	}
	
	echo "</tr>\n";
	echo "</table>\n";

	echo "</center>\n";

	echo "</table></div>";
}


function updateTemplate($input) {
	// Update a template in the database

	$templ = new Template;
	$templ->getFromDB($input["ID"],0);

	// dump status
	$null = array_pop($input);
	$updates = array();
	// fill array for update
	$x=0;
	foreach ($input as $key => $val) {
		if ($templ->fields[$key] != $input[$key]) {
			$templ->fields[$key] = $input[$key];
			$updates[$x] = $key;
			$x++;
		}
	}

	$templ->updateInDB($updates);

}

function addTemplate($input) {
	// Add template, nasty hack until we get PHP4-array-functions

	$templ = new Template;

	// dump status
	$null = array_pop($input);
	
	// fill array for update 
	foreach ($input as $key => $val) {
		if (empty($templ->fields[$key]) || $templ->fields[$key] != $input[$key]) {
			$templ->fields[$key] = $input[$key];
		}
	}
	$templ->addToDB();

}

function deleteTemplate($input) {
	// Delete Template
	
	$templ = new Template;
	$templ->deleteFromDB($input["ID"]);
	
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
	$query = "UPDATE glpi_prefs SET tracking_order = '".$input["tracking_order"]."' WHERE (user = '".$_SESSION["glpiname"]."')";
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
	$i=0;
	while ($i < count($cfg_install["languages"])) {
		echo "<option value=\"".$cfg_install["languages"][$i]."\"";
		if ($l==$cfg_install["languages"][$i]) { 
			echo " selected"; 
		}
		echo ">".$cfg_install["languages"][$i];
		$i++;
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
	$query = "UPDATE glpi_prefs SET language = '".$input["language"]."' WHERE (user = '".$_SESSION["glpiname"]."')";
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
	
	GLOBAL  $lang,$HTMLRel;
	
	$db = new DB;
	$query = "select * from glpi_config where ID = 1";
	$result = $db->query($query);
	
	echo "<form action=\"$target\" method=\"post\">";
	echo "<div align='center'><table class='tab_cadre'>";
	echo "<tr><th colspan='2'>".$lang["setup"][100]."</th></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][101]." </td><td> <input type=\"text\" name=\"root_doc\" value=\"". $db->result($result,0,"root_doc") ."\"></td></tr>";
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
	echo "<tr class='tab_bg_2'><td align='center'> ".$lang["setup"][110]." </td><td>   &nbsp;".$lang["choice"][0]."  &nbsp;<input type=\"radio\" name=\"jobs_at_login\" value=\"1\" "; if($db->result($result,0,"jobs_at_login") == 1) echo "checked"; echo " > &nbsp;".$lang["choice"][1]."  &nbsp;<input type=\"radio\" name=\"jobs_at_login\" value=\"0\" "; if($db->result($result,0,"jobs_at_login") == 0) echo "checked"; 
	echo " ></td></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][111]."</td><td> <input type=\"text\" name=\"list_limit\" value=\"". $db->result($result,0,"list_limit") ."\"></td></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][112]."</td><td><input type=\"text\" name=\"cut\" value=\"". $db->result($result,0,"cut") ."\"></td></tr>";
	
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





function showFormExtsources($target) {

	GLOBAL  $lang,$HTMLRel;
	
	$db = new DB;
	$query = "select * from glpi_config where ID = 1";
	$result = $db->query($query);
	
	echo "<form action=\"$target\" method=\"post\">";
	if(extension_loaded('ldap'))
	{
		echo "<div align='center'><p > ".$lang["setup"][151]."</p>";

		echo "<table class='tab_cadre'>";
		echo "<tr><th colspan='2'>".$lang["setup"][152]."</th></tr>";
		echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][153]."</td><td><input type=\"text\" name=\"ldap_host\" value=\"". $db->result($result,0,"ldap_host") ."\"></td></tr>";
		echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][154]."</td><td><input type=\"text\" name=\"ldap_basedn\" value=\"". $db->result($result,0,"ldap_basedn") ."\" ></td></tr>";
		echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][155]."</td><td><input type=\"text\" name=\"ldap_rootdn\" value=\"". $db->result($result,0,"ldap_rootdn") ."\" ></td></tr>";
		echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][156]."</td><td><input type=\"text\" name=\"ldap_pass\" value=\"". $db->result($result,0,"ldap_pass") ."\" ></td></tr>";
		echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][159]."</td><td><input type=\"text\" name=\"ldap_condition\" value=\"". $db->result($result,0,"ldap_condition") ."\" ></td></tr>";
		echo "</table>&nbsp;</div>";
	}
	else {
		echo "<input type=\"hidden\" name=\"LDAP_Test\" value=\"1\" >";
		echo "<div align='center'><table class='tab_cadre' width='400'>";
		echo "<tr><th colspan='2'>".$lang["setup"][152]."</th></tr>";
		echo "<tr class='tab_bg_2'><td align='center'><p class='red'>".$lang["setup"][157]."</p><p>".$lang["setup"][158]."</p></td></th></table></div>";
	}
	if(function_exists('imap_open')) {
		echo "<div align='center'>";
		echo "<p >".$lang["setup"][160]."</p>";
		echo "<p>".$lang["setup"][161]."</p>";
		echo "<table class='tab_cadre'>";
		echo "<tr><th colspan='2'>".$lang["setup"][162]."</th></tr>";
		echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][163]."</td><td><input type=\"text\" name=\"imap_auth_server\" value=\"". $db->result($result,0,"imap_auth_server") ."\" ></td></tr>";
		echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][164]."</td><td><input type=\"text\" name=\"imap_host\" value=\"". $db->result($result,0,"imap_host") ."\" ></td></tr>";
		echo "</table></div>";
	}
	else {
		echo "<input type=\"hidden\" name=\"IMAP_Test\" value=\"1\" >";
		
		echo "<div align='center'>&nbsp;<table class='tab_cadre' width='400'>";
		echo "<tr><th colspan='2'>".$lang["setup"][162]."</th></tr>";
		echo "<tr class='tab_bg_2'><td align='center'><p class='red'>".$lang["setup"][165]."</p><p>".$lang["setup"][166]."</p></td></tr></table></div>";
	}
	echo "<p class=\"submit\"><input type=\"submit\" name=\"update_ext\" class=\"submit\" value=\"Valider\" ></p>";
	echo "</form>";
}


//TODO : add entries to french dict
function titleMailing(){
// Un titre pour la gestion du suivi par mail
		
		GLOBAL  $lang,$HTMLRel;
                echo "<div align='center'><table border='0'><tr><td>";
                echo "<img src=\"".$HTMLRel."pics/mail.png\" alt='' title=''></td><td><span class='icon_nav'>Suivi des interventions par mail</span>";
		 echo "</b></td></tr></table></div>";



}


//TODO : add entries to french dict
function showFormMailing($target) {
	
		$db = new DB;
		$query = "select * from glpi_config where ID = 1";
		$result = $db->query($query);
		echo "<form action=\"$target\" method=\"post\">";
		
		
		echo "<div align='center'><table class='tab_cadre' width='600''><tr><th colspan='3'>Configuration de la fonction suivi par mail</th></tr>";
		
			if (function_exists('mail')) {
		
		echo "<tr class='tab_bg_2'><td >Utiliser les Mailing :</td><td align='center'>&nbsp; Oui  &nbsp;<input type=\"radio\" name=\"mailing\" value=\"1\" "; if($db->result($result,0,"mailing") == 1) echo "checked"; echo " > &nbsp;Non  &nbsp;<input type=\"radio\" name=\"mailing\" value=\"0\" "; if($db->result($result,0,"mailing") == 0) echo "checked"; echo " ></td></tr>";
		echo "<tr class='tab_bg_2'><td >Mail de l'administrateur systeme :</td><td> <input type=\"text\" name=\"admin_email\" size='40' value=\"".$db->result($result,0,"admin_email")."\"> </td></tr>";
		echo "<tr class='tab_bg_2'><td >Signature automatique  : </td><td><input type=\"text\" name=\"mailing_signature\" size='40' value=\"".$db->result($result,0,"mailing_signature")."\" ></td></tr></table>";
		
		echo "<p><b> Options de configuration </b></p>";
		
		echo "<table class='tab_cadre' width='600''><tr><th colspan='3'>L'administrateur Système doit recevoir une notification:<th></tr>";
		echo "<tr class='tab_bg_2'><td>A chaque nouvelle intervention</td><td>Oui : <input type=\"radio\" name=\"mailing_new_admin\" value=\"1\" "; if($db->result($result,0,"mailing_new_admin") == 1) echo "checked"; echo " ></td><td>Non : <input type=\"radio\" name=\"mailing_new_admin\" value=\"0\" "; if($db->result($result,0,"mailing_new_admin") == 0) echo "checked"; echo " ></td></tr>";
		
		//echo "<tr class='tab_bg_2'><td >A chaque changement de responsable</td><td>Oui : <input type=\"radio\" name=\"mailing_attrib_admin\" value=\"1\" "; if($db->result($result,0,"mailing_attrib_admin") == 1) echo "checked"; echo " ></td><td>Non : <input type=\"radio\" name=\"mailing_attrib_admin\" value=\"0\" "; if($db->result($result,0,"mailing_attrib_admin") == 0) echo "checked"; echo " ></td></tr>";
		
		echo "<tr class='tab_bg_2'><td >Pour chaque nouveau suivi</td><td>Oui : <input type=\"radio\" name=\"mailing_followup_admin\" value=\"1\" "; if($db->result($result,0,"mailing_followup_admin") == 1) echo "checked"; echo "></td><td>Non : <input type=\"radio\" name=\"mailing_followup_admin\" value=\"0\" "; if($db->result($result,0,"mailing_followup_admin") == 0) echo "checked"; echo " ></td></tr>";
		
		echo "<tr class='tab_bg_2'><td>A chaque fois qu'une intervention est marquée comme terminée</td><td>Oui : <input type=\"radio\" name=\"mailing_finish_admin\" value=\"1\" "; if($db->result($result,0,"mailing_finish_admin") == 1) echo "checked"; echo " ></td><td>Non : <input type=\"radio\" name=\"mailing_finish_admin\" value=\"0\" "; if($db->result($result,0,"mailing_finish_admin") == 0) echo "checked"; echo " ></td></tr>";
		
		echo "<tr class='tab_bg_2'><th colspan='3'>Les utilisateurs ayant un accés Admin doivent recevoir une notification :</th></tr>";
		
		echo "<tr class='tab_bg_2'><td>A chaque nouvelle intervention</td><td>Oui : <input type=\"radio\" name=\"mailing_new_all_admin\" value=\"1\" "; if($db->result($result,0,"mailing_new_all_admin") == 1) echo "checked"; echo  " ></td><td>Non : <input type=\"radio\" name=\"mailing_new_all_admin\" value=\"0\" "; if($db->result($result,0,"mailing_new_all_admin") == 0) echo "checked"; echo  " ></td></tr>";
		
		//echo "<tr class='tab_bg_2'><td>A chaque changement de responsable</td><td>Oui : <input type=\"radio\" name=\"mailing_attrib_all_admin\" value=\"1\"  "; if($db->result($result,0,"mailing_attrib_all_admin") == 1) echo "checked"; echo  " ></td><td>Non : <input type=\"radio\" name=\"mailing_attrib_all_admin\" value=\"0\"  "; if($db->result($result,0,"mailing_attrib_all_admin") == 0) echo "checked"; echo  " ></td></tr>";
		
		echo "<tr class='tab_bg_2'><td>Pour chaque nouveau suivi</td><td>Oui : <input type=\"radio\" name=\"mailing_followup_all_admin\" value=\"1\"  "; if($db->result($result,0,"mailing_followup_all_admin") == 1) echo "checked"; echo  " ></td><td>Non : <input type=\"radio\" name=\"mailing_followup_all_admin\" value=\"0\"  "; if($db->result($result,0,"mailing_followup_all_admin") == 0) echo "checked"; echo  " ></td></tr>";
		
		echo "<tr class='tab_bg_2'><td>A chaque fois qu'une intervention est marquée comme terminée</td><td>Oui : <input type=\"radio\" name=\"mailing_finish_all_admin\" value=\"1\"  "; if($db->result($result,0,"mailing_finish_all_admin") == 1) echo "checked"; echo  " ></td><td>Non : <input type=\"radio\" name=\"mailing_finish_all_admin\" value=\"0\"  "; if($db->result($result,0,"mailing_finish_all_admin") == 0) echo "checked"; echo  " ></td></tr>";
		
		
		echo "<tr'><th colspan='3'>Les utilisateurs ayant un accés Normal doivent recevoir une notification :</th></tr>";
		echo "<tr class='tab_bg_2'><td>A chaque nouvelle intervention</td><td>Oui : <input type=\"radio\" name=\"mailing_new_all_normal\" value=\"1\"  "; if($db->result($result,0,"mailing_new_all_normal") == 1) echo "checked"; echo  " ></td><td>Non : <input type=\"radio\" name=\"mailing_new_all_normal\" value=\"0\"  "; if($db->result($result,0,"mailing_new_all_normal") == 0) echo "checked"; echo  " ></td></tr>";
		//echo "<tr class='tab_bg_2'><td>A chaque changement de responsable</td><td>Oui : <input type=\"radio\" name=\"mailing_attrib_all_normal\" value=\"1\" "; if($db->result($result,0,"mailing_attrib_all_normal") == 1) echo "checked"; echo  "  ></td><td>Non : <input type=\"radio\" name=\"mailing_attrib_all_normal\" value=\"0\"  "; if($db->result($result,0,"mailing_attrib_all_normal") == 0) echo "checked"; echo  " ></td></tr>";
		
		echo "<tr class='tab_bg_2'><td>Pour chaque nouveau suivi</td><td>Oui : <input type=\"radio\" name=\"mailing_followup_all_normal\" value=\"1\" "; if($db->result($result,0,"mailing_followup_all_normal") == 1) echo "checked"; echo  " ></td><td>Non : <input type=\"radio\" name=\"mailing_followup_all_normal\" value=\"0\" "; if($db->result($result,0,"mailing_followup_all_normal") == 0) echo "checked"; echo  " ></td></tr>";
		
		echo "<tr class='tab_bg_2'><td>A chaque fois qu'une intervention est marquée comme terminée</td><td>Oui : <input type=\"radio\" name=\"mailing_finish_all_normal\" value=\"1\" "; if($db->result($result,0,"mailing_finish_all_normal") == 1) echo "checked"; echo  " ></td><td>Non : <input type=\"radio\" name=\"mailing_finish_all_normal\" value=\"0\" "; if($db->result($result,0,"mailing_finish_all_normal") == 0) echo "checked"; echo  " ></td></tr>";
		
		
		echo "<tr><th colspan='3'>La personne responsable de la tache doit recevoir un notification :</th></tr>";
		echo "<tr class='tab_bg_2'><td>A chaque nouvelle intervention</td><td>Oui : <input type=\"radio\" name=\"mailing_new_attrib\" value=\"1\" "; if($db->result($result,0,"mailing_new_attrib") == 1) echo "checked"; echo  " ></td><td>Non : <input type=\"radio\" name=\"mailing_new_attrib\" value=\"0\" "; if($db->result($result,0,"mailing_new_attrib") == 0) echo "checked"; echo  " ></td></tr>";
		
		//echo "<tr class='tab_bg_2'><td>A chaque changement de responsable</td><td>Oui : <input type=\"radio\" name=\"mailing_attrib_attrib\" value=\"1\" "; if($db->result($result,0,"mailing_attrib_attrib") == 1) echo "checked"; echo  " ></td><td>Non : <input type=\"radio\" name=\"mailing_attrib_attrib\" value=\"0\"  "; if($db->result($result,0,"mailing_attrib_attrib") == 0) echo "checked"; echo  " ></td></tr>";
		
		echo "<tr class='tab_bg_2'><td>Pour chaque nouveau suivi</td><td>Oui : <input type=\"radio\" name=\"mailing_followup_attrib\" value=\"1\" "; if($db->result($result,0,"mailing_followup_attrib") == 1) echo "checked"; echo  " ></td><td>Non : <input type=\"radio\" name=\"mailing_followup_attrib\" value=\"0\" "; if($db->result($result,0,"mailing_followup_attrib") == 0) echo "checked"; echo  " ></td></tr>";
		echo "<tr class='tab_bg_2'><td>A chaque fois qu'une intervention est marquée comme terminée</td><td>Oui : <input type=\"radio\" name=\"mailing_finish_attrib\" value=\"1\" "; if($db->result($result,0,"mailing_finish_attrib") == 1) echo "checked"; echo  " ></td><td>Non : <input type=\"radio\" name=\"mailing_finish_attrib\" value=\"0\" "; if($db->result($result,0,"mailing_finish_attrib") == 0) echo "checked"; echo  " ><td></td></tr>";
		
		echo "<tr><th colspan='3'>L'utilisateur demandeur doit recevoir une notification:</th></tr>";
		
		echo "<tr class='tab_bg_2'><td>A chaque nouvelle intervention le concernant</td><td>Oui : <input type=\"radio\" name=\"mailing_new_user\" value=\"1\" "; if($db->result($result,0,"mailing_new_user") == 1) echo "checked"; echo  " ></td><td>Non : <input type=\"radio\" name=\"mailing_new_user\" value=\"0\" "; if($db->result($result,0,"mailing_new_user") == 0) echo "checked"; echo  " ></td></tr>";
		
		//echo "<tr class='tab_bg_2'><td>A chaque changement de responsable d'une intervention le concernant</td><td>Oui : <input type=\"radio\" name=\"mailing_attrib_user\" value=\"1\" "; if($db->result($result,0,"mailing_attrib_user") == 1) echo "checked"; echo  " ></td><td>Non : <input type=\"radio\" name=\"mailing_attrib_user\" value=\"0\" "; if($db->result($result,0,"mailing_attrib_user") == 0) echo "checked"; echo  " ></td></tr>";
		
		echo "<tr class='tab_bg_2'><td>Pour chaque nouveau suivi sur une intervention le concernant</td><td>Oui : <input type=\"radio\" name=\"mailing_followup_user\" value=\"1\" "; if($db->result($result,0,"mailing_followup_user") == 1) echo "checked"; echo  " ></td><td>Non : <input type=\"radio\" name=\"mailing_followup_user\" value=\"0\" "; if($db->result($result,0,"mailing_followup_user") == 0) echo "checked"; echo  " ></td></tr>";
		
		echo "<tr class='tab_bg_2'><td>A chaque fois qu'une intervention le concernant est marquée comme terminée</td><td>Oui : <input type=\"radio\" name=\"mailing_finish_user\" value=\"1\"  "; if($db->result($result,0,"mailing_finish_user") == 1) echo "checked"; echo  " ></td><td>Non : <input type=\"radio\" name=\"mailing_finish_user\" value=\"0\" "; if($db->result($result,0,"mailing_finish_user") == 0) echo "checked"; echo  " ></td></tr>";
		echo "</table></div>";
		}
		else {
		
			
		echo "<tr class='tab_bg_2'><td align='center'><p class='red'>La fonction mail n'existe pas ou est désactivée sur votre système</p><p> Impossible de configurer les envois de suivis par mails</p></td></tr></table></div>";
		
		}
		
		echo "<p class=\"submit\"><input type=\"submit\" name=\"update_mailing\" class=\"submit\" value=\"Valider\" ></p>";
		echo "</form>";

}

function updateConfigGen($root_doc,$event_loglevel,$num_of_events,$expire_events,$jobs_at_login,$list_limit,$cut) {
	
	$db = new DB;
	
		$query = "update glpi_config set root_doc = '". $root_doc ."', ";
		$query.= "event_loglevel = '". $event_loglevel ."', num_of_events = '". $num_of_events ."', ";
		$query .= "expire_events = '". $expire_events ."', jobs_at_login = '". $jobs_at_login ."' , list_limit = '". $list_limit ."' , cut = '". $cut ."' where ID = '1' ";
		$db->query($query);
	
}


function updateLDAP($ldap_host,$ldap_basedn,$ldap_rootdn,$ldap_pass,$ldap_condition) {
	
	$db = new DB;
	//TODO : test the remote LDAP connection
	if(!empty($ldap_host)) {
		$query = "update glpi_config set ldap_host = '". $ldap_host ."', ";
		$query.= "ldap_basedn = '". $ldap_basedn ."', ldap_rootdn = '". $ldap_rootdn ."', ";
		$query .= "ldap_pass = '". $ldap_pass ."', ldap_condition = '". $ldap_condition ."' where ID = '1' ";
		$db->query($query);
	}
}
function updateIMAP($imap_auth_server,$imap_host) {
	$db = new DB;
	//TODO : test the remote IMAP connection
	if(!empty($imap_auth_server)) {
		$query = "update glpi_config set imap_auth_server = '". $imap_auth_server ."', ";
		$query.= "imap_host = '". $imap_host ."' where ID = '1'";
		$db->query($query);
	}
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
