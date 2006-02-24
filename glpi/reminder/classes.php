<?php
/*
 * @version $Id$
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.
 
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
// Original Author of file: Jean-mathieu Doléans
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");


class Reminder {

	var $fields	= array();
	var $updates	= array();
	
	function getfromDB ($ID) {

		global $db;
		$query = "SELECT * FROM glpi_reminder WHERE (ID = '$ID')";
		
		if ($result = $db->query($query)) {
		if ($db->numrows($result)==1){
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
	global $db;
	$fields = $db->list_fields("glpi_reminder");
	$columns = $db->num_fields($fields);
		for ($i = 0; $i < $columns; $i++) {
			$name = $db->field_name($fields, $i);
			$this->fields[$name] = "";
		}
	return true;
	}

	
	

	
	
	function updateInDB($updates)  {

		global $db;

		for ($i=0; $i < count($updates); $i++) {
			$query  = "UPDATE glpi_reminder SET ";
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
		
		global $db;

		// Build query
		$query = "INSERT INTO glpi_reminder (";
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

	function deleteFromDB($ID) {

		global $db;

		$this->getFromDB($ID);		
		
			$query = "DELETE from glpi_reminder WHERE ID = '$ID'";
			$result = $db->query($query);

				
	}
	
}

?>
