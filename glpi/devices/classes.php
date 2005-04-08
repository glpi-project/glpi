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

include ("_relpos.php");

//Class Devices
class Device {
	var $fields = array();
	var $updates = array();
	var $table='';
	var $type=0;

	function Device($dev_type) {
		$this->type=$dev_type;
		$this->table=getDeviceTable($dev_type);
	}
	
	function getFromDB($ID) {
		$db = new DB;
		$query = "SELECT * FROM ".$this->table." WHERE (ID = '$ID') limit 0,1";
		//echo $query;
		if ($result = $db->query($query)) {
			if ($db->numrows($result)==1) {
				$data = $db->fetch_array($result);
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
		$fields = $db->list_fields($this->table);
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
			$query  = "UPDATE ".$this->table." SET ";
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
		$i=0;
		// Build query
		$query = "INSERT INTO ".$this->table." (";
		
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
			return $db->insert_id();
		} else {
			return false;
		}
	}
	
	function deleteFromDB($ID) {

		$db = new DB;
		$query = "DELETE from ".$this->table." WHERE (ID = '$ID')";
		
		if ($result = $db->query($query)) {
			$query2 = "DELETE FROM glpi_computer_device WHERE (FK_device = '$ID' AND device_type='".$this->type."')";
			
			if ($result2 = $db->query($query2)) {
			
			
			
				return true;
			}
			else return false;
		}
		else return false;
	}
	
	function updateSpecif($compID,$newSpecif) {
		$db = new DB;
		$query = "UPDATE glpi_computer_device set specification = '".$newSpecif."' where ID = '".$compDevID."'";
		if($db->query($query)) {
			return true;
		} else { 
			return false;
		}
	}
	
	function computer_link($compID,$device_type,$specificity='') {
		$db = new DB;
		$query = "INSERT INTO glpi_computer_device (device_type,FK_device,FK_computers,specificity) values ('".$device_type."','".$this->fields["ID"]."','".$compID."','".$specificity."')";
		if($db->query($query)) {
			return true;
		} else { 
			return false;
		}
	}
}
?>