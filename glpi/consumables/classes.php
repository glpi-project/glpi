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

}

?>