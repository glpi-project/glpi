<?php
/*
 * @version $Id: cartridge.form.php 4487 2007-03-01 03:19:20Z jmd $
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
// Original Author of file: Walid Nouh
// Purpose of file:
// ----------------------------------------------------------------------


$NEEDED_ITEMS=array("rulesengine","ocsng","affectentity");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(!isset($tab["ID"])) $tab["ID"] = "";

if (!isset($_SESSION['glpi_onglet'])) $_SESSION['glpi_onglet']=1;
if (isset($_GET['onglet'])) {
	$_SESSION['glpi_onglet']=$_GET['onglet'];
}	

commonHeader($LANG["title"][2],$_SERVER['PHP_SELF'],"admin","Regles");

if (isset($tab["action"]))
{
	switch ($tab["action"])
	{
		case "edit_action" :
			$tab["ruleid"]=-1;
		case "add_action" :
			$ruleaction = new RuleAction;
			$ruleaction->showForm($_SERVER['PHP_SELF'],$tab["ID"],$tab["ruleid"]);
		break;
		case "update_action":
			$ruleaction = new RuleAction;
			$ruleaction->update($tab);
		 break;
		case "add_action":
			$ruleaction = new RuleAction;
			$ruleaction->add($tab);
		 break;
		case "edit_criteria" :
			$tab["ruleid"]=-1;
		case "add_criteria" :
			$ruleaction = new RuleCriteria;
			$ruleaction->showForm($_SERVER['PHP_SELF'],$tab["ID"],$tab["ruleid"]);
		break;
		case "update_criteria":
			$ruleaction = new RuleCriteria;
			$ruleaction->update($tab);
		 break;
		case "add_criteria":
			$ruleaction = new RuleCriteria;
			$ruleaction->add($tab);
		 break;
	}
}
else
{
	if (isset($tab["update_description"]))
	{
	$rule_description = new RuleDescription;
	$rule_description->update($tab);
	}
$rule = new OcsAffectEntityRule(1);
$rule->getRuleWithCriteriasAndActions($tab["ID"],1,1);
$rule->title();
$description = $rule->description;
$description->showForm($_SERVER['PHP_SELF'],$tab["ID"]);
switch($_SESSION['glpi_onglet']){
		case -1 :	
		case 1 :
			$rule->showCriteriasList($_SERVER['PHP_SELF'],false);
			$rule->showActionsList($_SERVER['PHP_SELF'],false);
			
		break;
		case 2 : 
			$rule->showCriteriasList($_SERVER['PHP_SELF'],true);
		break;
		case 3 :
			$rule->showActionsList($_SERVER['PHP_SELF'],true);
			break;
		default :
		break;
}
}
commonFooter();
?>
