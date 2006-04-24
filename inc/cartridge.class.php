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

 


//!  CartridgeType Class
/**
  This class is used to manage the various types of cartridges.
	\see Cartridge
	\author Julien Dombre
*/
class CartridgeType extends CommonDBTM {

	function CartridgeType () {
		$this->table="glpi_cartridges_type";
		$this->type=CARTRIDGE_TYPE;
	}

	function cleanDBonPurge($ID) {
		global $db;
		// Delete cartridges
		$query = "DELETE FROM glpi_cartridges WHERE (FK_glpi_cartridges_type = '$ID')";
		$db->query($query);
		// Delete all cartridge assoc
		$query2 = "DELETE FROM glpi_cartridges_assoc WHERE (FK_glpi_cartridges_type = '$ID')";
		$result2 = $db->query($query2);

		$query = "DELETE FROM glpi_infocoms WHERE (FK_device = '$ID' AND device_type='".CARTRIDGE_TYPE."')";
		$result = $db->query($query);

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

	///// SPECIFIC FUNCTIONS

	function countCartridges() {
		global $db;
		$query = "SELECT * FROM glpi_cartridges WHERE (FK_glpi_cartridges_type = '".$this->fields["ID"]."')";
		if ($result = $db->query($query)) {
			$number = $db->numrows($result);
			return $number;
		} else {
			return false;
		}
	}

	/**
	* Add a compatible printer type for a cartridge type
	*
	* Add the compatible printer $type type for the cartridge type $tID
	*
	*@param $tID integer: cartridge type identifier
	*@param $type integer: printer type identifier
	*@return nothing ()
	*
	**/
	function addCompatibleType($tID,$type){
		global $db;
		if ($tID>0&&$type>0){
	
			$query="INSERT INTO glpi_cartridges_assoc (FK_glpi_cartridges_type,FK_glpi_dropdown_model_printers ) VALUES ('$tID','$type');";
			$result = $db->query($query);
		}
	}

	/**
	* delete a compatible printer associated to a cartridge
	*
	* Delete a compatible printer associated to a cartridge with assoc identifier $ID
	*
	*@param $ID integer: glpi_cartridge_assoc identifier.
	*
	*@return nothing ()
	*
	**/
	function deleteCompatibleType($ID){
	
	global $db;
	$query="DELETE FROM glpi_cartridges_assoc WHERE ID= '$ID';";
	$result = $db->query($query);
	}

}

//!  Cartridge Class
/**
  This class is used to manage the cartridges.
  \see CartridgeType
  \author Julien Dombre
*/
class Cartridge extends CommonDBTM {

	function Cartridge () {
		$this->table="glpi_cartridges";
		$this->type=CARTRIDGE_ITEM_TYPE;
	}
	

	function cleanDBonPurge($ID) {
		global $db;
		$query = "DELETE FROM glpi_infocoms WHERE (FK_device = '$ID' AND device_type='".CARTRIDGE_ITEM_TYPE."')";
		$result = $db->query($query);
	}

	function prepareInputForAdd($input) {
		return array("FK_glpi_cartridges_type"=>$input["tID"],
				"date_in"=>date("Y-m-d"));
	}

	function postAddItem($newID,$input) {
		// Add infocoms if exists for the licence
		$ic=new Infocom();
	
		if ($ic->getFromDBforDevice(CARTRIDGE_TYPE,$this->fields["FK_glpi_cartridges_type"])){
			unset($ic->fields["ID"]);
			$ic->fields["FK_device"]=$newID;
			$ic->fields["device_type"]=CARTRIDGE_ITEM_TYPE;
			$ic->addToDB();
		}
	}

	function restore($input) {

		global $db;
		$query = "UPDATE glpi_cartridges SET date_out = NULL, date_use = NULL , FK_glpi_printers= NULL WHERE ID='".$input["ID"]."'";
		if ($result = $db->query($query)) {
			return true;
		} else {
			return false;
		}
	}

	// SPECIFIC FUNCTIONS
	function updatePages($ID,$pages){
		global $db;
		$query="UPDATE glpi_cartridges SET pages='$pages' WHERE ID='$ID'";
		$db->query($query);
		}

	/**
	* Link a cartridge to a printer.
	*
	* Link the first unused cartridge of type $Tid to the printer $pID
	*
	*@param $tID : cartridge type identifier
	*@param $pID : printer identifier
	*
	*@return nothing
	*
	**/
	function install($pID,$tID) {
		global $db,$lang;
	
		// Get first unused cartridge
		$query = "SELECT ID FROM glpi_cartridges WHERE FK_glpi_cartridges_type = '$tID' AND date_use IS NULL";
		$result = $db->query($query);
		if ($db->numrows($result)>0){
		// Mise a jour cartouche en prenant garde aux insertion multiples	
		$query = "UPDATE glpi_cartridges SET date_use = '".date("Y-m-d")."', FK_glpi_printers = '$pID' WHERE ID='".$db->result($result,0,0)."' AND date_use IS NULL";
		if ($result = $db->query($query)) {
			return true;
		} else {
			return false;
		}
		} else {
			 $_SESSION["MESSAGE_AFTER_REDIRECT"]=$lang["cartridges"][34];
			return false;		
		}
	}

	/**
	* UnLink a cartridge linked to a printer
	*
	* UnLink the cartridge identified by $ID
	*
	*@param $ID : cartridge identifier
	*
	*@return boolean
	*
	**/
	function uninstall($ID) {

		global $db;
		$query = "UPDATE glpi_cartridges SET date_out = '".date("Y-m-d")."' WHERE ID='$ID'";
		if ($result = $db->query($query)) {
			return true;
		} else {
			return false;
		}
	}


}

?>