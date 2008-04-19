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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}


// CLASSES contact
class Contact extends CommonDBTM{

	/**
	 * Constructor
	 **/
	function Contact () {
		$this->table="glpi_contacts";
		$this->type=CONTACT_TYPE;
		$this->entity_assign=true;
		$this->may_be_recursive=true;
	}

	function cleanDBonPurge($ID) {
		global $DB;

		$query = "DELETE from glpi_contact_enterprise WHERE FK_contact = '$ID'";
		$DB->query($query);
	}

	function defineOnglets($withtemplate){
		global $LANG;
		$ong[1]=$LANG["title"][26];
		if (haveRight("document","r"))	
			$ong[5]=$LANG["Menu"][27];
		if (haveRight("link","r"))	
			$ong[7]=$LANG["title"][34];
		if (haveRight("notes","r"))
			$ong[10]=$LANG["title"][37];
		return $ong;
	}


	/**
	 * Get address of the contact (company one)
	 *
	 *@return string containing the address
	 *
	 **/
	function GetAddress() {
		global $DB;

		$query = "SELECT  glpi_enterprises.name, glpi_enterprises.address, glpi_enterprises.postcode, glpi_enterprises.town, glpi_enterprises.state, glpi_enterprises.country FROM glpi_enterprises,glpi_contact_enterprise WHERE glpi_contact_enterprise.FK_contact = '".$this->fields["ID"]."' AND glpi_contact_enterprise.FK_enterprise = glpi_enterprises.ID";

		if ($result = $DB->query($query)) 
			if ($DB->numrows($result)){
				if ($data=$DB->fetch_assoc($result))	
					return $data;
			} 

		return "";

	}

	/**
	 * Get website of the contact (company one)
	 *
	 *@return string containing the website
	 *
	 **/
	function GetWebsite() {
		global $DB;

		$query = "SELECT  glpi_enterprises.website as website FROM glpi_enterprises,glpi_contact_enterprise WHERE glpi_contact_enterprise.FK_contact = '".$this->fields["ID"]."' AND glpi_contact_enterprise.FK_enterprise = glpi_enterprises.ID";

		if ($result = $DB->query($query)) 
			if ($DB->numrows($result)){
				return $DB->result($result, 0, "website");
			} else {
				return "";
			}
	}

	/**
	 * Print the contact form
	 *
	 *@param $target filename : where to go when done.
	 *@param $ID Integer : Id of the contact to print
	 *@param $withtemplate='' boolean : template or basic item
	 *
	 *
	 *@return Nothing (display)
	 *
	 **/
	function showForm ($target,$ID,$withtemplate='') {

		global $CFG_GLPI, $LANG;

		if (!haveRight("contact_enterprise","r")) return false;

		$spotted = false;
		$use_cache=true;


		if ($ID>0) {
			if($this->can($ID,'r')) {
				$spotted = true;	
			}
		} else {
			$use_cache=false;
			if ($this->can(-1,'w')){
				$spotted = true;	
			}
		} 


		if ($spotted){
			$canedit=$this->can($ID,'w');
			$canrecu=$this->can($ID,'recursive');

			$this->showOnglets($ID, $withtemplate,$_SESSION['glpi_onglet']);

			if ($canedit) {
				echo "<form method='post' name=form action=\"$target\"><div class='center'>";
				if (empty($ID)||$ID<0){
					echo "<input type='hidden' name='FK_entities' value='".$_SESSION["glpiactive_entity"]."'>";
				}
			}

			echo "<table class='tab_cadre_fixe' cellpadding='2' >";
			echo "<tr><th>";
			if (empty($ID)||$ID<0){
				echo $LANG["financial"][33];
			} else {
				echo $LANG["common"][2]." $ID";
			}
			if (isMultiEntitiesMode()){
				echo "&nbsp;(".getDropdownName("glpi_entities",$this->fields["FK_entities"]).")";
			}
			if ($ID) {				
				echo "&nbsp;<a href='".$CFG_GLPI["root_doc"]."/front/contact.vcard.php?ID=$ID'>".$LANG["common"][46]."</a>";
			}		

			echo "</th>";
			echo "<th>".$LANG["entity"][9].":&nbsp;";
			if ($canrecu) {
				dropdownYesNo("recursive",$this->fields["recursive"]);					
			} else {
				echo getYesNo($this->fields["recursive"]);
			}
			echo "</th>";
			echo "</tr>";

			
			if (!$use_cache||!($CFG_GLPI["cache"]->start($ID."_".$_SESSION["glpilanguage"],"GLPI_".$this->type))) {
				echo "<tr><td class='tab_bg_1' valign='top'>";
	
				echo "<table cellpadding='1' cellspacing='0' border='0'>\n";
	
				echo "<tr><td>".$LANG["common"][48].":	</td>";
				echo "<td>";
				autocompletionTextField("name","glpi_contacts","name",$this->fields["name"],30,$this->fields["FK_entities"]);	
				echo "</td></tr>";
	
				echo "<tr><td>".$LANG["common"][43].":	</td>";
				echo "<td>";
				autocompletionTextField("firstname","glpi_contacts","firstname",$this->fields["firstname"],30,$this->fields["FK_entities"]);	
				echo "</td></tr>";
	
				echo "<tr><td>".$LANG["help"][35].": 	</td>";
				echo "<td>";
				autocompletionTextField("phone","glpi_contacts","phone",$this->fields["phone"],30,$this->fields["FK_entities"]);	
	
				echo "</td></tr>";
	
				echo "<tr><td>".$LANG["help"][35]." 2:	</td><td>";
				autocompletionTextField("phone2","glpi_contacts","phone2",$this->fields["phone2"],30,$this->fields["FK_entities"]);
				echo "</td></tr>";
	
				echo "<tr><td>".$LANG["common"][42].":	</td><td>";
				autocompletionTextField("mobile","glpi_contacts","mobile",$this->fields["mobile"],30,$this->fields["FK_entities"]);
				echo "</td></tr>";
	
	
				echo "<tr><td>".$LANG["financial"][30].":	</td><td>";
				autocompletionTextField("fax","glpi_contacts","fax",$this->fields["fax"],30,$this->fields["FK_entities"]);
				echo "</td></tr>";
				echo "<tr><td>".$LANG["setup"][14].":	</td><td>";
				autocompletionTextField("email","glpi_contacts","email",$this->fields["email"],30,$this->fields["FK_entities"]);
				echo "</td></tr>";

				echo "<tr><td>".$LANG["common"][17].":	</td>";
				echo "<td>";
				dropdownValue("glpi_dropdown_contact_type","type",$this->fields["type"]);
				echo "</td>";
				echo "</tr>";
	
				echo "</table>";
	
				echo "</td>\n";	
	
				echo "<td class='tab_bg_1' valign='top'>";
	
				echo "<table cellpadding='1' cellspacing='0' border='0'><tr><td>";
				echo $LANG["common"][25].":	</td></tr>";
				echo "<tr><td class='center'><textarea cols='45' rows='4' name='comments' >".$this->fields["comments"]."</textarea>";
				echo "</td></tr></table>";
	
				echo "</td>";
				echo "</tr>";
				if ($use_cache){
					$CFG_GLPI["cache"]->end();
				}
			}

			if ($canedit) {
				if ($ID>0){
					echo "<tr>";
					echo "<td class='tab_bg_2' valign='top'>";
					echo "<input type='hidden' name='ID' value=\"$ID\">\n";
					echo "<div class='center'><input type='submit' name='update' value=\"".$LANG["buttons"][7]."\" class='submit' ></div>";
					echo "</td>\n\n";
					echo "<td class='tab_bg_2' valign='top'>\n";
					if (!$this->fields["deleted"])
						echo "<div class='center'><input type='submit' name='delete' value=\"".$LANG["buttons"][6]."\" class='submit'></div>";
					else {
						echo "<div class='center'><input type='submit' name='restore' value=\"".$LANG["buttons"][21]."\" class='submit'>";

						echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' name='purge' value=\"".$LANG["buttons"][22]."\" class='submit'></div>";
					}
					echo "</td>";
					echo "</tr>";

				} else {

					echo "<tr>";
					echo "<td class='tab_bg_2' valign='top' colspan='2'>";
					echo "<div class='center'><input type='submit' name='add' value=\"".$LANG["buttons"][8]."\" class='submit'></div>";
					echo "</td>";
					echo "</tr>";
				}
				echo "</table></div></form>";
			} else { // canedit
				echo "</table></div>";
			}

		} else { // con_spotted
			echo "<div class='center'><strong>".$LANG["common"][54]."</strong></div>";
			return false;

		}
		return true;
	}


}

?>
