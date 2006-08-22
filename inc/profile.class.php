<?php
/*
 * @version $Id: HEADER 3795 2006-08-22 03:57:36Z moyo $
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

 

// CLASSES contact
class Profile extends CommonDBTM{

	function Profile () {
		$this->table="glpi_profiles";
		$this->type=-1;
	}

	function post_updateItem($input,$updates,$history=1) {
		global $db;
		
		if (isset($input["is_default"])&&$input["is_default"]==1){
			$query="UPDATE glpi_profiles SET `is_default`='0' WHERE ID <> '".$input['ID']."'";
			$db->query($query);
		}
	}
	function cleanDBonPurge($ID) {

		global $db,$cfg_glpi,$LINK_ID_TABLE;

		$query = "DELETE FROM glpi_users_profiles WHERE (FK_profiles = '$ID')";
		$db->query($query);

	}

	function prepareInputForUpdate($input){
		if (isset($input["helpdesk_hardware_type"])){
			$types=$input["helpdesk_hardware_type"];
			unset($input["helpdesk_hardware_type"]);
			$input["helpdesk_hardware_type"]=0;
			foreach ($types as $val)
				$input["helpdesk_hardware_type"]+=pow(2,$val);
		}
		return $input;
	}
					
	function prepareInputForAdd($input){
		if (isset($input["helpdesk_hardware_type"])){
			$types=$input["helpdesk_hardware_type"];
			unset($input["helpdesk_hardware_type"]);
			$input["helpdesk_hardware_type"]=0;
			foreach ($types as $val)
				$input["helpdesk_hardware_type"]+=pow(2,$val);
		}
		return $input;
	}

	function updateForUser($ID,$prof){
		global $db;
		// Get user profile
		$query = "SELECT glpi_users_profiles.FK_profiles, glpi_users_profiles.ID FROM glpi_users_profiles INNER JOIN glpi_profiles ON (glpi_users_profiles.FK_profiles = glpi_profiles.ID) WHERE (glpi_users_profiles.FK_users = '$ID')";

		if ($result = $db->query($query)) {
			// Profile found
			if ($db->numrows($result)){
				$data=$db->fetch_array($result);
				if ($data["FK_profiles"]!=$prof){
					$query="UPDATE glpi_users_profiles SET FK_profiles='$prof' WHERE ID='".$data["ID"]."';";
					$db->query($query);
				}
			} else { // Profile not found
					
					$query="INSERT INTO glpi_users_profiles (FK_users, FK_profiles) VALUES ('$ID','$prof');";
					$db->query($query);
			}
		}

	}

	function getFromDBForUser($ID){

		// Make new database object and fill variables
		global $db;
		$ID_profile=0;
		// Get user profile
		$query = "SELECT FK_profiles FROM glpi_users_profiles WHERE (FK_users = '$ID')";
		
		if ($result = $db->query($query)) {
			if ($db->numrows($result)){
				$ID_profile = $db->result($result,0,0);
			}
				
			if (!$ID_profile||!$this->getFromDB($ID_profile)) {
				$ID_profile=0;
				// Get default profile
				$query = "SELECT ID FROM glpi_profiles WHERE (`is_default` = '1')";
				$result = $db->query($query);
				if ($db->numrows($result)){
					$ID_profile = $db->result($result,0,0);
					$this->updateForUser($ID,$ID_profile);
				} else {
					// Get first helpdesk profile
					$query = "SELECT ID FROM glpi_profiles WHERE (interface = 'helpdesk')";
					$result = $db->query($query);
					if ($db->numrows($result)){
						$ID_profile = $db->result($result,0,0);
						$this->updateForUser($ID,$ID_profile);
					}
				}
			}
		}
		if ($ID_profile){
			$this->getFromDB($ID_profile);
			return $ID_profile;
		} else return false;
	}
	// Unset unused rights for helpdesk
	function cleanProfile(){
		$helpdesk=array("name","interface","faq","reservation_helpdesk","create_ticket","comment_ticket","observe_ticket","password_update","helpdesk_hardware","helpdesk_hardware_type","show_group_ticket");
		if ($this->fields["interface"]=="helpdesk"){
			foreach($this->fields as $key=>$val){
				if (!in_array($key,$helpdesk))
					unset($this->fields[$key]);
			}
		}
	}

	/**
	* Print a good title for profiles pages
	*
	*
	*
	*
	*@return nothing (diplays)
	*
	**/
	function title(){
		//titre
		
		global  $lang,$HTMLRel;
	
		echo "<div align='center'><table border='0'><tr><td>";
		echo "<img src=\"".$HTMLRel."pics/profils.png\" alt='".$lang["Menu"][35]."' title='".$lang["Menu"][35]."'></td><td><span class='icon_sous_nav'><b>".$lang["Menu"][35]."</b></span>";
		echo "</td>";
		if (haveRight("profile","w")){
			echo "<td><a class='icon_consol' href='".$HTMLRel."front/profile.php?add=new'>".$lang["profiles"][0]."</a></td>";
		}
		echo "</tr></table></div>";
	}
	
	
	function showForm($target,$ID){
		global $lang,$cfg_glpi;
	
		if (!haveRight("profile","r")) return false;
	
		$onfocus="";
		if ($ID){
			$this->getFromDB($ID);
		} else {
			$this->getEmpty();
			$onfocus="onfocus=\"this.value=''\"";
		}
	
		if (empty($this->fields["interface"])) $this->fields["interface"]="helpdesk";
		if (empty($this->fields["name"])) $this->fields["name"]=$lang["common"][0];
	
	
		echo "<form name='form' method='post' action=\"$target\">";
		echo "<div align='center'>";
		echo "<table class='tab_cadre'><tr>";
		echo "<th>".$lang["common"][16].":</th>";
		echo "<th><input type='text' name='name' value=\"".$this->fields["name"]."\" $onfocus></th>";
		echo "<th>".$lang["profiles"][2].":</th>";
		echo "<th><select name='interface' id='profile_interface'>";
		echo "<option value='helpdesk' ".($this->fields["interface"]!="helpdesk"?"selected":"").">".$lang["Menu"][31]."</option>";
		echo "<option value='central' ".($this->fields["interface"]!="central"?"selected":"").">".$lang["title"][0]."</option>";
		echo "</select></th>";
		echo "</tr></table>";
		echo "</div>";
	
		echo "<script type='text/javascript' >\n";
		echo "   new Form.Element.Observer('profile_interface', 1, \n";
		echo "      function(element, value) {\n";
		echo "      	new Ajax.Updater('profile_form','".$cfg_glpi["root_doc"]."/ajax/profiles.php',{asynchronous:true, evalScripts:true, \n";
		echo "           method:'post', parameters:'interface=' + value+'&ID=$ID'\n";
		echo "})});\n";
		echo "document.getElementById('profile_interface').value='".$this->fields["interface"]."';";
		echo "</script>\n";
		echo "<br>";
	
		echo "<div align='center' id='profile_form'>";
		echo "</div>";
	
		echo "</form>";
	
	}
	
	
	function showHelpdeskForm($ID){
		global $lang;
	
		if (!haveRight("profile","r")) return false;
		$canedit=haveRight("profile","w");
		if ($ID){
			$this->getFromDB($ID);
		} else {
			$this->getEmpty();
		}
	
		echo "<table class='tab_cadre'><tr>";
		echo "<th colspan='4'>".$lang["profiles"][3].":&nbsp;&nbsp;".$lang["profiles"][13].":";
		dropdownYesNoInt("is_default",$this->fields["is_default"]);
		echo "</th></tr>";

		echo "<tr class='tab_bg_1'><td colspan='6' align='center'><strong>".$lang["profiles"][25]."</strong></td></tr>";

		echo "<tr class='tab_bg_2'>";
		echo "<td>".$lang["profiles"][24].":</td><td>";
		dropdownYesNoInt("password_update",$this->fields["password_update"]);
		echo "</td>";
		echo "<td colspan='2'>&nbsp;";
		echo "</td>";
		echo "</tr>";


		echo "<tr class='tab_bg_1'><td colspan='4' align='center'><strong>".$lang["title"][24]."</strong></td></tr>";
	
		echo "<tr class='tab_bg_2'>";
		echo "<td>".$lang["profiles"][5].":</td><td>";
		dropdownYesNoInt("create_ticket",$this->fields["create_ticket"]);
		echo "</td>";
		echo "<td>".$lang["profiles"][6].":</td><td>";
		dropdownYesNoInt("comment_ticket",$this->fields["comment_ticket"]);
		echo "</td></tr>";
	
		echo "<tr class='tab_bg_2'>";
		echo "<td>".$lang["profiles"][9].":</td><td>";
		dropdownYesNoInt("observe_ticket",$this->fields["observe_ticket"]);
		echo "</td>";
		echo "<td>".$lang["profiles"][26].":</td><td>";
		dropdownYesNoInt("show_group_ticket",$this->fields["show_group_ticket"]);
		echo "</td>";
		echo "</tr>";
		
		echo "<tr class='tab_bg_2'>";
		echo "<td>".$lang["setup"][350]."</td><td>";
		echo "<select name=\"helpdesk_hardware\">";
		echo "<option value=\"0\" ".($this->fields["helpdesk_hardware"]==0?"selected":"")." >------</option>";
		echo "<option value=\"".pow(2,HELPDESK_MY_HARDWARE)."\" ".($this->fields["helpdesk_hardware"]==pow(2,HELPDESK_MY_HARDWARE)?"selected":"")." >".$lang["tracking"][1]."</option>";
		echo "<option value=\"".pow(2,HELPDESK_ALL_HARDWARE)."\" ".($this->fields["helpdesk_hardware"]==pow(2,HELPDESK_ALL_HARDWARE)?"selected":"")." >".$lang["setup"][351]."</option>";
		echo "<option value=\"".(pow(2,HELPDESK_MY_HARDWARE)+pow(2,HELPDESK_ALL_HARDWARE))."\" ".($this->fields["helpdesk_hardware"]==(pow(2,HELPDESK_MY_HARDWARE)+pow(2,HELPDESK_ALL_HARDWARE))?"selected":"")." >".$lang["tracking"][1]." + ".$lang["setup"][351]."</option>";
		echo "</select>";
		echo "</td><td>".$lang["setup"][352]."</td>";
		echo "<td>";
			echo "<select name='helpdesk_hardware_type[]' multiple size='3'>";
			echo "<option value='".COMPUTER_TYPE."' ".(($this->fields["helpdesk_hardware_type"]&pow(2,COMPUTER_TYPE))?" selected":"").">".$lang["help"][25]."</option>\n";
			echo "<option value='".NETWORKING_TYPE."' ".(($this->fields["helpdesk_hardware_type"]&pow(2,NETWORKING_TYPE))?" selected":"").">".$lang["help"][26]."</option>\n";
			echo "<option value='".PRINTER_TYPE."' ".(($this->fields["helpdesk_hardware_type"]&pow(2,PRINTER_TYPE))?" selected":"").">".$lang["help"][27]."</option>\n";
			echo "<option value='".MONITOR_TYPE."' ".(($this->fields["helpdesk_hardware_type"]&pow(2,MONITOR_TYPE))?" selected":"").">".$lang["help"][28]."</option>\n";
			echo "<option value='".PERIPHERAL_TYPE."' ".(($this->fields["helpdesk_hardware_type"]&pow(2,PERIPHERAL_TYPE))?" selected":"").">".$lang["help"][29]."</option>\n";
			echo "<option value='".SOFTWARE_TYPE."' ".(($this->fields["helpdesk_hardware_type"]&pow(2,SOFTWARE_TYPE))?" selected":"").">".$lang["help"][31]."</option>\n";
			echo "<option value='".PHONE_TYPE."' ".(($this->fields["helpdesk_hardware_type"]&pow(2,PHONE_TYPE))?" selected":"").">".$lang["help"][35]."</option>\n";
			echo "</select>";
		echo "</td>";

		echo "</tr>";
	
		echo "<tr class='tab_bg_1'><td colspan='4' align='center'><strong>".$lang["Menu"][18]."</strong></td>";
		echo "</tr>";
	
	
		echo "<tr class='tab_bg_2'>";
		echo "<td>".$lang["knowbase"][1].":</td><td>";
		dropdownNoneReadWrite("faq",$this->fields["faq"],1,1,0);
		echo "</td>";
		echo "<td>".$lang["title"][35].":</td><td>";
		dropdownYesNoInt("reservation_helpdesk",$this->fields["reservation_helpdesk"]);
		echo "</td></tr>";
	
	
		if ($canedit){
			echo "<tr class='tab_bg_1'>";
			if ($ID){
				echo "<td colspan='2' align='center'>";
				echo "<input type='hidden' name='ID' value=$ID>";
				echo "<input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'>";
		
				echo "</td><td colspan='2' align='center'>";
				echo "<input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'>";
		
			} else {
				echo "<td colspan='4' align='center'>";
				echo "<input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'>";
			}
			echo "</td></tr>";
		}
		echo "</table>";
	
	}
	
	
	function showCentralForm($ID){
		global $lang;
	
		if (!haveRight("profile","r")) return false;
		$canedit=haveRight("profile","w");

		if ($ID){
			$this->getFromDB($ID);
		} else {
			$this->getEmpty();
		}
	
		echo "<table class='tab_cadre'><tr>";
		echo "<th colspan='6'>".$lang["profiles"][4].":&nbsp;&nbsp;".$lang["profiles"][13].":";
		dropdownYesNoInt("is_default",$this->fields["is_default"]);
		echo "</th></tr>";
	
		echo "<tr class='tab_bg_1'><td colspan='6' align='center'><strong>".$lang["setup"][10]."</strong></td></tr>";
	
		echo "<tr class='tab_bg_2'>";
		echo "<td>".$lang["Menu"][0].":</td><td>";
		dropdownNoneReadWrite("computer",$this->fields["computer"],1,1,1);
		echo "</td>";
		echo "<td>".$lang["Menu"][3].":</td><td>";
		dropdownNoneReadWrite("monitor",$this->fields["monitor"],1,1,1);
		echo "</td>";
		echo "<td>".$lang["Menu"][4].":</td><td>";
		dropdownNoneReadWrite("software",$this->fields["software"],1,1,1);
		echo "</td></tr>";
	
		echo "<tr class='tab_bg_2'>";
		echo "<td>".$lang["Menu"][1].":</td><td>";
		dropdownNoneReadWrite("networking",$this->fields["networking"],1,1,1);
		echo "</td>";
		echo "<td>".$lang["Menu"][2].":</td><td>";
		dropdownNoneReadWrite("printer",$this->fields["printer"],1,1,1);
		echo "</td>";
		echo "<td>".$lang["Menu"][21].":</td><td>";
		dropdownNoneReadWrite("cartridge",$this->fields["cartridge"],1,1,1);
		echo "</td></tr>";
	
		echo "<tr class='tab_bg_2'>";
		echo "<td>".$lang["Menu"][32].":</td><td>";
		dropdownNoneReadWrite("consumable",$this->fields["consumable"],1,1,1);
		echo "</td>";
		echo "<td>".$lang["Menu"][34].":</td><td>";
		dropdownNoneReadWrite("phone",$this->fields["phone"],1,1,1);
		echo "</td>";
		echo "<td>".$lang["Menu"][16].":</td><td>";
		dropdownNoneReadWrite("peripheral",$this->fields["peripheral"],1,1,1);
		echo "</td>";
		echo "</tr>";

		echo "<tr class='tab_bg_1'><td colspan='6' align='center'><strong>".$lang["profiles"][25]."</strong></td></tr>";


		echo "<tr class='tab_bg_2'>";
		echo "<td>".$lang["title"][37].":</td><td>";
		dropdownNoneReadWrite("notes",$this->fields["notes"],1,1,1);
		echo "</td>";
		echo "<td>".$lang["profiles"][24].":</td><td>";
		dropdownYesNoInt("password_update",$this->fields["password_update"]);
		echo "</td>";
		echo "<td>".$lang["reminder"][1].":</td><td>";
		dropdownNoneReadWrite("reminder_public",$this->fields["reminder_public"],1,1,1);
		echo "</td></tr>";
	
		echo "<tr class='tab_bg_1'><td colspan='6' align='center'><strong>".$lang["Menu"][26]."</strong></td></tr>";
	
		echo "<tr class='tab_bg_2'>";
		echo "<td>".$lang["Menu"][22]." / ".$lang["Menu"][23].":</td><td>";
		dropdownNoneReadWrite("contact_enterprise",$this->fields["contact_enterprise"],1,1,1);
		echo "</td>";
		echo "<td>".$lang["Menu"][27].":</td><td>";
		dropdownNoneReadWrite("document",$this->fields["document"],1,1,1);
		echo "</td>";
		echo "<td>".$lang["Menu"][24]." / ".$lang["Menu"][25].":</td><td>";
		dropdownNoneReadWrite("contract_infocom",$this->fields["contract_infocom"],1,1,1);
		echo "</td></tr>";
	
	
		echo "<tr class='tab_bg_1'><td colspan='6' align='center'><strong>".$lang["title"][24]."</strong></td></tr>";
	
		echo "<tr class='tab_bg_2'>";
		echo "<td>".$lang["profiles"][5].":</td><td>";
		dropdownYesNoInt("create_ticket",$this->fields["create_ticket"]);
		echo "</td>";
		echo "<td>".$lang["profiles"][14].":</td><td>";
		dropdownYesNoInt("delete_ticket",$this->fields["delete_ticket"]);
		echo "</td>";
		echo "<td>".$lang["profiles"][6].":</td><td>";
		dropdownYesNoInt("comment_ticket",$this->fields["comment_ticket"]);
		echo "</td></tr>";
	
		echo "<tr class='tab_bg_2'>";
		echo "<td>".$lang["profiles"][15].":</td><td>";
		dropdownYesNoInt("comment_all_ticket",$this->fields["comment_all_ticket"]);
		echo "</td>";
		echo "<td>".$lang["profiles"][18].":</td><td>";
		dropdownYesNoInt("update_ticket",$this->fields["update_ticket"]);
		echo "</td>";
		echo "<td>".$lang["profiles"][16].":</td><td>";
		dropdownYesNoInt("own_ticket",$this->fields["own_ticket"]);
		echo "</td></tr>";
	
		echo "<tr class='tab_bg_2'>";
		echo "<td>".$lang["profiles"][17].":</td><td>";
		dropdownYesNoInt("steal_ticket",$this->fields["steal_ticket"]);
		echo "</td>";
		echo "<td>".$lang["profiles"][19].":</td><td>";
		dropdownYesNoInt("assign_ticket",$this->fields["assign_ticket"]);
		echo "</td>";
		echo "<td>".$lang["profiles"][7].":</td><td>";
		dropdownYesNoInt("show_ticket",$this->fields["show_ticket"]);
		echo "</td></tr>";
	
		echo "<tr class='tab_bg_2'>";
		echo "<td>".$lang["profiles"][8].":</td><td>";
		dropdownYesNoInt("show_full_ticket",$this->fields["show_full_ticket"]);
		echo "</td>";
		echo "<td>".$lang["profiles"][9].":</td><td>";
		dropdownYesNoInt("observe_ticket",$this->fields["observe_ticket"]);
		echo "</td>";
		echo "<td>".$lang["stats"][19].":</td><td>";
		dropdownYesNoInt("statistic",$this->fields["statistic"]);
		echo "</td></tr>";
	
		echo "<tr class='tab_bg_2'>";
		echo "<td>".$lang["profiles"][20].":</td><td>";
		dropdownYesNoInt("show_planning",$this->fields["show_planning"]);
		echo "</td>";
		echo "<td>".$lang["profiles"][21].":</td><td>";
		dropdownYesNoInt("show_all_planning",$this->fields["show_all_planning"]);
		echo "</td>";

		echo "<td>".$lang["profiles"][26]."</td><td>";
		dropdownYesNoInt("show_group_ticket",$this->fields["show_group_ticket"]);
		echo "</td></tr>";

		echo "<tr class='tab_bg_2'>";

		echo "<td colspan='2'>".$lang["setup"][350].":</td><td>";
		echo "<select name=\"helpdesk_hardware\">";
		echo "<option value=\"0\" ".($this->fields["helpdesk_hardware"]==0?"selected":"")." >------</option>";
		echo "<option value=\"".pow(2,HELPDESK_MY_HARDWARE)."\" ".($this->fields["helpdesk_hardware"]==pow(2,HELPDESK_MY_HARDWARE)?"selected":"")." >".$lang["tracking"][1]."</option>";
		echo "<option value=\"".pow(2,HELPDESK_ALL_HARDWARE)."\" ".($this->fields["helpdesk_hardware"]==pow(2,HELPDESK_ALL_HARDWARE)?"selected":"")." >".$lang["setup"][351]."</option>";
		echo "<option value=\"".(pow(2,HELPDESK_MY_HARDWARE)+pow(2,HELPDESK_ALL_HARDWARE))."\" ".($this->fields["helpdesk_hardware"]==(pow(2,HELPDESK_MY_HARDWARE)+pow(2,HELPDESK_ALL_HARDWARE))?"selected":"")." >".$lang["tracking"][1]." + ".$lang["setup"][351]."</option>";
		echo "</select>";
		echo "</td>";

		echo "<td colspan='2'>".$lang["setup"][352].":</td>";
		echo "<td>";
			echo "<select name='helpdesk_hardware_type[]' multiple size='3'>";
			echo "<option value='".COMPUTER_TYPE."' ".(($this->fields["helpdesk_hardware_type"]&pow(2,COMPUTER_TYPE))?" selected":"").">".$lang["help"][25]."</option>\n";
			echo "<option value='".NETWORKING_TYPE."' ".(($this->fields["helpdesk_hardware_type"]&pow(2,NETWORKING_TYPE))?" selected":"").">".$lang["help"][26]."</option>\n";
			echo "<option value='".PRINTER_TYPE."' ".(($this->fields["helpdesk_hardware_type"]&pow(2,PRINTER_TYPE))?" selected":"").">".$lang["help"][27]."</option>\n";
			echo "<option value='".MONITOR_TYPE."' ".(($this->fields["helpdesk_hardware_type"]&pow(2,MONITOR_TYPE))?" selected":"").">".$lang["help"][28]."</option>\n";
			echo "<option value='".PERIPHERAL_TYPE."' ".(($this->fields["helpdesk_hardware_type"]&pow(2,PERIPHERAL_TYPE))?" selected":"").">".$lang["help"][29]."</option>\n";
			echo "<option value='".SOFTWARE_TYPE."' ".(($this->fields["helpdesk_hardware_type"]&pow(2,SOFTWARE_TYPE))?" selected":"").">".$lang["help"][31]."</option>\n";
			echo "<option value='".PHONE_TYPE."' ".(($this->fields["helpdesk_hardware_type"]&pow(2,PHONE_TYPE))?" selected":"").">".$lang["help"][35]."</option>\n";
			echo "</select>";
		echo "</td>";

		echo "</tr>";
	

	
		echo "<tr class='tab_bg_1'><td colspan='6' align='center'><strong>".$lang["Menu"][18]."</strong></td>";
		echo "</tr>";
	
		echo "<tr class='tab_bg_2'>";
		echo "<td>".$lang["knowbase"][1].":</td><td>";
		dropdownNoneReadWrite("faq",$this->fields["faq"],1,1,1);
		echo "</td>";
		echo "<td>".$lang["knowbase"][0].":</td><td>";
		dropdownNoneReadWrite("knowbase",$this->fields["knowbase"],1,1,1);
		echo "</td>";
		echo "<td>".$lang["title"][35].":</td><td>";
		dropdownYesNoInt("reservation_helpdesk",$this->fields["reservation_helpdesk"]);
		echo "</td></tr>";
	
		echo "<tr class='tab_bg_2'>";
		echo "<td>".$lang["Menu"][6].":</td><td>";
		dropdownNoneReadWrite("reports",$this->fields["reports"],1,1,0);
		echo "</td>";
		echo "<td>".$lang["Menu"][33].":</td><td>";
		dropdownNoneReadWrite("ocsng",$this->fields["ocsng"],1,0,1);
		echo "</td>";
		echo "<td>".$lang["profiles"][23].":</td><td>";
		dropdownNoneReadWrite("reservation_central",$this->fields["reservation_central"],1,1,1);
		echo "</td></tr>";
	
		echo "<tr class='tab_bg_1'><td colspan='6' align='center'><strong>".$lang["Menu"][15]."</strong></td>";
		echo "</tr>";
	
		echo "<tr class='tab_bg_2'>";
		echo "<td>".$lang["setup"][0].":</td><td>";
		dropdownNoneReadWrite("dropdown",$this->fields["dropdown"],1,0,1);
		echo "</td>";
		echo "<td>".$lang["setup"][222].":</td><td>";
		dropdownNoneReadWrite("device",$this->fields["device"],1,0,1);
		echo "</td>";
		echo "<td>".$lang["document"][7].":</td><td>";
		dropdownNoneReadWrite("typedoc",$this->fields["typedoc"],1,1,1);
		echo "</td></tr>";
	
		echo "<tr class='tab_bg_2'>";
		echo "<td>".$lang["setup"][87].":</td><td>";
		dropdownNoneReadWrite("link",$this->fields["link"],1,1,1);
		echo "</td>";
		echo "<td>".$lang["title"][2].":</td><td>";
		dropdownNoneReadWrite("config",$this->fields["config"],1,0,1);
		echo "</td>";
		echo "<td>".$lang["setup"][250].":</td><td>";
		dropdownNoneReadWrite("search_config",$this->fields["search_config"],1,0,1);
		echo "</td></tr>";
	
	
		echo "<tr class='tab_bg_2'>";
		echo "<td>".$lang["setup"][306].":</td><td>";
		dropdownNoneReadWrite("check_update",$this->fields["check_update"],1,1,0);
		echo "</td>";
		echo "<td>".$lang["Menu"][14].":</td><td>";
		dropdownNoneReadWrite("user",$this->fields["user"],1,1,1);
		echo "</td>";
		echo "<td>".$lang["Menu"][35].":</td><td>";
		dropdownNoneReadWrite("profile",$this->fields["profile"],1,1,1);
		echo "</td></tr>";
	
		echo "<tr class='tab_bg_2'>";
		echo "<td>".$lang["Menu"][36].":</td><td>";
		dropdownNoneReadWrite("group",$this->fields["group"],1,1,1);
		echo "</td>";
		echo "<td>".$lang["Menu"][12].":</td><td>";
		dropdownNoneReadWrite("backup",$this->fields["backup"],1,0,1);
		echo "</td>";
		echo "<td>".$lang["Menu"][30].":</td><td>";
		dropdownNoneReadWrite("logs",$this->fields["logs"],1,1,0);
		echo "</td></tr>";
		
		if ($canedit){
			echo "<tr class='tab_bg_1'>";
			if ($ID){
				echo "<td colspan='3' align='center'>";
				echo "<input type='hidden' name='ID' value=$ID>";
				echo "<input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'>";
				echo "</td><td colspan='3' align='center'>";
				echo "<input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'>";
			} else {
				echo "<td colspan='6' align='center'>";
				echo "<input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'>";
			}
			echo "</td></tr>";
		}
		echo "</table>";
	
	}

}

?>
