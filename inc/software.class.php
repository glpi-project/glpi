<?php
/*
* @version $Id$
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.
 
 http://indepnet.net/   http://glpi-project.org
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

 
// CLASSES Software

class Software  extends CommonDBTM {

	function Software () {
		$this->table="glpi_software";
		$this->type=SOFTWARE_TYPE;
		$this->dohistory=true;
	}

	function defineOnglets($withtemplate){
		global $lang,$cfg_glpi;
		$ong[1]= $lang["title"][26];
		if(empty($withtemplate)){
			$ong[2]= $lang["software"][19];
		}
		$ong[4] = $lang["Menu"][26];
		$ong[5] = $lang["title"][25];

		if(empty($withtemplate)){
			$ong[6]=$lang["title"][28];
			$ong[7]=$lang["title"][34];
			$ong[10]=$lang["title"][37];
			$ong[12]=$lang["title"][38];

		}	
		return $ong;
	}

	function prepareInputForUpdate($input) {
		// set new date.
		$input["date_mod"] = date("Y-m-d H:i:s");

		if (isset($input['is_update'])&&$input['is_update']=='N') $input['update_software']=-1;

		return $input;
	}

	function prepareInputForAdd($input) {
		// set new date.
		$input["date_mod"] = date("Y-m-d H:i:s");

		if (isset($input['is_update'])&&$input['is_update']=='N') $input['update_software']=-1;

		// dump status
		$input["_oldID"]=$input["ID"];
		unset($input['withtemplate']);
		unset($input['ID']);

		return $input;
	}
	function postAddItem($newID,$input) {
		global $db;
		// ADD Infocoms
		$ic= new Infocom();
		if ($ic->getFromDBforDevice(SOFTWARE_TYPE,$input["_oldID"])){
			$ic->fields["FK_device"]=$newID;
			unset ($ic->fields["ID"]);
			$ic->addToDB();
		}
	

		// ADD Contract				
		$query="SELECT FK_contract from glpi_contract_device WHERE FK_device='".$input["_oldID"]."' AND device_type='".SOFTWARE_TYPE."';";
		$result=$db->query($query);
		if ($db->numrows($result)>0){
		
			while ($data=$db->fetch_array($result))
				addDeviceContract($data["FK_contract"],SOFTWARE_TYPE,$newID);
		}
	
		// ADD Documents			
		$query="SELECT FK_doc from glpi_doc_device WHERE FK_device='".$input["_oldID"]."' AND device_type='".SOFTWARE_TYPE."';";
		$result=$db->query($query);
		if ($db->numrows($result)>0){
		
			while ($data=$db->fetch_array($result))
				addDeviceDocument($data["FK_doc"],SOFTWARE_TYPE,$newID);
		}

	}
	
	function cleanDBonPurge($ID) {

		global $db,$cfg_glpi;

		$query = "SELECT * FROM glpi_tracking WHERE (computer = '$ID'  AND device_type='".SOFTWARE_TYPE."')";
		$result = $db->query($query);

		if ($db->numrows($result))
		while ($data=$db->fetch_array($result)) {
			if ($cfg_glpi["keep_tracking_on_delete"]==1){
				$query = "UPDATE glpi_tracking SET computer = '0', device_type='0' WHERE ID='".$data["ID"]."';";
				$db->query($query);
			} else $job->delete(array("ID"=>$data["ID"]));
		}
		
		$query = "DELETE FROM glpi_infocoms WHERE (FK_device = '$ID' AND device_type='".SOFTWARE_TYPE."')";
		$result = $db->query($query);

		$query = "DELETE FROM glpi_contract_device WHERE (FK_device = '$ID' AND device_type='".SOFTWARE_TYPE."')";
		$result = $db->query($query);

		// Delete all Licenses
		$query2 = "SELECT ID FROM glpi_licenses WHERE (sID = '$ID')";
	
		if ($result2 = $db->query($query2)) {
			if ($db->numrows($result2)){
				$lic = new License;

				while ($data= $db->fetch_array($result2)) {
					$lic->delete(array("ID"=>$data["ID"]));
				}
			}
		}
	}

	function title(){

		global  $lang,$HTMLRel;
         
		echo "<div align='center'><table border='0'><tr><td>";
		echo "<img src=\"".$HTMLRel."pics/logiciels.png\" alt='".$lang["software"][0]."' title='".$lang["software"][0]."'></td>\n";
		if (haveRight("software","w")){
			echo "<td><a class='icon_consol' href=\"".$HTMLRel."front/setup.templates.php?type=".SOFTWARE_TYPE."&amp;add=1\"><strong>".$lang["software"][0]."</strong></a>\n";
			echo "</td>";
			echo "<td><a class='icon_consol'  href='".$HTMLRel."front/setup.templates.php?type=".SOFTWARE_TYPE."&amp;add=0'>".$lang["common"][8]."</a></td>";
		} else 
			echo "<td><span class='icon_sous_nav'><b>".$lang["Menu"][4]."</b></span></td>";
		echo "</tr></table></div>";

	}

	function showForm ($target,$ID,$search_software="",$withtemplate='') {
		// Show Software or blank form
	
		global $cfg_glpi,$lang;
	
		if (!haveRight("software","r")) return false;
	
		$sw_spotted = false;

		if(empty($ID) && $withtemplate == 1) {
			if($this->getEmpty()) $sw_spotted = true;
		} else {
			if($this->getfromDB($ID)) $sw_spotted = true;
		}

		if($sw_spotted) {
			if(!empty($withtemplate) && $withtemplate == 2) {
				$template = "newcomp";
				$datestring = $lang["computers"][14].": ";
				$date = convDateTime(date("Y-m-d H:i:s"));
			} elseif(!empty($withtemplate) && $withtemplate == 1) { 
				$template = "newtemplate";
				$datestring = $lang["computers"][14].": ";
				$date = convDateTime(date("Y-m-d H:i:s"));
			} else {
				$datestring = $lang["common"][26]." : ";
				$date = convDateTime($this->fields["date_mod"]);
				$template = false;
			}


		echo "<div align='center'><form method='post' action=\"$target\">";
		if(strcmp($template,"newtemplate") === 0) {
			echo "<input type=\"hidden\" name=\"is_template\" value=\"1\" />";
		}

		echo "<table class='tab_cadre_fixe'>";

		echo "<tr><th align='center' colspan='2' >";
		if(!$template) {
			echo $lang["software"][41].": ".$this->fields["ID"];
		}elseif (strcmp($template,"newcomp") === 0) {
			echo $lang["software"][42].": ".$this->fields["tplname"];
			echo "<input type='hidden' name='tplname' value='".$this->fields["tplname"]."'>";
		}elseif (strcmp($template,"newtemplate") === 0) {
			echo $lang["common"][6]."&nbsp;: ";
			autocompletionTextField("tplname","glpi_software","tplname",$this->fields["tplname"],20);
		}
		
		echo "</th><th colspan='2' align='center'>".$datestring.$date;
		if (!$template&&!empty($this->fields['tplname']))
			echo "&nbsp;&nbsp;&nbsp;(".$lang["common"][13].": ".$this->fields['tplname'].")";
		echo "</th></tr>";

		echo "<tr class='tab_bg_1'><td>".$lang["common"][16].":		</td>";
		echo "<td>";
		autocompletionTextField("name","glpi_software","name",$this->fields["name"],25);
		echo "</td>";

		echo "<td>".$lang["software"][5].":		</td>";
		echo "<td>";
		autocompletionTextField("version","glpi_software","version",$this->fields["version"],20);
		echo "</td></tr>";


		echo "<tr class='tab_bg_1'><td>".$lang["software"][3].": 	</td><td>";
		dropdownValue("glpi_dropdown_os", "platform", $this->fields["platform"]);
		echo "</td>";
	
		echo "<td>".$lang["common"][5].": 	</td><td>";
		dropdownValue("glpi_enterprises","FK_glpi_enterprise",$this->fields["FK_glpi_enterprise"]);
		echo "</td></tr>";

		echo "<tr class='tab_bg_1'>";
		echo "<td >".$lang["common"][34].": 	</td>";
		echo "<td >";
		dropdownAllUsers("FK_users", $this->fields["FK_users"]);
		echo "</td>";
			
			
		echo "<td>".$lang["common"][35].":</td><td>";
		dropdownValue("glpi_groups", "FK_groups", $this->fields["FK_groups"]);
		echo "</td></tr>";
	
		echo "<tr class='tab_bg_1'><td>".$lang["common"][10].": 	</td><td>";
		dropdownUsersID("tech_num", $this->fields["tech_num"],"interface");
		echo "</td>";

		echo "<td>".$lang["common"][15].": 	</td><td colspan='2'>";
		dropdownValue("glpi_dropdown_locations", "location", $this->fields["location"]);
		echo "</td></tr>";

		// UPDATE
		echo "<tr class='tab_bg_1'><td>".$lang["software"][29].":</td><td>";
		echo "<select name='is_update'><option value='Y' ".($ID&&$this->fields['is_update']=='Y'?"selected":"").">".$lang["choice"][1]."</option><option value='N' ".(!$ID||$this->fields['is_update']=='N'?"selected":"").">".$lang["choice"][0]."</option></select>";
		echo "&nbsp;".$lang["pager"][2]."&nbsp;";
		dropdownValue("glpi_software","update_software",$this->fields["update_software"]);
		echo "</td>";

		if (!$template){
			echo "<td>".$lang["reservation"][24].":</td><td><b>";
			showReservationForm(SOFTWARE_TYPE,$ID);
			echo "</b></td></tr>";
		} else 
			echo "<td colspan='2'>&nbsp;</td></tr>";
	
	

		echo "<tr class='tab_bg_1'><td valign='top'>";
		echo $lang["common"][25].":	</td>";
		echo "<td align='center' colspan='3'><textarea cols='50' rows='4' name='comments' >".$this->fields["comments"]."</textarea>";
		echo "</td></tr>";
	
		if (haveRight("software","w")){
			echo "<tr>";

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

				echo "<td class='tab_bg_2'>&nbsp;</td>";
				echo "<td class='tab_bg_2' valign='top'>";
				echo "<input type='hidden' name='ID' value=\"$ID\">\n";
				echo "<div align='center'><input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'></div>";
				echo "</td>";
				echo "<td class='tab_bg_2' valign='top' colspan='2'>\n";
				echo "<div align='center'>";
				if ($this->fields["deleted"]=='N')
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
			echo "<div align='center'><b>".$lang["software"][22]."</b></div>";
			return false;
		}

	}


	// SPECIFIC FUNCTIONS
	function countInstallations() {
		global $db;
		$query = "SELECT * FROM glpi_inst_software WHERE (sID = ".$this->fields["ID"].")";
		if ($result = $db->query($query)) {
			$number = $db->numrows($result);
			return $number;
		} else {
			return false;
		}
	}
}

class License  extends CommonDBTM {

	function License () {
		$this->table="glpi_licenses";
	}
	
	function prepareInputForUpdate($input) {
		if (empty($input['expire'])) unset($input['expire']);
		if (!isset($input['expire'])||$input['expire']=="0000-00-00") $input['expire']="NULL";
		if (isset($input['oem'])&&$input['oem']=='N') $input['oem_computer']=-1;

		return $input;
	}

	function prepareInputForAdd($input) {
		if (empty($input['expire'])||$input['expire']=="0000-00-00"||$input['expire']=="NULL") unset($input['expire']);
		if ($input['oem']=='N') $input['oem_computer']=-1;
		if ($input['oem_computer']==0) $input['oem_computer']=-1;
		unset($input["form"]);
		unset($input["withtemplate"]);
		unset($input["lID"]);
		return $input;
	}
	
	function postAddItem($newID,$input) {
		// Add license but not for unglobalize system
		if (!isset($input["_duplicate_license"])&&$input['oem']=='Y'&&$input['oem_computer']>0)
			installSoftware($input['oem_computer'],$newID);

		$type=SOFTWARE_TYPE;
		$dupid=$this->fields["sID"];
		if (isset($input["_duplicate_license"])){
			$type=LICENSE_TYPE;
			$dupid=$input["_duplicate_license"];
		} 
		
		// Add infocoms if exists for the licence
		$ic=new Infocom();
		if ($ic->getFromDBforDevice($type,$dupid)){
			unset($ic->fields["ID"]);
			$ic->fields["FK_device"]=$newID;
			$ic->fields["device_type"]=LICENSE_TYPE;
			$ic->addToDB();
		}
	}
	
	function cleanDBonPurge($ID) {

		global $db;

		$query = "DELETE FROM glpi_infocoms WHERE (FK_device = '$ID' AND device_type='".LICENSE_TYPE."')";
		$result = $db->query($query);

		// Delete Installations
		$query2 = "DELETE FROM glpi_inst_software WHERE (license = '$ID')";
		$db->query($query2);
	}


}
?>
