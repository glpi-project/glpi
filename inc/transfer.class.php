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
	var $INFOCOMS_TYPES = array(COMPUTER_TYPE, NETWORKING_TYPE, PRINTER_TYPE, MONITOR_TYPE, PERIPHERAL_TYPE, PHONE_TYPE, SOFTWARE_TYPE);
	var $CONTRACTS_TYPES = array(COMPUTER_TYPE, NETWORKING_TYPE, PRINTER_TYPE, MONITOR_TYPE, PERIPHERAL_TYPE, PHONE_TYPE, SOFTWARE_TYPE);
	var $TICKETS_TYPES = array(COMPUTER_TYPE, NETWORKING_TYPE, PRINTER_TYPE, MONITOR_TYPE, PERIPHERAL_TYPE, PHONE_TYPE, SOFTWARE_TYPE);
	var $DOCUMENTS_TYPES=array(ENTERPRISE_TYPE, CONTRACT_TYPE, CONTACT_TYPE, CONSUMABLE_TYPE, CARTRIDGE_TYPE, COMPUTER_TYPE, NETWORKING_TYPE, PRINTER_TYPE, MONITOR_TYPE, PERIPHERAL_TYPE, PHONE_TYPE, SOFTWARE_TYPE);
	function Transfer(){
		$this->table="glpi_transfers";
		$this->type=TRANSFER_TYPE;
	}

	// Generic function
	function moveItems($items,$to,$options){
		global $CFG_GLPI;
		// unset mailing
		$CFG_GLPI["mailing"]=0;
		
		// $items=array(TYPE => array(id_items))
		// $options=array()
		$this->to=$to;
		$this->options=$options;
		
		$default_options=array(
			'keep_tickets'=>0,
			'keep_networklinks'=>0,
			'keep_reservations'=>0,
			'keep_history'=>0,
			'keep_devices'=>0,
			'keep_infocoms'=>0,

			'keep_dc_monitor'=>0,
			'clean_dc_monitor'=>0,
			'keep_dc_phone'=>0,
			'clean_dc_phone'=>0,
			'keep_dc_peripheral'=>0,
			'clean_dc_peripheral'=>0,
			'keep_dc_printer'=>0,
			'clean_dc_printer'=>0,

			'keep_enterprises'=>0,
			'clean_enterprises'=>0,
			'keep_contacts'=>0,
			'clean_contacts'=>0,

			'keep_contracts'=>0,
			'clean_contracts'=>0,

			'keep_softwares'=>0,
			'clean_softwares'=>0,

			'keep_documents'=>0,
			'clean_documents'=>0,
			
			'keep_cartridges_type' =>0,
			'clean_cartridges_type' =>0,
			'keep_cartridges' =>0,

			'keep_consumables' =>0,
		);
		$ci=new CommonItem();

		if ($this->to>=0){
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
			$INVENTORY_TYPES = array(NETWORKING_TYPE, PRINTER_TYPE, MONITOR_TYPE, PERIPHERAL_TYPE, PHONE_TYPE, SOFTWARE_TYPE, CARTRIDGE_TYPE, CONSUMABLE_TYPE);
			foreach ($INVENTORY_TYPES as $type){
				$this->inittype=$type;
				if (isset($items[$type])&&count($items[$type])){
					foreach ($items[$type] as $ID){
						$this->transferItem($type,$ID,$ID);
					}
				}
			}
			// Management Items
			$MANAGEMENT_TYPES = array(ENTERPRISE_TYPE, CONTRACT_TYPE, CONTACT_TYPE, DOCUMENT_TYPE);
			foreach ($MANAGEMENT_TYPES as $type){
				$this->inittype=$type;
				if (isset($items[$type])&&count($items[$type])){
					foreach ($items[$type] as $ID){
						$this->transferItem($type,$ID,$ID);
					}
				}
			}
			// Tickets
			$OTHER_TYPES = array(TRACKING_TYPE);
			foreach ($OTHER_TYPES as $type){
				$this->inittype=$type;
				if (isset($items[$type])&&count($items[$type])){
					foreach ($items[$type] as $ID){
						$this->transferItem($type,$ID,$ID);
					}
				}
			}

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
		$types=array(COMPUTER_TYPE,NETWORKING_TYPE,PRINTER_TYPE,MONITOR_TYPE,PERIPHERAL_TYPE,PHONE_TYPE,SOFTWARE_TYPE,CONTRACT_TYPE,ENTERPRISE_TYPE,CONTACT_TYPE,TRACKING_TYPE,DOCUMENT_TYPE,CARTRIDGE_TYPE, CONSUMABLE_TYPE);
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
		if (count($DC_CONNECT)){
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

		$this->item_search[SOFTWARE_TYPE]=$this->createSearchConditionUsingArray($this->needtobe_transfer[SOFTWARE_TYPE]);

		$this->item_search[NETWORKING_TYPE]=$this->createSearchConditionUsingArray($this->needtobe_transfer[NETWORKING_TYPE]);

		// Tickets
		if ($this->options['keep_tickets']){
			foreach ($this->TICKETS_TYPES as $type)
			if(isset($this->item_search[$type])){
				$query="SELECT DISTINCT ID FROM glpi_tracking
				WHERE device_type='$type' AND computer IN ".$this->item_search[$type];
				if ($result = $DB->query($query)) {
					if ($DB->numrows($result)>0) { 
						if (!isset($this->needtobe_transfer[TRACKING_TYPE])){
							$this->needtobe_transfer[TRACKING_TYPE]=array();
						}
						while ($data=$DB->fetch_array($result)){
							$this->needtobe_transfer[TRACKING_TYPE][$data['ID']]=$data['ID'];
						}
					}
				}
			}
		}	
		$this->item_search[TRACKING_TYPE]=$this->createSearchConditionUsingArray($this->needtobe_transfer[TRACKING_TYPE]);

		// Contract : keep / delete + clean unused / keep unused
		if ($this->options['keep_contracts']){
			foreach ($this->CONTRACTS_TYPES as $type)
			if (isset($this->item_search[$type])){
				$query="SELECT FK_contract FROM glpi_contract_device
				WHERE device_type='$type' AND   FK_device IN ".$this->item_search[$type];
				if ($result = $DB->query($query)) {
					if ($DB->numrows($result)>0) { 
						if (!isset($this->needtobe_transfer[CONTRACT_TYPE])){
							$this->needtobe_transfer[CONTRACT_TYPE]=array();
 						}
						while ($data=$DB->fetch_array($result)){
							$this->needtobe_transfer[CONTRACT_TYPE][$data['FK_contract']]=$data['FK_contract'];
						}
					}
				}
			}
		}
		$this->item_search[CONTRACT_TYPE]=$this->createSearchConditionUsingArray($this->needtobe_transfer[CONTRACT_TYPE]);
		// Enterprise (depending of item link) / Contract - infocoms : keep / delete + clean unused / keep unused
		
		if ($this->options['keep_enterprises']){
			// Enterprise Contract
			$query="SELECT DISTINCT FK_enterprise FROM glpi_contract_enterprise WHERE FK_contract IN ".$this->item_search[CONTRACT_TYPE];
			if ($result = $DB->query($query)) {
				if ($DB->numrows($result)>0) { 
					if (!isset($this->needtobe_transfer[ENTERPRISE_TYPE])){
						$this->needtobe_transfer[ENTERPRISE_TYPE]=array();
					}
					while ($data=$DB->fetch_array($result)){
						$this->needtobe_transfer[ENTERPRISE_TYPE][$data['FK_enterprise']]=$data['FK_enterprise'];
					}
				}
			}
			// Ticket Contract
			$query="SELECT DISTINCT assign_ent FROM glpi_tracking WHERE assign_ent > 0 AND ID IN ".$this->item_search[TRACKING_TYPE];
			if ($result = $DB->query($query)) {
				if ($DB->numrows($result)>0) { 
					if (!isset($this->needtobe_transfer[ENTERPRISE_TYPE])){
						$this->needtobe_transfer[ENTERPRISE_TYPE]=array();
					}
					while ($data=$DB->fetch_array($result)){
						$this->needtobe_transfer[ENTERPRISE_TYPE][$data['assign_ent']]=$data['assign_ent'];
					}
				}
			}
			// Enterprise infocoms
			if ($this->options['keep_infocoms']){
				foreach ($this->INFOCOMS_TYPES as $type)
				if (isset($this->item_search[$type])){
					$query="SELECT DISTINCT FK_enterprise FROM glpi_infocoms
					WHERE FK_enterprise > 0 AND device_type='$type' AND FK_device IN ".$this->item_search[$type];
					if ($result = $DB->query($query)) {
						if ($DB->numrows($result)>0) { 
							if (!isset($this->needtobe_transfer[ENTERPRISE_TYPE])){
								$this->needtobe_transfer[ENTERPRISE_TYPE]=array();
							}
							while ($data=$DB->fetch_array($result)){
								$this->needtobe_transfer[ENTERPRISE_TYPE][$data['FK_enterprise']]=$data['FK_enterprise'];
							}
						}
					}
				}
			}
		}
		$this->item_search[ENTERPRISE_TYPE]=$this->createSearchConditionUsingArray($this->needtobe_transfer[ENTERPRISE_TYPE]);

		// Contact / Enterprise : keep / delete + clean unused / keep unused
		if ($this->options['keep_contacts']){
			// Enterprise Contact
			$query="SELECT DISTINCT FK_contact FROM glpi_contact_enterprise WHERE FK_enterprise IN ".$this->item_search[ENTERPRISE_TYPE];
			if ($result = $DB->query($query)) {
				if ($DB->numrows($result)>0) { 
					if (!isset($this->needtobe_transfer[CONTACT_TYPE])){
						$this->needtobe_transfer[CONTACT_TYPE]=array();
					}
					while ($data=$DB->fetch_array($result)){
						$this->needtobe_transfer[CONTACT_TYPE][$data['FK_contact']]=$data['FK_contact'];
					}
				}
			}
		}

		// Document : keep / delete + clean unused / keep unused
		if ($this->options['keep_documents']){
			foreach ($this->DOCUMENTS_TYPES as $type)
			if (isset($this->item_search[$type])){
				$query="SELECT FK_doc FROM glpi_doc_device
				WHERE device_type='$type' AND FK_device IN ".$this->item_search[$type];
				if ($result = $DB->query($query)) {
					if ($DB->numrows($result)>0) { 
						if (!isset($this->needtobe_transfer[DOCUMENT_TYPE])){
							$this->needtobe_transfer[DOCUMENT_TYPE]=array();
 						}
						while ($data=$DB->fetch_array($result)){
							$this->needtobe_transfer[DOCUMENT_TYPE][$data['FK_doc']]=$data['FK_doc'];
						}
					}
				}
			}
		}
		$this->item_search[DOCUMENT_TYPE]=$this->createSearchConditionUsingArray($this->needtobe_transfer[DOCUMENT_TYPE]);

		// printer -> cartridges : keep / delete + clean
		if ($this->options['keep_cartridges_type']){
			if (isset($this->item_search[PRINTER_TYPE])){
				$query="SELECT FK_glpi_cartridges_type FROM glpi_cartridges
				WHERE FK_glpi_printers IN ".$this->item_search[PRINTER_TYPE];
				if ($result = $DB->query($query)) {
					if ($DB->numrows($result)>0) { 
						if (!isset($this->needtobe_transfer[CARTRIDGE_TYPE])){
							$this->needtobe_transfer[CARTRIDGE_TYPE]=array();
 						}
						while ($data=$DB->fetch_array($result)){
							$this->needtobe_transfer[CARTRIDGE_TYPE][$data['FK_glpi_cartridges_type']]=$data['FK_glpi_cartridges_type'];
						}
					}
				}
			}
		}
		$this->item_search[CARTRIDGE_TYPE]=$this->createSearchConditionUsingArray($this->needtobe_transfer[CARTRIDGE_TYPE]);
	}
	function createSearchConditionUsingArray($array){
		if (is_array($array)&&count($array)){
			$condition="";
			$first=true;
			foreach ($array as $ID){
				if ($first){
					$first=false;
				} else {
					$condition.=",";
				}
				$condition.=$ID;
			}
			return "(".$condition.")";
		} else {
			return "(-1)";
		}
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
		global $CFG_GLPI,$DB;
		
		$cinew=new CommonItem();
		// Is already transfer ?
		if (!isset($this->already_transfer[$type][$ID])){
			// Check computer exists ?
			if ($cinew->getFromDB($type,$newID)){

				// Manage Ocs links 
				$dataocslink=array();
				$ocs_computer=false;
				if ($type==COMPUTER_TYPE&&$CFG_GLPI['ocs_mode']){
					$query="SELECT * FROM glpi_ocs_link WHERE glpi_id='$ID'";
					if ($result=$DB->query($query)){
						if ($DB->numrows($result)>0){
							$dataocslink=$DB->fetch_assoc($result);
							$ocs_computer=true;
						}
					}
					
				}

				// Network connection ? keep connected / keep_disconnected / delete
				if (in_array($type,
					array(COMPUTER_TYPE,NETWORKING_TYPE,PRINTER_TYPE,MONITOR_TYPE,PERIPHERAL_TYPE,PHONE_TYPE))) {
					$this->transferNetworkLink($type,$ID,$newID,$ocs_computer);
				}
				// Device : keep / delete : network case : delete if net connection delete in ocs case
				if (in_array($type,array(COMPUTER_TYPE))){
					$this->transferDevices($type,$ID,$ocs_computer);
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
				if (in_array($type,$this->INFOCOMS_TYPES)) {
					$this->transferInfocoms($type,$ID,$newID);
				}
			
				if ($type==COMPUTER_TYPE){
					// Monitor Direct Connect : keep / delete + clean unused / keep unused 
					$this->transferDirectConnection($type,$ID,MONITOR_TYPE,$ocs_computer);
					// Peripheral Direct Connect : keep / delete + clean unused / keep unused 
					$this->transferDirectConnection($type,$ID,PERIPHERAL_TYPE,$ocs_computer);
					// Phone Direct Connect : keep / delete + clean unused / keep unused 
					$this->transferDirectConnection($type,$ID,PHONE_TYPE);
					// Printer Direct Connect : keep / delete + clean unused / keep unused 
					$this->transferDirectConnection($type,$ID,PRINTER_TYPE,$ocs_computer);
					// Licence / Software :  keep / delete + clean unused / keep unused 
					$this->transferSoftwares($type,$ID,$ocs_computer);
				}
				// Computer Direct Connect : delete link if it is the initial transfer item (no recursion)
				if ($this->inittype==$type&&in_array($type,
					array(PRINTER_TYPE,MONITOR_TYPE,PERIPHERAL_TYPE,PHONE_TYPE))){
					$this->deleteDirectConnection($type,$ID);
				}

				// Contract : keep / delete + clean unused / keep unused
				if (in_array($type,$this->CONTRACTS_TYPES)) {
					$this->transferContracts($type,$ID,$newID);
				}

				// Contact / Enterprise : keep / delete + clean unused / keep unused
				if ($type==ENTERPRISE_TYPE){
					$this->transferEnterpriseContacts($ID,$newID);
				}

				// Document : keep / delete + clean unused / keep unused
				if (in_array($type,$this->DOCUMENTS_TYPES)) {
					$this->transferDocuments($type,$ID,$newID);
				}

				// transfer compatible printers
				if ($type==CARTRIDGE_TYPE) {
					$this->transferCompatiblePrinters($ID,$newID);
				}
				// Cartridges  and cartridges type linked to printer
				if ($type==PRINTER_TYPE) {
					$this->transferPrinterCartridges($ID,$newID);
				}
				// TODO Init transfer of contract / docs / software : check unused : if not ? what to do ?
				if ($this->inittype==$type&&$type==DOCUMENT_TYPE&&$ID==$newID) {
				
				}
				// TODO Cartridges of cartridges types
				if ($this->inittype==$type&&$type==CARTRIDGE_TYPE) {
				}

				// TODO Users ???? : Update right to new entity ?
				// TODO Linked Users ???? : Update right to new entity ?
				
				
				// Transfer Item
				$input=array("ID"=>$newID,'FK_entities' => $this->to);
				// Manage Location dropdown
				if (isset($cinew->obj->fields['location'])){
					$input['location']=$this->transferDropdownLocation($cinew->obj->fields['location']);
				}
				// Transfer Document file if exists (not to do if same entity) / Only for copy document
				if ($type==DOCUMENT_TYPE&&$ID!=$newID
					&&!empty($cinew->obj->fields['filename'])
					&&$cinew->obj->fields['FK_entities']!=$this->to
				){
					$input['filename']=$this->transferDocumentFile($cinew->obj->fields['filename']);
				}

				$cinew->obj->update($input);
				$this->addToAlreadyTransfer($type,$ID,$newID);
				doHook("item_transfer",array("type"=>$type, "ID" => $ID, "newID"=>$newID));
			}
		}
	}
	function addToAlreadyTransfer($type,$ID,$newID){
		if (!isset($this->already_transfer[$type])){
			$this->already_transfer[$type]=array();
		}
		$this->already_transfer[$type][$ID]=$newID;
	}
	function transferDocumentFile($filename){
		if (is_file(GLPI_DOC_DIR."/".$filename)){
			$splitter=split("/",$filename);
			if (count($splitter)==2){
				$dir=$splitter[0];
				$file=$splitter[1];
				// Save message
				$tmp=$_SESSION["MESSAGE_AFTER_REDIRECT"];
				$new_path=getUploadFileValidLocationName($dir,$file,0);
				// Restore message
				$_SESSION["MESSAGE_AFTER_REDIRECT"]=$tmp;
				if (copy(GLPI_DOC_DIR."/".$filename,GLPI_DOC_DIR."/".$new_path)){
					return $new_path;
				}
			} 
		}
		return "";
	}

	function transferDropdownLocation($locID){
		global $DB;

		if ($locID>0){
			if (isset($this->already_transfer['location'][$locID])){
				return $this->already_transfer['location'][$locID];
			} else { // Not already transfer
				// Search init item
				$query="SELECT * FROM glpi_dropdown_locations WHERE ID='$locID'";
				if ($result=$DB->query($query)){
					if ($DB->numrows($result)){
						$data=$DB->fetch_array($result);
						$data=addslashes_deep($data);
						// Search if the location already exists in the destination entity
							$query="SELECT ID FROM glpi_dropdown_locations WHERE FK_entities='".$this->to."' AND completename='".$data['completename']."'";	
							if ($result_search=$DB->query($query)){
								// Found : -> use it
								if ($DB->numrows($result_search)>0){
									$newID=$DB->result($result_search,0,'ID');
									$this->addToAlreadyTransfer('location',$locID,$newID);
									return $newID;
								}
							}
							// Not found : 
							$input=array();
							$input['tablename']='glpi_dropdown_locations';
							$input['FK_entities']=$this->to;
							$input['value']=$data['name'];
							$input['comments']=$data['comments'];
							$input['type']="under";
							$input['value2']=0; // parentID
							// if parentID>0 : transfer parent ID
							if ($data['parentID']>0){
								$input['value2']=$this->transferDropdownLocation($data['parentID']);
							}
							// add item
							$newID=addDropdown($input);
							$this->addToAlreadyTransfer('location',$locID,$newID);
							return $newID;
					} 
				}
			}
		}
		return 0;
	}
	
	function transferDropdownNetpoint($netID){
		global $DB;

		if ($netID>0){
			if (isset($this->already_transfer['netpoint'][$netID])){
				return $this->already_transfer['netpoint'][$netID];
			} else { // Not already transfer
				// Search init item
				$query="SELECT * FROM glpi_dropdown_netpoint WHERE ID='$netID'";
				if ($result=$DB->query($query)){
					if ($DB->numrows($result)){
						$data=$DB->fetch_array($result);
						$data=addslashes_deep($data);
						$locID=$this->transferDropdownLocation($data['location']);
						// Search if the location already exists in the destination entity
							$query="SELECT ID FROM glpi_dropdown_netpoint WHERE FK_entities='".$this->to."' AND name='".$data['name']."' AND location='$locID'";	
							if ($result_search=$DB->query($query)){
								// Found : -> use it
								if ($DB->numrows($result_search)>0){
									$newID=$DB->result($result_search,0,'ID');
									$this->addToAlreadyTransfer('netpoint',$netID,$newID);
									return $newID;
								}
							}
							// Not found : 
							$input=array();
							$input['tablename']='glpi_dropdown_netpoint';
							$input['FK_entities']=$this->to;
							$input['value']=$data['name'];
							$input['comments']=$data['comments'];
							$input['type']="under";
							$input['value2']=$locID; 
							// add item
							$newID=addDropdown($input);
							$this->addToAlreadyTransfer('netpoint',$netID,$newID);
							return $newID;
					} 
				}
			}
		}
		return 0;
	}	
	
	function transferPrinterCartridges($ID,$newID){
		global $DB;
		
		// Get cartrdiges linked
		$query = "SELECT *
			FROM glpi_cartridges 
			WHERE glpi_cartridges.FK_glpi_printers = '$ID'";
		if ($result = $DB->query($query)) {
			if ($DB->numrows($result)>0) { 
				$cart=new Cartridge();
				$carttype=new CartridgeType();

				while ($data=$DB->fetch_array($result)){
					$need_clean_process=false;
					// Foreach cartridges
					// if keep 
					if ($this->options['keep_cartridges_type']){ 
						$newcartID=-1;
						$newcarttypeID=-1;
						// 1 - Search carttype destination ?
						// Already transfer carttype : 
						if (isset($this->already_transfer[CARTRIDGE_TYPE][$data['FK_glpi_cartridges_type']])){
							$newcarttypeID=$this->already_transfer[CARTRIDGE_TYPE][$data['FK_glpi_cartridges_type']];
						} else {
							// Not already transfer cartype
							$query="SELECT count(*) AS CPT 
								FROM glpi_cartridges
								WHERE glpi_cartridges.FK_glpi_cartridges_type='".$data['FK_glpi_cartridges_type']."' 
								AND glpi_cartridges.FK_glpi_printers > 0 AND glpi_cartridges.FK_glpi_printers NOT IN ".$this->item_search[PRINTER_TYPE];
							$result_search=$DB->query($query);
							// Is the carttype will be completly transfer ?
							if ($DB->result($result_search,0,'CPT')==0){
								// Yes : transfer
								$need_clean_process=false;
								$this->transferItem(CARTRIDGE_TYPE,$data['FK_glpi_cartridges_type'],$data['FK_glpi_cartridges_type']);
								$newcarttypeID=$data['FK_glpi_cartridges_type'];
							} else {
								// No : copy carttype
								$need_clean_process=true;
								$carttype->getFromDB($data['FK_glpi_cartridges_type']);
								// Is existing carttype in the destination entity ?
								$query="SELECT * FROM glpi_cartridges_type WHERE FK_entities='".$this->to."' AND name='".addslashes($carttype->fields['name'])."'";
								if ($result_search=$DB->query($query)){
									if ($DB->numrows($result_search)>0){
										$newcarttypeID=$DB->result($result_search,0,'ID');
									}
								}
								// Not found -> transfer copy
								if ($newcarttypeID<0){
									// 1 - create new item
									unset($carttype->fields['ID']);
									$input=$carttype->fields;
									$input['FK_entities']=$this->to;
									unset($carttype->fields);
									$newcarttypeID=$carttype->add($input);
									// 2 - transfer as copy
									$this->transferItem(CARTRIDGE_TYPE,$data['FK_glpi_cartridges_type'],$newcarttypeID);
								}
								// Founded -> use to link : nothing to do
							}
						}
						
						// Update cartridge if needed
						if ($newcarttypeID>0&&$newcarttypeID!=$data['FK_glpi_cartridges_type']){
							$cart->update(array("ID"=>$data['ID'],'FK_glpi_cartridges_type' => $newcarttypeID));		
						}
					} else { // Do not keep 
						// If same printer : delete cartridges
						if ($ID==$newID){
							$del_query="DELETE FROM glpi_cartridges 
								WHERE FK_glpi_printers = '$ID'";
							$DB->query($del_query);
						}
						$need_clean_process=true;
					}
					// CLean process
					if ($need_clean_process&&$this->options['clean_cartridges_type']){
						// Clean carttype
						$query2 = "SELECT COUNT(*) AS CPT
								FROM glpi_cartridges 
								WHERE FK_glpi_cartridges_type = '" . $data['FK_glpi_cartridges_type'] . "'";
						$result2 = $DB->query($query2);
						if ($DB->result($result2, 0, 'CPT') == 0) {
							if ($this->options['clean_cartridges_type']==1){ // delete
								$carttype->delete(array ("ID" => $data['FK_glpi_cartridges_type']));
							}
							if ($this->options['clean_cartridges_type']==2){ // purge
								$carttype->delete(array ("ID" => $data['FK_glpi_cartridges_type']),1);
							}
						}
					}

				}
			}
		}
	
	}
	
	function transferSoftwares($type,$ID,$ocs_computer=false){
		global $DB;
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
									$query="SELECT * FROM glpi_software WHERE FK_entities='".$this->to."' AND name='".addslashes($soft->fields['name'])."'";
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
						if ($ocs_computer){
							$query="UPDATE glpi_ocs_link SET import_software = NULL WHERE glpi_id='$ID'";
							$DB->query($query);
						}
					}
					// CLean process
					if ($need_clean_process&&$this->options['clean_softwares']){
						// Clean license
						$query2 = "SELECT COUNT(*) AS CPT
								FROM glpi_inst_software 
								WHERE license = '" . $data['licID'] . "'";
						$result2 = $DB->query($query2);
						if ($DB->result($result2, 0, 'CPT') == 0) {
							$lic->delete(array (
								"ID" => $data['licID']
							));
						}
						// Clean software
						$query2 = "SELECT COUNT(*) AS CPT
								FROM glpi_licenses 
								WHERE sID = '" . $data['softID'] . "'";
						$result2 = $DB->query($query2);
						if ($DB->result($result2, 0, 'CPT') == 0) {
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

	function transferContracts($type,$ID,$newID){
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
							$query="SELECT DISTINCT device_type FROM glpi_contract_device WHERE FK_contract='$item_ID'";
							
							if ($result_type = $DB->query($query)) {
								if ($DB->numrows($result_type)>0) {
									while ($data_type=$DB->fetch_array($result_type)) 
									if ($canbetransfer) {
										$dtype=$data_type['device_type'];
										// No items to transfer -> exists links
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
							// Yes : transfer 
							if ($canbetransfer){
								$this->transferItem(CONTRACT_TYPE,$item_ID,$item_ID);
								$newcontractID=$item_ID;
							} else {
								$need_clean_process=true;
								$contract->getFromDB($item_ID);
								// No : search contract
								$query="SELECT * FROM glpi_contracts WHERE FK_entities='".$this->to."' AND name='".addslashes($contract->fields['name'])."'";
								if ($result_search=$DB->query($query)){
									if ($DB->numrows($result_search)>0){
										$newcontractID=$DB->result($result_search,0,'ID');
										$this->addToAlreadyTransfer(CONTRACT_TYPE,$item_ID,$newcontractID);
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
							if ($item_ID!=$newcontractID){
								$query="INSERT INTO glpi_contract_device (FK_contract,FK_device,device_type) VALUES ('$newcontractID','$newID','$type')";
								$DB->query($query);
							} else { // same contract for new item update link
								$query="UPDATE glpi_contract_device SET FK_device = '$newID' WHERE ID='".$data['ID']."'";
								$DB->query($query);
							}
						}
						// If clean and unused -> 
						if ($need_clean_process&&$this->options['clean_contracts']){
							$query = "SELECT COUNT(*) AS CPT 
								FROM glpi_contract_device 
								WHERE FK_contract='$item_ID'";
							if ($result_remaining=$DB->query($query)){
								if ($DB->result($result_remaining,0,'CPT')==0){
									if ($clean==1){
										$contract->delete(array('ID'=>$item_ID));
									} 
									if ($clean==2) { // purge
										$contract->delete(array('ID'=>$item_ID),1);
									}
								}
							}
						}
					}
				}
			}
		} else {// else unlink
			$query="DELETE FROM glpi_contract_device WHERE FK_device = '$ID' AND device_type = '$type'";
			$DB->query($query);
		}

	}

	function transferDocuments($type,$ID,$newID){
		global $DB;
		$need_clean_process=false;

		// if keep 
		if ($this->options['keep_documents']){
			$document=new Document();
			// Get contracts for the item
			$query="SELECT * FROM glpi_doc_device WHERE FK_device = '$ID' AND device_type = '$type'";
			if ($result = $DB->query($query)) {
				if ($DB->numrows($result)>0) { 
					// Foreach get item 
					while ($data=$DB->fetch_array($result)) {
						$need_clean_process=false;
						$item_ID=$data['FK_doc'];
						$newdocID=-1;
						// is already transfer ?
						if (isset($this->already_transfer[DOCUMENT_TYPE][$item_ID])){
							$newdocID=$this->already_transfer[DOCUMENT_TYPE][$item_ID];
							if ($newdocID!=$item_ID){
								$need_clean_process=true;
							}
						} else {
							// No
							// Can be transfer without copy ? = all linked items need to be transfer (so not copy)
							$canbetransfer=true;
							$query="SELECT DISTINCT device_type FROM glpi_doc_device WHERE FK_doc='$item_ID'";
							
							if ($result_type = $DB->query($query)) {
								if ($DB->numrows($result_type)>0) {
									while ($data_type=$DB->fetch_array($result_type)) {
										$dtype=$data_type['device_type'];
										if (isset($this->item_search[$dtype])&&$canbetransfer) {
											// No items to transfer -> exists links
											$query_search="SELECT count(*) AS CPT 
													FROM glpi_doc_device 
													WHERE FK_doc='$item_ID' AND device_type='$dtype' AND FK_device NOT IN ".$this->item_search[$dtype];
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
								$this->transferItem(DOCUMENT_TYPE,$item_ID,$item_ID);
								$newdocID=$item_ID;
							} else {
								$need_clean_process=true;
								$document->getFromDB($item_ID);
								// No : search contract
								$query="SELECT * FROM glpi_docs WHERE FK_entities='".$this->to."' AND name='".addslashes($document->fields['name'])."'";
								if ($result_search=$DB->query($query)){
									if ($DB->numrows($result_search)>0){
										$newdocID=$DB->result($result_search,0,'ID');
										$this->addToAlreadyTransfer(DOCUMENT_TYPE,$item_ID,$newdocID);
									}
								}
								// found : use it
								// not found : copy contract
								if ($newdocID<0){
									// 1 - create new item
									unset($document->fields['ID']);
									$input=$document->fields;
									$input['FK_entities']=$this->to;
									unset($document->fields);
									$newdocID=$document->add($input);
									// 2 - transfer as copy
									$this->transferItem(DOCUMENT_TYPE,$item_ID,$newdocID);
								}
							}
						}
						// Update links 
						if ($ID==$newID){
							if ($item_ID!=$newdocID){
								$query="UPDATE glpi_doc_device SET FK_doc = '$newdocID' WHERE ID='".$data['ID']."'";
								$DB->query($query);
							}
							// Same Item -> update links
						} else {
							// Copy Item -> copy links
							if ($item_ID!=$newdocID){
								$query="INSERT INTO glpi_doc_device (FK_doc,FK_device,device_type) VALUES ('$newdocID','$newID','$type')";
								$DB->query($query);
							} else { // same contract for new item update link
								$query="UPDATE glpi_doc_device SET FK_device = '$newID' WHERE ID='".$data['ID']."'";
								$DB->query($query);
							}
						}
						// If clean and unused -> 
						if ($need_clean_process&&$this->options['clean_documents']){
							$query = "SELECT COUNT(*) AS CPT 
								FROM glpi_doc_device 
								WHERE FK_doc='$item_ID'";
							if ($result_remaining=$DB->query($query)){
								if ($DB->result($result_remaining,0,'CPT')==0){
									if ($clean==1){
										$document->delete(array('ID'=>$item_ID));
									} 
									if ($clean==2) { // purge
										$document->delete(array('ID'=>$item_ID),1);
									}
								}
							}
						}
					}
				}
			}
		} else {// else unlink
			$query="DELETE FROM glpi_contract_device WHERE FK_device = '$ID' AND device_type = '$type'";
			$DB->query($query);
		}

	}

	function transferDirectConnection($type,$ID,$link_type,$ocs_computer=false){
		global $DB,$LINK_ID_TABLE;
		// Only same Item case : no duplication of computers
		// Default : delete
		$keep=0;
		$clean=0;
		$ocs_field="";

		switch ($link_type){
			case PRINTER_TYPE:
				$keep=$this->options['keep_dc_printer'];
				$clean=$this->options['clean_dc_printer'];
				$ocs_field="import_printer";
				break;
			case MONITOR_TYPE:
				$keep=$this->options['keep_dc_monitor'];
				$clean=$this->options['clean_dc_monitor'];
				$ocs_field="import_monitor";
				break;
			case PERIPHERAL_TYPE:
				$keep=$this->options['keep_dc_peripheral'];
				$clean=$this->options['clean_dc_peripheral'];
				$ocs_field="import_peripheral";
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
												$this->addToAlreadyTransfer($link_type,$item_ID,$newID);
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
								// OCS clean link
								if ($ocs_computer&&!empty($ocs_field)){
									$query="UPDATE glpi_ocs_link SET $ocs_field = NULL WHERE glpi_id='$ID'";
									$DB->query($query);
								}

							}
							// If clean and not linked dc -> delete
							if ($need_clean_process&&$clean){
								$query = "SELECT COUNT(*) AS CPT
									FROM glpi_connect_wire 
									WHERE end1='$item_ID' AND type='".$link_type."'";
								if ($result_dc=$DB->query($query)){
									if ($DB->result($result_dc,0,'CPT')==0){
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
								if ($ocs_computer&&!empty($ocs_field)){
									$query="UPDATE glpi_ocs_link SET $ocs_field = NULL WHERE glpi_id='$ID'";
									$DB->query($query);
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

		$query = "SELECT ID, assign_ent
			FROM glpi_tracking 
			WHERE computer = '$ID' AND device_type = '$type'";
		if ($result = $DB->query($query)) {
			if ($DB->numrows($result)!=0) { 
				switch ($this->options['keep_tickets']){
					// Transfer
					case 2: 
						// Same Item / Copy Item -> update entity
						while ($data=$DB->fetch_array($result)) {
							$assign_ent=0;
							if ($data['assign_ent']>0){
								$assign_ent=$this->transferSingleEnterprise($data['assign_ent']);
							}
							$job->update(array("ID"=>$data['ID'],'FK_entities' => $this->to, 'computer'=>$newID, 'device_type'=>$type, 'assign_ent'=>$assign_ent));
							$this->addToAlreadyTransfer(TRACKING_TYPE,$data['ID'],$data['ID']);
						}
					break;
					// Clean ref : keep ticket but clean link
					case 1: 
						// Same Item / Copy Item : keep and clean ref
						while ($data=$DB->fetch_array($result)) {
							$assign_ent=0;
							if ($data['assign_ent']>0){
								$assign_ent=$this->transferSingleEnterprise($data['assign_ent']);
							}

							$job->update(array("ID"=>$data['ID'],'device_type' => 0, 'computer'=>0, 'assign_ent'=>$assign_ent));
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
	function transferCompatiblePrinters($ID,$newID){
		global $DB;
		if ($ID!=$newID){
			
			$query="SELECT * FROM glpi_cartridges_assoc WHERE FK_glpi_cartridges_type='$ID'";
			if ($result = $DB->query($query)) {
				if ($DB->numrows($result)!=0) { 
					
					$cartype=new CartridgeType();
					while ($data=$DB->fetch_array($result)) {
						$data = addslashes_deep($data);
						$cartype->addCompatibleType($newID,$data["FK_glpi_dropdown_model_printers"]);
					}
				}
			}
			
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
					// transfert enterprise 
					$FK_enterprise=0; 
					if ($ic->fields['FK_enterprise']>0){
						$FK_enterprise=$this->transferSingleEnterprise($ic->fields['FK_enterprise']);
					}
					// Copy : copy infocoms
					if ($ID!=$newID){
						// Copy items
						$input=$ic->fields;
						$input['FK_device']=$newID;
						$input['FK_enterprise']=$FK_enterprise;
						unset($input['ID']);
						unset($ic->fields);
						$ic->add($input);
					} else {
						// Same Item : manage only enterprise move
						// Update enterprise
						if ($FK_enterprise>0 && $FK_enterprise!=$ic->fields['FK_enterprise']){
							$ic->update(array('ID'=>$ic->fields['ID'],'FK_enterprise'=>$FK_enterprise));
						}
					}
					break;
			}
		}
	}
	function transferSingleEnterprise($ID){
		global $DB;
		// TODO clean system
		$ent=new Enterprise();
		if ($this->options['keep_enterprises']&&$ent->getFromDB($ID)){
			// Already transfer
			if (isset($this->already_transfer[ENTERPRISE_TYPE][$ID])){
				return $this->already_transfer[ENTERPRISE_TYPE][$ID];
			} else {
				$newID=-1;
				// Not already transfer
				$links_remaining=0;
				// All linked items need to be transfer so transfer enterprise ?
				// Search for contract
				$query="SELECT count(*) AS CPT FROM glpi_contract_enterprise WHERE FK_enterprise='$ID' AND FK_contract NOT IN ".$this->item_search[CONTRACT_TYPE];
				$result_search=$DB->query($query);
				$links_remaining=$DB->result($result_search,0,'CPT');

				if ($links_remaining==0){
					// Search for infocoms
					if ($this->options['keep_infocoms']){
						foreach ($this->INFOCOMS_TYPES as $type){
							$query="SELECT count(*) AS CPT FROM glpi_infocoms
								WHERE device_type='$type' AND FK_device NOT IN ".$this->item_search[$type];
							if ($result_search = $DB->query($query)) {
								$links_remaining+=$DB->result($result_search,0,'CPT');
							}
						}
					}					
				}
				// All linked items need to be transfer -> use unique transfer system
				if ($links_remaining==0){
					$this->transferItem(ENTERPRISE_TYPE,$ID,$ID);
					$newID=$ID;
				} else { // else Transfer by Copy
					// Is existing item in the destination entity ?
					$query="SELECT * FROM glpi_enterprises WHERE FK_entities='".$this->to."' AND name='".addslashes($ent->fields['name'])."'";
					if ($result_search=$DB->query($query)){
						if ($DB->numrows($result_search)>0){
							$newID=$DB->result($result_search,0,'ID');
							$this->addToAlreadyTransfer(ENTERPRISE_TYPE,$ID,$newID);
						}
					}
					// Not found -> transfer copy
					if ($newID<0){
						// 1 - create new item
						unset($ent->fields['ID']);
						$input=$ent->fields;
						$input['FK_entities']=$this->to;
						unset($ent->fields);
						$newID=$ent->add($input);
						// 2 - transfer as copy
						$this->transferItem(ENTERPRISE_TYPE,$ID,$newID);
					}
					// Founded -> use to link : nothing to do
				}	
				return $newID;	
			}
		} else {
			return 0;
		}
	}

	function transferEnterpriseContacts($ID,$newID){
		global $DB;
		$need_clean_process=false;
		// if keep 
		if ($this->options['keep_contacts']){
			$contact=new Contact();
			// Get contracts for the item
			$query="SELECT * FROM glpi_contact_enterprise WHERE FK_enterprise = '$ID'";
			if ($result = $DB->query($query)) {
				if ($DB->numrows($result)>0) { 
					// Foreach get item 
					while ($data=$DB->fetch_array($result)) {
						$need_clean_process=false;
						$item_ID=$data['FK_contact'];
						$newcontactID=-1;
						// is already transfer ?
						if (isset($this->already_transfer[CONTACT_TYPE][$item_ID])){
							$newcontactID=$this->already_transfer[CONTACT_TYPE][$item_ID];
							if ($newcontactID!=$item_ID){
								$need_clean_process=true;
							}
						} else {
							// No
							// Can be transfer without copy ? = all linked items need to be transfer (so not copy)
							$canbetransfer=true;
							// No items to transfer -> exists links
							$query_search="SELECT count(*) AS CPT 
									FROM glpi_contact_enterprise 
									WHERE FK_contact='$item_ID' AND FK_enterprise NOT IN ".$this->item_search[ENTERPRISE_TYPE];
							$result_search = $DB->query($query_search);
							if ($DB->result($result_search,0,'CPT')>0){
								$canbetransfer=false;
							}
							// Yes : transfer 
							if ($canbetransfer){
								$this->transferItem(CONTACT_TYPE,$item_ID,$item_ID);
								$newcontactID=$item_ID;
							} else {
								$need_clean_process=true;
								$contact->getFromDB($item_ID);
								// No : search contract
								$query="SELECT * FROM glpi_contacts WHERE FK_entities='".$this->to."' AND name='".addslashes($contact->fields['name'])."' AND firstname='".addslashes($contact->fields['firstname'])."'";
								if ($result_search=$DB->query($query)){
									if ($DB->numrows($result_search)>0){
										$newcontactID=$DB->result($result_search,0,'ID');
										$this->addToAlreadyTransfer(CONTACT_TYPE,$item_ID,$newcontactID);
									}
								}
								// found : use it
								// not found : copy contract
								if ($newcontactID<0){
									// 1 - create new item
									unset($contact->fields['ID']);
									$input=$contact->fields;
									$input['FK_entities']=$this->to;
									unset($contact->fields);
									$newcontactID=$contact->add($input);
									// 2 - transfer as copy
									$this->transferItem(CONTACT_TYPE,$item_ID,$newcontactID);
								}
							}
						}
						// Update links 
						if ($ID==$newID){
							if ($item_ID!=$newcontactID){
								$query="UPDATE glpi_contact_enterprise SET FK_contact = '$newcontactID' WHERE ID='".$data['ID']."'";
								$DB->query($query);
							}
							// Same Item -> update links
						} else {
							// Copy Item -> copy links
							if ($item_ID!=$newcontactID){
								$query="INSERT INTO glpi_contact_enterprise (FK_contact,FK_enterprise) VALUES ('$newcontactID','$newID')";
								$DB->query($query);
							} else { // transfer contact but copy enterprise : update link
								$query="UPDATE glpi_contact_enterprise SET FK_enterprise = '$newID' WHERE ID='".$data['ID']."'";
								$DB->query($query);
							}
						}
						// If clean and unused -> 
						if ($need_clean_process&&$this->options['clean_contacts']){
							$query = "SELECT COUNT(*) AS CPT
								FROM glpi_contact_enterprise 
								WHERE FK_contact='$item_ID'";
							if ($result_remaining=$DB->query($query)){
								if ($DB->result($result_remaining,0,'CPT')==0){
									if ($clean==1){
										$contact->delete(array('ID'=>$item_ID));
									} 
									if ($clean==2) { // purge
										$contact->delete(array('ID'=>$item_ID),1);
									}
								}
							}
						}

					}
				}
			}
		} else {// else unlink
			$query="DELETE FROM glpi_contact_enterprise WHERE FK_enterprise = '$ID'";
			$DB->query($query);
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

	function transferDevices($type,$ID,$ocs_computer=false){
		global $DB;
		// Only same case because no duplication of computers
		switch ($this->options['keep_devices']){
			// delete devices
			case 0 :  
				$query = "DELETE FROM glpi_computer_device 
					WHERE FK_computers = '$ID'";
				$result = $DB->query($query);
				// Only case of ocs link update is needed (if devices are keep nothing to do)
				if ($ocs_computer){
					$query="UPDATE glpi_ocs_link SET import_ip = NULL WHERE glpi_id='$ID'";
					$DB->query($query);
				}
				break;
			// Keep devices
			case 1 :	
			default : 
				// Same item -> nothing to do
				break;
		}
	}

	function transferNetworkLink($type,$ID,$newID,$ocs_computer=false){
		global $DB;
		$np=new Netport();

		$query = "SELECT *
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
							// Only case of ocs link update is needed (if netports are keep nothing to do)
							if ($ocs_computer){
								$query="UPDATE glpi_ocs_link SET import_ip = NULL WHERE glpi_id='$ID'";
								$DB->query($query);
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
								if ($data['netpoint']){
									$netpointID=$this->transferDropdownNetpoint($data['netpoint']);
									$input['ID']=$data['ID'];
									$input['netpoint']=$netpointID;
									$np->update($input);
								}
							}
						} else { // Copy -> copy netports
							while ($data=$DB->fetch_array($result)) {
								$data = addslashes_deep($data);
								unset($data['ID']);
								$data['on_device']=$newID;
								$data['netpoint']=$this->transferDropdownNetpoint($data['netpoint']);
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
								$data['netpoint']=$this->transferDropdownNetpoint($data['netpoint']);
								unset($np->fields);
								$np->add($data);
							}
						} else {
							while ($data=$DB->fetch_array($result)) {
								// Not a copy -> only update netpoint
								if ($data['netpoint']){
									$netpointID=$this->transferDropdownNetpoint($data['netpoint']);
									$input['ID']=$data['ID'];
									$input['netpoint']=$netpointID;
									$np->update($input);
								}
							}
						}

						break;

				}
			}
		}
	}

	/**
	 * Print the transfer form
	 *
	 *
	 * Print transfer form
	 *
	 *@param $target filename : where to go when done.
	 *@param $ID Integer : Id of the contact to print
	 *@param $withtemplate='' boolean : template or basic item
	 *
	 *
	 *@return Nothing (display)
	 *
	 **/
	function showForm ($target,$ID,$withtemplate='') {

		global $CFG_GLPI, $LANG;

		if (!haveRight("transfer","r")) return false;

		$edit_form=true;
		if (!ereg("transfer.form.php",$_SERVER['PHP_SELF'])){
			$edit_form=false;
		}

		$con_spotted=false;
		$use_cache=true;
		if (empty($ID)) {
			if($this->getEmpty()) $con_spotted = true;
			$use_cache=false;
		} else {
			if($this->getfromDB($ID)) $con_spotted = true;
		}

		if ($con_spotted){

			echo "<form method='post' name=form action=\"$target\"><div class='center'>";

			echo "<table class='tab_cadre' cellpadding='2' >";
			if ($edit_form){
				echo "<tr><th colspan='4'>";
				if (empty($ID)) {
					echo $LANG["transfer"][2];
				} else {
					echo $LANG["common"][2]." $ID";
				}		
				echo "</th></tr>";
			} else {
					echo "<tr>";
					echo "<td class='tab_bg_2' valign='top' colspan='4'>";
					
					echo "<div class='center'>";
					dropdownActiveEntities('to_entity');
					echo "&nbsp;<input type='submit' name='transfer' value=\"".$LANG["buttons"][48]."\" class='submit'></div>";
					echo "</td>";
					echo "</tr>";
			}

			if (!$use_cache||!($CFG_GLPI["cache"]->start($ID."_".$_SESSION["glpilanguage"],"GLPI_".$this->type))) {
				if ($edit_form){
					echo "<tr class='tab_bg_1'>";
					echo "<td colspan='2'>".$LANG["common"][16].":	</td><td colspan='2'>";
					autocompletionTextField("name","glpi_transfers","name",$this->fields["name"],30);	
					echo "</td>";
					echo "</tr>";
				}

				$keep=array(0=>$LANG["buttons"][6],
						1=>$LANG["buttons"][49]);
				$clean=array(0=>$LANG["buttons"][49],
					1=>$LANG["buttons"][6],
					2=>$LANG["buttons"][22]);

				echo "<tr class='tab_bg_1'>";
				echo "<td>".$LANG["search"][7]." -> ".$LANG["title"][38].":	</td><td>";
				dropdownArrayValues('keep_history',$keep,$this->fields['keep_history']);
				echo "</td>";
				echo "<td colspan='2'>&nbsp;</td>";
				echo "</tr>";

				echo "<tr class='tab_bg_2'>";
				echo "<td colspan='4' class='center'><strong>".$LANG["Menu"][38]."</strong></td></tr>";

				echo "<tr class='tab_bg_1'>";
				echo "<td>".$LANG["search"][7]." -> ".$LANG["networking"][6].":	</td><td>";
				$options=array(0=>$LANG["buttons"][6],
						1=>$LANG["buttons"][49]." - ".$LANG["buttons"][10] ,
						2=>$LANG["buttons"][49]." - ".$LANG["buttons"][9] );
				dropdownArrayValues('keep_networklinks',$options,$this->fields['keep_networklinks']);
				echo "</td>";
				echo "<td>".$LANG["search"][7]." -> ".$LANG["title"][28].":	</td><td>";
				$options=array(0=>$LANG["buttons"][6],
						1=>$LANG["buttons"][49]." - ".$LANG["buttons"][10] ,
						2=>$LANG["buttons"][49]." - ".$LANG["buttons"][48] );
				dropdownArrayValues('keep_tickets',$options,$this->fields['keep_tickets']);
				echo "</td>";
				echo "</tr>";



				echo "<tr class='tab_bg_1'>";
				echo "<td>".$LANG["Menu"][0]." -> ".$LANG["Menu"][4].":	</td><td>";
				dropdownArrayValues('keep_softwares',$keep,$this->fields['keep_softwares']);
				echo "</td>";
				echo "<td>".$LANG["Menu"][4].": ".$LANG["transfer"][3]."	</td><td>";
				dropdownArrayValues('clean_softwares',$clean,$this->fields['clean_softwares']);
				echo "</td>";
				echo "</tr>";

				echo "<tr class='tab_bg_1'>";
				echo "<td>".$LANG["search"][7]." -> ".$LANG["Menu"][17].":	</td><td>";	
				dropdownArrayValues('keep_reservations',$keep,$this->fields['keep_reservations']);
				echo "</td>";
				echo "<td>".$LANG["Menu"][0]." -> ".$LANG["devices"][10].":	</td><td>";
				dropdownArrayValues('keep_devices',$keep,$this->fields['keep_devices']);
				echo "</td>";
				echo "</tr>";

				echo "<tr class='tab_bg_1'>";
				echo "<td>".$LANG["Menu"][2]." -> ".$LANG["title"][19]." / ".$LANG["cartridges"][12].":	</td><td>";
				dropdownArrayValues('keep_cartridges_type',$keep,$this->fields['keep_cartridges_type']);
				echo "</td>";
				echo "<td>".$LANG["cartridges"][12].": ".$LANG["transfer"][3]."	</td><td>";
				dropdownArrayValues('clean_cartridges_type',$clean,$this->fields['clean_cartridges_type']);
				echo "</td>";
				echo "</tr>";

				echo "<tr class='tab_bg_1'>";
				echo "<td>".$LANG["cartridges"][12]." -> ".$LANG["title"][19].":	</td><td>";
				dropdownArrayValues('keep_cartridges',$keep,$this->fields['keep_cartridges']);
				echo "</td>";				
				echo "<td>".$LANG["search"][7]." -> ".$LANG["financial"][3].":	</td><td>";
				dropdownArrayValues('keep_infocoms',$keep,$this->fields['keep_infocoms']);
				echo "</td>";
				echo "</tr>";

				echo "<tr class='tab_bg_1'>";
				echo "<td>".$LANG["setup"][92]." -> ".$LANG["Menu"][32].":	</td><td>";
				dropdownArrayValues('keep_consumables',$keep,$this->fields['keep_consumables']);
				echo "</td>";
				echo "<td colspan='2'>&nbsp;</td>";
				echo "</tr>";

				echo "<tr class='tab_bg_2'>";
				echo "<td colspan='4' class='center'><strong>".$LANG["connect"][0]."</strong></td></tr>";

				echo "<tr class='tab_bg_1'>";
				echo "<td>".$LANG["Menu"][3].":	</td><td>";
				dropdownArrayValues('keep_dc_monitor',$keep,$this->fields['keep_dc_monitor']);
				echo "</td>";
				echo "<td>".$LANG["Menu"][3].": ".$LANG["transfer"][3]."	</td><td>";
				dropdownArrayValues('clean_dc_monitor',$clean,$this->fields['clean_dc_monitor']);
				echo "</td>";
				echo "</tr>";

				echo "<tr class='tab_bg_1'>";
				echo "<td>".$LANG["Menu"][2].":	</td><td>";
				dropdownArrayValues('keep_dc_printer',$keep,$this->fields['keep_dc_printer']);
				echo "</td>";
				echo "<td>".$LANG["Menu"][2].": ".$LANG["transfer"][3]."	</td><td>";
				dropdownArrayValues('clean_dc_printer',$clean,$this->fields['clean_dc_printer']);
				echo "</td>";
				echo "</tr>";

				echo "<tr class='tab_bg_1'>";
				echo "<td>".$LANG["Menu"][16].":	</td><td>";
				dropdownArrayValues('keep_dc_peripheral',$keep,$this->fields['keep_dc_peripheral']);
				echo "</td>";
				echo "<td>".$LANG["Menu"][16].": ".$LANG["transfer"][3]."	</td><td>";
				dropdownArrayValues('clean_dc_peripheral',$clean,$this->fields['clean_dc_peripheral']);
				echo "</td>";
				echo "</tr>";

				echo "<tr class='tab_bg_1'>";
				echo "<td>".$LANG["Menu"][34].":	</td><td>";
				dropdownArrayValues('keep_dc_phone',$keep,$this->fields['keep_dc_phone']);
				echo "</td>";
				echo "<td>".$LANG["Menu"][34].": ".$LANG["transfer"][3]."	</td><td>";
				dropdownArrayValues('clean_dc_phone',$clean,$this->fields['clean_dc_phone']);
				echo "</td>";
				echo "</tr>";


				echo "<tr class='tab_bg_2'>";
				echo "<td colspan='4' class='center'><strong>".$LANG["Menu"][26]."</strong></td></tr>";


				echo "<tr class='tab_bg_1'>";
				echo "<td>".$LANG["search"][7]." -> ".$LANG["Menu"][23].":	</td><td>";
				dropdownArrayValues('keep_enterprises',$keep,$this->fields['keep_enterprises']);
				echo "</td>";
				echo "<td>".$LANG["Menu"][23].": ".$LANG["transfer"][3]."	</td><td>";
				dropdownArrayValues('clean_enterprises',$clean,$this->fields['clean_enterprises']);
				echo "</td>";
				echo "</tr>";

				echo "<tr class='tab_bg_1'>";
				echo "<td>".$LANG["Menu"][23]." -> ".$LANG["Menu"][22].":	</td><td>";
				dropdownArrayValues('keep_contacts',$keep,$this->fields['keep_contacts']);
				echo "</td>";
				echo "<td>".$LANG["Menu"][22].": ".$LANG["transfer"][3]."	</td><td>";
				dropdownArrayValues('clean_contacts',$clean,$this->fields['clean_contacts']);
				echo "</td>";
				echo "</tr>";

				echo "<tr class='tab_bg_1'>";
				echo "<td>".$LANG["search"][7]." -> ".$LANG["Menu"][27].":	</td><td>";
				dropdownArrayValues('keep_documents',$keep,$this->fields['keep_documents']);
				echo "</td>";
				echo "<td>".$LANG["Menu"][27].": ".$LANG["transfer"][3]."	</td><td>";
				dropdownArrayValues('clean_documents',$clean,$this->fields['clean_documents']);
				echo "</td>";
				echo "</tr>";

				echo "<tr class='tab_bg_1'>";
				echo "<td>".$LANG["search"][7]." -> ".$LANG["Menu"][25].":	</td><td>";
				dropdownArrayValues('keep_contracts',$keep,$this->fields['keep_contracts']);
				echo "</td>";
				echo "<td>".$LANG["Menu"][25].": ".$LANG["transfer"][3]."	</td><td>";
				dropdownArrayValues('clean_contracts',$clean,$this->fields['clean_contracts']);
				echo "</td>";
				echo "</tr>";

				if ($use_cache){
					$CFG_GLPI["cache"]->end();
				}
			}

			if (haveRight("transfer","w")) {
				if ($edit_form){
					if ($ID=="") {
						echo "<tr>";
						echo "<td class='tab_bg_2' valign='top' colspan='4'>";
						echo "<div class='center'><input type='submit' name='add' value=\"".$LANG["buttons"][8]."\" class='submit'></div>";
						echo "</td>";
						echo "</tr>";
					} else {
						echo "<tr>";
						echo "<td class='tab_bg_2' valign='top' colspan='2'>";
						echo "<input type='hidden' name='ID' value=\"$ID\">\n";
						echo "<div class='center'><input type='submit' name='update' value=\"".$LANG["buttons"][7]."\" class='submit' ></div>";
						echo "</td>\n\n";
						echo "<td class='tab_bg_2' valign='top' colspan='2'>\n";
						echo "<div class='center'><input type='submit' name='delete' value=\"".$LANG["buttons"][6]."\" class='submit'></div>";
						echo "</td>";
						echo "</tr>";
	
					}
				} 
			}
			echo "</table></div></form>";

		} else {
			echo "<div class='center'><strong>".$LANG["common"][54]."</strong></div>";
			return false;

		}
		return true;
	}
	// Display items to transfers
	function showTransferList(){
		global $LANG,$LINK_ID_TABLE,$DB,$CFG_GLPI;
		$ci=new CommonItem();
		if (isset($_SESSION['glpitransfer_list'])&&count($_SESSION['glpitransfer_list'])){
			echo "<div class='center'><strong>".$LANG["transfer"][5]."<br>".$LANG["transfer"][6];
			echo "</strong>";
			echo "</div>";
			//echo '<tr><th colspan="2">'.$LANG["transfer"][4].'</th></tr>';
			echo "<table class='tab_cadre_fixe' >";
			echo '<tr><th>'.$LANG["transfer"][7].'</th><th>'.$LANG["transfer"][8].":&nbsp;";
			$rand=dropdownValue("glpi_transfers","ID",0,0,-1,array('value_fieldname'=>'ID',
			'to_update'=>"transfer_form", 'url'=>$CFG_GLPI["root_doc"]."/ajax/transfers.php"));
			echo '</th></tr>';
			echo "<tr><td class='tab_bg_1' valign='top'>";
			
			foreach ($_SESSION['glpitransfer_list'] as $type => $tab){
				if (count($tab)){
					$table=$LINK_ID_TABLE[$type];
					$query="SELECT $table.name, glpi_entities.completename AS locname, glpi_entities.ID AS entID 
						FROM $table LEFT JOIN glpi_entities ON ($table.FK_entities = glpi_entities.ID) 
						WHERE $table.ID IN ".$this->createSearchConditionUsingArray($tab)."
						ORDER BY locname, $table.name";
					$entID=-1;
					if ($result=$DB->query($query)){
						if ($DB->numrows($result)){
							$ci->setType($type);
							echo '<h3>'.$ci->getType().'</h3>';
							while ($data=$DB->fetch_assoc($result)){
								if ($entID!=$data['entID']){
									if ($entID!=-1){
										echo '<br>';
									}
									$entID=$data['entID'];
									if ($entID>0){
										echo '<strong>'.$data['locname'].'</strong><br>';
									} else {
										echo '<strong>'.$LANG["entity"][2].'</strong><br>';
									}
								}
								echo $data['name']."<br>";
							}
						}
					}
				}
			}
			echo "</td><td class='tab_bg_2' valign='top'>";
			if (countElementsInTable('glpi_transfers')==0){
				echo $LANG["search"][15];
			} else {
				
				$params=array('ID'=>'__VALUE__');
				ajaxUpdateItemOnSelectEvent("dropdown_ID","transfer_form",$CFG_GLPI["root_doc"]."/ajax/transfers.php",$params,false);
				//ajaxUpdateItem("transfer_form",$CFG_GLPI["root_doc"]."/ajax/transfers.php",$params,false,"dropdown_ID".$rand);
			}
			echo "<div align='center' id='transfer_form'>";
			echo "<a href='".$_SERVER['PHp_SELF']."?clear=1'>".$LANG["transfer"][4]."</a>";
			echo "</div>";
			echo '</td></tr>';
			echo '</table>';
		} else {
			echo $LANG["common"][24];
		}

	}
}


?>
