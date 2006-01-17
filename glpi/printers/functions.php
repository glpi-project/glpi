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
           GLOBAL  $lang,$HTMLRel;
           
           echo "<div align='center'><table border='0'><tr><td>";

           echo "<img src=\"".$HTMLRel."pics/printer.png\" alt='".$lang["printers"][0]."' title='".$lang["printers"][0]."'></td><td><a  class='icon_consol' href=\"printers-add-select.php\"><b>".$lang["printers"][0]."</b></a>";

                echo "</td>";
                echo "<td><a class='icon_consol'  href='".$HTMLRel."setup/setup-templates.php?type=".PRINTER_TYPE."'>".$lang["common"][8]."</a></td>";
                echo "</tr></table></div>";
}

function showPrinterOnglets($target,$withtemplate,$actif){
	global $lang, $HTMLRel;
	
	$template="";
	if(!empty($withtemplate)){
		$template="&amp;withtemplate=$withtemplate";
	}

	echo "<div id='barre_onglets'><ul id='onglet'>";
	echo "<li "; if ($actif=="1"){ echo "class='actif'";} echo  "><a href='$target&amp;onglet=1$template'>".$lang["title"][26]."</a></li>";
	echo "<li "; if ($actif=="3") {echo "class='actif'";} echo "><a href='$target&amp;onglet=3$template'>".$lang["title"][27]."</a></li>";
	echo "<li "; if ($actif=="4") {echo "class='actif'";} echo "><a href='$target&amp;onglet=4$template'>".$lang["Menu"][26]."</a></li>";
	echo "<li "; if ($actif=="5") {echo "class='actif'";} echo "><a href='$target&amp;onglet=5$template'>".$lang["title"][25]."</a></li>";
	if(empty($withtemplate)){
	echo "<li "; if ($actif=="6") {echo "class='actif'";} echo "><a href='$target&amp;onglet=6$template'>".$lang["title"][28]."</a></li>";
	echo "<li "; if ($actif=="7") {echo "class='actif'";} echo "><a href='$target&amp;onglet=7$template'>".$lang["title"][34]."</a></li>";
	echo "<li "; if ($actif=="10") {echo "class='actif'";} echo "><a href='$target&amp;onglet=10$template'>".$lang["title"][37]."</a></li>";
	echo "<li class='invisible'>&nbsp;</li>";
	echo "<li "; if ($actif=="-1") {echo "class='actif'";} echo "><a href='$target&amp;onglet=-1$template'>".$lang["title"][29]."</a></li>";
	}
	
	echo "<li class='invisible'>&nbsp;</li>";
	
	if (empty($withtemplate)&&preg_match("/\?ID=([0-9]+)/",$target,$ereg)){
	$ID=$ereg[1];
	$next=getNextItem("glpi_printers",$ID);
	$prev=getPreviousItem("glpi_printers",$ID);
	$cleantarget=preg_replace("/\?ID=([0-9]+)/","",$target);
	if ($prev>0) echo "<li><a href='$cleantarget?ID=$prev'><img src=\"".$HTMLRel."pics/left.png\" alt='".$lang["buttons"][12]."' title='".$lang["buttons"][12]."'></a></li>";
	if ($next>0) echo "<li><a href='$cleantarget?ID=$next'><img src=\"".$HTMLRel."pics/right.png\" alt='".$lang["buttons"][11]."' title='".$lang["buttons"][11]."'></a></li>";

	if (isReservable(PRINTER_TYPE,$ID)){
		echo "<li class='invisible'>&nbsp;</li>";
		echo "<li "; if ($actif=="11") {echo "class='actif'";} echo "><a href='$target&amp;onglet=11$template'>".$lang["title"][35]."</a></li>";
	}
	}
	echo "</ul></div>";
	
}


function showPrintersForm ($target,$ID,$withtemplate='') {

	GLOBAL $cfg_install, $cfg_layout, $lang,$HTMLRel;

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
			$datestring = $lang["computers"][11].": ";
			$date = convDateTime($printer->fields["date_mod"]);
			$template = false;
		}


	echo "<div align='center' ><form method='post' name='form' action=\"$target\">\n";
		if(strcmp($template,"newtemplate") === 0) {
			echo "<input type=\"hidden\" name=\"is_template\" value=\"1\" />\n";
		}

	echo "<table class='tab_cadre' width='800' cellpadding='2'>\n";

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
	echo "<tr><td>".$lang["printers"][5].":	</td>\n";
	echo "<td>";
	autocompletionTextField("name","glpi_printers","name",$printer->fields["name"],20);		
	echo "</td></tr>\n";

	echo "<tr><td>".$lang["printers"][6].": 	</td><td>\n";
		dropdownValue("glpi_dropdown_locations", "location", $printer->fields["location"]);
	echo "</td></tr>\n";

	echo "<tr class='tab_bg_1'><td>".$lang["common"][5].": 	</td><td colspan='2'>\n";
		dropdownValue("glpi_enterprises","FK_glpi_enterprise",$printer->fields["FK_glpi_enterprise"]);
	echo "</td></tr>\n";
	
	echo "<tr class='tab_bg_1'><td>".$lang["common"][10].": 	</td><td colspan='2'>\n";
		dropdownUsersID("tech_num", $printer->fields["tech_num"]);
	echo "</td></tr>\n";
	
	echo "<tr><td>".$lang["printers"][7].":	</td><td>\n";
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

	echo "<tr><td>".$lang["printers"][9].": 	</td><td>\n";
		dropdownValue("glpi_type_printers", "type", $printer->fields["type"]);
	echo "</td></tr>\n";

	echo "<tr><td>".$lang["printers"][32].": 	</td><td>";
		dropdownValue("glpi_dropdown_model_printers", "model", $printer->fields["model"]);
	echo "</td></tr>";
		
	echo "<tr><td>".$lang["printers"][10].":	</td><td>\n";
	autocompletionTextField("serial","glpi_printers","serial",$printer->fields["serial"],20);	echo "</td></tr>\n";

	echo "<tr><td>".$lang["printers"][11].":</td><td>\n";
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

	echo "</table>\n";
	echo "</td>\n";	
	echo "</tr>\n";
	
	echo "<tr>\n";
	echo "<td class='tab_bg_1' valign='top' colspan='2'>\n";

	// table commentaires
	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'><tr><td valign='top'>\n";
	echo $lang["printers"][12].":	</td>\n";
	echo "<td align='center'><textarea cols='35' rows='4' name='comments' >".$printer->fields["comments"]."</textarea>\n";
	echo "</td></tr></table>\n";

	echo "</td>\n";
	echo "</tr>\n";
	
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

		echo "</table></form></div>";

	return true;	
	}
	else {
                echo "<div align='center'><b>".$lang["printers"][17]."</b></div>";
                return false;
        }

}


function updatePrinter($input) {
	// Update a printer in the database

	$printer = new Printer;
	$printer->getFromDB($input["ID"]);

	// set new date and make sure it gets updated
	$updates[0]= "date_mod";
	$printer->fields["date_mod"] = date("Y-m-d H:i:s");
	
	// Get all flags and fill with 0 if unchecked in form
	foreach ($printer->fields as $key => $val) {
		if (eregi("\.*flag\.*",$key)) {
			if (!isset($input[$key])) {
				$input[$key]=0;
			}
		}
	}	

	// Fill the update-array with changes
	$x=1;
	foreach ($input as $key => $val) {
		if (array_key_exists($key,$printer->fields) && $printer->fields[$key] != $input[$key]) {
			$printer->fields[$key] = $input[$key];
			$updates[$x] = $key;
			$x++;
		}
	}
	
	if (isset($input["state"]))
	if (isset($input["is_template"])&&$input["is_template"]==1)
	updateState(PRINTER_TYPE,$input["ID"],$input["state"],1);
	else updateState(PRINTER_TYPE,$input["ID"],$input["state"]);

	$printer->updateInDB($updates);

}

function addPrinter($input) {
	// Add Printer, nasty hack until we get PHP4-array-functions
	$db=new DB;
	$printer = new Printer;
	
	// dump status
	$oldID=$input["ID"];

	unset($input['add']);
	unset($input['withtemplate']);
	unset($input['ID']);
	
	// Manage state
	$state=-1;
	if (isset($input["state"])){
		$state=$input["state"];
		unset($input["state"]);
	}
	
 	// set new date.
 	$printer->fields["date_mod"] = date("Y-m-d H:i:s");
 	
	// fill array for update
	foreach ($input as $key => $val) {
		if ($key[0]!='_'&&(empty($printer->fields[$key]) || $printer->fields[$key] != $input[$key])) {
			$printer->fields[$key] = $input[$key];
		}
	}

	$newID=$printer->addToDB();
	
	
	// Add state
	if ($state>0){
		if (isset($input["is_template"])&&$input["is_template"]==1)
			updateState(PRINTER_TYPE,$newID,$state,1);
		else updateState(PRINTER_TYPE,$newID,$state);
	}
	
	// ADD Infocoms
	$ic= new Infocom();
	if ($ic->getFromDB(PRINTER_TYPE,$oldID)){
		$ic->fields["FK_device"]=$newID;
		unset ($ic->fields["ID"]);
		$ic->addToDB();
	}
	
		// ADD Ports
	$query="SELECT ID from glpi_networking_ports WHERE on_device='$oldID' AND device_type='".PRINTER_TYPE."';";
	$result=$db->query($query);
	if ($db->numrows($result)>0){
		
		while ($data=$db->fetch_array($result)){
			$np= new Netport();
			$np->getFromDB($data["ID"]);
			unset($np->fields["ID"]);
			unset($np->fields["ifaddr"]);
			unset($np->fields["ifmac"]);
			unset($np->fields["netpoint"]);
			$np->fields["on_device"]=$newID;
			$np->addToDB();
			}
	}

	// ADD Contract				
	$query="SELECT FK_contract from glpi_contract_device WHERE FK_device='$oldID' AND device_type='".PRINTER_TYPE."';";
	$result=$db->query($query);
	if ($db->numrows($result)>0){
		
		while ($data=$db->fetch_array($result))
			addDeviceContract($data["FK_contract"],PRINTER_TYPE,$newID);
	}
	
	// ADD Documents			
	$query="SELECT FK_doc from glpi_doc_device WHERE FK_device='$oldID' AND device_type='".PRINTER_TYPE."';";
	$result=$db->query($query);
	if ($db->numrows($result)>0){
		
		while ($data=$db->fetch_array($result))
			addDeviceDocument($data["FK_doc"],PRINTER_TYPE,$newID);
	}


	return $newID;
}

function deletePrinter($input,$force=0) {
	// Delete Printer
	
	$printer = new Printer;
	$printer->deleteFromDB($input["ID"],$force);
	
} 	

function restorePrinter($input) {
	// Restore Printer
	
	$ct = new Printer;
	$ct->restoreInDB($input["ID"]);
} 

?>
