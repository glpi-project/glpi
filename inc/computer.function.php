<?php
/*
 * @version $Id$
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


// FUNCTIONS Computers


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
	$canedit=haveRight("computer","w");

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
		echo "<tr><th colspan='65'>".$lang["devices"][10]."</th></tr>";
		foreach($comp->devices as $key => $val) {
			$device = new Device($val["devType"]);
			$device->getFromDB($val["devID"]);
			printDeviceComputer($device,$val["quantity"],$val["specificity"],$comp->fields["ID"],$val["compDevID"],$withtemplate);

		}
		if ($canedit&&!(!empty($withtemplate) && $withtemplate == 2)&&count($comp->devices))
			echo "<tr><td colspan='65' align='center' class='tab_bg_1'><input type='submit' class='submit' name='update_device' value='".$lang["buttons"][7]."'></td></tr>";
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
						dropdownConnect($type,COMPUTER_TYPE,"item",$withtemplate);
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
