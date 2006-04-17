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
// FUNCTIONS Printers 
//fonction imprimantes

function titlePrinters(){
	global  $lang,$HTMLRel;

	echo "<div align='center'><table border='0'><tr><td>";

	echo "<img src=\"".$HTMLRel."pics/printer.png\" alt='".$lang["printers"][0]."' title='".$lang["printers"][0]."'></td>";
	if (haveRight("printer","w")){
		echo "<td><a  class='icon_consol' href=\"".$HTMLRel."setup/setup-templates.php?type=".PRINTER_TYPE."&amp;add=1\"><b>".$lang["printers"][0]."</b></a></td>";
		echo "<td><a class='icon_consol'  href='".$HTMLRel."setup/setup-templates.php?type=".PRINTER_TYPE."&amp;add=0'>".$lang["common"][8]."</a></td>";
		echo "</tr></table></div>";
	} else echo "<td><span class='icon_sous_nav'><b>".$lang["Menu"][2]."</b></span></td>";
}

function showPrintersForm ($target,$ID,$withtemplate='') {

	global $cfg_glpi, $lang,$HTMLRel;
	if (!haveRight("printer","r")) return false;

	$printer = new Printer;

	$printer_spotted = false;

	if(empty($ID) && $withtemplate == 1) {
		if($printer->getEmpty()) $printer_spotted = true;
	} else {
		if($printer->getfromDB($ID)) $printer_spotted = true;
	}

	if($printer_spotted) {
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
			$date = convDateTime($printer->fields["date_mod"]);
			$template = false;
		}


	echo "<div align='center' ><form method='post' name='form' action=\"$target\">\n";
		if(strcmp($template,"newtemplate") === 0) {
			echo "<input type=\"hidden\" name=\"is_template\" value=\"1\" />\n";
		}

	echo "<table class='tab_cadre_fixe' cellpadding='2'>\n";

		echo "<tr><th align='center' >\n";
		if(!$template) {
			echo $lang["printers"][29].": ".$printer->fields["ID"];
		}elseif (strcmp($template,"newcomp") === 0) {
			echo $lang["printers"][28].": ".$printer->fields["tplname"];
			echo "<input type='hidden' name='tplname' value='".$printer->fields["tplname"]."'>";
		}elseif (strcmp($template,"newtemplate") === 0) {
			echo $lang["common"][6]."&nbsp;: ";
				autocompletionTextField("tplname","glpi_printers","tplname",$printer->fields["tplname"],20);		
		}
		
		echo "</th><th  align='center'>".$datestring.$date;
		if (!$template&&!empty($printer->fields['tplname']))
			echo "&nbsp;&nbsp;&nbsp;(".$lang["common"][13].": ".$printer->fields['tplname'].")";
		echo "</th></tr>\n";


	echo "<tr><td class='tab_bg_1' valign='top'>\n";

	// table identification
	echo "<table cellpadding='1' cellspacing='0' border='0'>\n";
	echo "<tr><td>".$lang["common"][16].":	</td>\n";
	echo "<td>";
	autocompletionTextField("name","glpi_printers","name",$printer->fields["name"],20);		
	echo "</td></tr>\n";

	echo "<tr><td>".$lang["common"][15].": 	</td><td>\n";
		dropdownValue("glpi_dropdown_locations", "location", $printer->fields["location"]);
	echo "</td></tr>\n";

	echo "<tr class='tab_bg_1'><td>".$lang["common"][5].": 	</td><td colspan='2'>\n";
		dropdownValue("glpi_enterprises","FK_glpi_enterprise",$printer->fields["FK_glpi_enterprise"]);
	echo "</td></tr>\n";
	
	echo "<tr class='tab_bg_1'><td>".$lang["common"][10].": 	</td><td colspan='2'>\n";
		dropdownUsersID("tech_num", $printer->fields["tech_num"],"interface");
	echo "</td></tr>\n";
	
	echo "<tr><td>".$lang["common"][21].":	</td><td>\n";
	autocompletionTextField("contact_num","glpi_printers","contact_num",$printer->fields["contact_num"],20);			
	echo "</td></tr>\n";

	echo "<tr><td>".$lang["printers"][8].":	</td><td>\n";
	autocompletionTextField("contact","glpi_printers","contact",$printer->fields["contact"],20);			
	echo "</td></tr>\n";
	if (!$template){
		echo "<tr><td>".$lang["reservation"][24].":</td><td><b>\n";
		showReservationForm(PRINTER_TYPE,$ID);
		echo "</b></td></tr>\n";
	}
		
		echo "<tr><td>".$lang["state"][0].":</td><td>\n";
		$si=new StateItem();
		$t=0;
		if ($template) $t=1;
		$si->getfromDB(PRINTER_TYPE,$printer->fields["ID"],$t);
		dropdownValue("glpi_dropdown_state", "state",$si->fields["state"]);
		echo "</td></tr>\n";

	echo "<tr><td>".$lang["setup"][88].": 	</td><td>\n";
		dropdownValue("glpi_dropdown_network", "network", $printer->fields["network"]);
	echo "</td></tr>\n";

	echo "<tr><td>".$lang["setup"][89].": 	</td><td>\n";
		dropdownValue("glpi_dropdown_domain", "domain", $printer->fields["domain"]);
	echo "</td></tr>\n";

		 
	echo "</table>"; // fin table indentification

	echo "</td>\n";	
	echo "<td class='tab_bg_1' valign='top'>\n";

	// table type,serial..
	echo "<table cellpadding='1' cellspacing='0' border='0'>\n";

	echo "<tr><td>".$lang["common"][17].": 	</td><td>\n";
		dropdownValue("glpi_type_printers", "type", $printer->fields["type"]);
	echo "</td></tr>\n";

	echo "<tr><td>".$lang["common"][22].": 	</td><td>";
		dropdownValue("glpi_dropdown_model_printers", "model", $printer->fields["model"]);
	echo "</td></tr>";
		
	echo "<tr><td>".$lang["common"][19].":	</td><td>\n";
	autocompletionTextField("serial","glpi_printers","serial",$printer->fields["serial"],20);	echo "</td></tr>\n";

	echo "<tr><td>".$lang["common"][20].":</td><td>\n";
	autocompletionTextField("otherserial","glpi_printers","otherserial",$printer->fields["otherserial"],20);
	echo "</td></tr>\n";

		echo "<tr><td>".$lang["printers"][18].": </td><td>\n";

		// serial interface?
		echo "<table border='0' cellpadding='2' cellspacing='0'><tr>\n";
		echo "<td>";
		if ($printer->fields["flags_serial"] == 1) {
			echo "<input type='checkbox' name='flags_serial' value='1' checked>";
		} else {
			echo "<input type='checkbox' name='flags_serial' value='1'>";
		}
		echo "</td><td>".$lang["printers"][14]."</td>\n";
		echo "</tr></table>\n";

		// parallel interface?
		echo "<table border='0' cellpadding='2' cellspacing='0'><tr>\n";
		echo "<td>";
		if ($printer->fields["flags_par"] == 1) {
			echo "<input type='checkbox' name='flags_par' value='1' checked>";
		} else {
			echo "<input type='checkbox' name='flags_par' value='1'>";
		}
		echo "</td><td>".$lang["printers"][15]."</td>\n";
		echo "</tr></table>\n";
		
		// USB ?
		echo "<table border='0' cellpadding='2' cellspacing='0'><tr>\n";
		echo "<td>\n";
		if ($printer->fields["flags_usb"] == 1) {
			echo "<input type='checkbox' name='flags_usb' value='1' checked>";
		} else {
			echo "<input type='checkbox' name='flags_usb' value='1'>";
		}
		echo "</td><td>".$lang["printers"][27]."</td>\n";
		echo "</tr></table>\n";
		
		// Ram ?
		echo "<tr><td>".$lang["printers"][23].":</td><td>\n";
		autocompletionTextField("ramSize","glpi_printers","ramSize",$printer->fields["ramSize"],20);
		echo "</td></tr>\n";
		// Initial count pages ?
		echo "<tr><td>".$lang["printers"][30].":</td><td>\n";
		autocompletionTextField("initial_pages","glpi_printers","initial_pages",$printer->fields["initial_pages"],20);		
		echo "</td></tr>\n";


	echo "<tr><td>".$lang["printers"][35].":</td><td>";
	echo "<select name='is_global'>";
	echo "<option value='0' ".(!$printer->fields["is_global"]?" selected":"").">".$lang["printers"][34]."</option>";
	echo "<option value='1' ".($printer->fields["is_global"]?" selected":"").">".$lang["printers"][33]."</option>";
	echo "</select>";
	echo "</td></tr>";

	echo "</table>\n";
	echo "</td>\n";	
	echo "</tr>\n";
	
	echo "<tr>\n";
	echo "<td class='tab_bg_1' valign='top' colspan='2'>\n";

	// table commentaires
	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'><tr><td valign='top'>\n";
	echo $lang["common"][25].":	</td>\n";
	echo "<td align='center'><textarea cols='35' rows='4' name='comments' >".$printer->fields["comments"]."</textarea>\n";
	echo "</td></tr></table>\n";

	echo "</td>\n";
	echo "</tr>\n";
	
	


	if (haveRight("printer","w")){
		echo "<tr>\n";
	
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
			echo "</td>\n\n";
			echo "<td class='tab_bg_2' valign='top' align='center'>\n";
			echo "<div align='center'>";
			if ($printer->fields["deleted"]=='N')
				echo "<input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'>";
			else {
				echo "<input type='submit' name='restore' value=\"".$lang["buttons"][21]."\" class='submit'>";
		
				echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' name='purge' value=\"".$lang["buttons"][22]."\" class='submit'>";
			}
			echo "</div>";
			echo "</td>";

		}
		echo "</tr>";
	}
		echo "</table></form></div>";

	return true;	
	}
	else {
                echo "<div align='center'><b>".$lang["printers"][17]."</b></div>";
                return false;
        }

}

?>
