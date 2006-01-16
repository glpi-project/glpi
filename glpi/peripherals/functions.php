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
 
// Based on:
// IRMA, Information Resource-Management and Administration
// Christian Bauer 
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
// FUNCTIONS peripheral


function titleperipherals(){
                GLOBAL  $lang,$HTMLRel;
                echo "<div align='center'><table border='0'><tr><td>";
                echo "<img src=\"".$HTMLRel."pics/periphs.png\" alt='".$lang["peripherals"][0]."' title='".$lang["peripherals"][0]."'></td><td><a  class='icon_consol' href=\"peripherals-add-select.php\"><b>".$lang["peripherals"][0]."</b></a>";
                echo "</td>";
                echo "<td><a class='icon_consol' href='".$HTMLRel."setup/setup-templates.php?type=".PERIPHERAL_TYPE."'>".$lang["common"][8]."</a></td>";
                echo "</tr></table></div>";
}

function showPeripheralOnglets($target,$withtemplate,$actif){
	global $lang, $HTMLRel;
	
	$template="";
	if(!empty($withtemplate)){
		$template="&amp;withtemplate=$withtemplate";
	}

	echo "<div id='barre_onglets'><ul id='onglet'>";
	echo "<li "; if ($actif=="1"){ echo "class='actif'";} echo  "><a href='$target&amp;onglet=1$template'>".$lang["title"][26]."</a></li>";
	echo "<li "; if ($actif=="4") {echo "class='actif'";} echo "><a href='$target&amp;onglet=4$template'>".$lang["Menu"][26]."</a></li>";
	echo "<li "; if ($actif=="5") {echo "class='actif'";} echo "><a href='$target&amp;onglet=5$template'>".$lang["title"][25]."</a></li>";
	if(empty($withtemplate)){
	echo "<li "; if ($actif=="6") {echo "class='actif'";} echo "><a href='$target&amp;onglet=6$template'>".$lang["title"][28]."</a></li>";
	echo "<li "; if ($actif=="7") {echo "class='actif'";} echo "><a href='$target&amp;onglet=7$template'>".$lang["title"][34]."</a></li>";
	echo "<li "; if ($actif=="10") {echo "class='actif'";} echo "><a href='$target&amp;onglet=10$template'>".$lang["title"][37]."</a></li>";
	echo "<li class='invisible'>&nbsp;</li>";
	echo "<li "; if ($actif=="-1") {echo "class='actif'";} echo "><a href='$target&amp;onglet=-1$template'>".$lang["title"][29]."</a></li>";
	}
	
	echo "<li class='invisible'>&nbsp;</li>";
	
	if (empty($withtemplate)&&preg_match("/\?ID=([0-9]+)/",$target,$ereg)){
	$ID=$ereg[1];
	$next=getNextItem("glpi_peripherals",$ID);
	$prev=getPreviousItem("glpi_peripherals",$ID);
	$cleantarget=preg_replace("/\?ID=([0-9]+)/","",$target);
	if ($prev>0) echo "<li><a href='$cleantarget?ID=$prev'><img src=\"".$HTMLRel."pics/left.png\" alt='".$lang["buttons"][12]."' title='".$lang["buttons"][12]."'></a></li>";
	if ($next>0) echo "<li><a href='$cleantarget?ID=$next'><img src=\"".$HTMLRel."pics/right.png\" alt='".$lang["buttons"][11]."' title='".$lang["buttons"][11]."'></a></li>";
	}
	echo "</ul></div>";
	
}


function showperipheralForm ($target,$ID,$withtemplate='') {

	GLOBAL $cfg_install, $cfg_layout, $lang,$HTMLRel;

	$mon = new Peripheral;

	$mon_spotted = false;

	if(empty($ID) && $withtemplate == 1) {
		if($mon->getEmpty()) $mon_spotted = true;
	} else {
		if($mon->getfromDB($ID)) $mon_spotted = true;
	}

	if($mon_spotted) {
		if(!empty($withtemplate) && $withtemplate == 2) {
			$template = "newcomp";
			$datestring = $lang["computers"][14].": ";
			$date = convDateTime(date("Y-m-d H:i:s"));
		} elseif(!empty($withtemplate) && $withtemplate == 1) { 
			$template = "newtemplate";
			$datestring = $lang["computers"][14].": ";
			$date = convDateTime(date("Y-m-d H:i:s"));
		} else {
			$datestring = $lang["computers"][11].": ";
			$date = convDateTime($mon->fields["date_mod"]);
			$template = false;
		}


	echo "<div align='center'>";
	echo "<form method='post' name=form action=\"$target\">";
	if(strcmp($template,"newtemplate") === 0) {
		echo "<input type=\"hidden\" name=\"is_template\" value=\"1\" />";
	}

	echo "<table width='800' class='tab_cadre' cellpadding='2'>";

		echo "<tr><th align='center' >";

		
		
		if(!$template) {
			echo $lang["peripherals"][29].": ".$mon->fields["ID"];
		}elseif (strcmp($template,"newcomp") === 0) {
			echo $lang["peripherals"][30].": ".$mon->fields["tplname"];
			echo "<input type='hidden' name='tplname' value='".$mon->fields["tplname"]."'>";
		}elseif (strcmp($template,"newtemplate") === 0) {
			echo $lang["common"][6]."&nbsp;: ";
			autocompletionTextField("tplname","glpi_peripherals","tplname",$mon->fields["tplname"],20);	
		}
		
		echo "</th><th  align='center'>".$datestring.$date;
		if (!$template&&!empty($mon->fields['tplname']))
			echo "&nbsp;&nbsp;&nbsp;(".$lang["common"][13].": ".$mon->fields['tplname'].")";
		echo "</th></tr>";

	echo "<tr><td class='tab_bg_1' valign='top'>";

	echo "<table cellpadding='1' cellspacing='0' border='0'>\n";

	echo "<tr><td>".$lang["peripherals"][5].":	</td>";
	echo "<td>";
	autocompletionTextField("name","glpi_peripherals","name",$mon->fields["name"],20);		
	echo "</td></tr>";

	echo "<tr><td>".$lang["peripherals"][6].": 	</td><td>";
		dropdownValue("glpi_dropdown_locations", "location", $mon->fields["location"]);
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["common"][10].": 	</td><td colspan='2'>";
		dropdownUsersID("tech_num", $mon->fields["tech_num"]);
	echo "</td></tr>";
		
	echo "<tr><td>".$lang["peripherals"][7].":	</td><td>";
	autocompletionTextField("contact_num","glpi_peripherals","contact_num",$mon->fields["contact_num"],20);		
	echo "</td></tr>";

	echo "<tr><td>".$lang["peripherals"][8].":	</td><td>";
	autocompletionTextField("contact","glpi_peripherals","contact",$mon->fields["contact"],20);		
	echo "</td></tr>";

		if (!$template){
		echo "<tr><td>".$lang["reservation"][24].":</td><td><b>";
		showReservationForm(PERIPHERAL_TYPE,$ID);
		echo "</b></td></tr>";
		}
		
	echo "<tr><td>".$lang["peripherals"][33].":</td><td>";
	echo "<select name='is_global'>";
	echo "<option value='0' ".(!$mon->fields["is_global"]?" selected":"").">".$lang["peripherals"][32]."</option>";
	echo "<option value='1' ".($mon->fields["is_global"]?" selected":"").">".$lang["peripherals"][31]."</option>";
	echo "</select>";
	echo "</td></tr>";
	echo "</table>";

	echo "</td>\n";	
	echo "<td class='tab_bg_1' valign='top'>";

	echo "<table cellpadding='1' cellspacing='0' border='0'>";

	echo "<tr><td>".$lang["peripherals"][9].": 	</td><td>";
		dropdownValue("glpi_type_peripherals", "type", $mon->fields["type"]);
	echo "</td></tr>";

	echo "<tr><td>".$lang["peripherals"][34].": 	</td><td>";
		dropdownValue("glpi_dropdown_model_peripherals", "model", $mon->fields["model"]);
	echo "</td></tr>";
	
	echo "<tr class='tab_bg_1'><td>".$lang["common"][5].": 	</td><td colspan='2'>";
		dropdownValue("glpi_enterprises","FK_glpi_enterprise",$mon->fields["FK_glpi_enterprise"]);
	echo "</td></tr>";
		
	echo "<tr><td>".$lang["peripherals"][18].":</td><td>";
	autocompletionTextField("brand","glpi_peripherals","brand",$mon->fields["brand"],20);		
	echo "</td></tr>";

	
	echo "<tr><td>".$lang["peripherals"][10].":	</td><td>";
	autocompletionTextField("serial","glpi_peripherals","serial",$mon->fields["serial"],20);		
	echo "</td></tr>";

	echo "<tr><td>".$lang["peripherals"][11].":</td><td>";
	autocompletionTextField("otherserial","glpi_peripherals","otherserial",$mon->fields["otherserial"],20);		
	echo "</td></tr>";

		
		echo "<tr><td>".$lang["state"][0].":</td><td>";
		$si=new StateItem();
		$t=0;
		if ($template) $t=1;
		$si->getfromDB(PERIPHERAL_TYPE,$mon->fields["ID"],$t);
		dropdownValue("glpi_dropdown_state", "state",$si->fields["state"]);
		echo "</td></tr>";
		

	
	echo "</table>";
	echo "</td>\n";	
	echo "</tr>";
	echo "<tr>";
	echo "<td class='tab_bg_1' valign='top' colspan='2'>";

	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'><tr><td valign='top'>";
	echo $lang["peripherals"][12].":	</td>";
	echo "<td align='center'><textarea cols='35' rows='4' name='comments' >".$mon->fields["comments"]."</textarea>";
	echo "</td></tr></table>";

	echo "</td>";
	echo "</tr>";
	
	echo "<tr>";

	if ($template) {

			if (empty($ID)||$withtemplate==2){
			echo "<td class='tab_bg_2' align='center' colspan='2'>\n";
			echo "<input type='hidden' name='ID' value=$ID>";
			echo "<input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'>";
			echo "</td>\n";
			} else {
			echo "<td class='tab_bg_2' align='center' colspan='2'>\n";
			echo "<input type='hidden' name='ID' value=$ID>";
			echo "<input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'>";
			echo "</td>\n";
			}


	} else {

		echo "<td class='tab_bg_2' valign='top' align='center'>";
		echo "<input type='hidden' name='ID' value=\"$ID\">\n";
		echo "<input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'>";
		echo "</td>";
		echo "<td class='tab_bg_2' valign='top'>\n";
		echo "<div align='center'>";
		if ($mon->fields["deleted"]=='N')
		echo "<input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'>";
		else {
		echo "<input type='submit' name='restore' value=\"".$lang["buttons"][21]."\" class='submit'>";
		
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' name='purge' value=\"".$lang["buttons"][22]."\" class='submit'>";
		}
		echo "</div>";
		echo "</td>";
	}
		echo "</tr>";

		echo "</table></form></div>";
	
		return true;	
	}
	else {
                echo "<div align='center'><b>".$lang["peripherals"][17]."</b></div>";
                return false;
        }

}


function updatePeripheral($input) {
	// Update a Peripheral in the database

	$mon = new Peripheral;
	$mon->getFromDB($input["ID"]);

	// set new date and make sure it gets updated
	$updates[0]= "date_mod";
	$mon->fields["date_mod"] = date("Y-m-d H:i:s");

	// Get all flags and fill with 0 if unchecked in form
	foreach ($mon->fields as $key => $val) {
		if (eregi("\.*flag\.*",$key)) {
			if (!isset($input[$key])) {
				$input[$key]=0;
			}
		}
	}

	// Fill the update-array with changes
	$x=1;
	foreach ($input as $key => $val) {
		if (array_key_exists($key,$mon->fields) && $mon->fields[$key] != $input[$key]) {
			$mon->fields[$key] = $input[$key];
			$updates[$x] = $key;
			$x++;
		}
	}
	
	if (isset($input["state"]))
	if (isset($input["is_template"])&&$input["is_template"]==1)
	updateState(PERIPHERAL_TYPE,$input["ID"],$input["state"],1);
	else updateState(PERIPHERAL_TYPE,$input["ID"],$input["state"]);

	$mon->updateInDB($updates);

}

function addPeripheral($input) {
	// Add Peripheral, nasty hack until we get PHP4-array-functions
	$db=new DB;
	$mon = new Peripheral;

	$oldID=$input["ID"];

	// dump status
	unset($input["ID"]);
	unset($input["add"]);
	
	// Manage state
	$state=-1;
	if (isset($input["state"])){
		$state=$input["state"];
		unset($input["state"]);
	}
 	
	// set new date.
 	$mon->fields["date_mod"] = date("Y-m-d H:i:s");
	
	// fill array for udpate
	foreach ($input as $key => $val) {
		if ($key[0]!='_'&&(!isset($mon->fields[$key]) || $mon->fields[$key] != $input[$key])) {
			$mon->fields[$key] = $input[$key];
		}
	}

	$newID=$mon->addToDB();

	
	// Add state
	if ($state>0){
		if (isset($input["is_template"])&&$input["is_template"]==1)
			updateState(PERIPHERAL_TYPE,$newID,$state,1);
		else updateState(PERIPHERAL_TYPE,$newID,$state);
	}
	
	// ADD Infocoms
	$ic= new Infocom();
	if ($ic->getFromDB(PERIPHERAL_TYPE,$oldID)){
		$ic->fields["FK_device"]=$newID;
		unset ($ic->fields["ID"]);
		$ic->addToDB();
	}
	
		// ADD Ports
	$query="SELECT ID from glpi_networking_ports WHERE on_device='$oldID' AND device_type='".PERIPHERAL_TYPE."';";
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
	$query="SELECT FK_contract from glpi_contract_device WHERE FK_device='$oldID' AND device_type='".PERIPHERAL_TYPE."';";
	$result=$db->query($query);
	if ($db->numrows($result)>0){
		
		while ($data=$db->fetch_array($result))
			addDeviceContract($data["FK_contract"],PERIPHERAL_TYPE,$newID);
	}
	
	// ADD Documents			
	$query="SELECT FK_doc from glpi_doc_device WHERE FK_device='$oldID' AND device_type='".PERIPHERAL_TYPE."';";
	$result=$db->query($query);
	if ($db->numrows($result)>0){
		
		while ($data=$db->fetch_array($result))
			addDeviceDocument($data["FK_doc"],PERIPHERAL_TYPE,$newID);
	}

	return $newID;
}

function deletePeripheral($input,$force=0) {
	// Delete Printer
	
	$mon = new Peripheral;
	$mon->deleteFromDB($input["ID"],$force);
	
}

function restorePeripheral($input) {
	// Restore Peripheral
	
	$ct = new Peripheral;
	$ct->restoreInDB($input["ID"]);
} 
 	
?>
