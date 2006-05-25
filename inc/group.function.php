<?php
/*
 * @version $Id: user.function.php 3510 2006-05-25 12:04:25Z moyo $
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.
 
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

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------


function showGroupDevice($ID){
	global $db,$cfg_glpi, $lang, $HTMLRel,$LINK_ID_TABLE,$INFOFORM_PAGES;
	
	$ci=new CommonItem();
	echo "<div align='center'><table class='tab_cadre'><tr><th>".$lang["common"][17]."</th><th>".$lang["common"][16]."</th></tr>";
	foreach ($cfg_glpi["linkuser_type"] as $type){
		$query="SELECT * from ".$LINK_ID_TABLE[$type]." WHERE FK_groups='$ID'";
		$result=$db->query($query);
		if ($db->numrows($result)>0){
			$ci->setType($type);
			$type_name=$ci->getType();
			$cansee=haveTypeRight($type,"r");
			while ($data=$db->fetch_array($result)){
				$link=$data["name"];
				if ($cansee) $link="<a href='".$cfg_glpi["root_doc"]."/".$INFOFORM_PAGES[$type]."?ID=".$data["ID"]."'>".$link."</a>";
				$linktype="";
				echo "<tr class='tab_bg_1'><td>$type_name</td><td>$link</td></tr>";
			}
		}

	}
	echo "</table></div>";
}

function showGroupUser($ID){
	global $db,$cfg_glpi, $lang, $HTMLRel;
	
	if (!haveRight("user","r")||!haveRight("group","r"))	return false;

	$canedit=haveRight("group","w");
	
	$nb_per_line=5;

	echo "<div align='center'><table class='tab_cadre'><tr><th colspan='$nb_per_line'>".$lang["Menu"][14]."</th></tr>";
	$query="SELECT glpi_users.* from glpi_users_groups LEFT JOIN glpi_users ON (glpi_users.ID = glpi_users_groups.FK_users) WHERE glpi_users_groups.FK_groups='$ID' ORDER BY glpi_users.name, glpi_users.realname";
	
	$result=$db->query($query);
	if ($db->numrows($result)>0){
		$i=0;
		
		while ($data=$db->fetch_array($result)){
			if ($i%$nb_per_line==0) {
				if ($i!=0) echo "</tr>";
				echo "<tr class='tab_bg_1'>";
			}
			if (empty($data["realname"]))
				$name=$data["name"];
			else $name=$data["realname"];
			echo "<td><a href='".$cfg_glpi["root_doc"]."/front/user.info.php?ID=".$data["ID"]."'>".$name.($cfg_glpi["view_ID"]?" (".$data["ID"].")":"")."</a>";
			echo "&nbsp;";
			if ($canedit)
				echo "<a href='".$_SERVER["PHP_SELF"]."?deleteuser=deleteuser&amp;gID=$ID&amp;ID=".$data["ID"]."'><img src='".$HTMLRel."pics/delete.png' alt='".$lang["buttons"][6]."'></a>";
			
			echo "</td>";
			$i++;
		}
		while ($i%$nb_per_line!=0){
			echo "<td>&nbsp;</td>";
			$i++;
		}
		echo "</tr>";
	}
	
	echo "</table></div><br>";

	if ($canedit){
		echo "<div align='center'><form method='post' action=\"".$cfg_glpi["root_doc"]."/front/group.form.php\">";
		echo "<table  class='tab_cadre_fixe'>";
	
		echo "<tr class='tab_bg_1'><th colspan='2'>".$lang["setup"][603]."</tr><tr><td class='tab_bg_2' align='center'>";
		echo "<input type='hidden' name='FK_groups' value='$ID'>";
		dropdownAllUsers("FK_users","glpi_users",0);
		echo "</td><td align='center' class='tab_bg_2'>";
		echo "<input type='submit' name='adduser' value=\"".$lang["buttons"][8]."\" class='submit'>";
		echo "</td></tr>";
	
		echo "</table></form></div>";
	}

}

function addUserGroup($uID,$gID){
	global $db;
if ($uID>0&&$gID>0){
	
	$query="INSERT INTO glpi_users_groups (FK_users,FK_groups ) VALUES ('$uID','$gID');";
	$result = $db->query($query);
}
}

function deleteUserGroup($ID){

global $db;
$query="DELETE FROM glpi_users_groups WHERE ID= '$ID';";
$result = $db->query($query);
}

?>
