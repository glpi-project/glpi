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
if(ereg("ruleaction.php",$_SERVER['PHP_SELF'])){
	define('GLPI_ROOT','..');
	$AJAX_INCLUDE=1;
	include (GLPI_ROOT."/inc/includes.php");
	header("Content-Type: text/html; charset=UTF-8");
	header_nocache();
};

if (!defined('GLPI_ROOT')){
	die("Can not acces directly to this file");
}

	include_once (GLPI_ROOT."/inc/rulesengine.function.php");
	
	if (!isset($RULES_ACTIONS)){
		include(GLPI_ROOT."/inc/rules.constant.php");
	}
	checkLoginUser();


	// Non define case
	if (isset($_POST["rule_type"])&&isset($RULES_ACTIONS[$_POST["rule_type"]])){
		// First include -> first of the predefined array
		if (!isset($_POST["field"])){
			$_POST["field"]=key($RULES_ACTIONS[$_POST["rule_type"]]);
		}

		$rand=dropdownRulesActions($_POST["rule_type"],"action_type",$_POST["field"]);

		echo "&nbsp;&nbsp;";
		echo "<span id='action_type_span'>\n";
		echo "</span>\n";

		$params=array('action_type'=>'__VALUE__',
				'field'=>$_POST["field"],
				'rule_type'=>$_POST["rule_type"],
		);
		ajaxUpdateItemOnSelectEvent("dropdown_action_type$rand","action_type_span",$CFG_GLPI["root_doc"]."/ajax/ruleactionvalue.php",$params,false);
		ajaxUpdateItem("action_type_span",$CFG_GLPI["root_doc"]."/ajax/ruleactionvalue.php",$params,false,"dropdown_action_type$rand");
	}

	

?>
