<?php
/*
 * @version $Id$
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

 


class Enterprise extends CommonDBTM {

	function Enterprise () {
		$this->table="glpi_enterprises";
		$this->type=ENTERPRISE_TYPE;
	}


	function cleanDBonPurge($ID) {

		global $db;

		$job=new Job;

		// Delete all enterprises associations from infocoms and contract
		$query3 = "DELETE FROM glpi_contract_enterprise WHERE (FK_enterprise = \"$ID\")";
		$result3 = $db->query($query3);
				
		// Delete all contact enterprise associations
		$query2 = "DELETE FROM glpi_contact_enterprise WHERE (FK_enterprise = \"$ID\")";
		$result2 = $db->query($query2);
					
		/// TODO : UPDATE ALL FK_manufacturer to NULL
	}
	
	function defineOnglets($withtemplate){
		global $lang,$cfg_glpi;

			if(haveRight("contact_enterprise","r"))
				$ong[1] = $lang["title"][26];
			if (haveRight("contract_infocom","r"))	
				$ong[4] = $lang["Menu"][26];
			if (haveRight("document","r"))
				$ong[5] = $lang["title"][25];
			if (haveRight("show_ticket","1"))	
				$ong[6] = $lang["title"][28];
			if (haveRight("link","r"))
				$ong[7] = $lang["title"][34];
			if (haveRight("notes","r"))
				$ong[10] = $lang["title"][37];
		return $ong;
	}


	// SPECIFIC FUNCTION

	function countContacts() {
		global $db;
		$query = "SELECT * FROM glpi_contact_enterprise WHERE (FK_enterprise = '".$this->fields["ID"]."')";
		if ($result = $db->query($query)) {
			$number = $db->numrows($result);
			return $number;
		} else {
			return false;
		}
	}

	function title(){
	
		global  $lang,$HTMLRel;
	
		echo "<div align='center'><table border='0'><tr><td>";
		echo "<img src=\"".$HTMLRel."pics/entreprises.png\" alt='".$lang["financial"][25]."' title='".$lang["financial"][25]."'></td>";
		if (haveRight("contact_enterprise","w")){
			echo "<td><a  class='icon_consol' href=\"enterprise.form.php\"><b>".$lang["financial"][25]."</b></a></td>";
		} else echo "<td><span class='icon_sous_nav'><b>".$lang["Menu"][23]."</b></span></td>";
		echo "</tr></table></div>";
	}
	
	
	
	
	function showForm ($target,$ID) {
		// Show Enterprise or blank form
		
		global $cfg_glpi,$lang;
	
		if (!haveRight("contact_enterprise","r")) return false;
	
		$spotted=false;
		if (!$ID) {
			
			if($this->getEmpty()) $spotted = true;
		} else {
			if($ent->getfromDB($ID)) $spotted = true;
		}
		if ($spotted){
		echo "<form method='post' action=\"$target\"><div align='center'>";
		echo "<table class='tab_cadre_fixe'>";
		echo "<tr><th colspan='4'><b>";
		if (!$ID) {
			echo $lang["financial"][25].":";
		} else {
			echo $lang["financial"][26]." ID $ID:";
		}		
		echo "</b></th></tr>";
	
		echo "<tr class='tab_bg_1'><td>".$lang["common"][16].":		</td>";
		echo "<td>";
			autocompletionTextField("name","glpi_enterprises","name",$ent->fields["name"],25);
		echo "</td>";
	
		echo "<td>".$lang["financial"][79].":		</td><td colspan='2'>";
		dropdownValue("glpi_dropdown_enttype", "type", $ent->fields["type"]);
		echo "</td></tr>";
		
		echo "<tr class='tab_bg_1'><td>".$lang["financial"][29].":		</td>";
		echo "<td>";
			autocompletionTextField("phonenumber","glpi_enterprises","phonenumber",$ent->fields["phonenumber"],25);	
		echo "</td>";
	
		echo "<td>".$lang["financial"][30].":		</td><td>";
			autocompletionTextField("fax","glpi_enterprises","fax",$ent->fields["fax"],25);	
		echo "</td></tr>";
	
		echo "<tr class='tab_bg_1'><td>".$lang["financial"][45].":		</td>";
		echo "<td>";
			autocompletionTextField("website","glpi_enterprises","website",$ent->fields["website"],25);	
		echo "</td>";
		echo "<td>".$lang["financial"][31].":		</td><td>";
			autocompletionTextField("email","glpi_enterprises","email",$ent->fields["email"],25);		
		echo "</td></tr>";
		
	
		echo "<tr class='tab_bg_1'><td >".$lang["financial"][44].":		</td>";
		echo "<td align='center'><textarea cols='35' rows='4' name='address' >".$ent->fields["address"]."</textarea>";
	
		echo "<td valign='top'>";
		echo $lang["common"][25].":	</td>";
		echo "<td align='center' colspan='2'><textarea cols='35' rows='4' name='comments' >".$ent->fields["comments"]."</textarea>";
		echo "</td></tr>";
		
	
		if (haveRight("contact_enterprise","w"))
		if (!$ID) {
	
			echo "<tr>";
			echo "<td class='tab_bg_2' valign='top' colspan='4'>";
			echo "<div align='center'><input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'></div>";
			echo "</td>";
			echo "</tr>";
	
		} else {
	
			echo "<tr>";
			echo "<td class='tab_bg_2'></td>";
			echo "<td class='tab_bg_2' valign='top'>";
			echo "<input type='hidden' name='ID' value=\"$ID\">\n";
			echo "<div align='center'><input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'></div>";
			echo "</td>\n\n";
			echo "<td class='tab_bg_2'>&nbsp;</td><td class='tab_bg_2' valign='top'>\n";
			echo "<input type='hidden' name='ID' value=\"$ID\">\n";
			if ($ent->fields["deleted"]=='N')
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
		echo "<div align='center'><b>".$lang["financial"][39]."</b></div>";
		return false;
		}
		
		return true;
	
	}
	

	
}

?>