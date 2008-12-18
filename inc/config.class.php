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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}

/// Config class 
class Config extends CommonDBTM {

	/**
	 * Constructor 
	**/
	function __construct () {
		$this->table="glpi_config";
		$this->type=-1;
	}
	/**
	 * Prepare input datas for updating the item 
	 *
	 *@param $input datas used to update the item
	 * 
	 *@return the modified $input array
	 * 
	**/
	function prepareInputForUpdate($input) {
		if (isset($input["smtp_password"])&&empty($input["smtp_password"]))
			unset($input["smtp_password"]);
		if (isset($input["proxy_password"])&&empty($input["proxy_password"]))
			unset($input["proxy_password"]);

		if (isset($input["planning_begin"]))
			$input["planning_begin"]=$input["planning_begin"].":00:00";
		if (isset($input["planning_end"]))
			$input["planning_end"]=$input["planning_end"].":00:00";


		// Manage DB Slave process
		if (isset($input['_dbslave_status'])){
			$already_active=isDBSlaveActive();
			if ($input['_dbslave_status']&&!$already_active){
				createDBSlaveConfig();
			} else {
				saveDBSlaveConf($input["_dbreplicate_dbhost"],$input["_dbreplicate_dbuser"],$input["_dbreplicate_dbpassword"],$input["_dbreplicate_dbdefault"]);
			}
			if (!$input['_dbslave_status']&&$already_active){
				deleteDBSlaveConfig();
			}

		}


		return $input;
	}

	/**
	 * Actions done after the UPDATE of the item in the database
	 *
	 *@param $input datas used to update the item
	 *@param $updates array of the updated fields
	 *@param $history store changes history ?
	 * 
	**/
	function post_updateItem($input,$updates,$history=1) {
		global $CACHE_CFG;
		if (count($updates)){
			cleanCache(); 
		}
	}

	/**
	 * Print the config form for common options
	 *
	 *@param $target filename : where to go when done.
	 * 
	 *@return Nothing (display) 
	 * 
	**/	
	function showFormMain($target) {
	
		global $DB, $LANG, $CFG_GLPI;
	
		if (!haveRight("config", "w"))
			return false;
		
		echo "<form name='form' action=\"$target\" method=\"post\">";
		echo "<div class='center' id='tabsbody'>";
		echo "<input type='hidden' name='ID' value='" . $CFG_GLPI["ID"] . "'>";

		echo "<table class='tab_cadre_fixe'>";
		echo "<tr><th colspan='4'>" . $LANG["setup"][70] . "</th></tr>";
	
		

	
		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["setup"][102] . " </td><td><select name=\"event_loglevel\">";
		$level = $CFG_GLPI["event_loglevel"];
		echo "<option value=\"1\"";
		if ($level == 1) {
			echo " selected";
		}
		echo ">" . $LANG["setup"][103] . " </option>";
		echo "<option value=\"2\"";
		if ($level == 2) {
			echo " selected";
		}
		echo ">" . $LANG["setup"][104] . "</option>";
		echo "<option value=\"3\"";
		if ($level == 3) {
			echo " selected";
		}
		echo ">" . $LANG["setup"][105] . "</option>";
		echo "<option value=\"4\"";
		if ($level == 4) {
			echo " selected";
		}
		echo ">" . $LANG["setup"][106] . " </option>";
		echo "<option value=\"5\"";
		if ($level == 5) {
			echo " selected";
		}
		echo ">" . $LANG["setup"][107] . "</option>";
		echo "</select></td>";
	
		echo "<td class='center'>" . $LANG["setup"][109] . " </td><td>";
		dropdownInteger('expire_events', $CFG_GLPI["expire_events"], 0, 365,10);
		echo "</td></tr>";
	
		echo "<tr class='tab_bg_2'>";
		echo "<td class='center'> " . $LANG["setup"][186] . " </td><td>";
		dropdownGMT("glpi_timezone", $CFG_GLPI["glpi_timezone"]);
		echo "</td>";								

	

		echo "<td class='center'> " . $LANG["setup"][185] . " </td><td>";
		dropdownYesNo("use_errorlog", $CFG_GLPI["use_errorlog"]);
		echo "</td></tr>";								



		echo "<tr class='tab_bg_2'>";

		echo "<td class='center'> " . $LANG["setup"][183] . " </td><td>";
		dropdownYesNo("use_cache", $CFG_GLPI["use_cache"]);
		echo " - ".getSize(filesizeDirectory(GLPI_CACHE_DIR));
		echo "</td><td class='center'>".$LANG["setup"][187]."</td><td>";
		dropdownInteger('cache_max_size',$CFG_GLPI["cache_max_size"],5,500,5);
		echo " MB";
		echo "</td></tr>";
							
		echo "<tr class='tab_bg_1'><td colspan='4' align='center'><strong>" . $LANG["Menu"][38] . "</strong></td></tr>";
	
		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["setup"][115] . "</td><td>";
		dropdownInteger('cartridges_alarm', $CFG_GLPI["cartridges_alarm"], -1, 100);
		echo "</td>";
	
		echo "<td class='center'>" . $LANG["setup"][221] . "</td><td>";
		showDateFormItem("date_fiscale",$CFG_GLPI["date_fiscale"],false);
		echo "</td></tr>";

		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["setup"][360] . "</td><td>";
		$tab=array(0=>$LANG["common"][59],1=>$LANG["entity"][8]);
		dropdownArrayValues('autoname_entity', $tab,$CFG_GLPI["autoname_entity"]);
		echo "</td>";
	
		echo "<td class='center'>&nbsp;</td><td>";
		echo "&nbsp;";
		echo "</td></tr>";
	
		echo "<tr class='tab_bg_1'><td colspan='4' align='center'><strong>" . $LANG["title"][24] . "</strong></td></tr>";
				
		echo "<tr class='tab_bg_2'><td class='center'> " . $LANG["setup"][116] . " </td><td>";
		dropdownYesNo("auto_assign", $CFG_GLPI["auto_assign"]);
		echo "</td>";

		echo "<td class='center'>" . $LANG["setup"][405] . "</td><td>";
		dropdownYesNo("followup_on_update_ticket", $CFG_GLPI["followup_on_update_ticket"]);
		echo "</td></tr>";
	
		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["tracking"][37] . "</td><td>";
		dropdownYesNo("keep_tracking_on_delete", $CFG_GLPI["keep_tracking_on_delete"]);
		echo "</td>";
		echo "<td class='center'>" . $LANG["setup"][409] . "</td><td>";
		dropdownValue("glpi_dropdown_rubdocs","default_rubdoc_tracking",$CFG_GLPI["default_rubdoc_tracking"]);
		echo "</td></tr>";

		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["setup"][608] . "</td><td>";
		dropdownYesNo("software_helpdesk_visible", $CFG_GLPI["software_helpdesk_visible"]);
		echo "</td>";
		echo "<td class='center' colspan='2'></td>";
		echo "</tr>";


	
		echo "<tr class='tab_bg_1'><td colspan='4' align='center'><strong>" . $LANG["common"][41] . "</strong></td></tr>";
	
		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["setup"][246] . " (" . $LANG["common"][44] . ")</td><td>";
		dropdownContractAlerting("contract_alerts", $CFG_GLPI["contract_alerts"]);
		echo "</td>";
	
		echo "<td class='center'>" . $LANG["setup"][247] . " (" . $LANG["common"][44] . ")</td><td>";
		echo "<select name=\"infocom_alerts\">";
		echo "<option value=\"0\" " . ($CFG_GLPI["infocom_alerts"] == 0 ? " selected " : "") . " >-----</option>";
		echo "<option value=\"" . pow(2, ALERT_END) . "\" " . ($CFG_GLPI["infocom_alerts"] == pow(2, ALERT_END) ? " selected " : "") . " >" . $LANG["financial"][80] . " </option>";
		echo "</select>";
		echo "</td></tr>";
	
		echo "<tr class='tab_bg_1'><td colspan='4' align='center'><strong>" . $LANG["setup"][306] . "</strong></td></tr>";
	
		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["setup"][306] . " </td><td><select name=\"auto_update_check\">";
		$check = $CFG_GLPI["auto_update_check"];
		echo "<option value=\"0\" " . ($check == 0 ? " selected" : "") . ">" . $LANG["setup"][307] . " </option>";
		echo "<option value=\"7\" " . ($check == 7 ? " selected" : "") . ">" . $LANG["setup"][308] . " </option>";
		echo "<option value=\"30\" " . ($check == 30 ? " selected" : "") . ">" . $LANG["setup"][309] . " </option>";
		echo "</select></td><td colspan='2'>&nbsp;</td></tr>";
	
		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["setup"][401] . " </td><td><input type=\"text\" name=\"proxy_name\" value=\"" . $CFG_GLPI["proxy_name"] . "\"></td>";
		echo "<td class='center'>" . $LANG["setup"][402] . " </td><td><input type=\"text\" name=\"proxy_port\" value=\"" . $CFG_GLPI["proxy_port"] . "\"></td></tr>";
	
		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["setup"][403] . " </td><td><input type=\"text\" name=\"proxy_user\" value=\"" . $CFG_GLPI["proxy_user"] . "\"></td>";
		echo "<td class='center'>" . $LANG["setup"][404] . " </td><td><input type=\"password\" name=\"proxy_password\" value=\"\"></td></tr>";

		echo "<tr class='tab_bg_1'><td colspan='4' align='center'><strong>" . $LANG["rulesengine"][77] . "</strong></td></tr>";
		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["rulesengine"][86] . " </td><td>";
		dropdownValue("glpi_dropdown_software_category","category_on_software_delete",$CFG_GLPI["category_on_software_delete"]);				
		echo "</td><td class='center' colspan='2'></td></tr>";
			
		echo "<tr class='tab_bg_2'><td colspan='4' align='center'><input type=\"submit\" name=\"update\" class=\"submit\" value=\"" . $LANG["buttons"][2] . "\" ></td></tr>";
		echo "</table></div>";
		echo "</form>";

	}


	/**
	 * Print the config form for display
	 *
	 *@param $target filename : where to go when done.
	 * 
	 *@return Nothing (display) 
	 * 
	**/	
	function showFormDisplay($target) {
	
		global $DB, $LANG, $CFG_GLPI;
	
		if (!haveRight("config", "w"))
			return false;
		
		echo "<form name='form' action=\"$target\" method=\"post\">";
		echo "<div class='center' id='tabsbody'>";
		echo "<input type='hidden' name='ID' value='" . $CFG_GLPI["ID"] . "'>";

		echo "<table class='tab_cadre_fixe'>";

		echo "<tr><th colspan='4'>" . $LANG["setup"][119] . "</th></tr>";
		
		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["setup"][149] . " </td><td>";
		dropdownInteger("decimal_number",$CFG_GLPI["decimal_number"],1,4);
		echo "</td>";
		
		echo "<td class='center'>" . $LANG["setup"][148] . "</td><td>";
		echo "<select name='time_step'>";
		$steps = array (
			5,
			10,
			15,
			20,
			30,
			60
		);
		foreach ($steps as $step) {
			echo "<option value='$step'" . ($CFG_GLPI["time_step"] == $step ? " selected " : "") . ">$step</option>";
		}
		echo "</select>&nbsp;" . $LANG["job"][22];
		echo "</td></tr>";
	
		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["setup"][112] . "</td><td>";
		dropdownInteger('cut', $CFG_GLPI["cut"], 50, 500,50);
		//<input size='10' type=\"text\" name=\"cut\" value=\"" . $CFG_GLPI["cut"] . "\">
		echo "</td>";
		
		$plan_begin = explode(":", $CFG_GLPI["planning_begin"]);
		$plan_end = explode(":", $CFG_GLPI["planning_end"]);
		echo "<td class='center'>" . $LANG["setup"][223] . "</td><td>";
		dropdownInteger('planning_begin', $plan_begin[0], 0, 24);
		echo "&nbsp;->&nbsp;";
		dropdownInteger('planning_end', $plan_end[0], 0, 24);
		echo " </td></tr>";
		

		echo "<tr class='tab_bg_2'>";
		echo "<td class='center'>" . $LANG["setup"][111]." <br> ".$LANG["common"][58]."</td><td>";
		dropdownInteger("list_limit_max",$CFG_GLPI["list_limit_max"],5,200,5);
		
		echo "</td><td class='center'>&nbsp;</td><td>&nbsp;";
		echo "</td></tr>";
		
		echo "<tr class='tab_bg_1'><td colspan='4' align='center'><strong>" . $LANG["setup"][6] . "</strong></td></tr>";

		echo "<tr class='tab_bg_2'><td class='center'> " . $LANG["setup"][118] . " </td><td colspan='3' align='center'>";
		echo "<textarea cols='70' rows='4' name='text_login' >";
		echo $CFG_GLPI["text_login"];
		echo "</textarea>";
		echo "</td></tr>";
		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["setup"][407] . "</td><td> <input size='30' type=\"text\" name=\"helpdeskhelp_url\" value=\"" . $CFG_GLPI["helpdeskhelp_url"] . "\"></td>";
		echo "<td class='center'>" . $LANG["setup"][408] . "</td><td> <input size='30' type=\"text\" name=\"centralhelp_url\" value=\"" . $CFG_GLPI["centralhelp_url"] . "\"></td></tr>";

		echo "<tr class='tab_bg_1'><td colspan='4' align='center'><strong>" . $LANG["setup"][147] . "</strong></td></tr>";
		
		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["setup"][120] . " </td><td>";
		dropdownYesNo("use_ajax", $CFG_GLPI["use_ajax"]);
		echo "</td>";
		
		echo "<td class='center'>" . $LANG["setup"][127] . " </td><td>";
		dropdownYesNo("ajax_autocompletion", $CFG_GLPI["ajax_autocompletion"]);
		echo "</td></tr>";
		
		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["setup"][121] . "</td><td><input type=\"text\" size='1' name=\"ajax_wildcard\" value=\"" . $CFG_GLPI["ajax_wildcard"] . "\"></td>";
		
		echo "<td class='center'>" . $LANG["setup"][122] . "</td><td>";
		dropdownInteger('dropdown_max', $CFG_GLPI["dropdown_max"], 0, 200);
		echo "</td></tr>";
		
		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["setup"][123] . "</td><td>";
		dropdownInteger('ajax_limit_count', $CFG_GLPI["ajax_limit_count"], 0, 200);
		echo "</td><td colspan='2'>&nbsp;</td></tr>";


		echo "<tr class='tab_bg_2'><td colspan='4' align='center'><input type=\"submit\" name=\"update\" class=\"submit\" value=\"" . $LANG["buttons"][2] . "\" ></td></tr>";
		echo "</table></div>";
		echo "</form>";

	}

	/**
	 * Print the config form for restrictions
	 *
	 *@param $target filename : where to go when done.
	 * 
	 *@return Nothing (display) 
	 * 
	**/	
	function showFormRestrict($target) {
	
		global $DB, $LANG, $CFG_GLPI;
	
		if (!haveRight("config", "w"))
			return false;
		
		echo "<form name='form' action=\"$target\" method=\"post\">";
		echo "<div class='center' id='tabsbody'>";
		echo "<input type='hidden' name='ID' value='" . $CFG_GLPI["ID"] . "'>";

		echo "<table class='tab_cadre_fixe'>";
								
		echo "<tr><th colspan='4'>" . $LANG["setup"][270] . "</th></tr>";
	
		echo "<tr class='tab_bg_2'>";
		echo "<td class='center'> " . $LANG["setup"][271] . " </td><td>";	adminManagementDropdown("monitors_management_restrict",$CFG_GLPI["monitors_management_restrict"]);	
		echo "</td><td class='center'> " . $LANG["setup"][272] . " </td><td>";		
		adminManagementDropdown("peripherals_management_restrict",$CFG_GLPI["peripherals_management_restrict"]);				
		echo "</td></tr>";
		
		echo "<tr class='tab_bg_2'>";
		echo "<td class='center'> " . $LANG["setup"][273] . " </td><td>";
		adminManagementDropdown("phones_management_restrict",$CFG_GLPI["phones_management_restrict"]);
		echo "</td><td class='center'> " . $LANG["setup"][275] . " </td><td>";		
		adminManagementDropdown("printers_management_restrict",$CFG_GLPI["printers_management_restrict"]);
		echo "</td></tr>";

		echo "<tr class='tab_bg_2'>";
		echo "<td class='center'> " . $LANG["setup"][276] . " </td><td>";
		adminManagementDropdown("licenses_management_restrict",$CFG_GLPI["licenses_management_restrict"],1);				
		echo "</td><td class='center'>".$LANG["setup"][277]."</td><td>";
		dropdownYesNo("license_deglobalisation",$CFG_GLPI["license_deglobalisation"]);
		echo"</td></tr>";

		echo "<tr><th colspan='2'>" . $LANG["setup"][134]. "</th><th colspan='2'></th></tr>";

		echo "<tr class='tab_bg_2'><td class='center'> " . $LANG["setup"][133] . " </td><td>";
		dropdownYesNo("ocs_mode", $CFG_GLPI["ocs_mode"]);
		echo "</td><td class='center'colspan='2'></tr>";

		echo "<tr><th colspan='2'>" . $LANG["login"][10] . "</th><th colspan='2'>".$LANG["Menu"][20]."</th></tr>";
		echo "<tr class='tab_bg_2'><td class='center'> " . $LANG["setup"][124] . " </td><td>";
		dropdownYesNo("auto_add_users", $CFG_GLPI["auto_add_users"]);
		echo "</td>";
		
		echo "<td class='center'> " . $LANG["setup"][117] . " </td><td>";
		dropdownYesNo("public_faq", $CFG_GLPI["public_faq"]);
		echo " </td></tr>";

		echo "<tr><th colspan='4' align='center'>" . $LANG["Menu"][31]. "</th></tr>";

		echo "<tr class='tab_bg_2'><td class='center'> " . $LANG["setup"][219] . " </td><td>";
		dropdownYesNo("permit_helpdesk", $CFG_GLPI["permit_helpdesk"]);
		echo "</td><td class='center'>" . $LANG["setup"][610] . "</td><td>";
		dropdownYesNo("ticket_title_mandatory", $CFG_GLPI["ticket_title_mandatory"]);
		echo "</td></tr>";

		echo "<tr class='tab_bg_2'><td class='center'> " . $LANG["setup"][611] . " </td><td>";
		dropdownYesNo("ticket_content_mandatory", $CFG_GLPI["ticket_content_mandatory"]);
		echo "</td><td class='center'>" . $LANG["setup"][612] . "</td><td>";
		dropdownYesNo("ticket_category_mandatory", $CFG_GLPI["ticket_category_mandatory"]);
		echo "</td></tr>";
		echo "<tr class='tab_bg_2'><td class='center'> " . $LANG["mailgate"][7] . " </td><td>";
		echo "<input type=\"text\" size='15' name=\"mailgate_filesize_max\" value=\"" . $CFG_GLPI["mailgate_filesize_max"] . "\">&nbsp;".$LANG["mailgate"][8]." - ".getSize($CFG_GLPI["mailgate_filesize_max"]);
		echo "</td><td class='center' colspan='2'>&nbsp;";
		echo "</td></tr>";

		echo "<tr class='tab_bg_2'><td colspan='4' align='center'><input type=\"submit\" name=\"update\" class=\"submit\" value=\"" . $LANG["buttons"][2] . "\" ></td></tr>";
		echo "</table></div>";
		echo "</form>";

	}

	/**
	 * Print the config form for connections
	 *
	 *@param $target filename : where to go when done.
	 * 
	 *@return Nothing (display) 
	 * 
	**/	
	function showFormConnection($target) {
	
		global $DB, $LANG, $CFG_GLPI;
	
		if (!haveRight("config", "w"))
			return false;
		
		echo "<form name='form' action=\"$target\" method=\"post\">";
		echo "<div class='center' id='tabsbody'>";
		echo "<input type='hidden' name='ID' value='" . $CFG_GLPI["ID"] . "'>";

		echo "<table class='tab_cadre_fixe'>";
								
		echo "<tr><th colspan='4'>" . $LANG["setup"][280]. " (" . $LANG["peripherals"][32] . ")</th></tr>";

		echo "<tr><th>&nbsp;</th><th>" . $LANG["setup"][281] . "</th><th>" . $LANG["setup"][282] . "</th><th>&nbsp;</th></tr>";

		echo "<tr class='tab_bg_2'><td class='center'> " . $LANG["common"][18] . " </td><td>" . $LANG["setup"][283] . ":&nbsp;";
		dropdownYesNo("autoupdate_link_contact", $CFG_GLPI["autoupdate_link_contact"]);
		echo "</td><td>" . $LANG["setup"][284] . ":&nbsp;";
		dropdownYesNo("autoclean_link_contact", $CFG_GLPI["autoclean_link_contact"]);
		echo "</td><td>&nbsp;</td></tr>";
		
		echo "<tr class='tab_bg_2'><td class='center'> " . $LANG["common"][34] . " </td><td>" . $LANG["setup"][283] . ":&nbsp;";
		dropdownYesNo("autoupdate_link_user", $CFG_GLPI["autoupdate_link_user"]);
		echo "</td><td>" . $LANG["setup"][284] . ":&nbsp;";
		dropdownYesNo("autoclean_link_user", $CFG_GLPI["autoclean_link_user"]);
		echo " </td><td>&nbsp;</td></tr>";

		echo "<tr class='tab_bg_2'><td class='center'> " . $LANG["common"][35] . " </td><td>" . $LANG["setup"][283] . ":&nbsp;";
		dropdownYesNo("autoupdate_link_group", $CFG_GLPI["autoupdate_link_group"]);
		echo "</td><td>" . $LANG["setup"][284] . ":&nbsp;";
		dropdownYesNo("autoclean_link_group", $CFG_GLPI["autoclean_link_group"]);
		echo "</td><td>&nbsp;</td></tr>";
		
		echo "<tr class='tab_bg_2'><td class='center'> " . $LANG["common"][15] . " </td><td>" . $LANG["setup"][283] . ":&nbsp;";
		dropdownYesNo("autoupdate_link_location", $CFG_GLPI["autoupdate_link_location"]);
		echo "</td><td>" . $LANG["setup"][284] . ":&nbsp;";
		dropdownYesNo("autoclean_link_location", $CFG_GLPI["autoclean_link_location"]);
		echo " </td><td>&nbsp;</td></tr>";
															
		echo "<tr class='tab_bg_2'><td class='center'> " . $LANG["state"][0] . " </td><td>";
		dropdownStateBehaviour("autoupdate_link_state", $LANG["setup"][197], $CFG_GLPI["autoupdate_link_state"]);
		echo "</td><td>";
		dropdownStateBehaviour("autoclean_link_state", $LANG["setup"][196], $CFG_GLPI["autoclean_link_state"]);
		echo " </td><td>&nbsp;</td></tr>";

		echo "<tr class='tab_bg_2'><td colspan='4' align='center'><input type=\"submit\" name=\"update\" class=\"submit\" value=\"" . $LANG["buttons"][2] . "\" ></td></tr>";
		echo "</table></div>";
		echo "</form>";

	}

	/**
	 * Print the config form for slave DB
	 *
	 *@param $target filename : where to go when done.
	 * 
	 *@return Nothing (display) 
	 * 
	**/	
	function showFormDBSlave($target) {
	
		global $DB, $LANG, $CFG_GLPI, $DBSlave;
	
		if (!haveRight("config", "w"))
			return false;
		
		echo "<form name='form' action=\"$target\" method=\"post\">";
		echo "<div class='center' id='tabsbody'>";
		echo "<input type='hidden' name='ID' value='" . $CFG_GLPI["ID"] . "'>";

		echo "<table class='tab_cadre_fixe'>";
		$active = isDBSlaveActive();
	
		echo "<tr class='tab_bg_2'><th colspan='4'>" . $LANG["setup"][800] . "</th></tr>";

		echo "<tr class='tab_bg_2'><td class='center'> " . $LANG["setup"][801] . " </td><td>";
		dropdownYesNo("_dbslave_status", $active);
		echo " </td><td  colspan='2'></td></tr>";

		if ($active){
			
			$DBSlave = getDBSlaveConf();

			echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["install"][30] . " </td><td><input type=\"text\" name=\"_dbreplicate_dbhost\" size='40' value=\"" . $DBSlave->dbhost . "\"></td>";
			echo "<td class='center'>" . $LANG["setup"][802] . "</td><td>";
			echo "<input type=\"text\" name=\"_dbreplicate_dbdefault\" value=\"" . $DBSlave->dbdefault . "\">";
			echo "</td></tr>";

			echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["install"][31] . "</td><td>";
			echo "<input type=\"text\" name=\"_dbreplicate_dbuser\" value=\"" . $DBSlave->dbuser . "\">";
			echo "<td class='center'>" . $LANG["install"][32] . "</td><td>";
			echo "<input type=\"password\" name=\"_dbreplicate_dbpassword\" value=\"" . $DBSlave->dbpassword . "\">";
			echo "</td></tr>";

			echo "<tr class='tab_bg_2'><th colspan='4'>" . $LANG["setup"][704] . "</th></tr>";

			echo "<tr class='tab_bg_2'><td class='center'> " . $LANG["setup"][804] . " </td><td>";
			dropdownYesNo("dbreplicate_notify_desynchronization", $CFG_GLPI["dbreplicate_notify_desynchronization"]);
			echo " </td>";

			echo "<td class='center'> " . $LANG["setup"][806] . " </td><td>";
			echo "<input type=\"text\" name=\"dbreplicate_maxdelay\" size='8' value=\"" . $CFG_GLPI["dbreplicate_maxdelay"] . "\">";
			echo "&nbsp;" . $LANG["stats"][34]."</td></tr>";

			echo "<tr class='tab_bg_2'><td class='center'> " . $LANG["setup"][203] . " </td><td colspan='3'>";
			echo "<input type=\"text\" size='50' name=\"dbreplicate_email\" value=\"" . $CFG_GLPI["dbreplicate_email"] . "\">";
			echo "</td></tr>";


			echo "<tr class='tab_bg_2'>";			
			if ($DBSlave->connected && !$DB->isSlave()) {
				echo "<td colspan='4' align='center'>" . $LANG["setup"][803] . " : ";
				echo timestampToString(getReplicateDelay(),1);
				echo "</td>";
			} else
				echo "<td colspan='4'></td>";

			echo "</tr>";

		}

		echo "<tr class='tab_bg_2'><td colspan='4' align='center'><input type=\"submit\" name=\"update\" class=\"submit\" value=\"" . $LANG["buttons"][2] . "\" ></td></tr>";
		echo "</table></div>";
		echo "</form>";

	}

	/**
	 * Print the config form for default user prefs
	 *
	 *@param $target filename : where to go when done.
	 *@param $data array containing datas (CFG_GLPI for global config / glpi_users fields for user prefs)
	 * 
	 *@return Nothing (display) 
	 * 
	**/	
	function showFormUserPrefs($target,$data=array()) {
	
		global $DB, $LANG;

		$oncentral=($_SESSION["glpiactiveprofile"]["interface"]=="central");
			
		echo "<form name='form' action=\"$target\" method=\"post\">";
		echo "<div class='center' id='tabsbody'>";
		echo "<input type='hidden' name='ID' value='" . $data["ID"] . "'>";

		echo "<table class='tab_cadre_fixe'>";

		echo "<tr><th colspan='4'>" . $LANG["setup"][119] . "</th></tr>";
	
		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["setup"][128] . " </td><td><select name=\"dateformat\">";

		$dateformats=array(
			0 => "YYYY-MM-DD",
			1 => "DD-MM-YYYY",
			2 => "MM-DD-YYYY"
		);
		foreach ($dateformats as $key => $val){
			echo "<option value=\"$key\"";
			if ($data["dateformat"] == $key) {
				echo " selected";
			}
			echo ">$val</option>";
		}
		echo "</select></td>";
		
		echo "<td class='center'>" . $LANG["setup"][150] . " </td><td><select name=\"numberformat\">";
		echo "<option value=\"0\"";
		if ($data["numberformat"] == 0) {
			echo " selected";
		}
		echo ">1 234.56</option>";
		echo "<option value=\"1\"";
		if ($data["numberformat"] == 1) {
			echo " selected";
		}
		echo ">1,234.56</option>";
		echo "<option value=\"2\"";
		if ($data["numberformat"] == 2) {
			echo " selected";
		}
		echo ">1 234,56</option>";
		echo "</select></td></tr>";

		if ($oncentral){			
			echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["setup"][129] . " </td><td>";
			dropdownYesNo("view_ID", $data["view_ID"]);
			echo "</td>";
			
			echo "<td class='center'>" . $LANG["setup"][131] . "</td><td>";
			dropdownInteger('dropdown_limit', $data["dropdown_limit"], 20, 100);
			echo "</td></tr>";

			echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["setup"][132] . "</td><td>";
			dropdownYesNo('flat_dropdowntree', $data["flat_dropdowntree"]);
			echo "</td><td class='center'>&nbsp;</td><td>&nbsp;";
			echo "</td></tr>";

		}
	
		if ($oncentral){

			$nextprev_item = $data["nextprev_item"];
			echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["setup"][130] . " </td><td><select name=\"nextprev_item\">";
			echo "<option value=\"ID\"";
			if ($nextprev_item == "ID") {
				echo " selected";
			}
			echo ">" . $LANG["common"][2] . " </option>";
			echo "<option value=\"name\"";
			if ($nextprev_item == "name") {
				echo " selected";
			}
			echo ">" . $LANG["common"][16] . "</option>";
			echo "</select></td>";
	
			echo "<td class='center'>" . $LANG["setup"][108] . "</td><td>";
			dropdownInteger('num_of_events', $data["num_of_events"], 5, 100,5);
			echo "</td></tr>";
		}



	
		echo "<tr class='tab_bg_2'>";
		echo "<td class='center'>" . $LANG["setup"][111]."</td><td>";
		dropdownInteger("list_limit",$data["list_limit"],5,200,5);
		
		echo "</td>";
		echo "<td class='center'>" . $LANG["setup"][113] . " </td><td>";
		dropdownLanguages("language", $data["language"]);

		echo "</td></tr>";		

		echo "<tr class='tab_bg_1'><td colspan='4' align='center'><strong>" . $LANG["title"][24] . "</strong></td></tr>";
	
		if ($oncentral){

			echo "<tr class='tab_bg_2'><td class='center'> " . $LANG["setup"][110] . " </td><td>";
			dropdownYesNo("jobs_at_login", $data["jobs_at_login"]);
			echo " </td><td>" . $LANG["setup"][40] . "</td><td>";
			dropdownYesNo("tracking_order", $data["tracking_order"]);
			echo "</td></tr>";
		} else {
			echo "<tr class='tab_bg_2'><td class='center'> " . $LANG["setup"][40] . " </td><td>";
			dropdownYesNo("tracking_order", $data["tracking_order"]);
			echo "</td><td colspan='2'>&nbsp;</td></tr>";


		}

		if ($oncentral){
			echo "<tr class='tab_bg_2'><td class='center'> " . $LANG["setup"][39] . " </td><td>";
			dropdownYesNo("followup_private", $data["followup_private"]);
			echo "</td><td colspan='2'>&nbsp;</td></tr>";
	
			echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["setup"][114] . "</td><td colspan='3'>";
			echo "<table><tr>";
			echo "<td bgcolor='" . $data["priority_1"] . "'>1:<input type=\"text\" name=\"priority_1\" size='7' value=\"" . $data["priority_1"] . "\"></td>";
			echo "<td bgcolor='" . $data["priority_2"] . "'>2:<input type=\"text\" name=\"priority_2\" size='7' value=\"" . $data["priority_2"] . "\"></td>";
			echo "<td bgcolor='" . $data["priority_3"] . "'>3:<input type=\"text\" name=\"priority_3\" size='7' value=\"" . $data["priority_3"] . "\"></td>";
			echo "<td bgcolor='" . $data["priority_4"] . "'>4:<input type=\"text\" name=\"priority_4\" size='7' value=\"" . $data["priority_4"] . "\"></td>";
			echo "<td bgcolor='" . $data["priority_5"] . "'>5:<input type=\"text\" name=\"priority_5\" size='7' value=\"" . $data["priority_5"] . "\"></td>";
			echo "</tr></table>";
			echo "</td></tr>";
	
			echo "<tr class='tab_bg_1'><td colspan='4' align='center'><strong>" . $LANG["softwarecategories"][5] . "</strong></td></tr>";
			echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["softwarecategories"][4]."</td><td>";
			$expand[0]=$LANG["softwarecategories"][1];
			$expand[1]=$LANG["softwarecategories"][2];
			dropdownArrayValues("expand_soft_categorized",$expand,$data["expand_soft_categorized"]);
			
			echo "</td><td class='center'>" . $LANG["softwarecategories"][3] . "</td><td>";
			dropdownArrayValues("expand_soft_not_categorized",$expand,$data["expand_soft_not_categorized"]);
	
			echo "</td></tr>";
		}


		echo "<tr class='tab_bg_2'><td colspan='4' align='center'><input type=\"submit\" name=\"update\" class=\"submit\" value=\"" . $LANG["buttons"][2] . "\" ></td></tr>";
		echo "</table></div>";
		echo "</form>";

	}


	/**
	 * Print the mailing config form
	 *
	 *@param $target filename : where to go when done. 
	 * 
	 *@return Nothing (display) 
	 * 
	**/
	function showFormMailing($target,$tabs) {
	
		global $DB, $LANG, $CFG_GLPI;
	
		if (!haveRight("config", "w"))
			return false;
	
		echo "<form action=\"$target\" method=\"post\">";
		echo "<input type='hidden' name='ID' value='" . $CFG_GLPI["ID"] . "'>";

		switch ($tabs){
			case 1:
			echo "<div class='center'><table class='tab_cadre_fixe'><tr><th colspan='2'>" . $LANG["setup"][201] . "</th></tr>";
	
			echo "<tr class='tab_bg_2'><td >" . $LANG["setup"][202] . "</td><td>";
			dropdownYesNo("mailing", $CFG_GLPI["mailing"]);
			echo "</td></tr>";
	
			echo "<tr class='tab_bg_2'><td >" . $LANG["setup"][203] . "</td><td> <input type=\"text\" name=\"admin_email\" size='40' value=\"" . $CFG_GLPI["admin_email"] . "\">";
			if (!isValidEmail($CFG_GLPI["admin_email"])){
				echo "<span class='red'>&nbsp;".$LANG["mailing"][110]."</span>";
			}
			echo " </td></tr>";

			echo "<tr class='tab_bg_2'><td >" . $LANG["setup"][207] . "</td><td> <input type=\"text\" name=\"admin_reply\" size='40' value=\"" . $CFG_GLPI["admin_reply"] . "\">";
			if (!isValidEmail($CFG_GLPI["admin_reply"])){
				echo "<span class='red'>&nbsp;".$LANG["mailing"][110]."</span>";
			}
			echo " </td></tr>";
	
			echo "<tr class='tab_bg_2'><td >" . $LANG["setup"][204] . "</td><td><textarea   cols='60' rows='3'  name=\"mailing_signature\" >".$CFG_GLPI["mailing_signature"]."</textarea></td></tr>";
	
			echo "<tr class='tab_bg_2'><td >" . $LANG["setup"][226] . "</td><td>";
			dropdownYesNo("url_in_mail", $CFG_GLPI["url_in_mail"]);
			echo "</td></tr>";
	
			echo "<tr class='tab_bg_2'><td >" . $LANG["setup"][227] . "</td><td> <input type=\"text\" name=\"url_base\" size='40' value=\"" . $CFG_GLPI["url_base"] . "\"> </td></tr>";
	
			if (!function_exists('mail')) {
				echo "<tr class='tab_bg_2'><td align='center' colspan='2'><span class='red'>" . $LANG["setup"][217] . " : </span><span>" . $LANG["setup"][218] . "</span></td></tr>";
			}
		
			echo "<tr class='tab_bg_2'><td >" . $LANG["setup"][245] . " " . $LANG["setup"][244] . "</td><td>";
			echo "<select name='cartridges_alert'> ";
			echo "<option value='0' " . ($CFG_GLPI["cartridges_alert"] == 0 ? "selected" : "") . " >" . $LANG["setup"][307] . "</option>";
			echo "<option value='" . DAY_TIMESTAMP . "' " . ($CFG_GLPI["cartridges_alert"] == DAY_TIMESTAMP ? "selected" : "") . " >" . $LANG["setup"][305] . "</option>";
			echo "<option value='" . WEEK_TIMESTAMP . "' " . ($CFG_GLPI["cartridges_alert"] == WEEK_TIMESTAMP ? "selected" : "") . " >" . $LANG["setup"][308] . "</option>";
			echo "<option value='" . MONTH_TIMESTAMP . "' " . ($CFG_GLPI["cartridges_alert"] == MONTH_TIMESTAMP ? "selected" : "") . " >" . $LANG["setup"][309] . "</option>";
			echo "</select>";
			echo "</td></tr>";
	
			echo "<tr class='tab_bg_2'><td >" . $LANG["setup"][245] . " " . $LANG["setup"][243] . "</td><td>";
			echo "<select name='consumables_alert'> ";
			echo "<option value='0' " . ($CFG_GLPI["consumables_alert"] == 0 ? "selected" : "") . " >" . $LANG["setup"][307] . "</option>";
			echo "<option value='" . DAY_TIMESTAMP . "' " . ($CFG_GLPI["cartridges_alert"] == DAY_TIMESTAMP ? "selected" : "") . " >" . $LANG["setup"][305] . "</option>";
			echo "<option value='" . WEEK_TIMESTAMP . "' " . ($CFG_GLPI["consumables_alert"] == WEEK_TIMESTAMP ? "selected" : "") . " >" . $LANG["setup"][308] . "</option>";
			echo "<option value='" . MONTH_TIMESTAMP . "' " . ($CFG_GLPI["consumables_alert"] == MONTH_TIMESTAMP ? "selected" : "") . " >" . $LANG["setup"][309] . "</option>";
			echo "</select>";
			echo "</td></tr>";

			echo "<tr class='tab_bg_2'><td >" . $LANG["setup"][264] . "</td><td>";
			dropdownYesNo("licenses_alert", $CFG_GLPI["licenses_alert"]);
			echo "</td></tr>";

			echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";

			echo "<tr class='tab_bg_2'><td >" . $LANG["setup"][231] . "</td><td>&nbsp; ";


			$mail_methods=array(MAIL_MAIL=>$LANG["setup"][650],
					MAIL_SMTP=>$LANG["setup"][651],
					MAIL_SMTPSSL=>$LANG["setup"][652],
					MAIL_SMTPTLS=>$LANG["setup"][653]);
	
			dropdownArrayValues("smtp_mode",$mail_methods,$CFG_GLPI["smtp_mode"]);

			echo "</td></tr>";
	
			echo "<tr class='tab_bg_2'><td >" . $LANG["setup"][232] . "</td><td> <input type=\"text\" name=\"smtp_host\" size='40' value=\"" . $CFG_GLPI["smtp_host"] . "\"> </td></tr>";
		
			echo "<tr class='tab_bg_2'><td >" . $LANG["setup"][234] . "</td><td> <input type=\"text\" name=\"smtp_username\" size='40' value=\"" . $CFG_GLPI["smtp_username"] . "\"> </td></tr>";
	
			echo "<tr class='tab_bg_2'><td >" . $LANG["setup"][235] . "</td><td> <input type=\"password\" name=\"smtp_password\" size='40' value=\"\"> </td></tr>";

	
			echo "<tr class='tab_bg_2'><td align='center' colspan='2'>";
			echo "<input type=\"submit\" name=\"update_mailing\" class=\"submit\" value=\"" . $LANG["buttons"][2] . "\" >";
			echo "</td></tr>";
	
			echo "</table>";
			echo "</div>";
			echo "</form>";
			echo "<form action=\"$target\" method=\"post\">";
			echo "<div class='center'><table class='tab_cadre_fixe'><tr><th colspan='2'>" . $LANG["setup"][229] . "</th></tr>";
			echo "<tr class='tab_bg_2'>";
			echo "<td class='center'>";
			echo "<input class=\"submit\" type=\"submit\" name=\"test_smtp_send\" value=\"" . $LANG["buttons"][2] . "\">";
			echo " </td></tr></table></div>";
		break;
		case 2:
			$profiles[USER_MAILING_TYPE . "_" . ADMIN_MAILING] = $LANG["setup"][237];
			$profiles[USER_MAILING_TYPE . "_" . ADMIN_ENTITY_MAILING] = $LANG["setup"][237]." ".$LANG["entity"][0];
			$profiles[USER_MAILING_TYPE . "_" . TECH_MAILING] = $LANG["common"][10];
			$profiles[USER_MAILING_TYPE . "_" . AUTHOR_MAILING] = $LANG["job"][4];
			$profiles[USER_MAILING_TYPE . "_" . RECIPIENT_MAILING] = $LANG["job"][3];
			$profiles[USER_MAILING_TYPE . "_" . USER_MAILING] = $LANG["common"][34] . " " . $LANG["common"][1];
			$profiles[USER_MAILING_TYPE . "_" . ASSIGN_MAILING] = $LANG["setup"][239];
			$profiles[USER_MAILING_TYPE . "_" . ASSIGN_ENT_MAILING] = $LANG["financial"][26];
			$profiles[USER_MAILING_TYPE . "_" . ASSIGN_GROUP_MAILING] = $LANG["setup"][248];
			$profiles[USER_MAILING_TYPE . "_" . SUPERVISOR_ASSIGN_GROUP_MAILING] = $LANG["common"][64]." ".$LANG["setup"][248];
			$profiles[USER_MAILING_TYPE . "_" . SUPERVISOR_AUTHOR_GROUP_MAILING] = $LANG["common"][64]." ".$LANG["setup"][249];
				
				
			asort($profiles);

			$query = "SELECT ID, name FROM glpi_profiles order by name";
			$result = $DB->query($query);
			while ($data = $DB->fetch_assoc($result)){
				$profiles[PROFILE_MAILING_TYPE ."_" . $data["ID"]] = $LANG["profiles"][22] . " " . $data["name"];
			}

			$query = "SELECT ID, name FROM glpi_groups order by name";
			$result = $DB->query($query);
			while ($data = $DB->fetch_assoc($result)){
				$profiles[GROUP_MAILING_TYPE ."_" . $data["ID"]] = $LANG["common"][35] . " " . $data["name"];
			}
	
			echo "<div class='center'>";
			echo "<input type='hidden' name='update_notifications' value='1'>";
			// ADMIN
			echo "<table class='tab_cadre_fixe'>";
			echo "<tr><th colspan='3'>" . $LANG["setup"][211] . "</th></tr>";
			echo "<tr class='tab_bg_2'>";
			showFormMailingType("new", $profiles);
			echo "</tr>";
			echo "<tr><th colspan='3'>" . $LANG["setup"][212] . "</th></tr>";
			echo "<tr class='tab_bg_1'>";
			showFormMailingType("followup", $profiles);
			echo "</tr>";
			echo "<tr class='tab_bg_2'><th colspan='3'>" . $LANG["setup"][213] . "</th></tr>";
			echo "<tr class='tab_bg_2'>";
			showFormMailingType("finish", $profiles);
			echo "</tr>";
			echo "<tr class='tab_bg_2'><th colspan='3'>" . $LANG["setup"][230] . "</th></tr>";
			echo "<tr class='tab_bg_1'>";
			$profiles[USER_MAILING_TYPE . "_" . OLD_ASSIGN_MAILING] = $LANG["setup"][236];
			ksort($profiles);
			showFormMailingType("update", $profiles);
			unset ($profiles[USER_MAILING_TYPE . "_" . OLD_ASSIGN_MAILING]);
			echo "</tr>";
	
			echo "<tr class='tab_bg_2'><th colspan='3'>" . $LANG["setup"][225] . "</th></tr>";
			echo "<tr class='tab_bg_2'>";
			unset ($profiles[USER_MAILING_TYPE . "_" . ASSIGN_MAILING]);
			unset ($profiles[USER_MAILING_TYPE . "_" . ASSIGN_ENT_MAILING]);
			unset ($profiles[USER_MAILING_TYPE . "_" . ASSIGN_GROUP_MAILING]);
			unset ($profiles[USER_MAILING_TYPE . "_" . SUPERVISOR_ASSIGN_GROUP_MAILING]);
			unset ($profiles[USER_MAILING_TYPE . "_" . SUPERVISOR_AUTHOR_GROUP_MAILING]);
			unset ($profiles[USER_MAILING_TYPE . "_" . RECIPIENT_MAILING]);

			showFormMailingType("resa", $profiles);
			echo "</tr>";
	
			echo "</table>";
			echo "</div>";
		break;
		case 3:
			$profiles[USER_MAILING_TYPE . "_" . ADMIN_MAILING] = $LANG["setup"][237];
			$profiles[USER_MAILING_TYPE . "_" . ADMIN_ENTITY_MAILING] = $LANG["setup"][237]." ".$LANG["entity"][0];
			$query = "SELECT ID, name FROM glpi_profiles order by name";
			$result = $DB->query($query);
			while ($data = $DB->fetch_assoc($result)){
				$profiles[PROFILE_MAILING_TYPE ."_" . $data["ID"]] = $LANG["profiles"][22] . " " . $data["name"];
			}
	
			$query = "SELECT ID, name FROM glpi_groups order by name";
			$result = $DB->query($query);
			while ($data = $DB->fetch_assoc($result)){
				$profiles[GROUP_MAILING_TYPE ."_" . $data["ID"]] = $LANG["common"][35] . " " . $data["name"];
			}
	
			ksort($profiles);
			echo "<div class='center'>";
			echo "<input type='hidden' name='update_notifications' value='1'>";
			// ADMIN
			echo "<table class='tab_cadre_fixe'>";
			echo "<tr><th colspan='3'>" . $LANG["setup"][243]."&nbsp;&nbsp;";
			echo "<input class=\"submit\" type=\"submit\" name=\"test_cron_consumables\" value=\"" . $LANG["buttons"][50] . "\">";
			echo "</th></tr>";
			echo "<tr class='tab_bg_2'>";
			showFormMailingType("alertconsumable", $profiles);
			echo "</tr>";

			echo "<tr><th colspan='3'>" . $LANG["setup"][244]."&nbsp;&nbsp;";
			echo "<input class=\"submit\" type=\"submit\" name=\"test_cron_cartridges\" value=\"" . $LANG["buttons"][50] . "\">";
			echo "</th></tr>";
			echo "<tr class='tab_bg_1'>";
			showFormMailingType("alertcartridge", $profiles);
			echo "</tr>";
			echo "<tr><th colspan='3'>" . $LANG["setup"][246]."&nbsp;&nbsp;";
			echo "<input class=\"submit\" type=\"submit\" name=\"test_cron_contracts\" value=\"" . $LANG["buttons"][50] . "\">";
			echo "</th></tr>";
			echo "<tr class='tab_bg_2'>";
			showFormMailingType("alertcontract", $profiles);
			echo "</tr>";
			echo "<tr><th colspan='3'>" . $LANG["setup"][247]."&nbsp;&nbsp;";
			echo "<input class=\"submit\" type=\"submit\" name=\"test_cron_infocoms\" value=\"" . $LANG["buttons"][50] . "\">";
			echo "</th></tr>";
			echo "<tr class='tab_bg_1'>";
			showFormMailingType("alertinfocom", $profiles);
			echo "</tr>";
			echo "<tr><th colspan='3'>" . $LANG["setup"][264]."&nbsp;&nbsp;";
			echo "<input class=\"submit\" type=\"submit\" name=\"test_cron_softwares\" value=\"" . $LANG["buttons"][50] . "\">";
			echo "</th></tr>";
			echo "<tr class='tab_bg_1'>";
			showFormMailingType("alertlicense", $profiles);
			echo "</tr>";

			echo "</table>";
			echo "</div>";
		break;
	
		}
		echo "</form>";
	
	}

}

/// OCS Config class
class ConfigOCS extends CommonDBTM {

	/**
	 * Constructor
	**/
	function __construct () {
		$this->table="glpi_ocs_config";
		$this->type=-1;
	}

	/**
	 * Prepare input datas for updating the item
	 *
	 *@param $input datas used to update the item 
	 * 
	 *@return the modified $input array
	 * 
	**/
	function prepareInputForUpdate($input) {
		if (isset($input["ocs_db_passwd"])&&!empty($input["ocs_db_passwd"])){
			$input["ocs_db_passwd"]=rawurlencode(stripslashes($input["ocs_db_passwd"]));
		} else {
			unset($input["ocs_db_passwd"]);
		}

		if (isset($input["import_ip"])){
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
		}

		return $input;
	}
	/**
	 * Actions done after the UPDATE of the item in the database
	 *
	 *@param $input datas used to update the item
	 *@param $updates array of the updated fields
	 *@param $history store changes history ? 
	 * 
	**/
	function post_updateItem($input,$updates,$history=1) {
		global $CACHE_CFG;
		if (count($updates)){
			$CACHE_CFG->remove("CFG_OCSGLPI_".$input["ID"],"GLPI_CFG",true);
		}
	}

}

?>
