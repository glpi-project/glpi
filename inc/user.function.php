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

function titleUsers(){
                
		// Un titre pour la gestion des users
		
		global  $lang,$HTMLRel;
                echo "<div align='center'><table border='0'><tr><td>";
                echo "<img src=\"".$HTMLRel."pics/users.png\" alt='".$lang["setup"][2]."' title='".$lang["setup"][2]."'></td>";
                echo "<td><a  class='icon_consol' href=\"user.form.php?new=1\"><b>".$lang["setup"][2]."</b></a></td>";
                if (useAuthExt())
	                echo "<td><a  class='icon_consol' href=\"user.form.php?new=1&ext_auth=1\"><b>".$lang["setup"][125]."</b></a></td>";
                echo "</tr></table></div>";
}
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

function showUserinfo($target,$ID) {
	
	// Affiche les infos User
	
	global $cfg_glpi, $lang;

	if (!haveRight("user","r")) return false;
	
	$user = new User();
	
	
	$user->getFromDB($ID);
	$prof=new Profile();
	$prof->getFromDBForUser($ID);
	
	
	echo "<div align='center'>";
		echo "<table class='tab_cadre'>";
		echo   "<tr><th colspan='2'>".$lang["setup"][57]." : " .$user->fields["name"]."</th></tr>";
		echo "<tr class='tab_bg_1'>";	
		
			echo "<td align='center'>".$lang["setup"][18]."</td>";
			
			echo "<td align='center'><b>".$user->fields["name"]."</b></td></tr>";
									
			echo "<tr class='tab_bg_1'><td align='center'>".$lang["setup"][13]."</td><td>".$user->fields["realname"]."</td></tr>";

			echo "<tr class='tab_bg_1'><td align='center'>".$lang["profiles"][22]."</td><td>".$prof->fields["name"]."</td></tr>";	
			echo "<tr class='tab_bg_1'><td align='center'>".$lang["setup"][14]."</td><td>".$user->fields["email"]."</td></tr>";
			echo "<tr class='tab_bg_1'><td align='center'>".$lang["setup"][15]."</td><td>".$user->fields["phone"]."</td></tr>";
			echo "<tr class='tab_bg_1'><td align='center'>".$lang["setup"][16]."</td><td>";
			echo getDropdownName("glpi_dropdown_locations",$user->fields["location"]);
			echo "</td></tr>";
			echo "<tr class='tab_bg_1'><td align='center'>".$lang["setup"][400]."</td><td>".($user->fields["active"]?$lang["choice"][1]:$lang["choice"][0])."</td></tr>";
	echo "</table></div>";

	echo "<div align='center' ><p><b>".$lang["tracking"][11]."</b></p></div>";
	
}




function showUserform($target,$name) {
	
	// Affiche un formulaire User
	global $cfg_glpi, $lang;

	if (!haveRight("user","r")) return false;
	
	$user = new User();
	if($name == 'Helpdesk') {
		echo "<div align='center'>";
		echo $lang["setup"][220];
		echo "</div>";
		return 0;
	}
	if(empty($name)) {
		// Partie ajout d'un user
		// il manque un getEmpty pour les users	
		$user->getEmpty();
	} else {
		$user->getfromDBbyName($name);
		
	}
	echo "<div align='center'>";
	echo "<form method='post' name=\"user_manager\" action=\"$target\"><table class='tab_cadre'>";
	echo "<tr><th colspan='2'>".$lang["setup"][57]." : " .$user->fields["name"]."</th></tr>";
	echo "<tr class='tab_bg_1'>";	
	echo "<td align='center'>".$lang["setup"][18]."</td>";
	// si on est dans le cas d'un ajout , cet input ne doit plus être hiden
	if ($name=="") {
		echo "<td><input  name='name' value=\"".$user->fields["name"]."\">";
		echo "</td></tr>";
	// si on est dans le cas d'un modif on affiche la modif du login si ce n'est pas une auth externe
	} else {
		if (empty($user->fields["password"])&&empty($user->fields["password_md5"])){
			echo "<td align='center'><b>".$user->fields["name"]."</b>";
			echo "<input type='hidden' name='name' value=\"".$user->fields["name"]."\">";
			}
		else {
			echo "<td>";
			autocompletionTextField("name","glpi_users","name",$user->fields["name"],20);
		}
		
		
		echo "<input type='hidden' name='ID' value=\"".$user->fields["ID"]."\">";
		
		echo "</td></tr>";
	}
	//do some rights verification
	if(haveRight("user","w")) {
		if (!empty($user->fields["password"])||!empty($user->fields["password_md5"])||$name==""){
			echo "<tr class='tab_bg_1'><td align='center'>".$lang["setup"][19]."</td><td><input type='password' name='password' value=\"".$user->fields["password"]."\" size='20' /></td></tr>";
		}
	}

	echo "<tr class='tab_bg_1'><td align='center'>".$lang["setup"][13]."</td><td>";
	autocompletionTextField("realname","glpi_users","realname",$user->fields["realname"],20);
	echo "</td></tr>";

	echo "</td></tr>";	
	echo "<tr class='tab_bg_1'><td align='center'>".$lang["profiles"][22]."</td><td>";
	$prof=new Profile();
	$prof->getFromDBforUser($user->fields["ID"]);
	dropdownValue("glpi_profiles","profile",$prof->fields["ID"]);
	echo "</td></tr>";
	echo "<tr class='tab_bg_1'><td align='center'>".$lang["setup"][14]."</td><td>";
	autocompletionTextField("email_form","glpi_users","email",$user->fields["email"],20);
	echo "</td></tr>";
	echo "<tr class='tab_bg_1'><td align='center'>".$lang["setup"][15]."</td><td>";
	autocompletionTextField("phone","glpi_users","phone",$user->fields["phone"],20);
	echo "</td></tr>";
	echo "<tr class='tab_bg_1'><td align='center'>".$lang["setup"][16]."</td><td>";
	dropdownValue("glpi_dropdown_locations", "location", $user->fields["location"]);
	echo "</td></tr>";
	echo "<tr class='tab_bg_1'><td align='center'>".$lang["setup"][400]."</td><td>";
	$active=0;
	if ($user->fields["active"]==""||$user->fields["active"]) $active=1;
	echo "<select name='active'>";
	echo "<option value='1' ".($active?" selected ":"").">".$lang["choice"][1]."</option>";
	echo "<option value='0' ".(!$active?" selected ":"").">".$lang["choice"][0]."</option>";
	
	echo "</select>";
	echo "</td></tr>";

	if (haveRight("user","w"))
	if ($name=="") {
		echo "<tr >";
		echo "<td class='tab_bg_2' valign='top' colspan='2' align='center'>";
		echo "<input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'>";
		echo "</td>";
		echo "</tr>";	
	} else {
		echo "<tr>";
		echo "<td class='tab_bg_2' valign='top' align='center'>";	
		echo "<input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit' >";
		echo "</td>";
		echo "<td class='tab_bg_2' valign='top' align='center'>\n";
		echo "<input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit' >";
		echo "</td>";
		echo "</tr>";
	}

	echo "</table></form></div>";
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
