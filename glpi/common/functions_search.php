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
	global $lang,$HTMLRel,$SEARCH_OPTION;
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
			echo "<a href='$target?add_search_count=1'><img src=\"".$HTMLRel."pics/plus.png\" alt='+'></a>&nbsp;&nbsp;&nbsp;&nbsp;";
			if ($_SESSION["glpisearchcount"]>1)
			echo "<a href='$target?delete_search_count=1'><img src=\"".$HTMLRel."pics/moins.png\" alt='-'></a>&nbsp;&nbsp;&nbsp;&nbsp;";
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
        	reset($option);
		foreach ($options as $key => $val) {
			$v=$val["table"].".".$val["field"];
			echo "<option value=\"".$v."\""; 
			if(is_array($field)&&isset($field[$i]) && $v == $field[$i]) echo "selected";
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
	reset($option);
	foreach ($options as $key => $val) {
		$v=$val["table"].".".$val["field"];
		echo "<option value=\"".$v."\"";
		if($v == $sort) echo "selected";
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

	// TODO : Voir les elements rechercher
	
	$toview=array_unique($toview);
	$toview_count=count($toview);
	
	// Construct the request 
	//// 1 - SELECT
	$SELECT ="SELECT ";
	for ($i=0;$i<$toview_count;$i++){
		$SELECT.=$SEARCH_OPTION[$type][$toview[$i]]["table"].".".$SEARCH_OPTION[$type][$toview[$i]]["field"]." AS ITEM_$i, ";
	}
	// Add ID
	$SELECT.=$LINK_ID_TABLE[$type].".ID AS ID ";

	//// 2 - FROM AND LEFT JOIN 
	$FROM = " FROM ".$LINK_ID_TABLE[$type];
	$already_link_tables=array();
	array_push($already_link_tables,$LINK_ID_TABLE[$type]);

	for ($i=1;$i<$toview_count;$i++){
		if (!in_array($SEARCH_OPTION[$type][$toview[$i]]["table"],$already_link_tables))
			$FROM.=addLeftJoin($LINK_ID_TABLE[$type],$already_link_tables,$SEARCH_OPTION[$type][$toview[$i]]["table"]);
	}	

	//// 3 - WHERE	
	$WHERE = " WHERE '1'='1' ";
	if (in_array($LINK_ID_TABLE[$type],$deleted_tables))
		$WHERE.= " AND ".$LINK_ID_TABLE[$type].".deleted='$deleted' ";
	if (in_array($LINK_ID_TABLE[$type],$template_tables))
		$WHERE.= " AND ".$LINK_ID_TABLE[$type].".is_template='0' ";

	// TODO : add elements recherchés dans la recherche

	//// 4 - ORDER 
	$ORDER= "ORDER BY $sort $order";

	$QUERY=$SELECT.$FROM.$WHERE.$ORDER;
//	echo $QUERY;
	
	// Get it from database and DISPLAY
	if ($result = $db->query($QUERY)) {
		$numrows= $db->numrows($result);
		if ($start<$numrows) {

			// Pager
			$parameters="sort=$sort&amp;order=$order".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains);
			printPager($start,$numrows,$target,$parameters);

			// Produce headline
			echo "<div align='center'><table border='0' class='tab_cadre'><tr>";

			// TABLE HEADER
			for ($i=0;$i<$toview_count;$i++){
				echo "<th>";
				$field=$SEARCH_OPTION[$type][$toview[$i]]["table"].".".$SEARCH_OPTION[$type][$toview[$i]]["field"];
				if ($sort==$field) {
					if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
					else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
				}
				echo "<a href=\"$target?sort=$field&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains)."\">";
				echo $SEARCH_OPTION[$type][$toview[$i]]["name"]."</a></th>";
			}
			echo "</tr>";

			for ($i=$start; $i < $numrows && $i<($start+$cfg_features["list_limit"]); $i++) {
				$ID = $db->result($result, $i, "ID");
				$comp = new Computer;
				$comp->getfromDB($ID,0);
				
				echo "<tr class='tab_bg_2'>";
				// Print first element
				echo "<td><b>";
				echo "<a href=\"".$cfg_install["root"]."/".$INFOFORM_PAGES[$type]."?ID=$ID\">";
				echo $db->result($result, $i, "ITEM_0");
				echo "</a></b></td>";
				for ($j=1;$j<$toview_count;$j++){
				echo "<td>";
				echo $db->result($result, $i, "ITEM_$j");
				echo "</b></td>";
					
				}
                 echo "</tr>";
			}

			// Close Table
			echo "</table></div>";

			// Pager
			echo "<br>";
			printPager($start,$numrows,$target,$parameters);

		} else {
			echo "<div align='center'><b>".$lang["computers"][32]."</b></div>";
			
		}
	}

}

/**
* Generic Function to add left join to a request
*
*
*@param $ref_table reference table
*@param $already_link_tables array of tables already joined
*@param $new_table new table to join
*
*
*@return Left join strin
*
**/
function addLeftJoin ($ref_table,$already_link_tables,$new_table){

switch ($new_table){
	case "glpi_dropdown_locations":
		return " LEFT JOIN $new_table ON ($ref_table.location = $new_table.ID) ";
		break;
	
	default :
		return "";
		break;
}
}
?>