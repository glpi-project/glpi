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
// CLASSES Printers


class Printer {

	var $fields	= array();
	var $updates	= array();
	
	function getfromDB ($ID) {

		// Make new database object and fill variables
		$db = new DB;
		$query = "SELECT * FROM glpi_printers WHERE (ID = '$ID')";
		if ($result = $db->query($query)) {
			if ($db->numrows($result)==1){
			$data = $db->fetch_assoc($result);
			foreach ($data as $key => $val) {
				$this->fields[$key] = $val;
			}
			return true;
		} else return false;
		
		} else {
			return false;
		}
	}

function getEmpty () {
	//make an empty database object
	$db = new DB;
	$fields = $db->list_fields("glpi_printers");
	$columns = $db->num_fields($fields);
	for ($i = 0; $i < $columns; $i++) {
		$name = $db->field_name($fields, $i);
		$this->fields[$name] = "";
	}
	return true;
}

	function updateInDB($updates)  {

		$db = new DB;

		for ($i=0; $i < count($updates); $i++) {
			$query  = "UPDATE glpi_printers SET ";
			$query .= $updates[$i];
			$query .= "='";
			$query .= $this->fields[$updates[$i]];
			$query .= "' WHERE ID='";
			$query .= $this->fields["ID"];	
			$query .= "'";
			$result=$db->query($query);
		}
		
	}
	
	function addToDB() {
		
		$db = new DB;
		// Build query
		$query = "INSERT INTO glpi_printers (";
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
		$db = new DB;
		$query = "UPDATE glpi_printers SET deleted='N' WHERE (ID = '$ID')";
		if ($result = $db->query($query)) {
			return true;
		} else {
			return false;
		}
	}

	function isUsed($ID){
	return true;
	$db = new DB;		
	$query="SELECT * from glpi_connect_wire where end1 = '$ID' AND type='".PRINTER_TYPE."'";
	$result = $db->query($query);
	if ($db->numrows($result)>0) return true;
	
	$query="SELECT * from glpi_tracking where computer = '$ID' AND device_type='".PRINTER_TYPE."'";
	$result = $db->query($query);
	if ($db->numrows($result)>0) return true;
	
	$query="SELECT * from glpi_networking_ports where on_device = '$ID' AND device_type='".PRINTER_TYPE."'";
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

		$db = new DB;
		if ($force==1||!$this->isUsed($ID)){
			$query = "DELETE from glpi_printers WHERE ID = '$ID'";
			if ($result = $db->query($query)) {
				$query = "SELECT ID FROM glpi_networking_ports WHERE (on_device = '$ID' AND device_type = '".PRINTER_TYPE."')";
				$result = $db->query($query);
				while ($data = $db->fetch_array($result)){
						$q = "DELETE FROM glpi_networking_wire WHERE (end1 = '".$data["ID"]."' OR end2 = '".$data["ID"]."')";
						$result2 = $db->query($q);					
						}

				$query2 = "DELETE FROM glpi_networking_ports WHERE (on_device = $ID AND device_type = '".PRINTER_TYPE."')";
				$result2 = $db->query($query2);
			
				Disconnect($ID,PRINTER_TYPE);	
			
				$query="select * from glpi_repair_item where (device_type='".PRINTER_TYPE."' and id_device='$ID')";
				$result = $db->query($query);
				
				$query="select * from glpi_reservation_item where (device_type='".PRINTER_TYPE."' and id_device='$ID')";
				if ($result = $db->query($query)) {
					if ($db->numrows($result)>0)
					deleteReservationItem(array("ID"=>$db->result($result,0,"ID")));
				}
			
				$query = "DELETE FROM glpi_infocoms WHERE (FK_device = '$ID' AND device_type='".PRINTER_TYPE."')";
				$result = $db->query($query);

				$query = "SELECT * FROM glpi_tracking WHERE (computer = '$ID'  AND device_type='".PRINTER_TYPE."')";
				$result = $db->query($query);
				$number = $db->numrows($result);
				$i=0;
				while ($i < $number) {
			  		$job = $db->result($result,$i,"ID");
			    		$query = "DELETE FROM glpi_followups WHERE (tracking = '$job')";
			      		$db->query($query);
					$i++;
				}

				$query = "DELETE FROM glpi_tracking WHERE (computer = '$ID' AND device_type='".PRINTER_TYPE."')";
				$result = $db->query($query);

				$query = "DELETE FROM glpi_contract_device WHERE (FK_device = '$ID' AND device_type='".PRINTER_TYPE."')";
				$result = $db->query($query);

				$query = "UPDATE glpi_cartridges  SET FK_glpi_printers = NULL WHERE (FK_glpi_printers='$ID')";
				$result = $db->query($query);
			
				return true;
			} else {
				return false;
			}
		} else {
		$query = "UPDATE glpi_printers SET deleted='Y' WHERE ID = '$ID'";		
		return ($result = $db->query($query));
		}
	}

}

?>
