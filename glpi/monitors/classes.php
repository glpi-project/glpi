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
// CLASSES Monitors


class Monitor {

	var $fields	= array();
	var $updates	= array();
	
	function getfromDB ($ID) {

		// Make new database object and fill variables
		$db = new DB;
		$query = "SELECT * FROM glpi_monitors WHERE (ID = '$ID')";
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
	$fields = $db->list_fields("glpi_monitors");
	$columns = mysql_num_fields($fields);
	for ($i = 0; $i < $columns; $i++) {
		$name = mysql_field_name($fields, $i);
		$this->fields[$name] = "";
	}
	return true;
}

	function updateInDB($updates)  {

		$db = new DB;

		for ($i=0; $i < count($updates); $i++) {
			$query  = "UPDATE glpi_monitors SET ";
			$query .= $updates[$i];
			$query .= "='";
			$query .= $this->fields[$updates[$i]];
			$query .= "' WHERE ID='";
			$query .= $this->fields["ID"];	
			$query .= "'";
			$result=$db->query($query);
		}
		
	}
	function getInsertElementID(){
		$db = new DB;

		// Build query
		$query = "SELECT ID FROM glpi_monitors WHERE ";
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
	
	function addToDB() {
		
		$db = new DB;

		// Build query
		$query = "INSERT INTO glpi_monitors (";
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


	function restoreInDB($ID) {
		$db = new DB;
		$query = "UPDATE glpi_monitors SET deleted='N' WHERE (ID = '$ID')";
		if ($result = $db->query($query)) {
			return true;
		} else {
			return false;
		}
	}

	function isUsed($ID){
	$db = new DB;		
	$query="SELECT * from glpi_connect_wire where end1 = '$ID' AND type='".MONITOR_TYPE."'";
	$result = $db->query($query);
	if ($db->numrows($result)>0) return true;
	
	$query="SELECT * from glpi_tracking where computer = '$ID' AND device_type='".MONITOR_TYPE."'";
	$result = $db->query($query);
	return ($db->numrows($result)>0);
	
	}


	function deleteFromDB($ID,$force=0) {

		$db = new DB;
		if ($force==1||!$this->isUsed($ID)){
			$query = "DELETE from glpi_monitors WHERE ID = '$ID'";
			if ($result = $db->query($query)) {

				$query = "DELETE FROM glpi_infocoms WHERE (FK_device = '$ID' AND device_type='".MONITOR_TYPE."')";
				$result = $db->query($query);

				$query = "DELETE FROM glpi_tracking WHERE (computer = '$ID' AND device_type='".MONITOR_TYPE."')";
				$result = $db->query($query);
				
				$query="select * from glpi_repair_item where (device_type='".MONITOR_TYPE."' and id_device='$ID')";
				$result = $db->query($query);

				$query="select * from glpi_reservation_item where (device_type='".MONITOR_TYPE."' and id_device='$ID')";
				if ($result = $db->query($query)) {
					if ($db->numrows($result)>0) {
						deleteReservationItem(array("ID"=>$db->result($result,0,"ID")));
					}
				}
		
				Disconnect($ID,MONITOR_TYPE);
			
				$query = "DELETE FROM glpi_contract_device WHERE (FK_device = '$ID' AND device_type='".MONITOR_TYPE."')";
				if ($result = $db->query($query)) {
					return true;
				}
			} else {
			return false;
			}
		} else {
		$query = "UPDATE glpi_monitors SET deleted='Y' WHERE ID = '$ID'";		
		return ($result = $db->query($query));
		}
	}

}

?>
