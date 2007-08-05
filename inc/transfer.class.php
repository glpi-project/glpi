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
			printCleanArray($this->needtobe_transfer);
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

		// Init types :
		$types=array(COMPUTER_TYPE,NETWORKING_TYPE,PRINTER_TYPE,MONITOR_TYPE,PERIPHERAL_TYPE,PHONE_TYPE,SOFTWARE_TYPE);
		foreach ($types as $t){
			if (!isset($this->needtobe_transfer[$t])){
					$this->needtobe_transfer[$t]=array();
			}
		}

		// Copy items to needtobe_transfer
		foreach ($items as $key => $tab){
			if (count($tab)){
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
						while ($data=$DB->fetch_array($result)){
							$this->needtobe_transfer[$type][$data['end1']]=$data['end1'];
						}
					}
				}
			}
		}	
		$this->item_search[MONITOR_TYPE]=$this->createSearchConditionUsingArray($this->needtobe_transfer[MONITOR_TYPE]);
		$this->item_search[PHONE_TYPE]=$this->createSearchConditionUsingArray($this->needtobe_transfer[PHONE_TYPE]);
		$this->item_search[PERIPHERAL_TYPE]=$this->createSearchConditionUsingArray($this->needtobe_transfer[PERIPHERAL_TYPE]);
		$this->item_search[PRINTER_TYPE]=$this->createSearchConditionUsingArray($this->needtobe_transfer[PRINTER_TYPE]);

		// Licence / Software :  keep / delete + clean unused / keep unused 
		if ($this->options['keep_softwares']){
			if (!empty($this->item_search[COMPUTER_TYPE])){
				$query = "SELECT glpi_licenses.sID
					FROM glpi_inst_software 
					INNER  JOIN glpi_licenses ON (glpi_inst_software.license = glpi_licenses.ID)
					WHERE glpi_inst_software.cID IN ".$this->item_search[COMPUTER_TYPE];
				if ($result = $DB->query($query)) {
					if ($DB->numrows($result)>0) { 
						while ($data=$DB->fetch_array($result)){
							$this->needtobe_transfer[SOFTWARE_TYPE][$data['sID']]=$data['sID'];
						}
					}
				}
			}
		}

		$this->item_search[SOFTWARE_TYPE]=$this->createSearchConditionUsingArray($this->needtobe_transfer[SOFTWARE_TYPE]);

		$this->item_search[NETWORKING_TYPE]=$this->createSearchConditionUsingArray($this->needtobe_transfer[NETWORKING_TYPE]);

		// Contract : keep / delete + clean unused / keep unused
		$CONTRACT=array(COMPUTER_TYPE,NETWORKING_TYPE,PRINTER_TYPE,MONITOR_TYPE,PERIPHERAL_TYPE,PHONE_TYPE,SOFTWARE_TYPE);
		if ($this->options['keep_contracts']){
			foreach ($CONTRACT as $type){
				if (!empty($this->item_search[$type])){
					$query="SELECT FK_contract FROM glpi_contract_device
					WHERE device_type='$type' AND   FK_device IN ".$this->item_search[$type];
					if ($result = $DB->query($query)) {
						if ($DB->numrows($result)>0) { 
							if (!isset($this->needtobe_transfer[$type])){
								$this->needtobe_transfer[$type]=array();
							}
	
							while ($data=$DB->fetch_array($result)){
								$this->needtobe_transfer[CONTRACT_TYPE][$data['FK_contract']]=$data['FK_contract'];
							}
						}
					}
				}
			}
		}

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
			
				if ($type==COMPUTER_TYPE){
					// Monitor Direct Connect : keep / delete + clean unused / keep unused 
					$this->transferDirectConnection($type,$ID,MONITOR_TYPE);
					// Peripheral Direct Connect : keep / delete + clean unused / keep unused 
					$this->transferDirectConnection($type,$ID,PERIPHERAL_TYPE);
					// Phone Direct Connect : keep / delete + clean unused / keep unused 
					$this->transferDirectConnection($type,$ID,PHONE_TYPE);
					// Printer Direct Connect : keep / delete + clean unused / keep unused 
					$this->transferDirectConnection($type,$ID,PRINTER_TYPE);
					// Licence / Software :  keep / delete + clean unused / keep unused 
					$this->transferSoftwares($type,$ID);
				}
				// Computer Direct Connect : delete link if it is the initial transfer item (no recursion)
				if ($this->inittype==$type&&in_array($type,
					array(PRINTER_TYPE,MONITOR_TYPE,PERIPHERAL_TYPE,PHONE_TYPE))){
					$this->deleteDirectConnection($type,$ID);
				}
	

				// Document : keep / delete + clean unused / keep unused + duplicate file ?

				// Contract : keep / delete + clean unused / keep unused
				if (in_array($type,
					array(COMPUTER_TYPE,NETWORKING_TYPE,PRINTER_TYPE,MONITOR_TYPE,PERIPHERAL_TYPE,PHONE_TYPE,SOFTWARE_TYPE))) {
					$this->transferContract($type,$ID,$newID);
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

	function transferSoftwares($type,$ID){
		global $DB;
		// TODO Update OCS links

		// Get licenses linked
		$query = "SELECT glpi_licenses.sID as softID, glpi_licenses.ID as licID, glpi_inst_software.ID as instID
			FROM glpi_inst_software 
			LEFT JOIN glpi_licenses ON (glpi_inst_software.license = glpi_licenses.ID)
			WHERE glpi_inst_software.cID = '$ID'";
		if ($result = $DB->query($query)) {
			if ($DB->numrows($result)>0) { 
				$lic=new License();
				$soft=new Software();

				while ($data=$DB->fetch_array($result)){
					$need_clean_process=false;
					// Foreach licenses
					// if keep 
					if ($this->options['keep_softwares']&&$data['softID']>0&&$data['licID']>0&&$data['instID']>0){ 
						$newlicID=-1;
						// Already_transfer license
						if (isset($this->already_transfer[LICENSE_TYPE][$data['licID']])){
							// Copy license : update link in inst_software
							if ($this->already_transfer[LICENSE_TYPE][$data['licID']]!=$data['licID']){
								$newlicID=$this->already_transfer[LICENSE_TYPE][$data['licID']];
								$need_clean_process=true;
							} 
							// Same license : nothing to do
						} else {
						// Not already transfer license 
							$newsoftID=-1;
							// 1 - Search software destination ?
							// Already transfer soft : 
							if (isset($this->already_transfer[SOFTWARE_TYPE][$data['softID']])){
								$newsoftID=$this->already_transfer[SOFTWARE_TYPE][$data['softID']];
							} else {
								// Not already transfer soft
								$query="SELECT count(*) AS CPT 
									FROM glpi_inst_software INNER JOIN glpi_licenses ON (glpi_inst_software.license = glpi_licenses.ID)
									WHERE glpi_licenses.sID='".$data['softID']."' AND glpi_inst_software.cID NOT IN ".$this->item_search[COMPUTER_TYPE];
								$result_search=$DB->query($query);
								// Is the software will be completly transfer ?
								if ($DB->result($result_search,0,'CPT')==0){
									// Yes : transfer
									$need_clean_process=false;
									$this->transferItem(SOFTWARE_TYPE,$data['softID'],$data['softID']);
									$newsoftID=$data['softID'];
								} else {
									// No : copy software
									$need_clean_process=true;
									$soft->getFromDB($data['softID']);
									// Is existing software in the destination entity ?
									$query="SELECT * FROM glpi_software WHERE is_global='1' AND FK_entities='".$this->to."' AND name='".addslashes($soft->fields['name'])."'";
									if ($result_search=$DB->query($query)){
										if ($DB->numrows($result_search)>0){
											$newsoftID=$DB->result($result_search,0,'ID');
										}
									}
									// Not found -> transfer copy
									if ($newsoftID<0){
										// 1 - create new item
										unset($soft->fields['ID']);
										$input=$soft->fields;
										$input['FK_entities']=$this->to;
										unset($soft->fields);
										$newsoftID=$soft->add($input);
										// 2 - transfer as copy
										$this->transferItem(SOFTWARE_TYPE,$data['softID'],$newsoftID);
									}
									// Founded -> use to link : nothing to do
								}
							}
							// 2 - Transfer licence
							if ($newsoftID>0&&$newsoftID!=$data['softID']){
							// destination soft <> original soft -> copy soft
								$query="SELECT count(*) AS CPT 
									FROM glpi_inst_software 
									WHERE glpi_inst_software.license='".$data['licID']."' AND glpi_inst_software.cID NOT IN ".$this->item_search[COMPUTER_TYPE];
								$result_search=$DB->query($query);
								// Is the license will be completly transfer ?
								if ($DB->result($result_search,0,'CPT')==0){
									// Yes : transfer license to copy software
									$lic->update(array("ID"=>$data['licID'],'sID' => $newsoftID));
									$this->addToAlreadyTransfer(LICENSE_TYPE,$data['licID'],$data['licID']);
								} else {
									$lic->getFromDB($data['licID']);
									// No : Search licence
									$query="SELECT ID 
										FROM glpi_licenses WHERE sID='$newsoftID' AND  version='".addslashes($lic->fields['version'])."' AND serial='".addslashes($lic->fields['serial'])."'";
									if ($result_search=$DB->query($query)){
										if ($DB->numrows($result_search)>0){
											$newlicID=$DB->result($result_search,0,'ID');
										}
									}
									if ($newlicID<0){
										// Not found : copy license
										unset($lic->fields['ID']);
										$input=$lic->fields;
										unset($lic->fields);
										$input['sID']=$newsoftID;
										$newlicID=$lic->add($input);
									}
									$this->addToAlreadyTransfer(LICENSE_TYPE,$data['licID'],$newlicID);
									// Found : use it 
								}
							} 
							// else destination soft = original soft -> nothing to do / keep links
						}
						// Update inst software if needed
						if ($newlicID>0&&$newlicID!=$data['licID']){
							$query="UPDATE glpi_inst_software SET license='$newlicID' WHERE ID='".$data['instID']."'";
							$DB->query($query);	
						}
					} else { // Do not keep 
						// Delete inst software for computer
						$del_query="DELETE FROM glpi_inst_software 
							WHERE ID = '".$data['instID']."'";
						$DB->query($del_query);
						$need_clean_process=true;
					}
					// CLean process
					if ($need_clean_process&&$this->options['clean_softwares']){
						// Clean license
						$query2 = "SELECT COUNT(*) 
								FROM glpi_inst_software 
								WHERE license = '" . $data['licID'] . "'";
						$result2 = $DB->query($query2);
						if ($DB->result($result2, 0, 0) == 0) {
							$lic->delete(array (
								"ID" => $data['licID']
							));
						}
						// Clean software
						$query2 = "SELECT COUNT(*) 
								FROM glpi_licenses 
								WHERE sID = '" . $data['softID'] . "'";
						$result2 = $DB->query($query2);
						if ($DB->result($result2, 0, 0) == 0) {
							if ($this->options['clean_softwares']==1){ // delete
								$soft->delete(array ("ID" => $data['softID']));
							}
							if ($this->options['clean_softwares']==2){ // purge
								$soft->delete(array ("ID" => $data['softID']),1);
							}
						}
					}

				}
			}
		}
	}

	function transferContract($type,$ID,$newID){
		global $DB;
		$need_clean_process=false;

		// if keep 
		if ($this->options['keep_contracts']){
			$contract=new Contract();
			// Get contracts for the item
			$query="SELECT * FROM glpi_contract_device WHERE FK_device = '$ID' AND device_type = '$type'";
			if ($result = $DB->query($query)) {
				if ($DB->numrows($result)>0) { 
					// Foreach get item 
					while ($data=$DB->fetch_array($result)) {
						$need_clean_process=false;
						$item_ID=$data['FK_contract'];
						$newcontractID=-1;
						// is already transfer ?
						if (isset($this->already_transfer[CONTRACT_TYPE][$item_ID])){
							$newcontractID=$this->already_transfer[CONTRACT_TYPE][$item_ID];
							if ($newcontractID!=$item_ID){
								$need_clean_process=true;
							}
						} else {
							// No
							// Can be transfer without copy ? = all linked items need to be transfer (so not copy)
							$canbetransfer=true;
							$query="SELECT DISTINCT device_type FROM glpi_contract_device WHERE FK_contract=''";
							
							if ($result_type = $DB->query($query)) {
								if ($DB->numrows($result_type)>0) {
									while ($data_type=$DB->fetch_array($result_type)&&$canbetransfer) {
										$dtype=$data_type['device_type'];
										// No items to transfer -> exists links
										if (empty($this->item_search[$dtype])){
											$canbetransfer=false;
										} else {
											$query_search="SELECT count(*) AS CPT 
													FROM glpi_contract_device 
													WHERE FK_contract='$item_ID' AND device_type='$dtype' AND FK_device NOT IN ".$this->item_search[$dtype];
											$result_search = $DB->query($query_search);
											if ($DB->result($result_search,0,'CPT')>0){
												$canbetransfer=false;
											}
										}
									}
								}
							}
							// Yes : transfer 
							if ($canbetransfer){
								$this->transferItem(CONTRACT_TYPE,$item_ID,$item_ID);
								$newcontractID=$item_ID;
							} else {
								$need_clean_process=true;
								$contract->getFromDB($item_ID);
								// No : search contract
								$query="SELECT * FROM glpi_contract WHERE FK_entities='".$this->to."' AND name='".addslashes($contract->fields['name'])."'";
								if ($result_search=$DB->query($query)){
									if ($DB->numrows($result_search)>0){
										$newcontractID=$DB->result($result_search,0,'ID');
									}
								}
								// found : use it
								// not found : copy contract
								if ($newcontractID<0){
									// 1 - create new item
									unset($contract->fields['ID']);
									$input=$contract->fields;
									$input['FK_entities']=$this->to;
									unset($contract->fields);
									$newcontractID=$contract->add($input);
									// 2 - transfer as copy
									$this->transferItem(CONTRACT_TYPE,$item_ID,$newcontractID);
								}
								$this->addToAlreadyTransfer(CONTRACT_TYPE,$item_ID,$newcontractID);
							}
						}
						// Update links 
						if ($ID==$newID){
							if ($item_ID!=$newcontractID){
								$query="UPDATE glpi_contract_device SET FK_contract = '$newcontractID' WHERE ID='".$data['ID']."'";
								$DB->query($query);
							}
							// Same Item -> update links
						} else {
							// Copy Item -> copy links
							$query="INSERT INTO glpi_contract_device (FK_contract,FK_device,device_type) VALUES ('$newcontractID','$newID','$type')";
							$DB->query($query);
						}
					}
				}
			}
		} else {// else unlink
			$query="DELETE FROM glpi_contract_device WHERE FK_device = '$ID' AND device_type = '$type'";
			$DB->query($query);
		}

		// If clean and unused -> 
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
								if (isset($this->already_transfer[$link_type][$item_ID])){
									$newID=$this->already_transfer[$link_type][$item_ID];
									// Already transfer as a copy : need clean process
									if ($newID!=$item_ID){
										$need_clean_process=true;
									}
								} else { // Not yet tranfer
									// Can be managed like a non global one ? = all linked computers need to be transfer (so not copy)
									$query="SELECT count(*) AS CPT FROM glpi_connect_wire WHERE type='".$link_type."' AND end1='$item_ID' AND end2 NOT IN ".$this->item_search[COMPUTER_TYPE];
									$result_search=$DB->query($query);
									// All linked computers need to be transfer -> use unique transfer system
									if ($DB->result($result_search,0,'CPT')==0){
										
										$need_clean_process=false;
										$this->transferItem($link_type,$item_ID,$item_ID);
										$newID=$item_ID;
									} else { // else Transfer by Copy
										$need_clean_process=true;
										// Is existing global item in the destination entity ?
										$query="SELECT * FROM ".$LINK_ID_TABLE[$link_type]." WHERE is_global='1' AND FK_entities='".$this->to."' AND name='".addslashes($ci->getField('name'))."'";
										if ($result_search=$DB->query($query)){
											if ($DB->numrows($result_search)>0){
												$newID=$DB->result($result_search,0,'ID');
											}
										}
										// Not found -> transfer copy
										if ($newID<0){
											// 1 - create new item
											unset($ci->obj->fields['ID']);
											$input=$ci->obj->fields;
											$input['FK_entities']=$this->to;
											unset($ci->obj->fields);
											$newID=$ci->obj->add($input);
											// 2 - transfer as copy
											$this->transferItem($link_type,$item_ID,$newID);
										}
										$this->addToAlreadyTransfer($link_type,$item_ID,$newID);
										// Founded -> use to link : nothing to do
									}
								}
								// Finish updated link if needed
								if ($newID>0&&$newID!=$item_ID){
									$query = "UPDATE glpi_connect_wire 
									SET end1='$newID' WHERE ID = '".$data['ID']."' ";	
									$DB->query($query);
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
										if ($clean==1){
											$ci->obj->delete(array('ID'=>$item_ID));
										} 
										if ($clean==2) { // purge
											$ci->obj->delete(array('ID'=>$item_ID),1);
										}
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
								if ($clean==1){
									$ci->obj->delete(array('ID'=>$item_ID));
								}
								if ($clean==2){ // purge
									$ci->obj->delete(array('ID'=>$item_ID),1);
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
							$this->addToAlreadyTransfer(TRACKING_TYPE,$data['ID'],$data['ID']);
						}
					break;
					// Clean ref : keep ticket but clean link
					case 1: 
						// Same Item / Copy Item : keep and clean ref
						while ($data=$DB->fetch_array($result)) {
							$job->update(array("ID"=>$data['ID'],'device_type' => 0, 'computer'=>0));
							$this->addToAlreadyTransfer(TRACKING_TYPE,$data['ID'],$data['ID']);
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
						unset($ic->fields);
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
						unset($ri->fields);
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
								unset($np->fields);
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
								unset($np->fields);
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
