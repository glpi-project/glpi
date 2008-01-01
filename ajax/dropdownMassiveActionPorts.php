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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------


define('GLPI_ROOT','..');
$AJAX_INCLUDE=1;
$NEEDED_ITEMS=array("search");
include (GLPI_ROOT."/inc/includes.php");
header("Content-Type: text/html; charset=UTF-8");
header_nocache();

checkRight("networking","w");

if (isset($_POST["action"])){
	echo "<input type='hidden' name='action' value='".$_POST["action"]."'>";
	switch($_POST["action"]){
		case "delete":
			echo "<input type=\"submit\" name=\"delete_several\" class=\"submit\" value=\"".$LANG["buttons"][2]."\" >";
		break;
		case "assign_vlan":
			dropdownValue("glpi_dropdown_vlan","vlan",0);
		echo "&nbsp;<input type=\"submit\" name=\"assign_vlan_several\" class=\"submit\" value=\"".$LANG["buttons"][2]."\" >";
		break;
		case "unassign_vlan":
			dropdownValue("glpi_dropdown_vlan","vlan",0);
		echo "&nbsp;<input type=\"submit\" name=\"unassign_vlan_several\" class=\"submit\" value=\"".$LANG["buttons"][2]."\" >";
		break;
		case "move":
			dropdownValue($LINK_ID_TABLE[$_POST['type']],"device",0);
			echo "&nbsp;<input type=\"submit\" name=\"move\" class=\"submit\" value=\"".$LANG["buttons"][2]."\" >";
		break;
	}
}

?>
