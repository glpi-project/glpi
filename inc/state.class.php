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

 
// CLASSES State_Item

class StateItem  extends CommonDBTM {

	var $state = array();
	var $obj = NULL;	

	function StateItem () {
		$this->table="glpi_state_item";
	}

	function getfromDB ($device_type,$id_device,$template=0) {
		global $db;

		$this->fields["state"]=-1;
		// Make new database object and fill variables
		
		$query = "SELECT * FROM glpi_state_item WHERE (device_type='$device_type' AND id_device = '$id_device' AND is_template='$template' )";

		if ($result = $db->query($query)) 
		if ($db->numrows($result)>0){
			$data = $db->fetch_array($result);
			foreach ($data as $key => $val) {
				$this->fields[$key] = $val;
			}
		if (!isset($this->fields["device_type"]))			
		return false;
			switch ($this->fields["device_type"]){
			case COMPUTER_TYPE :
				$this->obj=new Computer;
				break;
			case NETWORKING_TYPE :
				$this->obj=new Netdevice;
				break;
			case PRINTER_TYPE :
				$this->obj=new Printer;
				break;
			case PHONE_TYPE : 
				$this->obj= new Phone;	
				break;				
			case MONITOR_TYPE : 
				$this->obj= new Monitor;	
				break;
			case PERIPHERAL_TYPE : 
				$this->obj= new Peripheral;	
				break;				
			}
			
			if ($this->obj!=NULL)
			return $this->obj->getfromDB($this->fields["id_device"]);
			else return false;
			
		} else {
			return false;
		}
	}
	function getType (){
		global $lang;
		
		switch ($this->fields["device_type"]){
			case COMPUTER_TYPE :
				return $lang["computers"][44];
				break;
			case NETWORKING_TYPE :
				return $lang["networking"][12];
				break;
			case PRINTER_TYPE :
				return $lang["printers"][4];
				break;
			case MONITOR_TYPE : 
				return $lang["monitors"][4];
				break;
			case PERIPHERAL_TYPE : 
				return $lang["peripherals"][4];
				break;				
			case PHONE_TYPE : 
				return $lang["phones"][4];
				break;				
			}
	
	}
	
	function getItemType (){
		global $lang;
		
		switch ($this->fields["device_type"]){
			case COMPUTER_TYPE :
				return getDropdownName("glpi_type_computers",$this->obj->fields["type"]);
				break;
			case NETWORKING_TYPE :
				return getDropdownName("glpi_type_networking",$this->obj->fields["type"]);
				break;
			case PRINTER_TYPE :
				return getDropdownName("glpi_type_printers",$this->obj->fields["type"]);
				break;
			case MONITOR_TYPE : 
				return getDropdownName("glpi_type_monitors",$this->obj->fields["type"]);
				break;
			case PERIPHERAL_TYPE : 
				return getDropdownName("glpi_type_peripherals",$this->obj->fields["type"]);
				break;				
			case PHONE_TYPE : 
				return getDropdownName("glpi_type_phones",$this->obj->fields["type"]);
				break;				
			}
	
	}
	
	function getName(){
		if (isset($this->obj->fields["name"])&&$this->obj->fields["name"]!="")
	return $this->obj->fields["name"];
	else return "N/A";
	}
	
	function getLink(){
	
		global $cfg_glpi,$INFOFORM_PAGES;
		
		$show=$this->getName();
		// show id if it was configure else nothing
		if ($cfg_glpi["view_ID"]||empty($show)) $show.=" (".$this->fields["id_device"].")";


		return "<a href=\"".$cfg_glpi["root_doc"]."/".$INFOFORM_PAGES[$this->fields["device_type"]]."?ID=".$this->fields["id_device"]."\">$show</a>";
	}
	
}


?>