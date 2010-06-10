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


class DBmysql {

	var $dbhost	= ""; 
	var $dbuser = ""; 
	var $dbpassword	= "";
	var $dbdefault	= "";
	var $dbh ;

	function DB() {  // Constructor
		$this->dbh = mysql_connect($this->dbhost, $this->dbuser, $this->dbpassword);
		mysql_select_db($this->dbdefault);
	}
	function query($query) {
		return mysql_query($query);
	}
	function result($result, $i, $field) {
		return mysql_result($result, $i, $field);
	}
	function numrows($result) {
		return mysql_num_rows($result);
	}
	function fetch_array($result) {
		return mysql_fetch_array($result);
	}
	function fetch_row($result) {
		return mysql_fetch_row($result);
	}
	function num_fields($result) {
	return mysql_num_fields($result);
	}
	function list_tables() {
	return mysql_list_tables($this->dbdefault);
	}
	
	function error() {
		return mysql_error();
	}
	
}

class Connection {

	var $ID				= 0;
	var $end1			= 0;
	var $end2			= 0;
	var $type			= 0;
	var $device_name	= "";
	var $device_ID		= 0;

	function getComputerContact ($ID) {
		$db = new DB;
		$query = "SELECT * FROM connect_wire WHERE (end1 = '$ID' AND type = '$this->type')";
		if ($result=$db->query($query)) {
			$data = $db->fetch_array($result);
			$this->end2 = $data["end2"];
			return $this->end2;
		} else {
				return false;
		}
	}

	function getComputerData($ID) {
		$db = new DB;
		$query = "SELECT * FROM computers WHERE (ID = '$ID')";
		if ($result=$db->query($query)) {
			$data = $db->fetch_array($result);
			$this->device_name = $data["name"];
			$this->device_ID = $ID;
			return true;
		} else {
			return false;
		}
	}

	function deleteFromDB($ID) {

		$db = new DB;

		$query = "DELETE from connect_wire WHERE (end1 = '$ID' AND type = '$this->type')";
		if ($result = $db->query($query)) {
			return true;
		} else {
			return false;
		}
	}

	function addToDB() {
		$db = new DB;

		// Build query
		$query = "INSERT INTO connect_wire (end1,end2,type) VALUES ('$this->end1','$this->end2','$this->type')";
		if ($result=$db->query($query)) {
			return true;
		} else {
			return false;
		}
	}

}


?>
