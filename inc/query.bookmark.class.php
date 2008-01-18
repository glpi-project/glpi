<?php

/*
 * @version $Id: cron.class.php 6235 2008-01-02 17:57:10Z moyo $
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

if (!defined('GLPI_ROOT')) {
	die("Sorry. You can't access directly to this file");
}

class QueryBookmark extends CommonDBTM {

	function QueryBookmark() {
		global $CFG_GLPI;
		$this->table = "glpi_query_bookmark";
	}

	function showSaveQueryForm($target,$type,$user_id) {
		global $LANG;
		echo "<br>";
		echo "<div class='center'>";
		echo "<form method='post' name='form_save_query' action=\"$target\">";

		echo "<input type='hidden' name='type' value='" . $type . "'>";
		echo "<input type='hidden' name='FK_user' value='" . $user_id . "'>";

		echo "<table class='tab_cadre'>";
		echo "<tr><th align='center' colspan='2'>".$LANG["search"][21]." ".$LANG["search"][22]."</th>";
		echo "<tr><td class='tab_bg_1'>".$LANG["common"][16]."</td>"; 
		echo "<td class='tab_bg_1'>";
		autocompletionTextField("name",$this->table,"name",'',40);				
		echo "</td></tr>"; 
		echo "<tr><td class='tab_bg_1' colspan='2' align='center'>";
		echo "<input type='submit' name='save' value=\"".$LANG["buttons"][2]."\" class='submit'>";
		echo "</tr>";
		echo "</table></form></div>";
		
	}

	function showQuerySavedForm()
	{
		global $LANG;
		echo "<span align='center'>".$LANG["search"][23]."</span>";
	}
	
	function showQueryLoadedForm($url)
	{
		global $LANG;
		echo "<script type='text/javascript' >\n";
				echo "window.opener.location.href='$url';";
				echo "window.close();";
		echo "</script>";
		echo "<span align='center'>".$LANG["search"][23]."</span>";
	}
	
	function showLoadQueryForm($target,$type, $user_id) {
		global $DB,$LANG;
		$result = $DB->query("SELECT ID, name FROM glpi_query_bookmark WHERE FK_user=$user_id AND type=$type ORDER BY name");

		echo "<br>";
		echo "<div class='center'>";
		echo "<form method='post' name='form_load_query' action=\"$target\">";

		echo "<input type='hidden' name='type' value=\"$type\">";

		echo "<table class='tab_cadre'>";
		echo "<tr><th align='center' colspan='2'>".$LANG["common"][68]." ".$LANG["search"][22]."</th>";

		if( $DB->numrows($result))
		{
			echo "<tr><td class='tab_bg_1'>".$LANG["common"][16]."</td>"; 
			echo "<td class='tab_bg_1'>";
	
			$values = array();
			while ($data = $DB->fetch_array($result))
				$values[$data["ID"]] = $data["name"];
			dropdownArrayValues("ID",$values);
	
			echo "</td></tr>"; 
			echo "<tr><td class='tab_bg_1' colspan='2' align='center'>";
			echo "<input type='submit' name='load' value=\"".$LANG["buttons"][2]."\" class='submit'>";
			echo "&nbsp;<input type='submit' name='delete' value=\"".$LANG["buttons"][6]."\" class='submit'>";
			echo "</td></tr>";
		}
		else
			echo "<tr><td colspan='2' class='tab_bg_1'>".$LANG["search"][24]."</td></tr>";
		
		echo "</table></form></div>";

	}
}
?>
