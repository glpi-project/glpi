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
///// Manage Netdevices /////

function titleNetdevices() {
         // titre
         
         GLOBAL  $lang,$HTMLRel;

          echo "<div align='center'><table border='0'><tr><td>";
          echo "<img src=\"".$HTMLRel."pics/networking.png\" alt='".$lang["networking"][11]."' title='".$lang["networking"][11]."'></td><td><a  class='icon_consol' href=\"networking-add-select.php\"><b>".$lang["networking"][11]."</b></a>";
                echo "</td>";
                echo "<td><a class='icon_consol' href='".$HTMLRel."setup/setup-templates.php?type=".NETWORKING_TYPE."'>".$lang["common"][8]."</a></td>";
                echo "</tr></table></div>";
 
}

function showNetworkingOnglets($target,$withtemplate,$actif){
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
	$next=getNextItem("glpi_networking",$ID);
	$prev=getPreviousItem("glpi_networking",$ID);
	$cleantarget=preg_replace("/\?ID=([0-9]+)/","",$target);
	if ($prev>0) echo "<li><a href='$cleantarget?ID=$prev'><img src=\"".$HTMLRel."pics/left.png\" alt='".$lang["buttons"][12]."' title='".$lang["buttons"][12]."'></a></li>";
	if ($next>0) echo "<li><a href='$cleantarget?ID=$next'><img src=\"".$HTMLRel."pics/right.png\" alt='".$lang["buttons"][11]."' title='".$lang["buttons"][11]."'></a></li>";
	if (isReservable(NETWORKING_TYPE,$ID)){
		echo "<li class='invisible'>&nbsp;</li>";
		echo "<li "; if ($actif=="11") {echo "class='actif'";} echo "><a href='$target&amp;onglet=11$template'>".$lang["title"][35]."</a></li>";
	}
	}

	echo "</ul></div>";
	
}



function showNetworkingForm ($target,$ID,$withtemplate='') {
	// Show device or blank form
	
	GLOBAL $cfg_layout,$cfg_install, $lang,$HTMLRel;

	$netdev = new Netdevice;

	$netdev_spotted = false;

	if(empty($ID) && $withtemplate == 1) {
		if($netdev->getEmpty()) $netdev_spotted = true;
	} else {
		if($netdev->getfromDB($ID)) $netdev_spotted = true;
	}

	if($netdev_spotted) {
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
			$date = convDateTime($netdev->fields["date_mod"]);
			$template = false;
		}


	echo "<div align='center'><form name='form' method='post' action=\"$target\">\n";

		if(strcmp($template,"newtemplate") === 0) {
			echo "<input type=\"hidden\" name=\"is_template\" value=\"1\" />\n";
		}

	echo "<table width='800' class='tab_cadre' cellpadding='2'>\n";

		echo "<tr><th align='center' >\n";
		if(!$template) {
			echo $lang["networking"][54].": ".$netdev->fields["ID"];
		}elseif (strcmp($template,"newcomp") === 0) {
			echo $lang["networking"][53].": ".$netdev->fields["tplname"];
			echo "<input type='hidden' name='tplname' value='".$netdev->fields["tplname"]."'>";
		}elseif (strcmp($template,"newtemplate") === 0) {
			echo $lang["common"][6].": ";
			autocompletionTextField("tplname","glpi_networking","tplname",$netdev->fields["tplname"],20);	
		}
		echo "</th><th  align='center'>".$datestring.$date;
		if (!$template&&!empty($netdev->fields['tplname']))
			echo "&nbsp;&nbsp;&nbsp;(".$lang["common"][13].": ".$netdev->fields['tplname'].")";
		echo "</th></tr>\n";

	
	echo "<tr><td class='tab_bg_1' valign='top'>\n";

	echo "<table cellpadding='1' cellspacing='0' border='0'>\n";

	echo "<tr><td>".$lang["networking"][0].":	</td>\n";
	echo "<td>";
	autocompletionTextField("name","glpi_networking","name",$netdev->fields["name"],20);	
	echo "</td></tr>\n";

	echo "<tr><td>".$lang["networking"][1].": 	</td><td>\n";
		dropdownValue("glpi_dropdown_locations", "location", $netdev->fields["location"]);
	echo "</td></tr>\n";
	
	echo "<tr class='tab_bg_1'><td>".$lang["common"][10].": 	</td><td colspan='2'>\n";
		dropdownUsersID("tech_num", $netdev->fields["tech_num"]);
	echo "</td></tr>\n";
		
	echo "<tr><td>".$lang["networking"][4].":	</td><td>\n";
		autocompletionTextField("contact_num","glpi_networking","contact_num",$netdev->fields["contact_num"],20);	
	echo "</td></tr>\n";

	echo "<tr><td>".$lang["networking"][3].":	</td><td>\n";
		autocompletionTextField("contact","glpi_networking","contact",$netdev->fields["contact"],20);	
	echo "</td></tr>\n";
	
	if (!$template){
	echo "<tr><td>".$lang["reservation"][24].":</td><td><b>";
	showReservationForm(NETWORKING_TYPE,$ID);
	echo "</b></td></tr>";
	}

		
		echo "<tr><td>".$lang["state"][0].":</td><td>\n";
		$si=new StateItem();
		$t=0;
		if ($template) $t=1;
		$si->getfromDB(NETWORKING_TYPE,$netdev->fields["ID"],$t);
		dropdownValue("glpi_dropdown_state", "state",$si->fields["state"]);
		echo "</td></tr>\n";
		
	echo "<tr><td>".$lang["setup"][88].": 	</td><td>\n";
		dropdownValue("glpi_dropdown_network", "network", $netdev->fields["network"]);
	echo "</td></tr>\n";

	echo "<tr><td>".$lang["setup"][89].": 	</td><td>\n";
		dropdownValue("glpi_dropdown_domain", "domain", $netdev->fields["domain"]);
	echo "</td></tr>\n";

	echo "</table>\n";

	echo "</td>\n";	
	echo "<td class='tab_bg_1' valign='top'>\n";

	echo "<table cellpadding='1' cellspacing='0' border='0'>\n";

	echo "<tr><td>".$lang["networking"][2].": 	</td><td>\n";
		dropdownValue("glpi_type_networking", "type", $netdev->fields["type"]);
	echo "</td></tr>\n";

	echo "<tr><td>".$lang["networking"][58].": 	</td><td>";
		dropdownValue("glpi_dropdown_model_networking", "model", $netdev->fields["model"]);
	echo "</td></tr>";
	
	echo "<tr class='tab_bg_1'><td>".$lang["common"][5].": 	</td><td colspan='2'>\n";
		dropdownValue("glpi_enterprises","FK_glpi_enterprise",$netdev->fields["FK_glpi_enterprise"]);
	echo "</td></tr>\n";
	
	echo "<tr><td>".$lang["networking"][49].": 	</td><td>\n";
	dropdownValue("glpi_dropdown_firmware", "firmware", $netdev->fields["firmware"]);
	echo "</td></tr>\n";
		
	echo "<tr><td>".$lang["networking"][5].":	</td><td>\n";
	autocompletionTextField("ram","glpi_networking","ram",$netdev->fields["ram"],20);	
	echo "</td></tr>\n";

	echo "<tr><td>".$lang["networking"][6].":	</td><td>\n";
	autocompletionTextField("serial","glpi_networking","serial",$netdev->fields["serial"],20);	
	echo "</td></tr>\n";

	echo "<tr><td>".$lang["networking"][7].":</td><td>\n";
	autocompletionTextField("otherserial","glpi_networking","otherserial",$netdev->fields["otherserial"],20);	
	echo "</td></tr>\n";
	
	echo "<tr><td>".$lang["networking"][14].":</td><td>\n";
	autocompletionTextField("ifaddr","glpi_networking","ifaddr",$netdev->fields["ifaddr"],20);	
	echo "</td></tr>\n";

	echo "<tr><td>".$lang["networking"][15].":</td><td>\n";
	autocompletionTextField("ifmac","glpi_networking","ifmac",$netdev->fields["ifmac"],20);	
	echo "</td></tr>\n";
		
	echo "</table>\n";
	
	echo "</td>\n";	
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td class='tab_bg_1' valign='top' colspan='2'>\n";

	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'><tr><td valign='top'>\n";
	echo $lang["networking"][8].":	</td>\n";
	echo "<td align='center'><textarea cols='35' rows='4' name='comments' >".$netdev->fields["comments"]."</textarea>\n";
	echo "</td></tr></table>\n";

	echo "</td>";
	echo "</tr>\n";

	echo "<tr>\n";
	
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

		echo "<td class='tab_bg_2' valign='top'>";
		echo "<input type='hidden' name='ID' value=\"$ID\">\n";
		echo "<div align='center'><input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'></div>";
		echo "<td class='tab_bg_2' valign='top'>\n";

		echo "<div align='center'>\n";
		if ($netdev->fields["deleted"]=='N')
		echo "<input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'>\n";
		else {
		echo "<input type='submit' name='restore' value=\"".$lang["buttons"][21]."\" class='submit'>\n";
		
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' name='purge' value=\"".$lang["buttons"][22]."\" class='submit'>\n";
		}
		echo "</div>\n";
		echo "</td>\n";
	}
		echo "</tr>\n";

		echo "</table></form></div>\n";

	return true;
		}
	else {
                echo "<div align='center'><b>".$lang["networking"][38]."</b></div>";
                return false;
        }

}

function addNetdevice($input) {
	// Add Netdevice, nasty hack until we get PHP4-array-functions
	$db=new DB;
	$netdev = new Netdevice;

	// dump the status
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

 	// set new date.
 	$netdev->fields["date_mod"] = date("Y-m-d H:i:s");
	
	// fill array for update
	foreach ($input as $key => $val) {
		if ($key[0]!='_'&&(empty($netdev->fields[$key]) || $netdev->fields[$key]  != $input[$key])) {
			$netdev->fields[$key] = $input[$key];
		}
	}

	$newID=$netdev->addToDB();
	
	// Add state
	if ($state>0){
		if (isset($input["is_template"])&&$input["is_template"]==1)
			updateState(NETWORKING_TYPE,$newID,$state,1);
		else updateState(NETWORKING_TYPE,$newID,$state);	
	}
	
	// ADD Infocoms
	$ic= new Infocom();
	if ($ic->getFromDB(NETWORKING_TYPE,$oldID)){
		$ic->fields["FK_device"]=$newID;
		unset ($ic->fields["ID"]);
		$ic->addToDB();
	}
	
		// ADD Ports
	$query="SELECT ID from glpi_networking_ports WHERE on_device='$oldID' AND device_type='".NETWORKING_TYPE."';";
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
	$query="SELECT FK_contract from glpi_contract_device WHERE FK_device='$oldID' AND device_type='".NETWORKING_TYPE."';";
	$result=$db->query($query);
	if ($db->numrows($result)>0){
		
		while ($data=$db->fetch_array($result))
			addDeviceContract($data["FK_contract"],NETWORKING_TYPE,$newID);
	}
	
	// ADD Documents			
	$query="SELECT FK_doc from glpi_doc_device WHERE FK_device='$oldID' AND device_type='".NETWORKING_TYPE."';";
	$result=$db->query($query);
	if ($db->numrows($result)>0){
		
		while ($data=$db->fetch_array($result))
			addDeviceDocument($data["FK_doc"],NETWORKING_TYPE,$newID);
	}

	return $newID;
	
}

function updateNetdevice($input) {
	// Update a netdevice in the database

	$netdev = new Netdevice;
	$netdev->getFromDB($input["ID"]);

	// set new date and make sure it gets updated
	$updates[0]= "date_mod";
	$netdev->fields["date_mod"] = date("Y-m-d H:i:s");

	// Get all flags and fill with 0 if unchecked in form
	foreach ($netdev->fields as $key => $val) {
		if (eregi("\.*flag\.*",$key)) {
			if (!$input[$key]) {
				$input[$key]=0;
			}
		}
	}
		
	// Fill the update-array with changes
	$x=1;
	foreach ($input as $key => $val) {
		if (array_key_exists($key,$netdev->fields) && $netdev->fields[$key] != $input[$key]) {
			$netdev->fields[$key] = $input[$key];
			$updates[$x] = $key;
			$x++;
		}
	}
	if (isset($input["state"]))
	if (isset($input["is_template"])&&$input["is_template"]==1)
		updateState(NETWORKING_TYPE,$input["ID"],$input["state"],1);
	else updateState(NETWORKING_TYPE,$input["ID"],$input["state"]);

	$netdev->updateInDB($updates);

}

function deleteNetdevice($input,$force=0) {
	// Delete Netdevice
	
	$netdev = new Netdevice;
	$netdev->deleteFromDB($input["ID"],$force);
} 

function restoreNetdevice($input) {
	// Restore Netdevice
	
	$ct = new Netdevice;
	$ct->restoreInDB($input["ID"]);
} 



///// Manage Ports on Devices /////

function showPorts ($device,$device_type,$withtemplate='') {
	
	GLOBAL $cfg_layout,$cfg_install, $lang,$HTMLRel;
	
	$db = new DB;
	switch($device_type) {
		case COMPUTER_TYPE :
			$device_real_table_name = "glpi_computers";
			break;
		case NETWORKING_TYPE :
			$device_real_table_name = "glpi_networking";
			break;
		case PRINTER_TYPE :
			$device_real_table_name = "glpi_printers";
			break;
		case PERIPHERAL_TYPE :
			$device_real_table_name = "glpi_peripherals";
			break;
	}
	$query = "SELECT location from ".$device_real_table_name." where ID = ".$device."";
	$location = $db->result($db->query($query),0,"location");

	$query = "SELECT ID FROM glpi_networking_ports WHERE (on_device = $device AND device_type = $device_type) ORDER BY logical_number";
	if ($result = $db->query($query)) {
		if ($db->numrows($result)!=0) { 
			
			$colspan=8;
			if ($withtemplate!=2){
			}
			
			echo "<br><div align='center'><table class='tab_cadre' width='90%'>";
			echo "<tr><th colspan='$colspan'>";
			echo $db->numrows($result)." ";
			if ($db->numrows($result)<2) {
				echo $lang["networking"][37];
			} else {
				echo $lang["networking"][13];
			}
			echo ":</th>";

			echo "</tr>";        
			echo "<tr><th>#</th><th>".$lang["networking"][0]."</th><th>".$lang["networking"][51]."</th>";
			echo "<th>".$lang["networking"][14]."</th><th>".$lang["networking"][15]."</th>";
			echo "<th>".$lang["networking"][56]."</th>";
			echo "<th>".$lang["networking"][16]."</th><th>".$lang["networking"][17].":</th></tr>\n";
			$i=0;
			while ($devid=$db->fetch_row($result)) {
				$netport = new Netport;
				$netport->getfromDB(current($devid));
				echo "<tr class='tab_bg_1'>";
				echo "<td align='center'><b>";
				if ($withtemplate!=2) echo "<a href=\"".$cfg_install["root"]."/networking/networking-port.php?ID=".$netport->fields["ID"]."&amp;location=".$location."\">";
				echo $netport->fields["logical_number"];
				if ($withtemplate!=2) echo "</a>";
				echo "</b></td>";
        echo "<td>".$netport->fields["name"]."</td>";
		echo "<td>".getDropdownName("glpi_dropdown_netpoint",$netport->fields["netpoint"])."</td>";
				echo "<td>".$netport->fields["ifaddr"]."</td>";
				echo "<td>".$netport->fields["ifmac"]."</td>";
				// VLANs
				echo "<td>";
					showPortVLAN($netport->fields["ID"],$withtemplate);
				echo "</td>";
				echo "<td>".getDropdownName("glpi_dropdown_iface",$netport->fields["iface"])."</td>";
				echo "<td>";
					showConnection($netport->fields["ID"],$withtemplate,$device_type);
				echo "</td>";
				echo "</tr>";
			}
			echo "</table>";
			echo "</div>\n\n";
			// Assign VLAN form
			if ($withtemplate!=2){
			}

			
		}
	}
}

function showPortVLAN($ID,$withtemplate,$referer=''){
global $HTMLRel,$lang;
$db=new DB;

echo "<table cellpadding='0' cellspacing='0'>";
/*if ($withtemplate!=2){
	$sel="";
	if (isset($_GET["select"])&&$_GET["select"]=="all") $sel="checked";
		echo "<tr><td colspan='2'><input type='checkbox' name='toassign[$ID]' value='1' $sel></td></tr>";
}
*/
$query="SELECT * from glpi_networking_vlan WHERE FK_port='$ID'";
$result=$db->query($query);
if ($db->numrows($result)>0)
while ($line=$db->fetch_array($result)){
	echo "<tr><td>".getDropdownName("glpi_dropdown_vlan",$line["FK_vlan"]);
	echo "</td><td>";
	echo "<a href='".$HTMLRel."networking/networking-port.php?unassign_vlan=unassigned&amp;ID=".$line["ID"]."&referer=$referer'>";
    echo "<img src=\"".$HTMLRel."/pics/delete2.png\" alt='".$lang["buttons"][6]."' title='".$lang["buttons"][6]."'>";
    echo "</a></td></tr>";
}
echo "</table>";

}

function assignVlan($port,$vlan){
$db=new DB;
$query="INSERT INTO glpi_networking_vlan (FK_port,FK_vlan) VALUES ('$port','$vlan')";
$db->query($query);

$np=new NetPort();
if ($np->getContact($port)){
	$query="INSERT INTO glpi_networking_vlan (FK_port,FK_vlan) VALUES ('".$np->contact_id."','$vlan')";
	$db->query($query);
}

}

function unassignVlan($ID){
$db=new DB;
$query="DELETE FROM glpi_networking_vlan WHERE ID='$ID'";
$db->query($query);
}

function showNetportForm($target,$ID,$ondevice,$devtype,$several,$search = '', $location = '') {

	GLOBAL $cfg_install, $cfg_layout, $lang, $REFERER;

	$netport = new Netport;
	if($ID)
	{
		$netport->getFromDB($ID);
		$netport->getDeviceData($netport->fields["on_device"],$netport->fields["device_type"]);
	}
	else
	{
		$netport->getFromNull();
	}
	
	// Ajout des infos déjà remplies
	if (isset($_POST)&&!empty($_POST)){
	foreach ($netport->fields as $key => $val)
		if ($key!='ID'&&isset($_POST[$key]))
		$netport->fields[$key]=$_POST[$key];
	}
	
	
	echo "<div align='center'>";
	echo "<p><a class='icon_consol' href='$REFERER'>".$lang["buttons"][13]."</a></p>";
	
	echo "<form method='post' action=\"$target\">";

	echo "<input type='hidden' name='referer' value='$REFERER'>";
	echo "<table class='tab_cadre'><tr>";
	
	echo "<th colspan='4'>".$lang["networking"][20].":</th>";
	echo "</tr>";

	if ($several!="yes"){
	echo "<tr class='tab_bg_1'><td>".$lang["networking"][21].":</td>";
	echo "<td>";
	autocompletionTextField("logical_number","glpi_networking_ports","logical_number",$netport->fields["logical_number"],5);	
	echo "</td></tr>";
	}
	else {
	echo "<tr class='tab_bg_1'><td>".$lang["networking"][21].":</td>";
	echo "<input type='hidden' name='several' value='yes'>";
	echo "<input type='hidden' name='logical_number' value=''>";
	echo "<td>";
	echo $lang["networking"][47].":<select name='from_logical_number'>";
	for ($i=0;$i<100;$i++)
		echo "<option value='$i'>$i</option>";
	echo "</select>";
	echo $lang["networking"][48].":<select name='to_logical_number'>";
	for ($i=0;$i<100;$i++)
		echo "<option value='$i'>$i</option>";
	echo "</select>";

	echo "</td></tr>";
	}
	
	echo "<tr class='tab_bg_1'><td>".$lang["networking"][0].":</td>";
	echo "<td>";
	autocompletionTextField("name","glpi_networking_ports","name",$netport->fields["name"],20);	
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["networking"][16].":</td><td>";
		dropdownValue("glpi_dropdown_iface","iface", $netport->fields["iface"]);
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["networking"][14].":</td><td>";
	autocompletionTextField("ifaddr","glpi_networking_ports","ifaddr",$netport->fields["ifaddr"],20);	
	echo "</td></tr>\n";

	// Show device MAC adresses
	if ((!empty($netport->device_type)&&$netport->device_type==COMPUTER_TYPE)||($several!="yes"&&$devtype==COMPUTER_TYPE)){
		$comp=new Computer();

		if (!empty($netport->device_type))
		$comp->getFromDB($netport->device_ID,1);
		else 
		$comp->getFromDB($ondevice,1);

		$macs=array();
		$i=0;
		// Get MAC adresses :
		if (count($comp->devices)>0)	
			foreach ($comp->devices as $key => $val)
				if ($val['devType']==NETWORK_DEVICE&&!empty($val['specificity'])){
					$macs[$i]=$val['specificity'];
					$i++;
					}
		if (count($macs)>0){
			echo "<tr class='tab_bg_1'><td>".$lang["networking"][15].":</td><td>";
			echo "<select name='pre_mac'>";
			echo "<option value=''>------</option>";
			foreach ($macs as $key => $val){
			echo "<option value='".$val."' >$val</option>";	
			}
			echo "</select>";

			echo "</td></tr>\n";

			echo "<tr class='tab_bg_2'><td>&nbsp;</td>";
			echo "<td>".$lang["networking"][57];
			echo "</td></tr>\n";
			
		}
	}
	
	echo "<tr class='tab_bg_1'><td>".$lang["networking"][15].":</td><td>";
	autocompletionTextField("ifmac","glpi_networking_ports","ifmac",$netport->fields["ifmac"],25);	

	echo "</td></tr>\n";
	
	if ($several!="yes"){
	echo "<tr class='tab_bg_1'><td>".$lang["networking"][51].":</td>";
	
	echo "<td align='center' >";
		dropdownValue("glpi_dropdown_netpoint","netpoint", $netport->fields["netpoint"]);		
	echo "</td></tr>";
	}
	if ($ID) {
		echo "<tr class='tab_bg_2'>";
		echo "<td align='center'>";
		echo "<input type='hidden' name='ID' value=".$netport->fields["ID"].">";
		echo "<input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'>";
		echo "</td>";

		echo "<td align='center'>";
		echo "<input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'>";
		echo "</td></tr>";
	} else 
	{

		echo "<tr class='tab_bg_2'>";
		echo "<td align='center' colspan='2'>";
		echo "<input type='hidden' name='on_device' value='$ondevice'>";
		echo "<input type='hidden' name='device_type' value='$devtype'>";
		echo "<input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'>";
		echo "</td></tr>";
	}

	echo "</table></form></div>";	
	// SHOW VLAN 
	if ($ID){
	echo "<div align='center'>";
	echo "<form method='post' action=\"$target\">";
	echo "<input type='hidden' name='referer' value='$REFERER'>";
	echo "<input type='hidden' name='ID' value='$ID'>";

	echo "<table class='tab_cadre'><tr class='tab_bg_2'><td>";
	showPortVLAN($netport->fields["ID"],0,$REFERER);
	echo "</td></tr>";
	
	echo "<tr  class='tab_bg_2'><td>";
	echo $lang["networking"][55].":&nbsp;";
	dropdown("glpi_dropdown_vlan","vlan");
	echo "<input type='submit' name='assign_vlan' value='".$lang["buttons"][3]."' class='submit'>";
	echo "</td></tr>";
	
	echo "</table>";
	
	echo "</form>";
	


	
	echo "</div>";	

		
	}
}

function addNetport($input) {
	// Add Netport, nasty hack until we get PHP4-array-functions

	$netport = new Netport;
	
	// dump status
	unset($input['add']);
	unset($input['search']);

	// fill array for update 
	foreach ($input as $key => $val) {
		if ($key[0]!='_'&&(!isset($netport->fields[$key]) || $netport->fields[$key] != $input[$key])) {
			$netport->fields[$key] = $input[$key];
		}
	}
	
	return $netport->addToDB();
}

function updateNetport($input) {
	// Update a port

	$netport = new Netport;
	$netport->getFromDB($input["ID"]);

	// Fill the update-array with changes
	$x=0;
	$updates=array();
	foreach ($input as $key => $val) {
		if (array_key_exists($key,$netport->fields) && $netport->fields[$key] != $input[$key]) {
			$netport->fields[$key] = $input[$key];
			$updates[$x] = $key;
			$x++;
		}
	}
	$netport->updateInDB($updates);
}

function deleteNetport($input) {
	
	// Delete Netwire
	removeConnector($input["ID"]);	

	// Delete Netport
	$netport = new Netport;
	$netport->deleteFromDB($input["ID"]);



} 

function showPortsAdd($ID,$devtype) {
	
	GLOBAL $cfg_layout, $cfg_install, $lang;
	$db = new DB;
	switch($devtype) {
		case COMPUTER_TYPE :
			$device_real_table_name = "glpi_computers";
			break;
		case NETWORKING_TYPE :
			$device_real_table_name = "glpi_networking";
			break;
		case PRINTER_TYPE :
			$device_real_table_name = "glpi_printers";
			break;
		case PERIPHERAL_TYPE :
			$device_real_table_name = "glpi_peripherals";
			break;
	}
	$query = "SELECT location from ".$device_real_table_name." where ID = ".$ID."";
	$location = $db->result($db->query($query),0,"location");

	echo "<div align='center'><table class='tab_cadre' width='90%' cellpadding='2'>";
	echo "<tr>";
	echo "<td align='center' class='tab_bg_2'  >";
	echo "<a href=\"".$cfg_install["root"]."/networking/networking-port.php?on_device=$ID&amp;device_type=$devtype&amp;location=$location\"><b>";
	echo $lang["networking"][19];
	echo "</b></a></td>";
	echo "<td align='center' class='tab_bg_2' width='50%'>";
	echo "<a href=\"".$cfg_install["root"]."/networking/networking-port.php?on_device=$ID&amp;device_type=$devtype&amp;several=yes&amp;location=$location\"><b>";
	echo $lang["networking"][46];
	echo "</b></a></td>";

	echo "</tr>";
	echo "</table></div><br>";
}

function showConnection ($ID,$withtemplate='',$type=COMPUTER_TYPE) {

	GLOBAL $cfg_layout, $cfg_install, $lang;

	$contact = new Netport;
	$netport = new Netport;

	if ($contact->getContact($ID)) {
		$netport->getfromDB($contact->contact_id);
		$netport->getDeviceData($netport->fields["on_device"],$netport->fields["device_type"]);
		echo "\n\n<table border='0' cellspacing='0' width='100%'><tr ".($netport->deleted=='Y'?"class='tab_bg_2_2'":"").">";
		echo "<td><b>";
		echo "<a href=\"".$cfg_install["root"]."/networking/networking-port.php?ID=".$netport->fields["ID"]."\">";
		if (rtrim($netport->fields["name"])!="")
			echo $netport->fields["name"];
		else echo $lang["common"][0];
		echo "</a></b>";
		echo " ".$lang["networking"][25]." <b>";
		if ($netport->fields["device_type"]==COMPUTER_TYPE) {
			echo "<a href=\"".$cfg_install["root"]."/computers/computers-info-form.php?ID=".$netport->device_ID."\">";
		} else if ($netport->fields["device_type"]==NETWORKING_TYPE) {
			echo "<a href=\"".$cfg_install["root"]."/networking/networking-info-form.php?ID=".$netport->device_ID."\">";
		} else if ($netport->fields["device_type"]==PRINTER_TYPE) {
			echo "<a href=\"".$cfg_install["root"]."/printers/printers-info-form.php?ID=".$netport->device_ID."\">";
		} else if ($netport->fields["device_type"]==PERIPHERAL_TYPE) {
			echo "<a href=\"".$cfg_install["root"]."/peripherals/peripherals-info-form.php?ID=".$netport->device_ID."\">";
		}
		echo $netport->device_name;
		if ($cfg_layout['view_ID']) echo " (".$netport->device_ID.")";
		echo "</a>";
		echo "</b></td>";
		echo "<td align='right'><b>";
		if ($withtemplate!=2)
		echo "<a href=\"".$cfg_install["root"]."/networking/networking-port-disconnect.php?ID=$ID\">".$lang["buttons"][10]."</a>";
		else "&nbsp;";
		echo "</b></td>";
		echo "</tr></table>";
		
	} else {
		echo "<table border='0' cellspacing='0' width='100%'><tr>";
		echo "<td>".$lang["networking"][26]."</td>";
		echo "<td align='right'>";
		if ($withtemplate!=2&&$withtemplate!=1){
			echo "<form method='post' action=\"".$cfg_install["root"]."/networking/networking-port-connect.php\">";
			echo "<input type='hidden' name='connect' value='connect'>";
			echo "<input type='hidden' name='sport' value='$ID'>";
			dropdownConnectPort($ID,$type,"dport");
			echo "<input type='submit' value=\"".$lang["buttons"][9]."\" class='submit'>";
			echo "</form>";
			}
		else echo "&nbsp;";
		echo "</td>";
		echo "</tr></table>";
	}
}	


///// Wire the Ports /////


function makeConnector($sport,$dport) {

	GLOBAL $cfg_layout, $cfg_install, $lang;
	
	$db = new DB;
	// Get netpoint for $sport and $dport
	$ps=new Netport;
	$ps->getFromDB($sport);
	$nps="";
	$ips="";
	$macs="";
	if (isset($ps->fields["netpoint"])&&$ps->fields["netpoint"]!=0)
		$nps=$ps->fields["netpoint"];
	if (isset($ps->fields["ifaddr"]))
		$ips=$ps->fields["ifaddr"];
	if (isset($ps->fields["ifmac"]))
		$macs=$ps->fields["ifmac"];
		
		
	$pd=new Netport;
	$pd->getFromDB($dport);
	$npd="";
	$ipd="";
	$macd="";
	if (isset($pd->fields["netpoint"])&&$pd->fields["netpoint"]!=0)
		$npd=$pd->fields["netpoint"];
	if (isset($pd->fields["ifaddr"]))
		$ipd=$pd->fields["ifaddr"];
	if (isset($pd->fields["ifmac"]))
		$macd=$pd->fields["ifmac"];

	// Update unknown IP
	$updates[0]="ifaddr";
	if (empty($ips)&&!empty($ipd)){
		$ps->fields["ifaddr"]=$ipd;
		$ps->updateInDB($updates);
		echo "<div align='center'><b>".$lang["connect"][19]."</b></div>";
		}
	else if (!empty($ips)&&empty($ipd)){
		$pd->fields["ifaddr"]=$ips;		
		$pd->updateInDB($updates);
		echo "<div align='center'><b>".$lang["connect"][19]."</b></div>";
		}
	else if ($ips!=$ipd){
		echo "<div align='center'><b>".$lang["connect"][20]."</b></div>";
		}
	// Update unknown MAC
	$updates[0]="ifmac";
	if (empty($macs)&&!empty($macd)){
		$ps->fields["ifmac"]=$macd;
		$ps->updateInDB($updates);
		echo "<div align='center'><b>".$lang["connect"][21]."</b></div>";
		}
	else if (!empty($macs)&&empty($macd)){
		$pd->fields["ifmac"]=$macs;		
		$pd->updateInDB($updates);
		echo "<div align='center'><b>".$lang["connect"][21]."</b></div>";
		}
	else if ($macs!=$macd){
		echo "<div align='center'><b>".$lang["connect"][22]."</b></div>";
		}
	// Update unknown netpoint
	$updates[0]="netpoint";
	if (empty($nps)&&!empty($npd)){
		$ps->fields["netpoint"]=$npd;
		$ps->updateInDB($updates);
		echo "<div align='center'><b>".$lang["connect"][17]."</b></div>";
		}
	else if (!empty($nps)&&empty($npd)){
		$pd->fields["netpoint"]=$nps;		
		$pd->updateInDB($updates);
		echo "<div align='center'><b>".$lang["connect"][17]."</b></div>";
		}
	else if ($nps!=$npd){
		echo "<div align='center'><b>".$lang["connect"][18]."</b></div>";
		}
	
	$query = "INSERT INTO glpi_networking_wire VALUES (NULL,$sport,$dport)";
	if ($result = $db->query($query)) {
		$source=new CommonItem;
		$source->getFromDB($ps->fields['device_type'],$ps->fields['on_device']);
		$dest=new CommonItem;
		$dest->getFromDB($pd->fields['device_type'],$pd->fields['on_device']);
		echo "<br><div align='center'><b>".$lang["networking"][44]." ".$source->getName()." - ".$ps->fields['logical_number']."  (".$ps->fields['ifaddr']." - ".$ps->fields['ifmac'].") ".$lang["networking"][45]." ".$dest->getName()." - ".$pd->fields['logical_number']." (".$pd->fields['ifaddr']." - ".$pd->fields['ifmac'].") </b></div>";
		return true;
	} else {
		return false;
	}

}

function removeConnector($ID) {

	GLOBAL $cfg_layout, $cfg_install;
	
	// Update to blank networking item
	$nw=new Netwire;
	if ($ID2=$nw->getOppositeContact($ID)){
	
	$np1=new Netport;
	$np2=new Netport;
	$np1->getFromDB($ID);
	$np2->getFromDB($ID2);
	$npnet=-1;
	$npdev=-1;
	if ($np1->fields["device_type"]!=NETWORKING_TYPE&&$np2->fields["device_type"]==NETWORKING_TYPE){
		$npnet=$ID2;
		$npdev=$ID;
		}
	if ($np2->fields["device_type"]!=NETWORKING_TYPE&&$np1->fields["device_type"]==NETWORKING_TYPE){
		$npnet=$ID;
		$npdev=$ID2;
		}
	$db = new DB;
	if ($npnet!=-1&&$npdev!=-1){
		// Unset MAC and IP fron networking device
		$query = "UPDATE glpi_networking_ports SET ifaddr='', ifmac='' WHERE ID='$npnet'";	
		$db->query($query);
		// Unset netpoint from common device
		$query = "UPDATE glpi_networking_ports SET netpoint=NULL WHERE ID='$npdev'";	
		$db->query($query);

	}
	
	$query = "DELETE FROM glpi_networking_wire WHERE (end1 = '$ID' OR end2 = '$ID')";
	if ($result=$db->query($query)) {
		return true;
	} else {
		return false;
	}
	} else return false;
}


?>
