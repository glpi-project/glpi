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

	// Already transfer item
	var $already_transfer=array();	
	// Items simulate to move
	var $needtobe_transfer=array();	
	// Search in need to be transfer items
	var $item_search=array();
	var $options=array();
	var $to=-1;
	var $inittype=0;
	function Transfer(){
		$this->table="glpi_transfer";
		$this->type=-1;
	}

	// Generic function
	function moveItems($items,$to,$options){
		// $items=array(TYPE => array(id_items))
		// $options=array()
		$this->to=$to;
		$this->options=$options;
		
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
		$ci=new CommonItem();

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
			
			// Simulate transfers To know which items need to be transfer
			$this->simulateTransfer($items);
			//printCleanArray($this->needtobe_transfer);
			// Computer first
			$this->inittype=COMPUTER_TYPE;
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
	* simulate the transfer to know which items need to be transfer
	*
	*@param $type device type to transfer
	*@param $ID ID of the item to transfer
	*
	*
	**/
	function simulateTransfer($items){
		global $DB;
		// Copy items to needtobe_transfer
		foreach ($items as $key => $tab){
			if (count($tab)){
				if (!isset($this->needtobe_transfer[$key])){
					$this->needtobe_transfer[$key]=array();
				}
				foreach ($tab as $ID){
					$this->needtobe_transfer[$key][$ID]=$ID;
				}
			}
		}

		// Computer first
		$this->item_search[COMPUTER_TYPE]=$this->createSearchConditionUsingArray($this->needtobe_transfer[COMPUTER_TYPE]);

		// DIRECT CONNECTIONS
		$DC_CONNECT=array();
		if ($this->options['keep_dc_monitor']){
			$DC_CONNECT[]=MONITOR_TYPE;
		}
		if ($this->options['keep_dc_phone']){
			$DC_CONNECT[]=PHONE_TYPE;
		}
		if ($this->options['keep_dc_peripheral']){
			$DC_CONNECT[]=PERIPHERAL_TYPE;
		}
		if ($this->options['keep_dc_printer']){
			$DC_CONNECT[]=PRINTER_TYPE;
		}
		if (count($DC_CONNECT)&&!empty($this->item_search[COMPUTER_TYPE])){
			foreach ($DC_CONNECT as $type){
				$query = "SELECT DISTINCT end1
				FROM glpi_connect_wire 
				WHERE type='".$type."' AND end2 IN ".$this->item_search[COMPUTER_TYPE];
				if ($result = $DB->query($query)) {
					if ($DB->numrows($result)>0) { 
						if (!isset($this->needtobe_transfer[$type])){
							$this->needtobe_transfer[$type]=array();
						}

						while ($data=$DB->fetch_array($result)){
							$this->needtobe_transfer[$type][$data['end1']]=$data['end1'];
						}
					}
				}
			}
		}
		// Licence / Software :  keep / delete + clean unused / keep unused 
	

		// Contract : keep / delete + clean unused / keep unused
		$CONTRACT=array(COMPUTER_TYPE,NETWORKING_TYPE,PRINTER_TYPE,MONITOR_TYPE,PERIPHERAL_TYPE,PHONE_TYPE,SOFTWARE_TYPE);


		// Document : keep / delete + clean unused / keep unused + duplicate file ?
		// Enterprise (depending of item link) / Contract - infocoms : keep / delete + clean unused / keep unused
		// Contact / Enterprise : keep / delete + clean unused / keep unused
	}
	function createSearchConditionUsingArray($array){
		$condition="";
		if (is_array($array)&&count($array)){
			$first=true;
			foreach ($array as $ID){
				if ($first){
					$first=false;
				} else {
					$condition.=",";
				}
				$condition.=$ID;
			}
			$condition="(".$condition.")";
		}
		return $condition;
	}

	/**
	* transfer an item to another item (may be the same) in the new entity
	*
	*@param $type device type to transfer
	*@param $ID ID of the item to transfer
	*@param $newID new ID of the ite
	*
	* Transfer item to a new Item if $ID==$newID : only update FK_entities field : $ID!=$new ID -> copy datas (like template system)
	*@return nothing (diplays)
	*
	**/
	function transferItem($type,$ID,$newID){
		global $CFG_GLPI;
		$cinew=new CommonItem();
		// Is already transfer ?
		if (!isset($this->already_transfer[$type][$ID])){
			// Check computer exists ?
			if ($cinew->getFromDB($type,$newID)){
				// Network connection ? keep connected / keep_disconnected / delete
				if (in_array($type,
					array(COMPUTER_TYPE,NETWORKING_TYPE,PRINTER_TYPE,MONITOR_TYPE,PERIPHERAL_TYPE,PHONE_TYPE))) {
					$this->transferNetworkLink($type,$ID,$newID);
				}
				// Device : keep / delete : network case : delete if net connection delete in ocs case
				if (in_array($type,array(COMPUTER_TYPE))){
					$this->transferDevices($type,$ID);
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
					$this->transferDirectConnection($type,$ID,PHONE_TYPE);
					$this->transferDirectConnection($type,$ID,PRINTER_TYPE);
				}
				// Computer Direct Connect : delete link if it is the initial transfer item (no recursion)
				if ($this->inittype==$type&&in_array($type,
					array(PRINTER_TYPE,MONITOR_TYPE,PERIPHERAL_TYPE,PHONE_TYPE))){
					$this->deleteDirectConnection($type,$ID);
				}
	
				// Licence / Software :  keep / delete + clean unused / keep unused 

				// Document : keep / delete + clean unused / keep unused + duplicate file ?

				// Contract : keep / delete + clean unused / keep unused
				if (in_array($type,
					array(COMPUTER_TYPE,NETWORKING_TYPE,PRINTER_TYPE,MONITOR_TYPE,PERIPHERAL_TYPE,PHONE_TYPE,SOFTWARE_TYPE))) {
//					$this->transferContract($type,$ID,$newID);
				}
				// Enterprise (depending of item link) / Contract - infocoms : keep / delete + clean unused / keep unused
	
				// Contact / Enterprise : keep / delete + clean unused / keep unused
	
				// Users ????

				// Manage entity dropdowns : location / netpoints

				// Update Ocs links 

				// Transfer Item
				$cinew->obj->update(array("ID"=>$newID,'FK_entities' => $this->to));
				$this->addToAlreadyTransfer($type,$ID,$newID);
			}
		}
	}
	function addToAlreadyTransfer($type,$ID,$newID){
		if (!isset($this->already_transfer[$type])){
			$this->already_transfer[$type]=array();
		}
		$this->already_transfer[$type][$ID]=$newID;
	}

	function transferDirectConnection($type,$ID,$link_type){
		global $DB,$LINK_ID_TABLE;
		// Only same Item case : no duplication of computers
		// Default : delete
		// TODO manage OCS links
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
		$query = "SELECT * 
			FROM glpi_connect_wire 
			WHERE end2='$ID' AND type='".$link_type."'";
		if ($result = $DB->query($query)) {
			if ($DB->numrows($result)!=0) { 
				// Foreach get item 
				while ($data=$DB->fetch_array($result)) {
					$item_ID=$data['end1'];
					if ($ci->getFromDB($link_type,$item_ID)) {
						// If global :
						if ($ci->obj->fields['is_global']==1){
							$need_clean_process=false;
							// if keep 
							if ($keep){
								$newID=-1;
								// Is already transfer ? 
								if ($this->already_transfer[$link_type][$item_ID]){
									$newID=$this->already_transfer[$link_type][$item_ID];
									// Already transfer as a copy : need clean process
									if ($this->already_transfer[$link_type][$item_ID]!=$item_ID){
										$need_clean_process=true;
									}
								} else { // Not yet tranfer
									// Can be managed like a non global one ? = all linked computers need to be transfer (so not copy)
									$query="SELECT ID FROM glpi_connect_wire WHERE type='".$link_type."' AND end1='$item_ID' AND end2 NOT IN ".$this->item_search[COMPUTER_TYPE];
									$result_search=$DB->query($query);
									// All linked computers need to be transfer -> use unique transfer system
									if ($DB->numrows($result_search)==0){
										$need_clean_process=false;
										$this->transferItem($link_type,$item_ID,$item_ID);
										$newID=$item_ID;
									} else { // else Transfer by Copy
										$need_clean_process=true;
										// Is existing global item in the destination entity ?
										$query="SELECT * FROM ".$LINK_ID_TABLE[$link_type]." WHERE is_global='1' AND FK_entities='".$this->to."' AND name='".addslashes($ci->getField['name'])."'";
										if ($result_search=$DB->query($query)){
											if ($DB->numrows($result_search)>0){
												$newID=$DB->result($result_search,0,'ID');
											}
										}
										// Not found -> transfer copy
										if ($newID<0){
											// 1 - create new item
											$input=$ci->obj->fields;
											$input['FK_entities']=$this->to;
											unset($input['ID']);
											$newID=$ci->obj->add($input);
											// 2 - transfer as copy
											$this->transferItem($link_type,$item_ID,$newID);
										}
										// Founded -> use to link : nothing to do
									}
								}
								// Finish updated link if needed
								if ($newID>0&&$newID!=$item_ID){
									$query = "UPDATE glpi_connect_wire 
									SET end1='$newID' WHERE ID = '".$data['ID']."' ";	
								}
							} else {
								// Else delete link
								$del_query="DELETE FROM glpi_connect_wire 
									WHERE ID = '".$data['ID']."'";
								$DB->query($del_query);
								$need_clean_process=true;
							}
							// If clean and not linked dc -> delete
							if ($need_clean_process&&$clean){
								$query = "SELECT * 
									FROM glpi_connect_wire 
									WHERE end1='$item_ID' AND type='".$link_type."'";
								if ($result_dc=$DB->query($query)){
									if ($DB->numrows($result_dc)==0){
										$ci->obj->delete(array('ID'=>$item_ID));
									}
								}
							}
						} else { // If unique : 
							//if keep -> transfer list else unlink
							if ($keep){
								$this->transferItem($link_type,$item_ID,$item_ID);
							} else {
								// Else delete link
								$del_query="DELETE FROM glpi_connect_wire 
									WHERE ID = '".$data['ID']."'";
								$DB->query($del_query);
								//if clean -> delete
								if ($clean){
									$ci->obj->delete(array('ID'=>$item_ID));
								}
							}
						}
					} else {
						// Unexisting item
						$del_query="DELETE FROM glpi_connect_wire 
							WHERE ID = '".$data['ID']."'";
						$DB->query($del_query);
					}
				}
			}
		}	
	}

	function deleteDirectConnection($type,$ID){
		global $DB;
		// Delete Direct connection to computers for item type 
		$query = "SELECT * 
			FROM glpi_connect_wire 
			WHERE end1 = '$ID' AND type = '".$type."'";
		$result = $DB->query($query);
	}

	function transferTickets($type,$ID,$newID){
		global $DB;
		$job= new Job();

		$query = "SELECT ID 
			FROM glpi_tracking 
			WHERE computer = '$ID' AND device_type = '$type'";
		if ($result = $DB->query($query)) {
			if ($DB->numrows($result)!=0) { 
				switch ($this->options['keep_tickets']){
					// Transfer
					case 2: 
						// Same Item / Copy Item -> update entity
						while ($data=$DB->fetch_array($result)) {
							$job->update(array("ID"=>$data['ID'],'FK_entities' => $this->to,'computer'=>$newID));
							$this->already_transfer[TRACKING_TYPE][$data['ID']]=$data['ID'];
						}
					break;
					// Clean ref : keep ticket but clean link
					case 1: 
						// Same Item / Copy Item : keep and clean ref
						while ($data=$DB->fetch_array($result)) {
							$job->update(array("ID"=>$data['ID'],'device_type' => 0, 'computer'=>0));
							$this->already_transfer[TRACKING_TYPE][$data['ID']]=$data['ID'];
						}
					break;
					// Delete
					case 0:
						// Same item -> delete
						if ($ID==$newID){
							while ($data=$DB->fetch_array($result)) {
								$job->delete(array('ID'=>$data['ID']));
							}
						}
						// Copy Item : nothing to do
					break;
				}
			}
		}

	}

	function transferHistory($type,$ID,$newID){
		global $DB;

		switch ($this->options['keep_history']){
			// delete
			case 0 :  
				// Same item -> delete
				if ($ID==$newID){ 
					$query = "DELETE FROM glpi_history 
						WHERE device_type = '$type' AND FK_glpi_device = '$ID'";
					$result = $DB->query($query);
				}
				// Copy -> nothing to do
				break;
			// Keep history
			case 1 :	
			default : 
				// Copy -> Copy datas 
				if ($ID!=$newID){
					$query = "SELECT * FROM glpi_history 
						WHERE device_type = '$type' AND FK_glpi_device = '$ID'";
					$result=$DB->query($query);
					if ($result = $DB->query($query)) {
						if ($DB->numrows($result)!=0) { 
							while ($data=$DB->fetch_array($result)) {
								$data = addslashes_deep($data);
								$query = "INSERT INTO glpi_history
								(FK_glpi_device, device_type, device_internal_type, linked_action, user_name, date_mod, id_search_option, old_value, new_value)  
								VALUES
								('$newID','$type','".$data['device_internal_type']."','".$data['linked_action']."','". $data['user_name']."', '".$data['date_mod']."', '".$data['id_search_option']."', '".$data['old_value']."', '".$data['new_value']."');";
								$DB->query($query);
							}
						}
					}
				}
				// Same item -> nothing to do
				break;
		}
	}

	function transferInfocoms($type,$ID,$newID){
		global $DB;

		$ic=new Infocom();
		if ($ic->getFromDBforDevice($type,$ID)){
			switch ($this->options['keep_infocoms']){
				// delete
				case 0 :  
					// Same item -> delete
					if ($ID==$newID){ 
						$query = "DELETE FROM glpi_infocoms 
							WHERE device_type = '$type' AND FK_device = '$ID'";
						$result = $DB->query($query);
					}
					// Copy : nothing to do
					break;
				// Keep
				case 1 : 
				default : 
					// Copy : copy infocoms
					if ($ID!=$newID){
						// Copy items
						$input=$ic->fields;
						$input['FK_device']=$newID;
						unset($input['ID']);
						$ic->add($input);
					}
					// Same item : nothing to do
					break;
			}
		}

	}

	function transferReservations($type,$ID,$newID){
		global $DB;

		$ri=new ReservationItem();

		if ($ri->getFromDBbyItem($type,$ID)){
			switch ($this->options['keep_reservations']){
				// delete
				case 0 :  
					// Same item -> delete
					if ($ID==$newID){ 
						$ri->delete(array('ID'=>$ri->fields['ID']));
					}
					// Copy : nothing to do
					break;
				// Keep
				case 1 : 
				default : 
					// Copy : set item as reservable
					if ($ID!=$newID){
						$input['device_type']=$type;
						$input['id_device']=$newID;
						$input['active']=$ri->fields['active'];
						$ri->add($input);
					}
					// Same item -> nothing to do
					break;
			}
		}
	}

	function transferDevices($type,$ID){
		global $DB;
		// Only same case because no duplication of computers
		switch ($this->options['keep_devices']){
			// delete devices
			case 0 :  
				$query = "DELETE FROM glpi_computer_device 
					WHERE FK_computers = '$ID'";
				$result = $DB->query($query);
				// TODO manage OCS links : Clean OCS link
				break;
			// Keep devices
			case 1 :	
			default : 
				// Same item -> nothing to do
				break;
		}
	}

	function transferNetworkLink($type,$ID,$newID){
		global $DB;
		// TODO manage dropdown_netpoint on copy netpoint
		// TODO manage OCS links
		$np=new Netport();

		$query = "SELECT ID 
			FROM glpi_networking_ports 
			WHERE on_device = '$ID' AND device_type = '$type'";
		if ($result = $DB->query($query)) {
			if ($DB->numrows($result)!=0) { 
				switch ($this->options['keep_networklinks']){
					// Delete netport
					case 0 : 
						// Not a copy -> delete
						if ($ID==$newID){
							while ($data=$DB->fetch_array($result)) {
								$np->delete(array('ID'=>$data['ID']));
							}
						}
						// Copy -> do nothing
						break;
					// Disconnect
					case 1 : 
						// Not a copy -> disconnect
						if ($ID==$newID){ 
							while ($data=$DB->fetch_array($result)) {
								removeConnector($data['ID']);
							}
						} else { // Copy -> copy netpoints
							while ($data=$DB->fetch_array($result)) {
								$data = addslashes_deep($data);
								unset($data['ID']);
								$data['on_device']=$newID;
								$np->add($data);
							}
						}
						break;
					// Keep network links 
					case 2 : 
					default : 
						// Copy -> Copy netpoints (do not keep links)
						if ($ID!=$newID){
							while ($data=$DB->fetch_array($result)) {
								unset($data['ID']);
								$data['on_device']=$newID;
								$np->add($data);
							}
						}
						// Not a copy -> nothing to do
						break;

				}
			}
		}
	}
}
?>
