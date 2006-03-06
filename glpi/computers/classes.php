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

// Based on:
// IRMA, Information Resource-Management and Administration
// Christian Bauer 
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
// CLASSES Computers


class Computer {

	var $fields	= array();
	var $updates	= array();
	//format $device = array(ID,"ID type periph","ID dans la table device","valeur de specificity")
	var $devices	= array();
	
	function getfromDB ($ID,$load_device=0) {

		global $db;

		// Make new database object and fill variables
		
		$query = "SELECT * FROM glpi_computers WHERE (ID = '$ID') limit 0,1";
//		echo $query;
		if ($result = $db->query($query)) {
			if ($db->numrows($result)==1) {
				$data = $db->fetch_array($result);
				foreach ($data as $key => $val) {
					$this->fields[$key] = $val;
				}
				if ($load_device){
					$query = "SELECT ID, device_type, FK_device, specificity FROM glpi_computer_device WHERE FK_computers = '$ID' ORDER BY device_type, ID";
					if ($result = $db->query($query)) {
						if ($db->numrows($result)>0) {
							$i = 0;
							while($data = $db->fetch_array($result)) {
								$this->devices[$i] = array("compDevID"=>$data["ID"],"devType"=>$data["device_type"],"devID"=>$data["FK_device"],"specificity"=>$data["specificity"]);
								$i++;
							}
					}
				}
			}
			return true;
			}
			else return false;
		}
		else return false;
		return true;
	}
	
	function getEmpty() {
	//make an empty database object
		global $db;
		$fields = $db->list_fields("glpi_computers");
		$columns = $db->num_fields($fields);
		for ($i = 0; $i < $columns; $i++) {
			$name = $db->field_name($fields, $i);
			$this->fields[$name] = "";
		}
		return true;
	}

	function updateInDB($updates)  {
		global $db,$lang;
		

		for ($i=0; $i < count($updates); $i++) {
			$query  = "UPDATE glpi_computers SET ";
			$query .= $updates[$i];
			$query .= "='";
			$query .= $this->fields[$updates[$i]];
			$query .= "' WHERE ID='";
			$query .= $this->fields["ID"];	
			$query .= "'";
			$result=$db->query($query);
			

		
		// Mise a jour du contact des éléments rattachés
		if ($updates[$i]=="contact" ||$updates[$i]=="contact_num"){
			$items=array(PRINTER_TYPE,MONITOR_TYPE,PERIPHERAL_TYPE,PHONE_TYPE);
			$ci=new CommonItem();
			$update_done=false;
			$updates3[0]="contact";
			$updates3[1]="contact_num";
			
			foreach ($items as $type){
				$query = "SELECT * from glpi_connect_wire WHERE end2='".$this->fields["ID"]."' AND type='".$type."'";
				if ($result=$db->query($query)) {
					$resultnum = $db->numrows($result);
					if ($resultnum>0) {
						for ($j=0; $j < $resultnum; $j++) {
							$tID = $db->result($result, $j, "end1");
							$ci->getfromDB($type,$tID);
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
		if ($update_done) $_SESSION["MESSAGE_AFTER_REDIRECT"]=$lang["computers"][49];
		
		}
		
		// Mise a jour du lieu des éléments rattachés
		if ($updates[$i]=="location" && $this->fields[$updates[$i]]!=0){
			$items=array(PRINTER_TYPE,MONITOR_TYPE,PERIPHERAL_TYPE,PHONE_TYPE);
			$ci=new CommonItem();
			$update_done=false;
			$updates2[0]="location";
			
			foreach ($items as $type){
				$query = "SELECT * from glpi_connect_wire WHERE end2='".$this->fields["ID"]."' AND type='".$type."'";
				
				if ($result=$db->query($query)) {
					$resultnum = $db->numrows($result);
					
					if ($resultnum>0) {
						for ($j=0; $j < $resultnum; $j++) {
							$tID = $db->result($result, $j, "end1");

							$ci->getfromDB($type,$tID);
							if (!$ci->obj->fields['is_global']){
								$ci->obj->fields['location']=$this->fields['location'];
								$ci->obj->updateInDB($updates2);
								$update_done=true;
							}
						}
					}
				}
			}
		if ($update_done) $_SESSION["MESSAGE_AFTER_REDIRECT"]=$lang["computers"][48];

		}
	
		}
		
		
	}
	
	function addToDB() {
		
		global $db;

		// Build query
		$query = "INSERT INTO glpi_computers (";
		$i=0;
		foreach ($this->fields as $key => $val) {
				$fields[$i] = $key;
				$values[$i] = $val;
				$i++;
		}		
		for ($i=0; $i < count($fields); $i++) {
			$query .= $fields[$i];
			if ($i!=count($fields)-1) {
				$query .= ",";
			}
		}
		$query .= ") VALUES (";
		for ($i=0; $i < count($values); $i++) {
			$query .= "'".$values[$i]."'";
			if ($i!=count($values)-1) {
				$query .= ",";
			}
		}
		$query .= ")";

		$result=$db->query($query);
		return $db->insert_id();
	}

	function restoreInDB($ID) {
		global $db;
		$query = "UPDATE glpi_computers SET deleted='N' WHERE (ID = '$ID')";
		if ($result = $db->query($query)) {
			return true;
		} else {
			return false;
		}
	}

	function isUsed($ID){
	return true;
	global $db;
	$query="SELECT * from glpi_connect_wire where end2 = '$ID'";
	$result = $db->query($query);
	if ($db->numrows($result)>0) return true;
	
	$query="SELECT * from glpi_tracking where computer = '$ID' AND device_type='".COMPUTER_TYPE."'";
	$result = $db->query($query);
	if ($db->numrows($result)>0) return true;
	
	$query="SELECT * from glpi_networking_ports where on_device = '$ID' AND device_type='".COMPUTER_TYPE."'";
	$result = $db->query($query);
	if ($db->numrows($result)==0) return false;
	else {
		while ($data=$db->fetch_array($result)){
			$query2="SELECT * from glpi_networking_wire where end1 = '".$data['ID']."' OR end2='".$data['ID']."'";
			$result2 = $db->query($query2);
			if ($db->numrows($result2)>0) return true;
		}
		return false;
	}
	}


	function deleteFromDB($ID,$force=0) {
		global $db;

		$job=new Job;
		if ($force==1||!$this->isUsed($ID)){

			$query = "DELETE from glpi_computers WHERE ID = '$ID'";
			if ($result = $db->query($query)) {
				$query = "SELECT * FROM glpi_tracking WHERE (computer = '$ID'  AND device_type='".COMPUTER_TYPE."')";
				$result = $db->query($query);
				$number = $db->numrows($result);
				$i=0;
				while ($i < $number) {
 		  		$job->deleteinDB($db->result($result,$i,"ID"));
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

				return true;
			} else {
				return false;
			}
		} else {
		$query = "UPDATE glpi_computers SET deleted='Y' WHERE ID = '$ID'";		
		return ($result = $db->query($query));
		}
	}
}


?>
