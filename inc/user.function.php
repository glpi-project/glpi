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

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}


function showAddExtAuthUserForm($target){
	global $LANG;

	if (!haveRight("user","w")) return false;


	echo "<div class='center'>\n";
	echo "<form method='get' action=\"$target\">\n";

	echo "<table class='tab_cadre'>\n";
	echo "<tr><th colspan='4'>".$LANG["setup"][126]."</th></tr>\n";
	echo "<tr class='tab_bg_1'><td>".$LANG["login"][6]."</td>\n";
	echo "<td>";
	echo "<input type='text' name='login'>";
	echo "</td>";
	echo "<td align='center' class='tab_bg_2'>\n";
	echo "<input type='hidden' name='ext_auth' value='1'>\n";
	echo "<input type='submit' name='add_ext_auth_ldap' value=\"".$LANG["buttons"][8]." ".$LANG["login"][2]."\" class='submit'>\n";
	echo "</td>";
	echo "<td align='center' class='tab_bg_2'>\n";
	echo "<input type='submit' name='add_ext_auth_simple' value=\"".$LANG["buttons"][8]." ".$LANG["common"][62]."\" class='submit'>\n";
	echo "</td>";

	echo "</tr>\n";

	echo "</table>";
	echo "</form>\n";

	echo "</div>\n";

}

function dropdownUserType($myname,$value="post-only"){
	echo "<select name='$myname' >";
	echo "<option value=\"post-only\"";
	if ($value=="post-only") { echo " selected"; }
	echo ">Post Only</option>";
	echo "<option value=normal";
	if ($value=="normal") { echo " selected"; }
	echo ">Normal</option>";
	echo "<option value='admin'";
	if ($value=="admin") { echo " selected"; }
	echo ">Admin</option>";
	echo "<option value='super-admin'";
	if ($value=="super-admin") { echo " selected"; }
	echo ">Super-Admin</option>";
	echo "</select>";

}

function showDeviceUser($ID){
	global $DB,$CFG_GLPI, $LANG, $LINK_ID_TABLE,$INFOFORM_PAGES;

	$group_where="";
	$groups=array();
	$query="SELECT glpi_users_groups.FK_groups, glpi_groups.name FROM glpi_users_groups LEFT JOIN glpi_groups ON (glpi_groups.ID = glpi_users_groups.FK_groups) WHERE glpi_users_groups.FK_users='$ID';";
	$result=$DB->query($query);
	if ($DB->numrows($result)>0){
		$first=true;
		while ($data=$DB->fetch_array($result)){
			if ($first){
				$first=false;
			} else {
				$group_where.=" OR ";
			}
			$group_where.=" FK_groups = '".$data["FK_groups"]."' ";
			$groups[$data["FK_groups"]]=$data["name"];
		}
	}


	$ci=new CommonItem();
	echo "<div class='center'><table class='tab_cadre'><tr><th>".$LANG["common"][17]."</th><th>".$LANG["common"][16]."</th><th>&nbsp;</th></tr>";

	foreach ($CFG_GLPI["linkuser_type"] as $type){
		$query="SELECT * from ".$LINK_ID_TABLE[$type]." WHERE FK_users='$ID'";
		$result=$DB->query($query);
		if ($DB->numrows($result)>0){
			$ci->setType($type);
			$type_name=$ci->getType();
			$cansee=haveTypeRight($type,"r");
			while ($data=$DB->fetch_array($result)){
				$link=$data["name"];
				if ($cansee) $link="<a href='".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[$type]."?ID=".$data["ID"]."'>".$link.(($CFG_GLPI["view_ID"]||empty($link))?" (".$data["ID"].")":"")."</a>";
				$linktype="";
				if ($data["FK_users"]==$ID){
					$linktype=$LANG["common"][34];
				}
				echo "<tr class='tab_bg_1'><td>$type_name</td><td>$link</td><td>$linktype</td></tr>";
			}
		}

	}
	echo "</table></div><br>";

	if (!empty($group_where)){
		echo "<div class='center'><table class='tab_cadre'><tr><th>".$LANG["common"][17]."</th><th>".$LANG["common"][16]."</th><th>&nbsp;</th></tr>";
	
		foreach ($CFG_GLPI["linkuser_type"] as $type){
			$query="SELECT * from ".$LINK_ID_TABLE[$type]." WHERE $group_where";
			$result=$DB->query($query);
			if ($DB->numrows($result)>0){
				$ci->setType($type);
				$type_name=$ci->getType();
				$cansee=haveTypeRight($type,"r");
				while ($data=$DB->fetch_array($result)){
					$link=$data["name"];
					if ($cansee) $link="<a href='".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[$type]."?ID=".$data["ID"]."'>".$link.(($CFG_GLPI["view_ID"]||empty($link))?" (".$data["ID"].")":"")."</a>";
					$linktype="";
					if (isset($groups[$data["FK_groups"]])){
						$linktype=$LANG["common"][35]." ".$groups[$data["FK_groups"]];
					}
					echo "<tr class='tab_bg_1'><td>$type_name</td><td>$link</td><td>$linktype</td></tr>";
				}
			}
	
		}
		echo "</table></div><br>";
	}
}

function showGroupAssociated($target,$ID){
	global $DB,$CFG_GLPI, $LANG;

	if (!haveRight("user","r")||!haveRight("group","r"))	return false;

	$canedit=haveRight("user","w");
	$strict_entities=getUserEntities($ID,false);
	if (!haveAccessToOneOfEntities($strict_entities)&&!isViewAllEntities()){
		$canedit=false;
	}

	$nb_per_line=3;
	if ($canedit) $headerspan=$nb_per_line*2;
	else $headerspan=$nb_per_line;

	echo "<form name='groupuser_form' id='groupuser_form' method='post' action=\"$target\">";

	if ($canedit){
		echo "<div class='center'>";
		echo "<table  class='tab_cadre_fixe'>";

		echo "<tr class='tab_bg_1'><th colspan='2'>".$LANG["setup"][604]."</tr><tr><td class='tab_bg_2' align='center'>";
		echo "<input type='hidden' name='FK_users' value='$ID'>";
		$query="SELECT glpi_groups.ID, glpi_groups.name, glpi_entities.completename FROM glpi_groups LEFT JOIN glpi_entities ON (glpi_groups.FK_entities=glpi_entities.ID) WHERE glpi_groups.FK_entities IN (SELECT FK_entities FROM glpi_users_profiles WHERE FK_users = '$ID')";
		$result=$DB->query($query);
		if ($DB->numrows($result)>0){
			$groups=array();
			while ($data=$DB->fetch_array($result)){
				$groups[$data['ID']]=$data['name'];
				if (!empty($data['completename'])){
					$groups[$data['ID']].= ' - '.$data['completename'];
				}
			}
			dropdownArrayValues("FK_groups",$groups);

		}
		echo "</td><td align='center' class='tab_bg_2'>";
		echo "<input type='submit' name='addgroup' value=\"".$LANG["buttons"][8]."\" class='submit'>";
		echo "</td></tr>";

		echo "</table></div><br>";
	}

	echo "<div class='center'><table class='tab_cadrehov'><tr><th colspan='$headerspan'>".$LANG["Menu"][36]."</th></tr>";
	$query="SELECT glpi_groups.*, glpi_users_groups.ID AS IDD,glpi_users_groups.ID as linkID from glpi_users_groups LEFT JOIN glpi_groups ON (glpi_groups.ID = glpi_users_groups.FK_groups) WHERE glpi_users_groups.FK_users='$ID' ORDER BY glpi_groups.name";

	$result=$DB->query($query);
	if ($DB->numrows($result)>0){
		$i=0;

		while ($data=$DB->fetch_array($result)){
			if ($i%$nb_per_line==0) {
				if ($i!=0) echo "</tr>";
				echo "<tr class='tab_bg_1'>";
			}

			if ($canedit){
				echo "<td width='10'>";
				$sel="";
				if (isset($_GET["select"])&&$_GET["select"]=="all") $sel="checked";
				echo "<input type='checkbox' name='item[".$data["linkID"]."]' value='1' $sel>";
				echo "</td>";
			}

			echo "<td><a href='".$CFG_GLPI["root_doc"]."/front/group.form.php?ID=".$data["ID"]."'>".$data["name"].($CFG_GLPI["view_ID"]?" (".$data["ID"].")":"")."</a>";
			echo "&nbsp;";

			echo "</td>";
			$i++;
		}
		while ($i%$nb_per_line!=0){
			echo "<td>&nbsp;</td>";
			$i++;
		}
		echo "</tr>";
	}

	echo "</table></div>";

	if ($canedit){
		echo "<div class='center'>";
		echo "<table width='80%'>";
		echo "<tr><td><img src=\"".$CFG_GLPI["root_doc"]."/pics/arrow-left.png\" alt=''></td><td class='center'><a onclick= \"if ( markAllRows('groupuser_form') ) return false;\" href='".$_SERVER['PHP_SELF']."?ID=$ID&amp;select=all'>".$LANG["buttons"][18]."</a></td>";

		echo "<td>/</td><td class='center'><a onclick= \"if ( unMarkAllRows('groupuser_form') ) return false;\" href='".$_SERVER['PHP_SELF']."?ID=$ID&amp;select=none'>".$LANG["buttons"][19]."</a>";
		echo "</td><td align='left' width='80%'>";
		echo "<input type='submit' name='deletegroup' value=\"".$LANG["buttons"][6]."\" class='submit'>";
		echo "</td></tr>";
		echo "</table>";

		echo "</div>";

	}

	echo "</form>";

}

function showUserRights($target,$ID){
	global $DB,$CFG_GLPI, $LANG;

	if (!haveRight("user","r")||!haveRight("entity","r"))	return false;

	$canedit=haveRight("entity","w");
	$strict_entities=getUserEntities($ID,false);
	if (!haveAccessToOneOfEntities($strict_entities)&&!isViewAllEntities()){
		$canedit=false;
	}

	$canshowentity=haveRight("entity","r");
	$headerspan=1;
	if ($canedit) {
		$headerspan=2;
	}
	echo "<form name='entityuser_form' id='entityuser_form' method='post' action=\"$target\">";

	if ($canedit){
		echo "<div class='center'>";
		echo "<table  class='tab_cadre_fixe'>";

		echo "<tr class='tab_bg_1'><th colspan='4'>".$LANG["entity"][3]."</tr><tr class='tab_bg_2'><td class='center'>";
		echo "<input type='hidden' name='FK_users' value='$ID'>";
		dropdownValue("glpi_entities","FK_entities",0);
		echo "</td><td class='center'>";

		echo $LANG["profiles"][22].":";
		dropdownValue("glpi_profiles","FK_profiles");
		echo "</td><td class='center'>";
		echo $LANG["profiles"][28].":";
		dropdownYesNo("recursive",0);
		echo "</td><td class='center'>";
		echo "<input type='submit' name='addright' value=\"".$LANG["buttons"][8]."\" class='submit'>";
		echo "</td></tr>";

		echo "</table></div><br>";
	}

	echo "<div class='center'><table class='tab_cadrehov'><tr><th colspan='$headerspan'>".$LANG["Menu"][37]."</th><th>".$LANG["profiles"][22]." (D=".$LANG["profiles"][29].", R=".$LANG["profiles"][28].")</th></tr>";

	$query="SELECT DISTINCT glpi_users_profiles.ID as linkID, glpi_profiles.ID, glpi_profiles.name, glpi_users_profiles.recursive,
			glpi_users_profiles.dynamic, glpi_entities.completename, glpi_users_profiles.FK_entities
			FROM glpi_users_profiles 
			LEFT JOIN glpi_profiles ON (glpi_users_profiles.FK_profiles = glpi_profiles.ID)
			LEFT JOIN glpi_entities ON (glpi_users_profiles.FK_entities = glpi_entities.ID)
			WHERE glpi_users_profiles.FK_users='$ID';";

	$result=$DB->query($query);
	if ($DB->numrows($result)>0){
		$i=0;

		while ($data=$DB->fetch_array($result)){
			echo "<tr class='tab_bg_1'>";
			if ($canedit){
				echo "<td width='10'>";
				$sel="";
				if (isset($_GET["select"])&&$_GET["select"]=="all") $sel="checked";
				echo "<input type='checkbox' name='item[".$data["linkID"]."]' value='1' $sel>";
				echo "</td>";
			}
			if ($data["FK_entities"]==0) {
				$data["completename"]=$LANG["entity"][2];
			}
			echo "<td>";
			if ($canshowentity){
				echo "<a href='".$CFG_GLPI["root_doc"]."/front/entity.form.php?ID=".$data["FK_entities"]."'>";
			}
			echo $data["completename"].($CFG_GLPI["view_ID"]?" (".$data["FK_entities"].")":"");
			if ($canshowentity){
				echo "</a>";
			}
			echo "</td>";
			echo "<td>".$data["name"];
			if ($data["dynamic"]||$data["recursive"]){
				echo "<strong>&nbsp;(";
				if ($data["dynamic"]) echo "D";
				if ($data["dynamic"]&$data["recursive"]) echo ", ";
				if ($data["recursive"]) echo "R";
				echo ")</strong>";
			}

			echo "</td>";
			$i++;
		}
		echo "</tr>";
	}

	echo "</table></div>";

	if ($canedit){
		echo "<div class='center'>";
		echo "<table width='80%'>";
		echo "<tr><td><img src=\"".$CFG_GLPI["root_doc"]."/pics/arrow-left.png\" alt=''></td><td class='center'><a onclick= \"if ( markAllRows('entityuser_form') ) return false;\" href='".$_SERVER['PHP_SELF']."?ID=$ID&amp;select=all'>".$LANG["buttons"][18]."</a></td>";

		echo "<td>/</td><td class='center'><a onclick= \"if ( unMarkAllRows('entityuser_form') ) return false;\" href='".$_SERVER['PHP_SELF']."?ID=$ID&amp;select=none'>".$LANG["buttons"][19]."</a>";
		echo "</td><td align='left' width='80%'>";
		echo "<input type='submit' name='deleteright' value=\"".$LANG["buttons"][6]."\" class='submit'>";
		echo "</td></tr>";
		echo "</table>";

		echo "</div>";

	}

	echo "</form>";

}



function generateUserVcard($ID){

	$user = new User;
	$user->getFromDB($ID);

	// build the Vcard

	$vcard = new vCard();

	if (!empty($user->fields["realname"])||!empty($user->fields["firstname"])) $vcard->setName($user->fields["realname"], $user->fields["firstname"], "", ""); 
	else $vcard->setName($user->fields["name"], "", "", "");

	$vcard->setPhoneNumber($user->fields["phone"], "PREF;WORK;VOICE");
	$vcard->setPhoneNumber($user->fields["phone2"], "HOME;VOICE");
	$vcard->setPhoneNumber($user->fields["mobile"], "WORK;CELL");

	//if ($user->birthday) $vcard->setBirthday($user->birthday);

	$vcard->setEmail($user->fields["email"]);

	$vcard->setNote($user->fields["comments"]);

	// send the  VCard 

	$output = $vcard->getVCard();


	$filename =$vcard->getFileName();      // "xxx xxx.vcf"

	@Header("Content-Disposition: attachment; filename=\"$filename\"");
	@Header("Content-Length: ".strlen($output));
	@Header("Connection: close");
	@Header("content-type: text/x-vcard; charset=UTF-8");

	echo $output;

}

function getUserEntities($ID,$recursive=true){
	global $DB;

	$query="SELECT DISTINCT FK_entities, recursive
			FROM glpi_users_profiles 
			WHERE FK_users='$ID';";
	$result=$DB->query($query);
	if ($DB->numrows($result)>0){
		$entities=array();
		while ($data=$DB->fetch_assoc($result)){
			if ($data['recursive']&&$recursive){
				$tab=getSonsOfTreeItem('glpi_entities',$data['FK_entities']);
				$entities=array_merge($tab,$entities);
			} else {
				$entities[]=$data['FK_entities'];
			}
		}
		return $entities;
	} 

	return array();
}

/* Get all the authentication methods parameters for a specific auth_method and id_auth
	* and return it as an array 
	*/
function getAuthMethodsByID($auth_method, $id_auth) {
	global $DB;

	$auth_methods = array ();
	$sql = "";

	switch ($auth_method) {
		case AUTH_LDAP :
			//Get all the ldap directories
			$sql = "SELECT * FROM glpi_auth_ldap WHERE ID=" . $id_auth;
			break;
		case AUTH_MAIL :
			//Get all the pop/imap servers
			$sql = "SELECT * FROM glpi_auth_mail WHERE ID=" . $id_auth;
			break;
	}

	if ($sql != "") {
		$result = $DB->query($sql);
		if ($DB->numrows($result) > 0) {
			$auth_methods = $DB->fetch_array($result);
		}
	}
	//Return all the authentication methods in an array
	return $auth_methods;
}

function getAuthMethodName($auth_method, $id_auth, $link=0,$name=''){
	global $LANG,$CFG_GLPI;
	switch ($auth_method) {
		case AUTH_LDAP :
			if (empty($name)){
				$method = getAuthMethodsByID($auth_method,$id_auth);
				$name=$method["name"];
			}
			$out= $LANG["login"][2];
			if ($link && haveRight("config", "w")){
				return  $out."&nbsp " . $LANG["common"][52] . " <a href=\"" . $CFG_GLPI["root_doc"] . "/front/setup.auth.php?next=extauth_ldap&ID=" . $id_auth . "\">" . $name . "</a>";
			} else {
				return  $out."&nbsp " . $LANG["common"][52] . " " . $name;
			}
		break;
		case AUTH_MAIL :
			if (empty($name)){
				$method = getAuthMethodsByID($auth_method,$id_auth);
				$name=$method["name"];
			}
			$out= $LANG["login"][3];
			if ($link && haveRight("config", "w")){
				return  $out. "&nbsp " . $LANG["common"][52] . " <a href=\"" . $CFG_GLPI["root_doc"] . "/front/setup.auth.php?next=extauth_mail&ID=" . $id_auth . "\">" . $name . "</a>";
			} else {
				return  $out. "&nbsp " . $LANG["common"][52] . " " . $name;
			}
		break;
		case AUTH_CAS :
			return  $LANG["login"][4];
			break;
		case AUTH_DB_GLPI :
			return $LANG["login"][18];
		break;
		case NOT_YET_AUTHENTIFIED :
			return $LANG["login"][9];
			break;
	}
}

?>
