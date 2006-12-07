<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.

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

	return dropdownValue($table,$myname,0,1);
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
 * @param $display_comments display the comments near the dropdown
 * @return nothing (display the select box)
 *
 */
function dropdownValue($table,$myname,$value=0,$display_comments=1) {

	global $HTMLRel,$cfg_glpi,$lang,$phproot,$db;

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
	echo "           method:'post', parameters:'searchText='+value+'&value=$value&table=$table&myname=$myname&limit=$limit_length&comments=$display_comments&rand=$rand'\n";
	echo "})})\n";
	echo "</script>\n";

	echo "<div id='search_spinner_$myname$rand' style=' position:absolute;  filter:alpha(opacity=70); -moz-opacity:0.7; opacity: 0.7; display:none;'><img src=\"".$HTMLRel."pics/wait.png\" title='Processing....' alt='Processing....' /></div>\n";

	$nb=0;
	if ($cfg_glpi["use_ajax"]){
		$nb=countElementsInTable($table);
	}

	if (!$cfg_glpi["use_ajax"]||$nb<$cfg_glpi["ajax_limit_count"]){
		echo "<script type='text/javascript' >\n";
		echo "document.getElementById('search_spinner_$myname$rand').style.visibility='hidden';";
		echo "Element.hide('search_$myname$rand');";
		echo "</script>\n";
	}

	echo "<span id='results_$myname$rand'>\n";
	if (!$cfg_glpi["use_ajax"]||$nb<$cfg_glpi["ajax_limit_count"]){
		$_POST["myname"]=$myname;
		$_POST["table"]=$table;
		$_POST["value"]=$value;
		$_POST["rand"]=$rand;
		$_POST["comments"]=$display_comments;
		$_POST["limit"]=$limit_length;
		$_POST["searchText"]=$cfg_glpi["ajax_wildcard"];
		include ($phproot."/ajax/dropdownValue.php");
	} else {
		echo "<select name='$myname'><option value='$value'>$name</option></select>\n";
	}
	echo "</span>\n";	

	$comments_display="";
	$comments_display2="";
	if ($display_comments) {
		$comments_display=" onmouseout=\"cleanhide('comments_$myname$rand')\" onmouseover=\"cleandisplay('comments_$myname$rand')\" ";
		$comments_display2="<span class='over_link' id='comments_$myname$rand'>".nl2br($comments)."</span>";
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

	if ($display_comments){
		echo "<img alt='".$lang["common"][25]."' src='".$HTMLRel."pics/aide.png' $comments_display ";
		if ($dropdown_right&&!empty($which)) echo " style='cursor:pointer;'  onClick=\"window.open('".$cfg_glpi["root_doc"]."/front/popup.php?popup=dropdown&amp;which=$which"."' ,'mywindow', 'height=400, width=1000, top=100, left=100, scrollbars=yes' )\"";
		echo ">";
		echo $comments_display2;
	}

	if ($table=="glpi_enterprises"){
		echo getEnterpriseLinks($value);	
	}

	return $rand;
}



/**
 * Make a select box without parameters value
 *
 *
* @param $table the dropdown table from witch we want values on the select
 * @param $myname the name of the HTML select
 * @param $value the preselected value we want
 * @return nothing (print out an HTML select box)
 * 
 */
function dropdownNoValue($table,$myname,$value) {
	// Make a select box without parameters value

	global $db,$cfg_glpi;

	$where="";
	if (in_array($table,$cfg_glpi["deleted_tables"])){
		$where="WHERE deleted='N'";
	}
	if (in_array($table,$cfg_glpi["template_tables"])){
		$where.="AND is_template='0'";
	}

	if (in_array($table,$cfg_glpi["dropdowntree_tables"])){
		$query = "SELECT ID FROM $table $where ORDER BY completename";
	}
	else {
		$query = "SELECT ID FROM $table $where ORDER BY name";
	}

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
 * @param $myname select name
 * @param $value default value
 * @param $right limit user who have specific right : interface -> central ; ID -> only current user ; all -> all users ; sinon specific right like show_ticket, create_ticket....
 * @param $all Nobody or All display for none selected
 * @param $display_comments display comments near the dropdown
 * @param $helpdesk_ajax use ajax for helpdesk auto update (mail device_type)
 * @return nothing (print out an HTML select box)
 *
 *
 */
// $all =0 -> Nobody $all=1 -> All $all=-1-> nothing
function dropdownUsers($myname,$value,$right,$all=0,$display_comments=1,$helpdesk_ajax=0) {
	// Make a select box with all glpi users

	global $HTMLRel,$cfg_glpi,$lang,$phproot,$db;

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
	echo "           method:'post', parameters:'searchText=' + value+'&value=$value&myname=$myname&all=$all&right=$right&comments=$display_comments&rand=$rand&helpdesk_ajax=$helpdesk_ajax'\n";
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
		//echo "document.getElementById('search_$myname$rand').value='".$cfg_glpi["ajax_wildcard"]."';";
		echo "</script>\n";
	}

	$default_display="";
	$comments_display="";

	$user=getUserName($value,2);

	$default_display="<select id='dropdown_".$myname.$rand."' name='$myname'><option value='$value'>".substr($user["name"],0,$cfg_glpi["dropdown_limit"])."</option></select>\n";
	if ($display_comments) {
		$comments_display="<a href='".$user["link"]."'>";
		$comments_display.="<img alt='".$lang["common"][25]."' src='".$HTMLRel."pics/aide.png' onmouseout=\"cleanhide('comments_$myname$rand')\" onmouseover=\"cleandisplay('comments_$myname$rand')\">";
		$comments_display.="</a>";
		$comments_display.="<span class='over_link' id='comments_$myname$rand'>".$user["comments"]."</span>";
	}

	echo "<span id='results_$myname$rand'>\n";
	if (!$cfg_glpi["use_ajax"]||$nb<$cfg_glpi["ajax_limit_count"]){
		$_POST["myname"]=$myname;
		$_POST["all"]=$all;
		$_POST["value"]=$value;
		$_POST["right"]=$right;
		$_POST["rand"]=$rand;
		$_POST["comments"]=$display_comments;
		$_POST["searchText"]=$cfg_glpi["ajax_wildcard"];
		$_POST["helpdesk_ajax"]=$helpdesk_ajax;

		include ($phproot."/ajax/dropdownUsers.php");
	} else {
		if (!empty($value)&&$value>0){
			echo $default_display;
		} else {
			if ($all)
				echo "<select name='$myname'><option value='0'>[ ".$lang["search"][7]." ]</option></select>\n";
			else 
				echo "<select name='$myname'><option value='0'>[ Nobody ]</option></select>\n";
		}
	}

	echo "</span>\n";

	echo $comments_display;

	return $rand;
}


/**
 * Make a select box with all glpi users
 *
 *
* @param $myname select name
 * @param $value default value
 * @param $display_comments display comments near the dropdown
 * @param $helpdesk_ajax use ajax for helpdesk auto update (mail device_type)
 * @return nothing (print out an HTML select box)
 * 
 */
function dropdownAllUsers($myname,$value=0,$display_comments=1,$helpdesk_ajax=0) {
	return dropdownUsers($myname,$value,"all",0,$display_comments,$helpdesk_ajax);
}


/**
 * Make a select box with all glpi users where select key = ID
 *
 *
 *
* @param $myname select name
 * @param $value default value
 * @param $right limit user who have specific right : interface -> central ; ID -> only current user ; all -> all users ; sinon specific right like show_ticket, create_ticket....
 * @return nothing (print out an HTML select box)
 */
function dropdownUsersID($myname,$value,$right) {
	// Make a select box with all glpi users

	return dropdownUsers($myname,$value,$right);
}

/**
 * Get the value of a dropdown 
 *
 *
 * Returns the value of the dropdown from $table with ID $id.
 *
* @param $table the dropdown table from witch we want values on the select
 * @param $id id of the element to get
 * @param $withcomments give array with name and comments
 * @return string the value of the dropdown or &nbsp; if not exists
 */
function getDropdownName($table,$id,$withcomments=0) {
	global $db,$cfg_glpi,$lang;

	if (in_array($table,$cfg_glpi["dropdowntree_tables"])){
		return getTreeValueCompleteName($table,$id,$withcomments);

	} else	{

		$name = "";
		$comments = "";
		if ($id){
			$query = "select * from ". $table ." where ID = '". $id ."'";
			if ($result = $db->query($query)){
				if($db->numrows($result) != 0) {
					$data=$db->fetch_assoc($result);
					$name = $data["name"];
					if (isset($data["comments"]))
						$comments = $data["comments"];
	
					switch ($table){
						case "glpi_contacts" :
							$name .= " ".$data["firstname"];
							if (!empty($data["phone"])){
								$comments.="<br><strong>".$lang["financial"][29].":</strong> ".$data["phone"];
							}
							if (!empty($data["phone2"])){
								$comments.="<br><strong>".$lang["financial"][29]." 2:</strong> ".$data["phone2"];
							}
							if (!empty($data["mobile"])){
								$comments.="<br><strong>".$lang["common"][42].":</strong> ".$data["mobile"];
							}
							if (!empty($data["fax"])){
								$comments.="<br><strong>".$lang["financial"][30].":</strong> ".$data["fax"];
							}
							if (!empty($data["email"])){
								$comments.="<br><strong>".$lang["setup"][14].":</strong> ".$data["email"];
							}
	
							
							break;
						case "glpi_dropdown_netpoint":
							$name .= " (".getDropdownName("glpi_dropdown_locations",$data["location"]).")";
							break;
						case "glpi_software":
							$name .= "  (v. ".$data["version"].")";
							if ($data["platform"]!=0)
								$comments.="<br>".$lang["software"][3].": ".getDropdownName("glpi_dropdown_os",$data["platform"]);
							break;
					}
	
				}
			}
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
 * @param $myname the name of the HTML select
 * @param $value the preselected value we want
 * @param $field field of the glpi_tracking table to lookiup for possible users
 * @param $display_comments display the comments near the dropdown
 * @return nothing (print out an HTML select box)
 */

function dropdownUsersTracking($myname,$value,$field,$display_comments=1) {
	global $HTMLRel,$cfg_glpi,$lang,$phproot,$db;

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
	echo "           method:'post', parameters:'searchText=' + value+'&value=$value&field=$field&myname=$myname&comments=$display_comments&rand=$rand'\n";
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
		echo "</script>\n";
	}

	$default_display="";
	$comments_display="";
	$user=getUserName($value,2);
	$default_display="<select name='$myname'><option value='$value'>".substr($user["name"],0,$cfg_glpi["dropdown_limit"])."</option></select>\n";
	if ($display_comments) {
		$comments_display="<a href='".$user["link"]."'>";
		$comments_display.="<img alt='".$lang["common"][25]."' src='".$HTMLRel."pics/aide.png' onmouseout=\"cleanhide('comments_$myname$rand')\" onmouseover=\"cleandisplay('comments_$myname$rand')\">";
		$comments_display.="</a>";
		$comments_display.="<span class='over_link' id='comments_$myname$rand'>".$user["comments"]."</span>";
	}


	echo "<span id='results_$myname$rand'>\n";
	if (!$cfg_glpi["use_ajax"]||$nb<$cfg_glpi["ajax_limit_count"]){
		$_POST["myname"]=$myname;
		$_POST["value"]=$value;
		$_POST["field"]=$field;
		$_POST["rand"]=$rand;
		$_POST["comments"]=$display_comments;
		$_POST["searchText"]=$cfg_glpi["ajax_wildcard"];
		include ($phproot."/ajax/dropdownUsersTracking.php");

	}else {
		if (!empty($value)&&$value>0){
			echo $default_display;
		} else {
			echo "<select name='$myname'><option value='0'>[ ".$lang["search"][7]." ]</option></select>\n";
		}
	}
	echo "</span>\n";	

	echo $comments_display;	

	return $rand;
}

/**
 * 
 * Make a select box for icons
 *
 *
 * @param $value the preselected value we want
 * @param $myname the name of the HTML select
 * @param $store_path path where icons are stored
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
* @param $myname select name
 * @param $value default value
 * @param $value_type default value for the device type
 * @param $withenterprise Add enterprises to device type list
 * @param $withcartridge Add cartridges to device type list
 * @param $withconsumable Add consumables to device type list
 * @param $withcontracts Add contracts to device type list
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
	return $rand;
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
function dropdownYesNoInt($name,$value=0){
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
 * @param $none display none choice ? 
 * @param $read display read choice ? 
 * @param $write display write choice ? 
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
 * @param $userID User ID for my device section
 * @return nothing (print out an HTML select box)
 */
function dropdownTrackingDeviceType($myname,$value,$userID=0){
	global $lang,$HTMLRel,$cfg_glpi,$db,$LINK_ID_TABLE;

	$rand=mt_rand();

	if ($userID==0) $userID=$_SESSION["glpiID"];

	$already_add=array();

	if ($_SESSION["glpiprofile"]["helpdesk_hardware"]==0){
		echo "<input type='hidden' name='$myname' value='0'>";
		echo "<input type='hidden' name='computer' value='0'>";
	} else {
		echo "<div id='tracking_device_type_selecter'>";
		echo "<table width='100%'>";
		// View my hardware
		if ($_SESSION["glpiprofile"]["helpdesk_hardware"]&pow(2,HELPDESK_MY_HARDWARE)){
			$my_devices="";

			$ci=new CommonItem();
			$my_item="";

			if (isset($_SESSION["helpdeskSaved"]["_my_items"])) $my_item=$_SESSION["helpdeskSaved"]["_my_items"];

			// My items
			$my_devices.="<optgroup label=\"".$lang["tracking"][1]."\">";
			foreach ($cfg_glpi["linkuser_type"] as $type)
				if ($_SESSION["glpiprofile"]["helpdesk_hardware_type"]&pow(2,$type))
				{
					$query="SELECT * from ".$LINK_ID_TABLE[$type]." WHERE FK_users='".$userID."'";

					$result=$db->query($query);
					if ($db->numrows($result)>0){
						$ci->setType($type);
						$type_name=$ci->getType();
						while ($data=$db->fetch_array($result)){
							$my_devices.="<option value='".$type."_".$data["ID"]."' ".($my_item==$type."_".$data["ID"]?"selected":"").">$type_name - ".$data["name"].($cfg_glpi["view_ID"]?" (".$data["ID"].")":"")."</option>";
							$already_add[$type][]=$data["ID"];
						}
						
					}
				}
			$my_devices.="</optgroup>";


			// My group items
			if (haveRight("show_group_ticket","1")){
				$group_where="";
				$groups=array();
				$query="SELECT glpi_users_groups.FK_groups, glpi_groups.name FROM glpi_users_groups LEFT JOIN glpi_groups ON (glpi_groups.ID = glpi_users_groups.FK_groups) WHERE glpi_users_groups.FK_users='".$userID."';";
	
				$result=$db->query($query);
				$first=true;
				if ($db->numrows($result)>0){
					while ($data=$db->fetch_array($result)){
						if ($first) $first=false;
						else $group_where.=" OR ";
		
						$group_where.=" FK_groups = '".$data["FK_groups"]."' ";
					}
	
					$my_devices.="<optgroup label=\"".$lang["tracking"][1]." - ".$lang["common"][35]."\">";
					foreach ($cfg_glpi["linkuser_type"] as $type)
						if ($_SESSION["glpiprofile"]["helpdesk_hardware_type"]&pow(2,$type))
						{
							$query="SELECT * from ".$LINK_ID_TABLE[$type]." WHERE $group_where";
	
							$result=$db->query($query);
							if ($db->numrows($result)>0){
								$ci->setType($type);
								$type_name=$ci->getType();
								if (!isset($already_add[$type])) $already_add[$type]=array();
								while ($data=$db->fetch_array($result)){
									if (!in_array($data["ID"],$already_add[$type])){
										$my_devices.="<option value='".$type."_".$data["ID"]."' ".($my_item==$type."_".$data["ID"]?"selected":"").">$type_name - ".$data["name"].($cfg_glpi["view_ID"]?" (".$data["ID"].")":"")."</option>";
										$already_add[$type][]=$data["ID"];
									}
								}
								
							}
						}
					$my_devices.="</optgroup>";
				}
			}

			// Get linked items to computers
			if (isset($already_add[COMPUTER_TYPE])&&count($already_add[COMPUTER_TYPE])){

				$search_computer=" (";
				$first=true;
				foreach ($already_add[COMPUTER_TYPE] as $ID){
					if ($first) $first=false;
					else $search_computer.= " OR ";
					$search_computer.= " XXXX='$ID' ";
				}
				$search_computer.=" )";

				$my_devices.="<optgroup label=\"".$lang["reports"][36]."\">";
				// Direct Connection
				$types=array(PERIPHERAL_TYPE,MONITOR_TYPE,PRINTER_TYPE,PHONE_TYPE);
				foreach ($types as $type)
					if ($_SESSION["glpiprofile"]["helpdesk_hardware_type"]&pow(2,$type))
					{
						if (!isset($already_add[$type])) $already_add[$type]=array();
						$query="SELECT DISTINCT ".$LINK_ID_TABLE[$type].".* FROM glpi_connect_wire LEFT JOIN ".$LINK_ID_TABLE[$type]." ON (glpi_connect_wire.end1=".$LINK_ID_TABLE[$type].".ID) WHERE glpi_connect_wire.type='$type' AND  ".ereg_replace("XXXX","glpi_connect_wire.end2",$search_computer)." ORDER BY ".$LINK_ID_TABLE[$type].".name";
						$result=$db->query($query);
						if ($db->numrows($result)>0){
							$ci->setType($type);
							$type_name=$ci->getType();

							while ($data=$db->fetch_array($result)){
								if (!in_array($data["ID"],$already_add[$type])){
									$my_devices.="<option value='".$type."_".$data["ID"]."' ".($my_item==$type."_".$data["ID"]?"selected":"").">$type_name - ".$data["name"].($cfg_glpi["view_ID"]?" (".$data["ID"].")":"")."</option>";
									$already_add[$type][]=$data["ID"];
								}
							}
						}
					}
				$my_devices.= "</optgroup>";
				// Software
				if ($_SESSION["glpiprofile"]["helpdesk_hardware_type"]&pow(2,SOFTWARE_TYPE)){
					$query = "SELECT DISTINCT glpi_software.version as version, glpi_software.name as name, glpi_software.ID as ID FROM glpi_inst_software, glpi_software,glpi_licenses ";
					$query.= "WHERE glpi_inst_software.license = glpi_licenses.ID AND glpi_licenses.sID = glpi_software.ID AND ".ereg_replace("XXXX","glpi_inst_software.cID",$search_computer)." order by glpi_software.name";
					$result=$db->query($query);
					if ($db->numrows($result)>0){
						$my_devices.= "<optgroup label=\"".ucfirst($lang["software"][17])."\">";
						$ci->setType(SOFTWARE_TYPE);
						$type_name=$ci->getType();
						if (!isset($already_add[SOFTWARE_TYPE])) $already_add[SOFTWARE_TYPE]=array();
						while ($data=$db->fetch_array($result)){
							if (!in_array($data["ID"],$already_add[SOFTWARE_TYPE])){
								$my_devices.="<option value='".SOFTWARE_TYPE."_".$data["ID"]."' ".($my_item==SOFTWARE_TYPE."_".$data["ID"]?"selected":"").">$type_name - ".$data["name"]." (v. ".$data["version"].")".($cfg_glpi["view_ID"]?" (".$data["ID"].")":"")."</option>";
								$already_add[SOFTWARE_TYPE][]=$data["ID"];
							}
						}
						$my_devices.= "</optgroup>";
					}
				}
				
			}

			echo "<tr><td align='center'>".$lang["tracking"][1].":&nbsp;<select name='_my_items'><option value=''>--- ".$lang["help"][30]." ---</option>$my_devices</select>";
			if ($_SESSION["glpiprofile"]["helpdesk_hardware"]&pow(2,HELPDESK_ALL_HARDWARE))
				echo "<br>".$lang["tracking"][2].":&nbsp;";
			else echo "</td></tr>";
		}

		if ($_SESSION["glpiprofile"]["helpdesk_hardware"]&pow(2,HELPDESK_ALL_HARDWARE)){

			echo "<select id='search_$myname$rand' name='$myname'>\n";

			echo "<option value='0' ".(($value==0)?" selected":"").">".$lang["help"][30]."</option>\n";
			if ($_SESSION["glpiprofile"]["helpdesk_hardware_type"]&pow(2,COMPUTER_TYPE))
				echo "<option value='".COMPUTER_TYPE."' ".(($value==COMPUTER_TYPE)?" selected":"").">".$lang["help"][25]."</option>\n";
			if ($_SESSION["glpiprofile"]["helpdesk_hardware_type"]&pow(2,NETWORKING_TYPE))
				echo "<option value='".NETWORKING_TYPE."' ".(($value==NETWORKING_TYPE)?" selected":"").">".$lang["help"][26]."</option>\n";
			if ($_SESSION["glpiprofile"]["helpdesk_hardware_type"]&pow(2,PRINTER_TYPE))
				echo "<option value='".PRINTER_TYPE."' ".(($value==PRINTER_TYPE)?" selected":"").">".$lang["help"][27]."</option>\n";
			if ($_SESSION["glpiprofile"]["helpdesk_hardware_type"]&pow(2,MONITOR_TYPE))
				echo "<option value='".MONITOR_TYPE."' ".(($value==MONITOR_TYPE)?" selected":"").">".$lang["help"][28]."</option>\n";
			if ($_SESSION["glpiprofile"]["helpdesk_hardware_type"]&pow(2,PERIPHERAL_TYPE))
				echo "<option value='".PERIPHERAL_TYPE."' ".(($value==PERIPHERAL_TYPE)?" selected":"").">".$lang["help"][29]."</option>\n";
			if ($_SESSION["glpiprofile"]["helpdesk_hardware_type"]&pow(2,SOFTWARE_TYPE))
				echo "<option value='".SOFTWARE_TYPE."' ".(($value==SOFTWARE_TYPE)?" selected":"").">".$lang["help"][31]."</option>\n";
			if ($_SESSION["glpiprofile"]["helpdesk_hardware_type"]&pow(2,PHONE_TYPE))
				echo "<option value='".PHONE_TYPE."' ".(($value==PHONE_TYPE)?" selected":"").">".$lang["help"][35]."</option>\n";
			echo "</select>\n";

			echo "</td></tr><tr><td align='center'>";

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
			echo "</td></tr>";
		}
		echo "</table>";
		echo "</div";
	}		
	return $rand;
}

/**
 * Make a select box for connections
 *
 *
 *
 * @param $type type to connect
 * @param $fromtype from where the connection is
 * @param $myname select name
 * @param $onlyglobal display only global devices (used for templates)
 * @return nothing (print out an HTML select box)
 */
function dropdownConnect($type,$fromtype,$myname,$onlyglobal=0) {


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
	echo "           method:'post', parameters:'searchText=' + value+'&fromtype=$fromtype&idtable=$type&myname=$myname&onlyglobal=$onlyglobal'\n";
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

	return $rand;
}


/**
 * Make a select box for  connected port
 *
 *
 * @param $ID ID of the current port to connect
 * @param $type type of device where to search ports
 * @param $myname select name
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
	echo "<select name='type[$ID]' id='item_type$rand'>\n";
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

	return $rand;
}

/**
 * Make a select box for  software to install
 *
 *
 * @param $myname select name
 * @param $withtemplate is it a template computer ?
 * @param $massiveaction is it a massiveaction select ?
 * @return nothing (print out an HTML select box)
 */
function dropdownSoftwareToInstall($myname,$withtemplate,$massiveaction=0) {
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
	echo "           method:'post', parameters:'searchSoft=' + value+'&myname=$myname&withtemplate=$withtemplate&massiveaction=$massiveaction'\n";
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


	return $rand;
}

/**
 * Show div with auto completion
 *
 * @param $myname text field name
 * @param $table table to search for autocompletion
 * @param $field field to serahc for autocompletion
 * @param $value value to fill text field
 * @param $size size of the text field
 * @param $option option of the textfield
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
 * @param $target URL to post the form
 * @param $cID computer ID
 * @param $withtemplate is it a template computer ?
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
		if ($device_type==COMPUTER_TYPE)
			echo "<option value=\"install\">".$lang["buttons"][4]."</option>";
		if ($device_type==PHONE_TYPE || $device_type==PERIPHERAL_TYPE || $device_type==MONITOR_TYPE || $device_type==PERIPHERAL_TYPE || $device_type==PRINTER_TYPE){
			echo "<option value=\"connect\">".$lang["buttons"][9]."</option>";
			echo "<option value=\"disconnect\">".$lang["buttons"][10]."</option>";
		}
		if ($device_type==USER_TYPE){
			echo "<option value=\"add_group\">".$lang["setup"][604]."</option>";
		}
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

function dropdownMassiveActionPorts(){
	global $lang,$HTMLRel,$cfg_glpi;

	echo "<select name=\"massiveaction\" id='massiveaction'>";

	echo "<option value=\"-1\" selected>-----</option>";
	echo "<option value=\"delete\">".$lang["buttons"][6]."</option>";
	echo "<option value=\"assign_vlan\">".$lang["networking"][55]."</option>";
	echo "<option value=\"unassign_vlan\">".$lang["networking"][58]."</option>";
	echo "</select>";

	echo "<script type='text/javascript' >\n";
	echo "   new Form.Element.Observer('massiveaction', 1, \n";
	echo "      function(element, value) {\n";
	echo "      	new Ajax.Updater('show_massiveaction','".$cfg_glpi["root_doc"]."/ajax/dropdownMassiveActionPorts.php',{asynchronous:true, evalScripts:true, \n";	echo "           onComplete:function(request)\n";
	echo "            {Element.hide('search_spinner_massiveaction');}, \n";
	echo "           onLoading:function(request)\n";
	echo "            {Element.show('search_spinner_massiveaction');},\n";
	echo "           method:'post', parameters:'action='+value\n";
	echo "})})\n";
	echo "</script>\n";

	echo "<div id='search_spinner_massiveaction' style=' position:absolute;   filter:alpha(opacity=70); -moz-opacity:0.7; opacity: 0.7; display:none;'><img src=\"".$HTMLRel."pics/wait.png\" title='Processing....' alt='Processing....' /></div>\n";
	echo "<span id='show_massiveaction'>&nbsp;</span>\n";
}

function globalManagementDropdown($target,$withtemplate,$ID,$value){
	global $lang,$HTMLRel;	

	if ($value&&empty($withtemplate)) {
		echo $lang["peripherals"][31];

		echo "&nbsp;<a title=\"".$lang["common"][39]."\" href=\"javascript:confirmAction('".addslashes($lang["common"][40])."\\n".addslashes($lang["common"][39])."','$target?unglobalize=unglobalize&amp;ID=$ID')\">".$lang["common"][38]."</a>&nbsp;";	

		echo "<img alt=\"".$lang["common"][39]."\" title=\"".$lang["common"][39]."\" src=\"".$HTMLRel."pics/aide.png\">";
	} else {
		echo "<select name='is_global'>";
		echo "<option value='0' ".(!$value?" selected":"").">".$lang["peripherals"][32]."</option>";
		echo "<option value='1' ".($value?" selected":"").">".$lang["peripherals"][31]."</option>";
		echo "</select>";
	}
}

function dropdownContractAlerting($myname,$value){
	global $lang;
	echo "<select name='$myname'>";
	echo "<option value='0' ".($value==0?"selected":"")." >-------</option>";
	echo "<option value='".pow(2,ALERT_END)."' ".($value==pow(2,ALERT_END)?"selected":"")." >".$lang["buttons"][32]."</option>";
	echo "<option value='".pow(2,ALERT_NOTICE)."' ".($value==pow(2,ALERT_NOTICE)?"selected":"")." >".$lang["financial"][10]."</option>";
	echo "<option value='".(pow(2,ALERT_END)+pow(2,ALERT_NOTICE))."' ".($value==(pow(2,ALERT_END)+pow(2,ALERT_NOTICE))?"selected":"")." >".$lang["buttons"][32]." + ".$lang["financial"][10]."</option>";
	echo "</select>";

}


/**
 * Print a select with hours
 *
 * Print a select named $name with hours options and selected value $value
 *
 *@param $name string : HTML select name
 *@param $value integer : HTML select selected value
 *
 *@return Nothing (display)
 *
 **/
function dropdownHours($name,$value,$limit_planning=0){
	global $cfg_glpi;

	$begin=0;
	$end=24;
	$step=$cfg_glpi["time_step"];
	// Check if the $step is Ok for the $value field
	$split=split(":",$value);
	// Valid value XX:YY ou XX:YY:ZZ
	if (count($split)==2||count($split)==3){
		$min=$split[1];
		// Problem
		if (($min%$step)!=0){
			// set minimum step
			$step=5;
		}
	}

	if ($limit_planning){
		$plan_begin=split(":",$cfg_glpi["planning_begin"]);
		$plan_end=split(":",$cfg_glpi["planning_end"]);
		$begin=(int) $plan_begin[0];
		$end=(int) $plan_end[0];
	}
	echo "<select name=\"$name\">";
	for ($i=$begin;$i<=$end;$i++){
		if ($i<10)
			$tmp="0".$i;
		else $tmp=$i;

		for ($j=0;$j<60;$j+=$step){
			if ($j<10) $val=$tmp.":0$j";
			else $val=$tmp.":$j";

			echo "<option value='$val' ".($value==$val.":00"||$value==$val?" selected ":"").">$val</option>";
		}
	}
	echo "</select>";	
}	

function dropdownLicenseOfSoftware($myname,$sID) {
	global $db,$lang;

	$query="SELECT * from glpi_licenses WHERE sID='$sID' GROUP BY serial, expire, oem, oem_computer, buy ORDER BY serial,oem, oem_computer";
	//echo $query;
	$result=$db->query($query);
	if ($db->numrows($result)){
		echo "<select name='$myname'>";
		while ($data=$db->fetch_array($result)){
			echo "<option value='".$data["ID"]."'>".$data["serial"];
			if ($data["expire"]!=NULL) echo " - ".$lang["software"][25]." ".$data["expire"];
			else echo " - ".$lang["software"][26];
			if ($data["buy"]=='Y') echo " - ".$lang["software"][35];
			else echo " - ".$lang["software"][37];
			if ($data["oem"]=='Y') echo " - ".$lang["software"][28];
			echo "</option>";
		}
		echo "</select>";
	}

}
?>
