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

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
// FUNCTIONS State


function titleState(){
           GLOBAL  $lang,$HTMLRel;
           
              
	     
	     echo "<div align='center'><table border='0'><tr><td>";
                echo "<img src=\"".$HTMLRel."pics/status.png\" alt='' title=''></td><td><b><a class='icon_consol' href='".$HTMLRel."state/index.php?synthese=no'>".$lang["state"][1]."</a>";
		 echo "</b></td>";
	   echo "<td><a class='icon_consol' href='".$HTMLRel."state/index.php?synthese=yes'>".$lang["state"][11]."</a></td>";
         echo "</tr></table></div>";
	   
	   
}

function searchFormStateItem($field="",$phrasetype= "",$contains="",$sort= "",$state=""){
	// Print Search Form
	
	GLOBAL $cfg_install, $cfg_layout, $layout, $lang;

	$option["glpi_state_item.ID"]				= $lang["state"][4];
//	$option["glpi_reservation_item.device_type"]			= $lang["reservation"][3];
//	$option["glpi_dropdown_locations.name"]			= $lang["software"][4];
//	$option["glpi_software.version"]			= $lang["software"][5];
//      $option["glpi_state.comments"]			= $lang["state"][5];
	
	echo "<form method=\"get\" action=\"".$cfg_install["root"]."/state/index.php\">";
	echo "<div align='center'><table class='tab_cadre' width='750'>";
	echo "<tr><th colspan='3'><b>".$lang["search"][0].":</b></th></tr>";
	echo "<tr class='tab_bg_1'>";
	echo "<td align='center'>";
	echo $lang["state"][0]." : &nbsp; &nbsp;";
	dropdownValue("glpi_dropdown_state","state",$state);
	echo "</td><td align='center'>";
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
	echo "</td></tr></table></div></form>";
}

function showStateItemList($target,$username,$field,$phrasetype,$contains,$sort,$order,$start,$state){
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
	if(!empty($state)) {
		$where .= " AND (glpi_state_item.state = '".$state."')";
	}
	

	if (!$start) {
		$start = 0;
	}
	if (!$order) {
		$order = "ASC";
	}
	
	$query = "select glpi_state_item.device_type as d_type, glpi_state_item.id_device as id_device from glpi_state_item ";
	$query.= " LEFT JOIN glpi_dropdown_state ON (glpi_dropdown_state.ID = glpi_state_item.state) ";
	$query .= " where  $where AND glpi_state_item.is_template='0' ORDER BY $sort $order";

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
			// Pager
			$parameters="field=$field&amp;phrasetype=$phrasetype&amp;contains=$contains&amp;sort=$sort&amp;order=$order";
			printPager($start,$numrows,$target,$parameters);

			// Produce headline
			echo "<div align='center'><table  class='tab_cadrehov'><tr>";
			// Name
			echo "<th>";
			if ($sort=="glpi_state_item.ID") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&amp;phrasetype=$phrasetype&amp;contains=$contains&amp;sort=glpi_state_item.ID&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start\">";
			echo $lang["state"][4]."</a></th>";

			// Type			
			echo "<th>";
			if ($sort=="glpi_state_item.device_type") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&amp;phrasetype=$phrasetype&amp;contains=$contains&amp;sort=glpi_state_item.device_type&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start\">";
			echo $lang["state"][6]."</a></th>";

			// Item
			echo "<th>";
/*			if ($sort=="glpi_state_item.id_device") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&amp;phrasetype=$phrasetype&amp;contains=$contains&amp;sort=glpi_state_item.id_device&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start\">";
*/			echo $lang["state"][5];
//			 echo "</a>";
			 echo "</th>";

			
			// Type			
			echo "<th>";
			echo $lang["state"][9]."</th>";

			// Location			
			echo "<th>";
			echo $lang["state"][8]."</th>";
			
			// State			
			echo "<th>";
			if ($sort=="glpi_dropdown_state.name") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&amp;phrasetype=$phrasetype&amp;contains=$contains&amp;sort=glpi_dropdown_state.name&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start\">";
			echo $lang["state"][0]."</a></th>";

			echo "</tr>";

			for ($i=0; $i < $numrows_limit; $i++) {
				$id_device = $db->result($result_limit, $i, "id_device");
				$type = $db->result($result_limit, $i, "d_type");
				$ri = new StateItem;
				$ri->getfromDB($type,$id_device);
				echo "<tr class='tab_bg_2".(isset($ri->obj->fields["deleted"])&&$ri->obj->fields["deleted"]=='Y'?"_2":"")."' align='center'>";
				echo "<td>";
				echo $ri->fields["ID"];
				echo "</td>";
				
				echo "<td>". $ri->getType()."</td>";
				echo "<td><b>". $ri->getLink() ."</b></td>";
				echo "<td>". $ri->getItemType() ."</td>";
				echo "<td>". getDropdownName("glpi_dropdown_locations",$ri->obj->fields["location"]) ."</td>";
				echo "<td><b>". getDropdownName("glpi_dropdown_state",$ri->fields["state"]) ."</b></td>";

				echo "</tr>";
			}

			// Close Table
			echo "</table></div>";

			// Pager
			echo "<br>";
			printPager($start,$numrows,$target,$parameters);

		} else {
			echo "<div align='center'><b>".$lang["state"][7]."</b></div>";
		}
	}
	
}

function updateState($device_type,$id_device, $state,$template=0){
$si=new StateItem;

$where="";
if ($template==1)
$where= " AND is_template='1'";

$si->getFromDB($device_type,$id_device);
$db=new DB;

if ($state!=$si->fields["state"])
if ($si->fields["state"]!=-1){
if ($state==0)
	$db->query("DELETE FROM glpi_state_item WHERE device_type='$device_type' and id_device='$id_device' $where;");
else $db->query("UPDATE glpi_state_item SET state='$state' WHERE device_type='$device_type' and id_device='$id_device' $where;");

} else {
if ($state!=0){
	if ($template==1)
	$db->query("INSERT INTO glpi_state_item (device_type,id_device,state,is_template) VALUES ('$device_type','$id_device','$state','1');");
	else 
	$db->query("INSERT INTO glpi_state_item (device_type,id_device,state) VALUES ('$device_type','$id_device','$state');");
	}
	
}
	
}

function showStateSummary($target){
global $lang;
$db=new DB;

$query = "select glpi_dropdown_state.name as state, glpi_dropdown_state.ID as id_state, glpi_state_item.device_type as d_type, count(*) as cmp from glpi_state_item ";
$query.= " LEFT JOIN glpi_dropdown_state ON (glpi_dropdown_state.ID = glpi_state_item.state) ";
$query .= " WHERE glpi_state_item.is_template='0' GROUP BY glpi_state_item.device_type,glpi_dropdown_state.ID,glpi_dropdown_state.name ORDER BY glpi_dropdown_state.name,glpi_state_item.device_type";

$state_type=array(COMPUTER_TYPE,PRINTER_TYPE,MONITOR_TYPE,PERIPHERAL_TYPE,NETWORKING_TYPE);

$db->query($query);
	if ($result = $db->query($query)) {
		$numrows =  $db->numrows($result);
		if ($numrows>0){

			// Produce headline
			echo "<div align='center'><table  class='tab_cadrehov'><tr>";

			// Type			
			echo "<th>";
			echo $lang["state"][0]."</th>";

			$ci=new CommonItem;
			foreach ($state_type as $type){
				$ci->setType($type);
				echo "<th>".$ci->getType()."</th>";
				$total[$type]=0;
			}
			echo "<th>".$lang["state"][10]."</th>";
			echo "</tr>";
				$current_state=-1;
				$states=array();
			while ($data=$db->fetch_array($result)){
			$states[$data["id_state"]]["name"]=$data["state"];
			$states[$data["id_state"]][$data["d_type"]]=$data["cmp"];
			}
			
			foreach ($states as $key => $val)
			{
				$tot=0;
				echo "<tr class='tab_bg_2'><td align='center'><strong><a href='$target?state=$key'>".$val["name"]."</a></strong></td>";
				
				foreach ($state_type as $type){
					echo "<td align='center'>";
				
					if (isset($val[$type])) {
						echo $val[$type];
						$total[$type]+=$val[$type];
						$tot+=$val[$type];
					}
					else echo "&nbsp;";
					echo "</td>";
				}
				echo "<td align='center'><strong>$tot</strong></td>";
				echo "</tr>";
			}
			echo "<tr class='tab_bg_2'><td align='center'><strong><a href='$target?state=0'>".$lang["state"][10]."</a></strong></td>";
			$tot=0;
			foreach ($state_type as $type){
			echo "<td align='center'><strong>".$total[$type]."</strong></td>";
			$tot+=$total[$type];
			}
			echo "<td align='center'><strong>".$tot."</strong></td>";
			echo "</tr>";
			echo "</table></div>";

		}
		else {
			echo "<div align='center'><b>".$lang["state"][7]."</b></div>";
		}
	}


}

?>