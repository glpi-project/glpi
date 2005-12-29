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

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

class Connection {

	var $ID				= 0;
	var $end1			= 0;
	var $end2			= 0;
	var $type			= 0;
	var $device_name	= "";
	var $device_ID		= 0;

	function getComputerContact ($ID) {
		$db = new DB;
		$query = "SELECT * FROM glpi_connect_wire WHERE (end1 = '$ID' AND type = '$this->type')";
		if ($result=$db->query($query)) {
			if ($db->numrows($result)==0) return false;
			$ret=array();
			while ($data = $db->fetch_array($result)){
				if (isset($data["end2"])) {
					$ret[$data["ID"]] = $data["end2"];
				}
			}
			return $ret;
		} else {
				return false;
		}
	}

	function getComputerData($ID) {
		$db = new DB;
		$query = "SELECT * FROM glpi_computers WHERE (ID = '$ID')";
		if ($result=$db->query($query)) {
			if ($db->numrows($result)==0) return false;
			$data = $db->fetch_array($result);
			$this->device_name = $data["name"];
			$this->deleted = $data["deleted"];
			$this->device_ID = $ID;
			return true;
		} else {
			return false;
		}
	}

	function deleteFromDB($ID) {

		$db = new DB;

		$query = "DELETE from glpi_connect_wire WHERE (ID = '$ID')";
		if ($result = $db->query($query)) {
			return true;
		} else {
			return false;
		}
	}

	function addToDB() {
		$db = new DB;

		// Build query
		$query = "INSERT INTO glpi_connect_wire (end1,end2,type) VALUES ('$this->end1','$this->end2','$this->type')";
		$result=$db->query($query);
		return $db->insert_id();
	}

}
?>