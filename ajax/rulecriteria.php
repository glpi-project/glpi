<?php
/*
 * @version $Id$
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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

// Direct access to file
if(ereg("rulecriteria.php",$_SERVER['PHP_SELF'])){
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

	if (!isset($RULES_CRITERIAS)){
		include(GLPI_ROOT."/inc/rules.constant.php");
	}

	
	checkLoginUser();

	// Non define case
	if (isset($_POST["rule_type"])&&isset($RULES_CRITERIAS[$_POST["rule_type"]])){
		// First include -> first of the predefined array
		if (!isset($_POST["criteria"])){
			$_POST["criteria"]=key($RULES_CRITERIAS[$_POST["rule_type"]]);
		}
		$type="";
		if (isset($RULES_CRITERIAS[$_POST["rule_type"]][$_POST["criteria"]]['type'])){
			$type=$RULES_CRITERIAS[$_POST["rule_type"]][$_POST["criteria"]]['type'];
		}
		dropdownRulesConditions($type,"condition");
		echo "&nbsp;&nbsp;";
		$display=false;
		
		if (isset($RULES_CRITERIAS[$_POST["rule_type"]][$_POST["criteria"]]['type'])){
			switch($RULES_CRITERIAS[$_POST["rule_type"]][$_POST["criteria"]]['type']){
				case "dropdown":
					dropdownValue($RULES_CRITERIAS[$_POST["rule_type"]][$_POST["criteria"]]['table'],"pattern");
					$display=true;
					break;
				case "dropdown_users":
					dropdownAllUsers("pattern");
					$display=true;
					break;
				case "dropdown_request_type":
					include_once (GLPI_ROOT."/inc/tracking.function.php");
					dropdownRequestType("pattern");
					$display=true;
					break;
			}
		}
		if (!$display){
			autocompletionTextField("pattern", "glpi_rules_criterias", "pattern", "", 30);
		}
			
	

	}

	

?>
