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
 
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

 
// FUNCTIONS Computers
/**
* Print a good title for computer pages
*
*
*
*
*@return nothing (diplays)
*
**/
function titleComputers(){
	global  $lang,$HTMLRel;

	echo "<div align='center'><table border='0'><tr><td>";
	echo "<img src=\"".$HTMLRel."pics/computer.png\" alt='".$lang["computers"][0]."' title='".$lang["computers"][0]."'></td>";
	if (haveRight("computer","w")){
		echo "<td><a  class='icon_consol' href=\"".$HTMLRel."setup/setup-templates.php?type=".COMPUTER_TYPE."&amp;add=1\"><b>".$lang["computers"][0]."</b></a>";
		echo "</td>";
		echo "<td><a class='icon_consol' href='".$HTMLRel."setup/setup-templates.php?type=".COMPUTER_TYPE."&amp;add=0'>".$lang["common"][8]."</a></td>";
	} else echo "<td><span class='icon_sous_nav'><b>".$lang["Menu"][0]."</b></span></td>";
	echo "</tr></table></div>";

}


/**
* Test if a field is a dropdown
*
* Return true if the field $field is a dropdown 
* or false if not.
*
*@param $field string field name
*
*
*@return bool
*
**/
function IsDropdown($field) {
	$dropdown = array("netpoint","os","model");
	if(in_array($field,$dropdown)) {
		return true;
	}
	else  {
		return false;
	}
}
/**
* Test if a field is a device
*
* Return true if the field $field is a device 
* or false if not.
*
*@param $field string device name
*
*
*@return bool
*
**/
function IsDevice($field) {
	global $cfg_glpi;
	if(in_array($field,$cfg_glpi["devices_tables"])) {
		return true;
	}
	else  {
		return false;
	}
}

/**
* Print the computer form
*
*
* Print général computer form
*
*@param $target filename : where to go when done.
*@param $ID Integer : Id of the computer or the template to print
*@param $withtemplate='' boolean : template or basic computer
*
*
*@return Nothing (display)
*
**/
function showComputerForm($target,$ID,$withtemplate='') {
	global $lang,$HTMLRel,$cfg_glpi;

	if (!haveRight("computer","r")) return false;
	
	$comp = new Computer;
	$computer_spotted = false;
	if(empty($ID) && $withtemplate == 1) {
		if($comp->getEmpty()) $computer_spotted = true;
	} else {
		if($comp->getfromDB($ID)) $computer_spotted = true;
	}
	if($computer_spotted) {
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
			$date = convDateTime($comp->fields["date_mod"]);
			$template = false;
		}
		
		echo "<form name='form' method='post' action=\"$target\">";
		if(strcmp($template,"newtemplate") === 0) {
			echo "<input type=\"hidden\" name=\"is_template\" value=\"1\">";
		}

		echo "<div align='center'>";
		echo "<table class='tab_cadre_fixe' >";
		
		
		echo "<tr><th colspan ='2' align='center' >";
		if(!$template) {
			echo $lang["computers"][13].": ".$comp->fields["ID"];
		}elseif (strcmp($template,"newcomp") === 0) {
			echo $lang["computers"][12].": ".$comp->fields["tplname"];
			echo "<input type='hidden' name='tplname' value='".$comp->fields["tplname"]."'>";
		}elseif (strcmp($template,"newtemplate") === 0) {
			echo $lang["common"][6]."&nbsp;: ";
			autocompletionTextField("tplname","glpi_computers","tplname",$comp->fields["tplname"],20);	
		}
		
		
		echo "</th><th  colspan ='2' align='center'>".$datestring.$date;
		if (!$template&&!empty($comp->fields['tplname']))
			echo "&nbsp;&nbsp;&nbsp;(".$lang["common"][13].": ".$comp->fields['tplname'].")";
		if ($comp->fields["ocs_import"])
			echo "&nbsp;&nbsp;&nbsp;(".$lang["ocsng"][7].")";

		echo "</th></tr>";
		
		echo "<tr class='tab_bg_1'><td>".$lang["common"][16].":		</td>";

		echo "<td>";
		autocompletionTextField("name","glpi_computers","name",$comp->fields["name"],20);
		echo "</td>";
						
		echo "<td>".$lang["common"][18].":	</td><td>";
		autocompletionTextField("contact","glpi_computers","contact",$comp->fields["contact"],20);
		
		echo "</td></tr>";
		
		echo "<tr class='tab_bg_1'>";
				echo "<td >".$lang["common"][17].": 	</td>";
		echo "<td >";
			dropdownValue("glpi_type_computers", "type", $comp->fields["type"]);
		
		echo "</td>";

		
		
		echo "<td>".$lang["common"][21].":		</td><td>";
		autocompletionTextField("contact_num","glpi_computers","contact_num",$comp->fields["contact_num"],20);
		echo "</td></tr>";

		echo "<tr class='tab_bg_1'>";
		echo "<td >".$lang["common"][22].": 	</td>";
		echo "<td >";
			dropdownValue("glpi_dropdown_model", "model", $comp->fields["model"]);
		
		echo "</td>";
		
		
		echo "<td>".$lang["setup"][88].":</td><td>";
		dropdownValue("glpi_dropdown_network", "network", $comp->fields["network"]);
		echo "</td></tr>";

		echo "<tr class='tab_bg_1'>";
		echo "<td >".$lang["common"][15].": 	</td>";
		echo "<td >";
			dropdownValue("glpi_dropdown_locations", "location", $comp->fields["location"]);
		
		echo "</td>";
		
		
		echo "<td>".$lang["setup"][89].":</td><td>";
		dropdownValue("glpi_dropdown_domain", "domain", $comp->fields["domain"]);
		echo "</td></tr>";
		

		echo "<tr class='tab_bg_1'>";
		echo "<td >".$lang["common"][10].": 	</td>";
		echo "<td >";
			dropdownUsersID("tech_num",$comp->fields["tech_num"],"interface");
		echo "</td>";

		echo "<td>".$lang["common"][19].":	</td><td>";
		autocompletionTextField("serial","glpi_computers","serial",$comp->fields["serial"],20);
		echo "</td></tr>";

		echo "<tr class='tab_bg_1'>";
		echo "<td>".$lang["common"][5].": 	</td><td>";
		dropdownValue("glpi_enterprises","FK_glpi_enterprise",$comp->fields["FK_glpi_enterprise"]);
		echo "</td>";

		echo "<td>".$lang["common"][20].":	</td><td>";
		autocompletionTextField("otherserial","glpi_computers","otherserial",$comp->fields["otherserial"],20);
		echo "</td></tr>";
	
		echo "<tr class='tab_bg_1'>";
		
		echo "<td>".$lang["computers"][9].":</td><td>";
		dropdownValue("glpi_dropdown_os", "os", $comp->fields["os"]);
		echo "</td>";
		
		echo "<td>".$lang["state"][0].":</td><td>";
		$si=new StateItem();
		$t=0;
		if ($template) $t=1;
		$si->getfromDB(COMPUTER_TYPE,$comp->fields["ID"],$t);
		dropdownValue("glpi_dropdown_state", "state",$si->fields["state"]);
		echo "</td></tr>";
				
		echo "<tr class='tab_bg_1'>";
		
		echo "<td>".$lang["computers"][52].":</td><td>";
		dropdownValue("glpi_dropdown_os_version", "os_version", $comp->fields["os_version"]);
		echo "</td>";
		
		if (!$template){
		echo "<td>".$lang["reservation"][24].":</td><td><b>";
		showReservationForm(COMPUTER_TYPE,$ID);
		echo "</b></td>";
		} else echo "<td>&nbsp;</td><td>&nbsp;</td>";
		echo "</tr>";

		echo "<tr class='tab_bg_1'>";
		echo "<td>".$lang["computers"][53].":</td><td>";
		dropdownValue("glpi_dropdown_os_sp", "os_sp", $comp->fields["os_sp"]);
		echo "</td>";
		
		echo "<td valign='middle' rowspan='2'>".$lang["common"][25].":</td><td valign='middle' rowspan='2'><textarea  cols='60' rows='3' name='comments' >".$comp->fields["comments"]."</textarea></td>";
		echo "</tr>";
		echo "<tr class='tab_bg_1'>";
		echo "<td>".$lang["computers"][51].":</td><td>";
		dropdownValue("glpi_dropdown_auto_update", "auto_update", $comp->fields["auto_update"]);
		echo "</td>";
		
		echo "</tr>";
		if (haveRight("computer","w")) {
		echo "<tr>\n";
		if ($template) {
			if (empty($ID)||$withtemplate==2){
			echo "<td class='tab_bg_2' align='center' colspan='4'>\n";
			echo "<input type='hidden' name='ID' value=$ID>";
			echo "<input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'>";
			echo "</td>\n";
			} else {
			echo "<td class='tab_bg_2' align='center' colspan='4'>\n";
			echo "<input type='hidden' name='ID' value=$ID>";
			echo "<input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'>";
			echo "</td>\n";
			}
		} else {
			echo "<td class='tab_bg_2' colspan='2' align='center' valign='top'>\n";
			echo "<input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'>";
			echo "</td>\n";
                        echo "<td class='tab_bg_2' colspan='2'  align='center'>\n";
			echo "<input type='hidden' name='ID' value=$ID>";
		echo "<div align='center'>";
		if ($comp->fields["deleted"]=='N')
		echo "<input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'>";
		else {
		echo "<input type='submit' name='restore' value=\"".$lang["buttons"][21]."\" class='submit'>";
		
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' name='purge' value=\"".$lang["buttons"][22]."\" class='submit'>";
		}
		echo "</div>";
			echo "</td>";
		}
		echo "</tr>\n";
		}
		
		
		echo "</table>";
		echo "</div>";
	echo "</form>";
		
		
		return true;
	}
	else {
         echo "<div align='center'><b>".$lang["computers"][32]."</b></div>";
         return false;
        }
}
/**
* Print the form for devices linked to a computer or a template
*
*
* Print the form for devices linked to a computer or a template 
*
*@param $target filename : where to go when done.
*@param $ID Integer : Id of the computer or the template to print
*@param $withtemplate='' boolean : template or basic computer
*
*
*@return Nothing (display)
*
**/
function showDeviceComputerForm($target,$ID,$withtemplate='') {
	global $lang;

	if (!haveRight("computer","r")) return false;

	$comp = new Computer;
	if(empty($ID) && $withtemplate == 1) {
		$comp->getEmpty();
	} else {
		$comp->getfromDBwithDevices($ID);
	}

	if (!empty($ID)){
			//print devices.
		echo "<div align='center'>";
		echo "<form name='form_device_action' action=\"$target\" method=\"post\" >";
		echo "<input type='hidden' name='ID' value='$ID'>";	
		echo "<input type='hidden' name='device_action' value='$ID'>";			
		echo "<table class='tab_cadre_fixe' >";
		echo "<tr><th colspan='66'>".$lang["devices"][10]."</th></tr>";
		foreach($comp->devices as $key => $val) {
			$devType = $val["devType"];
			$devID = $val["devID"];
			$specif = $val["specificity"];
			$compDevID = $val["compDevID"];
			$device = new Device($devType);
			$device->getFromDB($devID);
			printDeviceComputer($device,$specif,$comp->fields["ID"],$compDevID,$withtemplate);
			
		}
		echo "</table>";

		echo "</form>";
		//ADD a new device form.
		device_selecter($_SERVER["PHP_SELF"],$comp->fields["ID"],$withtemplate);
		echo "</div>";
	}	


}

/**
* Print the computers or template local connections form. 
*
* Print the form for computers or templates connections to printers, screens or peripherals
*
*@param $target 
*@param $ID integer: Computer or template ID
*@param $withtemplate=''  boolean : Template or basic item.
*
*@return Nothing (call to classes members)
*
**/
function showConnections($target,$ID,$withtemplate='') {

	global $db,$cfg_glpi, $lang,$INFOFORM_PAGES;

	
	$state=new StateItem();
	$ci=new CommonItem;

	$items=array(PRINTER_TYPE=>$lang["computers"][39],MONITOR_TYPE=>$lang["computers"][40],PERIPHERAL_TYPE=>$lang["computers"][46],PHONE_TYPE=>$lang["computers"][55]);

	
	foreach ($items as $type => $title){
		if (!haveTypeRight($type,"r")) unset($items[$type]);
			
	}
	if (count($items)){
		echo "&nbsp;<div align='center'><table class='tab_cadre_fixe'>";

		echo "<tr><th colspan='".count($items)."'>".$lang["connect"][0].":</th></tr>";

		echo "<tr>";
		foreach ($items as $type => $title)
			echo "<th>".$title.":</th>";
		echo "</tr>";

		echo "<tr class='tab_bg_1'>";
	
		foreach ($items as $type=>$title){
			$canedit=haveTypeRight($type,"w");
	
			echo "<td align='center'>";
			$query = "SELECT * from glpi_connect_wire WHERE end2='$ID' AND type='".$type."'";
			if ($result=$db->query($query)) {
				$resultnum = $db->numrows($result);
				if ($resultnum>0) {
					echo "<table width='100%'>";
					for ($i=0; $i < $resultnum; $i++) {
						$tID = $db->result($result, $i, "end1");
						$connID = $db->result($result, $i, "ID");
						$ci->getFromDB($type,$tID);
				
						echo "<tr ".($ci->obj->fields["deleted"]=='Y'?"class='tab_bg_2_2'":"").">";
						echo "<td align='center'><b>";
						echo $ci->getLink();
						echo "</b>";
						if ($state->getfromDB($type,$tID))
							echo " - ".getDropdownName("glpi_dropdown_state",$state->fields['state']);

						echo "</td>";
						if($canedit&&(empty($withtemplate) || $withtemplate != 2)) {
							echo "<td align='center'><a 	href=\"".$cfg_glpi["root_doc"]."/front/computer.form.php?cID=$ID&amp;ID=$connID&amp;disconnect=1amp;withtemplate=".$withtemplate."\"><b>";
							echo $lang["buttons"][10];
							echo "</b></a></td>";
						}
						echo "</tr>";
					}
					echo "</table>";
				} else {
					switch ($type){
						case PRINTER_TYPE:
							echo $lang["computers"][38];
							break;
						case MONITOR_TYPE:
							echo $lang["computers"][37];
							break;
						case PERIPHERAL_TYPE:
							echo $lang["computers"][47];
							break;
						case PHONE_TYPE:
							echo $lang["computers"][54];
							break;
					}
					echo "<br>";
				}
				if ($canedit)
				if(empty($withtemplate) || $withtemplate != 2) {
					echo "<form method='post' action=\"$target\">";
					echo "<input type='hidden' name='connect' value='connect'>";
					echo "<input type='hidden' name='cID' value='$ID'>";
					echo "<input type='hidden' name='device_type' value='".$type."'>";
					dropdownConnect($type,"item");
					echo "<input type='submit' value=\"".$lang["buttons"][9]."\" class='submit'>";
					echo "</form>";
				}
			}
			echo "</td>";
		}

		echo "</tr>";
		echo "</table></div><br>";
	}
	
}




?>
