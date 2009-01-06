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
// Original Author of file: Walid Nouh
// Purpose of file:
// ----------------------------------------------------------------------
ini_set("memory_limit","-1");
ini_set("max_execution_time", "0");

if ($argv) {
	for ($i=1;$i<count($argv);$i++)
	{
		//To be able to use = in search filters, enter \= instead in command line
		//Replace the \= by ° not to match the split function
		$arg=str_replace('\=','°',$argv[$i]);
		$it = split("=",$arg);
		$it[0] = eregi_replace('^--','',$it[0]);
		
		//Replace the ° by = the find the good filter 
		$it=str_replace('°','=',$it);
		$_GET[$it[0]] = $it[1];
	}
}

$NEEDED_ITEMS=array(
					"rulesengine",
					"setup",
					"rule.softwarecategories",
					"rule.dictionnary.software","rule.dictionnary.dropdown");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

$CFG_GLPI["debug"]=0;

if (isset($_GET["dictionnary"]))
{
	$rulecollection = getRuleCollectionClass($_GET["dictionnary"]);
	if ($rulecollection)
		if ($_GET["dictionnary"]==RULE_DICTIONNARY_SOFTWARE && isset($_GET["manufacturer"])) {
			$rulecollection->replayRulesOnExistingDB(0,0,array(),$_GET["manufacturer"]);
		} else {
			$rulecollection->replayRulesOnExistingDB();
		}
}
else
{
	
	echo "Usage : php -q -f compute_dictionnary.php dictionnary=<option>  [ manufacturer=ID ]\n";
	echo "Options values :\n";
	echo RULE_DICTIONNARY_SOFTWARE." : softwares\n";
	echo RULE_DICTIONNARY_MANUFACTURER." : manufacturers\n";

	echo "--- Models ---\n";
	echo RULE_DICTIONNARY_MODEL_COMPUTER." : computers\n";
	echo RULE_DICTIONNARY_MODEL_MONITOR." : monitors\n";
	echo RULE_DICTIONNARY_MODEL_PERIPHERAL." : peripherals\n";	
	echo RULE_DICTIONNARY_MODEL_NETWORKING." : networking\n";	
	echo RULE_DICTIONNARY_MODEL_PRINTER." : printers\n";
	echo RULE_DICTIONNARY_MODEL_PHONE." : phones\n";

	echo "--- Types ---\n";
	echo RULE_DICTIONNARY_TYPE_COMPUTER." : computers\n";
	echo RULE_DICTIONNARY_TYPE_MONITOR." : monitors\n";
	echo RULE_DICTIONNARY_TYPE_PERIPHERAL." : peripherals\n";	
	echo RULE_DICTIONNARY_TYPE_NETWORKING." : networking\n";	
	echo RULE_DICTIONNARY_TYPE_PRINTER." : printers\n";
	echo RULE_DICTIONNARY_TYPE_PHONE." : phones\n";
		
}
?>
