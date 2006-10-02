<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.

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

include ("_relpos.php");

$NEEDED_ITEMS=array("setup");




include ("_relpos.php");

$NEEDED_ITEMS=array("setup");
include ($phproot . "/inc/includes.php");


commonHeader($lang["title"][2],$_SERVER["PHP_SELF"]);

if (isset($plugin_hooks["config_page"]) && is_array($plugin_hooks["config_page"])) {
	foreach ($plugin_hooks["config_page"] as $plug => $page){
		$function="plugin_version_$plug";
		$names[$plug]=$function();
		$pages[$plug]=$page;
	}
}

echo "<div align='center'><table border='0'><tr><td>";
echo "<img src=\"".$HTMLRel."pics/configuration.png\" alt='".$lang["Menu"][10]."' title='".$lang["Menu"][10]."'></td>";

// ligne a modifier en fonction de la modification des fichiers de langues 
echo "<td><span class='icon_sous_nav'><b>".$lang["setup"][700]."</b></span></td>";
echo "</tr></table></div>";

echo "<div align='center'><table class='tab_cadre' cellpadding='5'>";

// ligne a modifier en fonction de la modification des fichiers de langues
echo "<tr><th colspan='2'>".$lang["setup"][701]."</th></tr>";

foreach ($names as $key => $val) {

	echo "<tr class='tab_bg_1'><td align='center'><a href='".$HTMLRel."plugins/$key/".$pages[$key]."'><b>".$val["name"]."</b></a></td><td align='center'>#".$val["version"]."</td></tr>";
}

echo "</table></div>";

commonFooter();




?>
