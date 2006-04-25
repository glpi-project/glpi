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
 

 
// CLASSES Networking


class Netdevice extends CommonDBTM {

	function Netdevice () {
		$this->table="glpi_networking";
		$this->type=NETWORKING_TYPE;
		$this->dohistory=true;
	}

	
	function defineOnglets($withtemplate){
		global $lang;
		
		$ong[1]=$lang["title"][26];
		if (haveRight("contract_infocom","r"))
			$ong[4]=$lang["Menu"][26];
		if (haveRight("document","r"))
			$ong[5]=$lang["title"][25];

		if(empty($withtemplate)){
			if (haveRight("show_ticket","1"))
				$ong[6]=$lang["title"][28];
			if (haveRight("link","r"))
				$ong[7]=$lang["title"][34];
			if (haveRight("notes","r"))
				$ong[10]=$lang["title"][37];

			$ong[12]=$lang["title"][38];
		}	
		return $ong;
	}

	function prepareInputForUpdate($input) {
		// set new date.
		$input["date_mod"] = date("Y-m-d H:i:s");
	
		return $input;
	}

	function post_updateItem($input,$updates,$history=1) {

		if(isset($input["state"])){
			if (isset($input["is_template"])&&$input["is_template"]==1){
				updateState(NETWORKING_TYPE,$input["ID"],$input["state"],1,0);
			}else {
				updateState(NETWORKING_TYPE,$input["ID"],$input["state"],0,$history);
			}
		}
	}


	function prepareInputForAdd($input) {
		// set new date.
		$input["date_mod"] = date("Y-m-d H:i:s");
 
		// dump status
		$input["_oldID"]=$input["ID"];
		unset($input['withtemplate']);
		unset($input['ID']);
	
		// Manage state
		$input["_state"]=-1;
		if (isset($input["state"])){
			$input["_state"]=$input["state"];
			unset($input["state"]);
		}

		return $input;
	}

	function postAddItem($newID,$input) {
		global $db;
		// Add state
		if ($input["_state"]>0){
			if (isset($input["is_template"])&&$input["is_template"]==1)
				updateState(NETWORKING_TYPE,$newID,$input["_state"],1,0);
			else updateState(NETWORKING_TYPE,$newID,$input["_state"],0,0);
		}

		// ADD Infocoms
		$ic= new Infocom();
		if ($ic->getFromDBforDevice(NETWORKING_TYPE,$input["_oldID"])){
			$ic->fields["FK_device"]=$newID;
			unset ($ic->fields["ID"]);
			$ic->addToDB();
		}
	
		// ADD Ports
		$query="SELECT ID from glpi_networking_ports WHERE on_device='".$input["_oldID"]."' AND device_type='".NETWORKING_TYPE."';";
		$result=$db->query($query);
		if ($db->numrows($result)>0){
		
			while ($data=$db->fetch_array($result)){
				$np= new Netport();
				$np->getFromDB($data["ID"]);
				unset($np->fields["ID"]);
				unset($np->fields["ifaddr"]);
				unset($np->fields["ifmac"]);
				unset($np->fields["netpoint"]);
				$np->fields["on_device"]=$newID;
				$np->addToDB();
			}
		}

		// ADD Contract				
		$query="SELECT FK_contract from glpi_contract_device WHERE FK_device='".$input["_oldID"]."' AND device_type='".NETWORKING_TYPE."';";
		$result=$db->query($query);
		if ($db->numrows($result)>0){
		
			while ($data=$db->fetch_array($result))
				addDeviceContract($data["FK_contract"],NETWORKING_TYPE,$newID);
		}
	
		// ADD Documents			
		$query="SELECT FK_doc from glpi_doc_device WHERE FK_device='".$input["_oldID"]."' AND device_type='".NETWORKING_TYPE."';";
		$result=$db->query($query);
		if ($db->numrows($result)>0){
		
			while ($data=$db->fetch_array($result))
				addDeviceDocument($data["FK_doc"],NETWORKING_TYPE,$newID);
		}

	}

	function pre_deleteItem($ID) {
		removeConnector($ID);	
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


class Netport extends CommonDBTM {


	var $contact_id		= 0;
	
	var $device_name	= "";
	var $device_ID		= 0;
	var $device_type		= 0;

	function Netport () {
		$this->table="glpi_networking_ports";
		$this->type=-1;
	}

	function post_updateItem($input,$updates,$history){
		$tomatch=array("netpoint","ifaddr","ifmac");
		$updates=array_intersect($updates,$tomatch);
		if (count($updates)){
			$save_ID=$this->fields["ID"];
			$n=new Netwire;
			if ($this->fields["ID"]=$n->getOppositeContact($save_ID)){
				$this->updateInDB($updates);
			}
			$this->fields["ID"]=$save_ID;
		}
	}

	function prepareInputForUpdate($input) {
		// Is a preselected mac adress selected ?
		if (isset($input['pre_mac'])&&!empty($input['pre_mac'])){
			$input['ifmac']=$input['pre_mac'];
			unset($input['pre_mac']);
		}
		return $input;
	}


	function prepareInputForAdd($input) {
		unset($input['search']);
		return $input;
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
