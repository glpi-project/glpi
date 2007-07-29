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
					$this->transferItem(COMPUTER_TYPE,$ID);
				}
			}
			// Inventory Items : MONITOR....

			// Management Items

		}

	}

	function transferItem($type,$ID){
		$ci=new CommonItem();
		// Check computer exists ?
		if ($ci->getFromDB($type,$ID)){
			// Check item in other entity
			if ($ci->getFields['FK_entities']>0&&$ci->getFields['FK_entities']!=$this->to){
				// Network connection ? keep connected / keep_disconnected / delete
				if (in_array($type,
					array(COMPUTER_TYPE,NETWORKING_TYPE,PRINTER_TYPE,MONITOR_TYPE,PERIPHERAL_TYPE,PHONE_TYPE)){
					$this->transferNetworkLink($type,$ID);
				}
				// Device : keep / delete : network case : delete if net connection delete in ocs case
				if (in_array($type,array(COMPUTER_TYPE)){
					$this->transferDevices($type,$ID);
				}
				// Reservation : keep / delete
				if (in_array($type,$CFG_GLPI["reservation_types"])){
					$this->transferReservations($type,$ID);
				}
				// History : keep / delete
				$this->transferHistory($type,$ID);
				// Ticket : delete / keep and clean ref / keep and move
				$this->transferTickets($type,$ID);

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
				// Computer Direct Connect : delete link
				if (in_array($type,
					array(PRINTER_TYPE,MONITOR_TYPE,PERIPHERAL_TYPE,PHONE_TYPE)){
					$this->deleteDirectConnection($type,$ID);
				}

				

				
				// Infocoms : keep / delete
				// Licence / Software :  keep / delete + clean unused / keep unused 
				// Document : keep / delete + clean unused / keep unused + duplicate file ?
				// Contract : keep / delete + clean unused / keep unused
				// Enterprise (depending of item link) / Contract - infocoms : keep / delete + clean unused / keep unused
				// Contact / Enterprise : keep / delete + clean unused / keep unused
				// Users ????

				// Transfer Item
				$ci->obj->update(array("ID"=>$ID,'FK_entities' => $this->to));
				$this->already_transfer[COMPUTER_TYPE][$ID]=$ID;
			}
		}
	}
	function transferDirectConnection($type,$ID,$link_type){
		global $DB;
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
		// Get connections
			// Foreach get item 
				// If unique : 
					//if keep -> add to transfer list else unlink 
					//if clean -> delete
				// If global : 
					// copy if not already transfer + update link 
					// if clean and not linked dc -> delete
	}
	function transferTickets($type,$ID){
		global $DB;

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

	function transferHistory($type,$ID){
		global $DB;

		// If delete
		if ($this->options['keep_history']==0){
			$query = "DELETE FROM glpi_history WHERE ( device_type = '$type' AND FK_glpi_device = '$ID')";
			$result = $DB->query($query);
		}
	}

	function transferReservations($type,$ID){
		global $DB;

		$ri=new ReservationItem();
		// If delete
		if ($this->options['keep_reservations']==0){
			if ($ri->getFromDBbyItem($type,$ID)){
				$ri->delete(array('ID'=>$ri->fields['ID']));
			}
		}
	}

	function transferDevices($type,$ID){
		global $DB;

		// If not keep
		if ($this->options['keep_devices']==0){
			$query = "DELETE FROM glpi_computer_device WHERE (FK_computers = '$ID')";
			$result = $DB->query($query);
		}
	}

	function transferNetworkLink($type,$ID){
		global $DB;
	
		$np=new Netport();
		// If not keep or disconnect
		if ($this->options['keep_networklinks']!=2){
			$query = "SELECT ID FROM glpi_networking_ports WHERE (on_device = '$ID' AND device_type = '$type')";
			if ($result = $DB->query($query)) {
				if ($DB->numrows($result)!=0) { 
					// Delete netport
					if ($this->options['keep_networklinks']==0){
						while ($data=$DB->fetch_row($result)) {
							$np->delete(array('ID'=>$data['ID']));
						}
					} else { // only disconnect
						while ($data=$DB->fetch_row($result)) {
							removeConnector($data['ID']);
						}
					}
				}
			}
		}
	}
}
?>