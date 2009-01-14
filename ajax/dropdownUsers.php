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

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

// Direct access to file
if(strpos($_SERVER['PHP_SELF'],"dropdownUsers.php")){
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

if (!isset($_POST['right'])) {
	$_POST['right']="all";
}
// Default view : Nobody
if (!isset($_POST['all'])) {
	$_POST['all']=0;
}

$used=array();

if (isset($_POST['used'])) {
	if (is_array($_POST['used'])) {
		$used=$_POST['used'];
	} else {
		$used=unserialize(stripslashes($_POST['used']));
	}
}
if (isset($_POST["entity_restrict"])&&!is_numeric($_POST["entity_restrict"])&&!is_array($_POST["entity_restrict"])){
	$_POST["entity_restrict"]=unserialize(stripslashes($_POST["entity_restrict"]));
}

$result=dropdownUsersSelect(false, $_POST['right'], $_POST["entity_restrict"], $_POST['value'], $used, $_POST['searchText']);

$users=array();
if ($DB->numrows($result)) {
	while ($data=$DB->fetch_array($result)) {
		$users[$data["ID"]]=formatUserName($data["ID"],$data["name"],$data["realname"],$data["firstname"]);
		$logins[$data["ID"]]=$data["name"];
	}
}	

asort($users);

echo "<select id='dropdown_".$_POST["myname"].$_POST["rand"]."' name=\"".$_POST['myname']."\"";
if (isset($_POST["helpdesk_ajax"]) && !empty($_POST["helpdesk_ajax"]))
	echo " onChange='submit()'";
echo ">";

if ($_POST['searchText']!=$CFG_GLPI["ajax_wildcard"] && $DB->numrows($result)==$CFG_GLPI["dropdown_max"])
echo "<option value=\"0\">--".$LANG["common"][11]."--</option>";


if ($_POST['all']==0){
echo "<option value=\"0\">[ Nobody ]</option>";
} else if($_POST['all']==1) {
	echo "<option value=\"0\">[ ".$LANG["common"][66]." ]</option>";
}

if (isset($_POST['value'])){
	$output=getUserName($_POST['value']);
	if (!empty($output)&&$output!="&nbsp;")
		echo "<option selected value='".$_POST['value']."'>".$output."</option>";
}		

if (count($users)) {
	foreach ($users as $ID => $output){
			
		echo "<option value=\"".$ID."\" title=\"".cleanInputText($output." - ".$logins[$ID])."\">".substr($output,0,$_SESSION["glpidropdown_limit"])."</option>";
	}
}
echo "</select>";

if (!isMultiEntitiesMode())
{
	if (isset($_POST["comments"])&&$_POST["comments"]){
		$paramscomments=array('value'=>'__VALUE__','table'=>"glpi_users");
		if (isset($_POST['update_link'])){
			$paramscomments['withlink']="comments_link_".$_POST["myname"].$_POST["rand"];
		}
		ajaxUpdateItemOnSelectEvent("dropdown_".$_POST["myname"].$_POST["rand"],"comments_".$_POST["myname"].$_POST["rand"],$CFG_GLPI["root_doc"]."/ajax/comments.php",$paramscomments,false);
	}
}

// Manage updates others dropdown for helpdesk
if (isset($_POST["helpdesk_ajax"])&&$_POST["helpdesk_ajax"]){
	if (!isMultiEntitiesMode())
	{
		$paramscomments=array('userID'=>'__VALUE__',
				'entity_restrict'=>$_POST["entity_restrict"],
				'device_type'=>0,
				'author_field'=>"dropdown_author".$_POST["rand"]
		);

		ajaxUpdateItemOnSelectEvent("dropdown_author".$_POST["rand"],"tracking_my_devices",$CFG_GLPI["root_doc"]."/ajax/updateTrackingDeviceType.php",$paramscomments,false);
		$paramscomments=array('value'=>'__VALUE__');
		ajaxUpdateItemOnSelectEvent("dropdown_author".$_POST["rand"],"uemail_result",$CFG_GLPI["root_doc"]."/ajax/uemailUpdate.php",$paramscomments,false);
	}
}
?>