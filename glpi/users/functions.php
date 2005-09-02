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

function showUserinfo($target,$ID) {
	
	// Affiche les infos User
	
	GLOBAL $cfg_layout, $lang;
	
	$user = new User();
	
	
	$user->getfromDBbyID($ID);
		
	
	
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

	echo "<div align='center' ><p><b>".$lang["tracking"][11]."</b></p></div>";
	
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
	// si on est dans le cas d'un modif on affiche la modif du login si ce n'est pas une auth externe
	} else {
		if (empty($user->fields["password"])){
			echo "<td align='center'><b>".$user->fields["name"]."</b>";
			echo "<input type='hidden' name='name' value=\"".$user->fields["name"]."\">";
			}
		else echo "<td><input  name='name' value=\"".$user->fields["name"]."\">";
		
		
		echo "<input type='hidden' name='ID' value=\"".$user->fields["ID"]."\">";
		
		echo "</td></tr>";
	}
	//do some rights verification
	if(isSuperAdmin($_SESSION["glpitype"])) {
		if (!empty($user->fields["password"])||$name==""){
			echo "<tr class='tab_bg_1'><td align='center'>".$lang["setup"][19]."</td><td><input type='password' name='password' value=\"".$user->fields["password"]."\" size='20' /></td></tr>";
		}
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
	

	echo "<form method='get' action=\"".$cfg_install["root"]."/users/users-search.php\">";
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
			// Pager
			$parameters="field=$field&amp;phrasetype=$phrasetype&amp;contains=$contains&amp;sort=$sort&amp;order=$order";
			printPager($start,$numrows,$target,$parameters);

			// Produce headline
			echo "<center><table  class='tab_cadre'><tr>";

			// Name
			echo "<th>";
			if ($sort=="glpi_users.name") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&amp;phrasetype=$phrasetype&amp;contains=$contains&amp;sort=glpi_users.name&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start\">";
			echo $lang["setup"][12]."</a></th>";
			
			// realname		
			echo "<th>";
			if ($sort=="glpi_users.realname") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&amp;phrasetype=$phrasetype&amp;contains=$contains&amp;sort=glpi_users.realname&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start\">";
			echo $lang["setup"][13]."</a></th>";

			// type
			echo "<th>";
			if ($sort=="glpi_users.type") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&amp;phrasetype=$phrasetype&amp;contains=$contains&amp;sort=glpi_users.type&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start\">";
			echo $lang["setup"][17]."</a></th>";			
			
			// email
			echo "<th>";
			if ($sort=="glpi_users.email") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&amp;phrasetype=$phrasetype&amp;contains=$contains&amp;sort=glpi_users.email&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start\">";
			echo $lang["setup"][14]."</a></th>";

						
			// Phone
			echo "<th>";
			if ($sort=="glpi_users.phone") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&amp;phrasetype=$phrasetype&amp;contains=$contains&amp;sort=glpi_users.phone&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start\">";
			echo $lang["setup"][15]."</a></th>";

			
						
			// Location			
			echo "<th>";
			if ($sort=="glpi_dropdown_locations.name") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&amp;phrasetype=$phrasetype&amp;contains=$contains&amp;sort=glpi_dropdown_locations.name&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start\">";
			echo $lang["setup"][16]."</a></th></tr>";



			

			for ($i=0; $i < $numrows_limit; $i++) {
			$name= $db->result($result_limit, $i, "name");
				
				$user = new User($name);
				$user->getFromDB($name);
							
				echo "<tr class='tab_bg_2'>";
				echo "<td><b>";
				echo "<a href=\"".$cfg_install["root"]."/users/users-info-form.php?name=".$user->fields["name"] ."\">";
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
			echo "<br>";
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

			return $user->addToDB();
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
	//if user type is already admin or super-admin do not touch it
	if(isset($input["type"])&&!isSuperAdmin($_SESSION["glpitype"])) {
		if(!empty($input["type"]) && $input["type"] != "normal" && $input["type"] != "post-only") {
			$input["type"] = "normal";
		}
		
	}
	
	
	// fill array for update
	$x=0;
	foreach ($input as $key => $val) {
		if (array_key_exists($key,$user->fields) &&  $input[$key] != $user->fields[$key]) {
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
		$user = new User($input["ID"]);
		$user->deleteFromDB($input["ID"]);
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
function updateSort($input) {

	$db = new DB;
	//print_r($input);
	$query = "UPDATE glpi_users SET tracking_order = '".$input["tracking_order"]."' WHERE (ID = '".$_SESSION["glpiID"]."')";
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
	$query = "UPDATE glpi_users SET language = '".$input["language"]."' WHERE (ID = '".$_SESSION["glpiID"]."')";
	if ($result=$db->query($query)) {
		$_SESSION["glpilanguage"] = $input["language"];
		return true;
	} else {
		return false;
	}
}

function showSortForm($target) {

	GLOBAL $cfg_layout, $lang;
	
	$order = $_SESSION["tracking_order"];
	
	echo "<div align='center'>\n";
	echo "<form method='post' action=\"$target\">\n";

	echo "<table class='tab_cadre' cellpadding='5' width='30%'>\n";
	echo "<tr><th colspan='2'>".$lang["setup"][40]."</th></tr>\n";
	echo "<tr><td width='100%' align='center' class='tab_bg_1'>\n";
	echo "<select name='tracking_order'>\n";
	echo "<option value=\"yes\"";
	if ($order=="yes") { echo " selected"; }	
	echo ">".$lang["choice"][1];
	echo "<option value=\"no\"";
	if ($order=="no") { echo " selected"; }
	echo ">".$lang["choice"][0];
	echo "</select>\n";
	echo "</td>\n";
	echo "<td align='center' class='tab_bg_2'>\n";
	echo "<input type='submit' name='updatesort' value=\"".$lang["buttons"][14]."\" class='submit'>\n";
	echo "</td></tr>\n";
	echo "</table>";
	echo "</form>\n";

	echo "</div>\n";
}
?>