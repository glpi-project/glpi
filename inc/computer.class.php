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


// CLASSES Computers


class Computer extends CommonDBTM {


	//format $device = array(ID,"ID type periph","ID dans la table device","valeur de specificity")
	var $devices	= array();

	function Computer () {
		$this->table="glpi_computers";
		$this->type=COMPUTER_TYPE;
		$this->dohistory=true;
	}

	function defineOnglets($withtemplate){
		global $lang,$cfg_glpi;

		$ong[1]=$lang["title"][26];
		if (haveRight("software","r"))	
			$ong[2]=$lang["title"][12];
		if (haveRight("networking","r")||haveRight("printer","r")||haveRight("monitor","r")||haveRight("peripheral","r")||haveRight("phone","r"))	
			$ong[3]=$lang["title"][27];
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

			if ($cfg_glpi["ocs_mode"]&&haveRight("ocsng","w"))
				$ong[13]=$lang["Menu"][33];
		}	
		return $ong;
	}

	function getFromDBwithDevices ($ID) {

		global $db;

		if ($this->getFromDB($ID)){
			$query = "SELECT count(*) AS NB, ID, device_type, FK_device, specificity FROM glpi_computer_device WHERE FK_computers = '$ID' GROUP BY device_type, FK_device, specificity ORDER BY device_type, ID";
			if ($result = $db->query($query)) {
				if ($db->numrows($result)>0) {
					$i = 0;
					while($data = $db->fetch_array($result)) {
						$this->devices[$i] = array("compDevID"=>$data["ID"],"devType"=>$data["device_type"],"devID"=>$data["FK_device"],"specificity"=>$data["specificity"],"quantity"=>$data["NB"]);
						$i++;
					}
				}
				return true;
			} 
		}
		return false;
	}

	function prepareInputForUpdate($input) {
		// set new date.
		$input["date_mod"] = date("Y-m-d H:i:s");

		return $input;
	}

	function post_updateItem($input,$updates,$history=1) {
		global $db;
		// Manage changes for OCS if more than 1 element (date_mod)
		if ($this->fields["ocs_import"]&&$history==1&&count($updates)>1){
			mergeOcsArray($this->fields["ID"],$updates,"computer_update");
		}

		if(isset($input["state"])){
			if (isset($input["is_template"])&&$input["is_template"]==1){
				updateState(COMPUTER_TYPE,$input["ID"],$input["state"],1,0);
			}else {
				updateState(COMPUTER_TYPE,$input["ID"],$input["state"],0,$history);
			}
		}

		if (isset($input["_auto_update_ocs"])){
			$query="UPDATE glpi_ocs_link SET auto_update='".$input["_auto_update_ocs"]."' 	WHERE glpi_id='".$input["ID"]."'";
			$db->query($query);
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
				updateState(COMPUTER_TYPE,$newID,$input["_state"],1,0);
			else updateState(COMPUTER_TYPE,$newID,$input["_state"],0,0);
		}

		// ADD Devices
		$this->getFromDBwithDevices($input["_oldID"]);
		foreach($this->devices as $key => $val) {
			for ($i=0;$i<$val["quantity"];$i++){
				compdevice_add($newID,$val["devType"],$val["devID"],$val["specificity"],0);
			}
		}

		// ADD Infocoms
		$ic= new Infocom();
		if ($ic->getFromDBforDevice(COMPUTER_TYPE,$input["_oldID"])){
			$ic->fields["FK_device"]=$newID;
			unset ($ic->fields["ID"]);
			if (isset($ic->fields["num_immo"])) {
				$ic->fields["num_immo"] = autoName($ic->fields["num_immo"], "num_immo", 1, INFOCOM_TYPE);
			}
			$ic->addToDB();
		}

		// ADD software
		$query="SELECT license from glpi_inst_software WHERE cID='".$input["_oldID"]."'";
		$result=$db->query($query);
		if ($db->numrows($result)>0){
			while ($data=$db->fetch_array($result))
				installSoftware($newID,$data['license']);
		}

		// ADD Contract				
		$query="SELECT FK_contract from glpi_contract_device WHERE FK_device='".$input["_oldID"]."' AND device_type='".COMPUTER_TYPE."';";
		$result=$db->query($query);
		if ($db->numrows($result)>0){
			while ($data=$db->fetch_array($result))
				addDeviceContract($data["FK_contract"],COMPUTER_TYPE,$newID);
		}

		// ADD Documents			
		$query="SELECT FK_doc from glpi_doc_device WHERE FK_device='".$input["_oldID"]."' AND device_type='".COMPUTER_TYPE."';";
		$result=$db->query($query);
		if ($db->numrows($result)>0){
			while ($data=$db->fetch_array($result))
				addDeviceDocument($data["FK_doc"],COMPUTER_TYPE,$newID);
		}

		// ADD Ports
		$query="SELECT ID from glpi_networking_ports WHERE on_device='".$input["_oldID"]."' AND device_type='".COMPUTER_TYPE."';";
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

		// Add connected devices
		$query="SELECT * from glpi_connect_wire WHERE end2='".$input["_oldID"]."';";

		$result=$db->query($query);
		if ($db->numrows($result)>0){
			while ($data=$db->fetch_array($result)){
				Connect($data["end1"],$newID,$data["type"]);
			}
		}

	}

	function post_updateInDB($updates)  {
		global $db,$lang;


		for ($i=0; $i < count($updates); $i++) {

			// Mise a jour du contact des éléments rattachés
			if ($updates[$i]=="contact" ||$updates[$i]=="contact_num"){
				$items=array(PRINTER_TYPE,MONITOR_TYPE,PERIPHERAL_TYPE,PHONE_TYPE);
				$ci=new CommonItem();
				$update_done=false;
				$updates3[0]="contact";
				$updates3[1]="contact_num";

				foreach ($items as $t){
					$query = "SELECT * from glpi_connect_wire WHERE end2='".$this->fields["ID"]."' AND type='".$t."'";
					if ($result=$db->query($query)) {
						$resultnum = $db->numrows($result);
						if ($resultnum>0) {
							for ($j=0; $j < $resultnum; $j++) {
								$tID = $db->result($result, $j, "end1");
								$ci->getfromDB($t,$tID);
								if (!$ci->obj->fields['is_global']){
									if ($ci->obj->fields['contact']!=$this->fields['contact']||$ci->obj->fields['contact_num']!=$this->fields['contact_num']){
										$input["ID"]=$ci->obj->fields["ID"];
										$input['contact']=$this->fields['contact'];
										$input['contact_num']=$this->fields['contact_num'];
										$ci->obj->update($input);
										$update_done=true;
									}
								}
							}
						}
					}
				}

				if ($update_done) {
					if (!empty($_SESSION["MESSAGE_AFTER_REDIRECT"])) $_SESSION["MESSAGE_AFTER_REDIRECT"].="<br>";
					$_SESSION["MESSAGE_AFTER_REDIRECT"].=$lang["computers"][49];
				}

			}

			// Mise a jour des users et groupes des éléments rattachés
			if (($updates[$i]=="FK_users" && $this->fields["FK_users"]!=0)||($updates[$i]=="FK_groups" && $this->fields["FK_groups"]!=0)){
				$items=array(PRINTER_TYPE,MONITOR_TYPE,PERIPHERAL_TYPE,PHONE_TYPE);
				$ci=new CommonItem();
				$update_done=false;
				$updates4[0]="FK_users";
				$updates4[1]="FK_groups";

				foreach ($items as $t){
					$query = "SELECT * from glpi_connect_wire WHERE end2='".$this->fields["ID"]."' AND type='".$t."'";

					if ($result=$db->query($query)) {
						$resultnum = $db->numrows($result);

						if ($resultnum>0) {
							for ($j=0; $j < $resultnum; $j++) {
								$tID = $db->result($result, $j, "end1");

								$ci->getfromDB($t,$tID);
								if (!$ci->obj->fields['is_global']){
									if ($ci->obj->fields["FK_users"]!=$this->fields["FK_users"]||$ci->obj->fields["FK_groups"]!=$this->fields["FK_groups"]){
										$input["ID"]=$ci->obj->fields["ID"];
										$input["FK_users"]=$this->fields["FK_users"];
										$input["FK_groups"]=$this->fields["FK_groups"];
										$ci->obj->update($input);
										$update_done=true;
									}
								}
							}
						}
					}
				}
				if ($update_done) {
					if (!empty($_SESSION["MESSAGE_AFTER_REDIRECT"])) $_SESSION["MESSAGE_AFTER_REDIRECT"].="<br>";
					$_SESSION["MESSAGE_AFTER_REDIRECT"].=$lang["computers"][50];
				}

			}


			// Mise a jour des lieux des éléments rattachés
			if ($updates[$i]=="location" && $this->fields["location"]!=0){
				$items=array(PRINTER_TYPE,MONITOR_TYPE,PERIPHERAL_TYPE,PHONE_TYPE);
				$ci=new CommonItem();
				$update_done=false;
				$updates2[0]="location";

				foreach ($items as $t){
					$query = "SELECT * from glpi_connect_wire WHERE end2='".$this->fields["ID"]."' AND type='".$t."'";

					if ($result=$db->query($query)) {
						$resultnum = $db->numrows($result);

						if ($resultnum>0) {
							for ($j=0; $j < $resultnum; $j++) {
								$tID = $db->result($result, $j, "end1");

								$ci->getfromDB($t,$tID);
								if (!$ci->obj->fields['is_global']){
									if ($ci->obj->fields["location"]!=$this->fields["location"]){
										$input["ID"]=$ci->obj->fields["ID"];
										$input["location"]=$this->fields["location"];
										$ci->obj->update($input);
										$update_done=true;
									}
								}
							}
						}
					}
				}
				if ($update_done) {
					if (!empty($_SESSION["MESSAGE_AFTER_REDIRECT"])) $_SESSION["MESSAGE_AFTER_REDIRECT"].="<br>";
					$_SESSION["MESSAGE_AFTER_REDIRECT"].=$lang["computers"][48];
				}

			}

		}


	}


	function cleanDBonPurge($ID) {
		global $db,$cfg_glpi;

		$job=new Job;

		$query = "SELECT * FROM glpi_tracking WHERE (computer = '$ID'  AND device_type='".COMPUTER_TYPE."')";
		$result = $db->query($query);

		if ($db->numrows($result))
			while ($data=$db->fetch_array($result)) {
				if ($cfg_glpi["keep_tracking_on_delete"]==1){
					$query = "UPDATE glpi_tracking SET computer = '0', device_type='0' WHERE ID='".$data["ID"]."';";
					$db->query($query);
				} else $job->delete(array("ID"=>$data["ID"]));
			}

		$query = "DELETE FROM glpi_inst_software WHERE (cID = '$ID')";
		$result = $db->query($query);		

		$query = "DELETE FROM glpi_contract_device WHERE (FK_device = '$ID' AND device_type='".COMPUTER_TYPE."')";
		$result = $db->query($query);

		$query = "DELETE FROM glpi_state_item WHERE (id_device = '$ID' AND device_type='".COMPUTER_TYPE."')";
		$result = $db->query($query);

		$query = "DELETE FROM glpi_infocoms WHERE (FK_device = '$ID' AND device_type='".COMPUTER_TYPE."')";
		$result = $db->query($query);

		$query = "SELECT ID FROM glpi_networking_ports WHERE (on_device = '$ID' AND device_type = '".COMPUTER_TYPE."')";
		$result = $db->query($query);
		while ($data = $db->fetch_array($result)){
			$q = "DELETE FROM glpi_networking_wire WHERE (end1 = '".$data["ID"]."' OR end2 = '".$data["ID"]."')";
			$result2 = $db->query($q);					
		}	

		$query = "DELETE FROM glpi_networking_ports WHERE (on_device = '$ID' AND device_type = '".COMPUTER_TYPE."')";
		$result = $db->query($query);
		$query = "DELETE FROM glpi_connect_wire WHERE (end2 = '$ID')";
		$result = $db->query($query);

		$query="select * from glpi_reservation_item where (device_type='".COMPUTER_TYPE."' and id_device='$ID')";
		if ($result = $db->query($query)) {
			if ($db->numrows($result)>0) {
				$rr=new ReservationItem();
				$rr->delete(array("ID"=>$db->result($result,0,"ID")));
			}
		}

		$query = "DELETE FROM glpi_computer_device WHERE (FK_computers = '$ID')";
		$result = $db->query($query);

		$query = "DELETE FROM glpi_ocs_link WHERE (glpi_id = '$ID')";
		$result = $db->query($query);
	}

	/**
	 * Print a good title for computer pages
	 *
	 *@return nothing (diplays)
	 *
	 **/
	function title(){
		global  $lang,$HTMLRel;

		echo "<div align='center'><table border='0'><tr><td>";
		echo "<img src=\"".$HTMLRel."pics/computer.png\" alt='".$lang["computers"][0]."' title='".$lang["computers"][0]."'></td>";
		if (haveRight("computer","w")){
			echo "<td><a  class='icon_consol' href=\"".$HTMLRel."front/setup.templates.php?type=".COMPUTER_TYPE."&amp;add=1\"><b>".$lang["computers"][0]."</b></a>";
			echo "</td>";
			echo "<td><a class='icon_consol' href='".$HTMLRel."front/setup.templates.php?type=".COMPUTER_TYPE."&amp;add=0'>".$lang["common"][8]."</a></td>";
		} else echo "<td><span class='icon_sous_nav'><b>".$lang["Menu"][0]."</b></span></td>";
		echo "</tr></table></div>";

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
	function showForm($target,$ID,$withtemplate='') {
		global $lang,$HTMLRel,$cfg_glpi,$db;

		if (!haveRight("computer","r")) return false;


		$computer_spotted = false;
		if(empty($ID) && $withtemplate == 1) {
			if($this->getEmpty()) $computer_spotted = true;
		} else {
			if($this->getfromDB($ID)) $computer_spotted = true;
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
				$date = convDateTime($this->fields["date_mod"]);
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
				echo $lang["computers"][13].": ".$this->fields["ID"];
			}elseif (strcmp($template,"newcomp") === 0) {
				echo $lang["computers"][12].": ".$this->fields["tplname"];
				echo "<input type='hidden' name='tplname' value='".$this->fields["tplname"]."'>";
			}elseif (strcmp($template,"newtemplate") === 0) {
				echo $lang["common"][6]."&nbsp;: ";
				autocompletionTextField("tplname","glpi_computers","tplname",$this->fields["tplname"],20);	
			}


			echo "</th><th  colspan ='2' align='center'>".$datestring.$date;
			if (!$template&&!empty($this->fields['tplname']))
				echo "&nbsp;&nbsp;&nbsp;(".$lang["common"][13].": ".$this->fields['tplname'].")";
			if ($this->fields["ocs_import"])
				echo "&nbsp;&nbsp;&nbsp;(".$lang["ocsng"][7].")";

			echo "</th></tr>";

			echo "<tr class='tab_bg_1'><td>".$lang["common"][16]."*:		</td>";

			echo "<td>";

			$objectName = autoName($this->fields["name"], "name", ($template === "newcomp"), COMPUTER_TYPE);
			autocompletionTextField("name","glpi_computers","name",$objectName,20);

			//autocompletionTextField("name","glpi_computers","name",$this->fields["name"],20);
			echo "</td>";

			echo "<td>".$lang["common"][18].":	</td><td>";
			autocompletionTextField("contact","glpi_computers","contact",$this->fields["contact"],20);

			echo "</td></tr>";

			echo "<tr class='tab_bg_1'>";
			echo "<td >".$lang["common"][17].": 	</td>";
			echo "<td >";
			dropdownValue("glpi_type_computers", "type", $this->fields["type"]);

			echo "</td>";



			echo "<td>".$lang["common"][21].":		</td><td>";
			autocompletionTextField("contact_num","glpi_computers","contact_num",$this->fields["contact_num"],20);
			echo "</td></tr>";

			echo "<tr class='tab_bg_1'>";
			echo "<td >".$lang["common"][34].": 	</td>";
			echo "<td >";
			dropdownAllUsers("FK_users", $this->fields["FK_users"]);
			echo "</td>";


			echo "<td>".$lang["common"][35].":</td><td>";
			dropdownValue("glpi_groups", "FK_groups", $this->fields["FK_groups"]);
			echo "</td></tr>";

			echo "<tr class='tab_bg_1'>";
			echo "<td >".$lang["common"][22].": 	</td>";
			echo "<td >";
			dropdownValue("glpi_dropdown_model", "model", $this->fields["model"]);

			echo "</td>";


			echo "<td>".$lang["setup"][88].":</td><td>";
			dropdownValue("glpi_dropdown_network", "network", $this->fields["network"]);
			echo "</td></tr>";

			echo "<tr class='tab_bg_1'>";
			echo "<td >".$lang["common"][15].": 	</td>";
			echo "<td >";
			dropdownValue("glpi_dropdown_locations", "location", $this->fields["location"]);
			echo "</td>";


			echo "<td>".$lang["setup"][89].":</td><td>";
			dropdownValue("glpi_dropdown_domain", "domain", $this->fields["domain"]);
			echo "</td></tr>";


			echo "<tr class='tab_bg_1'>";
			echo "<td >".$lang["common"][10].": 	</td>";
			echo "<td >";
			dropdownUsersID("tech_num",$this->fields["tech_num"],"interface");
			echo "</td>";

			echo "<td>".$lang["common"][19].":	</td><td>";
			autocompletionTextField("serial","glpi_computers","serial",$this->fields["serial"],20);
			echo "</td></tr>";

			echo "<tr class='tab_bg_1'>";
			echo "<td>".$lang["common"][5].": 	</td><td>";
			dropdownValue("glpi_enterprises","FK_glpi_enterprise",$this->fields["FK_glpi_enterprise"]);
			echo "</td>";

			echo "<td>".$lang["common"][20]."*:	</td><td>";
			$objectName = autoName($this->fields["otherserial"], "otherserial", ($template === "newcomp"), COMPUTER_TYPE);
			autocompletionTextField("otherserial","glpi_computers","otherserial",$objectName,20);

			//autocompletionTextField("otherserial","glpi_computers","otherserial",$this->fields["otherserial"],20);
			echo "</td></tr>";

			echo "<tr class='tab_bg_1'>";

			echo "<td>".$lang["computers"][9].":</td><td>";
			dropdownValue("glpi_dropdown_os", "os", $this->fields["os"]);
			echo "</td>";

			echo "<td>".$lang["state"][0].":</td><td>";
			$si=new StateItem();
			$t=0;
			if ($template) $t=1;
			$si->getfromDB(COMPUTER_TYPE,$this->fields["ID"],$t);
			dropdownValue("glpi_dropdown_state", "state",$si->fields["state"]);
			echo "</td></tr>";

			echo "<tr class='tab_bg_1'>";

			echo "<td>".$lang["computers"][52].":</td><td>";
			dropdownValue("glpi_dropdown_os_version", "os_version", $this->fields["os_version"]);
			echo "</td>";

			if (!$template){
				echo "<td>".$lang["reservation"][24].":</td><td><b>";
				showReservationForm(COMPUTER_TYPE,$ID);
				echo "</b></td>";
			} else echo "<td>&nbsp;</td><td>&nbsp;</td>";
			echo "</tr>";

			echo "<tr class='tab_bg_1'>";
			echo "<td>".$lang["computers"][53].":</td><td>";
			dropdownValue("glpi_dropdown_os_sp", "os_sp", $this->fields["os_sp"]);
			echo "</td>";

			echo "<td valign='middle' rowspan='2'>".$lang["common"][25].":</td><td valign='middle' rowspan='2'><textarea  cols='50' rows='3' name='comments' >".$this->fields["comments"]."</textarea></td>";
			echo "</tr>";
			echo "<tr class='tab_bg_1'>";
			echo "<td>".$lang["computers"][51].":</td><td>";
			dropdownValue("glpi_dropdown_auto_update", "auto_update", $this->fields["auto_update"]);
			echo "</td>";
			echo "</tr>";

			if (!empty($ID)&&$this->fields["ocs_import"]){
				$query="SELECT * 
					FROM glpi_ocs_link 
					WHERE glpi_id='$ID'";

				$result=$db->query($query);
				if ($db->numrows($result)==1){
					$data=$db->fetch_array($result);
					echo "<tr class='tab_bg_1'>";
					echo "<td colspan='2' align='center'>";
					echo $lang["ocsng"][14].": ".convDateTime($data["last_ocs_update"]);
					echo "<br>";
					echo $lang["ocsng"][13].": ".convDateTime($data["last_update"]);
					echo "</td>";
					echo "<td >".$lang["ocsng"][6]." ".$lang["Menu"][33].": 	</td>";
					echo "<td >";
					dropdownYesNoInt("_auto_update_ocs",$data["auto_update"]);
					echo "</td>";

					echo "</tr>";
				}
			}

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
					if ($this->fields["deleted"]=='N')
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

}


?>
