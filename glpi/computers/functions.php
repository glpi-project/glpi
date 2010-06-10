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
// FUNCTIONS Computers

function searchFormComputers() {
	// Print Search Form
	
	GLOBAL $cfg_install, $cfg_layout, $layout, $lang;

	
	$option["ID"]				= $lang["computers"][31];
	$option["name"]				= $lang["computers"][7];
	$option["location"]			= $lang["computers"][10];
	$option["type"]				= $lang["computers"][8];
	$option["os"]				= $lang["computers"][9];
	$option["osver"]			= $lang["computers"][20];
	$option["processor"]		= $lang["computers"][21];
	$option["processor_speed"]	= $lang["computers"][22];
	$option["serial"]			= $lang["computers"][17];
	$option["otherserial"]		= $lang["computers"][18];
	$option["ramtype"]			= $lang["computers"][23];
	$option["ram"]				= $lang["computers"][24];
	$option["network"]			= $lang["computers"][26];
	$option["hdspace"]			= $lang["computers"][25];
	$option["sndcard"]			= $lang["computers"][33];
	$option["gfxcard"]			= $lang["computers"][34];
	$option["moboard"]			= $lang["computers"][35];
	$option["hdtype"]			= $lang["computers"][36];
	$option["comments"]			= $lang["computers"][19];
	$option["contact"]			= $lang["computers"][16];
	$option["contact_num"]		= $lang["computers"][15];
	$option["date_mod"]			= $lang["computers"][11];
	
	echo "<form method=get action=\"".$cfg_install["root"]."/computers/computers-search.php\">";
	echo "<center><table border=0 width=90%>";
	echo "<tr><th colspan=2><b>".$lang["search"][5].":</b></th></tr>";
	echo "<tr bgcolor=".$cfg_layout["tab_bg_1"].">";
	echo "<td align=center>";
		dropdown( "dropdown_locations",  "contains");
	echo "<input type=hidden name=field value=location>&nbsp;";
	echo $lang["search"][6];
	echo "&nbsp;<select name='sort' size='1'>";
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
	
	echo "<form method=get action=\"".$cfg_install["root"]."/computers/computers-search.php\">";
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


function showComputerList($target,$username,$field,$phrasetype,$contains,$sort,$order,$start) {

	// Lists Computers

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
	$query = "SELECT * FROM computers WHERE $where ORDER BY $sort $order";

	// Get it from database	
	$db = new DB;
	if ($result = $db->query($query)) {
		$numrows= $db->numrows($result);

		// Limit the result, if no limit applies, use prior result
		if ($numrows>$cfg_features["list_limit"]) {
			$query_limit = "SELECT * FROM computers WHERE $where ORDER BY $sort $order LIMIT $start,".$cfg_features["list_limit"]." ";
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
			echo $lang["computers"][7]."</th>";
		
			// Type
			echo "<th>";
			if ($sort=="type") {
				echo "&middot;&nbsp;";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=type&order=ASC&start=$start\">";
			echo $lang["computers"][8]."</a></th>";

			// OS
			echo "<th>";
			if ($sort=="os") {
				echo "&middot;&nbsp;";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=os&order=ASC&start=$start\">";
			echo $lang["computers"][9]."</th>";

			// Location			
			echo "<th>";
			if ($sort=="location") {
				echo "&middot;&nbsp;";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=location&order=ASC&start=$start\">";
			echo $lang["computers"][10]."</th>";

			// Last modified		
			echo "<th>";
			if ($sort=="date_mod") {
				echo "&middot;&nbsp;";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=date_mod&order=DESC&start=$start\">";
			echo $lang["computers"][11]."</th>";

			// Contact person
			echo "<th>";
			if ($sort=="contact") {
				echo "&middot;&nbsp;";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=contact&order=ASC&start=$start\">";
			echo $lang["computers"][16]."</th>";

			echo "</tr>";

			for ($i=0; $i < $numrows_limit; $i++) {
				$ID = $db->result($result_limit, $i, "ID");
				$comp = new Computer;
				$comp->getfromDB($ID,0);
				echo "<tr bgcolor=\"".$cfg_layout["tab_bg_2"]."\">";
				echo "<td><b>";
				echo "<a href=\"".$cfg_install["root"]."/computers/computers-info-form.php?ID=$ID\">";
				echo $comp->fields["name"]." (".$comp->fields["ID"].")";
				echo "</b></a></td>";
				echo "<td>".$comp->fields["type"]."</td>";
				echo "<td>".$comp->fields["os"]."</td>";
				echo "<td>".$comp->fields["location"]."</td>";
				echo "<td>".$comp->fields["date_mod"]."</td>";
				echo "<td>".$comp->fields["contact"]."</td>";
				echo "</tr>";
			}

			// Close Table
			echo "</table></center>";

			// Pager
			$parameters="field=$field&phrasetype=$phrasetype&contains=$contains&sort=$sort";
			printPager($start,$numrows,$target,$parameters);

		} else {
			echo "<center><b>".$lang["computers"][32]."</b></center>";
			echo "<hr noshade>";
			searchFormComputers();
		}
	}
}


function showComputerForm ($template,$target,$ID) {

	GLOBAL $cfg_install, $cfg_layout, $lang;

	$comp = new Computer;

	if ($comp->getfromDB($ID,$template)) {

		if ($template) {
			$datestring = $lang["computers"][14].": ";
			$date = date("Y-m-d H:i:s"); 
			
		} else {
			$datestring = $lang["computers"][11].": ";
			$date = $comp->fields["date_mod"];
		}
		echo "<center><table border=0>";
		echo "<form name=form method=post action=$target>";
		echo "<tr><th align=center>";
		if ($template) {
			echo $lang["computers"][12].": ".$comp->fields["templname"];
		} else {
			echo $lang["computers"][13].": ".$comp->fields["ID"];
		}
		echo "</th><th align=right>".$datestring.$date;
		echo "</th></tr>";
		
		echo "<tr><td bgcolor=#CCCCCC valign=top>";
		echo "<table cellpadding=0 cellspacing=0 border=0>\n";

		echo "<tr><td>".$lang["computers"][7].":		</td>";
		echo "<td><input type=text name=name value=\"".$comp->fields["name"]."\" size=10></td>";
		echo "</tr>";

		echo "<tr><td>".$lang["computers"][10].": 	</td>";
		echo "<td>";
			dropdownValue("dropdown_locations", "location", $comp->fields["location"]);
		echo "</td></tr>";

		echo "<tr><td>".$lang["computers"][15].":		</td>";
		echo "<td><input type=text name=contact_num value=\"".$comp->fields["contact_num"]."\" size=5>";
		echo "</td></tr>";
	
		echo "<tr><td>".$lang["computers"][16].":	</td>";
		echo "<td><input type=text name=contact size=12 value=\"".$comp->fields["contact"]."\">";
		echo "</td></tr>";

		echo "<tr><td>".$lang["computers"][17].":	</td>";
		echo "<td><input type=text name=serial size=12 value=\"".$comp->fields["serial"]."\">";
		echo "</td></tr>";

		echo "<tr><td>".$lang["computers"][18].":	</td>";
		echo "<td><input type=text size=12 name=otherserial value=\"".$comp->fields["otherserial"]."\">";
		echo "</td></tr>";

		echo "<tr><td valign=top>".$lang["computers"][19].":</td>";
		echo "<td><textarea cols=20 rows=8 name=comments wrap=soft>".$comp->fields["comments"]."</textarea>";
		echo "</td></tr>";

		echo "</table>";

		echo "</td>\n";	
		echo "<td bgcolor=#CCCCCC valign=top>\n";
		echo "<table cellpadding=0 cellspacing=0 border=0>";


		echo "<tr><td>".$lang["computers"][8].": 	</td>";
		echo "<td>";
			dropdownValue("type_computers", "type", $comp->fields["type"]);
		echo "</td></tr>";

		echo "<tr><td>".$lang["computers"][9].": 	</td>";
		echo "<td>";	
			dropdownValue("dropdown_os", "os", $comp->fields["os"]);
		echo "</td></tr>";
		
		echo "<tr><td>".$lang["computers"][20].":</td>";
		echo "<td><input type=text size=8 name=osver value=\"".$comp->fields["osver"]."\">";
		echo "</td></tr>";
		
		echo "<tr><td>".$lang["computers"][21].":	</td>";
		echo "<td>";
			dropdownValue("dropdown_processor", "processor", $comp->fields["processor"]);
		echo "</td></tr>";
	
		echo "<tr><td>".$lang["computers"][22].":	</td>";
		echo "<td><input type=text name=processor_speed size=4 value=\"".$comp->fields["processor_speed"]."\">";
		echo "</td></tr>";

		echo "<tr><td>".$lang["computers"][35].":	</td>";
		echo "<td>";
			dropdownValue("dropdown_moboard", "moboard", $comp->fields["moboard"]);
		echo "</td></tr>";

		echo "<tr><td>".$lang["computers"][33].":	</td>";
		echo "<td>";
			dropdownValue("dropdown_sndcard", "sndcard", $comp->fields["sndcard"]);
		echo "</td></tr>";
		
		echo "<tr><td>".$lang["computers"][34].":	</td>";
		echo "<td>";
			dropdownValue("dropdown_gfxcard", "gfxcard", $comp->fields["gfxcard"]);
		echo "</td></tr>";
				
		echo "<tr><td>".$lang["computers"][23].":	</td>";
		echo "<td>";
			dropdownValue("dropdown_ram", "ramtype", $comp->fields["ramtype"]);
		echo "</td></tr>";
		
		echo "<tr><td>".$lang["computers"][24].":	</td>";
		echo "<td><input type=text name=ram value=\"".$comp->fields["ram"]."\" size=3>";
		echo "</td></tr>";

		echo "<tr><td>".$lang["computers"][36].":	</td>";
		echo "<td>";
			dropdownValue("dropdown_hdtype", "hdtype", $comp->fields["hdtype"]);
		echo "</td></tr>";

		echo "<tr><td>".$lang["computers"][25].":	</td>";
		echo "<td><input type=text name=hdspace size=3 value=\"".$comp->fields["hdspace"]."\">";
		echo "</td></tr>";

		echo "<tr><td>".$lang["computers"][26].":	</td>";
		echo "<td>";
			dropdownValue("dropdown_network", "network", $comp->fields["network"]);
		echo "</td></tr>";
		
		echo "<tr><td>".$lang["computers"][41].":	</td>";
		echo "<td><input type=text name='achat_date' readonly size=10 value=\"".$comp->fields["achat_date"]."\">";
		echo "&nbsp; <input name='button' type='button'  onClick=\"window.open('mycalendar.php?form=form&elem=achat_date','Calendrier','width=200,height=220')\" value='".$lang["buttons"][15]."...'>";
		echo "&nbsp; <input name='button_reset' type='button' onClick=\"document.forms['form'].achat_date.value='0000-00-00'\" value='reset'>";
    echo "</td></tr>";
		
		echo "<tr><td>".$lang["computers"][42].":	</td>";
		echo "<td><input type=text name='date_fin_garantie' readonly size=10 value=\"".$comp->fields["date_fin_garantie"]."\">";
		echo "&nbsp; <input name='button' type='button' onClick=\"window.open('mycalendar.php?form=form&elem=date_fin_garantie','Calendrier','width=200,height=220')\" value='".$lang["buttons"][15]."...'>";
		echo "&nbsp; <input name='button_reset' type='button' onClick=\"document.forms['form'].date_fin_garantie.value='0000-00-00'\" value='reset'>";
    echo "</td></tr>";
		
		echo "<tr><td>".$lang["computers"][43].":	</td>";
		echo "<td>";
		if ($comp->fields["maintenance"] == 1) {
			echo " OUI <input type=radio name='maintenance' value=1 checked>";
			echo "&nbsp; &nbsp; NON <input type=radio name='maintenance' value=0>";
		} else {
			echo " OUI <input type=radio name='maintenance' value=1>";
			echo "&nbsp; &nbsp; NON <input type=radio name='maintenance' value=0 checked >";
		}
		echo "</td></tr>";
		
		echo "<tr><td>".$lang["computers"][27].": </td><td>";
		
		// Is Server?
		echo "<table border=0 cellpadding=2 cellspacing=0><tr>";
		echo "<td>";
		if ($comp->fields["flags_server"] == 1) {
			echo "<input type=checkbox name=flags_server value=1 checked>";
		} else {
			echo "<input type=checkbox name=flags_server value=1>";
		}
		echo "</td><td>".$lang["computers"][28]."</td>";
		echo "</tr></table>";

		echo "</td></tr>";


		echo "</table>";

		echo "</td>\n";	
		echo "</tr><tr>";

		if ($template) {
			echo "<td bgcolor=#DDDDDD align=center colspan=2>\n";
			echo "<input type=submit name=add value=\"".$lang["buttons"][8]."\">";
			echo "</td></form>\n";	
		} else {
			echo "<td bgcolor=#DDDDDD align=center valign=top>\n";
			echo "<input type=hidden name=ID value=$ID>";
			echo "<input type=submit name=update value=\"".$lang["buttons"][7]."\">";
			echo "</td></form>\n";	
			echo "<td bgcolor=#DDDDDD align=center>\n";
			echo "<form method=post action=\"$target\">";
			echo "<input type=hidden name=ID value=$ID>";
			echo "<input type=submit name=delete value=\"".$lang["buttons"][6]."\">";
			echo "";
			echo "</td></form>";
		}

		echo "</tr>\n";
		echo "</table>\n";

		echo "</center>\n";

		echo "</table></center>";

		return true;
	} else {
		echo "<center><b>".$lang["computers"][32]."</b></center>";
		echo "<hr noshade>";
		searchFormComputers();
		return false;
	}

}


function updateComputer($input) {
	// Update a computer in the database

	$comp = new Computer;
	$comp->getFromDB($input["ID"],0);

	// set new date and make sure it gets updated
	$updates[0]= "date_mod";
	$comp->fields["date_mod"] = date("Y-m-d H:i:s");

	// Pop off the last two attributes, no longer needed
	$null=array_pop($input);
	$null=array_pop($input);
	
	// Get all flags and fill with 0 if unchecked in form
	for ($i=0; $i < count($comp->fields); $i++) {
		list($key,$val) = each($comp->fields);
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
		if ($comp->fields[$key] != $input[$key]) {
			$comp->fields[$key] = $input[$key];
			$updates[$x] = $key;
			$x++;
		}
	}
	$comp->updateInDB($updates);
}

function addComputer($input) {
	// Add Computer

	$comp = new Computer;
	
  // set new date.
   $comp->fields["date_mod"] = date("Y-m-d H:i:s");
   
	// dump status
	$null=array_pop($input);
	
	// fill array for update
	for ($i=0; $i < count($input); $i++) {
		list($key,$val) = each($input);
		if ($comp->fields[$key] != $input[$key]) {
			$comp->fields[$key] = $input[$key];
		}
	}

	$comp->addToDB();
}

function deleteComputer($input) {
	// Delete Computer
	
	$comp = new Computer;
	$comp->deleteFromDB($input["ID"],$input["template"]);
} 	

function showConnections($ID) {

	GLOBAL $cfg_layout, $cfg_install, $lang;

	$db = new DB;

	echo "<center><table border=0 width=90% cols=2>";
	echo "<tr><th colspan=2>".$lang["connect"][0].":</th></tr>";
	echo "<tr><th>".$lang["computers"][39].":</th><th>".$lang["computers"][40].":</th></tr>";

	echo "<tr bgcolor=\"".$cfg_layout["tab_bg_1"]."\">";

	// Printers
	echo "<td align=center>";
	$query = "SELECT * from connect_wire WHERE end2='$ID' AND type='3'";
	if ($result=$db->query($query)) {
		$resultnum = $db->numrows($result);
		if ($resultnum>0) {
			for ($i=0; $i < $resultnum; $i++) {
				$tID = $db->result($result, $i, "end1");
				$printer = new Printer;
				$printer->getfromDB($tID);
				echo "<li><b><a href=\"".$cfg_install["root"]."/printers/printers-info-form.php?ID=$tID\">";
				echo $printer->fields["name"]." (".$printer->fields["ID"].")";
				echo "</b></a><br>";
			}			
		} else {
			echo $lang["computers"][38];
		}
	}
	echo "</td>";

	// Monitors
	echo "<td align=center>";
	$query = "SELECT * from connect_wire WHERE end2='$ID' AND type='4'";
	if ($result=$db->query($query)) {
		$resultnum = $db->numrows($result);
		if ($resultnum>0) {
			for ($i=0; $i < $resultnum; $i++) {
				$tID = $db->result($result, $i, "end1");
				$monitor = new Monitor;
				$monitor->getfromDB($tID);
				echo "<li><b><a href=\"".$cfg_install["root"]."/monitors/monitors-info-form.php?ID=$tID\">";
				echo $monitor->fields["name"]." (".$monitor->fields["ID"].")";
				echo "</b></a><br>";
			}			
		} else {
			echo $lang["computers"][37];
		}
	}
	echo "</td>";

	echo "</tr>";
	echo "</table></center><br>";
	
}




?>
