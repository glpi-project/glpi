<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2007 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

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
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}

/** \file search.function.php
 * Generic functions for Search Engine
 */
// Get search_option array
$SEARCH_OPTION=getSearchOptions();


function cleanSearchOption($type,$action='r'){
	global $CFG_GLPI,$SEARCH_OPTION;
	$options=$SEARCH_OPTION[$type];
	$todel=array();
	if (!haveRight('contract_infocom',$action)&&in_array($type,$CFG_GLPI["infocom_types"])){
		$todel=array_merge($todel,array('financial',25,26,27,28,29,30,37,38,50,51,52,53,54,55,56,57,58,59,120,121));
	}

	if ($type==COMPUTER_TYPE){
		if (!haveRight('networking',$action)){
			$todel=array_merge($todel,array('network',20,21,22,83,84,85));
		}
		if (!$CFG_GLPI['ocs_mode']||!haveRight('view_ocsng',$action)){
			$todel=array_merge($todel,array('ocsng',100,101,102,103));
		}
	}
	if (count($todel)){
		foreach ($todel as $ID){
			if (isset($options[$ID])){
				unset($options[$ID]);
			}
		}
	}

	return $options;
}


/**
 * Completion of the URL $_GET values with the $_SESSION values or define default values
 *
 *
 * @param $type item type to manage
 * @return nothing
 *
 */
function manageGetValuesInSearch($type=0){
	global $_GET;
	$tab=array();


	$default_values["start"]=0;
	$default_values["order"]="ASC";
	$default_values["deleted"]=0;
	$default_values["distinct"]="N";
	$default_values["link"]=array();
	$default_values["field"]=array(0=>"view");
	$default_values["contains"]=array(0=>"");
	$default_values["link2"]=array();
	$default_values["field2"]=array(0=>"view");
	$default_values["contains2"]=array(0=>"");
	$default_values["type2"]="";
	$default_values["sort"]=1;

	if (isset($_GET["reset_before"])){
		if (isset($_SESSION['glpisearch'][$type])){
			unset($_SESSION['glpisearch'][$type]);
		}
		if (isset($_SESSION['glpisearchcount'][$type])){
			unset($_SESSION['glpisearchcount'][$type]);
		}
		if (isset($_SESSION['glpisearchcount2'][$type])){
			unset($_SESSION['glpisearchcount2'][$type]);
		}
		if (isset($_GET["glpisearchcount"])){
			$_SESSION["glpisearchcount"][$type]=$_GET["glpisearchcount"];
		}
	}

	if (is_array($_GET)){
		foreach ($_GET as $key => $val){
			$_SESSION['glpisearch'][$type][$key]=$val;
		}
	}

	foreach ($default_values as $key => $val){
		if (!isset($_GET[$key])){
			if (isset($_SESSION['glpisearch'][$type][$key])) {
				$_GET[$key]=$_SESSION['glpisearch'][$type][$key];
			} else {
				$_GET[$key] = $val;
			}
		}
	}

	if (!isset($_SESSION["glpisearchcount"][$type])) $_SESSION["glpisearchcount"][$type]=1;
	if (!isset($_SESSION["glpisearchcount2"][$type])) $_SESSION["glpisearchcount2"][$type]=0;

}

/**
 * Print generic search form
 *
 * 
 *
 *@param $type type to display the form
 *@param $target url to post the form
 *@param $field array of the fields selected in the search form
 *@param $contains array of the search strings
 *@param $sort the "sort by" field value
 *@param $deleted the deleted value 
 *@param $link array of the link between each search.
 *@param $distinct only display distinct items
 *@param $contains2 array of the search strings for meta items
 *@param $field2 array of the fields selected in the search form for meta items
 *@param $type2 type to display the form for meta items
 *@param $link2 array of the link between each search. for meta items
 *
 *@return nothing (diplays)
 *
 **/
function searchForm($type,$target,$field="",$contains="",$sort= "",$deleted= 0,$link="",$distinct="Y",$link2="",$contains2="",$field2="",$type2=""){
	global $LANG,$SEARCH_OPTION,$CFG_GLPI,$LINK_ID_TABLE;

	$options=cleanSearchOption($type);

	// Meta search names
	$names=array(
			COMPUTER_TYPE => $LANG["Menu"][0],
			//		NETWORKING_TYPE => $LANG["Menu"][1],
			PRINTER_TYPE => $LANG["Menu"][2],
			MONITOR_TYPE => $LANG["Menu"][3],
			PERIPHERAL_TYPE => $LANG["Menu"][16],
			SOFTWARE_TYPE => $LANG["Menu"][4],
			PHONE_TYPE => $LANG["Menu"][34],	
		    );

	echo "<form method='get' action=\"$target\">";
	echo "<table class='tab_cadre_fixe'>";
//	echo "<tr><th colspan='5'>".$LANG["search"][0].":</th></tr>";
	echo "<tr class='tab_bg_1'>";
	echo "<td>";
	echo "<table>";

	// Display normal search parameters
	for ($i=0;$i<$_SESSION["glpisearchcount"][$type];$i++){
		echo "<tr><td class='right'>";
		// First line display add / delete images for normal and meta search items
		if ($i==0){
			echo "<a href='".$CFG_GLPI["root_doc"]."/front/computer.php?add_search_count=1&amp;type=$type'><img src=\"".$CFG_GLPI["root_doc"]."/pics/plus.png\" alt='+' title='".$LANG["search"][17]."'></a>&nbsp;&nbsp;&nbsp;&nbsp;";
			if ($_SESSION["glpisearchcount"][$type]>1)
				echo "<a href='".$CFG_GLPI["root_doc"]."/front/computer.php?delete_search_count=1&amp;type=$type'><img src=\"".$CFG_GLPI["root_doc"]."/pics/moins.png\" alt='-' title='".$LANG["search"][18]."'></a>&nbsp;&nbsp;&nbsp;&nbsp;";

			if (isset($names[$type])){
				echo "<a href='".$CFG_GLPI["root_doc"]."/front/computer.php?add_search_count2=1&amp;type=$type'><img src=\"".$CFG_GLPI["root_doc"]."/pics/meta_plus.png\" alt='+' title='".$LANG["search"][19]."'></a>&nbsp;&nbsp;&nbsp;&nbsp;";
				if ($_SESSION["glpisearchcount2"][$type]>0)
					echo "<a href='".$CFG_GLPI["root_doc"]."/front/computer.php?delete_search_count2=1&amp;type=$type'><img src=\"".$CFG_GLPI["root_doc"]."/pics/meta_moins.png\" alt='-' title='".$LANG["search"][20]."'></a>&nbsp;&nbsp;&nbsp;&nbsp;";
			}
		}
		// Display link item
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

			echo "</select>&nbsp;";
		}
		// display search field
		echo "<input type='text' size='15' name=\"contains[$i]\" value=\"". (is_array($contains)&&isset($contains[$i])?stripslashes($contains[$i]):"" )."\" >";
		echo "&nbsp;";
		echo $LANG["search"][10]."&nbsp;";

		// display select box to define serach item
		echo "<select name=\"field[$i]\" size='1'>";
		echo "<option value='view' ";
		if(is_array($field)&&isset($field[$i]) && $field[$i] == "view") echo "selected";
		echo ">".$LANG["search"][11]."</option>";

		reset($options);
		$first_group=true;
		foreach ($options as $key => $val) {
			// print groups
			if (!is_array($val)){
				if (!$first_group) echo "</optgroup>";
				else $first_group=false;
				echo "<optgroup label=\"$val\">";
			}else {
				echo "<option value=\"".$key."\""; 
				if(is_array($field)&&isset($field[$i]) && $key == $field[$i]) echo "selected";
				echo ">". utf8_substr($val["name"],0,32) ."</option>\n";
			}
		}
		if (!$first_group)
			echo "</optgroup>";

		echo "<option value='all' ";
		if(is_array($field)&&isset($field[$i]) && $field[$i] == "all") echo "selected";
		echo ">".$LANG["search"][7]."</option>";

		echo "</select>&nbsp;";


		echo "</td></tr>";
	}

	// Display meta search items
	$linked=array();
	if ($_SESSION["glpisearchcount2"][$type]>0){
		// Define meta search items to linked
		switch ($type){
			case COMPUTER_TYPE :
				$linked=array(PRINTER_TYPE,MONITOR_TYPE,PERIPHERAL_TYPE,SOFTWARE_TYPE,PHONE_TYPE);
				break;
				/*			case NETWORKING_TYPE :
							$linked=array(COMPUTER_TYPE,PRINTER_TYPE,PERIPHERAL_TYPE);
							break;
				 */			case PRINTER_TYPE :
				$linked=array(COMPUTER_TYPE);
				break;
			case MONITOR_TYPE :
				$linked=array(COMPUTER_TYPE);
				break;
			case PERIPHERAL_TYPE :
				$linked=array(COMPUTER_TYPE);
				break;
			case SOFTWARE_TYPE :
				$linked=array(COMPUTER_TYPE);
				break;
			case PHONE_TYPE :
				$linked=array(COMPUTER_TYPE);
				break;
		}
	}

	if (is_array($linked)&&count($linked)>0)
		for ($i=0;$i<$_SESSION["glpisearchcount2"][$type];$i++){
			echo "<tr><td class='left'>";
			$rand=mt_rand();

			// Display link item (not for the first item)
			//if ($i>0) {
			echo "<select name='link2[$i]'>";

			echo "<option value='AND' ";
			if(is_array($link2)&&isset($link2[$i]) && $link2[$i] == "AND") echo "selected";
			echo ">AND</option>";

			echo "<option value='OR' ";
			if(is_array($link2)&&isset($link2[$i]) && $link2[$i] == "OR") echo "selected";
			echo ">OR</option>";		

			echo "<option value='AND NOT' ";
			if(is_array($link2)&&isset($link2[$i]) && $link2[$i] == "AND NOT") echo "selected";
			echo ">AND NOT</option>";

			echo "<option value='OR NOT' ";
			if(is_array($link2)&&isset($link2[$i]) && $link2[$i] == "OR NOT") echo "selected";
			echo ">OR NOT</option>";

			echo "</select>";
			//}
			// Display select of the linked item type available
			echo "<select name='type2[$i]' id='type2_".$type."_".$i."_$rand'>";
			echo "<option value='-1'>-----</option>";
			foreach ($linked as $key)
				echo "<option value='$key'>".utf8_substr($names[$key],0,20)."</option>";
			echo "</select>";

			// Ajax script for display search meat item
			echo "<span id='show_".$type."_".$i."_$rand'>&nbsp;</span>\n";	

			$params=array('type'=>'__VALUE__',
					'num'=>$i,
					'field'=>(is_array($field2)&&isset($field2[$i])?$field2[$i]:""),
					'val'=>(is_array($contains2)&&isset($contains2[$i])?$contains2[$i]:""),
	
			);
			ajaxUpdateItemOnSelectEvent("type2_".$type."_".$i."_$rand","show_".$type."_".$i."_$rand",$CFG_GLPI["root_doc"]."/ajax/updateSearch.php",$params,false);

			
			if (is_array($type2)&&isset($type2[$i])&&$type2[$i]>0){
				echo "<script type='text/javascript' >";
				echo "window.document.getElementById('type2_".$type."_".$i."_$rand').value='".$type2[$i]."';";
				echo "</script>\n";

				$params['type']=$type2[$i];
				ajaxUpdateItem("show_".$type."_".$i."_$rand",$CFG_GLPI["root_doc"]."/ajax/updateSearch.php",$params,false);
			}
			echo "</td></tr>";
		}

	echo "</table>";
	echo "</td>";

	// Display sort selection
	echo "<td>";
	echo $LANG["search"][4];
	echo "&nbsp;<select name='sort' size='1'>";
	reset($options);
	$first_group=true;
	foreach ($options as $key => $val) {
		if (!is_array($val)){
			if (!$first_group) echo "</optgroup>";
			else $first_group=false;
			echo "<optgroup label=\"$val\">";
		}else {

			echo "<option value=\"".$key."\"";
			if($key == $sort) echo " selected";
			echo ">".utf8_substr($val["name"],0,20)."</option>\n";
		}
	}
	if (!$first_group)
		echo "</optgroup>";

	echo "</select> ";
	echo "</td>";

	// Display deleted selection
	echo "<td>";
	//	echo "<table>";
	if (in_array($LINK_ID_TABLE[$type],$CFG_GLPI["deleted_tables"])){
		//echo "<tr><td>";
		dropdownYesNo("deleted",$deleted);
		echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/showdeleted.png\" alt='".$LANG["common"][3]."' title='".$LANG["common"][3]."'>";
		//echo "</td></tr>";
	}

	echo "</td>";
	// Display Reset search
	echo "<td>";
	echo "<a href='".$CFG_GLPI["root_doc"]."/front/computer.php?reset_search=reset_search&amp;type=$type' ><img title=\"".$LANG["buttons"][16]."\" alt=\"".$LANG["buttons"][16]."\" src='".$CFG_GLPI["root_doc"]."/pics/reset.png' class='calendrier'></a>";
	echo "</td>";
	// Display submit button
	echo "<td width='80' class='tab_bg_2'>";
	echo "<input type='submit' value=\"".$LANG["buttons"][0]."\" class='submit' >";
	echo "</td></tr></table>";
	// Reset to start when submit new search
	echo "<input type='hidden' name='start' value='0'>";
	echo "</form>";

}
/**
 * Generic Search and list function
 *
 *
 * Build the query, make the search and list items after a search.
 *
 *@param $target filename where to go when done.
 *@param $field array of fields in witch the search would be done
 *@param $type type to display the form
 *@param $contains array of the search strings
 *@param $distinct display only distinct items
 *@param $sort the "sort by" field value
 *@param $order ASC or DSC (for mysql query)
 *@param $start row number from witch we start the query (limit $start,xxx)
 *@param $deleted Query on deleted items or not.
 *@param $link array of the link between each search.
 *@param $contains2 array of the search strings for meta items
 *@param $field2 array of the fields selected in the search form for meta items
 *@param $type2 type to display the form for meta items
 *@param $link2 array of the link between each search. for meta items
 *
 *
 *@return Nothing (display)
 *
 **/
function showList ($type,$target,$field,$contains,$sort,$order,$start,$deleted,$link,$distinct,$link2="",$contains2="",$field2="",$type2=""){
	global $DB,$INFOFORM_PAGES,$SEARCH_OPTION,$LINK_ID_TABLE,$CFG_GLPI,$LANG;

	$limitsearchopt=cleanSearchOption($type);

	$itemtable=$LINK_ID_TABLE[$type];
	if (isset($CFG_GLPI["union_search_type"][$type])){
		$itemtable=$CFG_GLPI["union_search_type"][$type];
	}
	$LIST_LIMIT=$_SESSION["glpilist_limit"];

	// Set display type for export if define
	$output_type=HTML_OUTPUT;
	if (isset($_GET["display_type"])){
		$output_type=$_GET["display_type"];
		// Limit to 10 element
		if ($_GET["display_type"]==GLOBAL_SEARCH){
			$LIST_LIMIT=GLOBAL_SEARCH_DISPLAY_COUNT;
		}
	}

	$entity_restrict=in_array($itemtable,$CFG_GLPI["specif_entities_tables"]);

	// Define meta table where search must be done in HAVING clause
	$META_SPECIF_TABLE=array("glpi_device_ram","glpi_device_hdd","glpi_device_processor","glpi_tracking");

	$names=array(
			COMPUTER_TYPE => $LANG["Menu"][0],
			//		NETWORKING_TYPE => $LANG["Menu"][1],
			PRINTER_TYPE => $LANG["Menu"][2],
			MONITOR_TYPE => $LANG["Menu"][3],
			PERIPHERAL_TYPE => $LANG["Menu"][16],
			SOFTWARE_TYPE => $LANG["Menu"][4],
			PHONE_TYPE => $LANG["Menu"][34],
		    );	

	// Get the items to display
	$toview=addDefaultToView($type);

	// Add default items
	$query="SELECT * FROM glpi_display WHERE type='$type' AND FK_users='".$_SESSION["glpiID"]."' ORDER by rank";
	$result=$DB->query($query);
	// GET default serach options
	if ($DB->numrows($result)==0){
		$query="SELECT * FROM glpi_display WHERE type='$type' AND FK_users='0' ORDER by rank";
		$result=$DB->query($query);
	}

	if ($DB->numrows($result)>0){
		while ($data=$DB->fetch_array($result)){
			array_push($toview,$data["num"]);
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

	// Manage search on all item
	$SEARCH_ALL=array();
	if (in_array("all",$field)){
		foreach ($field as $key => $val)
			if ($val=="all"){
				array_push($SEARCH_ALL,array("contains"=>$contains[$key]));
			}
	}

	// Clean toview array
	$toview=array_unique($toview);
	foreach ($toview as $key => $val){
		if (!isset($limitsearchopt[$val])){
			unset($toview[$key]);
		}
	}
	$toview_count=count($toview);

	// Construct the request 
	//// 1 - SELECT
	$SELECT ="SELECT ".addDefaultSelect($type);

	// Add select for all toview item
	foreach ($toview as $key => $val){
		$SELECT.=addSelect($type,$val,$key,0);
	}

	//// 2 - FROM AND LEFT JOIN
	// Set reference table
	
	$FROM = " FROM ".$itemtable;


	// Init already linked tables array in order not to link a table several times
	$already_link_tables=array();
	// Put reference table
	array_push($already_link_tables,$itemtable);

	// Add default join
	$COMMONLEFTJOIN=addDefaultJoin($type,$itemtable,$already_link_tables);
	$FROM.=$COMMONLEFTJOIN;


	// Add all table for toview items
	foreach ($toview as $key => $val){
		$FROM.=addLeftJoin($type,$itemtable,$already_link_tables,$SEARCH_OPTION[$type][$val]["table"],$SEARCH_OPTION[$type][$val]["linkfield"]);
	}


	// Search all case :
	if (count($SEARCH_ALL)>0)
		foreach ($SEARCH_OPTION[$type] as $key => $val){
			// Do not search on Group Name
			if (is_array($val)){
				$FROM.=addLeftJoin($type,$itemtable,$already_link_tables,$SEARCH_OPTION[$type][$key]["table"],$SEARCH_OPTION[$type][$key]["linkfield"]);
			}
		}


	//// 3 - WHERE

	// default string
	$WHERE="";
	$COMMONWHERE = addDefaultWhere($type);
	$first=empty($COMMONWHERE);
	

	// Add deleted if item have it
	if (in_array($itemtable,$CFG_GLPI["deleted_tables"])){
		$LINK= " AND " ;
		if ($first) {$LINK=" ";$first=false;}
		$COMMONWHERE.= $LINK.$itemtable.".deleted='$deleted' ";
	}
	// Remove template items
	if (in_array($itemtable,$CFG_GLPI["template_tables"])){
		$LINK= " AND " ;
		if ($first) {$LINK=" ";$first=false;}
		$COMMONWHERE.= $LINK.$itemtable.".is_template='0' ";
	}

	// Add Restrict to current entities
	if ($entity_restrict){
		$LINK= " AND " ;
		if ($first) {$LINK=" ";$first=false;}

		$COMMONWHERE.=getEntitiesRestrictRequest($LINK,$itemtable);
	}
	$first=true;
	// Add search conditions
	// If there is search items
	if ($_SESSION["glpisearchcount"][$type]>0&&count($contains)>0) {
		$i=0;
		for ($key=0;$key<$_SESSION["glpisearchcount"][$type];$key++){
			// if real search (strlen >0) and not all and view search
			if (isset($contains[$key])&&strlen($contains[$key])>0&&$field[$key]!="all"&&$field[$key]!="view"){
				$LINK=" ";
				$NOT=0;
				$tmplink="";
				if (is_array($link)&&isset($link[$key])){
					if (ereg("NOT",$link[$key])){
						$tmplink=" ".ereg_replace(" NOT","",$link[$key]);
						$NOT=1;
					} else {
						$tmplink=" ".$link[$key];
					}
				} else {
					$tmplink=" AND ";
				}
			
				// Manage Link if not first item
				if (!$first||$i>0) {
					$LINK=$tmplink;
				}
				// Add Where clause if not to be done ine HAVING CLAUSE
				if (!in_array($SEARCH_OPTION[$type][$field[$key]]["table"],$META_SPECIF_TABLE)){
					$WHERE.= addWhere($LINK,$NOT,$type,$field[$key],$contains[$key]);
					$i++;
				}
				// if real search (strlen >0) and view search
			} else if (isset($contains[$key])&&strlen($contains[$key])>0&&$field[$key]=="view"){
				$LINK=" OR ";
				$NOT=0;
				$globallink=" AND ";
				if (is_array($link)&&isset($link[$key])){
					switch ($link[$key]){
						case "AND";
							$LINK=" OR ";
							$globallink=" AND ";
							break;
						case "AND NOT";
							$LINK=" AND ";
							$NOT=1;
							$globallink=" AND ";
							break;
						case "OR";
							$LINK=" OR ";
							$globallink=" OR ";
							break;
						case "OR NOT";
							$LINK=" AND ";
							$NOT=1;
							$globallink=" OR ";
							break;
					}
				} else {
					$tmplink=" AND ";
				}

				// Manage Link if not first item
				if (!$first||$i>0) {
					$WHERE.=$globallink;
				}

				$WHERE.= " ( ";
				$first2=true;
				foreach ($toview as $key2 => $val2){
					// Add Where clause if not to be done ine HAVING CLAUSE
					if (!in_array($SEARCH_OPTION[$type][$val2]["table"],$META_SPECIF_TABLE)){
						$tmplink=$LINK;
						if ($first2) {
							$tmplink=" ";
							$first2=false;
						}
						$WHERE.= addWhere($tmplink,$NOT,$type,$val2,$contains[$key]);
					}
				}
				$WHERE.=" ) ";
				$i++;
				// if real search (strlen >0) and all search
			} else if (isset($contains[$key])&&strlen($contains[$key])>0&&$field[$key]=="all"){

				$LINK=" OR ";
				$NOT=0;
				$globallink=" AND ";
				if (is_array($link)&&isset($link[$key])){
					switch ($link[$key]){
						case "AND";
							$LINK=" OR ";
							$globallink=" AND ";
							break;
						case "AND NOT";
							$LINK=" AND ";
							$NOT=1;
							$globallink=" AND ";
							break;
						case "OR";
							$LINK=" OR ";
							$globallink=" OR ";
							break;
						case "OR NOT";
							$LINK=" AND ";
							$NOT=1;
							$globallink=" OR ";
							break;
					}
				} else {
					$tmplink=" AND ";
				}

				// Manage Link if not first item
				if (!$first||$i>0) {
					$WHERE.=$globallink;
				}


				$WHERE.= " ( ";
				$first2=true;

				foreach ($SEARCH_OPTION[$type] as $key2 => $val2)
					if (is_array($val2)){
						// Add Where clause if not to be done ine HAVING CLAUSE
						if (!in_array($val2["table"],$META_SPECIF_TABLE)){
							$tmplink=$LINK;
							if ($first2) {
								$tmplink=" ";
								$first2=false;
							}
							$WHERE.= addWhere($tmplink,$NOT,$type,$key2,$contains[$key]);
						}
					}

				$WHERE.=")";
				$i++;
			} 
		}
	}

	if (!empty($WHERE)||!empty($COMMONWHERE)){
		if (!empty($COMMONWHERE)){
			$WHERE=' WHERE '.$COMMONWHERE.(!empty($WHERE)?' AND ( '.$WHERE.' )':'');
		} else {
			$WHERE=' WHERE  '.$WHERE.' ';
		}
		$first=false;
	} else {
		$WHERE=" WHERE ";
	}

	//// 4 - ORDER
	$ORDER="ORDER BY ID";
	foreach($toview as $key => $val){
		if ($sort==$val){
			$ORDER= addOrderBy($type,$sort,$order,$key);	
		}
	}




	//// 5 - META SEARCH
	// Preprocessing
	if ($_SESSION["glpisearchcount2"][$type]>0&&is_array($type2)){

		// a - SELECT 
		for ($i=0;$i<$_SESSION["glpisearchcount2"][$type];$i++)
			if (isset($type2[$i])&&$type2[$i]>0&&isset($contains2[$i])&&strlen($contains2[$i]))	{
				$SELECT.=addSelect($type2[$i],$field2[$i],$i,1,$type2[$i]);		
			}

		// b - ADD LEFT JOIN 
		// Already link meta table in order not to linked a table several times
		$already_link_tables2=array();
		// Link reference tables
		for ($i=0;$i<$_SESSION["glpisearchcount2"][$type];$i++)
			if (isset($type2[$i])&&$type2[$i]>0&&isset($contains2[$i])&&strlen($contains2[$i])) {
				if (!in_array($LINK_ID_TABLE[$type2[$i]],$already_link_tables2)){
					$FROM.=addMetaLeftJoin($type,$type2[$i],$already_link_tables2,($contains2[$i]=="NULL"));
				}
			}
		// Link items tables
		for ($i=0;$i<$_SESSION["glpisearchcount2"][$type];$i++)
			if (isset($type2[$i])&&$type2[$i]>0&&isset($contains2[$i])&&strlen($contains2[$i])) {
				if (!in_array($SEARCH_OPTION[$type2[$i]][$field2[$i]]["table"]."_".$type2[$i],$already_link_tables2)){
					$FROM.=addLeftJoin($type2[$i],$LINK_ID_TABLE[$type2[$i]],$already_link_tables2,$SEARCH_OPTION[$type2[$i]][$field2[$i]]["table"],$SEARCH_OPTION[$type2[$i]][$field2[$i]]["linkfield"],0,1,$type2[$i]);				
				}

			}

	}


	//// 6 - Add item ID

	// Add ID to the select
	if (!empty($itemtable)){
		$SELECT.=$itemtable.".ID AS ID ";
	}

	//// 7 - Manage GROUP BY
	$GROUPBY="";
	// Meta Search / Search All / Count tickets
	if ($_SESSION["glpisearchcount2"][$type]>0||count($SEARCH_ALL)>0||in_array(60,$toview)){
		$GROUPBY=" GROUP BY $itemtable.ID";
	}

	// Specific case of group by : multiple links with the reference table
	if (empty($GROUPBY)){
		foreach ($toview as $key2 => $val2){
			if (empty($GROUPBY)&&(($val2=="all")
						||($type==COMPUTER_TYPE&&ereg("glpi_device",$SEARCH_OPTION[$type][$val2]["table"]))
						||(ereg("glpi_contracts",$SEARCH_OPTION[$type][$val2]["table"]))
						||($SEARCH_OPTION[$type][$val2]["table"]=="glpi_licenses")
						||($SEARCH_OPTION[$type][$val2]["table"]=="glpi_networking_ports")
						||($SEARCH_OPTION[$type][$val2]["table"]=="glpi_dropdown_netpoint")
						||($SEARCH_OPTION[$type][$val2]["table"]=="glpi_registry")
						||($type==USER_TYPE)
						||($type==CONTACT_TYPE&&$SEARCH_OPTION[$type][$val2]["table"]=="glpi_enterprises")
						||($type==ENTERPRISE_TYPE&&$SEARCH_OPTION[$type][$val2]["table"]=="glpi_contacts")
					     )) 

				$GROUPBY=" GROUP BY $itemtable.ID ";
		}
	}

	// Specific search define in META_SPECIF_TABLE : only for computer search (not meta search)
	if ($type==COMPUTER_TYPE){
		// For each real search item 
		foreach($contains as $key => $val)
			if (strlen($val)>0){
				// If not all and view search
				if ($field[$key]!="all"&&$field[$key]!="view"){
					foreach ($toview as $key2 => $val2){

						if (($val2==$field[$key])&&in_array($SEARCH_OPTION[$type][$val2]["table"],$META_SPECIF_TABLE)){
							if (!isset($link[$key])) {
								$link[$key]="AND";
							}
							
							$GROUPBY=addGroupByHaving($GROUPBY,$SEARCH_OPTION[$type][$field[$key]]["table"].".".$SEARCH_OPTION[$type][$field[$key]]["field"],strtolower($contains[$key]),$key2,0,$link[$key]);
						}
					}
				}
			}
	} 

	// Specific search for others item linked  (META search)
	if (is_array($type2)){
		for ($key=0;$key<$_SESSION["glpisearchcount2"][$type];$key++){
			if (isset($type2[$key])&&$type2[$key]>0&&isset($contains2[$key])&&strlen($contains2[$key]))
			{
				$LINK="";
				if (isset($link2[$key])) $LINK=$link2[$key];
				if ($SEARCH_OPTION[$type2[$key]][$field2[$key]]["meta"]==1){		
					$GROUPBY=addGroupByHaving($GROUPBY,$SEARCH_OPTION[$type2[$key]][$field2[$key]]["table"].".".$SEARCH_OPTION[$type2[$key]][$field2[$key]]["field"],strtolower($contains2[$key]),$key,1,$LINK);
				} else { // Meta Where Search
					$LINK=" ";
					$NOT=0;
					// Manage Link if not first item
					if (!$first) {
						if (is_array($link2)&&isset($link2[$key])&&ereg("NOT",$link2[$key])){
							$LINK=" ".ereg_replace(" NOT","",$link2[$key]);
							$NOT=1;
						}
						else if (is_array($link2)&&isset($link2[$key]))
							$LINK=" ".$link2[$key];
						else $LINK=" AND ";
					}

					$WHERE.= addWhere($LINK,$NOT,$type2[$key],$field2[$key],$contains2[$key],1);
				}
			}
		}
	}

	// If no research limit research to display item and compute number of item using simple request
	$nosearch=true;
	for ($i=0;$i<$_SESSION["glpisearchcount"][$type];$i++)
		if (isset($contains[$i])&&strlen($contains[$i])>0) $nosearch=false;

	if ($_SESSION["glpisearchcount2"][$type]>0)	
		$nosearch=false;

	$LIMIT="";
	$numrows=0;
	//No search : count number of items using a simple count(ID) request and LIMIT search
	if ($nosearch) {
		$LIMIT= " LIMIT $start, ".$LIST_LIMIT;

		$query_num="SELECT count(DISTINCT $itemtable.ID) FROM ".$itemtable.$COMMONLEFTJOIN;
		
		$first=true;

		if (!empty($COMMONWHERE)){
			$LINK= " AND " ;
			if ($first) {$LINK=" WHERE ";$first=false;}
			$query_num.= $LINK.$COMMONWHERE;
		}

		// Union Search :
		if (isset($CFG_GLPI["union_search_type"][$type])){
			$tmpquery=$query_num;
			$numrows=0;
			foreach ($CFG_GLPI[$CFG_GLPI["union_search_type"][$type]] as $ctype){
				if (haveTypeRight($ctype,'r')){
					// No ref table case
					if (empty($LINK_ID_TABLE[$type])){
						$query_num=ereg_replace($CFG_GLPI["union_search_type"][$type],$LINK_ID_TABLE[$ctype],$tmpquery);
						// State case :
						if ($type==STATE_TYPE){
							$query_num.=" AND ".$LINK_ID_TABLE[$ctype].".state > 0 ";
						}
					} else {// Ref table case
						$replace="FROM ".$LINK_ID_TABLE[$type]." INNER JOIN ".$LINK_ID_TABLE[$ctype]." ON (".$LINK_ID_TABLE[$type].".id_device = ".$LINK_ID_TABLE[$ctype].".ID AND ".$LINK_ID_TABLE[$type].".device_type='$ctype')";
						$query_num=ereg_replace("FROM ".$CFG_GLPI["union_search_type"][$type],$replace,$tmpquery);
						$query_num=ereg_replace($CFG_GLPI["union_search_type"][$type],$LINK_ID_TABLE[$ctype],$query_num);
					}
					$result_num = $DB->query($query_num);
					$numrows+= $DB->result($result_num,0,0);
				}
			}
		} else {
			
			$result_num = $DB->query($query_num);
			$numrows= $DB->result($result_num,0,0);
		}
	}
	
	// If export_all reset LIMIT condition
	if (isset($_GET['export_all'])) $LIMIT="";

	// Reset WHERE if empty
	if ($WHERE == " WHERE ") $WHERE="";


	$DB->query("SET SESSION group_concat_max_len = 9999999;");

	// Create QUERY
	if (isset($CFG_GLPI["union_search_type"][$type])){
		$first=true;
		$QUERY="";
		foreach ($CFG_GLPI[$CFG_GLPI["union_search_type"][$type]] as $ctype){
			if (haveTypeRight($ctype,'r')){
				if ($first){
					$first=false;
				} else {
					$QUERY.=" UNION ";
				}
				$tmpquery="";
				// No ref table case
				if (empty($LINK_ID_TABLE[$type])){
						$tmpquery=$SELECT.", $ctype AS TYPE ".$FROM.$WHERE;
						$tmpquery=ereg_replace($CFG_GLPI["union_search_type"][$type],$LINK_ID_TABLE[$ctype],$tmpquery);
						// State case :
						if ($type==STATE_TYPE){
							$tmpquery.=" AND ".$LINK_ID_TABLE[$ctype].".state > 0 ";
						}
				} else {// Ref table case
						$tmpquery=$SELECT.", $ctype AS TYPE, ".$LINK_ID_TABLE[$type].".ID AS refID ".$FROM.$WHERE;
						$replace="FROM ".$LINK_ID_TABLE[$type]." INNER JOIN ".$LINK_ID_TABLE[$ctype]." ON (".$LINK_ID_TABLE[$type].".id_device = ".$LINK_ID_TABLE[$ctype].".ID AND ".$LINK_ID_TABLE[$type].".device_type='$ctype')";
						$tmpquery=ereg_replace("FROM ".$CFG_GLPI["union_search_type"][$type],$replace,$tmpquery);
						$tmpquery=ereg_replace($CFG_GLPI["union_search_type"][$type],$LINK_ID_TABLE[$ctype],$tmpquery);
				}
				// SOFTWARE HACK
				if ($ctype==SOFTWARE_TYPE){
					$tmpquery=ereg_replace("glpi_software.serial","''",$tmpquery);
					$tmpquery=ereg_replace("glpi_software.otherserial","''",$tmpquery);
				}

				$QUERY.=$tmpquery;
			}
		}
		$QUERY.=ereg_replace($CFG_GLPI["union_search_type"][$type].".","",$ORDER).$LIMIT;
	} else {
		$QUERY=$SELECT.$FROM.$WHERE.$GROUPBY.$ORDER.$LIMIT;
	}

	//echo $QUERY."<br>\n";

	// Get it from database and DISPLAY
	if ($result = $DB->query($QUERY)) {

		// if real search or complete export : get numrows from request 
		if (!$nosearch||isset($_GET['export_all'])) 
			$numrows= $DB->numrows($result);

		// Contruct Pager parameters
		$globallinkto=getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains).getMultiSearchItemForLink("field2",$field2).getMultiSearchItemForLink("contains2",$contains2).getMultiSearchItemForLink("type2",$type2).getMultiSearchItemForLink("link2",$link2);

		$parameters="sort=$sort&amp;order=$order".$globallinkto;


		if ($output_type==GLOBAL_SEARCH){
			$ci = new CommonItem();	
			$ci->setType($type);
			echo "<div class='center'><h2>".$ci->getType($type);
			// More items
			if ($numrows>$start+GLOBAL_SEARCH_DISPLAY_COUNT){
				echo " <a href='$target?$parameters'>".$LANG["search"][7]."</a>";
			}
			echo "</h2></div>";
		}


		// If the begin of the view is before the number of items
		if ($start<$numrows) {

			// Display pager only for HTML
			if ($output_type==HTML_OUTPUT){
				printPager($start,$numrows,$target,$parameters,$type);
			}

			// Form to massive actions
			$isadmin=(haveTypeRight($type,"w")||(in_array($type,$CFG_GLPI["infocom_types"])&&haveTypeRight(INFOCOM_TYPE,"w")));

			if ($isadmin&&$output_type==HTML_OUTPUT){
				echo "<form method='post' name='massiveaction_form' id='massiveaction_form' action=\"".$CFG_GLPI["root_doc"]."/front/massiveaction.php\">";
			}

			// Compute number of columns to display
			// Add toview elements
			$nbcols=$toview_count;
			// Add meta search elements if real search (strlen>0) or only NOT search
			if ($_SESSION["glpisearchcount2"][$type]>0&&is_array($type2))
				for ($i=0;$i<$_SESSION["glpisearchcount2"][$type];$i++)
					if (isset($type2[$i])&&isset($contains2[$i])&&strlen($contains2[$i])>0&&$type2[$i]>0&&(!isset($link2[$i])||!ereg("NOT",$link2[$i]))) {
						$nbcols++;
					}

			if ($output_type==HTML_OUTPUT)// HTML display - massive modif
				$nbcols++;

			// Define begin and end var for loop
			// Search case
			$begin_display=$start;
			$end_display=$start+$LIST_LIMIT;
			// No search Case
			if ($nosearch){
				$begin_display=0;
				$end_display=min($numrows-$start,$LIST_LIMIT);
			}
			// Export All case
			if (isset($_GET['export_all'])) {
				$begin_display=0;
				$end_display=$numrows;
			}


			// Display List Header
			echo displaySearchHeader($output_type,$end_display-$begin_display+1,$nbcols);
			// New Line for Header Items Line
			echo displaySearchNewLine($output_type);
			$header_num=1;

			if ($output_type==HTML_OUTPUT){// HTML display - massive modif
				$search_config="";
				if (haveRight("search_config","w")||haveRight("search_config_global","w")){
					$tmp= " class='pointer'  onClick=\"window.open('".$CFG_GLPI["root_doc"]."/front/popup.php?popup=search_config&amp;type=$type' ,'glpipopup', 'height=400, width=1000, top=100, left=100, scrollbars=yes' )\"";

					$search_config= "<img alt='".$LANG["setup"][252]."' title='".$LANG["setup"][252]."' src='".$CFG_GLPI["root_doc"]."/pics/options_search.png' ";
					$search_config.=$tmp.">";
					//$search_config.= "<img alt='".$LANG["buttons"][6]."' title='".$LANG["buttons"][6]."' src='".$CFG_GLPI["root_doc"]."/pics/moins.png' ";
					//$search_config.=$tmp.">";
				}

				echo displaySearchHeaderItem($output_type,$search_config,$header_num,"",0,$order);
			}

			// Display column Headers for toview items
			foreach ($toview as $key => $val){
				$linkto="$target?sort=".$val."&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start".$globallinkto;

				echo displaySearchHeaderItem($output_type,$SEARCH_OPTION[$type][$val]["name"],$header_num,$linkto,$sort==$val,$order);
			}

			// Display columns Headers for meta items
			if ($_SESSION["glpisearchcount2"][$type]>0&&is_array($type2))
				for ($i=0;$i<$_SESSION["glpisearchcount2"][$type];$i++)
					if (isset($type2[$i])&&$type2[$i]>0&&isset($contains2[$i])&&strlen($contains2[$i])&&(!isset($link2[$i])
								||(!ereg("NOT",$link2[$i]) || $contains2[$i]=="NULL"))) {
						echo displaySearchHeaderItem($output_type,$names[$type2[$i]]." - ".$SEARCH_OPTION[$type2[$i]][$field2[$i]]["name"],$header_num);
					}
			// Add specific column Header
			if ($type==SOFTWARE_TYPE)
				echo displaySearchHeaderItem($output_type,$LANG["software"][11],$header_num);
			if ($type==CARTRIDGE_TYPE)
				echo displaySearchHeaderItem($output_type,$LANG["cartridges"][0],$header_num);	
			if ($type==CONSUMABLE_TYPE)
				echo displaySearchHeaderItem($output_type,$LANG["consumables"][0],$header_num);
			if ($type==STATE_TYPE||$type==RESERVATION_TYPE){
				echo displaySearchHeaderItem($output_type,$LANG["state"][6],$header_num);
				$ci = new CommonItem();
				
			}
			if ($type==RESERVATION_TYPE&&$output_type==HTML_OUTPUT){
				if (haveRight("reservation_central","w")){
					echo displaySearchHeaderItem($output_type,"&nbsp;",$header_num);
					echo displaySearchHeaderItem($output_type,"&nbsp;",$header_num);
				}
				echo displaySearchHeaderItem($output_type,"&nbsp;",$header_num);
			}
			// End Line for column headers		
			echo displaySearchEndLine($output_type);

			// if real search seek to begin of items to display (because of complete search)
			if (!$nosearch)
				$DB->data_seek($result,$start);

			// Define begin and end var for loop
			// Search case
			$i=$begin_display;			

			// Num of the row (1=header_line)
			$row_num=1;
			// Display Loop
			while ($i < $numrows && $i<($end_display)){
				// Column num
				$item_num=1;
				// Get data and increment loop variables
				$data=$DB->fetch_assoc($result);
				$i++;
				$row_num++;
				// New line
				echo displaySearchNewLine($output_type,($i%2));


				if ($output_type==HTML_OUTPUT){// HTML display - massive modif
					$tmpcheck="";
					if ($isadmin){
						$sel="";
						if (isset($_GET["select"])&&$_GET["select"]=="all") {
							$sel="checked";
						}
						if (isset($_SESSION['glpimassiveactionselected'][$data["ID"]])){
							$sel="checked";
						}
						$tmpcheck="<input type='checkbox' name='item[".$data["ID"]."]' value='1' $sel>";
					}
					echo displaySearchItem($output_type,$tmpcheck,$item_num,$row_num,"width='10'");
				}

				// Print first element - specific case for user 
				echo displaySearchItem($output_type,giveItem($type,$SEARCH_OPTION[$type][1]["table"].".".$SEARCH_OPTION[$type][1]["field"],$data,0,$SEARCH_OPTION[$type][1]["linkfield"]),$item_num,$row_num,displayConfigItem($type,$SEARCH_OPTION[$type][1]["table"].".".$SEARCH_OPTION[$type][1]["field"]));

				// Print other toview items
				foreach ($toview as $key => $val){
					// Do not display first item
					if ($key>0){
						echo displaySearchItem($output_type,giveItem($type,$SEARCH_OPTION[$type][$val]["table"].".".$SEARCH_OPTION[$type][$val]["field"],$data,$key,$SEARCH_OPTION[$type][$val]["linkfield"]),$item_num,$row_num,displayConfigItem($type,$SEARCH_OPTION[$type][$val]["table"].".".$SEARCH_OPTION[$type][$val]["field"]));
					}
				}

				// Print Meta Item
				if ($_SESSION["glpisearchcount2"][$type]>0&&is_array($type2))
					for ($j=0;$j<$_SESSION["glpisearchcount2"][$type];$j++)
						if (isset($type2[$j])&&$type2[$j]>0&&isset($contains2[$j])&&strlen($contains2[$j])&&(!isset($link2[$j])
									||(!ereg("NOT",$link2[$j]) || $contains2[$j]=="NULL"))){

							// General case
							if (!strpos($data["META_$j"],"$$$$")){
								echo displaySearchItem($output_type,$data["META_$j"],$item_num,$row_num);
							// Case of GROUP_CONCAT item : split item and multilline display
							} else {
								$split=explode("$$$$",$data["META_$j"]);
								$count_display=0;
								$out="";
								for ($k=0;$k<count($split);$k++)
									if ($contains2[$j]=="NULL"||(strlen($contains2[$j])==0
										||preg_match('/'.$contains2[$j].'/i',$split[$k])
									)){

										if ($count_display) $out.= "<br>";
										$count_display++;
										$out.= $split[$k];
									}
								echo displaySearchItem($output_type,$out,$item_num,$row_num);

							}
						}
				// Specific column display
				if ($type==CARTRIDGE_TYPE){
					echo displaySearchItem($output_type,countCartridges($data["ID"],$data["ALARM"],$output_type),$item_num,$row_num);
				}
				if ($type==SOFTWARE_TYPE){
					echo displaySearchItem($output_type,countInstallations($data["ID"],$output_type),$item_num,$row_num);
				}		
				if ($type==CONSUMABLE_TYPE){
					echo displaySearchItem($output_type,countConsumables($data["ID"],$data["ALARM"],$output_type),$item_num,$row_num);
				}	
				if ($type==STATE_TYPE||$type==RESERVATION_TYPE){
					$ci->setType($data["TYPE"]);
					echo displaySearchItem($output_type,$ci->getType(),$item_num,$row_num);
				}	
				if ($type==RESERVATION_TYPE&&$output_type==HTML_OUTPUT){
					if (haveRight("reservation_central","w")){
						if ($data["ACTIVE"]){
							echo displaySearchItem($output_type,"<a href=\"".$CFG_GLPI["root_doc"]."/front/reservation.php?ID=".$data["refID"]."&amp;active=0\"  title='".$LANG["buttons"][42]."'><img src=\"".$CFG_GLPI["root_doc"]."/pics/moins.png\" alt='' title=''></a>",$item_num,$row_num,"class='center'");
						} else {
							echo displaySearchItem($output_type,"<a href=\"".$CFG_GLPI["root_doc"]."/front/reservation.php?ID=".$data["refID"]."&amp;active=1\"  title='".$LANG["buttons"][41]."'><img src=\"".$CFG_GLPI["root_doc"]."/pics/plus.png\" alt='' title=''></a>",$item_num,$row_num,"class='center'");
						}

						echo displaySearchItem($output_type,"<a href=\"javascript:confirmAction('".addslashes($LANG["reservation"][38])."\\n".addslashes($LANG["reservation"][39])."','".$CFG_GLPI["root_doc"]."/front/reservation.php?ID=".$data["refID"]."&amp;delete=delete')\"  title='".$LANG["reservation"][6]."'><img src=\"".$CFG_GLPI["root_doc"]."/pics/delete.png\" alt='' title=''></a>",$item_num,$row_num,"class='center'");
					}
					if ($data["ACTIVE"]){
						echo displaySearchItem($output_type,"<a href='".$target."?show=resa&amp;ID=".$data["refID"]."' title='".$LANG["reservation"][21]."'><img src=\"".$CFG_GLPI["root_doc"]."/pics/reservation-3.png\" alt='' title=''></a>",$item_num,$row_num,"class='center'");
					} else {
						echo displaySearchItem($output_type,"&nbsp;",$item_num,$row_num);
					}
				}
				// End Line
				echo displaySearchEndLine($output_type);
			}
			$title="";
			// Create title
			if ($output_type==PDF_OUTPUT) {
				if ($_SESSION["glpisearchcount"][$type]>0&&count($contains)>0) {
					for ($key=0;$key<$_SESSION["glpisearchcount"][$type];$key++){
						if (strlen($contains[$key])){
							if (isset($link[$key])) $title.=" ".$link[$key]." ";
							switch ($field[$key]){
								case "all":
									$title.=$LANG["search"][7];
								break;
								case "view":
									$title.=$LANG["search"][11];
								break;
								default :
								$title.=$SEARCH_OPTION[$type][$field[$key]]["name"];
								break;
							}
							$title.=" = ".$contains[$key];
						}
					}
				}
				if ($_SESSION["glpisearchcount2"][$type]>0&&count($contains2)>0) {
					for ($key=0;$key<$_SESSION["glpisearchcount2"][$type];$key++){
						if (strlen($contains2[$key])){
							if (isset($link2[$key])) $title.=" ".$link2[$key]." ";
							$title.=$names[$type2[$key]]."/";
							$title.=$SEARCH_OPTION[$type2[$key]][$field2[$key]]["name"];
							$title.=" = ".$contains2[$key];
						}
					}
				}
			}

			// Display footer
			echo displaySearchFooter($output_type,$title);


			// Delete selected item
			if ($output_type==HTML_OUTPUT){
				if ($isadmin){
					
					echo "<table width='80%'>";
					echo "<tr><td><img src=\"".$CFG_GLPI["root_doc"]."/pics/arrow-left.png\" alt=''></td><td class='center'><a onclick= \"if ( markAllRows('massiveaction_form') ) return false;\" href='".$_SERVER['PHP_SELF']."?select=all' >".$LANG["buttons"][18]."</a></td>";
	
					echo "<td>/</td><td class='center'><a onclick= \"if ( unMarkAllRows('massiveaction_form') ) return false;\" href='".$_SERVER['PHP_SELF']."?select=none'>".$LANG["buttons"][19]."</a>";
					echo "</td><td class='left' width='80%'>";
					dropdownMassiveAction($type,$deleted);
					echo "</td>";
					echo "</table>";
	
					
					// End form for delete item
					echo "</form>";
				} else {
					echo "<br>";
				}
			}

			if ($output_type==HTML_OUTPUT) // In case of HTML display
				printPager($start,$numrows,$target,$parameters);

		} else {
			echo displaySearchError($output_type);

		}
	} else {
		echo $DB->error();
	}
	// Clean selection 
	$_SESSION['glpimassiveactionselected']=array();
}



/**
 * Generic Function to add GROUP BY to a request
 *
 *
 *@param $field field to add
 *@param $GROUPBY group by strign to complete
 *@param $val value search
 *@param $num item number 
 *@param $meta is it a meta item ?
 *@param $link link to use 
 *
 *
 *@return select string
 *
 **/
function addGroupByHaving($GROUPBY,$field,$val,$num,$meta=0,$link=""){

	$NOT=0;
	if (ereg("NOT",$link)){
		$NOT=1;
		$link=ereg_replace(" NOT","",$link);
	}

	if (empty($link)) $link="AND";

	$NAME="ITEM_";
	if ($meta) $NAME="META_";

	if (!ereg("GROUP BY ID",$GROUPBY)) $GROUPBY=" GROUP BY ID ";


	if (!ereg("$NAME$num",$GROUPBY)) {
		if (ereg("HAVING",$GROUPBY)) $GROUPBY.=" ".$link." ";
		else $GROUPBY.=" HAVING ";

		switch ($field){
			case "glpi_tracking.count" :
				$search=array("/\&lt;/","/\&gt;/");
				$replace=array("<",">");
				$val=preg_replace($search,$replace,$val);
		
				if (ereg("([<>])([=]*)[[:space:]]*([0-9]*)",$val,$regs)){
					if ($NOT){
						if ($regs[1]=='<') {
							$regs[1]='>';
						} else {
							$regs[1]='<';
						}
					} 
					$regs[1].=$regs[2];
					$GROUPBY.= " ($NAME$num ".$regs[1]." ".$regs[3]." ) ";
				} else {
					if (!$NOT){
						$GROUPBY.=" ( $NAME$num = ".(intval($val)).") ";
					} else {
						$GROUPBY.=" ( $NAME$num <> ".(intval($val)).") ";
					}
				}
			break;

			case "glpi_device_ram.specif_default" :
			case "glpi_device_processor.specif_default" :
			case "glpi_device_hdd.specif_default" :
				$search=array("/\&lt;/","/\&gt;/");
				$replace=array("<",">");
				$val=preg_replace($search,$replace,$val);
		
				if (ereg("([<>])([=]*)[[:space:]]*([0-9]*)",$val,$regs)){
					if ($NOT){
						if ($regs[1]=='<') {
							$regs[1]='>';
						} else {
							$regs[1]='<';
						}
					} 
					$regs[1].=$regs[2];

					$GROUPBY.= " ($NAME$num ".$regs[1]." ".$regs[3]." ) ";
				} else {
					if ($field=="glpi_device_hdd.specif_default"){
						$larg=1000;
					} else {
						$larg=100;
					}
					if (!$NOT){
						$GROUPBY.=" ( $NAME$num < ".(intval($val)+$larg)." AND $NAME$num > ".(intval($val)-$larg)." ) ";
					} else {
						$GROUPBY.=" ( $NAME$num > ".(intval($val)+$larg)." OR $NAME$num < ".(intval($val)-$larg)." ) ";
					}
				}
			break;
			break;
			default :
			$GROUPBY.= $NAME.$num.makeTextSearch($val,$NOT);
			break;
		}
	}
	return $GROUPBY;
}

/**
 * Generic Function to add ORDER BY to a request
 *
 *
 *@param $type ID of the device type
 *@param $ID field to add
 *@param $order order define
 *@param $key item number
 *
 *
 *@return select string
 *
 **/
function addOrderBy($type,$ID,$order,$key=0){
	global $SEARCH_OPTION,$CFG_GLPI,$PLUGIN_HOOKS;

	$table=$SEARCH_OPTION[$type][$ID]["table"];
	$field=$SEARCH_OPTION[$type][$ID]["field"];
	$linkfield=$SEARCH_OPTION[$type][$ID]["linkfield"];


	if (isset($CFG_GLPI["union_search_type"][$type])){
		return " ORDER BY ITEM_$key $order ";
	}

	switch($table.".".$field){
		case "glpi_device_hdd.specif_default" :
		case "glpi_device_ram.specif_default" :
		case "glpi_device_processor.specif_default" :
		case "glpi_tracking.count" :
			return " ORDER BY ITEM_$key $order ";
		break;
		case "glpi_auth_tables.name" :
			return " ORDER BY glpi_users.auth_method, glpi_auth_ldap.name, glpi_auth_mail.name $order ";
		break;
		case "glpi_contracts.end_date":
			return " ORDER BY ADDDATE(glpi_contracts.begin_date, INTERVAL glpi_contracts.duration MONTH) $order ";
		break;
		case "glpi_infocoms.end_warranty_buy":
			return " ORDER BY ADDDATE(glpi_infocoms.buy_date, INTERVAL glpi_infocoms.warranty_duration MONTH) $order ";
		break;
		case "glpi_infocoms.end_warranty_use":
			return " ORDER BY ADDDATE(glpi_infocoms.use_date, INTERVAL glpi_infocoms.warranty_duration MONTH) $order ";
		break;
		case "glpi_contracts.expire":
			return " ORDER BY ADDDATE(glpi_contracts.begin_date, INTERVAL glpi_contracts.duration MONTH) $order ";
		break;
		case "glpi_contracts.expire_notice":
			return " ORDER BY ADDDATE(glpi_contracts.begin_date, INTERVAL (glpi_contracts.duration-glpi_contracts.notice) MONTH) $order ";
		break;
		case "glpi_users.name" :
			$linkfield="";
			if (!empty($SEARCH_OPTION[$type][$ID]["linkfield"])){
				$linkfield="_".$SEARCH_OPTION[$type][$ID]["linkfield"];
			}
			if ($type==USER_TYPE){
				return " ORDER BY ".$table.$linkfield.".$field $order";
			} else {
				return " ORDER BY ".$table.$linkfield.".realname $order, ".$table.$linkfield.".firstname $order, ".$table.$linkfield.".name $order";
			}
			break;
		case "glpi_networking_ports.ifaddr" :
            		return " ORDER BY INET_ATON($table.$field) $order ";
            	break;
		default:
			// Plugin case
			if ($type>1000){
				if (isset($PLUGIN_HOOKS['plugin_types'][$type])){
					$function='plugin_'.$PLUGIN_HOOKS['plugin_types'][$type].'_addOrderBy';
					if (function_exists($function)){
						$out=$function($type,$ID,$order,$key);
						if (!empty($out)){
							return $out;
						}
					} 
				} 
			}

			return " ORDER BY $table.$field $order ";
		break;
	}

}


/**
 * Generic Function to add default columns to view
 *
 *
 *@param $type device type
 *
 *
 *@return select string
 *
 **/
function addDefaultToView ($type){

	$toview=array();
	// Add first element (name)
	array_push($toview,1);

	return $toview;
}


/**
 * Generic Function to add default select to a request
 *
 *
 *@param $type device type
 *
 *
 *@return select string
 *
 **/
function addDefaultSelect ($type){
	switch ($type){
		case RESERVATION_TYPE:
			return "glpi_reservation_item.active as ACTIVE, ";
		break;
		case CARTRIDGE_TYPE:
			return "glpi_cartridges_type.alarm as ALARM, ";
		break;
		case CONSUMABLE_TYPE:
			return "glpi_consumables_type.alarm as ALARM, ";
		break;
		default :
			return "";
		break;
	}
}

/**
 * Generic Function to add select to a request
 *
 *
 *@param $ID ID of the item to add
 *@param $num item num in the request
 *@param $type device type
 *@param $meta is it a meta item ?
 *@param $meta_type meta type table ID
 *
 *
 *@return select string
 *
 **/
function addSelect ($type,$ID,$num,$meta=0,$meta_type=0){
	global $LINK_ID_TABLE,$SEARCH_OPTION,$PLUGIN_HOOKS;

	$table=$SEARCH_OPTION[$type][$ID]["table"];
	$field=$SEARCH_OPTION[$type][$ID]["field"];
	$addtable="";
	$pretable="";
	$NAME="ITEM";
	if ($meta) {
		$NAME="META";
		if ($LINK_ID_TABLE[$meta_type]!=$table)
			$addtable="_".$meta_type;
	}

	switch ($table.".".$field){
		case "glpi_computers.name" :
		case "glpi_printers.name" :
		case "glpi_networking.name" :
		case "glpi_phones.name" :
		case "glpi_monitors.name" :
		case "glpi_software.name" :
		case "glpi_peripherals.name" :
		case "glpi_cartridges_type.name" :
		case "glpi_consumables_type.name" :
		case "glpi_contacts.name" :
		case "glpi_type_docs.name" :
		case "glpi_links.name" :
		case "glpi_entities.name" :
		case "glpi_docs.name" :
		case "glpi_ocs_config.name" :
		case "glpi_mailgate.name" :
		case "glpi_transfers.name" :
		case "state_types.name":
		case "reservation_types.name":
			if ($meta){
				if ($table!=$LINK_ID_TABLE[$type])
					return " GROUP_CONCAT( DISTINCT ".$pretable.$table.$addtable.".".$field." SEPARATOR '$$$$') AS META_$num, ";
				else return " GROUP_CONCAT( DISTINCT ".$table.$addtable.".".$field." SEPARATOR '$$$$') AS META_$num, ";

			}
			else {
				return $table.$addtable.".".$field." AS ".$NAME."_$num, ".$table.$addtable.".ID AS ".$NAME."_".$num."_2, ";
			}
		break;


		case "glpi_enterprises.name" :
		case "glpi_enterprises_infocoms.name" :
			if ($type==CONTACT_TYPE){
				return " GROUP_CONCAT( DISTINCT ".$pretable.$table.$addtable.".".$field." SEPARATOR '$$$$') AS ITEM_$num, ";
			} else {
				return $pretable.$table.$addtable.".".$field." AS ".$NAME."_$num, ".$pretable.$table.$addtable.".website AS ".$NAME."_".$num."_2, ".$pretable.$table.$addtable.".ID AS ".$NAME."_".$num."_3, ";
			}
		break;
		// Contact for display in the enterprise item
		case "glpi_contacts.completename":
			return " GROUP_CONCAT( DISTINCT CONCAT(".$pretable.$table.$addtable.".name, ' ', ".$pretable.$table.$addtable.".firstname) SEPARATOR '$$$$') AS ITEM_$num, ";
		break;
		case "glpi_users.name" :
			$linkfield="";
		if (!empty($SEARCH_OPTION[$type][$ID]["linkfield"]))
			$linkfield="_".$SEARCH_OPTION[$type][$ID]["linkfield"];

		return $pretable.$table.$linkfield.$addtable.".".$field." AS ".$NAME."_$num, ".$pretable.$table.$linkfield.$addtable.".realname AS ".$NAME."_".$num."_2, ".$pretable.$table.$linkfield.$addtable.".ID AS ".$NAME."_".$num."_3, ".$pretable.$table.$linkfield.$addtable.".firstname AS ".$NAME."_".$num."_4,";
		break;


		case "glpi_contracts.end_date" :
			return $pretable.$table.$addtable.".begin_date AS ".$NAME."_$num, ".$pretable.$table.$addtable.".duration AS ".$NAME."_".$num."_2, ";
		break;
		case "glpi_infocoms.end_warranty_buy":
			return $pretable.$table.$addtable.".buy_date AS ".$NAME."_$num, ".$pretable.$table.$addtable.".warranty_duration AS ".$NAME."_".$num."_2, ";
		break;
		case "glpi_infocoms.end_warranty_use":
			return $pretable.$table.$addtable.".use_date AS ".$NAME."_$num, ".$pretable.$table.$addtable.".warranty_duration AS ".$NAME."_".$num."_2, ";
		break;
		case "glpi_contracts.expire_notice" : // ajout jmd
			return $pretable.$table.$addtable.".begin_date AS ".$NAME."_$num, ".$pretable.$table.$addtable.".duration AS ".$NAME."_".$num."_2, ".$pretable.$table.$addtable.".notice AS ".$NAME."_".$num."_3, ";
		break;
		case "glpi_contracts.expire" : // ajout jmd
			return $pretable.$table.$addtable.".begin_date AS ".$NAME."_$num, ".$pretable.$table.$addtable.".duration AS ".$NAME."_".$num."_2, ";
		break;
		case "glpi_device_hdd.specif_default" :
			return " SUM(DEVICE_".HDD_DEVICE.".specificity) / COUNT( DEVICE_".HDD_DEVICE.".ID) * COUNT( DISTINCT DEVICE_".HDD_DEVICE.".ID) AS ".$NAME."_".$num.", ";
		break;
		case "glpi_device_ram.specif_default" :
			return " SUM(DEVICE_".RAM_DEVICE.".specificity) / COUNT( DEVICE_".RAM_DEVICE.".ID) * COUNT( DISTINCT DEVICE_".RAM_DEVICE.".ID) AS ".$NAME."_".$num.", ";
		break;
		case "glpi_device_processor.specif_default" :
			return " SUM(DEVICE_".PROCESSOR_DEVICE.".specificity) / COUNT( DEVICE_".PROCESSOR_DEVICE.".ID) AS ".$NAME."_".$num.", ";
		break;
		case "glpi_networking_ports.ifmac" :
			if ($type==COMPUTER_TYPE)
				return " GROUP_CONCAT( DISTINCT ".$pretable.$table.$addtable.".".$field." SEPARATOR '$$$$') AS ITEM_$num, GROUP_CONCAT( DISTINCT DEVICE_".NETWORK_DEVICE.".specificity  SEPARATOR '$$$$') AS ".$NAME."_".$num."_2, ";
			else return " GROUP_CONCAT( DISTINCT ".$pretable.$table.$addtable.".".$field." SEPARATOR '$$$$') AS ".$NAME."_$num, ";
		break;
		case "glpi_profiles.name" :
			if ($type==USER_TYPE){
				return " GROUP_CONCAT( ".$pretable.$table.$addtable.".".$field." SEPARATOR '$$$$') AS ITEM_$num, 
					GROUP_CONCAT( glpi_entities.completename SEPARATOR '$$$$') AS ITEM_".$num."_2,
					GROUP_CONCAT( glpi_users_profiles.recursive SEPARATOR '$$$$') AS ITEM_".$num."_3,";
			} else {
				return $table.$addtable.".".$field." AS ITEM_$num, ";
			}
		case "glpi_entities.completename" :
			if ($type==USER_TYPE){
				return " GROUP_CONCAT( ".$pretable.$table.$addtable.".completename SEPARATOR '$$$$') AS ITEM_$num, 
					GROUP_CONCAT( glpi_profiles.name SEPARATOR '$$$$') AS ITEM_".$num."_2,
					GROUP_CONCAT( glpi_users_profiles.recursive SEPARATOR '$$$$') AS ITEM_".$num."_3,";
			} else {
				return $pretable.$table.$addtable.".completename AS ".$NAME."_$num, ".$pretable.$table.$addtable.".ID AS ".$NAME."_".$num."_2, ";
			}

		case "glpi_groups.name" :
			if ($type==USER_TYPE){
				return " GROUP_CONCAT( DISTINCT ".$pretable.$table.$addtable.".".$field." SEPARATOR '$$$$') AS ITEM_$num, ";
			} else {
				return $table.$addtable.".".$field." AS ITEM_$num, ";
			}
		break;
		case "glpi_auth_tables.name":
			return "glpi_users.auth_method AS ".$NAME."_".$num.", glpi_users.id_auth AS ".$NAME."_".$num."_2, glpi_auth_ldap".$addtable.".".$field." AS ".$NAME."_".$num."_3, glpi_auth_mail".$addtable.".".$field." AS ".$NAME."_".$num."_4, ";
		break;
		case "glpi_contracts.name" :
		case "glpi_contracts.num" :
			if ($type!=CONTRACT_TYPE){
				return " GROUP_CONCAT( DISTINCT ".$pretable.$table.$addtable.".".$field." SEPARATOR '$$$$') AS ITEM_$num, ";
			} else {
				return $table.$addtable.".".$field." AS ITEM_$num, ";
			}
		break;
		case "glpi_licenses.serial" :
		case "glpi_licenses.version" :
		case "glpi_networking_ports.ifaddr" :
		case "glpi_dropdown_netpoint.name" :
		case "glpi_registry.registry_ocs_name" :
		case "glpi_registry.registry_value" :
			return " GROUP_CONCAT( DISTINCT ".$pretable.$table.$addtable.".".$field." SEPARATOR '$$$$') AS ".$NAME."_".$num.", ";
		break;
		case "glpi_tracking.count" :
			return " COUNT(DISTINCT glpi_tracking.ID) AS ".$NAME."_".$num.", ";
		break;
		default:

			// Plugin case
			if ($type>1000){
				if (isset($PLUGIN_HOOKS['plugin_types'][$type])){
					$function='plugin_'.$PLUGIN_HOOKS['plugin_types'][$type].'_addSelect';
					if (function_exists($function)){
						$out=$function($type,$ID,$num);
						if (!empty($out)){
							return $out;
						}
					} 
				} 
			}


			if ($meta){

				if ($table!=$LINK_ID_TABLE[$type])
					return " GROUP_CONCAT( DISTINCT ".$pretable.$table.$addtable.".".$field." SEPARATOR '$$$$') AS META_$num, ";
				else return " GROUP_CONCAT( DISTINCT ".$table.$addtable.".".$field." SEPARATOR '$$$$') AS META_$num, ";

			}
			else {
				return $table.$addtable.".".$field." AS ".$NAME."_$num, ";
			}
			break;
	}

}


/**
 * Generic Function to add default where to a request
 *
 *
 *@param $type device type
 *
 *@return select string
 *
 **/
function addDefaultWhere ($type){
	switch ($type){
		// No link
		case USER_TYPE:
			// View all entities
			if (isViewAllEntities()){
				return "";
			} else {
				return getEntitiesRestrictRequest("","glpi_users_profiles");
			}
		break;
		default :
			return "";
		break;
	}
}

/**
 * Generic Function to add where to a request
 *
 *
 *@param $val item num in the request
 *@param $nott is it a negative serach ?
 *@param $link link string
 *@param $type device type
 *@param $ID ID of the item to search
 *@param $meta is a meta search (meta=2 in search.class.php)
 *
 *@return select string
 *
 **/
function addWhere ($link,$nott,$type,$ID,$val,$meta=0){
	global $LINK_ID_TABLE,$LANG,$SEARCH_OPTION,$PLUGIN_HOOKS;

	$table=$SEARCH_OPTION[$type][$ID]["table"];
	$field=$SEARCH_OPTION[$type][$ID]["field"];
	
	if ($meta&&$LINK_ID_TABLE[$type]!=$table) $table.="_".$type;

	$SEARCH=makeTextSearch($val,$nott);

	switch ($table.".".$field){
		case "glpi_users.name" :
			$linkfield="";
			if (!empty($SEARCH_OPTION[$type][$ID]["linkfield"])){
				$linkfield="_".$SEARCH_OPTION[$type][$ID]["linkfield"];
			}
			if ($type==USER_TYPE){ // glpi_users case / not link table
				return $link." ( $table$linkfield.$field $SEARCH ) ";
			} else {
				$ADD="";
				if ($nott) {
					$ADD=" OR $table$linkfield.$field IS NULL";
				}
				return $link." ( $table$linkfield.$field $SEARCH OR $table$linkfield.realname $SEARCH OR $table$linkfield.firstname $SEARCH OR CONCAT($table$linkfield.realname,' ',$table$linkfield.firstname) $SEARCH $ADD) ";
			}
			break;
		case "glpi_device_hdd.specif_default" :
		case "glpi_device_ram.specif_default" :
		case "glpi_device_processor.specif_default" :
			return $link." $table.$field ".makeTextSearch("",$nott);
			break;
		case "glpi_networking_ports.ifmac" :
			if ($type==COMPUTER_TYPE){
				$ADD="";
				if ($nott) {
					$ADD=" OR $table.$field IS NULL";
				}

				return $link." (  DEVICE_".NETWORK_DEVICE.".specificity $SEARCH OR $table.$field $SEARCH $ADD ) ";
			} else {
				if ($nott) {
					$ADD=" OR $table.$field IS NULL";
				}

				return $link." $table.$field $SEARCH $ADD";
			}
			break;

		case "glpi_infocoms.end_warranty_use" :
		case "glpi_infocoms.end_warranty_buy" :
		case "glpi_contracts.end_date" :
		case "glpi_ocs_link.last_update":
		case "glpi_ocs_link.last_ocs_update":
		case "glpi_computers.date_mod":
		case "glpi_printers.date_mod":
		case "glpi_networking.date_mod":
		case "glpi_peripherals.date_mod":
		case "glpi_software.date_mod":
		case "glpi_phones.date_mod":
		case "glpi_monitors.date_mod":
		case "glpi_contracts.begin_date":
		case "glpi_docs.date_mod":
		case "glpi_infocoms.buy_date":
		case "glpi_infocoms.use_date":
		case "state_types.date_mod":
		case "reservation_types.date_mod":
		case "glpi_users.last_login":
		case "glpi_users.date_mod":
			$date_computation=$table.".".$field;
			$interval_search=" MONTH ";
			switch ($table.".".$field){
				case "glpi_contracts.end_date":
					$date_computation=" ADDDATE($table.begin_date, INTERVAL $table.duration MONTH) ";
					break;
				case "glpi_infocoms.end_warranty_use":
					$date_computation=" ADDDATE($table.use_date, INTERVAL $table.warranty_duration MONTH) ";
					break;
				case "glpi_infocoms.end_warranty_buy":
					$date_computation=" ADDDATE($table.buy_date, INTERVAL $table.warranty_duration MONTH) ";
					break;
			}
			
			$search=array("/\&lt;/","/\&gt;/");
			$replace=array("<",">");
			$val=preg_replace($search,$replace,$val);
			if (ereg("([<>=])(.*)",$val,$regs)){
				if (is_numeric($regs[2])){
					return $link." NOW() ".$regs[1]." ADDDATE($date_computation, INTERVAL ".$regs[2]." $interval_search) ";	
				} else {
					// Reformat date if needed
					$regs[2]=preg_replace('/(\d{1,2})-(\d{1,2})-(\d{4})/','\3-\2-\1',$regs[2]);
					if (ereg('[0-9]{2,4}-[0-9]{1,2}-[0-9]{1,2}',$regs[2])){
						return $link." $date_computation ".$regs[1]." '".$regs[2]."'";
					} else {
						return "";
					}
				}
			} else { // standard search
				// Date format modification if needed
				$val=preg_replace('/(\d{1,2})-(\d{1,2})-(\d{4})/','\3-\2-\1',$val);
				$SEARCH=makeTextSearch($val,$nott);
				$ADD="";	
				if ($nott) {
					$ADD=" OR $table.$field IS NULL";
				}
				return $link." ( $date_computation $SEARCH $ADD )";
			}
			break;
		case "glpi_contracts.expire" :
			$search=array("/\&lt;/","/\&gt;/");
			$replace=array("<",">");
			$val=preg_replace($search,$replace,$val);
			if (ereg("([<>=])(.*)",$val,$regs)){
				return $link." DATEDIFF(ADDDATE($table.begin_date, INTERVAL $table.duration MONTH),CURDATE() )".$regs[1].$regs[2]." ";
				} else {
				return $link." ADDDATE($table.begin_date, INTERVAL $table.duration MONTH) $SEARCH ";		
			}
			break;
		// ajout jmd
		case "glpi_contracts.expire_notice" :
			$search=array("/\&lt;/","/\&gt;/");
			$replace=array("<",">");
			$val=preg_replace($search,$replace,$val);
			if (ereg("([<>])(.*)",$val,$regs)){
				return $link." $table.notice<>0 AND DATEDIFF(ADDDATE($table.begin_date, INTERVAL ($table.duration - $table.notice) MONTH),CURDATE() )".$regs[1].$regs[2]." ";
			} else {
				return $link." ADDDATE($table.begin_date, INTERVAL ($table.duration - $table.notice) MONTH) $SEARCH ";		
			}
			break;

		case "glpi_infocoms.value":
		case "glpi_infocoms.warranty_value":
			if (is_numeric($val)){
				$search=array("/\&lt;/","/\&gt;/");
				$replace=array("<",">");
				$val=preg_replace($search,$replace,$val);
				if (ereg("([<>])([=]*)[[:space:]]*([0-9]*)",$val,$regs)){
					if ($nott){
						if ($regs[1]=='<') {
							$regs[1]='>';
						} else {
							$regs[1]='<';
						}
					} 
						$regs[1].=$regs[2];
					
					return $link." ($table.$field ".$regs[1]." ".$regs[3]." ) ";
				} else {
	
					$interval=100;
					$ADD="";
					if ($nott&&$val!="NULL") $ADD=" OR $table.$field IS NULL";
					if ($nott){
						return $link." ($table.$field < ".intval($val)."-$interval OR $table.$field > ".intval($val)."+$interval ".$ADD." ) ";
					} else {
						return $link." (($table.$field >= ".intval($val)."-$interval AND $table.$field <= ".intval($val)."+$interval) ".$ADD." ) ";
					}
				}
			}
			break;
		case "glpi_infocoms.amort_time":
		case "glpi_infocoms.warranty_duration":
			$ADD="";
			if ($nott&&$val!="NULL") {
				$ADD=" OR $table.$field IS NULL";
			}
			if (is_numeric($val))
			{
				if ($nott){
					return $link." ($table.$field <> ".intval($val)." ".$ADD." ) ";
				} else {
					return $link." ($table.$field = ".intval($val)."  ".$ADD." ) ";
				}
			}
			break;
		case "glpi_infocoms.amort_type":
			$ADD="";
			if ($nott&&$val!="NULL") {
				$ADD=" OR $table.$field IS NULL";
			}
			if (eregi($val,getAmortTypeName(1))) {
				$val=1;
			} else if (eregi($val,getAmortTypeName(2))) {
				$val=2;
			} 
			if ($val>0){
				if ($nott){
					return $link." ($table.$field <> $val ".$ADD." ) ";
				} else {
					return $link." ($table.$field = $val  ".$ADD." ) ";
				}
			}
			break;
		case "glpi_contacts.completename":
			return $link." ($table.name $SEARCH OR $table.firstname $SEARCH ) ";
		break;
		case "glpi_auth_tables.name":
			return $link." (glpi_auth_mail.name $SEARCH OR glpi_auth_ldap.name $SEARCH ) ";
		break;
		default:

			
			// Plugin case
			if ($type>1000){
				if (isset($PLUGIN_HOOKS['plugin_types'][$type])){
					$function='plugin_'.$PLUGIN_HOOKS['plugin_types'][$type].'_addWhere';
					if (function_exists($function)){
						$out=$function($link,$nott,$type,$ID,$val);
						if (!empty($out)){
							return $out;
						}
					} 
				} 
			}

			$ADD="";	
			if (($nott&&$val!="NULL")||$val=='^$') {
				$ADD=" OR $table.$field IS NULL";
			}
			
			return $link." ($table.$field $SEARCH ".$ADD." ) ";
			break;
	}

}


/**
 * Generic Function to display Items
 *
 *
 *@param $field field which have a specific display type
 *@param $type device type
 *
 *
 *@return string to print
 *
 **/
function displayConfigItem ($type,$field){

	switch ($field){
		case "glpi_ocs_link.last_update":
		case "glpi_ocs_link.last_ocs_update":
		case "glpi_computers.date_mod":
		case "glpi_printers.date_mod":
		case "glpi_networking.date_mod":
		case "glpi_peripherals.date_mod":
		case "glpi_phones.date_mod":
		case "glpi_software.date_mod":
		case "glpi_monitors.date_mod":
		case "glpi_docs.date_mod":
		case "glpi_ocs_config.date_mod" :
		case "glpi_users.last_login":
		case "glpi_users.date_mod":	
				return " class='center'";
			break;
		default:
			return "";
			break;
	}

}

/**
 * Generic Function to display Items
 *
 *
 *@param $field field to add
 *@param $data array containing data results
 *@param $num item num in the request
 *@param $type device type
 *@param $linkfield field used to link
 *
 *
 *@return string to print
 *
 **/
function giveItem ($type,$field,$data,$num,$linkfield=""){
	global $CFG_GLPI,$INFOFORM_PAGES,$CFG_GLPI,$LANG,$LINK_ID_TABLE,$PLUGIN_HOOKS;


	if (isset($CFG_GLPI["union_search_type"][$type])){
		return giveItem ($data["TYPE"],ereg_replace($CFG_GLPI["union_search_type"][$type],$LINK_ID_TABLE[$data["TYPE"]],$field),$data,$num,$linkfield);
	}


	switch ($field){
		case "glpi_computers.name" :
			$out= "<a href=\"".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[COMPUTER_TYPE]."?ID=".$data["ITEM_".$num."_2"]."\">";
			$out.= $data["ITEM_$num"];
			if ($CFG_GLPI["view_ID"]||empty($data["ITEM_$num"])) {
				$out.= " (".$data["ITEM_".$num."_2"].")";
			}
			$out.= "</a>";
			return $out;
		break;
		case "glpi_printers.name" :
			$out= "<a href=\"".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[PRINTER_TYPE]."?ID=".$data["ITEM_".$num."_2"]."\">";
			$out.= $data["ITEM_$num"];
			if ($CFG_GLPI["view_ID"]||empty($data["ITEM_$num"])) {
				$out.= " (".$data["ITEM_".$num."_2"].")";
			}
			$out.= "</a>";
			return $out;
		break;
		case "glpi_networking.name" :
			$out= "<a href=\"".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[NETWORKING_TYPE]."?ID=".$data["ITEM_".$num."_2"]."\">";
			$out.= $data["ITEM_$num"];
			if ($CFG_GLPI["view_ID"]||empty($data["ITEM_$num"])) {
				$out.= " (".$data["ITEM_".$num."_2"].")";
			}
			$out.= "</a>";
			return $out;
		break;
		case "glpi_phones.name" :
			$out= "<a href=\"".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[PHONE_TYPE]."?ID=".$data["ITEM_".$num."_2"]."\">";
			$out.= $data["ITEM_$num"];
			if ($CFG_GLPI["view_ID"]||empty($data["ITEM_$num"])) {
				$out.= " (".$data["ITEM_".$num."_2"].")";
			}
			$out.= "</a>";
			return $out;
		break;
		case "glpi_monitors.name" :
			$out= "<a href=\"".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[MONITOR_TYPE]."?ID=".$data["ITEM_".$num."_2"]."\">";
			$out.= $data["ITEM_$num"];
			if ($CFG_GLPI["view_ID"]||empty($data["ITEM_$num"])) {
				$out.= " (".$data["ITEM_".$num."_2"].")";
			}
			$out.= "</a>";
			return $out;
		break;
		case "glpi_software.name" :
			$out= "<a href=\"".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[SOFTWARE_TYPE]."?ID=".$data["ITEM_".$num."_2"]."\">";
			$out.= $data["ITEM_$num"];
			if ($CFG_GLPI["view_ID"]||empty($data["ITEM_$num"])) {
				$out.= " (".$data["ITEM_".$num."_2"].")";
			}
			$out.= "</a>";
			return $out;
		break;
		case "glpi_peripherals.name" :
			$out= "<a href=\"".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[PERIPHERAL_TYPE]."?ID=".$data["ITEM_".$num."_2"]."\">";
			$out.= $data["ITEM_$num"];
			if ($CFG_GLPI["view_ID"]||empty($data["ITEM_$num"])) {
				$out.= " (".$data["ITEM_".$num."_2"].")";
			}
			$out.= "</a>";
			return $out;
		break;
		case "glpi_cartridges_type.name" :
			$out= "<a href=\"".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[CARTRIDGE_TYPE]."?ID=".$data["ITEM_".$num."_2"]."\">";
			$out.= $data["ITEM_$num"];
			if ($CFG_GLPI["view_ID"]||empty($data["ITEM_$num"])) {
				$out.= " (".$data["ITEM_".$num."_2"].")";
			}
			$out.= "</a>";
			return $out;
		break;
		case "glpi_consumables_type.name" :
			$out= "<a href=\"".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[CONSUMABLE_TYPE]."?ID=".$data["ITEM_".$num."_2"]."\">";
			$out.= $data["ITEM_$num"];
			if ($CFG_GLPI["view_ID"]||empty($data["ITEM_$num"])) {
				$out.= " (".$data["ITEM_".$num."_2"].")";
			}
			$out.= "</a>";
			return $out;
		break;
		case "glpi_contacts.name" :
			$out= "<a href=\"".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[CONTACT_TYPE]."?ID=".$data["ITEM_".$num."_2"]."\">";
			$out.= $data["ITEM_$num"];
			if ($CFG_GLPI["view_ID"]||empty($data["ITEM_$num"])) {
				$out.= " (".$data["ITEM_".$num."_2"].")";
			}
			$out.= "</a>";
			return $out;
		break;
		case "glpi_type_docs.name" :
			$out= "<a href=\"".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[TYPEDOC_TYPE]."?ID=".$data["ITEM_".$num."_2"]."\">";
			$out.= $data["ITEM_$num"];
			if ($CFG_GLPI["view_ID"]||empty($data["ITEM_$num"])) {
				$out.= " (".$data["ITEM_".$num."_2"].")";
			}
			$out.= "</a>";
			return $out;
		break;
		case "glpi_links.name" :
			$out= "<a href=\"".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[LINK_TYPE]."?ID=".$data["ITEM_".$num."_2"]."\">";
			$out.= $data["ITEM_$num"];
			if ($CFG_GLPI["view_ID"]||empty($data["ITEM_$num"])) {
				$out.= " (".$data["ITEM_".$num."_2"].")";
			}
			$out.= "</a>";
			return $out;
		break;
		case "glpi_docs.name" :
			$out= "<a href=\"".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[DOCUMENT_TYPE]."?ID=".$data["ITEM_".$num."_2"]."\">";
			$out.= $data["ITEM_$num"];
			if ($CFG_GLPI["view_ID"]||empty($data["ITEM_$num"])) {
				$out.= " (".$data["ITEM_".$num."_2"].")";
			}
			$out.= "</a>";
			return $out;
		break;
		case "glpi_ocs_config.name" :
			$out= "<a href=\"".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[OCSNG_TYPE]."?ID=".$data["ITEM_".$num."_2"]."\">";
			$out.= $data["ITEM_$num"];
			if ($CFG_GLPI["view_ID"]||empty($data["ITEM_$num"])) {
				$out.= " (".$data["ITEM_".$num."_2"].")";
			}
			$out.= "</a>";
			return $out;
		break;
		case "glpi_entities.name" :
			$out= "<a href=\"".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[ENTITY_TYPE]."?ID=".$data["ITEM_".$num."_2"]."\">";
			$out.= $data["ITEM_$num"];
			if ($CFG_GLPI["view_ID"]||empty($data["ITEM_$num"])) {
				$out.= " (".$data["ITEM_".$num."_2"].")";
			}
			$out.= "</a>";
			return $out;
		break;
		case "glpi_mailgate.name" :
			$out= "<a href=\"".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[MAILGATE_TYPE]."?ID=".$data["ITEM_".$num."_2"]."\">";
			$out.= $data["ITEM_$num"];
			if ($CFG_GLPI["view_ID"]||empty($data["ITEM_$num"])) {
				$out.= " (".$data["ITEM_".$num."_2"].")";
			}
			$out.= "</a>";
			return $out;
		break;
		case "glpi_transfers.name" :
			$out= "<a href=\"".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[TRANSFER_TYPE]."?ID=".$data["ITEM_".$num."_2"]."\">";
			$out.= $data["ITEM_$num"];
			if ($CFG_GLPI["view_ID"]||empty($data["ITEM_$num"])) {
				$out.= " (".$data["ITEM_".$num."_2"].")";
			}
			$out.= "</a>";
			return $out;
		break;
		case "glpi_licenses.version" :
		case "glpi_licenses.serial" :
		case "glpi_networking_ports.ifaddr" :
		case "glpi_dropdown_netpoint.name" :
		case "glpi_registry.registry_ocs_name" :
		case "glpi_registry.registry_value" :
		$out="";
		$split=explode("$$$$",$data["ITEM_$num"]);

		$count_display=0;
		for ($k=0;$k<count($split);$k++)
			if (strlen(trim($split[$k]))>0){
				if ($count_display) $out.= "<br>";
				$count_display++;
				$out.= $split[$k];
			}
		return $out;

		break;
		case "glpi_users.name" :		
			// USER search case
			if (empty($linkfield)){
				$out= "<a href=\"".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[$type]."?ID=".$data['ID']."\">";
				$out.= $data["ITEM_$num"];
				if ($CFG_GLPI["view_ID"]||empty($data["ITEM_$num"])) $out.= " (".$data["ID"].")";
				$out.= "</a>";
			} else {
				$type=USER_TYPE;
				$out="";
				if ($data["ITEM_".$num."_3"]>0)
					$out= "<a href=\"".$CFG_GLPI["root_doc"]."/front/user.form.php?ID=".$data["ITEM_".$num."_3"]."\">";
				// print realname or login name
				if (!empty($data["ITEM_".$num."_2"])||!empty($data["ITEM_".$num."_4"]))
					$out .= $data["ITEM_".$num."_2"]." ".$data["ITEM_".$num."_4"];
				else $out .= $data["ITEM_$num"];

				if ($data["ITEM_".$num."_3"]>0&&($CFG_GLPI["view_ID"]||(empty($data["ITEM_$num"])))) $out.= " (".$data["ITEM_".$num."_3"].")";

				if ($data["ITEM_".$num."_3"]>0)
					$out.= "</a>";
			}
		return $out;
		break;
		case "glpi_groups.name" :		
			if (empty($linkfield)){
				$out="";
				$split=explode("$$$$",$data["ITEM_$num"]);

				$count_display=0;
				for ($k=0;$k<count($split);$k++)
					if (strlen(trim($split[$k]))>0){
						if ($count_display) $out.= "<br>";
						$count_display++;
						$out.= $split[$k];
					}
				return $out;
			} else {
				if ($type==GROUP_TYPE){
					$out= "<a href=\"".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[$type]."?ID=".$data['ID']."\">";
					$out.= $data["ITEM_$num"];
					if ($CFG_GLPI["view_ID"]||empty($data["ITEM_$num"])) $out.= " (".$data["ID"].")";
					$out.= "</a>";
				} else {
					$out= $data["ITEM_$num"];
				}
			}
		return $out;
		break;
		case "glpi_computers.comments" :
		case "glpi_networking.comments" :
		case "glpi_printers.comments" :
		case "glpi_monitors.comments" :
		case "glpi_peripherals.comments" :
		case "glpi_software.comments" :
		case "glpi_contacts.comments" :
		case "glpi_enterprises.comments" :
		case "glpi_users.comments" :
		case "glpi_phones.comments" :
		case "glpi_groups.comments" :
		case "glpi_entities.comments" :
		case "glpi_consumables_type.comments" :
		case "glpi_docs.comment" :
		case "glpi_cartridges_type.comments" :
			return nl2br($data["ITEM_$num"]);
		break;
		case "glpi_profiles.name" :
			if ($type==PROFILE_TYPE){
				$out= "<a href=\"".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[$type]."?ID=".$data['ID']."\">";
				$out.= $data["ITEM_$num"];
				if ($CFG_GLPI["view_ID"]||empty($data["ITEM_$num"])) {
					$out.= " (".$data["ID"].")";
				}
				$out.= "</a>";
			} else if ($type==USER_TYPE){	
				$out="";

				$split=explode("$$$$",$data["ITEM_$num"]);
				$split2=explode("$$$$",$data["ITEM_".$num."_2"]);
				$split3=explode("$$$$",$data["ITEM_".$num."_3"]);

				$count_display=0;
				for ($k=0;$k<count($split);$k++)
					if (strlen(trim($split[$k]))>0){
						if ($count_display) $out.= "<br>";
						$count_display++;
						$out.= $split[$k]." - ".$split2[$k];
						if ($split3[$k]){
							$out.=" (R)";
						}
					}
				return $out;
			} else {
				$out= $data["ITEM_$num"];
			}
			return $out;
		break;
		case "glpi_entities.completename" :
			if ($type==ENTITY_TYPE){
				$out= "<a href=\"".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[$type]."?ID=".$data['ID']."\">";
				$out.= $data["ITEM_$num"];
				if ($CFG_GLPI["view_ID"]||empty($data["ITEM_$num"])) {
					$out.= " (".$data["ID"].")";
				}
				$out.= "</a>";
			} else if ($type==USER_TYPE){	
				$out="";

				$split=explode("$$$$",$data["ITEM_$num"]);
				$split2=explode("$$$$",$data["ITEM_".$num."_2"]);
				$split3=explode("$$$$",$data["ITEM_".$num."_3"]);

				$count_display=0;
				for ($k=0;$k<count($split);$k++)
					if (strlen(trim($split[$k]))>0){
						if ($count_display) $out.= "<br>";
						$count_display++;
						$out.= $split[$k]." - ".$split2[$k];
						if ($split3[$k]){
							$out.=" (R)";
						}
					}
				return $out;
			} else {
				if ($data["ITEM_".$num."_2"]==0){
					$out=$LANG["entity"][2];
				} else {
					$out= $data["ITEM_$num"];
				}
			}
			return $out;
			break;
		case "glpi_contracts.name" :
			if (empty($linkfield)){
				$out="";
				$split=explode("$$$$",$data["ITEM_$num"]);

				$count_display=0;
				for ($k=0;$k<count($split);$k++)
					if (strlen(trim($split[$k]))>0){
						if ($count_display) $out.= "<br>";
						$count_display++;
						$out.= $split[$k];
					}
			} else {
				if ($type==CONTRACT_TYPE){
					$out= "<a href=\"".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[$type]."?ID=".$data['ID']."\">";
					$out.= $data["ITEM_$num"];
					if ($CFG_GLPI["view_ID"]||empty($data["ITEM_$num"])) $out.= " (".$data["ID"].")";
					$out.= "</a>";
				} else {
					$out= $data["ITEM_$num"];
				}
			}
		return $out;
		case "glpi_contracts.num" :
			if (empty($linkfield)){
				$out="";
				$split=explode("$$$$",$data["ITEM_$num"]);

				$count_display=0;
				for ($k=0;$k<count($split);$k++)
					if (strlen(trim($split[$k]))>0){
						if ($count_display) $out.= "<br>";
						$count_display++;
						$out.= $split[$k];
					}
				return $out;
			} else {
				return $data["ITEM_$num"];
			}
		break;

		case "glpi_contacts.completename":
				$out="";
				$split=explode("$$$$",$data["ITEM_$num"]);

				$count_display=0;
				for ($k=0;$k<count($split);$k++)
					if (strlen(trim($split[$k]))>0){
						if ($count_display) $out.= "<br>";
						$count_display++;
						$out.= $split[$k];
					}
				return $out;
			break;
		case "glpi_enterprises.name" :
			if (empty($linkfield)){
				if ($type==CONTACT_TYPE){
					$out="";
					$split=explode("$$$$",$data["ITEM_$num"]);
	
					$count_display=0;
					for ($k=0;$k<count($split);$k++)
						if (strlen(trim($split[$k]))>0){
							if ($count_display) $out.= "<br>";
							$count_display++;
							$out.= $split[$k];
						}
					return $out;

				} else {
					$out= "<a href=\"".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[$type]."?ID=".$data['ID']."\">";
					$out.= $data["ITEM_$num"];
					if ($CFG_GLPI["view_ID"]||empty($data["ITEM_$num"])) $out.= " (".$data["ID"].")";
					$out.= "</a>";
					if (!empty($data["ITEM_".$num."_2"])){
						$out.= "<a href='".formatOutputWebLink($data["ITEM_".$num."_2"])."' target='_blank'><img src='".$CFG_GLPI["root_doc"]."/pics/web.png' alt='website'></a>";
					}
				}
			} else {
				$type=ENTERPRISE_TYPE;
				$out="";
				if ($data["ITEM_".$num."_3"]>0)
					$out= "<a href=\"".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[$type]."?ID=".$data["ITEM_".$num."_3"]."\">";
				$out.= $data["ITEM_$num"];
				if ($data["ITEM_".$num."_3"]>0&&($CFG_GLPI["view_ID"]||(empty($data["ITEM_$num"])))) $out.= " (".$data["ITEM_".$num."_3"].")";
				if ($data["ITEM_".$num."_3"]>0)
					$out.= "</a>";
				if (!empty($data["ITEM_".$num."_2"])){
					$out.= "<a href='".formatOutputWebLink($data["ITEM_".$num."_2"])."' target='_blank'><img src='".$CFG_GLPI["root_doc"]."/pics/web.png' alt='website'></a>";
				}
			}
			return $out;
		break;	
		case "glpi_enterprises_infocoms.name" :
			$type=ENTERPRISE_TYPE;
			$out="";
			if (!empty($data["ITEM_".$num."_3"])){
				$out.= "<a href=\"".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[$type]."?ID=".$data["ITEM_".$num."_3"]."\">";
				$out.= $data["ITEM_$num"];
				if ($CFG_GLPI["view_ID"]||empty($data["ITEM_$num"])) 
					$out.= " (".$data["ITEM_".$num."_3"].")";
				$out.= "</a>";
			}
			return $out;
		break;
		case "glpi_type_docs.icon" :
			if (!empty($data["ITEM_$num"])){
				return "<img class='middle' alt='' src='".$CFG_GLPI["typedoc_icon_dir"]."/".$data["ITEM_$num"]."'>";
			}
			else {
				return "&nbsp;";
			}
		break;	

		case "glpi_docs.filename" :		
			return getDocumentLink($data["ITEM_$num"]);
		break;		
		case "glpi_docs.link" :
		case "glpi_enterprises.website" :
			if (!empty($data["ITEM_$num"])){
				$link=$data["ITEM_$num"];
				if (strlen($data["ITEM_$num"])>30){
					$link=utf8_substr($data["ITEM_$num"],0,30)."...";
				}
				return "<a href=\"".$data["ITEM_$num"]."\" target='_blank'>".$link."</a>";
			} else return "&nbsp;";
			break;	
		case "glpi_enterprises.email" :
		case "glpi_contacts.email" :
		case "glpi_users.email" :
			if (!empty($data["ITEM_$num"])){
				return "<a href='mailto:".$data["ITEM_$num"]."'>".$data["ITEM_$num"]."</a>";
			} else {
				return "&nbsp;";
			}
			break;	
		case "glpi_device_hdd.specif_default" :
		case "glpi_device_ram.specif_default" :
		case "glpi_device_processor.specif_default" :
			return $data["ITEM_".$num];
			break;
		case "glpi_networking_ports.ifmac" :
			$out="";
			if ($type==COMPUTER_TYPE){
				if (!empty($data["ITEM_".$num."_2"])){
					$split=explode("$$$$",$data["ITEM_".$num."_2"]);
					$count_display=0;
					for ($k=0;$k<count($split);$k++){
						if (strlen(trim($split[$k]))>0){	
							if ($count_display) {
								$out.= "<br>";
							} else {
								$out.= "hw=";
							}
							$count_display++;
							$out.= $split[$k];
						}
					}
					if (!empty($data["ITEM_".$num])) $out.= "<br>";
				}

				if (!empty($data["ITEM_".$num])){
					$split=explode("$$$$",$data["ITEM_".$num]);
					$count_display=0;
					for ($k=0;$k<count($split);$k++){
						if (strlen(trim($split[$k]))>0){	
							if ($count_display) $out.= "<br>";
							else $out.= "port=";
							$count_display++;
							$out.= $split[$k];
						}
					}
				}
			} else {
				$split=explode("$$$$",$data["ITEM_".$num]);
				$count_display=0;
				for ($k=0;$k<count($split);$k++){
					if (strlen(trim($split[$k]))>0){	
						if ($count_display){
							$out.= "<br>";
						}
						$count_display++;
						$out.= $split[$k];
					}
				}
			}
			return $out;
			break;
		case "glpi_contracts.duration":
		case "glpi_contracts.notice":
		case "glpi_contracts.periodicity":
		case "glpi_contracts.facturation":
			if (!empty($data["ITEM_$num"])){
				return $data["ITEM_$num"]." ".$LANG["financial"][57];
			} else {
				return "&nbsp;";
			}
			break;
		case "glpi_contracts.renewal":
			return getContractRenewalName($data["ITEM_$num"]);
			break;
		case "glpi_ocs_link.last_update":
		case "glpi_ocs_link.last_ocs_update":
		case "glpi_computers.date_mod":
		case "glpi_printers.date_mod":
		case "glpi_networking.date_mod":
		case "glpi_peripherals.date_mod":
		case "glpi_phones.date_mod":
		case "glpi_software.date_mod":
		case "glpi_monitors.date_mod":
		case "glpi_docs.date_mod":
		case "glpi_ocs_config.date_mod" :
		case "glpi_users.last_login":
		case "glpi_users.date_mod":	
			return convDateTime($data["ITEM_$num"]);
			break;
		case "glpi_infocoms.end_warranty_use":
		case "glpi_infocoms.end_warranty_buy":
		case "glpi_contracts.end_date":
			if ($data["ITEM_$num"]!=''&&$data["ITEM_$num"]!="0000-00-00"){
				return getWarrantyExpir($data["ITEM_$num"],$data["ITEM_".$num."_2"]);
			} else {
				return "&nbsp;"; 
			}
		break;
		case "glpi_contracts.expire_notice": // ajout jmd
			if ($data["ITEM_$num"]!=''&&$data["ITEM_$num"]!="0000-00-00"){
				return getExpir($data["ITEM_$num"],$data["ITEM_".$num."_2"],$data["ITEM_".$num."_3"]);
			} else {
				return "&nbsp;"; 
			}
		case "glpi_contracts.expire": // ajout jmd
			if ($data["ITEM_$num"]!=''&&$data["ITEM_$num"]!="0000-00-00"){
				return getExpir($data["ITEM_$num"],$data["ITEM_".$num."_2"]);
			} else {
				return "&nbsp;"; 
			}
		case "glpi_contracts.begin_date":
		case "glpi_infocoms.buy_date":
		case "glpi_infocoms.use_date":
			return convDate($data["ITEM_$num"]);
			break;
		case "glpi_infocoms.amort_time":
			if (!empty($data["ITEM_$num"])){
				return $data["ITEM_$num"]." ".$LANG["financial"][9];
			} else { 
				return "&nbsp;";
			}
			break;
		case "glpi_infocoms.warranty_duration":
			if (!empty($data["ITEM_$num"])){
				return $data["ITEM_$num"]." ".$LANG["financial"][57];
			} else {
				return "&nbsp;";
			}
			break;
		case "glpi_infocoms.amort_type":
			return getAmortTypeName($data["ITEM_$num"]);
			break;
		case "glpi_infocoms.value":
		case "glpi_infocoms.warranty_value":
			return number_format($data["ITEM_$num"],$CFG_GLPI["decimal_number"],'.','');
			break;
		case "glpi_infocoms.alert":
			if ($data["ITEM_$num"]==pow(2,ALERT_END)){
				return $LANG["financial"][80];
			} 
			return "";
			break;
		case "glpi_tracking.count":
			if ($data["ITEM_$num"]>0&&haveRight("show_all_ticket","1")){
				$out= "<a href=\"".$CFG_GLPI["root_doc"]."/front/tracking.php?reset=reset_before&status=all&type=$type&item=".$data['ID']."\">";
				$out.= $data["ITEM_$num"];
				$out.="</a>";
			} else {
				$out= $data["ITEM_$num"];
			}
			return $out;
			break;
		case "glpi_auth_tables.name" :
			return getAuthMethodName($data["ITEM_".$num], $data["ITEM_".$num."_2"], 1,$data["ITEM_".$num."_3"].$data["ITEM_".$num."_4"]);
			break;
		case "glpi_reservation_item.comments" :
			if (empty($data["ITEM_$num"])){
				return  "<a href='".$CFG_GLPI["root_doc"]."/front/reservation.php?comment=".$data["refID"]."' title='".$LANG["reservation"][22]."'>".$LANG["common"][49]."</a>";
			}else{
				return "<a href='".$CFG_GLPI["root_doc"]."/front/reservation.php?comment=".$data['refID']."' title='".$LANG["reservation"][22]."'>". resume_text($data["ITEM_$num"])."</a>";
			}
			break;
		default:
			// Plugin case
			if ($type>1000){
				if (isset($PLUGIN_HOOKS['plugin_types'][$type])){
					$function='plugin_'.$PLUGIN_HOOKS['plugin_types'][$type].'_giveItem';
					if (function_exists($function)){
						$out=$function($type,$field,$data,$num,$linkfield);
						if (!empty($out)){
							return $out;
						}
					} 
				} 
			}

			return $data["ITEM_$num"];
			break;
	}

}


/**
 * Generic Function to get transcript table name
 *
 *
 *@param $table reference table
 *@param $device_type device type ID
 *@param $meta_type meta table type ID
 *
 *@return Left join string
 *
 **/
function translate_table($table,$device_type=0,$meta_type=0){

	$ADD="";
	if ($meta_type) $ADD="_".$meta_type;

	switch ($table){
		case "glpi_computer_device":
			if ($device_type==0)
				return $table.$ADD;
			else return "DEVICE_".$device_type.$ADD;
			break;
		default :
			return $table.$ADD;
			break;
	}

}


/**
 * Generic Function to add Default left join to a request
 *
 *
 *@param $type reference ID
 *@param $ref_table reference table
 *@param $already_link_tables array of tables already joined
 *
 *@return Left join string
 *
 **/
function addDefaultJoin ($type,$ref_table,&$already_link_tables){

	switch ($type){
		// No link
		case USER_TYPE:
			return addLeftJoin($type,$ref_table,$already_link_tables,"glpi_users_profiles","");
		break;
		default :
			return "";
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
 *@param $meta is it a meta item ?
 *@param $meta_type meta type table
 *@param $linkfield linkfield for LeftJoin
 *
 *
 *@return Left join string
 *
 **/
function addLeftJoin ($type,$ref_table,&$already_link_tables,$new_table,$linkfield,$device_type=0,$meta=0,$meta_type=0){

	global $PLUGIN_HOOKS,$LANG;

	// Rename table for meta left join
	$AS="";
	$nt=$new_table;

	// Multiple link possibilies case
	if ($new_table=="glpi_users"){
		$AS = " AS ".$new_table."_".$linkfield;
		$nt.="_".$linkfield;
	}

	$addmetanum="";
	$rt=$ref_table;
	if ($meta) {
		$AS= " AS ".$new_table."_".$meta_type;
		$nt=$new_table."_".$meta_type;
		//$rt.="_".$meta_type;
	}

	// Auto link
	if ($ref_table==$new_table) return "";
	
	if (in_array(translate_table($new_table,$device_type,$meta_type).".".$linkfield,$already_link_tables)) return "";
	else array_push($already_link_tables,translate_table($new_table,$device_type,$meta_type).".".$linkfield);
	
	switch ($new_table){
		// No link
		case "glpi_auth_tables":
			return " LEFT JOIN glpi_auth_ldap ON (glpi_users.auth_method = ".AUTH_LDAP." AND glpi_users.id_auth = glpi_auth_ldap.ID) 
				LEFT JOIN glpi_auth_mail ON (glpi_users.auth_method = ".AUTH_MAIL." AND glpi_users.id_auth = glpi_auth_mail.ID) ";
		break;
		case "glpi_reservation_item":
			return "";
		break;
		case "glpi_entities_data":
			return " LEFT JOIN $new_table $AS ON ($rt.ID = $nt.FK_entities) ";
		break;
		case "glpi_ocs_link":
			return " LEFT JOIN $new_table $AS ON ($rt.ID = $nt.glpi_id) ";
		break;
		case "glpi_ocs_link":
			return " LEFT JOIN $new_table $AS ON ($rt.ID = $nt.glpi_id) ";
		break;
		case "glpi_registry":
			return " LEFT JOIN $new_table $AS ON ($rt.ID = $nt.computer_id) ";
		break;
		case "glpi_dropdown_os":
			if ($type==SOFTWARE_TYPE){
				return " LEFT JOIN $new_table $AS ON ($rt.platform = $nt.ID) ";
			} else  {
				return " LEFT JOIN $new_table $AS ON ($rt.os = $nt.ID) ";
			}
		break;
		case "glpi_networking_ports":
			$out="";
		// Add networking device for computers
		if ($type==COMPUTER_TYPE){
			$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_computer_device",$linkfield,NETWORK_DEVICE,$meta,$meta_type);
		}
		return $out." LEFT JOIN $new_table $AS ON ($rt.ID = $nt.on_device AND $nt.device_type='$type') ";
		break;
		case "glpi_dropdown_netpoint":
			// Link to glpi_networking_ports before
			$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_networking_ports",$linkfield);
		return $out." LEFT JOIN $new_table $AS ON (glpi_networking_ports.netpoint = $nt.ID) ";
		break;
		case "glpi_tracking":
			return " LEFT JOIN $new_table $AS ON ($nt.device_type='$type' AND $rt.ID = $nt.computer) ";
		break;
		case "glpi_users":
			return " LEFT JOIN $new_table $AS ON ($rt.$linkfield = $nt.ID) ";
		break;
		case "glpi_enterprises":
			if ($type==CONTACT_TYPE){
				$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_contact_enterprise","FK_contact");
				return $out." LEFT JOIN $new_table $AS ON (glpi_contact_enterprise.FK_enterprise = $nt.ID) ";
			} else {
				return " LEFT JOIN $new_table $AS ON ($rt.FK_enterprise = $nt.ID) ";
			}
		break;
		case "glpi_contacts":
			$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_contact_enterprise","FK_enterprise");
			return $out." LEFT JOIN $new_table $AS ON (glpi_contact_enterprise.FK_contact = $nt.ID) ";
		break;
		case "glpi_contact_enterprise":
			return " LEFT JOIN $new_table $AS ON ($rt.ID = $nt.$linkfield) ";
		break;
		case "glpi_dropdown_manufacturer":
			return " LEFT JOIN $new_table $AS ON ($rt.FK_glpi_enterprise = $nt.ID) ";
		break;

		case "glpi_enterprises_infocoms":
			$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_infocoms",$linkfield);
		return $out." LEFT JOIN glpi_enterprises AS glpi_enterprises_infocoms ON (glpi_infocoms.FK_enterprise = $nt.ID) ";
		break;
		case "glpi_dropdown_budget":
			$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_infocoms",$linkfield);
		return $out." LEFT JOIN $new_table $AS ON (glpi_infocoms.budget = $nt.ID) ";
		break;
		case "glpi_infocoms":
			return " LEFT JOIN $new_table $AS ON ($rt.ID = $nt.FK_device AND $nt.device_type='$type') ";
		break;
		case "glpi_contract_device":
			return " LEFT JOIN $new_table $AS ON ($rt.ID = $nt.FK_device AND $nt.device_type='$type') ";
		break;
		case "glpi_users_profiles":
			return " LEFT JOIN $new_table $AS ON ($rt.ID = $nt.FK_users) ";
		break;

		case "glpi_profiles":
			// Link to glpi_users_profiles before
			$out=addLeftJoin($type,$rt,$already_link_tables,"glpi_users_profiles",$linkfield);
			if ($type==USER_TYPE){
				$out.=addLeftJoin($type,"glpi_users_profiles",$already_link_tables,"glpi_complete_entities","FK_entities");
			}
		return $out." LEFT JOIN $new_table $AS ON (glpi_users_profiles.FK_profiles = $nt.ID) ";
		break;
		case "glpi_entities":
			if ($type==USER_TYPE){
				$out=addLeftJoin($type,"glpi_users_profiles",$already_link_tables,"glpi_profiles","");
				$out.=addLeftJoin($type,"glpi_users_profiles",$already_link_tables,"glpi_complete_entities","FK_entities");
				return $out;
			} else {
				return " LEFT JOIN $new_table $AS ON ($rt.$linkfield = $nt.ID) ";
			}
		break;
		case "glpi_complete_entities":
			array_push($already_link_tables,translate_table("glpi_entities",$device_type,$meta_type).".".$linkfield);

			if (empty($AS)){
				$AS = "AS glpi_entities";
			}
			return " LEFT JOIN ( SELECT * FROM glpi_entities UNION SELECT 0 AS ID, '".addslashes($LANG["entity"][2])."' AS name, -1 AS parentID, '".addslashes($LANG["entity"][2])."' AS completename, '' AS comments, -1 AS level) 
				$AS ON ($rt.$linkfield = glpi_entities.ID) ";
			break;
		case "glpi_users_groups":
			return " LEFT JOIN $new_table $AS ON ($rt.ID = $nt.FK_users) ";
		break;

		case "glpi_groups":
			if (empty($linkfield)){
				// Link to glpi_users_group before
				$out=addLeftJoin($type,$rt,$already_link_tables,"glpi_users_groups",$linkfield);

				return $out." LEFT JOIN $new_table $AS ON (glpi_users_groups.FK_groups = $nt.ID) ";
			} else {
				return " LEFT JOIN $new_table $AS ON ($rt.$linkfield = $nt.ID) ";
			}

		break;
		case "glpi_contracts":
			$out=addLeftJoin($type,$rt,$already_link_tables,"glpi_contract_device",$linkfield);
		return $out." LEFT JOIN $new_table $AS ON (glpi_contract_device.FK_contract = $nt.ID) ";
		break;
		case "glpi_licenses":
			if (!$meta){
				return " LEFT JOIN $new_table $AS ON ($rt.ID = $nt.sID) ";
			} else {
				return "";
			}
		break;	
		case "glpi_computer_device":
			if ($device_type==0){
				return " LEFT JOIN $new_table $AS ON ($rt.ID = $nt.FK_computers ) ";
			} else {
				return " LEFT JOIN $new_table AS DEVICE_".$device_type." ON ($rt.ID = DEVICE_".$device_type.".FK_computers AND DEVICE_".$device_type.".device_type='$device_type') ";
			}
		break;	
		case "glpi_device_processor":
			$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_computer_device",$linkfield,PROCESSOR_DEVICE,$meta,$meta_type);
			return $out." LEFT JOIN $new_table $AS ON (DEVICE_".PROCESSOR_DEVICE.".FK_device = $nt.ID) ";
		break;		
		case "glpi_device_ram":
			$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_computer_device",$linkfield,RAM_DEVICE,$meta,$meta_type);
			return $out." LEFT JOIN $new_table $AS ON (DEVICE_".RAM_DEVICE.".FK_device = $nt.ID) ";
		break;		
		case "glpi_device_iface":
			$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_computer_device",$linkfield,NETWORK_DEVICE,$meta,$meta_type);
			return $out." LEFT JOIN $new_table $AS ON (DEVICE_".NETWORK_DEVICE.".FK_device = $nt.ID) ";
		break;	
		case "glpi_device_sndcard":
			$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_computer_device",$linkfield,SND_DEVICE,$meta,$meta_type);
			return $out." LEFT JOIN $new_table $AS ON (DEVICE_".SND_DEVICE.".FK_device = $nt.ID) ";
		break;		
		case "glpi_device_gfxcard":
			$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_computer_device",$linkfield,GFX_DEVICE,$meta,$meta_type);
			return $out." LEFT JOIN $new_table $AS ON (DEVICE_".GFX_DEVICE.".FK_device = $nt.ID) ";
		break;	
		case "glpi_device_moboard":
			$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_computer_device",$linkfield,MOBOARD_DEVICE,$meta,$meta_type);
			return $out." LEFT JOIN $new_table $AS ON (DEVICE_".MOBOARD_DEVICE.".FK_device = $nt.ID) ";
		break;	
		case "glpi_device_hdd":
			$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_computer_device",$linkfield,HDD_DEVICE,$meta,$meta_type);
			return $out." LEFT JOIN $new_table $AS ON (DEVICE_".HDD_DEVICE.".FK_device = $nt.ID) ";
		break;
		default :
			// Plugin case
			if ($type>1000){
				if (isset($PLUGIN_HOOKS['plugin_types'][$type])){
					$function='plugin_'.$PLUGIN_HOOKS['plugin_types'][$type].'_addLeftJoin';
					if (function_exists($function)){
						$out=$function($type,$ref_table,$new_table,$linkfield);
						if (!empty($out)){
							return $out;
						}
					} 
				} 
			}
			if (!empty($linkfield)){
				return " LEFT JOIN $new_table $AS ON ($rt.$linkfield = $nt.ID) ";
			} else {
				return "";
			}

			break;
	}
}


/**
 * Generic Function to add left join for meta items
 *
 *
 *@param $from_type reference item type ID 
 *@param $to_type item type to add
 *@param $already_link_tables2 array of tables already joined
 *@param $null Used LEFT JOIN (null generation) or INNER JOIN for strict join
 *
 *
 *@return Meta Left join string
 *
 **/
function addMetaLeftJoin($from_type,$to_type,&$already_link_tables2,$null){
	global $LINK_ID_TABLE;


	$LINK=" INNER JOIN ";
	if ($null)
		$LINK=" LEFT JOIN ";

	switch ($from_type){
		case COMPUTER_TYPE :
			switch ($to_type){
				/*				case NETWORKING_TYPE :
								array_push($already_link_tables2,$LINK_ID_TABLE[NETWORKING_TYPE]."_$to_type");
								return " $LINK glpi_networking_ports as ports ON (glpi_computers.ID = ports.on_device AND ports.device_type='".COMPUTER_TYPE."') ".
								" $LINK glpi_networking_wire as wire1 ON (ports.ID = wire1.end1) ".
								" $LINK glpi_networking_ports as ports21 ON (ports21.device_type='".NETWORKING_TYPE."' AND wire1.end2 = ports21.ID ) ".
								" $LINK glpi_networking_wire as wire2 ON (ports.ID = wire2.end2) ".
								" $LINK glpi_networking_ports as ports22 ON (ports22.device_type='".NETWORKING_TYPE."' AND wire2.end1 = ports22.ID ) ".
								" $LINK glpi_networking$to_type ON (glpi_networking$to_type.ID = ports22.on_device OR glpi_networking.ID = ports21.on_device)";
								break;
				 */				
				case PRINTER_TYPE :
					array_push($already_link_tables2,$LINK_ID_TABLE[PRINTER_TYPE]);
					return " $LINK glpi_connect_wire AS conn_print_$to_type ON (conn_print_$to_type.end2=glpi_computers.ID  AND conn_print_$to_type.type='".PRINTER_TYPE."') ".
						" $LINK glpi_printers ON (conn_print_$to_type.end1=glpi_printers.ID) ";
					break;				
				case MONITOR_TYPE :
					array_push($already_link_tables2,$LINK_ID_TABLE[MONITOR_TYPE]);
					return " $LINK glpi_connect_wire AS conn_mon_$to_type ON (conn_mon_$to_type.end2=glpi_computers.ID  AND conn_mon_$to_type.type='".MONITOR_TYPE."') ".
						" $LINK glpi_monitors ON (conn_mon_$to_type.end1=glpi_monitors.ID) ";
					break;				
				case PERIPHERAL_TYPE :
					array_push($already_link_tables2,$LINK_ID_TABLE[PERIPHERAL_TYPE]);
					return " $LINK glpi_connect_wire AS conn_periph_$to_type ON (conn_periph_$num.end2=glpi_computers.ID  AND conn_periph_$to_type.type='".PERIPHERAL_TYPE."') ".
						" $LINK glpi_peripherals ON (conn_periph_$to_type.end1=glpi_peripherals.ID) ";
					break;				
				case PHONE_TYPE :
					array_push($already_link_tables2,$LINK_ID_TABLE[PHONE_TYPE]);
					return " $LINK glpi_connect_wire AS conn_phones_$to_type ON (conn_phones_$to_type.end2=glpi_computers.ID  AND conn_phones_$to_type.type='".PHONE_TYPE."') ".
						" $LINK glpi_phones ON (conn_phones_$to_type.end1=glpi_phones.ID) ";
					break;			

				case SOFTWARE_TYPE :
					array_push($already_link_tables2,$LINK_ID_TABLE[SOFTWARE_TYPE]);
					return " $LINK glpi_inst_software as inst_$to_type ON (inst_$to_type.cID = glpi_computers.ID) ".
						" $LINK glpi_licenses as glpi_licenses_$to_type ON ( inst_$to_type.license=glpi_licenses_$to_type.ID ) ".
						" $LINK glpi_software ON (glpi_licenses_$to_type.sID = glpi_software.ID)"; 
					break;
			}
			break;
		case MONITOR_TYPE :
			switch ($to_type){
				case COMPUTER_TYPE :
					array_push($already_link_tables2,$LINK_ID_TABLE[COMPUTER_TYPE]);
					return " $LINK glpi_connect_wire AS conn_mon_$to_type ON (conn_mon_$to_type.end1=glpi_monitors.ID  AND conn_mon_$to_type.type='".MONITOR_TYPE."') ".
						" $LINK glpi_computers ON (conn_mon_$to_type.end2=glpi_computers.ID) ";

					break;
			}
			break;		
		case PRINTER_TYPE :
			switch ($to_type){
				case COMPUTER_TYPE :
					array_push($already_link_tables2,$LINK_ID_TABLE[COMPUTER_TYPE]);
					return " $LINK glpi_connect_wire AS conn_mon_$to_type ON (conn_mon_$to_type.end1=glpi_printers.ID  AND conn_mon_$to_type.type='".PRINTER_TYPE."') ".
						" $LINK glpi_computers ON (conn_mon_$to_type.end2=glpi_computers.ID) ";

					break;
			}
			break;		
		case PERIPHERAL_TYPE :
			switch ($to_type){
				case COMPUTER_TYPE :
					array_push($already_link_tables2,$LINK_ID_TABLE[COMPUTER_TYPE]);
					return " $LINK glpi_connect_wire AS conn_mon_$to_type ON (conn_mon_$to_type.end1=glpi_peripherals.ID  AND conn_mon_$to_type.type='".PERIPHERAL_TYPE."') ".
						" $LINK glpi_computers ON (conn_mon_$to_type.end2=glpi_computers.ID) ";

					break;
			}
			break;		
		case PHONE_TYPE :
			switch ($to_type){
				case COMPUTER_TYPE :
					array_push($already_link_tables2,$LINK_ID_TABLE[COMPUTER_TYPE]);
					return " $LINK glpi_connect_wire AS conn_mon_$to_type ON (conn_mon_$to_type.end1=glpi_phones.ID  AND conn_mon_$to_type.type='".PHONE_TYPE."') ".
						" $LINK glpi_computers ON (conn_mon_$to_type.end2=glpi_computers.ID) ";

					break;
			}
			break;
		case SOFTWARE_TYPE :
			switch ($to_type){
				case COMPUTER_TYPE :
					array_push($already_link_tables2,$LINK_ID_TABLE[COMPUTER_TYPE]);
					return " $LINK glpi_licenses as glpi_licenses_$to_type ON ( glpi_licenses_$to_type.sID = glpi_software.ID ) ".
						" $LINK glpi_inst_software as inst_$to_type ON (inst_$to_type.license = glpi_licenses_$to_type.ID) ".
						" $LINK glpi_computers ON (inst_$to_type.cID = glpi_computers.ID)";

					break;
			}
			break;		


	}

}


/**
 * Convert an array to be add in url
 *
 *
 * @param $name name of array
 * @param $array array to be added
 * @return string to add
 *
 */
function getMultiSearchItemForLink($name,$array){
	$out="";
	
	if (is_array($array)&&count($array)>0){
		foreach($array as $key => $val){
			//		if ($name!="link"||$key!=0)
			$out.="&amp;".$name."[$key]=".$val;
		}
	}
	return $out;

}

function isInfocomSearch($device_type,$searchID){
	global $CFG_GLPI;
	return (($searchID>=25&&$searchID<=28)
	||($searchID>=37&&$searchID<=38)
	||($searchID>=50&&$searchID<=59)
	||($searchID>=120&&$searchID<=121))&&in_array($device_type,$CFG_GLPI["infocom_types"]);
}

?>
