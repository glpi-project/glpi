<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2008 by the INDEPNET Development Team.

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

class Alert extends CommonDBTM {

	/**
	 * Constructor
	 **/
	function Alert () {
		$this->table="glpi_alerts";
		$this->type=0;
	}
	/**
	 * Retrieve an item from the database
	 *
	 *@param $ID ID of the item to get
	 *@param $type ID of the type to get
	 *@return true if succeed else false
	 * 
	**/	
	function getFromDBForDevice ($type,$ID) {

		// Make new database object and fill variables
		global $DB;
		if (empty($ID)) return false;

		$query = "SELECT * FROM ".$this->table." WHERE (device_type='$type' AND FK_device = '$ID')";

		if ($result = $DB->query($query)) {
			if ($DB->numrows($result)==1){
				$this->fields = $DB->fetch_assoc($result);
				return true;
			} else return false;
		} else {
			return false;
		}
	}
	/**
	 * Clear all alerts of an alert type for an item
	 *
	 *@param $ID ID of the item to clear
	 *@param $device_type ID of the type to clear
	 *@param $alert_type ID of the alert type to clear
	 *@return nothing
	 * 
	**/	
	function clear($device_type,$ID,$alert_type){
		global $DB;

		$query="DELETE FROM ".$this->table." WHERE (device_type='$device_type' AND FK_device = '$ID' AND type='$alert_type')";
		$DB->query($query);
	}

}

?>
