<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2007 by the INDEPNET Development Team.

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

// Tracking Classes

class Job extends CommonDBTM{

	var $hardwaredatas	= array();
	var $computerfound	= 0;

	function Job(){
		$this->table="glpi_tracking";
		$this->type=TRACKING_TYPE;
	}

	function getFromDBwithData ($ID,$purecontent) {

		global $DB,$LANG;

		if ($this->getFromDB($ID)){

			if (!$purecontent) {
				$this->fields["contents"] = nl2br(preg_replace("/\r\n\r\n/","\r\n",$this->fields["contents"]));
			}

			$this->getHardwareData();
			return true;
		} else {
			return false;
		}
	}

	function getHardwareData(){
		$m= new CommonItem;
		if ($m->getfromDB($this->fields["device_type"],$this->fields["computer"])){
			$this->hardwaredatas=$m;
			$this->computerfound=0;
		} else {
			$this->hardwaredatas=$m;
			$this->computerfound=1;
		}
	}
	function cleanDBonPurge($ID) {
		global $DB;

		$query="SELECT ID FROM glpi_followups WHERE tracking = '$ID'";
		$result=$DB->query($query);
		if ($DB->numrows($result)>0)
			while ($data=$DB->fetch_array($result)){
				$querydel="DELETE FROM glpi_tracking_planning WHERE id_followup = '".$data['ID']."'";
				$DB->query($querydel);				
			}
		$query1="DELETE FROM glpi_followups WHERE tracking = '$ID'";
		$DB->query($query1);

	}

	function prepareInputForUpdate($input) {
		global $LANG,$CFG_GLPI;


		// Security checks
		if (!haveRight("assign_ticket","1")){
			if (isset($input["assign"])){
				// Can not steal or can steal and not assign to me
				if (!haveRight("steal_ticket","1")||$input["assign"]!=$_SESSION["glpiID"]){
					unset($input["assign"]);
				} 
			}
			if (isset($input["assign_ent"])){
				unset($input["assign_ent"]);
			}
			if (isset($input["assign_group"])){
				unset($input["assign_group"]);
			}

		}

		if (!haveRight("update_ticket","1")){
			// Manage assign and steal right
			if (isset($input["assign"])){
				$ret["assign"]=$input["assign"];
			}
			if (isset($input["assign_ent"])){
				$ret["assign_ent"]=$input["assign_ent"];
			}
			if (isset($input["assign_group"])){
				$ret["assign_group"]=$input["assign_group"];
			}
			// Can only update contents if no followups already added
			$ret["ID"]=$input["ID"];
			if (isset($input["contents"])){
				$ret["contents"]=$input["contents"];
			}
			if (isset($input["name"])){
				$ret["name"]=$input["name"];
			}
			$input=$ret;
		}

		if (isset($input["item"])&& $input["item"]!=0&&isset($input["type"])&& $input["type"]!=0){
			$input["computer"]=$input["item"];
			$input["device_type"]=$input["type"];
		} 
		
		if (isset($input["computer"])&&$input["computer"]>0&&isset($input["device_type"])&&$input["device_type"]>0){
			if (isset($this->fields['FK_group'])&&$this->fields['FK_group']){
				$ci=new CommonItem;
				$ci->getFromDB($input["device_type"],$input["computer"]);
				if ($tmp=$ci->getField('FK_groups')){
					$input["FK_group"] = $tmp;
				}
			}
		} else {
			unset($input["computer"]);
			unset($input["device_type"]);
		}	
		// add Document if exists
		if (isset($_FILES['filename'])&&count($_FILES['filename'])>0&&$_FILES['filename']["size"]>0){
			$input2=array();
			$input2["name"]=$LANG["tracking"][24]." ".$input["ID"];
			$input2["FK_tracking"]=$input["ID"];
			$input2["rubrique"]=$CFG_GLPI["default_rubdoc_tracking"];
			$this->getFromDB($input["ID"]);
			$input2["FK_entities"]=$this->fields["FK_entities"];
			$input2["_only_if_upload_succeed"]=1;
			$doc=new Document();
			if ($docID=$doc->add($input2)){
				addDeviceDocument($docID,TRACKING_TYPE,$input["ID"]);
			}
		}

		if (isset($input["document"])&&$input["document"]>0){
			addDeviceDocument($input["document"],TRACKING_TYPE,$input["ID"]);
			unset($input["document"]);
		}

		// Old values for add followup in change
		if ($CFG_GLPI["followup_on_update_ticket"]){
			$this->getFromDB($input["ID"]);
			$input["_old_assign_name"]=getAssignName($this->fields["assign"],USER_TYPE);
			$input["_old_assign"]=$this->fields["assign"];
			$input["_old_assign_ent_name"]=getAssignName($this->fields["assign_ent"],ENTERPRISE_TYPE);
			$input["_old_assign_group_name"]=getAssignName($this->fields["assign_group"],GROUP_TYPE);
			$input["_old_category"]=$this->fields["category"];
			$input["_old_item"]=$this->fields["computer"];
			$input["_old_item_type"]=$this->fields["device_type"];
			$input["_old_author"]=$this->fields["author"];
			$input["_old_group"]=$this->fields["FK_group"];
			$input["_old_priority"]=$this->fields["priority"];
			$input["_old_status"]=$this->fields["status"];
			$input["_old_request_type"]=$this->fields["request_type"];
			$input["_old_cost_time"]=$this->fields["cost_time"];
			$input["_old_cost_fixed"]=$this->fields["cost_fixed"];
			$input["_old_cost_material"]=$this->fields["cost_material"];
		}

		return $input;
	}

	function pre_updateInDB($input,$updates) {

		if (((in_array("assign",$updates)&&$input["assign"]>0)||(in_array("assign_ent",$updates)&&$input["assign_ent"]>0)||(in_array("assign_group",$updates)&&$input["assign_group"]>0))&&$this->fields["status"]=="new"){
			$updates[]="status";
			$this->fields["status"]="assign";
		}
		if (isset($input["status"])){
			if (isset($input["assign_ent"])&&$input["assign_ent"]==0&&isset($input["assign_group"])&&$input["assign_group"]==0&&
			isset($input["assign"])&&$input["assign"]==0&&$input["status"]=="assign"){
				$updates[]="status";
				$this->fields["status"]="new";
			}

			if (in_array("status",$updates)&&ereg("old_",$input["status"])){
				$updates[]="closedate";
				$this->fields["closedate"]=$_SESSION["glpi_currenttime"];
			}
		}

		if (in_array("author",$updates)){
			$user=new User;
			$user->getfromDB($input["author"]);
			if (!empty($user->fields["email"])){
				$updates[]="uemail";
				$this->fields["uemail"]=$user->fields["email"];
			}
		}

		return array($input,$updates);
	}

	function post_updateItem($input,$updates,$history=1) {
		global $CFG_GLPI,$LANG;

		if (count($updates)){
			// New values for add followup in change
			$change_followup_content="";
			$global_mail_change_count=0;
	
			// Update Ticket Tco
			if (in_array("realtime",$updates)||in_array("cost_time",$updates)|| in_array("cost_fixed",$updates)||in_array("cost_material",$updates)){
				$ci=new CommonItem;
				if ($ci->getfromDB($this->fields["device_type"],$this->fields["computer"])){
					$newinput=array();
					$newinput['ID']=$this->fields["computer"];
					$newinput['ticket_tco']=computeTicketTco($this->fields["device_type"],$this->fields["computer"]);
					$ci->obj->update($newinput);
				}
			}
	
	
	
			if ($CFG_GLPI["followup_on_update_ticket"]){
	
	
				if (in_array("name",$updates)){
					$change_followup_content.=$LANG["mailing"][45]."\n";
					$global_mail_change_count++;
				}
				if (in_array("contents",$updates)){
					$change_followup_content.=$LANG["mailing"][46]."\n";
					$global_mail_change_count++;
				}
	
				if (in_array("status",$updates)){
					$new_status=$this->fields["status"];
					$change_followup_content.=$LANG["mailing"][27].": ".getStatusName($input["_old_status"])." -> ".getStatusName($new_status)."\n";
	
					if (ereg("old_",$new_status))
						$newinput["add_close"]="add_close";
					if (in_array("closedate",$updates))	
						$global_mail_change_count++; // Manage closedate
	
					$global_mail_change_count++;
				}
	
				if (in_array("author",$updates)){
					$author=new User;
					$author->getFromDB($input["_old_author"]);
					$old_author_name=$author->getName();
					$author->getFromDB($this->fields["author"]);
					$new_author_name=$author->getName();
					$change_followup_content.=$LANG["mailing"][18].": $old_author_name -> ".$new_author_name."\n";
	
					$global_mail_change_count++;
				}
	
				if (in_array("FK_group",$updates)){
					$new_group=$this->fields["FK_group"];
					$old_group_name=ereg_replace("&nbsp;",$LANG["mailing"][109],getDropdownName("glpi_groups",$input["_old_group"]));
					$new_group_name=ereg_replace("&nbsp;",$LANG["mailing"][109],getDropdownName("glpi_groups",$new_group));
					$change_followup_content.=$LANG["mailing"][20].": ".$old_group_name." -> ".$new_group_name."\n";
					$global_mail_change_count++;
				}
	
				if (in_array("priority",$updates)){
					$new_priority=$this->fields["priority"];
					$change_followup_content.=$LANG["mailing"][15].": ".getPriorityName($input["_old_priority"])." -> ".getPriorityName($new_priority)."\n";
					$global_mail_change_count++;		
				}
				if (in_array("category",$updates)){
					$new_category=$this->fields["category"];
					$old_category_name=ereg_replace("&nbsp;",$LANG["mailing"][100],getDropdownName("glpi_dropdown_tracking_category",$input["_old_category"]));
					$new_category_name=ereg_replace("&nbsp;",$LANG["mailing"][100],getDropdownName("glpi_dropdown_tracking_category",$new_category));
					$change_followup_content.=$LANG["mailing"][14].": ".$old_category_name." -> ".$new_category_name."\n";
					$global_mail_change_count++;
				}
				if (in_array("request_type",$updates)){
					$new_request_type=$this->fields["request_type"];
					$old_request_type_name=getRequestTypeName($input["_old_request_type"]);
					$new_request_type_name=getRequestTypeName($new_request_type);
					$change_followup_content.=$LANG["mailing"][21].": ".$old_request_type_name." -> ".$new_request_type_name."\n";
					$global_mail_change_count++;
				}
				if (in_array("computer",$updates)||in_array("device_type",$updates)){	
					$ci=new CommonItem;
					$ci->getfromDB($input["_old_item_type"],$input["_old_item"]);
					$old_item_name=$ci->getName();
					if ($old_item_name=="N/A"||empty($old_item_name))
						$old_item_name=$LANG["mailing"][107];
					$ci->getfromDB($this->fields["device_type"],$this->fields["computer"]);
					$new_item_name=$ci->getName();
					if ($new_item_name=="N/A"||empty($new_item_name))
						$new_item_name=$LANG["mailing"][107];
	
					$change_followup_content.=$LANG["mailing"][17].": $old_item_name -> ".$new_item_name."\n";
					if (in_array("computer",$updates)) $global_mail_change_count++;
					if (in_array("device_type",$updates)) $global_mail_change_count++;
				}
	
	
				if (in_array("assign",$updates)){
					$new_assign_name=getAssignName($this->fields["assign"],USER_TYPE);
					if ($input["_old_assign"]==0){
						$input["_old_assign_name"]=$LANG["mailing"][105];
					}
					$change_followup_content.=$LANG["mailing"][12].": ".$input["_old_assign_name"]." -> ".$new_assign_name."\n";
					$global_mail_change_count++;
					
				} else {
					unset($input["_old_assign"]);
				}
	
				if (in_array("assign_ent",$updates)){
					$new_assign_ent_name=getAssignName($this->fields["assign_ent"],ENTERPRISE_TYPE);
					$change_followup_content.=$LANG["mailing"][12].": ".$input["_old_assign_ent_name"]." -> ".$new_assign_ent_name."\n";
					$global_mail_change_count++;
				}
				if (in_array("assign_group",$updates)){
					$new_assign_group_name=getAssignName($this->fields["assign_group"],GROUP_TYPE);
					$change_followup_content.=$LANG["mailing"][12].": ".$input["_old_assign_group_name"]." -> ".$new_assign_group_name."\n";
					$global_mail_change_count++;
				}
				if (in_array("cost_time",$updates)){
					$change_followup_content.=$LANG["mailing"][42].": ".number_format($input["_old_cost_time"],$CFG_GLPI["decimal_number"])." -> ".number_format($this->fields["cost_time"],$CFG_GLPI["decimal_number"])."\n";
					$global_mail_change_count++;
				}
				if (in_array("cost_fixed",$updates)){
					$change_followup_content.=$LANG["mailing"][43].": ".number_format($input["_old_cost_fixed"],$CFG_GLPI["decimal_number"])." -> ".number_format($this->fields["cost_fixed"],$CFG_GLPI["decimal_number"])."\n";
					$global_mail_change_count++;
				}
				if (in_array("cost_material",$updates)){
					$change_followup_content.=$LANG["mailing"][44].": ".number_format($input["_old_cost_material"],$CFG_GLPI["decimal_number"])." -> ".number_format($this->fields["cost_material"],$CFG_GLPI["decimal_number"])."\n";
					$global_mail_change_count++;
				}
	
				if (in_array("emailupdates",$updates)){
					if ($this->fields["emailupdates"]){
						$change_followup_content.=$LANG["mailing"][101]."\n";
					} else {
						$change_followup_content.=$LANG["mailing"][102]."\n";
					}
					$global_mail_change_count++;
				}
			}
	
			$mail_send=false;
	
			if (!empty($change_followup_content)){ // Add followup if not empty
				$newinput=array();
				$newinput["contents"]=addslashes($change_followup_content);
				$newinput["author"]=$_SESSION['glpiID'];
				$newinput["private"]=0;
				$newinput["hour"]=$newinput["minute"]=0;
				$newinput["tracking"]=$this->fields["ID"];
				$newinput["type"]="update";
				$newinput["_changes_to_log"]=true;
				// pass _old_assign if assig changed
				if (isset($input["_old_assign"])){
					$newinput["_old_assign"]=$input["_old_assign"];
				} 
				if (in_array("status",$updates)&&ereg("old_",$input["status"])){
					$newinput["type"]="finish";
				}
				$fup=new Followup();
				$fup->add($newinput);
				$mail_send=true;
			}
	
			
			// Clean content to mail
			$this->fields["contents"]=stripslashes($this->fields["contents"]);
	
			if (!$mail_send&&count($updates)>$global_mail_change_count&&$CFG_GLPI["mailing"]){
				$user=new User;
				$user->getfromDBbyName($_SESSION["glpiname"]);
				$mailtype="update";
				if (in_array("status",$updates)&&ereg("old_",$input["status"])){
					$mailtype="finish";
				} 
				if (isset($input["_old_assign"])){
					$this->fields["_old_assign"]=$input["_old_assign"];
				} 
				$mail = new Mailing($mailtype,$this,$user);
				$mail->send();
			}
		}
	}


	function prepareInputForAdd($input) {
		global $CFG_GLPI;

		// Manage helpdesk.html submission type
		unset($input["type"]);


		if (isset($_SESSION["glpiID"])) $input["recipient"]=$_SESSION["glpiID"];
		else if ($input["author"]) $input["recipient"]=$input["author"];

		if (!isset($input["request_type"])) $input["request_type"]=1;
		if (!isset($input["status"])) $input["status"]="new";
		if (!isset($input["assign"])) $input["assign"]=0;

		if (!isset($input["author"])){
			if (isset($_SESSION["glpiID"])&&$_SESSION["glpiID"]>0)
				$input["author"]=$_SESSION["glpiID"];
			else $input["author"]=1; // Helpdesk injector
		}

		if (!isset($input["date"])){
			$input["date"] = $_SESSION["glpi_currenttime"];
		}

		if (isset($input["computer"])&&$input["computer"]==0){
			$input["device_type"]=0;	
		}

		if ($input["device_type"]==0){
			$input["computer"]=0;
		}


		// Auto group define
		if (isset($input["computer"])&&$input["computer"]&&$input["device_type"]){
			$ci=new CommonItem;
			$ci->getFromDB($input["device_type"],$input["computer"]);
			if ($tmp=$ci->getField('FK_groups')){
				$input["FK_group"] = $tmp;
			}
		}

		if ($CFG_GLPI["auto_assign"]&&$input["assign"]==0&&isset($input["computer"])&&$input["computer"]>0&&isset($input["device_type"])&&$input["device_type"]>0){
			$ci=new CommonItem;
			$ci->getFromDB($input["device_type"],$input["computer"]);
			if ($tmp=$ci->getField('tech_num')){
				$input["assign"] = $tmp;
				if ($input["assign"]>0){
					$input["status"] = "assign";
				}
			}
		}

		// Process Business Rules
		$rules=new TrackingBusinessRuleCollection();
		$input=$rules->processAllRules($input,$input);

		if (isset($input["emailupdates"])&&$input["emailupdates"]&&empty($input["uemail"])){
			$user=new User();
			$user->getFromDB($input["author"]);
			$input["uemail"]=$user->fields["email"];
		}

		if ($input["assign"]>0&&$input["status"]=="new"){
			$input["status"] = "assign";
		}

		if (strstr($input["status"],"old_")){
			$input["closedate"] = $input["date"];
		}






		if (isset($input["hour"])&&isset($input["minute"])){
			$input["realtime"]=$input["hour"]+$input["minute"]/60;
			$input["_hour"]=$input["hour"];
			$input["_minute"]=$input["minute"];
			unset($input["hour"]);
			unset($input["minute"]);
		}

		// Add and close for central helpdesk
		if (isset($input["add_close"])){
			$input["status"]="old_done";
			unset($input["add_close"]);
			if (isset($input["date"])){
				$input["closedate"]=$input["date"];
			} else {
				$input["closedate"]=$_SESSION["glpi_currenttime"];
			}
		}

		if (empty($input["name"])) {
			$input["name"]=preg_replace('/\r\n/',' ',$input['contents']);
			$input["name"]=preg_replace('/\n/',' ',$input['name']);
			$input["name"]=substr($input['name'],0,70);
		}

		return $input;
	}

	function post_addItem($newID,$input) {
		global $LANG,$CFG_GLPI;

		// add Document if exists
		if (isset($_FILES['filename'])&&count($_FILES['filename'])>0&&$_FILES['filename']["size"]>0){
			$input2=array();
			$input2["name"]=$LANG["tracking"][24]." $newID";
			$input2["FK_tracking"]=$newID;
			$input2["FK_entities"]=$input["FK_entities"];
			$input2["rubrique"]=$CFG_GLPI["default_rubdoc_tracking"];
			$input2["_only_if_upload_succeed"]=1;
			$doc=new Document();
			if ($docID=$doc->add($input2))
				addDeviceDocument($docID,TRACKING_TYPE,$newID);
		}

		// Log this event
		logEvent($newID,"tracking",4,"tracking",getUserName($input["author"])." ".$LANG["log"][20]);

		$already_mail=false;
		if ((isset($input["_followup"])&&strlen($input["_followup"]))||(isset($input["_hour"])&&isset($input["_minute"])&&isset($input["realtime"])&&$input["realtime"]>0)){

			$fup=new Followup();
			$type="new";
			if (isset($this->fields["status"])&&ereg("old_",$this->fields["status"])) $type="finish";
			$toadd=array("type"=>$type,"tracking"=>$newID);
			if (isset($input["_hour"])) $toadd["hour"]=$input["_hour"];
			if (isset($input["_minute"])) $toadd["minute"]=$input["_minute"];
			if (isset($input["_followup"])&&strlen($input["_followup"])) $toadd["contents"]=$input["_followup"];

			$fup->add($toadd);
			$already_mail=true;
		}

		// Processing Email
		if ($CFG_GLPI["mailing"]&&!$already_mail)
		{
			$user=new User();
			$user->getFromDB($input["author"]);

			$this->fields=stripslashes_deep($this->fields);
			$type="new";
			if (isset($this->fields["status"])&&ereg("old_",$this->fields["status"])) $type="finish";
			$mail = new Mailing($type,$this,$user);
			$mail->send();
		}

	}

	// SPECIFIC FUNCTIONS

	function numberOfFollowups($with_private=1){
		global $DB;
		$RESTRICT="";
		if ($with_private!=1) $RESTRICT = " AND private='0'";
		// Set number of followups
		$query = "SELECT count(*) FROM glpi_followups WHERE tracking = ".$this->fields["ID"]." $RESTRICT";
		$result = $DB->query($query);
		return $DB->result($result,0,0);

	}

	function updateRealTime() {
		// update Status of Job

		global $DB;
		$query = "SELECT SUM(realtime) FROM glpi_followups WHERE tracking = '".$this->fields["ID"]."'";
		if ($result = $DB->query($query)) {
			$sum=$DB->result($result,0,0);
			if (is_null($sum)) $sum=0;
			$query2="UPDATE glpi_tracking SET realtime='".$sum."' WHERE ID='".$this->fields["ID"]."'";
			$DB->query($query2);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Get text describing Followups
	 * 
	* @param $format text or html
	* @param $sendprivate true if both public and private followups have to be printed in the email
	 */
	function textFollowups($format="text", $sendprivate=false) {
		// get the last followup for this job and give its contents as
		global $DB,$LANG;

		if (isset($this->fields["ID"])){
			$query = "SELECT * FROM glpi_followups WHERE tracking = '".$this->fields["ID"]."' ".($sendprivate?"":" AND private = '0' ")." ORDER by date DESC";
			$result=$DB->query($query);
			$nbfollow=$DB->numrows($result);
			if($format=="html"){
				$message = "<div class='description'><strong>".$LANG["mailing"][4]." : $nbfollow<br></strong></div><br>";

				if ($nbfollow>0){
					$fup=new Followup();
					while ($data=$DB->fetch_array($result)){
						$fup->getfromDB($data['ID']);
						$message .= "<strong>[ ".convDateTime($fup->fields["date"])." ] ".($fup->fields["private"]?"<i>".$LANG["job"][30]."</i>":"")."</strong><br>";
						$message .= "<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$LANG["job"][4].":</span> ".$fup->getAuthorName()."<br>";
						$message .= "<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$LANG["mailing"][3]."</span><br>".nl2br($fup->fields["contents"])."<br>";
						if ($fup->fields["realtime"]>0)
							$message .= "<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$LANG["mailing"][104].":</span> ".getRealtime($fup->fields["realtime"])."<br>";

						$message.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$LANG["mailing"][25]."</span> ";
						$query2="SELECT * from glpi_tracking_planning WHERE id_followup='".$data['ID']."'";
						$result2=$DB->query($query2);
						if ($DB->numrows($result2)==0)
							$message.=$LANG["job"][32]."<br>";
						else {
							$data2=$DB->fetch_array($result2);
							$message.=convDateTime($data2["begin"])." -> ".convDateTime($data2["end"])."<br>";
						}

						$message.=$LANG["mailing"][0]."<br>";	
					}	
				}
			}else{ // text format
				$message = $LANG["mailing"][1]."\n".$LANG["mailing"][4]." : $nbfollow\n".$LANG["mailing"][1]."\n";

				if ($nbfollow>0){
					$fup=new Followup();
					while ($data=$DB->fetch_array($result)){
						$fup->getfromDB($data['ID']);
						$message .= "[ ".convDateTime($fup->fields["date"])." ]".($fup->fields["private"]?"\t".$LANG["job"][30]:"")."\n";
						$message .= $LANG["job"][4].": ".$fup->getAuthorName()."\n";
						$message .= $LANG["mailing"][3]."\n".$fup->fields["contents"]."\n";
						if ($fup->fields["realtime"]>0)
							$message .= $LANG["mailing"][104].": ".getRealtime($fup->fields["realtime"])."\n";

						$message.=$LANG["mailing"][25]." ";
						$query2="SELECT * from glpi_tracking_planning WHERE id_followup='".$data['ID']."'";
						$result2=$DB->query($query2);
						if ($DB->numrows($result2)==0)
							$message.=$LANG["job"][32]."\n";
						else {
							$data2=$DB->fetch_array($result2);
							$message.=convDateTime($data2["begin"])." -> ".convDateTime($data2["end"])."\n";
						}

						$message.=$LANG["mailing"][0]."\n";	
					}	
				}


			}
			return $message;
		} else return "";
	}

	function textDescription($format="text"){
		global $DB,$LANG;


		$name=$LANG["help"][30];
		$contact='';
		$tech='';
		$name=$this->hardwaredatas->getType()." ".$this->hardwaredatas->getName();
		if ($this->hardwaredatas->obj!=NULL){
			if (isset($this->hardwaredatas->obj->fields["serial"])&&!empty($this->hardwaredatas->obj->fields["serial"])){
				$name.=" - ".$LANG["common"][19].": ".$this->hardwaredatas->obj->fields["serial"];
			}
			if (isset($this->hardwaredatas->obj->fields["model"])&&$this->hardwaredatas->obj->fields["model"]>0){
				$name.=" - ".$LANG["common"][22].": ".getDropdownName("glpi_dropdown_model",$this->hardwaredatas->obj->fields["model"]);
			}
			if (isset($this->hardwaredatas->obj->fields["tech_num"])&&$this->hardwaredatas->obj->fields["tech_num"]>0){
					$tech=getUserName($this->hardwaredatas->obj->fields["tech_num"]);
			}
			if (isset($this->hardwaredatas->obj->fields["contact"])){
				$contact=$this->hardwaredatas->obj->fields["contact"];
			}
			if (isset($this->hardwaredatas->obj->fields["FK_users"])){
				$contact=getUserName($this->hardwaredatas->obj->fields["FK_users"]);
			}
			if (isset($this->hardwaredatas->obj->fields["FK_groups"])){
				if (!empty($contact)) $contact.=" / ";
					$contact.=getDropdownName("glpi_groups",$this->hardwaredatas->obj->fields["FK_groups"]);
			}
		}

		if($format=="html"){
			$message= "<html><head> <style type=\"text/css\">";
			$message.=".description{ color: inherit; background: #ebebeb; border-style: solid; border-color: #8d8d8d; border-width: 0px 1px 1px 0px; }";
			$message.=" </style></head><body>";

			$message.="<div class='description'><strong>".$LANG["mailing"][5]."</strong></div><br>";
			$message.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$LANG["common"][57].":</span> ".$this->fields["name"]."<br>";
			$author=$this->getAuthorName();
			if (empty($author)) $author=$LANG["mailing"][108];
			$message.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$LANG["job"][4].":</span> ".$author."<br>";
			$message.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>". $LANG["search"][8].":</span> ".convDateTime($this->fields["date"])."<br>";
			$message.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>". $LANG["job"][44].":</span> ".getRequestTypeName($this->fields["request_type"])."<br>";
			$message.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>". $LANG["mailing"][7]."</span> ".$name."<br>";
			if (!empty($tech))
				$message.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>". $LANG["common"][10].":</span> ".$tech."<br>";
			$message.= "<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$LANG["joblist"][0].":</span> ".getStatusName($this->fields["status"])."<br>";
			$assign=getAssignName($this->fields["assign"],USER_TYPE);
			$assign_group=getAssignName($this->fields["assign_group"],GROUP_TYPE);
			if ($assign=="[Nobody]"){
				if (!empty($assign_group)){
					$assign=$assign_group;
				} else {
					$assign=$LANG["mailing"][105];
				}
			} else {
				if (!empty($assign_group)){
					$assign.=" / ".$assign_group;
				}
			}
			$message.= "<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$LANG["mailing"][8]."</span> ".$assign."<br>";
			$message.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$LANG["joblist"][2].":</span> ".getPriorityName($this->fields["priority"])."<br>";
			if ($this->fields["device_type"]!=SOFTWARE_TYPE&&!empty($contact))
				$message.= "<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$LANG["mailing"][28]."</span> ".$contact."<br>";
			if ($this->fields["emailupdates"]){
				$message.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$LANG["mailing"][103]."</span> ".$LANG["choice"][1]."<br>";
			} else {
				$message.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$LANG["mailing"][103]."</span> ".$LANG["choice"][0]."<br>";
			}

			$message.= "<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$LANG["common"][36].":</span> ";
			if (isset($this->fields["category"])&&$this->fields["category"]){
				$message.= getDropdownName("glpi_dropdown_tracking_category",$this->fields["category"]);
			} else $message.=$LANG["mailing"][100];
			$message.= "<br>";

			$message.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>". $LANG["mailing"][3]."</span><br>".nl2br($this->fields["contents"])."<br><br>";	

		}else{ //text format
			$message = $LANG["mailing"][1]."\n*".$LANG["mailing"][5]."*\n".$LANG["mailing"][1]."\n";
			$message.= $LANG["common"][57].": ".$this->fields["name"]."\n";
			$author=$this->getAuthorName();
			if (empty($author)) $author=$LANG["mailing"][108];
			$message.= $LANG["job"][4].": ".$author."\n";
			$message.= $LANG["search"][8].": ".convDateTime($this->fields["date"])."\n";
			$message.= $LANG["job"][44].": ".getRequestTypeName($this->fields["request_type"])."\n";
			$message.= $LANG["mailing"][7]." ".$name."\n";
			if (!empty($tech))
				$message.= $LANG["common"][10].": ".$tech."\n";
			$message.= $LANG["joblist"][0].": ".getStatusName($this->fields["status"])."\n";
			$assign=getAssignName($this->fields["assign"],USER_TYPE);
			$assign_group=getAssignName($this->fields["assign_group"],GROUP_TYPE);
			if ($assign=="[Nobody]"){
                                if (!empty($assign_group)){
                                        $assign=$assign_group;
                                } else {
                                        $assign=$LANG["mailing"][105];
                                }
                        } else {
				if (!empty($assign_group)){
	                                $assign.=" / ".$assign_group;
				}
                        }

			$message.= $LANG["mailing"][8]." ".$assign."\n";
			$message.= $LANG["joblist"][2].": ".getPriorityName($this->fields["priority"])."\n";
			if ($this->fields["device_type"]!=SOFTWARE_TYPE&&!empty($contact))
				$message.= $LANG["mailing"][28]." ".$contact."\n";
			if ($this->fields["emailupdates"]){
				$message.=$LANG["mailing"][103]." ".$LANG["choice"][1]."\n";
			} else {
				$message.=$LANG["mailing"][103]." ".$LANG["choice"][0]."\n";
			}

			$message.= $LANG["common"][36].": ";
			if (isset($this->fields["category"])&&$this->fields["category"]){
				$message.= getDropdownName("glpi_dropdown_tracking_category",$this->fields["category"]);
			} else $message.=$LANG["mailing"][100];
			$message.= "\n";

			$message.= $LANG["mailing"][3]."\n".$this->fields["contents"]."\n";	
			$message.="\n\n";

		}

		return $message;
	}


	function getAuthorName($link=0){
		return getUserName($this->fields["author"],$link);
	}

}


class Followup  extends CommonDBTM {

	function Followup () {
		$this->table="glpi_followups";
		$this->type=-1;
	}

	function cleanDBonPurge($ID) {
		global $DB;
		$querydel="DELETE FROM glpi_tracking_planning WHERE id_followup = '$ID'";
		$DB->query($querydel);				
	}

	function post_deleteFromDB($ID){
		$job=new Job();
		$job->getFromDB($this->fields['tracking']);
		$job->updateRealtime();		
	}


	function prepareInputForUpdate($input) {
		$input["realtime"]=$input["hour"]+$input["minute"]/60;
		$input["author"]=$_SESSION["glpiID"];
		if (isset($input["plan"])){
			$input["_plan"]=$input["plan"];
			unset($input["plan"]);
		}
		return $input;
	}

	function post_updateItem($input,$updates,$history=1) {
		global $CFG_GLPI;
		
		$mailsend=false;
		if (count($updates)){
			$job=new Job;
			$job->getFromDBwithData($input["tracking"],1);
	
			if ($CFG_GLPI["mailing"]&&
			 (in_array("contents",$updates)||isset($input['_need_send_mail']))){
				$user=new User;
				$user->getfromDBbyName($_SESSION["glpiname"]);
				$mail = new Mailing("followup",$job,$user,(isset($input["private"]) && $input["private"]));
				$mail->send();
				$mailsend=true;
			}
	
			if (in_array("realtime",$updates)) {
				$job->updateRealTime();
			}
		}
		if (isset($input["_plan"])){
			$pt=new PlanningTracking();
			// Update case
			if (isset($input["plan"]["ID"])){
				$input["plan"]['id_followup']=$input["ID"];
				$input["plan"]['id_tracking']=$input['tracking'];
				$input["plan"]['_nomail']=$mailsend;

				if (!$pt->update($input["plan"])){
					return false;
				}
				unset($input["plan"]);
			// Add case
			} else {
				$input["plan"]['id_followup']=$input["ID"];
				$input["plan"]['id_tracking']=$input['tracking'];
				$input["plan"]['_nomail']=1;

				if (!$pt->add($input["plan"])){
					return false;
				}
				unset($input["plan"]);
				$input['_need_send_mail']=true;
			}
		}
	}

	function prepareInputForAdd($input) {

		$input["_isadmin"]=haveRight("comment_all_ticket","1");

		$input["_job"]=new Job;
		$input["_job"]->getFromDB($input["tracking"]);

		// Security to add unauthorized followups
		if (!isset($input['_changes_to_log'])&&!$input["_isadmin"]&&$input["_job"]->fields["author"]!=$_SESSION["glpiID"]) return false;

		// Pass old assign From Job in case of assign change
		if (isset($input["_old_assign"])){
			$input["_job"]->fields["_old_assign"]=$input["_old_assign"];
		}


		if (!isset($input["type"])) $input["type"]="followup";
		$input["_type"]=$input["type"];
		unset($input["type"]);

		$input['_close']=0;
		unset($input["add"]);

		if (!isset($input["author"]))
			$input["author"]=$_SESSION["glpiID"];

		if ($input["_isadmin"]&&$input["_type"]!="update"&&$input["_type"]!="finish"){
			if (isset($input['plan'])){
				$input['_plan']=$input['plan'];
				unset($input['plan']);
			}	
			if (isset($input["add_close"])) $input['_close']=1;
			unset($input["add_close"]);

			if ($input["hour"]>0||$input["minute"]>0)
				$input["realtime"]=$input["hour"]+$input["minute"]/60;
		}

		unset($input["minute"]);
		unset($input["hour"]);

		$input["date"] = $_SESSION["glpi_currenttime"];

		return $input;
	}

	function post_addItem($newID,$input) {
		global $CFG_GLPI;

		if (isset($input["realtime"])&&$input["realtime"]>0) {
			$job=new Job();
			$job->getfromDB($input["tracking"]);
			$job->updateRealTime();
		}


		if ($input["_isadmin"]&&$input["_type"]!="update"&&$input["_type"]!="finish"){
			if (isset($input["_plan"])){
				$input["_plan"]['id_followup']=$newID;
				$input["_plan"]['id_tracking']=$input['tracking'];
				$input["_plan"]['_nomail']=1;
				$pt=new PlanningTracking();

				if (!$pt->add($input["_plan"])){
					return false;
				}
			}


			if ($input["_close"]&&$input["_type"]!="update"&&$input["_type"]!="finish"){
				$updates[]="status";
				$updates[]="closedate";
				$input["_job"]->fields["status"]="old_done";
				$input["_job"]->fields["closedate"] = $_SESSION["glpi_currenttime"];
				$input["_job"]->updateInDB($updates);
			}

		}

		if ($CFG_GLPI["mailing"]){
			if ($input["_close"]) $input["_type"]="finish";
			$user=new User;
			$user->getfromDBbyName($_SESSION["glpiname"]);
			$mail = new Mailing($input["_type"],$input["_job"],$user,
						(isset($input["private"])&&$input["private"]));
			$mail->send();
		}
	}


	// SPECIFIC FUNCTIONS

	function getAuthorName($link=0){
		return getUserName($this->fields["author"],$link);
	}	


}



?>
