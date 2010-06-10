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

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License (GPL)
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 To read the license please visit http://www.gnu.org/copyleft/gpl.html
 ----------------------------------------------------------------------
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------
*/
 

include ("_relpos.php");
// CLASSES Setup

class User {

	var $fields	= array();
	
	function getFromDB($name) {
		$db = new DB;
		$query = "SELECT * FROM users WHERE (name = '$name')";
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

	function addToDB() {
		
		$db = new DB;

		$this->comments = addslashes($this->comments);
		
		// Build query
		$query = "INSERT INTO users (";
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


	function updateInDB($updates)  {

		$db = new DB;

		for ($i=0; $i < count($updates); $i++) {
			$query  = "UPDATE users SET ";
			$query .= $updates[$i];
			$query .= "=";
			if ($updates[$i]=="password") {
				$query .= "PASSWORD('".$this->fields[$updates[$i]]."')";
			} else {
				$query .= "'".$this->fields[$updates[$i]]."'";
			}
			$query .= " WHERE name='";
			$query .= $this->fields["name"];	
			$query .= "'";
			$result=$db->query($query);
		}
		
	}

	function deleteFromDB($name) {

		$db = new DB;

		$query = "DELETE from users WHERE name = '$name'";
		if ($result = $db->query($query)) {
			$query2 = "DELETE from prefs WHERE user = '$name'";
			if ($result2 = $db->query($query2)) {
				// Tracking items left?
				$query_track = "SELECT assign FROM tracking WHERE (assign = '$name')";
				$result_track = $db->query($query_track);
				if ($db->numrows($result_track)>0) { 
					$query3 = "UPDATE tracking SET assign = '' WHERE (assign = '$name')";
					if ($result3 = $db->query($query3)) {
						return true;
					}
				} else {
					return true;
				}
			}
		} else {
			return false;
		}
	}
}

class Template {

	var $fields	= array();
	var $updates	= array();
	
	function getfromDB ($ID) {

		// Make new database object and fill variables
		$db = new DB;
		$query = "SELECT * FROM templates WHERE (ID = '$ID')";
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
			$query  = "UPDATE templates SET ";
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
		$query = "INSERT INTO templates (";
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

		$query = "DELETE from templates WHERE ID = '$ID'";
		if ($result = $db->query($query)) {
			return true;
		} else {
			return false;
		}
	}

}

?>
