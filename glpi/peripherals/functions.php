<?php
/*
 
  ----------------------------------------------------------------------
GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2004 by the INDEPNET Development Team.
 
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
                echo "<img src=\"".$HTMLRel."pics/periphs.png\" alt='".$lang["peripherals"][0]."' title='".$lang["peripherals"][0]."'></td><td><a  class='icon_consol' href=\"peripherals-info-form.php?new=1\"><b>".$lang["peripherals"][0]."</b></a>";
                echo "</td></tr></table></div>";
}


function searchFormperipheral($field="",$phrasetype= "",$contains="",$sort= "") {
	// Print Search Form
	
	GLOBAL $cfg_install, $cfg_layout, $layout, $lang;

	$option["periph.name"]				= $lang["peripherals"][5];
	$option["periph.ID"]				= $lang["peripherals"][23];
	$option["glpi_dropdown_locations.name"]			= $lang["peripherals"][6];
	$option["glpi_type_peripherals.name"]				= $lang["peripherals"][9];
	$option["periph.serial"]			= $lang["peripherals"][10];
	$option["periph.otherserial"]		= $lang["peripherals"][11]	;
	$option["periph.comments"]			= $lang["peripherals"][12];
	$option["periph.contact"]			= $lang["peripherals"][8];
	$option["periph.contact_num"]		= $lang["peripherals"][7];
	$option["periph.date_mod"]			= $lang["peripherals"][16];

	echo "<form method='get' action=\"".$cfg_install["root"]."/peripherals/peripherals-search.php\">";
	echo "<div align='center'><table  width='750' class='tab_cadre'>";
	echo "<tr><th colspan='2'><b>".$lang["search"][0].":</b></th></tr>";
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
	echo "</td><td width='80' align='center' class='tab_bg_2'>";
	echo "<input type='submit' value=\"".$lang["buttons"][0]."\" class='submit'>";
	echo "</td></tr></table></div></form>";
}


function showPeripheralList($target,$username,$field,$phrasetype,$contains,$sort,$order,$start) {

	// Lists peripheral

	GLOBAL $cfg_install, $cfg_layout, $cfg_features, $lang, $HTMLRel;

	$db = new DB;

	// Build query
	if($field=="all") {
		$where = " (";
		$fields = $db->list_fields("glpi_peripherals");
		$columns = $db->num_fields($fields);
		
		for ($i = 0; $i < $columns; $i++) {
			if($i != 0) {
				$where .= " OR ";
			}
			$coco = mysql_field_name($fields, $i);

			if($coco == "location") {
				$where .= " glpi_dropdown_locations.name LIKE '%".$contains."%'";
			}
			elseif($coco == "type") {
				$where .= " glpi_type_peripherals.name LIKE '%".$contains."%'";
			}
			else {
   				$where .= "periph.".$coco . " LIKE '%".$contains."%'";
			}
		}
		$where .= ")";
	}
	else {
		if ($phrasetype == "contains") {
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
	$query = "select periph.ID from glpi_peripherals as periph LEFT JOIN glpi_dropdown_locations on periph.location=glpi_dropdown_locations.ID ";
	$query .= "LEFT JOIN glpi_type_peripherals on periph.type = glpi_type_peripherals.ID ";
	$query .= "where $where ORDER BY $sort $order";

	// Get it from database	
	if ($result = $db->query($query)) {
		$numrows =  $db->numrows($result);

		// Limit the result, if no limit applies, use prior result
		if ($numrows > $cfg_features["list_limit"]) {
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
			if ($sort=="periph.name") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=periph.name&order=ASC&start=$start\">";
			echo $lang["peripherals"][5]."</a></th>";

			// Location			
			echo "<th>";
			if ($sort=="glpi_dropdown_locations.name") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_dropdown_locations.name&order=ASC&start=$start\">";
			echo $lang["peripherals"][6]."</a></th>";

			// Type
			echo "<th>";
			if ($sort=="glpi_type_peripherals.name") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_type_peripherals.name&order=ASC&start=$start\">";
			echo $lang["peripherals"][9]."</a></th>";

			// Last modified		
			echo "<th>";
			if ($sort=="periph.date_mod") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=periph.date_mod&order=DESC&start=$start\">";
			echo $lang["peripherals"][16]."</a></th>";

			// Contact person
			echo "<th>";
			if ($sort=="periph.contact") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=periph.contact&order=ASC&start=$start\">";
			echo $lang["peripherals"][8]."</a></th>";

			echo "</tr>";

			for ($i=0; $i < $numrows_limit; $i++) {
				$ID = $db->result($result_limit, $i, "ID");
				$mon = new Peripheral;
				$mon->getfromDB($ID);
				echo "<tr class='tab_bg_2'>";
				echo "<td><b>";
				echo "<a href=\"".$cfg_install["root"]."/peripherals/peripherals-info-form.php?ID=$ID\">";
				echo $mon->fields["name"]." (".$mon->fields["ID"].")";
				echo "</a></b></td>";
				echo "<td>". getDropdownName("glpi_dropdown_locations",$mon->fields["location"]) ."</td>";
				echo "<td>". getDropdownName("glpi_type_peripherals",$mon->fields["type"]) ."</td>";
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
			echo "<center><b>".$lang["peripherals"][17]."</b></center>";
			//echo "<hr noshade>";
			//searchFormperipheral();
		}
	}
}


function showperipheralForm ($target,$ID) {

	GLOBAL $cfg_install, $cfg_layout, $lang,$HTMLRel;

	$mon = new Peripheral;

	echo "<center><form method='post' name=form action=\"$target\">";
	echo "<table class='tab_cadre' cellpadding='2'>";
	echo "<tr><th colspan='2'><b>";
	if (empty($ID)) {
		echo $lang["peripherals"][3].":";
		$mon->getEmpty();
	} else {
		$mon->getfromDB($ID);
		echo $lang["peripherals"][4]." ID $ID:";
	}		
	echo "</b></th></tr>";
	
	echo "<tr><td class='tab_bg_1' valign='top'>";

	echo "<table cellpadding='1px' cellspacing='0' border='0'>\n";

	echo "<tr><td>".$lang["peripherals"][5].":	</td>";
	echo "<td><input type='text' name='name' value=\"".$mon->fields["name"]."\" size='20'></td>";
	echo "</tr>";

	echo "<tr><td>".$lang["peripherals"][6].": 	</td><td>";
		dropdownValue("glpi_dropdown_locations", "location", $mon->fields["location"]);
	echo "</td></tr>";

	echo "<tr><td>".$lang["peripherals"][7].":	</td>";
	echo "<td><input type='text' name='contact_num' value=\"".$mon->fields["contact_num"]."\" size='20'></td>";
	echo "</tr>";

	echo "<tr><td>".$lang["peripherals"][8].":	</td>";
	echo "<td><input type='text' name='contact' size='20' value=\"".$mon->fields["contact"]."\"></td>";
	echo "</tr>";
	if (!empty($ID)){
		echo "<tr><td>".$lang["reservation"][24].":</td><td><b>";
		showReservationForm(5,$ID);
		echo "</b></td></tr>";
	}

	echo "</table>";

	echo "</td>\n";	
	echo "<td class='tab_bg_1' valign='top'>";

	echo "<table cellpadding='1px' cellspacing='0' border='0'>";

	echo "<tr><td>".$lang["peripherals"][9].": 	</td><td>";
		dropdownValue("glpi_type_peripherals", "type", $mon->fields["type"]);
	echo "</td></tr>";
		
	echo "<tr><td>".$lang["peripherals"][18].":</td>";
	echo "<td><input type='text' size='20' name='brand' value=\"".$mon->fields["brand"]."\"></td>";
	echo "</tr>";

	
	echo "<tr><td>".$lang["peripherals"][10].":	</td>";
	echo "<td><input type='text' name='serial' size='20' value=\"".$mon->fields["serial"]."\"></td>";
	echo "</tr>";

	echo "<tr><td>".$lang["peripherals"][11].":</td>";
	echo "<td><input type='text' size='20' name='otherserial' value=\"".$mon->fields["otherserial"]."\"></td>";
	echo "</tr>";
	

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
	
	if ($ID=="") {

		echo "<tr>";
		echo "<td class='tab_bg_2' valign='top' colspan='2'>";
		echo "<center><input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'></center>";
		echo "</td>";
		echo "</form></tr>";

		echo "</table></center>";

	} else {

		echo "<tr>";
		echo "<td class='tab_bg_2' valign='top'>";
		echo "<input type='hidden' name='ID' value=\"$ID\">\n";
		echo "<center><input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit' class='submit'></center>";
		echo "</td></form>\n\n";
		echo "<form action=\"$target\" method='post'>\n";
		echo "<td class='tab_bg_2' valign='top'>\n";
		echo "<input type='hidden' name='ID' value=\"$ID\">\n";
		echo "<center><input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit' class='submit'></center>";
		echo "</td>";
		echo "</form></tr>";

		echo "</table></center>";

		showConnect($target,$ID,5);
	}
}


function updatePeripheral($input) {
	// Update a Peripheral in the database

	$mon = new Peripheral;
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

function addPeripheral($input) {
	// Add Peripheral, nasty hack until we get PHP4-array-functions

	$mon = new Peripheral;

	// dump status
	$null = array_pop($input);
	
	// fill array for udpate
	foreach ($input as $key => $val) {
		if (!isset($mon->fields[$key]) || $mon->fields[$key] != $input[$key]) {
			$mon->fields[$key] = $input[$key];
		}
	}

	$mon->addToDB();

}

function deletePeripheral($input) {
	// Delete Printer
	
	$mon = new Peripheral;
	$mon->deleteFromDB($input["ID"]);
	
} 	
?>
