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

$NEEDED_ITEMS=array("entity","rulesengine","rule.ocs","rule.right","user","profile");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

checkRight("entity","r");

$entity=new Entity();
$entitydata=new EntityData();

if (isset($_POST["update"]))
{
	$entity->check($_POST["ID"],'w');

	$entitydata->update($_POST);

	logEvent($_POST["ID"], "entity", 4, "setup", $_SESSION["glpiname"]." ".$LANG["log"][21]);
	glpi_header($_SERVER['HTTP_REFERER']);
} else if (isset($_POST["adduser"]))
{
	$entity->check($_POST["FK_entities"],'w');

	addUserProfileEntity($_POST);

	logEvent($_POST["FK_entities"], "entity", 4, "setup", $_SESSION["glpiname"]." ".$LANG["log"][61]);
	glpi_header($_SERVER['HTTP_REFERER']);
} else if (isset($_POST["add_rule"]))
{
	$entity->check($_POST["affectentity"],'w');


	$rule = new OcsAffectEntityRule;
	$ruleid = $rule->add($_POST);
	
	if ($ruleid)
	{
		//Add an action associated to the rule
		$ruleAction = new RuleAction;
		//Action is : affect computer to this entity
		$ruleAction->addActionByAttributes("assign", $ruleid, "FK_entities", $_POST["affectentity"]);
	}
		
	logEvent($ruleid, "rules", 4, "setup", $_SESSION["glpiname"]." ".$LANG["log"][20]);
	glpi_header($_SERVER['HTTP_REFERER']);
} else if (isset($_POST["add_user_rule"]))
{
	$entity->check($_POST["affectentity"],'w');


	$rule = new RightAffectRule;
	$ruleid = $rule->add($_POST);
	
	if ($ruleid)
	{
		//Add an action associated to the rule
		$ruleAction = new RuleAction;
		//Action is : affect computer to this entity
		$ruleAction->addActionByAttributes("assign", $ruleid, "FK_entities", $_POST["affectentity"]);
		if ($_POST["FK_profiles"]){
			$ruleAction->addActionByAttributes("assign", $ruleid, "FK_profiles", $_POST["FK_profiles"]);
		}
		$ruleAction->addActionByAttributes("assign", $ruleid, "recursive", $_POST["recursive"]);
	}
		
	logEvent($ruleid, "rules", 4, "setup", $_SESSION["glpiname"]." ".$LANG["log"][22]);
	glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["deleteuser"]))
{
	$entity->check($_POST["FK_entities"],'w');

	if (count($_POST["item"])){
		foreach ($_POST["item"] as $key => $val){
			deleteUserProfileEntity($key);
		}
	}

	logEvent($_POST["FK_entities"], "entity", 4, "setup", $_SESSION["glpiname"]." ".$LANG["log"][62]);
	glpi_header($_SERVER['HTTP_REFERER']);
}elseif (isset($_POST["delete_computer_rule"]) || isset($_POST["delete_user_rule"]))
{
	$entity->check($_POST["affectentity"],'w');

	if (isset($_POST["delete_computer_rule"])){
		$rule = new OcsAffectEntityRule;		
	} else {
		$rule = new RightAffectRule;
	}
		
	if (count($_POST["item"])){
		foreach ($_POST["item"] as $key => $val){	
			$rule->delete(array('ID'=>$key));
		}
	}

	logEvent(0, "rules", 4, "setup", $_SESSION["glpiname"]." ".$LANG["log"][22]);
	glpi_header($_SERVER['HTTP_REFERER']);
}

commonHeader($LANG["Menu"][37],$_SERVER['PHP_SELF'],"admin","entity");

$entity->showForm($_SERVER['PHP_SELF'],$_GET["ID"]);

commonFooter();
?>