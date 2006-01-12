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
else $_GET["distinct"] = "N";
	

if (!isset($_GET["link"]))
	if (isset($_SESSION['search'][$type]["link"])) $_GET["link"]=$_SESSION['search'][$type]["link"];
	else $_GET["link"] = "";

if (!isset($_GET["field"]))
	if (isset($_SESSION['search'][$type]["field"])) $_GET["field"]=$_SESSION['search'][$type]["field"];
	else $_GET["field"] = array(0 => "view");

if (!isset($_GET["contains"]))
	if (isset($_SESSION['search'][$type]["contains"])) $_GET["contains"]=$_SESSION['search'][$type]["contains"];
	else $_GET["contains"] = array(0 => "");

if (!isset($_GET["link2"]))
	if (isset($_SESSION['search'][$type]["link2"])) $_GET["link2"]=$_SESSION['search'][$type]["link2"];
	else $_GET["link2"] = "";

if (!isset($_GET["field2"]))
	if (isset($_SESSION['search'][$type]["field2"])) $_GET["field2"]=$_SESSION['search'][$type]["field2"];
	else $_GET["field2"] = array(0 => "view");

if (!isset($_GET["contains2"]))
	if (isset($_SESSION['search'][$type]["contains2"])) $_GET["contains2"]=$_SESSION['search'][$type]["contains2"];
	else $_GET["contains2"] = array(0 => "");

if (!isset($_GET["type2"]))
	if (isset($_SESSION['search'][$type]["type2"])) $_GET["type2"]=$_SESSION['search'][$type]["type2"];
	else $_GET["type2"] = "";


if (!isset($_GET["sort"]))
	if (isset($_SESSION['search'][$type]["sort"])) $_GET["sort"]=$_SESSION['search'][$type]["sort"];
	else $_GET["sort"] = 1;

if (!isset($_SESSION["glpisearchcount"][$type])) $_SESSION["glpisearchcount"][$type]=1;
if (!isset($_SESSION["glpisearchcount2"][$type])) $_SESSION["glpisearchcount2"][$type]=0;

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
function searchForm($type,$target,$field="",$contains="",$sort= "",$deleted= "",$link="",$distinct="Y",$link2="",$contains2="",$field2="",$type2=""){
	global $lang,$HTMLRel,$SEARCH_OPTION,$cfg_install,$LINK_ID_TABLE,$deleted_tables;
	$options=$SEARCH_OPTION[$type];


		$names=array(
		COMPUTER_TYPE => $lang["Menu"][0],
//		NETWORKING_TYPE => $lang["Menu"][1],
		PRINTER_TYPE => $lang["Menu"][2],
		MONITOR_TYPE => $lang["Menu"][3],
		PERIPHERAL_TYPE => $lang["Menu"][16],
		SOFTWARE_TYPE => $lang["Menu"][4],
		);
	
	echo "<form method=get action=\"$target\">";
	echo "<div align='center'><table border='0' width='850' class='tab_cadre'>";
	echo "<tr><th colspan='5'><b>".$lang["search"][0].":</b></th></tr>";
	echo "<tr class='tab_bg_1'>";
	echo "<td align='center'>";
	echo "<table>";
	
	for ($i=0;$i<$_SESSION["glpisearchcount"][$type];$i++){
		echo "<tr><td align='right'>";
		if ($i==0){
			echo "<a href='".$cfg_install["root"]."/computers/index.php?add_search_count=1&amp;type=$type'><img src=\"".$HTMLRel."pics/plus.png\" alt='+' title='".$lang["search"][17]."'></a>&nbsp;&nbsp;&nbsp;&nbsp;";
			if ($_SESSION["glpisearchcount"][$type]>1)
			echo "<a href='".$cfg_install["root"]."/computers/index.php?delete_search_count=1&amp;type=$type'><img src=\"".$HTMLRel."pics/moins.png\" alt='-' title='".$lang["search"][18]."'></a>&nbsp;&nbsp;&nbsp;&nbsp;";

			if (isset($names[$type])){
				echo "<a href='".$cfg_install["root"]."/computers/index.php?add_search_count2=1&amp;type=$type'><img src=\"".$HTMLRel."pics/meta_plus.png\" alt='+' title='".$lang["search"][19]."'></a>&nbsp;&nbsp;&nbsp;&nbsp;";
				if ($_SESSION["glpisearchcount2"][$type]>0)
				echo "<a href='".$cfg_install["root"]."/computers/index.php?delete_search_count2=1&amp;type=$type'><img src=\"".$HTMLRel."pics/meta_moins.png\" alt='-' title='".$lang["search"][20]."'></a>&nbsp;&nbsp;&nbsp;&nbsp;";
			}
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
			echo ">". substr($val["name"],0,20) ."</option>\n";
		}

    	echo "<option value='all' ";
		if(is_array($field)&&isset($field[$i]) && $field[$i] == "all") echo "selected";
		echo ">".$lang["search"][7]."</option>";

		echo "</select>&nbsp;";

		
		echo "</td></tr>";
	}

	$linked=array();
	if ($_SESSION["glpisearchcount2"][$type]>0){
		
		switch ($type){
			case COMPUTER_TYPE :
				$linked=array(PRINTER_TYPE,MONITOR_TYPE,PERIPHERAL_TYPE,SOFTWARE_TYPE);
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
		}
	}

	if (is_array($linked)&&count($linked)>0)
	for ($i=0;$i<$_SESSION["glpisearchcount2"][$type];$i++){
		echo "<tr><td align='right'>";
		$rand=mt_rand();
		
		if ($i>0) {
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
		}
	
		echo "<select name='type2[$i]' id='type2_".$type."_".$i."_$rand'>";
		echo "<option value='-1'>-----</option>";
		foreach ($linked as $key)
			echo "<option value='$key'>".substr($names[$key],0,20)."</option>";
		echo "</select>";

	echo "<script type='text/javascript' >\n";
	echo "   new Form.Element.Observer('type2_".$type."_".$i."_$rand', 1, \n";
	echo "      function(element, value) {\n";
	echo "      	new Ajax.Updater('show_".$type."_".$i."_$rand','".$cfg_install["root"]."/ajax/updateSearch.php',{asynchronous:true, evalScripts:true, \n";	
	echo "           method:'post', parameters:'type='+value+'&num=$i&field=".(is_array($field2)&&isset($field2[$i])?$field2[$i]:"")."&val=".(is_array($contains2)&&isset($contains2[$i])?$contains2[$i]:"")."'\n";
	echo "})})\n";
	echo "</script>\n";
		
	echo "<span id='show_".$type."_".$i."_$rand'>&nbsp;</span>\n";

	if (is_array($type2)&&isset($type2[$i])&&$type2[$i]>0){
		echo "<script type='text/javascript' >\n";
		echo "document.getElementById('type2_".$type."_".$i."_$rand').value='".$type2[$i]."';";
		echo "</script>\n";
	}
		
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
		echo ">".substr($val["name"],0,20)."</option>\n";
	}
	echo "</select> ";
	echo "</td>";
	
	echo "<td>";
//	echo "<table>";
	if (in_array($LINK_ID_TABLE[$type],$deleted_tables)){
		//echo "<tr><td>";
		echo "<select name='deleted'>";
		echo "<option value='Y' ".($deleted=='Y'?" selected ":"").">".$lang["choice"][0]."</option>";
		echo "<option value='N' ".($deleted=='N'?" selected ":"").">".$lang["choice"][1]."</option>";
		echo "</select>";
		echo "<img src=\"".$HTMLRel."pics/showdeleted.png\" alt='".$lang["common"][3]."' title='".$lang["common"][3]."'>";
		//echo "</td></tr>";
	}
	
/*	echo "<tr><td><select name='distinct'>";
	echo "<option value='Y' ".($distinct=='Y'?" selected ":"").">".$lang["choice"][0]."</option>";
	echo "<option value='N' ".($distinct=='N'?" selected ":"").">".$lang["choice"][1]."</option>";
	echo "</select>";
	echo "<img src=\"".$HTMLRel."pics/doublons.png\" alt='".$lang["common"][12]."' title='".$lang["common"][12]."'>";
	echo "</td></tr></table>";
*/
	echo "</td>";
	echo "<td>";
	echo "<a href='".$HTMLRel."/computers/index.php?reset_search=reset_search&amp;type=$type'><img title=\"".$lang["buttons"][16]."\" alt=\"".$lang["buttons"][16]."\" src='".$HTMLRel."pics/reset.png'</a>";
	echo "</td>";
	echo "<td width='80' align='center' class='tab_bg_2'>";
	echo "<input type='submit' value=\"".$lang["buttons"][0]."\" class='submit' >";
	echo "</td></tr></table></div>";
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
function showList ($type,$target,$field,$contains,$sort,$order,$start,$deleted,$link,$distinct,$link2="",$contains2="",$field2="",$type2=""){
	global $INFOFORM_PAGES,$SEARCH_OPTION,$LINK_ID_TABLE,$HTMLRel,$cfg_install,$deleted_tables,$template_tables,$lang,$cfg_features;
	$db=new DB;

	$META_SPECIF_TABLE=array("glpi_device_ram","glpi_device_hdd","glpi_device_processor");
	
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
			array_push($SEARCH_ALL,array("contains"=>$contains[$key]));
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
		$SELECT.=addSelect($type,$SEARCH_OPTION[$type][$toview[$i]]["table"],$SEARCH_OPTION[$type][$toview[$i]]["field"],$i,0);
	}

	// Get specific item
	if ($LINK_ID_TABLE[$type]=="glpi_cartridges_type"||$LINK_ID_TABLE[$type]=="glpi_consumables_type")
		$SELECT.=$LINK_ID_TABLE[$type].".alarm as ALARM, ";

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

	if ($_SESSION["glpisearchcount"][$type]>0&&count($contains)>0) {
		$i=0;

		//foreach($contains as $key => $val)
		for ($key=0;$key<$_SESSION["glpisearchcount"][$type];$key++)
		if (isset($contains[$key])&&strlen($contains[$key])>0&&$field[$key]!="all"&&$field[$key]!="view"){
			$LINK=" ";
			$NOT=0;
			if (!$first||$i>0) {
				if (is_array($link)&&isset($link[$key])&&ereg("NOT",$link[$key])){
				$LINK=" ".ereg_replace(" NOT","",$link[$key]);
				$NOT=1;
				}
				else if (is_array($link)&&isset($link[$key]))
					$LINK=" ".$link[$key];
				else $LINK=" AND ";
			}
			//echo $link[$key].$LINK.$i;
			if (!in_array($SEARCH_OPTION[$type][$field[$key]]["table"],$META_SPECIF_TABLE)){
				$WHERE.= $LINK.addWhere($NOT,$type,$SEARCH_OPTION[$type][$field[$key]]["table"],$SEARCH_OPTION[$type][$field[$key]]["field"],$contains[$key]);
                        	$i++;
			}
		} else if (isset($contains[$key])&&strlen($contains[$key])>0&&$field[$key]=="view"){

			$NOT=0;
			if (!$first||$i>0) {
				if (is_array($link)&&isset($link[$key])&&ereg("NOT",$link[$key])){
					$WHERE.=" ".ereg_replace(" NOT","",$link[$key]);
					$NOT=1;
				} else if (is_array($link)&&isset($link[$key]))
					$WHERE.=" ".$link[$key];
				else 
					$WHERE.=" AND ";
				
			}

			 $WHERE.= " ( ";
			$first2=true;
			foreach ($toview as $key2 => $val2)
	        if (!in_array($SEARCH_OPTION[$type][$val2]["table"],$META_SPECIF_TABLE)){
				$LINK=" OR ";
				if ($first2) {$LINK=" ";$first2=false;}
				$WHERE.= $LINK.addWhere($NOT,$type,$SEARCH_OPTION[$type][$val2]["table"],$SEARCH_OPTION[$type][$val2]["field"],$contains[$key]);
			}
			$WHERE.=" ) ";
			$i++;
		} else if (isset($contains[$key])&&strlen($contains[$key])>0&&$field[$key]=="all"){

			$NOT=0;
			if (!$first||$i>0) {
				if (ereg("NOT",$link[$key])){
				$WHERE.=" ".ereg_replace(" NOT","",$link[$key]);
				$NOT=1;
				}
				else 
				$WHERE.=" ".$link[$key];
			}
			 $WHERE.= " ( ";
			$first2=true;

   		        foreach ($SEARCH_OPTION[$type] as $key2 => $val2)
   		        if (!in_array($val2["table"],$META_SPECIF_TABLE)){
                                $LINK=" OR ";
                                if ($first2) {$LINK=" ";$first2=false;}
                                
                                $WHERE.= $LINK.addWhere($NOT,$type,$val2["table"],$val2["field"],$contains[$key]);
			}

		        $WHERE.=")";
		        $i++;
                } 

	}


	//// 4 - ORDER
	if (!in_array($SEARCH_OPTION[$type][$sort]["table"],$META_SPECIF_TABLE))	
		$ORDER= addOrderBy($SEARCH_OPTION[$type][$sort]["table"].".".$SEARCH_OPTION[$type][$sort]["field"],$order);
	else {
		foreach($toview as $key => $val)
		if ($sort==$val)
			$ORDER= addOrderBy($SEARCH_OPTION[$type][$sort]["table"].".".$SEARCH_OPTION[$type][$sort]["field"],$order,$key);	
	}


	
	//// 5 - META SEARCH
	// Preprocessing

	if ($_SESSION["glpisearchcount2"][$type]>0&&is_array($type2)){
		
		// a - SELECT 
		for ($i=0;$i<$_SESSION["glpisearchcount2"][$type];$i++)
		if (isset($type2[$i])&&$type2[$i]>0)	{
			$SELECT.=addSelect($type2[$i],$SEARCH_OPTION[$type2[$i]][$field2[$i]]["table"],$SEARCH_OPTION[$type2[$i]][$field2[$i]]["field"],$i,1);		
		}

		// b - ADD LEFT JOIN 
		$already_link_tables2=array();
		for ($i=0;$i<$_SESSION["glpisearchcount2"][$type];$i++)
		if (isset($type2[$i])&&$type2[$i]>0) {
			if (!in_array($LINK_ID_TABLE[$type2[$i]],$already_link_tables2))
				$FROM.=addMetaLeftJoin($type,$type2[$i],$already_link_tables2,$i);	
		}

		for ($i=0;$i<$_SESSION["glpisearchcount2"][$type];$i++)
		if (isset($type2[$i])&&$type2[$i]>0) {
			if (!in_array($SEARCH_OPTION[$type2[$i]][$field2[$i]]["table"],$already_link_tables2)){
				$FROM.=addLeftJoin($type2[$i],$LINK_ID_TABLE[$type2[$i]],$already_link_tables2,$SEARCH_OPTION[$type2[$i]][$field2[$i]]["table"],0,1,$i);				
			}
			
		}
	
	}
	
	
	//// 6 - Add item ID
	
	// Add ID
	$SELECT.=$LINK_ID_TABLE[$type].".ID AS ID ";

	//// 7 - Manage GROUP BY
	$GROUPBY="";
	if ($_SESSION["glpisearchcount2"][$type]>0)	
		$GROUPBY=" GROUP BY ID";

	if (empty($GROUPBY))
	foreach ($toview as $key2 => $val2)
	if (empty($GROUPBY)&&(($val2=="all")
		||($type==COMPUTER_TYPE&&ereg("glpi_device",$SEARCH_OPTION[$type][$val2]["table"]))
		||(ereg("glpi_contracts",$SEARCH_OPTION[$type][$val2]["table"]))
	)) 
		$GROUPBY=" GROUP BY ID ";
	

	// For computer search
	if ($type==COMPUTER_TYPE){
	
		foreach($contains as $key => $val)
		if (strlen($val)>0){

		if ($field[$key]!="all"&&$field[$key]!="view"){
			foreach ($toview as $key2 => $val2){
				//echo $val2."-".$field[$key]."-".$SEARCH_OPTION[$type][$val2]["table"]."<br>";
				 if (($val2==$field[$key])&&in_array($SEARCH_OPTION[$type][$val2]["table"],$META_SPECIF_TABLE)){
				if (!isset($link[$key])) $link[$key]="AND";
				//echo "tttt";
				$GROUPBY=addGroupByHaving($GROUPBY,$SEARCH_OPTION[$type][$field[$key]]["table"].".".$SEARCH_OPTION[$type][$field[$key]]["field"],strtolower($contains[$key]),$key2,0,$link[$key]);
				}
			}
		}
		}
	} 

	 // For others item linked 
		if (is_array($type2))
		for ($key=0;$key<$_SESSION["glpisearchcount2"][$type];$key++)
		if (isset($type2[$key])&&strlen($contains2[$key]))
		{
			$LINK="";
			if (isset($link2[$key])) $LINK=$link2[$key];
			
			$GROUPBY=addGroupByHaving($GROUPBY,$SEARCH_OPTION[$type2[$key]][$field2[$key]]["table"].".".$SEARCH_OPTION[$type2[$key]][$field2[$key]]["field"],strtolower($contains2[$key]),$key,1,$LINK);
		}
	// If no research limit research
	$nosearch=true;
	for ($i=0;$i<$_SESSION["glpisearchcount"][$type];$i++)
	if (isset($contains[$i])&&strlen($contains[$i])>0) $nosearch=false;
	
	if ($_SESSION["glpisearchcount2"][$type]>0)	
		$nosearch=false;

	$LIMIT="";
	$numrows=0;
	if ($nosearch) {
		$LIMIT= " LIMIT $start, ".$cfg_features["list_limit"];
		$query_num="SELECT count(ID) FROM ".$LINK_ID_TABLE[$type];
	
		$first=true;
		if (in_array($LINK_ID_TABLE[$type],$deleted_tables)){
			$LINK= " AND " ;
			if ($first) {$LINK=" WHERE ";$first=false;}
			$query_num.= $LINK.$LINK_ID_TABLE[$type].".deleted='$deleted' ";
		}
		if (in_array($LINK_ID_TABLE[$type],$template_tables)){
			$LINK= " AND " ;
			if ($first) {$LINK=" WHERE ";$first=false;}
			$query_num.= $LINK.$LINK_ID_TABLE[$type].".is_template='0' ";
		}
		$result_num = $db->query($query_num);
		$numrows= $db->result($result_num,0,0);
	}

	if ($WHERE == " WHERE ") $WHERE="";

	$QUERY=$SELECT.$FROM.$WHERE.$GROUPBY.$ORDER.$LIMIT;

	//echo $QUERY;

	if (isset($_GET["display_type"]))
		$output_type=$_GET["display_type"];
	else 
		$output_type=0;

	// Get it from database and DISPLAY
	if ($result = $db->query($QUERY)) {
		if (!$nosearch) 
			$numrows= $db->numrows($result);
		if ($start<$numrows) {

			// Pager
			$parameters="sort=$sort&amp;order=$order".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains).getMultiSearchItemForLink("field2",$field2).getMultiSearchItemForLink("contains2",$contains2).getMultiSearchItemForLink("type2",$type2).getMultiSearchItemForLink("link2",$link2);

			if ($output_type==0) // In case of HTML display
				printPager($start,$numrows,$target,$parameters,$type);
			
			$nbcols=$toview_count;
			// META HEADER
			if ($_SESSION["glpisearchcount2"][$type]>0&&is_array($type2))
			for ($i=0;$i<$_SESSION["glpisearchcount2"][$type];$i++)
			if (isset($type2[$i])&&strlen($contains2[$i])>0&&$type2[$i]>0&&(!isset($link2[$i])||!ereg("NOT",$link2[$i]))) {
				$nbcols++;
			}


			echo displaySearchHeader($output_type,$cfg_features["list_limit"]+1,$nbcols);

			echo displaySearchNewLine($output_type);
			$header_num=1;
			// TABLE HEADER
			for ($i=0;$i<$toview_count;$i++){

				$linkto="$target?sort=".$toview[$i]."&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains).getMultiSearchItemForLink("field2",$field2).getMultiSearchItemForLink("contains2",$contains2).getMultiSearchItemForLink("type2",$type2).getMultiSearchItemForLink("link2",$link2);

				echo displaySearchHeaderItem($output_type,$SEARCH_OPTION[$type][$toview[$i]]["name"],$header_num,$linkto,$sort==$toview[$i],$order);
			}
			// META HEADER
			if ($_SESSION["glpisearchcount2"][$type]>0&&is_array($type2))
			for ($i=0;$i<$_SESSION["glpisearchcount2"][$type];$i++)
			if (isset($type2[$i])&&strlen($contains2[$i])>0&&$type2[$i]>0&&(!isset($link2[$i])||!ereg("NOT",$link2[$i]))) {
				echo displaySearchHeaderItem($output_type,$SEARCH_OPTION[$type2[$i]][$field2[$i]]["name"],$header_num);
			}
			
			if ($output_type==0){ // Only in HTML
				if ($type==SOFTWARE_TYPE)
					echo displaySearchHeaderItem($output_type,$lang["software"][11]);
					
				if ($type==CARTRIDGE_TYPE)
					echo displaySearchHeaderItem($output_type,$lang["cartridges"][0]);	
				
				if ($type==CONSUMABLE_TYPE)
					echo displaySearchHeaderItem($output_type,$lang["consumables"][0]);
			}
					
			echo displaySearchEndLine($output_type);
			
			if (!$nosearch)
				$db->data_seek($result,$start);

			$i=$start;
			$end_display=$start+$cfg_features["list_limit"];

			if ($nosearch){
				$i=0;
				$end_display=$cfg_features["list_limit"];
				}
			

			$row_num=1;
			while ($i < $numrows && $i<($end_display)){
				$item_num=1;
				$data=$db->fetch_assoc($result);
				$i++;
				$row_num++;
				echo displaySearchNewLine($output_type);
				
				// Print first element
				if ($SEARCH_OPTION[$type][1]["table"].".".$SEARCH_OPTION[$type][1]["field"]=="glpi_users.name")
					echo displaySearchItem($output_type,giveItem($type,"glpi_users.name.brut",$data,0),$item_num,$row_num);
				else 
					echo displaySearchItem($output_type,giveItem($type,$SEARCH_OPTION[$type][1]["table"].".".$SEARCH_OPTION[$type][1]["field"],$data,0),$item_num,$row_num);
				// Print other items
				for ($j=1;$j<$toview_count;$j++){
					if ($SEARCH_OPTION[$type][$toview[$j]]["table"].".".$SEARCH_OPTION[$type][$toview[$j]]["field"]=="glpi_enterprises.name")
						echo displaySearchItem($output_type,giveItem($type,"glpi_enterprises.name.brut",$data,$j),$item_num,$row_num);
					else if ($SEARCH_OPTION[$type][$toview[$j]]["table"].".".$SEARCH_OPTION[$type][$toview[$j]]["field"]=="glpi_contracts.name")
						echo displaySearchItem($output_type,giveItem($type,"glpi_contracts.name.brut",$data,$j),$item_num,$row_num);
					else  
						echo displaySearchItem($output_type,giveItem($type,$SEARCH_OPTION[$type][$toview[$j]]["table"].".".$SEARCH_OPTION[$type][$toview[$j]]["field"],$data,$j),$item_num,$row_num);

				}
				
				// Print Meta Item
				if ($_SESSION["glpisearchcount2"][$type]>0&&is_array($type2))
				for ($j=0;$j<$_SESSION["glpisearchcount2"][$type];$j++)
				if (isset($type2[$j])&&strlen($contains2[$j])>0&&$type2[$j]>0&&(!isset($link2[$j])||!ereg("NOT",$link2[$j]))){
					if (!ereg("$$$$",$data["META_$j"]))
						echo displaySearchItem($output_type,$data["META_$j"],$item_num,$row_num);
					else {
						$split=explode("$$$$",$data["META_$j"]);
						$count_display=0;
						$out="";
						for ($k=0;$k<count($split);$k++)
						if (strlen($contains2[$j])==0||eregi($contains2[$j],$split[$k])){
						
							if ($count_display) $out.= "<br>";
							$count_display++;
							$out.= $split[$k];
						}
						echo displaySearchItem($output_type,$out,$item_num,$row_num);
					
					}
				}
				
				if ($output_type==0){ // Only in HTML
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
				}
		   		
		        echo displaySearchEndLine($output_type);
			}
			
			echo displaySearchFooter($output_type);
			
			if ($output_type==0) // In case of HTML display
				printPager($start,$numrows,$target,$parameters,$type);

		} else {
			echo displaySearchError($output_type);
			
		}
	}
	else echo $db->error();

}


function displaySearchHeaderItem($type,$value,&$num,$linkto="",$issort=0,$order=""){
	global $HTMLRel;
	$out="";
	switch ($type){
		case 1 : //sylk
			$out="F;SDM4;FG0C;".($num == 1 ? "Y1;" : "")."X$num\n";
			$out.= "C;N;K\"".sylk_clean($value)."\"\n"; 
			break;
		default :

		$out="<th>";
		if ($issort) {
			if ($order=="DESC") $out.="<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
			else $out.="<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
		}
		
		if (!empty($linkto))
			$out.= "<a href=\"$linkto\">";
		
		$out.= $value;
			
		if (!empty($linkto))
			$out.="</a>";
		
		$out.="</th>\n";
		break;
	}
$num++;
return $out;

}


function displaySearchItem($type,$value,&$num,$row){
	$out="";
	switch ($type){
		case 1 : //sylk
			$out="F;P3;FG0L;".($num == 1 ? "Y".$row.";" : "")."X$num\n";
			$out.= "C;N;K\"".sylk_clean($value)."\"\n"; 
			break;
		default :
			$out="<td>".$value."</td>\n";
			break;
	}
$num++;
return $out;

}

function displaySearchError($type){
	$out="";
	switch ($type){
		case 1 : //sylk
			break;
		default :
			$out= "<div align='center'><b>".$lang["search"][15]."</b></div>\n";
		break;
	}
return $out;

}

function displaySearchFooter($type){
	$out="";
	switch ($type){
		case 1 : //sylk
			break;
		default :
			$out= "</table></div><br>\n";
		break;
	}
return $out;

}

function displaySearchHeader($type,$rows,$cols){
	$out="";
	switch ($type){
		case 1 : // Sylk
			define("FORMAT_REEL",   1); // #,##0.00
			define("FORMAT_ENTIER", 2); // #,##0
			define("FORMAT_TEXTE",  3); // @

			$cfg_formats[FORMAT_ENTIER] = "FF0";
			$cfg_formats[FORMAT_REEL]   = "FF2";
			$cfg_formats[FORMAT_TEXTE]  = "FG0";
			
			// en-tête HTTP
        		// --------------------------------------------------------------------
        		header("Content-disposition: filename=glpi.slk");
        		header('Content-type: application/octetstream');
        		header('Pragma: no-cache');
        		header('Expires: 0');


        		// en-tête du fichier
        		// --------------------------------------------------------------------
        		echo "ID;PGLPI EXPORT\n"; // ID;Pappli
        		echo "\n";
        		// formats
        		echo "P;PGeneral\n";      
        		echo "P;P#,##0.00\n";       // P;Pformat_1 (reels)
        		echo "P;P#,##0\n";          // P;Pformat_2 (entiers)
        		echo "P;P@\n";              // P;Pformat_3 (textes)
        		echo "\n";
        		// polices
        		echo "P;EArial;M200\n";
        		echo "P;EArial;M200\n";
        		echo "P;EArial;M200\n";
        		echo "P;FArial;M200;SB\n";
        		echo "\n";
        		// nb lignes * nb colonnes
        		echo "B;Y".$rows;
        		echo ";X".$cols."\n"; // B;Yligmax;Xcolmax
        		echo "\n";

			// largeurs des colonnes
			for ($i=1;$i<=$cols;$i++)
				echo "F;W".$i." ".$i." 20\n";
			break;

		default :
			$out="<div align='center'><table border='0' class='tab_cadrehov'>\n";
			break;
	}
return $out;

}


function displaySearchNewLine($type){
	$out="";
	switch ($type){
		case 1 : //sylk
			$out="\n";
			break;

		default :
		$out="<tr class='tab_bg_2'>";
		break;
	}
return $out;
}

function displaySearchEndLine($type){
	$out="";
	switch ($type){
		case 1 : //sylk
			break;

		default :
			$out="</tr>";
			break;
	}
return $out;
}

/**
* Generic Function to add GROUP BY to a request
*
*
*@param $field field to add
*@param $order order define
*
*
*@return select string
*
**/

function addGroupByHaving($GROUPBY,$field,$val,$num,$meta=0,$link=""){

$NOT="";
if (ereg("NOT",$link)){
 $NOT=" NOT";
 $link=ereg_replace(" NOT","",$link);
}

if (empty($link)) $link="AND";

$NAME="ITEM_";
if ($meta) $NAME="META_";

if (!ereg("GROUP BY ID",$GROUPBY)) $GROUPBY=" GROUP BY ID ";


if (!ereg("$NAME$num",$GROUPBY))
switch ($field){

case "glpi_device_ram.specif_default" :
	$larg=100;
		
	if (ereg("HAVING",$GROUPBY)) $GROUPBY.=" ".$link." ";
	else $GROUPBY.=" HAVING ";

	if (empty($NOT))
		$GROUPBY.=" ( $NAME$num < ".($val+$larg)." AND $NAME$num > ".($val-$larg)." ) ";
	else 
		$GROUPBY.=" ( $NAME$num > ".($val+$larg)." OR $NAME$num < ".($val-$larg)." ) ";
	break;
case "glpi_device_processor.specif_default" :
	$larg=100;
		
	if (ereg("HAVING",$GROUPBY)) $GROUPBY.=" ".$link." ";
	else $GROUPBY.=" HAVING ";

	if (empty($NOT))
		$GROUPBY.=" ( $NAME$num < ".($val+$larg)." AND $NAME$num > ".($val-$larg)." ) ";
	else 
		$GROUPBY.=" ( $NAME$num > ".($val+$larg)." OR $NAME$num < ".($val-$larg)." ) ";
	break;
case "glpi_device_hdd.specif_default" :
	$larg=1000;

	if (ereg("HAVING",$GROUPBY)) $GROUPBY.=" ".$link." ";
	else $GROUPBY.=" HAVING ";
	if (empty($NOT))
		$GROUPBY.=" ( $NAME$num < ".($val+$larg)." AND $NAME$num > ".($val-$larg)." ) ";
	else 
		$GROUPBY.=" ( $NAME$num > ".($val+$larg)." OR $NAME$num < ".($val-$larg)." ) ";
	break;
default :
	if (ereg("HAVING",$GROUPBY)) $GROUPBY.=" ".$link." ";
	else $GROUPBY.=" HAVING ";

	$GROUPBY.=" ( $NAME$num $NOT LIKE '%$val%' ) ";

	break;
}

return $GROUPBY;

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
function addOrderBy($field,$order,$key=0){
	switch($field){
	case "glpi_device_hdd.specif_default" :
	case "glpi_device_ram.specif_default" :
	case "glpi_device_processor.specif_default" :
		return " ORDER BY ITEM_$key $order ";
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
function addSelect ($type,$table,$field,$num,$meta=0){
global $LINK_ID_TABLE;

$addtable="";
$pretable="";
$NAME="ITEM";
if ($meta) {
	$addtable="_".$num;
	$pretable="META_";
	$NAME="META";
}

switch ($table.".".$field){
case "glpi_enterprises.name" :
	return $pretable.$table.$addtable.".".$field." AS ".$NAME."_$num, ".$pretable.$table.$addtable.".website AS ".$NAME."_".$num."_2, ".$pretable.$table.$addtable.".ID AS ".$NAME."_".$num."_3, ";
	break;
case "glpi_users.name" :
	return $pretable.$table.$addtable.".".$field." AS ITEM_$num, glpi_users.realname AS ".$NAME."_".$num."_2, ";
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
		return $pretable.$table.$addtable.".".$field." AS ITEM_$num, DEVICE_".NETWORK_DEVICE.".specificity AS ".$NAME."_".$num."_2, ";
	else return $pretable.$table.$addtable.".".$field." AS ".$NAME."_$num, ";
	break;
default:
	if ($meta){
		
		if ($table!=$LINK_ID_TABLE[$type])
			return " GROUP_CONCAT( DISTINCT LCASE(".$pretable.$table.$addtable.".".$field.") SEPARATOR '$$$$') AS META_$num, ";
		else return " GROUP_CONCAT( DISTINCT LCASE(".$table.$addtable.".".$field.") SEPARATOR '$$$$') AS META_$num, ";

	}
	else 
		return $table.$addtable.".".$field." AS ITEM_$num, ";
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
function addWhere ($nott,$type,$table,$field,$val,$device_type=0,$meta=0,$meta_num=0){
global $LINK_ID_TABLE;

$NOT="";
if ($nott) $NOT=" NOT";
//echo $table.".".$field."-".$NOT;
switch ($table.".".$field){
case "glpi_users.name" :
	return " ( $table.$field $NOT LIKE '%".$val."%' AND glpi_users.realname $NOT LIKE '%".$val."%' ) ";
	break;
case "glpi_device_hdd.specif_default" :
//	$larg=500;
//	return " ( DEVICE_".HDD_DEVICE.".specificity < ".($val+$larg)." AND DEVICE_".HDD_DEVICE.".specificity > ".($val-$larg)." ) ";
	return " $table.$field $NOT LIKE '%%' ";
	break;
case "glpi_device_ram.specif_default" :
//	$larg=50;
//	return " ( DEVICE_".RAM_DEVICE.".specificity < ".($val+$larg)." AND DEVICE_".RAM_DEVICE.".specificity > ".($val-$larg)." ) ";
	return " $table.$field $NOT LIKE '%%' ";
	break;
case "glpi_device_processor.specif_default" :
//	$larg=50;
//	return " ( DEVICE_".RAM_DEVICE.".specificity < ".($val+$larg)." AND DEVICE_".RAM_DEVICE.".specificity > ".($val-$larg)." ) ";
	return " $table.$field $NOT LIKE '%%' ";
	break;

case "glpi_networking_ports.ifmac" :
	if ($type==COMPUTER_TYPE)
		return " (  DEVICE_".NETWORK_DEVICE.".specificity $NOT LIKE '%".$val."%' AND $table.$field $NOT LIKE '%".$val."%' ) ";
	else return " $table.$field $NOT LIKE '%".$val."%' ";
	break;
default:
	if ($meta)
		if ($table!=$LINK_ID_TABLE[$type])
			return " META_".$table."_".$meta_num.".$field $NOT LIKE '%".$val."%' ";
		else return " ".$table."_".$meta_num.".$field $NOT LIKE '%".$val."%' ";
	else 
	$ADD="";	
	if ($nott) $ADD=" OR $table.$field IS NULL";
	return " ($table.$field $NOT LIKE '%".$val."%' ".$ADD." ) ";
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
*
*
*@return string to print
*
**/
function giveItem ($type,$field,$data,$num){
global $cfg_install,$INFOFORM_PAGES,$HTMLRel,$cfg_layout;

switch ($field){
	case "glpi_users.name" :
		// print realname or login name
		if (!empty($data["ITEM_".$num."_2"]))
			return $data["ITEM_".$num."_2"];
		else return $data["ITEM_$num"];
		break;
	case "glpi_users.name.brut" :		
		$type=USER_TYPE;
		$out= "<a href=\"".$cfg_install["root"]."/".$INFOFORM_PAGES[$type]."?ID=".$data['ID']."\">";
		$out.= $data["ITEM_$num"];
		if ($cfg_layout["view_ID"]||empty($data["ITEM_$num"])) $out.= " (".$data["ID"].")";
		$out.= "</a>";
		return $out;
		break;
	case "glpi_computers.name" :
		$type=COMPUTER_TYPE;
		$out= "<a href=\"".$cfg_install["root"]."/".$INFOFORM_PAGES[$type]."?ID=".$data['ID']."\">";
		$out.= $data["ITEM_$num"];
		if ($cfg_layout["view_ID"]||empty($data["ITEM_$num"])) $out.= " (".$data["ID"].")";
		$out.= "</a>";
		return $out;
		break;
	case "glpi_printers.name" :
		$type=PRINTER_TYPE;
		$out= "<a href=\"".$cfg_install["root"]."/".$INFOFORM_PAGES[$type]."?ID=".$data['ID']."\">";
		$out.= $data["ITEM_$num"];
		if ($cfg_layout["view_ID"]||empty($data["ITEM_$num"])) $out.= " (".$data["ID"].")";
		$out.= "</a>";
		return $out;
		break;
	case "glpi_networking.name" :
		$type=NETWORKING_TYPE;
		$out= "<a href=\"".$cfg_install["root"]."/".$INFOFORM_PAGES[$type]."?ID=".$data['ID']."\">";
		$out.= $data["ITEM_$num"];
		if ($cfg_layout["view_ID"]||empty($data["ITEM_$num"])) $out.= " (".$data["ID"].")";
		$out.= "</a>";
		return $out;
		break;
	case "glpi_monitors.name" :
		$type=MONITOR_TYPE;
		$out= "<a href=\"".$cfg_install["root"]."/".$INFOFORM_PAGES[$type]."?ID=".$data['ID']."\">";
		$out.= $data["ITEM_$num"];
		if ($cfg_layout["view_ID"]||empty($data["ITEM_$num"])) $out.= " (".$data["ID"].")";
		$out.= "</a>";
		return $out;
		break;
	case "glpi_software.name" :
		$type=SOFTWARE_TYPE;
		$out= "<a href=\"".$cfg_install["root"]."/".$INFOFORM_PAGES[$type]."?ID=".$data['ID']."\">";
		$out.= $data["ITEM_$num"];
		if ($cfg_layout["view_ID"]||empty($data["ITEM_$num"])) $out.= " (".$data["ID"].")";
		$out.= "</a>";
 		return $out;
		break;
	case "glpi_peripherals.name" :
		$type=PERIPHERAL_TYPE;
		$out= "<a href=\"".$cfg_install["root"]."/".$INFOFORM_PAGES[$type]."?ID=".$data['ID']."\">";
		$out.= $data["ITEM_$num"];
		if ($cfg_layout["view_ID"]||empty($data["ITEM_$num"])) $out.= " (".$data["ID"].")";
		$out.= "</a>";
		return $out;
		break;	
	case "glpi_cartridges_type.name" :
		$type=CARTRIDGE_TYPE;
		$out= "<a href=\"".$cfg_install["root"]."/".$INFOFORM_PAGES[$type]."?ID=".$data['ID']."\">";
		$out.= $data["ITEM_$num"];
		if ($cfg_layout["view_ID"]||empty($data["ITEM_$num"])) $out.= " (".$data["ID"].")";
		$out.= "</a>";
		return $out;
		break;
	case "glpi_consumables_type.name" :
		$type=CONSUMABLE_TYPE;
		$out= "<a href=\"".$cfg_install["root"]."/".$INFOFORM_PAGES[$type]."?ID=".$data['ID']."\">";
		$out.= $data["ITEM_$num"];
		if ($cfg_layout["view_ID"]||empty($data["ITEM_$num"])) $out.= " (".$data["ID"].")";
		$out.= "</a>";
		return $out;
		break;	
	case "glpi_contacts.name" :
		$type=CONTACT_TYPE;
		$out= "<a href=\"".$cfg_install["root"]."/".$INFOFORM_PAGES[$type]."?ID=".$data['ID']."\">";
		$out.= $data["ITEM_$num"];
		if ($cfg_layout["view_ID"]||empty($data["ITEM_$num"])) $out.= " (".$data["ID"].")";
		$out.= "</a>";
		return $out;
		break;	
	case "glpi_contracts.name" :
		$type=CONTRACT_TYPE;
		$out= "<a href=\"".$cfg_install["root"]."/".$INFOFORM_PAGES[$type]."?ID=".$data['ID']."\">";
		$out.= $data["ITEM_$num"];
		if ($cfg_layout["view_ID"]||empty($data["ITEM_$num"])) $out.= " (".$data["ID"].")";
		$out.= "</a>";
		return $out;
		break;	
	case "glpi_contracts.name.brut" :
		$type=CONTRACT_TYPE;
		$out= $data["ITEM_$num"];
		if ($cfg_layout["view_ID"]||empty($data["ITEM_$num"])) $out.= " (".$data["ID"].")";
		return $out;
		break;			

	case "glpi_enterprises.name" :
		$type=ENTERPRISE_TYPE;
		$out= "<a href=\"".$cfg_install["root"]."/".$INFOFORM_PAGES[$type]."?ID=".$data['ID']."\">";
		$out.= $data["ITEM_$num"];
		if ($cfg_layout["view_ID"]||empty($data["ITEM_$num"])) $out.= " (".$data["ID"].")";
		$out.= "</a>";
		if (!empty($data["ITEM_".$num."_2"]))
			$out.= "<a href='".$data["ITEM_".$num."_2"]."' target='_blank'><img src='".$HTMLRel."/pics/web.png' alt='website'></a>";
		return $out;
		break;	
	case "glpi_enterprises.name.brut" :
		$type=ENTERPRISE_TYPE;
		$out= "<a href=\"".$cfg_install["root"]."/".$INFOFORM_PAGES[$type]."?ID=".$data["ITEM_".$num."_3"]."\">";
		$out.= $data["ITEM_$num"];
		if ($cfg_layout["view_ID"]||(empty($data["ITEM_$num"])&&!empty($data["ITEM_".$num."_3"]))) $out.= " (".$data["ITEM_".$num."_3"].")";
		$out.= "</a>";
		if (!empty($data["ITEM_".$num."_2"]))
			$out.= "<a href='".$data["ITEM_".$num."_2"]."' target='_blank'><img src='".$HTMLRel."/pics/web.png' alt='website'></a>";
		return $out;
		break;			
	case "glpi_docs.name" :		
		$type=DOCUMENT_TYPE;
		$out= "<a href=\"".$cfg_install["root"]."/".$INFOFORM_PAGES[$type]."?ID=".$data['ID']."\">";
		$out.= $data["ITEM_$num"];
		if ($cfg_layout["view_ID"]||empty($data["ITEM_$num"])) $out.= " (".$data["ID"].")";
		$out.= "</a>";
		return $out;
		break;
	case "glpi_type_docs.name" :		
		$type=TYPEDOC_TYPE;
		$out= "<a href=\"".$cfg_install["root"]."/".$INFOFORM_PAGES[$type]."?ID=".$data['ID']."\">";
		$out.= $data["ITEM_$num"];
		if ($cfg_layout["view_ID"]||empty($data["ITEM_$num"])) $out.= " (".$data["ID"].")";
		$out.= "</a>";
		return $out;
		break;
	case "glpi_links.name" :		
		$type=LINK_TYPE;
		$out= "<a href=\"".$cfg_install["root"]."/".$INFOFORM_PAGES[$type]."?ID=".$data['ID']."\">";
		$out.= $data["ITEM_$num"];
		if ($cfg_layout["view_ID"]||empty($data["ITEM_$num"])) $out.= " (".$data["ID"].")";
		$out.= "</a>";
		return $out;
		break;		
	case "glpi_type_docs.icon" :
		if (!empty($data["ITEM_$num"]))
			return "<img style='vertical-align:middle;' alt='' src='".$HTMLRel.$cfg_install["typedoc_icon_dir"]."/".$data["ITEM_$num"]."'>";
		else return "&nbsp;";
	break;	

	case "glpi_docs.filename" :		
		return getDocumentLink($data["ITEM_$num"]);
	break;		
	case "glpi_docs.link" :
	case "glpi_enterprises.website" :
		if (!empty($data["ITEM_$num"]))
			return "<a href=\"".$data["ITEM_$num"]."\" target='_blank'>".$data["ITEM_$num"]."</a>";
		else return "&nbsp;";
	break;	
	case "glpi_enterprises.email" :
	case "glpi_contacts.email" :
	case "glpi_users.email" :
		if (!empty($data["ITEM_$num"]))
			return "<a href='mailto:".$data["ITEM_$num"]."'>".$data["ITEM_$num"]."</a>";
		else return "&nbsp;";
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
				$out.= "hw=".$data["ITEM_".$num."_2"];
				if (!empty($data["ITEM_".$num])) $out.= " - ";
			}
		
			if (!empty($data["ITEM_".$num]))
				$out.= "port=".$data["ITEM_".$num];
		} else $out.= $data["ITEM_$num"];
		return $out;
	break;
	case "glpi_computers.date_mod":
	case "glpi_printers.date_mod":
	case "glpi_networking.date_mod":
	case "glpi_peripherals.date_mod":
	case "glpi_software.date_mod":
	case "glpi_monitors.date_mod":
		return convDateTime($data["ITEM_$num"]);
		break;
	case "glpi_contracts.begin_date":
		return convDate($data["ITEM_$num"]);
		break;
	default:
		return $data["ITEM_$num"];
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
function addLeftJoin ($type,$ref_table,&$already_link_tables,$new_table,$device_type=0,$meta=0,$meta_num=0){


if (in_array(translate_table($new_table,$device_type),$already_link_tables)) return "";
else array_push($already_link_tables,translate_table($new_table,$device_type));

// Rename table for meta left join
$AS="";
$nt=$new_table;
$addmetanum="";
$rt=$ref_table;
if ($meta) {
	$AS= " AS META_".$new_table."_".$meta_num;
	$nt="META_".$new_table."_".$meta_num;
	$rt.="_".$meta_num;
}


switch ($new_table){
	case "glpi_dropdown_locations":
		return " LEFT JOIN $new_table $AS ON ($rt.location = $nt.ID) ";
		break;
	case "glpi_dropdown_contract_type":
		return " LEFT JOIN $new_table $AS ON ($rt.contract_type = $nt.ID) ";
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
		return " LEFT JOIN $new_table $AS ON ($rt.type = $nt.ID) ";
		break;
	case "glpi_dropdown_model":
	case "glpi_dropdown_model_printers":
	case "glpi_dropdown_model_monitors":
	case "glpi_dropdown_model_peripherals":
	case "glpi_dropdown_model_networking":
		return " LEFT JOIN $new_table $AS ON ($rt.model = $nt.ID) ";
		break;
	case "glpi_dropdown_os":
	if ($type==SOFTWARE_TYPE)
		return " LEFT JOIN $new_table $AS ON ($rt.platform = $nt.ID) ";
	else 
		return " LEFT JOIN $new_table $AS ON ($rt.os = $nt.ID) ";
		break;
	case "glpi_networking_ports":
		$out="";
		// Add networking device for computers
		if ($type==COMPUTER_TYPE)
			$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_computer_device",NETWORK_DEVICE,$meta,$meta_num);
		
		return $out." LEFT JOIN $new_table $AS ON ($rt.ID = $nt.on_device AND $nt.device_type='$type') ";
		break;
	case "glpi_dropdown_netpoint":
		// Link to glpi_networking_ports before
		$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_networking_ports");
		
		return $out." LEFT JOIN $new_table $AS ON (glpi_networking_ports.netpoint = $nt.ID) ";
		break;
	case "glpi_users":
		return " LEFT JOIN $new_table $AS ON ($rt.tech_num = $nt.ID) ";
		break;
	case "glpi_enterprises":
		return " LEFT JOIN $new_table $AS ON ($rt.FK_glpi_enterprise = $nt.ID) ";
		break;
	case "glpi_infocoms":
		return " LEFT JOIN $new_table $AS ON ($rt.ID = $nt.FK_device AND $nt.device_type='$type') ";
		break;
	case "glpi_contract_device":
		return " LEFT JOIN $new_table $AS ON ($rt.ID = $nt.FK_device AND $nt.device_type='$type') ";
		break;
	case "glpi_state_item":
		return " LEFT JOIN $new_table $AS ON ($rt.ID = $nt.id_device AND $nt.device_type='$type') ";
		break;
	case "glpi_dropdown_state":
		// Link to glpi_state_item before
		$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_state_item");
		
		return $out." LEFT JOIN $new_table $AS ON (glpi_state_item.state = $nt.ID) ";
		break;
	
	case "glpi_contracts":
		// Link to glpi_networking_ports before
		$out=addLeftJoin($type,$rt,$already_link_tables,"glpi_contract_device");
		
		return $out." LEFT JOIN $new_table $AS ON (glpi_contract_device.FK_contract = $nt.ID) ";
		break;
	case "glpi_dropdown_network":
		return " LEFT JOIN $new_table $AS ON ($rt.network = $nt.ID) ";
		break;			
	case "glpi_dropdown_domain":
		return " LEFT JOIN $new_table $AS ON ($rt.domain = $nt.ID) ";
		break;			
	case "glpi_dropdown_firmware":
		return " LEFT JOIN $new_table $AS ON ($rt.firmware = $nt.ID) ";
		break;			
	case "glpi_dropdown_rubdocs":
		return " LEFT JOIN $new_table $AS ON ($rt.rubrique = $nt.ID) ";
		break;
	case "glpi_licenses":
		return " LEFT JOIN $new_table $AS ON ($rt.ID = $nt.sID) ";
		break;	
	case "glpi_computer_device":
		if ($device_type==0)
			return " LEFT JOIN $new_table $AS ON ($rt.ID = $nt.FK_computers ) ";
		else return " LEFT JOIN $new_table AS DEVICE_".$device_type." ON ($rt.ID = DEVICE_".$device_type.".FK_computers AND DEVICE_".$device_type.".device_type='$device_type') ";
		break;	
	case "glpi_device_processor":

		// Link to glpi_networking_ports before
		$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_computer_device",PROCESSOR_DEVICE,$meta,$meta_num);
		
		return $out." LEFT JOIN $new_table $AS ON (DEVICE_".PROCESSOR_DEVICE.".FK_device = $nt.ID) ";
		break;		
	case "glpi_device_ram":
		$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_computer_device",RAM_DEVICE,$meta,$meta_num);
		
		return $out." LEFT JOIN $new_table $AS ON (DEVICE_".RAM_DEVICE.".FK_device = $nt.ID) ";
		break;		
	case "glpi_device_iface":
		// Link to glpi_networking_ports before
		$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_computer_device",NETWORK_DEVICE,$meta,$meta_num);
		
		return $out." LEFT JOIN $new_table $AS ON (DEVICE_".NETWORK_DEVICE.".FK_device = $nt.ID) ";
		break;	
	case "glpi_device_sndcard":
		// Link to glpi_networking_ports before
		$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_computer_device",SND_DEVICE,$meta,$meta_num);
		
		return $out." LEFT JOIN $new_table $AS ON (DEVICE_".SND_DEVICE.".FK_device = $nt.ID) ";
		break;		
	case "glpi_device_gfxcard":
		// Link to glpi_networking_ports before
		$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_computer_device",GFX_DEVICE,$meta,$meta_num);
		
		return $out." LEFT JOIN $new_table $AS ON (DEVICE_".GFX_DEVICE.".FK_device = $nt.ID) ";
		break;	
	case "glpi_device_moboard":
		// Link to glpi_networking_ports before
		$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_computer_device",MOBOARD_DEVICE,$meta,$meta_num);
		
		return $out." LEFT JOIN $new_table $AS ON (DEVICE_".MOBOARD_DEVICE.".FK_device = $nt.ID) ";
		break;	
	case "glpi_device_hdd":
		// Link to glpi_networking_ports before
		$out=addLeftJoin($type,$ref_table,$already_link_tables,"glpi_computer_device",HDD_DEVICE,$meta,$meta_num);
		
		return $out." LEFT JOIN $new_table $AS ON (DEVICE_".HDD_DEVICE.".FK_device = $nt.ID) ";
		break;

	default :
		return "";
		break;
}
}

function addMetaLeftJoin($from_type,$to_type,&$already_link_tables2,$num){
global $LINK_ID_TABLE;
	
	switch ($from_type){
		case COMPUTER_TYPE :
			switch ($to_type){
/*				case NETWORKING_TYPE :
					array_push($already_link_tables2,$LINK_ID_TABLE[NETWORKING_TYPE]."_$num");
					return " INNER JOIN glpi_networking_ports as META_ports ON (glpi_computers.ID = META_ports.on_device AND META_ports.device_type='".COMPUTER_TYPE."') ".
						   " INNER JOIN glpi_networking_wire as META_wire1 ON (META_ports.ID = META_wire1.end1) ".
						   " INNER JOIN glpi_networking_ports as META_ports21 ON (META_ports21.device_type='".NETWORKING_TYPE."' AND META_wire1.end2 = META_ports21.ID ) ".
						   " INNER JOIN glpi_networking_wire as META_wire2 ON (META_ports.ID = META_wire2.end2) ".
						   " INNER JOIN glpi_networking_ports as META_ports22 ON (META_ports22.device_type='".NETWORKING_TYPE."' AND META_wire2.end1 = META_ports22.ID ) ".
						   " INNER JOIN glpi_networking$num ON (glpi_networking$num.ID = META_ports22.on_device OR glpi_networking.ID = META_ports21.on_device)";
				break;
*/				
				case PRINTER_TYPE :
					array_push($already_link_tables2,$LINK_ID_TABLE[PRINTER_TYPE]."_$num");
					return " INNER JOIN glpi_connect_wire AS META_conn_print_$num ON (META_conn_print_$num.end2=glpi_computers.ID  AND META_conn_print_$num.type='".PRINTER_TYPE."') ".
						   " INNER JOIN glpi_printers as glpi_printers_$num ON (META_conn_print_$num.end1=glpi_printers_$num.ID) ";
				break;				
				case MONITOR_TYPE :
					array_push($already_link_tables2,$LINK_ID_TABLE[MONITOR_TYPE]."_$num");
					return " INNER JOIN glpi_connect_wire AS META_conn_mon_$num ON (META_conn_mon_$num.end2=glpi_computers.ID  AND META_conn_mon_$num.type='".MONITOR_TYPE."') ".
						   " INNER JOIN glpi_monitors AS glpi_monitors_$num ON (META_conn_mon_$num.end1=glpi_monitors_$num.ID) ";
				break;				
				case PERIPHERAL_TYPE :
					array_push($already_link_tables2,$LINK_ID_TABLE[PERIPHERAL_TYPE]."_$num");
					return " INNER JOIN glpi_connect_wire AS META_conn_periph_$num ON (META_conn_periph_$num.end2=glpi_computers.ID  AND META_conn_periph_$num.type='".PERIPHERAL_TYPE."') ".
						   " INNER JOIN glpi_peripherals AS glpi_peripherals_$num ON (META_conn_periph_$num.end1=glpi_peripherals_$num.ID) ";
				break;				
				case SOFTWARE_TYPE :
					array_push($already_link_tables2,$LINK_ID_TABLE[SOFTWARE_TYPE]."_$num");
					return " INNER JOIN glpi_inst_software as META_inst_$num ON (META_inst_$num.cID = glpi_computers.ID) ".
						   " INNER JOIN glpi_licenses as META_lic_$num ON ( META_inst_$num.license=META_lic_$num.ID ) ".
						   " INNER JOIN glpi_software AS glpi_software_$num ON (META_lic_$num.sID = glpi_software_$num.ID) "; 
				break;
				}
			break;
		case MONITOR_TYPE :
			switch ($to_type){
				case COMPUTER_TYPE :
					array_push($already_link_tables2,$LINK_ID_TABLE[COMPUTER_TYPE]."_$num");
					return " INNER JOIN glpi_connect_wire AS META_conn_mon_$num ON (META_conn_mon_$num.end1=glpi_monitors.ID  AND META_conn_mon_$num.type='".MONITOR_TYPE."') ".
						   " INNER JOIN glpi_computers AS glpi_computers_$num ON (META_conn_mon_$num.end2=glpi_computers_$num.ID) ";
				
				break;
			}
			break;		
		case PRINTER_TYPE :
			switch ($to_type){
				case COMPUTER_TYPE :
					array_push($already_link_tables2,$LINK_ID_TABLE[COMPUTER_TYPE]."_$num");
					return " INNER JOIN glpi_connect_wire AS META_conn_mon_$num ON (META_conn_mon_$num.end1=glpi_printers.ID  AND META_conn_mon_$num.type='".PRINTER_TYPE."') ".
						   " INNER JOIN glpi_computers AS glpi_computers_$num ON (META_conn_mon_$num.end2=glpi_computers_$num.ID) ";
				
				break;
			}
			break;		
		case PERIPHERAL_TYPE :
			switch ($to_type){
				case COMPUTER_TYPE :
					array_push($already_link_tables2,$LINK_ID_TABLE[COMPUTER_TYPE]."_$num");
					return " INNER JOIN glpi_connect_wire AS META_conn_mon_$num ON (META_conn_mon_$num.end1=glpi_peripherals.ID  AND META_conn_mon_$num.type='".PERIPHERAL_TYPE."') ".
						   " INNER JOIN glpi_computers AS glpi_computers_$num ON (META_conn_mon_$num.end2=glpi_computers_$num.ID) ";
				
				break;
			}
			break;		
		case SOFTWARE_TYPE :
			switch ($to_type){
				case COMPUTER_TYPE :
					array_push($already_link_tables2,$LINK_ID_TABLE[COMPUTER_TYPE]."_$num");
				return " INNER JOIN glpi_licenses as META_lic_$num ON ( META_lic_$num.sID = glpi_software.ID ) ".
					   " INNER JOIN glpi_inst_software as META_inst_$num ON (META_inst_$num.license = META_lic_$num.ID) ".
					   " INNER JOIN glpi_computers AS glpi_computers_$num ON (META_inst_$num.cID = glpi_computers_$num.ID) ";
					
				break;
			}
			break;		
		
		
		}
	
}

function sylk_clean($value){

	$value=utf8_decode($value);
       	if (get_magic_quotes_runtime()) $value=stripslashes($value);
        $value=preg_replace('/\x0A/',' ',$value);
	$value=preg_replace('/<a[^>]+>/',' ',$value);
	$value=preg_replace('/<img[^>]+>/',' ',$value);
	$value=preg_replace('/<\/a>/',' ',$value);
        $value=preg_replace('/\x0D/',NULL,$value);
        $value=ereg_replace("\"","''",$value);
	$value=str_replace(';', ';;', $value);
return trim($value);
}

?>
