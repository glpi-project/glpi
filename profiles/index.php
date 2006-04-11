<?php
/*
 * @version $Id: functions_display.php 3110 2006-04-10 10:15:23Z silvermat $
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.
 
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

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_profiles.php");

checkAuthentication("super-admin");

commonHeader($lang["Menu"][35],$_SERVER["PHP_SELF"]);
titleProfiles();

if(!isset($_POST["ID"])) $ID=0;
else $ID=$_POST["ID"];

$prof=new Profile();

if (isset($_POST["update"])){
	$prof->update($_POST);
}else if (isset($_POST["add"])){
	$ID=$prof->add($_POST);
}

	echo "<div align='center'><form method='post' action=\"".$cfg_glpi["root_doc"]."/profiles/index.php\">";
	echo "<table class='tab_cadre' cellpadding='5'><tr><th colspan='2'>";
	echo $lang["profiles"][1].": </th></tr><tr class='tab_bg_1'><td>";

	$query="SELECT ID, name FROM glpi_profiles ORDER BY name";
	$result=$db->query($query);

	echo "<select name='ID'>";
	while ($data=$db->fetch_assoc($result)){
		echo "<option value='".$data["ID"]."' ".($ID==$data["ID"]?"selected":"").">".$data['name']."</option>";
	}
	echo "</select>";
	echo "<td><input type='submit' value=\"".$lang["buttons"][2]."\" class='submit' ></td></tr>";
	echo "</table></form></div>";


if(isset($_POST["ID"])){
	showProfilesForm($_SERVER["PHP_SELF"],$_POST["ID"]);
} else if (isset($_GET["add"])){
	showProfilesForm($_SERVER["PHP_SELF"],0);
}

commonFooter();


?>