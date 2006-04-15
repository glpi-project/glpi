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

include ("_relpos.php");
// FUNCTIONS peripheral


function titleperipherals(){
	global  $lang,$HTMLRel;
	echo "<div align='center'><table border='0'><tr><td>";
	echo "<img src=\"".$HTMLRel."pics/periphs.png\" alt='".$lang["peripherals"][0]."' title='".$lang["peripherals"][0]."'></td><td><a  class='icon_consol' href=\"".$HTMLRel."setup/setup-templates.php?type=".PERIPHERAL_TYPE."&amp;add=1\"><b>".$lang["peripherals"][0]."</b></a>";
	echo "</td>";
	echo "<td><a class='icon_consol' href='".$HTMLRel."setup/setup-templates.php?type=".PERIPHERAL_TYPE."&amp;add=0'>".$lang["common"][8]."</a></td>";
	echo "</tr></table></div>";
}

function showperipheralForm ($target,$ID,$withtemplate='') {

	global $cfg_glpi, $lang,$HTMLRel;

	if (!haveRight("peripheral","r")) return false;

	$mon = new Peripheral;

	$mon_spotted = false;

	if(empty($ID) && $withtemplate == 1) {
		if($mon->getEmpty()) $mon_spotted = true;
	} else {
		if($mon->getfromDB($ID)) $mon_spotted = true;
	}

	if($mon_spotted) {
		if(!empty($withtemplate) && $withtemplate == 2) {
			$template = "newcomp";
			$datestring = $lang["computers"][14].": ";
			$date = convDateTime(date("Y-m-d H:i:s"));
		} elseif(!empty($withtemplate) && $withtemplate == 1) { 
			$template = "newtemplate";
			$datestring = $lang["computers"][14].": ";
			$date = convDateTime(date("Y-m-d H:i:s"));
		} else {
			$datestring = $lang["common"][26].": ";
			$date = convDateTime($mon->fields["date_mod"]);
			$template = false;
		}


	echo "<div align='center'>";
	echo "<form method='post' name=form action=\"$target\">";
	if(strcmp($template,"newtemplate") === 0) {
		echo "<input type=\"hidden\" name=\"is_template\" value=\"1\" />";
	}

	echo "<table  class='tab_cadre_fixe' cellpadding='2'>";

		echo "<tr><th align='center' >";

		
		
		if(!$template) {
			echo $lang["peripherals"][29].": ".$mon->fields["ID"];
		}elseif (strcmp($template,"newcomp") === 0) {
			echo $lang["peripherals"][30].": ".$mon->fields["tplname"];
			echo "<input type='hidden' name='tplname' value='".$mon->fields["tplname"]."'>";
		}elseif (strcmp($template,"newtemplate") === 0) {
			echo $lang["common"][6]."&nbsp;: ";
			autocompletionTextField("tplname","glpi_peripherals","tplname",$mon->fields["tplname"],20);	
		}
		
		echo "</th><th  align='center'>".$datestring.$date;
		if (!$template&&!empty($mon->fields['tplname']))
			echo "&nbsp;&nbsp;&nbsp;(".$lang["common"][13].": ".$mon->fields['tplname'].")";
		echo "</th></tr>";

	echo "<tr><td class='tab_bg_1' valign='top'>";

	echo "<table cellpadding='1' cellspacing='0' border='0'>\n";

	echo "<tr><td>".$lang["common"][16].":	</td>";
	echo "<td>";
	autocompletionTextField("name","glpi_peripherals","name",$mon->fields["name"],20);		
	echo "</td></tr>";

	echo "<tr><td>".$lang["common"][15].": 	</td><td>";
		dropdownValue("glpi_dropdown_locations", "location", $mon->fields["location"]);
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["common"][10].": 	</td><td colspan='2'>";
		dropdownUsersID("tech_num", $mon->fields["tech_num"]);
	echo "</td></tr>";
		
	echo "<tr><td>".$lang["common"][21].":	</td><td>";
	autocompletionTextField("contact_num","glpi_peripherals","contact_num",$mon->fields["contact_num"],20);		
	echo "</td></tr>";

	echo "<tr><td>".$lang["common"][18].":	</td><td>";
	autocompletionTextField("contact","glpi_peripherals","contact",$mon->fields["contact"],20);		
	echo "</td></tr>";

		if (!$template){
		echo "<tr><td>".$lang["reservation"][24].":</td><td><b>";
		showReservationForm(PERIPHERAL_TYPE,$ID);
		echo "</b></td></tr>";
		}
		
	echo "<tr><td>".$lang["peripherals"][33].":</td><td>";
	echo "<select name='is_global'>";
	echo "<option value='0' ".(!$mon->fields["is_global"]?" selected":"").">".$lang["peripherals"][32]."</option>";
	echo "<option value='1' ".($mon->fields["is_global"]?" selected":"").">".$lang["peripherals"][31]."</option>";
	echo "</select>";
	echo "</td></tr>";
	echo "</table>";

	echo "</td>\n";	
	echo "<td class='tab_bg_1' valign='top'>";

	echo "<table cellpadding='1' cellspacing='0' border='0'>";

	echo "<tr><td>".$lang["common"][17].": 	</td><td>";
		dropdownValue("glpi_type_peripherals", "type", $mon->fields["type"]);
	echo "</td></tr>";

	echo "<tr><td>".$lang["common"][22].": 	</td><td>";
		dropdownValue("glpi_dropdown_model_peripherals", "model", $mon->fields["model"]);
	echo "</td></tr>";
	
	echo "<tr class='tab_bg_1'><td>".$lang["common"][5].": 	</td><td colspan='2'>";
		dropdownValue("glpi_enterprises","FK_glpi_enterprise",$mon->fields["FK_glpi_enterprise"]);
	echo "</td></tr>";
		
	echo "<tr><td>".$lang["peripherals"][18].":</td><td>";
	autocompletionTextField("brand","glpi_peripherals","brand",$mon->fields["brand"],20);		
	echo "</td></tr>";

	
	echo "<tr><td>".$lang["common"][19].":	</td><td>";
	autocompletionTextField("serial","glpi_peripherals","serial",$mon->fields["serial"],20);		
	echo "</td></tr>";

	echo "<tr><td>".$lang["common"][20].":</td><td>";
	autocompletionTextField("otherserial","glpi_peripherals","otherserial",$mon->fields["otherserial"],20);		
	echo "</td></tr>";

		
		echo "<tr><td>".$lang["state"][0].":</td><td>";
		$si=new StateItem();
		$t=0;
		if ($template) $t=1;
		$si->getfromDB(PERIPHERAL_TYPE,$mon->fields["ID"],$t);
		dropdownValue("glpi_dropdown_state", "state",$si->fields["state"]);
		echo "</td></tr>";
		

	
	echo "</table>";
	echo "</td>\n";	
	echo "</tr>";
	echo "<tr>";
	echo "<td class='tab_bg_1' valign='top' colspan='2'>";

	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'><tr><td valign='top'>";
	echo $lang["common"][25].":	</td>";
	echo "<td align='center'><textarea cols='35' rows='4' name='comments' >".$mon->fields["comments"]."</textarea>";
	echo "</td></tr></table>";

	echo "</td>";
	echo "</tr>";
	
	echo "<tr>";

	if ($template) {

			if (empty($ID)||$withtemplate==2){
			echo "<td class='tab_bg_2' align='center' colspan='2'>\n";
			echo "<input type='hidden' name='ID' value=$ID>";
			echo "<input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'>";
			echo "</td>\n";
			} else {
			echo "<td class='tab_bg_2' align='center' colspan='2'>\n";
			echo "<input type='hidden' name='ID' value=$ID>";
			echo "<input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'>";
			echo "</td>\n";
			}


	} else {

		echo "<td class='tab_bg_2' valign='top' align='center'>";
		echo "<input type='hidden' name='ID' value=\"$ID\">\n";
		echo "<input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'>";
		echo "</td>";
		echo "<td class='tab_bg_2' valign='top'>\n";
		echo "<div align='center'>";
		if ($mon->fields["deleted"]=='N')
		echo "<input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'>";
		else {
		echo "<input type='submit' name='restore' value=\"".$lang["buttons"][21]."\" class='submit'>";
		
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' name='purge' value=\"".$lang["buttons"][22]."\" class='submit'>";
		}
		echo "</div>";
		echo "</td>";
	}
		echo "</tr>";

		echo "</table></form></div>";
	
		return true;	
	}
	else {
                echo "<div align='center'><b>".$lang["peripherals"][17]."</b></div>";
                return false;
        }

}

 	
?>
