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

class RuleDictionnaryDropdown extends RuleCached{

	function RuleDictionnaryDropdown($type){
		parent::RuleCached();
		$this->rule_type=$type;
		$this->can_sort=true;
		$this->right="rule_dictionnary_dropdown";
	}

	function maxActionsCount(){
		return 1;
	}

	function getTitle() {
		global $LANG;
		switch ($this->rule_type){
			case RULE_DICTIONNARY_MANUFACTURER :
				return $LANG["rulesengine"][36];
			break;
			case RULE_DICTIONNARY_MODEL_COMPUTER :
				return $LANG["rulesengine"][50];
			break;
			case RULE_DICTIONNARY_TYPE_COMPUTER :
				return $LANG["rulesengine"][60];
			break;
			case RULE_DICTIONNARY_MODEL_MONITOR :
				return $LANG["rulesengine"][51];
			break;
			case RULE_DICTIONNARY_TYPE_MONITOR :
				return $LANG["rulesengine"][61];
			break;
			case RULE_DICTIONNARY_MODEL_PRINTER :
				return $LANG["rulesengine"][54];
			break;
			case RULE_DICTIONNARY_TYPE_PRINTER :
				return $LANG["rulesengine"][64];
			break;
			case RULE_DICTIONNARY_MODEL_PHONE :
				return $LANG["rulesengine"][52];
			break;
			case RULE_DICTIONNARY_TYPE_PHONE :
				return $LANG["rulesengine"][62];
			break;
			case RULE_DICTIONNARY_MODEL_PERIPHERAL :
				return $LANG["rulesengine"][53];
			break;
			case RULE_DICTIONNARY_TYPE_PERIPHERAL :
				return $LANG["rulesengine"][63];
			break;
			case RULE_DICTIONNARY_MODEL_NETWORKING :
				return $LANG["rulesengine"][55];
			break;
			case RULE_DICTIONNARY_TYPE_NETWORKING :
				return $LANG["rulesengine"][65];
			break;
			case RULE_DICTIONNARY_OS :
				return $LANG["rulesengine"][67];
			break;
			case RULE_DICTIONNARY_OS_SP :
				return $LANG["rulesengine"][68];
			break;
			case RULE_DICTIONNARY_OS_VERSION :
				return $LANG["rulesengine"][68];
			break;
		}
	}

	function showCacheRuleHeader(){
		if (in_array($this->rule_type,array(RULE_DICTIONNARY_MODEL_COMPUTER,
						RULE_DICTIONNARY_MODEL_MONITOR,
						RULE_DICTIONNARY_MODEL_PRINTER,
						RULE_DICTIONNARY_MODEL_PHONE,
						RULE_DICTIONNARY_MODEL_PERIPHERAL,
						RULE_DICTIONNARY_MODEL_NETWORKING,
							))){
			global $LANG;
			echo "<th colspan='3'>".$LANG["rulesengine"][100]." : ".$this->fields["name"]."</th></tr>";
			echo "<tr>";
			echo "<td class='tab_bg_1'>".$LANG["rulesengine"][104]."</td>";
			echo "<td class='tab_bg_1'>".$LANG["common"][5]."</td>";
			echo "<td class='tab_bg_1'>".$LANG["rulesengine"][105]."</td>";
			echo "</tr>";
		} else {
			parent::showCacheRuleHeader();
		}
	}

	function showCacheRuleDetail($fields){
		if (in_array($this->rule_type,array(RULE_DICTIONNARY_MODEL_COMPUTER,
						RULE_DICTIONNARY_MODEL_MONITOR,
						RULE_DICTIONNARY_MODEL_PRINTER,
						RULE_DICTIONNARY_MODEL_PHONE,
						RULE_DICTIONNARY_MODEL_PERIPHERAL,
						RULE_DICTIONNARY_MODEL_NETWORKING,
		))){
			global $LANG;
			echo "<td class='tab_bg_2'>".$fields["old_value"]."</td>";
			echo "<td class='tab_bg_2'>".($fields["manufacturer"]!=''?$fields["manufacturer"]:'')."</td>";		
			echo "<td class='tab_bg_2'>".($fields["new_value"]!=''?$fields["new_value"]:$LANG["rulesengine"][106])."</td>";
		} else {
			parent::showCacheRuleDetail($fields);
		}
	}


}



class DictionnaryDropdownCollection extends RuleCachedCollection{

	var $item_table="";
	

	function DictionnaryDropdownCollection($type){
		$this->rule_type = $type;
		$this->rule_class_name = 'RuleDictionnaryDropdown';
		$this->right="rule_dictionnary_dropdown";

		switch ($this->rule_type){
			case RULE_DICTIONNARY_MANUFACTURER :
				$this->item_table="glpi_dropdown_manufacturer";
				//Init cache system values
				$this->initCache("glpi_rule_cache_manufacturer");
			break;
			case RULE_DICTIONNARY_MODEL_COMPUTER :
				$this->item_table="glpi_dropdown_model";
				//Init cache system values
				$this->initCache("glpi_rule_cache_model_computer",array("name"=>"old_value","manufacturer"=>"manufacturer"));
			break;
			case RULE_DICTIONNARY_TYPE_COMPUTER :
				$this->item_table="glpi_type_computers";
				//Init cache system values
				$this->initCache("glpi_rule_cache_type_computer");
			break;
			case RULE_DICTIONNARY_MODEL_MONITOR :
				$this->item_table="glpi_dropdown_model_monitors";
				//Init cache system values
				$this->initCache("glpi_rule_cache_model_monitor",array("name"=>"old_value","manufacturer"=>"manufacturer"));
			break;
			case RULE_DICTIONNARY_TYPE_MONITOR :
				$this->item_table="glpi_type_monitors";
				//Init cache system values
				$this->initCache("glpi_rule_cache_type_monitor");
			break;
			case RULE_DICTIONNARY_MODEL_PRINTER :
				$this->item_table="glpi_dropdown_model_printers";
				//Init cache system values
				$this->initCache("glpi_rule_cache_model_printer",array("name"=>"old_value","manufacturer"=>"manufacturer"));
			break;
			case RULE_DICTIONNARY_TYPE_PRINTER :
				$this->item_table="glpi_type_printer";
				$this->initCache("glpi_rule_cache_type_printer");
			break;
			case RULE_DICTIONNARY_MODEL_PHONE :
				$this->item_table="glpi_dropdown_model_phones";
				$this->initCache("glpi_rule_cache_model_phone",array("name"=>"old_value","manufacturer"=>"manufacturer"));
			break;
			case RULE_DICTIONNARY_TYPE_PHONE :
				$this->item_table="glpi_type_phones";
				$this->initCache("glpi_rule_cache_type_phone");
			break;
			case RULE_DICTIONNARY_MODEL_PERIPHERAL :
				$this->item_table="glpi_dropdown_model_peripherals";
				$this->initCache("glpi_rule_cache_model_peripheral",array("name"=>"old_value","manufacturer"=>"manufacturer"));
			break;
			case RULE_DICTIONNARY_TYPE_PERIPHERAL :
				$this->item_table="glpi_type_peripherals";
				$this->initCache("glpi_rule_cache_type_peripheral");
			break;
			case RULE_DICTIONNARY_MODEL_NETWORKING :
				$this->item_table="glpi_dropdown_model_networking";
				$this->initCache("glpi_rule_cache_model_networking",array("name"=>"old_value","manufacturer"=>"manufacturer"));
			break;
			case RULE_DICTIONNARY_TYPE_NETWORKING :
				$this->item_table="glpi_type_networkings";
				$this->initCache("glpi_rule_cache_type_networking");
			break;
			case RULE_DICTIONNARY_OS :
				$this->item_table="glpi_dropdown_os";
				$this->initCache("glpi_rule_cache_os");
			break;
			case RULE_DICTIONNARY_OS_SP :
				$this->item_table="glpi_dropdown_os_sp";
				$this->initCache("glpi_rule_cache_os_sp");
			break;
			case RULE_DICTIONNARY_OS_VERSION :
				$this->item_table="glpi_dropdown_os_version";
				$this->initCache("glpi_rule_cache_os_version");
			break;
		}
		

	}

	function getTitle() {
		global $LANG;
		switch ($this->rule_type){
			case RULE_DICTIONNARY_MANUFACTURER :
				return $LANG["rulesengine"][36];
			break;
			case RULE_DICTIONNARY_MODEL_COMPUTER :
				return $LANG["rulesengine"][50];
			break;
			case RULE_DICTIONNARY_TYPE_COMPUTER :
				return $LANG["rulesengine"][60];
			break;
			case RULE_DICTIONNARY_MODEL_MONITOR :
				return $LANG["rulesengine"][51];
			break;
			case RULE_DICTIONNARY_TYPE_MONITOR :
				return $LANG["rulesengine"][61];
			break;
			case RULE_DICTIONNARY_MODEL_PRINTER :
				return $LANG["rulesengine"][54];
			break;
			case RULE_DICTIONNARY_TYPE_PRINTER :
				return $LANG["rulesengine"][64];
			break;
			case RULE_DICTIONNARY_MODEL_PHONE :
				return $LANG["rulesengine"][52];
			break;
			case RULE_DICTIONNARY_TYPE_PHONE :
				return $LANG["rulesengine"][62];
			break;
			case RULE_DICTIONNARY_MODEL_PERIPHERAL :
				return $LANG["rulesengine"][53];
			break;
			case RULE_DICTIONNARY_TYPE_PERIPHERAL :
				return $LANG["rulesengine"][63];
			break;
			case RULE_DICTIONNARY_MODEL_NETWORKING :
				return $LANG["rulesengine"][55];
			break;
			case RULE_DICTIONNARY_TYPE_NETWORKING :
				return $LANG["rulesengine"][65];
			break;
			case RULE_DICTIONNARY_OS :
				return $LANG["rulesengine"][67];
			break;
			case RULE_DICTIONNARY_OS_SP :
				return $LANG["rulesengine"][68];
			break;
			case RULE_DICTIONNARY_OS_VERSION :
				return $LANG["rulesengine"][68];
			break;
		}
	}

	function getRuleClass(){
		return new $this->rule_class_name($this->rule_type);
	}


	function replayRulesOnExistingDB($offset=0,$maxtime=0){
		global $DB,$LANG;



		// Model check : need to check using manufacturer extra data so specific function
		if (in_array($this->rule_type,array(RULE_DICTIONNARY_MODEL_COMPUTER,
						RULE_DICTIONNARY_MODEL_MONITOR,
						RULE_DICTIONNARY_MODEL_PRINTER,
						RULE_DICTIONNARY_MODEL_PHONE,
						RULE_DICTIONNARY_MODEL_PERIPHERAL,
						RULE_DICTIONNARY_MODEL_NETWORKING,
		))){
			return $this->replayRulesOnExistingDBForModel($offset,$maxtime);
		}


		if (isCommandLine())
			echo "replayRulesOnExistingDB started : " . date("r") . "\n";

		// Get All items
		$Sql="SELECT * FROM " . $this->item_table;
		if ($offset) {
			$Sql .= " LIMIT $offset,999999999";
		} 
		
		$result = $DB->query($Sql);

		$nb = $DB->numrows($result)+$offset;
		$i  = $offset;
		if ($result && $nb>$offset) {
			// Step to refresh progressbar
			$step=($nb>20 ? floor($nb/20) : 1);
			$send = array ();
			$send["tablename"] = $this->item_table;
			while ($data = $DB->fetch_array($result)){
				if (!($i % $step)) {
					if (isCommandLine()) {
						echo "replayRulesOnExistingDB : $i/$nb\r";
					} else {
						changeProgressBarPosition($i,$nb,"$i / $nb");
					}
				}

				//Replay Type dictionnary
				$ID=externalImportDropdown($this->item_table,addslashes($data["name"]),-1,array(),addslashes($data["comments"]));
				
				if ($data['ID'] != $ID) {
					$tomove[$data['ID']]=$ID;
					$send["oldID"] = $data['ID'];
					$send["newID"] = $ID;
					replaceDropDropDown($send);
				}		

				$i++;
				if ($maxtime) {
					$crt=explode(" ",microtime());
					if ($crt[0]+$crt[1] > $maxtime) {
						break;
					}
				}
			} // end while 
		}
		
		if (isCommandLine()) {
			echo "replayRulesOnExistingDB ended : " . date("r") . "\n";			
		} else {
			changeProgressBarPosition($i,$nb,"$i / $nb");
		}
		
		return ($i==$nb ? -1 : $i);
	} // function


	function replayRulesOnExistingDBForModel($offset=0,$maxtime=0){
		global $DB,$LANG;


		if (isCommandLine())
			echo "replayRulesOnExistingDB started : " . date("r") . "\n";

		// Model check : need to check using manufacturer extra data
		$model_table="";
		// Find linked table to model
		$RELATION = getDbRelations();
		if (isset ($RELATION[$this->item_table])){
			foreach ($RELATION[$this->item_table] as $table => $field){ 
				if ($field=="model"){
					$model_table=$table;
				}
			}
		} 
		if (empty($model_table)) {
			echo "Error replaying rules";
			return false;
		}


		// Need to give manufacturer from item table
		$Sql="SELECT DISTINCT glpi_dropdown_manufacturer.ID AS idmanu, glpi_dropdown_manufacturer.name AS manufacturer, ".
			$this->item_table.".ID AS ID, ".$this->item_table.".name AS name, ".$this->item_table.".comments AS comments ".
			"FROM ".$this->item_table.", $model_table LEFT JOIN glpi_dropdown_manufacturer ON ($model_table.FK_glpi_enterprise=glpi_dropdown_manufacturer.ID) ".
			"WHERE $model_table.model=".$this->item_table.".ID ";
		if ($offset) {
			$Sql .= " LIMIT $offset,999999999";
		} 
		$result = $DB->query($Sql);

		$nb = $DB->numrows($result)+$offset;
		$i  = $offset;
		
		if ($result && $nb>$offset) {
			// Step to refresh progressbar
			$step=($nb>20 ? floor($nb/20) : 1);
			$tocheck=array();
			while ($data = $DB->fetch_array($result)){

				if (!($i % $step)) {
					if (isCommandLine()) {
						echo "replayRulesOnExistingDB : $i/$nb\r";
					} else {
						changeProgressBarPosition($i,$nb,"$i / $nb");
					}
				}
				// Model case
				if (isset($data["manufacturer"])){
					$data["manufacturer"] = processManufacturerName($data["manufacturer"]);
				}

				//Replay Type dictionnary
				$ID=externalImportDropdown($this->item_table,addslashes($data["name"]),-1,$data,addslashes($data["comments"]));

				if ($data['ID'] != $ID) {
					$tocheck[$data["ID"]][]=$ID;

					$sql = "UPDATE $model_table SET model=".$ID." WHERE FK_glpi_enterprise=".$data['idmanu']." AND model=".$data['ID'];
					$DB->query($sql);
				}		

				$i++;
				if ($maxtime) {
					$crt=explode(" ",microtime());
					if ($crt[0]+$crt[1] > $maxtime) {
						break;
					}
				}
			} 

			foreach ($tocheck AS $ID => $tab) 	{
				$sql="SELECT COUNT(*) FROM $model_table WHERE model=$ID";
				$result = $DB->query($sql);
				$deletecartmodel=false;
				// No item left : delete old item
				if ($result && $DB->result($result,0,0)==0) {
					$Sql = "DELETE FROM ".$this->item_table." WHERE ID=".$ID;
					$resdel = $DB->query($Sql);
					$deletecartmodel=true;
				} 
				// Manage cartridge assoc Update items
				if ($this->rule_type==RULE_DICTIONNARY_MODEL_PRINTER){
					$sql="SELECT * FROM glpi_cartridges_assoc WHERE FK_glpi_dropdown_model_printers = '$ID'";
					if ($result=$DB->query($sql)){
						if ($DB->numrows($result)){	
							// Get compatible cartridge type
							$carttype=array();
							while ($data=$DB->fetch_array($result)){
								$carttype[]=$data['FK_glpi_cartridges_type'];
							}
							// Delete cartrodges_assoc
							if ($deletecartmodel){
								$sql="DELETE FROM glpi_cartridges_assoc WHERE FK_glpi_dropdown_model_printers = 'ID'";
								$DB->query($sql);
							}
							// Add new assoc
							if (!class_exists('CartridgeType')){
								include_once (GLPI_ROOT . "/inc/cartridge.function.php");
							}
							$ct=new CartridgeType();
							foreach ($carttype as $cartID){
								foreach ($tab as $model){
									$ct->addCompatibleType($cartID,$model);
								}
							}
						}
					}						
				}
	
			} // each tocheck
		}
		if (isCommandLine()) {
			echo "replayRulesOnExistingDB ended : " . date("r") . "\n";			
		} else {
			changeProgressBarPosition($i,$nb,"$i / $nb");
		}
		return ($i==$nb ? -1 : $i);
	}

}	
?>
