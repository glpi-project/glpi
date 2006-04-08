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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
// CLASSES Computers


class Computer extends CommonDBTM {


	//format $device = array(ID,"ID type periph","ID dans la table device","valeur de specificity")
	var $devices	= array();

	function Computer () {
		$this->table="glpi_computers";
		$this->type=COMPUTER_TYPE;
		$this->dohistory=true;
	}

	function defineOnglets($withtemplate){
		global $lang,$cfg_glpi;
		$ong= array(	1 => $lang["title"][26],
				2 => $lang["title"][12],
				3 => $lang["title"][27],
				4 => $lang["Menu"][26],
				5 => $lang["title"][25],
			);

		if(empty($withtemplate)){
			$ong[6]=$lang["title"][28];
			$ong[7]=$lang["title"][34];
			$ong[10]=$lang["title"][37];
			$ong[12]=$lang["title"][38];

			if ($cfg_glpi["ocs_mode"])
				$ong[13]=$lang["Menu"][33];
		}	
		return $ong;
	}
	
	function getFromDBwithDevices ($ID) {

		global $db;

		if ($this->getFromDB($ID)){
			$query = "SELECT ID, device_type, FK_device, specificity FROM glpi_computer_device WHERE FK_computers = '$ID' ORDER BY device_type, ID";
			if ($result = $db->query($query)) {
				if ($db->numrows($result)>0) {
					$i = 0;
					while($data = $db->fetch_array($result)) {
						$this->devices[$i] = array("compDevID"=>$data["ID"],"devType"=>$data["device_type"],"devID"=>$data["FK_device"],"specificity"=>$data["specificity"]);
						$i++;
					}
				}
			return true;
			} 
		}
		return false;
	}

	function prepareInputForUpdate($input) {
		// set new date.
		$input["date_mod"] = date("Y-m-d H:i:s");
	
		return $input;
	}

	function post_updateItem($input,$updates,$history=1) {
		// Manage changes for OCS if more than 1 element (date_mod)
		if ($this->fields["ocs_import"]&&$history==1&&count($updates)>1){
			mergeOcsArray($this->fields["ID"],$updates,"computer_update");
		}

		if(isset($input["state"])){
			if (isset($input["is_template"])&&$input["is_template"]==1){
				updateState(COMPUTER_TYPE,$input["ID"],$input["state"],1);
			}else {
				updateState(COMPUTER_TYPE,$input["ID"],$input["state"]);
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
		// Add state
		if ($input["_state"]>0){
			if (isset($input["is_template"])&&$input["is_template"]==1)
				updateState(COMPUTER_TYPE,$newID,$input["_state"],1);
			else updateState(COMPUTER_TYPE,$newID,$input["_state"]);
		}
	
		// ADD Devices
		$this->getFromDBwithDevices($oldID);
		foreach($this->devices as $key => $val) {
				compdevice_add($newID,$val["devType"],$val["devID"],$val["specificity"],0);
			}
	
		// ADD Infocoms
		$ic= new Infocom();
		if ($ic->getFromDBforDevice(COMPUTER_TYPE,$oldID)){
			$ic->fields["FK_device"]=$newID;
			unset ($ic->fields["ID"]);
			$ic->addToDB();
		}
	
		// ADD software
		$query="SELECT license from glpi_inst_software WHERE cID='$oldID'";
		$result=$db->query($query);
		if ($db->numrows($result)>0){
			while ($data=$db->fetch_array($result))
				installSoftware($newID,$data['license']);
		}
	
		// ADD Contract				
		$query="SELECT FK_contract from glpi_contract_device WHERE FK_device='$oldID' AND device_type='".COMPUTER_TYPE."';";
		$result=$db->query($query);
		if ($db->numrows($result)>0){
			while ($data=$db->fetch_array($result))
				addDeviceContract($data["FK_contract"],COMPUTER_TYPE,$newID);
		}

		// ADD Documents			
		$query="SELECT FK_doc from glpi_doc_device WHERE FK_device='$oldID' AND device_type='".COMPUTER_TYPE."';";
		$result=$db->query($query);
		if ($db->numrows($result)>0){
			while ($data=$db->fetch_array($result))
				addDeviceDocument($data["FK_doc"],COMPUTER_TYPE,$newID);
		}
	
		// ADD Ports
		$query="SELECT ID from glpi_networking_ports WHERE on_device='$oldID' AND device_type='".COMPUTER_TYPE."';";
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
	}

	function post_updateInDB($updates)  {
		global $db,$lang;
		

		for ($i=0; $i < count($updates); $i++) {
		
		// Mise a jour du contact des éléments rattachés
		if ($updates[$i]=="contact" ||$updates[$i]=="contact_num"){
			$items=array(PRINTER_TYPE,MONITOR_TYPE,PERIPHERAL_TYPE,PHONE_TYPE);
			$ci=new CommonItem();
			$update_done=false;
			$updates3[0]="contact";
			$updates3[1]="contact_num";
			
			foreach ($items as $t){
				$query = "SELECT * from glpi_connect_wire WHERE end2='".$this->fields["ID"]."' AND type='".$t."'";
				if ($result=$db->query($query)) {
					$resultnum = $db->numrows($result);
					if ($resultnum>0) {
						for ($j=0; $j < $resultnum; $j++) {
							$tID = $db->result($result, $j, "end1");
							$ci->getfromDB($t,$tID);
							if (!$ci->obj->fields['is_global']){
								$ci->obj->fields['contact']=$this->fields['contact'];
								$ci->obj->fields['contact_num']=$this->fields['contact_num'];
								$ci->obj->updateInDB($updates3);
								$update_done=true;
							}
						}
					}
				}
			}

		if ($update_done) {
			if (!empty($_SESSION["MESSAGE_AFTER_REDIRECT"])) $_SESSION["MESSAGE_AFTER_REDIRECT"].="<br>";
			$_SESSION["MESSAGE_AFTER_REDIRECT"].=$lang["computers"][49];
		}
		
		}
		
		// Mise a jour du lieu des éléments rattachés
		if ($updates[$i]=="location" && $this->fields[$updates[$i]]!=0){
			$items=array(PRINTER_TYPE,MONITOR_TYPE,PERIPHERAL_TYPE,PHONE_TYPE);
			$ci=new CommonItem();
			$update_done=false;
			$updates2[0]="location";
			
			foreach ($items as $t){
				$query = "SELECT * from glpi_connect_wire WHERE end2='".$this->fields["ID"]."' AND type='".$t."'";
				
				if ($result=$db->query($query)) {
					$resultnum = $db->numrows($result);
					
					if ($resultnum>0) {
						for ($j=0; $j < $resultnum; $j++) {
							$tID = $db->result($result, $j, "end1");

							$ci->getfromDB($t,$tID);
							if (!$ci->obj->fields['is_global']){
								$ci->obj->fields['location']=$this->fields['location'];
								$ci->obj->updateInDB($updates2);
								$update_done=true;
							}
						}
					}
				}
			}
		if ($update_done) {
			if (!empty($_SESSION["MESSAGE_AFTER_REDIRECT"])) $_SESSION["MESSAGE_AFTER_REDIRECT"].="<br>";
			$_SESSION["MESSAGE_AFTER_REDIRECT"].=$lang["computers"][48];
		}

		}
	
		}
		
		
	}
	

	function cleanDBonPurge($ID) {
		global $db;

		$job=new Job;

		$query = "SELECT * FROM glpi_tracking WHERE (computer = '$ID'  AND device_type='".COMPUTER_TYPE."')";
		$result = $db->query($query);
		$number = $db->numrows($result);
		$i=0;
		while ($i < $number) {
	  		$job->deleteFromDB($db->result($result,$i,"ID"));
			$i++;
		}

		$query = "DELETE FROM glpi_inst_software WHERE (cID = '$ID')";
		$result = $db->query($query);		

		$query = "DELETE FROM glpi_contract_device WHERE (FK_device = '$ID' AND device_type='".COMPUTER_TYPE."')";
		$result = $db->query($query);

		$query = "DELETE FROM glpi_state_item WHERE (id_device = '$ID' AND device_type='".COMPUTER_TYPE."')";
		$result = $db->query($query);

		$query = "DELETE FROM glpi_infocoms WHERE (FK_device = '$ID' AND device_type='".COMPUTER_TYPE."')";
		$result = $db->query($query);

		$query = "SELECT ID FROM glpi_networking_ports WHERE (on_device = '$ID' AND device_type = '".COMPUTER_TYPE."')";
		$result = $db->query($query);
		while ($data = $db->fetch_array($result)){
			$q = "DELETE FROM glpi_networking_wire WHERE (end1 = '".$data["ID"]."' OR end2 = '".$data["ID"]."')";
			$result2 = $db->query($q);					
		}	

		$query = "DELETE FROM glpi_networking_ports WHERE (on_device = '$ID' AND device_type = '".COMPUTER_TYPE."')";
		$result = $db->query($query);
		$query = "DELETE FROM glpi_connect_wire WHERE (end2 = '$ID')";
		$result = $db->query($query);
				
		$query="select * from glpi_reservation_item where (device_type='".COMPUTER_TYPE."' and id_device='$ID')";
		if ($result = $db->query($query)) {
			if ($db->numrows($result)>0) {
				deleteReservationItem(array("ID"=>$db->result($result,0,"ID")));
			}
		}

		$query = "DELETE FROM glpi_computer_device WHERE (FK_computers = '$ID')";
		$result = $db->query($query);

		$query = "DELETE FROM glpi_ocs_link WHERE (glpi_id = '$ID')";
		$result = $db->query($query);
	}
}


?>
