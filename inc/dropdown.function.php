<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2008 by the INDEPNET Development Team.

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

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}

// Functions Dropdown




/**
 * Print out an HTML "<select>" for a dropdown
 *
 * @param $table the dropdown table from witch we want values on the select
 * @param $myname the name of the HTML select
 * @param $display_comments display the comments near the dropdown
 * @param $entity_restrict Restrict to a defined entity
 * @param $used Already used items : not to display in dropdown
 * @return nothing (display the select box)
 **/
function dropdown($table,$myname,$display_comments=1,$entity_restrict=-1,$used=array()) {

	return dropdownValue($table,$myname,'',$display_comments,$entity_restrict,"",$used);
}

/**
 * Print out an HTML "<select>" for a dropdown with preselected value
 *
 *
 * @param $table the dropdown table from witch we want values on the select
 * @param $myname the name of the HTML select
 * @param $value the preselected value we want
 * @param $display_comments display the comments near the dropdown
 * @param $entity_restrict Restrict to a defined entity
 * @param $update_item Update a specific item on select change on dropdown (need value_fieldname, to_update, url (see ajaxUpdateItemOnSelectEvent for informations) and may have moreparams)
 * @param $used Already used items : not to display in dropdown
 * @return nothing (display the select box)
 *
 */
function dropdownValue($table,$myname,$value='',$display_comments=1,$entity_restrict=-1,$update_item="",$used=array()) {

	global $DB,$CFG_GLPI,$LANG;

	$rand=mt_rand();

	$name="------";
	$comments="";
	$limit_length=$_SESSION["glpidropdown_limit"];
	

	if (strlen($value)==0) $value=-1;

	if ($value>0 
		|| ($table=="glpi_entities"&&$value>=0)){
		$tmpname=getDropdownName($table,$value,1);
		if ($tmpname["name"]!="&nbsp;"){
			$name=$tmpname["name"];
			$comments=$tmpname["comments"];
			//$limit_length=max(strlen($name),$_SESSION["glpidropdown_limit"]);
			if (strlen($name) > $_SESSION["glpidropdown_limit"]) {
				if (in_array($table,$CFG_GLPI["dropdowntree_tables"])) {
					$pos = strrpos($name,">");
					$limit_length=max(strlen($name)-$pos,$_SESSION["glpidropdown_limit"]);
					if (strlen($name)>$limit_length) {
						$name = "&hellip;".utf8_substr($name,-$limit_length);
					}
				} else {
					$limit_length = strlen($name);
				}
			} else {
				$limit_length = $_SESSION["glpidropdown_limit"];
			}
		}
	}

	$use_ajax=false;
	if ($CFG_GLPI["use_ajax"]){
		$nb=0;
		if ($table=='glpi_entities' || in_array($table,$CFG_GLPI["specif_entities_tables"])){
			if (!($entity_restrict<0)){
				$nb=countElementsInTableForEntity($table,$entity_restrict);
			} else {
				$nb=countElementsInTableForMyEntities($table);
			}
		} else {
			$nb=countElementsInTable($table);
		}
		$nb -= count($used);
		if ($nb>$CFG_GLPI["ajax_limit_count"]){
			$use_ajax=true;
		}
	}
	
	$params=array('searchText'=>'__VALUE__',
			'value'=>$value,
			'table'=>$table,
			'myname'=>$myname,
			'limit'=>$limit_length,
			'comments'=>$display_comments,
			'rand'=>$rand,
			'entity_restrict'=>$entity_restrict,
			'update_item'=>$update_item,
			'used'=>$used
			);
	$default="<select name='$myname' id='dropdown_".$myname.$rand."'><option value='$value'>$name</option></select>\n";
	ajaxDropdown($use_ajax,"/ajax/dropdownValue.php",$params,$default,$rand);

	// Display comments
	$which="";

	$dropdown_right=false;

	if (strstr($table,"glpi_dropdown_")||strstr($table,"glpi_type_")){
		if (!in_array($table,$CFG_GLPI["specif_entities_tables"])){
			$dropdown_right=haveRight("dropdown","w");
		} else {
			$dropdown_right=haveRight("entity_dropdown","w");
		}

		if ($dropdown_right){
			$which=$table;
		}
	}

	if ($display_comments){
		echo "<img alt='' src='".$CFG_GLPI["root_doc"]."/pics/aide.png' onmouseout=\"cleanhide('comments_$myname$rand')\" onmouseover=\"cleandisplay('comments_$myname$rand')\" ";
		if ($dropdown_right && !empty($which)) {
			if (is_array($entity_restrict) && count($entity_restrict)==1) {
				$entity_restrict=array_pop($entity_restrict);
			}
			if (!is_array($entity_restrict)) {
				echo " style='cursor:pointer;'  onClick=\"var w = window.open('".$CFG_GLPI["root_doc"]."/front/popup.php?popup=dropdown&amp;which=$which"."&amp;rand=$rand&amp;FK_entities=$entity_restrict' ,'glpipopup', 'height=400, width=1000, top=100, left=100, scrollbars=yes' );w.focus();\"";
			}
		}
		echo ">";
		echo "<span class='over_link' id='comments_$myname$rand'>".nl2br($comments)."</span>";
	}
	// Display specific Links
	if ($table=="glpi_enterprises"){
		echo getEnterpriseLinks($value);	
	}

	return $rand;
}

/**
 * Print out an HTML "<select>" for a dropdown with preselected value
 *
 *
 * @param $myname the name of the HTML select
 * @param $value the preselected value we want
 * @param $location default location for search
 * @param $display_comments display the comments near the dropdown
 * @param $entity_restrict Restrict to a defined entity
 * @param $devtype
 * @return nothing (display the select box)
 *
 */
function dropdownNetpoint($myname,$value=0,$location=-1,$display_comments=1,$entity_restrict=-1,$devtype=-1) {

	global $DB,$CFG_GLPI,$LANG;

	$rand=mt_rand();

	$name="------";
	$comments="";
	$limit_length=$_SESSION["glpidropdown_limit"];
	if (empty($value)) $value=0;
	if ($value>0){
		$tmpname=getDropdownName("glpi_dropdown_netpoint",$value,1);
		if ($tmpname["name"]!="&nbsp;"){
			$name=$tmpname["name"];
			$comments=$tmpname["comments"];
			$limit_length=max(strlen($name),$_SESSION["glpidropdown_limit"]);
		}
	}
	
	$use_ajax=false;	
	if ($CFG_GLPI["use_ajax"]){
		if ($location < 0 || $devtype==NETWORKING_TYPE) {
			$nb=countElementsInTableForEntity("glpi_dropdown_netpoint",$entity_restrict);
		} else if ($location > 0) {
			$nb=countElementsInTable("glpi_dropdown_netpoint", "location=$location ");
		} else {
			$nb=countElementsInTable("glpi_dropdown_netpoint", "location=0 ".getEntitiesRestrictRequest(" AND ","glpi_dropdown_netpoint",'',$entity_restrict));
		}
		if ($nb>$CFG_GLPI["ajax_limit_count"]){
			$use_ajax=true;
		}
	}

	$params=array('searchText'=>'__VALUE__',
			'value'=>$value,
			'location'=>$location,
			'myname'=>$myname,
			'limit'=>$limit_length,
			'comments'=>$display_comments,
			'rand'=>$rand,
			'entity_restrict'=>$entity_restrict,
			'devtype'=>$devtype,
			);

	$default="<select name='$myname'><option value='$value'>$name</option></select>\n";
	ajaxDropdown($use_ajax,"/ajax/dropdownNetpoint.php",$params,$default,$rand);

	// Display comments 

	if ($display_comments){
		echo "<img alt='' src='".$CFG_GLPI["root_doc"]."/pics/aide.png' onmouseout=\"cleanhide('comments_$myname$rand')\" onmouseover=\"cleandisplay('comments_$myname$rand')\" ";
		if (haveRight("entity_dropdown","w")) {
			echo " style='cursor:pointer;'  onClick=\"var w = window.open('".$CFG_GLPI["root_doc"]."/front/popup.php?popup=dropdown&amp;which=glpi_dropdown_netpoint&amp;value2=$location"."&amp;rand=$rand&amp;FK_entities=$entity_restrict' ,'glpipopup', 'height=400, width=1000, top=100, left=100, scrollbars=yes' );w.focus();\"";
		}
		echo ">";
		echo "<span class='over_link' id='comments_$myname$rand'>".nl2br($comments)."</span>";
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
 * @param $entity_restrict Restrict to a defined entity
 * @return nothing (print out an HTML select box)
 * 
 */
function dropdownNoValue($table,$myname,$value,$entity_restrict=-1) {
	// Make a select box without parameters value

	global $DB,$CFG_GLPI,$LANG;

	$where="";
	if (in_array($table,$CFG_GLPI["specif_entities_tables"])){
		$where.= "WHERE ".$table.".FK_entities='".$entity_restrict."'";
	} 

	if (in_array($table,$CFG_GLPI["deleted_tables"])){
		if (empty($where)){
			$where=" WHERE ";
		} else {
			$where.=" AND ";
		}
		$where=" WHERE deleted='0'";
	}
	if (in_array($table,$CFG_GLPI["template_tables"])){
		if (empty($where)){
			$where=" WHERE ";
		} else {
			$where.=" AND ";
		}
		$where.=" is_template='0'";
	}

	if (empty($where)){
		$where=" WHERE ";
	} else {
		$where.=" AND ";
	}
	$where.=" ID<>'$value' ";
	
	if (in_array($table,$CFG_GLPI["dropdowntree_tables"])){
		$query = "SELECT ID, completename as name FROM $table $where  ORDER BY completename";
	}
	else {
		$query = "SELECT ID, name FROM $table $where AND ID<>'$value' ORDER BY name";
	}
	$result = $DB->query($query);

	
	echo "<select name=\"$myname\" size='1'>";
	if ($table=="glpi_entities"){
		echo "<option value=\"0\">".$LANG["entity"][2]."</option>";
	}

	if ($DB->numrows($result) > 0) {
		while ($data=$DB->fetch_array($result)) {
			echo "<option value=\"".$data['ID']."\">".$data['name']."</option>";
		}
	}
	echo "</select>";
}

/**
 * Execute the query to select box with all glpi users where select key = name
 * 
 * Internaly used by showGroupUsers, dropdownUsers and ajax/dropdownUsers.php
 *
 * @param $count true if execute an count(*), 
 * @param $right limit user who have specific right
 * @param $entity_restrict Restrict to a defined entity
 * @param $value default value
 * @param $used array of user ID
 * @param $search pattern 
 * 
 * @return mysql result set.
 *
 */
function dropdownUsersSelect ($count=true, $right="all", $entity_restrict=-1, $value=0, $used=array(), $search='') {

	global $DB, $CFG_GLPI;
	
	if ($entity_restrict<0) {
		$entity_restrict = $_SESSION["glpiactive_entity"];
	}

	$joinprofile=false;
	switch ($right){
		case "interface" :
			$where=" glpi_profiles.interface='central' ";
			$joinprofile=true;
			$where.=getEntitiesRestrictRequest("AND","glpi_users_profiles",'',$entity_restrict,1);
		break;
		case "ID" :
			$where=" glpi_users.ID='".$_SESSION["glpiID"]."' ";
		break;
		case "all" :
			$where=" glpi_users.ID > '1' ";
			$where.=getEntitiesRestrictRequest("AND","glpi_users_profiles",'',$entity_restrict,1);
		break;
		default :
			$joinprofile=true;
			$where=" ( glpi_profiles.".$right."='1' AND glpi_profiles.interface='central' ";
			$where.=getEntitiesRestrictRequest("AND","glpi_users_profiles",'',$entity_restrict,1);
			$where.=" ) ";
			
		break;
	}
	
	$where .= " AND glpi_users.deleted='0' AND glpi_users.active='1' ";
	
	if ($value || count($used)) {
		$where .= " AND glpi_users.ID NOT IN (";
		if ($value) {
			$first=false;
			$where .= $value;
		}
		else {
			$first=true;	
		}
		foreach($used as $val) {
			if ($first) {
				$first = false;
			} else {
				$where .= ",";
			}
			$where .= $val;
		}
		$where .= ")";
	}

	if ($count) {
		$query = "SELECT COUNT( DISTINCT glpi_users.ID ) AS CPT FROM glpi_users ";
	} else {
		$query = "SELECT DISTINCT glpi_users.* FROM glpi_users ";
	}
	$query.=" LEFT JOIN glpi_users_profiles ON (glpi_users.ID = glpi_users_profiles.FK_users)";
	if ($joinprofile){
		$query .= " LEFT JOIN glpi_profiles ON (glpi_profiles.ID= glpi_users_profiles.FK_profiles) ";
	}

	if ($count) {
		$query.= " WHERE $where ";
	} else {
		if (strlen($search)>0 && $search!=$CFG_GLPI["ajax_wildcard"]){
			$where.=" AND (glpi_users.name ".makeTextSearch($search)." OR glpi_users.realname ".makeTextSearch($search).
				"  OR glpi_users.firstname ".makeTextSearch($search)." OR CONCAT(glpi_users.realname,' ',glpi_users.firstname) ".makeTextSearch($search).")";
		}
		$query .= " WHERE $where ORDER BY glpi_users.realname,glpi_users.firstname, glpi_users.name ";
		if ($search != $CFG_GLPI["ajax_wildcard"]) {
			$query .= " LIMIT 0,".$CFG_GLPI["dropdown_max"];
		}
	}

	return $DB->query($query);
}

/**
 * Make a select box with all glpi users where select key = name
 *
 * 
 *
 * @param $myname select name
 * @param $value default value
 * @param $right limit user who have specific right : interface -> central ; ID -> only current user ; all -> all users ; sinon specific right like show_all_ticket, create_ticket....
 * @param $all Nobody or All display for none selected $all =0 -> Nobody $all=1 -> All $all=-1-> nothing
 * @param $display_comments display comments near the dropdown
 * @param $entity_restrict Restrict to a defined entity
 * @param $helpdesk_ajax use ajax for helpdesk auto update (mail device_type)
 * @param $used array of user ID
 * 
 * @return nothing (print out an HTML select box)
 *
 */
function dropdownUsers($myname,$value,$right,$all=0,$display_comments=1,$entity_restrict=-1,$helpdesk_ajax=0,$used=array()) {
	// Make a select box with all glpi users

	global $DB,$CFG_GLPI,$LANG;

	$rand=mt_rand();

	$use_ajax=false;
	if ($CFG_GLPI["use_ajax"]){
		$res=dropdownUsersSelect (true, $right, $entity_restrict, $value, $used);
		$nb=($res ? $DB->result($res,0,"CPT") : 0);
		if ($nb > $CFG_GLPI["ajax_limit_count"]) {
			$use_ajax=true;
		}
	}
	$user=getUserName($value,2);
	$default_display="";

	$default_display="<select id='dropdown_".$myname.$rand."' name='$myname'><option value='$value'>".substr($user["name"],0,$_SESSION["glpidropdown_limit"])."</option></select>\n";

	$view_users=(haveRight("user","r"));

	$params=array('searchText'=>'__VALUE__',
			'value'=>$value,
			'myname'=>$myname,
			'all'=>$all,
			'right'=>$right,
			'comments'=>$display_comments,
			'rand'=>$rand,
			'helpdesk_ajax'=>$helpdesk_ajax,
			'entity_restrict'=>$entity_restrict,
			'used'=>$used
			);
	if ($view_users){
		$params['update_link']=$view_users;
	}

	$default="";
	if (!empty($value)&&$value>0){
		$default=$default_display;
	} else {
		if ($all){
			$default="<select name='$myname' id='dropdown_".$myname.$rand."'><option value='0'>[ ".$LANG["common"][66]." ]</option></select>\n";
		} else {
			$default="<select name='$myname' id='dropdown_".$myname.$rand."'><option value='0'>[ Nobody ]</option></select>\n";
		}
	}

	ajaxDropdown($use_ajax,"/ajax/dropdownUsers.php",$params,$default,$rand);

	// Display comments

	if ($display_comments) {
		if ($view_users){
			if (empty($user["link"])){
				$user["link"]=$CFG_GLPI['root_doc']."/front/user.php";
			}
			echo "<a id='comments_link_$myname$rand' href='".$user["link"]."'>";
		}
		echo "<img alt='' src='".$CFG_GLPI["root_doc"]."/pics/aide.png' onmouseout=\"cleanhide('comments_$myname$rand')\" onmouseover=\"cleandisplay('comments_$myname$rand')\">";
		if ($view_users){
			echo "</a>";
		}
		echo "<span class='over_link' id='comments_$myname$rand'>".$user["comments"]."</span>";
	}

	return $rand;
}


/**
 * Make a select box with all glpi users
 *
 *
 * @param $myname select name
 * @param $value default value
 * @param $display_comments display comments near the dropdown
 * @param $entity_restrict Restrict to a defined entity
 * @param $helpdesk_ajax use ajax for helpdesk auto update (mail device_type)
 * @param $used array of user ID
 * 
 * @return nothing (print out an HTML select box)
 * 
 */
function dropdownAllUsers($myname,$value=0,$display_comments=1,$entity_restrict=-1,$helpdesk_ajax=0,$used=array()) {
	return dropdownUsers($myname,$value,"all",0,$display_comments,$entity_restrict,$helpdesk_ajax,$used);
}


/**
 * Make a select box with all glpi users where select key = ID
 *
 *
 *
* @param $myname select name
 * @param $value default value
 * @param $right limit user who have specific right : interface -> central ; ID -> only current user ; all -> all users ; sinon specific right like show_all_ticket, create_ticket....
 * @param $entity_restrict Restrict to a defined entity
 * @param $display_comments display comments near the dropdown
 * @return nothing (print out an HTML select box)
 */
function dropdownUsersID($myname,$value,$right,$display_comments=1,$entity_restrict=-1) {
	// Make a select box with all glpi users

	return dropdownUsers($myname,$value,$right,0,$display_comments,$entity_restrict);
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
	global $DB,$CFG_GLPI,$LANG;

	if (in_array($table,$CFG_GLPI["dropdowntree_tables"])){
		return getTreeValueCompleteName($table,$id,$withcomments);

	} else	{

		$name = "";
		$comments = "";
		if ($id){
			$query = "SELECT * FROM ". $table ." WHERE ID = '". $id ."'";
			if ($result = $DB->query($query)){
				if($DB->numrows($result) != 0) {
					$data=$DB->fetch_assoc($result);
					$name = $data["name"];
					if (isset($data["comments"])){
						$comments = $data["comments"];
					}
					switch ($table){
						case "glpi_contacts" :
							$name .= " ".$data["firstname"];
							if (!empty($data["phone"])){
								$comments.="<br><strong>".$LANG["help"][35].":</strong> ".$data["phone"];
							}
							if (!empty($data["phone2"])){
								$comments.="<br><strong>".$LANG["help"][35]." 2:</strong> ".$data["phone2"];
							}
							if (!empty($data["mobile"])){
								$comments.="<br><strong>".$LANG["common"][42].":</strong> ".$data["mobile"];
							}
							if (!empty($data["fax"])){
								$comments.="<br><strong>".$LANG["financial"][30].":</strong> ".$data["fax"];
							}
							if (!empty($data["email"])){
								$comments.="<br><strong>".$LANG["setup"][14].":</strong> ".$data["email"];
							}
							break;
						case "glpi_enterprises" :
							if (!empty($data["phone"])){
								$comments.="<br><strong>".$LANG["help"][35].":</strong> ".$data["phone"];
							}
							if (!empty($data["fax"])){
								$comments.="<br><strong>".$LANG["financial"][30].":</strong> ".$data["fax"];
							}
							if (!empty($data["email"])){
								$comments.="<br><strong>".$LANG["setup"][14].":</strong> ".$data["email"];
							}
							break;

						case "glpi_dropdown_netpoint":
							$name .= " (".getDropdownName("glpi_dropdown_locations",$data["location"]).")";
							break;
						case "glpi_software":
							if ($data["platform"]!=0 && $data["helpdesk_visible"] != 0)
								$comments.="<br>".$LANG["software"][3].": ".getDropdownName("glpi_dropdown_os",$data["platform"]);
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
 * Get values of a dropdown for a list of item
 *
* @param $table the dropdown table from witch we want values on the select
 * @param $ids array containing the ids to get
 * @return array containing the value of the dropdown or &nbsp; if not exists
 */
function getDropdownArrayNames($table,$ids) {
	global $DB,$CFG_GLPI;
	$tabs=array();

	if (count($ids)){
		$field='name';
		if (in_array($table,$CFG_GLPI["dropdowntree_tables"])){
			$field='completename';
		}

		$query="SELECT ID, $field FROM $table WHERE ID IN (";
		$first=true;
		foreach ($ids as $val){
			if (!$first) $query.=",";
			else $first=false;
			$query.=$val;
		}			
		$query.=")";

		if ($result=$DB->query($query)){
			while ($data=$DB->fetch_assoc($result)){
				$tabs[$data['ID']]=$data[$field];
			}
		}
	} 
	return $tabs;
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
	global $CFG_GLPI,$LANG,$DB;

	$rand=mt_rand();

	$use_ajax=false;
	if ($CFG_GLPI["use_ajax"]){
		if ($CFG_GLPI["ajax_limit_count"]==0){
			$use_ajax=true;
		} else {
			$query="SELECT COUNT(".$field.") FROM glpi_tracking ".getEntitiesRestrictRequest("WHERE","glpi_tracking");
			$result=$DB->query($query);
			$nb=$DB->result($result,0,0);
			if ($nb>$CFG_GLPI["ajax_limit_count"]){
				$use_ajax=true;
			}
		}
	}

	$default="";
	$user=getUserName($value,2);
	$default="<select name='$myname'><option value='$value'>".substr($user["name"],0,$_SESSION["glpidropdown_limit"])."</option></select>\n";
	if (empty($value)||$value==0){
			$default= "<select name='$myname'><option value='0'>[ ".$LANG["common"][66]." ]</option></select>\n";
	}

	$params=array('searchText'=>'__VALUE__',
			'value'=>$value,
			'field'=>$field,
			'myname'=>$myname,
			'comments'=>$display_comments,
			'rand'=>$rand
			);

	ajaxDropdown($use_ajax,"/ajax/dropdownUsersTracking.php",$params,$default,$rand);

	// Display comments 

	if ($display_comments) {
		if (empty($user["link"])){
			$user["link"]='#';
		}
		echo "<a href='".$user["link"]."'>";
		echo "<img alt='' src='".$CFG_GLPI["root_doc"]."/pics/aide.png' onmouseout=\"cleanhide('comments_$myname$rand')\" onmouseover=\"cleandisplay('comments_$myname$rand')\">";
		echo "</a>";
		echo "<span class='over_link' id='comments_$myname$rand'>".$user["comments"]."</span>";
	}

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
	global $LANG;
	if (is_dir($store_path)){
		if ($dh = opendir($store_path)) {
			$files=array();
			while (($file = readdir($dh)) !== false) {
				$files[]=$file;
			}
			closedir($dh);
			sort($files);
			echo "<select name=\"$myname\">";
			echo "<option value=''>-----</option>";
			foreach ($files as $file){
				if (preg_match("/\.png$/i",$file)){
					if ($file == $value) {
						echo "<option value=\"$file\" selected>".$file;
					} else {
						echo "<option value=\"$file\">".$file;
					}
					echo "</option>";
				}
			}
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
 * @param $value default device type
 * @param $types types to display
 * @return nothing (print out an HTML select box)
 */
function dropdownDeviceTypes($name,$value=0,$types=array()){
	global $CFG_GLPI;


	$options=array(0=>'----');
	if (count($types)){
		$ci=new CommonItem();
						
		foreach ($types as $type){
			$ci->setType($type);
			$options[$type]=$ci->getType();
		}
		asort($options);
	}

	dropdownArrayValues($name,$options,$value);

}



/**
 * 
 *Make a select box for all items
 *
 *
* @param $myname select name
 * @param $value default value
 * @param $value_type default value for the device type
 * @param $entity_restrict Restrict to a defined entity
 * @param $types Types used
 * @param $onlyglobal Restrict to global items
 * @return nothing (print out an HTML select box)
 */
function dropdownAllItems($myname,$value_type=0,$value=0,$entity_restrict=-1,$types='',$onlyglobal=false) {
	global $LANG,$CFG_GLPI;
	if (!is_array($types)){
		$types=$CFG_GLPI["state_types"];
	}
	$rand=mt_rand();
	$ci=new CommonItem();
	$options=array();
	
	foreach ($types as $type){
		$ci->setType($type);
		$options[$type]=$ci->getType();
	}
	asort($options);
	if (count($options)){
		echo "<select name='type' id='item_type$rand'>\n";
			echo "<option value='0'>-----</option>\n";
		foreach ($options as $key => $val){
			echo "<option value='".$key."'>".$val."</option>\n";
		}
		echo "</select>";

		$params=array('idtable'=>'__VALUE__',
			'value'=>$value,
			'myname'=>$myname,
			'entity_restrict'=>$entity_restrict,
			);
		if ($onlyglobal){
			$params['onlyglobal']=1;
		}
		ajaxUpdateItemOnSelectEvent("item_type$rand","show_$myname$rand",$CFG_GLPI["root_doc"]."/ajax/dropdownAllItems.php",$params);

		echo "<br><span id='show_$myname$rand'>&nbsp;</span>\n";

		if ($value>0){
			echo "<script type='text/javascript' >\n";
			echo "window.document.getElementById('item_type$rand').value='".$value_type."';";
			echo "</script>\n";

			$params["idtable"]=$value_type;
			ajaxUpdateItem("show_$myname$rand",$CFG_GLPI["root_doc"]."/ajax/dropdownAllItems.php",$params);
			
		}
	}
	return $rand;
}


/**
 * Make a select box for a boolean choice (Yes/No)
 *
 * @param $name select name
 * @param $value preselected value.
 * @return nothing (print out an HTML select box)
 */
function dropdownYesNo($name,$value=0){
	global $LANG;
	echo "<select name='$name' id='dropdownyesno_$name'>\n";
	echo "<option value='0' ".(!$value?" selected ":"").">".$LANG["choice"][0]."</option>\n";
	echo "<option value='1' ".($value?" selected ":"").">".$LANG["choice"][1]."</option>\n";
	echo "</select>\n";	
}	

/**
 * Get Yes No string
 *
 * @param $value Yes No value
 * @return string
 */
function getYesNo($value){
	global $LANG;
	if ($value){
		return $LANG["choice"][1];
	} else {
		return $LANG["choice"][0];
	}
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
	global $LANG;
	echo "<select name='$name'>\n";
	if ($none)
		echo "<option value='' ".(empty($value)?" selected ":"").">".$LANG["profiles"][12]."</option>\n";
	if ($read)
		echo "<option value='r' ".($value=='r'?" selected ":"").">".$LANG["profiles"][10]."</option>\n";
	if ($write)
		echo "<option value='w' ".($value=='w'?" selected ":"").">".$LANG["profiles"][11]."</option>\n";
	echo "</select>\n";	
}	

/**
 * Make a select box for Tracking my devices
 *
 *
 * @param $userID User ID for my device section
 * @param $entity_restrict restrict to a specific entity
 * @return nothing (print out an HTML select box)
 */
function dropdownMyDevices($userID=0,$entity_restrict=-1){
	global $DB,$LANG,$CFG_GLPI,$LINK_ID_TABLE;

	if ($userID==0) $userID=$_SESSION["glpiID"];

	$rand=mt_rand();

	$already_add=array();

	if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware"]&pow(2,HELPDESK_MY_HARDWARE)){
		$my_devices="";

		$ci=new CommonItem();
		$my_item="";

		if (isset($_SESSION["helpdeskSaved"]["_my_items"])) $my_item=$_SESSION["helpdeskSaved"]["_my_items"];

		// My items
		foreach ($CFG_GLPI["linkuser_types"] as $type){
			if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware_type"]&pow(2,$type)){
				$query="SELECT * FROM ".$LINK_ID_TABLE[$type]." WHERE FK_users='".$userID."' AND deleted='0' ";
				if (in_array($LINK_ID_TABLE[$type],$CFG_GLPI["template_tables"])){
					$query.=" AND is_template='0' ";
				}
				$query.=getEntitiesRestrictRequest("AND",$LINK_ID_TABLE[$type],"",$entity_restrict);
				$query.=" ORDER BY name ";

				$result=$DB->query($query);
				if ($DB->numrows($result)>0){
					$ci->setType($type);
					$type_name=$ci->getType();
					
					while ($data=$DB->fetch_array($result)){
						$output=$data["name"];
						if ($type!=SOFTWARE_TYPE){
						$output.=" - ".$data['serial']." - ".$data['otherserial'];
						}
						if (empty($output)||$_SESSION["glpiview_ID"]) $output.=" (".$data['ID'].")";
						$my_devices.="<option title=\"$output\" value='".$type."_".$data["ID"]."' ".($my_item==$type."_".$data["ID"]?"selected":"").">";
						$my_devices.="$type_name - ".substr($output,0,$_SESSION["glpidropdown_limit"]);
						$my_devices.="</option>";

						$already_add[$type][]=$data["ID"];
					}
				}
			}
		}
		if (!empty($my_devices)){
			$my_devices="<optgroup label=\"".$LANG["tracking"][1]."\">".$my_devices."</optgroup>";
		}


		// My group items
		if (haveRight("show_group_hardware","1")){
			$group_where="";
			$groups=array();
			$query="SELECT glpi_users_groups.FK_groups, glpi_groups.name FROM glpi_users_groups LEFT JOIN glpi_groups ON (glpi_groups.ID = glpi_users_groups.FK_groups) WHERE glpi_users_groups.FK_users='".$userID."' ";
			$query.=getEntitiesRestrictRequest("AND","glpi_groups","",$entity_restrict);
			$result=$DB->query($query);
			$first=true;
			if ($DB->numrows($result)>0){
				while ($data=$DB->fetch_array($result)){
					if ($first) $first=false;
					else $group_where.=" OR ";
	
					$group_where.=" FK_groups = '".$data["FK_groups"]."' ";
				}

				$tmp_device="";
				foreach ($CFG_GLPI["linkuser_types"] as $type){
					if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware_type"]&pow(2,$type))
					{
						$query="SELECT * FROM ".$LINK_ID_TABLE[$type]." WHERE ($group_where) AND deleted='0' ";
						$query.=getEntitiesRestrictRequest("AND",$LINK_ID_TABLE[$type],"",$entity_restrict);
						$result=$DB->query($query);
						if ($DB->numrows($result)>0){
							$ci->setType($type);
							$type_name=$ci->getType();
							if (!isset($already_add[$type])) $already_add[$type]=array();
							while ($data=$DB->fetch_array($result)){
								if (!in_array($data["ID"],$already_add[$type])){
									$output=$data["name"];
									if ($type!=SOFTWARE_TYPE){
										$output.=" - ".$data['serial']." - ".$data['otherserial'];
									}

									if (empty($output)||$_SESSION["glpiview_ID"]) $output.=" (".$data['ID'].")";
									$tmp_device.="<option title=\"$output\" value='".$type."_".$data["ID"]."' ".($my_item==$type."_".$data["ID"]?"selected":"").">";
									$tmp_device.="$type_name - ".substr($output,0,$_SESSION["glpidropdown_limit"]);
									$tmp_device.="</option>";
									$already_add[$type][]=$data["ID"];
								}
							}
						}
					}
				}
				if (!empty($tmp_device)){
					$my_devices.="<optgroup label=\"".$LANG["tracking"][1]." - ".$LANG["common"][35]."\">".$tmp_device."</optgroup>";
				}
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

			$tmp_device="";
			// Direct Connection
			$types=array(PERIPHERAL_TYPE,MONITOR_TYPE,PRINTER_TYPE,PHONE_TYPE);
			foreach ($types as $type){
				if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware_type"]&pow(2,$type)){
					if (!isset($already_add[$type])) $already_add[$type]=array();
					$query="SELECT DISTINCT ".$LINK_ID_TABLE[$type].".* FROM glpi_connect_wire LEFT JOIN ".$LINK_ID_TABLE[$type]." ON (glpi_connect_wire.end1=".$LINK_ID_TABLE[$type].".ID) WHERE glpi_connect_wire.type='$type' AND  ".str_replace("XXXX","glpi_connect_wire.end2",$search_computer)." AND ".$LINK_ID_TABLE[$type].".deleted='0' ";
					if (in_array($LINK_ID_TABLE[$type],$CFG_GLPI["template_tables"])){
						$query.=" AND is_template='0' ";
					}
					$query.=getEntitiesRestrictRequest("AND",$LINK_ID_TABLE[$type],"",$entity_restrict);
					$query.=" ORDER BY ".$LINK_ID_TABLE[$type].".name";

					$result=$DB->query($query);
					if ($DB->numrows($result)>0){
						$ci->setType($type);
						$type_name=$ci->getType();
							while ($data=$DB->fetch_array($result)){
							if (!in_array($data["ID"],$already_add[$type])){
								$output=$data["name"];
								if ($type!=SOFTWARE_TYPE){
									$output.=" - ".$data['serial']." - ".$data['otherserial'];
								}
								if (empty($output)||$_SESSION["glpiview_ID"]) $output.=" (".$data['ID'].")";
								$tmp_device.="<option title=\"$output\" value='".$type."_".$data["ID"]."' ".($my_item==$type."_".$data["ID"]?"selected":"").">";
								$tmp_device.="$type_name - ".substr($output,0,$_SESSION["glpidropdown_limit"]);
								$tmp_device.="</option>";

								$already_add[$type][]=$data["ID"];
							}
						}
					}
				}
			}
			if (!empty($tmp_device)){
				$my_devices.="<optgroup label=\"".$LANG["reports"][36]."\">".$tmp_device."</optgroup>";
			}
				
			// Software
			if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware_type"]&pow(2,SOFTWARE_TYPE)){
				$query = "SELECT DISTINCT glpi_softwareversions.name as version, glpi_software.name as name, glpi_software.ID as ID FROM glpi_inst_software, glpi_software,glpi_softwareversions ";
				$query.= "WHERE glpi_inst_software.vID = glpi_softwareversions.ID AND glpi_softwareversions.sID = glpi_software.ID AND ".str_replace("XXXX","glpi_inst_software.cID",$search_computer)." AND  glpi_software.helpdesk_visible=1 ";
				$query.=getEntitiesRestrictRequest("AND","glpi_software","",$entity_restrict);
				$query.=" ORDER BY glpi_software.name";

				$result=$DB->query($query);
				if ($DB->numrows($result)>0){
					$tmp_device="";
					$ci->setType(SOFTWARE_TYPE);
					$type_name=$ci->getType();
					if (!isset($already_add[SOFTWARE_TYPE])) $already_add[SOFTWARE_TYPE]=array();
					while ($data=$DB->fetch_array($result)){
						if (!in_array($data["ID"],$already_add[SOFTWARE_TYPE])){
							$tmp_device.="<option value='".SOFTWARE_TYPE."_".$data["ID"]."' ".($my_item==SOFTWARE_TYPE."_".$data["ID"]?"selected":"").">$type_name - ".$data["name"]." (v. ".$data["version"].")".($_SESSION["glpiview_ID"]?" (".$data["ID"].")":"")."</option>";
							$already_add[SOFTWARE_TYPE][]=$data["ID"];
						}
					}
					if (!empty($tmp_device)){
						$my_devices.="<optgroup label=\"".ucfirst($LANG["software"][17])."\">".$tmp_device."</optgroup>";
					}
				}
			}
		}
		echo "<div id='tracking_my_devices'>";
		echo $LANG["tracking"][1].":&nbsp;<select id='my_items' name='_my_items'><option value=''>--- ".$LANG["help"][30]." ---</option>$my_devices</select></div>";
	}

}
/**
 * Make a select box for Tracking All Devices
 *
 * @param $myname select name
 * @param $value preselected value.
 * @param $admin is an admin access ? 
 * @param $entity_restrict Restrict to a defined entity
 * @return nothing (print out an HTML select box)
 */
function dropdownTrackingAllDevices($myname,$value,$admin=0,$entity_restrict=-1){
	global $LANG,$CFG_GLPI,$DB,$LINK_ID_TABLE;

	$rand=mt_rand();

	if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware"]==0){
		echo "<input type='hidden' name='$myname' value='0'>";
		echo "<input type='hidden' name='computer' value='0'>";
	} else {
		echo "<div id='tracking_all_devices'>";

		if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware"]&pow(2,HELPDESK_ALL_HARDWARE)){
			// Display a message if view my hardware
			if (!$admin&&$_SESSION["glpiactiveprofile"]["helpdesk_hardware"]&pow(2,HELPDESK_MY_HARDWARE)){
				echo $LANG["tracking"][2].":<br>";
			}
			echo "<select id='search_$myname$rand' name='$myname'>\n";
			echo "<option value='0' ".(($value==0)?" selected":"").">".$LANG["help"][30]."</option>\n";
			// Also display type if selected
			if ($value==COMPUTER_TYPE||$_SESSION["glpiactiveprofile"]["helpdesk_hardware_type"]&pow(2,COMPUTER_TYPE))
				echo "<option value='".COMPUTER_TYPE."' ".(($value==COMPUTER_TYPE)?" selected":"").">".$LANG["help"][25]."</option>\n";
			if ($value==NETWORKING_TYPE||$_SESSION["glpiactiveprofile"]["helpdesk_hardware_type"]&pow(2,NETWORKING_TYPE))
				echo "<option value='".NETWORKING_TYPE."' ".(($value==NETWORKING_TYPE)?" selected":"").">".$LANG["help"][26]."</option>\n";
			if ($value==PRINTER_TYPE||$_SESSION["glpiactiveprofile"]["helpdesk_hardware_type"]&pow(2,PRINTER_TYPE))
				echo "<option value='".PRINTER_TYPE."' ".(($value==PRINTER_TYPE)?" selected":"").">".$LANG["help"][27]."</option>\n";
			if ($value==MONITOR_TYPE||$_SESSION["glpiactiveprofile"]["helpdesk_hardware_type"]&pow(2,MONITOR_TYPE))
				echo "<option value='".MONITOR_TYPE."' ".(($value==MONITOR_TYPE)?" selected":"").">".$LANG["help"][28]."</option>\n";
			if ($value==PERIPHERAL_TYPE||$_SESSION["glpiactiveprofile"]["helpdesk_hardware_type"]&pow(2,PERIPHERAL_TYPE))
				echo "<option value='".PERIPHERAL_TYPE."' ".(($value==PERIPHERAL_TYPE)?" selected":"").">".$LANG["help"][29]."</option>\n";
			if ($value==SOFTWARE_TYPE||$_SESSION["glpiactiveprofile"]["helpdesk_hardware_type"]&pow(2,SOFTWARE_TYPE))
				echo "<option value='".SOFTWARE_TYPE."' ".(($value==SOFTWARE_TYPE)?" selected":"").">".$LANG["help"][31]."</option>\n";
			if ($value==PHONE_TYPE||$_SESSION["glpiactiveprofile"]["helpdesk_hardware_type"]&pow(2,PHONE_TYPE))
				echo "<option value='".PHONE_TYPE."' ".(($value==PHONE_TYPE)?" selected":"").">".$LANG["help"][35]."</option>\n";
			echo "</select>\n";

			$params=array('type'=>'__VALUE__',
					'entity_restrict'=>$entity_restrict,
					'admin'=>$admin,
					'myname'=>"computer",
					);

			ajaxUpdateItemOnSelectEvent("search_$myname$rand","results_$myname$rand",$CFG_GLPI["root_doc"]."/ajax/dropdownTrackingDeviceType.php",$params);

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
		}
		echo "</div>";
	}		
	return $rand;
}

/**
 * Make a select box for connections
 *
 * @param $type type to connect
 * @param $fromtype from where the connection is
 * @param $myname select name
 * @param $onlyglobal display only global devices (used for templates)
 * @param $entity_restrict Restrict to a defined entity
 * @return nothing (print out an HTML select box)
 */
function dropdownConnect($type,$fromtype,$myname,$entity_restrict=-1,$onlyglobal=0) {
	global $CFG_GLPI,$LINK_ID_TABLE;

	$rand=mt_rand();

	$use_ajax=false;
	if ($CFG_GLPI["use_ajax"]){
		$nb=0;
		if ($entity_restrict>=0){
			$nb=countElementsInTableForEntity($LINK_ID_TABLE[$type],$entity_restrict);
		} else {
			$nb=countElementsInTableForMyEntities($LINK_ID_TABLE[$type]);
		}
		if ($nb>$CFG_GLPI["ajax_limit_count"]){
			$use_ajax=true;
		}
	}

        $params=array('searchText'=>'__VALUE__',
                        'fromtype'=>$fromtype,
                        'idtable'=>$type,
                        'myname'=>$myname,
                        'onlyglobal'=>$onlyglobal,
                        'entity_restrict'=>$entity_restrict,
                        );
	
	$default="<select name='$myname'><option value='0'>------</option></select>\n";
	ajaxDropdown($use_ajax,"/ajax/dropdownConnect.php",$params,$default,$rand);

	return $rand;
}


/**
 * Make a select box for  connected port
 *
 * @param $ID ID of the current port to connect
 * @param $type type of device where to search ports
 * @param $myname select name
 * @param $entity_restrict Restrict to a defined entity (or an array of entities)
 * @return nothing (print out an HTML select box)
 */
function dropdownConnectPort($ID,$type,$myname,$entity_restrict=-1) {
	global $LANG,$CFG_GLPI;

	$rand=mt_rand();
	echo "<select name='type[$ID]' id='item_type$rand'>\n";
	echo "<option value='0'>-----</option>\n";

	$ci =new CommonItem();

	foreach ($CFG_GLPI["netport_types"] as $type){
		$ci->setType($type);
		echo "<option value='".$type."'>".$ci->getType()."</option>\n";
	}

	echo "</select>\n";


	$params=array('type'=>'__VALUE__',
			'entity_restrict'=>$entity_restrict,
			'current'=>$ID,
			'myname'=>$myname,
			);

	ajaxUpdateItemOnSelectEvent("item_type$rand","show_$myname$rand",$CFG_GLPI["root_doc"]."/ajax/dropdownConnectPortDeviceType.php",$params);

	echo "<span id='show_$myname$rand'>&nbsp;</span>\n";

	return $rand;
}




/**
 * Make a select box for link document
 *
 * @param $myname name of the select box
 * @param $entity_restrict restrict multi entity
 * @param $used Already used items : not to display in dropdown
 * @return nothing (print out an HTML select box)
 */
function dropdownDocument($myname,$entity_restrict='',$used=array()) {
	global $DB,$LANG,$CFG_GLPI;

	$rand=mt_rand();

	$where=" WHERE glpi_docs.deleted='0' ";
	$where.=getEntitiesRestrictRequest("AND","glpi_docs",'',$entity_restrict,true);
	if (count($used)) {
		$where .= " AND ID NOT IN (0";
		foreach ($used as $ID)
			$where .= ",$ID";
		$where .= ")";
	}


	$query="SELECT * FROM glpi_dropdown_rubdocs WHERE ID IN (SELECT DISTINCT rubrique FROM glpi_docs $where) ORDER BY name";
	//error_log($query);
	$result=$DB->query($query);

	echo "<select name='_rubdoc' id='rubdoc'>\n";
	echo "<option value='0'>------</option>\n";
	while ($data=$DB->fetch_assoc($result)){
		echo "<option value='".$data['ID']."'>".$data['name']."</option>\n";
	}
	echo "</select>\n";

	$params=array('rubdoc'=>'__VALUE__',
			'entity_restrict'=>$entity_restrict,
			'rand'=>$rand,
			'myname'=>$myname,
			'used'=>$used
			);

	ajaxUpdateItemOnSelectEvent("rubdoc","show_$myname$rand",$CFG_GLPI["root_doc"]."/ajax/dropdownRubDocument.php",$params);

	echo "<span id='show_$myname$rand'>";
	$_POST["entity_restrict"]=$entity_restrict;
	$_POST["rubdoc"]=0;
	$_POST["myname"]=$myname;
	$_POST["rand"]=$rand;
	$_POST["used"]=$used;
	include (GLPI_ROOT."/ajax/dropdownRubDocument.php");
	echo "</span>\n";

	return $rand;
}


/**
 * Make a select box for  software to install
 *
 *
 * @param $myname select name
 * @param $massiveaction is it a massiveaction select ?
 * @param $entity_restrict Restrict to a defined entity
 * @return nothing (print out an HTML select box)
 */
function dropdownSoftwareToInstall($myname,$entity_restrict,$massiveaction=0) {
	global $CFG_GLPI;

	$rand=mt_rand();

	$use_ajax=false;

	if ($CFG_GLPI["use_ajax"]){
		if(countElementsInTableForEntity("glpi_software",$entity_restrict)>$CFG_GLPI["ajax_limit_count"]){
			$use_ajax=true;
		}
	}

        $params=array('searchText'=>'__VALUE__',
			'myname'=>$myname,
                        'entity_restrict'=>$entity_restrict,
                        );
	
	$default="<select name='$myname'><option value='0'>------</option></select>\n";
	ajaxDropdown($use_ajax,"/ajax/dropdownSelectSoftware.php",$params,$default,$rand);

	return $rand;
}


/**
 * Make a select box for  software to install
 *
 *
 * @param $myname select name
 * @param $sID ID of the software
 * @param $value value of the selected version
 * @return nothing (print out an HTML select box)
 */
function dropdownSoftwareVersions($myname,$sID,$value=0) {
	global $CFG_GLPI;

	$rand=mt_rand();


	$params=array('sID'=>$sID,
		'myname'=>$myname,
		'value'=>$value,
		);

	$default="<select name='$myname'><option value='0'>------</option></select>\n";
	ajaxDropdown(false,"/ajax/dropdownInstallVersion.php",$params,$default,$rand);

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
 * @param $entity_restrict Restrict to a defined entity
 * @param $user_restrict Restrict to a specific user
 * @return nothing (print out an HTML div)
 */
function autocompletionTextField($myname,$table,$field,$value='',$size=40,$entity_restrict=-1,$user_restrict=-1,$option=''){
	global $CFG_GLPI;

	if ($CFG_GLPI["use_ajax"]&&$CFG_GLPI["ajax_autocompletion"]){
		$rand=mt_rand();
		echo "<input $option id='textfield_$myname$rand' type='text' name='$myname' value=\"".cleanInputText($value)."\" size='$size'>\n";
		echo "<script type='text/javascript' >\n";

		echo "var textfield_$myname$rand = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy(
			new Ext.data.Connection ({
				url: '".$CFG_GLPI["root_doc"]."/ajax/autocompletion.php',
				extraParams : {
					table: '$table',
					field: '$field',";
				
				if (!empty($entity_restrict)&&$entity_restrict>=0){
					echo "entity_restrict: $entity_restrict,";
				}
				if (!empty($user_restrict)&&$user_restrict>=0){
					echo "user_restrict: $user_restrict,";
				}
				echo "
				},
				method: 'POST'
				})
			),
			reader: new Ext.data.JsonReader({
				totalProperty: 'totalCount',
				root: 'items',
				id: 'value',
			}, [
			{name: 'value', mapping: 'value'},
			])
		});
";
		
		
	
		echo "var searchfield_$myname$rand = new Ext.ux.form.SpanComboBox({
			store: textfield_$myname$rand,
			displayField:'value',
			pageSize:20,
			hideTrigger:true,
			resizable:true,
			applyTo: 'textfield_$myname$rand',
		});";
	
		echo "</script>";

	}	else {
		echo "<input $option type='text' name='$myname' value=\"".cleanInputText($value)."\" size='$size'>\n";
	}
}


/**
 * Make a select box form  for device type 
 *
 * @param $target URL to post the form
 * @param $cID computer ID
 * @param $withtemplate is it a template computer ?
 * @return nothing (print out an HTML select box)
 */
function device_selecter($target,$cID,$withtemplate='') {
	global $LANG,$CFG_GLPI;

	if (!haveRight("computer","w")) return false;

	if(!empty($withtemplate) && $withtemplate == 2) {
		//do nothing
	} else {
		echo "<table class='tab_cadre_fixe'>";
		echo "<tr  class='tab_bg_1'><td colspan='2' align='right' width='30%'>";
		echo $LANG["devices"][0].":";
		echo "</td>";
		echo "<td colspan='63'>"; 
		echo "<form action=\"$target\" method=\"post\">";

		$rand=mt_rand();

		echo "<select name=\"new_device_type\" id='device$rand'>";

		echo "<option value=\"-1\">-----</option>";
		$devices=getDictDeviceLabel(-1);
		
		foreach ($devices as $i => $name){
			echo "<option value=\"$i\">$name</option>";
		}
		echo "</select>";

		$params=array('idtable'=>'__VALUE__',
				'myname'=>'new_device_id',
				);
	
		ajaxUpdateItemOnSelectEvent("device$rand","showdevice$rand",$CFG_GLPI["root_doc"]."/ajax/dropdownDevice.php",$params);

		echo "<span id='showdevice$rand'>&nbsp;</span>\n";

		echo "<input type=\"hidden\" name=\"withtemplate\" value=\"".$withtemplate."\" >";
		echo "<input type=\"hidden\" name=\"connect_device\" value=\"".true."\" >";
		echo "<input type=\"hidden\" name=\"cID\" value=\"".$cID."\" >";
		echo "<input type=\"submit\" class ='submit' value=\"".$LANG["buttons"][2]."\" >";
		echo "</form>";
		echo "</td>";
		echo "</tr></table>";
	}
}

/**
 * Dropdown of actions for massive action
 *
 * @param $device_type item type
 * @param $deleted massive action for deleted items ?
 */
function dropdownMassiveAction($device_type,$deleted=0,$extraparams=array()){
	global $LANG,$CFG_GLPI,$PLUGIN_HOOKS;

	$isadmin=haveTypeRight($device_type,"w");
	
	echo "<select name=\"massiveaction\" id='massiveaction'>";

	echo "<option value=\"-1\" selected>-----</option>";
	if (!in_array($device_type,array(MAILGATE_TYPE,OCSNG_TYPE,ENTITY_TYPE))
	&& ( $isadmin
		||(in_array($device_type,$CFG_GLPI["infocom_types"])&&haveTypeRight(INFOCOM_TYPE,"w"))
		|| ($device_type==TRACKING_TYPE&&haveRight('update_ticket',1)) 
		)
	){
		
		echo "<option value=\"update\">".$LANG["buttons"][14]."</option>";
	}

	if ($deleted){
		if ($isadmin){
			echo "<option value=\"purge\">".$LANG["buttons"][22]."</option>";
			echo "<option value=\"restore\">".$LANG["buttons"][21]."</option>";
		}
	} else {
		// No delete for entities and tracking of not have right
		if ($device_type!=ENTITY_TYPE
		&&( ($isadmin && $device_type!=TRACKING_TYPE)
			|| ($device_type==TRACKING_TYPE&&haveRight('delete_ticket',1))
		)){
			echo "<option value=\"delete\">".$LANG["buttons"][6]."</option>";
		}
		if ($isadmin && in_array($device_type,array(PHONE_TYPE,PRINTER_TYPE,PERIPHERAL_TYPE,MONITOR_TYPE))){
			echo "<option value=\"connect\">".$LANG["buttons"][9]."</option>";
			echo "<option value=\"disconnect\">".$LANG["buttons"][10]."</option>";
		}
		if (haveTypeRight(DOCUMENT_TYPE,"w") && in_array($device_type,array(CARTRIDGE_TYPE,COMPUTER_TYPE,CONSUMABLE_TYPE,CONTACT_TYPE,CONTRACT_TYPE,ENTERPRISE_TYPE,
				MONITOR_TYPE,NETWORKING_TYPE,PERIPHERAL_TYPE,PHONE_TYPE,PRINTER_TYPE,SOFTWARE_TYPE))){
			echo "<option value=\"add_document\">".$LANG["document"][16]."</option>";
		}

		if (haveTypeRight(CONTRACT_TYPE,"w") &&in_array($device_type,$CFG_GLPI["state_types"])){
			echo "<option value=\"add_contract\">".$LANG["financial"][36]."</option>";
		}
		if (haveRight('transfer','r') && isMultiEntitiesMode() && 
				in_array($device_type, 	array(CARTRIDGE_TYPE,COMPUTER_TYPE,CONSUMABLE_TYPE,CONTACT_TYPE,CONTRACT_TYPE,ENTERPRISE_TYPE,
				MONITOR_TYPE,NETWORKING_TYPE,PERIPHERAL_TYPE,PHONE_TYPE,PRINTER_TYPE,SOFTWARE_TYPE,TRACKING_TYPE,DOCUMENT_TYPE,GROUP_TYPE))
				&& $isadmin
			){
			echo "<option value=\"add_transfer_list\">".$LANG["buttons"][48]."</option>";
		}
		switch ($device_type){
			case SOFTWARE_TYPE :
				if ($isadmin && countElementsInTable("glpi_rules_descriptions","rule_type='".RULE_SOFTWARE_CATEGORY."'") > 0){
					echo "<option value=\"compute_software_category\">".$LANG["rulesengine"][38]." ".$LANG["rulesengine"][40]."</option>";
				}
				if (haveRight("rule_dictionnary_software","w") && countElementsInTable("glpi_rules_descriptions","rule_type='".RULE_DICTIONNARY_SOFTWARE."'") > 0){
					echo "<option value=\"replay_dictionnary\">".$LANG["rulesengine"][76]."</option>";
				}
			
				break;
			case COMPUTER_TYPE :
				if ($isadmin){
					echo "<option value=\"connect_to_computer\">".$LANG["buttons"][9]."</option>";
					echo "<option value=\"install\">".$LANG["buttons"][4]."</option>";
					if ($CFG_GLPI['ocs_mode']){
						if (haveRight("ocsng","w") || haveRight("sync_ocsng","w")){
							echo "<option value=\"force_ocsng_update\">".$LANG["ocsng"][24]."</option>";
						}
						echo "<option value=\"unlock_ocsng_field\">".$LANG["buttons"][38]." ".$LANG["Menu"][33]." - ".$LANG["ocsng"][16]."</option>";
						echo "<option value=\"unlock_ocsng_monitor\">".$LANG["buttons"][38]." ".$LANG["Menu"][33]." - ".$LANG["ocsng"][30]."</option>";
						echo "<option value=\"unlock_ocsng_peripheral\">".$LANG["buttons"][38]." ".$LANG["Menu"][33]." - ".$LANG["ocsng"][32]."</option>";
						echo "<option value=\"unlock_ocsng_printer\">".$LANG["buttons"][38]." ".$LANG["Menu"][33]." - ".$LANG["ocsng"][34]."</option>";
						echo "<option value=\"unlock_ocsng_software\">".$LANG["buttons"][38]." ".$LANG["Menu"][33]." - ".$LANG["ocsng"][52]."</option>";
						echo "<option value=\"unlock_ocsng_ip\">".$LANG["buttons"][38]." ".$LANG["Menu"][33]." - ".$LANG["ocsng"][50]."</option>";
						echo "<option value=\"unlock_ocsng_disk\">".$LANG["buttons"][38]." ".$LANG["Menu"][33]." - ".$LANG["ocsng"][55]."</option>";
					}
				}
				break;
			case ENTERPRISE_TYPE :
				if ($isadmin){
					echo "<option value=\"add_contact\">".$LANG["financial"][24]."</option>";
				}
				break;
			case CONTACT_TYPE :
				if ($isadmin){
					echo "<option value=\"add_enterprise\">".$LANG["financial"][25]."</option>";
				}
				break;
			case USER_TYPE :
				if ($isadmin){
					echo "<option value=\"add_group\">".$LANG["setup"][604]."</option>";
					echo "<option value=\"add_userprofile\">".$LANG["setup"][607]."</option>";
				}

				if (haveRight("user","w")){
					echo "<option value=\"force_user_ldap_update\">".$LANG["ocsng"][24]."</option>";
				}

				break;
			case TRACKING_TYPE :
				if (haveRight("comment_all_ticket","1")){
					echo "<option value=\"add_followup\">".$LANG["job"][29]."</option>";
				}
				break;
		}

		// Plugin Specific actions
		if (isset($PLUGIN_HOOKS['use_massive_action'])){
			foreach ($PLUGIN_HOOKS['use_massive_action'] as $plugin => $val){
				$actions=doOneHook($plugin,'MassiveActions',$device_type);
				if (count($actions)){
					foreach ($actions as $key => $val){
						echo "<option value=\"$key\">$val</option>";
					}
				}
			}
		} 


	}
	echo "</select>";

	$params=array('action'=>'__VALUE__',
			'deleted'=>$deleted,
			'type'=>$device_type,
			);
	
	if (count($extraparams)){
		foreach ($extraparams as $key => $val){
			$params['extra_'.$key]=$val;
		}
	}
	
	ajaxUpdateItemOnSelectEvent("massiveaction","show_massiveaction",$CFG_GLPI["root_doc"]."/ajax/dropdownMassiveAction.php",$params);

	echo "<span id='show_massiveaction'>&nbsp;</span>\n";
}

/**
 * Dropdown of actions for massive action of networking ports
 *
 * @param $device_type item type
 */
function dropdownMassiveActionPorts($device_type){
	global $LANG,$CFG_GLPI;

	echo "<select name=\"massiveaction\" id='massiveaction'>";

	echo "<option value=\"-1\" selected>-----</option>";
	echo "<option value=\"delete\">".$LANG["buttons"][6]."</option>";
	echo "<option value=\"assign_vlan\">".$LANG["networking"][55]."</option>";
	echo "<option value=\"unassign_vlan\">".$LANG["networking"][58]."</option>";
	echo "<option value=\"move\">".$LANG["buttons"][20]."</option>";
	echo "</select>";


	$params=array('action'=>'__VALUE__',
			'type'=>$device_type,
			);
	
	ajaxUpdateItemOnSelectEvent("massiveaction","show_massiveaction",$CFG_GLPI["root_doc"]."/ajax/dropdownMassiveActionPorts.php",$params);

	echo "<span id='show_massiveaction'>&nbsp;</span>\n";
}

/**
 * Dropdown for global item management
 *
 * @param $target target for actions
 * @param $withtemplate template or basic computer
 * @param $ID item ID
 * @param $value value of global state
 * @param $management_restrict global management restrict mode
 */
function globalManagementDropdown($target,$withtemplate,$ID,$value,$management_restrict=0){
	global $LANG,$CFG_GLPI;	
	if ($value&&empty($withtemplate)) {
		echo $LANG["peripherals"][31];

		echo "&nbsp;<a title=\"".$LANG["common"][39]."\" href=\"javascript:confirmAction('".addslashes($LANG["common"][40])."\\n".addslashes($LANG["common"][39])."','$target?unglobalize=unglobalize&amp;ID=$ID')\">".$LANG["common"][38]."</a>&nbsp;";	

		echo "<img alt=\"".$LANG["common"][39]."\" title=\"".$LANG["common"][39]."\" src=\"".$CFG_GLPI["root_doc"]."/pics/aide.png\">";
	} else {

		if ($management_restrict == 2){
			echo "<select name='is_global'>";
			echo "<option value='0' ".(!$value?" selected":"").">".$LANG["peripherals"][32]."</option>";
			echo "<option value='1' ".($value?" selected":"").">".$LANG["peripherals"][31]."</option>";
			echo "</select>";
		} else {
			// Templates edition
			if (!empty($withtemplate)){
				echo "<input type='hidden' name='is_global' value=\"".$management_restrict."\">";
				echo (!$management_restrict?$LANG["peripherals"][32]:$LANG["peripherals"][31]);
			} else {
				echo (!$value?$LANG["peripherals"][32]:$LANG["peripherals"][31]);
			}
		}

	}
}
/**
 * Dropdown for alerting of contracts
 *
* @param $myname select name
 * @param $value default value
 */
function dropdownContractAlerting($myname,$value){
	global $LANG;
	echo "<select name='$myname'>";
	echo "<option value='0' ".($value==0?"selected":"")." >-------</option>";
	echo "<option value='".pow(2,ALERT_END)."' ".($value==pow(2,ALERT_END)?"selected":"")." >".$LANG["buttons"][32]."</option>";
	echo "<option value='".pow(2,ALERT_NOTICE)."' ".($value==pow(2,ALERT_NOTICE)?"selected":"")." >".$LANG["financial"][10]."</option>";
	echo "<option value='".(pow(2,ALERT_END)+pow(2,ALERT_NOTICE))."' ".($value==(pow(2,ALERT_END)+pow(2,ALERT_NOTICE))?"selected":"")." >".$LANG["buttons"][32]." + ".$LANG["financial"][10]."</option>";
	echo "</select>";

}


/**
 * Print a select with hours
 *
 * Print a select named $name with hours options and selected value $value
 *
 *@param $name string : HTML select name
 *@param $value integer : HTML select selected value
 *@param $limit_planning limit planning to the configuration range
 *
 *@return Nothing (display)
 *
 **/
function dropdownHours($name,$value,$limit_planning=0){
	global $CFG_GLPI;

	$begin=0;
	$end=24;
	$step=$CFG_GLPI["time_step"];
	// Check if the $step is Ok for the $value field
	$split=explode(":",$value);
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
		$plan_begin=explode(":",$CFG_GLPI["planning_begin"]);
		$plan_end=explode(":",$CFG_GLPI["planning_end"]);
		$begin=(int) $plan_begin[0];
		$end=(int) $plan_end[0];
	}
	echo "<select name=\"$name\">";
	for ($i=$begin;$i<$end;$i++){
		if ($i<10)
			$tmp="0".$i;
		else $tmp=$i;

		for ($j=0;$j<60;$j+=$step){
			if ($j<10) $val=$tmp.":0$j";
			else $val=$tmp.":$j";

			echo "<option value='$val' ".($value==$val.":00"||$value==$val?" selected ":"").">$val</option>";
		}
	}
	// Last item
	$val=$end.":00";
	echo "<option value='$val' ".($value==$val.":00"||$value==$val?" selected ":"").">$val</option>";
	echo "</select>";	
}	

/**
 * Dropdown licenses for a software
 *
* @param $myname select name
 * @param $sID software ID
 */
function dropdownLicenseOfSoftware($myname,$sID) {
	global $DB,$LANG;

	$query="SELECT * FROM glpi_licenses 
		WHERE sID='$sID' 
		GROUP BY version, serial, expire, oem, oem_computer, buy 
		ORDER BY version, serial, expire, oem, oem_computer, buy";
	$result=$DB->query($query);
	if ($DB->numrows($result)){
		echo "<select name='$myname'>";
		while ($data=$DB->fetch_array($result)){
			echo "<option value='".$data["ID"]."'>".$data["version"]." - ".$data["serial"];
			if ($data["expire"]!=NULL) echo " - ".$LANG["software"][25]." ".$data["expire"];
			else echo " - ".$LANG["software"][26];
			if ($data["buy"]) echo " - ".$LANG["software"][35];
			else echo " - ".$LANG["software"][37];
			if ($data["oem"]) echo " - ".$LANG["software"][28];
			echo "</option>";
		}
		echo "</select>";
	}

}

/**
 * Dropdown integers
 *
* @param $myname select name
 * @param $value default value
 * @param $min min value
 * @param $max max value
 * @param $step step used
 * @param $toadd values to add at the beginning
 */
function dropdownInteger($myname,$value,$min=0,$max=100,$step=1,$toadd=array()){

	echo "<select name='$myname'>\n";
	if (count($toadd)){
		foreach ($toadd as $key => $val){
			echo "<option value='$key' ".($key==$value?" selected ":"").">$val</option>";
		}
	}
	for ($i=$min;$i<=$max;$i+=$step){
		echo "<option value='$i' ".($i==$value?" selected ":"").">$i</option>";
	}
	echo "</select>";

}
/**
 * Dropdown available languages
 *
* @param $myname select name
 * @param $value default value
 */
function dropdownLanguages($myname,$value){
	global $CFG_GLPI;
	echo "<select name='$myname'>";

	foreach ($CFG_GLPI["languages"] as $key => $val){
		if (isset($val[1])&&is_file(GLPI_ROOT ."/locales/".$val[1])){
			echo "<option value=\"".$key."\"";
			if ($value==$key) { echo " selected"; }
			echo ">".$val[0]." ($key)";
		}
	}
	echo "</select>";
}

/**
 * Display entities of the loaded profile
 *
* @param $myname select name
 * @param $target target for entity change action
 */
function displayActiveEntities($target,$myname){
	global $CFG_GLPI,$LANG;
	$rand=mt_rand();
	
	echo "<div class='center' ><span class='b'>".$LANG["entity"][10]." ( <img src=\"".$CFG_GLPI["root_doc"]."/pics/entity_all.png\" alt=''> ".$LANG["entity"][11].")</span><br>";
	echo "<a style='font-size:14px;' href='".$target."?active_entity=all' title=\"".$LANG["buttons"][40]."\">".str_replace(" ","&nbsp;",$LANG["buttons"][40])."</a></div>";

	echo "<div class='left' style='width:100%'>";
	

	echo "<script type='javascript'>";
	echo "var Tree_Category_Loader = new Ext.tree.TreeLoader({
		dataUrl   :'".$CFG_GLPI["root_doc"]."/ajax/entitytreesons.php'
	});";

	echo "var Tree_Category = new Ext.tree.TreePanel({
		collapsible      : false,
		animCollapse     : false,
		border           : false,
		id               : 'tree_projectcategory$rand',
		el               : 'tree_projectcategory$rand',
		autoScroll       : true,
		animate          : false,
		enableDD         : true,
		containerScroll  : true,
		height           : 320,
		width            : 770,
		loader           : Tree_Category_Loader,
		rootVisible 	 : false,
	});";
	
	// SET the root node.
	echo "var Tree_Category_Root = new Ext.tree.AsyncTreeNode({
		text		: '',
		draggable	: false,
		id		: '-1'                  // this IS the id of the startnode
	});
		
	Tree_Category.setRootNode(Tree_Category_Root);";

	echo "// Render the tree.
		Tree_Category.render();
		Tree_Category_Root.expand();";

		echo "</script>";
	

		echo "<div id='tree_projectcategory$rand' ></div>";


/*		$class=" class='tree' ";
		$raquo="&raquo;";
		$fsize=16;
		$level=0;
		$havesons=false;
		if ($recursive && count(getEntitySons($ID))){
			$class=" class='treeroot' ";
			$raquo="<a href=\"javascript:showHideDiv('entity_subitem_$ID','entity_subitem_icon_$ID','" . $CFG_GLPI["root_doc"] . "/pics/expand.gif','" . $CFG_GLPI["root_doc"] . "/pics/collapse.gif');\"><img name='entity_subitem_icon_$ID' src=\"".$CFG_GLPI["root_doc"]."/pics/expand.gif\" alt=''></a>";
			$havesons=true;
		}
		$name=getDropdownName('glpi_entities',$ID);
		echo "<div $class>".str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", max(1,$level)),$raquo."&nbsp;<a style='font-size:".$fsize."px;' title=\"$name\" href='".$target."?active_entity=$ID'>".str_replace(" ","&nbsp;",$name)."</a>";

			echo "<div id='entity_subitem_$ID'>uuu";
			//displayEntityTree($target,$myname,$data['tree'],$level+1);
			echo "</div>";
		
		echo "</div>";

		//displayEntityTree($target,$myname,$ID,$recursive);
*/
	echo "</div>";
}
/**
 * Display entities tree 
 *
 * @param $myname select name
 * @param $target target for entity change action
 * @param $ID ID of the root entity
 * @param $level current level displayed
 */
function displayEntityTree($target,$myname,$ID,$recursive,$level=0){
	global $CFG_GLPI,$LANG;


	if (count($tree)){
		// Is multiple items to display ? only one expand it if have subitems
		foreach ($tree as $ID => $data){
			if (isset($data['name'])){
				$class=" class='tree' ";
				$raquo="&raquo;";
				$fsize=max(16-2*$level,12);

				// 
				$subitems=0;
				if (isset($data['tree'])&&count($data['tree'])){
					$subitems=count($data['tree']);
					if ($subitems>1){
						$raquo="<a href=\"javascript:showHideDiv('entity_subitem_$ID','entity_subitem_icon_$ID','" . $CFG_GLPI["root_doc"] . "/pics/expand.gif','" . $CFG_GLPI["root_doc"] . "/pics/collapse.gif');\"><img name='entity_subitem_icon_$ID' src=\"".$CFG_GLPI["root_doc"]."/pics/expand.gif\" alt=''></a>";
					}
				}
				
				if ($level==0){
					$class=" class='treeroot' ";
					$raquo="";
				} 


				echo "<div $class>".str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", max(1,$level)).$raquo."&nbsp;<a style='font-size:".$fsize."px;' title=\"".$data['name']."\" href='".$target."?active_entity=$ID'>".str_replace(" ","&nbsp;",$data['name'])."</a>";
				
				if ($subitems){
					echo "&nbsp;&nbsp;<a title=\"".$LANG["buttons"][40]."\" href='".$target."?active_entity=$ID&amp;recursive=1'><img alt=\"".$LANG["buttons"][40]."\" src='".$CFG_GLPI["root_doc"]."/pics/entity_all.png'></a></div>";
					if ($level!=0 && $subitems>1){
						echo "<div id='entity_subitem_$ID' style='display: none;'>";
						displayEntityTree($target,$myname,$data['tree'],$level+1);
						echo "</div>";
					}else {
						displayEntityTree($target,$myname,$data['tree'],$level+1);
					}
				} else {
					echo "&nbsp;</div>";
				}
			}
		}
	}
}



/**
 * Dropdown of ticket status 
 *
 * @param $name select name
 * @param $value default value
 */
function dropdownStatus($name,$value=0){
	global $LANG;

	echo "<select name='$name'>";
	echo "<option value='new' ".($value=="new"?" selected ":"").">".$LANG["joblist"][9]."</option>";
	echo "<option value='assign' ".($value=="assign"?" selected ":"").">".$LANG["joblist"][18]."</option>";
	echo "<option value='plan' ".($value=="plan"?" selected ":"").">".$LANG["joblist"][19]."</option>";
	echo "<option value='waiting' ".($value=="waiting"?" selected ":"").">".$LANG["joblist"][26]."</option>";
	echo "<option value='old_done' ".($value=="old_done"?" selected ":"").">".$LANG["joblist"][10]."</option>";
	echo "<option value='old_notdone' ".($value=="old_notdone"?" selected ":"").">".$LANG["joblist"][17]."</option>";
	echo "</select>";	
}

/**
 * Get ticket status Name
 *
 * @param $value status ID
 */
function getStatusName($value){
	global $LANG;

	switch ($value){
		case "new" :
			return $LANG["joblist"][9];
		break;
		case "assign" :
			return $LANG["joblist"][18];
		break;
		case "plan" :
			return $LANG["joblist"][19];
		break;
		case "waiting" :
			return $LANG["joblist"][26];
		break;
		case "old_done" :
			return $LANG["joblist"][10];
		break;
		case "old_notdone" :
			return $LANG["joblist"][17];
		break;
	}	
}

/**
 * Dropdown of ticket priority 
 *
 * @param $name select name
 * @param $value default value
 * @param $complete see also at least selection
 */
function dropdownPriority($name,$value=0,$complete=0){
	global $LANG;

	echo "<select name='$name'>";
	if ($complete){
		echo "<option value='0' ".($value==1?" selected ":"").">".$LANG["common"][66]."</option>";
		echo "<option value='-5' ".($value==-5?" selected ":"").">".$LANG["search"][16]." ".$LANG["help"][3]."</option>";
		echo "<option value='-4' ".($value==-4?" selected ":"").">".$LANG["search"][16]." ".$LANG["help"][4]."</option>";
		echo "<option value='-3' ".($value==-3?" selected ":"").">".$LANG["search"][16]." ".$LANG["help"][5]."</option>";
		echo "<option value='-2' ".($value==-2?" selected ":"").">".$LANG["search"][16]." ".$LANG["help"][6]."</option>";
		echo "<option value='-1' ".($value==-1?" selected ":"").">".$LANG["search"][16]." ".$LANG["help"][7]."</option>";
	}
	echo "<option value='5' ".($value==5?" selected ":"").">".$LANG["help"][3]."</option>";
	echo "<option value='4' ".($value==4?" selected ":"").">".$LANG["help"][4]."</option>";
	echo "<option value='3' ".($value==3?" selected ":"").">".$LANG["help"][5]."</option>";
	echo "<option value='2' ".($value==2?" selected ":"").">".$LANG["help"][6]."</option>";
	echo "<option value='1' ".($value==1?" selected ":"").">".$LANG["help"][7]."</option>";

	echo "</select>";	
}

/**
 * Get ticket priority Name
 *
 * @param $value status ID
 */
function getPriorityName($value){
	global $LANG;

	switch ($value){
		case 5 :
			return $LANG["help"][3];
			break;
		case 4 :
			return $LANG["help"][4];
			break;
		case 3 :
			return $LANG["help"][5];
			break;
		case 2 :
			return $LANG["help"][6];
			break;
		case 1 :
			return $LANG["help"][7];
			break;
	}	
}
/**
 * Get ticket request type name
 *
 * @param $value status ID
 */
function getRequestTypeName($value){
	global $LANG;

	switch ($value){
		case 1 :
			return $LANG["Menu"][31];
			break;
		case 2 :
			return $LANG["setup"][14];
			break;
		case 3 :
			return $LANG["help"][35];
			break;
		case 4 :
			return $LANG["tracking"][34];
			break;
		case 5 :
			return $LANG["tracking"][35];
			break;
		case 6 :
			return $LANG["common"][62];
			break;
		default : return "";
	}	
}
/**
 * Dropdown of ticket request type 
 *
 * @param $name select name
 * @param $value default value
 */
function dropdownRequestType($name,$value=0){
	global $LANG;

	echo "<select name='$name'>";
	echo "<option value='0' ".($value==0?" selected ":"").">-----</option>";
	echo "<option value='1' ".($value==1?" selected ":"").">".$LANG["Menu"][31]."</option>"; // Helpdesk
	echo "<option value='2' ".($value==2?" selected ":"").">".$LANG["setup"][14]."</option>"; // mail
	echo "<option value='3' ".($value==3?" selected ":"").">".$LANG["help"][35]."</option>"; // phone
	echo "<option value='4' ".($value==4?" selected ":"").">".$LANG["tracking"][34]."</option>"; // direct
	echo "<option value='5' ".($value==5?" selected ":"").">".$LANG["tracking"][35]."</option>"; // writing
	echo "<option value='6' ".($value==6?" selected ":"").">".$LANG["common"][62]."</option>"; // other

	echo "</select>";	
}

/**
 * Dropdown of amortissement type for infocoms
 *
 * @param $name select name
 * @param $value default value
 */
function dropdownAmortType($name,$value=0){
	global $LANG;

	echo "<select name='$name'>";
	echo "<option value='0' ".($value==0?" selected ":"").">-------------</option>";
	echo "<option value='2' ".($value==2?" selected ":"").">".$LANG["financial"][47]."</option>";
	echo "<option value='1' ".($value==1?" selected ":"").">".$LANG["financial"][48]."</option>";
	echo "</select>";	
}
/**
 * Get amortissement type name for infocoms
 *
 * @param $value status ID
 */
function getAmortTypeName($value){
	global $LANG;

	switch ($value){
		case 2 :
			return $LANG["financial"][47];
			break;
		case 1 :
			return $LANG["financial"][48];
			break;
		case 0 :
			return "";
			break;

	}
}	
/**
 * Get planninf state name
 *
 * @param $value status ID
 */
function getPlanningState($value)
{
	global $LANG;
	
	switch ($value){
		case 0:
			return $LANG["planning"][16];
			break;
		case 1:
			return $LANG["planning"][17];
			break;
		case 2:
			return $LANG["planning"][18];
			break;
	}
	
}

/**
 * Dropdown of planning state
 *
 * @param $name select name
 * @param $value default value
 */
function dropdownPlanningState($name,$value='')
{
	global $LANG;
	
	echo "<select name='$name' id='$name'>";

	echo "<option value='0'".($value==0?" selected ":"").">".$LANG["planning"][16]."</option>";
	echo "<option value='1'".($value==1?" selected ":"").">".$LANG["planning"][17]."</option>";
	echo "<option value='2'".($value==2?" selected ":"").">".$LANG["planning"][18]."</option>";

	echo "</select>";	
	
}

/**
 * Dropdown of values in an array
 *
 * @param $name select name
 * @param $elements array of elements to display
 * @param $value default value
 * @param $used already used elements key (do not display)
 * 
 */	
function dropdownArrayValues($name,$elements,$value='',$used=array()){
	$rand=mt_rand();
	echo "<select name='$name' id='dropdown_".$name.$rand."'>";

	foreach($elements as $key => $val){
		if (!isset($used[$key])) {
			echo "<option value='".$key."'".($value==$key?" selected ":"").">".$val."</option>";				
		}
	}

	echo "</select>";	
	return $rand;
}

/**
 * Remplace an dropdown by an hidden input field 
 * and display the value.
 *
 * @param $name select name
 * @param $elements array of elements to display
 * @param $value default value
 * @param $used already used elements key (not used in this RO mode)
 *  
 */	
function dropdownArrayValuesReadonly($name,$elements,$value='',$used=array()){

	echo "<input type='hidden' name='$name' value='$value'>";

	if (isset($elements[$value])) {
		echo $elements[$value]; 
	}
}

/**
 * Dropdown of states for behaviour config
 *
 * @param $name select name
 * @param $lib string to add for -1 value
 * @param $value default value
 */
function dropdownStateBehaviour ($name, $lib="", $value=0){
	global $DB, $LANG;
	
	$elements=array("0"=>$LANG["setup"][195]);
	if ($lib) {
		$elements["-1"]=$lib;	
	}

	$queryStateList = "SELECT ID,name from glpi_dropdown_state ORDER BY name";
	$result = $DB->query($queryStateList);
	if ($DB->numrows($result) > 0) {
		while (($data = $DB->fetch_assoc($result))) {
			$elements[$data["ID"]] = $LANG["setup"][198] . ": " . $data["name"];
		}
	}
	dropdownArrayValues($name, $elements, $value);
}

/**
 * Dropdown for global management config
 *
 * @param $name select name
 * @param $value default value
 * @param $software is it for software ?
 */
function adminManagementDropdown($name,$value,$software=0){
	global $LANG;
	echo "<select name=\"".$name."\">";

	if (!$software){
		$yesUnit = $LANG["peripherals"][32];
		$yesGlobal = $LANG["peripherals"][31];
	} else {
		$yesUnit = $LANG["ocsconfig"][46];
		$yesGlobal = $LANG["ocsconfig"][45];
	}
	
	echo "<option value=\"2\"";
	if ($value == 2) {
		echo " selected";
	}
	echo ">".$LANG["choice"][0]."</option>";
	
	echo "<option value=\"0\"";
	if ($value == 0) {
		echo " selected";
	}
	echo ">" . $LANG["choice"][1]." - ". $LANG["setup"][274]. " : ".  $yesUnit . "</option>";

	echo "<option value=\"1\"";
	if ($value == 1) {
		echo " selected";
	}
	echo ">" . $LANG["choice"][1]." - ". $LANG["setup"][274]. " : ". $yesGlobal . " </option>";
				
	echo "</select>";
}
/**
 * Dropdown for GMT selection
 *
 * @param $name select name
 * @param $value default value
 */
function dropdownGMT($name,$value=''){
	global $LANG;
	$elements = array ( -12, -11, -10, -9, -8, -7, -6, -5, -4, -3.5, -3, -2, -1, 0, 1, 2, 3, 3.5, 4, 4.5, 5, 5.5, 6, 6.5, 7, 8, 9, 9.5, 10, 11, 12, 13);
	
	echo "<select name='$name' id='dropdown_".$name."'>";

	foreach($elements as $element){
		if ($element != 0)
			$display_value = $LANG["gmt"][0].($element > 0?" +":" ").$element." ".$LANG["gmt"][1];
		else $display_value = $LANG["gmt"][0];
		echo "<option value='".$element."'".($element==$value?" selected ":"").">".$display_value."</option>";
	}

	echo "</select>";	
}

/**
 * Dropdown rules for a defined rule_type
 *
 * @param $myname select name
 * @param $rule_type rule type
 */
function dropdownRules ($rule_type, $myname){
	global $DB, $CFG_GLPI, $LANG;

	$rand=mt_rand();
	$limit_length=$_SESSION["glpidropdown_limit"];

	$use_ajax=false;
	if ($CFG_GLPI["use_ajax"]){
		$nb=countElementsInTable("glpi_rules_descriptions", "rule_type=".$rule_type);
		
		if ($nb>$CFG_GLPI["ajax_limit_count"]){
			$use_ajax=true;
		}
	}
	$params=array('searchText'=>'__VALUE__',
		'myname'=>$myname,
		'limit'=>$limit_length,
		'rand'=>$rand,
		'type'=>$rule_type
		);
	$default="<select name='$myname' id='dropdown_".$myname.$rand."'><option value='0'>------</option></select>\n";
	ajaxDropdown($use_ajax,"/ajax/dropdownRules.php",$params,$default,$rand);

	return $rand;
}
/**
 * Dropdown profiles which have rights under the active one
 *
 * @param $name select name
 * @param $value default value
 */
function dropdownUnderProfiles($name,$value=''){
	global $DB;

	$profiles[0]="-----";

	$prof=new Profile();

	$query="SELECT * FROM glpi_profiles ".$prof->getUnderProfileRetrictRequest("WHERE")." ORDER BY name";

	$res = $DB->query($query);

	//New rule -> get the next free ranking
	if ($DB->numrows($res)){
		while ($data = $DB->fetch_array($res)){
			$profiles[$data['ID']]=$data['name'];
		} 
	}

	dropdownArrayValues($name,$profiles,$value);
}

/**
 * Dropdown for infocoms alert config
 *
 * @param $name select name
 * @param $value default value
 */
function dropdownAlertInfocoms($name,$value=0){
	global $LANG;
	echo "<select name=\"$name\">";
	echo "<option value=\"0\" ".($value==0?" selected ":"")." >-----</option>";
	echo "<option value=\"".pow(2,ALERT_END)."\" ".($value==pow(2,ALERT_END)?" selected ":"")." >".$LANG["financial"][80]." </option>";
	echo "</select>";
}


/**
 * Private / Public switch for items which may be assign to a user and/or an entity
 *
 * @param $private default is private ?
 * @param $entity working entity ID
 * @param $recursive is the item recursive ?
 */
function privatePublicSwitch($private,$entity,$recursive){
	global $LANG,$CFG_GLPI;

	$rand=mt_rand();

	echo "<script type='text/javascript' >\n";
	echo "function setPrivate$rand(){\n";
		
		$params=array(
			'private'=>1,
			'recursive'=>$recursive,
			'FK_entities'=>$entity,
			'rand'=>$rand,
		);
		ajaxUpdateItemJsCode('private_switch'.$rand,$CFG_GLPI["root_doc"]."/ajax/private_public.php",$params,false);

		echo "};";
	echo "function setPublic$rand(){\n";
		
		$params=array(
			'private'=>0,
			'recursive'=>$recursive,
			'FK_entities'=>$entity,
			'rand'=>$rand,
		);
		ajaxUpdateItemJsCode('private_switch'.$rand,$CFG_GLPI["root_doc"]."/ajax/private_public.php",$params,false);

		echo "};";
	echo "</script>";


	echo "<span id='private_switch$rand'>";
		$_POST['rand']=$rand;
		$_POST['private']=$private;
		$_POST['recursive']=$recursive;
		$_POST['FK_entities']=$entity;
		include (GLPI_ROOT."/ajax/private_public.php");
	echo "</span>\n";
	return $rand;
}


/**
 * Print a select with contract priority
 *
 * Print a select named $name with contract periodicty options and selected value $value
 *
 *@param $name string : HTML select name
 *@param $value integer : HTML select selected value
 *
 *@return Nothing (display)
 *
 **/
function dropdownContractPeriodicity($name,$value=0){
	global $LANG;
	$values=array("1","2","3","6","12","24","36");

	echo "<select name='$name'>";
	echo "<option value='0' ".($value==0?" selected ":"").">-------------</option>";
	foreach ( $values as $val)
		echo "<option value='$val' ".($value==$val?" selected ":"").">".$val." ".$LANG["financial"][57]."</option>";
	echo "</select>";	
}

/**
 * Print a select with contract renewal
 *
 * Print a select named $name with contract renewal options and selected value $value
 *
 *@param $name string : HTML select name
 *@param $value integer : HTML select selected value
 *
 *@return Nothing (display)
 *
 **/
function dropdownContractRenewal($name,$value=0){
	global $LANG;

	echo "<select name='$name'>";
	echo "<option value='0' ".($value==0?" selected ":"").">-------------</option>";
	echo "<option value='1' ".($value==1?" selected ":"").">".$LANG["financial"][105]."</option>";
	echo "<option value='2' ".($value==2?" selected ":"").">".$LANG["financial"][106]."</option>";
	echo "</select>";	
}

/**
 * Get the renewal type name
 *
 *@param $value integer : HTML select selected value
 *
 *@return string
 *
 **/
function getContractRenewalName($value){
	global $LANG;
	switch ($value){
		case 1: return $LANG["financial"][105];break;
		case 2: return $LANG["financial"][106];break;
		default : return "";
	}
}
/**
 * Get renewal ID by name
 * @param $value the name of the renewal
 * 
 * @return the ID of the renewal
 */
function getContractRenewalIDByName($value){
	global $LANG;
	if (preg_match("/$value/i",$LANG["financial"][105])){
		return 1;
	} else if (preg_match("/$value/i",$LANG["financial"][106])){
		return 2;
	} 
	return 0;
}

?>
