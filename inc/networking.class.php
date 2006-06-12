<?php
/*
* @version $Id$
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.
 
 http://indepnet.net/   http://glpi-project.org
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
 

 
// CLASSES Networking


class Netdevice extends CommonDBTM {

	function Netdevice () {
		$this->table="glpi_networking";
		$this->type=NETWORKING_TYPE;
		$this->dohistory=true;
	}

	
	function defineOnglets($withtemplate){
		global $lang;
		
		$ong[1]=$lang["title"][26];
		if (haveRight("contract_infocom","r"))
			$ong[4]=$lang["Menu"][26];
		if (haveRight("document","r"))
			$ong[5]=$lang["title"][25];

		if(empty($withtemplate)){
			if (haveRight("show_ticket","1"))
				$ong[6]=$lang["title"][28];
			if (haveRight("link","r"))
				$ong[7]=$lang["title"][34];
			if (haveRight("notes","r"))
				$ong[10]=$lang["title"][37];

			$ong[12]=$lang["title"][38];
		}	
		return $ong;
	}

	function prepareInputForUpdate($input) {
		// set new date.
		$input["date_mod"] = date("Y-m-d H:i:s");
	
		return $input;
	}

	function post_updateItem($input,$updates,$history=1) {

		if(isset($input["state"])){
			if (isset($input["is_template"])&&$input["is_template"]==1){
				updateState(NETWORKING_TYPE,$input["ID"],$input["state"],1,0);
			}else {
				updateState(NETWORKING_TYPE,$input["ID"],$input["state"],0,$history);
			}
		}
	}


	function prepareInputForAdd($input) {
		// set new date.
		$input["date_mod"] = date("Y-m-d H:i:s");
 
		// dump status
		$input["_oldID"]=$input["ID"];
		unset($input['withtemplate']);
		unset($input['ID']);
	
		// Manage state
		$input["_state"]=-1;
		if (isset($input["state"])){
			$input["_state"]=$input["state"];
			unset($input["state"]);
		}

		return $input;
	}

	function postAddItem($newID,$input) {
		global $db;
		// Add state
		if ($input["_state"]>0){
			if (isset($input["is_template"])&&$input["is_template"]==1)
				updateState(NETWORKING_TYPE,$newID,$input["_state"],1,0);
			else updateState(NETWORKING_TYPE,$newID,$input["_state"],0,0);
		}

		// ADD Infocoms
		$ic= new Infocom();
		if ($ic->getFromDBforDevice(NETWORKING_TYPE,$input["_oldID"])){
			$ic->fields["FK_device"]=$newID;
			unset ($ic->fields["ID"]);
			if (isset($ic->fields["num_immo"])) {
			    $ic->fields["num_immo"] = autoName($ic->fields["num_immo"], "num_immo", 1, INFOCOM_TYPE);
			}
			$ic->addToDB();
		}
	
		// ADD Ports
		$query="SELECT ID from glpi_networking_ports WHERE on_device='".$input["_oldID"]."' AND device_type='".NETWORKING_TYPE."';";
		$result=$db->query($query);
		if ($db->numrows($result)>0){
		
			while ($data=$db->fetch_array($result)){
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
		$query="SELECT FK_contract from glpi_contract_device WHERE FK_device='".$input["_oldID"]."' AND device_type='".NETWORKING_TYPE."';";
		$result=$db->query($query);
		if ($db->numrows($result)>0){
		
			while ($data=$db->fetch_array($result))
				addDeviceContract($data["FK_contract"],NETWORKING_TYPE,$newID);
		}
	
		// ADD Documents			
		$query="SELECT FK_doc from glpi_doc_device WHERE FK_device='".$input["_oldID"]."' AND device_type='".NETWORKING_TYPE."';";
		$result=$db->query($query);
		if ($db->numrows($result)>0){
		
			while ($data=$db->fetch_array($result))
				addDeviceDocument($data["FK_doc"],NETWORKING_TYPE,$newID);
		}

	}

	function pre_deleteItem($ID) {
		removeConnector($ID);	
	}


	function cleanDBonPurge($ID) {
		global $db;

		$query = "SELECT ID FROM glpi_networking_ports WHERE (on_device = '$ID' AND device_type = '".NETWORKING_TYPE."')";
		$result = $db->query($query);
		while ($data = $db->fetch_array($result)){
			$q = "DELETE FROM glpi_networking_wire WHERE (end1 = '".$data["ID"]."' OR end2 = '".$data["ID"]."')";
			$result2 = $db->query($q);				
		}

		$job=new Job;

		$query = "SELECT * FROM glpi_tracking WHERE (computer = '$ID'  AND device_type='".NETWORKING_TYPE."')";
		$result = $db->query($query);
		$number = $db->numrows($result);
		$i=0;
		while ($i < $number) {
			$job->deleteFromDB($db->result($result,$i,"ID"));
			$i++;
		}
			
		$query = "DELETE FROM glpi_networking_ports WHERE (on_device = '$ID' AND device_type = '".NETWORKING_TYPE."')";
		$result = $db->query($query);

		$query = "DELETE FROM glpi_state_item WHERE (id_device = '$ID' AND device_type='".NETWORKING_TYPE."')";
		$result = $db->query($query);
			
		$query = "DELETE FROM glpi_infocoms WHERE (FK_device = '$ID' AND device_type='".NETWORKING_TYPE."')";
		$result = $db->query($query);

		$query = "DELETE FROM glpi_contract_device WHERE (FK_device = '$ID' AND device_type='".NETWORKING_TYPE."')";
		$result = $db->query($query);
				
		$query="select * from glpi_reservation_item where (device_type='".NETWORKING_TYPE."' and id_device='$ID')";
		if ($result = $db->query($query)) {
			if ($db->numrows($result)>0) {
				deleteReservationItem(array("ID"=>$db->result($result,0,"ID")));
			}
		}
	}

	function title() {
		// titre
		
		global  $lang,$HTMLRel;
	
		echo "<div align='center'><table border='0'><tr><td>";
		echo "<img src=\"".$HTMLRel."pics/networking.png\" alt='".$lang["networking"][11]."' title='".$lang["networking"][11]."'></td>";
		if (haveRight("networking","w")){
			echo "<td><a  class='icon_consol' href=\"".$HTMLRel."front/setup.templates.php?type=".NETWORKING_TYPE."&amp;add=1\"><b>".$lang["networking"][11]."</b></a>";
			echo "</td>";
			echo "<td><a class='icon_consol' href='".$HTMLRel."front/setup.templates.php?type=".NETWORKING_TYPE."&amp;add=0'>".$lang["common"][8]."</a></td>";
		} else echo "<td><span class='icon_sous_nav'><b>".$lang["Menu"][1]."</b></span></td>";
		echo "</tr></table></div>";
	
	}
	
	
	
	function showForm ($target,$ID,$withtemplate='') {
		// Show device or blank form
		
		global $cfg_glpi, $lang,$HTMLRel;
	
		if (!haveRight("networking","r")) return false;
	
		$spotted = false;
	
		if(empty($ID) && $withtemplate == 1) {
			if($this->getEmpty()) $spotted = true;
		} else {
			if($this->getfromDB($ID)) $spotted = true;
		}
	
		if($spotted) {
			if(!empty($withtemplate) && $withtemplate == 2) {
				$template = "newcomp";
				$datestring = $lang["computers"][14].": ";
				$date = convDateTime(date("Y-m-d H:i:s"));
			} elseif(!empty($withtemplate) && $withtemplate == 1) { 
				$template = "newtemplate";
				$datestring = $lang["computers"][14].": ";
				$date = convDateTime(date("Y-m-d H:i:s"));
			} else {
				$datestring = $lang["common"][26].": ";
				$date = convDateTime($this->fields["date_mod"]);
				$template = false;
			}
	
	
		echo "<div align='center'><form name='form' method='post' action=\"$target\">\n";
	
			if(strcmp($template,"newtemplate") === 0) {
				echo "<input type=\"hidden\" name=\"is_template\" value=\"1\" />\n";
			}
	
		echo "<table  class='tab_cadre_fixe' cellpadding='2'>\n";
	
			echo "<tr><th align='center' >\n";
			if(!$template) {
				echo $lang["networking"][54].": ".$this->fields["ID"];
			}elseif (strcmp($template,"newcomp") === 0) {
				echo $lang["networking"][53].": ".$this->fields["tplname"];
				echo "<input type='hidden' name='tplname' value='".$this->fields["tplname"]."'>";
			}elseif (strcmp($template,"newtemplate") === 0) {
				echo $lang["common"][6].": ";
				autocompletionTextField("tplname","glpi_networking","tplname",$this->fields["tplname"],20);	
			}
			echo "</th><th  align='center'>".$datestring.$date;
			if (!$template&&!empty($this->fields['tplname']))
				echo "&nbsp;&nbsp;&nbsp;(".$lang["common"][13].": ".$this->fields['tplname'].")";
			echo "</th></tr>\n";
	
		
		echo "<tr><td class='tab_bg_1' valign='top'>\n";
	
		echo "<table cellpadding='1' cellspacing='0' border='0'>\n";
	
		echo "<tr><td>".$lang["common"][16]."*:	</td>\n";
		echo "<td>";
		$objectName = autoName($this->fields["name"], "name", ($template === "newcomp"), NETWORKING_TYPE);
		autocompletionTextField("name","glpi_networking","name",$objectName,20);
		echo "</td></tr>\n";

		echo "<tr class='tab_bg_1'><td>".$lang["common"][5].": 	</td><td colspan='2'>\n";
			dropdownValue("glpi_enterprises","FK_glpi_enterprise",$this->fields["FK_glpi_enterprise"]);
		echo "</td></tr>\n";
	
		echo "<tr><td>".$lang["common"][15].": 	</td><td>\n";
			dropdownValue("glpi_dropdown_locations", "location", $this->fields["location"]);
		echo "</td></tr>\n";
		
		echo "<tr class='tab_bg_1'><td>".$lang["common"][10].": 	</td><td colspan='2'>\n";
			dropdownUsersID("tech_num", $this->fields["tech_num"],"interface");
		echo "</td></tr>\n";
			
		echo "<tr><td>".$lang["common"][21].":	</td><td>\n";
			autocompletionTextField("contact_num","glpi_networking","contact_num",$this->fields["contact_num"],20);	
		echo "</td></tr>\n";
	
		echo "<tr><td>".$lang["common"][18].":	</td><td>\n";
			autocompletionTextField("contact","glpi_networking","contact",$this->fields["contact"],20);	
		echo "</td></tr>\n";

		echo "<tr><td>".$lang["common"][34].": 	</td><td>";
			dropdownAllUsers("FK_users", $this->fields["FK_users"]);
		echo "</td></tr>";

		echo "<tr><td>".$lang["common"][35].": 	</td><td>";
			dropdownValue("glpi_groups", "FK_groups", $this->fields["FK_groups"]);
		echo "</td></tr>";
		
		if (!$template){
		echo "<tr><td>".$lang["reservation"][24].":</td><td><b>";
		showReservationForm(NETWORKING_TYPE,$ID);
		echo "</b></td></tr>";
		}
	
			
			echo "<tr><td>".$lang["state"][0].":</td><td>\n";
			$si=new StateItem();
			$t=0;
			if ($template) $t=1;
			$si->getfromDB(NETWORKING_TYPE,$this->fields["ID"],$t);
			dropdownValue("glpi_dropdown_state", "state",$si->fields["state"]);
			echo "</td></tr>\n";
			
	
		echo "</table>\n";
	
		echo "</td>\n";	
		echo "<td class='tab_bg_1' valign='top'>\n";
	
		echo "<table cellpadding='1' cellspacing='0' border='0'>\n";
	
		echo "<tr><td>".$lang["common"][17].": 	</td><td>\n";
			dropdownValue("glpi_type_networking", "type", $this->fields["type"]);
		echo "</td></tr>\n";
	
		echo "<tr><td>".$lang["common"][22].": 	</td><td>";
			dropdownValue("glpi_dropdown_model_networking", "model", $this->fields["model"]);
		echo "</td></tr>";
		
		echo "<tr><td>".$lang["networking"][49].": 	</td><td>\n";
		dropdownValue("glpi_dropdown_firmware", "firmware", $this->fields["firmware"]);
		echo "</td></tr>\n";
			
		echo "<tr><td>".$lang["networking"][5].":	</td><td>\n";
		autocompletionTextField("ram","glpi_networking","ram",$this->fields["ram"],20);	
		echo "</td></tr>\n";
	
		echo "<tr><td>".$lang["common"][19].":	</td><td>\n";
		autocompletionTextField("serial","glpi_networking","serial",$this->fields["serial"],20);	
		echo "</td></tr>\n";
	
		echo "<tr><td>".$lang["common"][20]."*:</td><td>\n";
		$objectName = autoName($this->fields["otherserial"], "otherserial", ($template === "newcomp"), NETWORKING_TYPE);
		autocompletionTextField("otherserial","glpi_networking","otherserial",$objectName,20);
		//autocompletionTextField("otherserial","glpi_networking","otherserial",$this->fields["otherserial"],20);	
		echo "</td></tr>\n";

		echo "<tr><td>".$lang["setup"][88].": 	</td><td>\n";
			dropdownValue("glpi_dropdown_network", "network", $this->fields["network"]);
		echo "</td></tr>\n";
	
		echo "<tr><td>".$lang["setup"][89].": 	</td><td>\n";
			dropdownValue("glpi_dropdown_domain", "domain", $this->fields["domain"]);
		echo "</td></tr>\n";
		
		echo "<tr><td>".$lang["networking"][14].":</td><td>\n";
		autocompletionTextField("ifaddr","glpi_networking","ifaddr",$this->fields["ifaddr"],20);	
		echo "</td></tr>\n";
	
		echo "<tr><td>".$lang["networking"][15].":</td><td>\n";
		autocompletionTextField("ifmac","glpi_networking","ifmac",$this->fields["ifmac"],20);	
		echo "</td></tr>\n";
			
		echo "</table>\n";
		
		echo "</td>\n";	
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td class='tab_bg_1' valign='top' colspan='2'>\n";
	
		echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'><tr><td valign='top'>\n";
		echo $lang["common"][25].":	</td>\n";
		echo "<td align='center'><textarea cols='35' rows='4' name='comments' >".$this->fields["comments"]."</textarea>\n";
		echo "</td></tr></table>\n";
	
		echo "</td>";
		echo "</tr>\n";
	
	
		if (haveRight("networking","w")) {
			echo "<tr>\n";
		
			if ($template) {
	
				if (empty($ID)||$withtemplate==2){
				echo "<td class='tab_bg_2' align='center' colspan='2'>\n";
				echo "<input type='hidden' name='ID' value=$ID>";
				echo "<input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'>";
				echo "</td>\n";
				} else {
				echo "<td class='tab_bg_2' align='center' colspan='2'>\n";
				echo "<input type='hidden' name='ID' value=$ID>";
				echo "<input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'>";
				echo "</td>\n";
				}
	
			} else {
	
				echo "<td class='tab_bg_2' valign='top'>";
				echo "<input type='hidden' name='ID' value=\"$ID\">\n";
				echo "<div align='center'><input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'></div>";
				echo "<td class='tab_bg_2' valign='top'>\n";
	
				echo "<div align='center'>\n";
				if ($this->fields["deleted"]=='N')
					echo "<input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'>\n";
				else {
					echo "<input type='submit' name='restore' value=\"".$lang["buttons"][21]."\" class='submit'>\n";
			
					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' name='purge' value=\"".$lang["buttons"][22]."\" class='submit'>\n";
				}
				echo "</div>\n";
				echo "</td>\n";
			}
			echo "</tr>\n";
		}
		
		echo "</table></form></div>\n";
	
		return true;
			}
		else {
			echo "<div align='center'><b>".$lang["networking"][38]."</b></div>";
			return false;
		}
	
	}

}


class Netport extends CommonDBTM {


	var $contact_id		= 0;
	
	var $device_name	= "";
	var $device_ID		= 0;
	var $device_type		= 0;

	function Netport () {
		$this->table="glpi_networking_ports";
		$this->type=-1;
	}

	function post_updateItem($input,$updates,$history){
		$tomatch=array("netpoint","ifaddr","ifmac");
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
		if (strlen($input["logical_number"])==0) unset($input["logical_number"]);
		//unset($input['search']);
		return $input;
	}

	function cleanDBonPurge($ID) {
		global $db;

		$query = "DELETE FROM glpi_networking_wire WHERE (end1 = '$ID' OR end2 = '$ID')";
		$result = $db->query($query);
	}

	// SPECIFIC FUNCTIONS

	function getDeviceData($ID,$type)
	{
		global $db,$LINK_ID_TABLE;
		
		$table = $LINK_ID_TABLE[$type];
		
		$query = "SELECT * FROM $table WHERE (ID = '$ID')";
		if ($result=$db->query($query))
		{
			$data = $db->fetch_array($result);
			$this->device_name = $data["name"];
			$this->deleted = $data["deleted"];
			$this->device_ID = $ID;
			$this->device_type = $type;
			return true;
		}
		else 
		{
			return false;
		}
	}

	function getContact($ID) 
	{
	
		$wire = new Netwire;
		if ($this->contact_id = $wire->getOppositeContact($ID))
		{
			return true;
		}
		else
		{
			return false;
		}
		
	}

	
}


class Netwire {

	var $ID		= 0;
	var $end1	= 0;
	var $end2	= 0;

	function getOppositeContact ($ID)
	{
		global $db;
		$query = "SELECT * FROM glpi_networking_wire WHERE (end1 = '$ID' OR end2 = '$ID')";
		if ($result=$db->query($query))
		{
			$data = $db->fetch_array($result);
			if (is_array($data)){
			 $this->end1 = $data["end1"];
			 $this->end2 = $data["end2"];
			 }

			if ($this->end1 == $ID)
			{
				return $this->end2;
			} 
			else if ($this->end2 == $ID)
			{
				return $this->end1;
			} 
			else 
			{
				return false;
			}
		}
	}
}
?>
