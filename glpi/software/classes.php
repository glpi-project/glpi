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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
// CLASSES Software

class Software  extends CommonDBTM {

	function Software () {
		$this->table="glpi_software";
	}
	
	function cleanDBonPurge($ID) {

		global $db;
		
		$query = "DELETE FROM glpi_infocoms WHERE (FK_device = '$ID' AND device_type='".SOFTWARE_TYPE."')";
		$result = $db->query($query);

		$query = "DELETE FROM glpi_contract_device WHERE (FK_device = '$ID' AND device_type='".SOFTWARE_TYPE."')";
		$result = $db->query($query);

		$job=new Job;

		$query = "SELECT * FROM glpi_tracking WHERE (computer = '$ID'  AND device_type='".SOFTWARE_TYPE."')";
		$result = $db->query($query);
		$number = $db->numrows($result);
		$i=0;
		while ($i < $number) {
			$job->deleteFromDB($db->result($result,$i,"ID"));
			$i++;
		}

		// Delete all Licenses
		$query2 = "SELECT ID FROM glpi_licenses WHERE (sID = '$ID')";
	
		if ($result2 = $db->query($query2)) {
			$i=0;
			while ($i < $db->numrows($result2)) {
				$lID = $db->result($result2,$i,"ID");
				$lic = new License;
				$lic->deleteFromDB($lID);
				$i++;
			}
		}
	}

	// SPECIFIC FUNCTIONS
	function countInstallations() {
		global $db;
		$query = "SELECT * FROM glpi_inst_software WHERE (sID = ".$this->fields["ID"].")";
		if ($result = $db->query($query)) {
			$number = $db->numrows($result);
			return $number;
		} else {
			return false;
		}
	}
}

class License  extends CommonDBTM {

	function License () {
		$this->table="glpi_licenses";
	}
	
	function updateInDB($updates)  {

		global $db;

		for ($i=0; $i < count($updates); $i++) {
			$query  = "UPDATE glpi_licenses SET ";
			$query .= $updates[$i];
			if ($updates[$i]=="expire"&&$this->fields[$updates[$i]]=="NULL")
				$query .= " = NULL";
			else {
				$query .= "='";
				$query .= $this->fields[$updates[$i]]."'";
			}
			$query .= " WHERE ID='";
			$query .= $this->fields["ID"];	
			$query .= "'";

			$result=$db->query($query);
		}
		
	}
	
	function cleanDBonPurge($ID) {

		global $db;

		$query = "DELETE FROM glpi_infocoms WHERE (FK_device = '$ID' AND device_type='".LICENSE_TYPE."')";
		$result = $db->query($query);

		// Delete Installations
		$query2 = "DELETE FROM glpi_inst_software WHERE (license = '$ID')";
		$db->query($query2);
	}

}
?>
