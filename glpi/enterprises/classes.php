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


class Enterprise extends CommonDBTM {

	function Enterprise () {
		$this->table="glpi_enterprises";
	}


	function cleanDBonPurge($ID) {

		global $db;

		$job=new Job;

		$query = "SELECT * FROM glpi_tracking WHERE (computer = '$ID'  AND device_type='".ENTERPRISE_TYPE."')";
		$result = $db->query($query);
		$number = $db->numrows($result);
		$i=0;
		while ($i < $number) {
			$job->deleteinDB($db->result($result,$i,"ID"));
			$i++;
		}

				
		// Delete all enterprises associations from infocoms and contract
		$query3 = "DELETE FROM glpi_contract_enterprise WHERE (FK_enterprise = \"$ID\")";
		$result3 = $db->query($query3);
				
		// Delete all contact enterprise associations
		$query2 = "DELETE FROM glpi_contact_enterprise WHERE (FK_enterprise = \"$ID\")";
		$result2 = $db->query($query2);
					
		/// TODO : UPDATE ALL FK_manufacturer to NULL
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