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

/**
 * Prints a direct connection to a computer
 *
 * @param $target the page where we'll print out this.
 * @param $ID the connection ID
 * @param $type the connection type
 * @return nothing (print out a table)
 *
 */
function showConnect($target,$ID,$type) {
	// Prints a direct connection to a computer

	global $LANG, $CFG_GLPI;

	$connect = new Connection;

	// Is global connection ?
	$global=0;
	$ci=new CommonItem();
	if (haveTypeRight($type,"r")){
		$canedit=haveTypeRight($type,"w");

		$ci->getFromDB($type,$ID);
		$global=$ci->getField('is_global');

		$computers = $connect->getComputerContact($type,$ID);
		if (!$computers) $nb=0;
		else $nb=count($computers);

		echo "<br><div class='center'><table width='50%' class='tab_cadre'><tr><th colspan='2'>";
		echo $LANG["connect"][0].": ".$nb;
		echo "</th></tr>";

		if ($computers&&count($computers)>0) {
			foreach ($computers as $key => $computer){
				if ($connect->getComputerData($computer)){
					echo "<tr><td class='tab_bg_1".($connect->deleted?"_2":"")."'><strong>".$LANG["help"][25].": ";
					echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/computer.form.php?ID=".$connect->device_ID."\">";
					echo $connect->device_name;
					if ($CFG_GLPI["view_ID"]||empty($connect->device_name)) echo " (".$connect->device_ID.")";
					echo "</a>";
					echo "</strong></td>";
					echo "<td class='tab_bg_2".($connect->deleted?"_2":"")."' align='center'><strong>";
					if ($canedit)
						echo "<a href=\"$target?disconnect=1&amp;cID=".$connect->device_ID."&amp;ID=".$key."\">".$LANG["buttons"][10]."</a>";
					else echo "&nbsp;";
					echo "</strong>";
				}
			}
		} else {
			echo "<tr><td class='tab_bg_1'><strong>".$LANG["help"][25].": </strong>";
			echo "<i>".$LANG["connect"][1]."</i>";
			echo "</td>";
			echo "<td class='tab_bg_2' align='center'>";
			if ($canedit){
				echo "<form method='post' action=\"$target\">";
				echo "<input type='hidden' name='connect' value='connect'>";
				echo "<input type='hidden' name='sID' value='$ID'>";
				echo "<input type='hidden' name='device_type' value='$type'>";
				dropdownConnect(COMPUTER_TYPE,$type,"item",$ci->getField('FK_entities'));
				echo "<input type='submit' value=\"".$LANG["buttons"][9]."\" class='submit'>";
				echo "</form>";
			} else echo "&nbsp;";

		}

		if ($global&&$computers&&count($computers)>0){
			echo "</td>";
			echo "</tr>";
			echo "<tr><td class='tab_bg_1'>&nbsp;";
			echo "</td>";
			echo "<td class='tab_bg_2' align='center'>";
			echo "<form method='post' action=\"$target\">";
			echo "<input type='hidden' name='connect' value='connect'>";
			echo "<input type='hidden' name='sID' value='$ID'>";
			echo "<input type='hidden' name='device_type' value='$type'>";
			dropdownConnect(COMPUTER_TYPE,$type,"item",$ci->getField('FK_entities'));
			echo "<input type='submit' value=\"".$LANG["buttons"][9]."\" class='submit'>";

			echo "</form>";

		}

		echo "</td>";
		echo "</tr>";
		echo "</table></div><br>";
	}
}

/**
 * Disconnects a direct connection
 * 
 *
 * @param $ID the connection ID to disconnect.
 * @param $dohistory make history
 * @param $ocs_server_id ocs server id of the computer if know
 * @return nothing
 */
function Disconnect($ID,$dohistory=1,$ocs_server_id=0) {
	global $DB,$LINK_ID_TABLE,$LANG,$CFG_GLPI;


	//Get info about the periph
	$query = "SELECT end1,end2, type FROM glpi_connect_wire WHERE ID='$ID'";		
	$res = $DB->query($query);

	if($DB->numrows($res)>0){
		$data = $DB->fetch_array($res);

		$decoConf = "";
		$type_elem= $data["type"]; 
		$id_elem= $data["end1"]; 
		$id_parent= $data["end2"]; 
		$table = $LINK_ID_TABLE[$type_elem];


		//Get the computer name
		$computer = new Computer;
		$computer->getFromDB($id_parent);
		//Get device fields
		$device=new CommonItem();
		$device->getFromDB($type_elem,$id_elem);
				
		if ($dohistory){

			//History log
			//Log deconnection in the computer's history
			$changes[0]='0';
			if ($device->getField("serial")){
				$changes[1]=addslashes($device->getField("name")." -- ".$device->getField("serial"));
			} else {
				$changes[1]=addslashes($device->getField("name"));
			}
					
			$changes[2]="";

			historyLog ($id_parent,COMPUTER_TYPE,$changes,$type_elem,HISTORY_DISCONNECT_DEVICE);
				
			//Log deconnection in the device's history
			$changes[1]=addslashes($computer->fields["name"]);
			historyLog ($id_elem,$type_elem,$changes,COMPUTER_TYPE,HISTORY_DISCONNECT_DEVICE);
		}

		if (!$device->getField('is_global')){
			
			$updates=array();
			if ($CFG_GLPI["autoclean_link_location"] && $device->getField('location')){
				$updates[]="location";
				$device->obj->fields['location']=0;
			}
			if ($CFG_GLPI["autoupdate_link_user"] && $device->getField('FK_users')) {
				$updates[]="FK_users";
				$device->obj->fields['FK_users']=0;	
			}
			if ($CFG_GLPI["autoclean_link_group"] && $device->getField('FK_groups')){
				$updates[]="FK_groups";
				$device->obj->fields['FK_groups']=0;
			}
			if ($CFG_GLPI["autoupdate_link_contact"] && $device->getField('contact')){
				$updates[]="contact";
				$device->obj->fields['contact']="";
			}
			if ($CFG_GLPI["autoupdate_link_contact"] && $device->getField('contact_num')){
				$updates[]="contact_num";
				$device->obj->fields['contact_num']="";
			}
			if (count($updates)) {
				$device->obj->updateInDB($updates);
			}
		}

		if ($ocs_server_id==0){
			$ocs_server_id = getOCSServerByMachineID($data["end2"]);
		}
		if ($ocs_server_id>0){

			//Get OCS configuration
			$ocs_config = getOcsConf($ocs_server_id);
				
			//Get the management mode for this device
			$mode = getMaterialManagementMode($ocs_config,$type_elem);
			$decoConf= $ocs_config["deconnection_behavior"];

			//Change status if : 
			// 1 : the management mode IS NOT global
			// 2 : a deconnection's status have been defined 
			// 3 : unique with serial
			if($mode >= 2 && strlen($decoConf)>0){
				//Delete periph from glpi
				if($decoConf == "delete")
					$device->obj->delete($id_elem);
							
				//Put periph in trash
				elseif($decoConf == "trash")
				{
					$tmp["ID"]=$id_elem;
					$tmp["deleted"]=1;
					$device->obj->update($tmp,$dohistory);
				}
				//Change status
				else {
					//get id status
					$query = "SELECT ID from glpi_dropdown_state WHERE name='$decoConf'";			
					$result = $DB->query($query );
					if($DB->numrows($result)>0){
						$id_res = $DB->fetch_array($result);
						$id_status= $id_res["ID"]; 
	
						$tmp["ID"]=$id_elem;
						$tmp["state"]=$id_status;
						
						$device->obj->update($tmp,$dohistory);
					}				
				}			
			}		
		} // $ocs_server_id>0
	}
	// Disconnects a direct connection
	$connect = new Connection;
	$connect->deletefromDB($ID);
}


/**
 *
 * Makes a direct connection
 *
 *
 *
 * @param $sID connection source ID.
 * @param $cID computer ID (where the sID would be connected).
 * @param $type connection type.
 * @param $dohistory store chaneg in history ?
 */
function Connect($sID,$cID,$type,$dohistory=1) {
	global $LANG,$CFG_GLPI;
	// Makes a direct connection

	$connect = new Connection;
	$connect->end1=$sID;
	$connect->end2=$cID;
	$connect->type=$type;
	$newID=$connect->addtoDB();
	// Mise a jour lieu du periph si n�essaire
	$dev=new CommonItem();
	$dev->getFromDB($type,$sID);

	if ($dohistory){
		$changes[0]='0';
		$changes[1]="";
		if ($dev->getField("serial")){
			$changes[2]=addslashes($dev->getField("name")." -- ".$dev->getField("serial"));
		} else {
			$changes[2]=addslashes($dev->getField("name"));
		}
					
		//Log connection in the device's history
		historyLog ($cID,COMPUTER_TYPE,$changes,$type,HISTORY_CONNECT_DEVICE);
	}

	if (!$dev->getField('is_global')){
		$comp=new Computer();
		$comp->getFromDB($cID);

		if ($dohistory){
			$changes[2]=addslashes($comp->fields["name"]);
			historyLog ($sID,$type,$changes,COMPUTER_TYPE,HISTORY_CONNECT_DEVICE);
		}
		
		if ($CFG_GLPI["autoupdate_link_location"]&&$comp->fields['location']!=$dev->getField('location')){
			$updates[0]="location";
			$dev->obj->fields['location']=addslashes($comp->fields['location']);
			$dev->obj->updateInDB($updates);
			addMessageAfterRedirect($LANG["computers"][48],true);
		}
		if (($CFG_GLPI["autoupdate_link_user"]&&$comp->fields['FK_users']!=$dev->getField('FK_users'))
		||($CFG_GLPI["autoupdate_link_group"]&&$comp->fields['FK_groups']!=$dev->getField('FK_groups'))){
			if ($CFG_GLPI["autoupdate_link_user"]){
				$updates[]="FK_users";
				$dev->obj->fields['FK_users']=$comp->fields['FK_users'];
			}
			if ($CFG_GLPI["autoupdate_link_group"]){
				$updates[]="FK_groups";
				$dev->obj->fields['FK_groups']=$comp->fields['FK_groups'];
			}
			$dev->obj->updateInDB($updates);
			addMessageAfterRedirect($LANG["computers"][50],true);
		}

		if ($CFG_GLPI["autoupdate_link_contact"]
		&&($comp->fields['contact']!=$dev->getField('contact')||$comp->fields['contact_num']!=$dev->getField('contact_num'))){
			$updates[0]="contact";
			$updates[1]="contact_num";
			$dev->obj->fields['contact']=addslashes($comp->fields['contact']);
			$dev->obj->fields['contact_num']=addslashes($comp->fields['contact_num']);
			$dev->obj->updateInDB($updates);
			addMessageAfterRedirect($LANG["computers"][49],true);
		}
	}
	return $newID;	
}

function getNumberConnections($type,$ID){
	global $DB;
	$query = "SELECT count(*) FROM glpi_connect_wire INNER JOIN glpi_computers ON ( glpi_connect_wire.end2=glpi_computers.ID ) WHERE glpi_connect_wire.end1 = '$ID' AND glpi_connect_wire.type = '$type' AND glpi_computers.deleted='0' AND glpi_computers.is_template='0'";

	$result = $DB->query($query);

	if ($DB->numrows($result)!=0){
		return $DB->result($result,0,0);
	} else return 0;

}

function unglobalizeDevice($device_type,$ID){
	global $DB;
	$ci=new CommonItem();
	// Update item to unit management :
	$ci->getFromDB($device_type,$ID);
	if ($ci->getField('is_global')){
		$input=array("ID"=>$ID,"is_global"=>"0");
		$ci->obj->update($input);

		// Get connect_wire for this connection
		$query = "SELECT glpi_connect_wire.ID AS connectID FROM glpi_connect_wire WHERE glpi_connect_wire.end1 = '$ID' AND glpi_connect_wire.type = '$device_type'";
		$result=$DB->query($query);
		if (($nb=$DB->numrows($result))>1){
			for ($i=1;$i<$nb;$i++){
				// Get ID of the computer
				if ($data=$DB->fetch_array($result)){
					// Add new Item
					unset($ci->obj->fields['ID']);
					if ($newID=$ci->obj->add(array("ID"=>$ID))){
						// Update Connection
						$query2="UPDATE glpi_connect_wire SET end1='$newID' WHERE ID='".$data["connectID"]."'";
						$DB->query($query2);
					}

				}
			}

		}
	}
}

?>
