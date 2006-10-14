<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.

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
		global $db;
		// Delete cartridconsumablesges
		$query = "DELETE FROM glpi_consumables WHERE (FK_glpi_consumables_type = '$ID')";
		$db->query($query);
	}

	function post_getEmpty () {
		global $cfg_glpi;
		$this->fields["alarm"]=$cfg_glpi["cartridges_alarm"];
	}

	function defineOnglets($withtemplate){
		global $lang;
		$ong[1]=$lang["title"][26];
		if (haveRight("contract_infocom","r"))	
			$ong[4]=$lang["Menu"][26];
		if (haveRight("document","r"))	
			$ong[5]=$lang["title"][25];
		if (haveRight("link","r"))	
			$ong[7]=$lang["title"][34];
		if (haveRight("notes","r"))
			$ong[10]=$lang["title"][37];
		return $ong;
	}


	/**
	 * Print a good title for Consumable pages
	 *
	 *
	 *
	 *
	 *@return nothing (diplays)
	 *
	 **/
	function title(){

		global  $lang,$cfg_glpi;

		echo "<div align='center'><table border='0'><tr><td>";
		echo "<a href='consumable.php'><img src=\"".$cfg_glpi["root_doc"]."/pics/consommables.png\" alt='".$lang["consumables"][6]."' title='".$lang["consumables"][6]."'></a></td>";
		if (haveRight("consumable","w")){
			echo "<td><a  class='icon_consol' href=\"consumable.form.php\"><b>".$lang["consumables"][6]."</b></a></td>";
		} else echo "<td><span class='icon_sous_nav'><b>".$lang["Menu"][32]."</b></span></td>";
		echo "<td><a class='icon_consol' href='consumable.php?synthese=yes'>".$lang["state"][11]."</a></td>";
		echo "</tr></table></div>";
	}


	/**
	 * Print the consumable type form
	 *
	 *
	 * Print g��al consumable type form
	 *
	 *@param $target filename : where to go when done.
	 *@param $ID Integer : Id of the consumable type
	 *
	 *
	 *@return Nothing (display)
	 *
	 **/
	function showForm ($target,$ID) {
		// Show ConsumableType or blank form

		global $cfg_glpi,$lang;

		if (!haveRight("consumable","r")) return false;


		$ct_spotted=false;

		if (!$ID) {

			if($this->getEmpty()) $ct_spotted = true;
		} else {
			if($this->getFromDB($ID)) $ct_spotted = true;
		}

		if ($ct_spotted){

			echo "<form method='post' action=\"$target\"><div align='center'>\n";
			echo "<table class='tab_cadre_fixe'>\n";
			echo "<tr><th colspan='3'><b>\n";
			if (!$ID) {
				echo $lang["consumables"][6].":";
			} else {
				echo $lang["consumables"][12]." ID $ID:";
			}		
			echo "</b></th></tr>\n";

			echo "<tr class='tab_bg_1'><td>".$lang["common"][16].":		</td>\n";
			echo "<td colspan='2'>";
			autocompletionTextField("name","glpi_consumables_type","name",$this->fields["name"],25);	
			echo "</td></tr>\n";

			echo "<tr class='tab_bg_1'><td>".$lang["consumables"][2].":		</td>\n";
			echo "<td colspan='2'>";
			autocompletionTextField("ref","glpi_consumables_type","ref",$this->fields["ref"],25);	
			echo "</td></tr>\n";

			echo "<tr class='tab_bg_1'><td>".$lang["common"][17].": 	</td><td colspan='2'>\n";
			dropdownValue("glpi_dropdown_consumable_type","type",$this->fields["type"]);
			echo "</td></tr>\n";

			echo "<tr class='tab_bg_1'><td>".$lang["common"][5].": 	</td><td colspan='2'>\n";
			dropdownValue("glpi_enterprises","FK_glpi_enterprise",$this->fields["FK_glpi_enterprise"]);
			echo "</td></tr>\n";

			echo "<tr class='tab_bg_1'><td>".$lang["common"][10].": 	</td><td colspan='2'>\n";
			dropdownUsersID("tech_num", $this->fields["tech_num"],"interface");
			echo "</td></tr>\n";

			echo "<tr class='tab_bg_1'><td>".$lang["consumables"][36].": 	</td><td colspan='2'>\n";
			dropdownValue("glpi_dropdown_locations","location",$this->fields["location"]);
			echo "</td></tr>\n";

			echo "<tr class='tab_bg_1'><td>".$lang["consumables"][38].":</td><td colspan='2'><select name='alarm'>\n";
			for ($i=-1;$i<=100;$i++)
				echo "<option value='$i' ".($i==$this->fields["alarm"]?" selected ":"").">$i</option>";
			echo "</select></td></tr>\n";


			echo "<tr class='tab_bg_1'><td valign='top'>\n";
			echo $lang["common"][25].":	</td>";
			echo "<td align='center' colspan='2'><textarea cols='35' rows='4' name='comments' >".$this->fields["comments"]."</textarea>";
			echo "</td></tr>\n";

			if (haveRight("consumable","w"))
				if (!$ID) {

					echo "<tr>\n";
					echo "<td class='tab_bg_2' valign='top' colspan='3'>\n";
					echo "<div align='center'><input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'></div>";
					echo "</td>";
					echo "</tr>\n";


				} else {

					echo "<tr>\n";
					echo "<td class='tab_bg_2'></td>";
					echo "<td class='tab_bg_2' valign='top'>";
					echo "<input type='hidden' name='ID' value=\"$ID\">\n";
					echo "<div align='center'><input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'></div>";
					echo "</td>";
					echo "<td class='tab_bg_2' valign='top'>\n";
					echo "<div align='center'>";
					if ($this->fields["deleted"]=='N')
						echo "<input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'>";
					else {
						echo "<input type='submit' name='restore' value=\"".$lang["buttons"][21]."\" class='submit'>";

						echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' name='purge' value=\"".$lang["buttons"][22]."\" class='submit'>\n";
					}
					echo "</div>";
					echo "</td>";
					echo "</tr>\n";


				}
			echo "</table></div></form>";

		} else {

			echo "<div align='center'><b>".$lang["consumables"][7]."</b></div>";
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
		global $db;
		$query = "DELETE FROM glpi_infocoms WHERE (FK_device = '$ID' AND device_type='".CONSUMABLE_ITEM_TYPE."')";
		$result = $db->query($query);
	}

	function prepareInputForAdd($input) {
		return array("FK_glpi_consumables_type"=>$input["tID"],
				"date_in"=>date("Y-m-d"));
	}

	function postAddItem($newID,$input) {
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
		global $db;
		$query = "UPDATE glpi_consumables SET date_out = NULL WHERE ID='".$input["ID"]."'";

		if ($result = $db->query($query)) {
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

		global $db;
		$query = "UPDATE glpi_consumables SET date_out = '".date("Y-m-d")."', id_user='$id_user' WHERE ID='$ID'";

		if ($result = $db->query($query)) {
			return true;
		} else {
			return false;
		}
	}


}

?>
