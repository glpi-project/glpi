<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2008 by the INDEPNET Development Team.

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


/**
 * Show devices of a group
 *
 * @param $ID integer : group ID
 */
function showGroupDevice($ID){
	global $DB,$CFG_GLPI, $LANG,$LINK_ID_TABLE,$INFOFORM_PAGES;

	$ci=new CommonItem();
	echo "<div class='center'><table class='tab_cadre'><tr><th>".$LANG["common"][17]."</th>" .
			"<th>".$LANG["common"][16]."</th><th>".$LANG["entity"][0]."</th></tr>";
	foreach ($CFG_GLPI["linkuser_types"] as $type){
		$query="SELECT * from ".$LINK_ID_TABLE[$type]." WHERE FK_groups='$ID' " .
			getEntitiesRestrictRequest(" AND ", $LINK_ID_TABLE[$type], '', '', isset($CFG_GLPI["recursive_type"][$type]));
		$result=$DB->query($query);
		if ($DB->numrows($result)>0){
			$ci->setType($type);
			$type_name=$ci->getType();
			$cansee=haveTypeRight($type,"r");
			while ($data=$DB->fetch_array($result)){
				$link=($data["name"] ? $data["name"] : "(".$data["ID"].")");
				if ($cansee) $link="<a href='".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[$type]."?ID=".$data["ID"]."'>".$link."</a>";
				$linktype="";
				echo "<tr class='tab_bg_1'><td>$type_name</td><td>$link</td>";
				echo "<td>".getDropdownName("glpi_entities",$data['FK_entities'])."</td></tr>";
			}
		}

	}
	echo "</table></div>";
}

/**
 * Show users of a group
 *
 * @param $target string : where to go on action
 * @param $ID integer : group ID
 */
function showGroupUsers($target,$ID){
	global $DB,$CFG_GLPI, $LANG;

	if (!haveRight("user","r")||!haveRight("group","r"))	return false;

	$group=new Group();
	$rand=mt_rand();
	if ($group->getFromDB($ID)){
		$canedit=$group->can($ID,"w");
	
		$nb_per_line=3;
		if ($canedit) {
			$headerspan=$nb_per_line*2;	
			echo "<form name='groupuser_form$rand' id='groupuser_form$rand' method='post' action=\"$target\">";
		} else {
			$headerspan=$nb_per_line;
		}
	
		echo "<div class='center'><table class='tab_cadrehov'><tr><th colspan='$headerspan'>".$LANG["Menu"][14]."</th></tr>";
		$query="SELECT glpi_users.*,glpi_users_groups.ID as linkID from glpi_users_groups LEFT JOIN glpi_users ON (glpi_users.ID = glpi_users_groups.FK_users) WHERE glpi_users_groups.FK_groups='$ID' " .
				"ORDER BY glpi_users.name, glpi_users.realname, glpi_users.firstname";
	
		$used = array();

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
	
				$used[$data["ID"]]=$data["ID"];
				
				echo "<td>";
				echo formatUserName($data["ID"],$data["name"],$data["realname"],$data["firstname"],1);
				echo "</td>";
				$i++;
			}
			while ($i%$nb_per_line!=0){
				echo "<td>&nbsp;</td>";
				if ($canedit) echo "<td>&nbsp;</td>";
				$i++;
			}
			echo "</tr>";
		}
	
		echo "</table></div>";
	
		if ($canedit){

			echo "<div class='center'>";
			echo "<table width='80%' class='tab_glpi'>";
			echo "<tr><td><img src=\"".$CFG_GLPI["root_doc"]."/pics/arrow-left.png\" alt=''></td><td class='center'><a onclick= \"if ( markAllRows('groupuser_form$rand') ) return false;\" href='".$_SERVER['PHP_SELF']."?ID=$ID&amp;select=all'>".$LANG["buttons"][18]."</a></td>";
	
			echo "<td>/</td><td class='center'><a onclick= \"if ( unMarkAllRows('groupuser_form$rand') ) return false;\" href='".$_SERVER['PHP_SELF']."?ID=$ID&amp;select=none'>".$LANG["buttons"][19]."</a>";
			echo "</td><td align='left' width='80%'>";
			echo "<input type='hidden' name='FK_groups' value='$ID'>";
			echo "<input type='submit' name='deleteuser' value=\"".$LANG["buttons"][6]."\" class='submit'>";
			echo "</td>";
			echo "</table>";
			echo "</div>";

			if ($group->fields["recursive"]) {
				$res=dropdownUsersSelect (true, "all", getEntitySons($group->fields["FK_entities"]), 0, $used);
			} else {
				$res=dropdownUsersSelect (true, "all", $group->fields["FK_entities"], 0, $used);
			}		
			$nb=($res ? $DB->result($res,0,"CPT") : 0);
			
			if ($nb) {		
				echo "<div class='center'>";
				echo "<table  class='tab_cadre_fixe'>";
				echo "<tr class='tab_bg_1'><th colspan='2'>".$LANG["setup"][603]."</tr><tr><td class='tab_bg_2' align='center'>";
				if ($group->fields["recursive"]) {
					dropdownUsers("FK_users",0,"all",-1,1,getEntitySons($group->fields["FK_entities"]),0,$used);
				} else {
					dropdownUsers("FK_users",0,"all",-1,1,$group->fields["FK_entities"],0,$used);
				}
				//dropdownAllUsers("FK_users",0,1,$group->fields["FK_entities"],0,$used);
				echo "</td><td align='center' class='tab_bg_2'>";
				echo "<input type='submit' name='adduser' value=\"".$LANG["buttons"][8]."\" class='submit'>";
				echo "</td></tr>";
		
				echo "</table></div><br>";
			}
	
			echo "</form>";
		}
	}
}

/**
 * Add a group to a user 
 *
 * @param $uID integer : user ID
 * @param $gID integer : group ID
 */
function addUserGroup($uID,$gID){
	global $DB;
	if ($uID>0&&$gID>0){

		$query="INSERT INTO glpi_users_groups (FK_users,FK_groups ) VALUES ('$uID','$gID');";
		$result = $DB->query($query);
	}
}

/**
 * Delete a group to a user 
 *
 * @param $ID integer : glpi_users_groups ID
 */
function deleteUserGroup($ID){

	global $DB;
	$query="DELETE FROM glpi_users_groups WHERE ID= '$ID';";
	$result = $DB->query($query);
}

/* // NOT_USED
function searchGroupID($name,$FK_entities){
	global $DB;
	$query ="SELECT ID from glpi_groups where name='$name' AND FK_entities='$FK_entities'";
	$result = $DB->query($query);
	if ($DB->numrows($result) > 0){
		$data= $DB->fetch_array($result);
		$groupID = $data['ID'];
		return $groupID;
	} 
	else return -1;
}
*/
?>
