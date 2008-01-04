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
	$rule->displayCriteriaSelectPattern("pattern",$_POST["criteria"],$_POST['condition']);
}

	

?>
