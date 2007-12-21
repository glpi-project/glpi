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
if (!defined('GLPI_ROOT')) {
	die("Sorry. You can't access directly to this file");
}

class DictionnaryOSCollection extends RuleDictionnaryCollection {

	var $item_table;
	
	function DictionnaryOSCollection() {

		$this->rule_type = RULE_DICTIONNARY_OS;
		$this->rule_class_name = 'DictionnaryOSRule';
		$this->stop_on_first_match=true;
		$this->right="rule_dictionnary_os";
		$this->item_table="glpi_dropdown_os";
		
		//Init cache system values
		$this->initCache("glpi_rule_cache_os");
	}

	function getTitle() {
		global $LANG;
		return $LANG["rulesengine"][67];
	}
	
	function replayRulesOnExistingDB()
	{
		global $DB;

		// error_log("DictionnaryManufacturerCollection::replayRulesOnExistingDB");
		$Sql="SELECT * FROM " . $this->item_table;
		$result = $DB->query($Sql);
		if ($result && $DB->numrows($result)>0) for ($i=0;$data = $DB->fetch_array($result);$i++) {
			//Replay os dictionnary
			$ID=externalImportDropdown($this->item_table,addslashes($data["name"]),-1,array(),addslashes($data["comments"]));

			if ($data['ID'] != $ID) {	
				$nbupd=0;		

				$item = new Computer;
				$Sql = "UPDATE glpi_computers SET os=".$ID." WHERE os=".$data['ID'];
					
				$resupd = $DB->query($Sql);
				$nbupd += ($resupd ? $DB->affected_rows() : -1);					

				$item = new Software;
				$Sql = "UPDATE glpi_software SET platform=".$ID." WHERE platform=".$data['ID'];
					
				$resupd = $DB->query($Sql);
				$nbupd += ($resupd ? $DB->affected_rows() : -1);					


				$Sql = "DELETE FROM ".$this->item_table." WHERE ID=".$data['ID'];
				$resdel = $DB->query($Sql);
				$nbdel = ($resdel ? $DB->affected_rows() : -1);

				// error_log("DIC0: " . $data['name'] . " : " . $data['ID'] . " => " . $ID . " => ($nbupd,$nbdel)");
			}		
		} // for fetch
	} // function
}

/**
* Rule class store all informations about a GLPI rule :
*   - description
*   - criterias
*   - actions
* 
**/
class DictionnaryOSRule extends RuleDictionnary {

	function DictionnaryOSRule() {
		$this->table = "glpi_rules_descriptions";
		$this->type = -1;
		$this->rule_type = RULE_DICTIONNARY_OS;
		$this->right="rule_dictionnary_os";
		$this->can_sort=true;
	}

	function maxActionsCount(){
		return 1;
	}

	function getTitle() {
		global $LANG;
		return $LANG["rulesengine"][67];
	}
}


class DictionnaryOSSPCollection extends RuleDictionnaryCollection {

	var $item_table;
	
	function DictionnaryOSSPCollection() {

		$this->rule_type = RULE_DICTIONNARY_OS_SP;
		$this->rule_class_name = 'DictionnaryOSSPRule';
		$this->stop_on_first_match=true;
		$this->right="rule_dictionnary_os";
		$this->item_table="glpi_dropdown_os_sp";
		
		//Init cache system values
		$this->initCache("glpi_rule_cache_os_sp");
	}

	function getTitle() {
		global $LANG;
		return $LANG["rulesengine"][68];
	}
	
	function replayRulesOnExistingDB()
	{
		global $DB;

		// error_log("DictionnaryManufacturerCollection::replayRulesOnExistingDB");
		$Sql="SELECT * FROM " . $this->item_table;
		$result = $DB->query($Sql);
		if ($result && $DB->numrows($result)>0) for ($i=0;$data = $DB->fetch_array($result);$i++) {
			//Replay manufacturer dictionnary
			$ID=externalImportDropdown($this->item_table,addslashes($data["name"]),-1,array(),addslashes($data["comments"]));

			if ($data['ID'] != $ID) {	
				$nbupd=0;		

				$item = new Computer;
				$Sql = "UPDATE glpi_computers SET os_sp=".$ID." WHERE os_sp=".$data['ID'];
					
				$resupd = $DB->query($Sql);
				$nbupd += ($resupd ? $DB->affected_rows() : -1);					

				$Sql = "DELETE FROM ".$this->item_table." WHERE ID=".$data['ID'];
				$resdel = $DB->query($Sql);
				$nbdel = ($resdel ? $DB->affected_rows() : -1);

				// error_log("DIC0: " . $data['name'] . " : " . $data['ID'] . " => " . $ID . " => ($nbupd,$nbdel)");
			}		
		} // for fetch
	} // function
}

/**
* Rule class store all informations about a GLPI rule :
*   - description
*   - criterias
*   - actions
* 
**/
class DictionnaryOSSPRule extends RuleDictionnary {

	function DictionnaryOSSPRule() {
		$this->table = "glpi_rules_descriptions";
		$this->type = -1;
		$this->rule_type = RULE_DICTIONNARY_OS_SP;
		$this->right="rule_dictionnary_os";
		$this->can_sort=true;
	}

	function maxActionsCount(){
		return 1;
	}

	function getTitle() {
		global $LANG;
		return $LANG["rulesengine"][68];
	}
}


class DictionnaryOSVersionCollection extends RuleDictionnaryCollection {

	var $item_table;
	
	function DictionnaryOSVersionCollection() {

		$this->rule_type = RULE_DICTIONNARY_OS_VERSION;
		$this->rule_class_name = 'DictionnaryOSVersionRule';
		$this->stop_on_first_match=true;
		$this->right="rule_dictionnary_os";
		$this->item_table="glpi_dropdown_os_version";
		
		//Init cache system values
		$this->initCache("glpi_rule_cache_os_version");
	}

	function getTitle() {
		global $LANG;
		return $LANG["rulesengine"][69];
	}
	
	function replayRulesOnExistingDB()
	{
		global $DB;

		// error_log("DictionnaryManufacturerCollection::replayRulesOnExistingDB");
		$Sql="SELECT * FROM " . $this->item_table;
		$result = $DB->query($Sql);
		if ($result && $DB->numrows($result)>0) for ($i=0;$data = $DB->fetch_array($result);$i++) {
			//Replay manufacturer dictionnary
			$ID=externalImportDropdown($this->item_table,addslashes($data["name"]),-1,array(),addslashes($data["comments"]));

			if ($data['ID'] != $ID) {	
				$nbupd=0;		

				$item = new Computer;
				$Sql = "UPDATE glpi_computers SET os_version=".$ID." WHERE os_version=".$data['ID'];
					
				$resupd = $DB->query($Sql);
				$nbupd += ($resupd ? $DB->affected_rows() : -1);					

				$Sql = "DELETE FROM ".$this->item_table." WHERE ID=".$data['ID'];
				$resdel = $DB->query($Sql);
				$nbdel = ($resdel ? $DB->affected_rows() : -1);

				// error_log("DIC0: " . $data['name'] . " : " . $data['ID'] . " => " . $ID . " => ($nbupd,$nbdel)");
			}		
		} // for fetch
	} // function
}

/**
* Rule class store all informations about a GLPI rule :
*   - description
*   - criterias
*   - actions
* 
**/
class DictionnaryOSVersionRule extends RuleDictionnary {

	function DictionnaryOSVersionRule() {
		$this->table = "glpi_rules_descriptions";
		$this->type = -1;
		$this->rule_type = RULE_DICTIONNARY_OS_VERSION;
		$this->right="rule_dictionnary_os";
		$this->can_sort=true;
	}

	function maxActionsCount(){
		return 1;
	}

	function getTitle() {
		global $LANG;
		return $LANG["rulesengine"][69];
	}
}

?>
