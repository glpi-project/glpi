<?php
/*
* @version $Id$
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.
 
 http://indepnet.net/   http://glpi-project.org
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

	global $lang, $cfg_glpi;

	$connect = new Connection;
		
	// Is global connection ?
	$global=0;
	$ci=new CommonItem();
	if (haveTypeRight($type,"r")){
		$canedit=haveTypeRight($type,"w");

		$ci->getFromDB($type,$ID);
		$global=$ci->obj->fields['is_global'];
		
		$connect->type=$type;
		$computers = $connect->getComputerContact($ID);

		echo "<br><div align='center'><table width='50%' class='tab_cadre'><tr><th colspan='2'>";
		echo $lang["connect"][0].":";
		echo "</th></tr>";

		if ($computers&&count($computers)>0) {
			foreach ($computers as $key => $computer){
				if ($connect->getComputerData($computer)){
					echo "<tr><td class='tab_bg_1".($connect->deleted=='Y'?"_2":"")."'><b>".$lang["help"][25].": ";
					echo "<a href=\"".$cfg_glpi["root_doc"]."/front/computer.form.php?ID=".$connect->device_ID."\">";
					echo $connect->device_name;
					if ($cfg_glpi["view_ID"]||empty($connect->device_name)) echo " (".$connect->device_ID.")";
					echo "</a>";
					echo "</b></td>";
					echo "<td class='tab_bg_2".($connect->deleted=='Y'?"_2":"")."' align='center'><b>";
					if ($canedit)
						echo "<a href=\"$target?disconnect=1&amp;ID=".$key."\">".$lang["buttons"][10]."</a>";
					else echo "&nbsp;";
					echo "</b>";
					}
			}
		} else {
			echo "<tr><td class='tab_bg_1'><b>".$lang["help"][25].": </b>";
			echo "<i>".$lang["connect"][1]."</i>";
			echo "</td>";
			echo "<td class='tab_bg_2' align='center'>";
			if ($canedit){
				echo "<form method='post' action=\"$target\">";
				echo "<input type='hidden' name='connect' value='connect'>";
				echo "<input type='hidden' name='sID' value='$ID'>";
				echo "<input type='hidden' name='device_type' value='$type'>";
				dropdownConnect(COMPUTER_TYPE,$type,"item");
				echo "<input type='submit' value=\"".$lang["buttons"][9]."\" class='submit'>";
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
			dropdownConnect(COMPUTER_TYPE,$type,"item");
			echo "<input type='submit' value=\"".$lang["buttons"][9]."\" class='submit'>";

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
* @return nothing
*/
function Disconnect($ID) {
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
*/
function Connect($sID,$cID,$type) {
	global $lang;
	// Makes a direct connection

	$connect = new Connection;
	$connect->end1=$sID;
	$connect->end2=$cID;
	$connect->type=$type;
	$newID=$connect->addtoDB();
	// Mise a jour lieu du periph si n�essaire
	$dev=new CommonItem();
	$dev->getFromDB($type,$sID);

	if (!isset($dev->obj->fields["is_global"])||!$dev->obj->fields["is_global"]){
		$comp=new Computer();
		$comp->getFromDB($cID);
		if ($comp->fields['location']!=$dev->obj->fields['location']){
			$updates[0]="location";
			$dev->obj->fields['location']=$comp->fields['location'];
			$dev->obj->updateInDB($updates);
			if (!empty($_SESSION["MESSAGE_AFTER_REDIRECT"])) $_SESSION["MESSAGE_AFTER_REDIRECT"].="<br>";
			$_SESSION["MESSAGE_AFTER_REDIRECT"]=$lang["computers"][48];
		}
		if ($comp->fields['FK_users']!=$dev->obj->fields['FK_users']||$comp->fields['FK_groups']!=$dev->obj->fields['FK_groups']){
			$updates[0]="FK_users";
			$updates[1]="FK_groups";
			$dev->obj->fields['FK_users']=$comp->fields['FK_users'];
			$dev->obj->fields['FK_groups']=$comp->fields['FK_groups'];
			$dev->obj->updateInDB($updates);
			if (!empty($_SESSION["MESSAGE_AFTER_REDIRECT"])) $_SESSION["MESSAGE_AFTER_REDIRECT"].="<br>";
			$_SESSION["MESSAGE_AFTER_REDIRECT"]=$lang["computers"][50];
		}

		if ($comp->fields['contact']!=$dev->obj->fields['contact']||$comp->fields['contact_num']!=$dev->obj->fields['contact_num']){
			$updates[0]="contact";
			$updates[1]="contact_num";
			$dev->obj->fields['contact']=$comp->fields['contact'];
			$dev->obj->fields['contact_num']=$comp->fields['contact_num'];
			$dev->obj->updateInDB($updates);
			if (!empty($_SESSION["MESSAGE_AFTER_REDIRECT"])) $_SESSION["MESSAGE_AFTER_REDIRECT"].="<br>";
			$_SESSION["MESSAGE_AFTER_REDIRECT"]=$lang["computers"][49];
		}
	}
	return $newID;	
}

function getNumberConnections($type,$ID){
	global $db;
	$query = "SELECT count(*) FROM glpi_connect_wire INNER JOIN glpi_computers ON ( glpi_connect_wire.end2=glpi_computers.ID ) WHERE glpi_connect_wire.end1 = '$ID' AND glpi_connect_wire.type = '$type' AND glpi_computers.deleted='N' AND glpi_computers.is_template='0'";
	
	$result = $db->query($query);
	
	if ($db->numrows($result)!=0){
		return $db->result($result,0,0);
	} else return 0;
	
}

function unglobalizeDevice($device_type,$ID){
	global $db;
	$ci=new CommonItem();
	// Update item to unit management :
	$ci->getFromDB($device_type,$ID);
	if ($ci->obj->fields["is_global"]){
		$input=array("ID"=>$ID,"is_global"=>"0");
		$ci->obj->update($input);
	
		// Get connect_wire for this connection
		$query = "SELECT glpi_connect_wire.ID AS connectID FROM glpi_connect_wire WHERE glpi_connect_wire.end1 = '$ID' AND glpi_connect_wire.type = '$device_type'";
		$result=$db->query($query);
		if (($nb=$db->numrows($result))>1){
			$si=new StateItem();
			$si->getfromDB($device_type,$ID,0);
	
			for ($i=1;$i<$nb;$i++){
				// Get ID of the computer
				if ($data=$db->fetch_array($result)){
					// Add new Item
					unset($ci->obj->fields["ID"]);
					if ($newID=$ci->obj->add(array("ID"=>$ID,"state"=>$si->fields["state"]))){
						// Update Connection
						$query2="UPDATE glpi_connect_wire SET end1='$newID' WHERE ID='".$data["connectID"]."'";
						$db->query($query2);
					}
					
				}
			}
			
		}
	}
}

?>
