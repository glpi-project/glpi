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

// CLASSES Computers


class Computer extends CommonDBTM {


	//Device container - format $device = array(ID,"device type","ID in device table","specificity value")
	var $devices	= array();

	/**
	 * Constructor
	 *
	**/
	function Computer () {
		$this->table="glpi_computers";
		$this->type=COMPUTER_TYPE;
		$this->dohistory=true;
	}

	/**
	 * Define onglets to display
	 *
	 *@param $withtemplate is a template view ? 
	 * 
	 *@return array containing the onglets 
	**/
	function defineOnglets($withtemplate){
		global $LANG,$CFG_GLPI;

		$ong[1]=$LANG["title"][26];
		if (haveRight("software","r"))	{
			$ong[2]=$LANG["Menu"][4];
		}
		if (haveRight("networking","r")||haveRight("printer","r")||haveRight("monitor","r")||haveRight("peripheral","r")||haveRight("phone","r")){	
			$ong[3]=$LANG["title"][27];
		}
		if (haveRight("contract_infocom","r")){
			$ong[4]=$LANG["Menu"][26];
		}
		if (haveRight("document","r")){
			$ong[5]=$LANG["Menu"][27];
		}

		if(empty($withtemplate)){
			if ($CFG_GLPI["ocs_mode"]){
				$ong[14]=$LANG["title"][43];
			}
			if (haveRight("show_all_ticket","1")){
				$ong[6]=$LANG["title"][28];
			}
			if (haveRight("link","r")){
				$ong[7]=$LANG["title"][34];
			}
			if (haveRight("notes","r")){
				$ong[10]=$LANG["title"][37];
			}
			if (haveRight("reservation_central","r")){
				$ong[11]=$LANG["Menu"][17];
			}
				
			$ong[12]=$LANG["title"][38];

			if ($CFG_GLPI["ocs_mode"]&&(haveRight("sync_ocsng","w")||haveRight("computer","w"))){
				$ong[13]=$LANG["Menu"][33];
			}
		}	
		return $ong;
	}
	/**
	 * Retrieve an item from the database with device associated
	 *
	 *@param $ID ID of the item to get
	 *@return true if succeed else false
	**/
	function getFromDBwithDevices ($ID) {

		global $DB;

		if ($this->getFromDB($ID)){
			$query = "SELECT count(*) AS NB, ID, device_type, FK_device, specificity FROM glpi_computer_device WHERE FK_computers = '$ID' GROUP BY device_type, FK_device, specificity ORDER BY device_type, ID";
			if ($result = $DB->query($query)) {
				if ($DB->numrows($result)>0) {
					$i = 0;
					while($data = $DB->fetch_array($result)) {
						$this->devices[$i] = array("compDevID"=>$data["ID"],"devType"=>$data["device_type"],"devID"=>$data["FK_device"],"specificity"=>$data["specificity"],"quantity"=>$data["NB"]);
						$i++;
					}
				}
				return true;
			} 
		}
		return false;
	}

	/**
	 * Actions done before the UPDATE of the item in the database 
	 *
	 *@param $input datas used to update the item
	 *@param $updates array of the updated fields 
	 * 
	 *@return nothing 
	 * 
	**/
	function pre_updateInDB($input,$updates) {
		$this->fields["date_mod"]=$_SESSION["glpi_currenttime"];
		$updates[]="date_mod";
		return array($input,$updates);
	}

	/**
	 * Actions done after the UPDATE of the item in the database
	 *
	 *@param $input datas used to update the item
	 *@param $updates array of the updated fields
	 *@param $history store changes history ? 
	 * 
	 *@return nothing
	 * 
	**/
	function post_updateItem($input,$updates,$history=1) {
		global $DB,$LANG,$CFG_GLPI;
		
		// Manage changes for OCS if more than 1 element (date_mod)
		// Need dohistory==1 if dohistory==2 no locking fields
		if ($this->fields["ocs_import"]&&$history==1&&count($updates)>1){
			mergeOcsArray($this->fields["ID"],$updates,"computer_update");
		}

		if (isset($input["_auto_update_ocs"])){
			$query="UPDATE glpi_ocs_link SET auto_update='".$input["_auto_update_ocs"]."' 	WHERE glpi_id='".$input["ID"]."'";
			$DB->query($query);
		}

		for ($i=0; $i < count($updates); $i++) {

			// Update contact of attached items
			
			if (($updates[$i]=="contact" ||$updates[$i]=="contact_num")&&$CFG_GLPI["autoupdate_link_contact"]){
				$items=array(PRINTER_TYPE,MONITOR_TYPE,PERIPHERAL_TYPE,PHONE_TYPE);
				$ci=new CommonItem();
				$update_done=false;
				$updates3[0]="contact";
				$updates3[1]="contact_num";

				foreach ($items as $t){
					$query = "SELECT * from glpi_connect_wire WHERE end2='".$this->fields["ID"]."' AND type='".$t."'";
					if ($result=$DB->query($query)) {
						$resultnum = $DB->numrows($result);
						if ($resultnum>0) {
							for ($j=0; $j < $resultnum; $j++) {
								$tID = $DB->result($result, $j, "end1");
								$ci->getFromDB($t,$tID);
								if (!$ci->getField('is_global')){
									if ($ci->getField('contact')!=$this->fields['contact']||$ci->getField('contact_num')!=$this->fields['contact_num']){
										$tmp["ID"]=$ci->getField('ID');
										$tmp['contact']=$this->fields['contact'];
										$tmp['contact_num']=$this->fields['contact_num'];
										$ci->obj->update($tmp);
										$update_done=true;
									}
								}
							}
						}
					}
				}

				if ($update_done) {
					addMessageAfterRedirect($LANG["computers"][49],true);
				}

			}

			// Update users and groups of attached items
			if (($updates[$i]=="FK_users" && $this->fields["FK_users"]!=0 && $CFG_GLPI["autoupdate_link_user"])||($updates[$i]=="FK_groups" && $this->fields["FK_groups"]!=0 && $CFG_GLPI["autoupdate_link_group"])){
				$items=array(PRINTER_TYPE,MONITOR_TYPE,PERIPHERAL_TYPE,PHONE_TYPE);
				$ci=new CommonItem();
				$update_done=false;
				$updates4[0]="FK_users";
				$updates4[1]="FK_groups";

				foreach ($items as $t){
					$query = "SELECT * from glpi_connect_wire WHERE end2='".$this->fields["ID"]."' AND type='".$t."'";

					if ($result=$DB->query($query)) {
						$resultnum = $DB->numrows($result);

						if ($resultnum>0) {
							for ($j=0; $j < $resultnum; $j++) {
								$tID = $DB->result($result, $j, "end1");

								$ci->getFromDB($t,$tID);
								if (!$ci->getField('is_global')){
									if ($ci->getField('FK_users')!=$this->fields["FK_users"]||$ci->getField('FK_groups')!=$this->fields["FK_groups"]){
										$tmp["ID"]=$ci->getField('ID');
										if ($CFG_GLPI["autoupdate_link_user"]){
											$tmp["FK_users"]=$this->fields["FK_users"];
										}
										if ($CFG_GLPI["autoupdate_link_group"]){
											$tmp["FK_groups"]=$this->fields["FK_groups"];
										}
										$ci->obj->update($tmp);
										$update_done=true;
									}
								}
							}
						}
					}
				}
				if ($update_done) {
					addMessageAfterRedirect($LANG["computers"][50],true);
				}

			}

			// Update state of attached items
			if ($updates[$i]=="state" && $CFG_GLPI["autoupdate_link_state"]<0){
				$items=array(PRINTER_TYPE,MONITOR_TYPE,PERIPHERAL_TYPE,PHONE_TYPE);
				$ci=new CommonItem();
				$update_done=false;

				foreach ($items as $t){
					$query = "SELECT * from glpi_connect_wire WHERE end2='".$this->fields["ID"]."' AND type='".$t."'";

					if ($result=$DB->query($query)) {
						$resultnum = $DB->numrows($result);

						if ($resultnum>0) {
							for ($j=0; $j < $resultnum; $j++) {
								$tID = $DB->result($result, $j, "end1");

								$ci->getFromDB($t,$tID);
								if (!$ci->getField('is_global')){
									if ($ci->getField('state')!=$this->fields["state"]){
										$tmp["ID"]=$ci->getField('ID');
										$tmp["state"]=$this->fields["state"];
										$ci->obj->update($tmp);
										$update_done=true;
									}
								}
							}
						}
					}
				}
				if ($update_done) {
					addMessageAfterRedirect($LANG["computers"][56],true);
				}

			}


			// Update loction of attached items
			if ($updates[$i]=="location" && $this->fields["location"]!=0 && $CFG_GLPI["autoupdate_link_location"]){
				$items=array(PRINTER_TYPE,MONITOR_TYPE,PERIPHERAL_TYPE,PHONE_TYPE);
				$ci=new CommonItem();
				$update_done=false;
				$updates2[0]="location";

				foreach ($items as $t){
					$query = "SELECT * from glpi_connect_wire WHERE end2='".$this->fields["ID"]."' AND type='".$t."'";

					if ($result=$DB->query($query)) {
						$resultnum = $DB->numrows($result);

						if ($resultnum>0) {
							for ($j=0; $j < $resultnum; $j++) {
								$tID = $DB->result($result, $j, "end1");

								$ci->getFromDB($t,$tID);
								if (!$ci->getField('is_global')){
									if ($ci->getField('location')!=$this->fields["location"]){
										$tmp["ID"]=$ci->getField('ID');
										$tmp["location"]=$this->fields["location"];
										$ci->obj->update($tmp);
										$update_done=true;
									}
								}
							}
						}
					}
				}
				if ($update_done) {
					addMessageAfterRedirect($LANG["computers"][48],true);
				}

			}

		}



	}
	/**
	 * Prepare input datas for adding the item
	 *
	 *@param $input datas used to add the item
	 *
	 *@return the modified $input array
	 * 
	**/
	function prepareInputForAdd($input) {
		// set new date.
		$input["date_mod"] = $_SESSION["glpi_currenttime"];

		if (isset($input["ID"])&&$input["ID"]>0){
			$input["_oldID"]=$input["ID"];
		}
		unset($input['ID']);
		unset($input['withtemplate']);

		return $input;
	}
	/**
	 * Actions done after the ADD of the item in the database
	 *
	 *@param $newID ID of the new item
	 *@param $input datas used to add the item
	 * 
	 *@param $input datas used to add the item
	 * 
	**/
	function post_addItem($newID,$input) {
		global $DB;

		// Manage add from template
		if (isset($input["_oldID"])){
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
					$ic->fields["num_immo"] = autoName($ic->fields["num_immo"], "num_immo", 1, INFOCOM_TYPE,$input['FK_entities']);
				}
				$ic->addToDB();
			}
	
			// ADD software
			$query="SELECT license from glpi_inst_software WHERE cID='".$input["_oldID"]."'";
			$result=$DB->query($query);
			if ($DB->numrows($result)>0){
				while ($data=$DB->fetch_array($result))
					installSoftware($newID,$data['license']);
			}
	
			// ADD Contract				
			$query="SELECT FK_contract from glpi_contract_device WHERE FK_device='".$input["_oldID"]."' AND device_type='".COMPUTER_TYPE."';";
			$result=$DB->query($query);
			if ($DB->numrows($result)>0){
				while ($data=$DB->fetch_array($result))
					addDeviceContract($data["FK_contract"],COMPUTER_TYPE,$newID);
			}
	
			// ADD Documents			
			$query="SELECT FK_doc from glpi_doc_device WHERE FK_device='".$input["_oldID"]."' AND device_type='".COMPUTER_TYPE."';";
			$result=$DB->query($query);
			if ($DB->numrows($result)>0){
				while ($data=$DB->fetch_array($result))
					addDeviceDocument($data["FK_doc"],COMPUTER_TYPE,$newID);
			}
	
			// ADD Ports
			$query="SELECT ID from glpi_networking_ports WHERE on_device='".$input["_oldID"]."' AND device_type='".COMPUTER_TYPE."';";
			$result=$DB->query($query);
			if ($DB->numrows($result)>0){
				while ($data=$DB->fetch_array($result)){
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
	
			$result=$DB->query($query);
			if ($DB->numrows($result)>0){
				while ($data=$DB->fetch_array($result)){
					Connect($data["end1"],$newID,$data["type"]);
				}
			}
		}

	}

	/**
	 * Actions done when item is deleted from the database
	 *
	 *@param $ID ID of the item
	 * 
	 *@return nothing 
	**/
	function cleanDBonPurge($ID) {
		global $DB,$CFG_GLPI;

		$job=new Job;

		$query = "SELECT * FROM glpi_tracking WHERE (computer = '$ID'  AND device_type='".COMPUTER_TYPE."')";
		$result = $DB->query($query);

		if ($DB->numrows($result))
			while ($data=$DB->fetch_array($result)) {
				if ($CFG_GLPI["keep_tracking_on_delete"]==1){
					$query = "UPDATE glpi_tracking SET computer = '0', device_type='0' WHERE ID='".$data["ID"]."';";
					$DB->query($query);
				} else $job->delete(array("ID"=>$data["ID"]));
			}

		$query = "DELETE FROM glpi_inst_software WHERE (cID = '$ID')";
		$result = $DB->query($query);		

		$query = "DELETE FROM glpi_contract_device WHERE (FK_device = '$ID' AND device_type='".COMPUTER_TYPE."')";
		$result = $DB->query($query);

		$query = "DELETE FROM glpi_infocoms WHERE (FK_device = '$ID' AND device_type='".COMPUTER_TYPE."')";
		$result = $DB->query($query);

		$query = "SELECT ID FROM glpi_networking_ports WHERE (on_device = '$ID' AND device_type = '".COMPUTER_TYPE."')";
		$result = $DB->query($query);
		while ($data = $DB->fetch_array($result)){
			$q = "DELETE FROM glpi_networking_wire WHERE (end1 = '".$data["ID"]."' OR end2 = '".$data["ID"]."')";
			$result2 = $DB->query($q);					
		}	

		$query = "DELETE FROM glpi_networking_ports WHERE (on_device = '$ID' AND device_type = '".COMPUTER_TYPE."')";
		$result = $DB->query($query);


		$query="SELECT * FROM glpi_connect_wire WHERE (end2='$ID')";
		if ($result = $DB->query($query)) {
			if ($DB->numrows($result)>0) {
				while ($data = $DB->fetch_array($result)){
					// Disconnect without auto actions
					Disconnect($data["ID"],1,false);
				}
			}
		}


		$query = "DELETE FROM glpi_registry WHERE (computer_id = '$ID')";
		$result = $DB->query($query);

		$query="select * from glpi_reservation_item where (device_type='".COMPUTER_TYPE."' and id_device='$ID')";
		if ($result = $DB->query($query)) {
			if ($DB->numrows($result)>0) {
				$rr=new ReservationItem();
				$rr->delete(array("ID"=>$DB->result($result,0,"ID")));
			}
		}

		$query = "DELETE FROM glpi_computer_device WHERE (FK_computers = '$ID')";
		$result = $DB->query($query);

		$query = "DELETE FROM glpi_ocs_link WHERE (glpi_id = '$ID')";
		$result = $DB->query($query);
	}

	/**
	 * Print the computer form
	 *
	 *
	 * Print general computer form
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
		global $LANG,$CFG_GLPI,$DB;

		if (!haveRight("computer","r")) return false;

		$computer_spotted = false;
		$use_cache=true;
		if((empty($ID) && $withtemplate == 1)||$ID==-1) {
			$use_cache=false;
			if($this->getEmpty()) $computer_spotted = true;
		} else {
			if($this->getFromDB($ID)&&haveAccessToEntity($this->fields["FK_entities"])) $computer_spotted = true;
		}
		
		if($computer_spotted) {

			$this->showOnglets($ID, $withtemplate,$_SESSION['glpi_onglet']);

			if(!empty($withtemplate) && $withtemplate == 2) {
				$use_cache=false;
				$template = "newcomp";
				$datestring = $LANG["computers"][14].": ";
				$date = convDateTime($_SESSION["glpi_currenttime"]);
			} elseif(!empty($withtemplate) && $withtemplate == 1) { 
				$use_cache=false;
				$template = "newtemplate";
				$datestring = $LANG["computers"][14].": ";
				$date = convDateTime($_SESSION["glpi_currenttime"]);
			} else {
				$datestring = $LANG["common"][26].": ";
				$date = convDateTime($this->fields["date_mod"]);
				$template = false;
			}

			echo "<form name='form' method='post' action=\"$target\">";
			if(strcmp($template,"newtemplate") === 0) {
				echo "<input type=\"hidden\" name=\"is_template\" value=\"1\">";
			}

			echo "<input type='hidden' name='FK_entities' value='".$this->fields["FK_entities"]."'>";

			echo "<div class='center'>";
			echo "<table class='tab_cadre_fixe' >";


			echo "<tr><th colspan ='2' align='center' >";
			if(!$template) {
				echo $LANG["common"][2]." ".$this->fields["ID"];
			}elseif (strcmp($template,"newcomp") === 0) {
				echo $LANG["computers"][12].": ".$this->fields["tplname"];
				echo "<input type='hidden' name='tplname' value='".$this->fields["tplname"]."'>";
			}elseif (strcmp($template,"newtemplate") === 0) {
				echo $LANG["common"][6]."&nbsp;: ";
				autocompletionTextField("tplname","glpi_computers","tplname",$this->fields["tplname"],20,$this->fields["FK_entities"]);	
			}
			if (isMultiEntitiesMode()){
				echo "&nbsp;(".getDropdownName("glpi_entities",$this->fields["FK_entities"]).")";
			}

			if (!$use_cache||!($CFG_GLPI["cache"]->start($ID."_".$_SESSION["glpilanguage"],"GLPI_".$this->type))) {

				echo "</th><th  colspan ='2' align='center'>".$datestring.$date;
				if (!$template&&!empty($this->fields['tplname']))
					echo "&nbsp;&nbsp;&nbsp;(".$LANG["common"][13].": ".$this->fields['tplname'].")";
				if ($this->fields["ocs_import"])
					echo "&nbsp;&nbsp;&nbsp;(".$LANG["ocsng"][7].")";
	
				echo "</th></tr>";


				echo "<tr class='tab_bg_1'><td>".$LANG["common"][16].($template?"*":"").":		</td>";
	
				echo "<td>";
	
				$objectName = autoName($this->fields["name"], "name", ($template === "newcomp"), COMPUTER_TYPE,$this->fields["FK_entities"]);
				autocompletionTextField("name","glpi_computers","name",$objectName,20,$this->fields["FK_entities"]);

				echo "</td>";
	
				echo "<td>".$LANG["common"][18].":	</td><td>";
				autocompletionTextField("contact","glpi_computers","contact",$this->fields["contact"],20,$this->fields["FK_entities"]);
	
				echo "</td></tr>";
	
				echo "<tr class='tab_bg_1'>";
				echo "<td >".$LANG["common"][17].": 	</td>";
				echo "<td >";
				dropdownValue("glpi_type_computers", "type", $this->fields["type"]);
				echo "</td>";
	
	
				echo "<td>".$LANG["common"][21].":		</td><td>";
				autocompletionTextField("contact_num","glpi_computers","contact_num",$this->fields["contact_num"],20,$this->fields["FK_entities"]);
				echo "</td></tr>";
	
				echo "<tr class='tab_bg_1'>";
				echo "<td >".$LANG["common"][22].": 	</td>";
				echo "<td >";
				dropdownValue("glpi_dropdown_model", "model", $this->fields["model"]);
				echo "</td>";
				
				echo "<td >".$LANG["common"][34].": 	</td>";
				echo "<td >";
				dropdownAllUsers("FK_users", $this->fields["FK_users"],1,$this->fields["FK_entities"]);
				echo "</td></tr>";
	
				echo "<tr class='tab_bg_1'>";
				echo "<td >".$LANG["common"][15].": 	</td>";
				echo "<td >";
				dropdownValue("glpi_dropdown_locations", "location", $this->fields["location"],1,$this->fields["FK_entities"]);
				echo "</td>";
				
				echo "<td>".$LANG["common"][35].":</td><td>";
				dropdownValue("glpi_groups", "FK_groups", $this->fields["FK_groups"],1,$this->fields["FK_entities"]);
				echo "</td></tr>";
	
				echo "<tr class='tab_bg_1'>";
				echo "<td>".$LANG["common"][5].": 	</td><td>";
				dropdownValue("glpi_dropdown_manufacturer","FK_glpi_enterprise",$this->fields["FK_glpi_enterprise"]);
				echo "</td>";
	
				echo "<td >".$LANG["common"][10].": 	</td>";
				echo "<td >";
				dropdownUsersID("tech_num",$this->fields["tech_num"],"interface",1,$this->fields["FK_entities"]);
				echo "</td></tr>";
				
				echo "<tr class='tab_bg_1'>";
				echo "<td>".$LANG["computers"][9].":</td><td>";
				dropdownValue("glpi_dropdown_os", "os", $this->fields["os"]);
				echo "</td>";
				
				echo "<td>".$LANG["setup"][88].":</td><td>";
				dropdownValue("glpi_dropdown_network", "network", $this->fields["network"]);
				echo "</td></tr>";
	
				echo "<tr class='tab_bg_1'>";
				echo "<td>".$LANG["computers"][52].":</td><td>";
				dropdownValue("glpi_dropdown_os_version", "os_version", $this->fields["os_version"]);
				echo "</td>";
	
	
				echo "<td>".$LANG["setup"][89].":</td><td>";
				dropdownValue("glpi_dropdown_domain", "domain", $this->fields["domain"]);
				echo "</td></tr>";
	
	
				echo "<tr class='tab_bg_1'>";
				echo "<td>".$LANG["computers"][53].":</td><td>";
				dropdownValue("glpi_dropdown_os_sp", "os_sp", $this->fields["os_sp"]);
				echo "</td>";
	
				echo "<td>".$LANG["common"][19].":	</td><td>";
				autocompletionTextField("serial","glpi_computers","serial",$this->fields["serial"],20,$this->fields["FK_entities"]);
				echo "</td></tr>";
	
				echo "<tr class='tab_bg_1'>";
				echo "<td>".$LANG["computers"][10]."</td><td>";
				autocompletionTextField("os_license_number","glpi_computers","os_license_number",$this->fields["os_license_number"],35,$this->fields["FK_entities"]);
				echo"</td>";
	
				echo "<td>".$LANG["common"][20].($template?"*":"").":	</td><td>";
				$objectName = autoName($this->fields["otherserial"], "otherserial", ($template === "newcomp"), COMPUTER_TYPE,$this->fields["FK_entities"]);
				autocompletionTextField("otherserial","glpi_computers","otherserial",$objectName,20,$this->fields["FK_entities"]);

				echo "</td></tr>";
	
				echo "<tr class='tab_bg_1'>";
				echo "<td>".$LANG["computers"][11]."</td><td>";
				autocompletionTextField("os_license_id","glpi_computers","os_license_id",$this->fields["os_license_id"],35,$this->fields["FK_entities"]);
				echo"</td>";
				
				echo "<td>".$LANG["state"][0].":</td><td>";
				dropdownValue("glpi_dropdown_state", "state",$this->fields["state"]);
				echo "</td>";

				// Get OCS Datas :
				$dataocs=array();
				if (!empty($ID)&&$this->fields["ocs_import"]&&haveRight("view_ocsng","r")){
					$query="SELECT * 
						FROM glpi_ocs_link 
						WHERE glpi_id='$ID'";
	
					$result=$DB->query($query);
					if ($DB->numrows($result)==1){
						$dataocs=$DB->fetch_array($result);
					}

				}

				echo "<tr class='tab_bg_1'>";
				if (!empty($ID)&&$this->fields["ocs_import"]&&haveRight("view_ocsng","r")&&haveRight("sync_ocsng","w")&&count($dataocs)){
					echo "<td >".$LANG["ocsng"][6]." ".$LANG["Menu"][33].":</td>";
					echo "<td >";
					dropdownYesNo("_auto_update_ocs",$dataocs["auto_update"]);
					echo "</td>";
				} else	{
					echo "<td colspan=2></td>";
				}
				echo "<td>".$LANG["computers"][51].":</td><td>";
				dropdownValue("glpi_dropdown_auto_update", "auto_update", $this->fields["auto_update"]);
				echo "</td>";

				echo "</tr>";
				
				echo "<tr class='tab_bg_1'>";
				
				if (!empty($ID)&&$this->fields["ocs_import"]&&haveRight("view_ocsng","r")&&count($dataocs)){
					echo "<td colspan='2' align='center'>";
					echo $LANG["ocsng"][14].": ".convDateTime($dataocs["last_ocs_update"]);
					echo "<br>";
					echo $LANG["ocsng"][13].": ".convDateTime($dataocs["last_update"]);
					echo "<br>";
					if (haveRight("ocsng","r")){
						echo $LANG["common"][52]." <a href='".$CFG_GLPI["root_doc"]."/front/ocsng.form.php?ID=".getOCSServerByMachineID($ID)."'>".getOCSServerNameByID($ID)."</a>";
						$query = "SELECT ocs_agent_version FROM glpi_ocs_link WHERE (glpi_id = '$ID')";
						$result_agent_version = $DB->query($query);
						$data_version = $DB->fetch_array($result_agent_version);
						if ($data_version["ocs_agent_version"] != NULL)
							echo " , ".$LANG["ocsng"][49]." : ".$data_version["ocs_agent_version"];
					} else {
						echo $LANG["common"][52]." ".getOCSServerNameByID($ID);	
						echo "</td>";
					}
					
				} else	{
					echo "<td colspan=2></td>";	
				}
				echo "<td valign='middle'>".$LANG["common"][25].":</td><td valign='middle'><textarea  cols='50' rows='3' name='comments' >".$this->fields["comments"]."</textarea></td>";
				echo "</tr>";
				if ($use_cache){
					$CFG_GLPI["cache"]->end();
				}
			}
			

			if (haveRight("computer","w")) {
				echo "<tr>\n";
				if ($template) {
					if (empty($ID)||$withtemplate==2){
						echo "<td class='tab_bg_2' align='center' colspan='4'>\n";
						echo "<input type='hidden' name='ID' value=$ID>";
						echo "<input type='submit' name='add' value=\"".$LANG["buttons"][8]."\" class='submit'>";
						echo "</td>\n";
					} else {
						echo "<td class='tab_bg_2' align='center' colspan='4'>\n";
						echo "<input type='hidden' name='ID' value=$ID>";
						echo "<input type='submit' name='update' value=\"".$LANG["buttons"][7]."\" class='submit'>";
						echo "</td>\n";
					}
				} else {
					echo "<td class='tab_bg_2' colspan='2' align='center' valign='top'>\n";
					echo "<input type='submit' name='update' value=\"".$LANG["buttons"][7]."\" class='submit'>";
					echo "</td>\n";
					echo "<td class='tab_bg_2' colspan='2'  align='center'>\n";
					echo "<input type='hidden' name='ID' value=$ID>";
					echo "<div class='center'>";
					if (!$this->fields["deleted"]){
						echo "<input type='submit' name='delete' value=\"".$LANG["buttons"][6]."\" class='submit'>";
					 }else {
						echo "<input type='submit' name='restore' value=\"".$LANG["buttons"][21]."\" class='submit'>";

						echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' name='purge' value=\"".$LANG["buttons"][22]."\" class='submit'>";
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
			echo "<div class='center'><strong>".$LANG["common"][54]."</strong></div>";
			return false;
		}
	}

}


?>
