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


// Functions Dropdown




/**
* Print out an HTML "<select>" for a dropdown
*
* 
* 
*
* @param $table the dropdown table from witch we want values on the select
* @param $myname the name of the HTML select
* @return nothing (display the select box)
**/
function dropdown($table,$myname) {


	global $HTMLRel,$cfg_install,$cfg_features;

	$rand=mt_rand();
echo "<input type='text' id='search_$myname$rand' name='____data_$myname$rand' size='4'>\n";

echo "<script type='text/javascript' >\n";
echo "   new Form.Element.Observer('search_$myname$rand', 1, \n";
echo "      function(element, value) {\n";
echo "      	new Ajax.Updater('results_$myname$rand','".$cfg_install["root"]."/ajax/dropdown.php',{asynchronous:true, evalScripts:true, \n";
echo "           onComplete:function(request)\n";
echo "            {Element.hide('search_spinner_$myname$rand');}, \n";
echo "           onLoading:function(request)\n";
echo "            {Element.show('search_spinner_$myname$rand');},\n";
echo "           method:'post', parameters:'searchText=' + value+'&table=$table&myname=$myname'\n";
echo "})})\n";
echo "</script>\n";


echo "<div id='search_spinner_$myname$rand' style=' position:absolute;   filter:alpha(opacity=70); -moz-opacity:0.7; opacity: 0.7; display:none;'><img src=\"".$HTMLRel."pics/wait.png\" title='Processing....' alt='' /></div>\n";

$nb=0;
if ($cfg_features["use_ajax"])
	$nb=countElementsInTable($table);

if (!$cfg_features["use_ajax"]||$nb<$cfg_features["ajax_limit_count"]){
	echo "<script type='text/javascript' >\n";
	echo "document.getElementById('search_spinner_$myname$rand').style.visibility='hidden';";
	echo "Element.hide('search_$myname$rand');";
	echo "document.getElementById('search_$myname$rand').value='".$cfg_features["ajax_wildcard"]."';";
	echo "</script>\n";
}


echo "<span id='results_$myname$rand'>\n";
echo "<select name='$myname'><option value='0'>------</option></select>\n";
echo "</span>\n";	

}

/**
* Print out an HTML "<select>" for a dropdown with preselected value
*
*
*
*
*
* @param $table the dropdown table from witch we want values on the select
* @param $myname the name of the HTML select
* @param $value the preselected value we want
* @return nothing (display the select box)
*
*/
function dropdownValue($table,$myname,$value) {
	
	global $HTMLRel,$cfg_install,$cfg_features;

	$rand=mt_rand();
echo "<input type='text' id='search_$myname$rand' name='____data_$myname$rand' size='4'>\n";

echo "<script type='text/javascript' >\n";
echo "   new Form.Element.Observer('search_$myname$rand', 1, \n";
echo "      function(element, value) {\n";
echo "      	new Ajax.Updater('results_$myname$rand','".$cfg_install["root"]."/ajax/dropdownValue.php',{asynchronous:true, evalScripts:true, \n";
echo "           onComplete:function(request)\n";
echo "            {Element.hide('search_spinner_$myname$rand');}, \n";
echo "           onLoading:function(request)\n";
echo "            {Element.show('search_spinner_$myname$rand');},\n";
echo "           method:'post', parameters:'searchText=' + value+'&value=$value&table=$table&myname=$myname'\n";
echo "})})\n";
echo "</script>\n";

echo "<div id='search_spinner_$myname$rand' style=' position:absolute;  filter:alpha(opacity=70); -moz-opacity:0.7; opacity: 0.7; display:none;'><img src=\"".$HTMLRel."pics/wait.png\" title='Processing....' alt='Processing....' /></div>\n";

$nb=0;
if ($cfg_features["use_ajax"])
	$nb=countElementsInTable($table);

if (!$cfg_features["use_ajax"]||$nb<$cfg_features["ajax_limit_count"]){
	echo "<script type='text/javascript' >\n";
	echo "document.getElementById('search_spinner_$myname$rand').style.visibility='hidden';";
	echo "Element.hide('search_$myname$rand');";
	echo "document.getElementById('search_$myname$rand').value='".$cfg_features["ajax_wildcard"]."';";
	echo "</script>\n";
}



echo "<span id='results_$myname$rand'>\n";
if (!empty($value)&&$value>0)
	echo "<select name='$myname'><option value='$value'>".getDropdownName($table,$value)."</option></select>\n";
else 
	echo "<select name='$myname'><option value='0'>------</option></select>\n";
echo "</span>\n";	
}



/**
* To be commented
*
*
*
*
*
* 
*/
function dropdownNoValue($table,$myname,$value) {
	// Make a select box without parameters value

	global $deleted_tables,$template_tables,$dropdowntree_tables;

	$db = new DB;

	$where="";
	if (in_array($table,$deleted_tables))
		$where="WHERE deleted='N'";
	if (in_array($table,$template_tables))
		$where.="AND is_template='0'";
		
	if (in_array($table,$dropdowntree_tables))
		$query = "SELECT ID FROM $table $where ORDER BY completename";
	else $query = "SELECT ID FROM $table $where ORDER BY name";
	$result = $db->query($query);
	
	echo "<select name=\"$myname\" size='1'>";
	$i = 0;
	$number = $db->numrows($result);
	if ($number > 0) {
		while ($i < $number) {
			$ID = $db->result($result, $i, "ID");
			if ($ID === $value) {
			} else {
				echo "<option value=\"$ID\">".getDropdownName($table,$ID)."</option>";
			}
			$i++;
		}
	}
	echo "</select>";
}


/**
* Make a select box with all glpi users where select key = name
*
* Think it's unused now.
*
*
* @param $value
* @param $myname
* @return nothing (print out an HTML select box)
*
*
*/
// $all =0 -> Nobody $all=1 -> All $all=-1-> nothing
function dropdownUsers($myname,$value,$all=0) {
	// Make a select box with all glpi users

	global $HTMLRel,$cfg_install,$cfg_features;

	$rand=mt_rand();
	echo "<input type='text' id='search_$myname$rand' name='____data_$myname$rand' size='4'>\n";

	echo "<script type='text/javascript' >\n";
	echo "   new Form.Element.Observer('search_$myname$rand', 1, \n";
	echo "      function(element, value) {\n";
	echo "      	new Ajax.Updater('results_$myname$rand','".$cfg_install["root"]."/ajax/dropdownUsers.php',{asynchronous:true, evalScripts:true, \n";
	echo "           onComplete:function(request)\n";
	echo "            {Element.hide('search_spinner_$myname$rand');}, \n";
	echo "           onLoading:function(request)\n";
	echo "            {Element.show('search_spinner_$myname$rand');},\n";
	echo "           method:'post', parameters:'searchText=' + value+'&value=$value&table=glpi_users&myname=$myname&all=$all'\n";
	echo "})})\n";
	echo "</script>\n";

echo "<div id='search_spinner_$myname$rand' style=' position:absolute;   filter:alpha(opacity=70); -moz-opacity:0.7; opacity: 0.7; display:none;'><img src=\"".$HTMLRel."pics/wait.png\" title='Processing....' alt='Processing....' /></div>\n";

$nb=0;
if ($cfg_features["use_ajax"])
	$nb=countElementsInTable("glpi_users");

if (!$cfg_features["use_ajax"]||$nb<$cfg_features["ajax_limit_count"]){
	echo "<script type='text/javascript' >\n";
	echo "document.getElementById('search_spinner_$myname$rand').style.visibility='hidden';";
	echo "Element.hide('search_$myname$rand');";
	echo "document.getElementById('search_$myname$rand').value='".$cfg_features["ajax_wildcard"]."';";
	echo "</script>\n";
}


	echo "<span id='results_$myname$rand'>\n";
	if (!empty($value)&&$value>0)
		echo "<select name='$myname'><option value='$value'>".getDropdownName("glpi_users",$value)."</option></select>\n";
	else 
		echo "<select name='$myname'><option value='0'>[ Nobody ]</option></select>\n";
	echo "</span>\n";	
}

function dropdownAllUsers($myname,$value) {
	global $lang;
	// Make a select box with all glpi users

	global $HTMLRel,$cfg_install,$cfg_features;

	$rand=mt_rand();
	echo "<input type='text' id='search_$myname$rand' name='____data_$myname$rand' size='4'>\n";

	echo "<script type='text/javascript' >\n";
	echo "   new Form.Element.Observer('search_$myname$rand', 1, \n";
	echo "      function(element, value) {\n";
	echo "      	new Ajax.Updater('results_$myname$rand','".$cfg_install["root"]."/ajax/dropdownAllUsers.php',{asynchronous:true, evalScripts:true, \n";
	echo "           onComplete:function(request)\n";
	echo "            {Element.hide('search_spinner_$myname$rand');}, \n";
	echo "           onLoading:function(request)\n";
	echo "            {Element.show('search_spinner_$myname$rand');},\n";
	echo "           method:'post', parameters:'searchText=' + value+'&value=$value&table=glpi_users&myname=$myname'\n";
	echo "})})\n";
	echo "</script>\n";

echo "<div id='search_spinner_$myname$rand' style=' position:absolute;   filter:alpha(opacity=70); -moz-opacity:0.7; opacity: 0.7; display:none;'><img src=\"".$HTMLRel."pics/wait.png\" title='Processing....' alt='Processing....' /></div>\n";

$nb=0;
if ($cfg_features["use_ajax"])
	$nb=countElementsInTable("glpi_users");

if (!$cfg_features["use_ajax"]||$nb<$cfg_features["ajax_limit_count"]){
	echo "<script type='text/javascript' >\n";
	echo "document.getElementById('search_spinner_$myname$rand').style.visibility='hidden';";
	echo "Element.hide('search_$myname$rand');";
	echo "document.getElementById('search_$myname$rand').value='".$cfg_features["ajax_wildcard"]."';";
	echo "</script>\n";
}

	echo "<span id='results_$myname$rand'>\n";
	if (!empty($value)&&$value>0)
		echo "<select name='$myname'><option value='$value'>".getDropdownName("glpi_users",$value)."</option></select>\n";
	else 
		echo "<select name='$myname'><option value='0'>[ Nobody ]</option></select>\n";
	echo "</span>\n";	

}


/**
* Make a select box with all glpi users where select key = name
*
* Think it's unused now.
*
*
* @param $value
* @param $myname
* @return nothing (print out an HTML select box)
*
*
*/
function dropdownAssign($value, $value_type,$myname) {
	// Make a select box with all glpi users
	global $HTMLRel,$cfg_install,$lang;
	
	$db=new DB;
	
	$items=array(
	USER_TYPE=>"glpi_users",
	ENTERPRISE_TYPE=>"glpi_enterprises",
	);

	$rand=mt_rand();
	echo "<table border='0'><tr><td>\n";
	echo "<select name='assign_type' id='item_type$rand'>\n";
	echo "<option value='0'>-----</option>\n";
	echo "<option ".($value_type==USER_TYPE?" selected ":"")." value='".USER_TYPE."'>".$lang["Menu"][14]."</option>\n";
	echo "<option ".($value_type==ENTERPRISE_TYPE?" selected ":"")." value='".ENTERPRISE_TYPE."'>".$lang["Menu"][23]."</option>\n";
	echo "</select>\n";
	
	
	echo "<script type='text/javascript' >\n";
	echo "   new Form.Element.Observer('item_type$rand', 1, \n";
	echo "      function(element, value) {\n";
	echo "      	new Ajax.Updater('show_$myname$rand','".$cfg_install["root"]."/ajax/dropdownAllItems.php',{asynchronous:true, evalScripts:true, \n";
	echo "           onComplete:function(request)\n";
	echo "            {Element.hide('search_spinner_$myname$rand');}, \n";
	echo "           onLoading:function(request)\n";
	echo "            {Element.show('search_spinner_$myname$rand');},\n";
	echo "           method:'post', parameters:'idtable='+value+'&myname=$myname&value=$value'\n";
	echo "})})\n";
	echo "</script>\n";
	
	echo "<div id='search_spinner_$myname$rand' style=' position:absolute; filter:alpha(opacity=70); -moz-opacity:0.7; opacity: 0.7; display:none;'><img src=\"".$HTMLRel."pics/wait.png\" title='Processing....' alt='Processing....' /></div>\n";
	echo "</td><td>\n"	;
	echo "<span id='show_$myname$rand'></span>\n";
	echo "</td></tr>\n";
	echo "</table>\n";
	
	
}

/**
* Make a select box with all glpi users where select key = ID
*
*
*
* @param $value
* @param $myname
* @return nothing (print out an HTML select box)
*/
function dropdownUsersID($myname,$value) {
	// Make a select box with all glpi users

	dropdownUsers($myname,$value);
}

/**
* Get the value of a dropdown 
*
*
* Returns the value of the dropdown from $table with ID $id.
*
* @param $table
* @param $id
* @return string the value of the dropdown or "" (\0) if not exists
*/
function getDropdownName($table,$id) {
	global $cfg_install,$dropdowntree_tables;
	
	if (in_array($table,$dropdowntree_tables)){
		$name=getTreeValueCompleteName($table,$id);

	} else	{
	
		$db = new DB;
		$name = "";
		$query = "select * from ". $table ." where ID = '". $id ."'";
		if ($result = $db->query($query))
		if($db->numrows($result) != 0) {
			$name = $db->result($result,0,"name");
			if ($table=="glpi_dropdown_netpoint")
				$name .= " (".getDropdownName("glpi_dropdown_locations",$db->result($result,0,"location")).")";
			
		}
	}
	if (empty($name)) return "&nbsp;";
	return $name;
}

/**
* Make a select box with all glpi users in tracking table
*
*
*
* @param $value
* @param $myname
* @param $champ
* @return nothing (print out an HTML select box)
*/

function dropdownUsersTracking($myname,$value,$champ) {
	global $HTMLRel,$cfg_install,$lang,$cfg_features;

	$rand=mt_rand();
	echo "<input type='text' id='search_$myname$rand' name='____data_$myname$rand' size='4'>\n";

	echo "<script type='text/javascript' >\n";
	echo "   new Form.Element.Observer('search_$myname$rand', 1, \n";
	echo "      function(element, value) {\n";
	echo "      	new Ajax.Updater('results_$myname$rand','".$cfg_install["root"]."/ajax/dropdownUsersTracking.php',{asynchronous:true, evalScripts:true, \n";
	echo "           onComplete:function(request)\n";
	echo "            {Element.hide('search_spinner_$myname$rand');}, \n";
	echo "           onLoading:function(request)\n";
	echo "            {Element.show('search_spinner_$myname$rand');},\n";
	echo "           method:'post', parameters:'searchText=' + value+'&value=$value&champ=$champ&myname=$myname'\n";
	echo "})})\n";
	echo "</script>\n";

	echo "<div id='search_spinner_$myname$rand' style=' position:absolute;   filter:alpha(opacity=70); -moz-opacity:0.7; opacity: 0.7; display:none;'><img src=\"".$HTMLRel."pics/wait.png\" title='Processing....' alt='Processing....' /></div>\n";

$nb=0;
if ($cfg_features["use_ajax"])
	$nb=countElementsInTable("glpi_users");

if (!$cfg_features["use_ajax"]||$nb<$cfg_features["ajax_limit_count"]){
	echo "<script type='text/javascript' >\n";
	echo "document.getElementById('search_spinner_$myname$rand').style.visibility='hidden';";
	echo "Element.hide('search_$myname$rand');";
	echo "document.getElementById('search_$myname$rand').value='".$cfg_features["ajax_wildcard"]."';";
	echo "</script>\n";
}

	echo "<span id='results_$myname$rand'>\n";
	if (!empty($value)&&$value>0)
		echo "<select name='$myname'><option value='$value'>".getDropdownName("glpi_users",$value)."</option></select>\n";
	else 
		echo "<select name='$myname'><option value='0'>[ ".$lang["search"][7]." ]</option></select>\n";
	echo "</span>\n";	
	
}

/**
* 
*
*
*
* @param $value
* @param $myname
* @param $store_path
* @return nothing (print out an HTML select box)
*/
function dropdownIcons($myname,$value,$store_path){
global $HTMLRel;
if (is_dir($store_path)){
if ($dh = opendir($store_path)) {
	echo "<select name=\"$myname\">";
       while (($file = readdir($dh)) !== false) {
           if (eregi(".png$",$file)){
	   if ($file == $value) {
				echo "<option value=\"$file\" selected>".$file;
			} else {
				echo "<option value=\"$file\">".$file;
			}
	echo "</option>";
	   }
	   
       
       }
       closedir($dh);
       echo "</select>";
   } else echo "Error reading directory $store_path";


} else echo "Error $store_path is not a directory";


}



function dropdownDeviceType($name,$device_type){
global $lang;
echo "<select name='$name'>\n";
	echo "<option value='0'>-----</option>\n";
    	echo "<option value='".COMPUTER_TYPE."' ".(($device_type==COMPUTER_TYPE)?" selected":"").">".$lang["help"][25]."</option>\n";
	echo "<option value='".NETWORKING_TYPE."' ".(($device_type==NETWORKING_TYPE)?" selected":"").">".$lang["help"][26]."</option>\n";
	echo "<option value='".PRINTER_TYPE."' ".(($device_type==PRINTER_TYPE)?" selected":"").">".$lang["help"][27]."</option>\n";
	echo "<option value='".MONITOR_TYPE."' ".(($device_type==MONITOR_TYPE)?" selected":"").">".$lang["help"][28]."</option>\n";
	echo "<option value='".PERIPHERAL_TYPE."' ".(($device_type==PERIPHERAL_TYPE)?" selected":"").">".$lang["help"][29]."</option>\n";
	echo "<option value='".SOFTWARE_TYPE."' ".(($device_type==SOFTWARE_TYPE)?" selected":"").">".$lang["help"][31]."</option>\n";
	echo "<option value='".CARTRIDGE_TYPE."' ".(($device_type==CARTRIDGE_TYPE)?" selected":"").">".$lang["Menu"][21]."</option>\n";
	echo "<option value='".CONSUMABLE_TYPE."' ".(($device_type==CONSUMABLE_TYPE)?" selected":"").">".$lang["Menu"][32]."</option>\n";
	echo "<option value='".CONTACT_TYPE."' ".(($device_type==CONTACT_TYPE)?" selected":"").">".$lang["Menu"][22]."</option>\n";
	echo "<option value='".ENTERPRISE_TYPE."' ".(($device_type==ENTERPRISE_TYPE)?" selected":"").">".$lang["Menu"][23]."</option>\n";
	echo "<option value='".CONTRACT_TYPE."' ".(($device_type==CONTRACT_TYPE)?" selected":"").">".$lang["Menu"][25]."</option>\n";
	echo "</select>\n";


}



/**
* 
*
*
*
* @param $name
* @param $withenterprise
* @param $withcartridge
* @param $withconsumable
* @param $search
* @param $value
* @return nothing (print out an HTML select box)
*/
function dropdownAllItems($myname,$value_type=0,$withenterprise=0,$withcartridge=0,$withconsumable=0,$withcontracts=0) {
	global $lang,$HTMLRel,$cfg_install;
	
	$db=new DB;
	
	$items=array(
	COMPUTER_TYPE=>"glpi_computers",
	NETWORKING_TYPE=>"glpi_networking",
	PRINTER_TYPE=>"glpi_printers",
	MONITOR_TYPE=>"glpi_monitors",
	PERIPHERAL_TYPE=>"glpi_peripherals",
	SOFTWARE_TYPE=>"glpi_software",
	);

	if ($withenterprise==1) $items[ENTERPRISE_TYPE]="glpi_enterprises";
	if ($withcartridge==1) $items[CARTRIDGE_TYPE]="glpi_cartridges_type";
	if ($withconsumable==1) $items[CONSUMABLE_TYPE]="glpi_consumables_type";
	if ($withcontracts==1) $items[CONTRACT_TYPE]="glpi_contracts";
	
	
	$rand=mt_rand();
	echo "<table border='0'><tr><td>\n";
	echo "<select name='type' id='item_type$rand'>\n";
	echo "<option value='0'>-----</option>\n";
	echo "<option ".($value_type==COMPUTER_TYPE?" selected ":"")." value='".COMPUTER_TYPE."'>".$lang["Menu"][0]."</option>\n";
	echo "<option ".($value_type==NETWORKING_TYPE?" selected ":"")." value='".NETWORKING_TYPE."'>".$lang["Menu"][1]."</option>\n";
	echo "<option ".($value_type==PRINTER_TYPE?" selected ":"")." value='".PRINTER_TYPE."'>".$lang["Menu"][2]."</option>\n";
	echo "<option ".($value_type==MONITOR_TYPE?" selected ":"")." value='".MONITOR_TYPE."'>".$lang["Menu"][3]."</option>\n";
	echo "<option ".($value_type==PERIPHERAL_TYPE?" selected ":"")." value='".PERIPHERAL_TYPE."'>".$lang["Menu"][16]."</option>\n";
	echo "<option ".($value_type==SOFTWARE_TYPE?" selected ":"")." value='".SOFTWARE_TYPE."'>".$lang["Menu"][4]."</option>\n";
	if ($withenterprise==1) echo "<option ".($value_type==ENTERPRISE_TYPE?" selected ":"")." value='".ENTERPRISE_TYPE."'>".$lang["Menu"][23]."</option>\n";
	if ($withcartridge==1) echo "<option ".($value_type==CARTRIDGE_TYPE?" selected ":"")." value='".CARTRIDGE_TYPE."'>".$lang["Menu"][21]."</option>\n";
	if ($withconsumable==1) echo "<option ".($value_type==CONSUMABLE_TYPE?" selected ":"")." value='".CONSUMABLE_TYPE."'>".$lang["Menu"][32]."</option>\n";
	if ($withcontracts==1) echo "<option ".($value_type==CONTRACT_TYPE?" selected ":"")." value='".CONTRACT_TYPE."'>".$lang["Menu"][25]."</option>\n";
	echo "</select>\n";
	
	
	echo "<script type='text/javascript' >\n";
	echo "   new Form.Element.Observer('item_type$rand', 1, \n";
	echo "      function(element, value) {\n";
	echo "      	new Ajax.Updater('show_$myname$rand','".$cfg_install["root"]."/ajax/dropdownAllItems.php',{asynchronous:true, evalScripts:true, \n";	echo "           onComplete:function(request)\n";
	echo "            {Element.hide('search_spinner_$myname$rand');}, \n";
	echo "           onLoading:function(request)\n";
	echo "            {Element.show('search_spinner_$myname$rand');},\n";
	echo "           method:'post', parameters:'idtable='+value+'&myname=$myname'\n";
	echo "})})\n";
	echo "</script>\n";
	
	echo "<div id='search_spinner_$myname$rand' style=' position:absolute;   filter:alpha(opacity=70); -moz-opacity:0.7; opacity: 0.7; display:none;'><img src=\"".$HTMLRel."pics/wait.png\" title='Processing....' alt='Processing....' /></div>\n";
	echo "</td><td>\n"	;
	echo "<span id='show_$myname$rand'>&nbsp;</span>\n";
	echo "</td></tr></table>\n";
	
}

/**
* Make a select box for a boolean choice (Yes/No)
*
*
*
* @param $name select name
* @param $value preselected value.
*
*/
function dropdownYesNo($name,$value){
	global $lang;
	echo "<select name='$name'>\n";
	echo "<option value='N' ".($value=='N'?" selected ":"").">".$lang["choice"][1]."</option>\n";
	echo "<option value='Y' ".($value=='Y'?" selected ":"").">".$lang["choice"][0]."</option>\n";
	echo "</select>\n";	
}	


function dropdownTrackingDeviceType($myname,$value){
	global $lang,$HTMLRel,$cfg_install;
	
	$rand=mt_rand();
	
	echo "<select id='search_$myname$rand' name='$myname'>\n";

	echo "<option value='0' ".(($value==0)?" selected":"").">".$lang["help"][30]."</option>\n";
	echo "<option value='".COMPUTER_TYPE."' ".(($value==COMPUTER_TYPE)?" selected":"").">".$lang["help"][25]."</option>\n";
	echo "<option value='".NETWORKING_TYPE."' ".(($value==NETWORKING_TYPE)?" selected":"").">".$lang["help"][26]."</option>\n";
	echo "<option value='".PRINTER_TYPE."' ".(($value==PRINTER_TYPE)?" selected":"").">".$lang["help"][27]."</option>\n";
	echo "<option value='".MONITOR_TYPE."' ".(($value==MONITOR_TYPE)?" selected":"").">".$lang["help"][28]."</option>\n";
	echo "<option value='".PERIPHERAL_TYPE."' ".(($value==PERIPHERAL_TYPE)?" selected":"").">".$lang["help"][29]."</option>\n";
	echo "<option value='".SOFTWARE_TYPE."' ".(($value==SOFTWARE_TYPE)?" selected":"").">".$lang["help"][31]."</option>\n";
	echo "</select>\n";

echo "<script type='text/javascript' >\n";
echo "   new Form.Element.Observer('search_$myname$rand', 1, \n";
echo "      function(element, value) {\n";
echo "      	new Ajax.Updater('results_$myname$rand','".$cfg_install["root"]."/ajax/dropdownTrackingDeviceType.php',{asynchronous:true, evalScripts:true, \n";
echo "           onComplete:function(request)\n";
echo "            {Element.hide('search_spinner_$myname$rand');}, \n";
echo "           onLoading:function(request)\n";
echo "            {Element.show('search_spinner_$myname$rand');},\n";
echo "           method:'post', parameters:'type=' + value+'&myname=computer'\n";
echo "})})\n";
echo "</script>\n";


echo "<div id='search_spinner_$myname$rand' style=' position:absolute;  filter:alpha(opacity=70); -moz-opacity:0.7; opacity: 0.7; display:none;'><img src=\"".$HTMLRel."pics/wait.png\" title='Processing....' alt='Processing....' /></div>\n";

echo "</td></tr><tr><td class='tab_bg_2' colspan='2'>";
echo "<div align='center'>";
echo "<span id='results_$myname$rand'>\n";

if (isset($_SESSION["helpdeskSaved"]["computer"])){
	$ci=new CommonItem();
	if ($ci->getFromDB($value,$_SESSION["helpdeskSaved"]["computer"])){
		echo "<select name='computer'>\n";
		echo "<option value='".$_SESSION["helpdeskSaved"]["computer"]."'>".$ci->getName()."</option>\n";
	
		echo "</select>\n";
	}
}

echo "</span>\n";	
echo "</div>";
			
}

function dropdownConnect($type,$myname) {


	global $HTMLRel,$cfg_install,$cfg_features;

		$items=array(
			COMPUTER_TYPE=>"glpi_computers",
			PRINTER_TYPE=>"glpi_printers",
			MONITOR_TYPE=>"glpi_monitors",
			PERIPHERAL_TYPE=>"glpi_peripherals",
		);

	$rand=mt_rand();
echo "<input type='text' id='search_$myname$rand' name='____data_$myname$rand' size='4'>\n";

echo "<script type='text/javascript' >\n";
echo "   new Form.Element.Observer('search_$myname$rand', 1, \n";
echo "      function(element, value) {\n";
echo "      	new Ajax.Updater('results_$myname$rand','".$cfg_install["root"]."/ajax/dropdownConnect.php',{asynchronous:true, evalScripts:true, \n";
echo "           onComplete:function(request)\n";
echo "            {Element.hide('search_spinner_$myname$rand');}, \n";
echo "           onLoading:function(request)\n";
echo "            {Element.show('search_spinner_$myname$rand');},\n";
echo "           method:'post', parameters:'searchText=' + value+'&idtable=$type&myname=$myname'\n";
echo "})})\n";
echo "</script>\n";

echo "<div id='search_spinner_$myname$rand' style=' position:absolute;   filter:alpha(opacity=70); -moz-opacity:0.7; opacity: 0.7; display:none;'><img src=\"".$HTMLRel."pics/wait.png\" title='Processing....' alt='' /></div>\n";

$nb=0;
if ($cfg_features["use_ajax"])
	$nb=countElementsInTable($items[$type]);

if (!$cfg_features["use_ajax"]||$nb<$cfg_features["ajax_limit_count"]){
	echo "<script type='text/javascript' >\n";
	echo "document.getElementById('search_spinner_$myname$rand').style.visibility='hidden';";
	echo "Element.hide('search_$myname$rand');";
	echo "document.getElementById('search_$myname$rand').value='".$cfg_features["ajax_wildcard"]."';";
	echo "</script>\n";
}


echo "<span id='results_$myname$rand'>\n";
echo "<select name='$myname'><option value='0'>------</option></select>\n";
echo "</span>\n";	
}


function dropdownConnectPort($ID,$type,$myname) {


	global $lang,$HTMLRel,$cfg_install;
	
	$db=new DB;
	
	$items=array(
	COMPUTER_TYPE=>"glpi_computers",
	NETWORKING_TYPE=>"glpi_networking",
	PRINTER_TYPE=>"glpi_printers",
	PERIPHERAL_TYPE=>"glpi_peripherals",
	);

	
	$rand=mt_rand();
	echo "<select name='type' id='item_type$rand'>\n";
	echo "<option value='0'>-----</option>\n";
	echo "<option value='".COMPUTER_TYPE."'>".$lang["Menu"][0]."</option>\n";
	echo "<option value='".NETWORKING_TYPE."'>".$lang["Menu"][1]."</option>\n";
	echo "<option value='".PRINTER_TYPE."'>".$lang["Menu"][2]."</option>\n";
	echo "<option value='".PERIPHERAL_TYPE."'>".$lang["Menu"][16]."</option>\n";
	echo "</select>\n";
	
	
	echo "<script type='text/javascript' >\n";
	echo "   new Form.Element.Observer('item_type$rand', 1, \n";
	echo "      function(element, value) {\n";
	echo "      	new Ajax.Updater('show_$myname$rand','".$cfg_install["root"]."/ajax/dropdownConnectPortDeviceType.php',{asynchronous:true, evalScripts:true, \n";	echo "           onComplete:function(request)\n";
	echo "            {Element.hide('search_spinner_$myname$rand');}, \n";
	echo "           onLoading:function(request)\n";
	echo "            {Element.show('search_spinner_$myname$rand');},\n";
	echo "           method:'post', parameters:'current=$ID&type='+value+'&myname=$myname'\n";
	echo "})})\n";
	echo "</script>\n";
	
	echo "<div id='search_spinner_$myname$rand' style=' position:absolute;   filter:alpha(opacity=70); -moz-opacity:0.7; opacity: 0.7; display:none;'><img src=\"".$HTMLRel."pics/wait.png\" title='Processing....' alt='Processing....' /></div>\n";
	echo "<span id='show_$myname$rand'>&nbsp;</span>\n";


}

function dropdownSoftwareToInstall($myname,$withtemplate) {
	global $lang,$HTMLRel,$cfg_install;
	
	$db=new DB;
	
	$rand=mt_rand();

	
	echo "<input type='text' id='search_$myname$rand' name='____data_$myname$rand' size='4'>\n";

	echo "<script type='text/javascript' >\n";
	echo "   new Form.Element.Observer('search_$myname$rand', 1, \n";
	echo "      function(element, value) {\n";
	echo "      	new Ajax.Updater('results_$myname$rand','".$cfg_install["root"]."/ajax/dropdownSelectSoftware.php',{asynchronous:true, evalScripts:true, \n";
	echo "           onComplete:function(request)\n";
	echo "            {Element.hide('search_spinner_$myname$rand');}, \n";
	echo "           onLoading:function(request)\n";
	echo "            {Element.show('search_spinner_$myname$rand');},\n";
	echo "           method:'post', parameters:'searchSoft=' + value+'&myname=$myname&withtemplate=$withtemplate'\n";
	echo "})})\n";
	echo "</script>\n";

	echo "<div id='search_spinner_$myname$rand' style=' position:absolute;   filter:alpha(opacity=70); -moz-opacity:0.7; opacity: 0.7; display:none;'><img src=\"".$HTMLRel."pics/wait.png\" title='Processing....' alt='' /></div>\n";

	echo "<span id='results_$myname$rand'>\n";
	echo "<select name='$myname'><option value='0'>------</option></select>\n";
	echo "</span>\n";	
	
	
	
}

function autocompletionTextField($myname,$table,$field,$value='',$size=20,$option=''){
	global $HTMLRel,$cfg_features;

	if ($cfg_features["use_ajax"]&&$cfg_features["ajax_autocompletion"]){
		$rand=mt_rand();
		echo "<input $option id='textfield_$myname$rand' type='text' name='$myname' value=\"".$value."\" size='$size'>\n";
		echo "<div id='textfieldupdate_$myname$rand' style='display:none;border:1px solid black;background-color:white;'></div>\n";
		echo "<script type='text/javascript' language='javascript' charset='utf-8'>";
	    echo "new Ajax.Autocompleter('textfield_$myname$rand','textfieldupdate_$myname$rand','".$HTMLRel."/ajax/autocompletion.php',{parameters:'table=$table&field=$field&myname=$myname'});";
		echo "</script>";
	}	else {
		echo "<input $option type='text' name='$myname' value=\"".$value."\" size=$size'>\n";
	}
}

?>