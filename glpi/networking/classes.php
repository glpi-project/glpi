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

// Based on:
// IRMA, Information Resource-Management and Administration
// Christian Bauer 
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------
 

include ("_relpos.php");
// CLASSES Networking


class Netdevice {

	var $fields	= array();
	var $updates	= array();
	
	function getfromDB ($ID) {

		// Make new database object and fill variables
		$db = new DB;
		$query = "SELECT * FROM glpi_networking WHERE (ID = '$ID')";
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
	
function getEmpty() {
		//make an empty database object
		$db = new DB;
		
		$fields = $db->list_fields("glpi_networking");
		$columns = mysql_num_fields($fields);
		for ($i = 0; $i < $columns; $i++) {
			$name = mysql_field_name($fields, $i);
			$this->fields[$name] = "";
		}
}

	function updateInDB($updates)  {
		$db = new DB;

		for ($i=0; $i < count($updates); $i++) {
			$query  = "UPDATE glpi_networking SET ";
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

		$this->fields["comments"] = addslashes($this->fields["comments"]);
		
		// Build query
		$query = "INSERT INTO glpi_networking (";
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
		$query = "UPDATE glpi_networking SET deleted='N' WHERE (ID = '$ID')";
		if ($result = $db->query($query)) {
			return true;
		} else {
			return false;
		}
	}

	function isUsed($ID){
	$db = new DB;		
	
	$query="SELECT * from glpi_tracking where computer = '$ID' AND device_type='".NETWORKING_TYPE."'";
	$result = $db->query($query);
	if ($db->numrows($result)>0) return true;
	
	$query="SELECT * from glpi_networking_ports where on_device = '$ID' AND device_type='".NETWORKING_TYPE."'";
	$result = $db->query($query);
	if ($db->numrows($result)==0) return false;
	else {
		while ($data=$db->fetch_array($result)){
			$query2="SELECT * from glpi_networking_wire where end1 = '".$data['ID']."' OR end2='".$data['ID']."'";
			$result2 = $db->query($query2);
			if ($db->numrows($result2)>0) return true;
		}
		return false;
	}
	}


	function deleteFromDB($ID,$force=0) {

		$db = new DB;

		if ($force==1||!$this->isUsed($ID)){
			$query = "DELETE from glpi_networking WHERE ID = '$ID'";
			
			if ($result = $db->query($query)) {
			
				$query = "SELECT ID FROM glpi_networking_ports WHERE (on_device = '$ID' AND device_type = '".NETWORKING_TYPE."')";
				$result = $db->query($query);
				while ($data = $db->fetch_array($result)){
						$q = "DELETE FROM glpi_networking_wire WHERE (end1 = '".$data["ID"]."' OR end2 = '".$data["ID"]."')";
						$result2 = $db->query($q);				
				}

				$query = "DELETE FROM glpi_tracking WHERE (computer = '$ID' AND device_type='".NETWORKING_TYPE."')";
				$result = $db->query($query);
			
				$query = "DELETE FROM glpi_networking_ports WHERE (on_device = '$ID' AND device_type = '".NETWORKING_TYPE."')";
				$result = $db->query($query);
			
				$query = "DELETE FROM glpi_infocoms WHERE (FK_device = '$ID' AND device_type='".NETWORKING_TYPE."')";
				$result = $db->query($query);

				$query = "DELETE FROM glpi_contract_device WHERE (FK_device = '$ID' AND device_type='".NETWORKING_TYPE."')";
				$result = $db->query($query);

				$query="select * from glpi_reservation_item where (device_type='".NETWORKING_TYPE."' and id_device='$ID')";
				if ($result = $db->query($query)) {
					if ($db->numrows($result)>0) {
						deleteReservationItem(array("ID"=>$db->result($result,0,"ID")));
					}
				}
			
				return true;
			} else {
				return false;
			}
		} else {
		$query = "UPDATE glpi_networking SET deleted='Y' WHERE ID = '$ID'";		
		return ($result = $db->query($query));
		}
			
	}

}


class Netport {

	var $fields		= array();

	var $contact_id		= 0;
	
	var $device_name	= "";
	var $device_ID		= 0;
	
	function getFromDB($ID) 
	{

		// Make new database object and fill variables
		$db = new DB;
		$query = "SELECT * FROM glpi_networking_ports WHERE (ID = '$ID')";
		if ($result = $db->query($query))
		{
			$data = $db->fetch_assoc($result);
			if (is_array($data))
			foreach ($data as $key => $val) {
				$this->fields[$key] = $val;
			}

			return true;

		} else {
			return false;
		}
	}
	
	function getFromNull()
	{
		$db = new DB;
		$query = "select * from glpi_networking_ports";
		$result = $db->query($query);
		$num_flds = $db->num_fields($result);
		for($i=0; $i < $num_flds; $i++)
		{
			$key = $db->field_name($result,$i);
			$this->fields[$key] = "";
		}
	}

	function getDeviceData($ID,$type)
	{
		$db = new DB;

		if ($type==2)
		{
			$table = "glpi_networking";
		}
		else if ($type==1)
		{
			$table = "glpi_computers";
		} 
		else if ($type==3)
		{
			$table = "glpi_printers";
		}

		$query = "SELECT * FROM $table WHERE (ID = '$ID')";
		if ($result=$db->query($query))
		{
			$data = $db->fetch_array($result);
			$this->device_name = $data["name"];
			$this->deleted = $data["deleted"];
			$this->device_ID = $ID;
			return true;
		}
		else 
		{
			return false;
		}
	}

	function getContact($ID) 
	{
	
		$wire = new Netwire;
		if ($this->contact_id = $wire->getOppositeContact($ID))
		{
			return true;
		}
		else
		{
			return false;
		}
		
	}

	function updateInDB($updates)
	{

		$db = new DB;

		for ($i=0; $i < count($updates); $i++)
		{
			$query  = "UPDATE glpi_networking_ports SET ";
			$query .= $updates[$i];
			$query .= "='";
			$query .= $this->fields[$updates[$i]];
			$query .= "' WHERE ID='";
			$query .= $this->fields["ID"];	
			$query .= "'";
		// Update opposite if exist
		if ($updates[$i]=="netpoint"||$updates[$i]=="ifaddr"||$updates[$i]=="ifmac"){
			$n=new Netwire;
			if ($opp=$n->getOppositeContact($this->fields["ID"])){
				$query.=" OR ID='$opp' ";
			}
		}
			$result=$db->query($query);
			}	
			
	}
	
	function addToDB()
	{
		$db = new DB;
		
		// Build query
		$query = "INSERT INTO glpi_networking_ports (";
		$i=0;
		foreach ($this->fields as $key => $val) {
			$fields[$i] = $key;
			$values[$i] = $val;
			$i++;
		}		
		for ($i=0; $i < count($fields); $i++)
		{
			$query .= $fields[$i];
			if ($i!=count($fields)-1)
			{
				$query .= ",";
			}
		}
		$query .= ") VALUES (";
		for ($i=0; $i < count($values); $i++) 
		{
			$query .= "'".$values[$i]."'";
			if ($i!=count($values)-1)
			{
				$query .= ",";
			}
		}
		$query .= ")";

		if ($result=$db->query($query))
		{
			return true;
		} else
		{
			return false;
		}
	
	}

	
	function deleteFromDB($ID)
	{

		$db = new DB;

		$query = "DELETE from glpi_networking_ports WHERE ID = '$ID'";
		if ($result = $db->query($query))
		{
			return true;
		} 
		else
		{
			return false;
		}
	}
	
}


class Netwire {

	var $ID		= 0;
	var $end1	= 0;
	var $end2	= 0;

	function getOppositeContact ($ID)
	{
		$db = new DB;
		$query = "SELECT * FROM glpi_networking_wire WHERE (end1 = '$ID' OR end2 = '$ID')";
		if ($result=$db->query($query))
		{
			$data = $db->fetch_array($result);
			if (is_array($data)){
			 $this->end1 = $data["end1"];
			 $this->end2 = $data["end2"];
			 }

			if ($this->end1 == $ID)
			{
				return $this->end2;
			} 
			else if ($this->end2 == $ID)
			{
				return $this->end1;
			} 
			else 
			{
				return false;
			}
		}
	}
}
?>
