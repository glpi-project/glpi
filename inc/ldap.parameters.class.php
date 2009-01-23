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
// Original Author of file: Olivier Andreotti
// Purpose of file:
// ----------------------------------------------------------------------
if (!defined('GLPI_ROOT')) {
	die("Sorry. You can't access directly to this file");
}

/// LDAP criteria class
class LdapCriteria extends CommonDBTM {

	/**
	 * Constructor
	**/
	function LdapCriteria() {
		$this->table = "glpi_rules_ldap_parameters";

	}

	/// Get parameters list
	function getParametersList() {
		global $DB;
		$sql = "SELECT * 
			FROM `" . $this->table . "` 
			WHERE rule_type=".RULE_AFFECT_RIGHTS." 
			ORDER BY name ASC";
		$result = $DB->query($sql);
		$parameters = array ();

		while ($datas = $DB->fetch_array($result))
			$parameters[] = $datas;

		return $parameters;
	}

	/**
	 * Print the ldap criteria form
	 *
	 *@param $target filename : where to go when done.
	 **/
	function showForm($target) {
		global $LANG,$CFG_GLPI;
		$canedit = haveRight("config", "w");
		$ID=-1;
		$parameters = $this->getParametersList();

		echo "<form name='entityaffectation_form' id='ldapcriterias_form' method='post' action=\"$target\">";

		if ($canedit) {
			echo "<div class='center'>";
			echo "<table  class='tab_cadre_fixe'>";
			echo "<tr class='tab_bg_1'><th colspan='5'>" .$LANG["ruleldap"][3] . "</tr><tr><td class='tab_bg_2' align='center'>";
			echo "</td><td align='center' class='tab_bg_2'>";
			echo $LANG["common"][16] . ":";
			autocompletionTextField("name", $this->table, "name", "", 30);
			echo $LANG["setup"][601] . ":";
			autocompletionTextField("value", $this->table, "value", "", 30);
			echo "<input type=hidden name='rule_type' value=\"" . RULE_AFFECT_RIGHTS . "\">";
			echo "<input type='submit' name='add' value=\"" . $LANG["buttons"][8] . "\" class='submit'>";
			echo "</td></tr>";
			
			echo "</table></div><br>";
		}

		if (!count($parameters)){
			echo "<center>".$LANG["ruleldap"][2]."</center>";
		} else {
			echo "<div class='center'><table class='tab_cadrehov'><tr><th colspan='3'>" . $LANG["common"][53]." ".$LANG["ruleldap"][1] . "</th></tr>";
			echo "<tr class='tab_bg_1'><td class='tab_bg_2' colspan='2'>" .  $LANG["common"][16]."</td><td class='tab_bg_2'>".$LANG["setup"][601] . "</td></tr>";

			foreach ($parameters as $parameter) {
				echo "<tr class='tab_bg_1'>";

				if ($canedit) {
					echo "<td width='10'>";
					$sel = "";
					if (isset ($_GET["select"]) && $_GET["select"] == "all")
						$sel = "checked";
					echo "<input type='checkbox' name='item[" . $parameter["ID"] . "]' value='1' $sel>";
					echo "</td>";
				}

				echo "<td>" . $parameter["name"] . "</td>";
				echo "<td>" . $parameter["value"] . "</td>";
				echo "</tr>";
			}
			echo "</table></div>";

			if ($canedit) {
				echo "<div class='center'>";
				echo "<table  width='80%'>";
				echo "<tr><td><img src=\"" . $CFG_GLPI["root_doc"] . "/pics/arrow-left.png\" alt=''></td><td class='center'><a onclick= \"if ( markAllRows('ldapcriterias_form') ) return false;\" href='" . $_SERVER['PHP_SELF'] . "?ID=$ID&amp;select=all'>" . $LANG["buttons"][18] . "</a></td>";
	
				echo "<td>/</td><td class='center'><a onclick= \"if ( unMarkAllRows('ldapcriterias_form') ) return false;\" href='" . $_SERVER['PHP_SELF'] . "?ID=$ID&amp;select=none'>" . $LANG["buttons"][19] . "</a>";
				echo "</td><td align='left' width='80%'>";
				echo "<input type='submit' name='delete' value=\"" . $LANG["buttons"][6] . "\" class='submit'>";
				echo "</td>";
				echo "</table>";
	
				echo "</div>";
	
			}
		}
		echo "</form>";
	}
	
	function prepareInputForAdd($input){
		//LDAP parameters MUST be in lower case
		//because the are retieved in lower case  from the directory
		$input["value"] = strtolower($input["value"]);
		return $input;
	}
}

?>
