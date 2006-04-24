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

 


//!  ConsumableType Class
/**
  This class is used to manage the various types of consumables.
	\see Consumable
	\author Julien Dombre
*/
class ConsumableType extends CommonDBTM {

	function ConsumableType () {
		$this->table="glpi_consumables_type";
		$this->type=CONSUMABLE_TYPE;
	}

	function cleanDBonPurge($ID) {
		global $db;
		// Delete cartridconsumablesges
		$query = "DELETE FROM glpi_consumables WHERE (FK_glpi_consumables_type = '$ID')";
		$db->query($query);
	}

	function post_getEmpty () {
		global $cfg_glpi;
		$this->fields["alarm"]=$cfg_glpi["cartridges_alarm"];
	}

	function defineOnglets($withtemplate){
		global $lang;
		$ong[1]=$lang["title"][26];
		if (haveRight("contract_infocom","r"))	
			$ong[4]=$lang["Menu"][26];
		if (haveRight("document","r"))	
			$ong[5]=$lang["title"][25];
		if (haveRight("link","r"))	
			$ong[7]=$lang["title"][34];
		if (haveRight("notes","r"))
			$ong[10]=$lang["title"][37];
		return $ong;
	}

}

//!  Consumable Class
/**
  This class is used to manage the consumables.
  \see ConsumableType
  \author Julien Dombre
*/
class Consumable extends CommonDBTM {

	function Consumable () {
		$this->table="glpi_consumables";
		$this->type=CONSUMABLE_ITEM_TYPE;
	}
	

	function cleanDBonPurge($ID) {
		global $db;
		$query = "DELETE FROM glpi_infocoms WHERE (FK_device = '$ID' AND device_type='".CONSUMABLE_ITEM_TYPE."')";
		$result = $db->query($query);
	}

	function prepareInputForAdd($input) {
		return array("FK_glpi_consumables_type"=>$input["tID"],
				"date_in"=>date("Y-m-d"));
	}

	function postAddItem($newID,$input) {
		// Add infocoms if exists for the licence
		$ic=new Infocom();
	
		if ($ic->getFromDBforDevice(CONSUMABLE_TYPE,$this->fields["FK_glpi_consumables_type"])){
			unset($ic->fields["ID"]);
			$ic->fields["FK_device"]=$newID;
			$ic->fields["device_type"]=CONSUMABLE_ITEM_TYPE;
			$ic->addToDB();
		}
	}

	function restore($input){
		global $db;
		$query = "UPDATE glpi_consumables SET date_out = NULL WHERE ID='".$input["ID"]."'";

		if ($result = $db->query($query)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	* UnLink a consumable linked to a printer
	*
	* UnLink the consumable identified by $ID
	*
	*@param $ID : consumable identifier
	*
	*@return boolean
	*
	**/
	function out($ID,$id_user=0) {

		global $db;
		$query = "UPDATE glpi_consumables SET date_out = '".date("Y-m-d")."', id_user='$id_user' WHERE ID='$ID'";

		if ($result = $db->query($query)) {
			return true;
		} else {
			return false;
		}
	}


}

?>