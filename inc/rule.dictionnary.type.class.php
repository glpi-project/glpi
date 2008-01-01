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
if (!defined('GLPI_ROOT')) {
	die("Sorry. You can't access directly to this file");
}

class DictionnaryTypeComputerCollection extends RuleTypeCollection {

	function DictionnaryTypeComputerCollection() {
		$this->rule_type = RULE_DICTIONNARY_TYPE_COMPUTER;
		$this->rule_class_name = 'DictionnaryTypeComputerRule';
		$this->stop_on_first_match=true;
		$this->right="rule_dictionnary_type";
		$this->item_table="glpi_type_computers";
		
		//Init cache system values
		$this->initCache("glpi_rule_cache_type_computer");
	}

	function getTitle() {
		global $LANG;
		return $LANG["rulesengine"][60];
	}
	
	function getRelatedObject()
	{
		return new Computer;
	}
}

/**
* Rule class store all informations about a GLPI rule :
*   - description
*   - criterias
*   - actions
* 
**/
class DictionnaryTypeComputerRule extends RuleDictionnaryType {

	function DictionnaryTypeComputerRule() {
		$this->table = "glpi_rules_descriptions";
		$this->type = -1;
		$this->rule_type = RULE_DICTIONNARY_TYPE_COMPUTER;
		$this->right="rule_dictionnary_type";
		$this->can_sort=true;
	}

	function getTitle() {
		global $LANG;
		return $LANG["rulesengine"][60];
	}
}

class DictionnaryTypeMonitorCollection extends RuleTypeCollection {

	function DictionnaryTypeMonitorCollection() {
		$this->rule_type = RULE_DICTIONNARY_TYPE_MONITOR;
		$this->rule_class_name = 'DictionnaryTypeMonitorRule';
		$this->stop_on_first_match=true;
		$this->right="rule_dictionnary_type";
		$this->item_table="glpi_type_monitors";

		//Init cache system values
		$this->initCache("glpi_rule_cache_type_monitor");
	}

	function getTitle() {
		global $LANG;
		return $LANG["rulesengine"][61];
	}
	
	function getRelatedObject()
	{
		return new Monitor;
	}
}

/**
* Rule class store all informations about a GLPI rule :
*   - description
*   - criterias
*   - actions
* 
**/
class DictionnaryTypeMonitorRule extends RuleDictionnaryType {

	function DictionnaryTypeMonitorRule() {
		$this->table = "glpi_rules_descriptions";
		$this->type = -1;
		$this->rule_type = RULE_DICTIONNARY_TYPE_MONITOR;
		$this->right="rule_dictionnary_type";
		$this->can_sort=true;
	}

	function getTitle() {
		global $LANG;
		return $LANG["rulesengine"][61];
	}
}

class DictionnaryTypeNetworkingCollection extends RuleTypeCollection {

	function DictionnaryTypeNetworkingCollection() {
		$this->rule_type = RULE_DICTIONNARY_TYPE_NETWORKING;
		$this->rule_class_name = 'DictionnaryTypeNetworkingRule';
		$this->stop_on_first_match=true;
		$this->right="rule_dictionnary_type";
		$this->item_table="glpi_type_networkings";
		
		//Init cache system values
		$this->initCache("glpi_rule_cache_type_networking");
	}

	function getTitle() {
		global $LANG;
		return $LANG["rulesengine"][65];
	}
	
	function getRelatedObject()
	{
		return new Networking;
	}
}

/**
* Rule class store all informations about a GLPI rule :
*   - description
*   - criterias
*   - actions
* 
**/
class DictionnaryTypeNetworkingRule extends RuleDictionnaryType {

	function DictionnaryTypeNetworkingRule() {
		$this->table = "glpi_rules_descriptions";
		$this->type = -1;
		$this->rule_type = RULE_DICTIONNARY_TYPE_NETWORKING;
		$this->right="rule_dictionnary_type";
		$this->can_sort=true;
	}

	function getTitle() {
		global $LANG;
		return $LANG["rulesengine"][65];
	}
}

class DictionnaryTypePeripheralCollection extends RuleTypeCollection {

	function DictionnaryTypePeripheralCollection() {
		$this->rule_type = RULE_DICTIONNARY_TYPE_PERIPHERAL;
		$this->rule_class_name = 'DictionnaryTypePeripheralRule';
		$this->stop_on_first_match=true;
		$this->right="rule_dictionnary_type";
		$this->item_table="glpi_type_peripherals";

		//Init cache system values
		$this->initCache("glpi_rule_cache_type_peripheral");
	}

	function getTitle() {
		global $LANG;
		return $LANG["rulesengine"][63];
	}
	
	function getRelatedObject()
	{
		return new Peripheral;
	}
}

/**
* Rule class store all informations about a GLPI rule :
*   - description
*   - criterias
*   - actions
* 
**/
class DictionnaryTypePeripheralRule extends RuleDictionnaryType {

	function DictionnaryTypePeripheralRule() {
		$this->table = "glpi_rules_descriptions";
		$this->type = -1;
		$this->rule_type = RULE_DICTIONNARY_TYPE_PERIPHERAL;
		$this->right="rule_dictionnary_type";
		$this->can_sort=true;
	}

	function getTitle() {
		global $LANG;
		return $LANG["rulesengine"][63];
	}
}

class DictionnaryTypePrinterCollection extends RuleTypeCollection {

	function DictionnaryTypePrinterCollection() {
		$this->rule_type = RULE_DICTIONNARY_TYPE_PRINTER;
		$this->rule_class_name = 'DictionnaryTypePrinterRule';
		$this->stop_on_first_match=true;
		$this->right="rule_dictionnary_type";
		$this->item_table="glpi_type_printers";

		//Init cache system values
		$this->initCache("glpi_rule_cache_type_printer");
	}

	function getTitle() {
		global $LANG;
		return $LANG["rulesengine"][64];
	}
	
	function getRelatedObject()
	{
		return new Printer;
	}
}

/**
* Rule class store all informations about a GLPI rule :
*   - description
*   - criterias
*   - actions
* 
**/
class DictionnaryTypePrinterRule extends RuleDictionnaryType {

	function DictionnaryTypePrinterRule() {
		$this->table = "glpi_rules_descriptions";
		$this->type = -1;
		$this->rule_type = RULE_DICTIONNARY_TYPE_PRINTER;
		$this->right="rule_dictionnary_type";
		$this->can_sort=true;
	}

	function getTitle() {
		global $LANG;
		return $LANG["rulesengine"][64];
	}
}

?>
