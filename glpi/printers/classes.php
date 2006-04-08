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
// CLASSES Printers


class Printer  extends CommonDBTM {

	function Printer () {
		$this->table="glpi_printers";
		$this->type=PRINTER_TYPE;
		$this->dohistory=true;

	}	

	function defineOnglets($withtemplate){
		global $lang,$cfg_glpi;
		$ong= array(	1 => $lang["title"][26],
				3 => $lang["title"][27],
				4 => $lang["Menu"][26],
				5 => $lang["title"][25],
			);

		if(empty($withtemplate)){
			$ong[6]=$lang["title"][28];
			$ong[7]=$lang["title"][34];
			$ong[10]=$lang["title"][37];
			$ong[12]=$lang["title"][38];

		}	
		return $ong;
	}


	function prepareInputForUpdate($input) {
		// set new date.
		$input["date_mod"] = date("Y-m-d H:i:s");
	
		return $input;
	}

	function post_updateItem($input,$updates,$history=1) {

		if(isset($input["state"])){
			if (isset($input["is_template"])&&$input["is_template"]==1){
				updateState(PRINTER_TYPE,$input["ID"],$input["state"],1,0);
			}else {
				updateState(PRINTER_TYPE,$input["ID"],$input["state"],0,$history);
			}
		}
	}

	function prepareInputForAdd($input) {
		// set new date.
		$input["date_mod"] = date("Y-m-d H:i:s");
 
		// dump status
		$input["_oldID"]=$input["ID"];
		unset($input['withtemplate']);
		unset($input['ID']);
	
		// Manage state
		$input["_state"]=-1;
		if (isset($input["state"])){
			$input["_state"]=$input["state"];
			unset($input["state"]);
		}

		return $input;
	}

	function postAddItem($newID,$input) {
		global $db;
		// Add state
		if ($input["_state"]>0){
			if (isset($input["is_template"])&&$input["is_template"]==1)
				updateState(PRINTER_TYPE,$newID,$input["_state"],1,0);
			else updateState(PRINTER_TYPE,$newID,$input["_state"],0,0);
		}

		// ADD Infocoms
		$ic= new Infocom();
		if ($ic->getFromDBforDevice(PRINTER_TYPE,$input["_oldID"])){
			$ic->fields["FK_device"]=$newID;
			unset ($ic->fields["ID"]);
			$ic->addToDB();
		}

		// ADD Ports
		$query="SELECT ID from glpi_networking_ports WHERE on_device='".$input["_oldID"]."' AND device_type='".PRINTER_TYPE."';";
		$result=$db->query($query);
		if ($db->numrows($result)>0){
		
			while ($data=$db->fetch_array($result)){
				$np= new Netport();
				$np->getFromDB($data["ID"]);
				unset($np->fields["ID"]);
				unset($np->fields["ifaddr"]);
				unset($np->fields["ifmac"]);
				unset($np->fields["netpoint"]);
				$np->fields["on_device"]=$newID;
				$np->addToDB();
				}
		}

		// ADD Contract				
		$query="SELECT FK_contract from glpi_contract_device WHERE FK_device='".$input["_oldID"]."' AND device_type='".PRINTER_TYPE."';";
		$result=$db->query($query);
		if ($db->numrows($result)>0){
		
			while ($data=$db->fetch_array($result))
				addDeviceContract($data["FK_contract"],PRINTER_TYPE,$newID);
		}
	
		// ADD Documents			
		$query="SELECT FK_doc from glpi_doc_device WHERE FK_device='".$input["_oldID"]."' AND device_type='".PRINTER_TYPE."';";
		$result=$db->query($query);
		if ($db->numrows($result)>0){
			
			while ($data=$db->fetch_array($result))
				addDeviceDocument($data["FK_doc"],PRINTER_TYPE,$newID);
		}

	}


	function cleanDBonPurge($ID) {

		$query = "SELECT ID FROM glpi_networking_ports WHERE (on_device = '$ID' AND device_type = '".PRINTER_TYPE."')";
		$result = $db->query($query);
		while ($data = $db->fetch_array($result)){
			$q = "DELETE FROM glpi_networking_wire WHERE (end1 = '".$data["ID"]."' OR end2 = '".$data["ID"]."')";
			$result2 = $db->query($q);					
		}

		$query2 = "DELETE FROM glpi_networking_ports WHERE (on_device = $ID AND device_type = '".PRINTER_TYPE."')";
		$result2 = $db->query($query2);
			
		$query2 = "DELETE from glpi_connect_wire WHERE (end1 = '$ID' AND type = '".PRINTER_TYPE."')";
		$result2 = $db->query($query2);
				
							
		$query="select * from glpi_reservation_item where (device_type='".PRINTER_TYPE."' and id_device='$ID')";
		if ($result = $db->query($query)) {
			if ($db->numrows($result)>0)
				deleteReservationItem(array("ID"=>$db->result($result,0,"ID")));
		}
			
		$query = "DELETE FROM glpi_infocoms WHERE (FK_device = '$ID' AND device_type='".PRINTER_TYPE."')";
		$result = $db->query($query);

		$job=new Job;

		$query = "SELECT * FROM glpi_tracking WHERE (computer = '$ID'  AND device_type='".PRINTER_TYPE."')";
		$result = $db->query($query);
		$number = $db->numrows($result);
		$i=0;
		while ($i < $number) {
 			$job->deleteFromDB($db->result($result,$i,"ID"));
			$i++;
		}

		$query = "DELETE FROM glpi_state_item WHERE (id_device = '$ID' AND device_type='".PRINTER_TYPE."')";
		$result = $db->query($query);

		$query = "DELETE FROM glpi_contract_device WHERE (FK_device = '$ID' AND device_type='".PRINTER_TYPE."')";
		$result = $db->query($query);

		$query = "UPDATE glpi_cartridges  SET FK_glpi_printers = NULL WHERE (FK_glpi_printers='$ID')";
		$result = $db->query($query);
			
	}

}

?>
