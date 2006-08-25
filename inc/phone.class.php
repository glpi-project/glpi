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


// CLASSES peripherals


class Phone extends CommonDBTM {

	function Phone () {
		$this->table="glpi_phones";
		$this->type=PHONE_TYPE;
		$this->dohistory=true;
	}

	function defineOnglets($withtemplate){
		global $lang,$cfg_glpi;
		$ong=array();

		$ong[1]=$lang["title"][26];
		if (haveRight("contract_infocom","r"))
			$ong[4]=$lang["Menu"][26];
		if (haveRight("document","r"))
			$ong[5]=$lang["title"][25];

		if(empty($withtemplate)){
			if (haveRight("show_ticket","1"))
				$ong[6]=$lang["title"][28];
			if (haveRight("link","r"))
				$ong[7]=$lang["title"][34];
			if (haveRight("notes","r"))
				$ong[10]=$lang["title"][37];
			$ong[12]=$lang["title"][38];
		}	

		return $ong;
	}

	function prepareInputForUpdate($input) {
		// set new date.
		$input["date_mod"] = date("Y-m-d H:i:s");

		return $input;
	}

	function post_updateItem($input,$updates,$history=1) {

		if(isset($input["state"])){
			if (isset($input["is_template"])&&$input["is_template"]==1){
				updateState(PHONE_TYPE,$input["ID"],$input["state"],1,0);
			}else {
				updateState(PHONE_TYPE,$input["ID"],$input["state"],0,$history);
			}
		}
	}

	function prepareInputForAdd($input) {
		// set new date.
		$input["date_mod"] = date("Y-m-d H:i:s");

		// dump status
		$input["_oldID"]=$input["ID"];
		unset($input['withtemplate']);
		unset($input['ID']);

		// Manage state
		$input["_state"]=-1;
		if (isset($input["state"])){
			$input["_state"]=$input["state"];
			unset($input["state"]);
		}

		return $input;
	}

	function postAddItem($newID,$input) {
		global $db;
		// Add state
		if ($input["_state"]>0){
			if (isset($input["is_template"])&&$input["is_template"]==1)
				updateState(PHONE_TYPE,$newID,$input["_state"],1,0);
			else updateState(PHONE_TYPE,$newID,$input["_state"],0,0);
		}

		// ADD Infocoms
		$ic= new Infocom();
		if ($ic->getFromDBforDevice(PHONE_TYPE,$input["_oldID"])){
			$ic->fields["FK_device"]=$newID;
			unset ($ic->fields["ID"]);
			if (isset($ic->fields["num_immo"])) {
				$ic->fields["num_immo"] = autoName($ic->fields["num_immo"], "num_immo", 1, INFOCOM_TYPE);
			}

			$ic->addToDB();
		}

		// ADD Ports
		$query="SELECT ID from glpi_networking_ports WHERE on_device='".$input["_oldID"]."' AND device_type='".PHONE_TYPE."';";
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
		$query="SELECT FK_contract from glpi_contract_device WHERE FK_device='".$input["_oldID"]."' AND device_type='".PHONE_TYPE."';";
		$result=$db->query($query);
		if ($db->numrows($result)>0){

			while ($data=$db->fetch_array($result))
				addDeviceContract($data["FK_contract"],PHONE_TYPE,$newID);
		}

		// ADD Documents			
		$query="SELECT FK_doc from glpi_doc_device WHERE FK_device='".$input["_oldID"]."' AND device_type='".PHONE_TYPE."';";
		$result=$db->query($query);
		if ($db->numrows($result)>0){

			while ($data=$db->fetch_array($result))
				addDeviceDocument($data["FK_doc"],PHONE_TYPE,$newID);
		}

	}



	function cleanDBonPurge($ID) {

		global $db,$cfg_glpi;


		$job =new Job();
		$query = "SELECT * FROM glpi_tracking WHERE (computer = '$ID'  AND device_type='".PHONE_TYPE."')";
		$result = $db->query($query);

		if ($db->numrows($result))
			while ($data=$db->fetch_array($result)) {
				if ($cfg_glpi["keep_tracking_on_delete"]==1){
					$query = "UPDATE glpi_tracking SET computer = '0', device_type='0' WHERE ID='".$data["ID"]."';";
					$db->query($query);
				} else $job->delete(array("ID"=>$data["ID"]));
			}

		$query="select * from glpi_reservation_item where (device_type='".PHONE_TYPE."' and id_device='$ID')";
		if ($result = $db->query($query)) {
			if ($db->numrows($result)>0){
				$rr=new ReservationItem();
				$rr->delete(array("ID"=>$db->result($result,0,"ID")));
			}
		}

		$query = "DELETE FROM glpi_infocoms WHERE (FK_device = '$ID' AND device_type='".PHONE_TYPE."')";
		$result = $db->query($query);


		$query = "DELETE FROM glpi_state_item WHERE (id_device = '$ID' AND device_type='".PHONE_TYPE."')";
		$result = $db->query($query);

		$query2 = "DELETE from glpi_connect_wire WHERE (end1 = '$ID' AND type = '".PHONE_TYPE."')";
		$result2 = $db->query($query2);

		$query = "DELETE FROM glpi_contract_device WHERE (FK_device = '$ID' AND device_type='".PHONE_TYPE."')";
		$result = $db->query($query);
	}


	function title(){
		global  $lang,$HTMLRel;
		echo "<div align='center'><table border='0'><tr><td>";
		echo "<img src=\"".$HTMLRel."pics/phones.png\" alt='".$lang["phones"][0]."' title='".$lang["phones"][0]."'></td>";
		if (haveRight("phone","w")){
			echo "<td><a  class='icon_consol' href=\"".$HTMLRel."front/setup.templates.php?type=".PHONE_TYPE."&amp;add=1\"><b>".$lang["phones"][0]."</b></a>";
			echo "</td>";
			echo "<td><a class='icon_consol' href='".$HTMLRel."front/setup.templates.php?type=".PHONE_TYPE."&amp;add=0'>".$lang["common"][8]."</a></td>";
		} else echo "<td><span class='icon_sous_nav'><b>".$lang["Menu"][34]."</b></span></td>";
		echo "</tr></table></div>";
	}


	function showForm ($target,$ID,$withtemplate='') {

		global $cfg_glpi, $lang,$HTMLRel;

		if (!haveRight("phone","r")) return false;



		$mon_spotted = false;

		if(empty($ID) && $withtemplate == 1) {
			if($this->getEmpty()) $mon_spotted = true;
		} else {
			if($this->getfromDB($ID)) $mon_spotted = true;
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
				$date = convDateTime($this->fields["date_mod"]);
				$template = false;
			}


			echo "<div align='center'>";
			echo "<form method='post' name=form action=\"$target\">";
			if(strcmp($template,"newtemplate") === 0) {
				echo "<input type=\"hidden\" name=\"is_template\" value=\"1\" />";
			}

			echo "<table width='950' class='tab_cadre' cellpadding='2'>";

			echo "<tr><th align='center' >";



			if(!$template) {
				echo $lang["phones"][29].": ".$this->fields["ID"];
			}elseif (strcmp($template,"newcomp") === 0) {
				echo $lang["phones"][30].": ".$this->fields["tplname"];
				echo "<input type='hidden' name='tplname' value='".$this->fields["tplname"]."'>";
			}elseif (strcmp($template,"newtemplate") === 0) {
				echo $lang["common"][6]."&nbsp;: ";
				autocompletionTextField("tplname","glpi_phones","tplname",$this->fields["tplname"],20);	
			}

			echo "</th><th  align='center'>".$datestring.$date;
			if (!$template&&!empty($this->fields['tplname']))
				echo "&nbsp;&nbsp;&nbsp;(".$lang["common"][13].": ".$this->fields['tplname'].")";
			echo "</th></tr>";

			echo "<tr><td class='tab_bg_1' valign='top'>";

			echo "<table cellpadding='1' cellspacing='0' border='0'>\n";

			echo "<tr><td>".$lang["common"][16]."*:	</td>";
			echo "<td>";
			$objectName = autoName($this->fields["name"], "name", ($template === "newcomp"), PHONE_TYPE);
			autocompletionTextField("name","glpi_phones","name",$objectName,20);

			//autocompletionTextField("name","glpi_phones","name",$this->fields["name"],20);		
			echo "</td></tr>";

			echo "<tr><td>".$lang["common"][15].": 	</td><td>";
			dropdownValue("glpi_dropdown_locations", "location", $this->fields["location"]);
			echo "</td></tr>";

			echo "<tr class='tab_bg_1'><td>".$lang["common"][10].": 	</td><td colspan='2'>";
			dropdownUsersID("tech_num", $this->fields["tech_num"],"interface");
			echo "</td></tr>";

			echo "<tr><td>".$lang["common"][21].":	</td><td>";
			autocompletionTextField("contact_num","glpi_phones","contact_num",$this->fields["contact_num"],20);		
			echo "</td></tr>";

			echo "<tr><td>".$lang["common"][18].":	</td><td>";
			autocompletionTextField("contact","glpi_phones","contact",$this->fields["contact"],20);		
			echo "</td></tr>";

			echo "<tr><td>".$lang["common"][34].": 	</td><td>";
			dropdownAllUsers("FK_users", $this->fields["FK_users"]);
			echo "</td></tr>";

			echo "<tr><td>".$lang["common"][35].": 	</td><td>";
			dropdownValue("glpi_groups", "FK_groups", $this->fields["FK_groups"]);
			echo "</td></tr>";

			if (!$template){
				echo "<tr><td>".$lang["reservation"][24].":</td><td><b>";
				showReservationForm(PHONE_TYPE,$ID);
				echo "</b></td></tr>";
			}


			echo "<tr><td>".$lang["common"][17].": 	</td><td>";
			dropdownValue("glpi_type_phones", "type", $this->fields["type"]);
			echo "</td></tr>";

			echo "<tr><td>".$lang["common"][22].": 	</td><td>";
			dropdownValue("glpi_dropdown_model_phones", "model", $this->fields["model"]);
			echo "</td></tr>";

			echo "</table>";

			echo "</td>\n";	
			echo "<td class='tab_bg_1' valign='top'>";

			echo "<table cellpadding='1' cellspacing='0' border='0'>";


			echo "<tr><td>".$lang["phones"][33].":</td><td>";
			globalManagementDropdown($target,$withtemplate,$this->fields["ID"],$this->fields["is_global"]);	
			echo "</td></tr>";

			echo "<tr><td>".$lang["phones"][36].":</td><td>";
			dropdownValue("glpi_dropdown_phone_power", "power", $this->fields["power"]);
			echo "</td></tr>";


			echo "<tr class='tab_bg_1'><td>".$lang["common"][5].": 	</td><td colspan='2'>";
			dropdownValue("glpi_enterprises","FK_glpi_enterprise",$this->fields["FK_glpi_enterprise"]);
			echo "</td></tr>";

			echo "<tr><td>".$lang["phones"][18].":</td><td>";
			autocompletionTextField("brand","glpi_phones","brand",$this->fields["brand"],20);		
			echo "</td></tr>";


			echo "<tr><td>".$lang["common"][19].":	</td><td>";
			autocompletionTextField("serial","glpi_phones","serial",$this->fields["serial"],20);		
			echo "</td></tr>";

			echo "<tr><td>".$lang["common"][20]."*:</td><td>";
			$objectName = autoName($this->fields["otherserial"], "otherserial", ($template === "newcomp"), PHONE_TYPE);
			autocompletionTextField("otherserial","glpi_phones","otherserial",$objectName,20);

			//autocompletionTextField("otherserial","glpi_phones","otherserial",$this->fields["otherserial"],20);		
			echo "</td></tr>";


			echo "<tr><td>".$lang["phones"][35].":	</td><td>";
			autocompletionTextField("firmware","glpi_phones","firmware",$this->fields["firmware"],20);		
			echo "</td></tr>";


			echo "<tr><td>".$lang["state"][0].":</td><td>";
			$si=new StateItem();
			$t=0;
			if ($template) $t=1;
			$si->getfromDB(PHONE_TYPE,$this->fields["ID"],$t);
			dropdownValue("glpi_dropdown_state", "state",$si->fields["state"]);
			echo "</td></tr>";

			echo "<tr><td>".$lang["phones"][40].":	</td><td>";
			autocompletionTextField("number_line","glpi_phones","number_line",$this->fields["number_line"],20);		
			echo "</td></tr>";


			echo "<tr><td>".$lang["phones"][37].": </td><td>";

			// micro?
			echo "<table border='0' cellpadding='2' cellspacing='0'><tr>";
			echo "<td>";
			if ($this->fields["flags_casque"] == 1) {
				echo "<input type='checkbox' name='flags_casque' value='1' checked>";
			} else {
				echo "<input type='checkbox' name='flags_casque' value='1'>";
			}
			echo "</td><td>".$lang["phones"][38]."</td></tr>";

			// hp?
			echo "<tr><td>";
			if ($this->fields["flags_hp"] == 1) {
				echo "<input type='checkbox' name='flags_hp' value='1' checked>";
			} else {
				echo "<input type='checkbox' name='flags_hp' value='1'>";
			}
			echo "</td><td>".$lang["phones"][39]."</td>";

			echo "</tr></table>";
			echo "</td></tr>";
			echo "</table>";
			echo "</td>\n";	
			echo "</tr>";
			echo "<tr>";
			echo "<td class='tab_bg_1' valign='top' colspan='2'>";

			echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'><tr><td valign='top'>";
			echo $lang["common"][25].":	</td>";
			echo "<td align='center'><textarea cols='35' rows='4' name='comments' >".$this->fields["comments"]."</textarea>";
			echo "</td></tr></table>";

			echo "</td>";
			echo "</tr>";

			if (haveRight("phone","w")){
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
			echo "<div align='center'><b>".$lang["phones"][17]."</b></div>";
			return false;
		}

	}



}

?>
