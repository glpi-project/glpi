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
// FUNCTIONS Monitors


function titleMonitors(){
                GLOBAL  $lang,$HTMLRel;
                echo "<div align='center'><table border='0'><tr><td>";
                echo "<img src=\"".$HTMLRel."pics/ecran.png\" alt='".$lang["monitors"][0]."' title='".$lang["monitors"][0]."'></td><td><a  class='icon_consol' href=\"monitors-add-select.php\"><b>".$lang["monitors"][0]."</b></a>";
                echo "</td>";
                echo "<td><a class='icon_consol' href='".$HTMLRel."setup/setup-templates.php?type=".MONITOR_TYPE."'>".$lang["common"][8]."</a></td>";
                echo "</tr></table></div>";
}

function showMonitorOnglets($target,$withtemplate,$actif){
	global $lang;
	
	echo "<div id='barre_onglets'><ul id='onglet'>";
	echo "<li "; if ($actif=="1"){ echo "class='actif'";} echo  "><a href='$target&onglet=1'>".$lang["title"][26]."</a></li>";
	echo "<li "; if ($actif=="4") {echo "class='actif'";} echo "><a href='$target&onglet=4'>".$lang["Menu"][26]."</a></li>";
	echo "<li "; if ($actif=="5") {echo "class='actif'";} echo "><a href='$target&onglet=5'>".$lang["title"][25]."</a></li>";
	if(empty($withtemplate)){
	echo "<li "; if ($actif=="6") {echo "class='actif'";} echo "><a href='$target&onglet=6'>".$lang["title"][28]."</a></li>";
	}
	
	echo "<li class='invisible'>&nbsp;</li>";
	
	if (empty($withtemplate)&&preg_match("/\?ID=([0-9]+)/",$target,$ereg)){
	$ID=$ereg[1];
	$next=getNextItem("glpi_monitors",$ID);
	$prev=getPreviousItem("glpi_monitors",$ID);
	$cleantarget=preg_replace("/\?ID=([0-9]+)/","",$target);
	if ($prev>0) echo "<li><a href='$cleantarget?ID=$prev'>".$lang["buttons"][12]."</a></li>";
	if ($next>0) echo "<li><a href='$cleantarget?ID=$next'>".$lang["buttons"][11]."</a></li>";
	}

	echo "</ul></div>";
	
}

function searchFormMonitors($field="",$phrasetype= "",$contains="",$sort= "",$deleted="") {
	// Print Search Form
	
	GLOBAL $cfg_install, $cfg_layout, $layout, $lang,$HTMLRel;

	$option["mon.name"]			= $lang["monitors"][5];
	$option["mon.ID"]			= $lang["monitors"][23];
	$option["glpi_dropdown_locations.name"]	= $lang["monitors"][6];
	$option["glpi_type_monitors.name"]	= $lang["monitors"][9];
	$option["mon.serial"]			= $lang["monitors"][10];
	$option["mon.otherserial"]		= $lang["monitors"][11]	;
	$option["mon.comments"]			= $lang["monitors"][12];
	$option["mon.contact"]			= $lang["monitors"][8];
	$option["mon.contact_num"]		= $lang["monitors"][7];
	$option["mon.date_mod"]			= $lang["monitors"][16];
	$option["glpi_enterprises.name"]			= $lang["common"][5];
	$option["resptech.name"]			=$lang["common"][10];

	echo "<form method='get' action=\"".$cfg_install["root"]."/monitors/monitors-search.php\">";
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
	echo "</select> ";
	echo "</td><td><input type='checkbox' name='deleted' ".($deleted=='Y'?" checked ":"").">";
	echo "<img src=\"".$HTMLRel."pics/showdeleted.png\" alt='".$lang["common"][3]."' title='".$lang["common"][3]."'>";
	echo "</td><td width='80' align='center' class='tab_bg_2'>";
	echo "<input type='submit' value=\"".$lang["buttons"][0]."\" class='submit'>";
	echo "</td></tr></table></div></form>";
}


function showMonitorList($target,$username,$field,$phrasetype,$contains,$sort,$order,$start,$deleted) {

	// Lists Monitors

	GLOBAL $cfg_install, $cfg_layout, $cfg_features, $lang, $HTMLRel;

	$db = new DB;

	// Build query
	if($field=="all") {
		$where = " (";
		$fields = $db->list_fields("glpi_monitors");
		$columns = $db->num_fields($fields);
		
		for ($i = 0; $i < $columns; $i++) {
			if($i != 0) {
				$where .= " OR ";
			}
			$coco = mysql_field_name($fields, $i);

			if($coco == "location") {
				$where .= getRealSearchForTreeItem("glpi_dropdown_locations",$contains);
			}
			elseif($coco == "type") {
				$where .= " glpi_type_monitors.name LIKE '%".$contains."%'";
			}
			elseif($coco == "FK_glpi_enterprise") {
				$where .= "glpi_enterprises.name LIKE '%".$contains."%'";
			}
			else if ($coco=="tech_num"){
				$where .= " resptech.name LIKE '%".$contains."%'";
			} 
			else {
   				$where .= "mon.".$coco . " LIKE '%".$contains."%'";
			}
		}
		
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
	$query = "select mon.ID from glpi_monitors as mon LEFT JOIN glpi_dropdown_locations on mon.location=glpi_dropdown_locations.ID ";
	$query .= "LEFT JOIN glpi_type_monitors on mon.type = glpi_type_monitors.ID ";
	$query.= " LEFT JOIN glpi_enterprises ON (glpi_enterprises.ID = mon.FK_glpi_enterprise ) ";
	$query.= " LEFT JOIN glpi_users as resptech ON (resptech.ID = mon.tech_num ) ";
	$query .= " where $where AND mon.deleted='$deleted' AND mon.is_template = '0' ORDER BY $sort $order";
	//echo $query;
	// Get it from database	
	if ($result = $db->query($query)) {
		$numrows= $db->numrows($result);

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
			echo "<center><table  class='tab_cadre'><tr>";

			// Name
			echo "<th>";
			if ($sort=="mon.name") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=mon.name&order=ASC&start=$start\">";
			echo $lang["monitors"][5]."</a></th>";

			// Manufacturer		
			echo "<th>";
			if ($sort=="glpi_enterprises.name") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_enterprises.name&order=ASC&start=$start\">";
			echo $lang["common"][5]."</a></th>";
			
			// Location			
			echo "<th>";
			if ($sort=="glpi_dropdown_locations.name") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_dropdown_locations.name&order=ASC&start=$start\">";
			echo $lang["monitors"][6]."</a></th>";

			// Type
			echo "<th>";
			if ($sort=="glpi_type_monitors.name") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_type_monitors.name&order=ASC&start=$start\">";
			echo $lang["monitors"][9]."</a></th>";

			// Last modified		
			echo "<th>";
			if ($sort=="mon.date_mod") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=mon.date_mod&order=DESC&start=$start\">";
			echo $lang["monitors"][16]."</a></th>";

			// Contact person
			echo "<th>";
			if ($sort=="mon.contact") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=mon.contact&order=ASC&start=$start\">";
			echo $lang["monitors"][8]."</a></th>";

			echo "</tr>";

			for ($i=0; $i < $numrows_limit; $i++) {
				$ID = $db->result($result_limit, $i, "mon.ID");
				$mon = new Monitor;
				$mon->getfromDB($ID);
				echo "<tr class='tab_bg_2'>";
				echo "<td><b>";
				echo "<a href=\"".$cfg_install["root"]."/monitors/monitors-info-form.php?ID=$ID\">";
				echo $mon->fields["name"]." (".$mon->fields["ID"].")";
				echo "</a></b></td>";
				echo "<td>". getDropdownName("glpi_enterprises",$mon->fields["FK_glpi_enterprise"]) ."</td>";
				echo "<td>". getDropdownName("glpi_dropdown_locations",$mon->fields["location"]) ."</td>";
				echo "<td>". getDropdownName("glpi_type_monitors",$mon->fields["type"]) ."</td>";
				echo "<td>".$mon->fields["date_mod"]."</td>";
				echo "<td>".$mon->fields["contact"]."</td>";
				echo "</tr>";
			}

			// Close Table
			echo "</table></center>";

			// Pager
			$parameters="field=$field&phrasetype=$phrasetype&contains=$contains&sort=$sort&order=$order";
			printPager($start,$numrows,$target,$parameters);

		} else {
			echo "<center><b>".$lang["monitors"][17]."</b></center>";
			echo "<hr noshade>";
		}
	}
}


function showMonitorsForm ($target,$ID,$withtemplate='') {

	GLOBAL $cfg_install, $cfg_layout, $lang,$HTMLRel;

	$mon = new Monitor;
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
			$date = date("Y-m-d H:i:s");
		} elseif(!empty($withtemplate) && $withtemplate == 1) { 
			$template = "newtemplate";
			$datestring = $lang["computers"][14].": ";
			$date = date("Y-m-d H:i:s");
		} else {
			$datestring = $lang["computers"][11]." : ";
			$date = $mon->fields["date_mod"];
			$template = false;
		}

	echo "<div align='center'><form method='post' name=form action=\"$target\">";
		if(strcmp($template,"newtemplate") === 0) {
			echo "<input type=\"hidden\" name=\"is_template\" value=\"1\" />";
		}
	
	echo "<table width='700' class='tab_cadre' cellpadding='2'>";

		echo "<tr><th align='center' >";
		if(!$template) {
			echo $lang["monitors"][29].": ".$mon->fields["ID"];
		}elseif (strcmp($template,"newcomp") === 0) {
			echo $lang["monitors"][30].": ".$mon->fields["tplname"];
		}elseif (strcmp($template,"newtemplate") === 0) {
			echo $lang["common"][6]."&nbsp;: <input type='text' name='tplname' value=\"".$mon->fields["tplname"]."\" size='20'>";
		}
		
		echo "</th><th  align='center'>".$datestring.$date;
		echo "</th></tr>";

	
	echo "<tr><td class='tab_bg_1' valign='top'>";

	echo "<table cellpadding='1px' cellspacing='0' border='0'>\n";

	echo "<tr><td>".$lang["monitors"][5].":	</td>";
	echo "<td><input type='text' name='name' value=\"".$mon->fields["name"]."\" size='20'></td>";
	echo "</tr>";

	echo "<tr><td>".$lang["monitors"][6].": 	</td><td>";
		dropdownValue("glpi_dropdown_locations", "location", $mon->fields["location"]);
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["common"][10].": 	</td><td colspan='2'>";
		dropdownUsersID( $mon->fields["tech_num"],"tech_num");
	echo "</td></tr>";
		
	echo "<tr class='tab_bg_1'><td>".$lang["common"][5].": 	</td><td colspan='2'>";
		dropdownValue("glpi_enterprises","FK_glpi_enterprise",$mon->fields["FK_glpi_enterprise"]);
	echo "</td></tr>";

	echo "<tr><td>".$lang["monitors"][7].":	</td>";
	echo "<td><input type='text' name='contact_num' value=\"".$mon->fields["contact_num"]."\" size='20'></td>";
	echo "</tr>";

	echo "<tr><td>".$lang["monitors"][8].":	</td>";
	echo "<td><input type='text' name='contact' size='20' value=\"".$mon->fields["contact"]."\"></td>";
	echo "</tr>";
	if (!$template){
		echo "<tr><td>".$lang["reservation"][24].":</td><td><b>";
		showReservationForm(MONITOR_TYPE,$ID);
		echo "</b></td></tr>";
	}
	if (!$template){
		echo "<tr><td>".$lang["repair"][0].":</td><td><b>";
		showRepairForm(MONITOR_TYPE,$ID);
		echo "</b></td></tr>";
	}

	echo "</table>";

	echo "</td>\n";	
	echo "<td class='tab_bg_1' valign='top'>";

	echo "<table cellpadding='1px' cellspacing='0' border='0'";

	echo "<tr><td>".$lang["monitors"][9].": 	</td><td>";
		dropdownValue("glpi_type_monitors", "type", $mon->fields["type"]);
	echo "</td></tr>";
		
	echo "<tr><td>".$lang["monitors"][10].":	</td>";
	echo "<td><input type='text' name='serial' size='20' value=\"".$mon->fields["serial"]."\"></td>";
	echo "</tr>";

	echo "<tr><td>".$lang["monitors"][11].":</td>";
	echo "<td><input type='text' size='20' name='otherserial' value=\"".$mon->fields["otherserial"]."\"></td>";
	echo "</tr>";

	echo "<tr><td>".$lang["monitors"][21].":</td>";
	echo "<td><input type='text' size='2' name='size' value=\"".$mon->fields["size"]."\">\"</td>";
	echo "</tr>";

		echo "<tr><td>".$lang["monitors"][18].": </td><td>";

		// micro?
		echo "<table border='0' cellpadding='2' cellspacing='0'><tr>";
		echo "<td>";
		if ($mon->fields["flags_micro"] == 1) {
			echo "<input type='checkbox' name='flags_micro' value='1' checked>";
		} else {
			echo "<input type='checkbox' name='flags_micro' value='1''>";
		}
		echo "</td><td>".$lang["monitors"][14]."</td>";
		echo "</tr></table>";

		// speakers?
		echo "<table border='0' cellpadding='2' cellspacing='0'><tr>";
		echo "<td>";
		if ($mon->fields["flags_speaker"] == 1) {
			echo "<input type='checkbox' name='flags_speaker' value='1' checked>";
		} else {
			echo "<input type='checkbox' name='flags_speaker' value='1'>";
		}
		echo "</td><td>".$lang["monitors"][15]."</td>";
		echo "</tr></table>";

		// sub-d?
		echo "<table border='0' cellpadding='2' cellspacing='0'><tr>";
		echo "<td>";
		if ($mon->fields["flags_subd"] == 1) {
			echo "<input type='checkbox' name='flags_subd' value='1' checked>";
		} else {
			echo "<input type='checkbox' name='flags_subd' value='1'>";
		}
		echo "</td><td>".$lang["monitors"][19]."</td>";
		echo "</tr></table>";

		// bnc?
		echo "<table border='0' cellpadding='2' cellspacing='0'><tr>";
		echo "<td>";
		if ($mon->fields["flags_bnc"] == 1) {
			echo "<input type='checkbox' name='flags_bnc' value='1' checked>";
		} else {
			echo "<input type='checkbox' name='flags_bnc' value='1'>";
		}
		echo "</td><td>".$lang["monitors"][20]."</td>";
		echo "</tr></table>";


echo "</td></tr>";

	echo "</table>";
	echo "</td>\n";	
	echo "</tr>";
	echo "<tr>";
	echo "<td class='tab_bg_1' valign='top' colspan='2'>";

	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'><tr><td valign='top'>";
	echo $lang["monitors"][12].":	</td>";
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

		echo "<td class='tab_bg_2' valign='top'>";
		echo "<input type='hidden' name='ID' value=\"$ID\">\n";
		echo "<center><input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit' class='submit'></center>";
		echo "</td></form>\n\n";
		echo "<form action=\"$target\" method='post'>\n";
		echo "<td class='tab_bg_2' valign='top'>\n";
		echo "<input type='hidden' name='ID' value=\"$ID\">\n";
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
		echo "</form></tr>";

		echo "</table></div>";
	
	return true;
		}
	else {
                echo "<div align='center'><b>".$lang["monitors"][17]."</b></div>";
                echo "<hr noshade>";
                searchFormMonitors();
                return false;
        }

	
	
}


function updateMonitor($input) {
	// Update a monitor in the database

	$mon = new Monitor;
	$mon->getFromDB($input["ID"]);

	// set new date and make sure it gets updated
	$updates[0]= "date_mod";
	$mon->fields["date_mod"] = date("Y-m-d H:i:s");

	// Pop off the last two attributes, no longer needed
	$null=array_pop($input);
	$null=array_pop($input);
	
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
		if ($mon->fields[$key] != $input[$key]) {
			$mon->fields[$key] = $input[$key];
			$updates[$x] = $key;
			$x++;
		}
	}

	$mon->updateInDB($updates);

}

function addMonitor($input) {
	// Add Monitor, nasty hack until we get PHP4-array-functions
	$db=new DB;

	$mon = new Monitor;

	// dump status
	$oldID=$input["ID"];

	$null = array_pop($input);
	$null = array_pop($input);
	
 	// set new date.
 	$mon->fields["date_mod"] = date("Y-m-d H:i:s");

	// fill array for udpate
	foreach ($input as $key => $val) {
		if (!isset($mon->fields[$key]) || $mon->fields[$key] != $input[$key]) {
			$mon->fields[$key] = $input[$key];
		}
	}

	$newID=$mon->addToDB();
	
	// ADD Infocoms
	$ic= new Infocom();
	if ($ic->getFromDB(MONITOR_TYPE,$oldID)){
		$ic->fields["FK_device"]=$newID;
		unset ($ic->fields["ID"]);
		$ic->addToDB();
	}

	// ADD Contract				
	$query="SELECT FK_contract from glpi_contract_device WHERE FK_device='$oldID' AND device_type='".MONITOR_TYPE."';";
	$result=$db->query($query);
	if ($db->numrows($result)>0){
		
		while ($data=$db->fetch_array($result))
			addDeviceContract($data["FK_contract"],MONITOR_TYPE,$newID);
	}
	
	// ADD Documents			
	$query="SELECT FK_doc from glpi_doc_device WHERE FK_device='$oldID' AND device_type='".MONITOR_TYPE."';";
	$result=$db->query($query);
	if ($db->numrows($result)>0){
		
		while ($data=$db->fetch_array($result))
			addDeviceDocument($data["FK_doc"],MONITOR_TYPE,$newID);
	}

	
	

}

function deleteMonitor($input,$force=0) {
	// Delete Printer
	
	$mon = new Monitor;
	$mon->deleteFromDB($input["ID"],$force);
	
} 	

function restoreMonitor($input) {
	// Restore Monitor
	
	$mon = new Monitor;
	$mon->restoreInDB($input["ID"]);
} 

?>
