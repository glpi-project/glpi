<?php
/*
  ----------------------------------------------------------------------
GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2004 by the INDEPNET Development Team.
 
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
 ----------------------------------------------------------------------
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------


*/
 

include ("_relpos.php");
include ($phproot . "/glpi/includes.php");

checkAuthentication("normal");

commonHeader("Utils",$_SERVER["PHP_SELF"]);

 // titre
        echo "<div align='center'><table ><tr><td>";
        echo "<img src=\"".$HTMLRel."pics/rapports.png\" alt='".$lang["Menu"][6]."' title='".$lang["Menu"][6]."'></td><td><span class='icon_nav'><b>".$lang["Menu"][6]."</b></span>";
        echo "</td></tr></table></div>";



echo "<div align='center'><table class='tab_cadre' cellpadding='5'>";
echo "<tr><th>".$lang["reports"][0].":</th></tr>";

// Outils ajoutés par GLPI V0.2
$utils_list["dhcp"]["name"] = "DHCP";
$utils_list["dhcp"]["file"] = "utils/dhcp.php";


$i = 0;
$count = count($utils_list);
while($data = each($utils_list)) {
	$val = $data[0];
	$name = $utils_list["$val"]["name"];
	$file = $utils_list["$val"]["file"];
	echo  "<tr class='tab_bg_1'><td align='center'><a href=\"$file\"><b>$name</b></a></td></tr>";
	$i++;
}

echo "</table></div>";

commonFooter();

?>
