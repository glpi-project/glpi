<?php
/*
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.
 
 http://indepnet.net/   http://glpi-project.org
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

 
// FUNCTIONS State


function titleState(){
           global  $lang,$HTMLRel;
           
              
	     
	     echo "<div align='center'><table border='0'><tr><td>";
                echo "<img src=\"".$HTMLRel."pics/status.png\" alt='' title=''></td><td><b><a class='icon_consol' href='".$HTMLRel."front/state.php?synthese=no'>".$lang["state"][1]."</a>";
		 echo "</b></td>";
	   echo "<td><a class='icon_consol' href='".$HTMLRel."front/state.php?synthese=yes'>".$lang["state"][11]."</a></td>";
         echo "</tr></table></div>";
	   
	   
}

function searchFormStateItem($field="",$phrasetype= "",$contains="",$sort= "",$state=""){
	// Print Search Form
	
	global $cfg_glpi,  $lang;

	$option["glpi_state_item.ID"]				= $lang["common"][2];
//	$option["glpi_reservation_item.device_type"]			= $lang["reservation"][3];
//	$option["glpi_dropdown_locations.name"]			= $lang["common"][15];
//	$option["glpi_software.version"]			= $lang["software"][5];
//      $option["glpi_state.comments"]			= $lang["common"][16];
	
	echo "<form method=\"get\" action=\"".$cfg_glpi["root_doc"]."/front/state.php\">";
	echo "<div align='center'><table class='tab_cadre' width='750'>";
	echo "<tr><th colspan='3'><b>".$lang["search"][0].":</b></th></tr>";
	echo "<tr class='tab_bg_1'>";
	echo "<td align='center'>";
	echo $lang["state"][0]." : &nbsp; &nbsp;";
	dropdownValue("glpi_dropdown_state","state",$state);
	echo "</td><td align='center'>";
	echo "<input type='text' size='15' name=\"contains\" value=\"". $contains ."\" />&nbsp;";
	echo $lang["search"][10];
	echo "&nbsp;<select name=\"field\" size='1'>";
        echo "<option value='all' ";
	if($field == "all") echo "selected";
	echo ">".$lang["search"][7]."</option>";
        reset($option);
	foreach ($option as $key => $val) {
		echo "<option value=\"".$key."\""; 
		if($key == $field) echo "selected";
		echo ">". $val ."</option>\n";
	}
	echo "</select>";
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

	global $db,$cfg_glpi, $lang, $HTMLRel;

	$state_type=$cfg_glpi["state_type"];

	foreach ($state_type as $key=>$type)
	if (!haveTypeRight($type,"r")) {
		unset($state_type[$key]);
	}
		

	// Build query
	if($field=="all") {
	$where=" 1 = 1 ";
	}
	else {
		$where = "($field ".makeTextSearch($contains).")";
	}
	if(!empty($state)) {
		$where .= " AND (glpi_state_item.state = '".$state."')";
	}
	
	$where.=" AND (";
	$first=true;
	foreach ($state_type as $val){
		if (!$first) $where.=" OR ";
		else $first=false;
		$where.=" glpi_state_item.device_type = $val ";
	}

	$where.=" )";

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
		if ($numrows > $cfg_glpi["list_limit"]&&!isset($_GET['export_all'])) {
			$query_limit = $query ." LIMIT $start,".$cfg_glpi["list_limit"]." ";
			$result_limit = $db->query($query_limit);
			$numrows_limit = $db->numrows($result_limit);
		} else {
			$numrows_limit = $numrows;
			$result_limit = $result;
		}
		
		if ($numrows_limit>0) {


			// Set display type for export if define
			$output_type=HTML_OUTPUT;
			if (isset($_GET["display_type"]))
				$output_type=$_GET["display_type"];

			// Pager
			$parameters="start=$start&amp;state=$state&amp;field=$field&amp;phrasetype=$phrasetype&amp;contains=$contains&amp;sort=$sort&amp;order=$order";
			if ($output_type==HTML_OUTPUT)
				printPager($start,$numrows,$target,$parameters,STATE_TYPE);

			$nbcols=6;
			// Display List Header
			echo displaySearchHeader($output_type,$numrows_limit+1,$nbcols);
			// New Line for Header Items Line
			echo displaySearchNewLine($output_type);
			
			$header_num=1;

			$linkto="$target?field=$field&amp;phrasetype=$phrasetype&amp;contains=$contains&amp;sort=glpi_state_item.ID&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start&amp;state=$state";
			echo displaySearchHeaderItem($output_type,$lang["common"][2],$header_num,$linkto,$sort=="glpi_state_item.ID",$order);

			$linkto="$target?field=$field&amp;phrasetype=$phrasetype&amp;contains=$contains&amp;sort=glpi_state_item.device_type&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start&amp;state=$state";
			echo displaySearchHeaderItem($output_type,$lang["state"][6],$header_num,$linkto,$sort=="glpi_state_item.device_type",$order);


			echo displaySearchHeaderItem($output_type,$lang["common"][16],$header_num,"",0,$order);

			echo displaySearchHeaderItem($output_type,$lang["common"][17],$header_num,"",0,$order);

			echo displaySearchHeaderItem($output_type,$lang["common"][15],$header_num,"",0,$order);

			$linkto="$target?field=$field&amp;phrasetype=$phrasetype&amp;contains=$contains&amp;sort=glpi_dropdown_state.name&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start&amp;state=$state";
			echo displaySearchHeaderItem($output_type,$lang["state"][0],$header_num,$linkto,$sort=="glpi_dropdown_state.name",$order);
			
			// End Line for column headers		
			echo displaySearchEndLine($output_type);

			// Num of the row (1=header_line)
			$row_num=1;
			for ($i=0; $i < $numrows_limit; $i++) {
				$data=$db->fetch_array($result_limit);
				$ri = new StateItem;
				$ri->getfromDB($data["d_type"],$data["id_device"]);


				// Column num
				$item_num=1;
				$row_num++;

				echo displaySearchNewLine($output_type);
				
				$deleted=isset($ri->obj->fields["deleted"])&&$ri->obj->fields["deleted"]=='Y';

				echo displaySearchItem($output_type,$ri->fields["ID"],$item_num,$row_num,$deleted);

				echo displaySearchItem($output_type,$ri->getType(),$item_num,$row_num,$deleted);

				echo displaySearchItem($output_type,"<strong>".$ri->getLink()."</strong>",$item_num,$row_num,$deleted);
				
				echo displaySearchItem($output_type,$ri->getItemType(),$item_num,$row_num,$deleted);

				echo displaySearchItem($output_type,getDropdownName("glpi_dropdown_locations",$ri->obj->fields["location"]),$item_num,$row_num,$deleted);

				echo displaySearchItem($output_type,getDropdownName("glpi_dropdown_state",$ri->fields["state"]),$item_num,$row_num,$deleted);

				// End Line
		        	echo displaySearchEndLine($output_type);
			}

			// Display footer
			echo displaySearchFooter($output_type);

			if ($output_type==HTML_OUTPUT) // In case of HTML display
				printPager($start,$numrows,$target,$parameters);

		} else {
			echo "<div align='center'><b>".$lang["state"][7]."</b></div>";
		}
	}
	
}

function updateState($device_type,$id_device, $state,$template=0,$dohistory=1){
global $db;

$si=new StateItem;

$where="";
if ($template==1)
$where= " AND is_template='1'";

$si->getFromDB($device_type,$id_device);

	if ($state!=$si->fields["state"])
	if ($si->fields["state"]!=-1){
		if ($state==0){
			$db->query("DELETE FROM glpi_state_item WHERE device_type='$device_type' and id_device='$id_device' $where;");
			
		
			
		
		}else{ $db->query("UPDATE glpi_state_item SET state='$state' WHERE device_type='$device_type' and id_device='$id_device' $where;");
		
		}
		if ($dohistory){
			$changes=array(31,addslashes(getDropdownName("glpi_dropdown_state",$si->fields["state"])), addslashes(getDropdownName( "glpi_dropdown_state",$state)));
			historyLog ($id_device,$device_type,$changes);
		}
	
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
global $db,$lang,$cfg_glpi;


$state_type=$cfg_glpi["state_type"];

foreach ($state_type as $key=>$type)
if (!haveTypeRight($type,"r")) {
	unset($state_type[$key]);
}

$query = "select glpi_dropdown_state.name as state, glpi_dropdown_state.ID as id_state, glpi_state_item.device_type as d_type, count(*) as cmp from glpi_state_item ";
$query.= " LEFT JOIN glpi_dropdown_state ON (glpi_dropdown_state.ID = glpi_state_item.state) ";
$query .= " WHERE glpi_state_item.is_template='0' GROUP BY glpi_state_item.device_type,glpi_dropdown_state.ID,glpi_dropdown_state.name ORDER BY glpi_dropdown_state.name,glpi_state_item.device_type";





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
			echo "<th>".$lang["common"][33]."</th>";
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
			echo "<tr class='tab_bg_2'><td align='center'><strong><a href='$target?state=0'>".$lang["common"][33]."</a></strong></td>";
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
