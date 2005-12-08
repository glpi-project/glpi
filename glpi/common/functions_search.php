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

function manageGetValuesInSearch($type=0){
global $_GET;
$tab=array();

if (is_array($_GET))
foreach ($_GET as $key => $val)
		$_SESSION['search'][$type][$key]=$val;

if (!isset($_GET["start"]))
	if (isset($_SESSION['search'][$type]["start"])) $_GET["start"]=$_SESSION['search'][$type]["start"];
	else $_GET["start"] = 0;

if (!isset($_GET["order"]))
	if (isset($_SESSION['search'][$type]["order"])) $_GET["order"]=$_SESSION['search'][$type]["order"];
	else $_GET["order"] = "ASC";

if (!isset($_GET["deleted"]))
if (isset($_SESSION['search'][$type]["deleted"])) $_GET["deleted"]=$_SESSION['search'][$type]["deleted"];
else $_GET["deleted"] = "N";

if (!isset($_GET["distinct"]))
if (isset($_SESSION['search'][$type]["distinct"])) $_GET["distinct"]=$_SESSION['search'][$type]["distinct"];
else $_GET["distinct"] = "Y";
	

if (!isset($_GET["link"]))
	if (isset($_SESSION['search'][$type]["link"])) $_GET["link"]=$_SESSION['search'][$type]["link"];
	else $_GET["link"] = "";

if (!isset($_GET["field"]))
	if (isset($_SESSION['search'][$type]["field"])) $_GET["field"]=$_SESSION['search'][$type]["field"];
	else $_GET["field"] = array(0 => "view");

if (!isset($_GET["contains"]))
	if (isset($_SESSION['search'][$type]["contains"])) $_GET["contains"]=$_SESSION['search'][$type]["contains"];
	else $_GET["contains"] = array(0 => "");

if (!isset($_GET["sort"]))
	if (isset($_SESSION['search'][$type]["sort"])) $_GET["sort"]=$_SESSION['search'][$type]["sort"];
	else $_GET["sort"] = 1;

}

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
function searchForm($type,$target,$field="",$contains="",$sort= "",$deleted= "",$link="",$distinct="Y"){
	global $lang,$HTMLRel,$SEARCH_OPTION,$cfg_install,$LINK_ID_TABLE,$deleted_tables;
	$options=$SEARCH_OPTION[$type];
	
	echo "<form method=get action=\"$target\">";
	echo "<div align='center'><table border='0' width='850' class='tab_cadre'>";
	echo "<tr><th colspan='5'><b>".$lang["search"][0].":</b></th></tr>";
	echo "<tr class='tab_bg_1'>";
	echo "<td align='center'>";
	echo "<table>";
	
	for ($i=0;$i<$_SESSION["glpisearchcount"];$i++){
		echo "<tr><td align='right'>";
		if ($i==0){
			echo "<a href='".$cfg_install["root"]."/computers/index.php?add_search_count=1'><img src=\"".$HTMLRel."pics/plus.png\" alt='+'></a>&nbsp;&nbsp;&nbsp;&nbsp;";
			if ($_SESSION["glpisearchcount"]>1)
			echo "<a href='".$cfg_install["root"]."/computers/index.php?delete_search_count=1'><img src=\"".$HTMLRel."pics/moins.png\" alt='-'></a>&nbsp;&nbsp;&nbsp;&nbsp;";
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
	
	echo "<td>";
	echo "<table>";
	if (in_array($LINK_ID_TABLE[$type],$deleted_tables)){
		echo "<tr><td><select name='deleted'>";
		echo "<option value='Y' ".($deleted=='Y'?" selected ":"").">".$lang["choice"][0]."</option>";
		echo "<option value='N' ".($deleted=='N'?" selected ":"").">".$lang["choice"][1]."</option>";
		echo "</select>";
		echo "<img src=\"".$HTMLRel."pics/showdeleted.png\" alt='".$lang["common"][3]."' title='".$lang["common"][3]."'>";
		echo "</td></tr>";
	}
	
	echo "<tr><td><select name='distinct'>";
	echo "<option value='Y' ".($distinct=='Y'?" selected ":"").">".$lang["choice"][0]."</option>";
	echo "<option value='N' ".($distinct=='N'?" selected ":"").">".$lang["choice"][1]."</option>";
	echo "</select>";
	echo "<img src=\"".$HTMLRel."pics/doublons.png\" alt='".$lang["common"][12]."' title='".$lang["common"][12]."'>";
	echo "</td></tr></table>";
	echo "</td>";
	echo "<td width='80' align='center' class='tab_bg_2'>";
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
function showList ($type,$target,$field,$contains,$sort,$order,$start,$deleted,$link,$distinct){
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
	$SELECT ="SELECT ";
	

	for ($i=0;$i<$toview_count;$i++){
		$SELECT.=addSelect($type,$SEARCH_OPTION[$type][$toview[$i]]["table"].".".$SEARCH_OPTION[$type][$toview[$i]]["field"],$i);
	}
	// Add ID
	$SELECT.=$LINK_ID_TABLE[$type].".ID AS ID ";
	// Get specific item
	if ($LINK_ID_TABLE[$type]=="glpi_cartridges_type"||$LINK_ID_TABLE[$type]=="glpi_consumables_type")
		$SELECT.=", ".$LINK_ID_TABLE[$type].".alarm as ALARM";

	//// 2 - FROM AND LEFT JOIN 
	$FROM = " FROM ".$LINK_ID_TABLE[$type];
	$already_link_tables=array();
	array_push($already_link_tables,$LINK_ID_TABLE[$type]);

	for ($i=1;$i<$toview_count;$i++)
		$FROM.=addLeftJoin($type,$LINK_ID_TABLE[$type],$already_link_tables,$SEARCH_OPTION[$type][$toview[$i]]["table"]);
		
	
	// Search all case :
	if (count($SEARCH_ALL)>0)
	foreach ($SEARCH_OPTION[$type] as $key => $val)
			$FROM.=addLeftJoin($type,$LINK_ID_TABLE[$type],$already_link_tables,$SEARCH_OPTION[$type][$key]["table"]);
	

	//// 3 - WHERE	
	
	$first=true;
	$WHERE = " WHERE ";
	if (in_array($LINK_ID_TABLE[$type],$deleted_tables)){
		$LINK= " AND " ;
		if ($first) {$LINK=" ";$first=false;}
		$WHERE.= $LINK.$LINK_ID_TABLE[$type].".deleted='$deleted' ";
	}
	if (in_array($LINK_ID_TABLE[$type],$template_tables)){
		$LINK= " AND " ;
		if ($first) {$LINK=" ";$first=false;}
		$WHERE.= $LINK.$LINK_ID_TABLE[$type].".is_template='0' ";
	}

	// Add search conditions
	
	if (count($contains)>0) {
		$i=0;

		$TOADD="";
		foreach($contains as $key => $val)
		if (strlen($val)>0&&$field[$key]!="all"&&$field[$key]!="view"){
			$LINK=" ";
			if ($i>0) $LINK=$link[$key];
			
			$TOADD.= $LINK.addWhere($type,$SEARCH_OPTION[$type][$field[$key]]["table"].".".$SEARCH_OPTION[$type][$field[$key]]["field"],$val);	
			$i++;
		} else if (strlen($val)>0&&$field[$key]=="view"){
						
			if ($i!=0)
				$TOADD.= " ".$link[$key];
			 $TOADD.= " ( ";
			$first2=true;
			foreach ($toview as $key2 => $val2){
				$LINK=" OR ";
				if ($first2) {$LINK=" ";$first2=false;}
				$TOADD.= $LINK.addWhere($type,$SEARCH_OPTION[$type][$val2]["table"].".".$SEARCH_OPTION[$type][$val2]["field"],$val);	
			}
			$TOADD.=" ) ";
			$i++;
		}
		
		if (!empty($TOADD)){
			$LINK= " AND " ;
			if ($first) {$LINK=" ";$first=false;}
			$WHERE.=$LINK." ( ".$TOADD." ) ";
		}
	}
	
	// Search ALL 
	if (count($SEARCH_ALL)>0)
	foreach ($SEARCH_ALL as $key => $val)
	if (!empty($val["contains"])){
		$LINK= " ".$val["link"]." " ;
		if ($first) {$LINK=" ";$first=false;}

		$WHERE.=$LINK." ( ";
		$first2=true;
		foreach ($SEARCH_OPTION[$type] as $key2 => $val2){
			$LINK=" OR ";
			if ($first2) {$LINK=" ";$first2=false;}
			$WHERE.= $LINK.addWhere($type,$val2["table"].".".$val2["field"],$val["contains"]);	
			}
		
		$WHERE.=")";
	}
	
	
	//// 4 - ORDER 
	$ORDER= addOrderBy($SEARCH_OPTION[$type][$sort]["table"].".".$SEARCH_OPTION[$type][$sort]["field"],$order);

	$GROUPBY=" GROUP BY ID";
	if ($distinct!='Y') $GROUPBY="";
	
	if ($WHERE == " WHERE ") $WHERE="";
	$QUERY=$SELECT.$FROM.$WHERE.$GROUPBY.$ORDER;
	//echo $QUERY;
	
	// Get it from database and DISPLAY
	if ($result = $db->query($QUERY)) {
		$numrows= $db->numrows($result);
		if ($start<$numrows) {

			// Pager
			$parameters="sort=$sort&amp;order=$order".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains);
			printPager($start,$numrows,$target,$parameters);

			// Produce headline
			echo "<div align='center'><table border='0' class='tab_cadrehov'><tr>\n";

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
			if ($type==SOFTWARE_TYPE)
					echo "<th>".$lang["software"][11]."</th>";
			if ($type==CARTRIDGE_TYPE)
					echo "<th>".$lang["cartridges"][0]."</th>";
			if ($type==CONSUMABLE_TYPE)
					echo "<th>".$lang["consumables"][0]."</th>";
					
					
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
				if ($SEARCH_OPTION[$type][1]["table"].".".$SEARCH_OPTION[$type][1]["field"]=="glpi_users.name")
					displayItem($type,"glpi_users.name.brut",$data,0);
				else 
					displayItem($type,$SEARCH_OPTION[$type][1]["table"].".".$SEARCH_OPTION[$type][1]["field"],$data,0);
				echo "</b></td>";
				// Print other items
				for ($j=1;$j<$toview_count;$j++){
					echo "<td>";
					if ($SEARCH_OPTION[$type][$toview[$j]]["table"].".".$SEARCH_OPTION[$type][$toview[$j]]["field"]=="glpi_enterprises.name")
						displayItem($type,"glpi_enterprises.name.brut",$data,$j);
					else if ($SEARCH_OPTION[$type][$toview[$j]]["table"].".".$SEARCH_OPTION[$type][$toview[$j]]["field"]=="glpi_contracts.name")
						displayItem($type,"glpi_contracts.name.brut",$data,$j);
					else  
						displayItem($type,$SEARCH_OPTION[$type][$toview[$j]]["table"].".".$SEARCH_OPTION[$type][$toview[$j]]["field"],$data,$j);
					echo "</td>";
				}
				
				if ($type==CARTRIDGE_TYPE){
					echo "<td>";
					countCartridges($data["ID"],$data["ALARM"]);
					echo "</td>";
				}
				
				if ($type==SOFTWARE_TYPE){
					echo "<td>";					
		   		countInstallations($data["ID"]);
					echo "</td>";
				}		
				
				if ($type==CONSUMABLE_TYPE){
					echo "<td>";					
		   		countConsumables($data["ID"],$data["ALARM"]);
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
			echo "<div align='center'><b>".$lang["search"][15]."</b></div>\n";
			
		}
	}
	else echo $db->error();

}

/**
* Generic Function to add ORDER BY to a request
*
*
*@param $field field to add
*@param $order order define
*
*
*@return select string
*
**/
function addOrderBy($field,$order){
	switch($field){
	case "glpi_device_hdd.specif_default" :
		return " ORDER BY DEVICE_".HDD_DEVICE.".specificity $order ";
		break;

	case "glpi_device_ram.specif_default" :
		return " ORDER BY DEVICE_".RAM_DEVICE.".specificity $order ";
		break;
	default:
		return " ORDER BY $field $order ";
		break;
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
function addSelect ($type,$field,$num){

switch ($field){
case "glpi_users.name" :
	return $field." AS ITEM_$num, glpi_users.realname AS ITEM_".$num."_2, ";
	break;
case "glpi_device_hdd.specif_default" :
	return $field." AS ITEM_$num, DEVICE_".HDD_DEVICE.".specificity AS ITEM_".$num."_2, ";
	break;
case "glpi_device_ram.specif_default" :
	return $field." AS ITEM_$num, DEVICE_".RAM_DEVICE.".specificity AS ITEM_".$num."_2, ";
	break;
case "glpi_networking_ports.ifmac" :
	if ($type==COMPUTER_TYPE)
		return $field." AS ITEM_$num, DEVICE_".NETWORK_DEVICE.".specificity AS ITEM_".$num."_2, ";
	else return $field." AS ITEM_$num, ";
	break;
default:
	return $field." AS ITEM_$num, ";
	break;
}

}

/**
* Generic Function to add where to a request
*
*
*@param $field field to add
*@param $val item num in the request
*
*
*@return select string
*
**/
function addWhere ($type,$field,$val,$device_type=0){

switch ($field){
case "glpi_users.name" :
	return " ( $field LIKE '%".$val."%' OR glpi_users.realname LIKE '%".$val."%' ) ";
	break;
case "glpi_device_hdd.specif_default" :
	$larg=500;
	return " ( DEVICE_".HDD_DEVICE.".specificity < ".($val+$larg)." AND DEVICE_".HDD_DEVICE.".specificity > ".($val-$larg)." ) ";
	break;
case "glpi_device_ram.specif_default" :
	$larg=50;
	return " ( DEVICE_".RAM_DEVICE.".specificity < ".($val+$larg)." AND DEVICE_".RAM_DEVICE.".specificity > ".($val-$larg)." ) ";
	break;
case "glpi_networking_ports.ifmac" :
	if ($type==COMPUTER_TYPE)
		return " (  DEVICE_".NETWORK_DEVICE.".specificity LIKE '%".$val."%'  OR $field LIKE '%".$val."%' ) ";
	else return " $field LIKE '%".$val."%' ";
	break;
	
default:
	return " $field LIKE '%".$val."%' ";
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
function displayItem ($type,$field,$data,$num){
global $cfg_install,$INFOFORM_PAGES,$HTMLRel;

switch ($field){
	case "glpi_users.name" :
		// print realname or login name
		if (!empty($data["ITEM_".$num."_2"]))
			echo $data["ITEM_".$num."_2"];
		else echo $data["ITEM_$num"];
		break;
	case "glpi_users.name.brut" :		
		$type=USER_TYPE;
		echo "<a href=\"".$cfg_install["root"]."/".$INFOFORM_PAGES[$type]."?ID=".$data['ID']."\">";
		echo $data["ITEM_$num"]." (".$data["ID"].")";
		echo "</a>";
		break;
	case "glpi_computers.name" :
		$type=COMPUTER_TYPE;
		echo "<a href=\"".$cfg_install["root"]."/".$INFOFORM_PAGES[$type]."?ID=".$data['ID']."\">";
		echo $data["ITEM_$num"]." (".$data["ID"].")";
		echo "</a>";
		break;
	case "glpi_printers.name" :
		$type=PRINTER_TYPE;
		echo "<a href=\"".$cfg_install["root"]."/".$INFOFORM_PAGES[$type]."?ID=".$data['ID']."\">";
		echo $data["ITEM_$num"]." (".$data["ID"].")";
		echo "</a>";
		break;
	case "glpi_networking.name" :
		$type=NETWORKING_TYPE;
		echo "<a href=\"".$cfg_install["root"]."/".$INFOFORM_PAGES[$type]."?ID=".$data['ID']."\">";
		echo $data["ITEM_$num"]." (".$data["ID"].")";
		echo "</a>";
		break;
	case "glpi_monitors.name" :
		$type=MONITOR_TYPE;
		echo "<a href=\"".$cfg_install["root"]."/".$INFOFORM_PAGES[$type]."?ID=".$data['ID']."\">";
		echo $data["ITEM_$num"]." (".$data["ID"].")";
		echo "</a>";
		break;
	case "glpi_software.name" :
		$type=SOFTWARE_TYPE;
		echo "<a href=\"".$cfg_install["root"]."/".$INFOFORM_PAGES[$type]."?ID=".$data['ID']."\">";
		echo $data["ITEM_$num"]." (".$data["ID"].")";
		echo "</a>";
		break;
	case "glpi_peripherals.name" :
		$type=PERIPHERAL_TYPE;
		echo "<a href=\"".$cfg_install["root"]."/".$INFOFORM_PAGES[$type]."?ID=".$data['ID']."\">";
		echo $data["ITEM_$num"]." (".$data["ID"].")";
		echo "</a>";
		break;	
	case "glpi_cartridges_type.name" :
		$type=CARTRIDGE_TYPE;
		echo "<a href=\"".$cfg_install["root"]."/".$INFOFORM_PAGES[$type]."?ID=".$data['ID']."\">";
		echo $data["ITEM_$num"]." (".$data["ID"].")";
		echo "</a>";
		break;
	case "glpi_consumables_type.name" :
		$type=CONSUMABLE_TYPE;
		echo "<a href=\"".$cfg_install["root"]."/".$INFOFORM_PAGES[$type]."?ID=".$data['ID']."\">";
		echo $data["ITEM_$num"]." (".$data["ID"].")";
		echo "</a>";
		break;	
	case "glpi_contacts.name" :
		$type=CONTACT_TYPE;
		echo "<a href=\"".$cfg_install["root"]."/".$INFOFORM_PAGES[$type]."?ID=".$data['ID']."\">";
		echo $data["ITEM_$num"]." (".$data["ID"].")";
		echo "</a>";
		break;	
	case "glpi_contracts.name" :
		$type=CONTRACT_TYPE;
		echo "<a href=\"".$cfg_install["root"]."/".$INFOFORM_PAGES[$type]."?ID=".$data['ID']."\">";
		echo $data["ITEM_$num"]." (".$data["ID"].")";
		echo "</a>";
		break;	
	case "glpi_contracts.name.brut" :
		$type=CONTRACT_TYPE;
		echo $data["ITEM_$num"];
		break;			

	case "glpi_enterprises.name" :
		$type=ENTERPRISE_TYPE;
		echo "<a href=\"".$cfg_install["root"]."/".$INFOFORM_PAGES[$type]."?ID=".$data['ID']."\">";
		echo $data["ITEM_$num"]." (".$data["ID"].")";
		echo "</a>";
		break;	
	case "glpi_enterprises.name.brut" :
		$type=ENTERPRISE_TYPE;
		echo $data["ITEM_$num"];
		break;			
	case "glpi_docs.name" :		
		$type=DOCUMENT_TYPE;
		echo "<a href=\"".$cfg_install["root"]."/".$INFOFORM_PAGES[$type]."?ID=".$data['ID']."\">";
		echo $data["ITEM_$num"]." (".$data["ID"].")";
		echo "</a>";
		break;
	case "glpi_type_docs.name" :		
		$type=TYPEDOC_TYPE;
		echo "<a href=\"".$cfg_install["root"]."/".$INFOFORM_PAGES[$type]."?ID=".$data['ID']."\">";
		echo $data["ITEM_$num"]." (".$data["ID"].")";
		echo "</a>";
		break;
	case "glpi_links.name" :		
		$type=LINK_TYPE;
		echo "<a href=\"".$cfg_install["root"]."/".$INFOFORM_PAGES[$type]."?ID=".$data['ID']."\">";
		echo $data["ITEM_$num"]." (".$data["ID"].")";
		echo "</a>";
		break;		
	case "glpi_type_docs.icon" :
		if (!empty($data["ITEM_$num"]))
			echo "<img style='vertical-align:middle;' alt='' src='".$HTMLRel.$cfg_install["typedoc_icon_dir"]."/".$data["ITEM_$num"]."'>";
		else echo "&nbsp;";
	break;	

	case "glpi_docs.filename" :		
		echo getDocumentLink($data["ITEM_$num"]);
	break;		
	case "glpi_docs.link" :
	case "glpi_enterprises.website" :
		if (!empty($data["ITEM_$num"]))
			echo "<a href=\"".$data["ITEM_$num"]."\">".$data["ITEM_$num"]."</a>";
		else echo "&nbsp;";
	break;	
	case "glpi_enterprises.email" :
	case "glpi_contacts.email" :
	case "glpi_users.email" :
		if (!empty($data["ITEM_$num"]))
			echo "<a href='mailto:".$data["ITEM_$num"]."'>".$data["ITEM_$num"]."</a>";
		else echo "&nbsp;";
	break;	
	case "glpi_device_hdd.specif_default" :
			if (empty($data["ITEM_".$num."_2"]))
				echo $data["ITEM_".$num];
			else echo $data["ITEM_".$num."_2"];
	break;
	case "glpi_device_ram.specif_default" :
			if (empty($data["ITEM_".$num."_2"]))
				echo $data["ITEM_".$num];
			else echo $data["ITEM_".$num."_2"];
	break;	
	case "glpi_networking_ports.ifmac" :
		if ($type==COMPUTER_TYPE){
			if (!empty($data["ITEM_".$num."_2"])){
				echo "hw=".$data["ITEM_".$num."_2"];
				if (!empty($data["ITEM_".$num])) echo " - ";
			}
		
			if (!empty($data["ITEM_".$num]))
				echo "port=".$data["ITEM_".$num];
		} else echo $data["ITEM_$num"];
	
	break;
	default:
		echo $data["ITEM_$num"];
		break;
}

}


/**
* Generic Function to get transcript table name
*
*
*@param $type reference ID
*@param $ref_table reference table
*@param $already_link_tables array of tables already joined
*@param $new_table new table to join
*@param $device_type device_type for search on computer device
*
*
*@return Left join string
*
**/
function translate_table($table,$device_type=0){

switch ($table){
	case "glpi_computer_device":
		if ($device_type==0)
			return $table;
		else return "DEVICE_".$device_type;
		break;
	default :
		return $table;
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
*@param $device_type device_type for search on computer device
*
*
*@return Left join string
*
**/
function addLeftJoin ($type,$ref_table,&$already_link_tables,$new_table,$device_type=0){

if (in_array(translate_table($new_table,$device_type),$already_link_tables)) return "";
else array_push($already_link_tables,translate_table($new_table,$device_type));

switch ($new_table){
	case "glpi_dropdown_locations":
		return " LEFT JOIN $new_table ON ($ref_table.location = $new_table.ID) ";
		break;
	case "glpi_dropdown_contract_type":
		return " LEFT JOIN $new_table ON ($ref_table.contract_type = $new_table.ID) ";
		break;
	case "glpi_type_computers":
	case "glpi_type_networking":
	case "glpi_type_printers":
	case "glpi_type_monitors":
	case "glpi_dropdown_contact_type":
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
	if ($type==SOFTWARE_TYPE)
		return " LEFT JOIN $new_table ON ($ref_table.platform = $new_table.ID) ";
	else 
		return " LEFT JOIN $new_table ON ($ref_table.os = $new_table.ID) ";
		break;
	case "glpi_networking_ports":
		$out="";
		// Add networking device for computers
		if ($type==COMPUTER_TYPE)
			$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_computer_device",NETWORK_DEVICE);
		
		return $out." LEFT JOIN $new_table ON ($ref_table.ID = $new_table.on_device AND $new_table.device_type='$type') ";
		break;
	case "glpi_dropdown_netpoint":
		// Link to glpi_networking_ports before
		$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_networking_ports");
		
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
		// Link to glpi_state_item before
		$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_state_item");
		
		return $out." LEFT JOIN $new_table ON (glpi_state_item.state = $new_table.ID) ";
		break;
	
	case "glpi_contracts":
		// Link to glpi_networking_ports before
		$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_contract_device");
		
		return $out." LEFT JOIN $new_table ON (glpi_contract_device.FK_contract = $new_table.ID) ";
		break;
	case "glpi_dropdown_network":
		return " LEFT JOIN $new_table ON ($ref_table.network = $new_table.ID) ";
		break;			
	case "glpi_dropdown_domain":
		return " LEFT JOIN $new_table ON ($ref_table.domain = $new_table.ID) ";
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
		if ($device_type==0)
			return " LEFT JOIN $new_table ON ($ref_table.ID = $new_table.FK_computers ) ";
		else return " LEFT JOIN $new_table AS DEVICE_".$device_type." ON ($ref_table.ID = DEVICE_".$device_type.".FK_computers AND DEVICE_".$device_type.".device_type='$device_type') ";
		break;	
	case "glpi_device_processor":
		// Link to glpi_networking_ports before
		$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_computer_device",PROCESSOR_DEVICE);
		
		return $out." LEFT JOIN $new_table ON (DEVICE_".PROCESSOR_DEVICE.".FK_device = $new_table.ID) ";
		break;		
	case "glpi_device_ram":
		$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_computer_device",RAM_DEVICE);
		
		return $out." LEFT JOIN $new_table ON (DEVICE_".RAM_DEVICE.".FK_device = $new_table.ID) ";
		break;		
	case "glpi_device_iface":
		// Link to glpi_networking_ports before
		$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_computer_device",NETWORK_DEVICE);
		
		return $out." LEFT JOIN $new_table ON (DEVICE_".NETWORK_DEVICE.".FK_device = $new_table.ID) ";
		break;	
	case "glpi_device_sndcard":
		// Link to glpi_networking_ports before
		$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_computer_device",SND_DEVICE);
		
		return $out." LEFT JOIN $new_table ON (DEVICE_".SND_DEVICE.".FK_device = $new_table.ID) ";
		break;		
	case "glpi_device_gfxcard":
		// Link to glpi_networking_ports before
		$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_computer_device",GFX_DEVICE);
		
		return $out." LEFT JOIN $new_table ON (DEVICE_".GFX_DEVICE.".FK_device = $new_table.ID) ";
		break;	
	case "glpi_device_moboard":
		// Link to glpi_networking_ports before
		$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_computer_device",MOBOARD_DEVICE);
		
		return $out." LEFT JOIN $new_table ON (DEVICE_".MOBOARD_DEVICE.".FK_device = $new_table.ID) ";
		break;	
	case "glpi_device_hdd":
		// Link to glpi_networking_ports before
		$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_computer_device",HDD_DEVICE);
		
		return $out." LEFT JOIN $new_table ON (DEVICE_".HDD_DEVICE.".FK_device = $new_table.ID) ";
		break;
	default :
		return "";
		break;
}
}
?>