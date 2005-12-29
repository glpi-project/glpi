<?php
/*
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2005 by the INDEPNET Development Team.
 
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

		GLOBAL $lang, $cfg_layout, $cfg_install;

		$connect = new Connection;

		// Is global connection ?
		$global=0;
		if ($type==PERIPHERAL_TYPE){
			$periph=new Peripheral;
			$periph->getFromDB($ID);
			$global=$periph->fields['is_global'];
		} else if ($type==MONITOR_TYPE){
			$mon=new Monitor;
			$mon->getFromDB($ID);
			$global=$mon->fields['is_global'];
		}
		
		$connect->type=$type;
		$computers = $connect->getComputerContact($ID);

		echo "<br><center><table width='50%' class='tab_cadre'><tr><th colspan='2'>";
		echo $lang["connect"][0].":";
		echo "</th></tr>";

		if ($computers&&count($computers)>0) {
			foreach ($computers as $key => $computer){
				if ($connect->getComputerData($computer)){
					echo "<tr><td class='tab_bg_1".($connect->deleted=='Y'?"_2":"")."'><b>".$lang["help"][25].": ";
					echo "<a href=\"".$cfg_install["root"]."/computers/computers-info-form.php?ID=".$connect->device_ID."\">";
					echo $connect->device_name." (".$connect->device_ID.")";
					echo "</a>";
					echo "</b></td>";
					echo "<td class='tab_bg_2".($connect->deleted=='Y'?"_2":"")."' align='center'><b>";
					echo "<a href=\"$target?disconnect=1&amp;ID=".$key."\">".$lang["connect"][3]."</a></b>";
					}
			}
		} else {
			echo "<tr><td class='tab_bg_1'><b>".$lang["help"][25].": </b>";
			echo "<i>".$lang["connect"][1]."</i>";
			echo "</td>";
			echo "<td class='tab_bg_2' align='center'>";
			echo "<form method='post' action=\"$target\">";
			echo "<input type='hidden' name='connect' value='connect'>";
			echo "<input type='hidden' name='sID' value='$ID'>";
			echo "<input type='hidden' name='device_type' value='$type'>";
			dropdownConnect(COMPUTER_TYPE,"item");
			echo "<input type='submit' value=\"".$lang["buttons"][9]."\" class='submit'>";

			echo "</form>";

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
			dropdownConnect(COMPUTER_TYPE,"item");
			echo "<input type='submit' value=\"".$lang["buttons"][9]."\" class='submit'>";

			echo "</form>";

		}

		echo "</td>";
		echo "</tr>";
		echo "</table></center><br>";
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
* @param $target
* @param $sID connection source ID.
* @param $cID computer ID (where the sID would be connected).
* @param $type connection type.
*/
function Connect($target,$sID,$cID,$type) {
	global $lang;
	// Makes a direct connection

	$connect = new Connection;
	$connect->end1=$sID;
	$connect->end2=$cID;
	$connect->type=$type;
	$connect->addtoDB();
	// Mise a jour lieu du periph si nécessaire
	$dev=new CommonItem();
	$dev->getFromDB($type,$sID);

	if (!isset($dev->obj->fields["is_global"])||!$dev->obj->fields["is_global"]){
		$comp=new Computer();
		$comp->getFromDB($cID);
		if ($comp->fields['location']!=$dev->obj->fields['location']){
			$updates[0]="location";
			$dev->obj->fields['location']=$comp->fields['location'];
			$dev->obj->updateInDB($updates);
			$_SESSION["MESSAGE_AFTER_REDIRECT"]=$lang["computers"][48];
		}
		if ($comp->fields['contact']!=$dev->obj->fields['contact']||$comp->fields['contact_num']!=$dev->obj->fields['contact_num']){
			$updates[0]="contact";
			$updates[1]="contact_num";
			$dev->obj->fields['contact']=$comp->fields['contact'];
			$dev->obj->fields['contact_num']=$comp->fields['contact_num'];
			$dev->obj->updateInDB($updates);
			$_SESSION["MESSAGE_AFTER_REDIRECT"]=$lang["computers"][49];
		}
	}
	
}

?>
