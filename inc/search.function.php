<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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
// Get search_option array / Already include in includes.php
if (!isset($SEARCH_OPTION)){
	$SEARCH_OPTION=getSearchOptions();
}

/**
 * Clean search options depending of user active profile
 *
 * @param $type item type to manage
 * @param $action action which is used to manupulate searchoption (r/w)
 * @return clean $SEARCH_OPTION array
 */
function cleanSearchOption($type,$action='r'){
	global $CFG_GLPI,$SEARCH_OPTION;
	$options=$SEARCH_OPTION[$type];
	$todel=array();
	if (!haveRight('infocom',$action)&&in_array($type,$CFG_GLPI["infocom_types"])){
		$todel=array_merge($todel,array('financial',25,26,27,28,37,38,50,51,52,53,54,55,56,57,58,59,120,122));
	}

	if (!haveRight('contract',$action)&&in_array($type,$CFG_GLPI["infocom_types"])){
		$todel=array_merge($todel,array('financial',29,30,130,131,132,133,134,135,136,137,138));
	}

	if ($type==COMPUTER_TYPE){
		if (!haveRight('networking',$action)){
			$todel=array_merge($todel,array('network',20,21,22,83,84,85));
		}
		if (!$CFG_GLPI['ocs_mode']||!haveRight('view_ocsng',$action)){
			$todel=array_merge($todel,array('ocsng',100,101,102,103));
		}
	}
	if (!haveRight('notes',$action)){
		$todel[]=90;
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
 * @param $usesession Use datas save in session
 * @param $save Save params to session
 * @return nothing
 */
function manageGetValuesInSearch($type=0,$usesession=true,$save=true){
	global $_GET,$DB;
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

	// First view of the page : try to load a bookmark
	if ($usesession && !isset($_SESSION['glpisearch'][$type])){
		$query="SELECT FK_bookmark 
			FROM glpi_display_default 
			WHERE FK_users='".$_SESSION['glpiID']."'
				AND device_type='$type';";
		if ($result=$DB->query($query)){
			if ($DB->numrows($result)>0){
				$IDtoload=$DB->result($result,0,0);
				// Set session variable
				$_SESSION['glpisearch'][$type]=array();
				// Load bookmark on main window
				$bookmark=new Bookmark();
				$bookmark->load($IDtoload,false);
			}
		}
	}

	if ($usesession&&isset($_GET["reset_before"])){
		if (isset($_SESSION['glpisearch'][$type])){
			unset($_SESSION['glpisearch'][$type]);
		}
		if (isset($_SESSION['glpisearchcount'][$type])){
			unset($_SESSION['glpisearchcount'][$type]);
		}
		if (isset($_SESSION['glpisearchcount2'][$type])){
			unset($_SESSION['glpisearchcount2'][$type]);
		}
		// Bookmark use
		if (isset($_GET["glpisearchcount"])){
			$_SESSION["glpisearchcount"][$type]=$_GET["glpisearchcount"];
		}
		// Bookmark use
		if (isset($_GET["glpisearchcount2"])){
			$_SESSION["glpisearchcount2"][$type]=$_GET["glpisearchcount2"];
		}
	}

	if (is_array($_GET)&&$save){
		foreach ($_GET as $key => $val){
			$_SESSION['glpisearch'][$type][$key]=$val;
		}
	}

	foreach ($default_values as $key => $val){
		if (!isset($_GET[$key])){
			if ($usesession&&isset($_SESSION['glpisearch'][$type][$key])) {
				$_GET[$key]=$_SESSION['glpisearch'][$type][$key];
			} else {
				$_GET[$key] = $val;
				$_SESSION['glpisearch'][$type][$key] = $val;
			}
		}
	}

	if (!isset($_SESSION["glpisearchcount"][$type])) {
		if (isset($_GET["glpisearchcount"])){
			$_SESSION["glpisearchcount"][$type]=$_GET["glpisearchcount"];
		} else {
			$_SESSION["glpisearchcount"][$type]=1;
		}
	}
	if (!isset($_SESSION["glpisearchcount2"][$type])) {
		// Set in URL for bookmark
		if (isset($_GET["glpisearchcount2"])){
			$_SESSION["glpisearchcount2"][$type]=$_GET["glpisearchcount2"];
		} else {
			$_SESSION["glpisearchcount2"][$type]=0;
		}
	}

}

/**
 * Print generic search form
 *
 * 
 *
 *@param $type type to display the form
 *@param $params parameters array may include field, contains, sort, deleted, link, link2, contains2, field2, type2
 *
 *@return nothing (diplays)
 *
 **/
function searchForm($type,$params){
	global $LANG,$SEARCH_OPTION,$CFG_GLPI,$LINK_ID_TABLE,$INFOFORM_PAGES;

	// Default values of parameters
	$default_values["link"]="";
	$default_values["field"]="";
	$default_values["contains"]="";
	$default_values["sort"]="";
	$default_values["deleted"]=0;
	$default_values["link2"]="";
	$default_values["contains2"]="";
	$default_values["field2"]="";
	$default_values["type2"]="";
	if (isset($INFOFORM_PAGES[$type])){
		$default_values["target"]=preg_replace(':^.*front/:','',str_replace('.form','',$INFOFORM_PAGES[$type]));
	} else {
		$default_values["target"]=$_SERVER['PHP_SELF'];
	}

	foreach ($default_values as $key => $val){
		if (isset($params[$key])){
			$$key=$params[$key];
		} else {
			$$key=$default_values[$key];
		}
	}


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
		echo ">".$LANG["common"][66]."</option>";

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
			 */			
			case PRINTER_TYPE :
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
			ajaxUpdateItemOnSelectEvent("type2_".$type."_".$i."_$rand","show_".$type."_".$i."_$rand",$CFG_GLPI["root_doc"]."/ajax/updateMetaSearch.php",$params,false);

			
			if (is_array($type2)&&isset($type2[$i])&&$type2[$i]>0){

				$params['type']=$type2[$i];
				ajaxUpdateItem("show_".$type."_".$i."_$rand",$CFG_GLPI["root_doc"]."/ajax/updateMetaSearch.php",$params,false);
				echo "<script type='text/javascript' >";
				echo "window.document.getElementById('type2_".$type."_".$i."_$rand').value='".$type2[$i]."';";
				echo "</script>\n";

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
	echo "<td align='center'>";
	echo "<a href='".$CFG_GLPI["root_doc"]."/front/computer.php?reset_search=reset_search&amp;type=$type' ><img title=\"".$LANG["buttons"][16]."\" alt=\"".$LANG["buttons"][16]."\" src='".$CFG_GLPI["root_doc"]."/pics/reset.png' class='calendrier'></a>";
	showSaveBookmarkButton(BOOKMARK_SEARCH,$type);

	echo "</td>";
	// Display submit button
	echo "<td width='80' class='tab_bg_2'>";
	echo "<input type='submit' value=\"".$LANG["buttons"][0]."\" class='submit' >";
	echo "</td></tr>"; 
	echo "</table>";
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
 *@param $type item type
 *@param $params parameters array may include field, contains, sort, order, start, deleted, link, link2, contains2, field2, type2
 *
 *
 *@return Nothing (display)
 *
 **/
function showList ($type,$params){
	global $DB,$INFOFORM_PAGES,$SEARCH_OPTION,$LINK_ID_TABLE,$CFG_GLPI,$LANG,$PLUGIN_HOOKS;

	// Default values of parameters
	$default_values["link"]=array();
	$default_values["field"]=array();
	$default_values["contains"]=array();
	$default_values["sort"]="1";
	$default_values["order"]="ASC";
	$default_values["start"]=0;
	$default_values["deleted"]=0;
	$default_values["export_all"]=0;
	$default_values["link2"]="";
	$default_values["contains2"]="";
	$default_values["field2"]="";
	$default_values["type2"]="";
	if (isset($INFOFORM_PAGES[$type])){
		$default_values["target"]=str_replace('front/','',str_replace('.form','',$INFOFORM_PAGES[$type]));
	} else {
		$default_values["target"]=$_SERVER['PHP_SELF'];
	}

	foreach ($default_values as $key => $val){
		if (isset($params[$key])){
			$$key=$params[$key];
		} else {
			$$key=$default_values[$key];
		}
	}
	if ($export_all){
		$start=0;
	}

	$limitsearchopt=cleanSearchOption($type);

	$itemtable=$LINK_ID_TABLE[$type];
	if (isset($CFG_GLPI["union_search_type"][$type])){
		$itemtable=$CFG_GLPI["union_search_type"][$type];
	}
	$LIST_LIMIT=$_SESSION['glpilist_limit'];

	// Set display type for export if define
	$output_type=HTML_OUTPUT;
	if (isset($_GET["display_type"])){
		$output_type=$_GET["display_type"];
		// Limit to 10 element
		if ($_GET["display_type"]==GLOBAL_SEARCH){
			$LIST_LIMIT=GLOBAL_SEARCH_DISPLAY_COUNT;
		}
	}

	$entity_restrict= ($type==ENTITY_TYPE || in_array($itemtable,$CFG_GLPI["specif_entities_tables"]));


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
	$query="SELECT * 
		FROM glpi_display 
		WHERE type='$type' AND FK_users='".$_SESSION["glpiID"]."' 
		ORDER BY rank";
	$result=$DB->query($query);
	// GET default serach options
	if ($DB->numrows($result)==0){
		$query="SELECT * 
			FROM glpi_display 
			WHERE type='$type' AND FK_users='0' 
			ORDER BY rank";
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

	/// TODO to delete see after OR use to_view / to_search arrays to manage left join
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


	/// TODO to delete : manage Left Join when need of search or display
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

		if ($type==ENTITY_TYPE) {
			$COMMONWHERE.=getEntitiesRestrictRequest($LINK,$itemtable,'ID','',true);
		} else if (isset($CFG_GLPI["union_search_type"][$type])) {
			// Will be replace below in Union/Recursivity Hack 
			$COMMONWHERE.=$LINK." ENTITYRESTRICT ";
		} else if (in_array($itemtable, $CFG_GLPI["recursive_type"])) {
			$COMMONWHERE.=getEntitiesRestrictRequest($LINK,$itemtable,'','',true);
		} else {
			$COMMONWHERE.=getEntitiesRestrictRequest($LINK,$itemtable);
		}
	}
	$WHERE="";
	$HAVING="";

	/// TODO do also having here / simplify view - all cases : duplicates
	// Add search conditions
	// If there is search items
	if ($_SESSION["glpisearchcount"][$type]>0&&count($contains)>0) {
		for ($key=0;$key<$_SESSION["glpisearchcount"][$type];$key++){
			// if real search (strlen >0) and not all and view search
			if (isset($contains[$key])&&strlen($contains[$key])>0){
				// common search
				if ($field[$key]!="all"&&$field[$key]!="view"){
					$LINK=" ";
					$NOT=0;
					$tmplink="";
					if (is_array($link)&&isset($link[$key])){
						if (strstr($link[$key],"NOT")){
							$tmplink=" ".str_replace(" NOT","",$link[$key]);
							$NOT=1;
						} else {
							$tmplink=" ".$link[$key];
						}
					} else {
						$tmplink=" AND ";
					}
				
					if (isset($SEARCH_OPTION[$type][$field[$key]]["usehaving"])){
						// Manage Link if not first item
						if (!empty($HAVING)) {
							$LINK=$tmplink;
						} 
						// Find key
						$item_num=array_search($field[$key],$toview);
	
						$HAVING.=addHaving($LINK,$NOT,$type,$field[$key],$contains[$key],0,$item_num);
					} else {
						// Manage Link if not first item
						if (!empty($WHERE)) {
							$LINK=$tmplink;
						}
						$WHERE.= addWhere($LINK,$NOT,$type,$field[$key],$contains[$key]);
					}
				//  view search
				} else if ($field[$key]=="view"){
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
					if (!empty($WHERE)) {
						$WHERE.=$globallink;
					}
	
					$WHERE.= " ( ";
					$first2=true;
					foreach ($toview as $key2 => $val2){
						// Add Where clause if not to be done in HAVING CLAUSE
						if (!isset($SEARCH_OPTION[$type][$val2]["usehaving"])){
							$tmplink=$LINK;
							if ($first2) {
								$tmplink=" ";
								$first2=false;
							}
							$WHERE.= addWhere($tmplink,$NOT,$type,$val2,$contains[$key]);
						}
					}
					$WHERE.=" ) ";
				
				// all search
				} else if ($field[$key]=="all"){
	
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
					if (!empty($WHERE)) {
						$WHERE.=$globallink;
					}
	
	
					$WHERE.= " ( ";
					$first2=true;
	
					foreach ($SEARCH_OPTION[$type] as $key2 => $val2)
						if (is_array($val2)){
							// Add Where clause if not to be done ine HAVING CLAUSE
						if (!isset($val2["usehaving"])){
								$tmplink=$LINK;
								if ($first2) {
									$tmplink=" ";
									$first2=false;
								}
								$WHERE.= addWhere($tmplink,$NOT,$type,$key2,$contains[$key]);
							}
						}
	
					$WHERE.=")";
				} 
			}
		}
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
					$FROM.=addMetaLeftJoin($type,$type2[$i],$already_link_tables2,
						(($contains2[$i]=="NULL")||(strstr($link2[$i],"NOT"))));
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
	if ($_SESSION["glpisearchcount2"][$type]>0 || !empty($HAVING)){
		$GROUPBY=" GROUP BY $itemtable.ID";
	}

	if (empty($GROUPBY)){
		foreach ($toview as $key2 => $val2){
			if (!empty($GROUPBY)){
				break;
			}
			if (isset($SEARCH_OPTION[$type][$val2]["forcegroupby"])){
				$GROUPBY=" GROUP BY $itemtable.ID";
			}
		}
	}

	// Specific search for others item linked  (META search)
	if (is_array($type2)){
		for ($key=0;$key<$_SESSION["glpisearchcount2"][$type];$key++){
			if (isset($type2[$key])&&$type2[$key]>0&&isset($contains2[$key])&&strlen($contains2[$key]))
			{
				$LINK="";

				// For AND NOT statement need to take into account all the group by items
				if (strstr($link2[$key],"AND NOT")
					|| isset($SEARCH_OPTION[$type2[$key]][$field2[$key]]["usehaving"])
				){
					$NOT=0;
					if (strstr($link2[$key],"NOT")){
						$tmplink=" ".str_replace(" NOT","",$link2[$key]);
						$NOT=1;
					} else {
						$tmplink=" ".$link2[$key];
					}
					if (!empty($HAVING)) {
						$LINK=$tmplink;
					}
					$HAVING.=addHaving($LINK,$NOT,$type2[$key],$field2[$key],$contains2[$key],1,$key);
				} else { // Meta Where Search
					$LINK=" ";
					$NOT=0;
					// Manage Link if not first item
					if (is_array($link2)&&isset($link2[$key])&&strstr($link2[$key],"NOT")){
						$tmplink=" ".str_replace(" NOT","",$link2[$key]);
						$NOT=1;
					}
					else if (is_array($link2)&&isset($link2[$key])){
						$tmplink=" ".$link2[$key];
					} else {
						$tmplink=" AND ";
					}
					if (!empty($WHERE)) {
						$LINK=$tmplink;
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

		$query_num="SELECT count(*) FROM ".$itemtable.$COMMONLEFTJOIN;

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
						$query_num=str_replace($CFG_GLPI["union_search_type"][$type],$LINK_ID_TABLE[$ctype],$tmpquery);
						// State case :
						if ($type==STATE_TYPE){
							$query_num.=" AND ".$LINK_ID_TABLE[$ctype].".state > 0 ";
						}
					} else {// Ref table case
						$replace="FROM ".$LINK_ID_TABLE[$type]." INNER JOIN ".$LINK_ID_TABLE[$ctype]." ON (".$LINK_ID_TABLE[$type].".id_device = ".$LINK_ID_TABLE[$ctype].".ID AND ".$LINK_ID_TABLE[$type].".device_type='$ctype')";
						$query_num=str_replace("FROM ".$CFG_GLPI["union_search_type"][$type],$replace,$tmpquery);
						$query_num=str_replace($CFG_GLPI["union_search_type"][$type],$LINK_ID_TABLE[$ctype],$query_num);
					}
					// Union/Recursivity Hack
					if (isset($CFG_GLPI["recursive_type"][$ctype])) {
						$query_num=str_replace("ENTITYRESTRICT",getEntitiesRestrictRequest('',$LINK_ID_TABLE[$ctype],'','',true),$query_num);
					} else {
						$query_num=str_replace("ENTITYRESTRICT",getEntitiesRestrictRequest('',$LINK_ID_TABLE[$ctype]),$query_num);
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
	if ($export_all) $LIMIT="";


	if (!empty($WHERE)||!empty($COMMONWHERE)){
		if (!empty($COMMONWHERE)){
			$WHERE=' WHERE '.$COMMONWHERE.(!empty($WHERE)?' AND ( '.$WHERE.' )':'');
		} else {
			$WHERE=' WHERE  '.$WHERE.' ';
		}
		$first=false;
	}

	if (!empty($HAVING)){
		$HAVING=' HAVING '.$HAVING;
	}

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
						$tmpquery=str_replace($CFG_GLPI["union_search_type"][$type],$LINK_ID_TABLE[$ctype],$tmpquery);
						// State case :
						if ($type==STATE_TYPE){
							$tmpquery.=" AND ".$LINK_ID_TABLE[$ctype].".state > 0 ";
						}
				} else {// Ref table case
						$tmpquery=$SELECT.", $ctype AS TYPE, ".$LINK_ID_TABLE[$type].".ID AS refID, ".$LINK_ID_TABLE[$ctype].".FK_entities AS ENTITY ".$FROM.$WHERE;
						$replace="FROM ".$LINK_ID_TABLE[$type]." INNER JOIN ".$LINK_ID_TABLE[$ctype]." ON (".$LINK_ID_TABLE[$type].".id_device = ".$LINK_ID_TABLE[$ctype].".ID AND ".$LINK_ID_TABLE[$type].".device_type='$ctype')";
						$tmpquery=str_replace("FROM ".$CFG_GLPI["union_search_type"][$type],$replace,$tmpquery);
						$tmpquery=str_replace($CFG_GLPI["union_search_type"][$type],$LINK_ID_TABLE[$ctype],$tmpquery);
				}
				// Union/Recursivity Hack
				if (isset($CFG_GLPI["recursive_type"][$ctype])) {
					$tmpquery=str_replace("ENTITYRESTRICT",getEntitiesRestrictRequest('',$LINK_ID_TABLE[$ctype],'','',true),$tmpquery);
				} else {
					$tmpquery=str_replace("ENTITYRESTRICT",getEntitiesRestrictRequest('',$LINK_ID_TABLE[$ctype]),$tmpquery);
				}
				// SOFTWARE HACK
				if ($ctype==SOFTWARE_TYPE){
					$tmpquery=str_replace("glpi_software.serial","''",$tmpquery);
					$tmpquery=str_replace("glpi_software.otherserial","''",$tmpquery);
				}

				$QUERY.=$tmpquery;
			}
		}
		if (empty($QUERY)){
			echo displaySearchError($output_type);
			return;
		}
		$QUERY.=str_replace($CFG_GLPI["union_search_type"][$type].".","",$ORDER).$LIMIT;
	} else {
		$QUERY=$SELECT.$FROM.$WHERE.$GROUPBY.$HAVING.$ORDER.$LIMIT;
	}

//	echo $QUERY."<br>\n";

	// Get it from database and DISPLAY
	if ($result = $DB->query($QUERY)) {

		// if real search or complete export : get numrows from request 
		if (!$nosearch||$export_all) 
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
				echo " <a href='$target?$parameters'>".$LANG["common"][66]."</a>";
			}
			echo "</h2></div>";
		}


		// If the begin of the view is before the number of items
		if ($start<$numrows) {

			// Display pager only for HTML
			if ($output_type==HTML_OUTPUT){

				// For plugin add new parameter if available
				if ($type>1000){
					if (isset($PLUGIN_HOOKS['plugin_types'][$type])){

						$function='plugin_'.$PLUGIN_HOOKS['plugin_types'][$type].'_addParamFordynamicReport';

						if (function_exists($function)){
							$out=$function($type);
							if (is_array($out)&&count($out)){
								foreach ($out as $key => $val){
									if (is_array($val)){
										$parameters.=getMultiSearchItemForLink($key,$val);
									} else {
										$parameters.="&amp;$key=$val";
									}
								}
							}
						} 
					} 
				}
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
					if (isset($type2[$i])&&isset($contains2[$i])&&strlen($contains2[$i])>0&&$type2[$i]>0&&(!isset($link2[$i])||!strstr($link2[$i],"NOT"))) {
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
			if ($export_all) {
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
					$tmp= " class='pointer'  onClick=\"var w = window.open('".$CFG_GLPI["root_doc"]."/front/popup.php?popup=search_config&amp;type=$type' ,'glpipopup', 'height=400, width=1000, top=100, left=100, scrollbars=yes' ); w.focus();\"";

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
								||(!strstr($link2[$i],"NOT") || $contains2[$i]=="NULL"))) {
						echo displaySearchHeaderItem($output_type,$names[$type2[$i]]." - ".$SEARCH_OPTION[$type2[$i]][$field2[$i]]["name"],$header_num);
					}
			// Add specific column Header
//			if ($type==SOFTWARE_TYPE)
//				echo displaySearchHeaderItem($output_type,$LANG["software"][11],$header_num);
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

			// Init list of items displayed
			if ($output_type==HTML_OUTPUT){
				initNavigateListItems($type);
			}

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

				// Add item in item list
				addToNavigateListItems($type,$data["ID"]);

				if ($output_type==HTML_OUTPUT){// HTML display - massive modif
					$tmpcheck="";
					if ($isadmin){
						if ($type==ENTITY_TYPE && !in_array($data["ID"],$_SESSION["glpiactiveentities"])) {							
							echo "&nbsp;";
						} else if (isset($CFG_GLPI["recursive_type"][$type]) && !in_array($data["FK_entities"],$_SESSION["glpiactiveentities"])) {
							echo "&nbsp;";
						} else {
							$sel="";
							if (isset($_GET["select"])&&$_GET["select"]=="all") {
								$sel="checked";
							}
							if (isset($_SESSION['glpimassiveactionselected'][$data["ID"]])){
								$sel="checked";
							}
							$tmpcheck="<input type='checkbox' name='item[".$data["ID"]."]' value='1' $sel>";							
						}						
					}
					echo displaySearchItem($output_type,$tmpcheck,$item_num,$row_num,"width='10'");
				}

				// Print first element - specific case for user 
				echo displaySearchItem($output_type,giveItem($type,1,$data,0),$item_num,$row_num,displayConfigItem($type,$SEARCH_OPTION[$type][1]["table"].".".$SEARCH_OPTION[$type][1]["field"]));

				// Print other toview items
				foreach ($toview as $key => $val){
					// Do not display first item
					if ($key>0){
						echo displaySearchItem($output_type,giveItem($type,$val,$data,$key),$item_num,$row_num,displayConfigItem($type,$SEARCH_OPTION[$type][$val]["table"].".".$SEARCH_OPTION[$type][$val]["field"]));
					}
				}

				// Print Meta Item
				if ($_SESSION["glpisearchcount2"][$type]>0&&is_array($type2))
					for ($j=0;$j<$_SESSION["glpisearchcount2"][$type];$j++)
						if (isset($type2[$j])&&$type2[$j]>0&&isset($contains2[$j])&&strlen($contains2[$j])&&(!isset($link2[$j])
									||(!strstr($link2[$j],"NOT") || $contains2[$j]=="NULL"))){

							// General case
							if (strpos($data["META_$j"],"$$$$")===false){
								$out=giveItem ($type2[$j],$field2[$j],$data,$j,1);
								echo displaySearchItem($output_type,$out,$item_num,$row_num);
							// Case of GROUP_CONCAT item : split item and multilline display
							} else {
								$split=explode("$$$$",$data["META_$j"]);
								$count_display=0;
								$out="";
								$unit="";
								if (isset($SEARCH_OPTION[$type2[$j]][$field2[$j]]['unit'])){
									$unit=$SEARCH_OPTION[$type2[$j]][$field2[$j]]['unit'];
								}
								for ($k=0;$k<count($split);$k++)
									if ($contains2[$j]=="NULL"||(strlen($contains2[$j])==0
										||preg_match('/'.$contains2[$j].'/i',$split[$k])
										|| isset($SEARCH_OPTION[$type2[$j]][$field2[$j]]['forcegroupby'])
									)){

										if ($count_display) $out.= "<br>";
										$count_display++;
										$out.= $split[$k].$unit;
									}
								echo displaySearchItem($output_type,$out,$item_num,$row_num);

							}
						}
				// Specific column display
				if ($type==CARTRIDGE_TYPE){
					echo displaySearchItem($output_type,countCartridges($data["ID"],$data["ALARM"],$output_type),$item_num,$row_num);
				}
//				if ($type==SOFTWARE_TYPE){
//					echo displaySearchItem($output_type,countInstallations($data["ID"],$output_type),$item_num,$row_num);
//				}		
				if ($type==CONSUMABLE_TYPE){
					echo displaySearchItem($output_type,countConsumables($data["ID"],$data["ALARM"],$output_type),$item_num,$row_num);
				}	
				if ($type==STATE_TYPE||$type==RESERVATION_TYPE){
					$ci->setType($data["TYPE"]);
					echo displaySearchItem($output_type,$ci->getType(),$item_num,$row_num);
				}	
				if ($type==RESERVATION_TYPE&&$output_type==HTML_OUTPUT){
					if (haveRight("reservation_central","w")){
						if (!haveAccessToEntity($data["ENTITY"])) {
							echo displaySearchItem($output_type,"&nbsp;",$item_num,$row_num);
							echo displaySearchItem($output_type,"&nbsp;",$item_num,$row_num);
						} else {
							if ($data["ACTIVE"]){
								echo displaySearchItem($output_type,"<a href=\"".$CFG_GLPI["root_doc"]."/front/reservation.php?ID=".$data["refID"]."&amp;active=0\"  title='".$LANG["buttons"][42]."'><img src=\"".$CFG_GLPI["root_doc"]."/pics/moins.png\" alt='' title=''></a>",$item_num,$row_num,"class='center'");
							} else {
								echo displaySearchItem($output_type,"<a href=\"".$CFG_GLPI["root_doc"]."/front/reservation.php?ID=".$data["refID"]."&amp;active=1\"  title='".$LANG["buttons"][41]."'><img src=\"".$CFG_GLPI["root_doc"]."/pics/plus.png\" alt='' title=''></a>",$item_num,$row_num,"class='center'");
							}

							echo displaySearchItem($output_type,"<a href=\"javascript:confirmAction('".addslashes($LANG["reservation"][38])."\\n".addslashes($LANG["reservation"][39])."','".$CFG_GLPI["root_doc"]."/front/reservation.php?ID=".$data["refID"]."&amp;delete=delete')\"  title='".$LANG["reservation"][6]."'><img src=\"".$CFG_GLPI["root_doc"]."/pics/delete.png\" alt='' title=''></a>",$item_num,$row_num,"class='center'");
						}
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
			if ($output_type==PDF_OUTPUT_LANDSCAPE || $output_type==PDF_OUTPUT_PORTRAIT){
				if ($_SESSION["glpisearchcount"][$type]>0&&count($contains)>0) {
					for ($key=0;$key<$_SESSION["glpisearchcount"][$type];$key++){
						if (strlen($contains[$key])){
							if (isset($link[$key])) $title.=" ".$link[$key]." ";
							switch ($field[$key]){
								case "all":
									$title.=$LANG["common"][66];
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
					
					echo "<table width='80%' class='tab_glpi'>";
					echo "<tr><td><img src=\"".$CFG_GLPI["root_doc"]."/pics/arrow-left.png\" alt=''></td><td class='center'><a onclick= \"if ( markCheckboxes('massiveaction_form') ) return false;\" href='".$_SERVER['PHP_SELF']."?select=all' >".$LANG["buttons"][18]."</a></td>";
	
					echo "<td>/</td><td class='center'><a onclick= \"if ( unMarkCheckboxes('massiveaction_form') ) return false;\" href='".$_SERVER['PHP_SELF']."?select=none'>".$LANG["buttons"][19]."</a>";
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
 *@param $LINK link to use 
 *@param $NOT is is a negative search ?
 *@param $type item type
 *@param $ID ID of the item to search
 *@param $val value search
 *@param $meta is it a meta item ?
 *@param $num item number 
 *
 *
 *@return select string
 *
 **/
function addHaving($LINK,$NOT,$type,$ID,$val,$meta,$num){
	global $SEARCH_OPTION;

	$table=$SEARCH_OPTION[$type][$ID]["table"];
	$field=$SEARCH_OPTION[$type][$ID]["field"];

	$NAME="ITEM_";
	if ($meta) $NAME="META_";

	// Plugin can override core definition for its type
	if ($type>1000){
		if (isset($PLUGIN_HOOKS['plugin_types'][$type])){
			$function='plugin_'.$PLUGIN_HOOKS['plugin_types'][$type].'_addHaving';
			if (function_exists($function)){
				$out=$function($LINK,$NOT,$type,$ID,$val,$num);
				if (!empty($out)){
					return $out;
				}
			} 
		} 
	}

	switch ($table.".".$field){
		default :
		break;
	}

	//// Default cases
	// Link with plugin tables 
	if ($type<=1000){
		if (preg_match("/^glpi_plugin_([a-zA-Z]+)/", $table, $matches)
		|| preg_match("/^glpi_dropdown_plugin_([a-zA-Z]+)/", $table, $matches) ){
			if (count($matches)==2){
				$plug=$matches[1];

				$function='plugin_'.$plug.'_addHaving';
				if (function_exists($function)){
					$out=$function($LINK,$NOT,$type,$ID,$val,$num);
					if (!empty($out)){
						return $out;
					}
				} 
			}
		} 
	}

	// Preformat items
	if (isset($SEARCH_OPTION[$type][$ID]["datatype"])){
		switch ($SEARCH_OPTION[$type][$ID]["datatype"]){
			case "number":
			case "decimal":

				$search=array("/\&lt;/","/\&gt;/");
				$replace=array("<",">");
				$val=preg_replace($search,$replace,$val);
		
				if (preg_match("/([<>])([=]*)[[:space:]]*([0-9]*)/",$val,$regs)){
					if ($NOT){
						if ($regs[1]=='<') {
							$regs[1]='>';
						} else {
							$regs[1]='<';
						}
					}
					$regs[1].=$regs[2];
					return " $LINK ($NAME$num ".$regs[1]." ".$regs[3]." ) ";
				} else {
					if (is_numeric($val)){
						if (isset($SEARCH_OPTION[$type][$ID]["width"])){
							if (!$NOT){
								return " $LINK ( $NAME$num < ".(intval($val)+$SEARCH_OPTION[$type][$ID]["width"])." AND $NAME$num > ".(intval($val)-$SEARCH_OPTION[$type][$ID]["width"])." ) ";
							} else {
								return " $LINK ( $NAME$num > ".(intval($val)+$SEARCH_OPTION[$type][$ID]["width"])." OR $NAME$num < ".(intval($val)-$SEARCH_OPTION[$type][$ID]["width"])." ) ";
							}
	
						} else { // Exact search
							if (!$NOT){
								return " $LINK ( $NAME$num = ".(intval($val)).") ";
							} else {
								return " $LINK ( $NAME$num <> ".(intval($val)).") ";
							}
						}
					}
				}
			break;
		}
	}


	$ADD="";
	if (($NOT&&$val!="NULL")||$val=='^$') {
		$ADD=" OR $NAME$num IS NULL";
	}

	return " $LINK ( ".$NAME.$num.makeTextSearch($val,$NOT)." $ADD ) ";

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

	// Security test for order
	if ($order!="ASC"){
		$order="DESC";
	}

	$table=$SEARCH_OPTION[$type][$ID]["table"];
	$field=$SEARCH_OPTION[$type][$ID]["field"];
	$linkfield=$SEARCH_OPTION[$type][$ID]["linkfield"];


	if (isset($CFG_GLPI["union_search_type"][$type])){
		return " ORDER BY ITEM_$key $order ";
	}

	// Plugin can override core definition for its type
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


	switch($table.".".$field){
		case "glpi_auth_tables.name" :
			return " ORDER BY glpi_users.auth_method, glpi_auth_ldap.name, glpi_auth_mail.name $order ";
		break;
		case "glpi_contracts.expire":
			return " ORDER BY ADDDATE(glpi_contracts.begin_date, INTERVAL glpi_contracts.duration MONTH) $order ";
		break;
		case "glpi_contracts.expire_notice":
			return " ORDER BY ADDDATE(glpi_contracts.begin_date, INTERVAL (glpi_contracts.duration-glpi_contracts.notice) MONTH) $order ";
		break;
		case "glpi_users.name" :
			if (!empty($SEARCH_OPTION[$type][$ID]["linkfield"])){
				$linkfield="_".$SEARCH_OPTION[$type][$ID]["linkfield"];

				return " ORDER BY ".$table.$linkfield.".realname $order, ".$table.$linkfield.".firstname $order, ".$table.$linkfield.".name $order";
			}
			break;
		case "glpi_networking_ports.ifaddr" :
            		return " ORDER BY INET_ATON($table.$field) $order ";
            	break;
	}

	//// Default cases

	// Link with plugin tables 
	if ($type<=1000){
		if (preg_match("/^glpi_plugin_([a-zA-Z]+)/", $table, $matches) 
		|| preg_match("/^glpi_dropdown_plugin_([a-zA-Z]+)/", $table, $matches) ){
			if (count($matches)==2){
				$plug=$matches[1];


				$function='plugin_'.$plug.'_addOrderBy';
				if (function_exists($function)){
					$out=$function($type,$ID,$order,$key);
					if (!empty($out)){
						return $out;
					}
				} 
			}
		} 
	}

	// Preformat items
	if (isset($SEARCH_OPTION[$type][$ID]["datatype"])){
		switch ($SEARCH_OPTION[$type][$ID]["datatype"]){
			case "date_delay":
				return " ORDER BY ADDDATE($table.".$SEARCH_OPTION[$type][$ID]["datafields"][1].", INTERVAL $table.".$SEARCH_OPTION[$type][$ID]["datafields"][2]." MONTH) $order ";
			break;
		}
	}

	//return " ORDER BY $table.$field $order ";
	return " ORDER BY ITEM_$key $order ";


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
	global $CFG_GLPI;

	$toview=array();

	// Add first element (name)
	array_push($toview,1);
	
	// Add entity view : 
	if (isMultiEntitiesMode() && (isset($CFG_GLPI["union_search_type"][$type]) || isset($CFG_GLPI["recursive_type"][$type]) || count($_SESSION["glpiactiveentities"])>1)) {
		array_push($toview,80);  
	}
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
	global $CFG_GLPI, $LINK_ID_TABLE;
	
	switch ($type){
		case RESERVATION_TYPE:
			$ret = "glpi_reservation_item.active as ACTIVE, ";
		break;
		case CARTRIDGE_TYPE:
			$ret = "glpi_cartridges_type.alarm as ALARM, ";
		break;
		case CONSUMABLE_TYPE:
			$ret = "glpi_consumables_type.alarm as ALARM, ";
		break;
		default :
			$ret = "";
		break;
	}
	if (isset($CFG_GLPI["recursive_type"][$type])) {
		$ret .= $LINK_ID_TABLE[$type].".FK_entities, ".$LINK_ID_TABLE[$type].".recursive, ";
	}
	return $ret;
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
	$NAME="ITEM";
	if ($meta) {
		$NAME="META";
		if ($LINK_ID_TABLE[$meta_type]!=$table)
			$addtable="_".$meta_type;
	}

	// Plugin can override core definition for its type
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

	switch ($table.".".$field){
		// Contact for display in the enterprise item
		case "glpi_contacts.completename":
			return " GROUP_CONCAT( DISTINCT CONCAT(".$table.$addtable.".name, ' ', ".$table.$addtable.".firstname) SEPARATOR '$$$$') AS ".$NAME."_$num, ";
		break;
		case "glpi_users.name" :
			if ($type!=USER_TYPE){
				$linkfield="";
				if (!empty($SEARCH_OPTION[$type][$ID]["linkfield"]))
					$linkfield="_".$SEARCH_OPTION[$type][$ID]["linkfield"];
	
				if ($meta){
					return " CONCAT(".$table.$linkfield.$addtable.".realname,' ',".$table.$linkfield.$addtable.".firstname) AS ".$NAME."_$num, ";
				} else {
					return $table.$linkfield.$addtable.".".$field." AS ".$NAME."_$num,
						".$table.$linkfield.$addtable.".realname AS ".$NAME."_".$num."_2,
						".$table.$linkfield.$addtable.".ID AS ".$NAME."_".$num."_3,
						".$table.$linkfield.$addtable.".firstname AS ".$NAME."_".$num."_4,";
				}
			}
		break;


		case "glpi_contracts.expire_notice" : // ajout jmd
			return $table.$addtable.".begin_date AS ".$NAME."_$num, ".$table.$addtable.".duration AS ".$NAME."_".$num."_2, ".$table.$addtable.".notice AS ".$NAME."_".$num."_3, ";
		break;
		case "glpi_contracts.expire" : // ajout jmd
			return $table.$addtable.".begin_date AS ".$NAME."_$num, ".$table.$addtable.".duration AS ".$NAME."_".$num."_2, ";
		break;
		case "glpi_softwarelicenses.number":
			return " FLOOR( SUM($table$addtable.$field) * COUNT(DISTINCT $table$addtable.ID) / COUNT($table$addtable.ID) ) AS ".$NAME."_".$num.", MIN($table$addtable.$field) AS ".$NAME."_".$num."_2, ";
		break;
		case "glpi_inst_software.count" :
			return " COUNT(DISTINCT glpi_inst_software$addtable.ID) AS ".$NAME."_".$num.", ";
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
		case "glpi_tracking.count" :
			return " COUNT(DISTINCT glpi_tracking$addtable.ID) AS ".$NAME."_".$num.", ";
		break;
		case "glpi_networking_ports.ifmac" :
			if ($type==COMPUTER_TYPE)
				return " GROUP_CONCAT( DISTINCT ".$table.$addtable.".".$field." SEPARATOR '$$$$') AS ".$NAME."_$num, GROUP_CONCAT( DISTINCT DEVICE_".NETWORK_DEVICE.".specificity  SEPARATOR '$$$$') AS ".$NAME."_".$num."_2, ";
			else return " GROUP_CONCAT( DISTINCT ".$table.$addtable.".".$field." SEPARATOR '$$$$') AS ".$NAME."_$num, ";
		break;
		case "glpi_profiles.name" :
			if ($type==USER_TYPE){
				return " GROUP_CONCAT( ".$table.$addtable.".".$field." SEPARATOR '$$$$') AS ".$NAME."_$num, 
					GROUP_CONCAT( glpi_entities.completename SEPARATOR '$$$$') AS ".$NAME."_".$num."_2,
					GROUP_CONCAT( glpi_users_profiles.recursive SEPARATOR '$$$$') AS ".$NAME."_".$num."_3,";
			} 
		break;
		case "glpi_entities.completename" :
			if ($type==USER_TYPE){
				return " GROUP_CONCAT( ".$table.$addtable.".completename SEPARATOR '$$$$') AS ".$NAME."_$num, 
					GROUP_CONCAT( glpi_profiles.name SEPARATOR '$$$$') AS ".$NAME."_".$num."_2,
					GROUP_CONCAT( glpi_users_profiles.recursive SEPARATOR '$$$$') AS ".$NAME."_".$num."_3,";
			} else {
				return $table.$addtable.".completename AS ".$NAME."_$num, ".$table.$addtable.".ID AS ".$NAME."_".$num."_2, ";
			}
			break;
/*		case "glpi_groups.name" :
			if ($type==USER_TYPE){
				return " GROUP_CONCAT( DISTINCT ".$table.$addtable.".".$field." SEPARATOR '$$$$') AS ".$NAME."_$num, ";
			} else {
				return $table.$addtable.".".$field." AS ".$NAME."_$num, ";
			}
		break;
*/
		case "glpi_auth_tables.name":
			return "glpi_users.auth_method AS ".$NAME."_".$num.", glpi_users.id_auth AS ".$NAME."_".$num."_2, glpi_auth_ldap".$addtable.".".$field." AS ".$NAME."_".$num."_3, glpi_auth_mail".$addtable.".".$field." AS ".$NAME."_".$num."_4, ";
		break;
		case "glpi_softwarelicenses.name" :
		case "glpi_softwareversions.name" :
			if ($meta){
				return " GROUP_CONCAT( DISTINCT CONCAT(glpi_software.name, ' - ',".$table.$addtable.".$field) SEPARATOR '$$$$') AS ".$NAME."_".$num.", ";
			} 
		break;
		case "glpi_softwarelicenses.serial" :
		case "glpi_softwarelicenses.otherserial" :
		case "glpi_softwarelicenses.expire" :
		case "glpi_softwarelicenses.comments" :
		case "glpi_softwareversions.comments" :
			if ($meta){
				return " GROUP_CONCAT( DISTINCT CONCAT(glpi_software.name, ' - ',".$table.$addtable.".$field) SEPARATOR '$$$$') AS ".$NAME."_".$num.", ";
			} else {
				return " GROUP_CONCAT( DISTINCT CONCAT($table$addtable.name, ' - ', $table$addtable.$field) SEPARATOR '$$$$') AS ".$NAME."_".$num.", ";
			}
		break;
		case "glpi_dropdown_state.name":
			if ($meta && $meta_type==SOFTWARE_TYPE) {
				return " GROUP_CONCAT( DISTINCT CONCAT(glpi_software.name, ' - ', glpi_softwareversions$addtable.name, ' - ', ".$table.$addtable.".$field) SEPARATOR '$$$$') AS ".$NAME."_".$num.", ";				
			} else if ($type==SOFTWARE_TYPE) {
				return " GROUP_CONCAT( DISTINCT CONCAT(glpi_softwareversions.name, ' - ', ".$table.$addtable.".$field) SEPARATOR '$$$$') AS ".$NAME."_".$num.", ";								
			} 		
		break;
		case "glpi_computerdisks.freepercent" :
			return " GROUP_CONCAT( ".($meta?"DISTINCT":"")." ROUND(100*".$table.$addtable.".freesize / ".$table.$addtable.".totalsize) SEPARATOR '$$$$') AS ".$NAME."_".$num.", ";
		break;
	}

	//// Default cases
	// Link with plugin tables 
	if ($type<=1000){
		if (preg_match("/^glpi_plugin_([a-zA-Z]+)/", $table, $matches) 
		|| preg_match("/^glpi_dropdown_plugin_([a-zA-Z]+)/", $table, $matches) ){
			if (count($matches)==2){
				$plug=$matches[1];

				$function='plugin_'.$plug.'_addSelect';
				if (function_exists($function)){
					$out=$function($type,$ID,$num);
					if (!empty($out)){
						return $out;
					}
				} 
			}
		} 
	}
	// Preformat items
	if (isset($SEARCH_OPTION[$type][$ID]["datatype"])){
		switch ($SEARCH_OPTION[$type][$ID]["datatype"]){
			case "date_delay":
				if ($meta){
					return " GROUP_CONCAT( DISTINCT ADDDATE($table$addtable.".$SEARCH_OPTION[$type][$ID]["datafields"][1].", INTERVAL $table$addtable.".$SEARCH_OPTION[$type][$ID]["datafields"][2]." MONTH) SEPARATOR '$$$$') AS ".$NAME."_$num, ";
				} else {
					return $table.$addtable.".".$SEARCH_OPTION[$type][$ID]["datafields"][1]." AS ".$NAME."_$num, ".$table.$addtable.".".$SEARCH_OPTION[$type][$ID]["datafields"][2]." AS ".$NAME."_".$num."_2, ";
				}
			break;
			case "itemlink" :
				if ($meta){
					return " GROUP_CONCAT( DISTINCT ".$table.$addtable.".".$field." SEPARATOR '$$$$') AS ".$NAME."_$num, ";
				}
				else {
					return $table.$addtable.".".$field." AS ".$NAME."_$num, ".$table.$addtable.".ID AS ".$NAME."_".$num."_2, ";
				}
			break;


		}
	}


	// Default case
	if ($meta || 
		(isset($SEARCH_OPTION[$type][$ID]["forcegroupby"]) && $SEARCH_OPTION[$type][$ID]["forcegroupby"])){
		return " GROUP_CONCAT( DISTINCT ".$table.$addtable.".".$field." SEPARATOR '$$$$') AS ".$NAME."_$num, ";
	}
	else {
		return $table.$addtable.".".$field." AS ".$NAME."_$num, ";
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
function addWhere($link,$nott,$type,$ID,$val,$meta=0){
	global $LINK_ID_TABLE,$LANG,$SEARCH_OPTION,$PLUGIN_HOOKS;

	$table=$SEARCH_OPTION[$type][$ID]["table"];
	$field=$SEARCH_OPTION[$type][$ID]["field"];
	
	$inittable=$table;
	if ($meta&&$LINK_ID_TABLE[$type]!=$table) {
		$table.="_".$type;
	}

	// Hack to allow search by ID on every sub-table
	if (preg_match('/^\$\$\$\$([0-9]+)$/',$val,$regs)){
 		return $link." ($table.ID ".($nott?"<>":"=").$regs[1].") ";
 	}

	$SEARCH=makeTextSearch($val,$nott);

	// Plugin can override core definition for its type
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

	switch ($inittable.".".$field){
		case "glpi_users.name" :
			$linkfield="";
			if (!empty($SEARCH_OPTION[$type][$ID]["linkfield"])){
				$linkfield="_".$SEARCH_OPTION[$type][$ID]["linkfield"];

				if ($meta&&$LINK_ID_TABLE[$type]!=$inittable) {
					$table=$inittable;
					$linkfield.="_".$type;
				}
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

		case "glpi_computerdisks.totalsize" : // -> number
		case "glpi_computerdisks.freesize" : // -> number
		case "glpi_computerdisks.freepercent" : // -> decimal : need to add computation param and unit

			$compute_size=$table.".".$field;
			$larg=1000;
			switch ($inittable.".".$field){
				case "glpi_computerdisks.freepercent";
					$larg=2;
					$compute_size="ROUND(100*$table.freesize/$table.totalsize)";
				break;
			}
			$search=array("/\&lt;/","/\&gt;/");
			$replace=array("<",">");
			$val=preg_replace($search,$replace,$val);
			if (preg_match("/([<>])([=]*)[[:space:]]*([0-9]*)/",$val,$regs)){
				if ($nott){
					if ($regs[1]=='<') {
						$regs[1]='>';
					} else {
						$regs[1]='<';
					}
				} 
				$regs[1].=$regs[2];
				return $link." ( $compute_size ".$regs[1]." ".$regs[3]." ) ";
			} else {
				

				if (!$nott){
					return $link." ( $compute_size < ".(intval($val)+$larg)." AND $compute_size > ".(intval($val)-$larg)." ) ";
				} else {
					return $link." ( $compute_size > ".(intval($val)+$larg)." OR $compute_size < ".(intval($val)-$larg)." ) ";
				}
			}
		break;
		case "glpi_networking_ports.ifmac" :
			$ADD="";
			if ($type==COMPUTER_TYPE){
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
		case "glpi_contracts.expire" :
			$search=array("/\&lt;/","/\&gt;/");
			$replace=array("<",">");
			$val=preg_replace($search,$replace,$val);
			if (preg_match("/([<>=])(.*)/",$val,$regs)){
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
			if (preg_match("/([<>])(.*)/",$val,$regs)){
				return $link." $table.notice<>0 AND DATEDIFF(ADDDATE($table.begin_date, INTERVAL ($table.duration - $table.notice) MONTH),CURDATE() )".$regs[1].$regs[2]." ";
			} else {
				return $link." ADDDATE($table.begin_date, INTERVAL ($table.duration - $table.notice) MONTH) $SEARCH ";		
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
			if (preg_match("/$val/i",getAmortTypeName(1))) {
				$val=1;
			} else if (preg_match("/$val/i",getAmortTypeName(2))) {
				$val=2;
			} 

			
			if (is_int($val)&&$val>0){
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
		case "glpi_contracts.renewal":
			return $link." ".$table.".".$field."=".getContractRenewalIDByName($val);
		break;
	}


	//// Default cases

	// Link with plugin tables 
	if ($type<=1000){
		if (preg_match("/^glpi_plugin_([a-zA-Z]+)/", $inittable, $matches) 
		|| preg_match("/^glpi_dropdown_plugin_([a-zA-Z]+)/", $inittable, $matches) ){
			if (count($matches)==2){
				$plug=$matches[1];

				$function='plugin_'.$plug.'_addWhere';
				if (function_exists($function)){
					$out=$function($link,$nott,$type,$ID,$val);
					if (!empty($out)){
						return $out;
					}
				} 
			}
		} 
	}

	// Preformat items
	if (isset($SEARCH_OPTION[$type][$ID]["datatype"])){
		switch ($SEARCH_OPTION[$type][$ID]["datatype"]){
			case "date":
			case "datetime":
			case "date_delay":
				$date_computation=$table.".".$field;
				$interval_search=" MONTH ";

			
				if ($SEARCH_OPTION[$type][$ID]["datatype"]=="date_delay"){
					$date_computation="ADDDATE($table.".$SEARCH_OPTION[$type][$ID]["datafields"][1].", INTERVAL $table.".$SEARCH_OPTION[$type][$ID]["datafields"][2]." MONTH)"; 
				}
				
				$search=array("/\&lt;/","/\&gt;/");
				$replace=array("<",">");
				$val=preg_replace($search,$replace,$val);
				if (preg_match("/([<>=])(.*)/",$val,$regs)){
					if (is_numeric($regs[2])){
						return $link." NOW() ".$regs[1]." ADDDATE($date_computation, INTERVAL ".$regs[2]." $interval_search) ";	
					} else {
						// Reformat date if needed
						$regs[2]=preg_replace('@(\d{1,2})(-|/)(\d{1,2})(-|/)(\d{4})@','\5-\3-\1',$regs[2]);
						if (preg_match('/[0-9]{2,4}-[0-9]{1,2}-[0-9]{1,2}/',$regs[2])){
							return $link." $date_computation ".$regs[1]." '".$regs[2]."'";
						} else {
							return "";
						}
					}
				} else { // standard search
					// Date format modification if needed
					$val=preg_replace('@(\d{1,2})(-|/)(\d{1,2})(-|/)(\d{4})@','\5-\3-\1',$val);
					$SEARCH=makeTextSearch($val,$nott);
					$ADD="";	
					if ($nott) {
						$ADD=" OR $date_computation IS NULL";
					}
					return $link." ( $date_computation $SEARCH $ADD )";
				}
			break;

			case "bool":
				if (!is_numeric($val)){
					if (strcasecmp($val,$LANG["choice"][0])==0){
						$val=0;
					} else 	if (strcasecmp($val,$LANG["choice"][1])==0){
						$val=1;
					}
				}
				// No break here : use number comparison case
			case "number":
			case "decimal":
				$search=array("/\&lt;/","/\&gt;/");
				$replace=array("<",">");
				$val=preg_replace($search,$replace,$val);
				if (preg_match("/([<>])([=]*)[[:space:]]*([0-9]*)/",$val,$regs)){
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
					if (is_numeric($val)){
						if (isset($SEARCH_OPTION[$type][$ID]["width"])){
							$ADD="";
							if ($nott&&$val!="NULL") $ADD=" OR $table.$field IS NULL";
							if ($nott){
								return $link." ($table.$field < ".(intval($val)-$SEARCH_OPTION[$type][$ID]["width"])." OR $table.$field > ".(intval($val)+$SEARCH_OPTION[$type][$ID]["width"])." ".$ADD." ) ";
							} else {
								return $link." (($table.$field >= ".(intval($val)-$SEARCH_OPTION[$type][$ID]["width"])." AND $table.$field <= ".(intval($val)+$SEARCH_OPTION[$type][$ID]["width"]).") ".$ADD." ) ";
							}
						} else {
							if (!$nott){
								return " $link ( $table.$field = ".(intval($val)).") ";
							} else {
								return " $link ( $table.$field <> ".(intval($val)).") ";
							}
						}
					}
				}
			break;
		}
	}



	// Default case 
	$ADD="";	
	if (($nott&&$val!="NULL")||$val=='^$') {
		$ADD=" OR $table.$field IS NULL";
	}
	
	return $link." ($table.$field $SEARCH ".$ADD." ) ";


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
 *@param $type device type
 *@param $ID ID of the SEARCH_OPTION item
 *@param $data array containing data results
 *@param $num item num in the request
 *@param $meta is a meta item ?
 *
 *@return string to print
 *
 **/
function giveItem ($type,$ID,$data,$num,$meta=0){
	global $CFG_GLPI,$SEARCH_OPTION,$INFOFORM_PAGES,$LANG,$PLUGIN_HOOKS;

	if (isset($CFG_GLPI["union_search_type"][$type])){
		return giveItem ($data["TYPE"],$ID,$data,$num);
	}

	// Plugin can override core definition for its type
	if ($type>1000){
		if (isset($PLUGIN_HOOKS['plugin_types'][$type])){
			$function='plugin_'.$PLUGIN_HOOKS['plugin_types'][$type].'_giveItem';
			if (function_exists($function)){
				$out=$function($type,$ID,$data,$num);
				if (!empty($out)){
					return $out;
				}
			} 
		} 
	}

	$NAME="ITEM_";
	if ($meta){
		$NAME="META_";
	}
	$table=$SEARCH_OPTION[$type][$ID]["table"];
	$field=$SEARCH_OPTION[$type][$ID]["field"];
	$linkfield=$SEARCH_OPTION[$type][$ID]["linkfield"];

	switch ($table.'.'.$field){
		case "glpi_users.name" :		
			// USER search case
			if (!empty($linkfield)){
				return formatUserName($data[$NAME.$num."_3"],$data[$NAME.$num],$data[$NAME.$num."_2"],$data[$NAME.$num."_4"],1);
			}
		break;
		case "glpi_profiles.name" :
			if ($type==USER_TYPE){
				$out="";

				$split=explode("$$$$",$data[$NAME.$num]);
				$split2=explode("$$$$",$data[$NAME.$num."_2"]);
				$split3=explode("$$$$",$data[$NAME.$num."_3"]);

				$count_display=0;
				$added=array();
				for ($k=0;$k<count($split);$k++)
					if (strlen(trim($split[$k]))>0){
						$text=$split[$k]." - ".$split2[$k];
						if ($split3[$k]){
							$text.=" (R)";
						}
						if (!in_array($text,$added)){
							if ($count_display) $out.= "<br>";
							$count_display++;
							$out.= $text;
							$added[]=$text;
						}
					}
				return $out;
			} 
		break;
		case "glpi_entities.completename" :
			 if ($type==USER_TYPE){	
				$out="";

				$split=explode("$$$$",$data[$NAME.$num]);
				$split2=explode("$$$$",$data[$NAME.$num."_2"]);
				$split3=explode("$$$$",$data[$NAME.$num."_3"]);
				$added=array();
				$count_display=0;
				for ($k=0;$k<count($split);$k++)
					if (strlen(trim($split[$k]))>0){
						$text=$split[$k]." - ".$split2[$k];
						if ($split3[$k]){
							$text.=" (R)";
						}
						if (!in_array($text,$added)){
							if ($count_display) $out.= "<br>";
							$count_display++;
							$out.= $text;
							$added[]=$text;
						}
					}
				return $out;
			} else {
				// Set name for Root entity
				if ($data[$NAME.$num."_2"]==0){
					$data[$NAME.$num]=$LANG["entity"][2];
				} 
			}
			break;

		case "glpi_type_docs.icon" :
			if (!empty($data[$NAME.$num])){
				return "<img class='middle' alt='' src='".$CFG_GLPI["typedoc_icon_dir"]."/".$data[$NAME.$num]."'>";
			}
			else {
				return "&nbsp;";
			}
		break;	

		case "glpi_docs.filename" :		
			return getDocumentLink($data[$NAME.$num]);
		break;		
		case "glpi_docs.link" :
		case "glpi_device_hdd.specif_default" :
		case "glpi_device_ram.specif_default" :
		case "glpi_device_processor.specif_default" :
			return $data[$NAME.$num];
			break;
		case "glpi_networking_ports.ifmac" :
			$out="";
			if ($type==COMPUTER_TYPE){
				$displayed=array();
				if (!empty($data[$NAME.$num."_2"])){
					$split=explode("$$$$",$data[$NAME.$num."_2"]);
					$count_display=0;
					for ($k=0;$k<count($split);$k++){
						$lowstr=strtolower($split[$k]);
						if (strlen(trim($split[$k]))>0
							&&!in_array($lowstr,$displayed)){	
							if ($count_display) {
								$out.= "<br>";
							} 
							$count_display++;
							$out.= $split[$k];
							$displayed[]=$lowstr;
						}
					}
					if (!empty($data[$NAME.$num])) $out.= "<br>";
				}

				if (!empty($data[$NAME.$num])){
					$split=explode("$$$$",$data[$NAME.$num]);
					$count_display=0;
					for ($k=0;$k<count($split);$k++){
						$lowstr=strtolower($split[$k]);
						if (strlen(trim($split[$k]))>0 
							&&!in_array($lowstr,$displayed)){	
							if ($count_display) {
								$out.= "<br>";
							}
							$count_display++;
							$out.= $split[$k];
							$displayed[]=$lowstr;
						}
					}
				}
				return $out;
			}
			break;
		case "glpi_contracts.duration":
		case "glpi_contracts.notice":
		case "glpi_contracts.periodicity":
		case "glpi_contracts.facturation":
			if (!empty($data[$NAME.$num])){
				return $data[$NAME.$num]." ".$LANG["financial"][57];
			} else {
				return "&nbsp;";
			}
			break;
		case "glpi_contracts.renewal":
			return getContractRenewalName($data[$NAME.$num]);
			break;

		case "glpi_contracts.expire_notice": // ajout jmd
			if ($data[$NAME.$num]!='' && !empty($data[$NAME.$num])){
				return getExpir($data[$NAME.$num],$data[$NAME.$num."_2"],$data[$NAME.$num."_3"]);
			} else {
				return "&nbsp;"; 
			}
		case "glpi_contracts.expire": // ajout jmd
			if ($data[$NAME.$num]!='' && !empty($data[$NAME.$num])){
				return getExpir($data[$NAME.$num],$data[$NAME.$num."_2"]);
			} else {
				return "&nbsp;"; 
			}
		case "glpi_infocoms.amort_time":
			if (!empty($data[$NAME.$num])){
				return $data[$NAME.$num]." ".$LANG["financial"][9];
			} else { 
				return "&nbsp;";
			}
			break;
		case "glpi_infocoms.warranty_duration":
			if (!empty($data[$NAME.$num])){
				return $data[$NAME.$num]." ".$LANG["financial"][57];
			} else {
				return "&nbsp;";
			}
			break;
		case "glpi_infocoms.amort_type":
			return getAmortTypeName($data[$NAME.$num]);
			break;
		case "glpi_infocoms.alert":
			if ($data[$NAME.$num]==pow(2,ALERT_END)){
				return $LANG["financial"][80];
			} 
			return "";
			break;
		case "glpi_contracts.alert":
			switch ($data[$NAME.$num]){
				case pow(2,ALERT_END);
					return $LANG["buttons"][32];
					break;
				case pow(2,ALERT_NOTICE);
					return $LANG["financial"][10];
					break;
				case pow(2,ALERT_END)+pow(2,ALERT_NOTICE);
					return $LANG["buttons"][32]." + ".$LANG["financial"][10];
					break;
			} 
			return "";
			break;
		case "glpi_tracking.count":
			if ($data[$NAME.$num]>0&&haveRight("show_all_ticket","1")){
				$out= "<a href=\"".$CFG_GLPI["root_doc"]."/front/tracking.php?reset=reset_before&status=all&type=$type&item=".$data['ID']."\">";
				$out.= $data[$NAME.$num];
				$out.="</a>";
			} else {
				$out= $data[$NAME.$num];
			}
			return $out;
			break;

		case "glpi_softwarelicenses.number":

			if ($data[$NAME.$num."_2"]==-1){
				return $LANG["software"][4];
			} else {
				if (empty($data[$NAME.$num])){
					return 0;
				} else {
					return $data[$NAME.$num];
				}
			}
		break;

		case "glpi_auth_tables.name" :
			return getAuthMethodName($data[$NAME.$num], $data[$NAME.$num."_2"], 1,$data[$NAME.$num."_3"].$data[$NAME.$num."_4"]);
			break;
		case "glpi_reservation_item.comments" :
			if (empty($data[$NAME.$num])){
				return  "<a href='".$CFG_GLPI["root_doc"]."/front/reservation.php?comment=".$data["refID"]."' title='".$LANG["reservation"][22]."'>".$LANG["common"][49]."</a>";
			}else{
				return "<a href='".$CFG_GLPI["root_doc"]."/front/reservation.php?comment=".$data['refID']."' title='".$LANG["reservation"][22]."'>". resume_text($data[$NAME.$num])."</a>";
			}
			break;
	}


	//// Default case 

	// Link with plugin tables : need to know left join structure
	if ($type<=1000){
		if (preg_match("/^glpi_plugin_([a-zA-Z]+)/", $table.'.'.$field, $matches) 
		|| preg_match("/^glpi_dropdown_plugin_([a-zA-Z]+)/", $table.'.'.$field, $matches) ){
			if (count($matches)==2){
				$plug=$matches[1];

				$function='plugin_'.$plug.'_giveItem';
				if (function_exists($function)){
					$out=$function($type,$ID,$data,$num);
					if (!empty($out)){
						return $out;
					}
				} 
			}
		} 
	}

	$unit='';
	if (isset($SEARCH_OPTION[$type][$ID]['unit'])){
		$unit=$SEARCH_OPTION[$type][$ID]['unit'];
	}


	// Preformat items
	if (isset($SEARCH_OPTION[$type][$ID]["datatype"])){
		switch ($SEARCH_OPTION[$type][$ID]["datatype"]){
			case "itemlink":
				if (!empty($data[$NAME.$num."_2"])){
					$out= "<a href=\"".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[$type]."?ID=".$data[$NAME.$num."_2"]."\">";
					$out.= $data[$NAME.$num];
					if ($_SESSION["glpiview_ID"]||empty($data[$NAME.$num])) {
						$out.= " (".$data[$NAME.$num."_2"].")";
					}
					$out.= "</a>";
					return $out;
				}
				break;
			case "text":
				return nl2br($data[$NAME.$num]);
				break;
			case "date":
				return convDate($data[$NAME.$num]);
				break;
			case "datetime":
				return convDateTime($data[$NAME.$num]);
				break;
			case "realtime":
				return getRealtime($data[$NAME.$num]);
				break;
			case "date_delay":
				if ($data[$NAME.$num]!='' && !empty($data[$NAME.$num])){
					return getWarrantyExpir($data[$NAME.$num],$data[$NAME.$num."_2"]);
				} else {
					return "&nbsp;"; 
				}
				break;
			case "email" :
				$email=trim($data[$NAME.$num]);
				if (!empty($email)){
					return "<a href='mailto:$email'>$email</a>";
				} else {
					return "&nbsp;";
				}
				break;	
			case "weblink" :
				$orig_link=trim($data[$NAME.$num]);
				if (!empty($orig_link)){
					if (strlen($orig_link)>30){
						$link=utf8_substr($orig_link,0,30)."...";
					} else {
						$link=$orig_link;
					}
					return "<a href=\"$orig_link\" target='_blank'>$link</a>";
				} else {
					return "&nbsp;";
				}
				break;	
			case "number":
				return formatNumber($data[$NAME.$num],false,0).$unit;
				break;
			case "decimal":
				return formatNumber($data[$NAME.$num]).$unit;
				break;
			case "bool":
				return getYesNo($data[$NAME.$num]).$unit;
				break;

		}
	}

	// Manage items with need group by / group_concat
	if (isset($SEARCH_OPTION[$type][$ID]['forcegroupby']) && $SEARCH_OPTION[$type][$ID]['forcegroupby']){
		$out="";
		$split=explode("$$$$",$data[$NAME.$num]);
		$count_display=0;
		for ($k=0;$k<count($split);$k++)
			if (strlen(trim($split[$k]))>0){
				if ($count_display) $out.= "<br>";
				$count_display++;
				$out.= $split[$k].$unit;
			}
		return $out;	
	}



	return $data[$NAME.$num].$unit;


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
		$addmetanum="_".$meta_type;
		$AS= " AS ".$nt.$addmetanum;
		$nt=$nt."_".$meta_type;
		//$rt.="_".$meta_type;
	}

	// Auto link
	if ($ref_table==$new_table) return "";
	
	if (in_array(translate_table($new_table,$device_type,$meta_type).".".$linkfield,$already_link_tables)) {
		return "";
	} else {
		array_push($already_link_tables,translate_table($new_table,$device_type,$meta_type).".".$linkfield); 
	}

	// Plugin can override core definition for its type
	if ($type>1000){
		if (isset($PLUGIN_HOOKS['plugin_types'][$type])){
			$function='plugin_'.$PLUGIN_HOOKS['plugin_types'][$type].'_addLeftJoin';
			if (function_exists($function)){
				$out=$function($type,$ref_table,$new_table,$linkfield,$already_link_tables);
				if (!empty($out)){
					return $out;
				}
			} 
		} 
	}

	
	switch ($new_table){
		// No link
		case "glpi_auth_tables":
			return " LEFT JOIN glpi_auth_ldap ON (glpi_users.auth_method = ".AUTH_LDAP." AND glpi_users.id_auth = glpi_auth_ldap.ID) 
				LEFT JOIN glpi_auth_mail ON (glpi_users.auth_method = ".AUTH_MAIL." AND glpi_users.id_auth = glpi_auth_mail.ID) ";
		break;
		case "glpi_reservation_item":
			return "";
		break;
		case "glpi_computerdisks":
			if ($meta){
				return " INNER JOIN $new_table $AS ON ($rt.ID = $nt.FK_computers) ";
			} else {
				return " LEFT JOIN $new_table $AS ON ($rt.ID = $nt.FK_computers) ";
			}
		break;
		case "glpi_dropdown_filesystems":
			$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_computerdisks",$linkfield);
		return $out." LEFT JOIN $new_table $AS ON (glpi_computerdisks.FK_filesystems = $nt.ID) ";
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
				return $out." LEFT JOIN $new_table $AS ON (glpi_contact_enterprise.FK_enterprise = $nt.ID ".
				getEntitiesRestrictRequest("AND","glpi_enterprises",'','',true).") ";
			} else {
				return " LEFT JOIN $new_table $AS ON ($rt.FK_enterprise = $nt.ID) ";
			}
		break;
		case "glpi_contacts":
			$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_contact_enterprise","FK_enterprise");
			return $out." LEFT JOIN $new_table $AS ON (glpi_contact_enterprise.FK_contact = $nt.ID ".
				getEntitiesRestrictRequest("AND","glpi_contacts",'','',true)." ) ";
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
			if ($type == SOFTWARE_TYPE) {
				// Return the infocom linked to the license, not the template linked to the software
				return addLeftJoin($type,$ref_table,$already_link_tables,"glpi_softwarelicenses",$linkfield) .
					" LEFT JOIN $new_table $AS ON (glpi_softwarelicenses.ID = $nt.FK_device AND $nt.device_type = ".SOFTWARELICENSE_TYPE.") ";	
			} else {
				return " LEFT JOIN $new_table $AS ON ($rt.ID = $nt.FK_device AND $nt.device_type='$type') ";
			}
		break;
		case "glpi_dropdown_state":
			if ($type == SOFTWARE_TYPE) {
				// Return the state of the version of the software
				$rt=translate_table("glpi_softwareversions",$meta,$meta_type);
				return addLeftJoin($type,$ref_table,$already_link_tables,"glpi_softwareversions",$linkfield,$device_type,$meta,$meta_type) .
					" LEFT JOIN $new_table $AS ON ($rt.state = $nt.ID)";
			} else {
				return " LEFT JOIN $new_table $AS ON ($rt.state = $nt.ID) ";				
			}		
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
				$out=addLeftJoin($type,$rt,$already_link_tables,"glpi_users_groups",$linkfield,$device_type,$meta,$meta_type);

				return $out." LEFT JOIN $new_table $AS ON (glpi_users_groups$addmetanum.FK_groups = $nt.ID) ";
			} else {
				return " LEFT JOIN $new_table $AS ON ($rt.$linkfield = $nt.ID) ";
			}

		break;
		case "glpi_contracts":
			$out=addLeftJoin($type,$rt,$already_link_tables,"glpi_contract_device",$linkfield,$device_type,$meta,$meta_type);
		return $out." LEFT JOIN $new_table $AS ON (glpi_contract_device$addmetanum.FK_contract = $nt.ID) ";
		break;
		case "glpi_dropdown_licensetypes":
			$rt=translate_table("glpi_softwarelicenses",$meta,$meta_type);
			return addLeftJoin($type,$ref_table,$already_link_tables,"glpi_softwarelicenses",$linkfield,$device_type,$meta,$meta_type) .
				" LEFT JOIN $new_table $AS ON ($rt.type = $nt.ID)";
			break;
		case "glpi_softwarelicenses":
			if (!$meta){
				return " LEFT JOIN $new_table $AS ON ($rt.ID = $nt.sID ".getEntitiesRestrictRequest("AND",$nt,'','',true).") ";
			} else {
				return "";
			}
		break;
		case "glpi_softwareversions":
			if (!$meta){
				return " LEFT JOIN $new_table $AS ON ($rt.ID = $nt.sID) ";
			} else {
				return "";
			}
		break;
		case "glpi_inst_software":
			$out=addLeftJoin($type,$rt,$already_link_tables,"glpi_softwareversions",$linkfield,$device_type,$meta,$meta_type);
		return $out." LEFT JOIN $new_table $AS ON (glpi_softwareversions$addmetanum.ID = $nt.vID) ";
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

			// Link with plugin tables : need to know left join structure
			if ($type<=1000){
				if (preg_match("/^glpi_plugin_([a-zA-Z]+)/", $new_table, $matches) 
				|| preg_match("/^glpi_dropdown_plugin_([a-zA-Z]+)/", $new_table, $matches) ){
					if (count($matches)==2){
						$plug=$matches[1];
						$function='plugin_'.$plug.'_addLeftJoin';
						if (function_exists($function)){
							$out=$function($type,$ref_table,$new_table,$linkfield,$already_link_tables);
							if (!empty($out)){
								return $out;
							}
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
 *@param $nullornott Used LEFT JOIN (null generation) or INNER JOIN for strict join
 *
 *
 *@return Meta Left join string
 *
 **/
function addMetaLeftJoin($from_type,$to_type,&$already_link_tables2,$nullornott){
	global $LINK_ID_TABLE;

	$LINK=" INNER JOIN ";
	if ($nullornott)
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
					return " $LINK glpi_connect_wire AS conn_periph_$to_type ON (conn_periph_$to_type.end2=glpi_computers.ID  AND conn_periph_$to_type.type='".PERIPHERAL_TYPE."') ".
						" $LINK glpi_peripherals ON (conn_periph_$to_type.end1=glpi_peripherals.ID) ";
					break;				
				case PHONE_TYPE :
					array_push($already_link_tables2,$LINK_ID_TABLE[PHONE_TYPE]);
					return " $LINK glpi_connect_wire AS conn_phones_$to_type ON (conn_phones_$to_type.end2=glpi_computers.ID  AND conn_phones_$to_type.type='".PHONE_TYPE."') ".
						" $LINK glpi_phones ON (conn_phones_$to_type.end1=glpi_phones.ID) ";
					break;			

				case SOFTWARE_TYPE :
					/// TODO: link licenses via installed software OR by affected/FK_computers ???
					array_push($already_link_tables2,$LINK_ID_TABLE[SOFTWARE_TYPE]);
					return " $LINK glpi_inst_software as inst_$to_type ON (inst_$to_type.cID = glpi_computers.ID) ".
						" $LINK glpi_softwareversions as glpi_softwareversions_$to_type ON ( inst_$to_type.vID=glpi_softwareversions_$to_type.ID ) ".
						" $LINK glpi_software ON (glpi_softwareversions_$to_type.sID = glpi_software.ID)".
						" $LINK glpi_softwarelicenses AS glpi_softwarelicenses_$to_type ON (glpi_software.ID=glpi_softwarelicenses_$to_type.sID " .
							getEntitiesRestrictRequest(' AND',"glpi_softwarelicenses_$to_type",'','',true).")";
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
					return " $LINK glpi_softwareversions as glpi_softwareversions_$to_type ON ( glpi_softwareversions_$to_type.sID = glpi_software.ID ) ".
						" $LINK glpi_inst_software as inst_$to_type ON (inst_$to_type.vID = glpi_softwareversions_$to_type.ID) ".
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
/**
 * Is the search item related to infocoms
 *
 *
 * @param $device_type item type
 * @param $searchID ID of the element in $SEARCH_OPTION
 * @return boolean
 *
 */
function isInfocomSearch($device_type,$searchID){
	global $CFG_GLPI;
	return (($searchID>=25&&$searchID<=28)
	||($searchID>=37&&$searchID<=38)
	||($searchID>=50&&$searchID<=59)
	||($searchID>=120&&$searchID<=122))&&in_array($device_type,$CFG_GLPI["infocom_types"]);
}

?>
