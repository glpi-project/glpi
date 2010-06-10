<?php
/*
 
 ----------------------------------------------------------------------
GLPI - Gestionnaire libre de parc informatique
 Copyright (C) 2002 by the INDEPNET Development Team.
 http://indepnet.net/   http://glpi.indepnet.org
 ----------------------------------------------------------------------
 Based on:
IRMA, Information Resource-Management and Administration
Christian Bauer, turin@incubus.de 

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
 ----------------------------------------------------------------------
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------
*/
 

include ("_relpos.php");
///// Manage Netdevices /////

function listNetdevices() {
	// List all netdevices

	GLOBAL $cfg_install,$cfg_layout, $lang;
	$db = new DB;

	$query = "SELECT ID FROM networking";

	if ($result = $db->query($query)) {
		if ($db->numrows($result)>0) {
			echo "<center><table border=0><tr>";
			echo "<th>".$lang["networking"][0]."</th><th>".$lang["networking"][1]."</th>";
			echo "<th>".$lang["networking"][2]."</th><th>".$lang["networking"][3]."</th>";
			echo "<th>".$lang["networking"][9]."</th></tr>";
			$i=0;
			while ($devid=$db->fetch_row($result)) {
				$netdev = new Netdevice;
				$netdev->getfromDB(current($devid));
				echo "<tr bgcolor=\"".$cfg_layout["tab_bg_1"]."\">";
				echo "<td><a href=\"".$cfg_install["root"]."/networking/networking-info-form.php?ID=".$netdev->fields["ID"]."\">".$netdev->fields["name"];
				echo " (".$netdev->fields["ID"].")</a></td>";
				echo "<td>".$netdev->fields["location"]."</td>";
				echo "<td>".$netdev->fields["type"]."</td>";
				echo "<td>".$netdev->fields["contact"]."</td>";
				echo "<td>".$netdev->fields["date_mod"]."</td>";
				echo "</tr>";
			}	
			echo "</table></center>";
		} else {
			echo "<b><center>".$lang["networking"][38]."</center></b>";
		}
	}
}

function showNetworkingForm ($target,$ID) {
	// Show device or blank form
	
	GLOBAL $cfg_layout,$cfg_install, $lang;

	$netdev = new Netdevice;

	echo "<center><form name=form method=post action=\"$target\">";
	echo "<table border=0 cellpadding=2>";
	echo "<tr><th colspan=2><b>";
	if (!$ID) {
		echo $lang["networking"][11].":";
	} else {
		$netdev->getfromDB($ID);
		echo $lang["networking"][12]." ID $ID:";
	}		
	echo "</b></th></tr>";
	
	echo "<tr><td bgcolor=".$cfg_layout["tab_bg_1"]." valign=top>";

	echo "<table cellpadding=0 cellspacing=0 border=0>\n";

	echo "<tr><td>".$lang["networking"][0].":	</td>";
	echo "<td><input type=text name=name value=\"".$netdev->fields["name"]."\" size=10></td>";
	echo "</tr>";

	echo "<tr><td>".$lang["networking"][1].": 	</td><td>";
		dropdownValue("dropdown_locations", "location", $netdev->fields["location"]);
	echo "</td></tr>";

	echo "<tr><td>".$lang["networking"][4].":	</td>";
	echo "<td><input type=text name=contact_num value=\"".$netdev->fields["contact_num"]."\" size=5></td>";
	echo "</tr>";

	echo "<tr><td>".$lang["networking"][3].":	</td>";
	echo "<td><input type=text name=contact size=12 value=\"".$netdev->fields["contact"]."\"></td>";
	echo "</tr>";

	echo "</table>";

	echo "</td>\n";	
	echo "<td bgcolor=".$cfg_layout["tab_bg_1"]." valign=top>";

	echo "<table cellpadding=0 cellspacing=0 border=0";

	echo "<tr><td>".$lang["networking"][2].": 	</td><td>";
		dropdownValue("type_networking", "type", $netdev->fields["type"]);
	echo "</td></tr>";
		
	echo "<tr><td>".$lang["networking"][5].":	</td>";
	echo "<td><input type=text name=ram value=\"".$netdev->fields["ram"]."\" size=3></td>";
	echo "</tr>";

	echo "<tr><td>".$lang["networking"][6].":	</td>";
	echo "<td><input type=text name=serial size=12 value=\"".$netdev->fields["serial"]."\"></td>";
	echo "</tr>";

	echo "<tr><td>".$lang["networking"][7].":</td>";
	echo "<td><input type=text size=12 name=otherserial value=\"".$netdev->fields["otherserial"]."\"></td>";
	echo "</tr>";
	echo "</table>";
	
	// A travailler il existe des gros bugs ...
	echo "</td>\n";	
	echo "</tr>";
	echo "<tr>";
	echo "<td bgcolor=".$cfg_layout["tab_bg_1"]." valign=top colspan=2>";
	
	echo "<table width=100% cellpadding=0 cellspacing=0 border=0><tr><td valign=top>";
	echo "<tr><td>".$lang["networking"][39].":	</td>";
	echo "<td><input type=text name='achat_date' readonly size=10 value=\"0000-00-00\">";
	echo "&nbsp; <input name='button' type='button' onClick=\"window.open('mycalendar.php?form=form&elem=achat_date','Calendrier','width=200,height=220')\" value='".$lang["buttons"][15]."...'>";
	echo "&nbsp; <input name='button_reset' type='button' onClick=\"document.forms['form'].achat_date.value='0000-00-00'\" value='reset'>";
  echo "</td></tr>";
	
	echo "<tr><td>".$lang["networking"][40].":	</td>";
	echo "<td><input type=text name='date_fin_garantie' readonly size=10 value=\"0000-00-00\">";
	echo "&nbsp; <input name='button' type='button' readonly onClick=\"window.open('mycalendar.php?form=form&elem=date_fin_garantie','Calendrier','width=200,height=220')\" value='".$lang["buttons"][15]."...'>";
	echo "&nbsp; <input name='button_reset' type='button' onClick=\"document.forms['form'].date_fin_garantie.value='0000-00-00'\" value='reset'>";
  echo "</td></tr>";
	
	echo "<tr><td>".$lang["networking"][41].":	</td>";
	echo "<td>";
	if ($netdev->fields["maintenance"] == 1) {
				echo " OUI <input type=radio name='maintenance' value=1 checked>";
				echo "&nbsp; &nbsp; NON <input type=radio name='maintenance' value=0>";
		} else {
				echo " OUI <input type=radio name='maintenance' value=1>";
				echo "&nbsp; &nbsp; NON <input type=radio name='maintenance' value=0 checked >";
			   }
	echo "</td></tr></table>";
		


	echo "</td>\n";	
	echo "</tr>";
	echo "<tr>";
	echo "<td bgcolor=".$cfg_layout["tab_bg_1"]." valign=top colspan=2>";

	echo "<table width=100% cellpadding=0 cellspacing=0 border=0><tr><td valign=top>";
	echo $lang["networking"][8].":	</td>";
	echo "<td align=center><textarea cols=35 rows=4 name=comments wrap=soft>".$netdev->fields["comments"]."</textarea>";
	echo "</td></tr></table>";

	echo "</td>";
	echo "</tr>";
	
	if (!$ID) {

		echo "<tr>";
		echo "<td bgcolor=".$cfg_layout["tab_bg_2"]." valign=top colspan=2>";
		echo "<center><input type=submit name=add value=\"".$lang["buttons"][8]."\"></center>";
		echo "</td>";
		echo "</form></tr>";

		echo "</table></center>";

	} else {

		echo "<tr>";
		echo "<td bgcolor=".$cfg_layout["tab_bg_2"]." valign=top>";
		echo "<input type=hidden name=ID value=\"$ID\">\n";
		echo "<center><input type=submit name=update value=\"".$lang["buttons"][7]."\"></center>";
		echo "</td></form>\n\n";
		echo "<form action=\"$target\" method=post>\n";
		echo "<td bgcolor=".$cfg_layout["tab_bg_2"]." valign=top>\n";
		echo "<input type=hidden name=ID value=\"$ID\">\n";
		echo "<center><input type=submit name=delete value=\"".$lang["buttons"][6]."\"></center>";
		echo "</td>";
		echo "</form></tr>";

		echo "</table></center>";

		showPorts($ID,2);

		showPortsAdd($ID,2);
	}
}

function addNetdevice($input) {
	// Add Netdevice, nasty hack until we get PHP4-array-functions

	$netdev = new Netdevice;

	// dump the status
	$null = array_pop($input);
	
	// fill array for update
	for ($i=0; $i < count($input); $i++) {
		list($key,$val) = each($input);
		if ($netdev->fields[$key] != $input[$key]) {
			$netdev->fields[$key] = $input[$key];
		}
	}

	if ($netdev->addToDB()) {
		return true;
	} else {
		return false;
	}
}

function updateNetdevice($input) {
	// Update a netdevice in the database

	$netdev = new Netdevice;
	$netdev->getFromDB($input["ID"]);

	// set new date and make sure it gets updated
	$updates[0]= "date_mod";
	$netdev->fields["date_mod"] = date("Y-m-d H:i:s");
 
 	// Pop off the last two attributes, no longer needed
	$null=array_pop($input);
	$null=array_pop($input);

	// Get all flags and fill with 0 if unchecked in form
	for ($i=0; $i < count($netdev->fields); $i++) {
		list($key,$val) = each($netdev->fields);
		if (eregi("\.*flag\.*",$key)) {
			if (!$input[$key]) {
				$input[$key]=0;
			}
		}
	}
		
	// Fill the update-array with changes
	$x=1;
	for ($i=0; $i < count($input); $i++) {
		list($key,$val) = each($input);
		if ($netdev->fields[$key] != $input[$key]) {
			$netdev->fields[$key] = $input[$key];
			$updates[$x] = $key;
			$x++;
		}
	}

	$netdev->updateInDB($updates);

}

function deleteNetdevice($input) {
	// Delete Netdevice
	
	$netdev = new Netdevice;
	$netdev->deleteFromDB($input["ID"]);
	
} 



///// Manage Ports on Devices /////

function showPorts ($device,$device_type) {
	
	GLOBAL $cfg_layout,$cfg_install, $lang;
	
	$db = new DB;

	$query = "SELECT ID FROM networking_ports WHERE (on_device = $device AND device_type = $device_type) ORDER BY logical_number";
	if ($result = $db->query($query)) {
		if ($db->numrows($result)!=0) { 
			echo "<br><center><table cellpadding=2 width=90%>";
			echo "<tr><th colspan=6>";
			echo $db->numrows($result)." ";
			if ($db->numrows($result)<2) {
				echo $lang["networking"][37];
			} else {
				echo $lang["networking"][13];
			}
			echo ":</th></tr>";
			echo "<tr><th>#</th><th>".$lang["networking"][0]."</th>";
			echo "<th>".$lang["networking"][14]."</th><th>".$lang["networking"][15]."</th>";
			echo "<th>".$lang["networking"][16]."</th><th>".$lang["networking"][17].":</th></tr>\n";
			$i=0;
			while ($devid=$db->fetch_row($result)) {
				$netport = new Netport;
				$netport->getfromDB(current($devid));
				echo "<tr bgcolor=\"".$cfg_layout["tab_bg_1"]."\">";
				echo "<td>".$netport->fields["logical_number"]."</td>";
				echo "<td align=center><b>";
				echo "<a href=\"".$cfg_install["root"]."/networking/networking-port.php?ID=".$netport->fields["ID"]."\">";
				echo $netport->fields["name"];
				echo "</a>";
				echo "</b></td>";
				echo "<td>".$netport->fields["ifaddr"]."</td>";
				echo "<td>".$netport->fields["ifmac"]."</td>";
				echo "<td>".$netport->fields["iface"]."</td>";
				echo "<td>";
					showConnection($netport->fields["ID"]);
				echo "</td>";
				echo "</tr>";
			}	
			echo "</table></center>\n\n";
		}
	}
}



function showNetportForm($target,$ID,$ondevice,$devtype) {

	GLOBAL $cfg_install, $cfg_layout, $lang;

	$netport = new Netport;
	if ($ID) {
		$netport->getFromDB($ID);
		$netport->getDeviceData($netport->fields["on_device"],$netport->fields["device_type"]);
	}	

	echo "<center><table><tr>";
	echo "<th colspan=2>".$lang["networking"][20].":</th>";
	echo "</tr>";
	echo "<form method=post action=\"$target\">";

	echo "<tr bgcolor=\"".$cfg_layout["tab_bg_1"]."\"><td>".$lang["networking"][21].":</td>";
	echo "<td><input type=text size=5 name=logical_number value=\"".$netport->fields["logical_number"]."\">";
	echo "</td></tr>";

	echo "<tr bgcolor=\"".$cfg_layout["tab_bg_1"]."\"><td>".$lang["networking"][0]."</td>";
	echo "<td><input type=text size=20 name=name value=\"".$netport->fields["name"]."\">";
	echo "</td></tr>";

	echo "<tr bgcolor=\"".$cfg_layout["tab_bg_1"]."\"><td>".$lang["networking"][16]."</td><td>";
		dropdownValue("dropdown_iface","iface", $netport->fields["iface"]);
	echo "</td></tr>";

	echo "<tr bgcolor=\"".$cfg_layout["tab_bg_1"]."\"><td>".$lang["networking"][22]."</td>";
	echo "<td><input type=text size=20 name=ifaddr value=\"".$netport->fields["ifaddr"]."\">";
	echo "</td></tr>";

	echo "<tr bgcolor=\"".$cfg_layout["tab_bg_1"]."\"><td>".$lang["networking"][23]."</td>";
	echo "<td><input type=text size=25 name=ifmac value=\"".$netport->fields["ifmac"]."\">";
	echo "</td></tr>";

	if ($ID) {
		echo "<tr bgcolor=\"".$cfg_layout["tab_bg_1"]."\"><td>".$lang["networking"][24]."</td>";
		echo "<td>";
			showConnection($netport->fields["ID"]);
		echo "</td></tr>";

		echo "<tr bgcolor=\"".$cfg_layout["tab_bg_2"]."\">";
		echo "<td align=center>";
		echo "<input type=hidden name=ID value=".$netport->fields["ID"].">";
		echo "<input type=submit name=update value=\"".$lang["buttons"][7]."\">";
		echo "</td></form>";

		echo "<form method=post action=$target>";
		echo "<input type=hidden name=ID value=$ID>";
		echo "<td align=center>";
		echo "<input type=submit name=delete value=\"".$lang["buttons"][6]."\">";
		echo "</td></tr></form>";
	} else {

		echo "<tr bgcolor=\"".$cfg_layout["tab_bg_2"]."\">";
		echo "<td align=center colspan=2>";
		echo "<input type=hidden name=on_device value=".$ondevice.">";
		echo "<input type=hidden name=device_type value=".$devtype.">";
		echo "<input type=submit name=add value=\"".$lang["buttons"][8]."\">";
		echo "</td></form>";
	}

	echo "</table>";	
}

function addNetport($input) {
	// Add Netport, nasty hack until we get PHP4-array-functions

	$netport = new Netport;

	// dump status
	$null = array_pop($input);
	
	// fill array for update 
	for ($i=0; $i < count($input); $i++) {
		list($key,$val) = each($input);
		if ($netport->fields[$key] != $input[$key]) {
			$netport->fields[$key] = $input[$key];
		}
	}

	if ($netport->addToDB()) {
		return true;
	} else {
		return false;
	}
}

function updateNetport($input) {
	// Update a port

	$netport = new Netport;
	$netport->getFromDB($input["ID"]);

	// Pop off the last two attributes, no longer needed
	$null=array_pop($input);
	$null=array_pop($input);
	
	// Fill the update-array with changes
	$x=0;
	for ($i=0; $i < count($input); $i++) {
		list($key,$val) = each($input);
		if ($netport->fields[$key] != $input[$key]) {
			$netport->fields[$key] = $input[$key];
			$updates[$x] = $key;
			$x++;
		}
	}

	$netport->updateInDB($updates);

}

function deleteNetport($input) {
	// Delete Netport
	
	$netport = new Netport;
	$netport->deleteFromDB($input["ID"]);
	
} 

function showPortsAdd($ID,$devtype) {
	
	GLOBAL $cfg_layout, $cfg_install, $lang;
	
	echo "<center><table border=0 width=90% cellpadding=2>";
	echo "<tr><td align=center bgcolor=\"".$cfg_layout["tab_bg_2"]."\"><b>";
	echo "<a href=\"".$cfg_install["root"]."/networking/networking-port.php?ondevice=$ID&devtype=$devtype\">";
	echo $lang["networking"][19];
	echo "</a></b></td></tr>";
	echo "</table></center><br><br>";
}

function showConnection ($ID) {

	GLOBAL $cfg_layout, $cfg_install, $lang;

	$contact = new Netport;
	if ($contact->getContact($ID)) {
		$netport = new Netport;
		$netport->getfromDB($contact->contact_id);
		$netport->getDeviceData($netport->fields["on_device"],$netport->fields["device_type"]);
		echo "\n\n<table border=0 cellspacing=0 width=100%><tr>";
		echo "<td><b>";
		echo "<a href=\"".$cfg_install["root"]."/networking/networking-port.php?ID=".$netport->fields["ID"]."\">";
		echo $netport->fields["name"];
		echo "</b></a>";
		echo " ".$lang["networking"][25]." <b>";
		if ($netport->fields["device_type"]==1) {
			echo "<a href=\"".$cfg_install["root"]."/computers/computers-info-form.php?ID=".$netport->device_ID."\">";
		} else if ($netport->fields["device_type"]==2) {
			echo "<a href=\"".$cfg_install["root"]."/networking/networking-info-form.php?ID=".$netport->device_ID."\">";
		} else if ($netport->fields["device_type"]==3) {
			echo "<a href=\"".$cfg_install["root"]."/printers/printers-info-form.php?ID=".$netport->device_ID."\">";
		}
		echo $netport->device_name." (".$netport->device_ID.")";
		echo "</a>";
		echo "</b></td>";
		echo "<td align=right><b>";
		echo "<a href=\"".$cfg_install["root"]."/networking/networking-port-disconnect.php?ID=$ID\">".$lang["buttons"][10]."</a>";
		echo "</b></td>";
		echo "</tr></table>";
		
	} else {
		echo "<table border=0 cellspacing=0 width=100%><tr>";
		echo "<td>".$lang["networking"][26]."</td>";
		echo "<td align=right><b>";
		echo "<a href=\"".$cfg_install["root"]."/networking/networking-port-connect.php?ID=$ID\">".$lang["buttons"][9]."</a>";
		echo "</b></td>";
		echo "</tr></table>";
	}
}	


///// Wire the Ports /////


function showConnectorSearch($target,$ID) {

	GLOBAL $cfg_layout,$cfg_install, $lang;

	echo "<center><table border=0>";
	echo "<tr><th colspan=2>".$lang["networking"][27]." $ID ".$lang["networking"][28].":</th></tr>";

	echo "<tr bgcolor=\"".$cfg_layout["tab_bg_1"]."\">";
	echo "<form method=post action=\"$target\">";
	echo "<td>".$lang["networking"][29]." <select name=type>";
	echo "<option value=name>".$lang["networking"][0]."</option>";
	echo "<option value=id>ID</option>";
	echo "</select>";
	echo $lang["networking"][30]." <input type=text size=10 name=comp>";
	echo "<input type=hidden name=pID1 value=$ID>";
	echo "<input type=hidden name=next value=\"compsearch\">";
	echo "</td><td bgcolor=\"".$cfg_layout["tab_bg_2"]."\">";
	echo "<input type=submit value=\"".$lang["buttons"][11]."\">";
	echo "</td></tr>";	
	echo "</form>";
	
	echo "<tr bgcolor=\"".$cfg_layout["tab_bg_1"]."\">";
	echo "<form method=post action=\"$target\">";
	echo "<td>".$lang["networking"][31].":";
	$db = new DB;
	$query = "SELECT ID,name,location from networking";
	$result = $db->query($query);
	$number = $db->numrows($result);
	echo "<select name=dID>";
	while ($i < $number) {
		$dID = $db->result($result, $i, "ID");
		$name = $db->result($result, $i, "name");
		$location = $db->result($result, $i, "location");
		echo "<option value=$dID>$name ($location)</option>";
		$i++;
	}
	echo "</select>";
	echo "<input type=hidden name=pID1 value=$ID>";
	echo "<input type=hidden name=next value=\"showports\">";
	echo "<input type=hidden name=device_type value=2>";
	echo "</td><td bgcolor=\"".$cfg_layout["tab_bg_2"]."\">";
	echo "<input type=submit value=\"".$lang["buttons"][11]."\">";
	echo "</td></tr>";
	echo "</form>";
		
	echo "</table>";	
}

function listConnectorComputers($target,$input) {
	
	GLOBAL $cfg_layout,$cfg_install, $lang;

	$pID1 = $input["pID1"];

	echo "<center><table border=0>";
	echo "<tr><th colspan=2>".$lang["networking"][27]." $pID1 ".$lang["networking"][32].". ".$lang["networking"][33].":</th></tr>";
	echo "<form method=post action=\"$target\"><tr><td>";

	echo "<tr bgcolor=\"".$cfg_layout["tab_bg_1"]."\">";
	echo "<td align=center>";

	$db = new DB;
	if ($input["type"] == "name") {
		$query = "SELECT ID,name,location from computers WHERE (name LIKE '%".$input["comp"]."%')";
	} else {
		$query = "SELECT ID,name,location from computers WHERE ID = ".$input["comp"];
	} 
	$result = $db->query($query);
	$number = $db->numrows($result);
	echo "<select name=dID>";
	while ($i < $number) {
		$dID = $db->result($result, $i, "ID");
		$name = $db->result($result, $i, "name");
		$location = $db->result($result, $i, "location");
		echo "<option value=$dID>$name ($location)</option>";
		$i++;
	}
	echo  "</select>";

	echo "</td>";
	echo "<td bgcolor=\"".$cfg_layout["tab_bg_2"]."\" align=center>";
	echo "<input type=hidden name=device_type value=1>";
	echo "<input type=hidden name=pID1 value=\"".$input["pID1"]."\">";
	echo "<input type=hidden name=next value=\"showports\">";
	echo "<input type=submit value=\"".$lang["buttons"][11]."\">";
	echo "</td></form></tr></table>";	

}

function listConnectorPorts($target,$input) {

	GLOBAL $cfg_layout,$cfg_install,$lang;
	
	$pID1 = $input["pID1"];

	$db = new DB;
	$query = "SELECT * FROM networking_ports WHERE (on_device = ".$input["dID"]." AND device_type = ".$input["device_type"].") ORDER BY logical_number";
	$result = $db->query($query);
	$number = $db->numrows($result);

	if ($number < 1) {
		echo "<center><b>".$lang["networking"][34]."</b></center>";
	} else {

		echo "<center><table border=0 cellspacing=2 width=90%>";
		echo "<tr><th>".$lang["networking"][27]." $pID1 ".$lang["networking"][35].". ".$lang["networking"][36]." ".$input["dID"].":</th></tr>";
		echo "</table></center>";

		echo "\n\n<br><center><table border=0 cellpadding=2 width=90%>";
		echo "<tr><th>#</th><th>".$lang["networking"][0]."</th>";
		echo "<th>".$lang["networking"][14]."</th><th>".$lang["networking"][15]."</th>";
		echo "<th>".$lang["networking"][16]."</th><th>".$lang["networking"][17].":</th></tr>\n";

		while ($data = $db->fetch_array($result)) {
			$pID2 = $data["ID"];
		
			$contact = new Netport;
			
			echo "<tr bgcolor=\"".$cfg_layout["tab_bg_1"]."\">";
			echo "<td>".$data["logical_number"]."</td>";
			echo "<td>";
			echo "<a href=\"".$cfg_install["root"]."/networking/networking-port.php?ID=".$data["ID"]."\">";
			echo $data["name"];
			echo "</a>";
			echo "</td>";
			echo "<td>".$data["ifaddr"]."</td>";
			echo "<td>".$data["ifmac"]."</td>";
			echo "<td>".$data["iface"]."</td>";
			echo "<td>";

			if ($contact->getContact($pID2)) {
				$netport = new Netport;
				$netport->getfromDB($contact->contact_id);
				$netport->getDeviceData($netport->fields["on_device"],$netport->fields["device_type"]);
				echo "\n\n<table border=0 cellspacing=0 width=100%><tr>";
				echo "<td>";
				echo "<a href=\"".$cfg_install["root"]."/networking/networking-port.php?ID=".$netport->fields["ID"]."\">";
				echo $netport->fields["name"];
				echo "</a>";
				echo " on ";
				echo "<a href=\"".$cfg_install["root"]."/computers/computers-info-form.php?ID=".$netport->device_ID."\">";
				echo $netport->device_name." (".$netport->device_ID.")";
				echo "</a>";
				echo "</td>";
				echo "<td align=right><b>";
				echo "<a href=\"".$cfg_install["root"]."/networking/networking-port-disconnect.php?ID=$ID\">".$lang["buttons"][9]."</a>";
				echo "</b></td>";
				echo "</tr></table>";
		
			} else {
				echo "<table border=0 cellspacing=0 width=100%><tr>";
				echo "<td>".$lang["networking"][26]."</td>";
				echo "<td align=right><b>";
				echo "<a href=\"$target?next=connect&sport=$pID1&dport=$pID2\">".$lang["buttons"][9]."</a>";
				echo "</b></td>";
				echo "</tr></table>";
			}
			
			echo "</td>";
			echo "</tr>";
		}
		echo "</table>";
	}
}

function makeConnector($sport,$dport) {

	GLOBAL $cfg_layout, $cfg_install;
	
	$db = new DB;
	$query = "INSERT INTO networking_wire VALUES (NULL,$sport,$dport)";
	if ($result = $db->query($query)) {
		echo "<center><b>Port $sport is now connected to port $dport.</b></center>";
		return true;
	} else {
		return false;
	}

}

function removeConnector($ID) {

	GLOBAL $cfg_layout, $cfg_install;
	
	$db = new DB;
	$query = "DELETE FROM networking_wire WHERE (end1 = '$ID' OR end2 = '$ID')";
	if ($result=$db->query($query)) {
		return true;
	} else {
		return false;
	}
}


?>
