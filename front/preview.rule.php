<?php
/*
 * @version $Id: setup.display.php 5008 2007-05-18 22:54:23Z moyo $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2007 by the INDEPNET Development Team.

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

$NEEDED_ITEMS=array("search","setup","ocsng","rulesengine","rule.ocs","computer","device","printer","networking","peripheral","monitor","software","infocom","phone","tracking","enterprise","reservation","setup","registry","admininfo","group","rule.softwarecategories","rule.dictionnary.software","rule.dictionnary.manufacturer",
"rule.dictionnary.model.computer","rule.dictionnary.model.monitor","rule.dictionnary.model.printer","rule.dictionnary.model.peripheral",
"rule.dictionnary.type.computer","rule.dictionnary.type.monitor","rule.dictionnary.type.printer","rule.dictionnary.type.peripheral");

if(!defined('GLPI_ROOT')){
	define('GLPI_ROOT', '..');
}

include (GLPI_ROOT . "/inc/includes.php");

if (isset($_POST["rule_type"]))$rule_type=$_POST["rule_type"];
elseif (isset($_GET["rule_type"]))$rule_type=$_GET["rule_type"];
else $rule_type=0;


if (isset($_POST["rule_id"]))$rule_id=$_POST["rule_id"];
elseif (isset($_GET["rule_id"]))$rule_id=$_GET["rule_id"];
else $rule_id=0;

$rule = getRuleClass($rule_type);
checkRight($rule->right,"w");

$test_rule_output=null;

if (isset($_POST["test_rule"]))
{
	$params=array();
	$test_rule_output=array();
	//Unset values that must not be processed by the rule
	unset($_POST["test_rule"]);
	unset($_POST["rule_id"]);
	unset($_POST["rule_type"]);
	$rule->getRuleWithCriteriasAndActions($rule_id,1,1);
	$rule->process($_POST,$test_rule_output,$params);
}

if (!ereg("popup",$_SERVER['PHP_SELF'])){
	commonHeader($LANG["title"][2],$_SERVER['PHP_SELF'],"config","display");
}


$rule->testRuleForm($_SERVER['PHP_SELF'],$rule_id,$test_rule_output);
if (!ereg("popup",$_SERVER['PHP_SELF'])){
	commonFooter();
}
?>
