<?php
/*
 
 ----------------------------------------------------------------------
GLPI - Gestionnaire libre de parc informatique
 Copyright (C) 2002 by the INDEPNET Development Team.
 http://indepnet.net/   http://glpi.indepnet.org
 ----------------------------------------------------------------------
 Based on:
IRMA, Information Resource-Management and Administration
Christian Bauer, turin@incubus.de 

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
 ----------------------------------------------------------------------
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------
*/
 

include ("_relpos.php");
// CLASSES Networking


class Netdevice {

	var $fields	= array();
	var $updates	= array();
	
	function getfromDB ($ID) {

		// Make new database object and fill variables
		$db = new DB;
		$query = "SELECT * FROM networking WHERE (ID = '$ID')";
		if ($result = $db->query($query)) {
			$data = mysql_fetch_array($result);
			for($i=0; $i < count($data); $i++) {
				list($key,$val) = each($data);
				$this->fields[$key] = $val;
			}
			return true;

		} else {
			return false;
		}
	}

	function updateInDB($updates)  {

		$db = new DB;

		for ($i=0; $i < count($updates); $i++) {
			$query  = "UPDATE networking SET ";
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

		$this->comments = addslashes($this->comments);
		
		// Build query
		$query = "INSERT INTO networking (";
		for ($i=0; $i < count($this->fields); $i++) {
			list($key,$val) = each($this->fields);
			$fields[$i] = $key;
			$values[$i] = $val;
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

	function deleteFromDB($ID) {

		$db = new DB;

		$query = "DELETE from networking WHERE ID = '$ID'";
		if ($result = $db->query($query)) {
			return true;
		} else {
			return false;
		}
	}

}


class Netport {

	var $fields		= array();

	var $contact_id		= 0;
	
	var $device_name	= "";
	var $device_ID		= 0;
	
	function getFromDB($ID) {

		// Make new database object and fill variables
		$db = new DB;
		$query = "SELECT * FROM networking_ports WHERE (ID = '$ID')";
		if ($result = $db->query($query)) {
			$data = mysql_fetch_array($result);
			for($i=0; $i < count($data); $i++) {
				list($key,$val) = each($data);
				$this->fields[$key] = $val;
			}

			return true;

		} else {
			return false;
		}
	}

	function getDeviceData($ID,$type) {
		$db = new DB;

		if ($type==2) {
			$table = "networking";
		} else if ($type==1) {
			$table = "computers";
		} else if ($type==3) {
			$table = "printers";
		}

		$query = "SELECT * FROM $table WHERE (ID = '$ID')";
		if ($result=$db->query($query)) {
			$data = mysql_fetch_array($result);
			$this->device_name = $data["name"];
			$this->device_ID = $ID;
			return true;
		} else {
			return false;
		}
	}

	function getContact($ID) {
	
		$wire = new Netwire;
		if ($this->contact_id = $wire->getOppositeContact($ID)) {
			return true;
		} else {
			return false;
		}
		
	}

	function updateInDB($updates)  {

		$db = new DB;

		for ($i=0; $i < count($updates); $i++) {
			$query  = "UPDATE networking_ports SET ";
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

		$this->comments = addslashes($this->comments);
		
		// Build query
		$query = "INSERT INTO networking_ports (";
		for ($i=0; $i < count($this->fields); $i++) {
			list($key,$val) = each($this->fields);
			$fields[$i] = $key;
			$values[$i] = $val;
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

	
	function deleteFromDB($ID) {

		$db = new DB;

		$query = "DELETE from networking_ports WHERE ID = '$ID'";
		if ($result = $db->query($query)) {
			return true;
		} else {
			return false;
		}
	}
	
}


class Netwire {

	var $ID		= 0;
	var $end1	= 0;
	var $end2	= 0;

	function getOppositeContact ($ID) {
		$db = new DB;
		$query = "SELECT * FROM networking_wire WHERE (end1 = '$ID' OR end2 = '$ID')";
		if ($result=$db->query($query)) {
			$data = mysql_fetch_array($result);
			$this->end1 = $data["end1"];
			$this->end2 = $data["end2"];

			if ($this->end1 == $ID) {
				return $this->end2;
			} else if ($this->end2 == $ID) {
				return $this->end1;
			} else {
				return false;
			}
		}
	}
}
?>
