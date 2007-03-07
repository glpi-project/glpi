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


$NEEDED_ITEMS=array("rulesengine","affectentity");

$PLUGIN_HOOKS['submenu_entry']['example']['add'] = 'index.php';
$PLUGIN_HOOKS['submenu_entry']['example']["<img  src='".$CFG_GLPI["root_doc"]."/pics/menu_showall.png' title='".$LANGEXAMPLE["test"]."' alt='".$LANGEXAMPLE["test"]."'>"] = 'index.php';
$PLUGIN_HOOKS['submenu_entry']['example'][$LANGEXAMPLE["test"]] = 'index.php';


define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(!isset($tab["ID"])) $tab["ID"] = "";

if (isset($tab["action"]))
{
		$rulecollection = new RuleCollection($tab["type"]);
		$rulecollection->changeRuleOrder($tab["ID"],$tab["action"]);
}elseif (isset($tab["deleterule"]))
{
	checkRight("config","w");
	$rule = new Rule;
		
	if (count($_POST["item"]))
		foreach ($_POST["item"] as $key => $val)
		{
			$rule->getRuleWithCriteriasAndActions($key,1,1);
			$input["ID"]=$key;
			$rule->delete($input);
		}
	
	$rulecollection = new RuleCollection($tab["type"]);
	$rulecollection->changeRuleOrder(-1,"");
		
	logEvent($_POST["FK_entities"], "rule", 4, "setup", $_SESSION["glpiname"]." ".$LANG["rulesengine"][20]);
	glpi_header($_SERVER['HTTP_REFERER']);
}

commonHeader($LANG["title"][2],$_SERVER['PHP_SELF'],"admin","rule",$LANG["rulesengine"][17]);
$rules_type[0]=RULE_OCS_AFFECT_COMPUTER;
//$rules_type[1]=RULE_LDAP_AFFECT_RIGHT;

foreach ($rules_type as $rule_type)
{
	$rule = new RuleCollection($rule_type);
	$rule->showForm($_SERVER['PHP_SELF']);
}
commonFooter();
?>
