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
 ----------------------------------------------------------------------
 Original Author of file: Julien Dombre
 Purpose of file:
 ----------------------------------------------------------------------
*/

// device_type
// 1 computers
// 2 networking
// 3 printers
// 4 monitors
// 5 peripherals
// 6 

include ("_relpos.php");
// FUNCTIONS Reservation

/// TOCHANGE
function titleReservation(){
           GLOBAL  $lang,$HTMLRel;
           
           echo "<div align='center'><table border='0'><tr><td>";

           echo "<img src=\"".$HTMLRel."pics/printer.png\" alt='".$lang["printers"][0]."' title='".$lang["printers"][0]."'>".$lang["reservation"][1]."</td>";

           echo "</td></tr></table></div>";
}

function searchFormReservationItem($field="",$phrasetype= "",$contains="",$sort= ""){
	// Print Search Form
	
	GLOBAL $cfg_install, $cfg_layout, $layout, $lang;

	$option["glpi_reservation_item.ID"]				= $lang["software"][1];
//	$option["type"]			= $lang["software"][3];
//	$option["glpi_dropdown_locations.name"]			= $lang["software"][4];
//	$option["glpi_software.version"]			= $lang["software"][5];
//	$option["glpi_software.comments"]			= $lang["software"][6];
	
	echo "<form method=get action=\"".$cfg_install["root"]."/reservation/index.php\">";
	echo "<center><table class='tab_cadre' width='750'>";
	echo "<tr><th colspan='2'><b>".$lang["search"][0].":</b></th></tr>";
	echo "<tr class='tab_bg_1'>";
	echo "<td align='center'>";
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
	echo $lang["search"][1];
	echo "&nbsp;<select name='phrasetype' size='1' >";
	echo "<option value='contains'";
	if($phrasetype == "contains") echo "selected";
	echo ">".$lang["search"][2]."</option>";
	echo "<option value='exact'";
	if($phrasetype == "exact") echo "selected";
	echo ">".$lang["search"][3]."</option>";
	echo "</select>";
	echo "<input type='text' size='15' name=\"contains\" value=\"". $contains ."\" />";
	echo "&nbsp;";
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
	echo "</td></tr></table></center></form>";
}

function showReservationItemList($target,$username,$field,$phrasetype,$contains,$sort,$order,$start){
	// Lists Reservation Items

	GLOBAL $cfg_install, $cfg_layout, $cfg_features, $lang, $HTMLRel;

		$db = new DB;

	// Build query
	if($field=="all") {
	/*	$where = " (";
		$where .= "res_item.".$coco . " LIKE '%".$contains."%'";
		$where .= ")";
	*/
	$where=" 1 = 1 ";
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
	
	$query = "select glpi_reservation_item.ID from glpi_reservation_item ";
	$query .= " where  $where ORDER BY $sort $order";
	//echo $query;
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
			if ($sort=="glpi_reservation_item.ID") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_reservation_item.ID&order=ASC&start=$start\">";
			echo $lang["reservation"][2]."</a></th>";

			// Location			
			echo "<th>";
			if ($sort=="glpi_reservation_item.device_type") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_reservation_item.device_type&order=ASC&start=$start\">";
			echo $lang["reservation"][3]."</a></th>";

			// Type
			echo "<th>";
			if ($sort=="glpi_reservation_item.id_device") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_reservation_item.id_device&order=ASC&start=$start\">";
			echo $lang["reservation"][4]."</a></th>";

			echo "<th>&nbsp;</th>";
			echo "</tr>";

			for ($i=0; $i < $numrows_limit; $i++) {
				$ID = $db->result($result_limit, $i, "ID");
				$ri = new ReservationItem;
				$ri->getfromDB($ID);
				echo "<tr class='tab_bg_2'>";
				echo "<td>";
				echo $ri->fields["ID"];
				echo "</td>";
				
				echo "<td>". $ri->getType()."</td>";
				echo "<td><b>". $ri->getLink() ."</b></td>";
				echo "<td>";
				showReservationForm($ri->fields["device_type"],$ri->fields["id_device"]);
				echo "</td>";
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

function showReservationForm($device_type,$id_device){

GLOBAL $cfg_install,$lang;

$query="select * from glpi_reservation_item where (device_type='$device_type' and id_device='$id_device')";
$db=new DB;
if ($result = $db->query($query)) {
		$numrows =  $db->numrows($result);
//echo "<form name='resa_form' method='post' action=".$cfg_install["root"]."/reservation/index.php>";
echo "<a href=\"".$cfg_install["root"]."/reservation/index.php?";
// Ajouter le matériel
if ($numrows==0){
//echo "<input type='hidden' name='id_device' value='$id_device'>";
//echo "<input type='hidden' name='device_type' value='$device_type'>";
//echo "<input class='submit' type='submit' name='add' value='".$lang["reservation"][7]."'>";
echo "id_device=$id_device&device_type=$device_type&add=add\">".$lang["reservation"][7]."</a>";
}
// Supprimer le matériel
else {
//echo "<input type='hidden' name='ID' value='".$db->result($result,0,"ID")."'>";
//echo "<input class='submit' type='submit' name='delete' value='".$lang["reservation"][6]."'>";
echo "ID=".$db->result($result,0,"ID")."&delete=delete\">".$lang["reservation"][6]."</a>";
}

//echo "</form>";
}
}

function addReservationItem($input){
// Add Reservation Item, nasty hack until we get PHP4-array-functions

	$ri = new ReservationItem;

	// dump status
	$null = array_pop($input);
	
	// fill array for update
	foreach ($input as $key => $val) {
		if (empty($sw->fields[$key]) || $sw->fields[$key] != $input[$key]) {
			$ri->fields[$key] = $input[$key];
		}
	}

	if ($ri->addToDB()) {
		return true;
	} else {
		return false;
	}


}

function deleteReservationItem($input){

	// Delete Reservation Item 
	
	$ri = new ReservationItem;
	$ri->deleteFromDB($input["ID"]);
}
?>