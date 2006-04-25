<?php
/*
 * @version $Id$
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

// Based on:
// IRMA, Information Resource-Management and Administration
// Christian Bauer 
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

function showPasswordForm($target,$name) {

	global $cfg_glpi, $lang;
	
	$user = new User();
	if ($user->getFromDBbyName($name)){
		echo "<form method='post' action=\"$target\">";
		echo "<div align='center'>&nbsp;<table class='tab_cadre' cellpadding='5' width='30%'>";
		echo "<tr><th colspan='2'>".$lang["setup"][11]." '".$user->fields["name"]."':</th></tr>";
		echo "<tr><td width='100%' align='center' class='tab_bg_1'>";
		echo "<input type='password' name='password' size='10'>";
		echo "</td><td align='center' class='tab_bg_2'>";
		echo "<input type='hidden' name='name' value=\"".$name."\">";
		echo "<input type='submit' name='changepw' value=\"".$lang["buttons"][14]."\" class='submit'>";
		echo "</td></tr>";
		echo "</table></div>";
		echo "</form>";
	}
}








function showLangSelect($target) {

	global $cfg_glpi, $lang;
	
	$l = $_SESSION["glpilanguage"]; 
	
	echo "<form method='post' action=\"$target\">";
	echo "<div align='center'>&nbsp;<table class='tab_cadre' cellpadding='5' width='30%'>";
	echo "<tr><th colspan='2'>".$lang["setup"][41].":</th></tr>";
	echo "<tr><td width='100%' align='center' class='tab_bg_1'>";
	echo "<select name='language'>";

	while (list($cle)=each($cfg_glpi["languages"])){
		echo "<option value=\"".$cle."\"";
			if ($l==$cle) { echo " selected"; }
		echo ">".$cfg_glpi["languages"][$cle][0]." ($cle)";
	}
	echo "</select>";
	echo "</td>";
	echo "<td align='center' class='tab_bg_2'>";
	echo "<input type='submit' name='changelang' value=\"".$lang["buttons"][14]."\" class='submit'>";
	echo "<input type='hidden' name='ID' value=\"".$_SESSION["glpiID"]."\">";
	echo "</td></tr>";
	echo "</table></div>";
	echo "</form>";
}

function showSortForm($target) {

	global $cfg_glpi, $lang;
	
	$order = $_SESSION["glpitracking_order"];
	
	echo "<div align='center'>\n";
	echo "<form method='post' action=\"$target\">\n";

	echo "<table class='tab_cadre' cellpadding='5' width='30%'>\n";
	echo "<tr><th colspan='2'>".$lang["setup"][40]."</th></tr>\n";
	echo "<tr><td width='100%' align='center' class='tab_bg_1'>\n";
	echo "<select name='tracking_order'>\n";
	echo "<option value=\"yes\"";
	if ($order=="yes") { echo " selected"; }	
	echo ">".$lang["choice"][1];
	echo "<option value=\"no\"";
	if ($order=="no") { echo " selected"; }
	echo ">".$lang["choice"][0];
	echo "</select>\n";
	echo "</td>\n";
	echo "<td align='center' class='tab_bg_2'>\n";
	echo "<input type='hidden' name='ID' value=\"".$_SESSION["glpiID"]."\">";
	echo "<input type='submit' name='updatesort' value=\"".$lang["buttons"][14]."\" class='submit'>\n";
	echo "</td></tr>\n";
	echo "</table>";
	echo "</form>\n";

	echo "</div>\n";
}

function showAddExtAuthUserForm($target){
	global $lang;
	
	if (!haveRight("user","w")) return false;


	echo "<div align='center'>\n";
	echo "<form method='get' action=\"$target\">\n";

	echo "<table class='tab_cadre' cellpadding='5'>\n";
	echo "<tr><th colspan='3'>".$lang["setup"][126]."</th></tr>\n";
	echo "<tr class='tab_bg_1'><td>".$lang["login"][6]."</td>\n";
	echo "<td>";
	echo "<input type='text' name='login'>";
	echo "</td>";
	echo "<td align='center' class='tab_bg_2' rowspan='2'>\n";
	echo "<input type='hidden' name='ext_auth' value='1'>\n";
	echo "<input type='submit' name='add_ext_auth' value=\"".$lang["buttons"][8]."\" class='submit'>\n";
	echo "</td></tr>\n";
	
	echo "</table>";
	echo "</form>\n";

	echo "</div>\n";
	
}

function dropdownUserType($myname,$value="post-only"){
		echo "<select name='$myname' >";
		echo "<option value=\"post-only\"";
		if ($value=="post-only") { echo " selected"; }
		echo ">Post Only</option>";
		echo "<option value=normal";
		if ($value=="normal") { echo " selected"; }
		echo ">Normal</option>";
		echo "<option value='admin'";
		if ($value=="admin") { echo " selected"; }
		echo ">Admin</option>";
		echo "<option value='super-admin'";
		if ($value=="super-admin") { echo " selected"; }
		echo ">Super-Admin</option>";
		echo "</select>";
	
}

function useAuthExt(){
global $cfg_glpi;	
return (!empty($cfg_glpi["imap_auth_server"])||!empty($cfg_glpi["ldap_host"])||!empty($cfg_glpi["cas_host"]));
}
?>
