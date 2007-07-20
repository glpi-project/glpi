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

function showGroupDevice($ID){
	global $DB,$CFG_GLPI, $LANG,$LINK_ID_TABLE,$INFOFORM_PAGES;

	$ci=new CommonItem();
	echo "<div class='center'><table class='tab_cadre'><tr><th>".$LANG["common"][17]."</th><th>".$LANG["common"][16]."</th></tr>";
	foreach ($CFG_GLPI["linkuser_type"] as $type){
		$query="SELECT * from ".$LINK_ID_TABLE[$type]." WHERE FK_groups='$ID'";
		$result=$DB->query($query);
		if ($DB->numrows($result)>0){
			$ci->setType($type);
			$type_name=$ci->getType();
			$cansee=haveTypeRight($type,"r");
			while ($data=$DB->fetch_array($result)){
				$link=$data["name"];
				if ($cansee) $link="<a href='".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[$type]."?ID=".$data["ID"]."'>".$link."</a>";
				$linktype="";
				echo "<tr class='tab_bg_1'><td>$type_name</td><td>$link</td></tr>";
			}
		}

	}
	echo "</table></div>";
}

function showGroupUser($target,$ID){
	global $DB,$CFG_GLPI, $LANG;

	if (!haveRight("user","r")||!haveRight("group","r"))	return false;

	$canedit=haveRight("group","w");

	$nb_per_line=3;
	if ($canedit) $headerspan=$nb_per_line*2;
	else $headerspan=$nb_per_line;

	echo "<form name='groupuser_form' id='groupuser_form' method='post' action=\"$target\">";

	$group=new Group();

	if ($group->getFromDB($ID)){
		if ($canedit){
	
			echo "<div class='center'>";
			echo "<table  class='tab_cadre_fixe'>";
			echo "<tr class='tab_bg_1'><th colspan='2'>".$LANG["setup"][603]."</tr><tr><td class='tab_bg_2' align='center'>";
			echo "<input type='hidden' name='FK_groups' value='$ID'>";
			dropdownAllUsers("FK_users",0,1,$group->fields["FK_entities"]);
			echo "</td><td align='center' class='tab_bg_2'>";
			echo "<input type='submit' name='adduser' value=\"".$LANG["buttons"][8]."\" class='submit'>";
			echo "</td></tr>";
	
			echo "</table></div><br>";
	
		}
	
	
	
		echo "<div class='center'><table class='tab_cadrehov'><tr><th colspan='$headerspan'>".$LANG["Menu"][14]."</th></tr>";
		$query="SELECT glpi_users.*,glpi_users_groups.ID as linkID from glpi_users_groups LEFT JOIN glpi_users ON (glpi_users.ID = glpi_users_groups.FK_users) WHERE glpi_users_groups.FK_groups='$ID' ORDER BY glpi_users.name, glpi_users.realname, glpi_users.firstname";
	
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
			echo "<table width='80%'>";
			echo "<tr><td><img src=\"".$CFG_GLPI["root_doc"]."/pics/arrow-left.png\" alt=''></td><td class='center'><a onclick= \"if ( markAllRows('groupuser_form') ) return false;\" href='".$_SERVER['PHP_SELF']."?ID=$ID&amp;select=all'>".$LANG["buttons"][18]."</a></td>";
	
			echo "<td>/</td><td class='center'><a onclick= \"if ( unMarkAllRows('groupuser_form') ) return false;\" href='".$_SERVER['PHP_SELF']."?ID=$ID&amp;select=none'>".$LANG["buttons"][19]."</a>";
			echo "</td><td align='left' width='80%'>";
			echo "<input type='submit' name='deleteuser' value=\"".$LANG["buttons"][6]."\" class='submit'>";
			echo "</td>";
			echo "</table>";
	
			echo "</div>";
	
		}
		echo "</form>";
	}

}

function addUserGroup($uID,$gID){
	global $DB;
	if ($uID>0&&$gID>0){

		$query="INSERT INTO glpi_users_groups (FK_users,FK_groups ) VALUES ('$uID','$gID');";
		$result = $DB->query($query);
	}
}

function deleteUserGroup($ID){

	global $DB;
	$query="DELETE FROM glpi_users_groups WHERE ID= '$ID';";
	$result = $DB->query($query);
}

function isLdapConfigured()
{
	global $DB;
	$query="SELECT ldap_host from glpi_auth_ldap WHERE ldap_host IS NOT NULL;";
	$result = $DB->query($query);
	if ($DB->numrows($result) > 0)
		return true;
	else
		return false;	
}
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
?>
