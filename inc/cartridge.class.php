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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------


if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}


//!  CartridgeType Class
/** CartridgeType Class
  This class is used to manage the various types of cartridges.
  \see Cartridge
  \author Julien Dombre
 */
class CartridgeType extends CommonDBTM {

	/**
	 * Constructor
	 **/
	function __construct () {
		$this->table="glpi_cartridges_type";
		$this->type=CARTRIDGE_TYPE;
		$this->entity_assign=true;
	}

	function cleanDBonPurge($ID) {
		global $DB;
		// Delete cartridges
		$query = "DELETE FROM glpi_cartridges WHERE (FK_glpi_cartridges_type = '$ID')";
		$DB->query($query);
		// Delete all cartridge assoc
		$query2 = "DELETE FROM glpi_cartridges_assoc WHERE (FK_glpi_cartridges_type = '$ID')";
		$result2 = $DB->query($query2);

		$query = "DELETE FROM glpi_infocoms WHERE (FK_device = '$ID' AND device_type='".CARTRIDGE_TYPE."')";
		$result = $DB->query($query);

	}

	function post_getEmpty () {
		global $CFG_GLPI;
		$this->fields["alarm"]=$CFG_GLPI["cartridges_alarm"];
	}

	function defineTabs($ID,$withtemplate){
		global $LANG;

		$ong[1]=$LANG["title"][26];
		if (haveRight("contract","r") || haveRight("infocom","r"))
			$ong[4]=$LANG["Menu"][26];
		if (haveRight("document","r"))
			$ong[5]=$LANG["Menu"][27];
		if (haveRight("link","r"))
			$ong[7]=$LANG["title"][34];
		if (haveRight("notes","r"))
			$ong[10]=$LANG["title"][37];

		return $ong;
	}

	///// SPECIFIC FUNCTIONS

	/**
	 * Count cartridge of the cartridge type
	 *
	 *@return number of cartridges
	 *
	 **/
	function countCartridges() {
		global $DB;
		$query = "SELECT * FROM glpi_cartridges WHERE (FK_glpi_cartridges_type = '".$this->fields["ID"]."')";
		if ($result = $DB->query($query)) {
			$number = $DB->numrows($result);
			return $number;
		} else {
			return false;
		}
	}

	/**
	 * Add a compatible printer type for a cartridge type
	 *
	 * Add the compatible printer $type type for the cartridge type $tID
	 *
	 *@param $tID integer: cartridge type identifier
	 *@param $type integer: printer type identifier
	 *@return nothing ()
	 *
	 **/
	function addCompatibleType($tID,$type){
		global $DB;
		if ($tID>0&&$type>0){

			$query="INSERT INTO glpi_cartridges_assoc (FK_glpi_cartridges_type,FK_glpi_dropdown_model_printers ) VALUES ('$tID','$type');";
			$result = $DB->query($query);
		}
	}

	/**
	 * delete a compatible printer associated to a cartridge
	 *
	 * Delete a compatible printer associated to a cartridge with assoc identifier $ID
	 *
	 *@param $ID integer: glpi_cartridge_assoc identifier.
	 *
	 *@return nothing ()
	 *
	 **/
	function deleteCompatibleType($ID){

		global $DB;
		$query="DELETE FROM glpi_cartridges_assoc WHERE ID= '$ID';";
		$result = $DB->query($query);
	}



	/**
	 * Print the cartridge type form
	 *
	 *
	 * Print general cartridge type form
	 *
	 *@param $target filename : where to go when done.
	 *@param $ID Integer : Id of the cartridge type
	 *@param $withtemplate='' boolean : template or basic item
	 *
	 *
	 *@return Nothing (display)
	 *
	 **/
	function showForm ($target,$ID,$withtemplate='') {
		// Show CartridgeType or blank form

		global $CFG_GLPI,$LANG;

		if (!haveRight("cartridge","r")) return false;


		$use_cache=true;

		if ($ID > 0){
			$this->check($ID,'r');
		} else {
			// Create item 
			$this->check(-1,'w');
			$use_cache=false;
			$this->getEmpty();
		} 


		$this->showTabs($ID, $withtemplate,$_SESSION['glpi_tab']);
		echo "<div class='center' id='tabsbody' ><form method='post' action=\"$target\">\n";
		if (empty($ID)){
			echo "<input type='hidden' name='FK_entities' value='".$_SESSION["glpiactive_entity"]."'>";
		}

		if (!$use_cache||!($CFG_GLPI["cache"]->start($ID."_".$_SESSION['glpilanguage'],"GLPI_".$this->type))) {
			echo "<table class='tab_cadre_fixe' >\n";
			echo "<tr><th colspan='3'>\n";
			if (!$ID) {
				echo $LANG["cartridges"][6];
			} else { 
				echo $LANG["common"][2]." $ID";
			}

			if (isMultiEntitiesMode()){
				echo "&nbsp;(".getDropdownName("glpi_entities",$this->fields["FK_entities"]).")";
			}			

			echo "</th></tr>\n";

			echo "<tr class='tab_bg_1'><td>".$LANG["common"][16].":		</td>\n";
			echo "<td colspan='2'>";
			autocompletionTextField("name","glpi_cartridges_type","name",$this->fields["name"],40,$this->fields["FK_entities"]);
			echo "</td></tr>\n";

			echo "<tr class='tab_bg_1'><td>".$LANG["consumables"][2].":		</td>\n";
			echo "<td colspan='2'>";
			autocompletionTextField("ref","glpi_cartridges_type","ref",$this->fields["ref"],40,$this->fields["FK_entities"]);	
			echo "</td></tr>\n";

			echo "<tr class='tab_bg_1'><td>".$LANG["common"][17].": 	</td><td colspan='2'>\n";
			dropdownValue("glpi_dropdown_cartridge_type","type",$this->fields["type"]);
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

		if (haveRight("cartridge","w"))
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

		echo "</table></form></div>";
		echo "<div id='tabcontent'></div>";
		echo "<script type='text/javascript'>loadDefaultTab();</script>";

		return true;
	}



}

//!  Cartridge Class
/**
  This class is used to manage the cartridges.
  \see CartridgeType
  \author Julien Dombre
 */
class Cartridge extends CommonDBTM {

	/**
	 * Constructor
	 **/
	function __construct () {
		$this->table="glpi_cartridges";
		$this->type=CARTRIDGE_ITEM_TYPE;
		// by the Cartridge Type
		$this->entity_assign=true;
	}


	function cleanDBonPurge($ID) {
		global $DB;
		$query = "DELETE FROM glpi_infocoms WHERE (FK_device = '$ID' AND device_type='".CARTRIDGE_ITEM_TYPE."')";
		$result = $DB->query($query);
	}

	function prepareInputForAdd($input) {
		return array("FK_glpi_cartridges_type"=>$input["tID"],
				"date_in"=>date("Y-m-d"));
	}

	function post_addItem($newID,$input) {
		// Add infocoms if exists for the licence
		$ic=new Infocom();

		if ($ic->getFromDBforDevice(CARTRIDGE_TYPE,$this->fields["FK_glpi_cartridges_type"])){
			unset($ic->fields["ID"]);
			$ic->fields["FK_device"]=$newID;
			$ic->fields["device_type"]=CARTRIDGE_ITEM_TYPE;
			$ic->addToDB();
		}
	}

	function restore($input,$history=1) {

		global $DB;
		$query = "UPDATE glpi_cartridges SET date_out = NULL, date_use = NULL , FK_glpi_printers= 0 WHERE ID='".$input["ID"]."'";
		if ($result = $DB->query($query)) {
			return true;
		} else {
			return false;
		}
	}

	// SPECIFIC FUNCTIONS
	/**
	 * Update count pages value of a cartridge
	 *
	 *@param $ID ID of the cartridge
	 *@param $pages  count pages value
	 *
	 **/
	function updatePages($ID,$pages){
		global $DB;
		$query="UPDATE glpi_cartridges SET pages='$pages' WHERE ID='$ID'";
		$DB->query($query);
	}

	/**
	 * Link a cartridge to a printer.
	 *
	 * Link the first unused cartridge of type $Tid to the printer $pID
	 *
	 *@param $tID : cartridge type identifier
	 *@param $pID : printer identifier
	 *
	 *@return nothing
	 *
	 **/
	function install($pID,$tID) {
		global $DB,$LANG;

		// Get first unused cartridge
		$query = "SELECT ID FROM glpi_cartridges WHERE FK_glpi_cartridges_type = '$tID' AND date_use IS NULL";
		$result = $DB->query($query);
		if ($DB->numrows($result)>0){
			// Mise a jour cartouche en prenant garde aux insertion multiples	
			$query = "UPDATE glpi_cartridges SET date_use = '".date("Y-m-d")."', FK_glpi_printers = '$pID' WHERE ID='".$DB->result($result,0,0)."' AND date_use IS NULL";
			if ($result = $DB->query($query)) {
				return true;
			} else {
				return false;
			}
		} else {
			addMessageAfterRedirect($LANG["cartridges"][34]);
			return false;		
		}
	}

	/**
	 * UnLink a cartridge linked to a printer
	 *
	 * UnLink the cartridge identified by $ID
	 *
	 *@param $ID : cartridge identifier
	 *
	 *@return boolean
	 *
	 **/
	function uninstall($ID) {

		global $DB;
		$query = "UPDATE glpi_cartridges SET date_out = '".date("Y-m-d")."' WHERE ID='$ID'";
		if ($result = $DB->query($query)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Get the ID of entity assigned to the cartdrige
	 * 
	 * @return ID of the entity 
	**/
	function getEntityID () {
		$ci=new CartridgeType();
		$ci->getFromDB($this->fields["FK_glpi_cartridges_type"]);
		return $ci->getEntityID();
	}	

}

?>
