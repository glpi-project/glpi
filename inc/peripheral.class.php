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

// CLASSES peripherals


class Peripheral  extends CommonDBTM  {

	/**
	 * Constructor
	**/
	function Peripheral () {
		$this->table="glpi_peripherals";
		$this->type=PERIPHERAL_TYPE;
		$this->dohistory=true;
		$this->entity_assign=true;

	}

	function defineTabs($withtemplate){
		global $LANG;
		$ong=array();
		if (haveRight("computer","r")){
			$ong[1]=$LANG["title"][26];
		}
		if (haveRight("contract","r") || haveRight("infocom","r")){
			$ong[4]=$LANG["Menu"][26];
		}
		if (haveRight("document","r")){
			$ong[5]=$LANG["Menu"][27];
		}

		if(empty($withtemplate)){
			if (haveRight("show_all_ticket","1")){
				$ong[6]=$LANG["title"][28];
			}
			if (haveRight("link","r")){
				$ong[7]=$LANG["title"][34];
			}
			if (haveRight("notes","r")){
				$ong[10]=$LANG["title"][37];
			}
			if (haveRight("reservation_central","r")){
				$ong[11]=$LANG["Menu"][17];
			}
				
			$ong[12]=$LANG["title"][38];
		}	
		return $ong;
	}

	function prepareInputForAdd($input) {

		if (isset($input["ID"])&&$input["ID"]>0){
			$input["_oldID"]=$input["ID"];
		}
		unset($input['ID']);
		unset($input['withtemplate']);

		return $input;
	}

	function post_addItem($newID,$input) {
		global $DB;

		// Manage add from template
		if (isset($input["_oldID"])){
			// ADD Infocoms
			$ic= new Infocom();
			if ($ic->getFromDBforDevice(PERIPHERAL_TYPE,$input["_oldID"])){
				$ic->fields["FK_device"]=$newID;
				unset ($ic->fields["ID"]);
				if (isset($ic->fields["num_immo"])) {
					$ic->fields["num_immo"] = autoName($ic->fields["num_immo"], "num_immo", 1, INFOCOM_TYPE,$input['FK_entities']);
				}
	
				$ic->addToDB();
			}
	
			// ADD Ports
			$query="SELECT ID from glpi_networking_ports WHERE on_device='".$input["_oldID"]."' AND device_type='".PERIPHERAL_TYPE."';";
			$result=$DB->query($query);
			if ($DB->numrows($result)>0){
	
				while ($data=$DB->fetch_array($result)){
					$np= new Netport();
					$np->getFromDB($data["ID"]);
					unset($np->fields["ID"]);
					unset($np->fields["ifaddr"]);
					unset($np->fields["ifmac"]);
					unset($np->fields["netpoint"]);
					$np->fields["on_device"]=$newID;
					$np->addToDB();
				}
			}
	
			// ADD Contract				
			$query="SELECT FK_contract from glpi_contract_device WHERE FK_device='".$input["_oldID"]."' AND device_type='".PERIPHERAL_TYPE."';";
			$result=$DB->query($query);
			if ($DB->numrows($result)>0){
	
				while ($data=$DB->fetch_array($result))
					addDeviceContract($data["FK_contract"],PERIPHERAL_TYPE,$newID);
			}
	
			// ADD Documents			
			$query="SELECT FK_doc from glpi_doc_device WHERE FK_device='".$input["_oldID"]."' AND device_type='".PERIPHERAL_TYPE."';";
			$result=$DB->query($query);
			if ($DB->numrows($result)>0){
	
				while ($data=$DB->fetch_array($result))
					addDeviceDocument($data["FK_doc"],PERIPHERAL_TYPE,$newID);
			}
		}

	}

	function cleanDBonPurge($ID) {
		global $DB,$CFG_GLPI;

		$job =new Job();
		$query = "SELECT * FROM glpi_tracking WHERE (computer = '$ID'  AND device_type='".PERIPHERAL_TYPE."')";
		$result = $DB->query($query);

		if ($DB->numrows($result))
			while ($data=$DB->fetch_array($result)) {
				if ($CFG_GLPI["keep_tracking_on_delete"]==1){
					$query = "UPDATE glpi_tracking SET computer = '0', device_type='0' WHERE ID='".$data["ID"]."';";
					$DB->query($query);
				} else $job->delete(array("ID"=>$data["ID"]));
			}

		$query="select * from glpi_reservation_item where (device_type='".PERIPHERAL_TYPE."' and id_device='$ID')";
		if ($result = $DB->query($query)) {
			if ($DB->numrows($result)>0){
				$rr=new ReservationItem();
				$rr->delete(array("ID"=>$DB->result($result,0,"ID")));
			}
		}

		$query = "DELETE FROM glpi_infocoms WHERE (FK_device = '$ID' AND device_type='".PERIPHERAL_TYPE."')";
		$result = $DB->query($query);

		$query="SELECT * FROM glpi_connect_wire WHERE (type='".PERIPHERAL_TYPE."' AND end1='$ID')";
		if ($result = $DB->query($query)) {
			if ($DB->numrows($result)>0) {
				while ($data = $DB->fetch_array($result)){
					// Disconnect without auto actions
					Disconnect($data["ID"],1,false);
				}
			}
		}

		$query = "DELETE FROM glpi_contract_device WHERE (FK_device = '$ID' AND device_type='".PERIPHERAL_TYPE."')";
		$result = $DB->query($query);
	}

	/**
	 * Print the peripheral form
	 *
	 *@param $target filename : where to go when done.
	 *@param $ID Integer : Id of the item to print
	 *@param $withtemplate integer template or basic item
	 *
	 *@return boolean item found
	 **/
	function showForm ($target,$ID,$withtemplate='') {

		global $CFG_GLPI, $LANG;

		if (!haveRight("peripheral","r")) return false;

		$use_cache=true;

		if ($ID > 0){
			$this->check($ID,'r');
		} else {
			// Create item 
			$this->check(-1,'w');
			$use_cache=false;
			$this->getEmpty();
		} 

		$this->showTabs($ID, $withtemplate,$_SESSION['glpi_tab']);
		if(!empty($withtemplate) && $withtemplate == 2) {
			$use_cache=false;
			$template = "newcomp";
			$datestring = $LANG["computers"][14].": ";
			$date = convDateTime($_SESSION["glpi_currenttime"]);
		} elseif(!empty($withtemplate) && $withtemplate == 1) { 
			$use_cache=false;
			$template = "newtemplate";
			$datestring = $LANG["computers"][14].": ";
			$date = convDateTime($_SESSION["glpi_currenttime"]);
		} else {
			$datestring = $LANG["common"][26].": ";
			$date = convDateTime($this->fields["date_mod"]);
			$template = false;
		}


		echo "<div class='center' id='tabsbody'>";
		echo "<form method='post' name=form action=\"$target\">";
		if(strcmp($template,"newtemplate") === 0) {
			echo "<input type=\"hidden\" name=\"is_template\" value=\"1\" />";
		}

		echo "<input type='hidden' name='FK_entities' value='".$this->fields["FK_entities"]."'>";

		echo "<table  class='tab_cadre_fixe' cellpadding='2'>";

		echo "<tr><th align='center' >";



		if(!$template) {
			echo $LANG["common"][2]." ".$this->fields["ID"];
		}elseif (strcmp($template,"newcomp") === 0) {
			echo $LANG["peripherals"][30].": ".$this->fields["tplname"];
			echo "<input type='hidden' name='tplname' value='".$this->fields["tplname"]."'>";
		}elseif (strcmp($template,"newtemplate") === 0) {
			echo $LANG["common"][6]."&nbsp;: ";
			autocompletionTextField("tplname","glpi_peripherals","tplname",$this->fields["tplname"],40,$this->fields["FK_entities"]);
		}
		if (isMultiEntitiesMode()){
			echo "&nbsp;(".getDropdownName("glpi_entities",$this->fields["FK_entities"]).")";
		}

		echo "</th><th  align='center'>".$datestring.$date;
		if (!$template&&!empty($this->fields['tplname']))
			echo "&nbsp;&nbsp;&nbsp;(".$LANG["common"][13].": ".$this->fields['tplname'].")";
		echo "</th></tr>";

		if (!$use_cache||!($CFG_GLPI["cache"]->start($ID."_".$_SESSION["glpilanguage"],"GLPI_".$this->type))) {
			echo "<tr><td class='tab_bg_1' valign='top'>";

			echo "<table cellpadding='1' cellspacing='0' border='0'>\n";

			echo "<tr><td>".$LANG["common"][16].($template?"*":"").":	</td>";
			echo "<td>";
			$objectName = autoName($this->fields["name"], "name", ($template === "newcomp"), PERIPHERAL_TYPE,$this->fields["FK_entities"]);
			autocompletionTextField("name","glpi_peripherals","name",$objectName,40,$this->fields["FK_entities"]);
			echo "</td></tr>";

			echo "<tr><td>".$LANG["common"][15].": 	</td><td>";
			dropdownValue("glpi_dropdown_locations", "location", $this->fields["location"],1,$this->fields["FK_entities"]);
			echo "</td></tr>";

			echo "<tr class='tab_bg_1'><td>".$LANG["common"][10].": 	</td><td colspan='2'>";
			dropdownUsersID("tech_num", $this->fields["tech_num"],"interface",1,$this->fields["FK_entities"]);
			echo "</td></tr>";

			echo "<tr><td>".$LANG["common"][21].":	</td><td>";
			autocompletionTextField("contact_num","glpi_peripherals","contact_num",$this->fields["contact_num"],40,$this->fields["FK_entities"]);		
			echo "</td></tr>";

			echo "<tr><td>".$LANG["common"][18].":	</td><td>";
			autocompletionTextField("contact","glpi_peripherals","contact",$this->fields["contact"],40,$this->fields["FK_entities"]);
			echo "</td></tr>";

			echo "<tr><td>".$LANG["common"][34].": 	</td><td>";
			dropdownAllUsers("FK_users", $this->fields["FK_users"],1,$this->fields["FK_entities"]);
			echo "</td></tr>";

			echo "<tr><td>".$LANG["common"][35].": 	</td><td>";
			dropdownValue("glpi_groups", "FK_groups", $this->fields["FK_groups"],1,$this->fields["FK_entities"]);
			echo "</td></tr>";

			

			echo "</table>";

			echo "</td>\n";	
			echo "<td class='tab_bg_1' valign='top'>";

			echo "<table cellpadding='1' cellspacing='0' border='0'>";

			echo "<tr><td>".$LANG["peripherals"][33].":</td><td>";
			globalManagementDropdown($target,$withtemplate,$this->fields["ID"],$this->fields["is_global"],$CFG_GLPI["peripherals_management_restrict"]);
			echo "</td></tr>";

			echo "<tr><td>".$LANG["common"][17].": 	</td><td>";
			dropdownValue("glpi_type_peripherals", "type", $this->fields["type"]);
			echo "</td></tr>";

			echo "<tr><td>".$LANG["common"][22].": 	</td><td>";
			dropdownValue("glpi_dropdown_model_peripherals", "model", $this->fields["model"]);
			echo "</td></tr>";

			echo "<tr class='tab_bg_1'><td>".$LANG["common"][5].": 	</td><td colspan='2'>";
			dropdownValue("glpi_dropdown_manufacturer","FK_glpi_enterprise",$this->fields["FK_glpi_enterprise"]);
			echo "</td></tr>";

			echo "<tr><td>".$LANG["peripherals"][18].":</td><td>";
			autocompletionTextField("brand","glpi_peripherals","brand",$this->fields["brand"],40,$this->fields["FK_entities"]);
			echo "</td></tr>";


			echo "<tr><td>".$LANG["common"][19].":	</td><td>";
			autocompletionTextField("serial","glpi_peripherals","serial",$this->fields["serial"],40,$this->fields["FK_entities"]);
			echo "</td></tr>";

			echo "<tr><td>".$LANG["common"][20].($template?"*":"").":</td><td>";
			$objectName = autoName($this->fields["otherserial"], "otherserial", ($template === "newcomp"), PERIPHERAL_TYPE,$this->fields["FK_entities"]);
			autocompletionTextField("otherserial","glpi_peripherals","otherserial",$objectName,40,$this->fields["FK_entities"]);

			echo "</td></tr>";


			echo "<tr><td>".$LANG["state"][0].":</td><td>";
			dropdownValue("glpi_dropdown_state", "state",$this->fields["state"]);
			echo "</td></tr>";



			echo "</table>";
			echo "</td>\n";	
			echo "</tr>";
			echo "<tr>";
			echo "<td class='tab_bg_1' valign='top' colspan='2'>";

			echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'><tr><td valign='top'>";
			echo $LANG["common"][25].":	</td>";
			echo "<td class='center'><textarea cols='35' rows='4' name='comments' >".$this->fields["comments"]."</textarea>";
			echo "</td></tr></table>";

			echo "</td>";
			echo "</tr>";
			if ($use_cache){
				$CFG_GLPI["cache"]->end();
			}
		}

		if (haveRight("peripheral","w")){
			echo "<tr>";

			if ($template) {

				if (empty($ID)||$withtemplate==2){
					echo "<td class='tab_bg_2' align='center' colspan='2'>\n";
					echo "<input type='hidden' name='ID' value=$ID>";
					echo "<input type='submit' name='add' value=\"".$LANG["buttons"][8]."\" class='submit'>";
					echo "</td>\n";
				} else {
					echo "<td class='tab_bg_2' align='center' colspan='2'>\n";
					echo "<input type='hidden' name='ID' value=$ID>";
					echo "<input type='submit' name='update' value=\"".$LANG["buttons"][7]."\" class='submit'>";
					echo "</td>\n";
				}


			} else {

				echo "<td class='tab_bg_2' valign='top' align='center'>";
				echo "<input type='hidden' name='ID' value=\"$ID\">\n";
				echo "<input type='submit' name='update' value=\"".$LANG["buttons"][7]."\" class='submit'>";
				echo "</td>";
				echo "<td class='tab_bg_2' valign='top'>\n";
				echo "<div class='center'>";
				if (!$this->fields["deleted"])
					echo "<input type='submit' name='delete' value=\"".$LANG["buttons"][6]."\" class='submit'>";
				else {
					echo "<input type='submit' name='restore' value=\"".$LANG["buttons"][21]."\" class='submit'>";
					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' name='purge' value=\"".$LANG["buttons"][22]."\" class='submit'>";
				}
				echo "</div>";
				echo "</td>";
			}
			echo "</tr>";
		}
		echo "</table></form></div>";
		echo "<div id='tabcontent'></div>";
		echo "<script type='text/javascript'>loadDefaultTab();</script>";

		return true;	
	}
}

?>
