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
// Original Author of file: Walid Nouh
// Purpose of file:
// ----------------------------------------------------------------------

$NEEDED_ITEMS=array("rulesengine","rule.dictionnary.software","software","rule.dictionnary.manufacturer","rule.softwarecategories","ocsng","setup");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

$rulecollection = new DictionnarySoftwareCollection;


if (isset($_POST["replay_rule"])) {
	commonHeader($LANG["rulesengine"][17],$_SERVER['PHP_SELF'],"admin","dictionnary",$rulecollection->rule_type);
	showWarningPageBeforeProcessingDictionnary($_SERVER['PHP_SELF']);
	commonFooter();
	
}
if (isset($_POST["replay_rule_process"])) {
	ini_set("max_execution_time", "0");

	if (!isset($_POST["manufacturer"])) 
		$_POST["manufacturer"] = 0;
		
	$deb=time();	
	commonHeader($LANG["rulesengine"][17],$_SERVER['PHP_SELF'],"admin","rule",$rulecollection->rule_type);

	echo "<div class='center'>"; 
	echo "<table class='tab_cadrehov'>";

	echo "<tr><th><div class='relative'><span><strong>" .$LANG["rulesengine"][35]. "</strong></span>";
	echo " - " .$LANG["rulesengine"][76]. "</th></tr>\n";
	echo "<tr><td align='center'>";
	createProgressBar($LANG["rulesengine"][90]);
	echo "</td></tr>\n";
	echo "</table>";
	echo "</div>";
	commonFooter(true);
	
	$rulecollection->replayRulesOnExistingDB(array(),$_POST["manufacturer"]);
	
	changeProgressBarMessage($LANG["rulesengine"][91]." (".timestampToString(time()-$deb).
		")<br /><a href='".$_SERVER['PHP_SELF']."'>".$LANG["buttons"][13]."</a>");
}
else include (GLPI_ROOT . "/front/rule.common.php");
?>
