<?php
/*
 * @version $Id: functions.php 3070 2006-04-07 21:27:33Z moyo $
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

/**
* Print a good title for profiles pages
*
*
*
*
*@return nothing (diplays)
*
**/
function titleProfiles(){
              //titre
              
        GLOBAL  $lang,$HTMLRel;

        echo "<div align='center'><table border='0'><tr><td>";
        echo "<img src=\"".$HTMLRel."pics/preferences.png\" alt='".$lang["Menu"][35]."' title='".$lang["Menu"][35]."'></td><td><span class='icon_sous_nav'><b>".$lang["Menu"][35]."</b></span>";
        echo "</td>";
         echo "<td><a class='icon_consol' href='".$HTMLRel."profiles/index.php?add=new'>".$lang["profiles"][0]."</a></td>";
	echo "</tr></table></div>";
}


function showProfilesForm($target,$ID){
	global $lang,$cfg_glpi;
	$prof=new Profile();
	$onfocus="";
	if ($ID){
		$prof->getFromDB($ID);
	} else {
		$prof->getEmpty();
		$onfocus="onfocus=\"this.value=''\"";
	}

	if (empty($prof->fields["interface"])) $prof->fields["interface"]="helpdesk";
	if (empty($prof->fields["name"])) $prof->fields["name"]=$lang["common"][0];


	echo "<form name='form' method='post' action=\"$target\">";
	echo "<div align='center'>";
	echo "<table class='tab_cadre'><tr>";
	echo "<th>".$lang["common"][16].":</th>";
	echo "<th><input type='text' name='name' value=\"".$prof->fields["name"]."\" $onfocus></th>";
	echo "<th>".$lang["profiles"][2].":</th>";
	echo "<th><select name='interface' id='profile_interface'>";
	echo "<option value='helpdesk' ".($prof->fields["interface"]!="helpdesk"?"selected":"").">".$lang["Menu"][31]."</option>";
	echo "<option value='central' ".($prof->fields["interface"]!="central"?"selected":"").">".$lang["title"][0]."</option>";
	echo "</select></th>";
	echo "</tr></table>";
	echo "</div>";

	echo "<script type='text/javascript' >\n";
	echo "   new Form.Element.Observer('profile_interface', 1, \n";
	echo "      function(element, value) {\n";
	echo "      	new Ajax.Updater('profile_form','".$cfg_glpi["root_doc"]."/ajax/profiles.php',{asynchronous:true, evalScripts:true, \n";
	echo "           method:'post', parameters:'interface=' + value+'&ID=$ID'\n";
	echo "})});\n";
	echo "document.getElementById('profile_interface').value='".$prof->fields["interface"]."';";
	echo "</script>\n";
	echo "<br>";

	echo "<div align='center' id='profile_form'>";
	echo "</div>";

	echo "</form>";

}


function showHelpdeskProfilesForm($ID){
	global $lang;
	$prof=new Profile();
	if ($ID){
		$prof->getFromDB($ID);
	} else {
		$prof->getEmpty();
	}

	echo "<table class='tab_cadre'><tr>";
	echo "<th colspan='4'>".$lang["profiles"][3].":&nbsp;&nbsp;".$lang["profiles"][13].":";
	dropdownYesNoInt("default",$prof->fields["default"]);
	echo "</th></tr>";
	echo "<tr class='tab_bg_1'><td colspan='4' align='center'><strong>".$lang["title"][24]."</strong></td></tr>";

	echo "<tr class='tab_bg_2'>";
	echo "<td>".$lang["profiles"][5].":</td><td>";
	dropdownYesNoInt("create_ticket",$prof->fields["create_ticket"]);
	echo "</td>";
	echo "<td>".$lang["profiles"][6].":</td><td>";
	dropdownYesNoInt("comment_ticket",$prof->fields["comment_ticket"]);
	echo "</td></tr>";

	echo "<tr class='tab_bg_2'>";
	echo "<td>".$lang["profiles"][7].":</td><td>";
	dropdownYesNoInt("show_ticket",$prof->fields["show_ticket"]);
	echo "</td>";
	echo "<td>".$lang["profiles"][9].":</td><td>";
	dropdownYesNoInt("observe_ticket",$prof->fields["observe_ticket"]);
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td colspan='4' align='center'><strong>".$lang["Menu"][18]."</strong></td>";
	echo "</tr>";


	echo "<tr class='tab_bg_2'>";
	echo "<td>".$lang["knowbase"][1].":</td><td>";
	dropdownNoneReadWrite("faq",$prof->fields["faq"],1,1,0);
	echo "</td>";
	echo "<td>".$lang["title"][35].":</td><td>";
	dropdownYesNoInt("reservation_helpdesk",$prof->fields["reservation_helpdesk"]);
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td colspan='4' align='center'>";
	if ($ID){
		echo "<input type='hidden' name='ID' value=$ID>";
		echo "<input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'>";
	} else {
		echo "<input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'>";
	}
	echo "</td></tr>";
	echo "</table>";

}

?>
