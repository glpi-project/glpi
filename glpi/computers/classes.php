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
// CLASSES Computers


class Computer extends CommonDBTM {


	//format $device = array(ID,"ID type periph","ID dans la table device","valeur de specificity")
	var $devices	= array();

	function Computer () {
		$this->table="glpi_computers";
	}
	
	function getFromDBwithDevices ($ID) {

		global $db;

		if ($this->getFromDB($ID)){
			$query = "SELECT ID, device_type, FK_device, specificity FROM glpi_computer_device WHERE FK_computers = '$ID' ORDER BY device_type, ID";
			if ($result = $db->query($query)) {
				if ($db->numrows($result)>0) {
					$i = 0;
					while($data = $db->fetch_array($result)) {
						$this->devices[$i] = array("compDevID"=>$data["ID"],"devType"=>$data["device_type"],"devID"=>$data["FK_device"],"specificity"=>$data["specificity"]);
						$i++;
					}
				}
			return true;
			} 
		}
		return false;
	}
	

	function post_updateInDB($updates)  {
		global $db,$lang;
		

		for ($i=0; $i < count($updates); $i++) {
		
		// Mise a jour du contact des éléments rattachés
		if ($updates[$i]=="contact" ||$updates[$i]=="contact_num"){
			$items=array(PRINTER_TYPE,MONITOR_TYPE,PERIPHERAL_TYPE,PHONE_TYPE);
			$ci=new CommonItem();
			$update_done=false;
			$updates3[0]="contact";
			$updates3[1]="contact_num";
			
			foreach ($items as $t){
				$query = "SELECT * from glpi_connect_wire WHERE end2='".$this->fields["ID"]."' AND type='".$t."'";
				if ($result=$db->query($query)) {
					$resultnum = $db->numrows($result);
					if ($resultnum>0) {
						for ($j=0; $j < $resultnum; $j++) {
							$tID = $db->result($result, $j, "end1");
							$ci->getfromDB($t,$tID);
							if (!$ci->obj->fields['is_global']){
								$ci->obj->fields['contact']=$this->fields['contact'];
								$ci->obj->fields['contact_num']=$this->fields['contact_num'];
								$ci->obj->updateInDB($updates3);
								$update_done=true;
							}
						}
					}
				}
			}

		if ($update_done) {
			if (!empty($_SESSION["MESSAGE_AFTER_REDIRECT"])) $_SESSION["MESSAGE_AFTER_REDIRECT"].="<br>";
			$_SESSION["MESSAGE_AFTER_REDIRECT"].=$lang["computers"][49];
		}
		
		}
		
		// Mise a jour du lieu des éléments rattachés
		if ($updates[$i]=="location" && $this->fields[$updates[$i]]!=0){
			$items=array(PRINTER_TYPE,MONITOR_TYPE,PERIPHERAL_TYPE,PHONE_TYPE);
			$ci=new CommonItem();
			$update_done=false;
			$updates2[0]="location";
			
			foreach ($items as $t){
				$query = "SELECT * from glpi_connect_wire WHERE end2='".$this->fields["ID"]."' AND type='".$t."'";
				
				if ($result=$db->query($query)) {
					$resultnum = $db->numrows($result);
					
					if ($resultnum>0) {
						for ($j=0; $j < $resultnum; $j++) {
							$tID = $db->result($result, $j, "end1");

							$ci->getfromDB($t,$tID);
							if (!$ci->obj->fields['is_global']){
								$ci->obj->fields['location']=$this->fields['location'];
								$ci->obj->updateInDB($updates2);
								$update_done=true;
							}
						}
					}
				}
			}
		if ($update_done) {
			if (!empty($_SESSION["MESSAGE_AFTER_REDIRECT"])) $_SESSION["MESSAGE_AFTER_REDIRECT"].="<br>";
			$_SESSION["MESSAGE_AFTER_REDIRECT"].=$lang["computers"][48];
		}

		}
	
		}
		
		
	}
	

	function cleanDBonPurge($ID) {
		global $db;

		$job=new Job;

		$query = "SELECT * FROM glpi_tracking WHERE (computer = '$ID'  AND device_type='".COMPUTER_TYPE."')";
		$result = $db->query($query);
		$number = $db->numrows($result);
		$i=0;
		while ($i < $number) {
	  		$job->deleteinDB($db->result($result,$i,"ID"));
			$i++;
		}

		$query = "DELETE FROM glpi_inst_software WHERE (cID = '$ID')";
		$result = $db->query($query);		

		$query = "DELETE FROM glpi_contract_device WHERE (FK_device = '$ID' AND device_type='".COMPUTER_TYPE."')";
		$result = $db->query($query);

		$query = "DELETE FROM glpi_state_item WHERE (id_device = '$ID' AND device_type='".COMPUTER_TYPE."')";
		$result = $db->query($query);

		$query = "DELETE FROM glpi_infocoms WHERE (FK_device = '$ID' AND device_type='".COMPUTER_TYPE."')";
		$result = $db->query($query);

		$query = "SELECT ID FROM glpi_networking_ports WHERE (on_device = '$ID' AND device_type = '".COMPUTER_TYPE."')";
		$result = $db->query($query);
		while ($data = $db->fetch_array($result)){
			$q = "DELETE FROM glpi_networking_wire WHERE (end1 = '".$data["ID"]."' OR end2 = '".$data["ID"]."')";
			$result2 = $db->query($q);					
		}	

		$query = "DELETE FROM glpi_networking_ports WHERE (on_device = '$ID' AND device_type = '".COMPUTER_TYPE."')";
		$result = $db->query($query);
		$query = "DELETE FROM glpi_connect_wire WHERE (end2 = '$ID')";
		$result = $db->query($query);
				
		$query="select * from glpi_reservation_item where (device_type='".COMPUTER_TYPE."' and id_device='$ID')";
		if ($result = $db->query($query)) {
			if ($db->numrows($result)>0) {
				deleteReservationItem(array("ID"=>$db->result($result,0,"ID")));
			}
		}

		$query = "DELETE FROM glpi_computer_device WHERE (FK_computers = '$ID')";
		$result = $db->query($query);

		$query = "DELETE FROM glpi_ocs_link WHERE (glpi_id = '$ID')";
		$result = $db->query($query);
	}
}


?>
