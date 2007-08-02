<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2007 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

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
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------


if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}

// Tracking Classes

class Transfer extends CommonDBTM{

	var $already_transfer=array();	
	var $options=array();
	var $to=-1;
	function Transfer(){
		$this->table="glpi_transfer";
		$this->type=-1;
	}

	// Generic function
	function moveItems($items,$to,$options){
		// $items=array(TYPE => array(id_items))
		// $options=array()

		$default_options=array(
			'keep_tickets'=>0,
			'keep_networklinks'=>0,
			'keep_history'=>0,
			'keep_history'=>0,
			'keep_devices'=>0,
			'keep_dc_monitor'=>0,
			'clean_dc_monitor'=>0,
			'keep_dc_phone'=>0,
			'clean_dc_phone'=>0,
			'keep_dc_peripheral'=>0,
			'clean_dc_peripheral'=>0,
			'keep_dc_printerr'=>0,
			'clean_dc_printer'=>0,
		);

		
		if ($this->to>0){
			// Store to
			$this->to=$to;
			// Store options
			$this->options=$options;
			foreach ($default_options as $key => $val){
				if (!isset($this->options[$key])){
					$this->options[$key]=$val;
				}
			}
			// Computer first
			if (isset($items[COMPUTER_TYPE])&&count($items[COMPUTER_TYPE])){
				foreach ($items[COMPUTER_TYPE] as $ID){
					$this->transferItem(COMPUTER_TYPE,$ID,$ID);
				}
			}
			// Inventory Items : MONITOR....

			// Management Items

		}

	}

	/**
	* transfer an item to another item (may be the same) in the new entity
	*
	*@param $type device type to transfer
	*@param $ID ID of the item to transfer
	*@param $newID new ID of the ite
	*
	* Transfer item to a new Item if $ID==$newID : only update FK_entities field : $ID!=$new ID -> copy datas
	*@return nothing (diplays)
	*
	**/
	function transferItem($type,$ID,$newID){
		$ci=new CommonItem();
		$cinew=new CommonItem();
		// Check computer exists ?
		if ($ci->getFromDB($type,$ID)&&$cinew->getFromDB($type,$newID)){
			// Check item in other entity
			if ($ci->getFields['FK_entities']>0&&$ci->getFields['FK_entities']!=$this->to){
				// Network connection ? keep connected / keep_disconnected / delete
				if (in_array($type,
					array(COMPUTER_TYPE,NETWORKING_TYPE,PRINTER_TYPE,MONITOR_TYPE,PERIPHERAL_TYPE,PHONE_TYPE))) {
					$this->transferNetworkLink($type,$ID,$newID);
				}
				// Device : keep / delete : network case : delete if net connection delete in ocs case
				if (in_array($type,array(COMPUTER_TYPE))){
					$this->transferDevices($type,$ID,$newID);
				}
				// Reservation : keep / delete
				if (in_array($type,$CFG_GLPI["reservation_types"])){
					$this->transferReservations($type,$ID,$newID);
				}
				// History : keep / delete
				$this->transferHistory($type,$ID,$newID);
				// Ticket : delete / keep and clean ref / keep and move
				$this->transferTickets($type,$ID,$newID);
				// Infocoms : keep / delete
				$this->transferInfocoms($type,$ID,$newID);


				// Monitor Direct Connect : keep / delete + clean unused / keep unused 
				// Peripheral Direct Connect : keep / delete + clean unused / keep unused 
				// Phone Direct Connect : keep / delete + clean unused / keep unused 
				// Printer Direct Connect : keep / delete + clean unused / keep unused 
				if ($type==COMPUTER_TYPE){
					$this->transferDirectConnection($type,$ID,MONITOR_TYPE);
					$this->transferDirectConnection($type,$ID,PERIPHERAL_TYPE);
					$this->transferDirectConnection($type,$ID,MONITOR_TYPE);
					$this->transferDirectConnection($type,$ID,PRINTER_TYPE);
				}
				// Computer Direct Connect : delete link if it is the initial transfer item (no recursion)
				if ($inittype==0&&in_array($type,
					array(PRINTER_TYPE,MONITOR_TYPE,PERIPHERAL_TYPE,PHONE_TYPE))){
					$this->deleteDirectConnection($type,$ID,$newID);
				}

				// Licence / Software :  keep / delete + clean unused / keep unused 

				// Document : keep / delete + clean unused / keep unused + duplicate file ?

				// Contract : keep / delete + clean unused / keep unused

				// Enterprise (depending of item link) / Contract - infocoms : keep / delete + clean unused / keep unused

				// Contact / Enterprise : keep / delete + clean unused / keep unused

				// Users ????

				// Transfer Item
				$cinew->obj->update(array("ID"=>$newID,'FK_entities' => $this->to));
				$this->already_transfer[$type][$ID]=$newID;
			}
		}
	}

	function deleteDirectConnection($type,$ID){
		global $DB;
		// TODO : manage ID / newID
		// Delete Direct connection to computers for item type 
		$query = "SELECT * from glpi_connect_wire WHERE end1='$ID' AND type='".$type."'";
		$result = $DB->query($query);
	}

	function transferDirectConnection($type,$ID,$link_type){
		global $DB;
		// TODO : manage ID / newID
		// Default : delete
		$keep=0;
		$clean=0;
		switch ($link_type){
			case PRINTER_TYPE:
				$keep=$this->options['keep_dc_printer'];
				$clean=$this->options['clean_dc_printer'];
				break;
			case MONITOR_TYPE:
				$keep=$this->options['keep_dc_monitor'];
				$clean=$this->options['clean_dc_monitor'];
				break;
			case PERIPHERAL_TYPE:
				$keep=$this->options['keep_dc_peripheral'];
				$clean=$this->options['clean_dc_peripheral'];
				break;
			case PHONE_TYPE:
				$keep=$this->options['keep_dc_phone'];
				$clean=$this->options['clean_dc_phone'];
				break;
		}

		$ci=new CommonItem();
		// Get connections
		$query = "SELECT * FROM glpi_connect_wire WHERE end2='$ID' AND type='".$link_type."'";
		if ($result = $DB->query($query)) {
			if ($DB->numrows($result)!=0) { 
				// Foreach get item 
				while ($data=$DB->fetch_row($result)) {
					$item_ID=$data['end1'];
					if ($ci->getFromDB($link_type,$item_ID)){
						// If unique : 
						if ($ci->obj->fields['is_global']==0){
							//if keep -> transfer list else unlink
							if ($keep){
								$this->transfer($link_type,$item_ID,$type);
							} else {
								$del_query="DELETE FROM glpi_connect_wire WHERE ID = '".$data['ID']."'";
								$DB->query($del_query);
							}
							//if clean -> delete
							if ($keep){
								$ci->obj->delete(array('ID'=>$item_ID));
							}
						} else {
						// If global : 
							// copy if not already transfer + update link 
							// if clean and not linked dc -> delete
						}
					} else {
						// Unexisting item
						$del_query="DELETE FROM glpi_connect_wire WHERE ID = '".$data['ID']."'";
						$DB->query($del_query);
					}
				}
			}
		}	

	}
	function transferTickets($type,$ID,$newID){
		global $DB;
		// TODO : manage ID / newID
		$job= new Job();

		$query = "SELECT ID FROM glpi_tracking WHERE (computer = '$ID' AND device_type = '$type')";
		if ($result = $DB->query($query)) {
			if ($DB->numrows($result)!=0) { 
				switch ($this->options['keep_tickets']){
					case 2: // Transfer
						while ($data=$DB->fetch_row($result)) {
							$job->update(array("ID"=>$data['ID'],'FK_entities' => $this->to));
							$this->already_transfer[TRACKING_TYPE][$data['ID']]=$data['ID'];
						}
					break;
					case 1: // Clean ref
						while ($data=$DB->fetch_row($result)) {
							$job->update(array("ID"=>$data['ID'],'device_type' => 0, 'computer'=>0));
							$this->already_transfer[TRACKING_TYPE][$data['ID']]=$data['ID'];
						}
					break;
					case 0:
						while ($data=$DB->fetch_row($result)) {
							$job->delete(array('ID'=>$data['ID']));
						}
					break;
				}
			}
		}

	}

	function transferHistory($type,$ID,$newID){
		global $DB;

		switch ($this->options['keep_history']){
			case 0 :  // delete
				if ($ID==$newID){ // Same item -> delete
					$query = "DELETE FROM glpi_history WHERE ( device_type = '$type' AND FK_glpi_device = '$ID')";
					$result = $DB->query($query);
				}
				break;
			case 1 :	// Keep history
			default : 
				// Copy datas if not the same : delete history
				if ($ID!=$newID){
					$query = "SELECT * FROM glpi_history WHERE (device_type = '$type' AND FK_glpi_device = '$ID')";
					$result=$DB->query($query);
					if ($result = $DB->query($query)) {
						if ($DB->numrows($result)!=0) { 
							while ($data=$DB->fetch_row($result)) {
								$query = "INSERT INTO glpi_history (FK_glpi_device,device_type,device_internal_type,linked_action,user_name,date_mod,id_search_option,old_value,new_value)  VALUES ('$newID','$type','".$data['device_internal_type']."','".$data['linked_action']."','". addslashes($data['user_name'])."','".$data['date_mod']."','".$data['id_search_option']."','".$data['old_value']."','".$data['new_value']."');";
								$DB->query($query);
							}
						}
					}
				}
				break;
		}
	}

	function transferInfocoms($type,$ID,$newID){
		global $DB;

		$ic=new Infocom();
		if ($ic->getFromDBforDevice($tyep,$ID)){
			switch ($this->options['keep_infocoms']){
				case 0 :  // delete
					if ($ID==$newID){ // Same item -> delete
						$query = "DELETE FROM glpi_infocoms WHERE ( device_type = '$type' AND FK_device = '$ID')";
						$result = $DB->query($query);
					}
					break;
				case 1 : // Keep
				default : 
					if ($ID!=$newID){
						// Copy items
						$ic->fields['FK_device']=$newID;
						unset($ic->fields['ID']);
						$ic->add();
					}
					break;
			}
		}

	}

	function transferReservations($type,$ID,$newID){
		global $DB;

		$ri=new ReservationItem();

		if ($ri->getFromDBbyItem($type,$ID)){
			switch ($this->options['keep_reservations']){
				case 0 :  // delete
					if ($ID==$newID){ // Same item -> delete
						$ri->delete(array('ID'=>$ri->fields['ID']));
					}
					break;
				case 1 : // Keep
				default : 
					// Not the same : set item as reservable
					if ($ID!=$newID){
						$input['device_type']=$type;
						$input['id_device']=$newID;
						$input['active']=$ri->fields['active'];
						$ri->add($input);
					}
					break;
			}
		}
	}

	function transferDevices($type,$ID,$newID){
		global $DB;

		switch ($this->options['keep_devices']){
			case 0 :  // delete
				if ($ID==$newID){ // Same item -> delete
					$query = "DELETE FROM glpi_computer_device WHERE (FK_computers = '$ID')";
					$result = $DB->query($query);
				}
				break;
			case 1 :	// Keep devices
			default : 
				// Copy datas if not the same 
				if ($ID!=$newID){
					$query = "SELECT * FROM glpi_computer_device WHERE (FK_computers = '$ID')";
					$result=$DB->query($query);
					if ($result = $DB->query($query)) {
						if ($DB->numrows($result)!=0) { 
							while ($data=$DB->fetch_row($result)) {
								compdevice_add($newID,$data['device_type'],$data['FK_device'],$data['specificity'],0);
							}
						}
					}
				}
				break;
		}
	}

	function transferNetworkLink($type,$ID,$newID){
		global $DB;
	
		$np=new Netport();
		// If not keep or disconnect

		$query = "SELECT ID FROM glpi_networking_ports WHERE (on_device = '$ID' AND device_type = '$type')";
		if ($result = $DB->query($query)) {
			if ($DB->numrows($result)!=0) { 
				switch ($this->options['keep_networklinks']){
					case 0 : // Delete netport
						// Not a copy -> delete
						if ($ID==$newID){
							while ($data=$DB->fetch_row($result)) {
								$np->delete(array('ID'=>$data['ID']));
							}
						}
						break;
					case 1 : // Disconnect
						if ($ID==$newID){ // Not a copy -> disconnect
							while ($data=$DB->fetch_row($result)) {
								removeConnector($data['ID']);
							}
						} else { // Item copy -> copy netpoints
							while ($data=$DB->fetch_row($result)) {
								unset($data['ID']);
								$data['on_device']=$newID;
								$np->add($data);
							}
						}
						break;
					case 2 : // Keep network links / update links if needed
					default : 
						// Copy datas if not the same 
						if ($ID!=$newID){
							while ($data=$DB->fetch_row($result)) {
								unset($data['ID']);
								$data['on_device']=$newID;
								$np->add($data);
							}
						}
						break;

				}
			}
		}
			}
}
?>
