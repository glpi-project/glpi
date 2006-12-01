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

// Based on:
// IRMA, Information Resource-Management and Administration
// Christian Bauer 
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");

$NEEDED_ITEMS=array("setup");
include ($phproot . "/inc/includes.php");

checkCentralAccess();


commonHeader($lang["title"][2],$_SERVER['PHP_SELF']);


// titre
echo "<div align='center'><table border='0'><tr><td>";
echo "<img src=\"".$HTMLRel."pics/configuration.png\" alt='".$lang["Menu"][10]."' title='".$lang["Menu"][10]."' ></td><td><span class='icon_sous_nav'><b>".$lang["Menu"][10]."</b></span>";
echo "</td></tr></table></div>";

echo "<div align='center'><table class='tab_cadre' cellpadding='5'>";
echo "<tr><th colspan='2'>".$lang["setup"][62]."</th></tr>";

$config=array();

if (haveRight("config","w")){
	$config["setup.config.php?next=confgen"]=$lang["setup"][70];
	$config["setup.config.php?next=confdisplay"]=$lang["setup"][119];
	$config["setup.config.php?next=mailing"]=$lang["setup"][68];
	$config["setup.config.php?next=extauth"]=$lang["setup"][67];
	if ($cfg_glpi["ocs_mode"]&&haveRight("ocsng","w"))
		$config["setup.config.php?next=ocsng"]=$lang["setup"][134];
}

$data=array();
$data["setup.display.php"]=$lang["setup"][250];
if (haveRight("dropdown","w")){
	$data["setup.dropdowns.php"]=$lang["setup"][0];
}
if (haveRight("device","w")){
	$data[$HTMLRel."front/device.php"]=$lang["setup"][222];
}
if (haveRight("typedoc","r")){
	$data[$HTMLRel."front/typedoc.php"]=$lang["document"][7];
}
if (haveRight("link","r")){
	$data[$HTMLRel."front/link.php"]=$lang["setup"][87];
}

echo "<tr class='tab_bg_1'>";
if (count($data)>0){
	echo "<td><table>";
	foreach ($data as $page => $title)
		echo "<tr><td><a href=\"$page\"><b>$title</b></a></td></tr>\n";
	echo "</table></td>";
} 

if (count($config)>0){
	echo "<td><table>";
	foreach ($config as $page => $title)
		echo "<tr><td><a href=\"$page\"><b>$title</b></a></td></tr>\n";
	echo "</table></td>";
} 

echo "</tr>";

if (isset($plugin_hooks['config_page'])&&is_array($plugin_hooks['config_page'])&&count($plugin_hooks['config_page']))
echo "<tr class='tab_bg_1'><td  colspan='2' align='center'><a href=\"setup.plugins.php\"><b>Plugins</b></a></td></tr>";
if (haveRight("check_update","r"))
echo "<tr class='tab_bg_1'><td  colspan='2' align='center'><a href=\"setup.version.php\"><b>".$lang["setup"][300]."</b></a></td></tr>";



echo "</table></div>";




commonFooter();
?>
