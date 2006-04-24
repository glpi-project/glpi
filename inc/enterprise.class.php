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

 


class Enterprise extends CommonDBTM {

	function Enterprise () {
		$this->table="glpi_enterprises";
		$this->type=ENTERPRISE_TYPE;
	}


	function cleanDBonPurge($ID) {

		global $db;

		$job=new Job;

		// Delete all enterprises associations from infocoms and contract
		$query3 = "DELETE FROM glpi_contract_enterprise WHERE (FK_enterprise = \"$ID\")";
		$result3 = $db->query($query3);
				
		// Delete all contact enterprise associations
		$query2 = "DELETE FROM glpi_contact_enterprise WHERE (FK_enterprise = \"$ID\")";
		$result2 = $db->query($query2);
					
		/// TODO : UPDATE ALL FK_manufacturer to NULL
	}
	
	function defineOnglets($withtemplate){
		global $lang,$cfg_glpi;

			if(haveRight("contact_enterprise","r"))
				$ong[1] = $lang["title"][26];
			if (haveRight("contract_infocom","r"))	
				$ong[4] = $lang["Menu"][26];
			if (haveRight("document","r"))
				$ong[5] = $lang["title"][25];
			if (haveRight("show_ticket","1"))	
				$ong[6] = $lang["title"][28];
			if (haveRight("link","r"))
				$ong[7] = $lang["title"][34];
			if (haveRight("notes","r"))
				$ong[10] = $lang["title"][37];
		return $ong;
	}


	// SPECIFIC FUNCTION

	function countContacts() {
		global $db;
		$query = "SELECT * FROM glpi_contact_enterprise WHERE (FK_enterprise = '".$this->fields["ID"]."')";
		if ($result = $db->query($query)) {
			$number = $db->numrows($result);
			return $number;
		} else {
			return false;
		}
	}

	

	
}

?>