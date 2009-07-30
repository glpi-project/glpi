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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}

/**
 *  Connection class used to connect computer to peripherals, printers and monitors
 */
class Connection {

	//! Connection ID
	var $ID				= 0;
	//! Connected Item  ID
	var $items_id			= 0;
	//! Computer ID
	var $computers_id			= 0;
	//! Connected Item Type
	var $itemtype			= 0;
	//! Name of the computer
	var $device_name	= "";
	//! ID of the computer
	var $device_ID		= 0;
	//! Is the computer Deleted
	var $is_deleted ='0';
	//! Is the computer a template
	var $is_template ='0';

	/**
	 * Get computers connected to a item
	 *
	 * $itemtype must set before
	 *
	 * @param $ID ID of the computer
   * @param $type type of the items searched
	 * @return array of ID of connected items
	 */
	function getComputersContact ($itemtype,$ID) {
		global $DB;
		$query = "SELECT glpi_computers_items.ID as connectID, glpi_computers_items.computers_id, glpi_computers.*
			FROM glpi_computers_items 
			INNER JOIN glpi_computers ON (glpi_computers.ID = glpi_computers_items.computers_id)
			 WHERE (glpi_computers_items.items_id = '$ID' AND glpi_computers_items.itemtype = '$itemtype'
				AND glpi_computers.is_template = '0')" .
				getEntitiesRestrictRequest(" AND", "glpi_computers");
				
		if ($result=$DB->query($query)) {
			if ($DB->numrows($result)==0) return false;
			$ret=array();
			while ($data = $DB->fetch_array($result)){
				if (isset($data["computers_id"])) {
					$ret[$data["connectID"]] = $data;
				}
			}
			return $ret;
		} else {
			return false;
		}
	}

	/**
	 * Delete connection
	 *
	 * @param $ID Connection ID
	 * @return boolean
	 */
	function deleteFromDB($ID) {

		global $DB;

		$query = "DELETE FROM glpi_computers_items WHERE (ID = '$ID')";
		if ($result = $DB->query($query)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Add a connection
	 *
	 * items_id, computers_id and itemtype must be set
	 *
	 * @return integer : ID of added connection
	 */
	function addToDB() {
		global $DB;

		// Build query
		$query = "INSERT INTO glpi_computers_items (items_id,computers_id,itemtype)
                     VALUES ('$this->items_id','$this->computers_id','$this->itemtype')";
		$result=$DB->query($query);
		return $DB->insert_id();
	}

}
?>
