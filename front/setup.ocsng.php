<?php


/*
 * @version $Id: setup.config.php 4050 2006-10-27 15:32:57Z moyo $
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

$NEEDED_ITEMS = array (
	"setup",
	"auth",
	"ocsng",
	"user"
);

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

if(empty($tab) && isset($_POST)) $tab = $_POST;
if(!isset($tab["withtemplate"])) $tab["withtemplate"] = "";

checkRight("config", "w");
$ocs = new Ocsng();

if (!isset ($_GET["ID"]))
	$_GET["ID"] = "";

commonHeader($LANG["title"][39], $_SERVER['PHP_SELF'], "admin");

if (isset($_GET["next"])) {
	
	if ($_GET["next"] == "ocsng")
	showFormOCSNGList($_SERVER['PHP_SELF']);
elseif ($_GET["next"] == "ocsng_show") {
	titleOCSNG();	
	$ocs->showForm($_SERVER['PHP_SELF'], $_GET["ID"]);
}
}
elseif (isset ($_POST["update_ocs_server"])) {
	$ocs->update($_POST);
	//glpi_header($_SERVER['HTTP_REFERER']);
	$ocs->showForm($_SERVER['PHP_SELF'], $_POST["ID"]);
}
elseif (isset ($_POST["add_ocs_server"])) {
	//If no name has been given to this configuration, then go back to the page without adding
	if ($_POST["name"] != "")
		$newid = $ocs->add($_POST);
	titleOCSNG();
	$ocs->showForm($_SERVER['PHP_SELF'], $newid);
	//glpi_header($CFG_GLPI["root_doc"] . "/front/setup.ocsng.php?next=ocsng");
}
elseif (isset ($_POST["delete_ocs_server"])) {
	$ocs->delete($_POST);
	glpi_header($CFG_GLPI["root_doc"] . "/front/setup.ocsng.php?next=ocsng");
}
elseif (isset ($_GET["withtemplate"])) {
	$ocs->ocsFormConfig($_SERVER['PHP_SELF'],$_GET["withtemplate"],1);
}

commonFooter();

function titleOCSNG() {
	// Un titre pour la gestion des sources externes

	global $LANG, $CFG_GLPI;

	displayTitle($CFG_GLPI["root_doc"] . "/pics/logoOcs2.png", $LANG["ocsng"][0], $LANG["ocsng"][0]);

}

function showFormOCSNGList($target) {

	global $DB, $LANG, $CFG_GLPI;
	
	
	if (!haveRight("config", "w"))
		return false;


	$buttons["setup.templates.php?type=".OCSNG_TYPE."&amp;add=1"]=$LANG["ocsng"][25];
	$buttons["setup.templates.php?type=".OCSNG_TYPE."&amp;add=0"]=$LANG["common"][8];
	$title="";
	displayTitle($CFG_GLPI["root_doc"]."/pics/logoOcs2.png",$LANG["Menu"][0],$title,$buttons);

	echo "<div align='center'>";

	echo "<form name=ldap action=\"$target?next=ocsng_show\" method=\"post\">";
	echo "<input type='hidden' name='ID' value='" . $CFG_GLPI["ID"] . "'>";

	echo "<table class='tab_cadre_fixe' cellpadding='5'>";
	echo "<tr><th colspan='2'>" . $LANG["Menu"][33] . "</th></tr>";
	echo "<tr class='tab_bg_1'><td align='center'>" . $LANG["common"][16] . "</td><td align='center'>" . $LANG["common"][52] . "</td></tr>";

	$sql = "SELECT * from glpi_ocs_config ORDER BY name";
	$result = $DB->query($sql);
	if ($DB->numrows($result)) {
		while ($ocs = $DB->fetch_array($result))
			echo "<tr class='tab_bg_2'><td align='center'><a href='" . $CFG_GLPI["root_doc"] . "/front/setup.ocsng.php?next=ocsng_show&amp;ID=" . $ocs["ID"] . "' >" . $ocs["name"] . "</a>" .
			"</td><td align='center'>" . $ocs["ocs_db_host"] . "</td></tr>";
	}
	echo "<tr><td  align='center' class='tab_bg_1' colspan='2'><input type=\"submit\" name=\"new\" class=\"submit\" value=\"" . $LANG["buttons"][8] . "\" ></td></tr>";
	echo "</table>";
	echo "</form>";
	echo "</div>";
}
?>
