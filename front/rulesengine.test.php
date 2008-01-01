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
// Original Author of file: Walid Nouh
// Purpose of file:
// ----------------------------------------------------------------------

$NEEDED_ITEMS=array("rulesengine","rule.ocs","rule.right","rule.softwarecategory","rule.dictionnary.software",
					"rule.dictionnary.model","rule.dictionnary.type","rule.dictionnary.os");

if(!defined('GLPI_ROOT')){
	define('GLPI_ROOT', '..');
}

include (GLPI_ROOT . "/inc/includes.php");

if (isset($_POST["rule_type"]))$rule_type=$_POST["rule_type"];
elseif (isset($_GET["rule_type"]))$rule_type=$_GET["rule_type"];
else $rule_type=0;

$rulecollection = getRuleCollectionClass($rule_type);
checkRight($rulecollection->right,"r");

if (!ereg("popup",$_SERVER['PHP_SELF'])){
	commonHeader($LANG["title"][2],$_SERVER['PHP_SELF'],"config","display");
}

$input = $rulecollection->showRulesEngineCriteriasForm($_SERVER['PHP_SELF'],$rule_type,$_POST);

if (isset($_POST["test_all_rules"]))
{
	//Unset values that must not be processed by the rule
	unset($_POST["rule_type"]);
	unset($_POST["test_all_rules"]);
	
	echo "<br>";
	$rulecollection->showRulesEnginePreviewResultsForm($_SERVER['PHP_SELF'],$_POST);
}


if (!ereg("popup",$_SERVER['PHP_SELF'])){
	commonFooter();
}

if (!ereg("popup",$_SERVER['PHP_SELF'])){
	commonFooter();
}

?>
