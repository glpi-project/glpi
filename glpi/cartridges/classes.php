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
 
// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");


//!  CartridgeType Class
/**
  This class is used to manage the various types of cartridges.
	\see Cartridge
	\author Julien Dombre
*/
class CartridgeType {

	//! Fields of CartridgeType
	/**
	Fields are :
	- ID 
  	- name : its name
  	- ref : its reference
  	- type : 1= Toner, 2=InkJet 
  	- FK_glpi_enterprise : FK to the manufacturer
  	- deleted : enum('Y','N') NOT NULL default 'N',
  	- comments : some comments
	*/
	var $fields	= array();
	//! Fields tu update
	var $updates	= array();
	
   //! Get the item from table glpi_cartridge_type from the database
    /*!
      \param ID ID of the CartridgeType.
      \return Is the item correctly loaded
    */
	function getfromDB ($ID) {

		// Make new database object and fill variables
		$db = new DB;
		$query = "SELECT * FROM glpi_cartridges_type WHERE (ID = '$ID')";
		
		if ($result = $db->query($query)) {
			$data = $db->fetch_array($result);
			if (!empty($data))	
			foreach ($data as $key => $val) {
				$this->fields[$key] = $val;
			}
			return true;

		} else {
			return false;
		}
	}
	
	function getEmpty () {
	$db = new DB;
	$fields = $db->list_fields("glpi_cartridges_type");
	$columns = mysql_num_fields($fields);
		for ($i = 0; $i < $columns; $i++) {
			$name = mysql_field_name($fields, $i);
			$this->fields[$name] = "";
		}
	}

	function countCartridges() {
		$db = new DB;
		$query = "SELECT * FROM glpi_cartridges WHERE (FK_glpi_cartridges_type = '".$this->fields["ID"]."')";
		if ($result = $db->query($query)) {
			$number = $db->numrows($result);
			return $number;
		} else {
			return false;
		}
	}
	function restoreInDB($ID) {
		$db = new DB;
		$query = "UPDATE glpi_cartridges_type SET deleted='N' WHERE (ID = '$ID')";
		if ($result = $db->query($query)) {
			return true;
		} else {
			return false;
		}
	}
	
	function updateInDB($updates)  {

		$db = new DB;

		for ($i=0; $i < count($updates); $i++) {
			$query  = "UPDATE glpi_cartridges_type SET ";
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
		$query = "INSERT INTO glpi_cartridges_type (";
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

	function deleteFromDB($ID,$force=0) {

		$db = new DB;
		$this->getFromDB($ID);		
		if ($force==1||$this->countCartridges()==0){
			$query = "DELETE from glpi_cartridges_type WHERE ID = '$ID'";
			if ($result = $db->query($query)) {
				// Delete cartridges
				if ($force==1){
				$query3 = "DELETE FROM glpi_cartridges WHERE (FK_glpi_cartridges_type = \"$ID\")";
				$result3 = $db->query($query3);
				} 
				// Delete all cartridge assoc
				$query2 = "DELETE FROM glpi_cartridges_assoc WHERE (FK_glpi_cartridges_type = \"$ID\")";
				if ($result2 = $db->query($query2)) {
					return true;
				}
			} else {
				return false;
			}
		} else {
		$query = "UPDATE glpi_cartridges_type SET deleted='Y' WHERE ID = '$ID'";		
		return ($result = $db->query($query));
		}
	}
	
}
//!  Cartridge Class
/**
  This class is used to manage the cartridges.
  \see CartridgeType
  \author Julien Dombre
*/

class Cartridge {


	var $fields	= array();
	var $updates	= array();
	
	function getfromDB ($ID) {

		// Make new database object and fill variables
		$db = new DB;
		$query = "SELECT * FROM glpi_cartridges WHERE (ID = '$ID')";
		if ($result = $db->query($query)) {
			$data = $db->fetch_array($result);
			foreach ($data as $key => $val) {
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
			$query  = "UPDATE glpi_cartridges SET ";
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
		$query = "INSERT INTO glpi_cartridges (";
		$i=0;
		foreach ($this->fields as $key => $val) 
		if (!is_integer($key)){
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


	function deleteFromDB($ID) {

		$db = new DB;

		$query = "DELETE from glpi_cartridges WHERE ID = '$ID'";
		if ($result = $db->query($query)) {
				return true;
		} else {
			return false;
		}
	}

}

?>
