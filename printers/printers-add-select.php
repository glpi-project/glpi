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
*/
 
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_computers.php");
include ($phproot . "/glpi/includes_setup.php");

checkAuthentication("admin");

commonHeader($lang["title"][8],$_SERVER["PHP_SELF"]);

      GLOBAL $cfg_install, $cfg_layout, $layout, $lang;


echo "<div align='center'><table border='0' cellpadding=5 class='tab_cadre'>";
echo "<tr><th>";
echo $lang["common"][7];
echo "</th></tr>";

$db = new DB;
$query = "SELECT * FROM glpi_printers where is_template = '1' ORDER BY tplname";
$result = $db->query($query);
$i = 0;
$number = $db->numrows($result);

while ($i < $number) {
	$ID = $db->result($result,$i, "ID");
  	$name = $db->result($result, $i, "tplname");
	echo "<tr class='tab_bg_1'><td align='center'><a href=\"printers-info-form.php?withtemplate=2&ID=$ID\">$name</a></td></tr>";
	$i++;
}

echo "</table></div>";

commonFooter();
?>
