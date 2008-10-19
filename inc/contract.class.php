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

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}


/// Contract class
class Contract extends CommonDBTM {

	/**
	 * Constructor
	 **/
	function __construct () {
		$this->table="glpi_contracts";
		$this->type=CONTRACT_TYPE;
		$this->entity_assign=true;
		$this->may_be_recursive=true;
	}

	function post_getEmpty () {
		global $CFG_GLPI;
		$this->fields["alert"]=$CFG_GLPI["contract_alerts"];
	}

	function cleanDBonPurge($ID) {

		global $DB;

		$query2 = "DELETE FROM glpi_contract_enterprise WHERE (FK_contract = '$ID')";
		$DB->query($query2);

		$query3 = "DELETE FROM glpi_contract_device WHERE (FK_contract = '$ID')";
		$DB->query($query3);
	}

	function defineTabs($withtemplate){
		global $LANG;
		$ong[1]=$LANG["title"][26];
		if (haveRight("document","r"))	
			$ong[5]=$LANG["Menu"][27];
		if (haveRight("link","r"))	
			$ong[7]=$LANG["title"][34];
		if (haveRight("notes","r"))
			$ong[10]=$LANG["title"][37];
		return $ong;
	}

	function pre_updateInDB($input,$updates,$oldvalues) {

		// Clean end alert if begin_date is after old one
		// Or if duration is greater than old one
		if ((isset($oldvalues['begin_date'])
			&& ($oldvalues['begin_date'] < $this->fields['begin_date'] ))
		|| ( isset($oldvalues['duration'])
			&& ($oldvalues['duration'] < $this->fields['duration'] ))
		){
			$alert=new Alert();
			$alert->clear($this->type,$this->fields['ID'],ALERT_END);
		}

		// Clean notice alert if begin_date is after old one
		// Or if duration is greater than old one
		// Or if notice is lesser than old one
		if ((isset($oldvalues['begin_date'])
			&& ($oldvalues['begin_date'] < $this->fields['begin_date'] ))
		|| ( isset($oldvalues['duration'])
			&& ($oldvalues['duration'] < $this->fields['duration'] ))
		|| ( isset($oldvalues['notice'])
			&& ($oldvalues['notice'] > $this->fields['notice'] ))
		){
			$alert=new Alert();
			$alert->clear($this->type,$this->fields['ID'],ALERT_NOTICE);
		}
		return array($input,$updates);
	}

	/**
	 * Print the contract form
	 *
	 *@param $target filename : where to go when done.
	 *@param $ID Integer : Id of the item to print
	 *@param $withtemplate integer template or basic item
	 *
	  *@return boolean item found
	 **/
	function showForm ($target,$ID,$withtemplate='') {
		// Show Contract or blank form

		global $CFG_GLPI,$LANG;

		if (!haveRight("contract","r")) return false;

		$use_cache=true;

		if ($ID > 0){
			$this->check($ID,'r');
		} else {
			// Create item 
			$this->check(-1,'w');
			$use_cache=false;
			$this->getEmpty();
		} 

		$can_edit=$this->can($ID,'w');

		$this->showTabs($ID, $withtemplate,$_SESSION['glpi_tab']);

		if ($can_edit) { 
			echo "<form name='form' method='post' action=\"$target\"><div class='center' id='tabsbody'>";
			if (empty($ID)||$ID<0){
				echo "<input type='hidden' name='FK_entities' value='".$_SESSION["glpiactive_entity"]."'>";
			}
		}
			
		echo "<table class='tab_cadre_fixe'>";

		$this->showFormHeader($ID,2);

		if (!$use_cache||!($CFG_GLPI["cache"]->start($ID."_".$_SESSION["glpilanguage"],"GLPI_".$this->type))) {

			echo "<tr class='tab_bg_1'><td>".$LANG["common"][16].":		</td><td>";
			autocompletionTextField("name","glpi_contracts","name",$this->fields["name"],40,$this->fields["FK_entities"]);
			echo "</td>";

			echo "<td>".$LANG["financial"][6].":		</td><td >";
			dropdownValue("glpi_dropdown_contract_type","contract_type",$this->fields["contract_type"]);
			echo "</td></tr>";

			echo "<tr class='tab_bg_1'><td>".$LANG["financial"][4].":		</td>";
			echo "<td><input type='text' name='num' value=\"".$this->fields["num"]."\" size='25'></td>";
	
			echo "<td colspan='2'></td></tr>";
	
			echo "<tr class='tab_bg_1'><td>".$LANG["financial"][5].":		</td><td>";
			echo "<input type='text' name='cost' value=\"".formatNumber($this->fields["cost"],true)."\" size='16'>";
			echo "</td>";
	
			echo "<td>".$LANG["search"][8].":	</td>";
			echo "<td>";
			showDateFormItem("begin_date",$this->fields["begin_date"]);
			echo "</td></tr>";
	
	
			echo "<tr class='tab_bg_1'><td>".$LANG["financial"][8].":		</td><td>";
			dropdownInteger("duration",$this->fields["duration"],0,120);
			echo " ".$LANG["financial"][57];
			if (!empty($this->fields["begin_date"])){
				echo " -> ".getWarrantyExpir($this->fields["begin_date"],$this->fields["duration"]);
			}
			echo "</td>";
	
			echo "<td>".$LANG["financial"][13].":		</td><td>";
			autocompletionTextField("compta_num","glpi_contracts","compta_num",$this->fields["compta_num"],40,$this->fields["FK_entities"]);
	
			echo "</td></tr>";
	
			echo "<tr class='tab_bg_1'><td>".$LANG["financial"][69].":		</td><td>";
			dropdownContractPeriodicity("periodicity",$this->fields["periodicity"]);
			echo "</td>";
	
	
			echo "<td>".$LANG["financial"][10].":		</td><td>";
			dropdownInteger("notice",$this->fields["notice"],0,120);
			echo " ".$LANG["financial"][57];
			if (!empty($this->fields["begin_date"]) && $this->fields["notice"]>0){
				echo " -> ".getWarrantyExpir($this->fields["begin_date"],$this->fields["duration"],$this->fields["notice"]);
			}
			echo "</td></tr>";
	
			echo "<tr class='tab_bg_1'><td>".$LANG["financial"][107].":		</td><td>";
			dropdownContractRenewal("renewal",$this->fields["renewal"]);
			echo "</td>";
	
	
			echo "<td>".$LANG["financial"][11].":		</td>";
			echo "<td>";
			dropdownContractPeriodicity("facturation",$this->fields["facturation"]);
			echo "</td></tr>";
	
			echo "<tr class='tab_bg_1'><td>".$LANG["financial"][83].":		</td><td>";
			dropdownInteger("device_countmax",$this->fields["device_countmax"],0,200);
			echo "</td>";
	
	
			echo "<td>".$LANG["common"][41]."</td>";
			echo "<td>";
			dropdownContractAlerting("alert",$this->fields["alert"]);
			echo "</td></tr>";
	
	
	
			echo "<tr class='tab_bg_1'><td valign='top'>";
			echo $LANG["common"][25].":	</td>";
			echo "<td align='center' colspan='3'><textarea cols='50' rows='4' name='comments' >".$this->fields["comments"]."</textarea>";
			echo "</td></tr>";
	
			echo "<tr class='tab_bg_2'><td>".$LANG["financial"][59].":		</td>";
			echo "<td colspan='3'>&nbsp;</td>";
			echo "</tr>";

			echo "<tr class='tab_bg_1'><td>".$LANG["financial"][60].":		</td><td colspan='3'>";
			echo $LANG["buttons"][33].":";
			dropdownHours("week_begin_hour",$this->fields["week_begin_hour"]);	
			echo $LANG["buttons"][32].":";
			dropdownHours("week_end_hour",$this->fields["week_end_hour"]);	
			echo "</td></tr>";

			echo "<tr class='tab_bg_1'><td>".$LANG["financial"][61].":		</td><td colspan='3'>";
			dropdownYesNo("saturday",$this->fields["saturday"]);
			echo $LANG["buttons"][33].":";
			dropdownHours("saturday_begin_hour",$this->fields["saturday_begin_hour"]);	
			echo $LANG["buttons"][32].":";
			dropdownHours("saturday_end_hour",$this->fields["saturday_end_hour"]);	
			echo "</td></tr>";

			echo "<tr class='tab_bg_1'><td>".$LANG["financial"][62].":		</td><td colspan='3'>";
			dropdownYesNo("monday",$this->fields["monday"]);
			echo $LANG["buttons"][33].":";
			dropdownHours("monday_begin_hour",$this->fields["monday_begin_hour"]);	
			echo $LANG["buttons"][32].":";
			dropdownHours("monday_end_hour",$this->fields["monday_end_hour"]);	
			echo "</td></tr>";
			if ($use_cache){
				$CFG_GLPI["cache"]->end();
			}
		}

		if ($can_edit) {
			echo "<tr>";

			if ($ID>0) {

				echo "<td class='tab_bg_2' valign='top' colspan='2'>";
				echo "<input type='hidden' name='ID' value=\"$ID\">\n";
				echo "<div class='center'><input type='submit' name='update' value=\"".$LANG["buttons"][7]."\" class='submit'></div>";
				echo "</td>\n\n";

				echo "<td class='tab_bg_2' valign='top'  colspan='2'>\n";
				if (!$this->fields["deleted"])
					echo "<div class='center'><input type='submit' name='delete' value=\"".$LANG["buttons"][6]."\" class='submit'></div>";
				else {
					echo "<div class='center'><input type='submit' name='restore' value=\"".$LANG["buttons"][21]."\" class='submit'>";

					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' name='purge' value=\"".$LANG["buttons"][22]."\" class='submit'></div>";
				}

				echo "</td>";
				echo "</tr>";

			} else {

				echo "<td class='tab_bg_2' valign='top' colspan='4'>";
				echo "<div class='center'><input type='submit' name='add' value=\"".$LANG["buttons"][8]."\" class='submit'></div>";
				echo "</td>";
				echo "</tr>";
			}
			echo "</table></div></form>";

		} else { // can't edit
			echo "</table></div>";
		}
	
		echo "<div id='tabcontent'></div>";
		echo "<script type='text/javascript'>loadDefaultTab();</script>";

		return true;
	}
	
	/**
	 * Can I change recusvive flag to false
	 * check if there is "linked" object in another entity
	 * 
	 * Overloaded from CommonDBTM
	 *
	 * @return booleen
	 **/
	function canUnrecurs () {

		global $DB, $CFG_GLPI, $LINK_ID_TABLE;
		
		$ID  = $this->fields['ID'];
		$ent = $this->fields['FK_entities'];

		if ($ID<0 || !$this->fields['recursive']) {
			return true;
		}

		if (!parent::canUnrecurs()) {
			return false;
		}
		
		// Search linked device infocom
		$sql = "SELECT DISTINCT device_type FROM glpi_contract_device WHERE FK_contract=$ID";
		$res = $DB->query($sql);
		
		if ($res) while ($data = $DB->fetch_assoc($res)) {
			if (isset($LINK_ID_TABLE[$data["device_type"]]) && 
				in_array($table=$LINK_ID_TABLE[$data["device_type"]], $CFG_GLPI["specif_entities_tables"])) {

				error_log("Contract::canUnrecurs for $table");
				if (countElementsInTable("glpi_contract_device, $table", 
					"glpi_contract_device.FK_contract=$ID AND glpi_contract_device.device_type=".$data["device_type"]." AND glpi_contract_device.FK_device=$table.ID AND $table.FK_entities!=$ent")>0) {
						return false;						
				}
			}			
		}
		
		return true;
	}

}

?>
