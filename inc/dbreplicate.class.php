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
class DBReplicate extends CommonDBTM{
	function DBReplicate()
	{
		$this->table="glpi_db_replicate";
	}
	
	function showForm($target) {
		global $LANG,$CFG_GLPI,$DB;
		$this->getFromDB($CFG_GLPI["ID"]);
		echo "<form name='form' action=\"$target\" method=\"post\">";
		echo "<input type='hidden' name='ID' value='" . $CFG_GLPI["ID"] . "'>";

		$active = isDBSlaveActive();

		echo "<div class='center'><table class='tab_cadre_fixe'>";

		echo "<tr class='tab_bg_2'><th colspan='4'>" . $LANG["setup"][800] . "</th></tr>";

		echo "<tr class='tab_bg_2'><td class='center'> " . $LANG["setup"][801] . " </td><td>";
		dropdownYesNo("slave_status", $active);
		echo " </td><td  colspan='2'></td></tr>";

		if (!$active)
			echo "<tr class='tab_bg_2'><td colspan='4' align='center'><input type=\"submit\" name=\"activate_slave\" class=\"submit\" value=\"" . $LANG["buttons"][2] . "\" ></td></tr>";
		else {
			$DBSlave = getDBSlaveConf();
			echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["install"][30] . " </td><td><input type=\"text\" name=\"dbhost\" size='40' value=\"" . $DBSlave->dbhost . "\"></td>";
			echo "<td class='center'>" . $LANG["setup"][802] . "</td><td>";
			echo "<input type=\"text\" name=\"dbdefault\" value=\"" . $DBSlave->dbdefault . "\">";
			echo "</td></tr>";

			echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["install"][31] . "</td><td>";
			echo "<input type=\"text\" name=\"dbuser\" value=\"" . $DBSlave->dbuser . "\">";
			echo "<td class='center'>" . $LANG["install"][32] . "</td><td>";
			echo "<input type=\"text\" name=\"dbpassword\" value=\"" . $DBSlave->dbpassword . "\">";
			echo "</td></tr>";

			echo "<tr class='tab_bg_2'><th colspan='4'>" . $LANG["setup"][704] . "</th></tr>";

			echo "<tr class='tab_bg_2'><td class='center'> " . $LANG["setup"][804] . " </td><td>";
			dropdownYesNo("notify_db_desynchronization", $this->fields["notify_db_desynchronization"]);
			echo " </td>";

			echo "<td class='center'> " . $LANG["setup"][805] . " </td><td>";
			autocompletionTextField("admin_email","glpi_db_replicate", "admin_email", $this->fields["admin_email"]);
			echo " </td></tr>";

			echo "<tr class='tab_bg_2'><td class='center'> " . $LANG["setup"][806] . " </td><td>";
			autocompletionTextField("max_delay", "glpi_db_replicate", "max_delay",$this->fields["max_delay"],10);
			echo "&nbsp;" . $LANG["stats"][34]." </td>";
			echo "<td colspan='2'></td></tr>";


			echo "<tr class='tab_bg_2'>";			
			if ($DBSlave->connected && !$DB->isSlave()) {
				echo "<td colspan='4' align='center'>" . $LANG["setup"][803] . " : ";
				echo timestampToString(getReplicateDelay(),1);
				echo "</td>";
			} else
				echo "<td colspan='4'></td>";

			echo "</tr>";

			echo "<tr class='tab_bg_2'><td colspan='4' align='center'><input type=\"submit\" name=\"update_slave\" class=\"submit\" value=\"" . $LANG["buttons"][2] . "\" ></td></tr>";

		}

		echo "</tr>";
		echo "</table></div>";
		echo "</form>";

	}
}
?>
