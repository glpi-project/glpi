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



$NEEDED_ITEMS=array("setup");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");




$NEEDED_ITEMS=array("setup");
include (GLPI_ROOT . "/inc/includes.php");


commonHeader($LANG["common"][12],$_SERVER['PHP_SELF'],"config","plugins");
$names=array();
if (isset($PLUGIN_HOOKS["config_page"]) && is_array($PLUGIN_HOOKS["config_page"])) {
	foreach ($PLUGIN_HOOKS["config_page"] as $plug => $page){
		$function="plugin_version_$plug";
		$infos[$plug]=$function();
		$names[$plug]=$infos[$plug]["name"];
		$pages[$plug]=$page;
	}
	asort($names);
}




echo "<div align='center'><table class='tab_cadre' cellpadding='5'>";

// ligne a modifier en fonction de la modification des fichiers de langues
echo "<tr><th colspan='2'>".$LANG["setup"][701]."</th></tr>";

foreach ($names as $key => $name) {

	$val = $infos[$key];
	if ($pages[$key]) {
		echo "<tr class='tab_bg_1'><td align='center'><a href='".$CFG_GLPI["root_doc"]."/plugins/$key/".$pages[$key]."'><strong>".$val["name"]."</strong></a></td>" .
				"<td><img src='../pics/greenbutton.png' /> #".$val["version"]."</td></tr>";		
	} else {
		echo "<tr class='tab_bg_2'><td align='center'>".$val["name"]."</td><td><img src='../pics/redbutton.png' /> #".$val["version"]." : ".$LANG["setup"][702].
			(isset($val["minGlpiVersion"]) ? "<br />GPLI >= " . $val["minGlpiVersion"] : "") .
			(isset($val["maxGlpiVersion"]) ? "<br />GLPI <= " . $val["maxGlpiVersion"] : "") . "</td></tr>";
	}
}

echo "</table></div>";

commonFooter();




?>
