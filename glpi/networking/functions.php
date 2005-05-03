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
	global $lang;
	
	echo "<div id='barre_onglets'><ul id='onglet'>";
	echo "<li "; if ($actif=="1"){ echo "class='actif'";} echo  "><a href='$target&onglet=1'>".$lang["title"][26]."</a></li>";
	echo "<li "; if ($actif=="4") {echo "class='actif'";} echo "><a href='$target&onglet=4'>".$lang["Menu"][26]."</a></li>";
	echo "<li "; if ($actif=="5") {echo "class='actif'";} echo "><a href='$target&onglet=5'>".$lang["title"][25]."</a></li>";
	if(empty($withtemplate)){
	echo "<li "; if ($actif=="6") {echo "class='actif'";} echo "><a href='$target&onglet=6'>".$lang["title"][28]."</a></li>";
	echo "<li class='invisible'>&nbsp;</li>";
	echo "<li "; if ($actif=="-1") {echo "class='actif'";} echo "><a href='$target&onglet=-1'>".$lang["title"][29]."</a></li>";
	}
	
	echo "<li class='invisible'>&nbsp;</li>";
	
	if (empty($withtemplate)&&preg_match("/\?ID=([0-9]+)/",$target,$ereg)){
	$ID=$ereg[1];
	$next=getNextItem("glpi_networking",$ID);
	$prev=getPreviousItem("glpi_networking",$ID);
	$cleantarget=preg_replace("/\?ID=([0-9]+)/","",$target);
		if ($prev>0) echo "<li><a href='$cleantarget?ID=$prev'><</a></li>";
	if ($next>0) echo "<li><a href='$cleantarget?ID=$next'>></a></li>";
	}

	echo "</ul></div>";
	
}

function searchFormNetworking($field="",$phrasetype= "",$contains="",$sort= "",$deleted= "") {
	// Netwokirng Search Form
	
	GLOBAL $cfg_install, $cfg_layout, $layout, $lang,$HTMLRel;

	$option["glpi_networking.name"]				= $lang["networking"][0];
	$option["glpi_networking.ID"]				= $lang["networking"][50];
	$option["glpi_dropdown_locations.name"]			= $lang["networking"][1];
	$option["glpi_type_networking.name"]				= $lang["networking"][2];
	$option["glpi_networking.serial"]			= $lang["networking"][6];
	$option["glpi_networking.otherserial"]		= $lang["networking"][7]	;
	$option["glpi_dropdown_firmware.name"]		= $lang["networking"][49]	;
	$option["glpi_networking.comments"]			= $lang["networking"][8];
	$option["glpi_networking.contact"]			= $lang["networking"][3];
	$option["glpi_networking.contact_num"]		= $lang["networking"][4];
	$option["glpi_networking.date_mod"]			= $lang["networking"][9];
	$option["glpi_networking_ports.ifaddr"] = $lang["networking"][14];
	$option["glpi_networking_ports.ifmac"] = $lang["networking"][15];
	$option["glpi_dropdown_netpoint.name"]			= $lang["networking"][51];
	$option["glpi_enterprises.name"]			= $lang["common"][5];
	$option["resptech.name"]			=$lang["common"][10];

	echo "<form method='get' action=\"".$cfg_install["root"]."/networking/networking-search.php\">";
	echo "<div align='center'><table  width='750' class='tab_cadre'>";
	echo "<tr><th colspan='3'><b>".$lang["search"][0].":</b></th></tr>";
	echo "<tr class='tab_bg_1'>";
	
	echo "<td align='center'>";
	echo "<input type='text' size='15' name=\"contains\" value=\"". $contains ."\" >";
	echo "&nbsp;";
	echo $lang["search"][10]."&nbsp;";
	echo "<select name=\"field\" size='1'>";
        echo "<option value='all' ";
	if($field == "all") echo "selected";
	echo ">".$lang["search"][7]."</option>";
        reset($option);
	foreach ($option as $key => $val) {
		echo "<option value=\"".$key."\""; 
		if($key == $field) echo "selected";
		echo ">". $val ."</option>\n";
	}
	echo "</select>&nbsp;";
	/*
	echo $lang["search"][1];
	echo "&nbsp;<select name='phrasetype' size='1' >";
	echo "<option value='contains'";
	if($phrasetype == "contains") echo "selected";
	echo ">".$lang["search"][2]."</option>";
	echo "<option value='exact'";
	if($phrasetype == "exact") echo "selected";
	echo ">".$lang["search"][3]."</option>";
	echo "</select>";
	*/
	
	echo $lang["search"][4];
	echo "&nbsp;<select name='sort' size='1'>";
	reset($option);
	foreach ($option as $key => $val) {
		echo "<option value=\"".$key."\"";
		if($key == $sort) echo "selected";
		echo ">".$val."</option>\n";
	}
	echo "</select>";
	echo "</td><td><input type='checkbox' name='deleted' ".($deleted=='Y'?" checked ":"").">";
	echo "<img src=\"".$HTMLRel."pics/showdeleted.png\" alt='".$lang["common"][3]."' title='".$lang["common"][3]."'>";
	echo "</td><td width='80' align='center' class='tab_bg_2'>";
	echo "<input type='submit' value=\"".$lang["buttons"][0]."\" class='submit'>";
	echo "</td></tr></table></div></form>";
}


function showNetworkingList($target,$username,$field,$phrasetype,$contains,$sort,$order,$start,$deleted) {

	// Lists networking

	GLOBAL $cfg_install, $cfg_layout, $cfg_features, $lang, $HTMLRel;;

	$db = new DB;

	// Build query
	if($field=="all") {
		$where = " (";
		$fields = $db->list_fields("glpi_networking");
		$columns = $db->num_fields($fields);
		
		for ($i = 0; $i < $columns; $i++) {
			if($i != 0) {
				$where .= " OR ";
			}
			$coco = $db->field_name($fields, $i);

			if($coco == "firmware") {
				$where .= " glpi_dropdown_firmware.name LIKE '%".$contains."%'";
			}
			elseif($coco == "location") {
				$where .= getRealSearchForTreeItem("glpi_dropdown_locations",$contains);
			}
			elseif($coco == "FK_glpi_enterprise") {
				$where .= "glpi_enterprises.name LIKE '%".$contains."%'";
			}
			elseif ($coco=="tech_num"){
				$where .= " resptech.name LIKE '%".$contains."%'";
			} 
			elseif($coco == "type") {
				$where .= " glpi_type_networking.name LIKE '%".$contains."%'";
			}
			else {
   				$where .= "glpi_networking.".$coco . " LIKE '%".$contains."%'";
			}
		}
		$where .= " OR glpi_networking_ports.ifaddr LIKE '%".$contains."%'";
		$where .= " OR glpi_networking_ports.ifmac LIKE '%".$contains."%'";
		$where .= " OR glpi_dropdown_netpoint.name LIKE '%".$contains."%'";
		$where .= ")";
	}
	else {
		if ($field=="glpi_dropdown_locations.name"){
			$where = getRealSearchForTreeItem("glpi_dropdown_locations",$contains);
		}		
		else if ($phrasetype == "contains") {
			$where = "($field LIKE '%".$contains."%')";
		}
		else {
			$where = "($field LIKE '".$contains."')";
		}
	}

	if (!$start) {
		$start = 0;
	}
	if (!$order) {
		$order = "ASC";
	}
	$query = "select DISTINCT glpi_networking.ID from glpi_networking LEFT JOIN glpi_dropdown_locations on glpi_networking.location=glpi_dropdown_locations.ID ";
	$query .= "LEFT JOIN glpi_type_networking on glpi_networking.type = glpi_type_networking.ID ";
	$query .= "LEFT JOIN glpi_dropdown_firmware on glpi_networking.firmware = glpi_dropdown_firmware.ID ";
	$query .= "LEFT JOIN glpi_networking_ports on (glpi_networking.ID = glpi_networking_ports.on_device AND  glpi_networking_ports.device_type='2')";	
	$query .= "LEFT JOIN glpi_dropdown_netpoint on (glpi_dropdown_netpoint.ID = glpi_networking_ports.netpoint)";
	$query.= " LEFT JOIN glpi_enterprises ON (glpi_enterprises.ID = glpi_networking.FK_glpi_enterprise ) ";
	$query.= " LEFT JOIN glpi_users as resptech ON (resptech.ID = glpi_networking.tech_num ) ";
	$query .= "where $where AND glpi_networking.deleted='$deleted' AND glpi_networking.is_template = '0' ORDER BY $sort $order";

	// Get it from database	
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
			if ($sort=="glpi_networking.name") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png
\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png
\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_networking.name&order=".($order=="ASC"?"DESC":"ASC")."&start=$start\">";
			echo $lang["networking"][0]."</a></th>";

			// Manufacturer		
			echo "<th>";
			if ($sort=="glpi_enterprises.name") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png
\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png
\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_enterprises.name&order=".($order=="ASC"?"DESC":"ASC")."&start=$start\">";
			echo $lang["common"][5]."</a></th>";
			
			// Location			
			echo "<th>";
			if ($sort=="glpi_dropdown_locations.name") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png
\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png
\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_dropdown_locations.name&order=".($order=="ASC"?"DESC":"ASC")."&start=$start\">";
			echo $lang["networking"][1]."</a></th>";

			// Type
			echo "<th>";
			if ($sort=="glpi_type_networking.name") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png
\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png
\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_type_networking.name&order=".($order=="ASC"?"DESC":"ASC")."&start=$start\">";
			echo $lang["networking"][2]."</a></th>";

			
			// Firmware
			echo "<th>";
			if ($sort=="glpi_dropdown_firmware.name") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png
\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png
\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_dropdown_firmware.name&order=".($order=="ASC"?"DESC":"ASC")."&start=$start\">";
			echo $lang["networking"][49]."</a></th>";

			
			
			// Last modified		
			echo "<th>";
			if ($sort=="glpi_networking.date_mod") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png
\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png
\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_networking.date_mod&order=".($order=="ASC"?"DESC":"ASC")."&start=$start\">";
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
				echo "<td>". getDropdownName("glpi_enterprises",$networking->fields["FK_glpi_enterprise"]) ."</td>";
				echo "<td>". getDropdownName("glpi_dropdown_locations",$networking->fields["location"]) ."</td>";
				echo "<td>". getDropdownName("glpi_type_networking",$networking->fields["type"]) ."</td>";
				echo "<td>". getDropdownName("glpi_dropdown_firmware",$networking->fields["firmware"]) ."</td>";
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
			$date = date("Y-m-d H:i:s");
		} elseif(!empty($withtemplate) && $withtemplate == 1) { 
			$template = "newtemplate";
			$datestring = $lang["computers"][14].": ";
			$date = date("Y-m-d H:i:s");
		} else {
			$datestring = $lang["computers"][11]." : ";
			$date = $netdev->fields["date_mod"];
			$template = false;
		}


	echo "<div align='center'><form name='form' method='post' action=\"$target\">";

		if(strcmp($template,"newtemplate") === 0) {
			echo "<input type=\"hidden\" name=\"is_template\" value=\"1\" />";
		}

	echo "<table width='700' class='tab_cadre' cellpadding='2'>";

		echo "<tr><th align='center' >";
		if(!$template) {
			echo $lang["networking"][54].": ".$netdev->fields["ID"];
		}elseif (strcmp($template,"newcomp") === 0) {
			echo $lang["networking"][53].": ".$netdev->fields["tplname"];
		}elseif (strcmp($template,"newtemplate") === 0) {
			echo $lang["common"][6]."&nbsp;: <input type='text' name='tplname' value=\"".$netdev->fields["tplname"]."\" size='20'>";
		}
		echo "</th><th  align='center'>".$datestring.$date;
		echo "</th></tr>";

	
	echo "<tr><td class='tab_bg_1' valign='top'>";

	echo "<table cellpadding='1px' cellspacing='0' border='0'>\n";

	echo "<tr><td>".$lang["networking"][0].":	</td>";
	echo "<td><input type='text' name='name' value=\"".$netdev->fields["name"]."\" size='20'></td>";
	echo "</tr>";

	echo "<tr><td>".$lang["networking"][1].": 	</td><td>";
		dropdownValue("glpi_dropdown_locations", "location", $netdev->fields["location"]);
	echo "</td></tr>";
	
	echo "<tr class='tab_bg_1'><td>".$lang["common"][10].": 	</td><td colspan='2'>";
		dropdownUsersID( $netdev->fields["tech_num"],"tech_num");
	echo "</td></tr>";
		
	echo "<tr><td>".$lang["networking"][4].":	</td>";
	echo "<td><input type='text' name='contact_num' value=\"".$netdev->fields["contact_num"]."\" size='20'></td>";
	echo "</tr>";

	echo "<tr><td>".$lang["networking"][3].":	</td>";
	echo "<td><input type='text' name='contact' size='20' value=\"".$netdev->fields["contact"]."\"></td>";
	echo "</tr>";
	
	if (!$template){
	echo "<tr><td>".$lang["reservation"][24].":</td><td><b>";
	showReservationForm(NETWORKING_TYPE,$ID);
	echo "</b></td></tr>";
	}

		if (!$template){
		echo "<td>".$lang["state"][0]."&nbsp;:</td><td><b>";
		$si=new StateItem();
		$si->getfromDB(NETWORKING_TYPE,$netdev->fields["ID"]);
		dropdownValue("glpi_dropdown_state", "state",$si->fields["state"]);
		echo "</b></td>";
		} else echo "<td>&nbsp;</td><td>&nbsp;</td>";

	echo "</table>";

	echo "</td>\n";	
	echo "<td class='tab_bg_1' valign='top'>";

	echo "<table cellpadding='1px' cellspacing='0' border='0'";

	echo "<tr><td>".$lang["networking"][2].": 	</td><td>";
		dropdownValue("glpi_type_networking", "type", $netdev->fields["type"]);
	echo "</td></tr>";
	
	echo "<tr class='tab_bg_1'><td>".$lang["common"][5].": 	</td><td colspan='2'>";
		dropdownValue("glpi_enterprises","FK_glpi_enterprise",$netdev->fields["FK_glpi_enterprise"]);
	echo "</td></tr>";
	
	echo "<tr><td>".$lang["networking"][49].": 	</td><td>";
	dropdownValue("glpi_dropdown_firmware", "firmware", $netdev->fields["firmware"]);
	echo "</td></tr>";
		
	echo "<tr><td>".$lang["networking"][5].":	</td>";
	echo "<td><input type='text' name='ram' value=\"".$netdev->fields["ram"]."\" size='10'></td>";
	echo "</tr>";

	echo "<tr><td>".$lang["networking"][6].":	</td>";
	echo "<td><input type='text' name='serial' size='20' value=\"".$netdev->fields["serial"]."\"></td>";
	echo "</tr>";

	echo "<tr><td>".$lang["networking"][7].":</td>";
	echo "<td><input type='text' size='20' name='otherserial' value=\"".$netdev->fields["otherserial"]."\"></td>";
	echo "</tr>";
	
	echo "<tr><td>".$lang["networking"][14].":</td>";
	echo "<td><input type='text' size='20' name='ifaddr' value=\"".$netdev->fields["ifaddr"]."\"></td>";
	echo "</tr>";

	echo "<tr><td>".$lang["networking"][15].":</td>";
	echo "<td><input type='text' size='20' name='ifmac' value=\"".$netdev->fields["ifmac"]."\"></td>";
	echo "</tr>";
		
	echo "</table>";
	
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

		echo "<td class='tab_bg_2' valign='top'>";
		echo "<input type='hidden' name='ID' value=\"$ID\">\n";
		echo "<div align='center'><input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'></div>";
		echo "</td></form>\n\n";
		echo "<form action=\"$target\" method='post'>\n";
		echo "<td class='tab_bg_2' valign='top'>\n";
		echo "<input type='hidden' name='ID' value=\"$ID\">\n";
		echo "<div align='center'>";
		if ($netdev->fields["deleted"]=='N')
		echo "<input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'>";
		else {
		echo "<input type='submit' name='restore' value=\"".$lang["buttons"][21]."\" class='submit'>";
		
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' name='purge' value=\"".$lang["buttons"][22]."\" class='submit'>";
		}
		echo "</div>";
		echo "</td>";
	}
		echo "</form></tr>";

		echo "</table></div>";

	return true;
		}
	else {
                echo "<div align='center'><b>".$lang["networking"][38]."</b></div>";
                echo "<hr noshade>";
                searchFormNetworking();
                return false;
        }

}

function addNetdevice($input) {
	// Add Netdevice, nasty hack until we get PHP4-array-functions
	$db=new DB;
	$netdev = new Netdevice;

	// dump the status
	$oldID=$input["ID"];

	$null = array_pop($input);
	$null = array_pop($input);
 	// set new date.
 	$netdev->fields["date_mod"] = date("Y-m-d H:i:s");
	
	// fill array for update
	foreach ($input as $key => $val) {
		if (empty($netdev->fields[$key]) || $netdev->fields[$key]  != $input[$key]) {
			$netdev->fields[$key] = $input[$key];
		}
	}

	$newID=$netdev->addToDB();
	
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
	updateState(NETWORKING_TYPE,$input["ID"],$input["state"]);

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
	
	GLOBAL $cfg_layout,$cfg_install, $lang;
	
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
			echo "<br><div align='center'><table class='tab_cadre' width='90%'>";
			echo "<tr><th colspan='7'>";
			echo $db->numrows($result)." ";
			if ($db->numrows($result)<2) {
				echo $lang["networking"][37];
			} else {
				echo $lang["networking"][13];
			}
			echo ":</th></tr>";        
			echo "<tr><th>#</th><th>".$lang["networking"][0]."</th><th>".$lang["networking"][51]."</th>";
			echo "<th>".$lang["networking"][14]."</th><th>".$lang["networking"][15]."</th>";
			echo "<th>".$lang["networking"][16]."</th><th>".$lang["networking"][17].":</th></tr>\n";
			$i=0;
			while ($devid=$db->fetch_row($result)) {
				$netport = new Netport;
				$netport->getfromDB(current($devid));
				echo "<tr class='tab_bg_1'>";
				echo "<td align='center'><b>";
				if ($withtemplate!=2) echo "<a href=\"".$cfg_install["root"]."/networking/networking-port.php?ID=".$netport->fields["ID"]."&location=".$location."\">";
				echo $netport->fields["logical_number"];
				if ($withtemplate!=2) echo "</a>";
				echo "</b></td>";
        echo "<td>".$netport->fields["name"]."</td>";
		echo "<td>".getDropdownName("glpi_dropdown_netpoint",$netport->fields["netpoint"])."</td>";
				echo "<td>".$netport->fields["ifaddr"]."</td>";
				echo "<td>".$netport->fields["ifmac"]."</td>";
				echo "<td>".getDropdownName("glpi_dropdown_iface",$netport->fields["iface"])."</td>";
				echo "<td>";
					showConnection($netport->fields["ID"],$withtemplate);
				echo "</td>";
				echo "</tr>";
			}
			echo "</table></div>\n\n";
		}
	}
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
	
	echo "<table class='tab_cadre'><tr>";
	
	echo "<th colspan='4'>".$lang["networking"][20].":</th>";
	echo "</tr>";
	echo "<form method='post' action=\"$target\">";
	echo "<input type='hidden' name='referer' value='$REFERER'>";

	if ($several!="yes"){
	echo "<tr class='tab_bg_1'><td>".$lang["networking"][21].":</td>";
	echo "<td><input type='text' size='5' name='logical_number' value=\"".$netport->fields["logical_number"]."\">";
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
	
	echo "<tr class='tab_bg_1'><td>".$lang["networking"][0]."</td>";
	echo "<td><input type='text' size='20' name='name' value=\"".$netport->fields["name"]."\">";
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["networking"][16]."</td><td>";
		dropdownValue("glpi_dropdown_iface","iface", $netport->fields["iface"]);
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["networking"][22]."</td>";
	echo "<td><input type='text' size='20' name='ifaddr' value=\"".$netport->fields["ifaddr"]."\">";
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["networking"][23]."</td>";
	echo "<td><input type='text' size='25' name='ifmac' value=\"".$netport->fields["ifmac"]."\">";
	echo "</td></tr>";
	
	if ($several!="yes"){
	echo "<tr class='tab_bg_1'><td>".$lang["networking"][51].":</td>";
	
	echo "<td align='center' >";
		NetpointLocationSearch($search,"netpoint",$location,$netport->fields["netpoint"]);
        echo "<input type='text' size='10'  name='search'>";
	echo "<input type='submit' value=\"".$lang["buttons"][0]."\" name='Modif_Interne' class='submit'>";
	echo "</td></tr>";
	}
	if ($ID) {
		echo "<tr class='tab_bg_1'><td>".$lang["networking"][24]."</td>";
		echo "<td>";
			showConnection($netport->fields["ID"]);
		echo "</td></tr>";

		echo "<tr class='tab_bg_2'>";
		echo "<td align='center'>";
		echo "<input type='hidden' name='ID' value=".$netport->fields["ID"].">";
		echo "<input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'>";
		echo "</td></form>";

		echo "<form method='post' action=$target>";
		echo "<input type='hidden' name='ID' value=$ID>";
		echo "<td align='center'>";
		echo "<input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'>";
		echo "</td></tr></form>";
	} else 
	{

		echo "<tr class='tab_bg_2'>";
		echo "<td align='center' colspan='2'>";
		echo "<input type='hidden' name='on_device' value='$ondevice'>";
		echo "<input type='hidden' name='device_type' value='$devtype'>";
		echo "<input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'>";
		echo "</td></form>";
	}

	echo "</table>";	
}

function addNetport($input) {
	// Add Netport, nasty hack until we get PHP4-array-functions

	$netport = new Netport;
	
	// dump status
	unset($input['search']);
	$null = array_pop($input);
	
	// fill array for update 
	foreach ($input as $key => $val) {
		if (!isset($netport->fields[$key]) || $netport->fields[$key] != $input[$key]) {
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
	echo "<a href=\"".$cfg_install["root"]."/networking/networking-port.php?on_device=$ID&device_type=$devtype&location=$location\"><b>";
	echo $lang["networking"][19];
	echo "</b></a></td>";
	echo "<td align='center' class='tab_bg_2' width='50%'>";
	echo "<a href=\"".$cfg_install["root"]."/networking/networking-port.php?on_device=$ID&device_type=$devtype&several=yes&location=$location\"><b>";
	echo $lang["networking"][46];
	echo "</b></a></td>";

	echo "</tr>";
	echo "</table></div><br>";
}

function showConnection ($ID,$withtemplate='') {

	GLOBAL $cfg_layout, $cfg_install, $lang;

	$contact = new Netport;
	if ($contact->getContact($ID)) {
		$netport = new Netport;
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
		echo $netport->device_name." (".$netport->device_ID.")";
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
		echo "<td align='right'><b>";
		if ($withtemplate!=2&&$withtemplate!=1)
		echo "<a href=\"".$cfg_install["root"]."/networking/networking-port-connect.php?ID=$ID\">".$lang["buttons"][9]."</a>";
		else echo "&nbsp;";
		echo "</b></td>";
		echo "</tr></table>";
	}
}	


///// Wire the Ports /////


function showConnectorSearch($target,$ID) {

	GLOBAL $cfg_layout,$cfg_install, $lang;

	$np=New NetPort();
	$np->getFromDB($ID);
	$ci=new CommonItem;
	$ci->getFromDB($np->fields['device_type'],$np->fields['on_device']);
	echo "<div align='center'><form method='post' action=\"$target\"><table border='0' class='tab_cadre'>";
	echo "<tr><th colspan='2'>".$lang["networking"][27]." ".$ci->getName()." - ".$np->fields["logical_number"]." ".$lang["networking"][28].":</th></tr>";

	echo "<tr class='tab_bg_1'>";
	
	echo "<td>".$lang["networking"][29]." <select name='type'>";
	echo "<option value='name'>".$lang["networking"][0]."</option>";
	echo "<option value='id'>ID</option>";
	echo "</select>";
	echo $lang["networking"][30]." <input type='text' size='10' name='comp'>";
	echo "<input type='hidden' name='pID1' value=\"$ID\">";
	echo "<input type='hidden' name='next' value=\"compsearch\">";
	echo "</td><td class='tab_bg_2'>";
	echo "<input type='submit' value=\"".$lang["buttons"][11]."\" class='submit'>";
	echo "</td></tr>";	
	echo "</form>";
	
	echo "<tr class='tab_bg_1'>";
	echo "<form method='get' action=\"$target\">";
	echo "<td>".$lang["networking"][31].":";
	$db = new DB;
	$query = "SELECT glpi_networking.ID AS ID, glpi_networking.name AS name, glpi_dropdown_locations.ID as location from glpi_networking LEFT JOIN glpi_dropdown_locations ON glpi_networking.location = glpi_dropdown_locations.id WHERE glpi_networking.is_template='0' AND glpi_networking.deleted='N' ORDER BY name";
	$result = $db->query($query);
	$number = $db->numrows($result);
	echo "<select name='dID'>";
	$i=0;
	while ($i < $number)
	{
		$dID = $db->result($result, $i, "ID");
		$name = $db->result($result, $i, "name");
		$location = $db->result($result, $i, "location");
		echo "<option value=\"$dID\">$name (".getTreeValueName("glpi_dropdown_locations",$location).")</option>";
		$i++;
	}
	echo "</select>";
	echo "<input type='hidden' name='pID1' value=\"$ID\">";
	echo "<input type='hidden' name='next' value=\"showports\">";
	echo "<input type='hidden' name='device_type' value='2'>";
	echo "</td><td class='tab_bg_2'>";
	echo "<input type='submit' value=\"".$lang["buttons"][11]."\" class='submit'>";
	echo "</td></tr>";
	echo "</table>";
	echo "</form>";	
}

function listConnectorComputers($target,$input) {
	
	GLOBAL $cfg_layout,$cfg_install, $lang;

	$pID1 = $input["pID1"];


	$db = new DB;
	if ($input["type"] == "name") {
		$query = "SELECT glpi_computers.ID as ID, glpi_computers.name as name, glpi_dropdown_locations.ID as location from glpi_computers LEFT JOIN glpi_dropdown_locations ON  glpi_computers.location = glpi_dropdown_locations.id WHERE glpi_computers.name LIKE '%".$input["comp"]."%'";
	} else {
		$query = "SELECT glpi_computers.ID as ID, glpi_computers.name as name, glpi_dropdown_locations.ID as location from glpi_computers LEFT JOIN glpi_dropdown_locations ON glpi_computers.location = glpi_dropdown_locations.id WHERE glpi_computers.ID LIKE '%".$input["comp"]."%'";
	} 
	
	$query.=" AND glpi_computers.is_template='0' and glpi_computers.deleted='N' ";
//echo $query;
	$query.= " ORDER BY glpi_computers.name";
	$result = $db->query($query);
	$number = $db->numrows($result);
	if ($number==0){
		echo "<div align=\"center\"><strong>";
		echo $lang["computers"][32]."<br>";
		echo "<a href=\"javascript:history.back()\">".$lang["buttons"][13]."</a>";
		echo "</strong></div>";
		return;
	}

	echo "<div align='center'><form method='get' action=\"$target\"><table border='0' class='tab_cadre'>";
	echo "<tr><th colspan='2'>".$lang["networking"][27]." $pID1 ".$lang["networking"][32].". ".$lang["networking"][33].":</th></tr>";
	echo "<tr><td>";

	echo "<tr class='tab_bg_1'>";
	echo "<td align='center'>";
	
		
	echo "<select name='dID'>";
	$i=0;
	while ($i < $number)
	{
		$dID = $db->result($result, $i, "ID");
		$name = $db->result($result, $i, "name");
		$location = $db->result($result, $i, "location");
		echo "<option value=\"$dID\">$dID - $name (".getTreeValueName("glpi_dropdown_locations",$location).")</option>";
		$i++;
	}
	echo  "</select>";

	echo "</td>";
	echo "<td class='tab_bg_2' align='center'>";
	echo "<input type='hidden' name='device_type' value='1'>";
	echo "<input type='hidden' name='pID1' value=\"".$pID1."\">";
	echo "<input type='hidden' name='next' value=\"showports\">";
	echo "<input type='submit' value=\"".$lang["buttons"][11]."\">";
	echo "</td></tr></table></form>";	

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

		echo "<div align='center'><table border='0' cellspacing=2 width='90%' class='tab_cadre'>";
		echo "<tr><th>".$lang["networking"][27]." $pID1 ".$lang["networking"][35].". ".$lang["networking"][36]." ".$input["dID"].":</th></tr>";
		echo "</table></div>";

		echo "\n\n<br><div align='center'><table border='0' cellpadding='2' width='90%' class='tab_cadre'>";
		echo "<tr><th>#</th><th>".$lang["networking"][0]."</th><th>".$lang["networking"][51]."</th>";
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
			echo "<td>".getDropdownName("glpi_dropdown_netpoint",$data["netpoint"])."</td>";			
			echo "<td>".$data["ifaddr"]."</td>";
			echo "<td>".$data["ifmac"]."</td>";
			echo "<td>".getDropdownName("glpi_dropdown_iface",$data["iface"])."</td>";
			echo "<td>";

			if ($contact->getContact($pID2)) {
				$netport = new Netport;
				$netport->getfromDB($contact->contact_id);
				$netport->getDeviceData($netport->fields["on_device"],$netport->fields["device_type"]);
				echo "\n\n<table border='0' cellspacing='0' width='100%'><tr>";
				echo "<td>";
				echo "<a href=\"".$cfg_install["root"]."/networking/networking-port.php?ID=".$netport->fields["ID"]."\">";
				echo $netport->fields["name"];
				echo "</a> ";
				echo $lang["networking"][25];
				echo " <a href=\"".$cfg_install["root"]."/computers/computers-info-form.php?ID=".$netport->device_ID."\">";
				echo $netport->device_name." (".$netport->device_ID.")";
				echo "</a>";
				echo "</td>";
				echo "<td align='right'><b>";
				echo "<a href=\"".$cfg_install["root"]."/networking/networking-port-disconnect.php?ID=".$netport->fields["ID"];
				if (!empty($pID1)) echo "&sport=$pID1";
				echo "\">".$lang["buttons"][10]."</a>";
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
	$npup=-1;
	if ($np1->fields["device_type"]!=NETWORKING_TYPE&&$np2->fields["device_type"]==NETWORKING_TYPE){
		$npup=$ID;
		}
	if ($np2->fields["device_type"]!=NETWORKING_TYPE&&$np1->fields["device_type"]==NETWORKING_TYPE){
		$npup=$ID2;
		}
	$db = new DB;
	if ($npup!=-1){
		$query = "UPDATE glpi_networking_ports SET netpoint=NULL, ifaddr='', ifmac='' WHERE ID='$npup'";	
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
