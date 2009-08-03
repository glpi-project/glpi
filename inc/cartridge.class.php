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
  \users_id Julien Dombre
 */
class CartridgeType extends CommonDBTM {

	/**
	 * Constructor
	 **/
	function __construct () {
		$this->table="glpi_cartridgesitems";
		$this->type=CARTRIDGE_TYPE;
		$this->entity_assign=true;
	}

	function cleanDBonPurge($ID) {
		global $DB;
		// Delete cartridges
		$query = "DELETE FROM glpi_cartridges WHERE (cartridgesitems_id = '$ID')";
		$DB->query($query);
		// Delete all cartridge assoc
		$query2 = "DELETE FROM glpi_cartridges_printersmodels WHERE (cartridgesitems_id = '$ID')";
		$result2 = $DB->query($query2);

		$query = "DELETE FROM glpi_infocoms WHERE (items_id = '$ID' AND itemtype='".CARTRIDGE_TYPE."')";
		$result = $DB->query($query);

	}

	function post_getEmpty () {
		global $CFG_GLPI;
		$this->fields["alarm_threshold"]=$CFG_GLPI["default_alarm_threshold"];
	}

	function defineTabs($ID,$withtemplate){
		global $LANG;

		$ong[1]=$LANG['Menu'][21];
		if ($ID>0){
			if (haveRight("contract","r") || haveRight("infocom","r"))
				$ong[4]=$LANG['Menu'][26];
			if (haveRight("document","r"))
				$ong[5]=$LANG['Menu'][27];
			if(empty($withtemplate)){
				if (haveRight("link","r"))
					$ong[7]=$LANG['title'][34];
				if (haveRight("notes","r"))
					$ong[10]=$LANG['title'][37];
			}
		}

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
		$query = "SELECT * FROM glpi_cartridges WHERE cartridgesitems_id = '".$this->fields["ID"]."'";
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
	 *@param $cartridgesitems_id integer: cartridge type identifier
	 *@param printersmodels_id integer: printer type identifier
	 *@return nothing ()
	 *
	 **/
	function addCompatibleType($tID,$printersmodels_id){
		global $DB;
		if ($tID>0&&$type>0){

			$query="INSERT INTO glpi_cartridges_printersmodels (cartridgesitems_id,printersmodels_id ) VALUES ('$cartridgesitems_id','$printersmodels_id');";
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
		$query="DELETE FROM glpi_cartridges_printersmodels WHERE ID= '$ID';";
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

		if ($ID > 0){
			$this->check($ID,'r');
		} else {
			// Create item 
			$this->check(-1,'w');
			$this->getEmpty();
		} 


      $this->showTabs($ID, $withtemplate,$_SESSION['glpi_tab']);
      $this->showFormHeader($target,$ID,$withtemplate);

      echo "<tr class='tab_bg_1'><td>".$LANG['common'][16].":		</td>\n";
      echo "<td>";
      autocompletionTextField("name","glpi_cartridgesitems","name",$this->fields["name"],40,$this->fields["entities_id"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'><td>".$LANG['consumables'][2].":		</td>\n";
      echo "<td>";
      autocompletionTextField("ref","glpi_cartridgesitems","ref",$this->fields["ref"],40,$this->fields["entities_id"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'><td>".$LANG['common'][17].": 	</td><td>\n";
      dropdownValue("glpi_cartridgesitemstypes","cartridgesitemstypes_id",$this->fields["cartridgesitemstypes_id"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'><td>".$LANG['common'][5].": 	</td><td>\n";
      dropdownValue("glpi_manufacturers","manufacturers_id",$this->fields["manufacturers_id"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'><td>".$LANG['common'][10].": 	</td><td>\n";
      dropdownUsersID("users_id_tech", $this->fields["users_id_tech"],"interface",1,$this->fields["entities_id"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'><td>".$LANG['consumables'][36].": 	</td><td>\n";
      dropdownValue("glpi_locations","locations_id",$this->fields["locations_id"],1,$this->fields["entities_id"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'><td>".$LANG['consumables'][38].":</td><td>";
      dropdownInteger('alarm_threshold',$this->fields["alarm_threshold"],-1,100);
      echo "</td></tr>\n";


      echo "<tr class='tab_bg_1'><td valign='top'>\n";
      echo $LANG['common'][25].":	</td>";
      echo "<td><textarea cols='60' rows='4' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>\n";

      $this->showFormButtons($ID,$withtemplate);

		echo "<div id='tabcontent'></div>";
		echo "<script type='text/javascript'>loadDefaultTab();</script>";

		return true;
	}



}

//!  Cartridge Class
/**
  This class is used to manage the cartridges.
  @see CartridgeType
  @author Julien Dombre
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
		$query = "DELETE FROM glpi_infocoms WHERE (items_id = '$ID' AND itemtype='".CARTRIDGE_ITEM_TYPE."')";
		$result = $DB->query($query);
	}

	function prepareInputForAdd($input) {
		return array("cartridgesitems_id"=>$input["tID"],
				"date_in"=>date("Y-m-d"));
	}

	function post_addItem($newID,$input) {
		// Add infocoms if exists for the licence
		$ic=new Infocom();

		if ($ic->getFromDBforDevice(CARTRIDGE_TYPE,$this->fields["cartridgesitems_id"])){
			unset($ic->fields["ID"]);
			$ic->fields["items_id"]=$newID;
			$ic->fields["itemtype"]=CARTRIDGE_ITEM_TYPE;
			if (empty($ic->fields['use_date'])){
				unset($ic->fields['use_date']);
			}
			if (empty($ic->fields['buy_date'])){
				unset($ic->fields['buy_date']);
			}
			$ic->addToDB();
		}
	}

	function restore($input,$history=1) {

		global $DB;
		$query = "UPDATE glpi_cartridges 
			SET date_out = NULL, date_use = NULL, printers_id= 0 
			WHERE ID='".$input["ID"]."'";
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
		$query = "SELECT ID 
			FROM glpi_cartridges 
			WHERE cartridgesitems_id = '$tID' AND date_use IS NULL";
		$result = $DB->query($query);
		if ($DB->numrows($result)>0){
			// Mise a jour cartouche en prenant garde aux insertion multiples	
			$query = "UPDATE glpi_cartridges 
				SET date_use = '".date("Y-m-d")."', printers_id = '$pID' 
				WHERE ID='".$DB->result($result,0,0)."' AND date_use IS NULL";
			if ($result = $DB->query($query)) {
				return true;
			} else {
				return false;
			}
		} else {
			addMessageAfterRedirect($LANG['cartridges'][34],false,ERROR);
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
		$query = "UPDATE glpi_cartridges 
			SET date_out = '".date("Y-m-d")."' 
			WHERE ID='$ID'";
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
		$ci->getFromDB($this->fields["cartridgesitems_id"]);
		return $ci->getEntityID();
	}	

}

?>
