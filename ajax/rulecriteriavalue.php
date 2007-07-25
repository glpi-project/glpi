<?php
/*
 * @version $Id$
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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

// Direct access to file
if(ereg("rulecriteriavalue.php",$_SERVER['PHP_SELF'])){
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

	
	checkLoginUser();
	// Non define case
	if (isset($_POST["rule_type"])){
		$rule=getRuleClass($_POST["rule_type"]);
		$criterias=$rule->getCriterias();
		if (count($criterias)){
			$display=false;
		
			if (isset($criterias[$_POST["criteria"]]['type'])){
				switch($criterias[$_POST["criteria"]]['type']){
					case "dropdown":
						if ($_POST['condition']==PATTERN_IS||$_POST['condition']==PATTERN_IS_NOT){
							dropdownValue($criterias[$_POST["criteria"]]['table'],"pattern");
							$display=true;
						}
						break;
					case "dropdown_users":
						if ($_POST['condition']==PATTERN_IS||$_POST['condition']==PATTERN_IS_NOT){
							dropdownAllUsers("pattern");
							$display=true;
						}
						break;
					case "dropdown_request_type":
						include_once (GLPI_ROOT."/inc/tracking.function.php");
						if ($_POST['condition']==PATTERN_IS||$_POST['condition']==PATTERN_IS_NOT){
							dropdownRequestType("pattern");
							$display=true;
						}
						break;
					case "dropdown_priority":
						if ($_POST['condition']==PATTERN_IS||$_POST['condition']==PATTERN_IS_NOT){
							dropdownPriority("pattern");
							$display=true;
						}
						break;
				}
			}
			if (!$display){
				autocompletionTextField("pattern", "glpi_rules_criterias", "pattern", "", 30);
			}
		}
	}

	

?>
