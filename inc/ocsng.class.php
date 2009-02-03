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

if (!defined('GLPI_ROOT')) {
	die("Sorry. You can't access directly to this file");
}

/// DB class to connect to a OCS server
class DBocs extends DBmysql {

	///Store the id of the ocs server
	var $ocs_server_id = -1;

	/**
	 * Constructor
	 * @param $ID ID of the ocs server ID
	**/
	function __construct($ID) {
		global $CFG_GLPI;
			$this->ocs_server_id = $ID;
			
			if ($CFG_GLPI["ocs_mode"]) {
				$data = getOcsConf($ID);
				$this->dbhost = $data["ocs_db_host"];
				$this->dbuser = $data["ocs_db_user"];
				$this->dbpassword = rawurldecode($data["ocs_db_passwd"]);
				$this->dbdefault = $data["ocs_db_name"];
				$this->dbenc="latin1";
				parent::__construct();
			}
	}
	
	/**
	 * Get current ocs server ID
	 * @return ID of the ocs server ID
	**/
	function getServerID()
	{
		return $this->ocs_server_id;
	}


}

/// OCS config class
class Ocsng extends CommonDBTM {

	/**
	 * Constructor
	**/
	function __construct() {
		$this->table = "glpi_ocs_config";
		$this->type = OCSNG_TYPE;
	}

	function defineTabs($ID,$withtemplate='')
	{
		global $LANG;
		$tabs[0]=$LANG["help"][30];
		
		//If connection to the OCS DB  is ok, and all rights are ok too
		if ($ID != '' && checkOCSconnection($ID) &&
				ocsCheckConfig(1) &&
					ocsCheckConfig(2) &&
						ocsCheckConfig(4) && 
							ocsCheckConfig(8)){
			$tabs[1]=$LANG["ocsconfig"][5];
			$tabs[2]=$LANG["ocsconfig"][27];
			$tabs[3]=$LANG["setup"][620];
		}
		return $tabs;
	}

	/**
	 * Print ocs config form
	 *
	 *@param $target form target
	 *@param $ID Integer : Id of the ocs config
	 *@param $withtemplate template or basic computer
	 *@param $templateid Integer : Id of the template used
	 *@todo clean template process
	 *@return Nothing (display)
	 *
	 **/
	function ocsFormConfig($target, $ID) {
		global $DB, $LANG, $CFG_GLPI;
		
		if (!haveRight("ocsng", "w"))
			return false;

		$this->getFromDB($ID);
		echo "<br>";		
		echo "<div class='center'>"; 
		echo "<form name='formconfig' action=\"$target\" method=\"post\">";
		echo "<input type='hidden' name='ID' value='" . $ID . "'>";
		echo "<table class='tab_cadre'>";
		echo "<tr><th>" . $LANG["ocsconfig"][27] ." ".$LANG["Menu"][0]. "</th><th>" . $LANG["title"][30] . "</th><th>" . $LANG["ocsconfig"][43] . "</th></tr>";
		echo "<tr><td class='tab_bg_2' valign='top'><table width='100%' cellpadding='1' cellspacing='0' border='0'>";

		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["common"][16] . " </td><td>";
		dropdownYesNo("import_general_name", $this->fields["import_general_name"]);
		echo "</td></tr>";

		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["computers"][9] . " </td><td>";
		dropdownYesNo("import_general_os", $this->fields["import_general_os"]);
		echo "</td></tr>";

		echo "<tr class='tab_bg_2'><td colspan='2'>";
		echo "</td></tr>";
		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["computers"][10] . " </td><td>";
		dropdownYesNo("import_os_serial", $this->fields["import_os_serial"]);
		echo "</td></tr>";

		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["common"][19] . " </td><td>";
		dropdownYesNo("import_general_serial", $this->fields["import_general_serial"]);
		echo "</td></tr>";
		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["common"][22] . " </td><td>";
		dropdownYesNo("import_general_model", $this->fields["import_general_model"]);
		echo "</td></tr>";
		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["common"][5] . " </td><td>";
		dropdownYesNo("import_general_enterprise", $this->fields["import_general_enterprise"]);
		echo "</td></tr>";
		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["common"][17] . " </td><td>";
		dropdownYesNo("import_general_type", $this->fields["import_general_type"]);
		echo "</td></tr>";
		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["setup"][89] . " </td><td>";
		dropdownYesNo("import_general_domain", $this->fields["import_general_domain"]);
		echo "</td></tr>";
		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["common"][18] . " </td><td>";
		dropdownYesNo("import_general_contact", $this->fields["import_general_contact"]);
		echo "</td></tr>";
		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["common"][25] . " </td><td>";
		dropdownYesNo("import_general_comments", $this->fields["import_general_comments"]);
		echo "</td></tr>";

		echo "<tr class='tab_bg_2'><td colspan='2'>";
		echo "</td></tr>";
		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["networking"][14] . " </td><td>";
		dropdownYesNo("import_ip", $this->fields["import_ip"]);
		echo "</td></tr>";

		echo "</table></td>";
		
		echo "<td class='tab_bg_2' valign='top'><table width='100%' cellpadding='1' cellspacing='0' border='0'>";

		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["devices"][4] . " </td><td>";
		dropdownYesNo("import_device_processor", $this->fields["import_device_processor"]);
		echo "</td></tr>";

		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["devices"][6] . " </td><td>";
		dropdownYesNo("import_device_memory", $this->fields["import_device_memory"]);
		echo "</td></tr>";

		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["devices"][1] . " </td><td>";
		dropdownYesNo("import_device_hdd", $this->fields["import_device_hdd"]);
		echo "</td></tr>";

		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["devices"][3] . " </td><td>";
		dropdownYesNo("import_device_iface", $this->fields["import_device_iface"]);
		echo "</td></tr>";

		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["devices"][2] . " </td><td>";
		dropdownYesNo("import_device_gfxcard", $this->fields["import_device_gfxcard"]);
		echo "</td></tr>";

		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["devices"][7] . " </td><td>";
		dropdownYesNo("import_device_sound", $this->fields["import_device_sound"]);
		echo "</td></tr>";

		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["devices"][19] . " </td><td>";
		dropdownYesNo("import_device_drives", $this->fields["import_device_drives"]);
		echo "</td></tr>";

		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["ocsconfig"][36] . " </td><td>";
		dropdownYesNo("import_device_modems", $this->fields["import_device_modems"]);
		echo "</td></tr>";

		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["ocsconfig"][37] . " </td><td>";
		dropdownYesNo("import_device_ports", $this->fields["import_device_ports"]);
		echo "</td></tr>";		

		echo "</table></td><td  class='tab_bg_2' valign='top'><table width='100%' cellpadding='1' cellspacing='0' border='0'>";

		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["common"][20] . " </td><td>";
		echo "<select name='import_otherserial'>";		echo "<option value=''>" . $LANG["ocsconfig"][11] . "</option>";
		$listColumnOCS = getColumnListFromAccountInfoTable($ID,"otherserial");		
		echo $listColumnOCS;
		echo "</select>";
		echo "</td></tr>";
		
		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["common"][15] . " </td><td>";
		echo "<select name='import_location'>";		echo "<option value=''>" . $LANG["ocsconfig"][11] . "</option>";
		$listColumnOCS = getColumnListFromAccountInfoTable($ID,"location");
		echo $listColumnOCS;
		echo "</select>";
		echo "</td></tr>";
		
		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["common"][35] . " </td><td>";
		echo "<select name='import_group'>";		echo "<option value=''>" . $LANG["ocsconfig"][11] . "</option>";
		$listColumnOCS = getColumnListFromAccountInfoTable($ID,"FK_groups");
		echo $listColumnOCS;
		echo "</select>";
		echo "</td></tr>";
		
		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["common"][21] . " </td><td>";
		echo "<select name='import_contact_num'>";		echo "<option value=''>" . $LANG["ocsconfig"][11] . "</option>";
		$listColumnOCS = getColumnListFromAccountInfoTable($ID,"contact_num");
		echo $listColumnOCS;
		echo "</select>";
		echo "</td></tr>";
		
		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["setup"][88] . " </td><td>";
		echo "<select name='import_network'>";		echo "<option value=''>" . $LANG["ocsconfig"][11] . "</option>";
		$listColumnOCS = getColumnListFromAccountInfoTable($ID,"network");
		echo $listColumnOCS;
		echo "</select>";
		echo "</td></tr>";

		echo "</table></td>";
		echo "</tr>";

		echo "<tr><th>" . $LANG["ocsconfig"][27] ." ".$LANG["Menu"][3]. "</th><th>" . $LANG["ocsconfig"][27] ." ".$LANG["Menu"][4] . "</th><th>&nbsp;</th></tr>";
		
		echo "<tr><td class='tab_bg_2' valign='top'><table width='100%' cellpadding='1' cellspacing='0' border='0'>";
		echo "<tr class='tab_bg_2'><td align='center'>" . $LANG["common"][25] . " </td><td>";
		dropdownYesNo("import_monitor_comments", $this->fields["import_monitor_comments"]);
		echo "</td></tr>";
		echo "</table></td>";
		
		echo "<td class='tab_bg_2' valign='top'><table width='100%' cellpadding='1' cellspacing='0' border='0'>";
		echo "<tr class='tab_bg_2'><td align='center'>" . $LANG["common"][25] . " </td><td>";
		dropdownYesNo("import_software_comments", $this->fields["import_software_comments"]);
		echo "</td></tr>";
		echo "</table></td>";
		echo "<td class='tab_bg_2' valign='top'></td></tr>"; 
		echo "</table>";
		echo "<p class=\"submit\"><input type=\"submit\" name=\"update_server\" class=\"submit\" value=\"" . $LANG["buttons"][2] . "\" ></p>";
		echo "</form></div>";

		echo "<br>";
		echo "<div class='center'>" . $LANG["ocsconfig"][15] . "</div>";
		echo "<div class='center'>" . $LANG["ocsconfig"][14] . "</div>";
		echo "<div class='center'>" . $LANG["ocsconfig"][13] . "</div>";
	}

	function ocsFormImportOptions($target, $ID,$withtemplate='',$templateid='')
	{
		global $LANG;
		
		$this->getFromDB($ID);
		echo "<br>";
		echo "<div class='center'>";
		echo "<form name='formconfig' action=\"$target\" method=\"post\">";
		echo "<table class='tab_cadre'>";
		echo "<input type='hidden' name='ID' value='" . $ID . "'>";
		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["ocsconfig"][59] . " </td><td> <input type=\"text\" size='30' name=\"ocs_url\" value=\"" . $this->fields["ocs_url"] . "\"></td></tr>";

		echo "<tr><th colspan='2'>" . $LANG["ocsconfig"][5] . "</th></tr>";

		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["ocsconfig"][17] . " </td><td> <input type=\"text\" size='30' name=\"tag_limit\" value=\"" . $this->fields["tag_limit"] . "\"></td></tr>";

		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["ocsconfig"][9] . " </td><td> <input type=\"text\" size='30' name=\"tag_exclude\" value=\"" . $this->fields["tag_exclude"] . "\"></td></tr>";

		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["ocsconfig"][16] . " </td><td>";
		dropdownValue("glpi_dropdown_state", "default_state", $this->fields["default_state"]);
		echo "</td></tr>";
		
		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["ocsconfig"][48] . " </td><td>";
		dropdownArrayValues("deconnection_behavior", 
			array(''=>$LANG["buttons"][49], "trash"=>$LANG["ocsconfig"][49], "delete"=>$LANG["ocsconfig"][50]), 
			$this->fields["deconnection_behavior"]);
		echo "</td></tr>";
		
		$import_array = array("0"=>$LANG["ocsconfig"][11],"1"=>$LANG["ocsconfig"][10],"2"=>$LANG["ocsconfig"][12]);
		$import_array2= array("0"=>$LANG["ocsconfig"][11],"1"=>$LANG["ocsconfig"][10],"2"=>$LANG["ocsconfig"][12],"3"=>$LANG["ocsconfig"][19]);
		$periph = $this->fields["import_periph"];
		$monitor = $this->fields["import_monitor"];
		$printer = $this->fields["import_printer"];
		$software = $this->fields["import_software"];
		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["Menu"][16] . " </td><td>";
		dropdownArrayValues("import_periph",$import_array,$periph);
		echo "</td></tr>";

		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["Menu"][3] . " </td><td>";
		dropdownArrayValues("import_monitor",$import_array2,$monitor);
		echo "</td></tr>";

		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["Menu"][2] . " </td><td>";
		dropdownArrayValues("import_printer",$import_array,$printer);
		echo "</td></tr>";

		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["Menu"][4] . " </td><td>";
		$import_array = array("0"=>$LANG["ocsconfig"][11],"1"=>$LANG["ocsconfig"][12]);
		dropdownArrayValues("import_software",$import_array,$software);
		echo "</td></tr>";

		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["computers"][8] . " </td><td>";
		dropdownYesNo("import_disk", $this->fields["import_disk"]);
		echo "</td></tr>";

		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["ocsconfig"][38] . " </td><td>";
		dropdownYesNo("use_soft_dict", $this->fields["use_soft_dict"]);
		echo "</td></tr>";		

		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["ocsconfig"][41] . " </td><td>";
		dropdownYesNo("import_registry", $this->fields["import_registry"]);
		echo "</td></tr>";

		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["ocsconfig"][40] . " </td><td>";
		dropdownInteger('cron_sync_number', $this->fields["cron_sync_number"], 0, 100);
		echo "</td></tr>";
		
		echo "<tr class='tab_bg_2'><td colspan='2 class='center'>";
		echo "<p class=\"submit\"><input type=\"submit\" name=\"update_server\" class=\"submit\" value=\"" . $LANG["buttons"][2] . "\" ></p>";
		echo "</td></tr>";
		echo "</form>";
		echo "</table></div>";
	}

	function ocsFormAutomaticLinkConfig($target, $ID,$withtemplate='',$templateid='') {
		global $DB, $LANG, $CFG_GLPI;
		
		if (!haveRight("ocsng", "w"))
			return false;

		$this->getFromDB($ID);		

		echo "<br>";
		echo "<div class='center'>";
		echo "<form name='formconfig' action=\"$target\" method=\"post\">";
		echo "<table class='tab_cadre'>";
		echo "<input type='hidden' name='ID' value='" . $ID . "'>";
		echo "<tr><th colspan='4'>" . $LANG["ocsconfig"][52] . "</th></tr>";
		echo "<tr class='tab_bg_2'><td>" . $LANG["ocsconfig"][53] . " </td><td>";
		dropdownYesNo("glpi_link_enabled", $this->fields["glpi_link_enabled"]);
		echo "</td><td colspan='2'></td></tr>";
		
		echo "<tr><th colspan='4'>" . $LANG["ocsconfig"][54] . "</th></tr>";
		echo "<tr class='tab_bg_2'><td>" . $LANG["networking"][14] . " </td><td>";
		dropdownYesNo("link_ip", $this->fields["link_ip"]);
		echo "</td>";
		echo "<td>" . $LANG["device_iface"][2] . " </td><td>";
		dropdownYesNo("link_mac_address", $this->fields["link_mac_address"]);
		echo "</td></tr>";
		echo "<tr class='tab_bg_2'><td>" . $LANG["rulesengine"][25] . " </td><td>";
		$link_array=array("0"=>$LANG["choice"][0],"1"=>$LANG["choice"][1]." : ".$LANG["ocsconfig"][57],"2"=>$LANG["choice"][1]." : ".$LANG["ocsconfig"][56]);
		dropdownArrayValues("link_name", $link_array,$this->fields["link_name"]);
		echo "</td>";
		echo "<td>" . $LANG["common"][19] . " </td><td>";
		dropdownYesNo("link_serial", $this->fields["link_serial"]);
		echo "</td></tr>";
		echo "<tr class='tab_bg_2'><td>" . $LANG["ocsconfig"][55] . " </td><td>";
		dropdownValue("glpi_dropdown_state", "link_if_status", $this->fields["link_if_status"]);
		echo "</td><td colspan='2'></tr>";
		
		echo "</table><br>".$LANG["ocsconfig"][58];
		echo "<p class=\"submit\"><input type=\"submit\" name=\"update_server\" class=\"submit\" value=\"" . $LANG["buttons"][2] . "\" ></p>";
		echo "</form>";
		echo "</div>";
	}

	/**
	 * Print simple ocs config form (database part)
	 *
	 *@param $target form target
	 *@param $ID Integer : Id of the ocs config
	 *@return Nothing (display)
	 *
	 **/
	function showForm($target, $ID) {

		global $DB, $DBocs, $LANG, $CFG_GLPI;

		if (!haveRight("ocsng", "w"))
			return false;
			
		//If no ID provided, or if the server is created using an existing template
		if (empty ($ID)) {
			$this->getEmpty();
		} else { 
			$this->getFromDB($ID);
		}
		
		$this->showTabs($ID, '',$_SESSION['glpi_tab']);
		
		$out  = "<div class='center' id='tabsbody'>";	
		$out .= "<form name='formdbconfig' action=\"$target\" method=\"post\">";
		$out .= "<table class='tab_cadre_fixe'>";
		$out .= "<tr class='tab_bg_2'><td class='center'>" . $LANG["common"][16] . " </td><td> <input type=\"text\" name=\"name\" value=\"" . $this->fields["name"] . "\"></td></tr>";
		$out .= "<tr class='tab_bg_2'><td class='center'>" . $LANG["ocsconfig"][2] . " </td><td> <input type=\"text\" name=\"ocs_db_host\" value=\"" . $this->fields["ocs_db_host"] . "\"></td></tr>";
		$out .= "<tr class='tab_bg_2'><td class='center'>" . $LANG["ocsconfig"][4] . " </td><td> <input type=\"text\" name=\"ocs_db_name\" value=\"" . $this->fields["ocs_db_name"] . "\"></td></tr>";
		$out .= "<tr class='tab_bg_2'><td class='center'>" . $LANG["ocsconfig"][1] . " </td><td> <input type=\"text\" name=\"ocs_db_user\" value=\"" . $this->fields["ocs_db_user"] . "\"></td></tr>";
		$out .= "<tr class='tab_bg_2'><td class='center'>" . $LANG["ocsconfig"][3] . " </td><td> <input type=\"password\" name=\"ocs_db_passwd\" value=\"\"></td></tr>";

		if ($ID == '')
			$out .= "<tr class='tab_bg_2'><td align='center' colspan=2><input type=\"submit\" name=\"add\" class=\"submit\" value=\"" . $LANG["buttons"][2] . "\" ></td></tr>";
		else
		{
			$out .= "<input type='hidden' name='ID' value='$ID'>";
			$out .= "<tr class='tab_bg_2'><td align='center' colspan=2><input type=\"submit\" name=\"update\" class=\"submit\" value=\"" . $LANG["buttons"][2] . "\" >";
			$out .= "&nbsp;<input type=\"submit\" name=\"delete\" class=\"submit\" value=\"" . $LANG["buttons"][6] . "\" ></td></tr>";
			
		}
		$out .= "</table>";
		$out .= "</form>";
		$out .= "</div>";
		$out .= "<div id='tabcontent'></div>";
		$out .= "<script type='text/javascript'>loadDefaultTab();</script>";
		echo $out;
	}
	
	function showDBConnectionStatus($ID)
	{
			global $LANG;
			$out="<br>";
			$out.="<div class='center'>";
			$out.="<div class='center'>";
			$out.="<table class='tab_cadre'>";
			$out.="<tr><th>" .$LANG["setup"][602] . "</th></tr>";
			$out.="<tr class='tab_bg_2'><td align='center'>";
			if ($ID != -1) {
			if (!checkOCSconnection($ID)){
				$out.=$LANG["ocsng"][21];
			}
			else if (!ocsCheckConfig(1)) {
				$out.=$LANG["ocsng"][20];
			}
			else if (!ocsCheckConfig(2)) {
				$out.=$LANG["ocsng"][42];
			}
			else if (!ocsCheckConfig(4)) {
				$out.=$LANG["ocsng"][43];
			}
			else if (!ocsCheckConfig(8)) {
				$out.=$LANG["ocsng"][44];
			}
			else {
				$out.=$LANG["ocsng"][18];
				$out.="<tr class='tab_bg_2'><td align='center'>".$LANG["ocsng"][19];
			}

		}
		$out.="</td>";
		$out.="</table>";
		$out.="</div>";
		echo $out;
		
	}
	
	function prepareInputForUpdate($input){
		
		if (isset($input["ocs_db_passwd"])&&!empty($input["ocs_db_passwd"])){
			$input["ocs_db_passwd"]=rawurlencode(stripslashes($input["ocs_db_passwd"]));
		} else {
			unset($input["ocs_db_passwd"]);
		}

		//Someting in the OCS configuration has changed -> need to update the checksum!
		if (isset($input["import_ip"]) || isset($input["import_disk"])){
			$input["checksum"]=0;
			
			//If import_disk is set : we're updating the importation options
			if (isset($input["import_disk"]))
			{
				if ($input["import_printer"]) $input["checksum"]|= pow(2,PRINTERS_FL);
				if ($input["import_software"]) $input["checksum"]|= pow(2,SOFTWARES_FL);
				if ($input["import_monitor"]) $input["checksum"]|= pow(2,MONITORS_FL);
				if ($input["import_periph"]) $input["checksum"]|= pow(2,INPUTS_FL);
				if ($input["import_registry"]) $input["checksum"]|= pow(2,REGISTRY_FL);
				if ($input["import_disk"]) $input["checksum"]|= pow(2,DRIVES_FL);
			}
			else
			//We're updating the general informations
			if (isset($input["import_ip"])){
			
				if ($input["import_ip"]) $input["checksum"]|= pow(2,NETWORKS_FL);
				if ($input["import_device_ports"]) $input["checksum"]|= pow(2,PORTS_FL);
				if ($input["import_device_modems"]) $input["checksum"]|= pow(2,MODEMS_FL);
				if ($input["import_device_drives"]) $input["checksum"]|= pow(2,STORAGES_FL);
				if ($input["import_device_sound"]) $input["checksum"]|= pow(2,SOUNDS_FL);
				if ($input["import_device_gfxcard"]) $input["checksum"]|= pow(2,VIDEOS_FL);
				if ($input["import_device_iface"]) $input["checksum"]|= pow(2,NETWORKS_FL);
				if ($input["import_device_hdd"]) $input["checksum"]|= pow(2,STORAGES_FL);
				if ($input["import_device_memory"]) $input["checksum"]|= pow(2,MEMORIES_FL);
				if (	$input["import_device_processor"]
						||$input["import_general_contact"]
						||$input["import_general_comments"]
						||$input["import_general_domain"]
						||$input["import_general_os"]
						||$input["import_general_name"]) $input["checksum"]|= pow(2,HARDWARE_FL);
				if (	$input["import_general_enterprise"]
						||$input["import_general_type"]
						||$input["import_general_model"]
						||$input["import_general_serial"]) $input["checksum"]|= pow(2,BIOS_FL);
			}
		}
		return $input;
	}
	
	function prepareInputForAdd($input){
		global $LANG,$DB;
		
		// Check if server config does not exists
		$query="SELECT * FROM `" . $this->table . "` WHERE name='".$input['name']."';";
		$result=$DB->query($query);
		if ($DB->numrows($result)>0){
			addMessageAfterRedirect($LANG["setup"][609]);
			return false;
		}
		
		
		if (isset($input["ocs_db_passwd"])&&!empty($input["ocs_db_passwd"])){
			$input["ocs_db_passwd"]=rawurlencode(stripslashes($input["ocs_db_passwd"]));
		} else {
			unset($input["ocs_db_passwd"]);
		}

		if (isset($input["import_ip"])){ # are inputs defined
			$input["checksum"]=0;

			if ($input["import_ip"]) $input["checksum"]|= pow(2,NETWORKS_FL);
			if ($input["import_device_ports"]) $input["checksum"]|= pow(2,PORTS_FL);
			if ($input["import_device_modems"]) $input["checksum"]|= pow(2,MODEMS_FL);
			if ($input["import_device_drives"]) $input["checksum"]|= pow(2,STORAGES_FL);
			if ($input["import_device_sound"]) $input["checksum"]|= pow(2,SOUNDS_FL);
			if ($input["import_device_gfxcard"]) $input["checksum"]|= pow(2,VIDEOS_FL);
			if ($input["import_device_iface"]) $input["checksum"]|= pow(2,NETWORKS_FL);
			if ($input["import_device_hdd"]) $input["checksum"]|= pow(2,STORAGES_FL);
			if ($input["import_device_memory"]) $input["checksum"]|= pow(2,MEMORIES_FL);
			if ($input["import_disk"]) $input["checksum"]|= pow(2,DRIVES_FL);

			if (	$input["import_device_processor"]
					||$input["import_general_contact"]
					||$input["import_general_comments"]
					||$input["import_general_domain"]
					||$input["import_general_os"]
					||$input["import_general_name"]) $input["checksum"]|= pow(2,HARDWARE_FL);
			if (	$input["import_general_enterprise"]
					||$input["import_general_type"]
					||$input["import_general_model"]
					||$input["import_general_serial"]) $input["checksum"]|= pow(2,BIOS_FL);
			if ($input["import_printer"]) $input["checksum"]|= pow(2,PRINTERS_FL);
			if ($input["import_software"]) $input["checksum"]|= pow(2,SOFTWARES_FL);
			if ($input["import_monitor"]) $input["checksum"]|= pow(2,MONITORS_FL);
			if ($input["import_periph"]) $input["checksum"]|= pow(2,INPUTS_FL);
			if ($input["import_registry"]) $input["checksum"]|= pow(2,REGISTRY_FL);
		}
		
		return $input;
	}

	function cleanDBonPurge($ID) {
		global $DB;

		$query = "DELETE FROM glpi_ocs_link WHERE (ocs_server_id = '$ID')";
		$result = $DB->query($query);
	}

		
	/**
	 * Update Admin Info retrieve config
	 *
	 *@param $tab data array
	 **/
	function updateAdminInfo($tab){
		$adm = new AdminInfo();	
		$adm->cleanDBonPurge($tab["ID"]);		
		if (isset ($tab["import_location"])){
 			if($tab["import_location"]!=""){
				$adm = new AdminInfo();			
				$adm->fields["ocs_server_id"] = $tab["ID"];							
				$adm->fields["glpi_column"] = "location";	
				$adm->fields["ocs_column"] = $tab["import_location"];				
				$isNewAdm = $adm->addToDB(); 
 			}          		
		}
		if (isset ($tab["import_otherserial"])){
			if($tab["import_otherserial"]!=""){
				$adm = new AdminInfo();			
				$adm->fields["ocs_server_id"] =  $tab["ID"];			
				$adm->fields["glpi_column"] = "otherserial";	
				$adm->fields["ocs_column"] = $tab["import_otherserial"];		
				$isNewAdm = $adm->addToDB();
			}				
		}
		if (isset ($tab["import_group"])){			
			if($tab["import_group"]!=""){
				$adm = new AdminInfo();			
				$adm->fields["ocs_server_id"] = $tab["ID"];		
				$adm->fields["glpi_column"] = "FK_groups";	
				$adm->fields["ocs_column"] = $tab["import_group"];				
				$isNewAdm = $adm->addToDB();
			}
		}
		if (isset ($tab["import_network"])){
			if($tab["import_network"]!=""){			
				$adm = new AdminInfo();			
				$adm->fields["ocs_server_id"] = $tab["ID"];		
				$adm->fields["glpi_column"] = "network";	
				$adm->fields["ocs_column"] = $tab["import_network"];				
				$isNewAdm = $adm->addToDB();
			}
		}
		if (isset ($tab["import_contact_num"])){
			if($tab["import_contact_num"]!=""){			
				$adm = new AdminInfo();			
				$adm->fields["ocs_server_id"] = $tab["ID"];		
				$adm->fields["glpi_column"] = "contact_num";	
				$adm->fields["ocs_column"] = $tab["import_contact_num"];				
				$isNewAdm = $adm->addToDB(); 
			}
		}
	}	
}


?>
