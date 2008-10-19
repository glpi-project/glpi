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



class Enterprise extends CommonDBTM {

	/**
	 * Constructor
	**/
	function __construct () {
		$this->table="glpi_enterprises";
		$this->type=ENTERPRISE_TYPE;
		$this->entity_assign=true;
		$this->may_be_recursive=true;
	}


	function cleanDBonPurge($ID) {

		global $DB;

		$job=new Job;

		// Delete all enterprises associations from infocoms and contract
		$query3 = "DELETE FROM glpi_contract_enterprise WHERE (FK_enterprise = \"$ID\")";
		$result3 = $DB->query($query3);

		// Delete all contact enterprise associations
		$query2 = "DELETE FROM glpi_contact_enterprise WHERE (FK_enterprise = \"$ID\")";
		$result2 = $DB->query($query2);
	}

	function defineTabs($withtemplate){
		global $LANG,$CFG_GLPI;

		if(haveRight("contact_enterprise","r")){
			$ong[1] = $LANG["title"][26];
		}
		if (haveRight("contract","r")){
			$ong[4] = $LANG["Menu"][26];
		}
		$ong[15] = $LANG["financial"][104];
		if (haveRight("document","r")){
			$ong[5] = $LANG["Menu"][27];
		}
		if (haveRight("show_all_ticket","1")){
			$ong[6] = $LANG["title"][28];
		}
		if (haveRight("link","r")){
			$ong[7] = $LANG["title"][34];
		}
		if (haveRight("notes","r")){
			$ong[10] = $LANG["title"][37];
		}

		return $ong;
	}


	// SPECIFIC FUNCTION

/*
	// NOT_USED
	function countContacts() {
		global $DB;
		$query = "SELECT * FROM glpi_contact_enterprise WHERE (FK_enterprise = '".$this->fields["ID"]."')";
		if ($result = $DB->query($query)) {
			$number = $DB->numrows($result);
			return $number;
		} else {
			return false;
		}
	}
*/
	/**
	 * Print the enterprise form
	 *
	 *@param $target form target
	 *@param $ID Integer : Id of the computer or the template to print
	 *@param $withtemplate='' boolean : template or basic computer
	 *
	 *@return Nothing (display)
	 **/
	function showForm ($target,$ID,$withtemplate='') {
		// Show Enterprise or blank form

		global $CFG_GLPI,$LANG;

		if (!haveRight("contact_enterprise","r")) return false;

		$use_cache=true;

		if ($ID > 0){
			$this->check($ID,'r');
		} else {
			// Create item 
			$this->check(-1,'w');
			$use_cache=false;
			$this->getEmpty();
		} 

		$canedit=$this->can($ID,'w');
		
		$this->showTabs($ID, $withtemplate,$_SESSION['glpi_tab']);
		if ($canedit) {
			echo "<form method='post' action=\"$target\">";
			if (empty($ID)||$ID<0){
				echo "<input type='hidden' name='FK_entities' value='".$_SESSION["glpiactive_entity"]."'>";
			}
		}
		echo "<div class='center' id='tabsbody' >";
		echo "<table class='tab_cadre_fixe'>";

		$this->showFormHeader($ID,2);

		if (!$use_cache||!($CFG_GLPI["cache"]->start($ID."_".$_SESSION["glpilanguage"],"GLPI_".$this->type))) {
			echo "<tr class='tab_bg_1'><td>".$LANG["common"][16].":		</td>";
			echo "<td>";
			autocompletionTextField("name","glpi_enterprises","name",$this->fields["name"],40,$this->fields["FK_entities"]);
			echo "</td>";

			echo "<td>".$LANG["financial"][79].":		</td><td>";
			dropdownValue("glpi_dropdown_enttype", "type", $this->fields["type"]);
			echo "</td></tr>";

			echo "<tr class='tab_bg_1'><td>".$LANG["help"][35].":		</td>";
			echo "<td>";
			autocompletionTextField("phonenumber","glpi_enterprises","phonenumber",$this->fields["phonenumber"],40,$this->fields["FK_entities"]);	
			echo "</td>";

			echo "<td valign='top' rowspan='4'>";
			echo $LANG["common"][25].":	</td>";
			echo "<td align='center'  rowspan='4'><textarea cols='35' rows='4' name='comments' >".$this->fields["comments"]."</textarea>";
			echo "</td></tr>";


			echo "<tr class='tab_bg_1'>";
			echo "<td>".$LANG["financial"][30].":		</td><td>";
			autocompletionTextField("fax","glpi_enterprises","fax",$this->fields["fax"],40,$this->fields["FK_entities"]);
			echo "</td>";
			echo "</tr>";

			echo "<tr class='tab_bg_1'><td>".$LANG["financial"][45].":		</td>";
			echo "<td>";
			autocompletionTextField("website","glpi_enterprises","website",$this->fields["website"],40,$this->fields["FK_entities"]);	
			echo "</td></tr>";

			echo "<tr class='tab_bg_1'>";
			echo "<td>".$LANG["setup"][14].":		</td><td>";
			autocompletionTextField("email","glpi_enterprises","email",$this->fields["email"],40,$this->fields["FK_entities"]);
			echo "</td></tr>";


			echo "<tr class='tab_bg_1'><td  rowspan='4'>".$LANG["financial"][44].":		</td>";
			echo "<td align='center' rowspan='4'><textarea cols='35' rows='4' name='address' >".$this->fields["address"]."</textarea>";
			echo "<td>".$LANG["financial"][100]."</td>";
			echo "<td>";
			autocompletionTextField("postcode","glpi_enterprises","postcode",$this->fields["postcode"],40,$this->fields["FK_entities"]);
			echo "</td>";
			echo "</tr>";

			echo "<tr class='tab_bg_1'>";
			echo "<td>".$LANG["financial"][101].":		</td><td>";
			autocompletionTextField("town","glpi_enterprises","town",$this->fields["town"],40,$this->fields["FK_entities"]);
			echo "</td></tr>";

			echo "<tr class='tab_bg_1'>";
			echo "<td>".$LANG["financial"][102].":		</td><td>";
			autocompletionTextField("state","glpi_enterprises","state",$this->fields["state"],40,$this->fields["FK_entities"]);
			echo "</td></tr>";

			echo "<tr class='tab_bg_1'>";
			echo "<td>".$LANG["financial"][103].":		</td><td>";
			autocompletionTextField("country","glpi_enterprises","country",$this->fields["country"],40,$this->fields["FK_entities"]);
			echo "</td></tr>";
	
			if ($use_cache){
				$CFG_GLPI["cache"]->end();
			}
		}

		if ($canedit) {
				echo "<tr>";

			if ($ID>0) {

				echo "<td class='tab_bg_2' valign='top' colspan='2'>";
				echo "<input type='hidden' name='ID' value=\"$ID\">\n";
				echo "<div class='center'><input type='submit' name='update' value=\"".$LANG["buttons"][7]."\" class='submit'></div>";
				echo "</td>\n\n";
				echo "<td class='tab_bg_2' valign='top' colspan='2'>\n";
				echo "<input type='hidden' name='ID' value=\"$ID\">\n";
				if (!$this->fields["deleted"])
					echo "<div class='center'><input type='submit' name='delete' value=\"".$LANG["buttons"][6]."\" class='submit'></div>";
				else {
					echo "<div class='center'><input type='submit' name='restore' value=\"".$LANG["buttons"][21]."\" class='submit'>";

					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' name='purge' value=\"".$LANG["buttons"][22]."\" class='submit'></div>";
				}

				echo "</td>";
				echo "</tr>";

			} else {
				echo "<td class='tab_bg_2' valign='top' colspan='4'>";
				echo "<div class='center'><input type='submit' name='add' value=\"".$LANG["buttons"][8]."\" class='submit'></div>";
				echo "</td>";
				echo "</tr>";

			}
			echo "</table></div></form>";
			
				
		} else { // canedit
			echo "</table></div>";				
		}
		
		echo "<div id='tabcontent'></div>";
		echo "<script type='text/javascript'>loadDefaultTab();</script>";

		return true;

	}

	/**
	 * Can I change recusvive flag to false
	 * check if there is "linked" object in another entity
	 * 
	 * Overloaded from CommonDBTM
	 *
	 * @return booleen
	 **/
	function canUnrecurs () {

		global $DB, $CFG_GLPI, $LINK_ID_TABLE;
		
		$ID  = $this->fields['ID'];
		$ent = $this->fields['FK_entities'];

		if ($ID<0 || !$this->fields['recursive']) {
			return true;
		}

		if (!parent::canUnrecurs()) {
			return false;
		}
		
		// Search linked device infocom
		$sql = "SELECT DISTINCT device_type FROM glpi_infocoms WHERE FK_enterprise=$ID";
		$res = $DB->query($sql);
		
		if ($res) while ($data = $DB->fetch_assoc($res)) {
			if (isset($LINK_ID_TABLE[$data["device_type"]]) && 
				in_array($table=$LINK_ID_TABLE[$data["device_type"]], $CFG_GLPI["specif_entities_tables"])) {

				if (countElementsInTable("glpi_infocoms, $table", 
					"glpi_infocoms.FK_enterprise=$ID AND glpi_infocoms.device_type=".$data["device_type"]." AND glpi_infocoms.FK_device=$table.ID AND $table.FK_entities!=$ent")>0) {
						return false;						
				}
			}			
		}
		
		return true;
	}

}

?>
