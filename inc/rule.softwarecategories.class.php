<?php
/*
 * @version $Id: rule.ocs.class.php 5175 2007-07-03 19:14:11Z tsmr $
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
// Original Author of file: Walid Nouh
// Purpose of file:
// ----------------------------------------------------------------------
if (!defined('GLPI_ROOT')) {
	die("Sorry. You can't access directly to this file");
}


class SoftwareCategoriesRuleCollection extends RuleCollection {

	function SoftwareCategoriesRuleCollection() {
		$this->rule_type = RULE_SOFTWARE_CATEGORY;
		$this->rule_class_name = 'SoftwareCategoriesRule';
		$this->stop_on_first_match=true;
		$this->right="rule_softwarecategories";
	}

	function getTitle() {
		global $LANG;
		return $LANG["rulesengine"][37];
	}

	/**
	 * Get the attributes needed for processing the rules
	 * @return an array of attributes
	 */
	function prepareInputDataForProcess($input,$software){
		print_r($software);
		$params["name"]=$software["name"];
		if (isset($software["FK_glpi_enterprise"]))
			$params["manufacturer"]=getDropdownName("glpi_dropdown_manufacturer",$software["FK_glpi_enterprise"]);
		return $params;
	}
}

/**
* Rule class store all informations about a GLPI rule :
*   - description
*   - criterias
*   - actions
* 
**/
class SoftwareCategoriesRule extends Rule {


	function SoftwareCategoriesRule() {
		$this->table = "glpi_rules_descriptions";
		$this->type = -1;
		$this->rule_type = RULE_SOFTWARE_CATEGORY;
		$this->right="rule_softwarecategories";
		$this->can_sort=true;
	}

	function getTitle() {
		global $LANG;
		return $LANG["rulesengine"][37];
	}

	function maxActionsCount(){
		// Unlimited
		return 1;
	}
	/**
	 * Display form to add rules
	 * @param $target 
	 * @param $ID
	 */
	function showAndAddRuleForm($target, $ID) {
		global $LANG, $CFG_GLPI;

		$canedit = haveRight($this->right, "w");

		echo "<form name='softwarecategories_form' id='softwarecategories_form' method='post' action=\"$target\">";

		if ($canedit) {

			echo "<div align='center'>";
			echo "<table  class='tab_cadre_fixe'>";
			echo "<tr class='tab_bg_1'><th colspan='4'>" .  $LANG["rulesengine"][36] . "</tr><tr><td class='tab_bg_2' align='center'>";
			echo $LANG["common"][16] . ":";
			echo "</td><td align='center' class='tab_bg_2'>";
			autocompletionTextField("name", "glpi_rules_descriptions", "name", "", 30);
			echo $LANG["joblist"][6] . ":";
			autocompletionTextField("description", "glpi_rules_descriptions", "description", "", 30);
			echo "</td><td align='center' class='tab_bg_2'>";
			echo $LANG["rulesengine"][9] . ":";
			$this->dropdownRulesMatch("match", "AND");
			echo "</td><td align='center' class='tab_bg_2'>";
			echo "<input type=hidden name='rule_type' value=\"" . $this->rule_type . "\">";
			echo "<input type=hidden name='FK_entities' value=\"-1\">";
			echo "<input type=hidden name='affectentity' value=\"" . $ID . "\">";
			echo "<input type='submit' name='add_rule' value=\"" . $LANG["buttons"][8] . "\" class='submit'>";
			echo "</td></tr>";

			echo "</table></div><br>";

		}

		echo "<div align='center'><table class='tab_cadrehov'><tr><th colspan='3'>" . $LANG["entity"][5] . "</th></tr>";

		//Get all rules and actions
		$rules = $this->getRulesByID( $ID, 0, 1);

		if (!empty ($rules)) {

			foreach ($rules as $rule) {
				echo "<tr class='tab_bg_1'>";

				if ($canedit) {
					echo "<td width='10'>";
					$sel = "";
					if (isset ($_GET["select"]) && $_GET["select"] == "all")
						$sel = "checked";
					echo "<input type='checkbox' name='item[" . $rule->fields["ID"] . "]' value='1' $sel>";
					echo "</td>";
				}

				if ($canedit)
					echo "<td><a href=\"" . $CFG_GLPI["root_doc"] . "/front/rule.ocs.form.php?ID=" . $rule->fields["ID"] . "&amp;onglet=1\">" . $rule->fields["name"] . "</a></td>";
				else
					echo "<td>" . $rule->fields["name"] . "</td>";

				echo "<td>" . $rule->fields["description"] . "</td>";
				echo "</tr>";
			}
		}
		echo "</table></div>";

		if ($canedit) {
			echo "<div align='center'>";
			echo "<table width='80%'>";
			echo "<tr><td><img src=\"" . $CFG_GLPI["root_doc"] . "/pics/arrow-left.png\" alt=''></td><td align='center'><a onclick= \"if ( markAllRows('softwarecategories_form') ) return false;\" href='" . $_SERVER['PHP_SELF'] . "?ID=$ID&amp;select=all'>" . $LANG["buttons"][18] . "</a></td>";

			echo "<td>/</td><td align='center'><a onclick= \"if ( unMarkAllRows('softwarecategories_form') ) return false;\" href='" . $_SERVER['PHP_SELF'] . "?ID=$ID&amp;select=none'>" . $LANG["buttons"][19] . "</a>";
			echo "</td><td align='left' width='80%'>";
			echo "<input type='submit' name='delete_softwarecategory' value=\"" . $LANG["buttons"][6] . "\" class='submit'>";
			echo "</td>";
			echo "</table>";

			echo "</div>";

		}
		echo "</form>";
	}
}


?>
