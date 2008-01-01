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

class DictionnaryModelComputerCollection extends RuleModelCollection {

	function DictionnaryModelComputerCollection() {
		$this->rule_type = RULE_DICTIONNARY_MODEL_COMPUTER;
		$this->rule_class_name = 'DictionnaryModelComputerRule';
		$this->stop_on_first_match=true;
		$this->right="rule_dictionnary_model";
		$this->item_table="glpi_dropdown_model";

		//Init cache system values
		$this->initCache("glpi_rule_cache_model_computer",array("name"=>"old_value","manufacturer"=>"manufacturer"));
	}

	function getTitle() {
		global $LANG;
		return $LANG["rulesengine"][50];
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
class DictionnaryModelComputerRule extends RuleDictionnaryModel {

	function DictionnaryModelComputerRule() {
		$this->table = "glpi_rules_descriptions";
		$this->type = -1;
		$this->rule_type = RULE_DICTIONNARY_MODEL_COMPUTER;
		$this->right="rule_dictionnary_model";
		$this->can_sort=true;
	}

	function getTitle() {
		global $LANG;
		return $LANG["rulesengine"][50];
	}
}

class DictionnaryModelMonitorCollection extends RuleModelCollection {

	function DictionnaryModelMonitorCollection() {
		$this->rule_type = RULE_DICTIONNARY_MODEL_MONITOR;
		$this->rule_class_name = 'DictionnaryModelMonitorRule';
		$this->stop_on_first_match=true;
		$this->right="rule_dictionnary_model";
		$this->item_table = "glpi_dropdown_model_monitors";

		//Init cache system values
		$this->initCache("glpi_rule_cache_model_monitor",array("name"=>"old_value","manufacturer"=>"manufacturer"));
	}

	function getTitle() {
		global $LANG;
		return $LANG["rulesengine"][51];
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
class DictionnaryModelMonitorRule extends RuleDictionnaryModel {

	function DictionnaryModelMonitorRule() {
		$this->table = "glpi_rules_descriptions";
		$this->type = -1;
		$this->rule_type = RULE_DICTIONNARY_MODEL_MONITOR;
		$this->right="rule_dictionnary_model";
		$this->can_sort=true;
	}

	function getTitle() {
		global $LANG;
		return $LANG["rulesengine"][52];
	}
}

class DictionnaryModelNetworkingCollection extends RuleModelCollection {

	function DictionnaryModelNetworkingCollection() {
		$this->rule_type = RULE_DICTIONNARY_MODEL_NETWORKING;
		$this->rule_class_name = 'DictionnaryModelNetworkingRule';
		$this->stop_on_first_match=true;
		$this->right="rule_dictionnary_model";
		$this->item_table="glpi_dropdown_model_networking";

		//Init cache system values
		$this->initCache("glpi_rule_cache_model_networking",array("name"=>"old_value","manufacturer"=>"manufacturer"));
	}

	function getTitle() {
		global $LANG;
		return $LANG["rulesengine"][55];
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
class DictionnaryModelNetworkingRule extends RuleDictionnaryModel {

	function DictionnaryModelNetworkingRule() {
		$this->table = "glpi_rules_descriptions";
		$this->type = -1;
		$this->rule_type = RULE_DICTIONNARY_MODEL_NETWORKING;
		$this->right="rule_dictionnary_model";
		$this->can_sort=true;
	}

	function getTitle() {
		global $LANG;
		return $LANG["rulesengine"][55];
	}
}

class DictionnaryModelPeripheralCollection extends RuleModelCollection {

	function DictionnaryModelPeripheralCollection() {
		$this->rule_type = RULE_DICTIONNARY_MODEL_PERIPHERAL;
		$this->rule_class_name = 'DictionnaryModelPeripheralRule';
		$this->stop_on_first_match=true;
		$this->right="rule_dictionnary_model";
		$this->item_table = "glpi_dropdown_model_peripherals";

		//Init cache system values
		$this->initCache("glpi_rule_cache_model_peripheral",array("name"=>"old_value","manufacturer"=>"manufacturer"));
	}

	function getTitle() {
		global $LANG;
		return $LANG["rulesengine"][54];
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
class DictionnaryModelPeripheralRule extends RuleDictionnaryModel {

	function DictionnaryModelPeripheralRule() {
		$this->table = "glpi_rules_descriptions";
		$this->type = -1;
		$this->rule_type = RULE_DICTIONNARY_MODEL_PERIPHERAL;
		$this->right="rule_dictionnary_model";
		$this->can_sort=true;
	}

	function getTitle() {
		global $LANG;
		return $LANG["rulesengine"][54];
	}
}

class DictionnaryModelPrinterCollection extends RuleModelCollection {

	function DictionnaryModelPrinterCollection() {
		$this->rule_type = RULE_DICTIONNARY_MODEL_PRINTER;
		$this->rule_class_name = 'DictionnaryModelPrinterRule';
		$this->stop_on_first_match=true;
		$this->right="rule_dictionnary_model";
		$this->item_table = "glpi_dropdown_model_printers";

		//Init cache system values
		$this->initCache("glpi_rule_cache_model_printer",array("name"=>"old_value","manufacturer"=>"manufacturer"));
	}

	function getTitle() {
		global $LANG;
		return $LANG["rulesengine"][54];
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
class DictionnaryModelPrinterRule extends RuleDictionnaryModel {

	function DictionnaryModelPrinterRule() {
		$this->table = "glpi_rules_descriptions";
		$this->type = -1;
		$this->rule_type = RULE_DICTIONNARY_MODEL_PRINTER;
		$this->right="rule_dictionnary_model";
		$this->can_sort=true;
	}

	function getTitle() {
		global $LANG;
		return $LANG["rulesengine"][54];
	}
}


?>
