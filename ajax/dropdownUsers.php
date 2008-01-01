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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

// Direct access to file
if(ereg("dropdownUsers.php",$_SERVER['PHP_SELF'])){
	define('GLPI_ROOT','..');
	$AJAX_INCLUDE=1;
	include (GLPI_ROOT."/inc/includes.php");
	header("Content-Type: text/html; charset=UTF-8");
	header_nocache();
}
if (!defined('GLPI_ROOT')){
	die("Can not acces directly to this file");
	}

checkCentralAccess();
// Make a select box with all glpi users

$joinprofile=false;
if (!isset($_POST['right'])) {
	$_POST['right']="all";
}
// Default view : Nobody
if (!isset($_POST['all'])) {
	$_POST['all']=0;
}

switch ($_POST['right']){
	case "interface" :
		$where=" glpi_profiles.".$_POST['right']."='central' ";
		$joinprofile=true;
		if (isset($_POST["entity_restrict"])&&$_POST["entity_restrict"]>=0){
			$where.=getEntitiesRestrictRequest("AND","glpi_users_profiles", '',$_POST["entity_restrict"],1);
		} else {
			$where.=getEntitiesRestrictRequest("AND","glpi_users_profiles",'',$_SESSION["glpiactive_entity"],1);
		}
	break;
	case "ID" :
		$where=" glpi_users.ID='".$_SESSION["glpiID"]."' ";
	break;
	case "all" :
		$where=" glpi_users.ID > '1' ";
		if (isset($_POST["entity_restrict"])&&$_POST["entity_restrict"]>=0){
			$where.=getEntitiesRestrictRequest("AND","glpi_users_profiles", '',$_POST["entity_restrict"],1);
		} else {
			$where.=getEntitiesRestrictRequest("AND","glpi_users_profiles",'',$_SESSION["glpiactive_entity"],1);
		}
	break;
	default :
		$joinprofile=true;
		$where=" ( glpi_profiles.".$_POST['right']."='1' AND glpi_profiles.interface='central' ";
		if (isset($_POST["entity_restrict"])&&$_POST["entity_restrict"]>=0){
			$where.=getEntitiesRestrictRequest("AND","glpi_users_profiles", '',$_POST["entity_restrict"],1);
		} else {
			$where.=getEntitiesRestrictRequest("AND","glpi_users_profiles",'',$_SESSION["glpiactive_entity"],1);
		}
		$where.=" ) ";
		
	break;
}

$where.=" AND glpi_users.deleted='0' AND glpi_users.active='1' ";

if (isset($_POST['value'])){
	$where.=" AND  (glpi_users.ID <> '".$_POST['value']."') ";
}

if (strlen($_POST['searchText'])>0&&$_POST['searchText']!=$CFG_GLPI["ajax_wildcard"]){
	$where.=" AND (glpi_users.name ".makeTextSearch($_POST['searchText'])." OR glpi_users.realname ".makeTextSearch($_POST['searchText'])." OR glpi_users.firstname ".makeTextSearch($_POST['searchText'])." OR CONCAT(glpi_users.realname,' ',glpi_users.firstname) ".makeTextSearch($_POST['searchText']).")";
}


$NBMAX=$CFG_GLPI["dropdown_max"];
$LIMIT="LIMIT 0,$NBMAX";
if ($_POST['searchText']==$CFG_GLPI["ajax_wildcard"]) $LIMIT="";

$query = "SELECT DISTINCT glpi_users.* FROM glpi_users ";
$query.=" LEFT JOIN glpi_users_profiles ON (glpi_users.ID = glpi_users_profiles.FK_users)";
if ($joinprofile){
  $query .=" LEFT JOIN glpi_profiles ON (glpi_profiles.ID= glpi_users_profiles.FK_profiles) ";
}
$query.= " WHERE $where ORDER BY glpi_users.realname,glpi_users.firstname, glpi_users.name $LIMIT";
//echo $query;
$result = $DB->query($query);

echo "<select id='dropdown_".$_POST["myname"].$_POST["rand"]."' name=\"".$_POST['myname']."\">";

if ($_POST['searchText']!=$CFG_GLPI["ajax_wildcard"]&&$DB->numrows($result)==$NBMAX)
echo "<option value=\"0\">--".$LANG["common"][11]."--</option>";


if ($_POST['all']==0){
echo "<option value=\"0\">[ Nobody ]</option>";
} else if($_POST['all']==1) {
	echo "<option value=\"0\">[ ".$LANG["search"][7]." ]</option>";
}

if (isset($_POST['value'])){
	$output=getUserName($_POST['value']);
	if (!empty($output)&&$output!="&nbsp;")
		echo "<option selected value='".$_POST['value']."'>".$output."</option>";
}		

if ($DB->numrows($result)) {
	while ($data=$DB->fetch_array($result)) {
			
		$output=formatUserName($data["ID"],$data["name"],$data["realname"],$data["firstname"]);
		
	
		echo "<option value=\"".$data["ID"]."\" title=\"$output - ".$data["name"]."\">".substr($output,0,$CFG_GLPI["dropdown_limit"])."</option>";
	}
}
echo "</select>";

if (isset($_POST["comments"])&&$_POST["comments"]){
	$params=array('value'=>'__VALUE__','table'=>"glpi_users", 'withlink'=>"comments_link_".$_POST["myname"].$_POST["rand"]);
	ajaxUpdateItemOnSelectEvent("dropdown_".$_POST["myname"].$_POST["rand"],"comments_".$_POST["myname"].$_POST["rand"],$CFG_GLPI["root_doc"]."/ajax/comments.php",$params,false);
}

// Manage updates others dropdown for helpdesk
if (isset($_POST["helpdesk_ajax"])&&$_POST["helpdesk_ajax"]){

	$params=array('userID'=>'__VALUE__',
			'device_type'=>0
	);
	ajaxUpdateItemOnSelectEvent("dropdown_author".$_POST["rand"],"tracking_my_devices",$CFG_GLPI["root_doc"]."/ajax/updateTrackingDeviceType.php",$params,false);

	$params=array('value'=>'__VALUE__'
	);
	ajaxUpdateItemOnSelectEvent("dropdown_author".$_POST["rand"],"uemail_result",$CFG_GLPI["root_doc"]."/ajax/uemailUpdate.php",$params,false);

}


?>
