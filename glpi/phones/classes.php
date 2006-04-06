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
// CLASSES peripherals


class Phone extends CommonDBTM {

	function Phone () {
		$this->table="glpi_phones";
	}


	function cleanDBonPurge($ID) {

		global $db;
		
		$query="select * from glpi_reservation_item where (device_type='".PHONE_TYPE."' and id_device='$ID')";
		if ($result = $db->query($query)) {
			if ($db->numrows($result)>0)
			deleteReservationItem(array("ID"=>$db->result($result,0,"ID")));
		}

		$query = "DELETE FROM glpi_infocoms WHERE (FK_device = '$ID' AND device_type='".PHONE_TYPE."')";
		$result = $db->query($query);

		$job=new Job;

		$query = "SELECT * FROM glpi_tracking WHERE (computer = '$ID'  AND device_type='".PHONE_TYPE."')";
		$result = $db->query($query);
		$number = $db->numrows($result);
		$i=0;
		while ($i < $number) {
			$job->deleteFromDB($db->result($result,$i,"ID"));
			$i++;
		}

		$query = "DELETE FROM glpi_state_item WHERE (id_device = '$ID' AND device_type='".PHONE_TYPE."')";
		$result = $db->query($query);
			
		$query2 = "DELETE from glpi_connect_wire WHERE (end1 = '$ID' AND type = '".PHONE_TYPE."')";
		$result2 = $db->query($query2);
				
		$query = "DELETE FROM glpi_contract_device WHERE (FK_device = '$ID' AND device_type='".PHONE_TYPE."')";
		$result = $db->query($query);
	}

}

?>