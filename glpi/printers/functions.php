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
// FUNCTIONS Printers

function searchFormPrinters() {
	// Print Search Form
	
	GLOBAL $cfg_install, $cfg_layout, $layout, $lang;

	$option["name"]				= $lang["printers"][5];
	$option["ID"]				= $lang["printers"][19];
	$option["location"]			= $lang["printers"][6];
	$option["type"]				= $lang["printers"][9];
	$option["serial"]			= $lang["printers"][10];
	$option["otherserial"]		= $lang["printers"][11]	;
	$option["comments"]			= $lang["printers"][12];
	$option["contact"]			= $lang["printers"][8];
	$option["contact_num"]		= $lang["printers"][7];
	$option["date_mod"]			= $lang["printers"][16];

	echo "<form method=get action=\"".$cfg_install["root"]."/printers/printers-search.php\">";
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

	echo "<form method=get action=\"".$cfg_install["root"]."/printers/printers-search.php\">";
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


function showPrintersList($target,$username,$field,$phrasetype,$contains,$sort,$order,$start) {

	// Lists Printers

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
	$query = "SELECT * FROM printers WHERE $where ORDER BY $sort";
	
	// Get it from database	
	$db = new DB;
	if ($result = $db->query($query)) {
		$numrows = $db->numrows($result);

		// Limit the result, if no limit applies, use prior result
		if ($numrows>$cfg_features["list_limit"]) {
			$query_limit = "SELECT * FROM printers WHERE $where ORDER BY $sort $order LIMIT $start,".$cfg_features["list_limit"]." ";
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
			echo $lang["printers"][5]."</th>";

			// Location			
			echo "<th>";
			if ($sort=="location") {
				echo "&middot;&nbsp;";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=location&order=ASC&start=$start\">";
			echo $lang["printers"][6]."</th>";

			// Type
			echo "<th>";
			if ($sort=="type") {
				echo "&middot;&nbsp;";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=type&order=ASC&start=$start\">";
			echo $lang["printers"][9]."</th>";

			// Last modified		
			echo "<th>";
			if ($sort=="date_mod") {
				echo "&middot;&nbsp;";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=date_mod&order=DESC&start=$start\">";
			echo $lang["printers"][16]."</th>";
	
			echo "</tr>";

			for ($i=0; $i < $numrows_limit; $i++) {
				$ID = $db->result($result_limit, $i, "ID");
				$printer = new Printer;
				$printer->getfromDB($ID);
				echo "<tr bgcolor=\"".$cfg_layout["tab_bg_2"]."\">";
				echo "<td><b>";
				echo "<a href=\"".$cfg_install["root"]."/printers/printers-info-form.php?ID=$ID\">";
				echo $printer->fields["name"]." (".$printer->fields["ID"].")";
				echo "</b></a></td>";
				echo "<td>".$printer->fields["location"]."</td>";
				echo "<td>".$printer->fields["type"]."</td>";
				echo "<td>".$printer->fields["date_mod"]."</td>";
				echo "</tr>";
			}

			// Close Table
			echo "</table></center>";

			// Pager
			$parameters="field=$field&phrasetype=$phrasetype&contains=$contains&sort=$sort";
			printPager($start,$numrows,$target,$parameters);

		} else {
			echo "<center><b>".$lang["printers"][17]."</b></center>";
			echo "<hr noshade>";
			searchFormPrinters();
		}
	}
}


function showPrintersForm ($target,$ID) {

	GLOBAL $cfg_install, $cfg_layout, $lang;

	$printer = new Printer;

	echo "<center><form method=post name=form action=\"$target\">";
	echo "<table border=0 cellpadding=2>";
	echo "<tr><th colspan=2><b>";
	if ($ID=="") {
		echo $lang["printers"][3].":";
	} else {
		$printer->getfromDB($ID);
		echo $lang["printers"][4]." ID $ID:";
	}		
	echo "</b></th></tr>";
	
	echo "<tr><td bgcolor=".$cfg_layout["tab_bg_1"]." valign=top>";

	echo "<table cellpadding=0 cellspacing=0 border=0>\n";

	echo "<tr><td>".$lang["printers"][5].":	</td>";
	echo "<td><input type=text name=name value=\"".$printer->fields["name"]."\" size=10></td>";
	echo "</tr>";

	echo "<tr><td>".$lang["printers"][6].": 	</td><td>";
		dropdownValue("dropdown_locations", "location", $printer->fields["location"]);
	echo "</td></tr>";

	echo "<tr><td>".$lang["printers"][7].":	</td>";
	echo "<td><input type=text name=contact_num value=\"".$printer->fields["contact_num"]."\" size=5></td>";
	echo "</tr>";

	echo "<tr><td>".$lang["printers"][8].":	</td>";
	echo "<td><input type=text name=contact size=12 value=\"".$printer->fields["contact"]."\"></td>";
	echo "</tr>";

	echo "</table>";

	echo "</td>\n";	
	echo "<td bgcolor=".$cfg_layout["tab_bg_1"]." valign=top>";

	echo "<table cellpadding=0 cellspacing=0 border=0";

	echo "<tr><td>".$lang["printers"][9].": 	</td><td>";
		dropdownValue("type_printers", "type", $printer->fields["type"]);
	echo "</td></tr>";
		
	echo "<tr><td>".$lang["printers"][10].":	</td>";
	echo "<td><input type=text name=serial size=12 value=\"".$printer->fields["serial"]."\"></td>";
	echo "</tr>";

	echo "<tr><td>".$lang["printers"][11].":</td>";
	echo "<td><input type=text size=12 name=otherserial value=\"".$printer->fields["otherserial"]."\"></td>";
	echo "</tr>";

		echo "<tr><td>".$lang["printers"][18].": </td><td>";

		// serial interface?
		echo "<table border=0 cellpadding=2 cellspacing=0><tr>";
		echo "<td>";
		if ($printer->fields["flags_serial"] == 1) {
			echo "<input type=checkbox name=flags_serial value=1 checked>";
		} else {
			echo "<input type=checkbox name=flags_serial value=1>";
		}
		echo "</td><td>".$lang["printers"][14]."</td>";
		echo "</tr></table>";

		// parallel interface?
		echo "<table border=0 cellpadding=2 cellspacing=0><tr>";
		echo "<td>";
		if ($printer->fields["flags_par"] == 1) {
			echo "<input type=checkbox name=flags_par value=1 checked>";
		} else {
			echo "<input type=checkbox name=flags_par value=1>";
		}
		echo "</td><td>".$lang["printers"][15]."</td>";
		echo "</tr></table>";
		
		echo "<tr><td>".$lang["printers"][23].":</td>";
		echo "<td><input type=text size=12 name=ramSize value=\"".$printer->fields["ramSize"]."\"></td>";
		echo "</tr>";

		echo "</td></tr>";

	echo "</table>";
	echo "</td>\n";	
	echo "</tr>";
	echo "<tr>";
	echo "<td bgcolor=".$cfg_layout["tab_bg_1"]." valign=top colspan=2>";
	
	
	
		echo "<table width=100% cellpadding=0 cellspacing=0 border=0><tr><td valign=top>";
	    echo "<tr><td>".$lang["printers"][20].":	</td>";
		echo "<td><input type=text name='achat_date' readonly size=10 value=\"0000-00-00\">";
		echo "&nbsp; <input name='button' type='button'  onClick=\"window.open('mycalendar.php?form=form&elem=achat_date','Calendrier','width=200,height=220')\" value=".$lang["buttons"][15]."...'>";
		echo "&nbsp; <input name='button_reset' type='button' onClick=\"document.forms['form'].achat_date.value='0000-00-00'\" value='reset'>";
    echo "</td></tr>";
		
		echo "<tr><td>".$lang["printers"][21].":	</td>";
		echo "<td><input type=text name='date_fin_garantie' readonly size=10 value=\"0000-00-00\">";
		echo "&nbsp; <input name='button' type='button' onClick=\"window.open('mycalendar.php?form=form&elem=date_fin_garantie','Calendrier','width=200,height=220')\" value='".$lang["buttons"][15]."...'>";
		echo "&nbsp; <input name='button_reset' type='button' onClick=\"document.forms['form'].date_fin_garantie.value='0000-00-00'\" value='reset'>";
    echo "</td></tr>";
		
		echo "<tr><td>".$lang["printers"][22].":	</td>";
		echo "<td>";
		if ($printer->fields["maintenance"] == 1) {
			echo " OUI <input type=radio name='maintenance' value=1 checked>";
			echo "&nbsp; &nbsp; NON <input type=radio name='maintenance' value=0>";
		} else {
			echo " OUI <input type=radio name='maintenance' value=1>";
			echo "&nbsp; &nbsp; NON <input type=radio name='maintenance' value=0 checked >";
		}
		echo "</td></tr>";
		
	echo "</table>";	
	echo "</td>\n";	
	echo "</tr>";
	echo "<tr>";
	echo "<td bgcolor=".$cfg_layout["tab_bg_1"]." valign=top colspan=2>";

	echo "<table width=100% cellpadding=0 cellspacing=0 border=0><tr><td valign=top>";
	echo $lang["printers"][12].":	</td>";
	echo "<td align=center><textarea cols=35 rows=4 name=comments wrap=soft>".$printer->fields["comments"]."</textarea>";
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

		showConnect($target,$ID,3);

		showPorts($ID,3);

		showPortsAdd($ID,3);
	}
}


function updatePrinter($input) {
	// Update a printer in the database

	$printer = new Printer;
	$printer->getFromDB($input["ID"]);

	// set new date and make sure it gets updated
	$updates[0]= "date_mod";
	$printer->fields["date_mod"] = date("Y-m-d H:i:s");
	
	// Pop off the last two attributes, no longer needed
	$null=array_pop($input);
	$null=array_pop($input);
	
	// Get all flags and fill with 0 if unchecked in form
	for ($i=0; $i < count($printer->fields); $i++) {
		list($key,$val) = each($printer->fields);
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
		if ($printer->fields[$key] != $input[$key]) {
			$printer->fields[$key] = $input[$key];
			$updates[$x] = $key;
			$x++;
		}
	}

	$printer->updateInDB($updates);

}

function addPrinter($input) {
	// Add Printer, nasty hack until we get PHP4-array-functions

	$printer = new Printer;
	
	// dump status
	$null = array_pop($input);
	
	// fill array for update
	for ($i=0; $i < count($input); $i++) {
		list($key,$val) = each($input);
		if ($printer->fields[$key] != $input[$key]) {
			$printer->fields[$key] = $input[$key];
		}
	}

	$printer->addToDB();

}

function deletePrinter($input) {
	// Delete Printer
	
	$printer = new Printer;
	$printer->deleteFromDB($input["ID"]);
	
} 	


?>
