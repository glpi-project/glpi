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
// Original Author of file: Olivier Andreotti
// Purpose of file:
// ----------------------------------------------------------------------

/** Display registry values for a computer
* @param $ID integer : computer ID
*/
function showRegistry($ID){
	
	global $DB,$CFG_GLPI, $LANG;
	
	if (!haveRight("computer","r")) return false;
	//REGISTRY HIVE
	$REGISTRY_HIVE=array("HKEY_CLASSES_ROOT",
	"HKEY_CURRENT_USER",
	"HKEY_LOCAL_MACHINE",
	"HKEY_USERS",
	"HKEY_CURRENT_CONFIG",
	"HKEY_DYN_DATA");


	$query = "SELECT ID FROM glpi_registry WHERE computer_id='".$ID."'";
	
	echo "<br>";
	if ($result = $DB->query($query)) {
		if ($DB->numrows($result)!=0) { 
			
			echo "<br><br><div class='center'><table class='tab_cadre_fixe'>";
			echo "<tr>";
			echo "<th colspan='4'>";
			echo $DB->numrows($result)." ";
			echo $LANG["registry"][4];
			
			echo ":</th>";

			echo "</tr>";        
			echo "<tr>";			
			echo "<th>".$LANG["registry"][6]."</th>";
			echo "<th>".$LANG["registry"][1]."</th><th>".$LANG["registry"][2]."</th>";
			echo "<th>".$LANG["registry"][3]."</th></tr>\n";	
			while ($regid=$DB->fetch_row($result)) {
				$reg = new Registry;
				$reg->getFromDB(current($regid));	
				echo "<tr class='tab_bg_1'>";								
				echo "<td>".$reg->fields["registry_ocs_name"]."</td>";
				echo "<td>".$REGISTRY_HIVE[$reg->fields["registry_hive"]]."</td>";
				echo "<td>".$reg->fields["registry_path"]."</td>";
				echo "<td>".$reg->fields["registry_value"]."</td>";		
				echo "</tr>";	
			
			}
			echo "</table>";
			echo "</div>\n\n";
	
		}
		else echo "<div class='center'><strong>".$LANG["registry"][5]."</strong></div>";
	}
	
}
?>
