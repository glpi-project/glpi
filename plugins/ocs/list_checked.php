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
// ----------------------------------------------------------------------
include ("_relpos.php");
include ($phproot."/glpi/includes.php");
include ($phproot."/plugins/ocs/functions/functions.php");
checkAuthentication("admin");
commonHeader($langOcs["title"][0],$_SERVER["PHP_SELF"]);
include($phproot."/plugins/ocs/dicts/".$_SESSION["glpilanguage"]."Ocs.php");
$db = new DB;
if(!empty($_GET["valid"])) {
	$query = "update glpi_ocs_link set human_checked = '1' where ID = '".$_GET["valid"]."'";
	$db->query($query);
}
$query = "select * from glpi_ocs_link where human_checked = '0'";
$result = $db->query($query);
	echo "<div align='center'><table class='tab_bg_2'>";
while($line = $db->fetch_array($result)) {
	$query2 = "select * from glpi_computers where ID = '".$line["glpi_id"]."'";
	$result2 = $db->query($query2);
	while($line2 = $db->fetch_array($result2)) {
		echo "<tr><td><a href=\"".$HTMLRel."computers/computers-info-form.php?ID=".$line2["ID"]."\">".$line2["name"]."</a></td>";
		echo "<td><a href=\"list_checked.php?valid=".$line["ID"]."\">".$lanOcs["list"][0]."</a></td>";
		echo "</tr>";
	}
}
	echo "</table></div>";
commonFooter();
?>