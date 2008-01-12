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



//!  ConsumableType Class
/**
  This class is used to manage the various types of consumables.
  \see Consumable
  \author Julien Dombre
 */
class ConsumableType extends CommonDBTM {

	function ConsumableType () {
		$this->table="glpi_consumables_type";
		$this->type=CONSUMABLE_TYPE;
	}

	function cleanDBonPurge($ID) {
		global $DB;
		// Delete cartridconsumablesges
		$query = "DELETE FROM glpi_consumables WHERE (FK_glpi_consumables_type = '$ID')";
		$DB->query($query);
	}

	function post_getEmpty () {
		global $CFG_GLPI;
		$this->fields["alarm"]=$CFG_GLPI["cartridges_alarm"];
	}

	function defineOnglets($withtemplate){
		global $LANG;
		$ong[1]=$LANG["title"][26];
		if (haveRight("contract_infocom","r"))	
			$ong[4]=$LANG["Menu"][26];
		if (haveRight("document","r"))	
			$ong[5]=$LANG["Menu"][27];
		if (haveRight("link","r"))	
			$ong[7]=$LANG["title"][34];
		if (haveRight("notes","r"))
			$ong[10]=$LANG["title"][37];
		return $ong;
	}

	/**
	 * Print the consumable type form
	 *
	 *
	 * Print g��al consumable type form
	 *
	 *@param $target filename : where to go when done.
	 *@param $ID Integer : Id of the consumable type
	 *@param $withtemplate='' boolean : template or basic item
	 *
	 *
	 *@return Nothing (display)
	 *
	 **/
	function showForm ($target,$ID,$withtemplate='') {
		// Show ConsumableType or blank form

		global $CFG_GLPI,$LANG;

		if (!haveRight("consumable","r")) return false;


		$ct_spotted=false;
		$use_cache=true;
		if (!$ID) {
			$use_cache=false;
			if($this->getEmpty()) $ct_spotted = true;
		} else {
			if($this->getFromDB($ID)&&haveAccessToEntity($this->fields["FK_entities"])) $ct_spotted = true;
		}

		if ($ct_spotted){

			$this->showOnglets($ID, $withtemplate,$_SESSION['glpi_onglet']);

			echo "<form method='post' action=\"$target\"><div class='center'>\n";
			if (empty($ID)){
				echo "<input type='hidden' name='FK_entities' value='".$_SESSION["glpiactive_entity"]."'>";
			}

			if (!$use_cache||!($CFG_GLPI["cache"]->start($ID."_".$_SESSION["glpilanguage"],"GLPI_".$this->type))) {

				echo "<table class='tab_cadre_fixe'>\n";
				echo "<tr><th colspan='3'>\n";
				if (!$ID) {
					echo $LANG["consumables"][6];
				} else {
					echo $LANG["common"][2]." $ID";
				}		
				if (isMultiEntitiesMode()){
					echo "&nbsp;(".getDropdownName("glpi_entities",$this->fields["FK_entities"]).")";
				}

				echo "</th></tr>\n";
	
				echo "<tr class='tab_bg_1'><td>".$LANG["common"][16].":		</td>\n";
				echo "<td colspan='2'>";
				autocompletionTextField("name","glpi_consumables_type","name",$this->fields["name"],25,$this->fields["FK_entities"]);	
				echo "</td></tr>\n";
	
				echo "<tr class='tab_bg_1'><td>".$LANG["consumables"][2].":		</td>\n";
				echo "<td colspan='2'>";
				autocompletionTextField("ref","glpi_consumables_type","ref",$this->fields["ref"],25,$this->fields["FK_entities"]);	
				echo "</td></tr>\n";
	
				echo "<tr class='tab_bg_1'><td>".$LANG["common"][17].": 	</td><td colspan='2'>\n";
				dropdownValue("glpi_dropdown_consumable_type","type",$this->fields["type"]);
				echo "</td></tr>\n";
	
				echo "<tr class='tab_bg_1'><td>".$LANG["common"][5].": 	</td><td colspan='2'>\n";
				dropdownValue("glpi_dropdown_manufacturer","FK_glpi_enterprise",$this->fields["FK_glpi_enterprise"]);
				echo "</td></tr>\n";
	
				echo "<tr class='tab_bg_1'><td>".$LANG["common"][10].": 	</td><td colspan='2'>\n";
				dropdownUsersID("tech_num", $this->fields["tech_num"],"interface",1,$this->fields["FK_entities"]);
				echo "</td></tr>\n";
	
				echo "<tr class='tab_bg_1'><td>".$LANG["consumables"][36].": 	</td><td colspan='2'>\n";
				dropdownValue("glpi_dropdown_locations","location",$this->fields["location"],1,$this->fields["FK_entities"]);
				echo "</td></tr>\n";
	
				echo "<tr class='tab_bg_1'><td>".$LANG["consumables"][38].":</td><td colspan='2'>";
				dropdownInteger('alarm',$this->fields["alarm"],-1,100);
				echo "</td></tr>\n";
	
	
				echo "<tr class='tab_bg_1'><td valign='top'>\n";
				echo $LANG["common"][25].":	</td>";
				echo "<td align='center' colspan='2'><textarea cols='35' rows='4' name='comments' >".$this->fields["comments"]."</textarea>";
				echo "</td></tr>\n";
				if ($use_cache){
					$CFG_GLPI["cache"]->end();
				}
			}

			if (haveRight("consumable","w"))
				if (!$ID) {

					echo "<tr>\n";
					echo "<td class='tab_bg_2' valign='top' colspan='3'>\n";
					echo "<div class='center'><input type='submit' name='add' value=\"".$LANG["buttons"][8]."\" class='submit'></div>";
					echo "</td>";
					echo "</tr>\n";


				} else {

					echo "<tr>\n";
					echo "<td class='tab_bg_2'></td>";
					echo "<td class='tab_bg_2' valign='top'>";
					echo "<input type='hidden' name='ID' value=\"$ID\">\n";
					echo "<div class='center'><input type='submit' name='update' value=\"".$LANG["buttons"][7]."\" class='submit'></div>";
					echo "</td>";
					echo "<td class='tab_bg_2' valign='top'>\n";
					echo "<div class='center'>";
					if (!$this->fields["deleted"])
						echo "<input type='submit' name='delete' value=\"".$LANG["buttons"][6]."\" class='submit'>";
					else {
						echo "<input type='submit' name='restore' value=\"".$LANG["buttons"][21]."\" class='submit'>";

						echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' name='purge' value=\"".$LANG["buttons"][22]."\" class='submit'>\n";
					}
					echo "</div>";
					echo "</td>";
					echo "</tr>\n";


				}
			echo "</table></div></form>";

		} else {

			echo "<div class='center'><strong>".$LANG["common"][54]."</strong></div>";
			return false;
		}
		return true;
	}

}

//!  Consumable Class
/**
  This class is used to manage the consumables.
  \see ConsumableType
  \author Julien Dombre
 */
class Consumable extends CommonDBTM {

	function Consumable () {
		$this->table="glpi_consumables";
		$this->type=CONSUMABLE_ITEM_TYPE;
	}


	function cleanDBonPurge($ID) {
		global $DB;
		$query = "DELETE FROM glpi_infocoms WHERE (FK_device = '$ID' AND device_type='".CONSUMABLE_ITEM_TYPE."')";
		$result = $DB->query($query);
	}

	function prepareInputForAdd($input) {
		return array("FK_glpi_consumables_type"=>$input["tID"],
				"date_in"=>date("Y-m-d"));
	}

	function post_addItem($newID,$input) {
		// Add infocoms if exists for the licence
		$ic=new Infocom();

		if ($ic->getFromDBforDevice(CONSUMABLE_TYPE,$this->fields["FK_glpi_consumables_type"])){
			unset($ic->fields["ID"]);
			$ic->fields["FK_device"]=$newID;
			$ic->fields["device_type"]=CONSUMABLE_ITEM_TYPE;
			$ic->addToDB();
		}
	}

	function restore($input){
		global $DB;
		$query = "UPDATE glpi_consumables SET date_out = NULL WHERE ID='".$input["ID"]."'";

		if ($result = $DB->query($query)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * UnLink a consumable linked to a printer
	 *
	 * UnLink the consumable identified by $ID
	 *
	 *@param $ID : consumable identifier
	 *@param $id_user : ID of the user giving the consumable
	 *
	 *@return boolean
	 *
	 **/
	function out($ID,$id_user=0) {

		global $DB;
		$query = "UPDATE glpi_consumables SET date_out = '".date("Y-m-d")."', id_user='$id_user' WHERE ID='$ID'";

		if ($result = $DB->query($query)) {
			return true;
		} else {
			return false;
		}
	}


}

?>
