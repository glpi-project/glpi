<?php
/*
 
  ----------------------------------------------------------------------
GLPI - Gestionnaire libre de parc informatique
 Copyright (C) 2002 by the INDEPNET Development Team.
 Bazile Lebeau, baaz@indepnet.net - Jean-Mathieu Doléans, jmd@indepnet.net
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

function titleNetdevices() {
         // titre
         
         GLOBAL  $lang,$HTMLRel;

          echo "<div align='center'><table border='0'><tr><td>";
          echo "<img src=\"".$HTMLRel."pics/networking.png\" alt='".$lang["networking"][11]."' title='".$lang["networking"][11]."'></td><td><a  class='icon_consol' href=\"networking-info-form.php\"><b>".$lang["networking"][11]."</b></a>";
          echo "</td></tr></table></div>";
 
}

function searchFormNetworking() {
	// Netwokirng Search Form
	
	GLOBAL $cfg_install, $cfg_layout, $layout, $lang;

	$option["networking.name"]				= $lang["printers"][5];
	$option["networking.ID"]				= $lang["printers"][19];
	$option["glpi_dropdown_locations.name"]			= $lang["printers"][6];
	$option["glpi_type_networking.name"]				= $lang["printers"][9];
	$option["networking.serial"]			= $lang["printers"][10];
	$option["networking.otherserial"]		= $lang["printers"][11]	;
	$option["networking.comments"]			= $lang["printers"][12];
	$option["networking.contact"]			= $lang["printers"][8];
	$option["networking.contact_num"]		= $lang["printers"][7];
	$option["networking.date_mod"]			= $lang["printers"][16];
	

	echo "<form method='get' action=\"".$cfg_install["root"]."/networking/networking-search.php\">";
	echo "<div align='center'><table  width='750' class='tab_cadre'>";
	echo "<tr><th colspan='2'><b>".$lang["search"][0].":</b></th></tr>";
	echo "<tr class='tab_bg_1'>";
	echo "<td align='center'>";
	echo "<select name=\"field\" size='1'>";

        reset($option);
	foreach ($option as $key => $val) {
		echo "<option value=$key>$val\n";
	}
	echo "</select>&nbsp;";
	echo $lang["search"][1];
	echo "&nbsp;<select name='phrasetype' size='1'>";
	echo "<option value='contains'>".$lang["search"][2]."</option>";
	echo "<option value='exact'>".$lang["search"][3]."</option>";
	echo "</select>";
	echo "<input type='text' size='10' name=\"contains\">";
	echo "&nbsp;";
	echo $lang["search"][4];
	echo "&nbsp;<select name='sort' size='1'>";
	reset($option);
	foreach ($option as $key => $val) {
		echo "<option value=$key>$val\n";
	}
	echo "</select> ";
	echo "</td><td width='80' align='center' class='tab_bg_2'>";
	echo "<input type='submit' value=\"".$lang["buttons"][0]."\" class='submit'>";
	echo "</td></tr></table></div></form>";
}


function showNetworkingList($target,$username,$field,$phrasetype,$contains,$sort,$order,$start) {

	// Lists networking

	GLOBAL $cfg_install, $cfg_layout, $cfg_features, $lang;

	// Build query
	if ($phrasetype == "contains") {
		$where = "($field LIKE '%".$contains."%')";
	} else {
		$where = "($field LIKE '".$contains."')";
	}
	if (!$start) {
		$start = 0;
	}
	if (!$order) {
		$order = "ASC";
	}
	$query = "select networking.ID from glpi_networking as networking LEFT JOIN glpi_dropdown_locations on networking.location=glpi_dropdown_locations.ID ";
	$query .= "LEFT JOIN glpi_type_networking on networking.type = glpi_type_networking.ID ";
	$query .= "where $where ORDER BY $sort $order";
	// Get it from database	
	$db = new DB;
	if ($result = $db->query($query)) {
		$numrows = $db->numrows($result);

		// Limit the result, if no limit applies, use prior result
		if ($numrows>$cfg_features["list_limit"]) {
			$query_limit = $query ." LIMIT $start,".$cfg_features["list_limit"]." ";
			$result_limit = $db->query($query_limit);
			$numrows_limit = $db->numrows($result_limit);

		} else {
			$numrows_limit = $numrows;
			$result_limit = $result;
		}


		if ($numrows_limit>0) {
			// Produce headline
			echo "<center><table class='tab_cadre'><tr>";

			// Name
			echo "<th>";
			if ($sort=="networking.name") {
				echo "&middot;&nbsp;";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=networking.name&order=ASC&start=$start\">";
			echo $lang["networking"][0]."</a></th>";

			// Location			
			echo "<th>";
			if ($sort=="networking.location") {
				echo "&middot;&nbsp;";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=networking.location&order=ASC&start=$start\">";
			echo $lang["networking"][1]."</a></th>";

			// Type
			echo "<th>";
			if ($sort=="networking.type") {
				echo "&middot;&nbsp;";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=networking.type&order=ASC&start=$start\">";
			echo $lang["networking"][2]."</a></th>";

			// Last modified		
			echo "<th>";
			if ($sort=="networking.date_mod") {
				echo "&middot;&nbsp;";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=networking.date_mod&order=DESC&start=$start\">";
			echo $lang["networking"][9]."</a></th>";
	
			echo "</tr>";

			for ($i=0; $i < $numrows_limit; $i++) {
				$ID = $db->result($result_limit, $i, "ID");
				$networking = new Netdevice;
				$networking->getfromDB($ID);
				echo "<tr class='tab_bg_2'>";
				echo "<td><b>";
				echo "<a href=\"".$cfg_install["root"]."/networking/networking-info-form.php?ID=$ID\">";
				echo $networking->fields["name"]." (".$networking->fields["ID"].")";
				echo "</a></b></td>";
				echo "<td>". getDropdownName("glpi_dropdown_locations",$networking->fields["location"]) ."</td>";
				echo "<td>". getDropdownName("glpi_type_monitors",$networking->fields["type"]) ."</td>";
				echo "<td>".$networking->fields["date_mod"]."</td>";
				echo "</tr>";
			}

			// Close Table
			echo "</table></center>";

			// Pager
			$parameters="field=$field&phrasetype=$phrasetype&contains=$contains&sort=$sort&order=$order";
			printPager($start,$numrows,$target,$parameters);

		} else {
			echo "<center><b>".$lang["networking"][38]."</b></center>";
			echo "<hr noshade>";
		}
	}
}




function listNetdevices() {
	// List all netdevices

	GLOBAL $cfg_install,$cfg_layout, $lang;
	$db = new DB;

	$query = "SELECT ID FROM glpi_networking";

	if ($result = $db->query($query)) {
		if ($db->numrows($result)>0) {
			echo "<div align='center'><table  class='tab_cadre'><tr>";
			echo "<th>".$lang["networking"][0]."</th><th>".$lang["networking"][1]."</th>";
			echo "<th>".$lang["networking"][2]."</th><th>".$lang["networking"][3]."</th>";
			echo "<th>".$lang["networking"][9]."</th></tr>";
			$i=0;
			while ($devid=$db->fetch_row($result)) {
				$netdev = new Netdevice;
				$netdev->getfromDB(current($devid));
				echo "<tr class='tab_bg_1'>";
				echo "<td><a href=\"".$cfg_install["root"]."/networking/networking-info-form.php?ID=".$netdev->fields["ID"]."\">".$netdev->fields["name"];
				echo " (".$netdev->fields["ID"].")</a></td>";
				echo "<td>".getDropdownName("glpi_dropdown_locations",$netdev->fields["location"])."</td>";
				echo "<td>".getDropdownName("glpi_type_networking",$netdev->fields["type"])."</td>";
				echo "<td>".$netdev->fields["contact"]."</td>";
				echo "<td>".$netdev->fields["date_mod"]."</td>";
				echo "</tr>";
			}	
			echo "</table></div>";
		} else {
			echo "<b><div align='center'>".$lang["networking"][38]."</div></b>";
		}
	}
}

function showNetworkingForm ($target,$ID) {
	// Show device or blank form
	
	GLOBAL $cfg_layout,$cfg_install, $lang;

	$netdev = new Netdevice;

	echo "<div align='center'><form name='form' method='post' action=\"$target\">";
	echo "<table class='tab_cadre' cellpadding='2'>";
	echo "<tr><th colspan='2'><b>";
	if (empty($ID)) {
		echo $lang["networking"][11].":";
		$netdev->getEmpty();
	} else {
		$netdev->getfromDB($ID);
		echo $lang["networking"][12]." ID $ID:";
	}		
	echo "</b></th></tr>";
	
	echo "<tr><td class='tab_bg_1' valign='top'>";

	echo "<table cellpadding='0' cellspacing='0' border='0'>\n";

	echo "<tr><td>".$lang["networking"][0].":	</td>";
	echo "<td><input type='text' name='name' value=\"".$netdev->fields["name"]."\" size='12'></td>";
	echo "</tr>";

	echo "<tr><td>".$lang["networking"][1].": 	</td><td>";
		dropdownValue("glpi_dropdown_locations", "location", $netdev->fields["location"]);
	echo "</td></tr>";

	echo "<tr><td>".$lang["networking"][4].":	</td>";
	echo "<td><input type='text' name='contact_num' value=\"".$netdev->fields["contact_num"]."\" size='12'></td>";
	echo "</tr>";

	echo "<tr><td>".$lang["networking"][3].":	</td>";
	echo "<td><input type='text' name='contact' size='12' value=\"".$netdev->fields["contact"]."\"></td>";
	echo "</tr>";
	echo "</table>";

	echo "</td>\n";	
	echo "<td class='tab_bg_1' valign='top'>";

	echo "<table cellpadding='0' cellspacing='0' border='0'";

	echo "<tr><td>".$lang["networking"][2].": 	</td><td>";
		dropdownValue("glpi_type_networking", "type", $netdev->fields["type"]);
	echo "</td></tr>";
	
	echo "<tr><td>".$lang["networking"][49].": 	</td><td>";
	dropdownValue("glpi_dropdown_firmware", "firmware", $netdev->fields["firmware"]);
	echo "</td></tr>";
		
	echo "<tr><td>".$lang["networking"][5].":	</td>";
	echo "<td><input type='text' name='ram' value=\"".$netdev->fields["ram"]."\" size='3'></td>";
	echo "</tr>";

	echo "<tr><td>".$lang["networking"][6].":	</td>";
	echo "<td><input type='text' name='serial' size='12' value=\"".$netdev->fields["serial"]."\"></td>";
	echo "</tr>";

	echo "<tr><td>".$lang["networking"][7].":</td>";
	echo "<td><input type='text' size='12' name='otherserial' value=\"".$netdev->fields["otherserial"]."\"></td>";
	echo "</tr>";
	echo "</table>";
	
	echo "</td>\n";	
	echo "</tr>";
	echo "<tr>";
	echo "<td class='tab_bg_1' valign='top' colspan='2'>";
	
	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'><tr><td valign='top'>";
	echo "<tr><td>".$lang["networking"][39].":	</td>";
	echo "<td><input type='text' name='achat_date' readonly size='10' value='".$netdev->fields["achat_date"]."'>";
	echo "&nbsp; <input name='button' type='button' class='button' onClick=\"window.open('mycalendar.php?form=form&elem=achat_date','Calendrier','width=200,height=220')\" value='".$lang["buttons"][15]."...'>";
	echo "&nbsp; <input name='button_reset' type='button' class='button' onClick=\"document.forms['form'].achat_date.value='0000-00-00'\" value='reset'>";
  echo "</td></tr>";
	
	echo "<tr><td>".$lang["networking"][40].":	</td>";
	echo "<td><input type='text' name='date_fin_garantie' readonly size='10' value='".$netdev->fields["date_fin_garantie"]."'>";
	echo "&nbsp; <input name='button' type='button' class='button' readonly onClick=\"window.open('mycalendar.php?form=form&elem=date_fin_garantie','Calendrier','width=200,height=220')\" value='".$lang["buttons"][15]."...'>";
	echo "&nbsp; <input name='button_reset' type='button' class='button' onClick=\"document.forms['form'].date_fin_garantie.value='0000-00-00'\" value='reset'>";
  echo "</td></tr>";
	
	echo "<tr><td>".$lang["networking"][41].":	</td>";
	echo "<td>";
	if ($netdev->fields["maintenance"] == 1) {
				echo " OUI <input type='radio' name='maintenance' value='1' checked>";
				echo "&nbsp; &nbsp; NON <input type='radio' name='maintenance' value='0'>";
		} else {
				echo " OUI <input type='radio' name='maintenance' value='1'>";
				echo "&nbsp; &nbsp; NON <input type='radio' name='maintenance' value='0' checked >";
			   }
	echo "</td></tr></table>";
		


	echo "</td>\n";	
	echo "</tr>";
	echo "<tr>";
	echo "<td class='tab_bg_1' valign='top' colspan='2'>";

	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'><tr><td valign='top'>";
	echo $lang["networking"][8].":	</td>";
	echo "<td align='center'><textarea cols='35' rows='4' name='comments' >".$netdev->fields["comments"]."</textarea>";
	echo "</td></tr></table>";

	echo "</td>";
	echo "</tr>";
	
	if (!$ID) {

		echo "<tr>";
		echo "<td class='tab_bg_2' valign='top' colspan='2'>";
		echo "<div align='center'><input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'></div>";
		echo "</td>";
		echo "</form></tr>";

		echo "</table></div>";

	} else {

		echo "<tr>";
		echo "<td class='tab_bg_2' valign='top'>";
		echo "<input type='hidden' name='ID' value=\"$ID\">\n";
		echo "<div align='center'><input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'></div>";
		echo "</td></form>\n\n";
		echo "<form action=\"$target\" method='post'>\n";
		echo "<td class='tab_bg_2' valign='top'>\n";
		echo "<input type='hidden' name='ID' value=\"$ID\">\n";
		echo "<div align='center'><input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'></div>";
		echo "</td>";
		echo "</form></tr>";

		echo "</table></div>";

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
	foreach ($input as $key => $val) {
		if (empty($netdev->fields[$key]) || $netdev->fields[$key]  != $input[$key]) {
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

	$query = "SELECT ID FROM glpi_networking_ports WHERE (on_device = $device AND device_type = $device_type) ORDER BY logical_number";
	if ($result = $db->query($query)) {
		if ($db->numrows($result)!=0) { 
			echo "<br><div align='center'><table class='tab_cadre' width='90%'>";
			echo "<tr><th colspan='6'>";
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
				echo "<tr class='tab_bg_1'>";
				echo "<td align='center'><b>";
				echo "<a href=\"".$cfg_install["root"]."/networking/networking-port.php?ID=".$netport->fields["ID"]."\">";
				echo $netport->fields["logical_number"];
				echo "</a>";
				echo "</b></td>";
        echo "<td>".$netport->fields["name"]."</td>";
				echo "<td>".$netport->fields["ifaddr"]."</td>";
				echo "<td>".$netport->fields["ifmac"]."</td>";
				echo "<td>".getDropdownName("glpi_dropdown_iface",$netport->fields["iface"])."</td>";
				echo "<td>";
					showConnection($netport->fields["ID"]);
				echo "</td>";
				echo "</tr>";
			}	
			echo "</table></div>\n\n";
		}
	}
}



function showNetportForm($target,$ID,$ondevice,$devtype,$several) {

	GLOBAL $cfg_install, $cfg_layout, $lang;

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

	echo "<div align='center'><table><tr>";
	echo "<th colspan='2'>".$lang["networking"][20].":</th>";
	echo "</tr>";
	echo "<form method='post' action=\"$target\">";

	if ($several!="yes"){
	echo "<tr class='tab_bg_1'><td>".$lang["networking"][21].":</td>";
	echo "<td><input type='text' size=5 name='logical_number' value=\"".$netport->fields["logical_number"]."\">";
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
	echo $lang["networking"][48].":<select name=to_logical_number>";
	for ($i=0;$i<100;$i++)
		echo "<option value='$i'>$i</option>";
	echo "</select>";

	echo "</td></tr>";
	}
	
	echo "<tr class='tab_bg_1'><td>".$lang["networking"][0]."</td>";
	echo "<td><input type='text' size=20 name=name value=\"".$netport->fields["name"]."\">";
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["networking"][16]."</td><td>";
		dropdownValue("glpi_dropdown_iface","iface", $netport->fields["iface"]);
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["networking"][22]."</td>";
	echo "<td><input type='text' size=20 name=ifaddr value=\"".$netport->fields["ifaddr"]."\">";
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["networking"][23]."</td>";
	echo "<td><input type='text' size=25 name=ifmac value=\"".$netport->fields["ifmac"]."\">";
	echo "</td></tr>";

	if ($ID) {
		echo "<tr class='tab_bg_1'><td>".$lang["networking"][24]."</td>";
		echo "<td>";
			showConnection($netport->fields["ID"]);
		echo "</td></tr>";

		echo "<tr class='tab_bg_2'>";
		echo "<td align='center'>";
		echo "<input type='hidden' name=ID value=".$netport->fields["ID"].">";
		echo "<input type='submit' name=update value=\"".$lang["buttons"][7]."\" class='submit'>";
		echo "</td></form>";

		echo "<form method='post' action=$target>";
		echo "<input type='hidden' name=ID value=$ID>";
		echo "<td align='center'>";
		echo "<input type='submit' name=delete value=\"".$lang["buttons"][6]."\" class='submit'>";
		echo "</td></tr></form>";
	} else 
	{

		echo "<tr class='tab_bg_2'>";
		echo "<td align='center' colspan='2'>";
		echo "<input type='hidden' name=on_device value=".$ondevice.">";
		echo "<input type='hidden' name=device_type value=".$devtype.">";
		echo "<input type='submit' name=add value=\"".$lang["buttons"][8]."\" class='submit'>";
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
	foreach ($input as $key => $val) {
		if (!isset($netport->fields[$key]) || $netport->fields[$key] != $input[$key]) {
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
	foreach ($input as $key => $val) {
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
	
	echo "<div align='center'><table class='tab_cadre' width='750' cellpadding='2'>";
	echo "<tr>";
	echo "<td align='center' class='tab_bg_2'  >";
	echo "<a href=\"".$cfg_install["root"]."/networking/networking-port.php?ondevice=$ID&devtype=$devtype\"><b>";
	echo $lang["networking"][19];
	echo "</b></a></td>";
	echo "<td align='center' class='tab_bg_2' width='50%'>";
	echo "<a href=\"".$cfg_install["root"]."/networking/networking-port.php?ondevice=$ID&devtype=$devtype&several=yes\"><b>";
	echo $lang["networking"][46];
	echo "</b></a></td>";

	echo "</tr>";
	echo "</table></div><br>";
}

function showConnection ($ID) {

	GLOBAL $cfg_layout, $cfg_install, $lang;

	$contact = new Netport;
	if ($contact->getContact($ID)) {
		$netport = new Netport;
		$netport->getfromDB($contact->contact_id);
		$netport->getDeviceData($netport->fields["on_device"],$netport->fields["device_type"]);
		echo "\n\n<table border='0' cellspacing='0' width='100%'><tr>";
		echo "<td><b>";
		echo "<a href=\"".$cfg_install["root"]."/networking/networking-port.php?ID=".$netport->fields["ID"]."\">";
		echo $netport->fields["name"];
		echo "</a></b>";
		echo " ".$lang["networking"][25]." <b>";
		if ($netport->fields["device_type"]==1) {
			echo "<a href=\"".$cfg_install["root"]."/computers/computers-info-form.php?ID=".$netport->device_ID."\">";
		} else if ($netport->fields["device_type"]==2) {
			echo "<a href=\"".$cfg_install["root"]."/networking/networking-info-form.php?ID=".$netport->device_ID."\">";
		} else if ($netport->fields["device_type"]==3) {
			echo "<a href=\"".$cfg_install["root"]."/networkings/networkings-info-form.php?ID=".$netport->device_ID."\">";
		}
		echo $netport->device_name." (".$netport->device_ID.")";
		echo "</a>";
		echo "</b></td>";
		echo "<td align='right'><b>";
		echo "<a href=\"".$cfg_install["root"]."/networking/networking-port-disconnect.php?ID=$ID\">".$lang["buttons"][10]."</a>";
		echo "</b></td>";
		echo "</tr></table>";
		
	} else {
		echo "<table border='0' cellspacing='0' width='100%'><tr>";
		echo "<td>".$lang["networking"][26]."</td>";
		echo "<td align='right'><b>";
		echo "<a href=\"".$cfg_install["root"]."/networking/networking-port-connect.php?ID=$ID\">".$lang["buttons"][9]."</a>";
		echo "</b></td>";
		echo "</tr></table>";
	}
}	


///// Wire the Ports /////


function showConnectorSearch($target,$ID) {

	GLOBAL $cfg_layout,$cfg_install, $lang;

	echo "<div align='center'><table border='0'>";
	echo "<tr><th colspan='2'>".$lang["networking"][27]." $ID ".$lang["networking"][28].":</th></tr>";

	echo "<tr class='tab_bg_1'>";
	echo "<form method='post' action=\"$target\">";
	echo "<td>".$lang["networking"][29]." <select name=type>";
	echo "<option value=name>".$lang["networking"][0]."</option>";
	echo "<option value=id>ID</option>";
	echo "</select>";
	echo $lang["networking"][30]." <input type='text' size=10 name=comp>";
	echo "<input type='hidden' name=pID1 value=$ID>";
	echo "<input type='hidden' name=next value=\"compsearch\">";
	echo "</td><td class='tab_bg_2'>";
	echo "<input type='submit' value=\"".$lang["buttons"][11]."\" class='submit'>";
	echo "</td></tr>";	
	echo "</form>";
	
	echo "<tr class='tab_bg_1'>";
	echo "<form method='post' action=\"$target\">";
	echo "<td>".$lang["networking"][31].":";
	$db = new DB;
	$query = "SELECT glpi_networking.ID AS ID, glpi_networking.name AS name, glpi_dropdown_locations.name as location from glpi_networking,glpi_dropdown_locations WHERE glpi_networking.location = glpi_dropdown_locations.id";
	$result = $db->query($query);
	$number = $db->numrows($result);
	echo "<select name=dID>";
	while ($i < $number)
	{
		$dID = $db->result($result, $i, "ID");
		$name = $db->result($result, $i, "name");
		$location = $db->result($result, $i, "location");
		echo "<option value=$dID>$name ($location)</option>";
		$i++;
	}
	echo "</select>";
	echo "<input type='hidden' name='pID1' value=$ID>";
	echo "<input type='hidden' name='next' value=\"showports\">";
	echo "<input type='hidden' name='device_type' value='2'>";
	echo "</td><td class='tab_bg_2'>";
	echo "<input type='submit' value=\"".$lang["buttons"][11]."\" class='submit'>";
	echo "</td></tr>";
	echo "</form>";
		
	echo "</table>";	
}

function listConnectorComputers($target,$input) {
	
	GLOBAL $cfg_layout,$cfg_install, $lang;

	$pID1 = $input["pID1"];

	echo "<div align='center'><table border='0'>";
	echo "<tr><th colspan='2'>".$lang["networking"][27]." $pID1 ".$lang["networking"][32].". ".$lang["networking"][33].":</th></tr>";
	echo "<form method='post' action=\"$target\"><tr><td>";

	echo "<tr class='tab_bg_1'>";
	echo "<td align='center'>";

	$db = new DB;
	if ($input["type"] == "name") {
		$query = "SELECT glpi_computers.ID as ID, glpi_computers.name as name, glpi_dropdown_locations.name as location from glpi_computers, glpi_dropdown_locations WHERE  glpi_computers.location = glpi_dropdown_locations.id AND glpi_computers.name LIKE '%".$input["comp"]."%'";
	} else {
		$query = "SELECT glpi_computers.ID as ID, glpi_computers.name as name, glpi_dropdown_locations.name as location from glpi_computers, glpi_dropdown_locations WHERE glpi_computers.location = glpi_dropdown_locations.id AND glpi_computers.ID = ".$input["comp"];
	} 

	$result = $db->query($query);
	$number = $db->numrows($result);
	echo "<select name=dID>";
	while ($i < $number)
	{
		$dID = $db->result($result, $i, "ID");
		$name = $db->result($result, $i, "name");
		$location = $db->result($result, $i, "location");
		echo "<option value=$dID>$name ($location)</option>";
		$i++;
	}
	echo  "</select>";

	echo "</td>";
	echo "<td class='tab_bg_2' align='center'>";
	echo "<input type='hidden' name=device_type value=1>";
	echo "<input type='hidden' name=pID1 value=\"".$pID1."\">";
	echo "<input type='hidden' name=next value=\"showports\">";
	echo "<input type='submit' value=\"".$lang["buttons"][11]."\">";
	echo "</td></form></tr></table>";	

}

function listConnectorPorts($target,$input) {

	GLOBAL $cfg_layout,$cfg_install,$lang;
	
	$pID1 = $input["pID1"];

	$db = new DB;
	$query = "SELECT * FROM glpi_networking_ports WHERE (on_device = ".$input["dID"]." AND device_type = ".$input["device_type"].") ORDER BY logical_number";
	$result = $db->query($query);
	$number = $db->numrows($result);

	if ($number < 1) {
		echo "<div align='center'><b>".$lang["networking"][34]."</b></div>";
	} else {

		echo "<div align='center'><table border='0' cellspacing=2 width='90%'>";
		echo "<tr><th>".$lang["networking"][27]." $pID1 ".$lang["networking"][35].". ".$lang["networking"][36]." ".$input["dID"].":</th></tr>";
		echo "</table></div>";

		echo "\n\n<br><div align='center'><table border='0' cellpadding='2' width='90%'>";
		echo "<tr><th>#</th><th>".$lang["networking"][0]."</th>";
		echo "<th>".$lang["networking"][14]."</th><th>".$lang["networking"][15]."</th>";
		echo "<th>".$lang["networking"][16]."</th><th>".$lang["networking"][17].":</th></tr>\n";

		while ($data = $db->fetch_array($result)) {
			$pID2 = $data["ID"];
		
			$contact = new Netport;
			
			echo "<tr class='tab_bg_1'>";
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
				echo "\n\n<table border='0' cellspacing='0' width='100%'><tr>";
				echo "<td>";
				echo "<a href=\"".$cfg_install["root"]."/networking/networking-port.php?ID=".$netport->fields["ID"]."\">";
				echo $netport->fields["name"];
				echo "</a>";
				echo " on ";
				echo "<a href=\"".$cfg_install["root"]."/computers/computers-info-form.php?ID=".$netport->device_ID."\">";
				echo $netport->device_name." (".$netport->device_ID.")";
				echo "</a>";
				echo "</td>";
				echo "<td align='right'><b>";
				echo "<a href=\"".$cfg_install["root"]."/networking/networking-port-disconnect.php?ID=".$netport->fields["ID"]."\">".$lang["buttons"][10]."</a>";
				echo "</b></td>";
				echo "</tr></table>";
		
			} else {
				echo "<table border='0' cellspacing='0' width='100%'><tr>";
				echo "<td>".$lang["networking"][26]."</td>";
				echo "<td align='right'><b>";
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

	GLOBAL $cfg_layout, $cfg_install, $lang;
	
	$db = new DB;
	$query = "INSERT INTO glpi_networking_wire VALUES (NULL,$sport,$dport)";
	if ($result = $db->query($query)) {
		echo "<div align='center'><b>".$lang["networking"][44]." ".$sport." ".$lang["networking"][45]." ".$dport."</b></div>";
		return true;
	} else {
		return false;
	}

}

function removeConnector($ID) {

	GLOBAL $cfg_layout, $cfg_install;
	
	$db = new DB;
	$query = "DELETE FROM glpi_networking_wire WHERE (end1 = '$ID' OR end2 = '$ID')";
	if ($result=$db->query($query)) {
		return true;
	} else {
		return false;
	}
}


?>
