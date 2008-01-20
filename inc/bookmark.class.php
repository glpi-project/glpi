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

class Bookmark extends CommonDBTM {

	function Bookmark() {
		global $CFG_GLPI;
		$this->table = "glpi_bookmark";
	}

	function showSaveBookmarkForm($target,$url, $user_id,$url) {
		global $LANG;
		echo "<br>";
		echo "<div class='center'>";
		echo "<form method='post' name='form_save_query' action=\"$target\">";

		echo "<input type='hidden' name='FK_users' value='" . $user_id . "'>";
		$taburl = parse_url($url);
		$index = strpos($taburl["path"],"plugins");
		if (!$index)
			$index = strpos($taburl["path"],"front");
		$path = substr($taburl["path"],$index,strlen($taburl["path"]) - $index);
			
		echo "<input type='hidden' name='path' value='" . urlencode($path) . "'>";
		echo "<input type='hidden' name='query' value='" . (isset($taburl["query"])?urlencode($taburl["query"]):'') . "'>";

		echo "<table class='tab_cadre'>";
		echo "<tr><th align='center' colspan='2'>".$LANG["bookmark"][0]." ".$LANG["bookmark"][1]."</th>";
		echo "<tr><td class='tab_bg_1'>".$LANG["common"][16]."</td>"; 
		echo "<td class='tab_bg_1'>";
		autocompletionTextField("name",$this->table,"name",'',40);				
		echo "</td></tr>"; 
		echo "<tr><td class='tab_bg_1' colspan='2' align='center'>";
		echo "<input type='submit' name='save' value=\"".$LANG["buttons"][2]."\" class='submit'>";
		echo "</tr>";
		echo "</table></form></div>";
		
	}

	function showBookmarkSavedForm()
	{
		global $LANG;
		echo "<div class='center'>"; 
		echo "<table class='tab_cadrehov'>";
		echo "<tr class='tab_bg_1'>";
		echo "<td>".$LANG["bookmark"][2]."</td>";
		echo "</tr></table></div>";
	}
	
	function showBookmarkLoadedForm($url)
	{
		global $LANG;
		echo "<script type='text/javascript' >\n";
				echo "window.opener.location.href='$url';";
				//echo "window.close();";
		echo "</script>";
	}
	
	function showLoadBookmarkForm($target,$user_id) {
		global $DB,$LANG,$CFG_GLPI;
		$result = $DB->query("SELECT ID, name FROM ".$this->table." WHERE FK_users=$user_id ORDER BY name");

		echo "<br>";

		echo "<div class='center'>"; 
		echo "<form method='post' name='form_load_bookmark' action=\"$target\">";

		echo "<table class='tab_cadrehov'>";
		echo "<tr><th align='center' colspan='2'>".$LANG["common"][68]." ".$LANG["bookmark"][1]."</th>";

		if( $DB->numrows($result))
		{

			while ($data = $DB->fetch_array($result))
			{
				echo "<tr class='tab_bg_1'>";
				echo "<td width='10'>";
				$sel="";
				if (isset($_GET["select"])&&$_GET["select"]=="all") $sel="checked";
				echo "<input type='checkbox' name='bookmark[" . $data["ID"] . "]' " . $sel . ">";
				echo "</td>";
				echo "<td>";
				echo "<a href=\"".GLPI_ROOT."/front/popup.php?action=load&bookmark_id=".$data["ID"]."\">".$data["name"]."</a>";
				echo "</td>";
				echo "</tr>";
			}
			echo "</table>";
			echo "</div>";
			
			echo "<div class='center'>";
			echo "<table width='80%'>";
			echo "<tr><td><img src=\"".$CFG_GLPI["root_doc"]."/pics/arrow-left.png\" alt=''></td><td class='center'><a onclick= \"if ( markAllRows('form_load_bookmark') ) return false;\" href='".$_SERVER['PHP_SELF']."?select=all'>".$LANG["buttons"][18]."</a></td>";
			echo "<td>/</td><td class='center'><a onclick= \"if ( unMarkAllRows('form_load_bookmark') ) return false;\" href='".$_SERVER['PHP_SELF']."?select=none'>".$LANG["buttons"][19]."</a>";
			echo "</td><td align='left' width='80%'>";
			echo "<input type='submit' name='delete' value=\"".$LANG["buttons"][6]."\" class='submit'>";
			echo "</td></tr>";
			echo "</table>";
	
			echo "</div>";

		}
		else
			echo "<tr class='tab_bg_1'><td colspan='2'>".$LANG["bookmark"][3]."</td></tr></table>";
		
		echo "</form></div>";
	}
}
?>
