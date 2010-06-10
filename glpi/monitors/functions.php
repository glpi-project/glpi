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
// FUNCTIONS Monitors

function searchFormMonitors() {
	// Print Search Form
	
	GLOBAL $cfg_install, $cfg_layout, $layout, $lang;

	$option["name"]				= $lang["monitors"][5];
	$option["ID"]				= $lang["monitors"][23];
	$option["location"]			= $lang["monitors"][6];
	$option["type"]				= $lang["monitors"][9];
	$option["serial"]			= $lang["monitors"][10];
	$option["otherserial"]		= $lang["monitors"][11]	;
	$option["comments"]			= $lang["monitors"][12];
	$option["contact"]			= $lang["monitors"][8];
	$option["contact_num"]		= $lang["monitors"][7];
	$option["date_mod"]			= $lang["monitors"][16];
	
	echo "<form method=get action=\"".$cfg_install["root"]."/monitors/monitors-search.php\">";
	echo "<center><table border=0 width=90%>";
	echo "<tr><th colspan=2><b>".$lang["search"][5].":</b></th></tr>";
	echo "<tr bgcolor=".$cfg_layout["tab_bg_1"].">";
	echo "<td align=center>";
		dropdown( "dropdown_locations",  "contains");
	echo "<input type=hidden name=field value=location>&nbsp;";
	echo $lang["search"][6];
	echo "&nbsp;<select name=sort size=1>";
	reset($option);
	for ($i=0; $i < count($option); $i++) {
		list($key,$val) = each ($option);
		echo "<option value=$key>$val\n";
	}
	echo "</select>";
	echo "<input type=hidden name=phrasetype value=exact>";
	echo "</td><td width=80 align=center bgcolor=".$cfg_layout["tab_bg_2"].">";
	echo "<input type=submit value=\"".$lang["buttons"][1]."\">";
	echo "</td></tr></table></form></center>";

	echo "<form method=get action=\"".$cfg_install["root"]."/monitors/monitors-search.php\">";
	echo "<center><table border=0 width=90%>";
	echo "<tr><th colspan=2><b>".$lang["search"][0].":</b></th></tr>";
	echo "<tr bgcolor=".$cfg_layout["tab_bg_1"].">";
	echo "<td align=center>";
	echo "<select name=\"field\" size=1>";
	reset($option);
	for ($i=0; $i < count($option); $i++) {
		list($key,$val) = each ($option);
		echo "<option value=$key>$val\n";
	}
	echo "</select>&nbsp;";
	echo $lang["search"][1];
	echo "&nbsp;<select name=phrasetype>";
	echo "<option value=contains>".$lang["search"][2]."</option>";
	echo "<option value=exact>".$lang["search"][3]."</option>";
	echo "</select>";
	echo "<input type=text size=5 name=\"contains\">"; 
	echo "&nbsp;";
	echo $lang["search"][4];
	echo "&nbsp;<select name=sort size=1>";
	reset($option);
	for ($i=0; $i < count($option); $i++) {
		list($key,$val) = each ($option);
		echo "<option value=$key>$val\n";
	}
	echo "</select> ";
	echo "</td><td width=80 align=center bgcolor=".$cfg_layout["tab_bg_2"].">";
	echo "<input type=submit value=\"".$lang["buttons"][0]."\">";
	echo "</td></tr></table></center></form>";
}


function showMonitorList($target,$username,$field,$phrasetype,$contains,$sort,$order,$start) {

	// Lists Monitors

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
	$query = "SELECT * FROM monitors WHERE $where ORDER BY $sort $order";

	// Get it from database	
	$db = new DB;
	if ($result = $db->query($query)) {
		$numrows= $db->numrows($result);

		// Limit the result, if no limit applies, use prior result
		if ($numrows>$cfg_features["list_limit"]) {
			$query_limit = "SELECT * FROM monitors WHERE $where ORDER BY $sort $order LIMIT $start,".$cfg_features["list_limit"]." ";
			$result_limit = $db->query($query_limit);
			$numrows_limit = $db->numrows($result_limit);
		} else {
			$numrows_limit = $numrows;
			$result_limit = $result;
		}
		

		if ($numrows_limit>0) {
			// Produce headline
			echo "<center><table border=0><tr>";

			// Name
			echo "<th>";
			if ($sort=="name") {
				echo "&middot;&nbsp;";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=name&order=ASC&start=$start\">";
			echo $lang["monitors"][5]."</th>";

			// Location			
			echo "<th>";
			if ($sort=="location") {
				echo "&middot;&nbsp;";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=location&order=ASC&start=$start\">";
			echo $lang["monitors"][6]."</th>";

			// Type
			echo "<th>";
			if ($sort=="type") {
				echo "&middot;&nbsp;";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=type&order=ASC&start=$start\">";
			echo $lang["monitors"][9]."</th>";

			// Last modified		
			echo "<th>";
			if ($sort=="date_mod") {
				echo "&middot;&nbsp;";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=date_mod&order=DESC&start=$start\">";
			echo $lang["monitors"][16]."</th>";

			// Contact person
			echo "<th>";
			if ($sort=="contact") {
				echo "&middot;&nbsp;";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=contact&order=ASC&start=$start\">";
			echo $lang["monitors"][8]."</th>";

			echo "</tr>";

			for ($i=0; $i < $numrows_limit; $i++) {
				$ID = $db->result($result_limit, $i, "ID");
				$mon = new Monitor;
				$mon->getfromDB($ID);
				echo "<tr bgcolor=\"".$cfg_layout["tab_bg_2"]."\">";
				echo "<td><b>";
				echo "<a href=\"".$cfg_install["root"]."/monitors/monitors-info-form.php?ID=$ID\">";
				echo $mon->fields["name"]." (".$mon->fields["ID"].")";
				echo "</b></a></td>";
				echo "<td>".$mon->fields["location"]."</td>";
				echo "<td>".$mon->fields["type"]."</td>";
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
			searchFormMonitors();
		}
	}
}


function showMonitorsForm ($target,$ID) {

	GLOBAL $cfg_install, $cfg_layout, $lang;

	$mon = new Monitor;

	echo "<center><form method=post name=form action=\"$target\">";
	echo "<table border=0 cellpadding=2>";
	echo "<tr><th colspan=2><b>";
	if ($ID=="") {
		echo $lang["monitors"][3].":";
	} else {
		$mon->getfromDB($ID);
		echo $lang["monitors"][4]." ID $ID:";
	}		
	echo "</b></th></tr>";
	
	echo "<tr><td bgcolor=".$cfg_layout["tab_bg_1"]." valign=top>";

	echo "<table cellpadding=0 cellspacing=0 border=0>\n";

	echo "<tr><td>".$lang["monitors"][5].":	</td>";
	echo "<td><input type=text name=name value=\"".$mon->fields["name"]."\" size=10></td>";
	echo "</tr>";

	echo "<tr><td>".$lang["monitors"][6].": 	</td><td>";
		dropdownValue("dropdown_locations", "location", $mon->fields["location"]);
	echo "</td></tr>";

	echo "<tr><td>".$lang["monitors"][7].":	</td>";
	echo "<td><input type=text name=contact_num value=\"".$mon->fields["contact_num"]."\" size=5></td>";
	echo "</tr>";

	echo "<tr><td>".$lang["monitors"][8].":	</td>";
	echo "<td><input type=text name=contact size=12 value=\"".$mon->fields["contact"]."\"></td>";
	echo "</tr>";

	echo "</table>";

	echo "</td>\n";	
	echo "<td bgcolor=".$cfg_layout["tab_bg_1"]." valign=top>";

	echo "<table cellpadding=0 cellspacing=0 border=0";

	echo "<tr><td>".$lang["monitors"][9].": 	</td><td>";
		dropdownValue("type_monitors", "type", $mon->fields["type"]);
	echo "</td></tr>";
		
	echo "<tr><td>".$lang["monitors"][10].":	</td>";
	echo "<td><input type=text name=serial size=12 value=\"".$mon->fields["serial"]."\"></td>";
	echo "</tr>";

	echo "<tr><td>".$lang["monitors"][11].":</td>";
	echo "<td><input type=text size=12 name=otherserial value=\"".$mon->fields["otherserial"]."\"></td>";
	echo "</tr>";

	echo "<tr><td>".$lang["monitors"][21].":</td>";
	echo "<td><input type=text size=2 name=size value=\"".$mon->fields["size"]."\">\"</td>";
	echo "</tr>";

		echo "<tr><td>".$lang["monitors"][18].": </td><td>";

		// micro?
		echo "<table border=0 cellpadding=2 cellspacing=0><tr>";
		echo "<td>";
		if ($mon->fields["flags_micro"] == 1) {
			echo "<input type=checkbox name=flags_micro value=1 checked>";
		} else {
			echo "<input type=checkbox name=flags_micro value=1>";
		}
		echo "</td><td>".$lang["monitors"][14]."</td>";
		echo "</tr></table>";

		// speakers?
		echo "<table border=0 cellpadding=2 cellspacing=0><tr>";
		echo "<td>";
		if ($mon->fields["flags_speaker"] == 1) {
			echo "<input type=checkbox name=flags_speaker value=1 checked>";
		} else {
			echo "<input type=checkbox name=flags_speaker value=1>";
		}
		echo "</td><td>".$lang["monitors"][15]."</td>";
		echo "</tr></table>";

		// sub-d?
		echo "<table border=0 cellpadding=2 cellspacing=0><tr>";
		echo "<td>";
		if ($mon->fields["flags_subd"] == 1) {
			echo "<input type=checkbox name=flags_subd value=1 checked>";
		} else {
			echo "<input type=checkbox name=flags_subd value=1>";
		}
		echo "</td><td>".$lang["monitors"][19]."</td>";
		echo "</tr></table>";

		// bnc?
		echo "<table border=0 cellpadding=2 cellspacing=0><tr>";
		echo "<td>";
		if ($mon->fields["flags_bnc"] == 1) {
			echo "<input type=checkbox name=flags_bnc value=1 checked>";
		} else {
			echo "<input type=checkbox name=flags_bnc value=1>";
		}
		echo "</td><td>".$lang["monitors"][20]."</td>";
		echo "</tr></table>";


		echo "<tr><td>".$lang["computers"][41].":	</td>";
		echo "<td><input type=text name='achat_date' readonly size=10 value=\"0000-00-00\">";
		echo "&nbsp; <input name='button' type='button'  onClick=\"window.open('mycalendar.php?form=form&elem=achat_date','Calendrier','width=200,height=220')\" value='".$lang["buttons"][15]."...'>";
		echo "&nbsp; <input name='button_reset' type='button' onClick=\"document.forms['form'].achat_date.value='0000-00-00'\" value='reset'>";
    echo "</td></tr>";
		
		echo "<tr><td>".$lang["computers"][42].":	</td>";
		echo "<td><input type=text name='date_fin_garantie' readonly size=10 value=\"0000-00-00\">";
		echo "&nbsp; <input name='button' type='button' onClick=\"window.open('mycalendar.php?form=form&elem=date_fin_garantie','Calendrier','width=200,height=220')\" value='".$lang["buttons"][15]."...'>";
		echo "&nbsp; <input name='button_reset' type='button' onClick=\"document.forms['form'].date_fin_garantie.value='0000-00-00'\" value='reset'>";
    echo "</td></tr>";
		
		echo "<tr><td>".$lang["computers"][43].":	</td>";
		echo "<td>";
		if ($mon->fields["maintenance"] == 1) {
			echo " OUI <input type=radio name='maintenance' value=1 checked>";
			echo "&nbsp; &nbsp; NON <input type=radio name='maintenance' value=0>";
		} else {
			echo " OUI <input type=radio name='maintenance' value=1>";
			echo "&nbsp; &nbsp; NON <input type=radio name='maintenance' value=0 checked >";
		}
		echo "</td></tr>";

echo "</td></tr>";

	echo "</table>";
	echo "</td>\n";	
	echo "</tr>";
	echo "<tr>";
	echo "<td bgcolor=".$cfg_layout["tab_bg_1"]." valign=top colspan=2>";

	echo "<table width=100% cellpadding=0 cellspacing=0 border=0><tr><td valign=top>";
	echo $lang["monitors"][12].":	</td>";
	echo "<td align=center><textarea cols=35 rows=4 name=comments wrap=soft>".$mon->fields["comments"]."</textarea>";
	echo "</td></tr></table>";

	echo "</td>";
	echo "</tr>";
	
	if ($ID=="") {

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

		showConnect($target,$ID,4);
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
	for ($i=0; $i < count($mon->fields); $i++) {
		list($key,$val) = each($mon->fields);
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

	$mon = new Monitor;

	// dump status
	$null = array_pop($input);
	
	// fill array for udpate
	for ($i=0; $i < count($input); $i++) {
		list($key,$val) = each($input);
		if ($mon->fields[$key] != $input[$key]) {
			$mon->fields[$key] = $input[$key];
		}
	}

	$mon->addToDB();

}

function deleteMonitor($input) {
	// Delete Printer
	
	$mon = new Monitor;
	$mon->deleteFromDB($input["ID"]);
	
} 	


?>
