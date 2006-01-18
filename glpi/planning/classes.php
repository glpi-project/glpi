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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
// CLASSES PlanningTracking

class PlanningTracking{
	var $fields	= array();
	var $updates	= array();
	
	function getfromDB ($ID) {

		// Make new database object and fill variables
		$db = new DB;
		$query = "SELECT * FROM glpi_tracking_planning WHERE (ID = '$ID')";
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
	//make an empty database object
	$db = new DB;
	$fields = $db->list_fields("glpi_tracking_planning");
	$columns = $db->num_fields($fields);
	for ($i = 0; $i < $columns; $i++) {
		$name = $db->field_name($fields, $i);
		$this->fields[$name] = "";
	}
}

	function updateInDB($updates)  {

		$db = new DB;

		for ($i=0; $i < count($updates); $i++) {
			$query  = "UPDATE glpi_tracking_planning SET ";
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
		$query = "INSERT INTO glpi_tracking_planning (";
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

		$db = new DB;

		$query = "DELETE from glpi_tracking_planning WHERE ID = '$ID'";
		if ($result = $db->query($query)) {
			return true;
		} else {
			return false;
		}
	}
	
	function is_alreadyplanned(){
		$db = new DB;
		if (!isset($this->fields["id_assign"])||empty($this->fields["id_assign"]))
		return true;
		
		// When modify a planning do not itself take into account 
		$ID_where="";
		if(isset($this->fields["ID"]))
		$ID_where=" (ID <> '".$this->fields["ID"]."') AND ";
		
		$query = "SELECT * FROM glpi_tracking_planning".
		" WHERE $ID_where (id_assign = '".$this->fields["id_assign"]."') AND ".
		" ( ('".$this->fields["begin"]."' < begin AND '".$this->fields["end"]."' > begin) ".
		" OR ('".$this->fields["begin"]."' < end AND '".$this->fields["end"]."' >= end) ".
		" OR ('".$this->fields["begin"]."' >= begin AND '".$this->fields["end"]."' < end))";
//		echo $query."<br>";
		if ($result=$db->query($query)){
			return ($db->numrows($result)>0);
		}
		return true;
		}
	function test_valid_date(){
		return (strtotime($this->fields["begin"])<strtotime($this->fields["end"]));
		}

	function displayError($type,$ID,$target){
		global $HTMLRel,$lang;
		
		echo "<br><div align='center'>";
		switch ($type){
			case "date":
			 echo $lang["planning"][1];
			break;
			case "is_res":
			 echo $lang["planning"][0];
			break;
			default :
				echo "Erreur Inconnue";
			break;
		}
		echo "<br><a href='".$target."?job=$ID'>".$lang["planning"][2]."</a>";
		echo "</div>";
		}

}


?>