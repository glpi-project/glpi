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

/**
* Print generic search form
*
* 
*
*@param $type='' type to display the form
*@param $target='' url to post the form
*@param $field='' array of the fields selected in the search form
*@param $contains='' array of the search strings
*@param $sort='' the "sort by" field value
*@param $deleted='' the deleted value 
*@param $link='' array of the link between each search.
*
*@return nothing (diplays)
*
**/
function searchForm($type,$target,$field="",$contains="",$sort= "",$deleted= "",$link=""){
	global $lang,$HTMLRel,$SEARCH_OPTION,$cfg_install;
	$options=$SEARCH_OPTION[$type];
	
	echo "<form method=get action=\"$target\">";
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
    	echo "<option value='view' ";
		if(is_array($field)&&isset($field[$i]) && $field[$i] == "view") echo "selected";
		echo ">".$lang["search"][11]."</option>";

        	reset($options);
		foreach ($options as $key => $val) {
			echo "<option value=\"".$key."\""; 
			if(is_array($field)&&isset($field[$i]) && $key == $field[$i]) echo "selected";
			echo ">". $val["name"] ."</option>\n";
		}

    	echo "<option value='all' ";
		if(is_array($field)&&isset($field[$i]) && $field[$i] == "all") echo "selected";
		echo ">".$lang["search"][7]."</option>";

		echo "</select>&nbsp;";

		
		echo "</td></tr>";
	}
	echo "</table>";
	echo "</td>";

	echo "<td>";
	echo $lang["search"][4];
	echo "&nbsp;<select name='sort' size='1'>";
	reset($options);
	foreach ($options as $key => $val) {
		echo "<option value=\"".$key."\"";
		if($key == $sort) echo "selected";
		echo ">".$val["name"]."</option>\n";
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
* Generic Search and list function
*
*
* Build the query, make the search and list items after a search.
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
function showList ($type,$target,$field,$contains,$sort,$order,$start,$deleted,$link){
	global $INFOFORM_PAGES,$SEARCH_OPTION,$LINK_ID_TABLE,$HTMLRel,$cfg_install,$deleted_tables,$template_tables,$lang,$cfg_features;
	$db=new DB;
	
	// Get the items to display
	$toview=array();
	// Add first element (name)
	array_push($toview,1);
	$query="SELECT * FROM glpi_display WHERE type='$type' ORDER by rank";
	$result=$db->query($query);
	if ($db->numrows($result)>0){
		while ($data=$db->fetch_array($result))
			array_push($toview,$data["num"]);
	}

	// Manage search on all item
	$SEARCH_ALL=array();
	if (in_array("all",$field)){
		foreach ($field as $key => $val)
		if ($val=="all"){
			$templink="AND";
			if (isset($link[$key])) $templink=$link[$key];
			array_push($SEARCH_ALL,array("link"=>$templink, "contains"=>$contains[$key]));
		}
	}
	
	// Add searched items
	if (count($field)>0)
		foreach($field as $key => $val)
			if (!in_array($val,$toview)&&$val!="all"&&$val!="view")
			array_push($toview,$val);
	
	// Add order item
	if (!in_array($sort,$toview))
		array_push($toview,$sort);
	
			
			
	// Clean toview array
	$toview=array_unique($toview);
	$toview_count=count($toview);
	
	// Construct the request 
	//// 1 - SELECT
	$SELECT ="SELECT DISTINCT ";

	for ($i=0;$i<$toview_count;$i++){
		$SELECT.=addSelect($SEARCH_OPTION[$type][$toview[$i]]["table"].".".$SEARCH_OPTION[$type][$toview[$i]]["field"],$i);
	}
	// Add ID
	$SELECT.=$LINK_ID_TABLE[$type].".ID AS ID ";

	//// 2 - FROM AND LEFT JOIN 
	$FROM = " FROM ".$LINK_ID_TABLE[$type];
	$already_link_tables=array();
	array_push($already_link_tables,$LINK_ID_TABLE[$type]);

	for ($i=1;$i<$toview_count;$i++){
		if (!in_array($SEARCH_OPTION[$type][$toview[$i]]["table"],$already_link_tables)){
			$FROM.=addLeftJoin($type,$LINK_ID_TABLE[$type],$already_link_tables,$SEARCH_OPTION[$type][$toview[$i]]["table"]);
			array_push($already_link_tables,$SEARCH_OPTION[$type][$toview[$i]]["table"]);
		}
	}	
	
	// Search all case :
	if (count($SEARCH_ALL)>0)
	foreach ($SEARCH_OPTION[$type] as $key => $val)
	if (!in_array($SEARCH_OPTION[$type][$key]["table"],$already_link_tables)){
			$FROM.=addLeftJoin($type,$LINK_ID_TABLE[$type],$already_link_tables,$SEARCH_OPTION[$type][$key]["table"]);
			array_push($already_link_tables,$SEARCH_OPTION[$type][$key]["table"]);
	}

	//// 3 - WHERE	
	$WHERE = " WHERE '1'='1' ";
	if (in_array($LINK_ID_TABLE[$type],$deleted_tables))
		$WHERE.= " AND ".$LINK_ID_TABLE[$type].".deleted='$deleted' ";
	if (in_array($LINK_ID_TABLE[$type],$template_tables))
		$WHERE.= " AND ".$LINK_ID_TABLE[$type].".is_template='0' ";

	// Add search conditions
	
	if (count($contains)>0) {
		$i=0;
		$WHERE.=" AND ( ";
		foreach($contains as $key => $val)
		if ($field[$key]!="all"&&$field[$key]!="view"){
			$LINK=" ";
			if ($i>0) $LINK=$link[$key];
			
			$WHERE.= " $LINK ".$SEARCH_OPTION[$type][$field[$key]]["table"].".".$SEARCH_OPTION[$type][$field[$key]]["field"]." LIKE '%".$val."%' ";	
			$i++;
		} else if ($field[$key]=="view"){
			if ($i!=0)
				$WHERE.= " ".$link[$key]." ( '0'='1' ";
			else  $WHERE.= " ( '0'='1' ";
			
			foreach ($toview as $key2 => $val2){
				$WHERE.= " OR ".$SEARCH_OPTION[$type][$val2]["table"].".".$SEARCH_OPTION[$type][$val2]["field"]." LIKE '%".$val."%' ";	
			}
			$WHERE.=" ) ";
			$i++;
		}
		$WHERE.=" ) ";
	}
	
	// Search ALL 
	if (count($SEARCH_ALL)>0)
	foreach ($SEARCH_ALL as $key => $val){
		$WHERE.=$val["link"]." ( '1'='0' ";
		foreach ($SEARCH_OPTION[$type] as $key2 => $val2)
			$WHERE.= " OR ".$val2["table"].".".$val2["field"]." LIKE '%".$val["contains"]."%' ";	
		
		$WHERE.=")";
	
	
	}
	
	
	//// 4 - ORDER 
	$ORDER= " ORDER BY ".$SEARCH_OPTION[$type][$sort]["table"].".".$SEARCH_OPTION[$type][$sort]["field"]." $order";

	$QUERY=$SELECT.$FROM.$WHERE.$ORDER;
	//echo $QUERY;
	
	// Get it from database and DISPLAY
	if ($result = $db->query($QUERY)) {
		$numrows= $db->numrows($result);
		if ($start<$numrows) {

			// Pager
			$parameters="sort=$sort&amp;order=$order".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains);
			printPager($start,$numrows,$target,$parameters);

			// Produce headline
			echo "<div align='center'><table border='0' class='tab_cadre'><tr>\n";

			// TABLE HEADER
			for ($i=0;$i<$toview_count;$i++){
				echo "<th>";
				if ($sort==$toview[$i]) {
					if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
					else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
				}
				echo "<a href=\"$target?sort=".$toview[$i]."&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains)."\">";
				echo $SEARCH_OPTION[$type][$toview[$i]]["name"]."</a></th>\n";
			}
			echo "</tr>\n";
			$db->data_seek($result,$start);
			$i=$start;
			//for ($i=$start; $i < $numrows && $i<($start+$cfg_features["list_limit"]); $i++) {
			while ($i < $numrows && $i<($start+$cfg_features["list_limit"])){
				$data=$db->fetch_assoc($result);
				$i++;
			
				echo "<tr class='tab_bg_2'>";
				// Print first element
				echo "<td><b>";
				echo "<a href=\"".$cfg_install["root"]."/".$INFOFORM_PAGES[$type]."?ID=".$data['ID']."\">";
				displayItem($SEARCH_OPTION[$type][1]["table"].".".$SEARCH_OPTION[$type][1]["field"],$data,0);
				echo "</a></b></td>";
				// Print other items
				for ($j=1;$j<$toview_count;$j++){
					echo "<td>";
					displayItem($SEARCH_OPTION[$type][$toview[$j]]["table"].".".$SEARCH_OPTION[$type][$toview[$j]]["field"],$data,$j);
					echo "</td>";
				}
		                 echo "</tr>\n";
			}

			// Close Table
			echo "</table></div>\n";

			// Pager
			echo "<br>";
			printPager($start,$numrows,$target,$parameters);

		} else {
			echo "<div align='center'><b>".$lang["computers"][32]."</b></div>\n";
			
		}
	}

}

/**
* Generic Function to add select to a request
*
*
*@param $field field to add
*@param $num item num in the request
*
*
*@return select string
*
**/
function addSelect ($field,$num){

switch ($field){
case "glpi_users.name" :
	return $field." AS ITEM_$num, glpi_users.realname AS ITEM_".$num."_2, ";
	break;
default:
	return $field." AS ITEM_$num, ";
	break;
}

}

/**
* Generic Function to display Items
*
*
*@param $field field to add
*@param $data arrau containing data results
*@param $num item num in the request
*
*
*@return string to print
*
**/
function displayItem ($field,$data,$num){


switch ($field){
	case "glpi_users.name" :
		// print realname or login name
		if (!empty($data["ITEM_".$num."_2"]))
			echo $data["ITEM_".$num."_2"];
		else echo $data["ITEM_$num"];
		break;
	default:
		echo $data["ITEM_$num"];
		break;
}

}



/**
* Generic Function to add left join to a request
*
*
*@param $type reference ID
*@param $ref_table reference table
*@param $already_link_tables array of tables already joined
*@param $new_table new table to join
*
*
*@return Left join string
*
**/
function addLeftJoin ($type,$ref_table,&$already_link_tables,$new_table){

switch ($new_table){
	case "glpi_dropdown_locations":
		return " LEFT JOIN $new_table ON ($ref_table.location = $new_table.ID) ";
		break;
	case "glpi_type_computers":
	case "glpi_type_networking":
	case "glpi_type_printers":
	case "glpi_type_monitors":
	case "glpi_dropdown_contact_type":
	case "glpi_dropdown_contract_type":
	case "glpi_dropdown_consumable_type":
	case "glpi_dropdown_cartridge_type":
	case "glpi_dropdown_enttype":
	case "glpi_type_peripherals":
		return " LEFT JOIN $new_table ON ($ref_table.type = $new_table.ID) ";
		break;
	case "glpi_dropdown_model":
		return " LEFT JOIN $new_table ON ($ref_table.model = $new_table.ID) ";
		break;
	case "glpi_dropdown_os":
		return " LEFT JOIN $new_table ON ($ref_table.os = $new_table.ID) ";
		break;
	case "glpi_networking_ports":
		return " LEFT JOIN $new_table ON ($ref_table.ID = $new_table.on_device AND $new_table.device_type='$type') ";
		break;
	case "glpi_dropdown_netpoint":
		$out="";
		// Link to glpi_networking_ports before
		if (!in_array("glpi_networking_ports",$already_link_tables)){
			$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_networking_ports");
			array_push($already_link_tables,"glpi_networking_ports");
		}
		
		return $out." LEFT JOIN $new_table ON (glpi_networking_ports.netpoint = $new_table.ID) ";
		break;
	case "glpi_users":
		return " LEFT JOIN $new_table ON ($ref_table.tech_num = $new_table.ID) ";
		break;
	case "glpi_enterprises":
		return " LEFT JOIN $new_table ON ($ref_table.FK_glpi_enterprise = $new_table.ID) ";
		break;
	case "glpi_infocoms":
		return " LEFT JOIN $new_table ON ($ref_table.ID = $new_table.FK_device AND $new_table.device_type='$type') ";
		break;
	case "glpi_contract_device":
		return " LEFT JOIN $new_table ON ($ref_table.ID = $new_table.FK_device AND $new_table.device_type='$type') ";
		break;
	case "glpi_state_item":
		return " LEFT JOIN $new_table ON ($ref_table.ID = $new_table.id_device AND $new_table.device_type='$type') ";
		break;
	case "glpi_dropdown_state":
		$out="";
		// Link to glpi_state_item before
		if (!in_array("glpi_state_item",$already_link_tables)){
			$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_state_item");
			array_push($already_link_tables,"glpi_state_item");
		}
		
		return $out." LEFT JOIN $new_table ON (glpi_state_item.state = $new_table.ID) ";
		break;
	
	case "glpi_contracts":
		$out="";
		// Link to glpi_networking_ports before
		if (!in_array("glpi_contract_device",$already_link_tables)){
			$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_contract_device");
			array_push($already_link_tables,"glpi_contract_device");
		}
		
		return $out." LEFT JOIN $new_table ON (glpi_contract_device.FK_contract = $new_table.ID) ";
		break;
	case "glpi_dropdown_firmware":
		return " LEFT JOIN $new_table ON ($ref_table.firmware = $new_table.ID) ";
		break;			
	case "glpi_dropdown_rubdocs":
		return " LEFT JOIN $new_table ON ($ref_table.rubrique = $new_table.ID) ";
		break;	
	case "glpi_licenses":
		return " LEFT JOIN $new_table ON ($ref_table.ID = $new_table.sID) ";
		break;	
	case "glpi_computer_device":
		return " LEFT JOIN $new_table ON ($ref_table.ID = $new_table.FK_computers) ";
		break;	
	case "glpi_device_processor":
		$out="";
		// Link to glpi_networking_ports before
		if (!in_array("glpi_computer_device",$already_link_tables)){
			$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_computer_device");
			array_push($already_link_tables,"glpi_computer_device");
		}
		
		return $out." LEFT JOIN $new_table ON (glpi_computer_device.FK_device = $new_table.ID AND glpi_computer_device.device_type = '".PROCESSOR_DEVICE."') ";
		break;		
	case "glpi_device_ram":
		$out="";
		// Link to glpi_networking_ports before
		if (!in_array("glpi_computer_device",$already_link_tables)){
			$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_computer_device");
			array_push($already_link_tables,"glpi_computer_device");
		}
		
		return $out." LEFT JOIN $new_table ON (glpi_computer_device.FK_device = $new_table.ID AND glpi_computer_device.device_type = '".RAM_DEVICE."') ";
		break;		
	case "glpi_device_iface":
		$out="";
		// Link to glpi_networking_ports before
		if (!in_array("glpi_computer_device",$already_link_tables)){
			$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_computer_device");
			array_push($already_link_tables,"glpi_computer_device");
		}
		
		return $out." LEFT JOIN $new_table ON (glpi_computer_device.FK_device = $new_table.ID AND glpi_computer_device.device_type = '".NETWORK_DEVICE."') ";
		break;	
	case "glpi_device_sndcard":
		$out="";
		// Link to glpi_networking_ports before
		if (!in_array("glpi_computer_device",$already_link_tables)){
			$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_computer_device");
			array_push($already_link_tables,"glpi_computer_device");
		}
		
		return $out." LEFT JOIN $new_table ON (glpi_computer_device.FK_device = $new_table.ID AND glpi_computer_device.device_type = '".SND_DEVICE."') ";
		break;		
	case "glpi_device_gfxcard":
		$out="";
		// Link to glpi_networking_ports before
		if (!in_array("glpi_computer_device",$already_link_tables)){
			$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_computer_device");
			array_push($already_link_tables,"glpi_computer_device");
		}
		
		return $out." LEFT JOIN $new_table ON (glpi_computer_device.FK_device = $new_table.ID AND glpi_computer_device.device_type = '".GFX_DEVICE."') ";
		break;	
	case "glpi_device_moboard":
		$out="";
		// Link to glpi_networking_ports before
		if (!in_array("glpi_computer_device",$already_link_tables)){
			$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_computer_device");
			array_push($already_link_tables,"glpi_computer_device");
		}
		
		return $out." LEFT JOIN $new_table ON (glpi_computer_device.FK_device = $new_table.ID AND glpi_computer_device.device_type = '".MOBOARD_DEVICE."') ";
		break;	
	case "glpi_device_hdd":
		$out="";
		// Link to glpi_networking_ports before
		if (!in_array("glpi_computer_device",$already_link_tables)){
			$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_computer_device");
			array_push($already_link_tables,"glpi_computer_device");
		}
		
		return $out." LEFT JOIN $new_table ON (glpi_computer_device.FK_device = $new_table.ID AND glpi_computer_device.device_type = '".HDD_DEVICE."') ";
		break;
	default :
		return "";
		break;
}
}
?>