<?php
/*
 
  ----------------------------------------------------------------------
GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2004 by the INDEPNET Development Team.
 
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

		/*if ($template) {
			$table = "glpi_templates";
		} else {*/
		$table = "glpi_computers";
		//}
		
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
				$query = "SELECT ID, device_type, FK_device, specificity FROM glpi_computer_device WHERE (FK_computers = '$ID')";
				if ($result = $db->query($query)) {
					if ($db->numrows($result)>0) {
						$i = 0;
						while($data = $db->fetch_array($result)) {
							$this->devices[$i] = array("compDevID"=>$data["ID"],"devTable"=>$data["device_type"],"devID"=>$data["FK_device"],"specificity"=>$data["specificity"]);
							$i++;
						}
						return true;
					}
					else return false;
				} 
				else return false;
			}
			else return false;
		}
		else return false;
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
		// Mise a jour du lieu des éléments rattachés
		if ($updates[$i]=="location"){
			$_SESSION["MESSAGE_AFTER_REDIRECT"]=$lang["computers"][48];
			$updates2[0]="location";
			//printers
			$query = "SELECT * from glpi_connect_wire WHERE end2='".$this->fields["ID"]."' AND type='3'";
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
			$query = "SELECT * from glpi_connect_wire WHERE end2='".$this->fields["ID"]."' AND type='4'";
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
			$query = "SELECT * from glpi_connect_wire WHERE end2='".$this->fields["ID"]."' AND type='5'";
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

		if ($result=$db->query($query)) {
			return true;
		} else {
			return false;
		}
	}

	function deleteFromDB($ID,$template) {

		if (!empty($template)) {
			$table = "glpi_templates";
		} else {
			$table = "glpi_computers";
		}

		$db = new DB;

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
			$query = "DELETE FROM glpi_tracking WHERE (computer = '$ID')";
			$result = $db->query($query);
			$query = "DELETE FROM glpi_inst_software WHERE (cID = '$ID')";
			$result = $db->query($query);

			$query = "SELECT ID FROM glpi_networking_ports WHERE (on_device = '$ID' AND device_type = '1')";
			$result = $db->query($query);
			while ($data = $db->fetch_array($result)){
					$q = "DELETE FROM glpi_networking_wire WHERE (end1 = '".$data["ID"]."' OR end2 = '".$data["ID"]."')";
					$result2 = $db->query($q);					
					}


			$query = "DELETE FROM glpi_networking_ports WHERE (device_on = '$ID' AND device_type = '1')";
			$result = $db->query($query);
			$query = "DELETE FROM glpi_connect_wire WHERE (end2 = '$ID')";
			$result = $db->query($query);

			$query="select * from glpi_reservation_item where (device_type='1' and id_device='$ID')";
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
	}
}


?>
