<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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


// CLASSES Networking


class Netdevice extends CommonDBTM {

	/**
	 * Constructor
	**/
	function __construct () {
		$this->table="glpi_networking";
		$this->type=NETWORKING_TYPE;
		$this->dohistory=true;
		$this->entity_assign=true;
		$this->may_be_recursive=true;
	}


	function defineTabs($ID,$withtemplate){
		global $LANG;

		$ong[1]=$LANG["title"][26];
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
			if ($ic->getFromDBforDevice(NETWORKING_TYPE,$input["_oldID"])){
				$ic->fields["FK_device"]=$newID;
				unset ($ic->fields["ID"]);
				if (isset($ic->fields["num_immo"])) {
					$ic->fields["num_immo"] = autoName($ic->fields["num_immo"], "num_immo", 1, INFOCOM_TYPE ,$input['FK_entities']);
				}
				$ic->addToDB();
			}
	
			// ADD Ports
			$query="SELECT ID 
				FROM glpi_networking_ports 
				WHERE on_device='".$input["_oldID"]."' AND device_type='".NETWORKING_TYPE."';";
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
			$query="SELECT FK_contract 
				FROM glpi_contract_device 
				WHERE FK_device='".$input["_oldID"]."' AND device_type='".NETWORKING_TYPE."';";
			$result=$DB->query($query);
			if ($DB->numrows($result)>0){
	
				while ($data=$DB->fetch_array($result))
					addDeviceContract($data["FK_contract"],NETWORKING_TYPE,$newID);
			}
	
			// ADD Documents			
			$query="SELECT FK_doc 
				FROM glpi_doc_device 
				WHERE FK_device='".$input["_oldID"]."' AND device_type='".NETWORKING_TYPE."';";
			$result=$DB->query($query);
			if ($DB->numrows($result)>0){
	
				while ($data=$DB->fetch_array($result))
					addDeviceDocument($data["FK_doc"],NETWORKING_TYPE,$newID);
			}
		}

	}

	function pre_deleteItem($ID) {
		removeConnector($ID);	
		return true;
	}


	function cleanDBonPurge($ID) {
		global $DB,$CFG_GLPI;


		$job =new Job();
		$query = "SELECT * FROM glpi_tracking WHERE computer = '$ID'  AND device_type='".NETWORKING_TYPE."'";
		$result = $DB->query($query);

		if ($DB->numrows($result))
			while ($data=$DB->fetch_array($result)) {
				if ($CFG_GLPI["keep_tracking_on_delete"]==1){
					$query = "UPDATE glpi_tracking SET computer = '0', device_type='0' WHERE ID='".$data["ID"]."';";
					$DB->query($query);
				} else $job->delete(array("ID"=>$data["ID"]));
			}

		$query = "SELECT ID FROM glpi_networking_ports WHERE on_device = '$ID' AND device_type = '".NETWORKING_TYPE."'";
		$result = $DB->query($query);
		while ($data = $DB->fetch_array($result)){
			$q = "DELETE FROM glpi_networking_wire WHERE end1 = '".$data["ID"]."' OR end2 = '".$data["ID"]."'";
			$result2 = $DB->query($q);				
		}


		$query = "DELETE FROM glpi_networking_ports WHERE on_device = '$ID' AND device_type = '".NETWORKING_TYPE."'";
		$result = $DB->query($query);

		$query = "DELETE FROM glpi_infocoms WHERE FK_device = '$ID' AND device_type='".NETWORKING_TYPE."'";
		$result = $DB->query($query);

		$query = "DELETE FROM glpi_contract_device WHERE FK_device = '$ID' AND device_type='".NETWORKING_TYPE."'";
		$result = $DB->query($query);

		$query="SELECT * FROM glpi_reservation_item WHERE device_type='".NETWORKING_TYPE."' AND id_device='$ID'";
		if ($result = $DB->query($query)) {
			if ($DB->numrows($result)>0) {
				$rr=new ReservationItem();
				$rr->delete(array("ID"=>$DB->result($result,0,"ID")));
			}
		}
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

		if ($ID<0 || !$this->fields['recursive']) {
			return true;
		}

		if (!parent::canUnrecurs()) {
			return false;
		}
		$entities = "(".$this->fields['FK_entities'];
		foreach (getEntityAncestors($this->fields['FK_entities']) as $papa) {
			$entities .= ",$papa";
		}
		$entities .= ")";

		// RELATION : networking -> _port -> _wire -> _port -> device

		// Evaluate connection in the 2 ways
		for ($tabend=array("end1"=>"end2","end2"=>"end1");list($enda,$endb)=each($tabend);) {
			
			$sql="SELECT device_type, GROUP_CONCAT(DISTINCT on_device) AS ids " .
				"FROM glpi_networking_wire, glpi_networking_ports " .
				"WHERE glpi_networking_wire.$endb = glpi_networking_ports.ID " .
				"AND   glpi_networking_wire.$enda IN (SELECT ID FROM glpi_networking_ports 
									WHERE device_type=".NETWORKING_TYPE." AND on_device='$ID') " .
				"GROUP BY device_type;";

			$res = $DB->query($sql);
			if ($res) while ($data = $DB->fetch_assoc($res)) {

				// For each device_type which are entity dependant
				if (isset($LINK_ID_TABLE[$data["device_type"]]) && 
					in_array($table=$LINK_ID_TABLE[$data["device_type"]], $CFG_GLPI["specif_entities_tables"])) {
	
					if (countElementsInTable("$table", "ID IN (".$data["ids"].") AND FK_entities NOT IN $entities")>0) {
							return false;						
					}
				}			
			}
		}
		
		return true;
	}

	/**
	 * Print the networking form
	 *
	 *@param $target filename : where to go when done.
	 *@param $ID Integer : Id of the item to print
	 *@param $withtemplate integer template or basic item
	 *
	 *@return boolean item found
	 **/
	function showForm ($target,$ID,$withtemplate='') {
		// Show device or blank form

		global $CFG_GLPI, $LANG;

		if (!haveRight("networking","r")) return false;

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
		$canrecu=$this->can($ID,'recursive');

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


		echo "<div class='center'  id='tabsbody'>";
		
		if ($canedit) {
			echo "<form name='form' method='post' action=\"$target\">\n";
			echo "<input type='hidden' name='FK_entities' value='".$this->fields["FK_entities"]."'>";
		}
		echo "<table  class='tab_cadre_fixe' cellpadding='2'>\n";

		$this->showFormHeader($ID, $withtemplate);

		if (!$use_cache||!($CFG_GLPI["cache"]->start($ID."_".$_SESSION['glpilanguage'],"GLPI_".$this->type))) {
			echo "<tr><td class='tab_bg_1' valign='top'>\n";

			echo "<table cellpadding='1' cellspacing='0' border='0'>\n";

			echo "<tr><td>".$LANG["common"][16].($template?"*":"").":	</td>\n";
			echo "<td>";
			$objectName = autoName($this->fields["name"], "name", ($template === "newcomp"), NETWORKING_TYPE,$this->fields["FK_entities"]);
			autocompletionTextField("name","glpi_networking","name",$objectName,40,$this->fields["FK_entities"]);
			echo "</td></tr>\n";

			echo "<tr class='tab_bg_1'><td>".$LANG["common"][5].": 	</td><td colspan='2'>\n";
			dropdownValue("glpi_dropdown_manufacturer","FK_glpi_enterprise",$this->fields["FK_glpi_enterprise"]);
			echo "</td></tr>\n";

			echo "<tr><td>".$LANG["common"][15].": 	</td><td>\n";
			dropdownValue("glpi_dropdown_locations", "location", $this->fields["location"],1,$this->fields["FK_entities"]);
			echo "</td></tr>\n";

			echo "<tr class='tab_bg_1'><td>".$LANG["common"][10].": 	</td><td colspan='2'>\n";
			dropdownUsersID("tech_num", $this->fields["tech_num"],"interface",1,$this->fields["FK_entities"]);
			echo "</td></tr>\n";

			echo "<tr><td>".$LANG["common"][21].":	</td><td>\n";
			autocompletionTextField("contact_num","glpi_networking","contact_num",$this->fields["contact_num"],40,$this->fields["FK_entities"]);
			echo "</td></tr>\n";

			echo "<tr><td>".$LANG["common"][18].":	</td><td>\n";
			autocompletionTextField("contact","glpi_networking","contact",$this->fields["contact"],40,$this->fields["FK_entities"]);
			echo "</td></tr>\n";

			echo "<tr><td>".$LANG["common"][34].": 	</td><td>";
			dropdownAllUsers("FK_users", $this->fields["FK_users"],1,$this->fields["FK_entities"]);
			echo "</td></tr>";

			echo "<tr><td>".$LANG["common"][35].": 	</td><td>";
			dropdownValue("glpi_groups", "FK_groups", $this->fields["FK_groups"],1,$this->fields["FK_entities"]);
			echo "</td></tr>";

			echo "<tr><td>".$LANG["state"][0].":</td><td>\n";
			dropdownValue("glpi_dropdown_state", "state",$this->fields["state"]);
			echo "</td></tr>\n";

			echo "<tr><td>$datestring</td><td>$date\n";
			if (!$template&&!empty($this->fields['tplname'])) {
				echo "&nbsp;&nbsp;&nbsp;(".$LANG["common"][13].": ".$this->fields['tplname'].")";
			}
			echo "</td></tr>\n";

			echo "</table>\n";

			echo "</td>\n";	
			echo "<td class='tab_bg_1' valign='top'>\n";

			echo "<table cellpadding='1' cellspacing='0' border='0'>\n";

			echo "<tr><td>".$LANG["common"][17].": 	</td><td>\n";
			dropdownValue("glpi_type_networking", "type", $this->fields["type"]);
			echo "</td></tr>\n";

			echo "<tr><td>".$LANG["common"][22].": 	</td><td>";
			dropdownValue("glpi_dropdown_model_networking", "model", $this->fields["model"]);
			echo "</td></tr>";

			echo "<tr><td>".$LANG["setup"][71].": 	</td><td>\n";
			dropdownValue("glpi_dropdown_firmware", "firmware", $this->fields["firmware"]);
			echo "</td></tr>\n";

			echo "<tr><td>".$LANG["networking"][5].":	</td><td>\n";
			autocompletionTextField("ram","glpi_networking","ram",$this->fields["ram"],40,$this->fields["FK_entities"]);
			echo "</td></tr>\n";

			echo "<tr><td>".$LANG["common"][19].":	</td><td>\n";
			autocompletionTextField("serial","glpi_networking","serial",$this->fields["serial"],40,$this->fields["FK_entities"]);
			echo "</td></tr>\n";

			echo "<tr><td>".$LANG["common"][20].($template?"*":"").":</td><td>\n";
			$objectName = autoName($this->fields["otherserial"], "otherserial", ($template === "newcomp"), NETWORKING_TYPE,$this->fields["FK_entities"]);
			autocompletionTextField("otherserial","glpi_networking","otherserial",$objectName,40,$this->fields["FK_entities"]);
			echo "</td></tr>\n";

			echo "<tr><td>".$LANG["setup"][88].": 	</td><td>\n";
			dropdownValue("glpi_dropdown_network", "network", $this->fields["network"]);
			echo "</td></tr>\n";

			echo "<tr><td>".$LANG["setup"][89].": 	</td><td>\n";
			dropdownValue("glpi_dropdown_domain", "domain", $this->fields["domain"]);
			echo "</td></tr>\n";

			echo "<tr><td>".$LANG["networking"][14].":</td><td>\n";
			autocompletionTextField("ifaddr","glpi_networking","ifaddr",$this->fields["ifaddr"],40,$this->fields["FK_entities"]);
			echo "</td></tr>\n";

			echo "<tr><td>".$LANG["networking"][15].":</td><td>\n";
			autocompletionTextField("ifmac","glpi_networking","ifmac",$this->fields["ifmac"],40,$this->fields["FK_entities"]);
			echo "</td></tr>\n";

			echo "</table>\n";

			echo "</td>\n";	
			echo "</tr>\n";
			echo "<tr>\n";
			echo "<td class='tab_bg_1' valign='top' colspan='2'>\n";

			echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'><tr><td valign='top'>\n";
			echo $LANG["common"][25].":	</td>\n";
			echo "<td class='center'><textarea cols='80' rows='4' name='comments' >".$this->fields["comments"]."</textarea>\n";
			echo "</td></tr></table>\n";

			echo "</td>";
			echo "</tr>\n";
			if ($use_cache){
				$CFG_GLPI["cache"]->end();
			}
		}

		if ($canedit) {
			echo "<tr class='tab_bg_2'>\n";
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

				echo "<td class='tab_bg_2' valign='top' width='33%'>";
				echo "<input type='hidden' name='ID' value=\"$ID\">\n";
				echo "<div class='center'><input type='submit' name='update' value=\"".$LANG["buttons"][7]."\" class='submit'></div></td>";
				echo "<td class='tab_bg_2' valign='top' width='33%'>\n";

				echo "<div class='center'>\n";
				if (!$this->fields["deleted"])
					echo "<input type='submit' name='delete' value=\"".$LANG["buttons"][6]."\" class='submit'>\n";
				else {
					echo "<input type='submit' name='restore' value=\"".$LANG["buttons"][21]."\" class='submit'>\n";

					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' name='purge' value=\"".$LANG["buttons"][22]."\" class='submit'>\n";
				}
				echo "</div>\n";
				echo "</td>\n";
			}
			echo "</tr>\n";
			echo "</table></form></div>\n";

		}else { // ! $canedit
			echo "</table></div>\n";
		}

		echo "<div id='tabcontent'></div>";
		echo "<script type='text/javascript'>loadDefaultTab();</script>";

		return true;

	}

}

/// Netport class
class Netport extends CommonDBTM {

	/// ID of the port connected to the current one
	var $contact_id		= 0;

	/// hardare data : name
	var $device_name	= "";
	/// hardare data : ID
	var $device_ID		= 0;
	/// hardare data : type
	var $device_type		= 0;
	/// hardare data : entity
	var $FK_entities		= -1;
	/// hardare data : location
	var $location		= -1;

	/**
	 * Constructor
	**/
	function __construct () {
		$this->table="glpi_networking_ports";
		$this->type = NETWORKING_PORT_TYPE;
	}

	function post_updateItem($input,$updates,$history=1){
		//$tomatch=array("netpoint","ifaddr","ifmac");
		// Only netpoint updates : ifaddr and ifmac may be different.
		$tomatch=array("netpoint");
		$updates=array_intersect($updates,$tomatch);
		if (count($updates)){
			$save_ID=$this->fields["ID"];
			$n=new Netwire;
			if ($this->fields["ID"]=$n->getOppositeContact($save_ID)){
				$this->updateInDB($updates);
			}
			$this->fields["ID"]=$save_ID;
		}
	}

	function prepareInputForUpdate($input) {
		// Is a preselected mac adress selected ?
		if (isset($input['pre_mac'])&&!empty($input['pre_mac'])){
			$input['ifmac']=$input['pre_mac'];
			unset($input['pre_mac']);
		}
		return $input;
	}


	function prepareInputForAdd($input) {
		if (isset($input["logical_number"])&&strlen($input["logical_number"])==0) unset($input["logical_number"]);
		//unset($input['search']);
		return $input;
	}

	function cleanDBonPurge($ID) {
		global $DB;

		$query = "DELETE FROM glpi_networking_wire WHERE (end1 = '$ID' OR end2 = '$ID')";
		$result = $DB->query($query);
	}

	// SPECIFIC FUNCTIONS

	/**
	 * Retrieve data in the port of the item which belongs to
	 *
	 *@param $ID Integer : Id of the item to print
	 *@param $type item type
	 *
	 *@return boolean item found
	 **/
	function getDeviceData($ID,$type)
	{
		global $DB,$LINK_ID_TABLE;

		$table = $LINK_ID_TABLE[$type];

		$query = "SELECT * FROM $table WHERE ID = '$ID'";
		if ($result=$DB->query($query))
		{
			$data = $DB->fetch_array($result);
			$this->device_name = $data["name"];
			$this->deleted = $data["deleted"];
			$this->FK_entities = $data["FK_entities"];
			$this->location = $data["location"];
			$this->device_ID = $ID;
			$this->device_type = $type;
			$this->recursive = (isset($data["recursive"])?$data["recursive"]:0);
			return true;
		}
		else 
		{
			return false;
		}
	}

	/**
	 * Get port opposite port ID if linked item
	 * ID store in contact_id
	 *@param $ID networking port ID
	 *
	 *@return boolean item found
	 **/
	function getContact($ID) {

		$wire = new Netwire;
		if ($this->contact_id = $wire->getOppositeContact($ID)){
			return true;
		}else{
			return false;
		}
	}

	function defineTabs($ID,$withtemplate) {
		global $LANG, $CFG_GLPI;

		$ong[1] = $LANG["title"][26];

		return $ong;
	}
}

/// Netwire class
class Netwire {

	/// ID of the netwire
	var $ID		= 0;
	/// first connected port ID
	var $end1	= 0;
	/// second connected port ID
	var $end2	= 0;

	/**
	 * Get port opposite port ID 
	 * 
	 *@param $ID networking port ID
	 *
	 *@return integer ID of opposite port. false if not found
	 **/
	function getOppositeContact ($ID){
		global $DB;
		$query = "SELECT * FROM glpi_networking_wire WHERE end1 = '$ID' OR end2 = '$ID'";
		if ($result=$DB->query($query))
		{
			$data = $DB->fetch_array($result);
			if (is_array($data)){
				$this->end1 = $data["end1"];
				$this->end2 = $data["end2"];
			}

			if ($this->end1 == $ID){
				return $this->end2;
			} else if ($this->end2 == $ID){
				return $this->end1;
			} else {
				return false;
			}
		}
	}
}

?>