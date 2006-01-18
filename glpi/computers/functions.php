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
 
// Based on:
// IRMA, Information Resource-Management and Administration
// Christian Bauer 
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
// FUNCTIONS Computers
/**
* Print a good title for computer pages
*
*
*
*
*@return nothing (diplays)
*
**/
function titleComputers(){
              //titre
              
        GLOBAL  $lang,$HTMLRel;

         echo "<div align='center'><table border='0'><tr><td>";
         echo "<img src=\"".$HTMLRel."pics/computer.png\" alt='".$lang["computers"][0]."' title='".$lang["computers"][0]."'></td><td><a  class='icon_consol' href=\"computers-add-select.php\"><b>".$lang["computers"][0]."</b></a>";
         echo "</td>";
         echo "<td><a class='icon_consol' href='".$HTMLRel."setup/setup-templates.php?type=".COMPUTER_TYPE."'>".$lang["common"][8]."</a></td>";
         echo "</tr></table></div>";

}

/**
* Print "onglets" (on the top of items forms)
*
* Print "onglets" for a better navigation.
*
*@param $target filename : The php file to display then
*@param $withtemplate bool : template or basic computers
*@param $actif witch of all the "onglets" is selected
*
*@return nothing (diplays)
*
**/
function showComputerOnglets($target,$withtemplate,$actif){
	global $lang,$HTMLRel;
	
	$template="";
	if(!empty($withtemplate)){
		$template="&amp;withtemplate=$withtemplate";
	}
	echo "<div id='barre_onglets'><ul id='onglet'>";
	echo "<li "; if ($actif=="1"){ echo "class='actif'";} echo  "><a href='$target&amp;onglet=1$template'>".$lang["title"][26]."</a></li>";
	echo "<li "; if ($actif=="2") {echo "class='actif'";} echo "><a href='$target&amp;onglet=2$template'>".$lang["title"][12]."</a></li>";
	echo "<li "; if ($actif=="3") {echo "class='actif'";} echo "><a href='$target&amp;onglet=3$template'>".$lang["title"][27]."</a></li>";
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

	$next=getNextItem("glpi_computers",$ID);
	$prev=getPreviousItem("glpi_computers",$ID);
	$cleantarget=preg_replace("/\?ID=([0-9]+)/","",$target);
	if ($prev>0) echo "<li><a href='$cleantarget?ID=$prev'><img src=\"".$HTMLRel."pics/left.png\" alt='".$lang["buttons"][12]."' title='".$lang["buttons"][12]."'></a></li>";
	if ($next>0) echo "<li><a href='$cleantarget?ID=$next'><img src=\"".$HTMLRel."pics/right.png\" alt='".$lang["buttons"][11]."' title='".$lang["buttons"][11]."'></a></li>";

	if (isReservable(COMPUTER_TYPE,$ID)){
		echo "<li class='invisible'>&nbsp;</li>";
		echo "<li "; if ($actif=="11") {echo "class='actif'";} echo "><a href='$target&amp;onglet=11$template'>".$lang["title"][35]."</a></li>";
	}

	}
	echo "</ul></div>";
	
}





/**
* Test if a field is a dropdown
*
* Return true if the field $field is a dropdown 
* or false if not.
*
*@param $field string field name
*
*
*@return bool
*
**/
function IsDropdown($field) {
	$dropdown = array("netpoint","os","model");
	if(in_array($field,$dropdown)) {
		return true;
	}
	else  {
		return false;
	}
}
/**
* Test if a field is a device
*
* Return true if the field $field is a device 
* or false if not.
*
*@param $field string device name
*
*
*@return bool
*
**/
function IsDevice($field) {
	global $cfg_devices_tables;
	if(in_array($field,$cfg_devices_tables)) {
		return true;
	}
	else  {
		return false;
	}
}

/**
* Print the computer form
*
*
* Print général computer form
*
*@param $target filename : where to go when done.
*@param $ID Integer : Id of the computer or the template to print
*@param $withtemplate='' boolean : template or basic computer
*
*
*@return Nothing (display)
*
**/
function showComputerForm($target,$ID,$withtemplate='') {
	global $lang,$HTMLRel,$cfg_layout;
	$comp = new Computer;
	$computer_spotted = false;
	if(empty($ID) && $withtemplate == 1) {
		if($comp->getEmpty()) $computer_spotted = true;
	} else {
		if($comp->getfromDB($ID)) $computer_spotted = true;
	}
	if($computer_spotted) {
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
			$date = convDateTime($comp->fields["date_mod"]);
			$template = false;
		}
		
		echo "<form name='form' method='post' action=\"$target\">";
		if(strcmp($template,"newtemplate") === 0) {
			echo "<input type=\"hidden\" name=\"is_template\" value=\"1\">";
		}

		echo "<div align='center'>";
		echo "<table width='800' class='tab_cadre' >";
		
		
		echo "<tr><th colspan ='2' align='center' >";
		if(!$template) {
			echo $lang["computers"][13].": ".$comp->fields["ID"];
		}elseif (strcmp($template,"newcomp") === 0) {
			echo $lang["computers"][12].": ".$comp->fields["tplname"];
			echo "<input type='hidden' name='tplname' value='".$comp->fields["tplname"]."'>";
		}elseif (strcmp($template,"newtemplate") === 0) {
			echo $lang["common"][6]."&nbsp;: ";
			autocompletionTextField("tplname","glpi_computers","tplname",$comp->fields["tplname"],20);	
		}
		
		echo "</th><th  colspan ='2' align='center'>".$datestring.$date;
		if (!$template&&!empty($comp->fields['tplname']))
			echo "&nbsp;&nbsp;&nbsp;(".$lang["common"][13].": ".$comp->fields['tplname'].")";
		echo "</th></tr>";
		
		echo "<tr class='tab_bg_1'><td>".$lang["computers"][7].":		</td>";

		echo "<td>";
		autocompletionTextField("name","glpi_computers","name",$comp->fields["name"],20);
		echo "</td>";
						
		echo "<td>".$lang["computers"][16].":	</td><td>";
		autocompletionTextField("contact","glpi_computers","contact",$comp->fields["contact"],20);
		
		echo "</td></tr>";
		
		echo "<tr class='tab_bg_1'>";
				echo "<td >".$lang["computers"][8].": 	</td>";
		echo "<td >";
			dropdownValue("glpi_type_computers", "type", $comp->fields["type"]);
		
		echo "</td>";

		
		
		echo "<td>".$lang["computers"][15].":		</td><td>";
		autocompletionTextField("contact_num","glpi_computers","contact_num",$comp->fields["contact_num"],20);
		echo "</td></tr>";

		echo "<tr class='tab_bg_1'>";
		echo "<td >".$lang["computers"][10].": 	</td>";
		echo "<td >";
			dropdownValue("glpi_dropdown_locations", "location", $comp->fields["location"]);
		
		echo "</td>";
		
		
		echo "<td>".$lang["setup"][88].":</td><td>";
		dropdownValue("glpi_dropdown_network", "network", $comp->fields["network"]);
		echo "</td></tr>";

		echo "<tr class='tab_bg_1'>";
		echo "<td >".$lang["computers"][50].": 	</td>";
		echo "<td >";
			dropdownValue("glpi_dropdown_model", "model", $comp->fields["model"]);
		
		echo "</td>";
		
		
		echo "<td>".$lang["setup"][89].":</td><td>";
		dropdownValue("glpi_dropdown_domain", "domain", $comp->fields["domain"]);
		echo "</td></tr>";
		

		echo "<tr class='tab_bg_1'>";
		echo "<td >".$lang["common"][10].": 	</td>";
		echo "<td >";
			dropdownUsersID("tech_num",$comp->fields["tech_num"]);
		echo "</td>";

		echo "<td valign='middle' rowspan='4'>".$lang["computers"][19].":</td><td valign='middle' rowspan='4'><textarea  cols='35' rows='5' name='comments' >".$comp->fields["comments"]."</textarea></td></tr>";

		echo "<tr class='tab_bg_1'>";
		echo "<td>".$lang["common"][5].": 	</td><td>";
		dropdownValue("glpi_enterprises","FK_glpi_enterprise",$comp->fields["FK_glpi_enterprise"]);
		echo "</td></tr>";

		echo "<tr class='tab_bg_1'><td>".$lang["computers"][17].":	</td><td>";
		autocompletionTextField("serial","glpi_computers","serial",$comp->fields["serial"],20);
		echo "</td></tr>";

		
		echo "<tr class='tab_bg_1'>";
		
		echo "<td>".$lang["computers"][18].":	</td><td>";
		autocompletionTextField("otherserial","glpi_computers","otherserial",$comp->fields["otherserial"],20);
		echo "</td></tr>";

		
		echo "<tr class='tab_bg_1'>";
		
		echo "<td>".$lang["computers"][9].":</td><td>";
		dropdownValue("glpi_dropdown_os", "os", $comp->fields["os"]);
		echo "</td>";
		
		echo "<td>".$lang["state"][0].":</td><td>";
		$si=new StateItem();
		$t=0;
		if ($template) $t=1;
		$si->getfromDB(COMPUTER_TYPE,$comp->fields["ID"],$t);
		dropdownValue("glpi_dropdown_state", "state",$si->fields["state"]);
		echo "</td></tr>";
				
		echo "<tr class='tab_bg_1'>";
		
		echo "<td>".$lang["computers"][51].":</td><td>";
		dropdownValue("glpi_dropdown_auto_update", "auto_update", $comp->fields["auto_update"]);
		echo "</td>";
		
		if (!$template){
		echo "<td>".$lang["reservation"][24].":</td><td><b>";
		showReservationForm(COMPUTER_TYPE,$ID);
		echo "</b></td>";
		} else echo "<td>&nbsp;</td><td>&nbsp;</td>";
		
		
		
		
		echo "</tr><tr>";
		if ($template) {
			if (empty($ID)||$withtemplate==2){
			echo "<td class='tab_bg_2' align='center' colspan='4'>\n";
			echo "<input type='hidden' name='ID' value=$ID>";
			echo "<input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'>";
			echo "</td>\n";
			} else {
			echo "<td class='tab_bg_2' align='center' colspan='4'>\n";
			echo "<input type='hidden' name='ID' value=$ID>";
			echo "<input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'>";
			echo "</td>\n";
			}
		} else {
			echo "<td class='tab_bg_2' colspan='2' align='center' valign='top'>\n";
			echo "<input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'>";
			echo "</td>\n";
                        echo "<td class='tab_bg_2' colspan='2'  align='center'>\n";
			echo "<input type='hidden' name='ID' value=$ID>";
		echo "<div align='center'>";
		if ($comp->fields["deleted"]=='N')
		echo "<input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'>";
		else {
		echo "<input type='submit' name='restore' value=\"".$lang["buttons"][21]."\" class='submit'>";
		
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' name='purge' value=\"".$lang["buttons"][22]."\" class='submit'>";
		}
		echo "</div>";
			echo "</td>";
		}

		echo "</tr>\n";
		
		
		
		echo "</table>";
		echo "</div>";
	echo "</form>";
		
		
		return true;
	}
	else {
         echo "<div align='center'><b>".$lang["computers"][32]."</b></div>";
         return false;
        }
}
/**
* Print the form for devices linked to a computer or a template
*
*
* Print the form for devices linked to a computer or a template 
*
*@param $target filename : where to go when done.
*@param $ID Integer : Id of the computer or the template to print
*@param $withtemplate='' boolean : template or basic computer
*
*
*@return Nothing (display)
*
**/
function showDeviceComputerForm($target,$ID,$withtemplate='') {
	global $lang;
	$comp = new Computer;
	if(empty($ID) && $withtemplate == 1) {
		$comp->getEmpty();
	} else {
		$comp->getfromDB($ID,1);
	}

	if (!empty($ID)){
			//print devices.
		echo "<div align='center'>";
		echo "<form name='form_device_action' action=\"$target\" method=\"post\" >";
		echo "<input type='hidden' name='ID' value='$ID'>";	
		echo "<input type='hidden' name='device_action' value='$ID'>";			
		echo "<table width='800' class='tab_cadre' >";
		echo "<tr><th colspan='66'>".$lang["devices"][10]."</th></tr>";
		foreach($comp->devices as $key => $val) {
			$devType = $val["devType"];
			$devID = $val["devID"];
			$specif = $val["specificity"];
			$compDevID = $val["compDevID"];
			$device = new Device($devType);
			$device->getFromDB($devID);
			printDeviceComputer($device,$specif,$comp->fields["ID"],$compDevID,$withtemplate);
			
		}
		echo "</table>";

		echo "</form>";
		//ADD a new device form.
		device_selecter($_SERVER["PHP_SELF"],$comp->fields["ID"],$withtemplate);
		echo "</div>";
	}	


}
/**
* Update some elements of a computer in the database.
*
* Update some elements of a computer in the database.
*
*@param $input array : the _POST vars returned bye the computer form when press update (see showcomputerform())
*
*
*@return Nothing (call to the class member Computers->updateInDB )
*
**/
function updateComputer($input) {
	// Update a computer in the database

	$comp = new Computer;
	$comp->getFromDB($input["ID"],0);

	// set new date and make sure it gets updated
	$updates[0]= "date_mod";
	$comp->fields["date_mod"] = date("Y-m-d H:i:s");

	// Get all flags and fill with 0 if unchecked in form
	foreach  ($comp->fields as $key => $val) {
		if (eregi("\.*flag\.*",$key)) {
			if (empty($input[$key])) {
				$input[$key]=0;
			}
		}
	}
	
	// Fill the update-array with changes
	$x=1;
	foreach ($input as $key => $val) {
		if (array_key_exists($key,$comp->fields) && $comp->fields[$key]  != $input[$key]) {
			$comp->fields[$key] = $input[$key];
			$updates[$x] = $key;
			$x++;
		}
	}
	
	if(isset($input["state"])){
		if (isset($input["is_template"])&&$input["is_template"]==1){
			updateState(COMPUTER_TYPE,$input["ID"],$input["state"],1);
		}else {
			updateState(COMPUTER_TYPE,$input["ID"],$input["state"]);
		}
	}
	$comp->updateInDB($updates);
	
}
/**
* Add a computer in the database.
*
* Add a computer in the database with all it's items.
*
*@param $input array : the _POST vars returned bye the computer form when press add(see showcomputerform())
*
*
*@return Nothing (call to classes members)
*
**/
function addComputer($input) {
	// Add Computer
	$db=new DB;
	$comp = new Computer;
	
  	// set new date.
   	$comp->fields["date_mod"] = date("Y-m-d H:i:s");
   
	// dump status
	$oldID=$input["ID"];
	unset($input['add']);
	unset($input['withtemplate']);
	unset($input['ID']);
	
	// Manage state
	$state=-1;
	if (isset($input["state"])){
		$state=$input["state"];
		unset($input["state"]);
	}

	$i=0;
	
	// fill array for update
	foreach ($input as $key => $val){
	if ($key[0]!='_'&&(!isset($comp->fields[$key]) || $comp->fields[$key] != $input[$key])) {
			$comp->fields[$key] = $input[$key];
		}		
	}
	$newID=$comp->addToDB();
	
	// Add state
	if ($state>0){
		if (isset($input["is_template"])&&$input["is_template"]==1)
			updateState(COMPUTER_TYPE,$newID,$state,1);
		else updateState(COMPUTER_TYPE,$newID,$state);
	}
	
	// ADD Devices
	$comp->getFromDB($oldID,1);
	foreach($comp->devices as $key => $val) {
			compdevice_add($newID,$val["devType"],$val["devID"],$val["specificity"]);
		}
	
	// ADD Infocoms
	$ic= new Infocom();
	if ($ic->getFromDB(COMPUTER_TYPE,$oldID)){
		$ic->fields["FK_device"]=$newID;
		unset ($ic->fields["ID"]);
		$ic->addToDB();
	}
	
	// ADD software
	$query="SELECT license from glpi_inst_software WHERE cID='$oldID'";
	$result=$db->query($query);
	if ($db->numrows($result)>0){
		
		while ($data=$db->fetch_array($result))
			installSoftware($newID,$data['license']);
	}
	
	// ADD Contract				
	$query="SELECT FK_contract from glpi_contract_device WHERE FK_device='$oldID' AND device_type='".COMPUTER_TYPE."';";
	$result=$db->query($query);
	if ($db->numrows($result)>0){
		
		while ($data=$db->fetch_array($result))
			addDeviceContract($data["FK_contract"],COMPUTER_TYPE,$newID);
	}

	// ADD Documents			
	$query="SELECT FK_doc from glpi_doc_device WHERE FK_device='$oldID' AND device_type='".COMPUTER_TYPE."';";
	$result=$db->query($query);
	if ($db->numrows($result)>0){
		
		while ($data=$db->fetch_array($result))
			addDeviceDocument($data["FK_doc"],COMPUTER_TYPE,$newID);
	}
	
	// ADD Ports
	$query="SELECT ID from glpi_networking_ports WHERE on_device='$oldID' AND device_type='".COMPUTER_TYPE."';";
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
		
	return $newID;
}
/**
* Delete a computer in the database.
*
* Delete a computer in the database.
*
*@param $input array : the _POST vars returned bye the computer form when press delete(see showcomputerform())
*@param $force=0 int : how far the computer is deleted (moved to trash or purged from db).
*
*@return Nothing ()
*
**/
function deleteComputer($input,$force=0) {
	// Delete Computer

	$comp = new Computer;
	$comp->deleteFromDB($input["ID"],$force);
} 	
/**
* Restore a computer trashed in the database.
*
* Restore a computer trashed in the database.
*
*@param $input array : the _POST vars returned bye the computer form when press restore(see showcomputerform())
*
*@return Nothing ()
*
**/
function restoreComputer($input) {
	// Restore Computer
	
	$ct = new Computer;
	$ct->restoreInDB($input["ID"]);
} 

/**
* Print the computers or template local connections form. 
*
* Print the form for computers or templates connections to printers, screens or peripherals
*
*@param $ID integer: Computer or template ID
*@param $withtemplate=''  boolean : Template or basic item.
*
*@return Nothing (call to classes members)
*
**/
function showConnections($target,$ID,$withtemplate='') {

	GLOBAL $cfg_layout, $cfg_install, $lang;

	$db = new DB;
	
	$state=new StateItem();

	echo "&nbsp;<div align='center'><table border='0' width='90%' class='tab_cadre'>";
	echo "<tr><th colspan='3'>".$lang["connect"][0].":</th></tr>";
	echo "<tr><th>".$lang["computers"][39].":</th><th>".$lang["computers"][40].":</th><th>".$lang["computers"][46].":</th></tr>";

	echo "<tr class='tab_bg_1'>";

	// Printers
	echo "<td align='center'>";
	$query = "SELECT * from glpi_connect_wire WHERE end2='$ID' AND type='".PRINTER_TYPE."'";
	if ($result=$db->query($query)) {
		$resultnum = $db->numrows($result);
		if ($resultnum>0) {
			echo "<table width='100%'>";
			for ($i=0; $i < $resultnum; $i++) {
				$tID = $db->result($result, $i, "end1");
				$connID = $db->result($result, $i, "ID");
				$printer = new Printer;
				$printer->getfromDB($tID);
				
				echo "<tr ".($printer->fields["deleted"]=='Y'?"class='tab_bg_2_2'":"").">";
				echo "<td align='center'><a href=\"".$cfg_install["root"]."/printers/printers-info-form.php?ID=$tID\"><b>";
				echo $printer->fields["name"]." (".$printer->fields["ID"].")";
				echo "</b></a>";
				if ($state->getfromDB(PRINTER_TYPE,$tID))
				echo " - ".getDropdownName("glpi_dropdown_state",$state->fields['state']);

				echo "</td>";
				if(!empty($withtemplate) && $withtemplate == 2) {
					//do nothing
				} else {
					echo "<td align='center'><a href=\"".$cfg_install["root"]."/computers/computers-info-form.php?cID=$ID&amp;ID=$connID&amp;disconnect=1amp;withtemplate=".$withtemplate."\"><b>";
					echo $lang["buttons"][10];
					echo "</b></a></td>";
				}
				echo "</tr>";
			}
			echo "</table>";
		} else {
			echo $lang["computers"][38]."<br>";
		}
		if(!empty($withtemplate) && $withtemplate == 2) {
			//do nothing
		} else {
			echo "<form method='post' action=\"$target\">";
			echo "<input type='hidden' name='connect' value='connect'>";
			echo "<input type='hidden' name='cID' value='$ID'>";
			echo "<input type='hidden' name='device_type' value='".PRINTER_TYPE."'>";
			dropdownConnect(PRINTER_TYPE,"item");
			echo "<input type='submit' value=\"".$lang["buttons"][9]."\" class='submit'>";

			echo "</form>";
		}

	}
	echo "</td>";

	// Monitors
	echo "<td align='center'>";
	$query = "SELECT * from glpi_connect_wire WHERE end2='$ID' AND type='".MONITOR_TYPE."'";
	if ($result=$db->query($query)) {
		$resultnum = $db->numrows($result);
		if ($resultnum>0) {
			echo "<table width='100%'>";
			for ($i=0; $i < $resultnum; $i++) {
				$tID = $db->result($result, $i, "end1");
				$connID = $db->result($result, $i, "ID");
				$monitor = new Monitor;
				$monitor->getfromDB($tID);
				echo "<tr ".($monitor->fields["deleted"]=='Y'?"class='tab_bg_2_2'":"").">";
				echo "<td align='center'><a href=\"".$cfg_install["root"]."/monitors/monitors-info-form.php?ID=$tID\"><b>";
				echo $monitor->fields["name"]." (".$monitor->fields["ID"].")";
				echo "</b></a>";
				if ($state->getfromDB(MONITOR_TYPE,$tID))
				echo " - ".getDropdownName("glpi_dropdown_state",$state->fields['state']);
				
				echo "</td>";
				if(!empty($withtemplate) && $withtemplate == 2) {
					//do nothing
				} else {
					echo "<td align='center'><a href=\"".$cfg_install["root"]."/computers/computers-info-form.php?cID=$ID&amp;ID=$connID&amp;disconnect=1&amp;withtemplate=".$withtemplate."\"><b>";
					echo $lang["buttons"][10];
					echo "</b></a></td>";
				}
				echo "</tr>";
			}
			echo "</table>";			
		} else {
			echo $lang["computers"][37]."<br>";
		}
		if(!empty($withtemplate) && $withtemplate == 2) {
			//do nothing
		} else {
			echo "<form method='post' action=\"$target\">";
			echo "<input type='hidden' name='connect' value='connect'>";
			echo "<input type='hidden' name='cID' value='$ID'>";
			echo "<input type='hidden' name='device_type' value='".MONITOR_TYPE."'>";
			dropdownConnect(MONITOR_TYPE,"item");
			echo "<input type='submit' value=\"".$lang["buttons"][9]."\" class='submit'>";

			echo "</form>";

		}

	}
	echo "</td>";
	
	//Peripherals
	echo "<td align='center'>";
	$query = "SELECT * from glpi_connect_wire WHERE end2='$ID' AND type='".PERIPHERAL_TYPE."'";
	if ($result=$db->query($query)) {
		$resultnum = $db->numrows($result);
		if ($resultnum>0) {
			echo "<table width='100%'>";
			for ($i=0; $i < $resultnum; $i++) {
				$tID = $db->result($result, $i, "end1");
				$connID = $db->result($result, $i, "ID");
				$periph = new Peripheral;
				$periph->getfromDB($tID);
				echo "<tr ".($periph->fields["deleted"]=='Y'?"class='tab_bg_2_2'":"").">";
				echo "<td align='center'><a href=\"".$cfg_install["root"]."/peripherals/peripherals-info-form.php?ID=$tID\"><b>";
				echo $periph->fields["name"]." (".$periph->fields["ID"].")";
				echo "</b></a>";

				if ($state->getfromDB(PERIPHERAL_TYPE,$tID))
				echo " - ".getDropdownName("glpi_dropdown_state",$state->fields['state']);
				
				echo "</td>";
				if(!empty($withtemplate) && $withtemplate == 2) {
					//do nothing
				} else {
					echo "<td align='center'><a href=\"".$cfg_install["root"]."/computers/computers-info-form.php?cID=$ID&amp;ID=$connID&amp;disconnect=1&amp;withtemplate=".$withtemplate."\"><b>";
					echo $lang["buttons"][10];
					echo "</b></a></td>";
				}
				echo "</tr>";
			}
			echo "</table>";			
		} else {
			echo $lang["computers"][47]."<br>";
		}
		if(!empty($withtemplate) && $withtemplate == 2) {
			//do nothing
		} else {
			echo "<form method='post' action=\"$target\">";
			echo "<input type='hidden' name='connect' value='connect'>";
			echo "<input type='hidden' name='cID' value='$ID'>";
			echo "<input type='hidden' name='device_type' value='".PERIPHERAL_TYPE."'>";
			dropdownConnect(PERIPHERAL_TYPE,"item");
			echo "<input type='submit' value=\"".$lang["buttons"][9]."\" class='submit'>";

			echo "</form>";

		}

	}

	echo "</tr>";
	echo "</table></div><br>";
	
}




?>
