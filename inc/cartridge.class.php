<?php
/*
* @version $Id$
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.
 
 http://indepnet.net/   http://glpi-project.org
 ----------------------------------------------------------------------

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
 ------------------------------------------------------------------------
*/
 
// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

 


//!  CartridgeType Class
/**
  This class is used to manage the various types of cartridges.
	\see Cartridge
	\author Julien Dombre
*/
class CartridgeType extends CommonDBTM {

	function CartridgeType () {
		$this->table="glpi_cartridges_type";
		$this->type=CARTRIDGE_TYPE;
	}

	function cleanDBonPurge($ID) {
		global $db;
		// Delete cartridges
		$query = "DELETE FROM glpi_cartridges WHERE (FK_glpi_cartridges_type = '$ID')";
		$db->query($query);
		// Delete all cartridge assoc
		$query2 = "DELETE FROM glpi_cartridges_assoc WHERE (FK_glpi_cartridges_type = '$ID')";
		$result2 = $db->query($query2);

		$query = "DELETE FROM glpi_infocoms WHERE (FK_device = '$ID' AND device_type='".CARTRIDGE_TYPE."')";
		$result = $db->query($query);

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

	///// SPECIFIC FUNCTIONS

	function countCartridges() {
		global $db;
		$query = "SELECT * FROM glpi_cartridges WHERE (FK_glpi_cartridges_type = '".$this->fields["ID"]."')";
		if ($result = $db->query($query)) {
			$number = $db->numrows($result);
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
		global $db;
		if ($tID>0&&$type>0){
	
			$query="INSERT INTO glpi_cartridges_assoc (FK_glpi_cartridges_type,FK_glpi_dropdown_model_printers ) VALUES ('$tID','$type');";
			$result = $db->query($query);
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
	
	global $db;
	$query="DELETE FROM glpi_cartridges_assoc WHERE ID= '$ID';";
	$result = $db->query($query);
	}

	
	/**
	* Print a good title for Cartridge pages
	*
	*
	*
	*
	*@return nothing (diplays)
	*
	**/
	function title(){
	
		global  $lang,$HTMLRel;
		
		echo "<div align='center'><table border='0'><tr><td>";
		echo "<img src=\"".$HTMLRel."pics/cartouches.png\" alt='".$lang["cartridges"][6]."' title='".$lang["cartridges"][6]."'></td>";
		if (haveRight("cartridge","w")){
			echo "<td><a  class='icon_consol' href=\"cartridge.form.php\"><b>".$lang["cartridges"][6]."</b></a></td>";
		} else echo "<td><span class='icon_sous_nav'><b>".$lang["Menu"][21]."</b></span></td>";
		echo "</tr></table></div>";
	}
	
	
	/**
	* Print the cartridge type form
	*
	*
	* Print general cartridge type form
	*
	*@param $target filename : where to go when done.
	*@param $ID Integer : Id of the cartridge type
	*
	*
	*@return Nothing (display)
	*
	**/
	function showForm ($target,$ID) {
		// Show CartridgeType or blank form
		
		global $cfg_glpi,$lang;
	
		if (!haveRight("cartridge","r")) return false;
	
		
		$ct_spotted = false;
		
		if (empty($ID)) {
			
			if($this->getEmpty()) $ct_spotted = true;
		} else {
			if($this->getfromDB($ID)) $ct_spotted = true;
		}		
		
		if ($ct_spotted){
		
		echo "<form method='post' action=\"$target\"><div align='center'>\n";
		
		echo "<table class='tab_cadre_fixe'>\n";
		echo "<tr><th colspan='3'><b>\n";
		if (!$ID) 
			echo $lang["cartridges"][6].":";
		else echo $lang["cartridges"][12]." ID $ID:";
		
		echo "</b></th></tr>\n";
	
		echo "<tr class='tab_bg_1'><td>".$lang["common"][16].":		</td>\n";
		echo "<td colspan='2'>";
		autocompletionTextField("name","glpi_cartridges_type","name",$this->fields["name"],25);
		echo "</td></tr>\n";
	
		echo "<tr class='tab_bg_1'><td>".$lang["cartridges"][2].":		</td>\n";
		echo "<td colspan='2'>";
		autocompletionTextField("ref","glpi_cartridges_type","ref",$this->fields["ref"],25);	
		echo "</td></tr>\n";
	
		echo "<tr class='tab_bg_1'><td>".$lang["common"][17].": 	</td><td colspan='2'>\n";
			dropdownValue("glpi_dropdown_cartridge_type","type",$this->fields["type"]);
		echo "</td></tr>\n";
	
		echo "<tr class='tab_bg_1'><td>".$lang["common"][5].": 	</td><td colspan='2'>\n";
			dropdownValue("glpi_enterprises","FK_glpi_enterprise",$this->fields["FK_glpi_enterprise"]);
		echo "</td></tr>\n";
	
		echo "<tr class='tab_bg_1'><td>".$lang["common"][10].": 	</td><td colspan='2'>\n";
			dropdownUsersID("tech_num", $this->fields["tech_num"],"interface");
		echo "</td></tr>\n";
	
		echo "<tr class='tab_bg_1'><td>".$lang["cartridges"][36].": 	</td><td colspan='2'>\n";
			dropdownValue("glpi_dropdown_locations","location",$this->fields["location"]);
		echo "</td></tr>\n";
	
		echo "<tr class='tab_bg_1'><td>".$lang["cartridges"][38].":</td><td colspan='2'><select name='alarm'>\n";
		for ($i=-1;$i<=100;$i++)
			echo "<option value='$i' ".($i==$this->fields["alarm"]?" selected ":"").">$i</option>";
		echo "</select></td></tr>\n";
		
		
		echo "<tr class='tab_bg_1'><td valign='top'>\n";
		echo $lang["common"][25].":	</td>";
		echo "<td align='center' colspan='2'><textarea cols='35' rows='4' name='comments' >".$this->fields["comments"]."</textarea>";
		echo "</td></tr>\n";
		
	
		if (haveRight("cartridge","w"))
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
	
		}
		else {
		echo "<div align='center'><b>".$lang["cartridges"][7]."</b></div>";
		return false;
		}
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

	function Cartridge () {
		$this->table="glpi_cartridges";
		$this->type=CARTRIDGE_ITEM_TYPE;
	}
	

	function cleanDBonPurge($ID) {
		global $db;
		$query = "DELETE FROM glpi_infocoms WHERE (FK_device = '$ID' AND device_type='".CARTRIDGE_ITEM_TYPE."')";
		$result = $db->query($query);
	}

	function prepareInputForAdd($input) {
		return array("FK_glpi_cartridges_type"=>$input["tID"],
				"date_in"=>date("Y-m-d"));
	}

	function postAddItem($newID,$input) {
		// Add infocoms if exists for the licence
		$ic=new Infocom();
	
		if ($ic->getFromDBforDevice(CARTRIDGE_TYPE,$this->fields["FK_glpi_cartridges_type"])){
			unset($ic->fields["ID"]);
			$ic->fields["FK_device"]=$newID;
			$ic->fields["device_type"]=CARTRIDGE_ITEM_TYPE;
			$ic->addToDB();
		}
	}

	function restore($input) {

		global $db;
		$query = "UPDATE glpi_cartridges SET date_out = NULL, date_use = NULL , FK_glpi_printers= NULL WHERE ID='".$input["ID"]."'";
		if ($result = $db->query($query)) {
			return true;
		} else {
			return false;
		}
	}

	// SPECIFIC FUNCTIONS
	function updatePages($ID,$pages){
		global $db;
		$query="UPDATE glpi_cartridges SET pages='$pages' WHERE ID='$ID'";
		$db->query($query);
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
		global $db,$lang;
	
		// Get first unused cartridge
		$query = "SELECT ID FROM glpi_cartridges WHERE FK_glpi_cartridges_type = '$tID' AND date_use IS NULL";
		$result = $db->query($query);
		if ($db->numrows($result)>0){
		// Mise a jour cartouche en prenant garde aux insertion multiples	
		$query = "UPDATE glpi_cartridges SET date_use = '".date("Y-m-d")."', FK_glpi_printers = '$pID' WHERE ID='".$db->result($result,0,0)."' AND date_use IS NULL";
		if ($result = $db->query($query)) {
			return true;
		} else {
			return false;
		}
		} else {
			 $_SESSION["MESSAGE_AFTER_REDIRECT"]=$lang["cartridges"][34];
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

		global $db;
		$query = "UPDATE glpi_cartridges SET date_out = '".date("Y-m-d")."' WHERE ID='$ID'";
		if ($result = $db->query($query)) {
			return true;
		} else {
			return false;
		}
	}


}

?>