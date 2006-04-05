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

include ("_relpos.php");


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
	}

	function post_getEmpty () {
		global $cfg_glpi;
		$this->fields["alarm"]=$cfg_glpi["cartridges_alarm"];
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

}

?>