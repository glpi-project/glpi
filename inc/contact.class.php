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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

 

// CLASSES contact
class Contact extends CommonDBTM{

	function Contact () {
		$this->table="glpi_contacts";
		$this->type=CONTACT_TYPE;
	}

	function cleanDBonPurge($ID) {
		global $db;
			
		$query = "DELETE from glpi_contact_enterprise WHERE FK_contact = '$ID'";
		$db->query($query);
	}

	function defineOnglets($withtemplate){
		global $lang;
		$ong[1]=$lang["title"][26];
		if (haveRight("link","r"))	
			$ong[7]=$lang["title"][34];
		if (haveRight("notes","r"))
			$ong[10]=$lang["title"][37];
		return $ong;
	}


	function GetAddress() {
		global $db;

		$query = "SELECT  glpi_enterprises.name, glpi_enterprises.address, glpi_enterprises.postcode, glpi_enterprises.town, glpi_enterprises.state, glpi_enterprises.country FROM glpi_enterprises,glpi_contact_enterprise WHERE glpi_contact_enterprise.FK_contact = '".$this->fields["ID"]."' AND glpi_contact_enterprise.FK_enterprise = glpi_enterprises.ID";
		
		if ($result = $db->query($query)) 
		if ($db->numrows($result)){
				if ($data=$db->fetch_assoc($result))	
					return $data;
		} 
		
		return "";
		
	}

	function GetWebsite() {
		global $db;

		$query = "SELECT  glpi_enterprises.website as website FROM glpi_enterprises,glpi_contact_enterprise WHERE glpi_contact_enterprise.FK_contact = '".$this->fields["ID"]."' AND glpi_contact_enterprise.FK_enterprise = glpi_enterprises.ID";
		
		if ($result = $db->query($query)) 
		if ($db->numrows($result)){
			return $db->result($result, 0, "website");
		} else {
			return "";
		}
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
		if (haveRight("contact_enterprise","w")){
			echo "<td><a  class='icon_consol' href=\"contact.form.php?new=1\"><b>".$lang["financial"][24]."</b></a></td>";
		} else echo "<td><span class='icon_sous_nav'><b>".$lang["Menu"][22]."</b></span></td>";
		echo "</tr></table></div>";
	}
	
	/**
	* Print the contact form
	*
	*
	* Print général contact form
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
	
		if (!haveRight("contact_enterprise","r")) return false;
	
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
			echo $lang["financial"][33].":";
			
		} else {
			echo $lang["common"][18]." ID $ID:";
			echo "<a href='".$cfg_glpi["root_doc"]."/front/contact.vcard.php?ID=$ID'>Vcard</a>";
		}		
		echo "</b></th></tr>";
		
		echo "<tr><td class='tab_bg_1' valign='top'>";
	
		echo "<table cellpadding='1' cellspacing='0' border='0'>\n";
	
		echo "<tr><td>".$lang["common"][16].":	</td>";
		echo "<td>";
		autocompletionTextField("name","glpi_contacts","name",$this->fields["name"],30);	
		echo "</td></tr>";

		echo "<tr><td>".$lang["common"][43].":	</td>";
		echo "<td>";
		autocompletionTextField("firstname","glpi_contacts","firstname",$this->fields["firstname"],30);	
		echo "</td></tr>";
	
		echo "<tr><td>".$lang["financial"][29].": 	</td>";
		echo "<td>";
		autocompletionTextField("phone","glpi_contacts","phone",$this->fields["phone"],30);	
	
		echo "</td></tr>";
	
		echo "<tr><td>".$lang["financial"][29]." 2:	</td><td>";
		autocompletionTextField("phone2","glpi_contacts","phone2",$this->fields["phone2"],30);
		echo "</td></tr>";

		echo "<tr><td>".$lang["common"][42].":	</td><td>";
		autocompletionTextField("mobile","glpi_contacts","mobile",$this->fields["mobile"],30);
		echo "</td></tr>";

	
		echo "<tr><td>".$lang["financial"][30].":	</td><td>";
		autocompletionTextField("fax","glpi_contacts","fax",$this->fields["fax"],30);
		echo "</td></tr>";
		echo "<tr><td>".$lang["financial"][31].":	</td><td>";
		autocompletionTextField("email","glpi_contacts","email",$this->fields["email"],30);
		echo "</td></tr>";
		echo "<tr><td>".$lang["common"][17].":	</td>";
		echo "<td>";
		dropdownValue("glpi_dropdown_contact_type","type",$this->fields["type"]);
		echo "</td>";
		echo "</tr>";
	
		echo "</table>";
	
		echo "</td>\n";	
		
		echo "<td class='tab_bg_1' valign='top'>";
	
		echo "<table cellpadding='1' cellspacing='0' border='0'><tr><td>";
		echo $lang["common"][25].":	</td></tr>";
		echo "<tr><td align='center'><textarea cols='45' rows='4' name='comments' >".$this->fields["comments"]."</textarea>";
		echo "</td></tr></table>";
	
		echo "</td>";
		echo "</tr>";
		
		if (haveRight("contact_enterprise","w")) 
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
			if ($this->fields["deleted"]=='N')
			echo "<div align='center'><input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'></div>";
			else {
			echo "<div align='center'><input type='submit' name='restore' value=\"".$lang["buttons"][21]."\" class='submit'>";
			
			echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' name='purge' value=\"".$lang["buttons"][22]."\" class='submit'></div>";
			}
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