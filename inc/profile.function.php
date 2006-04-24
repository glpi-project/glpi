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
         echo "<td><a class='icon_consol' href='".$HTMLRel."front/profile.php?add=new'>".$lang["profiles"][0]."</a></td>";
	echo "</tr></table></div>";
}


function showProfilesForm($target,$ID){
	global $lang,$cfg_glpi;

	if (!haveRight("profile","r")) return false;

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

	if (!haveRight("profile","r")) return false;

	$prof=new Profile();
	if ($ID){
		$prof->getFromDB($ID);
	} else {
		$prof->getEmpty();
	}

	echo "<table class='tab_cadre'><tr>";
	echo "<th colspan='4'>".$lang["profiles"][3].":&nbsp;&nbsp;".$lang["profiles"][13].":";
	dropdownYesNoInt("is_default",$prof->fields["is_default"]);
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
	echo "<td>".$lang["profiles"][9].":</td><td>";
	dropdownYesNoInt("observe_ticket",$prof->fields["observe_ticket"]);
	echo "</td>";
	echo "<td>&nbsp;</td><td>&nbsp;";
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


	echo "<tr class='tab_bg_1'>";
	if ($ID){
		echo "<td colspan='2' align='center'>";
		echo "<input type='hidden' name='ID' value=$ID>";
		echo "<input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'>";

		echo "</td><td colspan='2' align='center'>";
		echo "<input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'>";

	} else {
		echo "<td colspan='4' align='center'>";
		echo "<input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'>";
	}
	echo "</td></tr>";
	echo "</table>";

}


function showCentralProfilesForm($ID){
	global $lang;

	if (!haveRight("profile","r")) return false;

	$prof=new Profile();
	if ($ID){
		$prof->getFromDB($ID);
	} else {
		$prof->getEmpty();
	}

	echo "<table class='tab_cadre'><tr>";
	echo "<th colspan='6'>".$lang["profiles"][4].":&nbsp;&nbsp;".$lang["profiles"][13].":";
	dropdownYesNoInt("is_default",$prof->fields["is_default"]);
	echo "</th></tr>";

	echo "<tr class='tab_bg_1'><td colspan='6' align='center'><strong>".$lang["setup"][10]."</strong></td></tr>";

	echo "<tr class='tab_bg_2'>";
	echo "<td>".$lang["Menu"][0].":</td><td>";
	dropdownNoneReadWrite("computer",$prof->fields["computer"],1,1,1);
	echo "</td>";
	echo "<td>".$lang["Menu"][3].":</td><td>";
	dropdownNoneReadWrite("monitor",$prof->fields["monitor"],1,1,1);
	echo "</td>";
	echo "<td>".$lang["Menu"][4].":</td><td>";
	dropdownNoneReadWrite("software",$prof->fields["software"],1,1,1);
	echo "</td></tr";

	echo "<tr class='tab_bg_2'>";
	echo "<td>".$lang["Menu"][1].":</td><td>";
	dropdownNoneReadWrite("networking",$prof->fields["networking"],1,1,1);
	echo "</td>";
	echo "<td>".$lang["Menu"][2].":</td><td>";
	dropdownNoneReadWrite("printer",$prof->fields["printer"],1,1,1);
	echo "</td>";
	echo "<td>".$lang["Menu"][21].":</td><td>";
	dropdownNoneReadWrite("cartridge",$prof->fields["cartridge"],1,1,1);
	echo "</td></tr>";

	echo "<tr class='tab_bg_2'>";
	echo "<td>".$lang["Menu"][32].":</td><td>";
	dropdownNoneReadWrite("consumable",$prof->fields["consumable"],1,1,1);
	echo "</td>";
	echo "<td>".$lang["Menu"][34].":</td><td>";
	dropdownNoneReadWrite("phone",$prof->fields["phone"],1,1,1);
	echo "</td>";
	echo "<td>".$lang["Menu"][16].":</td><td>";
	dropdownNoneReadWrite("peripheral",$prof->fields["peripheral"],1,1,1);
	echo "</td>";
	echo "</tr>";

	echo "<tr class='tab_bg_1'><td colspan='6' align='center'><strong>".$lang["Menu"][26]."</strong></td></tr>";

	echo "<tr class='tab_bg_2'>";
	echo "<td>".$lang["Menu"][22]." / ".$lang["Menu"][23].":</td><td>";
	dropdownNoneReadWrite("contact_enterprise",$prof->fields["contact_enterprise"],1,1,1);
	echo "</td>";
	echo "<td>".$lang["Menu"][27].":</td><td>";
	dropdownNoneReadWrite("document",$prof->fields["document"],1,1,1);
	echo "</td>";
	echo "<td>".$lang["Menu"][24]." / ".$lang["Menu"][25].":</td><td>";
	dropdownNoneReadWrite("contract_infocom",$prof->fields["contract_infocom"],1,1,1);
	echo "</td></tr>";


	echo "<tr class='tab_bg_1'><td colspan='6' align='center'><strong>".$lang["title"][24]."</strong></td></tr>";

	echo "<tr class='tab_bg_2'>";
	echo "<td>".$lang["profiles"][5].":</td><td>";
	dropdownYesNoInt("create_ticket",$prof->fields["create_ticket"]);
	echo "</td>";
	echo "<td>".$lang["profiles"][14].":</td><td>";
	dropdownYesNoInt("delete_ticket",$prof->fields["delete_ticket"]);
	echo "</td>";
	echo "<td>".$lang["profiles"][6].":</td><td>";
	dropdownYesNoInt("comment_ticket",$prof->fields["comment_ticket"]);
	echo "</td></tr>";

	echo "<tr class='tab_bg_2'>";
	echo "<td>".$lang["profiles"][15].":</td><td>";
	dropdownYesNoInt("comment_all_ticket",$prof->fields["comment_all_ticket"]);
	echo "</td>";
	echo "<td>".$lang["profiles"][18].":</td><td>";
	dropdownYesNoInt("update_ticket",$prof->fields["update_ticket"]);
	echo "</td>";
	echo "<td>".$lang["profiles"][16].":</td><td>";
	dropdownYesNoInt("own_ticket",$prof->fields["own_ticket"]);
	echo "</td></tr>";

	echo "<tr class='tab_bg_2'>";
	echo "<td>".$lang["profiles"][17].":</td><td>";
	dropdownYesNoInt("steal_ticket",$prof->fields["steal_ticket"]);
	echo "</td>";
	echo "<td>".$lang["profiles"][19].":</td><td>";
	dropdownYesNoInt("assign_ticket",$prof->fields["assign_ticket"]);
	echo "</td>";
	echo "<td>".$lang["profiles"][7].":</td><td>";
	dropdownYesNoInt("show_ticket",$prof->fields["show_ticket"]);
	echo "</td></tr>";

	echo "<tr class='tab_bg_2'>";
	echo "<td>".$lang["profiles"][8].":</td><td>";
	dropdownYesNoInt("show_full_ticket",$prof->fields["show_full_ticket"]);
	echo "</td>";
	echo "<td>".$lang["profiles"][9].":</td><td>";
	dropdownYesNoInt("observe_ticket",$prof->fields["observe_ticket"]);
	echo "</td>";
	echo "<td>".$lang["stats"][19].":</td><td>";
	dropdownYesNoInt("statistic",$prof->fields["statistic"]);
	echo "</td></tr>";

	echo "<tr class='tab_bg_2'>";
	echo "<td>".$lang["profiles"][20].":</td><td>";
	dropdownYesNoInt("show_planning",$prof->fields["show_planning"]);
	echo "</td>";
	echo "<td>".$lang["profiles"][21].":</td><td>";
	dropdownYesNoInt("show_all_planning",$prof->fields["show_all_planning"]);
	echo "</td>";
	echo "<td>&nbsp;</td><td>";
	echo "&nbsp;</td></tr>";


	echo "<tr class='tab_bg_1'><td colspan='6' align='center'><strong>".$lang["Menu"][18]."</strong></td>";
	echo "</tr>";

	echo "<tr class='tab_bg_2'>";
	echo "<td>".$lang["knowbase"][1].":</td><td>";
	dropdownNoneReadWrite("faq",$prof->fields["faq"],1,1,1);
	echo "</td>";
	echo "<td>".$lang["knowbase"][0].":</td><td>";
	dropdownNoneReadWrite("knowbase",$prof->fields["knowbase"],1,1,1);
	echo "</td>";
	echo "<td>".$lang["title"][35].":</td><td>";
	dropdownYesNoInt("reservation_helpdesk",$prof->fields["reservation_helpdesk"]);
	echo "</td></tr>";

	echo "<tr class='tab_bg_2'>";
	echo "<td>".$lang["Menu"][6].":</td><td>";
	dropdownNoneReadWrite("reports",$prof->fields["reports"],1,1,0);
	echo "</td>";
	echo "<td>".$lang["Menu"][33].":</td><td>";
	dropdownNoneReadWrite("ocsng",$prof->fields["ocsng"],1,0,1);
	echo "</td>";
	echo "<td>".$lang["profiles"][23].":</td><td>";
	dropdownNoneReadWrite("reservation_central",$prof->fields["reservation_central"],1,1,1);
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td colspan='6' align='center'><strong>".$lang["Menu"][15]."</strong></td>";
	echo "</tr>";

	echo "<tr class='tab_bg_2'>";
	echo "<td>".$lang["setup"][0].":</td><td>";
	dropdownNoneReadWrite("dropdown",$prof->fields["dropdown"],1,0,1);
	echo "</td>";
	echo "<td>".$lang["setup"][222].":</td><td>";
	dropdownNoneReadWrite("device",$prof->fields["device"],1,0,1);
	echo "</td>";
	echo "<td>".$lang["document"][7].":</td><td>";
	dropdownNoneReadWrite("typedoc",$prof->fields["typedoc"],1,1,1);
	echo "</td></tr>";

	echo "<tr class='tab_bg_2'>";
	echo "<td>".$lang["setup"][87].":</td><td>";
	dropdownNoneReadWrite("link",$prof->fields["link"],1,1,1);
	echo "</td>";
	echo "<td>".$lang["title"][2].":</td><td>";
	dropdownNoneReadWrite("config",$prof->fields["config"],1,0,1);
	echo "</td>";
	echo "<td>".$lang["setup"][250].":</td><td>";
	dropdownNoneReadWrite("search_config",$prof->fields["search_config"],1,0,1);
	echo "</td></tr>";


	echo "<tr class='tab_bg_2'>";
	echo "<td>".$lang["setup"][306].":</td><td>";
	dropdownYesNoInt("update",$prof->fields["update"]);
	echo "</td>";
	echo "<td>".$lang["Menu"][14].":</td><td>";
	dropdownNoneReadWrite("user",$prof->fields["user"],1,1,1);
	echo "</td>";
	echo "<td>".$lang["Menu"][35].":</td><td>";
	dropdownNoneReadWrite("profile",$prof->fields["profile"],1,1,1);
	echo "</td></tr>";

	echo "<tr class='tab_bg_2'>";
	echo "<td>".$lang["Menu"][30].":</td><td>";
	dropdownNoneReadWrite("logs",$prof->fields["logs"],1,1,0);
	echo "</td>";
	echo "<td>".$lang["Menu"][12].":</td><td>";
	dropdownNoneReadWrite("backup",$prof->fields["backup"],1,0,1);
	echo "</td>";
	echo "<td>".$lang["reminder"][1].":</td><td>";
	dropdownNoneReadWrite("reminder_public",$prof->fields["reminder_public"],1,0,1);
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'>";
	if ($ID){
		echo "<td colspan='3' align='center'>";
		echo "<input type='hidden' name='ID' value=$ID>";
		echo "<input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'>";
		echo "</td><td colspan='3' align='center'>";
		echo "<input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'>";
	} else {
		echo "<td colspan='6' align='center'>";
		echo "<input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'>";
	}
	echo "</td></tr>";
	echo "</table>";

}

?>
