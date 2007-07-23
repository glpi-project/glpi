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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}

// CLASSES Setup

class Config extends CommonDBTM {

	function Config () {
		$this->table="glpi_config";
		$this->type=-1;
	}

	function prepareInputForUpdate($input) {
		if (isset($input["planning_begin"]))
			$input["planning_begin"]=$input["planning_begin"].":00:00";
		if (isset($input["planning_end"]))
			$input["planning_end"]=$input["planning_end"].":00:00";
		return $input;
	}

	function post_updateItem($input,$updates,$history=1) {
		global $CACHE_CFG;
		if (count($updates)){
			cleanCache(); 
		}
	}
	
	function showForm($target) {
	
		global $DB, $LANG, $CFG_GLPI;
	
		if (!haveRight("config", "w"))
			return false;

		echo "<form name='form' action=\"$target\" method=\"post\">";
		echo "<input type='hidden' name='ID' value='" . $CFG_GLPI["ID"] . "'>";
	
		echo "<div id='barre_onglets'><ul id='onglet'>";
		echo "<li ";
		if ($_SESSION['glpi_configgen'] == 1) {
			echo "class='actif'";
		}
		echo "><a href='$target?onglet=1'>" . $LANG["setup"][70] . "</a></li>";
		echo "<li ";
		if ($_SESSION['glpi_configgen'] == 2) {
			echo "class='actif'";
		}
		echo "><a href='$target?onglet=2'>" . $LANG["setup"][119] . "</a></li>";

		echo "<li ";
		if ($_SESSION['glpi_configgen'] == 3) {
			echo "class='actif'";
		}
		echo "><a href='$target?onglet=3'>" . $LANG["setup"][184] . "</a></li>";

		echo "</ul></div>";

		switch ($_SESSION['glpi_configgen']){
			// MAIN CONFIG
			case 1 :

				echo "<div class='center'><table class='tab_cadre_fixe'>";
				echo "<tr><th colspan='4'>" . $LANG["setup"][70] . "</th></tr>";
			
				$default_language = $CFG_GLPI["default_language"];
				echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["setup"][113] . " </td><td>";
				dropdownLanguages("default_language", $CFG_GLPI["default_language"]);
			
				echo "</td>"; 
				
				echo "<td class='center'> " . $LANG["setup"][183] . " </td><td>";
				dropdownYesNo("use_cache", $CFG_GLPI["use_cache"]);
				echo "</td>";
				
			
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
			
				echo "<td class='center'>" . $LANG["setup"][109] . " </td><td><input type=\"text\" name=\"expire_events\" value=\"" . $CFG_GLPI["expire_events"] . "\"></td></tr>";
			
				echo "<tr class='tab_bg_2'>";
			
				echo "<td class='center'>" . $LANG["setup"][138] . " </td><td><select name=\"debug\">";
				echo "<option value=\"" . NORMAL_MODE . "\" " . ($CFG_GLPI["debug"] == NORMAL_MODE ? " selected " : "") . " >" . $LANG["setup"][135] . " </option>";
				echo "<option value=\"" . TRANSLATION_MODE . "\" " . ($CFG_GLPI["debug"] == TRANSLATION_MODE ? " selected " : "") . " >" . $LANG["setup"][136] . " </option>";
				echo "<option value=\"" . DEBUG_MODE . "\" " . ($CFG_GLPI["debug"] == DEBUG_MODE ? " selected " : "") . " >" . $LANG["setup"][137] . " </option>";
				echo "<option value=\"" . DEMO_MODE . "\" " . ($CFG_GLPI["debug"] == DEMO_MODE ? " selected " : "") . " >" . $LANG["setup"][141] . " </option>";
				echo "</select></td>";
	
				echo "<td class='center'> " . $LANG["setup"][185] . " </td><td>";
				dropdownYesNo("use_errorlog", $CFG_GLPI["use_errorlog"]);
				echo "</td></tr>";								
	
				echo "<tr class='tab_bg_2'>";
				echo "<td class='center'> " . $LANG["setup"][186] . " </td><td>";
				dropdownGMT("glpi_timezone", $CFG_GLPI["glpi_timezone"]);
				echo "</td><td colspan='2'></td></tr>";								
									
				echo "<tr class='tab_bg_1'><td colspan='4' align='center'><strong>" . $LANG["setup"][10] . "</strong></td></tr>";
			
				echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["setup"][115] . "</td><td>";
				dropdownInteger('cartridges_alarm', $CFG_GLPI["cartridges_alarm"], -1, 100);
				echo "</td>";
			
				echo "<td class='center'>" . $LANG["setup"][221] . "</td><td>";
				showCalendarForm("form", "date_fiscale", $CFG_GLPI["date_fiscale"], 0);
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
				echo "<td class='center'>" . $LANG["setup"][404] . " </td><td><input type=\"text\" name=\"proxy_password\" value=\"" . $CFG_GLPI["proxy_password"] . "\"></td></tr>";
			
				echo "<tr class='tab_bg_2'><td colspan='4' align='center'><input type=\"submit\" name=\"update\" class=\"submit\" value=\"" . $LANG["buttons"][2] . "\" ></td></tr>";
			
				echo "</table></div>";
				break;
			// DISPLAY CONFIG
			case 2 :
				$cfg = new Config();
				$cfg->getFromDB(1);
		
				// Needed for list_limit
				echo "<div class='center'><table class='tab_cadre_fixe'>";
				echo "<tr><th colspan='4'>" . $LANG["setup"][119] . "</th></tr>";
			
				echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["setup"][128] . " </td><td><select name=\"dateformat\">";
				echo "<option value=\"0\"";
				if ($CFG_GLPI["dateformat"] == 0) {
					echo " selected";
				}
				echo ">YYYY-MM-DD</option>";
				echo "<option value=\"1\"";
				if ($CFG_GLPI["dateformat"] == 1) {
					echo " selected";
				}
				echo ">DD-MM-YYYY</option>";
				echo "</select></td>";
				
				echo "<td class='center'>" . $LANG["setup"][130] . " </td><td><select name=\"nextprev_item\">";
				$nextprev_item = $CFG_GLPI["nextprev_item"];
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
				echo "</select></td></tr>";
				
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
			
				echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["setup"][129] . " </td><td>";
				dropdownYesNo("view_ID", $CFG_GLPI["view_ID"]);
				echo "</td>";
				
				$plan_begin = split(":", $CFG_GLPI["planning_begin"]);
				$plan_end = split(":", $CFG_GLPI["planning_end"]);
				echo "<td class='center'>" . $LANG["setup"][223] . "</td><td>";
				dropdownInteger('planning_begin', $plan_begin[0], 0, 24);
				echo "&nbsp;->&nbsp;";
				dropdownInteger('planning_end', $plan_end[0], 0, 24);
				echo " </td></tr>";
			
				echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["setup"][112] . "</td><td><input size='10' type=\"text\" name=\"cut\" value=\"" . $CFG_GLPI["cut"] . "\"></td>";
			
				echo "<td class='center'>" . $LANG["setup"][131] . "</td><td>";
				dropdownInteger('dropdown_limit', $CFG_GLPI["dropdown_limit"], 20, 100);
				echo "</td></tr>";

				echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["setup"][132] . "</td><td>";
				dropdownYesNo('flat_dropdowntree', $CFG_GLPI["flat_dropdowntree"]);
				echo "</td>";
			
				echo "<td class='center'>" . $LANG["setup"][108] . "</td><td><input size='10' type=\"text\" name=\"num_of_events\" value=\"" . $CFG_GLPI["num_of_events"] . "\">";
				echo "</td></tr>";
			
				echo "<tr class='tab_bg_1'><td colspan='4' align='center'><strong>" . $LANG["setup"][111] . "</strong></td></tr>";

				echo "<tr class='tab_bg_2'>";
				echo "<td class='center'>" . $LANG["common"][44]."</td><td>";
				dropdownInteger("list_limit",$cfg->fields["list_limit"],5,200,5);
				
				echo "</td><td class='center'>" . $LANG["common"][58] . "</td><td>";
				dropdownInteger("list_limit",$cfg->fields["list_limit_max"],5,200,5);

				echo "</td></tr>";

				echo "<tr class='tab_bg_1'><td colspan='4' align='center'><strong>" . $LANG["title"][24] . "</strong></td></tr>";
			
				echo "<tr class='tab_bg_2'><td class='center'> " . $LANG["setup"][110] . " </td><td>";
				dropdownYesNo("jobs_at_login", $CFG_GLPI["jobs_at_login"]);
				echo " </td><td colspan='2'>&nbsp;</td></tr>";
			
				echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["setup"][114] . "</td><td colspan='3'>";
				echo "<table><tr>";
				echo "<td bgcolor='" . $CFG_GLPI["priority_1"] . "'>1:<input type=\"text\" name=\"priority_1\" size='7' value=\"" . $CFG_GLPI["priority_1"] . "\"></td>";
				echo "<td bgcolor='" . $CFG_GLPI["priority_2"] . "'>2:<input type=\"text\" name=\"priority_2\" size='7' value=\"" . $CFG_GLPI["priority_2"] . "\"></td>";
				echo "<td bgcolor='" . $CFG_GLPI["priority_3"] . "'>3:<input type=\"text\" name=\"priority_3\" size='7' value=\"" . $CFG_GLPI["priority_3"] . "\"></td>";
				echo "<td bgcolor='" . $CFG_GLPI["priority_4"] . "'>4:<input type=\"text\" name=\"priority_4\" size='7' value=\"" . $CFG_GLPI["priority_4"] . "\"></td>";
				echo "<td bgcolor='" . $CFG_GLPI["priority_5"] . "'>5:<input type=\"text\" name=\"priority_5\" size='7' value=\"" . $CFG_GLPI["priority_5"] . "\"></td>";
				echo "</tr></table>";
				echo "</td></tr>";
			
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

				echo "<tr class='tab_bg_1'><td colspan='4' align='center'><strong>" . $LANG["setup"][406] . "</strong></td></tr>";

				echo "<tr class='tab_bg_2'><td class='center'> " . $LANG["setup"][118] . " </td><td colspan='3' align='center'>";
				echo "<textarea cols='70' rows='4' name='text_login' >";
				echo $CFG_GLPI["text_login"];
				echo "</textarea>";
				echo "</td></tr>";

				echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["setup"][407] . "</td><td> <input size='30' type=\"text\" name=\"helpdeskhelp_url\" value=\"" . $CFG_GLPI["helpdeskhelp_url"] . "\"></td>";
				echo "<td class='center'>" . $LANG["setup"][408] . "</td><td> <input size='30' type=\"text\" name=\"centralhelp_url\" value=\"" . $CFG_GLPI["centralhelp_url"] . "\"></td></tr>";

				
				echo "<tr class='tab_bg_2'><td colspan='4' align='center'><input type=\"submit\" name=\"update\" class=\"submit\" value=\"" . $LANG["buttons"][2] . "\" ></td></tr>";
				
				echo "</table></div>";
				
			break;
			// RESTRICTIONS CONFIG
			case 3:
				echo "<div class='center'><table class='tab_cadre_fixe'>";
								
				echo "<tr><th colspan='4'>" . $LANG["setup"][270] . "</th></tr>";
			
				echo "<tr class='tab_bg_2'>";
				adminManagementDropdown("monitors_management_restrict",$LANG["setup"][271],$CFG_GLPI["monitors_management_restrict"]);											
				adminManagementDropdown("peripherals_management_restrict",$LANG["setup"][272],$CFG_GLPI["peripherals_management_restrict"]);				
				echo "</tr>";
				
				echo "<tr class='tab_bg_2'>";
				adminManagementDropdown("phones_management_restrict",$LANG["setup"][273],$CFG_GLPI["phones_management_restrict"]);				
				adminManagementDropdown("printers_management_restrict",$LANG["setup"][275],$CFG_GLPI["printers_management_restrict"]);				
				echo "</tr>";

				echo "<tr class='tab_bg_2'>";
				adminManagementDropdown("licenses_management_restrict",$LANG["setup"][276],$CFG_GLPI["licenses_management_restrict"],1);				
				echo "<td >".$LANG["setup"][277]."</td><td>";
				dropdownYesNo("license_deglobalisation",$CFG_GLPI["license_deglobalisation"]);
				echo"</td></tr>";

				echo "<tr><th colspan='2'>" . $LANG["setup"][134]. "</th><th colspan='2'>" . $LANG["Menu"][31] . "</th></tr>";

				echo "<tr class='tab_bg_2'><td class='center'> " . $LANG["setup"][133] . " </td><td>";
				dropdownYesNo("ocs_mode", $CFG_GLPI["ocs_mode"]);
				echo "</td><td class='center'>" . $LANG["setup"][219] . "</td><td>";
				dropdownYesNo("permit_helpdesk", $CFG_GLPI["permit_helpdesk"]);
				echo "</td></tr>";

				echo "<tr><th colspan='2'>" . $LANG["login"][10] . "</th><th colspan='2'>".$LANG["Menu"][20]."</th></tr>";
				echo "<tr class='tab_bg_2'><td class='center'> " . $LANG["setup"][124] . " </td><td>";
				dropdownYesNo("auto_add_users", $CFG_GLPI["auto_add_users"]);
				echo "</td>";
				
				echo "<td class='center'> " . $LANG["setup"][117] . " </td><td>";
				dropdownYesNo("public_faq", $CFG_GLPI["public_faq"]);
				echo " </td></tr>";

				echo "<tr><th colspan='4'>" . $LANG["setup"][280]. "</th></tr>";

				echo "<tr class='tab_bg_2'><td class='center'> " . $LANG["common"][18] . " </td><td>";
				dropdownYesNo("autoupdate_link_contact", $CFG_GLPI["autoupdate_link_contact"]);
				echo "</td>";
				
				echo "<td class='center'> " . $LANG["common"][34] . " </td><td>";
				dropdownYesNo("autoupdate_link_user", $CFG_GLPI["autoupdate_link_user"]);
				echo " </td></tr>";

				echo "<tr class='tab_bg_2'><td class='center'> " . $LANG["common"][35] . " </td><td>";
				dropdownYesNo("autoupdate_link_group", $CFG_GLPI["autoupdate_link_group"]);
				echo "</td>";
				
				echo "<td class='center'> " . $LANG["common"][15] . " </td><td>";
				dropdownYesNo("autoupdate_link_location", $CFG_GLPI["autoupdate_link_location"]);
				echo " </td></tr>";

				
				echo "<tr class='tab_bg_2'><td colspan='4' align='center'><input type=\"submit\" name=\"update\" class=\"submit\" value=\"" . $LANG["buttons"][2] . "\" ></td></tr>";

									
				echo "</table></div>";
				
			break;
		}
		echo "</form>";

	}


	function showFormMailing($target) {
	
		global $DB, $LANG, $CFG_GLPI;
	
		if (!haveRight("config", "w"))
			return false;
	
		echo "<form action=\"$target\" method=\"post\">";
		echo "<input type='hidden' name='ID' value='" . $CFG_GLPI["ID"] . "'>";
	
		echo "<div id='barre_onglets'><ul id='onglet'>";
		echo "<li ";
		if ($_SESSION['glpi_mailconfig'] == 1) {
			echo "class='actif'";
		}
		echo "><a href='$target?onglet=1'>" . $LANG["Menu"][10] . "</a></li>";
		echo "<li ";
		if ($_SESSION['glpi_mailconfig'] == 2) {
			echo "class='actif'";
		}
		echo "><a href='$target?onglet=2'>" . $LANG["setup"][240] . "</a></li>";
		echo "<li ";
		if ($_SESSION['glpi_mailconfig'] == 3) {
			echo "class='actif'";
		}
		echo "><a href='$target?onglet=3'>" . $LANG["setup"][242] . "</a></li>";
		echo "</ul></div>";
	
		if ($_SESSION['glpi_mailconfig'] == 1) {
			echo "<div class='center'><table class='tab_cadre_fixe'><tr><th colspan='2'>" . $LANG["setup"][201] . "</th></tr>";
	
			echo "<tr class='tab_bg_2'><td >" . $LANG["setup"][202] . "</td><td>";
			dropdownYesNo("mailing", $CFG_GLPI["mailing"]);
			echo "</td></tr>";
	
			echo "<tr class='tab_bg_2'><td >" . $LANG["setup"][203] . "</td><td> <input type=\"text\" name=\"admin_email\" size='40' value=\"" . $CFG_GLPI["admin_email"] . "\">";
			if (!isValidEmail($CFG_GLPI["admin_email"])){
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
	
			echo "<tr class='tab_bg_2'><td >" . $LANG["setup"][231] . "</td><td>&nbsp; ";
	
			if (!function_exists('mail')) { // if mail php disabled we forced SMTP usage 
				echo $LANG["choice"][1] . "  &nbsp;<input type=\"radio\" name=\"smtp_mode\" value=\"1\" checked >";
			} else {
				dropdownYesNo("smtp_mode", $CFG_GLPI["smtp_mode"]);
			}
			echo "</td></tr>";
	
			echo "<tr class='tab_bg_2'><td >" . $LANG["setup"][232] . "</td><td> <input type=\"text\" name=\"smtp_host\" size='40' value=\"" . $CFG_GLPI["smtp_host"] . "\"> </td></tr>";
	
			echo "<tr class='tab_bg_2'><td >" . $LANG["setup"][233] . "</td><td> <input type=\"text\" name=\"smtp_port\" size='40' value=\"" . $CFG_GLPI["smtp_port"] . "\"> </td></tr>";
	
			echo "<tr class='tab_bg_2'><td >" . $LANG["setup"][234] . "</td><td> <input type=\"text\" name=\"smtp_username\" size='40' value=\"" . $CFG_GLPI["smtp_username"] . "\"> </td></tr>";
	
			echo "<tr class='tab_bg_2'><td >" . $LANG["setup"][235] . "</td><td> <input type=\"password\" name=\"smtp_password\" size='40' value=\"" . $CFG_GLPI["smtp_password"] . "\"> </td></tr>";
	
			echo "<tr class='tab_bg_2'><td >" . $LANG["setup"][245] . " " . $LANG["setup"][244] . "</td><td>";
			echo "<select name='cartridges_alert'> ";
			echo "<option value='0' " . ($CFG_GLPI["cartridges_alert"] == 0 ? "selected" : "") . " >" . $LANG["setup"][307] . "</option>";
			echo "<option value='" . WEEK_TIMESTAMP . "' " . ($CFG_GLPI["cartridges_alert"] == WEEK_TIMESTAMP ? "selected" : "") . " >" . $LANG["setup"][308] . "</option>";
			echo "<option value='" . MONTH_TIMESTAMP . "' " . ($CFG_GLPI["cartridges_alert"] == MONTH_TIMESTAMP ? "selected" : "") . " >" . $LANG["setup"][309] . "</option>";
			echo "</select>";
			echo "</td></tr>";
	
			echo "<tr class='tab_bg_2'><td >" . $LANG["setup"][245] . " " . $LANG["setup"][243] . "</td><td>";
			echo "<select name='consumables_alert'> ";
			echo "<option value='0' " . ($CFG_GLPI["consumables_alert"] == 0 ? "selected" : "") . " >" . $LANG["setup"][307] . "</option>";
			echo "<option value='" . WEEK_TIMESTAMP . "' " . ($CFG_GLPI["consumables_alert"] == WEEK_TIMESTAMP ? "selected" : "") . " >" . $LANG["setup"][308] . "</option>";
			echo "<option value='" . MONTH_TIMESTAMP . "' " . ($CFG_GLPI["consumables_alert"] == MONTH_TIMESTAMP ? "selected" : "") . " >" . $LANG["setup"][309] . "</option>";
			echo "</select>";
			echo "</td></tr>";
	
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
	
		} else
			if ($_SESSION['glpi_mailconfig'] == 2) {
	
				$profiles[USER_MAILING_TYPE . "_" . ADMIN_MAILING] = $LANG["setup"][237];
				$profiles[USER_MAILING_TYPE . "_" . TECH_MAILING] = $LANG["common"][10];
				$profiles[USER_MAILING_TYPE . "_" . USER_MAILING] = $LANG["common"][34] . " " . $LANG["common"][1];
				$profiles[USER_MAILING_TYPE . "_" . AUTHOR_MAILING] = $LANG["job"][4];
				$profiles[USER_MAILING_TYPE . "_" . ASSIGN_MAILING] = $LANG["setup"][239];
				$profiles[USER_MAILING_TYPE . "_" . ASSIGN_ENT_MAILING] = $LANG["financial"][26];
				$profiles[USER_MAILING_TYPE . "_" . RECIPIENT_MAILING] = $LANG["job"][3];
	
				$query = "SELECT ID, name FROM glpi_profiles order by name";
				$result = $DB->query($query);
				while ($data = $DB->fetch_assoc($result))
					$profiles[PROFILE_MAILING_TYPE .
					"_" . $data["ID"]] = $LANG["profiles"][22] . " " . $data["name"];
	
				$query = "SELECT ID, name FROM glpi_groups order by name";
				$result = $DB->query($query);
				while ($data = $DB->fetch_assoc($result))
					$profiles[GROUP_MAILING_TYPE .
					"_" . $data["ID"]] = $LANG["common"][35] . " " . $data["name"];
	
				ksort($profiles);
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
				unset ($profiles[USER_MAILING_TYPE . "_" . RECIPIENT_MAILING]);
				showFormMailingType("resa", $profiles);
				echo "</tr>";
	
				echo "</table>";
				echo "</div>";
			} else
				if ($_SESSION['glpi_mailconfig'] == 3) {
					$profiles[USER_MAILING_TYPE . "_" . ADMIN_MAILING] = $LANG["setup"][237];
					$query = "SELECT ID, name FROM glpi_profiles order by name";
					$result = $DB->query($query);
					while ($data = $DB->fetch_assoc($result))
						$profiles[PROFILE_MAILING_TYPE .
						"_" . $data["ID"]] = $LANG["profiles"][22] . " " . $data["name"];
	
					$query = "SELECT ID, name FROM glpi_groups order by name";
					$result = $DB->query($query);
					while ($data = $DB->fetch_assoc($result))
						$profiles[GROUP_MAILING_TYPE .
						"_" . $data["ID"]] = $LANG["common"][35] . " " . $data["name"];
	
					ksort($profiles);
					echo "<div class='center'>";
					echo "<input type='hidden' name='update_notifications' value='1'>";
					// ADMIN
					echo "<table class='tab_cadre_fixe'>";
					echo "<tr><th colspan='3'>" . $LANG["setup"][243] . "</th></tr>";
					echo "<tr class='tab_bg_2'>";
					showFormMailingType("alertconsumable", $profiles);
					echo "</tr>";
					echo "<tr><th colspan='3'>" . $LANG["setup"][244] . "</th></tr>";
					echo "<tr class='tab_bg_1'>";
					showFormMailingType("alertcartridge", $profiles);
					echo "</tr>";
					echo "<tr><th colspan='3'>" . $LANG["setup"][246] . "</th></tr>";
					echo "<tr class='tab_bg_2'>";
					showFormMailingType("alertcontract", $profiles);
					echo "</tr>";
					echo "<tr><th colspan='3'>" . $LANG["setup"][247] . "</th></tr>";
					echo "<tr class='tab_bg_1'>";
					showFormMailingType("alertinfocom", $profiles);
					echo "</tr>";
					echo "</table>";
					echo "</div>";
	
				}
		echo "</form>";
	
	}

}

class ConfigOCS extends CommonDBTM {

	function ConfigOCS () {
		$this->table="glpi_ocs_config";
		$this->type=-1;
	}

	function prepareInputForUpdate($input) {
		if (isset($input["ocs_db_passwd"])&&!empty($input["ocs_db_passwd"])){
			$input["ocs_db_passwd"]=urlencode(stripslashes($input["ocs_db_passwd"]));
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
	function post_updateItem($input,$updates,$history=1) {
		global $CACHE_CFG;
		if (count($updates)){
			$CACHE_CFG->remove("CFG_OCSGLPI_".$input["ID"],"GLPI_CFG");
		}
	}

}

?>
