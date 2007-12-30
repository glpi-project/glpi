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

class DictionnaryManufacturerCollection extends RuleCachedCollection {

	var $item_table;
	
	function DictionnaryManufacturerCollection() {

		$this->rule_type = RULE_DICTIONNARY_MANUFACTURER;
		$this->rule_class_name = 'DictionnaryManufacturerRule';
		$this->stop_on_first_match=true;
		$this->right="rule_dictionnary_manufacturer";
		$this->item_table="glpi_dropdown_manufacturer";
		
		//Init cache system values
		$this->initCache("glpi_rule_cache_manufacturer");
	}

	function getTitle() {
		global $LANG;
		return $LANG["rulesengine"][36];
	}
	
	function replayRulesOnExistingDB()
	{
		global $DB;

		if (isCommandLine())
			echo "replayRulesOnExistingDB started : " . date("r") . "\n";
		// error_log("DictionnaryManufacturerCollection::replayRulesOnExistingDB");
		
		$types = array(COMPUTER_TYPE, PRINTER_TYPE, SOFTWARE_TYPE, PHONE_TYPE, PERIPHERAL_TYPE, NETWORKING_TYPE, MONITOR_TYPE);
		//$this->deleteCache();

		$Sql="SELECT * FROM " . $this->item_table;
		$result = $DB->query($Sql);
		if ($result) {
			$nb=$DB->numrows($result);
			$step=($nb>20 ? floor($DB->numrows($result)/20) : 1);
		} else {
			$nb=0;
		}
		if ($nb>0) for ($i=0;$data = $DB->fetch_array($result);$i++) {
			if (!($i % $step)) {
				if (isCommandLine()) {
					echo "replayRulesOnExistingDB : $i/$nb\r";
				} else {
					changeProgressBarPosition($i,$nb,"$i / $nb");
				}
			}
			//Replay manufacturer dictionnary
			$ID=externalImportDropdown($this->item_table,addslashes($data["name"]),-1,array(),addslashes($data["comments"]));

			if ($data['ID'] != $ID) {	
				$nbupd=0;		
				foreach ($types as $type)
				{
					$item = new CommonItem;
					$item->setType($type,1);
					$obj = $item->obj;

					$Sql = "UPDATE ".$obj->table." SET FK_glpi_enterprise=".$ID." WHERE FK_glpi_enterprise=".$data['ID'];
					$resupd = $DB->query($Sql);
					$nbupd += ($resupd ? $DB->affected_rows() : -1);					
				}
				$Sql = "DELETE FROM ".$this->item_table." WHERE ID=".$data['ID'];
				$resdel = $DB->query($Sql);
				$nbdel = ($resdel ? $DB->affected_rows() : -1);

				//error_log("DIC0: " . $data['name'] . " : " . $data['ID'] . " => " . $ID . " => ($nbupd,$nbdel)");
			}		
		} // for fetch

		if (isCommandLine()) {
			echo "replayRulesOnExistingDB ended : " . date("r") . "\n";			
		} else {
			changeProgressBarPosition($nb,$nb,"$i / $nb");
		}
	} // function
}

/**
* Rule class store all informations about a GLPI rule :
*   - description
*   - criterias
*   - actions
* 
**/
class DictionnaryManufacturerRule extends RuleCached {

	function DictionnaryManufacturerRule() {
		$this->table = "glpi_rules_descriptions";
		$this->type = -1;
		$this->rule_type = RULE_DICTIONNARY_MANUFACTURER;
		$this->right="rule_dictionnary_manufacturer";
		$this->can_sort=true;
	}

	function maxActionsCount(){
		return 1;
	}

	function getTitle() {
		global $LANG;
		return $LANG["rulesengine"][36];
	}
}
?>
