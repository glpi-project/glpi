<?php

/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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

$NEEDED_ITEMS = array (
	"ocsng",
	"computer",
	"device",
	"printer",
	"networking",
	"peripheral",
	"monitor",
	"software",
	"infocom",
	"phone",
	"tracking",
	"enterprise",
	"setup",
	"admininfo"
);

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

checkRight("ocsng", "w");

commonHeader($LANG["ocsng"][0], $_SERVER['PHP_SELF'], "utils","ocsng");
if (isset ($_SESSION["ocs_import"]))
	unset ($_SESSION["ocs_import"]);
if (isset ($_SESSION["ocs_link"]))
	unset ($_SESSION["ocs_link"]);
if (isset ($_SESSION["ocs_update"]))
	unset ($_SESSION["ocs_update"]);

if (isset($_GET["ocs_server_id"]) && $_GET["ocs_server_id"]) {
	$name = "";
	if (isset($_GET["ocs_server_id"]))
		$_SESSION["ocs_server_id"] = $_GET["ocs_server_id"];
				
	$sql = "SELECT name 
		FROM glpi_ocs_config 
		WHERE ID='".$_SESSION["ocs_server_id"]."'";
	$result = $DB->query($sql);
	if ($DB->numrows($result) > 0) {
		$datas = $DB->fetch_array($result);
		$name = " : " . $datas["name"];
	}
	echo "<div align='center'><table border='0'><tr><td>";
	echo "<img src=\"" . $CFG_GLPI["root_doc"] . "/pics/logoOcs.png\" alt='" . $LANG["ocsng"][0] . "' title='" . $LANG["ocsng"][0] . "' ></td>";
	echo "</tr></table></div>";

	echo "<div align='center'><table class='tab_cadre' cellpadding='5'>";
	echo "<tr><th>" . $LANG["ocsng"][0] . " " . $name . "</th></tr>";

	echo "<tr class='tab_bg_1'><td  align='center'><a href=\"ocsng.sync.php\"><b>" . $LANG["ocsng"][1] . "</b></a></td></tr>";

	echo "<tr class='tab_bg_1'><td align='center'><a href=\"ocsng.import.php\"><b>" . $LANG["ocsng"][2] . "</b></a></td> </tr>";

	echo "<tr class='tab_bg_1'><td align='center'><a href=\"ocsng.link.php\"><b>" . $LANG["ocsng"][4] . "</b></a></td> </tr>";

	echo "<tr class='tab_bg_1'><td align='center'><a href=\"ocsng.clean.php\"><b>" . $LANG["ocsng"][3] . "</b></a></td> </tr>";

	echo "</table></div>";

	ocsManageDeleted($_SESSION["ocs_server_id"]);
} else {
	ocsChooseServer($_SERVER['PHP_SELF']);
}
commonFooter();
?>
