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
// CLASSES Software

class Software {

	var $fields	= array();
	var $updates	= array();
	
	function getfromDB ($ID) {

		// Make new database object and fill variables
		$db = new DB;
		$query = "SELECT * FROM software WHERE (ID = '$ID')";
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

	function countInstallations() {
		$db = new DB;
		$query = "SELECT * FROM inst_software WHERE (sID = ".$this->fields["ID"].")";
		if ($result = $db->query($query)) {
			$number = $db->numrows($result);
			return $number;
		} else {
			return false;
		}
	}
	
	function updateInDB($updates)  {

		$db = new DB;

		for ($i=0; $i < count($updates); $i++) {
			$query  = "UPDATE software SET ";
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
		$query = "INSERT INTO software (";
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

		$query = "DELETE from software WHERE ID = '$ID'";
		if ($result = $db->query($query)) {
			// Delete all Licenses
			$query2 = "SELECT ID FROM licenses WHERE (sID = \"$ID\")";
	
			if ($result2 = $db->query($query2)) {
				$i=0;
				while ($i < $db->numrows($result2)) {
					$lID = $db->result($result2,$i,"ID");
					$lic = new License;
					$lic->deleteFromDB($lID);
					$i++;
				}			
				return true;
			}
		} else {
			return false;
		}
	}

}

class License {

	var $ID		= 0;
	var $sID	= 0;
	var $serial	= "";
	
	function getfromDB ($ID) {

		// Make new database object and fill variables
		$db = new DB;
		$query = "SELECT * FROM licenses WHERE (ID = '$ID')";
		if ($result = $db->query($query)) {
			$this->ID = $ID;
			$this->sID = $db->result($result,0,"sID");
			$this->serial = $db->result($result,0,"serial");
			return true;

		} else {
			return false;
		}
	}
	
	function addToDB() {
		
		$db = new DB;

		// Build query
		$query = "INSERT INTO licenses VALUES (NULL,$this->sID,'$this->serial')";

		if ($result=$db->query($query)) {
			return true;
		} else {
			return false;
		}
	}


	function deleteFromDB($ID) {

		$db = new DB;

		$query = "DELETE from licenses WHERE ID = '$ID'";
		if ($result = $db->query($query)) {
			// Delete Installations
			$query2 = "DELETE FROM inst_software WHERE (license = '$ID')";
			if ($result2 = $db->query($query2)) {
				return true;
			}
		} else {
			return false;
		}
	}

}
?>
