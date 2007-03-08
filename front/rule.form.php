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


$NEEDED_ITEMS=array("entity","rulesengine","ocsng","rule.ocs");

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
if (isset($tab["delete_criteria"]))
{
	
	if (count($_POST["item"]))
		foreach ($_POST["item"] as $key => $val)
		{
			$rulecriteria = new RuleCriteria;
			$input["ID"]=$key;
			$rulecriteria->delete($input);
		}
	
	glpi_header($_SERVER['HTTP_REFERER']);
}
if (isset($tab["delete_action"]))
{
	
	if (count($_POST["item"]))
		foreach ($_POST["item"] as $key => $val)
		{
			$ruleaction = new RuleAction;
			$input["ID"]=$key;
			$ruleaction->delete($input);
		}
	
	glpi_header($_SERVER['HTTP_REFERER']);
}
elseif (isset($tab["add_criteria"]))
{
	$rulecriteria = new RuleCriteria;
	$rulecriteria->add($tab);
	glpi_header($_SERVER['HTTP_REFERER']);
}
elseif (isset($tab["add_action"]))
{
	$ruleaction = new RuleAction;
	$ruleaction->add($tab);
	glpi_header($_SERVER['HTTP_REFERER']);
}
elseif (isset($tab["update_description"]))
{
	$rule = new Rule;
	$rule->update($tab);
	glpi_header($_SERVER['HTTP_REFERER']);
}

$rule = getRuleByType(getRuleType($tab["ID"]));
$rule->getRuleWithCriteriasAndActions($tab["ID"],1,1);

$rule->title();
$rule->showForm($_SERVER['PHP_SELF'],$tab["ID"]);
switch($_SESSION['glpi_onglet']){
		case -1 :	
		case 1 :
			$rule->showCriteriasList($_SERVER['PHP_SELF'],false);
			$rule->showActionsList($_SERVER['PHP_SELF'],false);
			
		break;
		case 2 : 
			if ($tab["ID"] != -1) 
				$rule->showCriteriasList($_SERVER['PHP_SELF'],true);
			else
				$rule->showCriteriasList($_SERVER['PHP_SELF'],false);	
		break;
		case 3 :
			if ($tab["ID"] != -1)
				$rule->showActionsList($_SERVER['PHP_SELF'],true);
			else
				$rule->showActionsList($_SERVER['PHP_SELF'],false);	
			break;
		default :
		break;
}
commonFooter();
?>
