<?php
/*
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2005 by the INDEPNET Development Team.
 
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
	//format $device = array(ID,"nom de table periph","ID dans la table device","valeur de specificity")
	var $devices	= array();
	
	function getfromDB ($ID) {

		$table = "glpi_computers";
		
		// Make new database object and fill variables
		$db = new DB;
		$query = "SELECT * FROM $table WHERE (ID = '$ID') limit 0,1";
		//echo $query;
		if ($result = $db->query($query)) {
			if ($db->numrows($result)==1) {
				$data = $db->fetch_array($result);
				foreach ($data as $key => $val) {
					$this->fields[$key] = $val;
				}
				$query = "SELECT ID, device_type, FK_device, specificity FROM glpi_computer_device WHERE (FK_computers = '$ID') ORDER BY device_type";
				if ($result = $db->query($query)) {
					if ($db->numrows($result)>0) {
						$i = 0;
						while($data = $db->fetch_array($result)) {
							$this->devices[$i] = array("compDevID"=>$data["ID"],"devType"=>$data["device_type"],"devID"=>$data["FK_device"],"specificity"=>$data["specificity"]);
							$i++;
						}
						return true;
					}
					
				} 
				
			}
			else return false;
		}
		else return false;
		return true;
	}
	
	function getInsertElementID(){
		$db = new DB;

		// Build query
		$query = "SELECT ID FROM glpi_computers WHERE ";
		$i=0;
		foreach ($this->fields as $key => $val) {
			if(!(strcmp($key,'ID') === 0)) { 
				$fields[$i] = $key;
				$values[$i] = $val;
				$i++;
			}
		}		
		for ($i=0; $i < count($fields); $i++) {
			
			$query .= $fields[$i];
			$query .= " = '".$values[$i]."' ";
			if ($i!=count($fields)-1) $query.=" AND ";
		}
		
		$result=$db->query($query);
		
		if ($db->numrows($result)==1)
		return $db->result($result,0,"ID");
		else return 0;
	
	}
	
	function getEmpty() {
	//make an empty database object
		$db = new DB;
		$fields = $db->list_fields("glpi_computers");
		$columns = mysql_num_fields($fields);
		for ($i = 0; $i < $columns; $i++) {
			$name = mysql_field_name($fields, $i);
			$this->fields[$name] = "";
		}
		return true;
	}

	function updateInDB($updates)  {
		global $lang;
		$db = new DB;

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
			$_SESSION["MESSAGE_AFTER_REDIRECT"]=$lang["computers"][49];
			$updates3[0]="contact";
			$updates3[1]="contact_num";
			//printers
			$query = "SELECT * from glpi_connect_wire WHERE end2='".$this->fields["ID"]."' AND type='".PRINTER_TYPE."'";
			if ($result=$db->query($query)) {
				$resultnum = $db->numrows($result);
				if ($resultnum>0) {
				for ($i=0; $i < $resultnum; $i++) {
					$tID = $db->result($result, $i, "end1");
					$printer = new Printer;
					$printer->getfromDB($tID);
					$printer->fields['contact']=$this->fields['contact'];
					$printer->fields['contact_num']=$this->fields['contact_num'];
					$printer->updateInDB($updates3);
					}
				}
			}
			//monitors
			$query = "SELECT * from glpi_connect_wire WHERE end2='".$this->fields["ID"]."' AND type='".MONITOR_TYPE."'";
			if ($result=$db->query($query)) {
				$resultnum = $db->numrows($result);
				if ($resultnum>0) {
				for ($i=0; $i < $resultnum; $i++) {
					$tID = $db->result($result, $i, "end1");
					$monitor = new Monitor;
					$monitor->getfromDB($tID);
					$monitor->fields['contact']=$this->fields['contact'];
					$monitor->fields['contact_num']=$this->fields['contact_num'];
					$monitor->updateInDB($updates3);
					}
				}
			}
			//Peripherals
			$query = "SELECT * from glpi_connect_wire WHERE end2='".$this->fields["ID"]."' AND type='".PERIPHERAL_TYPE."'";
			if ($result=$db->query($query)) {
				$resultnum = $db->numrows($result);
				if ($resultnum>0) {
				for ($i=0; $i < $resultnum; $i++) {
					$tID = $db->result($result, $i, "end1");
					$peri = new Peripheral;
					$peri->getfromDB($tID);
					$peri->fields['contact']=$this->fields['contact'];
					$peri->fields['contact_num']=$this->fields['contact_num'];
					$peri->updateInDB($updates3);
					}
				}
			}
		
		
		}
		
		// Mise a jour du lieu des éléments rattachés
		if ($updates[$i]=="location" && $this->fields[$updates[$i]]!=0){
			$_SESSION["MESSAGE_AFTER_REDIRECT"]=$lang["computers"][48];
			$updates2[0]="location";
			//printers
			$query = "SELECT * from glpi_connect_wire WHERE end2='".$this->fields["ID"]."' AND type='".PRINTER_TYPE."'";
			if ($result=$db->query($query)) {
				$resultnum = $db->numrows($result);
				if ($resultnum>0) {
				for ($i=0; $i < $resultnum; $i++) {
					$tID = $db->result($result, $i, "end1");
					$printer = new Printer;
					$printer->getfromDB($tID);
					$printer->fields['location']=$this->fields['location'];
					$printer->updateInDB($updates2);
					}
				}
			}
			//monitors
			$query = "SELECT * from glpi_connect_wire WHERE end2='".$this->fields["ID"]."' AND type='".MONITOR_TYPE."'";
			if ($result=$db->query($query)) {
				$resultnum = $db->numrows($result);
				if ($resultnum>0) {
				for ($i=0; $i < $resultnum; $i++) {
					$tID = $db->result($result, $i, "end1");
					$monitor = new Monitor;
					$monitor->getfromDB($tID);
					$monitor->fields['location']=$this->fields['location'];
					$monitor->updateInDB($updates2);
					}
				}
			}
			//Peripherals
			$query = "SELECT * from glpi_connect_wire WHERE end2='".$this->fields["ID"]."' AND type='".PERIPHERAL_TYPE."'";
			if ($result=$db->query($query)) {
				$resultnum = $db->numrows($result);
				if ($resultnum>0) {
				for ($i=0; $i < $resultnum; $i++) {
					$tID = $db->result($result, $i, "end1");
					$peri = new Peripheral;
					$peri->getfromDB($tID);
					$peri->fields['location']=$this->fields['location'];
					$peri->updateInDB($updates2);
					}
				}
			}
		}
		}
		
		
	}
	
	function addToDB() {
		
		$db = new DB;

		// Build query
		$query = "INSERT INTO glpi_computers (";
		$i=0;
		foreach ($this->fields as $key => $val) {
			if(!(strcmp($key,'withtemplate') === 0 || strcmp($key,'add') === 0)) { 
				$fields[$i] = $key;
				$values[$i] = $val;
				$i++;
			}
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

		if ($result=$db->query($query)) {
			return true;
		} else {
			return false;
		}
	}

	function restoreInDB($ID) {
		$db = new DB;
		$query = "UPDATE glpi_computers SET deleted='N' WHERE (ID = '$ID')";
		if ($result = $db->query($query)) {
			return true;
		} else {
			return false;
		}
	}

	function isUsed($ID){
	$db = new DB;		
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

		$table = "glpi_computers";

		$db = new DB;

		if ($force==1||!$this->isUsed($ID)){

			$query = "DELETE from $table WHERE ID = '$ID'";
			if ($result = $db->query($query)) {
				$query = "SELECT * FROM glpi_tracking WHERE (computer = '$ID')";
				$result = $db->query($query);
				$number = $db->numrows($result);
				$i=0;
				while ($i < $number) {
			  		$job = $db->result($result,$i,"ID");
			    		$query = "DELETE FROM glpi_followups WHERE (tracking = '$job')";
			      		$db->query($query);
					$i++;
				}
				$query = "DELETE FROM glpi_tracking WHERE (computer = '$ID' AND device_type='".COMPUTER_TYPE."')";
				$result = $db->query($query);
				$query = "DELETE FROM glpi_inst_software WHERE (cID = '$ID')";
				$result = $db->query($query);		

				$query = "DELETE FROM glpi_contract_device WHERE (FK_device = '$ID' AND device_type='".COMPUTER_TYPE."')";
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
				
				$query="select * from glpi_repair_item where (device_type='".COMPUTER_TYPE."' and id_device='$ID')";
				$result = $db->query($query);
				
				$query="select * from glpi_reservation_item where (device_type='".COMPUTER_TYPE."' and id_device='$ID')";
				if ($result = $db->query($query)) {
					if ($db->numrows($result)>0) {
						deleteReservationItem(array("ID"=>$db->result($result,0,"ID")));
					}
				}
				$query = "DELETE FROM glpi_computer_device WHERE (FK_computer = '$ID')";
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
