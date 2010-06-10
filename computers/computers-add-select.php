<?php
/*
 
 ----------------------------------------------------------------------
GLPI - Gestionnaire libre de parc informatique
 Copyright (C) 2002 by the INDEPNET Development Team.
 http://indepnet.net/   http://glpi.indepnet.org
 ----------------------------------------------------------------------
 Based on:
IRMA, Information Resource-Management and Administration
Christian Bauer, turin@incubus.de 

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
include ($phproot . "/glpi/includes_computers.php");
include ($phproot . "/glpi/includes_setup.php");

checkAuthentication("admin");

commonHeader("Computers",$PHP_SELF);

      GLOBAL $cfg_install, $cfg_layout, $layout, $lang;


echo "<center><table border=0 cellpadding=5>";
echo "<tr><th>";
echo $lang["computers"][45];
echo "</th></tr>";

$db = new DB;
$query = "SELECT * FROM templates";
$result = $db->query($query);
$i = 0;
$number = $db->numrows($result);

while ($i < $number) {
	$ID = $db->result($result,$i, "ID");
  	$name = $db->result($result, $i, "templname");
	echo "<tr bgcolor=\"".$cfg_layout["tab_bg_1"]."\"><td align=center><a href=\"computers-info-form.php?withtemplate=1&ID=$ID\">$name</a></td></tr>";
	$i++;
}

echo "</table></center>";

commonFooter();
?>
