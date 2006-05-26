<?php
/*
 * @version $Id: contact.class.php 3364 2006-04-25 17:38:51Z moyo $
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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

 

// CLASSES contact
class Group extends CommonDBTM{

	function Group () {
		$this->table="glpi_groups";
		$this->type=CONTACT_TYPE;
	}

	function cleanDBonPurge($ID) {
		global $db,$cfg_glpi,$LINK_ID_TABLE;
			
		$query = "DELETE from glpi_users_groups WHERE FK_groups = '$ID'";
		$db->query($query);

		foreach ($cfg_glpi["linkuser_type"] as $type){
			$query2="UPDATE ".$LINK_ID_TABLE[$type]." SET FK_groups=0 WHERE FK_groups='$ID';";
			$db->query($query2);
		}
	}

	function post_getEmpty () {
		global $cfg_glpi;
		$this->fields["ldap_field"]=$cfg_glpi["ldap_field_group"];
	}


	function defineOnglets($withtemplate){
		global $lang;
		if (haveRight("user","r"))	
			$ong[1]=$lang["Menu"][14];

		$ong[2]=$lang["common"][1];
		return $ong;
	}



	/**
	* Print a good title for coontact pages
	*
	*
	*
	*
	*@return nothing (diplays)
	*
	**/
	function title(){
		global  $lang,$HTMLRel;
		echo "<div align='center'><table border='0'><tr><td>";
		echo "<img src=\"".$HTMLRel."pics/contacts.png\" alt='".$lang["financial"][24]."' title='".$lang["financial"][24]."'></td>";
		if (haveRight("group","w")){
			echo "<td><a  class='icon_consol' href=\"group.form.php?new=1\"><b>".$lang["setup"][602]."</b></a></td>";
		} else echo "<td><span class='icon_sous_nav'><b>".$lang["setup"][602]."</b></span></td>";
		echo "</tr></table></div>";
	}
	
	/**
	* Print the group form
	*
	*
	* Print group form
	*
	*@param $target filename : where to go when done.
	*@param $ID Integer : Id of the contact to print
	*
	*
	*@return Nothing (display)
	*
	**/
	function showForm ($target,$ID) {
	
		global $cfg_glpi, $lang,$HTMLRel;
	
		if (!haveRight("group","r")) return false;
	
		$con_spotted=false;
		
		if (empty($ID)) {
			
			if($this->getEmpty()) $con_spotted = true;
		} else {
			if($this->getfromDB($ID)) $con_spotted = true;
		}
		
		if ($con_spotted){
		echo "<form method='post' name=form action=\"$target\"><div align='center'>";
		echo "<table class='tab_cadre_fixe' cellpadding='2' >";
		echo "<tr><th colspan='2'><b>";
		if (empty($ID)) {
			echo $lang["setup"][605].":";
			
		} else {
			echo $lang["common"][35]." ID $ID:";
		}		
		echo "</b></th></tr>";
		
		echo "<tr><td class='tab_bg_1' valign='top'>";
	
		echo "<table cellpadding='1' cellspacing='0' border='0'>\n";
	
		echo "<tr><td>".$lang["common"][16].":	</td>";
		echo "<td>";
		autocompletionTextField("name","glpi_groups","name",$this->fields["name"],30);	
		echo "</td></tr>";
		
		if(!empty($cfg_glpi["ldap_host"])){
			echo "<tr><td>".$lang["setup"][600].":	</td>";
			echo "<td>";
			autocompletionTextField("ldap_field","glpi_groups","ldap_field",$this->fields["ldap_field"],30);	
			echo "</td></tr>";

			echo "<tr><td>".$lang["setup"][601].":	</td>";
			echo "<td>";
			autocompletionTextField("ldap_value","glpi_groups","ldap_value",$this->fields["ldap_value"],30);	
			echo "</td></tr>";
		}
	
		echo "</table>";
	
		echo "</td>\n";	
		
		echo "<td class='tab_bg_1' valign='top'>";
	
		echo "<table cellpadding='1' cellspacing='0' border='0'><tr><td>";
		echo $lang["common"][25].":	</td></tr>";
		echo "<tr><td align='center'><textarea cols='45' rows='4' name='comments' >".$this->fields["comments"]."</textarea>";
		echo "</td></tr></table>";
	
		echo "</td>";
		echo "</tr>";
		
		if (haveRight("group","w")) 
		if ($ID=="") {
	
			echo "<tr>";
			echo "<td class='tab_bg_2' valign='top' colspan='2'>";
			echo "<div align='center'><input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'></div>";
			echo "</td>";
			echo "</tr>";
	
	
		} else {
	
			echo "<tr>";
			echo "<td class='tab_bg_2' valign='top'>";
			echo "<input type='hidden' name='ID' value=\"$ID\">\n";
			echo "<div align='center'><input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit' ></div>";
			echo "</td>\n\n";
			echo "<td class='tab_bg_2' valign='top'>\n";
			echo "<div align='center'><input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'></div>";
			
			echo "</td>";
			echo "</tr>";
	
		}
		echo "</table></div></form>";
		
		} else {
		echo "<div align='center'><b>".$lang["financial"][38]."</b></div>";
		return false;
		
		}
		return true;
	}


}

?>