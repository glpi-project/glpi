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

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}
$rule = new $rulecollection->rule_class_name();

if(!isset($_GET["ID"])) $_GET["ID"] = "";

checkRight($rulecollection->right,"r");

if (isset($_GET["action"])){
	checkRight($rulecollection->right,"w");
	$rulecollection->changeRuleOrder($_GET["ID"],$_GET["action"]);
	glpi_header($_SERVER['HTTP_REFERER']);
}elseif (isset($_POST["action"])){
	checkRight($rulecollection->right,"w");	

	// Use massive action system
	switch ($_POST["action"]){
		case "delete":
			if (isset($_POST["item"])&&count($_POST["item"])){
				foreach ($_POST["item"] as $key => $val){
					$rule->getFromDB($key);
					$input["ID"]=$key;
					$rulecollection->deleteRuleOrder($rule->fields["ranking"]);
					$rule->delete(array('ID'=>$key));
				}
				logEvent(0, "rules", 4, "setup", $_SESSION["glpiname"]." ".$LANG["log"][22]);
				glpi_header($_SERVER['HTTP_REFERER']);
			}
		break;
		case "move_rule":
			if (isset($_POST["item"])&&count($_POST["item"])){
				foreach ($_POST["item"] as $key => $val){
					$rule->getFromDB($key);
					$rulecollection->moveRule($key,$_POST['ranking'],$_POST['move_type']);
				}
			}
		break;
		case "activate_rule":
			if (isset($_POST["item"])){
				$rule = new Rule();
				foreach ($_POST["item"] as $key => $val){
					if ($val==1) {
						$input['ID']=$key;
						$input['active']=$_POST["activate_rule"];
						$rule->update($input);
					}
				}
			}
		break;		
	}
} else if (isset($_POST["replay_rule"])){

	checkRight($rulecollection->right,"w");	

	// Force ini_set is not a good idea
	//ini_set("max_execution_time", "0");
	$timer=new Script_Timer();
	$timer->Start_Timer();
	commonHeader($LANG["rulesengine"][17],$_SERVER['PHP_SELF'],"admin",getCategoryNameToDisplay($rulecollection->rule_type),$rulecollection->rule_type);

	if (!isset($_POST['replay_confirm'])&&$rulecollection->warningBeforeReplayRulesOnExistingDB($_SERVER['PHP_SELF'])){
		commonFooter();
	}

	echo "<div class='center'>"; 
	echo "<table class='tab_cadrehov'>";

	echo "<tr><th><div class='relative'><span><strong>" .$LANG["rulesengine"][36]. "</strong></span>";
	echo " - " .$LANG["rulesengine"][76]. "</th></tr>\n";
	echo "<tr><td align='center'>";
	createProgressBar($LANG["rulesengine"][90]);
	echo "</td></tr>\n";
	echo "</table>";
	echo "</div>";
	commonFooter(true);
	
	$rulecollection->replayRulesOnExistingDB(array(),$_POST);

	changeProgressBarMessage($LANG["rulesengine"][91]." (".timestampToString($timer->Get_Time()).
		")<br /><a href='".$_SERVER['PHP_SELF']."'>".$LANG["buttons"][13]."</a>");
	//glpi_header($_SERVER['PHP_SELF']);
}

commonHeader($LANG["rulesengine"][17],$_SERVER['PHP_SELF'],"admin",getCategoryNameToDisplay($rulecollection->rule_type),$rulecollection->rule_type);

$rulecollection->showForm($_SERVER['PHP_SELF']);
commonFooter();
?>
