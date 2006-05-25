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


	global $HTMLRel,$cfg_glpi;

	$rand=mt_rand();
	
	displaySearchTextAjaxDropdown($myname.$rand);

echo "<script type='text/javascript' >\n";
echo "   new Form.Element.Observer('search_$myname$rand', 1, \n";
echo "      function(element, value) {\n";
echo "      	new Ajax.Updater('results_$myname$rand','".$cfg_glpi["root_doc"]."/ajax/dropdown.php',{asynchronous:true, evalScripts:true, \n";
echo "           onComplete:function(request)\n";
echo "            {Element.hide('search_spinner_$myname$rand');}, \n";
echo "           onLoading:function(request)\n";
echo "            {Element.show('search_spinner_$myname$rand');},\n";
echo "           method:'post', parameters:'searchText=' + value+'&table=$table&myname=$myname'\n";
echo "})})\n";
echo "</script>\n";


echo "<div id='search_spinner_$myname$rand' style=' position:absolute;   filter:alpha(opacity=70); -moz-opacity:0.7; opacity: 0.7; display:none;'><img src=\"".$HTMLRel."pics/wait.png\" title='Processing....' alt='' /></div>\n";

$nb=0;
if ($cfg_glpi["use_ajax"])
	$nb=countElementsInTable($table);

if (!$cfg_glpi["use_ajax"]||$nb<$cfg_glpi["ajax_limit_count"]){
	echo "<script type='text/javascript' >\n";
	echo "document.getElementById('search_spinner_$myname$rand').style.visibility='hidden';";
	echo "Element.hide('search_$myname$rand');";
	echo "document.getElementById('search_$myname$rand').value='".$cfg_glpi["ajax_wildcard"]."';";
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
function dropdownValue($table,$myname,$value,$display_comments=1) {
	
	global $HTMLRel,$cfg_glpi,$lang;

	$rand=mt_rand();
	
	displaySearchTextAjaxDropdown($myname.$rand);
$name="------";
$comments="";
$limit_length=$cfg_glpi["dropdown_limit"];
if (empty($value)) $value=0;
if ($value>0){
	$tmpname=getDropdownName($table,$value,1);
	if ($tmpname["name"]!="&nbsp;"){
		$name=$tmpname["name"];
		$comments=$tmpname["comments"];
		$limit_length=max(strlen($name),$cfg_glpi["dropdown_limit"]);
	}
}

echo "<script type='text/javascript' >\n";
echo "   new Form.Element.Observer('search_$myname$rand', 1, \n";
echo "      function(element, value) {\n";
echo "      	new Ajax.Updater('results_$myname$rand','".$cfg_glpi["root_doc"]."/ajax/dropdownValue.php',{asynchronous:true, evalScripts:true, \n";
echo "           onComplete:function(request)\n";
echo "            {Element.hide('search_spinner_$myname$rand');}, \n";
echo "           onLoading:function(request)\n";
echo "            {Element.show('search_spinner_$myname$rand');},\n";
echo "           method:'post', parameters:'searchText='+value+'&value=$value&table=$table&myname=$myname&limit=$limit_length'\n";
echo "})})\n";
echo "</script>\n";

echo "<div id='search_spinner_$myname$rand' style=' position:absolute;  filter:alpha(opacity=70); -moz-opacity:0.7; opacity: 0.7; display:none;'><img src=\"".$HTMLRel."pics/wait.png\" title='Processing....' alt='Processing....' /></div>\n";

$nb=0;
if ($cfg_glpi["use_ajax"])
	$nb=countElementsInTable($table);

if (!$cfg_glpi["use_ajax"]||$nb<$cfg_glpi["ajax_limit_count"]){
	echo "<script type='text/javascript' >\n";
	echo "document.getElementById('search_spinner_$myname$rand').style.visibility='hidden';";
	echo "Element.hide('search_$myname$rand');";
	echo "document.getElementById('search_$myname$rand').value='".$cfg_glpi["ajax_wildcard"]."';";
	echo "</script>\n";
}



echo "<span id='results_$myname$rand'>\n";
echo "<select name='$myname'><option value='$value'>$name</option></select>\n";
echo "</span>\n";	

$comments_display="";
$comments_display2="";
if ($display_comments&&!empty($comments)) {
	$comments_display=" onmouseout=\"cleanhide('comments_$rand')\" onmouseover=\"cleandisplay('comments_$rand')\" ";
	$comments_display2="<span class='over_link' id='comments_$rand'>".nl2br($comments)."</span>";
}

$which="";
$dropdown_right=haveRight("dropdown","w");

if ($dropdown_right){
	if (ereg("glpi_dropdown_",$table)||ereg("glpi_type_",$table)){
		$search=array("/glpi_dropdown_/","/glpi_type_/");
		$replace=array("","");
		$which=preg_replace($search,$replace,$table);
	}
}
	
if (!empty($which))
	echo "<a href='".$cfg_glpi["root_doc"]."/front/setup.dropdowns.php?which=$which"."' target='_blank'>";

if (!empty($which)||($display_comments&&!empty($comments)))
	echo "<img alt='".$lang["common"][25]."' src='".$HTMLRel."pics/aide.png' $comments_display>";

if (!empty($which))
	echo "</a>";

	echo $comments_display2;

if ($table=="glpi_enterprises")
	echo getEnterpriseLinks($value);	

}



/**
* Make a select box without parameters value
*
*
* @param $table
* @param $myname
* @param $value
* @return nothing (print out an HTML select box)
* 
*/
function dropdownNoValue($table,$myname,$value) {
	// Make a select box without parameters value

	global $db,$cfg_glpi;

	$where="";
	if (in_array($table,$cfg_glpi["deleted_tables"]))
		$where="WHERE deleted='N'";
	if (in_array($table,$cfg_glpi["template_tables"]))
		$where.="AND is_template='0'";
		
	if (in_array($table,$cfg_glpi["dropdowntree_tables"]))
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
* @param $all
* @return nothing (print out an HTML select box)
*
*
*/
// $all =0 -> Nobody $all=1 -> All $all=-1-> nothing
function dropdownUsers($myname,$value,$right,$all=0,$display_comments=1) {
	// Make a select box with all glpi users

	global $HTMLRel,$cfg_glpi,$lang;

	$rand=mt_rand();
	
	displaySearchTextAjaxDropdown($myname.$rand);
	
	echo "<script type='text/javascript' >\n";
	echo "   new Form.Element.Observer('search_$myname$rand', 1, \n";
	echo "      function(element, value) {\n";
	echo "      	new Ajax.Updater('results_$myname$rand','".$cfg_glpi["root_doc"]."/ajax/dropdownUsers.php',{asynchronous:true, evalScripts:true, \n";
	echo "           onComplete:function(request)\n";
	echo "            {Element.hide('search_spinner_$myname$rand');}, \n";
	echo "           onLoading:function(request)\n";
	echo "            {Element.show('search_spinner_$myname$rand');},\n";
	echo "           method:'post', parameters:'searchText=' + value+'&value=$value&table=glpi_users&myname=$myname&all=$all&right=$right'\n";
	echo "})})\n";
	echo "</script>\n";

echo "<div id='search_spinner_$myname$rand' style=' position:absolute;   filter:alpha(opacity=70); -moz-opacity:0.7; opacity: 0.7; display:none;'><img src=\"".$HTMLRel."pics/wait.png\" title='Processing....' alt='Processing....' /></div>\n";

$nb=0;
if ($cfg_glpi["use_ajax"])
	$nb=countElementsInTable("glpi_users");

if (!$cfg_glpi["use_ajax"]||$nb<$cfg_glpi["ajax_limit_count"]){
	echo "<script type='text/javascript' >\n";
	echo "document.getElementById('search_spinner_$myname$rand').style.visibility='hidden';";
	echo "Element.hide('search_$myname$rand');";
	echo "document.getElementById('search_$myname$rand').value='".$cfg_glpi["ajax_wildcard"]."';";
	echo "</script>\n";
}


	echo "<span id='results_$myname$rand'>\n";
	if (!empty($value)&&$value>0){
		$user=getUserName($value,2);
		echo "<select name='$myname'><option value='$value'>".substr($user["name"],0,$cfg_glpi["dropdown_limit"])."</option></select>\n";
		echo "</span>\n";	
		if (!empty($user["comments"])&&$display_comments) {
			echo "<a href='".$user["link"]."'>";
			echo "<img alt='".$lang["common"][25]."' src='".$HTMLRel."pics/aide.png' onmouseout=\"cleanhide('comments_$rand')\" onmouseover=\"cleandisplay('comments_$rand')\">";
			echo "</a>";
			echo "<span class='over_link' id='comments_$rand'>".$user["comments"]."</span>";
		}
		
	} else {
		if ($all)
			echo "<select name='$myname'><option value='0'>[ ".$lang["search"][7]." ]</option></select>\n";
		else 
			echo "<select name='$myname'><option value='0'>[ Nobody ]</option></select>\n";
		echo "</span>\n";	
	}
	


}


/**
* Make a select box with all glpi users
*
*
* @param $myname
* @param $value
* @return nothing (print out an HTML select box)
* 
*/
function dropdownAllUsers($myname,$value,$display_comments=1) {
	global $lang;
	// Make a select box with all glpi users

	global $HTMLRel,$cfg_glpi;

	$rand=mt_rand();
	
	displaySearchTextAjaxDropdown($myname.$rand);

	echo "<script type='text/javascript' >\n";
	echo "   new Form.Element.Observer('search_$myname$rand', 1, \n";
	echo "      function(element, value) {\n";
	echo "      	new Ajax.Updater('results_$myname$rand','".$cfg_glpi["root_doc"]."/ajax/dropdownAllUsers.php',{asynchronous:true, evalScripts:true, \n";
	echo "           onComplete:function(request)\n";
	echo "            {Element.hide('search_spinner_$myname$rand');}, \n";
	echo "           onLoading:function(request)\n";
	echo "            {Element.show('search_spinner_$myname$rand');},\n";
	echo "           method:'post', parameters:'searchText=' + value+'&value=$value&table=glpi_users&myname=$myname'\n";
	echo "})})\n";
	echo "</script>\n";

echo "<div id='search_spinner_$myname$rand' style=' position:absolute;   filter:alpha(opacity=70); -moz-opacity:0.7; opacity: 0.7; display:none;'><img src=\"".$HTMLRel."pics/wait.png\" title='Processing....' alt='Processing....' /></div>\n";

$nb=0;
if ($cfg_glpi["use_ajax"])
	$nb=countElementsInTable("glpi_users");

if (!$cfg_glpi["use_ajax"]||$nb<$cfg_glpi["ajax_limit_count"]){
	echo "<script type='text/javascript' >\n";
	echo "document.getElementById('search_spinner_$myname$rand').style.visibility='hidden';";
	echo "Element.hide('search_$myname$rand');";
	echo "document.getElementById('search_$myname$rand').value='".$cfg_glpi["ajax_wildcard"]."';";
	echo "</script>\n";
}

	echo "<span id='results_$myname$rand'>\n";
	if (!empty($value)&&$value>0){
		$user=getUserName($value,2);
		echo "<select name='$myname'><option value='$value'>".substr($user["name"],0,$cfg_glpi["dropdown_limit"])."</option></select>\n";
		echo "</span>\n";	
		if (!empty($user["comments"])&&$display_comments) {
			echo "<a href='".$user["link"]."'>";
			echo "<img alt='".$lang["common"][25]."' src='".$HTMLRel."pics/aide.png' onmouseout=\"cleanhide('comments_$rand')\" onmouseover=\"cleandisplay('comments_$rand')\">";
			echo "</a>";
			echo "<span class='over_link' id='comments_$rand'>".$user["comments"]."</span>";
		}
		
		}
	else {
		echo "<select name='$myname'><option value='0'>[ Nobody ]</option></select>\n";
		echo "</span>\n";	
	}
	

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
function dropdownUsersID($myname,$value,$right) {
	// Make a select box with all glpi users

	dropdownUsers($myname,$value,$right);
}

/**
* Get the value of a dropdown 
*
*
* Returns the value of the dropdown from $table with ID $id.
*
* @param $table
* @param $id
* @param $withcomments
* @return string the value of the dropdown or &nbsp; if not exists
*/
function getDropdownName($table,$id,$withcomments=0) {
	global $db,$cfg_glpi;
	
	if (in_array($table,$cfg_glpi["dropdowntree_tables"])){
		return getTreeValueCompleteName($table,$id,$withcomments);

	} else	{
	
		$name = "";
		$comments = "";
		$query = "select * from ". $table ." where ID = '". $id ."'";
		if ($result = $db->query($query))
		if($db->numrows($result) != 0) {
			$data=$db->fetch_assoc($result);
			$name = $data["name"];
			if (isset($data["comments"]))
				$comments = $data["comments"];
			if ($table=="glpi_dropdown_netpoint")
				$name .= " (".getDropdownName("glpi_dropdown_locations",$db->result($result,0,"location")).")";
			
		}
	}
	if (empty($name)) $name="&nbsp;";
	if ($withcomments) return array("name"=>$name,"comments"=>$comments);
	else return $name;
}

/**
* Make a select box with all glpi users in tracking table
*
*
*
* @param $value
* @param $myname
* @param $champ
* @param $display_comments
* @return nothing (print out an HTML select box)
*/

function dropdownUsersTracking($myname,$value,$champ,$display_comments=1) {
	global $HTMLRel,$cfg_glpi,$lang;

	$rand=mt_rand();
	
	displaySearchTextAjaxDropdown($myname.$rand);

	echo "<script type='text/javascript' >\n";
	echo "   new Form.Element.Observer('search_$myname$rand', 1, \n";
	echo "      function(element, value) {\n";
	echo "      	new Ajax.Updater('results_$myname$rand','".$cfg_glpi["root_doc"]."/ajax/dropdownUsersTracking.php',{asynchronous:true, evalScripts:true, \n";
	echo "           onComplete:function(request)\n";
	echo "            {Element.hide('search_spinner_$myname$rand');}, \n";
	echo "           onLoading:function(request)\n";
	echo "            {Element.show('search_spinner_$myname$rand');},\n";
	echo "           method:'post', parameters:'searchText=' + value+'&value=$value&champ=$champ&myname=$myname'\n";
	echo "})})\n";
	echo "</script>\n";

	echo "<div id='search_spinner_$myname$rand' style=' position:absolute;   filter:alpha(opacity=70); -moz-opacity:0.7; opacity: 0.7; display:none;'><img src=\"".$HTMLRel."pics/wait.png\" title='Processing....' alt='Processing....' /></div>\n";

$nb=0;
if ($cfg_glpi["use_ajax"])
	$nb=countElementsInTable("glpi_users");

if (!$cfg_glpi["use_ajax"]||$nb<$cfg_glpi["ajax_limit_count"]){
	echo "<script type='text/javascript' >\n";
	echo "document.getElementById('search_spinner_$myname$rand').style.visibility='hidden';";
	echo "Element.hide('search_$myname$rand');";
	echo "document.getElementById('search_$myname$rand').value='".$cfg_glpi["ajax_wildcard"]."';";
	echo "</script>\n";
}

	echo "<span id='results_$myname$rand'>\n";
	if (!empty($value)&&$value>0){
		$user=getUserName($value,2);
		echo "<select name='$myname'><option value='$value'>".substr($user["name"],0,$cfg_glpi["dropdown_limit"])."</option></select>\n";
		echo "</span>\n";	
		if (!empty($user["comments"])&&$display_comments) {
			echo "<a href='".$user["link"]."'>";
			echo "<img alt='".$lang["common"][25]."' src='".$HTMLRel."pics/aide.png' onmouseout=\"cleanhide('comments_$rand')\" onmouseover=\"cleandisplay('comments_$rand')\">";
			echo "</a>";
			echo "<span class='over_link' id='comments_$rand'>".$user["comments"]."</span>";
		}

		}
	else {
		echo "<select name='$myname'><option value='0'>[ ".$lang["search"][7]." ]</option></select>\n";
		echo "</span>\n";	
	}
	
	
}

/**
* 
* Make a select box for icons
*
*
* @param $value
* @param $myname
* @param $store_path
* @return nothing (print out an HTML select box)
*/
function dropdownIcons($myname,$value,$store_path){
global $HTMLRel,$lang;
if (is_dir($store_path)){
if ($dh = opendir($store_path)) {
	echo "<select name=\"$myname\">";
	echo "<option value=''>-----</option>";
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


/**
* 
* Make a select box for device type
*
*
* @param $name name of the select box
* @param $device_type default device type
* @param $soft with softwares ?
* @param $cart with cartridges ?
* @param $cons with consumables ?
* @return nothing (print out an HTML select box)
*/
function dropdownDeviceType($name,$device_type,$soft=1,$cart=1,$cons=1){
global $lang;
echo "<select name='$name'>\n";
	echo "<option value='0'>-----</option>\n";
    	echo "<option value='".COMPUTER_TYPE."' ".(($device_type==COMPUTER_TYPE)?" selected":"").">".$lang["help"][25]."</option>\n";
	echo "<option value='".NETWORKING_TYPE."' ".(($device_type==NETWORKING_TYPE)?" selected":"").">".$lang["help"][26]."</option>\n";
	echo "<option value='".PRINTER_TYPE."' ".(($device_type==PRINTER_TYPE)?" selected":"").">".$lang["help"][27]."</option>\n";
	echo "<option value='".MONITOR_TYPE."' ".(($device_type==MONITOR_TYPE)?" selected":"").">".$lang["help"][28]."</option>\n";
	echo "<option value='".PERIPHERAL_TYPE."' ".(($device_type==PERIPHERAL_TYPE)?" selected":"").">".$lang["help"][29]."</option>\n";
	echo "<option value='".PHONE_TYPE."' ".(($device_type==PHONE_TYPE)?" selected":"").">".$lang["help"][35]."</option>\n";

	if ($soft)
		echo "<option value='".SOFTWARE_TYPE."' ".(($device_type==SOFTWARE_TYPE)?" selected":"").">".$lang["help"][31]."</option>\n";
	if ($cart)
	echo "<option value='".CARTRIDGE_TYPE."' ".(($device_type==CARTRIDGE_TYPE)?" selected":"").">".$lang["Menu"][21]."</option>\n";
	if ($cons)
	echo "<option value='".CONSUMABLE_TYPE."' ".(($device_type==CONSUMABLE_TYPE)?" selected":"").">".$lang["Menu"][32]."</option>\n";
	echo "<option value='".CONTACT_TYPE."' ".(($device_type==CONTACT_TYPE)?" selected":"").">".$lang["Menu"][22]."</option>\n";
	echo "<option value='".ENTERPRISE_TYPE."' ".(($device_type==ENTERPRISE_TYPE)?" selected":"").">".$lang["Menu"][23]."</option>\n";
	echo "<option value='".CONTRACT_TYPE."' ".(($device_type==CONTRACT_TYPE)?" selected":"").">".$lang["Menu"][25]."</option>\n";
	echo "</select>\n";


}



/**
* 
*Make a select box for all items
*
*
* @param $myname
* @param $value_type
* @param $value
* @param $withenterprise
* @param $withcartridge
* @param $withconsumable
* @param $withcontracts
* @return nothing (print out an HTML select box)
*/
function dropdownAllItems($myname,$value_type=0,$value=0,$withenterprise=0,$withcartridge=0,$withconsumable=0,$withcontracts=0) {
	global $db,$lang,$HTMLRel,$cfg_glpi;
	
	$items=array(
	COMPUTER_TYPE=>"glpi_computers",
	NETWORKING_TYPE=>"glpi_networking",
	PRINTER_TYPE=>"glpi_printers",
	MONITOR_TYPE=>"glpi_monitors",
	PERIPHERAL_TYPE=>"glpi_peripherals",
	SOFTWARE_TYPE=>"glpi_software",
	PHONE_TYPE=>"glpi_phones",
	);

	if ($withenterprise==1) $items[ENTERPRISE_TYPE]="glpi_enterprises";
	if ($withcartridge==1) $items[CARTRIDGE_TYPE]="glpi_cartridges_type";
	if ($withconsumable==1) $items[CONSUMABLE_TYPE]="glpi_consumables_type";
	if ($withcontracts==1) $items[CONTRACT_TYPE]="glpi_contracts";
	
	
	$rand=mt_rand();
	echo "<table border='0'><tr><td>\n";
	echo "<select name='type' id='item_type$rand'>\n";
	echo "<option value='0'>-----</option>\n";
	echo "<option value='".COMPUTER_TYPE."'>".$lang["Menu"][0]."</option>\n";
	echo "<option value='".NETWORKING_TYPE."'>".$lang["Menu"][1]."</option>\n";
	echo "<option value='".PRINTER_TYPE."'>".$lang["Menu"][2]."</option>\n";
	echo "<option value='".MONITOR_TYPE."'>".$lang["Menu"][3]."</option>\n";
	echo "<option value='".PERIPHERAL_TYPE."'>".$lang["Menu"][16]."</option>\n";
	echo "<option value='".SOFTWARE_TYPE."'>".$lang["Menu"][4]."</option>\n";
	echo "<option value='".PHONE_TYPE."'>".$lang["Menu"][34]."</option>\n";
	if ($withenterprise==1) echo "<option value='".ENTERPRISE_TYPE."'>".$lang["Menu"][23]."</option>\n";
	if ($withcartridge==1) echo "<option value='".CARTRIDGE_TYPE."'>".$lang["Menu"][21]."</option>\n";
	if ($withconsumable==1) echo "<option value='".CONSUMABLE_TYPE."'>".$lang["Menu"][32]."</option>\n";
	if ($withcontracts==1) echo "<option value='".CONTRACT_TYPE."'>".$lang["Menu"][25]."</option>\n";
	echo "</select>\n";
	
	
	echo "<script type='text/javascript' >\n";
	echo "   new Form.Element.Observer('item_type$rand', 1, \n";
	echo "      function(element, value) {\n";
	echo "      	new Ajax.Updater('show_$myname$rand','".$cfg_glpi["root_doc"]."/ajax/dropdownAllItems.php',{asynchronous:true, evalScripts:true, \n";	echo "           onComplete:function(request)\n";
	echo "            {Element.hide('search_spinner_$myname$rand');}, \n";
	echo "           onLoading:function(request)\n";
	echo "            {Element.show('search_spinner_$myname$rand');},\n";
	echo "           method:'post', parameters:'idtable='+value+'&myname=$myname&value=$value'\n";
	echo "})})\n";
	echo "</script>\n";
	
	echo "<div id='search_spinner_$myname$rand' style=' position:absolute;   filter:alpha(opacity=70); -moz-opacity:0.7; opacity: 0.7; display:none;'><img src=\"".$HTMLRel."pics/wait.png\" title='Processing....' alt='Processing....' /></div>\n";
	echo "</td><td>\n"	;
	echo "<span id='show_$myname$rand'>&nbsp;</span>\n";
	echo "</td></tr></table>\n";

	if ($value>0){
		echo "<script type='text/javascript' >\n";
		echo "document.getElementById('search_spinner_$myname$rand').style.visibility='hidden';";
		echo "document.getElementById('item_type$rand').value='".$value_type."';";
		echo "</script>\n";
	}

}

/**
* Make a select box for a boolean choice (Yes/No)
*
*
*
* @param $name select name
* @param $value preselected value.
* @return nothing (print out an HTML select box)
*/
function dropdownYesNo($name,$value){
	global $lang;
	echo "<select name='$name'>\n";
	echo "<option value='N' ".($value=='N'?" selected ":"").">".$lang["choice"][0]."</option>\n";
	echo "<option value='Y' ".($value=='Y'?" selected ":"").">".$lang["choice"][1]."</option>\n";
	echo "</select>\n";	
}	


/**
* Make a select box for a boolean choice (Yes/No)
*
*
*
* @param $name select name
* @param $value preselected value.
* @return nothing (print out an HTML select box)
*/
function dropdownYesNoInt($name,$value){
	global $lang;
	echo "<select name='$name'>\n";
	echo "<option value='0' ".(!$value?" selected ":"").">".$lang["choice"][0]."</option>\n";
	echo "<option value='1' ".($value?" selected ":"").">".$lang["choice"][1]."</option>\n";
	echo "</select>\n";	
}	


/**
* Make a select box for a None Read Write choice
*
*
*
* @param $name select name
* @param $value preselected value.
* @return nothing (print out an HTML select box)
*/
function dropdownNoneReadWrite($name,$value,$none=1,$read=1,$write=1){
	global $lang;
	echo "<select name='$name'>\n";
	if ($none)
		echo "<option value='' ".(empty($value)?" selected ":"").">".$lang["profiles"][12]."</option>\n";
	if ($read)
		echo "<option value='r' ".($value=='r'?" selected ":"").">".$lang["profiles"][10]."</option>\n";
	if ($write)
		echo "<option value='w' ".($value=='w'?" selected ":"").">".$lang["profiles"][11]."</option>\n";
	echo "</select>\n";	
}	

/**
* Make a select box for Tracking device type
*
*
*
* @param $myname select name
* @param $value preselected value.
* @return nothing (print out an HTML select box)
*/
function dropdownTrackingDeviceType($myname,$value){
	global $lang,$HTMLRel,$cfg_glpi;
	
	$rand=mt_rand();
	
	echo "<select id='search_$myname$rand' name='$myname'>\n";

	echo "<option value='0' ".(($value==0)?" selected":"").">".$lang["help"][30]."</option>\n";
	echo "<option value='".COMPUTER_TYPE."' ".(($value==COMPUTER_TYPE)?" selected":"").">".$lang["help"][25]."</option>\n";
	echo "<option value='".NETWORKING_TYPE."' ".(($value==NETWORKING_TYPE)?" selected":"").">".$lang["help"][26]."</option>\n";
	echo "<option value='".PRINTER_TYPE."' ".(($value==PRINTER_TYPE)?" selected":"").">".$lang["help"][27]."</option>\n";
	echo "<option value='".MONITOR_TYPE."' ".(($value==MONITOR_TYPE)?" selected":"").">".$lang["help"][28]."</option>\n";
	echo "<option value='".PERIPHERAL_TYPE."' ".(($value==PERIPHERAL_TYPE)?" selected":"").">".$lang["help"][29]."</option>\n";
	echo "<option value='".SOFTWARE_TYPE."' ".(($value==SOFTWARE_TYPE)?" selected":"").">".$lang["help"][31]."</option>\n";
	echo "<option value='".PHONE_TYPE."' ".(($value==PHONE_TYPE)?" selected":"").">".$lang["help"][35]."</option>\n";
	echo "</select>\n";

echo "<script type='text/javascript' >\n";
echo "   new Form.Element.Observer('search_$myname$rand', 1, \n";
echo "      function(element, value) {\n";
echo "      	new Ajax.Updater('results_$myname$rand','".$cfg_glpi["root_doc"]."/ajax/dropdownTrackingDeviceType.php',{asynchronous:true, evalScripts:true, \n";
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

/**
* Make a select box for connections
*
*
*
* @param $type
* @param $myname
* @return nothing (print out an HTML select box)
*/
function dropdownConnect($type,$myname,$onlyglobal=0) {


	global $HTMLRel,$cfg_glpi;

		$items=array(
			COMPUTER_TYPE=>"glpi_computers",
			PRINTER_TYPE=>"glpi_printers",
			MONITOR_TYPE=>"glpi_monitors",
			PERIPHERAL_TYPE=>"glpi_peripherals",
			PHONE_TYPE=>"glpi_phones",
		);

	$rand=mt_rand();

	displaySearchTextAjaxDropdown($myname.$rand);

echo "<script type='text/javascript' >\n";
echo "   new Form.Element.Observer('search_$myname$rand', 1, \n";
echo "      function(element, value) {\n";
echo "      	new Ajax.Updater('results_$myname$rand','".$cfg_glpi["root_doc"]."/ajax/dropdownConnect.php',{asynchronous:true, evalScripts:true, \n";
echo "           onComplete:function(request)\n";
echo "            {Element.hide('search_spinner_$myname$rand');}, \n";
echo "           onLoading:function(request)\n";
echo "            {Element.show('search_spinner_$myname$rand');},\n";
echo "           method:'post', parameters:'searchText=' + value+'&idtable=$type&myname=$myname&onlyglobal=$onlyglobal'\n";
echo "})})\n";
echo "</script>\n";

echo "<div id='search_spinner_$myname$rand' style=' position:absolute;   filter:alpha(opacity=70); -moz-opacity:0.7; opacity: 0.7; display:none;'><img src=\"".$HTMLRel."pics/wait.png\" title='Processing....' alt='' /></div>\n";

$nb=0;
if ($cfg_glpi["use_ajax"])
	$nb=countElementsInTable($items[$type]);

if (!$cfg_glpi["use_ajax"]||$nb<$cfg_glpi["ajax_limit_count"]){
	echo "<script type='text/javascript' >\n";
	echo "document.getElementById('search_spinner_$myname$rand').style.visibility='hidden';";
	echo "Element.hide('search_$myname$rand');";
	echo "document.getElementById('search_$myname$rand').value='".$cfg_glpi["ajax_wildcard"]."';";
	echo "</script>\n";
}


echo "<span id='results_$myname$rand'>\n";
echo "<select name='$myname'><option value='0'>------</option></select>\n";
echo "</span>\n";	
}


/**
* Make a select box for  connected port
*
*
* @param $ID
* @param $type
* @param $myname
* @return nothing (print out an HTML select box)
*/
function dropdownConnectPort($ID,$type,$myname) {


	global $db,$lang,$HTMLRel,$cfg_glpi;
	
	$items=array(
	COMPUTER_TYPE=>"glpi_computers",
	NETWORKING_TYPE=>"glpi_networking",
	PRINTER_TYPE=>"glpi_printers",
	PERIPHERAL_TYPE=>"glpi_peripherals",
	PHONE_TYPE=>"glpi_phones",
	);

	
	$rand=mt_rand();
	echo "<select name='type' id='item_type$rand'>\n";
	echo "<option value='0'>-----</option>\n";
	echo "<option value='".COMPUTER_TYPE."'>".$lang["Menu"][0]."</option>\n";
	echo "<option value='".NETWORKING_TYPE."'>".$lang["Menu"][1]."</option>\n";
	echo "<option value='".PRINTER_TYPE."'>".$lang["Menu"][2]."</option>\n";
	echo "<option value='".PERIPHERAL_TYPE."'>".$lang["Menu"][16]."</option>\n";
	echo "<option value='".PHONE_TYPE."'>".$lang["Menu"][34]."</option>\n";
	echo "</select>\n";
	
	
	echo "<script type='text/javascript' >\n";
	echo "   new Form.Element.Observer('item_type$rand', 1, \n";
	echo "      function(element, value) {\n";
	echo "      	new Ajax.Updater('show_$myname$rand','".$cfg_glpi["root_doc"]."/ajax/dropdownConnectPortDeviceType.php',{asynchronous:true, evalScripts:true, \n";	
	echo "           onComplete:function(request)\n";
	echo "            {Element.hide('search_spinner_$myname$rand');}, \n";
	echo "           onLoading:function(request)\n";
	echo "            {Element.show('search_spinner_$myname$rand');Element.hide('not_connected_display$ID');},\n";
	echo "           method:'post', parameters:'current=$ID&type='+value+'&myname=$myname'\n";
	echo "})})\n";
	echo "</script>\n";
	
	echo "<div id='search_spinner_$myname$rand' style=' position:absolute;   filter:alpha(opacity=70); -moz-opacity:0.7; opacity: 0.7; display:none;'><img src=\"".$HTMLRel."pics/wait.png\" title='Processing....' alt='Processing....' /></div>\n";
	echo "<span id='show_$myname$rand'>&nbsp;</span>\n";


}

/**
* Make a select box for  software to install
*
*
* @param $myname
* @param $withtemplate
* @return nothing (print out an HTML select box)
*/
function dropdownSoftwareToInstall($myname,$withtemplate) {
	global $db,$lang,$HTMLRel,$cfg_glpi;
	
	$rand=mt_rand();

	displaySearchTextAjaxDropdown($myname.$rand);

	echo "<script type='text/javascript' >\n";
	echo "   new Form.Element.Observer('search_$myname$rand', 1, \n";
	echo "      function(element, value) {\n";
	echo "      	new Ajax.Updater('results_$myname$rand','".$cfg_glpi["root_doc"]."/ajax/dropdownSelectSoftware.php',{asynchronous:true, evalScripts:true, \n";
	echo "           onComplete:function(request)\n";
	echo "            {Element.hide('search_spinner_$myname$rand');}, \n";
	echo "           onLoading:function(request)\n";
	echo "            {Element.show('search_spinner_$myname$rand');},\n";
	echo "           method:'post', parameters:'searchSoft=' + value+'&myname=$myname&withtemplate=$withtemplate'\n";
	echo "})})\n";
	echo "</script>\n";

	echo "<div id='search_spinner_$myname$rand' style=' position:absolute;   filter:alpha(opacity=70); -moz-opacity:0.7; opacity: 0.7; display:none;'><img src=\"".$HTMLRel."pics/wait.png\" title='Processing....' alt='' /></div>\n";


	$nb=0;
	if ($cfg_glpi["use_ajax"])
		$nb=countElementsInTable("glpi_software");

	if (!$cfg_glpi["use_ajax"]||$nb<$cfg_glpi["ajax_limit_count"]){
		echo "<script type='text/javascript' >\n";
		echo "document.getElementById('search_spinner_$myname$rand').style.visibility='hidden';";
		echo "Element.hide('search_$myname$rand');";
		echo "document.getElementById('search_$myname$rand').value='".$cfg_glpi["ajax_wildcard"]."';";
		echo "</script>\n";
	}


	echo "<span id='results_$myname$rand'>\n";
	echo "<select name='$myname'><option value='0'>------</option></select>\n";
	echo "</span>\n";	
	
	
	
}

/**
* Show div with auto completion
*
* @param $myname
* @param $table
* @param $field
* @param $value
* @param $size
* @param $option
* @return nothing (print out an HTML div)
*/
function autocompletionTextField($myname,$table,$field,$value='',$size=20,$option=''){
	global $HTMLRel,$cfg_glpi;

	if ($cfg_glpi["use_ajax"]&&$cfg_glpi["ajax_autocompletion"]){
		$rand=mt_rand();
		echo "<input $option id='textfield_$myname$rand' type='text' name='$myname' value=\"".ereg_replace("\"","''",$value)."\" size='$size'>\n";
		echo "<div id='textfieldupdate_$myname$rand' style='display:none;border:1px solid black;background-color:white;'></div>\n";
		echo "<script type='text/javascript' language='javascript' charset='utf-8'>";
	    echo "new Ajax.Autocompleter('textfield_$myname$rand','textfieldupdate_$myname$rand','".$HTMLRel."/ajax/autocompletion.php',{parameters:'table=$table&field=$field&myname=$myname'});";
		echo "</script>";
	}	else {
		echo "<input $option type='text' name='$myname' value=\"".ereg_replace("\"","''",$value)."\" size='$size'>\n";
	}
}


/**
* Make a select box form  for device type 
*
*
* @param $target
* @param $cID
* @param $withtemplate
* @return nothing (print out an HTML select box)
*/
function device_selecter($target,$cID,$withtemplate='') {
	global $lang,$HTMLRel,$cfg_glpi;

	if (!haveRight("computer","w")) return false;

	if(!empty($withtemplate) && $withtemplate == 2) {
	//do nothing
	} else {
		echo "<table class='tab_cadre_fixe'>";
		echo "<tr  class='tab_bg_1'><td colspan='2' align='right'>";
		echo $lang["devices"][0].":";
		echo "</td>";
		echo "<td colspan='63'>"; 
		echo "<form action=\"$target\" method=\"post\">";

		$rand=mt_rand();

		echo "<select name=\"new_device_type\" id='device$rand'>";
		
		echo "<option value=\"-1\">-----</option>";
		echo "<option value=\"".MOBOARD_DEVICE."\">".getDictDeviceLabel(MOBOARD_DEVICE)."</option>";
		echo "<option value=\"".HDD_DEVICE."\">".getDictDeviceLabel(HDD_DEVICE)."</option>";
		echo "<option value=\"".GFX_DEVICE."\">".getDictDeviceLabel(GFX_DEVICE)."</option>";
		echo "<option value=\"".NETWORK_DEVICE."\">".getDictDeviceLabel(NETWORK_DEVICE)."</option>";
		echo "<option value=\"".PROCESSOR_DEVICE."\">".getDictDeviceLabel(PROCESSOR_DEVICE)."</option>";
		echo "<option value=\"".SND_DEVICE."\">".getDictDeviceLabel(SND_DEVICE)."</option>";
		echo "<option value=\"".RAM_DEVICE."\">".getDictDeviceLabel(RAM_DEVICE)."</option>";
		echo "<option value=\"".DRIVE_DEVICE."\">".getDictDeviceLabel(DRIVE_DEVICE)."</option>";
		echo "<option value=\"".CONTROL_DEVICE."\">".getDictDeviceLabel(CONTROL_DEVICE)."</option>";
		echo "<option value=\"".PCI_DEVICE."\">".getDictDeviceLabel(PCI_DEVICE)."</option>";
		echo "<option value=\"".CASE_DEVICE."\">".getDictDeviceLabel(CASE_DEVICE)."</option>";
		echo "<option value=\"".POWER_DEVICE."\">".getDictDeviceLabel(POWER_DEVICE)."</option>";
		echo "</select>";

		echo "<script type='text/javascript' >\n";
		echo "   new Form.Element.Observer('device$rand', 1, \n";
		echo "      function(element, value) {\n";
		echo "      	new Ajax.Updater('showdevice$rand','".$cfg_glpi["root_doc"]."/ajax/dropdownDevice.php',{asynchronous:true, evalScripts:true, \n";	echo "           onComplete:function(request)\n";
		echo "            {Element.hide('search_spinner_device$rand');}, \n";
		echo "           onLoading:function(request)\n";
		echo "            {Element.show('search_spinner_device$rand');},\n";
		echo "           method:'post', parameters:'idtable='+value+'&myname=new_device_id'\n";
		echo "})})\n";
		echo "</script>\n";
	
		echo "<div id='search_spinner_device$rand' style=' position:absolute;   filter:alpha(opacity=70); -moz-opacity:0.7; opacity: 0.7; display:none;'><img src=\"".$HTMLRel."pics/wait.png\" title='Processing....' alt='Processing....' /></div>\n";
		echo "<span id='showdevice$rand'>&nbsp;</span>\n";


		echo "<input type=\"hidden\" name=\"withtemplate\" value=\"".$withtemplate."\" >";
		echo "<input type=\"hidden\" name=\"connect_device\" value=\"".true."\" >";
		echo "<input type=\"hidden\" name=\"cID\" value=\"".$cID."\" >";
		echo "<input type=\"submit\" class ='submit' value=\"".$lang["buttons"][2]."\" >";
		echo "</form>";
		echo "</td>";
		echo "</tr></table>";
		}
}


function displaySearchTextAjaxDropdown($id){
	global $cfg_glpi;
	echo "<input type='text' ondblclick=\"document.getElementById('search_$id').value='".$cfg_glpi["ajax_wildcard"]."';\" id='search_$id' name='____data_$id' size='4'>\n";

}

function dropdownMassiveAction($device_type,$deleted){
	global $lang,$HTMLRel,$cfg_glpi;

		echo "<select name=\"massiveaction\" id='massiveaction'>";
		
		echo "<option value=\"-1\" selected>-----</option>";
		if ($deleted=="Y"){
			echo "<option value=\"purge\">".$lang["buttons"][22]."</option>";
			echo "<option value=\"restore\">".$lang["buttons"][21]."</option>";
		} else {
			echo "<option value=\"delete\">".$lang["buttons"][6]."</option>";
			echo "<option value=\"update\">".$lang["buttons"][14]."</option>";
		}
		echo "</select>";

	echo "<script type='text/javascript' >\n";
	echo "   new Form.Element.Observer('massiveaction', 1, \n";
	echo "      function(element, value) {\n";
	echo "      	new Ajax.Updater('show_massiveaction','".$cfg_glpi["root_doc"]."/ajax/dropdownMassiveAction.php',{asynchronous:true, evalScripts:true, \n";	echo "           onComplete:function(request)\n";
	echo "            {Element.hide('search_spinner_massiveaction');}, \n";
	echo "           onLoading:function(request)\n";
	echo "            {Element.show('search_spinner_massiveaction');},\n";
	echo "           method:'post', parameters:'deleted=$deleted&action='+value+'&type=$device_type'\n";
	echo "})})\n";
	echo "</script>\n";
	
	echo "<div id='search_spinner_massiveaction' style=' position:absolute;   filter:alpha(opacity=70); -moz-opacity:0.7; opacity: 0.7; display:none;'><img src=\"".$HTMLRel."pics/wait.png\" title='Processing....' alt='Processing....' /></div>\n";
	echo "<span id='show_massiveaction'>&nbsp;</span>\n";
	


}

?>