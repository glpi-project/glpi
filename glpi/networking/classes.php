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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------
 

include ("_relpos.php");
// CLASSES Networking


class Netdevice extends CommonDBTM {

	function Netdevice () {
		$this->table="glpi_networking";
	}

	


	function cleanDBonPurge($ID) {
		global $db;

		$query = "SELECT ID FROM glpi_networking_ports WHERE (on_device = '$ID' AND device_type = '".NETWORKING_TYPE."')";
		$result = $db->query($query);
		while ($data = $db->fetch_array($result)){
			$q = "DELETE FROM glpi_networking_wire WHERE (end1 = '".$data["ID"]."' OR end2 = '".$data["ID"]."')";
			$result2 = $db->query($q);				
		}

		$job=new Job;

		$query = "SELECT * FROM glpi_tracking WHERE (computer = '$ID'  AND device_type='".NETWORKING_TYPE."')";
		$result = $db->query($query);
		$number = $db->numrows($result);
		$i=0;
		while ($i < $number) {
			$job->deleteFromDB($db->result($result,$i,"ID"));
			$i++;
		}
			
		$query = "DELETE FROM glpi_networking_ports WHERE (on_device = '$ID' AND device_type = '".NETWORKING_TYPE."')";
		$result = $db->query($query);

		$query = "DELETE FROM glpi_state_item WHERE (id_device = '$ID' AND device_type='".NETWORKING_TYPE."')";
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
	}
}


class Netport  extends CommonDBTM {


	var $contact_id		= 0;
	
	var $device_name	= "";
	var $device_ID		= 0;
	var $device_type		= 0;

	function Netdevice () {
		$this->table="glpi_networking_ports";
	}


	function updateInDB($updates)
	{

		global $db;

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

	// SPECIFIC FUNCTIONS

	function getFromNull()
	{
		global $db;
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
		global $db,$LINK_ID_TABLE;
		
		$table = $LINK_ID_TABLE[$type];
		
		$query = "SELECT * FROM $table WHERE (ID = '$ID')";
		if ($result=$db->query($query))
		{
			$data = $db->fetch_array($result);
			$this->device_name = $data["name"];
			$this->deleted = $data["deleted"];
			$this->device_ID = $ID;
			$this->device_type = $type;
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

	
}


class Netwire {

	var $ID		= 0;
	var $end1	= 0;
	var $end2	= 0;

	function getOppositeContact ($ID)
	{
		global $db;
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
