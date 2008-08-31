<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2008 by the INDEPNET Development Team.

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

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}

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
	global $CFG_GLPI;
	if(in_array($field,$CFG_GLPI["devices_tables"])) {
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
	global $LANG,$CFG_GLPI;

	if (!haveRight("computer","r")) return false;
	$canedit=haveRight("computer","w");

	$comp = new Computer;
	if(empty($ID) && $withtemplate == 1) {
		$comp->getEmpty();
	} else {
		$comp->getFromDBwithDevices($ID);
	}

	if (!empty($ID)){
		//print devices.
		if (!($CFG_GLPI["cache"]->start("device_".$ID."_".$_SESSION["glpilanguage"],"GLPI_".COMPUTER_TYPE))) {
			echo "<div class='center'>";
			echo "<form name='form_device_action' action=\"$target\" method=\"post\" >";
			echo "<input type='hidden' name='ID' value='$ID'>";	
			echo "<input type='hidden' name='device_action' value='$ID'>";			
			echo "<table class='tab_cadre_fixe' >";
			echo "<tr><th colspan='65'>".$LANG["title"][30]."</th></tr>";
			foreach($comp->devices as $key => $val) {
				$device = new Device($val["devType"]);
				$device->getFromDB($val["devID"]);
				printDeviceComputer($device,$val["quantity"],$val["specificity"],$comp->fields["ID"],$val["compDevID"],$withtemplate);
	
			}
			$CFG_GLPI["cache"]->end();
		}
		if ($canedit&&!(!empty($withtemplate) && $withtemplate == 2)&&count($comp->devices))
			echo "<tr><td colspan='65' align='center' class='tab_bg_1'><input type='submit' class='submit' name='update_device' value='".$LANG["buttons"][7]."'></td></tr>";
		echo "</table>";

		echo "</form>";
		//ADD a new device form.
		device_selecter($_SERVER['PHP_SELF'],$comp->fields["ID"],$withtemplate);
		echo "</div><br>";
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

	global $DB,$CFG_GLPI, $LANG,$INFOFORM_PAGES;


	$ci=new CommonItem;

	$items=array(PRINTER_TYPE=>$LANG["computers"][39],MONITOR_TYPE=>$LANG["computers"][40],PERIPHERAL_TYPE=>$LANG["computers"][46],PHONE_TYPE=>$LANG["computers"][55]);
	$comp=new Computer();
	$canedit=haveTypeRight(COMPUTER_TYPE,"w");

	if ($comp->getFromDB($ID)){

		foreach ($items as $type => $title){
			if (!haveTypeRight($type,"r")) unset($items[$type]);
	
		}
		if (count($items)){
			echo "<div class='center'><table class='tab_cadre_fixe'>";
	
			echo "<tr><th colspan='".max(2,count($items))."'>".$LANG["connect"][0].":</th></tr>";
	
			echo "<tr>";
			$header_displayed=0;

			foreach ($items as $type => $title){

				if ($header_displayed==2){
					break;
				}
				echo "<th>".$title.":</th>";
				$header_displayed++;
			}
			echo "</tr>";
			echo "<tr class='tab_bg_1'>";
			$items_displayed=0;
			foreach ($items as $type=>$title){
				if ($items_displayed==2){
					
					echo "</tr><tr>";
					$header_displayed=0;
					foreach ($items as $tmp_title){
						if ($header_displayed>=2){
							echo "<th>".$tmp_title.":</th>";
						}
						$header_displayed++;
					}

					echo "</tr><tr class='tab_bg_1'>";
				}
				echo "<td class='center'>";
				$query = "SELECT * FROM glpi_connect_wire WHERE end2='$ID' AND type='".$type."'";
				if ($result=$DB->query($query)) {
					$resultnum = $DB->numrows($result);
					if ($resultnum>0) {
						echo "<table width='100%'>";
						for ($i=0; $i < $resultnum; $i++) {
							$tID = $DB->result($result, $i, "end1");
							$connID = $DB->result($result, $i, "ID");
							$ci->getFromDB($type,$tID);
	
							echo "<tr ".($ci->getField('deleted')?"class='tab_bg_2_2'":"").">";
							echo "<td class='center'><strong>";
							echo $ci->getLink();
							echo "</strong>";
							echo " - ".getDropdownName("glpi_dropdown_state",$ci->getField('state'));
	
							echo "</td><td>".$ci->getField('serial');
							echo "</td><td>".$ci->getField('otherserial');
							echo "</td><td>";
							if($canedit&&(empty($withtemplate) || $withtemplate != 2)) {
								echo "<td class='center'><a 	href=\"".$CFG_GLPI["root_doc"]."/front/computer.form.php?cID=$ID&amp;ID=$connID&amp;disconnect=1&amp;withtemplate=".$withtemplate."\"><strong>";
								echo $LANG["buttons"][10];
								echo "</strong></a></td>";
							}
							echo "</tr>";
						}
						echo "</table>";
					} else {
						switch ($type){
							case PRINTER_TYPE:
								echo $LANG["computers"][38];
								break;
							case MONITOR_TYPE:
								echo $LANG["computers"][37];
								break;
							case PERIPHERAL_TYPE:
								echo $LANG["computers"][47];
								break;
							case PHONE_TYPE:
								echo $LANG["computers"][54];
								break;
						}
						echo "<br>";
					}
					if ($canedit){
						if(empty($withtemplate) || $withtemplate != 2) {
							echo "<form method='post' action=\"$target\">";
							echo "<input type='hidden' name='connect' value='connect'>";
							echo "<input type='hidden' name='cID' value='$ID'>";
							echo "<input type='hidden' name='device_type' value='".$type."'>";
							if (empty($withtemplate)){
								echo "<input type='hidden' name='dohistory' value='1'>";
							} else { // No history for template
								echo "<input type='hidden' name='dohistory' value='0'>";
							}
							dropdownConnect($type,COMPUTER_TYPE,"item",$comp->fields["FK_entities"],$withtemplate);
							echo "<input type='submit' value=\"".$LANG["buttons"][9]."\" class='submit'>";
							echo "</form>";
						}
					}
				}
				echo "</td>";
				$items_displayed++;
			}
	
			echo "</tr>";
			echo "</table></div><br>";
		}
	}
}

/**
 * Print the computers disks
 *
 *@param $ID integer: Computer or template ID
 *@param $withtemplate=''  boolean : Template or basic item.
 *
 *@return Nothing (call to classes members)
 *
 **/
function showComputerDisks($ID,$withtemplate='') {
	global $DB, $CFG_GLPI, $LANG;
	if (!haveRight("computer", "r"))
		return false;
	$canedit = haveRight("computer", "w");

	echo "<div class='center'>";
	
	$query = "SELECT glpi_dropdown_filesystems.name as fsname, glpi_computerdisks.* FROM glpi_computerdisks
		LEFT JOIN glpi_dropdown_filesystems ON (glpi_computerdisks.FK_filesystems = glpi_dropdown_filesystems.ID)
		WHERE (FK_computers = '$ID')";

	if ($result=$DB->query($query)){
		echo "<table class='tab_cadre_fixe'><tr>";
		echo "<th colspan='6'>".$LANG["computers"][8]."</th></tr>";
		if ($DB->numrows($result)){
			//echo "<th colspan='6'>".$LANG["computers"][8]."</th></tr>";
			echo "<tr><th>".$LANG["common"][16]."</th>";
			echo "<th>".$LANG["computers"][6]."</th>";
			echo "<th>".$LANG["computers"][5]."</th>";
			echo "<th>".$LANG["computers"][4]."</th>";
			echo "<th>".$LANG["computers"][3]."</th>";
			echo "<th>".$LANG["computers"][2]."</th>";
			echo "</tr>";
			while ($data=$DB->fetch_assoc($result)){
				echo "<tr class='tab_bg_2'>";
				if ($canedit){
					echo "<td><a href='computerdisk.form.php?ID=".$data['ID']."'>".$data['name'].(empty($data['name'])?$data['ID']:"")."</a></td>";
				} else {
						echo "<td>".$data['name'].(empty($data['name'])?$data['ID']:"")."</td>";
				}
				echo "<td>".$data['device']."</td>";
				echo "<td>".$data['mountpoint']."</td>";
				echo "<td>".$data['fsname']."</td>";
				echo "<td>".number_format($data['totalsize'], 0, '.', ' ')."&nbsp;".$LANG["common"][82]."</td>";
				echo "<td>".number_format($data['freesize'], 0, '.', ' ')."&nbsp;".$LANG["common"][82]."</td>";
			}
			//echo "</table>";
		} else {
			echo "<th colspan='6'>".$LANG["search"][15]."</th></tr>";
		}
	if ($canedit){
		echo "<tr class='tab_bg_2'><th colspan='6''><a href='computerdisk.form.php?cID=$ID'>".$LANG["computers"][7]."</a></th></tr></table>";
	}
	
	}
	echo "</div><br>";

	
}


?>
