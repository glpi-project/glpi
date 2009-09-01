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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------


$NEEDED_ITEMS = array ('computer', 'document', 'group', 'infocom', 'monitor', 'networking',
   'peripheral', 'phone', 'planning', 'printer', 'rule.tracking', 'rulesengine', 'software',
   'tracking', 'user');

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

checkRight("create_ticket","1");

commonHeader("Helpdesk",$_SERVER['PHP_SELF'],"maintain","helpdesk");

if (isset($_POST["_my_items"])&&!empty($_POST["_my_items"])){
	$splitter=explode("_",$_POST["_my_items"]);
	if (count($splitter)==2){
		$_POST["itemtype"]=$splitter[0];
		$_POST["items_id"]=$splitter[1];
	}
}

if (!isset($_POST["add"]))
	$post_ticket = false;
else
	$post_ticket = true;

if (!isset($_POST["entities_id"]))
	$entity_restrict = $_SESSION["glpiactive_entity"];
else
	$entity_restrict = $_POST["entities_id"];


if (isset($_GET["itemtype"])) $itemtype=$_GET["itemtype"];
else if (isset($_SESSION["helpdeskSaved"]["itemtype"])) $itemtype=$_SESSION["helpdeskSaved"]["itemtype"];
else $itemtype=0;

if (isset($_GET["items_id"])) $computer=$_GET["items_id"];
else if (isset($_SESSION["helpdeskSaved"]["items_id"])) $computer=$_SESSION["helpdeskSaved"]["items_id"];
else $computer=0;

if (!$post_ticket && isset($_POST["status"]))
	$status=$_POST["status"];
elseif (!isset($_SESSION["helpdeskSaved"]["status"])) $status=1;
else $status=$_SESSION["helpdeskSaved"]["status"];

if (!$post_ticket && isset($_POST["users_id"]))
	$users_id=$_POST["users_id"];
elseif (!isset($_SESSION["helpdeskSaved"]["users_id"])) $users_id=$_SESSION["glpiID"];
else $users_id=$_SESSION["helpdeskSaved"]["users_id"];

if (!$post_ticket && isset($_POST["groups_id"]))
	$group=$_POST["groups_id"];
elseif (!isset($_SESSION["helpdeskSaved"]["groups_id"])) $group=0;
else $group=$_SESSION["helpdeskSaved"]["groups_id"];

if (!$post_ticket && isset($_POST["users_id_assign"]))
	$users_id_assign=$_POST["users_id_assign"];
elseif (!isset($_SESSION["helpdeskSaved"]["users_id_assign"])) $users_id_assign=0;
else $users_id_assign=$_SESSION["helpdeskSaved"]["users_id_assign"];

if (!$post_ticket && isset($_POST["groups_id_assign"]))
	$groups_id_assign=$_POST["groups_id_assign"];
elseif (!isset($_SESSION["helpdeskSaved"]["groups_id_assign"])) $groups_id_assign=0;
else $groups_id_assign=$_SESSION["helpdeskSaved"]["groups_id_assign"];

if (!$post_ticket && isset($_POST["minute"]))
	$minute=$_POST["minute"];
elseif (!isset($_SESSION["helpdeskSaved"]["minute"])) $minute=0;
else $minute=$_SESSION["helpdeskSaved"]["minute"];

if (!$post_ticket && isset($_POST["hour"]))
	$hour=$_POST["hour"];
elseif (!isset($_SESSION["helpdeskSaved"]["hour"])) $hour=0;
else $hour=$_SESSION["helpdeskSaved"]["hour"];


if (!$post_ticket && isset($_POST["date"]))
	$date=$_POST["date"];
elseif (!isset($_SESSION["helpdeskSaved"]["date"])) $date=date("Y-m-d H:i:s");
else $date=$_SESSION["helpdeskSaved"]["date"];

if (!$post_ticket && isset($_POST["ticketscategories_id"]))
	$ticketscategories_id=$_POST["ticketscategories_id"];
elseif (!isset($_SESSION["helpdeskSaved"]["ticketscategories_id"])) $ticketscategories_id=0;
else $ticketscategories_id=$_SESSION["helpdeskSaved"]["ticketscategories_id"];

if (!$post_ticket && isset($_POST["priority"]))
	$priority=$_POST["priority"];
elseif (!isset($_SESSION["helpdeskSaved"]["priority"])) $priority=3;
else $priority=$_SESSION["helpdeskSaved"]["priority"];

if (!$post_ticket && isset($_POST["request_type"]))
	$request_type=$_POST["request_type"];
elseif (!isset($_SESSION["helpdeskSaved"]["request_type"])) $request_type=$_SESSION["glpidefault_request_type"];
else $request_type=$_SESSION["helpdeskSaved"]["request_type"];

if (!$post_ticket && isset($_POST["name"])) {
   $name=stripslashes($_POST["name"]);
} else if (!isset($_SESSION["helpdeskSaved"]["name"])) {
   $name='';
} else {
   $name=stripslashes($_SESSION["helpdeskSaved"]["name"]);
}

if (!$post_ticket && isset($_POST["content"])) {
   $content=cleanPostForTextArea($_POST["content"]);
} else if (!isset($_SESSION["helpdeskSaved"]["content"])) {
   $content='';
} else {
   $content=cleanPostForTextArea($_SESSION["helpdeskSaved"]["content"]);
}

if (!$post_ticket && isset($_POST["_followup"]))
	$followup=$_POST["_followup"];
elseif (!isset($_SESSION["helpdeskSaved"]["_followup"])) $followup=array();
else {
	$followup=$_SESSION["helpdeskSaved"]["_followup"];
}

if (!$post_ticket && isset($_POST["plan"]))
	$followup['plan']=$_POST["plan"];
elseif (!isset($_SESSION["helpdeskSaved"]["plan"])) $followup['plan']=array();
else {
	$followup['plan']=$_SESSION["helpdeskSaved"]["plan"];
}


if (isset($_SESSION["helpdeskSaved"])&&count($_GET)==0){
	unset($_SESSION["helpdeskSaved"]);
}

$track=new Job();

if (isset($_POST["priority"]) && $post_ticket){
	if ($newID=$track->add($_POST)){
		logEvent($newID, "tracking", 4, "tracking", $_SESSION["glpiname"]." ".$LANG['log'][20]." $newID.");
	}
	glpi_header($_SERVER['HTTP_REFERER']);
} else {
	addFormTracking($itemtype,$computer,$_SERVER['PHP_SELF'],$users_id,$group,$users_id_assign,$groups_id_assign,$name,$content,$ticketscategories_id,$priority,$request_type,$hour,$minute,$date,$entity_restrict,$status,$followup);
}

commonFooter();
?>
