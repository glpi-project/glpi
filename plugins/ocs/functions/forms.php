<?php
/*
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2005 by the INDEPNET Development Team.
 
 http://indepnet.net/   http://glpi.indepnet.org
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

// Original Author of file: Bazile Lebeau :wq
// Purpose of file:
// ---------------------------------------------------------------------
function ocsFormConfig($target, $id) {

	GLOBAL  $lang, $langOcs;
	$db = new DB;
	$query = "select * from glpi_ocs_config where ID = '".$id."'";
	$result = $db->query($query);
	echo "<form name='formconfig' action=\"$target\" method=\"post\">";
	echo "<div align='center'><table class='tab_cadre'>";
	echo "<tr><th colspan='2'>".$langOcs["config"][0]."</th></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$langOcs["config"][2]." </td><td> <input type=\"text\" name=\"ocs_db_host\" value=\"".$db->result($result,0,"ocs_db_host")."\"></td></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$langOcs["config"][4]." </td><td> <input type=\"text\" name=\"ocs_db_name\" value=\"".$db->result($result,0,"ocs_db_name")."\"></td></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$langOcs["config"][1]." </td><td> <input type=\"text\" name=\"ocs_db_user\" value=\"".$db->result($result,0,"ocs_db_user")."\"></td></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$langOcs["config"][3]." </td><td> <input type=\"password\" name=\"ocs_db_passwd\" value=\"".$db->result($result,0,"ocs_db_passwd")."\"></td></tr>";
	
	echo "</table></div>";
	echo "<p class=\"submit\"><input type=\"submit\" name=\"update_conf_ocs\" class=\"submit\" value=\"".$langOcs["buttons"][0]."\" ></p>";
	echo "</form>";
	
}

?>
