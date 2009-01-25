<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}



class InfoCom extends CommonDBTM {


	/**
	 * Constructor
	**/
	function __construct () {
		$this->table="glpi_infocoms";
		$this->type=INFOCOM_TYPE;
		$this->dohistory=true;
		$this->auto_message_on_action=false;
	}

	function post_getEmpty () {
		global $CFG_GLPI;
		$this->fields["alert"]=$CFG_GLPI["infocom_alerts"];
	}


	/**
	 * Retrieve an item from the database for a device
	 *
	 *@param $ID ID of the device to retrieve infocom
	 *@param $device_type type of the device to retrieve infocom
	 *@return true if succeed else false
	**/
	function getFromDBforDevice ($device_type,$ID) {

		global $DB;
		$query = "SELECT * 
			FROM glpi_infocoms 
			WHERE FK_device = '$ID' AND device_type='$device_type'";
	
		if ($result = $DB->query($query)) {
			if ($DB->numrows($result)==1){	
				$data = $DB->fetch_assoc($result);

				foreach ($data as $key => $val) {
					$this->fields[$key] = $val;
				}
				return true;
			} else {
				$this->getEmpty();
				$this->fields["FK_device"]=$ID;
				$this->fields["device_type"]=$device_type;
				return false;
			}
		} else {
			return false;
		}
	}

	function prepareInputForAdd($input) { 
		global $CFG_GLPI;
		if (!$this->getFromDBforDevice($input['device_type'],$input['FK_device'])){
			$input['alert']=$CFG_GLPI["infocom_alerts"];
			return $input; 
		} 
		return false; 
	} 

	function prepareInputForUpdate($input) {
		if (isset($input["ID"])){

			$this->getFromDB($input["ID"]);
		} else {
			if (!$this->getFromDBforDevice($input["device_type"],$input["FK_device"])){
				$input2["FK_device"]=$input["FK_device"];
				$input2["device_type"]=$input["device_type"];
				$this->add($input2);
				$this->getFromDBforDevice($input["device_type"],$input["FK_device"]);
			}
			$input["ID"]=$this->fields["ID"];
		}

		if (isset($input['warranty_duration'])){
			$input['_warranty_duration']=$this->fields['warranty_duration'];
		}
		return $input;
	}


	function pre_updateInDB($input,$updates,$oldvalues) {

		// Clean end alert if buy_date is after old one
		// Or if duration is greater than old one
		if ((isset($oldvalues['buy_date'])
			&& ($oldvalues['buy_date'] < $this->fields['buy_date'] ))
		|| ( isset($oldvalues['warranty_duration'])
			&& ($oldvalues['warranty_duration'] < $this->fields['warranty_duration'] ))
		){
			$alert=new Alert();
			$alert->clear($this->type,$this->fields['ID'],ALERT_END);
//			exit();
		}

		return array($input,$updates);
	}

	/**
	 * Is the object assigned to an entity
	 * 
	 * @return boolean
	**/
	function isEntityAssign () {
		$ci=new CommonItem();
		$ci->setType($this->fields["device_type"], true);
		
		return $ci->obj->isEntityAssign();
	}
	/**
	 * Get the ID of entity assigned to the object
	 * 
	 * @return ID of the entity 
	**/
	function getEntityID () {
		$ci=new CommonItem();
		$ci->getFromDB($this->fields["device_type"], $this->fields["FK_device"]);

		return $ci->obj->getEntityID();
	}	
	/**
	 * Is the object may be recursive
	 * 
	 * @return boolean
	**/
	function maybeRecursive () {
		$ci=new CommonItem();
		$ci->setType($this->fields["device_type"], true);
		
		return $ci->obj->maybeRecursive();
	}
	/**
	 * Is the object recursive
	 * 
	 * Can be overloaded (ex : infocom)
	 * 
	 * @return integer (0/1) 
	**/
	function isRecursive () {
		$ci=new CommonItem();
		$ci->getFromDB($this->fields["device_type"], $this->fields["FK_device"]);

		return $ci->obj->isRecursive();
	}	
}

?>