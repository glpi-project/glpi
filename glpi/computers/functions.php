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
// FUNCTIONS Computers
/**
* Print a good title for computer pages
*
*
*
*
*@return nothing (diplays)
*
**/
function titleComputers(){
              //titre
              
        GLOBAL  $lang,$HTMLRel;

         echo "<div align='center'><table border='0'><tr><td>";
         echo "<img src=\"".$HTMLRel."pics/computer.png\" alt='".$lang["computers"][0]."' title='".$lang["computers"][0]."'></td><td><a  class='icon_consol' href=\"computers-add-select.php\"><b>".$lang["computers"][0]."</b></a>";
         echo "</td>";
         echo "<td><a class='icon_consol' href='".$HTMLRel."setup/setup-templates.php?type=".COMPUTER_TYPE."'>".$lang["common"][8]."</a></td>";
         echo "</tr></table></div>";

}

/**
* Print "onglets" (on the top of items forms)
*
* Print "onglets" for a better navigation.
*
*@param $target filename : The php file to display then
*@param $withtemplate bool : template or basic computers
*@param $actif witch of all the "onglets" is selected
*
*@return nothing (diplays)
*
**/
function showComputerOnglets($target,$withtemplate,$actif){
	global $lang,$HTMLRel;
	
	$template="";
	if(!empty($withtemplate)){
		$template="&amp;withtemplate=$withtemplate";
	}
	echo "<div id='barre_onglets'><ul id='onglet'>";
	echo "<li "; if ($actif=="1"){ echo "class='actif'";} echo  "><a href='$target&amp;onglet=1$template'>".$lang["title"][26]."</a></li>";
	echo "<li "; if ($actif=="2") {echo "class='actif'";} echo "><a href='$target&amp;onglet=2$template'>".$lang["title"][12]."</a></li>";
	echo "<li "; if ($actif=="3") {echo "class='actif'";} echo "><a href='$target&amp;onglet=3$template'>".$lang["title"][27]."</a></li>";
	echo "<li "; if ($actif=="4") {echo "class='actif'";} echo "><a href='$target&amp;onglet=4$template'>".$lang["Menu"][26]."</a></li>";
	echo "<li "; if ($actif=="5") {echo "class='actif'";} echo "><a href='$target&amp;onglet=5$template'>".$lang["title"][25]."</a></li>";
	if(empty($withtemplate)){
	echo "<li "; if ($actif=="6") {echo "class='actif'";} echo "><a href='$target&amp;onglet=6$template'>".$lang["title"][28]."</a></li>";
	echo "<li "; if ($actif=="7") {echo "class='actif'";} echo "><a href='$target&amp;onglet=7$template'>".$lang["title"][34]."</a></li>";
	echo "<li class='invisible'>&nbsp;</li>";
	
	echo "<li "; if ($actif=="-1") {echo "class='actif'";} echo "><a href='$target&amp;onglet=-1$template'>".$lang["title"][29]."</a></li>";
	}
	echo "<li class='invisible'>&nbsp;</li>";
	
	if (empty($withtemplate)&&preg_match("/\?ID=([0-9]+)/",$target,$ereg)){
	$ID=$ereg[1];
	$next=getNextItem("glpi_computers",$ID);
	$prev=getPreviousItem("glpi_computers",$ID);
	$cleantarget=preg_replace("/\?ID=([0-9]+)/","",$target);
	if ($prev>0) echo "<li><a href='$cleantarget?ID=$prev'><img src=\"".$HTMLRel."pics/left.png\" alt='".$lang["buttons"][12]."' title='".$lang["buttons"][12]."'></a></li>";
	if ($next>0) echo "<li><a href='$cleantarget?ID=$next'><img src=\"".$HTMLRel."pics/right.png\" alt='".$lang["buttons"][11]."' title='".$lang["buttons"][11]."'></a></li>";
	}
	echo "</ul></div>";
	
}




/**
* Print search form for computers
*
* 
*
*@param $field='' array of the fields selected in the search form
*@param $contains='' array of the search strings
*@param $sort='' the "sort by" field value
*@param $deleted='' the deleted value 
*@param $link='' array of the link between each search.
*
*@return nothing (diplays)
*
**/
function searchFormComputers($field="",$contains="",$sort= "",$deleted= "",$link="") {
	// Print Search Form
	
	GLOBAL $cfg_install, $cfg_layout, $layout, $lang,$HTMLRel;

	$option["comp.ID"]				= $lang["computers"][31];
	$option["comp.name"]				= $lang["computers"][7];
	$option["glpi_dropdown_locations.name"]		= $lang["computers"][10];
	$option["glpi_type_computers.name"]		= $lang["computers"][8];
	$option["glpi_dropdown_model.name"]		= $lang["computers"][50];
	$option["glpi_dropdown_os.name"]		= $lang["computers"][9];
	$option["processor.designation"]		= $lang["computers"][21];
	$option["comp.serial"]				= $lang["computers"][17];
	$option["comp.otherserial"]			= $lang["computers"][18];
	$option["ram.designation"]			= $lang["computers"][23];
	$option["iface.designation"]			= $lang["computers"][26];
	$option["sndcard.designation"]			= $lang["computers"][33];
	$option["gfxcard.designation"]			= $lang["computers"][34];
	$option["moboard.designation"]			= $lang["computers"][35];
	$option["hdd.designation"]			= $lang["computers"][36];
	$option["comp.comments"]			= $lang["computers"][19];
	$option["comp.contact"]				= $lang["computers"][16];
	$option["comp.contact_num"]		        = $lang["computers"][15];
	$option["comp.date_mod"]			= $lang["computers"][11];
	$option["glpi_networking_ports.ifaddr"]		= $lang["networking"][14];
	$option["glpi_networking_ports.ifmac"]		= $lang["networking"][15];
	$option["glpi_dropdown_netpoint.name"]		= $lang["networking"][51];
	$option["glpi_enterprises.name"]		= $lang["common"][5];
	$option["resptech.name"]			= $lang["common"][10];
	$option=addInfocomOptionFieldsToResearch($option);
	$option=addContractOptionFieldsToResearch($option);

	
	echo "<form method=get action=\"".$cfg_install["root"]."/computers/computers-search.php\">";
	echo "<div align='center'><table border='0' width='850' class='tab_cadre'>";
	echo "<tr><th colspan='4'><b>".$lang["search"][0].":</b></th></tr>";
	echo "<tr class='tab_bg_1'>";
	echo "<td align='center'>";
	echo "<table>";
	
	for ($i=0;$i<$_SESSION["glpisearchcount"];$i++){
		echo "<tr><td align='right'>";
		if ($i==0){
			echo "<a href='".$cfg_install["root"]."/computers/computers-search.php?add_search_count=1'><img src=\"".$HTMLRel."pics/plus.png\" alt='+'></a>&nbsp;&nbsp;&nbsp;&nbsp;";
			if ($_SESSION["glpisearchcount"]>1)
			echo "<a href='".$cfg_install["root"]."/computers/computers-search.php?delete_search_count=1'><img src=\"".$HTMLRel."pics/moins.png\" alt='-'></a>&nbsp;&nbsp;&nbsp;&nbsp;";
		}
		if ($i>0) {
			echo "<select name='link[$i]'>";
			
			echo "<option value='AND' ";
			if(is_array($link)&&isset($link[$i]) && $link[$i] == "AND") echo "selected";
			echo ">AND</option>";
			
			echo "<option value='OR' ";
			if(is_array($link)&&isset($link[$i]) && $link[$i] == "OR") echo "selected";
			echo ">OR</option>";		

			echo "<option value='AND NOT' ";
			if(is_array($link)&&isset($link[$i]) && $link[$i] == "AND NOT") echo "selected";
			echo ">AND NOT</option>";		
			
			echo "<option value='OR NOT' ";
			if(is_array($link)&&isset($link[$i]) && $link[$i] == "OR NOT") echo "selected";
			echo ">OR NOT</option>";
			
			echo "</select>";
		}
		
		echo "<input type='text' size='15' name=\"contains[$i]\" value=\"". (is_array($contains)&&isset($contains[$i])?stripslashes($contains[$i]):"" )."\" >";
		echo "&nbsp;";
		echo $lang["search"][10]."&nbsp;";
	
		echo "<select name=\"field[$i]\" size='1'>";
        	echo "<option value='all' ";
		if(is_array($field)&&isset($field[$i]) && $field[$i] == "all") echo "selected";
		echo ">".$lang["search"][7]."</option>";
        	reset($option);
		foreach ($option as $key => $val) {
			echo "<option value=\"".$key."\""; 
			if(is_array($field)&&isset($field[$i]) && $key == $field[$i]) echo "selected";
			echo ">". $val ."</option>\n";
		}
		echo "</select>&nbsp;";

		
		echo "</td></tr>";
	}
	echo "</table>";
	echo "</td>";

	echo "<td>";
	echo $lang["search"][4];
	echo "&nbsp;<select name='sort' size='1'>";
	reset($option);
	foreach ($option as $key => $val) {
		echo "<option value=\"".$key."\"";
		if($key == $sort) echo "selected";
		echo ">".$val."</option>\n";
	}
	echo "</select> ";
	echo "</td>";
	
	echo "<td><input type='checkbox' name='deleted' ".($deleted=='Y'?" checked ":"").">";
	echo "<img src=\"".$HTMLRel."pics/showdeleted.png\" alt='".$lang["common"][3]."' title='".$lang["common"][3]."'>";
	echo "</td><td width='80' align='center' class='tab_bg_2'>";
	echo "<input type='submit' value=\"".$lang["buttons"][0]."\" class='submit' >";
	echo "</td></tr></table></div></form>";
}

/**
* Test if a field is a dropdown
*
* Return true if the field $field is a dropdown 
* or false if not.
*
*@param $field string field name
*
*
*@return bool
*
**/
function IsDropdown($field) {
	$dropdown = array("netpoint","os","model");
	if(in_array($field,$dropdown)) {
		return true;
	}
	else  {
		return false;
	}
}
/**
* Test if a field is a device
*
* Return true if the field $field is a device 
* or false if not.
*
*@param $field string device name
*
*
*@return bool
*
**/
function IsDevice($field) {
	global $cfg_devices_tables;
	if(in_array($field,$cfg_devices_tables)) {
		return true;
	}
	else  {
		return false;
	}
}
/**
* Search and list computers
*
*
* Build the query, make the search and list computers after a search.
*
*@param $target filename where to go when done.
*@param $username not used to be deleted.
*@param $field array of fields in witch the search would be done
*@param $contains array of the search strings
*@param $sort the "sort by" field value
*@param $order ASC or DSC (for mysql query)
*@param $start row number from witch we start the query (limit $start,xxx)
*@param $deleted Query on deleted items or not.
*@param $link array of the link between each search.
*
*
*@return Nothing (display)
*
**/
function showComputerList($target,$username,$field,$contains,$sort,$order,$start,$deleted,$link) {

	$db = new DB;
	// Lists Computers

	GLOBAL $cfg_install, $cfg_layout, $cfg_features, $lang,$HTMLRel, $cfg_devices_tables;

	$where ="";
	
	foreach ($field as $k => $f)
	if ($k<$_SESSION["glpisearchcount"])
	if ($contains[$k]==""){
		if ($k>0) $where.=" ".$link[$k]." ";
		$where.=" ('1'='1') ";
		}
	else {
		if ($k>0) $where.=" ".$link[$k]." ";
		$where.="( ";
		// Build query
		if($f == "all") {
		
			$query = "SHOW COLUMNS FROM glpi_computers";
			$result = $db->query($query);
			$i = 0;
			while($line = $db->fetch_array($result)) {
				if($i != 0) {
					$where .= " OR ";
				}
				if(IsDropdown($line["Field"])) {
					$where .= " glpi_dropdown_". $line["Field"] .".name LIKE '%".$contains[$k]."%'";
				}
				elseif($line["Field"] == "location") {
					$where .= getRealSearchForTreeItem("glpi_dropdown_locations",$contains[$k]);
				}
				elseif($line["Field"] == "FK_glpi_enterprise") {
					$where .= "glpi_enterprises.name LIKE '%".$contains[$k]."%'";
				}
				elseif ($line["Field"]=="tech_num"){
					$where .= " resptech.name LIKE '%".$contains[$k]."%'";
				} 
				else {
   					$where .= "comp.".$line["Field"] . " LIKE '%".$contains[$k]."%'";
				}
				$i++;
			}
			foreach($cfg_devices_tables as $key => $val) {
				//Hack pour ne pas avoir un "case" dans la requete (mot clé).
				if(strcmp($val,"case") == 0) $val = "Tcase";
				$where .= " OR ".$val.".designation LIKE '%".$contains[$k]."%'";
			}
			$where .= " OR glpi_networking_ports.ifaddr LIKE '%".$contains[$k]."%'";
			$where .= " OR glpi_networking_ports.ifmac LIKE '%".$contains[$k]."%'";
			$where .= " OR glpi_dropdown_netpoint.name LIKE '%".$contains[$k]."%'";
			$where .= " OR glpi_type_computers.name LIKE '%".$contains[$k]."%'";
			$where .= getInfocomSearchToViewAllRequest($contains[$k]);
			$where .= getContractSearchToViewAllRequest($contains[$k]);
		}
		else {
			if(IsDevice($f)) {
				$where .= "glpi_device_".$f." LIKE '%".$contains[$k]."'";
			}
			else if ($f=="glpi_dropdown_locations.name"){
				$where .= getRealSearchForTreeItem("glpi_dropdown_locations",$contains[$k]);
			}
			else {
				$where .= "$f LIKE '%".$contains[$k]."%'";
			}
		}
	$where.=" )";
	}
	
	
	if (!$start) {
		$start = 0;
	}
	if (!$order) {
		$order = "ASC";
	}
	$query = "select DISTINCT comp.ID from glpi_computers as comp LEFT JOIN glpi_computer_device as gcdev ON (comp.ID = gcdev.FK_computers) ";
	$query.= " LEFT JOIN glpi_device_moboard as moboard ON (moboard.ID = gcdev.FK_device AND gcdev.device_type = '".MOBOARD_DEVICE."') ";
	$query.= " LEFT JOIN glpi_device_processor as processor ON (processor.ID = gcdev.FK_device AND gcdev.device_type = '".PROCESSOR_DEVICE."') ";
	$query.= " LEFT JOIN glpi_device_gfxcard as gfxcard ON (gfxcard.ID = gcdev.FK_DEVICE AND gcdev.device_type = '".GFX_DEVICE."') ";
	$query.= " LEFT JOIN glpi_device_hdd as hdd ON (hdd.ID = gcdev.FK_DEVICE AND gcdev.device_type = '".HDD_DEVICE."') ";
	$query.= " LEFT JOIN glpi_device_iface as iface ON (iface.ID = gcdev.FK_DEVICE AND gcdev.device_type = '".NETWORK_DEVICE."') ";
	$query.= " LEFT JOIN glpi_device_ram as ram ON (ram.ID = gcdev.FK_DEVICE AND gcdev.device_type = '".RAM_DEVICE."') ";
	$query.= " LEFT JOIN glpi_device_sndcard as sndcard ON (sndcard.ID = gcdev.FK_DEVICE AND gcdev.device_type = '".SND_DEVICE."') ";
	$query.= " LEFT JOIN glpi_device_drive as drive ON (drive.ID = gcdev.FK_DEVICE AND gcdev.device_type = '".DRIVE_DEVICE."') ";
	$query.= " LEFT JOIN glpi_device_control as control ON (control.ID = gcdev.FK_DEVICE AND gcdev.device_type = '".CONTROL_DEVICE."') ";
	$query.= " LEFT JOIN glpi_device_pci as pci ON (pci.ID = gcdev.FK_DEVICE AND gcdev.device_type = '".PCI_DEVICE."') ";
	$query.= " LEFT JOIN glpi_device_case as Tcase ON (Tcase.ID = gcdev.FK_DEVICE AND gcdev.device_type = '".CASE_DEVICE."') ";
	$query.= " LEFT JOIN glpi_device_power as power ON (power.ID = gcdev.FK_DEVICE AND gcdev.device_type = '".POWER_DEVICE."') ";
	$query.= " LEFT JOIN glpi_networking_ports on (comp.ID = glpi_networking_ports.on_device AND  glpi_networking_ports.device_type='".COMPUTER_TYPE."') ";
	$query.= " LEFT JOIN glpi_dropdown_netpoint on (glpi_dropdown_netpoint.ID = glpi_networking_ports.netpoint) ";
	$query.= " LEFT JOIN glpi_dropdown_os on (glpi_dropdown_os.ID = comp.os) ";
	$query.= " LEFT JOIN glpi_dropdown_locations on (glpi_dropdown_locations.ID = comp.location) ";
	$query.= " LEFT JOIN glpi_dropdown_model on (glpi_dropdown_model.ID = comp.model) ";
	$query.= " LEFT JOIN glpi_enterprises ON (glpi_enterprises.ID = comp.FK_glpi_enterprise ) ";
	$query.= " LEFT JOIN glpi_users as resptech ON (resptech.ID = comp.tech_num ) ";
	$query.= " LEFT JOIN glpi_type_computers ON (glpi_type_computers.ID = comp.type ) ";
	$query.= getInfocomSearchToRequest("comp",COMPUTER_TYPE);
	$query.= getContractSearchToRequest("comp",COMPUTER_TYPE);

	$query.= " where ";
	if (!empty($where)) $query .= " $where AND ";
	$query .= " comp.deleted='$deleted' AND comp.is_template = '0'  ORDER BY $sort $order";

	// Get it from database	
	if ($result = $db->query($query)) {
		$numrows= $db->numrows($result);

		// Limit the result, if no limit applies, use prior result
		if ($numrows>$cfg_features["list_limit"]) {
			$query_limit = $query. " LIMIT $start,".$cfg_features["list_limit"]." ";
			$result_limit = $db->query($query_limit);
			$numrows_limit = $db->numrows($result_limit);
		} else {
			$numrows_limit = $numrows;
			$result_limit = $result;
		}
		
		if ($numrows_limit>0) {

			// Pager
			$parameters="sort=$sort&amp;order=$order".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains);
			printPager($start,$numrows,$target,$parameters);

			// Produce headline
			echo "<div align='center'><table border='0' class='tab_cadre'><tr>";

			// Name
			echo "<th>";
			if ($sort=="comp.name") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?sort=comp.name&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains)."\">";
			echo $lang["computers"][7]."</a></th>";
		
			// State		
			echo "<th>".$lang["state"][0]."</th>";
			
			
			// Manufacturer		
			echo "<th>";
			if ($sort=="glpi_enterprises.name") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?sort=glpi_enterprises.name&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains)."\">";
			echo $lang["common"][5]."</a></th>";
			
		        // Serial
			echo "<th>";
			if ($sort=="comp.serial") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?sort=comp.serial&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains)."\">";
			echo $lang["computers"][6]."</a></th>";
		

			// Type
			echo "<th>";
			if ($sort=="glpi_type_computers.name") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?sort=glpi_type_computers.name&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains)."\">";
			echo $lang["computers"][8]."</a></th>";

						// Type
			echo "<th>";
			if ($sort=="glpi_dropdown_model.name") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?sort=glpi_dropdown_model.name&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains)."\">";
			echo $lang["computers"][50]."</a></th>";

			// OS
			echo "<th>";
			if ($sort=="glpi_dropdown_os.name") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?sort=glpi_dropdown_os.name&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains)."\">";
			echo $lang["computers"][9]."</a></th>";

			// Location			
			echo "<th>";
			if ($sort=="glpi_dropdown_locations.completename") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?sort=glpi_dropdown_locations.completename&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains)."\">";
			echo $lang["computers"][10]."</a></th>";

			// Last modified		
			echo "<th>";
			if ($sort=="date_mod") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?sort=date_mod&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains)."\">";
			echo $lang["computers"][11]."</a></th>";

			// Contact person
			echo "<th>";
			if ($sort=="contact") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?sort=contact&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains)."\">";
			echo $lang["computers"][16]."</a></th>";

			echo "</tr>";

			for ($i=0; $i < $numrows_limit; $i++) {
				$ID = $db->result($result_limit, $i, "ID");
				$comp = new Computer;
				$comp->getfromDB($ID,0);
				$state=new StateItem;
				$state->getfromDB(COMPUTER_TYPE,$ID);
				
				echo "<tr class='tab_bg_2'>";
				echo "<td><b>";
				echo "<a href=\"".$cfg_install["root"]."/computers/computers-info-form.php?ID=$ID\">";
				echo $comp->fields["name"]." (".$comp->fields["ID"].")";
				echo "</a></b></td>";
				echo "<td>".getDropdownName("glpi_dropdown_state",$state->fields["state"])."</td>";
				echo "<td>". getDropdownName("glpi_enterprises",$comp->fields["FK_glpi_enterprise"]) ."</td>";
				echo "<td>".$comp->fields["serial"]."</td>";
                                echo "<td>". getDropdownName("glpi_type_computers",$comp->fields["type"]) ."</td>";
				echo "<td>". getDropdownName("glpi_dropdown_model",$comp->fields["model"]) ."</td>";
				echo "<td>". getDropdownName("glpi_dropdown_os",$comp->fields["os"]) ."</td>";
				echo "<td>". getDropdownName("glpi_dropdown_locations", $comp->fields["location"]) ."</td>";
				echo "<td>".$comp->fields["date_mod"]."</td>";
				echo "<td>".$comp->fields["contact"]."</td>";
                                
                                echo "</tr>";
			}

			// Close Table
			echo "</table></div>";

			// Pager
//			$parameters="sort=$sort&amp;order=$order".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains);
			echo "<br>";
			printPager($start,$numrows,$target,$parameters);

		} else {
			echo "<div align='center'><b>".$lang["computers"][32]."</b></div>";
			
		}
	}
}
/**
* Print the computer form
*
*
* Print général computer form
*
*@param $target filename : where to go when done.
*@param $ID Integer : Id of the computer or the template to print
*@param $withtemplate='' boolean : template or basic computer
*
*
*@return Nothing (display)
*
**/
function showComputerForm($target,$ID,$withtemplate='') {
	global $lang,$HTMLRel,$cfg_layout;
	$comp = new Computer;
	$computer_spotted = false;
	if(empty($ID) && $withtemplate == 1) {
		if($comp->getEmpty()) $computer_spotted = true;
	} else {
		if($comp->getfromDB($ID)) $computer_spotted = true;
	}
	if($computer_spotted) {
		if(!empty($withtemplate) && $withtemplate == 2) {
			$template = "newcomp";
			$datestring = $lang["computers"][14].": ";
			$date = date("Y-m-d H:i:s");
		} elseif(!empty($withtemplate) && $withtemplate == 1) { 
			$template = "newtemplate";
			$datestring = $lang["computers"][14].": ";
			$date = date("Y-m-d H:i:s");
		} else {
			$datestring = $lang["computers"][11].": ";
			$date = $comp->fields["date_mod"];
			$template = false;
		}
		
		echo "<form name='form' method='post' action=\"$target\">";
		echo "<div align='center'>";
		echo "<table width='800' class='tab_cadre' >";
		
		if(strcmp($template,"newtemplate") === 0) {
			echo "<input type=\"hidden\" name=\"is_template\" value=\"1\" />";
		}
		
		echo "<tr><th colspan ='2' align='center' >";
		if(!$template) {
			echo $lang["computers"][13].": ".$comp->fields["ID"];
		}elseif (strcmp($template,"newcomp") === 0) {
			echo $lang["computers"][12].": ".$comp->fields["tplname"];
		}elseif (strcmp($template,"newtemplate") === 0) {
			echo $lang["common"][6]."&nbsp;: <input type='text' name='tplname' value=\"".$comp->fields["tplname"]."\" size='20'>";
		}
		
		echo "</th><th  colspan ='2' align='center'>".$datestring.$date;
		echo "</th></tr>";
		
		echo "<tr class='tab_bg_1'><td>".$lang["computers"][7].":		</td>";
		echo "<td><input type='text' name='name' value=\"".$comp->fields["name"]."\" size='20'></td>";
						
		echo "<td>".$lang["computers"][16].":	</td><td><input type='text' name='contact' size='20' value=\"".$comp->fields["contact"]."\">";
		echo "</td></tr>";
		
		echo "<tr class='tab_bg_1'>";
				echo "<td >".$lang["computers"][8].": 	</td>";
		echo "<td >";
			dropdownValue("glpi_type_computers", "type", $comp->fields["type"]);
		
		echo "</td>";

		
		
		echo "<td>".$lang["computers"][15].":		</td><td><input type='text' name='contact_num' value=\"".$comp->fields["contact_num"]."\" size='20'></td></tr>";

		echo "<tr class='tab_bg_1'>";
		echo "<td >".$lang["computers"][10].": 	</td>";
		echo "<td >";
			dropdownValue("glpi_dropdown_locations", "location", $comp->fields["location"]);
		
		echo "</td>";
		
		
		echo "<td>".$lang["setup"][88].":</td><td>";
		dropdownValue("glpi_dropdown_network", "network", $comp->fields["network"]);
		echo "</td></tr>";

		echo "<tr class='tab_bg_1'>";
		echo "<td >".$lang["computers"][50].": 	</td>";
		echo "<td >";
			dropdownValue("glpi_dropdown_model", "model", $comp->fields["model"]);
		
		echo "</td>";
		
		
		echo "<td>".$lang["setup"][89].":</td><td>";
		dropdownValue("glpi_dropdown_domain", "domain", $comp->fields["domain"]);
		echo "</td></tr>";
		

		echo "<tr class='tab_bg_1'>";
		echo "<td >".$lang["common"][10].": 	</td>";
		echo "<td >";
			dropdownUsersID( $comp->fields["tech_num"],"tech_num");
		echo "</td>";

		echo "<td valign='middle' rowspan='3'>".$lang["computers"][19].":</td><td valign='middle' rowspan='3'><textarea  cols='35' rows='4' name='comments' >".$comp->fields["comments"]."</textarea></td></tr>";

		echo "<tr class='tab_bg_1'>";
		echo "<td>".$lang["common"][5].": 	</td><td>";
		dropdownValue("glpi_enterprises","FK_glpi_enterprise",$comp->fields["FK_glpi_enterprise"]);
		echo "</td></tr>";

		echo "<tr class='tab_bg_1'><td>".$lang["computers"][17].":	</td>";
		echo "<td><input type='text' name='serial' size='20' value=\"".$comp->fields["serial"]."\">";
		echo "</td></tr>";

		
		echo "<tr class='tab_bg_1'>";
		
		echo "<td>".$lang["computers"][18].":	</td>";
		echo "<td><input type='text' size='20' name='otherserial' value=\"".$comp->fields["otherserial"]."\">";
		echo "</td>";

		
		echo "<td>".$lang["state"][0].":</td><td>";
		$si=new StateItem();
		$t=0;
		if ($template) $t=1;
		$si->getfromDB(COMPUTER_TYPE,$comp->fields["ID"],$t);
		dropdownValue("glpi_dropdown_state", "state",$si->fields["state"]);
		echo "</td></tr>";


		echo "<tr class='tab_bg_1'>";
		
		echo "<td>".$lang["computers"][9].":</td><td>";
		dropdownValue("glpi_dropdown_os", "os", $comp->fields["os"]);
		echo "</td>";
		
		if (!$template){
		echo "<td>".$lang["reservation"][24].":</td><td><b>";
		showReservationForm(COMPUTER_TYPE,$ID);
		echo "</b></td>";
		} else echo "<td>&nbsp;</td><td>&nbsp;</td>";
		
		
		
		
		
		echo "</tr><tr>";
		if ($template) {
			if (empty($ID)||$withtemplate==2){
			echo "<td class='tab_bg_2' align='center' colspan='4'>\n";
			echo "<input type='hidden' name='ID' value=$ID>";
			echo "<input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'>";
			echo "</td>\n";
			} else {
			echo "<td class='tab_bg_2' align='center' colspan='4'>\n";
			echo "<input type='hidden' name='ID' value=$ID>";
			echo "<input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'>";
			echo "</td>\n";
			}
		} else {
			echo "<td class='tab_bg_2' colspan='2' align='center' valign='top'>\n";
			echo "<input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'>";
			echo "</td>\n";
                        echo "<td class='tab_bg_2' colspan='2'  align='center'>\n";
			echo "<input type='hidden' name='ID' value=$ID>";
		echo "<div align='center'>";
		if ($comp->fields["deleted"]=='N')
		echo "<input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'>";
		else {
		echo "<input type='submit' name='restore' value=\"".$lang["buttons"][21]."\" class='submit'>";
		
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' name='purge' value=\"".$lang["buttons"][22]."\" class='submit'>";
		}
		echo "</div>";
			echo "</td>";
		}

		echo "</tr>\n";
		
		
		
		echo "</table>";
		echo "</div>";
	echo "</form>";
		
		
		return true;
	}
	else {
         echo "<div align='center'><b>".$lang["computers"][32]."</b></div>";
         echo "<hr noshade>";
         searchFormComputers();
         return false;
        }
}
/**
* Print the form for devices linked to a computer or a template
*
*
* Print the form for devices linked to a computer or a template 
*
*@param $target filename : where to go when done.
*@param $ID Integer : Id of the computer or the template to print
*@param $withtemplate='' boolean : template or basic computer
*
*
*@return Nothing (display)
*
**/
function showDeviceComputerForm($target,$ID,$withtemplate='') {
	global $lang;
	$comp = new Computer;
	if(empty($ID) && $withtemplate == 1) {
		$comp->getEmpty();
	} else {
		$comp->getfromDB($ID,1);
	}

	if (!empty($ID)){
			//print devices.
		echo "<div align='center'>";
		echo "<form name='form_device_action' action=\"$target\" method=\"post\" >";
		echo "<input type='hidden' name='ID' value='$ID'>";	
		echo "<input type='hidden' name='device_action' value='$ID'>";			
		echo "<table width='800' class='tab_cadre' >";
		echo "<tr><th colspan='66'>".$lang["devices"][10]."</th></tr>";
		foreach($comp->devices as $key => $val) {
			$devType = $val["devType"];
			$devID = $val["devID"];
			$specif = $val["specificity"];
			$compDevID = $val["compDevID"];
			$device = new Device($devType);
			$device->getFromDB($devID);
			printDeviceComputer($device,$specif,$comp->fields["ID"],$compDevID,$withtemplate);
			
		}
		echo "</table>";

		echo "</form>";
		//ADD a new device form.
		device_selecter($_SERVER["PHP_SELF"],$comp->fields["ID"],$withtemplate);
		echo "</div>";
	}	


}
/**
* Update some elements of a computer in the database.
*
* Update some elements of a computer in the database.
*
*@param $input array : the _POST vars returned bye the computer form when press update (see showcomputerform())
*
*
*@return Nothing (call to the class member Computers->updateInDB )
*
**/
function updateComputer($input) {
	// Update a computer in the database

	$comp = new Computer;
	$comp->getFromDB($input["ID"],0);

	// set new date and make sure it gets updated
	$updates[0]= "date_mod";
	$comp->fields["date_mod"] = date("Y-m-d H:i:s");

	// Get all flags and fill with 0 if unchecked in form
	foreach  ($comp->fields as $key => $val) {
		if (eregi("\.*flag\.*",$key)) {
			if (empty($input[$key])) {
				$input[$key]=0;
			}
		}
	}
	
	// Fill the update-array with changes
	$x=1;
	foreach ($input as $key => $val) {
		if (array_key_exists($key,$comp->fields) && $comp->fields[$key]  != $input[$key]) {
			$comp->fields[$key] = $input[$key];
			$updates[$x] = $key;
			$x++;
		}
	}
	
	if (isset($input["is_template"])&&$input["is_template"]==1)
	updateState(COMPUTER_TYPE,$input["ID"],$input["state"],1);
	else updateState(COMPUTER_TYPE,$input["ID"],$input["state"]);
	$comp->updateInDB($updates);
	
}
/**
* Add a computer in the database.
*
* Add a computer in the database with all it's items.
*
*@param $input array : the _POST vars returned bye the computer form when press add(see showcomputerform())
*
*
*@return Nothing (call to classes members)
*
**/
function addComputer($input) {
	// Add Computer
	$db=new DB;
	$comp = new Computer;
	
  	// set new date.
   	$comp->fields["date_mod"] = date("Y-m-d H:i:s");
   
	// dump status
	$oldID=$input["ID"];
	$null=array_pop($input);
	$null=array_pop($input);
	$null=array_pop($input);
	
	// Manage state
	$state=$input["state"];
	unset($input["state"]);
	$i=0;
	
	// fill array for update
	foreach ($input as $key => $val){
	if ($key[0]!='_'&&(!isset($comp->fields[$key]) || $comp->fields[$key] != $input[$key])) {
			$comp->fields[$key] = $input[$key];
		}		
	}
	$newID=$comp->addToDB();
	
	// Add state
	if (isset($input["is_template"])&&$input["is_template"]==1)
	updateState(COMPUTER_TYPE,$newID,$state,1);
	else updateState(COMPUTER_TYPE,$newID,$state);
	
	// ADD Devices
	$comp->getFromDB($oldID);
	foreach($comp->devices as $key => $val) {
			compdevice_add($newID,$val["devType"],$val["devID"],$val["specificity"]);
		}
	
	// ADD Infocoms
	$ic= new Infocom();
	if ($ic->getFromDB(COMPUTER_TYPE,$oldID)){
		$ic->fields["FK_device"]=$newID;
		unset ($ic->fields["ID"]);
		$ic->addToDB();
	}
	
	// ADD software
	$query="SELECT license from glpi_inst_software WHERE cID='$oldID'";
	$result=$db->query($query);
	if ($db->numrows($result)>0){
		
		while ($data=$db->fetch_array($result))
			installSoftware($newID,$data['license']);
	}
	
	// ADD Contract				
	$query="SELECT FK_contract from glpi_contract_device WHERE FK_device='$oldID' AND device_type='".COMPUTER_TYPE."';";
	$result=$db->query($query);
	if ($db->numrows($result)>0){
		
		while ($data=$db->fetch_array($result))
			addDeviceContract($data["FK_contract"],COMPUTER_TYPE,$newID);
	}

	// ADD Documents			
	$query="SELECT FK_doc from glpi_doc_device WHERE FK_device='$oldID' AND device_type='".COMPUTER_TYPE."';";
	$result=$db->query($query);
	if ($db->numrows($result)>0){
		
		while ($data=$db->fetch_array($result))
			addDeviceDocument($data["FK_doc"],COMPUTER_TYPE,$newID);
	}
	
	// ADD Ports
	$query="SELECT ID from glpi_networking_ports WHERE on_device='$oldID' AND device_type='".COMPUTER_TYPE."';";
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
		
	return $newID;
}
/**
* Delete a computer in the database.
*
* Delete a computer in the database.
*
*@param $input array : the _POST vars returned bye the computer form when press delete(see showcomputerform())
*@param $force=0 int : how far the computer is deleted (moved to trash or purged from db).
*
*@return Nothing ()
*
**/
function deleteComputer($input,$force=0) {
	// Delete Computer

	$comp = new Computer;
	$comp->deleteFromDB($input["ID"],$force);
} 	
/**
* Restore a computer trashed in the database.
*
* Restore a computer trashed in the database.
*
*@param $input array : the _POST vars returned bye the computer form when press restore(see showcomputerform())
*
*@return Nothing ()
*
**/
function restoreComputer($input) {
	// Restore Computer
	
	$ct = new Computer;
	$ct->restoreInDB($input["ID"]);
} 

/**
* Print the computers or template local connections form. 
*
* Print the form for computers or templates connections to printers, screens or peripherals
*
*@param $ID integer: Computer or template ID
*@param $withtemplate=''  boolean : Template or basic item.
*
*@return Nothing (call to classes members)
*
**/
function showConnections($ID,$withtemplate='') {

	GLOBAL $cfg_layout, $cfg_install, $lang;

	$db = new DB;
	
	$state=new StateItem();

	echo "&nbsp;<div align='center'><table border='0' width='90%' class='tab_cadre'>";
	echo "<tr><th colspan='3'>".$lang["connect"][0].":</th></tr>";
	echo "<tr><th>".$lang["computers"][39].":</th><th>".$lang["computers"][40].":</th><th>".$lang["computers"][46].":</th></tr>";

	echo "<tr class='tab_bg_1'>";

	// Printers
	echo "<td align='center'>";
	$query = "SELECT * from glpi_connect_wire WHERE end2='$ID' AND type='".PRINTER_TYPE."'";
	if ($result=$db->query($query)) {
		$resultnum = $db->numrows($result);
		if ($resultnum>0) {
			echo "<table width='100%'>";
			for ($i=0; $i < $resultnum; $i++) {
				$tID = $db->result($result, $i, "end1");
				$connID = $db->result($result, $i, "ID");
				$printer = new Printer;
				$printer->getfromDB($tID);
				
				echo "<tr ".($printer->fields["deleted"]=='Y'?"class='tab_bg_2_2'":"").">";
				echo "<td align='center'><a href=\"".$cfg_install["root"]."/printers/printers-info-form.php?ID=$tID\"><b>";
				echo $printer->fields["name"]." (".$printer->fields["ID"].")";
				echo "</b></a>";
				if ($state->getfromDB(PRINTER_TYPE,$tID))
				echo " - ".getDropdownName("glpi_dropdown_state",$state->fields['state']);

				echo "</td>";
				if(!empty($withtemplate) && $withtemplate == 2) {
					//do nothing
				} else {
					echo "<td align='center'><a href=\"".$cfg_install["root"]."/computers/computers-info-form.php?cID=$ID&amp;ID=$connID&amp;disconnect=1amp;withtemplate=".$withtemplate."\"><b>";
					echo $lang["buttons"][10];
					echo "</b></a></td>";
				}
				echo "</tr>";
			}
			echo "</table>";
		} else {
			echo $lang["computers"][38]."<br>";
		}
		if(!empty($withtemplate) && $withtemplate == 2) {
			//do nothing
		} else {
			echo "<a href=\"".$cfg_install["root"]."/computers/computers-info-form.php?ID=$ID&amp;connect=1&amp;device_type=printer&amp;withtemplate=".$withtemplate."\"><b>";
			echo $lang["buttons"][9];
			echo "</b></a>";
		}

	}
	echo "</td>";

	// Monitors
	echo "<td align='center'>";
	$query = "SELECT * from glpi_connect_wire WHERE end2='$ID' AND type='".MONITOR_TYPE."'";
	if ($result=$db->query($query)) {
		$resultnum = $db->numrows($result);
		if ($resultnum>0) {
			echo "<table width='100%'>";
			for ($i=0; $i < $resultnum; $i++) {
				$tID = $db->result($result, $i, "end1");
				$connID = $db->result($result, $i, "ID");
				$monitor = new Monitor;
				$monitor->getfromDB($tID);
				echo "<tr ".($monitor->fields["deleted"]=='Y'?"class='tab_bg_2_2'":"").">";
				echo "<td align='center'><a href=\"".$cfg_install["root"]."/monitors/monitors-info-form.php?ID=$tID\"><b>";
				echo $monitor->fields["name"]." (".$monitor->fields["ID"].")";
				echo "</b></a>";
				if ($state->getfromDB(MONITOR_TYPE,$tID))
				echo " - ".getDropdownName("glpi_dropdown_state",$state->fields['state']);
				
				echo "</td>";
				if(!empty($withtemplate) && $withtemplate == 2) {
					//do nothing
				} else {
					echo "<td align='center'><a href=\"".$cfg_install["root"]."/computers/computers-info-form.php?cID=$ID&amp;ID=$connID&amp;disconnect=1&amp;withtemplate=".$withtemplate."\"><b>";
					echo $lang["buttons"][10];
					echo "</b></a></td>";
				}
				echo "</tr>";
			}
			echo "</table>";			
		} else {
			echo $lang["computers"][37]."<br>";
		}
		if(!empty($withtemplate) && $withtemplate == 2) {
			//do nothing
		} else {
			echo "<a href=\"".$cfg_install["root"]."/computers/computers-info-form.php?ID=$ID&amp;connect=1&amp;device_type=monitor&amp;withtemplate=".$withtemplate."\"><b>";
			echo $lang["buttons"][9];
			echo "</b></a>";
		}

	}
	echo "</td>";
	
	//Peripherals
	echo "<td align='center'>";
	$query = "SELECT * from glpi_connect_wire WHERE end2='$ID' AND type='".PERIPHERAL_TYPE."'";
	if ($result=$db->query($query)) {
		$resultnum = $db->numrows($result);
		if ($resultnum>0) {
			echo "<table width='100%'>";
			for ($i=0; $i < $resultnum; $i++) {
				$tID = $db->result($result, $i, "end1");
				$connID = $db->result($result, $i, "ID");
				$periph = new Peripheral;
				$periph->getfromDB($tID);
				echo "<tr ".($periph->fields["deleted"]=='Y'?"class='tab_bg_2_2'":"").">";
				echo "<td align='center'><a href=\"".$cfg_install["root"]."/peripherals/peripherals-info-form.php?ID=$tID\"><b>";
				echo $periph->fields["name"]." (".$periph->fields["ID"].")";
				echo "</b></a>";

				if ($state->getfromDB(PERIPHERAL_TYPE,$tID))
				echo " - ".getDropdownName("glpi_dropdown_state",$state->fields['state']);
				
				echo "</td>";
				if(!empty($withtemplate) && $withtemplate == 2) {
					//do nothing
				} else {
					echo "<td align='center'><a href=\"".$cfg_install["root"]."/computers/computers-info-form.php?cID=$ID&amp;ID=$connID&amp;disconnect=1&amp;withtemplate=".$withtemplate."\"><b>";
					echo $lang["buttons"][10];
					echo "</b></a></td>";
				}
				echo "</tr>";
			}
			echo "</table>";			
		} else {
			echo $lang["computers"][47]."<br>";
		}
		if(!empty($withtemplate) && $withtemplate == 2) {
			//do nothing
		} else {
			echo "<a href=\"".$cfg_install["root"]."/computers/computers-info-form.php?ID=$ID&amp;connect=1&amp;device_type=peripheral&amp;withtemplate=".$withtemplate."\"><b>";
			echo $lang["buttons"][9];
			echo "</b></a>";
		}

	}

	echo "</tr>";
	echo "</table></div><br>";
	
}




?>
