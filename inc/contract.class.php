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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}



class Contract extends CommonDBTM {


	function Contract () {
		$this->table="glpi_contracts";
		$this->type=CONTRACT_TYPE;
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

	function defineOnglets($withtemplate){
		global $LANG;
		$ong[1]=$LANG["title"][26];
		if (haveRight("document","r"))	
			$ong[5]=$LANG["title"][25];
		if (haveRight("link","r"))	
			$ong[7]=$LANG["title"][34];
		if (haveRight("notes","r"))
			$ong[10]=$LANG["title"][37];
		return $ong;
	}


	/**
	 * Print the contract form
	 *
	 *
	 * Print general contract form
	 *
	 *@param $target filename : where to go when done.
	 *@param $ID Integer : Id of the contact to print
	 *@param $withtemplate='' boolean : template or basic item
	 *
	 *@return Nothing (display)
	 *
	 **/
	function showForm ($target,$ID,$withtemplate='') {
		// Show Contract or blank form

		global $CFG_GLPI,$LANG;

		if (!haveRight("contract_infocom","r")) return false;

		$con_spotted=false;
		$use_cache=true;
		if (!$ID) {
			$use_cache=false;
			if($this->getEmpty()) $con_spotted = true;
		} else {
			if($this->getfromDB($ID)&&haveAccessToEntity($this->fields["FK_entities"])) $con_spotted = true;
		}

		if ($con_spotted){

			$this->showOnglets($ID, $withtemplate,$_SESSION['glpi_onglet']);

			echo "<form name='form' method='post' action=\"$target\"><div class='center'>";

			if (empty($ID)){
				echo "<input type='hidden' name='FK_entities' value='".$_SESSION["glpiactive_entity"]."'>";
			}

			echo "<table class='tab_cadre_fixe'>";
			echo "<tr><th colspan='4'>";
			if (!$ID) {
				echo $LANG["financial"][36];
			} else {
				echo $LANG["common"][2]." $ID";
			}		
			if (isMultiEntitiesMode()){
				echo "&nbsp;(".getDropdownName("glpi_entities",$this->fields["FK_entities"]).")";
			}

			echo "</th></tr>";

			if (!$use_cache||!($CFG_GLPI["cache"]->start($ID."_".$_SESSION["glpilanguage"],"GLPI_".$this->type))) {

				echo "<tr class='tab_bg_1'><td>".$LANG["common"][16].":		</td><td>";
				autocompletionTextField("name","glpi_contracts","name",$this->fields["name"],25,$this->fields["FK_entities"]);
				echo "</td>";

				echo "<td>".$LANG["financial"][6].":		</td><td >";
				dropdownValue("glpi_dropdown_contract_type","contract_type",$this->fields["contract_type"]);
				echo "</td></tr>";
	
	
				echo "<tr class='tab_bg_1'><td>".$LANG["financial"][4].":		</td>";
				echo "<td><input type='text' name='num' value=\"".$this->fields["num"]."\" size='25'></td>";
	
				echo "<td>".$LANG["search"][8].":	</td>";
				echo "<td>";
				showCalendarForm("form","begin_date",$this->fields["begin_date"]);	
				echo "</td>";
				echo "</tr>";
	
	
				echo "<tr class='tab_bg_1'><td>".$LANG["financial"][5].":		</td><td>";
				echo "<input type='text' name='cost' value=\"".number_format($this->fields["cost"],$CFG_GLPI["decimal_number"],'.','')."\" size='16'>";
				echo "</td>";
	
				echo "<td>".$LANG["financial"][13].":		</td><td>";
				autocompletionTextField("compta_num","glpi_contracts","compta_num",$this->fields["compta_num"],25,$this->fields["FK_entities"]);
	
				echo "</td></tr>";
	
	
				echo "<tr class='tab_bg_1'><td>".$LANG["financial"][8].":		</td><td>";
				dropdownInteger("duration",$this->fields["duration"],0,120);
				echo " ".$LANG["financial"][57];
				if ($this->fields["begin_date"]!=''&&$this->fields["begin_date"]!="0000-00-00")
					echo " -> ".getWarrantyExpir($this->fields["begin_date"],$this->fields["duration"]);
				echo "</td>";
	
				echo "<td>".$LANG["financial"][10].":		</td><td>";
				dropdownInteger("notice",$this->fields["notice"],0,120);
				echo " ".$LANG["financial"][57];
				if ($this->fields["begin_date"]!=''&&$this->fields["begin_date"]!="0000-00-00")
					echo " -> ".getWarrantyExpir($this->fields["begin_date"],$this->fields["duration"]-$this->fields["notice"]);
				echo "</td></tr>";
	
				echo "<tr class='tab_bg_1'><td>".$LANG["financial"][69].":		</td><td>";
				dropdownContractPeriodicity("periodicity",$this->fields["periodicity"]);
				echo "</td>";
	
	
				echo "<td>".$LANG["financial"][11].":		</td>";
				echo "<td>";
				dropdownContractPeriodicity("facturation",$this->fields["facturation"]);
				echo "</td></tr>";
	
				echo "<tr class='tab_bg_1'><td>".$LANG["financial"][107].":		</td><td>";
				dropdownContractRenewal("renewal",$this->fields["renewal"]);
				echo "</td>";
	
	
				echo "<td>&nbsp;</td>";
				echo "<td>&nbsp;";
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

			if (haveRight("contract_infocom","w"))
				if (!$ID) {

					echo "<tr>";
					echo "<td class='tab_bg_2' valign='top' colspan='4'>";
					echo "<div class='center'><input type='submit' name='add' value=\"".$LANG["buttons"][8]."\" class='submit'></div>";
					echo "</td>";
					echo "</tr>";

				} else {

					echo "<tr>";
					echo "<td class='tab_bg_2'></td>";
					echo "<td class='tab_bg_2' valign='top'>";
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

				}
			echo "</table></div></form>";

		} else {
			echo "<div class='center'><strong>".$LANG["common"][54]."</strong></div>";
			return false;

		}

		return true;
	}

}

?>
